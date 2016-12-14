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


    <xsl:template match="section[. = 'Settings']">
        <xsl:variable name="param" select="$params/settings"/>
        <xsl:variable name="settings" select="$param/current_settings"/>

        <div id="settings">
            <div class="row">
                <div class="col-md-6">

                    <!-- user settings -->
                    <div id="profile-settings" class="skin-text top-level">

                        <h3>Profile settings</h3>

                        <div>
                            <h4>Zodiac sign</h4>
                            <img height="100" width="100" src="img/zodiac/{$settings/sign}.jpg" alt="sign"/>
                            <h4><xsl:value-of select="$settings/sign"/></h4>
                        </div>

                        <div>
                            <h4>Avatar</h4>
                            <img height="60" width="60" src="{$param/avatar_path}{$settings/Avatar}" alt="avatar"/>
                        </div>

                        <p><button type="submit" name="save_settings">Save settings</button></p>
                        <p>
                            <input type="text" name="Firstname" maxlength="20" value="{$settings/Firstname}"/>
                            <xsl:text>First name</xsl:text>
                        </p>

                        <p>
                            <input type="text" name="Surname" maxlength="20" value="{$settings/Surname}"/>
                            <xsl:text>Surname</xsl:text>
                        </p>

                        <p>
                            <input type="email" name="Email" maxlength="30" value="{$settings/Email}"/>
                            <xsl:text>E-mail</xsl:text>
                        </p>

                        <p>
                            <input type="text" name="Imnumber" maxlength="20" value="{$settings/Imnumber}"/>
                            <xsl:text>ICQ / IM number</xsl:text>
                        </p>

                        <p>
                            <select name="Gender">
                                <xsl:variable name="genderTypes">
                                    <type name="none" text="select"/>
                                    <type name="male" text="male"/>
                                    <type name="female" text="female"/>
                                </xsl:variable>
                                <xsl:for-each select="exsl:node-set($genderTypes)/*">
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
                            <input type="date" name="Birthdate" min="1000-01-01" max="2016-01-01" size="10" value="{$settings/birth_date}"/>
                            <xsl:text>Date of birth (dd-mm-yyyy)</xsl:text>
                        </p>

                        <p>
                            <xsl:text>Age: </xsl:text>
                            <xsl:value-of select="$settings/age"/>
                        </p>

                        <p>
                            <xsl:text>Rank: </xsl:text>
                            <xsl:value-of select="$param/player_type"/>
                        </p>

                        <p>
                            <select name="Country">
                                <option value="Unknown">I'm a pirate - no country</option>
                                <xsl:variable name="countries">
                                    <country name="Albania"/>
                                    <country name="Algeria"/>
                                    <country name="Argentina"/>
                                    <country name="Armenia"/>
                                    <country name="Australia"/>
                                    <country name="Austria"/>
                                    <country name="Azerbaijan"/>
                                    <country name="Bahamas"/>
                                    <country name="Barbados"/>
                                    <country name="Belarus"/>
                                    <country name="Belgium"/>
                                    <country name="Bolivia"/>
                                    <country name="Bosnia and Herzegovina"/>
                                    <country name="Brazil"/>
                                    <country name="Bulgaria"/>
                                    <country name="Cambodia"/>
                                    <country name="Canada"/>
                                    <country name="Chile"/>
                                    <country name="China"/>
                                    <country name="Chinese Taipei"/>
                                    <country name="Colombia"/>
                                    <country name="Costa Rica"/>
                                    <country name="Croatia"/>
                                    <country name="Cuba"/>
                                    <country name="Cyprus"/>
                                    <country name="Czech Republic"/>
                                    <country name="Denmark"/>
                                    <country name="Dominican Republic"/>
                                    <country name="Ecuador"/>
                                    <country name="United Kingdom"/>
                                    <country name="Eritrea"/>
                                    <country name="Estonia"/>
                                    <country name="Ethiopia"/>
                                    <country name="Europe"/>
                                    <country name="Fiji Islands"/>
                                    <country name="Finland"/>
                                    <country name="France"/>
                                    <country name="Germany"/>
                                    <country name="Ghana"/>
                                    <country name="Greece"/>
                                    <country name="Greenland"/>
                                    <country name="Guatemala"/>
                                    <country name="Hungary"/>
                                    <country name="Iceland"/>
                                    <country name="India"/>
                                    <country name="Indonesia"/>
                                    <country name="Iran"/>
                                    <country name="Iraq"/>
                                    <country name="Ireland"/>
                                    <country name="Israel"/>
                                    <country name="Italy"/>
                                    <country name="Ivory Coast"/>
                                    <country name="Japan"/>
                                    <country name="Jamaica"/>
                                    <country name="Kazakstan"/>
                                    <country name="Kenya"/>
                                    <country name="Laos"/>
                                    <country name="Latvia"/>
                                    <country name="Liechtenstein"/>
                                    <country name="Lithuania"/>
                                    <country name="Macedonia"/>
                                    <country name="Malaysia"/>
                                    <country name="Mexico"/>
                                    <country name="Moldova"/>
                                    <country name="Morocco"/>
                                    <country name="Netherlands"/>
                                    <country name="New Zealand"/>
                                    <country name="North Korea"/>
                                    <country name="Norway"/>
                                    <country name="Pakistan"/>
                                    <country name="Panama"/>
                                    <country name="Paraguay"/>
                                    <country name="Peru"/>
                                    <country name="Philippines"/>
                                    <country name="Poland"/>
                                    <country name="Portugal"/>
                                    <country name="Puerto Rico"/>
                                    <country name="Russia"/>
                                    <country name="Romania"/>
                                    <country name="Salvador"/>
                                    <country name="San Marino"/>
                                    <country name="Saudi Arabia"/>
                                    <country name="Serbia"/>
                                    <country name="Singapore"/>
                                    <country name="Slovakia"/>
                                    <country name="Slovenia"/>
                                    <country name="Somalia"/>
                                    <country name="South Africa"/>
                                    <country name="South Korea"/>
                                    <country name="Spain"/>
                                    <country name="Sri Lanka"/>
                                    <country name="Sudan"/>
                                    <country name="Sweden"/>
                                    <country name="Switzerland"/>
                                    <country name="Taiwan"/>
                                    <country name="Thailand"/>
                                    <country name="Togo"/>
                                    <country name="Trinidad"/>
                                    <country name="Turkey"/>
                                    <country name="Ukraine"/>
                                    <country name="United Arab Emirates"/>
                                    <country name="United Kingdom"/>
                                    <country name="United States"/>
                                    <country name="Uzbekistan"/>
                                    <country name="Venezuela"/>
                                    <country name="Vietnam"/>
                                    <country name="Zimbabwe"/>
                                </xsl:variable>
                                <xsl:for-each select="exsl:node-set($countries)/*">
                                    <option value="{@name}">
                                        <xsl:if test="$settings/Country = @name">
                                            <xsl:attribute name="selected">selected</xsl:attribute>
                                        </xsl:if>
                                        <xsl:value-of select="@name"/>
                                    </option>
                                </xsl:for-each>
                            </select>
                            <xsl:text>Country</xsl:text>
                            <img class="icon" width="18" height="12" src="img/flags/{$settings/Country}.gif" alt="country flag" title="{$settings/Country}"/>
                        </p>

                        <p>
                            <select name="Status">
                                <xsl:variable name="statusTypes">
                                    <status name="none" text="none"/>
                                    <status name="ready" text="looking for game"/>
                                    <status name="quick" text="looking for quick game"/>
                                    <status name="dnd" text="do not disturb"/>
                                    <status name="newbie" text="newbie"/>
                                </xsl:variable>
                                <xsl:for-each select="exsl:node-set($statusTypes)/*">
                                    <option value="{@name}">
                                        <xsl:if test="$settings/Status = @name">
                                            <xsl:attribute name="selected">selected</xsl:attribute>
                                        </xsl:if>
                                        <xsl:value-of select="@text"/>
                                    </option>
                                </xsl:for-each>
                            </select>
                            <xsl:text>Status</xsl:text>
                            <xsl:if test="$settings/Status != 'none'">
                                <img class="icon" width="20" height="14" src="img/{$settings/Status}.png" alt="status flag" title="{$settings/Status}"/>
                            </xsl:if>
                        </p>

                        <p>Game mode flags</p>
                        <p>
                            <input type="checkbox" name="BlindFlag">
                                <xsl:if test="$settings/BlindFlag = 'yes'">
                                    <xsl:attribute name="checked">checked</xsl:attribute>
                                </xsl:if>
                            </input>
                            <span>
                                <xsl:attribute name="title">players are unable to see each other's cards</xsl:attribute>
                                <xsl:text>Hidden cards</xsl:text>
                            </span>
                            <xsl:if test="$settings/BlindFlag = 'yes'">
                                <img class="icon" width="20" height="14" src="img/blind.png" alt="Hidden cards" title="Hidden cards"/>
                            </xsl:if>
                        </p>
                        <p>
                            <input type="checkbox" name="FriendlyFlag">
                                <xsl:if test="$settings/FriendlyFlag = 'yes'">
                                    <xsl:attribute name="checked">checked</xsl:attribute>
                                </xsl:if>
                            </input>
                            <span>
                                <xsl:attribute name="title">the game will not effect player's score</xsl:attribute>
                                <xsl:text>Friendly play</xsl:text>
                            </span>
                            <xsl:if test="$settings/FriendlyFlag = 'yes'">
                                <img class="icon" width="20" height="14" src="img/friendly_play.png" alt="Friendly play" title="Friendly play"/>
                            </xsl:if>
                        </p>
                        <p>
                            <input type="checkbox" name="LongFlag">
                                <xsl:if test="$settings/LongFlag = 'yes'">
                                    <xsl:attribute name="checked">checked</xsl:attribute>
                                </xsl:if>
                            </input>
                            <span>
                                <xsl:attribute name="title">
                                    <xsl:text>starting and maximum tower and wall values are higher in this game mode</xsl:text>
                                </xsl:attribute>
                                <xsl:text>Long mode</xsl:text>
                            </span>
                            <xsl:if test="$settings/LongFlag = 'yes'">
                                <img class="icon" width="20" height="14" src="img/long_mode.png" alt="Long mode" title="Long mode"/>
                            </xsl:if>
                        </p>

                        <p>Hobbies, Interests:</p>
                        <p>
                            <textarea name="Hobby" rows="5" cols="30">
                                <xsl:value-of select="$settings/Hobby"/>
                            </textarea>
                        </p>

                        <xsl:if test="$param/change_own_avatar = 'yes'">
                            <h4>Avatar options</h4>
                            <p>
                                <input type="file" name="avatar_image_file"/>
                                <button class="button-icon" type="submit" name="upload_avatar_image" title="Upload file">
                                    <span class="glyphicon glyphicon-upload"/>
                                </button>
                                <button class="button-icon" type="submit" name="reset_avatar" title="Clear avatar">
                                    <span class="glyphicon glyphicon-trash"/>
                                </button>
                            </p>
                        </xsl:if>
                        <hr/>

                        <h4>MArcomage shop</h4>
                        <p>
                            <span class="wallet"><xsl:value-of select="$param/gold"/> gold available</span>
                            <select name="selected_item">
                                <xsl:variable name="shopItems">
                                    <item name="game_slot" text="Game slot"/>
                                    <item name="deck_slot" text="Deck slot"/>
                                </xsl:variable>
                                <xsl:for-each select="exsl:node-set($shopItems)/*">
                                    <option value="{@name}">
                                        <xsl:value-of select="@text"/>
                                    </option>
                                </xsl:for-each>
                            </select>
                            <button class="button-icon" type="submit" name="buy_item" title="Purchase item">
                                <span class="glyphicon glyphicon-usd"/>
                            </button>
                        </p>
                        <div class="shop-item">
                            <p id="game_slot_desc">
                                <xsl:text>Extra game slot for </xsl:text>
                                <xsl:value-of select="$param/game_slot_cost"/>
                                <xsl:text> gold</xsl:text>
                            </p>
                            <p>More game slots will allow you to play more games at the same time</p>
                            <p>
                                <xsl:text>Extra game slots already purchased: </xsl:text>
                                <b><xsl:value-of select="$param/game_slots"/></b>
                            </p>
                        </div>
                        <div class="shop-item">
                            <p id="deck_slot_desc">
                                <xsl:text>Extra deck slot for </xsl:text>
                                <xsl:value-of select="$param/deck_slot_cost"/>
                                <xsl:text> gold</xsl:text>
                            </p>
                            <p>More deck slots will allow you to have more decks to play with</p>
                            <p>
                                <xsl:text>Extra deck slots already purchased: </xsl:text>
                                <b><xsl:value-of select="$param/deck_slots"/></b>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">

                    <!-- game settings -->
                    <div id="account-settings" class="skin-text top-level">
                        <div>
                            <h3>Account settings</h3>
                            <p>
                                <button type="submit" name="save_settings">Save settings</button>
                                <xsl:if test="$param/player_level &lt; $param/tutorial_end">
                                    <button type="submit" name="skip_tutorial">Skip tutorial</button>
                                </xsl:if>
                            </p>

                            <p>
                                <input type="password" name="password" maxlength="20" placeholder="Password..."/>
                                <xsl:text>New password</xsl:text>
                            </p>
                            <p>
                                <input type="password" name="confirm_password" maxlength="20" placeholder="Confirm password..."/>
                                <xsl:text>Confirm password</xsl:text>
                            </p>
                            <p><button type="submit" name="change_password">Change password</button></p>

                            <p>
                                <select name="Timezone">
                                    <xsl:variable name="timezones">
                                        <timezone offset="-12" name="Eniwetok, Kwajalein"/>
                                        <timezone offset="-11" name="Midway Island, Samoa"/>
                                        <timezone offset="-10" name="Hawaii"/>
                                        <timezone offset="-9" name="Alaska"/>
                                        <timezone offset="-8" name="Pacific Time (US &amp; Canada), Tijuana"/>
                                        <timezone offset="-7" name="Mountain Time (US &amp; Canada), Arizona"/>
                                        <timezone offset="-6" name="Central Time (US &amp; Canada), Mexico City"/>
                                        <timezone offset="-5" name="Eastern Time (US &amp; Canada), Bogota, Lima, Quito"/>
                                        <timezone offset="-4" name="Atlantic Time (Canada), Caracas, La Paz"/>
                                        <timezone offset="-3" name="Brassila, Buenos Aires, Georgetown, Falkland Is"/>
                                        <timezone offset="-2" name="Mid-Atlantic, Ascension Is., St. Helena"/>
                                        <timezone offset="-1" name="Azores, Cape Verde Islands"/>
                                        <timezone offset="+0" name="Casablanca, Dublin, Edinburgh, London, Lisbon, Monrovia"/>
                                        <timezone offset="+1" name="Prague, Amsterdam, Berlin, Brussels, Madrid, Paris"/>
                                        <timezone offset="+2" name="Cairo, Helsinki, Kaliningrad, South Africa"/>
                                        <timezone offset="+3" name="Baghdad, Riyadh, Moscow, Nairobi"/>
                                        <timezone offset="+4" name="Abu Dhabi, Baku, Muscat, Tbilisi"/>
                                        <timezone offset="+5" name="Ekaterinburg, Islamabad, Karachi, Tashkent"/>
                                        <timezone offset="+6" name="Almaty, Colombo, Dhaka, Novosibirsk"/>
                                        <timezone offset="+7" name="Bangkok, Hanoi, Jakarta"/>
                                        <timezone offset="+8" name="Beijing, Hong Kong, Perth, Singapore, Taipei"/>
                                        <timezone offset="+9" name="Osaka, Sapporo, Seoul, Tokyo, Yakutsk"/>
                                        <timezone offset="+10" name="Canberra, Guam, Melbourne, Sydney, Vladivostok"/>
                                        <timezone offset="+11" name="Magadan, New Caledonia, Solomon Islands"/>
                                        <timezone offset="+12" name="Auckland, Wellington, Fiji, Marshall Island"/>
                                    </xsl:variable>
                                    <xsl:for-each select="exsl:node-set($timezones)/*">
                                        <option value="{@offset}">
                                            <xsl:if test="$settings/Timezone = @offset">
                                                <xsl:attribute name="selected">selected</xsl:attribute>
                                            </xsl:if>
                                            <xsl:text>GMT </xsl:text>
                                            <xsl:value-of select="@offset"/>
                                            <xsl:text> (</xsl:text>
                                            <xsl:value-of select="@name"/>)
                                        </option>
                                    </xsl:for-each>
                                </select>
                                <xsl:text>Time zone</xsl:text>
                            </p>

                            <h4>Layout options</h4>
                            <xsl:if test="$settings/Background != 0">
                                <div id="preview">
                                    <h4>Background image</h4>
                                    <img width="204" height="152" src="img/backgrounds/bg_{$settings/Background}.jpg" alt=""/>
                                </div>
                            </xsl:if>

                            <p>
                                <select name="DefaultFilter">
                                    <xsl:variable name="filterTypes">
                                        <filter name="none" text="No players filters"/>
                                        <filter name="active" text="Active players"/>
                                        <filter name="offline" text="Active and offline players"/>
                                        <filter name="all" text="Show all players"/>
                                    </xsl:variable>
                                    <xsl:for-each select="exsl:node-set($filterTypes)/*">
                                        <option value="{@name}">
                                            <xsl:if test="$settings/DefaultFilter = @name">
                                                <xsl:attribute name="selected">selected</xsl:attribute>
                                            </xsl:if>
                                            <xsl:value-of select="@text"/>
                                        </option>
                                    </xsl:for-each>
                                </select>
                                <span>
                                    <xsl:attribute name="title">
                                        <xsl:text>determine default value of the players list filter in the Players section</xsl:text>
                                    </xsl:attribute>
                                    <xsl:text>Players list filter</xsl:text>
                                </span>
                            </p>

                            <p>
                                <select name="Skin">
                                    <xsl:variable name="skins">
                                        <skin value="0" name="blue (dark)"/>
                                        <skin value="1" name="rain (light)"/>
                                        <skin value="2" name="purple (light)"/>
                                        <skin value="3" name="green (dark)"/>
                                        <skin value="4" name="stars (dark)"/>
                                        <skin value="5" name="clouds (light)"/>
                                        <skin value="6" name="old theme (dark)"/>
                                        <skin value="7" name="fire (dark)"/>
                                        <skin value="8" name="halloween (dark)"/>
                                    </xsl:variable>
                                    <xsl:for-each select="exsl:node-set($skins)/*">
                                        <xsl:sort select="@name" order="ascending"/>
                                        <option value="{@value}">
                                            <xsl:if test="$settings/Skin = @value">
                                                <xsl:attribute name="selected">selected</xsl:attribute>
                                            </xsl:if>
                                            <xsl:value-of select="@name"/>
                                        </option>
                                    </xsl:for-each>
                                </select>
                                <span>
                                    <xsl:attribute name="title">
                                        <xsl:text>select a skin that will be used throughout the whole site</xsl:text>
                                    </xsl:attribute>
                                    <xsl:text>Skin selection</xsl:text>
                                </span>
                            </p>

                            <p>
                                <select name="Background">
                                    <xsl:variable name="backgrounds">
                                        <background value="0" name="- transparent -"/>
                                        <background value="1" name="hill castle"/>
                                        <background value="2" name="cloud castle"/>
                                        <background value="3" name="dark forest"/>
                                        <background value="4" name="daemon"/>
                                        <background value="5" name="thief"/>
                                        <background value="6" name="black dragon"/>
                                        <background value="7" name="elven city"/>
                                        <background value="8" name="wolf"/>
                                        <background value="9" name="forest castle"/>
                                        <background value="10" name="rider"/>
                                        <background value="11" name="azure shore"/>
                                        <background value="12" name="lost city"/>
                                        <background value="13" name="flowers"/>
                                        <background value="14" name="night sky"/>
                                        <background value="15" name="snow bunny"/>
                                        <background value="16" name="still water"/>
                                        <background value="17" name="moon lake"/>
                                        <background value="18" name="ghostship"/>
                                        <background value="19" name="fortress"/>
                                        <background value="20" name="lost castle"/>
                                        <background value="21" name="castle silhouette"/>
                                        <background value="22" name="fairy castle"/>
                                        <background value="23" name="fairy forest"/>
                                        <background value="24" name="dragon lady"/>
                                        <background value="25" name="azure unicorn"/>
                                        <background value="26" name="phoenix nebula"/>
                                        <background value="27" name="shadow priestess"/>
                                        <background value="28" name="marcomage"/>
                                        <background value="29" name="troll bridge"/>
                                        <background value="30" name="arcomage"/>
                                        <background value="31" name="halloween"/>
                                        <background value="32" name="valkyrie"/>
                                    </xsl:variable>
                                    <xsl:for-each select="exsl:node-set($backgrounds)/*">
                                        <xsl:sort select="@name" order="ascending"/>
                                        <option value="{@value}">
                                            <xsl:if test="$settings/Background = @value">
                                                <xsl:attribute name="selected">selected</xsl:attribute>
                                            </xsl:if>
                                            <xsl:value-of select="@name"/>
                                        </option>
                                    </xsl:for-each>
                                </select>
                                <span>
                                    <xsl:attribute name="title">
                                        <xsl:text>select a game background picture that will be shown in the Game section only</xsl:text>
                                    </xsl:attribute>
                                    <xsl:text>Game background</xsl:text>
                                </span>
                            </p>

                            <p>
                                <select name="Autorefresh">
                                    <xsl:variable name="refreshValues">
                                        <value name="0" text="off"/>
                                        <value name="10" text="10 seconds"/>
                                        <value name="30" text="30 seconds"/>
                                        <value name="60" text="1 minute"/>
                                        <value name="300" text="5 minutes"/>
                                        <value name="600" text="10 minutes"/>
                                    </xsl:variable>
                                    <xsl:for-each select="exsl:node-set($refreshValues)/*">
                                        <option value="{@name}">
                                            <xsl:if test="$settings/Autorefresh = @name">
                                                <xsl:attribute name="selected">selected</xsl:attribute>
                                            </xsl:if>
                                            <xsl:value-of select="@text"/>
                                        </option>
                                    </xsl:for-each>
                                </select>
                                <span>
                                    <xsl:attribute name="title">
                                        <xsl:text>activate automatic refresh in the Games section, which is based on the selected value</xsl:text>
                                    </xsl:attribute>
                                    <xsl:text>Auto refresh</xsl:text>
                                </span>
                            </p>

                            <p>
                                <select name="AutoAi">
                                    <xsl:variable name="autoAiValues">
                                        <value name="0" text="disabled"/>
                                        <value name="5" text="5 seconds"/>
                                        <value name="10" text="10 seconds"/>
                                        <value name="30" text="30 seconds"/>
                                        <value name="60" text="1 minute"/>
                                    </xsl:variable>
                                    <xsl:for-each select="exsl:node-set($autoAiValues)/*">
                                        <option value="{@name}">
                                            <xsl:if test="$settings/AutoAi = @name">
                                                <xsl:attribute name="selected">selected</xsl:attribute>
                                            </xsl:if>
                                            <xsl:value-of select="@text"/>
                                        </option>
                                    </xsl:for-each>
                                </select>
                                <span>
                                    <xsl:attribute name="title">
                                        <xsl:text>opponent in AI games will make his move automatically with specified time delay</xsl:text>
                                    </xsl:attribute>
                                    <xsl:text>Auto AI move</xsl:text>
                                </span>
                            </p>

                            <p>
                                <select name="Timeout">
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
                                    <xsl:for-each select="exsl:node-set($timeoutValues)/*">
                                        <option value="{@name}">
                                            <xsl:if test="$settings/Timeout = @name">
                                                <xsl:attribute name="selected">selected</xsl:attribute>
                                            </xsl:if>
                                            <xsl:value-of select="@text"/>
                                        </option>
                                    </xsl:for-each>
                                </select>
                                <span>
                                    <xsl:attribute name="title">
                                        <xsl:text>set default value for turn timeout when creating new games in the Games section</xsl:text>
                                    </xsl:attribute>
                                    <xsl:text>Turn timeout</xsl:text>
                                </span>
                            </p>

                            <p>
                                <input type="checkbox" name="Insignias">
                                    <xsl:if test="$settings/Insignias = 'yes'">
                                        <xsl:attribute name="checked">checked</xsl:attribute>
                                    </xsl:if>
                                </input>
                                <span>
                                    <xsl:attribute name="title">
                                        <xsl:text>display card keywords in form of an insignia instead of text form</xsl:text>
                                    </xsl:attribute>
                                    <xsl:text>Show keyword insignias</xsl:text>
                                </span>
                            </p>

                            <p>
                                <input type="checkbox" name="PlayButtons">
                                    <xsl:if test="$settings/PlayButtons = 'yes'">
                                        <xsl:attribute name="checked">checked</xsl:attribute>
                                    </xsl:if>
                                </input>
                                <span>
                                    <xsl:attribute name="title">
                                        <xsl:text>each card in game will have a local play button (if playable) instead of global play button</xsl:text>
                                    </xsl:attribute>
                                    <xsl:text>Play card buttons</xsl:text>
                                </span>
                            </p>

                            <p>
                                <input type="checkbox" name="Chatorder">
                                    <xsl:if test="$settings/Chatorder = 'yes'">
                                        <xsl:attribute name="checked">checked</xsl:attribute>
                                    </xsl:if>
                                </input>
                                <span>
                                    <xsl:attribute name="title">
                                        <xsl:text>reverse chat order in the chat box (Game section), by default chat messages are ordered from newest (top) to oldest (bottom)</xsl:text>
                                    </xsl:attribute>
                                    <xsl:text>Reverse chat message order</xsl:text>
                                </span>
                            </p>

                            <p>
                                <input type="checkbox" name="IntegratedChat">
                                    <xsl:if test="$settings/IntegratedChat = 'yes'">
                                        <xsl:attribute name="checked">checked</xsl:attribute>
                                    </xsl:if>
                                </input>
                                <span>
                                    <xsl:attribute name="title">
                                        <xsl:text>integrate chat into the game screen instead of using chat pop-up tool</xsl:text>
                                    </xsl:attribute>
                                    <xsl:text>Integrated chat</xsl:text>
                                </span>
                            </p>

                            <p>
                                <input type="checkbox" name="Avatargame">
                                    <xsl:if test="$settings/Avatargame = 'yes'">
                                        <xsl:attribute name="checked">checked</xsl:attribute>
                                    </xsl:if>
                                </input>
                                <span>
                                    <xsl:attribute name="title">display avatars of both players in the game</xsl:attribute>
                                    <xsl:text>Show avatar in game</xsl:text>
                                </span>
                            </p>

                            <p>
                                <input type="checkbox" name="OldCardLook">
                                    <xsl:if test="$settings/OldCardLook = 'yes'">
                                        <xsl:attribute name="checked">checked</xsl:attribute>
                                    </xsl:if>
                                </input>
                                <span>
                                    <xsl:attribute name="title">display cards with old appearance</xsl:attribute>
                                    <xsl:text>Old card appearance</xsl:text>
                                </span>
                            </p>

                            <p>
                                <input type="checkbox" name="Miniflags">
                                    <xsl:if test="$settings/Miniflags = 'yes'">
                                        <xsl:attribute name="checked">checked</xsl:attribute>
                                    </xsl:if>
                                </input>
                                <span>
                                    <xsl:attribute name="title">
                                        <xsl:text>display mini card flags (new card and revealed flags) within card picture instead of standard card flags</xsl:text>
                                    </xsl:attribute>
                                    <xsl:text>Mini card flags</xsl:text>
                                </span>
                            </p>

                            <p>
                                <input type="checkbox" name="Reports">
                                    <xsl:if test="$settings/Reports = 'yes'">
                                        <xsl:attribute name="checked">checked</xsl:attribute>
                                    </xsl:if>
                                </input>
                                <span>
                                    <xsl:attribute name="title">
                                        <xsl:text>when active, system will automaticaly send private message to inform you about a game outcome (when it finishes) or when someone accepts or rejects your challenge</xsl:text>
                                    </xsl:attribute>
                                    <xsl:text>Battle report messages</xsl:text>
                                </span>
                            </p>

                            <p>
                                <input type="checkbox" name="Forum_notification">
                                    <xsl:if test="$settings/Forum_notification = 'yes'">
                                        <xsl:attribute name="checked">checked</xsl:attribute>
                                    </xsl:if>
                                </input>
                                <span>
                                    <xsl:attribute name="title">
                                        <xsl:text>display forum notification (new posts) in the menu bar</xsl:text>
                                    </xsl:attribute>
                                    <xsl:text>Forum notification</xsl:text>
                                </span>
                            </p>

                            <p>
                                <input type="checkbox" name="Concepts_notification">
                                    <xsl:if test="$settings/Concepts_notification = 'yes'">
                                        <xsl:attribute name="checked">checked</xsl:attribute>
                                    </xsl:if>
                                </input>
                                <span>
                                    <xsl:attribute name="title">
                                        <xsl:text>display concepts notification (new concepts) in the menu bar</xsl:text>
                                    </xsl:attribute>
                                    <xsl:text>Concepts notification</xsl:text>
                                </span>
                            </p>

                            <p>
                                <input type="checkbox" name="RandomDeck">
                                    <xsl:if test="$settings/RandomDeck = 'yes'">
                                        <xsl:attribute name="checked">checked</xsl:attribute>
                                    </xsl:if>
                                </input>
                                <span>
                                    <xsl:attribute name="title">
                                        <xsl:text>allow to choose a random deck to play with when starting a game</xsl:text>
                                    </xsl:attribute>
                                    <xsl:text>Random deck selection</xsl:text>
                                </span>
                            </p>

                            <p>
                                <input type="checkbox" name="GameLimit">
                                    <xsl:if test="$settings/GameLimit = 'yes'">
                                        <xsl:attribute name="checked">checked</xsl:attribute>
                                    </xsl:if>
                                </input>
                                <span>
                                    <xsl:attribute name="title">
                                        <xsl:text>disallow simultaneous games against the same opponent</xsl:text>
                                    </xsl:attribute>
                                    <xsl:text>Disallow simultaneous games</xsl:text>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </xsl:template>


</xsl:stylesheet>
