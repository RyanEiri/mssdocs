<?xml version="1.0" encoding="UTF-8"?>
<!--
  Parts the metadata stylesheets share (included by tei-to-dc.xsl and tei-to-rad.xsl; context node: the msDesc): the title (decision D2 of docs/mssdocs-metadata-mapping.md), the
  extent in leaves and the size in cm (D8), and the labels of coded values (section 7a of that document).
-->
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:t="http://www.tei-c.org/ns/1.0"
    exclude-result-prefixes="t">

  <!-- where the title comes from: item (a single msItem's title), statement (titleStmt), summary, or empty -->
  <xsl:template name="msdesc-title-source">
    <xsl:variable name="items" select="t:msContents/t:msItem"/>
    <xsl:choose>
      <xsl:when test="count($items) = 1 and normalize-space($items/t:title) != ''">item</xsl:when>
      <xsl:when test="normalize-space(ancestor::t:teiHeader/t:fileDesc/t:titleStmt/t:title[normalize-space(.) != ''][1]) != ''">statement</xsl:when>
      <xsl:when test="normalize-space(t:msContents/t:summary) != ''">summary</xsl:when>
    </xsl:choose>
  </xsl:template>

  <xsl:template name="msdesc-title">
    <xsl:variable name="source"><xsl:call-template name="msdesc-title-source"/></xsl:variable>
    <xsl:choose>
      <xsl:when test="$source = 'item'"><xsl:value-of select="normalize-space(t:msContents/t:msItem/t:title)"/></xsl:when>
      <xsl:when test="$source = 'statement'"><xsl:value-of select="normalize-space(ancestor::t:teiHeader/t:fileDesc/t:titleStmt/t:title[normalize-space(.) != ''][1])"/></xsl:when>
      <xsl:when test="$source = 'summary'"><xsl:value-of select="normalize-space(t:msContents/t:summary)"/></xsl:when>
    </xsl:choose>
  </xsl:template>

  <!-- "16 leaves" / "1 leaf": the support's own extent text, else the book-block count (its value, else the number written in it) -->
  <xsl:template name="leaves">
    <xsl:variable name="support" select="t:physDesc/t:objectDesc/t:supportDesc/t:support"/>
    <xsl:variable name="count">
      <xsl:for-each select="$support/t:num[@type = 'book-block'][1]">
        <xsl:choose>
          <xsl:when test="@value and number(@value) = number(@value)"><xsl:value-of select="number(@value)"/></xsl:when>
          <xsl:when test="number(normalize-space(.)) = number(normalize-space(.))"><xsl:value-of select="number(normalize-space(.))"/></xsl:when>
        </xsl:choose>
      </xsl:for-each>
    </xsl:variable>
    <xsl:choose>
      <xsl:when test="normalize-space($support/t:extent) != ''"><xsl:value-of select="normalize-space($support/t:extent)"/></xsl:when>
      <xsl:when test="$count = '1'">1 leaf</xsl:when>
      <xsl:when test="$count != ''"><xsl:value-of select="$count"/> leaves</xsl:when>
    </xsl:choose>
  </xsl:template>

  <!-- "18.2-18.7 x 12.0-12.2 cm": height and width of the first dimensions that has both; nothing when it lacks one -->
  <xsl:template name="size">
    <xsl:for-each select="t:physDesc/t:objectDesc/t:supportDesc/t:support/t:dimensions[t:height and t:width][1]">
      <xsl:call-template name="measure"><xsl:with-param name="node" select="t:height"/></xsl:call-template>
      <xsl:text> x </xsl:text>
      <xsl:call-template name="measure"><xsl:with-param name="node" select="t:width"/></xsl:call-template>
      <xsl:text> cm</xsl:text>
    </xsl:for-each>
  </xsl:template>

  <!-- one measurement in cm: a measured range (atLeast, atMost), else the quantity, else the number written in the element; millimetres are converted -->
  <xsl:template name="measure">
    <xsl:param name="node"/>
    <xsl:variable name="divisor">
      <xsl:choose><xsl:when test="$node/@unit = 'cm'">1</xsl:when><xsl:otherwise>10</xsl:otherwise></xsl:choose>
    </xsl:variable>
    <xsl:choose>
      <xsl:when test="$node/@atLeast and $node/@atMost and number($node/@atLeast) != number($node/@atMost)">
        <xsl:value-of select="format-number(number($node/@atLeast) div $divisor, '0.0')"/>-<xsl:value-of select="format-number(number($node/@atMost) div $divisor, '0.0')"/>
      </xsl:when>
      <xsl:when test="$node/@quantity"><xsl:value-of select="format-number(number($node/@quantity) div $divisor, '0.0')"/></xsl:when>
      <xsl:when test="$node/@atLeast"><xsl:value-of select="format-number(number($node/@atLeast) div $divisor, '0.0')"/></xsl:when>
      <xsl:otherwise><xsl:value-of select="format-number(number(normalize-space($node)) div $divisor, '0.0')"/></xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <xsl:template name="form-label">
    <xsl:param name="code"/>
    <xsl:choose>
      <xsl:when test="$code = 'codex'">A bound codex</xsl:when>
      <xsl:when test="$code = 'leaf'">A loose leaf</xsl:when>
      <xsl:when test="$code = 'scroll'">A scroll</xsl:when>
      <xsl:when test="$code = 'other'">Any other format</xsl:when>
      <xsl:otherwise><xsl:value-of select="$code"/></xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <xsl:template name="material-label">
    <xsl:param name="code"/>
    <xsl:choose>
      <xsl:when test="$code = 'perg'">Parchment</xsl:when>
      <xsl:when test="$code = 'chart'">Paper</xsl:when>
      <xsl:when test="$code = 'mixed'">A combination of materials</xsl:when>
      <xsl:when test="$code = 'unknown'">Support material unknown</xsl:when>
      <xsl:otherwise><xsl:value-of select="$code"/></xsl:otherwise>
    </xsl:choose>
  </xsl:template>
</xsl:stylesheet>
