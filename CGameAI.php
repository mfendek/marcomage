<?php
	// MArcomage AI

	// 1 - determine playable cards
	// 2 - simulate each card with every one of its card modes via CalculatePreview()
	// 3 - analyse impact of each case on game
	// 4 - sort choices by accumulated points
	// 5 - cards with zero or less points are not qualified for playing
	// 6 - card is discarded if there are no qualified nor playable cards
	// 7 - discard action chooses a card which requires highest accumulation of additional resources to be playable
?>
<?php
	class CGameAI
	{
		protected $Game;

		public function __construct(CGame $game)
		{
			$this->Game = $game;
		}

		protected function StaticConfig()
		{
			// AI behavior configuration (more points, more likely to choose such action)

			// static configuration (base value)
			$static['mine']['Quarry'] = 80;
			$static['mine']['Magic'] = 120;
			$static['mine']['Dungeons'] = 100;
			$static['mine']['Bricks'] = 4;
			$static['mine']['Gems'] = 6;
			$static['mine']['Recruits'] = 5;
			$static['mine']['Tower'] = 7.5;
			$static['mine']['Wall'] = 5;

			$static['his']['Quarry'] = 96;
			$static['his']['Magic'] = 144;
			$static['his']['Dungeons'] = 120;
			$static['his']['Bricks'] = 4.8;
			$static['his']['Gems'] = 7.2;
			$static['his']['Recruits'] = 6;
			$static['his']['Tower'] = 9;
			$static['his']['Wall'] = 6;

			return $static;
		}

		protected function MiscConfig()
		{
			// misc configuration (special actions)
			$misc['play_again'] = 300; // play again cards (Quick and Swift)
			$misc['summon'] = 150; // summon rare cards
			$misc['discard'] = 250; // discard rare cards
			$misc['cleanup'] = 50; // discard poison cards (common cards not from deck)
			$misc['poison'] = 50; // add poison cards

			return $misc;
		}

		protected function DynamicConfig($ai_player)
		{
			global $game_config;

			$game = $this->Game;

			// determine game mode (normal or long)
			$g_mode = ($game->GetGameMode('LongMode') == 'yes') ? 'long' : 'normal';

			// game configuration
			$max_tower = $game_config[$g_mode]['max_tower'];
			$max_wall = $game_config[$g_mode]['max_wall'];

			// prepare basic information
			$opponent = ($game->Name1() == $ai_player) ? $game->Name2() : $game->Name1();
			$mydata = $game->GameData[$ai_player];
			$hisdata = $game->GameData[$opponent];

			// AI behavior configuration (more points, more likely to choose such action)

			// dynamic configuration (adjustment factor based on current game situation)
			foreach (array('Quarry', 'Magic', 'Dungeons') as $facility)
			{
				$dynamic['mine'][$facility] = $this->FacilityEval($mydata->$facility);
				$dynamic['his'][$facility] = $this->FacilityEval($hisdata->$facility);
			}

			foreach (array('Bricks', 'Gems', 'Recruits') as $resource)
			{
				$dynamic['mine'][$resource] = $this->ResourceEval($mydata->$resource);
				$dynamic['his'][$resource] = $this->ResourceEval($hisdata->$resource);
			}

			$dynamic['mine']['Tower'] = $this->TowerEval($mydata->Tower, $max_tower);
			$dynamic['mine']['Wall'] = $this->WallEval($mydata->Wall, $max_wall);
			$dynamic['his']['Tower'] = $this->TowerEval($hisdata->Tower, $max_tower);
			$dynamic['his']['Wall'] = $this->WallEval($hisdata->Wall, $max_wall);

			return $dynamic;
		}

		protected function FacilityEval($facility) { return min(2.5, (6 / (pow($facility, 2))) + 0.6); }
		protected function ResourceEval($resource) { return ((20 / ($resource + 40)) + 0.8); }

		protected function TowerEval($tower, $max_tower)
		{
			$ratio = ($tower / $max_tower) * 100;
			return ((pow(($ratio - 50), 2) / 3000) + 0.9);
		}

		protected function WallEval($wall, $max_wall)
		{
			$ratio = ($wall / $max_wall) * 100;
			return min(1.5, (5 / ($ratio + 5)) + 0.85);
		}

		protected function Config($ai_player)
		{
			// compute adjusted points (base value * adjustment factor)
			$ai_config = array();
			$static = $this->StaticConfig();
			$dynamic = $this->DynamicConfig($ai_player);

			foreach (array('mine', 'his') as $side)
				foreach ($static[$side] as $name => $value)
					$ai_config[$side][$name] = $value * $dynamic[$side][$name];

			return $ai_config;
		}

		public function DetermineMove($ai_player) // determine card action ('play'/'discard'), card position (1-8) and card mode (0/1-8)
		{
			global $carddb;
			global $game_config;

			$game = $this->Game;

			// determine game mode (normal or long)
			$g_mode = ($game->GetGameMode('LongMode') == 'yes') ? 'long' : 'normal';

			// game configuration
			$max_tower = $game_config[$g_mode]['max_tower'];
			$max_wall = $game_config[$g_mode]['max_wall'];
			$res_vic = $game_config[$g_mode]['res_victory'];

			// prepare basic information
			$opponent = ($game->Name1() == $ai_player) ? $game->Name2() : $game->Name1();
			$mydata = $game->GameData[$ai_player];
			$hisdata = $game->GameData[$opponent];
			$my_deck = $mydata->Deck;
			$his_deck = $hisdata->Deck;

			// backup initial data state
			$mydata_b = clone $mydata;
			$hisdata_b = clone $hisdata;

			// format game attributes
			$my_attributes = $his_attributes = array();
			foreach (array('Quarry', 'Magic', 'Dungeons', 'Bricks', 'Gems', 'Recruits', 'Tower', 'Wall') as $attribute)
			{
				$my_attributes[$attribute] = $mydata->$attribute;
				$his_attributes[$attribute] = $hisdata->$attribute;
			}

			// default action
			$action = 'discard';

			// determine playable cards
			$playable_positions = array(); // 'card_position' => 'card modes'
			$myhand = $mydata->Hand;
			$my_handdata = $carddb->GetData($myhand);
			$his_handdata = $carddb->GetData($hisdata->Hand);

			foreach( $my_handdata as $i => $card )
				if ($mydata->Bricks >= $card['bricks'] and $mydata->Gems >= $card['gems'] and $mydata->Recruits >= $card['recruits'])
					$playable_positions[] = $i;

			// choose a card for playing
			if (count($playable_positions) > 0)
			{
				// determine all possible actions is this turn
				$max_points = 0;
				$choices = array();
				$ai_config = $this->Config($ai_player);
				$misc_config = $this->MiscConfig();

				foreach ($playable_positions as $pos)
				{
					$card = $my_handdata[$pos];
					$play_again = (strpos($card['keywords'], 'Quick') !== false or strpos($card['keywords'], 'Swift') !== false);

					if ($card['modes'] > 0) $modes = array_keys(array_fill(1, $card['modes'], 0));
					else $modes = array(0);

					// analyze all possible card modes
					foreach ($modes as $i)
					{
						$points = 0;
						$preview = $game->CalculatePreview($ai_player, $pos, $i);

						$player_data = $preview['player'];
						$opponent_data = $preview['opponent'];

						$my_after = $player_data['attributes'];
						$his_after = $opponent_data['attributes'];

						// calculate points from my attributes
						foreach ($my_attributes as $attr_name => $attr_value)
							$points+= ($my_after[$attr_name] - $my_attributes[$attr_name]) * $ai_config['mine'][$attr_name];

						// calculate points from his attributes
						foreach ($his_attributes as $attr_name => $attr_value)
							$points+= ($his_attributes[$attr_name] - $his_after[$attr_name]) * $ai_config['his'][$attr_name];

						// add extra points in case of play again card
						if ($play_again) $points+= $misc_config['play_again'];

						// analyze rare cards in hand
						$my_handchanges = $player_data['hand_changes'];
						$his_handchanges = $opponent_data['hand_changes'];

						if (is_array($my_handchanges))
							foreach ($my_handchanges as $card_pos => $cur_card)
							{
								// previous card
								$prev_card = $my_handdata[$card_pos];
								$prev_class = $prev_card['class'];

								// new card
								$card_data = $carddb->GetCard($cur_card);
								$card_class = $card_data->Class;

								// gain extra points if rare cards were added to player's hand (summoning related cards)
								if ($card_class == 'Rare')
									$points+= $misc_config['summon'];

								// lose points if rare cards were discarded from hand (ignore played card position)
								if ($card_pos != $pos and $prev_class == 'Rare')
								{
									// determine how soon is possible to play this card
									$cost_needed = $prev_card['bricks'] + $prev_card['gems'] + $prev_card['recruits'];
									$cost_missing = max(0, $prev_card['bricks'] - $my_attributes['Bricks']) + max(0, $prev_card['gems'] - $my_attributes['Gems']) + max(0, $prev_card['recruits'] - $my_attributes['Recruits']);

									$play_ratio = ($cost_needed > 0 ) ? ($cost_needed - $cost_missing) / $cost_needed : 1;
									$points-= $misc_config['discard'] * $play_ratio;
								}

								// gain extra points in case poison cards were discarded from hand
								if ($prev_class == 'Common' and !in_array($prev_card['id'], $my_deck->Common) and in_array($cur_card, $my_deck->$card_class))
									$points+= $misc_config['cleanup'];

								// lose points in case poison cards were added to hand
								if ($card_class == 'Common' and !in_array($cur_card, $my_deck->Common) and in_array($prev_card['id'], $my_deck->$prev_class))
									$points-= $misc_config['poison'];
							}

						if (is_array($his_handchanges))
							foreach ($his_handchanges as $card_pos => $cur_card)
							{
								// previous card
								$prev_card = $his_handdata[$card_pos];
								$prev_class = $prev_card['class'];

								// new card
								$card_data = $carddb->GetCard($cur_card);
								$card_class = $card_data->Class;

								// lose points if rare cards were added to opponent's hand
								if ($card_class == 'Rare')
									$points-= $misc_config['summon'];

								// gain extra points if rare cards were discarded from opponent's hand (discard related cards)
								if ($prev_class == 'Rare')
								{
									// determine how soon is opponent going to be able to play this card
									$cost_needed = $prev_card['bricks'] + $prev_card['gems'] + $prev_card['recruits'];
									$cost_missing = max(0, $prev_card['bricks'] - $his_attributes['Bricks']) + max(0, $prev_card['gems'] - $his_attributes['Gems']) + max(0, $prev_card['recruits'] - $his_attributes['Recruits']);

									$play_ratio = ($cost_needed > 0 ) ? ($cost_needed - $cost_missing) / $cost_needed : 1;
									$points+= $misc_config['discard'] * $play_ratio;
								}

								// lose points in case poison cards were discarded from opponent's hand
								if ($prev_class == 'Common' and !in_array($prev_card['id'], $his_deck->Common) and in_array($cur_card, $his_deck->$card_class))
									$points-= $misc_config['cleanup'];

								// gain extra points in case poison cards were added to opponent's hand
								if ($card_class == 'Common' and !in_array($cur_card, $his_deck->Common) and in_array($prev_card['id'], $his_deck->$prev_class))
									$points+= $misc_config['poison'];
							}

						// evaluate how efficiently was the stock spent
						$stock_before = $my_attributes['Bricks'] + $my_attributes['Gems'] + $my_attributes['Recruits'];
						$card_cost = $card['bricks'] + $card['gems'] + $card['recruits'];
						$stock_ratio = ($stock_before > 0) ? $card_cost / $stock_before : 1;
						$points+= $points * $stock_ratio;

						// check victory and loss conditions
						$victory = ($his_after['Tower'] <= 0 or $my_after['Tower'] >= $max_tower or ($my_after['Bricks'] + $my_after['Gems'] + $my_after['Recruits']) >= $res_vic);

						$loss = ($my_after['Tower'] <= 0 or $his_after['Tower'] >= $max_tower or ($his_after['Bricks'] + $his_after['Gems'] + $his_after['Recruits']) >= $res_vic);

						// evaluate victory and loss conditions (avoid draws)
						if ($victory and !$loss) $points = 99999; // choose this choice in case of certain win
						elseif (!$victory and $loss) $points = 0; // never choose this choice in case of certain loss

						$data = array();
						$data['pos'] = $pos;
						$data['mode'] = $i;
						$data['points'] = $points;
						$max_points = max($max_points, $points);

						$choices[] = $data;

						// restore initial data state
						$game->GameData[$ai_player] = clone $mydata_b;
						$game->GameData[$opponent] = clone $hisdata_b;
					}
				}

				if ($max_points > 0) // if no card qualified for playing, discard a card instead (prevent play action)
				{
					$best_choices = array();
					foreach ($choices as $choice)
						if ($choice['points'] == $max_points)
						{
							$cur_choice['pos'] = $choice['pos'];
							$cur_choice['mode'] = $choice['mode'];
							$best_choices[] = $cur_choice;
						}
					$best_choice = $best_choices[array_mt_rand($best_choices)];

					$action = 'play';
					$cardpos = $best_choice['pos'];
					$mode = $best_choice['mode'];
				}
			}

			// choose a card for discarding
			if ($action == 'discard')
			{
				$rares = $rest = $selected = array();
				// split cards into two groups based on rarity (rares and the rest)
				foreach( $my_handdata as $i => $card )
					if ($card['class'] == 'Rare') $rares[$i] = $card;
					else $rest[$i] = $card;

				// don't discard rares unless there is no other choice
				$selected = (count($rest) == 0) ? $rares : $rest;

				// calculate resources missing for each card
				$max = 0;
				$missing_res = array();
				foreach( $selected as $i => $card )
				{
					$missing = max(0,$card['bricks'] - $mydata->Bricks) + max(0,$card['gems'] - $mydata->Gems) + max(0,$card['recruits'] - $mydata->Recruits);
					$missing_res[$i] = $missing;
					$max = max($max, $missing);
				}

				// pick cards with most resources missing to play (sort by card rarity)
				$storage = array("Common" => array(), "Uncommon" => array(), "Rare" => array());

				foreach ($missing_res as $i => $missing)
					if ($missing == $max)
					{
						$card_rarity = $my_handdata[$i]['class'];
						$storage[$card_rarity][] = $i;
					}

				// pick preferably cards with lower rarity, but choose random card within the rarity group
				shuffle($storage['Common']); shuffle($storage['Uncommon']); shuffle($storage['Rare']);
				$storage_temp = array_merge($storage['Common'], $storage['Uncommon'], $storage['Rare']);
				$cardpos = array_shift($storage_temp);

				$mode = 0;
			}

			return array('action' => $action, 'cardpos' => $cardpos, 'mode' => $mode);
		}
	}

	class CChallengeAI extends CGameAI
	{
		private $Name;

		public function __construct(CGame $game)
		{
			parent:: __construct($game);
			$this->Name = $game->AI;
		}

		protected function StaticConfig()
		{
			global $challengesdb;

			// load configuration data from challenges database
			$challenge = $challengesdb->GetChallenge($this->Name);
			if (!$challenge) return parent:: StaticConfig();

			return $challenge->Config;
		}
	}
?>
