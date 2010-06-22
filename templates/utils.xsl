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
			<input type="button" title="Bold" value="B" onclick="addTags('[b]', '[/b]')" />
			<input type="button" title="Italics" value="I" onclick="addTags('[i]', '[/i]')" />
			<input type="button" title="Hyperlink" value="Url" onclick="addTags('[url]', '[/url]')" />
			<input type="button" title="Quote" value="Quote" onclick="addTags('[quote]', '[/quote]')" />
		</div>
	</xsl:variable>
	<func:result select="exsl:node-set($buttons)"/>
</func:function>

<func:function name="am:datediff">
	<xsl:param name="datetime1" as="xs:string" />
	<xsl:param name="datetime2" as="xs:string" />
	<func:result select="date:seconds(date:difference(str:replace($datetime1, ' ', 'T'), str:replace($datetime2, ' ', 'T')))" />
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


<func:function name="am:cardstring">
	<xsl:param name="card" />
	<xsl:param name="c_img" select="'yes'" />
	<xsl:param name="c_keywords" select="'yes'" />
	<xsl:param name="c_text" select="'yes'" />
	<xsl:param name="c_oldlook" select="'no'" />

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
				<img src="img/concepts/{$card/picture}" width="80px" height="60px" alt="" >
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
			<xsl:if test="$c_keywords = 'yes'">
				<xsl:choose>
					<xsl:when test="$card/picture">
						<p><b><xsl:value-of select="$card/keywords"/></b></p>
					</xsl:when>
					<xsl:otherwise>
						<xsl:variable name="descriptions" select="document('keywords.xml')/am:keywords" />
						<p>
							<xsl:for-each select="str:split($card/keywords, ',')">
								<b>
									<xsl:variable name="keyword_name" select="." />
									<xsl:attribute name="title">
										<xsl:value-of select="$descriptions/am:keyword[contains($keyword_name, am:name)]/am:description"/>
									</xsl:attribute>
									<xsl:value-of select="$keyword_name"/>
									<xsl:text>.</xsl:text>
								</b>
							</xsl:for-each>
						</p>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:if>

			<!-- card effect -->
			<xsl:if test="$c_text = 'yes'">
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
			</xsl:if>

		</div>
	</xsl:variable>
	<func:result select="exsl:node-set($cardstring)"/>
</func:function>


</xsl:stylesheet>
