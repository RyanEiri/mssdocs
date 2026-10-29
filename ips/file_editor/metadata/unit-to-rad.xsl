<?xml version="1.0" encoding="UTF-8"?>
<!--
  A RAD description (Rules for Archival Description, July 2008) of a unit of description (fonds, collection, series or file) as an XHTML fragment, from the unit XML that
  projects/unit_lib.php (ips_units_xml) makes. XSLT 1.0. Design: docs/mssdocs-rad-hierarchy-design.md (webserversAC repository), sections 1 and 3.

  The level of detail is RAD's first level (1.0D1 for a fonds, collection or series; 1.0D2 for a file): Title. - dates. - extent, then the administrative history / biographical sketch
  and the custodial history (not for a file: RAD gives them to the higher levels), the scope and content, and the notes (language, restrictions on access, terms of use, accruals, general note).
  Nothing is repeated from a unit above (RAD 1.0A2d) unless the parameter repeat is "yes" (footnote 1a, the site's choice): then the inherited values the unit XML carries are printed
  too, marked as from the unit they come from. A fonds or collection title ends with its level word (1.1B3). Dates are ISO 8601, with the EDTF open ends (1906/..) for an open unit.
  Every paragraph carries its RAD rule in data-rule. Fields the unit does not hold are left out.
-->
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

  <xsl:output method="xml" encoding="UTF-8" indent="yes" omit-xml-declaration="yes"/>
  <xsl:strip-space elements="*"/>
  <xsl:param name="repeat" select="'no'"/>

  <xsl:variable name="lower" select="'abcdefghijklmnopqrstuvwxyzáðéíóúýþæö'"/>
  <xsl:variable name="upper" select="'ABCDEFGHIJKLMNOPQRSTUVWXYZÁÐÉÍÓÚÝÞÆÖ'"/>

  <xsl:template match="/unit">
    <xsl:variable name="level" select="@level"/>
    <xsl:variable name="title" select="normalize-space(title)"/>
    <xsl:variable name="word">
      <xsl:choose>
        <xsl:when test="$level = 'fonds'">fonds</xsl:when>
        <xsl:when test="$level = 'collection'">collection</xsl:when>
      </xsl:choose>
    </xsl:variable>
    <!-- 1.1B3: the title of a fonds or collection names its level, unless it already ends with the word -->
    <xsl:variable name="full-title">
      <xsl:value-of select="$title"/>
      <xsl:if test="$word != '' and translate(substring($title, string-length($title) - string-length($word) + 1), $upper, $lower) != $word"><xsl:value-of select="concat(' ', $word)"/></xsl:if>
    </xsl:variable>
    <xsl:variable name="dates">
      <xsl:choose>
        <xsl:when test="dates/@start != '' and dates/@end != '' and dates/@start = dates/@end"><xsl:value-of select="dates/@start"/></xsl:when>
        <xsl:when test="dates/@start != '' and dates/@end != ''"><xsl:value-of select="concat(dates/@start, '/', dates/@end)"/></xsl:when>
        <xsl:when test="dates/@start != ''"><xsl:value-of select="concat(dates/@start, '/..')"/></xsl:when>
        <xsl:when test="dates/@end != ''"><xsl:value-of select="concat('../', dates/@end)"/></xsl:when>
      </xsl:choose>
    </xsl:variable>
    <xsl:variable name="extent" select="normalize-space(extent)"/>

    <div class="rad-record rad-unit">
      <xsl:attribute name="data-id"><xsl:value-of select="@id"/></xsl:attribute>
      <xsl:attribute name="data-level"><xsl:value-of select="@level"/></xsl:attribute>
      <xsl:attribute name="data-status">
        <xsl:choose>
          <xsl:when test="@effective = 'published' or @effective = 'private'"><xsl:value-of select="@effective"/></xsl:when>
          <xsl:otherwise>draft</xsl:otherwise>
        </xsl:choose>
      </xsl:attribute>

      <xsl:if test="chain/unit">
        <p class="rad-part-of">
          <span class="rad-label">Part of: </span>
          <xsl:for-each select="chain/unit">
            <xsl:if test="position() &gt; 1"> › </xsl:if><xsl:value-of select="normalize-space(@title)"/>
          </xsl:for-each>
        </p>
      </xsl:if>

      <p class="rad-head" data-rule="1.1B">
        <span class="rad-title"><xsl:value-of select="normalize-space($full-title)"/></span><xsl:text>.</xsl:text>
        <xsl:if test="$dates != ''"><xsl:text> – </xsl:text><span class="rad-date" data-rule="1.4B2"><xsl:value-of select="$dates"/></span><xsl:text>.</xsl:text></xsl:if>
        <xsl:if test="$extent != ''"><xsl:text> – </xsl:text><span class="rad-physical" data-rule="1.5B"><xsl:value-of select="$extent"/></span></xsl:if>
      </p>

      <xsl:if test="$level != 'file'">
        <xsl:call-template name="paragraph"><xsl:with-param name="class" select="'rad-admin'"/><xsl:with-param name="rule" select="'1.7B'"/>
          <xsl:with-param name="label" select="'Administrative history / biographical sketch'"/><xsl:with-param name="text" select="normalize-space(admin-history)"/></xsl:call-template>
        <xsl:call-template name="paragraph"><xsl:with-param name="class" select="'rad-custodial'"/><xsl:with-param name="rule" select="'1.7C'"/>
          <xsl:with-param name="label" select="'Custodial history'"/><xsl:with-param name="text" select="normalize-space(custodial-history)"/></xsl:call-template>
        <xsl:call-template name="inherited"><xsl:with-param name="field" select="'custodial-history'"/><xsl:with-param name="class" select="'rad-custodial'"/><xsl:with-param name="rule" select="'1.7C'"/><xsl:with-param name="label" select="'Custodial history'"/></xsl:call-template>
      </xsl:if>
      <xsl:call-template name="paragraph"><xsl:with-param name="class" select="'rad-scope'"/><xsl:with-param name="rule" select="'1.7D'"/>
        <xsl:with-param name="label" select="'Scope and content'"/><xsl:with-param name="text" select="normalize-space(scope-content)"/></xsl:call-template>

      <div class="rad-notes">
        <xsl:call-template name="note"><xsl:with-param name="rule" select="'1.8B14'"/><xsl:with-param name="label" select="'Language'"/><xsl:with-param name="text" select="normalize-space(language)"/></xsl:call-template>
        <xsl:call-template name="inherited"><xsl:with-param name="field" select="'language'"/><xsl:with-param name="class" select="'rad-note'"/><xsl:with-param name="rule" select="'1.8B14'"/><xsl:with-param name="label" select="'Language'"/></xsl:call-template>
        <xsl:call-template name="note"><xsl:with-param name="rule" select="'1.8B16a'"/><xsl:with-param name="label" select="'Restrictions on access'"/><xsl:with-param name="text" select="normalize-space(access)"/></xsl:call-template>
        <xsl:call-template name="inherited"><xsl:with-param name="field" select="'access'"/><xsl:with-param name="class" select="'rad-note'"/><xsl:with-param name="rule" select="'1.8B16a'"/><xsl:with-param name="label" select="'Restrictions on access'"/></xsl:call-template>
        <xsl:call-template name="note"><xsl:with-param name="rule" select="'1.8B16c'"/><xsl:with-param name="label" select="'Terms governing use and reproduction'"/><xsl:with-param name="text" select="normalize-space(terms)"/></xsl:call-template>
        <xsl:call-template name="inherited"><xsl:with-param name="field" select="'terms'"/><xsl:with-param name="class" select="'rad-note'"/><xsl:with-param name="rule" select="'1.8B16c'"/><xsl:with-param name="label" select="'Terms governing use and reproduction'"/></xsl:call-template>
        <xsl:call-template name="note"><xsl:with-param name="rule" select="'1.8B19'"/><xsl:with-param name="label" select="'Accruals'"/><xsl:with-param name="text" select="normalize-space(accruals)"/></xsl:call-template>
        <xsl:call-template name="note"><xsl:with-param name="rule" select="'1.8B21'"/><xsl:with-param name="label" select="'General note'"/><xsl:with-param name="text" select="normalize-space(general-note)"/></xsl:call-template>
      </div>

      <xsl:if test="normalize-space(repository) != '' or ($repeat = 'yes' and inherited[@field = 'repository'])">
        <p class="rad-repository"><span class="rad-label">Repository: </span>
          <xsl:choose>
            <xsl:when test="normalize-space(repository) != ''"><xsl:value-of select="normalize-space(repository)"/></xsl:when>
            <xsl:otherwise><xsl:value-of select="normalize-space(inherited[@field = 'repository'])"/><xsl:text> (from </xsl:text><xsl:value-of select="inherited[@field = 'repository']/@from-title"/><xsl:text>)</xsl:text></xsl:otherwise>
          </xsl:choose>
        </p>
      </xsl:if>
    </div>
  </xsl:template>

  <!-- the inherited value of a field the unit leaves empty, printed only when the site repeats inherited fields (footnote 1a) -->
  <xsl:template name="inherited">
    <xsl:param name="field"/>
    <xsl:param name="class"/>
    <xsl:param name="rule"/>
    <xsl:param name="label"/>
    <xsl:if test="$repeat = 'yes' and normalize-space(*[local-name() = $field]) = '' and inherited[@field = $field]">
      <xsl:call-template name="paragraph">
        <xsl:with-param name="class" select="$class"/>
        <xsl:with-param name="rule" select="$rule"/>
        <xsl:with-param name="label" select="$label"/>
        <xsl:with-param name="text" select="concat(normalize-space(inherited[@field = $field]), ' (from ', inherited[@field = $field]/@from-title, ')')"/>
      </xsl:call-template>
    </xsl:if>
  </xsl:template>

  <!-- a full stop after text that does not already end a sentence -->
  <xsl:template name="full-stop">
    <xsl:param name="text"/>
    <xsl:if test="not(contains('.!?”', substring($text, string-length($text))))">.</xsl:if>
  </xsl:template>

  <xsl:template name="paragraph">
    <xsl:param name="class"/>
    <xsl:param name="rule"/>
    <xsl:param name="label"/>
    <xsl:param name="text"/>
    <xsl:if test="$text != ''">
      <p class="{$class}" data-rule="{$rule}"><span class="rad-label"><xsl:value-of select="$label"/>: </span><xsl:value-of select="$text"/><xsl:call-template name="full-stop"><xsl:with-param name="text" select="$text"/></xsl:call-template></p>
    </xsl:if>
  </xsl:template>

  <xsl:template name="note">
    <xsl:param name="rule"/>
    <xsl:param name="label"/>
    <xsl:param name="text"/>
    <xsl:call-template name="paragraph">
      <xsl:with-param name="class" select="'rad-note'"/>
      <xsl:with-param name="rule" select="$rule"/>
      <xsl:with-param name="label" select="$label"/>
      <xsl:with-param name="text" select="$text"/>
    </xsl:call-template>
  </xsl:template>
</xsl:stylesheet>
