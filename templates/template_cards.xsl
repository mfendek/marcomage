<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
                xmlns="http://www.w3.org/1999/xhtml"
                xmlns:am="http://arcomage.netvor.sk"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:date="http://exslt.org/dates-and-times"
                xmlns:exsl="http://exslt.org/common"
                xmlns:str="http://exslt.org/strings"
                extension-element-prefixes="date exsl str">
<xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes" doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" />


<xsl:template match="section[. = 'Cards']">
	<xsl:variable name="param" select="$params/cards" />

<div id="cards">
		<h3>Cards</h3>

		<div id="cards_table">

		<!-- begin buttons and filters -->

		<div class="filters_trans">
			<xsl:variable name="classes">
				<class name="Common"   text="Common"   />
				<class name="Uncommon" text="Uncommon" />
				<class name="Rare"     text="Rare"     />
				<class name="none"     text="Any"      />
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
					<xsl:value-of select="@text"/>
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

			<input type="submit" name="cards_filter" value="Apply filters" />
		</div>
		<!-- end buttons and filters -->

		<table cellspacing="0" class="skin_text">
			<tr>
				<th>Card</th>
				<th></th>
				<th><p>Card name</p></th>
				<th><p>Rarity</p></th>
				<th><p>Cost</p></th>
				<th><p>Effect</p></th>
				<th><p>Created</p></th>
				<th><p>Modified</p></th>
			</tr>
			<xsl:for-each select="$param/CardList/*">
				<xsl:sort select="name" order="ascending"/>
				<tr>
					<td align="center"><xsl:copy-of select="am:cardstring(current(), $param/c_img, $param/c_keywords, $param/c_text, $param/c_oldlook)" /></td>
					<td><p><input type="submit" name="view_card[{id}]" value="+" /></p></td>
					<td><p><xsl:value-of select="name"/></p></td>
					<td><p><xsl:value-of select="class"/></p></td>
					<td><p><xsl:value-of select="bricks" />/<xsl:value-of select="gems" />/<xsl:value-of select="recruits" /></p></td>
					<td><p class="effect"><xsl:value-of select="am:cardeffect(effect)" disable-output-escaping="yes"/></p></td>
					<td><p><xsl:value-of select="am:format-date(created)"/></p></td>
					<td><p><xsl:value-of select="am:format-date(modified)"/></p></td>
				</tr>
			</xsl:for-each>
		</table>

		</div>

</div>

</xsl:template>


<xsl:template match="section[. = 'Cards_details']">
	<xsl:variable name="param" select="$params/cards_details" />

	<div id="cards_details">

		<h3>Card details</h3>

		<div id="card_details" class="skin_text">
			<input type="submit" name="Cards" value="Back" />
			<xsl:choose>
				<xsl:when test="$param/discussion = 'no' and $param/create_thread = 'yes'">
					<input type="submit" name="card_thread[{$param/data/id}]" value="Start discussion" />
				</xsl:when>
				<xsl:when test="$param/discussion = 'yes'">
					<input type="submit" name="card_thread[{$param/data/id}]" value="View discussion" />
				</xsl:when>
			</xsl:choose>
			<hr />

			<div class="card_preview"><xsl:copy-of select="am:cardstring($param/data, $param/c_img, $param/c_keywords, $param/c_text, $param/c_oldlook)" /></div>
			<div class="limit">
				<p><span><xsl:value-of select="$param/data/name"/></span>Name</p>
				<p><span><xsl:value-of select="$param/data/class"/></span>Rarity</p>
				<p><span><xsl:value-of select="$param/data/keywords"/></span>Keywords</p>
				<p><span><xsl:value-of select="$param/data/bricks"/>/<xsl:value-of select="$param/data/gems"/>/<xsl:value-of select="$param/data/recruits"/></span>Cost (B/G/R)</p>
				<p><span><xsl:value-of select="$param/data/modes"/></span>Modes</p>
				<p><span><xsl:value-of select="am:format-date($param/data/created)"/></span>Created</p>
				<p><span><xsl:value-of select="am:format-date($param/data/modified)"/></span>Modified</p>
				<p><span><xsl:value-of select="$param/statistics/Played"/> / <xsl:value-of select="$param/statistics/PlayedTotal"/></span>Played</p>
				<p><span><xsl:value-of select="$param/statistics/Discarded"/> / <xsl:value-of select="$param/statistics/DiscardedTotal"/></span>Discarded</p>
				<p><span><xsl:value-of select="$param/statistics/Drawn"/> / <xsl:value-of select="$param/statistics/DrawnTotal"/></span>Drawn</p>
			</div>
			<p>Effect</p>
			<p><xsl:value-of select="am:cardeffect($param/data/effect)" disable-output-escaping="yes"/></p>
			<hr />
			<p>Code</p>
			<div><xsl:copy-of select="am:textencode($param/data/code)" /></div>
		</div>
	</div>

</xsl:template>


</xsl:stylesheet>
