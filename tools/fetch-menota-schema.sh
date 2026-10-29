#!/usr/bin/env bash
#
# Downloads the current Menota schema files from menota.org into a folder, for the file editor's XML validator (ips/file_editor/schema.php):
#
#   menotaP5.rng          the RELAX NG schema the validator checks a document against (Menota Handbook 3.0, a customisation of TEI P5)
#   menotaP5.xml          the ODD source it was generated from (kept for provenance; the validator does not read it)
#   menota-entities.txt   the list of named character entities (&ae; ...) Menota text uses, which the validator replaces with their characters
#   SOURCE.txt            where each file came from, when, and its SHA-256, so a change upstream shows
#
# menota.org states no licence for these files, so they are not kept in this repository. Run this against a folder outside it, for example the shared vendor folder on the file
# share (/mnt/www/_vendor/menota-3.0), and give each site the one schemas/ folder it needs by the usual sync. Run it again to update; a download that is not well-formed XML,
# or a schema libxml2 cannot load, replaces nothing.
#
# Usage: tools/fetch-menota-schema.sh <folder>      (needs curl and xmllint)
set -euo pipefail

BASE=https://www.menota.org
FILES="menotaP5.rng menotaP5.xml menota-entities.txt"

[ $# -eq 1 ] || { echo "usage: $0 <folder to put the schema in>" >&2; exit 2; }
mkdir -p "$1"
dest="$(cd "$1" && pwd)"
repo="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
case "$dest/" in
  "$repo"/*) echo "refusing to write inside the mssdocs repository: the schema has no stated licence" >&2; exit 2 ;;
esac
work="$(mktemp -d)"
trap 'rm -rf "$work"' EXIT

for f in $FILES; do
  curl -fsSL --max-time 120 -o "$work/$f" "$BASE/$f"
  [ -s "$work/$f" ] || { echo "$f came back empty" >&2; exit 1; }
done
xmllint --noout "$work/menotaP5.rng" "$work/menotaP5.xml"
# the schema must be one libxml2 (PHP's and Python's validator) can load: the 2016 build was not (a compile error shows even for an unrelated test document)
printf '<TEI xmlns="http://www.tei-c.org/ns/1.0"/>' > "$work/probe.xml"
xmllint --noout --relaxng "$work/menotaP5.rng" "$work/probe.xml" >/dev/null 2>"$work/rng.err" || true
if grep -q -i "parser error\|failed to compile\|forbidden pattern" "$work/rng.err"; then
  echo "libxml2 cannot load the downloaded schema:" >&2; head -3 "$work/rng.err" >&2; exit 1
fi
grep -c "<!ENTITY" "$work/menota-entities.txt" >/dev/null || { echo "menota-entities.txt holds no entities" >&2; exit 1; }

{
  echo "Fetched from $BASE on $(date -u +%Y-%m-%dT%H:%M:%SZ) by tools/fetch-menota-schema.sh."
  echo "Menota (https://www.menota.org/) states no licence for these files; they are not committed to the mssdocs repository."
  echo
  for f in $FILES; do echo "$(sha256sum "$work/$f" | cut -d' ' -f1)  $f  ($(wc -c <"$work/$f") bytes, $BASE/$f)"; done
  echo
  head -c 2000 "$work/menotaP5.rng" | tr -s ' \t\n' ' ' | grep -o 'Schema generated from ODD source[^>]*' | sed 's/ *--$//' | head -1
  echo
} > "$work/SOURCE.txt"

for f in $FILES SOURCE.txt; do install -m 664 "$work/$f" "$dest/$f"; done
echo "Menota schema installed in $dest:"
cat "$dest/SOURCE.txt"
