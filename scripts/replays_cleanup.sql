-- --------------------
-- MArcomage sql script
-- --------------------
-- deletes replays data that belong to finished replays with 0 views and are older than 2 days
--

UPDATE `replays` SET `Data` = '', `Deleted` = TRUE WHERE `Deleted` = FALSE AND `Views` = 0 AND `EndType` != "Pending" AND `Finished` < NOW() - INTERVAL 2 DAY;
