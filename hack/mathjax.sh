#!/usr/bin/env bash
# Pins the resources/MathJax submodule to a tag and updates the CDN URL's
# major version in resources/ext.SimpleMathJax.js. The two are versioned
# separately on purpose: the local copy is pinned to an exact tag, while
# the CDN URL only pins a major version (jsdelivr resolves it to the
# latest matching release).
#
# Usage: hack/mathjax.sh <local-version> <cdn-major-version>
#   e.g. hack/mathjax.sh 4.1.3 4
set -euo pipefail
cd "$(dirname "$0")/.."

MATHJAX_DIR="resources/MathJax"
JS_FILE="resources/ext.SimpleMathJax.js"
LOCAL_VERSION="${1:-}"
CDN_VERSION="${2:-}"

if [ -z "$LOCAL_VERSION" ] || [ -z "$CDN_VERSION" ]; then
	echo "Usage: hack/mathjax.sh <local-version> <cdn-major-version>  (e.g. hack/mathjax.sh 4.1.3 4)" >&2
	exit 1
fi

git submodule update --init "$MATHJAX_DIR"
(cd "$MATHJAX_DIR" && git fetch --tags origin && git checkout "tags/$LOCAL_VERSION")
git add "$MATHJAX_DIR"

sed -i -E "s#(cdn\.jsdelivr\.net/npm/mathjax@)[^/]+#\1${CDN_VERSION}#" "$JS_FILE"
git add "$JS_FILE"

echo "==> Pinned $MATHJAX_DIR to $LOCAL_VERSION and the CDN URL to mathjax@$CDN_VERSION."
echo "==> Review with 'git status', then commit the bump."
