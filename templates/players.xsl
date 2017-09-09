<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
                xmlns="http://www.w3.org/1999/xhtml"
                xmlns:am="http://arcomage.net"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:exsl="http://exslt.org/common"
                extension-element-prefixes="exsl">
  <xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes"
              doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN"
              doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"/>

  <!-- includes -->
  <xsl:include href="main.xsl"/>

  <xsl:template match="section[. = 'Players']">
    <xsl:variable name="param" select="$params/players"/>

    <xsl:variable name="list" select="$param/list"/>

    <xsl:if test="$param/is_logged_in = 'yes' and $param/active_decks = 0">
      <p class="information-line information-line--inner warning">You need at least one ready deck to challenge other players.</p>
    </xsl:if>

    <xsl:if test="$param/free_slots = 0">
      <p class="information-line information-line--inner warning">You cannot initiate any more games.</p>
    </xsl:if>

    <xsl:choose>
      <xsl:when test="$param/is_logged_in = 'yes'">
        <!-- advanced navigation (for authenticated users only) -->
        <div class="filters">
          <!-- begin name filter -->
          <input type="text" name="pname_filter" maxlength="20" size="20" value="{$param/pname_filter}"
                 title="search phrase for player names"/>

          <!-- activity filter -->
          <xsl:variable name="activityTypes">
            <value name="No activity filter" value="none"/>
            <value name="Active players" value="active"/>
            <value name="Active and offline players" value="offline"/>
            <value name="Show all players" value="all"/>
          </xsl:variable>
          <xsl:copy-of select="am:htmlSelectBox('activity_filter', $param/activity_filter, $activityTypes, '')"/>

          <!-- status filter -->
          <xsl:variable name="statusTypes">
            <value name="No status filter" value="none"/>
            <value name="Looking for game" value="ready"/>
            <value name="Looking for quick game" value="quick"/>
            <value name="Do not disturb" value="dnd"/>
            <value name="Newbie" value="newbie"/>
          </xsl:variable>
          <xsl:copy-of select="am:htmlSelectBox('status_filter', $param/status_filter, $statusTypes, '')"/>

          <!-- players list sort -->
          <xsl:variable name="sortTypes">
            <value name="Level" value="level"/>
            <value name="Username" value="username"/>
            <value name="Country" value="country"/>
            <value name="Quarry gained" value="quarry"/>
            <value name="Magic gained" value="magic"/>
            <value name="Dungeons gained" value="dungeons"/>
            <value name="Rares played" value="rares"/>
            <value name="AI challenges" value="ai_challenges"/>
            <value name="Tower built" value="tower"/>
            <value name="Wall built" value="wall"/>
            <value name="Tower destroyed" value="tower_damage"/>
            <value name="Wall destroyed" value="wall_damage"/>
            <value name="Assassin" value="assassin"/>
            <value name="Builder" value="builder"/>
            <value name="Carpenter" value="carpenter"/>
            <value name="Collector" value="collector"/>
            <value name="Desolator" value="desolator"/>
            <value name="Dragon" value="dragon"/>
            <value name="Gentle touch" value="gentle_touch"/>
            <value name="Saboteur" value="saboteur"/>
            <value name="Snob" value="snob"/>
            <value name="Survivor" value="survivor"/>
            <value name="Titan" value="titan"/>
          </xsl:variable>
          <xsl:copy-of select="am:htmlSelectBox('players_sort', $param/players_sort, $sortTypes, '')"/>

          <button class="button-icon" type="submit" name="players_apply_filters" title="Apply filters">
            <span class="glyphicon glyphicon-filter"/>
          </button>
        </div>
        <div class="filters">
          <!-- upper navigation -->
          <xsl:copy-of select="am:upperNavigation($param/page_count, $param/current_page, 'players')"/>
        </div>
      </xsl:when>
      <xsl:otherwise>
        <!-- simple navigation (for anonymous users) -->
        <div class="filters">
          <xsl:copy-of select="am:simpleNavigation('Players', 'players_current_page', $param/current_page, $param/page_count)"/>
        </div>
      </xsl:otherwise>
    </xsl:choose>

    <!-- begin players list -->
    <div class="responsive-table responsive-table--with-avatars-sm responsive-table--centered table-sm skin-text top-level">
      <!-- table header -->
      <div class="row">
        <div class="col-sm-2"/>

        <xsl:variable name="columns">
          <column name="username" text="Username" sortable="yes" size="3"/>
          <column name="level" text="Level" sortable="yes" size="1"/>
          <column name="score" text="Wins / Losses / Draws" sortable="no" size="3"/>
          <column name="status" text="Status" sortable="no" size="2"/>
          <column name="other" text="" sortable="no" size="1"/>
        </xsl:variable>

        <xsl:for-each select="exsl:node-set($columns)/*">
          <div class="col-sm-{@size}">
            <p>
              <xsl:value-of select="@text"/>
            </p>
          </div>
        </xsl:for-each>
      </div>

      <!-- table body -->
      <xsl:for-each select="$list/*">
        <div class="row table-row table-row--details">
          <div class="col-sm-2">
            <p>
              <a href="{am:makeUrl('Players_details', 'Profile', name)}">
                <img class="avatar-image" height="60" width="60" src="{$param/avatar_path}{avatar}" alt="avatar"/>
              </a>
            </p>
          </div>
          <div class="col-sm-3">
            <p>
              <a class="hidden-link" href="{am:makeUrl('Players_achievements', 'Profile', name)}">
                <img class="icon-image" height="16" width="16" src="img/achievement.png" alt="{name}'s achievements"
                     title="{name}'s achievements"/>
              </a>
              <img class="icon-image" width="18" height="12" src="img/flags/{am:fileName(country)}.gif"
                   alt="country flag" title="{country}"/>
              <a class="hidden-link details-link" href="{am:makeUrl('Players_details', 'Profile', name)}">
                <xsl:value-of select="name"/>
              </a>

              <!-- choose name color according to inactivity time -->
              <xsl:choose>
                <!-- 3 weeks = dead -->
                <xsl:when test="inactivity &gt; 60 * 60 * 24 * 7 * 3">
                  <span class="icon-player-activity dead" title="dead"/>
                </xsl:when>
                <!-- 1 week = inactive -->
                <xsl:when test="inactivity &gt; 60 * 60 * 24 * 7 * 1">
                  <span class="icon-player-activity inactive" title="inactive"/>
                </xsl:when>
                <!-- 10 minutes = offline -->
                <xsl:when test="inactivity &gt; 60 * 10">
                  <span class="icon-player-activity offline" title="offline"/>
                </xsl:when>
                <!-- online -->
                <xsl:otherwise>
                  <span class="icon-player-activity online" title="online"/>
                </xsl:otherwise>
              </xsl:choose>

              <xsl:if test="rank != 'user'">
                <!-- player rank -->
                <img class="icon-image" width="9" height="12" src="img/{rank}.png" alt="rank flag" title="{rank}"/>
              </xsl:if>
            </p>
          </div>

          <div class="col-sm-1">
            <p>
              <xsl:value-of select="level"/>
            </p>
          </div>
          <div class="col-sm-3">
            <p>
              <xsl:value-of select="wins"/>
              <xsl:text> / </xsl:text>
              <xsl:value-of select="losses"/>
              <xsl:text> / </xsl:text>
              <xsl:value-of select="draws"/>
            </p>
          </div>

          <div class="col-sm-2">
            <p>
              <xsl:if test="status != 'none'">
                <img class="icon-image" width="20" height="14" src="img/{status}.png" alt="status flag" title="{status}"/>
              </xsl:if>
              <xsl:copy-of select="am:gameModeFlags(blind_flag, friendly_flag, long_flag)"/>
            </p>
          </div>

          <div class="col-sm-1">
            <p>
              <xsl:if test="$param/messages = 'yes'">
                <button class="button-icon" type="submit" name="message_create" value="{name}" title="Send message">
                  <span class="glyphicon glyphicon-send"/>
                </button>
              </xsl:if>
            </p>
          </div>
        </div>
      </xsl:for-each>
    </div>

    <xsl:if test="$param/is_logged_in = 'yes'">
      <div class="filters">
        <!-- lower navigation -->
        <xsl:copy-of select="am:lowerNavigation($param/page_count, $param/current_page, 'players', 'Players')"/>
      </div>
    </xsl:if>

    <input type="hidden" name="players_current_page" value="{$param/current_page}"/>
  </xsl:template>


  <xsl:template match="section[. = 'Players_details']">
    <xsl:variable name="param" select="$params/profile"/>
    <xsl:variable name="opponent" select="$param/player_name"/>
    <xsl:variable name="activeDecks" select="count($param/decks/*)"/>

    <div class="skin-text details-form">
      <h3><xsl:value-of select="$param/player_name"/>'s details
      </h3>
      <div class="row details-form__image-header">
        <div class="col-sm-4">
          <p>
            <img class="bordered-image" height="60" width="60" src="{$param/avatar_path}{$param/avatar}" alt="avatar"/>
          </p>
        </div>
        <div class="col-sm-4">
          <p>
            <a class="hidden-link" href="{am:makeUrl('Players_achievements', 'Profile', $param/player_name)}">
              <img height="29" width="29" src="img/achievement_large.png"
                   alt="{$param/player_name}'s achievements" title="{$param/player_name}'s achievements"/>
            </a>
          </p>
        </div>
        <div class="col-sm-4">
          <p>
            <img class="bordered-image" height="100" width="100" src="img/zodiac/{$param/sign}.jpg" alt="{$param/sign}" title="{$param/sign}"/>
          </p>
        </div>
      </div>
      <div class="row">
        <div class="col-sm-6">
          <div class="row">
            <div class="col-xs-12">
              <div class="exp-progress-bar exp-progress-bar--large">
                <xsl:attribute name="title">
                  <xsl:value-of select="$param/exp"/>
                  <xsl:text> / </xsl:text>
                  <xsl:value-of select="$param/next_level"/>
                </xsl:attribute>
                <div>
                  <xsl:attribute name="style">
                    <xsl:text>width: </xsl:text>
                    <xsl:value-of select="$param/exp_bar * 100"/>
                    <xsl:text>%</xsl:text>
                  </xsl:attribute>
                </div>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-xs-6">
              <p>Level</p>
            </div>
            <div class="col-xs-6">
              <p class="detail-value">
                <xsl:value-of select="$param/level"/>
              </p>
            </div>
          </div>
          <div class="row">
            <div class="col-xs-6">
              <p>Wins / Losses / Draws</p>
            </div>
            <div class="col-xs-6">
              <p class="detail-value">
                <xsl:value-of select="$param/wins"/>
                <xsl:text> / </xsl:text>
                <xsl:value-of select="$param/losses"/>
                <xsl:text> / </xsl:text>
                <xsl:value-of select="$param/draws"/>
              </p>
            </div>
          </div>
          <div class="row">
            <div class="col-xs-6">
              <p>Free slots</p>
            </div>
            <div class="col-xs-6">
              <p class="detail-value">
                <xsl:value-of select="$param/free_slots"/>
              </p>
            </div>
          </div>
          <div class="row">
            <div class="col-xs-6">
              <p>Rank</p>
            </div>
            <div class="col-xs-6">
              <p>
                <span class="detail-value">
                  <xsl:value-of select="$param/player_type"/>
                </span>
                <xsl:if test="$param/player_type != 'user'">
                  <img class="icon-image" width="9" height="12" src="img/{$param/player_type}.png" alt="rank flag" title="{$param/player_type}"/>
                </xsl:if>
              </p>
            </div>
          </div>
          <div class="row">
            <div class="col-xs-6">
              <p>Status</p>
            </div>
            <div class="col-xs-6">
              <xsl:if test="$param/status != 'none'">
                <img class="icon-image" width="20" height="14" src="img/{$param/status}.png" alt="status flag" title="{$param/status}"/>
              </xsl:if>
              <xsl:copy-of select="am:gameModeFlags(
                $param/blind_flag,
                $param/friendly_flag,
                $param/long_flag
              )"/>
            </div>
          </div>
          <div class="row">
            <div class="col-xs-6">
              <p>Country</p>
            </div>
            <div class="col-xs-6">
              <p>
                <img class="icon-image" width="18" height="12" src="img/flags/{am:fileName($param/country)}.gif"
                     alt="country flag" title="{$param/country}"/>
                <span class="detail-value">
                  <xsl:value-of select="$param/country"/>
                </span>
              </p>
            </div>
          </div>
          <div class="row">
            <div class="col-xs-6">
              <p>Gold</p>
            </div>
            <div class="col-xs-6">
              <p class="detail-value">
                <xsl:value-of select="$param/gold"/>
              </p>
            </div>
          </div>
          <div class="row">
            <div class="col-xs-6">
              <p>Bonus game slots</p>
            </div>
            <div class="col-xs-6">
              <p class="detail-value">
                <xsl:value-of select="$param/game_slots"/>
              </p>
            </div>
          </div>
          <div class="row">
            <div class="col-xs-6">
              <p>Bonus deck slots</p>
            </div>
            <div class="col-xs-6">
              <p class="detail-value">
                <xsl:value-of select="$param/deck_slots"/>
              </p>
            </div>
          </div>
          <div class="row">
            <div class="col-xs-6">
              <p>Forum posts</p>
            </div>
            <div class="col-xs-6">
              <p class="detail-value">
                <xsl:value-of select="$param/post_count"/>
              </p>
            </div>
          </div>
          <div class="row">
            <div class="col-xs-6">
              <p>Registered</p>
            </div>
            <div class="col-xs-6">
              <p class="detail-value">
                <xsl:choose>
                  <xsl:when test="$param/registered != '1970-01-01 00:00:01'">
                    <xsl:copy-of select="am:dateTime($param/registered, $param/timezone)"/>
                  </xsl:when>
                  <xsl:otherwise>Before 18. August, 2009</xsl:otherwise>
                </xsl:choose>
              </p>
            </div>
          </div>
          <div class="row">
            <div class="col-xs-6">
              <p>Last seen</p>
            </div>
            <div class="col-xs-6">
              <p class="detail-value">
                <xsl:choose>
                  <xsl:when test="$param/last_query != '1970-01-01 00:00:01'">
                    <xsl:copy-of select="am:dateTime($param/last_query, $param/timezone)"/>
                  </xsl:when>
                  <xsl:otherwise>n/a</xsl:otherwise>
                </xsl:choose>
              </p>
            </div>
          </div>
          <div class="row">
            <div class="col-xs-6">
              <p>First name</p>
            </div>
            <div class="col-xs-6">
              <p class="detail-value">
                <xsl:value-of select="$param/first_name"/>
              </p>
            </div>
          </div>
          <div class="row">
            <div class="col-xs-6">
              <p>Surname</p>
            </div>
            <div class="col-xs-6">
              <p class="detail-value">
                <xsl:value-of select="$param/surname"/>
              </p>
            </div>
          </div>

          <div class="row">
            <div class="col-xs-6">
              <p>Gender</p>
            </div>
            <div class="col-xs-6">
              <p class="detail-value">
                <xsl:value-of select="$param/gender"/>
              </p>
            </div>
          </div>

          <div class="row">
            <div class="col-xs-6">
              <p>E-mail</p>
            </div>
            <div class="col-xs-6">
              <p class="detail-value">
                <xsl:if test="$param/is_logged_in = 'yes'">
                  <xsl:value-of select="$param/email"/>
                </xsl:if>
              </p>
            </div>
          </div>

          <div class="row">
            <div class="col-xs-6">
              <p>ICQ / IM</p>
            </div>
            <div class="col-xs-6">
              <p class="detail-value">
                <xsl:value-of select="$param/im_number"/>
              </p>
            </div>
          </div>

          <div class="row">
            <div class="col-xs-6">
              <p>Date of birth</p>
            </div>
            <div class="col-xs-6">
              <p class="detail-value">
                <xsl:value-of select="$param/birth_date"/>
              </p>
            </div>
          </div>

          <div class="row">
            <div class="col-xs-6">
              <p>Age</p>
            </div>
            <div class="col-xs-6">
              <p class="detail-value">
                <xsl:value-of select="$param/age"/>
              </p>
            </div>
          </div>
        </div>
        <div class="col-sm-6">
          <xsl:if test="count($param/statistics/*) &gt; 0">
            <div class="details-form__data-list">

              <h3>Versus statistics</h3>

              <h4>Victories</h4>
              <xsl:for-each select="$param/statistics/wins/*">
                <p>
                  <span class="pull-right">
                    <xsl:value-of select="count"/> (<xsl:value-of select="ratio"/>%)
                  </span>
                  <xsl:value-of select="outcome_type"/>
                </p>
              </xsl:for-each>
              <p>
                <span class="pull-right">
                  <xsl:value-of select="$param/statistics/wins_total"/>
                </span>
                Total
              </p>

              <h4>Losses</h4>
              <xsl:for-each select="$param/statistics/losses/*">
                <p>
                  <span class="pull-right">
                    <xsl:value-of select="count"/> (<xsl:value-of select="ratio"/>%)
                  </span>
                  <xsl:value-of select="outcome_type"/>
                </p>
              </xsl:for-each>
              <p>
                <span class="pull-right">
                  <xsl:value-of select="$param/statistics/losses_total"/>
                </span>
                Total
              </p>

              <h4>Other</h4>
              <xsl:for-each select="$param/statistics/other/*">
                <p>
                  <span class="pull-right">
                    <xsl:value-of select="count"/> (<xsl:value-of select="ratio"/>%)
                  </span>
                  <xsl:value-of select="outcome_type"/>
                </p>
              </xsl:for-each>
              <p>
                <span class="pull-right">
                  <xsl:value-of select="$param/statistics/other_total"/>
                </span>
                Total
              </p>

              <h4>Average game duration</h4>
              <p>
                <span class="pull-right">
                  <xsl:value-of select="$param/statistics/turns"/>
                </span>
                Turns
              </p>
              <p>
                <span class="pull-right">
                  <xsl:value-of select="$param/statistics/rounds"/>
                </span>
                Rounds
              </p>
              <p>
                <span class="pull-right">
                  <xsl:value-of select="$param/statistics/turns_long"/>
                </span>
                <xsl:text>Turns</xsl:text>
                <img class="icon-image" width="20" height="14" src="img/long_mode.png" alt="Long mode" title="Long mode"/>
              </p>
              <p>
                <span class="pull-right">
                  <xsl:value-of select="$param/statistics/rounds_long"/>
                </span>
                <xsl:text>Rounds</xsl:text>
                <img class="icon-image" width="20" height="14" src="img/long_mode.png" alt="Long mode" title="Long mode"/>
              </p>

            </div>
          </xsl:if>
        </div>
      </div>

      <p>Hobbies, interests</p>
      <div class="details-form__content detail-value">
        <xsl:copy-of select="am:textEncode($param/hobby)"/>
      </div>

      <!--check if the player is allowed to challenge this opponent:-->
      <!-- - can't have more than MAX_GAMES active games + initiated challenges + received challenges-->
      <!-- - can't be in the $challengefrom['Player2'] or in the $activegames['Player1'] (['Player2'] is allowed)-->
      <!-- - can't play without a ready deck-->
      <!-- - can't challenge self-->
      <h4>Actions</h4>

      <xsl:if test="$param/messages = 'yes'">
        <button class="button-icon" type="submit" name="message_create" value="{am:urlEncode($opponent)}" title="Send message">
          <span class="glyphicon glyphicon-send"/>
        </button>
      </xsl:if>

      <xsl:if test="$param/send_challenges = 'yes' and $opponent != $param/current_player_name">

        <xsl:choose>

          <xsl:when test="$activeDecks &gt; 0 and $param/free_slots &gt; 0">
            <p>
              <select name="challenge_deck" size="1">
                <xsl:if test="$param/random_deck_option = 'yes'">
                  <option value="{$param/random_deck}">select random</option>
                </xsl:if>
                <xsl:for-each select="$param/decks/*">
                  <option value="{deck_id}">
                    <xsl:value-of select="deck_name"/>
                  </option>
                </xsl:for-each>
                <xsl:for-each select="$param/ai_challenges/*">
                  <xsl:sort select="fullname" order="ascending"/>
                  <option value="{name}" class="challenge-deck">
                    <xsl:value-of select="name"/>
                  </option>
                </xsl:for-each>
              </select>
              <xsl:variable name="timeoutValues">
                <value name="0" text="unlimited"/>
                <value name="86400" text="1 day"/>
                <value name="43200" text="12 hours"/>
                <value name="21600" text="6 hours"/>
                <value name="10800" text="3 hours"/>
                <value name="3600" text="1 hour"/>
                <value name="1800" text="30 minutes"/>
                <value name="300" text="5 minutes"/>
              </xsl:variable>
              <select name="turn_timeout" title="Turn timeout">
                <xsl:for-each select="exsl:node-set($timeoutValues)/*">
                  <option value="{@name}">
                    <xsl:if test="$param/timeout = @name">
                      <xsl:attribute name="selected">selected</xsl:attribute>
                    </xsl:if>
                    <xsl:value-of select="@text"/>
                  </option>
                </xsl:for-each>
              </select>
              <button class="button-icon" type="submit" name="send_challenge" value="{am:urlEncode($opponent)}" title="Send challenge">
                <span class="glyphicon glyphicon-tower"/>
              </button>
            </p>
            <p>
              <input type="checkbox" name="hidden_cards">
                <xsl:if test="$param/hidden_cards = 'yes'">
                  <xsl:attribute name="checked">checked</xsl:attribute>
                </xsl:if>
              </input>
              <xsl:text>Hide opponent's cards</xsl:text>
            </p>
            <p>
              <input type="checkbox" name="friendly_play">
                <xsl:if test="$param/friendly_play = 'yes'">
                  <xsl:attribute name="checked">checked</xsl:attribute>
                </xsl:if>
              </input>
              <xsl:text>Friendly play</xsl:text>
            </p>
            <p>
              <input type="checkbox" name="long_mode">
                <xsl:if test="$param/long_mode = 'yes'">
                  <xsl:attribute name="checked">checked</xsl:attribute>
                </xsl:if>
              </input>
              <xsl:text>Long mode</xsl:text>
            </p>
            <xsl:copy-of select="am:bbCodeButtons('content')"/>
            <textarea name="content" rows="10" cols="50"/>
          </xsl:when>

          <xsl:when test="$activeDecks = 0">
            <p class="information-line information-line--inner warning">
              You need at least one ready deck to challenge other players.
            </p>
          </xsl:when>

          <xsl:when test="$param/free_slots = 0">
            <p class="warning">You cannot initiate any more games.</p>
          </xsl:when>
        </xsl:choose>
      </xsl:if>

      <xsl:if test="$param/change_rights = 'yes'">
        <fieldset>
          <legend>Account options</legend>
          <p>
            <button type="submit" name="reset_password" value="{am:urlEncode($opponent)}">
              <xsl:text>Reset password</xsl:text>
            </button>
            <button type="submit" name="change_access" value="{am:urlEncode($opponent)}">
              <xsl:text>Change access rights</xsl:text>
            </button>
            <xsl:variable name="userTypes">
              <type name="moderator" text="Moderator"/>
              <type name="supervisor" text="Supervisor"/>
              <type name="user" text="User"/>
              <type name="squashed" text="Squashed"/>
              <type name="limited" text="Limited"/>
              <type name="banned" text="Banned"/>
            </xsl:variable>
            <select name="new_access" size="1">
              <xsl:for-each select="exsl:node-set($userTypes)/*">
                <option value="{@name}">
                  <xsl:if test="$param/player_type = @name">
                    <xsl:attribute name="selected">selected</xsl:attribute>
                  </xsl:if>
                  <xsl:value-of select="@text"/>
                </option>
              </xsl:for-each>
            </select>
          </p>
          <p>
            <button type="submit" name="delete_player" value="{am:urlEncode($opponent)}">
              <xsl:text>Delete player</xsl:text>
            </button>
            <button type="submit" name="rename_player" value="{am:urlEncode($opponent)}">
              <xsl:text>Rename player</xsl:text>
            </button>
            <input type="text" name="new_username" maxlength="20"/>
          </p>
        </fieldset>
      </xsl:if>

      <xsl:if test="$param/export_deck = 'yes' and count($param/export_decks/*) &gt; 0">
        <fieldset>
          <legend>Deck options</legend>
          <select name="exported_deck" size="1">
            <xsl:for-each select="$param/export_decks/*">
              <option value="{deck_id}">
                <xsl:value-of select="deck_name"/>
                <xsl:text> (</xsl:text>
                <xsl:value-of select="wins"/>
                <xsl:text> / </xsl:text>
                <xsl:value-of select="losses"/>
                <xsl:text> / </xsl:text>
                <xsl:value-of select="draws"/>
                <xsl:text>)</xsl:text>
              </option>
            </xsl:for-each>
          </select>
          <button type="submit" name="export_deck_remote" value="{am:urlEncode($opponent)}">
            <xsl:text>Export deck</xsl:text>
          </button>
        </fieldset>
      </xsl:if>

      <xsl:if test="$param/system_notification = 'yes'">
        <fieldset>
          <legend>System message</legend>
          <button type="submit" name="system_notification" value="{am:urlEncode($opponent)}">
            <xsl:text>Send system notification</xsl:text>
          </button>
        </fieldset>
      </xsl:if>

      <xsl:if test="$param/change_all_avatar = 'yes'">
        <fieldset>
          <legend>Avatar options</legend>
          <button type="submit" name="reset_avatar_remote" value="{am:urlEncode($opponent)}">
            <xsl:text>Reset avatar</xsl:text>
          </button>
        </fieldset>
      </xsl:if>

      <xsl:if test="$param/reset_exp = 'yes'">
        <fieldset>
          <legend>Score options</legend>
          <button type="submit" name="reset_exp" value="{am:urlEncode($opponent)}">Reset exp</button>
          <input type="text" name="gold_amount" maxlength="4" size="4"/>
          <button type="submit" name="add_gold" value="{am:urlEncode($opponent)}">Add gold</button>
        </fieldset>
      </xsl:if>
    </div>
  </xsl:template>


  <xsl:template match="section[. = 'Players_achievements']">
    <xsl:variable name="param" select="$params/achievements"/>

    <div class="skin-label top-level image-grid">
      <h3>
        <a href="{am:makeUrl('Players_details', 'Profile', $param/player_name)}">
          <xsl:value-of select="$param/player_name"/>
        </a>
        <xsl:text>'s achievements</xsl:text>
      </h3>

      <div class="row">
        <!-- number of columns (configurable) -->
        <xsl:variable name="columns" select="3"/>
        <xsl:for-each select="$param/data/*">
          <div class="col-md-4">
            <p class="image-grid__heading">Tier
              <xsl:value-of select="position()"/>
            </p>

            <xsl:variable name="currentTier" select="current()"/>

            <xsl:for-each select="current()/*[position() &lt;= floor(((count(current()/*) - 1) div $columns)) + 1]">
              <div class="row">
                <xsl:variable name="i" select="position()"/>
                <xsl:for-each select="exsl:node-set($currentTier)/*[position() &gt;= (($i - 1)*$columns + 1) and position() &lt;= $i*$columns]">
                  <div class="col-xs-4">
                    <img class="achievement" height="100" width="100" alt="{name}">
                      <xsl:choose>
                        <!-- case 1: final achievement -->
                        <xsl:when test="count = ''">
                          <xsl:attribute name="src">
                            <xsl:text>img/achievements/</xsl:text>
                            <xsl:value-of select="am:fileName(name)"/>
                            <xsl:if test="condition = 'yes'">_gained</xsl:if>
                            <xsl:text>.png</xsl:text>
                          </xsl:attribute>
                          <xsl:attribute name="title">
                            <xsl:value-of select="name"/>
                            <xsl:text> - </xsl:text>
                            <xsl:value-of select="desc"/>
                            <xsl:text> (</xsl:text>
                            <xsl:value-of select="reward"/>
                            <xsl:text> gold reward)</xsl:text>
                            <xsl:if test="condition = 'yes'">
                              <xsl:text> achievement already gained</xsl:text>
                            </xsl:if>
                          </xsl:attribute>
                        </xsl:when>
                        <!-- case 2: regular achievement -->
                        <xsl:otherwise>
                          <xsl:attribute name="src">
                            <xsl:text>img/achievements/</xsl:text>
                            <xsl:value-of select="am:fileName(name)"/>
                            <xsl:if test="count &gt;= condition">_gained</xsl:if>
                            <xsl:text>.png</xsl:text>
                          </xsl:attribute>
                          <xsl:attribute name="title">
                            <xsl:value-of select="name"/>
                            <xsl:text> (</xsl:text>
                            <xsl:value-of select="count"/>
                            <xsl:text> / </xsl:text>
                            <xsl:value-of select="condition"/>
                            <xsl:text>) - </xsl:text>
                            <xsl:value-of select="desc"/>
                            <xsl:text> (</xsl:text>
                            <xsl:value-of select="reward"/>
                            <xsl:text> gold reward)</xsl:text>
                            <xsl:if test="count &gt;= condition">
                              <xsl:text> achievement already gained</xsl:text>
                            </xsl:if>
                          </xsl:attribute>
                        </xsl:otherwise>
                      </xsl:choose>
                    </img>
                    <p class="image-grid__item-title">
                      <xsl:if test="(count = '' and condition = 'yes') or (count != '' and count &gt;= condition)">
                        <!-- highlight gained achievement -->
                        <xsl:attribute name="class">achievement-name info</xsl:attribute>
                      </xsl:if>
                      <xsl:value-of select="name"/>
                    </p>
                  </div>
                </xsl:for-each>
              </div>
            </xsl:for-each>
          </div>
        </xsl:for-each>
      </div>
    </div>
  </xsl:template>

</xsl:stylesheet>
