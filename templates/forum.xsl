<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
                xmlns="http://www.w3.org/1999/xhtml"
                xmlns:am="http://arcomage.net"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:date="http://exslt.org/dates-and-times"
                xmlns:exsl="http://exslt.org/common"
                xmlns:func="http://exslt.org/functions"
                xmlns:str="http://exslt.org/strings"
                extension-element-prefixes="date exsl func str">
    <xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes"
                doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN"
                doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"/>

    <!-- includes -->
    <xsl:include href="main.xsl"/>


    <xsl:template match="section[. = 'Forum']">
        <xsl:variable name="param" select="$params/forum_overview"/>

        <div class="forum">
            <div class="row">
                <xsl:for-each select="$param/groups/*">
                    <div class="col-md-6">
                        <div class="skin-text top-level">
                            <div class="responsive-table table-sm">
                                <!-- table header -->
                                <div class="row">
                                    <div class="col-sm-1">
                                        <p class="sortable">
                                            <xsl:if test="$param/is_logged_in = 'yes'">
                                                <button class="button-icon" type="submit" name="forum_search" title="Search">
                                                    <span class="glyphicon glyphicon-search"/>
                                                </button>
                                            </xsl:if>
                                        </p>
                                    </div>
                                    <div class="col-sm-5">
                                        <p>Topic</p>
                                    </div>
                                    <div class="col-sm-2">
                                        <p>Author</p>
                                    </div>
                                    <div class="col-sm-1">
                                        <p>Posts</p>
                                    </div>
                                    <div class="col-sm-3">
                                        <p>Latest post</p>
                                    </div>
                                </div>

                                <!-- table body -->
                                <xsl:for-each select="sections/*">
                                    <div class="row table-row">
                                        <div class="col-sm-12">
                                            <div class="skin-label">
                                                <h5>
                                                    <a href="{am:makeUrl('Forum_section', 'current_section', section_id)}">
                                                        <xsl:value-of select="section_name"/>
                                                    </a>
                                                    <xsl:text> (</xsl:text>
                                                    <xsl:value-of select="count"/>
                                                    <xsl:text>) - </xsl:text>
                                                    <xsl:value-of select="description"/>
                                                </h5>
                                            </div>
                                        </div>
                                    </div>

                                    <xsl:for-each select="threadlist/*">

                                        <xsl:variable name="hasPosts" select="boolean(post_count > 0)"/>

                                        <div class="row table-row">
                                            <div class="col-sm-1">
                                                <p>
                                                    <xsl:choose>
                                                        <xsl:when test="priority = 'sticky'">
                                                            <img src="img/sticky.gif" width="22" height="15" alt="sticky" title="Sticky" class="icon"/>
                                                        </xsl:when>
                                                        <xsl:when test="priority = 'important'">
                                                            <img src="img/important.gif" width="18" height="13" alt="important" title="Important" class="icon"/>
                                                        </xsl:when>
                                                    </xsl:choose>
                                                    <xsl:if test="is_locked = 'yes'">
                                                        <img src="img/locked.gif" width="15" height="16" alt="locked" title="Locked" class="icon"/>
                                                    </xsl:if>
                                                </p>
                                            </div>
                                            <div class="col-sm-5">
                                                <p>
                                                    <a href="{am:makeUrl('Forum_thread', 'current_thread', thread_id, 'thread_current_page', 0)}">
                                                        <xsl:value-of select="title"/>
                                                    </a>
                                                </p>
                                            </div>
                                            <div class="col-sm-2">
                                                <p>
                                                    <a class="profile" href="{am:makeUrl('Players_details', 'Profile', author)}">
                                                        <xsl:value-of select="author"/>
                                                    </a>
                                                </p>
                                            </div>
                                            <div class="col-sm-1">
                                                <p><xsl:value-of select="post_count"/></p>
                                            </div>
                                            <div class="col-sm-3">
                                                <xsl:choose>
                                                    <xsl:when test="$hasPosts">
                                                        <p>
                                                            <xsl:if test="am:dateDiff(last_post, $param/notification) &lt; 0">
                                                                <xsl:attribute name="class">new</xsl:attribute>
                                                            </xsl:if>
                                                            <a href="{am:makeUrl('Forum_thread', 'current_thread', thread_id, 'thread_current_page', am:max(last_page - 1, 0))}#latest">
                                                                <xsl:value-of select="am:dateTime(last_post, $param/timezone)"/>
                                                            </a>
                                                            <xsl:text> by </xsl:text>
                                                            <a class="profile"
                                                               href="{am:makeUrl('Players_details', 'Profile', last_author)}">
                                                                <xsl:value-of select="last_author"/>
                                                            </a>
                                                        </p>
                                                    </xsl:when>
                                                    <xsl:otherwise>
                                                        <p>n/a</p>
                                                    </xsl:otherwise>
                                                </xsl:choose>
                                            </div>
                                        </div>
                                    </xsl:for-each>
                                </xsl:for-each>
                            </div>
                        </div>
                    </div>
                </xsl:for-each>
            </div>
        </div>
    </xsl:template>


    <xsl:template match="section[. = 'Forum_search']">
        <xsl:variable name="param" select="$params/forum_search"/>

        <div class="forum top-level">

            <div class="filters">
                <input type="text" name="phrase" maxlength="50" size="30" value="{$param/phrase}" title="search phrase"/>

                <!-- target selector -->
                <xsl:variable name="targets">
                    <class name="posts"/>
                    <class name="threads"/>
                </xsl:variable>

                <select name="target">
                    <xsl:if test="$param/target != 'all'">
                        <xsl:attribute name="class">filter-active</xsl:attribute>
                    </xsl:if>
                    <option value="all">
                        <xsl:if test="$param/target = 'all'">
                            <xsl:attribute name="selected">selected</xsl:attribute>
                        </xsl:if>
                        <xsl:text>any source</xsl:text>
                    </option>
                    <xsl:for-each select="exsl:node-set($targets)/*">
                        <option value="{@name}">
                            <xsl:if test="$param/target = @name">
                                <xsl:attribute name="selected">selected</xsl:attribute>
                            </xsl:if>
                            <xsl:value-of select="@name"/>
                        </option>
                    </xsl:for-each>
                </select>

                <!-- section selector -->
                <select name="section">
                    <xsl:if test="$param/section != 'any'">
                        <xsl:attribute name="class">filter-active</xsl:attribute>
                    </xsl:if>
                    <option value="any">
                        <xsl:if test="$param/section = 'any'">
                            <xsl:attribute name="selected">selected</xsl:attribute>
                        </xsl:if>
                        <xsl:text>any section</xsl:text>
                    </option>
                    <xsl:for-each select="$param/sections/*">
                        <option value="{section_id}">
                            <xsl:if test="$param/section = section_id">
                                <xsl:attribute name="selected">selected</xsl:attribute>
                            </xsl:if>
                            <xsl:value-of select="section_name"/>
                        </option>
                    </xsl:for-each>
                </select>

                <button type="submit" name="forum_search">Search</button>
            </div>

            <xsl:choose>
                <xsl:when test="count($param/threads/*) &gt; 0">
                    <div class="skin-text">
                        <div class="responsive-table table-sm">
                            <!-- table header -->
                            <div class="row">
                                <div class="col-sm-1">
                                    <p/>
                                </div>
                                <div class="col-sm-5">
                                    <p>Topic</p>
                                </div>
                                <div class="col-sm-2">
                                    <p>Author</p>
                                </div>
                                <div class="col-sm-1">
                                    <p>Posts</p>
                                </div>
                                <div class="col-sm-3">
                                    <p>Latest post</p>
                                </div>
                            </div>

                            <!-- table body -->
                            <xsl:for-each select="$param/threads/*">
                                <xsl:variable name="hasPosts" select="boolean(post_count > 0)"/>

                                <div class="row table-row">
                                    <div class="col-sm-1">
                                        <p>
                                            <xsl:choose>
                                                <xsl:when test="priority = 'sticky'">
                                                    <img src="img/sticky.gif" width="22" height="15" alt="sticky" title="Sticky" class="icon"/>
                                                </xsl:when>
                                                <xsl:when test="priority = 'important'">
                                                    <img src="img/important.gif" width="18" height="13" alt="important" title="Important" class="icon"/>
                                                </xsl:when>
                                            </xsl:choose>
                                            <xsl:if test="is_locked = 'yes'">
                                                <img src="img/locked.gif" width="15" height="16" alt="locked" title="Locked" class="icon"/>
                                            </xsl:if>
                                        </p>
                                    </div>
                                    <div class="col-sm-5">
                                        <p>
                                            <a href="{am:makeUrl('Forum_thread', 'current_thread', thread_id, 'thread_current_page', 0)}">
                                                <xsl:value-of select="title"/>
                                            </a>
                                        </p>
                                    </div>
                                    <div class="col-sm-2">
                                        <p>
                                            <a class="profile"
                                               href="{am:makeUrl('Players_details', 'Profile', author)}">
                                                <xsl:value-of select="author"/>
                                            </a>
                                        </p>
                                    </div>
                                    <div class="col-sm-1">
                                        <p><xsl:value-of select="post_count"/></p>
                                    </div>
                                    <div class="col-sm-3">
                                        <xsl:choose>
                                            <xsl:when test="$hasPosts">
                                                <p>
                                                    <xsl:if test="am:dateDiff(last_post, $param/notification) &lt; 0">
                                                        <xsl:attribute name="class">new</xsl:attribute>
                                                    </xsl:if>
                                                    <a href="{am:makeUrl('Forum_thread', 'current_thread', thread_id, 'thread_current_page', am:max(last_page - 1, 0))}#latest">
                                                        <xsl:value-of select="am:dateTime(last_post, $param/timezone)"/>
                                                    </a>
                                                    <xsl:text> by </xsl:text>
                                                    <a class="profile"
                                                       href="{am:makeUrl('Players_details', 'Profile', last_author)}">
                                                        <xsl:value-of select="last_author"/>
                                                    </a>
                                                </p>
                                            </xsl:when>
                                            <xsl:otherwise>
                                                <p>n/a</p>
                                            </xsl:otherwise>
                                        </xsl:choose>
                                    </div>
                                </div>
                            </xsl:for-each>
                        </div>
                    </div>
                </xsl:when>
                <xsl:otherwise>
                    <p class="information-line warning">No results matched selected criteria.</p>
                </xsl:otherwise>
            </xsl:choose>

        </div>
    </xsl:template>


    <xsl:template match="section[. = 'Forum_section']">
        <xsl:variable name="param" select="$params/forum_section"/>

        <div class="forum top-level">

            <div class="skin-text">

                <div class="responsive-table table-sm">
                    <!-- table header -->
                    <div class="row">
                        <div class="col-sm-1">
                            <p class="sortable">
                                <xsl:if test="$param/is_logged_in = 'yes'">
                                    <button class="button-icon" type="submit" name="forum_search" title="Search">
                                        <span class="glyphicon glyphicon-search"/>
                                    </button>
                                </xsl:if>
                            </p>
                        </div>
                        <div class="col-sm-5">
                            <p>Topic</p>
                        </div>
                        <div class="col-sm-2">
                            <p>Author</p>
                        </div>
                        <div class="col-sm-1">
                            <p>Posts</p>
                        </div>
                        <div class="col-sm-3">
                            <p>Latest post</p>
                        </div>
                    </div>

                    <!-- table body -->
                    <div class="row table-row">
                        <div class="col-sm-12">
                            <div class="skin-label">
                                <div class="row">
                                    <div class="col-md-7">
                                        <h5>
                                            <a href="{am:makeUrl('Forum')}">
                                                <xsl:value-of select="$param/section/section_name"/>
                                            </a>
                                            <xsl:text> - </xsl:text>
                                            <xsl:value-of select="$param/section/description"/>
                                        </h5>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="navigation">
                                            <!-- navigation -->
                                            <xsl:copy-of select="am:forumNavigation(
                                                'Forum_section', 'current_section', $param/section/section_id,
                                                'section_current_page', $param/current_page, $param/pages
                                            )"/>

                                            <xsl:if test="$param/create_thread = 'yes'">
                                                <button class="button-icon" type="submit" name="new_thread" title="New thread">
                                                    <span class="glyphicon glyphicon-plus"/>
                                                </button>
                                            </xsl:if>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <xsl:for-each select="$param/threads/*">

                        <xsl:variable name="hasPosts" select="boolean(post_count > 0)"/>

                        <div class="row table-row">
                            <div class="col-sm-1">
                                <p>
                                    <xsl:choose>
                                        <xsl:when test="priority = 'sticky'">
                                            <img src="img/sticky.gif" width="22" height="15" alt="sticky" title="Sticky" class="icon"/>
                                        </xsl:when>
                                        <xsl:when test="priority = 'important'">
                                            <img src="img/important.gif" width="18" height="13" alt="important" title="Important" class="icon"/>
                                        </xsl:when>
                                    </xsl:choose>
                                    <xsl:if test="is_locked = 'yes'">
                                        <img src="img/locked.gif" width="15" height="16" alt="locked" title="Locked" class="icon"/>
                                    </xsl:if>
                                </p>
                            </div>
                            <div class="col-sm-5">
                                <p>
                                    <a href="{am:makeUrl('Forum_thread', 'current_thread', thread_id, 'thread_current_page', 0)}">
                                        <xsl:value-of select="title"/>
                                    </a>
                                </p>
                            </div>
                            <div class="col-sm-2">
                                <p>
                                    <a class="profile" href="{am:makeUrl('Players_details', 'Profile', author)}">
                                        <xsl:value-of select="author"/>
                                    </a>
                                </p>
                            </div>
                            <div class="col-sm-1">
                                <p><xsl:value-of select="post_count"/></p>
                            </div>
                            <div class="col-sm-3">
                                <xsl:choose>
                                    <xsl:when test="$hasPosts">
                                        <p>
                                            <xsl:if test="am:dateDiff(last_post, $param/notification) &lt; 0">
                                                <xsl:attribute name="class">new</xsl:attribute>
                                            </xsl:if>
                                            <a href="{am:makeUrl('Forum_thread', 'current_thread', thread_id, 'thread_current_page', am:max(last_page - 1, 0))}#latest">
                                                <xsl:value-of select="am:dateTime(last_post, $param/timezone)"/>
                                            </a>
                                            <xsl:text> by </xsl:text>
                                            <a class="profile"
                                               href="{am:makeUrl('Players_details', 'Profile', last_author)}">
                                                <xsl:value-of select="last_author"/>
                                            </a>
                                        </p>
                                    </xsl:when>
                                    <xsl:otherwise>
                                        <p>n/a</p>
                                    </xsl:otherwise>
                                </xsl:choose>
                            </div>
                        </div>
                    </xsl:for-each>
                </div>
            </div>

            <input type="hidden" name="current_section" value="{$param/section/section_id}"/>
        </div>
    </xsl:template>


    <xsl:template match="section[. = 'Forum_thread']">
        <xsl:variable name="param" select="$params/forum_thread"/>

        <xsl:variable name="section" select="$param/section_data"/>
        <xsl:variable name="thread" select="$param/thread_data"/>
        <xsl:variable name="deletePost" select="$param/delete_post"/>
        <!-- is unlocked or you have the right to lock/unlock -->
        <xsl:variable name="canModify" select="$thread/is_locked = 'no' or $param/lock_thread = 'yes'"/>

        <div id="thread-details" class="top-level">

            <xsl:variable name="navigationBar">
                <div class="thread-bar skin-label">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>
                                <a href="{am:makeUrl('Forum_section', 'current_section', $section/section_id)}">
                                    <xsl:value-of select="$section/section_name"/>
                                </a>
                                <span>&gt;</span>
                                <a href="{am:makeUrl('Forum_thread', 'current_thread', $thread/thread_id, 'thread_current_page', $param/current_page)}">
                                    <xsl:value-of select="$thread/title"/>
                                </a>
                                <xsl:if test="$thread/is_locked = 'yes'">
                                    <img src="img/locked.gif" width="15" height="16" alt="locked" title="Locked" class="icon"/>
                                </xsl:if>
                            </h5>
                        </div>
                        <div class="col-md-6">
                            <div class="navigation">
                                <xsl:choose>
                                    <xsl:when test="$thread/reference_concept &gt; 0">
                                        <a class="button" href="{am:makeUrl('Concepts_details', 'current_concept', $thread/reference_concept)}">
                                            <xsl:text>View concept</xsl:text>
                                        </a>
                                    </xsl:when>
                                    <xsl:when test="$thread/reference_card &gt; 0">
                                        <a class="button" href="{am:makeUrl('Cards_details', 'card', $thread/reference_card)}">
                                            <xsl:text>View card</xsl:text>
                                        </a>
                                    </xsl:when>
                                    <xsl:when test="$param/is_logged_in = 'yes' and $thread/reference_replay &gt; 0">
                                        <a class="button" href="{am:makeUrl('Replays_details', 'CurrentReplay', $thread/reference_replay, 'PlayerView', 1, 'Turn', 1)}">
                                            <xsl:text>View replay</xsl:text>
                                        </a>
                                    </xsl:when>
                                    <xsl:when test="$param/is_logged_in = 'yes' and $thread/reference_deck &gt; 0">
                                        <a class="button" href="{am:makeUrl('Decks_details', 'current_deck', $thread/reference_deck)}">
                                            <xsl:text>View deck</xsl:text>
                                        </a>
                                    </xsl:when>
                                </xsl:choose>

                                <xsl:if test="$param/lock_thread = 'yes'">
                                    <xsl:choose>
                                        <xsl:when test="$thread/is_locked = 'no'">
                                            <button class="button-icon" type="submit" name="thread_lock" title="Lock">
                                                <span class="glyphicon glyphicon-lock"/>
                                            </button>
                                        </xsl:when>
                                        <xsl:otherwise>
                                            <button class="button-icon" type="submit" name="thread_unlock" title="Unlock">
                                                <span class="glyphicon glyphicon-fullscreen"/>
                                            </button>
                                        </xsl:otherwise>
                                    </xsl:choose>
                                </xsl:if>

                                <xsl:if test="$param/del_all_thread = 'yes' and $canModify">
                                    <xsl:choose>
                                        <xsl:when test="$param/delete_thread = 'no'">
                                            <button class="button-icon" type="submit" name="thread_delete" title="Delete">
                                                <span class="glyphicon glyphicon-trash"/>
                                            </button>
                                        </xsl:when>
                                        <xsl:otherwise>
                                            <button class="button-icon marked_button" type="submit" name="thread_delete_confirm" title="Confirm delete">
                                                <span class="glyphicon glyphicon-trash"/>
                                            </button>
                                        </xsl:otherwise>
                                    </xsl:choose>
                                </xsl:if>

                                <xsl:if test="$param/edit_thread = 'yes' and $canModify">
                                    <button class="button-icon" type="submit" name="edit_thread" title="Edit">
                                        <span class="glyphicon glyphicon-pencil"/>
                                    </button>
                                </xsl:if>

                                <!-- navigation -->
                                <xsl:copy-of select="am:forumNavigation(
                                    'Forum_thread', 'current_thread', $thread/thread_id,
                                    'thread_current_page', $param/current_page, $param/pages_count
                                )"/>

                                <xsl:if test="$param/create_post = 'yes' and $thread/is_locked = 'no'">
                                    <button class="button-icon" type="submit" name="new_post" title="New post">
                                        <span class="glyphicon glyphicon-plus"/>
                                    </button>
                                </xsl:if>

                            </div>
                        </div>
                    </div>
                </div>
            </xsl:variable>

            <xsl:copy-of select="$navigationBar"/>

            <div id="post-list">

                <xsl:for-each select="$param/post_list/*">

                    <div class="skin-text">
                        <div class="post-header row">

                            <div class="col-sm-6">
                                <div class="post-author">
                                    <xsl:if test="position() = last()">
                                        <a id="latest"/>
                                    </xsl:if>
                                    <a href="{am:makeUrl('Players_details', 'Profile', author)}">
                                        <xsl:value-of select="author"/>
                                    </a>

                                    <xsl:text> on </xsl:text>

                                    <span>
                                        <xsl:if test="am:dateDiff(created_at, $param/notification) &lt; 0">
                                            <xsl:attribute name="class">new</xsl:attribute>
                                        </xsl:if>
                                        <xsl:value-of select="am:dateTime(created_at, $param/timezone)"/>
                                    </span>
                                </div>
                            </div>

                            <div class="col-sm-6">
                                <div class="post-buttons">
                                    <xsl:if test="$param/create_post = 'yes' and $thread/is_locked = 'no'">
                                        <button class="button-icon" type="submit" name="quote_post" value="{post_id}" title="Quote">
                                            <span class="glyphicon glyphicon-share"/>
                                        </button>
                                    </xsl:if>

                                    <xsl:if test="($param/edit_all_post = 'yes' or ($param/edit_own_post = 'yes' and $param/player_name = author)) and $canModify">
                                        <button class="button-icon" type="submit" name="edit_post" value="{post_id}" title="Edit">
                                            <span class="glyphicon glyphicon-pencil"/>
                                        </button>
                                    </xsl:if>

                                    <xsl:if test="$param/del_all_post = 'yes' and $canModify">
                                        <xsl:choose>
                                            <xsl:when test="$deletePost != post_id">
                                                <button class="button-icon" type="submit" name="delete_post" value="{post_id}" title="Delete">
                                                    <span class="glyphicon glyphicon-trash"/>
                                                </button>
                                            </xsl:when>
                                            <xsl:otherwise>
                                                <button class="button-icon marked_button" type="submit" name="delete_post_confirm" value="{post_id}" title="Confirm delete">
                                                    <span class="glyphicon glyphicon-trash"/>
                                                </button>
                                            </xsl:otherwise>
                                        </xsl:choose>
                                    </xsl:if>

                                    <xsl:variable name="postId" select="concat('post', post_id)"/>
                                    <a id="{$postId}" class="permalink" href="{am:makeUrl('Forum_thread', 'current_thread', $thread/thread_id, 'thread_current_page', $param/current_page)}#{$postId}" title="Permalink">
                                        <xsl:text>#</xsl:text>
                                        <xsl:value-of select="position() + $param/current_page * $param/posts_per_page"/>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-1">
                                <div class="post-avatar">
                                    <a href="{am:makeUrl('Players_details', 'Profile', author)}">
                                        <img class="avatar" height="60" width="60" src="{$param/avatar_path}{avatar}" alt="avatar"/>
                                    </a>
                                </div>
                            </div>

                            <div class="col-md-11">
                                <div class="post-content">
                                    <div>
                                        <xsl:value-of select="am:bbCodeParseExtended(content)" disable-output-escaping="yes"/>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </xsl:for-each>
            </div>

            <xsl:copy-of select="$navigationBar"/>

            <input type="hidden" name="current_section" value="{$thread/section_id}"/>
            <input type="hidden" name="current_thread" value="{$thread/thread_id}"/>
            <input type="hidden" name="thread_current_page" value="{$param/current_page}"/>

        </div>
    </xsl:template>


    <xsl:template match="section[. = 'Forum_thread_new']">
        <xsl:variable name="param" select="$params/forum_thread_new"/>

        <xsl:variable name="section" select="$param/section_data"/>

        <div class="forum-new-edit">

            <h3>Create new thread to the section
                <span>
                    <xsl:value-of select="$section/section_name"/>
                </span>
            </h3>

            <div class="skin-text">
                <p>Topic:
                    <input type="text" name="title" maxlength="50" size="45" value="{$param/title}"/>
                </p>
                <p>

                    <xsl:text>Priority:</xsl:text>

                    <select name="priority">
                        <option value="normal" selected="selected">Normal</option>
                        <xsl:if test="$param/change_priority = 'yes'">
                            <option value="important">Important</option>
                            <option value="sticky">Sticky</option>
                        </xsl:if>
                    </select>

                </p>

                <a class="button button-icon" href="{am:makeUrl('Forum_section', 'current_section', $section/section_id)}">
                    <span class="glyphicon glyphicon-arrow-left"/>
                </a>
                <button class="button-icon" type="submit" name="create_thread" title="Create thread">
                    <span class="glyphicon glyphicon-ok"/>
                </button>
                <xsl:copy-of select="am:bbCodeButtons('content')"/>
                <hr/>

                <textarea name="content" rows="10" cols="50">
                    <xsl:value-of select="$param/content"/>
                </textarea>

            </div>

            <input type="hidden" name="current_section" value="{$section/section_id}"/>
        </div>
    </xsl:template>


    <xsl:template match="section[. = 'Forum_post_new']">
        <xsl:variable name="param" select="$params/forum_post_new"/>

        <xsl:variable name="thread" select="$param/thread_data"/>

        <div class="forum-new-edit">

            <h3>New post in thread
                <span>
                    <xsl:value-of select="$thread/title"/>
                </span>
            </h3>

            <div class="skin-text">

                <a class="button button-icon" href="{am:makeUrl('Forum_thread', 'current_thread', $thread/thread_id, 'thread_current_page', 0)}">
                    <span class="glyphicon glyphicon-arrow-left"/>
                </a>
                <button class="button-icon" type="submit" name="create_post" title="Create post">
                    <span class="glyphicon glyphicon-ok"/>
                </button>
                <xsl:copy-of select="am:bbCodeButtons('content')"/>
                <hr/>

                <textarea name="content" rows="10" cols="50">
                    <xsl:value-of select="$param/content"/>
                </textarea>

            </div>

            <input type="hidden" name="current_thread" value="{$thread/thread_id}"/>

        </div>
    </xsl:template>


    <xsl:template match="section[. = 'Forum_thread_edit']">
        <xsl:variable name="param" select="$params/forum_thread_edit"/>

        <xsl:variable name="section" select="$param/section_data"/>
        <xsl:variable name="thread" select="$param/thread_data"/>
        <xsl:variable name="sectionList" select="$param/SectionList"/>

        <div class="forum-new-edit">

            <h3>Edit thread</h3>

            <div class="skin-text">
                <p>Topic: <input type="text" name="title" maxlength="50" size="45" value="{$thread/title}"/>
                </p>

                <p>
                    <xsl:text>Priority:</xsl:text>

                    <select name="priority">
                        <xsl:if test="$param/change_priority = 'no'">
                            <xsl:attribute name="disabled">disabled</xsl:attribute>
                        </xsl:if>

                        <xsl:variable name="priorityTypes">
                            <type name="normal" text="Normal"/>
                            <type name="important" text="Important"/>
                            <type name="sticky" text="Sticky"/>
                        </xsl:variable>

                        <xsl:for-each select="exsl:node-set($priorityTypes)/*">
                            <option value="{@name}">
                                <xsl:if test="$thread/priority = @name">
                                    <xsl:attribute name="selected">selected</xsl:attribute>
                                </xsl:if>
                                <xsl:value-of select="@text"/>
                            </option>
                        </xsl:for-each>
                    </select>
                </p>

                <a class="button button-icon" href="{am:makeUrl('Forum_thread', 'current_thread', $thread/thread_id, 'thread_current_page', 0)}">
                    <span class="glyphicon glyphicon-arrow-left"/>
                </a>
                <button class="button-icon" type="submit" name="modify_thread" title="Save">
                    <span class="glyphicon glyphicon-ok"/>
                </button>

                <xsl:if test="$param/move_thread = 'yes'">
                    <hr/>

                    <p>
                        Current section:
                        <span>
                            <xsl:value-of select="$section/section_name"/>
                        </span>
                    </p>

                    <p>
                        <xsl:text>Target section:</xsl:text>

                        <select name="section_select">
                            <xsl:for-each select="$sectionList/*">
                                <option value="{section_id}">
                                    <xsl:value-of select="section_name"/>
                                </option>
                            </xsl:for-each>
                        </select>

                        <button class="button-icon" type="submit" name="move_thread" title="Change section">
                            <span class="glyphicon glyphicon-transfer"/>
                        </button>
                    </p>
                </xsl:if>
            </div>
            <input type="hidden" name="current_thread" value="{$thread/thread_id}"/>
        </div>
    </xsl:template>


    <xsl:template match="section[. = 'Forum_post_edit']">
        <xsl:variable name="param" select="$params/forum_post_edit"/>

        <xsl:variable name="post" select="$param/post_data"/>
        <xsl:variable name="thread" select="$param/thread_data"/>
        <xsl:variable name="threadList" select="$param/thread_list"/>
        <xsl:variable name="currentPage" select="$param/current_page"/>

        <div class="forum-new-edit">
            <h3>Edit post</h3>
            <div class="skin-text">
                <a class="button button-icon" href="{am:makeUrl('Forum_thread', 'current_thread', $post/thread_id, 'thread_current_page', $currentPage)}">
                    <span class="glyphicon glyphicon-arrow-left"/>
                </a>
                <button class="button-icon" type="submit" name="modify_post" title="Save">
                    <span class="glyphicon glyphicon-ok"/>
                </button>
                <xsl:copy-of select="am:bbCodeButtons('content')"/>
                <hr/>

                <textarea name="content" rows="10" cols="50">
                    <xsl:value-of select="$param/content"/>
                </textarea>

                <xsl:if test="$param/move_post = 'yes'">
                    <hr/>
                    <p>
                        Current thread:
                        <span>
                            <xsl:value-of select="$thread/title"/>
                        </span>
                    </p>
                    <xsl:if test="count($threadList/*) &gt; 0">
                        <p>
                            <xsl:text>Target thread:</xsl:text>
                            <select name="thread_select">
                                <xsl:for-each select="$threadList/*">
                                    <option value="{thread_id}">
                                        <xsl:value-of select="title"/>
                                    </option>
                                </xsl:for-each>
                            </select>

                            <button class="button-icon" type="submit" name="move_post" title="Change thread">
                                <span class="glyphicon glyphicon-transfer"/>
                            </button>
                        </p>
                    </xsl:if>
                </xsl:if>
            </div>

            <input type="hidden" name="current_thread" value="{$post/thread_id}"/>
            <input type="hidden" name="current_post" value="{$post/post_id}"/>
            <input type="hidden" name="thread_current_page" value="{$currentPage}"/>
        </div>
    </xsl:template>


    <func:function name="am:forumNavigation">
        <xsl:param name="location" as="xs:string"/>
        <xsl:param name="name" as="xs:string"/>
        <xsl:param name="id" as="xs:integer"/>
        <xsl:param name="pagination" as="xs:string"/>
        <xsl:param name="current" as="xs:integer"/>
        <xsl:param name="pageCount" as="xs:integer"/>

        <xsl:variable name="output">
            <xsl:choose>
                <xsl:when test="$current &gt; 0">
                    <a class="button button-icon" href="{am:makeUrl($location, $name, $id, $pagination, am:max($current - 1, 0))}">
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
                    <a class="button button-icon" href="{am:makeUrl($location, $name, $id, $pagination, 0)}">
                        <span class="glyphicon glyphicon-step-backward"/>
                    </a>
                </xsl:when>
                <xsl:otherwise>
                    <span class="disabled">
                        <span class="glyphicon glyphicon-step-backward"/>
                    </span>
                </xsl:otherwise>
            </xsl:choose>

            <xsl:for-each select="str:split(am:numbers(am:max($current - 2, 0), am:min($current + 2, am:max($pageCount - 1, 0))), ',')">
                <xsl:choose>
                    <xsl:when test="$current != .">
                        <a class="button button-icon" href="{am:makeUrl($location, $name, $id, $pagination, text())}">
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
                    <a class="button button-icon" href="{am:makeUrl($location, $name, $id, $pagination, am:max($pageCount - 1, 0))}">
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
                    <a class="button button-icon" href="{am:makeUrl($location, $name, $id, $pagination, am:min($current + 1, am:max($pageCount - 1, 0)))}">
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


</xsl:stylesheet>
