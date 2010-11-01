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


<xsl:template match="section[. = 'Replays']">
	<xsl:variable name="param" select="$params/replays" />

	<div id="games">

	<h3>Game replays</h3>
	<!-- begin filters and navigation -->
	<div class="filters">
		<!-- player filter -->
		<xsl:variable name="players">
			<value name="No player filter" value="none" />
		</xsl:variable>
		<xsl:copy-of select="am:htmlSelectBox('PlayerFilter', $param/PlayerFilter, $players, $param/players)"/>

		<xsl:variable name="mode_options">
			<value name="ignore"  value="none"    />
			<value name="include" value="include" />
			<value name="exclude" value="exclude" />
		</xsl:variable>

		<!-- hidden cards filter -->
		<img width="20px" height="14px" src="img/blind.png" alt="blind flag" class="icon" title="Hidden cards" />
		<xsl:copy-of select="am:htmlSelectBox('HiddenCards', $param/HiddenCards, $mode_options, '')"/>

		<!-- friendly game filter -->
		<img width="20px" height="14px" src="img/friendly_play.png" alt="friendly flag" class="icon" title="Friendly play" />
		<xsl:copy-of select="am:htmlSelectBox('FriendlyPlay', $param/FriendlyPlay, $mode_options, '')"/>

		<!-- long mode filter -->
		<img width="20px" height="14px" src="img/long_mode.png" alt="long mode" class="icon" title="Long mode" />
		<xsl:copy-of select="am:htmlSelectBox('LongMode', $param/LongMode, $mode_options, '')"/>

		<!-- victory type filter -->
		<xsl:variable name="victory_types">
			<value name="No victory filter"     value="none"         />
			<value name="Tower building"        value="Construction" />
			<value name="Tower destruction"     value="Destruction"  />
			<value name="Resource accumulation" value="Resource"     />
			<value name="Timeout"               value="Timeout"      />
			<value name="Draw"                  value="Draw"         />
			<value name="Surrender"             value="Surrender"    />
			<value name="Aborted"               value="Abort"        />
			<value name="Abandon"               value="Abandon"      />
		</xsl:variable>
		<xsl:copy-of select="am:htmlSelectBox('VictoryFilter', $param/VictoryFilter, $victory_types, '')"/>

		<button type="submit" name="filter_replays">Apply filters</button>
		<xsl:if test="$param/my_replays = 'yes'">
			<button type="submit" name="my_replays">My replays</button>
		</xsl:if>

		<!-- navigation -->
		<xsl:copy-of select="am:upper_navigation($param/page_count, $param/current_page, 'replays')"/>
	</div>
	<!-- end filters and navigation -->

	<xsl:choose>
		<xsl:when test="count($param/list/*) &gt; 0">
			<table cellspacing="0" class="skin_text">
				<tr>
					<xsl:variable name="columns">
						<column name="Winner"    text="Winner"   sortable="yes" />
						<column name="Loser"     text="Loser"    sortable="no"  />
						<column name="EndType"   text="Outcome"  sortable="no"  />
						<column name="Rounds"    text="Rounds"   sortable="yes" />
						<column name="Turns"     text="Turns"    sortable="yes" />
						<column name="Started"   text="Started"  sortable="yes" />
						<column name="Finished"  text="Finished" sortable="yes" />
						<column name="GameModes" text="Modes"    sortable="no"  />
						<column name="Views"     text="Views"    sortable="no"  />
						<column name="other"     text=""         sortable="no"  />
					</xsl:variable>
					
					<xsl:for-each select="exsl:node-set($columns)/*">
						<th>
							<p>
								<xsl:value-of select="@text"/>
								<xsl:if test="@sortable = 'yes'">
									<button type="submit" class="small_button" value="{@name}">
										<xsl:if test="$param/cond = @name">
											<xsl:attribute name="class">small_button pushed</xsl:attribute>
										</xsl:if>
										<xsl:choose>
											<xsl:when test="$param/cond = @name and $param/order = 'DESC'">
												<xsl:attribute name="name">replays_ord_asc</xsl:attribute>
												<xsl:text>\/</xsl:text>
											</xsl:when>
											<xsl:otherwise>
												<xsl:attribute name="name">replays_ord_desc</xsl:attribute>
												<xsl:text>/\</xsl:text>
											</xsl:otherwise>
										</xsl:choose>
									</button>
								</xsl:if>
							</p>
						</th>
					</xsl:for-each>
				</tr>
				<xsl:for-each select="$param/list/*">
					<tr class="table_row">
						<td>
							<p>
								<xsl:choose>
									<xsl:when test="Winner != ''"><xsl:value-of select="Winner"/></xsl:when>
									<xsl:otherwise><xsl:value-of select="Player1"/>/<xsl:value-of select="Player2"/></xsl:otherwise>
								</xsl:choose>
							</p>
						</td>
						<td>
							<p>
								<xsl:choose>
									<xsl:when test="Winner = Player1"><xsl:value-of select="Player2"/></xsl:when>
									<xsl:when test="Winner = Player2"><xsl:value-of select="Player1"/></xsl:when>
								</xsl:choose>
							</p>
						</td>
						<td><p><xsl:value-of select="EndType"/></p></td>
						<td><p><xsl:value-of select="Rounds"/></p></td>
						<td><p><xsl:value-of select="Turns"/></p></td>
						<td><p><xsl:value-of select="am:datetime(Started, $param/timezone)"/></p></td>
						<td><p><xsl:value-of select="am:datetime(Finished, $param/timezone)"/></p></td>
						<td>
							<p>
								<xsl:if test="contains(GameModes, 'HiddenCards')">
									<img width="20px" height="14px" src="img/blind.png" alt="blind flag" class="icon" title="Hidden cards" />
								</xsl:if>
								<xsl:if test="contains(GameModes, 'FriendlyPlay')">
									<img width="20px" height="14px" src="img/friendly_play.png" alt="friendly flag" class="icon" title="Friendly play" />
								</xsl:if>
								<xsl:if test="contains(GameModes, 'LongMode')">
									<img width="20px" height="14px" src="img/long_mode.png" alt="long_mode" class="icon" title="Long mode" />
								</xsl:if>
							</p>
						</td>
						<td><p><xsl:value-of select="Views"/></p></td>
						<td>
							<xsl:if test="Deleted = 'no'">
								<p><a class="button" href="{php:functionString('makeurl', 'Replays_details', 'CurrentReplay', GameID, 'PlayerView', 1, 'Turn', 1)}">&gt;</a></p>
							</xsl:if>
						</td>
					</tr>
				</xsl:for-each>
			</table>

			<div class="filters">
				<!-- lower navigation -->
				<xsl:copy-of select="am:lower_navigation($param/page_count, $param/current_page, 'replays', 'Replays')"/>
			</div>
		</xsl:when>
	<xsl:otherwise>
		<p class="information_line warning">There are no game replays.</p>
	</xsl:otherwise>
	</xsl:choose>

	<input type="hidden" name="CurrentRepPage" value="{$param/current_page}" />
	<input type="hidden" name="ReplaysOrder" value="{$param/order}"/>
	<input type="hidden" name="ReplaysCond" value="{$param/cond}"/>

	</div>

</xsl:template>


<xsl:template match="section[. = 'Replays_details']">
	<xsl:variable name="param" select="$params/replay" />
	<xsl:variable name="turns" select="$param/turns" />
	<xsl:variable name="current" select="$param/CurrentTurn" />

	<div id="game">

	<p class="information_line">
		<!-- begin navigation -->
		<xsl:choose>
			<xsl:when test="$current &gt; 1">
				<a class="button" href="{php:functionString('makeurl', 'Replays_details', 'CurrentReplay', $param/CurrentReplay, 'PlayerView', $param/PlayerView, 'Turn', am:max($current - 1, 1))}">&lt;</a>
			</xsl:when>
			<xsl:otherwise>
				<span class="disabled">&lt;</span>
			</xsl:otherwise>
		</xsl:choose>

		<xsl:choose>
			<xsl:when test="$current &lt; $turns">
				<a id="next" class="button" href="{php:functionString('makeurl', 'Replays_details', 'CurrentReplay', $param/CurrentReplay, 'PlayerView', $param/PlayerView, 'Turn', am:min($current + 1, $turns))}">&gt;</a>
			</xsl:when>
			<xsl:otherwise>
				<span class="disabled">&gt;</span>
			</xsl:otherwise>
		</xsl:choose>

		<!-- player switcher -->
		<xsl:variable name="view">
			<xsl:choose>
				<xsl:when test="$param/PlayerView = 1">2</xsl:when>
				<xsl:otherwise>1</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
		<a class="button" href="{php:functionString('makeurl', 'Replays_details', 'CurrentReplay', $param/CurrentReplay, 'PlayerView', $view, 'Turn', $current)}">Switch players</a>

		<!-- slideshow button -->
		<button type="button" name="slideshow">Play</button>
		<!-- end navigation -->
	</p>

	<!-- display supportive information -->
	<xsl:choose>
		<xsl:when test="$current = $turns">
			<p class="information_line info"><xsl:value-of select="$param/Winner"/> has won in round <xsl:value-of select="$param/Round"/> (turn <xsl:value-of select="$current"/>). <xsl:value-of select="$param/Outcome"/>.</p>
		</xsl:when>
		<xsl:otherwise>
			<p class="information_line info">Round <xsl:value-of select="$param/Round"/> (turn <xsl:value-of select="$current"/> of <xsl:value-of select="$turns"/>)</p>
		</xsl:otherwise>
	</xsl:choose>

	<!-- four rows: player1 cards, messages and buttons, the current status, player2 cards -->
	<table class="centered" cellpadding="0" cellspacing="0">

	<xsl:if test="$param/Background != 0">
		<xsl:attribute name="style">background-image: url('img/backgrounds/bg_<xsl:value-of select="$param/Background"/>.jpg'); background-position: center center; background-repeat: no-repeat;</xsl:attribute>
	</xsl:if>

	<!-- begin player1 cards -->
	<tr valign="top" class="hand">
		<xsl:for-each select="$param/p1Hand/*">
			<td align="center">
				<!--  display card flags, if set -->
				<xsl:choose>
					<xsl:when test="$param/HiddenCards = 'yes' and Revealed = 'yes' and $param/c_miniflags = 'no'">
						<div class="flag_space">
							<xsl:if test="NewCard = 'yes'">
								<span class="newcard">NEW</span>
							</xsl:if>
							<img src="img/revealed.png" class="revealed" width="20px" height="14px" alt="revealed" title="Revealed" />
						</div>
						<div class="clear_floats"></div>
					</xsl:when>
					<xsl:when test="NewCard = 'yes' and $param/c_miniflags = 'no'">
						<p class="flag">NEW CARD</p>
					</xsl:when>
				</xsl:choose>

				<!-- display card -->
				<xsl:copy-of select="am:cardstring(Data, $param/c_img, $param/c_oldlook, $param/c_insignias, $param/c_miniflags, NewCard, Revealed)" />
			</td>
		</xsl:for-each>
	</tr>
	<!-- end player1 cards -->

	<!-- begin messages and game buttons -->
	<tr class="buttons">
		<td class="game_mode_flags">
			<!-- game mode flags -->
			<xsl:if test="$param/HiddenCards = 'yes'">
				<img src="img/blind.png" width="20px" height="14px" alt="hidden cards" title="Hidden cards" class="icon" />
			</xsl:if>
			<xsl:if test="$param/FriendlyPlay = 'yes'">
				<img src="img/friendly_play.png" width="20px" height="14px" alt="friendly play" title="Friendly play" class="icon" />
			</xsl:if>
			<xsl:if test="$param/LongMode = 'yes'">
				<img src="img/long_mode.png" width="20px" height="14px" alt="long mode" title="Long mode" class="icon" />
			</xsl:if>
		</td>
		<td></td>
		<td></td>
		<td></td>
		<td></td>
		<td></td>
		<td></td>
		<td></td>
	</tr>
	<!-- end messages and game buttons -->

	<!-- begin status -->
	<tr>
		<!-- begin player1 empire info -->
		<xsl:choose>
			<xsl:when test="$param/minimize = 'yes'">
				<td class="minstats">
					<div>Quarry: <span>
						<xsl:value-of select="$param/p1Quarry"/>
						</span>
						<xsl:if test="$param/p1changes/Quarry != 0">
							<span class="changes"><xsl:value-of select="$param/p1changes/Quarry"/></span>
						</xsl:if>
					</div>
					<div>Bricks: <span>
						<xsl:value-of select="$param/p1Bricks"/>
						</span>
						<xsl:if test="$param/p1changes/Bricks != 0">
							<span class="changes"><xsl:value-of select="$param/p1changes/Bricks"/></span>
						</xsl:if>
					</div>
					<div>Magic: <span>
						<xsl:value-of select="$param/p1Magic"/>
						</span>
						<xsl:if test="$param/p1changes/Magic != 0">
							<span class="changes"><xsl:value-of select="$param/p1changes/Magic"/></span>
						</xsl:if>
					</div>
					<div>Gems: <span>
						<xsl:value-of select="$param/p1Gems"/>
						</span>
						<xsl:if test="$param/p1changes/Gems != 0">
							<span class="changes"><xsl:value-of select="$param/p1changes/Gems"/></span>
						</xsl:if>
					</div>
					<div>Dungeon: <span>
						<xsl:value-of select="$param/p1Dungeons"/>
						</span>
						<xsl:if test="$param/p1changes/Dungeons != 0">
							<span class="changes"><xsl:value-of select="$param/p1changes/Dungeons"/></span>
						</xsl:if>
					</div>
					<div>Recruits: <span>
						<xsl:value-of select="$param/p1Recruits"/>
						</span>
						<xsl:if test="$param/p1changes/Recruits != 0">
							<span class="changes"><xsl:value-of select="$param/p1changes/Recruits"/></span>
						</xsl:if>
					</div>
					<h5>
						<a class="profile" href="{php:functionString('makeurl', 'Players_details', 'Profile', $param/Player1)}"><xsl:value-of select="$param/Player1"/></a>
					</h5>
					<p class="info_label">Tower: <span>
						<xsl:value-of select="$param/p1Tower"/>
						</span>
						<xsl:if test="$param/p1changes/Tower != 0">
							<span class="changes"><xsl:value-of select="$param/p1changes/Tower"/></span>
						</xsl:if>
					</p>
					<p class="info_label">Wall: <span>
						<xsl:value-of select="$param/p1Wall"/>
						</span>
						<xsl:if test="$param/p1changes/Wall != 0">
							<span class="changes"><xsl:value-of select="$param/p1changes/Wall"/></span>
						</xsl:if>
					</p>
				</td>
			</xsl:when>
			<xsl:otherwise>
				<td class="stats">
					<div>
						<p class="facility">
							<xsl:value-of select="$param/p1Quarry"/>
							<xsl:if test="$param/p1changes/Quarry != 0">
								<span class="changes"><xsl:value-of select="$param/p1changes/Quarry"/></span>
							</xsl:if>
						</p>
						<p class="resource">
							<xsl:value-of select="$param/p1Bricks"/>
							<xsl:if test="$param/p1changes/Bricks != 0">
								<span class="changes"><xsl:value-of select="$param/p1changes/Bricks"/></span>
							</xsl:if>
						</p>
					</div>
					<div>
						<p class="facility">
							<xsl:value-of select="$param/p1Magic"/>
							<xsl:if test="$param/p1changes/Magic != 0">
								<span class="changes"><xsl:value-of select="$param/p1changes/Magic"/></span>
							</xsl:if>
						</p>
						<p class="resource">
							<xsl:value-of select="$param/p1Gems"/>
							<xsl:if test="$param/p1changes/Gems != 0">
								<span class="changes"><xsl:value-of select="$param/p1changes/Gems"/></span>
							</xsl:if>
						</p>
					</div>
					<div>
						<p class="facility">
							<xsl:value-of select="$param/p1Dungeons"/>
							<xsl:if test="$param/p1changes/Dungeons != 0">
								<span class="changes"><xsl:value-of select="$param/p1changes/Dungeons"/></span>
							</xsl:if>
						</p>
						<p class="resource">
							<xsl:value-of select="$param/p1Recruits"/>
							<xsl:if test="$param/p1changes/Recruits != 0">
								<span class="changes"><xsl:value-of select="$param/p1changes/Recruits"/></span>
							</xsl:if>
						</p>
					</div>
					<h5>
						<a class="profile" href="{php:functionString('makeurl', 'Players_details', 'Profile', $param/Player1)}"><xsl:value-of select="$param/Player1"/></a>
					</h5>
					<p class="info_label">Tower: <span>
						<xsl:value-of select="$param/p1Tower"/>
						</span>
						<xsl:if test="$param/p1changes/Tower != 0">
							<span class="changes"><xsl:value-of select="$param/p1changes/Tower"/></span>
						</xsl:if>
					</p>
					<p class="info_label">Wall: <span>
						<xsl:value-of select="$param/p1Wall"/>
						</span>
						<xsl:if test="$param/p1changes/Wall != 0">
							<span class="changes"><xsl:value-of select="$param/p1changes/Wall"/></span>
						</xsl:if>
					</p>
				</td>
			</xsl:otherwise>
		</xsl:choose>
		<!-- end player1 empire info -->

		<!-- begin player1 tower and wall -->
		<xsl:choose>
			<xsl:when test="$param/minimize = 'yes'">
				<td></td>
			</xsl:when>
			<xsl:otherwise>
				<td valign="bottom">
					<table cellpadding="0" cellspacing="0" summary="layout table">
						<tr>
							<td valign="bottom">
								<div style="margin: 0ex 1ex 0ex 1ex;">
									<img width="65px" style="display:block" alt="" >
										<xsl:choose>
											<xsl:when test="$param/p1Tower = $param/max_tower">
												<xsl:attribute name="src">img/victory_red.png</xsl:attribute>
												<xsl:attribute name="height">114px</xsl:attribute>
											</xsl:when>
											<xsl:otherwise>
												<xsl:attribute name="src">img/towera_red.png</xsl:attribute>
												<xsl:attribute name="height">91px</xsl:attribute>
											</xsl:otherwise>
										</xsl:choose>
									</img>
									<div class="towerbody" style="margin-left: 14px; height: {170 * $param/p1Tower div $param/max_tower}px;"></div>
								</div>
							</td>
							<td valign="bottom">
								<xsl:if test="$param/p1Wall &gt; 0">
									<div>
										<img src="img/korunka.png" width="19px" height="11px" style="display:block" alt="" />
										<div class="wallbody" style="height: {270 * $param/p1Wall div $param/max_wall}px;"></div>
									</div>
								</xsl:if>
							</td>
						</tr>
					</table>
				</td>
			</xsl:otherwise>
		</xsl:choose>
		<!-- end player1 tower and wall -->

		<!-- begin player1 discarded cards -->
		<xsl:choose>
			<xsl:when test="count($param/p1DisCards0/*) = 0 and count($param/p1DisCards1/*) = 0">
				<td></td>
			</xsl:when>
			<xsl:otherwise>
				<td align="center">
					<p class="info_label" style="font-size: small">Discarded</p>
					<div class="history" style="width: 99px;">
						<table cellpadding="0" cellspacing="0">
							<tr valign="top">
								<xsl:for-each select="$param/p1DisCards0/*">
									<td align="center"><xsl:copy-of select="am:cardstring(current(), $param/c_img, $param/c_oldlook, $param/c_insignias)" /></td>
								</xsl:for-each>
								<td style="border-right: thin solid yellow"></td>
								<xsl:for-each select="$param/p1DisCards1/*">
									<td align="center"><xsl:copy-of select="am:cardstring(current(), $param/c_img, $param/c_oldlook, $param/c_insignias)" /></td>
								</xsl:for-each>
							</tr>
						</table>
					</div>
				</td>
			</xsl:otherwise>
		</xsl:choose>
		<!-- end player1 discarded cards -->

		<!-- begin player1 last played card(s) -->
		<td align="center">
			<div class="history">
				<table cellpadding="0" cellspacing="0">
					<tr valign="top">
						<xsl:if test="count($param/p1LastCard/*) &gt; 0">
							<xsl:for-each select="$param/p1LastCard/*">
								<xsl:sort select="CardPosition" order="descending" data-type="number"/>
								<td align="center">
									<p>
										<xsl:choose>
											<xsl:when test="CardAction = 'play'">
												<xsl:attribute name="class">flag played</xsl:attribute>
												<xsl:text>PLAYED</xsl:text>
												<xsl:if test="CardMode != 0">
													<xsl:text> mode </xsl:text><xsl:value-of select="CardMode"/>
												</xsl:if>
											</xsl:when>
											<xsl:otherwise>
												<xsl:attribute name="class">flag discarded</xsl:attribute>
												<xsl:text>DISCARDED!</xsl:text>
											</xsl:otherwise>
										</xsl:choose>
									</p>
									<xsl:copy-of select="am:cardstring(CardData, $param/c_img, $param/c_oldlook, $param/c_insignias)" />
								</td>
							</xsl:for-each>
						</xsl:if>
					</tr>
				</table>
			</div>
		</td>
		<!-- end player1 last played card(s) -->

		<!-- begin player2 last played card(s) -->
		<td align="center">
			<div class="history">
				<table cellpadding="0" cellspacing="0">
					<tr valign="top">
						<xsl:if test="count($param/p2LastCard/*) &gt; 0">
							<xsl:for-each select="$param/p2LastCard/*">
								<xsl:sort select="CardPosition" order="descending" data-type="number"/>
								<td align="center">
									<p>
										<xsl:choose>
											<xsl:when test="CardAction = 'play'">
												<xsl:attribute name="class">flag played</xsl:attribute>
												<xsl:text>PLAYED</xsl:text>
												<xsl:if test="CardMode != 0">
													<xsl:text> mode </xsl:text><xsl:value-of select="CardMode"/>
												</xsl:if>
											</xsl:when>
											<xsl:otherwise>
												<xsl:attribute name="class">flag discarded</xsl:attribute>
												<xsl:text>DISCARDED!</xsl:text>
											</xsl:otherwise>
										</xsl:choose>
									</p>
									<xsl:copy-of select="am:cardstring(CardData, $param/c_img, $param/c_oldlook, $param/c_insignias)" />
								</td>
							</xsl:for-each>
						</xsl:if>
					</tr>
				</table>
			</div>
		</td>
		<!-- end player2 last played card(s) -->

		<!-- begin player2 discarded cards -->
		<xsl:choose>
			<xsl:when test="count($param/p2DisCards0/*) = 0 and count($param/p2DisCards1/*) = 0">
				<td></td>
			</xsl:when>
			<xsl:otherwise>
				<td align="center">
					<p class="info_label" style="font-size: small">Discarded</p>
					<div class="history" style="width: 99px;">
						<table cellpadding="0" cellspacing="0">
							<tr valign="top">
								<xsl:for-each select="$param/p2DisCards1/*">
									<td align="center"><xsl:copy-of select="am:cardstring(current(), $param/c_img, $param/c_oldlook, $param/c_insignias)" /></td>
								</xsl:for-each>
								<td style="border-right: thin solid yellow"></td>
								<xsl:for-each select="$param/p2DisCards0/*">
									<td align="center"><xsl:copy-of select="am:cardstring(current(), $param/c_img, $param/c_oldlook, $param/c_insignias)" /></td>
								</xsl:for-each>
							</tr>
						</table>
					</div>
				</td>
			</xsl:otherwise>
		</xsl:choose>
		<!-- end player2 discarded cards -->

		<!-- begin player2 tower and wall -->
		<xsl:choose>
			<xsl:when test="$param/minimize = 'yes'">
				<td></td>
			</xsl:when>
			<xsl:otherwise>
				<td valign="bottom" align="right">
					<table cellpadding="0" cellspacing="0" summary="layout table">
						<tr>
							<td valign="bottom">
								<xsl:if test="$param/p2Wall &gt; 0">
									<div>
										<img src="img/korunka.png" width="19px" height="11px" style="display:block" alt="" />
										<div class="wallbody" style="height: {270 * $param/p2Wall div $param/max_wall}px;"></div>
									</div>
								</xsl:if>
							</td>
							<td valign="bottom">
								<div style="margin: 0ex 1ex 0ex 1ex;">
									<img width="65px" style="display:block" alt="" >
										<xsl:choose>
											<xsl:when test="$param/p2Tower = $param/max_tower">
												<xsl:attribute name="src">img/victory_blue.png</xsl:attribute>
												<xsl:attribute name="height">114px</xsl:attribute>
											</xsl:when>
											<xsl:otherwise>
												<xsl:attribute name="src">img/towera_blue.png</xsl:attribute>
												<xsl:attribute name="height">91px</xsl:attribute>
											</xsl:otherwise>
										</xsl:choose>
									</img>
									<div class="towerbody" style="margin-left: 14px; height: {170 * $param/p2Tower div $param/max_tower}px;"></div>
								</div>
							</td>
						</tr>
					</table>
				</td>
			</xsl:otherwise>
		</xsl:choose>
		<!-- end player2 tower and wall -->

		<!-- begin player2 empire info -->
		<xsl:choose>
			<xsl:when test="$param/minimize = 'yes'">
				<td class="minstats">
					<div>Quarry: <span>
						<xsl:value-of select="$param/p2Quarry"/>
						</span>
						<xsl:if test="$param/p2changes/Quarry != 0">
							<span class="changes"><xsl:value-of select="$param/p2changes/Quarry"/></span>
						</xsl:if>
					</div>
					<div>Bricks: <span>
						<xsl:value-of select="$param/p2Bricks"/>
						</span>
						<xsl:if test="$param/p2changes/Bricks != 0">
							<span class="changes"><xsl:value-of select="$param/p2changes/Bricks"/></span>
						</xsl:if>
					</div>
					<div>Magic: <span>
						<xsl:value-of select="$param/p2Magic"/>
						</span>
						<xsl:if test="$param/p2changes/Magic != 0">
							<span class="changes"><xsl:value-of select="$param/p2changes/Magic"/></span>
						</xsl:if>
					</div>
					<div>Gems: <span>
						<xsl:value-of select="$param/p2Gems"/>
						</span>
						<xsl:if test="$param/p2changes/Gems != 0">
							<span class="changes"><xsl:value-of select="$param/p2changes/Gems"/></span>
						</xsl:if>
					</div>
					<div>Dungeon: <span>
						<xsl:value-of select="$param/p2Dungeons"/>
						</span>
						<xsl:if test="$param/p2changes/Dungeons != 0">
							<span class="changes"><xsl:value-of select="$param/p2changes/Dungeons"/></span>
						</xsl:if>
					</div>
					<div>Recruits: <span>
						<xsl:value-of select="$param/p2Recruits"/>
						</span>
						<xsl:if test="$param/p2changes/Recruits != 0">
							<span class="changes"><xsl:value-of select="$param/p2changes/Recruits"/></span>
						</xsl:if>
					</div>
					<h5>
						<a class="profile" href="{php:functionString('makeurl', 'Players_details', 'Profile', $param/Player2)}"><xsl:value-of select="$param/Player2"/></a>
					</h5>
					<p class="info_label">Tower: <span>
						<xsl:value-of select="$param/p2Tower"/>
						</span>
						<xsl:if test="$param/p2changes/Tower != 0">
							<span class="changes"><xsl:value-of select="$param/p2changes/Tower"/></span>
						</xsl:if>
					</p>
					<p class="info_label">Wall: <span>
						<xsl:value-of select="$param/p2Wall"/>
						</span>
						<xsl:if test="$param/p2changes/Wall != 0">
							<span class="changes"><xsl:value-of select="$param/p2changes/Wall"/></span>
						</xsl:if>
					</p>
				</td>
			</xsl:when>
			<xsl:otherwise>
				<td class="stats" align="right">
					<div>
						<p class="facility">
							<xsl:value-of select="$param/p2Quarry"/>
							<xsl:if test="$param/p2changes/Quarry != 0">
								<span class="changes"><xsl:value-of select="$param/p2changes/Quarry"/></span>
							</xsl:if>
						</p>
						<p class="resource">
							<xsl:value-of select="$param/p2Bricks"/>
							<xsl:if test="$param/p2changes/Bricks != 0">
								<span class="changes"><xsl:value-of select="$param/p2changes/Bricks"/></span>
							</xsl:if>
						</p>
					</div>
					<div>
						<p class="facility">
							<xsl:value-of select="$param/p2Magic"/>
							<xsl:if test="$param/p2changes/Magic != 0">
								<span class="changes"><xsl:value-of select="$param/p2changes/Magic"/></span>
							</xsl:if>
						</p>
						<p class="resource">
							<xsl:value-of select="$param/p2Gems"/>
							<xsl:if test="$param/p2changes/Gems != 0">
								<span class="changes"><xsl:value-of select="$param/p2changes/Gems"/></span>
							</xsl:if>
						</p>
					</div>
					<div>
						<p class="facility">
							<xsl:value-of select="$param/p2Dungeons"/>
							<xsl:if test="$param/p2changes/Dungeons != 0">
								<span class="changes"><xsl:value-of select="$param/p2changes/Dungeons"/></span>
							</xsl:if>
						</p>
						<p class="resource">
							<xsl:value-of select="$param/p2Recruits"/>
							<xsl:if test="$param/p2changes/Recruits != 0">
								<span class="changes"><xsl:value-of select="$param/p2changes/Recruits"/></span>
							</xsl:if>
						</p>
					</div>
					<h5>
						<a class="profile" href="{php:functionString('makeurl', 'Players_details', 'Profile', $param/Player2)}"><xsl:value-of select="$param/Player2"/></a>
					</h5>
					<p class="info_label">Tower: <span>
						<xsl:value-of select="$param/p2Tower"/>
						</span>
						<xsl:if test="$param/p2changes/Tower != 0">
							<span class="changes"><xsl:value-of select="$param/p2changes/Tower"/></span>
						</xsl:if>
					</p>
					<p class="info_label">Wall: <span>
						<xsl:value-of select="$param/p2Wall"/>
						</span>
						<xsl:if test="$param/p2changes/Wall != 0">
							<span class="changes"><xsl:value-of select="$param/p2changes/Wall"/></span>
						</xsl:if>
					</p>
				</td>
			</xsl:otherwise>
		</xsl:choose>
		<!-- end player2 empire info -->
	</tr>
	<!-- end status -->

	<!-- begin tokens -->
	<tr>

	<!-- begin player1 tokens -->
		<xsl:for-each select="$param/p1Tokens/*">
			<td>
				<xsl:if test="Name != 'none'">
					<p class="token_counter">
						<xsl:if test="Change &lt; 0">
							<xsl:attribute name="style">color: lime</xsl:attribute>
						</xsl:if>
						<xsl:choose>
							<xsl:when test="$param/c_insignias = 'yes'">
								<img class="insignia" src="img/insignias/{am:file_name(Name)}.png" width="12px" height="12px" alt="{Name}" title="{Name}" />
							</xsl:when>
							<xsl:otherwise>
								<xsl:value-of select="Name"/>
							</xsl:otherwise>
						</xsl:choose>
						<span><xsl:value-of select="Value"/></span>
						<xsl:if test="Change != 0">
							<span class="changes">
								<xsl:if test="Change &gt; 0">+</xsl:if>
								<xsl:value-of select="Change"/>
							</span>
						</xsl:if>
					</p>
				</xsl:if>
			</td>
		</xsl:for-each>	
	<!-- end player1 tokens -->

		<!-- game state indicator -->
		<td colspan="2"><p class="info_label"><xsl:value-of select="$param/Current"/>'s turn</p></td>

	<!-- begin player2 tokens -->
		<xsl:for-each select="$param/p2Tokens/*">
			<td>
				<xsl:if test="Name != 'none'">
					<p class="token_counter">
						<xsl:if test="Change &lt; 0">
							<xsl:attribute name="style">color: lime</xsl:attribute>
						</xsl:if>
						<xsl:choose>
							<xsl:when test="$param/c_insignias = 'yes'">
								<img class="insignia" src="img/insignias/{am:file_name(Name)}.png" width="12px" height="12px" alt="{Name}" title="{Name}" />
							</xsl:when>
							<xsl:otherwise>
								<xsl:value-of select="Name"/>
							</xsl:otherwise>
						</xsl:choose>
						<span><xsl:value-of select="Value"/></span>
						<xsl:if test="Change != 0">
							<span class="changes">
								<xsl:if test="Change &gt; 0">+</xsl:if>
								<xsl:value-of select="Change"/>
							</span>
						</xsl:if>
					</p>
				</xsl:if>
			</td>
		</xsl:for-each>
	<!-- end player2 tokens -->

	</tr>
	<!-- end tokens -->

	<!-- begin player2 cards -->
	<tr valign="top" class="hand">
		<xsl:for-each select="$param/p2Hand/*">
			<td align="center">
				<!--  display card flags, if set -->
				<xsl:choose>
					<xsl:when test="$param/HiddenCards = 'yes' and Revealed = 'yes' and $param/c_miniflags = 'no'">
						<div class="flag_space">
							<xsl:if test="NewCard = 'yes'">
								<span class="newcard">NEW</span>
							</xsl:if>
							<img src="img/revealed.png" class="revealed" width="20px" height="14px" alt="revealed" title="Revealed" />
						</div>
						<div class="clear_floats"></div>
					</xsl:when>
					<xsl:when test="NewCard = 'yes' and $param/c_miniflags = 'no'">
						<p class="flag">NEW CARD</p>
					</xsl:when>
				</xsl:choose>

				<!-- display card -->
				<xsl:copy-of select="am:cardstring(Data, $param/c_img, $param/c_oldlook, $param/c_insignias, $param/c_miniflags, NewCard, Revealed)" />
			</td>
		</xsl:for-each>
	</tr>
	<!-- end player2 cards -->

	</table>

	</div>

</xsl:template>


<xsl:template match="section[. = 'Replays_history']">
	<xsl:variable name="param" select="$params/replays_history" />
	<xsl:variable name="turns" select="$param/turns" />
	<xsl:variable name="current" select="$param/CurrentTurn" />

	<div id="game">

	<p class="information_line">
		<!-- begin navigation -->
		<xsl:choose>
			<xsl:when test="$current &gt; 1">
				<a class="button" href="{php:functionString('makeurl', 'Replays_history', 'CurrentReplay', $param/CurrentReplay, 'Turn', am:max($current - 1, 1))}">&lt;</a>
			</xsl:when>
			<xsl:otherwise>
				<span class="disabled">&lt;</span>
			</xsl:otherwise>
		</xsl:choose>

		<xsl:choose>
			<xsl:when test="$current &lt; $turns">
				<a class="button" href="{php:functionString('makeurl', 'Replays_history', 'CurrentReplay', $param/CurrentReplay, 'Turn', am:min($current + 1, $turns))}">&gt;</a>
			</xsl:when>
			<xsl:otherwise>
				<span class="disabled">&gt;</span>
			</xsl:otherwise>
		</xsl:choose>

		<a class="button" href="{php:functionString('makeurl', 'Games_details', 'CurrentGame', $param/CurrentReplay)}">Back to game</a>
		<!-- end navigation -->
	</p>

	<!-- display supportive information -->
	<p class="information_line info">Round <xsl:value-of select="$param/Round"/></p>

	<!-- four rows: player1 cards, messages and buttons, the current status, player2 cards -->
	<table class="centered" cellpadding="0" cellspacing="0">

	<xsl:if test="$param/Background != 0">
		<xsl:attribute name="style">background-image: url('img/backgrounds/bg_<xsl:value-of select="$param/Background"/>.jpg'); background-position: center center; background-repeat: no-repeat;</xsl:attribute>
	</xsl:if>

	<!-- begin player1 cards -->
	<tr valign="top" class="hand">
		<xsl:for-each select="$param/p1Hand/*">
			<td align="center">
				<!--  display card flags, if set -->
				<xsl:choose>
					<xsl:when test="$param/HiddenCards = 'yes' and Revealed = 'yes' and $param/c_miniflags = 'no'">
						<div class="flag_space">
							<xsl:if test="NewCard = 'yes'">
								<span class="newcard">NEW</span>
							</xsl:if>
							<img src="img/revealed.png" class="revealed" width="20px" height="14px" alt="revealed" title="Revealed" />
						</div>
						<div class="clear_floats"></div>
					</xsl:when>
					<xsl:when test="NewCard = 'yes' and $param/c_miniflags = 'no'">
						<p class="flag">NEW CARD</p>
					</xsl:when>
				</xsl:choose>

				<!-- display card -->
				<xsl:copy-of select="am:cardstring(Data, $param/c_img, $param/c_oldlook, $param/c_insignias, $param/c_miniflags, NewCard, Revealed)" />
			</td>
		</xsl:for-each>
	</tr>
	<!-- end player1 cards -->

	<!-- begin messages and game buttons -->
	<tr class="buttons">
		<td class="game_mode_flags">
			<!-- game mode flags -->
			<xsl:if test="$param/HiddenCards = 'yes'">
				<img src="img/blind.png" width="20px" height="14px" alt="hidden cards" title="Hidden cards" class="icon" />
			</xsl:if>
			<xsl:if test="$param/FriendlyPlay = 'yes'">
				<img src="img/friendly_play.png" width="20px" height="14px" alt="friendly play" title="Friendly play" class="icon" />
			</xsl:if>
			<xsl:if test="$param/LongMode = 'yes'">
				<img src="img/long_mode.png" width="20px" height="14px" alt="long mode" title="Long mode" class="icon" />
			</xsl:if>
		</td>
		<td></td>
		<td></td>
		<td></td>
		<td></td>
		<td></td>
		<td></td>
		<td></td>
	</tr>
	<!-- end messages and game buttons -->

	<!-- begin status -->
	<tr>
		<!-- begin player1 empire info -->
		<xsl:choose>
			<xsl:when test="$param/minimize = 'yes'">
				<td class="minstats">
					<div>Quarry: <span>
						<xsl:value-of select="$param/p1Quarry"/>
						</span>
						<xsl:if test="$param/p1changes/Quarry != 0">
							<span class="changes"><xsl:value-of select="$param/p1changes/Quarry"/></span>
						</xsl:if>
					</div>
					<div>Bricks: <span>
						<xsl:value-of select="$param/p1Bricks"/>
						</span>
						<xsl:if test="$param/p1changes/Bricks != 0">
							<span class="changes"><xsl:value-of select="$param/p1changes/Bricks"/></span>
						</xsl:if>
					</div>
					<div>Magic: <span>
						<xsl:value-of select="$param/p1Magic"/>
						</span>
						<xsl:if test="$param/p1changes/Magic != 0">
							<span class="changes"><xsl:value-of select="$param/p1changes/Magic"/></span>
						</xsl:if>
					</div>
					<div>Gems: <span>
						<xsl:value-of select="$param/p1Gems"/>
						</span>
						<xsl:if test="$param/p1changes/Gems != 0">
							<span class="changes"><xsl:value-of select="$param/p1changes/Gems"/></span>
						</xsl:if>
					</div>
					<div>Dungeon: <span>
						<xsl:value-of select="$param/p1Dungeons"/>
						</span>
						<xsl:if test="$param/p1changes/Dungeons != 0">
							<span class="changes"><xsl:value-of select="$param/p1changes/Dungeons"/></span>
						</xsl:if>
					</div>
					<div>Recruits: <span>
						<xsl:value-of select="$param/p1Recruits"/>
						</span>
						<xsl:if test="$param/p1changes/Recruits != 0">
							<span class="changes"><xsl:value-of select="$param/p1changes/Recruits"/></span>
						</xsl:if>
					</div>
					<h5>
						<a class="profile" href="{php:functionString('makeurl', 'Players_details', 'Profile', $param/Player1)}"><xsl:value-of select="$param/Player1"/></a>
					</h5>
					<p class="info_label">Tower: <span>
						<xsl:value-of select="$param/p1Tower"/>
						</span>
						<xsl:if test="$param/p1changes/Tower != 0">
							<span class="changes"><xsl:value-of select="$param/p1changes/Tower"/></span>
						</xsl:if>
					</p>
					<p class="info_label">Wall: <span>
						<xsl:value-of select="$param/p1Wall"/>
						</span>
						<xsl:if test="$param/p1changes/Wall != 0">
							<span class="changes"><xsl:value-of select="$param/p1changes/Wall"/></span>
						</xsl:if>
					</p>
				</td>
			</xsl:when>
			<xsl:otherwise>
				<td class="stats">
					<div>
						<p class="facility">
							<xsl:value-of select="$param/p1Quarry"/>
							<xsl:if test="$param/p1changes/Quarry != 0">
								<span class="changes"><xsl:value-of select="$param/p1changes/Quarry"/></span>
							</xsl:if>
						</p>
						<p class="resource">
							<xsl:value-of select="$param/p1Bricks"/>
							<xsl:if test="$param/p1changes/Bricks != 0">
								<span class="changes"><xsl:value-of select="$param/p1changes/Bricks"/></span>
							</xsl:if>
						</p>
					</div>
					<div>
						<p class="facility">
							<xsl:value-of select="$param/p1Magic"/>
							<xsl:if test="$param/p1changes/Magic != 0">
								<span class="changes"><xsl:value-of select="$param/p1changes/Magic"/></span>
							</xsl:if>
						</p>
						<p class="resource">
							<xsl:value-of select="$param/p1Gems"/>
							<xsl:if test="$param/p1changes/Gems != 0">
								<span class="changes"><xsl:value-of select="$param/p1changes/Gems"/></span>
							</xsl:if>
						</p>
					</div>
					<div>
						<p class="facility">
							<xsl:value-of select="$param/p1Dungeons"/>
							<xsl:if test="$param/p1changes/Dungeons != 0">
								<span class="changes"><xsl:value-of select="$param/p1changes/Dungeons"/></span>
							</xsl:if>
						</p>
						<p class="resource">
							<xsl:value-of select="$param/p1Recruits"/>
							<xsl:if test="$param/p1changes/Recruits != 0">
								<span class="changes"><xsl:value-of select="$param/p1changes/Recruits"/></span>
							</xsl:if>
						</p>
					</div>
					<h5>
						<a class="profile" href="{php:functionString('makeurl', 'Players_details', 'Profile', $param/Player1)}"><xsl:value-of select="$param/Player1"/></a>
					</h5>
					<p class="info_label">Tower: <span>
						<xsl:value-of select="$param/p1Tower"/>
						</span>
						<xsl:if test="$param/p1changes/Tower != 0">
							<span class="changes"><xsl:value-of select="$param/p1changes/Tower"/></span>
						</xsl:if>
					</p>
					<p class="info_label">Wall: <span>
						<xsl:value-of select="$param/p1Wall"/>
						</span>
						<xsl:if test="$param/p1changes/Wall != 0">
							<span class="changes"><xsl:value-of select="$param/p1changes/Wall"/></span>
						</xsl:if>
					</p>
				</td>
			</xsl:otherwise>
		</xsl:choose>
		<!-- end player1 empire info -->

		<!-- begin player1 tower and wall -->
		<xsl:choose>
			<xsl:when test="$param/minimize = 'yes'">
				<td></td>
			</xsl:when>
			<xsl:otherwise>
				<td valign="bottom">
					<table cellpadding="0" cellspacing="0" summary="layout table">
						<tr>
							<td valign="bottom">
								<div style="margin: 0ex 1ex 0ex 1ex;">
									<img width="65px" style="display:block" alt="" >
										<xsl:choose>
											<xsl:when test="$param/p1Tower = $param/max_tower">
												<xsl:attribute name="src">img/victory_red.png</xsl:attribute>
												<xsl:attribute name="height">114px</xsl:attribute>
											</xsl:when>
											<xsl:otherwise>
												<xsl:attribute name="src">img/towera_red.png</xsl:attribute>
												<xsl:attribute name="height">91px</xsl:attribute>
											</xsl:otherwise>
										</xsl:choose>
									</img>
									<div class="towerbody" style="margin-left: 14px; height: {170 * $param/p1Tower div $param/max_tower}px;"></div>
								</div>
							</td>
							<td valign="bottom">
								<xsl:if test="$param/p1Wall &gt; 0">
									<div>
										<img src="img/korunka.png" width="19px" height="11px" style="display:block" alt="" />
										<div class="wallbody" style="height: {270 * $param/p1Wall div $param/max_wall}px;"></div>
									</div>
								</xsl:if>
							</td>
						</tr>
					</table>
				</td>
			</xsl:otherwise>
		</xsl:choose>
		<!-- end player1 tower and wall -->

		<!-- begin player1 discarded cards -->
		<xsl:choose>
			<xsl:when test="count($param/p1DisCards0/*) = 0 and count($param/p1DisCards1/*) = 0">
				<td></td>
			</xsl:when>
			<xsl:otherwise>
				<td align="center">
					<p class="info_label" style="font-size: small">Discarded</p>
					<div class="history" style="width: 99px;">
						<table cellpadding="0" cellspacing="0">
							<tr valign="top">
								<xsl:for-each select="$param/p1DisCards0/*">
									<td align="center"><xsl:copy-of select="am:cardstring(current(), $param/c_img, $param/c_oldlook, $param/c_insignias)" /></td>
								</xsl:for-each>
								<td style="border-right: thin solid yellow"></td>
								<xsl:for-each select="$param/p1DisCards1/*">
									<td align="center"><xsl:copy-of select="am:cardstring(current(), $param/c_img, $param/c_oldlook, $param/c_insignias)" /></td>
								</xsl:for-each>
							</tr>
						</table>
					</div>
				</td>
			</xsl:otherwise>
		</xsl:choose>
		<!-- end player1 discarded cards -->

		<!-- begin player1 last played card(s) -->
		<td align="center">
			<div class="history">
				<table cellpadding="0" cellspacing="0">
					<tr valign="top">
						<xsl:if test="count($param/p1LastCard/*) &gt; 0">
							<xsl:for-each select="$param/p1LastCard/*">
								<xsl:sort select="CardPosition" order="descending" data-type="number"/>
								<td align="center">
									<p>
										<xsl:choose>
											<xsl:when test="CardAction = 'play'">
												<xsl:attribute name="class">flag played</xsl:attribute>
												<xsl:text>PLAYED</xsl:text>
												<xsl:if test="CardMode != 0">
													<xsl:text> mode </xsl:text><xsl:value-of select="CardMode"/>
												</xsl:if>
											</xsl:when>
											<xsl:otherwise>
												<xsl:attribute name="class">flag discarded</xsl:attribute>
												<xsl:text>DISCARDED!</xsl:text>
											</xsl:otherwise>
										</xsl:choose>
									</p>
									<xsl:copy-of select="am:cardstring(CardData, $param/c_img, $param/c_oldlook, $param/c_insignias)" />
								</td>
							</xsl:for-each>
						</xsl:if>
					</tr>
				</table>
			</div>
		</td>
		<!-- end player1 last played card(s) -->

		<!-- begin player2 last played card(s) -->
		<td align="center">
			<div class="history">
				<table cellpadding="0" cellspacing="0">
					<tr valign="top">
						<xsl:if test="count($param/p2LastCard/*) &gt; 0">
							<xsl:for-each select="$param/p2LastCard/*">
								<xsl:sort select="CardPosition" order="descending" data-type="number"/>
								<td align="center">
									<p>
										<xsl:choose>
											<xsl:when test="CardAction = 'play'">
												<xsl:attribute name="class">flag played</xsl:attribute>
												<xsl:text>PLAYED</xsl:text>
												<xsl:if test="CardMode != 0">
													<xsl:text> mode </xsl:text><xsl:value-of select="CardMode"/>
												</xsl:if>
											</xsl:when>
											<xsl:otherwise>
												<xsl:attribute name="class">flag discarded</xsl:attribute>
												<xsl:text>DISCARDED!</xsl:text>
											</xsl:otherwise>
										</xsl:choose>
									</p>
									<xsl:copy-of select="am:cardstring(CardData, $param/c_img, $param/c_oldlook, $param/c_insignias)" />
								</td>
							</xsl:for-each>
						</xsl:if>
					</tr>
				</table>
			</div>
		</td>
		<!-- end player2 last played card(s) -->

		<!-- begin player2 discarded cards -->
		<xsl:choose>
			<xsl:when test="count($param/p2DisCards0/*) = 0 and count($param/p2DisCards1/*) = 0">
				<td></td>
			</xsl:when>
			<xsl:otherwise>
				<td align="center">
					<p class="info_label" style="font-size: small">Discarded</p>
					<div class="history" style="width: 99px;">
						<table cellpadding="0" cellspacing="0">
							<tr valign="top">
								<xsl:for-each select="$param/p2DisCards1/*">
									<td align="center"><xsl:copy-of select="am:cardstring(current(), $param/c_img, $param/c_oldlook, $param/c_insignias)" /></td>
								</xsl:for-each>
								<td style="border-right: thin solid yellow"></td>
								<xsl:for-each select="$param/p2DisCards0/*">
									<td align="center"><xsl:copy-of select="am:cardstring(current(), $param/c_img, $param/c_oldlook, $param/c_insignias)" /></td>
								</xsl:for-each>
							</tr>
						</table>
					</div>
				</td>
			</xsl:otherwise>
		</xsl:choose>
		<!-- end player2 discarded cards -->

		<!-- begin player2 tower and wall -->
		<xsl:choose>
			<xsl:when test="$param/minimize = 'yes'">
				<td></td>
			</xsl:when>
			<xsl:otherwise>
				<td valign="bottom" align="right">
					<table cellpadding="0" cellspacing="0" summary="layout table">
						<tr>
							<td valign="bottom">
								<xsl:if test="$param/p2Wall &gt; 0">
									<div>
										<img src="img/korunka.png" width="19px" height="11px" style="display:block" alt="" />
										<div class="wallbody" style="height: {270 * $param/p2Wall div $param/max_wall}px;"></div>
									</div>
								</xsl:if>
							</td>
							<td valign="bottom">
								<div style="margin: 0ex 1ex 0ex 1ex;">
									<img width="65px" style="display:block" alt="" >
										<xsl:choose>
											<xsl:when test="$param/p2Tower = $param/max_tower">
												<xsl:attribute name="src">img/victory_blue.png</xsl:attribute>
												<xsl:attribute name="height">114px</xsl:attribute>
											</xsl:when>
											<xsl:otherwise>
												<xsl:attribute name="src">img/towera_blue.png</xsl:attribute>
												<xsl:attribute name="height">91px</xsl:attribute>
											</xsl:otherwise>
										</xsl:choose>
									</img>
									<div class="towerbody" style="margin-left: 14px; height: {170 * $param/p2Tower div $param/max_tower}px;"></div>
								</div>
							</td>
						</tr>
					</table>
				</td>
			</xsl:otherwise>
		</xsl:choose>
		<!-- end player2 tower and wall -->

		<!-- begin player2 empire info -->
		<xsl:choose>
			<xsl:when test="$param/minimize = 'yes'">
				<td class="minstats">
					<div>Quarry: <span>
						<xsl:value-of select="$param/p2Quarry"/>
						</span>
						<xsl:if test="$param/p2changes/Quarry != 0">
							<span class="changes"><xsl:value-of select="$param/p2changes/Quarry"/></span>
						</xsl:if>
					</div>
					<div>Bricks: <span>
						<xsl:value-of select="$param/p2Bricks"/>
						</span>
						<xsl:if test="$param/p2changes/Bricks != 0">
							<span class="changes"><xsl:value-of select="$param/p2changes/Bricks"/></span>
						</xsl:if>
					</div>
					<div>Magic: <span>
						<xsl:value-of select="$param/p2Magic"/>
						</span>
						<xsl:if test="$param/p2changes/Magic != 0">
							<span class="changes"><xsl:value-of select="$param/p2changes/Magic"/></span>
						</xsl:if>
					</div>
					<div>Gems: <span>
						<xsl:value-of select="$param/p2Gems"/>
						</span>
						<xsl:if test="$param/p2changes/Gems != 0">
							<span class="changes"><xsl:value-of select="$param/p2changes/Gems"/></span>
						</xsl:if>
					</div>
					<div>Dungeon: <span>
						<xsl:value-of select="$param/p2Dungeons"/>
						</span>
						<xsl:if test="$param/p2changes/Dungeons != 0">
							<span class="changes"><xsl:value-of select="$param/p2changes/Dungeons"/></span>
						</xsl:if>
					</div>
					<div>Recruits: <span>
						<xsl:value-of select="$param/p2Recruits"/>
						</span>
						<xsl:if test="$param/p2changes/Recruits != 0">
							<span class="changes"><xsl:value-of select="$param/p2changes/Recruits"/></span>
						</xsl:if>
					</div>
					<h5>
						<a class="profile" href="{php:functionString('makeurl', 'Players_details', 'Profile', $param/Player2)}"><xsl:value-of select="$param/Player2"/></a>
					</h5>
					<p class="info_label">Tower: <span>
						<xsl:value-of select="$param/p2Tower"/>
						</span>
						<xsl:if test="$param/p2changes/Tower != 0">
							<span class="changes"><xsl:value-of select="$param/p2changes/Tower"/></span>
						</xsl:if>
					</p>
					<p class="info_label">Wall: <span>
						<xsl:value-of select="$param/p2Wall"/>
						</span>
						<xsl:if test="$param/p2changes/Wall != 0">
							<span class="changes"><xsl:value-of select="$param/p2changes/Wall"/></span>
						</xsl:if>
					</p>
				</td>
			</xsl:when>
			<xsl:otherwise>
				<td class="stats" align="right">
					<div>
						<p class="facility">
							<xsl:value-of select="$param/p2Quarry"/>
							<xsl:if test="$param/p2changes/Quarry != 0">
								<span class="changes"><xsl:value-of select="$param/p2changes/Quarry"/></span>
							</xsl:if>
						</p>
						<p class="resource">
							<xsl:value-of select="$param/p2Bricks"/>
							<xsl:if test="$param/p2changes/Bricks != 0">
								<span class="changes"><xsl:value-of select="$param/p2changes/Bricks"/></span>
							</xsl:if>
						</p>
					</div>
					<div>
						<p class="facility">
							<xsl:value-of select="$param/p2Magic"/>
							<xsl:if test="$param/p2changes/Magic != 0">
								<span class="changes"><xsl:value-of select="$param/p2changes/Magic"/></span>
							</xsl:if>
						</p>
						<p class="resource">
							<xsl:value-of select="$param/p2Gems"/>
							<xsl:if test="$param/p2changes/Gems != 0">
								<span class="changes"><xsl:value-of select="$param/p2changes/Gems"/></span>
							</xsl:if>
						</p>
					</div>
					<div>
						<p class="facility">
							<xsl:value-of select="$param/p2Dungeons"/>
							<xsl:if test="$param/p2changes/Dungeons != 0">
								<span class="changes"><xsl:value-of select="$param/p2changes/Dungeons"/></span>
							</xsl:if>
						</p>
						<p class="resource">
							<xsl:value-of select="$param/p2Recruits"/>
							<xsl:if test="$param/p2changes/Recruits != 0">
								<span class="changes"><xsl:value-of select="$param/p2changes/Recruits"/></span>
							</xsl:if>
						</p>
					</div>
					<h5>
						<a class="profile" href="{php:functionString('makeurl', 'Players_details', 'Profile', $param/Player2)}"><xsl:value-of select="$param/Player2"/></a>
					</h5>
					<p class="info_label">Tower: <span>
						<xsl:value-of select="$param/p2Tower"/>
						</span>
						<xsl:if test="$param/p2changes/Tower != 0">
							<span class="changes"><xsl:value-of select="$param/p2changes/Tower"/></span>
						</xsl:if>
					</p>
					<p class="info_label">Wall: <span>
						<xsl:value-of select="$param/p2Wall"/>
						</span>
						<xsl:if test="$param/p2changes/Wall != 0">
							<span class="changes"><xsl:value-of select="$param/p2changes/Wall"/></span>
						</xsl:if>
					</p>
				</td>
			</xsl:otherwise>
		</xsl:choose>
		<!-- end player2 empire info -->
	</tr>
	<!-- end status -->

	<!-- begin tokens -->
	<tr>

	<!-- begin player1 tokens -->
		<xsl:for-each select="$param/p1Tokens/*">
			<td>
				<xsl:if test="Name != 'none'">
					<p class="token_counter">
						<xsl:if test="Change &lt; 0">
							<xsl:attribute name="style">color: lime</xsl:attribute>
						</xsl:if>
						<xsl:choose>
							<xsl:when test="$param/c_insignias = 'yes'">
								<img class="insignia" src="img/insignias/{am:file_name(Name)}.png" width="12px" height="12px" alt="{Name}" title="{Name}" />
							</xsl:when>
							<xsl:otherwise>
								<xsl:value-of select="Name"/>
							</xsl:otherwise>
						</xsl:choose>
						<span><xsl:value-of select="Value"/></span>
						<xsl:if test="Change != 0">
							<span class="changes">
								<xsl:if test="Change &gt; 0">+</xsl:if>
								<xsl:value-of select="Change"/>
							</span>
						</xsl:if>
					</p>
				</xsl:if>
			</td>
		</xsl:for-each>	
	<!-- end player1 tokens -->

		<!-- game state indicator -->
		<td colspan="2"><p class="info_label"><xsl:value-of select="$param/Current"/>'s turn</p></td>

	<!-- begin player2 tokens -->
		<xsl:for-each select="$param/p2Tokens/*">
			<td>
				<xsl:if test="Name != 'none'">
					<p class="token_counter">
						<xsl:if test="Change &lt; 0">
							<xsl:attribute name="style">color: lime</xsl:attribute>
						</xsl:if>
						<xsl:choose>
							<xsl:when test="$param/c_insignias = 'yes'">
								<img class="insignia" src="img/insignias/{am:file_name(Name)}.png" width="12px" height="12px" alt="{Name}" title="{Name}" />
							</xsl:when>
							<xsl:otherwise>
								<xsl:value-of select="Name"/>
							</xsl:otherwise>
						</xsl:choose>
						<span><xsl:value-of select="Value"/></span>
						<xsl:if test="Change != 0">
							<span class="changes">
								<xsl:if test="Change &gt; 0">+</xsl:if>
								<xsl:value-of select="Change"/>
							</span>
						</xsl:if>
					</p>
				</xsl:if>
			</td>
		</xsl:for-each>
	<!-- end player2 tokens -->

	</tr>
	<!-- end tokens -->

	<!-- begin player2 cards -->
	<tr valign="top" class="hand">
		<xsl:for-each select="$param/p2Hand/*">
			<td align="center">
				<!--  display new card indicator, if set -->
				<xsl:if test="NewCard = 'yes' and ($param/HiddenCards = 'no' or Revealed = 'yes') and $param/c_miniflags = 'no'">
					<p class="flag">NEW CARD</p>
				</xsl:if>

				<!-- display card -->
				<xsl:choose>
					<xsl:when test="$param/HiddenCards = 'yes' and Revealed = 'no'">
						<div class="hidden_card">
							<!--  display new card indicator, if set -->
							<xsl:if test="NewCard = 'yes'">
								<p class="flag">NEW CARD</p>
							</xsl:if>
						</div>
					</xsl:when>
					<xsl:otherwise>
						<xsl:copy-of select="am:cardstring(Data, $param/c_img, $param/c_oldlook, $param/c_insignias, $param/c_miniflags, NewCard)" />
					</xsl:otherwise>
				</xsl:choose>
			</td>
		</xsl:for-each>
	</tr>
	<!-- end player2 cards -->

	</table>

	</div>

</xsl:template>


</xsl:stylesheet>
