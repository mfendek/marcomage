ALTER TABLE `chats` CHANGE `Name` `Name` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;

ALTER TABLE `concepts` CHANGE `Name` `Name` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL, 
CHANGE `Class` `Class` ENUM('Common','Uncommon','Rare') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
CHANGE `Bricks` `Bricks` TINYINT UNSIGNED NOT NULL DEFAULT '0',
CHANGE `Gems` `Gems` TINYINT UNSIGNED NOT NULL DEFAULT '0',
CHANGE `Recruits` `Recruits` TINYINT UNSIGNED NOT NULL DEFAULT '0',
CHANGE `Picture` `Picture` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'blank.jpg',
CHANGE `Author` `Author` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;

ALTER TABLE `decks` CHANGE `Username` `Username` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
CHANGE `Deckname` `Deckname` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
CHANGE `Ready` `Ready` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
CHANGE `Wins` `Wins` SMALLINT UNSIGNED NOT NULL DEFAULT '0',
CHANGE `Losses` `Losses` SMALLINT UNSIGNED NOT NULL DEFAULT '0',
CHANGE `Draws` `Draws` SMALLINT UNSIGNED NOT NULL DEFAULT '0';

ALTER TABLE `forum_posts` CHANGE `Author` `Author` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
CHANGE `Deleted` `Deleted` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0';

ALTER TABLE `forum_sections` CHANGE `SectionID` `SectionID` TINYINT UNSIGNED NOT NULL,
CHANGE `SectionOrder` `SectionOrder` TINYINT UNSIGNED NOT NULL;

ALTER TABLE `forum_threads` CHANGE `Author` `Author` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
CHANGE `Locked` `Locked` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
CHANGE `Deleted` `Deleted` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
CHANGE `SectionID` `SectionID` TINYINT UNSIGNED NOT NULL,
CHANGE `PostCount` `PostCount` MEDIUMINT UNSIGNED NOT NULL DEFAULT '0',
CHANGE `LastAuthor` `LastAuthor` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '';

ALTER TABLE `games` CHANGE `Player1` `Player1` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
CHANGE `Player2` `Player2` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
CHANGE `Current` `Current` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
CHANGE `Round` `Round` SMALLINT UNSIGNED NOT NULL DEFAULT '1',
CHANGE `Winner` `Winner` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
CHANGE `Surrender` `Surrender` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
CHANGE `Timeout` `Timeout` MEDIUMINT UNSIGNED NOT NULL DEFAULT '0',
CHANGE `AI` `AI` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '';

ALTER TABLE `logins` CHANGE `Username` `Username` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;

ALTER TABLE `messages` CHANGE `Author` `Author` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
CHANGE `Recipient` `Recipient` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
CHANGE `AuthorDelete` `AuthorDelete` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
CHANGE `RecipientDelete` `RecipientDelete` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
CHANGE `Unread` `Unread` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1';

ALTER TABLE `replays` CHANGE `Player1` `Player1` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
CHANGE `Player2` `Player2` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
CHANGE `Rounds` `Rounds` SMALLINT UNSIGNED NOT NULL DEFAULT '1',
CHANGE `Turns` `Turns` SMALLINT UNSIGNED NOT NULL DEFAULT '1',
CHANGE `Winner` `Winner` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
CHANGE `AI` `AI` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
CHANGE `Deleted` `Deleted` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
CHANGE `Views` `Views` SMALLINT UNSIGNED NOT NULL DEFAULT '0';

ALTER TABLE `scores` CHANGE `Username` `Username` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
CHANGE `Level` `Level` SMALLINT UNSIGNED NOT NULL DEFAULT '0',
CHANGE `Exp` `Exp` MEDIUMINT UNSIGNED NOT NULL DEFAULT '0',
CHANGE `Gold` `Gold` MEDIUMINT UNSIGNED NOT NULL DEFAULT '0',
CHANGE `Wins` `Wins` MEDIUMINT UNSIGNED NOT NULL DEFAULT '0',
CHANGE `Losses` `Losses` MEDIUMINT UNSIGNED NOT NULL DEFAULT '0',
CHANGE `Draws` `Draws` MEDIUMINT UNSIGNED NOT NULL DEFAULT '0',
CHANGE `GameSlots` `GameSlots` SMALLINT UNSIGNED NOT NULL DEFAULT '0',
CHANGE `Assassin` `Assassin` SMALLINT UNSIGNED NOT NULL DEFAULT '0',
CHANGE `Builder` `Builder` SMALLINT UNSIGNED NOT NULL DEFAULT '0',
CHANGE `Carpenter` `Carpenter` SMALLINT UNSIGNED NOT NULL DEFAULT '0',
CHANGE `Collector` `Collector` SMALLINT UNSIGNED NOT NULL DEFAULT '0',
CHANGE `Desolator` `Desolator` SMALLINT UNSIGNED NOT NULL DEFAULT '0',
CHANGE `Dragon` `Dragon` SMALLINT UNSIGNED NOT NULL DEFAULT '0',
CHANGE `Gentle_touch` `Gentle_touch` SMALLINT UNSIGNED NOT NULL DEFAULT '0',
CHANGE `Saboteur` `Saboteur` SMALLINT UNSIGNED NOT NULL DEFAULT '0',
CHANGE `Snob` `Snob` SMALLINT UNSIGNED NOT NULL DEFAULT '0',
CHANGE `Survivor` `Survivor` SMALLINT UNSIGNED NOT NULL DEFAULT '0',
CHANGE `Titan` `Titan` SMALLINT UNSIGNED NOT NULL DEFAULT '0',
CHANGE `Quarry` `Quarry` MEDIUMINT UNSIGNED NOT NULL DEFAULT '0',
CHANGE `Magic` `Magic` MEDIUMINT UNSIGNED NOT NULL DEFAULT '0',
CHANGE `Dungeons` `Dungeons` MEDIUMINT UNSIGNED NOT NULL DEFAULT '0',
CHANGE `Rares` `Rares` MEDIUMINT UNSIGNED NOT NULL DEFAULT '0',
CHANGE `Tower` `Tower` MEDIUMINT UNSIGNED NOT NULL DEFAULT '0',
CHANGE `Wall` `Wall` MEDIUMINT UNSIGNED NOT NULL DEFAULT '0',
CHANGE `TowerDamage` `TowerDamage` MEDIUMINT UNSIGNED NOT NULL DEFAULT '0',
CHANGE `WallDamage` `WallDamage` MEDIUMINT UNSIGNED NOT NULL DEFAULT '0',
CHANGE `Challenges` `Challenges` SMALLINT UNSIGNED NOT NULL DEFAULT '0';

ALTER TABLE `settings` CHANGE `Username` `Username` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
CHANGE `Firstname` `Firstname` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
CHANGE `Surname` `Surname` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
CHANGE `Email` `Email` VARCHAR(30) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
CHANGE `Imnumber` `Imnumber` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
CHANGE `Country` `Country` VARCHAR(30) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Unknown',
CHANGE `Avatar` `Avatar` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'noavatar.jpg',
CHANGE `FriendlyFlag` `FriendlyFlag` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
CHANGE `BlindFlag` `BlindFlag` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
CHANGE `LongFlag` `LongFlag` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
CHANGE `Images` `Images` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1',
CHANGE `Insignias` `Insignias` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1',
CHANGE `CardPool` `CardPool` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
CHANGE `PlayButtons` `PlayButtons` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1',
CHANGE `Nationality` `Nationality` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
CHANGE `Chatorder` `Chatorder` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
CHANGE `IntegratedChat` `IntegratedChat` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
CHANGE `Avatargame` `Avatargame` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1',
CHANGE `Avatarlist` `Avatarlist` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1',
CHANGE `Correction` `Correction` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
CHANGE `OldCardLook` `OldCardLook` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
CHANGE `Miniflags` `Miniflags` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
CHANGE `Reports` `Reports` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
CHANGE `Forum_notification` `Forum_notification` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1',
CHANGE `Concepts_notification` `Concepts_notification` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1',
CHANGE `Skin` `Skin` TINYINT UNSIGNED NOT NULL DEFAULT '0',
CHANGE `Background` `Background` TINYINT UNSIGNED NOT NULL DEFAULT '0',
CHANGE `GamesDetails` `GamesDetails` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
CHANGE `Autorefresh` `Autorefresh` SMALLINT UNSIGNED NOT NULL DEFAULT '0',
CHANGE `Cards_per_row` `Cards_per_row` TINYINT(2) UNSIGNED NOT NULL DEFAULT '10',
CHANGE `RandomDeck` `RandomDeck` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1',
CHANGE `GameLimit` `GameLimit` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
CHANGE `Timeout` `Timeout` MEDIUMINT UNSIGNED NOT NULL DEFAULT '0';
