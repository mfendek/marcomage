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

  <xsl:template match="section[. = 'Help']">
    <xsl:variable name="param" select="$params/help"/>

    <xsl:variable name="gameManual" select="document('../xml/help.xml')/am:help"/>
    <xsl:variable name="content" select="$gameManual/am:part[@name=$param/part]"/>

    <div class="side-navbar">
      <div class="row">
        <div class="col-sm-3">
          <!-- game manual menu -->
          <aside class="skin-label top-level side-navbar__menu-items">
            <h3>Game manual</h3>
            <ul>
              <xsl:for-each select="exsl:node-set($gameManual)/*">
                <li>
                  <a href="{am:makeUrl('Help', 'help_part', @name)}">
                    <xsl:value-of select="@name"/>
                  </a>
                </li>
              </xsl:for-each>
            </ul>

          </aside>
        </div>
        <div class="col-sm-9">
          <!-- game manual content -->
          <div class="skin-text top-level side-navbar__content">
            <div>
              <xsl:choose>
                <xsl:when test="$content">
                  <h3>
                    <xsl:value-of select="$param/part"/>
                  </h3>
                  <xsl:value-of select="$content" disable-output-escaping="yes"/>
                </xsl:when>
                <xsl:otherwise>
                  <h3>Selected page not found</h3>
                </xsl:otherwise>
              </xsl:choose>
            </div>
          </div>
        </div>
      </div>
    </div>

  </xsl:template>

</xsl:stylesheet>
