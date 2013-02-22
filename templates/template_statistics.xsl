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


<xsl:template match="section[. = 'Statistics']">
	<xsl:variable name="param" select="$params/statistics" />

<div id="statistics">
	<!-- subsection navigation -->
	<div class="filters">
		<xsl:variable name="types">
			<type name="Played - latest"     value="Played"         />
			<type name="Played - overall"    value="PlayedTotal"    />
			<type name="Discarded - latest"  value="Discarded"      />
			<type name="Discarded - overall" value="DiscardedTotal" />
			<type name="Drawn - latest"      value="Drawn"          />
			<type name="Drawn - overall"     value="DrawnTotal"     />
		</xsl:variable>

		<select name="selected_statistic">
			<xsl:if test="$param/current_subsection = 'card_statistics'">
				<xsl:attribute name="class">filter_active</xsl:attribute>
			</xsl:if>
			<xsl:for-each select="exsl:node-set($types)/*">
			<option value="{@value}">
				<xsl:if test="$param/current_statistic = @value">
					<xsl:attribute name="selected">selected</xsl:attribute>
				</xsl:if>
				<xsl:value-of select="@name"/>
			</option>
			</xsl:for-each>
		</select>

		<xsl:variable name="sizes">
			<size name="10"       value="10"   />
			<size name="15"       value="15"   />
			<size name="20"       value="20"   />
			<size name="30"       value="30"   />
			<size name="50"       value="50"   />
			<size name="Show all" value="full" />
		</xsl:variable>

		<select name="selected_size">
			<xsl:if test="$param/current_subsection = 'card_statistics'">
				<xsl:attribute name="class">filter_active</xsl:attribute>
			</xsl:if>
			<xsl:for-each select="exsl:node-set($sizes)/*">
			<option value="{@value}">
				<xsl:if test="$param/current_size = @value">
					<xsl:attribute name="selected">selected</xsl:attribute>
				</xsl:if>
				<xsl:value-of select="@name"/>
			</option>
			</xsl:for-each>
		</select>

		<button type="submit" name="card_statistics">Select</button>
		<button type="submit" name="other_statistics">
			<xsl:if test="$param/current_subsection = 'other_statistics'">
				<xsl:attribute name="class">pushed</xsl:attribute>
			</xsl:if>
			<xsl:text>Other statistics</xsl:text>
		</button>
	</div>

	<div class="skin_label">

	<xsl:choose>
		<!-- begin subsection card statistics -->
		<xsl:when test="$param/current_subsection = 'card_statistics'">
			<xsl:for-each select="$param/card_statistics/*">
				<div class="skin_text">
					<h4><xsl:value-of select="name()"/> cards</h4>
					<xsl:if test="count(bottom/*) &gt; 0"><h5>Top</h5></xsl:if>
					<ol>
					<xsl:for-each select="top/*">
						<li>
							<span><xsl:value-of select="factor"/></span>
							<a href="{am:makeurl('Cards_details', 'card', id)}"><xsl:value-of select="name"/></a>
						</li>
					</xsl:for-each>
					</ol>
					<xsl:if test="count(bottom/*) &gt; 0">
						<h5>Bottom</h5>
						<ol>
						<xsl:for-each select="bottom/*">
							<li>
								<span><xsl:value-of select="factor"/></span>
								<a href="{am:makeurl('Cards_details', 'card', id)}"><xsl:value-of select="name"/></a>
							</li>
						</xsl:for-each>
						</ol>
					</xsl:if>
				</div>
			</xsl:for-each>
		</xsl:when>
		<!-- end subsection card statistics -->

		<!-- begin subsection other statistics -->
		<xsl:when test="$param/current_subsection = 'other_statistics'">
			<div class="skin_text">
				<h4>Backgrounds</h4>
				<xsl:for-each select="$param/backgrounds/*">
					<xsl:sort select="count" order="descending" data-type="number" />
					<p>
						<span><xsl:value-of select="count"/>%</span>
						<xsl:value-of select="name"/>
					</p>
				</xsl:for-each>
			</div>

			<div class="skin_text">
				<h4>Skins</h4>
				<xsl:for-each select="$param/skins/*">
					<xsl:sort select="count" order="descending" data-type="number" />
					<p>
						<span><xsl:value-of select="count"/>%</span>
						<xsl:value-of select="name"/>
					</p>
				</xsl:for-each>

				<h4>Game modes</h4>
				<p><span><xsl:value-of select="$param/game_modes/hidden"/>%</span>Hidden cards</p>
				<p><span><xsl:value-of select="$param/game_modes/friendly"/>%</span>Friendly play</p>
				<p><span><xsl:value-of select="$param/game_modes/long"/>%</span>Long mode</p>
				<p><span><xsl:value-of select="$param/game_modes/ai"/>%</span>AI mode</p>
				<p><span><xsl:value-of select="$param/game_modes/ai_wins"/>%</span>AI win ratio</p>
				<p><span><xsl:value-of select="$param/game_modes/challenge"/>%</span>AI challenge</p>
				<p><span><xsl:value-of select="$param/game_modes/challenge_wins"/>%</span>AI challenge win ratio</p>

				<h4>Victory types</h4>
				<xsl:for-each select="$param/victory_types/*">
					<xsl:sort select="count" order="descending" data-type="number" />
					<p>
						<span><xsl:value-of select="count"/>%</span>
						<xsl:value-of select="type"/>
					</p>
				</xsl:for-each>
			</div>

			<div class="skin_text">
				<h4>Suggested concepts</h4>
				<ol>
				<xsl:for-each select="$param/suggested/*">
					<li>
						<span><xsl:value-of select="count"/></span>
						<a class="profile" href="{am:makeurl('Players_details', 'Profile', Author)}"><xsl:value-of select="Author"/></a>
					</li>
				</xsl:for-each>
				</ol>

				<h4>Implemented concepts</h4>
				<ol>
				<xsl:for-each select="$param/implemented/*">
					<li>
						<span><xsl:value-of select="count"/></span>
						<a class="profile" href="{am:makeurl('Players_details', 'Profile', Author)}"><xsl:value-of select="Author"/></a>
					</li>
				</xsl:for-each>
				</ol>
			</div>
		</xsl:when>
		<!-- end subsection other statistics -->
	</xsl:choose>

	<div class="clear_floats"></div>

	</div>
</div>

</xsl:template>


</xsl:stylesheet>
