<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
                xmlns="http://www.w3.org/1999/xhtml"
                xmlns:am="http://arcomage.netvor.sk"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes" doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" />


<xsl:template match="section[. = 'Statistics']">
	<xsl:variable name="param" select="$params/statistics" />

<div id="statistics">
	<h3>Statistics</h3>

	<!-- subsection navigation -->
	<div class="filter_trans">
		<input type="submit" name="card_statistics[Played]" value="Played - latest">
			<xsl:if test="$param/current_subsection = 'card_statistics' and $param/current_statistic = 'Played'">
				<xsl:attribute name="class">pushed</xsl:attribute>
			</xsl:if>
		</input>

		<input type="submit" name="card_statistics[PlayedTotal]" value="Played - overall">
			<xsl:if test="$param/current_subsection = 'card_statistics' and $param/current_statistic = 'PlayedTotal'">
				<xsl:attribute name="class">pushed</xsl:attribute>
			</xsl:if>
		</input>

		<input type="submit" name="card_statistics[Discarded]" value="Discarded - latest">
			<xsl:if test="$param/current_subsection = 'card_statistics' and $param/current_statistic = 'Discarded'">
				<xsl:attribute name="class">pushed</xsl:attribute>
			</xsl:if>
		</input>

		<input type="submit" name="card_statistics[DiscardedTotal]" value="Discarded - overall">
			<xsl:if test="$param/current_subsection = 'card_statistics' and $param/current_statistic = 'DiscardedTotal'">
				<xsl:attribute name="class">pushed</xsl:attribute>
			</xsl:if>
		</input>

		<input type="submit" name="card_statistics[Drawn]" value="Drawn - latest">
			<xsl:if test="$param/current_subsection = 'card_statistics' and $param/current_statistic = 'Drawn'">
				<xsl:attribute name="class">pushed</xsl:attribute>
			</xsl:if>
		</input>

		<input type="submit" name="card_statistics[DrawnTotal]" value="Drawn - overall">
			<xsl:if test="$param/current_subsection = 'card_statistics' and $param/current_statistic = 'DrawnTotal'">
				<xsl:attribute name="class">pushed</xsl:attribute>
			</xsl:if>
		</input>

		<input type="submit" name="other_statistics" value="Other statistics">
			<xsl:if test="$param/current_subsection = 'other_statistics'">
				<xsl:attribute name="class">pushed</xsl:attribute>
			</xsl:if>
		</input>
	</div>

	<div class="skin_label">

	<xsl:choose>
		<!-- begin subsection card statistics -->
		<xsl:when test="$param/current_subsection = 'card_statistics'">
			<xsl:for-each select="$param/card_statistics/*">
				<div class="skin_text">
					<h4><xsl:value-of select="name()"/> cards</h4>
					<h5>Best</h5>
					<xsl:for-each select="top/*">
						<p>
							<span><input type="submit" name="view_card[{id}]" value="+" /></span>
							<xsl:value-of select="position()"/>. <xsl:value-of select="name"/>
						</p>
					</xsl:for-each>
					<h5>Worst</h5>
					<xsl:for-each select="bottom/*">
						<p>
							<span><input type="submit" name="view_card[{id}]" value="+" /></span>
							<xsl:value-of select="position()"/>. <xsl:value-of select="name"/>
						</p>
					</xsl:for-each>
				</div>
			</xsl:for-each>
		</xsl:when>
		<!-- end subsection card statistics -->

		<!-- begin subsection other statistics -->
		<xsl:when test="$param/current_subsection = 'other_statistics'">
			<div class="skin_text">
				<h4>Backgrounds</h4>
				<xsl:for-each select="$param/backgrounds/*">
					<xsl:sort select="name" order="ascending"/>
					<p>
						<span><xsl:value-of select="count"/>%</span>
						<xsl:value-of select="name"/>
					</p>
				</xsl:for-each>
			</div>

			<div class="skin_text">
				<h4>Skins</h4>
				<xsl:for-each select="$param/skins/*">
					<xsl:sort select="name" order="ascending"/>
					<p>
						<span><xsl:value-of select="count"/>%</span>
						<xsl:value-of select="name"/>
					</p>
				</xsl:for-each>

				<h4>Game modes</h4>
				<p><span><xsl:value-of select="$param/game_modes/hidden"/>%</span>Hidden cards</p>
				<p><span><xsl:value-of select="$param/game_modes/friendly"/>%</span>Friendly play</p>

				<h4>Victory types</h4>
				<xsl:for-each select="$param/victory_types/*">
					<p>
						<span><xsl:value-of select="count"/>%</span>
						<xsl:value-of select="type"/>
					</p>
				</xsl:for-each>
			</div>

			<div class="skin_text">
				<h4>Suggested concepts</h4>
				<xsl:for-each select="$param/suggested/*">
					<p>
						<span><xsl:value-of select="count"/></span>
						<xsl:value-of select="position()"/>. <xsl:value-of select="Author"/>
					</p>
				</xsl:for-each>

				<h4>Implemented concepts</h4>
				<xsl:for-each select="$param/implemented/*">
					<p>
						<span><xsl:value-of select="count"/></span>
						<xsl:value-of select="position()"/>. <xsl:value-of select="Author"/>
					</p>
				</xsl:for-each>
			</div>
		</xsl:when>
		<!-- end subsection other statistics -->
	</xsl:choose>

	<div class="clear_floats"></div>

	</div>
</div>

</xsl:template>


</xsl:stylesheet>
