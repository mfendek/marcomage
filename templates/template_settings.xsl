<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
                xmlns="http://www.w3.org/1999/xhtml"
                xmlns:am="http://arcomage.netvor.sk"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes" doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" />


<xsl:template match="section[. = 'Settings']">
	<xsl:variable name="param" select="$params/settings" />
	
	<xsl:variable name="settings" select="$param/current_settings" />
	<xsl:variable name="countries" select="document('countries.xml')/am:countries" />
	<xsl:variable name="timezones" select="document('timezones.xml')/am:timezones" />
	<xsl:variable name="skins" select="document('skins.xml')/am:skins" />
	<xsl:variable name="backgrounds" select="document('backgrounds.xml')/am:backgrounds" />

	<div id="settings">

		<!-- user settings -->
		<div id="sett_float_left" class="skin_text">

			<h3>Profile settings</h3>

			<div><h4>Zodiac sign</h4><img height="100px" width="100px" src="img/zodiac/{$settings/Sign}.jpg" alt="sign" /><h4><xsl:value-of select="$settings/Sign"/></h4></div>

			<div><h4>Avatar</h4><img height="60px" width="60px" src="img/avatars/{$settings/Avatar}" alt="avatar" /></div>
			<p><input type="submit" name="user_settings" value="Save user settings" /></p>
			<p><input type="text" name="Firstname" maxlength="20" value="{$settings/Firstname}" />First name</p>
			<p><input type="text" name="Surname" maxlength="20" value="{$settings/Surname}" />Surname</p>
		
			<p><input type="text" name="Email" maxlength="30" value="{$settings/Email}" />E-mail</p>
		
			<p><input type="text" name="Imnumber" maxlength="20" value="{$settings/Imnumber}" />ICQ / IM number</p>
		
			<p>
				<select name="Gender">
					<option value=""><xsl:if test="$settings/Gender = 'none'"><xsl:attribute name="selected">selected</xsl:attribute></xsl:if>select</option>
					<option value="male"><xsl:if test="$settings/Gender = 'male'"><xsl:attribute name="selected">selected</xsl:attribute></xsl:if>male</option>
					<option value="female"><xsl:if test="$settings/Gender = 'female'"><xsl:attribute name="selected">selected</xsl:attribute></xsl:if>female</option>
				</select>
				<xsl:text>Gender</xsl:text>
			</p>

			<p>
				<input type="text" name="Birthday" maxlength="2" size="2" value="{$settings/Birthdate/day}"/>
				<input type="text" name="Birthmonth" maxlength="2" size="2" value="{$settings/Birthdate/month}"/>
				<input type="text" name="Birthyear" maxlength="4" size="4" value="{$settings/Birthdate/year}"/>
				<xsl:text>Date of birth (dd-mm-yyyy)</xsl:text>
			</p>

			<p>Age: <xsl:value-of select="$settings/Age"/></p>
			
			<p>Rank: <xsl:value-of select="$param/PlayerType"/></p>

			<p>
				<select name="Country">
					<option value="Unknown">I'm a pirate - no country</option>
					<xsl:for-each select="$countries/am:country">
						<option value="{text()}">
							<xsl:if test="$settings/Country = text()"><xsl:attribute name="selected">selected</xsl:attribute></xsl:if>
							<xsl:value-of select="text()"/>
						</option>
					</xsl:for-each>
				</select>
				<xsl:text>Country</xsl:text>
				<img width="18px" height="12px" src="img/flags/{$settings/Country}.gif" alt="country flag" class="country_flag" />
			</p>

			<p>Hobbies, Interests:</p>
			<p><textarea name="Hobby" rows="5" cols="30"><xsl:value-of select="$settings/Hobby"/></textarea></p>

			<xsl:if test="$param/change_own_avatar = 'yes'">
				<h4>Avatar options</h4>
				<p><input name="uploadedfile" type="file" style="color: white"/><input type="submit" name="Avatar" value="Upload avatar" /><input type="submit" name="reset_avatar" value="Clear avatar" /></p>
			</xsl:if>
		</div>

		<!-- game settings -->
		<div id="sett_float_right" class="skin_text">
		<div>
			<h3>Account settings</h3>
			<p><input type="submit" name="game_settings" value="Save game settings" /><input type="submit" name="reset_notification" value= "Reset notification" /></p>
			<p><input type="password" name="NewPassword" maxlength="20" />New password</p>
			<p><input type="password" name="NewPassword2" maxlength="20" />Confirm password</p>
			<p><input type="submit" name="changepasswd" value="Change password" /></p>

			<p>
				<select name="Timezone">
					<xsl:for-each select="$timezones/am:timezone">
						<option value="{am:offset}">
							<xsl:if test="$settings/Timezone = am:offset"><xsl:attribute name="selected">selected</xsl:attribute></xsl:if>
							<xsl:text>GMT </xsl:text><xsl:value-of select="am:offset"/> (<xsl:value-of select="am:name"/>)
					</option>
					</xsl:for-each>
				</select>
				<xsl:text>Time zone</xsl:text>
			</p>

			<h4>Layout options</h4>
			<xsl:if test="$settings/Background != 0">
				<div id="preview">
					<h4>Background image</h4>
					<img width="204px" height="152px" src="img/backgrounds/bg_{$settings/Background}.jpg" alt="" />
				</div>
			</xsl:if>

			<p>
				<select name="Skin">
					<xsl:for-each select="$skins/am:skin">
						<xsl:sort select="am:name" order="ascending"/>
						<option value="{am:value}">
							<xsl:if test="$settings/Skin = am:value">
								<xsl:attribute name="selected">selected</xsl:attribute>
							</xsl:if>
							<xsl:value-of select="am:name"/>
						</option>
					</xsl:for-each>
				</select>
				<xsl:text>Skin selection</xsl:text>
			</p>

			<p>
				<select name="Background">
					<xsl:for-each select="$backgrounds/am:background">
						<xsl:sort select="am:name" order="ascending"/>
						<option value="{am:value}">
							<xsl:if test="$settings/Background = am:value">
								<xsl:attribute name="selected">selected</xsl:attribute>
							</xsl:if>
							<xsl:value-of select="am:name"/>
						</option>
					</xsl:for-each>
				</select>
				<xsl:text>Game background</xsl:text>
			</p>

			<p><input type="checkbox" name="GamesDetails"><xsl:if test="$settings/GamesDetails = 'yes'"><xsl:attribute name="checked">checked</xsl:attribute></xsl:if></input>Advanced games list</p>
			<p><input type="checkbox" name="Minimize"   ><xsl:if test="$settings/Minimize    = 'yes'"><xsl:attribute name="checked">checked</xsl:attribute></xsl:if></input>Minimized game view</p>
			<p><input type="checkbox" name="Cardtext"   ><xsl:if test="$settings/Cardtext    = 'yes'"><xsl:attribute name="checked">checked</xsl:attribute></xsl:if></input>Show card text</p>
			<p><input type="checkbox" name="Images"     ><xsl:if test="$settings/Images      = 'yes'"><xsl:attribute name="checked">checked</xsl:attribute></xsl:if></input>Show card images</p>
			<p><input type="checkbox" name="Keywords"   ><xsl:if test="$settings/Keywords    = 'yes'"><xsl:attribute name="checked">checked</xsl:attribute></xsl:if></input>Show card keywords</p>
			<p><input type="checkbox" name="Nationality"><xsl:if test="$settings/Nationality = 'yes'"><xsl:attribute name="checked">checked</xsl:attribute></xsl:if></input>Show nationality in players list</p>
			<p><input type="checkbox" name="Chatorder"  ><xsl:if test="$settings/Chatorder   = 'yes'"><xsl:attribute name="checked">checked</xsl:attribute></xsl:if></input>Reverse chat message order</p>
			<p><input type="checkbox" name="Avatargame" ><xsl:if test="$settings/Avatargame  = 'yes'"><xsl:attribute name="checked">checked</xsl:attribute></xsl:if></input>Show avatar in game</p>
			<p><input type="checkbox" name="Avatarlist" ><xsl:if test="$settings/Avatarlist  = 'yes'"><xsl:attribute name="checked">checked</xsl:attribute></xsl:if></input>Show avatar in players list</p>
			<p><input type="checkbox" name="Correction" ><xsl:if test="$settings/Correction  = 'yes'"><xsl:attribute name="checked">checked</xsl:attribute></xsl:if></input>Avatar display correction for chat (Firefox 2.x only)</p>
			<p><input type="checkbox" name="OldCardLook"><xsl:if test="$settings/OldCardLook = 'yes'"><xsl:attribute name="checked">checked</xsl:attribute></xsl:if></input>Old card appearance</p>
			<p><input type="checkbox" name="Reports"    ><xsl:if test="$settings/Reports     = 'yes'"><xsl:attribute name="checked">checked</xsl:attribute></xsl:if></input>System messages</p>
			<p><input type="checkbox" name="Forum_notification"><xsl:if test="$settings/Forum_notification = 'yes'"><xsl:attribute name="checked">checked</xsl:attribute></xsl:if></input>Forum notification</p>
			<p><input type="checkbox" name="Concepts_notification"><xsl:if test="$settings/Concepts_notification = 'yes'"><xsl:attribute name="checked">checked</xsl:attribute></xsl:if></input>Concepts notification</p>
		</div>
		</div>

		<div class="clear_floats"></div>
	</div>
</xsl:template>


</xsl:stylesheet>
