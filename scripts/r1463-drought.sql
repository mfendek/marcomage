UPDATE `decks` SET `Data` = REPLACE(`Data`, 'i:206;', 'i:0;') WHERE `Data` LIKE '%i:206;%';
UPDATE `decks` SET `Data` = REPLACE(`Data`, 's:3:"206";', 'i:0;') WHERE `Data` LIKE '%s:3:"206";%';
