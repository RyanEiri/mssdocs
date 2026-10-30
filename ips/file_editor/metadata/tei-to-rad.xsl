<?xml version="1.0" encoding="UTF-8"?>
<!--
  A RAD description (Rules for Archival Description, July 2008, chapter 13 "Discrete items") of one manuscript, from the TEI msDesc, as an XHTML fragment, XSLT 1.0.
  The rules each part follows and the decisions behind them are in docs/mssdocs-metadata-mapping.md of the webserversAC repository (section 5). Every paragraph carries its RAD rule
  in data-rule.

  Layout, as RAD's own examples print an item (13.10): a first line "Title proper. - date(s). - physical description", then the archival description (scope and content, custodial
  history), then a paragraph for each note, then the access points. Parts the record does not hold are left out, never filled in. The level of description is not printed: RAD has no
  field for it (it is for the AtoM export), and a generated "Item is ..." could not be made grammatical.
  The stylesheet does not decide who may see a record: record/@data-status is revisionDesc/@status for the caller (a record with none is a draft).
-->
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:t="http://www.tei-c.org/ns/1.0"
    exclude-result-prefixes="t">

  <xsl:include href="tei-common.xsl"/>

  <xsl:output method="xml" encoding="UTF-8" indent="yes" omit-xml-declaration="yes"/>
  <xsl:strip-space elements="*"/>

  <xsl:key name="by-name" match="t:author | t:respStmt/t:name | t:persName" use="normalize-space(.)"/>
  <xsl:key name="by-place" match="t:origPlace | t:placeName" use="normalize-space(.)"/>

  <xsl:template match="/">
    <xsl:apply-templates select="(//t:sourceDesc/t:msDesc)[1]"/>
  </xsl:template>

  <xsl:template match="t:msDesc">
    <xsl:variable name="header" select="ancestor::t:teiHeader"/>
    <xsl:variable name="items" select="t:msContents/t:msItem"/>
    <xsl:variable name="status" select="normalize-space($header/t:revisionDesc/@status)"/>
    <!-- a printed book (mapping section 9): the copy's msItem points at a biblStruct in the same file that describes the publication -->
    <xsl:variable name="ptr" select="string(t:msContents/t:msItem/t:bibl/t:ptr/@target)"/>
    <xsl:variable name="bibl" select="//t:biblStruct[$ptr != '' and @xml:id = substring-after($ptr, '#')][1]"/>
    <xsl:variable name="mono" select="$bibl/t:monogr[1]"/>
    <xsl:variable name="title">
      <xsl:choose>
        <xsl:when test="$mono/t:title[not(@type) or @type = 'main' or @type = 'desc'][normalize-space(.) != '']">
          <xsl:value-of select="normalize-space(($mono/t:title[@type = 'main' or not(@type)][normalize-space(.) != ''] | $mono/t:title[@type = 'desc'][normalize-space(.) != ''])[1])"/>
        </xsl:when>
        <xsl:otherwise><xsl:call-template name="msdesc-title"/></xsl:otherwise>
      </xsl:choose>
    </xsl:variable>
    <xsl:variable name="title-source"><xsl:call-template name="msdesc-title-source"/></xsl:variable>

    <div class="rad-record">
      <xsl:attribute name="data-id"><xsl:value-of select="@xml:id"/></xsl:attribute>
      <xsl:attribute name="data-status">
        <xsl:choose>
          <xsl:when test="$status = 'published' or $status = 'private'"><xsl:value-of select="$status"/></xsl:when>
          <xsl:otherwise>draft</xsl:otherwise>
        </xsl:choose>
      </xsl:attribute>

      <!-- title and statement of responsibility (1.1), date (1.4), physical description (1.5) -->
      <xsl:variable name="transcribed" select="($items/t:author[@source = 'transcribed'] | $header/t:fileDesc/t:titleStmt/t:author[@source = 'transcribed'])[normalize-space(.) != ''][1]"/>
      <xsl:variable name="date"><xsl:call-template name="rad-date"/></xsl:variable>
      <xsl:variable name="physical"><xsl:call-template name="rad-physical"/></xsl:variable>
      <xsl:if test="$title != ''">
        <p class="rad-head" data-rule="1.1B">
          <span class="rad-title"><xsl:value-of select="$title"/></span>
          <xsl:if test="$transcribed">
            <xsl:text> / </xsl:text><span class="rad-responsibility" data-rule="1.1F"><xsl:value-of select="normalize-space($transcribed)"/></span>
          </xsl:if>
          <xsl:text>.</xsl:text>
          <xsl:if test="$bibl">
            <xsl:call-template name="rad-publication"><xsl:with-param name="mono" select="$mono"/></xsl:call-template>
          </xsl:if>
          <xsl:if test="$date != '' and not($bibl)"><xsl:text> – </xsl:text><span class="rad-date" data-rule="1.4B2"><xsl:value-of select="$date"/></span><xsl:text>.</xsl:text></xsl:if>
          <xsl:if test="$physical != ''"><xsl:text> – </xsl:text><span class="rad-physical" data-rule="1.5"><xsl:value-of select="$physical"/></span></xsl:if>
        </p>
      </xsl:if>

      <!-- archival description (1.7): scope and content, custodial history -->
      <xsl:variable name="summary" select="normalize-space(t:msContents/t:summary)"/>
      <xsl:variable name="contents">
        <xsl:for-each select="$items[normalize-space(t:title) != '' or normalize-space(t:locus) != '']">
          <xsl:if test="position() &gt; 1"><xsl:text>; </xsl:text></xsl:if>
          <xsl:value-of select="normalize-space(t:locus)"/>
          <xsl:if test="normalize-space(t:locus) != '' and normalize-space(t:title) != ''">, </xsl:if>
          <xsl:value-of select="normalize-space(t:title)"/>
        </xsl:for-each>
      </xsl:variable>
      <xsl:variable name="scope">
        <xsl:if test="$summary != '' and $summary != $title"><xsl:value-of select="$summary"/></xsl:if>
        <xsl:if test="$summary != '' and $summary != $title and $contents != ''">. </xsl:if>
        <xsl:if test="$contents != '' and not($summary = '' and count($items) = 1 and $contents = $title)">Contents: <xsl:value-of select="$contents"/></xsl:if>
      </xsl:variable>
      <xsl:call-template name="paragraph">
        <xsl:with-param name="class" select="'rad-scope'"/>
        <xsl:with-param name="rule" select="'13.7D'"/>
        <xsl:with-param name="label" select="'Scope and content'"/>
        <xsl:with-param name="text" select="normalize-space($scope)"/>
      </xsl:call-template>
      <xsl:variable name="custody">
        <xsl:for-each select="(t:history/t:provenance | t:additional/t:adminInfo/t:custodialHist)[normalize-space(.) != '']">
          <xsl:if test="position() &gt; 1"><xsl:text> </xsl:text></xsl:if>
          <xsl:value-of select="normalize-space(.)"/>
        </xsl:for-each>
      </xsl:variable>
      <xsl:call-template name="paragraph">
        <xsl:with-param name="class" select="'rad-custodial'"/>
        <xsl:with-param name="rule" select="'13.7C'"/>
        <xsl:with-param name="label" select="'Custodial history'"/>
        <xsl:with-param name="text" select="$custody"/>
      </xsl:call-template>

      <!-- notes (1.8B) -->
      <div class="rad-notes">
        <xsl:if test="$title-source = 'item' and $items/t:title/@type = 'supplied' and not($bibl)">
          <xsl:call-template name="note"><xsl:with-param name="rule" select="'1.8B2'"/><xsl:with-param name="label" select="'Source of title proper'"/>
            <xsl:with-param name="text" select="'Title based on the contents of the manuscript.'"/></xsl:call-template>
        </xsl:if>
        <xsl:variable name="variants">
          <xsl:for-each select="t:msIdentifier/t:msName[normalize-space(.) != '']">
            <xsl:if test="position() &gt; 1"><xsl:text>; </xsl:text></xsl:if><xsl:value-of select="normalize-space(.)"/>
          </xsl:for-each>
        </xsl:variable>
        <xsl:call-template name="note"><xsl:with-param name="rule" select="'1.8B1'"/><xsl:with-param name="label" select="'Variations in title'"/>
          <xsl:with-param name="text" select="concat(substring('Also known as: ', 1, 15 * number($variants != '')), $variants)"/></xsl:call-template>

        <xsl:variable name="makers">
          <xsl:for-each select="($items/t:author | $header/t:fileDesc/t:titleStmt/t:author | $mono/t:author)[normalize-space(.) != '' and not(@source = 'transcribed') and not(@cert = 'low' or @cert = 'medium')][generate-id() = generate-id(key('by-name', normalize-space(.))[1])]">
            <xsl:if test="position() &gt; 1"><xsl:text>; </xsl:text></xsl:if><xsl:value-of select="normalize-space(.)"/>
          </xsl:for-each>
        </xsl:variable>
        <xsl:call-template name="note"><xsl:with-param name="rule" select="'1.8B5'"/><xsl:with-param name="label" select="'Statement of responsibility'"/>
          <xsl:with-param name="text" select="concat(substring('By ', 1, 3 * number($makers != '')), $makers)"/></xsl:call-template>
        <!-- editors and translators of a printed book (1.8B5) -->
        <xsl:variable name="editors"><xsl:for-each select="$mono/t:editor[normalize-space(.) != '']"><xsl:if test="position() &gt; 1"><xsl:text>; </xsl:text></xsl:if><xsl:value-of select="normalize-space(.)"/></xsl:for-each></xsl:variable>
        <xsl:call-template name="note"><xsl:with-param name="rule" select="'1.8B5'"/><xsl:with-param name="label" select="'Editors'"/>
          <xsl:with-param name="text" select="concat(substring('Edited by ', 1, 10 * number($editors != '')), $editors)"/></xsl:call-template>
        <xsl:variable name="translators"><xsl:for-each select="$mono/t:respStmt[normalize-space(t:resp) = 'Translator']/t:name[normalize-space(.) != '']"><xsl:if test="position() &gt; 1"><xsl:text>; </xsl:text></xsl:if><xsl:value-of select="normalize-space(.)"/></xsl:for-each></xsl:variable>
        <xsl:call-template name="note"><xsl:with-param name="rule" select="'1.8B5'"/><xsl:with-param name="label" select="'Translators'"/>
          <xsl:with-param name="text" select="concat(substring('Translated by ', 1, 14 * number($translators != '')), $translators)"/></xsl:call-template>
        <xsl:variable name="attributed">
          <xsl:for-each select="($items/t:author | $header/t:fileDesc/t:titleStmt/t:author)[normalize-space(.) != '' and (@cert = 'low' or @cert = 'medium')][generate-id() = generate-id(key('by-name', normalize-space(.))[1])]">
            <xsl:if test="position() &gt; 1"><xsl:text>; </xsl:text></xsl:if><xsl:value-of select="normalize-space(.)"/>
          </xsl:for-each>
        </xsl:variable>
        <xsl:call-template name="note"><xsl:with-param name="rule" select="'1.8B6'"/><xsl:with-param name="label" select="'Attributions and conjectures'"/>
          <xsl:with-param name="text" select="concat(substring('Attributed to ', 1, 14 * number($attributed != '')), $attributed)"/></xsl:call-template>

        <!-- dates and place of creation (1.8B8) -->
        <xsl:variable name="origin" select="t:history/t:origin"/>
        <xsl:variable name="evidence">
          <xsl:choose>
            <xsl:when test="$origin/t:origDate[1]/@evidence = 'internal'">Date based on internal evidence.</xsl:when>
            <xsl:when test="$origin/t:origDate[1]/@evidence = 'external'">Date based on external evidence.</xsl:when>
            <xsl:when test="$origin/t:origDate[1]/@evidence = 'conjecture'">Date is conjectural.</xsl:when>
          </xsl:choose>
        </xsl:variable>
        <xsl:variable name="said" select="normalize-space($origin/t:origDate[1])"/>
        <xsl:variable name="datewords">
          <xsl:if test="$said != '' and not($origin/t:origDate[1]/@when = $said) and not(number($said) = number($said))"><xsl:value-of select="$said"/></xsl:if>
        </xsl:variable>
        <xsl:variable name="place">
          <xsl:if test="normalize-space($origin/t:origPlace[1]) != ''">Place of creation: <xsl:value-of select="normalize-space($origin/t:origPlace[1])"/>.</xsl:if>
        </xsl:variable>
        <xsl:call-template name="note"><xsl:with-param name="rule" select="'1.8B8'"/><xsl:with-param name="label" select="'Dates'"/>
          <xsl:with-param name="text" select="normalize-space(concat($evidence, ' ', $datewords, ' ', $place))"/></xsl:call-template>

        <!-- physical description (1.8B9), condition (1.8B9a), conservation (1.8B9b), accompanying material (1.8B9c) -->
        <xsl:variable name="physdesc">
          <xsl:for-each select="(t:physDesc/t:handDesc//t:p | t:physDesc/t:decoDesc//t:p | t:physDesc/t:additions//t:p | t:physDesc/t:bindingDesc//t:p | t:physDesc/t:objectDesc/t:supportDesc/t:foliation[normalize-space(.) != ''])[normalize-space(.) != '']">
            <xsl:if test="position() &gt; 1"><xsl:text> </xsl:text></xsl:if><xsl:value-of select="normalize-space(.)"/>
            <xsl:call-template name="full-stop"><xsl:with-param name="text" select="normalize-space(.)"/></xsl:call-template>
          </xsl:for-each>
        </xsl:variable>
        <xsl:call-template name="note"><xsl:with-param name="rule" select="'1.8B9'"/><xsl:with-param name="label" select="'Physical description'"/><xsl:with-param name="text" select="$physdesc"/></xsl:call-template>
        <xsl:variable name="condition">
          <xsl:choose>
            <xsl:when test="normalize-space(t:physDesc//t:condition) != ''"><xsl:value-of select="normalize-space(t:physDesc//t:condition)"/></xsl:when>
            <xsl:when test="t:physDesc//t:condition/@ana = 'poor'">Condition is poor.</xsl:when>
          </xsl:choose>
        </xsl:variable>
        <xsl:call-template name="note"><xsl:with-param name="rule" select="'1.8B9a'"/><xsl:with-param name="label" select="'Physical condition'"/><xsl:with-param name="text" select="$condition"/></xsl:call-template>
        <xsl:call-template name="note"><xsl:with-param name="rule" select="'1.8B9b'"/><xsl:with-param name="label" select="'Conservation'"/>
          <xsl:with-param name="text" select="normalize-space(t:additional/t:adminInfo/t:custodialHist/t:custEvent[@type = 'conservation'])"/></xsl:call-template>
        <xsl:variable name="accompanying">
          <xsl:for-each select="t:physDesc/t:accMat//t:item[normalize-space(.) != '']">
            <xsl:if test="position() &gt; 1"><xsl:text> </xsl:text></xsl:if>
            <xsl:value-of select="concat(position(), '. ', normalize-space(.))"/>
          </xsl:for-each>
        </xsl:variable>
        <xsl:call-template name="note"><xsl:with-param name="rule" select="'1.8B9c'"/><xsl:with-param name="label" select="'Accompanying material'"/>
          <xsl:with-param name="text" select="normalize-space(concat($accompanying, ' ', normalize-space(t:physDesc/t:accMat[not(.//t:item)])))"/></xsl:call-template>

        <xsl:variable name="others">
          <xsl:for-each select="t:msIdentifier/t:altIdentifier[normalize-space(t:idno) != '' and not($bibl and @type = 'image-label')]">
            <xsl:if test="position() &gt; 1"><xsl:text>; </xsl:text></xsl:if>
            <xsl:value-of select="normalize-space(t:idno)"/>
            <xsl:if test="@type != ''"> (<xsl:value-of select="@type"/>)</xsl:if>
          </xsl:for-each>
        </xsl:variable>
        <xsl:call-template name="note"><xsl:with-param name="rule" select="'1.8B11'"/><xsl:with-param name="label" select="'Alpha-numeric designations'"/><xsl:with-param name="text" select="$others"/></xsl:call-template>
        <xsl:call-template name="note"><xsl:with-param name="rule" select="'1.8B12'"/><xsl:with-param name="label" select="'Immediate source of acquisition'"/>
          <xsl:with-param name="text" select="normalize-space(t:history/t:acquisition)"/></xsl:call-template>

        <xsl:variable name="language">
          <xsl:choose>
            <xsl:when test="normalize-space(t:msContents/t:textLang) != ''"><xsl:value-of select="normalize-space(t:msContents/t:textLang)"/></xsl:when>
            <xsl:otherwise><xsl:call-template name="language-name"><xsl:with-param name="code" select="t:msContents/t:textLang/@mainLang"/></xsl:call-template></xsl:otherwise>
          </xsl:choose>
        </xsl:variable>
        <xsl:call-template name="note"><xsl:with-param name="rule" select="'1.8B14'"/><xsl:with-param name="label" select="'Language'"/><xsl:with-param name="text" select="$language"/></xsl:call-template>
        <xsl:call-template name="note"><xsl:with-param name="rule" select="'1.8B15b'"/><xsl:with-param name="label" select="'Availability of other formats'"/>
          <xsl:with-param name="text" select="normalize-space(t:additional/t:surrogates)"/></xsl:call-template>

        <xsl:variable name="access">
          <xsl:for-each select="t:additional/t:adminInfo/t:availability[1]">
            <xsl:variable name="spoken"><xsl:for-each select="t:p"><xsl:value-of select="concat(' ', normalize-space(.))"/></xsl:for-each></xsl:variable>
            <xsl:choose>
              <xsl:when test="normalize-space($spoken) != ''"><xsl:value-of select="normalize-space($spoken)"/></xsl:when>
              <xsl:when test="@status = 'free'">No restrictions on access.</xsl:when>
              <xsl:when test="@status = 'restricted'">Access is restricted.</xsl:when>
            </xsl:choose>
          </xsl:for-each>
        </xsl:variable>
        <xsl:call-template name="note"><xsl:with-param name="rule" select="'1.8B16a'"/><xsl:with-param name="label" select="'Restrictions on access'"/><xsl:with-param name="text" select="$access"/></xsl:call-template>
        <xsl:call-template name="note"><xsl:with-param name="rule" select="'1.8B16c'"/><xsl:with-param name="label" select="'Terms governing use and reproduction'"/>
          <xsl:with-param name="text" select="normalize-space(t:additional/t:adminInfo/t:availability/t:licence)"/></xsl:call-template>
        <xsl:variable name="references">
          <xsl:for-each select="t:additional/t:listBibl/t:bibl[normalize-space(.) != '']">
            <xsl:if test="position() &gt; 1"><xsl:text>; </xsl:text></xsl:if><xsl:value-of select="normalize-space(.)"/>
          </xsl:for-each>
        </xsl:variable>
        <xsl:call-template name="note"><xsl:with-param name="rule" select="'1.8B17'"/><xsl:with-param name="label" select="'Finding aids'"/><xsl:with-param name="text" select="$references"/></xsl:call-template>

        <!-- general notes (1.8B21): the colophon, incipit, explicit and note of each item, quoted not interpreted -->
        <xsl:for-each select="$items[normalize-space(t:colophon) != '' or normalize-space(t:incipit) != '' or normalize-space(t:explicit) != '' or normalize-space(t:note) != '']">
          <xsl:variable name="general">
            <xsl:if test="normalize-space(t:locus) != ''"><xsl:value-of select="concat(normalize-space(t:locus), ': ')"/></xsl:if>
            <xsl:if test="normalize-space(t:colophon) != ''">Colophon: &#8220;<xsl:value-of select="normalize-space(t:colophon)"/>&#8221;. </xsl:if>
            <xsl:if test="normalize-space(t:incipit) != ''">Incipit: &#8220;<xsl:value-of select="normalize-space(t:incipit[1])"/>&#8221;. </xsl:if>
            <xsl:if test="normalize-space(t:explicit) != ''">Explicit: &#8220;<xsl:value-of select="normalize-space(t:explicit[1])"/>&#8221;. </xsl:if>
            <xsl:if test="normalize-space(t:note) != ''"><xsl:value-of select="normalize-space(t:note)"/></xsl:if>
          </xsl:variable>
          <xsl:call-template name="note"><xsl:with-param name="rule" select="'1.8B21'"/><xsl:with-param name="label" select="'General note'"/><xsl:with-param name="text" select="normalize-space($general)"/></xsl:call-template>
        </xsl:for-each>
      </div>

      <!-- access points: names, places, subjects -->
      <div class="rad-access">
        <xsl:variable name="names" select="($items/t:author | $header/t:fileDesc/t:titleStmt/t:author | $mono/t:author | $mono/t:editor | $items/t:respStmt[t:resp/@key = 'crp']/t:name | $items/t:respStmt/t:name[@key] | .//t:persName[@key])[normalize-space(.) != ''][generate-id() = generate-id(key('by-name', normalize-space(.))[1])]"/>
        <xsl:if test="$names">
          <p class="rad-access-names"><span class="rad-label">Name</span><xsl:text> </xsl:text>
            <xsl:for-each select="$names">
              <xsl:if test="position() &gt; 1"><xsl:text>; </xsl:text></xsl:if>
              <xsl:value-of select="normalize-space(.)"/>
              <xsl:call-template name="authority-key"/>
            </xsl:for-each>
          </p>
        </xsl:if>
        <xsl:variable name="places" select="(t:history/t:origin/t:origPlace | .//t:placeName[@key])[normalize-space(.) != ''][generate-id() = generate-id(key('by-place', normalize-space(.))[1])]"/>
        <xsl:if test="$places">
          <p class="rad-access-places"><span class="rad-label">Place</span><xsl:text> </xsl:text>
            <xsl:for-each select="$places">
              <xsl:if test="position() &gt; 1"><xsl:text>; </xsl:text></xsl:if>
              <xsl:value-of select="normalize-space(.)"/>
              <xsl:call-template name="authority-key"/>
            </xsl:for-each>
          </p>
        </xsl:if>
        <xsl:variable name="terms" select="$header/t:profileDesc/t:textClass/t:keywords/t:term[normalize-space(.) != '']"/>
        <xsl:if test="$terms">
          <p class="rad-access-subjects"><span class="rad-label">Subject</span><xsl:text> </xsl:text>
            <xsl:for-each select="$terms">
              <xsl:if test="position() &gt; 1"><xsl:text>; </xsl:text></xsl:if><xsl:value-of select="normalize-space(.)"/>
            </xsl:for-each>
          </p>
        </xsl:if>
      </div>
    </div>
  </xsl:template>

  <!-- " (AndDav001)" for a heading that points into an authority file; the key's leading # is dropped -->
  <xsl:template name="authority-key">
    <xsl:if test="@key != '' and not(contains(normalize-space(@key), ' '))">
      <xsl:text> (</xsl:text>
      <xsl:choose>
        <xsl:when test="starts-with(@key, '#')"><xsl:value-of select="substring(@key, 2)"/></xsl:when>
        <xsl:otherwise><xsl:value-of select="@key"/></xsl:otherwise>
      </xsl:choose>
      <xsl:text>)</xsl:text>
    </xsl:if>
  </xsl:template>

  <!-- The edition (1.2B1) and the publication area (1.4C, 1.4D, 1.4F) of a printed book: "Winnipeg : Ólafur S. Thorgeirsson, 1917". An unknown place is [S.l.] and an unknown publisher [s.n.]
       (RAD 1.4C6, 1.4D7: supplied information in square brackets). The date is the date of publication (1.4F), ISO 8601 like every date here (D13); an undated book prints none, because RAD
       says not to use "n.d." (1.4B, footnote 18). A bare edition number is the cataloguer's, so it is bracketed; a statement is printed as it stands. -->
  <xsl:template name="rad-publication">
    <xsl:param name="mono"/>
    <xsl:variable name="edition" select="normalize-space($mono/t:edition[1])"/>
    <xsl:if test="$edition != ''">
      <xsl:text> – </xsl:text><span class="rad-edition" data-rule="1.2B1">
        <xsl:choose>
          <xsl:when test="string(number($edition)) != 'NaN'">[Edition <xsl:value-of select="$edition"/>]</xsl:when>
          <xsl:otherwise><xsl:value-of select="$edition"/></xsl:otherwise>
        </xsl:choose>
      </span><xsl:text>.</xsl:text>
    </xsl:if>
    <xsl:variable name="place" select="normalize-space($mono/t:imprint/t:pubPlace[1])"/>
    <xsl:variable name="house" select="normalize-space($mono/t:imprint/t:publisher[1])"/>
    <xsl:variable name="date">
      <xsl:for-each select="$mono/t:imprint/t:date[1]">
        <xsl:variable name="iso"><xsl:call-template name="origdate-iso"/></xsl:variable>
        <xsl:value-of select="$iso"/><xsl:value-of select="substring('?', 1, number($iso != '' and (@cert = 'low' or @cert = 'medium')))"/>
      </xsl:for-each>
    </xsl:variable>
    <xsl:text> – </xsl:text>
    <span class="rad-publication">
      <span class="rad-place" data-rule="1.4C"><xsl:choose><xsl:when test="$place != ''"><xsl:value-of select="$place"/></xsl:when><xsl:otherwise>[S.l.]</xsl:otherwise></xsl:choose></span>
      <xsl:text> : </xsl:text>
      <span class="rad-publisher" data-rule="1.4D"><xsl:choose><xsl:when test="$house != ''"><xsl:value-of select="$house"/></xsl:when><xsl:otherwise>[s.n.]</xsl:otherwise></xsl:choose></span>
      <xsl:if test="$date != ''"><xsl:text>, </xsl:text><span class="rad-date" data-rule="1.4F"><xsl:value-of select="$date"/></span></xsl:if>
    </span><xsl:text>.</xsl:text>
  </xsl:template>

  <!-- 1.4B2: the date in ISO 8601 (a date, or "start/end"); RAD leaves the format to the institution (1.4B2, footnote 15), and Ryan chose ISO 8601 (2026-10-29), so RAD's bracket
       forms of 1.4B5 are not used. An uncertain date carries the ISO 8601-2 (EDTF) mark ? (evidence a conjecture, or cert low or medium); a range open at one end is "1850/.." or "../1850"
       (EDTF). How the date is known is said in the 1.8B8 note. A value that is not an ISO 8601 date is not printed. -->
  <xsl:template name="rad-date">
    <xsl:for-each select="t:history/t:origin/t:origDate[1]">
      <xsl:variable name="iso"><xsl:call-template name="origdate-iso"/></xsl:variable>
      <xsl:variable name="doubtful" select="@evidence = 'conjecture' or @cert = 'low' or @cert = 'medium'"/>
      <xsl:variable name="before-ok"><xsl:call-template name="iso-valid"><xsl:with-param name="d" select="@notBefore"/></xsl:call-template></xsl:variable>
      <xsl:variable name="after-ok"><xsl:call-template name="iso-valid"><xsl:with-param name="d" select="@notAfter"/></xsl:call-template></xsl:variable>
      <xsl:choose>
        <xsl:when test="$iso != ''"><xsl:value-of select="$iso"/><xsl:value-of select="substring('?', 1, number($doubtful))"/></xsl:when>
        <xsl:when test="not(@when) and not(@from) and @notBefore and not(@notAfter) and $before-ok = 'y'"><xsl:value-of select="@notBefore"/>/..<xsl:value-of select="substring('?', 1, number($doubtful))"/></xsl:when>
        <xsl:when test="not(@when) and not(@to) and @notAfter and not(@notBefore) and $after-ok = 'y'">../<xsl:value-of select="@notAfter"/><xsl:value-of select="substring('?', 1, number($doubtful))"/></xsl:when>
      </xsl:choose>
    </xsl:for-each>
  </xsl:template>

  <!-- 1.5: extent : other physical details ; dimensions + accompanying material (each part only when the record holds it) -->
  <xsl:template name="rad-physical">
    <xsl:variable name="extent"><xsl:call-template name="leaves"/></xsl:variable>
    <xsl:variable name="material">
      <xsl:variable name="code" select="t:physDesc/t:objectDesc/t:supportDesc/@material"/>
      <xsl:choose>
        <xsl:when test="$code = 'perg'">parchment</xsl:when>
        <xsl:when test="$code = 'chart'">paper</xsl:when>
        <xsl:when test="$code = 'mixed'">mixed materials</xsl:when>
      </xsl:choose>
    </xsl:variable>
    <xsl:variable name="illustrated" select="t:physDesc/t:decoDesc/t:decoNote[@type = 'illustration' and normalize-space(.) != '']"/>
    <xsl:variable name="other" select="concat($material, substring(', ', 1, 2 * number($material != '' and boolean($illustrated))), substring('ill.', 1, 4 * number(boolean($illustrated))))"/>
    <xsl:variable name="size"><xsl:call-template name="size"/></xsl:variable>
    <xsl:variable name="pieces" select="count(t:physDesc/t:accMat//t:item[normalize-space(.) != ''])"/>
    <xsl:variable name="accompanying">
      <xsl:choose>
        <xsl:when test="$pieces = 1">1 accompanying item</xsl:when>
        <xsl:when test="$pieces &gt; 1"><xsl:value-of select="$pieces"/> accompanying items</xsl:when>
      </xsl:choose>
    </xsl:variable>
    <xsl:value-of select="$extent"/>
    <xsl:if test="$other != ''"><xsl:if test="$extent != ''"> : </xsl:if><xsl:value-of select="$other"/></xsl:if>
    <xsl:if test="$size != ''"><xsl:if test="$extent != '' or $other != ''"> ; </xsl:if><xsl:value-of select="$size"/></xsl:if>
    <xsl:if test="$accompanying != ''"><xsl:if test="$extent != '' or $other != '' or $size != ''"> + </xsl:if><xsl:value-of select="$accompanying"/></xsl:if>
  </xsl:template>

  <xsl:template name="language-name">
    <xsl:param name="code"/>
    <xsl:choose>
      <xsl:when test="$code = 'is'">Icelandic</xsl:when>
      <xsl:when test="$code = 'en'">English</xsl:when>
      <xsl:when test="$code = 'la'">Latin</xsl:when>
      <xsl:when test="$code = 'da'">Danish</xsl:when>
      <xsl:when test="$code = 'no' or $code = 'nb'">Norwegian</xsl:when>
      <xsl:when test="$code = 'sv'">Swedish</xsl:when>
      <xsl:when test="$code = 'de'">German</xsl:when>
      <xsl:when test="$code = 'fr'">French</xsl:when>
      <xsl:otherwise><xsl:value-of select="$code"/></xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <!-- a full stop after text that does not already end a sentence -->
  <xsl:template name="full-stop">
    <xsl:param name="text"/>
    <xsl:if test="not(contains('.!?&#8221;', substring($text, string-length($text))))">.</xsl:if>
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
