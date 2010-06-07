CREATE TABLE `statistics` (
  `CardID` int(10) unsigned NOT NULL,
  `Played` int(10) unsigned NOT NULL DEFAULT '0',
  `Discarded` int(10) unsigned NOT NULL DEFAULT '0',
  `PlayedTotal` int(10) unsigned NOT NULL DEFAULT '0',
  `DiscardedTotal` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`CardID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
