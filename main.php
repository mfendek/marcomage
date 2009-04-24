<?php
/*
	MArcomage
*/
?>
<?php
	$querytime_start = microtime(TRUE);
	
	/*	<section: APPLICATION LOGIC>	*/
	
	define("MAX_GAMES", 15);
	define("MESSAGE_LENGTH", 1000);
	define("CHALLENGE_LENGTH", 250);
	define("SYSTEM_NAME", "MArcomage"); // user name for system notification
	define("NUM_THREADS", 4); // number of threads per section in the forum main page
	define("THREADS_PER_PAGE", 30);
	define("POSTS_PER_PAGE", 20);
	define("POST_LENGTH", 4000);
	define("PLAYERS_PER_PAGE", 50);
	define("MESSAGES_PER_PAGE", 15);
	
	require_once('CDatabase.php');
	require_once('CLogin.php');
	require_once('CScore.php');
	require_once('CCard.php');
	require_once('CDeck.php');
	require_once('CGame.php');
	require_once('CNovels.php');
	require_once('CSettings.php');
	require_once('CChat.php');
	require_once('CPlayer.php');
	require_once('CMessage.php');
	require_once('CPost.php');
	require_once('CThread.php');
	require_once('CForum.php');
	require_once('utils.php');
	require_once('Access.php');
	
	$db = new CDatabase("localhost", "arcomage", "", "arcomage");
	
	$logindb = new CLogin($db);
	$scoredb = new CScores($db);
	$carddb = new CCards($db);
	$deckdb = new CDecks($db);
	$gamedb = new CGames($db);
	$chatdb = new CChats($db);
	$settingdb = new CSettings($db);
	$playerdb = new CPlayers($db);
	$messagedb = new CMessage($db);
	$noveldb = new CNovels($db);
	$forum = new CForum($db);

	$current = "Page"; // set a meaningful default

	$session = $logindb->Login();

	do { // dummy scope
	
	if( !$session )
	{
		if (isset($_POST['Login']))
		{
			$current = "Page";
			$information = "Login failed.";
		}
		elseif (isset($_POST['Registration']))
		{
			if (isset($_POST['ReturnToLogin'])) // TODO: rename this
			{
				$current = "Page";
			}
			elseif (!isset($_POST['Register']))
			{
				$current = "Registration";
			}
			elseif (!isset($_POST['NewUsername']) || !isset($_POST['NewPassword']) || !isset ($_POST['NewPassword2']) || trim($_POST['NewUsername']) == '' || trim($_POST['NewPassword']) == '' || trim($_POST['NewPassword2']) == '')
			{
				$current = "Registration";
				$error = "Please enter all required inputs.";
			}
//			elseif (preg_match("(<|>|'|\"|:)", $_POST['NewUsername']))
//			{
//				$current = "Registration";
//				$error = "Name contains invalid characters.";
//			}
			elseif ($_POST['NewPassword'] != $_POST['NewPassword2'])
			{
				$current = "Registration";
				$error = "The two passwords don't match.";
			}
			elseif (($playerdb->GetPlayer($_POST['NewUsername'])) OR (strtolower($_POST['NewUsername']) == strtolower(SYSTEM_NAME)))
			{
				$current = "Registration";
				$error = "That name is already taken.";
			}
			elseif (!$playerdb->CreatePlayer($_POST['NewUsername'], $_POST['NewPassword']))
			{
				$current = "Registration";
				$error = "Failed to register new user.";
			}
			else
			{
				$current = "Page";
				$information = "User registered. You may now log in.";
			}
		}
		else
		{
			$current = "Page";
			$information = "Please log in.";
		}
	}
	else
	{
		// at this point we're logged in
		$player = $playerdb->GetPlayer($session->Username());

		if( !$player )
		{
			$session = false;
			$current = "Page";
			$error = "Failed to load player data! Please report this!";
			break;
		}

		// verify login privilege
		if( !$access_rights[$player->Type()]["login"] )
		{
			$session = false;
			$current = "Page";
			$warning = "This user is not permitted to log in.";
			break;
		}

		date_default_timezone_set("Etc/UTC");
		$db->Query("SET time_zone='Etc/UTC'");

		// login page messages
		if (isset($_POST['Login']))
		{
			$current = "Page"; // new sessions default to here
		}
		else
		
		// navigation bar messages
		if (isset($_POST['Page']))
		{
			$current = "Page";
		}
		elseif (isset($_POST['Forum']))
		{
			$current = "Forum";
		}
		elseif (isset($_POST['Challenges']))
		{
			$current = "Challenges";
		}
		elseif (isset($_POST['Players'])) 
		{
			$current = "Players";
		}
		elseif (isset($_POST['Games']))
		{
			$current = "Games";
		}
		elseif (isset($_POST['Decks']))
		{
			$current = "Decks";
		}
		elseif (isset($_POST['Novels']))
		{
			$current = "Novels";
		}
		elseif (isset($_POST['Settings']))
		{
			$current = "Settings";
		}
		elseif (isset($_POST['Logout']))
		{
			$logindb->Logout($session);
			
			$information = "You have successfully logged out.";
			$current = "Page";
		}
		else
		
		// inner-page messages
		{
			// Explanation of how message passing is done:
			//
			// All requests are retrieved from POST data as <message, value>.
			// Due to the fact that <input> actually has no real 'value' attribute and we can't use <button> (IE incompatibility),
			// we are forced to use the following (insane) workaround: we will encode both message and value as the 'name' attribute.
			//
			// Thanks to an array-like notation, we can store the message and data as
			//   name="message[data]" value="text"
			// which when received will be structured as
			//   $_POST['message'] => Array(['data'] => text)
			// To extract the value of 'data', do array_shift(array_keys($_POST['message']['data'])).
			//
			// Note that 'message' must not contain any non-alphanumeric characters, as browsers escape those to _.
			// Strangely, this constraint does not apply to the 'data' part enclosed in []'s- although escaping is still neccessary.
			// Therefore, make use the provided functionality - postencode() when storing, and postdecode() when extracting data.
			
			foreach($_POST as $message => $value)
			{
				// game-related messages
				if ($message == 'view_game') // Games -> vs. %s
				{
					$gameid = postdecode(array_shift(array_keys($value)));
					$game = $gamedb->GetGame($gameid);
					
					// check if the game exists
					if (!$game) { /*$error = 'No such game!';*/ $current = 'Games'; break; }
					
					// check if this user is allowed to view this game
					if ($player->Name() != $game->Name1() and $player->Name() != $game->Name2()) { $current = 'Games'; break; }
					
					// check if the game is a game in progress (and not a challenge)
					if ($game->State == 'waiting') { /*$error = 'Opponent did not accept the challenge yet!';*/ $current = 'Games'; break; }
					
					// disable re-visiting
					if ( (($player->Name() == $game->Name1()) && ($game->State == 'P1 over')) || (($player->Name() == $game->Name2()) && ($game->State == 'P2 over')) ) { /*$error = 'Game already over.';*/ $current = 'Games'; break; }
					
					$_POST['CurrentGame'] = $gameid;
					$current = "Game";
					break;
				}
				
				if ($message == 'jump_to_game') // Games -> vs. %s
				{	
					$gameid = $_POST['games_list'];	
					$game = $gamedb->GetGame($gameid);
					
					// check if the game exists
					if (!$game) { /*$error = 'No such game!';*/ $current = 'Games'; break; }
					
					// check if this user is allowed to view this game
					if ($player->Name() != $game->Name1() and $player->Name() != $game->Name2()) { $current = 'Games'; break; }
					
					// check if the game is a game in progress (and not a challenge)
					if ($game->State == 'waiting') { /*$error = 'Opponent did not accept the challenge yet!';*/ $current = 'Games'; break; }
					
					// disable re-visiting
					if ( (($player->Name() == $game->Name1()) && ($game->State == 'P1 over')) || (($player->Name() == $game->Name2()) && ($game->State == 'P2 over')) ) { /*$error = 'Game already over.';*/ $current = 'Games'; break; }
					
					$_POST['CurrentGame'] = $gameid;
					$current = "Game";
					break;
				}
				
				if ($message == 'active_game') // Games -> vs. %s
				{
					$list = $gamedb->ListActiveGames($player->Name());
					
					$games_yourturn = array();
					$index = 0;
					
					if (count($list) > 0)
						foreach ($list as $i => $data)
						{
							$game = $gamedb->GetGame2($data['Player1'], $data['Player2']);
							
							if ($game->GameData->Current == $player->Name())
							{
								$games_yourturn[$index] = $game;
								$index++;
							}
						}
					//check if there is an active game
					if ($index == 0) { /*$error = 'No games your turn!';*/ $current = 'Games'; break; }
					
					$game = $games_yourturn[0];
					if ($index > 1)
						foreach ($games_yourturn as $i => $cur_game)
						{
							if ($_POST['CurrentGame'] == $cur_game->ID())
							{
								$game = $games_yourturn[(($i + 1) % $index)];//wrap around
								break;
							}	
						}
					
					// check if the game exists
					if (!$game) { /*$error = 'No such game!';*/ $current = 'Games'; break; }
					
					// check if this user is allowed to view this game
					if ($player->Name() != $game->Name1() and $player->Name() != $game->Name2()) { $current = 'Games'; break; }
					
					// check if the game is a game in progress (and not a challenge)
					if ($game->State == 'waiting') { /*$error = 'Opponent did not accept the challenge yet!';*/ $current = 'Games'; break; }
					
					// disable re-visiting
					if ( (($player->Name() == $game->Name1()) && ($game->State == 'P1 over')) || (($player->Name() == $game->Name2()) && ($game->State == 'P2 over')) ) { /*$error = 'Game already over.';*/ $current = 'Games'; break; }
					
					$_POST['CurrentGame'] = $game->ID();
					$current = "Game";
					break;
				}
				
				if ($message == 'view_deck')
				{	// show deck a player is currently playing with
					$gameid = $_POST['CurrentGame'];
					$game = $gamedb->GetGame($gameid);
					
					// check if the game exists
					if (!$game) { /*$error = 'No such game!';*/ $current = 'Games'; break; }
					
					// check if this user is allowed to perform game actions
					if ($player->Name() != $game->Name1() and $player->Name() != $game->Name2()) { $current = 'Game'; break; }
					
					$current = 'Deck_view';
					break;
				}
				
				if ($message == 'send_message')
				{	// message contains no data itself
					$msg = $_POST['ChatMessage'];
					
					$gameid = $_POST['CurrentGame'];
					$game = $gamedb->GetGame($gameid);
					
					// check if the game exists
					if (!$game) { /*$error = 'No such game!';*/ $current = 'Games'; break; }
					
					// check if this user is allowed to send messages in this game
					if ($player->Name() != $game->Name1() and $player->Name() != $game->Name2()) { $current = 'Game'; break; }
					
					// do not post empty messages (prevents accidental send)
					if (trim($msg) == '') { /*$error = 'You can't send empty chat messages.';*/ $current = 'Game'; break; }
					
					// check access rights
					if (!$access_rights[$player->Type()]["chat"]) { $error = 'Access denied.'; $current = 'Game'; break; }
					
					$chatdb->SaveChatMessage($game->ID(), $msg, $player->Name());
					
					$current = 'Game';
					break;
				}
				
				if ($message == 'discard_card') // Games -> vs. %s -> Discard
				{
					$cardpos = postdecode(array_shift(array_keys($value)));
					
					$gameid = $_POST['CurrentGame'];
					$game = $gamedb->GetGame($gameid);
					
					// check if the game exists
					if (!$game) { /*$error = 'No such game!';*/ $current = 'Games'; break; }
					
					// check if this user is allowed to perform game actions
					if ($player->Name() != $game->Name1() and $player->Name() != $game->Name2()) { $current = 'Game'; break; }
					
					// the rest of the checks are done internally
					$result = $game->PlayCard($player->Name(), $cardpos, 0, 'discard');
					
					if ($result == 'OK')
					{
						$game->SaveGame();
						$information = "You have discarded a card.";
					}
					/*else $error = $result;*/
					
					$current = "Game";
					break;
				}
				
				if ($message == 'play_card') // Games -> vs. %s -> Play
				{
					$cardpos = array_shift(array_keys($value));
					$mode = (isset($_POST['card_mode']) and isset($_POST['card_mode'][$cardpos])) ? $_POST['card_mode'][$cardpos] : 0;
					
					$gameid = $_POST['CurrentGame'];
					$game = $gamedb->GetGame($gameid);
					
					// check if the game exists
					if (!$game) { /*$error = 'No such game!';*/ $current = 'Games'; break; }
					
					// check if this user is allowed to perform game actions
					if ($player->Name() != $game->Name1() and $player->Name() != $game->Name2()) { $current = 'Game'; break; }
					
					// the rest of the checks are done internally
					$result = $game->PlayCard($player->Name(), $cardpos, $mode, 'play');
					
					if ($result == 'OK')
					{
						$game->SaveGame();

						if ($game->State == 'finished')
						{
							$player1 = $game->Name1();
							$player2 = $game->Name2();

							// update score
							$score1 = $scoredb->GetScore($player1);
							$score2 = $scoredb->GetScore($player2);
							$data = &$game->GameData;
							
							if ($data->Winner == $player1) { $score1->ScoreData->Wins++; $score2->ScoreData->Losses++; }
							elseif ($data->Winner == $player2) { $score2->ScoreData->Wins++; $score1->ScoreData->Losses++; }
							else {$score1->ScoreData->Draws++; $score2->ScoreData->Draws++; }
							
							$score1->SaveScore();
							$score2->SaveScore();

							// send battle report message
							$opponent = $playerdb->GetPlayer(($player1 != $player->Name()) ? $player1 : $player2);
							$opponent_rep = $opponent->GetSetting("Reports");
							$player_rep = $player->GetSetting("Reports");
							$outcome = $game->GameData->Outcome;

							$messagedb->SendBattleReport($player->Name(), $opponent->Name(), $player_rep, $opponent_rep, $outcome);
						}
						else
						{
							$information = "You have played a card.";
						}
					}
					/*else $error = $result;*/
					
					$current = "Game";
					break;
				}
				
				if ($message == 'surrender') // Games -> vs. %s -> Surrender
				{
					// only symbolic functionality... rest is handled below
					$current = "Game";
					break;
				}
				
				if ($message == 'confirm_surrender') // Games -> vs. %s -> Surrender -> Confirm surrender
				{
					$gameid = $_POST['CurrentGame'];
					$game = $gamedb->GetGame($gameid);
					
					// check if the game exists
					if (!$game) { /*$error = 'No such game!';*/ $current = 'Games'; break; }
					
					// check if this user is allowed to surrender in this game
					if ($player->Name() != $game->Name1() and $player->Name() != $game->Name2()) { $current = 'Game'; break; }
					
					$result = $game->SurrenderGame($player->Name());
					
					if ($result == 'OK')
					{
						$data = &$game->GameData;
						$score = $scoredb->GetScore($player->Name());
						$score->ScoreData->Losses++;
						$score->SaveScore();
						
						$score = $scoredb->GetScore($data->Winner);
						$score->ScoreData->Wins++;
						$score->SaveScore();

						$player1 = $game->Name1();
						$player2 = $game->Name2();

						// send battle report message
						$opponent = $playerdb->GetPlayer(($player1 != $player->Name()) ? $player1 : $player2);
						$opponent_rep = $opponent->GetSetting("Reports");
						$player_rep = $player->GetSetting("Reports");
						$outcome = $game->GameData->Outcome;

						$messagedb->SendBattleReport($player->Name(), $opponent->Name(), $player_rep, $opponent_rep, $outcome);
					}
					/*else $error = $result;*/
					
					$current = "Game";
					break;
				}
				
				if ($message == 'abort_game') // Games -> vs. %s -> Abort game
				{
					// an option to end the game without hurting your score
					// applies only to games against 'dead' players (abandoned games)
					$gameid = $_POST['CurrentGame'];
					$game = $gamedb->GetGame($gameid);
					
					// check if the game exists
					if (!$game) { /*$error = 'No such game!';*/ $current = 'Games'; break; }
					
					// check if this user is allowed to abort this game
					if ($player->Name() != $game->Name1() and $player->Name() != $game->Name2()) { $current = 'Game'; break; }
					
					// only allow aborting abandoned games
					if (!$playerdb->isDead($game->Name1()) and !$playerdb->isDead($game->Name2())) { /*$error = 'Action not allowed!';*/ $current = 'Game'; break; }
					
					$result = $game->AbortGame($player->Name());
					
					if ($result == 'OK')
					{
						$player1 = $game->Name1();
						$player2 = $game->Name2();

						// send battle report message
						$opponent = $playerdb->GetPlayer(($player1 != $player->Name()) ? $player1 : $player2);
						$opponent_rep = $opponent->GetSetting("Reports");
						$player_rep = $player->GetSetting("Reports");
						$outcome = $game->GameData->Outcome;

						$messagedb->SendBattleReport($player->Name(), $opponent->Name(), $player_rep, $opponent_rep, $outcome);
					}
					/*else $error = $result;*/
					
					$current = "Game";
					break;
				}
				
				if ($message == 'finish_game') // Games -> vs. %s -> Finish game
				{
					// an option to end the game when opponent refuses to play
					// applies only to games against non-'dead' players, when opponet didn't take action for more then 3 weeks
					$gameid = $_POST['CurrentGame'];
					$game = $gamedb->GetGame($gameid);
					$data = &$game->GameData;
					
					// check if the game exists
					if (!$game) { /*$error = 'No such game!';*/ $current = 'Games'; break; }
					
					// check if this user is allowed to abort this game
					if ($player->Name() != $game->Name1() and $player->Name() != $game->Name2()) { $current = 'Game'; break; }
					
					// only allow finishing active games
					if ($playerdb->isDead($game->Name1()) or $playerdb->isDead($game->Name2())) { /*$error = 'Action not allowed!';*/ $current = 'Game'; break; }
					
					// and only if the abort criteria are met
					if( time() - $data->Timestamp < 60*60*24*7*3 || $data->Current == $player->Name() ) { /*$error = 'Action not allowed!';*/ $current = 'Game'; break; }
					
					$result = $game->FinishGame($player->Name());
					
					if ($result == 'OK')
					{
						$player1 = $game->Name1();
						$player2 = $game->Name2();
						$score1 = $scoredb->GetScore($player1);
						$score2 = $scoredb->GetScore($player2);
						
						if ($data->Winner == $player1) { $score1->ScoreData->Wins++; $score2->ScoreData->Losses++; }
						elseif ($data->Winner == $player2) { $score2->ScoreData->Wins++; $score1->ScoreData->Losses++; }
						else {$score1->ScoreData->Draws++; $score2->ScoreData->Draws++; }
						
						$score1->SaveScore();
						$score2->SaveScore();

						// send battle report message
						$opponent = $playerdb->GetPlayer(($player1 != $player->Name()) ? $player1 : $player2);
						$opponent_rep = $opponent->GetSetting("Reports");
						$player_rep = $player->GetSetting("Reports");
						$outcome = $game->GameData->Outcome;

						$messagedb->SendBattleReport($player->Name(), $opponent->Name(), $player_rep, $opponent_rep, $outcome);
					}
					/*else $error = $result;*/
					
					$current = "Game";
					break;
				}
				
				if ($message == 'Confirm') // Games -> vs. %s -> Leave the game
				{
					$gameid = $_POST['CurrentGame'];
					$game = $gamedb->GetGame($gameid);
					
					// check if the game exists
					if (!$game) { /*$error = 'No such game!';*/ $current = 'Games'; break; }
					
					// disable re-visiting (or the player would set this twice >_>)
					if ( (($player->Name() == $game->Name1()) && ($game->State == 'P1 over')) || (($player->Name() == $game->Name2()) && ($game->State == 'P2 over')) ) { $current = 'Games'; break; }
					
					// only allow if the game is over (stay if not)
					if ($game->State == 'in progress') { $current = "Game"; break; }
					
					if ($game->State == 'finished')
					{
						// we are the first one to acknowledge
						$game->State = ($game->Name1() == $player->Name()) ? 'P1 over' : 'P2 over';
						$game->SaveGame();
						// inform other player about leaving the game
						$chatdb->SaveChatMessage($game->ID(), "has left the game", $player->Name());
					}
					else // 'P1 over' or 'P2 over'
					{
						// the other player has already acknowledged
						$gamedb->DeleteGame($game->ID());
						$chatdb->DeleteChat($game->ID());
					}
					
					$current = "Games";
					break;
				}
				// end game-related messages
				
				// challenge-related messages
				if ($message == 'accept_challenge') // Challenges -> Accept
				{
					$opponent = array_shift(array_keys($value));
					$deckname = (isset($_POST['AcceptDeck']) and isset($_POST['AcceptDeck'][$opponent])) ? $_POST['AcceptDeck'][$opponent] : '(null)';
					$opponent = postdecode($opponent);
					$deckname = postdecode($deckname);
					
					$deck = $deckdb->GetDeck($player->Name(), $deckname);
					
					// check if such deck exists
					if (!$deck) { $error = 'No such deck!'; $current = 'Players'; break; }
					
					// check if the deck is ready (all 45 cards)
					if (!$deck->isReady()) { $error = 'This deck is not yet ready for gameplay!'; $current = 'Decks'; break; }
					
					// check if such opponent exists
					if (!$playerdb->GetPlayer($opponent)) { $error = 'No such player!'; $current = 'Players'; break; }
					
					$game = $gamedb->GetGame2($opponent, $player->Name());
					
					// check if the challenge exists
					if (!$game) { $error = 'No such challenge!'; $current = 'Players'; break; }
					
					// check if the game is a challenge and not an active game
					if ($game->State != 'waiting') { $error = 'Game already in progress!'; $current = 'Players'; break; }
					
					// the player may never have more than MAX_GAMES games at once, even potential ones (challenges)
					if (count($gamedb->ListActiveGames($player->Name())) + count($gamedb->ListChallengesFrom($player->Name())) >= MAX_GAMES)
					{
						$error = 'You may only have '.MAX_GAMES.' simultaneous games at once (this also covers your challenges).'; $current = 'Players'; break;
					}
					
					// check access rights
					if (!$access_rights[$player->Type()]["accept_challenges"]) { $error = 'Access denied.'; $current = 'Challenges'; break; }
					
					// accept the challenge
					$game->GameData->Player[$player->Name()]->Deck = $deck->DeckData;
					$game->StartGame();
					$game->SaveGame();
					$messagedb->CancelChallenge($game->ID());

					if ($playerdb->GetPlayer($opponent)->GetSetting("Reports") == "yes")
						$messagedb->SendMessage("MArcomage", $opponent, "Challenge accepted", 'Player '.$player->Name().' has accepted your challenge.');
					
					$information = 'You have accepted a challenge from '.htmlencode($opponent).'.';
					$current = 'Challenges';
					break;
				}
				
				if ($message == 'reject_challenge') // Challenges -> Reject
				{
					//FIXME: uses names for game identification
					
					$opponent = postdecode(array_shift(array_keys($value)));
					
					// check if such opponent exists
					if (!$playerdb->GetPlayer($opponent)) { $error = 'Player '.htmlencode($opponent).' does not exist!'; $current = 'Players'; break; }
					
					$game = $gamedb->GetGame2($opponent, $player->Name());
					
					// check if the challenge exists
					if (!$game) { $error = 'No such challenge!'; $current = 'Players'; break; }
					
					// check if the game is a challenge (and not a game in progress)
					if ($game->State != 'waiting') { $error = 'Game already in progress!'; $current = 'Players'; break; }
					
					// delete t3h challenge/game entry
					$gamedb->DeleteGame2($opponent, $player->Name());
					$chatdb->DeleteChat($game->ID());
					$messagedb->CancelChallenge($game->ID());

					if ($playerdb->GetPlayer($opponent)->GetSetting("Reports") == "yes")
						$messagedb->SendMessage("MArcomage", $opponent, "Challenge rejected", 'Player '.$player->Name().' has rejected your challenge.');
					
					$information = 'You have rejected a challenge.';
					$current = 'Challenges';
					break;
				}
				
				if ($message == 'prepare_challenge') // Players -> Challenge this user
				{
					// check access rights
					if (!$access_rights[$player->Type()]["send_challenges"]) { $error = 'Access denied.'; $current = 'Players'; break; }
				
					$_POST['cur_player'] = postdecode(array_shift(array_keys($value)));
				
					// this is only used to assist the function below
					// do not remove two-step challenging mechanism, we will make use of it when challenges will be transformed to messages
					$current = 'Profile';
					break;
				}
				
				if ($message == 'send_challenge') // Players -> Send challenge
				{
					//FIXME: uses names for game identification
					
					// check access rights
					if (!$access_rights[$player->Type()]["send_challenges"]) { $error = 'Access denied.'; $current = 'Players'; break; }
					
					$opponent = postdecode(array_shift(array_keys($value)));
					$deckname = isset($_POST['ChallengeDeck']) ? postdecode($_POST['ChallengeDeck']) : '(null)';
					
					$_POST['cur_player'] = $opponent;
					$deck = $deckdb->GetDeck($player->Name(), $deckname);
					
					// check if such deck exists
					if (!$deck) { $error = 'Deck '.$deckname.' does not exist!'; $current = 'Profile'; break; }
					
					// check if the deck is ready (all 45 cards)
					if (!$deck->isReady()) { $error = 'Deck '.$deckname.' is not yet ready for gameplay!'; $current = 'Profile'; break; }
					
					// check if such opponent exists
					if (!$playerdb->GetPlayer($opponent)) { $error = 'Player '.htmlencode($opponent).' does not exist!'; $current = 'Profile'; break; }
					
					// check if that opponent was already challenged, or if there is a game already in progress
					if ($gamedb->GetGame2($player->Name(), $opponent)) { $error = 'You are already playing against '.htmlencode($opponent).'!'; $current = 'Games'; break; }
					
					// check if you are within the MAX_GAMES limit
					if (count($gamedb->ListActiveGames($player->Name())) + count($gamedb->ListChallengesFrom($player->Name())) + count($gamedb->ListChallengesTo($player->Name())) >= MAX_GAMES) { $error = 'Too many games / challenges! Please resolve some.'; $current = 'Challenges'; break; }
					
					// check challenge text length
					if (strlen($_POST['Content']) > CHALLENGE_LENGTH) { $error = "Message too long"; $current = "Details"; break; }
					
					// create a new challenge
					$game = $gamedb->CreateGame($player->Name(), $opponent, $deck->DeckData);
					if (!$game) { $error = 'Failed to create new game!'; $current = 'Profile'; break; }
					
					$res = $messagedb->SendChallenge($player->Name(), $opponent, $_POST['Content'], $game->ID());
					if (!$res) { $error = 'Failed to create new challenge!'; $current = 'Profile'; break; }
					
					$information = 'You have challenged '.htmlencode($opponent).'. Waiting for reply.';
					$current = 'Profile';
					break;
				}
				
				if ($message == 'withdraw_challenge') // Players -> Cancel
				{
					//FIXME: uses names for game identification
					
					$opponent = postdecode(array_shift(array_keys($value)));
					$_POST['cur_player'] = $opponent;
					
					// check if such opponent exists
					if (!$playerdb->GetPlayer($opponent)) { $error = 'Player '.htmlencode($opponent).' does not exist!'; $current = 'Profile'; break; }
					
					$game = $gamedb->GetGame2($player->Name(), $opponent);
					
					// check if the challenge exists
					if (!$game) { $error = 'No such challenge!'; $current = 'Profile'; break; }
					
					// check if the game is a a challenge (and not a game in progress)
					if ($game->State != 'waiting') { $error = 'Game already in progress!'; $current = 'Profile'; break; }
					
					// delete t3h challenge/game entry
					$gamedb->DeleteGame2($player->Name(), $opponent);
					$chatdb->DeleteChat($game->ID());
					$messagedb->CancelChallenge($game->ID());
					
					$information = 'You have withdrawn a challenge.';
					$current = 'Profile';
					break;
				}
				
				if ($message == 'withdraw_challenge2') // Challenges -> Cancel
				{
					//FIXME: uses names for game identification
					
					$opponent = postdecode(array_shift(array_keys($value)));
					$_POST['cur_player'] = $opponent;
					
					// check if such opponent exists
					if (!$playerdb->GetPlayer($opponent)) { $error = 'Player '.htmlencode($opponent).' does not exist!'; $current = 'Profile'; break; }
					
					$game = $gamedb->GetGame2($player->Name(), $opponent);
					
					// check if the challenge exists
					if (!$game) { $error = 'No such challenge!'; $current = 'Challenges'; break; }
					
					// check if the game is a a challenge (and not a game in progress)
					if ($game->State != 'waiting') { $error = 'Game already in progress!'; $current = 'Challenges'; break; }
					
					// delete t3h challenge/game entry
					$gamedb->DeleteGame2($player->Name(), $opponent);
					$chatdb->DeleteChat($game->ID());
					$messagedb->CancelChallenge($game->ID());
					
					$information = 'You have withdrawn a challenge.';
					$_POST['outgoing'] = "outgoing"; // stay in "Outgoing" subsection
					$current = 'Challenges';
					break;
				}
				
				if ($message == 'incoming') // view challenges to player
				{
					$current = 'Challenges';
					break;
				}
				
				if ($message == 'outgoing') // view challenges from player
				{
					$current = 'Challenges';
					break;
				}
				// end challenge-related messages
				
				// message-related messages
				if ($message == 'message_details') // view message
				{
					$messageid = array_shift(array_keys($value));
					
					$message = $messagedb->GetMessage($messageid, $player->Name());
					
					if (!$message) { $error = "No such message!"; $current = "Challenges"; break; }
					
					$current = 'Message_details';
					break;
				}
				
				if ($message == 'message_retrieve') // retrieve message (even deleted one)
				{				
					$messageid = array_shift(array_keys($value));
					
					// check access rights
					if (!$access_rights[$player->Type()]["see_all_messages"]) { $error = 'Access denied.'; $current = 'Challenges'; break; }
						
					$message = $messagedb->RetrieveMessage($messageid);
					
					if (!$message) { $error = "No such message!"; $current = "Challenges"; break; }
					
					$current = 'Message_details';
					break;
				}
				
				if ($message == 'message_delete') // delete message
				{
					$messageid = array_shift(array_keys($value));
					
					$message = $messagedb->GetMessage($messageid, $player->Name());
					
					if (!$message) { $error = "No such message!"; $current = "Challenges"; break; }
					
					$current = 'Message_details';
					break;
				}
				
				if ($message == 'message_delete_confirm') // delete message confirmation
				{
					$messageid = array_shift(array_keys($value));
					
					$message = $messagedb->DeleteMessage($messageid, $player->Name());
					
					if (!$message) { $error = "No such message!"; $current = "Challenges"; break; }
					
					$information = "Message deleted";
					
					$current = 'Challenges';
					break;
				}
				
				if ($message == 'message_cancel') // cancel new message creation
				{
					$current = 'Challenges';
					break;
				}
				
				if ($message == 'message_send') // send new message
				{
					$recipient = $_POST['Recipient'];
					$author = $_POST['Author'];
					
					// check access rights
					if (!$access_rights[$player->Type()]["messages"]) { $error = 'Access denied.'; $current = 'Challenges'; break; }
				
					if ((trim($_POST['Subject']) == "") AND (trim($_POST['Content']) == "")) { $error = "No message input specified"; $current = "Message_new"; break; }
					
					if (strlen($_POST['Content']) > MESSAGE_LENGTH) { $error = "Message too long"; $current = "Message_new"; break; }
				
					$message = $messagedb->SendMessage($_POST['Author'], $_POST['Recipient'], $_POST['Subject'], $_POST['Content']);
					
					if (!$message) { $error = "Failed to send message"; $current = "Challenges"; break; }
					
					$_POST['CurrentLocation'] = "sent_mail";
					$information = "Message sent";
					
					$current = 'Challenges';
					break;
				}
				
				if ($message == 'message_create') // go to new message screen
				{
					// check access rights
					if (!$access_rights[$player->Type()]["messages"]) { $error = 'Access denied.'; $current = 'Challenges'; break; }
				
					$recipient = postdecode(array_shift(array_keys($value)));
					$author = $player->Name();
					
					$current = 'Message_new';
					break;
				}
				
				if ($message == 'system_notification') // go to new message screen to write system notification
				{
					// check access rights
					if (!$access_rights[$player->Type()]["system_notification"]) { $error = 'Access denied.'; $current = 'Players'; break; }
				
					$recipient = postdecode(array_shift(array_keys($value)));
					$author = SYSTEM_NAME;
																							
					$current = 'Message_new';
					break;
				}
				
				if ($message == 'inbox') // view messages to player
				{
					$_POST['CurrentLocation'] = "inbox";
					$_POST['CurrentFilterDate'] = "none";
					$_POST['CurrentFilterName'] = "none";
					$_POST['CurrentMesPage'] = 0;
					unset($_POST['CurrentCond']);
					unset($_POST['CurrentOrd']);
					$current = 'Challenges';
					break;
				}
				
				if ($message == 'sent_mail') // view messages from player
				{
					$_POST['CurrentLocation'] = "sent_mail";
					$_POST['CurrentFilterDate'] = "none";
					$_POST['CurrentFilterName'] = "none";
					$_POST['CurrentMesPage'] = 0;
					unset($_POST['CurrentCond']);
					unset($_POST['CurrentOrd']);
					$current = 'Challenges';
					break;
				}
				
 				if ($message == 'all_mail') // view messages from player
 				{
 					// check access rights
 					if (!$access_rights[$player->Type()]["see_all_messages"]) { $error = 'Access denied.'; $current = 'Challenges'; break; }
					$_POST['CurrentLocation'] = "all_mail";
 					$_POST['CurrentFilterDate'] = "none";
					$_POST['CurrentFilterName'] = "none";
					$_POST['CurrentMesPage'] = 0;
					unset($_POST['CurrentCond']);
					unset($_POST['CurrentOrd']);
 					$current = 'Challenges';
 					break;
 				}
				
				$temp = array("asc" => "ASC", "desc" => "DESC");
				foreach($temp as $type => $order_val)
				{
					if ($message == 'mes_ord_'.$type) // select ascending or descending order in message list
					{
						$_POST['CurrentCond'] = array_shift(array_keys($value));
						$_POST['CurrentOrd'] = $order_val;
						
						$current = "Challenges";
						
						break;
					}
				}
				
				if ($message == 'message_filter') // use filter
				{
					$_POST['CurrentFilterDate'] = $_POST['date_filter'];
					$_POST['CurrentFilterName'] = ((isset($_POST['name_filter'])) ? postdecode($_POST['name_filter']) : "none");
					$_POST['CurrentMesPage'] = 0;
					
					$current = 'Challenges';
					break;
				}
				
				if ($message == 'select_page_mes') // Messages -> select page (previous and next button)
				{
					$_POST['CurrentMesPage'] = array_shift(array_keys($value));
					$current = "Challenges";
					
					break;
				}
				
				if ($message == 'Jump_messages') // Messages -> select page (Jump to page)
				{
					$_POST['CurrentMesPage'] = $_POST['jump_to_page'];
					$current = "Challenges";
					
					break;
				}
				
				if ($message == 'Delete_mass') // Messages -> delete selected messages
				{
					$deleted_messages = array();
					
					for ($i = 1; $i<= MESSAGES_PER_PAGE; $i++)
						if (isset($_POST['Mass_delete_'.$i]))
						{
							$current_message = array_shift(array_keys($_POST['Mass_delete_'.$i]));
							array_push($deleted_messages, $current_message);
						}
					
					if (count($deleted_messages) > 0)
					{
						$result = $messagedb->MassDeleteMessage($deleted_messages, $player->Name());
						if (!$result) { $error = "Failed to delete messages"; $current = "Challenges"; break; }
						
						$information = "Messages deleted";
					}
					else $warning = "No messages selected";
					
					$current = "Challenges";
					break;
				}
				// end message-related messages
				
				// view user details
				if ($message == 'user_details') // Players -> User details
				{
					$opponent = postdecode(array_shift(array_keys($value)));
					
					$_POST['Profile'] = $opponent;
					$current = 'Profile';
					break;
				}
				
				if ($message == 'change_access') // Players -> User details -> Change access rights
				{
					$opponent = postdecode(array_shift(array_keys($value)));
					
					$_POST['Profile'] = $opponent;
					
					// check access rights
					if (!$access_rights[$player->Type()]["change_rights"]) { $error = 'Access denied.'; $current = 'Profile'; break; }
										
					$target = $playerdb->GetPlayer($opponent);
					$target->ChangeAccessRights($_POST['new_access']);
					
					$information = 'Access rights changed.';
								
					$current = 'Profile';
					break;
				}
				// end view user details
				
				// deck-related messages
				if ($message == 'modify_deck') // Decks -> Modify this deck
				{
					$deckname = postdecode(array_shift(array_keys($value)));
					
					$_POST['CurrentDeck'] = $deckname;
					$_POST['ClassFilter'] = 'Common';
					$_POST['CostFilter'] = 'none';
					$_POST['KeywordFilter'] = 'none';
					$_POST['AdvancedFilter'] = 'none';
					$_POST['SupportFilter'] = 'none';
					$current = 'Deck_edit';
					break;
				}
				
				if ($message == 'add_card') // Decks -> Modify this deck -> Take
				{
					$cardid = (int)postdecode(array_shift(array_keys($value)));
					
					//download deck, download card
					$deckname = $_POST['CurrentDeck'];
					$deck = $player->GetDeck($deckname);
					$card = $carddb->GetCard($cardid);
					$classname = $card->CardData->Class;
					
					$current = 'Deck_edit';
					
					// verify if the card id is valid
					if ($classname == 'None') break;
					
					// check if the card isn't already there
					$pos = array_search($cardid, $deck->DeckData->$classname);
					if ($pos !== false) break;
					
					// check if the deck's corresponding section isn't already full
					if ($deck->DeckData->Count($classname) == 15) break;
					
					// success, find an empty spot in the section, write the cardid there and upload the deck with new values
					$pos = array_search(0, $deck->DeckData->$classname);
					
					// workaround: PHP interpretes $classname[$pos] as a character in the string $classname instead of an element of the array {$classname}[]
					//$deck->DeckData->$classname[$pos] = $cardid;
					$aaargh = &$deck->DeckData->$classname;
					$aaargh[$pos] = $cardid;
					
					$deck->SaveDeck();
					break;
				}
				
				if ($message == 'set_tokens') // Decks -> Set tokens
				{
					$deckname = $_POST['CurrentDeck'];
					$deck = $player->GetDeck($deckname);
					
					$current = 'Deck_edit';
					
					// read tokens from inputs
					$tokens = array();
					foreach ($deck->DeckData->Tokens as $token_index => $token)
						$tokens[$token_index] = $_POST['Token'.$token_index];
					
					$length = count($tokens);
					
					// remove empty tokens
					$tokens = array_diff($tokens, array('none'));
					
					// remove duplicates
					$tokens = array_unique($tokens);
					$tokens = array_pad($tokens, $length, 'none');
					
					// sort tokens, add consistent keys
					$i = 1;
					$sorted_tokens = array();
					foreach ($tokens as $token)
					{
						$sorted_tokens[$i] = $token;
						$i++;
					}
					
					// save token data
					$deck->DeckData->Tokens = $sorted_tokens;
					
					$deck->SaveDeck();
					
					break;
				}
				
				if ($message == 'reset_tokens') // Decks -> Reset tokens
				{
					$deckname = $_POST['CurrentDeck'];
					$deck = $player->GetDeck($deckname);
					
					$current = 'Deck_edit';
					
					$deck->DeckData->Tokens = array(1 => 'none', 'none', 'none');
					
					$deck->SaveDeck();
					
					break;
				}
				
				if ($message == 'auto_tokens') // Decks -> Assign tokens automatically
				{
					$deckname = $_POST['CurrentDeck'];
					$deck = $player->GetDeck($deckname);
					
					$current = 'Deck_edit';
					
					$tokens_temp = $deck->CalculateKeywords();
					$tokens = array();
					
					// adjust array keys
					foreach ($tokens_temp as $key => $value) $tokens[$key + 1] = $value;
					
					$deck->DeckData->Tokens = $tokens;
					
					$deck->SaveDeck();
					
					break;
				}
				
				if ($message == 'filter') // Decks -> Modify this deck -> Apply filters
				{
					$_POST['CostFilter'] = $_POST['selected_cost'];
					$_POST['KeywordFilter'] = $_POST['selected_keyword'];
					$_POST['ClassFilter'] = $_POST['selected_rarity'];
					$_POST['AdvancedFilter'] = $_POST['advanced_filter'];
					$_POST['SupportFilter'] = $_POST['support_filter'];
                    
					$current = 'Deck_edit';
					
					break;
				}
				
				if ($message == 'return_card') // Decks -> Modify this deck -> Return
				{
					$cardid = (int)postdecode(array_shift(array_keys($value)));
					
					// download deck, download card
					$deckname = $_POST['CurrentDeck'];
					$deck = $player->GetDeck($deckname);
					$card = $carddb->GetCard($cardid);
					
					$current = 'Deck_edit';
					
					$cardclass = $card->CardData->Class;
					
					// check if the card is present in the deck
					$pos = array_search($cardid, $deck->DeckData->$cardclass);
					if ($pos === false) break;
					
					// success, write a 0, and upload the deck with new values
					
					// can't do this nicely, see the previous comment
					//$deck->DeckData->$cardclass[$pos] = 0;
					$aaargh = &$deck->DeckData->$cardclass;
					$aaargh[$pos] = 0;
					
					$deck->SaveDeck();
					break;
				}
				
				if ($message == 'reset_deck_prepare') // Decks -> Reset
				{
					// only symbolic functionality... rest is handled below
					$deckname = $_POST['CurrentDeck'];
					$current = 'Deck_edit';
					
					break;
				}
				
				if ($message == 'reset_deck_confirm') // Decks -> Modify this deck -> Confirm reset
				{
					$deckname = $_POST['CurrentDeck'];
					$deck = $player->GetDeck($deckname);
					
					$current = 'Deck_edit';
					
					$deck->DeckData->Common   = array(1=>0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
					$deck->DeckData->Uncommon = array(1=>0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
					$deck->DeckData->Rare     = array(1=>0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
					
					$deck->DeckData->Tokens = array(1 => 'none', 'none', 'none');
					
					$deck->SaveDeck();
					
					break;
				}
				
				if ($message == 'randomize_deck_prepare') // Decks -> Randomize
				{
					// only symbolic functionality... rest is handled below
					$deckname = $_POST['CurrentDeck'];
					$current = 'Deck_edit';
					
					break;
				}
				
				if ($message == 'randomize_deck_confirm') // Decks -> Modify this deck -> Confirm randomize
				{
					$deckname = $_POST['CurrentDeck'];
					$deck = $player->GetDeck($deckname);
					
					$current = 'Deck_edit';
					
					$common_cards = $carddb->GetList("Common");
					$uncommon_cards = $carddb->GetList("Uncommon");
					$rare_cards = $carddb->GetList("Rare");
					
					Shuffle($common_cards); //shuffle will create an array with index starting from 0, but we need to start from 1
					Shuffle($uncommon_cards);
					Shuffle($rare_cards);
					
					$common_cards = array_slice($common_cards,0,15);
					$uncommon_cards = array_slice($uncommon_cards, 0,15);
					$rare_cards = array_slice($rare_cards,0,15);
					
					$c_cards = array();//we make this whole mess only because we use arrays indexed starting with 1 and all PHP functions returns arrays starting with 0
					$u_cards = array();
					$r_cards = array();
					
					for ($i = 1; $i <= 15; $i++) { $c_cards[$i] = $common_cards[$i-1]; }
					for ($i = 1; $i <= 15; $i++) { $u_cards[$i] = $uncommon_cards[$i-1]; }
					for ($i = 1; $i <= 15; $i++) { $r_cards[$i] = $rare_cards[$i-1]; }
					
					$deck->DeckData->Common = $c_cards;
					$deck->DeckData->Uncommon = $u_cards;
					$deck->DeckData->Rare = $r_cards;
					
					$deck->SaveDeck();
					
					break;
				}
				
				if ($message == 'finish_deck') // Decks -> Modify this deck -> Finish
				{
					$deckname = $_POST['CurrentDeck'];
					$deck = $player->GetDeck($deckname);
					
					$current = 'Deck_edit';
					
					$common_cards = $carddb->GetList("Common");
					$uncommon_cards = $carddb->GetList("Uncommon");
					$rare_cards = $carddb->GetList("Rare");
					
					// array_diff ensures that cards already in the deck won't be added again
					$common_cards = array_diff($common_cards, $deck->DeckData->Common);
					$uncommon_cards = array_diff($uncommon_cards, $deck->DeckData->Uncommon);
					$rare_cards = array_diff($rare_cards, $deck->DeckData->Rare);
					
					Shuffle($common_cards); //shuffle will create an array with index starting from 0, but we need to start from 1
					Shuffle($uncommon_cards);
					Shuffle($rare_cards);
					
					$common_cards = array_slice($common_cards,0,15);
					$uncommon_cards = array_slice($uncommon_cards, 0,15);
					$rare_cards = array_slice($rare_cards,0,15);
					
					for ($i = 1; $i <= 15; $i++)
					{
						if ($deck->DeckData->Common[$i] == 0) $deck->DeckData->Common[$i] = $common_cards[$i-1];
						if ($deck->DeckData->Uncommon[$i] == 0) $deck->DeckData->Uncommon[$i] = $uncommon_cards[$i-1];
						if ($deck->DeckData->Rare[$i] == 0) $deck->DeckData->Rare[$i] = $rare_cards[$i-1];
					}
					
					$deck->SaveDeck();
					
					break;
				}
				
				if ($message == 'rename_deck') // Decks -> Modify this deck -> Rename
				{
					$curname = $_POST['CurrentDeck'];
					$newname = $_POST['NewDeckName'];
					$list = $player->ListDecks();
					$pos = array_search($newname, $list);
					if ($pos !== false)
					{
						$error = 'Cannot change deck name, it is already used by another deck.';
						$current = 'Deck_edit';
					}
					elseif (trim($newname) == '')
					{
						$error = 'Cannot change deck name, invalid input.';
						$current = 'Deck_edit';
					}
					else
					{
						$deck = $player->GetDeck($curname);
						
						if ($deck != false)
						{
							$deck->RenameDeck($newname);
							$_POST['CurrentDeck'] = $newname;
							
							$information = "Deck saved.";
							$current = 'Deck_edit';
						}
						else
						{
							$error = 'Cannot view deck, name no longer exists.';
							$current = 'Decks';
						}
					}
					break;
				}
				// end deck-related messages
				
				// novels-related messages
				
				if ($message == 'view_novel') // Novels -> expand novel
				{
					$_POST['current_novel'] = array_shift(array_keys($value));
					
					$current = 'Novels';
					
					break;
				}
				
				if ($message == 'collapse_novel') // Novels -> collapse novel
				{
					$_POST['current_novel'] = "";
					$_POST['current_chapter'] = "";
					
					$current = 'Novels';
					
					break;
				}
				
				if ($message == 'view_chapter') // Novels -> select chapter
				{
					$_POST['current_chapter'] = array_shift(array_keys($value));
					
					$_POST['current_page'] = 0;
					
					$current = 'Novels';
					
					break;
				}
				
				if ($message == 'select_page') // Novels -> select page (previous and next button)
				{
					$_POST['current_page'] = array_shift(array_keys($value));
					
					$current = 'Novels';
					
					break;
				}
				
				if ($message == 'Jump') // Novels -> select page (Jump to page)
				{
					$_POST['current_page'] = $_POST['jump_to_page'];
					
					$current = 'Novels';
					
					break;
				}
				
				// end novels related messages
				
				// settings-related messages
				
				if ($message == 'user_settings') //upload user settings
				{
					$settings = $settingdb->UserSettingsList();
					foreach($settings as $input => $setting)
						if (isset($_POST[$input]) and $input != 'Birthdate')
							$player->ChangeSetting($setting, $_POST[$input]);

					//birthdate is handled separately
					if( $_POST['Birthyear'] == "" ) $_POST['Birthyear'] = '0000';
					if( $_POST['Birthmonth'] == "" ) $_POST['Birthmonth'] = '00';
					if( $_POST['Birthday'] == "" ) $_POST['Birthday'] = '00';

					$result = CheckDateInput($_POST['Birthyear'], $_POST['Birthmonth'], $_POST['Birthday']);
					if( $result != "" )
						$error = $result;
					elseif( intval(date("Y")) <= $_POST['Birthyear'] )
						$error = "Invalid birthdate";
					else
						$player->ChangeSetting("Birthdate", $_POST['Birthyear']."-".$_POST['Birthmonth']."-".$_POST['Birthday']);

					$information = "User settings saved";
					$current = 'Settings';
					
					break;
				}
				
				if ($message == 'game_settings') //upload game settings
				{
					$settings = $settingdb->GameSettingsList();
					unset($settings['Timezone']); //handle timezone separately
					
					foreach($settings as $input => $setting)
					{
						if( isset($_POST[$input]) ) // option is checked
							$player->ChangeSetting($setting, "yes");
						else // assume option is unchecked
							$player->ChangeSetting($setting, "no");
					}
					
					if( isset($_POST['Timezone']) and (int)$_POST['Timezone'] >= -12 and (int)$_POST['Timezone'] <= +12 )
						$player->ChangeSetting("Timezone", $_POST['Timezone']);
					
					$information = "Game settings saved";
					
					$current = 'Settings';
					
					break;
				}
				
				if ($message == 'Avatar') //upload avatar
				{
					// check access rights
					if (!$access_rights[$player->Type()]["change_own_avatar"]) { $error = 'Access denied.'; $current = 'Settings'; break; }
				
					$former_name = $player->GetSetting("Avatar");
					$former_path = 'img/avatars/'.$former_name;
					
					$type = $_FILES['uploadedfile']['type'];
					$pos = strrpos($type, "/") + 1;
					
					$code_type = substr($type, $pos, strlen($type) - $pos);
					$filtered_name = preg_replace("/[^a-zA-Z0-9_-]/i", "_", $player->Name());
					
					$code_name = time().$filtered_name.'.'.$code_type;
					$target_path = 'img/avatars/'.$code_name;
					
					$supported_types = array("image/jpg", "image/jpeg", "image/gif", "image/png");
										
					if (($_FILES['uploadedfile']['tmp_name'] == ""))
						$error = "Invalid input file";
					else
					if (($_FILES['uploadedfile']['size'] > 10*1000 ))
						$error = "File is too big";
					else
					if (!in_array($_FILES['uploadedfile']['type'], $supported_types))
						$error = "Unsupported input file";
					else
					if (move_uploaded_file($_FILES['uploadedfile']['tmp_name'], $target_path) == FALSE)
						$error = "Upload failed, error code ".$_FILES['uploadedfile']['error'];
					else
					{
						if ((file_exists($former_path)) and ($former_name != "noavatar.jpg")) unlink($former_path);
						$player->ChangeSetting("Avatar", $code_name);
						$information = "Avatar uploaded";
					}
					
					$current = 'Settings';
					
					break;
				}
				
				if ($message == 'reset_avatar') // reset own avatar
				{
					// check access rights
					if (!$access_rights[$player->Type()]["change_own_avatar"]) { $error = 'Access denied.'; $current = 'Settings'; break; }
					
					$former_name = $player->GetSetting("Avatar");
					$former_path = 'img/avatars/'.$former_name;
					
					if ((file_exists($former_path)) and ($former_name != "noavatar.jpg")) unlink($former_path);
					$player->ChangeSetting("Avatar", "noavatar.jpg");
					$information = "Avatar cleared";
					
					$current = 'Settings';
					
					break;
				}
				
				if ($message == 'reset_avatar_remote') // reset some player's avatar
				{
					$_POST['cur_player'] = postdecode(array_shift(array_keys($value)));
					
					$opponent = $playerdb->GetPlayer($_POST['cur_player']);
					
					// check access rights
					if (!$access_rights[$player->Type()]["change_all_avatar"]) { $error = 'Access denied.'; $current = 'Profile'; break; }
					
					$former_name = $opponent->GetSetting("Avatar");
					$former_path = 'img/avatars/'.$former_name;
					
					if ((file_exists($former_path)) and ($former_name != "noavatar.jpg")) unlink($former_path);
					$opponent->ChangeSetting("Avatar", "noavatar.jpg");
					$information = "Avatar cleared";
					
					$current = 'Profile';
					
					break;
				}
				
				if ($message == 'changepasswd') //change password
				{
					if (!isset($_POST['NewPassword']) || !isset ($_POST['NewPassword2']) || trim($_POST['NewPassword']) == '' || trim($_POST['NewPassword2']) == '')
						$error = "Please enter all required inputs.";
					
					elseif ($_POST['NewPassword'] != $_POST['NewPassword2'])
						$error = "The two passwords don't match.";
					
					elseif (!$logindb->ChangePassword($player->Name(), $_POST['NewPassword']))
						$error = "Failed to change password.";
					
					else $information = "Password changed";
					
					$current = 'Settings';
					
					break;
				}
				
				// end settings-related messages
				
				// begin forum oriented messages
				
				// begin section oriented messages
				
				if ($message == 'section_details') // forum -> section
				{
					$section_id = array_shift(array_keys($value));
					
					$current = 'Section_details';
					
					break;
				}
				
				if ($message == 'section_select_page') // forum -> section -> select page with select element
				{
					$section_id = $_POST['CurrentSection'];
					$current_page = $_POST['section_select_page'];
					
					$current = 'Section_details';
					
					break;
				}
				
				if ($message == 'section_page_jump') // forum -> section -> select page with previous or next button
				{
					$section_id = $_POST['CurrentSection'];
					$current_page = array_shift(array_keys($value));
										
					$current = 'Section_details';
					
					break;
				}
				
				if ($message == 'new_thread') // forum -> section -> new thread
				{				
					$section_id = $_POST['CurrentSection'];
					
					// check access rights
					if (!$access_rights[$player->Type()]["create_thread"]) { $error = 'Access denied.'; $current = 'Section_details'; break; }
										
					$current = 'New_thread';
					
					break;
				}
				
				if ($message == 'create_thread') // forum -> section -> new thread -> create new thread
				{
					$section_id = $_POST['CurrentSection'];
					
					// check access rights
					if (!$access_rights[$player->Type()]["create_thread"]) { $error = 'Access denied.'; $current = 'Section_details'; break; }
					// check access rights
					if ((!$access_rights[$player->Type()]["chng_priority"]) AND ($_POST['Priority'] != "normal")) { $error = 'Access denied.'; $current = 'Section_details'; break; }
					
					if ((trim($_POST['Title']) == "") OR (trim($_POST['Content']) == "")) { $error = "Invalid input"; $current = "New_thread"; break; }
					
					if (strlen($_POST['Content']) > POST_LENGTH) { $error = "Thread text is too long"; $current = "New_thread"; break; }
					
					$thread_id = $forum->Threads->ThreadExists($_POST['Title']);
					if ($thread_id) { $error = "Thread already exists"; $current = "Thread_details"; break; }
					
					$new_thread = $forum->Threads->CreateThread($_POST['Title'], $player->Name(), $_POST['Priority'], $section_id);
					if ($new_thread === FALSE) { $error = "Failed to create new thread"; $current = "Section_details"; break; }
					// $new_thread contains ID of currently created thread, which can be 0
					
					$new_post = $forum->Threads->Posts->CreatePost($new_thread, $player->Name(), $_POST['Content']);					
					if (!$new_post) { $error = "Failed to create new post"; $current = "Section_details"; break; }
					
					$information = "Thread created";
										
					$current = 'Section_details';
					
					break;
				}
				
				if ($message == 'thread_last_page') // forum -> section -> thread -> go to last page
				{
					$thread_id = array_shift(array_keys($value));
														
					$current_page = $forum->Threads->Posts->CountPages($thread_id) - 1;
															
					$current = 'Thread_details';
					
					break;
				}
				
				// end section oriented messages
				
				// begin thread oriented messages
				
				if ($message == 'thread_details') // forum -> section -> thread
				{				
					$thread_id = array_shift(array_keys($value));
										
					$current = 'Thread_details';
					
					break;
				}
				
				if ($message == 'thread_select_page') // forum -> section -> thread -> select page with select element
				{
					$thread_id = $_POST['CurrentThread'];
					$current_page = $_POST['thread_select_page'];
					
					$current = 'Thread_details';
					
					break;
				}
				
				if ($message == 'thread_page_jump') // forum -> section -> thread -> select page with previous or next button
				{
					$thread_id = $_POST['CurrentThread'];
					
					$current_page = array_shift(array_keys($value));
										
					$current = 'Thread_details';
					
					break;
				}
				
				if ($message == 'thread_lock') // forum -> section -> thread -> lock thread
				{
					$thread_id = $_POST['CurrentThread'];					
					$current_page = $_POST['CurrentPage'];
					
					// check access rights
					if (!$access_rights[$player->Type()]["lock_thread"]) { $error = 'Access denied.'; $current = 'Thread_details'; break; }
					
					$lock = $forum->Threads->LockThread($thread_id);					
					if (!$lock) { $error = "Failed to lock thread"; $current = "Thread_details"; break; }
					
					$information = "Thread locked";
										
					$current = 'Thread_details';
					
					break;
				}
				
				if ($message == 'thread_unlock') // forum -> section -> thread -> unlock thread
				{
					$thread_id = $_POST['CurrentThread'];					
					$current_page = $_POST['CurrentPage'];
					
					// check access rights
					if (!$access_rights[$player->Type()]["lock_thread"]) { $error = 'Access denied.'; $current = 'Thread_details'; break; }
					
					$lock = $forum->Threads->UnlockThread($thread_id);					
					if (!$lock) { $error = "Failed to unlock thread"; $current = "Thread_details"; break; }
					
					$information = "Thread unlocked";
										
					$current = 'Thread_details';
					
					break;
				}
				
				if ($message == 'thread_delete') // forum -> section -> thread -> delete thread
				{
					// only symbolic functionality... rest is handled below
					$section_id = $_POST['CurrentSection'];
					$thread_id = $_POST['CurrentThread'];					
					$current_page = $_POST['CurrentPage'];
					
					// check access rights
					if (!$access_rights[$player->Type()]["del_all_thread"]) { $error = 'Access denied.'; $current = 'Thread_details'; break; }
										
					$current = 'Thread_details';
					break;
				}
				
				if ($message == 'thread_delete_confirm') // forum -> section -> thread -> confirm delete thread
				{
					$section_id = $_POST['CurrentSection'];
					$thread_id = $_POST['CurrentThread'];					
					$current_page = $_POST['CurrentPage'];
					
					// check access rights
					if (!$access_rights[$player->Type()]["del_all_thread"]) { $error = 'Access denied.'; $current = 'Thread_details'; break; }
					
					$delete = $forum->Threads->DeleteThread($thread_id);					
					if (!$delete) { $error = "Failed to delete thread"; $current = "Thread_details"; break; }
										
					$information = "Thread deleted";
										
					$current = 'Section_details';
					
					break;
				}
				
				if ($message == 'new_post') // forum -> section -> thread -> new post
				{
					$thread_id = $_POST['CurrentThread'];
					
					// check if thread is locked
					if ($forum->Threads->IsLocked($thread_id)) { $error = 'Thread is locked.'; $current = 'Thread_details'; break; }
					
					// check access rights
					if (!$access_rights[$player->Type()]["create_post"]) { $error = 'Access denied.'; $current = 'Thread_details'; break; }
					
					$current = 'New_post';
					
					break;
				}
				
				if ($message == 'create_post') // forum -> section -> thread -> create new post
				{
					$thread_id = $_POST['CurrentThread'];
					
					// check if thread is locked
					if ($forum->Threads->IsLocked($thread_id)) { $error = 'Thread is locked.'; $current = 'Thread_details'; break; }
					
					// check access rights
					if (!$access_rights[$player->Type()]["create_post"]) { $error = 'Access denied.'; $current = 'Thread_details'; break; }
														
					if (trim($_POST['Content']) == "") { $error = "Invalid input"; $current = "New_post"; break; }
					
					if (strlen($_POST['Content']) > POST_LENGTH) { $error = "Post text is too long"; $current = "New_post"; break; }
									
					$new_post = $forum->Threads->Posts->CreatePost($thread_id, $player->Name(), $_POST['Content']);					
					if (!$new_post) { $error = "Failed to create new post"; $current = "Thread_details"; break; }
					
					$information = "Post created";
					
					$current_page = ($forum->Threads->Posts->CountPages($thread_id)) - 1;
										
					$current = 'Thread_details';
					
					break;
				}
				
				if ($message == 'edit_thread') // forum -> section -> thread -> edit thread
				{
					$thread_id = $_POST['CurrentThread'];
					
					$thread_data = $forum->Threads->GetThread($thread_id);
					
					// check if thread is locked and if you have access to unlock it
					if (($forum->Threads->IsLocked($thread_id)) AND (!$access_rights[$player->Type()]["lock_thread"])) { $error = 'Thread is locked.'; $current = 'Thread_details'; break; }
					
					// check access rights
					if (!(($access_rights[$player->Type()]["edit_all_thread"]) OR ($access_rights[$player->Type()]["edit_own_thread"] AND $thread_data['Author'] == $player->Name()))) { $error = 'Access denied.'; $current = 'Thread_details'; break; }
										
					$current = 'Edit_thread';
					
					break;
				}
				
				if ($message == 'modify_thread') // forum -> section -> thread -> modify thread
				{
					$thread_id = $_POST['CurrentThread'];
					
					$thread_data = $forum->Threads->GetThread($thread_id);
					
					// check if thread is locked and if you have access to unlock it
					if (($forum->Threads->IsLocked($thread_id)) AND (!$access_rights[$player->Type()]["lock_thread"])) { $error = 'Thread is locked.'; $current = 'Thread_details'; break; }
					
					// check access rights
					if (!(($access_rights[$player->Type()]["edit_all_thread"]) OR ($access_rights[$player->Type()]["edit_own_thread"] AND $thread_data['Author'] == $player->Name()))) { $error = 'Access denied.'; $current = 'Thread_details'; break; }
					
					// check access rights
					if ((!$access_rights[$player->Type()]["chng_priority"]) AND (isset($_POST['Priority'])) AND ($_POST['Priority'] != $thread_data['Priority'])) { $error = 'Access denied.'; $current = 'Thread_details'; break; }
														
					if (trim($_POST['Title']) == "") { $error = "Invalid input"; $current = "Thread_details"; break; }
					
					$new_priority = ((isset($_POST['Priority'])) ? $_POST['Priority'] : $thread_data['Priority']);
									
					$edited_thread = $forum->Threads->EditThread($thread_id, $_POST['Title'], $new_priority);					
					if (!$edited_thread) { $error = "Failed to edit thread"; $current = "Thread_details"; break; }
					
					$information = "Changes saved";
															
					$current = 'Thread_details';
					
					break;
				}
				
				if ($message == 'move_thread') // forum -> section -> thread -> edit thread -> move thread to a new section
				{
					$thread_id = $_POST['CurrentThread'];					
					$new_section = $_POST['section_select'];
					
					// check access rights
					if (!$access_rights[$player->Type()]["move_thread"]) { $error = 'Access denied.'; $current = 'Thread_details'; break; }
														
					$move = $forum->Threads->MoveThread($thread_id, $new_section);
					if (!$move) { $error = "Failed to change sections"; $current = "Edit_thread"; break; }
					
					$information = "Section changed";
															
					$current = 'Edit_thread';
					
					break;
				}
				
				// end thread oriented messages
				
				// begin post oriented messages
				
				if ($message == 'edit_post') // forum -> section -> thread -> edit post
				{
					$thread_id = $_POST['CurrentThread'];
					$post_id = array_shift(array_keys($value));
					$current_page = $_POST['CurrentPage'];
					
					// check if thread is locked and if you have access to unlock it
					if (($forum->Threads->IsLocked($thread_id)) AND (!$access_rights[$player->Type()]["lock_thread"])) { $error = 'Thread is locked.'; $current = 'Thread_details'; break; }
					
					$post_data = $forum->Threads->Posts->GetPost($post_id);
					
					if (!(($access_rights[$player->Type()]["edit_all_post"]) OR ($access_rights[$player->Type()]["edit_own_post"] AND $post_data['Author'] == $player->Name()))) { $error = 'Access denied.'; $current = 'Thread_details'; break; }
										
					$current = 'Edit_post';
					
					break;
				}
				
				if ($message == 'modify_post') // forum -> section -> thread -> save edited post
				{
					$thread_id = $_POST['CurrentThread'];
					$current_page = $_POST['CurrentPage'];
					$post_id = $_POST['CurrentPost'];
					
					// check if thread is locked and if you have access to unlock it
					if (($forum->Threads->IsLocked($thread_id)) AND (!$access_rights[$player->Type()]["lock_thread"])) { $error = 'Thread is locked.'; $current = 'Thread_details'; break; }
					
					$post_data = $forum->Threads->Posts->GetPost($post_id);
					
					if (!(($access_rights[$player->Type()]["edit_all_post"]) OR ($access_rights[$player->Type()]["edit_own_post"] AND $post_data['Author'] == $player->Name()))) { $error = 'Access denied.'; $current = 'Thread_details'; break; }
														
					if (trim($_POST['Content']) == "") { $error = "Invalid input"; $current = "Edit_post"; break; }
					
					if (strlen($_POST['Content']) > POST_LENGTH) { $error = "Post text is too long"; $current = "Edit_post"; break; }
									
					$edited_post = $forum->Threads->Posts->EditPost($post_id, $_POST['Content']);					
					if (!$edited_post) { $error = "Failed to edit post"; $current = "Thread_details"; break; }
					
					$information = "Changes saved";
															
					$current = 'Thread_details';
					
					break;
				}
				
				if ($message == 'delete_post') // forum -> section -> thread -> delete post
				{
					// only symbolic functionality... rest is handled below
					$thread_id = $_POST['CurrentThread'];
					$deleting_post = array_shift(array_keys($value));				
					$current_page = $_POST['CurrentPage'];
					
					// check if thread is locked and if you have access to unlock it
					if (($forum->Threads->IsLocked($thread_id)) AND (!$access_rights[$player->Type()]["lock_thread"])) { $error = 'Thread is locked.'; $current = 'Thread_details'; break; }
					
					// check access rights
					if (!$access_rights[$player->Type()]["del_all_post"]) { $error = 'Access denied.'; $current = 'Thread_details'; break; }
					
					$information = "Please confirm post deletion";
					
					$current = 'Thread_details';
					break;
				}
				
				if ($message == 'delete_post_confirm') // forum -> section -> thread -> delete post confirm
				{
					$thread_id = $_POST['CurrentThread'];
					$post_id = array_shift(array_keys($value));
					$current_page = $_POST['CurrentPage'];
					
					// check if thread is locked and if you have access to unlock it
					if (($forum->Threads->IsLocked($thread_id)) AND (!$access_rights[$player->Type()]["lock_thread"])) { $error = 'Thread is locked.'; $current = 'Thread_details'; break; }
					
					// check access rights
					if (!$access_rights[$player->Type()]["del_all_post"]) { $error = 'Access denied.'; $current = 'Thread_details'; break; }
					
					$deleted_post = $forum->Threads->Posts->DeletePost($post_id);
					if (!$deleted_post) { $error = "Failed to delete post"; $current = "Thread_details"; break; }
					
					$max_page = $forum->Threads->Posts->CountPages($thread_id) - 1;
					
					$current_page = (($_POST['CurrentPage'] <= $max_page) ? $_POST['CurrentPage'] : $max_page);
					
					$information = "Post deleted";
										
					$current = 'Thread_details';
					
					break;
				}
																
				if ($message == 'move_post') // forum -> section -> thread -> post -> edit post -> move post to a new thread
				{
					$thread_id = $_POST['CurrentThread'];
					$post_id = $_POST['CurrentPost'];
					$new_thread = $_POST['thread_select'];
					$current_page = $_POST['CurrentPage'];
					
					// check access rights
					if (!$access_rights[$player->Type()]["move_post"]) { $error = 'Access denied.'; $current = 'Thread_details'; break; }
					
					$move = $forum->Threads->Posts->MovePost($post_id, $new_thread);
					if (!$move) { $error = "Failed to change threads"; $current = "Thread_details"; break; }
					
					$post_data = $forum->Threads->Posts->GetPost($post_id);
					$current_page = 0; // go to first page of target thread on success
					
					$information = "Thread changed";
					
					$current = 'Edit_post';
					
					break;
				}
				
				// end thread oriented messages
				
				// end forum oriented messages
				
				// begin players related messages
				
				$temp = array("asc" => "ASC", "desc" => "DESC");
				foreach($temp as $type => $order_val)
				{
					if ($message == 'players_ord_'.$type) // select ascending or descending order in players list
					{
						$_POST['CurrentCondition'] = postdecode(array_shift(array_keys($value)));
						$_POST['CurrentOrder'] = $order_val;
						
						$current = "Players";
						
						break;
					}
				}
				
				if ($message == 'filter_players') // use player filter in players list
				{
					$_POST['CurrentFilter'] = $_POST['player_filter'];
					$_POST['CurrentPlayersPage'] = 0;
					
					$current = "Players";
					
					break;
				}
				
				if ($message == 'select_page_players') // Players -> select page (previous and next button)
				{
					$_POST['CurrentPlayersPage'] = array_shift(array_keys($value));
					
					$current = "Players";
					
					break;
				}
				
				if ($message == 'Jump_players') // Players -> select page (Jump to page)
				{
					$_POST['CurrentPlayersPage'] = $_POST['jump_to_page'];
					
					$current = "Players";
					
					break;
				}
				
				// end players related messages
				
				// refresh button :)
				if ($message == 'Refresh')
				{
					$current = postdecode(array_shift(array_keys($value)));
					break;
				}
			} // foreach($_POST as $msg)
		} // inner-page messages
	} // else ($session)

	} while(0); // end dummy scope

	// clear all used temporary variables ... because php uses weird variable scope -_-
	unset($list);
	unset($deck);
	unset($card);
	unset($game);
	unset($gameid);
	unset($opponent);
	
	/*	</section>	*/

	/*	<section: PRESENTATION>	*/
		
	// whether to display the login box or navigation bar
	$params["main"]["is_logged_in"] = ($session) ? 'yes' : 'no';

	// which section to display
	$params["main"]["section"] = $current;

	// session information, if necessary
	if( $session and !$session->hasCookies() )
	{
		$params["main"]["username"] = $session->Username();
		$params["main"]["sessionid"] = $session->SessionID();
	}

	if( !$session )
	{
		// login box params
		$params["loginbox"]["error_msg"] = @$error;
		$params["loginbox"]["warning_msg"] = @$warning;
		$params["loginbox"]["info_msg"] = @$information;
	}
	else
	{
		// navbar params
		$params["navbar"]["player_name"] = $player->Name();
		$params["navbar"]["current"] = $current;
		$params["navbar"]["error_msg"] = @$error;
		$params["navbar"]["warning_msg"] = @$warning;
		$params["navbar"]["info_msg"] = @$information;
		$params["navbar"]['NumChallenges'] = count($gamedb->ListChallengesTo($player->Name()));
		$params["navbar"]['NumUnread'] = $messagedb->CountUnreadMessages($player->Name());
		$params["navbar"]['IsSomethingNew'] = (($forum->IsSomethingNew($player->PreviousLogin())) ? 'yes' : 'no');
		
		$list = $gamedb->ListActiveGames($player->Name());
		$temp = 0;
		
		if (count($list) > 0)
		{
			foreach ($list as $data)
			{
				$game = $gamedb->GetGame2($data['Player1'], $data['Player2']);
				
				if ($game->GameData->Current == $player->Name()) $temp++;
			}
		}
		
		$params["navbar"]['NumGames'] = $temp;
	}
	
// now display current inner-page contents
switch( $current )
{
case 'Page':
	// decide what screen is default (depends on whether the user is logged in)
	$default_page = ( !$session ) ? 'Main' : 'News';
	$selected = isset($_POST['WebPage']) ? postdecode(array_shift(array_keys($_POST['WebPage']))) : $default_page;

	// list the names of the files to display
	// (all files whose name matches up to the first space character)
	$files = preg_grep('/^'.$selected.'( .*)?\.xml/i', scandir('pages',1));

	$params['website']['selected'] = $selected;
	$params['website']['files'] = $files;
	$params['website']['timezone'] = ( isset($player) ) ? $player->GetSetting("Timezone") : '+0';
	break;


case 'Deck_edit':
	$currentdeck = $params['deck_edit']['CurrentDeck'] = $_POST['CurrentDeck'];
	$classfilter = $params['deck_edit']['ClassFilter'] = $_POST['ClassFilter'];
	$costfilter = $params['deck_edit']['CostFilter'] = $_POST['CostFilter'];
	$keywordfilter = $params['deck_edit']['KeywordFilter'] = $_POST['KeywordFilter'];
	$advancedfilter = $params['deck_edit']['AdvancedFilter'] = $_POST['AdvancedFilter'];
	$supportfilter = $params['deck_edit']['SupportFilter'] = $_POST['SupportFilter'];

	$params['deck_edit']['keywords'] = $carddb->Keywords();

	// download the neccessary data
	$deck = $player->GetDeck($currentdeck);

	$params['deck_edit']['reset'] = ( (isset($_POST["reset_deck_prepare"] )) ? 'yes' : 'no');
	$params['deck_edit']['randomize'] = ( (isset($_POST["randomize_deck_prepare"] )) ? 'yes' : 'no');

	// load card display settings
	$c_text = $player->GetSetting("Cardtext");
	$c_img = $player->GetSetting("Images");
	$c_keywords = $player->GetSetting("Keywords");
	$c_oldlook = $player->GetSetting("OldCardLook");

	// calculate average cost per turn

	// define a data structure for our needs
	$sub_array = array('Common' => 0, 'Uncommon' => 0, 'Rare' => 0);

	$sum = array('Bricks' => $sub_array ,'Gems' => $sub_array, 'Recruits' => $sub_array, 'Count' => $sub_array);
	$avg = array('Bricks' => $sub_array ,'Gems' => $sub_array, 'Recruits' => $sub_array);
	$res = array('Bricks' => 0 ,'Gems' => 0, 'Recruits' => 0);

	foreach ($sub_array as $class => $value)
	{
		foreach ($deck->DeckData->$class as $index => $cardid)
		{
			if ($cardid != 0)
			{
				$card = $carddb->GetCard($cardid);
				$sum['Bricks'][$class]+= $card->CardData->Bricks;
				$sum['Gems'][$class]+= $card->CardData->Gems;
				$sum['Recruits'][$class]+= $card->CardData->Recruits;
				$sum['Count'][$class]+= 1;
			}
		}
	}

	foreach ($avg as $type => $value)
	{
		if ($sum['Count']['Common'] == 0) $avg[$type]['Common'] = 0;
		else $avg[$type]['Common'] = ($sum[$type]['Common'] * 0.65)/$sum['Count']['Common'];

		if ($sum['Count']['Uncommon'] == 0) $avg[$type]['Uncommon'] = 0;
		else $avg[$type]['Uncommon'] = ($sum[$type]['Uncommon'] * 0.29)/$sum['Count']['Uncommon'];

		if ($sum['Count']['Rare'] == 0) $avg[$type]['Rare'] = 0;
		else $avg[$type]['Rare'] = (($sum[$type]['Rare'] * 0.06)/$sum['Count']['Rare']);
	}

	foreach ($avg as $type => $value) $res[$type] = round($avg[$type]['Common'] + $avg[$type]['Uncommon'] + $avg[$type]['Rare'],2);

	$params['deck_edit']['Res'] = $res;

	$list = $carddb->GetList($classfilter, $keywordfilter, $costfilter, $advancedfilter, $supportfilter);
	
	foreach($list as $list_index => $cardid)
	{
		// check if the card isn't already present in the deck
		if (!(array_search($cardid, $deck->DeckData->$classfilter) !== false))
		{
			$params['deck_edit']['CardList'][$list_index]['CardID'] = $cardid;
			$params['deck_edit']['CardList'][$list_index]['CardString'] = $carddb->GetCard($cardid)->CardString($c_text, $c_img, $c_keywords, $c_oldlook);
		}
	}
	
	$params['deck_edit']['ListCount'] = count($list);

	$params['deck_edit']['Take'] = ( $deck->DeckData->Count($classfilter) < 15 ) ? 'yes' : 'no';

	foreach (array('Common', 'Uncommon', 'Rare') as $class)
	{
		$cards = array();
		
		foreach ($deck->DeckData->$class as $index => $cardid)
		{
			// index manipulation, due to the fact that we count from 1, instead of 0
			// PHP does not have div function, so we must use floor and standard division
			$row = floor(($index - 1) / 3);
			$column = ($index - 1) % 3;
			
			$cards[$row][$column]['CardID'] = $cardid;
			$cards[$row][$column]['CardString'] = $carddb->GetCard($cardid)->CardString($c_text, $c_img, $c_keywords, $c_oldlook);
		}
		
		$params['deck_edit']['DeckCards'][$class] = $cards;
	}

	$params['deck_edit']['Tokens'] = $deck->DeckData->Tokens;
	$params['deck_edit']['TokenKeywords'] = $carddb->TokenKeywords();

	break;


case 'Decks':
	$params['decks']['list'] = $player->ListDecks();

	break;


case 'Players':	

	// defaults for list ordering
	if (!isset($_POST['CurrentOrder'])) $_POST['CurrentOrder'] = "DESC";
	if (!isset($_POST['CurrentCondition'])) $_POST['CurrentCondition'] = "Rank";

	$params['players']['order'] = $order = $_POST['CurrentOrder'];
	$params['players']['condition'] = $condition = $_POST['CurrentCondition'];

	$params['players']['CurrentFilter'] = $filter = ((isset($_POST['CurrentFilter'])) ? $_POST['CurrentFilter'] : "none");

	$params['players']['PlayerName'] = $player->Name();

	// check for active decks
	$params['players']['active_decks'] = count($player->ListReadyDecks());

	//retrieve layout setting
	$params['players']['show_nationality'] = $player->GetSetting("Nationality");
	$params['players']['show_avatars'] = $player->GetSetting("Avatarlist");

	$activegames = $gamedb->ListActiveGames($player->Name());
	$challengesfrom = $gamedb->ListChallengesFrom($player->Name());
	$challengesto = $gamedb->ListChallengesTo($player->Name());
	$endedgames = $gamedb->ListEndedGames($player->Name());

	$params['players']['free_slots'] = MAX_GAMES - (count($activegames) + count($challengesfrom) + count($challengesto));

	$params['players']['messages'] = ($access_rights[$player->Type()]["messages"]) ? 'yes' : 'no';
	$params['players']['send_challenges'] = ($access_rights[$player->Type()]["send_challenges"]) ? 'yes' : 'no';

	$current_page = ((isset($_POST['CurrentPlayersPage'])) ? $_POST['CurrentPlayersPage'] : 0);
	$params['players']['current_page'] = $current_page;

	$page_count = $playerdb->CountPages($filter);
	$pages = array();
	if ($page_count > 0) for ($i = 0; $i < $page_count; $i++) $pages[$i] = $i;
	$params['players']['pages'] = $pages;
	$params['players']['page_count'] = $page_count;

	// get the list of all existing players; (Username, Wins, Losses, Draws, Last Query, Free slots, Avatar, Country)
	$list = $playerdb->ListPlayers($filter, $condition, $order, $current_page);

	// for each player, display their name, score, and if conditions are met, also display the challenge button
	foreach ($list as $i => $data)
	{
		$last_query = strtotime($data['Last Query']);
		// choose name color according to inactivity time
		if     (time() - $last_query > (60*60*24*7*3))
		{ $namecolor ='gray'; $player_type = "Dead"; } // 'dead' after 3 weeks of inactivity
		elseif (time() - $last_query > (60*60*24*7*1))
		{ $namecolor ='maroon'; $player_type = "Inactive"; } // 'not interested' after 1 week of inactivity
		elseif (time() - $last_query > (60*10))
		{ $namecolor ='red'; $player_type = "Offline"; } // 'offline' after 10 minutes of inactivity
		else //(time(0 - $last_query <= (60*10))
		{ $namecolor ='lime'; $player_type = "Online"; } // default 'online' players

		$opponent = $data['Username'];

		$entry = array();
		$entry['name'] = $data['Username'];
		$entry['wins'] = $data['Wins'];
		$entry['losses'] = $data['Losses'];
		$entry['draws'] = $data['Draws'];
		$entry['avatar'] = $data['Avatar'];
		$entry['country'] = $data['Country'];
		$entry['last_query'] = $data['Last Query'];
		$entry['free_slots'] = $data['Free slots'];
		$entry['rank'] = $data['Rank'];
		$entry['player_type'] = $player_type; // unused
		$entry['namecolor'] = $namecolor;
		$entry['challenged'] = (array_search(array('Player1' => $player->Name(), 'Player2' => $opponent), $challengesfrom) !== false) ? 'yes' : 'no';
		$entry['playingagainst'] = (array_search(array('Player1' => $player->Name(), 'Player2' => $opponent), $activegames) !== false) ? 'yes' : 'no';
		$entry['waitingforack'] = (array_search(array('Player1' => $player->Name(), 'Player2' => $opponent), $endedgames) !== false) ? 'yes' : 'no';

		$params['players']['list'][] = $entry;
	}
	
	break;


case 'Profile':

	// retrieve name of a player we are currently viewing
	$cur_player = (isset($_POST['Profile'])) ? $_POST['Profile'] : $_POST['cur_player'];

	$p = $playerdb->GetPlayer($cur_player);
	$settings = $p->GetUserSettings();

	$params['profile']['PlayerName'] = $p->Name();
	$params['profile']['PlayerType'] = $p->Type();
	$params['profile']['Firstname'] = $settings['Firstname'];
	$params['profile']['Surname'] = $settings['Surname'];
	$params['profile']['Gender'] = $settings['Gender'];
	$params['profile']['Country'] = $settings['Country'];
	$params['profile']['Avatar'] = $settings['Avatar'];
	$params['profile']['Email'] = $settings['Email'];
	$params['profile']['Imnumber'] = $settings['Imnumber'];
	$params['profile']['Hobby'] = $settings['Hobby'];

	if( $settings["Birthdate"] != "0000-00-00" )
	{
		$params['profile']['Age'] = $settingdb->CalculateAge($settings['Birthdate']);
		$params['profile']['Sign'] = $settingdb->CalculateSign($settings['Birthdate']);
		$params['profile']['Birthdate'] = date("d-m-Y", strtotime($settings['Birthdate']));
	}
	else
	{
		$params['profile']['Age'] = 'Unknown';
		$params['profile']['Sign'] = 'Unknown';
		$params['profile']['Birthdate'] = 'Unknown';
	}

	$params['profile']['CurPlayerName'] = $player->Name();
	$params['profile']['timezone'] = $player->GetSetting("Timezone");
	$params['profile']['send_challenges'] = ($access_rights[$player->Type()]["send_challenges"]) ? 'yes' : 'no';
	$params['profile']['messages'] = ($access_rights[$player->Type()]["messages"]) ? 'yes' : 'no';
	$params['profile']['change_rights'] = ($access_rights[$player->Type()]["change_rights"]) ? 'yes' : 'no';
	$params['profile']['system_notification'] = ($access_rights[$player->Type()]["system_notification"]) ? 'yes' : 'no';
	$params['profile']['change_all_avatar'] = ($access_rights[$player->Type()]["change_all_avatar"]) ? 'yes' : 'no';

	$activegames = $gamedb->ListActiveGames($player->Name());
	$challengesfrom = $gamedb->ListChallengesFrom($player->Name());
	$challengesto = $gamedb->ListChallengesTo($player->Name());
	$endedgames = $gamedb->ListEndedGames($player->Name());
	$params['profile']['free_slots'] = MAX_GAMES - (count($activegames) + count($challengesfrom) + count($challengesto));
	$params['profile']['decks'] = $player->ListReadyDecks();

	$params['profile']['challenged'] = (array_search(array('Player1' => $player->Name(), 'Player2' => $cur_player), $challengesfrom) !== false) ? 'yes' : 'no';
	$params['profile']['playingagainst'] = (array_search(array('Player1' => $player->Name(), 'Player2' => $cur_player), $activegames) !== false) ? 'yes' : 'no';
	$params['profile']['waitingforack'] = (array_search(array('Player1' => $player->Name(), 'Player2' => $cur_player), $endedgames) !== false) ? 'yes' : 'no';

	$params['profile']['challenging'] = (isset($_POST['prepare_challenge'])) ? 'yes' : 'no';

	if ($params['profile']['challenged'])
	{
		$params['profile']['challenge'] = $messagedb->GetChallenge($player->Name(), $cur_player);
		$params['profile']['challenge']['Content'] = textencode($params['profile']['challenge']['Content']);
	}

	break;


case 'Challenges':
	$params['challenges']['PlayerName'] = $player->Name();
	$params['challenges']['PreviousLogin'] = $player->PreviousLogin();
	$params['challenges']['timezone'] = $player->GetSetting("Timezone"); 
	$params['challenges']['max_games'] = MAX_GAMES;
	$params['challenges']['system_name'] = SYSTEM_NAME;

	$decks = $params['challenges']['decks'] = $player->ListReadyDecks();
	$params['challenges']['deck_count'] = count($decks);
	$params['challenges']['startedgames'] = count($gamedb->ListActiveGames($player->Name())) + count($gamedb->ListChallengesFrom($player->Name()));

	if (isset($_POST['incoming'])) $current_subsection = "incoming";
	elseif (isset($_POST['outgoing'])) $current_subsection = "outgoing";
	elseif (!isset($current_subsection)) $current_subsection = "incoming";

	$function_type = (($current_subsection == "incoming") ? "ListChallengesTo" : "ListChallengesFrom");
	$params['challenges']['challenges'] = $messagedb->$function_type($player->Name());
	$params['challenges']['challenges_count'] = count($params['challenges']['challenges']);
	$params['challenges']['current_subsection'] = $current_subsection;

	$current_location = ((isset($_POST['CurrentLocation'])) ? $_POST['CurrentLocation'] : "inbox");

	if (!isset($_POST['CurrentOrd'])) $_POST['CurrentOrd'] = "DESC"; // default ordering
	if (!isset($_POST['CurrentCond'])) $_POST['CurrentCond'] =  "Created"; // default order condition

	$params['challenges']['current_order'] = $current_order = $_POST['CurrentOrd'];
	$params['challenges']['current_condition'] = $current_condition = $_POST['CurrentCond'];

	$current_page = ((isset($_POST['CurrentMesPage'])) ? $_POST['CurrentMesPage'] : 0);
	$params['challenges']['current_page'] = $current_page;

	// filter initialization
	if (!isset($_POST['CurrentFilterDate'])) $_POST['CurrentFilterDate'] = "none";
	if (!isset($_POST['CurrentFilterName'])) $_POST['CurrentFilterName'] = "none";

	$params['challenges']['date_val'] = $date = $_POST['CurrentFilterDate'];
	$params['challenges']['name_val'] = $name = $_POST['CurrentFilterName'];

	if ($current_location == "all_mail")
	{
		$list_type = "ListAllMessages";
		$name_type = "ListAllNames";
		$pages_type = "CountPagesAll";
	}
	elseif ($current_location == "sent_mail")
	{
		$list_type = "ListMessagesFrom";
		$name_type = "ListNamesFrom";
		$pages_type = "CountPagesFrom";
	}
	else
	{
		$list_type = "ListMessagesTo";
		$name_type = "ListNamesTo";
		$pages_type = "CountPagesTo";
	}

	$list = $messagedb->$list_type($player->Name(), $date, $name, $current_condition, $current_order, $current_page);
	$name_list = $messagedb->$name_type($player->Name(), $date);

	$page_count = $messagedb->$pages_type($player->Name(), $date, $name);
	$pages = array();
	if ($page_count > 0) for ($i = 0; $i < $page_count; $i++) $pages[$i] = $i;
	$params['challenges']['pages'] = $pages;
	$params['challenges']['page_count'] = $page_count;

	$params['challenges']['messages'] = $list;
	$params['challenges']['messages_count'] = count($list);
	$params['challenges']['current_location'] = $current_location;
	$params['challenges']['timesections'] = $messagedb->Timesections();
	$params['challenges']['name_filter'] = $name_list;
	$params['challenges']['current_page'] = $current_page;

	$params['challenges']['send_messages'] = (($access_rights[$player->Type()]["messages"]) ? 'yes' : 'no');
	$params['challenges']['accept_challenges'] = (($access_rights[$player->Type()]["accept_challenges"]) ? 'yes' : 'no');
	$params['challenges']['see_all_messages'] = (($access_rights[$player->Type()]["see_all_messages"]) ? 'yes' : 'no');

	break;


case 'Message_details':
	$params['message_details']['PlayerName'] = $player->Name();
	$params['message_details']['system_name'] = SYSTEM_NAME;
	$params['message_details']['timezone'] = $player->GetSetting("Timezone"); 

	$params['message_details']['Author'] = $message['Author'];
	$params['message_details']['Recipient'] = $message['Recipient'];
	$params['message_details']['Subject'] = $message['Subject'];
	$params['message_details']['Content'] = $message['Content'];
	$params['message_details']['MessageID'] = $messageid;
	$params['message_details']['delete'] = ((isset($_POST["message_delete"])) ? 'yes' : 'no');
	$params['message_details']['messages'] = (($access_rights[$player->Type()]["messages"]) ? 'yes' : 'no');

	$current_location = ((isset($_POST['CurrentLocation'])) ? $_POST['CurrentLocation'] : "inbox");

	$params['message_details']['current_location'] = $current_location;

	$params['message_details']['Created'] = $message['Created'];
	$params['message_details']['Stamp'] = 1 + strtotime($message['Created']) % 4; // hash function - assign stamp picture

	break;


case 'Message_new':
	$params['message_new']['Author'] = $author;
	$params['message_new']['Recipient'] = $recipient;
	$params['message_new']['Content'] = ((isset($_POST['Content'])) ? $_POST['Content'] : '');
 	$params['message_new']['Subject'] = ((isset($_POST['Subject'])) ? $_POST['Subject'] : '');

	break;


case 'Games':
	$params['games']['PlayerName'] = $player->Name();

	$list = $gamedb->ListActiveGames($player->Name());
	if (count($list) > 0)
	{
		foreach ($list as $i => $data)
		{
			$game = $gamedb->GetGame2($data['Player1'], $data['Player2']);
			$opponent = ($data['Player1'] != $player->Name()) ? $data['Player1'] : $data['Player2'];

			$params['games']['list'][$i]['opponent'] = $opponent;
			$params['games']['list'][$i]['active'] = (($playerdb->GetPlayer($opponent)->isOnline()) ? 'yes' : 'no');
			$params['games']['list'][$i]['ready'] = (($game->GameData->Current == $player->Name()) ? 'yes' : 'no');
			$params['games']['list'][$i]['gameid'] = $game->ID();
			$params['games']['list'][$i]['gamestate'] = $game->State;
			$params['games']['list'][$i]['isdead'] = (($playerdb->isDead($opponent)) ? 'yes' : 'no');
		}
	}

	break;


case 'Game':
	$gameid = $_POST['CurrentGame'];

	// prepare the neccessary data
	$game = $gamedb->GetGame($gameid);
	$player1 = $game->Name1();
	$player2 = $game->Name2();
	$data = &$game->GameData;

	$opponent = $playerdb->GetPlayer(($player1 != $player->Name()) ? $player1 : $player2);
	$mydata = &$data->Player[$player->Name()];
	$hisdata = &$data->Player[$opponent->Name()];

	$params['game']['CurrentGame'] = $gameid;

	$params['game']['chat'] = (($access_rights[$player->Type()]["chat"]) ? 'yes' : 'no');

	// load needed settings
	$c_text = $player->GetSetting("Cardtext");
	$c_img = $player->GetSetting("Images");
	$c_keywords = $player->GetSetting("Keywords");
	$c_oldlook = $player->GetSetting("OldCardLook");

	$params['game']['minimize'] = $player->GetSetting("Minimize");
	$params['game']['mycountry'] = $player->GetSetting("Country");
	$params['game']['hiscountry'] = $opponent->GetSetting("Country");
	$params['game']['timezone'] = $player->GetSetting("Timezone");

	$params['game']['GameState'] = $game->State;
	$params['game']['Round'] = $data->Round;
	$params['game']['Outcome'] = $data->Outcome;
	$params['game']['Winner'] = $data->Winner;
	$params['game']['PlayerName'] = $player->Name();
	$params['game']['OpponentName'] = $opponent->Name();
	$params['game']['Current'] = $data->Current;
	$params['game']['Timestamp'] = $data->Timestamp;

	// my hand
	$myhand = $mydata->Hand;
	foreach ($myhand as $list_index => $cardid)
	{
		$card = $carddb->GetCard($cardid);

		$params['game']['MyHand'][$list_index]['CardID'] = $cardid;
		$params['game']['MyHand'][$list_index]['CardString'] = $card->CardString($c_text, $c_img, $c_keywords, $c_oldlook);
		$params['game']['MyHand'][$list_index]['Playable'] = ((($mydata->Bricks >= $card->CardData->Bricks) and ($mydata->Gems >= $card->CardData->Gems) and ($mydata->Recruits >= $card->CardData->Recruits) and ($game->State == 'in progress') and ($data->Current == $player->Name())) ? 'yes' : 'no');
		$params['game']['MyHand'][$list_index]['Modes'] = $card->CardData->Modes;
		$params['game']['MyHand'][$list_index]['NewCard'] = (isset($mydata->NewCards[$list_index]) ? 'yes' : 'no');
	}

	$params['game']['MyBricks'] = $mydata->Bricks;
	$params['game']['MyGems'] = $mydata->Gems;
	$params['game']['MyRecruits'] = $mydata->Recruits;
	$params['game']['MyQuarry'] = $mydata->Quarry;
	$params['game']['MyMagic'] = $mydata->Magic;
	$params['game']['MyDungeons'] = $mydata->Dungeons;
	$params['game']['MyTower'] = $mydata->Tower;
	$params['game']['MyTowerBody'] = (170 * ($mydata->Tower/100));
	$params['game']['MyWall'] = $mydata->Wall;
	$params['game']['MyWallBody'] = (270 * ($mydata->Wall/150));
	
	// my discarded cards
	$mydiscards0 = array();
	$mydiscards1 = array();

	if (count($mydata->DisCards[0]) > 0)
		foreach ($mydata->DisCards[0] as $index => $cardid)
			$mydiscards0[$index] = $carddb->GetCard($cardid)->CardString($c_text, $c_img, $c_keywords, $c_oldlook);
	if (count($mydata->DisCards[1]) > 0)
		foreach ($mydata->DisCards[1] as $index => $cardid)
			$mydiscards1[$index] = $carddb->GetCard($cardid)->CardString($c_text, $c_img, $c_keywords, $c_oldlook);

	$params['game']['MyDisCards0'] = $mydiscards0;// cards discarded from my hand
	$params['game']['MyDisCards1'] = $mydiscards1;// cards discarded from his hand

	// my last played cards
	$mylastcard = array();
	for( $i = 1; $i <= count($mydata->LastCard); $i++ )
	{
		$mylastcard[$i]['CardString'] = $carddb->GetCard($mydata->LastCard[$i])->CardString($c_text, $c_img, $c_keywords, $c_oldlook);
		$mylastcard[$i]['CardAction'] = $mydata->LastAction[$i];
		$mylastcard[$i]['CardMode'] = $mydata->LastMode[$i];
	}

	$params['game']['MyLastCard'] = $mylastcard;

	// my tokens
	$my_token_names = $mydata->TokenNames;
	$my_token_values = $mydata->TokenValues;
	$my_token_changes = $mydata->TokenChanges;

	$my_tokens = array();
	foreach ($my_token_names as $index => $value)
	{
		$my_tokens[$index]['Name'] = $my_token_names[$index];
		$my_tokens[$index]['Value'] = $my_token_values[$index];
		$my_tokens[$index]['Change'] = $my_token_changes[$index];
	}

	$params['game']['MyTokens'] = $my_tokens;

	// his hand
	$hishand = $hisdata->Hand;
	foreach ($hishand as $list_index => $cardid)
	{
		$card = $carddb->GetCard($cardid);
		
		$params['game']['HisHand'][$list_index]['CardString'] = $card->CardString($c_text, $c_img, $c_keywords, $c_oldlook);
		$params['game']['HisHand'][$list_index]['NewCard'] = (isset($hisdata->NewCards[$list_index]) ? 'yes' : 'no');
	}

	$params['game']['HisBricks'] = $hisdata->Bricks;
	$params['game']['HisGems'] = $hisdata->Gems;
	$params['game']['HisRecruits'] = $hisdata->Recruits;
	$params['game']['HisQuarry'] = $hisdata->Quarry;
	$params['game']['HisMagic'] = $hisdata->Magic;
	$params['game']['HisDungeons'] = $hisdata->Dungeons;
	$params['game']['HisTower'] = $hisdata->Tower;
	$params['game']['HisTowerBody'] = (170 * ($hisdata->Tower/100));
	$params['game']['HisWall'] = $hisdata->Wall;
	$params['game']['HisWallBody'] = (270 * ($hisdata->Wall/150));

	// his discarded cards
	$hisdiscards0 = array();
	$hisdiscards1 = array();

	if (count($hisdata->DisCards[0]) > 0)
		foreach ($hisdata->DisCards[0] as $index => $cardid)
			$hisdiscards0[$index] = $carddb->GetCard($cardid)->CardString($c_text, $c_img, $c_keywords, $c_oldlook);
	if (count($hisdata->DisCards[1]) > 0)
		foreach ($hisdata->DisCards[1] as $index => $cardid)
			$hisdiscards1[$index] = $carddb->GetCard($cardid)->CardString($c_text, $c_img, $c_keywords, $c_oldlook);

	$params['game']['HisDisCards0'] = $hisdiscards0;// cards discarded from my hand
	$params['game']['HisDisCards1'] = $hisdiscards1;// cards discarded from his hand
	
	// his last played cards
	$hislastcard = array();
	for( $i = 1; $i <= count($hisdata->LastCard); $i++ )
	{
		$hislastcard[$i]['CardString'] = $carddb->GetCard($hisdata->LastCard[$i])->CardString($c_text, $c_img, $c_keywords, $c_oldlook);
		$hislastcard[$i]['CardAction'] = $hisdata->LastAction[$i];
		$hislastcard[$i]['CardMode'] = $hisdata->LastMode[$i];
	}

	$params['game']['HisLastCard'] = $hislastcard;

	// his tokens
	$his_token_names = $hisdata->TokenNames;
	$his_token_values = $hisdata->TokenValues;
	$his_token_changes = $hisdata->TokenChanges;

	$his_tokens = array();
	foreach ($his_token_names as $index => $value)
	{
		$his_tokens[$index]['Name'] = $his_token_names[$index];
		$his_tokens[$index]['Value'] = $his_token_values[$index];
		$his_tokens[$index]['Change'] = $his_token_changes[$index];
	}

	$params['game']['HisTokens'] = array_reverse($his_tokens);

	// - <quick game switching menu>
	$list = $gamedb->ListActiveGames($player->Name());

	foreach ($list as $i => $names)
	{
		$game_list = $gamedb->GetGame2($names['Player1'], $names['Player2']);
		$opponent_list = ($names['Player1'] != $player->Name()) ? $names['Player1'] : $names['Player2'];
		$opponent_object = $playerdb->GetPlayer($opponent_list);

		$color = ''; // no extra color default
		if ($game_list->GameData->Current == $player->Name()) $color = 'lime'; // when it is your turn
		if ($game_list->State == 'in progress' and $playerdb->isDead($opponent_list)) $color = 'gray'; // when game can be aborted
		if ($game_list->State == 'finished') $color = '#ff69b4'; // when game is finished color HotPink

		$params['game']['GameList'][$i]['Value'] = $game_list->ID();
		$params['game']['GameList'][$i]['Content'] = 'vs. '.htmlencode($opponent_list);
		$params['game']['GameList'][$i]['Selected'] = (($game_list->ID() == $_POST['CurrentGame']) ? 'yes' : 'no');
		$params['game']['GameList'][$i]['Color'] = $color;
	}
	// - </quick game switching menu>

	// - <'jump to next game' button>

	$list = $gamedb->ListActiveGames($player->Name());

	$num_games_your_turn = 0;
	foreach ($list as $i => $names)
		if ($gamedb->GetGame2($names['Player1'], $names['Player2'])->GameData->Current == $player->Name())
			$num_games_your_turn++;
	$params['game']['num_games_your_turn'] = $num_games_your_turn;

	// - </'jump to next game' button>

	// - <game state indicator>
	$params['game']['opp_isOnline'] = (($opponent->isOnline()) ? 'yes' : 'no');
	$params['game']['opp_isDead'] = (($opponent->isDead()) ? 'yes' : 'no');
	$params['game']['surrender'] = ((isset($_POST["surrender"])) ? 'yes' : 'no');
	$params['game']['finish_game'] = ((time() - $data->Timestamp >= 60*60*24*7*3 and $data->Current != $player->Name()) ? 'yes' : 'no');

	// your resources and tower
	$colors = array ('Quarry'=> '', 'Magic'=> '', 'Dungeons'=> '', 'Bricks'=> '', 'Gems'=> '', 'Recruits'=> '', 'Tower'=> '', 'Wall'=> '');
	foreach ($colors as $attribute => $color)
	{
		if ($mydata->Changes[$attribute] > 0) $colors[$attribute] = 'color: lime';
		elseif ($mydata->Changes[$attribute] < 0) $colors[$attribute] = 'color: orange';
		else $colors[$attribute] = '';
	}

	$params['game']['mycolors'] = $colors;

	// opponent's resources and tower
	$colors = array ('Quarry'=> '', 'Magic'=> '', 'Dungeons'=> '', 'Bricks'=> '', 'Gems'=> '', 'Recruits'=> '', 'Tower'=> '', 'Wall'=> '');
	foreach ($colors as $attribute => $color)
	{
		if ($hisdata->Changes[$attribute] > 0) $colors[$attribute] = 'color: lime';
		elseif ($hisdata->Changes[$attribute] < 0) $colors[$attribute] = 'color: orange';
	}

	$params['game']['hiscolors'] = $colors;	

	// chatboard

	$params['game']['display_avatar'] = $player->GetSetting("Avatargame");
	$params['game']['correction'] = $player->GetSetting("Correction");

	$params['game']['myavatar'] = $player->GetSetting("Avatar");
	$params['game']['hisavatar'] = $opponent->GetSetting("Avatar");

	$order = ( $player->GetSetting("Chatorder") == "yes" ) ? "ASC" : "DESC";
	$params['game']['messagelist'] = $message_list = $chatdb->ListChatMessages($game->ID(), $order);

	break;


case 'Deck_view':
	$gameid = $_POST['CurrentGame'];
	$game = $gamedb->GetGame($gameid);

	$params['deck_view']['CurrentGame'] = $gameid;
	$deck_data['Common'] = $game->GameData->Player[$player->Name()]->Deck->Common;
	$deck_data['Uncommon'] = $game->GameData->Player[$player->Name()]->Deck->Uncommon;
	$deck_data['Rare'] = $game->GameData->Player[$player->Name()]->Deck->Rare;

	//load needed settings
	$c_text = $player->GetSetting("Cardtext");
	$c_img = $player->GetSetting("Images");
	$c_keywords = $player->GetSetting("Keywords");
	$c_oldlook = $player->GetSetting("OldCardLook");
	
	foreach (array('Common', 'Uncommon', 'Rare') as $class)
	{
		$cards = array();
		
		foreach ($deck_data[$class] as $index => $cardid)
		{
			// index manipulation, due to the fact that we count from 1, instead of 0
			// PHP does not have div function, so we must use floor and standard division
			$row = floor(($index - 1) / 3);
			$column = ($index - 1) % 3;
			
			$cards[$row][$column]['CardID'] = $cardid;
			$cards[$row][$column]['CardString'] = $carddb->GetCard($cardid)->CardString($c_text, $c_img, $c_keywords, $c_oldlook);
		}
		
		$params['deck_view']['DeckCards'][$class] = $cards;
	}
	
	break;


case 'Novels':
	$current_novel = ( isset($_POST['current_novel']) ) ? $_POST['current_novel'] : "";
	$current_chapter = ( isset($_POST['current_chapter']) ) ? $_POST['current_chapter'] : "";
	$current_page = ( isset($_POST['current_page']) ) ? $_POST['current_page'] : "";

	$params['novels']['current_novel'] = $current_novel;
	$params['novels']['current_chapter'] = $current_chapter;
	$params['novels']['current_page'] = $current_page;
	$params['novels']['novelslist'] = $noveldb->GetNovelsList();
	$params['novels']['chapterslist'] = ($current_novel != "") ? $noveldb->GetChaptersList($current_novel) : null;
	$params['novels']['ListPages'] = $noveldb->ListPages($current_novel, $current_chapter);
	$params['novels']['PageContent'] = $noveldb->GetPageContent($current_novel, $current_chapter, $current_page);

	break;


case 'Settings':
	$params['settings']['current_settings'] = $player->GetSettings();
	$params['settings']['PlayerType'] = $player->Type();
	$params['settings']['change_own_avatar'] = (($access_rights[$player->Type()]["change_own_avatar"]) ? 'yes' : 'no');

	//date is handled separately
	$birthdate = $params['settings']['current_settings']["Birthdate"];
	list($year, $month, $day) = explode("-", $birthdate);

	if( $birthdate != "0000-00-00" )
	{
		$params['settings']['current_settings']["Age"] = $settingdb->CalculateAge($birthdate);
		$params['settings']['current_settings']["Sign"] = $settingdb->CalculateSign($birthdate);
		$params['settings']['current_settings']["Birthdate"] = array('year'=>$year, 'month'=>$month, 'day'=>$day);
	}
	else
	{
		$params['settings']['current_settings']["Age"] = "Unknown";
		$params['settings']['current_settings']["Sign"] = "Unknown";
		$params['settings']['current_settings']["Birthdate"] = array('year'=>'', 'month'=>'', 'day'=>'');
	}

	break;


case 'Forum':
	$params['forum_overview']['sections'] = $forum->ListSections();	
	$params['forum_overview']['PreviousLogin'] = $player->PreviousLogin();
	$params['forum_overview']['timezone'] = $player->GetSetting("Timezone");

	foreach($params['forum_overview']['sections'] as $index => $data)
		$params['forum_overview']['sections'][$index]['threadlist'] = $forum->Threads->ListThreadsMain($index);

	break;


case 'Section_details':
	// uses: $current_page, $section_id
	if (!isset($current_page)) $current_page = 0;

	$params['forum_section']['section'] = $forum->GetSection($section_id);
	$params['forum_section']['threads'] = $forum->Threads->ListThreads($section_id, $current_page, "");
	$params['forum_section']['pages'] = $forum->Threads->CountPages($section_id);
	$params['forum_section']['current_page'] = $current_page;
	$params['forum_section']['create_thread'] = (($access_rights[$player->Type()]["create_thread"]) ? 'yes' : 'no');
	$params['forum_section']['PreviousLogin'] = $player->PreviousLogin();
	$params['forum_section']['timezone'] = $player->GetSetting("Timezone");

	break;


case 'Thread_details':
	if (!isset($current_page)) $current_page = 0;

	$params['forum_thread']['Thread'] = $thread_data = $forum->Threads->GetThread($thread_id);
	$params['forum_thread']['Section'] = $forum->GetSection($thread_data['SectionID']);
	$params['forum_thread']['Pages'] = $forum->Threads->Posts->CountPages($thread_id);
	$params['forum_thread']['CurrentPage'] = $current_page;
	$params['forum_thread']['PostList'] = $forum->Threads->Posts->ListPosts($thread_id, $current_page);
	$params['forum_thread']['Delete'] = ((isset($_POST['thread_delete'])) ? 'yes' : 'no');
	$params['forum_thread']['DeletePost'] = ((isset($deleting_post)) ? $deleting_post : 0);
	$params['forum_thread']['PlayerName'] = $player->Name();
	$params['forum_thread']['PreviousLogin'] = $player->PreviousLogin();
	$params['forum_thread']['timezone'] = $player->GetSetting("Timezone");

	$params['forum_thread']['lock_thread'] = (($access_rights[$player->Type()]["lock_thread"]) ? 'yes' : 'no');
	$params['forum_thread']['del_all_thread'] = (($access_rights[$player->Type()]["del_all_thread"]) ? 'yes' : 'no');
	$params['forum_thread']['edit_thread'] = ((($access_rights[$player->Type()]["edit_all_thread"]) OR ($access_rights[$player->Type()]["edit_own_thread"] AND $thread_data['Author'] == $player->Name())) ? 'yes' : 'no');
	$params['forum_thread']['create_post'] = (($access_rights[$player->Type()]["create_post"]) ? 'yes' : 'no');
	$params['forum_thread']['del_all_post'] = (($access_rights[$player->Type()]["del_all_post"]) ? 'yes' : 'no');
	$params['forum_thread']['edit_all_post'] = (($access_rights[$player->Type()]["edit_all_post"]) ? 'yes' : 'no');
	$params['forum_thread']['edit_own_post'] = (($access_rights[$player->Type()]["edit_own_post"]) ? 'yes' : 'no');

	break;


case 'New_thread':
	$params['forum_thread_new']['Section'] = $forum->GetSection($section_id);
	$params['forum_thread_new']['Content'] = ((isset($_POST['Content'])) ? $_POST['Content'] : "");
	$params['forum_thread_new']['Title'] = ((isset($_POST['Title'])) ? $_POST['Title'] : "");
	$params['forum_thread_new']['chng_priority'] = (($access_rights[$player->Type()]["chng_priority"]) ? 'yes' : 'no');

	break;


case 'New_post':
	$params['forum_post_new']['Thread'] = $forum->Threads->GetThread($thread_id);
	$params['forum_post_new']['Content'] = ((isset($_POST['Content'])) ? $_POST['Content'] : "");

	break;


case 'Edit_thread':
	$params['forum_thread_edit']['Thread'] = $thread_data = $forum->Threads->GetThread($thread_id);
	$params['forum_thread_edit']['Section'] = $forum->GetSection($thread_data['SectionID']);
	$params['forum_thread_edit']['SectionList'] = $forum->ListTargetSections($thread_data['SectionID']);
	$params['forum_thread_edit']['chng_priority'] = (($access_rights[$player->Type()]["chng_priority"]) ? 'yes' : 'no');
	$params['forum_thread_edit']['move_thread'] = (($access_rights[$player->Type()]["move_thread"]) ? 'yes' : 'no');

	break;


case 'Edit_post':
	$params['forum_post_edit']['Post'] = $post_data;
	$params['forum_post_edit']['CurrentPage'] = $current_page;
	$params['forum_post_edit']['ThreadList'] = $forum->Threads->ListTargetThreads($post_data['ThreadID']);
	$params['forum_post_edit']['Thread'] = $forum->Threads->GetThread($post_data['ThreadID']);
	$params['forum_post_edit']['Content'] = ((isset($_POST['Content'])) ? $_POST['Content'] : $post_data['Content']);
	$params['forum_post_edit']['move_post'] = (($access_rights[$player->Type()]["move_post"]) ? 'yes' : 'no');

	break;


default:
	break;
}


	// HTML code generation

	$querytime_end = microtime(TRUE);
	$xslttime_start = $querytime_end;

	echo XSLT("templates/arcomage.xsl", $params);

	$xslttime_end = microtime(TRUE);

	$logic = (int)(1000*($querytime_end - $querytime_start));
	$transform = (int)(1000*($xslttime_end - $xslttime_start));
	$total = (int)(1000*($xslttime_end - $querytime_start));
	echo "<!-- Page generated in {$total} ({$logic} + {$transform}) ms. {$db->queries} queries used. -->";
?>
