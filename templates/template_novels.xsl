<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet [ <!ENTITY minus "&#8722;"> ]>
<xsl:stylesheet version="1.0"
                xmlns="http://www.w3.org/1999/xhtml"
                xmlns:am="http://arcomage.netvor.sk"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes" doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" />


<xsl:template match="section[. = 'Novels']">
	<xsl:variable name="param" select="$params/novels" />
	
	<xsl:variable name="novel" select="$param/current_novel" />
	<xsl:variable name="chapter" select="$param/current_chapter" />
	<xsl:variable name="page" select="$param/current_page" />
	<xsl:variable name="pagelist" select="$param/ListPages" />
	<xsl:variable name="content" select="$param/PageContent" />

	<div id="novels">
	
		<!-- novel menu -->
		<div id="nov_float_left">
			<h3>Novels menu</h3>
			<ul>
				<xsl:for-each select="$param/novelslist/*">
					<li>
						<xsl:choose>
						<xsl:when test="$novel = text()">
							<input type="submit" name="collapse_novel" value="&minus;" /><span class="novel_selected"><xsl:value-of select="text()"/></span>
							<ul>
								<xsl:for-each select="$param/chapterslist/*">
									<li>
										<input type="submit" name="view_chapter[{text()}]" value=">" />
										<span><xsl:if test="$chapter = text()"><xsl:attribute name="class">chapter_selected</xsl:attribute></xsl:if><xsl:value-of select="text()"/></span>
									</li>
								</xsl:for-each>
							</ul>
						</xsl:when>
						<xsl:otherwise>
							<input type="submit" name="view_novel[{text()}]" value="+" />
							<xsl:value-of select="text()"/>
						</xsl:otherwise>
						</xsl:choose>
					</li>
				</xsl:for-each>
			</ul>
		</div>

		<!-- novel content -->
		<div id="nov_float_right">

			<xsl:choose>
			<xsl:when test="$chapter = ''">

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
				<p>Copyright 2008 Lukáš Čajági</p>

			</xsl:when>
			<xsl:otherwise>

				<div class="navigation">
				
					<input class="previous" type="submit" name="select_page[{$page - 1}]" value="Previous">
						<xsl:if test="$page = 0"><xsl:attribute name="disabled">disabled</xsl:attribute></xsl:if>
					</input>

					<input class="next" type="submit" name="select_page[{$page + 1}]" value="Next">
						<xsl:if test="$page = count($pagelist/*) - 1"><xsl:attribute name="disabled">disabled</xsl:attribute></xsl:if>
					</input>

					<select name="jump_to_page">
						<xsl:for-each select="$pagelist/*">
							<option value="{position() - 1}">
								<xsl:if test="$page = position() - 1"><xsl:attribute name="selected">selected</xsl:attribute></xsl:if>
								<xsl:value-of select="text()"/>
							</option>
						</xsl:for-each>
					</select>
					<input type="submit" name="Jump" value="Select page" />

				</div>

				<xsl:value-of select="$content" disable-output-escaping="yes" />

				<div class="navigation">

					<input class="previous" type="submit" name="select_page[{$page - 1}]" value="Previous">
						<xsl:if test="$page = 0"><xsl:attribute name="disabled">disabled</xsl:attribute></xsl:if>
					</input>

					<input type="submit" name="Refresh[Novels]" value="Back to top" />

					<input class="next" type="submit" name="select_page[{$page + 1}]" value="Next">
						<xsl:if test="$page = count($pagelist/*) - 1"><xsl:attribute name="disabled">disabled</xsl:attribute></xsl:if>
					</input>

				</div>

			</xsl:otherwise>
			</xsl:choose>

		</div>

		<div class="clear_floats"></div>

		<xsl:if test="$novel != ''"><input type="hidden" name="current_novel" value="{$novel}"/></xsl:if>
		<xsl:if test="$chapter != ''"><input type="hidden" name="current_chapter" value="{$chapter}"/></xsl:if>
		<xsl:if test="$page != ''"><input type="hidden" name="current_page" value="{$page}"/></xsl:if>

	</div>
</xsl:template>


</xsl:stylesheet>
