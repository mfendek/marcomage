<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
                xmlns="http://www.w3.org/1999/xhtml"
                xmlns:am="http://arcomage.netvor.sk"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:exsl="http://exslt.org/common"
                extension-element-prefixes="exsl">
<xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes" doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" />

<!-- includes -->
<xsl:include href="template_main.xsl" />


<xsl:template match="section[. = 'Decks']">
	<xsl:variable name="param" select="$params/decks" />

	<div id="decks">
	<table cellspacing="0" class="skin_text">
		<tr>
			<th><p>Deck name</p></th>
			<th><p>Wins</p></th>
			<th><p>Losses</p></th>
			<th><p>Draws</p></th>
			<th><p>Last change</p></th>
		</tr>
		<xsl:for-each select="$param/list/*">
			<tr class="table_row">
				<td>
					<p>
						<xsl:if test="Ready = 'yes'">
							<xsl:attribute name="class">p_online</xsl:attribute>
						</xsl:if>
						<xsl:value-of select="Deckname"/>
					</p>
				</td>
				<td><p><xsl:value-of select="Wins"/></p></td>
				<td><p><xsl:value-of select="Losses"/></p></td>
				<td><p><xsl:value-of select="Draws"/></p></td>
				<td>
					<p>
						<xsl:choose>
							<xsl:when test="Modified != '0000-00-00 00:00:00'"><xsl:value-of select="am:datetime(Modified, $param/timezone)"/></xsl:when>
							<xsl:otherwise>n/a</xsl:otherwise>
						</xsl:choose>
					</p>
				</td>
				<td><p><a class="button" href="{am:makeurl('Decks_edit', 'CurrentDeck', DeckID)}">&gt;</a></p></td>
			</tr>
		</xsl:for-each>
	</table>
	</div>
</xsl:template>


<xsl:template match="section[. = 'Decks_view']">
	<xsl:variable name="param" select="$params/deck_view" />

	<div style="text-align: center">
		<a class="button" href="{am:makeurl('Games_details', 'CurrentGame', $param/CurrentGame)}">Back to game</a>
	</div>

	<table class="deck skin_label" cellpadding="0" cellspacing="0" >

		<tr>
			<th><p>Common</p></th>
			<th><p>Uncommon</p></th>
			<th><p>Rare</p></th>
		</tr>

		<tr valign="top">
		<xsl:for-each select="$param/DeckCards/*"> <!-- Common, Uncommon, Rare sections -->
			<td>
				<table class="centered" cellpadding="0" cellspacing="0">
				<xsl:variable name="cards" select="."/>
				<xsl:for-each select="$cards/*[position() &lt;= 5]"> <!-- row counting hack -->
				<tr>
					<xsl:variable name="i" select="position()"/>
					<xsl:for-each select="$cards/*[position() &gt;= $i*3-2 and position() &lt;= $i*3]">
						<td>
							<xsl:copy-of select="am:cardstring(current(), $param/c_img, $param/c_oldlook, $param/c_insignias, $param/c_foils)" />
						</td>
					</xsl:for-each>
				</tr>
				</xsl:for-each>
				</table>
			</td>
		</xsl:for-each>
		</tr>

	</table>
</xsl:template>


<xsl:template match="section[. = 'Decks_edit']">
	<xsl:variable name="param" select="$params/deck_edit" />

	<!-- remember the current location across pages -->
	<div>
		<input type="hidden" name="CurrentDeck" value="{$param/CurrentDeck}"/>
		<input type="hidden" name="CardPool" value="{$param/card_pool}"/>
	</div>

	<div class="misc">

	<div id="tokens">
		<xsl:for-each select="$param/Tokens/*">
			<xsl:variable name="token" select="." />

			<select name="Token{position()}">
				<option value="none">
					<xsl:if test="$token = 'none'">
						<xsl:attribute name="selected">selected</xsl:attribute>
					</xsl:if>
					<xsl:text>None</xsl:text>
				</option>
				<xsl:for-each select="$param/TokenKeywords/*">
					<option value="{text()}">
						<xsl:if test="$token = .">
							<xsl:attribute name="selected">selected</xsl:attribute>
						</xsl:if>
						<xsl:value-of select="text()"/>
					</option>
				</xsl:for-each>
			</select>
		</xsl:for-each>

		<button type="submit" name="set_tokens">Set</button>
		<button type="submit" name="auto_tokens">Auto</button>
	</div>

	<input type="text" name="NewDeckName" value="{$param/deckname}" maxlength="20" />
	<button type="submit" name="rename_deck">Rename</button>

	<xsl:choose>
		<xsl:when test="$param/reset = 'no'">
			<button type="submit" name="reset_deck_prepare">Reset</button>
		</xsl:when>
		<xsl:otherwise>
			<button type="submit" name="reset_deck_confirm" class="marked_button">Confirm reset</button>
		</xsl:otherwise>
	</xsl:choose>

	<button type="submit" name="export_deck">Export</button>
	<input type="file" name="uploadedfile" />
	<button type="submit" name="import_deck">Import</button>
	<xsl:choose>
		<xsl:when test="$param/reset_stats = 'no'">
			<button type="submit" name="reset_stats_prepare">Reset statistics</button>
		</xsl:when>
		<xsl:otherwise>
			<button type="submit" name="reset_stats_confirm" class="marked_button">Confirm reset</button>
		</xsl:otherwise>
	</xsl:choose>

	</div>

	<div class="filters">

	<div id="cost_per_turn" title="average cost per turn (bricks, gems, recruits)">
		<b><xsl:value-of select="$param/Res/Bricks"/></b>
		<b><xsl:value-of select="$param/Res/Gems"/></b>
		<b><xsl:value-of select="$param/Res/Recruits"/></b>
	</div>

	<p class="deck_stats">
		<xsl:attribute name="title">deck statistics (wins / losses / draws)</xsl:attribute>
		<b><xsl:value-of select="$param/wins"/></b>
		<xsl:text> / </xsl:text>
		<b><xsl:value-of select="$param/losses"/></b>
		<xsl:text> / </xsl:text>
		<b><xsl:value-of select="$param/draws"/></b>
	</p>

	<!-- card name filter -->
	<input type="text" name="NameFilter" maxlength="20" size="15" value="{$param/NameFilter}" title="search phrase for card name (CASE sensitive, type first letter as capital if you want the card name to start with that letter)" />

	<!-- card rarity filter -->
	<xsl:variable name="classes">
		<value name="Common"   value="Common"   />
		<value name="Uncommon" value="Uncommon" />
		<value name="Rare"     value="Rare"     />
		<value name="Any"      value="none"     />
	</xsl:variable>
	<xsl:copy-of select="am:htmlSelectBox('ClassFilter', $param/ClassFilter, $classes, '')"/>

	<!-- card keyword filter -->
	<xsl:variable name="keywords">
		<value name="No keyword filter" value="none"        />
		<value name="Any keyword"       value="Any keyword" />
		<value name="No keywords"       value="No keywords" />
	</xsl:variable>
	<xsl:copy-of select="am:htmlSelectBox('KeywordFilter', $param/KeywordFilter, $keywords, $param/keywords)"/>

	<!-- cost filter -->
	<xsl:variable name="costs">
		<value name="No cost filter" value="none"  />
		<value name="Bricks only"    value="Red"   />
		<value name="Gems only"      value="Blue"  />
		<value name="Recruits only"  value="Green" />
		<value name="Zero cost"      value="Zero"  />
		<value name="Mixed cost"     value="Mixed" />
	</xsl:variable>
	<xsl:copy-of select="am:htmlSelectBox('CostFilter', $param/CostFilter, $costs, '')"/>

	<!-- advanced filter select menu - filters based upon appearance in card text -->
	<xsl:variable name="advanced">
		<value name="No adv. filter" value="none"          />
		<value name="Attack"         value="Attack:"       />
		<value name="Discard"        value="Discard "      />
		<value name="Replace"        value="Replace "      />
		<value name="Reveal"         value="Reveal"        />
		<value name="Summon"         value="Summons"       />
		<value name="Production"     value="Prod"          />
		<value name="Persistent"     value="Replace a card in hand with self" />
		<value name="Wall +"         value="Wall: +"       />
		<value name="Wall -"         value="Wall: -"       />
		<value name="Tower +"        value="Tower: +"      />
		<value name="Tower -"        value="Tower: -"      />
		<value name="Facilities +"   value="Facilities: +" />
		<value name="Facilities -"   value="Facilities: -" />
		<value name="Magic +"        value="Magic: +"      />
		<value name="Magic -"        value="Magic: -"      />
		<value name="Quarry +"       value="Quarry: +"     />
		<value name="Quarry -"       value="Quarry: -"     />
		<value name="Dungeon +"      value="Dungeon: +"    />
		<value name="Dungeon -"      value="Dungeon: -"    />
		<value name="Stock +"        value="Stock: +"      />
		<value name="Stock -"        value="Stock: -"      />
		<value name="Gems +"         value="Gems: +"       />
		<value name="Gems -"         value="Gems: -"       />
		<value name="Bricks +"       value="Bricks: +"     />
		<value name="Bricks -"       value="Bricks: -"     />
		<value name="Recruits +"     value="Recruits: +"   />
		<value name="Recruits -"     value="Recruits: -"   />
	</xsl:variable>
	<xsl:copy-of select="am:htmlSelectBox('AdvancedFilter', $param/AdvancedFilter, $advanced, '')"/>

	<!-- support keyword filter -->
	<xsl:variable name="support">
		<value name="No support filter" value="none"        />
		<value name="Any keyword"       value="Any keyword" />
		<value name="No keywords"       value="No keywords" />
	</xsl:variable>
	<xsl:copy-of select="am:htmlSelectBox('SupportFilter', $param/SupportFilter, $support, $param/keywords)"/>

	<!-- creation date filter -->
	<xsl:variable name="created">
		<value name="No created filter" value="none" />
	</xsl:variable>
	<xsl:copy-of select="am:htmlSelectBox('CreatedFilter', $param/CreatedFilter, $created, $param/created_dates)"/>

	<!-- modification date filter -->
	<xsl:variable name="modified">
		<value name="No modified filter" value="none" />
	</xsl:variable>
	<xsl:copy-of select="am:htmlSelectBox('ModifiedFilter', $param/ModifiedFilter, $modified, $param/modified_dates)"/>

	<!-- card level filter -->
	<xsl:variable name="level">
		<value name="No level filter" value="none" />
	</xsl:variable>
	<xsl:copy-of select="am:htmlSelectBox('LevelFilter', $param/LevelFilter, $level, $param/levels)"/>

	<button type="submit" name="filter">Apply filters</button>
	<button type="submit" name="card_pool_switch">
		<xsl:if test="count($param/CardList/*) &gt; 0">
			<xsl:attribute name="class">marked_button</xsl:attribute>		
		</xsl:if>
		<xsl:choose>
			<xsl:when test="$param/card_pool = 'yes'">Hide card pool</xsl:when>
			<xsl:when test="$param/card_pool = 'no'">Show card pool</xsl:when>
		</xsl:choose>
	</button>

	</div>

	<!-- cards in card pool -->
	<div id="card_pool">
	<xsl:if test="$param/card_pool = 'no'">
		<xsl:attribute name="class">hidden</xsl:attribute>		
	</xsl:if>
	<!-- sort cards in card pool by card name -->
	<xsl:variable name="card_list">
		<xsl:for-each select="$param/CardList/*">
			<xsl:sort select="name" order="ascending"/>
			<xsl:copy-of select="." />
		</xsl:for-each>
	</xsl:variable>
	<xsl:variable name="columns" select="$param/cards_per_row"/>

	<xsl:for-each select="exsl:node-set($card_list)/*[position() &lt;= floor(((count(exsl:node-set($card_list)/*) - 1) div $columns)) + 1]">
		<table cellpadding="0" cellspacing="0">
			<tr valign="top">
				<xsl:variable name="i" select="position()"/>
				<xsl:for-each select="exsl:node-set($card_list)/*[position() &gt;= (($i - 1)*$columns + 1) and position() &lt;= $i*$columns]">
					<!-- display card slot -->
					<td id="card_{id}" >
						<xsl:choose>
							<xsl:when test="excluded = 'no'">
								<xsl:attribute name="onclick">return TakeCard(<xsl:value-of select="id" />)</xsl:attribute>
							</xsl:when>
							<xsl:otherwise>
								<xsl:attribute name="class">taken</xsl:attribute>
							</xsl:otherwise>
						</xsl:choose>
						<xsl:copy-of select="am:cardstring(current(), $param/c_img, $param/c_oldlook, $param/c_insignias, $param/c_foils)" />
					</td>
				</xsl:for-each>
			</tr>
			<tr>
				<xsl:variable name="i" select="position()"/>
				<xsl:for-each select="exsl:node-set($card_list)/*[position() &gt;= (($i - 1)*$columns + 1) and position() &lt;= $i*$columns]">
					<!-- display Take button if JavaScript is disabled -->
					<td>
						<xsl:if test="excluded = 'no'">
							<noscript><div><button type="submit" name="add_card" value="{id}">Take</button></div></noscript>
						</xsl:if>
					</td>
				</xsl:for-each>
			</tr>
		</table>
	</xsl:for-each>
	</div>

	<!-- cards in deck -->
	<table class="deck skin_label" cellpadding="0" cellspacing="0" >

		<tr>
			<th><p>Common</p></th>
			<th><p>Uncommon</p></th>
			<th><p>Rare</p></th>
		</tr>

		<tr valign="top">
		<xsl:for-each select="$param/DeckCards/*"> <!-- Common, Uncommon, Rare sections -->
			<td>
				<table class="centered" cellpadding="0" cellspacing="0">
				<xsl:variable name="rarity" select="position()"/>
				<xsl:variable name="cards" select="."/>
				<xsl:for-each select="$cards/*[position() &lt;= 5]"> <!-- row counting hack -->
				<tr>
					<xsl:variable name="i" select="position()"/>
					<xsl:for-each select="$cards/*[position() &gt;= $i*3-2 and position() &lt;= $i*3]">
						<td id="slot_{(($i - 1) * 3) + position() + 15 * ($rarity - 1)}" >
							<xsl:if test="id &gt; 0"><xsl:attribute name="onclick">return RemoveCard(<xsl:value-of select="id" />)</xsl:attribute></xsl:if>
							<xsl:copy-of select="am:cardstring(current(), $param/c_img, $param/c_oldlook, $param/c_insignias, $param/c_foils)" />
							<xsl:if test="id != 0">
								<noscript><div><button type="submit" name="return_card" value="{id}">Return</button></div></noscript>
							</xsl:if>
						</td>
					</xsl:for-each>
				</tr>
				</xsl:for-each>
				</table>
			</td>
		</xsl:for-each>
		</tr>

	</table>
</xsl:template>


</xsl:stylesheet>
