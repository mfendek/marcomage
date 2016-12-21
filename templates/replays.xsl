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


    <xsl:template match="section[. = 'Replays']">
        <xsl:variable name="param" select="$params/replays"/>

        <div id="games">
            <!-- begin filters and navigation -->
            <div class="filters">
                <!-- player filter -->
                <input type="text" name="player_filter" maxlength="20" size="20" value="{$param/player_filter}" title="search phrase for player name"/>

                <!-- victory type filter -->
                <xsl:variable name="victoryTypes">
                    <value name="No victory filter" value="none"/>
                    <value name="Tower building" value="Construction"/>
                    <value name="Tower destruction" value="Destruction"/>
                    <value name="Resource accumulation" value="Resource"/>
                    <value name="Timeout" value="Timeout"/>
                    <value name="Draw" value="Draw"/>
                    <value name="Surrender" value="Surrender"/>
                    <value name="Aborted" value="Abort"/>
                    <value name="Abandon" value="Abandon"/>
                </xsl:variable>
                <xsl:copy-of select="am:htmlSelectBox('victory_filter', $param/victory_filter, $victoryTypes, '')"/>

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

                <!-- long mode filter -->
                <img class="icon" width="20" height="14" src="img/long_mode.png" alt="Long mode" title="Long mode"/>
                <xsl:copy-of select="am:htmlSelectBox('long_mode', $param/long_mode, $modeOptions, '')"/>

                <!-- ai mode filter -->
                <img class="icon" width="20" height="14" src="img/ai_mode.png" alt="AI mode" title="AI mode"/>
                <xsl:copy-of select="am:htmlSelectBox('ai_mode', $param/ai_mode, $modeOptions, '')"/>

                <!-- ai challenge filter -->
                <img class="icon" width="20" height="14" src="img/ai_challenge.png" alt="AI challenge" title="AI challenge"/>
                <xsl:copy-of select="am:htmlSelectBox('challenge_filter', $param/challenge_filter, $modeOptions, $param/ai_challenges)"/>

                <button class="button-icon" type="submit" name="replays_apply_filters" title="Apply filters">
                    <span class="glyphicon glyphicon-filter"/>
                </button>
                <button class="button-icon" type="submit" name="show_my_replays" title="My replays">
                    <span class="glyphicon glyphicon-user"/>
                </button>
            </div>
            <div class="filters">
                <!-- navigation -->
                <xsl:copy-of select="am:upperNavigation($param/page_count, $param/current_page, 'replays')"/>
            </div>
            <!-- end filters and navigation -->

            <xsl:choose>
                <xsl:when test="count($param/list/*) &gt; 0">
                    <div class="responsive-table table-sm skin-text">
                        <!-- table header -->
                        <div class="row">
                            <xsl:variable name="columns">
                                <column name="winner" text="Winner" sortable="yes" size="2"/>
                                <column name="loser" text="Defeated" sortable="no" size="2"/>
                                <column name="outcome_type" text="Outcome" sortable="no" size="1"/>
                                <column name="rounds" text="Rounds" sortable="yes" size="2"/>
                                <column name="started_at" text="Started" sortable="yes" size="1"/>
                                <column name="finished_at" text="Finished" sortable="yes" size="1"/>
                                <column name="game_modes" text="Modes" sortable="no" size="2"/>
                                <column name="views" text="Views" sortable="no" size="1"/>
                            </xsl:variable>

                            <xsl:for-each select="exsl:node-set($columns)/*">
                                <div class="col-sm-{@size}">
                                    <p>
                                        <xsl:if test="@sortable = 'yes'">
                                            <xsl:attribute name="class">sortable</xsl:attribute>
                                        </xsl:if>

                                        <span><xsl:value-of select="@text"/></span>
                                        <xsl:if test="@sortable = 'yes'">
                                            <button class="button-icon" type="submit" value="{@name}">
                                                <xsl:if test="$param/cond = @name">
                                                    <xsl:attribute name="class">button-icon pushed</xsl:attribute>
                                                </xsl:if>
                                                <xsl:choose>
                                                    <xsl:when test="$param/cond = @name and $param/order = 'DESC'">
                                                        <xsl:attribute name="name">replays_order_asc</xsl:attribute>
                                                        <span class="glyphicon glyphicon-sort-by-attributes-alt"/>
                                                    </xsl:when>
                                                    <xsl:otherwise>
                                                        <xsl:attribute name="name">replays_order_desc</xsl:attribute>
                                                        <span class="glyphicon glyphicon-sort-by-attributes"/>
                                                    </xsl:otherwise>
                                                </xsl:choose>
                                            </button>
                                        </xsl:if>
                                    </p>
                                </div>
                            </xsl:for-each>
                        </div>

                        <!-- table body -->
                        <xsl:for-each select="$param/list/*">
                            <!-- AI challenge name transformation -->
                            <xsl:variable name="player2">
                                <xsl:choose>
                                    <xsl:when test="ai_name != ''">
                                        <xsl:value-of select="ai_name"/>
                                    </xsl:when>
                                    <xsl:otherwise>
                                        <xsl:value-of select="player2"/>
                                    </xsl:otherwise>
                                </xsl:choose>
                            </xsl:variable>
                            <div class="row table-row details">
                                <div class="col-sm-2">
                                    <p>
                                        <xsl:variable name="winner">
                                            <xsl:choose>
                                                <xsl:when test="winner != '' and winner = player1">
                                                    <xsl:value-of select="player1"/>
                                                </xsl:when>
                                                <xsl:when test="winner != '' and winner = player2">
                                                    <xsl:value-of select="$player2"/>
                                                </xsl:when>
                                                <xsl:otherwise>
                                                    <xsl:value-of select="player1"/> / <xsl:value-of select="$player2"/>
                                                </xsl:otherwise>
                                            </xsl:choose>
                                        </xsl:variable>

                                        <xsl:choose>
                                            <xsl:when test="is_deleted = 'no'">
                                                <a class="profile" href="{am:makeUrl('Replays_details', 'CurrentReplay', game_id, 'PlayerView', 1, 'Turn', 1)}">
                                                    <xsl:value-of select="$winner"/>
                                                </a>
                                            </xsl:when>
                                            <xsl:otherwise><xsl:value-of select="$winner"/></xsl:otherwise>
                                        </xsl:choose>
                                    </p>
                                </div>
                                <div class="col-sm-2">
                                    <p>
                                        <xsl:choose>
                                            <xsl:when test="winner = player1">
                                                <xsl:value-of select="$player2"/>
                                            </xsl:when>
                                            <xsl:when test="winner = player2">
                                                <xsl:value-of select="player1"/>
                                            </xsl:when>
                                        </xsl:choose>
                                    </p>
                                </div>
                                <div class="col-sm-1"><p><xsl:value-of select="outcome_type"/></p></div>
                                <div class="col-sm-2"><p><xsl:value-of select="rounds"/></p></div>
                                <div class="col-sm-1"><p><xsl:value-of select="am:dateTime(started_at, $param/timezone)"/></p></div>
                                <div class="col-sm-1"><p><xsl:value-of select="am:dateTime(finished_at, $param/timezone)"/></p></div>
                                <div class="col-sm-2">
                                    <p>
                                        <xsl:copy-of select="am:gameModeFlags(
                                            am:hasGameMode(GameModes, 'HiddenCards'),
                                            am:hasGameMode(GameModes, 'FriendlyPlay'),
                                            am:hasGameMode(GameModes, 'LongMode'),
                                            am:hasGameMode(GameModes, 'AIMode'),
                                            ai_name
                                        )"/>
                                    </p>
                                </div>
                                <div class="col-sm-1"><p><xsl:value-of select="views"/></p></div>
                            </div>
                        </xsl:for-each>
                    </div>

                    <div class="filters">
                        <!-- lower navigation -->
                        <xsl:copy-of select="am:lowerNavigation($param/page_count, $param/current_page, 'replays', 'Replays')"/>
                    </div>
                </xsl:when>
                <xsl:otherwise>
                    <p class="information-line warning">There are no game replays.</p>
                </xsl:otherwise>
            </xsl:choose>

            <input type="hidden" name="replays_current_page" value="{$param/current_page}"/>
            <input type="hidden" name="replays_current_order" value="{$param/order}"/>
            <input type="hidden" name="replays_current_condition" value="{$param/cond}"/>
        </div>

    </xsl:template>


    <xsl:template match="section[. = 'Replays_details']">
        <xsl:variable name="param" select="$params/replay"/>
        <xsl:variable name="turns" select="$param/turns"/>
        <xsl:variable name="current" select="$param/current_turn"/>

        <div class="game top-level">
            <div class="row">
                <div class="col-sm-7">
                    <!-- begin navigation -->
                    <p class="information-line">
                        <!-- previous -->
                        <xsl:choose>
                            <xsl:when test="$current &gt; 1">
                                <a class="button button-icon" href="{am:makeUrl('Replays_details', 'CurrentReplay', $param/current_replay, 'PlayerView', $param/player_view, 'Turn', am:max($current - 1, 1))}">
                                    <span class="glyphicon glyphicon-chevron-left"/>
                                </a>
                            </xsl:when>
                            <xsl:otherwise>
                                <span class="disabled">
                                    <span class="glyphicon glyphicon-chevron-left"/>
                                </span>
                            </xsl:otherwise>
                        </xsl:choose>

                        <!-- first -->
                        <xsl:choose>
                            <xsl:when test="$current &gt; 1">
                                <a class="button button-icon" href="{am:makeUrl('Replays_details', 'CurrentReplay', $param/current_replay, 'PlayerView', $param/player_view, 'Turn', 1)}">
                                    <span class="glyphicon glyphicon-step-backward"/>
                                </a>
                            </xsl:when>
                            <xsl:otherwise>
                                <span class="disabled">
                                    <span class="glyphicon glyphicon-step-backward"/>
                                </span>
                            </xsl:otherwise>
                        </xsl:choose>

                        <!-- page selection -->
                        <xsl:for-each select="str:split(am:numbers(am:max($current - 5, 1), am:min($current + 5, $turns)), ',')">
                            <xsl:choose>
                                <xsl:when test="$current != .">
                                    <a class="button button-icon" href="{am:makeUrl('Replays_details', 'CurrentReplay', $param/current_replay, 'PlayerView', $param/player_view, 'Turn', text())}">
                                        <xsl:value-of select="text()"/>
                                    </a>
                                </xsl:when>
                                <xsl:otherwise>
                                    <span class="disabled">
                                        <xsl:value-of select="text()"/>
                                    </span>
                                </xsl:otherwise>
                            </xsl:choose>
                        </xsl:for-each>

                        <!-- last -->
                        <xsl:choose>
                            <xsl:when test="$current &lt; $turns">
                                <a class="button button-icon" href="{am:makeUrl('Replays_details', 'CurrentReplay', $param/current_replay, 'PlayerView', $param/player_view, 'Turn', $turns)}">
                                    <span class="glyphicon glyphicon-step-forward"/>
                                </a>
                            </xsl:when>
                            <xsl:otherwise>
                                <span class="disabled">
                                    <span class="glyphicon glyphicon-step-forward"/>
                                </span>
                            </xsl:otherwise>
                        </xsl:choose>

                        <!-- next -->
                        <xsl:choose>
                            <xsl:when test="$current &lt; $turns">
                                <a id="next" class="button button-icon" href="{am:makeUrl('Replays_details', 'CurrentReplay', $param/current_replay, 'PlayerView', $param/player_view, 'Turn', am:min($current + 1, $turns))}">
                                    <span class="glyphicon glyphicon-chevron-right"/>
                                </a>
                            </xsl:when>
                            <xsl:otherwise>
                                <span class="disabled">
                                    <span class="glyphicon glyphicon-chevron-right"/>
                                </span>
                            </xsl:otherwise>
                        </xsl:choose>
                    </p>
                    <!-- end navigation -->
                </div>
                <div class="col-sm-5">
                    <p class="information-line">
                        <!-- player switcher -->
                        <xsl:variable name="view">
                            <xsl:choose>
                                <xsl:when test="$param/player_view = 1">2</xsl:when>
                                <xsl:otherwise>1</xsl:otherwise>
                            </xsl:choose>
                        </xsl:variable>
                        <a class="button button-icon" href="{am:makeUrl('Replays_details', 'CurrentReplay', $param/current_replay, 'PlayerView', $view, 'Turn', $current)}" title="Switch players">
                            <span class="glyphicon glyphicon-transfer"/>
                        </a>

                        <!-- discussion -->
                        <xsl:choose>
                            <xsl:when test="$param/discussion = 0 and $param/create_thread = 'yes'">
                                <button class="button-icon" type="submit" name="find_replay_thread" value="{$param/current_replay}" title="Start discussion">
                                    <span class="glyphicon glyphicon-comment"/>
                                </button>
                            </xsl:when>
                            <xsl:when test="$param/discussion &gt; 0">
                                <a class="button button-icon" href="{am:makeUrl('Forum_thread', 'current_thread', $param/discussion, 'CurrentPage', 0)}" title="View discussion">
                                    <span class="glyphicon glyphicon-comment"/>
                                </a>
                            </xsl:when>
                        </xsl:choose>

                        <!-- slide show button -->
                        <xsl:if test="$current &lt; $turns">
                            <button class="button-icon" type="button" name="slideshow-play" title="Play">
                                <span class="glyphicon glyphicon-play"/>
                            </button>
                            <button class="button-icon" type="button" name="slideshow-pause" title="Pause">
                                <span class="glyphicon glyphicon-pause"/>
                            </button>
                        </xsl:if>
                    </p>
                </div>
            </div>
            <div class="row">
                <!-- basic game information -->
                <xsl:choose>
                    <xsl:when test="$current = $turns">
                        <p class="information-line info">
                            <xsl:choose>
                                <!-- player won -->
                                <xsl:when test="$param/winner != ''">
                                    <xsl:value-of select="$param/winner"/>
                                    <xsl:text> has won in round</xsl:text>
                                    <xsl:value-of select="$param/round"/>
                                    <xsl:text> (turn </xsl:text>
                                    <xsl:value-of select="$current"/>
                                    <xsl:text>).</xsl:text>
                                    <xsl:value-of select="$param/outcome"/>
                                    <xsl:text>.</xsl:text>
                                </xsl:when>
                                <!-- opponent won -->
                                <xsl:when test="($param/winner = '') and ($param/outcome_type = 'Draw')">
                                    <xsl:text>Game ended in a draw in round </xsl:text>
                                    <xsl:value-of select="$param/round"/>
                                    <xsl:text> (turn </xsl:text>
                                    <xsl:value-of select="$current"/>
                                    <xsl:text>).</xsl:text>
                                </xsl:when>
                                <!-- draw -->
                                <xsl:when test="($param/winner = '') and ($param/outcome_type = 'Abort')">
                                    <xsl:text>Game was aborted in round </xsl:text>
                                    <xsl:value-of select="$param/round"/>
                                    <xsl:text> (turn </xsl:text>
                                    <xsl:value-of select="$current"/>
                                    <xsl:text>).</xsl:text>
                                </xsl:when>
                            </xsl:choose>
                        </p>
                    </xsl:when>
                    <xsl:otherwise>
                        <!-- game round -->
                        <p class="information-line info">
                            <xsl:text>Round </xsl:text>
                            <xsl:value-of select="$param/round"/>
                            <xsl:text> (turn </xsl:text>
                            <xsl:value-of select="$current"/>
                            <xsl:text> of </xsl:text>
                            <xsl:value-of select="$turns"/>
                            <xsl:text>)</xsl:text>
                        </p>
                    </xsl:otherwise>
                </xsl:choose>
            </div>

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

                        <!-- game state indicator -->
                        <p class="info-label game-state"><xsl:value-of select="$param/current_player"/>'s turn</p>
                    </div>
                </div>

                <!-- player1 cards -->
                <div class="row hand">
                    <xsl:copy-of select="am:hand(
                        $param/p1_hand, $param/hidden_cards, $param/card_mini_flag,
                        $param/card_old_look, $param/card_insignias, $param/p1_card_foils
                    )"/>
                </div>

                <div class="row">
                    <div class="col-md-3 empire-info">
                        <!-- player1 empire info -->
                        <div class="stats">
                            <xsl:copy-of select="am:stockInfo($param/p1_stock, $param/p1_changes, $param/res_victory)"/>
                            <xsl:copy-of select="am:castleInfo(
                                $param/p1_tower, $param/p1_changes/tower, $param/max_tower,
                                $param/p1_wall, $param/p1_changes/wall, $param/max_wall
                            )"/>
                        </div>

                        <!-- player1 tower and wall -->
                        <div>
                            <xsl:copy-of select="am:castleDisplay(
                                'left', $param/p1_tower, $param/p1_wall, $param/max_tower, $param/max_wall
                            )"/>
                        </div>

                        <div class="token-list">
                            <!-- player1 name -->
                            <p class="token-counter player-label">
                                <xsl:copy-of select="am:playerName($param/player1, $param/ai_name, $param/system_name)"/>
                                <img class="icon" width="18" height="12" src="img/flags/{$param/p1_country}.gif" alt="country flag" title="{$param/p1_country}"/>
                            </p>

                            <!-- player1 tokens -->
                            <xsl:copy-of select="am:tokens($param/p1_tokens, $param/card_insignias)"/>
                        </div>
                    </div>
                    <div class="col-md-6 empire-info">
                        <!-- player1 discarded cards -->
                        <xsl:copy-of select="am:discardedCards(
                            $param/p1_discarded_cards_0, $param/p1_discarded_cards_1,
                            $param/card_old_look, $param/card_insignias, $param/p1_card_foils
                        )"/>

                        <!-- player1 last played card(s) -->
                        <div class="card-list">
                            <xsl:copy-of select="am:cardHistory(
                                $param/p1_last_card, $param/card_old_look, $param/card_insignias, $param/p1_card_foils
                            )"/>
                        </div>

                        <!-- player2 last played card(s) -->
                        <div class="card-list">
                            <xsl:copy-of select="am:cardHistory(
                                $param/p2_last_card, $param/card_old_look, $param/card_insignias, $param/p2_card_foils
                            )"/>
                        </div>

                        <!-- player2 discarded cards -->
                        <xsl:copy-of select="am:discardedCards(
                            $param/p2_discarded_cards_1, $param/p2_discarded_cards_0,
                            $param/card_old_look, $param/card_insignias, $param/p2_card_foils
                        )"/>
                    </div>
                    <div class="col-md-3 empire-info">
                        <!-- player2 tower and wall -->
                        <div>
                            <xsl:copy-of select="am:castleDisplay(
                                'right', $param/p2_tower, $param/p2_wall, $param/max_tower, $param/max_wall
                            )"/>
                        </div>

                        <!-- player2 empire info -->
                        <div class="stats align-right">
                            <xsl:copy-of select="am:stockInfo($param/p2_stock, $param/p2_changes, $param/res_victory)"/>
                            <xsl:copy-of select="am:castleInfo(
                                $param/p2_tower, $param/p2_changes/tower, $param/max_tower,
                                $param/p2_wall, $param/p2_changes/wall, $param/max_wall
                            )"/>
                        </div>

                        <div class="token-list">
                            <!-- player2 tokens -->
                            <xsl:copy-of select="am:tokens($param/p2_tokens, $param/card_insignias)"/>

                            <!-- player2 name -->
                            <p class="token-counter player-label">
                                <img class="icon" width="18" height="12" src="img/flags/{$param/p2_country}.gif" alt="country flag" title="{$param/p2_country}"/>
                                <xsl:copy-of select="am:playerName($param/player2, $param/ai_name, $param/system_name)"/>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- player2 cards -->
                <div class="row hand">
                    <xsl:copy-of select="am:hand(
                        $param/p2_hand, $param/hidden_cards, $param/card_mini_flag,
                        $param/card_old_look, $param/card_insignias, $param/p2_card_foils
                    )"/>
                </div>
            </div>
        </div>

    </xsl:template>


    <xsl:template match="section[. = 'Replays_history']">
        <xsl:variable name="param" select="$params/replays_history"/>
        <xsl:variable name="turns" select="$param/turns"/>
        <xsl:variable name="current" select="$param/current_turn"/>

        <div class="game top-level">
            <div class="row">
                <!-- begin navigation -->
                <p class="information-line">
                    <a class="button" href="{am:makeUrl('Games_details', 'current_game', $param/current_replay)}">
                        <xsl:text>Back to game</xsl:text>
                    </a>

                    <!-- previous -->
                    <xsl:choose>
                        <xsl:when test="$current &gt; 1">
                            <a class="button button-icon" href="{am:makeUrl('Replays_history', 'CurrentReplay', $param/current_replay, 'Turn', am:max($current - 1, 1))}">
                                <span class="glyphicon glyphicon-chevron-left"/>
                            </a>
                        </xsl:when>
                        <xsl:otherwise>
                            <span class="disabled">
                                <span class="glyphicon glyphicon-chevron-left"/>
                            </span>
                        </xsl:otherwise>
                    </xsl:choose>

                    <!-- first -->
                    <xsl:choose>
                        <xsl:when test="$current &gt; 1">
                            <a class="button button-icon" href="{am:makeUrl('Replays_history', 'CurrentReplay', $param/current_replay, 'Turn', 1)}">
                                <span class="glyphicon glyphicon-step-backward"/>
                            </a>
                        </xsl:when>
                        <xsl:otherwise>
                            <span class="disabled">
                                <span class="glyphicon glyphicon-step-backward"/>
                            </span>
                        </xsl:otherwise>
                    </xsl:choose>

                    <!-- page selection -->
                    <xsl:for-each select="str:split(am:numbers(am:max($current - 5, 1), am:min($current + 5, $turns)), ',')">
                        <xsl:choose>
                            <xsl:when test="$current != .">
                                <a class="button button-icon" href="{am:makeUrl('Replays_history', 'CurrentReplay', $param/current_replay, 'Turn', text())}">
                                    <xsl:value-of select="text()"/>
                                </a>
                            </xsl:when>
                            <xsl:otherwise>
                                <span class="disabled">
                                    <xsl:value-of select="text()"/>
                                </span>
                            </xsl:otherwise>
                        </xsl:choose>
                    </xsl:for-each>

                    <!-- last -->
                    <xsl:choose>
                        <xsl:when test="$current &lt; $turns">
                            <a class="button button-icon" href="{am:makeUrl('Replays_history', 'CurrentReplay', $param/current_replay, 'Turn', $turns)}">
                                <span class="glyphicon glyphicon-step-forward"/>
                            </a>
                        </xsl:when>
                        <xsl:otherwise>
                            <span class="disabled">
                                <span class="glyphicon glyphicon-step-forward"/>
                            </span>
                        </xsl:otherwise>
                    </xsl:choose>

                    <!-- next -->
                    <xsl:choose>
                        <xsl:when test="$current &lt; $turns">
                            <a class="button" href="{am:makeUrl('Replays_history', 'CurrentReplay', $param/current_replay, 'Turn', am:min($current + 1, $turns))}">
                                <span class="glyphicon glyphicon-chevron-right"/>
                            </a>
                        </xsl:when>
                        <xsl:otherwise>
                            <span class="disabled">
                                <span class="glyphicon glyphicon-chevron-right"/>
                            </span>
                        </xsl:otherwise>
                    </xsl:choose>
                </p>
                <!-- end navigation -->
            </div>

            <div class="row">
                <!-- game round -->
                <p class="information-line info">
                    <xsl:text>Round </xsl:text>
                    <xsl:value-of select="$param/round"/>
                </p>
            </div>


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

                        <!-- game state indicator -->
                        <p class="info-label game-state"><xsl:value-of select="$param/current_player"/>'s turn</p>
                    </div>
                </div>

                <!-- player1 cards -->
                <div class="row hand">
                    <xsl:copy-of select="am:hand(
                        $param/p1_hand, $param/hidden_cards, $param/card_mini_flag,
                        $param/card_old_look, $param/card_insignias, $param/p1_card_foils
                    )"/>
                </div>

                <div class="row">
                    <div class="col-md-3 empire-info">
                        <!-- player1 empire info -->
                        <div class="stats">
                            <xsl:copy-of select="am:stockInfo($param/p1_stock, $param/p1_changes, $param/res_victory)"/>
                            <xsl:copy-of select="am:castleInfo(
                                $param/p1_tower, $param/p1_changes/tower, $param/max_tower,
                                $param/p1_wall, $param/p1_changes/wall, $param/max_wall
                            )"/>
                        </div>

                        <!-- player1 tower and wall -->
                        <div>
                            <xsl:copy-of select="am:castleDisplay(
                                'left', $param/p1_tower, $param/p1_wall, $param/max_tower, $param/max_wall
                            )"/>
                        </div>

                        <div class="token-list">
                            <!-- player1 name -->
                            <p class="token-counter player-label">
                                <xsl:copy-of select="am:playerName($param/player1, $param/ai_name, $param/system_name)"/>
                                <img class="icon" width="18" height="12" src="img/flags/{$param/p1_country}.gif" alt="country flag" title="{$param/p1_country}"/>
                            </p>

                            <!-- player1 tokens -->
                            <xsl:copy-of select="am:tokens($param/p1_tokens, $param/card_insignias)"/>
                        </div>
                    </div>
                    <div class="col-md-6 empire-info">
                        <!-- player1 discarded cards -->
                        <xsl:copy-of select="am:discardedCards(
                            $param/p1_discarded_cards_0, $param/p1_discarded_cards_1,
                            $param/card_old_look, $param/card_insignias, $param/p1_card_foils
                        )"/>

                        <!-- player1 last played card(s) -->
                        <div class="card-list">
                            <xsl:copy-of select="am:cardHistory(
                                $param/p1_last_card, $param/card_old_look, $param/card_insignias, $param/p1_card_foils
                            )"/>
                        </div>

                        <!-- player2 last played card(s) -->
                        <div class="card-list">
                            <xsl:copy-of select="am:cardHistory(
                                $param/p2_last_card, $param/card_old_look, $param/card_insignias, $param/p2_card_foils
                            )"/>
                        </div>

                        <!-- player2 discarded cards -->
                        <xsl:copy-of select="am:discardedCards(
                            $param/p2_discarded_cards_1, $param/p2_discarded_cards_0,
                            $param/card_old_look, $param/card_insignias, $param/p2_card_foils
                        )"/>
                    </div>
                    <div class="col-md-3 empire-info">
                        <!-- player2 tower and wall -->
                        <div>
                            <xsl:copy-of select="am:castleDisplay(
                                'right', $param/p2_tower, $param/p2_wall, $param/max_tower, $param/max_wall
                            )"/>
                        </div>

                        <!-- player2 empire info -->
                        <div class="stats align-right">
                            <xsl:copy-of select="am:stockInfo($param/p2_stock, $param/p2_changes, $param/res_victory)"/>
                            <xsl:copy-of select="am:castleInfo(
                                $param/p2_tower, $param/p2_changes/tower, $param/max_tower,
                                $param/p2_wall, $param/p2_changes/wall, $param/max_wall
                            )"/>
                        </div>

                        <div class="token-list">
                            <!-- player2 tokens -->
                            <xsl:copy-of select="am:tokens($param/p2_tokens, $param/card_insignias)"/>

                            <!-- player2 name -->
                            <p class="token-counter player-label">
                                <img class="icon" width="18" height="12" src="img/flags/{$param/p2_country}.gif" alt="country flag" title="{$param/p2_country}"/>
                                <xsl:copy-of select="am:playerName($param/player2, $param/ai_name, $param/system_name)"/>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- player2 cards -->
                <div class="row hand">
                    <xsl:copy-of select="am:opponentHand(
                        $param/p2_hand, $param/hidden_cards, $param/card_mini_flag,
                        $param/card_old_look, $param/card_insignias, $param/p2_card_foils
                    )"/>
                </div>
            </div>
        </div>

    </xsl:template>


</xsl:stylesheet>
