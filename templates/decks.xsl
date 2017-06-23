<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
                xmlns="http://www.w3.org/1999/xhtml"
                xmlns:am="http://arcomage.net"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:exsl="http://exslt.org/common"
                extension-element-prefixes="exsl">
    <xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes"
                doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN"
                doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"/>

    <!-- includes -->
    <xsl:include href="main.xsl"/>


    <xsl:template match="section[. = 'Decks']">
        <xsl:variable name="param" select="$params/decks"/>

        <div class="decks">

            <!-- upper navigation -->
            <xsl:if test="$param/player_level &gt;= $param/tutorial_end">
                <div class="decks-nav-bar">
                    <a class="button" href="{am:makeUrl('Decks_shared')}">Shared decks</a>
                </div>
            </xsl:if>

            <div class="responsive-table table-sm skin-text">
                <!-- table header -->
                <div class="row">
                    <div class="col-sm-1">
                        <p>State</p>
                    </div>
                    <div class="col-sm-4">
                        <p>Name</p>
                    </div>
                    <div class="col-sm-3">
                        <p>Wins / Losses / Draws</p>
                    </div>
                    <div class="col-sm-4">
                        <p>Last change</p>
                    </div>
                </div>

                <!-- table body -->
                <xsl:for-each select="$param/list/*">
                    <div class="row table-row details">
                        <div class="col-sm-1">
                            <p>
                                <xsl:if test="is_ready = 'yes'">
                                    <span class="deck-item-icon"><span class="glyphicon glyphicon-ok" title="Ready"/></span>
                                </xsl:if>
                                <xsl:if test="is_shared = 1">
                                    <span class="deck-item-icon"><span class="glyphicon glyphicon-eye-open" title="Shared"/></span>
                                </xsl:if>
                            </p>
                        </div>
                        <div class="col-sm-4">
                            <p>
                                <a class="profile" href="{am:makeUrl('Decks_edit', 'current_deck', deck_id)}">
                                    <xsl:value-of select="deck_name"/>
                                </a>
                            </p>
                        </div>
                        <div class="col-sm-3">
                            <p>
                                <xsl:value-of select="wins"/>
                                <xsl:text> / </xsl:text>
                                <xsl:value-of select="losses"/>
                                <xsl:text> / </xsl:text>
                                <xsl:value-of select="draws"/>
                            </p>
                        </div>
                        <div class="col-sm-4">
                            <p>
                                <xsl:choose>
                                    <xsl:when test="modified_at != '1970-01-01 00:00:01'">
                                        <xsl:copy-of select="am:dateTime(modified_at, $param/timezone)"/>
                                    </xsl:when>
                                    <xsl:otherwise>n/a</xsl:otherwise>
                                </xsl:choose>
                            </p>
                        </div>
                    </div>
                </xsl:for-each>
            </div>
        </div>
    </xsl:template>


    <xsl:template match="section[. = 'Decks_shared']">
        <xsl:variable name="param" select="$params/decks_shared"/>

        <div class="decks">

            <!-- upper navigation -->
            <div class="decks-nav-bar">

                <!-- author filter -->
                <xsl:if test="count($param/authors/*) &gt; 0">
                    <xsl:variable name="authors">
                        <value name="No author filter" value="none"/>
                    </xsl:variable>
                    <xsl:copy-of select="am:htmlSelectBox(
                        'author_filter', $param/author_val, $authors, $param/authors
                    )"/>
                </xsl:if>

                <button class="button-icon" type="submit" name="decks_shared_filter" title="Apply filters">
                    <span class="glyphicon glyphicon-filter"/>
                </button>
                <xsl:copy-of select="am:upperNavigation($param/page_count, $param/current_page, 'decks')"/>

                <!-- selected deck -->
                <span>Target deck</span>
                <select name="selected_deck" size="1">
                    <xsl:for-each select="$param/decks/*">
                        <option value="{deck_id}">
                            <xsl:value-of select="deck_name"/>
                        </option>
                    </xsl:for-each>
                </select>
            </div>

            <div class="responsive-table table-sm skin-text">
                <!-- table header -->
                <div class="row">
                    <xsl:variable name="columns">
                        <column name="deck_name" text="Name" sortable="yes" size="2"/>
                        <column name="username" text="Author" sortable="yes" size="3"/>
                        <column name="score" text="Wins / Losses / Draws" sortable="no" size="3"/>
                        <column name="modified_at" text="Last change" sortable="yes" size="3"/>
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
                                        <xsl:if test="$param/current_condition = @name">
                                            <xsl:attribute name="class">button-icon pushed</xsl:attribute>
                                        </xsl:if>
                                        <xsl:choose>
                                            <xsl:when test="(($param/current_condition = @name) and ($param/current_order = 'DESC'))">
                                                <xsl:attribute name="name">decks_order_asc</xsl:attribute>
                                                <span class="glyphicon glyphicon-sort-by-attributes-alt"/>
                                            </xsl:when>
                                            <xsl:otherwise>
                                                <xsl:attribute name="name">decks_order_desc</xsl:attribute>
                                                <span class="glyphicon glyphicon-sort-by-attributes"/>
                                            </xsl:otherwise>
                                        </xsl:choose>
                                    </button>
                                </xsl:if>
                            </p>
                        </div>
                    </xsl:for-each>
                    <div class="col-sm-1">
                        <p/>
                    </div>
                </div>

                <!-- table body -->
                <xsl:for-each select="$param/shared_list/*">
                    <div class="row table-row details">
                        <div class="col-sm-2">
                            <p>
                                <a class="profile" href="{am:makeUrl('Decks_details', 'current_deck', deck_id)}">
                                    <xsl:value-of select="deck_name"/>
                                </a>
                            </p>
                        </div>
                        <div class="col-sm-3">
                            <p>
                                <a class="profile" href="{am:makeUrl('Players_details', 'Profile', username)}">
                                    <xsl:value-of select="username"/>
                                </a>
                            </p>
                        </div>
                        <div class="col-sm-3">
                            <p>
                                <xsl:value-of select="wins"/>
                                <xsl:text> / </xsl:text>
                                <xsl:value-of select="losses"/>
                                <xsl:text> / </xsl:text>
                                <xsl:value-of select="draws"/>
                            </p>
                        </div>
                        <div class="col-sm-3">
                            <p>
                                <xsl:choose>
                                    <xsl:when test="modified_at != '1970-01-01 00:00:01'">
                                        <xsl:copy-of select="am:dateTime(modified_at, $param/timezone)"/>
                                    </xsl:when>
                                    <xsl:otherwise>n/a</xsl:otherwise>
                                </xsl:choose>
                            </p>
                        </div>
                        <div class="col-sm-1">
                            <p>
                                <button class="button-icon" type="submit" name="import_shared_deck" value="{deck_id}" title="Import">
                                    <span class="glyphicon glyphicon-duplicate"/>
                                </button>
                            </p>
                        </div>
                    </div>
                </xsl:for-each>
            </div>

            <div class="decks-nav-bar">
                <!-- lower navigation -->
                <xsl:copy-of select="am:lowerNavigation($param/page_count, $param/current_page, 'decks', 'Decks_shared')"/>
            </div>

            <input type="hidden" name="decks_current_page" value="{$param/current_page}"/>
            <input type="hidden" name="decks_current_order" value="{$param/current_order}"/>
            <input type="hidden" name="decks_current_condition" value="{$param/current_condition}"/>

        </div>
    </xsl:template>


    <xsl:template match="section[. = 'Decks_details']">
        <xsl:variable name="param" select="$params/decks_details"/>

        <div id="deck-shared">

            <div class="decks-nav-bar">
                <a class="button button-icon" href="{am:makeUrl('Decks_shared')}">
                    <span class="glyphicon glyphicon-arrow-left"/>
                </a>
                <xsl:choose>
                    <xsl:when test="$param/discussion = 0 and $param/create_thread = 'yes'">
                        <button class="button-icon" type="submit" name="find_deck_thread" value="{$param/deck_id}" title="Start discussion">
                            <span class="glyphicon glyphicon-comment"/>
                        </button>
                    </xsl:when>
                    <xsl:when test="$param/discussion &gt; 0">
                        <a class="button button-icon" href="{am:makeUrl('Forum_thread', 'current_thread', $param/discussion, 'thread_current_page', 0)}" title="View discussion">
                            <span class="glyphicon glyphicon-comment"/>
                        </a>
                    </xsl:when>
                </xsl:choose>
                <div class="skin-text">
                    <span>
                        <xsl:value-of select="$param/deck_name"/>
                    </span>
                    <span class="cost-per-turn" title="average cost per turn (bricks, gems, recruits)">
                        <b><xsl:value-of select="$param/avg_cost/bricks"/></b>
                        <b><xsl:value-of select="$param/avg_cost/gems"/></b>
                        <b><xsl:value-of select="$param/avg_cost/recruits"/></b>
                    </span>
                    <xsl:if test="$param/tokens != ''">
                        <span>
                            <xsl:value-of select="$param/tokens"/>
                        </span>
                    </xsl:if>
                </div>
            </div>
            <xsl:if test="$param/note != ''">
                <div class="skin-text">
                    <p>
                        <xsl:value-of select="$param/note"/>
                    </p>
                </div>
            </xsl:if>

            <xsl:copy-of select="am:renderDeck(
                $param/deck_cards, $param/card_old_look, $param/card_insignias, $param/card_foils
            )"/>

        </div>

    </xsl:template>


    <xsl:template match="section[. = 'Decks_view']">
        <xsl:variable name="param" select="$params/deck_view"/>

        <div class="back-to-game">
            <a class="button" href="{am:makeUrl('Games_details', 'current_game', $param/current_game)}">Back to game</a>
        </div>

        <xsl:copy-of select="am:renderDeck(
            $param/deck_cards, $param/card_old_look, $param/card_insignias, $param/card_foils
        )"/>

    </xsl:template>


    <xsl:template match="section[. = 'Decks_edit']">
        <xsl:variable name="param" select="$params/deck_edit"/>

        <div id="deck_edit">
            <div class="deck-options">
                <xsl:choose>
                    <xsl:when test="$param/reset = 'no'">
                        <button class="button-icon" type="submit" name="reset_deck_prepare" title="Reset deck">
                            <span class="glyphicon glyphicon-trash"/>
                        </button>
                    </xsl:when>
                    <xsl:otherwise>
                        <button class="button-icon marked_button" type="submit" name="reset_deck_confirm" title="Confirm deck reset">
                            <span class="glyphicon glyphicon-trash"/>
                        </button>
                    </xsl:otherwise>
                </xsl:choose>

                <input type="text" name="new_deck_name" value="{$param/deck_name}" maxlength="20"/>
                <button class="button-icon" type="submit" name="rename_deck" title="Rename deck">
                    <span class="glyphicon glyphicon-pencil"/>
                </button>

                <input type="file" name="deck_data_file"/>
                <button class="button-icon" type="submit" name="import_deck" title="Import deck">
                    <span class="glyphicon glyphicon-open-file"/>
                </button>
                <button class="button-icon" type="submit" name="export_deck" title="Export deck">
                    <span class="glyphicon glyphicon-save-file"/>
                </button>

                <button class="button-icon" type="button" name="print" title="Print">
                    <span class="glyphicon glyphicon-print" />
                </button>

                <!-- share/unshare button -->
                <xsl:if test="$param/player_level &gt;= $param/tutorial_end">
                    <xsl:choose>
                        <xsl:when test="$param/shared = 'yes'">
                            <button class="button-icon" type="submit" name="unshare_deck" title="Unshare deck">
                                <span class="glyphicon glyphicon-eye-close"/>
                            </button>
                        </xsl:when>
                        <xsl:otherwise>
                            <button class="button-icon" type="submit" name="share_deck" title="Share deck">
                                <span class="glyphicon glyphicon-eye-open"/>
                            </button>
                        </xsl:otherwise>
                    </xsl:choose>
                </xsl:if>

                <a class="button button-icon" id="deck-note" href="{am:makeUrl('Decks_note', 'current_deck', $param/current_deck)}" title="Note">
                    <xsl:if test="$param/note != ''">
                        <xsl:attribute name="class">button button-icon marked_button</xsl:attribute>
                    </xsl:if>
                    <span class="glyphicon glyphicon-edit"/>
                </a>
            </div>
            <div class="deck-options">
                <div id="tokens">
                    <xsl:for-each select="$param/tokens/*">
                        <xsl:variable name="token" select="."/>

                        <select name="Token{position()}">
                            <option value="none">
                                <xsl:if test="$token = 'none'">
                                    <xsl:attribute name="selected">selected</xsl:attribute>
                                </xsl:if>
                                <xsl:text>None</xsl:text>
                            </option>
                            <xsl:for-each select="$param/token_keywords/*">
                                <option value="{text()}">
                                    <xsl:if test="$token = .">
                                        <xsl:attribute name="selected">selected</xsl:attribute>
                                    </xsl:if>
                                    <xsl:value-of select="text()"/>
                                </option>
                            </xsl:for-each>
                        </select>
                    </xsl:for-each>

                    <button class="button-icon" type="submit" name="set_tokens" title="Save tokens">
                        <span class="glyphicon glyphicon-floppy-disk"/>
                    </button>
                    <button class="button-icon" type="submit" name="auto_tokens" title="Let AI assign tokens">
                        <span class="glyphicon glyphicon-hdd"/>
                    </button>
                </div>

                <div class="cost-per-turn" title="average cost per turn (bricks, gems, recruits)">
                    <b><xsl:value-of select="$param/avg_cost/bricks"/></b>
                    <b><xsl:value-of select="$param/avg_cost/gems"/></b>
                    <b><xsl:value-of select="$param/avg_cost/recruits"/></b>
                </div>

                <p class="deck-stats">
                    <xsl:attribute name="title">deck statistics (wins / losses / draws)</xsl:attribute>
                    <b><xsl:value-of select="$param/wins"/></b>
                    <xsl:text> / </xsl:text>
                    <b><xsl:value-of select="$param/losses"/></b>
                    <xsl:text> / </xsl:text>
                    <b><xsl:value-of select="$param/draws"/></b>
                </p>

                <xsl:choose>
                    <xsl:when test="$param/reset_stats = 'no'">
                        <button class="button-icon" type="submit" name="reset_stats_prepare" title="Reset deck statistics">
                            <span class="glyphicon glyphicon-retweet"/>
                        </button>
                    </xsl:when>
                    <xsl:otherwise>
                        <button class="button-icon marked_button" type="submit" name="reset_stats_confirm" title="Confirm reset statistics">
                            <span class="glyphicon glyphicon-retweet"/>
                        </button>
                    </xsl:otherwise>
                </xsl:choose>
            </div>

            <div class="deck-options">
                <xsl:copy-of select="am:cardFilters(
                    $param/keywords, $param/levels, $param/created_dates, $param/modified_dates,
                    $param/name_filter, $param/rarity_filter, $param/keyword_filter, $param/cost_filter,
                    $param/advanced_filter, $param/support_filter, $param/created_filter, $param/modified_filter,
                    $param/level_filter, $param/card_sort
                )"/>

                <button class="button-icon" type="submit" name="deck_apply_filters" title="Apply filters">
                    <span class="glyphicon glyphicon-filter"/>
                </button>
                <button class="button-icon" type="submit" name="card_pool_switch" title="show / hide card pool">
                    <xsl:attribute name="class">
                        <xsl:choose>
                            <xsl:when test="$param/card_pool = 'yes'">button-icon hide-card-pool</xsl:when>
                            <xsl:otherwise>button-icon show-card-pool</xsl:otherwise>
                        </xsl:choose>
                    </xsl:attribute>
                    <xsl:choose>
                        <xsl:when test="$param/card_pool = 'yes'">
                            <span class="glyphicon glyphicon-resize-small"/>
                        </xsl:when>
                        <xsl:otherwise>
                            <span class="glyphicon glyphicon-resize-full"/>
                        </xsl:otherwise>
                    </xsl:choose>
                </button>
            </div>

            <!-- cards in card pool -->
            <div id="card-pool" class="row">
                <xsl:if test="$param/card_pool = 'no'">
                    <xsl:attribute name="class">hidden</xsl:attribute>
                </xsl:if>
                <!-- sort cards in card pool -->
                <xsl:variable name="cardList">
                    <xsl:choose>
                        <!-- sort by total card cost -->
                        <xsl:when test="$param/card_sort = 'cost'">
                            <xsl:for-each select="$param/card_list/*">
                                <xsl:sort select="bricks + gems + recruits" order="ascending" data-type="number"/>
                                <xsl:copy-of select="."/>
                            </xsl:for-each>
                        </xsl:when>
                        <!-- sort by card name (default sorting) -->
                        <xsl:otherwise>
                            <xsl:for-each select="$param/card_list/*">
                                <xsl:sort select="name" order="ascending"/>
                                <xsl:copy-of select="."/>
                            </xsl:for-each>
                        </xsl:otherwise>
                    </xsl:choose>
                </xsl:variable>

                <xsl:for-each select="exsl:node-set($cardList)/*">
                    <div>
                        <!-- display card slot -->
                        <div id="card_{id}">
                            <xsl:choose>
                                <xsl:when test="locked = 'no' and excluded = 'no'">
                                    <xsl:attribute name="onclick">return takeCard(<xsl:value-of select="id"/>)</xsl:attribute>
                                </xsl:when>
                                <xsl:when test="locked = 'no' and excluded = 'yes'">
                                    <xsl:attribute name="class">taken</xsl:attribute>
                                </xsl:when>
                            </xsl:choose>
                            <xsl:copy-of select="am:cardString(
                                current(), $param/card_old_look, $param/card_insignias, $param/card_foils
                            )"/>
                            <xsl:if test="locked = 'yes'">
                                <p class="locked-card">locked</p>
                            </xsl:if>
                        </div>
                        <xsl:if test="locked = 'no' and excluded = 'no'">
                            <noscript>
                                <div>
                                    <button class="button-icon" type="submit" name="add_card" value="{id}" title="Take">
                                        <span class="glyphicon glyphicon-download"/>
                                    </button>
                                </div>
                            </noscript>
                        </xsl:if>
                    </div>
                </xsl:for-each>
            </div>

            <!-- cards in deck -->
            <xsl:copy-of select="am:renderDeck(
                $param/deck_cards, $param/card_old_look, $param/card_insignias, $param/card_foils, false()
            )"/>
        </div>

        <!-- remember the current location across pages -->
        <div>
            <input type="hidden" name="current_deck" value="{$param/current_deck}"/>
            <input type="hidden" name="card_pool" value="{$param/card_pool}"/>
        </div>

        <!-- deck note dialog -->
        <div class="modal fade" id="deck-note-dialog" role="dialog">
            <div class="vertical-alignment-helper">
                <div class="modal-dialog vertical-align-center">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button name="close-modal" type="button" class="close" data-dismiss="modal">&#10006;</button>
                            <p class="modal-title">Note</p>
                        </div>
                        <div class="modal-body">
                            <p>
                                <textarea name="content" rows="10" cols="50">
                                    <xsl:value-of select="$param/note"/>
                                </textarea>
                            </p>
                        </div>
                        <div class="modal-footer">
                            <button name="deck-note-dialog-save" type="button" class="btn btn-default">
                                <span class="btn-inner">
                                    <span class="btn-text">Save</span>
                                </span>
                            </button>
                            <button name="deck-note-dialog-clear" type="button" class="btn btn-default">
                                <span class="btn-inner">
                                    <span class="btn-text">Clear</span>
                                </span>
                            </button>
                            <button name="deck-note-dialog-dismiss" type="button" class="btn btn-default" data-dismiss="modal">
                                <span class="btn-inner">
                                    <span class="btn-text">Close</span>
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </xsl:template>


    <xsl:template match="section[. = 'Decks_note']">
        <xsl:variable name="param" select="$params/deck_note"/>

        <div class="deck-note">

            <h3>Deck note</h3>

            <div class="skin-text">
                <a class="button button-icon" href="{am:makeUrl('Decks_edit', 'current_deck', $param/current_deck)}">
                    <span class="glyphicon glyphicon-arrow-left"/>
                </a>
                <button type="submit" name="save_deck_note_return">Save &amp; return</button>
                <button type="submit" name="save_deck_note">Save</button>
                <button type="submit" name="clear_deck_note">Clear</button>
                <button type="submit" name="clear_deck_note_return">Clear &amp; return</button>
                <hr/>

                <textarea name="content" rows="10" cols="50">
                    <xsl:value-of select="$param/text"/>
                </textarea>
            </div>

            <input type="hidden" name="current_deck" value="{$param/current_deck}"/>
        </div>

    </xsl:template>


</xsl:stylesheet>
