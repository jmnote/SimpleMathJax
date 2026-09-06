#!/usr/bin/env bash
# Pins the bundled local MathJax submodule to one tag.
#
# The CDN version ($wgSmjCdnVersion's default in extension.json) is managed
# independently — edit that value directly, the same as any other config
# default — since it doesn't have to track the local submodule's version.
#
# Usage: hack/local-mathjax.sh <version>
#   e.g. hack/local-mathjax.sh 4.1.3
set -euo pipefail
cd "$(dirname "$0")/.."

MATHJAX_DIR="resources/MathJax"
LOCAL_VERSION="${1:-}"

if [ -z "$LOCAL_VERSION" ]; then
	echo "Usage: hack/local-mathjax.sh <version>  (e.g. hack/local-mathjax.sh 4.1.3)" >&2
	exit 1
fi

git submodule update --init "$MATHJAX_DIR"
(cd "$MATHJAX_DIR" && git fetch --tags origin && git checkout "tags/$LOCAL_VERSION")
git add "$MATHJAX_DIR"

echo "==> Pinned local MathJax to $LOCAL_VERSION."
echo "==> Review with 'git status', then commit the bump."
