-- backup DB just in case

-- set proper timezone
SET time_zone = "+00:00";

-- change timestamp and date default values to valid values
ALTER TABLE `forum_threads` CHANGE `LastPost` `LastPost` TIMESTAMP NOT NULL DEFAULT '1970-01-01 00:00:01';
ALTER TABLE `games` CHANGE `ChatNotification1` `ChatNotification1` TIMESTAMP NOT NULL DEFAULT '1970-01-01 00:00:01';
ALTER TABLE `games` CHANGE `ChatNotification2` `ChatNotification2` TIMESTAMP NOT NULL DEFAULT '1970-01-01 00:00:01';
ALTER TABLE `logins` CHANGE `Last Query` `Last Query` TIMESTAMP NOT NULL DEFAULT '1970-01-01 00:00:01';
ALTER TABLE `logins` CHANGE `Notification` `Notification` TIMESTAMP NOT NULL DEFAULT '1970-01-01 00:00:01';
ALTER TABLE `replays` CHANGE `Finished` `Finished` TIMESTAMP NOT NULL DEFAULT '1970-01-01 00:00:01';
ALTER TABLE `settings` CHANGE `Birthdate` `Birthdate` DATE NOT NULL DEFAULT '1000-01-01';

-- correct live values as well
UPDATE `forum_threads` SET `LastPost` = '1970-01-01 00:00:01' WHERE `LastPost` = '0000-00-00 00:00:00';
UPDATE `games` SET `ChatNotification1` = '1970-01-01 00:00:01' WHERE `ChatNotification1` = '0000-00-00 00:00:00';
UPDATE `games` SET `ChatNotification2` = '1970-01-01 00:00:01' WHERE `ChatNotification2` = '0000-00-00 00:00:00';
UPDATE `logins` SET `Last Query` = '1970-01-01 00:00:01' WHERE `Last Query` = '0000-00-00 00:00:00';
UPDATE `logins` SET `Notification` = '1970-01-01 00:00:01' WHERE `Notification` = '0000-00-00 00:00:00';
UPDATE `replays` SET `Finished` = '1970-01-01 00:00:01' WHERE `Finished` = '0000-00-00 00:00:00';
UPDATE `settings` SET `Birthdate` = '1000-01-01' WHERE `Birthdate` = '0000-00-00';

-- add missing defaults
ALTER TABLE `games` CHANGE `Current` `Current` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '';
ALTER TABLE `settings` CHANGE `Firstname` `Firstname` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '';
ALTER TABLE `settings` CHANGE `Surname` `Surname` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '';
ALTER TABLE `settings` CHANGE `Email` `Email` VARCHAR(30) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '';
ALTER TABLE `settings` CHANGE `Imnumber` `Imnumber` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '';
