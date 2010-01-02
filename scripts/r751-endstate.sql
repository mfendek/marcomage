ALTER TABLE `games`  ADD `EndState` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' AFTER `Outcome`;
UPDATE `games` SET `EndState` = "Draw" WHERE `Outcome` = "Draw";
UPDATE `games` SET `EndState` = "Abort" WHERE `Outcome` = "Aborted";
