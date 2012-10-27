<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
                xmlns="http://www.w3.org/1999/xhtml"
                xmlns:am="http://arcomage.netvor.sk"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:exsl="http://exslt.org/common"
                xmlns:php="http://php.net/xsl"
                xmlns:str="http://exslt.org/strings"
                extension-element-prefixes="exsl php str">
<xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes" doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" />

<!-- includes -->
<xsl:include href="template_main.xsl" />


<xsl:template match="section[. = 'Games']">
	<xsl:variable name="param" select="$params/games" />
	<xsl:variable name="activedecks" select="count($param/decks/*)" />
	<xsl:variable name="list" select="$param/list" />
	<xsl:variable name="timeout_values">
		<value name="0"     text="unlimited"  />
		<value name="86400" text="1 day"      />
		<value name="43200" text="12 hours"   />
		<value name="21600" text="6 hours"    />
		<value name="10800" text="3 hours"    />
		<value name="3600"  text="1 hour"     />
		<value name="1800"  text="30 minutes" />
		<value name="300"   text="5 minutes"  />
	</xsl:variable>

	<div id="games">

	<!-- autorefresh -->
	<xsl:if test="$param/autorefresh &gt; 0">
		<input type="hidden" name="Autorefresh" value="{$param/autorefresh}"/>
	</xsl:if>

	<!-- begin active games list -->
	<div id="active_games" class="skin_label">
	<h3>Active games</h3>
	<xsl:choose>
		<xsl:when test="count($list/*) &gt; 0">
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
					<th><p>Modes</p></th>
					<th><p>Timeout</p></th>
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
								<a class="profile" href="{php:functionString('makeurl', 'Players_details', 'Profile', opponent)}">
									<!-- rename opponent to actual AI name in case of AI challenge -->
									<xsl:choose>
										<xsl:when test="ai != ''"><xsl:value-of select="ai"/></xsl:when>
										<xsl:otherwise><xsl:value-of select="opponent"/></xsl:otherwise>
									</xsl:choose>
								</a>
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
							<p>
								<xsl:if test="contains(game_modes, 'HiddenCards')">
									<img class="icon" width="20px" height="14px" src="img/blind.png" alt="Hidden cards" title="Hidden cards" />
								</xsl:if>
								<xsl:if test="contains(game_modes, 'FriendlyPlay')">
									<img class="icon" width="20px" height="14px" src="img/friendly_play.png" alt="Friendly play" title="Friendly play" />
								</xsl:if>
								<xsl:if test="contains(game_modes, 'LongMode')">
									<img class="icon" width="20px" height="14px" src="img/long_mode.png" alt="Long mode" title="Long mode" />
								</xsl:if>
								<xsl:if test="contains(game_modes, 'AIMode')">
									<img class="icon" width="20px" height="14px" src="img/ai_mode.png" alt="AI mode" title="AI mode" />
								</xsl:if>
								<xsl:if test="ai != ''">
									<img class="icon" width="20px" height="14px" src="img/ai_challenge.png" alt="AI challenge - {ai}" title="AI challenge - {ai}" />
								</xsl:if>
							</p>
						</td>
						<td>
							<p>
								<xsl:variable name="timeout" select="timeout" />
								<xsl:value-of select="exsl:node-set($timeout_values)/*[@name = $timeout]/@text"/>
							</p>
						</td>
						<td>
							<xsl:choose>
								<xsl:when test="gamestate = 'in progress' and isdead = 'yes'">
									<p class="ended_game" >Can be aborted</p>
								</xsl:when>
								<xsl:when test="gamestate = 'in progress' and finishable = 'yes'">
									<p class="ended_game" >Can be finished</p>
								</xsl:when>
								<xsl:when test="gamestate != 'in progress'">
									<p class="ended_game">Game has ended</p>
								</xsl:when>
							</xsl:choose>
						</td>
						<td>
							<p>
								<a class="button" href="{php:functionString('makeurl', 'Games_details', 'CurrentGame', gameid)}">
									<xsl:if test="ready = 'yes'">
										<xsl:attribute name="class">button marked_button</xsl:attribute>
									</xsl:if>
									<xsl:text>&gt;</xsl:text>
								</a>
							</p>
						</td>
					</tr>
				</xsl:for-each>
			</table>
	</xsl:when>
	<xsl:otherwise>
		<p class="information_line warning">You have no active games.</p>
	</xsl:otherwise>
	</xsl:choose>
	</div>
	<!-- end active games list -->

	<!-- begin hosted games list -->
	<div id="hosted_games" class="skin_label">
	<h3>Hosted games</h3>

	<!-- warning messages -->
	<xsl:if test="$activedecks = 0">
		<p class="information_line warning">You need at least one ready deck to host/join a game.</p>
	</xsl:if>
	<xsl:if test="$param/free_slots = 0">
		<p class="information_line warning">You cannot host/enter any more games.</p>
	</xsl:if>

	<!-- subsection navigation -->
	<p>
		<a class="button" href="{php:functionString('makeurl', 'Games', 'subsection', 'free_games')}">
			<xsl:if test="$param/current_subsection = 'free_games'">
				<xsl:attribute name="class">button pushed</xsl:attribute>
			</xsl:if>
			<xsl:text>Available games</xsl:text>
		</a>

		<a class="button" href="{php:functionString('makeurl', 'Games', 'subsection', 'hosted_games')}">
			<xsl:if test="$param/current_subsection = 'hosted_games'">
				<xsl:attribute name="class">button pushed</xsl:attribute>
			</xsl:if>
			<xsl:text>My games</xsl:text>
		</a>

		<a class="button" href="{php:functionString('makeurl', 'Games', 'subsection', 'ai_games')}">
			<xsl:if test="$param/current_subsection = 'ai_games'">
				<xsl:attribute name="class">button pushed</xsl:attribute>
			</xsl:if>
			<xsl:text>AI games</xsl:text>
		</a>
	</p>

	<xsl:choose>
		<!-- begin subsection free games -->
		<xsl:when test="$param/current_subsection = 'free_games'">

		<!-- begin filters -->
		<p class="filters">
			<xsl:variable name="mode_options">
				<value name="ignore"  value="none"    />
				<value name="include" value="include" />
				<value name="exclude" value="exclude" />
			</xsl:variable>

			<!-- hidden cards filter -->
			<img class="icon" width="20px" height="14px" src="img/blind.png" alt="Hidden cards" title="Hidden cards" />
			<xsl:copy-of select="am:htmlSelectBox('HiddenCards', $param/HiddenCards, $mode_options, '')"/>

			<!-- friendly game filter -->
			<img class="icon" width="20px" height="14px" src="img/friendly_play.png" alt="Friendly play" title="Friendly play" />
			<xsl:copy-of select="am:htmlSelectBox('FriendlyPlay', $param/FriendlyPlay, $mode_options, '')"/>

			<!-- friendly game filter -->
			<img class="icon" width="20px" height="14px" src="img/long_mode.png" alt="Long mode" title="Long mode" />
			<xsl:copy-of select="am:htmlSelectBox('LongMode', $param/LongMode, $mode_options, '')"/>

			<button type="submit" name="filter_hosted_games">Apply filters</button>
		</p>
		<!-- end filters -->

		<!-- selected deck -->
		<xsl:if test="$activedecks &gt; 0 and $param/free_slots &gt; 0">
			<p class="misc">
				<span>Select deck</span>
				<select name="SelectedDeck" size="1">
					<xsl:if test="$param/RandomDeck = 'yes'">
						<option value="{am:urlencode($param/random_deck)}">select random</option>
					</xsl:if>
					<xsl:for-each select="$param/decks/*">
						<option value="{am:urlencode(DeckID)}"><xsl:value-of select="Deckname"/></option>
					</xsl:for-each>
				</select>
				<button type="submit" name="quick_game">Quick game vs AI</button>
			</p>
		</xsl:if>

		<!-- free games list -->
		<xsl:choose>
			<xsl:when test="count($param/free_games/*) &gt; 0">
				<table cellspacing="0" class="skin_text">
					<tr>
						<th></th>
						<th><p>Opponent</p></th>
						<th><p>Created</p></th>
						<th><p>Modes</p></th>
						<th><p>Timeout</p></th>
						<th></th>
					</tr>
					<xsl:for-each select="$param/free_games/*">
						<tr class="table_row">
							<td>
								<p class="flags">
									<xsl:if test="status != 'none'">
										<img class="icon" width="20px" height="14px" src="img/{status}.png" alt="status flag" title="{status}" />
									</xsl:if>
								</p>
							</td>
							<td>
								<p>
									<xsl:if test="active = 'yes'">
										<xsl:attribute name="class">p_online</xsl:attribute>
									</xsl:if>
									<a class="profile" href="{php:functionString('makeurl', 'Players_details', 'Profile', opponent)}"><xsl:value-of select="opponent"/></a>
								</p>
							</td>
							<td><p><xsl:value-of select="am:datetime(gameaction, $param/timezone)"/></p></td>
							<td>
								<p>
									<xsl:if test="contains(game_modes, 'HiddenCards')">
										<img class="icon" width="20px" height="14px" src="img/blind.png" alt="Hidden cards" title="Hidden cards" />
									</xsl:if>
									<xsl:if test="contains(game_modes, 'FriendlyPlay')">
										<img class="icon" width="20px" height="14px" src="img/friendly_play.png" alt="Friendly play" title="Friendly play" />
									</xsl:if>
									<xsl:if test="contains(game_modes, 'LongMode')">
										<img class="icon" width="20px" height="14px" src="img/long_mode.png" alt="Long mode" title="Long mode" />
									</xsl:if>
								</p>
							</td>
							<td>
								<p>
									<xsl:variable name="timeout" select="timeout" />
									<xsl:value-of select="exsl:node-set($timeout_values)/*[@name = $timeout]/@text"/>
								</p>
							</td>
							<td>
								<xsl:if test="$activedecks &gt; 0 and $param/free_slots &gt; 0">
									<p><button type="submit" name="join_game" value="{gameid}">Join</button></p>
								</xsl:if>
							</td>
						</tr>
					</xsl:for-each>
				</table>
			</xsl:when>
			<xsl:otherwise>
				<p class="information_line warning">There are no hosted games.</p>
			</xsl:otherwise>
		</xsl:choose>

		</xsl:when>
		<!-- end subsection free games -->

		<!-- begin subsection hosted games -->
		<xsl:when test="$param/current_subsection = 'hosted_games'">

		<!-- host new game interface -->
		<xsl:if test="$activedecks &gt; 0 and $param/free_slots &gt; 0">
			<p class="misc">
				<img class="icon" width="20px" height="14px" src="img/blind.png" alt="Hidden cards" title="Hidden cards" />
				<input type="checkbox" name="HiddenMode">
					<xsl:if test="$param/BlindFlag = 'yes'"><xsl:attribute name="checked">checked</xsl:attribute></xsl:if>
				</input>
				<img class="icon" width="20px" height="14px" src="img/friendly_play.png" alt="Friendly play" title="Friendly play" />
				<input type="checkbox" name="FriendlyMode">
					<xsl:if test="$param/FriendlyFlag = 'yes'"><xsl:attribute name="checked">checked</xsl:attribute></xsl:if>
				</input>
				<img class="icon" width="20px" height="14px" src="img/long_mode.png" alt="Long mode" title="Long mode" />
				<input type="checkbox" name="LongMode">
					<xsl:if test="$param/LongFlag = 'yes'"><xsl:attribute name="checked">checked</xsl:attribute></xsl:if>
				</input>
				<select name="Timeout" title="Turn timeout">
					<xsl:for-each select="exsl:node-set($timeout_values)/*">
						<option value="{@name}">
							<xsl:if test="$param/timeout = @name">
								<xsl:attribute name="selected">selected</xsl:attribute>
							</xsl:if>
							<xsl:value-of select="@text"/>
						</option>
					</xsl:for-each>
				</select>
				<button type="submit" name="host_game">Host game</button>
			</p>
			<p class="misc">
				<span>Select deck</span>
				<select name="SelectedDeck" size="1">
					<xsl:if test="$param/RandomDeck = 'yes'">
						<option value="{am:urlencode($param/random_deck)}">select random</option>
					</xsl:if>
					<xsl:for-each select="$param/decks/*">
						<option value="{am:urlencode(DeckID)}"><xsl:value-of select="Deckname"/></option>
					</xsl:for-each>
				</select>
			</p>
		</xsl:if>

		<!-- hosted games by player list -->
		<xsl:choose>
			<xsl:when test="count($param/hosted_games/*) &gt; 0">
				<table cellspacing="0" class="skin_text">
					<tr>
						<th><p>Created</p></th>
						<th><p>Modes</p></th>
						<th><p>Timeout</p></th>
						<th></th>
					</tr>
					<xsl:for-each select="$param/hosted_games/*">
						<tr class="table_row">
							<td><p><xsl:value-of select="am:datetime(gameaction, $param/timezone)"/></p></td>
							<td>
								<p>
									<xsl:if test="contains(game_modes, 'HiddenCards')">
										<img class="icon" width="20px" height="14px" src="img/blind.png" alt="Hidden cards" title="Hidden cards" />
									</xsl:if>
									<xsl:if test="contains(game_modes, 'FriendlyPlay')">
										<img class="icon" width="20px" height="14px" src="img/friendly_play.png" alt="Friendly play" title="Friendly play" />
									</xsl:if>
									<xsl:if test="contains(game_modes, 'LongMode')">
										<img class="icon" width="20px" height="14px" src="img/long_mode.png" alt="Long mode" title="Long mode" />
									</xsl:if>
								</p>
							</td>
							<td>
								<p>
									<xsl:variable name="timeout" select="timeout" />
									<xsl:value-of select="exsl:node-set($timeout_values)/*[@name = $timeout]/@text"/>
								</p>
							</td>
							<td><p><button type="submit" name="unhost_game" value="{gameid}">Cancel</button></p></td>
						</tr>
					</xsl:for-each>
				</table>
			</xsl:when>
			<xsl:otherwise>
				<p class="information_line warning">There are no hosted games.</p>
			</xsl:otherwise>
		</xsl:choose>

		</xsl:when>
		<!-- end hosted games subsection -->

		<!-- begin subsection AI games -->
		<xsl:when test="$param/current_subsection = 'ai_games'">

		<!-- host new AI game interface -->
		<xsl:if test="$activedecks &gt; 0 and $param/free_slots &gt; 0">
			<p class="misc">
				<img class="icon" width="20px" height="14px" src="img/blind.png" alt="Hidden cards" title="Hidden cards" />
				<input type="checkbox" name="HiddenMode">
					<xsl:if test="$param/BlindFlag = 'yes'"><xsl:attribute name="checked">checked</xsl:attribute></xsl:if>
				</input>
				<img class="icon" width="20px" height="14px" src="img/long_mode.png" alt="Long mode" title="Long mode" />
				<input type="checkbox" name="LongMode">
					<xsl:if test="$param/LongFlag = 'yes'"><xsl:attribute name="checked">checked</xsl:attribute></xsl:if>
				</input>
				<button type="submit" name="ai_game">Create game</button>
			</p>
			<p class="misc" style="margin-bottom: 0.5ex">
				<span>Select deck</span>
				<select name="SelectedDeck" size="1" title="your deck">
					<xsl:if test="$param/RandomDeck = 'yes'">
						<option value="{am:urlencode($param/random_deck)}">select random</option>
					</xsl:if>
					<xsl:for-each select="$param/decks/*">
						<option value="{am:urlencode(DeckID)}"><xsl:value-of select="Deckname"/></option>
					</xsl:for-each>
					<xsl:if test="$param/edit_all_card = 'yes'">
						<xsl:for-each select="$param/ai_challenges/*">
							<xsl:sort select="fullname" order="ascending" />
							<option value="{name}"><xsl:value-of select="name"/></option>
						</xsl:for-each>
					</xsl:if>
				</select>
				<span>Select AI deck</span>
				<select name="SelectedAIDeck" size="1" title="AI deck (used only when playing against AI)">
					<option value="starter_deck">starter deck</option>
					<xsl:if test="$param/RandomDeck = 'yes'">
						<option value="{am:urlencode($param/random_ai_deck)}">select random</option>
					</xsl:if>
					<xsl:for-each select="$param/decks/*">
						<option value="{am:urlencode(DeckID)}"><xsl:value-of select="Deckname"/></option>
					</xsl:for-each>
				</select>
			</p>
		</xsl:if>

		<!-- AI challenge interface -->
		<xsl:if test="$activedecks &gt; 0 and $param/free_slots &gt; 0">
			<p class="misc">
				<span>Select AI challenge</span>
				<select name="selected_challenge" size="1">
					<xsl:for-each select="$param/ai_challenges/*">
						<xsl:sort select="fullname" order="ascending" />
						<option value="{name}"><xsl:value-of select="fullname"/></option>
					</xsl:for-each>
				</select>
				<button type="submit" name="ai_challenge">Play challenge</button>
			</p>
		</xsl:if>

		<div id="ai_challenges">
			<xsl:for-each select="$param/ai_challenges/*">
				<xsl:sort select="fullname" order="ascending" />
				<div class="skin_text">
					<h4><xsl:value-of select="fullname"/></h4>
					<p><xsl:value-of select="description"/></p>
				</div>
			</xsl:for-each>
		</div>

		</xsl:when>
		<!-- end AI games subsection -->
	</xsl:choose>

	</div>
	<!-- end hosted games section -->

	<div class="clear_floats"></div>
	</div>

</xsl:template>


<xsl:template match="section[. = 'Games_details']">
	<xsl:variable name="param" select="$params/game" />
	<xsl:variable name="my_turn" select="$param/GameState = 'in progress' and $param/Current = $param/PlayerName and $param/Surrender = ''" />

	<div id="game">

	<!-- autorefresh -->
	<xsl:if test="$param/autorefresh &gt; 0">
		<input type="hidden" name="Autorefresh" value="{$param/autorefresh}"/>
	</xsl:if>

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
				<!--  display card flags, if set -->
				<xsl:choose>
					<xsl:when test="$param/HiddenCards = 'yes' and Revealed = 'yes' and $param/c_miniflags = 'no'">
						<div class="flag_space">
							<xsl:if test="NewCard = 'yes'">
								<span class="newcard">NEW</span>
							</xsl:if>
							<img src="img/game/revealed.png" class="revealed" width="20px" height="14px" alt="revealed" title="Revealed" />
						</div>
						<div class="clear_floats"></div>
					</xsl:when>
					<xsl:when test="NewCard = 'yes' and $param/c_miniflags = 'no'">
						<p class="flag">NEW CARD</p>
					</xsl:when>
				</xsl:choose>

				<!-- display card -->
				<div>
					<xsl:if test="$my_turn and Playable = 'no'">
						<xsl:attribute name="class">unplayable</xsl:attribute>
					</xsl:if>
					<xsl:variable name="revealed" select="$param/c_miniflags = 'yes' and $param/HiddenCards = 'yes' and Revealed = 'yes'" />
					<xsl:variable name="new_card" select="$param/c_miniflags = 'yes' and NewCard = 'yes'" />
					<xsl:copy-of select="am:cardstring(Data, $param/c_img, $param/c_oldlook, $param/c_insignias, $param/c_my_foils, $new_card, $revealed, $param/keywords_count)" />
				</div>

				<!-- select button and card modes (buttons are locked when surrender request is active) -->
				<xsl:if test="$my_turn">
					<xsl:if test="$param/PlayButtons = 'yes' and Playable = 'yes'">
						<button type="submit" name="play_card" value="{position()}">Play</button> 
					</xsl:if>
					<input type="radio" name="selected_card" value="{position()}" />
					<xsl:if test="Playable = 'yes' and Modes &gt; 0">
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
	<tr class="buttons">
		<td class="game_mode_flags">
			<!-- game mode flags -->
			<xsl:if test="$param/HiddenCards = 'yes'">
				<img class="icon" src="img/blind.png" width="20px" height="14px" alt="Hidden cards" title="Hidden cards" />
			</xsl:if>
			<xsl:if test="$param/FriendlyPlay = 'yes'">
				<img class="icon" src="img/friendly_play.png" width="20px" height="14px" alt="Friendly play" title="Friendly play" />
			</xsl:if>
			<xsl:if test="$param/LongMode = 'yes'">
				<img class="icon" src="img/long_mode.png" width="20px" height="14px" alt="Long mode" title="Long mode" />
			</xsl:if>
			<xsl:if test="$param/AIMode = 'yes'">
				<img class="icon" src="img/ai_mode.png" width="20px" height="14px" alt="AI mode" title="AI mode" />
			</xsl:if>
			<xsl:if test="$param/AI != ''">
				<img class="icon" src="img/ai_challenge.png" width="20px" height="14px" alt="AI challenge - {$param/AI}" title="AI challenge - {$param/AI}" />
			</xsl:if>
		</td>
		<td>
			<!-- show only in case chat pop-up tool can be used -->
			<xsl:if test="$param/AIMode = 'no' and $param/integrated_chat = 'no'">
				<button type="button" name="show_chat" >
					<!-- highlight button in case there are new messages -->
					<xsl:if test="$param/new_chat_messages = 'yes'">
						<xsl:attribute name="class">marked_button</xsl:attribute>
					</xsl:if>
					<xsl:text>Chat</xsl:text>
				</button>
			</xsl:if>
			<xsl:if test="$param/GameState = 'in progress'">
				<a class="button" href="{php:functionString('makeurl', 'Replays_history', 'CurrentReplay', $param/CurrentGame)}">History</a>
			</xsl:if>
		</td>
		<td>
			<!-- 'refresh' button -->
			<a class="button" id="game_refresh" href="{php:functionString('makeurl', 'Games_details', 'CurrentGame', $param/CurrentGame)}" accesskey="w" >Refresh</a>
			<xsl:if test="$param/nextgame_button = 'yes'">
				<button type="submit" name="active_game">Next</button>
			</xsl:if> 
		</td>
		<xsl:choose>
			<xsl:when test="$param/AIMode = 'yes' and not($my_turn) and $param/GameState = 'in progress'">
				<td colspan="2"><button type="submit" name="ai_move">Execute AI move</button></td>
			</xsl:when>
			<xsl:when test="$param/AIMode = 'no' and not($my_turn) and $param/finish_move = 'yes'">
				<td colspan="2"><button type="submit" name="finish_move">Execute opponent's move</button></td>
			</xsl:when>
			<xsl:otherwise>
				<td>
					<xsl:if test="$param/PlayButtons = 'no' and $my_turn">
						<button type="submit" name="play_card" value="0">Play</button>
					</xsl:if>
				</td>
				<td>
					<xsl:if test="$my_turn">
						<button type="submit" name="discard_card">Discard</button>
					</xsl:if>
				</td>
			</xsl:otherwise>
		</xsl:choose>
		<td>
			<xsl:if test="$my_turn and $param/HiddenCards = 'no'">
				<button type="button" name="preview_card">Preview</button>
			</xsl:if>
		</td>
		<td>
			<a class="button" href="{php:functionString('makeurl', 'Decks_view', 'CurrentGame', $param/CurrentGame)}">Deck</a>
			<a class="button" id="game_note" href="{php:functionString('makeurl', 'Games_note', 'CurrentGame', $param/CurrentGame)}" >
				<xsl:if test="$param/has_note = 'yes'">
					<xsl:attribute name="class">button marked_button</xsl:attribute>
				</xsl:if>
				<xsl:text>Note</xsl:text>
			</a>
		</td>

		<!-- begin surrender/abort button -->
		<td>
			<xsl:if test="$param/GameState = 'in progress'">
				<xsl:choose>
					<xsl:when test="$param/opp_isDead = 'yes'">
						<button type="submit" name="abort_game">Abort game</button>
					</xsl:when>
					<xsl:when test="$param/finish_game = 'yes'">
						<button type="submit" name="finish_game">Finish game</button>
					</xsl:when>
					<xsl:when test="$param/Surrender = $param/OpponentName">
						<button type="submit" name="accept_surrender">Accept</button>
						<button type="submit" name="reject_surrender">Reject</button>
					</xsl:when>
					<xsl:when test="$param/Surrender = $param/PlayerName">
						<button type="submit" name="cancel_surrender">Cancel surrender</button>
					</xsl:when>
					<xsl:otherwise>
						<button type="submit" name="surrender">Surrender</button>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:if>
		<!-- end surrender/abort button -->
		</td>
		<!-- end game state indicator -->
	</tr>
	<!-- end messages and game buttons -->

	<!-- begin status -->
	<tr>
		<!-- begin your empire info -->
		<td class="stats">
			<div>
				<p class="facility">
					<xsl:attribute name="title">Quarry: <xsl:value-of select="$param/MyQuarry"/> (Facilities total: <xsl:value-of select="$param/MyQuarry + $param/MyMagic + $param/MyDungeons"/>)</xsl:attribute>
					<xsl:value-of select="$param/MyQuarry"/>
					<xsl:if test="$param/mychanges/Quarry != 0">
						<span class="changes"><xsl:value-of select="$param/mychanges/Quarry"/></span>
					</xsl:if>
				</p>
				<p class="resource">
					<xsl:attribute name="title">Bricks: <xsl:value-of select="$param/MyBricks"/> (Resources total: <xsl:value-of select="$param/MyBricks + $param/MyGems + $param/MyRecruits"/>)</xsl:attribute>
					<xsl:value-of select="$param/MyBricks"/>
					<xsl:if test="$param/mychanges/Bricks != 0">
						<span class="changes"><xsl:value-of select="$param/mychanges/Bricks"/></span>
					</xsl:if>
				</p>
			</div>
			<div>
				<p class="facility">
					<xsl:attribute name="title">Magic: <xsl:value-of select="$param/MyMagic"/> (Facilities total: <xsl:value-of select="$param/MyQuarry + $param/MyMagic + $param/MyDungeons"/>)</xsl:attribute>
					<xsl:value-of select="$param/MyMagic"/>
					<xsl:if test="$param/mychanges/Magic != 0">
						<span class="changes"><xsl:value-of select="$param/mychanges/Magic"/></span>
					</xsl:if>
				</p>
				<p class="resource">
					<xsl:attribute name="title">Gems: <xsl:value-of select="$param/MyGems"/> (Resources total: <xsl:value-of select="$param/MyBricks + $param/MyGems + $param/MyRecruits"/>)</xsl:attribute>
					<xsl:value-of select="$param/MyGems"/>
					<xsl:if test="$param/mychanges/Gems != 0">
						<span class="changes"><xsl:value-of select="$param/mychanges/Gems"/></span>
					</xsl:if>
				</p>
			</div>
			<div>
				<p class="facility">
					<xsl:attribute name="title">Dungeon: <xsl:value-of select="$param/MyDungeons"/> (Facilities total: <xsl:value-of select="$param/MyQuarry + $param/MyMagic + $param/MyDungeons"/>)</xsl:attribute>
					<xsl:value-of select="$param/MyDungeons"/>
					<xsl:if test="$param/mychanges/Dungeons != 0">
						<span class="changes"><xsl:value-of select="$param/mychanges/Dungeons"/></span>
					</xsl:if>
				</p>
				<p class="resource">
					<xsl:attribute name="title">Recruits: <xsl:value-of select="$param/MyRecruits"/> (Resources total: <xsl:value-of select="$param/MyBricks + $param/MyGems + $param/MyRecruits"/>)</xsl:attribute>
					<xsl:value-of select="$param/MyRecruits"/>
					<xsl:if test="$param/mychanges/Recruits != 0">
						<span class="changes"><xsl:value-of select="$param/mychanges/Recruits"/></span>
					</xsl:if>
				</p>
			</div>
			<h5>
				<xsl:value-of select="$param/PlayerName"/>
				<img class="icon" width="18px" height="12px" src="img/flags/{$param/mycountry}.gif" alt="country flag" title="{$param/mycountry}" />
			</h5>
			<p class="info_label">
				<xsl:attribute name="title">Tower: <xsl:value-of select="$param/MyTower"/> / <xsl:value-of select="$param/max_tower"/> (Castle total: <xsl:value-of select="$param/MyTower + $param/MyWall"/> / <xsl:value-of select="$param/max_tower + $param/max_wall"/>)</xsl:attribute>
				<xsl:text>Tower: </xsl:text>
				<span><xsl:value-of select="$param/MyTower"/></span>
				<xsl:if test="$param/mychanges/Tower != 0">
					<span class="changes"><xsl:value-of select="$param/mychanges/Tower"/></span>
				</xsl:if>
			</p>
			<p class="info_label">
				<xsl:attribute name="title">Wall: <xsl:value-of select="$param/MyWall"/> / <xsl:value-of select="$param/max_wall"/> (Castle total: <xsl:value-of select="$param/MyTower + $param/MyWall"/> / <xsl:value-of select="$param/max_tower + $param/max_wall"/>)</xsl:attribute>
				<xsl:text>Wall: </xsl:text>
				<span><xsl:value-of select="$param/MyWall"/></span>
				<xsl:if test="$param/mychanges/Wall != 0">
					<span class="changes"><xsl:value-of select="$param/mychanges/Wall"/></span>
				</xsl:if>
			</p>
		</td>
		<!-- end your empire info -->

		<!-- begin your tower and wall -->
		<td valign="bottom">
			<table cellpadding="0" cellspacing="0" summary="layout table">
				<tr>
					<td valign="bottom">
						<div style="margin: 0ex 1ex 0ex 1ex;">
							<img width="65px" style="display:block" alt="" >
								<xsl:choose>
									<xsl:when test="$param/MyTower = $param/max_tower">
										<xsl:attribute name="src">img/game/victory_top_red.png</xsl:attribute>
										<xsl:attribute name="height">114px</xsl:attribute>
									</xsl:when>
									<xsl:otherwise>
										<xsl:attribute name="src">img/game/tower_top_red.png</xsl:attribute>
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
								<img src="img/game/wall_top.png" width="19px" height="11px" style="display:block" alt="" />
								<div class="wallbody" style="height: {270 * $param/MyWall div $param/max_wall}px;"></div>
							</div>
						</xsl:if>
					</td>
				</tr>
			</table>
		</td>
		<!-- end your tower and wall -->

		<!-- begin your discarded cards -->
		<xsl:choose>
			<xsl:when test="count($param/MyDisCards0/*) = 0 and count($param/MyDisCards1/*) = 0">
				<td></td>
			</xsl:when>
			<xsl:otherwise>
				<td align="center" valign="top">
					<p class="info_label history_label">Discarded</p>
					<div class="history" style="width: 99px;">
						<table cellpadding="0" cellspacing="0">
							<tr valign="top">
								<xsl:for-each select="$param/MyDisCards0/*">
									<td align="center"><xsl:copy-of select="am:cardstring(current(), $param/c_img, $param/c_oldlook, $param/c_insignias, $param/c_my_foils)" /></td>
								</xsl:for-each>
								<td style="border-right: thin solid yellow"></td>
								<xsl:for-each select="$param/MyDisCards1/*">
									<td align="center"><xsl:copy-of select="am:cardstring(current(), $param/c_img, $param/c_oldlook, $param/c_insignias, $param/c_my_foils)" /></td>
								</xsl:for-each>
							</tr>
						</table>
					</div>
				</td>
			</xsl:otherwise>
		</xsl:choose>
		<!-- end discarded cards -->

		<!-- begin your last played card(s) -->
		<td align="center" valign="top">
			<div class="history">
				<table cellpadding="0" cellspacing="0">
					<tr valign="top">
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
									<xsl:copy-of select="am:cardstring(CardData, $param/c_img, $param/c_oldlook, $param/c_insignias, $param/c_my_foils)" />
								</td>
							</xsl:for-each>
						</xsl:if>
					</tr>
				</table>
			</div>
		</td>
		<!-- end your last played card(s) -->

		<!-- begin his last played card(s) -->
		<td align="center" valign="top">
			<div class="history">
				<table cellpadding="0" cellspacing="0">
					<tr valign="top">
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
									<xsl:copy-of select="am:cardstring(CardData, $param/c_img, $param/c_oldlook, $param/c_insignias, $param/c_his_foils)" />
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
				<td align="center" valign="top">
					<p class="info_label history_label">Discarded</p>
					<div class="history" style="width: 99px;">
						<table cellpadding="0" cellspacing="0">
							<tr valign="top">
								<xsl:for-each select="$param/HisDisCards1/*">
									<td align="center"><xsl:copy-of select="am:cardstring(current(), $param/c_img, $param/c_oldlook, $param/c_insignias, $param/c_his_foils)" /></td>
								</xsl:for-each>
								<td style="border-right: thin solid yellow"></td>
								<xsl:for-each select="$param/HisDisCards0/*">
									<td align="center"><xsl:copy-of select="am:cardstring(current(), $param/c_img, $param/c_oldlook, $param/c_insignias, $param/c_his_foils)" /></td>
								</xsl:for-each>
							</tr>
						</table>
					</div>
				</td>
			</xsl:otherwise>
		</xsl:choose>
		<!-- end discarded cards -->

		<!-- begin his tower and wall -->
		<td valign="bottom" align="right">
			<table cellpadding="0" cellspacing="0" summary="layout table">
				<tr>
					<td valign="bottom">
						<xsl:if test="$param/HisWall &gt; 0">
							<div>
								<img src="img/game/wall_top.png" width="19px" height="11px" style="display:block" alt="" />
								<div class="wallbody" style="height: {270 * $param/HisWall div $param/max_wall}px;"></div>
							</div>
						</xsl:if>
					</td>
					<td valign="bottom">
						<div style="margin: 0ex 1ex 0ex 1ex;">
							<img width="65px" style="display:block" alt="" >
								<xsl:choose>
									<xsl:when test="$param/HisTower = $param/max_tower">
										<xsl:attribute name="src">img/game/victory_top_blue.png</xsl:attribute>
										<xsl:attribute name="height">114px</xsl:attribute>
									</xsl:when>
									<xsl:otherwise>
										<xsl:attribute name="src">img/game/tower_top_blue.png</xsl:attribute>
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
		<!-- end his tower and wall -->

		<!-- begin his empire info -->
		<td class="stats" align="right">
			<div>
				<p class="facility">
					<xsl:attribute name="title">Quarry: <xsl:value-of select="$param/HisQuarry"/> (Facilities total: <xsl:value-of select="$param/HisQuarry + $param/HisMagic + $param/HisDungeons"/>)</xsl:attribute>
					<xsl:value-of select="$param/HisQuarry"/>
					<xsl:if test="$param/hischanges/Quarry != 0">
						<span class="changes"><xsl:value-of select="$param/hischanges/Quarry"/></span>
					</xsl:if>
				</p>
				<p class="resource">
					<xsl:attribute name="title">Bricks: <xsl:value-of select="$param/HisBricks"/> (Resources total: <xsl:value-of select="$param/HisBricks + $param/HisGems + $param/HisRecruits"/>)</xsl:attribute>
					<xsl:value-of select="$param/HisBricks"/>
					<xsl:if test="$param/hischanges/Bricks != 0">
						<span class="changes"><xsl:value-of select="$param/hischanges/Bricks"/></span>
					</xsl:if>
				</p>
			</div>
			<div>
				<p class="facility">
					<xsl:attribute name="title">Magic: <xsl:value-of select="$param/HisMagic"/> (Facilities total: <xsl:value-of select="$param/HisQuarry + $param/HisMagic + $param/HisDungeons"/>)</xsl:attribute>
					<xsl:value-of select="$param/HisMagic"/>
					<xsl:if test="$param/hischanges/Magic != 0">
						<span class="changes"><xsl:value-of select="$param/hischanges/Magic"/></span>
					</xsl:if>
				</p>
				<p class="resource">
					<xsl:attribute name="title">Gems: <xsl:value-of select="$param/HisGems"/> (Resources total: <xsl:value-of select="$param/HisBricks + $param/HisGems + $param/HisRecruits"/>)</xsl:attribute>
					<xsl:value-of select="$param/HisGems"/>
					<xsl:if test="$param/hischanges/Gems != 0">
						<span class="changes"><xsl:value-of select="$param/hischanges/Gems"/></span>
					</xsl:if>
				</p>
			</div>
			<div>
				<p class="facility">
					<xsl:attribute name="title">Dungeons: <xsl:value-of select="$param/HisDungeons"/> (Facilities total: <xsl:value-of select="$param/HisQuarry + $param/HisMagic + $param/HisDungeons"/>)</xsl:attribute>
					<xsl:value-of select="$param/HisDungeons"/>
					<xsl:if test="$param/hischanges/Dungeons != 0">
						<span class="changes"><xsl:value-of select="$param/hischanges/Dungeons"/></span>
					</xsl:if>
				</p>
				<p class="resource">
					<xsl:attribute name="title">Recruits: <xsl:value-of select="$param/HisRecruits"/> (Resources total: <xsl:value-of select="$param/HisBricks + $param/HisGems + $param/HisRecruits"/>)</xsl:attribute>
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
				<img class="icon" width="18px" height="12px" src="img/flags/{$param/hiscountry}.gif" alt="country flag" title="{$param/hiscountry}" />
				<a class="profile" href="{php:functionString('makeurl', 'Players_details', 'Profile', $param/OpponentName)}">
					<!-- rename opponent to actual AI name in case of AI challenge -->
					<xsl:choose>
						<xsl:when test="$param/AI != ''"><xsl:value-of select="$param/AI"/></xsl:when>
						<xsl:otherwise><xsl:value-of select="$param/OpponentName"/></xsl:otherwise>
					</xsl:choose>
				</a>
			</h5>
			<p class="info_label">
				<xsl:attribute name="title">Tower: <xsl:value-of select="$param/HisTower"/> / <xsl:value-of select="$param/max_tower"/> (Castle total: <xsl:value-of select="$param/HisTower + $param/HisWall"/> / <xsl:value-of select="$param/max_tower + $param/max_wall"/>)</xsl:attribute>
				<xsl:text>Tower: </xsl:text>
				<span><xsl:value-of select="$param/HisTower"/></span>
				<xsl:if test="$param/hischanges/Tower != 0">
					<span class="changes"><xsl:value-of select="$param/hischanges/Tower"/></span>
				</xsl:if>
			</p>
			<p class="info_label">
				<xsl:attribute name="title">Wall: <xsl:value-of select="$param/HisWall"/> / <xsl:value-of select="$param/max_wall"/> (Castle total: <xsl:value-of select="$param/HisTower + $param/HisWall"/> / <xsl:value-of select="$param/max_tower + $param/max_wall"/>)</xsl:attribute>
				<xsl:text>Wall: </xsl:text>
				<span><xsl:value-of select="$param/HisWall"/></span>
				<xsl:if test="$param/hischanges/Wall != 0">
					<span class="changes"><xsl:value-of select="$param/hischanges/Wall"/></span>
				</xsl:if>
			</p>
		</td>
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
	<!-- end my tokens -->

		<!-- begin game state indicator -->
		<td colspan="2" style="text-align: center">
			<xsl:choose>
				<xsl:when test="$param/GameState = 'in progress'">
					<p class="info_label">
						<xsl:choose>
							<xsl:when test="$param/Surrender = $param/OpponentName">
								<span class="player"><xsl:value-of select="$param/OpponentName"/> wishes to surrender</span>
							</xsl:when>
							<xsl:when test="$param/Surrender = $param/PlayerName">
								<span class="opponent">You have requested to surrender</span>
							</xsl:when>
							<xsl:when test="$param/Current = $param/PlayerName">
								<span class="player"><xsl:text>It is your turn</xsl:text></span>
							</xsl:when>
							<xsl:otherwise>
								<span class="opponent"><xsl:text>It is </xsl:text><xsl:value-of select="$param/OpponentName"/><xsl:text>'s turn</xsl:text></span>
							</xsl:otherwise>
						</xsl:choose>
					</p>
				</xsl:when>
				<xsl:otherwise>
					<button type="submit" name="Confirm">Leave the game</button>
				</xsl:otherwise>
			</xsl:choose>
		</td>

	<!-- begin his tokens -->
		<xsl:for-each select="$param/HisTokens/*">
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
	<!-- end his tokens -->

	</tr>
	<!-- end tokens -->

	<!-- begin his cards -->
	<tr valign="top" class="hand">
		<xsl:for-each select="$param/HisHand/*">
			<td align="center">
				<!--  display new card indicator, if set -->
				<xsl:if test="NewCard = 'yes' and ($param/HiddenCards = 'no' or Revealed = 'yes' or $param/GameState != 'in progress') and $param/c_miniflags = 'no'">
					<p class="flag">NEW CARD</p>
				</xsl:if>

				<!-- display card -->
				<xsl:choose>
					<xsl:when test="($param/HiddenCards = 'yes') and (Revealed = 'no') and ($param/GameState = 'in progress')">
						<div class="hidden_card">
							<!--  display new card indicator, if set -->
							<xsl:if test="NewCard = 'yes'">
								<p class="flag">NEW CARD</p>
							</xsl:if>
						</div>
					</xsl:when>
					<xsl:otherwise>
						<div>
							<xsl:if test="Playable = 'no'">
								<xsl:attribute name="class">unplayable</xsl:attribute>
							</xsl:if>
							<xsl:variable name="new_card" select="$param/c_miniflags = 'yes' and NewCard = 'yes'" />
							<xsl:copy-of select="am:cardstring(Data, $param/c_img, $param/c_oldlook, $param/c_insignias, $param/c_his_foils, $new_card)" />
						</div>
					</xsl:otherwise>
				</xsl:choose>
			</td>
		</xsl:for-each>
	</tr>
	<!-- end his cards -->

	</table>

	<!-- begin chatboard -->

	<!-- chatboard is not available in AI mode -->
	<xsl:if test="$param/AIMode = 'no'">

	<div class="chatsection">
		<!-- disable in case integrated chat setting is disabled -->
		<xsl:if test="$param/integrated_chat = 'no'"><xsl:attribute name="style">display: none</xsl:attribute></xsl:if>

		<!-- avatars normal version -->
		<xsl:if test="($param/display_avatar = 'yes') and ($param/correction = 'no')">
			<img style="float: left;  margin: 0.5ex 0ex 0ex 0ex;" class="avatar" height="60px" width="60px" src="img/avatars/{$param/myavatar}" alt="avatar" />
			<img style="float: right; margin: 0.5ex 0ex 0ex 0ex;" class="avatar" height="60px" width="60px" src="img/avatars/{$param/hisavatar}" alt="avatar" />
		</xsl:if>

		<!-- message list -->
		<xsl:if test="count($param/messagelist/*) &gt; 0">
			<div class="chatbox">
				<!-- scrolls chatbox to bottom if reverse chatorder setting is active -->
				<xsl:if test="$param/reverse_chat = 'yes'"><xsl:attribute name="class">chatbox scroll_max</xsl:attribute></xsl:if>
				<xsl:for-each select="$param/messagelist/*">
					<p>
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
						<xsl:text> on </xsl:text>
						<xsl:value-of select="am:datetime(Timestamp, $param/timezone)"/>
					</p>
					<div><xsl:value-of select="am:BBCode_parse_extended(Message)" disable-output-escaping="yes" /></div>
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
				<button type="submit" name="send_message" tabindex="2" accesskey="s">Send</button>
			</div>
		</xsl:if>

		<div style="clear: both"></div>
	</div>

	<!-- chat dialog (do not display) -->
	<div title="Chat" style="display: none">
		<!-- enable in case integrated chat setting is disabled -->
		<xsl:if test="$param/integrated_chat = 'no'"><xsl:attribute name="id">chat_dialog</xsl:attribute></xsl:if>

		<!-- message list -->
		<div>
			<xsl:if test="count($param/messagelist/*) &gt; 0">
				<!-- scrolls chatbox to bottom if reverse chatorder setting is active -->
				<xsl:if test="$param/reverse_chat = 'yes'"><xsl:attribute name="class">scroll_max</xsl:attribute></xsl:if>
				<xsl:for-each select="$param/messagelist/*">
					<p>
						<xsl:if test="$param/display_avatar = 'yes'">
							<img class="avatar" height="20px" width="20px" alt="avatar" >
								<xsl:choose>
									<xsl:when test="Name = $param/PlayerName">
										<xsl:attribute name="src">img/avatars/<xsl:value-of select="$param/myavatar"/></xsl:attribute>
									</xsl:when>
									<xsl:when test="Name = $param/OpponentName">
										<xsl:attribute name="src">img/avatars/<xsl:value-of select="$param/hisavatar"/></xsl:attribute>
									</xsl:when>
								</xsl:choose>
							</img>
						</xsl:if>
						<span>
							<xsl:choose>
								<xsl:when test="Name = $param/PlayerName">
									<xsl:attribute name="class">chatbox_player</xsl:attribute>
								</xsl:when>
								<!-- highlight new chat messages (never highlight own chat messages) -->
								<xsl:when test="am:datediff(Timestamp, $param/chat_notification) &lt; 0">
									<xsl:attribute name="class">new_message</xsl:attribute>
								</xsl:when>
								<xsl:when test="Name = $param/OpponentName">
									<xsl:attribute name="class">chatbox_opponent</xsl:attribute>
								</xsl:when>
								<xsl:otherwise>
									<xsl:attribute name="class">chatbox_system</xsl:attribute>
								</xsl:otherwise>
							</xsl:choose>
							<xsl:value-of select="Name"/>
							<xsl:text> on </xsl:text>
							<xsl:value-of select="am:datetime(Timestamp, $param/timezone)"/>
						</span>
					</p>
					<div class="chat_message"><xsl:value-of select="am:BBCode_parse_extended(Message)" disable-output-escaping="yes" /></div>
				</xsl:for-each>
			</xsl:if>
		</div>

		<textarea name="chat_area" rows="3" cols="50"></textarea>
	</div>

	</xsl:if>

	<!-- end chatboard -->

	<!-- game note dialog (do not display) -->
	<div id="game_note_dialog" title="Game note" style="display: none">
		<textarea name="Content" rows="10" cols="50"><xsl:value-of select="$param/GameNote"/></textarea>
	</div>

	</div>

</xsl:template>


<xsl:template match="section[. = 'Games_note']">
	<xsl:variable name="param" select="$params/game_note" />

	<!-- remember the current location across pages -->
	<div>
		<input type="hidden" name="CurrentGame" value="{$param/CurrentGame}"/>
	</div>

	<div id="game_note">

	<h3>Game note</h3>

	<div class="skin_text">
		<a class="button" href="{php:functionString('makeurl', 'Games_details', 'CurrentGame', $param/CurrentGame)}">Back to game</a>
		<button type="submit" name="save_note_return">Save &amp; return</button>
		<button type="submit" name="save_note">Save</button>
		<button type="submit" name="clear_note">Clear</button>
		<button type="submit" name="clear_note_return">Clear &amp; return</button>
		<hr/>

		<textarea name="Content" rows="10" cols="50"><xsl:value-of select="$param/text"/></textarea>
	</div>

	</div>

</xsl:template>


</xsl:stylesheet>
