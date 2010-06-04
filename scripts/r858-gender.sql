UPDATE `settings` SET `Gender` = "none" WHERE `Gender` = "";

ALTER TABLE `settings` CHANGE `Gender` `Gender` ENUM('none','male','female') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'none';
