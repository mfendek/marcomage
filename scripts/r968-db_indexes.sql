ALTER TABLE `concepts` ADD INDEX ( `State` );
ALTER TABLE `concepts` ADD INDEX ( `Author` );
ALTER TABLE `concepts` ADD INDEX ( `LastChange` );
ALTER TABLE `games` ADD INDEX ( `Player1` );
ALTER TABLE `games` ADD INDEX ( `Player2` );
ALTER TABLE `games` ADD INDEX ( `Current` );
ALTER TABLE `messages` ADD INDEX ( `Recipient` );
ALTER TABLE `logins` ADD INDEX ( `Last Query` );
ALTER TABLE `replays_head` ADD INDEX ( `Finished` );
