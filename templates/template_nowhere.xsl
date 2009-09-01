<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
                xmlns="http://www.w3.org/1999/xhtml"
                xmlns:am="http://arcomage.netvor.sk"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes" doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" />


<xsl:template match="*">
	<xsl:variable name="param" select="$params/content" />
	<p class="information_line warning" >Welcome to no-man's land...</p>
	<p class="information_line warning" >You are seeing this page because the template for section '<xsl:value-of select="."/>' is missing.</p>
	<p class="information_line warning" >Please notify us about this issue.</p>
</xsl:template>


</xsl:stylesheet>
