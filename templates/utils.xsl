<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
                xmlns="http://www.w3.org/1999/xhtml"
                xmlns:am="http://arcomage.netvor.sk"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:date="http://exslt.org/dates-and-times"
                xmlns:exsl="http://exslt.org/common"
                xmlns:func="http://exslt.org/functions"
                xmlns:php="http://php.net/xsl"
                xmlns:str="http://exslt.org/strings"
                extension-element-prefixes="date func php str">
<xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes" doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" />


<xsl:include href="date.format-date.function.xsl" /> <!-- date:format-date(string, string) -->
<xsl:include href="date.difference.function.xsl" /> <!-- date:difference(string, string) -->


<func:function name="am:urlencode">
	<xsl:param name="string" as="xs:string" />
	<func:result select="str:encode-uri($string, true())" />
</func:function>


<func:function name="am:textencode">
	<xsl:param name="text" as="xs:string" />

	<!-- change newlines into html paragraphs -->
	<xsl:variable name="lines" select="str:split($text, '&#10;')" />
	<xsl:variable name="output">
		<xsl:for-each select="$lines">
			<!-- change urls into html hyperlinks -->
			<xsl:variable name="words" select="str:split(text(), ' ')"/>
			<xsl:for-each select="$words">
				<xsl:if test="position() != 1">
					<xsl:text> </xsl:text>
				</xsl:if>
				<xsl:choose>
					<xsl:when test="contains(text(), 'http://')">
						<xsl:value-of select="substring-before(text(), 'http://')"/>
						<a href="http://{substring-after(text(), 'http://')}">http://<xsl:value-of select="substring-after(text(), 'http://')"/></a>
					</xsl:when>
					<xsl:when test="contains(text(), 'https://')">
						<xsl:value-of select="substring-before(text(), 'https://')"/>
						<a href="https://{substring-after(text(), 'https://')}">https://<xsl:value-of select="substring-after(text(), 'https://')"/></a>
					</xsl:when>
					<xsl:otherwise>
						<xsl:value-of select="text()"/>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:for-each>
			<br/>
		</xsl:for-each>
	</xsl:variable>

	<func:result select="$output" />
</func:function>


<func:function name="am:min">
	<xsl:param name="num1" as="xs:integer" />
	<xsl:param name="num2" as="xs:integer" />
	<func:result>
		<xsl:choose>
			<xsl:when test="$num1 &lt; $num2"><xsl:value-of select="$num1"/></xsl:when>
			<xsl:otherwise><xsl:value-of select="$num2"/></xsl:otherwise>
		</xsl:choose>
	</func:result>
</func:function>


<func:function name="am:max">
	<xsl:param name="num1" as="xs:integer" />
	<xsl:param name="num2" as="xs:integer" />
	<func:result>
		<xsl:choose>
			<xsl:when test="$num1 &gt; $num2"><xsl:value-of select="$num1"/></xsl:when>
			<xsl:otherwise><xsl:value-of select="$num2"/></xsl:otherwise>
		</xsl:choose>
	</func:result>
</func:function>


<func:function name="am:datetime">
	<xsl:param name="datetime" as="xs:string" />
	<xsl:param name="timezone" as="xs:string" select="'+0'" />
	<xsl:variable name="date" select="str:replace($datetime, ' ', 'T')" />
	<xsl:variable name="zone" select="concat('Etc/GMT', str:replace(str:replace(str:replace($timezone, '+', '*'), '-', '+'), '*', '-'))" />
	<func:result select="php:functionString('ZoneTime', $date, $zone, 'H:i, j. M, Y')" />
</func:function>


<func:function name="am:format-date">
	<xsl:param name="date" as="xs:string" />
	<func:result select="date:format-date($date, 'd. MMM, yyyy')" />
</func:function>


<func:function name="am:lowercase">
	<xsl:param name="string" as="xs:string" />
	<xsl:variable name="smallcase" select="'abcdefghijklmnopqrstuvwxyz'"/>
	<xsl:variable name="uppercase" select="'ABCDEFGHIJKLMNOPQRSTUVWXYZ'"/>
	<func:result select="translate($string, $uppercase, $smallcase)" />
</func:function>


<!--
<func:function name="am:BBCode_parse">
	<xsl:param name="orig_content" as="xs:string" />
	<func:result select="php:functionString('parse_post', $orig_content)" />
</func:function>
-->

<func:function name="am:BBCode_parse_extended">
	<xsl:param name="orig_content" as="xs:string" />
	<func:result select="php:functionString('parse_post', $orig_content, 'true')" />
</func:function>

<func:function name="am:BBcodeButtons">
	<xsl:variable name="buttons">
		<div class="BBcodeButtons">
			<button type="button" title="Bold" onclick="addTags('[b]', '[/b]')">B</button>
			<button type="button" title="Italics" onclick="addTags('[i]', '[/i]')">I</button>
			<button type="button" title="Hyperlink" onclick="addTags('[url]', '[/url]')">Url</button>
			<button type="button" title="Quote" onclick="addTags('[quote]', '[/quote]')">Quote</button>
		</div>
	</xsl:variable>
	<func:result select="exsl:node-set($buttons)"/>
</func:function>

<func:function name="am:datediff">
	<xsl:param name="datetime1" as="xs:string" />
	<xsl:param name="datetime2" as="xs:string" />
	<func:result select="date:seconds(date:difference(str:replace($datetime1, ' ', 'T'), str:replace($datetime2, ' ', 'T')))" />
</func:function>


<func:function name="am:upper_navigation">
	<xsl:param name="page_count" as="xs:integer" />
	<xsl:param name="current" as="xs:integer" />
	<xsl:param name="button_name" as="xs:string" />

	<xsl:variable name="output">
		<!-- arrow buttons selector -->
		<button type="submit" name="{concat('select_page_', $button_name)}" value="{am:max($current - 1, 0)}">
			<xsl:if test="$current = 0">
				<xsl:attribute name="disabled">disabled</xsl:attribute>
			</xsl:if>
			<xsl:text>&lt;</xsl:text>
		</button>
		<button type="submit" name="{concat('select_page_', $button_name)}" value="{am:min($current + 1, $page_count - 1)}">
			<xsl:if test="$current = am:max($page_count - 1, 0)">
				<xsl:attribute name="disabled">disabled</xsl:attribute>
			</xsl:if>
			<xsl:text>&gt;</xsl:text>
		</button>
		<xsl:if test="$page_count &gt; 0">
			<!-- page selector -->
			<select name="page_selector">
				<xsl:for-each select="am:page_list($page_count)">
					<option value="{.}">
						<xsl:if test="$current = ."><xsl:attribute name="selected">selected</xsl:attribute></xsl:if>
						<xsl:value-of select="."/>
					</option>
				</xsl:for-each>
			</select>
			<button type="submit" name="{concat('seek_page_', $button_name)}">Select</button>
		</xsl:if>
	</xsl:variable>

	<func:result select="$output" />
</func:function>


<func:function name="am:lower_navigation">
	<xsl:param name="page_count" as="xs:integer" />
	<xsl:param name="current" as="xs:integer" />
	<xsl:param name="arrow_button" as="xs:string" />
	<xsl:param name="back_button" as="xs:string" />

	<xsl:variable name="output">
		<!-- arrow buttons selector -->
		<button type="submit" name="{concat('select_page_', $arrow_button)}" value="{am:max($current - 1, 0)}">
			<xsl:if test="$current = 0">
				<xsl:attribute name="disabled">disabled</xsl:attribute>
			</xsl:if>
			<xsl:text>&lt;</xsl:text>
		</button>

		<button type="submit" name="Refresh" value="{$back_button}">Back to top</button>

		<button type="submit" name="{concat('select_page_', $arrow_button)}" value="{am:min($current + 1, $page_count - 1)}">
			<xsl:if test="$current = am:max($page_count - 1, 0)">
				<xsl:attribute name="disabled">disabled</xsl:attribute>
			</xsl:if>
			<xsl:text>&gt;</xsl:text>
		</button>
	</xsl:variable>

	<func:result select="$output" />
</func:function>


<func:function name="am:page_list">
	<xsl:param name="count" as="xs:integer" />
	<func:result select="str:split(am:numbers(0, $count - 1), ',')" />
</func:function>


<func:function name="am:numbers">
	<xsl:param name="from" as="xs:integer" />
	<xsl:param name="to" as="xs:integer" />

	<func:result select="php:functionString('Numbers', $from, $to)" />
</func:function>


<func:function name="am:simple_navigation">
	<xsl:param name="location" as="xs:string" />
	<xsl:param name="page_type" as="xs:string" />
	<xsl:param name="current" as="xs:integer" />
	<xsl:param name="page_count" as="xs:integer" />

	<xsl:variable name="output">
		<xsl:choose>
			<xsl:when test="$current &gt; 0">
				<a class="button" href="{php:functionString('makeurl', $location, $page_type, am:max($current - 1, 0))}">&lt;</a>
			</xsl:when>
			<xsl:otherwise>
				<span class="disabled">&lt;</span>
			</xsl:otherwise>
		</xsl:choose>

		<xsl:choose>
			<xsl:when test="$current &gt; 0">
				<a class="button" href="{php:functionString('makeurl', $location, $page_type, 0)}">First</a>
			</xsl:when>
			<xsl:otherwise>
				<span class="disabled">First</span>
			</xsl:otherwise>
		</xsl:choose>

		<xsl:for-each select="str:split(am:numbers(am:max($current - 5, 0), am:min($current + 5, am:max($page_count - 1, 0))), ',')">
			<xsl:choose>
				<xsl:when test="$current != .">
					<a class="button" href="{php:functionString('makeurl', $location, $page_type, text())}"><xsl:value-of select="text()"/></a>
				</xsl:when>
				<xsl:otherwise>
					<span class="disabled"><xsl:value-of select="text()"/></span>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:for-each>

		<xsl:choose>
			<xsl:when test="$current &lt; am:max($page_count - 1, 0)">
				<a class="button" href="{php:functionString('makeurl', $location, $page_type, am:max($page_count - 1, 0))}">Last</a>
			</xsl:when>
			<xsl:otherwise>
				<span class="disabled">Last</span>
			</xsl:otherwise>
		</xsl:choose>

		<xsl:choose>
			<xsl:when test="$current &lt; am:max($page_count - 1, 0)">
				<a class="button" href="{php:functionString('makeurl', $location, $page_type, am:min($current + 1, am:max($page_count - 1, 0)))}">&gt;</a>
			</xsl:when>
			<xsl:otherwise>
				<span class="disabled">&gt;</span>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:variable>

	<func:result select="$output" />
</func:function>


<func:function name="am:htmlSelectBox">
	<!-- generates html select box -->
	<xsl:param name="name" as="xs:string" />
	<xsl:param name="current" as="xs:string" />
	<xsl:param name="static_values" as="xs:node-set" />
	<xsl:param name="dynamic_values" as="xs:node-set" />

	<xsl:variable name="converted">
		<xsl:for-each select="exsl:node-set($dynamic_values)/*">
			<value name="{text()}" value="{text()}" />
		</xsl:for-each>
	</xsl:variable>

	<xsl:variable name="values" select="exsl:node-set($static_values) | exsl:node-set($converted)" />

	<xsl:variable name="output">
		<select name="{$name}">
			<xsl:if test="$current != 'none'">
				<xsl:attribute name="class">filter_active</xsl:attribute>
			</xsl:if>
			<xsl:for-each select="$values/*">
				<option value="{@value}">
					<xsl:if test="$current = @value">
						<xsl:attribute name="selected">selected</xsl:attribute>
					</xsl:if>
					<xsl:value-of select="@name"/>
				</option>
			</xsl:for-each>
		</select>
	</xsl:variable>

	<func:result select="$output" />
</func:function>


<func:function name="am:cardeffect">
	<xsl:param name="effect" as="xs:string" />
	<!-- ad-hoc html entity corrections -->
	<xsl:variable name="replace">
		<from> &lt; </from><to> &amp;lt; </to>
		<from> &gt; </from><to> &amp;gt; </to>
		<from> &lt;= </from><to> &amp;lt;= </to>
		<from> &gt;= </from><to> &amp;gt;= </to>
	</xsl:variable>
	<func:result select="str:replace($effect, exsl:node-set($replace)/*[local-name()='from'], exsl:node-set($replace)/*[local-name()='to'])" />
</func:function>


<func:function name="am:file_name">
	<xsl:param name="name" as="xs:string" />
	<func:result select="am:lowercase(str:replace($name, ' ', '_'))" />
</func:function>


<func:function name="am:cardstring">
	<xsl:param name="card" />
	<xsl:param name="c_img" select="'yes'" />
	<xsl:param name="c_oldlook" select="'no'" />
	<xsl:param name="c_insignias" select="'yes'" />

	<xsl:variable name="cardstring">

		<xsl:variable name="class">
			<xsl:choose>
				<xsl:when test="$card/class = 'Common'"> common_class</xsl:when>
				<xsl:when test="$card/class = 'Uncommon'"> uncommon_class</xsl:when>
				<xsl:when test="$card/class = 'Rare'"> rare_class</xsl:when>
				<xsl:otherwise> no_class</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
		<xsl:variable name="type">
			<xsl:choose>
				<xsl:when test="$card/bricks = 0 and $card/gems = 0 and $card/recruits = 0">
					<xsl:text> zero_cost</xsl:text>
				</xsl:when>
				<xsl:when test="$card/bricks &gt; 0 and $card/gems = 0 and $card/recruits = 0">
					<xsl:text> bricks_cost</xsl:text>
				</xsl:when>
				<xsl:when test="$card/bricks = 0 and $card/gems &gt; 0 and $card/recruits = 0">
					<xsl:text> gem_cost</xsl:text>
				</xsl:when>
				<xsl:when test="$card/bricks = 0 and $card/gems = 0 and $card/recruits &gt; 0">
					<xsl:text> rec_cost</xsl:text>
				</xsl:when>
				<xsl:otherwise>
					<xsl:text> mixed_cost</xsl:text>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
		<xsl:variable name="bgimage">
			<xsl:if test="$c_oldlook = 'no'"> with_bgimage</xsl:if>
		</xsl:variable>

		<div class="karta{$class}{$type}{$bgimage}">

			<!-- display the cost (spheres with numbers in the center) -->
			<xsl:choose>
				<xsl:when test="$card/bricks &gt; 0 and $card/gems = $card/bricks and $card/recruits = $card/bricks">
					<div class="all"><xsl:value-of select="$card/bricks"/></div>
				</xsl:when>
				<xsl:when test="$card/bricks = 0 and $card/gems = 0 and $card/recruits = 0">
					<div class="null">0</div>
				</xsl:when>
				<xsl:otherwise>
					<xsl:if test="$card/recruits &gt; 0">
						<div class="rek"><xsl:value-of select="$card/recruits"/></div>
					</xsl:if>
					<xsl:if test="$card/gems &gt; 0">
						<div class="gemy"><xsl:value-of select="$card/gems"/></div>
					</xsl:if>
					<xsl:if test="$card/bricks &gt; 0">
						<div class="tehla"><xsl:value-of select="$card/bricks"/></div>
					</xsl:if>
				</xsl:otherwise>
			</xsl:choose>

			<!-- name -->
			<h5><xsl:value-of select="$card/name"/></h5>

			<!-- card's image and its border (colored via CSS according to class) -->
			<xsl:if test="$c_img = 'yes'">
				<img src="img/concepts/{$card/picture}" width="80px" height="60px" alt="{$card/name}" >
					<xsl:choose>
						<xsl:when test="$card/picture">
							<xsl:attribute name="src">img/concepts/<xsl:value-of select="$card/picture"/></xsl:attribute>
						</xsl:when>
						<xsl:otherwise>
							<xsl:attribute name="src">img/cards/g<xsl:value-of select="$card/id"/>.jpg</xsl:attribute>
						</xsl:otherwise>
					</xsl:choose>
				</img>
			</xsl:if>

			<!-- keywords -->
			<xsl:choose>
				<xsl:when test="$card/picture">
					<p><b><xsl:value-of select="$card/keywords"/></b></p>
				</xsl:when>
				<xsl:otherwise>
					<xsl:variable name="descriptions" select="document('keywords.xml')/am:keywords" />
					<p>
						<xsl:for-each select="str:split($card/keywords, ',')">
							<xsl:variable name="keyword_name" select="." />
							<xsl:variable name="keyword" select="$descriptions/am:keyword[contains($keyword_name, am:name)]" />
							<xsl:choose>
								<xsl:when test="$c_insignias = 'yes'">

								<img class="insignia" src="img/insignias/{am:file_name($keyword/am:name)}.png" width="12px" height="12px" alt="{$keyword_name}" title="{concat($keyword_name, ' - ', $keyword/am:description)}" />

								</xsl:when>
								<xsl:otherwise>
								<b>
									<xsl:attribute name="title">
										<xsl:value-of select="$keyword/am:description"/>
									</xsl:attribute>
									<xsl:value-of select="$keyword_name"/>
									<xsl:text>.</xsl:text>
								</b>
								</xsl:otherwise>
							</xsl:choose>
						</xsl:for-each>
					</p>
				</xsl:otherwise>
			</xsl:choose>

			<!-- card effect -->
			<div>
				<xsl:choose>
					<xsl:when test="$card/picture">
						<xsl:copy-of select="am:textencode($card/effect)" />
					</xsl:when>
					<xsl:otherwise>
						<xsl:value-of select="am:cardeffect($card/effect)" disable-output-escaping="yes"/>
					</xsl:otherwise>
				</xsl:choose>
			</div>

		</div>
	</xsl:variable>
	<func:result select="exsl:node-set($cardstring)"/>
</func:function>


</xsl:stylesheet>
