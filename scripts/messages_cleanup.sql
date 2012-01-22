-- --------------------
-- MArcomage sql script
-- --------------------
-- deletes system messages that are older than 3 months
--

DELETE FROM `messages` WHERE `Author` = 'MArcomage' AND `Created` < NOW() - INTERVAL 3 MONTH;
