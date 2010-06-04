ALTER TABLE `settings` CHANGE `Status` `Status` CHAR(10) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'newbie';

UPDATE `settings` SET `Status` = "newbie" WHERE `Status` = "noob";

