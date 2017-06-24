<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
                xmlns="http://www.w3.org/1999/xhtml"
                xmlns:am="http://arcomage.net"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:date="http://exslt.org/dates-and-times"
                xmlns:exsl="http://exslt.org/common"
                xmlns:func="http://exslt.org/functions"
                xmlns:php="http://php.net/xsl"
                xmlns:str="http://exslt.org/strings"
                extension-element-prefixes="date func php str">
    <xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes"
                doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN"
                doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"/>


    <!-- date:format-date(string, string) -->
    <xsl:include href="date.format-date.function.xsl"/>

    <!-- date:difference(string, string) -->
    <xsl:include href="date.difference.function.xsl"/>


    <func:function name="am:urlEncode">
        <xsl:param name="string" as="xs:string"/>
        <func:result select="str:encode-uri($string, true())"/>
    </func:function>


    <func:function name="am:makeUrl">
        <xsl:param name="location"/>
        <xsl:param name="key1" select="''"/>
        <xsl:param name="val1" select="''"/>
        <xsl:param name="key2" select="''"/>
        <xsl:param name="val2" select="''"/>
        <xsl:param name="key3" select="''"/>
        <xsl:param name="val3" select="''"/>
        <xsl:param name="key4" select="''"/>
        <xsl:param name="val4" select="''"/>
        <func:result select="php:functionString('Util\Xslt::makeUrl', $location, $key1, $val1, $key2, $val2, $key3, $val3, $key4, $val4)"/>
    </func:function>


    <func:function name="am:textEncode">
        <xsl:param name="text" as="xs:string"/>

        <!-- change newlines into html paragraphs -->
        <xsl:variable name="lines" select="str:split($text, '&#10;')"/>
        <xsl:variable name="output">
            <xsl:for-each select="$lines">
                <!-- change urls into html hyperlinks -->
                <xsl:variable name="words" select="str:split(text(), ' ')"/>
                <xsl:for-each select="$words">
                    <xsl:if test="position() != 1">
                        <xsl:text> </xsl:text>
                    </xsl:if>
                    <xsl:choose>
                        <xsl:when test="contains(text(), 'http://')">
                            <xsl:value-of select="substring-before(text(), 'http://')"/>
                            <a href="http://{substring-after(text(), 'http://')}">
                                http://<xsl:value-of select="substring-after(text(), 'http://')"/>
                            </a>
                        </xsl:when>
                        <xsl:when test="contains(text(), 'https://')">
                            <xsl:value-of select="substring-before(text(), 'https://')"/>
                            <a href="https://{substring-after(text(), 'https://')}">
                                https://<xsl:value-of select="substring-after(text(), 'https://')"/>
                            </a>
                        </xsl:when>
                        <xsl:otherwise>
                            <xsl:value-of select="text()"/>
                        </xsl:otherwise>
                    </xsl:choose>
                </xsl:for-each>
                <br/>
            </xsl:for-each>
        </xsl:variable>

        <func:result select="$output"/>
    </func:function>


    <func:function name="am:min">
        <xsl:param name="num1" as="xs:integer"/>
        <xsl:param name="num2" as="xs:integer"/>
        <func:result>
            <xsl:choose>
                <xsl:when test="$num1 &lt; $num2">
                    <xsl:value-of select="$num1"/>
                </xsl:when>
                <xsl:otherwise>
                    <xsl:value-of select="$num2"/>
                </xsl:otherwise>
            </xsl:choose>
        </func:result>
    </func:function>


    <func:function name="am:max">
        <xsl:param name="num1" as="xs:integer"/>
        <xsl:param name="num2" as="xs:integer"/>
        <func:result>
            <xsl:choose>
                <xsl:when test="$num1 &gt; $num2">
                    <xsl:value-of select="$num1"/>
                </xsl:when>
                <xsl:otherwise>
                    <xsl:value-of select="$num2"/>
                </xsl:otherwise>
            </xsl:choose>
        </func:result>
    </func:function>


    <func:function name="am:dateTime">
        <xsl:param name="datetime" as="xs:string"/>
        <xsl:param name="timezone" as="xs:string" select="'+0'"/>
        <xsl:variable name="date" select="str:replace($datetime, ' ', 'T')"/>
        <xsl:variable name="zone" select="concat('Etc/GMT', str:replace(str:replace(str:replace($timezone, '+', '*'), '-', '+'), '*', '-'))"/>
        <func:result>
            <span data-timestamp="{php:functionString('Util\Xslt::zoneTime', $date)}">
                <xsl:value-of select="php:functionString('Util\Xslt::zoneTime', $date, $zone, 'H:i, j. M, Y')"/>
            </span>
        </func:result>
    </func:function>


    <func:function name="am:formatDate">
        <xsl:param name="date" as="xs:string"/>
        <func:result select="date:format-date($date, 'd. MMM, yyyy')"/>
    </func:function>


    <func:function name="am:lowercase">
        <xsl:param name="string" as="xs:string"/>
        <xsl:variable name="lowercase" select="'abcdefghijklmnopqrstuvwxyz'"/>
        <xsl:variable name="uppercase" select="'ABCDEFGHIJKLMNOPQRSTUVWXYZ'"/>
        <func:result select="translate($string, $uppercase, $lowercase)"/>
    </func:function>


    <func:function name="am:bbCodeParse">
        <xsl:param name="content" as="xs:string"/>
        <func:result select="php:functionString('Util\Encode::bbDecode', $content)"/>
    </func:function>

    <func:function name="am:bbCodeParseExtended">
        <xsl:param name="content" as="xs:string"/>
        <func:result select="php:functionString('Util\Encode::bbDecode', $content, 'true')"/>
    </func:function>

    <func:function name="am:bbCodeButtons">
        <xsl:param name="target" as="xs:string"/>
        <xsl:variable name="buttons">
            <div id="{$target}" class="bb-code-buttons">
                <button type="button" name="bold" title="Bold - [b]text[/b]">B</button>
                <button type="button" name="italics" title="Italics - [i]text[/i]">I</button>
                <button type="button" name="link" title="Internal hyperlink - [link=?location=...]text[/link]">
                    <xsl:text>Link</xsl:text>
                </button>
                <button type="button" name="url" title="External hyperlink - [url=http://...]text[/url]">Url</button>
                <button type="button" name="quote" title="Quote - [quote=name]text[/quote]">Quote</button>
                <button type="button" name="card" title="Card - [card=id/] or [card=id]text[/card]">Card</button>
                <button type="button" name="keyword" title="Keyword - [keyword]name[/keyword]">Keyword</button>
                <button type="button" name="concept" title="Concept - [concept=id]text[/concept]">Concept</button>
            </div>
        </xsl:variable>
        <func:result select="exsl:node-set($buttons)"/>
    </func:function>

    <func:function name="am:dateDiff">
        <xsl:param name="datetime1" as="xs:string"/>
        <xsl:param name="datetime2" as="xs:string"/>

        <func:result select="date:seconds(date:difference(str:replace($datetime1, ' ', 'T'), str:replace($datetime2, ' ', 'T')))"/>
    </func:function>


    <func:function name="am:upperNavigation">
        <xsl:param name="pageCount" as="xs:integer"/>
        <xsl:param name="current" as="xs:integer"/>
        <xsl:param name="buttonName" as="xs:string"/>

        <xsl:variable name="output">
            <xsl:variable name="navButtonName" select="concat($buttonName, '_select_page')" />

            <!-- previous -->
            <button class="button-icon" type="submit" name="{$navButtonName}" value="{am:max($current - 1, 0)}">
                <xsl:if test="$current = 0">
                    <xsl:attribute name="disabled">disabled</xsl:attribute>
                </xsl:if>
                <span class="glyphicon glyphicon-chevron-left"/>
            </button>

            <!-- first -->
            <button class="button-icon" type="submit" name="{$navButtonName}" value="0">
                <xsl:if test="$current = 0">
                    <xsl:attribute name="disabled">disabled</xsl:attribute>
                </xsl:if>
                <span class="glyphicon glyphicon-step-backward"/>
            </button>

            <xsl:if test="$pageCount &gt; 0">
                <!-- page selection -->
                <xsl:for-each select="str:split(am:numbers(am:max($current - 5, 0), am:min($current + 5, am:max($pageCount - 1, 0))), ',')">
                    <button class="button-navigation" type="submit" name="{$navButtonName}" value="{text()}">
                        <xsl:if test="$current = .">
                            <xsl:attribute name="disabled">disabled</xsl:attribute>
                        </xsl:if>
                        <xsl:value-of select="text()"/>
                    </button>
                </xsl:for-each>
            </xsl:if>

            <!-- last -->
            <button class="button-icon" type="submit" name="{$navButtonName}" value="{am:max($pageCount - 1, 0)}">
                <xsl:if test="$current = am:max($pageCount - 1, 0)">
                    <xsl:attribute name="disabled">disabled</xsl:attribute>
                </xsl:if>
                <span class="glyphicon glyphicon-step-forward"/>
            </button>

            <!-- next -->
            <button class="button-icon" type="submit" name="{$navButtonName}" value="{am:min($current + 1, $pageCount - 1)}">
                <xsl:if test="$current = am:max($pageCount - 1, 0)">
                    <xsl:attribute name="disabled">disabled</xsl:attribute>
                </xsl:if>
                <span class="glyphicon glyphicon-chevron-right"/>
            </button>
        </xsl:variable>

        <func:result select="$output"/>
    </func:function>


    <func:function name="am:lowerNavigation">
        <xsl:param name="pageCount" as="xs:integer"/>
        <xsl:param name="current" as="xs:integer"/>
        <xsl:param name="arrowButton" as="xs:string"/>
        <xsl:param name="backButton" as="xs:string"/>

        <xsl:variable name="output">
            <xsl:variable name="navButtonName" select="concat($arrowButton, '_select_page')" />

            <!-- arrow buttons selector -->
            <button class="button-icon" type="submit" name="{$navButtonName}" value="{am:max($current - 1, 0)}">
                <xsl:if test="$current = 0">
                    <xsl:attribute name="disabled">disabled</xsl:attribute>
                </xsl:if>
                <span class="glyphicon glyphicon-chevron-left"/>
            </button>

            <button class="button-icon" type="submit" name="back_to_top" value="{$backButton}">
                <span class="glyphicon glyphicon-triangle-top"/>
            </button>

            <button class="button-icon" type="submit" name="{$navButtonName}" value="{am:min($current + 1, $pageCount - 1)}">
                <xsl:if test="$current = am:max($pageCount - 1, 0)">
                    <xsl:attribute name="disabled">disabled</xsl:attribute>
                </xsl:if>
                <span class="glyphicon glyphicon-chevron-right"/>
            </button>
        </xsl:variable>

        <func:result select="$output"/>
    </func:function>


    <func:function name="am:pageList">
        <xsl:param name="count" as="xs:integer"/>
        <func:result select="str:split(am:numbers(0, $count - 1), ',')"/>
    </func:function>


    <func:function name="am:numbers">
        <xsl:param name="from" as="xs:integer"/>
        <xsl:param name="to" as="xs:integer"/>

        <func:result select="php:functionString('Util\Xslt::numbers', $from, $to)"/>
    </func:function>


    <func:function name="am:simpleNavigation">
        <xsl:param name="location" as="xs:string"/>
        <xsl:param name="pageType" as="xs:string"/>
        <xsl:param name="current" as="xs:integer"/>
        <xsl:param name="pageCount" as="xs:integer"/>

        <xsl:variable name="output">
            <xsl:choose>
                <xsl:when test="$current &gt; 0">
                    <a class="button button-icon" href="{am:makeUrl($location, $pageType, am:max($current - 1, 0))}">
                        <span class="glyphicon glyphicon-chevron-left"/>
                    </a>
                </xsl:when>
                <xsl:otherwise>
                    <span class="disabled">
                        <span class="glyphicon glyphicon-chevron-left"/>
                    </span>
                </xsl:otherwise>
            </xsl:choose>

            <xsl:choose>
                <xsl:when test="$current &gt; 0">
                    <a class="button button-icon" href="{am:makeUrl($location, $pageType, 0)}">
                        <span class="glyphicon glyphicon-step-backward"/>
                    </a>
                </xsl:when>
                <xsl:otherwise>
                    <span class="disabled">
                        <span class="glyphicon glyphicon-step-backward"/>
                    </span>
                </xsl:otherwise>
            </xsl:choose>

            <xsl:for-each
                    select="str:split(am:numbers(am:max($current - 5, 0), am:min($current + 5, am:max($pageCount - 1, 0))), ',')">
                <xsl:choose>
                    <xsl:when test="$current != .">
                        <a class="button button-icon" href="{am:makeUrl($location, $pageType, text())}">
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

            <xsl:choose>
                <xsl:when test="$current &lt; am:max($pageCount - 1, 0)">
                    <a class="button button-icon" href="{am:makeUrl($location, $pageType, am:max($pageCount - 1, 0))}">
                        <span class="glyphicon glyphicon-step-forward"/>
                    </a>
                </xsl:when>
                <xsl:otherwise>
                    <span class="disabled">
                        <span class="glyphicon glyphicon-step-forward"/>
                    </span>
                </xsl:otherwise>
            </xsl:choose>

            <xsl:choose>
                <xsl:when test="$current &lt; am:max($pageCount - 1, 0)">
                    <a class="button button-icon" href="{am:makeUrl($location, $pageType, am:min($current + 1, am:max($pageCount - 1, 0)))}">
                        <span class="glyphicon glyphicon-chevron-right"/>
                    </a>
                </xsl:when>
                <xsl:otherwise>
                    <span class="disabled">
                        <span class="glyphicon glyphicon-chevron-right"/>
                    </span>
                </xsl:otherwise>
            </xsl:choose>
        </xsl:variable>

        <func:result select="$output"/>
    </func:function>


    <func:function name="am:htmlSelectBox">
        <!-- generates html select box -->
        <xsl:param name="name" as="xs:string"/>
        <xsl:param name="current" as="xs:string"/>
        <xsl:param name="staticValues" as="xs:node-set"/>
        <xsl:param name="dynamicValues" as="xs:node-set"/>
        <xsl:param name="title" as="xs:string" select="''"/>

        <xsl:variable name="converted">
            <xsl:for-each select="exsl:node-set($dynamicValues)/*">
                <value name="{text()}" value="{text()}"/>
            </xsl:for-each>
        </xsl:variable>

        <xsl:variable name="values" select="exsl:node-set($staticValues) | exsl:node-set($converted)"/>

        <xsl:variable name="output">
            <select name="{$name}">
                <xsl:if test="$current != 'none'">
                    <xsl:attribute name="class">filter-active</xsl:attribute>
                </xsl:if>
                <xsl:if test="$title != ''">
                    <xsl:attribute name="title">
                        <xsl:value-of select="$title"/>
                    </xsl:attribute>
                </xsl:if>
                <xsl:for-each select="$values/*">
                    <option value="{@value}">
                        <xsl:if test="$current = @value">
                            <xsl:attribute name="selected">selected</xsl:attribute>
                        </xsl:if>
                        <xsl:value-of select="@name"/>
                    </option>
                </xsl:for-each>
            </select>
        </xsl:variable>

        <func:result select="$output"/>
    </func:function>


    <func:function name="am:cardEffect">
        <xsl:param name="effect" as="xs:string"/>
        <xsl:param name="option" as="xs:string" select="''"/>

        <xsl:variable name="result">
            <xsl:choose>
                <!-- case 1: plain text only (used in title elements) -->
                <xsl:when test="$option = 'plain_text'">
                    <xsl:value-of select="$effect" />
                </xsl:when>
                <!-- case 2: standard case -->
                <xsl:otherwise>
                    <!-- ad-hoc html entity corrections -->
                    <xsl:variable name="replace">
                        <from> &lt; </from>
                        <to> &amp;lt; </to>
                        <from> &gt; </from>
                        <to> &amp;gt; </to>
                        <from> &lt;= </from>
                        <to> &amp;lt;= </to>
                        <from> &gt;= </from>
                        <to> &amp;gt;= </to>
                    </xsl:variable>
                    <xsl:value-of select="str:replace(
                        $effect, exsl:node-set($replace)/*[local-name()='from'],
                        exsl:node-set($replace)/*[local-name()='to']
                    )" />
                </xsl:otherwise>
            </xsl:choose>
        </xsl:variable>

        <func:result select="php:functionString('Util\Encode::cardDecode', $result, $option)"/>
    </func:function>


    <func:function name="am:fileName">
        <xsl:param name="name" as="xs:string"/>
        <func:result select="am:lowercase(str:replace($name, ' ', '_'))"/>
    </func:function>


    <func:function name="am:cardString">
        <xsl:param name="card"/>
        <xsl:param name="oldLook" select="'no'"/>
        <xsl:param name="insignias" select="'yes'"/>
        <xsl:param name="foils" select="''"/>
        <xsl:param name="new" select="false()"/>
        <xsl:param name="revealed" select="false()"/>
        <xsl:param name="keywordsCount" select="false()"/>

        <xsl:variable name="result">

            <xsl:variable name="rarity">
                <xsl:choose>
                    <xsl:when test="$card/rarity = 'Common'">common</xsl:when>
                    <xsl:when test="$card/rarity = 'Uncommon'">uncommon</xsl:when>
                    <xsl:when test="$card/rarity = 'Rare'">rare</xsl:when>
                    <xsl:otherwise>no-rarity</xsl:otherwise>
                </xsl:choose>
            </xsl:variable>
            <xsl:variable name="type">
                <xsl:choose>
                    <xsl:when test="$card/bricks = 0 and $card/gems = 0 and $card/recruits = 0">
                        <xsl:text> zero-cost</xsl:text>
                    </xsl:when>
                    <xsl:when test="$card/bricks &gt; 0 and $card/gems = 0 and $card/recruits = 0">
                        <xsl:text> bricks-cost</xsl:text>
                    </xsl:when>
                    <xsl:when test="$card/bricks = 0 and $card/gems &gt; 0 and $card/recruits = 0">
                        <xsl:text> gems-cost</xsl:text>
                    </xsl:when>
                    <xsl:when test="$card/bricks = 0 and $card/gems = 0 and $card/recruits &gt; 0">
                        <xsl:text> recruits-cost</xsl:text>
                    </xsl:when>
                    <xsl:otherwise>
                        <xsl:text> mixed-cost</xsl:text>
                    </xsl:otherwise>
                </xsl:choose>
            </xsl:variable>
            <xsl:variable name="bgImage">
                <xsl:if test="$oldLook = 'no'"> with-bg-image</xsl:if>
            </xsl:variable>
            <xsl:variable name="foil">
                <xsl:if test="contains(concat(',', $foils, ','), concat(',', $card/id, ','))"> foil</xsl:if>
            </xsl:variable>

            <div class="card {$rarity}{$type}{$bgImage}{$foil}">
                <!-- display the cost (spheres with numbers in the center) -->
                <div class="card-top">
                    <xsl:choose>
                        <xsl:when test="$card/bricks = 0 and $card/gems = 0 and $card/recruits = 0">
                            <span class="icon-zero">0</span>
                        </xsl:when>
                        <xsl:otherwise>
                            <xsl:if test="$card/bricks &gt; 0">
                                <span class="icon-bricks">
                                    <xsl:value-of select="$card/bricks"/>
                                </span>
                            </xsl:if>
                            <xsl:if test="$card/gems &gt; 0">
                                <span class="icon-gems">
                                    <xsl:value-of select="$card/gems"/>
                                </span>
                            </xsl:if>
                            <xsl:if test="$card/recruits &gt; 0">
                                <span class="icon-recruits">
                                    <xsl:value-of select="$card/recruits"/>
                                </span>
                            </xsl:if>
                        </xsl:otherwise>
                    </xsl:choose>

                    <xsl:choose>
                        <xsl:when test="$card/rarity = 'Common'"><span class="icon-rarity common">C</span></xsl:when>
                        <xsl:when test="$card/rarity = 'Uncommon'"><span class="icon-rarity uncommon">U</span></xsl:when>
                        <xsl:when test="$card/rarity = 'Rare'"><span class="icon-rarity rare">R</span></xsl:when>
                    </xsl:choose>
                </div>

                <!-- card's image and its border (colored via CSS according to class) -->
                <div class="card-image">
                    <img width="80" height="60" class="img-rounded" alt="{$card/name}">
                        <xsl:choose>
                            <xsl:when test="$card/picture">
                                <xsl:attribute name="src">
                                    <xsl:value-of select="$card/picture"/>
                                </xsl:attribute>
                            </xsl:when>
                            <xsl:otherwise>
                                <xsl:attribute name="src">
                                    <xsl:text>img/cards/card_</xsl:text>
                                    <xsl:value-of select="$card/id"/>
                                    <xsl:text>.png?v=</xsl:text>
                                    <xsl:value-of select="$card/modified"/>
                                </xsl:attribute>
                            </xsl:otherwise>
                        </xsl:choose>
                    </img>
                    <xsl:if test="$revealed or $new">
                        <div class="mini-flags">
                            <xsl:if test="$new">
                                <img src="img/card/new_miniflag.png" width="12" height="12" alt="new card" title="New card"/>
                            </xsl:if>
                            <xsl:if test="$revealed">
                                <img src="img/card/revealed_miniflag.png" width="12" height="12" alt="revealed" title="Revealed"/>
                            </xsl:if>
                        </div>
                    </xsl:if>
                </div>

                <!-- name -->
                <h5><xsl:value-of select="$card/name"/></h5>

                <!-- keywords -->
                <xsl:choose>
                    <xsl:when test="$card/picture">
                        <p class="keywords"><b><xsl:value-of select="$card/keywords"/></b></p>
                    </xsl:when>
                    <xsl:otherwise>
                        <xsl:variable name="descriptions" select="document('../xml/keywords.xml')/am:keywords"/>
                        <p class="keywords">
                            <xsl:for-each select="str:split($card/keywords, ',')">
                                <xsl:variable name="keywordName" select="."/>
                                <xsl:variable name="keyword" select="$descriptions/am:keyword[contains($keywordName, am:name)]"/>
                                <xsl:variable name="keywordDescription">
                                    <xsl:if test="$keyword/am:basic_gain &gt; 0 or $keyword/am:bonus_gain &gt; 0">
                                        <xsl:text>Basic gain </xsl:text>
                                        <xsl:value-of select="$keyword/am:basic_gain"/>
                                        <xsl:text>, bonus gain </xsl:text>
                                        <xsl:value-of select="$keyword/am:bonus_gain"/>
                                        <xsl:if test="$keywordsCount">
                                            <xsl:text>, real gain when played </xsl:text>
                                            <xsl:for-each select="$keywordsCount/*">
                                                <xsl:if test="name = $keywordName">
                                                    <xsl:value-of select="$keyword/am:basic_gain + (count - 1) * $keyword/am:bonus_gain"/>
                                                </xsl:if>
                                            </xsl:for-each>
                                        </xsl:if>
                                        <xsl:text>, </xsl:text>
                                    </xsl:if>
                                    <xsl:value-of select="am:cardEffect($keyword/am:description, 'plain_text')"/>
                                </xsl:variable>
                                <xsl:choose>
                                    <xsl:when test="$insignias = 'yes'">
                                        <img class="insignia" src="img/insignias/{am:fileName($keyword/am:name)}.png"
                                             width="12" height="12" alt="{$keywordName}"
                                             title="{concat($keywordName, ' - ', $keywordDescription)}"/>
                                    </xsl:when>
                                    <xsl:otherwise>
                                        <b>
                                            <xsl:attribute name="title">
                                                <xsl:value-of select="$keywordDescription"/>
                                            </xsl:attribute>
                                            <xsl:value-of select="$keywordName"/>
                                            <xsl:text>.</xsl:text>
                                        </b>
                                    </xsl:otherwise>
                                </xsl:choose>
                            </xsl:for-each>
                        </p>
                    </xsl:otherwise>
                </xsl:choose>

                <!-- card effect -->
                <p class="effect">
                    <xsl:choose>
                        <xsl:when test="$card/picture">
                            <xsl:value-of select="am:bbCodeParse($card/effect)" disable-output-escaping="yes"/>
                        </xsl:when>
                        <xsl:otherwise>
                            <xsl:value-of select="am:cardEffect($card/effect)" disable-output-escaping="yes"/>
                        </xsl:otherwise>
                    </xsl:choose>
                </p>

            </div>
        </xsl:variable>
        <func:result select="exsl:node-set($result)"/>
    </func:function>


    <func:function name="am:cardFilters">
        <xsl:param name="keywordsList"/>
        <xsl:param name="levelsList"/>
        <xsl:param name="createdDates"/>
        <xsl:param name="modifiedDates"/>
        <xsl:param name="nameFilter" as="xs:string"/>
        <xsl:param name="rarityFilter" as="xs:string"/>
        <xsl:param name="keywordFilter" as="xs:string"/>
        <xsl:param name="costFilter" as="xs:string"/>
        <xsl:param name="advancedFilter" as="xs:string"/>
        <xsl:param name="supportFilter" as="xs:string"/>
        <xsl:param name="createdFilter" as="xs:string"/>
        <xsl:param name="modifiedFilter" as="xs:string"/>
        <xsl:param name="levelFilter" as="xs:string"/>
        <xsl:param name="sort" as="xs:string"/>

        <xsl:variable name="result">

            <!-- card name filter -->
            <input type="text" name="name_filter" maxlength="20" size="15" value="{$nameFilter}">
                <xsl:attribute name="title">
                    <xsl:text>search phrase for card name (CASE sensitive, type first letter as capital</xsl:text>
                    <xsl:text> if you want the card name to start with that letter)</xsl:text>
                </xsl:attribute>
            </input>

            <!-- card rarity filter -->
            <xsl:variable name="rarities">
                <value name="Common" value="Common"/>
                <value name="Uncommon" value="Uncommon"/>
                <value name="Rare" value="Rare"/>
                <value name="Any" value="none"/>
            </xsl:variable>
            <xsl:copy-of select="am:htmlSelectBox('rarity_filter', $rarityFilter, $rarities, '', 'rarity filter')"/>

            <!-- card keyword filter -->
            <xsl:variable name="keywords">
                <value name="No keyword filter" value="none"/>
                <value name="Any keyword" value="Any keyword"/>
                <value name="No keywords" value="No keywords"/>
            </xsl:variable>
            <xsl:copy-of select="am:htmlSelectBox(
                'keyword_filter', $keywordFilter, $keywords, $keywordsList, 'keyword filter'
            )"/>

            <!-- cost filter -->
            <xsl:variable name="costs">
                <value name="No cost filter" value="none"/>
                <value name="Bricks only" value="Red"/>
                <value name="Gems only" value="Blue"/>
                <value name="Recruits only" value="Green"/>
                <value name="Zero cost" value="Zero"/>
                <value name="Mixed cost" value="Mixed"/>
            </xsl:variable>
            <xsl:copy-of select="am:htmlSelectBox('cost_filter', $costFilter, $costs, '', 'cost filter')"/>

            <!-- advanced filter select menu - filters based upon appearance in card text -->
            <xsl:variable name="advanced">
                <value name="No adv. filter" value="none"/>
                <value name="Attack" value="Attack:"/>
                <value name="Discard" value="Discard "/>
                <value name="Replace" value="Replace "/>
                <value name="Reveal" value="Reveal"/>
                <value name="Summon" value="Summons"/>
                <value name="Evoke" value="Evoke"/>
                <value name="Steal" value="Steal"/>
                <value name="Production" value="Prod"/>
                <value name="Persistent" value="Replace a card in hand with self"/>
                <value name="Wall +" value="Wall: +"/>
                <value name="Wall -" value="Wall: -"/>
                <value name="Tower +" value="Tower: +"/>
                <value name="Tower -" value="Tower: -"/>
                <value name="Facilities +" value="Facilities: +"/>
                <value name="Facilities -" value="Facilities: -"/>
                <value name="Quarry +" value="Quarry: +"/>
                <value name="Quarry -" value="Quarry: -"/>
                <value name="Magic +" value="Magic: +"/>
                <value name="Magic -" value="Magic: -"/>
                <value name="Dungeon +" value="Dungeon: +"/>
                <value name="Dungeon -" value="Dungeon: -"/>
                <value name="Stock +" value="Stock: +"/>
                <value name="Stock -" value="Stock: -"/>
                <value name="Bricks +" value="Bricks: +"/>
                <value name="Bricks -" value="Bricks: -"/>
                <value name="Gems +" value="Gems: +"/>
                <value name="Gems -" value="Gems: -"/>
                <value name="Recruits +" value="Recruits: +"/>
                <value name="Recruits -" value="Recruits: -"/>
                <value name="Random resources" value="random resource"/>
            </xsl:variable>
            <xsl:copy-of select="am:htmlSelectBox(
                'advanced_filter', $advancedFilter, $advanced, '', 'advanced filter'
            )"/>

            <!-- support keyword filter -->
            <xsl:variable name="support">
                <value name="No support filter" value="none"/>
                <value name="Any keyword" value="Any keyword"/>
                <value name="No keywords" value="No keywords"/>
            </xsl:variable>
            <xsl:copy-of select="am:htmlSelectBox(
                'support_filter', $supportFilter, $support, $keywordsList, 'support keyword filter'
            )"/>

            <!-- creation date filter -->
            <xsl:variable name="created">
                <value name="No created filter" value="none"/>
            </xsl:variable>
            <xsl:copy-of select="am:htmlSelectBox(
                'created_filter', $createdFilter, $created, $createdDates, 'date created filter'
            )"/>

            <!-- modification date filter -->
            <xsl:variable name="modified">
                <value name="No modified filter" value="none"/>
            </xsl:variable>
            <xsl:copy-of select="am:htmlSelectBox(
                'modified_filter', $modifiedFilter, $modified, $modifiedDates, 'date modified filter'
            )"/>

            <!-- card level filter -->
            <xsl:variable name="level">
                <value name="No level filter" value="none"/>
            </xsl:variable>
            <xsl:copy-of select="am:htmlSelectBox(
                'level_filter', $levelFilter, $level, $levelsList, 'level filter'
            )"/>

            <!-- sorting options -->
            <xsl:variable name="cardSort">
                <value name="Sort by name" value="name"/>
                <value name="Sort by cost" value="cost"/>
            </xsl:variable>
            <xsl:copy-of select="am:htmlSelectBox('card_sort', $sort, $cardSort, '', 'sorting order')"/>
        </xsl:variable>
        <func:result select="exsl:node-set($result)"/>
    </func:function>


    <func:function name="am:renderDeck">
        <xsl:param name="cardList"/>
        <xsl:param name="oldLook" as="xs:string"/>
        <xsl:param name="insignias" as="xs:string"/>
        <xsl:param name="foils" as="xs:string"/>
        <xsl:param name="static" select="true()"/>

        <xsl:variable name="result">
            <div class="deck row">
                <!-- Common, Uncommon, Rare sections -->
                <xsl:for-each select="$cardList/*">
                    <div class="col-md-4">
                        <div class="skin-label top-level">
                            <xsl:variable name="rarity">
                                <xsl:choose>
                                    <xsl:when test="position() = 1">common</xsl:when>
                                    <xsl:when test="position() = 2">uncommon</xsl:when>
                                    <xsl:otherwise>rare</xsl:otherwise>
                                </xsl:choose>
                            </xsl:variable>
                            <p>
                                <xsl:attribute name="class">
                                    <xsl:text>deck-header </xsl:text>
                                    <xsl:value-of select="$rarity" />
                                </xsl:attribute>
                                <xsl:value-of select="$rarity" />
                            </p>
                            <div class="deck-cards">
                                <xsl:variable name="column" select="position()"/>
                                <xsl:variable name="cards" select="."/>
                                <!-- row counting hack -->
                                <xsl:for-each select="$cards/*[position() &lt;= 5]">
                                    <div class="row">
                                        <xsl:variable name="i" select="position()"/>
                                        <xsl:for-each select="$cards/*[position() &gt;= $i*3-2 and position() &lt;= $i*3]">
                                            <div class="col-xs-4">
                                                <xsl:if test="not($static)">
                                                    <xsl:attribute name="id">slot_<xsl:value-of select="(($i - 1) * 3) + position() + 15 * ($column - 1)"/></xsl:attribute>
                                                </xsl:if>

                                                <xsl:if test="not($static) and id &gt; 0">
                                                    <xsl:attribute name="data-remove-card"><xsl:value-of select="id"/></xsl:attribute>
                                                </xsl:if>

                                                <xsl:copy-of select="am:cardString(
                                                    current(), $oldLook, $insignias, $foils
                                                )"/>

                                                <xsl:if test="not($static) and id != 0">
                                                    <noscript>
                                                        <div>
                                                            <button class="button-icon" type="submit" name="return_card" value="{id}" title="Return">
                                                                <span class="glyphicon glyphicon-upload"/>
                                                            </button>
                                                        </div>
                                                    </noscript>
                                                </xsl:if>
                                            </div>
                                        </xsl:for-each>
                                    </div>
                                </xsl:for-each>
                            </div>
                        </div>
                    </div>
                </xsl:for-each>
            </div>
        </xsl:variable>
        <func:result select="exsl:node-set($result)"/>
    </func:function>


    <!-- game mode flags -->
    <func:function name="am:gameModeFlags">
        <xsl:param name="hiddenCards" as="xs:string"/>
        <xsl:param name="friendlyPlay" as="xs:string"/>
        <xsl:param name="longMode" as="xs:string"/>
        <xsl:param name="aiMode" as="xs:string" select="'no'"/>
        <xsl:param name="aiName" as="xs:string" select="''"/>

        <xsl:variable name="result">
            <xsl:if test="$hiddenCards = 'yes'">
                <img class="icon" src="img/blind.png" width="20" height="14" alt="Hidden cards" title="Hidden cards"/>
            </xsl:if>
            <xsl:if test="$friendlyPlay = 'yes'">
                <img class="icon" src="img/friendly_play.png" width="20" height="14" alt="Friendly play" title="Friendly play"/>
            </xsl:if>
            <xsl:if test="$longMode = 'yes'">
                <img class="icon" src="img/long_mode.png" width="20" height="14" alt="Long mode" title="Long mode"/>
            </xsl:if>
            <xsl:if test="$aiMode = 'yes'">
                <img class="icon" src="img/ai_mode.png" width="20" height="14" alt="AI mode" title="AI mode"/>
            </xsl:if>
            <xsl:if test="$aiName != ''">
                <img class="icon" src="img/ai_challenge.png" width="20" height="14" alt="AI challenge - {$aiName}" title="AI challenge - {$aiName}"/>
            </xsl:if>
        </xsl:variable>
        <func:result select="exsl:node-set($result)"/>
    </func:function>


</xsl:stylesheet>
