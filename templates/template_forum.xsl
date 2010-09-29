<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
                xmlns="http://www.w3.org/1999/xhtml"
                xmlns:am="http://arcomage.netvor.sk"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:date="http://exslt.org/dates-and-times"
                xmlns:exsl="http://exslt.org/common"
                xmlns:func="http://exslt.org/functions"
                xmlns:php="http://php.net/xsl"
                xmlns:str="http://exslt.org/strings"
                extension-element-prefixes="date exsl func php str">
<xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes" doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" />

<!-- includes -->
<xsl:include href="template_main.xsl" />


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
	<th><p><button type="submit" class="search_button" name="forum_search">Search</button>Latest post</p></th>
	</tr>

	<xsl:for-each select="$param/sections/*">

		<tr><td colspan="6">
		<div class="skin_label">
		<h5>
			<a href="{php:functionString('makeurl', 'Forum_section', 'CurrentSection', SectionID)}"><xsl:value-of select="SectionName"/></a>
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
				<p>
					<xsl:choose>
						<xsl:when test="Priority = 'sticky'">
							<img src="img/sticky.gif" width="22px" height="15x" alt="sticky" title="Sticky" class="icon" />
						</xsl:when>
						<xsl:when test="Priority = 'important'">
							<img src="img/important.gif" width="18px" height="13px" alt="important" title="Important" class="icon" />
						</xsl:when>
					</xsl:choose>
					<xsl:if test="Locked = 'yes'"><img src="img/locked.gif" width="15px" height="16px" alt="locked" title="Locked" class="icon" /></xsl:if>
				</p>
			</td>
			<td>
				<p class="headings">
					<a href="{php:functionString('makeurl', 'Forum_thread', 'CurrentThread', ThreadID, 'CurrentPage', 0)}"><xsl:value-of select="Title" /></a>
				</p>
			</td>
			<td>
				<p><a class="profile" href="{php:functionString('makeurl', 'Players_details', 'Profile', Author)}"><xsl:value-of select="Author"/></a></p>
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
							<xsl:if test="am:datediff(LastPost, $param/notification) &lt; 0">
								<xsl:attribute name="class">new</xsl:attribute>
							</xsl:if>
							<a href="{php:functionString('makeurl', 'Forum_thread', 'CurrentThread', ThreadID, 'CurrentPage', am:max(LastPage - 1, 0), '#latest')}"><xsl:value-of select="am:datetime(LastPost, $param/timezone)" /></a>
							<xsl:text> by </xsl:text>
							<a class="profile" href="{php:functionString('makeurl', 'Players_details', 'Profile', LastAuthor)}"><xsl:value-of select="LastAuthor"/></a>
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


<xsl:template match="section[. = 'Forum_search']">
	<xsl:variable name="param" select="$params/forum_search" />

	<div id="forum">

	<h3>MArcomage discussion forum</h3>
	<h4>Search</h4>
	
	<div class="filters">
		<input type="text" name="phrase" maxlength="50" size="30" value="{$param/phrase}" />

		<!-- target selector -->
		<xsl:variable name="targets">
			<class name="posts"   />
			<class name="threads" />
		</xsl:variable>

		<select name="target">
			<xsl:if test="$param/target != 'all'">
					<xsl:attribute name="class">filter_active</xsl:attribute>
			</xsl:if>
			<option value="all">
				<xsl:if test="$param/target = 'all'">
					<xsl:attribute name="selected">selected</xsl:attribute>
				</xsl:if>
				<xsl:text>any source</xsl:text>
			</option>
			<xsl:for-each select="exsl:node-set($targets)/*">
			<option value="{@name}">
				<xsl:if test="$param/target = @name">
					<xsl:attribute name="selected">selected</xsl:attribute>
				</xsl:if>
				<xsl:value-of select="@name"/>
			</option>
			</xsl:for-each>
		</select>

		<!-- section selector -->
		<select name="section">
			<xsl:if test="$param/section != 'any'">
					<xsl:attribute name="class">filter_active</xsl:attribute>
			</xsl:if>
			<option value="any">
				<xsl:if test="$param/section = 'any'">
					<xsl:attribute name="selected">selected</xsl:attribute>
				</xsl:if>
				<xsl:text>any section</xsl:text>
			</option>
			<xsl:for-each select="$param/sections/*">
				<option value="{SectionID}">
					<xsl:if test="$param/section = SectionID">
						<xsl:attribute name="selected">selected</xsl:attribute>
					</xsl:if>
					<xsl:value-of select="SectionName"/>
				</option>
			</xsl:for-each>
		</select>

		<button type="submit" name="forum_search">Search</button>
	</div>

	<xsl:choose>
		<xsl:when test="count($param/threads/*) &gt; 0">
			<div class="skin_text">

			<table cellspacing="0" cellpadding="0">

			<tr>
			<th><p></p></th>
			<th><p>Topic</p></th>
			<th><p>Author</p></th>
			<th><p>Posts</p></th>
			<th><p>Created</p></th>
			<th><p>Latest post</p></th>
			</tr>

			<xsl:for-each select="$param/threads/*">

				<xsl:variable name="hasposts" select="boolean(PostCount > 0)" />

				<tr class="table_row">
				<td>
					<p>
						<xsl:choose>
							<xsl:when test="Priority = 'sticky'">
								<img src="img/sticky.gif" width="22px" height="15x" alt="sticky" title="Sticky" class="icon" />
							</xsl:when>
							<xsl:when test="Priority = 'important'">
								<img src="img/important.gif" width="18px" height="13px" alt="important" title="Important" class="icon" />
							</xsl:when>
						</xsl:choose>
						<xsl:if test="Locked = 'yes'"><img src="img/locked.gif" width="15px" height="16px" alt="locked" title="Locked" class="icon" /></xsl:if>
					</p>
				</td>
				<td>
					<p class="headings">
						<a href="{php:functionString('makeurl', 'Forum_thread', 'CurrentThread', ThreadID, 'CurrentPage', 0)}"><xsl:value-of select="Title" /></a>
					</p>
				</td>
				<td>
					<p><a class="profile" href="{php:functionString('makeurl', 'Players_details', 'Profile', Author)}"><xsl:value-of select="Author"/></a></p>
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
								<xsl:if test="am:datediff(LastPost, $param/notification) &lt; 0">
									<xsl:attribute name="class">new</xsl:attribute>
								</xsl:if>
								<a href="{php:functionString('makeurl', 'Forum_thread', 'CurrentThread', ThreadID, 'CurrentPage', am:max(LastPage - 1, 0), '#latest')}"><xsl:value-of select="am:datetime(LastPost, $param/timezone)" /></a>
								<xsl:text> by </xsl:text>
								<a class="profile" href="{php:functionString('makeurl', 'Players_details', 'Profile', LastAuthor)}"><xsl:value-of select="LastAuthor"/></a>
							</p>
						</xsl:when>
						<xsl:otherwise><p>n/a</p></xsl:otherwise>
					</xsl:choose>
				</td>
				</tr>

			</xsl:for-each>	

			</table>

			</div>
		</xsl:when>
		<xsl:otherwise>
			<p class="information_line warning">No results matched selected criteria.</p>
		</xsl:otherwise>
	</xsl:choose>

	</div>
</xsl:template>


<xsl:template match="section[. = 'Forum_section']">
	<xsl:variable name="param" select="$params/forum_section" />

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
	<th><p><button type="submit" class="search_button" name="forum_search">Search</button>Latest post</p></th>
	</tr>

	<tr><td colspan="6">
	<div class="skin_label">

	<h5>
		<a href="{php:functionString('makeurl', 'Forum')}"><xsl:value-of select="$param/section/SectionName"/></a>
		<xsl:text> - </xsl:text>
		<xsl:value-of select="$param/section/Description"/>
	</h5>
	<p>

	<!-- navigation -->
	<xsl:copy-of select="am:forum_navigation('Forum_section', 'CurrentSection', $param/section/SectionID, $param/current_page, $param/pages)"/>

	<xsl:if test="$param/create_thread = 'yes'">
		<button type="submit" name="new_thread">New thread</button>
	</xsl:if>

	</p>
	<div></div>
	</div>

	</td></tr>

	<xsl:for-each select="$param/threads/*">

		<xsl:variable name="hasposts" select="boolean(PostCount > 0)" />

		<tr class="table_row">
		<td>
			<p>
				<xsl:choose>
					<xsl:when test="Priority = 'sticky'">
						<img src="img/sticky.gif" width="22px" height="15x" alt="sticky" title="Sticky" class="icon" />
					</xsl:when>
					<xsl:when test="Priority = 'important'">
						<img src="img/important.gif" width="18px" height="13px" alt="important" title="Important" class="icon" />
					</xsl:when>
				</xsl:choose>
				<xsl:if test="Locked = 'yes'"><img src="img/locked.gif" width="15px" height="16px" alt="locked" title="Locked" class="icon" /></xsl:if>
			</p>
		</td>
		<td>
			<p class="headings">
				<a href="{php:functionString('makeurl', 'Forum_thread', 'CurrentThread', ThreadID, 'CurrentPage', 0)}"><xsl:value-of select="Title" /></a>
			</p>
		</td>
		<td>
			<p><a class="profile" href="{php:functionString('makeurl', 'Players_details', 'Profile', Author)}"><xsl:value-of select="Author"/></a></p>
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
						<xsl:if test="am:datediff(LastPost, $param/notification) &lt; 0">
							<xsl:attribute name="class">new</xsl:attribute>
						</xsl:if>
						<a href="{php:functionString('makeurl', 'Forum_thread', 'CurrentThread', ThreadID, 'CurrentPage', am:max(LastPage - 1, 0), '#latest')}"><xsl:value-of select="am:datetime(LastPost, $param/timezone)" /></a>
						<xsl:text> by </xsl:text>
						<a class="profile" href="{php:functionString('makeurl', 'Players_details', 'Profile', LastAuthor)}"><xsl:value-of select="LastAuthor"/></a>
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

	
<xsl:template match="section[. = 'Forum_thread']">
	<xsl:variable name="param" select="$params/forum_thread" />

	<xsl:variable name="section" select="$param/Section"/>
	<xsl:variable name="thread" select="$param/Thread"/>
	<xsl:variable name="delete_post" select="$param/DeletePost"/>
	<!-- is unlocked or you have the right to lock/unlock -->
	<xsl:variable name="can_modify" select="$thread/Locked = 'no' or $param/lock_thread = 'yes'"/>

	<div id="thread_details">
		
	<h3>MArcomage discussion forum</h3>
		
	<h4>Thread details</h4>

	<xsl:variable name="nav_bar">
		<div class="thread_bar skin_label">
			<h5>
				<a href="{php:functionString('makeurl', 'Forum_section', 'CurrentSection', $section/SectionID)}"><xsl:value-of select="$section/SectionName"/></a>
				<xsl:text> - </xsl:text>
				<xsl:value-of select="$thread/Title"/>
				<xsl:if test="$thread/Locked = 'yes'">
					<img src="img/locked.gif" width="15px" height="16px" alt="locked" title="Locked" class="icon" />
				</xsl:if>
			</h5>
			<p>
			<xsl:if test="$param/concept &gt; 0">
				<a class="button" href="{php:functionString('makeurl', 'Concepts_details', 'CurrentConcept', $param/concept)}">View concept</a>
			</xsl:if>
			<xsl:if test="$thread/CardID &gt; 0">
				<a class="button" href="{php:functionString('makeurl', 'Cards_details', 'card', $thread/CardID)}">View card</a>
			</xsl:if>

			<xsl:if test="$param/lock_thread = 'yes'">
				<xsl:choose>
					<xsl:when test="$thread/Locked = 'no'">
						<button type="submit" name="thread_lock">Lock</button>
					</xsl:when>
					<xsl:otherwise>
						<button type="submit" name="thread_unlock">Unlock</button>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:if>
			
			<xsl:if test="$param/del_all_thread = 'yes' and $can_modify = true()">
				<xsl:choose>
					<xsl:when test="$param/Delete = 'no'">
						<button type="submit" name="thread_delete">Delete</button>
					</xsl:when>
					<xsl:otherwise>
						<button type="submit" class="marked_button" name="thread_delete_confirm">Confirm delete</button>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:if>

			<xsl:if test="$param/edit_thread = 'yes' and $can_modify = true()">
				<button type="submit" name="edit_thread">Edit</button>
			</xsl:if>

			<!-- navigation -->
			<xsl:copy-of select="am:forum_navigation('Forum_thread', 'CurrentThread', $thread/ThreadID, $param/CurrentPage, $param/Pages)"/>

			<xsl:if test="$param/create_post = 'yes' and $thread/Locked = 'no'">
				<button type="submit" name="new_post">New post</button>
			</xsl:if>

			</p>
			<div class="clear_floats"></div>
		</div>
	</xsl:variable>	

	<xsl:copy-of select="$nav_bar"/>

	<div id="post_list">

		<xsl:for-each select="$param/PostList/*">

			<div class="skin_text">
			<div class="post_header">

			<span class="post_title">
				<xsl:if test="position() = last()">
					<a id="latest"></a>
				</xsl:if>
				<a href="{php:functionString('makeurl', 'Players_details', 'Profile', Author)}"><xsl:value-of select="Author"/></a>

				<xsl:text> on </xsl:text> 

				<span>
					<xsl:if test="am:datediff(Created, $param/notification) &lt; 0">
						<xsl:attribute name="class">new</xsl:attribute>
					</xsl:if>
					<xsl:value-of select="am:datetime(Created, $param/timezone)" />
				</span>
			</span>

			<xsl:if test="$param/create_post = 'yes' and $thread/Locked = 'no'">
				<button type="submit" name="quote_post" value="{PostID}">Quote</button>
			</xsl:if>

			<xsl:if test="($param/edit_all_post = 'yes' or ($param/edit_own_post = 'yes' and $param/PlayerName = Author)) and $can_modify = true()">
				<button type="submit" name="edit_post" value="{PostID}">Edit</button>
			</xsl:if>

			<xsl:if test="$param/del_all_post = 'yes' and $can_modify = true()">
				<xsl:choose>
					<xsl:when test="$delete_post != PostID">
						<button type="submit" name="delete_post" value="{PostID}">Delete</button>
					</xsl:when>
					<xsl:otherwise>
						<button type="submit" class="marked_button" name="delete_post_confirm" value="{PostID}">Confirm delete</button>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:if>

			<a id="{concat('post', PostID)}" class="permalink" href="{php:functionString('makeurl', 'Forum_thread', 'CurrentThread', $thread/ThreadID, 'CurrentPage', $param/CurrentPage, concat('#post', PostID))}" title="Permalink" >#<xsl:value-of select="position() + $param/CurrentPage * $param/posts_per_page" /></a>

			<div class="clear_floats"></div>

			</div>

			<div>

			<div class="post_avatar"><a href="{php:functionString('makeurl', 'Players_details', 'Profile', Author)}"><img class="avatar" height="60px" width="60px" src="img/avatars/{Avatar}" alt="avatar" /></a></div>

			<div class="post_content"><div><xsl:value-of select="am:BBCode_parse_extended(Content)" disable-output-escaping="yes" /></div></div>

			<div class="clear_floats"></div>

			</div>

			</div>

		</xsl:for-each>
	</div>

	<xsl:copy-of select="$nav_bar"/>

	<input type="hidden" name="CurrentSection" value="{$thread/SectionID}" />
	<input type="hidden" name="CurrentThread" value="{$thread/ThreadID}" />
	<input type="hidden" name="CurrentPage" value="{$param/CurrentPage}" />

	</div>
</xsl:template>


<xsl:template match="section[. = 'Forum_thread_new']">
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
		
		<button type="submit" name="create_thread">Create thread</button>
		<a class="button" href="{php:functionString('makeurl', 'Forum_section', 'CurrentSection', $section/SectionID)}">Back</a>
		<xsl:copy-of select="am:BBcodeButtons()"/>
		<hr/>
		
		<textarea name="Content" rows="10" cols="50"><xsl:value-of select="$param/Content"/></textarea>
		
		</div>
		
		<input type="hidden" name="CurrentSection" value="{$section/SectionID}" />
	</div>
</xsl:template>


<xsl:template match="section[. = 'Forum_post_new']">
	<xsl:variable name="param" select="$params/forum_post_new" />

	<xsl:variable name="thread" select="$param/Thread" />

	<div id="forum_new_edit">

	<h3>New post in thread - <span><xsl:value-of select="$thread/Title"/></span></h3>

	<div class="skin_text">
	
	<button type="submit" name="create_post">Create post</button>
	<a class="button" href="{php:functionString('makeurl', 'Forum_thread', 'CurrentThread', $thread/ThreadID, 'CurrentPage', 0)}">Back</a>
	<xsl:copy-of select="am:BBcodeButtons()"/>
	<hr/>
	
	<textarea name="Content" rows="10" cols="50"><xsl:value-of select="$param/Content"/></textarea>

	</div>
	
	<input type="hidden" name="CurrentThread" value = "{$thread/ThreadID}" />

	</div>
</xsl:template>


<xsl:template match="section[. = 'Forum_thread_edit']">
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

				<xsl:variable name="priority_types">
					<type name="normal"    text="Normal"    />
					<type name="important" text="Important" />
					<type name="sticky"    text="Sticky"    />
				</xsl:variable>

				<xsl:for-each select="exsl:node-set($priority_types)/*">
					<option value="{@name}">
						<xsl:if test="$thread/Priority = @name">
							<xsl:attribute name="selected">selected</xsl:attribute>
						</xsl:if>
						<xsl:value-of select="@text"/>
					</option>
				</xsl:for-each>
			</select>
			</p>
			
			<button type="submit" name="modify_thread">Save</button>
			<a class="button" href="{php:functionString('makeurl', 'Forum_thread', 'CurrentThread', $thread/ThreadID, 'CurrentPage', 0)}">Back</a>

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
				
				<button type="submit" name="move_thread">Change section</button>
			</xsl:if>
		</div>
		<input type="hidden" name="CurrentThread" value="{$thread/ThreadID}"/>
	</div>
</xsl:template>


<xsl:template match="section[. = 'Forum_post_edit']">
	<xsl:variable name="param" select="$params/forum_post_edit" />
	
	<xsl:variable name="post" select="$param/Post"/>
	<xsl:variable name="thread" select="$param/Thread"/>
	<xsl:variable name="thread_list" select="$param/ThreadList"/>
	<xsl:variable name="current_page" select="$param/CurrentPage"/>
	
	<div id="forum_new_edit">
		<h3>Edit post</h3>
		<div class="skin_text">	
		<button type="submit" name="modify_post">Save</button>
		<a class="button" href="{php:functionString('makeurl', 'Forum_thread', 'CurrentThread', $post/ThreadID, 'CurrentPage', 0)}">Back</a>
		<xsl:copy-of select="am:BBcodeButtons()"/>
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
				<button type="submit" name="move_post">Change thread</button>
			</xsl:if>
		</xsl:if>
			
		</div>
		
		<input type="hidden" name="CurrentThread" value="{$post/ThreadID}"/>
		<input type="hidden" name="CurrentPost" value="{$post/PostID}"/>
		<input type="hidden" name="CurrentPage" value="{$current_page}"/>
	</div>
</xsl:template>


<func:function name="am:forum_navigation">
	<xsl:param name="location" as="xs:string" />
	<xsl:param name="section_name" as="xs:string" />
	<xsl:param name="section_id" as="xs:integer" />
	<xsl:param name="current" as="xs:integer" />
	<xsl:param name="page_count" as="xs:integer" />

	<xsl:variable name="output">
		<xsl:choose>
			<xsl:when test="$current &gt; 0">
				<a class="button" href="{php:functionString('makeurl', $location, $section_name, $section_id, 'CurrentPage', am:max($current - 1, 0))}">&lt;</a>
			</xsl:when>
			<xsl:otherwise>
				<span class="disabled">&lt;</span>
			</xsl:otherwise>
		</xsl:choose>

		<xsl:choose>
			<xsl:when test="$current &gt; 0">
				<a class="button" href="{php:functionString('makeurl', $location, $section_name, $section_id, 'CurrentPage', 0)}">First</a>
			</xsl:when>
			<xsl:otherwise>
				<span class="disabled">First</span>
			</xsl:otherwise>
		</xsl:choose>

		<xsl:for-each select="str:split(am:numbers(am:max($current - 2, 0), am:min($current + 2, am:max($page_count - 1, 0))), ',')">
			<xsl:choose>
				<xsl:when test="$current != .">
					<a class="button" href="{php:functionString('makeurl', $location, $section_name, $section_id, 'CurrentPage', text())}"><xsl:value-of select="text()"/></a>
				</xsl:when>
				<xsl:otherwise>
					<span class="disabled"><xsl:value-of select="text()"/></span>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:for-each>

		<xsl:choose>
			<xsl:when test="$current &lt; am:max($page_count - 1, 0)">
				<a class="button" href="{php:functionString('makeurl', $location, $section_name, $section_id, 'CurrentPage', am:max($page_count - 1, 0))}">Last</a>
			</xsl:when>
			<xsl:otherwise>
				<span class="disabled">Last</span>
			</xsl:otherwise>
		</xsl:choose>

		<xsl:choose>
			<xsl:when test="$current &lt; am:max($page_count - 1, 0)">
				<a class="button" href="{php:functionString('makeurl', $location, $section_name, $section_id, 'CurrentPage', am:min($current + 1, am:max($page_count - 1, 0)))}">&gt;</a>
			</xsl:when>
			<xsl:otherwise>
				<span class="disabled">&gt;</span>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:variable>

	<func:result select="$output" />
</func:function>


</xsl:stylesheet>
