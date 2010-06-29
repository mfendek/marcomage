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
		<p class="information_line warning">You need at least one ready deck to challenge other players.</p>
	</xsl:if>
	
	<xsl:if test="$param/free_slots = 0">
		<p class="information_line warning" >You cannot initiate any more games.</p>
	</xsl:if> 

	<div id="players">
		<div class="filters">
			<!-- begin name filter -->
			<input type="text" name="pname_filter" maxlength="20" size="20" value="{$param/pname_filter}" />

			<!-- activity filter -->
			<xsl:variable name="activity_types">
				<class name="No activity filter"         value="none"    />
				<class name="Active players"             value="active"  />
				<class name="Active and offline players" value="offline" />
				<class name="Show all players"           value="all"     />
			</xsl:variable>

			<select name="activity_filter">
				<xsl:if test="$param/activity_filter != 'none'">
					<xsl:attribute name="class">filter_active</xsl:attribute>
				</xsl:if>
				<xsl:for-each select="exsl:node-set($activity_types)/*">
				<option value="{@value}">
					<xsl:if test="$param/activity_filter = @value">
						<xsl:attribute name="selected">selected</xsl:attribute>
					</xsl:if>
					<xsl:value-of select="@name"/>
				</option>
				</xsl:for-each>
			</select>

			<!-- status filter -->
			<xsl:variable name="status_types">
				<class name="No status filter"       value="none"   />
				<class name="Looking for game"       value="ready"  />
				<class name="Looking for quick game" value="quick"  />
				<class name="Do not disturb"         value="dnd"    />
				<class name="Newbie"                 value="newbie" />
			</xsl:variable>

			<select name="status_filter">
				<xsl:if test="$param/status_filter != 'none'">
					<xsl:attribute name="class">filter_active</xsl:attribute>
				</xsl:if>
				<xsl:for-each select="exsl:node-set($status_types)/*">
				<option value="{@value}">
					<xsl:if test="$param/status_filter = @value">
						<xsl:attribute name="selected">selected</xsl:attribute>
					</xsl:if>
					<xsl:value-of select="@name"/>
				</option>
				</xsl:for-each>
			</select>
			<input type="submit" name="filter_players" value="Apply filters" />

			<!-- upper navigation -->
			<xsl:copy-of select="am:upper_navigation($param/page_count, $param/current_page, 'players')"/>
		</div>

		<!-- begin players list -->
		<table class="centered skin_text" cellspacing="0">
			<tr>
				<xsl:if test="$param/show_avatars = 'yes'"><th></th></xsl:if>
				<xsl:if test="$param/show_nationality = 'yes'"><th></th></xsl:if>			
				
				<xsl:variable name="columns">
					<column name="Country"    text="Flag"       sortable="yes" />
					<column name="Username"   text="Username"   sortable="yes" />
					<column name="Level"      text="Level"      sortable="yes" />
					<column name="Exp"        text="Exp"        sortable="no"  />
					<column name="Wins"       text="Wins"       sortable="no"  />
					<column name="Losses"     text="Losses"     sortable="no"  />
					<column name="Draws"      text="Draws"      sortable="no"  />
					<column name="Status"     text="Status"     sortable="no"  />
					<column name="other"      text=""           sortable="no"  />
				</xsl:variable>
				
				<xsl:for-each select="exsl:node-set($columns)/*">
					<th>
						<p>
							<xsl:value-of select="@text"/>
							<xsl:if test="@sortable = 'yes'">
								<input type="submit" class="small_button">
									<xsl:if test="$param/condition = @name">
										<xsl:attribute name="class">small_button pushed</xsl:attribute>
									</xsl:if>
									<xsl:choose>
										<xsl:when test="$param/condition = @name and $param/order = 'DESC'">
											<xsl:attribute name="name">players_ord_asc[<xsl:value-of select="@name"/>]</xsl:attribute>
											<xsl:attribute name="value">\/</xsl:attribute>
										</xsl:when>
										<xsl:otherwise>
											<xsl:attribute name="name">players_ord_desc[<xsl:value-of select="@name"/>]</xsl:attribute>
											<xsl:attribute name="value">/\</xsl:attribute>
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
						<td><p><xsl:value-of select="country"/></p></td>
					</xsl:if>
					
					<td><img width="18px" height="12px" src="img/flags/{country}.gif" alt="country flag" class="icon" title="{country}" /></td>

					<xsl:variable name="player_class">
						<xsl:choose> <!-- choose name color according to inactivity time -->
							<xsl:when test="inactivity &gt; 60*60*24*7*3">p_dead</xsl:when> <!-- 3 weeks = dead -->
							<xsl:when test="inactivity &gt; 60*60*24*7*1">p_inactive</xsl:when> <!-- 1 week = inactive -->
							<xsl:when test="inactivity &gt; 60*10       ">p_offline</xsl:when> <!-- 10 minutes = offline -->
							<xsl:otherwise                               >p_online</xsl:otherwise> <!-- online -->
						</xsl:choose>
					</xsl:variable>
					<td>
						<p class="{$player_class}">
							<xsl:value-of select="name"/>
							<xsl:if test="rank != 'user'"> <!-- player rank -->
								<img width="9px" height="12px" src="img/{rank}.png" alt="rank flag" class="icon" title="{rank}" />
							</xsl:if>
						</p>
					</td>

					<td><p><xsl:value-of select="level"/></p></td>
					<td>
						<div class="progress_bar">
							<div><xsl:attribute name="style">width: <xsl:value-of select="round(exp * 50)"/>px</xsl:attribute></div>
						</div>
					</td>
					<td><p><xsl:value-of select="wins"/></p></td>
					<td><p><xsl:value-of select="losses"/></p></td>
					<td><p><xsl:value-of select="draws"/></p></td>
					<td>
						<p>
							<xsl:if test="status != 'none'">
								<img width="20px" height="14px" src="img/{status}.png" alt="status flag" class="icon" title="{status}" />
							</xsl:if>
							<xsl:if test="friendly_flag = 'yes'">
								<img width="20px" height="14px" src="img/friendly_play.png" alt="friendly flag" class="icon" title="Friendly play" />
							</xsl:if>
							<xsl:if test="blind_flag = 'yes'">
								<img width="20px" height="14px" src="img/blind.png" alt="blind flag" class="icon" title="Hidden cards" />
							</xsl:if>
						</p>
					</td>
					
					<td style="text-align: left;">
						<input class="small_button" type="submit" name="user_details[{name}]" value="i" />
						<xsl:if test="$param/messages = 'yes'">
							<input class="small_button" type="submit" name="message_create[{name}]" value="m" />
						</xsl:if>
						<xsl:if test="$param/send_challenges = 'yes' and $param/free_slots &gt; 0 and $param/active_decks &gt; 0 and name != $param/PlayerName and challenged = 'no' and playingagainst = 'no' and waitingforack = 'no'">
							<input class="small_button" type="submit" name="prepare_challenge[{name}]" value="Challenge" />
						</xsl:if>
					</td>
					<td>
						<xsl:choose>
							<xsl:when test="challenged     = 'yes'"><p class="error">waiting for answer</p></xsl:when>
							<xsl:when test="playingagainst = 'yes'"><p>game already in progress</p></xsl:when>
							<xsl:when test="waitingforack  = 'yes'"><p class="warning">game over, waiting for opponent</p></xsl:when>
						</xsl:choose>
					</td>
					
				</tr>
			</xsl:for-each>
		</table>

		<!-- lower navigation -->
		<div class="filters">
			<xsl:copy-of select="am:lower_navigation($param/page_count, $param/current_page, 'players', 'Players')"/>
		</div>

		<input type ="hidden" name="CurrentPlayersPage" value="{$param/current_page}" />
		<input type ="hidden" name="CurrentOrder" value="{$param/order}" />
		<input type ="hidden" name="CurrentCondition" value="{$param/condition}" />

	</div>
</xsl:template>


</xsl:stylesheet>
