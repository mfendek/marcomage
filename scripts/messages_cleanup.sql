-- --------------------
-- MArcomage sql script
-- --------------------
-- deletes system messages that are older than 3 months
--

DELETE FROM `message` WHERE `author` = 'MArcomage' AND `created_at` < NOW() - INTERVAL 3 MONTH;
