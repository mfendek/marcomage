<?php
/*
	MArcomage
*/
?>
<?php
	$querytime_start = microtime(TRUE);
	
	/*	<section: APPLICATION LOGIC>	*/
	
	require_once('Config.php');
	require_once('CDatabase.php');
	require_once('CLogin.php');
	require_once('CScore.php');
	require_once('CCard.php');
	require_once('CConcept.php');
	require_once('CDeck.php');
	require_once('CGame.php');
	require_once('CReplay.php');
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

	date_default_timezone_set("Etc/UTC");
	$db->Query("SET time_zone='Etc/UTC'");
	
	$logindb = new CLogin($db);
	$scoredb = new CScores($db);
	$carddb = new CCards();
	$conceptdb = new CConcepts($db);
	$deckdb = new CDecks($db);
	$gamedb = new CGames($db);
	$replaydb = new CReplays($db);
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
		elseif (isset($_POST['Messages']))
		{
			$current = "Messages";
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
		elseif (isset($_POST['Concepts']))
		{
			$current = "Concepts";
		}
		elseif (isset($_POST['Novels']))
		{
			$current = "Novels";
		}
		elseif (isset($_POST['Settings']))
		{
			$current = "Settings";
		}
		elseif (isset($_POST['Replays']))
		{
			$current = "Replays";
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
				
				if ($message == 'active_game') // Games -> next game button
				{
					$list = $gamedb->ListCurrentGames($player->Name());
					
					//check if there is an active game
					if (count($list) == 0) { /*$error = 'No games your turn!';*/ $current = 'Games'; break; }
					
					$game_id = $list[0];
					foreach ($list as $i => $cur_game)
					{
						if ($_POST['CurrentGame'] == $cur_game)
						{
							$game_id = $list[($i + 1) % count($list)];//wrap around
							break;
						}	
					}
					
					$game = $gamedb->GetGame($game_id);
					
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
				
				if ($message == 'view_note')
				{	// show current's player game note
					$gameid = $_POST['CurrentGame'];
					$game = $gamedb->GetGame($gameid);
					
					// check if the game exists
					if (!$game) { /*$error = 'No such game!';*/ $current = 'Games'; break; }
					
					// check if this user is allowed to perform game actions
					if ($player->Name() != $game->Name1() and $player->Name() != $game->Name2()) { $current = 'Game'; break; }
					
					$current = 'Game_note';
					break;
				}
				
				if ($message == 'save_note')
				{	// save current's player game note
					$gameid = $_POST['CurrentGame'];
					$game = $gamedb->GetGame($gameid);
					
					// check if the game exists
					if (!$game) { /*$error = 'No such game!';*/ $current = 'Games'; break; }
					
					// check if this user is allowed to perform game actions
					if ($player->Name() != $game->Name1() and $player->Name() != $game->Name2()) { $current = 'Game'; break; }
					
					$new_note = $_POST['Content'];
					
					if (strlen($new_note) > MESSAGE_LENGTH) { $error = "Game note is too long"; $current = "Game_note"; break; }
					
					$game->SetNote($player->Name(), $new_note);
					$game->SaveGame();
					
					$information = 'Game note saved.';
					
					$current = 'Game_note';
					break;
				}
				
				if ($message == 'save_note_return')
				{	// save current's player game note and return to game screen
					$gameid = $_POST['CurrentGame'];
					$game = $gamedb->GetGame($gameid);
					
					// check if the game exists
					if (!$game) { /*$error = 'No such game!';*/ $current = 'Games'; break; }
					
					// check if this user is allowed to view this game
					if ($player->Name() != $game->Name1() and $player->Name() != $game->Name2()) { $current = 'Games'; break; }
					
					// disable re-visiting
					if ( (($player->Name() == $game->Name1()) && ($game->State == 'P1 over')) || (($player->Name() == $game->Name2()) && ($game->State == 'P2 over')) ) { /*$error = 'Game already over.';*/ $current = 'Games'; break; }
					
					$new_note = $_POST['Content'];
					
					if (strlen($new_note) > MESSAGE_LENGTH) { $error = "Game note is too long"; $current = "Game_note"; break; }
					
					$game->SetNote($player->Name(), $new_note);
					$game->SaveGame();
					
					$information = 'Game note saved.';
					
					$current = 'Game';
					break;
				}
				
				if ($message == 'clear_note')
				{	// clear current's player game note
					$gameid = $_POST['CurrentGame'];
					$game = $gamedb->GetGame($gameid);
					
					// check if the game exists
					if (!$game) { /*$error = 'No such game!';*/ $current = 'Games'; break; }
					
					// check if this user is allowed to perform game actions
					if ($player->Name() != $game->Name1() and $player->Name() != $game->Name2()) { $current = 'Game'; break; }
					
					$game->ClearNote($player->Name());
					$game->SaveGame();
					
					$information = 'Game note cleared.';
					
					$current = 'Game_note';
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
						$replaydb->UpdateReplay($game);
						
						if ($game->State == "finished")
							$replaydb->FinishReplay($game);
						
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
						$replaydb->UpdateReplay($game);
						
						if ($game->State == 'finished')
							$replaydb->FinishReplay($game);

						if (($game->State == 'finished') AND ($game->GetGameMode('FriendlyPlay') == "no"))
						{
							$player1 = $game->Name1();
							$player2 = $game->Name2();
							$exp1 = $game->CalculateExp($player1);
							$exp2 = $game->CalculateExp($player2);
							$p1 = $playerdb->GetPlayer($player1);
							$p2 = $playerdb->GetPlayer($player2);
							$p1_rep = $p1->GetSetting("Reports");
							$p2_rep = $p2->GetSetting("Reports");
							
							// update score
							$score1 = $scoredb->GetScore($player1);
							$score2 = $scoredb->GetScore($player2);
							
							if ($game->Winner == $player1) { $score1->ScoreData->Wins++; $score2->ScoreData->Losses++; }
							elseif ($game->Winner == $player2) { $score2->ScoreData->Wins++; $score1->ScoreData->Losses++; }
							else {$score1->ScoreData->Draws++; $score2->ScoreData->Draws++; }
							
							$levelup1 = $score1->AddExp($exp1['exp']);
							$levelup2 = $score2->AddExp($exp2['exp']);
							$score1->SaveScore();
							$score2->SaveScore();
							
							// send level up messages
							if ($levelup1 AND ($p1_rep == "yes")) $messagedb->LevelUp($player1, $score1->ScoreData->Level);
							if ($levelup2 AND ($p2_rep == "yes")) $messagedb->LevelUp($player2, $score2->ScoreData->Level);
							
							// add bonus deck slot every 6th level
							if ($levelup1 AND (($p1->GetLevel() % BONUS_DECK_SLOTS) == 0)) $deckdb->CreateDeck($player1, time());
							if ($levelup2 AND (($p2->GetLevel() % BONUS_DECK_SLOTS) == 0)) $deckdb->CreateDeck($player2, time());
							
							// send battle report message
							$outcome = $game->Outcome();
							$winner = $game->Winner;
							$hidden = $game->GetGameMode('HiddenCards');

							$messagedb->SendBattleReport($player1, $player2, $p1_rep, $p2_rep, $outcome, $hidden, $exp1['message'], $exp2['message'], $winner);
						}
						
						$information = "You have played a card.";
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
						$replaydb->FinishReplay($game);
					
					if (($result == 'OK') AND ($game->GetGameMode('FriendlyPlay') == "no"))
					{
						$exp1 = $game->CalculateExp($player->Name());
						$exp2 = $game->CalculateExp($game->Winner);
						$opponent = $playerdb->GetPlayer($game->Winner);
						$opponent_rep = $opponent->GetSetting("Reports");
						$player_rep = $player->GetSetting("Reports");
						
						// update score
						$score1 = $scoredb->GetScore($player->Name());
						$score1->ScoreData->Losses++;
						$levelup1 = $score1->AddExp($exp1['exp']);
						$score1->SaveScore();
						
						$score2 = $scoredb->GetScore($game->Winner);
						$score2->ScoreData->Wins++;
						$levelup2 = $score2->AddExp($exp2['exp']);
						$score2->SaveScore();
						
						// send level up messages
						if ($levelup1 AND ($player_rep == "yes")) $messagedb->LevelUp($player->Name(), $score1->ScoreData->Level);
						if ($levelup2 AND ($opponent_rep == "yes")) $messagedb->LevelUp($opponent->Name(), $score2->ScoreData->Level);
						
						// add bonus deck slot every 6th level
						if ($levelup1 AND (($player->GetLevel() % BONUS_DECK_SLOTS) == 0)) $deckdb->CreateDeck($player->Name(), time());
						if ($levelup2 AND (($opponent->GetLevel() % BONUS_DECK_SLOTS) == 0)) $deckdb->CreateDeck($opponent->Name(), time());

						// send battle report message
						$outcome = $game->Outcome();
						$winner = $game->Winner;
						$hidden = $game->GetGameMode('HiddenCards');

						$messagedb->SendBattleReport($player->Name(), $opponent->Name(), $player_rep, $opponent_rep, $outcome, $hidden, $exp1['message'], $exp2['message'], $winner);
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
						$replaydb->FinishReplay($game);
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
					
					// check if the game exists
					if (!$game) { /*$error = 'No such game!';*/ $current = 'Games'; break; }
					
					// check if this user is allowed to abort this game
					if ($player->Name() != $game->Name1() and $player->Name() != $game->Name2()) { $current = 'Game'; break; }
					
					// only allow finishing active games
					if ($playerdb->isDead($game->Name1()) or $playerdb->isDead($game->Name2())) { /*$error = 'Action not allowed!';*/ $current = 'Game'; break; }
					
					// and only if the abort criteria are met
					if( time() - strtotime($game->LastAction) < 60*60*24*7*3 || $game->Current == $player->Name() ) { /*$error = 'Action not allowed!';*/ $current = 'Game'; break; }
					
					$result = $game->FinishGame($player->Name());
					
					if ($result == 'OK')
						$replaydb->FinishReplay($game);
					
					if (($result == 'OK') AND ($game->GetGameMode('FriendlyPlay') == "no"))
					{
						$player1 = $game->Name1();
						$player2 = $game->Name2();
						$exp1 = $game->CalculateExp($player1);
						$exp2 = $game->CalculateExp($player2);
						$p1 = $playerdb->GetPlayer($player1);
						$p2 = $playerdb->GetPlayer($player2);
						$p1_rep = $p1->GetSetting("Reports");
						$p2_rep = $p2->GetSetting("Reports");
						
						// update score
						$score1 = $scoredb->GetScore($player1);
						$score2 = $scoredb->GetScore($player2);
						
						if ($game->Winner == $player1) { $score1->ScoreData->Wins++; $score2->ScoreData->Losses++; }
						elseif ($game->Winner == $player2) { $score2->ScoreData->Wins++; $score1->ScoreData->Losses++; }
						else {$score1->ScoreData->Draws++; $score2->ScoreData->Draws++; }
						
						$levelup1 = $score1->AddExp($exp1['exp']);
						$levelup2 = $score2->AddExp($exp2['exp']);
						$score1->SaveScore();
						$score2->SaveScore();
						
						// send level up messages
						if ($levelup1 AND ($p1_rep == "yes")) $messagedb->LevelUp($player1, $score1->ScoreData->Level);
						if ($levelup2 AND ($p2_rep == "yes")) $messagedb->LevelUp($player2, $score2->ScoreData->Level);
						
						// add bonus deck slot every 6th level
						if ($levelup1 AND (($p1->GetLevel() % BONUS_DECK_SLOTS) == 0)) $deckdb->CreateDeck($player1, time());
						if ($levelup2 AND (($p2->GetLevel() % BONUS_DECK_SLOTS) == 0)) $deckdb->CreateDeck($player2, time());

						// send battle report message
						$outcome = $game->Outcome();
						$winner = $game->Winner;
						$hidden = $game->GetGameMode('HiddenCards');

						$messagedb->SendBattleReport($player1, $player2, $p1_rep, $p2_rep, $outcome, $hidden, $exp1['message'], $exp2['message'], $winner);
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
				
				if ($message == 'host_game') // Games -> Host game
				{
					$subsection = "hosted_games";
					
					// check access rights
					if (!$access_rights[$player->Type()]["send_challenges"]) { $error = 'Access denied.'; $current = 'Games'; break; }
					
					$deckname = isset($_POST['SelectedDeck']) ? postdecode($_POST['SelectedDeck']) : '(null)';
					
					$deck = $deckdb->GetDeck($player->Name(), $deckname);
					
					// check if such deck exists
					if (!$deck) { $error = 'Deck '.$deckname.' does not exist!'; $current = 'Games'; break; }
					
					// check if the deck is ready (all 45 cards)
					if (!$deck->isReady()) { $error = 'Deck '.$deckname.' is not yet ready for gameplay!'; $current = 'Games'; break; }
					
					// check if you are within the MAX_GAMES limit
					if ($gamedb->CountFreeSlots1($player->Name()) == 0) { $error = 'Too many games / challenges! Please resolve some.'; $current = 'Games'; break; }
					
					// create a new challenge
					$game = $gamedb->CreateGame($player->Name(), '', $deck->DeckData);
					if (!$game) { $error = 'Failed to create new game!'; $current = 'Games'; break; }
					
					// set game modes
					$hidden_cards = (isset($_POST['HiddenCards']) ? 'yes' : 'no');
					$friendly_play = (isset($_POST['FriendlyMode']) ? 'yes' : 'no');
					$game_modes = array();
					if ($hidden_cards == "yes") $game_modes[] = 'HiddenCards';
					if ($friendly_play == "yes") $game_modes[] = 'FriendlyPlay';
					$game->SetGameModes(implode(',', $game_modes));
					
					$information = 'Game created. Waiting for opponent to join.';
					$current = 'Games';
					break;
				}
				
				if ($message == 'unhost_game') // Games -> Unhost game
				{
					$game_id = array_shift(array_keys($value));
					$game = $gamedb->GetGame($game_id);
					$subsection = "hosted_games";
					
					// check if the game exists
					if (!$game) { $error = 'No such game!'; $current = 'Games'; break; }
					
					// check if the game is a a challenge (and not a game in progress)
					if ($game->State != 'waiting') { $error = 'Game already in progress!'; $current = 'Games'; break; }
					
					// delete game entry
					$gamedb->DeleteGame($game->ID());
					$chatdb->DeleteChat($game->ID());
					
					$information = 'You have canceled a game.';
					$current = 'Games';
					break;
				}
				
				if ($message == 'join_game') // Games -> Join game
				{					
					$subsection = "free_games";
					
					// check access rights
					if (!$access_rights[$player->Type()]["accept_challenges"]) { $error = 'Access denied.'; $current = 'Games'; break; }
					
					$game_id = array_shift(array_keys($value));
					$game = $gamedb->GetGame($game_id);
					
					// check if the game exists
					if (!$game) { $error = 'No such game!'; $current = 'Games'; break; }
					
					// check if the game is a challenge and not an active game
					if ($game->State != 'waiting') { $error = 'Game already in progress!'; $current = 'Games'; break; }
					
					// check if you are within the MAX_GAMES limit
					if ($gamedb->CountFreeSlots1($player->Name()) == 0) { $error = 'You may only have '.MAX_GAMES.' simultaneous games at once (this also includes your challenges).'; $current = 'Games'; break; }
					
					$opponent = $game->Name1();
					
					$deckname = isset($_POST['SelectedDeck']) ? postdecode($_POST['SelectedDeck']) : '(null)';
					$deck = $deckdb->GetDeck($player->Name(), $deckname);
					
					// check if such deck exists
					if (!$deck) { $error = 'No such deck!'; $current = 'Games'; break; }
					
					// check if the deck is ready (all 45 cards)
					if (!$deck->isReady()) { $error = 'This deck is not yet ready for gameplay!'; $current = 'Decks'; break; }
					
					// check if such opponent exists
					if (!$playerdb->GetPlayer($opponent)) { $error = 'No such player!'; $current = 'Games'; break; }
					
					// check if that opponent was already challenged, or if there is a game already in progress
					if ($gamedb->CheckGame($opponent, $player->Name())) { $error = 'You are already playing against '.htmlencode($opponent).'!'; $current = 'Games'; break; }
					
					// join the game
					$gamedb->JoinGame($player->Name(), $game_id);
					$game = $gamedb->GetGame($game_id); // refresh game data
					$game->StartGame($player->Name(), $deck->DeckData);
					$game->SaveGame();
					$replaydb->CreateReplay($game); // create game replay
					
					$information = 'You have joined '.htmlencode($opponent).'\'s game.';
					$current = 'Games';
					break;
				}
				
				if ($message == 'free_games') // view available games, where player can join
				{
					$current = 'Games';
					break;
				}
				
				if ($message == 'hosted_games') // view games hosted by player
				{
					$current = 'Games';
					break;
				}
				
				if ($message == 'filter_hosted_games') // use filter in hosted games view
				{
					$current = 'Games';
					$subsection = "free_games";
					break;
				}
				
				// end game-related messages
				
				// challenge-related messages
				if ($message == 'accept_challenge') // Challenges -> Accept
				{
					// check access rights
					if (!$access_rights[$player->Type()]["accept_challenges"]) { $error = 'Access denied.'; $current = 'Messages'; break; }
					
					$game_id = array_shift(array_keys($value));
					$game = $gamedb->GetGame($game_id);
					
					// check if the challenge exists
					if (!$game) { $error = 'No such challenge!'; $current = 'Messages'; break; }
					
					// check if the game is a challenge and not an active game
					if ($game->State != 'waiting') { $error = 'Game already in progress!'; $current = 'Messages'; break; }
					
					// the player may never have more than MAX_GAMES games at once, even potential ones (challenges)
					if ($gamedb->CountFreeSlots2($player->Name()) == 0) { $error = 'You may only have '.MAX_GAMES.' simultaneous games at once (this also includes your challenges).'; $current = 'Messages'; break; }
					
					$opponent = $game->Name1();
					
					$deckname = isset($_POST['AcceptDeck']) ? postdecode($_POST['AcceptDeck']) : '(null)';
					$deck = $deckdb->GetDeck($player->Name(), $deckname);
					
					// check if such deck exists
					if (!$deck) { $error = 'No such deck!'; $current = 'Messages'; break; }
					
					// check if the deck is ready (all 45 cards)
					if (!$deck->isReady()) { $error = 'This deck is not yet ready for gameplay!'; $current = 'Decks'; break; }
					
					// check if such opponent exists
					if (!$playerdb->GetPlayer($opponent)) { $error = 'No such player!'; $current = 'Messages'; break; }
					
					// check if player can enter the game
					if ($game->Name2() != $player->Name()) { $error = 'Invalid player'; $current = 'Messages'; break; }
					
					// accept the challenge
					$game->StartGame($player->Name(), $deck->DeckData);
					$game->SaveGame();
					$replaydb->CreateReplay($game); // create game replay
					$messagedb->CancelChallenge($game->ID());
					
					$information = 'You have accepted a challenge from '.htmlencode($opponent).'.';
					$current = 'Messages';
					break;
				}
				
				if ($message == 'reject_challenge') // Challenges -> Reject
				{
					$game_id = array_shift(array_keys($value));
					
					$game = $gamedb->GetGame($game_id);
					
					// check if the challenge exists
					if (!$game) { $error = 'No such challenge!'; $current = 'Messages'; break; }
					
					// check if the game is a challenge (and not a game in progress)
					if ($game->State != 'waiting') { $error = 'Game already in progress!'; $current = 'Messages'; break; }
					
					$opponent = $game->Name1();
					
					// check if such opponent exists
					if (!$playerdb->GetPlayer($opponent)) { $error = 'Player '.htmlencode($opponent).' does not exist!'; $current = 'Messages'; break; }
					
					// delete t3h challenge/game entry
					$gamedb->DeleteGame($game->ID());
					$chatdb->DeleteChat($game->ID());
					$messagedb->CancelChallenge($game->ID());
					
					$information = 'You have rejected a challenge.';
					$current = 'Messages';
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
					// check access rights
					if (!$access_rights[$player->Type()]["send_challenges"]) { $error = 'Access denied.'; $current = 'Players'; break; }
					
					$_POST['cur_player'] = $opponent = postdecode(array_shift(array_keys($value)));
					$deckname = isset($_POST['ChallengeDeck']) ? postdecode($_POST['ChallengeDeck']) : '(null)';
					
					$deck = $deckdb->GetDeck($player->Name(), $deckname);
					
					// check if such deck exists
					if (!$deck) { $error = 'Deck '.$deckname.' does not exist!'; $current = 'Profile'; break; }
					
					// check if the deck is ready (all 45 cards)
					if (!$deck->isReady()) { $error = 'Deck '.$deckname.' is not yet ready for gameplay!'; $current = 'Profile'; break; }
					
					// check if such opponent exists
					if (!$playerdb->GetPlayer($opponent)) { $error = 'Player '.htmlencode($opponent).' does not exist!'; $current = 'Profile'; break; }
					
					// check if that opponent was already challenged, or if there is a game already in progress
					if ($gamedb->CheckGame($player->Name(), $opponent)) { $error = 'You are already playing against '.htmlencode($opponent).'!'; $current = 'Profile'; break; }
					
					// check if you are within the MAX_GAMES limit
					if ($gamedb->CountFreeSlots1($player->Name()) == 0) { $error = 'Too many games / challenges! Please resolve some.'; $current = 'Messages'; break; }
					
					// check challenge text length
					if (strlen($_POST['Content']) > CHALLENGE_LENGTH) { $error = "Message too long"; $current = "Details"; break; }
					
					// create a new challenge
					$game = $gamedb->CreateGame($player->Name(), $opponent, $deck->DeckData);
					if (!$game) { $error = 'Failed to create new game!'; $current = 'Profile'; break; }
					
					// set game modes
					$hidden_cards = (isset($_POST['HiddenCards']) ? 'yes' : 'no');
					$friendly_play = (isset($_POST['FriendlyPlay']) ? 'yes' : 'no');
					$game_modes = array();
					if ($hidden_cards == "yes") $game_modes[] = 'HiddenCards';
					if ($friendly_play == "yes") $game_modes[] = 'FriendlyPlay';
					$game->SetGameModes(implode(',', $game_modes));
					
					$challenge_text = 'Hide opponent\'s cards: '.$hidden_cards."\n";
					$challenge_text.= 'Friendly play: '.$friendly_play."\n";
					$challenge_text.= $_POST['Content'];
					
					$res = $messagedb->SendChallenge($player->Name(), $opponent, $challenge_text, $game->ID());
					if (!$res) { $error = 'Failed to create new challenge!'; $current = 'Profile'; break; }
					
					$information = 'You have challenged '.htmlencode($opponent).'. Waiting for reply.';
					$current = 'Profile';
					break;
				}
				
				if ($message == 'withdraw_challenge') // Players -> Cancel
				{
					$game_id = array_shift(array_keys($value));
					$game = $gamedb->GetGame($game_id);
					
					// check if the challenge exists
					if (!$game) { $error = 'No such challenge!'; $current = 'Profile'; break; }
					
					// check if the game is a a challenge (and not a game in progress)
					if ($game->State != 'waiting') { $error = 'Game already in progress!'; $current = 'Profile'; break; }
					
					$_POST['cur_player'] = $opponent = $game->Name2();
					
					// check if such opponent exists
					if (!$playerdb->GetPlayer($opponent)) { $error = 'Player '.htmlencode($opponent).' does not exist!'; $current = 'Profile'; break; }
					
					// delete t3h challenge/game entry
					$gamedb->DeleteGame($game->ID());
					$chatdb->DeleteChat($game->ID());
					$messagedb->CancelChallenge($game->ID());
					
					$information = 'You have withdrawn a challenge.';
					$current = 'Profile';
					break;
				}
				
				if ($message == 'withdraw_challenge2') // Challenges -> Cancel
				{
					$game_id = array_shift(array_keys($value));
					$game = $gamedb->GetGame($game_id);
					
					// check if the challenge exists
					if (!$game) { $error = 'No such challenge!'; $current = 'Messages'; break; }
					
					// check if the game is a a challenge (and not a game in progress)
					if ($game->State != 'waiting') { $error = 'Game already in progress!'; $current = 'Messages'; break; }
					
					$_POST['cur_player'] = $opponent = $game->Name2();
					
					// check if such opponent exists
					if (!$playerdb->GetPlayer($opponent)) { $error = 'Player '.htmlencode($opponent).' does not exist!'; $current = 'Profile'; break; }
					
					// delete t3h challenge/game entry
					$gamedb->DeleteGame($game->ID());
					$chatdb->DeleteChat($game->ID());
					$messagedb->CancelChallenge($game->ID());
					
					$information = 'You have withdrawn a challenge.';
					$_POST['outgoing'] = "outgoing"; // stay in "Outgoing" subsection
					$current = 'Messages';
					break;
				}
				
				if ($message == 'incoming') // view challenges to player
				{
					$current = 'Messages';
					break;
				}
				
				if ($message == 'outgoing') // view challenges from player
				{
					$current = 'Messages';
					break;
				}
				// end challenge-related messages
				
				// message-related messages
				if ($message == 'message_details') // view message
				{
					$messageid = array_shift(array_keys($value));
					
					$message = $messagedb->GetMessage($messageid, $player->Name());
					
					if (!$message) { $error = "No such message!"; $current = "Messages"; break; }
					
					$current = 'Message_details';
					break;
				}
				
				if ($message == 'message_retrieve') // retrieve message (even deleted one)
				{				
					$messageid = array_shift(array_keys($value));
					
					// check access rights
					if (!$access_rights[$player->Type()]["see_all_messages"]) { $error = 'Access denied.'; $current = 'Messages'; break; }
						
					$message = $messagedb->RetrieveMessage($messageid);
					
					if (!$message) { $error = "No such message!"; $current = "Messages"; break; }
					
					$current = 'Message_details';
					break;
				}
				
				if ($message == 'message_delete') // delete message
				{
					$messageid = array_shift(array_keys($value));
					
					$message = $messagedb->GetMessage($messageid, $player->Name());
					
					if (!$message) { $error = "No such message!"; $current = "Messages"; break; }
					
					$current = 'Message_details';
					break;
				}
				
				if ($message == 'message_delete_confirm') // delete message confirmation
				{
					$messageid = array_shift(array_keys($value));
					
					$message = $messagedb->DeleteMessage($messageid, $player->Name());
					
					if (!$message) { $error = "No such message!"; $current = "Messages"; break; }
					
					$information = "Message deleted";
					
					$current = 'Messages';
					break;
				}
				
				if ($message == 'message_cancel') // cancel new message creation
				{
					$current = 'Messages';
					break;
				}
				
				if ($message == 'message_send') // send new message
				{
					$recipient = $_POST['Recipient'];
					$author = $_POST['Author'];
					
					// check access rights
					if (!$access_rights[$player->Type()]["messages"]) { $error = 'Access denied.'; $current = 'Messages'; break; }
				
					if ((trim($_POST['Subject']) == "") AND (trim($_POST['Content']) == "")) { $error = "No message input specified"; $current = "Message_new"; break; }
					
					if (strlen($_POST['Content']) > MESSAGE_LENGTH) { $error = "Message too long"; $current = "Message_new"; break; }
				
					$message = $messagedb->SendMessage($_POST['Author'], $_POST['Recipient'], $_POST['Subject'], $_POST['Content']);
					
					if (!$message) { $error = "Failed to send message"; $current = "Messages"; break; }
					
					$_POST['CurrentLocation'] = "sent_mail";
					$information = "Message sent";
					
					$current = 'Messages';
					break;
				}
				
				if ($message == 'message_create') // go to new message screen
				{
					// check access rights
					if (!$access_rights[$player->Type()]["messages"]) { $error = 'Access denied.'; $current = 'Messages'; break; }
				
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
					$_POST['date_filter'] = "none";
					$_POST['name_filter'] = "none";
					$_POST['CurrentMesPage'] = 0;
					unset($_POST['CurrentCond']);
					unset($_POST['CurrentOrd']);
					$current = 'Messages';
					break;
				}
				
				if ($message == 'sent_mail') // view messages from player
				{
					$_POST['CurrentLocation'] = "sent_mail";
					$_POST['date_filter'] = "none";
					$_POST['name_filter'] = "none";
					$_POST['CurrentMesPage'] = 0;
					unset($_POST['CurrentCond']);
					unset($_POST['CurrentOrd']);
					$current = 'Messages';
					break;
				}
				
 				if ($message == 'all_mail') // view messages from player
 				{
 					// check access rights
 					if (!$access_rights[$player->Type()]["see_all_messages"]) { $error = 'Access denied.'; $current = 'Messages'; break; }
					$_POST['CurrentLocation'] = "all_mail";
					$_POST['date_filter'] = "none";
					$_POST['name_filter'] = "none";
					$_POST['CurrentMesPage'] = 0;
					unset($_POST['CurrentCond']);
					unset($_POST['CurrentOrd']);
 					$current = 'Messages';
 					break;
 				}
				
				$temp = array("asc" => "ASC", "desc" => "DESC");
				foreach($temp as $type => $order_val)
				{
					if ($message == 'mes_ord_'.$type) // select ascending or descending order in message list
					{
						$_POST['CurrentCond'] = array_shift(array_keys($value));
						$_POST['CurrentOrd'] = $order_val;
						
						$current = "Messages";
						
						break;
					}
				}
				
				if ($message == 'message_filter') // use filter
				{
					$_POST['CurrentMesPage'] = 0;
					
					$current = 'Messages';
					break;
				}
				
				if ($message == 'select_page_mes') // Messages -> select page (previous and next button)
				{
					$_POST['CurrentMesPage'] = array_shift(array_keys($value));
					$current = "Messages";
					
					break;
				}
				
				if ($message == 'Jump_messages') // Messages -> select page (Jump to page)
				{
					$_POST['CurrentMesPage'] = $_POST['jump_to_page'];
					$current = "Messages";
					
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
						if (!$result) { $error = "Failed to delete messages"; $current = "Messages"; break; }
						
						$information = "Messages deleted";
					}
					else $warning = "No messages selected";
					
					$current = "Messages";
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
				
				if ($message == 'reset_exp') // Players -> User details -> Reset exp
				{
					$opponent = postdecode(array_shift(array_keys($value)));
					
					$_POST['Profile'] = $opponent;
					
					// check access rights
					if (!$access_rights[$player->Type()]["change_rights"]) { $error = 'Access denied.'; $current = 'Profile'; break; }
					
					// reset level end exp
					$score = $scoredb->GetScore($opponent);
					$score->ResetExp();
					$score->SaveScore();
					
					// delete bonus deck slots
					$decks = $deckdb->ListDecks($opponent);
					foreach ($decks as $i => $deck_data)
						if ($i >= DECK_SLOTS) $deckdb->DeleteDeck($opponent, $deck_data['Deckname']);
					
					$information = 'Exp reset.';
					
					$current = 'Profile';
					break;
				}
				// end view user details
				
				// deck-related messages
				if ($message == 'modify_deck') // Decks -> Modify this deck
				{
					$deckname = postdecode(array_shift(array_keys($value)));
					
					$_POST['CurrentDeck'] = $deckname;
					$current = 'Deck_edit';
					break;
				}
				
				if ($message == 'add_card') // Decks -> Modify this deck -> Take
				{
					$cardid = (int)postdecode(array_shift(array_keys($value)));
					$deckname = $_POST['CurrentDeck'];
					
					//download deck
					$deck = $player->GetDeck($deckname);
					
					// add card, saving the deck on success
					if( $deck->AddCard($cardid) )
						$deck->SaveDeck();
					else
						$error = 'Unable to add the chosen card to this deck.';
					
					$current = 'Deck_edit';
					break;
				}
				
				if ($message == 'return_card') // Decks -> Modify this deck -> Return
				{
					$cardid = (int)postdecode(array_shift(array_keys($value)));
					$deckname = $_POST['CurrentDeck'];
					
					// download deck
					$deck = $player->GetDeck($deckname);
					
					// remove card, saving the deck on success
					if( $deck->ReturnCard($cardid) )
						$deck->SaveDeck();
					else
						$error = 'Unable to remove the chosen card from this deck.';
					
					$current = 'Deck_edit';
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
					
					$information = 'Tokens set.';
					
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
					
					$information = 'Tokens set.';
					
					break;
				}
				
				if ($message == 'filter') // Decks -> Modify this deck -> Apply filters
				{
					$current = 'Deck_edit';
					
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
					
					// reset deck, saving it on success
					if( $deck->ResetDeck() )
						$deck->SaveDeck();
					else
						$error = 'Failed to reset this deck.';
				
					$current = 'Deck_edit';
					break;
				}
				
				if ($message == 'finish_deck') // Decks -> Modify this deck -> Finish
				{
					$deckname = $_POST['CurrentDeck'];
					$deck = $player->GetDeck($deckname);
					
					// finish deck, saving it on success
					if( $deck->FinishDeck() )
						$deck->SaveDeck();
					else
						$error = 'Failed to finish this deck.';

					$current = 'Deck_edit';
					break;
				}
				
				if ($message == 'rename_deck') // Decks -> Modify this deck -> Rename
				{
					$curname = $_POST['CurrentDeck'];
					$newname = $_POST['NewDeckName'];
					$list = $player->ListDecks();
					$deck_names = array();
					foreach ($list as $deck) $deck_names[] = $deck['Deckname'];
					$pos = array_search($newname, $deck_names);
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
				
				if ($message == 'export_deck') // Decks -> Modify this deck -> Export
				{
					$curname = $_POST['CurrentDeck'];
					$deck = $player->GetDeck($curname);
					$file = $deck->ToCSV();
					
					$content_type = 'text/csv';
					$file_name = preg_replace("/[^a-zA-Z0-9_-]/i", "_", $deck->Deckname()).'.csv';
					$file_length = strlen($file);
					
					header('Content-Type: '.$content_type.'');
					header('Content-Disposition: attachment; filename="'.$file_name.'"');
					header('Content-Length: '.$file_length);
					echo $file;
					
					return; // skip the presentation layer
				}
				
				if ($message == 'import_deck') // Decks -> Modify this deck -> Import
				{
					$curname = $_POST['CurrentDeck'];
					$current = 'Deck_edit';
					
					//$supported_types = array("text/csv", "text/comma-separated-values");
					$supported_types = array("csv");
					
					if (($_FILES['uploadedfile']['tmp_name'] == ""))
						$error = "Invalid input file";
					else
					/* MIME file type checking cannot be used, there are browser specific issues (Firefox, Chrome), instead use file extension check
					if (!in_array($_FILES['uploadedfile']['type'], $supported_types))
						$error = "Unsupported input file";
					else
					*/
					if (!in_array(end(explode(".", $_FILES['uploadedfile']['name'])), $supported_types))
						$error = "Unsupported input file";
					else
					if (($_FILES['uploadedfile']['size'] > 1*1000 ))
						$error = "File is too big";
					else
					{
						// load file
						$file = file_get_contents($_FILES['uploadedfile']['tmp_name']);
						
						// import data
						$deck = $player->GetDeck($curname);
						
						if ($deck != false)
						{
							$result = $deck->FromCSV($file);
							if ($result != "Success")	$error = $result;
							else
							{
								$deck->SaveDeck();
								$_POST['CurrentDeck'] = $deck->Deckname();
								$information = "Deck successfully imported.";
							}
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
				
				// concepts-related messages
				$temp = array("asc" => "ASC", "desc" => "DESC");
				foreach($temp as $type => $order_val)
				{
					if ($message == 'concepts_ord_'.$type) // select ascending or descending order in card concepts list
					{
						$_POST['CurrentCon'] = array_shift(array_keys($value));
						$_POST['CurrentOrder'] = $order_val;
						
						$current = "Concepts";
						
						break;
					}
				}
				
				if ($message == 'concepts_filter') // use filter
				{
					$_POST['CurrentConPage'] = 0;
					
					$current = 'Concepts';
					break;
				}
				
				if ($message == 'my_concepts') // use "my cards" quick button
				{
					$_POST['date_filter'] = "none";
					$_POST['author_filter'] = $player->Name();
					$_POST['state_filter'] = "none";
					$_POST['CurrentConPage'] = 0;
					
					$current = 'Concepts';
					break;
				}
				
				if ($message == 'select_page_con') // Concepts -> select page (previous and next button)
				{
					$_POST['CurrentConPage'] = array_shift(array_keys($value));
					$current = "Concepts";
					
					break;
				}
				
				if ($message == 'Jump_concepts') // Concepts -> select page (Jump to page)
				{
					$_POST['CurrentConPage'] = $_POST['jump_to_page'];
					$current = "Concepts";
					
					break;
				}
				
				if ($message == 'new_concept') // go to new card formular
				{
					// check access rights
					if (!$access_rights[$player->Type()]["create_card"]) { $error = 'Access denied.'; $current = 'Concepts'; break; }
					$current = "Concepts_new";
					
					break;
				}
				
				if ($message == 'create_concept') // create new card concept
				{
					// check access rights
					if (!$access_rights[$player->Type()]["create_card"]) { $error = 'Access denied.'; $current = 'Concepts'; break; }
					
					// add default cost values
					if (trim($_POST['bricks']) == "") $_POST['bricks'] = 0;
					if (trim($_POST['gems']) == "") $_POST['gems'] = 0;
					if (trim($_POST['recruits']) == "") $_POST['recruits'] = 0;
					
					$data = array();
					$inputs = array('name', 'class', 'bricks', 'gems', 'recruits', 'effect', 'keywords', 'note');
					foreach ($inputs as $input) $data[$input] = $_POST[$input];
					$data['author'] = $player->Name();
					
					// input checks
					$check = $conceptdb->CheckInputs($data);
					
					if ($check != "") { $error = $check; $current = "Concepts_new"; break; }
					
					$concept_id = $conceptdb->CreateConcept($data);
					if (!$concept_id) { $error = "Failed to create new card"; $current = "Concepts_new"; break; }
					
					$information = "New card created";
					$current = "Concepts_edit";
					
					break;
				}
				
				if ($message == 'view_concept') // go to card details
				{
					$concept_id = array_shift(array_keys($value));
					
					if (!$conceptdb->Exists($concept_id)) { $error = 'No such card.'; $current = 'Concepts'; break; }
					$concept = $conceptdb->GetConcept($concept_id);
					
					$current = "Concepts_details";
					
					break;
				}
				
				if ($message == 'edit_concept') // go to card edit formaular
				{
					$concept_id = array_shift(array_keys($value));
					
					if (!$conceptdb->Exists($concept_id)) { $error = 'No such card.'; $current = 'Concepts'; break; }
					$concept = $conceptdb->GetConcept($concept_id);
					
					// check access rights
					if (!($access_rights[$player->Type()]["edit_all_card"] OR ($access_rights[$player->Type()]["edit_own_card"] AND $player->Name() == $concept->ConceptData->Author))) { $error = 'Access denied.'; $current = 'Concepts'; break; }
					
					$current = "Concepts_edit";
					
					break;
				}
				
				if ($message == 'save_concept') // save edited changes
				{
					$concept_id = $_POST['CurrentConcept'];
					
					if (!$conceptdb->Exists($concept_id)) { $error = 'No such card.'; $current = 'Concepts'; break; }
					$concept = $conceptdb->GetConcept($concept_id);
					
					// check access rights
					if (!($access_rights[$player->Type()]["edit_all_card"] OR ($access_rights[$player->Type()]["edit_own_card"] AND $player->Name() == $concept->ConceptData->Author))) { $error = 'Access denied.'; $current = 'Concepts'; break; }
					
					$old_name = $concept->Name();
					$new_name = $_POST['name'];
					$thread_id = $concept->ThreadID();
					
					// add default cost values
					if (trim($_POST['bricks']) == "") $_POST['bricks'] = 0;
					if (trim($_POST['gems']) == "") $_POST['gems'] = 0;
					if (trim($_POST['recruits']) == "") $_POST['recruits'] = 0;
					
					$data = array();
					$inputs = array('name', 'class', 'bricks', 'gems', 'recruits', 'effect', 'keywords', 'note');
					foreach ($inputs as $input) $data[$input] = $_POST[$input];
					
					// input checks
					$check = $conceptdb->CheckInputs($data);
					
					if ($check != "") { $error = $check; $current = "Concepts_edit"; break; }
					
					$result = $concept->EditConcept($data);
					if (!$result) { $error = "Failed to save changes"; $current = "Concepts_edit"; break; }
					
					// update corresponding thread name if necessary
					if ((trim($old_name) != trim($new_name)) AND ($thread_id > 0))
					{
						$result = $forum->Threads->EditThread($thread_id, $new_name, 'normal');					
						if (!$result) { $error = "Failed to rename thread"; $current = "Concepts_edit"; break; }
					}
					
					$information = "Changes saved";
					$current = "Concepts_edit";
					
					break;
				}
				
				if ($message == 'save_concept_special') // save edited changes (special access)
				{
					$concept_id = $_POST['CurrentConcept'];
					
					if (!$conceptdb->Exists($concept_id)) { $error = 'No such card.'; $current = 'Concepts'; break; }
					$concept = $conceptdb->GetConcept($concept_id);
					
					// check access rights
					if (!$access_rights[$player->Type()]["edit_all_card"]) { $error = 'Access denied.'; $current = 'Concepts'; break; }
					
					$old_name = $concept->Name();
					$new_name = $_POST['name'];
					$thread_id = $concept->ThreadID();
					
					// add default cost values
					if (trim($_POST['bricks']) == "") $_POST['bricks'] = 0;
					if (trim($_POST['gems']) == "") $_POST['gems'] = 0;
					if (trim($_POST['recruits']) == "") $_POST['recruits'] = 0;
					
					$data = array();
					$inputs = array('name', 'class', 'bricks', 'gems', 'recruits', 'effect', 'keywords', 'note', 'state');
					foreach ($inputs as $input) $data[$input] = $_POST[$input];
					
					// input checks
					$check = $conceptdb->CheckInputs($data);
					
					if ($check != "") { $error = $check; $current = "Concepts_edit"; break; }
					
					$result = $concept->EditConceptSpecial($data);
					if (!$result) { $error = "Failed to save changes"; $current = "Concepts_edit"; break; }
					
					// update corresponding thread name if necessary
					if ((trim($old_name) != trim($new_name)) AND ($thread_id > 0))
					{
						$result = $forum->Threads->EditThread($thread_id, $new_name, 'normal');					
						if (!$result) { $error = "Failed to rename thread"; $current = "Concepts_edit"; break; }
					}
					
					$information = "Changes saved";
					$current = "Concepts_edit";
					
					break;
				}
				
				if ($message == 'upload_pic') // upload card_picture
				{
					$concept_id = $_POST['CurrentConcept'];
					
					if (!$conceptdb->Exists($concept_id)) { $error = 'No such card.'; $current = 'Concepts'; break; }
					$concept = $conceptdb->GetConcept($concept_id);
					
					// check access rights
					if (!($access_rights[$player->Type()]["edit_all_card"] OR ($access_rights[$player->Type()]["edit_own_card"] AND $player->Name() == $concept->ConceptData->Author))) { $error = 'Access denied.'; $current = 'Concepts'; break; }
					
					$former_name = $concept->ConceptData->Picture;
					$former_path = 'img/concepts/'.$former_name;
					
					$type = $_FILES['uploadedfile']['type'];
					$pos = strrpos($type, "/") + 1;
					
					$code_type = substr($type, $pos, strlen($type) - $pos);
					$filtered_name = preg_replace("/[^a-zA-Z0-9_-]/i", "_", $player->Name());
					
					$code_name = time().$filtered_name.'.'.$code_type;
					$target_path = 'img/concepts/'.$code_name;
					
					$supported_types = array("image/jpg", "image/jpeg", "image/gif", "image/png");
					
					if (($_FILES['uploadedfile']['tmp_name'] == ""))
						$error = "Invalid input file";
					else
					if (($_FILES['uploadedfile']['size'] > 50*1000 ))
						$error = "File is too big";
					else
					if (!in_array($_FILES['uploadedfile']['type'], $supported_types))
						$error = "Unsupported input file";
					else
					if (move_uploaded_file($_FILES['uploadedfile']['tmp_name'], $target_path) == FALSE)
						$error = "Upload failed, error code ".$_FILES['uploadedfile']['error'];
					else
					{
						if ((file_exists($former_path)) and ($former_name != "blank.jpg")) unlink($former_path);
						$concept->EditPicture($code_name);
						$information = "Picture uploaded";
					}
					
					$current = 'Concepts_edit';
					
					break;
				}
				
				if ($message == 'clear_img') // clear card picture
				{
					$concept_id = $_POST['CurrentConcept'];
					
					if (!$conceptdb->Exists($concept_id)) { $error = 'No such card.'; $current = 'Concepts'; break; }
					$concept = $conceptdb->GetConcept($concept_id);
					
					// check access rights
					if (!($access_rights[$player->Type()]["edit_all_card"] OR ($access_rights[$player->Type()]["edit_own_card"] AND $player->Name() == $concept->ConceptData->Author))) { $error = 'Access denied.'; $current = 'Concepts'; break; }
					
					$former_name = $concept->ConceptData->Picture;
					$former_path = 'img/concepts/'.$former_name;
					
					if ((file_exists($former_path)) and ($former_name != "blank.jpg")) unlink($former_path);
					$concept->ResetPicture();

					$information = "Card picture cleared";
					$current = 'Concepts_edit';
					
					break;
				}
				
				if ($message == 'delete_concept') // delete card concept
				{
					$concept_id = array_shift(array_keys($value));
					
					if (!$conceptdb->Exists($concept_id)) { $error = 'No such card.'; $current = 'Concepts'; break; }
					$concept = $conceptdb->GetConcept($concept_id);
					
					// check access rights
					if (!($access_rights[$player->Type()]["delete_all_card"] OR ($access_rights[$player->Type()]["delete_own_card"] AND $player->Name() == $concept->ConceptData->Author))) { $error = 'Access denied.'; $current = 'Concepts'; break; }
					
					$current = "Concepts_edit";
					
					break;
				}
				
				if ($message == 'delete_concept_confirm') // delete card concept confirmation
				{
					$concept_id = $_POST['CurrentConcept'];
					
					if (!$conceptdb->Exists($concept_id)) { $error = 'No such card.'; $current = 'Concepts'; break; }
					$concept = $conceptdb->GetConcept($concept_id);
					
					// check access rights
					if (!($access_rights[$player->Type()]["delete_all_card"] OR ($access_rights[$player->Type()]["delete_own_card"] AND $player->Name() == $concept->ConceptData->Author))) { $error = 'Access denied.'; $current = 'Concepts'; break; }
					
					$result = $concept->DeleteConcept();
					if (!$result) { $error = "Failed to delete card"; $current = "Concepts_edit"; break; }
					
					$information = "Card deleted";
					$current = "Concepts";
					
					break;
				}
				
				if ($message == 'concept_thread') // create new thread for specified card concept
				{
					$concept_id = $_POST['CurrentConcept'];
					$section_id = 6; // section for discussing concepts
					
					// check access rights
					if (!$access_rights[$player->Type()]["create_thread"]) { $error = 'Access denied.'; $current = 'Concepts_details'; break; }
					
					$concept = $conceptdb->GetConcept($concept_id);
					$thread_id = $concept->ThreadID();
					if ($thread_id > 0) { $error = "Thread already exists"; $current = "Thread_details"; break; }
					
					$concept_name = $concept->Name();
					
					$new_thread = $forum->Threads->CreateThread($concept_name, $player->Name(), 'normal', $section_id);
					if ($new_thread === false) { $error = "Failed to create new thread"; $current = "Concepts_details"; break; }
					// $new_thread contains ID of currently created thread, which can be 0
					
					$result = $concept->AssignThread($new_thread);
					if (!$result) { $error = "Failed to assign new thread"; $current = "Concepts_details"; break; }
					
					$information = "Thread created";
					$thread_id = $new_thread;
					
					$current = 'Thread_details';
					
					break;
				}
				
				// end concepts-related messages
				
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
					if (strlen($_POST['Hobby']) > HOBBY_LENGTH) { $_POST['Hobby'] = substr($_POST['Hobby'], 0, HOBBY_LENGTH); $warning = "Hobby text is too long"; }

					$settings = $settingdb->UserSettingsList();
					$_POST['FriendlyFlag'] = (isset($_POST['FriendlyFlag'])) ? 'yes' : 'no';
					$_POST['BlindFlag'] = (isset($_POST['BlindFlag'])) ? 'yes' : 'no';
					foreach($settings as $setting)
						if (isset($_POST[$setting]) and $setting != 'Birthdate')
							$player->ChangeSetting($setting, $_POST[$setting]);

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
					// handle timezone, skin and background separately
					unset($settings['Timezone']);
					unset($settings['Skin']);
					unset($settings['Background']);
					unset($settings['PlayerFilter']);
					unset($settings['Autorefresh']);
					
					foreach($settings as $setting)
					{
						if( isset($_POST[$setting]) ) // option is checked
							$player->ChangeSetting($setting, "yes");
						else // assume option is unchecked
							$player->ChangeSetting($setting, "no");
					}
					
					if( isset($_POST['Timezone']) and (int)$_POST['Timezone'] >= -12 and (int)$_POST['Timezone'] <= +12 )
						$player->ChangeSetting("Timezone", $_POST['Timezone']);
					
					$player->ChangeSetting("Skin", $_POST['Skin']);
					$player->ChangeSetting("Background", $_POST['Background']);
					$player->ChangeSetting("PlayerFilter", $_POST['PlayerFilter']);
					$player->ChangeSetting("Autorefresh", $_POST['Autorefresh']);
					
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
				
				if ($message == 'reset_notification') //reset notification
				{
					if ($player->ResetNotification()) $information = 'Notification successfully reset';
					else $error = 'Failed to reset notification';
					
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
				
				// begin replays related messages
				
				if ($message == 'filter_replays') // use filter in replays list
				{
					$current = 'Replays';
					break;
				}
				
				if ($message == 'select_page_replays') // Replays -> select page (previous and next button)
				{
					$current_page = array_shift(array_keys($value));
					$current = "Replays";
					
					break;
				}
				
				if ($message == 'seek_page_replays') // Replays -> select page (page selector)
				{
					$current_page = $_POST['page_selector'];
					$current = "Replays";
					
					break;
				}
				
				if ($message == 'view_replay') // Replays -> select specific replay
				{
					$gameid = array_shift(array_keys($value));
					$player_view = array_pop($value);
					$replay = $replaydb->GetReplay($gameid, 1); // view first turn
					
					// check if the game replay exists
					if (!$replay) { /*$error = 'No such game replay!';*/ $current = 'Replays'; break; }
					
					$_POST['CurrentReplay'] = $gameid;
					$_POST['PlayerView'] = $player_view;
					$current = "Replay";
					
					break;
				}
				
				if ($message == 'select_turn') // Replay -> select turn (previous or next button)
				{
					$gameid = $_POST['CurrentReplay'];
					$_POST['turn_selector'] = $turn = array_shift(array_keys($value));
					$replay = $replaydb->GetReplay($gameid, $turn);
					
					// check if the game replay exists
					if (!$replay) { /*$error = 'No such game replay!';*/ $current = 'Replays'; break; }
					
					$current = "Replay";
					
					break;
				}
				
				if ($message == 'seek_turn') // Replay -> select turn (page selector)
				{
					$gameid = $_POST['CurrentReplay'];
					$turn = $_POST['turn_selector'];
					$replay = $replaydb->GetReplay($gameid, $turn);
					
					// check if the game replay exists
					if (!$replay) { /*$error = 'No such game replay!';*/ $current = 'Replays'; break; }
					
					$current = "Replay";
					
					break;
				}
				
				if ($message == 'switch_players') // Replay -> switch player view
				{
					$_POST['PlayerView'] = ($_POST['PlayerView'] == 1) ? 2 : 1;
					$current = "Replay";
					
					break;
				}
				
				// end replays related messages
				
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
		$params["main"]["skin"] = 0; // default skin (user is not logged in, can't retrieve his settings)
		$params["main"]["autorefresh"] = 0; // autorefresh is inactive by default
	}
	else
	{
		// navbar params
		$params["navbar"]["player_name"] = $player->Name();
		$params["navbar"]["level"] = $player->GetLevel();
		$params["navbar"]["current"] = $current;
		$params["navbar"]["error_msg"] = @$error;
		$params["navbar"]["warning_msg"] = @$warning;
		$params["navbar"]["info_msg"] = @$information;
		$params["navbar"]['NumMessages'] = count($gamedb->ListChallengesTo($player->Name()));
		$params["navbar"]['NumUnread'] = $messagedb->CountUnreadMessages($player->Name());

		// menubar notification (depends on current user's game settings)
		$forum_not = ($player->GetSetting("Forum_notification") == 'yes');
		$concepts_not = ($player->GetSetting("Concepts_notification") == 'yes');
		$params["navbar"]['IsSomethingNew'] = ($forum_not AND $forum->IsSomethingNew($player->PreviousLogin())) ? 'yes' : 'no';
		$params["navbar"]['NewConcepts'] = ($concepts_not AND $conceptdb->NewConcepts($player->PreviousLogin())) ? 'yes' : 'no';
		$params["navbar"]['NumGames'] = count($gamedb->ListCurrentGames($player->Name()));
		$params["main"]["skin"] = $player->GetSetting("Skin");
		$params["main"]["autorefresh"] = ($current == "Games") ? $player->GetSetting("Autorefresh") : 0; // apply only in games section
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
	// display all news when viewing news archive, display only recent news otherwise
	$params['website']['recent_news_only'] = (($selected == "News") AND !(isset($_POST['WebPage']) AND (array_shift($_POST['WebPage']) == "Show all news"))) ? 'yes' : 'no';
	break;


case 'Deck_edit':
	$currentdeck = $params['deck_edit']['CurrentDeck'] = $_POST['CurrentDeck'];
	$classfilter = $params['deck_edit']['ClassFilter'] = isset($_POST['ClassFilter']) ? $_POST['ClassFilter'] : 'Common';
	$costfilter = $params['deck_edit']['CostFilter'] = isset($_POST['CostFilter']) ? $_POST['CostFilter'] : 'none';
	$keywordfilter = $params['deck_edit']['KeywordFilter'] = isset($_POST['KeywordFilter']) ? $_POST['KeywordFilter'] : 'none';
	$advancedfilter = $params['deck_edit']['AdvancedFilter'] = isset($_POST['AdvancedFilter']) ? $_POST['AdvancedFilter'] : 'none';
	$supportfilter = $params['deck_edit']['SupportFilter'] = isset($_POST['SupportFilter']) ? $_POST['SupportFilter'] : 'none';

	$params['deck_edit']['keywords'] = $carddb->Keywords();

	// download the neccessary data
	$deck = $player->GetDeck($currentdeck);

	$params['deck_edit']['reset'] = ( (isset($_POST["reset_deck_prepare"] )) ? 'yes' : 'no');

	// load card display settings
	$params['deck_edit']['c_text'] = $player->GetSetting("Cardtext");
	$params['deck_edit']['c_img'] = $player->GetSetting("Images");
	$params['deck_edit']['c_keywords'] = $player->GetSetting("Keywords");
	$params['deck_edit']['c_oldlook'] = $player->GetSetting("OldCardLook");

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

	$params['deck_edit']['Take'] = ( $deck->DeckData->Count($classfilter) < 15 ) ? 'yes' : 'no';

	$filter = array();
	if( $classfilter != 'none' ) $filter['class'] = $classfilter;
	if( $keywordfilter != 'none' ) $filter['keyword'] = $keywordfilter;
	if( $costfilter != 'none' ) $filter['cost'] = $costfilter;
	if( $advancedfilter != 'none' ) $filter['advanced'] = $advancedfilter;
	if( $supportfilter != 'none' ) $filter['support'] = $supportfilter;
	$ids = array_diff($carddb->GetList($filter), $deck->DeckData->$classfilter); // cards not present in the deck
	$params['deck_edit']['CardList'] = $carddb->GetData($ids);

	foreach (array('Common', 'Uncommon', 'Rare') as $class)
		$params['deck_edit']['DeckCards'][$class] = $carddb->GetData($deck->DeckData->$class);

	$params['deck_edit']['Tokens'] = $deck->DeckData->Tokens;
	$params['deck_edit']['TokenKeywords'] = $carddb->TokenKeywords();

	break;


case 'Decks':
	$params['decks']['list'] = $list = $player->ListDecks();
	foreach ($list as $i => $deck_data) $params['decks']['list'][$i]['Ready'] = ($player->GetDeck($deck_data['Deckname'])->isReady()) ? 'yes' : 'no';
	$params['decks']['timezone'] = $player->GetSetting("Timezone"); 

	break;

case 'Concepts':
	// filter initialization
	$params['concepts']['date_val'] = $date = (isset($_POST['date_filter'])) ? $_POST['date_filter'] : 'none';
	$params['concepts']['author_val'] = $author = (isset($_POST['author_filter'])) ? postdecode($_POST['author_filter']) : 'none';
	$params['concepts']['state_val'] = $state = (isset($_POST['state_filter'])) ? $_POST['state_filter'] : 'none';

	if (!isset($_POST['CurrentOrder'])) $_POST['CurrentOrder'] = "DESC"; // default ordering
	if (!isset($_POST['CurrentCon'])) $_POST['CurrentCon'] =  "LastChange"; // default order condition

	$params['concepts']['current_order'] = $order = $_POST['CurrentOrder'];
	$params['concepts']['current_condition'] = $condition = $_POST['CurrentCon'];

	$current_page = ((isset($_POST['CurrentConPage'])) ? $_POST['CurrentConPage'] : 0);
	$params['concepts']['current_page'] = $current_page;

	$params['concepts']['list'] = $conceptdb->GetList($author, $date, $state, $condition, $order, $current_page);
	$page_count = $conceptdb->CountPages($author, $date, $state);
	$pages = array();
	if ($page_count > 0) for ($i = 0; $i < $page_count; $i++) $pages[$i] = $i;
	$params['concepts']['pages'] = $pages;
	$params['concepts']['page_count'] = $page_count;

	$params['concepts']['timesections'] = $messagedb->Timesections();
	$params['concepts']['PreviousLogin'] = $player->PreviousLogin();
	$params['concepts']['authors'] = $authors = $conceptdb->ListAuthors($date);
	$params['concepts']['mycards'] = (in_array($player->Name(), $authors) ? 'yes' : 'no');
	$params['concepts']['timezone'] = $player->GetSetting("Timezone");
	$params['concepts']['PlayerName'] = $player->Name();
	$params['concepts']['create_card'] = (($access_rights[$player->Type()]["create_card"]) ? 'yes' : 'no');
	$params['concepts']['edit_own_card'] = (($access_rights[$player->Type()]["edit_own_card"]) ? 'yes' : 'no');
	$params['concepts']['edit_all_card'] = (($access_rights[$player->Type()]["edit_all_card"]) ? 'yes' : 'no');
	$params['concepts']['delete_own_card'] = (($access_rights[$player->Type()]["delete_own_card"]) ? 'yes' : 'no');
	$params['concepts']['delete_all_card'] = (($access_rights[$player->Type()]["delete_all_card"]) ? 'yes' : 'no');
	$params['concepts']['c_text'] = $player->GetSetting("Cardtext");
	$params['concepts']['c_img'] = $player->GetSetting("Images");
	$params['concepts']['c_keywords'] = $player->GetSetting("Keywords");
	$params['concepts']['c_oldlook'] = $player->GetSetting("OldCardLook");

	break;


case 'Concepts_new':
	$params['concepts_new']['data'] = (isset($data)) ? $data : array();
	$params['concepts_new']['stored'] = (isset($data)) ? 'yes' : 'no';

	break;


case 'Concepts_edit':
	$concept = $conceptdb->GetConcept($concept_id);
	$inputs = array('Name', 'Class', 'Bricks', 'Gems', 'Recruits', 'Effect', 'Keywords', 'Picture', 'Note', 'State', 'Author');
	$data = array();
	foreach ($inputs as $input) $data[strtolower($input)] = $concept->ConceptData->$input;
	$data['id'] = $concept_id;
	$params['concepts_edit']['data'] = $data;

	$params['concepts_edit']['edit_all_card'] = (($access_rights[$player->Type()]["edit_all_card"]) ? 'yes' : 'no');
	$params['concepts_edit']['delete_own_card'] = (($access_rights[$player->Type()]["delete_own_card"]) ? 'yes' : 'no');
	$params['concepts_edit']['delete_all_card'] = (($access_rights[$player->Type()]["delete_all_card"]) ? 'yes' : 'no');
	$params['concepts_edit']['PlayerName'] = $player->Name();
	$params['concepts_edit']['delete'] = ((isset($_POST["delete_concept"])) ? 'yes' : 'no');
	$params['concepts_edit']['c_text'] = $player->GetSetting("Cardtext");
	$params['concepts_edit']['c_img'] = $player->GetSetting("Images");
	$params['concepts_edit']['c_keywords'] = $player->GetSetting("Keywords");
	$params['concepts_edit']['c_oldlook'] = $player->GetSetting("OldCardLook");

	break;


case 'Concepts_details':
	$concept = $conceptdb->GetConcept($concept_id);
	$inputs = array('Name', 'Class', 'Bricks', 'Gems', 'Recruits', 'Effect', 'Keywords', 'Picture', 'Note', 'State', 'Author', 'ThreadID');
	$data = array();
	foreach ($inputs as $input) $data[strtolower($input)] = $concept->ConceptData->$input;
	$data['id'] = $concept_id;
	$params['concepts_details']['data'] = $data;

	$params['concepts_details']['create_thread'] = ($access_rights[$player->Type()]["create_thread"]) ? 'yes' : 'no';
	$params['concepts_details']['edit_all_card'] = ($access_rights[$player->Type()]["edit_all_card"]) ? 'yes' : 'no';
	$params['concepts_details']['delete_own_card'] = ($access_rights[$player->Type()]["delete_own_card"]) ? 'yes' : 'no';
	$params['concepts_details']['delete_all_card'] = ($access_rights[$player->Type()]["delete_all_card"]) ? 'yes' : 'no';
	$params['concepts_details']['c_text'] = $player->GetSetting("Cardtext");
	$params['concepts_details']['c_img'] = $player->GetSetting("Images");
	$params['concepts_details']['c_keywords'] = $player->GetSetting("Keywords");
	$params['concepts_details']['c_oldlook'] = $player->GetSetting("OldCardLook");

	break;


case 'Players':	

	// defaults for list ordering
	if (!isset($_POST['CurrentOrder'])) $_POST['CurrentOrder'] = "DESC";
	if (!isset($_POST['CurrentCondition'])) $_POST['CurrentCondition'] = "Level";

	$params['players']['order'] = $order = $_POST['CurrentOrder'];
	$params['players']['condition'] = $condition = $_POST['CurrentCondition'];

	// filter initialization
	$params['players']['CurrentFilter'] = $filter = ((isset($_POST['player_filter'])) ? $_POST['player_filter'] : $player->GetSetting("PlayerFilter"));
	$params['players']['status_filter'] = $status_filter = (isset($_POST['status_filter'])) ? $_POST['status_filter'] : 'none';

	$params['players']['PlayerName'] = $player->Name();

	// check for active decks
	$params['players']['active_decks'] = count($player->ListReadyDecks());

	//retrieve layout setting
	$params['players']['show_nationality'] = $player->GetSetting("Nationality");
	$params['players']['show_avatars'] = $player->GetSetting("Avatarlist");

	$opponents = $gamedb->ListOpponents($player->Name());
	$challengesfrom = $gamedb->ListChallengesFrom($player->Name());
	$endedgames = $gamedb->ListEndedGames($player->Name());

	$params['players']['free_slots'] = $gamedb->CountFreeSlots1($player->Name());

	$params['players']['messages'] = ($access_rights[$player->Type()]["messages"]) ? 'yes' : 'no';
	$params['players']['send_challenges'] = ($access_rights[$player->Type()]["send_challenges"]) ? 'yes' : 'no';

	$current_page = ((isset($_POST['CurrentPlayersPage'])) ? $_POST['CurrentPlayersPage'] : 0);
	$params['players']['current_page'] = $current_page;

	$page_count = $playerdb->CountPages($filter, $status_filter);
	$pages = array();
	if ($page_count > 0) for ($i = 0; $i < $page_count; $i++) $pages[$i] = $i;
	$params['players']['pages'] = $pages;
	$params['players']['page_count'] = $page_count;

	// get the list of all existing players; (Username, Wins, Losses, Draws, Last Query, Free slots, Avatar, Country)
	$list = $playerdb->ListPlayers($filter, $status_filter, $condition, $order, $current_page);

	// for each player, display their name, score, and if conditions are met, also display the challenge button
	foreach ($list as $i => $data)
	{
		$opponent = $data['Username'];

		$entry = array();
		$entry['name'] = $data['Username'];
		$entry['rank'] = $data['UserType'];
		$entry['level'] = $data['Level'];
		$entry['exp'] = $data['Exp'] / $scoredb->NextLevel($data['Level']);
		$entry['wins'] = $data['Wins'];
		$entry['losses'] = $data['Losses'];
		$entry['draws'] = $data['Draws'];
		$entry['avatar'] = $data['Avatar'];
		$entry['status'] = $data['Status'];
		$entry['friendly_flag'] = $data['FriendlyFlag'];
		$entry['blind_flag'] = $data['BlindFlag'];
		$entry['country'] = $data['Country'];
		$entry['last_query'] = $data['Last Query'];
		$entry['free_slots'] = $data['Free slots'];
		$entry['inactivity'] = time() - strtotime($data['Last Query']);
		$entry['challenged'] = (array_search($opponent, $challengesfrom) !== false) ? 'yes' : 'no';
		$entry['playingagainst'] = (array_search($opponent, $opponents) !== false) ? 'yes' : 'no';
		$entry['waitingforack'] = (array_search($opponent, $endedgames) !== false) ? 'yes' : 'no';

		$params['players']['list'][] = $entry;
	}
	
	break;


case 'Profile':

	// retrieve name of a player we are currently viewing
	$cur_player = (isset($_POST['Profile'])) ? $_POST['Profile'] : $_POST['cur_player'];

	$p = $playerdb->GetPlayer($cur_player);
	$settings = $p->GetUserSettings();
	$score = $scoredb->GetScore($cur_player);

	$params['profile']['PlayerName'] = $p->Name();
	$params['profile']['PlayerType'] = $p->Type();
	$params['profile']['LastQuery'] = $p->LastQuery();
	$params['profile']['Registered'] = $p->Registered();
	$params['profile']['Firstname'] = $settings['Firstname'];
	$params['profile']['Surname'] = $settings['Surname'];
	$params['profile']['Gender'] = $settings['Gender'];
	$params['profile']['Country'] = $settings['Country'];
	$params['profile']['Status'] = $settings['Status'];
	$params['profile']['FriendlyFlag'] = $settings['FriendlyFlag'];
	$params['profile']['BlindFlag'] = $settings['BlindFlag'];
	$params['profile']['Avatar'] = $settings['Avatar'];
	$params['profile']['Email'] = $settings['Email'];
	$params['profile']['Imnumber'] = $settings['Imnumber'];
	$params['profile']['Hobby'] = $settings['Hobby'];
	$params['profile']['Level'] = $score->ScoreData->Level;
	$params['profile']['Exp'] = $score->ScoreData->Exp;
	$params['profile']['NextLevel'] = $scoredb->NextLevel($score->ScoreData->Level);
	$params['profile']['Wins'] = $score->ScoreData->Wins;
	$params['profile']['Losses'] = $score->ScoreData->Losses;
	$params['profile']['Draws'] = $score->ScoreData->Draws;

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
	$params['profile']['HiddenCards'] = $player->GetSetting("BlindFlag");
	$params['profile']['FriendlyPlay'] = $player->GetSetting("FriendlyFlag");
	$params['profile']['RandomDeck'] = $player->GetSetting("RandomDeck");
	$params['profile']['timezone'] = $player->GetSetting("Timezone");
	$params['profile']['send_challenges'] = ($access_rights[$player->Type()]["send_challenges"]) ? 'yes' : 'no';
	$params['profile']['messages'] = ($access_rights[$player->Type()]["messages"]) ? 'yes' : 'no';
	$params['profile']['change_rights'] = ($access_rights[$player->Type()]["change_rights"]) ? 'yes' : 'no';
	$params['profile']['system_notification'] = ($access_rights[$player->Type()]["system_notification"]) ? 'yes' : 'no';
	$params['profile']['change_all_avatar'] = ($access_rights[$player->Type()]["change_all_avatar"]) ? 'yes' : 'no';
	$params['profile']['reset_exp'] = ($access_rights[$player->Type()]["reset_exp"]) ? 'yes' : 'no';
	$params['profile']['free_slots'] = $gamedb->CountFreeSlots1($player->Name());
	$params['profile']['decks'] = $decks = $player->ListReadyDecks();
	$params['profile']['random_deck'] = (count($decks) > 0) ? $decks[array_rand($decks)] : '';

	$params['profile']['challenged'] = (array_search($cur_player, $gamedb->ListChallengesFrom($player->Name())) !== false) ? 'yes' : 'no';
	$params['profile']['playingagainst'] = (array_search($cur_player, $gamedb->ListOpponents($player->Name())) !== false) ? 'yes' : 'no';
	$params['profile']['waitingforack'] = (array_search($cur_player, $gamedb->ListEndedGames($player->Name())) !== false) ? 'yes' : 'no';

	$params['profile']['challenging'] = (isset($_POST['prepare_challenge'])) ? 'yes' : 'no';

	if ($params['profile']['challenged'])
	{
		$params['profile']['challenge'] = $messagedb->GetChallenge($player->Name(), $cur_player);
	}

	break;


case 'Messages':
	$params['messages']['PlayerName'] = $player->Name();
	$params['messages']['PreviousLogin'] = $player->PreviousLogin();
	$params['messages']['timezone'] = $player->GetSetting("Timezone");
	$params['messages']['RandomDeck'] = $player->GetSetting("RandomDeck");
	$params['messages']['system_name'] = SYSTEM_NAME;

	$decks = $params['messages']['decks'] = $player->ListReadyDecks();
	$params['messages']['random_deck'] = (count($decks) > 0) ? $decks[array_rand($decks)] : '';
	$params['messages']['deck_count'] = count($decks);
	$params['messages']['free_slots'] = $gamedb->CountFreeSlots2($player->Name());

	if (isset($_POST['incoming'])) $current_subsection = "incoming";
	elseif (isset($_POST['outgoing'])) $current_subsection = "outgoing";
	elseif (!isset($current_subsection)) $current_subsection = "incoming";

	$function_type = (($current_subsection == "incoming") ? "ListChallengesTo" : "ListChallengesFrom");
	$params['messages']['challenges'] = $messagedb->$function_type($player->Name());
	$params['messages']['challenges_count'] = count($params['messages']['challenges']);
	$params['messages']['current_subsection'] = $current_subsection;

	$current_location = ((isset($_POST['CurrentLocation'])) ? $_POST['CurrentLocation'] : "inbox");

	if (!isset($_POST['CurrentOrd'])) $_POST['CurrentOrd'] = "DESC"; // default ordering
	if (!isset($_POST['CurrentCond'])) $_POST['CurrentCond'] =  "Created"; // default order condition

	$params['messages']['current_order'] = $current_order = $_POST['CurrentOrd'];
	$params['messages']['current_condition'] = $current_condition = $_POST['CurrentCond'];

	$current_page = ((isset($_POST['CurrentMesPage'])) ? $_POST['CurrentMesPage'] : 0);
	$params['messages']['current_page'] = $current_page;

	// filter initialization
	$params['messages']['date_val'] = $date = (isset($_POST['date_filter'])) ? $_POST['date_filter'] : 'none';
	$params['messages']['name_val'] = $name = (isset($_POST['name_filter'])) ? postdecode($_POST['name_filter']) : 'none';

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
	$params['messages']['pages'] = $pages;
	$params['messages']['page_count'] = $page_count;

	$params['messages']['messages'] = $list;
	$params['messages']['messages_count'] = count($list);
	$params['messages']['current_location'] = $current_location;
	$params['messages']['timesections'] = $messagedb->Timesections();
	$params['messages']['name_filter'] = $name_list;
	$params['messages']['current_page'] = $current_page;

	$params['messages']['send_messages'] = (($access_rights[$player->Type()]["messages"]) ? 'yes' : 'no');
	$params['messages']['accept_challenges'] = (($access_rights[$player->Type()]["accept_challenges"]) ? 'yes' : 'no');
	$params['messages']['see_all_messages'] = (($access_rights[$player->Type()]["see_all_messages"]) ? 'yes' : 'no');

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
	$params['games']['timezone'] = $player->GetSetting("Timezone");
	$params['games']['games_details'] = $player->GetSetting("GamesDetails");
	$params['games']['BlindFlag'] = $player->GetSetting("BlindFlag");
	$params['games']['FriendlyFlag'] = $player->GetSetting("FriendlyFlag");
	$params['games']['RandomDeck'] = $player->GetSetting("RandomDeck");

	$list = $gamedb->ListGamesData($player->Name());
	if (count($list) > 0)
	{
		foreach ($list as $i => $data)
		{
			$opponent = ($data['Player1'] != $player->Name()) ? $data['Player1'] : $data['Player2'];
			$last_seen = $playerdb->LastQuery($opponent);
			$inactivity = time() - strtotime($last_seen);

			$params['games']['list'][$i]['opponent'] = $opponent;
			$params['games']['list'][$i]['ready'] = ($data['Current'] == $player->Name()) ? 'yes' : 'no';
			$params['games']['list'][$i]['gameid'] = $data['GameID'];
			$params['games']['list'][$i]['gamestate'] = $data['State'];
			$params['games']['list'][$i]['round'] = $data['Round'];
			$params['games']['list'][$i]['active'] = ($inactivity < 60*10) ? 'yes' : 'no';
			$params['games']['list'][$i]['isdead'] = ($inactivity  > 60*60*24*7*3) ? 'yes' : 'no';
			$params['games']['list'][$i]['gameaction'] = $data['Last Action'];
			$params['games']['list'][$i]['lastseen'] = $last_seen;
		}
	}

	if (isset($_POST['free_games'])) $subsection = "free_games";
	elseif (isset($_POST['hosted_games'])) $subsection = "hosted_games";
	elseif (!isset($subsection)) $subsection = "free_games";
	$params['games']['current_subsection'] = $subsection;
	$params['games']['HiddenCards'] = $hidden_f = (isset($_POST['HiddenCards'])) ? $_POST['HiddenCards'] : "ignore";
	$params['games']['FriendlyPlay'] = $friendly_f = (isset($_POST['FriendlyPlay'])) ? $_POST['FriendlyPlay'] : "ignore";

	$hostedgames = $gamedb->ListHostedGames($player->Name());
	$free_games = $gamedb->ListFreeGames($player->Name(), $hidden_f, $friendly_f);
	$params['games']['free_slots'] = $gamedb->CountFreeSlots1($player->Name());
	$params['games']['decks'] = $decks = $player->ListReadyDecks();
	$params['games']['random_deck'] = (count($decks) > 0) ? $decks[array_rand($decks)] : '';

	if (count($free_games) > 0)
	{
		foreach ($free_games as $i => $data)
		{
			$inactivity = time() - strtotime($playerdb->LastQuery($data['Player1']));

			$params['games']['free_games'][$i]['opponent'] = $data['Player1'];
			$params['games']['free_games'][$i]['gameid'] = $data['GameID'];
			$params['games']['free_games'][$i]['active'] = ($inactivity < 60*10) ? 'yes' : 'no';
			$params['games']['free_games'][$i]['gameaction'] = $data['Last Action'];
			$params['games']['free_games'][$i]['friendly_play'] = (strpos($data['GameModes'], 'FriendlyPlay') !== false) ? 'yes' : 'no';
			$params['games']['free_games'][$i]['hidden_cards'] = (strpos($data['GameModes'], 'HiddenCards') !== false) ? 'yes' : 'no';
		}
	}

	if (count($hostedgames) > 0)
	{
		foreach ($hostedgames as $i => $data)
		{
			$params['games']['hosted_games'][$i]['gameid'] = $data['GameID'];
			$params['games']['hosted_games'][$i]['gameaction'] = $data['Last Action'];
			$params['games']['hosted_games'][$i]['friendly_play'] = (strpos($data['GameModes'], 'FriendlyPlay') !== false) ? 'yes' : 'no';
			$params['games']['hosted_games'][$i]['hidden_cards'] = (strpos($data['GameModes'], 'HiddenCards') !== false) ? 'yes' : 'no';
		}
	}

	break;


case 'Game':
	$gameid = $_POST['CurrentGame'];

	// prepare the neccessary data
	$game = $gamedb->GetGame($gameid);
	$player1 = $game->Name1();
	$player2 = $game->Name2();

	$opponent = $playerdb->GetPlayer(($player1 != $player->Name()) ? $player1 : $player2);
	$mydata = &$game->GameData[$player->Name()];
	$hisdata = &$game->GameData[$opponent->Name()];

	$params['game']['CurrentGame'] = $gameid;
	$params['game']['current'] = $current;

	$params['game']['chat'] = (($access_rights[$player->Type()]["chat"]) ? 'yes' : 'no');

	// load needed settings
	$params['game']['c_text'] = $player->GetSetting("Cardtext");
	$params['game']['c_img'] = $player->GetSetting("Images");
	$params['game']['c_keywords'] = $player->GetSetting("Keywords");
	$params['game']['c_oldlook'] = $player->GetSetting("OldCardLook");

	$params['game']['minimize'] = $player->GetSetting("Minimize");
	$params['game']['mycountry'] = $player->GetSetting("Country");
	$params['game']['hiscountry'] = $opponent->GetSetting("Country");
	$params['game']['timezone'] = $player->GetSetting("Timezone");
	$params['game']['Background'] = $player->GetSetting("Background");

	$params['game']['GameState'] = $game->State;
	$params['game']['Round'] = $game->Round;
	$params['game']['Outcome'] = $game->Outcome();
	$params['game']['EndType'] = $game->EndType;
	$params['game']['Winner'] = $game->Winner;
	$params['game']['PlayerName'] = $player->Name();
	$params['game']['OpponentName'] = $opponent->Name();
	$params['game']['Current'] = $game->Current;
	$params['game']['Timestamp'] = $game->LastAction;
	$params['game']['has_note'] = ($game->GetNote($player->Name()) != "") ? 'yes' : 'no';
	$params['game']['HiddenCards'] = $game->GetGameMode('HiddenCards');
	$params['game']['FriendlyPlay'] = $game->GetGameMode('FriendlyPlay');
	$params['game']['max_tower'] = $game_config['max_tower'];
	$params['game']['max_wall'] = $game_config['max_wall'];

	// my hand
	$myhand = $mydata->Hand;
	$handdata = $carddb->GetData($myhand);
	foreach( $handdata as $i => $card )
	{
		$entry = array();
		$entry['CardID'] = $card['id'];
		$entry['Data'] = $card;
		$entry['Playable'] = ( $mydata->Bricks >= $card['bricks'] and $mydata->Gems >= $card['gems'] and $mydata->Recruits >= $card['recruits'] and $game->State == 'in progress' and $game->Current == $player->Name() ) ? 'yes' : 'no';
		$entry['Modes'] = $card['modes'];
		$entry['NewCard'] = ( isset($mydata->NewCards[$i]) ) ? 'yes' : 'no';
		$params['game']['MyHand'][$i] = $entry;
	}

	$params['game']['MyBricks'] = $mydata->Bricks;
	$params['game']['MyGems'] = $mydata->Gems;
	$params['game']['MyRecruits'] = $mydata->Recruits;
	$params['game']['MyQuarry'] = $mydata->Quarry;
	$params['game']['MyMagic'] = $mydata->Magic;
	$params['game']['MyDungeons'] = $mydata->Dungeons;
	$params['game']['MyTower'] = $mydata->Tower;
	$params['game']['MyWall'] = $mydata->Wall;
	
	// my discarded cards
	if( count($mydata->DisCards[0]) > 0 )
		$params['game']['MyDisCards0'] = $carddb->GetData($mydata->DisCards[0]); // cards discarded from my hand
	if( count($mydata->DisCards[1]) > 0 )
		$params['game']['MyDisCards1'] = $carddb->GetData($mydata->DisCards[1]); // cards discarded from his hand

	// my last played cards
	$mylastcard = array();
	$tmp = $carddb->GetData($mydata->LastCard);
	foreach( $tmp as $i => $card )
	{
		$mylastcard[$i]['CardData'] = $card;
		$mylastcard[$i]['CardAction'] = $mydata->LastAction[$i];
		$mylastcard[$i]['CardMode'] = $mydata->LastMode[$i];
		$mylastcard[$i]['CardPosition'] = $i;
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
	$handdata = $carddb->GetData($hishand);
	foreach( $handdata as $i => $card )
	{
		$entry = array();
		$entry['Data'] = $card;
		$entry['NewCard'] = ( isset($hisdata->NewCards[$i]) ) ? 'yes' : 'no';
		$entry['Revealed'] = ( isset($hisdata->Revealed[$i]) ) ? 'yes' : 'no';
		$params['game']['HisHand'][$i] = $entry;
	}

	$params['game']['HisBricks'] = $hisdata->Bricks;
	$params['game']['HisGems'] = $hisdata->Gems;
	$params['game']['HisRecruits'] = $hisdata->Recruits;
	$params['game']['HisQuarry'] = $hisdata->Quarry;
	$params['game']['HisMagic'] = $hisdata->Magic;
	$params['game']['HisDungeons'] = $hisdata->Dungeons;
	$params['game']['HisTower'] = $hisdata->Tower;
	$params['game']['HisWall'] = $hisdata->Wall;

	// his discarded cards
	if( count($hisdata->DisCards[0]) > 0 )
		$params['game']['HisDisCards0'] = $carddb->GetData($hisdata->DisCards[0]); // cards discarded from my hand
	if( count($hisdata->DisCards[1]) > 0 )
		$params['game']['HisDisCards1'] = $carddb->GetData($hisdata->DisCards[1]); // cards discarded from his hand
	
	// his last played cards
	$hislastcard = array();
	$tmp = $carddb->GetData($hisdata->LastCard);
	foreach( $tmp as $i => $card )
	{
		$hislastcard[$i]['CardData'] = $card;
		$hislastcard[$i]['CardAction'] = $hisdata->LastAction[$i];
		$hislastcard[$i]['CardMode'] = $hisdata->LastMode[$i];
		$hislastcard[$i]['CardPosition'] = $i;
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

	foreach ($list as $i => $data)
	{
		$cur_game = $gamedb->GetGame($data['GameID']);
		$cur_opponent = ($cur_game->Name1() != $player->Name()) ? $cur_game->Name1() : $cur_game->Name2();

		$color = ''; // no extra color default
		if ($cur_game->Current == $player->Name()) $color = 'lime'; // when it is your turn
		if ($cur_game->State == 'in progress' and $playerdb->isDead($cur_opponent)) $color = 'gray'; // when game can be aborted
		if ($cur_game->State != 'in progress') $color = '#ff69b4'; // when game is finished color HotPink

		$params['game']['GameList'][$i]['Value'] = $cur_game->ID();
		$params['game']['GameList'][$i]['Content'] = 'vs. '.htmlencode($cur_opponent);
		$params['game']['GameList'][$i]['Selected'] = (($cur_game->ID() == $_POST['CurrentGame']) ? 'yes' : 'no');
		$params['game']['GameList'][$i]['Color'] = $color;
	}
	// - </quick game switching menu>

	// - <'jump to next game' button>

	$params['game']['num_games_your_turn'] = count($gamedb->ListCurrentGames($player->Name()));

	// - </'jump to next game' button>

	// - <game state indicator>
	$params['game']['opp_isOnline'] = (($opponent->isOnline()) ? 'yes' : 'no');
	$params['game']['opp_isDead'] = (($opponent->isDead()) ? 'yes' : 'no');
	$params['game']['surrender'] = ((isset($_POST["surrender"])) ? 'yes' : 'no');
	$params['game']['finish_game'] = ((time() - strtotime($game->LastAction) >= 60*60*24*7*3 and $game->Current != $player->Name()) ? 'yes' : 'no');

	// your resources and tower
	$changes = array ('Quarry'=> '', 'Magic'=> '', 'Dungeons'=> '', 'Bricks'=> '', 'Gems'=> '', 'Recruits'=> '', 'Tower'=> '', 'Wall'=> '');
	foreach ($changes as $attribute => $change)
		$changes[$attribute] = (($mydata->Changes[$attribute] > 0) ? '+' : '').$mydata->Changes[$attribute];

	$params['game']['mychanges'] = $changes;

	// opponent's resources and tower
	$changes = array ('Quarry'=> '', 'Magic'=> '', 'Dungeons'=> '', 'Bricks'=> '', 'Gems'=> '', 'Recruits'=> '', 'Tower'=> '', 'Wall'=> '');
	foreach ($changes as $attribute => $change)
		$changes[$attribute] = (($hisdata->Changes[$attribute] > 0) ? '+' : '').$hisdata->Changes[$attribute];

	$params['game']['hischanges'] = $changes;

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
	$deck = $game->GameData[$player->Name()]->Deck;

	//load needed settings
	$params['deck_view']['c_text'] = $player->GetSetting("Cardtext");
	$params['deck_view']['c_img'] = $player->GetSetting("Images");
	$params['deck_view']['c_keywords'] = $player->GetSetting("Keywords");
	$params['deck_view']['c_oldlook'] = $player->GetSetting("OldCardLook");

	$params['deck_view']['CurrentGame'] = $gameid;

	foreach (array('Common', 'Uncommon', 'Rare') as $class)
		$params['deck_view']['DeckCards'][$class] = $carddb->GetData($deck->$class);
	
	break;


case 'Game_note':
	$gameid = $_POST['CurrentGame'];
	$game = $gamedb->GetGame($gameid);

	$params['game_note']['CurrentGame'] = $gameid;
	$params['game_note']['text'] = (isset($new_note)) ? $new_note : $game->GetNote($player->Name());

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
	$params['forum_thread']['concept'] = $conceptdb->FindConcept($thread_id);

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

case 'Replays':
	if (!isset($current_page)) $current_page = 0;
	$params['replays']['current_page'] = $current_page;
	$params['replays']['PlayerFilter'] = $player_f = (isset($_POST['PlayerFilter'])) ? $_POST['PlayerFilter'] : "none";
	$params['replays']['HiddenCards'] = $hidden_f = (isset($_POST['HiddenCards'])) ? $_POST['HiddenCards'] : "ignore";
	$params['replays']['FriendlyPlay'] = $friendly_f = (isset($_POST['FriendlyPlay'])) ? $_POST['FriendlyPlay'] : "ignore";
	$params['replays']['VictoryFilter'] = $victory_f = (isset($_POST['VictoryFilter'])) ? $_POST['VictoryFilter'] : "none";
	$params['replays']['list'] = $replaydb->ListReplays($player_f, $hidden_f, $friendly_f, $victory_f, $current_page);
	$params['replays']['count_pages'] = $count = $replaydb->CountPages($player_f, $hidden_f, $friendly_f, $victory_f);
	$pages = array();
	for ($i = 0; $i < $count; $i++) $pages[$i] = $i;
	$params['replays']['pages'] = $pages;
	$params['replays']['timezone'] = $player->GetSetting("Timezone");
	$params['replays']['players'] = $replaydb->ListPlayers();

	break;

case 'Replay':
	$params['replay']['CurrentReplay'] = $gameid = $_POST['CurrentReplay'];
	$params['replay']['PlayerView'] = $player_view = (isset($_POST['PlayerView'])) ? $_POST['PlayerView'] : 1;
	$params['replay']['CurrentTurn'] = $turn = (isset($_POST['turn_selector']) ? $_POST['turn_selector'] : 1);

	// prepare the necessary data
	$replay = $replaydb->GetReplay($gameid, $turn);

	// determine player view
	$player1 = ($player_view == 1) ? $replay->Name1() : $replay->Name2();
	$player2 = ($player_view == 1) ? $replay->Name2() : $replay->Name1();

	$replay_data = $replay->ReplayData;
	$p1data = $replay_data[$player1];
	$p2data = $replay_data[$player2];

	// load needed settings
	$params['replay']['c_text'] = $player->GetSetting("Cardtext");
	$params['replay']['c_img'] = $player->GetSetting("Images");
	$params['replay']['c_keywords'] = $player->GetSetting("Keywords");
	$params['replay']['c_oldlook'] = $player->GetSetting("OldCardLook");
	$params['replay']['minimize'] = $player->GetSetting("Minimize");
	$params['replay']['Background'] = $player->GetSetting("Background");

	$params['replay']['turns'] = $turns = $replay->NumberOfTurns();
	$pages = array();
	for ($i = 1; $i <= $turns; $i++) $pages[$i] = $i;
	$params['replay']['pages'] = $pages;
	$params['replay']['Round'] = $replay->Round;
	$params['replay']['Outcome'] = $replay->Outcome();
	$params['replay']['EndType'] = $replay->EndType;
	$params['replay']['Winner'] = $replay->Winner;
	$params['replay']['Player1'] = $player1;
	$params['replay']['Player2'] = $player2;
	$params['replay']['Current'] = $replay->Current;
	$params['replay']['HiddenCards'] = $replay->GetGameMode('HiddenCards');
	$params['replay']['FriendlyPlay'] = $replay->GetGameMode('FriendlyPlay');
	$params['replay']['max_tower'] = $game_config['max_tower'];
	$params['replay']['max_wall'] = $game_config['max_wall'];

	// player1 hand
	$p1hand = $p1data->Hand;
	$handdata = $carddb->GetData($p1hand);
	foreach( $handdata as $i => $card )
	{
		$entry = array();
		$entry['Data'] = $card;
		$entry['NewCard'] = ( isset($p1data->NewCards[$i]) ) ? 'yes' : 'no';
		$params['replay']['p1Hand'][$i] = $entry;
	}

	$params['replay']['p1Bricks'] = $p1data->Bricks;
	$params['replay']['p1Gems'] = $p1data->Gems;
	$params['replay']['p1Recruits'] = $p1data->Recruits;
	$params['replay']['p1Quarry'] = $p1data->Quarry;
	$params['replay']['p1Magic'] = $p1data->Magic;
	$params['replay']['p1Dungeons'] = $p1data->Dungeons;
	$params['replay']['p1Tower'] = $p1data->Tower;
	$params['replay']['p1Wall'] = $p1data->Wall;

	// player1 discarded cards
	if( count($p1data->DisCards[0]) > 0 )
		$params['replay']['p1DisCards0'] = $carddb->GetData($p1data->DisCards[0]); // cards discarded from player1 hand
	if( count($p1data->DisCards[1]) > 0 )
		$params['replay']['p1DisCards1'] = $carddb->GetData($p1data->DisCards[1]); // cards discarded from player2 hand

	// player1 last played cards
	$p1lastcard = array();
	$tmp = $carddb->GetData($p1data->LastCard);
	foreach( $tmp as $i => $card )
	{
		$p1lastcard[$i]['CardData'] = $card;
		$p1lastcard[$i]['CardAction'] = $p1data->LastAction[$i];
		$p1lastcard[$i]['CardMode'] = $p1data->LastMode[$i];
		$p1lastcard[$i]['CardPosition'] = $i;
	}
	$params['replay']['p1LastCard'] = $p1lastcard;

	// player1 tokens
	$p1_token_names = $p1data->TokenNames;
	$p1_token_values = $p1data->TokenValues;
	$p1_token_changes = $p1data->TokenChanges;

	$p1_tokens = array();
	foreach ($p1_token_names as $index => $value)
	{
		$p1_tokens[$index]['Name'] = $p1_token_names[$index];
		$p1_tokens[$index]['Value'] = $p1_token_values[$index];
		$p1_tokens[$index]['Change'] = $p1_token_changes[$index];
	}

	$params['replay']['p1Tokens'] = $p1_tokens;

	// player2 hand
	$p2hand = $p2data->Hand;
	$handdata = $carddb->GetData($p2hand);
	foreach( $handdata as $i => $card )
	{
		$entry = array();
		$entry['Data'] = $card;
		$entry['NewCard'] = ( isset($p2data->NewCards[$i]) ) ? 'yes' : 'no';
		$params['replay']['p2Hand'][$i] = $entry;
	}

	$params['replay']['p2Bricks'] = $p2data->Bricks;
	$params['replay']['p2Gems'] = $p2data->Gems;
	$params['replay']['p2Recruits'] = $p2data->Recruits;
	$params['replay']['p2Quarry'] = $p2data->Quarry;
	$params['replay']['p2Magic'] = $p2data->Magic;
	$params['replay']['p2Dungeons'] = $p2data->Dungeons;
	$params['replay']['p2Tower'] = $p2data->Tower;
	$params['replay']['p2Wall'] = $p2data->Wall;

	// player2 discarded cards
	if( count($p2data->DisCards[0]) > 0 )
		$params['replay']['p2DisCards0'] = $carddb->GetData($p2data->DisCards[0]); // cards discarded from player1 hand
	if( count($p2data->DisCards[1]) > 0 )
		$params['replay']['p2DisCards1'] = $carddb->GetData($p2data->DisCards[1]); // cards discarded from player2 hand

	// player2 last played cards
	$p2lastcard = array();
	$tmp = $carddb->GetData($p2data->LastCard);
	foreach( $tmp as $i => $card )
	{
		$p2lastcard[$i]['CardData'] = $card;
		$p2lastcard[$i]['CardAction'] = $p2data->LastAction[$i];
		$p2lastcard[$i]['CardMode'] = $p2data->LastMode[$i];
		$p2lastcard[$i]['CardPosition'] = $i;
	}
	$params['replay']['p2LastCard'] = $p2lastcard;

	// player2 tokens
	$p2_token_names = $p2data->TokenNames;
	$p2_token_values = $p2data->TokenValues;
	$p2_token_changes = $p2data->TokenChanges;

	$p2_tokens = array();
	foreach ($p2_token_names as $index => $value)
	{
		$p2_tokens[$index]['Name'] = $p2_token_names[$index];
		$p2_tokens[$index]['Value'] = $p2_token_values[$index];
		$p2_tokens[$index]['Change'] = $p2_token_changes[$index];
	}

	$params['replay']['p2Tokens'] = array_reverse($p2_tokens);

	// changes

	// player1 resources and tower
	$changes = array ('Quarry'=> '', 'Magic'=> '', 'Dungeons'=> '', 'Bricks'=> '', 'Gems'=> '', 'Recruits'=> '', 'Tower'=> '', 'Wall'=> '');
	foreach ($changes as $attribute => $change)
		$changes[$attribute] = (($p1data->Changes[$attribute] > 0) ? '+' : '').$p1data->Changes[$attribute];

	$params['replay']['p1changes'] = $changes;

	// player2 resources and tower
	$changes = array ('Quarry'=> '', 'Magic'=> '', 'Dungeons'=> '', 'Bricks'=> '', 'Gems'=> '', 'Recruits'=> '', 'Tower'=> '', 'Wall'=> '');
	foreach ($changes as $attribute => $change)
		$changes[$attribute] = (($p2data->Changes[$attribute] > 0) ? '+' : '').$p2data->Changes[$attribute];

	$params['replay']['p2changes'] = $changes;

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
