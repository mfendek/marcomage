SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

RENAME TABLE `forum_posts` TO `forum_post`;

ALTER TABLE `forum_post` CHANGE `PostID` `post_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
CHANGE `Author` `author` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
CHANGE `Content` `content` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
CHANGE `ThreadID` `thread_id` INT(10) NOT NULL,
CHANGE `Deleted` `is_deleted` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
CHANGE `Created` `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;


RENAME TABLE `forum_threads` TO `forum_thread`;

ALTER TABLE `forum_thread` CHANGE `ThreadID` `thread_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
CHANGE `Title` `title` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
CHANGE `Author` `author` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
CHANGE `Priority` `priority` ENUM('normal','important','sticky') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'normal',
CHANGE `Locked` `is_locked` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
CHANGE `Deleted` `is_deleted` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
CHANGE `SectionID` `section_id` TINYINT(3) UNSIGNED NOT NULL,
CHANGE `Created` `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
CHANGE `PostCount` `post_count` MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0',
CHANGE `LastAuthor` `last_author` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
CHANGE `LastPost` `last_post` TIMESTAMP NOT NULL DEFAULT '1970-01-01 00:00:01',
CHANGE `CardID` `card_id` INT(10) UNSIGNED NOT NULL DEFAULT '0';


RENAME TABLE `forum_sections` TO `forum_section`;

ALTER TABLE `forum_section` CHANGE `SectionID` `section_id` TINYINT(3) UNSIGNED NOT NULL,
CHANGE `SectionName` `section_name` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
CHANGE `Description` `description` VARCHAR(80) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
CHANGE `SectionOrder` `section_order` TINYINT(3) UNSIGNED NOT NULL;
