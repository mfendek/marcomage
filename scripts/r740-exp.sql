ALTER TABLE `scores`  ADD `Level` INT(10) UNSIGNED NOT NULL DEFAULT '0' AFTER `Username`,  ADD `Exp` INT(10) UNSIGNED NOT NULL DEFAULT '0' AFTER `Level`;
