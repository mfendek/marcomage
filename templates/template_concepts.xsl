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


<xsl:template match="section[. = 'Concepts']">
	<xsl:variable name="param" select="$params/concepts" />

	<div id="concepts">
		<h3>Card concepts</h3>

		<div id="concepts_table">

		<!-- begin buttons and filters -->

		<div class="filters_trans">
			<xsl:if test="$param/create_card = 'yes'">
				<input type="submit" name="new_card" value="New card" />
			</xsl:if>

			<!-- begin date filter -->

			<select name="date_filter">
				<xsl:if test="$param/date_val != 'none'">
						<xsl:attribute name="style">border-color: lime</xsl:attribute>
				</xsl:if>
				<option value="none">
					<xsl:if test="$param/date_val = 'none'">
						<xsl:attribute name="selected">selected</xsl:attribute>
					</xsl:if>
					No date filter
				</option>
				<xsl:for-each select="$param/timesections/*">
					<option value="{time}">
						<xsl:if test="$param/date_val = time">
							<xsl:attribute name="selected">selected</xsl:attribute>
						</xsl:if>
						<xsl:value-of select="text"/>
					</option>
				</xsl:for-each>
			</select>

			<!-- end date filter -->

			<xsl:if test="count($param/authors/*) &gt; 0">
			<!-- begin author filter -->

			<select name="author_filter">
				<xsl:if test="$param/author_val != 'none'">
					<xsl:attribute name="style">border-color: lime</xsl:attribute>
				</xsl:if>
				<option value="none">
					<xsl:if test="$param/author_val = 'none'">
						<xsl:attribute name="selected">selected</xsl:attribute>
					</xsl:if>
					No author filter
				</option>
				<xsl:for-each select="$param/authors/*">
					<option value="{am:urlencode(.)}">
						<xsl:if test="$param/author_val = .">
							<xsl:attribute name="selected">selected</xsl:attribute>
						</xsl:if>
						<xsl:value-of select="text()"/>
					</option>
				</xsl:for-each>
			</select>

			<!-- end author filter -->
			</xsl:if>

			<!-- begin state filter -->
			<xsl:variable name="states">
				<class name="waiting"     />
				<class name="rejected"    />
				<class name="implemented" />
			</xsl:variable>

			<select name="state_filter">
				<xsl:if test="$param/state_val != 'none'">
					<xsl:attribute name="style">border-color: lime</xsl:attribute>
				</xsl:if>
				<option value="none">
					<xsl:if test="$param/state_val = 'none'">
						<xsl:attribute name="selected">selected</xsl:attribute>
					</xsl:if>
					No state filter
				</option>
				<xsl:for-each select="exsl:node-set($states)/*">
				<option value="{@name}">
					<xsl:if test="$param/state_val = @name">
						<xsl:attribute name="selected">selected</xsl:attribute>
					</xsl:if>
					<xsl:value-of select="@name"/>
				</option>
				</xsl:for-each>
			</select>

			<input type="submit" name="concepts_filter" value="Apply filters" />
			<xsl:if test="$param/mycards = 'yes'">
				<input type="submit" name="my_concepts" value="My cards" />
			</xsl:if>

		<!-- upper navigation -->
				<xsl:if test="$param/page_count &gt; 0">
					<!-- previous button -->
					<input type="submit" name="select_page_con[{$param/current_page - 1}]" value="&lt;">
						<xsl:if test="$param/current_page &lt;= 0"><xsl:attribute name="disabled">disabled</xsl:attribute></xsl:if>
					</input>

					<!-- next button -->
					<input type="submit" name="select_page_con[{$param/current_page + 1}]" value="&gt;">
						<xsl:if test="$param/current_page &gt;= $param/page_count - 1"><xsl:attribute name="disabled">disabled</xsl:attribute></xsl:if>
					</input>

					<!-- page selector -->
					<select name="jump_to_page">
						<xsl:for-each select="$param/pages/*">
							<option value="{.}">
								<xsl:if test="$param/current_page = ."><xsl:attribute name="selected">selected</xsl:attribute></xsl:if>
								<xsl:value-of select="."/>
							</option>
						</xsl:for-each>
					</select>
					<input type="submit" name="Jump_concepts" value="Select page" />
				</xsl:if>
		<!-- end upper navigation -->

		<!-- end buttons and filters -->
		</div>
		
		<table cellspacing="0">
			<tr>
				<th>Card</th>
				<th>
					<p>Card name<input class="details" type="submit" >
						<xsl:choose>
							<xsl:when test="(($param/current_condition = 'Name') and ($param/current_order = 'DESC'))">
								<xsl:attribute name="name">concepts_ord_asc[Name]</xsl:attribute>
								<xsl:attribute name="value">\/</xsl:attribute>
							</xsl:when>
							<xsl:otherwise>
								<xsl:attribute name="name">concepts_ord_desc[Name]</xsl:attribute>
								<xsl:attribute name="value">/\</xsl:attribute>
							</xsl:otherwise>
						</xsl:choose>
						<xsl:if test="$param/current_condition = 'Name'">
							<xsl:attribute name="style">border-color: lime</xsl:attribute>
						</xsl:if>
					</input></p>
				</th>
				<th><p>Author</p></th>
				<th>
					<p>Last change<input class="details" type="submit" >
						<xsl:choose>
							<xsl:when test="(($param/current_condition = 'LastChange') and ($param/current_order = 'DESC'))">
								<xsl:attribute name="name">concepts_ord_asc[LastChange]</xsl:attribute>
								<xsl:attribute name="value">\/</xsl:attribute>
							</xsl:when>
							<xsl:otherwise>
								<xsl:attribute name="name">concepts_ord_desc[LastChange]</xsl:attribute>
								<xsl:attribute name="value">/\</xsl:attribute>
							</xsl:otherwise>
						</xsl:choose>
						<xsl:if test="$param/current_condition = 'LastChange'">
							<xsl:attribute name="style">border-color: lime</xsl:attribute>
						</xsl:if>
					</input></p>
				</th>
				<th><p>Note</p></th>
				<th><p>State</p></th>
				<th></th>
			</tr>
			<xsl:for-each select="$param/list/*">
				<tr class="table_row">
					<td align="center"><xsl:copy-of select="am:cardstring(current(), $param/c_img, $param/c_keywords, $param/c_text, $param/c_oldlook)" /></td>
					<td><p><xsl:value-of select="name"/></p></td>
					<td><p><xsl:value-of select="author"/></p></td>
					<td>
						<p>
							<xsl:if test="am:datediff(lastchange, $param/PreviousLogin) &lt; 0">
								<xsl:attribute name="style">color: orange</xsl:attribute>
							</xsl:if>
							<xsl:value-of select="am:datetime(lastchange, $param/timezone)"/>
						</p>
					</td>
					<td><p class="note"><xsl:value-of select="am:textencode(note)" disable-output-escaping="yes" /></p></td>
					<td><p><xsl:value-of select="state"/></p></td>
					<td>
						<p>
							<xsl:if test="$param/edit_all_card = 'yes' or ($param/edit_own_card = 'yes' and ($param/PlayerName = author))">
								<input class="details" type="submit" name="edit_card[{id}]" value="E" />
							</xsl:if>
							<xsl:if test="$param/delete_all_card = 'yes' or ($param/delete_own_card = 'yes' and ($param/PlayerName = author))">
								<input class="details" type="submit" name="delete_card[{id}]" value="D" />
							</xsl:if>
						</p>
					</td>
				</tr>
			</xsl:for-each>
		</table>

		<div class="filters_trans">
		<!-- lower navigation -->
				<xsl:if test="$param/page_count &gt; 0">
					<!-- previous button -->
					<input type="submit" name="select_page_con[{$param/current_page - 1}]" value="&lt;">
						<xsl:if test="$param/current_page &lt;= 0"><xsl:attribute name="disabled">disabled</xsl:attribute></xsl:if>
					</input>

					<input type="submit" name="Concepts" value="Back to top" />

					<!-- next button -->
					<input type="submit" name="select_page_con[{$param/current_page + 1}]" value="&gt;">
						<xsl:if test="$param/current_page &gt;= $param/page_count - 1"><xsl:attribute name="disabled">disabled</xsl:attribute></xsl:if>
					</input>
				</xsl:if>

		<!-- end lower navigation -->
		</div>

		</div>

		<input type="hidden" name="CurrentFilterChange" value="{$param/date_val}" />
		<input type="hidden" name="CurrentFilterAuthor" value="{$param/author_val}" />
		<input type="hidden" name="CurrentFilterState" value="{$param/state_val}" />
		<input type="hidden" name="CurrentConPage" value="{$param/current_page}" />
		<input type="hidden" name="CurrentOrder" value="{$param/current_order}" />
		<input type="hidden" name="CurrentCon" value="{$param/current_condition}" />
	</div>
</xsl:template>


<xsl:template match="section[. = 'Concepts_new']">
	<xsl:variable name="param" select="$params/concepts_new" />

	<div id="concepts_edit">

		<h3>New card</h3>

		<div id="card_edit">
			<input type="submit" name="Concepts" value="Back" />
			<input type="submit" name="create_card" value="Create card" />

			<hr />

			<div class="limit">
				<p>
					<span>
						<input type="text" name="name" maxlength="64" size="35" >
							<xsl:if test="$param/stored = 'yes'">
								<xsl:attribute name="value"><xsl:value-of select="$param/data/name"/></xsl:attribute>
							</xsl:if>
						</input>
					</span>Name</p>
				<p>
					<span>
						<xsl:variable name="classes">
							<class name="Common"   />
							<class name="Uncommon" />
							<class name="Rare"     />
						</xsl:variable>

						<select name="class">
							<xsl:for-each select="exsl:node-set($classes)/*">
							<option value="{@name}">
								<xsl:if test="$param/stored = 'yes' and $param/data/class = @name">
									<xsl:attribute name="selected">selected</xsl:attribute>
								</xsl:if>
								<xsl:value-of select="@name"/>
							</option>
							</xsl:for-each>
						</select>
					</span>
				Rarity</p>
				<p>
					<span>
						<input type="text" name="bricks" maxlength="2" size="2" >
							<xsl:attribute name="value">
								<xsl:choose>
									<xsl:when test="$param/stored = 'yes'">
										<xsl:value-of select="$param/data/bricks"/>
									</xsl:when>
									<xsl:otherwise>0</xsl:otherwise>
								</xsl:choose>
							</xsl:attribute>
						</input>
						<input type="text" name="gems" maxlength="2" size="2" >
							<xsl:attribute name="value">
								<xsl:choose>
									<xsl:when test="$param/stored = 'yes'">
										<xsl:value-of select="$param/data/gems"/>
									</xsl:when>
									<xsl:otherwise>0</xsl:otherwise>
								</xsl:choose>
							</xsl:attribute>
						</input>
						<input type="text" name="recruits" maxlength="2" size="2" >
							<xsl:attribute name="value">
								<xsl:choose>
									<xsl:when test="$param/stored = 'yes'">
										<xsl:value-of select="$param/data/recruits"/>
									</xsl:when>
									<xsl:otherwise>0</xsl:otherwise>
								</xsl:choose>
							</xsl:attribute>
						</input>
					</span>
				Cost (B/G/R)</p>
				<p><span>
					<input type="text" name="keywords" maxlength="100" size="35" >
						<xsl:if test="$param/stored = 'yes'">
							<xsl:attribute name="value"><xsl:value-of select="$param/data/keywords"/></xsl:attribute>
						</xsl:if>
					</input>
					</span>Keywords</p>
			</div>
			<p>Effect</p>
			<textarea name="effect" rows="6" cols="50">
				<xsl:if test="$param/stored = 'yes'">
					<xsl:value-of select="$param/data/effect"/>
				</xsl:if>
			</textarea>
			<p>Note</p>
			<textarea name="note" rows="6" cols="50">
				<xsl:if test="$param/stored = 'yes'">
					<xsl:value-of select="$param/data/note"/>
				</xsl:if>
			</textarea>
		</div>
	</div>

</xsl:template>


<xsl:template match="section[. = 'Concepts_edit']">
	<xsl:variable name="param" select="$params/concepts_edit" />

	<div id="concepts_edit">

		<h3>Edit card</h3>

		<div id="card_edit">
			<input type="submit" name="Concepts" value="Back" />
			<xsl:if test="$param/data/author = $param/PlayerName">
				<input type="submit" name="save_card" value="Save" />
			</xsl:if>
			<xsl:if test="$param/edit_all_card = 'yes'">
				<input type="submit" name="save_card_special" value="Special save" />
			</xsl:if>
			<xsl:if test="$param/delete_all_card = 'yes' or ($param/delete_own_card = 'yes' and $param/data/author = $param/PlayerName)">
				<xsl:choose>
					<xsl:when test="$param/delete = 'no'">
						<input type="submit" name="delete_card[{$param/data/id}]" value="Delete" />
					</xsl:when>
					<xsl:otherwise>
						<input type="submit" name="delete_card_confirm" value="Confirm delete" class="menuselected" />
					</xsl:otherwise>
				</xsl:choose>
			</xsl:if>
			<hr />

			<div class="card_preview"><xsl:copy-of select="am:cardstring($param/data, $param/c_img, $param/c_keywords, $param/c_text, $param/c_oldlook)" /></div>
			<div class="limit">
				<p>
					<span>
						<input type="text" name="name" maxlength="64" size="35" value="{$param/data/name}" />
					</span>Name</p>
				<p>
					<span>
						<xsl:variable name="classes">
							<class name="Common"   />
							<class name="Uncommon" />
							<class name="Rare"     />
						</xsl:variable>

						<select name="class">
							<xsl:for-each select="exsl:node-set($classes)/*">
							<option value="{@name}">
								<xsl:if test="$param/data/class = @name">
									<xsl:attribute name="selected">selected</xsl:attribute>
								</xsl:if>
								<xsl:value-of select="@name"/>
							</option>
							</xsl:for-each>
						</select>
					</span>
				Rarity</p>
				<p>
					<span>
						<input type="text" name="bricks" maxlength="2" size="2" value="{$param/data/bricks}" />
						<input type="text" name="gems" maxlength="2" size="2" value="{$param/data/gems}" />
						<input type="text" name="recruits" maxlength="2" size="2" value="{$param/data/recruits}" />
					</span>
				Cost (B/G/R)</p>
				<p><span>
					<input type="text" name="keywords" maxlength="100" size="35" value="{$param/data/keywords}" />
					</span>Keywords</p>
				<p>
					<span>
						<xsl:variable name="states">
							<class name="waiting"     />
							<class name="rejected"    />
							<class name="implemented" />
						</xsl:variable>

						<select name="state">
							<xsl:if test="$param/edit_all_card = 'no'">
								<xsl:attribute name="disabled">disabled</xsl:attribute>
							</xsl:if>
							<xsl:for-each select="exsl:node-set($states)/*">
							<option value="{@name}">
								<xsl:if test="$param/data/state = @name">
									<xsl:attribute name="selected">selected</xsl:attribute>
								</xsl:if>
								<xsl:value-of select="@name"/>
							</option>
							</xsl:for-each>
						</select>
					</span>
				State</p>
			</div>
			<p>Effect</p>
			<textarea name="effect" rows="6" cols="50"><xsl:value-of select="$param/data/effect"/></textarea>
			<p>Note</p>
			<textarea name="note" rows="6" cols="50"><xsl:value-of select="$param/data/note"/></textarea>
			<p>Card picture 
				<input name="uploadedfile" type="file" style="color: white"/><input type="submit" name="upload_pic[{$param/data/id}]" value="Upload" />
				<input type="submit" name="clear_img[{$param/data/id}]" value="Clear" />
			</p>
		</div>

		<input type="hidden" name="CurrentConcept" value="{$param/data/id}" />
	</div>

</xsl:template>


</xsl:stylesheet>
