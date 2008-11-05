UPDATE `settings` SET `Birthdate` =  STR_TO_DATE(`Birthdate`, '%d-%m-%Y') WHERE `Birthdate` != "";
UPDATE `settings` SET `Birthdate` = "0000-00-00" WHERE `Birthdate` = "";
ALTER TABLE `settings` CHANGE `Birthdate` `Birthdate` DATE NOT NULL DEFAULT '0000-00-00';
