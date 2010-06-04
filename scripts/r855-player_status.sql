ALTER TABLE `settings` CHANGE `Status` `Status` ENUM('newbie','ready','quick','dnd','none') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'newbie';
