UPDATE `settings` SET `Avatar` = CONCAT(`Avatar`, ".jpg");

ALTER TABLE `settings` CHANGE `Avatar` `Avatar` CHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'noavatar.jpg';
