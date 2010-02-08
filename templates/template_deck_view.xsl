<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
                xmlns="http://www.w3.org/1999/xhtml"
                xmlns:am="http://arcomage.netvor.sk"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes" doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" />


<xsl:template match="section[. = 'Deck_view']">
	<xsl:variable name="param" select="$params/deck_view" />

	<div style="text-align: center">
		<input type="submit" name="view_game[{$param/CurrentGame}]" value="Back to game"/>
	</div>

	<table class="deck skin_label" cellpadding="0" cellspacing="0" >

		<tr>
			<th><p>Common</p></th>
			<th><p>Uncommon</p></th>
			<th><p>Rare</p></th>
		</tr>

		<tr valign="top">
		<xsl:for-each select="$param/DeckCards/*"> <!-- Common, Uncommon, Rare sections -->
			<td>
				<table class="centered" cellpadding="0" cellspacing="0">
				<xsl:variable name="cards" select="."/>
				<xsl:for-each select="$cards/*[position() &lt;= 5]"> <!-- row counting hack -->
				<tr>
					<xsl:variable name="i" select="position()"/>
					<xsl:for-each select="$cards/*[position() &gt;= $i*3-2 and position() &lt;= $i*3]">
						<td>
							<xsl:copy-of select="am:cardstring(current(), $param/c_img, $param/c_keywords, $param/c_text, $param/c_oldlook)" />
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
