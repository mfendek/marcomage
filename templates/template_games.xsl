<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
                xmlns="http://www.w3.org/1999/xhtml"
                xmlns:am="http://arcomage.netvor.sk"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes" doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" />


<xsl:template match="section[. = 'Games']">
	<xsl:variable name="param" select="$params/games" />
	
	<xsl:variable name="list" select="$param/list" />
	
	<xsl:choose>
	<xsl:when test="count($list) &gt; 0">
		<div id="games">
			<xsl:for-each select="$list/*">
				<div>
					<input type = "submit" name="view_game[{gameid}]" value="vs. {opponent}">
						<xsl:if test="active = 'yes'">
							<xsl:attribute name="style">font-style: italic</xsl:attribute>
						</xsl:if>
						<xsl:if test="ready = 'yes'">
							<xsl:attribute name="class">marked_button</xsl:attribute>
						</xsl:if>
					</input>
				</div>

				<xsl:choose>
				<xsl:when test="gamestate = 'in progress'">
					<xsl:if test="isdead = 'yes'">
						<p class="ended_game" >Game can be aborted</p>
					</xsl:if>
				</xsl:when>
				<xsl:otherwise>
					<p class="ended_game">Game has ended</p>
				</xsl:otherwise>
				</xsl:choose>
			</xsl:for-each>
		</div>
	</xsl:when>
	<xsl:otherwise>
			<p class="information_line warning">You have no active games.</p>
	</xsl:otherwise>
	</xsl:choose>
	
</xsl:template>


</xsl:stylesheet>
