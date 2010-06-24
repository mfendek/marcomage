-- --------------------
-- MArcomage sql script
-- --------------------
-- resets latest card statistics
--

SET AUTOCOMMIT=0;

START TRANSACTION;

UPDATE `statistics` SET `Played` = 0, `Discarded` = 0, `Drawn` = 0;

COMMIT;
