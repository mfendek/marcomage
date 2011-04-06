-- create backup table
CREATE TABLE `temp_replays_head` SELECT * FROM `replays_head`;

-- add new data column which is required by the new data format
ALTER TABLE `replays_head` ADD `Data` BLOB NOT NULL;
