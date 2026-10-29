<?xml version="1.0" encoding="UTF-8"?>
<!--
  The HTML article of a rendered document: the RAD description of a manuscript (the XHTML of ips/file_editor/metadata/tei-to-rad.xsl) and the TEI text, from the <document> render-tei.py
  assembles. XSLT 1.0. Output is the <article> fragment; render-tei.py wraps it in a page with the style's CSS.
  Only div, p and span of the description are copied, with their class, so nothing but text and that structure reaches the page; the TEI text maps a small set of elements and passes the text
  of every other element through, so no text is lost.
-->
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:t="http://www.tei-c.org/ns/1.0"
    exclude-result-prefixes="t">

  <xsl:output method="xml" encoding="UTF-8" indent="no" omit-xml-declaration="yes"/>

  <xsl:template match="/document">
    <article class="doc">
      <xsl:attribute name="data-status"><xsl:value-of select="@status"/></xsl:attribute>
      <xsl:if test="@lang"><xsl:attribute name="lang"><xsl:value-of select="@lang"/></xsl:attribute></xsl:if>
      <header>
        <h1><xsl:value-of select="title"/></h1>
        <p class="doc-meta">
          <xsl:value-of select="shelfmark"/>
          <xsl:if test="normalize-space(shelfmark) != '' and normalize-space(holder) != ''"> · </xsl:if>
          <xsl:value-of select="holder"/>
        </p>
      </header>
      <section class="description">
        <xsl:apply-templates select="description/*" mode="rad"/>
      </section>
      <xsl:if test="text/*">
        <section class="text">
          <h2>Text</h2>
          <xsl:apply-templates select="text/*"/>
        </section>
      </xsl:if>
    </article>
  </xsl:template>

  <!-- the description: div, p and span with their class only -->
  <xsl:template match="div | p | span" mode="rad">
    <xsl:element name="{local-name()}">
      <xsl:if test="@class"><xsl:attribute name="class"><xsl:value-of select="@class"/></xsl:attribute></xsl:if>
      <xsl:apply-templates mode="rad"/>
    </xsl:element>
  </xsl:template>

  <!-- the TEI text -->
  <xsl:template match="t:body | t:text"><xsl:apply-templates/></xsl:template>
  <xsl:template match="t:div"><section class="tei-div"><xsl:apply-templates/></section></xsl:template>
  <xsl:template match="t:head"><h3><xsl:apply-templates/></h3></xsl:template>
  <xsl:template match="t:p"><p><xsl:apply-templates/></p></xsl:template>
  <xsl:template match="t:lb"><br/></xsl:template>
  <xsl:template match="t:pb"><span class="pb"><xsl:if test="@n">[<xsl:value-of select="@n"/>]</xsl:if></span></xsl:template>
  <xsl:template match="t:lg"><div class="lg"><xsl:apply-templates/></div></xsl:template>
  <xsl:template match="t:l"><div class="l"><xsl:apply-templates/></div></xsl:template>
  <xsl:template match="t:list"><ul><xsl:apply-templates/></ul></xsl:template>
  <xsl:template match="t:item"><li><xsl:apply-templates/></li></xsl:template>
  <xsl:template match="t:quote"><blockquote><xsl:apply-templates/></blockquote></xsl:template>
  <xsl:template match="t:note"><span class="note"> [<xsl:apply-templates/>]</span></xsl:template>
  <xsl:template match="t:hi">
    <xsl:choose>
      <xsl:when test="@rend = 'italic' or @rend = 'i'"><i><xsl:apply-templates/></i></xsl:when>
      <xsl:when test="@rend = 'bold' or @rend = 'b'"><b><xsl:apply-templates/></b></xsl:when>
      <xsl:when test="@rend = 'sup' or @rend = 'superscript'"><sup><xsl:apply-templates/></sup></xsl:when>
      <xsl:when test="@rend = 'sub' or @rend = 'subscript'"><sub><xsl:apply-templates/></sub></xsl:when>
      <xsl:otherwise><span class="hi"><xsl:apply-templates/></span></xsl:otherwise>
    </xsl:choose>
  </xsl:template>
  <xsl:template match="t:foreign"><i><xsl:apply-templates/></i></xsl:template>
  <!-- any other element: its text is kept -->
  <xsl:template match="t:*"><xsl:apply-templates/></xsl:template>
</xsl:stylesheet>
