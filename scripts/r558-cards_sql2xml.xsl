<?xml version="1.0" encoding="utf-8" ?>
<!--
	adjusts a PhpMyAdmin cards table xml dump's format a bit:
	* changes capitalization
	* puts bricks, gems and recruits under a common 'cost' element
	* splits the keyword string into individual elements [not used]
	* wraps 'effect' into an unescaped CDATA string
 -->
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:am="http://arcomage.netvor.sk"
                xmlns="http://arcomage.netvor.sk"
                exclude-result-prefixes="am">
<xsl:output method="xml"
            version="1.0"
            encoding="utf-8"
            indent="yes"
            media-type="text/xml"
            cdata-section-elements="am:effect"
/>

<xsl:template match="/">
  <xsl:comment>MArcomage cards database (XML)</xsl:comment>
  <xsl:apply-templates />
</xsl:template>

<xsl:template match="arcomage">
  <cards xmlns="http://arcomage.netvor.sk">
    <xsl:apply-templates select="cards" />
  </cards>
</xsl:template>

<!-- transforms a card from sql-like schema to target xml schema -->
<xsl:template match="cards">
  <card id="{ CardID }">
    <name><xsl:value-of select="Name" /></name>
    <class><xsl:value-of select="Class" /></class>
    <cost>
      <bricks><xsl:value-of select="Bricks" /></bricks>
      <gems><xsl:value-of select="Gems" /></gems>
      <recruits><xsl:value-of select="Recruits" /></recruits>
    </cost>
    <modes><xsl:value-of select="Modes" /></modes>
<!--<keywords><xsl:call-template name="keyword-split"><xsl:with-param name="text" select="Keywords/text()" /></xsl:call-template></keywords>-->
    <keywords><xsl:value-of select="Keywords" /></keywords>
    <effect><xsl:apply-templates mode="copy" select="Effect/text()" /></effect>
  </card>
</xsl:template>

<!-- transforms a dot-delimited keywords string into a keyword node-set -->
<xsl:template name="keyword-split">
  <xsl:param name="text" />
  <xsl:if test="contains($text,'.')">
    <keyword><xsl:value-of select="substring-before($text,'.')" /></keyword>
    <xsl:call-template name="keyword-split">
      <xsl:with-param name="text" select="substring-after($text,'. ')" />
    </xsl:call-template>
  </xsl:if>
</xsl:template>

<!-- recursively copies all elements and their attributes -->
<xsl:template match="*" mode="copy">
  <xsl:element name="{local-name()}">
    <xsl:for-each select="@*"><xsl:copy select="." /></xsl:for-each>
    <xsl:apply-templates mode="copy" />
  </xsl:element>
</xsl:template>

</xsl:stylesheet>
