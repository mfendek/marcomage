<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
                xmlns="http://www.w3.org/1999/xhtml"
                xmlns:am="http://arcomage.netvor.sk"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
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
		<input type="submit" name="incoming" value="Incoming">
			<xsl:if test="$param/current_subsection = 'incoming'">
				<xsl:attribute name="class">pushed</xsl:attribute>
			</xsl:if>
		</input>

		<input type="submit" name="outgoing" value="Outgoing">
			<xsl:if test="$param/current_subsection = 'outgoing'">
				<xsl:attribute name="class">pushed</xsl:attribute>
			</xsl:if>
		</input>
	</p>

	<!-- selected deck -->
	<xsl:if test="($param/current_subsection = 'incoming') and ($param/deck_count &gt; 0)">
		<p class="game_filters">
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
									<input class="small_button" type="submit" name="user_details[{Author}]" value="i" />
									<span>
										<xsl:if test="Online = 'yes'">
											<xsl:attribute name="class">p_online</xsl:attribute>
										</xsl:if>
										<xsl:value-of select="Author"/>
									</span>
									<xsl:text> has challenged you on </xsl:text>
									<span><xsl:value-of select="am:datetime(Created, $param/timezone)"/></span>
								</p>
								<xsl:if test="Content != ''">
									<div class="challenge_content"><xsl:value-of select="am:BBCode_parse_extended(Content)" disable-output-escaping="yes" /></div>
								</xsl:if>
								<p>
									<xsl:if test="($param/deck_count &gt; 0) and ($param/free_slots &gt; 0) and ($param/accept_challenges = 'yes')">
										<input type="submit" name="accept_challenge[{GameID}]" value="Accept" />
									</xsl:if>
									<input type="submit" name="reject_challenge[{GameID}]" value="Reject" />
								</p>
							</xsl:when>
							<xsl:when test="$param/current_subsection = 'outgoing'">
								<p>
									<xsl:text>You challenged </xsl:text>
									<span>
										<xsl:if test="Online = 'yes'">
											<xsl:attribute name="class">p_online</xsl:attribute>
										</xsl:if>
										<xsl:value-of select="Recipient"/>
									</span>
									<xsl:text> on </xsl:text>
									<span><xsl:value-of select="am:datetime(Created, $param/timezone)"/></span>
								</p>
								<xsl:if test="Content != ''">
									<div class="challenge_content"><xsl:value-of select="am:BBCode_parse_extended(Content)" disable-output-escaping="yes" /></div>
								</xsl:if>
								<p><input type="submit" name="withdraw_challenge2[{GameID}]" value="Withdraw challenge" /></p>
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
		<input type="submit" name="inbox" value="Inbox" >
			<xsl:if test="$param/current_location = 'inbox'">
				<xsl:attribute name="class">pushed</xsl:attribute>
			</xsl:if>
		</input>
		<input type="submit" name="sent_mail" value="Sent mail" >
			<xsl:if test="$param/current_location = 'sent_mail'">
				<xsl:attribute name="class">pushed</xsl:attribute>
			</xsl:if>
		</input>
		<xsl:if test="$param/see_all_messages = 'yes'" >
			<input type="submit" name="all_mail" value="All mail" >
				<xsl:if test="$param/current_location = 'all_mail'">
					<xsl:attribute name="class">pushed</xsl:attribute>
				</xsl:if>
			</input>
		</xsl:if>
	</p>

	<div class="message_filters">
		<!-- begin date filter -->

		<select name="date_filter">
			<xsl:if test="$param/date_val != 'none'">
					<xsl:attribute name="class">filter_active</xsl:attribute>
			</xsl:if>
			<option value="none">
				<xsl:if test="$param/date_val = 'none'">
					<xsl:attribute name="selected">selected</xsl:attribute>
				</xsl:if>
				<xsl:text>No date filter</xsl:text>
			</option>
			<xsl:for-each select="$param/timesections/*">
				<option value="{time}">
					<xsl:if test="$param/date_val = time">
						<xsl:attribute name="selected">selected</xsl:attribute>
					</xsl:if>
					<xsl:value-of select="text"/>
				</option>
			</xsl:for-each>
		</select>

		<!-- end date filter -->

		<xsl:if test="$param/messages_count &gt; 0">
		<!-- begin name filter -->

		<select name="name_filter">
			<xsl:if test="$param/name_val != 'none'">
				<xsl:attribute name="class">filter_active</xsl:attribute>
			</xsl:if>
			<option value="none">
				<xsl:if test="$param/name_val = 'none'">
					<xsl:attribute name="selected">selected</xsl:attribute>
				</xsl:if>
				<xsl:text>No name filter</xsl:text>
			</option>
			<xsl:for-each select="$param/name_filter/*">
				<option value="{am:urlencode(.)}">
					<xsl:if test="$param/name_val = .">
						<xsl:attribute name="selected">selected</xsl:attribute>
					</xsl:if>
					<xsl:value-of select="text()"/>
				</option>
			</xsl:for-each>
		</select>

		<!-- end name filter -->
		</xsl:if>

		<input type = "submit" name = "message_filter" value = "Apply filters" />
	</div>

	<div class="message_filters">
	<!-- upper navigation -->
			<xsl:if test="$param/page_count &gt; 0">
				<!-- previous button -->
				<input type="submit" name="select_page_mes[{$param/current_page - 1}]" value="&lt;">
					<xsl:if test="$param/current_page &lt;= 0"><xsl:attribute name="disabled">disabled</xsl:attribute></xsl:if>
				</input>

				<!-- next button -->
				<input type="submit" name="select_page_mes[{$param/current_page + 1}]" value="&gt;">
					<xsl:if test="$param/current_page &gt;= $param/page_count - 1"><xsl:attribute name="disabled">disabled</xsl:attribute></xsl:if>
				</input>

				<!-- page selector -->
				<select name="jump_to_page">
					<xsl:for-each select="$param/pages/*">
						<option value="{.}">
							<xsl:if test="$param/current_page = ."><xsl:attribute name="selected">selected</xsl:attribute></xsl:if>
							<xsl:value-of select="."/>
						</option>
					</xsl:for-each>
				</select>
				<input type="submit" name="Jump_messages" value="Select page" />
				<xsl:if test="$param/current_location != 'all_mail'">
					<input type="submit" name="Delete_mass" value="Delete selected" />
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
							<p>To<input class="small_button" type="submit" >
									<xsl:choose>
										<xsl:when test="(($param/current_condition = 'Recipient') and ($param/current_order = 'DESC'))">
											<xsl:attribute name="name">mes_ord_asc[Recipient]</xsl:attribute>
											<xsl:attribute name="value">\/</xsl:attribute>
										</xsl:when>
										<xsl:otherwise>
											<xsl:attribute name="name">mes_ord_desc[Recipient]</xsl:attribute>
											<xsl:attribute name="value">/\</xsl:attribute>
										</xsl:otherwise>
									</xsl:choose>
									<xsl:if test="$param/current_condition = 'Recipient'">
										<xsl:attribute name="class">small_button pushed</xsl:attribute>
									</xsl:if>
								</input></p>
						</xsl:when>
						<xsl:otherwise>
							<p>From<input class="small_button" type="submit" >
									<xsl:choose>
										<xsl:when test="(($param/current_condition = 'Author') and ($param/current_order = 'DESC'))">
											<xsl:attribute name="name">mes_ord_asc[Author]</xsl:attribute>
											<xsl:attribute name="value">\/</xsl:attribute>
										</xsl:when>
										<xsl:otherwise>
											<xsl:attribute name="name">mes_ord_desc[Author]</xsl:attribute>
											<xsl:attribute name="value">/\</xsl:attribute>
										</xsl:otherwise>
									</xsl:choose>
									<xsl:if test="$param/current_condition = 'Author'">
										<xsl:attribute name="class">small_button pushed</xsl:attribute>
									</xsl:if>
								</input></p>
						</xsl:otherwise>
					</xsl:choose>
				</th>
				<xsl:if test="$param/current_location = 'all_mail'">
					<th><p>To</p></th>
				</xsl:if>
				<th><p>Subject</p></th>
				<th>
					<p>Sent on<input class="small_button" type="submit" >
					<xsl:choose>
						<xsl:when test="(($param/current_condition = 'Created') and ($param/current_order = 'DESC'))">
							<xsl:attribute name="name">mes_ord_asc[Created]</xsl:attribute>
							<xsl:attribute name="value">\/</xsl:attribute>
						</xsl:when>
						<xsl:otherwise>
							<xsl:attribute name="name">mes_ord_desc[Created]</xsl:attribute>
							<xsl:attribute name="value">/\</xsl:attribute>
						</xsl:otherwise>
					</xsl:choose>
					<xsl:if test="$param/current_condition = 'Created'">
						<xsl:attribute name="class">small_button pushed</xsl:attribute>
					</xsl:if>
					</input></p>
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
							<xsl:when test="Unread = 'yes' and am:datediff(Created, $param/PreviousLogin) &lt;= 0">
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
							<input class="small_button" type="submit" value="+" >
								<xsl:choose>
									<xsl:when test="$param/current_location = 'all_mail'">
										<xsl:attribute name="name">message_retrieve[<xsl:value-of select="MessageID"/>]</xsl:attribute>
									</xsl:when>
									<xsl:otherwise>
										<xsl:attribute name="name">message_details[<xsl:value-of select="MessageID"/>]</xsl:attribute>
									</xsl:otherwise>
								</xsl:choose>
							</input>
							<xsl:if test="$param/current_location != 'all_mail'">
								<input class="small_button" type="submit" name="message_delete[{MessageID}]" value="D" />
								<input type="checkbox" class="table_checkbox" name="Mass_delete_{position()}[{MessageID}]" />
							</xsl:if>
							<xsl:if test="(($param/send_messages = 'yes') and ($param/current_location = 'inbox') and (Author != $param/system_name) and (Author != $param/PlayerName))">
								<input class="small_button" type="submit" name="message_create[{Author}]" value="R" />
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
						<input type="submit" name="message_delete[{$param/MessageID}]" value="Delete" />
					</xsl:when>
					<xsl:otherwise>
						<input type="submit" name="message_delete_confirm[{$param/MessageID}]" value="Confirm delete" />
					</xsl:otherwise>
				</xsl:choose>
			</xsl:if>
			<xsl:if test="($param/messages = 'yes') and ($param/Recipient = $param/PlayerName) and ($param/Author != $param/system_name) and ($param/Author != $param/PlayerName)">
				<input type="submit" name="message_create[{am:urlencode($param/Author)}]" value="Reply" />
			</xsl:if>
			<input type="submit" name="message_cancel" value="Back" />
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
			<input type="text" name="Subject" maxlength="30" size="25" value="{$param/Subject}" onkeypress="return BlockEnter(event)" />
		</p>
		<input type="submit" name="message_send" value="Send" />
		<input type="submit" name="message_cancel" value="Discard" />
		<xsl:copy-of select="am:BBcodeButtons()"/>
		<hr/>

		<textarea name="Content" rows="6" cols="50"><xsl:value-of select="$param/Content"/></textarea>
	</div>

	<input type="hidden" name="Author" value="{$param/Author}" />
	<input type="hidden" name="Recipient" value="{$param/Recipient}" />

	</div>

</xsl:template>


</xsl:stylesheet>
