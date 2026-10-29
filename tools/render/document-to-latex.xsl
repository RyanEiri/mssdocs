<?xml version="1.0" encoding="UTF-8"?>
<!--
  The LaTeX body of a rendered document, from the <document> render-tei.py assembles: everything after \begin{document}. XSLT 1.0, text output. Design (docs/mssdocs-metadata-mapping.md,
  plan stage 5): the structure is made here, the style (fonts, page, spacing) lives in the style's preamble (styles/*.tex), which defines the macros used below: \radtitle, \radhead,
  \radpartof, \radsubhead, \radpara, \teihead, \teipb and the teiverse environment.
  Escaping is NOT done here (XSLT 1.0 has no good way to): render-tei.py escapes every text node and the attributes this sheet prints (pb/@n) before the transformation, so a value copied
  with value-of is already safe TeX. A TEI element not listed passes its text through, so no text is lost.
-->
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:t="http://www.tei-c.org/ns/1.0"
    exclude-result-prefixes="t">

  <xsl:output method="text" encoding="UTF-8"/>

  <xsl:template match="/document">
    <xsl:text>\begin{document}&#10;</xsl:text>
    <xsl:text>\radtitle{</xsl:text><xsl:value-of select="normalize-space(title)"/><xsl:text>}{</xsl:text><xsl:value-of select="normalize-space(shelfmark)"/><xsl:text>}{</xsl:text><xsl:value-of select="normalize-space(holder)"/><xsl:text>}&#10;&#10;</xsl:text>
    <xsl:apply-templates select="description/*" mode="rad"/>
    <xsl:if test="text/*">
      <xsl:text>&#10;\radsubhead{Text}&#10;&#10;</xsl:text>
      <xsl:apply-templates select="text/*"/>
    </xsl:if>
    <xsl:text>&#10;\end{document}&#10;</xsl:text>
  </xsl:template>

  <!-- the description -->
  <xsl:template match="div[@class='rad-record']" mode="rad"><xsl:apply-templates mode="rad"/></xsl:template>
  <xsl:template match="div[@class='rad-notes']" mode="rad">
    <xsl:if test="p"><xsl:text>\radsubhead{Notes}&#10;&#10;</xsl:text><xsl:apply-templates mode="rad"/></xsl:if>
  </xsl:template>
  <xsl:template match="div[@class='rad-access']" mode="rad">
    <xsl:if test="p"><xsl:text>\radsubhead{Access points}&#10;&#10;</xsl:text><xsl:apply-templates mode="rad"/></xsl:if>
  </xsl:template>
  <xsl:template match="p[@class='rad-part-of']" mode="rad">
    <xsl:text>\radpartof{</xsl:text><xsl:value-of select="normalize-space(.)"/><xsl:text>}&#10;&#10;</xsl:text>
  </xsl:template>
  <xsl:template match="p[@class='rad-head']" mode="rad">
    <xsl:text>\radhead{</xsl:text><xsl:value-of select="normalize-space(span[@class='rad-title'])"/><xsl:text>}{</xsl:text>
    <xsl:value-of select="normalize-space(span[@class='rad-responsibility'])"/><xsl:text>}{</xsl:text>
    <xsl:value-of select="normalize-space(span[@class='rad-date'])"/><xsl:text>}{</xsl:text>
    <xsl:value-of select="normalize-space(span[@class='rad-physical'])"/><xsl:text>}&#10;&#10;</xsl:text>
  </xsl:template>
  <!-- a labelled paragraph: the label, then the rest -->
  <xsl:template match="p[span[@class='rad-label']]" mode="rad">
    <xsl:variable name="label" select="normalize-space(span[@class='rad-label'][1])"/>
    <xsl:text>\radpara{</xsl:text>
    <xsl:choose>
      <xsl:when test="substring($label, string-length($label)) = ':'"><xsl:value-of select="substring($label, 1, string-length($label) - 1)"/></xsl:when>
      <xsl:otherwise><xsl:value-of select="$label"/></xsl:otherwise>
    </xsl:choose>
    <xsl:text>}{</xsl:text>
    <xsl:value-of select="normalize-space(substring(normalize-space(.), string-length($label) + 1))"/>
    <xsl:text>}&#10;&#10;</xsl:text>
  </xsl:template>
  <xsl:template match="p" mode="rad"><xsl:value-of select="normalize-space(.)"/><xsl:text>&#10;&#10;</xsl:text></xsl:template>

  <!-- the TEI text -->
  <xsl:template match="t:body | t:text"><xsl:apply-templates/></xsl:template>
  <xsl:template match="t:div"><xsl:apply-templates/></xsl:template>
  <xsl:template match="t:head"><xsl:text>&#10;\teihead{</xsl:text><xsl:apply-templates/><xsl:text>}&#10;&#10;</xsl:text></xsl:template>
  <xsl:template match="t:p"><xsl:apply-templates/><xsl:text>&#10;&#10;</xsl:text></xsl:template>
  <xsl:template match="t:lb"><xsl:text>\newline{}</xsl:text></xsl:template>
  <xsl:template match="t:pb"><xsl:text>\teipb{</xsl:text><xsl:value-of select="@n"/><xsl:text>}</xsl:text></xsl:template>
  <xsl:template match="t:lg"><xsl:text>\begin{teiverse}&#10;</xsl:text><xsl:apply-templates/><xsl:text>\end{teiverse}&#10;&#10;</xsl:text></xsl:template>
  <xsl:template match="t:l"><xsl:apply-templates/><xsl:text> \\&#10;</xsl:text></xsl:template>
  <xsl:template match="t:list"><xsl:text>\begin{itemize}&#10;</xsl:text><xsl:apply-templates/><xsl:text>\end{itemize}&#10;&#10;</xsl:text></xsl:template>
  <xsl:template match="t:item"><xsl:text>\item </xsl:text><xsl:apply-templates/><xsl:text>&#10;</xsl:text></xsl:template>
  <xsl:template match="t:quote"><xsl:text>\begin{quote}&#10;</xsl:text><xsl:apply-templates/><xsl:text>&#10;\end{quote}&#10;&#10;</xsl:text></xsl:template>
  <xsl:template match="t:note"><xsl:text>\footnote{</xsl:text><xsl:apply-templates/><xsl:text>}</xsl:text></xsl:template>
  <xsl:template match="t:hi">
    <xsl:choose>
      <xsl:when test="@rend = 'italic' or @rend = 'i'"><xsl:text>\textit{</xsl:text><xsl:apply-templates/><xsl:text>}</xsl:text></xsl:when>
      <xsl:when test="@rend = 'bold' or @rend = 'b'"><xsl:text>\textbf{</xsl:text><xsl:apply-templates/><xsl:text>}</xsl:text></xsl:when>
      <xsl:when test="@rend = 'sup' or @rend = 'superscript'"><xsl:text>\textsuperscript{</xsl:text><xsl:apply-templates/><xsl:text>}</xsl:text></xsl:when>
      <xsl:otherwise><xsl:apply-templates/></xsl:otherwise>
    </xsl:choose>
  </xsl:template>
  <xsl:template match="t:foreign"><xsl:text>\textit{</xsl:text><xsl:apply-templates/><xsl:text>}</xsl:text></xsl:template>
  <xsl:template match="t:*"><xsl:apply-templates/></xsl:template>
</xsl:stylesheet>
