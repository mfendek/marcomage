ALTER TABLE `games` CHANGE `EndType` `EndType` ENUM('Pending','Surrender','Abort','Abandon','Destruction','Draw','Construction','Resource','Timeout') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Pending';