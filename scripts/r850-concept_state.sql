ALTER TABLE `concepts` CHANGE `State` `State` ENUM('waiting','rejected','interesting','implemented') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'waiting';
