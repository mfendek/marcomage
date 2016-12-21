SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

RENAME TABLE `concepts` TO `concept`;

ALTER TABLE `concept` CHANGE `CardID` `card_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
CHANGE `Name` `name` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
CHANGE `Rarity` `rarity` ENUM('Common','Uncommon','Rare') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
CHANGE `Bricks` `bricks` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
CHANGE `Gems` `gems` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
CHANGE `Recruits` `recruits` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
CHANGE `Effect` `effect` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
CHANGE `Keywords` `keywords` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
CHANGE `Picture` `picture` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'blank.jpg',
CHANGE `Note` `note` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
CHANGE `State` `state` ENUM('waiting','rejected','interesting','implemented') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'waiting',
CHANGE `Author` `author` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
CHANGE `LastChange` `modified_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
CHANGE `ThreadID` `thread_id` INT(10) UNSIGNED NOT NULL DEFAULT '0';
