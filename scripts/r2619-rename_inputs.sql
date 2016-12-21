UPDATE `forum_post` SET `content` = REPLACE(`content`, 'CurrentConcept=', 'current_concept=');
UPDATE `forum_post` SET `content` = REPLACE(`content`, 'CurrentDeck=', 'current_deck=');
UPDATE `forum_post` SET `content` = REPLACE(`content`, 'CurrentSection=', 'current_section=');
UPDATE `forum_post` SET `content` = REPLACE(`content`, 'CurrentThread=', 'current_thread=');
