<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
                xmlns="http://www.w3.org/1999/xhtml"
                xmlns:am="http://arcomage.netvor.sk"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes" doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" />


<!--
	base structure template - header, navbar, content and footer
-->
<xsl:template name="main">
	<xsl:variable name="param" select="$params/main" />

	<html xmlns="http://www.w3.org/1999/xhtml" xmlns:am="http://arcomage.netvor.sk" lang="en" xml:lang="en">
	<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="description" content="Arcomage multiplayer on-line fantasy card game" />
	<meta name="author" content="Mojmír Fendek, Viktor Štujber" />
	<meta name="keywords" content="Arcomage, MArcomage, free, online, fantasy, card game, fantasy novels"/>
	<link rel="stylesheet" href="styles/general.css" type="text/css" title="standard style" />
	<link rel="stylesheet" href="styles/menubar.css" type="text/css" title="standard style" />
	<link rel="stylesheet" href="styles/decks.css" type="text/css" title="standard style" />
	<link rel="stylesheet" href="styles/card.css" type="text/css" title="standard style" />
	<link rel="stylesheet" href="styles/games.css" type="text/css" title="standard style" />
	<link rel="stylesheet" href="styles/players.css" type="text/css" title="standard style" />
	<link rel="stylesheet" href="styles/messages.css" type="text/css" title="standard style" />
	<link rel="stylesheet" href="styles/webpage.css" type="text/css" title="standard style" />
	<link rel="stylesheet" href="styles/settings.css" type="text/css" title="standard style" />
	<link rel="stylesheet" href="styles/novels.css" type="text/css" title="standard style" />
	<link rel="stylesheet" href="styles/forum.css" type="text/css" title="standard style" />
	<link rel="stylesheet" href="styles/concepts.css" type="text/css" title="standard style" />
	<link rel="stylesheet" href="styles/cards.css" type="text/css" title="standard style" />
	<link rel="stylesheet" href="styles/statistics.css" type="text/css" title="standard style" />
	<link rel="stylesheet" href="styles/skins/skin{$param/skin}.css" type="text/css" title="standard style" />
	<link rel="icon" href="img/favicon.png" type="image/png" />
	<title>MArcomage</title>
	<xsl:if test="$param/autorefresh &gt; 0">
		<xsl:element name="script">
			<xsl:attribute name="type">text/javascript</xsl:attribute>
			<xsl:text>setTimeout('window.location.reload()', </xsl:text>
			<xsl:value-of select="$param/autorefresh" />
			<xsl:text>000);</xsl:text>
		</xsl:element>
	</xsl:if>
	<script type="text/javascript" src="javascript/BBtags.js"></script>
	<script type="text/javascript" src="javascript/utils.js"></script>
	</head>
	<body>
	<form action="" enctype="multipart/form-data" method="post">


	<!-- login box or navbar, depending on 'is_logged_in' -->
	<div>
	<xsl:choose>
		<xsl:when test="$param/is_logged_in = 'yes'">
			<xsl:call-template name="navbar" />
		</xsl:when>
		<xsl:otherwise>
			<xsl:call-template name="loginbox" />
		</xsl:otherwise>
	</xsl:choose>
	</div>

	<!-- content goes here -->
	<xsl:apply-templates select="$param/section" />

	<!-- session string -->
	<xsl:if test="$param/sessionid">
		<div>
			<input type="hidden" name="Username" value="{$param/username}" />
			<input type="hidden" name="SessionID" value="{$param/sessionid}" />
		</div>
	</xsl:if>

	</form>
	</body>
	</html>
</xsl:template>


</xsl:stylesheet>
