# Metadata from the TEI header

A manuscript is described once, in TEI (`msDesc`). The other metadata is generated from it and never kept by hand.

| File | Output |
|---|---|
| `tei-to-dc.xsl` | Dublin Core (qualified, `dcterms`) as `<record id status lang>` holding `dc:` and `dcterms:` elements |

XSLT 1.0, so PHP's `XSLTProcessor` (`php8.2-xml` includes `xsl`) and lxml both run it. `record/@status` is `revisionDesc/@status` (`draft`, `published` or `private`; a record with none is a draft): the stylesheet does not decide who may see a record, the caller shows public zones only `published` ones.

A field the record lacks is left out, never filled in. What each field comes from, and the reasons, are in `docs/mssdocs-metadata-mapping.md` of the webserversAC repository. The labels for coded values (`@material`, `@form`, the text classes) are in the named templates at the end of the stylesheet; a class code whose label is not confirmed gives a `dc:type` but no `dc:subject`.

Command line (lxml): `python3 -c "import lxml.etree as e,sys; print(str(e.XSLT(e.parse('tei-to-dc.xsl'))(e.parse(sys.argv[1]))))" record.xml`
