<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
                xmlns="http://www.w3.org/1999/xhtml"
                xmlns:am="http://arcomage.netvor.sk"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes" doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" />


<xsl:template match="section[. = 'Registration']">
	<xsl:variable name="param" select="$params/registration" />

	<div id="registration">

	<h3>Registration</h3>
	<p>Login name</p>
	<div><input type="text" name="NewUsername" maxlength="20" /></div>
	<p>Password</p>
	<div><input type="password" name="NewPassword" maxlength="20" /></div>
	<p>Confirm password</p>
	<div><input type="password" name="NewPassword2" maxlength="20" /></div>
	<div>
	<button type="submit" name="Register">Register</button>
	<button type="submit" name="ReturnToLogin">Back</button>
	</div>

	</div>
</xsl:template>


</xsl:stylesheet>
