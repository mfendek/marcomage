<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet [ <!ENTITY uarr "&#8593;"> <!ENTITY rarr "&#8594;"> <!ENTITY crarr "&#8629;"> ]>
<xsl:stylesheet version="1.0"
                xmlns="http://www.w3.org/1999/xhtml"
                xmlns:am="http://arcomage.netvor.sk"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:date="http://exslt.org/dates-and-times"
                xmlns:exsl="http://exslt.org/common"
                xmlns:str="http://exslt.org/strings"
                extension-element-prefixes="date exsl str">
<xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes" doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" />


<xsl:template match="section[. = 'Forum']">
	<xsl:variable name="param" select="$params/forum_overview" />

	<div id="forum">
	<h3>MArcomage discussion forum</h3>
	<h4>Sections list</h4>

	<div class="skin_text">
	<table cellspacing="0" cellpadding="0">

	<tr>
	<th><p></p></th>
	<th><p>Topic</p></th>
	<th><p>Author</p></th>
	<th><p>Posts</p></th>
	<th><p>Created</p></th>
	<th><p>Last post</p></th>
	</tr>

	<xsl:for-each select="$param/sections/*">

		<tr><td colspan="6">
		<div class="skin_label">
		<h5>
			<input type="submit" name="section_details[{SectionID}]" value="&crarr;" />
			<span><xsl:value-of select="SectionName"/></span>
			( <xsl:value-of select="count" /> ) - <xsl:value-of select="Description" />
		</h5>
		<p></p>
		<div></div>
		</div>
		</td></tr>
		<xsl:for-each select="threadlist/*">

			<xsl:variable name="hasposts" select="boolean(PostCount > 0)" />

			<tr class="table_row">
			<td>
				<p><input class="details" type = "submit" name="thread_details[{ThreadID}]" value="+" /></p>
			</td>
			<td>
				<p>
					<xsl:attribute name="style">
					<xsl:choose>
					<xsl:when test="Priority = 'sticky'">color: red</xsl:when>
					<xsl:when test="Priority = 'important'">color: orange</xsl:when>
					</xsl:choose>
					</xsl:attribute>

					<xsl:value-of select="Title" />
					<xsl:if test="Locked = 'yes'"><img src="img/password.png" width="25px" height="20px" alt="locked" /></xsl:if>
				</p>
			</td>
			<td>
				<p><xsl:value-of select="Author"/></p>
			</td>
			<td>
				<p><xsl:value-of select="PostCount"/></p>
			</td>
			<td>
				<p><xsl:value-of select="am:datetime(Created, $param/timezone)"/></p>
			</td>
			<td>
				<xsl:choose>
					<xsl:when test="$hasposts">
						<p>
							<xsl:if test="am:datediff(LastPost, $param/PreviousLogin) &lt; 0">
								<xsl:attribute name="class">new</xsl:attribute>
							</xsl:if>
							<xsl:value-of select="concat(am:datetime(LastPost, $param/timezone), ' by ', LastAuthor)" />
							<input class="details" type="submit" name="thread_last_page[{ThreadID}]" value="&rarr;" />
						</p>
					</xsl:when>
					<xsl:otherwise><p>n/a</p></xsl:otherwise>
				</xsl:choose>
			</td>
			</tr>
		</xsl:for-each>
	</xsl:for-each>	

	</table>
	</div>
	</div>
</xsl:template>


<xsl:template match="section[. = 'Section_details']">
	<xsl:variable name="param" select="$params/forum_section" />

	<xsl:variable name="pages" select="$param/pages" />
	<xsl:variable name="current" select="$param/current_page" />

	<div id="forum">

	<h3>MArcomage discussion forum</h3>
	<h4>Section details</h4>

	<div class="skin_text">

	<table cellspacing="0" cellpadding="0">

	<tr>
	<th><p></p></th>
	<th><p>Topic</p></th>
	<th><p>Author</p></th>
	<th><p>Posts</p></th>
	<th><p>Created</p></th>
	<th><p>Last post</p></th>
	</tr>

	<tr><td colspan="6">
	<div class="skin_label">

	<h5><input type="submit" name="Forum" value="&uarr;" /><span><xsl:value-of select="$param/section/SectionName"/></span> - <xsl:value-of select="$param/section/Description"/></h5>
	<p>

	<input type="submit" name="section_page_jump[{am:max($current - 1, 0)}]" value="&lt;">
		<xsl:if test="$current = 0">
			<xsl:attribute name="disabled">disabled</xsl:attribute>
		</xsl:if>
	</input>

	<input type="submit" name="section_page_jump[0]" value="First">
		<xsl:if test="$current = 0">
			<xsl:attribute name="disabled">disabled</xsl:attribute>
		</xsl:if>
	</input>

	<xsl:variable name="numbers">
		<xsl:call-template name="numbers">
			<xsl:with-param name="from" select="am:max($current - 2, 0)"/>
			<xsl:with-param name="to" select="am:min($current + 2, $pages - 1)"/>
		</xsl:call-template>
	</xsl:variable>
	<xsl:for-each select="exsl:node-set($numbers)/*">
		<input type="submit" name="section_select_page" value="{text()}">
			<xsl:if test="$current = .">
				<xsl:attribute name="disabled">disabled</xsl:attribute>
			</xsl:if>
		</input>
	</xsl:for-each>

	<input type="submit" name="section_page_jump[{$pages - 1}]" value="Last">
		<xsl:if test="$current = am:max($pages - 1, 0)">
			<xsl:attribute name="disabled">disabled</xsl:attribute>
		</xsl:if>
	</input>

	<input type="submit" name="section_page_jump[{am:min($current + 1, $pages - 1)}]" value="&gt;">
		<xsl:if test="$current = am:max($pages - 1, 0)">
			<xsl:attribute name="disabled">disabled</xsl:attribute>
		</xsl:if>
	</input>

	<xsl:if test="$param/create_thread = 'yes'">
		<input type="submit" name="new_thread" value="New thread" />
	</xsl:if>

	</p>
	<div></div>
	</div>

	</td></tr>

	<xsl:for-each select="$param/threads/*">

		<xsl:variable name="hasposts" select="boolean(PostCount > 0)" />

		<tr class="table_row">
		<td>
			<p><input class="details" type = "submit" name="thread_details[{ThreadID}]" value="+" /></p>
		</td>
		<td>
			<p>
				<xsl:attribute name="style">
				<xsl:choose>
				<xsl:when test="Priority = 'sticky'">color: red</xsl:when>
				<xsl:when test="Priority = 'important'">color: orange</xsl:when>
				</xsl:choose>
				</xsl:attribute>
				
				<xsl:value-of select="Title" />
				<xsl:if test="Locked = 'yes'"><img src="img/password.png" width="25px" height="20px" alt="locked" /></xsl:if>
			</p>
		</td>
		<td>
			<p><xsl:value-of select="Author"/></p>
		</td>
		<td>
			<p><xsl:value-of select="PostCount"/></p>
		</td>
		<td>
			<p><xsl:value-of select="am:datetime(Created, $param/timezone)"/></p>
		</td>
		<td>
			<xsl:choose>
				<xsl:when test="$hasposts">
					<p>
						<xsl:if test="am:datediff(LastPost, $param/PreviousLogin) &lt; 0">
							<xsl:attribute name="class">new</xsl:attribute>
						</xsl:if>
						<xsl:value-of select="concat(am:datetime(LastPost, $param/timezone), ' by ', LastAuthor)" />
						<input class="details" type="submit" name="thread_last_page[{ThreadID}]" value="&rarr;" />
					</p>
				</xsl:when>
				<xsl:otherwise><p>n/a</p></xsl:otherwise>
			</xsl:choose>
		</td>
		</tr>

	</xsl:for-each>	

	</table>

	</div>

	<input type="hidden" name="CurrentSection" value = "{$param/section/SectionID}" />

	</div>
</xsl:template>

	
<xsl:template match="section[. = 'Thread_details']">
	<xsl:variable name="param" select="$params/forum_thread" />

	<xsl:variable name="section" select="$param/Section"/>
	<xsl:variable name="current_page" select="$param/CurrentPage"/>
	<xsl:variable name="thread" select="$param/Thread"/>
	<xsl:variable name="pages" select="$param/Pages"/>
	<xsl:variable name="delete_post" select="$param/DeletePost"/>
	<!-- is unlocked or you have the right to lock/unlock -->
	<xsl:variable name="can_modify" select="$thread/Locked = 'no' or $param/lock_thread = 'yes'"/>

	<div id="thread_details">
		
	<h3>MArcomage discussion forum</h3>
		
	<h4>Thread details</h4>

	<xsl:variable name="nav_bar">
		<div class="thread_bar skin_label">
			<h5>
				<input type="submit" name="section_details[{$section/SectionID}]" value="&uarr;" />
				<span><xsl:value-of select="$section/SectionName"/></span> - <xsl:value-of select="$thread/Title"/>
				<xsl:if test="$thread/Locked = 'yes'"><img src="img/password.png" width="25px" height="20px" alt="locked" /></xsl:if>
			</h5>
			<p>
			<xsl:if test="$param/concept &gt; 0">
				<input type="submit" name="view_concept[{$param/concept}]" value="Go to concept" />
			</xsl:if>

			<xsl:if test="$param/lock_thread = 'yes'">
				<xsl:choose>
					<xsl:when test="$thread/Locked = 'no'">
						<input type="submit" name="thread_lock" value="Lock" />
					</xsl:when>
					<xsl:otherwise>
						<input type="submit" name="thread_unlock" value="Unlock" />
					</xsl:otherwise>
				</xsl:choose>
			</xsl:if>
			
			<xsl:if test="$param/del_all_thread = 'yes' and $can_modify = true()">
				<xsl:choose>
					<xsl:when test="$param/Delete = 'no'">
						<input type="submit" name="thread_delete" value="Delete" />
					</xsl:when>
					<xsl:otherwise>
						<input type="submit" name="thread_delete_confirm" value="Confirm delete" />
					</xsl:otherwise>
				</xsl:choose>
			</xsl:if>

			<xsl:if test="$param/edit_thread = 'yes' and $can_modify = true()">
				<input type="submit" name="edit_thread" value="Edit" />
			</xsl:if>

			<input type="submit" name="thread_page_jump[{am:max($current_page - 1, 0)}]" value="&lt;">
				<xsl:if test="$current_page = 0">
					<xsl:attribute name="disabled">disabled</xsl:attribute>
				</xsl:if>
			</input>

			<input type="submit" name="thread_page_jump[0]" value="First">
				<xsl:if test="$current_page = 0">
					<xsl:attribute name="disabled">disabled</xsl:attribute>
				</xsl:if>
			</input>

			<xsl:variable name="numbers">
				<xsl:call-template name="numbers">
					<xsl:with-param name="from" select="am:max($current_page - 2, 0)"/>
					<xsl:with-param name="to" select="am:min($current_page + 2, $pages - 1)"/>
				</xsl:call-template>
			</xsl:variable>
			<xsl:for-each select="exsl:node-set($numbers)/*">
				<input type="submit" name="thread_select_page" value="{text()}">
					<xsl:if test="$current_page = .">
						<xsl:attribute name="disabled">disabled</xsl:attribute>
					</xsl:if>
				</input>
			</xsl:for-each>

			<input type="submit" name="thread_page_jump[{$pages - 1}]" value="Last">
				<xsl:if test="$current_page = am:max($pages - 1, 0)">
					<xsl:attribute name="disabled">disabled</xsl:attribute>
				</xsl:if>
			</input>

			<input type="submit" name="thread_page_jump[{am:min($current_page + 1, $pages - 1)}]" value="&gt;">
				<xsl:if test="$current_page = am:max($pages - 1, 0)">
					<xsl:attribute name="disabled">disabled</xsl:attribute>
				</xsl:if>
			</input>

			<xsl:if test="$param/create_post = 'yes' and $thread/Locked = 'no'">
				<input type = "submit" name="new_post" value="New post" />
			</xsl:if>

			</p>
			<div class="clear_floats"></div>
		</div>
	</xsl:variable>	

	<xsl:copy-of select="$nav_bar"/>

	<div id="post_list">

		<xsl:for-each select="$param/PostList/*">

			<div class="skin_text">
			
			<div>
							
			<h5><xsl:value-of select="Author"/></h5>
			
			<img class="avatar" height="60px" width="60px" src="img/avatars/{Avatar}" alt="avatar" />
			
			<p>
			<input class="details" type="submit" name="user_details[{Author}]" value = "i" />
			<input class="details" type="submit" name="message_create[{Author}]" value = "m" />
			
			</p>
			
			<p>
				<xsl:if test="am:datediff(Created, $param/PreviousLogin) &lt; 0">
					<xsl:attribute name="class">new</xsl:attribute>
				</xsl:if>
				<xsl:value-of select="am:datetime(Created, $param/timezone)" />
			</p>
			
			</div>
			
			<div>
			
			<div><xsl:copy-of select="am:textencode(Content)" /></div>
			
			</div>
			
			<div class="clear_floats"></div>
			
			<div>
			
			<xsl:if test="($param/edit_all_post = 'yes' or ($param/edit_own_post = 'yes' and $param/PlayerName = Author)) and $can_modify = true()">
				<input type="submit" name="edit_post[{PostID}]" value="Edit" />
			</xsl:if>

			<xsl:if test="$param/del_all_post = 'yes' and $can_modify = true()">
				<xsl:choose>
					<xsl:when test="$delete_post != PostID">
						<input type="submit" name="delete_post[{PostID}]" value="Delete" />
					</xsl:when>
					<xsl:otherwise>
						<input type="submit" name="delete_post_confirm[{PostID}]" value="Confirm delete" />
					</xsl:otherwise>
				</xsl:choose>
			</xsl:if>
			
			</div>
			
			</div>

		</xsl:for-each>
	</div>

	<xsl:copy-of select="$nav_bar"/>

	<input type="hidden" name="CurrentSection" value="{$thread/SectionID}" />
	<input type="hidden" name="CurrentThread" value="{$thread/ThreadID}" />
	<input type="hidden" name="CurrentPage" value="{$current_page}" />

	</div>
</xsl:template>


<xsl:template match="section[. = 'New_thread']">
	<xsl:variable name="param" select="$params/forum_thread_new" />

	<xsl:variable name="section" select="$param/Section"/>

	<div id="forum_new_edit">
	
	<h3>Create new thread to the section <span><xsl:value-of select="$section/SectionName"/></span></h3>
	
	<div class="skin_text">
		<p>Topic:<input type="text" name="Title" maxlength="50" size="45" value="{$param/Title}" /></p>
		<p>

		<xsl:text>Priority:</xsl:text>

		<select name="Priority">
		<option value="normal" selected="selected" >Normal</option>
		<xsl:if test="$param/chng_priority = 'yes'">
			<option value="important">Important</option>
			<option value="sticky">Sticky</option>
		</xsl:if>
		</select>
		
		</p>
		
		<input type="submit" name="create_thread" value="Create thread" />
		<input type="submit" name="section_details[{$section/SectionID}]" value="Back" />
		<hr/>
		
		<textarea name="Content" rows="10" cols="50"><xsl:value-of select="$param/Content"/></textarea>
		
		</div>
		
		<input type="hidden" name="CurrentSection" value="{$section/SectionID}" />
	</div>
</xsl:template>


<xsl:template match="section[. = 'New_post']">
	<xsl:variable name="param" select="$params/forum_post_new" />

	<xsl:variable name="thread" select="$param/Thread" />

	<div id="forum_new_edit">

	<h3>New post in thread - <span><xsl:value-of select="$thread/Title"/></span></h3>

	<div class="skin_text">
	
	<input type="submit" name="create_post" value="Create post" />
	<input type="submit" name="thread_details[{$thread/ThreadID}]" value="Back" />
	<hr/>
	
	<textarea name="Content" rows="10" cols="50"><xsl:value-of select="$param/Content"/></textarea>

	</div>
	
	<input type="hidden" name="CurrentThread" value = "{$thread/ThreadID}" />

	</div>
</xsl:template>


<xsl:template match="section[. = 'Edit_thread']">
	<xsl:variable name="param" select="$params/forum_thread_edit" />

	<xsl:variable name="section" select="$param/Section"/>
	<xsl:variable name="thread" select="$param/Thread"/>
	<xsl:variable name="section_list" select="$param/SectionList"/>

	<div id="forum_new_edit">
	
		<h3>Edit thread</h3>
	
		<div class="skin_text">
			<p>Topic:<input type="text" name="Title" maxlength="50" size="45" value="{$thread/Title}" /></p>

			<p>

			<xsl:text>Priority:</xsl:text>

			<select name="Priority">
				<xsl:if test="$param/chng_priority = 'no'">
					<xsl:attribute name="disabled">disabled</xsl:attribute>
				</xsl:if>
				<option value="normal"><xsl:if test="$thread/Priority = 'normal'"><xsl:attribute name="selected">selected</xsl:attribute></xsl:if>Normal</option>
				<option value="important"><xsl:if test="$thread/Priority = 'important'"><xsl:attribute name="selected">selected</xsl:attribute></xsl:if>Important</option>
				<option value="sticky"><xsl:if test="$thread/Priority = 'sticky'"><xsl:attribute name="selected">selected</xsl:attribute></xsl:if>Sticky</option>
			</select>
			</p>
			
			<input type="submit" name="modify_thread" value="Save" />
			<input type="submit" name="thread_details[{$thread/ThreadID}]" value="Back" />

			<xsl:if test="$param/move_thread = 'yes'">
				<hr/>
				
				<p>Current section: <span><xsl:value-of select="$section/SectionName"/></span></p>
				
				<p>

				<xsl:text>Target section:</xsl:text>

				<select name="section_select">
					<xsl:for-each select="$section_list/*">
						<option value="{SectionID}"><xsl:value-of select="SectionName"/></option>
					</xsl:for-each>
				</select>
				
				</p>
				
				<input type="submit" name="move_thread" value="Change section" />
			</xsl:if>
		</div>
		<input type="hidden" name="CurrentThread" value="{$thread/ThreadID}"/>
	</div>
</xsl:template>


<xsl:template match="section[. = 'Edit_post']">
	<xsl:variable name="param" select="$params/forum_post_edit" />
	
	<xsl:variable name="post" select="$param/Post"/>
	<xsl:variable name="thread" select="$param/Thread"/>
	<xsl:variable name="thread_list" select="$param/ThreadList"/>
	<xsl:variable name="current_page" select="$param/CurrentPage"/>
	
	<div id="forum_new_edit">
		<h3>Edit post</h3>
		<div class="skin_text">	
		<input type="submit" name="modify_post" value="Save" />
		<input type="submit" name="thread_details[{$post/ThreadID}]" value="Back" />
		<hr/>

		<textarea name="Content" rows="10" cols="50">
			<xsl:value-of select="$param/Content"/>
		</textarea>
		
		<xsl:if test="$param/move_post = 'yes'">
			<hr/>
			<p>Current thread: <span><xsl:value-of select="$thread/Title"/></span></p>
			<xsl:if test="count($thread_list/*) &gt; 0">
				<p>
					<xsl:text>Target thread:</xsl:text>
					<select name="thread_select">
						<xsl:for-each select="$thread_list/*">
							<option value="{ThreadID}"><xsl:value-of select="Title"/></option>
						</xsl:for-each>
					</select>
				</p>
				<input type="submit" name="move_post" value="Change thread" />
			</xsl:if>
		</xsl:if>
			
		</div>
		
		<input type="hidden" name="CurrentThread" value="{$post/ThreadID}"/>
		<input type="hidden" name="CurrentPost" value="{$post/PostID}"/>
		<input type="hidden" name="CurrentPage" value="{$current_page}"/>
	</div>
</xsl:template>


<xsl:template name="numbers">
	<xsl:param name="from"/>
	<xsl:param name="to"/>
	<xsl:if test="$from &lt;= $to">
		<div><xsl:value-of select="$from"/></div>
		<xsl:call-template name="numbers">
			<xsl:with-param name="from" select="$from+1"/>
			<xsl:with-param name="to" select="$to"/>
		</xsl:call-template>
	</xsl:if>
</xsl:template>

</xsl:stylesheet>
