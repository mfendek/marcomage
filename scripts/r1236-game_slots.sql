ALTER TABLE `scores`  ADD `GameSlots` INT(5) UNSIGNED NOT NULL DEFAULT '0';
UPDATE `scores` SET `GameSlots` = FLOOR(`Level` / 4);
