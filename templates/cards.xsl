<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
                xmlns="http://www.w3.org/1999/xhtml"
                xmlns:am="http://arcomage.net"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:date="http://exslt.org/dates-and-times"
                extension-element-prefixes="date">
    <xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes"
                doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN"
                doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"/>

    <!-- includes -->
    <xsl:include href="main.xsl"/>


    <xsl:template match="section[. = 'Cards']">
        <xsl:variable name="param" select="$params/cards"/>

        <!-- begin buttons and filters -->
        <xsl:choose>
            <xsl:when test="$param/is_logged_in = 'yes'">
                <!-- advanced navigation (for authenticated users only) -->
                <div class="filters">

                    <xsl:copy-of select="am:cardFilters(
                        $param/keywords, $param/levels, $param/created_dates, $param/modified_dates,
                        $param/name_filter, $param/rarity_filter, $param/keyword_filter, $param/cost_filter,
                        $param/advanced_filter, $param/support_filter, $param/created_filter, $param/modified_filter,
                        $param/level_filter, $param/card_sort
                    )"/>

                    <button class="button-icon" type="submit" name="cards_apply_filters" title="Apply filters">
                        <span class="glyphicon glyphicon-filter"/>
                    </button>
                </div>

                <!-- navigation -->
                <div class="filters">
                    <xsl:copy-of select="am:upperNavigation($param/page_count, $param/current_page, 'cards')"/>
                </div>

            </xsl:when>
            <xsl:otherwise>
                <!-- simple navigation (for anonymous users) -->
                <div class="filters">
                    <xsl:copy-of select="am:simpleNavigation(
                        'Cards', 'cards_current_page', $param/current_page, $param/page_count
                    )"/>
                </div>
            </xsl:otherwise>
        </xsl:choose>
        <!-- end buttons and filters -->

        <div class="responsive-table responsive-table--centered table-md skin-text top-level">
            <!-- table header -->
            <div class="row">
                <div class="col-md-1">
                    <p>Card</p>
                </div>
                <div class="col-md-2">
                    <p>Name</p>
                </div>
                <div class="col-md-1">
                    <p>Rarity</p>
                </div>
                <div class="col-md-1">
                    <p>Cost</p>
                </div>
                <div class="col-md-1">
                    <p>Level</p>
                </div>
                <div class="col-md-4">
                    <p>Effect</p>
                </div>
                <div class="col-md-1">
                    <p>Created</p>
                </div>
                <div class="col-md-1">
                    <p>Modified</p>
                </div>
            </div>

            <!-- table body -->
            <xsl:for-each select="$param/card_list/*">
                <div class="row">
                    <div class="col-md-1">
                        <xsl:copy-of select="am:cardString(
                            current(), $param/card_old_look, $param/card_insignias, $param/card_foils
                        )"/>
                    </div>
                    <div class="col-md-2">
                        <p>
                            <a href="{am:makeUrl('Cards_details', 'card', id)}">
                                <xsl:value-of select="name"/>
                            </a>
                        </p>
                    </div>
                    <div class="col-md-1"><p><xsl:value-of select="rarity"/></p></div>
                    <div class="col-md-1">
                        <p>
                            <xsl:value-of select="bricks"/>
                            <xsl:text> / </xsl:text>
                            <xsl:value-of select="gems"/>
                            <xsl:text> / </xsl:text>
                            <xsl:value-of select="recruits"/>
                        </p>
                    </div>
                    <div class="col-md-1"><p><xsl:value-of select="level"/></p></div>
                    <div class="col-md-4">
                        <p class="effect">
                            <xsl:value-of select="am:cardEffect(effect)" disable-output-escaping="yes"/>
                        </p>
                    </div>
                    <div class="col-md-1"><p><xsl:value-of select="am:formatDate(created)"/></p></div>
                    <div class="col-md-1"><p><xsl:value-of select="am:formatDate(modified)"/></p></div>
                </div>
            </xsl:for-each>
        </div>

        <xsl:if test="$param/is_logged_in = 'yes'">
            <div class="filters">
                <!-- lower navigation -->
                <xsl:copy-of select="am:lowerNavigation($param/page_count, $param/current_page, 'cards', 'Cards')"/>
            </div>
        </xsl:if>

        <input type="hidden" name="cards_current_page" value="{$param/current_page}"/>

    </xsl:template>


    <xsl:template match="section[. = 'Cards_details']">
        <xsl:variable name="param" select="$params/cards_details"/>

        <div id="cards_details">

            <div class="skin-text details-form">
                <div class="details-form__menu">
                    <a class="button button-icon" href="{am:makeUrl('Cards')}">
                        <span class="glyphicon glyphicon-arrow-left"/>
                    </a>
                    <xsl:choose>
                        <xsl:when test="$param/discussion = 0 and $param/create_thread = 'yes'">
                            <button class="button-icon" type="submit" name="find_card_thread" value="{$param/data/id}" title="Start discussion">
                                <span class="glyphicon glyphicon-comment"/>
                            </button>
                        </xsl:when>
                        <xsl:when test="$param/discussion &gt; 0">
                            <a class="button button-icon" href="{am:makeUrl('Forum_thread', 'current_thread', $param/discussion, 'thread_current_page', 0)}" title="View discussion">
                                <span class="glyphicon glyphicon-comment"/>
                            </a>
                        </xsl:when>
                    </xsl:choose>
                    <xsl:if test="$param/is_logged_in = 'yes' and $param/foil_version = 'no'">
                        <span id="foil-version-purchase">
                            <xsl:text>Buy foil version for </xsl:text>
                            <xsl:value-of select="$param/foil_cost"/>
                            <xsl:text> gold</xsl:text>
                        </span>
                        <button class="button-icon" type="submit" name="buy_foil_card" value="{$param/data/id}">
                            <xsl:if test="$param/gold &lt; $param/foil_cost">
                                <xsl:attribute name="disabled">disabled</xsl:attribute>
                            </xsl:if>
                            <span class="glyphicon glyphicon-usd"/>
                        </button>
                    </xsl:if>
                </div>
                <hr/>

                <xsl:copy-of select="am:cardString(
                    $param/data, $param/card_old_look, $param/card_insignias, $param/card_foils
                )"/>

                <div class="row">
                    <div class="col-xs-6">Id</div>
                    <div class="col-xs-6"><xsl:value-of select="$param/data/id"/></div>
                </div>
                <div class="row">
                    <div class="col-xs-6">Name</div>
                    <div class="col-xs-6">
                        <span id="foil-version-name"><xsl:value-of select="$param/data/name"/></span>
                    </div>
                </div>
                <div class="row">
                    <div class="col-xs-6">Rarity</div>
                    <div class="col-xs-6"><xsl:value-of select="$param/data/rarity"/></div>
                </div>
                <div class="row">
                    <div class="col-xs-6">Keywords</div>
                    <div class="col-xs-6">
                        <xsl:for-each select="$param/data/keywords_list/*">
                            <a href="{am:makeUrl('Cards_keyword_details', 'keyword', .)}">
                                <xsl:value-of select="."/>
                            </a>
                        </xsl:for-each>
                    </div>
                </div>
                <div class="row">
                    <div class="col-xs-6">Cost (B / G / R)</div>
                    <div class="col-xs-6">
                        <xsl:value-of select="$param/data/bricks"/>
                        <xsl:text> / </xsl:text>
                        <xsl:value-of select="$param/data/gems"/>
                        <xsl:text> / </xsl:text>
                        <xsl:value-of select="$param/data/recruits"/>
                    </div>
                </div>
                <div class="row">
                    <div class="col-xs-6">Modes</div>
                    <div class="col-xs-6"><xsl:value-of select="$param/data/modes"/></div>
                </div>
                <div class="row">
                    <div class="col-xs-6">Level</div>
                    <div class="col-xs-6"><xsl:value-of select="$param/data/level"/></div>
                </div>
                <div class="row">
                    <div class="col-xs-6">Created</div>
                    <div class="col-xs-6"><xsl:value-of select="am:formatDate($param/data/created)"/></div>
                </div>
                <div class="row">
                    <div class="col-xs-6">Modified</div>
                    <div class="col-xs-6"><xsl:value-of select="am:formatDate($param/data/modified)"/></div>
                </div>
                <div class="row">
                    <div class="col-xs-6">Played</div>
                    <div class="col-xs-6">
                        <xsl:value-of select="$param/statistics/played"/>
                        <xsl:text> / </xsl:text>
                        <xsl:value-of select="$param/statistics/played_total"/>
                    </div>
                </div>
                <div class="row">
                    <div class="col-xs-6">Discarded</div>
                    <div class="col-xs-6">
                        <xsl:value-of select="$param/statistics/discarded"/>
                        <xsl:text> / </xsl:text>
                        <xsl:value-of select="$param/statistics/discarded_total"/>
                    </div>
                </div>
                <div class="row">
                    <div class="col-xs-6">Drawn</div>
                    <div class="col-xs-6">
                        <xsl:value-of select="$param/statistics/drawn"/>
                        <xsl:text> / </xsl:text>
                        <xsl:value-of select="$param/statistics/drawn_total"/>
                    </div>
                </div>

                <p>BB code</p>
                <div>
                    <input type="text" name="bb_code" maxlength="64" size="25" value="[card={$param/data/id}]{$param/data/name}[/card]" title="BB code"/>
                </div>
                <p>Effect</p>
                <div>
                    <xsl:value-of select="am:cardEffect($param/data/effect)" disable-output-escaping="yes"/>
                </div>
                <p>Code</p>
                <div class="code">
                    <pre>
                        <xsl:copy-of select="$param/data/code/text()"/>
                    </pre>
                </div>
            </div>
        </div>

    </xsl:template>


    <xsl:template match="section[. = 'Cards_lookup']">
        <xsl:variable name="param" select="$params/cards_lookup"/>

        <xsl:copy-of select="am:cardString($param/data, $param/card_old_look, $param/card_insignias, $param/card_foils)"/>

    </xsl:template>


    <xsl:template match="section[. = 'Cards_keywords']">
        <xsl:variable name="param" select="$params/cards_keywords"/>

        <div class="responsive-table responsive-table--centered table-sm skin-text top-level">
            <!-- table header -->
            <div class="row">
                <div class="col-sm-2">
                    <p class="sortable-cell">
                        <xsl:choose>
                            <xsl:when test="$param/order = 'name'">
                                <a href="{am:makeUrl('Cards_keywords', 'keywords_order', 'execution')}">Order</a>
                            </xsl:when>
                            <xsl:otherwise>Order</xsl:otherwise>
                        </xsl:choose>
                    </p>
                </div>
                <div class="col-sm-2">
                    <p class="sortable-cell">
                        <xsl:choose>
                            <xsl:when test="$param/order = 'execution'">
                                <a href="{am:makeUrl('Cards_keywords', 'keywords_order', 'name')}">Name</a>
                            </xsl:when>
                            <xsl:otherwise>Name</xsl:otherwise>
                        </xsl:choose>
                    </p>
                </div>
                <div class="col-sm-8">
                    <p>Effect</p>
                </div>
            </div>

            <!-- table body -->
            <xsl:for-each select="$param/keywords/*">
                <div class="row table-row table-row--details">
                    <div class="col-sm-2">
                        <p>
                            <img class="keyword-insignia" src="img/insignias/{am:fileName(name)}.png" width="12" height="12" alt="{name}" title="{name}"/>
                            <span><xsl:value-of select="order"/></span>
                        </p>
                    </div>
                    <div class="col-sm-2">
                        <p>
                            <a class="hidden-link" href="{am:makeUrl('Cards_keyword_details', 'keyword', name)}">
                                <xsl:value-of select="name"/>
                            </a>
                        </p>
                    </div>
                    <div class="col-sm-8">
                        <p class="long-text">
                            <xsl:if test="basic_gain &gt; 0 or bonus_gain &gt; 0">
                                <xsl:text>Basic gain </xsl:text>
                                <xsl:value-of select="basic_gain"/>
                                <xsl:text>, bonus gain </xsl:text>
                                <xsl:value-of select="bonus_gain"/>
                                <xsl:text>, </xsl:text>
                            </xsl:if>
                            <xsl:value-of select="am:cardEffect(description)" disable-output-escaping="yes"/>
                        </p>
                    </div>
                </div>
            </xsl:for-each>
        </div>

    </xsl:template>


    <xsl:template match="section[. = 'Cards_keyword_details']">
        <xsl:variable name="param" select="$params/keyword_details"/>
        <xsl:variable name="keyword" select="document('../xml/keywords.xml')/am:keywords/am:keyword[am:name = $param/name]"/>

        <xsl:choose>

            <xsl:when test="$keyword">
                <div class="skin-text skin-text--plain-content top-level">
                    <h3>
                        <a href="{am:makeUrl('Cards_keywords')}">Keywords</a>
                        &gt;
                        <xsl:value-of select="$keyword/am:name"/>
                    </h3>

                    <h4>Icon</h4>
                    <p>
                        <img class="keyword-insignia" src="img/insignias/{am:fileName($keyword/am:name)}.png" width="12"
                            height="12" alt="{$keyword/am:name}" title="{$keyword/am:name}"/>
                    </p>
                    <h4>Effect</h4>
                    <p class="long-text">
                        <xsl:if test="$keyword/am:basic_gain &gt; 0 or $keyword/am:bonus_gain &gt; 0">
                            <xsl:text>Basic gain </xsl:text>
                            <xsl:value-of select="$keyword/am:basic_gain"/>
                            <xsl:text>, bonus gain </xsl:text>
                            <xsl:value-of select="$keyword/am:bonus_gain"/>
                            <xsl:text>, </xsl:text>
                        </xsl:if>
                        <xsl:value-of select="am:cardEffect($keyword/am:description)" disable-output-escaping="yes"/>
                    </p>
                    <h4>Lore</h4>
                    <div class="long-text">
                        <xsl:value-of select="$keyword/am:lore" disable-output-escaping="yes"/>
                    </div>
                    <h4>Code</h4>
                    <div class="code">
                        <pre>
                            <xsl:copy-of select="$keyword/am:code/text()"/>
                        </pre>
                    </div>
                </div>
            </xsl:when>

            <xsl:otherwise>
                <h3 class="information-line error">Invalid keyword.</h3>
            </xsl:otherwise>

        </xsl:choose>

    </xsl:template>


</xsl:stylesheet>
