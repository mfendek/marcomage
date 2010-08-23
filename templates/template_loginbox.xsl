<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
                xmlns="http://www.w3.org/1999/xhtml"
                xmlns:am="http://arcomage.netvor.sk"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:exsl="http://exslt.org/common"
                xmlns:php="http://php.net/xsl"
                extension-element-prefixes="exsl php">
<xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes" doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" />


<xsl:template name="loginbox">
	<xsl:variable name="param" select="$params/loginbox" />

	<div id="login_box">

	<div id="login_inputs">
		<p>
			<img src="img/username.png" width="25px" height="20px" alt="username" title="username" />
			<input type="text" name="Username" maxlength="20" tabindex="1" />
		</p>
		<p>
			<img src="img/password.png" width="25px" height="20px" alt="password" title="password" />
			<input type="password" name="Password" maxlength="20" tabindex="2" />
		</p>
		<p>
			<input type="submit" name="Login" value="Login" tabindex="3" />
			<input type="submit" name="Registration" value="Register" tabindex="4" />
		</p>
	</div>

	<h1>MArcomage</h1>
	<h2>Free multiplayer on-line fantasy card game</h2>

	<div id="login_message">
		<xsl:if test="$param/error_msg != ''">
			<p class="error"><xsl:value-of select="$param/error_msg"/></p>
		</xsl:if>
		<xsl:if test="$param/warning_msg != ''">
			<p class="warning"><xsl:value-of select="$param/warning_msg"/></p>
		</xsl:if>
		<xsl:if test="$param/info_msg != ''">
			<p class="info"><xsl:value-of select="$param/info_msg"/></p>
		</xsl:if>
	</div>

	<!-- sections menubar -->
	<div id="sections">
		<xsl:variable name="sections">
			<value name="Webpage" value="Introduction"   />
			<value name="Help"    value="Game manual"    />
			<value name="Novels"  value="Fantasy novels" />
		</xsl:variable>
		<xsl:for-each select="exsl:node-set($sections)/*">
			<a class="button" href="{php:functionString('makeurl', @name)}" >
				<xsl:if test="$param/current = @name">
					<xsl:attribute name="class">button pushed</xsl:attribute>
				</xsl:if>
				<xsl:value-of select="@value"/>
			</a>
		</xsl:for-each>
	</div>

	</div>
</xsl:template>


</xsl:stylesheet>
