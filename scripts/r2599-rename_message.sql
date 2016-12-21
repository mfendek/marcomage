SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

RENAME TABLE `messages` TO `message`;

ALTER TABLE `message` CHANGE `MessageID` `message_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
CHANGE `Author` `author` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
CHANGE `Recipient` `recipient` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
CHANGE `Subject` `subject` VARCHAR(30) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
CHANGE `Content` `content` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
CHANGE `AuthorDelete` `is_deleted_author` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
CHANGE `RecipientDelete` `is_deleted_recipient` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
CHANGE `Unread` `is_unread` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1',
CHANGE `GameID` `game_id` INT(10) UNSIGNED NOT NULL DEFAULT '0',
CHANGE `Created` `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
