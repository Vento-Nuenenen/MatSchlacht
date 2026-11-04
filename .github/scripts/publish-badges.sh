#!/usr/bin/env bash
# Publishes the SVG files of a directory to the "badges" branch. The tests and the linter
# workflow run at the same time, so the files of the other workflow are kept and a rejected
# push is retried on top of the new state.
#
# Usage: GITHUB_TOKEN=... GITHUB_REPOSITORY=owner/repo publish-badges.sh <directory>
set -euo pipefail

source_directory=$(realpath "$1")
remote="${BADGES_REMOTE:-https://x-access-token:${GITHUB_TOKEN:-}@github.com/${GITHUB_REPOSITORY:-}.git}"

cd "$(mktemp -d)"
git init -q -b badges
git config user.name 'github-actions[bot]'
git config user.email '41898282+github-actions[bot]@users.noreply.github.com'
git remote add origin "$remote"

for attempt in 1 2 3 4 5; do
    if git fetch -q origin badges 2>/dev/null; then
        git reset -q --hard FETCH_HEAD
    fi

    cp "$source_directory"/*.svg .
    git add -- *.svg

    if git diff --cached --quiet; then
        echo 'Badges are up to date.'
        exit 0
    fi

    git commit -q -m 'Update badges'

    if git push -q origin badges; then
        exit 0
    fi

    sleep $((attempt * 3))
done

echo 'Could not publish the badges.' >&2
exit 1
