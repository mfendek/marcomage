<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
                xmlns="http://www.w3.org/1999/xhtml"
                xmlns:am="http://arcomage.net"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes"
                doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN"
                doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"/>

    <!-- includes -->
    <xsl:include href="main.xsl"/>


    <xsl:template match="section[. = 'Error']">
        <xsl:variable name="param" select="$params/error"/>
        <div id="error-page" class="skin-text top-level">
            <h1>Error has occurred</h1>
            <div>
                <p><img class="img-rounded" width="350" height="212" src="img/error.gif" alt=""/></p>
                <p>Something went wrong. Error details are listed below:</p>
                <p class="error-details"><xsl:value-of select="$param/message"/></p>
            </div>
        </div>
    </xsl:template>


</xsl:stylesheet>
