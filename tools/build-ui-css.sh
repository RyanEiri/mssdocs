#!/usr/bin/env bash
#
# Compile ips/css/ips-ui.css, the admin UI's stylesheet, so its pages load no CDN scripts.
#
# Usage: tools/build-ui-css.sh
#
# Runs the Tailwind v4 CLI (installed with npm into a throwaway directory) against the UI's own sources
# (ips/js/ui/*.js and the ips/html/app-*.php templates), with the tokens from tools/ips-ui.css. The build emits only
# the classes it can see in those sources, so a class reachable only through a conditional expression can be dropped:
# re-run it whenever a Tailwind class is added or removed, and check the pages afterwards.
set -euo pipefail

here="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
out="$here/ips/css/ips-ui.css"
work="$(mktemp -d)"
trap 'rm -rf "$work"' EXIT
cd "$work"
npm init -y >/dev/null
npm install --silent tailwindcss @tailwindcss/cli
{
  printf '@source "%s";\n' "$here/ips/js/ui"
  printf '@source "%s";\n' "$here/ips/html"
  cat "$here/tools/ips-ui.css"
} > input.css
npx @tailwindcss/cli -i input.css -o "$out" --minify
echo "wrote $out ($(wc -c < "$out") bytes)"
