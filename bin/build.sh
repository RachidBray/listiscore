#!/usr/bin/env bash
#
# Builds a clean, wp.org-ready copy of the plugin (respecting .distignore)
# into build/listing-health-score/, then zips it.
#
# Tools like Plugin Check scan whatever directory they're pointed at as-is —
# they don't know about .distignore, which is only a convention honored by
# wp.org's own SVN/deploy tooling. Running Plugin Check against this repo
# directly will always flag tests/, vendor/, AGENTS.md, .github, etc. Run it
# against this script's output instead for a result that reflects what
# actually ships.
#
# Usage: composer build   (or: bash bin/build.sh)

set -euo pipefail

SLUG="listing-health-score"
ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BUILD_DIR="$ROOT_DIR/build"
DEST_DIR="$BUILD_DIR/$SLUG"

rm -rf "$BUILD_DIR"
mkdir -p "$DEST_DIR"

rsync -a \
	--exclude '/build' \
	--exclude-from="$ROOT_DIR/.distignore" \
	"$ROOT_DIR/" "$DEST_DIR/"

cd "$BUILD_DIR"
zip -rq "$SLUG.zip" "$SLUG"

echo "Built $BUILD_DIR/$SLUG.zip"
echo "Install/scan $DEST_DIR (or the zip) with Plugin Check for an accurate result."
