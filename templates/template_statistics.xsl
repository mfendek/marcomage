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

	<div class="skin_label">
		<div class="skin_text">
			<h4>Most played (latest)</h4>
			<xsl:for-each select="$param/most_played/*">
				<p>
					<span><input type="submit" name="view_card[{id}]" value="+" /></span>
					<xsl:value-of select="position()"/>. <xsl:value-of select="name"/>
				</p>
			</xsl:for-each>
			<h4>Most discarded (latest)</h4>
			<xsl:for-each select="$param/most_discarded/*">
				<p>
					<span><input type="submit" name="view_card[{id}]" value="+" /></span>
					<xsl:value-of select="position()"/>. <xsl:value-of select="name"/>
				</p>
			</xsl:for-each>
		</div>

		<div class="skin_text">
			<h4>Least played (latest)</h4>
			<xsl:for-each select="$param/least_played/*">
				<p>
					<span><input type="submit" name="view_card[{id}]" value="+" /></span>
					<xsl:value-of select="position()"/>. <xsl:value-of select="name"/>
				</p>
			</xsl:for-each>
			<h4>Least discarded (latest)</h4>
			<xsl:for-each select="$param/least_discarded/*">
				<p>
					<span><input type="submit" name="view_card[{id}]" value="+" /></span>
					<xsl:value-of select="position()"/>. <xsl:value-of select="name"/>
				</p>
			</xsl:for-each>
		</div>
		<div class="skin_text">
			<h4>Most played (overall)</h4>
			<xsl:for-each select="$param/most_played_total/*">
				<p>
					<span><input type="submit" name="view_card[{id}]" value="+" /></span>
					<xsl:value-of select="position()"/>. <xsl:value-of select="name"/>
				</p>
			</xsl:for-each>
			<h4>Most discarded (overall)</h4>
			<xsl:for-each select="$param/most_discarded_total/*">
				<p>
					<span><input type="submit" name="view_card[{id}]" value="+" /></span>
					<xsl:value-of select="position()"/>. <xsl:value-of select="name"/>
				</p>
			</xsl:for-each>
		</div>

		<div class="skin_text">
			<h4>Least played (overall)</h4>
			<xsl:for-each select="$param/least_played_total/*">
				<p>
					<span><input type="submit" name="view_card[{id}]" value="+" /></span>
					<xsl:value-of select="position()"/>. <xsl:value-of select="name"/>
				</p>
			</xsl:for-each>
			<h4>Least discarded (overall)</h4>
			<xsl:for-each select="$param/least_discarded_total/*">
				<p>
					<span><input type="submit" name="view_card[{id}]" value="+" /></span>
					<xsl:value-of select="position()"/>. <xsl:value-of select="name"/>
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
		<div class="clear_floats"></div>
	</div>
</div>

</xsl:template>


</xsl:stylesheet>
