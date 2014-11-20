<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
                xmlns="http://www.w3.org/1999/xhtml"
                xmlns:am="http://arcomage.netvor.sk"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:exsl="http://exslt.org/common"
                extension-element-prefixes="exsl">
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
	<meta name="description" content="free online fantasy card game inspired by original Arcomage" />
	<meta name="author" content="Mojmír Fendek, Viktor Štujber" />
	<meta name="keywords" content="Arcomage, MArcomage, multiplayer, free, online, fantasy, card game, fantasy novels"/>
	<link rel="stylesheet" href="styles/general.css" type="text/css" title="standard style" />
	<link rel="stylesheet" href="styles/card.css" type="text/css" title="standard style" />
	<link rel="stylesheet" href="styles/cursor.css" type="text/css" title="standard style" />
	<xsl:choose>
		<xsl:when test="$param/is_logged_in = 'yes'">
			<link rel="stylesheet" href="styles/menubar.css" type="text/css" title="standard style" />
		</xsl:when>
		<xsl:otherwise>
			<link rel="stylesheet" href="styles/login.css" type="text/css" title="standard style" />
		</xsl:otherwise>
	</xsl:choose>
	<link rel="stylesheet" href="styles/{$current_section}.css" type="text/css" title="standard style" />
	<link rel="stylesheet" href="styles/skins/skin{$param/skin}.css" type="text/css" title="standard style" />
	<xsl:if test="$param/new_user = 'yes'">
		<link rel="stylesheet" href="styles/intro.css" type="text/css" title="standard style" />
	</xsl:if>
	<xsl:if test="$param/new_level_gained &gt; 0">
		<link rel="stylesheet" href="styles/levelup.css" type="text/css" title="standard style" />
	</xsl:if>
	<link rel="icon" href="img/favicon.png" type="image/png" />
	<title>
		<xsl:if test="$param/subsection != ''">
			<xsl:value-of select="$param/subsection" />
			<xsl:text> - </xsl:text>
		</xsl:if>
		<xsl:value-of select="$section_name" />
		<xsl:if test="$section_name = 'Games' and $param/current_games &gt; 0"> (<xsl:value-of select="$param/current_games" />)</xsl:if>
		<xsl:text> - MArcomage</xsl:text>
	</title>
	<script type="text/javascript" src="javascript/jquery/jquery.js"></script>
	<script type="text/javascript" src="javascript/jquery/jquery_ui.js"></script>
	<script type="text/javascript" src="javascript/scrollto.js"></script>
	<script type="text/javascript" src="javascript/cookie.js"></script>
	<script type="text/javascript" src="javascript/cursor.js"></script>
	<script type="text/javascript" src="javascript/utils.js"></script>
	<script type="text/javascript" src="javascript/{$current_section}.js"></script>
	<xsl:if test="$param/new_user = 'yes'">
		<script type="text/javascript" src="javascript/intro.js"></script>
	</xsl:if>
	<xsl:if test="$param/new_level_gained &gt; 0">
		<script type="text/javascript" src="javascript/levelup.js"></script>
	</xsl:if>
	<xsl:if test="$param/level = 0">
		<script type="text/javascript" src="javascript/highlight.js"></script>
	</xsl:if>
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

	<!-- display welcome message for new users -->
	<xsl:if test="$param/new_user = 'yes'">
		<div id="intro_dialog" title="Introduction" style="display: none">
			<h3>Welcome to MArcomage</h3>
			<p>Greetings <b><xsl:value-of select="$param/player_name" /></b>. By playing games you earn <b>experience</b> points and once you have sufficient amount, you will gain a new <b>level</b>. This will unlock new <b>cards</b> and even entire new <b>sections</b> to explore. Now, without further delay, let's play the game.</p>
		</div>
	</xsl:if>

	<!-- display levelup message -->
	<xsl:if test="$param/new_level_gained &gt; 0">
		<xsl:variable name="levels">
			<value id="1"  section="Decks"      desc="You are now able to improve your decks."            />
			<value id="2"  section="Cards"      desc="You are now able to access complete card database." />
			<value id="3"  section="Replays"    desc="You may now re-watch every finished game."          />
			<value id="4"  section="Novels"     desc="You may now access fantasy novels."                 />
			<value id="5"  section="Concepts"   desc="You may now publish card concepts."                 />
			<value id="6"  section="Statistics" desc="You may now access game statistics."                />
			<value id="7"  section=""           desc="" />
			<value id="8"  section=""           desc="" />
			<value id="9"  section=""           desc="" />
			<value id="10" section=""           desc="You may now play AI challenges (games section) and import shared decks of other players (decks section)." />
		</xsl:variable>

		<xsl:variable name="levelup_data" select="exsl:node-set($levels)/*[@id = $param/new_level_gained]" />
		<xsl:if test="$levelup_data">
			<div id="levelup_dialog" title="Level up!" style="display: none">
				<h3>Congratulations, you have reached level <xsl:value-of select="$levelup_data/@id" /> !</h3>
				<xsl:if test="$levelup_data/@section != ''">
					<p><b><xsl:value-of select="$levelup_data/@section" /></b> section unlocked.</p>
					<input type="hidden" name="unlock_section" value="{$levelup_data/@section}" />
				</xsl:if>
				<xsl:if test="$levelup_data/@desc != ''">
					<p><xsl:value-of select="$levelup_data/@desc" /></p>
				</xsl:if>
				<xsl:if test="count($param/new_cards/*) &gt; 0">
					<p>New cards available.</p>
					<table class="centered" cellpadding="0" cellspacing="0" >
						<!-- number of columns (configurable) -->
						<xsl:variable name="columns" select="10"/>
						<xsl:for-each select="$param/new_cards/*[position() &lt;= floor(((count($param/new_cards/*) - 1) div $columns)) + 1]">
							<tr valign="top">
									<xsl:variable name="i" select="position()"/>
									<xsl:for-each select="$param/new_cards/*[position() &gt;= (($i - 1)*$columns + 1) and position() &lt;= $i*$columns]">
										<td>
											<xsl:copy-of select="am:cardstring(current(), $param/c_img, $param/c_oldlook, $param/c_insignias, $param/c_foils)" />
										</td>
									</xsl:for-each>
							</tr>
						</xsl:for-each>
					</table>
				</xsl:if>
			</div>
		</xsl:if>
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
	<div class="skin_text">
		<a href="{am:makeurl('Players_details', 'Profile', $param/player_name)}"><xsl:value-of select="$param/player_name"/> [<xsl:value-of select="$param/level" />]</a>
		<a class="achievement_link" href="{am:makeurl('Players_achievements', 'Profile', $param/player_name)}">
			<img class="icon" height="16px" width="16px" src="img/achievement.png" alt="{$param/player_name}'s achievements" title="{$param/player_name}'s achievements" />
		</a>
		<div class="progress_bar">
			<xsl:attribute name="title">
				<xsl:value-of select="$param/exp"/>
				<xsl:text> / </xsl:text>
				<xsl:value-of select="$param/nextlevel"/>
				</xsl:attribute>
			<div><xsl:attribute name="style">width: <xsl:value-of select="round($param/expbar * 50)"/>px</xsl:attribute></div>
		</div>
	</div>
	</div>

	<div id="menu_float_right">
		<button type="submit" name="reset_notification" value= "{am:urlencode($current_section)}">RN</button>
		<button type="submit" name="Logout" accesskey="q">Logout</button>
	</div>

	<div id="menu_center">

	<xsl:variable name="sections">
		<!-- section name, level requirement -->
		<value name="Webpage"    level="0" />
		<value name="Help"       level="0" />
		<value name="Forum"      level="0" />
		<value name="Messages"   level="0" />
		<value name="Players"    level="0" />
		<value name="Games"      level="0" />
		<value name="Decks"      level="1" />
		<value name="Concepts"   level="5" />
		<value name="Cards"      level="2" />
		<value name="Replays"    level="3" />
		<value name="Novels"     level="4" />
		<value name="Statistics" level="6" />
		<value name="Settings"   level="0" />
	</xsl:variable>

	<xsl:for-each select="exsl:node-set($sections)/*">
		<xsl:if test="$param/level &gt;= @level">
			<a class="button" href="{am:makeurl(@name)}" >
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


<xsl:template name="outer_navbar">
	<!-- navigation bar for anonymous user -->
	<xsl:variable name="param" select="$params/navbar" />

	<div id="login_box">

  <span id="social_links">
    <a href="https://plus.google.com/101815655483915729081"><img src="img/google_plus.png" width="16px" height="16px" alt="google plus page" /></a>
    <a href="http://www.facebook.com/pages/MArcomage/182322255140456"><img src="img/facebook.png" width="16px" height="16px" alt="facebook page" /></a>
  </span>

  <div id="login_area">
    <div id="login_inputs">
      <img src="img/username.png" width="25px" height="20px" alt="username" />
      <input type="text" name="Username" title="username" maxlength="20" tabindex="1" />
      <img src="img/password.png" width="25px" height="20px" alt="password" />
      <input type="password" name="Password" title="password" maxlength="20" tabindex="2" />
      <button class="marked_button" type="submit" name="Login" tabindex="3">Login</button>
    </div>

    <p id="login_message">
      <xsl:if test="$param/error_msg != ''">
        <span class="error"><xsl:value-of select="$param/error_msg"/></span>
      </xsl:if>
      <xsl:if test="$param/warning_msg != ''">
        <span class="warning"><xsl:value-of select="$param/warning_msg"/></span>
      </xsl:if>
      <xsl:if test="$param/info_msg != ''">
        <span class="info"><xsl:value-of select="$param/info_msg"/></span>
      </xsl:if>
    </p>
  </div>

  <h1>MArcomage</h1>
  <h2>Free multiplayer on-line fantasy card game</h2>

  <div class="clear_floats"></div>

	<!-- sections menubar -->
	<div id="sections">
    <button class="marked_button" type="submit" name="Registration" tabindex="4">Register</button>
		<xsl:variable name="sections">
			<value name="Webpage"  />
			<value name="Help"     />
			<value name="Forum"    />
			<value name="Players"  />
			<value name="Cards"    />
			<value name="Concepts" />
			<value name="Novels"   />
		</xsl:variable>
		<xsl:for-each select="exsl:node-set($sections)/*">
			<a class="button" href="{am:makeurl(@name)}" >
				<xsl:if test="$param/section_name = @name">
					<xsl:attribute name="class">button pushed</xsl:attribute>
				</xsl:if>
				<xsl:value-of select="@name"/>
			</a>
		</xsl:for-each>
	</div>

	</div>
</xsl:template>


</xsl:stylesheet>
