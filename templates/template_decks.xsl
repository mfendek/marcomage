<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet [ <!ENTITY rarr "&#8594;"> ]>
<xsl:stylesheet version="1.0"
                xmlns="http://www.w3.org/1999/xhtml"
                xmlns:am="http://arcomage.netvor.sk"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes" doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" />


<xsl:template match="section[. = 'Decks']">
	<xsl:variable name="param" select="$params/decks" />

	<div id="decks">
	<table cellspacing="0" class="skin_text">
		<tr>
			<th><p>Deck name</p></th>
			<th><p>Last change</p></th>
			<th></th>
		</tr>
		<xsl:for-each select="$param/list/*">
			<tr class="table_row">
				<td>
					<p>
						<xsl:if test="Ready = 'yes'">
							<xsl:attribute name="class">p_online</xsl:attribute>
						</xsl:if>
						<xsl:value-of select="Deckname"/>
					</p>
				</td>
				<td>
					<p>
						<xsl:choose>
							<xsl:when test="Modified != '0000-00-00 00:00:00'"><xsl:value-of select="am:datetime(Modified, $param/timezone)"/></xsl:when>
							<xsl:otherwise>n/a</xsl:otherwise>
						</xsl:choose>
					</p>
				</td>
				<td><p><input type="submit" name="modify_deck[{am:urlencode(Deckname)}]" value="&rarr;" /></p></td>
			</tr>
		</xsl:for-each>
	</table>
	</div>
</xsl:template>


</xsl:stylesheet>
