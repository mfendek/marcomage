SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

RENAME TABLE `logins` TO `login`;

ALTER TABLE `login` CHANGE `Username` `username` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
CHANGE `Password` `password` CHAR(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
CHANGE `SessionID` `session_id` INT(10) UNSIGNED NOT NULL DEFAULT '0',
CHANGE `UserType` `user_type` ENUM('user','moderator','supervisor','admin','squashed','limited','banned') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'user',
CHANGE `Registered` `registered_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
CHANGE `Last IP` `last_ip` CHAR(15) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '0.0.0.0',
CHANGE `Last Query` `last_activity_at` TIMESTAMP NOT NULL DEFAULT '1970-01-01 00:00:01',
CHANGE `Notification` `notification_at` TIMESTAMP NOT NULL DEFAULT '1970-01-01 00:00:01';
