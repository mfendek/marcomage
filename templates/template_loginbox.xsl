<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
                xmlns="http://www.w3.org/1999/xhtml"
                xmlns:am="http://arcomage.netvor.sk"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes" doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" />


<xsl:template name="loginbox">
	<xsl:variable name="param" select="$params/loginbox" />

	<div>

	<div id="login_box">
	<div id="lbox_float_left">
	<div>

	<p>Login name</p>
	<div><input type="text" name="Username" maxlength="20" tabindex="1" /></div>
	<p>Password</p>
	<div><input type="password" name="Password" maxlength="20" tabindex="2" /></div>
	<div><input type="hidden" name="Remember" value="yes" /></div>
	<div>
	<input type="submit" name="Login" value="Login" tabindex="3" />
	<input type="submit" name="Registration" value="Register" tabindex="4" />
	</div>

	<xsl:if test="$param/error_msg != ''">
		<p class="information_trans" style="color: red"><xsl:value-of select="$param/error_msg"/></p>
	</xsl:if>
	<xsl:if test="$param/warning_msg != ''">
		<p class="information_trans" style="color: yellow"><xsl:value-of select="$param/warning_msg"/></p>
	</xsl:if>
	<xsl:if test="$param/info_msg != ''">
		<p class="information_trans" style="color: lime"><xsl:value-of select="$param/info_msg"/></p>
	</xsl:if>
	<xsl:if test="($param/error_msg = '') and ($param/warning_msg = '') and ($param/info_msg = '')">
		<p style="height: 1.2em;" class="information_trans"></p>
	</xsl:if>

	</div>
	</div>

	<div id="lbox_float_right">
	<div>

	<h1>MArcomage</h1>
	<h2>Free multiplayer on-line fantasy card game</h2>

	</div>
	</div>

	<div class="clear_floats" />

	</div>
	</div>
</xsl:template>


</xsl:stylesheet>
