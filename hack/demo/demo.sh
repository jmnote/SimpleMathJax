#!/usr/bin/env bash
# Local test wiki: the official `mediawiki` Docker image + SQLite (no
# separate DB container), with this repo bind-mounted in as the extension —
# no MediaWiki core checkout needed. See AGENTS.md.
#
# Usage: hack/demo/demo.sh [up|down] [demo]
#        hack/demo/demo.sh screenshot [demo]
# `demo` is a top-level key in docs/demos.yaml (e.g. default, directMath);
# its capture is saved to docs/screenshots/screenshot-<demo>.png. Defaults
# to `default`; `screenshot` with no `demo` given screenshots every demo in
# demos.yaml.
set -euo pipefail
DEMO_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$DEMO_DIR/../.."

IMAGE=mediawiki:1.43
NAME=demo
PORT=8080
DATA="$PWD/hack/demo/temp"
DOCKER_USER=33:33
PASS=demo12345678

# Extracts a query.tokens.* field from an API JSON response (properly
# unescaped — plain grep/sed would mangle tokens containing backslashes).
# Uses php since this project already requires it, rather than reaching for
# another language just for a one-line JSON parse.
json_field() {
	php -r 'echo json_decode(stream_get_contents(STDIN), true)["query"]["tokens"][$argv[1]];' "$1"
}

# Prints a demo's `settings:` block from docs/demos.yaml — the raw PHP
# lines appended into LocalSettings.php (see up()) and shown on the demo
# page in a <syntaxhighlight lang="php"> block. See render.php.
local_settings_body() {
	php "$DEMO_DIR/render.php" docs/demos.yaml "$1" settings
}

# Prints a demo's `examples:` list from docs/demos.yaml, each rendered as a
# syntaxhighlight block next to its live render, in a responsive flex row.
# See render.php.
render_examples() {
	php "$DEMO_DIR/render.php" docs/demos.yaml "$1" examples
}

# Logs in as Admin and edits Main Page with a small demo showing primes
# inside $...$/$$...$$ surviving wikitext emphasis parsing, prefixed with the
# demo's own `settings:` block (wrapped in <syntaxhighlight>) for
# context. Re-run on every up so editing a demo's entry in docs/demos.yaml and
# re-running `up` (or `screenshot`) always shows the latest content.
seed_main_page() {
	local demo="$1"
	local jar url="http://localhost:$PORT/api.php"
	jar=$(mktemp)
	local login_token
	login_token=$(curl -s -c "$jar" "$url?action=query&meta=tokens&type=login&format=json" | json_field logintoken)
	curl -s -b "$jar" -c "$jar" \
		--data-urlencode "action=login" --data-urlencode "lgname=Admin" \
		--data-urlencode "lgpassword=$PASS" --data-urlencode "lgtoken=$login_token" \
		--data-urlencode "format=json" "$url" >/dev/null
	local csrf_token
	csrf_token=$(curl -s -b "$jar" -c "$jar" "$url?action=query&meta=tokens&format=json" | json_field csrftoken)

	local page
	page=$(mktemp)
	{
		echo '<syntaxhighlight lang="php">'
		local_settings_body "$demo"
		echo '</syntaxhighlight>'
		echo
		render_examples "$demo"
	} > "$page"

	curl -s -b "$jar" -c "$jar" \
		--data-urlencode "action=edit" --data-urlencode "title=Main Page" \
		--data-urlencode "text@$page" \
		--data-urlencode "token=$csrf_token" --data-urlencode "format=json" "$url" >/dev/null
	rm -f "$jar" "$page"
	echo "==> Seeded Main Page with the $demo demo"
}

wait_for_wiki() {
	for _ in $(seq 1 30); do
		curl -sf -o /dev/null "http://localhost:$PORT/index.php/Main_Page" && return 0
		sleep 1
	done
	echo "Wiki did not come up in time" >&2
	return 1
}

up() {
	local demo="${1:-default}"
	mkdir -p "$DATA"
	chmod 777 "$DATA"
	if [ ! -f "$DATA/LocalSettings.php" ]; then
		echo "==> First run: installing MediaWiki into $DATA"
		docker run --rm --user "$DOCKER_USER" \
			-v "$DATA:/var/www/html/data" \
			-v "$PWD:/var/www/html/extensions/SimpleMathJax:ro" \
			"$IMAGE" \
			php maintenance/run.php install \
				--confpath /var/www/html/data \
				--dbtype sqlite --dbpath /var/www/html/data \
				--scriptpath "" --server "http://localhost:$PORT" \
				--pass "$PASS" \
				--extensions SyntaxHighlight_GeSHi \
				"SimpleMathJax Demo" Admin
		local_settings_body "$demo" >> "$DATA/LocalSettings.php"
	fi
	docker rm -f "$NAME" >/dev/null 2>&1 || true
	docker run --rm -d --name "$NAME" --user "$DOCKER_USER" \
		-p "$PORT:80" \
		-v "$DATA:/var/www/html/data" \
		-v "$DATA/LocalSettings.php:/var/www/html/LocalSettings.php:ro" \
		-v "$PWD:/var/www/html/extensions/SimpleMathJax:ro" \
		"$IMAGE"
	echo "==> Wiki running at http://localhost:$PORT (Admin / $PASS)"
	wait_for_wiki && seed_main_page "$demo"
}

# Stops the container and wipes its data, so the next `up` reinstalls fresh
# (also how you pick up changes to a demo's `settings:` block, which is
# only applied during install).
down() {
	docker rm -f "$NAME" >/dev/null 2>&1 || true
	rm -rf "$DATA"
}

# Screenshots one demo's page with a real browser (Puppeteer); see
# screenshot.mjs. Forces a fresh install (down, then up) so the demo's own
# `settings:` block is guaranteed to be the one in effect, then saves to
# docs/screenshots/screenshot-<demo>.png. A demo with `addMenuShot: true`
# (see render.php) gets one extra screenshot,
# docs/screenshots/screenshot-<demo>-menu.png, after right-clicking its
# first mjx-container — needed to show MathJax's context menu, e.g. for
# $wgSmjEnableMenu, since the normal capture never triggers one.
screenshot_one() {
	local demo="$1"
	down
	up "$demo"
	if [ ! -d "$DEMO_DIR/node_modules" ]; then
		echo "==> Installing screenshot dependencies (npm install)"
		( cd "$DEMO_DIR" && npm install )
	fi
	local right_click=""
	if [ "$(php "$DEMO_DIR/render.php" docs/demos.yaml "$demo" addmenushot)" = "true" ]; then
		right_click=mjx-container
	fi
	mkdir -p "$PWD/docs/screenshots"
	(
		cd "$DEMO_DIR" &&
		OUT="$PWD/../../docs/screenshots/screenshot-$demo.png" \
		RIGHT_CLICK="$right_click" \
		RIGHT_CLICK_OUT="$PWD/../../docs/screenshots/screenshot-$demo-menu.png" \
		node screenshot.mjs
	)
}

# Screenshots every demo in docs/demos.yaml when none is named.
screenshot() {
	if [ -n "${1:-}" ]; then
		screenshot_one "$1"
		return
	fi
	local demo
	for demo in $(sed -n 's/^- name: //p' docs/demos.yaml); do
		screenshot_one "$demo"
	done
}

case "${1:-up}" in
	up) up "${2:-}" ;;
	down) down ;;
	screenshot) screenshot "${2:-}" ;;
	*)
		echo "Usage: $0 [up|down] [demo]  |  $0 screenshot [demo]" >&2
		exit 1
		;;
esac
