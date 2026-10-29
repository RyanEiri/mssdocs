<?xml version="1.0" encoding="UTF-8"?>
<!--
  Dublin Core (qualified, dcterms) from the TEI header of a manuscript description (msDesc), XSLT 1.0.
  The mapping and the reasons for each choice are in docs/mssdocs-metadata-mapping.md of the webserversAC repository (section 4); the labels for coded values (section 7a) are in the
  named templates at the end of this file.

  Output: <record id="…" status="…" lang="…"> holding dc: and dcterms: elements, in the mapping's order. Fields the record does not hold are left out, never filled in.
  status is revisionDesc/@status (draft, published or private); a record with none is draft. The stylesheet does not decide who may see a record: that is for the caller.
  Run by PHP's XSLTProcessor and by lxml (the tests); it needs nothing beyond XSLT 1.0.
-->
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:t="http://www.tei-c.org/ns/1.0"
    xmlns:dc="http://purl.org/dc/elements/1.1/"
    xmlns:dcterms="http://purl.org/dc/terms/"
    exclude-result-prefixes="t">

  <xsl:output method="xml" encoding="UTF-8" indent="yes"/>
  <xsl:strip-space elements="*"/>

  <xsl:key name="by-text" match="t:author | t:respStmt/t:name" use="normalize-space(.)"/>

  <xsl:template match="/">
    <xsl:apply-templates select="(//t:sourceDesc/t:msDesc)[1]"/>
  </xsl:template>

  <xsl:template match="t:msDesc">
    <xsl:variable name="header" select="ancestor::t:teiHeader"/>
    <xsl:variable name="items" select="t:msContents/t:msItem"/>
    <xsl:variable name="itemtitle" select="normalize-space($items/t:title)"/>
    <xsl:variable name="stmttitle" select="normalize-space($header/t:fileDesc/t:titleStmt/t:title[normalize-space(.) != ''][1])"/>
    <xsl:variable name="summary" select="normalize-space(t:msContents/t:summary)"/>
    <!-- D2: a single msItem's title, then the titleStmt title, then the summary -->
    <xsl:variable name="title">
      <xsl:choose>
        <xsl:when test="count($items) = 1 and $itemtitle != ''"><xsl:value-of select="$itemtitle"/></xsl:when>
        <xsl:when test="$stmttitle != ''"><xsl:value-of select="$stmttitle"/></xsl:when>
        <xsl:otherwise><xsl:value-of select="$summary"/></xsl:otherwise>
      </xsl:choose>
    </xsl:variable>
    <xsl:variable name="status" select="normalize-space($header/t:revisionDesc/@status)"/>

    <record>
      <xsl:attribute name="id"><xsl:value-of select="@xml:id"/></xsl:attribute>
      <xsl:attribute name="status">
        <xsl:choose>
          <xsl:when test="$status = 'published' or $status = 'private'"><xsl:value-of select="$status"/></xsl:when>
          <xsl:otherwise>draft</xsl:otherwise>
        </xsl:choose>
      </xsl:attribute>
      <xsl:if test="@xml:lang"><xsl:attribute name="lang"><xsl:value-of select="@xml:lang"/></xsl:attribute></xsl:if>

      <xsl:if test="$title != ''"><dc:title><xsl:value-of select="$title"/></dc:title></xsl:if>
      <xsl:for-each select="t:msIdentifier/t:msName[normalize-space(.) != '']">
        <dcterms:alternative><xsl:value-of select="normalize-space(.)"/></dcterms:alternative>
      </xsl:for-each>
      <xsl:if test="$stmttitle != '' and $stmttitle != $title">
        <dcterms:alternative><xsl:value-of select="$stmttitle"/></dcterms:alternative>
      </xsl:if>

      <!-- creators: msItem authors, else titleStmt authors, else a correspondent (respStmt/resp[@key='crp']); each name once -->
      <xsl:variable name="authors" select="$items/t:author[normalize-space(.) != ''] | $header/t:fileDesc/t:titleStmt/t:author[normalize-space(.) != '']"/>
      <xsl:variable name="writers" select="$items/t:respStmt[t:resp/@key = 'crp']/t:name[normalize-space(.) != '']"/>
      <xsl:for-each select="($authors | $writers)[generate-id() = generate-id(key('by-text', normalize-space(.))[1])]">
        <dc:creator><xsl:value-of select="normalize-space(.)"/></dc:creator>
      </xsl:for-each>
      <!-- contributors: the other responsibility statements, scribes named in the hand description -->
      <xsl:for-each select="($items/t:respStmt[not(t:resp/@key = 'crp')]/t:name | $header/t:fileDesc/t:titleStmt/t:respStmt/t:name | t:physDesc/t:handDesc/t:handNote/t:persName)[normalize-space(.) != '']">
        <dc:contributor><xsl:value-of select="normalize-space(.)"/></dc:contributor>
      </xsl:for-each>

      <!-- dates: a machine value (when, or an interval), never the display text -->
      <xsl:variable name="date">
        <xsl:for-each select="t:history/t:origin/t:origDate[1]">
          <xsl:choose>
            <xsl:when test="@when"><xsl:value-of select="@when"/></xsl:when>
            <xsl:when test="@notBefore and @notAfter"><xsl:value-of select="concat(@notBefore, '/', @notAfter)"/></xsl:when>
            <xsl:when test="@from and @to"><xsl:value-of select="concat(@from, '/', @to)"/></xsl:when>
          </xsl:choose>
        </xsl:for-each>
      </xsl:variable>
      <xsl:if test="$date != ''">
        <dc:date><xsl:value-of select="$date"/></dc:date>
        <dcterms:created><xsl:value-of select="$date"/></dcterms:created>
      </xsl:if>
      <xsl:for-each select="t:history/t:origin/t:origPlace[1][normalize-space(.) != '']">
        <dcterms:spatial><xsl:value-of select="normalize-space(.)"/></dcterms:spatial>
      </xsl:for-each>

      <!-- descriptions: the summary (unless it is the title), then illustration notes -->
      <xsl:if test="$summary != '' and $summary != $title"><dc:description><xsl:value-of select="$summary"/></dc:description></xsl:if>
      <xsl:for-each select="t:physDesc/t:decoDesc/t:decoNote[normalize-space(.) != '']">
        <dc:description><xsl:value-of select="normalize-space(.)"/></dc:description>
      </xsl:for-each>

      <xsl:for-each select="$header/t:profileDesc/t:textClass/t:keywords/t:term[normalize-space(.) != '']">
        <dc:subject><xsl:value-of select="normalize-space(.)"/></dc:subject>
      </xsl:for-each>
      <xsl:for-each select="$items[@class][not(@class = preceding-sibling::t:msItem/@class)]">
        <xsl:call-template name="class-subjects"><xsl:with-param name="codes" select="normalize-space(@class)"/></xsl:call-template>
      </xsl:for-each>

      <xsl:call-template name="types"/>

      <xsl:for-each select="t:physDesc/t:objectDesc/@form[. != '']">
        <dc:format><xsl:call-template name="form-label"><xsl:with-param name="code" select="."/></xsl:call-template></dc:format>
      </xsl:for-each>
      <xsl:for-each select="t:physDesc/t:objectDesc/t:supportDesc/@material[. != '']">
        <dcterms:medium><xsl:call-template name="material-label"><xsl:with-param name="code" select="."/></xsl:call-template></dcterms:medium>
      </xsl:for-each>
      <xsl:call-template name="extent"/>

      <xsl:for-each select="t:msIdentifier/t:idno[normalize-space(.) != ''] | t:msIdentifier/t:altIdentifier/t:idno[normalize-space(.) != '']">
        <dc:identifier><xsl:value-of select="normalize-space(.)"/></dc:identifier>
      </xsl:for-each>

      <!-- languages: the main language, then the others -->
      <xsl:for-each select="t:msContents/t:textLang[@mainLang]">
        <dc:language><xsl:value-of select="@mainLang"/></dc:language>
        <xsl:call-template name="tokens">
          <xsl:with-param name="list" select="normalize-space(@otherLangs)"/>
          <xsl:with-param name="element" select="'dc:language'"/>
        </xsl:call-template>
      </xsl:for-each>

      <!-- publisher: the holder of the original (D6) -->
      <xsl:variable name="r" select="normalize-space(t:msIdentifier/t:repository)"/>
      <xsl:variable name="i" select="normalize-space(t:msIdentifier/t:institution)"/>
      <xsl:variable name="s" select="normalize-space(t:msIdentifier/t:settlement)"/>
      <xsl:variable name="c" select="normalize-space(t:msIdentifier/t:country)"/>
      <xsl:variable name="publisher" select="concat($r,
          substring(', ', 1, 2 * number($r != '' and $i != '')), $i,
          substring(', ', 1, 2 * number(($r != '' or $i != '') and $s != '')), $s,
          substring(', ', 1, 2 * number(($r != '' or $i != '' or $s != '') and $c != '')), $c)"/>
      <xsl:if test="$publisher != ''"><dc:publisher><xsl:value-of select="$publisher"/></dc:publisher></xsl:if>

      <xsl:variable name="provenance">
        <xsl:for-each select="(t:history/t:provenance | t:history/t:acquisition | t:additional/t:adminInfo/t:custodialHist)[normalize-space(.) != '']">
          <xsl:if test="position() &gt; 1"><xsl:text> </xsl:text></xsl:if>
          <xsl:value-of select="normalize-space(.)"/>
        </xsl:for-each>
      </xsl:variable>
      <xsl:if test="normalize-space($provenance) != ''"><dcterms:provenance><xsl:value-of select="normalize-space($provenance)"/></dcterms:provenance></xsl:if>

      <xsl:for-each select="t:msIdentifier/t:collection[normalize-space(.) != '']">
        <dcterms:isPartOf><xsl:value-of select="normalize-space(.)"/></dcterms:isPartOf>
      </xsl:for-each>
      <xsl:for-each select="t:msPart/t:msIdentifier/t:idno[normalize-space(.) != '']">
        <dcterms:hasPart><xsl:value-of select="normalize-space(.)"/></dcterms:hasPart>
      </xsl:for-each>
      <xsl:for-each select="t:additional/t:listBibl/t:bibl[normalize-space(.) != '']">
        <dcterms:references><xsl:value-of select="normalize-space(.)"/></dcterms:references>
      </xsl:for-each>
      <xsl:for-each select="t:additional/t:surrogates[normalize-space(.) != '']">
        <dcterms:hasFormat><xsl:value-of select="normalize-space(.)"/></dcterms:hasFormat>
      </xsl:for-each>
      <xsl:for-each select="t:additional/t:adminInfo/t:availability[1]">
        <xsl:variable name="spoken">
          <xsl:for-each select="t:p"><xsl:value-of select="concat(' ', normalize-space(.))"/></xsl:for-each>
        </xsl:variable>
        <xsl:variable name="said" select="normalize-space($spoken)"/>
        <xsl:choose>
          <xsl:when test="$said != ''"><dcterms:accessRights><xsl:value-of select="$said"/></dcterms:accessRights></xsl:when>
          <xsl:when test="@status = 'free'"><dcterms:accessRights>Free</dcterms:accessRights></xsl:when>
          <xsl:when test="@status = 'restricted'"><dcterms:accessRights>Restricted</dcterms:accessRights></xsl:when>
        </xsl:choose>
        <xsl:for-each select="t:licence[normalize-space(.) != '']">
          <dcterms:license><xsl:value-of select="normalize-space(.)"/></dcterms:license>
        </xsl:for-each>
      </xsl:for-each>
    </record>
  </xsl:template>

  <!-- dcterms:extent: "16 leaves; 18.2-18.7 x 12.0-12.2 cm" (the support's extent text, else the book-block count; then the leaf size) -->
  <xsl:template name="extent">
    <xsl:variable name="support" select="t:physDesc/t:objectDesc/t:supportDesc/t:support"/>
    <xsl:variable name="count">
      <xsl:for-each select="$support/t:num[@type = 'book-block'][1]">
        <xsl:choose>
          <xsl:when test="@value and number(@value) = number(@value)"><xsl:value-of select="number(@value)"/></xsl:when>
          <xsl:when test="number(normalize-space(.)) = number(normalize-space(.))"><xsl:value-of select="number(normalize-space(.))"/></xsl:when>
        </xsl:choose>
      </xsl:for-each>
    </xsl:variable>
    <xsl:variable name="leaves">
      <xsl:choose>
        <xsl:when test="normalize-space($support/t:extent) != ''"><xsl:value-of select="normalize-space($support/t:extent)"/></xsl:when>
        <xsl:when test="$count = '1'">1 leaf</xsl:when>
        <xsl:when test="$count != ''"><xsl:value-of select="$count"/> leaves</xsl:when>
      </xsl:choose>
    </xsl:variable>
    <xsl:variable name="size">
      <xsl:for-each select="$support/t:dimensions[t:height and t:width][1]">
        <xsl:call-template name="measure"><xsl:with-param name="node" select="t:height"/></xsl:call-template>
        <xsl:text> x </xsl:text>
        <xsl:call-template name="measure"><xsl:with-param name="node" select="t:width"/></xsl:call-template>
        <xsl:text> cm</xsl:text>
      </xsl:for-each>
    </xsl:variable>
    <xsl:if test="$leaves != '' or $size != ''">
      <dcterms:extent>
        <xsl:value-of select="$leaves"/>
        <xsl:if test="$leaves != '' and $size != ''">; </xsl:if>
        <xsl:value-of select="$size"/>
      </dcterms:extent>
    </xsl:if>
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

  <!-- dc:type from the classes of the items: StillImage for a graphic class, Text for every other -->
  <xsl:template name="types">
    <xsl:variable name="classes">
      <xsl:for-each select="t:msContents/t:msItem[@class]"><xsl:value-of select="concat(' ', normalize-space(@class))"/></xsl:for-each>
    </xsl:variable>
    <xsl:variable name="all" select="concat(normalize-space($classes), ' ')"/>
    <xsl:if test="contains(concat(' ', $all), ' myndl ')"><dc:type>StillImage</dc:type></xsl:if>
    <xsl:variable name="others">
      <xsl:call-template name="non-image"><xsl:with-param name="list" select="$all"/></xsl:call-template>
    </xsl:variable>
    <xsl:if test="$others != ''"><dc:type>Text</dc:type></xsl:if>
  </xsl:template>

  <xsl:template name="non-image">
    <xsl:param name="list"/>
    <xsl:if test="normalize-space($list) != ''">
      <xsl:variable name="first" select="substring-before(concat(normalize-space($list), ' '), ' ')"/>
      <xsl:if test="$first != 'myndl'">y</xsl:if>
      <xsl:call-template name="non-image"><xsl:with-param name="list" select="substring-after(normalize-space($list), ' ')"/></xsl:call-template>
    </xsl:if>
  </xsl:template>

  <!-- a dc:subject for each class code whose label is confirmed (section 7a); a code whose label is only inferred gives none until the classdecl taxonomy confirms it -->
  <xsl:template name="class-subjects">
    <xsl:param name="codes"/>
    <xsl:if test="$codes != ''">
      <xsl:variable name="code" select="substring-before(concat($codes, ' '), ' ')"/>
      <xsl:variable name="label">
        <xsl:choose>
          <xsl:when test="$code = 'fas'">Legendary sagas</xsl:when>
          <xsl:when test="$code = 'ridd'">Indigenous chivalric romances</xsl:when>
          <xsl:when test="$code = 'edd'">Eddic poetry</xsl:when>
          <xsl:when test="$code = 'isl'">Icelandic family sagas</xsl:when>
          <xsl:when test="$code = 'kvad'">Poetry of various kinds, other than Eddic</xsl:when>
          <xsl:when test="$code = 'kon'">Kings' sagas</xsl:when>
          <xsl:when test="$code = 'islt'">Short tales of Icelanders</xsl:when>
          <xsl:when test="$code = 'rimur'">Metrical romances (rímur)</xsl:when>
          <xsl:when test="$code = 'forns'">Post-mediaeval legendary sagas</xsl:when>
          <xsl:when test="$code = 'avint'">Exempla</xsl:when>
          <xsl:when test="$code = 'konth'">Short tales of Norwegian kings</xsl:when>
          <xsl:when test="$code = 'almb'">Chapbooks</xsl:when>
          <xsl:when test="$code = 'riddst'">Post-mediaeval chivalric romances</xsl:when>
          <xsl:when test="$code = 'sendbr'">Letters</xsl:when>
          <xsl:when test="$code = 'salm'">Hymns</xsl:when>
          <xsl:when test="$code = 'myndl'">Graphical material</xsl:when>
        </xsl:choose>
      </xsl:variable>
      <xsl:if test="$label != ''"><dc:subject><xsl:value-of select="$label"/></dc:subject></xsl:if>
      <xsl:call-template name="class-subjects"><xsl:with-param name="codes" select="substring-after($codes, ' ')"/></xsl:call-template>
    </xsl:if>
  </xsl:template>

  <xsl:template name="tokens">
    <xsl:param name="list"/>
    <xsl:param name="element"/>
    <xsl:if test="$list != ''">
      <xsl:element name="{$element}" namespace="http://purl.org/dc/elements/1.1/"><xsl:value-of select="substring-before(concat($list, ' '), ' ')"/></xsl:element>
      <xsl:call-template name="tokens">
        <xsl:with-param name="list" select="substring-after($list, ' ')"/>
        <xsl:with-param name="element" select="$element"/>
      </xsl:call-template>
    </xsl:if>
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
