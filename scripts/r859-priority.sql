ALTER TABLE `forum_threads` CHANGE `Priority` `Priority` ENUM('normal','important','sticky') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'normal';
