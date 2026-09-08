#!/usr/bin/env bash
# Local test wiki: the official `mediawiki` Docker image + SQLite (no
# separate DB container), with this repo bind-mounted in as the extension —
# no MediaWiki core checkout needed. See AGENTS.md. VisualEditor is also
# installed (bundled with the image, so no extra download) so
# resources/ve/*.js can be exercised by hand at ?veaction=edit; nothing here
# exercises it automatically, since screenshot() only ever edits through the
# API (see seed_demo_page), not VisualEditor.
#
# Usage: hack/demo/demo.sh [up|down] [demo]
#        hack/demo/demo.sh screenshot [demo]
#        hack/demo/demo.sh animations [version]
#        MW_VERSION=1.45 hack/demo/demo.sh screenshot [demo]
# `demo` is a top-level key in hack/demo/demo-screenshots.yaml (e.g. default01, custom01);
# its capture is saved to docs/screenshots/$MW_VERSION/screenshot-<demo>.png.
# Defaults to `default01`; `screenshot` with no `demo` given screenshots
# every demo in demo-screenshots.yaml. MW_VERSION selects the `mediawiki` Docker image
# tag to test against (default 1.43) — e.g. to check whether a bug is
# specific to one MediaWiki version. `animations` takes its version the
# same way as `screenshot` takes a demo - as a plain argument, not
# MW_VERSION - and with none given loops over every version in
# demo-animations.yaml's own `mediawiki: versions:` list instead of
# defaulting to one (see animations() below).
set -euo pipefail
DEMO_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$DEMO_DIR/../.."

MW_VERSION="${MW_VERSION:-1.43}"
IMAGE="mediawiki:$MW_VERSION"
NAME=demo
PORT=8080
DATA="$PWD/hack/demo/temp"
DOCKER_USER=33:33
PASS=demo12345678
PAGE_TITLE=SimpleMathJax
SCREENSHOT_DIR="$PWD/docs/screenshots/$MW_VERSION"

# Encodes the configured page title for index.php query-string URLs. API
# requests below use curl's --data-urlencode instead.
PAGE_TITLE_URL=$(php -r 'echo rawurlencode( $argv[1] );' "$PAGE_TITLE")

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

# Prints a demo's `settings:` block from docs/demo-screenshots.yaml — the raw PHP
# lines appended into LocalSettings.php (see up()) and shown on the demo
# page in a <syntaxhighlight lang="php"> block. See render.php.
local_settings_body() {
	php "$DEMO_DIR/render.php" "$DEMO_DIR/demo-screenshots.yaml" "$1" settings
}

# Prints a demo's `examples:` list from docs/demo-screenshots.yaml, each rendered as a
# syntaxhighlight block next to its live render, in a responsive flex row.
# See render.php.
render_examples() {
	php "$DEMO_DIR/render.php" "$DEMO_DIR/demo-screenshots.yaml" "$1" examples
}

# Logs in as Admin and edits the configured demo page (not "Main Page", which
# the installer already fills with its own default content) with a small
# demo showing primes inside $...$/$$...$$ surviving wikitext emphasis
# parsing, prefixed with a MediaWiki {{CURRENTVERSION}}/SimpleMathJax
# version line (so a screenshot alone shows what it was captured against —
# useful once screenshots exist for more than one MediaWiki version, see
# MW_VERSION) and the demo's own `settings:` block (wrapped in
# <syntaxhighlight>) for context. Blanks the page first so the demo edit
# always has an empty previous revision to diff against — a real
# two-column diff, not just a "page creation" summary — regardless of how
# many times this runs against the same wiki (see screenshot_one's
# addDiffShot handling). Re-run on every up so editing a demo's entry in
# docs/demo-screenshots.yaml and re-running `up` (or `screenshot`) always shows the
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
		--data-urlencode "action=edit" --data-urlencode "title=$PAGE_TITLE" \
		--data-urlencode "text=" \
		--data-urlencode "token=$csrf_token" --data-urlencode "format=json" "$url" >/dev/null

	local smj_version
	smj_version=$(php -r 'echo json_decode(file_get_contents($argv[1]), true)["version"];' "$PWD/extension.json")

	local page
	page=$(mktemp)
	{
		echo "SimpleMathJax $smj_version, MediaWiki {{CURRENTVERSION}}"
		echo
		echo '<syntaxhighlight lang="php">'
		local_settings_body "$demo"
		echo '</syntaxhighlight>'
		echo
		render_examples "$demo"
	} > "$page"

	local edit_response
	edit_response=$(curl -s -b "$jar" -c "$jar" \
		--data-urlencode "action=edit" --data-urlencode "title=$PAGE_TITLE" \
		--data-urlencode "text@$page" \
		--data-urlencode "token=$csrf_token" --data-urlencode "format=json" "$url")
	rm -f "$jar" "$page"
	echo "$edit_response" | edit_new_revid > "$DATA/last_revid"
	echo "==> Seeded the $PAGE_TITLE page with the $demo demo"
}

wait_for_wiki() {
	# Main Page, not the configured demo page: it's the installer's own
	# default page, so it already exists the moment the wiki responds — unlike
	# the demo page, which seed_demo_page hasn't created yet at this point.
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
				--extensions SyntaxHighlight_GeSHi,VisualEditor \
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
# $SCREENSHOT_DIR/screenshot-<demo>.png (docs/screenshots/$MW_VERSION by
# default). A demo with `addRightClickShot: true` (see render.php) gets one
# extra screenshot, screenshot-<demo>-rightclick.png, after right-clicking
# its first mjx-container — needed to show MathJax's context menu, e.g. for
# $wgSmjEnableMenu, since the normal capture never triggers one. A demo with
# `addDiffShot: true` gets a separate extra screenshot,
# screenshot-<demo>-diff.png, of the demo page's diff against the blank
# revision seed_demo_page saves right before its real edit — needed to show
# $wgSmjIgnoreHtmlClass keeping bare-delimiter scanning out of diff views,
# since a diff is a different page/URL entirely, not something a click on
# the normal capture can reveal.
screenshot_one() {
	local demo="$1"
	down
	up "$demo"
	if [ ! -d "$DEMO_DIR/node_modules" ]; then
		echo "==> Installing screenshot dependencies (npm install)"
		( cd "$DEMO_DIR" && npm install )
	fi
	local right_click=""
	if [ "$(php "$DEMO_DIR/render.php" "$DEMO_DIR/demo-screenshots.yaml" "$demo" addrightclickshot)" = "true" ]; then
		right_click=mjx-container
	fi
	mkdir -p "$SCREENSHOT_DIR"
	(
		cd "$DEMO_DIR" &&
		URL="http://localhost:$PORT/index.php?title=$PAGE_TITLE_URL" \
		OUT="$SCREENSHOT_DIR/screenshot-$demo.png" \
		RIGHT_CLICK="$right_click" \
		RIGHT_CLICK_OUT="$SCREENSHOT_DIR/screenshot-$demo-rightclick.png" \
		node screenshot.mjs
	)
	if [ "$(php "$DEMO_DIR/render.php" "$DEMO_DIR/demo-screenshots.yaml" "$demo" adddiffshot)" = "true" ]; then
		local revid
		revid=$(<"$DATA/last_revid")
		(
			cd "$DEMO_DIR" &&
			URL="http://localhost:$PORT/index.php?title=$PAGE_TITLE_URL&diff=prev&oldid=$revid" \
			OUT="$SCREENSHOT_DIR/screenshot-$demo-diff.png" \
			node screenshot.mjs
		)
	fi
}

# Screenshots every demo in docs/demo-screenshots.yaml when none is named.
screenshot() {
	if [ -n "${1:-}" ]; then
		screenshot_one "$1"
		return
	fi
	# Regenerating every demo: clear old captures first so a demo that got
	# renamed or removed from demo-screenshots.yaml doesn't leave a stale screenshot
	# behind under its old name.
	mkdir -p "$SCREENSHOT_DIR"
	rm -f "$SCREENSHOT_DIR"/screenshot-*.png
	local demo
	for demo in $(sed -n 's/^- name: //p' "$DEMO_DIR/demo-screenshots.yaml"); do
		screenshot_one "$demo"
	done
}

# demo-animations.yaml's optional top-level `mediawiki: versions:` list -
# only consulted when animations() itself is asked to pick the version (no
# version argument given), and only to decide which versions to loop over
# then; falls back to just the one already-defaulted $MW_VERSION if that
# block is missing.
animation_versions() {
	local versions
	versions=$(awk '/^mediawiki:/ { f=1; next } /^[a-zA-Z]/ { f=0 } f' "$DEMO_DIR/demo-animations.yaml" |
		sed -n 's/^[[:space:]]*-[[:space:]]*//p')
	echo "${versions:-$MW_VERSION}"
}

# Records docs/animations/$MW_VERSION/animation-<name>.gif for each doc in
# demo-animations.yaml: inserting that doc's formulas via VisualEditor's
# Insert menu (see resources/ve/*.js), then saving the page. Forces a
# fresh install (down, then up) so it always starts from the same blank
# state. Shares screenshot_one's node_modules (puppeteer) plus its own
# gif-encoding deps, so also covered by that one `npm install`.
animations_one() {
	IMAGE="mediawiki:$MW_VERSION"
	down
	up
	if [ ! -d "$DEMO_DIR/node_modules" ]; then
		echo "==> Installing animation dependencies (npm install)"
		( cd "$DEMO_DIR" && npm install )
	fi
	local animation_dir="$PWD/docs/animations/$MW_VERSION"
	mkdir -p "$animation_dir"
	(
		cd "$DEMO_DIR" &&
		URL="http://localhost:$PORT" \
		OUT_DIR="$animation_dir" \
		node ve_animation.mjs
	)
}

# Runs animations_one for a single explicit version (given as an argument,
# same as screenshot's demo argument - not MW_VERSION), or, with none
# given, once per version in demo-animations.yaml's own
# `mediawiki: versions:` list.
animations() {
	if [ -n "${1:-}" ]; then
		MW_VERSION="$1" animations_one
		return
	fi
	local v
	for v in $(animation_versions); do
		MW_VERSION="$v" animations_one
	done
}

case "${1:-up}" in
	up) up "${2:-}" ;;
	down) down ;;
	screenshot) screenshot "${2:-}" ;;
	animations) animations "${2:-}" ;;
	*)
		echo "Usage: $0 [up|down] [demo]  |  $0 screenshot [demo]  |  $0 animations [version]" >&2
		exit 1
		;;
esac
