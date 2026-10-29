<?xml version="1.0" encoding="UTF-8"?>
<!--
  Dublin Core (qualified, dcterms) for a unit of description (fonds, collection, series or file) from the unit XML that projects/unit_lib.php (ips_units_xml) makes: the unit's own
  description, the chain of units above it and the units below. XSLT 1.0. Design: docs/mssdocs-rad-hierarchy-design.md (webserversAC repository), section 3.6.

  Output as for an item (tei-to-dc.xsl): <record id status lang> with dc: and dcterms: elements, the status being the unit's EFFECTIVE visibility (the least public along its chain, so a
  caller that shows only "published" records cannot publish a unit whose parent is private). Fields the unit does not hold are left out. dc:type is Collection. Dates are ISO 8601; a unit
  that is open (no end date) gives no dc:date, because ISO 8601 has no open interval (the RAD view says it in EDTF).
-->
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:dc="http://purl.org/dc/elements/1.1/"
    xmlns:dcterms="http://purl.org/dc/terms/">

  <xsl:output method="xml" encoding="UTF-8" indent="yes"/>
  <xsl:strip-space elements="*"/>

  <xsl:template match="/unit">
    <record>
      <xsl:attribute name="id"><xsl:value-of select="@id"/></xsl:attribute>
      <xsl:attribute name="status">
        <xsl:choose>
          <xsl:when test="@effective = 'published' or @effective = 'private'"><xsl:value-of select="@effective"/></xsl:when>
          <xsl:otherwise>draft</xsl:otherwise>
        </xsl:choose>
      </xsl:attribute>
      <dc:title><xsl:value-of select="normalize-space(title)"/></dc:title>
      <xsl:if test="normalize-space(reference-code) != ''"><dc:identifier><xsl:value-of select="normalize-space(reference-code)"/></dc:identifier></xsl:if>
      <xsl:if test="dates/@start != '' and dates/@end != ''">
        <dc:date>
          <xsl:choose>
            <xsl:when test="dates/@start = dates/@end"><xsl:value-of select="dates/@start"/></xsl:when>
            <xsl:otherwise><xsl:value-of select="concat(dates/@start, '/', dates/@end)"/></xsl:otherwise>
          </xsl:choose>
        </dc:date>
      </xsl:if>
      <xsl:if test="normalize-space(scope-content) != ''"><dc:description><xsl:value-of select="normalize-space(scope-content)"/></dc:description></xsl:if>
      <xsl:if test="normalize-space(admin-history) != ''"><dcterms:abstract><xsl:value-of select="normalize-space(admin-history)"/></dcterms:abstract></xsl:if>
      <dc:type>Collection</dc:type>
      <xsl:if test="normalize-space(extent) != ''"><dcterms:extent><xsl:value-of select="normalize-space(extent)"/></dcterms:extent></xsl:if>
      <xsl:if test="normalize-space(language) != ''"><dc:language><xsl:value-of select="normalize-space(language)"/></dc:language></xsl:if>
      <xsl:if test="normalize-space(repository) != ''"><dc:publisher><xsl:value-of select="normalize-space(repository)"/></dc:publisher></xsl:if>
      <xsl:if test="normalize-space(custodial-history) != ''"><dcterms:provenance><xsl:value-of select="normalize-space(custodial-history)"/></dcterms:provenance></xsl:if>
      <xsl:if test="normalize-space(access) != ''"><dcterms:accessRights><xsl:value-of select="normalize-space(access)"/></dcterms:accessRights></xsl:if>
      <xsl:if test="normalize-space(terms) != ''"><dc:rights><xsl:value-of select="normalize-space(terms)"/></dc:rights></xsl:if>
      <xsl:for-each select="chain/unit[last()]">
        <dcterms:isPartOf><xsl:value-of select="normalize-space(@title)"/></dcterms:isPartOf>
      </xsl:for-each>
      <xsl:for-each select="children/unit">
        <dcterms:hasPart><xsl:value-of select="normalize-space(@title)"/></dcterms:hasPart>
      </xsl:for-each>
    </record>
  </xsl:template>
</xsl:stylesheet>
