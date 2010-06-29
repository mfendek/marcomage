<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
                xmlns="http://www.w3.org/1999/xhtml"
                xmlns:am="http://arcomage.netvor.sk"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:exsl="http://exslt.org/common"
                extension-element-prefixes="exsl">
<xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes" doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" />


<xsl:template match="section[. = 'Deck_edit']">
	<xsl:variable name="param" select="$params/deck_edit" />

	<!-- remember the current location across pages -->
	<div>
		<input type="hidden" name="CurrentDeck" value="{$param/CurrentDeck}"/>
	</div>

	<div class="filters">

	<div id="cost_per_turn">
		<xsl:text>Avg cost / turn</xsl:text>
		<b><xsl:value-of select="$param/Res/Bricks"/></b>
		<b><xsl:value-of select="$param/Res/Gems"/></b>
		<b><xsl:value-of select="$param/Res/Recruits"/></b>
	</div>

	<xsl:variable name="classes">
		<class name="Common"   />
		<class name="Uncommon" />
		<class name="Rare"     />
	</xsl:variable>

	<select name="ClassFilter">
		<xsl:if test="$param/ClassFilter != 'none'">
			<xsl:attribute name="class">filter_active</xsl:attribute>
		</xsl:if>
		<xsl:for-each select="exsl:node-set($classes)/*">
		<option value="{@name}">
			<xsl:if test="$param/ClassFilter = @name">
				<xsl:attribute name="selected">selected</xsl:attribute>
			</xsl:if>
			<xsl:value-of select="@name"/>
		</option>
		</xsl:for-each>
	</select>

	<select name="KeywordFilter">
		<xsl:if test="$param/KeywordFilter != 'none'">
				<xsl:attribute name="class">filter_active</xsl:attribute>
		</xsl:if>
		<option value="none">
			<xsl:if test="$param/KeywordFilter = 'none'">
				<xsl:attribute name="selected">selected</xsl:attribute>
			</xsl:if>
			<xsl:text>No keyword filters</xsl:text>
		</option>
		<option value="Any keyword">
			<xsl:if test="$param/KeywordFilter = 'Any keyword'">
				<xsl:attribute name="selected">selected</xsl:attribute>
			</xsl:if>
			<xsl:text>Any keyword</xsl:text>
		</option>
		<option value="No keywords">
			<xsl:if test="$param/KeywordFilter = 'No keywords'">
				<xsl:attribute name="selected">selected</xsl:attribute>
			</xsl:if>
			<xsl:text>No keywords</xsl:text>
		</option>	
		<xsl:for-each select="$param/keywords/*">
			<option value="{text()}">
				<xsl:if test="$param/KeywordFilter = .">
					<xsl:attribute name="selected">selected</xsl:attribute>
				</xsl:if>
				<xsl:value-of select="text()"/>
			</option>
		</xsl:for-each>
	</select>

	<xsl:variable name="costs">
		<cost name="none"  text="No cost filters" />
		<cost name="Red"   text="Bricks only"     />
		<cost name="Blue"  text="Gems only"       />
		<cost name="Green" text="Recruits only"   />
		<cost name="Zero"  text="Zero cost"       />
		<cost name="Mixed" text="Mixed cost"      />
	</xsl:variable>

	<select name="CostFilter">
		<xsl:if test="$param/CostFilter != 'none'">
			<xsl:attribute name="class">filter_active</xsl:attribute>
		</xsl:if>
		<xsl:for-each select="exsl:node-set($costs)/*">
			<option value="{@name}">
				<xsl:if test="$param/CostFilter = @name">
					<xsl:attribute name="selected">selected</xsl:attribute>
				</xsl:if>
				<xsl:value-of select="@text"/>
			</option>
		</xsl:for-each>
	</select>

	<!-- advanced filter select menu - filters based upon appearance in card text -->
	<xsl:variable name="advanced">
		<adv name="none"          text="No adv. filters" />
		<adv name="Attack:"       text="Attack"          />
		<adv name="Discard"       text="Discard"         />
		<adv name="Replace"       text="Replace"         />
		<adv name="Reveal"        text="Reveal"          />
		<adv name="Production"    text="Production"      />
		<adv name="Wall: +"       text="Wall +"          />
		<adv name="Wall: -"       text="Wall -"          />
		<adv name="Tower: +"      text="Tower +"         />
		<adv name="Tower: -"      text="Tower -"         />
		<adv name="Facilities: +" text="Facilities +"    />
		<adv name="Facilities: -" text="Facilities -"    />
		<adv name="Magic: +"      text="Magic +"         />
		<adv name="Magic: -"      text="Magic -"         />
		<adv name="Quarry: +"     text="Quarry +"        />
		<adv name="Quarry: -"     text="Quarry -"        />
		<adv name="Dungeon: +"    text="Dungeon +"       />
		<adv name="Dungeon: -"    text="Dungeon -"       />
		<adv name="Stock: +"      text="Stock +"         />
		<adv name="Stock: -"      text="Stock -"         />
		<adv name="Gems: +"       text="Gems +"          />
		<adv name="Gems: -"       text="Gems -"          />
		<adv name="Bricks: +"     text="Bricks +"        />
		<adv name="Bricks: -"     text="Bricks -"        />
		<adv name="Recruits: +"   text="Recruits +"      />
		<adv name="Recruits: -"   text="Recruits -"      />
	</xsl:variable>

	<select name="AdvancedFilter">
		<xsl:if test="$param/AdvancedFilter != 'none'">
			<xsl:attribute name="class">filter_active</xsl:attribute>
		</xsl:if>
		<xsl:for-each select="exsl:node-set($advanced)/*">
			<option value="{@name}">
				<xsl:if test="$param/AdvancedFilter = @name">
					<xsl:attribute name="selected">selected</xsl:attribute>
				</xsl:if>
				<xsl:value-of select="@text"/>
			</option>
		</xsl:for-each>
	</select>

	<select name="SupportFilter">
		<xsl:if test="$param/SupportFilter != 'none'">
				<xsl:attribute name="class">filter_active</xsl:attribute>
		</xsl:if>
		<option value="none">
			<xsl:if test="$param/SupportFilter = 'none'">
				<xsl:attribute name="selected">selected</xsl:attribute>
			</xsl:if>
			<xsl:text>No support filters</xsl:text>
		</option>
		<option value="Any keyword">
			<xsl:if test="$param/SupportFilter = 'Any keyword'">
				<xsl:attribute name="selected">selected</xsl:attribute>
			</xsl:if>
			<xsl:text>Any keyword</xsl:text>
		</option>
		<option value="No keywords">
			<xsl:if test="$param/SupportFilter = 'No keywords'">
				<xsl:attribute name="selected">selected</xsl:attribute>
			</xsl:if>
			<xsl:text>No keywords</xsl:text>
		</option>	
		<xsl:for-each select="$param/keywords/*">
			<option value="{text()}">
				<xsl:if test="$param/SupportFilter = .">
					<xsl:attribute name="selected">selected</xsl:attribute>
				</xsl:if>
				<xsl:value-of select="text()"/>
			</option>
		</xsl:for-each>
	</select>

	<select name="CreatedFilter">
		<xsl:if test="$param/CreatedFilter != 'none'">
			<xsl:attribute name="class">filter_active</xsl:attribute>
		</xsl:if>
		<option value="none">
			<xsl:if test="$param/CreatedFilter = 'none'">
				<xsl:attribute name="selected">selected</xsl:attribute>
			</xsl:if>
			<xsl:text>No created filters</xsl:text>
		</option>
		<xsl:for-each select="$param/created_dates/*">
			<option value="{text()}">
				<xsl:if test="$param/CreatedFilter = .">
					<xsl:attribute name="selected">selected</xsl:attribute>
				</xsl:if>
				<xsl:value-of select="am:format-date(text())"/>
			</option>
		</xsl:for-each>
	</select>

	<select name="ModifiedFilter">
		<xsl:if test="$param/ModifiedFilter != 'none'">
			<xsl:attribute name="class">filter_active</xsl:attribute>
		</xsl:if>
		<option value="none">
			<xsl:if test="$param/ModifiedFilter = 'none'">
				<xsl:attribute name="selected">selected</xsl:attribute>
			</xsl:if>
			<xsl:text>No modified filters</xsl:text>
		</option>
		<xsl:for-each select="$param/modified_dates/*">
			<option value="{text()}">
				<xsl:if test="$param/ModifiedFilter = .">
					<xsl:attribute name="selected">selected</xsl:attribute>
				</xsl:if>
				<xsl:value-of select="am:format-date(text())"/>
			</option>
		</xsl:for-each>
	</select>

	<input type="submit" name="filter" value="Apply filters" />
	
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

		<input type="submit" name="set_tokens" value="Set" />
		<input type="submit" name="auto_tokens" value="Auto" />
	</div>

	<input type="text" name="NewDeckName" value="{$param/CurrentDeck}" maxlength="20" />
	<input type="submit" name="rename_deck" value="Rename" />

	<xsl:choose>
		<xsl:when test="$param/reset = 'no'">
			<input type="submit" name="reset_deck_prepare" value="Reset" />
		</xsl:when>
		<xsl:otherwise>
			<input type="submit" name="reset_deck_confirm" value="Confirm reset" />
		</xsl:otherwise>
	</xsl:choose>

	<input type="submit" name="export_deck" value="Export" />
	<input name="uploadedfile" type="file" style="color: white"/>
	<input type="submit" name="import_deck" value="Import" />

	</div>

	<!-- cards in card pool -->
	<div class="scroll">
	<table cellpadding="0" cellspacing="0">
		<xsl:choose>
			<xsl:when test="count($param/CardList/*) &gt; 0">
				<tr valign="top">
					<xsl:for-each select="$param/CardList/*">
						<xsl:sort select="name" order="ascending"/>
						<td id="card_{id}" >
							<xsl:if test="excluded = 'no'">
								<xsl:attribute name="onclick">return TakeCard(<xsl:value-of select="id" />)</xsl:attribute>
								<xsl:copy-of select="am:cardstring(current(), $param/c_img, $param/c_keywords, $param/c_text, $param/c_oldlook)" />
							</xsl:if>
						</td>
					</xsl:for-each>
				</tr>
				<xsl:if test="$param/Take = 'yes'">
				<tr>
					<xsl:for-each select="$param/CardList/*">
						<xsl:sort select="name" order="ascending"/>
						<!-- if the deck's $classfilter section isn't full yet, display the button that adds the card -->
						<td><xsl:if test="excluded = 'no'"><noscript><div><input type="submit" name="add_card[{id}]" value="Take" /></div></noscript></xsl:if></td>
					</xsl:for-each>
				</tr>
				</xsl:if>
			</xsl:when>
			<xsl:otherwise>
				<tr><td></td></tr>
			</xsl:otherwise>
		</xsl:choose>
	</table>
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
							<xsl:copy-of select="am:cardstring(current(), $param/c_img, $param/c_keywords, $param/c_text, $param/c_oldlook)" />
							<xsl:if test="id != 0">
								<noscript><div><input type="submit" name="return_card[{id}]" value="Return" /></div></noscript>
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
