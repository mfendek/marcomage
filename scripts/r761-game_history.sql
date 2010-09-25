CREATE TABLE `replays` (
  `GameID` int(10) unsigned NOT NULL,
  `Turn` int(5) unsigned NOT NULL DEFAULT '1',
  `Player1` char(20) COLLATE utf8_unicode_ci NOT NULL,
  `Player2` char(20) COLLATE utf8_unicode_ci NOT NULL,
  `Current` char(20) COLLATE utf8_unicode_ci NOT NULL,
  `Round` int(3) unsigned NOT NULL DEFAULT '1',
  `Winner` char(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `EndType` enum('Pending','Surrender','Abort','Abandon','Destruction','Draw','Construction','Resource','Timeout') COLLATE utf8_unicode_ci NOT NULL,
  `Data` text COLLATE utf8_unicode_ci NOT NULL,
  `GameModes` set('HiddenCards','FriendlyPlay') COLLATE utf8_unicode_ci NOT NULL,
  `Created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Final` char(3) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'no',
  PRIMARY KEY (`GameID`,`Turn`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
