<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
                xmlns="http://www.w3.org/1999/xhtml"
                xmlns:am="http://arcomage.netvor.sk"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes" doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" />


<xsl:template name="navbar">
	<xsl:variable name="param" select="$params/navbar" />

	<div id="menubar">

	<div id="menu_float_left">
	<p><xsl:value-of select="$param/player_name"/> (<xsl:value-of select="$param/level"/>)</p>
	</div>

	<div id="menu_float_right">
	<input type="submit" name="Logout" value="Logout" accesskey="q" />
	</div>

	<div id="menu_center">

	<input type="submit" name="Page" value="Webpage" >
		<xsl:if test="$param/current = 'Page'">
			<xsl:attribute name="class">pushed</xsl:attribute>
		</xsl:if>
	</input>
	<input type="submit" name="Forum" value="Forum" >
		<xsl:if test="(
			($param/current = 'Forum') or 
			($param/current = 'Forum_search') or 
			($param/current = 'Section_details') or 
			($param/current = 'New_thread') or 
			($param/current = 'Thread_details') or 
			($param/current = 'New_post') or 
			($param/current = 'Edit_post') or 
			($param/current = 'Edit_thread')
		)">
			<xsl:attribute name="class">pushed</xsl:attribute>
		</xsl:if>
	</input>
	<xsl:if test="$param/IsSomethingNew = 'yes'">
		<img src="img/book.gif" alt="" width="18px" height="14px" />
	</xsl:if>
	<input type="submit" name="Messages" value="Messages" >
		<xsl:if test="($param/current = 'Messages') or ($param/current = 'Message_details') or ($param/current = 'Message_new')">
			<xsl:attribute name="class">pushed</xsl:attribute>
		</xsl:if>
	</input>
	<xsl:if test="($param/NumMessages &gt; 0) or ($param/NumUnread &gt; 0)">
		<img src="img/new_post.gif" alt="" width="15px" height="10px" />
	</xsl:if>
	<input type="submit" name="Players" value="Players" >
		<xsl:if test="($param/current = 'Players') or ($param/current = 'Profile')">
			<xsl:attribute name="class">pushed</xsl:attribute>
		</xsl:if>
	</input>
	<input type="submit" name="Games" value="Games" >
		<xsl:if test="($param/current = 'Games') or ($param/current = 'Game') or ($param/current = 'Deck_view') or ($param/current = 'Game_note')">
			<xsl:attribute name="class">pushed</xsl:attribute>
		</xsl:if>
	</input>
	<xsl:if test="$param/NumGames &gt; 0">
		<img src="img/battle.gif" alt="" width="20px" height="13px" />
	</xsl:if>
	<input type="submit" name="Decks" value="Decks" >
		<xsl:if test="($param/current = 'Decks') or ($param/current = 'Deck_edit')">
			<xsl:attribute name="class">pushed</xsl:attribute>
		</xsl:if>
	</input>
	<input type="submit" name="Concepts" value="Concepts" >
		<xsl:if test="($param/current = 'Concepts') or ($param/current = 'Concepts_new') or ($param/current = 'Concepts_edit') or ($param/current = 'Concepts_details')">
			<xsl:attribute name="class">pushed</xsl:attribute>
		</xsl:if>
	</input>
	<xsl:if test="$param/NewConcepts = 'yes'">
		<img src="img/new_card.gif" alt="" width="10px" height="14px" />
	</xsl:if>
	<input type="submit" name="Cards" value="Cards" >
		<xsl:if test="($param/current = 'Cards') or ($param/current = 'Cards_details')">
			<xsl:attribute name="class">pushed</xsl:attribute>
		</xsl:if>
	</input>
	<input type="submit" name="Replays" value="Replays" >
		<xsl:if test="($param/current = 'Replays') or ($param/current = 'Replay')">
			<xsl:attribute name="class">pushed</xsl:attribute>
		</xsl:if>
	</input>
	<input type="submit" name="Novels" value="Novels" >
		<xsl:if test="$param/current = 'Novels'">
			<xsl:attribute name="class">pushed</xsl:attribute>
		</xsl:if>
	</input>
	<input type="submit" name="Settings" value="Settings" >
		<xsl:if test="$param/current = 'Settings'">
			<xsl:attribute name="class">pushed</xsl:attribute>
		</xsl:if>
	</input>

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
