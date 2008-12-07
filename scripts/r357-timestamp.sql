START TRANSACTION;

-- change Timestamp column's type from int(10) to timestamp
ALTER TABLE `chats` CHANGE `Timestamp` `Timestamp-old` INT( 10 ) UNSIGNED ZEROFILL NOT NULL;
ALTER TABLE `chats` ADD `Timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `Message`;
UPDATE `chats` SET `Timestamp` = FROM_UNIXTIME(`Timestamp-old`);
ALTER TABLE `chats` DROP `Timestamp-old`;

-- change Created column's type from datetime to timestamp
ALTER TABLE `forum_posts` CHANGE `Created` `Created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;

-- change Created column's type from datetime to timestamp
ALTER TABLE `forum_threads` CHANGE `Created` `Created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;

-- add a + in front of timezones with offset 0
UPDATE `settings` SET `Timezone` = '+0' WHERE `Timezone` = '0';

COMMIT;
