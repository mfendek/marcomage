-- --------------------
-- MArcomage sql script
-- --------------------
-- deletes dead users that didn't finish a single game
--

SET AUTOCOMMIT=0;

START TRANSACTION;

CREATE TEMPORARY TABLE tmp (Username CHAR(20));

INSERT INTO tmp
SELECT `Username` FROM `logins`
WHERE
`Username` IN (SELECT `Username` FROM `scores` WHERE `Wins` + `Losses` + `Draws` = 0)
AND
`Username` IN (SELECT `Username` FROM `logins` WHERE UNIX_TIMESTAMP(NOW()) - `Last Query` > 60*60*24*7*3)
;

DELETE FROM `logins` WHERE `Username` IN (SELECT `Username` FROM tmp);
DELETE FROM `scores` WHERE `Username` IN (SELECT `Username` FROM tmp);
DELETE FROM `decks` WHERE `Username` IN (SELECT `Username` FROM tmp);
DELETE FROM `chats` WHERE `Name` IN (SELECT `Username` FROM tmp);
DELETE FROM `settings` WHERE `Username` IN (SELECT `Username` FROM tmp);
DELETE FROM `games` WHERE `Player1` IN (SELECT `Username` FROM tmp);
DELETE FROM `games` WHERE `Player2` IN (SELECT `Username` FROM tmp);

COMMIT;
