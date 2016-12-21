SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

RENAME TABLE `games` TO `game`;

ALTER TABLE `game` CHANGE `GameID` `game_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
CHANGE `Player1` `player1` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
CHANGE `Player2` `player2` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
CHANGE `State` `state` ENUM('waiting','in progress','finished','P1 over','P2 over') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'waiting',
CHANGE `Current` `current` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
CHANGE `Round` `round` SMALLINT(5) UNSIGNED NOT NULL DEFAULT '1',
CHANGE `Winner` `winner` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
CHANGE `Surrender` `surrender` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
CHANGE `EndType` `outcome_type` ENUM('Pending','Surrender','Abort','Abandon','Destruction','Draw','Construction','Resource','Timeout') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Pending',
CHANGE `Last Action` `last_action_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
CHANGE `ChatNotification1` `chat_notification1` TIMESTAMP NOT NULL DEFAULT '1970-01-01 00:00:01',
CHANGE `ChatNotification2` `chat_notification2` TIMESTAMP NOT NULL DEFAULT '1970-01-01 00:00:01',
CHANGE `Data` `data` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
CHANGE `DeckID1` `deck_id1` INT(10) UNSIGNED NOT NULL DEFAULT '0',
CHANGE `DeckID2` `deck_id2` INT(10) UNSIGNED NOT NULL DEFAULT '0',
CHANGE `Note1` `note1` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
CHANGE `Note2` `note2` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
CHANGE `GameModes` `game_modes` SET('HiddenCards','FriendlyPlay','LongMode','AIMode') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
CHANGE `Timeout` `turn_timeout` MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0',
CHANGE `AI` `ai_name` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '';


RENAME TABLE `replays` TO `replay`;

ALTER TABLE `replay` CHANGE `GameID` `game_id` INT(10) UNSIGNED NOT NULL,
CHANGE `Player1` `player1` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
CHANGE `Player2` `player2` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
CHANGE `Rounds` `rounds` SMALLINT(5) UNSIGNED NOT NULL DEFAULT '1',
CHANGE `Turns` `turns` SMALLINT(5) UNSIGNED NOT NULL DEFAULT '1',
CHANGE `Winner` `winner` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
CHANGE `EndType` `outcome_type` ENUM('Pending','Surrender','Abort','Abandon','Destruction','Draw','Construction','Resource','Timeout') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
CHANGE `GameModes` `game_modes` SET('HiddenCards','FriendlyPlay','LongMode','AIMode') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
CHANGE `AI` `ai_name` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
CHANGE `Started` `started_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
CHANGE `Finished` `finished_at` TIMESTAMP NOT NULL DEFAULT '1970-01-01 00:00:01',
CHANGE `Deleted` `is_deleted` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
CHANGE `Views` `views` SMALLINT(5) UNSIGNED NOT NULL DEFAULT '0',
CHANGE `ThreadID` `thread_id` INT(10) UNSIGNED NOT NULL DEFAULT '0',
CHANGE `Data` `data` BLOB NULL DEFAULT NULL;
