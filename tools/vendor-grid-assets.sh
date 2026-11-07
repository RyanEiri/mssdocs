#!/usr/bin/env bash
#
# Re-creates the spreadsheet grid the CSV file type uses, from pinned npm packages, so its provenance is reproducible and nothing is loaded from a CDN at run time:
#
#   ips/js/jspreadsheet/index.js           Jspreadsheet CE (MIT), the global (UMD) build
#   ips/js/jspreadsheet/jspreadsheet.css
#   ips/js/jspreadsheet/jsuites.js         jsuites (MIT), which Jspreadsheet CE needs (editors, dropdowns, context menu)
#   ips/js/jspreadsheet/jsuites.css
#   ips/js/jspreadsheet/LICENSE-*.txt
#
# Usage: tools/vendor-grid-assets.sh      (needs node and npm; run it again only to change a version)
set -euo pipefail

JSPREADSHEET=5.0.4
JSUITES=6.6.1

here="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
out="$here/ips/js/jspreadsheet"
work="$(mktemp -d)"
trap 'rm -rf "$work"' EXIT
cd "$work"
npm init -y >/dev/null
npm install --silent "jspreadsheet-ce@$JSPREADSHEET" "jsuites@$JSUITES"

mkdir -p "$out"
rm -f "$out"/*
install -m 0644 node_modules/jspreadsheet-ce/dist/index.js "$out/index.js"
install -m 0644 node_modules/jspreadsheet-ce/dist/jspreadsheet.css "$out/jspreadsheet.css"
install -m 0644 node_modules/jsuites/dist/jsuites.js "$out/jsuites.js"
install -m 0644 node_modules/jsuites/dist/jsuites.css "$out/jsuites.css"
install -m 0644 node_modules/jspreadsheet-ce/LICENSE "$out/LICENSE-jspreadsheet-ce.txt"
install -m 0644 node_modules/jsuites/LICENSE "$out/LICENSE-jsuites.txt"

echo "jspreadsheet-ce $JSPREADSHEET, jsuites $JSUITES: $(du -ch "$out"/*.js "$out"/*.css | tail -1 | cut -f1) in ips/js/jspreadsheet"
