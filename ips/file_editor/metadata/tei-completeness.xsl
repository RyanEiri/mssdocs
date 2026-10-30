<?xml version="1.0" encoding="UTF-8"?>
<!--
  What a record lacks that the Dublin Core and RAD views would want (docs/mssdocs-metadata-mapping.md, section 5): <completeness id="…"> holding a <missing field="…"> for each of
  creator, date, extent, dimensions, language and identifier that the TEI does not hold. It reports and never blocks; a record that lacks nothing gives an empty <completeness/>.
  XSLT 1.0; the file editor shows it beside the generated views.
-->
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:t="http://www.tei-c.org/ns/1.0"
    exclude-result-prefixes="t">

  <xsl:include href="tei-common.xsl"/>
  <xsl:output method="xml" encoding="UTF-8" indent="yes"/>

  <xsl:template match="/">
    <xsl:apply-templates select="(//t:sourceDesc/t:msDesc)[1]"/>
  </xsl:template>

  <xsl:template match="t:msDesc">
    <xsl:variable name="leaves"><xsl:call-template name="leaves"/></xsl:variable>
    <xsl:variable name="size"><xsl:call-template name="size"/></xsl:variable>
    <!-- a printed book (mapping section 9): the publication is the biblStruct the copy's msItem points at -->
    <xsl:variable name="ptr" select="string(t:msContents/t:msItem/t:bibl/t:ptr/@target)"/>
    <xsl:variable name="bibl" select="//t:biblStruct[$ptr != '' and @xml:id = substring-after($ptr, '#')][1]"/>
    <xsl:variable name="mono" select="$bibl/t:monogr[1]"/>
    <completeness id="{@xml:id}">
      <xsl:if test="not($mono/t:author[normalize-space(.) != ''] or $mono/t:editor[normalize-space(.) != ''] or $mono/t:respStmt/t:name[normalize-space(.) != ''] or t:msContents/t:msItem/t:author[normalize-space(.) != ''] or ancestor::t:teiHeader/t:fileDesc/t:titleStmt/t:author[normalize-space(.) != ''] or t:msContents/t:msItem/t:respStmt[t:resp/@key = 'crp']/t:name[normalize-space(.) != ''])">
        <missing field="creator">No author is named (an author element in an msItem or the title statement).</missing>
      </xsl:if>
      <xsl:variable name="date">
        <xsl:for-each select="(t:history/t:origin/t:origDate | $mono/t:imprint/t:date)[1]"><xsl:call-template name="origdate-iso"/></xsl:for-each>
      </xsl:variable>
      <xsl:if test="$date = ''">
        <xsl:choose>
          <xsl:when test="t:history/t:origin/t:origDate[1][@when or @notBefore or @notAfter or @from or @to]">
            <missing field="date">The date of creation has no ISO 8601 value: use YYYY, YYYY-MM or YYYY-MM-DD in when, or both ends of an interval in notBefore and notAfter (or from and to), the start not after the end.</missing>
          </xsl:when>
          <xsl:otherwise>
            <missing field="date">No date of creation with a machine value (origDate with when, notBefore and notAfter, or from and to).</missing>
          </xsl:otherwise>
        </xsl:choose>
      </xsl:if>
      <xsl:if test="$bibl and normalize-space($mono/t:imprint/t:publisher[1]) = ''">
        <missing field="publisher">No publisher in the imprint (RAD 1.4D prints [s.n.]).</missing>
      </xsl:if>
      <xsl:if test="$bibl and normalize-space($mono/t:imprint/t:pubPlace[1]) = ''">
        <missing field="place">No place of publication in the imprint (RAD 1.4C prints [S.l.]).</missing>
      </xsl:if>
      <xsl:if test="$leaves = ''">
        <missing field="extent">No extent (a support extent, or a book-block count).</missing>
      </xsl:if>
      <xsl:if test="$size = ''">
        <missing field="dimensions">No leaf size with both a height and a width.</missing>
      </xsl:if>
      <xsl:if test="not(t:msContents/t:textLang/@mainLang)">
        <missing field="language">No main language (textLang with mainLang).</missing>
      </xsl:if>
      <xsl:if test="normalize-space(t:msIdentifier/t:idno) = ''">
        <missing field="identifier">No shelfmark (msIdentifier/idno).</missing>
      </xsl:if>
    </completeness>
  </xsl:template>
</xsl:stylesheet>
