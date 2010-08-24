<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
                xmlns="http://www.w3.org/1999/xhtml"
                xmlns:am="http://arcomage.netvor.sk"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:date="http://exslt.org/dates-and-times"
                xmlns:exsl="http://exslt.org/common"
                xmlns:php="http://php.net/xsl"
                extension-element-prefixes="date exsl php">
<xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes" doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" />


<xsl:template match="section[. = 'Concepts']">
	<xsl:variable name="param" select="$params/concepts" />

	<div id="concepts">
		<h3>Card concepts</h3>

		<div id="concepts_table">

		<!-- begin buttons and filters -->

		<div class="filters">
			<xsl:if test="$param/create_card = 'yes'">
				<button type="submit" name="new_concept">New card</button>
			</xsl:if>

			<!-- card name filter -->
			<input type="text" name="card_name" maxlength="64" size="30" value="{$param/card_name}" />

			<!-- date filter -->
			<xsl:variable name="dates">
				<value name="No date filter" value="none" />
				<value name="1 day"          value="1"    />
				<value name="2 days"         value="2"    />
				<value name="5 days"         value="5"    />
				<value name="1 week"         value="7"    />
				<value name="2 weeks"        value="14"   />
				<value name="3 weeks"        value="21"   />
				<value name="1 month"        value="30"   />
				<value name="3 months"       value="91"   />
				<value name="6 months"       value="182"  />
				<value name="1 year"         value="365"  />
			</xsl:variable>
			<xsl:copy-of select="am:htmlSelectBox('date_filter_concepts', $param/date_val, $dates, '')"/>

			<!-- author filter -->
			<xsl:if test="count($param/authors/*) &gt; 0">
				<xsl:variable name="authors">
					<value name="No author filter" value="none" />
				</xsl:variable>
				<xsl:copy-of select="am:htmlSelectBox('author_filter', $param/author_val, $authors, $param/authors)"/>
			</xsl:if>

			<!-- state filter -->
			<xsl:variable name="states">
				<value name="No state filter" value="none"        />
				<value name="waiting"         value="waiting"     />
				<value name="rejected"        value="rejected"    />
				<value name="interesting"     value="interesting" />
				<value name="implemented"     value="implemented" />
			</xsl:variable>
			<xsl:copy-of select="am:htmlSelectBox('state_filter', $param/state_val, $states, '')"/>

			<button type="submit" name="concepts_filter">Apply filters</button>
			<xsl:if test="$param/mycards = 'yes'">
				<button type="submit" name="my_concepts">My cards</button>
			</xsl:if>

			<!-- upper navigation -->
			<xsl:copy-of select="am:upper_navigation($param/page_count, $param/current_page, 'concepts')"/>

		<!-- end buttons and filters -->
		</div>
		
		<table cellspacing="0" class="skin_text">
			<tr>
				<th>Card</th>
				<th>
					<p>
						<xsl:text>Card name</xsl:text>
						<button class="small_button" type="submit" value="Name" >
							<xsl:if test="$param/current_condition = 'Name'">
								<xsl:attribute name="class">small_button pushed</xsl:attribute>
							</xsl:if>
							<xsl:choose>
								<xsl:when test="(($param/current_condition = 'Name') and ($param/current_order = 'DESC'))">
									<xsl:attribute name="name">concepts_ord_asc</xsl:attribute>
									<xsl:text>\/</xsl:text>
								</xsl:when>
								<xsl:otherwise>
									<xsl:attribute name="name">concepts_ord_desc</xsl:attribute>
									<xsl:text>/\</xsl:text>
								</xsl:otherwise>
							</xsl:choose>
						</button>
					</p>
				</th>
				<th><p>Author</p></th>
				<th><p>Rarity</p></th>
				<th>
					<p>
						<xsl:text>Last change</xsl:text>
						<button class="small_button" type="submit" value="LastChange" >
							<xsl:if test="$param/current_condition = 'LastChange'">
								<xsl:attribute name="class">small_button pushed</xsl:attribute>
							</xsl:if>
							<xsl:choose>
								<xsl:when test="(($param/current_condition = 'LastChange') and ($param/current_order = 'DESC'))">
										<xsl:attribute name="name">concepts_ord_asc</xsl:attribute>
										<xsl:text>\/</xsl:text>
								</xsl:when>
								<xsl:otherwise>
										<xsl:attribute name="name">concepts_ord_desc</xsl:attribute>
										<xsl:text>/\</xsl:text>
								</xsl:otherwise>
							</xsl:choose>
						</button>
					</p>
				</th>
				<th><p>State</p></th>
				<th></th>
			</tr>
			<xsl:for-each select="$param/list/*">
				<tr>
					<td align="center"><xsl:copy-of select="am:cardstring(current(), $param/c_img, $param/c_keywords, $param/c_text, $param/c_oldlook)" /></td>
					<td><p><a href="{php:functionString('makeurl', 'Concepts_details', 'CurrentConcept', id)}"><xsl:value-of select="name"/></a></p></td>
					<td><p><a class="profile" href="{php:functionString('makeurl', 'Profile', 'Profile', author)}"><xsl:value-of select="author"/></a></p></td>
					<td><p><xsl:value-of select="class"/></p></td>
					<td>
						<p>
							<xsl:if test="am:datediff(lastchange, $param/notification) &lt; 0">
								<xsl:attribute name="class">highlighted</xsl:attribute>
							</xsl:if>
							<xsl:value-of select="am:datetime(lastchange, $param/timezone)"/>
						</p>
					</td>
					<td><p><xsl:value-of select="state"/></p></td>
					<td>
						<p>
							<xsl:if test="$param/edit_all_card = 'yes' or ($param/edit_own_card = 'yes' and ($param/PlayerName = author))">
								<button class="small_button" type="submit" name="edit_concept" value="{id}">E</button>
							</xsl:if>
							<xsl:if test="$param/delete_all_card = 'yes' or ($param/delete_own_card = 'yes' and ($param/PlayerName = author))">
								<button class="small_button" type="submit" name="delete_concept" value="{id}">D</button>
							</xsl:if>
						</p>
					</td>
				</tr>
			</xsl:for-each>
		</table>

		<div class="filters">
			<!-- lower navigation -->
			<xsl:copy-of select="am:lower_navigation($param/page_count, $param/current_page, 'concepts', 'Concepts')"/>
		</div>

		</div>

		<input type="hidden" name="CurrentConPage" value="{$param/current_page}" />
		<input type="hidden" name="CurrentOrder" value="{$param/current_order}" />
		<input type="hidden" name="CurrentCon" value="{$param/current_condition}" />
	</div>
</xsl:template>


<xsl:template match="section[. = 'Concepts_new']">
	<xsl:variable name="param" select="$params/concepts_new" />

	<div id="concepts_edit">

		<h3>New card</h3>

		<div id="card_edit" class="skin_text">
			<a class="button" href="{php:functionString('makeurl', 'Concepts')}">Back</a>
			<button type="submit" name="create_concept">Create card</button>

			<hr />

			<div class="limit">
				<p>
					<span>
						<input type="text" name="name" maxlength="64" size="35" >
							<xsl:if test="$param/stored = 'yes'">
								<xsl:attribute name="value"><xsl:value-of select="$param/data/name"/></xsl:attribute>
							</xsl:if>
						</input>
					</span>
					<xsl:text>Name</xsl:text>
				</p>
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
					<xsl:text>Rarity</xsl:text>
				</p>
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
					<xsl:text>Cost (B/G/R)</xsl:text>
				</p>
				<p>
					<span>
						<input type="text" name="keywords" maxlength="100" size="35" >
							<xsl:if test="$param/stored = 'yes'">
								<xsl:attribute name="value"><xsl:value-of select="$param/data/keywords"/></xsl:attribute>
							</xsl:if>
						</input>
					</span>
					<xsl:text>Keywords</xsl:text>
				</p>
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

		<div id="card_edit" class="skin_text">
			<a class="button" href="{php:functionString('makeurl', 'Concepts')}">Back</a>
			<a class="button" href="{php:functionString('makeurl', 'Concepts_details', 'CurrentConcept', $param/data/id)}">Details</a>
			<xsl:if test="$param/data/author = $param/PlayerName">
				<button type="submit" name="save_concept">Save</button>
			</xsl:if>
			<xsl:if test="$param/edit_all_card = 'yes'">
				<button type="submit" name="save_concept_special">Special save</button>
			</xsl:if>
			<xsl:if test="$param/delete_all_card = 'yes' or ($param/delete_own_card = 'yes' and $param/data/author = $param/PlayerName)">
				<xsl:choose>
					<xsl:when test="$param/delete = 'no'">
						<button type="submit" name="delete_concept" value="{$param/data/id}">Delete</button>
					</xsl:when>
					<xsl:otherwise>
						<button type="submit" name="delete_concept_confirm" class="marked_button">Confirm delete</button>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:if>
			<hr />

			<div class="card_preview"><xsl:copy-of select="am:cardstring($param/data, $param/c_img, $param/c_keywords, $param/c_text, $param/c_oldlook)" /></div>
			<div class="limit">
				<p>
					<span>
						<input type="text" name="name" maxlength="64" size="35" value="{$param/data/name}"  />
					</span>
					<xsl:text>Name</xsl:text>
				</p>
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
					<xsl:text>Rarity</xsl:text>
				</p>
				<p>
					<span>
						<input type="text" name="bricks" maxlength="2" size="2" value="{$param/data/bricks}"  />
						<input type="text" name="gems" maxlength="2" size="2" value="{$param/data/gems}"  />
						<input type="text" name="recruits" maxlength="2" size="2" value="{$param/data/recruits}"  />
					</span>
					<xsl:text>Cost (B/G/R)</xsl:text>
				</p>
				<p>
					<span>
						<input type="text" name="keywords" maxlength="100" size="35" value="{$param/data/keywords}"  />
					</span>
					<xsl:text>Keywords</xsl:text>
				</p>
				<p>
					<span>
						<xsl:variable name="states">
							<class name="waiting"     />
							<class name="rejected"    />
							<class name="interesting" />
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
					<xsl:text>State</xsl:text>
				</p>
			</div>
			<p>Effect</p>
			<textarea name="effect" rows="6" cols="50"><xsl:value-of select="$param/data/effect"/></textarea>
			<p>Note</p>
			<textarea name="note" rows="6" cols="50"><xsl:value-of select="$param/data/note"/></textarea>
			<p>
				<xsl:text>Card picture</xsl:text>
				<input type="file" name="uploadedfile" />
				<button type="submit" name="upload_pic" value="{$param/data/id}">Upload</button>
				<button type="submit" name="clear_img" value="{$param/data/id}">Clear</button>
			</p>
		</div>

		<input type="hidden" name="CurrentConcept" value="{$param/data/id}" />
	</div>

</xsl:template>


<xsl:template match="section[. = 'Concepts_details']">
	<xsl:variable name="param" select="$params/concepts_details" />

	<div id="concepts_edit">

		<h3>Card details</h3>

		<div id="card_edit" class="skin_text">
			<a class="button" href="{php:functionString('makeurl', 'Concepts')}">Back</a>
			<xsl:if test="$param/edit_all_card = 'yes' or ($param/edit_own_card = 'yes' and ($param/PlayerName = author))">
				<button type="submit" name="edit_concept" value="{$param/data/id}">Edit</button>
			</xsl:if>
			<hr />

			<div class="card_preview"><xsl:copy-of select="am:cardstring($param/data, $param/c_img, $param/c_keywords, $param/c_text, $param/c_oldlook)" /></div>
			<div class="limit">
				<p><span><a class="profile" href="{php:functionString('makeurl', 'Profile', 'Profile', $param/data/author)}"><xsl:value-of select="$param/data/author"/></a></span>Author</p>
				<p><span><xsl:value-of select="$param/data/name"/></span>Name</p>
				<p><span><xsl:value-of select="$param/data/class"/></span>Rarity</p>
				<p><span><xsl:value-of select="$param/data/keywords"/></span>Keywords</p>
				<p><span><xsl:value-of select="$param/data/state"/></span>State</p>
			</div>
			<p>Note</p>
			<div class="note"><xsl:copy-of select="am:textencode($param/data/note)" /></div>
			<p>
				<xsl:choose>
					<xsl:when test="$param/data/threadid = 0 and $param/create_thread = 'yes'">
						<button type="submit" name="concept_thread">Start discussion</button>
					</xsl:when>
					<xsl:when test="$param/data/threadid &gt; 0">
						<button type="submit" name="thread_details" value="{$param/data/threadid}">View discussion</button>
					</xsl:when>
				</xsl:choose>
			</p>
		</div>
		<input type="hidden" name="CurrentConcept" value="{$param/data/id}" />
	</div>

</xsl:template>


</xsl:stylesheet>
