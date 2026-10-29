# Metadata from the TEI header

A manuscript is described once, in TEI (`msDesc`). The other metadata is generated from it and never kept by hand.

| File | Output |
|---|---|
| `tei-to-dc.xsl` | Dublin Core (qualified, `dcterms`) as `<record id status lang>` holding `dc:` and `dcterms:` elements |
| `tei-to-rad.xsl` | A RAD description (*Rules for Archival Description*, 2008, chapter 13) as an XHTML fragment; every paragraph carries its rule in `data-rule` |
| `tei-completeness.xsl` | `<completeness>` listing what the record lacks that the two views want (creator, date, extent, dimensions, language, identifier); it reports and never blocks |
| `unit-to-rad.xsl` | A RAD description of a **unit of description** (fonds, collection, series or file) at RAD's first level of detail (1.0D1, 1.0D2), from the unit XML of `projects/unit_lib.php` (`ips_units_xml`); `repeat=yes` prints the values inherited from above too (RAD footnote 1a) |
| `unit-to-dc.xsl` | Dublin Core for a unit: `dc:type` Collection, `dcterms:isPartOf` / `hasPart`, and the unit's effective visibility as its status |
| `tei-common.xsl` | Parts the others include: the title order, the extent and size, the labels of coded values |

XSLT 1.0, so PHP's `XSLTProcessor` (`php8.2-xml` includes `xsl`) and lxml both run it. A stylesheet must be loaded from a file so its `xsl:include` resolves. `record/@status` (Dublin Core) and `@data-status` (RAD) are `revisionDesc/@status` (`draft`, `published` or `private`; a record with none is a draft): the stylesheets do not decide who may see a record, the caller shows public zones only `published` ones.

Dates are ISO 8601: the `origDate` value is checked, a value that is not an ISO 8601 date is left out of both views and named by the completeness report, and RAD prints a doubtful date with the ISO 8601-2 mark `?`. A field the record lacks is left out, never filled in. What each field comes from, and the reasons, are in `docs/mssdocs-metadata-mapping.md` of the webserversAC repository. The labels for coded values (`@material`, `@form`, the text classes) are in `tei-common.xsl` and the named templates of `tei-to-dc.xsl`; a class code whose label is not confirmed gives a `dc:type` but no `dc:subject`.

Command line (lxml): `python3 -c "import lxml.etree as e,sys; print(str(e.XSLT(e.parse('tei-to-rad.xsl'))(e.parse(sys.argv[1]))))" record.xml`
