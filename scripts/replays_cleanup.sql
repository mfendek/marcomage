-- --------------------
-- MArcomage sql script
-- --------------------
-- deletes replays data that belong to finished replays with 0 views and are older than 2 days
--

UPDATE `replay` SET `data` = NULL, `is_deleted` = TRUE WHERE `is_deleted` = FALSE AND `views` = 0 AND `outcome_type` != "Pending" AND `finished_at` < NOW() - INTERVAL 2 DAY;
