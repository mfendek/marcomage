<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
                xmlns="http://www.w3.org/1999/xhtml"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes"
              doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN"
              doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"/>

  <!-- includes -->
  <xsl:include href="main.xsl"/>

  <xsl:template match="section[. = 'Registration']">
    <xsl:variable name="param" select="$params/registration"/>

    <div class="skin-text top-level registration">
      <h3 class="registration__title">Registration</h3>
      <p class="registration__label">Login name</p>
      <div>
        <input type="text" name="new_username" maxlength="20" placeholder="Username..."/>
      </div>
      <p class="registration__label">Password</p>
      <div>
        <input type="password" name="new_password" maxlength="20" placeholder="Password..."/>
      </div>
      <p class="registration__label">Confirm password</p>
      <div>
        <input type="password" name="confirm_password" maxlength="20" placeholder="Confirm password..."/>
      </div>
      <div>
        <button type="submit" name="register">Register</button>
      </div>

      <xsl:if test="$param/captcha_key != ''">
        <div class="g-recaptcha" data-sitekey="{$param/captcha_key}"/>
      </xsl:if>
    </div>
  </xsl:template>

</xsl:stylesheet>
