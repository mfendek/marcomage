<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
                xmlns="http://www.w3.org/1999/xhtml"
                xmlns:am="http://arcomage.netvor.sk"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:exsl="http://exslt.org/common"
                extension-element-prefixes="exsl">
<xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes" doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" />


<xsl:template match="section[. = 'Game']">
	<xsl:variable name="param" select="$params/game" />

	<!-- remember the current location across pages -->
	<div><input type="hidden" name="CurrentGame" value="{$param/CurrentGame}"/></div>

	<xsl:choose>
		<!-- display supportive information -->
		<xsl:when test="$param/GameState = 'in progress'">
			<p class="information_line">Round <xsl:value-of select="$param/Round"/></p>
		</xsl:when>
		<!-- finished -->
		<xsl:otherwise>
			<p class="information_trans">
				<xsl:choose>
					<xsl:when test="$param/Winner = $param/PlayerName">You have won in round <xsl:value-of select="$param/Round"/>. <xsl:value-of select="$param/Outcome"/>.</xsl:when>
					<xsl:when test="$param/Winner = $param/OpponentName"><xsl:value-of select="$param/Winner"/> has won in round <xsl:value-of select="$param/Round"/>. <xsl:value-of select="$param/Outcome"/>.</xsl:when>
					<xsl:when test="($param/Winner = '') and ($param/Outcome = 'Draw')">Game ended in a draw in round <xsl:value-of select="$param/Round"/>.</xsl:when>
					<xsl:when test="($param/Winner = '') and ($param/Outcome = 'Aborted')">Game was aborted in round <xsl:value-of select="$param/Round"/>.</xsl:when>
				</xsl:choose>
			</p>
		</xsl:otherwise>
	</xsl:choose>

	<!-- four rows: your cards, messages and buttons, the current status, opponent's cards -->
	<table class="centered" cellpadding="0" cellspacing="0">

	<!-- begin your cards -->
	<tr valign="top">
		<xsl:for-each select="$param/MyHand/*">
			<td align="center">
				<!--  display discard button -->
				<xsl:if test="($param/GameState = 'in progress') and ($param/Current = $param/PlayerName)">
					<input type="submit" name="discard_card[{position()}]" value="Discard"/>
				</xsl:if>

				<!--  display new card indicator, if set -->
				<xsl:if test="NewCard = 'yes'">
					<p class="newcard_flag">NEW CARD</p>
				</xsl:if>

				<!-- display card -->
				<xsl:copy-of select="am:cardstring(Data, $param/c_img, $param/c_keywords, $param/c_text, $param/c_oldlook)" />
				
				<!-- play button and card modes -->
				<xsl:if test="Playable = 'yes'">
					<input type="submit" name="play_card[{position()}]" value="Play"/>
					<xsl:if test="Modes &gt; 0">
						<select name="card_mode[{position()}]" class="card_modes" size="1">
							<xsl:variable name="numbers">
								<xsl:call-template name="numbers">
									<xsl:with-param name="from" select="1"/>
									<xsl:with-param name="to" select="Modes"/>
								</xsl:call-template>
							</xsl:variable>
							<xsl:for-each select="exsl:node-set($numbers)/*">
								<option value="{.}"><xsl:value-of select="."/></option>
							</xsl:for-each>
						</select>
					</xsl:if>
				</xsl:if>
			</td>
		</xsl:for-each>
	</tr>
	<!-- end your cards -->

	<!-- begin messages and game buttons -->
	<tr>
		<!-- begin quick game switching menu -->
		<td>
			<select name="games_list">
				<xsl:for-each select="$param/GameList/*">
					<option value="{Value}">
						<xsl:if test="Selected = 'yes'">
							<xsl:attribute name="selected">selected</xsl:attribute>
						</xsl:if>
						<xsl:if test="Color != ''">
							<xsl:attribute name="style">color: <xsl:value-of select="Color"/></xsl:attribute>
						</xsl:if>
						<xsl:value-of select="Content"/>
					</option>
				</xsl:for-each>
			</select>
		</td>
		<td>
			<input type="submit" name="jump_to_game" value="Select" />
		<!-- end quick game switching menu -->

		<!-- begin 'jump to next game' button -->
			<xsl:if test="$param/num_games_your_turn &gt; 0">
				<input type="submit" name="active_game" value="Next game" />
			</xsl:if>
		</td>
		<!-- end 'jump to next game' button -->
		<td></td>

		<!-- begin game state indicator -->
		<xsl:choose>
			<xsl:when test="$param/GameState = 'in progress'">
				<td colspan="2">
					<p class="info_label">
						<xsl:choose>
							<xsl:when test="$param/Current = $param/PlayerName">
								<xsl:attribute name="style">color: lime</xsl:attribute>
								It is your turn
							</xsl:when>
							<xsl:when test="$param/opp_isOnline = 'yes'">
								<xsl:attribute name="style">color: <xsl:value-of select="am:color('HotPink')"/></xsl:attribute>
								It is <span style="color: white;"><xsl:value-of select="$param/OpponentName"/></span>'s turn
							</xsl:when>
							<xsl:otherwise>
								<xsl:attribute name="style">color: <xsl:value-of select="am:color('HotPink')"/></xsl:attribute>
								It is <xsl:value-of select="$param/OpponentName"/>'s turn
							</xsl:otherwise>
						</xsl:choose>
					</p>
				</td>
				<td></td>
				<td>
					<input type="submit" name="view_deck" value="Deck" />
					<input type="submit" name="view_note" value="Note" >
						<xsl:if test="$param/has_note = 'yes'">
							<xsl:attribute name="class">menuselected</xsl:attribute>
						</xsl:if>
					</input>
				</td>

				<!-- begin surrender/abort button -->
				<td style="text-align: right">
					<xsl:choose>
						<xsl:when test="$param/opp_isDead = 'yes'">
							<input type="submit" name="abort_game" value="Abort game" />
						</xsl:when>
						<xsl:when test="$param/finish_game = 'yes'">
							<input type="submit" name="finish_game" value="Finish game" />
						</xsl:when>
						<xsl:when test="$param/surrender = 'yes'">
							<input type="submit" name="confirm_surrender" value="Confirm surrender" />
						</xsl:when>
						<xsl:otherwise>
							<input type="submit" name="surrender" value="Surrender" />
						</xsl:otherwise>
					</xsl:choose>
				<!-- end surrender/abort button -->
				</td>
			</xsl:when>
			<xsl:otherwise>
				<td align="center" colspan="2">
					<input type="submit" name="Confirm" value="Leave the game" />
				</td>
			</xsl:otherwise>
		</xsl:choose>
		<!-- end game state indicator -->
	</tr>
	<!-- end messages and game buttons -->

	<!-- begin status -->
	<tr>
		<!-- begin your empire info -->
		<xsl:choose>
			<xsl:when test="$param/minimize = 'yes'">
				<td class="minstats">
					<div>Quarry: <span>
						<xsl:if test="$param/mycolors/Quarry != ''">
							<xsl:attribute name="style"><xsl:value-of select="$param/mycolors/Quarry"/></xsl:attribute>
						</xsl:if>
						<xsl:value-of select="$param/MyQuarry"/>
						</span>
					</div>
					<div>Bricks: <span>
						<xsl:if test="$param/mycolors/Bricks != ''">
							<xsl:attribute name="style"><xsl:value-of select="$param/mycolors/Bricks"/></xsl:attribute>
						</xsl:if>
						<xsl:value-of select="$param/MyBricks"/>
						</span>
					</div>
					<div>Magic: <span>
						<xsl:if test="$param/mycolors/Magic != ''">
							<xsl:attribute name="style"><xsl:value-of select="$param/mycolors/Magic"/></xsl:attribute>
						</xsl:if>
						<xsl:value-of select="$param/MyMagic"/>
						</span>
					</div>
					<div>Gems: <span>
						<xsl:if test="$param/mycolors/Gems != ''">
							<xsl:attribute name="style"><xsl:value-of select="$param/mycolors/Gems"/></xsl:attribute>
						</xsl:if>
						<xsl:value-of select="$param/MyGems"/>
						</span>
					</div>
					<div>Dungeon: <span>
						<xsl:if test="$param/mycolors/Dungeons != ''">
							<xsl:attribute name="style"><xsl:value-of select="$param/mycolors/Dungeons"/></xsl:attribute>
						</xsl:if>
						<xsl:value-of select="$param/MyDungeons"/>
						</span>
					</div>
					<div>Recruits: <span>
						<xsl:if test="$param/mycolors/Recruits != ''">
							<xsl:attribute name="style"><xsl:value-of select="$param/mycolors/Recruits"/></xsl:attribute>
						</xsl:if>
						<xsl:value-of select="$param/MyRecruits"/>
						</span>
					</div>
					<h5>
						<xsl:value-of select="$param/PlayerName"/>
						<img width="18px" height="12px" src="img/flags/{$param/mycountry}.gif" alt="country flag" />
					</h5>
					<p class="info_label">Tower: <span>
						<xsl:if test="$param/mycolors/Tower != ''">
							<xsl:attribute name="style"><xsl:value-of select="$param/mycolors/Tower"/></xsl:attribute>
						</xsl:if>
						<xsl:value-of select="$param/MyTower"/>
						</span>
					</p>
					<p class="info_label">Wall: <span>
						<xsl:if test="$param/mycolors/Wall != ''">
							<xsl:attribute name="style"><xsl:value-of select="$param/mycolors/Wall"/></xsl:attribute>
						</xsl:if>
						<xsl:value-of select="$param/MyWall"/>
						</span>
					</p>
				</td>
			</xsl:when>
			<xsl:otherwise>
				<td class="stats">
					<div>
						<p>
							<xsl:if test="$param/mycolors/Quarry != ''">
								<xsl:attribute name="style"><xsl:value-of select="$param/mycolors/Quarry"/></xsl:attribute>
							</xsl:if>
							<xsl:value-of select="$param/MyQuarry"/>
						</p>
						<p>
							<xsl:if test="$param/mycolors/Bricks != ''">
								<xsl:attribute name="style"><xsl:value-of select="$param/mycolors/Bricks"/></xsl:attribute>
							</xsl:if>
							<xsl:value-of select="$param/MyBricks"/>
						</p>
					</div>
					<div>
						<p>
							<xsl:if test="$param/mycolors/Magic != ''">
								<xsl:attribute name="style"><xsl:value-of select="$param/mycolors/Magic"/></xsl:attribute>
							</xsl:if>
							<xsl:value-of select="$param/MyMagic"/>
						</p>
						<p>
							<xsl:if test="$param/mycolors/Gems != ''">
								<xsl:attribute name="style"><xsl:value-of select="$param/mycolors/Gems"/></xsl:attribute>
							</xsl:if>
							<xsl:value-of select="$param/MyGems"/>
						</p>
					</div>
					<div>
						<p>
							<xsl:if test="$param/mycolors/Dungeons != ''">
								<xsl:attribute name="style"><xsl:value-of select="$param/mycolors/Dungeons"/></xsl:attribute>
							</xsl:if>
							<xsl:value-of select="$param/MyDungeons"/>
						</p>
						<p>
							<xsl:if test="$param/mycolors/Recruits != ''">
								<xsl:attribute name="style"><xsl:value-of select="$param/mycolors/Recruits"/></xsl:attribute>
							</xsl:if>
							<xsl:value-of select="$param/MyRecruits"/>
						</p>
					</div>
					<h5>
						<xsl:value-of select="$param/PlayerName"/>
						<img width="18px" height="12px" src="img/flags/{$param/mycountry}.gif" alt="country flag" />
					</h5>
					<p class="info_label">Tower: <span>
						<xsl:if test="$param/mycolors/Tower != ''">
							<xsl:attribute name="style"><xsl:value-of select="$param/mycolors/Tower"/></xsl:attribute>
						</xsl:if>
						<xsl:value-of select="$param/MyTower"/>
						</span>
					</p>
					<p class="info_label">Wall: <span>
						<xsl:if test="$param/mycolors/Wall != ''">
							<xsl:attribute name="style"><xsl:value-of select="$param/mycolors/Wall"/></xsl:attribute>
						</xsl:if>
						<xsl:value-of select="$param/MyWall"/>
						</span>
					</p>
				</td>
			</xsl:otherwise>
		</xsl:choose>
		<!-- end your empire info -->

		<!-- begin your tower and wall -->
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
											<xsl:when test="$param/MyTower = 100">
												<xsl:attribute name="src">img/victory_red.png</xsl:attribute>
												<xsl:attribute name="height">114px</xsl:attribute>
											</xsl:when>
											<xsl:otherwise>
												<xsl:attribute name="src">img/towera_red.png</xsl:attribute>
												<xsl:attribute name="height">91px</xsl:attribute>
											</xsl:otherwise>
										</xsl:choose>
									</img>
									<div class="towerbody" style="margin-left: 14px; height: {170 * $param/MyTower div 100}px;"></div>
								</div>
							</td>
							<td valign="bottom">
								<xsl:if test="$param/MyWall &gt; 0">
									<div>
										<img src="img/korunka.png" width="19px" height="11px" style="display:block" alt="" />
										<div class="wallbody" style="height: {270 * $param/MyWall div 150}px;"></div>
									</div>
								</xsl:if>
							</td>
						</tr>
					</table>
				</td>
			</xsl:otherwise>
		</xsl:choose>
		<!-- end your tower and wall -->

		<!-- begin your discarded cards -->
		<xsl:choose>
			<xsl:when test="count($param/MyDisCards0/*) = 0 and count($param/MyDisCards1/*) = 0">
				<td></td>
			</xsl:when>
			<xsl:otherwise>
				<td align="center">
					<p class="info_label" style="font-size: small">Discarded</p>
					<div class="history" style="width: 99px;">
						<table cellpadding="0" cellspacing="0">
							<tr>
								<xsl:for-each select="$param/MyDisCards0/*">
									<td align="center"><xsl:copy-of select="am:cardstring(current(), $param/c_img, $param/c_keywords, $param/c_text, $param/c_oldlook)" /></td>
								</xsl:for-each>
								<td style="border-right: thin solid yellow"></td>
								<xsl:for-each select="$param/MyDisCards1/*">
									<td align="center"><xsl:copy-of select="am:cardstring(current(), $param/c_img, $param/c_keywords, $param/c_text, $param/c_oldlook)" /></td>
								</xsl:for-each>
							</tr>
						</table>
					</div>
				</td>
			</xsl:otherwise>
		</xsl:choose>
		<!-- end discarded cards -->

		<!-- begin your last played card(s) -->
		<td align="center">
			<div class="history">
				<table cellpadding="0" cellspacing="0">
					<tr>
						<xsl:if test="count($param/MyLastCard/*) &gt; 0">
							<xsl:for-each select="$param/MyLastCard/*">
								<xsl:sort select="name()" order="descending"/>
								<td align="center">
									<p class="newcard_flag history_flag">
										<xsl:choose>
											<xsl:when test="CardAction = 'play'">
												<xsl:attribute name="style">color: lime</xsl:attribute>
												PLAYED
												<xsl:if test="CardMode != 0">
													 mode <xsl:value-of select="CardMode"/>
												</xsl:if>
											</xsl:when>
											<xsl:otherwise>
												<xsl:attribute name="style">color: red</xsl:attribute>
												DISCARDED!
											</xsl:otherwise>
										</xsl:choose>
									</p>
									<xsl:copy-of select="am:cardstring(CardData, $param/c_img, $param/c_keywords, $param/c_text, $param/c_oldlook)" />
								</td>
							</xsl:for-each>
						</xsl:if>
					</tr>
				</table>
			</div>
		</td>
		<!-- end your last played card(s) -->

		<!-- begin his last played card(s) -->
		<td align="center">
			<div class="history">
				<table cellpadding="0" cellspacing="0">
					<tr>
						<xsl:if test="count($param/HisLastCard/*) &gt; 0">
							<xsl:for-each select="$param/HisLastCard/*">
								<xsl:sort select="name()" order="descending"/>
								<td align="center">
									<p class="newcard_flag history_flag">
										<xsl:choose>
											<xsl:when test="CardAction = 'play'">
												<xsl:attribute name="style">color: lime</xsl:attribute>
												PLAYED
												<xsl:if test="CardMode != 0">
													 mode <xsl:value-of select="CardMode"/>
												</xsl:if>
											</xsl:when>
											<xsl:otherwise>
												<xsl:attribute name="style">color: red</xsl:attribute>
												DISCARDED!
											</xsl:otherwise>
										</xsl:choose>
									</p>
									<xsl:copy-of select="am:cardstring(CardData, $param/c_img, $param/c_keywords, $param/c_text, $param/c_oldlook)" />
								</td>
							</xsl:for-each>
						</xsl:if>
					</tr>
				</table>
			</div>
		</td>
		<!-- end his last played card(s) -->

		<!-- begin his discarded cards -->
		<xsl:choose>
			<xsl:when test="count($param/HisDisCards0/*) = 0 and count($param/HisDisCards1/*) = 0">
				<td></td>
			</xsl:when>
			<xsl:otherwise>
				<td align="center">
					<p class="info_label" style="font-size: small">Discarded</p>
					<div class="history" style="width: 99px;">
						<table cellpadding="0" cellspacing="0">
							<tr>
								<xsl:for-each select="$param/HisDisCards1/*">
									<td align="center"><xsl:copy-of select="am:cardstring(current(), $param/c_img, $param/c_keywords, $param/c_text, $param/c_oldlook)" /></td>
								</xsl:for-each>
								<td style="border-right: thin solid yellow"></td>
								<xsl:for-each select="$param/HisDisCards0/*">
									<td align="center"><xsl:copy-of select="am:cardstring(current(), $param/c_img, $param/c_keywords, $param/c_text, $param/c_oldlook)" /></td>
								</xsl:for-each>
							</tr>
						</table>
					</div>
				</td>
			</xsl:otherwise>
		</xsl:choose>
		<!-- end discarded cards -->

		<!-- begin his tower and wall -->
		<xsl:choose>
			<xsl:when test="$param/minimize = 'yes'">
				<td></td>
			</xsl:when>
			<xsl:otherwise>
				<td valign="bottom" align="right">
					<table cellpadding="0" cellspacing="0" summary="layout table">
						<tr>
							<td valign="bottom">
								<xsl:if test="$param/HisWall &gt; 0">
									<div>
										<img src="img/korunka.png" width="19px" height="11px" style="display:block" alt="" />
										<div class="wallbody" style="height: {270 * $param/HisWall div 150}px;"></div>
									</div>
								</xsl:if>
							</td>
							<td valign="bottom">
								<div style="margin: 0ex 1ex 0ex 1ex;">
									<img width="65px" style="display:block" alt="" >
										<xsl:choose>
											<xsl:when test="$param/HisTower = 100">
												<xsl:attribute name="src">img/victory_blue.png</xsl:attribute>
												<xsl:attribute name="height">114px</xsl:attribute>
											</xsl:when>
											<xsl:otherwise>
												<xsl:attribute name="src">img/towera_blue.png</xsl:attribute>
												<xsl:attribute name="height">91px</xsl:attribute>
											</xsl:otherwise>
										</xsl:choose>
									</img>
									<div class="towerbody" style="margin-left: 14px; height: {170 * $param/HisTower div 100}px;"></div>
								</div>
							</td>
						</tr>
					</table>
				</td>
			</xsl:otherwise>
		</xsl:choose>
		<!-- end his tower and wall -->

		<!-- begin his empire info -->
		<xsl:choose>
			<xsl:when test="$param/minimize = 'yes'">
				<td class="minstats">
					<div>Quarry: <span>
						<xsl:if test="$param/hiscolors/Quarry != ''">
							<xsl:attribute name="style"><xsl:value-of select="$param/hiscolors/Quarry"/></xsl:attribute>
						</xsl:if>
						<xsl:value-of select="$param/HisQuarry"/>
						</span>
					</div>
					<div>Bricks: <span>
						<xsl:if test="$param/hiscolors/Bricks != ''">
							<xsl:attribute name="style"><xsl:value-of select="$param/hiscolors/Bricks"/></xsl:attribute>
						</xsl:if>
						<xsl:value-of select="$param/HisBricks"/>
						</span>
					</div>
					<div>Magic: <span>
						<xsl:if test="$param/hiscolors/Magic != ''">
							<xsl:attribute name="style"><xsl:value-of select="$param/hiscolors/Magic"/></xsl:attribute>
						</xsl:if>
						<xsl:value-of select="$param/HisMagic"/>
						</span>
					</div>
					<div>Gems: <span>
						<xsl:if test="$param/hiscolors/Gems != ''">
							<xsl:attribute name="style"><xsl:value-of select="$param/hiscolors/Gems"/></xsl:attribute>
						</xsl:if>
						<xsl:value-of select="$param/HisGems"/>
						</span>
					</div>
					<div>Dungeon: <span>
						<xsl:if test="$param/hiscolors/Dungeons != ''">
							<xsl:attribute name="style"><xsl:value-of select="$param/hiscolors/Dungeons"/></xsl:attribute>
						</xsl:if>
						<xsl:value-of select="$param/HisDungeons"/>
						</span>
					</div>
					<div>Recruits: <span>
						<xsl:if test="$param/hiscolors/Recruits != ''">
							<xsl:attribute name="style"><xsl:value-of select="$param/hiscolors/Recruits"/></xsl:attribute>
						</xsl:if>
						<xsl:value-of select="$param/HisRecruits"/>
						</span>
					</div>
					<h5>
						<img width="18px" height="12px" src="img/flags/{$param/hiscountry}.gif" alt="country flag" />
						<xsl:value-of select="$param/OpponentName"/>
						<input class="details" type="submit" name="user_details[{$param/OpponentName}]" value="i" />
					</h5>
					<p class="info_label">Tower: <span>
						<xsl:if test="$param/hiscolors/Tower != ''">
							<xsl:attribute name="style"><xsl:value-of select="$param/hiscolors/Tower"/></xsl:attribute>
						</xsl:if>
						<xsl:value-of select="$param/HisTower"/>
						</span>
					</p>
					<p class="info_label">Wall: <span>
						<xsl:if test="$param/hiscolors/Wall != ''">
							<xsl:attribute name="style"><xsl:value-of select="$param/hiscolors/Wall"/></xsl:attribute>
						</xsl:if>
						<xsl:value-of select="$param/HisWall"/>
						</span>
					</p>
				</td>
			</xsl:when>
			<xsl:otherwise>
				<td class="stats" align="right">
					<div>
						<p>
							<xsl:if test="$param/hiscolors/Quarry != ''">
								<xsl:attribute name="style"><xsl:value-of select="$param/hiscolors/Quarry"/></xsl:attribute>
							</xsl:if>
							<xsl:value-of select="$param/HisQuarry"/>
						</p>
						<p>
							<xsl:if test="$param/hiscolors/Bricks != ''">
								<xsl:attribute name="style"><xsl:value-of select="$param/hiscolors/Bricks"/></xsl:attribute>
							</xsl:if>
							<xsl:value-of select="$param/HisBricks"/>
						</p>
					</div>
					<div>
						<p>
							<xsl:if test="$param/hiscolors/Magic != ''">
								<xsl:attribute name="style"><xsl:value-of select="$param/hiscolors/Magic"/></xsl:attribute>
							</xsl:if>
							<xsl:value-of select="$param/HisMagic"/>
						</p>
						<p>
							<xsl:if test="$param/hiscolors/Gems != ''">
								<xsl:attribute name="style"><xsl:value-of select="$param/hiscolors/Gems"/></xsl:attribute>
							</xsl:if>
							<xsl:value-of select="$param/HisGems"/>
						</p>
					</div>
					<div>
						<p>
							<xsl:if test="$param/hiscolors/Dungeons != ''">
								<xsl:attribute name="style"><xsl:value-of select="$param/hiscolors/Dungeons"/></xsl:attribute>
							</xsl:if>
							<xsl:value-of select="$param/HisDungeons"/>
						</p>
						<p>
							<xsl:if test="$param/hiscolors/Recruits != ''">
								<xsl:attribute name="style"><xsl:value-of select="$param/hiscolors/Recruits"/></xsl:attribute>
							</xsl:if>
							<xsl:value-of select="$param/HisRecruits"/>
						</p>
					</div>
					<h5>
						<img width="18px" height="12px" src="img/flags/{$param/hiscountry}.gif" alt="country flag" />
						<xsl:value-of select="$param/OpponentName"/>
						<input class="details" type="submit" name="user_details[{$param/OpponentName}]" value="i" />
					</h5>
					<p class="info_label">Tower: <span>
						<xsl:if test="$param/hiscolors/Tower != ''">
							<xsl:attribute name="style"><xsl:value-of select="$param/hiscolors/Tower"/></xsl:attribute>
						</xsl:if>
						<xsl:value-of select="$param/HisTower"/>
						</span>
					</p>
					<p class="info_label">Wall: <span>
						<xsl:if test="$param/hiscolors/Wall != ''">
							<xsl:attribute name="style"><xsl:value-of select="$param/hiscolors/Wall"/></xsl:attribute>
						</xsl:if>
						<xsl:value-of select="$param/HisWall"/>
						</span>
					</p>
				</td>
			</xsl:otherwise>
		</xsl:choose>
		<!-- end his empire info -->
	</tr>
	<!-- end status -->

	<!-- begin tokens -->
	<tr>

	<!-- begin my tokens -->
		<xsl:for-each select="$param/MyTokens/*">
			<td>
				<xsl:if test="Name != 'none'">
					<p class="token_counter">
						<xsl:if test="Change &lt; 0">
							<xsl:attribute name="style">color: lime</xsl:attribute>
						</xsl:if>
						<xsl:value-of select="Name"/>
						<span>
							<xsl:choose>
								<xsl:when test="Change &gt; 0">
									<xsl:attribute name="style">color: lime</xsl:attribute>
								</xsl:when>
								<xsl:when test="Change &lt; 0">
									<xsl:attribute name="style">color: orange</xsl:attribute>
								</xsl:when>
							</xsl:choose>
							<xsl:value-of select="Value"/>
						</span>
					</p>
				</xsl:if>
			</td>
		</xsl:for-each>	
	<!-- end my tokens -->

		<td></td>
		<td></td>

	<!-- begin his tokens -->
		<xsl:for-each select="$param/HisTokens/*">
			<td>
				<xsl:if test="Name != 'none'">
					<p class="token_counter">
						<xsl:if test="Change &lt; 0">
							<xsl:attribute name="style">color: lime</xsl:attribute>
						</xsl:if>
						<xsl:value-of select="Name"/>
						<span>
							<xsl:choose>
								<xsl:when test="Change &gt; 0">
									<xsl:attribute name="style">color: lime</xsl:attribute>
								</xsl:when>
								<xsl:when test="Change &lt; 0">
									<xsl:attribute name="style">color: orange</xsl:attribute>
								</xsl:when>
							</xsl:choose>
							<xsl:value-of select="Value"/>
						</span>
					</p>
				</xsl:if>
			</td>
		</xsl:for-each>
	<!-- end his tokens -->

	</tr>
	<!-- end tokens -->

	<!-- begin his cards -->
	<tr valign="top">
		<xsl:for-each select="$param/HisHand/*">
			<td align="center">
				<!--  display new card indicator, if set -->
				<xsl:if test="NewCard = 'yes'">
					<p class="newcard_flag">NEW CARD</p>
				</xsl:if>

				<!-- display card -->
				<xsl:copy-of select="am:cardstring(Data, $param/c_img, $param/c_keywords, $param/c_text, $param/c_oldlook)" />
			</td>
		</xsl:for-each>
	</tr>
	<!-- end his cards -->

	<!-- begin chatboard -->
	<tr>
		<td colspan="8" align="center">
			<div class="chatsection">
				<!-- avatars normal version -->
				<xsl:if test="($param/display_avatar = 'yes') and ($param/correction = 'no')">
					<img style="float: left;  margin: 0.5ex 0ex 0ex 0ex;" class="avatar" height="60px" width="60px" src="img/avatars/{$param/myavatar}" alt="avatar" />
					<img style="float: right; margin: 0.5ex 0ex 0ex 0ex;" class="avatar" height="60px" width="60px" src="img/avatars/{$param/hisavatar}" alt="avatar" />
				</xsl:if>

				<!-- message list -->
				<xsl:if test="count($param/messagelist/*) &gt; 0">
					<div class="chatbox">
						<xsl:for-each select="$param/messagelist/*">
							<p>
								<span><xsl:value-of select="am:datetime(Timestamp, $param/timezone)"/></span>
								<span>
									<xsl:choose>
										<xsl:when test="Name = $param/PlayerName">
											<xsl:attribute name="style">color: <xsl:value-of select="am:color('LightBlue')"/></xsl:attribute>
										</xsl:when>
										<xsl:when test="Name = $param/OpponentName">
											<xsl:attribute name="style">color: <xsl:value-of select="am:color('LightGreen')"/></xsl:attribute>
										</xsl:when>
										<xsl:otherwise>
											<xsl:attribute name="style">color: red</xsl:attribute>
										</xsl:otherwise>
									</xsl:choose>
									<xsl:value-of select="Name"/> : 
								</span>
								<span><xsl:value-of select="Message"/></span>
							</p>
						</xsl:for-each>
					</div>
				</xsl:if>

				<!-- avatars corrected version -->
				<xsl:if test="($param/display_avatar = 'yes') and ($param/correction = 'yes')">
					<img style="float: left;  margin: 0.5ex 0ex 0ex 0ex;" class="avatar" height="60px" width="60px" src="img/avatars/{$param/myavatar}" alt="avatar" />
					<img style="float: right; margin: 0.5ex 0ex 0ex 0ex;" class="avatar" height="60px" width="60px" src="img/avatars/{$param/hisavatar}" alt="avatar" />
				</xsl:if>

				<!-- chatboard inputs -->
				<xsl:if test="$param/chat = 'yes'">
					<div id="chat_inputs">
						<input class="text_data chatboard" type="text" name="ChatMessage" size="115" maxlength="300" style="font-size: normal; margin-right: 2ex;" tabindex="1" accesskey="a" />
						<input type="submit" name="send_message" value="Send message" tabindex="2" accesskey="s" />
					</div>
				</xsl:if>

				<div style="clear: both"></div>
			</div>
		</td>
	</tr>
	<!-- end chatboard -->

	</table>

</xsl:template>


</xsl:stylesheet>
