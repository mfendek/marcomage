<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
                xmlns="http://www.w3.org/1999/xhtml"
                xmlns:am="http://arcomage.netvor.sk"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:exsl="http://exslt.org/common"
                xmlns:php="http://php.net/xsl"
                extension-element-prefixes="exsl php">
<xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes" doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" />

<!-- includes -->
<xsl:include href="utils.xsl" />


<!-- global copy of the input xml document -->
<xsl:variable name="params" select="params" />

<xsl:template match="/">
	<xsl:variable name="param" select="$params/main" />

	<html xmlns="http://www.w3.org/1999/xhtml" xmlns:am="http://arcomage.netvor.sk" lang="en" xml:lang="en">
	<head>
	<!-- HTML header -->
	<xsl:variable name="section_name" select="$param/section_name" />
	<xsl:variable name="current_section" select="am:lowercase($section_name)" />

	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="description" content="Arcomage multiplayer on-line fantasy card game" />
	<meta name="author" content="Mojmír Fendek, Viktor Štujber" />
	<meta name="keywords" content="Arcomage, MArcomage, free, online, fantasy, card game, fantasy novels"/>
	<link rel="stylesheet" href="styles/general.css" type="text/css" title="standard style" />
	<link rel="stylesheet" href="styles/card.css" type="text/css" title="standard style" />
	<xsl:choose>
		<xsl:when test="$param/is_logged_in = 'yes'">
			<link rel="stylesheet" href="styles/menubar.css" type="text/css" title="standard style" />
		</xsl:when>
		<xsl:otherwise>
			<link rel="stylesheet" href="styles/login.css" type="text/css" title="standard style" />
		</xsl:otherwise>
	</xsl:choose>
	<xsl:if test="$current_section != ''">
		<link rel="stylesheet" href="styles/{$current_section}.css" type="text/css" title="standard style" />
	</xsl:if>
	<link rel="stylesheet" href="styles/skins/skin{$param/skin}.css" type="text/css" title="standard style" />
	<link rel="icon" href="img/favicon.png" type="image/png" />
	<title>
		<xsl:if test="$section_name != ''">
			<xsl:value-of select="$section_name" />
			<xsl:if test="$section_name = 'Games' and $param/current_games &gt; 0"> (<xsl:value-of select="$param/current_games" />)</xsl:if>
			<xsl:text> - </xsl:text>
		</xsl:if>
		<xsl:text>MArcomage</xsl:text>
	</title>
	<script type="text/javascript" src="javascript/jquery/jquery.js"></script>
	<script type="text/javascript" src="javascript/jquery/jquery_ui.js"></script>
	<script type="text/javascript" src="javascript/scrollto.js"></script>
	<script type="text/javascript" src="javascript/cookie.js"></script>
	<script type="text/javascript" src="javascript/bbtags.js"></script>
	<script type="text/javascript" src="javascript/utils.js"></script>
	<xsl:comment><![CDATA[[if lt IE 9]><script type="text/javascript" src="javascript/ie9.js"></script><![endif]]]></xsl:comment>
	</head>
	<body>
	<form action="" enctype="multipart/form-data" method="post">

	<!-- navigation bar -->
	<div>
	<xsl:choose>
		<xsl:when test="$param/is_logged_in = 'yes'">
			<xsl:call-template name="inner_navbar" />
		</xsl:when>
		<xsl:otherwise>
			<xsl:call-template name="outer_navbar" />
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


<xsl:template name="inner_navbar">
	<!-- navigation bar for authenticated user -->
	<xsl:variable name="param" select="$params/navbar" />

	<xsl:variable name="current_section" select="$param/section_name" />

	<div id="menubar">

	<div id="menu_float_left">
	<p>
		<a href="{php:functionString('makeurl', 'Players_details', 'Profile', $param/player_name)}"><xsl:value-of select="$param/player_name"/></a>
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

	<xsl:variable name="sections">
		<value name="Webpage"    />
		<value name="Help"       />
		<value name="Forum"      />
		<value name="Messages"   />
		<value name="Players"    />
		<value name="Games"      />
		<value name="Decks"      />
		<value name="Concepts"   />
		<value name="Cards"      />
		<value name="Replays"    />
		<value name="Novels"     />
		<value name="Statistics" />
		<value name="Settings"   />
	</xsl:variable>

	<xsl:for-each select="exsl:node-set($sections)/*">
		<a class="button" href="{php:functionString('makeurl', @name)}" >
			<xsl:if test="$current_section = @name">
				<xsl:attribute name="class">button pushed</xsl:attribute>
			</xsl:if>
			<xsl:value-of select="@name"/>
		</a>
		<xsl:choose>
			<xsl:when test="'Forum' = @name and $param/forum_notice = 'yes'">
				<img src="img/book.gif" alt="" width="18px" height="14px" title="New post" />
			</xsl:when>
			<xsl:when test="'Messages' = @name and $param/message_notice = 'yes'">
				<img src="img/new_post.gif" alt="" width="15px" height="10px" title="New message" />
			</xsl:when>
			<xsl:when test="'Games' = @name and $param/game_notice = 'yes'">
				<img src="img/battle.gif" alt="" width="20px" height="13px" title="Your turn" />
			</xsl:when>
			<xsl:when test="'Concepts' = @name and $param/concept_notice = 'yes'">
				<img src="img/new_card.gif" alt="" width="10px" height="14px" title="New card" />
			</xsl:when>
		</xsl:choose>
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


<xsl:template name="outer_navbar">
	<!-- navigation bar for anonymous user -->
	<xsl:variable name="param" select="$params/navbar" />

	<div id="login_box">

	<div id="login_inputs">
		<p>
			<img src="img/username.png" width="25px" height="20px" alt="username" title="username" />
			<input type="text" name="Username" maxlength="20" tabindex="1" />
		</p>
		<p>
			<img src="img/password.png" width="25px" height="20px" alt="password" title="password" />
			<input type="password" name="Password" maxlength="20" tabindex="2" />
		</p>
		<p>
			<button type="submit" name="Login" tabindex="3">Login</button>
			<button type="submit" name="Registration" tabindex="4">Register</button>
		</p>
	</div>

	<h1>MArcomage</h1>
	<h2>Free multiplayer on-line fantasy card game</h2>

	<div id="login_message">
		<xsl:if test="$param/error_msg != ''">
			<p class="error"><xsl:value-of select="$param/error_msg"/></p>
		</xsl:if>
		<xsl:if test="$param/warning_msg != ''">
			<p class="warning"><xsl:value-of select="$param/warning_msg"/></p>
		</xsl:if>
		<xsl:if test="$param/info_msg != ''">
			<p class="info"><xsl:value-of select="$param/info_msg"/></p>
		</xsl:if>
	</div>

	<!-- sections menubar -->
	<div id="sections">
		<xsl:variable name="sections">
			<value name="Webpage"  value="Introduction"     />
			<value name="Help"     value="Game manual"      />
			<value name="Forum"    value="Discussion forum" />
			<value name="Cards"    value="MArcomage cards"  />
			<value name="Concepts" value="Card concepts"    />
			<value name="Novels"   value="Fantasy novels"   />
		</xsl:variable>
		<xsl:for-each select="exsl:node-set($sections)/*">
			<a class="button" href="{php:functionString('makeurl', @name)}" >
				<xsl:if test="$param/section_name = @name">
					<xsl:attribute name="class">button pushed</xsl:attribute>
				</xsl:if>
				<xsl:value-of select="@value"/>
			</a>
		</xsl:for-each>
	</div>

	</div>
</xsl:template>


</xsl:stylesheet>
