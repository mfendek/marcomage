<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet [ <!ENTITY rarr "&#8594;"> ]>
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
			<table cellspacing="0" class="skin_text">
				<tr>
					<th><p>Opponent</p></th>
					<xsl:if test="$param/games_details = 'yes'">
						<th><p>Last seen</p></th>
					</xsl:if>
					<th><p>Round</p></th>
					<xsl:if test="$param/games_details = 'yes'">
						<th><p>Last game action</p></th>
					</xsl:if>
					<th><p>Info</p></th>
					<th></th>
				</tr>
				<xsl:for-each select="$list/*">
					<tr class="table_row">
						<td>
							<p>
								<xsl:if test="active = 'yes'">
									<xsl:attribute name="class">p_online</xsl:attribute>
								</xsl:if>
								<xsl:value-of select="opponent"/>
							</p>
						</td>
						<xsl:if test="$param/games_details = 'yes'">
							<td><p><xsl:value-of select="am:datetime(lastseen, $param/timezone)"/></p></td>
						</xsl:if>
						<td><p><xsl:value-of select="round"/></p></td>
						<xsl:if test="$param/games_details = 'yes'">
							<td><p><xsl:value-of select="am:datetime(gameaction, $param/timezone)"/></p></td>
						</xsl:if>
						<td>
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
						</td>
						<td>
							<p>
								<input type="submit" name="view_game[{gameid}]" value="&rarr;">
									<xsl:if test="ready = 'yes'">
										<xsl:attribute name="class">marked_button</xsl:attribute>
									</xsl:if>
								</input>
							</p>
						</td>
					</tr>
				</xsl:for-each>
			</table>
		</div>
	</xsl:when>
	<xsl:otherwise>
			<p class="information_line warning">You have no active games.</p>
	</xsl:otherwise>
	</xsl:choose>
	
</xsl:template>


</xsl:stylesheet>
