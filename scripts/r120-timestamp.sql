ALTER TABLE `chats` ADD `Timestamp` int(10) UNSIGNED ZEROFILL NOT NULL;

UPDATE `chats` set `Timestamp` = 1188604800;