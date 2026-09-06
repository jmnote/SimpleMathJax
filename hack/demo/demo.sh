#!/usr/bin/env bash
# Local test wiki: the official `mediawiki` Docker image + SQLite (no
# separate DB container), with this repo bind-mounted in as the extension —
# no MediaWiki core checkout needed. See AGENTS.md.
#
# Usage: hack/demo/demo.sh [up|down] [demo]
#        hack/demo/demo.sh screenshot [demo]
# `demo` is a top-level key in hack/demo/demos.yaml (e.g. default01, custom01);
# its capture is saved to docs/screenshots/screenshot-<demo>.png. Defaults
# to `default01`; `screenshot` with no `demo` given screenshots every demo in
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

# Extracts the new revision id from an action=edit API JSON response, so a
# demo with `addDiffShot: true` can link straight to "diff against the
# previous revision" without a second API round-trip to look it up.
edit_new_revid() {
	php -r 'echo json_decode(stream_get_contents(STDIN), true)["edit"]["newrevid"];'
}

# Prints a demo's `settings:` block from docs/demos.yaml — the raw PHP
# lines appended into LocalSettings.php (see up()) and shown on the demo
# page in a <syntaxhighlight lang="php"> block. See render.php.
local_settings_body() {
	php "$DEMO_DIR/render.php" "$DEMO_DIR/demos.yaml" "$1" settings
}

# Prints a demo's `examples:` list from docs/demos.yaml, each rendered as a
# syntaxhighlight block next to its live render, in a responsive flex row.
# See render.php.
render_examples() {
	php "$DEMO_DIR/render.php" "$DEMO_DIR/demos.yaml" "$1" examples
}

# Logs in as Admin and edits a page called "Demo" (not "Main Page", which
# the installer already fills with its own default content) with a small
# demo showing primes inside $...$/$$...$$ surviving wikitext emphasis
# parsing, prefixed with the demo's own `settings:` block (wrapped in
# <syntaxhighlight>) for context. Blanks the page first so the demo edit
# always has an empty previous revision to diff against — a real
# two-column diff, not just a "page creation" summary — regardless of how
# many times this runs against the same wiki (see screenshot_one's
# addDiffShot handling). Re-run on every up so editing a demo's entry in
# docs/demos.yaml and re-running `up` (or `screenshot`) always shows the
# latest content.
seed_demo_page() {
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

	curl -s -b "$jar" -c "$jar" \
		--data-urlencode "action=edit" --data-urlencode "title=Demo" \
		--data-urlencode "text=" \
		--data-urlencode "token=$csrf_token" --data-urlencode "format=json" "$url" >/dev/null

	local page
	page=$(mktemp)
	{
		echo '<syntaxhighlight lang="php">'
		local_settings_body "$demo"
		echo '</syntaxhighlight>'
		echo
		render_examples "$demo"
	} > "$page"

	local edit_response
	edit_response=$(curl -s -b "$jar" -c "$jar" \
		--data-urlencode "action=edit" --data-urlencode "title=Demo" \
		--data-urlencode "text@$page" \
		--data-urlencode "token=$csrf_token" --data-urlencode "format=json" "$url")
	rm -f "$jar" "$page"
	echo "$edit_response" | edit_new_revid > "$DATA/last_revid"
	echo "==> Seeded the Demo page with the $demo demo"
}

wait_for_wiki() {
	# Main Page, not Demo: it's the installer's own default page, so it
	# already exists the moment the wiki responds — unlike Demo, which
	# seed_demo_page hasn't created yet at this point.
	for _ in $(seq 1 30); do
		curl -sf -o /dev/null "http://localhost:$PORT/index.php/Main_Page" && return 0
		sleep 1
	done
	echo "Wiki did not come up in time" >&2
	return 1
}

up() {
	local demo="${1:-default01}"
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
				SimpleMathJax Admin
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
	wait_for_wiki && seed_demo_page "$demo"
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
# docs/screenshots/screenshot-<demo>.png. A demo with `addRightClickShot: true`
# (see render.php) gets one extra screenshot,
# docs/screenshots/screenshot-<demo>-rightclick.png, after right-clicking its
# first mjx-container — needed to show MathJax's context menu, e.g. for
# $wgSmjEnableMenu, since the normal capture never triggers one. A demo with
# `addDiffShot: true` gets a separate extra screenshot,
# docs/screenshots/screenshot-<demo>-diff.png, of the Demo page's diff
# against the blank revision seed_demo_page saves right before its real
# edit — needed to show $wgSmjIgnoreHtmlClass keeping bare-delimiter
# scanning out of diff views, since a diff is a different page/URL entirely,
# not something a click on the normal capture can reveal.
screenshot_one() {
	local demo="$1"
	down
	up "$demo"
	if [ ! -d "$DEMO_DIR/node_modules" ]; then
		echo "==> Installing screenshot dependencies (npm install)"
		( cd "$DEMO_DIR" && npm install )
	fi
	local right_click=""
	if [ "$(php "$DEMO_DIR/render.php" "$DEMO_DIR/demos.yaml" "$demo" addrightclickshot)" = "true" ]; then
		right_click=mjx-container
	fi
	mkdir -p "$PWD/docs/screenshots"
	(
		cd "$DEMO_DIR" &&
		URL="http://localhost:$PORT/index.php/Demo" \
		OUT="$PWD/../../docs/screenshots/screenshot-$demo.png" \
		RIGHT_CLICK="$right_click" \
		RIGHT_CLICK_OUT="$PWD/../../docs/screenshots/screenshot-$demo-rightclick.png" \
		node screenshot.mjs
	)
	if [ "$(php "$DEMO_DIR/render.php" "$DEMO_DIR/demos.yaml" "$demo" adddiffshot)" = "true" ]; then
		local revid
		revid=$(<"$DATA/last_revid")
		(
			cd "$DEMO_DIR" &&
			URL="http://localhost:$PORT/index.php?title=Demo&diff=prev&oldid=$revid" \
			OUT="$PWD/../../docs/screenshots/screenshot-$demo-diff.png" \
			node screenshot.mjs
		)
	fi
}

# Screenshots every demo in docs/demos.yaml when none is named.
screenshot() {
	if [ -n "${1:-}" ]; then
		screenshot_one "$1"
		return
	fi
	# Regenerating every demo: clear old captures first so a demo that got
	# renamed or removed from demos.yaml doesn't leave a stale screenshot
	# behind under its old name.
	rm -f "$PWD/docs/screenshots"/screenshot-*.png
	local demo
	for demo in $(sed -n 's/^- name: //p' "$DEMO_DIR/demos.yaml"); do
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
