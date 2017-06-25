<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
                xmlns="http://www.w3.org/1999/xhtml"
                xmlns:am="http://arcomage.net"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:exsl="http://exslt.org/common"
                extension-element-prefixes="exsl">
    <xsl:output omit-xml-declaration="yes" method="xml" version="1.0" encoding="UTF-8" indent="yes"
                doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN"
                doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"/>

    <!-- includes -->
    <xsl:include href="utils.xsl"/>


    <!-- global copy of the input xml document -->
    <xsl:variable name="params" select="params"/>

    <xsl:template match="/">
        <xsl:variable name="param" select="$params/main"/>

        <html xmlns="http://www.w3.org/1999/xhtml" xmlns:am="http://arcomage.net" lang="en" xml:lang="en">
            <xsl:choose>
                <xsl:when test="$param/include_layout = 'no'">
                    <xsl:apply-templates select="$param/section"/>
                </xsl:when>
                <xsl:otherwise>
                    <xsl:variable name="section_name" select="$param/section_name"/>
                    <xsl:variable name="current_section" select="am:lowercase($section_name)"/>
                    <!-- HTML header -->
                    <head>
                        <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
                        <meta name="description" content="free online fantasy card game inspired by original Arcomage"/>
                        <meta name="author" content="Mojmír Fendek, Viktor Štujber"/>
                        <meta name="keywords" content="Arcomage, MArcomage, multiplayer, free, online, fantasy, card game, fantasy novels"/>

                        <script type="text/javascript" src="js/dist/main.js?v={$param/cc_version}"/>
                        <link rel="stylesheet" href="styles/css/main.css?v={$param/cc_version}" type="text/css" title="standard style"/>
                        <link rel="stylesheet" href="styles/general.css?v={$param/cc_version}" type="text/css" title="standard style"/>
                        <link rel="stylesheet" href="styles/card.css?v={$param/cc_version}" type="text/css" title="standard style"/>
                        <xsl:choose>
                            <xsl:when test="$param/is_logged_in = 'yes'">
                                <link rel="stylesheet" href="styles/menubar.css?v={$param/cc_version}" type="text/css" title="standard style"/>
                            </xsl:when>
                            <xsl:otherwise>
                                <link rel="stylesheet" href="styles/login.css?v={$param/cc_version}" type="text/css" title="standard style"/>
                            </xsl:otherwise>
                        </xsl:choose>
                        <link rel="stylesheet" href="styles/{$current_section}.css?v={$param/cc_version}" type="text/css" title="standard style"/>
                        <link rel="stylesheet" href="styles/skins/skin{$param/skin}.css?v={$param/cc_version}" type="text/css" title="standard style"/>
                        <xsl:if test="$param/new_user = 'yes'">
                            <link rel="stylesheet" href="styles/intro.css?v={$param/cc_version}" type="text/css" title="standard style"/>
                        </xsl:if>
                        <xsl:if test="$param/new_level_gained &gt; 0">
                            <link rel="stylesheet" href="styles/levelup.css?v={$param/cc_version}" type="text/css" title="standard style"/>
                        </xsl:if>
                        <link rel="icon" href="img/favicon.png?v={$param/cc_version}" type="image/png"/>
                        <title>
                            <xsl:if test="$param/subsection != ''">
                                <xsl:value-of select="$param/subsection"/>
                                <xsl:text> - </xsl:text>
                            </xsl:if>
                            <xsl:value-of select="$section_name"/>
                            <xsl:if test="$section_name = 'Games' and $param/current_games &gt; 0">
                                <xsl:text>(</xsl:text>
                                <xsl:value-of select="$param/current_games"/>
                                <xsl:text>)</xsl:text>
                            </xsl:if>
                            <xsl:text> - MArcomage</xsl:text>
                        </title>
                        <xsl:if test="$param/include_captcha = 'yes'">
                            <script src='https://www.google.com/recaptcha/api.js'/>
                        </xsl:if>
                    </head>
                    <body data-section="{$current_section}" data-tutorial="{$param/tutorial_active}">
                        <div class="container">
                            <form enctype="multipart/form-data" method="post">

                                <!-- navigation bar -->
                                <header>
                                    <xsl:choose>
                                        <xsl:when test="$param/is_logged_in = 'yes'">
                                            <xsl:call-template name="inner_navbar"/>
                                        </xsl:when>
                                        <xsl:otherwise>
                                            <xsl:call-template name="outer_navbar"/>
                                        </xsl:otherwise>
                                    </xsl:choose>
                                </header>

                                <!-- content goes here -->
                                <xsl:apply-templates select="$param/section"/>

                                <!-- session string -->
                                <xsl:if test="$param/session_id">
                                    <div>
                                        <input type="hidden" name="username" value="{$param/username}"/>
                                        <input type="hidden" name="session_id" value="{$param/session_id}"/>
                                    </div>
                                </xsl:if>

                                <!-- display welcome message for new users -->
                                <xsl:if test="$param/new_user = 'yes'">
                                    <div class="modal fade" id="intro-dialog" role="dialog">
                                        <div class="vertical-alignment-helper">
                                            <div class="modal-dialog vertical-align-center">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <button name="close-modal" type="button" class="close" data-dismiss="modal">&#10006;</button>
                                                        <p class="modal-title">Welcome to MArcomage</p>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p>
                                                            Greetings <b><xsl:value-of select="$param/player_name"/></b>.
                                                            By playing games you earn <b>experience</b> points and once you have sufficient
                                                            amount, you will gain a new <b>level</b>. This will unlock new <b>cards</b> and even
                                                            entire new <b>sections</b> to explore. Now, without further delay, let's play the game.
                                                        </p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button name="intro-dialog-dismiss" type="button" class="btn btn-default" data-dismiss="modal">
                                                            <span class="btn-inner"><span class="btn-text">Close</span></span>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </xsl:if>

                                <!-- display levelup message -->
                                <xsl:if test="$param/new_level_gained &gt; 0">
                                    <xsl:variable name="levels">
                                        <value id="1" section="Decks" desc="You are now able to improve your decks."/>
                                        <value id="2" section="Cards" desc="You are now able to access complete card database."/>
                                        <value id="3" section="Replays" desc="You may now re-watch every finished game."/>
                                        <value id="4" section="Concepts" desc="You may now publish card concepts."/>
                                        <value id="5" section="Statistics" desc="You may now access game statistics."/>
                                        <value id="6" section="" desc=""/>
                                        <value id="7" section="" desc=""/>
                                        <value id="8" section="" desc=""/>
                                        <value id="9" section="" desc=""/>
                                        <value id="10" section="" desc="You may now play AI challenges (games section) and import shared decks of other players (decks section)."/>
                                    </xsl:variable>

                                    <xsl:variable name="levelup_data" select="exsl:node-set($levels)/*[@id = $param/new_level_gained]"/>
                                    <xsl:if test="$levelup_data">
                                        <div class="modal fade" id="level-up-dialog" role="dialog">
                                            <div class="vertical-alignment-helper">
                                                <div class="modal-dialog vertical-align-center">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <button name="close-modal" type="button" class="close" data-dismiss="modal">&#10006;</button>
                                                            <p class="modal-title">Congratulations, you have reached level <xsl:value-of select="$levelup_data/@id"/> !</p>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p>
                                                                <xsl:if test="$levelup_data/@section != ''">
                                                                    <p><b><xsl:value-of select="$levelup_data/@section"/></b> section unlocked.</p>
                                                                    <input type="hidden" name="unlock_section" value="{$levelup_data/@section}"/>
                                                                </xsl:if>
                                                                <xsl:if test="$levelup_data/@desc != ''">
                                                                    <p><xsl:value-of select="$levelup_data/@desc"/></p>
                                                                </xsl:if>
                                                                <xsl:if test="count($param/new_cards/*) &gt; 0">
                                                                    <p>New cards available.</p>
                                                                    <div class="unlocked-cards">
                                                                        <xsl:for-each select="$param/new_cards/*">
                                                                            <xsl:sort select="name" order="ascending"/>
                                                                            <div>
                                                                                <xsl:copy-of select="am:cardString(
                                                                                    current(), $param/card_old_look, $param/card_insignias, $param/card_foils
                                                                                )"/>
                                                                            </div>
                                                                        </xsl:for-each>
                                                                    </div>
                                                                </xsl:if>
                                                            </p>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button name="level-up-dialog-dismiss" type="button" class="btn btn-default" data-dismiss="modal">
                                                                <span class="btn-inner"><span class="btn-text">Close</span></span>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </xsl:if>
                                </xsl:if>
                            </form>
                        </div>
                        <div id="card-lookup"/>

                        <!-- error message dialog -->
                        <div class="modal fade" id="error-message" role="dialog">
                            <div class="vertical-alignment-helper">
                                <div class="modal-dialog vertical-align-center">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <button name="close-modal" type="button" class="close" data-dismiss="modal">&#10006;</button>
                                            <p class="modal-title">An error has occurred</p>
                                        </div>
                                        <div class="modal-body"><p/></div>
                                        <div class="modal-footer">
                                            <button name="error-message-dismiss" type="button" class="btn btn-default">
                                                <span class="btn-inner"><span class="btn-text">Ok</span></span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- info message dialog -->
                        <div class="modal fade" id="info-message" role="dialog">
                            <div class="vertical-alignment-helper">
                                <div class="modal-dialog vertical-align-center">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <button name="close-modal" type="button" class="close" data-dismiss="modal">&#10006;</button>
                                            <p class="modal-title"/>
                                        </div>
                                        <div class="modal-body"/>
                                        <div class="modal-footer">
                                            <button name="info-message-dismiss" type="button" class="btn btn-default">
                                                <span class="btn-inner"><span class="btn-text">Ok</span></span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- confirm dialog -->
                        <div class="modal fade" id="confirm-message" role="dialog">
                            <div class="vertical-alignment-helper">
                                <div class="modal-dialog vertical-align-center">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <button name="close-modal" type="button" class="close" data-dismiss="modal">&#10006;</button>
                                            <p class="modal-title"/>
                                        </div>
                                        <div class="modal-body"/>
                                        <div class="modal-footer">
                                            <button name="confirm" type="button" class="btn btn-default">
                                                <span class="btn-inner"><span class="btn-text">Ok</span></span>
                                            </button>
                                            <button name="cancel" type="button" class="btn btn-default" data-dismiss="modal">
                                                <span class="btn-inner">
                                                    <span class="btn-text">Cancel</span>
                                                </span>
                                            </button>
                                            <input name="confirmed" type="hidden" value=""/>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </body>
                </xsl:otherwise>
            </xsl:choose>
        </html>
    </xsl:template>


    <xsl:template name="inner_navbar">
        <!-- navigation bar for authenticated user -->
        <xsl:variable name="param" select="$params/navbar"/>

        <xsl:variable name="current_section" select="$param/section_name"/>

        <div id="menu-bar" class="skin-label top-level">
            <div class="row">
                <div class="col-md-2">
                    <div id="menu-player-info">
                        <div class="skin-text">
                            <div>
                                <a class="achievement-link" href="{am:makeUrl('Players_achievements', 'Profile', $param/player_name)}">
                                    <img class="icon" height="16" width="16" src="img/achievement.png"
                                         alt="{$param/player_name}'s achievements" title="{$param/player_name}'s achievements"/>
                                </a>

                                <a href="{am:makeUrl('Players_details', 'Profile', $param/player_name)}">
                                    [<xsl:value-of select="$param/level"/>] <xsl:value-of select="$param/player_name"/>
                                </a>
                            </div>
                            <div class="exp-progress-bar">
                                <xsl:attribute name="title">
                                    <xsl:value-of select="$param/exp"/>
                                    <xsl:text> / </xsl:text>
                                    <xsl:value-of select="$param/next_level"/>
                                </xsl:attribute>
                                <div>
                                    <xsl:attribute name="style">
                                        <xsl:text>width: </xsl:text>
                                        <xsl:value-of select="$param/exp_bar * 100"/>
                                        <xsl:text>%</xsl:text>
                                    </xsl:attribute>
                                </div>
                            </div>
                            <div class="navbar-notifications">
                                <xsl:if test="$param/game_notice = 'yes'">
                                    <a class="image-link" href="{am:makeUrl('Games')}">
                                        <img src="img/battle.gif" alt="" width="20" height="13" title="It's your turn in one of your games"/>
                                    </a>
                                </xsl:if>
                                <xsl:if test="$param/message_notice = 'yes'">
                                    <a class="image-link" href="{am:makeUrl('Messages')}">
                                        <img src="img/new_post.gif" alt="" width="15" height="10" title="New private message"/>
                                    </a>
                                </xsl:if>
                                <xsl:if test="$param/forum_notice = 'yes'">
                                    <a class="image-link" href="{am:makeUrl('Forum')}">
                                        <img src="img/book.gif" alt="" width="18" height="14" title="New forum post"/>
                                    </a>
                                </xsl:if>
                                <xsl:if test="$param/concept_notice = 'yes'">
                                    <a class="image-link" href="{am:makeUrl('Concepts')}">
                                        <img src="img/new_card.gif" alt="" width="10" height="14" title="New card concept"/>
                                    </a>
                                </xsl:if>
                                <button class="button-icon" type="submit" name="reset_notification" value="{am:urlEncode($current_section)}" title="Clear notifications">
                                    <span class="glyphicon glyphicon-repeat"/>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 nav-buttons">
                    <xsl:variable name="sections">
                        <!-- section name, level requirement -->
                        <value name="Games" level="0"/>
                        <value name="Decks" level="1"/>
                        <value name="Players" level="0"/>
                        <value name="Replays" level="3"/>
                        <value name="Cards" level="2"/>
                        <value name="Concepts" level="5"/>
                    </xsl:variable>
                    <nav class="menu-center">
                        <xsl:for-each select="exsl:node-set($sections)/*">
                            <xsl:if test="$param/level &gt;= @level">
                                <a class="button" href="{am:makeUrl(@name)}">
                                    <xsl:if test="$current_section = @name">
                                        <xsl:attribute name="class">button marked_button</xsl:attribute>
                                    </xsl:if>
                                    <xsl:value-of select="@name"/>
                                </a>
                            </xsl:if>
                        </xsl:for-each>
                    </nav>
                </div>
                <div class="col-md-4 nav-buttons">
                    <xsl:variable name="sections">
                        <value name="Webpage" icon="home"/>
                        <value name="Messages" icon="envelope" />
                        <value name="Forum" icon="list-alt"/>
                        <value name="Help" icon="question-sign"/>
                        <value name="Settings" icon="wrench"/>
                        <value name="Statistics" icon="stats"/>
                        <value name="Novels" icon="book"/>
                    </xsl:variable>
                    <xsl:for-each select="exsl:node-set($sections)/*">
                        <a class="button button-icon" href="{am:makeUrl(@name)}" title="{@name}">
                            <xsl:if test="$current_section = @name">
                                <xsl:attribute name="class">button button-icon marked_button</xsl:attribute>
                            </xsl:if>
                            <span class="glyphicon glyphicon-{@icon}"/>
                        </a>
                    </xsl:for-each>
                    <button class="button-icon" type="submit" name="Logout" accesskey="q" title="Logout">
                        <span class="glyphicon glyphicon-log-out"/>
                    </button>
                </div>
            </div>
            <div class="row">
                <xsl:if test="$param/error_msg != ''">
                    <p class="information-line error">
                        <xsl:value-of select="$param/error_msg"/>
                    </p>
                </xsl:if>
                <xsl:if test="$param/warning_msg != ''">
                    <p class="information-line warning">
                        <xsl:value-of select="$param/warning_msg"/>
                    </p>
                </xsl:if>
                <xsl:if test="$param/info_msg != ''">
                    <p class="information-line info">
                        <xsl:value-of select="$param/info_msg"/>
                    </p>
                </xsl:if>
                <xsl:if test="($param/error_msg = '') and ($param/warning_msg = '') and ($param/info_msg = '')">
                    <p class="blank-line"/>
                </xsl:if>
            </div>
        </div>

    </xsl:template>


    <xsl:template name="outer_navbar">
        <!-- navigation bar for anonymous user -->
        <xsl:variable name="param" select="$params/navbar"/>

        <div id="login-box" class="top-level">
            <div class="row">
                <div class="col-sm-5">
                    <h1>MArcomage</h1>
                    <h2>Free multiplayer on-line fantasy card game</h2>
                </div>
                <div class="col-sm-6">
                    <div id="login_area">
                        <div id="login-inputs" class="row">
                            <div class="col-md-5">
                                <input type="text" name="username" title="username" maxlength="20" placeholder="Username..." tabindex="1"/>
                                <img src="img/username.png" width="25" height="20" alt="username"/>
                            </div>
                            <div class="col-md-5">
                                <input type="password" name="password" title="password" maxlength="20" placeholder="Password..." tabindex="2"/>
                                <img src="img/password.png" width="25" height="20" alt="password"/>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" name="login" tabindex="3">Login</button>
                            </div>
                        </div>

                        <p id="login-message">
                            <xsl:if test="$param/error_msg != ''">
                                <span class="error">
                                    <xsl:value-of select="$param/error_msg"/>
                                </span>
                            </xsl:if>
                            <xsl:if test="$param/warning_msg != ''">
                                <span class="warning">
                                    <xsl:value-of select="$param/warning_msg"/>
                                </span>
                            </xsl:if>
                            <xsl:if test="$param/info_msg != ''">
                                <span class="info">
                                    <xsl:value-of select="$param/info_msg"/>
                                </span>
                            </xsl:if>
                        </p>
                    </div>
                </div>
                <div class="col-sm-1">
                    <div id="social-links">
                        <a href="{$param/google_plus}">
                            <img src="img/google_plus.png" width="16" height="16" alt="google plus page"/>
                        </a>
                        <a href="{$param/facebook}">
                            <img src="img/facebook.png" width="16" height="16" alt="facebook page"/>
                        </a>
                    </div>
                </div>
            </div>
            <div class="row nav-buttons">
                <div class="col-sm-2">
                    <xsl:variable name="sections">
                        <value name="Webpage" icon="home"/>
                        <value name="Help" icon="question-sign"/>
                        <value name="Novels" icon="book"/>
                    </xsl:variable>
                    <xsl:for-each select="exsl:node-set($sections)/*">
                        <a class="button button-icon" href="{am:makeUrl(@name)}" title="{@name}">
                            <xsl:if test="$param/section_name = @name">
                                <xsl:attribute name="class">button button-icon marked_button</xsl:attribute>
                            </xsl:if>
                            <span class="glyphicon glyphicon-{@icon}"/>
                        </a>
                    </xsl:for-each>
                </div>
                <div class="col-sm-10">
                    <!-- sections menu bar -->
                    <nav id="sections">
                        <button type="submit" name="registration" tabindex="4">
                            <xsl:if test="$param/current = 'Registration'">
                                <xsl:attribute name="class">marked_button</xsl:attribute>
                            </xsl:if>
                            <xsl:text>Register</xsl:text>
                        </button>
                        <xsl:variable name="sections">
                            <value name="Forum"/>
                            <value name="Players"/>
                            <value name="Cards"/>
                            <value name="Concepts"/>
                        </xsl:variable>
                        <xsl:for-each select="exsl:node-set($sections)/*">
                            <a class="button" href="{am:makeUrl(@name)}">
                                <xsl:if test="$param/section_name = @name">
                                    <xsl:attribute name="class">button marked_button</xsl:attribute>
                                </xsl:if>
                                <xsl:value-of select="@name"/>
                            </a>
                        </xsl:for-each>
                    </nav>
                </div>
            </div>
        </div>
    </xsl:template>


</xsl:stylesheet>
