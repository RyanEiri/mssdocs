#!/usr/bin/env python3
"""Renders a TEI manuscript description (a record with a msDesc, as the file editor catalogues it) as an HTML page or a PDF, in a chosen style.

    render-tei.py record.xml --format html --style catalogue --out out/        # out/<id>.html
    render-tei.py record.xml --format pdf  --style plain     --out out/        # out/<id>.pdf   (pdfLaTeX)
    render-tei.py record.xml --format tex                    --out out/        # out/<id>.tex   (the LaTeX source)

What is rendered is the RAD description of the manuscript (the XHTML that ips/file_editor/metadata/tei-to-rad.xsl makes, so a printed catalogue entry says what the editor's Metadata panel
shows) followed by the text of the TEI <body>, if the file has any. The page and the PDF are made by XSLT 1.0 (document-to-html.xsl, document-to-latex.xsl) from one <document> this
tool assembles; the style is a CSS file (HTML) or a LaTeX preamble (PDF) in styles/. Only a record whose status is `published` is rendered (revisionDesc/@status; none is a draft, see
D9 of docs/mssdocs-metadata-mapping.md): --allow-unpublished overrides it for a working copy.

LaTeX: the text is escaped here (XSLT 1.0 cannot do it safely): every text node and the attributes the stylesheet prints, with the TeX specials made harmless and the characters the Type 1
Latin Modern fonts lack (runes, MUFI characters, most of Latin Extended-B) replaced by [U+XXXX] and listed, so a compile never stops on an unknown character. The PDF is made with pdfLaTeX
(-no-shell-escape): LuaLaTeX with fontspec cannot load OpenType fonts on every machine. A tool for the controller, not for the web hosts.

Exit status: 0 done, 2 cannot run (bad arguments, no pdflatex), 3 the record is not published, 4 the record cannot be described (not XML, no msDesc), 5 LaTeX failed.
"""

import argparse
import copy
import re
import shutil
import subprocess
import sys
import tempfile
from pathlib import Path

from lxml import etree

HERE = Path(__file__).resolve().parent
METADATA = HERE.parent.parent / "ips" / "file_editor" / "metadata"
STYLES = HERE / "styles"
TEI = "http://www.tei-c.org/ns/1.0"
STYLE_NAMES = sorted(p.stem for p in STYLES.glob("*.tex"))

# TeX specials (the order does not matter: one pass over the characters)
TEX_SPECIALS = {
    "\\": r"\textbackslash{}", "{": r"\{", "}": r"\}", "&": r"\&", "%": r"\%", "$": r"\$", "#": r"\#", "_": r"\_", "^": r"\textasciicircum{}", "~": r"\textasciitilde{}",
}
# characters outside Latin-1 and Latin Extended-A that T1 can set, and Old Norse letters made with an accent command
TEX_EXTRA = {
    "–": "--", "—": "---", "‘": "`", "’": "'", "‚": ",", "“": "``", "”": "''", "„": ",,", "†": r"\dag{}", "‡": r"\ddag{}",
    "…": r"\ldots{}", "‰": r"\textperthousand{}", "‹": r"\guilsinglleft{}", "›": r"\guilsinglright{}", "€": r"\texteuro{}",
    "ǫ": r"\k{o}", "Ǫ": r"\k{O}",
}
LATIN_RANGE = (0x20, 0x7E), (0xA0, 0x17F)


def tex_escape(text, missing=None):
    """`text` as safe LaTeX. A character the fonts cannot set becomes [U+XXXX] and is added to `missing` (a set) when one is given."""
    out = []
    for ch in text:
        code = ord(ch)
        if ch in TEX_SPECIALS:
            out.append(TEX_SPECIALS[ch])
        elif ch in TEX_EXTRA:
            out.append(TEX_EXTRA[ch])
        elif ch in "\n\r\t" or ch == "\u00a0":
            out.append(" ")
        elif ch == "\u00ad":  # soft hyphen
            out.append(r"\-")
        elif any(low <= code <= high for low, high in LATIN_RANGE):
            out.append(ch)
        else:
            if missing is not None:
                missing.add(ch)
            out.append("[U+%04X]" % code)
    return "".join(out)


def load_entities(path):
    """name -> hex code point from menota-entities.txt, for a file that uses Menota's named character entities."""
    pattern = re.compile(r'<!ENTITY\s+([A-Za-z_][A-Za-z0-9._-]*)\s+"&#x([0-9A-Fa-f]+);"\s*>')
    return dict(pattern.findall(Path(path).read_text(encoding="utf-8", errors="replace")))


def parse_tei(path, entities=None):
    """The TEI document: no network, no DTD, no entity expansion. Named entities are replaced from `entities` first when one is given."""
    data = Path(path).read_text(encoding="utf-8")
    if entities:
        declared = set(re.findall(r"<!ENTITY\s+([A-Za-z_][A-Za-z0-9._-]*)\s", data))
        data = re.sub(r"&([A-Za-z_][A-Za-z0-9._-]*);", lambda m: m.group(0) if m.group(1) in {"amp", "lt", "gt", "quot", "apos"} | declared or m.group(1) not in entities else "&#x" + entities[m.group(1)] + ";", data)
    data = re.sub(r'^(<\?xml[^>]*?)\s+encoding\s*=\s*(["\'])[^"\']*\2', r'\1 encoding="UTF-8"', data, count=1)
    parser = etree.XMLParser(resolve_entities=False, no_network=True, load_dtd=False, huge_tree=False)
    return etree.fromstring(data.encode("utf-8"), parser).getroottree()


def sheet(name, folder=METADATA):
    return etree.XSLT(etree.parse(str(folder / name)))


def has_text(body):
    """Whether the <body> holds any text or element content worth printing."""
    return body is not None and bool("".join(body.itertext()).strip() or body.xpath(".//*[self::t:pb or self::t:lb or self::t:list]", namespaces={"t": TEI}))


def assemble(tei):
    """The <document> the render stylesheets read, or (None, message) when the record cannot be described. Returns (document, status, dc_record)."""
    rad = sheet("tei-to-rad.xsl")(tei)
    dc = sheet("tei-to-dc.xsl")(tei).getroot()
    if rad.getroot() is None or dc is None:
        return None, None, None
    dc_ns = {"dc": "http://purl.org/dc/elements/1.1/"}
    first = lambda path: (dc.xpath(path, namespaces=dc_ns) or [""])[0]
    document = etree.Element("document", id=dc.get("id") or "", status=dc.get("status"), lang=dc.get("lang") or "")
    for name, path in (("title", "string(dc:title)"), ("shelfmark", "string(dc:identifier)"), ("holder", "string(dc:publisher)")):
        etree.SubElement(document, name).text = dc.xpath(path, namespaces=dc_ns)
    etree.SubElement(document, "description").append(copy.deepcopy(rad.getroot()))
    text = etree.SubElement(document, "text")
    body = tei.xpath("//t:text/t:body", namespaces={"t": TEI})
    if body and has_text(body[0]):
        text.append(copy.deepcopy(body[0]))
    return document, dc.get("status"), dc


def escape_tree(document, missing):
    """Escapes every text node and the attributes the LaTeX stylesheet prints (pb/@n and the document's id and status) in place."""
    for element in document.iter():
        if not isinstance(element.tag, str):
            continue
        if element.text:
            element.text = tex_escape(element.text, missing)
        if element.tail:
            element.tail = tex_escape(element.tail, missing)
        if "n" in element.attrib:
            element.set("n", tex_escape(element.get("n"), missing))


def html_page(document, style):
    body = sheet("document-to-html.xsl", HERE)(etree.ElementTree(document))
    title = document.findtext("title") or document.get("id") or "Record"
    css = (STYLES / f"{style}.css").read_text(encoding="utf-8")
    fragment = etree.tostring(body.getroot(), encoding="unicode", method="html")
    escape = lambda s: s.replace("&", "&amp;").replace("<", "&lt;").replace(">", "&gt;")
    lang = document.get("lang") or "en"
    return f'<!DOCTYPE html>\n<html lang="{escape(lang)}">\n<head>\n<meta charset="utf-8">\n<meta name="viewport" content="width=device-width, initial-scale=1">\n<title>{escape(title)}</title>\n<style>\n{css}</style>\n</head>\n<body>\n{fragment}\n</body>\n</html>\n'


def latex_source(document, style, missing):
    working = copy.deepcopy(document)
    escape_tree(working, missing)
    body = str(sheet("document-to-latex.xsl", HERE)(etree.ElementTree(working)))
    preamble = (STYLES / f"{style}.tex").read_text(encoding="utf-8")
    ident = tex_escape(document.get("id") or "")
    status = tex_escape(document.get("status") or "")
    return f"{preamble}\n\\newcommand{{\\docid}}{{{ident}}}\n\\newcommand{{\\docstatus}}{{{status}}}\n\n{body}"


def compile_pdf(source, jobname):
    """The PDF bytes of a LaTeX source, or raises RuntimeError with the end of the log."""
    if not shutil.which("pdflatex"):
        raise FileNotFoundError("pdflatex is not installed")
    with tempfile.TemporaryDirectory(prefix="render-tei-") as tmp:
        tex = Path(tmp) / f"{jobname}.tex"
        tex.write_text(source, encoding="utf-8")
        for _ in range(2):  # the second pass settles the page numbers
            run = subprocess.run(["pdflatex", "-no-shell-escape", "-interaction=nonstopmode", "-halt-on-error", f"-jobname={jobname}", tex.name],
                                 cwd=tmp, capture_output=True, timeout=180, check=False)  # bytes: TeX writes Latin-1 to its terminal
            if run.returncode != 0:
                log = (Path(tmp) / f"{jobname}.log")
                tail = log.read_text(encoding="utf-8", errors="replace")[-1500:] if log.exists() else run.stdout.decode("utf-8", errors="replace")[-1500:]
                raise RuntimeError(tail)
        return (Path(tmp) / f"{jobname}.pdf").read_bytes()


def main(argv=None):
    ap = argparse.ArgumentParser(description=__doc__.split("\n\n")[0])
    ap.add_argument("record", help="the TEI file")
    ap.add_argument("--format", choices=["html", "tex", "pdf"], default="html")
    ap.add_argument("--style", choices=STYLE_NAMES, default="catalogue")
    ap.add_argument("--out", default=".", help="the folder to write into (made if missing)")
    ap.add_argument("--allow-unpublished", action="store_true", help="render a record that is a draft or private (a working copy)")
    ap.add_argument("--entities", help="menota-entities.txt, for a record that uses Menota's named character entities")
    args = ap.parse_args(argv)
    try:
        tei = parse_tei(args.record, load_entities(args.entities) if args.entities else None)
    except (OSError, etree.XMLSyntaxError) as error:
        print(f"{args.record}: cannot read it as XML ({error})", file=sys.stderr)
        return 4
    document, status, _ = assemble(tei)
    if document is None:
        print(f"{args.record}: no manuscript description (a TEI msDesc in the sourceDesc) to render", file=sys.stderr)
        return 4
    if status != "published" and not args.allow_unpublished:
        print(f"{args.record}: the record is {status}, not published; use --allow-unpublished for a working copy", file=sys.stderr)
        return 3
    name = re.sub(r"[^A-Za-z0-9._-]+", "-", document.get("id") or Path(args.record).stem).strip("-").lstrip(".") or "record"  # no path, no hidden file
    out = Path(args.out)
    out.mkdir(parents=True, exist_ok=True)
    target = out / f"{name}.{args.format}"
    if args.format == "html":
        target.write_text(html_page(document, args.style), encoding="utf-8")
    else:
        missing = set()
        source = latex_source(document, args.style, missing)
        if missing:
            print("characters the fonts lack, shown as [U+XXXX]: " + " ".join("U+%04X" % ord(c) for c in sorted(missing)), file=sys.stderr)
        if args.format == "tex":
            target.write_text(source, encoding="utf-8")
        else:
            try:
                target.write_bytes(compile_pdf(source, name))
            except FileNotFoundError as error:
                print(str(error), file=sys.stderr)
                return 2
            except RuntimeError as error:
                print(f"LaTeX failed:\n{error}", file=sys.stderr)
                return 5
    print(target)
    return 0


if __name__ == "__main__":
    sys.exit(main())
