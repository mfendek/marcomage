<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
                xmlns="http://www.w3.org/1999/xhtml"
                xmlns:am="http://arcomage.netvor.sk"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:exsl="http://exslt.org/common"
                extension-element-prefixes="exsl">
<xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes" doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" />


<xsl:template match="section[. = 'Players']">
	<xsl:variable name="param" select="$params/players" />
	
	<xsl:variable name="list" select="$param/list" />
	
	<xsl:if test="$param/active_decks = 0">
		<p class="information_line" style="color: yellow;">You need at least one ready deck to challenge other players.</p>
	</xsl:if>
	
	<xsl:if test="$param/free_slots = 0">
		<p class="information_trans" style = "color: yellow;">You cannot initiate any more games.</p>
	</xsl:if> 

	<div id="players">
		<div class="filters_trans" style="text-align: center;">
			<!-- begin player filter -->
			<select name="player_filter">
				<xsl:if test="$param/CurrentFilter != 'none'"><xsl:attribute name="style">border-color: lime</xsl:attribute></xsl:if>
				<option value="none" >No players filters</option>
				<option value="active"><xsl:if test="$param/CurrentFilter = 'active'"><xsl:attribute name="selected">selected</xsl:attribute></xsl:if>Active players</option>
				<option value="offline"><xsl:if test="$param/CurrentFilter = 'offline'"><xsl:attribute name="selected">selected</xsl:attribute></xsl:if>Active and offline players</option>
				<option value="all"><xsl:if test="$param/CurrentFilter = 'all'"><xsl:attribute name="selected">selected</xsl:attribute></xsl:if>Show all players</option>
			</select>
			<input type="submit" name="filter_players" value="Apply filter" />

			<!-- upper navigation -->
			<xsl:if test="$param/page_count &gt; 0">
				<!-- previous button -->
				<input type="submit" name="select_page_players[{$param/current_page - 1}]" value="&lt;">
					<xsl:if test="$param/current_page &lt;= 0"><xsl:attribute name="disabled">disabled</xsl:attribute></xsl:if>
				</input>

				<!-- next button -->
				<input type="submit" name="select_page_players[{$param/current_page + 1}]" value="&gt;">
					<xsl:if test="$param/current_page &gt;= $param/page_count - 1"><xsl:attribute name="disabled">disabled</xsl:attribute></xsl:if>
				</input>

				<!-- page selector -->
				<select name="jump_to_page">
					<xsl:for-each select="$param/pages/*">
						<option value="{.}">
							<xsl:if test="$param/current_page = ."><xsl:attribute name="selected">selected</xsl:attribute></xsl:if>
							<xsl:value-of select="."/>
						</option>
					</xsl:for-each>
				</select>
				<input type="submit" name="Jump_players" value="Select page" />
			</xsl:if>
		</div>

		<!-- begin players list -->
		<table class="centered" cellspacing="0">
			<tr>
				<xsl:if test="$param/show_avatars = 'yes'"><th></th></xsl:if>
				<xsl:if test="$param/show_nationality = 'yes'"><th></th></xsl:if>			
				
				<xsl:variable name="columns">
					<column name="Country"    text="Flag"       sortable="yes" />
					<column name="Username"   text="Username"   sortable="yes" />
					<column name="Rank"       text="Wins"       sortable="yes" />
					<column name="Losses"     text="Losses"     sortable="no"  />
					<column name="Draws"      text="Draws"      sortable="no"  />
					<column name="Free Slots" text="Free Slots" sortable="yes" />
					<column name="other"      text=""           sortable="no"  />
				</xsl:variable>
				
				<xsl:for-each select="exsl:node-set($columns)/*">
					<th>
						<p>
							<xsl:value-of select="@text"/>
							<xsl:if test="@sortable = 'yes'">
								<input type="submit" class="details">
									<xsl:if test="$param/condition = @name">
										<xsl:attribute name="style">border-color: lime</xsl:attribute>
									</xsl:if>
									<xsl:choose>
										<xsl:when test="$param/condition = @name and $param/order = 'DESC'">
											<xsl:attribute name="name">players_ord_asc[<xsl:value-of select="@name"/>]</xsl:attribute>
											<xsl:attribute name="value">/\</xsl:attribute>
										</xsl:when>
										<xsl:otherwise>
											<xsl:attribute name="name">players_ord_desc[<xsl:value-of select="@name"/>]</xsl:attribute>
											<xsl:attribute name="value">\/</xsl:attribute>
										</xsl:otherwise>
									</xsl:choose>
								</input>
							</xsl:if>
						</p>
					</th>
				</xsl:for-each>
			</tr>
			
			<xsl:for-each select="$list/*">
				<tr class="table_row" align="center">
				
					<xsl:if test="$param/show_avatars = 'yes'">
						<td>
							<xsl:if test="avatar != 'noavatar.jpg'">
								<img class="avatar" height="60px" width="60px" src="img/avatars/{avatar}" alt="avatar" />
							</xsl:if>
						</td>
					</xsl:if>
					
					<xsl:if test="$param/show_nationality = 'yes'">
						<td>
							<p style="color: white"><xsl:value-of select="country"/></p>
						</td>
					</xsl:if>
					
					<td><img width="18px" height="12px" src="img/flags/{country}.gif" alt="country flag" /></td>
					<td><p style="color: {namecolor}"><xsl:value-of select="name"/></p></td>
					<td><p><xsl:value-of select="wins"/></p></td>
					<td><p><xsl:value-of select="losses"/></p></td>
					<td><p><xsl:value-of select="draws"/></p></td>
					<td><p><xsl:value-of select="free_slots"/></p></td>
					
					<td style="text-align: left;">
						<input class="details" type="submit" name="user_details[{name}]" value="i" />
						<xsl:if test="$param/messages = 'yes'">
							<input class="details" type="submit" name="message_create[{name}]" value="m" />
						</xsl:if>
						<xsl:if test="$param/send_challenges = 'yes' and $param/free_slots &gt; 0 and $param/active_decks &gt; 0 and name != $param/PlayerName and challenged = 'no' and playingagainst = 'no' and waitingforack = 'no'">
							<input class="details" type="submit" name="prepare_challenge[{name}]" value="Challenge" />
						</xsl:if>
					</td>
					<td>
						<xsl:choose>
							<xsl:when test="challenged     = 'yes'"><p style="color: red;">waiting for answer</p></xsl:when>
							<xsl:when test="playingagainst = 'yes'"><p>game already in progress</p></xsl:when>
							<xsl:when test="waitingforack  = 'yes'"><p style="color: blue;">game over, waiting for opponent</p></xsl:when>
						</xsl:choose>
					</td>
					
				</tr>
			</xsl:for-each>
		</table>

		<!-- lower navigation -->
		<div class="filters_trans" style="text-align: center;">
			<xsl:if test="$param/page_count &gt; 0">
				<!-- previous button -->
				<input type="submit" name="select_page_players[{$param/current_page - 1}]" value="&lt;">
					<xsl:if test="$param/current_page &lt;= 0"><xsl:attribute name="disabled">disabled</xsl:attribute></xsl:if>
				</input>

				<!-- back to top button -->
				<input type = "submit" name="Players" value="Back to top" />

				<!-- next button -->
				<input type="submit" name="select_page_players[{$param/current_page + 1}]" value="&gt;">
					<xsl:if test="$param/current_page &gt;= $param/page_count - 1"><xsl:attribute name="disabled">disabled</xsl:attribute></xsl:if>
				</input>
			</xsl:if>
		</div>

		<xsl:if test="$param/CurrentFilter">
			<input type ="hidden" name="CurrentFilter" value="{$param/CurrentFilter}" />
		</xsl:if>
		<input type ="hidden" name="CurrentPlayersPage" value="{$param/current_page}" />

	</div>
</xsl:template>


</xsl:stylesheet>