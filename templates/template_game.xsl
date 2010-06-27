<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
                xmlns="http://www.w3.org/1999/xhtml"
                xmlns:am="http://arcomage.netvor.sk"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:str="http://exslt.org/strings"
                extension-element-prefixes="str">
<xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes" doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" />


<xsl:template match="section[. = 'Game']">
	<xsl:variable name="param" select="$params/game" />
	<!-- scrolls chatbox to bottom if reverse chatorder setting is active -->
	<xsl:if test="$param/reverse_chat = 'yes'">
		<xsl:element name="script">
			<xsl:attribute name="type">text/javascript</xsl:attribute>
			<xsl:text>$(document).ready(function() { $(".chatbox").scrollTo('max'); });</xsl:text>
		</xsl:element>
	</xsl:if>

	<div id="game">

	<!-- remember the current location across pages -->
	<div><input type="hidden" name="CurrentGame" value="{$param/CurrentGame}"/></div>

	<xsl:choose>
		<!-- display supportive information -->
		<xsl:when test="$param/GameState = 'in progress'">
			<p class="information_line info">Round <xsl:value-of select="$param/Round"/></p>
		</xsl:when>
		<!-- finished -->
		<xsl:otherwise>
			<p class="information_line info">
				<xsl:choose>
					<xsl:when test="$param/Winner = $param/PlayerName">You have won in round <xsl:value-of select="$param/Round"/>. <xsl:value-of select="$param/Outcome"/>.</xsl:when>
					<xsl:when test="$param/Winner = $param/OpponentName"><xsl:value-of select="$param/Winner"/> has won in round <xsl:value-of select="$param/Round"/>. <xsl:value-of select="$param/Outcome"/>.</xsl:when>
					<xsl:when test="($param/Winner = '') and ($param/EndType = 'Draw')">Game ended in a draw in round <xsl:value-of select="$param/Round"/>.</xsl:when>
					<xsl:when test="($param/Winner = '') and ($param/EndType = 'Abort')">Game was aborted in round <xsl:value-of select="$param/Round"/>.</xsl:when>
				</xsl:choose>
			</p>
		</xsl:otherwise>
	</xsl:choose>

	<!-- four rows: your cards, messages and buttons, the current status, opponent's cards -->
	<table class="centered" cellpadding="0" cellspacing="0">

	<xsl:if test="$param/Background != 0">
		<xsl:attribute name="style">background-image: url('img/backgrounds/bg_<xsl:value-of select="$param/Background"/>.jpg'); background-position: center center; background-repeat: no-repeat;</xsl:attribute>
	</xsl:if>

	<!-- begin your cards -->
	<tr valign="top" class="hand">
		<xsl:for-each select="$param/MyHand/*">
			<td align="center">
				<!--  display discard button -->
				<xsl:if test="($param/GameState = 'in progress') and ($param/Current = $param/PlayerName)">
					<input type="submit" name="discard_card[{position()}]" value="Discard"/>
				</xsl:if>

				<!--  display card flags, if set -->
				<xsl:choose>
					<xsl:when test="$param/HiddenCards = 'yes' and Revealed = 'yes'">
						<div class="flag_space">
							<xsl:if test="NewCard = 'yes'">
								<span class="newcard">NEW</span>
							</xsl:if>
							<img src="img/revealed.png" class="revealed" width="20px" height="14px" alt="revealed" title="Revealed" />
						</div>
						<div class="clear_floats"></div>
					</xsl:when>
					<xsl:when test="NewCard = 'yes'">
						<p class="flag">NEW CARD</p>
					</xsl:when>
				</xsl:choose>

				<!-- display card -->
				<xsl:copy-of select="am:cardstring(Data, $param/c_img, $param/c_keywords, $param/c_text, $param/c_oldlook)" />
				
				<!-- play button and card modes -->
				<xsl:if test="Playable = 'yes'">
					<input type="submit" name="play_card[{position()}]" value="Play"/>
					<xsl:if test="Modes &gt; 0">
						<select name="card_mode[{position()}]" class="card_modes" size="1">
							<xsl:for-each select="str:split(am:numbers(1, Modes), ',')">
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
			<input type="submit" name="active_game" value="Next game" />
		</td>
		<!-- end quick game switching menu -->
		<td>
			<!-- 'refresh' button -->
			<input type="submit" name="Refresh[{am:urlencode($param/current)}]" value="Refresh" accesskey="w" />
		</td>

		<!-- begin game state indicator -->
		<xsl:choose>
			<xsl:when test="$param/GameState = 'in progress'">
				<td colspan="2">
					<p class="info_label">
						<xsl:choose>
							<xsl:when test="$param/Current = $param/PlayerName">
								<span class="player"><xsl:text>It is your turn</xsl:text></span>
							</xsl:when>
							<xsl:otherwise>
								<span class="opponent"><xsl:text>It is </xsl:text><xsl:value-of select="$param/OpponentName"/><xsl:text>'s turn</xsl:text></span>
							</xsl:otherwise>
						</xsl:choose>
					</p>
				</td>
				<td></td>
				<td>
					<input type="submit" name="view_deck" value="Deck" />
					<input type="submit" name="view_note" value="Note" >
						<xsl:if test="$param/has_note = 'yes'">
							<xsl:attribute name="class">marked_button</xsl:attribute>
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
						<xsl:value-of select="$param/MyQuarry"/>
						</span>
						<xsl:if test="$param/mychanges/Quarry != 0">
							<span class="changes"><xsl:value-of select="$param/mychanges/Quarry"/></span>
						</xsl:if>
					</div>
					<div>Bricks: <span>
						<xsl:value-of select="$param/MyBricks"/>
						</span>
						<xsl:if test="$param/mychanges/Bricks != 0">
							<span class="changes"><xsl:value-of select="$param/mychanges/Bricks"/></span>
						</xsl:if>
					</div>
					<div>Magic: <span>
						<xsl:value-of select="$param/MyMagic"/>
						</span>
						<xsl:if test="$param/mychanges/Magic != 0">
							<span class="changes"><xsl:value-of select="$param/mychanges/Magic"/></span>
						</xsl:if>
					</div>
					<div>Gems: <span>
						<xsl:value-of select="$param/MyGems"/>
						</span>
						<xsl:if test="$param/mychanges/Gems != 0">
							<span class="changes"><xsl:value-of select="$param/mychanges/Gems"/></span>
						</xsl:if>
					</div>
					<div>Dungeon: <span>
						<xsl:value-of select="$param/MyDungeons"/>
						</span>
						<xsl:if test="$param/mychanges/Dungeons != 0">
							<span class="changes"><xsl:value-of select="$param/mychanges/Dungeons"/></span>
						</xsl:if>
					</div>
					<div>Recruits: <span>
						<xsl:value-of select="$param/MyRecruits"/>
						</span>
						<xsl:if test="$param/mychanges/Recruits != 0">
							<span class="changes"><xsl:value-of select="$param/mychanges/Recruits"/></span>
						</xsl:if>
					</div>
					<h5>
						<xsl:value-of select="$param/PlayerName"/>
						<img width="18px" height="12px" src="img/flags/{$param/mycountry}.gif" alt="country flag" class="icon" title="{$param/mycountry}" />
					</h5>
					<p class="info_label">Tower: <span>
						<xsl:value-of select="$param/MyTower"/>
						</span>
						<xsl:if test="$param/mychanges/Tower != 0">
							<span class="changes"><xsl:value-of select="$param/mychanges/Tower"/></span>
						</xsl:if>
					</p>
					<p class="info_label">Wall: <span>
						<xsl:value-of select="$param/MyWall"/>
						</span>
						<xsl:if test="$param/mychanges/Wall != 0">
							<span class="changes"><xsl:value-of select="$param/mychanges/Wall"/></span>
						</xsl:if>
					</p>
				</td>
			</xsl:when>
			<xsl:otherwise>
				<td class="stats">
					<div>
						<p class="facility">
							<xsl:value-of select="$param/MyQuarry"/>
							<xsl:if test="$param/mychanges/Quarry != 0">
								<span class="changes"><xsl:value-of select="$param/mychanges/Quarry"/></span>
							</xsl:if>
						</p>
						<p class="resource">
							<xsl:value-of select="$param/MyBricks"/>
							<xsl:if test="$param/mychanges/Bricks != 0">
								<span class="changes"><xsl:value-of select="$param/mychanges/Bricks"/></span>
							</xsl:if>
						</p>
					</div>
					<div>
						<p class="facility">
							<xsl:value-of select="$param/MyMagic"/>
							<xsl:if test="$param/mychanges/Magic != 0">
								<span class="changes"><xsl:value-of select="$param/mychanges/Magic"/></span>
							</xsl:if>
						</p>
						<p class="resource">
							<xsl:value-of select="$param/MyGems"/>
							<xsl:if test="$param/mychanges/Gems != 0">
								<span class="changes"><xsl:value-of select="$param/mychanges/Gems"/></span>
							</xsl:if>
						</p>
					</div>
					<div>
						<p class="facility">
							<xsl:value-of select="$param/MyDungeons"/>
							<xsl:if test="$param/mychanges/Dungeons != 0">
								<span class="changes"><xsl:value-of select="$param/mychanges/Dungeons"/></span>
							</xsl:if>
						</p>
						<p class="resource">
							<xsl:value-of select="$param/MyRecruits"/>
							<xsl:if test="$param/mychanges/Recruits != 0">
								<span class="changes"><xsl:value-of select="$param/mychanges/Recruits"/></span>
							</xsl:if>
						</p>
					</div>
					<h5>
						<xsl:value-of select="$param/PlayerName"/>
						<img width="18px" height="12px" src="img/flags/{$param/mycountry}.gif" alt="country flag" class="icon" title="{$param/mycountry}" />
					</h5>
					<p class="info_label">Tower: <span>
						<xsl:value-of select="$param/MyTower"/>
						</span>
						<xsl:if test="$param/mychanges/Tower != 0">
							<span class="changes"><xsl:value-of select="$param/mychanges/Tower"/></span>
						</xsl:if>
					</p>
					<p class="info_label">Wall: <span>
						<xsl:value-of select="$param/MyWall"/>
						</span>
						<xsl:if test="$param/mychanges/Wall != 0">
							<span class="changes"><xsl:value-of select="$param/mychanges/Wall"/></span>
						</xsl:if>
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
											<xsl:when test="$param/MyTower = $param/max_tower">
												<xsl:attribute name="src">img/victory_red.png</xsl:attribute>
												<xsl:attribute name="height">114px</xsl:attribute>
											</xsl:when>
											<xsl:otherwise>
												<xsl:attribute name="src">img/towera_red.png</xsl:attribute>
												<xsl:attribute name="height">91px</xsl:attribute>
											</xsl:otherwise>
										</xsl:choose>
									</img>
									<div class="towerbody" style="margin-left: 14px; height: {170 * $param/MyTower div $param/max_tower}px;"></div>
								</div>
							</td>
							<td valign="bottom">
								<xsl:if test="$param/MyWall &gt; 0">
									<div>
										<img src="img/korunka.png" width="19px" height="11px" style="display:block" alt="" />
										<div class="wallbody" style="height: {270 * $param/MyWall div $param/max_wall}px;"></div>
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
										<div class="wallbody" style="height: {270 * $param/HisWall div $param/max_wall}px;"></div>
									</div>
								</xsl:if>
							</td>
							<td valign="bottom">
								<div style="margin: 0ex 1ex 0ex 1ex;">
									<img width="65px" style="display:block" alt="" >
										<xsl:choose>
											<xsl:when test="$param/HisTower = $param/max_tower">
												<xsl:attribute name="src">img/victory_blue.png</xsl:attribute>
												<xsl:attribute name="height">114px</xsl:attribute>
											</xsl:when>
											<xsl:otherwise>
												<xsl:attribute name="src">img/towera_blue.png</xsl:attribute>
												<xsl:attribute name="height">91px</xsl:attribute>
											</xsl:otherwise>
										</xsl:choose>
									</img>
									<div class="towerbody" style="margin-left: 14px; height: {170 * $param/HisTower div $param/max_tower}px;"></div>
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
						<xsl:value-of select="$param/HisQuarry"/>
						</span>
						<xsl:if test="$param/hischanges/Quarry != 0">
							<span class="changes"><xsl:value-of select="$param/hischanges/Quarry"/></span>
						</xsl:if>
					</div>
					<div>Bricks: <span>
						<xsl:value-of select="$param/HisBricks"/>
						</span>
						<xsl:if test="$param/hischanges/Bricks != 0">
							<span class="changes"><xsl:value-of select="$param/hischanges/Bricks"/></span>
						</xsl:if>
					</div>
					<div>Magic: <span>
						<xsl:value-of select="$param/HisMagic"/>
						</span>
						<xsl:if test="$param/hischanges/Magic != 0">
							<span class="changes"><xsl:value-of select="$param/hischanges/Magic"/></span>
						</xsl:if>
					</div>
					<div>Gems: <span>
						<xsl:value-of select="$param/HisGems"/>
						</span>
						<xsl:if test="$param/hischanges/Gems != 0">
							<span class="changes"><xsl:value-of select="$param/hischanges/Gems"/></span>
						</xsl:if>
					</div>
					<div>Dungeon: <span>
						<xsl:value-of select="$param/HisDungeons"/>
						</span>
						<xsl:if test="$param/hischanges/Dungeons != 0">
							<span class="changes"><xsl:value-of select="$param/hischanges/Dungeons"/></span>
						</xsl:if>
					</div>
					<div>Recruits: <span>
						<xsl:value-of select="$param/HisRecruits"/>
						</span>
						<xsl:if test="$param/hischanges/Recruits != 0">
							<span class="changes"><xsl:value-of select="$param/hischanges/Recruits"/></span>
						</xsl:if>
					</div>
					<h5>
						<img width="18px" height="12px" src="img/flags/{$param/hiscountry}.gif" alt="country flag" class="icon" title="{$param/hiscountry}" />
						<xsl:value-of select="$param/OpponentName"/>
						<input class="small_button" type="submit" name="user_details[{$param/OpponentName}]" value="i" />
					</h5>
					<p class="info_label">Tower: <span>
						<xsl:value-of select="$param/HisTower"/>
						</span>
						<xsl:if test="$param/hischanges/Tower != 0">
							<span class="changes"><xsl:value-of select="$param/hischanges/Tower"/></span>
						</xsl:if>
					</p>
					<p class="info_label">Wall: <span>
						<xsl:value-of select="$param/HisWall"/>
						</span>
						<xsl:if test="$param/hischanges/Wall != 0">
							<span class="changes"><xsl:value-of select="$param/hischanges/Wall"/></span>
						</xsl:if>
					</p>
				</td>
			</xsl:when>
			<xsl:otherwise>
				<td class="stats" align="right">
					<div>
						<p class="facility">
							<xsl:value-of select="$param/HisQuarry"/>
							<xsl:if test="$param/hischanges/Quarry != 0">
								<span class="changes"><xsl:value-of select="$param/hischanges/Quarry"/></span>
							</xsl:if>
						</p>
						<p class="resource">
							<xsl:value-of select="$param/HisBricks"/>
							<xsl:if test="$param/hischanges/Bricks != 0">
								<span class="changes"><xsl:value-of select="$param/hischanges/Bricks"/></span>
							</xsl:if>
						</p>
					</div>
					<div>
						<p class="facility">
							<xsl:value-of select="$param/HisMagic"/>
							<xsl:if test="$param/hischanges/Magic != 0">
								<span class="changes"><xsl:value-of select="$param/hischanges/Magic"/></span>
							</xsl:if>
						</p>
						<p class="resource">
							<xsl:value-of select="$param/HisGems"/>
							<xsl:if test="$param/hischanges/Gems != 0">
								<span class="changes"><xsl:value-of select="$param/hischanges/Gems"/></span>
							</xsl:if>
						</p>
					</div>
					<div>
						<p class="facility">
							<xsl:value-of select="$param/HisDungeons"/>
							<xsl:if test="$param/hischanges/Dungeons != 0">
								<span class="changes"><xsl:value-of select="$param/hischanges/Dungeons"/></span>
							</xsl:if>
						</p>
						<p class="resource">
							<xsl:value-of select="$param/HisRecruits"/>
							<xsl:if test="$param/hischanges/Recruits != 0">
								<span class="changes"><xsl:value-of select="$param/hischanges/Recruits"/></span>
							</xsl:if>
						</p>
					</div>
					<h5>
						<xsl:if test="$param/opp_isOnline = 'yes'">
							<xsl:attribute name="class">player</xsl:attribute>
						</xsl:if>
						<img width="18px" height="12px" src="img/flags/{$param/hiscountry}.gif" alt="country flag" class="icon" title="{$param/hiscountry}" />
						<xsl:value-of select="$param/OpponentName"/>
						<input class="small_button" type="submit" name="user_details[{$param/OpponentName}]" value="i" />
					</h5>
					<p class="info_label">Tower: <span>
						<xsl:value-of select="$param/HisTower"/>
						</span>
						<xsl:if test="$param/hischanges/Tower != 0">
							<span class="changes"><xsl:value-of select="$param/hischanges/Tower"/></span>
						</xsl:if>
					</p>
					<p class="info_label">Wall: <span>
						<xsl:value-of select="$param/HisWall"/>
						</span>
						<xsl:if test="$param/hischanges/Wall != 0">
							<span class="changes"><xsl:value-of select="$param/hischanges/Wall"/></span>
						</xsl:if>
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
	<!-- end my tokens -->

		<td class="game_mode_flags">
			<!-- game mode flags -->
			<xsl:if test="$param/HiddenCards = 'yes'">
				<img src="img/blind.png" width="20px" height="14px" alt="hidden cards" title="Hidden cards" class="icon" />
			</xsl:if>
			<xsl:if test="$param/FriendlyPlay = 'yes'">
				<img src="img/friendly_play.png" width="20px" height="14px" alt="friendly play" title="Friendly play" class="icon" />
			</xsl:if>
		</td>
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
	<!-- end his tokens -->

	</tr>
	<!-- end tokens -->

	<!-- begin his cards -->
	<tr valign="top" class="hand">
		<xsl:for-each select="$param/HisHand/*">
			<td align="center">
				<!--  display new card indicator, if set -->
				<xsl:if test="NewCard = 'yes'">
					<p class="flag">NEW CARD</p>
				</xsl:if>

				<!-- display card -->
				<xsl:choose>
					<xsl:when test="($param/HiddenCards = 'yes') and (Revealed = 'no') and ($param/GameState = 'in progress')">
						<div class="hidden_card"></div>
					</xsl:when>
					<xsl:otherwise>
						<xsl:copy-of select="am:cardstring(Data, $param/c_img, $param/c_keywords, $param/c_text, $param/c_oldlook)" />
					</xsl:otherwise>
				</xsl:choose>
			</td>
		</xsl:for-each>
	</tr>
	<!-- end his cards -->

	</table>

	<!-- begin chatboard -->
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
									<xsl:attribute name="class">chatbox_player</xsl:attribute>
								</xsl:when>
								<xsl:when test="Name = $param/OpponentName">
									<xsl:attribute name="class">chatbox_opponent</xsl:attribute>
								</xsl:when>
								<xsl:otherwise>
									<xsl:attribute name="class">chatbox_system</xsl:attribute>
								</xsl:otherwise>
							</xsl:choose>
							<xsl:value-of select="Name"/>
							<xsl:text> : </xsl:text>
						</span>
						<span><xsl:copy-of select="am:textencode(Message)"/></span>
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
				<input class="text_data chatboard" type="text" name="ChatMessage" size="122" maxlength="300" tabindex="1" accesskey="a" />
				<input type="submit" name="send_message" value="Send" tabindex="2" accesskey="s" />
			</div>
		</xsl:if>

		<div style="clear: both"></div>
	</div>
	<!-- end chatboard -->

	</div>

</xsl:template>


</xsl:stylesheet>
