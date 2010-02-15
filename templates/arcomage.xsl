<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
                xmlns="http://www.w3.org/1999/xhtml"
                xmlns:am="http://arcomage.netvor.sk"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes" doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" />

<!-- global copy of the input xml document -->
<xsl:variable name="params" select="params" />

<!-- includes -->
<xsl:include href="template_main.xsl" />
<xsl:include href="template_navbar.xsl" />
<xsl:include href="template_loginbox.xsl" />

<xsl:include href="template_cards.xsl" />
<xsl:include href="template_concepts.xsl" />
<xsl:include href="template_deck_edit.xsl" />
<xsl:include href="template_deck_view.xsl" />
<xsl:include href="template_decks.xsl" />
<xsl:include href="template_forum.xsl" />
<xsl:include href="template_game.xsl" />
<xsl:include href="template_game_note.xsl" />
<xsl:include href="template_games.xsl" />
<xsl:include href="template_messages.xsl" />
<xsl:include href="template_novels.xsl" />
<xsl:include href="template_players.xsl" />
<xsl:include href="template_profile.xsl" />
<xsl:include href="template_registration.xsl" />
<xsl:include href="template_settings.xsl" />
<xsl:include href="template_website.xsl" />
<xsl:include href="template_replays.xsl" />
<xsl:include href="template_nowhere.xsl" /> <!-- needs to be the last included section -->
<xsl:include href="utils.xsl" />


<xsl:template match="/">
	<xsl:call-template name="main" />
</xsl:template>

<!--
<xsl:template match="*">
	<xsl:message terminate="yes">[fatal error] No template to match input '<xsl:value-of select="local-name()"/>':'<xsl:value-of select="."/>'. Processing incomplete.</xsl:message>
</xsl:template>
-->

</xsl:stylesheet>
