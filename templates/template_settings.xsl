<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
                xmlns="http://www.w3.org/1999/xhtml"
                xmlns:am="http://arcomage.netvor.sk"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:exsl="http://exslt.org/common"
                extension-element-prefixes="exsl">
<xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes" doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" />


<xsl:template match="section[. = 'Settings']">
	<xsl:variable name="param" select="$params/settings" />
	
	<xsl:variable name="settings" select="$param/current_settings" />
	<xsl:variable name="countries" select="document('countries.xml')/am:countries" />
	<xsl:variable name="timezones" select="document('timezones.xml')/am:timezones" />
	<xsl:variable name="skins" select="document('skins.xml')/am:skins" />
	<xsl:variable name="backgrounds" select="document('backgrounds.xml')/am:backgrounds" />
	<xsl:variable name="refresh_values">
		<value name="0"   text="off"        />
		<value name="10"  text="10 seconds" />
		<value name="30"  text="30 seconds" />
		<value name="60"  text="1 minute"   />
		<value name="300" text="5 minutes"  />
		<value name="600" text="10 minutes" />
	</xsl:variable>

	<div id="settings">

		<!-- user settings -->
		<div id="sett_float_left" class="skin_text">

			<h3>Profile settings</h3>

			<div><h4>Zodiac sign</h4><img height="100px" width="100px" src="img/zodiac/{$settings/Sign}.jpg" alt="sign" /><h4><xsl:value-of select="$settings/Sign"/></h4></div>

			<div><h4>Avatar</h4><img height="60px" width="60px" src="img/avatars/{$settings/Avatar}" alt="avatar" /></div>
			<p><input type="text" name="Firstname" maxlength="20" value="{$settings/Firstname}" />First name</p>
			<p><input type="text" name="Surname" maxlength="20" value="{$settings/Surname}" />Surname</p>
		
			<p><input type="text" name="Email" maxlength="30" value="{$settings/Email}" />E-mail</p>
		
			<p><input type="text" name="Imnumber" maxlength="20" value="{$settings/Imnumber}" />ICQ / IM number</p>

			<xsl:variable name="gender_types">
				<type name="none"   text="select" />
				<type name="male"   text="male"   />
				<type name="female" text="female" />
			</xsl:variable>

			<p>
				<select name="Gender">
					<xsl:for-each select="exsl:node-set($gender_types)/*">
						<option value="{@name}">
							<xsl:if test="$settings/Gender = @name">
								<xsl:attribute name="selected">selected</xsl:attribute>
							</xsl:if>
							<xsl:value-of select="@text"/>
						</option>
					</xsl:for-each>
				</select>
				<xsl:text>Gender</xsl:text>
			</p>

			<p>
				<input type="text" name="Birthday" maxlength="2" size="2" value="{$settings/Birthdate/day}" />
				<input type="text" name="Birthmonth" maxlength="2" size="2" value="{$settings/Birthdate/month}" />
				<input type="text" name="Birthyear" maxlength="4" size="4" value="{$settings/Birthdate/year}" />
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
				<img width="18px" height="12px" src="img/flags/{$settings/Country}.gif" alt="country flag" class="icon" title="{$settings/Country}" />
			</p>

			<xsl:variable name="status_types">
				<status name="none"   text="none"                   />
				<status name="ready"  text="looking for game"       />
				<status name="quick"  text="looking for quick game" />
				<status name="dnd"    text="do not disturb"         />
				<status name="newbie" text="newbie"                 />
			</xsl:variable>

			<p>
				<select name="Status">
					<xsl:for-each select="exsl:node-set($status_types)/*">
						<option value="{@name}">
							<xsl:if test="$settings/Status = @name">
								<xsl:attribute name="selected">selected</xsl:attribute>
							</xsl:if>
							<xsl:value-of select="@text"/>
						</option>
					</xsl:for-each>
				</select>
				<xsl:text>Status</xsl:text>
				<xsl:if test="$settings/Status != 'none'"><img width="20px" height="14px" src="img/{$settings/Status}.png" alt="status flag" class="icon" title="{$settings/Status}" /></xsl:if>
			</p>

			<p>Game mode flags</p>
			<p>
				<input type="checkbox" name="FriendlyFlag">
					<xsl:if test="$settings/FriendlyFlag = 'yes'">
						<xsl:attribute name="checked">checked</xsl:attribute>
					</xsl:if>
				</input>
				<xsl:text>Friendly play</xsl:text>
				<xsl:if test="$settings/FriendlyFlag = 'yes'">
					<img width="20px" height="14px" src="img/friendly_play.png" alt="friendly flag" class="icon" title="Friendly play" />
				</xsl:if>
			</p>
			<p>
				<input type="checkbox" name="BlindFlag">
					<xsl:if test="$settings/BlindFlag = 'yes'">
						<xsl:attribute name="checked">checked</xsl:attribute>
					</xsl:if>
				</input>
				<xsl:text>Hidden cards</xsl:text>
				<xsl:if test="$settings/BlindFlag = 'yes'">
					<img width="20px" height="14px" src="img/blind.png" alt="blind flag" class="icon" title="Hidden cards" />
				</xsl:if>
			</p>

			<p>Hobbies, Interests:</p>
			<p><textarea name="Hobby" rows="5" cols="30"><xsl:value-of select="$settings/Hobby"/></textarea></p>

			<xsl:if test="$param/change_own_avatar = 'yes'">
				<h4>Avatar options</h4>
				<p>
					<input type="file" name="uploadedfile" />
					<button type="submit" name="Avatar">Upload avatar</button>
					<button type="submit" name="reset_avatar">Clear avatar</button>
				</p>
			</xsl:if>
		</div>

		<!-- game settings -->
		<div id="sett_float_right" class="skin_text">
		<div>
			<h3>Account settings</h3>
			<p><button type="submit" name="user_settings">Save settings</button></p>
			<p><input type="password" name="NewPassword" maxlength="20" />New password</p>
			<p><input type="password" name="NewPassword2" maxlength="20" />Confirm password</p>
			<p><button type="submit" name="changepasswd">Change password</button></p>

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

			<xsl:variable name="filter_types">
				<filter name="none"    text="No players filters"         />
				<filter name="active"  text="Active players"             />
				<filter name="offline" text="Active and offline players" />
				<filter name="all"     text="Show all players"           />
			</xsl:variable>

			<p>
				<select name="DefaultFilter">
					<xsl:for-each select="exsl:node-set($filter_types)/*">
						<option value="{@name}">
							<xsl:if test="$settings/DefaultFilter = @name">
								<xsl:attribute name="selected">selected</xsl:attribute>
							</xsl:if>
							<xsl:value-of select="@text"/>
						</option>
					</xsl:for-each>
				</select>
				<xsl:text>Players list filter</xsl:text>
			</p>

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

			<p>
				<select name="Autorefresh">
					<xsl:for-each select="exsl:node-set($refresh_values)/*">
						<option value="{@name}">
							<xsl:if test="$settings/Autorefresh = @name">
								<xsl:attribute name="selected">selected</xsl:attribute>
							</xsl:if>
							<xsl:value-of select="@text"/>
						</option>
					</xsl:for-each>
				</select>
				<xsl:text>Auto refresh</xsl:text>
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
			<p><input type="checkbox" name="Reports"    ><xsl:if test="$settings/Reports     = 'yes'"><xsl:attribute name="checked">checked</xsl:attribute></xsl:if></input>Battle report messages</p>
			<p><input type="checkbox" name="Forum_notification"><xsl:if test="$settings/Forum_notification = 'yes'"><xsl:attribute name="checked">checked</xsl:attribute></xsl:if></input>Forum notification</p>
			<p><input type="checkbox" name="Concepts_notification"><xsl:if test="$settings/Concepts_notification = 'yes'"><xsl:attribute name="checked">checked</xsl:attribute></xsl:if></input>Concepts notification</p>
			<p><input type="checkbox" name="RandomDeck"><xsl:if test="$settings/RandomDeck = 'yes'"><xsl:attribute name="checked">checked</xsl:attribute></xsl:if></input>Random deck selection</p>
		</div>
		</div>

		<div class="clear_floats"></div>
	</div>
</xsl:template>


</xsl:stylesheet>
