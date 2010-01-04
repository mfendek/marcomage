ALTER TABLE `games` DROP `Outcome`;
UPDATE `games` SET `EndState` = "Pending" WHERE `EndState` = "";
ALTER TABLE `games` CHANGE `EndState` `EndState` ENUM('Pending','Surrender','Abort','Abandon','Destruction','Draw','Construction','Resource','Timeout') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '';
