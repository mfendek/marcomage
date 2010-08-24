<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
                xmlns="http://www.w3.org/1999/xhtml"
                xmlns:am="http://arcomage.netvor.sk"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:php="http://php.net/xsl"
                extension-element-prefixes="php">
<xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes" doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" />


<xsl:template name="navbar">
	<xsl:variable name="param" select="$params/navbar" />

	<xsl:variable name="sections" select="document('sections.xml')/am:sections" />
	<xsl:variable name="current_section" select="$sections/am:section/am:subsection[text() = $param/current]/../@name" />

	<div id="menubar">

	<div id="menu_float_left">
	<p>
		<a class="profile" href="{php:functionString('makeurl', 'Profile', 'Profile', $param/player_name)}"><xsl:value-of select="$param/player_name"/></a>
		<xsl:text> (</xsl:text>
		<xsl:value-of select="$param/level"/>
		<xsl:text>)</xsl:text>
	</p>
	</div>

	<div id="menu_float_right">
		<button type="submit" name="reset_notification" value= "{am:urlencode($current_section)}">RN</button>
		<button type="submit" name="Logout" accesskey="q">Logout</button>
	</div>

	<div id="menu_center">

	<xsl:for-each select="$sections/*">
		<a class="button" href="{php:functionString('makeurl', @name)}" >
			<xsl:if test="$current_section = @name">
				<xsl:attribute name="class">button pushed</xsl:attribute>
			</xsl:if>
			<xsl:value-of select="@name"/>
		</a>
		<xsl:if test="'Forum' = @name and $param/forum_notice = 'yes'">
			<img src="img/book.gif" alt="" width="18px" height="14px" title="New post" />
		</xsl:if>
		<xsl:if test="'Messages' = @name and $param/message_notice = 'yes'">
			<img src="img/new_post.gif" alt="" width="15px" height="10px" title="New message" />
		</xsl:if>
		<xsl:if test="'Games' = @name and $param/game_notice = 'yes'">
			<img src="img/battle.gif" alt="" width="20px" height="13px" title="Your turn" />
		</xsl:if>
		<xsl:if test="'Concepts' = @name and $param/concept_notice = 'yes'">
			<img src="img/new_card.gif" alt="" width="10px" height="14px" title="New card" />
		</xsl:if>
	</xsl:for-each>

	</div>

	<div class="clear_floats" /></div>

	<hr />

	<xsl:if test="$param/error_msg != ''">
		<p class="information_line error"><xsl:value-of select="$param/error_msg"/></p>
	</xsl:if>
	<xsl:if test="$param/warning_msg != ''">
		<p class="information_line warning"><xsl:value-of select="$param/warning_msg"/></p>
	</xsl:if>
	<xsl:if test="$param/info_msg != ''">
		<p class="information_line info"><xsl:value-of select="$param/info_msg"/></p>
	</xsl:if>
	<xsl:if test="($param/error_msg = '') and ($param/warning_msg = '') and ($param/info_msg = '')">
		<p class="blank_line"></p>
	</xsl:if>

</xsl:template>


</xsl:stylesheet>
