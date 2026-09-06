#!/usr/bin/env bash
# Pins local MathJax and the CDN version used by the extension to one tag.
#
# Usage: hack/mathjax.sh <local-version> <cdn-version>
#   e.g. hack/mathjax.sh 4.1.3 4
set -euo pipefail
cd "$(dirname "$0")/.."

MATHJAX_DIR="resources/MathJax"
EXTENSION_JSON="extension.json"
LOCAL_VERSION="${1:-}"
CDN_VERSION="${2:-}"

if [ -z "$LOCAL_VERSION" ] || [ -z "$CDN_VERSION" ]; then
	echo "Usage: hack/mathjax.sh <local-version> <cdn-version>  (e.g. hack/mathjax.sh 4.1.3 4)" >&2
	exit 1
fi

git submodule update --init "$MATHJAX_DIR"
(cd "$MATHJAX_DIR" && git fetch --tags origin && git checkout "tags/$LOCAL_VERSION")
git add "$MATHJAX_DIR"

# Fail loudly rather than silently no-op'ing if extension.json's SmjCdnVersion
# config entry is ever renamed or reshaped again.
if ! grep -A1 '"SmjCdnVersion"' "$EXTENSION_JSON" | grep -q '"value": "[0-9.]'; then
	echo "hack/mathjax.sh: could not find SmjCdnVersion's value in $EXTENSION_JSON" >&2
	exit 1
fi

# Keep this narrow so unrelated manifest formatting remains untouched.
sed -i -E '/"SmjCdnVersion"/,/"value"/ s/("value": ")[0-9.]+/\1'"${CDN_VERSION}"'/' "$EXTENSION_JSON"
git add "$EXTENSION_JSON"

echo "==> Pinned local MathJax to $LOCAL_VERSION and CDN MathJax to $CDN_VERSION."
echo "==> Review with 'git status', then commit the bump."
