<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
                xmlns="http://www.w3.org/1999/xhtml"
                xmlns:am="http://arcomage.net"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:exsl="http://exslt.org/common"
                xmlns:str="http://exslt.org/strings"
                extension-element-prefixes="exsl str">
    <xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes"
                doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN"
                doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"/>

    <!-- includes -->
    <xsl:include href="main.xsl"/>
    <xsl:include href="game_helpers.xsl"/>


    <xsl:template match="section[. = 'Games']">
        <xsl:variable name="param" select="$params/games"/>
        <xsl:variable name="activeDecks" select="count($param/decks/*)"/>
        <xsl:variable name="list" select="$param/list"/>
        <xsl:variable name="timeoutValues">
            <value name="0" text="no limit"/>
            <value name="86400" text="1 day"/>
            <value name="43200" text="12 hours"/>
            <value name="21600" text="6 hours"/>
            <value name="10800" text="3 hours"/>
            <value name="3600" text="1 hour"/>
            <value name="1800" text="30 minutes"/>
            <value name="300" text="5 minutes"/>
        </xsl:variable>

        <div id="games">
            <xsl:if test="$param/games_subsection = 'started_games'">
                <!-- begin active games list -->
                <div id="active-games" class="skin-label top-level">
                    <xsl:copy-of select="am:gameSectionNavigation($param/games_subsection)"/>
                    <xsl:choose>
                        <xsl:when test="count($list/*) &gt; 0">
                            <div class="responsive-table table-sm skin-text">
                                <!-- table header -->
                                <div class="row">
                                    <div class="col-sm-2">
                                        <p>Opponent</p>
                                    </div>
                                    <div class="col-sm-2">
                                        <p>Modes</p>
                                    </div>
                                    <div class="col-sm-3">
                                        <p>Info</p>
                                    </div>
                                    <div class="col-sm-2">
                                        <p>Timeout</p>
                                    </div>
                                    <div class="col-sm-1">
                                        <p>Round</p>
                                    </div>
                                    <div class="col-sm-2">
                                        <p>Last action</p>
                                    </div>
                                </div>

                                <!-- table body -->
                                <xsl:for-each select="$list/*">
                                    <div class="row table-row details">
                                        <div class="col-sm-2">
                                            <p>
                                                <a class="profile" href="{am:makeUrl('Games_details', 'current_game', game_id)}">
                                                    <xsl:choose>
                                                        <xsl:when test="opponent = $param/system_name">
                                                            <xsl:copy-of select="am:playerName(opponent, ai, $param/system_name)"/>
                                                        </xsl:when>
                                                        <xsl:otherwise>
                                                            <xsl:value-of select="opponent"/>
                                                        </xsl:otherwise>
                                                    </xsl:choose>
                                                </a>

                                                <xsl:if test="opponent != $param/system_name and active = 'yes'">
                                                    <span class="icon-player-activity online" title="online"/>
                                                </xsl:if>
                                            </p>
                                        </div>
                                        <div class="col-sm-2">
                                            <p>
                                                <xsl:copy-of select="am:gameModeFlags(
                                                    am:hasGameMode(game_modes, 'HiddenCards'),
                                                    am:hasGameMode(game_modes, 'FriendlyPlay'),
                                                    am:hasGameMode(game_modes, 'LongMode'),
                                                    am:hasGameMode(game_modes, 'AIMode'),
                                                    ai
                                                )"/>
                                            </p>
                                        </div>
                                        <div class="col-sm-3">
                                            <xsl:choose>
                                                <xsl:when test="game_state = 'in progress' and is_dead = 'yes'">
                                                    <p class="ended-game">Can be aborted</p>
                                                </xsl:when>
                                                <xsl:when test="game_state = 'in progress' and finish_allowed = 'yes'">
                                                    <p class="ended-game">Can be finished</p>
                                                </xsl:when>
                                                <xsl:when test="game_state = 'in progress' and finish_move = 'yes'">
                                                    <p class="ended-game">AI move can be done</p>
                                                </xsl:when>
                                                <xsl:when test="game_state != 'in progress'">
                                                    <p class="ended-game">Game has ended</p>
                                                </xsl:when>
                                                <xsl:when test="ready = 'yes'">
                                                    <img src="img/battle.gif" alt="" width="20" height="13" title="It's your turn"/>
                                                </xsl:when>
                                            </xsl:choose>
                                        </div>
                                        <div class="col-sm-2"><p><xsl:value-of select="timeout"/></p></div>
                                        <div class="col-sm-1"><p><xsl:value-of select="round"/></p></div>
                                        <div class="col-sm-2">
                                            <p><xsl:copy-of select="am:dateTime(game_action, $param/timezone)"/></p>
                                        </div>
                                    </div>
                                </xsl:for-each>
                            </div>
                        </xsl:when>
                        <xsl:otherwise>
                            <p class="information-line warning">You have no active games.</p>
                        </xsl:otherwise>
                    </xsl:choose>
                </div>
                <!-- end active games list -->
            </xsl:if>
            <xsl:if test="$param/games_subsection = 'game_creation'">
                <!-- begin hosted games list -->
                <div id="hosted-games" class="skin-label top-level">
                    <xsl:copy-of select="am:gameSectionNavigation($param/games_subsection)"/>

                    <!-- warning messages -->
                    <xsl:if test="$activeDecks = 0">
                        <p class="information-line warning">You need at least one ready deck to host/join a game.</p>
                    </xsl:if>
                    <xsl:if test="$param/free_slots = 0">
                        <p class="information-line warning">You cannot host/enter any more games.</p>
                    </xsl:if>

                    <!-- subsection navigation -->
                    <p>
                        <a class="button" href="{am:makeUrl('Games', 'games_subsection', 'game_creation', 'subsection', 'free_games')}">
                            <xsl:if test="$param/current_subsection = 'free_games'">
                                <xsl:attribute name="class">button pushed</xsl:attribute>
                            </xsl:if>
                            <xsl:text>Hosted games</xsl:text>
                        </a>

                        <a class="button" href="{am:makeUrl('Games', 'games_subsection', 'game_creation', 'subsection', 'hosted_games')}">
                            <xsl:if test="$param/current_subsection = 'hosted_games'">
                                <xsl:attribute name="class">button pushed</xsl:attribute>
                            </xsl:if>
                            <xsl:text>My games</xsl:text>
                        </a>

                        <a class="button" href="{am:makeUrl('Games', 'games_subsection', 'game_creation', 'subsection', 'ai_games')}">
                            <xsl:if test="$param/current_subsection = 'ai_games'">
                                <xsl:attribute name="class">button pushed</xsl:attribute>
                            </xsl:if>
                            <xsl:text>AI games</xsl:text>
                        </a>
                    </p>

                    <xsl:choose>
                        <!-- begin subsection free games -->
                        <xsl:when test="$param/current_subsection = 'free_games'">

                            <!-- begin filters -->
                            <p class="filters">
                                <!-- selected deck -->
                                <xsl:if test="$activeDecks &gt; 0 and $param/free_slots &gt; 0">
                                    <span>Select deck</span>
                                    <select name="selected_deck" size="1">
                                        <xsl:if test="$param/random_deck_option = 'yes'">
                                            <option value="{$param/random_deck}">select random</option>
                                        </xsl:if>
                                        <xsl:for-each select="$param/decks/*">
                                            <option value="{deck_id}">
                                                <xsl:value-of select="deck_name"/>
                                            </option>
                                        </xsl:for-each>
                                        <xsl:if test="$param/show_challenges = 'yes'">
                                            <xsl:for-each select="$param/ai_challenges/*">
                                                <xsl:sort select="fullname" order="ascending"/>
                                                <option value="{name}" class="challenge-deck">
                                                    <xsl:value-of select="name"/>
                                                </option>
                                            </xsl:for-each>
                                        </xsl:if>
                                    </select>
                                    <button type="submit" name="quick_game">Quick game vs AI</button>
                                </xsl:if>

                                <xsl:variable name="modeOptions">
                                    <value name="ignore" value="none"/>
                                    <value name="include" value="include"/>
                                    <value name="exclude" value="exclude"/>
                                </xsl:variable>

                                <!-- hidden cards filter -->
                                <img class="icon" width="20" height="14" src="img/blind.png" alt="Hidden cards" title="Hidden cards"/>
                                <xsl:copy-of select="am:htmlSelectBox('hidden_cards', $param/hidden_cards, $modeOptions, '')"/>

                                <!-- friendly game filter -->
                                <img class="icon" width="20" height="14" src="img/friendly_play.png" alt="Friendly play" title="Friendly play"/>
                                <xsl:copy-of select="am:htmlSelectBox('friendly_play', $param/friendly_play, $modeOptions, '')"/>

                                <!-- friendly game filter -->
                                <img class="icon" width="20" height="14" src="img/long_mode.png" alt="Long mode" title="Long mode"/>
                                <xsl:copy-of select="am:htmlSelectBox('long_mode', $param/long_mode, $modeOptions, '')"/>
                                <button class="button-icon" type="submit" name="filter_hosted_games" title="Apply filters">
                                    <span class="glyphicon glyphicon-filter"/>
                                </button>
                            </p>
                            <!-- end filters -->

                            <!-- free games list -->
                            <xsl:choose>
                                <xsl:when test="count($param/free_games/*) &gt; 0">
                                    <div class="responsive-table table-sm skin-text">
                                        <!-- table header -->
                                        <div class="row">
                                            <div class="col-sm-3">
                                                <p>Opponent</p>
                                            </div>
                                            <div class="col-sm-2">
                                                <p>Modes</p>
                                            </div>
                                            <div class="col-sm-2">
                                                <p>Timeout</p>
                                            </div>
                                            <div class="col-sm-3">
                                                <p>Created</p>
                                            </div>
                                            <div class="col-sm-2">
                                                <p/>
                                            </div>
                                        </div>

                                        <!-- table body -->
                                        <xsl:for-each select="$param/free_games/*">
                                            <div class="row table-row">
                                                <div class="col-sm-3">
                                                    <p>
                                                        <xsl:if test="status != 'none'">
                                                            <img class="icon" width="20" height="14" src="img/{status}.png" alt="status flag" title="{status}"/>
                                                        </xsl:if>

                                                        <a class="profile" href="{am:makeUrl('Players_details', 'Profile', opponent)}">
                                                            <xsl:value-of select="opponent"/>
                                                        </a>

                                                        <xsl:if test="active = 'yes'">
                                                            <span class="icon-player-activity online" title="online"/>
                                                        </xsl:if>
                                                    </p>
                                                </div>
                                                <div class="col-sm-2">
                                                    <p>
                                                        <xsl:copy-of select="am:gameModeFlags(
                                                            am:hasGameMode(game_modes, 'HiddenCards'),
                                                            am:hasGameMode(game_modes, 'FriendlyPlay'),
                                                            am:hasGameMode(game_modes, 'LongMode')
                                                        )"/>
                                                    </p>
                                                </div>
                                                <div class="col-sm-2">
                                                    <p>
                                                        <xsl:variable name="timeout" select="timeout"/>
                                                        <xsl:value-of select="exsl:node-set($timeoutValues)/*[@name = $timeout]/@text"/>
                                                    </p>
                                                </div>
                                                <div class="col-sm-3">
                                                    <p>
                                                        <xsl:copy-of select="am:dateTime(game_action, $param/timezone)"/>
                                                    </p>
                                                </div>
                                                <div class="col-sm-2">
                                                    <xsl:if test="$activeDecks &gt; 0 and $param/free_slots &gt; 0">
                                                        <p>
                                                            <button type="submit" name="join_game" value="{game_id}">
                                                                <xsl:text>Join</xsl:text>
                                                            </button>
                                                        </p>
                                                    </xsl:if>
                                                </div>
                                            </div>
                                        </xsl:for-each>
                                    </div>
                                </xsl:when>
                                <xsl:otherwise>
                                    <p class="information-line warning">There are no hosted games.</p>
                                </xsl:otherwise>
                            </xsl:choose>

                        </xsl:when>
                        <!-- end subsection free games -->

                        <!-- begin subsection hosted games -->
                        <xsl:when test="$param/current_subsection = 'hosted_games'">

                            <!-- host new game interface -->
                            <xsl:if test="$activeDecks &gt; 0 and $param/free_slots &gt; 0">
                                <p class="misc">
                                    <span>Select deck</span>
                                    <select name="selected_deck" size="1">
                                        <xsl:if test="$param/random_deck_option = 'yes'">
                                            <option value="{$param/random_deck}">select random</option>
                                        </xsl:if>
                                        <xsl:for-each select="$param/decks/*">
                                            <option value="{deck_id}">
                                                <xsl:value-of select="deck_name"/>
                                            </option>
                                        </xsl:for-each>
                                        <xsl:if test="$param/show_challenges = 'yes'">
                                            <xsl:for-each select="$param/ai_challenges/*">
                                                <xsl:sort select="fullname" order="ascending"/>
                                                <option value="{name}" class="challenge-deck">
                                                    <xsl:value-of select="name"/>
                                                </option>
                                            </xsl:for-each>
                                        </xsl:if>
                                    </select>

                                    <img class="icon" width="20" height="14" src="img/blind.png" alt="Hidden cards" title="Hidden cards"/>
                                    <input type="checkbox" name="hidden_mode">
                                        <xsl:if test="$param/blind_flag = 'yes'">
                                            <xsl:attribute name="checked">checked</xsl:attribute>
                                        </xsl:if>
                                    </input>
                                    <img class="icon" width="20" height="14" src="img/friendly_play.png" alt="Friendly play" title="Friendly play"/>
                                    <input type="checkbox" name="friendly_mode">
                                        <xsl:if test="$param/friendly_flag = 'yes'">
                                            <xsl:attribute name="checked">checked</xsl:attribute>
                                        </xsl:if>
                                    </input>
                                    <img class="icon" width="20" height="14" src="img/long_mode.png" alt="Long mode" title="Long mode"/>
                                    <input type="checkbox" name="long_mode">
                                        <xsl:if test="$param/long_flag = 'yes'">
                                            <xsl:attribute name="checked">checked</xsl:attribute>
                                        </xsl:if>
                                    </input>
                                    <select name="turn_timeout" title="Turn timeout">
                                        <xsl:for-each select="exsl:node-set($timeoutValues)/*">
                                            <option value="{@name}">
                                                <xsl:if test="$param/timeout = @name">
                                                    <xsl:attribute name="selected">selected</xsl:attribute>
                                                </xsl:if>
                                                <xsl:value-of select="@text"/>
                                            </option>
                                        </xsl:for-each>
                                    </select>
                                    <button type="submit" name="host_game">Create game</button>
                                </p>
                            </xsl:if>

                            <!-- hosted games by player list -->
                            <xsl:choose>
                                <xsl:when test="count($param/hosted_games/*) &gt; 0">

                                    <div class="responsive-table table-sm skin-text">
                                        <!-- table header -->
                                        <div class="row">
                                            <div class="col-sm-4">
                                                <p>Modes</p>
                                            </div>
                                            <div class="col-sm-3">
                                                <p>Timeout</p>
                                            </div>
                                            <div class="col-sm-3">
                                                <p>Created</p>
                                            </div>
                                            <div class="col-sm-2">
                                                <p/>
                                            </div>
                                        </div>


                                        <!-- table body -->
                                        <xsl:for-each select="$param/hosted_games/*">
                                            <div class="row table-row">
                                                <div class="col-sm-4">
                                                    <p>
                                                        <xsl:copy-of select="am:gameModeFlags(
                                                            am:hasGameMode(game_modes, 'HiddenCards'),
                                                            am:hasGameMode(game_modes, 'FriendlyPlay'),
                                                            am:hasGameMode(game_modes, 'LongMode')
                                                        )"/>
                                                    </p>
                                                </div>
                                                <div class="col-sm-3">
                                                    <p>
                                                        <xsl:variable name="timeout" select="timeout"/>
                                                        <xsl:value-of select="exsl:node-set($timeoutValues)/*[@name = $timeout]/@text"/>
                                                    </p>
                                                </div>
                                                <div class="col-sm-3">
                                                    <p>
                                                        <xsl:copy-of select="am:dateTime(game_action, $param/timezone)"/>
                                                    </p>
                                                </div>
                                                <div class="col-sm-2">
                                                    <p>
                                                        <button type="submit" name="unhost_game" value="{game_id}" title="Delete game">
                                                            <span class="glyphicon glyphicon-trash"/>
                                                        </button>
                                                    </p>
                                                </div>
                                            </div>
                                        </xsl:for-each>
                                    </div>
                                </xsl:when>
                                <xsl:otherwise>
                                    <p class="information-line warning">There are no hosted games.</p>
                                </xsl:otherwise>
                            </xsl:choose>

                        </xsl:when>
                        <!-- end hosted games subsection -->

                        <!-- begin subsection AI games -->
                        <xsl:when test="$param/current_subsection = 'ai_games'">

                            <!-- host new AI game interface -->
                            <xsl:if test="$activeDecks &gt; 0 and $param/free_slots &gt; 0">
                                <p class="misc">
                                    <span>Select deck</span>
                                    <select name="selected_deck" size="1" title="your deck">
                                        <xsl:if test="$param/random_deck_option = 'yes'">
                                            <option value="{$param/random_deck}">select random</option>
                                        </xsl:if>
                                        <xsl:for-each select="$param/decks/*">
                                            <option value="{deck_id}">
                                                <xsl:value-of select="deck_name"/>
                                            </option>
                                        </xsl:for-each>
                                        <xsl:if test="$param/show_challenges = 'yes'">
                                            <xsl:for-each select="$param/ai_challenges/*">
                                                <xsl:sort select="fullname" order="ascending"/>
                                                <option value="{name}" class="challenge-deck">
                                                    <xsl:value-of select="name"/>
                                                </option>
                                            </xsl:for-each>
                                        </xsl:if>
                                    </select>

                                    <span>Select AI deck</span>
                                    <select name="selected_ai_deck" size="1" title="AI deck (used only when playing against AI)">
                                        <option value="starter_deck">starter deck</option>
                                        <xsl:if test="$param/random_deck_option = 'yes'">
                                            <option value="{$param/random_ai_deck}">select random</option>
                                        </xsl:if>
                                        <xsl:for-each select="$param/decks/*">
                                            <option value="{deck_id}">
                                                <xsl:value-of select="deck_name"/>
                                            </option>
                                        </xsl:for-each>
                                    </select>

                                    <img class="icon" width="20" height="14" src="img/blind.png" alt="Hidden cards" title="Hidden cards"/>
                                    <input type="checkbox" name="hidden_mode">
                                        <xsl:if test="$param/blind_flag = 'yes'">
                                            <xsl:attribute name="checked">checked</xsl:attribute>
                                        </xsl:if>
                                    </input>
                                    <img class="icon" width="20" height="14" src="img/long_mode.png" alt="Long mode" title="Long mode"/>
                                    <input type="checkbox" name="long_mode">
                                        <xsl:if test="$param/long_flag = 'yes'">
                                            <xsl:attribute name="checked">checked</xsl:attribute>
                                        </xsl:if>
                                    </input>
                                    <button type="submit" name="ai_game">Create game</button>
                                </p>
                            </xsl:if>

                            <!-- AI challenge interface (show only to players that finished tutorial) -->
                            <xsl:if test="$param/show_challenges = 'yes'">
                                <xsl:if test="$activeDecks &gt; 0">
                                    <p class="misc">
                                        <span>Select AI challenge</span>
                                        <select name="selected_challenge" size="1">
                                            <xsl:for-each select="$param/ai_challenges/*">
                                                <xsl:sort select="fullname" order="ascending"/>
                                                <option value="{name}">
                                                    <xsl:value-of select="fullname"/>
                                                </option>
                                            </xsl:for-each>
                                        </select>
                                        <xsl:if test="$param/free_slots &gt; 0">
                                            <button type="submit" name="ai_challenge">Play challenge</button>
                                        </xsl:if>
                                    </p>
                                </xsl:if>

                                <div id="ai-challenges">
                                    <xsl:for-each select="$param/ai_challenges/*">
                                        <xsl:sort select="fullname" order="ascending"/>
                                        <div id="ai-challenge-{name}" class="skin-text">
                                            <h4><xsl:value-of select="fullname"/></h4>
                                            <p>
                                                <img class="avatar" height="60" width="60" src="{$param/avatar_path}{am:fileName(name)}.png" alt="avatar"/>
                                                <xsl:value-of select="description"/>
                                            </p>
                                        </div>
                                    </xsl:for-each>
                                </div>
                            </xsl:if>

                        </xsl:when>
                        <!-- end AI games subsection -->
                    </xsl:choose>

                </div>
                <!-- end hosted games section -->
            </xsl:if>

            <!-- auto refresh -->
            <xsl:if test="$param/auto_refresh &gt; 0">
                <input type="hidden" name="auto_refresh" value="{$param/auto_refresh}"/>
            </xsl:if>
        </div>

    </xsl:template>


    <xsl:template match="section[. = 'Games_details']">
        <xsl:variable name="param" select="$params/game"/>
        <xsl:variable name="isMyTurn" select="$param/game_state = 'in progress' and $param/current = $param/player_name and $param/surrender = ''"/>

        <div class="game top-level">
            <div class="row">
                <xsl:choose>
                    <!-- game in progress -->
                    <xsl:when test="$param/game_state = 'in progress'">
                        <!-- game round -->
                        <p class="information-line info">
                            <xsl:text>Round </xsl:text>
                            <xsl:value-of select="$param/round"/>
                            <xsl:if test="$param/timeout != ''">
                                <xsl:text> (</xsl:text>
                                <xsl:value-of select="$param/timeout"/>
                                <xsl:text>)</xsl:text>
                            </xsl:if>
                        </p>
                    </xsl:when>
                    <!-- game is finished -->
                    <xsl:otherwise>
                        <p class="information-line info">
                            <xsl:choose>
                                <!-- player won -->
                                <xsl:when test="$param/winner = $param/player_name">
                                    <xsl:text>You have won in round </xsl:text>
                                    <xsl:value-of select="$param/round"/>
                                    <xsl:text>. </xsl:text>
                                    <xsl:value-of select="$param/outcome"/>
                                    <xsl:text>.</xsl:text>
                                </xsl:when>
                                <!-- opponent won -->
                                <xsl:when test="$param/winner = $param/opponent_name">
                                    <xsl:value-of select="$param/winner"/>
                                    <xsl:text> has won in round </xsl:text>
                                    <xsl:value-of select="$param/round"/>
                                    <xsl:text>. </xsl:text>
                                    <xsl:value-of select="$param/outcome"/>
                                    <xsl:text>.</xsl:text>
                                </xsl:when>
                                <!-- draw -->
                                <xsl:when test="($param/winner = '') and ($param/outcome_type = 'Draw')">
                                    <xsl:text>Game ended in a draw in round </xsl:text>
                                    <xsl:value-of select="$param/round"/>
                                    <xsl:text>.</xsl:text>
                                </xsl:when>
                                <!-- abort -->
                                <xsl:when test="($param/winner = '') and ($param/outcome_type = 'Abort')">
                                    <xsl:text>Game was aborted in round </xsl:text>
                                    <xsl:value-of select="$param/round"/>
                                    <xsl:text>.</xsl:text>
                                </xsl:when>
                            </xsl:choose>
                        </p>
                    </xsl:otherwise>
                </xsl:choose>
            </div>

            <!-- cheat menu -->
            <xsl:if test="$param/ai_mode = 'yes' and $param/ai_name = '' and $param/player_level &gt;= $param/tutorial_end">
                <div class="row">
                    <div id="game-cheat-menu">
                        <xsl:if test="$param/cheat_menu = 'no'">
                            <xsl:attribute name="style">display: none;</xsl:attribute>
                        </xsl:if>

                        <!-- player selector -->
                        <xsl:variable name="targetPlayers">
                            <value name="mine" text="Player"/>
                            <value name="his" text="Opponent"/>
                        </xsl:variable>
                        <select name="target_player" title="target player">
                            <xsl:for-each select="exsl:node-set($targetPlayers)/*">
                                <option value="{@name}">
                                    <xsl:if test="$param/target_player = @name">
                                        <xsl:attribute name="selected">selected</xsl:attribute>
                                    </xsl:if>
                                    <xsl:value-of select="@text"/>
                                </option>
                            </xsl:for-each>
                        </select>

                        <!-- card selector -->
                        <xsl:if test="count($param/card_names/*) &gt; 0">
                            <select name="card_id" title="card">
                                <xsl:for-each select="$param/card_names/*">
                                    <option value="{id}">
                                        <xsl:if test="$param/card_id = id">
                                            <xsl:attribute name="selected">selected</xsl:attribute>
                                        </xsl:if>
                                        <xsl:value-of select="name"/>
                                    </option>
                                </xsl:for-each>
                            </select>
                        </xsl:if>

                        <!-- card position selector -->
                        <select name="card_pos" title="card position">
                            <xsl:for-each select="str:split(am:numbers(1, 8), ',')">
                                <option value="{.}">
                                    <xsl:if test="$param/card_pos = .">
                                        <xsl:attribute name="selected">selected</xsl:attribute>
                                    </xsl:if>
                                    <xsl:value-of select="."/>
                                </option>
                            </xsl:for-each>
                        </select>

                        <button type="submit" name="put_card">Place card</button>

                        <!-- AI action selector -->
                        <select name="ai_action" title="card action">
                            <option value="play">play</option>
                            <option value="discard">discard</option>
                        </select>

                        <!-- AI card mode selector -->
                        <select name="ai_card_mode" title="card mode">
                            <xsl:for-each select="str:split(am:numbers(0, 8), ',')">
                                <option value="{.}"><xsl:value-of select="."/></option>
                            </xsl:for-each>
                        </select>

                        <button type="submit" name="custom_ai_move">Custom AI move</button>

                        <!-- changes selector -->
                        <xsl:variable name="targetChanges">
                            <value name="Quarry" text="Quarry"/>
                            <value name="Magic" text="Magic"/>
                            <value name="Dungeons" text="Dungeon"/>
                            <value name="Bricks" text="Bricks"/>
                            <value name="Gems" text="Gems"/>
                            <value name="Recruits" text="Recruits"/>
                            <value name="Tower" text="Tower"/>
                            <value name="Wall" text="Wall"/>
                            <value name="Facilities" text="Facilities"/>
                            <value name="Stock" text="Stock"/>
                        </xsl:variable>
                        <select name="target_change" title="target change">
                            <xsl:for-each select="exsl:node-set($targetChanges)/*">
                                <option value="{@name}">
                                    <xsl:if test="$param/target_change = @name">
                                        <xsl:attribute name="selected">selected</xsl:attribute>
                                    </xsl:if>
                                    <xsl:value-of select="@text"/>
                                </option>
                            </xsl:for-each>
                        </select>

                        <!-- change value input -->
                        <input type="text" name="target_value" maxlength="5" size="4" value="{$param/target_value}" title="new value"/>

                        <button type="submit" name="change_attribute">Change</button>
                        <button type="submit" name="change_game_mode">
                            <xsl:choose>
                                <xsl:when test="$param/hidden_cards = 'yes'">Reveal cards</xsl:when>
                                <xsl:otherwise>Hide cards</xsl:otherwise>
                            </xsl:choose>
                        </button>
                    </div>
                </div>
            </xsl:if>


            <div class="game-display">
                <!-- custom background image -->
                <xsl:if test="$param/background_img != 0">
                    <xsl:attribute name="style">
                        <xsl:text>background-image: url('img/backgrounds/bg_</xsl:text>
                        <xsl:value-of select="$param/background_img"/>
                        <xsl:text>.jpg');</xsl:text>
                    </xsl:attribute>
                </xsl:if>

                <!-- begin messages and game buttons -->
                <div class="row">
                    <div class="col-md-5">
                        <!-- game mode flags -->
                        <div class="game-mode-flags">
                            <xsl:copy-of select="am:gameModeFlags(
                                $param/hidden_cards, $param/friendly_play, $param/long_mode, $param/ai_mode, $param/ai_name
                            )"/>
                        </div>

                        <!-- begin game state indicator -->
                        <xsl:choose>
                            <xsl:when test="$param/game_state = 'in progress'">
                                <p class="info-label game-state">
                                    <xsl:choose>
                                        <xsl:when test="$param/surrender = $param/opponent_name">
                                            <span class="player">
                                                <xsl:value-of select="$param/opponent_name"/> wishes to surrender
                                            </span>
                                        </xsl:when>
                                        <xsl:when test="$param/surrender = $param/player_name">
                                            <span class="opponent">You have requested to surrender</span>
                                        </xsl:when>
                                        <xsl:when test="$param/current = $param/player_name">
                                            <span class="player">
                                                <xsl:text>It is your turn</xsl:text>
                                            </span>
                                        </xsl:when>
                                        <xsl:otherwise>
                                            <span class="opponent">
                                                <xsl:text>It is </xsl:text>
                                                <xsl:value-of select="$param/opponent_name"/>
                                                <xsl:text>'s turn</xsl:text>
                                            </span>
                                        </xsl:otherwise>
                                    </xsl:choose>
                                </p>
                            </xsl:when>
                            <xsl:otherwise>
                                <button type="submit" name="leave_game">Leave the game</button>
                            </xsl:otherwise>
                        </xsl:choose>
                        <!-- end game state indicator -->

                        <!-- begin surrender/abort button -->
                        <xsl:if test="$param/game_state = 'in progress'">
                            <xsl:choose>
                                <xsl:when test="$param/opponent_is_dead = 'yes'">
                                    <button type="submit" name="abort_game">Abort game</button>
                                </xsl:when>
                                <xsl:when test="$param/finish_game = 'yes'">
                                    <button type="submit" name="finish_game">Finish game</button>
                                </xsl:when>
                                <xsl:when test="$param/surrender = $param/opponent_name">
                                    <button type="submit" name="accept_surrender">Accept</button>
                                    <button type="submit" name="reject_surrender">Reject</button>
                                </xsl:when>
                                <xsl:when test="$param/surrender = $param/player_name">
                                    <button type="submit" name="cancel_surrender">Cancel surrender</button>
                                </xsl:when>
                                <xsl:otherwise>
                                    <button type="submit" name="initiate_surrender">Surrender</button>
                                </xsl:otherwise>
                            </xsl:choose>
                        </xsl:if>
                        <!-- end surrender/abort button -->
                    </div>
                    <div class="col-md-7">
                        <div class="buttons">
                            <!-- 'refresh' button -->
                            <a class="button button-icon" id="game_refresh" href="{am:makeUrl('Games_details', 'current_game', $param/current_game)}" accesskey="w" title="Refresh">
                                <span class="glyphicon glyphicon-refresh"/>
                            </a>

                            <!-- next game button -->
                            <xsl:if test="$param/next_game_button = 'yes'">
                                <button class="button-icon" type="submit" name="next_game" title="Next game">
                                    <span class="glyphicon glyphicon-log-in"/>
                                </button>
                            </xsl:if>

                            <!-- execute move button -->
                            <xsl:choose>
                                <xsl:when test="$param/ai_mode = 'yes' and not($isMyTurn) and $param/game_state = 'in progress'">
                                    <button type="submit" name="ai_move">Execute AI move</button>
                                </xsl:when>
                                <xsl:when test="$param/ai_mode = 'no' and not($isMyTurn) and $param/game_state = 'in progress' and $param/finish_move = 'yes'">
                                    <button type="submit" name="finish_move">Execute opponent's move</button>
                                </xsl:when>
                                <xsl:otherwise>
                                    <xsl:if test="$param/play_buttons = 'no' and $isMyTurn and $param/cards_playable = 'yes'">
                                        <button type="submit" name="play_card" value="0">Play</button>
                                    </xsl:if>
                                </xsl:otherwise>
                            </xsl:choose>

                            <!-- card preview -->
                            <xsl:if test="$isMyTurn and $param/hidden_cards = 'no' and $param/cards_playable = 'yes'">
                                <button class="button-icon" type="submit" name="preview_card" title="Preview">
                                    <span class="glyphicon glyphicon-eye-open"/>
                                </button>
                            </xsl:if>

                            <!-- game history -->
                            <xsl:if test="$param/game_state = 'in progress'">
                                <a class="button button-icon" href="{am:makeUrl('Replays_history', 'CurrentReplay', $param/current_game)}" title="History">
                                    <span class="glyphicon glyphicon-book"/>
                                </a>
                            </xsl:if>

                            <!-- discard button -->
                            <xsl:if test="$isMyTurn">
                                <button type="submit" name="discard_card">Discard</button>
                            </xsl:if>

                            <!-- current deck -->
                            <a class="button" href="{am:makeUrl('Decks_view', 'current_game', $param/current_game)}">Deck</a>

                            <!-- game note -->
                            <a class="button button-icon" id="game-note" href="{am:makeUrl('Games_note', 'current_game', $param/current_game)}" title="Note">
                                <xsl:if test="$param/has_note = 'yes'">
                                    <xsl:attribute name="class">button button-icon marked_button</xsl:attribute>
                                </xsl:if>
                                <span class="glyphicon glyphicon-edit"/>
                            </a>

                            <xsl:choose>
                                <!-- show cheat menu -->
                                <xsl:when test="$param/ai_mode = 'yes' and $param/ai_name = '' and $param/player_level &gt;= $param/tutorial_end">
                                    <button type="button" name="show_cheats">
                                        <xsl:choose>
                                            <xsl:when test="$param/cheat_menu = 'yes'">Hide</xsl:when>
                                            <xsl:otherwise>Cheat</xsl:otherwise>
                                        </xsl:choose>
                                    </button>
                                </xsl:when>
                                <!-- show chat only in case chat pop-up tool can be used -->
                                <xsl:when test="$param/ai_mode = 'no' and $param/integrated_chat = 'no'">
                                    <button class="button-icon" type="button" name="show_chat" title="Chat">
                                        <!-- highlight button in case there are new messages -->
                                        <xsl:if test="$param/new_chat_messages = 'yes'">
                                            <xsl:attribute name="class">button-icon marked_button</xsl:attribute>
                                        </xsl:if>
                                        <span class="glyphicon glyphicon-comment"/>
                                    </button>
                                </xsl:when>
                            </xsl:choose>
                        </div>
                    </div>
                </div>
                <!-- end messages and game buttons -->

                <!-- begin your cards -->
                <div class="row hand my-hand">
                    <xsl:for-each select="exsl:node-set($param/p1_hand)/*">
                        <div>
                            <!-- display card flags, if set -->
                            <xsl:choose>
                                <xsl:when test="$param/hidden_cards = 'yes' and revealed = 'yes' and $param/card_mini_flag = 'no'">
                                    <div class="flag-space">
                                        <xsl:if test="new_card = 'yes'">
                                            <span class="new-card">new</span>
                                        </xsl:if>
                                        <img src="img/game/revealed.png" width="20" height="14" alt="revealed" title="Revealed"/>
                                    </div>
                                </xsl:when>
                                <xsl:when test="new_card = 'yes' and $param/card_mini_flag = 'no'">
                                    <p class="flag">new card</p>
                                </xsl:when>
                            </xsl:choose>

                            <!-- display card -->
                            <div>
                                <xsl:choose>
                                    <!-- suggested and unplayable card -->
                                    <xsl:when test="$isMyTurn and suggested = 'yes' and playable = 'no'">
                                        <xsl:attribute name="class">unplayable suggested</xsl:attribute>
                                    </xsl:when>
                                    <!-- unplayable card -->
                                    <xsl:when test="$isMyTurn and playable = 'no'">
                                        <xsl:attribute name="class">unplayable</xsl:attribute>
                                    </xsl:when>
                                    <!-- suggested card -->
                                    <xsl:when test="$isMyTurn and suggested = 'yes'">
                                        <xsl:attribute name="class">suggested</xsl:attribute>
                                    </xsl:when>
                                </xsl:choose>
                                <xsl:variable name="revealed" select="$param/card_mini_flag = 'yes' and $param/hidden_cards = 'yes' and revealed = 'yes'"/>
                                <xsl:variable name="newCard" select="$param/card_mini_flag = 'yes' and new_card = 'yes'"/>
                                <xsl:copy-of select="am:cardString(
                                    card_data, $param/card_old_look, $param/card_insignias, $param/p1_card_foils, $newCard, $revealed, $param/keywords_count
                                )"/>
                            </div>

                            <!-- select button and card modes (buttons are locked when surrender request is active) -->
                            <xsl:if test="$isMyTurn">
                                <xsl:if test="$param/play_buttons = 'yes' and playable = 'yes'">
                                    <button type="submit" name="play_card" value="{position()}">
                                        <!-- suggested card -->
                                        <xsl:if test="suggested = 'yes'">
                                            <xsl:attribute name="class">suggested</xsl:attribute>
                                        </xsl:if>
                                        <xsl:text>Play</xsl:text>
                                    </button>
                                </xsl:if>
                                <input type="radio" name="selected_card" value="{position()}"/>
                                <xsl:if test="playable = 'yes' and modes &gt; 0">
                                    <select name="card_mode[{position()}]" class="card-modes" size="1">
                                        <xsl:variable name="suggestedMode">
                                            <xsl:choose>
                                                <xsl:when test="suggested = 'yes'">
                                                    <xsl:value-of select="suggested_mode"/>
                                                </xsl:when>
                                                <xsl:otherwise>0</xsl:otherwise>
                                            </xsl:choose>
                                        </xsl:variable>
                                        <xsl:for-each select="str:split(am:numbers(1, modes), ',')">
                                            <option value="{.}">
                                                <!-- pre-select suggested mode -->
                                                <xsl:if test="$suggestedMode &gt; 0 and $suggestedMode = .">
                                                    <xsl:attribute name="selected">selected</xsl:attribute>
                                                </xsl:if>
                                                <xsl:value-of select="."/>
                                            </option>
                                        </xsl:for-each>
                                    </select>
                                </xsl:if>
                            </xsl:if>
                        </div>
                    </xsl:for-each>
                </div>
                <!-- end your cards -->

                <div class="row">
                    <div class="col-md-3 empire-info">
                        <!-- my empire info -->
                        <div class="stats">
                            <xsl:copy-of select="am:stockInfo($param/p1_stock, $param/p1_changes, $param/res_victory)"/>
                            <xsl:copy-of select="am:castleInfo(
                                $param/p1_tower, $param/p1_changes/tower, $param/max_tower,
                                $param/p1_wall, $param/p1_changes/wall, $param/max_wall
                            )"/>
                        </div>

                        <!-- my tower and wall -->
                        <div class="castle-display">
                            <xsl:copy-of select="am:castleDisplay(
                                'left', $param/p1_tower, $param/p1_wall, $param/max_tower, $param/max_wall
                            )"/>
                        </div>

                        <div class="player-info">
                            <!-- player name -->
                            <p class="token-counter player-label">
                                <xsl:value-of select="$param/player_name"/>
                                <img class="icon" width="18" height="12" src="img/flags/{$param/p1_country}.gif" alt="country flag" title="{$param/p1_country}"/>
                            </p>

                            <img class="avatar" height="60" width="60" src="{$param/avatar_path}{$param/p1_avatar}" alt="avatar"/>

                            <!-- my tokens -->
                            <div class="token-list">
                                <xsl:copy-of select="am:tokens($param/p1_tokens, $param/card_insignias)"/>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 empire-info">
                        <!-- my discarded cards -->
                        <xsl:copy-of select="am:discardedCards(
                            $param/p1_discarded_cards_0, $param/p1_discarded_cards_1,
                            $param/card_old_look, $param/card_insignias, $param/p1_card_foils
                        )"/>

                        <!-- my last played card(s) -->
                        <div class="card-list">
                            <xsl:copy-of select="am:cardHistory(
                                $param/p1_last_card, $param/card_old_look, $param/card_insignias, $param/p1_card_foils
                            )"/>
                        </div>

                        <!-- his last played card(s) -->
                        <div class="card-list">
                            <xsl:copy-of select="am:cardHistory(
                                $param/p2_last_card, $param/card_old_look, $param/card_insignias, $param/p2_card_foils
                            )"/>
                        </div>

                        <!-- his discarded cards -->
                        <xsl:copy-of select="am:discardedCards(
                            $param/p2_discarded_cards_1, $param/p2_discarded_cards_0,
                            $param/card_old_look, $param/card_insignias, $param/p2_card_foils
                        )"/>
                    </div>
                    <div class="col-md-3 empire-info">
                        <!-- his tower and wall -->
                        <div class="castle-display">
                            <xsl:copy-of select="am:castleDisplay(
                                'right', $param/p2_tower, $param/p2_wall, $param/max_tower, $param/max_wall
                            )"/>
                        </div>

                        <!-- his empire info -->
                        <div class="stats align-right">
                            <xsl:copy-of select="am:stockInfo($param/p2_stock, $param/p2_changes, $param/res_victory)"/>
                            <xsl:copy-of select="am:castleInfo(
                                $param/p2_tower, $param/p2_changes/tower, $param/max_tower,
                                $param/p2_wall, $param/p2_changes/wall, $param/max_wall
                            )"/>
                        </div>

                        <div class="player-info">
                            <!-- opponent's name -->
                            <p class="token-counter player-label opponent-label">
                                <img class="icon" width="18" height="12" src="img/flags/{$param/p2_country}.gif" alt="country flag" title="{$param/p2_country}"/>

                                <xsl:copy-of select="am:playerName($param/opponent_name, $param/ai_name, $param/system_name)"/>

                                <xsl:if test="$param/opponent_is_online = 'yes'">
                                    <span class="icon-player-activity online" title="online"/>
                                </xsl:if>
                            </p>

                            <xsl:variable name="avatarName" select="am:avatarFileName(
                                $param/p2_avatar, $param/opponent_name, $param/ai_name, $param/system_name
                            )"/>

                            <img class="avatar" height="60" width="60" src="{$param/avatar_path}{$avatarName}" alt="avatar"/>

                            <!-- his tokens -->
                            <div class="token-list">
                                <xsl:copy-of select="am:tokens($param/p2_tokens, $param/card_insignias)"/>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- his cards -->
                <div class="row hand">
                    <xsl:copy-of select="am:opponentHand(
                        $param/p2_hand, $param/hidden_cards, $param/card_mini_flag,
                        $param/card_old_look, $param/card_insignias, $param/p2_card_foils, $param/game_state
                    )"/>
                </div>
            </div>

            <!-- begin chat board -->

            <!-- chat board is not available in AI mode -->
            <xsl:if test="$param/ai_mode = 'no'">
                <!-- disabled in case integrated chat setting is disabled -->
                <xsl:if test="$param/integrated_chat = 'yes'">
                    <div class="row chat-section">
                        <!-- main chat board content -->
                        <div class="col-sm-12">
                            <div>
                                <!-- message list -->
                                <xsl:if test="count($param/message_list/*) &gt; 0">
                                    <div class="chat-box">
                                        <!-- scrolls chat box to bottom if reverse chat order setting is active -->
                                        <xsl:if test="$param/reverse_chat = 'yes'">
                                            <xsl:attribute name="class">chat-box scroll_max</xsl:attribute>
                                        </xsl:if>
                                        <xsl:for-each select="$param/message_list/*">
                                            <p>
                                                <img class="avatar" height="20" width="20" alt="avatar">
                                                    <xsl:choose>
                                                        <xsl:when test="author = $param/player_name">
                                                            <xsl:attribute name="src">
                                                                <xsl:value-of select="$param/avatar_path"/>
                                                                <xsl:value-of select="$param/p1_avatar"/>
                                                            </xsl:attribute>
                                                        </xsl:when>
                                                        <xsl:when test="author = $param/opponent_name">
                                                            <xsl:attribute name="src">
                                                                <xsl:value-of select="$param/avatar_path"/>
                                                                <xsl:value-of select="$param/p2_avatar"/>
                                                            </xsl:attribute>
                                                        </xsl:when>
                                                    </xsl:choose>
                                                </img>
                                                <span>
                                                    <xsl:choose>
                                                        <xsl:when test="author = $param/player_name">
                                                            <xsl:attribute name="class">chat-box-player</xsl:attribute>
                                                        </xsl:when>
                                                        <xsl:when test="author = $param/opponent_name">
                                                            <xsl:attribute name="class">chat-box-opponent</xsl:attribute>
                                                        </xsl:when>
                                                        <xsl:otherwise>
                                                            <xsl:attribute name="class">chat-box-system</xsl:attribute>
                                                        </xsl:otherwise>
                                                    </xsl:choose>
                                                    <xsl:value-of select="author"/>
                                                    <xsl:text> on </xsl:text>
                                                    <xsl:copy-of select="am:dateTime(created_at, $param/timezone)"/>
                                                </span>
                                            </p>
                                            <div>
                                                <xsl:value-of select="am:bbCodeParseExtended(message)" disable-output-escaping="yes"/>
                                            </div>
                                        </xsl:for-each>
                                    </div>
                                </xsl:if>

                                <!-- chat board inputs -->
                                <xsl:if test="$param/chat = 'yes'">
                                    <div id="chat-inputs">
                                        <textarea name="chat_message" rows="3" cols="50" tabindex="1" accesskey="a"/>
                                        <button class="button-icon" type="submit" name="send_message" tabindex="2" accesskey="s" title="Send">
                                            <span class="glyphicon glyphicon-send"/>
                                        </button>
                                    </div>
                                </xsl:if>
                            </div>
                        </div>
                    </div>
                </xsl:if>

                <!-- chat modal dialog -->
                <xsl:if test="$param/integrated_chat = 'no'">
                    <!-- enabled in case integrated chat setting is disabled -->
                    <div id="chat-dialog">
                        <!-- message list -->
                        <div>
                            <xsl:if test="count($param/message_list/*) &gt; 0">
                                <!-- scrolls chat box to bottom if reverse chat order setting is active -->
                                <xsl:if test="$param/reverse_chat = 'yes'">
                                    <xsl:attribute name="class">scroll_max</xsl:attribute>
                                </xsl:if>
                                <xsl:for-each select="$param/message_list/*">
                                    <p>
                                        <img class="avatar" height="20" width="20" alt="avatar">
                                            <xsl:choose>
                                                <xsl:when test="author = $param/player_name">
                                                    <xsl:attribute name="src">
                                                        <xsl:value-of select="$param/avatar_path"/>
                                                        <xsl:value-of select="$param/p1_avatar"/>
                                                    </xsl:attribute>
                                                </xsl:when>
                                                <xsl:when test="author = $param/opponent_name">
                                                    <xsl:attribute name="src">
                                                        <xsl:value-of select="$param/avatar_path"/>
                                                        <xsl:value-of select="$param/p2_avatar"/>
                                                    </xsl:attribute>
                                                </xsl:when>
                                            </xsl:choose>
                                        </img>
                                        <span>
                                            <xsl:choose>
                                                <xsl:when test="author = $param/player_name">
                                                    <xsl:attribute name="class">chat-box-player</xsl:attribute>
                                                </xsl:when>
                                                <!-- highlight new chat messages (never highlight own chat messages) -->
                                                <xsl:when test="am:dateDiff(created_at, $param/chat_notification) &lt; 0">
                                                    <xsl:attribute name="class">new_message</xsl:attribute>
                                                </xsl:when>
                                                <xsl:when test="author = $param/opponent_name">
                                                    <xsl:attribute name="class">chat-box-opponent</xsl:attribute>
                                                </xsl:when>
                                                <xsl:otherwise>
                                                    <xsl:attribute name="class">chat-box-system</xsl:attribute>
                                                </xsl:otherwise>
                                            </xsl:choose>
                                            <xsl:value-of select="author"/>
                                            <xsl:text> on </xsl:text>
                                            <xsl:copy-of select="am:dateTime(created_at, $param/timezone)"/>
                                        </span>
                                    </p>
                                    <div class="chat-message">
                                        <xsl:value-of select="am:bbCodeParseExtended(message)" disable-output-escaping="yes"/>
                                    </div>
                                </xsl:for-each>
                            </xsl:if>
                        </div>

                        <textarea name="chat_area" rows="3" cols="50"/>
                    </div>
                </xsl:if>

            </xsl:if>
            <!-- end chat board -->

            <div>
                <!-- remember the current location across pages -->
                <input type="hidden" name="current_game" value="{$param/current_game}"/>

                <!-- remember cheat menu visibility -->
                <input type="hidden" name="cheat_menu" value="{$param/cheat_menu}"/>

                <input type="hidden" name="current_round" value="{$param/round}"/>

                <!-- automatic actions -->
                <xsl:choose>
                    <!-- auto ai move -->
                    <xsl:when test="$param/auto_ai &gt; 0">
                        <input type="hidden" name="auto_ai" value="{$param/auto_ai}"/>
                    </xsl:when>
                    <!-- auto refresh -->
                    <xsl:when test="$param/auto_refresh &gt; 0">
                        <input type="hidden" name="auto_refresh" value="{$param/auto_refresh}"/>
                    </xsl:when>
                </xsl:choose>
            </div>

            <!-- game note modal dialog -->
            <div id="game-note-dialog">
                <textarea name="content" rows="10" cols="50">
                    <xsl:value-of select="$param/game_note"/>
                </textarea>
            </div>
        </div>

    </xsl:template>


    <xsl:template match="section[. = 'Games_preview']">
        <xsl:variable name="param" select="$params/game"/>
        <xsl:variable name="isMyTurn" select="$param/game_state = 'in progress' and $param/current = $param/player_name and $param/surrender = ''"/>

        <div class="game top-level">
            <div class="game-display">
                <!-- custom background image -->
                <xsl:if test="$param/background_img != 0">
                    <xsl:attribute name="style">
                        <xsl:text>background-image: url('img/backgrounds/bg_</xsl:text>
                        <xsl:value-of select="$param/background_img"/>
                        <xsl:text>.jpg');</xsl:text>
                    </xsl:attribute>
                </xsl:if>

                <!-- messages and game buttons -->
                <div class="row">
                    <div>
                        <!-- game mode flags -->
                        <div class="game-mode-flags">
                            <xsl:copy-of select="am:gameModeFlags(
                                $param/hidden_cards, $param/friendly_play, $param/long_mode, $param/ai_mode, $param/ai_name
                            )"/>
                        </div>

                        <!-- close preview button -->
                        <a class="button" href="{am:makeUrl('Games_details', 'current_game', $param/current_game)}">
                            <xsl:text>Close preview</xsl:text>
                        </a>
                    </div>
                </div>

                <!-- begin your cards -->
                <div class="row hand">
                    <xsl:for-each select="exsl:node-set($param/p1_hand)/*">
                        <div>
                            <!--  display card flags, if set -->
                            <xsl:choose>
                                <xsl:when test="$param/hidden_cards = 'yes' and revealed = 'yes' and $param/card_mini_flag = 'no'">
                                    <div class="flag-space">
                                        <xsl:if test="new_card = 'yes'">
                                            <span class="new-card">new</span>
                                        </xsl:if>
                                        <img src="img/game/revealed.png" width="20" height="14" alt="revealed" title="Revealed"/>
                                    </div>
                                </xsl:when>
                                <xsl:when test="new_card = 'yes' and $param/card_mini_flag = 'no'">
                                    <p class="flag">new card</p>
                                </xsl:when>
                            </xsl:choose>

                            <!-- display card -->
                            <div>
                                <xsl:if test="$isMyTurn and playable = 'no'">
                                    <xsl:attribute name="class">unplayable</xsl:attribute>
                                </xsl:if>
                                <xsl:variable name="revealed" select="$param/card_mini_flag = 'yes' and $param/hidden_cards = 'yes' and revealed = 'yes'"/>
                                <xsl:variable name="newCard" select="$param/card_mini_flag = 'yes' and new_card = 'yes'"/>
                                <xsl:copy-of select="am:cardString(
                                    card_data, $param/card_old_look, $param/card_insignias, $param/p1_card_foils, $newCard, $revealed, $param/keywords_count
                                )"/>
                            </div>
                        </div>
                    </xsl:for-each>
                </div>
                <!-- end your cards -->

                <div class="row">
                    <div class="col-md-3 empire-info">
                        <!-- my empire info -->
                        <div class="stats">
                            <xsl:copy-of select="am:stockInfo($param/p1_stock, $param/p1_changes, $param/res_victory)"/>
                            <xsl:copy-of select="am:castleInfo(
                                $param/p1_tower, $param/p1_changes/tower, $param/max_tower,
                                $param/p1_wall, $param/p1_changes/wall, $param/max_wall
                            )"/>
                        </div>

                        <!-- my tower and wall -->
                        <div class="castle-display">
                            <xsl:copy-of select="am:castleDisplay(
                                'left', $param/p1_tower, $param/p1_wall, $param/max_tower, $param/max_wall
                            )"/>
                        </div>

                        <div class="player-info">
                            <!-- player name -->
                            <p class="token-counter player-label">
                                <xsl:value-of select="$param/player_name"/>
                                <img class="icon" width="18" height="12" src="img/flags/{$param/p1_country}.gif" alt="country flag" title="{$param/p1_country}"/>
                            </p>

                            <img class="avatar" height="60" width="60" src="{$param/avatar_path}{$param/p1_avatar}" alt="avatar"/>

                            <!-- my tokens -->
                            <div class="token-list">
                                <xsl:copy-of select="am:tokens($param/p1_tokens, $param/card_insignias)"/>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 empire-info">
                        <!-- my discarded cards -->
                        <xsl:copy-of select="am:discardedCards(
                            $param/p1_discarded_cards_0, $param/p1_discarded_cards_1,
                            $param/card_old_look, $param/card_insignias, $param/p1_card_foils
                        )"/>

                        <!-- my last played card(s) -->
                        <div class="card-list">
                            <xsl:copy-of select="am:cardHistory(
                                $param/p1_last_card, $param/card_old_look, $param/card_insignias, $param/p1_card_foils
                            )"/>
                        </div>

                        <!-- his last played card(s) -->
                        <div class="card-list">
                            <xsl:copy-of select="am:cardHistory(
                                $param/p2_last_card, $param/card_old_look, $param/card_insignias, $param/p2_card_foils
                            )"/>
                        </div>

                        <!-- his discarded cards -->
                        <xsl:copy-of select="am:discardedCards(
                            $param/p2_discarded_cards_1, $param/p2_discarded_cards_0,
                            $param/card_old_look, $param/card_insignias, $param/p2_card_foils
                        )"/>
                    </div>
                    <div class="col-md-3 empire-info">
                        <!-- his tower and wall -->
                        <div class="castle-display">
                            <xsl:copy-of select="am:castleDisplay(
                                'right', $param/p2_tower, $param/p2_wall, $param/max_tower, $param/max_wall
                            )"/>
                        </div>

                        <!-- his empire info -->
                        <div class="stats align-right">
                            <xsl:copy-of select="am:stockInfo($param/p2_stock, $param/p2_changes, $param/res_victory)"/>
                            <xsl:copy-of select="am:castleInfo(
                                $param/p2_tower, $param/p2_changes/tower, $param/max_tower,
                                $param/p2_wall, $param/p2_changes/wall, $param/max_wall
                            )"/>
                        </div>

                        <div class="player-info">
                            <!-- opponent's name -->
                            <p class="token-counter player-label opponent-label">
                                <img class="icon" width="18" height="12" src="img/flags/{$param/p2_country}.gif" alt="country flag" title="{$param/p2_country}"/>

                                <xsl:copy-of select="am:playerName($param/opponent_name, $param/ai_name, $param/system_name)"/>

                                <xsl:if test="$param/opponent_is_online = 'yes'">
                                    <span class="icon-player-activity online" title="online"/>
                                </xsl:if>
                            </p>

                            <xsl:variable name="avatarName" select="am:avatarFileName(
                                $param/p2_avatar, $param/opponent_name, $param/ai_name, $param/system_name
                            )"/>

                            <img class="avatar" height="60" width="60" src="{$param/avatar_path}{$avatarName}" alt="avatar"/>

                            <!-- his tokens -->
                            <div class="token-list">
                                <xsl:copy-of select="am:tokens($param/p2_tokens, $param/card_insignias)"/>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- his cards -->
                <div class="row hand">
                    <xsl:for-each select="$param/p2_hand/*">
                        <div>
                            <!-- display hidden card -->
                            <div class="hidden-card" />
                        </div>
                    </xsl:for-each>
                </div>
            </div>

            <!-- remember the current location across pages -->
            <div>
                <input type="hidden" name="current_game" value="{$param/current_game}"/>
            </div>
        </div>

    </xsl:template>


    <xsl:template match="section[. = 'Games_note']">
        <xsl:variable name="param" select="$params/game_note"/>

        <!-- remember the current location across pages -->
        <div>
            <input type="hidden" name="current_game" value="{$param/current_game}"/>
        </div>

        <div class="game-note">

            <h3>Game note</h3>

            <div class="skin-text">
                <a class="button button-icon" href="{am:makeUrl('Games_details', 'current_game', $param/current_game)}">
                    <span class="glyphicon glyphicon-arrow-left"/>
                </a>
                <button type="submit" name="save_game_note_return">Save &amp; return</button>
                <button type="submit" name="save_game_note">Save</button>
                <button type="submit" name="clear_game_note">Clear</button>
                <button type="submit" name="clear_game_note_return">Clear &amp; return</button>
                <hr/>

                <textarea name="content" rows="10" cols="50">
                    <xsl:value-of select="$param/text"/>
                </textarea>
            </div>

        </div>

    </xsl:template>


</xsl:stylesheet>
