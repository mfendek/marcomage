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

	<!-- upper navigation -->
	<xsl:if test="$param/player_level &gt;= 10">
		<div class="decks_navbar">
			<a class="button" href="{am:makeurl('Decks_shared')}">Shared decks</a>
		</div>
	</xsl:if>
	<table cellspacing="0" class="skin_text">
		<tr>
			<th><p>Deck name</p></th>
			<th><p>Wins</p></th>
			<th><p>Losses</p></th>
			<th><p>Draws</p></th>
			<xsl:if test="$param/player_level &gt;= 10">
				<th><p>Shared</p></th>
			</xsl:if>
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
				<xsl:if test="$param/player_level &gt;= 10">
					<td>
						<p>
							<xsl:choose>
								<xsl:when test="Shared = 1">yes</xsl:when>
								<xsl:otherwise>no</xsl:otherwise>
							</xsl:choose>
						</p>
					</td>
				</xsl:if>
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


<xsl:template match="section[. = 'Decks_shared']">
	<xsl:variable name="param" select="$params/decks_shared" />

	<div id="decks">

	<!-- upper navigation -->
	<div class="decks_navbar">

		<!-- author filter -->
		<xsl:if test="count($param/authors/*) &gt; 0">
			<xsl:variable name="authors">
				<value name="No author filter" value="none" />
			</xsl:variable>
			<xsl:copy-of select="am:htmlSelectBox('author_filter', $param/author_val, $authors, $param/authors)"/>
		</xsl:if>

		<button type="submit" name="decks_shared_filter">Apply filters</button>
		<xsl:copy-of select="am:upper_navigation($param/page_count, $param/current_page, 'decks')"/>

		<!-- selected deck -->
		<span>Target deck</span>
		<select name="SelectedDeck" size="1">
			<xsl:for-each select="$param/decks/*">
				<option value="{am:urlencode(DeckID)}"><xsl:value-of select="Deckname"/></option>
			</xsl:for-each>
		</select>
	</div>

	<table cellspacing="0" class="skin_text">
		<tr>
			<xsl:variable name="columns">
				<column name="Deckname" text="Deck name"   sortable="yes" />
				<column name="Username" text="Author"      sortable="yes" />
				<column name="Wins"     text="Wins"        sortable="no"  />
				<column name="Losses"   text="Losses"      sortable="no"  />
				<column name="Draws"    text="Draws"       sortable="no"  />
				<column name="Modified" text="Last change" sortable="yes" />
			</xsl:variable>
			
			<xsl:for-each select="exsl:node-set($columns)/*">
				<th>
					<p>
						<xsl:value-of select="@text"/>
						<xsl:if test="@sortable = 'yes'">
							<button class="small_button" type="submit" value="{@name}" >
								<xsl:if test="$param/current_condition = @name">
									<xsl:attribute name="class">small_button pushed</xsl:attribute>
								</xsl:if>
								<xsl:choose>
									<xsl:when test="(($param/current_condition = @name) and ($param/current_order = 'DESC'))">
										<xsl:attribute name="name">decks_ord_asc</xsl:attribute>
										<xsl:text>\/</xsl:text>
									</xsl:when>
									<xsl:otherwise>
										<xsl:attribute name="name">decks_ord_desc</xsl:attribute>
										<xsl:text>/\</xsl:text>
									</xsl:otherwise>
								</xsl:choose>
							</button>
						</xsl:if>
					</p>
				</th>
			</xsl:for-each>
			<th></th>
		</tr>
		<xsl:for-each select="$param/shared_list/*">
			<tr class="table_row">
				<td><p><a class="deck" href="{am:makeurl('Decks_details', 'CurrentDeck', DeckID)}"><xsl:value-of select="Deckname"/></a></p></td>
				<td><p><a class="profile" href="{am:makeurl('Players_details', 'Profile', Username)}"><xsl:value-of select="Username"/></a></p></td>
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
				<td><button type="submit" name="import_shared_deck" value="{DeckID}">Import</button></td>
			</tr>
		</xsl:for-each>
	</table>

	<div class="decks_navbar">
		<!-- lower navigation -->
		<xsl:copy-of select="am:lower_navigation($param/page_count, $param/current_page, 'decks', 'Decks_shared')"/>
	</div>

	<input type="hidden" name="CurrentDeckPage" value="{$param/current_page}" />
	<input type="hidden" name="CurrentDeckOrder" value="{$param/current_order}" />
	<input type="hidden" name="CurrentDeckCon" value="{$param/current_condition}" />

	</div>
</xsl:template>


<xsl:template match="section[. = 'Decks_details']">
	<xsl:variable name="param" select="$params/decks_details" />

	<div id="deck_shared">

	<div class="decks_navbar">
		<a class="button" href="{am:makeurl('Decks_shared')}">Shared decks</a>
		<span><xsl:value-of select="$param/deckname" /></span>
		<span id="cost_per_turn" title="average cost per turn (bricks, gems, recruits)">
			<b><xsl:value-of select="$param/res/Bricks"/></b>
			<b><xsl:value-of select="$param/res/Gems"/></b>
			<b><xsl:value-of select="$param/res/Recruits"/></b>
		</span>
			<xsl:if test="$param/tokens != ''">
				<span><xsl:value-of select="$param/tokens" /></span>
			</xsl:if>
	</div>
		<xsl:if test="$param/note != ''">
			<div class="skin_text">
				<p><xsl:value-of select="$param/note" /></p>
			</div>
		</xsl:if>

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
	<a class="button" id="deck_note" href="{am:makeurl('Decks_note', 'CurrentDeck', $param/CurrentDeck)}" >
		<xsl:if test="$param/note != ''">
			<xsl:attribute name="class">button marked_button</xsl:attribute>
		</xsl:if>
		<xsl:text>Note</xsl:text>
	</a>

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

	<!-- share/unshare button -->
	<xsl:if test="$param/player_level &gt;= 10">
		<xsl:choose>
			<xsl:when test="$param/shared = 'yes'">
				<button type="submit" name="unshare_deck">Unshare deck</button>
			</xsl:when>
			<xsl:otherwise>
				<button type="submit" name="share_deck">Share deck</button>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:if>

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

	<!-- sorting options -->
	<xsl:variable name="card_sort">
		<value name="Sort by name" value="name" />
		<value name="Sort by cost" value="cost" />
	</xsl:variable>
	<xsl:copy-of select="am:htmlSelectBox('card_sort', $param/card_sort, $card_sort, '')"/>

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
	<!-- sort cards in card pool -->
	<xsl:variable name="card_list">
		<xsl:choose>
			<!-- sort by total card cost -->
			<xsl:when test="$param/card_sort = 'cost'">
				<xsl:for-each select="$param/CardList/*">
					<xsl:sort select="bricks + gems + recruits" order="ascending" data-type="number"/>
					<xsl:copy-of select="." />
				</xsl:for-each>
			</xsl:when>
			<!-- sort by card name (default sorting) -->
			<xsl:otherwise>
				<xsl:for-each select="$param/CardList/*">
					<xsl:sort select="name" order="ascending"/>
					<xsl:copy-of select="." />
				</xsl:for-each>
			</xsl:otherwise>
		</xsl:choose>
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

	<!-- deck note dialog (do not display) -->
	<div id="deck_note_dialog" title="Deck note" style="display: none">
		<textarea name="Content" rows="10" cols="50"><xsl:value-of select="$param/note"/></textarea>
	</div>
	
</xsl:template>


<xsl:template match="section[. = 'Decks_note']">
	<xsl:variable name="param" select="$params/deck_note" />

	<div id="deck_note">

	<h3>Deck note</h3>

	<div class="skin_text">
		<a class="button" href="{am:makeurl('Decks_edit', 'CurrentDeck', $param/CurrentDeck)}">Back</a>
		<button type="submit" name="save_dnote_return">Save &amp; return</button>
		<button type="submit" name="save_dnote">Save</button>
		<button type="submit" name="clear_dnote">Clear</button>
		<button type="submit" name="clear_dnote_return">Clear &amp; return</button>
		<hr/>

		<textarea name="Content" rows="10" cols="50"><xsl:value-of select="$param/text"/></textarea>
	</div>

		<input type="hidden" name="CurrentDeck" value="{$param/CurrentDeck}"/>
	</div>

</xsl:template>


</xsl:stylesheet>
