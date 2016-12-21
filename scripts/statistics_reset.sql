-- --------------------
-- MArcomage sql script
-- --------------------
-- resets latest card statistics
--

SET AUTOCOMMIT=0;

START TRANSACTION;

UPDATE `statistic` SET `played` = 0, `discarded` = 0, `drawn` = 0;

COMMIT;
