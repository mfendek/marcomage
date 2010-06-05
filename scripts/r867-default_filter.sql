ALTER TABLE `settings` CHANGE `PlayerFilter` `DefaultFilter` ENUM('none','active','offline','all') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'none';
