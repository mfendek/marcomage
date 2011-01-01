<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
                xmlns="http://www.w3.org/1999/xhtml"
                xmlns:am="http://arcomage.netvor.sk"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:exsl="http://exslt.org/common"
                xmlns:php="http://php.net/xsl"
                extension-element-prefixes="exsl php">
<xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes" doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" />

<!-- includes -->
<xsl:include href="template_main.xsl" />


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
			<input type="text" name="pname_filter" maxlength="20" size="20" value="{$param/pname_filter}" title="search phrase for player names" />

			<!-- activity filter -->
			<xsl:variable name="activity_types">
				<value name="No activity filter"         value="none"    />
				<value name="Active players"             value="active"  />
				<value name="Active and offline players" value="offline" />
				<value name="Show all players"           value="all"     />
			</xsl:variable>
			<xsl:copy-of select="am:htmlSelectBox('activity_filter', $param/activity_filter, $activity_types, '')"/>

			<!-- status filter -->
			<xsl:variable name="status_types">
				<value name="No status filter"       value="none"   />
				<value name="Looking for game"       value="ready"  />
				<value name="Looking for quick game" value="quick"  />
				<value name="Do not disturb"         value="dnd"    />
				<value name="Newbie"                 value="newbie" />
			</xsl:variable>
			<xsl:copy-of select="am:htmlSelectBox('status_filter', $param/status_filter, $status_types, '')"/>

			<!-- achievement sort (special sort option) -->
			<xsl:variable name="achievement_types">
				<value name="No achievement sort" value="none"         />
				<value name="Quarry gained"       value="Quarry"       />
				<value name="Magic gained"        value="Magic"        />
				<value name="Dungeons gained"     value="Dungeons"     />
				<value name="Rares played"        value="Rares"        />
				<value name="Tower built"         value="Tower"        />
				<value name="Wall built"          value="Wall"         />
				<value name="Tower destroyed"     value="TowerDamage"  />
				<value name="Wall destroyed"      value="WallDamage"   />
				<value name="Assassin"            value="Assassin"     />
				<value name="Builder"             value="Builder"      />
				<value name="Carpenter"           value="Carpenter"    />
				<value name="Collector"           value="Collector"    />
				<value name="Desolator"           value="Desolator"    />
				<value name="Dragon"              value="Dragon"       />
				<value name="Gentle touch"        value="Gentle_touch" />
				<value name="Saboteur"            value="Saboteur"     />
				<value name="Snob"                value="Snob"         />
				<value name="Survivor"            value="Survivor"     />
				<value name="Titan"               value="Titan"        />
			</xsl:variable>
			<xsl:copy-of select="am:htmlSelectBox('achievement_sort', $param/achievement_sort, $achievement_types, '')"/>

			<button type="submit" name="filter_players">Apply filters</button>

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
								<button type="submit" class="small_button" value="{@name}">
									<xsl:if test="$param/condition = @name">
										<xsl:attribute name="class">small_button pushed</xsl:attribute>
									</xsl:if>
									<xsl:choose>
										<xsl:when test="$param/condition = @name and $param/order = 'DESC'">
											<xsl:attribute name="name">players_ord_asc</xsl:attribute>
											<xsl:text>\/</xsl:text>
										</xsl:when>
										<xsl:otherwise>
											<xsl:attribute name="name">players_ord_desc</xsl:attribute>
											<xsl:text>/\</xsl:text>
										</xsl:otherwise>
									</xsl:choose>
								</button>
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
					
					<td><img class="icon" width="18px" height="12px" src="img/flags/{country}.gif" alt="country flag" title="{country}" /></td>

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
							<a class="profile" href="{php:functionString('makeurl', 'Players_details', 'Profile', name)}"><xsl:value-of select="name"/></a>
							<xsl:if test="rank != 'user'"> <!-- player rank -->
								<img class="icon" width="9px" height="12px" src="img/{rank}.png" alt="rank flag" title="{rank}" />
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
								<img class="icon" width="20px" height="14px" src="img/{status}.png" alt="status flag" title="{status}" />
							</xsl:if>
							<xsl:if test="friendly_flag = 'yes'">
								<img class="icon" width="20px" height="14px" src="img/friendly_play.png" alt="Friendly play" title="Friendly play" />
							</xsl:if>
							<xsl:if test="blind_flag = 'yes'">
								<img class="icon" width="20px" height="14px" src="img/blind.png" alt="Hidden cards" title="Hidden cards" />
							</xsl:if>
							<xsl:if test="long_flag = 'yes'">
								<img class="icon" width="20px" height="14px" src="img/long_mode.png" alt="Long mode" title="Long mode" />
							</xsl:if>
						</p>
					</td>
					
					<td style="text-align: left;">
						<xsl:if test="$param/messages = 'yes'">
							<button class="small_button" type="submit" name="message_create" value="{name}">m</button>
						</xsl:if>
						<xsl:if test="$param/send_challenges = 'yes' and $param/free_slots &gt; 0 and $param/active_decks &gt; 0 and name != $param/PlayerName">
							<button class="small_button" type="submit" name="prepare_challenge" value="{name}">Challenge</button>
						</xsl:if>
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


<xsl:template match="section[. = 'Players_details']">
	<xsl:variable name="param" select="$params/profile" />
	<xsl:variable name="opponent" select="$param/PlayerName" />
	<xsl:variable name="activedecks" select="count($param/decks/*)" />

	<div id="details">
	<div class="skin_text">
		<h3><xsl:value-of select="$param/PlayerName"/>'s details</h3>

		<div class="details_float_right">
			<p>Zodiac sign</p>
			<img height="100px" width="100px" src="img/zodiac/{$param/Sign}.jpg" alt="sign" />
			<p><xsl:value-of select="$param/Sign"/></p>
		</div>

		<div class="details_float_right">
			<p>Avatar</p>
			<img height="60px" width="60px" src="img/avatars/{$param/Avatar}" alt="avatar" />
		</div>

		<xsl:if test="count($param/statistics/*) &gt; 0">
			<div class="statistics">

			<h3>Versus statistics</h3>

			<h4>Victories</h4>
			<xsl:for-each select="$param/statistics/wins/*">
				<p>
					<span><xsl:value-of select="count"/> (<xsl:value-of select="ratio"/>%)</span>
					<xsl:value-of select="EndType"/>
				</p>
			</xsl:for-each>
			<p><span><xsl:value-of select="$param/statistics/wins_total"/></span>Total</p>

			<h4>Losses</h4>
			<xsl:for-each select="$param/statistics/losses/*">
				<p>
					<span><xsl:value-of select="count"/> (<xsl:value-of select="ratio"/>%)</span>
					<xsl:value-of select="EndType"/>
				</p>
			</xsl:for-each>
			<p><span><xsl:value-of select="$param/statistics/losses_total"/></span>Total</p>

			<h4>Other</h4>
			<xsl:for-each select="$param/statistics/other/*">
				<p>
					<span><xsl:value-of select="count"/> (<xsl:value-of select="ratio"/>%)</span>
					<xsl:value-of select="EndType"/>
				</p>
			</xsl:for-each>
			<p><span><xsl:value-of select="$param/statistics/other_total"/></span>Total</p>

			<h4>Average game duration</h4>
			<p><span><xsl:value-of select="$param/statistics/turns"/></span>Turns</p>
			<p><span><xsl:value-of select="$param/statistics/rounds"/></span>Rounds</p>
			<p><span><xsl:value-of select="$param/statistics/turns_long"/></span>
				<xsl:text>Turns</xsl:text>
				<img class="icon" width="20px" height="14px" src="img/long_mode.png" alt="Long mode" title="Long mode" />
			</p>
			<p><span><xsl:value-of select="$param/statistics/rounds_long"/></span>
				<xsl:text>Rounds</xsl:text>
				<img class="icon" width="20px" height="14px" src="img/long_mode.png" alt="Long mode" title="Long mode" />
			</p>

			</div>
		</xsl:if>

		<p class="player_achievements">
			<a href="{php:functionString('makeurl', 'Players_achievements', 'Profile', $param/PlayerName)}"><xsl:value-of select="$param/PlayerName"/>'s achievements</a>
		</p>

		<p>First name<span class="detail_value"><xsl:value-of select="$param/Firstname"/></span></p>
		<p>Surname<span class="detail_value"><xsl:value-of select="$param/Surname"/></span></p>

		<xsl:variable name="gender_color">
			<xsl:choose>
				<xsl:when test="$param/Gender = 'male'">blue</xsl:when>
				<xsl:when test="$param/Gender = 'female'">HotPink</xsl:when>
				<xsl:otherwise>green</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
		
		<p>Gender<span class="detail_value" style="color: {$gender_color}"><xsl:value-of select="$param/Gender"/></span></p>
		<p>E-mail<span class="detail_value"><xsl:value-of select="$param/Email"/></span></p>
		<p>ICQ / IM<span class="detail_value"><xsl:value-of select="$param/Imnumber"/></span></p>
		<p>Date of birth (dd-mm-yyyy)<span class="detail_value"><xsl:value-of select="$param/Birthdate"/></span></p>
		<p>Age<span class="detail_value"><xsl:value-of select="$param/Age"/></span></p>
		<p>
			<xsl:text>Rank</xsl:text>
			<span class="detail_value"><xsl:value-of select="$param/PlayerType"/></span>
			<xsl:if test="$param/PlayerType != 'user'">
				<img class="icon" width="9px" height="12px" src="img/{$param/PlayerType}.png" alt="rank flag" title="{$param/PlayerType}" />
			</xsl:if>
		</p>
		<p>Country<img class="icon" width="18px" height="12px" src="img/flags/{$param/Country}.gif" alt="country flag" title="{$param/Country}" /> <span class="detail_value"><xsl:value-of select="$param/Country"/></span></p>
		<p>
			<xsl:text>Status</xsl:text>
			<xsl:if test="$param/Status != 'none'"><img class="icon" width="20px" height="14px" src="img/{$param/Status}.png" alt="status flag" title="{$param/Status}" /></xsl:if>
			<xsl:if test="$param/FriendlyFlag = 'yes'"><img class="icon" width="20px" height="14px" src="img/friendly_play.png" alt="Friendly play" title="Friendly play" /></xsl:if>
			<xsl:if test="$param/BlindFlag = 'yes'"><img class="icon" width="20px" height="14px" src="img/blind.png" alt="Hidden cards" title="Hidden cards" /></xsl:if>
			<xsl:if test="$param/LongFlag = 'yes'"><img class="icon" width="20px" height="14px" src="img/long_mode.png" alt="Long mode" title="Long mode" /></xsl:if>
		</p>
		<p>Level<span class="detail_value"><xsl:value-of select="$param/Level"/></span></p>
		<p>
			<xsl:text>Experience</xsl:text>
			<span class="detail_value">
				<xsl:value-of select="$param/Exp"/>
				<xsl:text> / </xsl:text>
				<xsl:value-of select="$param/NextLevel"/>
			</span>
		</p>
		<p>
			<xsl:text>Wins / Losses / Draws</xsl:text>
			<span class="detail_value">
				<xsl:value-of select="$param/Wins"/>
				<xsl:text> / </xsl:text>
				<xsl:value-of select="$param/Losses"/>
				<xsl:text> / </xsl:text>
				<xsl:value-of select="$param/Draws"/>
			</span>
		</p>
		<p>Free slots<span class="detail_value"><xsl:value-of select="$param/FreeSlots"/></span></p>
		<p>Number of posts<span class="detail_value"><xsl:value-of select="$param/Posts"/></span></p>
		<p>
			<xsl:text>Registered on</xsl:text>
			<span class="detail_value">
				<xsl:choose>
					<xsl:when test="$param/Registered != '0000-00-00 00:00:00'"><xsl:value-of select="am:datetime($param/Registered, $param/timezone)"/></xsl:when>
				<xsl:otherwise>Before 18. August, 2009</xsl:otherwise>
				</xsl:choose>
			</span>
		</p>
		<p>
			<xsl:text>Last seen on</xsl:text>
			<span class="detail_value">
				<xsl:choose>
						<xsl:when test="$param/LastQuery != '0000-00-00 00:00:00'"><xsl:value-of select="am:datetime($param/LastQuery, $param/timezone)"/></xsl:when>
					<xsl:otherwise>n/a</xsl:otherwise>
				</xsl:choose>
			</span>
		</p>

		<p>Hobbies, interests</p>
		<div class="detail_value hobbies"><xsl:copy-of select="am:textencode($param/Hobby)"/></div>

<!--		
		check if the player is allowed to challenge this opponent:
		- can't have more than MAX_GAMES active games + initiated challenges + received challenges
		- can't be in the $challengefrom['Player2'] or in the $activegames['Player1'] (['Player2'] is allowed)
		- can't play without a ready deck
		- can't challenge self
-->
		<xsl:if test="$param/send_challenges = 'yes' and $opponent != $param/CurPlayerName">
			<h4>Challenge options</h4>
			
			<xsl:choose>

				<xsl:when test="$activedecks &gt; 0 and $param/free_slots &gt; 0">
					<xsl:choose>
						<xsl:when test="$param/challenging = 'no'">
							<p><button type="submit" name="prepare_challenge" value="{am:urlencode($opponent)}">Challenge this user</button></p>
						</xsl:when>
						<xsl:otherwise>
							<p>
								<button type="submit" name="send_challenge" value="{am:urlencode($opponent)}">Send challenge</button>
								<select name="ChallengeDeck" size="1">
									<xsl:if test="$param/RandomDeck = 'yes'">
										<option value="{am:urlencode($param/random_deck)}">select random</option>
									</xsl:if>
									<xsl:for-each select="$param/decks/*">
										<option value="{am:urlencode(DeckID)}"><xsl:value-of select="Deckname"/></option>
									</xsl:for-each>
								</select>
							</p>
							<p>
								<input type="checkbox" name="HiddenCards">
									<xsl:if test="$param/HiddenCards = 'yes'"><xsl:attribute name="checked">checked</xsl:attribute></xsl:if>
								</input>
								<xsl:text>Hide opponent's cards</xsl:text>
							</p>
							<p>
								<input type="checkbox" name="FriendlyPlay">
									<xsl:if test="$param/FriendlyPlay = 'yes'"><xsl:attribute name="checked">checked</xsl:attribute></xsl:if>
								</input>
								<xsl:text>Friendly play</xsl:text>
							</p>
							<p>
								<input type="checkbox" name="LongMode">
									<xsl:if test="$param/LongMode = 'yes'"><xsl:attribute name="checked">checked</xsl:attribute></xsl:if>
								</input>
								<xsl:text>Long mode</xsl:text>
							</p>
							<xsl:copy-of select="am:BBcodeButtons()"/>
							<textarea name="Content" rows="10" cols="50"></textarea>
						</xsl:otherwise>
					</xsl:choose>
				</xsl:when>
				
				<xsl:when test="$activedecks = 0">
					<p class="information_line warning">You need at least one ready deck to challenge other players.</p>
				</xsl:when>
				
				<xsl:when test="$param/free_slots = 0">
					<p class="warning">You cannot initiate any more games.</p>
				</xsl:when>
				
			</xsl:choose>
		</xsl:if>
		
		<xsl:if test="$param/messages = 'yes'">
			<h4>Message options</h4>
			<button type="submit" name="message_create" value="{am:urlencode($opponent)}">Send message</button>
		</xsl:if>
		
		<xsl:if test="$param/change_rights = 'yes'">
			<h4>Change access rights</h4>			
			<button type="submit" name="change_access" value="{am:urlencode($opponent)}">Change access rights</button>
			<xsl:variable name="user_types">
				<type name="moderator"  text="Moderator"/>
				<type name="supervisor" text="Supervisor"/>
				<type name="user"       text="User"     />
				<type name="squashed"   text="Squashed" />
				<type name="limited"    text="Limited"  />
				<type name="banned"     text="Banned"   />
			</xsl:variable>
			<select name="new_access" size="1">
				<xsl:for-each select="exsl:node-set($user_types)/*">
					<option value="{@name}">
						<xsl:if test="$param/PlayerType = @name"><xsl:attribute name="selected">selected</xsl:attribute></xsl:if>
						<xsl:value-of select="@text"/>
					</option>
				</xsl:for-each>
			</select>
		</xsl:if>

		<xsl:if test="$param/export_deck = 'yes' and count($param/export_decks/*) &gt; 0">
			<h4>Export deck</h4>
			<select name="ExportDeck" size="1">
				<xsl:for-each select="$param/export_decks/*">
					<option value="{am:urlencode(DeckID)}">
						<xsl:value-of select="Deckname"/>
						<xsl:text> (</xsl:text>
						<xsl:value-of select="Wins"/>
						<xsl:text> / </xsl:text>
						<xsl:value-of select="Losses"/>
						<xsl:text> / </xsl:text>
						<xsl:value-of select="Draws"/>
						<xsl:text>)</xsl:text>
					</option>
				</xsl:for-each>
			</select>
			<button type="submit" name="export_deck_remote" value="{am:urlencode($opponent)}">Export</button>
		</xsl:if>

		<xsl:if test="$param/system_notification = 'yes'">
			<h4>System notification</h4>
			<button type="submit" name="system_notification" value="{am:urlencode($opponent)}">Send system notification</button>
		</xsl:if>

		<xsl:if test="$param/change_all_avatar = 'yes'">
			<h4>Reset avatar</h4>
			<button type="submit" name="reset_avatar_remote" value="{am:urlencode($opponent)}">Reset</button>
		</xsl:if>

		<xsl:if test="$param/reset_exp = 'yes'">
			<h4>Reset exp</h4>
			<button type="submit" name="reset_exp" value="{am:urlencode($opponent)}">Reset</button>

			<h4>Add gold (<xsl:value-of select="$opponent"/> has <xsl:value-of select="$param/Gold"/> gold)</h4>
			<p>
				<input type="text" name="gold_amount" maxlength="4" size="4" />
				<button type="submit" name="add_gold" value="{am:urlencode($opponent)}">Add</button>
			</p>
		</xsl:if>

		<div class="clear_floats"></div>
	</div>
	</div>
</xsl:template>


<xsl:template match="section[. = 'Players_achievements']">
	<xsl:variable name="param" select="$params/achievements" />
	<xsl:variable name="opponent" select="$param/PlayerName" />

	<div id="achievements">
		<h3><xsl:value-of select="$param/PlayerName"/>'s achievements</h3>

		<table class="centered skin_label" cellpadding="0" cellspacing="0" >

		<!-- table header -->
		<tr>
			<xsl:for-each select="$param/data/*">
				<th><p>Tier <xsl:value-of select="position()"/></p></th>
			</xsl:for-each>
		</tr>

		<!-- table body -->
		<tr>
			<!-- number of columns (configurable) -->
			<xsl:variable name="columns" select="3"/>
			<xsl:for-each select="$param/data/*">
				<td>
					<table cellpadding="0" cellspacing="0">
					<xsl:variable name="cur_tier" select="current()"/>

					<xsl:for-each select="current()/*[position() &lt;= floor(((count(current()/*) - 1) div $columns)) + 1]">
						<tr>
							<xsl:variable name="i" select="position()"/>
							<xsl:for-each select="exsl:node-set($cur_tier)/*[position() &gt;= (($i - 1)*$columns + 1) and position() &lt;= $i*$columns]">
								<td>
									<img class="achievement" height="100px" width="100px" alt="{name}" >
									<xsl:choose>
										<!-- case 1: final achievement -->
										<xsl:when test="count = ''">
											<xsl:attribute name="src">
												<xsl:text>img/achievements/</xsl:text>
												<xsl:value-of select="am:file_name(name)"/>
												<xsl:if test="condition = 'yes'">_gained</xsl:if>
												<xsl:text>.png</xsl:text>
											</xsl:attribute>
											<xsl:attribute name="title">
												<xsl:value-of select="name"/>
												<xsl:text> - </xsl:text>
												<xsl:value-of select="desc"/>
												<xsl:text> (</xsl:text>
												<xsl:value-of select="reward"/>
												<xsl:text> gold reward)</xsl:text>
												<xsl:if test="condition = 'yes'"> achievement already gained</xsl:if>
											</xsl:attribute>
										</xsl:when>
										<!-- case 2: regular achievement -->
										<xsl:otherwise>
											<xsl:attribute name="src">
												<xsl:text>img/achievements/</xsl:text>
												<xsl:value-of select="am:file_name(name)"/>
												<xsl:if test="count &gt;= condition">_gained</xsl:if>
												<xsl:text>.png</xsl:text>
											</xsl:attribute>
											<xsl:attribute name="title">
												<xsl:value-of select="name"/>
												<xsl:text> (</xsl:text>
												<xsl:value-of select="count"/>
												<xsl:text> / </xsl:text>
												<xsl:value-of select="condition"/>
												<xsl:text> ) - </xsl:text>
												<xsl:value-of select="desc"/>
												<xsl:text> (</xsl:text>
												<xsl:value-of select="reward"/>
												<xsl:text> gold reward)</xsl:text>
												<xsl:if test="count &gt;= condition"> achievement already gained</xsl:if>
											</xsl:attribute>
										</xsl:otherwise>
									</xsl:choose>
									</img>
								</td>
							</xsl:for-each>
						</tr>
					</xsl:for-each>
					</table>
				</td>
			</xsl:for-each>
		</tr>

	</table>
	</div>
</xsl:template>


</xsl:stylesheet>
