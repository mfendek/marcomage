<?php

	// UPDATE SCRIPT FOR REPLAYS
	// tranforms old game data format to new one

	require_once('../config.php');
	require_once('../CDatabase.php');
	require_once('../CDeck.php');
	require_once('../CGame.php');
	require_once('../CGameAI.php');
	require_once('../CChat.php');
	require_once('../CReplay.php');

	class CReplayData
	{
		public $Hand;
		public $LastCard;
		public $LastMode;
		public $LastAction;
		public $NewCards;
		public $Revealed;
		public $Changes;
		public $DisCards;
		public $TokenNames;
		public $TokenValues;
		public $TokenChanges;
		public $Tower;
		public $Wall;
		public $Quarry;
		public $Magic;
		public $Dungeons;
		public $Bricks;
		public $Gems;
		public $Recruits;
	}

	$db = new CDatabase($server, $username, $password, $database);
	if( $db->status != 'SUCCESS' )
	{
		header("Content-Type: text/html");
		die("Unable to connect to database, aborting.");
	}

	if( false === date_default_timezone_set("Etc/UTC")
	||  false === $db->Query("SET time_zone='Etc/UTC'")
	&&  false === $db->Query("SET time_zone='+0:00'") )
	{
		header("Content-Type: text/html");
		die("Unable to configure time zone, aborting.");
	}

	$replaydb = new CReplays($db);

	echo "Updating replay data...<br /><br />";

	// get list of untransformed replays
	$list = $db->Query("SELECT `Player1`, `Player2`, `GameID` FROM `replays_head` WHERE `Deleted` = FALSE ORDER BY `GameID` ASC");
	if ($list === false) exit('Failed to retrieve replays from DB.');

	foreach( $list as $data )
	{
		$game_id = $data['GameID'];
		$player1 = $data['Player1'];
		$player2 = $data['Player2'];

		// get number of turns for current replay
		$result = $db->Query('SELECT MAX(`Turn`) as `Turns` FROM `replays_data` WHERE `GameID` = ?', array($game_id));
		if ($result === false or count($result) == 0) echo 'Failed to retrieve replay data - turns (GameID = '.$game_id.') from DB.<br />';
		else
		{

		$turns = $result[0];
		$turns = $turns['Turns'];
		$replay_data = array();

		// process replay - turn by turn
		for ($cur_turn = 1; $cur_turn <= $turns; $cur_turn++)
		{
			// get old replay data
			$result = $db->Query("SELECT `Current`, `Round`, `Data` FROM `replays_data` WHERE `GameID` = ? AND `Turn` = ?", array($game_id, $cur_turn));
			if ($result === false or count($result) == 0) echo 'Failed to retrieve replay data (GameID = '.$game_id.', Turn = '.$cur_turn.') from DB.<br />';
			else
			{

			// process old replay data
			$result_data = $result[0];
			$old_data = $result_data['Data'];
			$old_data = unserialize($old_data);
			$player1_data = $old_data[$player1];
			$player2_data = $old_data[$player2];

			// update replay data
			$attributes = array('Hand', 'LastCard', 'LastMode', 'LastAction', 'NewCards', 'Revealed', 'Changes', 'DisCards', 'TokenNames', 'TokenValues', 'TokenChanges', 'Tower', 'Wall', 'Quarry', 'Magic', 'Dungeons', 'Bricks', 'Gems', 'Recruits');
	
			$data1 = new CGamePlayerData;
			$data2 = new CGamePlayerData;

			foreach( $attributes as $attribute )
			{
				$data1->$attribute = $player1_data->$attribute;
				$data2->$attribute = $player2_data->$attribute;
			}

			$new_data[1] = $data1;
			$new_data[2] = $data2;

			$replay_turn = new CReplayTurn;
			$replay_turn->Current = $result_data['Current'];
			$replay_turn->Round = $result_data['Round'];
			$replay_turn->GameData = $new_data;

			$replay_data[$cur_turn] = $replay_turn;
			}
		}

		$last_turn = $replay_data[$turns];
		$rounds = $last_turn->Round;

		// save updated data
		$result = $db->Query("UPDATE `replays_head` SET `Rounds` = ?, `Turns` = ?, `Data` = ? WHERE `GameID` = ?", array($rounds, $turns, gzcompress(serialize($replay_data)), $game_id));
		if ($result === false) echo 'Failed to mark replay as transformed (GameID = '.$game_id.').<br />';
		}
	}

	echo "<br /><br />Done.";
?>
