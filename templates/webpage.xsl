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
    <xsl:strip-space elements="*"/>

    <!-- includes -->
    <xsl:include href="main.xsl"/>


    <xsl:template match="section[. = 'Webpage']">
        <xsl:variable name="param" select="$params/webpage"/>

        <div id="web-page" class="skin-text top-level">
            <div class="row top-navbar">
                <aside id="web-page-menu">
                    <xsl:variable name="webSections">
                        <value name="Main" value="Summary"/>
                        <value name="News" value="Latest news"/>
                        <value name="Modified" value="Card changes"/>
                        <value name="Faq" value="F .   A .   Q ."/>
                        <value name="Credits" value="Hall of fame"/>
                        <value name="Archive" value="News archive"/>
                        <value name="History" value="Project history"/>
                    </xsl:variable>

                    <xsl:for-each select="exsl:node-set($webSections)/*">
                        <a class="button" href="{am:makeUrl('Webpage', 'WebSection', @name)}">
                            <xsl:if test="$param/selected = @name">
                                <xsl:attribute name="class">button marked_button</xsl:attribute>
                            </xsl:if>
                            <xsl:value-of select="@value"/>
                        </a>
                    </xsl:for-each>
                </aside>
            </div>
            <div class="row">
                <div id="web-page-content">
                    <xsl:for-each select="$param/files/*">
                        <xsl:if test="($param/recent_news_only = 'no') or (position() &lt;= 4)">

                            <!-- TODO: check if file exists and print error if not -->
                            <xsl:variable name="entry" select="document(am:urlEncode(concat('pages/', text())))/am:entry"/>

                            <xsl:if test="string($entry/am:date/text())">
                                <div class="date-time">
                                    <xsl:copy-of select="am:dateTime($entry/am:date/text(), $param/timezone)"/>
                                </div>
                            </xsl:if>

                            <xsl:if test="string($entry/am:title/text())">
                                <h3>
                                    <xsl:value-of select="$entry/am:title/text()"/>
                                </h3>
                            </xsl:if>

                            <div>
                                <xsl:copy-of select="$entry/am:content/node()"/>
                                <hr/>
                            </div>
                        </xsl:if>

                    </xsl:for-each>
                </div>
            </div>
        </div>
    </xsl:template>


</xsl:stylesheet>
