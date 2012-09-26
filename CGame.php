<?php
/*
	CGame - representation of a game between two players
*/
?>
<?php
	class CGames
	{
		private $db;
		
		public function __construct(CDatabase &$database)
		{
			$this->db = &$database;
		}
		
		public function GetDB()
		{
			return $this->db;
		}
		
		public function CreateGame($player1, $player2, CDeck $deck1, $game_modes)
		{
			$db = $this->db;
			
			$game_data[1] = new CGamePlayerData;
			$game_data[1]->Deck = $deck1->DeckData;
			$game_data[2] = new CGamePlayerData;
			
			$result = $db->Query('INSERT INTO `games` (`Player1`, `Player2`, `Data`, `DeckID1`, `GameModes`) VALUES (?, ?, ?, ?, ?)', array($player1, $player2, serialize($game_data), $deck1->ID(), implode(',', $game_modes)));
			if ($result === false) return false;
			
			$game = new CGame($db->LastID(), $player1, $player2, $this);
			if (!$game->LoadGame()) return false;
			
			return $game;
		}
		
		public function DeleteGame($gameid)
		{
			$db = $this->db;

			$result = $db->Query('DELETE FROM `games` WHERE `GameID` = ?', array($gameid));
			if ($result === false) return false;
			
			return true;
		}
		
		public function DeleteGames($player) // delete all games and related data for specified player
		{
			global $replaydb;
			$db = $this->db;
			
			// get list of games that are going to be deleted
			$result = $db->Query('SELECT `GameID` FROM `games` WHERE (`Player1` = ?) OR (`Player2` = ?)', array($player, $player));
			if ($result === false) return false;
			
			$games = array();
			foreach( $result as $data )
				$games[] = $data['GameID'];
			
			// delete games
			$result = $db->Query('DELETE FROM `games` WHERE (`Player1` = ?) OR (`Player2` = ?)', array($player, $player));
			if ($result === false) return false;
			
			$chatdb = new CChats($db);
			
			// delete related data
			foreach ($games as $gameid)
			{
				$res = $chatdb->DeleteChat($gameid);
				if (!$res) return false;
				$res = $replaydb->DeleteReplay($gameid);
				if (!$res) return false;
			}
			
			return true;
		}
		
		public function GetGame($gameid)
		{
			$db = $this->db;

			$result = $db->Query('SELECT `Player1`, `Player2` FROM `games` WHERE `GameID` = ?', array($gameid));
			if ($result === false or count($result) == 0) return false;
			
			$players = $result[0];
			$player1 = $players['Player1'];
			$player2 = $players['Player2'];
			
			$game = new CGame($gameid, $player1, $player2, $this);
			$result = $game->LoadGame();
			if (!$result) return false;
			
			return $game;
		}
		
		public function JoinGame($player, $game_id)
		{
			$db = $this->db;

			$result = $db->Query('UPDATE `games` SET `Player2` = ? WHERE `GameID` = ?', array($player, $game_id));
			if ($result === false) return false;
			
			return true;
		}
		
		public function CountFreeSlots1($player) // used in all cases except when accepting a challenge
		{
			global $playerdb;
			$db = $this->db;

			// outgoing = chalenges_from + hosted_games
			$outgoing = '`Player1` = ? AND `State` = "waiting"';

			// incoming challenges
			$challenges_to = '`Player2` = ? AND `State` = "waiting"';

			// active games
			$active_games = '`Player1` = ? AND (`State` != "waiting" AND `State` != "P1 over")) OR (`Player2` = ? AND (`State` != "waiting" AND `State` != "P2 over")';

			$result = $db->Query('SELECT COUNT(`GameID`) as `count` FROM `games` WHERE ('.$outgoing.') OR ('.$challenges_to.') OR ('.$active_games.')', array($player, $player, $player, $player));
			if ($result === false or count($result) == 0) return false;

			$data = $result[0];

			return max(0, MAX_GAMES + $playerdb->GetGameSlots($player) - $data['count']); // make sure the result is not negative
		}
		
		public function CountFreeSlots2($player) // used only when accepting a challenge
		{
			global $playerdb;
			$db = $this->db;

			// outgoing = chalenges_from + hosted_games
			$outgoing = '`Player1` = ? AND `State` = "waiting"';

			// active games
			$active_games = '`Player1` = ? AND (`State` != "waiting" AND `State` != "P1 over")) OR (`Player2` = ? AND (`State` != "waiting" AND `State` != "P2 over")';

			$result = $db->Query('SELECT COUNT(`GameID`) as `count` FROM `games` WHERE ('.$outgoing.') OR ('.$active_games.')', array($player, $player, $player));
			if ($result === false or count($result) == 0) return false;

			$data = $result[0];

			return max(0, MAX_GAMES + $playerdb->GetGameSlots($player) - $data['count']);
		}
		
		public function ListChallengesFrom($player)
		{
			// $player is on the left side and $Status = "waiting"
			$db = $this->db;

			$result = $db->Query('SELECT `Player2` FROM `games` WHERE `Player1` = ? AND `Player2` != "" AND `State` = "waiting"', array($player));
			if ($result === false) return false;
			
			$names = array();
			foreach( $result as $data )
				$names[] = $data['Player2'];
			
			return $names;
		}
		
		public function ListChallengesTo($player)
		{
			// $player is on the right side and $Status = "waiting"
			$db = $this->db;

			$result = $db->Query('SELECT `Player1` FROM `games` WHERE `Player2` = ? AND `State` = "waiting"', array($player));
			if ($result === false) return false;
			
			$names = array();
			foreach( $result as $data )
				$names[] = $data['Player1'];
			
			return $names;
		}
		
		public function ListFreeGames($player, $hidden = "none", $friendly = "none", $long = "ignore")
		{
			// list hosted games, where player can join
			$db = $this->db;

			$hidden_q = ($hidden != "none") ? ' AND FIND_IN_SET("HiddenCards", `GameModes`) '.(($hidden == "include") ? '>' : '=').' 0' : '';
			$friendly_q = ($friendly != "none") ? ' AND FIND_IN_SET("FriendlyPlay", `GameModes`) '.(($friendly == "include") ? '>' : '=').' 0' : '';
			$long_q = ($long != "none") ? ' AND FIND_IN_SET("LongMode", `GameModes`) '.(($long == "include") ? '>' : '=').' 0' : '';

			$result = $db->Query('SELECT `GameID`, `Player1`, `Last Action`, `GameModes` FROM `games` WHERE `Player1` != ? AND `Player2` = "" AND `State` = "waiting"'.$hidden_q.$friendly_q.$long_q.' ORDER BY `Last Action` DESC', array($player));
			if ($result === false) return false;

			return $result;
		}
		
		public function ListHostedGames($player)
		{
			// list hosted games, hosted by specific player
			$db = $this->db;

			$result = $db->Query('SELECT `GameID`, `Last Action`, `GameModes` FROM `games` WHERE `Player1` = ? AND `Player2` = "" AND `State` = "waiting" ORDER BY `Last Action` DESC', array($player));
			if ($result === false) return false;

			return $result;
		}
		
		public function ListActiveGames($player)
		{
			// $player is either on the left or right side and Status != 'waiting' or 'P? over'
			$db = $this->db;

			$result = $db->Query('SELECT `GameID` FROM `games` WHERE (`Player1` = ? AND (`State` != "waiting" AND `State` != "P1 over")) OR (`Player2` = ? AND (`State` != "waiting" AND `State` != "P2 over"))', array($player, $player));
			if ($result === false) return false;

			return $result;
		}
		
		public function ListGamesData($player)
		{
			// $player is either on the left or right side and Status != 'waiting' or 'P? over'
			$db = $this->db;

			$result = $db->Query('SELECT `GameID`, `Player1`, `Player2`, `State`, `Current`, `Round`, `Last Action`, `GameModes`, `AI` FROM `games` WHERE (`Player1` = ? AND (`State` != "waiting" AND `State` != "P1 over")) OR (`Player2` = ? AND (`State` != "waiting" AND `State` != "P2 over"))', array($player, $player));
			if ($result === false) return false;

			return $result;
		}
		
		/// return number of games where it's specified player's turn
		public function CountCurrentGames($player)
		{
			$db = $this->db;

			$result = $db->Query('SELECT COUNT(`GameID`) as `count` FROM `games` WHERE `Current` = ? AND `State` = "in progress"', array($player));
			if ($result === false or count($result) == 0) return false;

			$data = $result[0];

			return $data['count'];
		}
		
		public function NextGameList($player)
		{
			// provide list of active games with opponent names
			$db = $this->db;

			$result = $db->Query('SELECT `GameID`, (CASE WHEN `Player1` = ? THEN `Player2` ELSE `Player1` END) as `Opponent` FROM `games` WHERE (`Player1` = ? OR `Player2` = ?) AND ((`State` = "in progress" AND (`Current` = ? OR (`Surrender` != ? AND `Surrender` != ""))) OR `State` = "finished" OR (`State` = "P1 over" AND `Player2` = ?) OR (`State` = "P2 over" AND `Player1` = ?))', array($player, $player, $player, $player, $player, $player, $player));
			if ($result === false) return false;
			
			$game_data = array();
			foreach( $result as $data )
				$game_data[$data['GameID']] = $data['Opponent'];
			
			return $game_data;
		}

		// check if there is already a game between two specified players
		public function CheckGame($player1, $player2)
		{
			$db = $this->db;

			$result = $db->Query('SELECT 1 FROM `games` WHERE `State` = "in progress" AND ((`Player1` = ? AND `Player2` = ?) OR (`Player1` = ? AND `Player2` = ?)) LIMIT 1', array($player1, $player2, $player2, $player1));
			if ($result === false or count($result) == 0) return false;

			return true;
		}
	}
	
	
	class CGame
	{
		private $Games;
		private $GameID;
		private $Player1;
		private $Player2;
		private $DeckID1; // player's 1 deck slot reference ID (statistics purposes only)
		private $DeckID2; // player's 2 deck slot reference ID (statistics purposes only)
		private $Note1;
		private $Note2;
		private $HiddenCards; // hide opponent's cards (yes/no)
		private $FriendlyPlay; // allow game to effect player score (yes/no)
		private $LongMode; // long game mode (yes/no)
		private $AIMode; // ai game mode (yes/no)
		private $GameAI = false;
		private $Chat;
		public $State; // 'waiting' / 'in progress' / 'finished' / 'P1 over' / 'P2 over'
		public $Current; // name of the player whose turn it currently is
		public $Round; // incremented after each play/discard action
		public $Winner; // if defined, name of the winner
		public $Surrender; // if defined, name of the player who requested to surrender
		public $EndType; // game end type: 'Pending', 'Construction', 'Destruction', 'Resource', 'Timeout', 'Draw', 'Surrender', 'Abort', 'Abandon'
		public $LastAction; // timestamp of the most recent action
		public $ChatNotification1; // timestamp of the last chat view for Player1
		public $ChatNotification2; // timestamp of the last chat view for Player2
		public $GameData; // array (name => CGamePlayerData)
		public $AI; // AI challenge name (optional)
		
		public function __construct($gameid, $player1, $player2, CGames $Games)
		{
			$this->GameID = $gameid;
			$this->Player1 = $player1;
			$this->Player2 = $player2;
			$this->Games = &$Games;
			$this->GameData[$player1] = new CGamePlayerData;
			$this->GameData[$player2] = new CGamePlayerData;
		}
		
		public function __destruct()
		{
		}
		
		public function ID()
		{
			return $this->GameID;
		}
		
		public function Name1()
		{
			return $this->Player1;
		}
		
		public function Name2()
		{
			return $this->Player2;
		}
		
		public function DeckID1()
		{
			return $this->DeckID1;
		}
		
		public function DeckID2()
		{
			return $this->DeckID2;
		}
		
		public function Outcome()
		{
			$outcomes = array(
				'Surrender' => 'Opponent has surrendered',
				'Abort' => 'Aborted',
				'Abandon' => 'Opponent has fled the battlefield',
				'Destruction' => 'Tower destruction victory',
				'Draw' => 'Draw',
				'Construction' => 'Tower building victory',
				'Resource' => 'Resource accumulation victory',
				'Timeout' => 'Timeout victory',
				'Pending' => 'Pending'
			);
			return $outcomes[$this->EndType];
		}
		
		public function GetNote($player)
		{
			return (($this->Player1 == $player) ? $this->Note1 : $this->Note2);
		}
		
		public function SetNote($player, $new_content)
		{
			if ($this->Player1 == $player) $this->Note1 = $new_content;
			else $this->Note2 = $new_content;
		}
		
		public function ClearNote($player)
		{
			if ($this->Player1 == $player) $this->Note1 = '';
			else $this->Note2 = '';
		}
		
		public function GetGameMode($game_mode)
		{
			return $this->$game_mode;
		}
		
		public function SaveChatMessage($message, $name)
		{
			return $this->Chat->SaveChatMessage($this->GameID, $message, $name);
		}
		
		public function DeleteChat()
		{
			return $this->Chat->DeleteChat($this->GameID);
		}
		
		public function ListChatMessages($order)
		{
			return $this->Chat->ListChatMessages($this->GameID, $order);
		}
		
		public function NewMessages($player, $time)
		{
			return $this->Chat->NewMessages($this->GameID, $player, $time);
		}
		
		public function ResetChatNotification($player)
		{
			$db = $this->Games->getDB();

			$chat_notification = ($player == $this->Player1) ? 'ChatNotification1' : 'ChatNotification2';

			$result = $db->Query('UPDATE `games` SET `'.$chat_notification.'` = NOW() WHERE `GameID` = ?', array($this->GameID));
			if ($result === false) return false;

			return true;
		}
		
		public function LoadGame()
		{
			$db = $this->Games->getDB();

			$result = $db->Query('SELECT `State`, `Current`, `Round`, `Winner`, `Surrender`, `EndType`, `Last Action`, `ChatNotification1`, `ChatNotification2`, `Data`, `DeckID1`, `DeckID2`, `Note1`, `Note2`, `GameModes`, `AI` FROM `games` WHERE `GameID` = ?', array($this->GameID));
			if ($result === false or count($result) == 0) return false;
			
			$data = $result[0];
			$this->State = $data['State'];
			$this->Current = $data['Current'];
			$this->Round = $data['Round'];
			$this->Winner = $data['Winner'];
			$this->Surrender = $data['Surrender'];
			$this->EndType = $data['EndType'];
			$this->LastAction = $data['Last Action'];
			$this->ChatNotification1 = $data['ChatNotification1'];
			$this->ChatNotification2 = $data['ChatNotification2'];
			$this->DeckID1 = $data['DeckID1'];
			$this->DeckID2 = $data['DeckID2'];
			$this->Note1 = $data['Note1'];
			$this->Note2 = $data['Note2'];
			$this->AI = $data['AI'];
			$this->HiddenCards = (strpos($data['GameModes'], 'HiddenCards') !== false) ? 'yes' : 'no';
			$this->FriendlyPlay = (strpos($data['GameModes'], 'FriendlyPlay') !== false) ? 'yes' : 'no';
			$this->LongMode = (strpos($data['GameModes'], 'LongMode') !== false) ? 'yes' : 'no';
			$this->AIMode = (strpos($data['GameModes'], 'AIMode') !== false) ? 'yes' : 'no';
			$game_data = unserialize($data['Data']);
			
			// transform symbolic names to real names
			$this->GameData[$this->Player1] = $game_data[1];
			$this->GameData[$this->Player2] = $game_data[2];
			$this->Chat = new CChats($db);
			
			// initialize game AI
			$this->GameAI = ($this->AI != '') ? new CChallengeAI($this) : new CGameAI($this);
			
			return true;
		}
		
		public function SaveGame()
		{
			$db = $this->Games->getDB();

			// transform real names to symbolic names
			$game_data[1] = $this->GameData[$this->Player1];
			$game_data[2] = $this->GameData[$this->Player2];

			$result = $db->Query('UPDATE `games` SET `State` = ?, `Current` = ?, `Round` = ?, `Winner` = ?, `Surrender` = ?, `EndType` = ?, `Last Action` = ?, `Data` = ?, `DeckID1` = ?, `DeckID2` = ?, `Note1` = ?, `Note2` = ?, `AI` = ? WHERE `GameID` = ?', array($this->State, $this->Current, $this->Round, $this->Winner, $this->Surrender, $this->EndType, $this->LastAction, serialize($game_data), $this->DeckID1, $this->DeckID2, $this->Note1, $this->Note2, $this->AI, $this->GameID));
			if ($result === false) return false;

			return true;
		}
		
		public function DeleteGame()
		{
			$db = $this->Games->getDB();
			
			$db->txnBegin();
			if (!$this->Games->DeleteGame($this->GameID)) { $db->txnRollback(); return false; }
			// delete chat associated with the game
			if (!$this->DeleteChat()) { $db->txnRollback(); return false; }
			$db->txnCommit();
			
			return true;
		}
		
		public function DeleteChallenge()
		{
			global $messagedb;
			
			$db = $this->Games->getDB();
			
			$db->txnBegin();
			if (!$this->Games->DeleteGame($this->GameID)) { $db->txnRollback(); return false; }
			if (!$messagedb->CancelChallenge($this->GameID)) { $db->txnRollback(); return false; }
			$db->txnCommit();
			
			return true;
		}
		
		public function JoinGame($player)
		{
			if (!$this->Games->JoinGame($player, $this->GameID)) return false;
			
			$this->Player2 = $player;
			
			return true;
		}
		
		public function StartGame($player, CDeck $deck, $challenge_name = '')
		{
			global $game_config;
			global $challengesdb;
			
			$this->GameData[$player] = new CGamePlayerData;
			$this->GameData[$player]->Deck = $deck->DeckData;
			
			// update deck slot reference
			$this->DeckID2 = $deck->ID();
			
			// determine game mode (normal or long)
			$g_mode = ($this->LongMode == 'yes') ? 'long' : 'normal';
			
			$this->State = 'in progress';
			$this->LastAction = date('Y-m-d G:i:s');
			$this->Current = ((mt_rand(0,1) == 1) ? $this->Player1 : $this->Player2);
			
			$p1 = &$this->GameData[$this->Player1];
			$p2 = &$this->GameData[$this->Player2];
			
			$p1->LastCard[1] = $p2->LastCard[1] = 0;
			$p1->LastMode[1] = $p2->LastMode[1] = 0;
			$p1->LastAction[1] = $p2->LastAction[1] = 'play';
			$p1->NewCards = $p2->NewCards = $p1->Revealed = $p2->Revealed = null;
			$p1->DisCards[0] = $p1->DisCards[1] = $p2->DisCards[0] = $p2->DisCards[1] = null; //0 - cards discarded from my hand, 1 - discarded from opponents hand
			$p1->Changes = $p2->Changes = array ('Quarry'=> 0, 'Magic'=> 0, 'Dungeons'=> 0, 'Bricks'=> 0, 'Gems'=> 0, 'Recruits'=> 0, 'Tower'=> 0, 'Wall'=> 0);
			$p1->Tower = $p2->Tower = $game_config[$g_mode]['init_tower'];
			$p1->Wall = $p2->Wall = $game_config[$g_mode]['init_wall'];
			$p1->Quarry = $p2->Quarry = 3;
			$p1->Magic = $p2->Magic = 3;
			$p1->Dungeons = $p2->Dungeons = 3;
			$p1->Bricks = $p2->Bricks = 15;
			$p1->Gems = $p2->Gems = 5;
			$p1->Recruits = $p2->Recruits = 10;
			
			// add starting bonus to second player
			if ($this->Current == $this->Player1)
			{
				$p2->Bricks+= 1;
				$p2->Gems+= 1;
				$p2->Recruits+= 1;
			}
			else
			{
				$p1->Bricks+= 1;
				$p1->Gems+= 1;
				$p1->Recruits+= 1;
			}
			
			// initialize tokens
			$p1->TokenNames = $p1->Deck->Tokens;
			$p2->TokenNames = $p2->Deck->Tokens;
			
			$p1->TokenValues = $p1->TokenChanges = array_fill_keys(array_keys($p1->TokenNames), 0);
			$p2->TokenValues = $p2->TokenChanges = array_fill_keys(array_keys($p2->TokenNames), 0);
			
			$p1->Hand = $this->DrawHand_initial($p1->Deck);
			$p2->Hand = $this->DrawHand_initial($p2->Deck);
			
			// process AI challenge (done only if the game is an AI challenge)
			if ($challenge_name != '')
			{
				$this->AI = $challenge_name;
				
				// load AI challenge data
				$challenge = $challengesdb->GetChallenge($challenge_name);
				if ($challenge)
				{
					$p1_init = $challenge->Init['his'];
					$p2_init = $challenge->Init['mine'];
					
					foreach ($p1_init as $attr_name => $attr_value)
						$p1->$attr_name = $attr_value;
					
					foreach ($p2_init as $attr_name => $attr_value)
						$p2->$attr_name = $attr_value;
				}
			}
		}
		
		public function SurrenderGame()
		{
			// only allow surrender if the game is still on
			if ($this->State != 'in progress' OR $this->Surrender == '') return 'Action not allowed!';
			
			$this->State = 'finished';
			$this->Winner = ($this->Player1 == $this->Surrender) ? $this->Player2 : $this->Player1;
			$this->EndType = 'Surrender';
			
			return 'OK';
		}

		public function RequestSurrender($playername)
		{
			// only allow to request for surrender if the game is still on
			if ($this->State != 'in progress' OR $this->Surrender != '') return 'Action not allowed!';

			$this->Surrender = $playername;

			return 'OK';
		}

		public function CancelSurrender()
		{
			// only allow to cancel surrender request if the game is still on
			if ($this->State != 'in progress' OR $this->Surrender == '') return 'Action not allowed!';

			$this->Surrender = '';

			return 'OK';
		}

		public function AbortGame($playername)
		{
			// only allow surrender if the game is still on
			if ($this->State != 'in progress') return 'Action not allowed!';
			
			$this->State = 'finished';
			$this->Winner = '';
			$this->EndType = 'Abort';
			
			return 'OK';
		}
		
		public function FinishGame($playername)
		{
			// only allow surrender if the game is still on
			if ($this->State != 'in progress') return 'Action not allowed!';
			
			$this->State = 'finished';
			$this->Winner = ($this->Player1 == $playername) ? $this->Player1 : $this->Player2;
			$opponent = ($this->Player1 == $playername) ? $this->Player2 : $this->Player1;
			$this->EndType = 'Abandon';
			
			return 'OK';
		}
		
		public function PlayCard($playername, $cardpos, $mode, $action)
		{
			global $carddb;
			global $keyworddb;
			global $scoredb;
			global $statistics;
			global $game_config;
			
			// only allow discarding if the game is still on
			if ($this->State != 'in progress') return 'Action not allowed!';
			
			// only allow action when it's the players' turn
			if ($this->Current != $playername) return 'Action only allowed on your turn!';
			
			// anti-hack
			if (($cardpos < 1) || ($cardpos > 8)) return 'Wrong card position!';
			if (($action != 'play') && ($action != 'discard')) return 'Invalid action!';
			
			// determine game mode (normal or long)
			$g_mode = ($this->LongMode == 'yes') ? 'long' : 'normal';
			
			// game configuration
			$max_tower = $game_config[$g_mode]['max_tower'];
			$max_wall = $game_config[$g_mode]['max_wall'];
			$init_tower = $game_config[$g_mode]['init_tower'];
			$init_wall = $game_config[$g_mode]['init_wall'];
			$res_vic = $game_config[$g_mode]['res_victory'];
			$time_vic = $game_config[$g_mode]['time_victory'];
			
			// prepare basic information
			$score = $scoredb->GetScore($playername);
			$opponent = ($this->Player1 == $playername) ? $this->Player2 : $this->Player1;
			$round = $this->Round;
			$mydata = &$this->GameData[$playername];
			$hisdata = &$this->GameData[$opponent];
			$my_deck = $mydata->Deck;
			$his_deck = $hisdata->Deck;
			
			// find out what card is at that position
			$cardid = $mydata->Hand[$cardpos];
			$card = $carddb->GetCard($cardid);
			
			// verify if there are enough resources
			if ($action == 'play' AND ($mydata->Bricks < $card->Bricks || $mydata->Gems < $card->Gems || $mydata->Recruits < $card->Recruits)) return 'Insufficient resources!';
			
			// verify mode (depends on card)
			if ($action == 'play' AND ($mode < 0 OR $mode > $card->Modes OR ($mode == 0 AND $card->Modes > 0))) return 'Bad mode!';
			
			// AI challenge check (rare cards are not allowed to be played by player in this game mode)
			if ($action == 'play' AND $this->AI != '' AND $card->Class == 'Rare' AND $playername != SYSTEM_NAME) return "Rare cards can't be played in this game mode!";
			
			// process card history
			$mylastcardindex = count($mydata->LastCard);
			$hislastcardindex = count($hisdata->LastCard);
			
			// prepare supplementary information
			$mylast_card = $carddb->GetCard($mydata->LastCard[$mylastcardindex]);
			$mylast_action = $mydata->LastAction[$mylastcardindex];
			$hislast_card = $carddb->GetCard($hisdata->LastCard[$hislastcardindex]);
			$hislast_action = $hisdata->LastAction[$hislastcardindex];
			$hidden_cards = ($this->HiddenCards == 'yes');
			
			//we need to store this information, because some cards will need it to make their effect, however after effect this information is not stored
			$mynewflags = $mydata->NewCards;
			$hisnewflags = $hisdata->NewCards;
			$discarded_cards[0] = $mydata->DisCards[0];
			$discarded_cards[1] = $mydata->DisCards[1];
			
			// create a copy of interesting game attributes
			$attributes = array('Quarry', 'Magic', 'Dungeons', 'Bricks', 'Gems', 'Recruits', 'Tower', 'Wall');
			$mydata_temp = $hisdata_temp = array();
			
			foreach ($attributes as $attribute)
			{
				$mydata_temp[$attribute] = $mydata->$attribute;
				$hisdata_temp[$attribute] = $hisdata->$attribute;
			}
			
			// prepare changes made during previous round
			if ($mylast_card->IsPlayAgainCard() and $mylast_action == 'play')
			{ // case 1: changes are no longer available - fetch data from replay
				$last_round_data = $this->LastRound();

				// case 1: failed to load replay data - log warning and proceed with default changes data
				if (!$last_round_data)
				{
					error_log("Failed to load replay data game ID = ".$this->GameID." p1 = ".$this->Player1." p2 = ".$this->Player2);
					$mychanges = $hischanges = array ('Quarry'=> 0, 'Magic'=> 0, 'Dungeons'=> 0, 'Bricks'=> 0, 'Gems'=> 0, 'Recruits'=> 0, 'Tower'=> 0, 'Wall'=> 0);
				}
				// case 2: success
				else
				{
					$mychanges = $last_round_data[$playername]->Changes;
					$hischanges = $last_round_data[$opponent]->Changes;
				}
			}
			else
			{ // case 2: changes are still available
				$mychanges = $mydata->Changes;
				$hischanges = $hisdata->Changes;
			}
			
			// clear newcards flag, changes indicator and discarded cards here, if required
			if (!($mylast_card->IsPlayAgainCard() and $mylast_action == 'play'))
			{
				$mydata->NewCards = null;
				$mydata->Changes = $hisdata->Changes = array ('Quarry'=> 0, 'Magic'=> 0, 'Dungeons'=> 0, 'Bricks'=> 0, 'Gems'=> 0, 'Recruits'=> 0, 'Tower'=> 0, 'Wall'=> 0);
				$mydata->DisCards[0] = $mydata->DisCards[1] = null;
				$mydata->TokenChanges = $hisdata->TokenChanges = array_fill_keys(array_keys($mydata->TokenNames), 0);
			}
			
			// by default, opponent goes next (but this may change via card)
			$nextplayer = $opponent;
			
			// next card drawn will be decided randomly unless this changes
			$nextcard = -1;
			
			// default production factor
			$bricks_production = 1;
			$gems_production = 1;
			$recruits_production = 1;
			
			// branch here according to $action
			if ($action == 'play')
			{
				// update player score (award 'Rares' - number of rare cards played)
				if ($card->Class == 'Rare' and $this->FriendlyPlay == 'no') $score->UpdateAward('Rares');
				
				// subtract card cost
				$mydata->Bricks-= $card->Bricks;
				$mydata->Gems-= $card->Gems;
				$mydata->Recruits-= $card->Recruits;
				
				// update copy of game attributes (card cost was substracted)
				foreach ($mydata_temp as $attribute => $value)
				{
					$mydata_temp[$attribute] = $mydata->$attribute;
					$hisdata_temp[$attribute] = $hisdata->$attribute;
				}
				
				// create a copy of token counters
				$mytokens_temp = $mydata->TokenValues;
				$histokens_temp = $hisdata->TokenValues;
				
				//create a copy of both players' hands and newcards flags (for difference computations only)
				$myhand = $mydata->Hand;
				$hishand = $hisdata->Hand;
				$mynewcards = $mydata->NewCards;
				$hisnewcards = $hisdata->NewCards;
				
				// execute card action !!!
				if( eval($card->Code) === FALSE )
					error_log("Debug: ".$cardid.": ".$card->Code);

				// apply limits to game attributes
				$this->ApplyGameLimits($mydata);
				$this->ApplyGameLimits($hisdata);

				// keyword processing
				if ($card->Keywords != '')
				{
					// list all keywords in order they are to be executed
					$category_keywords = $this->CategoryKeywords();
					$effect_keywords = $this->EffectKeywords();

					$keywords = array_merge($category_keywords, $effect_keywords);
					foreach ($keywords as $keyword_name)
            if ($card->HasKeyWord($keyword_name))
            {
              $keyword = $keyworddb->GetKeyword($keyword_name);
  
              // case 1: token keyword
              if ($keyword->isTokenKeyword())
              {
                // count number of cards with matching keyword (we don't count the played card)
                $amount = $this->KeywordCount($mydata->Hand, $keyword_name) - 1;
  
                // check if player has matching token counter set
                $token_index = array_search($keyword_name, $mydata->TokenNames);
  
                if ($token_index)
                {
                  // increase token counter by basic gain + bonus gain
                  $mydata->TokenValues[$token_index]+= $keyword->Basic_gain + $amount * $keyword->Bonus_gain;
  
                  // execute token keyword effect if counter reached 100
                  if ($mydata->TokenValues[$token_index] >= 100)
                  {
                    // reset token counter
                    $mydata->TokenValues[$token_index] = 0;
  
                    // execute token keyword effect
                    if( eval($keyword->Code) === FALSE )
                      error_log("Debug: ".$keyword_name.": ".$keyword->Code);
                  }
                }
              }
              // case 2: standard keyword
              else
              {
                if( eval($keyword->Code) === FALSE )
                  error_log("Debug: ".$keyword_name.": ".$keyword->Code);
              }
            }
				}

				//process discarded cards
				$mydiscards_index = count($mydata->DisCards[0]);
				$hisdiscards_index = count($mydata->DisCards[1]);
				
				//compute and store the discarded cards
				//we don't need to take into account the position of the played card. It hasn't been proccessed yet. In other words if it was discarded we know it, because the newcards flag was set, if not then newcards flag isn't set yet.
				for ($i = 1; $i <= 8; $i++)
				{
					//this last condition makes sure that played card which discards itself from hand will not get into discarded cards
					if( ((!isset($mynewcards[$i]) and isset($mydata->NewCards[$i])) or $myhand[$i] != $mydata->Hand[$i]) and $i != $cardpos )
					{
						$mydiscards_index++;
						$mydata->DisCards[0][$mydiscards_index] = $myhand[$i];
						$statistics->UpdateCardStats($myhand[$i], 'discard'); // update card statistics (card discarded by card effect)
						// hide revealed card if it was revealed before and discarded now
						if (isset($mydata->Revealed[$i])); unset($mydata->Revealed[$i]);
					}
					
					if (((!(isset($hisnewcards[$i]))) and (isset($hisdata->NewCards[$i]))) or ($hishand[$i] != $hisdata->Hand[$i]))
					{
						$hisdiscards_index++;
						$mydata->DisCards[1][$hisdiscards_index] = $hishand[$i];
						$statistics->UpdateCardStats($hishand[$i], 'discard'); // update card statistics (card discarded by card effect)
						// hide revealed card if it was revealed before and discarded now
						if (isset($hisdata->Revealed[$i])); unset($hisdata->Revealed[$i]);
					}
					
				}
				
				// apply limits to game attributes
				$this->ApplyGameLimits($mydata);
				$this->ApplyGameLimits($hisdata);
				
				// compute changes on token counters
				foreach ($mytokens_temp as $index => $token_val)
				{
					$mydata->TokenChanges[$index] += $mydata->TokenValues[$index] - $mytokens_temp[$index];
					$hisdata->TokenChanges[$index] += $hisdata->TokenValues[$index] - $histokens_temp[$index];
				}
			}
			
			// add production at the end of turn
			$mydata->Bricks+= $bricks_production * $mydata->Quarry;
			$mydata->Gems+= $gems_production * $mydata->Magic;
			$mydata->Recruits+= $recruits_production * $mydata->Dungeons;
			
			// compute changes on game attributes
			$my_diffs = $his_diffs = array();
			$attributes = array('Quarry', 'Magic', 'Dungeons', 'Bricks', 'Gems', 'Recruits', 'Tower', 'Wall');
			foreach ($attributes as $attribute)
			{
				$my_diffs[$attribute] = $my_diff = $mydata->$attribute - $mydata_temp[$attribute];
				$mydata->Changes[$attribute]+= $my_diff;
				
				$his_diffs[$attribute] = $his_diff = $hisdata->$attribute - $hisdata_temp[$attribute];
				$hisdata->Changes[$attribute]+= $his_diff;
			}
			
			if ($this->FriendlyPlay == 'no')
			{
				// update player score (awards 'Quarry', 'Magic', 'Dungeons', 'Tower', 'Wall')
				foreach (array('Quarry', 'Magic', 'Dungeons', 'Tower', 'Wall') as $attribute)
					if ($my_diffs[$attribute] > 0) $score->UpdateAward($attribute, $my_diffs[$attribute]);
				
				// update player score (award 'TowerDamage' and 'WallDamage')
				foreach (array('Tower', 'Wall') as $attribute)
					if ($his_diffs[$attribute] < 0) $score->UpdateAward($attribute.'Damage', ($his_diffs[$attribute] * (-1)));
				
				// save player score
				$score->SaveScore();
			}
			
			// draw card at the end of turn
			if( $nextcard > 0 )
			{// value was decided by a card effect
				$mydata->Hand[$cardpos] = $nextcard;
			}
			elseif( $nextcard == 0 )
			{// drawing was disabled entirely by a card effect
			}
			elseif( $nextcard == -1 )
			{// normal drawing
				if (($action == 'play') AND ($card->IsPlayAgainCard())) $drawfunc = 'DrawCard_norare';
				elseif ($action == 'play') $drawfunc = 'DrawCard_random';
				else $drawfunc = 'DrawCard_different';
				
				$mydata->Hand[$cardpos] = $this->DrawCard($my_deck, $mydata->Hand, $cardpos, $drawfunc);
			}
			
			// store info about this current action, updating history as needed
			if ($mylast_card->IsPlayAgainCard() and $mylast_action == 'play') 
			{
				// preserve history when the previously played card was a "play again" card
				$mylastcardindex++;
			}
			else
			{
				// otherwise erase the old history and start a new one
				$mydata->LastCard = null;
				$mydata->LastMode = null;
				$mydata->LastAction = null;
				$mylastcardindex = 1;
			}
			
			// record the current action in history
			$mydata->LastCard[$mylastcardindex] = $cardid;
			$mydata->LastMode[$mylastcardindex] = $mode;
			$mydata->LastAction[$mylastcardindex] = $action;
			$mydata->NewCards[$cardpos] = 1; //TODO: this shouldn't apply everytime
			if (isset($mydata->Revealed[$cardpos])); unset($mydata->Revealed[$cardpos]);
			
			// check victory conditions (in this predetermined order)
			if(     $mydata->Tower > 0 and $hisdata->Tower <= 0 )
			{	// tower destruction victory - player
				$this->Winner = $playername;
				$this->EndType = 'Destruction';
				$this->State = 'finished';
			}
			elseif( $mydata->Tower <= 0 and $hisdata->Tower > 0 )
			{	// tower destruction victory - opponent
				$this->Winner = $opponent;
				$this->EndType = 'Destruction';
				$this->State = 'finished';
			}
			elseif( $mydata->Tower <= 0 and $hisdata->Tower <= 0 )
			{	// tower destruction victory - draw
				$this->Winner = '';
				$this->EndType = 'Draw';
				$this->State = 'finished';
			}
			elseif( $mydata->Tower >= $max_tower and $hisdata->Tower < $max_tower )
			{	// tower building victory - player
				$this->Winner = $playername;
				$this->EndType = 'Construction';
				$this->State = 'finished';
			}
			elseif( $mydata->Tower < $max_tower and $hisdata->Tower >= $max_tower )
			{	// tower building victory - opponent
				$this->Winner = $opponent;
				$this->EndType = 'Construction';
				$this->State = 'finished';
			}
			elseif( $mydata->Tower >= $max_tower and $hisdata->Tower >= $max_tower )
			{	// tower building victory - draw
				$this->Winner = '';
				$this->EndType = 'Draw';
				$this->State = 'finished';
			}
			elseif( ($mydata->Bricks + $mydata->Gems + $mydata->Recruits) >= $res_vic and !(($hisdata->Bricks + $hisdata->Gems + $hisdata->Recruits) >= $res_vic) )
			{	// resource accumulation victory - player
				$this->Winner = $playername;
				$this->EndType = 'Resource';
				$this->State = 'finished';
			}
			elseif( ($hisdata->Bricks + $hisdata->Gems + $hisdata->Recruits) >= $res_vic and !(($mydata->Bricks + $mydata->Gems + $mydata->Recruits) >= $res_vic) )
			{	// resource accumulation victory - opponent
				$this->Winner = $opponent;
				$this->EndType = 'Resource';
				$this->State = 'finished';
			}
			elseif( ($mydata->Bricks + $mydata->Gems + $mydata->Recruits) >= $res_vic and ($hisdata->Bricks + $hisdata->Gems + $hisdata->Recruits) >= $res_vic )
			{	// resource accumulation victory - draw
				$this->Winner = '';
				$this->EndType = 'Draw';
				$this->State = 'finished';
			}
			elseif( $this->Round >= $time_vic )
			{	// timeout victory
				$this->EndType = 'Timeout';
				$this->State = 'finished';
				
				// compare towers
				if    ( $mydata->Tower > $hisdata->Tower ) $this->Winner = $playername;
				elseif( $mydata->Tower < $hisdata->Tower ) $this->Winner = $opponent;
				// compare walls
				elseif( $mydata->Wall > $hisdata->Wall ) $this->Winner = $playername;
				elseif( $mydata->Wall < $hisdata->Wall ) $this->Winner = $opponent;
				// compare facilities
				elseif( $mydata->Quarry + $mydata->Magic + $mydata->Dungeons > $hisdata->Quarry + $hisdata->Magic + $hisdata->Dungeons ) $this->Winner = $playername;
				elseif( $mydata->Quarry + $mydata->Magic + $mydata->Dungeons < $hisdata->Quarry + $hisdata->Magic + $hisdata->Dungeons ) $this->Winner = $opponent;
				// compare resources
				elseif( $mydata->Bricks + $mydata->Gems + $mydata->Recruits > $hisdata->Bricks + $hisdata->Gems + $hisdata->Recruits ) $this->Winner = $playername;
				elseif( $mydata->Bricks + $mydata->Gems + $mydata->Recruits < $hisdata->Bricks + $hisdata->Gems + $hisdata->Recruits ) $this->Winner = $opponent;
				// else draw
				else
				{
					$this->Winner = '';
					$this->EndType = 'Draw';
				}
			}
			
			$this->Current = $nextplayer;
			$this->LastAction = date('Y-m-d G:i:s');
			if( $nextplayer != $playername )
				$this->Round++;
			
			// update card statistics (card was played or discarded by standard discard action)
			$statistics->UpdateCardStats($cardid, $action);
			
			return 'OK';
		}

		///
		/// Simulates impact on the game if specified card would be played (doesn't effect game or statistics)
		/// Provides results in form of an array containing all game attributes and their changes
		/// @param string $playername player name
		/// @param int $cardpos position of the played card
		/// @param int $mode mode of the played card
		/// @return array game attributes and their changes
		public function CalculatePreview($playername, $cardpos, $mode)
		{
			global $carddb;
			global $keyworddb;
			global $statistics;
			global $game_config;
			
			// only allow discarding if the game is still on
			if ($this->State != 'in progress') return 'Action not allowed!';
			
			// only allow action when it's the players' turn
			if ($this->Current != $playername) return 'Action only allowed on your turn!';
			
			// anti-hack
			if (($cardpos < 1) || ($cardpos > 8)) return 'Wrong card position!';
			
			// disable statistics
			$statistics->Deactivate();
			
			// determine game mode (normal or long)
			$g_mode = ($this->LongMode == 'yes') ? 'long' : 'normal';
			
			// game configuration
			$max_tower = $game_config[$g_mode]['max_tower'];
			$max_wall = $game_config[$g_mode]['max_wall'];
			$init_tower = $game_config[$g_mode]['init_tower'];
			$init_wall = $game_config[$g_mode]['init_wall'];
			$res_vic = $game_config[$g_mode]['res_victory'];
			$time_vic = $game_config[$g_mode]['time_victory'];
			
			// prepare basic information
			$opponent = ($this->Player1 == $playername) ? $this->Player2 : $this->Player1;
			$round = $this->Round;
			$mydata = $this->GameData[$playername];
			$hisdata = $this->GameData[$opponent];
			$my_deck = $mydata->Deck;
			$his_deck = $hisdata->Deck;
			
			// find out what card is at that position
			$cardid = $mydata->Hand[$cardpos];
			$card = $carddb->GetCard($cardid);
			
			// verify if there are enough resources
			if (($mydata->Bricks < $card->Bricks) || ($mydata->Gems < $card->Gems) || ($mydata->Recruits < $card->Recruits)) return 'Insufficient resources!';
			
			// verify mode (depends on card)
			if (($mode < 0) OR ($mode > $card->Modes) OR ($mode == 0 AND $card->Modes > 0)) return 'Bad mode!';
			
			// AI challenge check (rare cards are not allowed to be played by player in this game mode)
			if ($this->AI != '' AND $card->Class == 'Rare' AND $playername != SYSTEM_NAME) return "Rare cards can't be played in this game mode!";
			
			// process card history
			$mylastcardindex = count($mydata->LastCard);
			$hislastcardindex = count($hisdata->LastCard);
			
			// prepare supplementary information
			$mylast_card = $carddb->GetCard($mydata->LastCard[$mylastcardindex]);
			$mylast_action = $mydata->LastAction[$mylastcardindex];
			$hislast_card = $carddb->GetCard($hisdata->LastCard[$hislastcardindex]);
			$hislast_action = $hisdata->LastAction[$hislastcardindex];
			$hidden_cards = ($this->HiddenCards == 'yes');
			
			//we need to store this information, because some cards will need it to make their effect, however after effect this information is not stored
			$mynewflags = $mydata->NewCards;
			$hisnewflags = $hisdata->NewCards;
			$discarded_cards[0] = $mydata->DisCards[0];
			$discarded_cards[1] = $mydata->DisCards[1];
			
			// create a copy of interesting game attributes
			$attributes = array('Quarry', 'Magic', 'Dungeons', 'Bricks', 'Gems', 'Recruits', 'Tower', 'Wall');
			$mydata_temp = $hisdata_temp = array();
			
			foreach ($attributes as $attribute)
			{
				$mydata_temp[$attribute] = $mydata->$attribute;
				$hisdata_temp[$attribute] = $hisdata->$attribute;
			}
			
			// prepare changes made during previous round
			if ($mylast_card->IsPlayAgainCard() and $mylast_action == 'play')
			{ // case 1: changes are no longer available - fetch data from replay
				$last_round_data = $this->LastRound();

				// case 1: failed to load replay data - log warning and proceed with default changes data
				if (!$last_round_data)
				{
					error_log("Failed to load replay data game ID = ".$this->GameID." p1 = ".$this->Player1." p2 = ".$this->Player2);
					$mychanges = $hischanges = array ('Quarry'=> 0, 'Magic'=> 0, 'Dungeons'=> 0, 'Bricks'=> 0, 'Gems'=> 0, 'Recruits'=> 0, 'Tower'=> 0, 'Wall'=> 0);
				}
				// case 2: success
				else
				{
					$mychanges = $last_round_data[$playername]->Changes;
					$hischanges = $last_round_data[$opponent]->Changes;
				}
			}
			else
			{ // case 2: changes are still available
				$mychanges = $mydata->Changes;
				$hischanges = $hisdata->Changes;
			}
			
			// clear newcards flag, changes indicator and discarded cards here, if required
			if (!($mylast_card->IsPlayAgainCard() and $mylast_action == 'play'))
			{
				$mydata->NewCards = null;
				$mydata->Changes = $hisdata->Changes = array ('Quarry'=> 0, 'Magic'=> 0, 'Dungeons'=> 0, 'Bricks'=> 0, 'Gems'=> 0, 'Recruits'=> 0, 'Tower'=> 0, 'Wall'=> 0);
				$mydata->DisCards[0] = $mydata->DisCards[1] = null;
				$mydata->TokenChanges = $hisdata->TokenChanges = array_fill_keys(array_keys($mydata->TokenNames), 0);
			}
			
			// by default, opponent goes next (but this may change via card)
			$nextplayer = $opponent;
			
			// next card drawn will be decided randomly unless this changes
			$nextcard = -1;
			
			// default production factor
			$bricks_production = 1;
			$gems_production = 1;
			$recruits_production = 1;
			
			$mydata->Bricks-= $card->Bricks;
			$mydata->Gems-= $card->Gems;
			$mydata->Recruits-= $card->Recruits;
			
			// update copy of game attributes (card cost was substracted)
			foreach ($mydata_temp as $attribute => $value)
			{
				$mydata_temp[$attribute] = $mydata->$attribute;
				$hisdata_temp[$attribute] = $hisdata->$attribute;
			}
			
			// create a copy of token counters
			$mytokens_temp = $mydata->TokenValues;
			$histokens_temp = $hisdata->TokenValues;
			
			//create a copy of both players' hands and newcards flags (for difference computations only)
			$myhand = $mydata->Hand;
			$hishand = $hisdata->Hand;
			$mynewcards = $mydata->NewCards;
			$hisnewcards = $hisdata->NewCards;
			
			// execute card action !!!
			if( eval($card->Code) === FALSE )
				error_log("Debug: ".$cardid.": ".$card->Code);

			// apply limits to game attributes
			$this->ApplyGameLimits($mydata);
			$this->ApplyGameLimits($hisdata);

			// keyword processing
			if ($card->Keywords != '')
			{
				// list all keywords in order they are to be executed
				$category_keywords = $this->CategoryKeywords();
				$effect_keywords = $this->EffectKeywords();

				$keywords = array_merge($category_keywords, $effect_keywords);
				foreach ($keywords as $keyword_name)
					if ($card->HasKeyWord($keyword_name))
					{
						$keyword = $keyworddb->GetKeyword($keyword_name);

						// case 1: token keyword
						if ($keyword->isTokenKeyword())
						{
              // count number of cards with matching keyword (we don't count the played card)
              $amount = $this->KeywordCount($mydata->Hand, $keyword_name) - 1;

              // check if player has matching token counter set
              $token_index = array_search($keyword_name, $mydata->TokenNames);

              if ($token_index)
              {
                // increase token counter by basic gain + bonus gain
                $mydata->TokenValues[$token_index]+= $keyword->Basic_gain + $amount * $keyword->Bonus_gain;

                // execute token keyword effect if counter reached 100
                if ($mydata->TokenValues[$token_index] >= 100)
                {
                  // reset token counter
                  $mydata->TokenValues[$token_index] = 0;

                  // execute token keyword effect
                  if( eval($keyword->Code) === FALSE )
                    error_log("Debug: ".$keyword_name.": ".$keyword->Code);
                }
              }
						}
						// case 2: standard keyword
						else
						{
              if( eval($keyword->Code) === FALSE )
                error_log("Debug: ".$keyword_name.": ".$keyword->Code);
            }
					}
			}
			
			// apply limits to game attributes
			$this->ApplyGameLimits($mydata);
			$this->ApplyGameLimits($hisdata);
			
			// compute changes on token counters
			foreach ($mytokens_temp as $index => $token_val)
			{
				$mydata->TokenChanges[$index] += $mydata->TokenValues[$index] - $mytokens_temp[$index];
				$hisdata->TokenChanges[$index] += $hisdata->TokenValues[$index] - $histokens_temp[$index];
			}
			
			// add production at the end of turn
			$mydata->Bricks+= $bricks_production * $mydata->Quarry;
			$mydata->Gems+= $gems_production * $mydata->Magic;
			$mydata->Recruits+= $recruits_production * $mydata->Dungeons;
			
			// compute changes on game attributes
			$attributes = array('Quarry', 'Magic', 'Dungeons', 'Bricks', 'Gems', 'Recruits', 'Tower', 'Wall');
			foreach ($attributes as $attribute)
			{
				$mydata->Changes[$attribute]+= $mydata->$attribute - $mydata_temp[$attribute];
				$hisdata->Changes[$attribute]+= $hisdata->$attribute - $hisdata_temp[$attribute];
			}
			
			// draw card by card effect)
			if ($nextcard > 0)
				$mydata->Hand[$cardpos] = $nextcard;
			
			$result = array();

			// card data
			$result['card']['name'] = $card->Name;
			$result['card']['mode'] = $mode;
			$result['card']['position'] = $cardpos;

			// player data
			$result['player']['name'] = $playername;

			// calculate changes in hand
			$hand_changes = array();
			for ($i = 1; $i <= 8; $i++)
				if ($myhand[$i] != $mydata->Hand[$i]) $hand_changes[$i] = $mydata->Hand[$i];

			$result['player']['hand_changes'] = $hand_changes;

			// game attributes
			$my_attr = array();
			foreach ($attributes as $attribute) $my_attr[$attribute] = $mydata->$attribute;
			$result['player']['attributes'] = $my_attr;
			$result['player']['changes'] = $mydata->Changes;

			// tokens
			$my_tokens = $my_tokens_changes = array();
			foreach ($mytokens_temp as $index => $token_val)
				if ($mydata->TokenNames[$index] != 'none')
				{
					$token_name = $mydata->TokenNames[$index];
					$my_tokens[$token_name] = $mydata->TokenValues[$index];
					$my_tokens_changes[$token_name] = $mydata->TokenChanges[$index];
				}

			$result['player']['tokens'] = $my_tokens;
			$result['player']['tokens_changes'] = $my_tokens_changes;

			// opponent data
			$result['opponent']['name'] = $opponent;

			// calculate changes in hand
			$hand_changes = array();
			for ($i = 1; $i <= 8; $i++)
				if ($hishand[$i] != $hisdata->Hand[$i]) $hand_changes[$i] = $hisdata->Hand[$i];

			$result['opponent']['hand_changes'] = $hand_changes;

			// game attributes
			$his_attr = array();
			foreach ($attributes as $attribute) $his_attr[$attribute] = $hisdata->$attribute;
			$result['opponent']['attributes'] = $his_attr;
			$result['opponent']['changes'] = $hisdata->Changes;

			// tokens
			$his_tokens = $his_tokens_changes = array();
			foreach ($histokens_temp as $index => $token_val)
				if ($hisdata->TokenNames[$index] != 'none')
				{
					$token_name = $hisdata->TokenNames[$index];
					$his_tokens[$token_name] = $hisdata->TokenValues[$index];
					$his_tokens_changes[$token_name] = $hisdata->TokenChanges[$index];
				}

			$result['opponent']['tokens'] = $his_tokens;
			$result['opponent']['tokens_changes'] = $his_tokens_changes;

			return $result;
		}

		///
		/// Format game attributes and their changes into a text message
		/// @param array $game_attributes game attributes and their changes
		/// @return string information message
		public function FormatPreview(array $game_attributes)
		{
			$card_name = $game_attributes['card']['name'];
			$card_mode = $game_attributes['card']['mode'];

			$my_name = $game_attributes['player']['name'];
			$my_attr = $game_attributes['player']['attributes'];
			$my_changes = $game_attributes['player']['changes'];
			$my_tokens = $game_attributes['player']['tokens'];
			$my_tokens_changes = $game_attributes['player']['tokens_changes'];

			$his_name = $game_attributes['opponent']['name'];
			$his_attr = $game_attributes['opponent']['attributes'];
			$his_changes = $game_attributes['opponent']['changes'];
			$his_tokens = $game_attributes['opponent']['tokens'];
			$his_tokens_changes = $game_attributes['opponent']['tokens_changes'];

			// create result text message
			$message = array();

			// card name and card mode
			$message[] = $card_name.(($card_mode > 0) ? ' (mode '.$card_mode.')' : '');

			// player data
			$message[] = "\n".$my_name."\n";

			$my_part = $his_part = array();
			// game attributes
			foreach ($my_attr as $attr_name => $attr_value)
				if ($my_changes[$attr_name] != 0)
					$my_part[] = $attr_name.': '.$attr_value.' ('.(($my_changes[$attr_name] > 0) ? '+' : '').$my_changes[$attr_name].')';

			// tokens
			foreach ($my_tokens as $token_name => $token_value)
				if ($my_tokens_changes[$token_name] != 0)
					$my_part[] = $token_name.': '.$token_value.' ('.(($my_tokens_changes[$token_name] > 0) ? '+' : '').$my_tokens_changes[$token_name].')';

			if (count($my_part) == 0) $my_part[] = 'no changes';
			$message = array_merge($message, $my_part);

			// opponent data
			$message[] = "\n".$his_name."\n";

			// game attributes
			foreach ($his_attr as $attr_name => $attr_value)
				if ($his_changes[$attr_name] != 0)
					$his_part[] = $attr_name.': '.$attr_value.' ('.(($his_changes[$attr_name] > 0) ? '+' : '').$his_changes[$attr_name].')';

			// tokens
			foreach ($his_tokens as $token_name => $token_value)
				if ($his_tokens_changes[$token_name] != 0)
					$his_part[] = $token_name.': '.$token_value.' ('.(($his_tokens_changes[$token_name] > 0) ? '+' : '').$his_tokens_changes[$token_name].')';

			if (count($his_part) == 0) $his_part[] = 'no changes';
			$message = array_merge($message, $his_part);

			return implode("\n", $message);
		}
		
		private function KeywordCount(array $hand, $keyword)
		{
			global $carddb;
			
			$count = 0;
			
			foreach ($hand as $cardid)
				if ($carddb->GetCard($cardid)->HasKeyword($keyword))
					$count++;
			
			return $count;
		}
		
		private function KeywordValue($keywords, $target_keyword)
		{
			$result = preg_match('/'.$target_keyword.' \((\d+)\)/', $keywords, $matches);
			if ($result == 0) return 0;
			
			return (int)$matches[1];
		}
		
		private function CountDistinctKeywords(array $hand)
		{
			global $carddb;
			
			$first = true;
			$keywords_list = "";
			
			foreach ($hand as $cardid)
			{
				$keyword = $carddb->GetCard($cardid)->Keywords;
				if ($keyword != "") // ignore cards with no keywords
					if ($first)
					{
						$keywords_list = $keyword;
						$first = false;
					}
					else $keywords_list.= ",".$keyword;
			}
			
			if ($keywords_list == "") return 0; // no keywords in hand
			
			$words = explode(",", $keywords_list); // split individual keywords
			foreach($words as $word)
			{
				$word = preg_split("/ \(/", $word, 0); // remove parameter if present
				$word = $word[0];
				$keywords[$word] = $word; // removes duplicates
			}
			
			return count($keywords);
		}
		
		// returns one card at type-random from the specified source with the specified draw function
		private function DrawCard($source, array $hand, $card_pos, $draw_function)
		{
			global $statistics;

			while (1)
			{
				if (!isset($card_pos) or !is_numeric($card_pos) or ($card_pos < 1) or ($card_pos > 8))
				{
					error_log('Debug: invalid card position: '.$card_pos.'');
					$cur_card = 0;
				}
				else
					$cur_card = $hand[$card_pos];
				
				$nextcard = $this->$draw_function($source, $cur_card);
				
				// count the number of occurences of the same card on other slots
				$match = 0;
				for ($i = 1; $i <= 8; $i++)
					if (($hand[$i] == $nextcard) and ($card_pos != $i))
						$match++; //do not count the card already played
				
				if (mt_rand(1, pow(2, $match)) == 1)
				{
					$statistics->UpdateCardStats($nextcard, 'draw');
					return $nextcard; // chance to retain the card decreases exponentially as the number of matches increases
				}
			}
			
		}
		
		// returns new hand from the specified source with the specified draw function
		private function DrawHand($source, $draw_function)
		{
			$hand = array(1=> 0, 0, 0, 0, 0, 0, 0, 0);
			//card position is in this case irrelevant - send current position (it contains empty slot anyway)
			for ($i = 1; $i <= 8; $i++) $hand[$i] = $this->DrawCard($source, $hand, $i, $draw_function);
 			return $hand;
 		}
		
		// returns one card at type-random from the specified deck
		private function DrawCard_random(CDeckData $deck)
		{
			$i = mt_rand(1, 100);
			if     ($i <= 65) return $deck->Common[mt_rand(1, 15)]; // common
			elseif ($i <= 65 + 29) return $deck->Uncommon[mt_rand(1, 15)]; // uncommon
			elseif ($i <= 65 + 29 + 6) return $deck->Rare[mt_rand(1, 15)]; // rare
		}
		
		// returns one card at type-random from the specified deck, different from those on your hand
		private function DrawCard_different(CDeckData $deck, $cardid)
		{
			do
				$nextcard = $this->DrawCard_random($deck);
			while( $nextcard == $cardid );

			return $nextcard;
		}
		
		// returns one card at type-random from the specified deck - no rare
		private function DrawCard_norare(CDeckData $deck)
		{
			$i = mt_rand(1, 94);
			if ($i <= 65) return $deck->Common[mt_rand(1, 15)]; // common
			else return $deck->Uncommon[mt_rand(1, 15)]; // uncommon
		}
		
		// returns one card at random from the specified list of card ids
		private function DrawCard_list(array $list)
		{
			if (count($list) == 0) return 0; // "empty slot" card
			return $list[array_mt_rand($list)];
		}
		
		// returns a new hand consisting of type-random cards chosen from the specified deck
		private function DrawHand_random(CDeckData $deck)
		{
			return $this->DrawHand($deck, 'DrawCard_random');
		}
		
		// returns a new hand consisting of type-random cards chosen from the specified deck (excluding rare cards)
		private function DrawHand_norare(CDeckData $deck)
		{
			return $this->DrawHand($deck, 'DrawCard_norare');
		}
		
		// returns initial hand which always consist of 6 common and 2 uncommon cards
		private function DrawHand_initial(CDeckData $deck)
		{
			// initialize empty hand
			$hand = array(1=> 0, 0, 0, 0, 0, 0, 0, 0);

			// draw 6 common cards
			for ($i = 1; $i <= 6; $i++) $hand[$i] = $this->DrawCard($deck->Common, $hand, $i, 'DrawCard_list');

			// draw 2 uncommon cards
			for ($i = 7; $i <= 8; $i++) $hand[$i] = $this->DrawCard($deck->Uncommon, $hand, $i, 'DrawCard_list');

			// shuffle card positions
			$keys = array_keys($hand);
			shuffle($hand);
			$hand = array_combine($keys, $hand);

			return $hand;
		}

		// returns a new hand consisting of random cards from the specified list of card ids
		private function DrawHand_list(array $list)
		{
			return $this->DrawHand($list, 'DrawCard_list');
		}
		
		// performs an attack - first reducing wall, then tower
		// may lower both values below 0
		private function Attack($atk, &$tower, &$wall)
		{
			$damage = $atk;
			
			// first, try to stop the attack with the wall
			if( $wall > 0 )
			{
				$damage-= $wall;
				$wall-= $atk;
				if( $wall < 0 ) $wall = 0;
			}
			
			// rest of the damage hits the tower
			if( $damage > 0 )
				$tower-= $damage;
		}
		
		private function ApplyGameLimits(CGamePlayerData &$data)
		{
			global $game_config;
			
			// determine game mode (normal or long)
			$g_mode = ($this->LongMode == 'yes') ? 'long' : 'normal';
			
			$data->Quarry = max($data->Quarry, 1);
			$data->Magic = max($data->Magic, 1);
			$data->Dungeons = max($data->Dungeons, 1);
			$data->Bricks = max($data->Bricks, 0);
			$data->Gems = max($data->Gems, 0);
			$data->Recruits = max($data->Recruits, 0);
			$data->Tower = min(max($data->Tower, 0), $game_config[$g_mode]['max_tower']);
			$data->Wall = min(max($data->Wall, 0), $game_config[$g_mode]['max_wall']);

			foreach ($data->TokenValues as $index => $token_val)
				$data->TokenValues[$index] = max(min($data->TokenValues[$index], 100), 0);
		}
		
		public function CalculateExp($player)
		{
			global $carddb;
			global $playerdb;
			global $game_config;
			
			// determine game mode (normal or long)
			$g_mode = ($this->LongMode == 'yes') ? 'long' : 'normal';
			
			// game configuration
			$max_tower = $game_config[$g_mode]['max_tower'];
			$max_wall = $game_config[$g_mode]['max_wall'];
			$res_vic = $game_config[$g_mode]['res_victory'];
			
			$opponent = ($this->Player1 == $player) ? $this->Player2 : $this->Player1;
			$mydata = $this->GameData[$player];
			$hisdata = $this->GameData[$opponent];
			$round = $this->Round;
			$winner = $this->Winner;
			$endtype = $this->EndType;
			$mylevel = $playerdb->GetLevel($player);
			$hislevel = $playerdb->GetLevel($opponent);
			
			$win = ($player == $winner);
			$exp = 100; // base exp
			$gold = 0; // base gold
			$message = 'Base = '.$exp.' EXP'."\n";
			
			// first phase: Game rating
			if ($endtype == 'Resource' AND $win) $mod = 1.15;
			elseif ($endtype == 'Construction' AND $win) $mod = 1.10;
			elseif ($endtype == 'Destruction' AND $win) $mod = 1.05;
			elseif ($endtype == 'Abandon' AND $win) $mod = 1;
			elseif ($endtype == 'Surrender' AND $win) $mod = 0.95;
			elseif ($endtype == 'Timeout' AND $win) $mod = 0.6;
			elseif ($endtype == 'Draw') $mod = 0.5;
			elseif ($endtype == 'Timeout' AND !$win) $mod = 0.4;
			elseif ($endtype == 'Destruction' AND !$win) $mod = 0.15;
			elseif ($endtype == 'Construction' AND !$win) $mod = 0.1;
			elseif ($endtype == 'Resource' AND !$win) $mod = 0.05;
			elseif ($endtype == 'Surrender' AND !$win) $mod = 0;
			elseif ($endtype == 'Abandon' AND !$win) $mod = 0;
			else $mod = 0; // should never happen
			
			// update exp and message
			$exp = round($exp * $mod);
			$message.= 'Game rating'."\n".'Modifier: '.$mod.', Total: '.$exp.' EXP'."\n";
			
			// second phase: Opponent rating
			if ($mylevel > $hislevel) $mod = 1 - 0.05 * min(10, $mylevel - $hislevel);
			elseif ($mylevel < $hislevel) $mod = 1 + 0.1 * min(10, $hislevel - $mylevel);
			else $mod = 1;
			
			// update exp and message
			$exp = round($exp * $mod);
			$message.= 'Opponent rating'."\n".'Modifier: '.$mod.', Total: '.$exp.' EXP'."\n";
			
			// third phase: Victory rating
			if ($win)// if player is winner
			{
				$bonus = array(1 => 1, 2 => 1.25, 3 => 1.75); // tactical (1), minor (2) and major (3) victory bonuses
				$victories = array();
				
				// Resource accumulation victory
				$enemy_stock = $hisdata->Bricks + $hisdata->Gems + $hisdata->Recruits;
				if ($enemy_stock < round($res_vic / 3)) $victories[] = 3;
				elseif (($enemy_stock >= round($res_vic / 3)) AND ($enemy_stock <= round($res_vic * 2 / 3))) $victories[] = 2;
				else $victories[] = 1;
				
				// Tower building victory
				if ($hisdata->Tower < round($max_tower / 3)) $victories[] = 3;
				elseif (($hisdata->Tower >= round($max_tower / 3)) AND ($hisdata->Tower <= round($max_tower * 2 / 3))) $victories[] = 2;
				else $victories[] = 1;
				
				// Tower destruction victory
				if ($mydata->Tower > round($max_tower * 2 / 3)) $victories[] = 3;
				elseif (($mydata->Tower >= round($max_tower / 3)) AND ($mydata->Tower <= round($max_tower * 2 / 3))) $victories[] = 2;
				else $victories[] = 1;
				
				$victory = floor(array_sum($victories) / count($victories)); // calculate avg (rounded down)
				$mod = $bonus[$victory];
				
				// update exp and message
				$exp = round($exp * $mod);
				$message.= 'Victory rating'."\n".'Modifier: '.$mod.', Total: '.$exp.' EXP'."\n";
			}
			else // if player is loser
			{
				$bonus = array(1 => 1, 2 => 1.25, 3 => 1.75); // tactical (1), minor (2) and major (3) victory bonuses
				$victories = array();
				
				// Resource accumulation victory
				$stock = $mydata->Bricks + $mydata->Gems + $mydata->Recruits;
				if ($stock > round($res_vic * 2 / 3)) $victories[] = 3;
				elseif (($stock >= round($res_vic / 3)) AND ($stock <= round($res_vic * 2 / 3))) $victories[] = 2;
				else $victories[] = 1;
				
				// Tower building victory
				if ($mydata->Tower > round($max_tower * 2 / 3)) $victories[] = 3;
				elseif (($mydata->Tower >= round($max_tower / 3)) AND ($mydata->Tower <= round($max_tower * 2 / 3))) $victories[] = 2;
				else $victories[] = 1;
				
				// Tower destruction victory
				if ($hisdata->Tower < round($max_tower / 3)) $victories[] = 3;
				elseif (($hisdata->Tower >= round($max_tower / 3)) AND ($hisdata->Tower <= round($max_tower * 2 / 3))) $victories[] = 2;
				else $victories[] = 1;
				
				$victory = ceil(array_sum($victories) / count($victories)); // calculate avg (rounded up)
				$mod = $bonus[$victory];
				
				// update exp and message
				$exp = round($exp * $mod);
				$message.= 'Victory rating'."\n".'Modifier: '.$mod.', Total: '.$exp.' EXP'."\n";
			}
			
			// fourth phase: Awards
			$received = array();
			
			if ($win)
			{
				$mylastcardindex = count($mydata->LastCard);
				$mylast_card = $carddb->GetCard($mydata->LastCard[$mylastcardindex]);
				$mylast_action = $mydata->LastAction[$mylastcardindex];
				$standard_victory = ($endtype == 'Resource' OR $endtype == 'Construction' OR $endtype == 'Destruction');
				
				// awards list 'award_name' => 'gold_gain'
				$awards = array(
				'Saboteur' => 1, 
				'Gentle touch' => 2, 
				'Desolator' => 3, 
				'Dragon' => 3, 
				'Carpenter' => 4, 
				'Titan' => 4, 
				'Assassin' => 5, 
				'Snob' => 6, 
				'Collector' => 7, 
				'Builder' => 8, 
				'Survivor' => 9
				);
				ksort($awards); // sort alphabetically
				
				$assassin_limit = ($g_mode == 'long') ? 20 : 10;
				
				// Assassin
				if ($round <= $assassin_limit AND $standard_victory) $received[] = 'Assassin';
				
				// Desolator
				if ($hisdata->Quarry == 1 AND $hisdata->Magic == 1 AND $hisdata->Dungeons == 1) $received[] = 'Desolator';
				
				// Dragon
				if ($mylast_card->HasKeyword("Dragon") AND $mylast_action == 'play' AND $standard_victory) $received[] = 'Dragon';
				
				// Carpenter
				if ($mydata->Quarry >= 6 AND $mydata->Magic >= 6 AND $mydata->Dungeons >= 6) $received[] = 'Carpenter';
				
				// Builder
				if ($mydata->Wall == $max_wall) $received[] = 'Builder';
				
				// Gentle touch
				if ($mylast_card->Class == 'Common' AND $mylast_action == 'play' AND $standard_victory) $received[] = 'Gentle touch';
				
				// Snob
				if ($mylast_action == 'discard' AND $standard_victory) $received[] = 'Snob';
				
				// Collector
				$tmp = 0;
				for ($i = 1; $i <= 8; $i++)
				{
					$cur_card = $carddb->GetCard($mydata->Hand[$i]);
					if ($cur_card->Class == "Rare") $tmp++;
				}
				if ($tmp >= 4) $received[] = 'Collector';
				
				// Titan
				if ($mylast_card->ID == 315 AND $mylast_action == 'play' AND $endtype == 'Destruction') $received[] = 'Titan';
				
				// Saboteur
				if ($hisdata->Tower == 0 AND $hisdata->Wall > 0 AND $standard_victory) $received[] = 'Saboteur';
				
				// Survivor
				if (($mydata->Tower == 1) AND ($mydata->Wall == 0)) $received[] = 'Survivor';
				
				// update message, calculate gold
				if (count($received) > 0)
				{
					$award_temp = array();
					foreach ($received as $award)
					{
						$gold+= $awards[$award];
						$award_temp[] = $award.' ('.$awards[$award].' gold)';
					}
					$message.= 'Awards'."\n".implode("\n", $award_temp)."\n";
				}
				else $message.= 'Awards'."\n".'None achieved'."\n";
			}
			
			// finalize report
			$message.= "\n".'You gained '.$exp.' EXP'.(($gold > 0) ? ' and '.$gold.' gold' : '');
			
			return array('exp' => $exp, 'gold' => $gold, 'message' => $message, 'awards' => $received);
		}

		public function DetermineAIMove()
		{
			return $this->GameAI->DetermineMove();
		}

		public function CategoryKeywords()
		{
      return array('Alliance', 'Aqua', 'Barbarian', 'Beast', 'Brigand', 'Burning', 'Destruction', 'Dragon', 'Holy', 'Illusion', 'Legend', 'Mage', 'Nature', 'Restoration', 'Runic', 'Soldier', 'Titan', 'Undead', 'Unliving');
		}

		public function EffectKeywords()
		{
      return array('Durable', 'Quick', 'Swift', 'Far sight', 'Banish', 'Skirmisher', 'Horde', 'Rebirth', 'Flare attack', 'Frenzy', 'Aria', 'Enduring', 'Charge', 'Siege');
		}

		private function LastRound() // fetch data of the first turn of the current round
		{
			global $replaydb;

			$replay = $replaydb->GetReplay($this->GameID);
			if (!$replay) return false;

			$turn_data = $replay->LastRound();
			if (!$turn_data) return false;

			return $turn_data->GameData;
		}
	}
	
	
	class CGamePlayerData
	{
		public $Deck; // CDeckData
		public $Hand; // array ($i => $cardid)
		public $LastCard; // list of cards played last turn (in the order they were played)
		public $LastMode; // list of modes corresponding to cards played last turn (each is 0 or 1-8)
		public $LastAction; // list of actions corresponding to cards played last turn ('play'/'discard')
		public $NewCards; // associative array, where keys are card positions which have changed (values are arbitrary at the moment)
		public $Revealed; // associative array, where keys are card positions which are revealed (values are arbitrary at the moment)
		public $Changes; // associative array, where keys are game atributes (resources, facilties, tower and wall). Values are ammount of difference
		public $DisCards; //array of two lists, one for each player. List contais all cards that where discarded during player's turn(s). Can be empty.
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
?>
