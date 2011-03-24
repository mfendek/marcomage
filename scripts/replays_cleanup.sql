-- --------------------
-- MArcomage sql script
-- --------------------
-- deletes replays data that belong to finished replays with 0 views and are older than 2 days
--

SET AUTOCOMMIT=0;

START TRANSACTION;

-- copy data structure
CREATE TABLE `replays_tmp` LIKE `replays_data`;

-- move replay data that will be preserved to temporary table (replays with positive views, unfinished replays and replays that are younger than 2 days)
INSERT INTO `replays_tmp` SELECT `GameID`, `Turn`, `Current`, `Round`, `Data` FROM (SELECT `GameID` FROM `replays_head` WHERE `Views` > 0 OR `EndType` = "Pending" OR `Finished` > NOW() - INTERVAL 2 DAY) as `head` INNER JOIN `replays_data` USING (`GameID`);

-- delete old table along with data that doesn't need to be preserved
DROP TABLE `replays_data`;

-- rename temporary table
RENAME TABLE `replays_tmp` TO `replays_data`;

-- update replays_head table about recent data deletion
UPDATE `replays_head` SET `Deleted` = 1 WHERE `Deleted` = 0 AND NOT (`Views` > 0 OR `EndType` = "Pending" OR `Finished` > NOW() - INTERVAL 2 DAY);

COMMIT;
