#!/usr/bin/env python3
"""Validates an XML document against a RELAX NG schema (the Menota schema, schemas/menotaP5.rng) for the file editor, and lists every problem with its line.

Why Python: PHP's DOMDocument::relaxNGValidate recompiles the schema on every call, and with libxml2 2.9.14 (Debian 12, the web hosts) compiling the 800 KB Menota
schema takes minutes, far past a web request. lxml built on libxml2 2.15 or newer does it in about 4 seconds (measured; libxml2 2.14.6, which the lxml wheels on PyPI
bundle, and 2.9.14 take minutes), so this refuses to run on an older one unless --force is given. It is a command-line tool anywhere lxml with libxml2 2.15+ is installed,
and what schema.php runs for the editor's Validate button on a host that has set one up (IPS_FE_PYTHON):

    validate_xml.py --schema menotaP5.rng --file record.xml [other.xml ...]               # prints each document's problems; exit 0 all valid, 1 some invalid, 2 cannot run
    validate_xml.py --schema menotaP5.rng < record.xml                                  # the document on stdin, the answer as one line of JSON (what schema.php reads)

Named character entities (&aelig; &mdash; ...) that Menota text uses are replaced with their numeric references from menota-entities.txt (next to the schema, or --entities)
before parsing, as schema.php does for the save check. Nothing external is ever loaded: no DTD, no external entity, no network."""

import argparse
import json
import re
import sys
from pathlib import Path

from lxml import etree

XML_ENTITIES = {"amp", "lt", "gt", "quot", "apos"}  # the five every XML parser knows
MIN_LIBXML2 = (2, 15, 0)  # compiling the Menota schema takes minutes below this
MAX_ERRORS = 50
ENTITY_LINE = re.compile(r'<!ENTITY\s+([A-Za-z_][A-Za-z0-9._-]*)\s+"&#x([0-9A-Fa-f]+);"\s*>')
REFERENCE = re.compile(r"&([A-Za-z_][A-Za-z0-9._-]*);")
DECLARED = re.compile(r"<!ENTITY\s+([A-Za-z_][A-Za-z0-9._-]*)\s")


def load_entities(path):
    """name -> hex code point, from menota-entities.txt (empty when the file is not there). Lines look like: <!-- RUNE X --> <!ENTITY name "&#x16CC;">"""
    try:
        text = Path(path).read_text(encoding="utf-8", errors="replace")
    except OSError:
        return {}
    return {name: code for name, code in ENTITY_LINE.findall(text)}


def substitute_entities(text, entities):
    """text with each &name; the Menota list knows, and the document does not declare itself, as the numeric reference it stands for."""
    if not entities or "&" not in text:
        return text
    declared = set(DECLARED.findall(text))

    def replace(match):
        name = match.group(1)
        if name in XML_ENTITIES or name in declared or name not in entities:
            return match.group(0)
        return "&#x" + entities[name] + ";"

    return REFERENCE.sub(replace, text)


def problems(log, limit=MAX_ERRORS):
    out, truncated = [], False
    for entry in log:
        if entry.level < etree.ErrorLevels.ERROR:
            continue
        if len(out) >= limit:
            truncated = True
            break
        out.append({"line": max(0, entry.line), "column": max(0, entry.column), "message": " ".join(entry.message.split())})
    return out, truncated


def validate(text, schema, entities):
    """The result for one document: {valid, stage: None|'parse'|'schema', errors: [{line, column, message}], truncated}. `schema` is a compiled etree.RelaxNG."""
    text = substitute_entities(text, entities)
    # The text is UTF-8 whatever its XML declaration says (the editor holds characters, not bytes): make the declaration agree so it is not decoded twice.
    data = re.sub(r'^(<\?xml[^>]*?)\s+encoding\s*=\s*(["\'])[^"\']*\2', r'\1 encoding="UTF-8"', text, count=1).encode("utf-8")
    parser = etree.XMLParser(resolve_entities=False, no_network=True, load_dtd=False, dtd_validation=False, huge_tree=False)
    try:
        document = etree.fromstring(data, parser).getroottree()
    except etree.XMLSyntaxError as error:
        errors, truncated = problems(error.error_log)
        if not errors:
            errors = [{"line": max(0, error.lineno or 0), "column": max(0, error.offset or 0), "message": str(error)}]
        return {"valid": False, "stage": "parse", "errors": errors, "truncated": truncated}
    if schema.validate(document):
        return {"valid": True, "stage": None, "errors": [], "truncated": False}
    errors, truncated = problems(schema.error_log)
    return {"valid": False, "stage": "schema", "errors": errors, "truncated": truncated}


def libxml2_version():
    return tuple(etree.LIBXML_VERSION)


def main(argv=None):
    ap = argparse.ArgumentParser(description=__doc__.split("\n\n")[0])
    ap.add_argument("--schema", required=True, help="the RELAX NG schema (menotaP5.rng)")
    ap.add_argument("--force", action="store_true", help="run even on a libxml2 older than 2.15 (compiling the Menota schema then takes minutes)")
    ap.add_argument("--entities", help="menota-entities.txt (default: next to the schema)")
    ap.add_argument("--file", nargs="+", help="the documents to check; without it the document is read from stdin and the answer is JSON")
    args = ap.parse_args(argv)
    if libxml2_version() < MIN_LIBXML2 and not args.force:
        message = "this lxml uses libxml2 %s; compiling the Menota schema takes minutes below %s (use an lxml built on libxml2 %s or newer, or --force)" % (
            ".".join(map(str, libxml2_version())), ".".join(map(str, MIN_LIBXML2)), ".".join(map(str, MIN_LIBXML2)))
        print(message if args.file else json.dumps({"error": message}), file=sys.stderr if args.file else sys.stdout)
        return 2
    try:
        schema = etree.RelaxNG(etree.parse(args.schema))
    except (OSError, etree.LxmlError) as error:
        print(json.dumps({"error": "the schema could not be loaded: " + str(error)}) if not args.file else "cannot load the schema: " + str(error), file=sys.stderr if args.file else sys.stdout)
        return 2
    entities = load_entities(args.entities or Path(args.schema).with_name("menota-entities.txt"))
    if not args.file:
        print(json.dumps(validate(sys.stdin.buffer.read().decode("utf-8", errors="replace"), schema, entities), ensure_ascii=False))
        return 0
    status = 0
    for name in args.file:
        try:
            text = Path(name).read_text(encoding="utf-8", errors="replace")
        except OSError as error:
            print(f"{name}: cannot read ({error})", file=sys.stderr)
            return 2
        result = validate(text, schema, entities)
        if result["valid"]:
            print(f"{name}: valid")
            continue
        status = 1
        print(f"{name}: {len(result['errors'])}{'+' if result['truncated'] else ''} problem(s)" + (" (not well-formed XML)" if result["stage"] == "parse" else ""))
        for error in result["errors"]:
            print(f"  line {error['line']}: {error['message']}")
    return status


if __name__ == "__main__":
    sys.exit(main())
