ALTER TABLE `games` CHANGE `State` `State` ENUM('waiting','in progress','finished','P1 over','P2 over') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'waiting';
