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
	require_once('CKeyword.php');
	require_once('CConcept.php');
	require_once('CDeck.php');
	require_once('CGame.php');
	require_once('CReplay.php');
	require_once('CSettings.php');
	require_once('CChat.php');
	require_once('CPlayer.php');
	require_once('CMessage.php');
	require_once('CPost.php');
	require_once('CThread.php');
	require_once('CForum.php');
	require_once('CStatistics.php');
	require_once('utils.php');
	require_once('Access.php');
	require_once('parser/parse.php');
	
	$db = new CDatabase($server, $username, $password, $database);

	date_default_timezone_set("Etc/UTC");
	$db->Query("SET time_zone='Etc/UTC'");
	
	$logindb = new CLogin($db);
	$scoredb = new CScores($db);
	$carddb = new CCards();
	$keyworddb = new CKeywords();
	$conceptdb = new CConcepts($db);
	$deckdb = new CDecks($db);
	$gamedb = new CGames($db);
	$replaydb = new CReplays($db);
	$chatdb = new CChats($db);
	$settingdb = new CSettings($db);
	$playerdb = new CPlayers($db);
	$messagedb = new CMessage($db);
	$forum = new CForum($db);
	$statistics = new CStatistics($db);

	// process GET request
	foreach ($_GET as $param_name => $param_value) $_POST[$param_name] = $param_value;

	$current = (isset($_POST['location'])) ? $_POST['location'] : "Webpage"; // set a meaningful default

	$session = $logindb->Login();

	do { // dummy scope
	
	if( !$session )
	{
		if (isset($_POST['Login']))
		{
			$current = "Webpage";
			$information = "Login failed.";
		}
		elseif (isset($_POST['Registration']))
		{
			$current = "Registration";
		}
		elseif (isset($_POST['ReturnToLogin'])) // TODO: rename this
		{
			$current = "Webpage";
		}
		elseif (isset($_POST['Register']))
		{
			if (!isset($_POST['NewUsername']) || !isset($_POST['NewPassword']) || !isset ($_POST['NewPassword2']) || trim($_POST['NewUsername']) == '' || trim($_POST['NewPassword']) == '' || trim($_POST['NewPassword2']) == '')
			{
				$current = "Registration";
				$error = "Please enter all required inputs.";
			}
//	  elseif (preg_match("(<|>|'|\"|:)", $_POST['NewUsername']))
//	  {
//		  $current = "Registration";
//		  $error = "Name contains invalid characters.";
//	  }
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
				$current = "Webpage";
				$information = "User registered. You may now log in.";
			}
		}
		else
		{
			$public_sections = array('Webpage', 'Help', 'Novels');
			if (!in_array($current, $public_sections)) $current = "Webpage";
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
			$current = "Webpage";
			$error = "Failed to load player data! Please report this!";
			break;
		}

		// verify login privilege
		if( !$access_rights[$player->Type()]["login"] )
		{
			$session = false;
			$current = "Webpage";
			$warning = "This user is not permitted to log in.";
			break;
		}

		// login page messages
		if (isset($_POST['Login']))
		{
			$current = "Webpage"; // new sessions default to here
		}
		else
		
		// navigation bar messages
		if (isset($_POST['Logout']))
		{
			$logindb->Logout($session);
			
			$information = "You have successfully logged out.";
			$current = "Webpage";
		}
		else
		
		// inner-page messages
		{
			// begin cards related messages

			if (isset($_POST['cards_filter'])) // Cards -> Apply filters
			{
				$_POST['CurrentCardsPage'] = 0;
				$current = 'Cards';

				break;
			}

			if (isset($_POST['select_page_cards'])) // Cards -> select page (previous and next button)
			{
				$_POST['CurrentCardsPage'] = $_POST['select_page_cards'];
				$current = 'Cards';

				break;
			}

			if (isset($_POST['seek_page_cards'])) // Cards -> select page (page selector)
			{
				$_POST['CurrentCardsPage'] = $_POST['page_selector'];
				$current = 'Cards';

				break;
			}

			if (isset($_POST['card_thread'])) // find matching thread for specified card or create a new matching thread
			{
				$card_id = $_POST['card_thread'];

				// check access rights
				if (!$access_rights[$player->Type()]["create_thread"]) { $error = 'Access denied.'; $current = 'Cards'; break; }

				// check value
				if (!is_numeric($card_id)) { $error = "Invalid card id"; $current = "Cards"; break; }

				$thread_id = $forum->Threads->CardThread($card_id);
				if (!$thread_id)
				{
					$card = $carddb->GetCard($card_id);
					$title = $card->CardData->Name;
					$section_id = 7; // section for discussing balance changes
					$new_thread = $forum->Threads->CreateThread($title, $player->Name(), 'normal', $section_id, $card_id);
					if (!$new_thread) { $error = "Failed to create new thread"; $current = "Cards"; break; }

					$thread_id = $new_thread;
				}

				$_POST['CurrentThread'] = $thread_id;
				$current = 'Thread_details';

				break;
			}

			// end cards related messages

			// concepts-related messages

			$temp = array("asc" => "ASC", "desc" => "DESC");
			foreach($temp as $type => $order_val)
			{
				if (isset($_POST['concepts_ord_'.$type])) // select ascending or descending order in card concepts list
				{
					$_POST['CurrentCon'] = $_POST['concepts_ord_'.$type];
					$_POST['CurrentOrder'] = $order_val;

					$current = "Concepts";

					break;
				}
			}

			if (isset($_POST['concepts_filter'])) // use filter
			{
				$_POST['CurrentConPage'] = 0;

				$current = 'Concepts';
				break;
			}

			if (isset($_POST['my_concepts'])) // use "my cards" quick button
			{
				$_POST['date_filter_concepts'] = "none";
				$_POST['author_filter'] = $player->Name();
				$_POST['state_filter'] = "none";
				$_POST['CurrentConPage'] = 0;

				$current = 'Concepts';
				break;
			}

			if (isset($_POST['select_page_concepts'])) // Concepts -> select page (previous and next button)
			{
				$_POST['CurrentConPage'] = $_POST['select_page_concepts'];
				$current = "Concepts";

				break;
			}

			if (isset($_POST['seek_page_concepts'])) // Concepts -> select page (Jump to page)
			{
				$_POST['CurrentConPage'] = $_POST['page_selector'];
				$current = "Concepts";

				break;
			}

			if (isset($_POST['new_concept'])) // go to new card formular
			{
				// check access rights
				if (!$access_rights[$player->Type()]["create_card"]) { $error = 'Access denied.'; $current = 'Concepts'; break; }
				$current = "Concepts_new";

				break;
			}

			if (isset($_POST['create_concept'])) // create new card concept
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

				$_POST['CurrentConcept'] = $concept_id;
				$information = "New card created";
				$current = "Concepts_edit";

				break;
			}

			if (isset($_POST['edit_concept'])) // go to card edit formaular
			{
				$concept_id = $_POST['edit_concept'];

				if (!$conceptdb->Exists($concept_id)) { $error = 'No such card.'; $current = 'Concepts'; break; }
				$concept = $conceptdb->GetConcept($concept_id);

				// check access rights
				if (!($access_rights[$player->Type()]["edit_all_card"] OR ($access_rights[$player->Type()]["edit_own_card"] AND $player->Name() == $concept->ConceptData->Author))) { $error = 'Access denied.'; $current = 'Concepts'; break; }

				$_POST['CurrentConcept'] = $concept_id;
				$current = "Concepts_edit";

				break;
			}

			if (isset($_POST['save_concept'])) // save edited changes
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

			if (isset($_POST['save_concept_special'])) // save edited changes (special access)
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

			if (isset($_POST['upload_pic'])) // upload card_picture
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

			if (isset($_POST['clear_img'])) // clear card picture
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

			if (isset($_POST['delete_concept'])) // delete card concept
			{
				$concept_id = $_POST['delete_concept'];

				if (!$conceptdb->Exists($concept_id)) { $error = 'No such card.'; $current = 'Concepts'; break; }
				$concept = $conceptdb->GetConcept($concept_id);

				// check access rights
				if (!($access_rights[$player->Type()]["delete_all_card"] OR ($access_rights[$player->Type()]["delete_own_card"] AND $player->Name() == $concept->ConceptData->Author))) { $error = 'Access denied.'; $current = 'Concepts'; break; }

				$_POST['CurrentConcept'] = $concept_id;
				$current = "Concepts_edit";

				break;
			}

			if (isset($_POST['delete_concept_confirm'])) // delete card concept confirmation
			{
				$concept_id = $_POST['CurrentConcept'];

				if (!$conceptdb->Exists($concept_id)) { $error = 'No such card.'; $current = 'Concepts'; break; }
				$concept = $conceptdb->GetConcept($concept_id);
				$thread_id = $concept->ThreadID();
				$concept_name = $concept->Name();

				// check access rights
				if (!($access_rights[$player->Type()]["delete_all_card"] OR ($access_rights[$player->Type()]["delete_own_card"] AND $player->Name() == $concept->ConceptData->Author))) { $error = 'Access denied.'; $current = 'Concepts'; break; }

				$result = $concept->DeleteConcept();
				if (!$result) { $error = "Failed to delete card"; $current = "Concepts_edit"; break; }

				$result = $forum->Threads->EditThread($thread_id, $concept_name.' [Deleted]', 'normal');					
				if (!$result) { $error = "Failed to rename thread"; $current = "Concepts"; break; }

				$information = "Card deleted";
				$current = "Concepts";

				break;
			}

			if (isset($_POST['concept_thread'])) // create new thread for specified card concept
			{
				$concept_id = $_POST['CurrentConcept'];
				$section_id = 6; // section for discussing concepts

				// check access rights
				if (!$access_rights[$player->Type()]["create_thread"]) { $error = 'Access denied.'; $current = 'Concepts_details'; break; }

				$concept = $conceptdb->GetConcept($concept_id);
				if (!$concept) { $error = 'No such card.'; $current = 'Concepts'; break; }
				$thread_id = $concept->ThreadID();
				if ($thread_id > 0) { $error = "Thread already exists"; $current = "Thread_details"; $_POST['CurrentThread'] = $thread_id; break; }

				$concept_name = $concept->Name();

				$new_thread = $forum->Threads->CreateThread($concept_name, $player->Name(), 'normal', $section_id);
				if ($new_thread === false) { $error = "Failed to create new thread"; $current = "Concepts_details"; break; }
				// $new_thread contains ID of currently created thread, which can be 0

				$result = $concept->AssignThread($new_thread);
				if (!$result) { $error = "Failed to assign new thread"; $current = "Concepts_details"; break; }

				$_POST['CurrentThread'] = $new_thread;
				$information = "Thread created";
				$current = 'Thread_details';

				break;
			}

			// end concepts-related messages

			// deck-related messages

			if (isset($_POST['add_card'])) // Decks -> Modify this deck -> Take
			{
				$cardid = $_POST['add_card'];
				$deckname = $_POST['CurrentDeck'];

				//download deck
				$deck = $player->GetDeck($deckname);
				if (!$deck) { $error = 'No such deck.'; $current = 'Decks'; break; }

				// add card, saving the deck on success
				if( $deck->AddCard($cardid) )
				{
					// set tokens when deck is finished and player forgot to set them
					if ((count(array_diff($deck->DeckData->Tokens, array('none'))) == 0) AND $deck->isReady())
						$deck->SetAutoTokens();
					
					$deck->SaveDeck();
				}
				else
					$error = 'Unable to add the chosen card to this deck.';

				$current = 'Deck_edit';
				break;
			}

			if (isset($_POST['return_card'])) // Decks -> Modify this deck -> Return
			{
				$cardid = $_POST['return_card'];
				$deckname = $_POST['CurrentDeck'];

				// download deck
				$deck = $player->GetDeck($deckname);
				if (!$deck) { $error = 'No such deck.'; $current = 'Decks'; break; }

				// remove card, saving the deck on success
				if( $deck->ReturnCard($cardid) )
					$deck->SaveDeck();
				else
					$error = 'Unable to remove the chosen card from this deck.';

				$current = 'Deck_edit';
				break;
			}

			if (isset($_POST['set_tokens'])) // Decks -> Set tokens
			{
				$deckname = $_POST['CurrentDeck'];
				$deck = $player->GetDeck($deckname);
				if (!$deck) { $error = 'No such deck.'; $current = 'Decks'; break; }

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
				$current = 'Deck_edit';

				break;
			}

			if (isset($_POST['auto_tokens'])) // Decks -> Assign tokens automatically
			{
				$deckname = $_POST['CurrentDeck'];
				$deck = $player->GetDeck($deckname);
				if (!$deck) { $error = 'No such deck.'; $current = 'Decks'; break; }

				$deck->SetAutoTokens();					
				$deck->SaveDeck();

				$information = 'Tokens set.';
				$current = 'Deck_edit';

				break;
			}

			if (isset($_POST['filter'])) // Decks -> Modify this deck -> Apply filters
			{
				$current = 'Deck_edit';

				break;
			}

			if (isset($_POST['reset_deck_prepare'])) // Decks -> Reset
			{
				// only symbolic functionality... rest is handled below
				$current = 'Deck_edit';

				break;
			}

			if (isset($_POST['reset_deck_confirm'])) // Decks -> Modify this deck -> Confirm reset
			{
				$deckname = $_POST['CurrentDeck'];
				$deck = $player->GetDeck($deckname);
				if (!$deck) { $error = 'No such deck.'; $current = 'Decks'; break; }

				// reset deck, saving it on success
				if( $deck->ResetDeck() )
					$deck->SaveDeck();
				else
					$error = 'Failed to reset this deck.';

				$current = 'Deck_edit';
				break;
			}

			if (isset($_POST['rename_deck'])) // Decks -> Modify this deck -> Rename
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

			if (isset($_POST['export_deck'])) // Decks -> Modify this deck -> Export
			{
				$curname = $_POST['CurrentDeck'];
				$deck = $player->GetDeck($curname);
				if (!$deck) { $error = 'No such deck.'; $current = 'Decks'; break; }
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

			if (isset($_POST['import_deck'])) // Decks -> Modify this deck -> Import
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

			// begin forum related messages

			// begin section related messages

			if (isset($_POST['new_thread'])) // forum -> section -> new thread
			{
				// check access rights
				if (!$access_rights[$player->Type()]["create_thread"]) { $error = 'Access denied.'; $current = 'Section_details'; break; }

				$current = 'New_thread';

				break;
			}

			if (isset($_POST['create_thread'])) // forum -> section -> new thread -> create new thread
			{
				$section_id = $_POST['CurrentSection'];

				// check access rights
				if (!$access_rights[$player->Type()]["create_thread"]) { $error = 'Access denied.'; $current = 'Section_details'; break; }
				// check access rights
				if ((!$access_rights[$player->Type()]["chng_priority"]) AND ($_POST['Priority'] != "normal")) { $error = 'Access denied.'; $current = 'Section_details'; break; }

				if ((trim($_POST['Title']) == "") OR (trim($_POST['Content']) == "")) { $error = "Invalid input"; $current = "New_thread"; break; }

				if (strlen($_POST['Content']) > POST_LENGTH) { $error = "Thread text is too long"; $current = "New_thread"; break; }

				$thread_id = $forum->Threads->ThreadExists($_POST['Title']);
				if ($thread_id) { $error = "Thread already exists"; $current = "Thread_details"; $_POST['CurrentThread'] = $thread_id; break; }

				$new_thread = $forum->Threads->CreateThread($_POST['Title'], $player->Name(), $_POST['Priority'], $section_id);
				if ($new_thread === FALSE) { $error = "Failed to create new thread"; $current = "Section_details"; break; }
				// $new_thread contains ID of currently created thread, which can be 0

				$new_post = $forum->Threads->Posts->CreatePost($new_thread, $player->Name(), $_POST['Content']);
				if (!$new_post) { $error = "Failed to create new post"; $current = "Section_details"; break; }

				$forum->Threads->RefreshThread($new_thread); // update post count, last author and last post

				$information = "Thread created";
				$current = 'Section_details';

				break;
			}

			if (isset($_POST['forum_search'])) // forum -> Search
			{
				$current = 'Forum_search';

				break;
			}

			// end section related messages

			// begin thread related messages

			if (isset($_POST['thread_lock'])) // forum -> section -> thread -> lock thread
			{
				$thread_id = $_POST['CurrentThread'];

				// check access rights
				if (!$access_rights[$player->Type()]["lock_thread"]) { $error = 'Access denied.'; $current = 'Thread_details'; break; }

				$lock = $forum->Threads->LockThread($thread_id);
				if (!$lock) { $error = "Failed to lock thread"; $current = "Thread_details"; break; }

				$information = "Thread locked";
				$current = 'Thread_details';

				break;
			}

			if (isset($_POST['thread_unlock'])) // forum -> section -> thread -> unlock thread
			{
				$thread_id = $_POST['CurrentThread'];

				// check access rights
				if (!$access_rights[$player->Type()]["lock_thread"]) { $error = 'Access denied.'; $current = 'Thread_details'; break; }

				$lock = $forum->Threads->UnlockThread($thread_id);
				if (!$lock) { $error = "Failed to unlock thread"; $current = "Thread_details"; break; }

				$information = "Thread unlocked";
				$current = 'Thread_details';

				break;
			}

			if (isset($_POST['thread_delete'])) // forum -> section -> thread -> delete thread
			{
				// only symbolic functionality... rest is handled below

				// check access rights
				if (!$access_rights[$player->Type()]["del_all_thread"]) { $error = 'Access denied.'; $current = 'Thread_details'; break; }

				$current = 'Thread_details';
				break;
			}

			if (isset($_POST['thread_delete_confirm'])) // forum -> section -> thread -> confirm delete thread
			{
				$thread_id = $_POST['CurrentThread'];

				// check access rights
				if (!$access_rights[$player->Type()]["del_all_thread"]) { $error = 'Access denied.'; $current = 'Thread_details'; break; }

				$delete = $forum->Threads->DeleteThread($thread_id);
				if (!$delete) { $error = "Failed to delete thread"; $current = "Thread_details"; break; }

				// check for linked card concepts, update when necessary
				$concept_id = $conceptdb->FindConcept($thread_id);

				if ($concept_id > 0)
				{
					$delete = $conceptdb->RemoveThread($concept_id);
					if (!$delete) { $error = "Failed to unlink matching concept"; $current = "Thread_details"; break; }
				}

				$information = "Thread deleted";
				$current = 'Section_details';

				break;
			}

			if (isset($_POST['new_post'])) // forum -> section -> thread -> new post
			{
				$thread_id = $_POST['CurrentThread'];

				// check if thread is locked
				if ($forum->Threads->IsLocked($thread_id)) { $error = 'Thread is locked.'; $current = 'Thread_details'; break; }

				// check access rights
				if (!$access_rights[$player->Type()]["create_post"]) { $error = 'Access denied.'; $current = 'Thread_details'; break; }

				$current = 'New_post';

				break;
			}

			if (isset($_POST['create_post'])) // forum -> section -> thread -> create new post
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

				$forum->Threads->RefreshThread($thread_id); // update post count, last author and last post

				$_POST['CurrentPage'] = max(($forum->Threads->Posts->CountPages($thread_id)) - 1, 0);
				$information = "Post created";
				$current = 'Thread_details';

				break;
			}

			if (isset($_POST['quote_post'])) // forum -> section -> thread -> quote post
			{
				$thread_id = $_POST['CurrentThread'];

				// check if thread is locked and if you have access to unlock it
				if (($forum->Threads->IsLocked($thread_id)) AND (!$access_rights[$player->Type()]["lock_thread"])) { $error = 'Thread is locked.'; $current = 'Thread_details'; break; }

				// check access rights
				if (!$access_rights[$player->Type()]["create_post"]) { $error = 'Access denied.'; $current = 'Thread_details'; break; }

				$current = 'New_post';

				break;
			}

			if (isset($_POST['edit_thread']))  // forum -> section -> thread -> edit thread
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

			if (isset($_POST['modify_thread'])) // forum -> section -> thread -> modify thread
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

			if (isset($_POST['move_thread'])) // forum -> section -> thread -> edit thread -> move thread to a new section
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

			// end thread related messages

			// begin post related messages

			if (isset($_POST['edit_post'])) // forum -> section -> thread -> edit post
			{
				$thread_id = $_POST['CurrentThread'];
				$_POST['CurrentPost'] = $post_id = $_POST['edit_post'];

				// check if thread is locked and if you have access to unlock it
				if (($forum->Threads->IsLocked($thread_id)) AND (!$access_rights[$player->Type()]["lock_thread"])) { $error = 'Thread is locked.'; $current = 'Thread_details'; break; }

				$post_data = $forum->Threads->Posts->GetPost($post_id);

				if (!(($access_rights[$player->Type()]["edit_all_post"]) OR ($access_rights[$player->Type()]["edit_own_post"] AND $post_data['Author'] == $player->Name()))) { $error = 'Access denied.'; $current = 'Thread_details'; break; }

				$current = 'Edit_post';

				break;
			}

			if (isset($_POST['modify_post'])) // forum -> section -> thread -> save edited post
			{
				$thread_id = $_POST['CurrentThread'];
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

			if (isset($_POST['delete_post'])) // forum -> section -> thread -> delete post
			{
				// only symbolic functionality... rest is handled below
				$thread_id = $_POST['CurrentThread'];

				// check if thread is locked and if you have access to unlock it
				if (($forum->Threads->IsLocked($thread_id)) AND (!$access_rights[$player->Type()]["lock_thread"])) { $error = 'Thread is locked.'; $current = 'Thread_details'; break; }

				// check access rights
				if (!$access_rights[$player->Type()]["del_all_post"]) { $error = 'Access denied.'; $current = 'Thread_details'; break; }

				$information = "Please confirm post deletion";
				$current = 'Thread_details';
				break;
			}

			if (isset($_POST['delete_post_confirm'])) // forum -> section -> thread -> delete post confirm
			{
				$thread_id = $_POST['CurrentThread'];
				$post_id = $_POST['delete_post_confirm'];

				// check if thread is locked and if you have access to unlock it
				if (($forum->Threads->IsLocked($thread_id)) AND (!$access_rights[$player->Type()]["lock_thread"])) { $error = 'Thread is locked.'; $current = 'Thread_details'; break; }

				// check access rights
				if (!$access_rights[$player->Type()]["del_all_post"]) { $error = 'Access denied.'; $current = 'Thread_details'; break; }

				$deleted_post = $forum->Threads->Posts->DeletePost($post_id);
				if (!$deleted_post) { $error = "Failed to delete post"; $current = "Thread_details"; break; }

				$forum->Threads->RefreshThread($thread_id); // update post count, last author and last post

				$max_page = max($forum->Threads->Posts->CountPages($thread_id) - 1, 0);
				$_POST['CurrentPage'] = (($_POST['CurrentPage'] <= $max_page) ? $_POST['CurrentPage'] : $max_page);

				$information = "Post deleted";
				$current = 'Thread_details';

				break;
			}

			if (isset($_POST['move_post'])) // forum -> section -> thread -> post -> edit post -> move post to a new thread
			{
				$thread_id = $_POST['CurrentThread'];
				$post_id = $_POST['CurrentPost'];
				$new_thread = $_POST['thread_select'];

				// check access rights
				if (!$access_rights[$player->Type()]["move_post"]) { $error = 'Access denied.'; $current = 'Thread_details'; break; }

				$move = $forum->Threads->Posts->MovePost($post_id, $new_thread);
				if (!$move) { $error = "Failed to change threads"; $current = "Thread_details"; break; }

				 // update post count, last author and last post of both former and target threads
				$forum->Threads->RefreshThread($thread_id);
				$forum->Threads->RefreshThread($new_thread);

				$_POST['CurrentPage'] = 0; // go to first page of target thread on success
				$information = "Thread changed";
				$current = 'Edit_post';

				break;
			}

			// end thread related messages

			// end forum related messages

			// game-related messages

			if (isset($_POST['jump_to_game'])) // Games -> vs. %s
			{
				$gameid = $_POST['games_list'];	
				$game = $gamedb->GetGame($gameid);

				// check if the game exists
				if (!$game) { $error = 'No such game!'; $current = 'Games'; break; }

				// check if this user is allowed to view this game
				if ($player->Name() != $game->Name1() and $player->Name() != $game->Name2()) { $current = 'Games'; break; }

				// check if the game is a game in progress (and not a challenge)
				if ($game->State == 'waiting') { $error = 'Opponent did not accept the challenge yet!'; $current = 'Games'; break; }

				// disable re-visiting
				if ( (($player->Name() == $game->Name1()) && ($game->State == 'P1 over')) || (($player->Name() == $game->Name2()) && ($game->State == 'P2 over')) ) { $error = 'Game already over.'; $current = 'Games'; break; }

				$_POST['CurrentGame'] = $gameid;
				$current = "Game";
				break;
			}
			
			if (isset($_POST['active_game'])) // Games -> next game button
			{
				$list = $gamedb->NextGameList($player->Name());

				//check if there is an active game
				if (count($list) == 0) { $error = 'No games your turn!'; $current = 'Games'; break; }

				$active = $inactive = array();

				foreach ($list as $game_id => $opponent_name)
				{
					// separate games into two groups based on opponent activity
					$inactivity = time() - strtotime($playerdb->LastQuery($opponent_name));
					if ($inactivity < 60*10) $active[] = $game_id;
					else $inactive[] = $game_id;
				}

				$list = array_merge($active, $inactive);

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
				if (!$game) { $error = 'No such game!'; $current = 'Games'; break; }

				// check if this user is allowed to view this game
				if ($player->Name() != $game->Name1() and $player->Name() != $game->Name2()) { $current = 'Games'; break; }

				// check if the game is a game in progress (and not a challenge)
				if ($game->State == 'waiting') { $error = 'Opponent did not accept the challenge yet!'; $current = 'Games'; break; }

				// disable re-visiting
				if ( (($player->Name() == $game->Name1()) && ($game->State == 'P1 over')) || (($player->Name() == $game->Name2()) && ($game->State == 'P2 over')) ) { $error = 'Game already over.'; $current = 'Games'; break; }

				$_POST['CurrentGame'] = $game->ID();
				$current = "Game";
				break;
			}

			if (isset($_POST['save_note']))	// save current's player game note
			{
				$gameid = $_POST['CurrentGame'];
				$game = $gamedb->GetGame($gameid);

				// check if the game exists
				if (!$game) { $error = 'No such game!'; $current = 'Games'; break; }

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

			if (isset($_POST['save_note_return'])) // save current's player game note and return to game screen
			{
				$gameid = $_POST['CurrentGame'];
				$game = $gamedb->GetGame($gameid);

				// check if the game exists
				if (!$game) { $error = 'No such game!'; $current = 'Games'; break; }

				// check if this user is allowed to view this game
				if ($player->Name() != $game->Name1() and $player->Name() != $game->Name2()) { $current = 'Games'; break; }

				// disable re-visiting
				if ( (($player->Name() == $game->Name1()) && ($game->State == 'P1 over')) || (($player->Name() == $game->Name2()) && ($game->State == 'P2 over')) ) { $error = 'Game already over.'; $current = 'Games'; break; }

				$new_note = $_POST['Content'];

				if (strlen($new_note) > MESSAGE_LENGTH) { $error = "Game note is too long"; $current = "Game_note"; break; }

				$game->SetNote($player->Name(), $new_note);
				$game->SaveGame();

				$information = 'Game note saved.';
				$current = 'Game';
				break;
			}

			if (isset($_POST['clear_note'])) // clear current's player game note
			{
				$gameid = $_POST['CurrentGame'];
				$game = $gamedb->GetGame($gameid);

				// check if the game exists
				if (!$game) { $error = 'No such game!'; $current = 'Games'; break; }

				// check if this user is allowed to perform game actions
				if ($player->Name() != $game->Name1() and $player->Name() != $game->Name2()) { $current = 'Game'; break; }

				$game->ClearNote($player->Name());
				$game->SaveGame();

				$information = 'Game note cleared.';
				$current = 'Game_note';
				break;
			}

			if (isset($_POST['clear_note_return']))	// clear current's player game note and return to game screen
			{
				$gameid = $_POST['CurrentGame'];
				$game = $gamedb->GetGame($gameid);

				// check if the game exists
				if (!$game) { $error = 'No such game!'; $current = 'Games'; break; }

				// check if this user is allowed to perform game actions
				if ($player->Name() != $game->Name1() and $player->Name() != $game->Name2()) { $current = 'Game'; break; }

				// disable re-visiting
				if ( (($player->Name() == $game->Name1()) && ($game->State == 'P1 over')) || (($player->Name() == $game->Name2()) && ($game->State == 'P2 over')) ) { $error = 'Game already over.'; $current = 'Games'; break; }

				$game->ClearNote($player->Name());
				$game->SaveGame();

				$information = 'Game note cleared.';
				$current = 'Game';
				break;
			}

			if (isset($_POST['send_message'])) // message contains no data itself
			{
				$msg = $_POST['ChatMessage'];

				$gameid = $_POST['CurrentGame'];
				$game = $gamedb->GetGame($gameid);

				// check if the game exists
				if (!$game) { $error = 'No such game!'; $current = 'Games'; break; }

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

			if (isset($_POST['discard_card'])) // Games -> vs. %s -> Discard
			{
				$cardpos = $_POST['discard_card'];

				$gameid = $_POST['CurrentGame'];
				$game = $gamedb->GetGame($gameid);

				// check if the game exists
				if (!$game) { $error = 'No such game!'; $current = 'Games'; break; }

				// check if this user is allowed to perform game actions
				if (($player->Name() != $game->Name1() and $player->Name() != $game->Name2()) or $game->Surrender != '') { $current = 'Game'; break; }

				// check card position
				if (!is_numeric($cardpos)) { $error = 'Invalid card position.'; $current = 'Game'; break; }

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
				else $error = $result;

				$current = "Game";
				break;
			}

			if (isset($_POST['play_card'])) // Games -> vs. %s -> Play
			{
				$cardpos = $_POST['play_card'];
				$mode = (isset($_POST['card_mode']) and isset($_POST['card_mode'][$cardpos])) ? $_POST['card_mode'][$cardpos] : 0;

				$gameid = $_POST['CurrentGame'];
				$game = $gamedb->GetGame($gameid);

				// check if the game exists
				if (!$game) { $error = 'No such game!'; $current = 'Games'; break; }

				// check if this user is allowed to perform game actions
				if (($player->Name() != $game->Name1() and $player->Name() != $game->Name2()) or $game->Surrender != '') { $current = 'Game'; break; }

				// check card position
				if (!is_numeric($cardpos)) { $error = 'Invalid card position.'; $current = 'Game'; break; }

				// check card mode
				if (!is_numeric($mode)) { $error = 'Invalid mode.'; $current = 'Game'; break; }

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
						$p1_rep = $p1->GetSettings()->GetSetting('Reports');
						$p2_rep = $p2->GetSettings()->GetSetting('Reports');

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
				else $error = $result;

				$current = "Game";
				break;
			}

			if (isset($_POST['surrender'])) // Games -> vs. %s -> Surrender -> send surrender request to opponent
			{
				$gameid = $_POST['CurrentGame'];
				$game = $gamedb->GetGame($gameid);

				// check if the game exists
				if (!$game) { $error = 'No such game!'; $current = 'Games'; break; }

				// check if this user is allowed to surrender in this game
				if ($player->Name() != $game->Name1() and $player->Name() != $game->Name2()) { $current = 'Game'; break; }

				$result = $game->RequestSurrender($player->Name());

				if ($result == 'OK') $information = 'Surrender request sent.';

				$current = "Game";
				break;
			}

			if (isset($_POST['cancel_surrender'])) // Games -> vs. %s -> Surrender -> cancel surrender request to opponent
			{
				$gameid = $_POST['CurrentGame'];
				$game = $gamedb->GetGame($gameid);

				// check if the game exists
				if (!$game) { $error = 'No such game!'; $current = 'Games'; break; }

				// check if this user is allowed to cancel surrender in this game
				if ($player->Name() != $game->Surrender) { $current = 'Game'; break; }

				$result = $game->CancelSurrender();

				if ($result == 'OK') $information = 'Surrender request cancelled.';

				$current = "Game";
				break;
			}

			if (isset($_POST['reject_surrender'])) // Games -> vs. %s -> Surrender -> reject surrender request from opponent
			{
				$gameid = $_POST['CurrentGame'];
				$game = $gamedb->GetGame($gameid);

				// check if the game exists
				if (!$game) { $error = 'No such game!'; $current = 'Games'; break; }

				// check if this user is allowed to reject surrender in this game
				if (($player->Name() != $game->Name1() and $player->Name() != $game->Name2()) or ($player->Name() == $game->Surrender)) { $current = 'Game'; break; }

				$result = $game->CancelSurrender();

				if ($result == 'OK') $information = 'Surrender request rejected.';

				$current = "Game";
				break;
			}

			if (isset($_POST['accept_surrender'])) // Games -> vs. %s -> Surrender -> accept surrender from opponent
			{
				$gameid = $_POST['CurrentGame'];
				$game = $gamedb->GetGame($gameid);

				// check if the game exists
				if (!$game) { $error = 'No such game!'; $current = 'Games'; break; }

				// check if this user is allowed to accept surrender in this game
				if (($player->Name() != $game->Name1() and $player->Name() != $game->Name2()) or ($player->Name() == $game->Surrender)) { $current = 'Game'; break; }

				$result = $game->SurrenderGame();

				if ($result == 'OK')
				{
					$information = 'Surrender request accepted.';
					$replaydb->FinishReplay($game);
				}

				if (($result == 'OK') AND ($game->GetGameMode('FriendlyPlay') == "no"))
				{
					$loser = $game->Surrender;
					$exp1 = $game->CalculateExp($game->Winner);
					$exp2 = $game->CalculateExp($loser);
					$opponent = $playerdb->GetPlayer($loser);
					$opponent_rep = $opponent->GetSettings()->GetSetting('Reports');
					$player_rep = $player->GetSettings()->GetSetting('Reports');

					// update score
					$score1 = $scoredb->GetScore($game->Winner);
					$score1->ScoreData->Wins++;
					$levelup1 = $score1->AddExp($exp1['exp']);
					$score1->SaveScore();

					$score2 = $scoredb->GetScore($loser);
					$score2->ScoreData->Losses++;
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
				else $error = $result;

				$current = "Game";
				break;
			}

			if (isset($_POST['abort_game'])) // Games -> vs. %s -> Abort game
			{
				// an option to end the game without hurting your score
				// applies only to games against 'dead' players (abandoned games)
				$gameid = $_POST['CurrentGame'];
				$game = $gamedb->GetGame($gameid);

				// check if the game exists
				if (!$game) { $error = 'No such game!'; $current = 'Games'; break; }

				// check if this user is allowed to abort this game
				if ($player->Name() != $game->Name1() and $player->Name() != $game->Name2()) { $current = 'Game'; break; }

				// only allow aborting abandoned games
				if (!$playerdb->isDead($game->Name1()) and !$playerdb->isDead($game->Name2())) { $error = 'Action not allowed!'; $current = 'Game'; break; }

				$result = $game->AbortGame($player->Name());

				if ($result == 'OK')
					$replaydb->FinishReplay($game);
				else $error = $result;

				$current = "Game";
				break;
			}

			if (isset($_POST['finish_game'])) // Games -> vs. %s -> Finish game
			{
				// an option to end the game when opponent refuses to play
				// applies only to games against non-'dead' players, when opponet didn't take action for more then 3 weeks
				$gameid = $_POST['CurrentGame'];
				$game = $gamedb->GetGame($gameid);

				// check if the game exists
				if (!$game) { $error = 'No such game!'; $current = 'Games'; break; }

				// check if this user is allowed to abort this game
				if ($player->Name() != $game->Name1() and $player->Name() != $game->Name2()) { $current = 'Game'; break; }

				// only allow finishing active games
				if ($playerdb->isDead($game->Name1()) or $playerdb->isDead($game->Name2())) { $error = 'Action not allowed!'; $current = 'Game'; break; }

				// and only if the abort criteria are met
				if( time() - strtotime($game->LastAction) < 60*60*24*7*3 || $game->Current == $player->Name() ) { $error = 'Action not allowed!'; $current = 'Game'; break; }

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
					$p1_rep = $p1->GetSettings()->GetSetting('Reports');
					$p2_rep = $p2->GetSettings()->GetSetting('Reports');

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
				else $error = $result;

				$current = "Game";
				break;
			}

			if (isset($_POST['Confirm'])) // Games -> vs. %s -> Leave the game
			{
				$gameid = $_POST['CurrentGame'];
				$game = $gamedb->GetGame($gameid);

				// check if the game exists
				if (!$game) { $error = 'No such game!'; $current = 'Games'; break; }

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

			if (isset($_POST['host_game'])) // Games -> Host game
			{
				$_POST['subsection'] = 'hosted_games';

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
				$hidden_cards = (isset($_POST['HiddenMode']) ? 'yes' : 'no');
				$friendly_play = (isset($_POST['FriendlyMode']) ? 'yes' : 'no');
				$game_modes = array();
				if ($hidden_cards == "yes") $game_modes[] = 'HiddenCards';
				if ($friendly_play == "yes") $game_modes[] = 'FriendlyPlay';
				$game->SetGameModes(implode(',', $game_modes));

				$information = 'Game created. Waiting for opponent to join.';
				$current = 'Games';
				break;
			}

			if (isset($_POST['unhost_game'])) // Games -> Unhost game
			{
				$game_id = $_POST['unhost_game'];
				$game = $gamedb->GetGame($game_id);
				$_POST['subsection'] = 'hosted_games';

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

			if (isset($_POST['join_game'])) // Games -> Join game
			{
				$_POST['subsection'] = 'free_games';

				// check access rights
				if (!$access_rights[$player->Type()]["accept_challenges"]) { $error = 'Access denied.'; $current = 'Games'; break; }

				$game_id = $_POST['join_game'];
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

			if (isset($_POST['filter_hosted_games'])) // use filter in hosted games view
			{
				$_POST['subsection'] = 'free_games';
				$current = 'Games';
				break;
			}

			// end game-related messages

			// begin misc messages

			if (isset($_POST['Refresh'])) // refresh button :)
			{
				$current = $_POST['Refresh'];
				break;
			}

			if (isset($_POST['reset_notification'])) // reset notification
			{
				if ($player->ResetNotification()) $information = 'Notification successfully reset';
				else $error = 'Failed to reset notification';

				$current = $_POST['reset_notification'];
				break;
			}

			// end misc messages

			// begin challenge related messages

			if (isset($_POST['accept_challenge'])) // Challenges -> Accept
			{
				// check access rights
				if (!$access_rights[$player->Type()]["accept_challenges"]) { $error = 'Access denied.'; $current = 'Messages'; break; }

				$game_id = $_POST['accept_challenge'];
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

			if (isset($_POST['reject_challenge'])) // Challenges -> Reject
			{
				$game_id = $_POST['reject_challenge'];
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

			if (isset($_POST['prepare_challenge'])) // Players -> Challenge this user
			{
				// check access rights
				if (!$access_rights[$player->Type()]["send_challenges"]) { $error = 'Access denied.'; $current = 'Players'; break; }

				$_POST['cur_player'] = postdecode($_POST['prepare_challenge']);

				// this is only used to assist the function below
				$current = 'Profile';
				break;
			}

			if (isset($_POST['send_challenge'])) // Players -> Send challenge
			{
				// check access rights
				if (!$access_rights[$player->Type()]["send_challenges"]) { $error = 'Access denied.'; $current = 'Players'; break; }

				$_POST['cur_player'] = $opponent = postdecode($_POST['send_challenge']);
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

			if (isset($_POST['withdraw_challenge'])) // Players -> Cancel
			{
				$game_id = $_POST['withdraw_challenge'];
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

			if (isset($_POST['withdraw_challenge2'])) // Challenges -> Cancel
			{
				$game_id = $_POST['withdraw_challenge2'];
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

			// end challenge related messages

			// begin message related messages

			if (isset($_POST['message_details'])) // view message
			{
				$messageid = $_POST['message_details'];
				$message = $messagedb->GetMessage($messageid, $player->Name());

				if (!$message) { $error = "No such message!"; $current = "Messages"; break; }

				$_POST['CurrentMessage'] = $messageid;
				$current = 'Message_details';
				break;
			}

			if (isset($_POST['message_retrieve'])) // retrieve message (even deleted one)
			{
				$messageid = $_POST['message_retrieve'];

				// check access rights
				if (!$access_rights[$player->Type()]["see_all_messages"]) { $error = 'Access denied.'; $current = 'Messages'; break; }

				$message = $messagedb->RetrieveMessage($messageid);
				if (!$message) { $error = "No such message!"; $current = "Messages"; break; }

				$_POST['CurrentMessage'] = $messageid;
				$current = 'Message_details';
				break;
			}

			if (isset($_POST['message_delete'])) // delete message
			{
				$messageid = $_POST['message_delete'];
				$message = $messagedb->GetMessage($messageid, $player->Name());

				if (!$message) { $error = "No such message!"; $current = "Messages"; break; }

				$_POST['CurrentMessage'] = $messageid;
				$current = 'Message_details';
				break;
			}

			if (isset($_POST['message_delete_confirm'])) // delete message confirmation
			{
				$messageid = $_POST['message_delete_confirm'];
				$message = $messagedb->DeleteMessage($messageid, $player->Name());

				if (!$message) { $error = "No such message!"; $current = "Messages"; break; }

				$information = "Message deleted";
				$current = 'Messages';
				break;
			}

			if (isset($_POST['message_cancel'])) // cancel new message creation
			{
				$current = 'Messages';
				break;
			}

			if (isset($_POST['message_send'])) // send new message
			{
				$recipient = $_POST['Recipient'];
				$author = $_POST['Author'];

				// check access rights
				if (!$access_rights[$player->Type()]["messages"]) { $error = 'Access denied.'; $current = 'Messages'; break; }
				if ((trim($_POST['Subject']) == "") AND (trim($_POST['Content']) == "")) { $error = "No message input specified"; $current = "Message_new"; break; }
				if (strlen($_POST['Content']) > MESSAGE_LENGTH) { $error = "Message too long"; $current = "Message_new"; break; }
				if (!$playerdb->GetPlayer($_POST['Recipient'])) { $error = "Recipient doesn't exist"; $current = "Message_new"; break; }

				$message = $messagedb->SendMessage($_POST['Author'], $_POST['Recipient'], $_POST['Subject'], $_POST['Content']);

				if (!$message) { $error = "Failed to send message"; $current = "Messages"; break; }

				$_POST['CurrentLocation'] = "sent_mail";
				$information = "Message sent";
				$current = 'Messages';
				break;
			}

			if (isset($_POST['message_create'])) // go to new message screen
			{
				// check access rights
				if (!$access_rights[$player->Type()]["messages"]) { $error = 'Access denied.'; $current = 'Messages'; break; }

				$_POST['Recipient'] = postdecode($_POST['message_create']);
				$_POST['Author'] = $player->Name();

				$current = 'Message_new';
				break;
			}

			if (isset($_POST['system_notification'])) // go to new message screen to write system notification
			{
				// check access rights
				if (!$access_rights[$player->Type()]["system_notification"]) { $error = 'Access denied.'; $current = 'Players'; break; }

				$_POST['Recipient'] = postdecode($_POST['system_notification']);
				$_POST['Author'] = SYSTEM_NAME;

				$current = 'Message_new';
				break;
			}

			$temp = array("asc" => "ASC", "desc" => "DESC");
			foreach($temp as $type => $order_val)
			{
			if (isset($_POST['mes_ord_'.$type])) // select ascending or descending order in message list
				{
					$_POST['CurrentCond'] = $_POST['mes_ord_'.$type];
					$_POST['CurrentOrd'] = $order_val;

					$current = "Messages";

					break;
				}
			}

			if (isset($_POST['message_filter'])) // use filter
			{
				$_POST['CurrentMesPage'] = 0;
				$current = 'Messages';
				break;
			}

			if (isset($_POST['select_page_messages'])) // Messages -> select page (previous and next button)
			{
				$_POST['CurrentMesPage'] = $_POST['select_page_messages'];
				$current = "Messages";

				break;
			}

			if (isset($_POST['seek_page_messages'])) // Messages -> select page (Jump to page)
			{
				$_POST['CurrentMesPage'] = $_POST['page_selector'];
				$current = "Messages";

				break;
			}

			if (isset($_POST['Delete_mass'])) // Messages -> delete selected messages
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
			
			// begin profile related messages

			if (isset($_POST['change_access'])) // Players -> User details -> Change access rights
			{
				$_POST['Profile'] = $opponent = postdecode($_POST['change_access']);

				// check access rights
				if (!$access_rights[$player->Type()]["change_rights"]) { $error = 'Access denied.'; $current = 'Profile'; break; }

				$target = $playerdb->GetPlayer($opponent);
				$target->ChangeAccessRights($_POST['new_access']);

				$information = 'Access rights changed.';
				$current = 'Profile';
				break;
			}

			if (isset($_POST['reset_exp'])) // Players -> User details -> Reset exp
			{
				$_POST['Profile'] = $opponent = postdecode($_POST['reset_exp']);

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

			if (isset($_POST['reset_avatar_remote'])) // reset some player's avatar
			{
				$_POST['cur_player'] = postdecode($_POST['reset_avatar_remote']);

				$opponent = $playerdb->GetPlayer($_POST['cur_player']);
				if (!$opponent) { $error = 'Player '.htmlencode($opponent).' does not exist!'; $current = 'Players'; break; }

				// check access rights
				if (!$access_rights[$player->Type()]["change_all_avatar"]) { $error = 'Access denied.'; $current = 'Profile'; break; }

				$settings = $opponent->GetSettings();
				$former_name = $settings->GetSetting('Avatar');
				$former_path = 'img/avatars/'.$former_name;

				if ((file_exists($former_path)) and ($former_name != "noavatar.jpg")) unlink($former_path);
				$settings->ChangeSetting('Avatar', "noavatar.jpg");
				$settings->SaveSettings();

				$information = "Avatar cleared";
				$current = 'Profile';

				break;
			}

			// end profile related messages

			// begin players related messages

			$temp = array("asc" => "ASC", "desc" => "DESC");
			foreach($temp as $type => $order_val)
			{
				if (isset($_POST['players_ord_'.$type])) // select ascending or descending order in players list
				{
					$_POST['CurrentCondition'] = $_POST['players_ord_'.$type];
					$_POST['CurrentOrder'] = $order_val;

					$current = "Players";
					break;
				}
			}

			if (isset($_POST['filter_players'])) // use player filter in players list
			{
				$_POST['CurrentPlayersPage'] = 0;
				$current = "Players";

				break;
			}

			if (isset($_POST['select_page_players'])) // Players -> select page (previous and next button)
			{
				$_POST['CurrentPlayersPage'] = $_POST['select_page_players'];
				$current = "Players";

				break;
			}

			if (isset($_POST['seek_page_players'])) // Players -> select page (Jump to page)
			{
				$_POST['CurrentPlayersPage'] = $_POST['page_selector'];
				$current = "Players";

				break;
			}

			// end players related messages

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
				// settings-related messages
				
				if ($message == 'user_settings') // upload user settings
				{
					if (strlen($_POST['Hobby']) > HOBBY_LENGTH) { $_POST['Hobby'] = substr($_POST['Hobby'], 0, HOBBY_LENGTH); $warning = "Hobby text is too long"; }

					$settings = $player->GetSettings();
					$bool_settings = $settings->ListBooleanSettings();
					$other_settings = $settings->ListOtherSettings();

					// process yes/no settings
					foreach($bool_settings as $setting) $settings->ChangeSetting($setting, ((isset($_POST[$setting])) ? 'yes' : 'no'));
					// process other settings
					foreach($other_settings as $setting)
						if (isset($_POST[$setting]) and $setting != 'Birthdate'and $setting != 'Avatar') $settings->ChangeSetting($setting, $_POST[$setting]);

					// birthdate is handled separately
					if( $_POST['Birthyear'] == "" ) $_POST['Birthyear'] = '0000';
					if( $_POST['Birthmonth'] == "" ) $_POST['Birthmonth'] = '00';
					if( $_POST['Birthday'] == "" ) $_POST['Birthday'] = '00';

					$result = CheckDateInput($_POST['Birthyear'], $_POST['Birthmonth'], $_POST['Birthday']);
					if( $result != "" )
						$error = $result;
					elseif( intval(date("Y")) <= $_POST['Birthyear'] )
						$error = "Invalid birthdate";
					else
						$settings->ChangeSetting('Birthdate', implode("-", array($_POST['Birthyear'], $_POST['Birthmonth'], $_POST['Birthday'])));

					$settings->SaveSettings();

					$information = "User settings saved";
					$current = 'Settings';

					break;
				}
				
				if ($message == 'Avatar') //upload avatar
				{
					// check access rights
					if (!$access_rights[$player->Type()]["change_own_avatar"]) { $error = 'Access denied.'; $current = 'Settings'; break; }
					
					$settings = $player->GetSettings();
					
					$former_name = $settings->GetSetting('Avatar');
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
						$settings->ChangeSetting('Avatar', $code_name);
						$settings->SaveSettings();
						$information = "Avatar uploaded";
					}
					
					$current = 'Settings';
					
					break;
				}
				
				if ($message == 'reset_avatar') // reset own avatar
				{
					// check access rights
					if (!$access_rights[$player->Type()]["change_own_avatar"]) { $error = 'Access denied.'; $current = 'Settings'; break; }
					
					$settings = $player->GetSettings();
					
					$former_name = $settings->GetSetting('Avatar');
					$former_path = 'img/avatars/'.$former_name;
					
					if ((file_exists($former_path)) and ($former_name != "noavatar.jpg")) unlink($former_path);
					$settings->ChangeSetting('Avatar', "noavatar.jpg");
					$settings->SaveSettings();
					$information = "Avatar cleared";
					
					$current = 'Settings';
					
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
				
				// begin replays related messages
				
				$temp = array("asc" => "ASC", "desc" => "DESC");
				foreach($temp as $type => $order_val)
				{
					if ($message == 'replays_ord_'.$type) // select ascending or descending order in game replays list
					{
						$_POST['ReplaysCond'] = array_shift(array_keys($value));
						$_POST['ReplaysOrder'] = $order_val;
						
						$current = "Replays";
						
						break;
					}
				}
				
				if ($message == 'filter_replays') // use filter in replays list
				{
					$_POST['CurrentRepPage'] = 0;
					$current = 'Replays';
					break;
				}
				
				if ($message == 'my_replays') // show only current player's replays
				{
					$_POST['PlayerFilter'] = $player->Name();
					$_POST['HiddenCards'] = "none";
					$_POST['FriendlyPlay'] = "none";
					$_POST['VictoryFilter'] = "none";
					$_POST['CurrentRepPage'] = 0;
					
					$current = 'Replays';
					break;
				}
				
				if ($message == 'select_page_replays') // Replays -> select page (previous and next button)
				{
					$_POST['CurrentRepPage'] = array_shift(array_keys($value));
					$current = "Replays";
					
					break;
				}
				
				if ($message == 'seek_page_replays') // Replays -> select page (page selector)
				{
					$_POST['CurrentRepPage'] = $_POST['page_selector'];
					$current = "Replays";
					
					break;
				}
				
				// end replays related messages
				
				// begin statistics related messages
				
				if ($message == 'card_statistics') // view card statistics
				{
					$current = 'Statistics';
					break;
				}
				
				if ($message == 'other_statistics') // view other statistics
				{
					$current = 'Statistics';
					break;
				}
				
				// end statistics related messages
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
		$params["loginbox"]["current"] = $current;
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

		// menubar notification (depends on current user's game settings)
		$settings = $player->GetSettings();
		$forum_not = ($settings->GetSetting('Forum_notification') == 'yes');
		$concepts_not = ($settings->GetSetting('Concepts_notification') == 'yes');
		$params["navbar"]['forum_notice'] = ($forum_not AND $forum->NewPosts($player->GetNotification())) ? 'yes' : 'no';
		$params["navbar"]['message_notice'] = (count($gamedb->ListChallengesTo($player->Name())) + $messagedb->CountUnreadMessages($player->Name()) > 0) ? 'yes' : 'no';
		$params["navbar"]['concept_notice'] = ($concepts_not AND $conceptdb->NewConcepts($player->GetNotification())) ? 'yes' : 'no';
		$params["navbar"]['game_notice'] = $gamedb->IsAnyCurrentGame($player->Name()) ? 'yes' : 'no';
		$params["main"]["skin"] = $settings->GetSetting('Skin');
		$params["main"]["autorefresh"] = ($current == "Games") ? $settings->GetSetting('Autorefresh') : 0; // apply only in games section
	}
	
// now display current inner-page contents
switch( $current )
{
case 'Webpage':
	// decide what screen is default (depends on whether the user is logged in)
	$default_page = ( !$session ) ? 'Main' : 'News';
	$params['website']['selected'] = $selected = isset($_POST['WebSection']) ? $_POST['WebSection'] : $default_page;

	$websections = array('Main', 'News', 'Archive', 'Modified', 'Faq', 'Credits', 'History');
	if (!in_array($selected, $websections)) { $display_error = 'Invalid web section.'; break; }

	// display all news when viewing news archive, display only recent news otherwise
	if ($selected == 'Archive') { $selected = 'News'; $params['website']['recent_news_only'] = 'no'; }
	else $params['website']['recent_news_only'] = 'yes';

	// list the names of the files to display
	// (all files whose name matches up to the first space character)
	$files = preg_grep('/^'.$selected.'( .*)?\.xml/i', scandir('pages',1));

	$params['website']['websections'] = $websections;
	$params['website']['files'] = $files;
	$params['website']['timezone'] = ( isset($player) ) ? $player->GetSettings()->GetSetting('Timezone') : '+0';
	break;


case 'Help':
	$params['help']['part'] = (isset($_POST['help_part'])) ? $_POST['help_part'] : 'Introduction';

	break;


case 'Registration':

	break;


case 'Deck_edit':
	$currentdeck = $params['deck_edit']['CurrentDeck'] = isset($_POST['CurrentDeck']) ? $_POST['CurrentDeck'] : '';
	$classfilter = $params['deck_edit']['ClassFilter'] = isset($_POST['ClassFilter']) ? $_POST['ClassFilter'] : 'Common';
	$costfilter = $params['deck_edit']['CostFilter'] = isset($_POST['CostFilter']) ? $_POST['CostFilter'] : 'none';
	$keywordfilter = $params['deck_edit']['KeywordFilter'] = isset($_POST['KeywordFilter']) ? $_POST['KeywordFilter'] : 'none';
	$advancedfilter = $params['deck_edit']['AdvancedFilter'] = isset($_POST['AdvancedFilter']) ? $_POST['AdvancedFilter'] : 'none';
	$supportfilter = $params['deck_edit']['SupportFilter'] = isset($_POST['SupportFilter']) ? $_POST['SupportFilter'] : 'none';
	$createdfilter = $params['deck_edit']['CreatedFilter'] = isset($_POST['CreatedFilter']) ? $_POST['CreatedFilter'] : 'none';
	$modifiedfilter = $params['deck_edit']['ModifiedFilter'] = isset($_POST['ModifiedFilter']) ? $_POST['ModifiedFilter'] : 'none';

	$params['deck_edit']['keywords'] = $carddb->Keywords();
	$params['deck_edit']['created_dates'] = $carddb->ListCreationDates();
	$params['deck_edit']['modified_dates'] = $carddb->ListModifyDates();

	// download the neccessary data
	$deck = $player->GetDeck($currentdeck);
	if (!$deck) { $display_error = "Invalid deck."; break; }

	$params['deck_edit']['reset'] = ( (isset($_POST["reset_deck_prepare"] )) ? 'yes' : 'no');

	// load card display settings
	$settings = $player->GetSettings();
	$params['deck_edit']['c_text'] = $settings->GetSetting('Cardtext');
	$params['deck_edit']['c_img'] = $settings->GetSetting('Images');
	$params['deck_edit']['c_keywords'] = $settings->GetSetting('Keywords');
	$params['deck_edit']['c_oldlook'] = $settings->GetSetting('OldCardLook');
	$params['deck_edit']['Res'] = $deck->AvgCostPerTurn(); // calculate average cost per turn
	$params['deck_edit']['Take'] = ( $deck->DeckData->Count($classfilter) < 15 ) ? 'yes' : 'no';

	$filter = array();
	if( $classfilter != 'none' ) $filter['class'] = $classfilter;
	if( $keywordfilter != 'none' ) $filter['keyword'] = $keywordfilter;
	if( $costfilter != 'none' ) $filter['cost'] = $costfilter;
	if( $advancedfilter != 'none' ) $filter['advanced'] = $advancedfilter;
	if( $supportfilter != 'none' ) $filter['support'] = $supportfilter;
	if( $createdfilter != 'none' ) $filter['created'] = $createdfilter;
	if( $modifiedfilter != 'none' ) $filter['modified'] = $modifiedfilter;
	$exluded = $deck->DeckData->$classfilter; // cards not present in the deck
	$card_list = $carddb->GetData($carddb->GetList($filter));
	foreach ($card_list as $i => $data) $card_list[$i]['excluded'] = (in_array($data['id'], $exluded)) ? 'yes' : 'no';

	$params['deck_edit']['CardList'] = $card_list;

	foreach (array('Common', 'Uncommon', 'Rare') as $class)
		$params['deck_edit']['DeckCards'][$class] = $carddb->GetData($deck->DeckData->$class);

	$params['deck_edit']['Tokens'] = $deck->DeckData->Tokens;
	$params['deck_edit']['TokenKeywords'] = $carddb->TokenKeywords();

	break;


case 'Decks':
	$params['decks']['list'] = $list = $player->ListDecks();
	$params['decks']['timezone'] = $player->GetSettings()->GetSetting('Timezone');

	break;

case 'Concepts':
	// filter initialization
	$params['concepts']['card_name'] = $name = (isset($_POST['card_name'])) ? trim($_POST['card_name']) : '';
	$params['concepts']['date_val'] = $date = (isset($_POST['date_filter_concepts'])) ? $_POST['date_filter_concepts'] : 'none';
	$params['concepts']['author_val'] = $author = (isset($_POST['author_filter'])) ? $_POST['author_filter'] : 'none';
	$params['concepts']['state_val'] = $state = (isset($_POST['state_filter'])) ? $_POST['state_filter'] : 'none';

	if (!isset($_POST['CurrentOrder'])) $_POST['CurrentOrder'] = "DESC"; // default ordering
	if (!isset($_POST['CurrentCon'])) $_POST['CurrentCon'] =  "LastChange"; // default order condition

	$params['concepts']['current_order'] = $order = $_POST['CurrentOrder'];
	$params['concepts']['current_condition'] = $condition = $_POST['CurrentCon'];

	$current_page = ((isset($_POST['CurrentConPage'])) ? $_POST['CurrentConPage'] : 0);
	if (!is_numeric($current_page) OR $current_page < 0) { $display_error = 'Invalid concepts page.'; break; }
	$params['concepts']['current_page'] = $current_page;

	$params['concepts']['list'] = $conceptdb->GetList($name, $author, $date, $state, $condition, $order, $current_page);
	$params['concepts']['page_count'] = $conceptdb->CountPages($name, $author, $date, $state);

	$settings = $player->GetSettings();
	$params['concepts']['notification'] = $player->GetNotification();
	$params['concepts']['authors'] = $authors = $conceptdb->ListAuthors($date);
	$params['concepts']['mycards'] = (in_array($player->Name(), $authors) ? 'yes' : 'no');
	$params['concepts']['timezone'] = $settings->GetSetting('Timezone');
	$params['concepts']['PlayerName'] = $player->Name();
	$params['concepts']['create_card'] = (($access_rights[$player->Type()]["create_card"]) ? 'yes' : 'no');
	$params['concepts']['edit_own_card'] = (($access_rights[$player->Type()]["edit_own_card"]) ? 'yes' : 'no');
	$params['concepts']['edit_all_card'] = (($access_rights[$player->Type()]["edit_all_card"]) ? 'yes' : 'no');
	$params['concepts']['delete_own_card'] = (($access_rights[$player->Type()]["delete_own_card"]) ? 'yes' : 'no');
	$params['concepts']['delete_all_card'] = (($access_rights[$player->Type()]["delete_all_card"]) ? 'yes' : 'no');
	$params['concepts']['c_text'] = $settings->GetSetting('Cardtext');
	$params['concepts']['c_img'] = $settings->GetSetting('Images');
	$params['concepts']['c_keywords'] = $settings->GetSetting('Keywords');
	$params['concepts']['c_oldlook'] = $settings->GetSetting('OldCardLook');

	break;


case 'Concepts_new':
	$params['concepts_new']['data'] = (isset($data)) ? $data : array();
	$params['concepts_new']['stored'] = (isset($data)) ? 'yes' : 'no';

	break;


case 'Concepts_edit':
	$concept_id = (isset($_POST['CurrentConcept'])) ? $_POST['CurrentConcept'] : 0;
	if (!is_numeric($concept_id) OR $concept_id <= 0) { $display_error = 'Invalid concept id.'; break; }

	$concept = $conceptdb->GetConcept($concept_id);
	if ($concept->ConceptData->Name == "Invalid Concept") { $display_error = 'Invalid concept.'; break; }

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
	$settings = $player->GetSettings();
	$params['concepts_edit']['c_text'] = $settings->GetSetting('Cardtext');
	$params['concepts_edit']['c_img'] = $settings->GetSetting('Images');
	$params['concepts_edit']['c_keywords'] = $settings->GetSetting('Keywords');
	$params['concepts_edit']['c_oldlook'] = $settings->GetSetting('OldCardLook');

	break;


case 'Concepts_details':
	$concept_id = (isset($_POST['CurrentConcept'])) ? $_POST['CurrentConcept'] : 0;
	if (!is_numeric($concept_id) OR $concept_id <= 0) { $display_error = 'Invalid concept id.'; break; }

	$concept = $conceptdb->GetConcept($concept_id);
	if ($concept->ConceptData->Name == "Invalid Concept") { $display_error = 'Invalid concept.'; break; }

	$inputs = array('Name', 'Class', 'Bricks', 'Gems', 'Recruits', 'Effect', 'Keywords', 'Picture', 'Note', 'State', 'Author', 'ThreadID');
	$data = array();
	foreach ($inputs as $input) $data[strtolower($input)] = $concept->ConceptData->$input;
	$data['id'] = $concept_id;
	$params['concepts_details']['data'] = $data;

	$params['concepts_details']['create_thread'] = ($access_rights[$player->Type()]["create_thread"]) ? 'yes' : 'no';
	$params['concepts_details']['edit_all_card'] = ($access_rights[$player->Type()]["edit_all_card"]) ? 'yes' : 'no';
	$params['concepts_details']['delete_own_card'] = ($access_rights[$player->Type()]["delete_own_card"]) ? 'yes' : 'no';
	$params['concepts_details']['delete_all_card'] = ($access_rights[$player->Type()]["delete_all_card"]) ? 'yes' : 'no';
	$settings = $player->GetSettings();
	$params['concepts_details']['c_text'] = $settings->GetSetting('Cardtext');
	$params['concepts_details']['c_img'] = $settings->GetSetting('Images');
	$params['concepts_details']['c_keywords'] = $settings->GetSetting('Keywords');
	$params['concepts_details']['c_oldlook'] = $settings->GetSetting('OldCardLook');

	break;


case 'Players':	

	// defaults for list ordering
	if (!isset($_POST['CurrentOrder'])) $_POST['CurrentOrder'] = "DESC";
	if (!isset($_POST['CurrentCondition'])) $_POST['CurrentCondition'] = "Level";

	$params['players']['order'] = $order = $_POST['CurrentOrder'];
	$params['players']['condition'] = $condition = $_POST['CurrentCondition'];

	$settings = $player->GetSettings();

	// filter initialization
	$params['players']['activity_filter'] = $activity_filter = ((isset($_POST['activity_filter'])) ? $_POST['activity_filter'] : $settings->GetSetting('DefaultFilter'));
	$params['players']['status_filter'] = $status_filter = (isset($_POST['status_filter'])) ? $_POST['status_filter'] : 'none';
	$params['players']['pname_filter'] = $pname_filter = (isset($_POST['pname_filter'])) ? trim($_POST['pname_filter']) : '';

	$params['players']['PlayerName'] = $player->Name();

	// check for active decks
	$params['players']['active_decks'] = count($player->ListReadyDecks());

	//retrieve layout setting
	$params['players']['show_nationality'] = $settings->GetSetting('Nationality');
	$params['players']['show_avatars'] = $settings->GetSetting('Avatarlist');

	$opponents = $gamedb->ListOpponents($player->Name());
	$challengesfrom = $gamedb->ListChallengesFrom($player->Name());
	$endedgames = $gamedb->ListEndedGames($player->Name());

	$params['players']['free_slots'] = $gamedb->CountFreeSlots1($player->Name());

	$params['players']['messages'] = ($access_rights[$player->Type()]["messages"]) ? 'yes' : 'no';
	$params['players']['send_challenges'] = ($access_rights[$player->Type()]["send_challenges"]) ? 'yes' : 'no';

	$current_page = ((isset($_POST['CurrentPlayersPage'])) ? $_POST['CurrentPlayersPage'] : 0);
	$params['players']['current_page'] = $current_page;

	$params['players']['page_count'] = $playerdb->CountPages($activity_filter, $status_filter, $pname_filter);

	// get the list of all existing players; (Username, Wins, Losses, Draws, Last Query, Free slots, Avatar, Country)
	$list = $playerdb->ListPlayers($activity_filter, $status_filter, $pname_filter, $condition, $order, $current_page);

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
		$entry['friendly_flag'] = ($data['FriendlyFlag'] == 1) ? 'yes' : 'no';
		$entry['blind_flag'] = ($data['BlindFlag'] == 1) ? 'yes' : 'no';
		$entry['country'] = $data['Country'];
		$entry['last_query'] = $data['Last Query'];
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
	if (!$p) { $display_error = 'Invalid player.'; break; }

	$p_settings = $p->GetSettings();
	$score = $scoredb->GetScore($cur_player);

	$params['profile']['PlayerName'] = $p->Name();
	$params['profile']['PlayerType'] = $p->Type();
	$params['profile']['LastQuery'] = $p->LastQuery();
	$params['profile']['Registered'] = $p->Registered();
	$params['profile']['Firstname'] = $p_settings->GetSetting('Firstname');
	$params['profile']['Surname'] = $p_settings->GetSetting('Surname');
	$params['profile']['Gender'] = $p_settings->GetSetting('Gender');
	$params['profile']['Country'] = $p_settings->GetSetting('Country');
	$params['profile']['Status'] = $p_settings->GetSetting('Status');
	$params['profile']['FriendlyFlag'] = $p_settings->GetSetting('FriendlyFlag');
	$params['profile']['BlindFlag'] = $p_settings->GetSetting('BlindFlag');
	$params['profile']['Avatar'] = $p_settings->GetSetting('Avatar');
	$params['profile']['Email'] = $p_settings->GetSetting('Email');
	$params['profile']['Imnumber'] = $p_settings->GetSetting('Imnumber');
	$params['profile']['Hobby'] = $p_settings->GetSetting('Hobby');
	$params['profile']['Level'] = $score->ScoreData->Level;
	$params['profile']['FreeSlots'] = $p->FreeSlots();
	$params['profile']['Exp'] = $score->ScoreData->Exp;
	$params['profile']['NextLevel'] = $scoredb->NextLevel($score->ScoreData->Level);
	$params['profile']['Wins'] = $score->ScoreData->Wins;
	$params['profile']['Losses'] = $score->ScoreData->Losses;
	$params['profile']['Draws'] = $score->ScoreData->Draws;
	$params['profile']['Posts'] = $forum->Threads->Posts->CountPosts($cur_player);

	if( $p_settings->GetSetting('Birthdate') != "0000-00-00" )
	{
		$params['profile']['Age'] = $p_settings->Age();
		$params['profile']['Sign'] = $p_settings->Sign();
		$params['profile']['Birthdate'] = date("d-m-Y", strtotime($p_settings->GetSetting('Birthdate')));
	}
	else
	{
		$params['profile']['Age'] = 'Unknown';
		$params['profile']['Sign'] = 'Unknown';
		$params['profile']['Birthdate'] = 'Unknown';
	}

	$settings = $player->GetSettings();
	$params['profile']['CurPlayerName'] = $player->Name();
	$params['profile']['HiddenCards'] = $settings->GetSetting('BlindFlag');
	$params['profile']['FriendlyPlay'] = $settings->GetSetting('FriendlyFlag');
	$params['profile']['RandomDeck'] = $settings->GetSetting('RandomDeck');
	$params['profile']['timezone'] = $settings->GetSetting('Timezone');
	$params['profile']['send_challenges'] = ($access_rights[$player->Type()]["send_challenges"]) ? 'yes' : 'no';
	$params['profile']['messages'] = ($access_rights[$player->Type()]["messages"]) ? 'yes' : 'no';
	$params['profile']['change_rights'] = (($access_rights[$player->Type()]["change_rights"]) AND $p->Type() != "admin") ? 'yes' : 'no';
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

	$params['profile']['statistics'] = $player->GetVersusStats($p->Name());

	break;


case 'Messages':
	$current_subsection = isset($_POST['challengebox']) ? $_POST['challengebox'] : "incoming";
	$current_location = ((isset($_POST['CurrentLocation'])) ? $_POST['CurrentLocation'] : "inbox");

	if ($current_subsection != 'incoming' AND $current_subsection != 'outgoing') { $display_error = "Invalid challenges subsection."; break; }
	if (!in_array($current_location, array('inbox', 'sent_mail', 'all_mail'))) { $display_error = "Invalid messages subsection."; break; }
	if ($current_location == 'all_mail' AND !$access_rights[$player->Type()]["see_all_messages"]) { $display_error = 'Access denied.'; break; }

	$settings = $player->GetSettings();
	$params['messages']['PlayerName'] = $player->Name();
	$params['messages']['notification'] = $player->GetNotification();
	$params['messages']['timezone'] = $settings->GetSetting('Timezone');
	$params['messages']['RandomDeck'] = $settings->GetSetting('RandomDeck');
	$params['messages']['system_name'] = SYSTEM_NAME;

	$decks = $params['messages']['decks'] = $player->ListReadyDecks();
	$params['messages']['random_deck'] = (count($decks) > 0) ? $decks[array_rand($decks)] : '';
	$params['messages']['deck_count'] = count($decks);
	$params['messages']['free_slots'] = $gamedb->CountFreeSlots2($player->Name());

	$function_type = (($current_subsection == "incoming") ? "ListChallengesTo" : "ListChallengesFrom");
	$params['messages']['challenges'] = $messagedb->$function_type($player->Name());
	$params['messages']['challenges_count'] = count($params['messages']['challenges']);
	$params['messages']['current_subsection'] = $current_subsection;

	$params['messages']['date_val'] = $date = (isset($_POST['date_filter'])) ? $_POST['date_filter'] : 'none';
	$params['messages']['name_val'] = $name = (isset($_POST['name_filter'])) ? postdecode($_POST['name_filter']) : 'none';
	$params['messages']['current_order'] = $current_order = (isset($_POST['CurrentOrd'])) ? $_POST['CurrentOrd'] : 'DESC';
	$params['messages']['current_condition'] = $current_condition = (isset($_POST['CurrentCond'])) ? $_POST['CurrentCond'] : 'Created';
	$params['messages']['current_page'] = $current_page = (isset($_POST['CurrentMesPage'])) ? $_POST['CurrentMesPage'] : 0;

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

	$params['messages']['page_count'] = $messagedb->$pages_type($player->Name(), $date, $name);
	$params['messages']['messages'] = $list;
	$params['messages']['messages_count'] = count($list);
	$params['messages']['current_location'] = $current_location;
	$params['messages']['name_filter'] = $name_list;
	$params['messages']['current_page'] = $current_page;

	$params['messages']['send_messages'] = (($access_rights[$player->Type()]["messages"]) ? 'yes' : 'no');
	$params['messages']['accept_challenges'] = (($access_rights[$player->Type()]["accept_challenges"]) ? 'yes' : 'no');
	$params['messages']['see_all_messages'] = (($access_rights[$player->Type()]["see_all_messages"]) ? 'yes' : 'no');

	break;


case 'Message_details':
	$messageid = $_POST['CurrentMessage'];
	$message = $messagedb->GetMessage($messageid, $player->Name());
	if (!$message) { $display_error = "Invalid message."; break; }

	$params['message_details']['PlayerName'] = $player->Name();
	$params['message_details']['system_name'] = SYSTEM_NAME;
	$params['message_details']['timezone'] = $player->GetSettings()->GetSetting('Timezone'); 

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
	$params['message_new']['Author'] = $_POST['Author'];
	$params['message_new']['Recipient'] = $_POST['Recipient'];
	$params['message_new']['Content'] = ((isset($_POST['Content'])) ? $_POST['Content'] : '');
 	$params['message_new']['Subject'] = ((isset($_POST['Subject'])) ? $_POST['Subject'] : '');

	break;


case 'Games':
	$settings = $player->GetSettings();
	$params['games']['PlayerName'] = $player->Name();
	$params['games']['timezone'] = $settings->GetSetting('Timezone');
	$params['games']['games_details'] = $settings->GetSetting('GamesDetails');
	$params['games']['BlindFlag'] = $settings->GetSetting('BlindFlag');
	$params['games']['FriendlyFlag'] = $settings->GetSetting('FriendlyFlag');
	$params['games']['RandomDeck'] = $settings->GetSetting('RandomDeck');

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
			$params['games']['list'][$i]['finishable'] = (time() - strtotime($data['Last Action']) >= 60*60*24*7*3 and $data['Current'] != $player->Name()) ? 'yes' : 'no';
		}
	}

	$params['games']['current_subsection'] = (isset($_POST['subsection'])) ? $_POST['subsection'] : 'free_games';
	$params['games']['HiddenCards'] = $hidden_f = (isset($_POST['HiddenCards'])) ? $_POST['HiddenCards'] : 'none';
	$params['games']['FriendlyPlay'] = $friendly_f = (isset($_POST['FriendlyPlay'])) ? $_POST['FriendlyPlay'] : 'none';

	$hostedgames = $gamedb->ListHostedGames($player->Name());
	$free_games = $gamedb->ListFreeGames($player->Name(), $hidden_f, $friendly_f);
	$params['games']['free_slots'] = $gamedb->CountFreeSlots1($player->Name());
	$params['games']['decks'] = $decks = $player->ListReadyDecks();
	$params['games']['random_deck'] = (count($decks) > 0) ? $decks[array_rand($decks)] : '';

	if (count($free_games) > 0)
	{
		$buffer = array();
		foreach ($free_games as $i => $data)
		{
			$opponent_name = $data['Player1'];

			// buffer supplementary data to reduce number of queries
			if (isset($buffer[$opponent_name]))
			{
				$status = $buffer[$opponent_name]['status'];
				$inactivity = $buffer[$opponent_name]['inactivity'];
			}
			else
			{
				$cur_player = $playerdb->GetPlayer($opponent_name);
				$buffer[$opponent_name]['status'] = $status = $cur_player->GetSettings()->GetSetting('Status');
				$buffer[$opponent_name]['inactivity'] = $inactivity = time() - strtotime($cur_player->LastQuery());
			}

			$params['games']['free_games'][$i]['opponent'] = $opponent_name;
			$params['games']['free_games'][$i]['gameid'] = $data['GameID'];
			$params['games']['free_games'][$i]['active'] = ($inactivity < 60*10) ? 'yes' : 'no';
			$params['games']['free_games'][$i]['status'] = $status;
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
	$game = $gamedb->GetGame($gameid);

	// check if the game exists
	if (!$game) { $display_error = 'No such game!'; break; }

	$player1 = $game->Name1();
	$player2 = $game->Name2();

	// check if this user is allowed to view this game
	if ($player->Name() != $player1 and $player->Name() != $player2) { $display_error = 'You are not allowed to access this game.'; break; }

	// check if the game is a game in progress (and not a challenge)
	if ($game->State == 'waiting') { $display_error = 'Opponent did not accept the challenge yet!'; break; }

	// disable re-visiting
	if ( (($player->Name() == $player1) && ($game->State == 'P1 over')) || (($player->Name() == $player2) && ($game->State == 'P2 over')) ) { $display_error = 'Game is already over.'; break; }

	// prepare the neccessary data
	$opponent = $playerdb->GetPlayer(($player1 != $player->Name()) ? $player1 : $player2);
	$mydata = &$game->GameData[$player->Name()];
	$hisdata = &$game->GameData[$opponent->Name()];

	$params['game']['CurrentGame'] = $gameid;
	$params['game']['chat'] = (($access_rights[$player->Type()]["chat"]) ? 'yes' : 'no');

	// load needed settings
	$settings = $player->GetSettings();
	$o_settings = $opponent->GetSettings();
	$params['game']['c_text'] = $settings->GetSetting('Cardtext');
	$params['game']['c_img'] = $settings->GetSetting('Images');
	$params['game']['c_keywords'] = $settings->GetSetting('Keywords');
	$params['game']['c_oldlook'] = $settings->GetSetting('OldCardLook');

	$params['game']['minimize'] = $settings->GetSetting('Minimize');
	$params['game']['mycountry'] = $settings->GetSetting('Country');
	$params['game']['hiscountry'] = $o_settings->GetSetting('Country');
	$params['game']['timezone'] = $settings->GetSetting('Timezone');
	$params['game']['Background'] = $settings->GetSetting('Background');

	$params['game']['GameState'] = $game->State;
	$params['game']['Round'] = $game->Round;
	$params['game']['Outcome'] = $game->Outcome();
	$params['game']['EndType'] = $game->EndType;
	$params['game']['Winner'] = $game->Winner;
	$params['game']['Surrender'] = $game->Surrender;
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
		$entry['Revealed'] = ( isset($mydata->Revealed[$i]) ) ? 'yes' : 'no';
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
	$nextgame_button = false;

	foreach ($list as $i => $data)
	{
		$cur_game = $gamedb->GetGame($data['GameID']);
		$cur_opponent = ($cur_game->Name1() != $player->Name()) ? $cur_game->Name1() : $cur_game->Name2();

		if ($cur_game->Current == $player->Name()) $nextgame_button = true;

		$params['game']['GameList'][$i]['Value'] = $cur_game->ID();
		$params['game']['GameList'][$i]['Content'] = 'vs. '.htmlencode($cur_opponent);
		$params['game']['GameList'][$i]['Selected'] = (($cur_game->ID() == $_POST['CurrentGame']) ? 'yes' : 'no');
		$params['game']['GameList'][$i]['is_current'] = ($cur_game->Current == $player->Name()) ? 'yes' : 'no';
		$params['game']['GameList'][$i]['is_dead'] = ($playerdb->isDead($cur_opponent)) ? 'yes' : 'no';
		$params['game']['GameList'][$i]['in_progress'] = ($cur_game->State == 'in progress') ? 'yes' : 'no';
	}
	// - </quick game switching menu>

	// - <'jump to next game' button> 
	$params['game']['nextgame_button'] = ($nextgame_button) ? 'yes' : 'no';

	// - <game state indicator>
	$params['game']['opp_isOnline'] = (($opponent->isOnline()) ? 'yes' : 'no');
	$params['game']['opp_isDead'] = (($opponent->isDead()) ? 'yes' : 'no');
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

	$params['game']['display_avatar'] = $settings->GetSetting('Avatargame');
	$params['game']['correction'] = $settings->GetSetting('Correction');

	$params['game']['myavatar'] = $settings->GetSetting('Avatar');
	$params['game']['hisavatar'] = $o_settings->GetSetting('Avatar');

	$order = ( $settings->GetSetting('Chatorder') == "yes" ) ? "ASC" : "DESC";
	$params['game']['reverse_chat'] = $settings->GetSetting('Chatorder');
	$params['game']['messagelist'] = $message_list = $chatdb->ListChatMessages($game->ID(), $order);

	break;


case 'Deck_view':
	$gameid = $_POST['CurrentGame'];
	$game = $gamedb->GetGame($gameid);

	// check if the game exists
	if (!$game) { $display_error = 'No such game!'; break; }

	// check if this user is allowed to view this game
	if ($player->Name() != $game->Name1() and $player->Name() != $game->Name2()) { $display_error = 'You are not allowed to access this game.'; break; }

	$deck = $game->GameData[$player->Name()]->Deck;

	//load needed settings
	$settings = $player->GetSettings();
	$params['deck_view']['c_text'] = $settings->GetSetting('Cardtext');
	$params['deck_view']['c_img'] = $settings->GetSetting('Images');
	$params['deck_view']['c_keywords'] = $settings->GetSetting('Keywords');
	$params['deck_view']['c_oldlook'] = $settings->GetSetting('OldCardLook');

	$params['deck_view']['CurrentGame'] = $gameid;

	foreach (array('Common', 'Uncommon', 'Rare') as $class)
		$params['deck_view']['DeckCards'][$class] = $carddb->GetData($deck->$class);

	break;


case 'Game_note':
	$gameid = $_POST['CurrentGame'];
	$game = $gamedb->GetGame($gameid);

	// check if the game exists
	if (!$game) { $display_error = 'No such game!'; break; }

	// check if this user is allowed to view this game
	if ($player->Name() != $game->Name1() and $player->Name() != $game->Name2()) { $display_error = 'You are not allowed to access this game.'; break; }

	$params['game_note']['CurrentGame'] = $gameid;
	$params['game_note']['text'] = (isset($new_note)) ? $new_note : $game->GetNote($player->Name());

	break;


case 'Novels':
	$params['novels']['novel'] = $novel = ( isset($_POST['novel']) ) ? $_POST['novel'] : "";
	$params['novels']['chapter'] = $chapter = ( isset($_POST['chapter']) ) ? $_POST['chapter'] : "";
	$params['novels']['part'] = $part = ( isset($_POST['part']) ) ? $_POST['part'] : "";
	$params['novels']['page'] = $page = ( isset($_POST['page']) ) ? $_POST['page'] : "";

	break;


case 'Settings':
	$settings = $player->GetSettings();
	$params['settings']['current_settings'] = $settings->GetAll();
	$params['settings']['PlayerType'] = $player->Type();
	$params['settings']['change_own_avatar'] = (($access_rights[$player->Type()]["change_own_avatar"]) ? 'yes' : 'no');

	//date is handled separately
	$birthdate = $settings->GetSetting('Birthdate');
	list($year, $month, $day) = explode("-", $birthdate);

	if( $birthdate != "0000-00-00" )
	{
		$params['settings']['current_settings']["Age"] = $settings->Age();
		$params['settings']['current_settings']["Sign"] = $settings->Sign();
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
	$params['forum_overview']['notification'] = $player->GetNotification();
	$params['forum_overview']['timezone'] = $player->GetSettings()->GetSetting('Timezone');

	break;


case 'Forum_search':
	$params['forum_search']['phrase'] = $phrase = (isset($_POST['phrase'])) ? $_POST['phrase'] : '';
	$params['forum_search']['target'] = $target = (isset($_POST['target'])) ? $_POST['target'] : 'all';
	$params['forum_search']['section'] = $section = (isset($_POST['section'])) ? $_POST['section'] : 'any';
	$params['forum_search']['threads'] = (trim($phrase) != "") ? $forum->Search($phrase, $target, $section) : array();
	$params['forum_search']['sections'] = $forum->ListTargetSections();
	$params['forum_search']['notification'] = $player->GetNotification();
	$params['forum_search']['timezone'] = $player->GetSettings()->GetSetting('Timezone');

	break;


case 'Section_details':
	$section_id = $_POST['CurrentSection'];
	$current_page = (isset($_POST['CurrentPage'])) ? $_POST['CurrentPage'] : 0;

	$section = $forum->GetSection($section_id);
	if (!$section) { $display_error = "Invalid forum section."; break; }

	$thread_list = $forum->Threads->ListThreads($section_id, $current_page);
	if ($thread_list === false) { $display_error = "Invalid section page."; break; }

	$params['forum_section']['section'] = $section;
	$params['forum_section']['threads'] = $thread_list;
	$params['forum_section']['pages'] = $forum->Threads->CountPages($section_id);
	$params['forum_section']['current_page'] = $current_page;
	$params['forum_section']['create_thread'] = (($access_rights[$player->Type()]["create_thread"]) ? 'yes' : 'no');
	$params['forum_section']['notification'] = $player->GetNotification();
	$params['forum_section']['timezone'] = $player->GetSettings()->GetSetting('Timezone');

	break;


case 'Thread_details':
	$thread_id = $_POST['CurrentThread'];
	$current_page = (isset($_POST['CurrentPage'])) ? $_POST['CurrentPage'] : 0;

	$thread_data = $forum->Threads->GetThread($thread_id);
	if (!$thread_data) { $display_error = "Invalid forum thread."; break; }

	$post_list = $forum->Threads->Posts->ListPosts($thread_id, $current_page);
	if ($post_list === FALSE) { $display_error = "Invalid thread page."; break; }

	$params['forum_thread']['Thread'] = $thread_data;
	$params['forum_thread']['Section'] = $forum->GetSection($thread_data['SectionID']);
	$params['forum_thread']['Pages'] = $forum->Threads->Posts->CountPages($thread_id);
	$params['forum_thread']['CurrentPage'] = $current_page;
	$params['forum_thread']['PostList'] = $post_list;
	$params['forum_thread']['Delete'] = ((isset($_POST['thread_delete'])) ? 'yes' : 'no');
	$params['forum_thread']['DeletePost'] = ((isset($_POST['delete_post'])) ? $_POST['delete_post'] : 0);
	$params['forum_thread']['PlayerName'] = $player->Name();
	$params['forum_thread']['notification'] = $player->GetNotification();
	$params['forum_thread']['timezone'] = $player->GetSettings()->GetSetting('Timezone');
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
	$section = $forum->GetSection($_POST['CurrentSection']);
	if (!$section) { $display_error = "Invalid forum section."; break; }

	$params['forum_thread_new']['Section'] = $section;
	$params['forum_thread_new']['Content'] = ((isset($_POST['Content'])) ? $_POST['Content'] : "");
	$params['forum_thread_new']['Title'] = ((isset($_POST['Title'])) ? $_POST['Title'] : "");
	$params['forum_thread_new']['chng_priority'] = (($access_rights[$player->Type()]["chng_priority"]) ? 'yes' : 'no');

	break;


case 'New_post':
	$thread = $forum->Threads->GetThread($_POST['CurrentThread']);
	if (!$thread) { $display_error = "Invalid thread."; break; }

	$params['forum_post_new']['Thread'] = $thread;
	if (isset($_POST['quote_post']))
	{
		$post_data = $forum->Threads->Posts->GetPost($_POST['quote_post']);
		$quoted_content = '[quote='.$post_data['Author'].']'.$post_data['Content'].'[/quote]';
	}
	$params['forum_post_new']['Content'] = ((isset($_POST['Content'])) ? $_POST['Content'] : ((isset($quoted_content)) ? $quoted_content : ''));

	break;


case 'Edit_thread':
	$thread_data = $forum->Threads->GetThread($_POST['CurrentThread']);
	if (!$thread_data) { $display_error = "Invalid thread."; break; }

	$params['forum_thread_edit']['Thread'] = $thread_data;
	$params['forum_thread_edit']['Section'] = $forum->GetSection($thread_data['SectionID']);
	$params['forum_thread_edit']['SectionList'] = $forum->ListTargetSections($thread_data['SectionID']);
	$params['forum_thread_edit']['chng_priority'] = (($access_rights[$player->Type()]["chng_priority"]) ? 'yes' : 'no');
	$params['forum_thread_edit']['move_thread'] = (($access_rights[$player->Type()]["move_thread"]) ? 'yes' : 'no');

	break;


case 'Edit_post':
	$post_data = $forum->Threads->Posts->GetPost($_POST['CurrentPost']);
	if (!$post_data) { $display_error = "Invalid post."; break; }

	$params['forum_post_edit']['Post'] = $post_data;
	$params['forum_post_edit']['CurrentPage'] = $_POST['CurrentPage'];
	$params['forum_post_edit']['ThreadList'] = $forum->Threads->ListTargetThreads($post_data['ThreadID']);
	$params['forum_post_edit']['Thread'] = $forum->Threads->GetThread($post_data['ThreadID']);
	$params['forum_post_edit']['Content'] = ((isset($_POST['Content'])) ? $_POST['Content'] : $post_data['Content']);
	$params['forum_post_edit']['move_post'] = (($access_rights[$player->Type()]["move_post"]) ? 'yes' : 'no');

	break;

case 'Replays':
	$current_page = ((isset($_POST['CurrentRepPage'])) ? $_POST['CurrentRepPage'] : 0);
	$params['replays']['current_page'] = $current_page;
	$params['replays']['PlayerFilter'] = $player_f = (isset($_POST['PlayerFilter'])) ? $_POST['PlayerFilter'] : "none";
	$params['replays']['HiddenCards'] = $hidden_f = (isset($_POST['HiddenCards'])) ? $_POST['HiddenCards'] : "none";
	$params['replays']['FriendlyPlay'] = $friendly_f = (isset($_POST['FriendlyPlay'])) ? $_POST['FriendlyPlay'] : "none";
	$params['replays']['VictoryFilter'] = $victory_f = (isset($_POST['VictoryFilter'])) ? $_POST['VictoryFilter'] : "none";

	if (!isset($_POST['ReplaysOrder'])) $_POST['ReplaysOrder'] = "DESC"; // default ordering
	if (!isset($_POST['ReplaysCond'])) $_POST['ReplaysCond'] =  "Finished"; // default order condition
	$params['replays']['order'] = $order = $_POST['ReplaysOrder'];
	$params['replays']['cond'] = $cond = $_POST['ReplaysCond'];

	$params['replays']['list'] = $replaydb->ListReplays($player_f, $hidden_f, $friendly_f, $victory_f, $current_page, $cond, $order);
	$params['replays']['page_count'] = $replaydb->CountPages($player_f, $hidden_f, $friendly_f, $victory_f);
	$params['replays']['timezone'] = $player->GetSettings()->GetSetting('Timezone');
	$params['replays']['players'] = $replay_players = $replaydb->ListPlayers();
	$params['replays']['my_replays'] = (in_array($player->Name(), $replay_players) ? 'yes' : 'no');

	break;

case 'Replay':
	$params['replay']['CurrentReplay'] = $gameid = (isset($_POST['CurrentReplay'])) ? $_POST['CurrentReplay'] : 0;
	$params['replay']['PlayerView'] = $player_view = (isset($_POST['PlayerView'])) ? $_POST['PlayerView'] : 1;
	$params['replay']['CurrentTurn'] = $turn = (isset($_POST['Turn']) ? $_POST['Turn'] : 1);

	// prepare the necessary data
	$replay = $replaydb->GetReplay($gameid, $turn);
	if (!$replay) { $display_error = "Invalid replay."; break; }
	if (!($player_view == 1 OR $player_view == 2)) { $display_error = "Invalid player selection."; break; }

	// increment number of views each time player enters a replay
	if ($turn == 1 AND $player_view == 1) $replay->IncrementViews();

	// determine player view
	$player1 = ($player_view == 1) ? $replay->Name1() : $replay->Name2();
	$player2 = ($player_view == 1) ? $replay->Name2() : $replay->Name1();

	$replay_data = $replay->ReplayData;
	$p1data = $replay_data[$player1];
	$p2data = $replay_data[$player2];

	// load needed settings
	$settings = $player->GetSettings();
	$params['replay']['c_text'] = $settings->GetSetting('Cardtext');
	$params['replay']['c_img'] = $settings->GetSetting('Images');
	$params['replay']['c_keywords'] = $settings->GetSetting('Keywords');
	$params['replay']['c_oldlook'] = $settings->GetSetting('OldCardLook');
	$params['replay']['minimize'] = $settings->GetSetting('Minimize');
	$params['replay']['Background'] = $settings->GetSetting('Background');

	$params['replay']['turns'] = $turns = $replay->NumberOfTurns();
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
		$entry['Revealed'] = ( isset($p1data->Revealed[$i]) ) ? 'yes' : 'no';
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
		$entry['Revealed'] = ( isset($p2data->Revealed[$i]) ) ? 'yes' : 'no';
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

case 'Cards':
	$current_page = ((isset($_POST['CurrentCardsPage'])) ? $_POST['CurrentCardsPage'] : 0);
	if (!is_numeric($current_page) OR $current_page < 0) { $display_error = 'Invalid cards page.'; break; }

	$params['cards']['current_page'] = $current_page;
	$classfilter = $params['cards']['ClassFilter'] = isset($_POST['ClassFilter']) ? $_POST['ClassFilter'] : 'none';
	$costfilter = $params['cards']['CostFilter'] = isset($_POST['CostFilter']) ? $_POST['CostFilter'] : 'none';
	$keywordfilter = $params['cards']['KeywordFilter'] = isset($_POST['KeywordFilter']) ? $_POST['KeywordFilter'] : 'none';
	$advancedfilter = $params['cards']['AdvancedFilter'] = isset($_POST['AdvancedFilter']) ? $_POST['AdvancedFilter'] : 'none';
	$supportfilter = $params['cards']['SupportFilter'] = isset($_POST['SupportFilter']) ? $_POST['SupportFilter'] : 'none';
	$createdfilter = $params['cards']['CreatedFilter'] = isset($_POST['CreatedFilter']) ? $_POST['CreatedFilter'] : 'none';
	$modifiedfilter = $params['cards']['ModifiedFilter'] = isset($_POST['ModifiedFilter']) ? $_POST['ModifiedFilter'] : 'none';

	$params['cards']['keywords'] = $carddb->Keywords();
	$params['cards']['created_dates'] = $carddb->ListCreationDates();
	$params['cards']['modified_dates'] = $carddb->ListModifyDates();

	$filter = array();
	if( $classfilter != 'none' ) $filter['class'] = $classfilter;
	if( $keywordfilter != 'none' ) $filter['keyword'] = $keywordfilter;
	if( $costfilter != 'none' ) $filter['cost'] = $costfilter;
	if( $advancedfilter != 'none' ) $filter['advanced'] = $advancedfilter;
	if( $supportfilter != 'none' ) $filter['support'] = $supportfilter;
	if( $createdfilter != 'none' ) $filter['created'] = $createdfilter;
	if( $modifiedfilter != 'none' ) $filter['modified'] = $modifiedfilter;
	$ids = $carddb->GetList($filter);
	$params['cards']['CardList'] = $carddb->GetData($ids, $current_page);
	$params['cards']['page_count'] = $carddb->CountPages($filter);

	// load card display settings
	$settings = $player->GetSettings();
	$params['cards']['c_text'] = $settings->GetSetting('Cardtext');
	$params['cards']['c_img'] = $settings->GetSetting('Images');
	$params['cards']['c_keywords'] = $settings->GetSetting('Keywords');
	$params['cards']['c_oldlook'] = $settings->GetSetting('OldCardLook');

	break;


case 'Cards_details':
	$card_id = isset($_POST['card']) ? $_POST['card'] : 0;
	if (!is_numeric($card_id) OR $card_id <= 0) { $display_error = 'Invalid card id.'; break; }

	$card = $carddb->GetCard($card_id);
	if ($card->CardData->Name == "Invalid Card") { $display_error = 'Invalid card.'; break; }

	$params['cards_details']['data'] = $card->GetData();
	$thread_id = $forum->Threads->CardThread($card_id);
	$params['cards_details']['discussion'] = ($thread_id) ? 'yes' : 'no';
	$params['cards_details']['create_thread'] = ($access_rights[$player->Type()]["create_thread"]) ? 'yes' : 'no';
	$params['cards_details']['statistics'] = $statistics->CardStatistics($card_id);

	// load card display settings
	$settings = $player->GetSettings();
	$params['cards_details']['c_text'] = $settings->GetSetting('Cardtext');
	$params['cards_details']['c_img'] = $settings->GetSetting('Images');
	$params['cards_details']['c_keywords'] = $settings->GetSetting('Keywords');
	$params['cards_details']['c_oldlook'] = $settings->GetSetting('OldCardLook');

	break;


case 'Statistics':
	// uses: $current_statistic
	if (isset($_POST['card_statistics'])) $subsection = "card_statistics";
	elseif (isset($_POST['other_statistics'])) $subsection = "other_statistics";
	elseif (!isset($subsection)) $subsection = "card_statistics";

	if (!isset($current_statistic)) $current_statistic = "Played";

	$params['statistics']['current_subsection'] = $subsection;
	$params['statistics']['current_statistic'] = $current_statistic = (isset($_POST['selected_statistic'])) ? $_POST['selected_statistic'] : 'Played';
	$params['statistics']['current_size'] = $current_size = (isset($_POST['selected_size'])) ? $_POST['selected_size'] : 10;

	if ($subsection == "card_statistics")
	{
		$params['statistics']['card_statistics'] = $statistics->Cards($current_statistic, $current_size);
	}
	elseif ($subsection == "other_statistics")
	{
		$params['statistics']['victory_types'] = $statistics->VictoryTypes();
		$params['statistics']['game_modes'] = $statistics->GameModes();
		$params['statistics']['skins'] = $statistics->Skins();
		$params['statistics']['backgrounds'] = $statistics->Backgrounds();
		$params['statistics']['suggested'] = $statistics->SuggestedConcepts();
		$params['statistics']['implemented'] = $statistics->ImplementedConcepts();
	}

	break;


default:
	break;
}

	// error handler
	if (isset($display_error) AND $display_error != '') { $current = 'Error'; $params["error"]["message"] = $display_error; }

	// which section to display
	$params["main"]["section"] = $current;

	// HTML code generation

	$querytime_end = microtime(TRUE);
	$xslttime_start = $querytime_end;

	echo XSLT("templates/arcomage.xsl", $params);

	$xslttime_end = microtime(TRUE);

	$query = (int)(1000*$db->qtime);
	$logic = (int)(1000*($querytime_end - $querytime_start)) - $query;
	$transform = (int)(1000*($xslttime_end - $xslttime_start));
	$total = (int)(1000*($xslttime_end - $querytime_start));
	echo "<!-- Page generated in {$total} (php:{$logic} + sql:{$query} + xslt:{$transform}) ms. {$db->queries} queries used. -->";
?>
