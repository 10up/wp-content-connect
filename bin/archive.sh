#!/usr/bin/env bash
#
# Zip the plugin into release/wp-content-connect.zip: the committed files plus
# the built (git-ignored) dist/ folder, minus anything marked export-ignore in
# .gitattributes. This is the same zip the release workflow attaches to a
# release, so run `npm run build` first.
#
# dist/ is staged in a throwaway index, so your own staging area is untouched.

set -euo pipefail
cd "$( dirname "$0" )/.."

if [ ! -d dist ]; then
	echo "dist/ is missing. Run \`npm run build\` first." >&2
	exit 1
fi

INDEX="$( mktemp )"
trap 'rm -f "$INDEX"' EXIT
cp "$( git rev-parse --git-path index )" "$INDEX"

TREE="$( GIT_INDEX_FILE="$INDEX" bash -c 'git add -f dist && git write-tree' )"

mkdir -p release
git archive --format=zip --prefix=wp-content-connect/ -o release/wp-content-connect.zip "$TREE"
echo "Wrote release/wp-content-connect.zip"
