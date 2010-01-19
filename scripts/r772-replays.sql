RENAME TABLE `replays` TO `replays_data`;

CREATE TABLE `replays_head` (
  `GameID` int(10) unsigned NOT NULL,
  `Player1` char(20) COLLATE utf8_unicode_ci NOT NULL,
  `Player2` char(20) COLLATE utf8_unicode_ci NOT NULL,
  `Rounds` int(3) unsigned NOT NULL DEFAULT '1',
  `Turns` int(5) unsigned NOT NULL DEFAULT '1',
  `Winner` char(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `EndType` enum('Pending','Surrender','Abort','Abandon','Destruction','Draw','Construction','Resource','Timeout') COLLATE utf8_unicode_ci NOT NULL,
  `GameModes` set('HiddenCards','FriendlyPlay') COLLATE utf8_unicode_ci NOT NULL,
  `Started` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Finished` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`GameID`),
  KEY `Player1` (`Player1`),
  KEY `Player2` (`Player2`),
  KEY `EndType` (`EndType`),
  KEY `GameModes` (`GameModes`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `replays_head` SELECT `GameID`, `Player1`, `Player2`, MAX(`Round`) as `Rounds`, MAX(`Turn`) as `Turns`, `Winner`, `EndType`, `GameModes`, MIN(`Created`) as `Started`, MAX(`Created`) as `Finished` FROM `replays_data` GROUP BY `GameID`;

ALTER TABLE `replays_data` DROP `Player1`, DROP `Player2`, DROP `Winner`, DROP `EndType`, DROP `GameModes`, DROP `Created`, DROP `Final`;
