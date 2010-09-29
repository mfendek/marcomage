<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
                xmlns="http://www.w3.org/1999/xhtml"
                xmlns:am="http://arcomage.netvor.sk"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:exsl="http://exslt.org/common"
                xmlns:php="http://php.net/xsl"
                extension-element-prefixes="exsl php">
<xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes" doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" />
<xsl:strip-space elements="*" />

<!-- includes -->
<xsl:include href="template_main.xsl" />


<xsl:template match="section[. = 'Webpage']">
	<xsl:variable name="param" select="$params/webpage" />

	<div id="webpage">

	<div id="webpg_float_left">
	<div class="skin_label">
	<xsl:variable name="websections">
		<value name="Main"     value="Main page"       />
		<value name="News"     value="Latest news"     />
		<value name="Archive"  value="News archive"    />
		<value name="Modified" value="Modified cards"  />
		<value name="Faq"      value="F .   A .   Q ." />
		<value name="Credits"  value="Hall of fame"    />
		<value name="History"  value="Project history" />
	</xsl:variable>

	<xsl:for-each select="exsl:node-set($websections)/*">
		<p>
			<a class="button" href="{php:functionString('makeurl', 'Webpage', 'WebSection', @name)}">
				<xsl:if test="$param/selected = @name">
					<xsl:attribute name="class">button pushed</xsl:attribute>
				</xsl:if>
				<xsl:value-of select="@value" />
			</a>
		</p>
	</xsl:for-each>
	</div>
	</div>

	<div id="webpg_float_right" class="skin_text">
		<xsl:for-each select="$param/files/*">
		<xsl:if test="($param/recent_news_only = 'no') or (position() &lt;= 4)">

			<!-- TODO: check if file exists and print error if not -->
			<xsl:variable name="entry" select="document(am:urlencode(concat('pages/', text())))/am:entry" />

			<xsl:if test="string($entry/am:date/text())">
				<div class="date_time">
					<xsl:value-of select="am:datetime($entry/am:date/text(), $param/timezone)" />
				</div>
			</xsl:if>

			<xsl:if test="string($entry/am:title/text())">
				<h3>
					<xsl:value-of select="$entry/am:title/text()" />
				</h3>
			</xsl:if>

			<div>
				<xsl:copy-of select="$entry/am:content/node()" />
				<hr/>
			</div>
		</xsl:if>

		</xsl:for-each>
	</div>
	<div class="clear_floats"></div>

	</div>
</xsl:template>


</xsl:stylesheet>
