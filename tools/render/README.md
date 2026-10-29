# render-tei: a manuscript description as HTML or PDF

```
render-tei.py record.xml --format html --style catalogue --out out/     # out/<xml:id>.html, one self-contained page
render-tei.py record.xml --format pdf  --style plain     --out out/     # out/<xml:id>.pdf   (pdfLaTeX)
render-tei.py record.xml --format tex                    --out out/     # the LaTeX source
```

A tool for the controller (like `tools/validate-tei.py`), not for the web hosts: it needs `lxml` and, for a PDF, `pdflatex` (TeX Live with `lmodern`, `microtype`, `fancyhdr`, `ragged2e`).

**What it prints.** The RAD description of the manuscript, the same text the file editor's Metadata panel shows (it is made by `ips/file_editor/metadata/tei-to-rad.xsl`), then the text of the TEI `<body>` when the file has any (`div`, `head`, `p`, `lb`, `pb`, `hi`, `lg`/`l`, `list`/`item`, `quote`, `note`, `foreign`; the text of any other element is kept). The page and the LaTeX are made by XSLT 1.0 (`document-to-html.xsl`, `document-to-latex.xsl`) from one `<document>` the tool assembles.

**Styles** (`styles/`): `catalogue` (a serif page, small capitals for the labels) and `plain` (sans-serif, a working copy with the record's id and status in the header). Each is a CSS file for HTML (system fonts only, inline, no external resource) and a LaTeX preamble for PDF. To add one, add `styles/<name>.css` and `styles/<name>.tex` defining the macros `\radtitle`, `\radhead`, `\radpartof`, `\radsubhead`, `\radpara`, `\teihead`, `\teipb` and the `teiverse` environment.

**Only published records.** A record is rendered when `revisionDesc/@status` is `published`; a draft, a private record and one with no status are refused (exit 3). `--allow-unpublished` makes a working copy. (Decision D9 of the metadata mapping document.)

**LaTeX safety.** The text is escaped in Python, not in XSLT: every text node and the attributes the stylesheet prints, with the TeX specials made harmless. The PDF is made with `pdflatex -no-shell-escape`. Characters the Type 1 Latin Modern fonts lack (runes, MUFI characters, most of Latin Extended-B) are shown as `[U+XXXX]` and listed on stderr, so a compile never stops on an unknown character; `ǫ` and `Ǫ` are set with an accent command. **Why pdfLaTeX and not LuaLaTeX with `fontspec`:** LuaTeX cannot load OpenType fonts on every machine (it could not on the development machine), whereas pdfLaTeX with Type 1 fonts always works. A style that needs a font with more characters (for example Old Norse manuscripts with MUFI) would need LuaLaTeX and a working font setup; that is a later decision.

Exit status: 0 done, 2 cannot run, 3 not published, 4 cannot be described (not XML, no `msDesc`), 5 LaTeX failed (the end of its log is printed). Tests: `tests/test_render_tei.py` in the fleet repository.
