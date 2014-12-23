<?php
/*
	CStatistics - statistics module
*/
?>
<?php
	class CStatistics
	{
		private $db;
		private $Active;

		public function __construct(CDatabase &$database)
		{
			$this->db = &$database;
			$this->Active = true;
		}

		public function getDB()
		{
			return $this->db;
		}

		public function deactivate()
		{
			return $this->Active = false;
		}

		public function suggestedConcepts() // calculate top 10 concept authors for suggested concepts
		{
			$db = $this->db;

			$result = $db->query('SELECT `Author`, COUNT(`Author`) as `count` FROM `concepts` WHERE (`State` = "waiting") OR (`State` = "interesting") GROUP BY `Author` ORDER BY `count` DESC, `Author` ASC LIMIT 10');
			if ($result === false) return false;

			return $result;
		}

		public function implementedConcepts() // calculate top 10 concept authors for implemented concepts
		{
			$db = $this->db;

			$result = $db->query('SELECT `Author`, COUNT(`Author`) as `count` FROM `concepts` WHERE `State` = "implemented" GROUP BY `Author` ORDER BY `count` DESC, `Author` ASC LIMIT 10');
			if ($result === false) return false;

			return $result;
		}

		public function victoryTypes() // calculate game victory types statistics
		{
			$db = $this->db;

			// fill statistics with default values
			$statistics = array('Construction', 'Destruction', 'Resource', 'Timeout', 'Draw', 'Surrender', 'Abort', 'Abandon');
			$statistics = array_combine($statistics, array_fill(0, count($statistics), 0));

			// get number of different victory types
			$result = $db->query('SELECT `EndType`, COUNT(`EndType`) as `count` FROM `replays` WHERE `EndType` != "Pending" GROUP BY `EndType`');
			if ($result === false) return false;

			$total_games = 0;
			foreach ($result as $data)
			{
				$total_games+= $data['count'];
				$statistics[$data['EndType']] = $data['count'];
			}
			$rounded = array();

			// calculate percentage, restructure data
			foreach ($statistics as $statistic => $value)
			{
				$cur_statistic['type'] = $statistic;
				$cur_statistic['count'] = ($total_games > 0) ? round(($value / $total_games) * 100, 2) : 0;
				$rounded[] = $cur_statistic;
			}

			return $rounded;
		}

		public function gameModes() // calculate game modes statistics
		{
			$db = $this->db;

			// get number of games with various game modes
			$results = $db->query('SELECT `GameModes`, COUNT(`GameModes`) as `count` FROM `replays` WHERE `EndType` != "Pending" GROUP BY `GameModes`');
			if ($results === false) return false;

			$total_games = 0;
			$game_modes = array();
			foreach ($results as $res_data)
			{
				$total_games+= $res_data['count'];

				$modes = explode(",", $res_data['GameModes']);
				foreach ($modes as $mode)
				{
					if (isset($game_modes[$mode]))
					{
						$game_modes[$mode]+= $res_data['count'];
					}
					else
					{
						$game_modes[$mode] = $res_data['count'];
					}
				}
			}

			$statistics['hidden'] = (isset($game_modes['HiddenCards'])) ? $game_modes['HiddenCards'] : 0;
			$statistics['friendly'] = (isset($game_modes['FriendlyPlay'])) ? $game_modes['FriendlyPlay'] : 0;
			$statistics['long'] = (isset($game_modes['LongMode'])) ? $game_modes['LongMode'] : 0;

			// get number of AI mode games (exclude AI challenges)
			$result = $db->query('SELECT COUNT(`GameID`) as `ai` FROM `replays` WHERE `EndType` != "Pending" AND FIND_IN_SET("AIMode", `GameModes`) > 0 AND `AI` = ""');
			if ($result === false or count($result) == 0) return false;

			$data = $result[0];
			$statistics['ai'] = $data['ai'];

			// get number of AI victories (exclude AI challenges)
			$result = $db->query('SELECT COUNT(`GameID`) as `ai_wins` FROM `replays` WHERE `EndType` != "Pending" AND FIND_IN_SET("AIMode", `GameModes`) > 0 AND `AI` = "" AND `Winner` = ?', array(SYSTEM_NAME));
			if ($result === false or count($result) == 0) return false;

			$data = $result[0];
			$ai_wins = $data['ai_wins'];

			$ai_win_ratio = ($statistics['ai'] > 0) ? round(($ai_wins / $statistics['ai']) * 100, 2) : 0;

			// get number of AI challenge games
			$result = $db->query('SELECT COUNT(`GameID`) as `challenge` FROM `replays` WHERE `EndType` != "Pending" AND `AI` != ""');
			if ($result === false or count($result) == 0) return false;

			$data = $result[0];
			$statistics['challenge'] = $data['challenge'];

			// get number of AI challenge victories
			$result = $db->query('SELECT COUNT(`GameID`) as `challenge_wins` FROM `replays` WHERE `EndType` != "Pending" AND `AI` != "" AND `Winner` = ?', array(SYSTEM_NAME));
			if ($result === false or count($result) == 0) return false;

			$data = $result[0];
			$challenge_wins = $data['challenge_wins'];

			$challenge_win_ratio = ($statistics['challenge'] > 0) ? round(($challenge_wins / $statistics['challenge']) * 100, 2) : 0;

			// calculate percentage
			foreach ($statistics as $statistic => $value) $statistics[$statistic] = ($total_games > 0) ? round(($value / $total_games) * 100, 2) : 0;

			// calculate AI win ratio
			$statistics['ai_wins'] = $ai_win_ratio;
			$statistics['challenge_wins'] = $challenge_win_ratio;

			return $statistics;
		}

		public function versusStats($player1, $player2) // calculate versus statistics for the two specified players from player1 perspecive
		{
			$db = $this->db;
			$statistics = array();

			$params = array(
			'wins' => array($player1, $player2, $player2, $player1, $player1), 
			'losses' => array($player1, $player2, $player2, $player1, $player2), 
			'other' => array($player1, $player2, $player2, $player1, '')
			);

			// get number of games with various game modes
			$results = $db->multiquery('SELECT `EndType`, COUNT(`EndType`) as `count` FROM `replays` WHERE (`EndType` != "Pending") AND ((`Player1` = ? AND `Player2` = ?) OR (`Player1` = ? AND `Player2` = ?)) AND `Winner` = ? GROUP BY `EndType` ORDER BY `count` DESC', $params);
			if ($results === false) return false;

			foreach ($results as $result_name => $result)
			{
				$total = 0;
				if (count($result) > 0)
				{
					foreach ($result as $data)
					{
						$statistics[$result_name][] = $data;
						$total+= $data['count'];
					}
	
					// calculate percentage
					foreach ($statistics[$result_name] as $i => $data) $statistics[$result_name][$i]['ratio'] = ($total > 0) ? round(($data['count'] / $total) * 100, 1) : 0;
				}
				$statistics[$result_name.'_total'] = $total;
			}

			// average game duration (normal mode)
			$result = $db->query('SELECT ROUND(IFNULL(AVG(`Turns`), 0), 1) as `Turns`, ROUND(IFNULL(AVG(`Rounds`), 0), 1) as `Rounds` FROM `replays` WHERE (`EndType` != "Pending") AND (FIND_IN_SET("LongMode", `GameModes`) = 0) AND ((`Player1` = ? AND `Player2` = ?) OR (`Player1` = ? AND `Player2` = ?))', array($player1, $player2, $player2, $player1));
			if ($result === false or count($result) == 0) return false;

			$data = $result[0];
			$statistics['turns'] = $data['Turns'];
			$statistics['rounds'] = $data['Rounds'];

			// average game duration (long mode)
			$result = $db->query('SELECT ROUND(IFNULL(AVG(`Turns`), 0), 1) as `Turns`, ROUND(IFNULL(AVG(`Rounds`), 0), 1) as `Rounds` FROM `replays` WHERE (`EndType` != "Pending") AND (FIND_IN_SET("LongMode", `GameModes`) > 0) AND ((`Player1` = ? AND `Player2` = ?) OR (`Player1` = ? AND `Player2` = ?))', array($player1, $player2, $player2, $player1));
			if ($result === false or count($result) == 0) return false;

			$data = $result[0];
			$statistics['turns_long'] = $data['Turns'];
			$statistics['rounds_long'] = $data['Rounds'];

			return $statistics;
		}

		public function gameStats($player) // calculate overall game statistics for the specified player
		{
			$db = $this->db;
			$statistics = array();

			// wins statistics
			$result = $db->query('SELECT `EndType`, COUNT(`EndType`) as `count` FROM `replays` WHERE (`EndType` != "Pending") AND (`Player1` = ? OR `Player2` = ?) AND `Winner` = ? GROUP BY `EndType` ORDER BY `count` DESC', array($player, $player, $player));
			if ($result === false) return false;

			$wins_total = 0;
			if (count($result) > 0)
			{
				foreach ($result as $data)
				{
					$statistics['wins'][] = $data;
					$wins_total+= $data['count'];
				}

				// calculate percentage
				foreach ($statistics['wins'] as $i => $data) $statistics['wins'][$i]['ratio'] = ($wins_total > 0) ? round(($data['count'] / $wins_total) * 100, 1) : 0;
			}
			$statistics['wins_total'] = $wins_total;

			// loss statistics
			$result = $db->query('SELECT `EndType`, COUNT(`EndType`) as `count` FROM `replays` WHERE (`EndType` != "Pending") AND (`Player1` = ? OR `Player2` = ?) AND `Winner` != ? AND `Winner` != "" GROUP BY `EndType` ORDER BY `count` DESC', array($player, $player, $player));
			if ($result === false) return false;
			$losses_total = 0;

			if (count($result) > 0)
			{
				foreach ($result as $data)
				{
					$statistics['losses'][] = $data;
					$losses_total+= $data['count'];
				}

				// calculate percentage
				foreach ($statistics['losses'] as $i => $data) $statistics['losses'][$i]['ratio'] = ($losses_total > 0) ? round(($data['count'] / $losses_total) * 100, 1) : 0;
			}
			$statistics['losses_total'] = $losses_total;

			// other statistics (draws, aborts...)
			$result = $db->query('SELECT `EndType`, COUNT(`EndType`) as `count` FROM `replays` WHERE (`EndType` != "Pending") AND (`Player1` = ? OR `Player2` = ?) AND `Winner` = "" GROUP BY `EndType` ORDER BY `count` DESC', array($player, $player));
			if ($result === false) return false;

			$other_total = 0;
			if (count($result) > 0)
			{
				foreach ($result as $data)
				{
					$statistics['other'][] = $data;
					$other_total+= $data['count'];
				}

				// calculate percentage
				foreach ($statistics['other'] as $i => $data) $statistics['other'][$i]['ratio'] = ($other_total > 0) ? round(($data['count'] / $other_total) * 100, 2) : 0;
			}
			$statistics['other_total'] = $other_total;

			// average game duration (normal mode)
			$result = $db->query('SELECT ROUND(IFNULL(AVG(`Turns`), 0), 1) as `Turns`, ROUND(IFNULL(AVG(`Rounds`), 0), 1) as `Rounds` FROM `replays` WHERE (`EndType` != "Pending") AND (FIND_IN_SET("LongMode", `GameModes`) = 0) AND (`Player1` = ? OR `Player2` = ?)', array($player, $player));
			if ($result === false or count($result) == 0) return false;

			$data = $result[0];
			$statistics['turns'] = $data['Turns'];
			$statistics['rounds'] = $data['Rounds'];

			// average game duration (long mode)
			$result = $db->query('SELECT ROUND(IFNULL(AVG(`Turns`), 0), 1) as `Turns`, ROUND(IFNULL(AVG(`Rounds`), 0), 1) as `Rounds` FROM `replays` WHERE (`EndType` != "Pending") AND (FIND_IN_SET("LongMode", `GameModes`) > 0) AND (`Player1` = ? OR `Player2` = ?)', array($player, $player));
			if ($result === false or count($result) == 0) return false;

			$data = $result[0];
			$statistics['turns_long'] = $data['Turns'];
			$statistics['rounds_long'] = $data['Rounds'];

			return $statistics;
		}

		public function cards($condition, $list_size) // calculate card statistics according to specified paramaters
		{
			global $carddb;

			$db = $this->db;

			$condition = (in_array($condition, array('Played', 'PlayedTotal', 'Discarded', 'DiscardedTotal', 'Drawn', 'DrawnTotal'))) ? $condition : 'Played';

			$result = $db->query('SELECT `CardID`, `'.$condition.'` as `value` FROM `statistics` WHERE `CardID` > 0 ORDER BY `'.$condition.'` DESC, `CardID` ASC');
			if ($result === false) return false;

			$cards = $values = array();
			foreach( $result as $data )
			{
				$cards[] = $data['CardID'];
				$values[$data['CardID']] = $data['value']; // assign a statistic value to each card id
			}

			// case 1: card statistics are avaialbe
			if (count($cards) > 0) {
				$cards_data = $carddb->getData($cards);
			}
			// case 2: there are no card statistics available
			else {
				$cards_data = array();
			}

			$separated = array('Common' => array(), 'Uncommon' => array(), 'Rare' => array());
			$statistics = array(
			'Common' => array('top' => array(), 'bottom' => array()), 
			'Uncommon' => array('top' => array(), 'bottom' => array()), 
			'Rare' => array('top' => array(), 'bottom' => array()));
			$total = array('Common' => 0, 'Uncommon' => 0, 'Rare' => 0);

			// separate card list by card rarity, calculate total sum for each rarity type
			foreach ($cards_data as $data)
			{
				$separated[$data['class']][] = $data;
				$total[$data['class']]+= $values[$data['id']]; // add current's card statistics to current card rarity total
			}

			// make top and bottom lists for each rarity type
			foreach ($separated as $rarity => $list)
			{
				$statistics[$rarity]['top'] = ($list_size == 'full') ? $list : array_slice($list, 0, $list_size);
				$statistics[$rarity]['bottom'] = ($list_size == 'full') ? array() : array_slice(array_reverse($list), 0, $list_size);
			}

			// calculate usage factor for each card (relative to card's rarity)
			foreach ($statistics as $rarity => $types)
				foreach ($types as $type => $list)
					foreach ($list as $i => $cur_card)
						$statistics[$rarity][$type][$i]['factor'] = ($total[$rarity] > 0) ? round($values[$cur_card['id']] / $total[$rarity], 5) * 1000 : 0;

			return $statistics;
		}

		public function cardStatistics($card_id) // return statistics for specified card
		{
			$db = $this->db;

			$result = $db->query('SELECT `Played`, `Discarded`, `Drawn`, `PlayedTotal`, `DiscardedTotal`, `DrawnTotal` FROM `statistics` WHERE `CardID` = ?', array($card_id));
			if ($result === false) return false;
			if (count($result) == 0)
				$data = array('Played' => 0, 'Discarded' => 0, 'Drawn' => 0, 'PlayedTotal' => 0, 'DiscardedTotal' => 0, 'DrawnTotal' => 0);
			else
				$data = $result[0];

			return $data;
		}

		public function updateCardStats($card_id, $action) // update card statistics (used when card is played, drawn or discarded)
		{
			if (!$this->Active) return true; // do not update card statistics when disabled

			$db = $this->db;

			if ($action == "play") $action_q = 'Played';
			elseif ($action == "discard") $action_q = 'Discarded';
			elseif ($action == "draw") $action_q = 'Drawn';
			else return false; // invalid action

			// check if the card is already present in the database
			$result = $db->query('SELECT 1 FROM `statistics` WHERE `CardID` = ?', array($card_id));
			if ($result === false) return false;
			if (count($result) == 0) // add new record when necessary
			{
				$result = $db->query('INSERT INTO `statistics` (`CardID`) VALUES (?)', array($card_id));
				if ($result === false) return false;
			}

			// update card statistics
			$result = $db->query('UPDATE `statistics` SET `'.$action_q.'` = `'.$action_q.'` + 1, `'.$action_q.'Total` = `'.$action_q.'Total` + 1 WHERE `CardID` = ?', array($card_id));
			if ($result === false) return false;

			return true;
		}
	}
?>
