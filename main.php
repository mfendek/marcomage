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
	$all_colors = array("RosyBrown"=>"#bc8f8f", "DeepSkyBlue"=>"#00bfff", "DarkSeaGreen"=>"#8fbc8f", "DarkRed"=>"#8b0000", "HotPink"=>"#ff69b4", "LightBlue"=>"#add8e6", "LightGreen"=>"#90ee90", "Gainsboro"=>"#dcdcdc", "DeepSkyBlue"=>"#00bfff", "DarkGoldenRod"=>"#b8860b");
	
	require_once('CDatabase.php');
	require_once('CLogin.php');
	require_once('CScore.php');
	require_once('CCard.php');
	require_once('CDeck.php');
	require_once('CGame.php');
	require_once('CNovels.php');
	require_once('CSettings.php');
	require_once('CChat.php');
	require_once('CHtml.php');
	require_once('CPlayer.php');
	require_once('CMessage.php');
	require_once('CPost.php');
	require_once('CThread.php');
	require_once('CForum.php');
	require_once('utils.php');
	require_once('Presentation.php');
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
	$html = new CHtml($db);
	$noveldb = new CNovels($db);
	$forum = new CForum($db);

	$current = "Page"; // set a meaningful default

	$session = $logindb->Login();
	
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
			elseif (!isset($_POST['NewUsername']) || !isset($_POST['NewPassword']) || !isset ($_POST['NewPassword2']) || $_POST['NewUsername'] == '' || $_POST['NewPassword'] == '' || $_POST['NewPassword2'] == '')
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
		}
		else

		// verify login privilege
		if( !$access_rights[$player->Type()]["login"] )
		{
			$session = false;
			$current = "Page";
			$warning = "This user is not permitted to log in.";
		}
		else
	
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
					if (!$game) { /*$error = 'No such game!';*/ $current = 'Game'; break; }
					
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
					if (!$game) { /*$error = 'No such game!';*/ $current = 'Game'; break; }
					
					// check if this user is allowed to send messages in this game
					if ($player->Name() != $game->Name1() and $player->Name() != $game->Name2()) { $current = 'Game'; break; }
					
					// do not post empty messages (prevents accidental send)
					if ($msg == '') { /*$error = 'You can't send empty chat messages.';*/ $current = 'Game'; break; }
					
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
					
					if ($result == 'OK') $information = "You have discarded a card.";
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
						if ($game->State == 'finished')
						{
							$player1 = $game->Name1();
							$player2 = $game->Name2();
							$score1 = $scoredb->GetScore($player1);
							$score2 = $scoredb->GetScore($player2);
							$data = &$game->GameData;
							
							if ($data->Winner == $player1) { $score1->ScoreData->Wins++; $score2->ScoreData->Losses++; }
							elseif ($data->Winner == $player2) { $score2->ScoreData->Wins++; $score1->ScoreData->Losses++; }
							else {$score1->ScoreData->Draws++; $score2->ScoreData->Draws++; }
							
							$score1->SaveScore();
							$score2->SaveScore();
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
					$current = 'Details';
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
					if (!$deck) { $error = 'Deck '.$deckname.' does not exist!'; $current = 'Details'; break; }
					
					// check if the deck is ready (all 45 cards)
					if (!$deck->isReady()) { $error = 'Deck '.$deckname.' is not yet ready for gameplay!'; $current = 'Details'; break; }
					
					// check if such opponent exists
					if (!$playerdb->GetPlayer($opponent)) { $error = 'Player '.htmlencode($opponent).' does not exist!'; $current = 'Details'; break; }
					
					// check if that opponent was already challenged, or if there is a game already in progress
					if ($gamedb->GetGame2($player->Name(), $opponent)) { $error = 'You are already playing against '.htmlencode($opponent).'!'; $current = 'Games'; break; }
					
					// check if you are within the MAX_GAMES limit
					if (count($gamedb->ListActiveGames($player->Name())) + count($gamedb->ListChallengesFrom($player->Name())) + count($gamedb->ListChallengesTo($player->Name())) >= MAX_GAMES) { $error = 'Too many games / challenges! Please resolve some.'; $current = 'Challenges'; break; }
					
					// check challenge text length
					if (strlen($_POST['Content']) > CHALLENGE_LENGTH) { $error = "Message too long"; $current = "Details"; break; }
					
					// create a new challenge
					$game = $gamedb->CreateGame($player->Name(), $opponent, $deck->DeckData);
					if (!$game) { $error = 'Failed to create new game!'; $current = 'Details'; break; }
					
					$res = $messagedb->SendChallenge($player->Name(), $opponent, $_POST['Content'], $game->ID());
					if (!$res) { $error = 'Failed to create new challenge!'; $current = 'Details'; break; }
					
					$information = 'You have challenged '.htmlencode($opponent).'. Waiting for reply.';
					$current = 'Details';
					break;
				}
				
				if ($message == 'withdraw_challenge') // Players -> Cancel
				{
					//FIXME: uses names for game identification
					
					$opponent = postdecode(array_shift(array_keys($value)));
					$_POST['cur_player'] = $opponent;
					
					// check if such opponent exists
					if (!$playerdb->GetPlayer($opponent)) { $error = 'Player '.htmlencode($opponent).' does not exist!'; $current = 'Details'; break; }
					
					$game = $gamedb->GetGame2($player->Name(), $opponent);
					
					// check if the challenge exists
					if (!$game) { $error = 'No such challenge!'; $current = 'Details'; break; }
					
					// check if the game is a a challenge (and not a game in progress)
					if ($game->State != 'waiting') { $error = 'Game already in progress!'; $current = 'Details'; break; }
					
					// delete t3h challenge/game entry
					$gamedb->DeleteGame2($player->Name(), $opponent);
					$chatdb->DeleteChat($game->ID());
					$messagedb->CancelChallenge($game->ID());
					
					$information = 'You have withdrawn a challenge.';
					$current = 'Details';
					break;
				}
				
				if ($message == 'withdraw_challenge2') // Challenges -> Cancel
				{
					//FIXME: uses names for game identification
					
					$opponent = postdecode(array_shift(array_keys($value)));
					$_POST['cur_player'] = $opponent;
					
					// check if such opponent exists
					if (!$playerdb->GetPlayer($opponent)) { $error = 'Player '.htmlencode($opponent).' does not exist!'; $current = 'Details'; break; }
					
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
					
					$current_location = $_POST['CurrentLocation'];
					
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
					
					$current_location = $_POST['CurrentLocation'];
					
					$current = 'Message_details';
					break;
				}
				
				if ($message == 'message_delete') // delete message
				{
					$messageid = array_shift(array_keys($value));
					
					$message = $messagedb->GetMessage($messageid, $player->Name());
					
					if (!$message) { $error = "No such message!"; $current = "Challenges"; break; }
					
					$current_location = $_POST['CurrentLocation'];
					
					$current = 'Message_details';
					break;
				}
				
				if ($message == 'message_delete_confirm') // delete message confirmation
				{
					$messageid = array_shift(array_keys($value));
					
					$message = $messagedb->DeleteMessage($messageid, $player->Name());
					
					if (!$message) { $error = "No such message!"; $current = "Challenges"; break; }
					
					$current_location = $_POST['CurrentLocation'];
					$information = "Message deleted";
					
					$current = 'Challenges';
					break;
				}
				
				if ($message == 'message_cancel') // cancel new message creation
				{
					$current_location = $_POST['CurrentLocation'];
					
					$current = 'Challenges';
					break;
				}
				
				if ($message == 'message_send') // send new message
				{
					$recipient = $_POST['Recipient'];
					$author = $_POST['Author'];
					
					// check access rights
					if (!$access_rights[$player->Type()]["messages"]) { $error = 'Access denied.'; $current = 'Challenges'; break; }
				
					if (($_POST['Subject'] == "") AND ($_POST['Content'] == "")) { $error = "No message input specified"; $current = "Message_new"; break; }
					
					if (strlen($_POST['Content']) > MESSAGE_LENGTH) { $error = "Message too long"; $current = "Message_new"; break; }
				
					$message = $messagedb->SendMessage($_POST['Author'], $_POST['Recipient'], $_POST['Subject'], $_POST['Content']);
					
					if (!$message) { $error = "Failed to send message"; $current = "Challenges"; break; }
					
					$current_location = "sent_mail";
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
					$current = 'Challenges';
					break;
				}
				
				if ($message == 'sent_mail') // view messages from player
				{
					$current = 'Challenges';
					break;
				}
				
 				if ($message == 'all_mail') // view messages from player
 				{
 					// check access rights
 					if (!$access_rights[$player->Type()]["see_all_messages"]) { $error = 'Access denied.'; $current = 'Challenges'; break; }
 																										
 					$current = 'Challenges';
 					break;
 				}
				
				$temp = array("asc" => "ASC", "desc" => "DESC");
				foreach($temp as $type => $order_val)
				{
					if ($message == 'mes_ord_'.$type) // select ascending or descending order in message list
					{
						$condition = array_shift(array_keys($value));
						$order = $order_val;
						
						// preserve filter configuration
						$cur_filter = $_POST['CurrentFilter'];
						$cur_filter_val = $_POST['CurrentFilterVal'];
						$current_location = $_POST['CurrentLocation'];
						
						$current = "Challenges";
						
						break;
					}
				}
				
				if ($message == 'message_filter_name') // use name filter
				{
					$cur_filter = "Name";
					$cur_filter_val = postdecode($_POST['name_filter']);
					$current_location = $_POST['CurrentLocation'];
					
					$current = 'Challenges';
					break;
				}
				
				if ($message == 'message_filter_date') // use name filter
				{
				
					$cur_filter = "Created";
					$cur_filter_val = postdecode($_POST['date_filter']);
					$current_location = $_POST['CurrentLocation'];
					
					$current = 'Challenges';
					break;
				}
				// end message-related messages
				
				// view user details
				if ($message == 'user_details') // Players -> User details
				{
					$opponent = postdecode(array_shift(array_keys($value)));
					
					$_POST['Details'] = $opponent;
					$current = 'Details';
					break;
				}
				
				if ($message == 'change_access') // Players -> User details -> Change access rights
				{
					$opponent = postdecode(array_shift(array_keys($value)));
					
					$_POST['Details'] = $opponent;
					
					// check access rights
					if (!$access_rights[$player->Type()]["change_rights"]) { $error = 'Access denied.'; $current = 'Details'; break; }
										
					$target = $playerdb->GetPlayer($opponent);
					$target->ChangeAccessRights($_POST['new_access']);
					
					$information = 'Access rights changed.';
								
					$current = 'Details';
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
					$current = 'Deck';
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
					
					$current = 'Deck';
					
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
				
				if ($message == 'filter') // Decks -> Modify this deck -> Apply filters
				{
					$_POST['CostFilter'] = $_POST['selected_cost'];
					$_POST['KeywordFilter'] = $_POST['selected_keyword'];
					$_POST['ClassFilter'] = $_POST['selected_rarity'];
					$_POST['AdvancedFilter'] = $_POST['advanced_filter'];
                    
					$current = 'Deck';
					
					break;
				}
				
				if ($message == 'return_card') // Decks -> Modify this deck -> Return
				{
					$cardid = (int)postdecode(array_shift(array_keys($value)));
					
					// download deck, download card
					$deckname = $_POST['CurrentDeck'];
					$deck = $player->GetDeck($deckname);
					$card = $carddb->GetCard($cardid);
					
					$current = 'Deck';
					
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
					$current = 'Deck';
					
					break;
				}
				
				if ($message == 'reset_deck_confirm') // Decks -> Modify this deck -> Confirm reset
				{
					$deckname = $_POST['CurrentDeck'];
					$deck = $player->GetDeck($deckname);
					
					$current = 'Deck';
					
					$deck->DeckData->Common   = array(1=>0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
					$deck->DeckData->Uncommon = array(1=>0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
					$deck->DeckData->Rare     = array(1=>0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
					
					$deck->SaveDeck();
					
					break;
				}
				
				if ($message == 'randomize_deck_prepare') // Decks -> Randomize
				{
					// only symbolic functionality... rest is handled below
					$deckname = $_POST['CurrentDeck'];
					$current = 'Deck';
					
					break;
				}
				
				if ($message == 'randomize_deck_confirm') // Decks -> Modify this deck -> Confirm randomize
				{
					$deckname = $_POST['CurrentDeck'];
					$deck = $player->GetDeck($deckname);
					
					$current = 'Deck';
					
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
					
					$current = 'Deck';
					
					$common_cards = $carddb->GetList("Common");
					$uncommon_cards = $carddb->GetList("Uncommon");
					$rare_cards = $carddb->GetList("Rare");
					
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
						$current = 'Deck';
					}
					elseif ($newname == '')
					{
						$error = 'Cannot change deck name, invalid input.';
						$current = 'Deck';
					}
					else
					{
						$deck = $player->GetDeck($curname);
						
						if ($deck != false)
						{
							$deck->RenameDeck($newname);
							$_POST['CurrentDeck'] = $newname;
							
							$information = "Deck saved.";
							$current = 'Deck';
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
				
				//these two cases are similiar - one is the page navigation that is at the top and the other one is at the bottom. We cannot use same input buttons a selects for both navigations, because then user would have to select desired page at BOTH select elements, otherwise a conflict is inevitable. Therefore we split this similiar case into two, separate ones.				
				for ($i = 1; $i <= 2; $i++)
					if ($message == 'Jump'.$i) // Novels -> select page (Jump to page)
					{
						$_POST['current_page'] = $_POST['jump_to_page'.$i];
						
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
					if ((@$_POST['Birthyear'] != "") AND (@$_POST['Birthmonth'] != "") AND (@$_POST['Birthday'] != ""))
					{
						$temp_error = CheckDateInput($_POST['Birthyear'], $_POST['Birthmonth'], $_POST['Birthday']);
						
						if ($temp_error != "") $error = $temp_error;
						elseif (intval(date("Y")) <= $_POST['Birthyear'])
							$error = "Invalid birthdate";
						else
						{
							$player->ChangeSetting("Birthdate", $_POST['Birthyear']."-".$_POST['Birthmonth']."-".$_POST['Birthday']);
							$information = "Settings saved";
						}
					}
					else
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
										
					$code_name = time().$player->Name().'.'.$code_type;
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
					if (!$access_rights[$player->Type()]["change_all_avatar"]) { $error = 'Access denied.'; $current = 'Details'; break; }
					
					$former_name = $opponent->GetSetting("Avatar");
					$former_path = 'img/avatars/'.$former_name;
					
					if ((file_exists($former_path)) and ($former_name != "noavatar.jpg")) unlink($former_path);
					$opponent->ChangeSetting("Avatar", "noavatar.jpg");
					$information = "Avatar cleared";
					
					$current = 'Details';
					
					break;
				}
				
				if ($message == 'changepasswd') //change password
				{
					if (!isset($_POST['NewPassword']) || !isset ($_POST['NewPassword2']) || $_POST['NewPassword'] == '' || $_POST['NewPassword2'] == '')
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
					$current_page = $_POST['page_selector'];
					
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
					
					if (($_POST['Title'] == "") OR ($_POST['Content'] == "")) { $error = "Invalid input"; $current = "New_thread"; break; }
					
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
				
				//these two cases are similiar - one is the page navigation that is at the top and the other one is at the bottom. We cannot use same input buttons a selects for both navigations, because then user would have to select desired page at BOTH select elements, otherwise a conflict is inevitable. Therefore we split this similiar case into two, separate ones.
				for ($i = 1; $i <= 2; $i++)
					if ($message == 'thread_select_page'.$i) // forum -> section -> thread -> select page with select element
					{
						$thread_id = $_POST['CurrentThread'];
						$current_page = $_POST['thread_page_selector'.$i];
						
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
														
					if ($_POST['Content'] == "") { $error = "Invalid input"; $current = "New_post"; break; }
					
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
														
					if ($_POST['Title'] == "") { $error = "Invalid input"; $current = "Thread_details"; break; }
					
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
														
					if ($_POST['Content'] == "") { $error = "Invalid input"; $current = "Edit_post"; break; }
					
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
						$condition = postdecode(array_shift(array_keys($value)));
						$order = $order_val;
						
						// preserve filter configuration
						$filter_cond = $_POST['CurrentFilter'];
						
						$current = "Players";
						
						break;
					}
				}
				
				if ($message == 'filter_players') // use player filter in players list
				{
					$filter_cond  = $_POST['player_filter'];
					
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

	// clear all used temporary variables ... because php uses weird variable scope -_-
	unset($list);
	unset($deck);
	unset($card);
	unset($game);
	unset($gameid);
	unset($opponent);
	
	/*	</section>	*/
	
	/*	<section: PRESENTATION>	*/
		
	$param['error'] = ((isset($error)) ? $error : "");
	$param['warning'] = ((isset($warning)) ? $warning : "");
	$param['information'] = ((isset($information)) ? $information : "");
	
	if (!$session)
	{
		$menu = Generate_LoginBox($param);
	}
	else
	{
		$param['PlayerName'] = $player->Name();
		$param['PreviousLogin'] = $player->PreviousLogin();
		$param['Current'] = $current;
		$param['NumChallenges'] = count($gamedb->ListChallengesTo($player->Name()));
		$param['NumUnread'] = $messagedb->CountUndreadMessages($player->Name());
		$param['IsSomethingNew'] = $forum->IsSomethingNew($player->PreviousLogin());
		
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
		
		$param['NumGames'] = $temp;
		
		$menu = Generate_NavigationBar($param);
	}
	
	// now display current inner-page contents
	
	/* -------- *
	 * | PAGE | *
	 * -------- */
	if ($current == "Page")
	{
		//decide what screen is default (depends on whether the user is logged in)
		$default_page = ( !$session ) ? 'Main' : 'News';
		$selected = isset($_POST['WebPage']) ? postdecode(array_shift(array_keys($_POST['WebPage']))) : $default_page;
		$param['Page']['selected'] = $selected;
		
		if     ($selected == 'Main'   ) $param['Page']['html'] = $html->MainPage();
		elseif ($selected == 'News'   ) $param['Page']['html'] = $html->NewsPage();
		elseif ($selected == 'Cardmod') $param['Page']['html'] = $html->ModCardsPage();
		elseif ($selected == 'Help'   ) $param['Page']['html'] = $html->HelpPage();
		elseif ($selected == 'Faq'    ) $param['Page']['html'] = $html->FaqPage();
		elseif ($selected == 'Credits') $param['Page']['html'] = $html->Credits();
				
		$content = Generate_Page($param);
	}
	
	/* ---------------- *
	 * | REGISTRATION | *
	 * ---------------- */
	elseif ($current == "Registration")
	{
		$content = Generate_Registration();
	}
	
	/* -------- *
	 * | DECK | *
	 * -------- */
	elseif ($current == "Deck")
	{
		$param['Colors'] = $all_colors;
		
		$currentdeck = $param['Deck']['CurrentDeck'] = $_POST['CurrentDeck'];
		$classfilter = $param['Deck']['ClassFilter'] = $_POST['ClassFilter'];
		$costfilter = $param['Deck']['CostFilter'] = $_POST['CostFilter'];
		$keywordfilter = $param['Deck']['KeywordFilter'] = $_POST['KeywordFilter'];
		$advancedfilter = $param['Deck']['AdvancedFilter'] = $_POST['AdvancedFilter'];
		
		// download the neccessary data
		$deck = $player->GetDeck($currentdeck);
		
		$param['Deck']['reset'] = isset($_POST["reset_deck_prepare"]);
		$param['Deck']['randomize'] = isset($_POST["randomize_deck_prepare"]);
		
		// load card display settings
		$param['Deck']['c_text'] = $player->GetSetting("Cardtext");
		$param['Deck']['c_img'] = $player->GetSetting("Images");
		$param['Deck']['c_keywords'] = $player->GetSetting("Keywords");
		$param['Deck']['c_oldlook'] = $player->GetSetting("OldCardLook");
		
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
		
		foreach ($avg as $type => $value) $res[$type] = $avg[$type]['Common'] + $avg[$type]['Uncommon'] + $avg[$type]['Rare'];
		
		$param['Deck']['Res'] = $res;
		
		$list = $carddb->GetList($classfilter, $keywordfilter, $costfilter, $advancedfilter);
		
		foreach($list as $list_index => $cardid)
		{
			// check if the card isn't already present in the deck
			if (!(array_search($cardid, $deck->DeckData->$classfilter) !== false))
				$param['Deck']['CardList'][$list_index] = $cardid;
		}
		
		if ($deck->DeckData->Count($classfilter) < 15) $param['Deck']['Take'] = true;
		else $param['Deck']['Take'] = false;
		
		foreach (array('Common'=>'Lime', 'Uncommon'=>$all_colors["DarkRed"], 'Rare'=>'Yellow') as $class => $classcolor)
		{
			$param['Deck']['DeckCards'][$class] = $deck->DeckData->$class;
		}
		
		$content = Generate_Deck($param);
	}
	
	/* --------- *
	 * | DECKS | *
	 * --------- */
	elseif ($current == "Decks")
	{
		$param['Decks']['list'] = $player->ListDecks();
		
		$content = Generate_Decks($param);
	}
	
	/* ----------- *
	 * | PLAYERS | *
	 * ----------- */
	elseif ($current == "Players")
	{
		// begin ordering
		
		// create defaults for not used ordering
		$columns = array(1 => "Rank2", 2 => "Username", 3 => "Country", 4 => "Free slots");
		
		foreach($columns as $index => $column_name)
		{
			$bname[$column_name] = "desc";
			$val[$column_name] = "\/";
		}
		
		// defaults for list ordering
		if ($order == "") $order = "DESC";
		if ($condition == "") $condition = "Rank2";
		
		if ($order == "ASC")
		{
			$bname[$condition] = "desc";
			$val[$condition] = "\/";
		}
		elseif ($order == "DESC")
		{
			$bname[$condition] = "asc";
			$val[$condition] = "/\\";
		}
		
		$param['Players']['order'] = $order;
		$param['Players']['condition'] = $condition;
		$param['Players']['bname'] = $bname;
		$param['Players']['val'] = $val;
		
		// end ordering
		
		$param['Players']['CurrentFilter'] = $filter = ((isset($filter_cond)) ? $filter_cond : "none");
		
		$param['Players']['PlayerName'] = $player->Name();
		
		// get the list of all existing players; the contents are a numbered array of (Username, Wins, Losses, Draws, Last Query, Free slots, Avatar, Country)
		$param['Players']['list'] = $list = $playerdb->ListPlayers($player->GetSetting("Showdead"), $filter, $condition, $order);
		
		// check for active decks
		$param['Players']['active'] = ( count($player->ListReadyDecks()) > 0 );
		
		//retrieve layout setting
		$param['Players']['show_nationality'] = $player->GetSetting("Nationality");
		$show["Online"] = $param['Players']['Online'] = $player->GetSetting("Online");
		$show["Offline"] = $param['Players']['Offline'] = $player->GetSetting("Offline");
		$show["Inactive"] = $param['Players']['Inactive'] = $player->GetSetting("Inactive");
		$show["Dead"] = $param['Players']['Dead'] = $player->GetSetting("Dead");
		
		$activegames = $gamedb->ListActiveGames($player->Name());
		$challengesfrom = $gamedb->ListChallengesFrom($player->Name());
		$challengesto = $gamedb->ListChallengesTo($player->Name());
		$endedgames = $gamedb->ListEndedGames($player->Name());
		$pendinggames = count($activegames) + count($challengesfrom) + count($challengesto);
		
		$param['Players']['pendinggames'] = $pendinggames;
		
		$param['Players']['messages'] = ($access_rights[$player->Type()]["messages"]);
		$param['Players']['send_challenges'] = ($access_rights[$player->Type()]["send_challenges"]);
		
		// for each player, display their name, score, and if conditions are met, also display the challenge button
		foreach ($list as $i => $data)
		{
			// choose name color according to inactivity time
			if     (time() - $data['Last Query'] > (60*60*24*7*3))
			{ $namecolor ='style="color: gray;"'; $player_type = "Dead"; } // players are considered 'dead' after 3 weeks of inactivity
			elseif (time() - $data['Last Query'] > (60*60*24*7*1))
			{ $namecolor ='style="color: maroon;"'; $player_type = "Inactive"; } // players are considered 'not interested' after 1 week of inactivity
			elseif (time() - $data['Last Query'] > (60*10))
			{ $namecolor ='style="color: red;"'; $player_type = "Offline"; } // players are considered 'offline' after 10 minutes of inactivity
			else //(time(0 - $data['Last Query'] <= (60*10))
			{ $namecolor ='style="color: lime;"'; $player_type = "Online"; } // default 'online' players
							
			$opponent = $data['Username'];
			$param['Players'][$opponent]['player_type'] = $player_type;
			$param['Players'][$opponent]['namecolor'] = $namecolor;
									
			$param['Players'][$opponent]['challenged'] = (array_search(array('Player1' => $player->Name(), 'Player2' => $opponent), $challengesfrom) !== false);
			$param['Players'][$opponent]['playingagainst'] = (array_search(array('Player1' => $player->Name(), 'Player2' => $opponent), $activegames) !== false);
			$param['Players'][$opponent]['waitingforack'] = (array_search(array('Player1' => $player->Name(), 'Player2' => $opponent), $endedgames) !== false);
		}
		
		$content = Generate_Players($param);
	}
	
	/* ----------- *
	 * | DETAILS | *
	 * ----------- */
	 
	elseif ($current == "Details")
	{
		$param['Colors'] = $all_colors;
		
		// retrieve name of a player we are currently viewing
		$cur_player = (isset($_POST['Details'])) ? $_POST['Details'] : $_POST['cur_player'];
	
		$p = $playerdb->GetPlayer($cur_player);
		$param['Details']['PlayerName'] = $p->Name();
		$param['Details']['PlayerType'] = $p->Type();
		$param['Details']['CurPlayerName'] = $player->Name();
		$param['Details']['messages'] = ($access_rights[$player->Type()]["messages"]);
		$param['Details']['send_challenges'] = ($access_rights[$player->Type()]["send_challenges"]);
		$param['Details']['change_rights'] = ($access_rights[$player->Type()]["change_rights"]);
		$param['Details']['system_notification'] = ($access_rights[$player->Type()]["system_notification"]);
		$param['Details']['change_all_avatar'] = ($access_rights[$player->Type()]["change_all_avatar"]);
		$param['Details']['Timezone'] = $player->GetSetting("Timezone");
		
		$param['Details']['decks'] = $player->ListReadyDecks();
		
		$activegames = $gamedb->ListActiveGames($player->Name());
		$challengesfrom = $gamedb->ListChallengesFrom($player->Name());
		$challengesto = $gamedb->ListChallengesTo($player->Name());
		$endedgames = $gamedb->ListEndedGames($player->Name());
		
		$param['Details']['pendinggames'] = count($activegames) + count($challengesfrom) + count($challengesto);
		
		$param['Details']['challenged'] = (array_search(array('Player1' => $player->Name(), 'Player2' => $cur_player), $challengesfrom) !== false);
		$param['Details']['playingagainst'] = (array_search(array('Player1' => $player->Name(), 'Player2' => $cur_player), $activegames) !== false);
		$param['Details']['waitingforack'] = (array_search(array('Player1' => $player->Name(), 'Player2' => $cur_player), $endedgames) !== false);
		
		if ($param['Details']['challenged']) $param['Details']['challenge'] = $messagedb->GetChallenge($player->Name(), $cur_player);
		
		$current_settings = $param['Details']['current_settings'] = $p->GetUserSettings();
		if ($current_settings["Birthdate"] != "0000-00-00")
		{
			$param['Details']['current_settings']["Age"] = $settingdb->CalculateAge($current_settings["Birthdate"]);
			$param['Details']['current_settings']["Sign"] = $settingdb->CalculateSign($current_settings["Birthdate"]);
			$param['Details']['current_settings']["Birthdate"] = date("d-m-Y", strtotime($current_settings["Birthdate"]));
		}
		else
		{
			$param['Details']['current_settings']["Age"] = "Unknown";
			$param['Details']['current_settings']["Sign"] = "Unknown";
			$param['Details']['current_settings']["Birthdate"] = "Unknown";
		}
		
		$content = Generate_Details($param);
	}
	
	/* -------------- *
	 * | CHALLENGES | *
	 * -------------- */
	elseif ($current == "Challenges")
	{
		$decks = $param['Challenges']['decks'] = $player->ListReadyDecks();
		$param['Challenges']['startedgames'] = count($gamedb->ListActiveGames($player->Name())) + count($gamedb->ListChallengesFrom($player->Name()));
		$param['Challenges']['Timezone'] = $player->GetSetting("Timezone");
		
		if (isset($_POST['incoming'])) $current_subsection = "incoming";
		elseif (isset($_POST['outgoing'])) $current_subsection = "outgoing";
		elseif (!isset($current_subsection)) $current_subsection = "incoming";
		
		$function_type = (($current_subsection == "incoming") ? "ListChallengesTo" : "ListChallengesFrom");
		$param['Challenges']['challenges'] = $messagedb->$function_type($player->Name());
		$param['Challenges']['current_subsection'] = $current_subsection;
		
		if (isset($_POST['inbox'])) $current_location = "inbox";
		elseif (isset($_POST['sent_mail'])) $current_location = "sent_mail";
		elseif (isset($_POST['all_mail'])) $current_location = "all_mail";
		elseif (!isset($current_location)) $current_location = "inbox";
		
		$current_order = (isset($order)) ? $order : "DESC"; // default ordering
		$current_condition = (isset($condition)) ? $condition : "Created"; // default order condition
		$current_filter = (isset($cur_filter)) ? $cur_filter : ""; // default filter
		$filter_val = (isset($cur_filter_val)) ? $cur_filter_val : ""; // default filter value
		
 		$list_type = ($current_location == "all_mail") ? "ListAllMessages" : (($current_location == "sent_mail") ? "ListMessagesFrom" : "ListMessagesTo");
 		$name_type = ($current_location == "all_mail") ? "ListAllNames" : (($current_location == "sent_mail") ? "ListNamesFrom" : "ListNamesTo");
		
		$list = $messagedb->$list_type($player->Name(), $current_filter, $filter_val, $current_condition, $current_order);
		$name_list = $messagedb->$name_type($player->Name());
		
		$param['Challenges']['messages'] = $list;
		$param['Challenges']['current_location'] = $current_location;
		$param['Challenges']['current_filter'] = $current_filter;
		$param['Challenges']['current_filter_val'] = $filter_val;
		$param['Challenges']['current_order'] = $current_order;
		$param['Challenges']['current_condition'] = $current_condition;
		$param['Challenges']['timesections'] = $messagedb->Timesections();
		$param['Challenges']['name_filter'] = $name_list;
		
		$param['Challenges']['send_messages'] = ($access_rights[$player->Type()]["messages"]);
		$param['Challenges']['accept_challenges'] = ($access_rights[$player->Type()]["accept_challenges"]);
		$param['Challenges']['see_all_messages'] = ($access_rights[$player->Type()]["see_all_messages"]);
		
		$content = Generate_Challenges($param);
	}
	
	elseif ($current == "Message_details")
	{
		$param['Message_details']['Author'] = $message['Author'];
		$param['Message_details']['Recipient'] = $message['Recipient'];
		$param['Message_details']['Subject'] = $message['Subject'];
		$param['Message_details']['Content'] = $message['Content'];
		$param['Message_details']['MessageID'] = $messageid;
		$param['Message_details']['delete'] = !isset($_POST["message_delete"]);
		$param['Message_details']['messages'] = ($access_rights[$player->Type()]["messages"]);
		
		if (!isset($current_location)) $current_location = "inbox";
		
		$param['Message_details']['current_location'] = $current_location;
		
		$timezone = $player->GetSetting("Timezone");
		
		//recalculate time to players perspective
		$time = strtotime($message['Created']);
		$offset = abs($timezone);
		$sign = ($timezone > 0) ? '-' : (($timezone < 0) ? '+' : '');
		$date = ZoneTime($time, "Etc/GMT".$sign.$offset, "G:i:s | F j, y");
		
		$param['Message_details']['Created'] = $date;
		$param['Message_details']['Stamp'] = ($time % 4) + 1; // hash function - assign stamp picture
		
		$content = Generate_Message_details($param);
	}
	
	elseif ($current == "Message_new")
	{
		$param['Message_new']['Author'] = $author;
		$param['Message_new']['Recipient'] = $recipient;
		$param['Message_new']['Content'] = ((isset($_POST['Content'])) ? $_POST['Content'] : '');
 		$param['Message_new']['Subject'] = ((isset($_POST['Subject'])) ? $_POST['Subject'] : '');
		
		$content = Generate_Message_new($param);
	}
	
	/* --------- *
	 * | GAMES | *
	 * --------- */
	elseif ($current == "Games")
	{
		$param['Games']['PlayerName'] = $player->Name();
		$list = $gamedb->ListActiveGames($player->Name());
		
		if (count($list) > 0)
		{
			foreach ($list as $i => $data)
			{
				$game = $gamedb->GetGame2($data['Player1'], $data['Player2']);
				$opponent = ($data['Player1'] != $player->Name()) ? $data['Player1'] : $data['Player2'];
				
				$param['Games']['list'][$i]['opponent'] = $opponent;
				$param['Games']['list'][$i]['active'] = $playerdb->GetPlayer($opponent)->isOnline();
				$param['Games']['list'][$i]['ready'] = $game->GameData->Current == $player->Name();
				$param['Games']['list'][$i]['gameid'] = $game->ID();
				$param['Games']['list'][$i]['gamestate'] = $game->State;
				$param['Games']['list'][$i]['isdead'] = $playerdb->isDead($opponent);
			}
		}
		
		$content = Generate_Games($param);
	}
	
	/* -------- *
	 * | GAME | *
	 * -------- */
	elseif ($current == "Game") 
	{
		$gameid = $_POST['CurrentGame'];
		
		// prepare the neccessary data
		$game = $gamedb->GetGame($gameid);
		$player1 = $game->Name1();
		$player2 = $game->Name2();
		$data = &$game->GameData;
		
		$opponent = $playerdb->GetPlayer(($player1 != $player->Name()) ? $player1 : $player2);
		$mydata = &$data->Player[$player->Name()];
		$hisdata = &$data->Player[$opponent->Name()];
		
		$param['Colors'] = $all_colors;
		$param['Game']['CurrentGame'] = $gameid;
		
 		$param['Game']['chat'] = ($access_rights[$player->Type()]["chat"]);
		
		//load needed settings
		$param['Game']['c_text'] = $player->GetSetting("Cardtext");
		$param['Game']['c_img'] = $player->GetSetting("Images");
		$param['Game']['c_keywords'] = $player->GetSetting("Keywords");
		$param['Game']['c_oldlook'] = $player->GetSetting("OldCardLook");
		
		$param['Game']['minimize'] = $player->GetSetting("Minimize");
		$param['Game']['mycountry'] = $player->GetSetting("Country");
		$param['Game']['hiscountry'] = $opponent->GetSetting("Country");
		
		$param['Game']['GameState'] = $game->State;
		$param['Game']['Round'] = $data->Round;
		$param['Game']['Outcome'] = $data->Outcome;
		$param['Game']['Winner'] = $data->Winner;
		$param['Game']['PlayerName'] = $player->Name();
		$param['Game']['OpponentName'] = $opponent->Name();
		$param['Game']['Current'] = $data->Current;
		$param['Game']['Timestamp'] = $data->Timestamp;
		
		$param['Game']['MyHand'] = $mydata->Hand;
		$param['Game']['MyNewCards'] = $mydata->NewCards;
		$param['Game']['MyBricks'] = $mydata->Bricks;
		$param['Game']['MyGems'] = $mydata->Gems;
		$param['Game']['MyRecruits'] = $mydata->Recruits;
		$param['Game']['MyQuarry'] = $mydata->Quarry;
		$param['Game']['MyMagic'] = $mydata->Magic;
		$param['Game']['MyDungeons'] = $mydata->Dungeons;
		$param['Game']['MyTower'] = $mydata->Tower;
		$param['Game']['MyWall'] = $mydata->Wall;
		$param['Game']['MyDisCards'] = $mydata->DisCards;
		$param['Game']['MyLastCard'] = $mydata->LastCard;
		$param['Game']['MyLastAction'] = $mydata->LastAction;
		$param['Game']['MyLastMode'] = $mydata->LastMode;
		
		$param['Game']['HisHand'] = $hisdata->Hand;
		$param['Game']['HisNewCards'] = $hisdata->NewCards;
		$param['Game']['HisBricks'] = $hisdata->Bricks;
		$param['Game']['HisGems'] = $hisdata->Gems;
		$param['Game']['HisRecruits'] = $hisdata->Recruits;
		$param['Game']['HisQuarry'] = $hisdata->Quarry;
		$param['Game']['HisMagic'] = $hisdata->Magic;
		$param['Game']['HisDungeons'] = $hisdata->Dungeons;
		$param['Game']['HisTower'] = $hisdata->Tower;
		$param['Game']['HisWall'] = $hisdata->Wall;
		$param['Game']['HisDisCards'] = $hisdata->DisCards;
		$param['Game']['HisLastCard'] = $hisdata->LastCard;
		$param['Game']['HisLastAction'] = $hisdata->LastAction;
		$param['Game']['HisLastMode'] = $hisdata->LastMode;
		
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
			if ($game_list->State == 'finished') $color = $all_colors["HotPink"]; // when game is finished
			
			$param['Game']['GameList'][$i]['Value'] = $game_list->ID();
			$param['Game']['GameList'][$i]['Content'] = 'vs. '.htmlencode($opponent_list);
			$param['Game']['GameList'][$i]['Selected'] = ($game_list->ID() == $_POST['CurrentGame']);
			$param['Game']['GameList'][$i]['Color'] = $color;
		}
		// - </quick game switching menu>
		
		// - <'jump to next game' button>
		
		$list = $gamedb->ListActiveGames($player->Name());
		
		$num_games_your_turn = 0;
		foreach ($list as $i => $names)
			if ($gamedb->GetGame2($names['Player1'], $names['Player2'])->GameData->Current == $player->Name()) $num_games_your_turn++;
		$param['Game']['num_games_your_turn'] = $num_games_your_turn;
		
		// - </'jump to next game' button>
		
		// - <game state indicator>
		$param['Game']['opp_isOnline'] = $opponent->isOnline();
		$param['Game']['opp_isDead'] = $opponent->isDead();
		$param['Game']['surrender'] = !isset($_POST["surrender"]);
		
		// your resources and tower
		$colors = array ('Quarry'=> '', 'Magic'=> '', 'Dungeons'=> '', 'Bricks'=> '', 'Gems'=> '', 'Recruits'=> '', 'Tower'=> '', 'Wall'=> '');
		foreach ($colors as $attribute => $color)
		{
			if ($mydata->Changes[$attribute] > 0) $colors[$attribute] = ' style="color: lime"';
			elseif ($mydata->Changes[$attribute] < 0) $colors[$attribute] = ' style="color: orange"';
		}
		
		$param['Game']['mycolors'] = $colors;
		
		// opponent's resources and tower
		
		$colors = array ('Quarry'=> '', 'Magic'=> '', 'Dungeons'=> '', 'Bricks'=> '', 'Gems'=> '', 'Recruits'=> '', 'Tower'=> '', 'Wall'=> '');
		foreach ($colors as $attribute => $color)
		{
			if ($hisdata->Changes[$attribute] > 0) $colors[$attribute] = ' style="color: lime"';
			elseif ($hisdata->Changes[$attribute] < 0) $colors[$attribute] = ' style="color: orange"';
		}
		
		$param['Game']['hiscolors'] = $colors;	
		
		// chatboard
		
		$param['Game']['display_avatar'] = $player->GetSetting("Avatargame");
		$param['Game']['correction'] = $player->GetSetting("Correction");
		
		$param['Game']['myavatar'] = $player->GetSetting("Avatar");
		$param['Game']['hisavatar'] = $opponent->GetSetting("Avatar");
		
		$messagelist = $param['Game']['messagelist'] = $chatdb->ListChatMessages($game->ID());
		if ($messagelist != NULL)
		{
			$order = $param['Game']['Chatorder'] = $player->GetSetting("Chatorder");
			$timezone = $param['Game']['Timezone'] = $player->GetSetting("Timezone");
		}
		
		$content = Generate_Game($param);
	}
	
	elseif ($current == "Deck_view") 
	{
		$gameid = $_POST['CurrentGame'];
		$game = $gamedb->GetGame($gameid);
		
		$param['Colors'] = $all_colors;
		
		$param['Deck_view']['CurrentGame'] = $gameid;
		$param['Deck_view']['deck']['Common'] = $game->GameData->Player[$player->Name()]->Deck->Common;
		$param['Deck_view']['deck']['Uncommon'] = $game->GameData->Player[$player->Name()]->Deck->Uncommon;
		$param['Deck_view']['deck']['Rare'] = $game->GameData->Player[$player->Name()]->Deck->Rare;
		
		//load needed settings
		$param['Deck_view']['c_text'] = $player->GetSetting("Cardtext");
		$param['Deck_view']['c_img'] = $player->GetSetting("Images");
		$param['Deck_view']['c_keywords'] = $player->GetSetting("Keywords");
		$param['Deck_view']['c_oldlook'] = $player->GetSetting("OldCardLook");
		
		$content = Generate_Deck_view($param);
	}
	
	/* ---------- *
	 * | NOVELS | *
	 * ---------- */
	 
	elseif ($current == "Novels")
	{
		$current_novel = ((isset($_POST['current_novel'])) ? $_POST['current_novel'] : "");
		$current_chapter = ((isset($_POST['current_chapter'])) ? $_POST['current_chapter'] : "");
		$current_page = ((isset($_POST['current_page'])) ? $_POST['current_page'] : "");
		
		$param['Novels']['current_novel'] = $current_novel;
		$param['Novels']['current_chapter'] = $current_chapter;
		$param['Novels']['current_page'] = $current_page;
		$param['Novels']['novelslist'] = $noveldb->GetNovelsList();		
		$param['Novels']['chapterslist'] = (($current_novel != "") ? $noveldb->GetChaptersList($current_novel) : null);
		$param['Novels']['ListPages'] = $noveldb->ListPages($current_novel, $current_chapter);
		$param['Novels']['PageContent']	= $noveldb->GetPageContent($current_novel, $current_chapter, $current_page);
		
		$content = Generate_Novels($param);
	}
	
	/* ------------ *
	 * | SETTINGS | *
	 * ------------ */
	 
	elseif ($current == "Settings")
	{
		$param['Settings']['current_settings'] = $player->GetSettings();
		$param['Settings']['countries'] = $settingdb->CountryNames();
		$param['Settings']['timezones'] = $settingdb->TimeZones();
		$param['Settings']['PlayerType'] = $player->Type();
		$param['Settings']['change_own_avatar'] = ($access_rights[$player->Type()]["change_own_avatar"]);
		
		//date is handled separately
		$birthdate = $param['Settings']['current_settings']["Birthdate"];
		if( $birthdate != "0000-00-00" )
		{
			$param['Settings']['current_settings']["Age"] = $settingdb->CalculateAge($birthdate);
			$param['Settings']['current_settings']["Sign"] = $settingdb->CalculateSign($birthdate);
			$param['Settings']['current_settings']["Birthdate"] = date("d-m-Y", strtotime($birthdate));
		}
		else
		{
			$param['Settings']['current_settings']["Age"] = "Unknown";
			$param['Settings']['current_settings']["Sign"] = "Unknown";
			$param['Settings']['current_settings']["Birthdate"] = "Unknown";
		}
		
		$content = Generate_Settings($param);
	}
	
	/* --------- *
	 * | FORUM | *
	 * --------- */
	 
	elseif ($current == "Forum")
	{
		$list = $param['Forum']['sections'] = $forum->ListSections();
		$param['Forum']['Timezone'] = $player->GetSetting("Timezone");
		
		foreach($list as $index => $data)
		{
			$param['Forum']['threadlist'][$index] = $forum->Threads->ListThreadsMain($data['SectionID']);
		}
		
		$content = Generate_Forum($param);
	}
	
	elseif ($current == "Section_details")
	{
		if (!isset($current_page)) $current_page = 0;
	
		$param['Section_details']['Section'] = $forum->GetSection($section_id);
		$param['Section_details']['Pages'] = $forum->Threads->CountPages($section_id);
		$param['Section_details']['CurrentPage'] = $current_page;
		$param['Section_details']['Timezone'] = $player->GetSetting("Timezone");		
		$param['Section_details']['threadlist'] = $forum->Threads->ListThreads($section_id, $current_page, "");
		
		$param['Section_details']['create_thread'] = ($access_rights[$player->Type()]["create_thread"]);
		
		$content = Generate_Section_details($param);
	}
	
	elseif ($current == "New_thread")
	{
		$param['New_thread']['Section'] = $forum->GetSection($section_id);
		$param['New_thread']['Content'] = ((isset($_POST['Content'])) ? $_POST['Content'] : "");
		$param['New_thread']['Title'] = ((isset($_POST['Title'])) ? $_POST['Title'] : "");
		
		$param['New_thread']['chng_priority'] = ($access_rights[$player->Type()]["chng_priority"]);
										
		$content = Generate_New_thread($param);
	}
	
	elseif ($current == "Thread_details")
	{
		if (!isset($current_page)) $current_page = 0;
	
		$param['Thread_details']['Thread'] = $thread_data = $forum->Threads->GetThread($thread_id);
		
		// retrieve section_id - cannot rely on hidden, because we allow to access threads direct from main page		
		$param['Thread_details']['Section'] = $forum->GetSection($thread_data['Section']);
		
		$param['Thread_details']['Pages'] = $forum->Threads->Posts->CountPages($thread_id);
		$param['Thread_details']['CurrentPage'] = $current_page;
		$param['Thread_details']['Timezone'] = $player->GetSetting("Timezone");		
		$param['Thread_details']['PostList'] = $forum->Threads->Posts->ListPosts($thread_id, $current_page);
		$param['Thread_details']['AvatarsList'] = $forum->Threads->Posts->ListPosts_Avatars($thread_id, $current_page);
		$param['Thread_details']['Delete'] = (isset($_POST['thread_delete']));
		$param['Thread_details']['DeletePost'] = ((isset($deleting_post)) ? $deleting_post : false);
		
		$param['Thread_details']['lock_thread'] = ($access_rights[$player->Type()]["lock_thread"]);
		$param['Thread_details']['del_all_thread'] = ($access_rights[$player->Type()]["del_all_thread"]);
		$param['Thread_details']['edit_thread'] = (($access_rights[$player->Type()]["edit_all_thread"]) OR ($access_rights[$player->Type()]["edit_own_thread"] AND $thread_data['Author'] == $player->Name()));
		$param['Thread_details']['create_post'] = ($access_rights[$player->Type()]["create_post"]);
		$param['Thread_details']['del_all_post'] = ($access_rights[$player->Type()]["del_all_post"]);
		$param['Thread_details']['edit_all_post'] = ($access_rights[$player->Type()]["edit_all_post"]);
		$param['Thread_details']['edit_own_post'] = ($access_rights[$player->Type()]["edit_own_post"]);
		
		$content = Generate_Thread_details($param);
	}
	
	elseif ($current == "New_post")
	{
		$param['New_post']['Thread'] = $forum->Threads->GetThread($thread_id);
		$param['New_post']['Content'] = ((isset($_POST['Content'])) ? $_POST['Content'] : "");
											
		$content = Generate_New_post($param);
	}
	
	elseif ($current == "Edit_post")
	{
		$param['Edit_post']['Post'] = $post_data;
		$param['Edit_post']['CurrentPage'] = $current_page;
		$param['Edit_post']['ThreadList'] = $forum->Threads->ListTargetThreads($post_data['Thread']);
		$param['Edit_post']['Thread'] = $forum->Threads->GetThread($post_data['Thread']);
		$param['Edit_post']['Content'] = ((isset($_POST['Content'])) ? $_POST['Content'] : $post_data['Content']);
		
		$param['Edit_post']['move_post'] = ($access_rights[$player->Type()]["move_post"]);
											
		$content = Generate_Edit_post($param);
	}
	
	elseif ($current == "Edit_thread")
	{
		$param['Edit_thread']['Thread'] = $thread_data = $forum->Threads->GetThread($thread_id);
		$param['Edit_thread']['Section'] = $forum->GetSection($thread_data['Section']);
		$param['Edit_thread']['SectionList'] = $forum->ListTargetSections($thread_data['Section']);
		
		$param['Edit_thread']['chng_priority'] = ($access_rights[$player->Type()]["chng_priority"]);
		$param['Edit_thread']['move_thread'] = ($access_rights[$player->Type()]["move_thread"]);
											
		$content = Generate_Edit_thread($param);
	}
	
	/* ----------- *
	 * | NOWHERE | *
	 * ----------- */
	else
	{
		$content = Generate_Nowhere();
	}
	
	// HTML code generation
	
	echo Generate_Header();
	
	echo $menu;
	echo $content;
	
	$param['sessionstring'] = (($session && !$session->hasCookies()) ? $session->SessionString() : "");
	echo Generate_Footer($param);
	
	$querytime_end = microtime(TRUE);
	echo '<!-- Page generated in '.(int)(1000*($querytime_end - $querytime_start)).' ms. '.$db->queries.' queries used. -->';
?>
