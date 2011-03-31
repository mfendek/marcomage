-- --------------------
-- MArcomage sql script
-- --------------------
-- deletes replays data that belong to finished replays with 0 views and are older than 2 days
--

-- delete replays data
DELETE `replays_data` FROM `replays_head` INNER JOIN `replays_data` USING (`GameID`) WHERE `Views` = 0 AND `EndType` != "Pending" AND `Finished` < NOW() - INTERVAL 2 DAY;

-- update replays_head table about recent data deletion
UPDATE `replays_head` SET `Deleted` = 1 WHERE `Deleted` = 0 AND `Views` = 0 AND `EndType` != "Pending" AND `Finished` < NOW() - INTERVAL 2 DAY;
