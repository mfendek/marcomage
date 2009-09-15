<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
                xmlns="http://www.w3.org/1999/xhtml"
                xmlns:am="http://arcomage.netvor.sk"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:exsl="http://exslt.org/common"
                extension-element-prefixes="exsl">
<xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes" doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" />


<xsl:template match="section[. = 'Profile']">
	<xsl:variable name="param" select="$params/profile" />
	<xsl:variable name="opponent" select="$param/PlayerName" />
	<xsl:variable name="activedecks" select="count($param/decks)" />

	<div id="details">
	<div class="skin_text">
		<h3><xsl:value-of select="$param/PlayerName"/>'s details</h3>

		<div class="details_float_right">
			<p>Zodiac sign</p>
			<img height="100px" width="100px" src="img/zodiac/{$param/Sign}.jpg" alt="sign" />
			<p><xsl:value-of select="$param/Sign"/></p>
		</div>

		<div class="details_float_right">
			<p>Avatar</p>
			<img height="60px" width="60px" src="img/avatars/{$param/Avatar}" alt="avatar" />
		</div>
		
		<p>First name: <span class="detail_value"><xsl:value-of select="$param/Firstname"/></span></p>
		<p>Surname: <span class="detail_value"><xsl:value-of select="$param/Surname"/></span></p>

		<xsl:variable name="gender_color">
			<xsl:choose>
				<xsl:when test="$param/Gender = 'male'">blue</xsl:when>
				<xsl:when test="$param/Gender = 'female'">HotPink</xsl:when>
				<xsl:otherwise>green</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
		
		<p>Gender: <span style="color: {$gender_color}"><xsl:value-of select="$param/Gender"/></span></p>
		<p>E-mail: <span class="detail_value"><xsl:value-of select="$param/Email"/></span></p>
		<p>ICQ / IM: <span class="detail_value"><xsl:value-of select="$param/Imnumber"/></span></p>
		<p>Date of birth (dd-mm-yyyy): <span class="detail_value"><xsl:value-of select="$param/Birthdate"/></span></p>
		<p>Age: <span class="detail_value"><xsl:value-of select="$param/Age"/></span></p>
		<p>Rank: <span class="detail_value"><xsl:value-of select="$param/PlayerType"/></span></p>
		<p>Country: <img width="18px" height="12px" src="img/flags/{$param/Country}.gif" alt="country flag" class="country_flag" /> <span class="detail_value"><xsl:value-of select="$param/Country"/></span></p>
		<p>Registered on: 
			<span class="detail_value">
				<xsl:choose>
					<xsl:when test="$param/Registered != '0000-00-00 00:00:00'"><xsl:value-of select="am:datetime($param/Registered, $param/timezone)"/></xsl:when>
				<xsl:otherwise>Before 18. August, 2009</xsl:otherwise>
				</xsl:choose>
			</span>
		</p>
		<p>Last seen on: 
			<span class="detail_value">
				<xsl:choose>
						<xsl:when test="$param/LastQuery != '0000-00-00 00:00:00'"><xsl:value-of select="am:datetime($param/LastQuery, $param/timezone)"/></xsl:when>
					<xsl:otherwise>n/a</xsl:otherwise>
				</xsl:choose>
			</span>
		</p>

		<p>Hobbies, Interests:</p>
		<div class="detail_value"><xsl:copy-of select="am:textencode($param/Hobby)"/></div>

<!--		
		check if the player is allowed to challenge this opponent:
		- can't have more than MAX_GAMES active games + initiated challenges + received challenges
		- can't be in the $challengefrom['Player2'] or in the $activegames['Player1'] (['Player2'] is allowed)
		- can't play without a ready deck
		- can't challenge self
-->
		<xsl:if test="$param/send_challenges = 'yes' and $opponent != $param/CurPlayerName">
			<h4>Challenge options</h4>
			
			<xsl:choose>
			
				<xsl:when test="$param/waitingforack = 'yes'">
					<p style="color: blue">game over, waiting for opponent</p>
				</xsl:when>
				
				<xsl:when test="$param/playingagainst = 'yes'">
					<p style="color: green">game already in progress</p>
				</xsl:when>
				
				<xsl:when test="$param/challenged = 'yes'">
					<xsl:variable name="challenge" select="$param/challenge"/>
					<p>
						<span style="color: red">waiting for answer</span>
						<input type="submit" name="withdraw_challenge[{am:urlencode($opponent)}]" value="Cancel" />
					</p>
					
					<xsl:if test="$param/challenge/Content != ''">
						<div class="challenge_text">
							<xsl:copy-of select="am:textencode($param/challenge/Content)" />
						</div>
					</xsl:if>
					<p style="color: green">Challenged on <xsl:value-of select="am:datetime($param/challenge/Created, $param/timezone)"/></p>
				</xsl:when>
				
				<xsl:when test="$activedecks &gt; 0 and $param/free_slots &gt; 0">
					<p>
						<xsl:choose>
							<xsl:when test="$param/challenging = 'no'">
								<input type="submit" name="prepare_challenge[{am:urlencode($opponent)}]" value="Challenge this user" />
							</xsl:when>
							<xsl:otherwise>
								<input type="submit" name="send_challenge[{am:urlencode($opponent)}]" value="Send challenge" />
								<select name="ChallengeDeck" size="1">
									<xsl:for-each select="$param/decks/*">
										<option value="{am:urlencode(text())}"><xsl:value-of select="text()"/></option>
									</xsl:for-each>
								</select>
								<textarea name="Content" rows="10" cols="50"></textarea>
							</xsl:otherwise>
						</xsl:choose>
					</p>
				</xsl:when>
				
				<xsl:when test="$activedecks = 0">
					<p class="information_line warning">You need at least one ready deck to challenge other players.</p>
				</xsl:when>
				
				<xsl:when test="$param/free_slots = 0">
					<p style="color: yellow">You cannot initiate any more games.</p>
				</xsl:when>
				
			</xsl:choose>
		</xsl:if>
		
		<xsl:if test="$param/messages = 'yes'">
			<h4>Message options</h4>
			<input type="submit" name="message_create[{am:urlencode($opponent)}]" value="Send message" />
		</xsl:if>
		
		<xsl:if test="$param/change_rights = 'yes'">
			<h4>Change access rights</h4>			
			<input type="submit" name="change_access[{am:urlencode($opponent)}]" value="Change access rights" />
			<xsl:variable name="user_types">
				<type name="moderator" text="Moderator"/>
				<type name="user"      text="User"     />
				<type name="squashed"  text="Squashed" />
				<type name="limited"   text="Limited"  />
				<type name="banned"    text="Banned"   />
			</xsl:variable>
			<select name="new_access" size="1">
				<xsl:for-each select="exsl:node-set($user_types)/*">
					<option value="{@name}">
						<xsl:if test="$param/PlayerType = @name"><xsl:attribute name="selected">selected</xsl:attribute></xsl:if>
						<xsl:value-of select="@text"/>
					</option>
				</xsl:for-each>
			</select>
		</xsl:if>
		
		<xsl:if test="$param/system_notification = 'yes'">
			<h4>System notification</h4>
			<input type="submit" name="system_notification[{am:urlencode($opponent)}]" value="Send system notification" />
		</xsl:if>

		<xsl:if test="$param/change_all_avatar = 'yes'">
			<h4>Reset avatar</h4>
			<input type="submit" name="reset_avatar_remote[{am:urlencode($opponent)}]" value="Reset" />
		</xsl:if>

	</div>
	</div>
</xsl:template>


</xsl:stylesheet>
