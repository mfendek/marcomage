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
	<xsl:variable name="colors" select="document('colors.xml')/am:colors"/>		

	<!-- remember the current location across pages -->
	<div>
		<input type="hidden" name="CurrentDeck" value="{$param/CurrentDeck}"/>
	</div>

	<div class="filters">

	<div id="cost_per_turn">
		Avg cost / turn:
		<b>
			<xsl:attribute name="style">color: <xsl:value-of select="$colors/am:color[am:name='RosyBrown']/am:code"/></xsl:attribute>
			<xsl:value-of select="$param/Res/Bricks"/>
		</b>
		<b>
			<xsl:attribute name="style">color: <xsl:value-of select="$colors/am:color[am:name='DeepSkyBlue']/am:code"/></xsl:attribute>
			<xsl:value-of select="$param/Res/Gems"/>
		</b>
		<b>
			<xsl:attribute name="style">color: <xsl:value-of select="$colors/am:color[am:name='DarkSeaGreen']/am:code"/></xsl:attribute>
			<xsl:value-of select="$param/Res/Recruits"/>
		</b>
	</div>

	<xsl:variable name="classes">
		<class name="Common"   />
		<class name="Uncommon" />
		<class name="Rare"     />
	</xsl:variable>

	<select name="ClassFilter">
		<xsl:if test="$param/ClassFilter != 'none'">
			<xsl:attribute name="style">border-color: lime</xsl:attribute>
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
				<xsl:attribute name="style">border-color: lime</xsl:attribute>
		</xsl:if>
		<option value="none">
			<xsl:if test="$param/KeywordFilter = 'none'">
				<xsl:attribute name="selected">selected</xsl:attribute>
			</xsl:if>
			No keyword filters
		</option>
		<option value="Any keyword">
			<xsl:if test="$param/KeywordFilter = 'Any keyword'">
				<xsl:attribute name="selected">selected</xsl:attribute>
			</xsl:if>
			Any keyword
		</option>
		<option value="No keywords">
			<xsl:if test="$param/KeywordFilter = 'No keywords'">
				<xsl:attribute name="selected">selected</xsl:attribute>
			</xsl:if>
			No keywords
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
			<xsl:attribute name="style">border-color: lime</xsl:attribute>
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
		<adv name="none"        text="No adv. filters" />
		<adv name="Attack:"     text="Attack"          />
		<adv name="discard"     text="Discard"         />
		<adv name="Production"  text="Production"      />
		<adv name="Wall: +"     text="Wall +"          />
		<adv name="Wall: -"     text="Wall -"          />
		<adv name="Tower: +"    text="Tower +"         />
		<adv name="Tower: -"    text="Tower -"         />
		<adv name="Stock: +"    text="Stock +"         />
		<adv name="Stock: -"    text="Stock -"         />
		<adv name="Magic: +"    text="Magic +"         />
		<adv name="Magic: -"    text="Magic -"         />
		<adv name="Quarry: +"   text="Quarry +"        />
		<adv name="Quarry: -"   text="Quarry -"        />
		<adv name="Dungeon: +"  text="Dungeon +"       />
		<adv name="Dungeon: -"  text="Dungeon -"       />
		<adv name="Gems: +"     text="Gems +"          />
		<adv name="Gems: -"     text="Gems -"          />
		<adv name="Bricks: +"   text="Bricks +"        />
		<adv name="Bricks: -"   text="Bricks -"        />
		<adv name="Recruits: +" text="Recruits +"      />
		<adv name="Recruits: -" text="Recruits -"      />
	</xsl:variable>

	<select name="AdvancedFilter">
		<xsl:if test="$param/AdvancedFilter != 'none'">
			<xsl:attribute name="style">border-color: lime</xsl:attribute>
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
				<xsl:attribute name="style">border-color: lime</xsl:attribute>
		</xsl:if>
		<option value="none">
			<xsl:if test="$param/SupportFilter = 'none'">
				<xsl:attribute name="selected">selected</xsl:attribute>
			</xsl:if>
			No support filters
		</option>
		<option value="Any keyword">
			<xsl:if test="$param/SupportFilter = 'Any keyword'">
				<xsl:attribute name="selected">selected</xsl:attribute>
			</xsl:if>
			Any keyword
		</option>
		<option value="No keywords">
			<xsl:if test="$param/SupportFilter = 'No keywords'">
				<xsl:attribute name="selected">selected</xsl:attribute>
			</xsl:if>
			No keywords
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

	<input type="submit" name="filter" value="Apply filters" />
	
	</div>	
	<div class="filters">

	<div id="tokens">
		<xsl:for-each select="$param/Tokens/*">
			<xsl:variable name="token" select="." />

			<select name="Token{position()}">
				<option value="none">
					<xsl:if test="$token = 'none'">
						<xsl:attribute name="selected">selected</xsl:attribute>
					</xsl:if>
					None
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

	<input type="text" class="text_data" name="NewDeckName" value="{$param/CurrentDeck}" maxlength="20" />
	<input type="submit" name="rename_deck" value="Rename" />

	<xsl:choose>
		<xsl:when test="$param/reset = 'no'">
			<input type="submit" name="reset_deck_prepare" value="Reset" />
		</xsl:when>
		<xsl:otherwise>
			<input type="submit" name="reset_deck_confirm" value="Confirm reset" />
		</xsl:otherwise>
	</xsl:choose>

	<xsl:choose>
		<xsl:when test="$param/randomize = 'no'">
			<input type="submit" name="randomize_deck_prepare" value="Randomize" />
		</xsl:when>
		<xsl:otherwise>
			<input type="submit" name="randomize_deck_confirm" value="Confirm randomize" />
		</xsl:otherwise>
	</xsl:choose>

	<input type="submit" name="finish_deck" value="Finish" />

	</div>

	<hr />

	<div class="scroll">
	<table cellpadding="0" cellspacing="0">
		<xsl:choose>
			<xsl:when test="$param/ListCount &gt; 0">
				<tr valign="top">
					<xsl:for-each select="$param/CardList/*">
						<td>
							<xsl:value-of select="CardString" disable-output-escaping="yes" />
						</td>
					</xsl:for-each>
				</tr>
				<xsl:if test="$param/Take = 'yes'">
				<tr>
					<xsl:for-each select="$param/CardList/*">
						<!-- if the deck's $classfilter section isn't full yet, display the button that adds the card -->
						<td><input type="submit" name="add_card[{CardID}]" value="Take" /></td>
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

	<table class="deck" cellpadding="0" cellspacing="0" >

		<xsl:variable name="rows">
			<adv name="Common"   text="Lime"    />
			<adv name="Uncommon" text="DarkRed" />
			<adv name="Rare"     text="Yellow"  />
		</xsl:variable>

		<tr>
		<xsl:for-each select="exsl:node-set($rows)/*">
			<th>
				<p>
				<xsl:variable name="color" select="@text" />
				<xsl:attribute name="style">color: <xsl:value-of select="$colors/am:color[am:name = $color]/am:code"/></xsl:attribute>
				<xsl:value-of select="@name"/>
				</p>
			</th>
		</xsl:for-each>
		</tr>

		<tr valign="top">
		<xsl:for-each select="$param/DeckCards/*">
			<td>
				<table class="centered" cellpadding="0" cellspacing="0">
				<xsl:for-each select="./*">
				<tr>
					<xsl:for-each select="./*">
						<td>
							<xsl:value-of select="CardString" disable-output-escaping="yes" />
							<xsl:if test="CardID != 0">
								<input type="submit" name="return_card[{CardID}]" value="Return" />
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
