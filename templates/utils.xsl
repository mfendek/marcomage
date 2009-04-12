<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
                xmlns="http://www.w3.org/1999/xhtml"
                xmlns:am="http://arcomage.netvor.sk"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:date="http://exslt.org/dates-and-times"
                xmlns:func="http://exslt.org/functions"
                xmlns:php="http://php.net/xsl"
                xmlns:str="http://exslt.org/strings"
                extension-element-prefixes="date func php str">
<xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes" doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" />


<xsl:include href="date.format-date.function.xsl" /> <!-- date:format-date(string, string) -->
<xsl:include href="date.difference.function.xsl" /> <!-- date:difference(string, string) -->


<func:function name="am:urlencode">
	<xsl:param name="string" as="xs:string" />
	<func:result select="str:encode-uri($string, true())" />
</func:function>


<func:function name="am:textencode">
	<xsl:param name="text" as="xs:string" />
	<func:result select="php:functionString('textencode', $text)" />
</func:function>


<func:function name="am:min">
	<xsl:param name="num1" as="xs:integer" />
	<xsl:param name="num2" as="xs:integer" />
	<func:result>
		<xsl:choose>
			<xsl:when test="$num1 &lt; $num2"><xsl:value-of select="$num1"/></xsl:when>
			<xsl:otherwise><xsl:value-of select="$num2"/></xsl:otherwise>
		</xsl:choose>
	</func:result>
</func:function>


<func:function name="am:max">
	<xsl:param name="num1" as="xs:integer" />
	<xsl:param name="num2" as="xs:integer" />
	<func:result>
		<xsl:choose>
			<xsl:when test="$num1 &gt; $num2"><xsl:value-of select="$num1"/></xsl:when>
			<xsl:otherwise><xsl:value-of select="$num2"/></xsl:otherwise>
		</xsl:choose>
	</func:result>
</func:function>


<func:function name="am:datetime">
	<xsl:param name="datetime" as="xs:string" />
	<xsl:param name="timezone" as="xs:string" select="'+0'" />
	<xsl:variable name="date" select="str:replace($datetime, ' ', 'T')" />
	<xsl:variable name="zone" select="concat('Etc/GMT', str:replace(str:replace(str:replace($timezone, '+', '*'), '-', '+'), '*', '-'))" />
	<func:result select="php:functionString('ZoneTime', $date, $zone, 'H:i | j. F, Y')" />
</func:function>


<func:function name="am:datediff">
	<xsl:param name="datetime1" as="xs:string" />
	<xsl:param name="datetime2" as="xs:string" />
	<func:result select="date:seconds(date:difference(str:replace($datetime1, ' ', 'T'), str:replace($datetime2, ' ', 'T')))" />
</func:function>


</xsl:stylesheet>
