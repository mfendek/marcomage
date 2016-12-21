<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
                xmlns="http://www.w3.org/1999/xhtml"
                xmlns:am="http://arcomage.net"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:date="http://exslt.org/dates-and-times"
                xmlns:exsl="http://exslt.org/common"
                extension-element-prefixes="date exsl">
    <xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes"
                doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN"
                doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"/>

    <!-- includes -->
    <xsl:include href="main.xsl"/>


    <xsl:template match="section[. = 'Concepts']">
        <xsl:variable name="param" select="$params/concepts"/>

        <div id="concepts">
            <!-- begin buttons and filters -->

            <xsl:choose>
                <xsl:when test="$param/is_logged_in = 'yes'">
                    <!-- advanced navigation (for authenticated users only) -->
                    <div class="filters">

                        <xsl:if test="$param/create_card = 'yes'">
                            <button class="button-icon" type="submit" name="new_concept" title="New card">
                                <span class="glyphicon glyphicon-plus"/>
                            </button>
                        </xsl:if>

                        <!-- card name filter -->
                        <input type="text" name="card_name" maxlength="64" size="30" value="{$param/card_name}"
                               title="search phrase for card name"/>

                        <!-- date filter -->
                        <xsl:variable name="dates">
                            <value name="No date filter" value="none"/>
                            <value name="1 day" value="1"/>
                            <value name="2 days" value="2"/>
                            <value name="5 days" value="5"/>
                            <value name="1 week" value="7"/>
                            <value name="2 weeks" value="14"/>
                            <value name="3 weeks" value="21"/>
                            <value name="1 month" value="30"/>
                            <value name="3 months" value="91"/>
                            <value name="6 months" value="182"/>
                            <value name="1 year" value="365"/>
                        </xsl:variable>
                        <xsl:copy-of select="am:htmlSelectBox('date_filter_concepts', $param/date_val, $dates, '')"/>

                        <!-- author filter -->
                        <xsl:if test="count($param/authors/*) &gt; 0">
                            <xsl:variable name="authors">
                                <value name="No author filter" value="none"/>
                            </xsl:variable>
                            <xsl:copy-of select="am:htmlSelectBox(
                                'author_filter', $param/author_val, $authors, $param/authors
                            )"/>
                        </xsl:if>

                        <!-- state filter -->
                        <xsl:variable name="states">
                            <value name="No state filter" value="none"/>
                            <value name="waiting" value="waiting"/>
                            <value name="rejected" value="rejected"/>
                            <value name="interesting" value="interesting"/>
                            <value name="implemented" value="implemented"/>
                        </xsl:variable>
                        <xsl:copy-of select="am:htmlSelectBox('state_filter', $param/state_val, $states, '')"/>

                        <button class="button-icon" type="submit" name="concepts_apply_filters" title="Apply filters">
                            <span class="glyphicon glyphicon-filter"/>
                        </button>

                        <xsl:if test="$param/my_cards = 'yes'">
                            <button class="button-icon" type="submit" name="show_my_concepts" title="My cards">
                                <span class="glyphicon glyphicon-user"/>
                            </button>
                        </xsl:if>
                    </div>

                    <!-- upper navigation -->
                    <div class="filters">
                        <xsl:copy-of select="am:upperNavigation($param/page_count, $param/current_page, 'concepts')"/>
                    </div>

                </xsl:when>
                <xsl:otherwise>
                    <!-- simple navigation (for anonymous users) -->
                    <div class="filters">
                        <xsl:copy-of select="am:simpleNavigation(
                            'Concepts', 'concepts_current_page', $param/current_page, $param/page_count
                        )"/>
                    </div>
                </xsl:otherwise>
            </xsl:choose>

            <!-- end buttons and filters -->

            <div class="responsive-table table-sm skin-text">
                <!-- table header -->
                <div class="row">
                    <xsl:variable name="columns">
                        <column name="card" text="Card" sortable="no" size="2"/>
                        <column name="name" text="Name" sortable="yes" size="2"/>
                        <column name="rarity" text="Rarity" sortable="no" size="1"/>
                        <column name="author" text="Author" sortable="no" size="2"/>
                        <column name="modified_at" text="Last change" sortable="yes" size="2"/>
                        <column name="state" text="State" sortable="no" size="1"/>
                    </xsl:variable>

                    <xsl:for-each select="exsl:node-set($columns)/*">
                        <div class="col-sm-{@size}">
                            <p>
                                <xsl:if test="$param/is_logged_in = 'yes' and @sortable = 'yes'">
                                    <xsl:attribute name="class">sortable</xsl:attribute>
                                </xsl:if>

                                <span><xsl:value-of select="@text"/></span>
                                <xsl:if test="$param/is_logged_in = 'yes' and @sortable = 'yes'">
                                    <button class="button-icon" type="submit" value="{@name}">
                                        <xsl:if test="$param/current_condition = @name">
                                            <xsl:attribute name="class">button-icon pushed</xsl:attribute>
                                        </xsl:if>
                                        <xsl:choose>
                                            <xsl:when test="(($param/current_condition = @name) and ($param/current_order = 'DESC'))">
                                                <xsl:attribute name="name">concepts_order_asc</xsl:attribute>
                                                <span class="glyphicon glyphicon-sort-by-attributes-alt"/>
                                            </xsl:when>
                                            <xsl:otherwise>
                                                <xsl:attribute name="name">concepts_order_desc</xsl:attribute>
                                                <span class="glyphicon glyphicon-sort-by-attributes"/>
                                            </xsl:otherwise>
                                        </xsl:choose>
                                    </button>
                                </xsl:if>
                            </p>
                        </div>
                    </xsl:for-each>
                    <div class="col-sm-2">
                        <p/>
                    </div>
                </div>

                <!-- table body -->
                <xsl:for-each select="$param/list/*">
                    <div class="row">
                        <div class="col-sm-2">
                            <xsl:copy-of select="am:cardString(current(), $param/card_old_look)"/>
                        </div>
                        <div class="col-sm-2">
                            <p>
                                <a href="{am:makeUrl('Concepts_details', 'current_concept', id)}">
                                    <xsl:value-of select="name"/>
                                </a>
                            </p>
                        </div>
                        <div class="col-sm-1">
                            <p>
                                <xsl:value-of select="rarity"/>
                            </p>
                        </div>
                        <div class="col-sm-2">
                            <p>
                                <a class="profile" href="{am:makeUrl('Players_details', 'Profile', author)}">
                                    <xsl:value-of select="author"/>
                                </a>
                            </p>
                        </div>
                        <div class="col-sm-2">
                            <p>
                                <xsl:if test="am:dateDiff(modified_at, $param/notification) &lt; 0">
                                    <xsl:attribute name="class">highlighted</xsl:attribute>
                                </xsl:if>
                                <xsl:value-of select="am:dateTime(modified_at, $param/timezone)"/>
                            </p>
                        </div>
                        <div class="col-sm-1"><p><xsl:value-of select="state"/></p></div>
                        <div class="col-sm-2">
                            <p>
                                <xsl:if test="$param/edit_all_card = 'yes' or ($param/edit_own_card = 'yes' and ($param/player_name = author))">
                                    <button class="button-icon" type="submit" name="edit_concept" value="{id}" title="Edit">
                                        <span class="glyphicon glyphicon-pencil"/>
                                    </button>
                                </xsl:if>
                                <xsl:if test="$param/delete_all_card = 'yes' or ($param/delete_own_card = 'yes' and ($param/player_name = author))">
                                    <button class="button-icon" type="submit" name="delete_concept" value="{id}" title="Delete">
                                        <span class="glyphicon glyphicon-trash"/>
                                    </button>
                                </xsl:if>
                            </p>
                        </div>
                    </div>
                </xsl:for-each>
            </div>

            <xsl:if test="$param/is_logged_in = 'yes'">
                <div class="filters">
                    <!-- lower navigation -->
                    <xsl:copy-of select="am:lowerNavigation(
                        $param/page_count, $param/current_page, 'concepts', 'Concepts'
                    )"/>
                </div>
            </xsl:if>

            <input type="hidden" name="concepts_current_page" value="{$param/current_page}"/>
            <input type="hidden" name="concepts_current_order" value="{$param/current_order}"/>
            <input type="hidden" name="concepts_current_condition" value="{$param/current_condition}"/>
        </div>
    </xsl:template>


    <xsl:template match="section[. = 'Concepts_new']">
        <xsl:variable name="param" select="$params/concepts_new"/>

        <div class="concepts-edit">

            <div class="skin-text">
                <a class="button button-icon" href="{am:makeUrl('Concepts')}">
                    <span class="glyphicon glyphicon-arrow-left"/>
                </a>
                <button class="button-icon" type="submit" name="create_concept" title="Create card">
                    <span class="glyphicon glyphicon-ok"/>
                </button>

                <hr/>

                <div class="limit">
                    <div class="row">
                        <div class="col-xs-3">Name</div>
                        <div class="col-xs-9">
                            <input type="text" name="name" maxlength="64" size="35">
                                <xsl:if test="$param/stored = 'yes'">
                                    <xsl:attribute name="value">
                                        <xsl:value-of select="$param/data/name"/>
                                    </xsl:attribute>
                                </xsl:if>
                            </input>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-3">Rarity</div>
                        <div class="col-xs-9">
                            <xsl:variable name="rarities">
                                <class name="Common"/>
                                <class name="Uncommon"/>
                                <class name="Rare"/>
                            </xsl:variable>

                            <select name="rarity">
                                <xsl:for-each select="exsl:node-set($rarities)/*">
                                    <option value="{@name}">
                                        <xsl:if test="$param/stored = 'yes' and $param/data/rarity = @name">
                                            <xsl:attribute name="selected">selected</xsl:attribute>
                                        </xsl:if>
                                        <xsl:value-of select="@name"/>
                                    </option>
                                </xsl:for-each>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-3">Cost (B/G/R)</div>
                        <div class="col-xs-9">
                            <input type="text" name="bricks" maxlength="2" size="2">
                                <xsl:attribute name="value">
                                    <xsl:choose>
                                        <xsl:when test="$param/stored = 'yes'">
                                            <xsl:value-of select="$param/data/bricks"/>
                                        </xsl:when>
                                        <xsl:otherwise>0</xsl:otherwise>
                                    </xsl:choose>
                                </xsl:attribute>
                            </input>
                            <input type="text" name="gems" maxlength="2" size="2">
                                <xsl:attribute name="value">
                                    <xsl:choose>
                                        <xsl:when test="$param/stored = 'yes'">
                                            <xsl:value-of select="$param/data/gems"/>
                                        </xsl:when>
                                        <xsl:otherwise>0</xsl:otherwise>
                                    </xsl:choose>
                                </xsl:attribute>
                            </input>
                            <input type="text" name="recruits" maxlength="2" size="2">
                                <xsl:attribute name="value">
                                    <xsl:choose>
                                        <xsl:when test="$param/stored = 'yes'">
                                            <xsl:value-of select="$param/data/recruits"/>
                                        </xsl:when>
                                        <xsl:otherwise>0</xsl:otherwise>
                                    </xsl:choose>
                                </xsl:attribute>
                            </input>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-3">Keywords</div>
                        <div class="col-xs-9">
                            <input type="text" name="keywords" maxlength="100" size="35">
                                <xsl:if test="$param/stored = 'yes'">
                                    <xsl:attribute name="value">
                                        <xsl:value-of select="$param/data/keywords"/>
                                    </xsl:attribute>
                                </xsl:if>
                            </input>
                        </div>
                    </div>
                </div>
                <p>Effect</p>
                <xsl:copy-of select="am:bbCodeButtons('effect')"/>
                <textarea name="effect" rows="6" cols="50">
                    <xsl:if test="$param/stored = 'yes'">
                        <xsl:value-of select="$param/data/effect"/>
                    </xsl:if>
                </textarea>
                <p>Note</p>
                <xsl:copy-of select="am:bbCodeButtons('note')"/>
                <textarea name="note" rows="6" cols="50">
                    <xsl:if test="$param/stored = 'yes'">
                        <xsl:value-of select="$param/data/note"/>
                    </xsl:if>
                </textarea>
            </div>
        </div>

    </xsl:template>


    <xsl:template match="section[. = 'Concepts_edit']">
        <xsl:variable name="param" select="$params/concepts_edit"/>

        <div class="concepts-edit">

            <div class="skin-text">
                <div>
                    <a class="button button-icon" href="{am:makeUrl('Concepts')}">
                        <span class="glyphicon glyphicon-arrow-left"/>
                    </a>
                    <a class="button button-icon" href="{am:makeUrl('Concepts_details', 'current_concept', $param/data/id)}" title="Details">
                        <span class="glyphicon glyphicon-zoom-in"/>
                    </a>
                    <xsl:if test="$param/data/author = $param/player_name">
                        <button class="button-icon" type="submit" name="save_concept" title="Save">
                            <span class="glyphicon glyphicon-ok"/>
                        </button>
                    </xsl:if>
                    <xsl:if test="$param/edit_all_card = 'yes'">
                        <button class="button-icon" type="submit" name="save_concept_special" title="Special save">
                            <span class="glyphicon glyphicon-check"/>
                        </button>
                    </xsl:if>
                    <xsl:if test="$param/delete_all_card = 'yes' or ($param/delete_own_card = 'yes' and $param/data/author = $param/player_name)">
                        <xsl:choose>
                            <xsl:when test="$param/delete = 'no'">
                                <button class="button-icon" type="submit" name="delete_concept" value="{$param/data/id}" title="Delete">
                                    <span class="glyphicon glyphicon-trash"/>
                                </button>
                            </xsl:when>
                            <xsl:otherwise>
                                <button class="button-icon marked_button" type="submit" name="delete_concept_confirm" title="Confirm delete">
                                    <span class="glyphicon glyphicon-trash"/>
                                </button>
                            </xsl:otherwise>
                        </xsl:choose>
                    </xsl:if>
                </div>
                <hr/>

                <div class="card-preview">
                    <xsl:copy-of select="am:cardString($param/data, $param/card_old_look)"/>
                </div>
                <div class="limit">
                    <div class="row">
                        <div class="col-xs-3">Name</div>
                        <div class="col-xs-9">
                            <input type="text" name="name" maxlength="64" size="35" value="{$param/data/name}"/>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-3">Rarity</div>
                        <div class="col-xs-9">
                            <xsl:variable name="rarities">
                                <class name="Common"/>
                                <class name="Uncommon"/>
                                <class name="Rare"/>
                            </xsl:variable>

                            <select name="rarity">
                                <xsl:for-each select="exsl:node-set($rarities)/*">
                                    <option value="{@name}">
                                        <xsl:if test="$param/data/rarity = @name">
                                            <xsl:attribute name="selected">selected</xsl:attribute>
                                        </xsl:if>
                                        <xsl:value-of select="@name"/>
                                    </option>
                                </xsl:for-each>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-3">Cost (B/G/R)</div>
                        <div class="col-xs-9">
                            <input type="text" name="bricks" maxlength="2" size="2" value="{$param/data/bricks}"/>
                            <input type="text" name="gems" maxlength="2" size="2" value="{$param/data/gems}"/>
                            <input type="text" name="recruits" maxlength="2" size="2" value="{$param/data/recruits}"/>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-3">Keywords</div>
                        <div class="col-xs-9">
                            <input type="text" name="keywords" maxlength="100" size="35" value="{$param/data/keywords}"/>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-3">State</div>
                        <div class="col-xs-9">
                            <xsl:variable name="states">
                                <class name="waiting"/>
                                <class name="rejected"/>
                                <class name="interesting"/>
                                <class name="implemented"/>
                            </xsl:variable>

                            <select name="state">
                                <xsl:if test="$param/edit_all_card = 'no'">
                                    <xsl:attribute name="disabled">disabled</xsl:attribute>
                                </xsl:if>
                                <xsl:for-each select="exsl:node-set($states)/*">
                                    <option value="{@name}">
                                        <xsl:if test="$param/data/state = @name">
                                            <xsl:attribute name="selected">selected</xsl:attribute>
                                        </xsl:if>
                                        <xsl:value-of select="@name"/>
                                    </option>
                                </xsl:for-each>
                            </select>
                        </div>
                    </div>
                </div>
                <p>Card picture</p>
                <div>
                    <input type="file" name="concept_image_file"/>
                    <button class="button-icon" type="submit" name="upload_concept_image" value="{$param/data/id}" title="Upload file">
                        <span class="glyphicon glyphicon-upload"/>
                    </button>
                    <button class="button-icon" type="submit" name="clear_concept_image" value="{$param/data/id}" title="Clear image">
                        <span class="glyphicon glyphicon-trash"/>
                    </button>
                </div>
                <p>Effect</p>
                <xsl:copy-of select="am:bbCodeButtons('effect')"/>
                <textarea name="effect" rows="6" cols="50">
                    <xsl:value-of select="$param/data/effect"/>
                </textarea>
                <p>Note</p>
                <xsl:copy-of select="am:bbCodeButtons('note')"/>
                <textarea name="note" rows="6" cols="50">
                    <xsl:value-of select="$param/data/note"/>
                </textarea>
            </div>

            <input type="hidden" name="current_concept" value="{$param/data/id}"/>
        </div>

    </xsl:template>


    <xsl:template match="section[. = 'Concepts_details']">
        <xsl:variable name="param" select="$params/concepts_details"/>

        <div class="concepts-edit">

            <div class="skin-text">
                <div>
                    <a class="button button-icon" href="{am:makeUrl('Concepts')}">
                        <span class="glyphicon glyphicon-arrow-left"/>
                    </a>

                    <xsl:if test="$param/edit_all_card = 'yes' or ($param/edit_own_card = 'yes' and ($param/player_name = author))">
                        <button class="button-icon" type="submit" name="edit_concept" value="{$param/data/id}" title="Edit">
                            <span class="glyphicon glyphicon-pencil"/>
                        </button>
                    </xsl:if>

                    <xsl:choose>
                        <xsl:when test="$param/discussion = 0 and $param/create_thread = 'yes'">
                            <button class="button-icon" type="submit" name="find_concept_thread" title="Start discussion">
                                <span class="glyphicon glyphicon-comment"/>
                            </button>
                        </xsl:when>
                        <xsl:when test="$param/discussion &gt; 0">
                            <a class="button button-icon" href="{am:makeUrl('Forum_thread', 'current_thread', $param/discussion, 'CurrentPage', 0)}" title="View discussion">
                                <span class="glyphicon glyphicon-comment"/>
                            </a>
                        </xsl:when>
                    </xsl:choose>
                </div>

                <hr/>

                <div class="card-preview">
                    <xsl:copy-of select="am:cardString($param/data, $param/card_old_look)"/>
                </div>
                <div class="limit">
                    <div class="row">
                        <div class="col-xs-6">Author</div>
                        <div class="col-xs-6">
                            <a class="profile" href="{am:makeUrl('Players_details', 'Profile', $param/data/author)}">
                                <xsl:value-of select="$param/data/author"/>
                            </a>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-6">Id</div>
                        <div class="col-xs-6">
                            <xsl:value-of select="$param/data/id"/>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-6">Name</div>
                        <div class="col-xs-6">
                            <xsl:value-of select="$param/data/name"/>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-6">Rarity</div>
                        <div class="col-xs-6">
                            <xsl:value-of select="$param/data/rarity"/>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-6">Keywords</div>
                        <div class="col-xs-6">
                            <xsl:value-of select="$param/data/keywords"/>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-6">State</div>
                        <div class="col-xs-6">
                            <xsl:value-of select="$param/data/state"/>
                        </div>
                    </div>
                </div>
                <p>BB code</p>
                <div>
                    <input type="text" name="bb_code" maxlength="64" size="25" value="[concept={$param/data/id}]{$param/data/name}[/concept]" title="BB code"/>
                </div>
                <p>Effect</p>
                <div>
                    <xsl:value-of select="am:bbCodeParse($param/data/effect)" disable-output-escaping="yes"/>
                </div>
                <p>Note</p>
                <div class="note">
                    <xsl:value-of select="am:bbCodeParse($param/data/note)" disable-output-escaping="yes"/>
                </div>
                <div class="clear-floats"/>
            </div>
            <input type="hidden" name="current_concept" value="{$param/data/id}"/>
        </div>

    </xsl:template>


</xsl:stylesheet>
