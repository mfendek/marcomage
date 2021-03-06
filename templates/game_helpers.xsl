<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
                xmlns="http://www.w3.org/1999/xhtml"
                xmlns:am="http://arcomage.net"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:date="http://exslt.org/dates-and-times"
                xmlns:exsl="http://exslt.org/common"
                xmlns:func="http://exslt.org/functions"
                xmlns:php="http://php.net/xsl"
                xmlns:str="http://exslt.org/strings"
                extension-element-prefixes="date func php str">
  <xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes"
              doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN"
              doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"/>

  <!-- resources and facilities into -->
  <func:function name="am:stockInfo">
    <xsl:param name="stock"/>
    <xsl:param name="changes"/>
    <xsl:param name="resourceVictory" as="xs:integer"/>

    <xsl:variable name="info">
      <div class="game__stock-info game__stock-info--bricks">
        <p class="game__facility">
          <xsl:attribute name="title">
            <xsl:text>Quarry: </xsl:text>
            <xsl:value-of select="$stock/quarry"/>
            <xsl:text> (Facilities total: </xsl:text>
            <xsl:value-of select="$stock/quarry + $stock/magic + $stock/dungeons"/>
            <xsl:text>)</xsl:text>
          </xsl:attribute>
          <xsl:value-of select="$stock/quarry"/>
          <xsl:if test="$changes/quarry != 0">
            <span class="game__empire-changes">
              <xsl:value-of select="$changes/quarry"/>
            </span>
          </xsl:if>
        </p>
        <p class="game__resource">
          <xsl:attribute name="title">
            <xsl:text>Bricks: </xsl:text>
            <xsl:value-of select="$stock/bricks"/>
            <xsl:text> (Resources total: </xsl:text>
            <xsl:value-of select="$stock/bricks + $stock/gems + $stock/recruits"/>
            <xsl:text> / </xsl:text>
            <xsl:value-of select="$resourceVictory"/>
            <xsl:text>)</xsl:text>
          </xsl:attribute>
          <xsl:value-of select="$stock/bricks"/>
          <xsl:if test="$changes/bricks != 0">
            <span class="game__empire-changes">
              <xsl:value-of select="$changes/bricks"/>
            </span>
          </xsl:if>
        </p>
      </div>
      <div class="game__stock-info game__stock-info--gems">
        <p class="game__facility">
          <xsl:attribute name="title">
            <xsl:text>Magic: </xsl:text>
            <xsl:value-of select="$stock/magic"/>
            <xsl:text> (Facilities total: </xsl:text>
            <xsl:value-of select="$stock/quarry + $stock/magic + $stock/dungeons"/>
            <xsl:text>)</xsl:text>
          </xsl:attribute>
          <xsl:value-of select="$stock/magic"/>
          <xsl:if test="$changes/magic != 0">
            <span class="game__empire-changes">
              <xsl:value-of select="$changes/magic"/>
            </span>
          </xsl:if>
        </p>
        <p class="game__resource">
          <xsl:attribute name="title">
            <xsl:text>Gems: </xsl:text>
            <xsl:value-of select="$stock/gems"/>
            <xsl:text> (Resources total: </xsl:text>
            <xsl:value-of select="$stock/bricks + $stock/gems + $stock/recruits"/>
            <xsl:text> / </xsl:text>
            <xsl:value-of select="$resourceVictory"/>
            <xsl:text>)</xsl:text>
          </xsl:attribute>
          <xsl:value-of select="$stock/gems"/>
          <xsl:if test="$changes/gems != 0">
            <span class="game__empire-changes">
              <xsl:value-of select="$changes/gems"/>
            </span>
          </xsl:if>
        </p>
      </div>
      <div class="game__stock-info game__stock-info--recruits">
        <p class="game__facility">
          <xsl:attribute name="title">
            <xsl:text>Dungeon: </xsl:text>
            <xsl:value-of select="$stock/dungeons"/>
            <xsl:text> (Facilities total: </xsl:text>
            <xsl:value-of select="$stock/quarry + $stock/magic + $stock/dungeons"/>
            <xsl:text>)</xsl:text>
          </xsl:attribute>
          <xsl:value-of select="$stock/dungeons"/>
          <xsl:if test="$changes/dungeons != 0">
            <span class="game__empire-changes">
              <xsl:value-of select="$changes/dungeons"/>
            </span>
          </xsl:if>
        </p>
        <p class="game__resource">
          <xsl:attribute name="title">
            <xsl:text>Recruits: </xsl:text>
            <xsl:value-of select="$stock/recruits"/>
            <xsl:text> (Resources total: </xsl:text>
            <xsl:value-of select="$stock/bricks + $stock/gems + $stock/recruits"/>
            <xsl:text> / </xsl:text>
            <xsl:value-of select="$resourceVictory"/>
            <xsl:text>)</xsl:text>
          </xsl:attribute>
          <xsl:value-of select="$stock/recruits"/>
          <xsl:if test="$changes/recruits != 0">
            <span class="game__empire-changes">
              <xsl:value-of select="$changes/recruits"/>
            </span>
          </xsl:if>
        </p>
      </div>
    </xsl:variable>
    <func:result select="exsl:node-set($info)"/>
  </func:function>


  <!-- castle info -->
  <func:function name="am:castleInfo">
    <xsl:param name="tower" as="xs:integer"/>
    <xsl:param name="changeTower" as="xs:integer"/>
    <xsl:param name="maxTower" as="xs:integer"/>
    <xsl:param name="wall" as="xs:integer"/>
    <xsl:param name="changeWall" as="xs:integer"/>
    <xsl:param name="maxWall" as="xs:integer"/>

    <xsl:variable name="info">
      <div>
        <p class="info-label">
          <span>
            <xsl:attribute name="title">
              <xsl:text>Tower: </xsl:text>
              <xsl:value-of select="$tower"/>
              <xsl:text> / </xsl:text>
              <xsl:value-of select="$maxTower"/>
              <xsl:text> (Castle total: </xsl:text>
              <xsl:value-of select="$tower + $wall"/>
              <xsl:text> / </xsl:text>
              <xsl:value-of select="$maxTower + $maxWall"/>
              <xsl:text>)</xsl:text>
            </xsl:attribute>
            <xsl:text>Tower: </xsl:text>
            <xsl:value-of select="$tower"/>
          </span>
          <xsl:if test="$changeTower != 0">
            <span class="game__empire-changes">
              <xsl:value-of select="$changeTower"/>
            </span>
          </xsl:if>
        </p>
        <p class="info-label">
          <span>
            <xsl:attribute name="title">
              <xsl:text>Wall: </xsl:text>
              <xsl:value-of select="$wall"/>
              <xsl:text> / </xsl:text>
              <xsl:value-of select="$maxWall"/>
              <xsl:text> (Castle total: </xsl:text>
              <xsl:value-of select="$tower + $wall"/>
              <xsl:text> / </xsl:text>
              <xsl:value-of select="$maxTower + $maxWall"/>
              <xsl:text>)</xsl:text>
            </xsl:attribute>
            <xsl:text>Wall: </xsl:text>
            <xsl:value-of select="$wall"/>
          </span>
          <xsl:if test="$changeWall != 0">
            <span class="game__empire-changes">
              <xsl:value-of select="$changeWall"/>
            </span>
          </xsl:if>
        </p>
      </div>
    </xsl:variable>
    <func:result select="exsl:node-set($info)"/>
  </func:function>


  <func:function name="am:hasGameMode">
    <xsl:param name="input" as="xs:string"/>
    <xsl:param name="mode" as="xs:string"/>

    <xsl:variable name="result">
      <xsl:choose>
        <xsl:when test="contains($input, $mode)">yes</xsl:when>
        <xsl:otherwise>no</xsl:otherwise>
      </xsl:choose>
    </xsl:variable>
    <func:result select="$result"/>
  </func:function>


  <!-- castle graphic -->
  <func:function name="am:castleDisplay">
    <xsl:param name="orientation" as="xs:string"/>
    <xsl:param name="tower" as="xs:integer"/>
    <xsl:param name="wall" as="xs:integer"/>
    <xsl:param name="maxTower" as="xs:integer"/>
    <xsl:param name="maxWall" as="xs:integer"/>

    <!-- determine tower color -->
    <xsl:variable name="color">
      <xsl:choose>
        <xsl:when test="$orientation = 'left'">red</xsl:when>
        <xsl:otherwise>blue</xsl:otherwise>
      </xsl:choose>
    </xsl:variable>

    <xsl:variable name="result">
      <div class="game__castle-graphic">
        <!-- wall left of tower -->
        <xsl:if test="$orientation = 'right'">
          <xsl:if test="$wall &gt; 0">
            <div class="game__wall-display">
              <img src="img/game/wall_top.png" width="19" height="11" alt=""/>
              <div style="height: {270 * $wall div $maxWall}px;"/>
            </div>
          </xsl:if>
        </xsl:if>
        <!-- tower -->
        <div class="game__tower-display">
          <img width="65" alt="">
            <xsl:choose>
              <xsl:when test="$tower = $maxTower">
                <xsl:attribute name="src">
                  <xsl:text>img/game/victory_top_</xsl:text>
                  <xsl:value-of select="$color"/>
                  <xsl:text>.png</xsl:text>
                </xsl:attribute>
                <xsl:attribute name="height">114</xsl:attribute>
              </xsl:when>
              <xsl:otherwise>
                <xsl:attribute name="src">
                  <xsl:text>img/game/tower_top_</xsl:text>
                  <xsl:value-of select="$color"/>
                  <xsl:text>.png</xsl:text>
                </xsl:attribute>
                <xsl:attribute name="height">91</xsl:attribute>
              </xsl:otherwise>
            </xsl:choose>
          </img>
          <div style="height: {170 * $tower div $maxTower}px;"/>
        </div>
        <!-- wall right of tower -->
        <xsl:if test="$orientation = 'left'">
          <xsl:if test="$wall &gt; 0">
            <div class="game__wall-display">
              <img src="img/game/wall_top.png" width="19" height="11" alt=""/>
              <div style="height: {270 * $wall div $maxWall}px;"/>
            </div>
          </xsl:if>
        </xsl:if>
      </div>
    </xsl:variable>
    <func:result select="exsl:node-set($result)"/>
  </func:function>


  <!-- discarded cards -->
  <func:function name="am:discardedCards">
    <xsl:param name="leftList"/>
    <xsl:param name="rightList"/>
    <xsl:param name="oldLook" as="xs:string"/>
    <xsl:param name="insignias" as="xs:string"/>
    <xsl:param name="foils" as="xs:string"/>

    <xsl:variable name="result">
      <xsl:choose>
        <xsl:when test="count($leftList/*) = 0 and count($rightList/*) = 0">
          <div class="game__card-list"/>
        </xsl:when>
        <xsl:otherwise>
          <div class="game__card-list">
            <p class="info-label info-label--small">Discarded</p>
            <div class="game__card-log">
              <xsl:for-each select="$leftList/*">
                <div>
                  <xsl:copy-of select="am:cardString(current(), $oldLook, $insignias, $foils)"/>
                </div>
              </xsl:for-each>
              <div class="game__card-log-separator"/>
              <xsl:for-each select="$rightList/*">
                <div>
                  <xsl:copy-of select="am:cardString(current(), $oldLook, $insignias, $foils)"/>
                </div>
              </xsl:for-each>
            </div>
          </div>
        </xsl:otherwise>
      </xsl:choose>
    </xsl:variable>
    <func:result select="exsl:node-set($result)"/>
  </func:function>


  <!-- card history  -->
  <func:function name="am:cardHistory">
    <xsl:param name="cardList"/>
    <xsl:param name="oldLook" as="xs:string"/>
    <xsl:param name="insignias" as="xs:string"/>
    <xsl:param name="foils" as="xs:string"/>

    <xsl:variable name="result">
      <div class="game__card-log">
        <xsl:if test="count($cardList/*) &gt; 0">
          <xsl:for-each select="$cardList/*">
            <xsl:sort select="card_position" order="descending" data-type="number"/>
            <div>
              <p>
                <xsl:choose>
                  <xsl:when test="card_action = 'play'">
                    <xsl:attribute name="class">card-flag card-flag--played</xsl:attribute>
                    <xsl:text>played</xsl:text>
                    <xsl:if test="card_mode != 0">
                      <span>
                        <xsl:text> mode </xsl:text>
                        <xsl:value-of select="card_mode"/>
                      </span>
                    </xsl:if>
                  </xsl:when>
                  <xsl:otherwise>
                    <xsl:attribute name="class">card-flag card-flag--discarded</xsl:attribute>
                    <xsl:text>discarded</xsl:text>
                  </xsl:otherwise>
                </xsl:choose>
              </p>
              <xsl:copy-of select="am:cardString(card_data, $oldLook, $insignias, $foils)"/>
            </div>
          </xsl:for-each>
        </xsl:if>
      </div>
    </xsl:variable>
    <func:result select="exsl:node-set($result)"/>
  </func:function>


  <!-- tokens -->
  <func:function name="am:tokens">
    <xsl:param name="tokensList"/>
    <xsl:param name="insignias" as="xs:string"/>

    <xsl:variable name="result">
      <xsl:for-each select="$tokensList/*">
        <xsl:if test="name != 'none'">
          <p class="token-counter">
            <xsl:if test="change &lt; 0">
              <xsl:attribute name="style">color: lime</xsl:attribute>
            </xsl:if>
            <xsl:choose>
              <xsl:when test="$insignias = 'yes'">
                <img class="keyword-insignia" src="img/insignias/{am:fileName(name)}.png" width="12" height="12"
                     alt="{name}" title="{name}"/>
              </xsl:when>
              <xsl:otherwise>
                <xsl:value-of select="name"/>
              </xsl:otherwise>
            </xsl:choose>
            <span>
              <xsl:value-of select="value"/>
            </span>
            <xsl:if test="change != 0">
              <span class="game__empire-changes">
                <xsl:if test="change &gt; 0">+</xsl:if>
                <xsl:value-of select="change"/>
              </span>
            </xsl:if>
          </p>
        </xsl:if>
      </xsl:for-each>
    </xsl:variable>
    <func:result select="exsl:node-set($result)"/>
  </func:function>


  <!-- player hand -->
  <func:function name="am:hand">
    <xsl:param name="cardList"/>
    <xsl:param name="hiddenCards" as="xs:string"/>
    <xsl:param name="miniFlags" as="xs:string"/>
    <xsl:param name="oldLook" as="xs:string"/>
    <xsl:param name="insignias" as="xs:string"/>
    <xsl:param name="foils" as="xs:string"/>

    <xsl:variable name="result">
      <xsl:for-each select="$cardList/*">
        <div>
          <!--  display card flags, if set -->
          <xsl:choose>
            <xsl:when test="$hiddenCards = 'yes' and revealed = 'yes' and $miniFlags = 'no'">
              <div class="flag-space">
                <xsl:if test="new_card = 'yes'">
                  <span class="new-card">new</span>
                </xsl:if>
                <img src="img/game/revealed.png" width="20" height="14" alt="revealed" title="Revealed"/>
              </div>
            </xsl:when>
            <xsl:when test="new_card = 'yes' and $miniFlags = 'no'">
              <p class="card-flag">new card</p>
            </xsl:when>
          </xsl:choose>

          <!-- display card -->
          <xsl:variable name="revealed" select="$miniFlags = 'yes' and $hiddenCards = 'yes' and revealed = 'yes'"/>
          <xsl:variable name="newCard" select="$miniFlags = 'yes' and new_card = 'yes'"/>
          <xsl:copy-of select="am:cardString(card_data, $oldLook, $insignias, $foils, $newCard, $revealed)"/>
        </div>
      </xsl:for-each>
    </xsl:variable>
    <func:result select="exsl:node-set($result)"/>
  </func:function>


  <!-- opponent's hand -->
  <func:function name="am:opponentHand">
    <xsl:param name="cardList"/>
    <xsl:param name="hiddenCards" as="xs:string"/>
    <xsl:param name="miniFlags" as="xs:string"/>
    <xsl:param name="oldLook" as="xs:string"/>
    <xsl:param name="insignias" as="xs:string"/>
    <xsl:param name="foils" as="xs:string"/>
    <xsl:param name="gameState" as="xs:string" select="''"/>

    <xsl:variable name="result">
      <xsl:variable name="showCards" select="$gameState != '' and $gameState != 'in progress'"/>

      <xsl:for-each select="$cardList/*">
        <div>
          <!--  display new card indicator, if set -->
          <xsl:if test="new_card = 'yes' and ($hiddenCards = 'no' or revealed = 'yes' or $showCards) and $miniFlags = 'no'">
            <p class="card-flag">new card</p>
          </xsl:if>

          <!-- display card -->
          <xsl:choose>
            <xsl:when test="($hiddenCards = 'yes') and (revealed = 'no') and not($showCards)">
              <div class="hidden-card">
                <!--  display new card indicator, if set -->
                <xsl:if test="new_card = 'yes'">
                  <p class="card-flag">new card</p>
                </xsl:if>
              </div>
            </xsl:when>
            <xsl:otherwise>
              <div>
                <xsl:if test="playable = 'no'">
                  <xsl:attribute name="class">unplayable</xsl:attribute>
                </xsl:if>
                <xsl:variable name="newCard" select="$miniFlags = 'yes' and new_card = 'yes'"/>
                <xsl:copy-of select="am:cardString(card_data, $oldLook, $insignias, $foils, $newCard)"/>
              </div>
            </xsl:otherwise>
          </xsl:choose>
        </div>
      </xsl:for-each>
    </xsl:variable>
    <func:result select="exsl:node-set($result)"/>
  </func:function>


  <!-- player name -->
  <func:function name="am:playerName">
    <xsl:param name="name" as="xs:string"/>
    <xsl:param name="aiName" as="xs:string"/>
    <xsl:param name="systemName" as="xs:string"/>

    <xsl:variable name="result">
      <xsl:choose>
        <!-- case 1: AI name -->
        <xsl:when test="$name = $systemName">
          <span>
            <xsl:choose>
              <!-- case 1: AI challenge name -->
              <xsl:when test="$aiName != ''">
                <xsl:value-of select="$aiName"/>
              </xsl:when>
              <!-- case 2: AI standard name -->
              <xsl:otherwise>
                <xsl:value-of select="$systemName"/>
              </xsl:otherwise>
            </xsl:choose>
          </span>
        </xsl:when>
        <!-- case 2: real player name -->
        <xsl:otherwise>
          <a class="hidden-link" href="{am:makeUrl('Players_details', 'Profile', $name)}">
            <xsl:value-of select="$name"/>
          </a>
        </xsl:otherwise>
      </xsl:choose>
    </xsl:variable>
    <func:result select="exsl:node-set($result)"/>
  </func:function>


  <!-- avatar file name -->
  <func:function name="am:avatarFileName">
    <xsl:param name="avatar" as="xs:string"/>
    <xsl:param name="name" as="xs:string"/>
    <xsl:param name="aiName" as="xs:string"/>
    <xsl:param name="systemName" as="xs:string"/>

    <xsl:variable name="result">
      <xsl:choose>
        <!-- case 1: AI avatar -->
        <xsl:when test="$name = $systemName">
          <xsl:choose>
            <!-- case 1: AI challenge avatar -->
            <xsl:when test="$aiName != ''">
              <xsl:value-of select="concat(am:fileName($aiName), '.png')"/>
            </xsl:when>
            <!-- case 2: standard AI avatar -->
            <xsl:otherwise>ai.png</xsl:otherwise>
          </xsl:choose>
        </xsl:when>
        <!-- case 2: real player avatar -->
        <xsl:otherwise>
          <xsl:value-of select="$avatar"/>
        </xsl:otherwise>
      </xsl:choose>
    </xsl:variable>
    <func:result select="$result"/>
  </func:function>

</xsl:stylesheet>
