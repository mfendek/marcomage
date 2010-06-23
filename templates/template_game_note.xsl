<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
                xmlns="http://www.w3.org/1999/xhtml"
                xmlns:am="http://arcomage.netvor.sk"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes" doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" />

<xsl:template match="section[. = 'Game_note']">
	<xsl:variable name="param" select="$params/game_note" />

	<!-- remember the current location across pages -->
	<div>
		<input type="hidden" name="CurrentGame" value="{$param/CurrentGame}"/>
	</div>

	<div id="game_note">

	<h3>Game note</h3>

	<div class="skin_text">
		<input type="submit" name="view_game[{$param/CurrentGame}]" value="Back to game"/>
		<input type="submit" name="save_note_return" value="Save &amp; return" />
		<input type="submit" name="save_note" value="Save" />
		<input type="submit" name="clear_note" value="Clear" />
		<input type="submit" name="clear_note_return" value="Clear &amp; return" />
		<hr/>

		<textarea name="Content" rows="10" cols="50"><xsl:value-of select="$param/text"/></textarea>
	</div>

	</div>

</xsl:template>


</xsl:stylesheet>
