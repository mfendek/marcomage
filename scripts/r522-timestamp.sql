START TRANSACTION;

-- change 'Last Query' column's type from int(10) to timestamp
ALTER TABLE `logins` CHANGE `Last Query` `Last Query-old` INT( 10 ) UNSIGNED NOT NULL;
ALTER TABLE `logins` ADD `Last Query` TIMESTAMP NOT NULL DEFAULT 0 AFTER `Last IP`;
UPDATE `logins` SET `Last Query` = FROM_UNIXTIME(`Last Query-old`);
ALTER TABLE `logins` DROP `Last Query-old`;

-- change 'PreviousLogin' column's type from int(10) to timestamp
ALTER TABLE `logins` CHANGE `PreviousLogin` `PreviousLogin-old` INT( 10 ) UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `logins` ADD `PreviousLogin` TIMESTAMP NOT NULL DEFAULT 0 AFTER `Last Query`;
UPDATE `logins` SET `PreviousLogin` = FROM_UNIXTIME(`PreviousLogin-old`);
ALTER TABLE `logins` DROP `PreviousLogin-old`;

COMMIT;
