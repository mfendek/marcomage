<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
                xmlns="http://www.w3.org/1999/xhtml"
                xmlns:am="http://arcomage.netvor.sk"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:exsl="http://exslt.org/common"
                extension-element-prefixes="exsl">
<xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes" doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" />


<xsl:template match="section[. = 'Deck_view']">
	<xsl:variable name="param" select="$params/deck_view" />
	<xsl:variable name="colors" select="document('colors.xml')/am:colors"/>		

	<!-- remember the current location across pages -->
	<div>
		<input type="hidden" name="CurrentGame" value="{$param/CurrentGame}"/>
	</div>
	
	<div style="text-align: center">
		<input type="submit" name="view_game[{$param/CurrentGame}]" value="Back to game"/>
	</div>

	<table class="deck" cellpadding="0" cellspacing="0" >

		<xsl:variable name="rows">
			<adv name="Common"   text="Lime"    />
			<adv name="Uncommon" text="DarkRed" />
			<adv name="Rare"     text="Yellow"  />
		</xsl:variable>

		<tr>
		<xsl:for-each select="exsl:node-set($rows)/*">
			<th>
				<p>
				<xsl:variable name="color" select="@text" />
				<xsl:attribute name="style">color: <xsl:value-of select="$colors/am:color[am:name = $color]/am:code"/></xsl:attribute>
				<xsl:value-of select="@name"/>
				</p>
			</th>
		</xsl:for-each>
		</tr>

		<tr valign="top">
		<xsl:for-each select="$param/DeckCards/*">
			<td>
				<table class="centered" cellpadding="0" cellspacing="0">
				<xsl:for-each select="./*">
				<tr>
					<xsl:for-each select="./*">
						<td>
							<xsl:value-of select="CardString" disable-output-escaping="yes" />
						</td>
					</xsl:for-each>
				</tr>
				</xsl:for-each>
				</table>
			</td>
		</xsl:for-each>
		</tr>

	</table>
</xsl:template>


</xsl:stylesheet>
