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

		<div class="filters">
			<!-- card rarity filter -->
			<xsl:variable name="classes">
				<value name="Common"  >Common</value>
				<value name="Uncommon">Uncommon</value>
				<value name="Rare"    >Rare</value>
				<value name="Any"     >none</value>
			</xsl:variable>
			<xsl:copy-of select="am:filter('ClassFilter', $param/ClassFilter, exsl:node-set($classes))"/>

			<!-- card keyword filter -->
			<xsl:variable name="keywords">
				<value name="No keyword filter">none</value>
				<value name="Any keyword"      >Any keyword</value>
				<value name="No keywords"      >No keywords</value>
			</xsl:variable>
			<xsl:copy-of select="am:filter('KeywordFilter', $param/KeywordFilter, exsl:node-set($keywords) | am:array2values($param/keywords))"/>

			<!-- cost filter -->
			<xsl:variable name="costs">
				<value name="No cost filter">none</value>
				<value name="Bricks only"   >Red</value>
				<value name="Gems only"     >Blue</value>
				<value name="Recruits only" >Green</value>
				<value name="Zero cost"     >Zero</value>
				<value name="Mixed cost"    >Mixed</value>
			</xsl:variable>
			<xsl:copy-of select="am:filter('CostFilter', $param/CostFilter, exsl:node-set($costs))"/>

			<!-- advanced filter select menu - filters based upon appearance in card text -->
			<xsl:variable name="advanced">
				<value name="No adv. filter">none</value>
				<value name="Attack"        >Attack:</value>
				<value name="Discard"       >Discard</value>
				<value name="Replace"       >Replace</value>
				<value name="Reveal"        >Reveal</value>
				<value name="Production"    >Production</value>
				<value name="Wall +"        >Wall: +</value>
				<value name="Wall -"        >Wall: -</value>
				<value name="Tower +"       >Tower: +</value>
				<value name="Tower -"       >Tower: -</value>
				<value name="Facilities +"  >Facilities: +</value>
				<value name="Facilities -"  >Facilities: -</value>
				<value name="Magic +"       >Magic: +</value>
				<value name="Magic -"       >Magic: -</value>
				<value name="Quarry +"      >Quarry: +</value>
				<value name="Quarry -"      >Quarry: -</value>
				<value name="Dungeon +"     >Dungeon: +</value>
				<value name="Dungeon -"     >Dungeon: -</value>
				<value name="Stock +"       >Stock: +</value>
				<value name="Stock -"       >Stock: -</value>
				<value name="Gems +"        >Gems: +</value>
				<value name="Gems -"        >Gems: -</value>
				<value name="Bricks +"      >Bricks: +</value>
				<value name="Bricks -"      >Bricks: -</value>
				<value name="Recruits +"    >Recruits: +</value>
				<value name="Recruits -"    >Recruits: -</value>
			</xsl:variable>
			<xsl:copy-of select="am:filter('AdvancedFilter', $param/AdvancedFilter, exsl:node-set($advanced))"/>

			<!-- support keyword filter -->
			<xsl:variable name="support">
				<value name="No support filter">none</value>
				<value name="Any keyword"      >Any keyword</value>
				<value name="No keywords"      >No keywords</value>
			</xsl:variable>
			<xsl:copy-of select="am:filter('SupportFilter', $param/SupportFilter, exsl:node-set($support) | am:array2values($param/keywords))"/>

			<!-- creation date filter -->
			<xsl:variable name="created">
				<value name="No created filter">none</value>
			</xsl:variable>
			<xsl:variable name="creation_dates">
				<xsl:for-each select="$param/created_dates/*">
					<value name="{am:format-date(text())}"><xsl:value-of select="text()"/></value>
				</xsl:for-each>
			</xsl:variable>
			<xsl:copy-of select="am:filter('CreatedFilter', $param/CreatedFilter, exsl:node-set($created) | exsl:node-set($creation_dates))"/>

			<!-- modification date filter -->
			<xsl:variable name="modified">
				<value name="No modified filter">none</value>
			</xsl:variable>
			<xsl:variable name="modification_dates">
				<xsl:for-each select="$param//modified_dates/*">
					<value name="{am:format-date(text())}"><xsl:value-of select="text()"/></value>
				</xsl:for-each>
			</xsl:variable>
			<xsl:copy-of select="am:filter('ModifiedFilter', $param/ModifiedFilter, exsl:node-set($modified) | exsl:node-set($modification_dates))"/>

			<input type="submit" name="cards_filter" value="Apply filters" />

			<!-- navigation -->
			<xsl:copy-of select="am:upper_navigation($param/page_count, $param/current_page, 'cards')"/>
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

		<div class="filters">
			<!-- lower navigation -->
			<xsl:copy-of select="am:lower_navigation($param/page_count, $param/current_page, 'cards', 'Cards')"/>
		</div>

		</div>

		<input type="hidden" name="CurrentCardsPage" value="{$param/current_page}" />
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
