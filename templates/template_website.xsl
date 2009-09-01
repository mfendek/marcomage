<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
                xmlns="http://www.w3.org/1999/xhtml"
                xmlns:am="http://arcomage.netvor.sk"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes" doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" />
<xsl:strip-space elements="*" />


<xsl:template match="section[. = 'Page']">
	<xsl:variable name="param" select="$params/website" />

	<div id="webpage">

	<!-- remember the current location across pages -->
	<input type="hidden" name="CurrentPage" value="{$param/selected}" />

	<div id="webpg_float_left">
	<div class="skin_label">
	<p><input type="submit" name="WebPage[Main]" value="Main page" /></p>
	<p><input type="submit" name="WebPage[News]" value="Latest news" /></p>
	<p><input type="submit" name="WebPage[Modified]" value="Modified cards" /></p>
	<p><input type="submit" name="WebPage[Help]" value="Game manual" /></p>
	<p><input type="submit" name="WebPage[Faq]" value="F .   A .   Q . " /></p>
	<p><input type="submit" name="WebPage[Credits]" value="Hall of fame" /></p>
	<p><input type="submit" name="WebPage[History]" value="Project history" /></p>
	</div>
	</div>

	<div id="webpg_float_right" class="skin_text">
		<xsl:for-each select="$param/files/*">

			<!-- TODO: check if file exists and print error if not -->
			<xsl:variable name="entry" select="document(am:urlencode(concat('../pages/', .)))/am:entry" />

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

		</xsl:for-each>
	</div>
	<div class="clear_floats"></div>

	</div>
</xsl:template>


</xsl:stylesheet>
