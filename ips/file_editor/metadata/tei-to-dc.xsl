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

  <xsl:include href="tei-common.xsl"/>

  <xsl:output method="xml" encoding="UTF-8" indent="yes"/>
  <xsl:strip-space elements="*"/>

  <xsl:key name="by-text" match="t:author | t:respStmt/t:name" use="normalize-space(.)"/>

  <xsl:template match="/">
    <xsl:apply-templates select="(//t:sourceDesc/t:msDesc)[1]"/>
  </xsl:template>

  <xsl:template match="t:msDesc">
    <xsl:variable name="header" select="ancestor::t:teiHeader"/>
    <xsl:variable name="items" select="t:msContents/t:msItem"/>
    <xsl:variable name="stmttitle" select="normalize-space($header/t:fileDesc/t:titleStmt/t:title[normalize-space(.) != ''][1])"/>
    <xsl:variable name="summary" select="normalize-space(t:msContents/t:summary)"/>
    <!-- a printed book (section 9 of the mapping): the copy's msItem points at a biblStruct in the same file, which describes the publication -->
    <xsl:variable name="ptr" select="string(t:msContents/t:msItem/t:bibl/t:ptr/@target)"/>
    <xsl:variable name="bibl" select="//t:biblStruct[$ptr != '' and @xml:id = substring-after($ptr, '#')][1]"/>
    <xsl:variable name="mono" select="$bibl/t:monogr[1]"/>
    <!-- D2: a single msItem's title, then the titleStmt title, then the summary; a printed book's own title is the biblStruct's (main, else descriptive) -->
    <xsl:variable name="title">
      <xsl:choose>
        <xsl:when test="$mono/t:title[not(@type) or @type = 'main' or @type = 'desc'][normalize-space(.) != '']">
          <xsl:value-of select="normalize-space(($mono/t:title[@type = 'main' or not(@type)][normalize-space(.) != ''] | $mono/t:title[@type = 'desc'][normalize-space(.) != ''])[1])"/>
        </xsl:when>
        <xsl:otherwise><xsl:call-template name="msdesc-title"/></xsl:otherwise>
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
      <!-- an English title a cataloguer supplied, and a parallel title -->
      <xsl:for-each select="$mono/t:title[@type = 'translated' or @type = 'parallel' or @type = 'alt'][normalize-space(.) != '']">
        <dcterms:alternative><xsl:value-of select="normalize-space(.)"/></dcterms:alternative>
      </xsl:for-each>

      <!-- creators: msItem authors, else titleStmt authors, else a correspondent (respStmt/resp[@key='crp']); each name once -->
      <xsl:variable name="authors" select="$items/t:author[normalize-space(.) != ''] | $header/t:fileDesc/t:titleStmt/t:author[normalize-space(.) != ''] | $mono/t:author[normalize-space(.) != '']"/>
      <xsl:variable name="writers" select="$items/t:respStmt[t:resp/@key = 'crp']/t:name[normalize-space(.) != '']"/>
      <xsl:for-each select="($authors | $writers)[generate-id() = generate-id(key('by-text', normalize-space(.))[1])]">
        <dc:creator><xsl:value-of select="normalize-space(.)"/></dc:creator>
      </xsl:for-each>
      <!-- contributors: the other responsibility statements, scribes named in the hand description -->
      <xsl:for-each select="($items/t:respStmt[not(t:resp/@key = 'crp')]/t:name | $header/t:fileDesc/t:titleStmt/t:respStmt/t:name | t:physDesc/t:handDesc/t:handNote/t:persName | $mono/t:editor | $mono/t:respStmt/t:name)[normalize-space(.) != '']">
        <dc:contributor><xsl:value-of select="normalize-space(.)"/></dc:contributor>
      </xsl:for-each>

      <!-- dates: an ISO 8601 machine value (a date, or an interval "start/end"), never the display text; a value that is not ISO 8601 gives no date -->
      <xsl:variable name="date">
        <xsl:for-each select="(t:history/t:origin/t:origDate | $mono/t:imprint/t:date)[1]"><xsl:call-template name="origdate-iso"/></xsl:for-each>
      </xsl:variable>
      <xsl:if test="$date != ''">
        <dc:date><xsl:value-of select="$date"/></dc:date>
        <!-- the date of publication is not a date of creation -->
        <xsl:if test="not($bibl)"><dcterms:created><xsl:value-of select="$date"/></dcterms:created></xsl:if>
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
      <xsl:if test="$bibl and not($items/@class)"><dc:type>Text</dc:type></xsl:if>

      <xsl:for-each select="t:physDesc/t:objectDesc/@form[. != '']">
        <dc:format><xsl:call-template name="form-label"><xsl:with-param name="code" select="."/></xsl:call-template></dc:format>
      </xsl:for-each>
      <xsl:for-each select="t:physDesc/t:objectDesc/t:supportDesc/@material[. != '']">
        <dcterms:medium><xsl:call-template name="material-label"><xsl:with-param name="code" select="."/></xsl:call-template></dcterms:medium>
      </xsl:for-each>
      <xsl:call-template name="extent"/>

      <xsl:for-each select="t:msIdentifier/t:idno[normalize-space(.) != ''] | t:msIdentifier/t:altIdentifier[not(@type = 'image-label')]/t:idno[normalize-space(.) != '']">
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
      <xsl:choose>
        <!-- a printed book: the publisher is the imprint's, and the holder is the custodian of this copy (mapping section 9, D16) -->
        <xsl:when test="$bibl">
          <xsl:for-each select="$mono/t:imprint/t:publisher[normalize-space(.) != ''][1]"><dc:publisher><xsl:value-of select="normalize-space(.)"/></dc:publisher></xsl:for-each>
          <xsl:variable name="place" select="normalize-space($mono/t:imprint/t:pubPlace[1])"/>
          <xsl:variable name="house" select="normalize-space($mono/t:imprint/t:publisher[1])"/>
          <xsl:variable name="when" select="normalize-space($mono/t:imprint/t:date[1][normalize-space(.) != 'n.d.'])"/>
          <xsl:variable name="ed" select="normalize-space($mono/t:edition[1])"/>
          <xsl:variable name="cite" select="concat($place, substring(': ', 1, 2 * number($place != '' and $house != '')), $house,
              substring(', ', 1, 2 * number(($place != '' or $house != '') and $when != '')), $when)"/>
          <xsl:if test="$cite != '' or $ed != ''">
            <dc:source><xsl:value-of select="$cite"/><xsl:if test="$ed != ''"><xsl:if test="$cite != ''">. </xsl:if>Edition: <xsl:value-of select="$ed"/></xsl:if></dc:source>
          </xsl:if>
        </xsl:when>
        <xsl:when test="$publisher != ''"><dc:publisher><xsl:value-of select="$publisher"/></dc:publisher></xsl:when>
      </xsl:choose>

      <xsl:variable name="provenance">
        <xsl:if test="$bibl and (t:msIdentifier/t:collection != '' or $publisher != '')">Held <xsl:if test="normalize-space(t:msIdentifier/t:collection[1]) != ''">in <xsl:value-of select="normalize-space(t:msIdentifier/t:collection[1])"/></xsl:if><xsl:if test="$publisher != ''"><xsl:text> (</xsl:text><xsl:value-of select="$publisher"/><xsl:text>)</xsl:text></xsl:if>.</xsl:if>
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

  <!-- dcterms:extent: "16 leaves; 18.2-18.7 x 12.0-12.2 cm" (tei-common.xsl gives the two parts) -->
  <xsl:template name="extent">
    <xsl:variable name="leaves"><xsl:call-template name="leaves"/></xsl:variable>
    <xsl:variable name="size"><xsl:call-template name="size"/></xsl:variable>
    <xsl:if test="$leaves != '' or $size != ''">
      <dcterms:extent>
        <xsl:value-of select="$leaves"/>
        <xsl:if test="$leaves != '' and $size != ''">; </xsl:if>
        <xsl:value-of select="$size"/>
      </dcterms:extent>
    </xsl:if>
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

</xsl:stylesheet>
