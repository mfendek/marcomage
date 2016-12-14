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


    <xsl:template match="section[. = 'Messages']">
        <xsl:variable name="param" select="$params/messages"/>

        <div id="message_section">

            <div class="row">
                <div class="col-md-6">
                    <!-- begin challenges -->

                    <div id="challenges" class="skin-label top-level">

                        <h3>Challenges</h3>

                        <xsl:if test="$param/deck_count = 0">
                            <p class="information-line warning">You need at least one ready deck to accept challenges.</p>
                        </xsl:if>

                        <xsl:if test="$param/free_slots = 0">
                            <p class="information-line warning">You cannot start any more games.</p>
                        </xsl:if>

                        <p>
                            <xsl:variable name="challengeSections">
                                <value name="incoming" value="Incoming"/>
                                <value name="outgoing" value="Outgoing"/>
                            </xsl:variable>
                            <xsl:for-each select="exsl:node-set($challengeSections)/*">
                                <a class="button" href="{am:makeUrl('Messages', 'challenges_subsection', @name, 'messages_subsection', $param/current_location)}">
                                    <xsl:if test="$param/current_subsection = @name">
                                        <xsl:attribute name="class">button pushed</xsl:attribute>
                                    </xsl:if>
                                    <xsl:value-of select="@value"/>
                                </a>
                            </xsl:for-each>
                        </p>

                        <!-- selected deck -->
                        <xsl:if test="($param/current_subsection = 'incoming') and ($param/deck_count &gt; 0)">
                            <p>
                                <xsl:text>Select deck </xsl:text>
                                <select name="AcceptDeck" size="1">
                                    <xsl:if test="$param/random_deck_option = 'yes'">
                                        <option value="{$param/random_deck}">select random</option>
                                    </xsl:if>
                                    <xsl:for-each select="$param/decks/*">
                                        <option value="{DeckID}">
                                            <xsl:value-of select="Deckname"/>
                                        </option>
                                    </xsl:for-each>
                                    <xsl:for-each select="$param/ai_challenges/*">
                                        <xsl:sort select="fullname" order="ascending"/>
                                        <option value="{name}">
                                            <xsl:value-of select="name"/>
                                        </option>
                                    </xsl:for-each>
                                </select>
                            </p>
                        </xsl:if>

                        <xsl:choose>
                            <xsl:when test="$param/challenges_count &gt; 0">
                                <div class="challenge_box">
                                    <xsl:for-each select="$param/challenges/*">
                                        <div class="skin-text">
                                            <xsl:choose>
                                                <xsl:when test="$param/current_subsection = 'incoming'">
                                                    <p>
                                                        <span>
                                                            <xsl:if test="Online = 'yes'">
                                                                <xsl:attribute name="class">p_online</xsl:attribute>
                                                            </xsl:if>
                                                            <a class="profile" href="{am:makeUrl('Players_details', 'Profile', Author)}">
                                                                <xsl:value-of select="Author"/>
                                                            </a>
                                                        </span>
                                                        <xsl:text> has challenged you on </xsl:text>
                                                        <span>
                                                            <xsl:value-of select="am:dateTime(Created, $param/timezone)"/>
                                                        </span>
                                                    </p>
                                                    <xsl:if test="Content != ''">
                                                        <div class="challenge_content">
                                                            <xsl:value-of select="am:bbCodeParseExtended(Content)" disable-output-escaping="yes"/>
                                                        </div>
                                                    </xsl:if>
                                                    <p>
                                                        <xsl:if test="($param/deck_count &gt; 0) and ($param/free_slots &gt; 0) and ($param/accept_challenges = 'yes')">
                                                            <button type="submit" name="accept_challenge" value="{GameID}">
                                                                Accept
                                                            </button>
                                                        </xsl:if>
                                                        <button type="submit" name="reject_challenge" value="{GameID}">
                                                            Reject
                                                        </button>
                                                    </p>
                                                </xsl:when>
                                                <xsl:when test="$param/current_subsection = 'outgoing'">
                                                    <p>
                                                        <xsl:text>You challenged </xsl:text>
                                                        <span>
                                                            <xsl:if test="Online = 'yes'">
                                                                <xsl:attribute name="class">p_online</xsl:attribute>
                                                            </xsl:if>
                                                            <a class="profile" href="{am:makeUrl('Players_details', 'Profile', Recipient)}">
                                                                <xsl:value-of select="Recipient"/>
                                                            </a>
                                                        </span>
                                                        <xsl:text> on </xsl:text>
                                                        <span>
                                                            <xsl:value-of select="am:dateTime(Created, $param/timezone)"/>
                                                        </span>
                                                    </p>
                                                    <xsl:if test="Content != ''">
                                                        <div class="challenge_content">
                                                            <xsl:value-of select="am:bbCodeParseExtended(Content)" disable-output-escaping="yes"/>
                                                        </div>
                                                    </xsl:if>
                                                    <p>
                                                        <button type="submit" name="withdraw_challenge" value="{GameID}">
                                                            Withdraw challenge
                                                        </button>
                                                    </p>
                                                </xsl:when>
                                            </xsl:choose>
                                        </div>
                                    </xsl:for-each>
                                </div>
                            </xsl:when>
                            <xsl:otherwise>
                                <p class="information-line info">You have no
                                    <xsl:value-of select="$param/current_subsection"/> challenges.
                                </p>
                            </xsl:otherwise>
                        </xsl:choose>

                    </div>

                    <!-- end challenges -->
                </div>
                <div class="col-md-6">

                    <!-- begin messages -->

                    <div id="messages" class="skin-label top-level">

                        <h3>Messages</h3>

                        <!-- begin buttons and filters -->

                        <p>
                            <xsl:variable name="messageSections">
                                <value name="inbox" value="Inbox"/>
                                <value name="sent_mail" value="Sent mail"/>
                                <value name="all_mail" value="All mail"/>
                            </xsl:variable>
                            <xsl:for-each select="exsl:node-set($messageSections)/*">
                                <xsl:if test="@name != 'all_mail' or $param/see_all_messages = 'yes'">
                                    <a class="button" href="{am:makeUrl('Messages', 'challenges_subsection', $param/current_subsection, 'messages_subsection', @name)}">
                                        <xsl:if test="$param/current_location = @name">
                                            <xsl:attribute name="class">button pushed</xsl:attribute>
                                        </xsl:if>
                                        <xsl:value-of select="@value"/>
                                    </a>
                                </xsl:if>
                            </xsl:for-each>
                        </p>

                        <div class="filters">
                            <!-- name filter -->
                            <input type="text" name="name_filter" maxlength="20" size="20" value="{$param/name_val}" title="search phrase for player name"/>

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
                            <xsl:copy-of select="am:htmlSelectBox('date_filter', $param/date_val, $dates, '')"/>

                            <button class="button-icon" type="submit" name="messages_apply_filters" title="Apply filters">
                                <span class="glyphicon glyphicon-filter"/>
                            </button>
                        </div>

                        <div class="filters">
                            <!-- upper navigation -->
                            <xsl:if test="$param/page_count &gt; 0">
                                <xsl:copy-of select="am:upperNavigation($param/page_count, $param/current_page, 'messages')"/>

                                <xsl:if test="$param/current_location != 'all_mail'">
                                    <button type="submit" name="delete_mass_messages">Delete selected</button>
                                </xsl:if>
                            </xsl:if>

                            <!-- end buttons and filters -->
                        </div>

                        <xsl:if test="($param/messages_count = 0) and (($param/date_val != 'none') or ($param/name_val != 'none'))">
                            <p class="information-line warning">No messages matched selected criteria.</p>
                        </xsl:if>

                        <xsl:if test="($param/messages_count = 0) and ($param/date_val = 'none') and ($param/name_val = 'none')">
                            <p class="information-line info">You have no messages.</p>
                        </xsl:if>

                        <!-- begin messages table -->

                        <xsl:if test="$param/messages_count &gt; 0">
                            <table class="skin-text">
                                <!-- begin table header -->
                                <tr>
                                    <th>
                                        <xsl:choose>
                                            <xsl:when test="$param/current_location = 'sent_mail'">
                                                <p>
                                                    <span>To</span>
                                                    <button class="button-icon" type="submit" value="Recipient">
                                                        <xsl:if test="$param/current_condition = 'Recipient'">
                                                            <xsl:attribute name="class">button-icon pushed</xsl:attribute>
                                                        </xsl:if>
                                                        <xsl:choose>
                                                            <xsl:when test="(($param/current_condition = 'Recipient') and ($param/current_order = 'DESC'))">
                                                                <xsl:attribute name="name">messages_order_asc</xsl:attribute>
                                                                <span class="glyphicon glyphicon-sort-by-attributes-alt"/>
                                                            </xsl:when>
                                                            <xsl:otherwise>
                                                                <xsl:attribute name="name">messages_order_desc</xsl:attribute>
                                                                <span class="glyphicon glyphicon-sort-by-attributes"/>
                                                            </xsl:otherwise>
                                                        </xsl:choose>
                                                    </button>
                                                </p>
                                            </xsl:when>
                                            <xsl:otherwise>
                                                <p>
                                                    <span>From</span>
                                                    <button class="button-icon" type="submit" value="Author">
                                                        <xsl:if test="$param/current_condition = 'Author'">
                                                            <xsl:attribute name="class">button-icon pushed</xsl:attribute>
                                                        </xsl:if>
                                                        <xsl:choose>
                                                            <xsl:when test="(($param/current_condition = 'Author') and ($param/current_order = 'DESC'))">
                                                                <xsl:attribute name="name">messages_order_asc</xsl:attribute>
                                                                <span class="glyphicon glyphicon-sort-by-attributes-alt"/>
                                                            </xsl:when>
                                                            <xsl:otherwise>
                                                                <xsl:attribute name="name">messages_order_desc</xsl:attribute>
                                                                <span class="glyphicon glyphicon-sort-by-attributes"/>
                                                            </xsl:otherwise>
                                                        </xsl:choose>
                                                    </button>
                                                </p>
                                            </xsl:otherwise>
                                        </xsl:choose>
                                    </th>
                                    <xsl:if test="$param/current_location = 'all_mail'">
                                        <th><p>To</p></th>
                                    </xsl:if>
                                    <th><p>Subject</p></th>
                                    <th>
                                        <p>
                                            <span>Sent on</span>
                                            <button class="button-icon" type="submit" value="Created">
                                                <xsl:if test="$param/current_condition = 'Created'">
                                                    <xsl:attribute name="class">button-icon pushed</xsl:attribute>
                                                </xsl:if>
                                                <xsl:choose>
                                                    <xsl:when test="(($param/current_condition = 'Created') and ($param/current_order = 'DESC'))">
                                                        <xsl:attribute name="name">messages_order_asc</xsl:attribute>
                                                        <span class="glyphicon glyphicon-sort-by-attributes-alt"/>
                                                    </xsl:when>
                                                    <xsl:otherwise>
                                                        <xsl:attribute name="name">messages_order_desc</xsl:attribute>
                                                        <span class="glyphicon glyphicon-sort-by-attributes"/>
                                                    </xsl:otherwise>
                                                </xsl:choose>
                                            </button>
                                        </p>
                                    </th>
                                    <th/>
                                </tr>
                                <!-- end table header -->

                                <!-- begin table body -->
                                <xsl:for-each select="$param/messages/*">
                                    <tr class="table-row">
                                        <xsl:if test="$param/current_location = 'inbox'">
                                            <xsl:choose>
                                                <!-- TODO format time to seconds and independent of user timezone -->
                                                <xsl:when test="Unread = 'yes' and am:dateDiff(Created, $param/notification) &lt;= 0">
                                                    <xsl:attribute name="class">table-row new_message</xsl:attribute>
                                                </xsl:when>
                                                <xsl:when test="Unread = 'yes'">
                                                    <xsl:attribute name="class">table-row unread</xsl:attribute>
                                                </xsl:when>
                                                <xsl:when test="Author = $param/system_name">
                                                    <xsl:attribute name="class">table-row system_message</xsl:attribute>
                                                </xsl:when>
                                            </xsl:choose>
                                        </xsl:if>
                                        <td>
                                            <p>
                                                <xsl:choose>
                                                    <xsl:when test="$param/current_location = 'sent_mail'">
                                                        <xsl:value-of select="Recipient"/>
                                                    </xsl:when>
                                                    <xsl:otherwise>
                                                        <xsl:value-of select="Author"/>
                                                    </xsl:otherwise>
                                                </xsl:choose>
                                            </p>
                                        </td>
                                        <xsl:if test="$param/current_location = 'all_mail'">
                                            <td><p><xsl:value-of select="Recipient"/></p></td>
                                        </xsl:if>
                                        <td><p><xsl:value-of select="Subject"/></p></td>
                                        <td><p><xsl:value-of select="am:dateTime(Created, $param/timezone)"/></p></td>
                                        <td>
                                            <p style="text-align: left">
                                                <button class="button-icon" type="submit" value="{MessageID}" title="Message details">
                                                    <xsl:choose>
                                                        <xsl:when test="$param/current_location = 'all_mail'">
                                                            <xsl:attribute name="name">message_retrieve</xsl:attribute>
                                                        </xsl:when>
                                                        <xsl:otherwise>
                                                            <xsl:attribute name="name">message_details</xsl:attribute>
                                                        </xsl:otherwise>
                                                    </xsl:choose>
                                                    <span class="glyphicon glyphicon-zoom-in"/>
                                                </button>
                                                <xsl:if test="$param/current_location != 'all_mail'">
                                                    <input type="checkbox" class="table-checkbox" name="mass_delete_{position()}" value="{MessageID}"/>
                                                </xsl:if>
                                                <xsl:if test="(($param/send_messages = 'yes') and ($param/current_location = 'inbox') and (Author != $param/system_name) and (Author != $param/player_name))">
                                                    <button class="button-icon" type="submit" name="message_create" value="{Author}" title="Reply">
                                                        <span class="glyphicon glyphicon-share-alt"/>
                                                    </button>
                                                </xsl:if>
                                            </p>
                                        </td>
                                    </tr>
                                </xsl:for-each>
                                <!-- end table body -->
                            </table>
                        </xsl:if>

                        <!-- end messages table -->

                    </div>

                    <!-- end messages -->
                </div>
            </div>

            <input type="hidden" name="challenges_subsection" value="{$param/current_subsection}"/>
            <input type="hidden" name="messages_subsection" value="{$param/current_location}"/>
            <input type="hidden" name="messages_current_page" value="{$param/current_page}"/>
            <input type="hidden" name="messages_current_order" value="{$param/current_order}"/>
            <input type="hidden" name="messages_current_condition" value="{$param/current_condition}"/>

        </div>

    </xsl:template>


    <xsl:template match="section[. = 'Messages_details']">
        <xsl:variable name="param" select="$params/message_details"/>

        <div class="mes_details">

            <h3>Message details</h3>

            <div class="skin-text">
                <img class="stamp_picture" src="img/stamps/stamp{$param/stamp}.png" width="100" height="100" alt="Marcopost stamp"/>
                <p>
                    <span>From:</span>
                    <xsl:value-of select="$param/author"/>
                </p>
                <p>
                    <span>To:</span>
                    <xsl:value-of select="$param/recipient"/>
                </p>
                <p>
                    <span>Subject:</span>
                    <xsl:value-of select="$param/subject"/>
                </p>
                <p>
                    <span>Sent on:</span>
                    <xsl:value-of select="am:dateTime($param/created, $param/timezone)"/>
                </p>
                <p>
                    <button class="button-icon" type="submit" name="message_cancel">
                        <span class="glyphicon glyphicon-arrow-left"/>
                    </button>
                    <xsl:if test="$param/current_location != 'all_mail'">
                        <xsl:choose>
                            <xsl:when test="$param/delete = 'no'">
                                <button class="button-icon" type="submit" name="message_delete" value="{$param/message_id}" title="Delete">
                                    <span class="glyphicon glyphicon-trash"/>
                                </button>
                            </xsl:when>
                            <xsl:otherwise>
                                <button class="button-icon marked_button" type="submit" name="message_delete_confirm" value="{$param/message_id}" title="Confirm delete">
                                    <span class="glyphicon glyphicon-trash"/>
                                </button>
                            </xsl:otherwise>
                        </xsl:choose>
                    </xsl:if>
                    <xsl:if test="($param/messages = 'yes') and ($param/recipient = $param/player_name) and ($param/author != $param/system_name) and ($param/author != $param/player_name)">
                        <button class="button-icon" type="submit" name="message_create" value="{am:urlEncode($param/author)}" title="Reply">
                            <span class="glyphicon glyphicon-share-alt"/>
                        </button>
                    </xsl:if>
                </p>
                <hr/>
                <div>
                    <xsl:value-of select="am:bbCodeParseExtended($param/content)" disable-output-escaping="yes"/>
                </div>
            </div>
            <input type="hidden" name="messages_subsection" value="{$param/current_location}"/>

        </div>

    </xsl:template>


    <xsl:template match="section[. = 'Messages_new']">
        <xsl:variable name="param" select="$params/message_new"/>

        <div class="mes_details">

            <h3>New message</h3>

            <div class="skin-text">
                <img class="stamp_picture" src="img/stamps/stamp0.png" width="100" height="100" alt="Marcopost stamp"/>
                <p>
                    <span>From:</span>
                    <xsl:value-of select="$param/author"/>
                </p>
                <p>
                    <span>To:</span>
                    <xsl:value-of select="$param/recipient"/>
                </p>
                <p>
                    <span>Subject:</span>
                    <input type="text" name="subject" maxlength="30" size="25" value="{$param/subject}"/>
                </p>
                <p>
                    <button class="button-icon" type="submit" name="message_send" title="Send message">
                        <span class="glyphicon glyphicon-send"/>
                    </button>
                    <button class="button-icon" type="submit" name="message_cancel" title="Discard">
                        <span class="glyphicon glyphicon-trash"/>
                    </button>
                </p>
                <xsl:copy-of select="am:bbCodeButtons('content')"/>
                <hr/>

                <textarea name="content" rows="6" cols="50">
                    <xsl:value-of select="$param/content"/>
                </textarea>
            </div>

            <input type="hidden" name="author" value="{$param/author}"/>
            <input type="hidden" name="recipient" value="{$param/recipient}"/>

        </div>

    </xsl:template>


</xsl:stylesheet>
