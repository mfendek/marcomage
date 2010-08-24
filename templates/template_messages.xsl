<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
                xmlns="http://www.w3.org/1999/xhtml"
                xmlns:am="http://arcomage.netvor.sk"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:exsl="http://exslt.org/common"
                xmlns:php="http://php.net/xsl"
                extension-element-prefixes="exsl php">
<xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes" doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" />


<xsl:template match="section[. = 'Messages']">
	<xsl:variable name="param" select="$params/messages" />

	<div id="message_section">

	<!-- begin challenges -->

	<div id="challenges" class="skin_label">

	<h3>Challenges</h3>

	<xsl:if test="$param/deck_count = 0">
		<p class="information_line warning">You need at least one ready deck to accept challenges.</p>
	</xsl:if>

	<xsl:if test="$param/free_slots = 0">
		<p class="information_line warning">You cannot start any more games.</p>
	</xsl:if>

	<p>
		<xsl:variable name="challenge_sections">
			<value name="incoming" value="Incoming" />
			<value name="outgoing" value="Outgoing" />
		</xsl:variable>
		<xsl:for-each select="exsl:node-set($challenge_sections)/*">
			<a class="button" href="{php:functionString('makeurl', 'Messages', 'challengebox', @name, 'CurrentLocation', $param/current_location)}">
				<xsl:if test="$param/current_subsection = @name">
					<xsl:attribute name="class">button pushed</xsl:attribute>
				</xsl:if>
				<xsl:value-of select="@value"/>
			</a>
		</xsl:for-each>
	</p>

	<!-- selected deck -->
	<xsl:if test="($param/current_subsection = 'incoming') and ($param/deck_count &gt; 0)">
		<p>
			<xsl:text>Select deck </xsl:text>
			<select name="AcceptDeck" size="1">
				<xsl:if test="$param/RandomDeck = 'yes'">
					<option value="{am:urlencode($param/random_deck)}">select random</option>
				</xsl:if>
				<xsl:for-each select="$param/decks/*">
					<option value="{am:urlencode(text())}"><xsl:value-of select="text()"/></option>
				</xsl:for-each>
			</select>
		</p>
	</xsl:if>

	<xsl:choose>
		<xsl:when test="$param/challenges_count &gt; 0">
			<div class="challenge_box">
				<xsl:for-each select="$param/challenges/*">
					<div class="skin_text">
						<xsl:choose>
							<xsl:when test="$param/current_subsection = 'incoming'">
								<p>
									<span>
										<xsl:if test="Online = 'yes'">
											<xsl:attribute name="class">p_online</xsl:attribute>
										</xsl:if>
										<a class="profile" href="{php:functionString('makeurl', 'Profile', 'Profile', Author)}"><xsl:value-of select="Author"/></a>
									</span>
									<xsl:text> has challenged you on </xsl:text>
									<span><xsl:value-of select="am:datetime(Created, $param/timezone)"/></span>
								</p>
								<xsl:if test="Content != ''">
									<div class="challenge_content"><xsl:value-of select="am:BBCode_parse_extended(Content)" disable-output-escaping="yes" /></div>
								</xsl:if>
								<p>
									<xsl:if test="($param/deck_count &gt; 0) and ($param/free_slots &gt; 0) and ($param/accept_challenges = 'yes')">
										<button type="submit" name="accept_challenge" value="{GameID}">Accept</button>
									</xsl:if>
									<button type="submit" name="reject_challenge" value="{GameID}">Reject</button>
								</p>
							</xsl:when>
							<xsl:when test="$param/current_subsection = 'outgoing'">
								<p>
									<xsl:text>You challenged </xsl:text>
									<span>
										<xsl:if test="Online = 'yes'">
											<xsl:attribute name="class">p_online</xsl:attribute>
										</xsl:if>
										<a class="profile" href="{php:functionString('makeurl', 'Profile', 'Profile', Recipient)}"><xsl:value-of select="Recipient"/></a>
									</span>
									<xsl:text> on </xsl:text>
									<span><xsl:value-of select="am:datetime(Created, $param/timezone)"/></span>
								</p>
								<xsl:if test="Content != ''">
									<div class="challenge_content"><xsl:value-of select="am:BBCode_parse_extended(Content)" disable-output-escaping="yes" /></div>
								</xsl:if>
								<p><button type="submit" name="withdraw_challenge2" value="{GameID}">Withdraw challenge</button></p>
							</xsl:when>
						</xsl:choose>
					</div>
				</xsl:for-each>
			</div>
		</xsl:when>
		<xsl:otherwise>
			<p class="information_line info">You have no <xsl:value-of select="$param/current_subsection"/> challenges.</p>
		</xsl:otherwise>
	</xsl:choose>

	</div>

	<!-- end challenges -->

	<!-- begin messages -->

	<div id="messages" class="skin_label">

	<h3>Messages</h3>

	<!-- begin buttons and filters -->

	<p>
		<xsl:variable name="message_sections">
			<value name="inbox"     value="Inbox"     />
			<value name="sent_mail" value="Sent mail" />
			<value name="all_mail"  value="All mail"  />
		</xsl:variable>
		<xsl:for-each select="exsl:node-set($message_sections)/*">
			<xsl:if test="@name != 'all_mail' or $param/see_all_messages = 'yes'" >
				<a class="button" href="{php:functionString('makeurl', 'Messages', 'challengebox', $param/current_subsection, 'CurrentLocation', @name)}">
					<xsl:if test="$param/current_location = @name">
						<xsl:attribute name="class">button pushed</xsl:attribute>
					</xsl:if>
					<xsl:value-of select="@value"/>
				</a>
			</xsl:if>
		</xsl:for-each>
	</p>

	<div class="filters">
		<!-- date filter -->
		<xsl:variable name="dates">
			<value name="No date filter" value="none" />
			<value name="1 day"          value="1"    />
			<value name="2 days"         value="2"    />
			<value name="5 days"         value="5"    />
			<value name="1 week"         value="7"    />
			<value name="2 weeks"        value="14"   />
			<value name="3 weeks"        value="21"   />
			<value name="1 month"        value="30"   />
			<value name="3 months"       value="91"   />
			<value name="6 months"       value="182"  />
			<value name="1 year"         value="365"  />
		</xsl:variable>
		<xsl:copy-of select="am:htmlSelectBox('date_filter', $param/date_val, $dates, '')"/>

		<!-- name filter -->
		<xsl:if test="$param/messages_count &gt; 0">
			<xsl:variable name="authors">
				<value name="No name filter" value="none" />
			</xsl:variable>
			<xsl:copy-of select="am:htmlSelectBox('name_filter', $param/name_val, $authors, $param/name_filter)"/>
		</xsl:if>

		<button type="submit" name="message_filter">Apply filters</button>
	</div>

	<div class="filters">
	<!-- upper navigation -->
		<xsl:if test="$param/page_count &gt; 0">
			<xsl:copy-of select="am:upper_navigation($param/page_count, $param/current_page, 'messages')"/>

			<xsl:if test="$param/current_location != 'all_mail'">
				<button type="submit" name="Delete_mass">Delete selected</button>
			</xsl:if>
		</xsl:if>

	<!-- end buttons and filters -->
	</div>

	<xsl:if test="($param/messages_count = 0) and (($param/date_val != 'none') or ($param/name_val != 'none'))">
		<p class="information_line warning">No messages matched selected criteria.</p>
	</xsl:if>

	<xsl:if test="($param/messages_count = 0) and ($param/date_val = 'none') and ($param/name_val = 'none')">
		<p class="information_line info">You have no messages.</p>
	</xsl:if>

	<!-- begin messages table -->

	<xsl:if test="$param/messages_count &gt; 0">
		<table cellspacing="0" class="skin_text">
			<!-- begin table header -->
			<tr>
				<th>
					<xsl:choose>
						<xsl:when test="$param/current_location = 'sent_mail'">
							<p>
								<xsl:text>To</xsl:text>
								<button class="small_button" type="submit" value="Recipient" >
									<xsl:if test="$param/current_condition = 'Recipient'">
										<xsl:attribute name="class">small_button pushed</xsl:attribute>
									</xsl:if>
									<xsl:choose>
										<xsl:when test="(($param/current_condition = 'Recipient') and ($param/current_order = 'DESC'))">
											<xsl:attribute name="name">mes_ord_asc</xsl:attribute>
											<xsl:text>\/</xsl:text>
										</xsl:when>
										<xsl:otherwise>
											<xsl:attribute name="name">mes_ord_desc</xsl:attribute>
											<xsl:text>/\</xsl:text>
										</xsl:otherwise>
									</xsl:choose>
								</button>
							</p>
						</xsl:when>
						<xsl:otherwise>
							<p>
								<xsl:text>From</xsl:text>
								<button class="small_button" type="submit" value="Author" >
									<xsl:if test="$param/current_condition = 'Author'">
										<xsl:attribute name="class">small_button pushed</xsl:attribute>
									</xsl:if>
									<xsl:choose>
										<xsl:when test="(($param/current_condition = 'Author') and ($param/current_order = 'DESC'))">
											<xsl:attribute name="name">mes_ord_asc</xsl:attribute>
											<xsl:text>\/</xsl:text>
										</xsl:when>
										<xsl:otherwise>
											<xsl:attribute name="name">mes_ord_desc</xsl:attribute>
											<xsl:text>/\</xsl:text>
										</xsl:otherwise>
									</xsl:choose>
								</button>
							</p>
						</xsl:otherwise>
					</xsl:choose>
				</th>
				<xsl:if test="$param/current_location = 'all_mail'">
					<th><p>To</p></th>
				</xsl:if>
				<th><p>Subject</p></th>
				<th>
					<p>
						<xsl:text>Sent on</xsl:text>
						<button class="small_button" type="submit" value="Created" >
							<xsl:if test="$param/current_condition = 'Created'">
								<xsl:attribute name="class">small_button pushed</xsl:attribute>
							</xsl:if>
							<xsl:choose>
								<xsl:when test="(($param/current_condition = 'Created') and ($param/current_order = 'DESC'))">
									<xsl:attribute name="name">mes_ord_asc</xsl:attribute>
									<xsl:text>\/</xsl:text>
								</xsl:when>
								<xsl:otherwise>
									<xsl:attribute name="name">mes_ord_desc</xsl:attribute>
									<xsl:text>/\</xsl:text>
								</xsl:otherwise>
							</xsl:choose>
						</button>
					</p>
				</th>
				<th></th>
			</tr>
			<!-- end table header -->

			<!-- begin table body -->
			<xsl:for-each select="$param/messages/*">
				<tr class="table_row">
					<xsl:if test="$param/current_location = 'inbox'">
						<xsl:choose>
							<!-- TODO format time to seconds and independant of user timezone -->
							<xsl:when test="Unread = 'yes' and am:datediff(Created, $param/notification) &lt;= 0">
								<xsl:attribute name="class">table_row new_message</xsl:attribute>
							</xsl:when>
							<xsl:when test="Unread = 'yes'">
								<xsl:attribute name="class">table_row unread</xsl:attribute>
							</xsl:when>
							<xsl:when test="Author = $param/system_name">
								<xsl:attribute name="class">table_row system_message</xsl:attribute>
							</xsl:when>
						</xsl:choose>
					</xsl:if>
					<td>
						<p>
							<xsl:choose>
								<xsl:when test="$param/current_location = 'sent_mail'">
									<xsl:value-of select="Recipient"/>
								</xsl:when>
								<xsl:otherwise>
									<xsl:value-of select="Author"/>
								</xsl:otherwise>
							</xsl:choose>
						</p>
					</td>
					<xsl:if test="$param/current_location = 'all_mail'">
						<td><p><xsl:value-of select="Recipient"/></p></td>
					</xsl:if>
					<td><p><xsl:value-of select="Subject"/></p></td>
					<td><p><xsl:value-of select="am:datetime(Created, $param/timezone)"/></p></td>
					<td>
						<p style="text-align: left">
							<button class="small_button" type="submit" value="{MessageID}" >
								<xsl:choose>
									<xsl:when test="$param/current_location = 'all_mail'">
										<xsl:attribute name="name">message_retrieve</xsl:attribute>
									</xsl:when>
									<xsl:otherwise>
										<xsl:attribute name="name">message_details</xsl:attribute>
									</xsl:otherwise>
								</xsl:choose>
								<xsl:text>+</xsl:text>
							</button>
							<xsl:if test="$param/current_location != 'all_mail'">
								<button class="small_button" type="submit" name="message_delete" value="{MessageID}">D</button>
								<input type="checkbox" class="table_checkbox" name="Mass_delete_{position()}[{MessageID}]" />
							</xsl:if>
							<xsl:if test="(($param/send_messages = 'yes') and ($param/current_location = 'inbox') and (Author != $param/system_name) and (Author != $param/PlayerName))">
								<button class="small_button" type="submit" name="message_create" value="{Author}">R</button>
							</xsl:if>
						</p>
					</td>
				</tr>
			</xsl:for-each>
			<!-- end table body -->
		</table>
	</xsl:if>

	<!-- end messages table -->

	</div>

	<!-- end messages -->

	<div class="clear_floats"></div>

	<input type="hidden" name="challengebox" value="{$param/current_subsection}" />
	<input type="hidden" name="CurrentLocation" value="{$param/current_location}" />
	<input type="hidden" name="CurrentMesPage" value="{$param/current_page}" />
	<input type="hidden" name="CurrentOrd" value="{$param/current_order}" />
	<input type="hidden" name="CurrentCond" value="{$param/current_condition}" />

	</div>

</xsl:template>


<xsl:template match="section[. = 'Message_details']">
	<xsl:variable name="param" select="$params/message_details" />

	<div id="mes_details">

	<h3>Message details</h3>

	<div class="skin_text">
		<img class="stamp_picture" src="img/stamps/stamp{$param/Stamp}.png" width="100px" height="100px" alt="Marcopost stamp" />
		<p><span>From:</span><xsl:value-of select="$param/Author"/></p>
		<p><span>To:</span><xsl:value-of select="$param/Recipient"/></p>
		<p><span>Subject:</span><xsl:value-of select="$param/Subject"/></p>
		<p><span>Sent on:</span><xsl:value-of select="am:datetime($param/Created, $param/timezone)"/></p>
		<p>
			<xsl:if test="$param/current_location != 'all_mail'">
				<xsl:choose>
					<xsl:when test="$param/delete = 'no'">
						<button type="submit" name="message_delete" value="{$param/MessageID}">Delete</button>
					</xsl:when>
					<xsl:otherwise>
						<button type="submit" class="marked_button" name="message_delete_confirm" value="{$param/MessageID}">Confirm delete</button>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:if>
			<xsl:if test="($param/messages = 'yes') and ($param/Recipient = $param/PlayerName) and ($param/Author != $param/system_name) and ($param/Author != $param/PlayerName)">
				<button type="submit" name="message_create" value="{am:urlencode($param/Author)}">Reply</button>
			</xsl:if>
			<button type="submit" name="message_cancel">Back</button>
		</p>
		<hr/>
		<div><xsl:value-of select="am:BBCode_parse_extended($param/Content)" disable-output-escaping="yes" /></div>
	</div>
	<input type="hidden" name="CurrentLocation" value="{$param/current_location}" />

	</div>

</xsl:template>


<xsl:template match="section[. = 'Message_new']">
	<xsl:variable name="param" select="$params/message_new" />

	<div id="mes_details">

	<h3>New message</h3>

	<div class="skin_text">
		<img class="stamp_picture" src="img/stamps/stamp0.png" width="100px" height="100px" alt="Marcopost stamp" />
		<p><span>From:</span><xsl:value-of select="$param/Author"/></p>
		<p><span>To:</span><xsl:value-of select="$param/Recipient"/></p>
		<p>
			<span>Subject:</span>
			<input type="text" name="Subject" maxlength="30" size="25" value="{$param/Subject}" />
		</p>
		<button type="submit" name="message_send">Send</button>
		<button type="submit" name="message_cancel">Discard</button>
		<xsl:copy-of select="am:BBcodeButtons()"/>
		<hr/>

		<textarea name="Content" rows="6" cols="50"><xsl:value-of select="$param/Content"/></textarea>
	</div>

	<input type="hidden" name="Author" value="{$param/Author}" />
	<input type="hidden" name="Recipient" value="{$param/Recipient}" />

	</div>

</xsl:template>


</xsl:stylesheet>
