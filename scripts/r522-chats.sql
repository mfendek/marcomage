START TRANSACTION;

-- get rid of old data --
ALTER TABLE `chats` DROP PRIMARY KEY;
ALTER TABLE `chats` DROP `Number`;

-- reposition the timestamp column
ALTER TABLE `chats` CHANGE `Timestamp` `Timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `GameID`;

-- remove duplicate rows --
ALTER TABLE `chats`  ADD `ChatID` INT(10) NOT NULL AUTO_INCREMENT FIRST,  ADD PRIMARY KEY (ChatID);
DELETE c.* from `chats` AS c INNER JOIN ( SELECT `GameID`, `Timestamp`, `Name`, MIN(`ChatID`) as `min_id` FROM `chats` GROUP BY `GameID`, `Timestamp`, `Name` HAVING COUNT(*) > 1 ) AS d ON c.`GameID` = d.`GameID` AND c.`Timestamp` = d.`Timestamp` AND c.`Name` = d.`Name` AND c.`ChatID` != d.`min_id`;
ALTER TABLE `chats` DROP `ChatID`;

-- define natural primary key --
ALTER TABLE `chats` ADD PRIMARY KEY ( `GameID`, `Timestamp`, `Name` );

COMMIT;
