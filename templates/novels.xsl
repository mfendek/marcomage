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


    <xsl:template match="section[. = 'Novels']">
        <xsl:variable name="param" select="$params/novels"/>

        <xsl:variable name="novels" select="document('../xml/novels.xml')/am:novels"/>
        <xsl:variable name="chapters" select="$novels/am:book[@name = $param/novel]"/>
        <xsl:variable name="parts" select="$chapters/am:chapter[@name = $param/chapter]"/>
        <xsl:variable name="pages" select="count($parts/am:part[@name = $param/part]/am:page)"/>
        <xsl:variable name="page" select="$param/page"/>
        <xsl:variable name="content" select="$parts/am:part[@name = $param/part]/am:page[position() = $page]"/>

        <div id="novels">
            <div class="row">
                <div class="col-md-3">
                    <!-- novels menu -->
                    <aside id="novels-menu" class="skin-label top-level">

                        <h3>Novels menu</h3>

                        <ul>
                            <xsl:for-each select="exsl:node-set($novels)/*">
                                <li>
                                    <xsl:choose>
                                        <!-- display expanded novel -->
                                        <xsl:when test="$param/novel = @name">
                                            <a class="button button-icon pushed" href="{am:makeUrl('Novels')}">
                                                <span class="glyphicon glyphicon-zoom-out"/>
                                            </a>
                                            <xsl:text>Book </xsl:text><xsl:value-of select="position()"/><xsl:text>: </xsl:text>
                                            <xsl:value-of select="@name"/>
                                            <ul>
                                                <xsl:for-each select="exsl:node-set($chapters)/*">
                                                    <li>
                                                        <xsl:choose>
                                                            <!-- display expanded chapter -->
                                                            <xsl:when test="$param/chapter = @name">
                                                                <a class="button button-icon pushed" href="{am:makeUrl('Novels', 'novel', $param/novel)}">
                                                                    <span class="glyphicon glyphicon-zoom-out"/>
                                                                </a>
                                                                <xsl:text>Chapter </xsl:text><xsl:value-of select="position()"/><xsl:text>: </xsl:text>
                                                                <xsl:value-of select="@name"/>
                                                                <ul>
                                                                    <xsl:for-each select="exsl:node-set($parts)/*">
                                                                        <li>
                                                                            <a class="button button-icon" href="{am:makeUrl('Novels', 'novel', $param/novel, 'chapter', $param/chapter, 'part', @name, 'page', 1)}">
                                                                                <xsl:if test="$param/part = @name">
                                                                                    <xsl:attribute name="class">button button-icon pushed</xsl:attribute>
                                                                                </xsl:if>
                                                                                <xsl:choose>
                                                                                    <xsl:when test="$param/part = @name">
                                                                                        <span class="glyphicon glyphicon-bookmark"/>
                                                                                    </xsl:when>
                                                                                    <xsl:otherwise>
                                                                                        <span class="glyphicon glyphicon-book"/>
                                                                                    </xsl:otherwise>
                                                                                </xsl:choose>
                                                                            </a>
                                                                            <xsl:value-of select="@name"/>
                                                                        </li>
                                                                    </xsl:for-each>
                                                                </ul>
                                                            </xsl:when>
                                                            <!-- display collapsed chapter -->
                                                            <xsl:otherwise>
                                                                <a class="button button-icon" href="{am:makeUrl('Novels', 'novel', $param/novel, 'chapter', @name)}">
                                                                    <span class="glyphicon glyphicon-zoom-in"/>
                                                                </a>
                                                                <xsl:text>Chapter </xsl:text>
                                                                <xsl:value-of select="position()"/>
                                                                <xsl:text>: </xsl:text>
                                                                <xsl:value-of select="@name"/>
                                                            </xsl:otherwise>
                                                        </xsl:choose>
                                                    </li>
                                                </xsl:for-each>
                                            </ul>
                                        </xsl:when>
                                        <!-- display collapsed novel -->
                                        <xsl:otherwise>
                                            <a class="button button-icon" href="{am:makeUrl('Novels', 'novel', @name)}">
                                                <span class="glyphicon glyphicon-zoom-in"/>
                                            </a>
                                            <xsl:text>Book </xsl:text>
                                            <xsl:value-of select="position()"/>
                                            <xsl:text>: </xsl:text>
                                            <xsl:value-of select="@name"/>
                                        </xsl:otherwise>
                                    </xsl:choose>
                                </li>
                            </xsl:for-each>
                        </ul>
                    </aside>
                </div>

                <div class="col-md-9">
                    <!-- novels content -->
                    <div id="novels-content" class="skin-text top-level">
                        <div>
                            <xsl:choose>
                                <xsl:when test="$content">
                                    <!-- display content -->
                                    <xsl:variable name="navigationBar">
                                        <div class="previous">
                                            <xsl:choose>
                                                <xsl:when test="$page &gt; 1">
                                                    <a class="button button-icon" href="{am:makeUrl('Novels', 'novel', $param/novel, 'chapter', $param/chapter, 'part', $param/part, 'page', am:max($page - 1, 1))}">
                                                        <span class="glyphicon glyphicon-chevron-left"/>
                                                    </a>
                                                </xsl:when>
                                                <xsl:otherwise>
                                                    <span class="disabled">
                                                        <span class="glyphicon glyphicon-chevron-left"/>
                                                    </span>
                                                </xsl:otherwise>
                                            </xsl:choose>
                                        </div>

                                        <div class="next">
                                            <xsl:choose>
                                                <xsl:when test="$page &lt; $pages">
                                                    <a class="button button-icon" href="{am:makeUrl('Novels', 'novel', $param/novel, 'chapter', $param/chapter, 'part', $param/part, 'page', am:min($page + 1, $pages))}">
                                                        <span class="glyphicon glyphicon-chevron-right"/>
                                                    </a>
                                                </xsl:when>
                                                <xsl:otherwise>
                                                    <span class="disabled">
                                                        <span class="glyphicon glyphicon-chevron-right"/>
                                                    </span>
                                                </xsl:otherwise>
                                            </xsl:choose>
                                        </div>
                                    </xsl:variable>

                                    <!-- upper navigation -->
                                    <div class="navigation">
                                        <xsl:copy-of select="$navigationBar"/>
                                        <xsl:value-of select="$page"/>
                                        <xsl:text> / </xsl:text>
                                        <xsl:value-of select="$pages"/>
                                    </div>

                                    <!-- page content -->
                                    <xsl:value-of select="$content" disable-output-escaping="yes"/>

                                    <!-- lower navigation -->
                                    <div class="navigation">
                                        <xsl:copy-of select="$navigationBar"/>
                                        <a class="button button-icon" href="{am:makeUrl('Novels', 'novel', $param/novel, 'chapter', $param/chapter, 'part', $param/part, 'page', $page)}">
                                            <span class="glyphicon glyphicon-triangle-top"/>
                                        </a>
                                    </div>

                                </xsl:when>
                                <xsl:otherwise>
                                    <!-- display welcome page -->
                                    <h3>Welcome to the Fantasy novels section</h3>

                                    <p>
                                        All novels are written by our external associate <a href="mailto:thomasteekanne@gmail.com">Lukáš Čajági</a>, and therefore all novels
                                        are exclusive property of the author.
                                    </p>
                                    <br/>
                                    <p>This novel will probably be a trilogy, the name is not known yet. The author provided us with description of the first book:</p>
                                    <p>
                                        <b>"Only the fewest people in the world have the luxury of knowing who they really are,
                                            how hard it is to find something worth living for. Now try to imagine that upon
                                            finding it, it is snatched away from you forever. What would you do? Would you care
                                            to live on while the lives of everyone around you shatter? Could your consience bear
                                            the thought of you being responsible for the whole mess? This is the story of a man
                                            who has to cope with these questions."
                                        </b>
                                    </p>
                                    <p><i>Trasymachos, former member of the Alchemist's Guild, outlawed fugitive</i></p>
                                    <br/>
                                    <p>For feedback regarding novels please use the appropriate thread in the forum's 'Novels' section.</p>
                                    <br/>
                                    <p>Please select a book, chapter and part you wish to read.</p>
                                    <br/>
                                    <p>Copyright 2008-2016 Lukáš Čajági</p>
                                </xsl:otherwise>
                            </xsl:choose>

                        </div>
                    </div>
                </div>
            </div>

        </div>
    </xsl:template>


</xsl:stylesheet>
