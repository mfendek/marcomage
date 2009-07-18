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
		
		public function CreateGame($player1, $player2, $deck1)
		{
			$db = $this->db;
			$result = $db->Query('SELECT IFNULL(MAX(`GameID`)+1, 1) as `max` FROM `games`');
			$data = $result->Next();
			$gameid = (int)$data['max'];
			
			$game = new CGame($gameid, $player1, $player2, $this);
			$game->GameData->Player[$player1]->Deck = $deck1;
			$game->GameData->Player[$player2]->Deck = 0;
			
			$result = $db->Query('INSERT INTO `games` (`GameID`, `Player1`, `Player2`, `State`, `Data`) VALUES ("'.$game->ID().'", "'.$db->Escape($game->Name1()).'", "'.$db->Escape($game->Name2()).'", "'.$db->Escape($game->State).'", "'.$db->Escape(serialize($game->GameData)).'")');
			if (!$result) return false;
			
			return $game;
		}
		
		public function DeleteGame($gameid)
		{
			$db = $this->db;
			$result = $db->Query('DELETE FROM `games` WHERE `GameID` = '.$db->Escape($gameid));
			if (!$result) return false;
			
			return true;
		}

		public function DeleteGame2($player1, $player2)
		{
			$db = $this->db;
			$result = $db->Query('DELETE FROM `games` WHERE `Player1` = "'.$db->Escape($player1).'" AND `Player2` = "'.$db->Escape($player2).'"');
			if (!$result) return false;
			
			return true;
		}
		
		public function GetGame($gameid)
		{
			$db = $this->db;
			$result = $db->Query('SELECT `Player1`, `Player2` FROM `games` WHERE `GameID` = '.$db->Escape($gameid));
			if (!$result) return false;
			if (!$result->Rows()) return false;
			
			$players = $result->Next();
			$player1 = $players['Player1'];
			$player2 = $players['Player2'];
			
			$game = new CGame($gameid, $player1, $player2, $this);
			$game->LoadGame();
			
			return $game;
		}

		public function GetGame2($player1, $player2)
		{
			$db = $this->db;
			$result = $db->Query('SELECT `GameID` FROM `games` WHERE `Player1` = "'.$db->Escape($player1).'" AND `Player2` = "'.$db->Escape($player2).'"');
			if (!$result) return false;
			if (!$result->Rows()) return false;
			
			$data = $result->Next();
			$gameid = $data['GameID'];
			
			$game = new CGame($gameid, $player1, $player2, $this);
			$game->LoadGame();
			
			return $game;
		}
		
		public function ListChallengesFrom($player)
		{
			// $player is on the left side and $Status = "waiting"
			$db = $this->db;
			$result = $db->Query('SELECT `Player1`, `Player2` FROM `games` WHERE `Player1` = "'.$db->Escape($player).'" AND `State` = "waiting"');
			if (!$result) return false;
			
			$games = array();
			for ($i = 1; $i <= $result->Rows(); $i++)
				$games[$i] = $result->Next();
			
			return $games;
		}
		
		public function ListChallengesTo($player)
		{
			// $player is on the right side and $Status = "waiting"
			$db = $this->db;
			$result = $db->Query('SELECT `Player1` FROM `games` WHERE `Player2` = "'.$db->Escape($player).'" AND `State` = "waiting"');
			if (!$result) return false;
			
			$games = array();
			for ($i = 1; $i <= $result->Rows(); $i++)
				$games[$i] = $result->Next();
			
			return $games;
		}
		
		public function ListActiveGames($player)
		{
			// $player is either on the left or right side and Status != 'waiting' or 'P? over'
			$db = $this->db;
			$result = $db->Query('SELECT `Player1`, `Player2` FROM `games` WHERE (`Player1` = "'.$db->Escape($player).'" AND (`State` != "waiting" AND `State` != "P1 over")) OR (`Player2` = "'.$db->Escape($player).'" AND (`State` != "waiting" AND `State` != "P2 over"))');
			if (!$result) return false;
			
			$games = array();
			for ($i = 1; $i <= $result->Rows(); $i++)
				$games[$i] = $result->Next();
			
			return $games;
		}
		
		public function ListEndedGames($player)
		{
			$db = $this->db;
			$result = $db->Query('SELECT `Player1`, `Player2` FROM `games` WHERE (`Player1` = "'.$db->Escape($player).'" AND `State` = "P1 over") OR (`Player2` = "'.$db->Escape($player).'" AND `State` = "P2 over")');
			if (!$result) return false;
			
			$games = array();
			for ($i = 1; $i <= $result->Rows(); $i++)
				$games[$i] = $result->Next();
			
			return $games;
		}
	}
	
	
	class CGame
	{
		private $Games;
		private $GameID;
		private $Player1;
		private $Player2;
		private $Note1;
		private $Note2;
		public $State; // 'waiting' / 'in progress' / 'finished' / 'P1 over' / 'P2 over'
		public $GameData;
		
		public function __construct($gameid, $player1, $player2, CGames $Games)
		{
			$this->GameID = $gameid;
			$this->Player1 = $player1;
			$this->Player2 = $player2;
			$this->State = "waiting";
			$this->Games = &$Games;
			$this->GameData = new CGameData;
			$this->GameData->Player[$player1] = new CGamePlayerData;
			$this->GameData->Player[$player2] = new CGamePlayerData;
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
		
		public function LoadGame()
		{
			$db = $this->Games->getDB();
			$result = $db->Query('SELECT `State`, `Data`, `Note1`, `Note2` FROM `games` WHERE `Player1` = "'.$db->Escape($this->Player1).'" AND `Player2` = "'.$db->Escape($this->Player2).'"');
			if (!$result) return false;
			if (!$result->Rows()) return false;
			
			$data = $result->Next();
			$this->State = $data['State'];
			$this->Note1 = $data['Note1'];
			$this->Note2 = $data['Note2'];
			$this->GameData = unserialize($data['Data']);
			
			return true;
		}
		
		public function SaveGame()
		{
			$db = $this->Games->getDB();
			$result = $db->Query('UPDATE `games` SET `State` = "'.$db->Escape($this->State).'", `Data` = "'.$db->Escape(serialize($this->GameData)).'", `Note1` = "'.$db->Escape($this->Note1).'", `Note2` = "'.$db->Escape($this->Note2).'" WHERE `Player1` = "'.$db->Escape($this->Player1).'" AND `Player2` = "'.$db->Escape($this->Player2).'"');
			if (!$result) return false;
			
			return true;
		}
		
		public function StartGame()
		{
			$this->State = 'in progress';
			
			$this->GameData->Current = ((mt_rand(0,1) == 1) ? $this->Player1 : $this->Player2);
			$this->GameData->Round = 1;
			$this->GameData->Winner = '';
			$this->GameData->Outcome = '';
			$this->GameData->Timestamp = time();
			
			$p1 = &$this->GameData->Player[$this->Player1];
			$p2 = &$this->GameData->Player[$this->Player2];
			
			$p1->LastCard[1] = $p2->LastCard[1] = 0;
			$p1->LastMode[1] = $p2->LastMode[1] = 0;
			$p1->LastAction[1] = $p2->LastAction[1] = 'play';
			$p1->NewCards = $p2->NewCards = null;
			$p1->DisCards[0] = $p1->DisCards[1] = $p2->DisCards[0] = $p2->DisCards[1] = null; //0 - cards discarded from my hand, 1 - discarded from opponents hand
			$p1->Changes = $p2->Changes = array ('Quarry'=> 0, 'Magic'=> 0, 'Dungeons'=> 0, 'Bricks'=> 0, 'Gems'=> 0, 'Recruits'=> 0, 'Tower'=> 0, 'Wall'=> 0);
			$p1->Tower = $p2->Tower = 30;
			$p1->Wall = $p2->Wall = 20;
			$p1->Quarry = $p2->Quarry = 3;
			$p1->Magic = $p2->Magic = 3;
			$p1->Dungeons = $p2->Dungeons = 3;
			$p1->Bricks = $p2->Bricks = 15;
			$p1->Gems = $p2->Gems = 5;
			$p1->Recruits = $p2->Recruits = 10;
			
			// add starting bonus to second player
			if ($this->GameData->Current == $this->Player1)
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
		}
		
		public function SurrenderGame($playername)
		{
			// only allow surrender if the game is still on
			if ($this->State != 'in progress') return 'Action not allowed!';
			
			$this->State = 'finished';
			$this->GameData->Winner = ($this->Player1 == $playername) ? $this->Player2 : $this->Player1;
			$this->GameData->Outcome = 'Opponent has surrendered';
			$this->SaveGame();
			
			return 'OK';
		}
		
		public function AbortGame($playername)
		{
			// only allow surrender if the game is still on
			if ($this->State != 'in progress') return 'Action not allowed!';
			
			$this->State = 'finished';
			$this->GameData->Winner = '';
			$this->GameData->Outcome = 'Aborted';
			$this->SaveGame();
			
			return 'OK';
		}
		
		public function FinishGame($playername)
		{
			// only allow surrender if the game is still on
			if ($this->State != 'in progress') return 'Action not allowed!';
			
			$this->State = 'finished';
			$this->GameData->Winner = ($this->Player1 == $playername) ? $this->Player1 : $this->Player2;
			$opponent = ($this->Player1 == $playername) ? $this->Player2 : $this->Player1;
			$this->GameData->Outcome = $opponent.' has fled the battlefield';
			$this->SaveGame();
			
			return 'OK';
		}
		
		public function PlayCard($playername, $cardpos, $mode, $action)
		{
			global $carddb;
			
			$data = &$this->GameData;
			
			// only allow discarding if the game is still on
			if ($this->State != 'in progress') return 'Action not allowed!';
			
			// only allow action when it's the players' turn
			if ($data->Current != $playername) return 'Action only allowed on your turn!';
			
			// anti-hack
			if (($cardpos < 1) || ($cardpos > 8)) return 'Wrong card position!';
			if (($action != 'play') && ($action != 'discard')) return 'Invalid action!';
			
			// prepare basic information
			$opponent = ($this->Player1 == $playername) ? $this->Player2 : $this->Player1;
			$mydata = &$data->Player[$playername];
			$hisdata = &$data->Player[$opponent];
			
			// find out what card is at that position
			$cardid = $mydata->Hand[$cardpos];
			$card = $carddb->GetCard($cardid);
			
			// process card history
			$mylastcardindex = count($mydata->LastCard);
			$hislastcardindex = count($hisdata->LastCard);
			
			// prepare supplementary information
			$mylast_card = $carddb->GetCard($mydata->LastCard[$mylastcardindex]);
			$mylast_action = $mydata->LastAction[$mylastcardindex];
			$hislast_card = $carddb->GetCard($hisdata->LastCard[$hislastcardindex]);
			$hislast_action = $hisdata->LastAction[$hislastcardindex];
			
			//we need to store this information, because some cards will need it to make their effect, however after effect this information is not stored
			$mychanges = $mydata->Changes;
			$hischanges = $hisdata->Changes;
			$mynewflags = $mydata->NewCards;
			$hisnewflags = $hisdata->NewCards;
			$discarded_cards[0] = $mydata->DisCards[0];
			$discarded_cards[1] = $mydata->DisCards[1];
			
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
				// verify mode (depends on card)
				if( $mode < 0
				||  $mode > $card->CardData->Modes
				||  $mode == 0 && $card->CardData->Modes > 0
				  ) return 'Bad mode!';
				
				// verify if there are enough resources
				if (($mydata->Bricks < $card->CardData->Bricks) || ($mydata->Gems < $card->CardData->Gems) || ($mydata->Recruits < $card->CardData->Recruits)) return 'Insufficient resources!';
				
				$mydata->Bricks-= $card->CardData->Bricks;
				$mydata->Gems-= $card->CardData->Gems;
				$mydata->Recruits-= $card->CardData->Recruits;
				
				//create a copy of interesting game attributes
				$mydata_temp = $hisdata_temp = array ('Quarry'=> 0, 'Magic'=> 0, 'Dungeons'=> 0, 'Bricks'=> 0, 'Gems'=> 0, 'Recruits'=> 0, 'Tower'=> 0, 'Wall'=> 0);
				
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
				if( eval($card->CardData->Code) === FALSE )
					error_log("Debug: ".$cardid.": ".$card->CardData->Code);
	
				//begin keyword processing
				
				//begin order independent keywords
				
				//process Durable cards - they stays on hand
				if ($card->HasKeyWord("Durable"))
					$nextcard = $cardid;
				
				//process Quick cards - play again with no production
				if ($card->HasKeyWord("Quick"))
				{
					$nextplayer = $playername;
					$bricks_production = 0;
					$gems_production = 0;
					$recruits_production = 0;
				}
				
				//process Swift cards - play again with production
				if ($card->HasKeyWord("Swift"))
				{
					$nextplayer = $playername;
				}
				
				//process Unliving cards - Sturdiness (1/3 of total card cost return)
				if ($card->HasKeyWord("Unliving"))
				{
					$ammount = $this->KeywordCount($mydata->Hand, "Unliving") - 1; // we don't count the played card
					$token_index = array_search("Unliving", $mydata->TokenNames);
					
					if ($token_index)
					{
						$mydata->TokenValues[$token_index]+= 9 + $ammount * 8; // basic gain + bonus gain
						
						if ($mydata->TokenValues[$token_index] >= 100)
						{
							$mydata->Bricks+= round($card->CardData->Bricks / 3);
							$mydata->Gems+= round($card->CardData->Gems / 3);
							$mydata->Recruits+= round($card->CardData->Recruits / 3);
							
							$mydata->TokenValues[$token_index] = 0;
						}
					}
				}
				
				//process Soldier cards - Veteran troops (1/2 recruits card cost return)
				if ($card->HasKeyWord("Soldier"))
				{
					$ammount = $this->KeywordCount($mydata->Hand, "Soldier") - 1; // we don't count the played card
					$token_index = array_search("Soldier", $mydata->TokenNames);
					
					if ($token_index)
					{
						$mydata->TokenValues[$token_index]+= 15 + $ammount * 10; // basic gain + bonus gain
						
						if ($mydata->TokenValues[$token_index] >= 100)
						{
							$mydata->Recruits+= round($card->CardData->Recruits / 2);
							
							$mydata->TokenValues[$token_index] = 0;
						}
					}
				}
				
				//process Mage cards - Willpower (raises magic by 1)
				if ($card->HasKeyWord("Mage"))
				{
					$ammount = $this->KeywordCount($mydata->Hand, "Mage") - 1; // we don't count the played card
					$token_index = array_search("Mage", $mydata->TokenNames);
					
					if ($token_index)
					{
						$mydata->TokenValues[$token_index]+= 10 + $ammount * 6; // basic gain + bonus gain
						
						if ($mydata->TokenValues[$token_index] >= 100)
						{
							$mydata->Magic+= 1;
							
							$mydata->TokenValues[$token_index] = 0;
						}
					}
				}
				
				//process Undead cards - Unholy sacrifice (discard undead card with highest cost, gain resources equal to the cost)
				if ($card->HasKeyWord("Undead"))
				{
					$ammount = $this->KeywordCount($mydata->Hand, "Undead") - 1; // we don't count the played card
					$token_index = array_search("Undead", $mydata->TokenNames);
					
					if ($token_index)
					{
						$mydata->TokenValues[$token_index]+= 5 + $ammount * 5; // basic gain + bonus gain
						
						if ($mydata->TokenValues[$token_index] >= 100)
						{
							$high = $costs = array();
							$max = 0;
							for ($i = 1; $i <= 8; $i++)
								if ($i != $cardpos)
								{
									$costs[$i] = $carddb->GetCard($mydata->Hand[$i])->GetResources('');
									if ($costs[$i] > $max) $max = $costs[$i];
								}
							
							for ($i = 1; $i <= 8; $i++)
								if (($i != $cardpos) AND ($costs[$i] == $max)) $high[$i] = $i;
							
							$target = array_rand($high);
							$discarded_card = $carddb->GetCard($mydata->Hand[$target]);
							$mydata->Hand[$target] = $this->DrawCard($mydata->Deck, $mydata->Hand, $target, 'DrawCard_random');
							$mydata->NewCards[$target] = 1;
							
							$mydata->Bricks+= min($discarded_card->GetResources('Bricks'), 20);
							$mydata->Gems+= min($discarded_card->GetResources('Gems'), 20);
							$mydata->Recruits+= min($discarded_card->GetResources('Recruits'), 20);
							
							$mydata->TokenValues[$token_index] = 0;
						}
					}
				}
				
				//process Burning cards - Fire blast (replace one card from enemy hand with Searing fire)
				if ($card->HasKeyWord("Burning"))
				{
					$ammount = $this->KeywordCount($mydata->Hand, "Burning") - 1; // we don't count the played card
					$token_index = array_search("Burning", $mydata->TokenNames);
					
					if ($token_index)
					{
						$mydata->TokenValues[$token_index]+= 3 + $ammount * 11; // basic gain + bonus gain
						
						if ($mydata->TokenValues[$token_index] >= 100)
						{
							// target card is discarded only if it has same or lower rarity then the played card
							$rarities = array("Common" => 0, "Uncommon" => 1, "Rare" => 2);
							$storage = array("Common" => array(), "Uncommon" => array(), "Rare" => array());
							$played_rank = $rarities[$card->GetClass()];
							
							for ($i = 1; $i <= 8; $i++)
							{
								$dis_card = $carddb->GetCard($hisdata->Hand[$i]);
								$dis_class = $dis_card->GetClass();
								$dis_rank = $rarities[$dis_class];
								
								// pick only cards that can be discarded by played card
								if ((!$dis_card->HasKeyword("Burning")) AND ($dis_rank <= $played_rank)) $storage[$dis_class][] = $i;
							}
							
							if ((count($storage['Common']) + count($storage['Uncommon']) + count($storage['Rare'])) > 0)
							{
								// pick preferably cards with higher rarity, but choose random card within the rarity group
								shuffle($storage['Common']); shuffle($storage['Uncommon']); shuffle($storage['Rare']);
								$storage_temp = array_merge($storage['Common'], $storage['Uncommon'], $storage['Rare']);
								$discarded_pos = array_pop($storage_temp);
								$hisdata->Hand[$discarded_pos] = 248;
								$hisdata->NewCards[$discarded_pos] = 1;
							}
							
							$mydata->TokenValues[$token_index] = 0;
						}
					}
				}
				
				//process Holy cards - Purification (discard one random undead card from enemy hand and get additional stock)
				if ($card->HasKeyWord("Holy"))
				{
					$ammount = $this->KeywordCount($mydata->Hand, "Holy") - 1; // we don't count the played card
					$token_index = array_search("Holy", $mydata->TokenNames);
					
					if ($token_index)
					{
						$mydata->TokenValues[$token_index]+= 25 + $ammount * 5; // basic gain + bonus gain
						
						if ($mydata->TokenValues[$token_index] >= 100)
						{
							// target card is discarded only if it has same or lower rarity then the played card
							$rarities = array("Common" => 0, "Uncommon" => 1, "Rare" => 2);
							$storage = array("Common" => array(), "Uncommon" => array(), "Rare" => array());
							$played_rank = $rarities[$card->GetClass()];
							
							for ($i = 1; $i <= 8; $i++)
							{
								$dis_card = $carddb->GetCard($hisdata->Hand[$i]);
								$dis_class = $dis_card->GetClass();
								$dis_rank = $rarities[$dis_class];
								
								// pick only cards that can be discarded by played card
								if (($dis_card->HasKeyword("Undead")) AND ($dis_rank <= $played_rank)) $storage[$dis_class][] = $i;
							}
							
							if ((count($storage['Common']) + count($storage['Uncommon']) + count($storage['Rare'])) > 0)
							{
								// pick preferably cards with higher rarity, but choose random card within the rarity group
								shuffle($storage['Common']); shuffle($storage['Uncommon']); shuffle($storage['Rare']);
								$storage_temp = array_merge($storage['Common'], $storage['Uncommon'], $storage['Rare']);
								$discarded_pos = array_pop($storage_temp);
								$dis_rarity = $carddb->GetCard($hisdata->Hand[$discarded_pos])->GetClass();
								$hisdata->Hand[$discarded_pos] = 381;
								$hisdata->NewCards[$discarded_pos] = 1;
								$stock = array("Common" => 1, "Uncommon" => 2, "Rare" => 3);
								$gained = $stock[$dis_rarity];
								$mydata->Bricks+= $gained;
								$mydata->Gems+= $gained;
								$mydata->Recruits+= $gained;
							}
							
							$mydata->TokenValues[$token_index] = 0;
						}
					}
				}
				
				//process Brigand cards - Robbery (steal additional stock)
				if ($card->HasKeyWord("Brigand"))
				{
					$ammount = $this->KeywordCount($mydata->Hand, "Brigand") - 1; // we don't count the played card
					$token_index = array_search("Brigand", $mydata->TokenNames);
					
					if ($token_index)
					{
						$mydata->TokenValues[$token_index]+= 10 + $ammount * 10; // basic gain + bonus gain
						
						if ($mydata->TokenValues[$token_index] >= 100)
						{
							$stock = array("Common" => 1, "Uncommon" => 2, "Rare" => 3);
							$gained = $stock[$card->GetClass()];
							$mydata->Bricks+= $gained;
							$mydata->Gems+= $gained;
							$mydata->Recruits+= $gained;
							$hisdata->Bricks-= $gained;
							$hisdata->Gems-= $gained;
							$hisdata->Recruits-= $gained;
							
							$mydata->TokenValues[$token_index] = 0;
						}
					}
				}
				
				//process Barbarian cards - Devastation (additional damage to enemy wall)
				if ($card->HasKeyWord("Barbarian"))
				{
					$ammount = $this->KeywordCount($mydata->Hand, "Barbarian") - 1; // we don't count the played card
					$token_index = array_search("Barbarian", $mydata->TokenNames);
					
					if ($token_index)
					{
						$mydata->TokenValues[$token_index]+= 4 + $ammount * 15; // basic gain + bonus gain
						
						if ($mydata->TokenValues[$token_index] >= 100)
						{
							$damage = array("Common" => 3, "Uncommon" => 8, "Rare" => 15);
							$hisdata->Wall-= $damage[$card->GetClass()];
							
							$mydata->TokenValues[$token_index] = 0;
						}
					}
				}
				
				//process Beast cards - Fierce attack (additional damage to enemy)
				if ($card->HasKeyWord("Beast"))
				{
					$ammount = $this->KeywordCount($mydata->Hand, "Beast") - 1; // we don't count the played card
					$token_index = array_search("Beast", $mydata->TokenNames);
					
					if ($token_index)
					{
						$mydata->TokenValues[$token_index]+= 14 + $ammount * 10; // basic gain + bonus gain
						
						if ($mydata->TokenValues[$token_index] >= 100)
						{
							$damage = array("Common" => 2, "Uncommon" => 5, "Rare" => 10);
							$this->Attack($damage[$card->GetClass()], $hisdata->Tower, $hisdata->Wall);
							
							$mydata->TokenValues[$token_index] = 0;
						}
					}
				}
				
				//process Dragon cards - get a Dragon egg when there are at least two dragon cards in hand
				if ($card->HasKeyWord("Dragon"))
				{
					if ($this->KeywordCount($mydata->Hand, "Dragon") > 1) $nextcard = 131;
				}
				
				//process Banish cards - discard one random Durable card from enemy hand, if there is one
				if ($card->HasKeyWord("Banish"))
				{
					// target card is discarded only if it has same or lower rarity then the played card
					$rarities = array("Common" => 0, "Uncommon" => 1, "Rare" => 2);
					$storage = array("Common" => array(), "Uncommon" => array(), "Rare" => array());
					$played_rank = $rarities[$card->GetClass()];
					
					for ($i = 1; $i <= 8; $i++)
					{
						$dis_card = $carddb->GetCard($hisdata->Hand[$i]);
						$dis_class = $dis_card->GetClass();
						$dis_rank = $rarities[$dis_class];
						
						// pick only cards that can be discarded by played card
						if (($dis_card->HasKeyword("Durable")) AND ($dis_rank <= $played_rank)) $storage[$dis_class][] = $i;
					}
					
					if ((count($storage['Common']) + count($storage['Uncommon']) + count($storage['Rare'])) > 0)
					{
						// pick preferably cards with higher rarity, but choose random card within the rarity group
						shuffle($storage['Common']); shuffle($storage['Uncommon']); shuffle($storage['Rare']);
						$storage_temp = array_merge($storage['Common'], $storage['Uncommon'], $storage['Rare']);
						$discarded_pos = array_pop($storage_temp);
						$hisdata->Hand[$discarded_pos] = $this->DrawCard($hisdata->Deck, $hisdata->Hand, $discarded_pos, 'DrawCard_random');
						$hisdata->NewCards[$discarded_pos] = 1;
					}
				}
				
				//process Titan cards - Titan's will (draw a Titan card)
				if ($card->HasKeyWord("Titan"))
				{
					$ammount = $this->KeywordCount($mydata->Hand, "Titan") - 1; // we don't count the played card
					$token_index = array_search("Titan", $mydata->TokenNames);
					
					if ($token_index)
					{
						$mydata->TokenValues[$token_index]+= 22 + $ammount * 5; // basic gain + bonus gain
						
						if ($mydata->TokenValues[$token_index] >= 100)
						{
							$nextcard = $this->DrawCard($carddb->GetList(array('keyword'=>"Titan")), $mydata->Hand, $cardpos, 'DrawCard_list');
							
							$mydata->TokenValues[$token_index] = 0;
						}
					}
				}
				
				//process Alliance cards - Arcane knowledge (additional Production X2)
				if ($card->HasKeyWord("Alliance"))
				{
					$ammount = $this->KeywordCount($mydata->Hand, "Alliance") - 1; // we don't count the played card
					$token_index = array_search("Alliance", $mydata->TokenNames);
					
					if ($token_index)
					{
						$mydata->TokenValues[$token_index]+= 17 + $ammount * 3; // basic gain + bonus gain
						
						if ($mydata->TokenValues[$token_index] >= 100)
						{
							$bricks_production*= 2;
							$gems_production*= 2;
							$recruits_production*= 2;
							
							$mydata->TokenValues[$token_index] = 0;
						}
					}
				}
				
				//process Legend cards - raises all facilities by one, if there is a rare card in hand
				if ($card->HasKeyWord("Legend"))
				{
					$found = false;
					for ($i = 1; $i <= 8; $i++)
						if (($i != $cardpos) AND !$found) // played card does not count
						{
							$cur_card = $carddb->GetCard($mydata->Hand[$i]);
							if ($cur_card->GetClass() == "Rare") $found = true;
						}
					
					if ($found)
					{
						$mydata->Quarry+= 1;
						$mydata->Magic+= 1;
						$mydata->Dungeons+= 1;
					}
				}
				
				//process Skirmisher cards - discard one random Charge card from enemy hand, if there is one
				if ($card->HasKeyWord("Skirmisher"))
				{
					// target card is discarded only if it has same or lower rarity then the played card
					$rarities = array("Common" => 0, "Uncommon" => 1, "Rare" => 2);
					$storage = array("Common" => array(), "Uncommon" => array(), "Rare" => array());
					$played_rank = $rarities[$card->GetClass()];
					
					for ($i = 1; $i <= 8; $i++)
					{
						$dis_card = $carddb->GetCard($hisdata->Hand[$i]);
						$dis_class = $dis_card->GetClass();
						$dis_rank = $rarities[$dis_class];
						
						// pick only cards that can be discarded by played card
						if (($dis_card->HasKeyword("Charge")) AND ($dis_rank <= $played_rank)) $storage[$dis_class][] = $i;
					}
					
					if ((count($storage['Common']) + count($storage['Uncommon']) + count($storage['Rare'])) > 0)
					{
						// pick preferably cards with higher rarity, but choose random card within the rarity group
						shuffle($storage['Common']); shuffle($storage['Uncommon']); shuffle($storage['Rare']);
						$storage_temp = array_merge($storage['Common'], $storage['Uncommon'], $storage['Rare']);
						$discarded_pos = array_pop($storage_temp);
						$hisdata->Hand[$discarded_pos] = $this->DrawCard($hisdata->Deck, $hisdata->Hand, $discarded_pos, 'DrawCard_random');
						$hisdata->NewCards[$discarded_pos] = 1;
					}
				}
				
				//process Destruction cards - reduces enemy facility or resource
				if (($card->HasKeyWord("Destruction")) AND ($mylast_card->HasKeyword("Destruction")) AND ($mylast_action == 'play') AND ($mylast_card->GetClass() != 'Common') AND ($cardid != $mylast_card->GetID()))
				{
					$max = max($hisdata->Quarry, $hisdata->Magic, $hisdata->Dungeons);
					if ($max > 3)
					{
						$facilities = array("Quarry" => $hisdata->Quarry, "Magic" => $hisdata->Magic, "Dungeons" => $hisdata->Dungeons);
						$temp = array();
						foreach ($facilities as $facility => $f_value)
							if ($f_value == $max) $temp[$facility] = $f_value;
						$chosen = array_rand($temp);						
						$hisdata->$chosen--;
					}
					else
					{
						$max = max($hisdata->Bricks, $hisdata->Gems, $hisdata->Recruits);
						$resources = array("Bricks" => $hisdata->Bricks, "Gems" => $hisdata->Gems, "Recruits" => $hisdata->Recruits);
						$temp = array();
						foreach ($resources as $resource => $r_value)
							if ($r_value == $max) $temp[$resource] = $r_value;
						$chosen = array_rand($temp);						
						$hisdata->$chosen-= 10;
					}
				}
				
				//process Restoration cards - raises facility or resource
				if (($card->HasKeyWord("Restoration")) AND ($mylast_card->HasKeyword("Restoration")) AND ($mylast_action == 'play') AND ($mylast_card->GetClass() != 'Common') AND ($cardid != $mylast_card->GetID()))
				{
					$min = min($mydata->Quarry, $mydata->Magic, $mydata->Dungeons);
					if ($min < 3)
					{
						$facilities = array("Quarry" => $mydata->Quarry, "Magic" => $mydata->Magic, "Dungeons" => $mydata->Dungeons);
						$temp = array();
						foreach ($facilities as $facility => $f_value)
							if ($f_value == $min) $temp[$facility] = $f_value;
						$chosen = array_rand($temp);						
						$mydata->$chosen++;
					}
					else
					{
						$min = min($mydata->Bricks, $mydata->Gems, $mydata->Recruits);
						$resources = array("Bricks" => $mydata->Bricks, "Gems" => $mydata->Gems, "Recruits" => $mydata->Recruits);
						$temp = array();
						foreach ($resources as $resource => $r_value)
							if ($r_value == $min) $temp[$resource] = $r_value;
						$chosen = array_rand($temp);						
						$mydata->$chosen+= 10;
					}
				}
				
				//process Illusion cards - draw a rare card from enemy deck
				if (($card->HasKeyWord("Illusion")) AND ($mylast_card->HasKeyword("Illusion")) AND ($mylast_action == 'play') AND ($mylast_card->GetClass() != 'Common') AND ($cardid != $mylast_card->GetID()))
				{
					$nextcard = $this->DrawCard($hisdata->Deck->Rare, $mydata->Hand, $cardpos, 'DrawCard_list');
				}
				
				//process Nature cards - draw a rare nature card
				if (($card->HasKeyWord("Nature")) AND ($mylast_card->HasKeyword("Nature")) AND ($mylast_action == 'play') AND ($mylast_card->GetClass() != 'Common') AND ($cardid != $mylast_card->GetID()))
				{
					$nextcard = $this->DrawCard($carddb->GetList(array('class'=>"Rare", 'keyword'=>"Nature")), $mydata->Hand, $cardpos, 'DrawCard_list');
				}
				
				//end order independent keywords
				
				//begin order dependent keywords
				
				//process Rebirth cards - if there are enough Burning cards in game the card stays on hand and player get additional gems
				if ($card->HasKeyWord("Rebirth"))
				{
					if (($this->KeywordCount($mydata->Hand, "Burning") + $this->KeywordCount($hisdata->Hand, "Burning")) > 3)
					{
						$nextcard = $cardid;
						$mydata->Gems+= 16;
					}
				}
				
				//process Flare attack cards - place searing fire cards to both players hands (odd and even positions randomly selected)
				if ($card->HasKeyWord("Flare attack"))
				{
					$selector = mt_rand(0,1);
					for ($i = 1; $i <= 4; $i++)
					{
						// current index (odd and even positions)
						$mine = 2*$i - $selector;
						$his = 2*$i - (1 - $selector);
						
						$mytarget = $carddb->GetCard($mydata->Hand[$mine]);
						$histarget = $carddb->GetCard($hisdata->Hand[$his]);
						
						$my_rarity = $mytarget->GetClass();
						$his_rarity = $histarget->GetClass();
						
						// played card position is ignored, does not discard burning cards (rares cards can only be rares)
						if (($mine != $cardpos) AND (!$mytarget->HasKeyword("Burning")) AND (($my_rarity != 'Rare') OR ($my_rarity == $card->GetClass())))
						{
							$mydata->Hand[$mine] = 248;
							$mydata->NewCards[$mine] = 1;
						}
						
						if ((!$histarget->HasKeyword("Burning")) AND (($his_rarity != 'Rare') OR ($his_rarity == $card->GetClass())))
						{
							$hisdata->Hand[$his] = 248;
							$hisdata->NewCards[$his] = 1;
						}
					}
				}
				
				//process Frenzy cards - deal additional damage based on recruits cost
				if ($card->HasKeyWord("Frenzy"))
				{
					if ($this->KeywordCount($mydata->Hand, "Frenzy") > 1) $this->Attack($card->GetResources('Recruits'), $hisdata->Tower, $hisdata->Wall);
				}
				
				//process Enduring cards - if last card played was the same card, bonus attack
				if ($card->HasKeyWord("Enduring"))
				{
					if (($mydata->LastCard[$mylastcardindex] == $cardid) AND ($mylast_action == 'play'))
					{
						$bonus_damage = $this->KeywordValue($card->GetKeywords(), 'Enduring');
						$this->Attack($bonus_damage, $hisdata->Tower, $hisdata->Wall);
					}
				}
				
				//process Charge cards - if enemy wall is 0, bonus damage to enemy tower
				if ($card->HasKeyWord("Charge"))
				{
					$charge_damage = $this->KeywordValue($card->GetKeywords(), 'Charge');
					if ($hisdata->Wall == 0) $hisdata->Tower-= $charge_damage;
				}
				
				//end order dependent keywords
				
				//end keyword processing
				
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
					}
					
					if (((!(isset($hisnewcards[$i]))) and (isset($hisdata->NewCards[$i]))) or ($hishand[$i] != $hisdata->Hand[$i]))
					{
						$hisdiscards_index++;
						$mydata->DisCards[1][$hisdiscards_index] = $hishand[$i];
					}
					
				}
				
				// apply game limits and compute the changes
				$mydata_copy = $hisdata_copy = array ('Quarry'=> 0, 'Magic'=> 0, 'Dungeons'=> 0, 'Bricks'=> 0, 'Gems'=> 0, 'Recruits'=> 0, 'Tower'=> 0, 'Wall'=> 0);
				
				// create a copy of all game attributes
				foreach ($mydata_copy as $attribute => $value)
				{
					$mydata_copy[$attribute] = $mydata->$attribute;
					$hisdata_copy[$attribute] = $hisdata->$attribute;
				}
				
				// apply game limits to copy of attributes
				$mydata_copy = $this->ApplyGameLimits($mydata_copy);
				$hisdata_copy = $this->ApplyGameLimits($hisdata_copy);
				
				// apply limits to game attributes
				foreach ($mydata_copy as $attribute => $value)
				{
					$mydata->$attribute = $mydata_copy[$attribute];
					$hisdata->$attribute = $hisdata_copy[$attribute];
				}
				
				// production is applied to copy of the game attributes only (for changes array needs), production factor is descresed because normal production is a default card effect, thus it doesn't need to be highlighted - only abnormal productions (production X0, X2, X3...) are displayed via changes array
				$mydata_copy['Bricks']+= ($bricks_production - 1) * $mydata->Quarry;
				$mydata_copy['Gems']+= ($gems_production - 1) * $mydata->Magic;
				$mydata_copy['Recruits']+= ($recruits_production - 1) * $mydata->Dungeons;
				
				// add the new difference to the changes arrays
				foreach ($mydata_temp as $attribute => $value)
				{
					$mydata->Changes[$attribute] += $mydata_copy[$attribute] - $mydata_temp[$attribute];
					$hisdata->Changes[$attribute] += $hisdata_copy[$attribute] - $hisdata_temp[$attribute];
				}
				
				// compute changes on token counters
				foreach ($mytokens_temp as $index => $token_val)
				{
					$mydata->TokenChanges[$index] += $mydata->TokenValues[$index] - $mytokens_temp[$index];
					$hisdata->TokenChanges[$index] += $hisdata->TokenValues[$index] - $histokens_temp[$index];
				}
				
				// apply limits to token counters
				foreach ($mytokens_temp as $index => $token_val)
				{
					$mydata->TokenValues[$index] = max(min($mydata->TokenValues[$index], 100), 0);
					$hisdata->TokenValues[$index] = max(min($hisdata->TokenValues[$index], 100), 0);
				}
			}
			
			// add production at the end of turn
			$mydata->Bricks+= $bricks_production * $mydata->Quarry;
			$mydata->Gems+= $gems_production * $mydata->Magic;
			$mydata->Recruits+= $recruits_production * $mydata->Dungeons;
										
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
				
				$mydata->Hand[$cardpos] = $this->DrawCard($mydata->Deck, $mydata->Hand, $cardpos, $drawfunc);
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
			
			// check victory conditions (in this predetermined order)
			if(     $mydata->Tower > 0 and $hisdata->Tower <= 0 )
			{	// tower destruction victory - player
				$data->Winner = $playername;
				$data->Outcome = 'Tower destruction victory';
				$this->State = 'finished';
			}
			elseif( $mydata->Tower <= 0 and $hisdata->Tower > 0 )
			{	// tower destruction victory - opponent
				$data->Winner = $opponent;
				$data->Outcome = 'Tower destruction victory';
				$this->State = 'finished';
			}
			elseif( $mydata->Tower <= 0 and $hisdata->Tower <= 0 )
			{	// tower destruction victory - draw
				$data->Winner = '';
				$data->Outcome = 'Draw';
				$this->State = 'finished';
			}
			elseif( $mydata->Tower >= 100 and $hisdata->Tower < 100 )
			{	// tower building victory - player
				$data->Winner = $playername;
				$data->Outcome = 'Tower building victory';
				$this->State = 'finished';
			}
			elseif( $mydata->Tower < 100 and $hisdata->Tower >= 100 )
			{	// tower building victory - opponent
				$data->Winner = $opponent;
				$data->Outcome = 'Tower building victory';
				$this->State = 'finished';
			}
			elseif( $mydata->Tower >= 100 and $hisdata->Tower >= 100 )
			{	// tower building victory - draw
				$data->Winner = '';
				$data->Outcome = 'Draw';
				$this->State = 'finished';
			}
			elseif( ($mydata->Bricks + $mydata->Gems + $mydata->Recruits) >= 400 and !(($hisdata->Bricks + $hisdata->Gems + $hisdata->Recruits) >= 400) )
			{	// resource accumulation victory - player
				$data->Winner = $playername;
				$data->Outcome = 'Resource accumulation victory';
				$this->State = 'finished';
			}
			elseif( ($hisdata->Bricks + $hisdata->Gems + $hisdata->Recruits) >= 400 and !(($mydata->Bricks + $mydata->Gems + $mydata->Recruits) >= 400) )
			{	// resource accumulation victory - opponent
				$data->Winner = $opponent;
				$data->Outcome = 'Resource accumulation victory';
				$this->State = 'finished';
			}
			elseif( ($mydata->Bricks + $mydata->Gems + $mydata->Recruits) >= 400 and ($hisdata->Bricks + $hisdata->Gems + $hisdata->Recruits) >= 400 )
			{	// resource accumulation victory - draw
				$data->Winner = '';
				$data->Outcome = 'Draw';
				$this->State = 'finished';
			}
			elseif( $data->Round >= 250 )
			{	// timeout victory
				$data->Outcome = 'Timeout victory';
				$this->State = 'finished';
				
				// compare towers
				if    ( $mydata->Tower > $hisdata->Tower ) $data->Winner = $playername;
				elseif( $mydata->Tower < $hisdata->Tower ) $data->Winner = $opponent;
				// compare walls
				elseif( $mydata->Wall > $hisdata->Wall ) $data->Winner = $playername;
				elseif( $mydata->Wall < $hisdata->Wall ) $data->Winner = $opponent;
				// compare facilities
				elseif( $mydata->Quarry + $mydata->Magic + $mydata->Dungeons > $hisdata->Quarry + $hisdata->Magic + $hisdata->Dungeons ) $data->Winner = $playername;
				elseif( $mydata->Quarry + $mydata->Magic + $mydata->Dungeons < $hisdata->Quarry + $hisdata->Magic + $hisdata->Dungeons ) $data->Winner = $opponent;
				// compare resources
				elseif( $mydata->Bricks + $mydata->Gems + $mydata->Recruits > $hisdata->Bricks + $hisdata->Gems + $hisdata->Recruits ) $data->Winner = $playername;
				elseif( $mydata->Bricks + $mydata->Gems + $mydata->Recruits < $hisdata->Bricks + $hisdata->Gems + $hisdata->Recruits ) $data->Winner = $opponent;
				// else draw
				else
				{
					$data->Winner = '';
					$data->Outcome = 'Draw';
				}
			}
			else
			{	//game continues
				$data->Current = $nextplayer;
				$data->Timestamp = time();
				if( $nextplayer != $playername )
					$data->Round++;
			}
			
			return 'OK';
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
				$keyword = $carddb->GetCard($cardid)->GetKeywords();
				if ($keyword != "") // ignore cards with no keywords
					if ($first)
					{
						$keywords_list = $keyword;
						$first = false;
					}
					else $keywords_list.= " ".$keyword;
			}
			
			if ($keywords_list == "") return 0; // no keywords in hand
			
			$words = preg_split("/\. ?/", $keywords_list, -1, PREG_SPLIT_NO_EMPTY); // split individual keywords
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
			while (1)
			{
				$nextcard = $this->$draw_function($source, $hand[$card_pos]);
				
				// count the number of occurences of the same card on other slots
				$match = 0;
				for ($i = 1; $i <= 8; $i++)
					if (($hand[$i] == $nextcard) and ($card_pos != $i))
						$match++; //do not count the card already played
				
				if (mt_rand(1, pow(2, $match)) == 1) return $nextcard; // chance to retain the card decreases exponentially as the number of matches increases
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
			return $list[array_rand($list)];
		}
		
		// returns a new hand consisting of type-random cards chosen from the specified deck
		private function DrawHand_random(CDeckData $deck)
		{
			return $this->DrawHand($deck, 'DrawCard_random');
		}
		
		// returns a starting hand
		private function DrawHand_initial(CDeckData $deck)
		{
			return $this->DrawHand($deck, 'DrawCard_norare');
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
		
		private function ApplyGameLimits(array $attributes)
		{			
			if ($attributes["Quarry"] < 1) $attributes["Quarry"] = 1;
			if ($attributes["Magic"] < 1) $attributes["Magic"] = 1;
			if ($attributes["Dungeons"] < 1) $attributes["Dungeons"] = 1;
			if ($attributes["Bricks"] < 0) $attributes["Bricks"] = 0;
			if ($attributes["Gems"] < 0) $attributes["Gems"] = 0;
			if ($attributes["Recruits"] < 0) $attributes["Recruits"] = 0;
			if ($attributes["Tower"] < 0) $attributes["Tower"] = 0;
			if ($attributes["Tower"] > 100) $attributes["Tower"] = 100;
			if ($attributes["Wall"] < 0) $attributes["Wall"] = 0;
			if ($attributes["Wall"] > 150) $attributes["Wall"] = 150;
			
			return $attributes;
		}
	}
	
	
	class CGameData
	{
		public $Player; // array (name => CGamePlayerData)
		public $Current; // name of the player whose turn it currently is
		public $Round; // incremented after each play/discard action
		public $Winner; // if defined, name of the winner
		public $Outcome; // type: 'Build victory', 'Tower elimination victory', 'Opponent has surrendered', 'Draw', 'Aborted', 'Opponent fled the battlefield'
		public $Timestamp; // timestamp of the most recent action
	}
	
	class CGamePlayerData
	{
		public $Deck; // CDeckData
		public $Hand; // array ($i => $cardid)
		public $LastCard; // list of cards played last turn (in the order they were played)
		public $LastMode; // list of modes corresponding to cards played last turn (each is 0 or 1-8)
		public $LastAction; // list of actions corresponding to cards played last turn ('play'/'discard')
		public $NewCards; // associative array, where keys are card positions which have changed (values are arbitrary at the moment)
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
