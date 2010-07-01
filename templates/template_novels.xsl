<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet [ <!ENTITY minus "&#8722;"> ]>
<xsl:stylesheet version="1.0"
                xmlns="http://www.w3.org/1999/xhtml"
                xmlns:am="http://arcomage.netvor.sk"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes" doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" />


<xsl:template match="section[. = 'Novels']">
	<xsl:variable name="param" select="$params/novels" />
	
	<xsl:variable name="page" select="$param/page" />
	<xsl:variable name="pages" select="$param/pages" />

	<div id="novels">

		<!-- novel menu -->
		<div id="nov_float_left" class="skin_label">

		<h3>Novels menu</h3>

		<ul>
		<xsl:for-each select="$param/novelslist/*">
			<li>
			<xsl:choose>
			<!-- display expanded novel -->
			<xsl:when test="$param/novel = text()">
				<input type="submit" name="collapse_novel" value="&minus;" class="pushed" />
				<xsl:text>Book </xsl:text><xsl:value-of select="position()"/><xsl:text>: </xsl:text>
				<xsl:value-of select="text()"/>
				<ul>
					<xsl:for-each select="$param/chapterslist/*">
						<li>
							<xsl:choose>
							<!-- display expanded chapter -->
							<xsl:when test="$param/chapter = text()">
								<input type="submit" name="collapse_chapter" value="&minus;" class="pushed" />
								<xsl:text>Chapter </xsl:text><xsl:value-of select="position()"/><xsl:text>: </xsl:text>
								<xsl:value-of select="text()"/>
								<ul>
									<xsl:for-each select="$param/partslist/*">
										<li>
											<input type="submit" name="view_part[{text()}]" value=">">
												<xsl:if test="$param/part = text()"><xsl:attribute name="class">pushed</xsl:attribute></xsl:if>
											</input>
											<xsl:value-of select="text()"/>
										</li>
									</xsl:for-each>
								</ul>
							</xsl:when>
							<!-- display collapsed chapter -->
							<xsl:otherwise>
								<input type="submit" name="view_chapter[{text()}]" value="+">
									<xsl:if test="$param/chapter = text()"><xsl:attribute name="class">pushed</xsl:attribute></xsl:if>
								</input>
								<xsl:text>Chapter </xsl:text><xsl:value-of select="position()"/><xsl:text>: </xsl:text>
								<xsl:value-of select="text()"/>
							</xsl:otherwise>
							</xsl:choose>
						</li>
					</xsl:for-each>
				</ul>
			</xsl:when>
			<!-- display collapsed novel -->
			<xsl:otherwise>
				<input type="submit" name="view_novel[{text()}]" value="+" />
				<xsl:text>Book </xsl:text><xsl:value-of select="position()"/><xsl:text>: </xsl:text>
				<xsl:value-of select="text()"/>
			</xsl:otherwise>
			</xsl:choose>
			</li>
		</xsl:for-each>
		</ul>

		</div>

		<!-- novel content -->
		<div id="nov_float_right" class="skin_text">

		<xsl:choose>
		<xsl:when test="$param/part = ''">

		<!-- display welcome page -->
		<h3>Welcome to the Fantasy novels section</h3>

		<p>All novels are written by our external associate <a href="mailto:thomasteekanne@gmail.com">Lukáš Čajági</a>, and therefore all novels are exclusive property of the author. We will add new pieces of the novel once a week or so.</p>
		<br />
		<p>This novel will probably be a trilogy, the name is not known yet. The author provided us with description of the first book:</p>
		<p><b>"Only the fewest people in the world have the luxury of knowing who they really are, how hard it is to find something worth living for. Now try to imagine that upon finding it, it is snatched away from you forever. What would you do? Would you care to live on while the lives of everyone around you shatter? Could your consience bear the thought of you being responsible for the whole mess? This is the story of a man who has to cope with these questions."</b></p>
		<p><i>Trasymachos, former member of the Alchemist's Guild, outlawed fugitive</i></p>
		<br />
		<p>For feedback regarding novels please use the appropriate thread in the forum's 'Novels' section.</p>
		<br />
		<p>Please select a book and chapter you wish to read.</p>
		<br />
		<p>Copyright 2008-2010 Lukáš Čajági</p>

		</xsl:when>
		<!-- display content -->
		<xsl:otherwise>

		<!-- upper navigation -->
		<div class="navigation">
			<input class="previous" type="submit" name="select_page_novels[{am:max($page - 1, 1)}]" value="Previous">
				<xsl:if test="$page = 1"><xsl:attribute name="disabled">disabled</xsl:attribute></xsl:if>
			</input>

			<input class="next" type="submit" name="select_page_novels[{am:min($page + 1, $pages)}]" value="Next">
				<xsl:if test="$page = $pages"><xsl:attribute name="disabled">disabled</xsl:attribute></xsl:if>
			</input>

			<select name="page_selector">
				<xsl:for-each select="am:page_list($pages)">
					<option value="{. + 1}">
						<xsl:if test="$page = (. + 1)"><xsl:attribute name="selected">selected</xsl:attribute></xsl:if>
						<xsl:value-of select=". + 1"/>
					</option>
				</xsl:for-each>
			</select>
			<input type="submit" name="seek_page_novels" value="Select page" />
		</div>

		<!-- page content -->
		<xsl:value-of select="$param/content" disable-output-escaping="yes" />

		<!-- lower navigation -->
		<div class="navigation">
			<input class="previous" type="submit" name="select_page_novels[{am:max($page - 1, 1)}]" value="Previous">
				<xsl:if test="$page = 1"><xsl:attribute name="disabled">disabled</xsl:attribute></xsl:if>
			</input>

			<input type="submit" name="Novels" value="Back to top" />

			<input class="next" type="submit" name="select_page_novels[{am:min($page + 1, $pages)}]" value="Next">
				<xsl:if test="$page = $pages"><xsl:attribute name="disabled">disabled</xsl:attribute></xsl:if>
			</input>
		</div>

		</xsl:otherwise>
		</xsl:choose>

		</div>
		<div class="clear_floats"></div>

		<!-- navigation data -->
		<xsl:if test="$param/novel != ''"><input type="hidden" name="novel" value="{$param/novel}"/></xsl:if>
		<xsl:if test="$param/chapter != ''"><input type="hidden" name="chapter" value="{$param/chapter}"/></xsl:if>
		<xsl:if test="$param/part != ''"><input type="hidden" name="part" value="{$param/part}"/></xsl:if>
		<xsl:if test="$page != ''"><input type="hidden" name="page" value="{$page}"/></xsl:if>
	</div>
</xsl:template>


</xsl:stylesheet>
