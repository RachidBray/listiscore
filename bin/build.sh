#!/usr/bin/env bash
#
# Builds a clean, wp.org-ready copy of the plugin (respecting .distignore)
# into build/listing-health-score/, then zips it.
#
# Starts from `git archive` (exactly what's committed at HEAD), not a plain
# directory copy: local-only files that exist on disk but were never meant
# to ship (editor cruft, local dev notes, anything gitignored) can never
# leak into the build this way, regardless of what is or isn't listed in
# .distignore. .distignore only needs to cover tracked files that shouldn't
# ship (tests/, composer.json, etc.).
#
# Tools like Plugin Check scan whatever directory they're pointed at as-is —
# they don't know about .distignore, which is only a convention honored by
# wp.org's own SVN/deploy tooling. Running Plugin Check against this repo
# directly will always flag tests/, vendor/, .github, etc. Run it
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

git -C "$ROOT_DIR" archive HEAD | tar -x -C "$DEST_DIR"

# Remove tracked-but-non-shipping paths (tests/, composer.json, etc.) from
# the extracted tree.
while IFS= read -r pattern; do
	[ -z "$pattern" ] && continue
	case "$pattern" in \#*) continue ;; esac
	find "$DEST_DIR" -path "$DEST_DIR/${pattern#/}" -prune -exec rm -rf {} +
done < "$ROOT_DIR/.distignore"

cd "$BUILD_DIR"
zip -rq "$SLUG.zip" "$SLUG"

echo "Built $BUILD_DIR/$SLUG.zip"
echo "Install/scan $DEST_DIR (or the zip) with Plugin Check for an accurate result."
