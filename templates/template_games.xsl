<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet [ <!ENTITY rarr "&#8594;"> ]>
<xsl:stylesheet version="1.0"
                xmlns="http://www.w3.org/1999/xhtml"
                xmlns:am="http://arcomage.netvor.sk"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes" doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" />


<xsl:template match="section[. = 'Games']">
	<xsl:variable name="param" select="$params/games" />
	<xsl:variable name="activedecks" select="count($param/decks/*)" />
	<xsl:variable name="list" select="$param/list" />

	<div id="games">

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
										<p class="ended_game" >Can be aborted</p>
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
		<input type="submit" name="free_games" value="Available games">
			<xsl:if test="$param/current_subsection = 'free_games'">
				<xsl:attribute name="class">pushed</xsl:attribute>
			</xsl:if>
		</input>

		<input type="submit" name="hosted_games" value="My games">
			<xsl:if test="$param/current_subsection = 'hosted_games'">
				<xsl:attribute name="class">pushed</xsl:attribute>
			</xsl:if>
		</input>
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
			<img width="20px" height="14px" src="img/blind.png" alt="blind flag" class="icon" title="Hidden cards" />
			<xsl:copy-of select="am:htmlSelectBox('HiddenCards', $param/HiddenCards, $mode_options, '')"/>

			<!-- friendly game filter -->
			<img width="20px" height="14px" src="img/friendly_play.png" alt="friendly flag" class="icon" title="Friendly play" />
			<xsl:copy-of select="am:htmlSelectBox('FriendlyPlay', $param/FriendlyPlay, $mode_options, '')"/>

			<input type="submit" name="filter_hosted_games" value="Apply filters" />
		</p>
		<!-- end filters -->

		<!-- selected deck -->
		<xsl:if test="$activedecks &gt; 0 and $param/free_slots &gt; 0">
			<p class="misc">
				<xsl:text>Select deck </xsl:text>
				<select name="SelectedDeck" size="1">
					<xsl:if test="$param/RandomDeck = 'yes'">
						<option value="{am:urlencode($param/random_deck)}">select random</option>
					</xsl:if>
					<xsl:for-each select="$param/decks/*">
						<option value="{am:urlencode(text())}"><xsl:value-of select="text()"/></option>
					</xsl:for-each>
				</select>
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
						<th></th>
					</tr>
					<xsl:for-each select="$param/free_games/*">
						<tr class="table_row">
							<td>
								<p class="flags">
									<input class="small_button" type="submit" name="user_details[{opponent}]" value="i" />
									<xsl:if test="status != 'none'">
										<img width="20px" height="14px" src="img/{status}.png" alt="status flag" class="icon" title="{status}" />
									</xsl:if>
								</p>
							</td>
							<td>
								<p>
									<xsl:if test="active = 'yes'">
										<xsl:attribute name="class">p_online</xsl:attribute>
									</xsl:if>
									<xsl:value-of select="opponent"/>
								</p>
							</td>
							<td><p><xsl:value-of select="am:datetime(gameaction, $param/timezone)"/></p></td>
							<td>
								<p>
									<xsl:if test="hidden_cards = 'yes'">
										<img width="20px" height="14px" src="img/blind.png" alt="blind flag" class="icon" title="Hidden cards" />
									</xsl:if>
									<xsl:if test="friendly_play = 'yes'">
										<img width="20px" height="14px" src="img/friendly_play.png" alt="friendly flag" class="icon" title="Friendly play" />
									</xsl:if>
								</p>
							</td>
							<td>
								<xsl:if test="$activedecks &gt; 0 and $param/free_slots &gt; 0">
									<p><input type="submit" name="join_game[{gameid}]" value="Join" /></p>
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
				<select name="SelectedDeck" size="1">
					<xsl:if test="$param/RandomDeck = 'yes'">
						<option value="{am:urlencode($param/random_deck)}">select random</option>
					</xsl:if>
					<xsl:for-each select="$param/decks/*">
						<option value="{am:urlencode(text())}"><xsl:value-of select="text()"/></option>
					</xsl:for-each>
				</select>
				<img width="20px" height="14px" src="img/blind.png" alt="blind flag" class="icon" title="Hidden cards" />
				<input type="checkbox" name="HiddenMode">
					<xsl:if test="$param/BlindFlag = 'yes'"><xsl:attribute name="checked">checked</xsl:attribute></xsl:if>
				</input>
				<img width="20px" height="14px" src="img/friendly_play.png" alt="friendly flag" class="icon" title="Friendly play" />
				<input type="checkbox" name="FriendlyMode">
					<xsl:if test="$param/FriendlyFlag = 'yes'"><xsl:attribute name="checked">checked</xsl:attribute></xsl:if>
				</input>
				<input type="submit" name="host_game" value="Host game" />
			</p>
		</xsl:if>

		<!-- hosted games by player list -->
		<xsl:choose>
			<xsl:when test="count($param/hosted_games/*) &gt; 0">
				<table cellspacing="0" class="skin_text">
					<tr>
						<th><p>Created</p></th>
						<th><p>Modes</p></th>
						<th></th>
					</tr>
					<xsl:for-each select="$param/hosted_games/*">
						<tr class="table_row">
							<td><p><xsl:value-of select="am:datetime(gameaction, $param/timezone)"/></p></td>
							<td>
								<p>
									<xsl:if test="hidden_cards = 'yes'">
										<img width="20px" height="14px" src="img/blind.png" alt="blind flag" class="icon" title="Hidden cards" />
									</xsl:if>
									<xsl:if test="friendly_play = 'yes'">
										<img width="20px" height="14px" src="img/friendly_play.png" alt="friendly flag" class="icon" title="Friendly play" />
									</xsl:if>
								</p>
							</td>
							<td><p><input type="submit" name="unhost_game[{gameid}]" value="Cancel" /></p></td>
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
	</xsl:choose>

	</div>
	<!-- end hosted games section -->

	<div class="clear_floats"></div>
	</div>

</xsl:template>


</xsl:stylesheet>
