<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
                xmlns="http://www.w3.org/1999/xhtml"
                xmlns:am="http://arcomage.netvor.sk"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:exsl="http://exslt.org/common"
                extension-element-prefixes="exsl">
<xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes" doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" />

<!-- includes -->
<xsl:include href="template_main.xsl" />


<xsl:template match="section[. = 'Help']">
	<xsl:variable name="param" select="$params/help" />

	<xsl:variable name="game_manual" select="document('help.xml')/am:help" />
	<xsl:variable name="content" select="$game_manual/am:part[@name=$param/part]" />

	<div id="help">

		<!-- game manual menu -->
		<div id="help_menu" class="skin_label">

		<h3>Game manual</h3>

		<ul>
		<xsl:for-each select="exsl:node-set($game_manual)/*">
			<li><a href="{am:makeurl('Help', 'help_part', @name)}"><xsl:value-of select="@name"/></a></li>
		</xsl:for-each>
		</ul>

		</div>

		<!-- game manual content -->
		<div id="content" class="skin_text">
			<div>
				<xsl:choose>
					<xsl:when test="$content">
						<h3><xsl:value-of select="$param/part"/></h3>
						<xsl:value-of select="$content" disable-output-escaping="yes" />
					</xsl:when>
					<xsl:otherwise>
						<h3>Selected page not found</h3>
					</xsl:otherwise>
				</xsl:choose>
			</div>
		</div>
		<div class="clear_floats"></div>
	</div>

</xsl:template>


</xsl:stylesheet>
