-- --------------------
-- MArcomage sql script
-- --------------------
-- deletes replays data that belong to replays with 0 views and are older than 2 days
--

SET AUTOCOMMIT=0;

START TRANSACTION;

CREATE TABLE `replays_tmp` (
  `GameID` int(10) unsigned NOT NULL,
  `Turn` int(5) unsigned NOT NULL DEFAULT '1',
  `Current` char(20) COLLATE utf8_unicode_ci NOT NULL,
  `Round` int(3) unsigned NOT NULL DEFAULT '1',
  `Data` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`GameID`,`Turn`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `replays_tmp` SELECT `GameID`, `Turn`, `Current`, `Round`, `Data`  FROM (SELECT `GameID` FROM `replays_head` WHERE `Views` > 0 OR `EndType` = "Pending" OR (UNIX_TIMESTAMP() - UNIX_TIMESTAMP(`Finished`) < (60 * 60 * 24 * 2))) as `head` INNER JOIN `replays_data` USING (`GameID`);

DROP TABLE `replays_data`;

RENAME TABLE `replays_tmp` TO `replays_data`;

UPDATE `replays_head` SET `Deleted` = 1 WHERE `Deleted` = 0 AND NOT (`Views` > 0 OR `EndType` = "Pending" OR (UNIX_TIMESTAMP() - UNIX_TIMESTAMP(`Finished`) < (60 * 60 * 24 * 2)));

COMMIT;
