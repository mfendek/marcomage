<?php
/*
	presentation
*/
?>
<?php

	function Generate_Registration()
	{
		$content = "";
	
		$content.= '<div id="registration">'."\n";
		
		$content.= '<h3>Registration</h3>'."\n";
		$content.= '<p>Login name</p>'."\n";
		$content.= '<div><input class="text_data" type="text" name="NewUsername" maxlength="20" /></div>'."\n";
		$content.= '<p>Password</p>'."\n";
		$content.= '<div><input class="text_data" type="password" name="NewPassword" maxlength="20" /></div>'."\n";
		$content.= '<p>Confirm password</p>'."\n";
		$content.= '<div><input class="text_data" type="password" name="NewPassword2" maxlength="20" /></div>'."\n";
		$content.= '<div>'."\n";
		$content.= '<input type="submit" name="Register" value="Register" />'."\n";
		$content.= '<input type="submit" name="ReturnToLogin" value="Back" />'."\n";
		$content.= '</div>'."\n";
		$content.= '<input type="hidden" name="Registration" value="Register new user" />'."\n";
		
		$content.= '</div>'."\n";
		
		return $content;
	}
	
	function Generate_Deck(array $param)
	{
		$content = "";
		$all_colors = $param['Colors'];
				
		global $carddb;
	
		$currentdeck = $param['Deck']['CurrentDeck'];
		$classfilter = $param['Deck']['ClassFilter'];
		$costfilter = $param['Deck']['CostFilter'];
		$keywordfilter = $param['Deck']['KeywordFilter'];
		$advancedfilter = $param['Deck']['AdvancedFilter'];
		
		// remember the current location across pages
		$content.= '<input type = "hidden" name = "CurrentDeck" value = "'.htmlencode($currentdeck).'"/>'."\n";
		$content.= '<input type = "hidden" name = "ClassFilter" value = "'.htmlencode($classfilter).'"/>'."\n";
		$content.= '<input type = "hidden" name = "CostFilter" value = "'.htmlencode($costfilter).'"/>'."\n";
		$content.= '<input type = "hidden" name = "KeywordFilter" value = "'.htmlencode($keywordfilter).'"/>'."\n";
		$content.= '<input type = "hidden" name = "AdvancedFilter" value = "'.htmlencode($advancedfilter).'"/>'."\n";
		
		
		// load card display settings
		$c_text = $param['Deck']['c_text'];
		$c_img = $param['Deck']['c_img'];
		$c_keywords = $param['Deck']['c_keywords'];
		$c_oldlook = $param['Deck']['c_oldlook'];
		
		$res = $param['Deck']['Res'];
				
		$content.= '<div class="filters">'."\n";
		
		$content.= '<select name="selected_rarity"'.(($classfilter != "none") ? ' style="border-color: lime;" ' : '').'>'."\n";
		$content.= '<option value="Common" '.(($classfilter == 'Common') ? 'selected="selected"' : '').'>Common</option>'."\n";
		$content.= '<option value="Uncommon" '.(($classfilter == 'Uncommon') ? 'selected="selected"' : '').'>Uncommon</option>'."\n";
		$content.= '<option value="Rare" '.(($classfilter == 'Rare') ? 'selected="selected"' : '').'>Rare</option>'."\n";
		$content.= '</select>'."\n";
		
		$content.= '<select name="selected_keyword"'.(($keywordfilter != "none") ? ' style="border-color: lime;" ' : '').'>'."\n";
		$keywords = array_merge( array("none"=>"No keyword filters", "All keywords"=>"All keywords", "No keywords"=>"No keywords"), $carddb->Keywords());
		foreach($keywords as $keyword => $text)
			$content.= '<option value="'.$keyword.'" '.(($keywordfilter == $keyword)?'selected="selected"':'').'>'.$text.'</option>'."\n";
		$content.= '</select>'."\n";
		
		$content.= '<select name="selected_cost"'.(($costfilter != "none") ? ' style="border-color: lime;" ' : '').'>'."\n";
		$costs = array("none"=>"No cost filters", "Red"=>"Bricks only", "Blue"=>"Gems only", "Green"=>"Recruits only", "Zero"=>"Zero cost", "Mixed"=>"Mixed cost");
		foreach ($costs as $cost => $text)
			$content.= '<option value="'.$cost.'" '.(($costfilter == $cost)?'selected="selected"':'').'>'.$text.'</option>'."\n";
		$content.= '</select>'."\n";
		
		// advanced filter select menu - filters based upon appearance in card text
		$content.= '<select name="advanced_filter"'.(($advancedfilter != "none") ? ' style="border-color: lime;" ' : '').'>'."\n";
		$advanced_options = $carddb->ListAdvanced();
		foreach($advanced_options as $option => $name)
			$content.= '<option value="'.$option.'"'.(($advancedfilter == $option) ? ' selected="selected" ' : '').'>'.$name.'</option>'."\n";
		$content.= '</select>'."\n";
		
		$reset = (!$param['Deck']['reset']) ? 'name = "reset_deck_prepare" value = "Reset"'
		                                    : 'name = "reset_deck_confirm" value = "Confirm reset"';
		$randomize = (!$param['Deck']['randomize']) ? 'name = "randomize_deck_prepare" value = "Randomize"'
		                                            : 'name = "randomize_deck_confirm" value = "Confirm randomize"';
		
		$content.= '<input type = "submit" name = "filter" value = "Apply filters" />'."\n".
			 '<input type = "text" class="text_data" name = "NewDeckName" value = "'.htmlencode($currentdeck).'" maxlength="20" />'."\n".
			 '<input type = "submit" name = "rename_deck" value = "Rename" />'."\n".
			 '<input type = "submit" '.$reset.' />'."\n".
			 '<input type = "submit" '.$randomize.' />'."\n".
			 '<input type = "submit" name = "finish_deck" value = "Finish" />'."\n";
		     
		$content.= 'Avg cost / turn: <b style="color: '.$all_colors["RosyBrown"].';">'.round($res['Bricks'],2).' </b> <b style="color: '.$all_colors["DeepSkyBlue"].';">'.round($res['Gems'],2).' </b> <b style="color: '.$all_colors["DarkSeaGreen"].';">'.round($res['Recruits'],2).' </b>'."\n";
		$content.= '</div>'."\n";
		
		$content.= '<hr />'."\n";
		     
		$content.= '<div class="scroll">'."\n";
		$content.= '<table cellpadding="0" cellspacing="0">'."\n";
		
		$list = $param['Deck']['CardList'];
		
		if (count($list) > 0)
		{
			$content.= '<tr valign="top">'."\n";	
				
			foreach($list as $cardid)
			{			
				$card = $carddb->GetCard($cardid);
				$content.= '<td>'."\n";
				
				$content.= $card->CardString($c_text, $c_img, $c_keywords, $c_oldlook);
				$content.= '</td>'."\n";
			}
					
			$content.= '</tr>'."\n";
			
			if ($param['Deck']['Take'])
			{
			$content.= '<tr>'."\n";
					
				foreach($list as $cardid)
				{				
					$content.= '<td>'."\n";
					
					// if the deck's $classfilter section isn't full yet, display the button that adds the card
					$content.= '<input type = "submit" name = "add_card['.$cardid.']" value="Take" />'."\n";
					
					$content.= '</td>'."\n";
				}		
			$content.= '</tr>'."\n";
			}
		}
		else $content.= '<tr><td></td></tr>'."\n";
		
		$content.= '</table>'."\n";
		$content.= '</div>'."\n";
		
		
		$content.= '<table class="deck" cellpadding="0" cellspacing="0" >'."\n";
		
		$content.= '<tr>'."\n";
			foreach (array('Common'=>'Lime', 'Uncommon'=>$all_colors["DarkRed"], 'Rare'=>'Yellow') as $class => $classcolor)
			{
				$content.= '<th>'."\n";
				// section text
				$content.= '<p style="color: '.$classcolor.';">'.$class.' Cards</p>';
				$content.= '</th>'."\n";
			}
		$content.= '</tr>'."\n";
		
		$content.= '<tr valign="top">'."\n";
		
		foreach (array('Common'=>'Lime', 'Uncommon'=>$all_colors["DarkRed"], 'Rare'=>'Yellow') as $class => $classcolor)
		{
			$content.= '<td>'."\n";
			
			$content.= '<table class="centered" cellpadding="0" cellspacing="0">'."\n";
			$content.= '<tr>'."\n";

			foreach ($param['Deck']['DeckCards'][$class] as $index => $cardid)
			{
				$content.= '<td>'."\n";
				
				$card = $carddb->GetCard($cardid);
				
				$content.= $card->CardString($c_text, $c_img, $c_keywords, $c_oldlook);
				
				if ($cardid != 0) $content.= '<input type = "submit" name = "return_card['.$cardid.']" value="Return" />'."\n";
				
				$content.= '</td>'."\n";
				
				if ((($index % 3) == 0) && ($index != 15)) $content.= '</tr>'."\n".'<tr>'."\n"; //next row
				
			}
			$content.= '</tr>'."\n";
			$content.= '</table>'."\n";
			
			$content.= '</td>'."\n";
		}
		$content.= '</tr>'."\n";
		
		$content.= '</table>'."\n";
		
		return $content;
	}
	
	function Generate_Game(array $param)
	{
		$content = "";
		$all_colors = $param['Colors'];
				
		global $carddb;
		
		$gameid = $param['Game']['CurrentGame'];
		
		$c_text = $param['Game']['c_text'];
		$c_img = $param['Game']['c_img'];
		$c_keywords = $param['Game']['c_keywords'];
		$c_oldlook = $param['Game']['c_oldlook'];
		
		$minimize = $param['Game']['minimize'];
		$mycountry = $param['Game']['mycountry'];
		$hiscountry = $param['Game']['hiscountry'];

		// remember the current location across pages
		$content.= '<input type = "hidden" name = "CurrentGame" value = "'.$gameid.'"/>'."\n";
		
		// display supportive information
		if ($param['Game']['GameState'] == 'in progress')
		{
			$content.= '<p class="information_line">'.'Round '.$param['Game']['Round'].'</p>'."\n";
		}
		else // 'finished'
		{
			$content.= '<p class="information_trans">';
			
			if ($param['Game']['Winner'] == $param['Game']['PlayerName'])
				$content.= 'You have won'.' in round '.$param['Game']['Round'].'. '.$param['Game']['Outcome'].'.';
			elseif ($param['Game']['Winner'] == $param['Game']['OpponentName'])
				$content.= $param['Game']['Winner'].' has won'.' in round '.$param['Game']['Round'].'. '.$param['Game']['Outcome'].'.';
			elseif ($param['Game']['Winner'] == '' and $param['Game']['Outcome'] == 'Draw')
				$content.= 'Game ended in a draw in round '.$param['Game']['Round'].'.';
			elseif ($param['Game']['Winner'] == '' and $param['Game']['Outcome'] == 'Aborted')
				$content.= 'Game was aborted in round '.$param['Game']['Round'].'.';
			
			$content.= '</p>'."\n";
		}		
		
		// four rows: your cards, messages and buttons, the current status, opponent's cards
		$content.= '<table class="centered" cellpadding="0" cellspacing="0">'."\n";
		
		// <your cards>
		$content.= '<tr valign="top">'."\n";
		
		for ($i = 1; $i <= 8; $i++)
		{
			$cardid = $param['Game']['MyHand'][$i];
			$card = $carddb->GetCard($cardid);
			
			$content.= '<td align="center">'."\n";
			
			// display discard button
			if (($param['Game']['GameState'] == 'in progress') && ($param['Game']['Current'] == $param['Game']['PlayerName'])) $content.= '<input type = "submit" name="discard_card['.$i.']" value="Discard"/>'."\n";

			// display new card indicator, if set
			if (isset($param['Game']['MyNewCards'][$i])) $content.= '<p class="newcard_flag">NEW CARD</p>'."\n";
			
			// display card
			$content.= $card->CardString($c_text, $c_img, $c_keywords, $c_oldlook);
			
			// if the card is playable under the current conditions, display the 'play' and the optional 'mode' controls
			if (($param['Game']['MyBricks'] >= $card->CardData->Bricks) and ($param['Game']['MyGems'] >= $card->CardData->Gems) and ($param['Game']['MyRecruits'] >= $card->CardData->Recruits) and ($param['Game']['GameState'] == 'in progress') and ($param['Game']['Current'] == $param['Game']['PlayerName']))
			{
				$content.= '<input type = "submit" name="play_card['.$i.']" value="Play"/>'."\n";
				
				if (($card->CardData->Modes > 1))
				{
					$content.= '<select name="card_mode['.$i.']" size="1">'."\n";
					for ($m = 1; $m <= $card->CardData->Modes; $m++)
					{
						// hack to skip special cases of several cards when the card could target itself even though it's against the rules
						if ($m == $i && ($cardid == 269 || $cardid == 298)) continue;
						$content.= '<option value="'.$m.'">'.$m.'</option>'."\n";
					}
					$content.= '</select>'."\n";
				}
			}
			
			$content.= '</td>'."\n";
		}
		
		$content.= '</tr>'."\n";
		// </your cards>
		
		// <messages (and game buttons)>
		$content.= '<tr>'."\n";
		
		// - <quick game switching menu>
		$content.= '<td>'."\n";
		$content.= '<select name="games_list">'."\n";
		
					
		foreach ($param['Game']['GameList'] as $i => $names)
		{					
			$content.= '<option value="'.$param['Game']['GameList'][$i]['Value'].'"'.(($param['Game']['GameList'][$i]['Selected']) ? ' selected="selected"' : '').(($param['Game']['GameList'][$i]['Color'] != '') ? ' style="color: '.$param['Game']['GameList'][$i]['Color'].'"' : '').'>'.$param['Game']['GameList'][$i]['Content'].'</option>'."\n";
		}
			
		$content.= '</select>'."\n";			
		$content.= '</td>'."\n";

		$content.= '<td>'."\n";
		
		$content.= '<input type="submit" name="jump_to_game" value="Select" />'."\n";	
		
		// - </quick game switching menu>
		
		// - <'jump to next game' button>
		
		if ($param['Game']['num_games_your_turn'] > 0) $content.= '<input type="submit" name="active_game" value="Next game" />'."\n";
		
		$content.= '</td>'."\n";
		
		// - </'jump to next game' button>
		
		$content.= '<td></td>'."\n";
				
		// - <game state indicator>
		if ($param['Game']['GameState'] == 'in progress')
		{
			$content.= '<td colspan="2">'."\n";
			if ($param['Game']['Current'] == $param['Game']['PlayerName'])
				$content.= '<p class="info_label" style="color: lime">It is your turn';
			else
			if ($param['Game']['opp_isOnline'])
				$content.= '<p class="info_label" style="color: '.$all_colors["HotPink"].'">It is <span style="color: white;">'.htmlencode($param['Game']['OpponentName']).'</span>\'s turn';
			else
				$content.= '<p class="info_label" style="color: '.$all_colors["HotPink"].'">It is '.htmlencode($param['Game']['OpponentName']).'\'s turn';
			$content.= '</p>'."\n";
			$content.= '</td>'."\n";
			$content.= '<td></td>'."\n".'<td><input type="submit" name="view_deck" value="Deck" /></td>'."\n";
			
			// -- <surrender/abort button>
			$content.= '<td style="text-align: right">'."\n";
			if( $param['Game']['opp_isDead'] )
			{
        			$content.= '<input type="submit" name="abort_game" value="Abort game" />'."\n";
			}
			elseif( time() - $param['Game']['Timestamp'] >= 60*60*24*7*3 and $param['Game']['Current'] != $param['Game']['PlayerName'] )
			{
				$content.= '<input type="submit" name="finish_game" value="Finish game" />'."\n";
			}
			else
			{
				$content.= ($param['Game']['surrender'])
				? '<input type="submit" name="surrender" value="Surrender" />'."\n"
				: '<input type="submit" name="confirm_surrender" value="Confirm surrender" />'."\n";
			}
			$content.= '</td>'."\n";
			// -- </surrender/abort button>
		}
		else
		{
			$content.= '<td align="center" colspan="2">'."\n";
			$content.= '<input type="submit" name="Confirm" value="Leave the game" />'."\n";
			$content.= '</td>'."\n";
		}
		// - </game state indicator>
		
		$content.= '</tr>'."\n";
		// </messages (and game buttons)>
		
		// status
		$content.= '<tr>'."\n";
		
		// your resources and tower
		$colors = $param['Game']['mycolors'];
		
		if ($minimize == "yes")
		{
			$content.=
			'<td class="minstats">'."\n".
			'<div>Quarry: <span'.$colors['Quarry'].'>'.$param['Game']['MyQuarry'].'</span></div>'."\n".
			'<div>Bricks: <span'.$colors['Bricks'].'>'.$param['Game']['MyBricks'].'</span></div>'."\n".
			'<div>Magic: <span'.$colors['Magic'].'>'.$param['Game']['MyMagic'].'</span></div>'."\n".
			'<div>Gems: <span'.$colors['Gems'].'>'.$param['Game']['MyGems'].'</span></div>'."\n".
			'<div>Dungeon: <span'.$colors['Dungeons'].'>'.$param['Game']['MyDungeons'].'</span></div>'."\n".
			'<div>Recruits: <span'.$colors['Recruits'].'>'.$param['Game']['MyRecruits'].'</span></div>'."\n".
			'<h5>'.htmlencode($param['Game']['PlayerName']).'<img width="18px" height="12px" src="img/flags/'.$mycountry.'.gif" alt="country flag" /></h5>'."\n".
			'<p class="info_label">Tower: <span'.$colors['Tower'].'>'.$param['Game']['MyTower'].'</span></p>'."\n".
			'<p class="info_label">Wall: <span'.$colors['Wall'].'>'.$param['Game']['MyWall'].'</span></p>'."\n".
			'</td>'."\n";
		}
		else
		{
			$content.=
			'<td class="stats">'."\n".
			'<div>'."\n".'<p'.$colors['Quarry'].'>'.$param['Game']['MyQuarry'].'</p>'."\n".'<p'.$colors['Bricks'].'>'.$param['Game']['MyBricks'].'</p>'."\n".'</div>'."\n".
			'<div>'."\n".'<p'.$colors['Magic'].'>'.$param['Game']['MyMagic'].'</p>'."\n".'<p'.$colors['Gems'].'>'.$param['Game']['MyGems'].'</p>'."\n".'</div>'."\n".
			'<div>'."\n".'<p'.$colors['Dungeons'].'>'.$param['Game']['MyDungeons'].'</p>'."\n".'<p'.$colors['Recruits'].'>'.$param['Game']['MyRecruits'].'</p>'."\n".'</div>'."\n".
			'<h5>'.htmlencode($param['Game']['PlayerName']).'<img width="18px" height="12px" src="img/flags/'.$mycountry.'.gif" alt="country flag" /></h5>'."\n".
			'<p class="info_label">Tower: <span'.$colors['Tower'].'>'.$param['Game']['MyTower'].'</span></p>'."\n".
			'<p class="info_label">Wall: <span'.$colors['Wall'].'>'.$param['Game']['MyWall'].'</span></p>'."\n".
			'</td>'."\n";
		}
		
		if ($minimize == "yes")	$content.= '<td></td>'."\n";
		else
		{
			$content.= '<td valign="bottom">'."\n";
			
			$content.= '<table cellpadding="0" cellspacing="0" summary="layout table">'."\n";
			$content.= '<tr>'."\n";
			$content.= '<td valign="bottom">'."\n";
			
			if ($param['Game']['MyTower'] == 100) $image = 'victory_red.png" width="65px" height="114px"';
			else $image = 'towera_red.png" width="65px" height="91px"';
			
			$content.= '<div style="margin: 0ex 1ex 0ex 1ex;">'."\n".
			'<img src="img/'.$image.' style="display:block;" alt="" />'."\n".
			'<div class="towerbody" style="margin-left: 14px; height: '.(170 *($param['Game']['MyTower']/100)).'px;"></div>'."\n".
			'</div>'."\n";
			
			$content.= '</td>'."\n";
			$content.= '<td valign="bottom">'."\n";
			
			if ($param['Game']['MyWall'] > 0)
				$content.= '<div>'."\n".
				'<img src="img/korunka.png" width="19px" height="11px" style="display:block" alt="" />'."\n".
				'<div class="wallbody" style="height: '.(270 * ($param['Game']['MyWall']/150)).'px;"></div>'."\n".
				'</div>'."\n";
			
			$content.= '</td>'."\n";
			$content.= '</tr>'."\n";
			$content.= '</table>'."\n";
			
			$content.= '</td>'."\n";
		}
		
		// display discarded cards at your turn
		if ((count($param['Game']['MyDisCards'][0]) == 0) and (count($param['Game']['MyDisCards'][1]) == 0)) $content.= '<td></td>'."\n";
		else
		{
			$content.= '<td align="center">'."\n";
			if ((count($param['Game']['MyDisCards'][0]) >=1) or (count($param['Game']['MyDisCards'][1]) >=1)) $content.= '<p class="info_label" style="font-size: small;">Discarded</p>';
			$content.= '<div class="history" style="width: 99px;">'."\n";//this strange width adjustment is here because Firefox need a one pixel wider div
			$content.= ' <table cellpadding="0" cellspacing="0">'."\n";
			$content.= '  <tr>'."\n";
			
						
			for ($j = 0; $j <=1; $j++)
			{
				for ($i = count($param['Game']['MyDisCards'][$j]); $i >= 1; $i--)
				{
					$border = '';
					if (($j == 0) and ($i == 1)) $border = ' style="border-right: thin solid yellow" ';
					elseif ((count($param['Game']['MyDisCards'][0]) == 0) and ($j == 1) and ($i == count($param['Game']['MyDisCards'][1]))) $border = ' style="border-left: thin solid yellow" ';
					
					$content.= '   <td align="center"'.$border.'>'."\n";
					$content.= $carddb->GetCard($param['Game']['MyDisCards'][$j][$i])->CardString($c_text, $c_img, $c_keywords, $c_oldlook);
					$content.= '   </td>'."\n";
				}
			}		
			
			$content.= '  </tr>'."\n";
			$content.= ' </table>'."\n";
			$content.= '</div>'."\n";
			$content.= '</td>'."\n";
		}
		
		// display your last played card(s)
		$content.= '<td align="center">'."\n";
		$content.= '<div class="history">'."\n";
		$content.= ' <table cellpadding="0" cellspacing="0">'."\n";
		$content.= '  <tr>'."\n";
		for ($i = count($param['Game']['MyLastCard']); $i >= 1; $i--)
		{
			$content.= '   <td align="center">'."\n";
			$content.= '    <p class="newcard_flag history_flag" style="color: ';
			if ($param['Game']['MyLastAction'][$i] == 'play') $content.= 'lime">PLAYED'; else $content.= 'red">DISCARDED!';
			if ($param['Game']['MyLastMode'][$i] != 0) $content.= ' mode '.$param['Game']['MyLastMode'][$i];
			$content.='</p>'."\n";
			$content.= $carddb->GetCard($param['Game']['MyLastCard'][$i])->CardString($c_text, $c_img, $c_keywords, $c_oldlook);
			$content.= '   </td>'."\n";
		}
		$content.= '  </tr>'."\n";
		$content.= ' </table>'."\n";
		$content.= '</div>'."\n";
		$content.= '</td>'."\n";
				
		// display opponent's last played card(s)
		$content.= '<td align="center">'."\n";
		$content.= '<div class="history">'."\n";
		$content.= ' <table cellpadding="0" cellspacing="0">'."\n";
		$content.= '  <tr>'."\n";
		for ($i = count($param['Game']['HisLastCard']); $i >= 1; $i--)
		{
			$content.= '   <td align="center">'."\n";
			$content.= '    <p  class="newcard_flag history_flag" style="color: ';
			if ($param['Game']['HisLastAction'][$i] == 'play') $content.= 'lime">PLAYED'; else $content.= 'red">DISCARDED!';
			if ($param['Game']['HisLastMode'][$i] != 0) $content.= ' mode '.$param['Game']['HisLastMode'][$i];
			$content.='</p>'."\n";
			$content.= $carddb->GetCard($param['Game']['HisLastCard'][$i])->CardString($c_text, $c_img, $c_keywords, $c_oldlook);
			$content.= '   </td>'."\n";
		}
		$content.= '  </tr>'."\n";
		$content.= ' </table>'."\n";
		$content.= '</div>'."\n";
		$content.= '</td>'."\n";
		
		//display opponent's discarded cards
		if ((count($param['Game']['HisDisCards'][0]) == 0) and (count($param['Game']['HisDisCards'][1]) == 0)) $content.= '<td></td>'."\n";
		else
		{
			$content.= '<td align="center">'."\n";
			if ((count($param['Game']['HisDisCards'][0]) >=1) or (count($param['Game']['HisDisCards'][1]) >=1)) $content.= '<p class="info_label" style="font-size: small;">Discarded</p>';
			$content.= '<div class="history" style="width: 99px;">'."\n";//this strange width adjustment is here because Firefox need a one pixel wider div
			$content.= ' <table cellpadding="0" cellspacing="0">'."\n";
			$content.= '  <tr>'."\n";
			
						
			for ($j = 1; $j >=0; $j--)
			{
				for ($i = count($param['Game']['HisDisCards'][$j]); $i >= 1; $i--)
				{
					$border = '';
					if (($j == 1) and ($i == 1)) $border = ' style="border-right: thin solid yellow" ';
					elseif ((count($param['Game']['HisDisCards'][1]) == 0) and ($j == 0) and ($i == count($param['Game']['HisDisCards'][0]))) $border = ' style="border-left: thin solid yellow" ';
					
					$content.= '   <td align="center"'.$border.'>'."\n";
					$content.= $carddb->GetCard($param['Game']['HisDisCards'][$j][$i])->CardString($c_text, $c_img, $c_keywords, $c_oldlook);
					$content.= '   </td>'."\n";
				}
			}		
			
			$content.= '  </tr>'."\n";
			$content.= ' </table>'."\n";
			$content.= '</div>'."\n";
			$content.= '</td>'."\n";
		}
		
		// opponent's resources and tower
		
		$colors = $param['Game']['hiscolors'];
		
		if ($minimize == "yes")	$content.= '<td></td>'."\n";
		else
		{
			$content.= '<td valign="bottom">'."\n";
			
			$content.= '<table cellpadding="0" cellspacing="0" summary="layout table">'."\n";
			$content.= '<tr>'."\n";
			$content.= '<td valign="bottom">'."\n";
			
			if ($param['Game']['HisWall'] > 0)
				$content.= '<div>'."\n".
				'<img src="img/korunka.png" width="19px" height="11px" style="display:block" alt="" />'."\n".
				'<div class="wallbody" style="height: '.(270 * ($param['Game']['HisWall']/150)).'px;"></div>'."\n".
				'</div>'."\n";
			
			$content.= '</td>'."\n";
			$content.= '<td valign="bottom">'."\n";
			
			if ($param['Game']['HisTower'] == 100) $image = 'victory_blue.png" width="65px" height="114px"';
			else $image = 'towera_blue.png" width="65px" height="91px"';
			
			$content.= '<div style="margin: 0ex 1ex 0ex 1ex;">'."\n".
			'<img src="img/'.$image.' style="display:block;" alt="" />'."\n".
			'<div class="towerbody" style="margin-left: 14px; height: '.(170 *($param['Game']['HisTower']/100)).'px;"></div>'."\n".
			'</div>'."\n";
			
			$content.= '</td>'."\n";
			$content.= '</tr>'."\n";
			$content.= '</table>'."\n";
			
			$content.= '</td>'."\n";
		}
		
		if ($minimize == "yes")
		{
			$content.=
			'<td class="minstats">'."\n".
			'<div>Quarry: <span'.$colors['Quarry'].'>'.$param['Game']['HisQuarry'].'</span></div>'."\n".
			'<div>Bricks: <span'.$colors['Bricks'].'>'.$param['Game']['HisBricks'].'</span></div>'."\n".
			'<div>Magic: <span'.$colors['Magic'].'>'.$param['Game']['HisMagic'].'</span></div>'."\n".
			'<div>Gems: <span'.$colors['Gems'].'>'.$param['Game']['HisGems'].'</span></div>'."\n".
			'<div>Dungeon: <span'.$colors['Dungeons'].'>'.$param['Game']['HisDungeons'].'</span></div>'."\n".
			'<div>Recruits: <span'.$colors['Recruits'].'>'.$param['Game']['HisRecruits'].'</span></div>'."\n".
			'<h5><img width="18px" height="12px" src="img/flags/'.$hiscountry.'.gif" alt="country flag" />'.htmlencode($param['Game']['OpponentName']).'<input class="details" type = "submit" name = "user_details['.postencode($param['Game']['OpponentName']).']" value = "i" /></h5>'."\n".
			'<p class="info_label">Tower: <span'.$colors['Tower'].'>'.$param['Game']['HisTower'].'</span></p>'."\n".
			'<p class="info_label">Wall: <span'.$colors['Wall'].'>'.$param['Game']['HisWall'].'</span></p>'."\n".
			'</td>'."\n";
		}
		else
		{
			$content.=	
			'<td class="stats" align="right">'."\n".
			'<div>'."\n".'<p'.$colors['Quarry'].'>'.$param['Game']['HisQuarry'].'</p>'."\n".'<p'.$colors['Bricks'].'>'.$param['Game']['HisBricks'].'</p>'."\n".'</div>'."\n".
			'<div>'."\n".'<p'.$colors['Magic'].'>'.$param['Game']['HisMagic'].'</p>'."\n".'<p'.$colors['Gems'].'>'.$param['Game']['HisGems'].'</p>'."\n".'</div>'."\n".
			'<div>'."\n".'<p'.$colors['Dungeons'].'>'.$param['Game']['HisDungeons'].'</p>'."\n".'<p'.$colors['Recruits'].'>'.$param['Game']['HisRecruits'].'</p>'."\n".'</div>'."\n".
			'<h5><img width="18px" height="12px" src="img/flags/'.$hiscountry.'.gif" alt="country flag" />'.htmlencode($param['Game']['OpponentName']).'<input class="details" type = "submit" name = "user_details['.postencode($param['Game']['OpponentName']).']" value = "i" /></h5>'."\n".
			'<p class="info_label">Tower: <span'.$colors['Tower'].'>'.$param['Game']['HisTower'].'</span></p>'."\n".
			'<p class="info_label">Wall: <span'.$colors['Wall'].'>'.$param['Game']['HisWall'].'</span></p>'."\n".
			'</td>'."\n";
		}
				
		$content.= '</tr>'."\n";
		// end: status
		
		// opponent's cards
		$content.= '<tr valign="top">'."\n";
		
		for($i = 1; $i <= 8; $i++)
		{
			$cardid = $param['Game']['HisHand'][$i];
			$card = $carddb->GetCard($cardid);
			
			$content.= '<td align="center">'."\n";
			
			// display new card indicator, if set
			if (isset($param['Game']['HisNewCards'][$i])) $content.= '<p class="newcard_flag">NEW CARD</p>'."\n";
			
			// display card
			$content.= $card->CardString($c_text, $c_img, $c_keywords, $c_oldlook);
			
			$content.= '</td>'."\n";
		}
		
		$content.= '</tr>'."\n";
		// end: opponent's cards
		
		
		//chatboard
		$content.= '<tr>'."\n";
		$content.= '<td colspan="8" align="center">'."\n";
		
		$content.= '<div class="chatsection">'."\n";
		
		$display_avatar = $param['Game']['display_avatar'];
		$correction = $param['Game']['correction'];
		
		if (($display_avatar  == "yes") AND ($correction == "no"))
		{
			$content.= "\t".'<img style="float: left;	margin: 0.5ex 0ex 0ex 0ex;" class="avatar" height="60px" width="60px" src="img/avatars/'.htmlencode($param['Game']['myavatar']).'" alt="avatar" />'."\n";
			
			$content.= "\t".'<img style="float: right; margin: 0.5ex 0ex 0ex 0ex;" class="avatar" height="60px" width="60px" src="img/avatars/'.htmlencode($param['Game']['hisavatar']).'" alt="avatar" />'."\n";
		}
		
		$messagelist = $param['Game']['messagelist'];
		if ($messagelist != NULL)
		{
			$content.= '<div class="chatbox">'."\n";
			$messagecount = count($messagelist);

			$order = $param['Game']['Chatorder'];
			$timezone = $param['Game']['Timezone'];
			
			for ($j = 1; $j <= $messagecount; $j++)
			{
				if ( $order == "yes")
					$i = $messagecount - $j + 1;
				else
					$i = $j;
				
				$name = $messagelist[$i]['Name'];
				$message = $messagelist[$i]['Message'];
				$time = $messagelist[$i]['Timestamp'];
				$color = ($name == $param['Game']['PlayerName']) ? $all_colors["LightBlue"] : (($name == $param['Game']['OpponentName']) ? $all_colors["LightGreen"] : 'red');
				
				//recalculate time to players perspective
				$offset = abs($timezone);
				$sign = ($timezone > 0) ? '-' : (($timezone < 0) ? '+' : '');
				$date = ZoneTime($time, "Etc/GMT".$sign.$offset, "G:i:s | d-M");
				
				$content.= '<p>'."\n";
				$content.= '<span>'.$date."</span>"."\n";
				$content.= '<span style="color : '.$color.'">'.htmlencode($name).' : </span>'."\n";
				$content.= '<span>'.htmlencode($message).'</span>'."\n";
				$content.= '</p>'."\n";
			}
			$content.= '</div>'."\n";
		}
		else
			$content.= '<div style ="margin-bottom: 0.5ex"></div>'."\n";
		
		if (($display_avatar  == "yes") AND ($correction == "yes"))
		{
			$content.= '<img style="float: left;  margin: 0.5ex 0ex 0ex 0ex;" class="avatar" height="60px" width="60px" src="img/avatars/'.$param['Game']['myavatar'].'" alt="avatar" />'."\n";
			
			$content.= '<img style="float: right; margin: 0.5ex 0ex 0ex 0ex;" class="avatar" height="60px" width="60px" src="img/avatars/'.$param['Game']['hisavatar'].'" alt="avatar" />'."\n";
		}
		
		if ($param['Game']['chat'])
		{
			$content.= '<input class="text_data chatboard" type="text" name="ChatMessage" size="115" maxlength="300" style="font-size: normal; margin-right: 2ex;" tabindex="1" accesskey="a" />'."\n";
			$content.= '<input type="submit" name="send_message" value="Send message" tabindex="2" accesskey="s" />'."\n";
		}
		$content.= '<div style="clear: both"></div>'."\n";
		$content.= '</div>'."\n";
		$content.= 
		"\n".'</td>'."\n".
		"\n".'</tr>'."\n";
		//end chatboard
		
		$content.= '</table>'."\n";
		
		return $content;
	}
	
	function Generate_Deck_view(array $param)
	{
		$content = "";
		$all_colors = $param['Colors'];
		
		global $carddb;
		
		$gameid = $param['Deck_view']['CurrentGame'];
				
		$c_text = $param['Deck_view']['c_text'];
		$c_img = $param['Deck_view']['c_img'];
		$c_keywords = $param['Deck_view']['c_keywords'];
		$c_oldlook = $param['Deck_view']['c_oldlook'];
		
		// remember the current location across pages
		$content.= '<input type = "hidden" name = "CurrentGame" value = "'.$gameid.'"/>'."\n";
		
		$content.= '<div style="text-align: center"><input type = "submit" name="view_game['.$gameid.']" value="Back to game"/></div>'."\n";
		
		$content.= '<table class="deck" cellpadding="0" cellspacing="0" >'."\n";
		
		$content.= '<tr>'."\n";
			foreach (array('Common'=>'Lime', 'Uncommon'=>$all_colors["DarkRed"], 'Rare'=>'Yellow') as $class => $classcolor)
			{
				$content.= '<th>'."\n";
				// section text
				$content.= '<p style="color: '.$classcolor.';">'.$class.' Cards</p>';
				$content.= '</th>'."\n";
			}
		$content.= '</tr>'."\n";
		
		$content.= '<tr>'."\n";
		
		foreach (array('Common'=>'Lime', 'Uncommon'=>$all_colors["DarkRed"], 'Rare'=>'Yellow') as $class => $classcolor)
		{
			$content.= '<td>'."\n";
			
			$content.= '<table class="centered" cellpadding="0" cellspacing="0">'."\n";
			$content.= '<tr>'."\n";

			foreach ($param['Deck_view']['deck'][$class] as $index => $cardid)
			{
				$content.= '<td>'."\n";
				
				$card = $carddb->GetCard($cardid);
				
				$content.= $card->CardString($c_text, $c_img, $c_keywords, $c_oldlook);
				
				$content.= '</td>'."\n";
				
				if ((($index % 3) == 0) && ($index != 15)) $content.= '</tr>'."\n".'<tr>'."\n"; //next row
				
			}
			$content.= '</tr>'."\n";
			$content.= '</table>'."\n";
			
			$content.= '</td>'."\n";
		}
		$content.= '</tr>'."\n";
		
		$content.= '</table>'."\n";
		
		return $content;
	}
	
	function Generate_Page(array $param)
	{
		$content = "";
			
		// remember the current location across pages
		$content.= '<input type = "hidden" name = "CurrentPage" value = "'.$param['Page']['selected'].'"/>'."\n";
		
		$content.= '<div id="webpage">'."\n";
		
		$content.= '<div id="webpg_float_left">'."\n";
		$content.= '<div>'."\n";
		$content.= '<input type="submit" name="WebPage[Main]" value="Main page" />'."\n";
		$content.= '<input type="submit" name="WebPage[News]" value="Latest news" />'."\n";
		$content.= '<input type="submit" name="WebPage[Cardmod]" value="Modified cards" />'."\n";
		$content.= '<input type="submit" name="WebPage[Help]" value="Game manual" />'."\n";
		$content.= '<input type="submit" name="WebPage[Faq]" value="F .   A .   Q . " />'."\n";
		$content.= '<input type="submit" name="WebPage[Credits]" value="Hall of fame" />'."\n";
		$content.= '</div>'."\n";	
		$content.= '</div>'."\n";
		
		$content.= '<div id="webpg_float_right">'."\n";
		$content.= '<div>'."\n";
		
		$content.= $param['Page']['html'];
				
		$content.= "\n".'</div>'."\n";			
		$content.= '</div>'."\n";
						
		$content.= '</div>'."\n";
		$content.= '<div class="clear_floats"></div>';
		
		return $content;
	}
	
	function Generate_Decks(array $param)
	{
		$content = "";
			
		$content.= '<div id="decks">'."\n";
		
		$list = $param['Decks']['list'];
		foreach ($list as $deckname)
		{
			$content.= '<div>'."\n";
			$content.= '<input type="submit" name="modify_deck['.postencode($deckname).']" value="'.htmlencode($deckname).'" />'."\n";
			$content.= '</div>'."\n";
		}
		
		$content.= '</div>'."\n";
		
		return $content;
	}
	
	function Generate_Players(array $param)
	{
		$content = "";
		
		// get the list of all existing players; the contents are a numbered array of (Username, Wins, Losses, Draws, Last Query)
		$list = $param['Players']['list'];
		
		//retrieve layout setting
		$show_nationality = $param['Players']['show_nationality'];
		
		$show["Online"] = $param['Players']['Online'];
		$show["Offline"] = $param['Players']['Offline'];
		$show["Inactive"] = $param['Players']['Inactive'];
		$show["Dead"] = $param['Players']['Dead'];
		
		//if there is at least one group of avatars displayed, create column
		$avatar_col = (($show["Online"] == "yes") OR ($show["Offline"] == "yes")  OR ($show["Inactive"] == "yes") OR ($show["Dead"] == "yes"));
		if (!$param['Players']['active']) $content.= '<p class="information_line" style = "color: yellow;">You need at least one ready deck to challenge other players.</p>'."\n";
		
		$pendinggames = $param['Players']['pendinggames'];
		
		if ($pendinggames >= MAX_GAMES) $content.= '<p class="information_trans" style = "color: yellow;">You cannot initiate any more games.</p>'."\n";
		$content.= '<div id="players">'."\n";
		
		$content.= '<div class="filters_trans" style="text-align: center;">'."\n";
		
		// begin player filter
		$content.= '<select name="player_filter"'.(($param['Players']['CurrentFilter'] != "none") ? ' style="border-color: lime;" ' : '').'>'."\n";
		$content.= "\t".'<option value="none" >No players filters</option>'."\n";
		$content.= "\t".'<option value="active" '.(($param['Players']['CurrentFilter'] == "active") ? ' selected="selected" ' : '').'>Active players</option>'."\n";
		$content.= "\t".'<option value="offline" '.(($param['Players']['CurrentFilter'] == "offline") ? ' selected="selected" ' : '').'>Active and offline players</option>'."\n";
		$content.= '</select>'."\n";
		
		$content.= '<input type = "submit" name = "filter_players" value = "&lt;" />'."\n";
		// end player filter
		
		$content.= '</div>'."\n";
		
		// begin ordering
		$order = $param['Players']['order'];
		$condition = $param['Players']['condition'];
		$bname = $param['Players']['bname'];
		$val = $param['Players']['val'];
		// end ordering	
		
		$content.= '<table class="centered" cellspacing="0">'."\n";
		
		$content.= '<tr>'."\n";
		if ($avatar_col) $content.= '<th></th>'."\n";
		if ($show_nationality == "yes") $content.= '<th></th>'."\n";
		$content.= '<th><p>Flag<input type = "submit" class="details" '.(($condition == "Country") ? ' style="border-color: lime;" ' : '').'name = "players_ord_'.$bname["Country"].'['.postencode("Country").']" value = "'.$val["Country"].'" /></p></th>'."\n";
		$content.= '<th><p>Username<input type = "submit" class="details" '.(($condition == "Username") ? ' style="border-color: lime;" ' : '').'name = "players_ord_'.$bname["Username"].'['.postencode("Username").']" value = "'.$val["Username"].'" /></p></th>'."\n";
		$content.= '<th><p>Wins<input type = "submit" class="details" '.(($condition == "Rank2") ? ' style="border-color: lime;" ' : '').'name = "players_ord_'.$bname["Rank2"].'['.postencode("Rank2").']" value = "'.$val["Rank2"].'" /></p></th>'."\n";
		$content.= '<th><p>Losses</p></th>'."\n";
		$content.= '<th><p>Draws</p></th>'."\n";
		$content.= '<th><p>Free slots<input type = "submit" class="details" '.(($condition == "Free slots") ? ' style="border-color: lime;" ' : '').'name = "players_ord_'.$bname["Free slots"].'['.postencode("Free slots").']" value = "'.$val["Free slots"].'" /></p></th>'."\n";
		$content.= '<th></th>'."\n";
		$content.= '</tr>'."\n";
		
		
		// for each player, display their name, score, and if conditions are met, also display the challenge button
		foreach ($list as $i => $data)
		{		
			$content.= '<tr class="table_row" align="center">'."\n";
			
			$opponent = $data['Username'];
			$player_type = $param['Players'][$opponent]['player_type'];
			$namecolor = $param['Players'][$opponent]['namecolor'];
			$country = $data['Country'];
			
			$challenged = $param['Players'][$opponent]['challenged'];
			$playingagainst = $param['Players'][$opponent]['playingagainst'];
			$waitingforack = $param['Players'][$opponent]['waitingforack'];
			
			if ($show[$player_type] =="yes")
			{
				if ($data['Avatar'] != "noavatar.jpg")
					$content.= '<td><img class="avatar" height="60px" width="60px" src="img/avatars/'.htmlencode($data['Avatar']).'" alt="avatar" /></td>'."\n";
				else
					$content.= '<td></td>'."\n";
			}
			elseif ($avatar_col) $content.= '<td></td>'."\n";
			
			$challenge_ok = (!$waitingforack AND !$playingagainst AND !$challenged);
			$player_ok = (($opponent != $param['Players']['PlayerName']) AND ($param['Players']['active']) AND ($pendinggames < MAX_GAMES));
			// display challenge button when all conditions are set
			$challenge_button = "";
			
			if ($challenge_ok AND $player_ok) $challenge_button = '<input class="details" type = "submit" name = "prepare_challenge['.postencode($opponent).']" value = "Challenge" />'."\n";
			
			if ($show_nationality == "yes") $content.= '<td><p style="color: white">'.$country.'</p></td>'."\n";
			$content.= '<td><img width="18px" height="12px" src="img/flags/'.$country.'.gif" alt="country flag" /></td>'."\n";
			$content.= '<td><p '.$namecolor.'>'.htmlencode($data['Username']).'</p></td>'."\n";
			$content.= '<td><p>'.$data['Wins'].'</p></td>'."\n";
			$content.= '<td><p>'.$data['Losses'].'</p></td>'."\n";
			$content.= '<td><p>'.$data['Draws'].'</p></td>'."\n";
			$content.= '<td><p>'.((isset($data['Free slots'])) ? (MAX_GAMES - $data['Free slots']) : MAX_GAMES).'</p></td>'."\n";
			$content.= '<td style="text-align: left;">'."\n";
			$content.= '<input class="details" type = "submit" name = "user_details['.postencode($opponent).']" value = "i" />'."\n";
			if ($param['Players']['messages']) $content.= '<input class="details" type = "submit" name = "message_create['.postencode($opponent).']" value = "m" />'."\n";
			if ($param['Players']['send_challenges']) $content.= $challenge_button;
			$content.= '</td>'."\n";
			$content.= "\n";
			
			if ($opponent != $param['Players']['PlayerName'])
			{
				if ($waitingforack)
				{
					$content.= '<td><p style="color: blue;">game over, waiting for opponent</p></td>'."\n";
				}
				elseif ($playingagainst)
				{
					$content.= '<td><p>game already in progress</p></td>'."\n";
				}
				elseif ($challenged)
				{
					$content.= '<td><p style="color: red;">waiting for answer</p></td>'."\n";
				}
				else $content.= '<td></td>'."\n";
			}
			else $content.= '<td></td>'."\n";
			
			$content.= '</tr>'."\n";
		}
		
		$content.= '</table>'."\n";
		
		if ($param['Players']['CurrentFilter'] != "") $content.= '<input type ="hidden" name="CurrentFilter" value="'.$param['Players']['CurrentFilter'].'" />'."\n";
		
		$content.= '</div>'."\n";
		
		return $content;
	}
	
	function Generate_Details(array $param)
	{
		$content = "";
		$all_colors = $param['Colors'];
				
		$challenged = $param['Details']['challenged'];
		$playingagainst = $param['Details']['playingagainst'];
		$waitingforack = $param['Details']['waitingforack'];
		$opponent = $param['Details']['PlayerName'];
		$pendinggames = $param['Details']['pendinggames'];
				
		$decks = $param['Details']['decks'];
		$activedecks = count($decks);
						
		$content.= '<div id="details">'."\n";
		$content.= '<div>'."\n";
		
		$content.= "\t".'<h3>'.htmlencode($param['Details']['PlayerName']).'\'s details</h3>'."\n";
		
		$content.= "\t".'<div class="details_float_right">'."\n";
		$content.= "\t"."\t".'<p>Zodiac sign</p>'."\n";
		$content.= "\t"."\t".'<img height="100px" width="100px" src="img/zodiac/'.$param['Details']['current_settings']['Sign'].'.jpg" alt="sign" />'."\n";
		$content.= "\t"."\t".'<p>'.$param['Details']['current_settings']['Sign'].'</p>'."\n";
		$content.= "\t".'</div>'."\n";
		
		$content.= "\t".'<div class="details_float_right">'."\n";
		$content.= "\t"."\t".'<p>Avatar</p>'."\n";
		$content.= "\t"."\t".'<img height="60px" width="60px" src="img/avatars/'.htmlencode($param['Details']['current_settings']['Avatar']).'" alt="avatar" />'."\n";
		$content.= "\t".'</div>'."\n";
		
		$content.= "\t".'<p>First name: <span class="detail_value">'.htmlencode($param['Details']['current_settings']['Firstname']).'</span></p>'."\n";
		$content.= "\t".'<p>Surname: <span class="detail_value">'.htmlencode($param['Details']['current_settings']['Surname']).'</span></p>'."\n";
		
		if ($param['Details']['current_settings']['Gender'] == "male") $temp_color = $all_colors["DeepSkyBlue"];
		elseif ($param['Details']['current_settings']['Gender'] == "female") $temp_color = $all_colors["HotPink"];
		else $temp_color = "lime";
		
		$content.= "\t".'<p>Gender: <span style="color: '.$temp_color.'">'.$param['Details']['current_settings']['Gender'].'</span></p>'."\n";
		
		$content.= "\t".'<p>E-mail: <span class="detail_value">'.htmlencode($param['Details']['current_settings']['Email']).'</span></p>'."\n";
		
		$content.= "\t".'<p>ICQ / IM number: <span class="detail_value">'.htmlencode($param['Details']['current_settings']['Imnumber']).'</span></p>'."\n";
				
		$content.= "\t".'<p>Date of birth (DD-MM-YYYY): <span class="detail_value">'.$param['Details']['current_settings']['Birthdate'].'</span></p>'."\n";
		
		$content.= "\t".'<p>Age: <span class="detail_value">'.$param['Details']['current_settings']['Age'].'</span></p>'."\n";
		$content.= "\t".'<p>Rank: <span class="detail_value">'.$param['Details']['PlayerType'].'</span></p>'."\n";
		$content.= "\t".'<p>Country: <img width="18px" height="12px" src="img/flags/'.$param['Details']['current_settings']['Country'].'.gif" alt="country flag" /> <span class="detail_value">'.$param['Details']['current_settings']['Country'].'</span></p>'."\n";
		
		$content.= "\t".'<p>Hobbies, Interests:</p>'."\n";
		
		$content.= "\t".'<p class="detail_value" style="max-width: 30ex;">'.textencode($param['Details']['current_settings']['Hobby']).'</p>'."\n";
		
		// check if the player is allowed to challenge this opponent:
		// - can't have more than MAX_GAMES active games + initiated challenges + received challenges
		// - can't be in the $challengefrom['Player2'] or in the $activegames['Player1'] (['Player2'] is allowed)
		// - can't play without a ready deck
		// - can't challenge self				
		if (($opponent != $param['Details']['CurPlayerName']) AND ($param['Details']['send_challenges']))
		{
			$content.= "\t".'<h4>Challenge options</h4>'."\n";
		
			if ($waitingforack)
			{
				$content.= '<p style="color: blue;">game over, waiting for opponent</p>'."\n";
			}
			elseif ($playingagainst)
			{
				$content.= '<p style="color: lime;">game already in progress</p>'."\n";
			}
			elseif ($challenged)
			{
				$challenge_text = $param['Details']['challenge']['Content'];
				$timezone = $param['Details']['Timezone'];
				
				//recalculate time to players perspective
				$time = strtotime($param['Details']['challenge']['Created']);
				$offset = abs($timezone);
				$sign = ($timezone > 0) ? '-' : (($timezone < 0) ? '+' : '');
				$challenge_time = ZoneTime($time, "Etc/GMT".$sign.$offset, "G:i:s | F j, y");
				
				$content.= '<p>'."\n";
				$content.= '<span style="color: red;">waiting for answer</span>'."\n";
				$content.= '<input type = "submit" name = "withdraw_challenge['.postencode($opponent).']" value = "Cancel" />'."\n";
				$content.= '</p>'."\n";
				if ($challenge_text != "") $content.= '<p class="challenge_text">'.textencode($challenge_text).'</p>'."\n";
				$content.= '<p style="color: lime">Challenged on '.$challenge_time.'</p>'."\n";
			}
			elseif ($activedecks > 0 and $pendinggames < MAX_GAMES)
			{
				if (!isset($_POST['prepare_challenge']) or postdecode(array_shift(array_keys($_POST['prepare_challenge']))) != $opponent)
				{
					$content.= '<p><input type = "submit" name = "prepare_challenge['.postencode($opponent).']" value = "Challenge this user" /></p>'."\n";
				}
				else
				{
					$content.= '<p>'."\n";
				
					$content.= '<input type = "submit" name = "send_challenge['.postencode($opponent).']" value = "Send challenge" />'."\n";
					$content.= "\n".'<select name = "ChallengeDeck" size = "1">'."\n";
					foreach ($decks as $deckname) $content.= '<option value = "'.postencode($deckname).'">'.htmlencode($deckname).'</option>'."\n";
					$content.= '</select>'."\n";
					
					$content.= '<textarea class="challenge_text" name="Content" rows="10" cols="50"></textarea>'."\n";
					
					$content.= '</p>'."\n";
				}
			}
			elseif ($activedecks == 0)
			{
				$content.= '<p class="information_line" style = "color: yellow;">You need at least one ready deck to challenge other players.</p>'."\n";
			}
			elseif ($pendinggames >= MAX_GAMES)
			{
				$content.= '<p style = "color: yellow;">You cannot initiate any more games.</p>'."\n";
			}
		}
		if ($param['Details']['messages'])
		{
			$content.= "\t".'<h4>Message options</h4>'."\n";
			
			$content.= "\t".'<input type = "submit" name = "message_create['.postencode($opponent).']" value = "Send message" />'."\n";
		}
		
		if (($param['Details']['change_rights']) AND ($param['Details']['PlayerType'] != "admin"))
		{
			$content.= "\t".'<h4>Change access rights</h4>'."\n";
			
			$user_types = array("moderator" => "Moderator", "user" => "User", "squashed" => "Squashed", "limited" => "Limited", "banned" => "Banned");
			
			$content.= "\t".'<input type = "submit" name = "change_access['.postencode($opponent).']" value = "Change access rights" />'."\n";			
			$content.= '<select name = "new_access" size="1">'."\n";
			
			foreach ($user_types as $type_name => $type_value)
				$content.= "\t".'<option value = "'.$type_name.'"'.(($param['Details']['PlayerType'] == $type_name) ? ' selected="selected" ' : '').'>'.$type_value.'</option>'."\n";
			
			$content.= '</select>'."\n";
		}
		
		if ($param['Details']['system_notification'])
		{
			$content.= "\t".'<h4>System notification</h4>'."\n";
						
			$content.= "\t".'<input type = "submit" name = "system_notification['.postencode($opponent).']" value = "Send system notification" />'."\n";
		}
		
		if ($param['Details']['change_all_avatar'])
		{
			$content.= "\t".'<h4>Reset avatar</h4>'."\n";
						
			$content.= "\t".'<input type = "submit" name = "reset_avatar_remote['.postencode($opponent).']" value = "Reset" />'."\n";
		}
		
		$content.= '</div>'."\n";
		$content.= '</div>'."\n";
		
		return $content;
	}
	
	function Generate_Challenges(array $param)
	{
		$content = "";
	
		$decks = $param['Challenges']['decks'];
		$timezone = $param['Challenges']['Timezone'];
		
		$content.= '<div id="message_section">'."\n";
		
		// begin challenges
		
		$content.= '<div id="challenges">'."\n";
		
		$content.= '<h3>Challenges</h3>'."\n";
		
		$numdecks = count($decks);
		if ($numdecks == 0) $content.= '<p style = "color: yellow;">You need at least one ready deck to accept challenges.</p>'."\n";
		
		$startedgames = $param['Challenges']['startedgames'];
		if ($startedgames >= MAX_GAMES) $content.= '<p style = "color: yellow;">You cannot start any more games.</p>'."\n";
		
		$challenges = $param['Challenges']['challenges'];
		$subsection = $param['Challenges']['current_subsection'];
		
		$target = ($subsection == "outgoing") ? 'Recipient' : 'Author';
		$col_name = ($subsection == "outgoing") ? 'To' : 'From';
		
		$incoming_color = ($subsection == "incoming") ? ' style="border-color: lime;" ' : '';
		$outgoing_color = ($subsection == "outgoing") ? ' style="border-color: lime;" ' : '';
		
		$content.= '<p>'."\n";
		$content.= '<input type = "submit" name = "incoming" value = "Incoming"'.$incoming_color.' />'."\n";
		$content.= '<input type = "submit" name = "outgoing" value = "Outgoing"'.$outgoing_color.' />'."\n";
		$content.= '</p>'."\n";
		
		if ($challenges)
		{
			$content.= '<div class="challenge_box">'."\n";
			
			foreach ($challenges as $challenge)
			{
				//recalculate time to players perspective
				$time = strtotime($challenge['Created']);
				$offset = abs($timezone);
				$sign = ($timezone > 0) ? '-' : (($timezone < 0) ? '+' : '');
				$date = ZoneTime($time, "Etc/GMT".$sign.$offset, "G:i:s | F j, Y");
			
				$opponent = $challenge[$target];
			
				$content.= '<div>'."\n";
				
				if ($subsection == "incoming")
				{
					$content.= '<p><span>'.htmlencode($opponent).'</span> has challenged you on <span>'.$date.'</span>.</p>'."\n";
					if ($challenge['Content'] != "") $content.= '<p class="challenge_content">'.textencode($challenge['Content']).'</p>'."\n";
					$content.= '<p>'."\n";
					
					if ($numdecks > 0 and $startedgames < MAX_GAMES)
					{
						if ($param['Challenges']['accept_challenges']) $content.= '<input type = "submit" name="accept_challenge['.postencode($opponent).']" value="Accept" />'."\n";
						$content.= '<select name = "AcceptDeck['.postencode($opponent).']" size="1">'."\n";
						foreach ($decks as $deckname) $content.= '<option value = "'.postencode($deckname).'">'.htmlencode($deckname).'</option>'."\n";
						$content.= '</select>'."\n";
					}
					$content.= '<input type = "submit" name="reject_challenge['.postencode($opponent).']" value="Reject" />'."\n";
					
					$content.= '</p>'."\n";
				}
				elseif ($subsection == "outgoing")
				{
					$content.= '<p>You challenged <span>'.htmlencode($opponent).'</span> on <span>'.$date.'</span>.</p>'."\n";
					if ($challenge['Content'] != "") $content.= '<p class="challenge_content">'.textencode($challenge['Content']).'</p>'."\n";
					$content.= '<p><input type = "submit" name = "withdraw_challenge2['.postencode($opponent).']" value = "Withdraw challenge" /></p>'."\n";
				}
				
				$content.= '</div>'."\n";
			}
			
			$content.= '</div>'."\n";
		}
		else
		{
			$content.= '<p>You have no '.$subsection.' challenges.</p>'."\n";
		}
		
		$content.= '</div>'."\n";
		
		// end challenges
		
		// begin messages
		
		$location = $param['Challenges']['current_location'];
		$filter = $param['Challenges']['current_filter'];
		$filter_val = $param['Challenges']['current_filter_val'];
		$order = $param['Challenges']['current_order'];
		$condition = $param['Challenges']['current_condition'];
		
		$sent_color = ($location == "sent_mail") ? ' style="border-color: lime;" ' : '';
		$inbox_color = ($location == "inbox") ? ' style="border-color: lime;" ' : '';
		$allmail_color = ($location == "all_mail") ? ' style="border-color: lime;" ' : '';
		$target = ($location == "sent_mail") ? 'Recipient' : 'Author';
		$col_name = ($location == "sent_mail") ? 'To' : 'From';
		
		$content.= '<div id="messages">'."\n";
		
		$content.= '<h3>Messages</h3>'."\n";
		
		$list = $param['Challenges']['messages'];
			
		$content.= '<div class="filters_trans" style="text-align: left;">'."\n";
		
		$content.= '<input type = "submit" name = "inbox" value = "Inbox"'.$inbox_color.' />'."\n";
		$content.= '<input type = "submit" name = "sent_mail" value = "Sent mail"'.$sent_color.' />'."\n";
		if ($param['Challenges']['see_all_messages']) $content.= '<input type = "submit" name = "all_mail" value = "All mail"'.$allmail_color.' />'."\n";
		
		// begin date filter
		$date_list = $param['Challenges']['timesections'];
										
		$content.= '<select name="date_filter"'.(($filter == "Created") ? ' style="border-color: lime;" ' : '').'>'."\n";
		foreach($date_list as $time_val => $time_name)
		{
			$content.= "\t".'<option value="'.$time_val.'" '.(($filter_val == $time_val) ? ' selected="selected" ' : '').'>'.$time_name.'</option>'."\n";
		}
		$content.= '</select>'."\n";
				
		$content.= '<input type = "submit" name = "message_filter_date" value = "&lt;" />'."\n";
		// end date filter
		
		// end buttons div when name filter isn't displayed
		if (!$list)	$content.= '</div>'."\n";
		
		if ($list)
		{
			// begin name filter
			$name_list = $param['Challenges']['name_filter'];
			
			if ($name_list)
			{								
				$content.= '<select name="name_filter"'.(($filter == "Name") ? ' style="border-color: lime;"' : '').'>'."\n";
				foreach($name_list as $index => $data)
				{
					$content.= "\t".'<option value="'.postencode($data[$target]).'"'.(($filter_val == $data[$target]) ? ' selected="selected" ' : '').' >'.htmlencode($data[$target]).'</option>'."\n";
				}
				$content.= '</select>'."\n";
						
				$content.= '<input type = "submit" name = "message_filter_name" value = "&lt;" />'."\n";
			}
			// end name filter
			
			// end buttons div
			$content.= '</div>'."\n";
					
			$name_ord = ($condition == $target) ? (($order == "ASC") ? 'desc' : 'asc') : 'asc';
			$date_ord = ($condition == "Created") ? (($order == "ASC") ? 'desc' : 'asc') : 'asc';
			
			$name_val = ($name_ord == 'asc') ? "\/" : "/\\";
			$date_val = ($date_ord == 'asc') ? "\/" : "/\\";	
					
			$content.= '<table cellspacing="0">'."\n";
			
			$content.= '<tr>'."\n";
			$content.= '<th><p>'.$col_name.'<input class="details" type = "submit" '.(($condition == $target) ? ' style="border-color: lime;" ' : '').'name = "mes_ord_'.$name_ord.'['.$target.']" value = "'.$name_val.'" /></p></th>'."\n";
			if ($location == "all_mail") $content.= '<th><p>To</p></th>'."\n";
			$content.= '<th><p>Subject</p></th>'."\n";
			$content.= '<th><p>Sent on<input class="details" type = "submit" '.(($condition == "Created") ? ' style="border-color: lime;" ' : '').'name = "mes_ord_'.$date_ord.'[Created]" value = "'.$date_val.'" /></p></th>'."\n";
			$content.= '<th></th>'."\n";
			$content.= '</tr>'."\n";
			
			$i = 0;
			foreach ($list as $data)
			{
				//recalculate time to players perspective
				$time = strtotime($data['Created']);
				$offset = abs($timezone);
				$sign = ($timezone > 0) ? '-' : (($timezone < 0) ? '+' : '');
				$date = ZoneTime($time, "Etc/GMT".$sign.$offset, "G:i:s | F j, y");
				
				$reply_button = (($location == "inbox") AND ($data[$target] != $param['PlayerName'])) ? '<input class="details" type = "submit" name = "message_create['.postencode($data['Author']).']" value = "R" />'."\n" : '';
				
				if ($location == "inbox") $row_color = (($data['Unread'] == "yes") ? (($param['PreviousLogin'] <= strtotime($data['Created'])) ? ' style="color: red;"' : ' style="color: orange;"') : (($data['Author'] == SYSTEM_NAME) ? ' style="color: #00bfff;"' : ''));
				else $row_color = "";
				
				$details_button = ($location == "all_mail") ? 'message_retrieve' : 'message_details';
				
				$content.= '<tr class="table_row"'.$row_color.'>'."\n";
				$content.= '<td><p>'.htmlencode($data[$target]).'</p></td>'."\n";
				if ($location == "all_mail") $content.= '<td><p>'.htmlencode($data['Recipient']).'</p></td>'."\n";
				$content.= '<td><p>'.htmlencode($data['Subject']).'</p></td>'."\n";
				$content.= '<td><p>'.$date.'</p></td>'."\n";
				$content.= '<td><p style="text-align: left;">'."\n";
				$content.= '<input class="details" type = "submit" name = "'.$details_button.'['.$data['MessageID'].']" value="+" />'."\n";
				if ($location != "all_mail") $content.= '<input class="details" type = "submit" name = "message_delete['.$data['MessageID'].']" value="D" />'."\n";
				if (($param['Challenges']['send_messages']) AND ($data['Author'] != SYSTEM_NAME)) $content.= $reply_button;
				$content.= '</p></td>';
				
				$content.= '</tr>'."\n";
			}
			
		$content.= '</table>'."\n";
		}
	
		$content.= '<input type ="hidden" name="CurrentLocation" value="'.$location.'" />'."\n";
		$content.= '<input type ="hidden" name="CurrentFilter" value="'.$filter.'" />'."\n";
		$content.= '<input type ="hidden" name="CurrentFilterVal" value="'.$filter_val.'" />'."\n";
		
		if ((!$list) AND $filter != "") $content.= '<p class="information_trans">No messages matched selected criteria.</p>'."\n";
		elseif ((!$list) AND $filter == "") $content.= '<p class="information_trans">You have no messages.</p>'."\n";
				
		$content.= '</div>'."\n";
				
		// end messages
				
		$content.= '<div class="clear_floats"></div>'."\n";
		
		$content.= '</div>'."\n";
		
		return $content;
	}
	
	function Generate_Message_details(array $param)
	{
		$content = "";
	
		$content.= '<div id="mes_details">'."\n";
		
		$content.= '<h3>Message details</h3>'."\n";
		
		$content.= '<div>'."\n";
		
		$content.= '<img class="stamp_picture" src="img/stamps/stamp'.$param['Message_details']['Stamp'].'.png" width="100px" height="100px" alt="Marcopost stamp" />'."\n";
		
		$content.= '<p><span>From:</span>'.htmlencode($param['Message_details']['Author']).'</p>'."\n";
		$content.= '<p><span>To:</span>'.htmlencode($param['Message_details']['Recipient']).'</p>'."\n";
		$content.= '<p><span>Subject:</span>'.htmlencode($param['Message_details']['Subject']).'</p>'."\n";
		$content.= '<p><span>Sent on:</span>'.$param['Message_details']['Created'].'</p>'."\n";
		
		$temp = '['.$param['Message_details']['MessageID'].']';
		$delete = ($param['Message_details']['delete']) ? 'name = "message_delete'.$temp.'" value = "Delete"'
		                                    : 'name = "message_delete_confirm'.$temp.'" value = "Confirm delete"';
		
		$reply = (($param['PlayerName'] != $param['Message_details']['Author']) AND ($param['PlayerName'] == $param['Message_details']['Recipient'])) ? "\t".'<input type = "submit" name = "message_create['.postencode($param['Message_details']['Author']).']" value="Reply" />'."\n" : '';
				
		$content.= '<p>'."\n";
		
		if (($param['Message_details']['messages']) AND ($param['Message_details']['Author'] != SYSTEM_NAME)) $content.= $reply;
		if ($param['Message_details']['current_location'] != "all_mail") $content.= "\t".'<input type = "submit" '.$delete.' />'."\n";
		$content.= "\t".'<input type = "submit" name = "message_cancel" value="Back" />'."\n";
		$content.= "\t".'</p>'."\n";
		$content.= '<hr/>'."\n";
		
		$content.= '<p>'.textencode($param['Message_details']['Content']).'</p>'."\n";
		
		$content.= '</div>'."\n";
		
		$content.= '<input type ="hidden" name="CurrentLocation" value="'.$param['Message_details']['current_location'].'" />'."\n";
		
		$content.= '</div>'."\n";
		
		return $content;
	}
	
	function Generate_Message_new(array $param)
	{
		$content = "";
	
		$content.= '<div id="mes_details">'."\n";
		
		$content.= '<h3>New message</h3>'."\n";
		
		$content.= '<div>'."\n";
		
		$content.= '<img class="stamp_picture" src="img/stamps/stamp0.png" width="100px" height="100px" alt="Marcopost stamp" />'."\n";
		
		$content.= '<p><span>From:</span>'.htmlencode($param['Message_new']['Author']).'</p>'."\n";
		$content.= '<p><span>To:</span>'.htmlencode($param['Message_new']['Recipient']).'</p>'."\n";
		$content.= '<p><span>Subject:</span><input class="text_data" type="text" name="Subject" maxlength="30" size="25" value="'.$param['Message_new']['Subject'].'" /></p>'."\n";
		$content.= '<input type = "submit" name = "message_send" value="Send" />'."\n";
		$content.= '<input type = "submit" name = "message_cancel" value="Discard" />'."\n";
		$content.= '<hr/>'."\n";
		
		$content.= '<textarea name="Content" rows="6" cols="50">'.htmlencode($param['Message_new']['Content']).'</textarea>'."\n";
		
		$content.= '</div>'."\n";
		
		$content.= '</div>'."\n";
		
		$content.= '<input type ="hidden" name="Author" value="'.htmlencode($param['Message_new']['Author']).'" />'."\n";
		$content.= '<input type ="hidden" name="Recipient" value="'.htmlencode($param['Message_new']['Recipient']).'" />'."\n";
		
		return $content;
	}
	
	function Generate_Games(array $param)
	{
		$content = "";
	
		$list = $param['Games']['list'];
		
		if (count($list) > 0)
		{
			$content.= '<div id="games">'."\n";
			
			foreach ($list as $i => $data)
			{							
				$opponent = $param['Games']['list'][$i]['opponent'];
				
				$active = ($param['Games']['list'][$i]['active']) ? ' style="border-color: lime;"' : '';
				$ready = ($param['Games']['list'][$i]['ready']) ? ' class="menuselected"' : '';
				$content.= '<div>'."\n".'<input'.$active.$ready.' type = "submit" name="view_game['.$param['Games']['list'][$i]['gameid'].']" value="vs. '.htmlencode($opponent).'"/>'."\n".'</div>'."\n";
				
				if ($param['Games']['list'][$i]['gamestate'] == 'in progress')
				{
					if ($param['Games']['list'][$i]['isdead']) $content.= '<p class="aborted_game" style="color: gray;">Game can be aborted</p>'."\n";
				}
				else
				{
					$content.= '<p class="ended_game">Game has ended</p>'."\n";
				}
				
			}
			
			$content.= '</div>'."\n";
		}
		else
		{
			$content.= '<p class="information_trans">You have no active games.</p>'."\n";
		}
		
		return $content;
	}
	
	function Generate_Novels(array $param)
	{
		$content = "";
	
		$current_novel = $param['Novels']['current_novel'];
		$current_chapter = $param['Novels']['current_chapter'];
		$current_page = $param['Novels']['current_page'];
		
		if ($current_novel != "") $content.= '<input type ="hidden" name="current_novel" value="'.$current_novel.'" />'."\n";
		if ($current_chapter != "") $content.= '<input type = "hidden" name = "current_chapter" value = "'.$current_chapter.'"/>'."\n";	
		if ($current_page != "") $content.= '<input type = "hidden" name = "current_page" value = "'.$current_page.'"/>'."\n";	
	
		$content.= '<div id="novels">'."\n";
		
		// begin novel menu
		
		$content.= '<div id="nov_float_left">'."\n";
		
		$content.= "\t".'<h3>Novels menu</h3>'."\n";
			
		$novelslist = $param['Novels']['novelslist'];
		
		if (count($novelslist > 0))
		{
			$content.= '<ul>'."\n";
			foreach($novelslist as $novelkey => $novelname)
			{
				if ($current_novel  == $novelname)
				{
					$content.= "\t".'<li><input type="submit" name="collapse_novel" value="&minus;" /><span class="novel_selected">'.$novelname.'</span>'."\n";
					
					$chapterslist = $param['Novels']['chapterslist'];
					if (count($chapterslist > 0))
					{
						$content.= "\t".'<ul>'."\n";
						foreach($chapterslist as $chapterkey => $chaptername)
						{
							$content.= "\t"."\t".'<li><input type="submit" name="view_chapter['.$chaptername.']" value=">" /><span'.(($chaptername == $current_chapter) ? ' class="chapter_selected"' : '').'>'.$chaptername.'</span></li>'."\n";
						}
						$content.= "\t".'</ul>'."\n";
					}
					$content.= "\t".'</li>'."\n";
				}
				else $content.= "\t".'<li><input type="submit" name="view_novel['.$novelname.']" value="+" />'.$novelname.'</li>'."\n";
				
			}
			$content.= '</ul>'."\n";
		}
				
		$content.= '</div>'."\n";	
		
		//end novel menu
				
		//begin novel content
		
		$content.= '<div id="nov_float_right">'."\n";
		
		if ($current_chapter == "")
		{		
			$content.= '<h3>Welcome to the Fantasy novels section</h3>'."\n";
			
			$content.= '<p>All novels are written by our external associate <a href="mailto:thomasteekanne@gmail.com">Lukáš Čajági</a>, and therefore all novels are exclusive property of the author. We will add new pieces of the novel once a week or so.</p>'."\n";
			$content.= '<br />'."\n";
			$content.= '<p>This novel will probably be a trilogy, the name is not known yet. The author provided us with description of the first book:</p>'."\n";
			$content.= '<p><b>"Only the fewest people in the world have the luxury of knowing who they really are, how hard it is to find something worth living for. Now try to imagine that upon finding it, it is snatched away from you forever. What would you do? Would you care to live on while the lives of everyone around you shatter? Could your consience bear the thought of you being responsible for the whole mess? This is the story of a man who has to cope with these questions."</b></p>'."\n";
			$content.= '<p><i>Trasymachos, former member of the Alchemist\'s Guild, outlawed fugitive</i></p>'."\n";
			$content.= '<br />'."\n";
			$content.= '<p>For feedback regarding novels we have created a <a href="https://netvor.sk/trac/arcomage/ticket/66#preview">ticket</a> on our bug-tracking system, until we have a forum.</p>'."\n";
			$content.= '<br />'."\n";
			$content.= '<p>Please select a book and chapter you wish to read.</p>'."\n";
			$content.= '<br />'."\n";
			$content.= '<p>Copyright 2008 Lukáš Čajági</p>'."\n";
		}
		else
		{
			$page_list = $param['Novels']['ListPages'];
			$num_pages = count($page_list);
			
			$content.= '<div class="navigation">'."\n";
			
			if ($current_page > 0) $content.= '<input class="previous" type="submit" name="select_page['.($current_page - 1).']" value="Previous" />'."\n";
			if ($current_page < ($num_pages - 1)) $content.= '<input class="next" type="submit" name="select_page['.($current_page + 1).']" value="Next" />'."\n";
			
			$content.= '<select name="jump_to_page1">'."\n";
			foreach ($page_list as $page => $page_name)
				$content.= "\t".'<option value="'.$page.'"'.(($current_page == $page) ? ' selected="selected" ' : '').'>'.$page_name.'</option>'."\n";
			$content.= '</select>'."\n";
			
			$content.= '<input type="submit" name="Jump1" value="Select page" />'."\n";
			
			$content.= '</div>'."\n";
			
			$content.= $param['Novels']['PageContent'];
			
			$content.= '<div class="navigation">'."\n";
			
			if ($current_page > 0) $content.= '<input class="previous" type="submit" name="select_page['.($current_page - 1).']" value="Previous" />'."\n";
			if ($current_page < ($num_pages - 1)) $content.= '<input class="next" type="submit" name="select_page['.($current_page + 1).']" value="Next" />'."\n";
			
			$content.= '<select name="jump_to_page2">'."\n";
			foreach ($page_list as $page => $page_name)
				$content.= "\t".'<option value="'.$page.'"'.(($current_page == $page) ? ' selected="selected" ' : '').'>'.$page_name.'</option>'."\n";
			$content.= '</select>'."\n";
			
			$content.= '<input type="submit" name="Jump2" value="Select page" />'."\n";
			
			$content.= '</div>'."\n";
		}
		
		$content.= '</div>'."\n";
		
		//end novel content
		
		$content.= '<div class="clear_floats"></div>'."\n";
		
		$content.= '</div>'."\n";
		
		return $content;
	}
	
	function Generate_Settings(array $param)
	{
		$content = "";
		
		$current_settings = $param['Settings']['current_settings'];
		$countries = $param['Settings']['countries'];
		$timezones = $param['Settings']['timezones'];
		
		$content.= '<div id="settings">'."\n";
		
		//begin user settings
		$content.= '<div id="sett_float_left">'."\n";
		$content.= '<div>'."\n";
		
		$content.= '<h3>Profile settings</h3>'."\n";
		
		$content.= '<div><h4>Zodiac sign</h4><img height="100px" width="100px" src="img/zodiac/'.$current_settings['Sign'].'.jpg" alt="sign" /><h4>'.$current_settings['Sign'].'</h4></div>'."\n";
		
		$content.= '<div><h4>Avatar</h4><img height="60px" width="60px" src="img/avatars/'.htmlencode($current_settings['Avatar']).'" alt="avatar" /></div>'."\n";
		$content.= '<div>First name:<input class="text_data" type="text" name="Firstname" maxlength="20" value="'.$current_settings['Firstname'].'" /></div>'."\n";
		$content.= '<div>Surname:<input class="text_data" type="text" name="Surname" maxlength="20" value="'.$current_settings['Surname'].'" /></div>'."\n";
		
		$content.= '<div>Gender:'."\n";
		$content.= '<select name="Gender">'."\n";
		$content.= '<option value="" '.(($current_settings['Gender'] == "none") ? 'selected="selected"' : '').' >select</option>'."\n";
		$content.= '<option value="male" '.(($current_settings['Gender'] == "male") ? 'selected="selected"' : '').' >male</option>'."\n";
		$content.= '<option value="female" '.(($current_settings['Gender'] == "female") ? 'selected="selected"' : '').' >female</option>'."\n";
		$content.= '</select>'."\n";
		$content.= '</div>'."\n";
		
		$content.= '<div>E-mail:<input class="text_data" type="text" name="Email" maxlength="30" value="'.$current_settings['Email'].'" /></div>'."\n";
		
		$content.= '<div>ICQ / IM number:<input class="text_data" type="text" name="Imnumber" maxlength="20" value="'.$current_settings['Imnumber'].'" /></div>'."\n";
		
		if ($current_settings['Birthdate'] == "Unknown") $birthday = $birthmonth = $birthyear = "";
		else list($birthday, $birthmonth, $birthyear) = explode("-", $current_settings['Birthdate']);
				
		$content.= '<div>Date of birth (DD-MM-YYYY):'."\n";
		$content.= '<input class="text_data" type="text" name="Birthday" maxlength="2" size="2" value="'.$birthday.'"/>'."\n";
		$content.= '<input class="text_data" type="text" name="Birthmonth" maxlength="2" size="2" value="'.$birthmonth.'"/>'."\n";
		$content.= '<input class="text_data" type="text" name="Birthyear" maxlength="4" size="4" value="'.$birthyear.'"/>'."\n";
		$content.= '</div>'."\n";
		$content.= '<div>Age: '.$current_settings['Age'].'</div>'."\n";
		
		$content.= '<div>Rank: '.$param['Settings']['PlayerType'].'</div>'."\n";
		
		$content.= '<div>Choose your country: <img width="18px" height="12px" src="img/flags/'.$current_settings['Country'].'.gif" alt="country flag" />'."\n";
		
		$content.= '<select name="Country">'."\n";
		
		foreach($countries as $country => $value)
			$content.= '<option value="'.$value.'"'.(($current_settings['Country'] == $value) ? ' selected="selected" ' : '').'>'.$country.'</option>'."\n";
		
		$content.= '</select></div>'."\n";
		
		$content.= '<div>Hobbies, Interests:</div>'."\n";
		
		$content.= '<div><textarea name="Hobby" rows="5" cols="30">'.$current_settings['Hobby'].'</textarea></div>'."\n";
		
		$content.= '<div>Save user settings:<input type="submit" name="user_settings" value="Save" /></div>'."\n";
		
		if ($param['Settings']['change_own_avatar'])
		{		
			$content.= '<div>Upload avatar: <input name="uploadedfile" type="file" style="color: black; border-style: none;"/><input type="submit" name="Avatar" value="Upload" /></div>'."\n";
			
			$content.= '<div>Clear avatar:<input type="submit" name="reset_avatar" value="Reset" /></div>'."\n";
		}
		
		$content.= '</div>'."\n";
		$content.= '</div>'."\n";
		
		//end user settings
		
		//begin game settings
		$content.= '<div id="sett_float_right">'."\n";
		$content.= '<div>'."\n";
		
		$content.= '<h3>Account settings</h3>'."\n";
		$content.= '<div>New password:<input class="text_data" type="password" name="NewPassword" maxlength="20" /></div>'."\n";
		$content.= '<div>Confirm password:<input class="text_data" type="password" name="NewPassword2" maxlength="20" /></div>'."\n";
		$content.= '<div>Change password:<input type = "submit" name= "changepasswd" value= "Change" /></div>'."\n";
		
		$content.= '<div>Time zone:'."\n";
		$content.= '<select name="Timezone">'."\n";		
		foreach($timezones as $gmt => $name)
			$content.= '<option value="'.$gmt.'"'.(($current_settings['Timezone'] == $gmt) ? ' selected="selected" ' : '').'>GMT '.$gmt.' ('.$name.')</option>'."\n";
		$content.= '</select>'."\n";
		$content.= '</div>'."\n";
		
		$content.= '<h4>Layout options</h4>'."\n";
		
		$content.= '<div>'."\n";
		$content.= '<div><input type="checkbox" name="Minimize" '.(($current_settings['Minimize'] == "yes") ? ' checked="checked" ' : '').'/>Minimized game view</div>'."\n";
		
		$content.= '<div><input type="checkbox" name="Cardtext" '.(($current_settings['Cardtext'] == "yes") ? ' checked="checked" ' : '').'/>Show card text</div>'."\n";
		$content.= '<div><input type="checkbox" name="Images" '.(($current_settings['Images'] == "yes") ? ' checked="checked" ' : '').'/>Show card images</div>'."\n";
		$content.= '<div><input type="checkbox" name="Keywords" '.(($current_settings['Keywords'] == "yes") ? ' checked="checked" ' : '').'/>Show card keywords</div>'."\n";
		
		$content.= '<div><input type="checkbox" name="Online" '.(($current_settings['Online'] == "yes") ? ' checked="checked" ' : '').'/>Show avatar for on-line users</div>'."\n";
		$content.= '<div><input type="checkbox" name="Offline" '.(($current_settings['Offline'] == "yes") ? ' checked="checked" ' : '').'/>Show avatar for off-line users</div>'."\n";
		$content.= '<div><input type="checkbox" name="Inactive" '.(($current_settings['Inactive'] == "yes") ? ' checked="checked" ' : '').'/>Show avatar for inactive users</div>'."\n";
		$content.= '<div><input type="checkbox" name="Dead" '.(($current_settings['Dead'] == "yes") ? ' checked="checked" ' : '').'/>Show avatar for dead users</div>'."\n";
		$content.= '<div><input type="checkbox" name="Nationality" '.(($current_settings['Nationality'] == "yes") ? ' checked="checked" ' : '').'/>Show nationality in players list</div>'."\n";
				
		$content.= '<div><input type="checkbox" name="Chatorder" '.(($current_settings['Chatorder'] == "yes") ? ' checked="checked" ' : '').'/>Reverse chat message order</div>'."\n";
		
		$content.= '<div><input type="checkbox" name="Avatargame" '.(($current_settings['Avatargame'] == "yes") ? ' checked="checked" ' : '').'/>Show avatar in game</div>'."\n";

		$content.= '<div><input type="checkbox" name="Showdead" '.(($current_settings['Showdead'] == "yes") ? ' checked="checked" ' : '').'/>Show dead users in players list</div>'."\n";

		$content.= '<div><input type="checkbox" name="Correction" '.(($current_settings['Correction'] == "yes") ? ' checked="checked" ' : '').'/>Avatar display correction for chat (Firefox 2.x only)</div>'."\n";
		
		$content.= '<div><input type="checkbox" name="OldCardLook" '.(($current_settings['OldCardLook'] == "yes") ? ' checked="checked" ' : '').'/>Old card appearance</div>'."\n";
		
		$content.= '</div>'."\n";
		
		$content.= '<div>Save game settings:<input type="submit" name="game_settings" value="Save" /></div>'."\n";
		
		$content.= '</div>'."\n";
		$content.= '</div>'."\n";
		//end game settings
		
		$content.= '<div class="clear_floats"></div>'."\n";
		
		$content.= '</div>'."\n";
		
		return $content;
	}
	
	function Generate_Forum(array $param)
	{
		$content = "";
		
		$timezone = $param['Forum']['Timezone'];
		$list = $param['Forum']['sections'];
		
		$content.= '<div id="forum">'."\n";
		
		$content.= '<h3>MArcomage discussion forum</h3>'."\n";
		
		$content.= '<h4>Sections list</h4>'."\n";
		
		$content.= '<div>'."\n";
		
		$content.= '<table cellspacing="0" cellpadding="0">'."\n";
			
			$content.= '<tr>'."\n";
			$content.= '<th><p></p></th>'."\n";
			$content.= '<th><p>Topic</p></th>'."\n";
			$content.= '<th><p>Author</p></th>'."\n";
			$content.= '<th><p>Posts</p></th>'."\n";
			$content.= '<th><p>Created</p></th>'."\n";
			$content.= '<th><p>Last post</p></th>'."\n";
			$content.= '</tr>'."\n";
		
		if ($list)
		{
			foreach($list as $index => $data)
			{
				$content.= '<tr><td colspan="6">'."\n";
				$content.= '<div>'."\n";
				
				$content.= '<h5><input type = "submit" name="section_details['.$data['SectionID'].']" value="&crarr;" /><span>'.$data['SectionName'].'</span> ( '.(($data['count'] == null) ? 0 : $data['count']).' ) - '.$data['Description'].'</h5>'."\n";
				$content.= '<p></p>'."\n";
				$content.= '<div></div>'."\n";	
				$content.= '</div>'."\n";
						
				$content.= '</td></tr>'."\n";		
				
				if ($param['Forum']['threadlist'][$index])
				{
					foreach($param['Forum']['threadlist'][$index] as $thread_data)
					{
						$time = strtotime($thread_data['Created']);
						$offset = abs($timezone);
						$sign = ($timezone > 0) ? '-' : (($timezone < 0) ? '+' : '');
						$create_date = ZoneTime($time, "Etc/GMT".$sign.$offset, "G:i | j. F, Y");
						
						if ($thread_data['last_post'] != null)
						{
							$time = strtotime($thread_data['last_post']);
							$offset = abs($timezone);
							$sign = ($timezone > 0) ? '-' : (($timezone < 0) ? '+' : '');
							$post_date = ZoneTime($time, "Etc/GMT".$sign.$offset, "G:i | j. F, Y");
						}
											
						$style = (($thread_data['Priority'] == "sticky") ? ' style="color: red" ' : (($thread_data['Priority'] == "important") ? ' style="color: orange" ' : ''));						
						$locked = (($thread_data['Locked'] == "yes") ? ' (locked)' : '');					
						
						$content.= '<tr class="table_row">'."\n";
						$content.= '<td><p><input class="details" type = "submit" name="thread_details['.$thread_data['ThreadID'].']" value="+" /></p></td>'."\n";
						$content.= '<td><p'.$style.'>'.htmlencode($thread_data['Title']).$locked.'</p></td>'."\n";
						$content.= '<td><p>'.htmlencode($thread_data['Author']).'</p></td>'."\n";
						$content.= '<td><p>'.(($thread_data['post_count'] == null) ? 0 : $thread_data['post_count']).'</p></td>'."\n";
						$content.= '<td><p>'.$create_date.'</p></td>'."\n";
						$content.= '<td><p>'.(($thread_data['last_post'] == null) ? 'n/a' : $post_date." by ".htmlencode($thread_data['PostAuthor']).' <input class="details" type = "submit" name="thread_last_page['.$thread_data['ThreadID'].']" value="&rarr;" '.(($param['PreviousLogin'] < $time) ? ' style="border-color: red;" ' : "").'/>').'</p></td>'."\n";
						$content.= '</tr>'."\n";
					}
				}
			}
		}
		
		$content.= '</table>'."\n";
		
		$content.= '</div>'."\n";
		
		$content.= '</div>'."\n";
			
		return $content;
	}
	
	function Generate_Section_details(array $param)
	{
		$content = "";
		
		$timezone = $param['Section_details']['Timezone'];
		$list = $param['Section_details']['threadlist'];
		$section = $param['Section_details']['Section'];
		$pages = $param['Section_details']['Pages'];
		$current_page = $param['Section_details']['CurrentPage'];
		
		$content.= '<div id="forum">'."\n";
				
		$content.= '<h3>MArcomage discussion forum</h3>'."\n";
		
		$content.= '<h4>Section details</h4>'."\n";
				
		$content.= '<div>'."\n";
		
		$content.= '<table cellspacing="0" cellpadding="0">'."\n";
			
		$content.= '<tr>'."\n";
		$content.= '<th><p></p></th>'."\n";
		$content.= '<th><p>Topic</p></th>'."\n";
		$content.= '<th><p>Author</p></th>'."\n";
		$content.= '<th><p>Posts</p></th>'."\n";
		$content.= '<th><p>Created</p></th>'."\n";
		$content.= '<th><p>Last post</p></th>'."\n";
		$content.= '</tr>'."\n";
	
		$content.= '<tr><td colspan="6">'."\n";
		$content.= '<div>'."\n";
		
		$content.= '<h5><input type = "submit" name="Forum" value="&uarr;" /><span>'.$section['SectionName'].'</span> - '.$section['Description'].'</h5>'."\n";
		$content.= '<p>'."\n";
		if ($current_page > 0) $content.= '<input type = "submit" name="section_page_jump['.($current_page - 1).']" value="&lt;" />'."\n";		
		if ($current_page < ($pages - 1)) $content.= '<input type = "submit" name="section_page_jump['.($current_page + 1).']" value="&gt;" />'."\n";
		if ($pages > 0)
		{
			$content.= '<select name="page_selector">'."\n";
			for ($i = 0; $i < $pages; $i++)
			{
				$content.= "\t".'<option value="'.$i.'"'.(($current_page == $i) ? ' selected="selected" ' : '').'>'.$i.'</option>'."\n";
			}
			$content.= '</select>'."\n";		
			$content.= '<input type = "submit" name="section_select_page" value="Select" />'."\n";
		}		
		if ($param['Section_details']['create_thread']) $content.= '<input type = "submit" name="new_thread" value="New thread" />'."\n";
		$content.= '</p>'."\n";
		$content.= '<div></div>'."\n";	
		$content.= '</div>'."\n";
				
		$content.= '</td></tr>'."\n";		
		
		if ($list)
		{
			foreach($list as $thread_data)
			{					
				$time = strtotime($thread_data['Created']);
				$offset = abs($timezone);
				$sign = ($timezone > 0) ? '-' : (($timezone < 0) ? '+' : '');
				$create_date = ZoneTime($time, "Etc/GMT".$sign.$offset, "G:i | j. F, Y");
				
				if ($thread_data['last_post'] != null)
				{
					$time = strtotime($thread_data['last_post']);
					$offset = abs($timezone);
					$sign = ($timezone > 0) ? '-' : (($timezone < 0) ? '+' : '');
					$post_date = ZoneTime($time, "Etc/GMT".$sign.$offset, "G:i | j. F, Y");
				}
			
				$style = (($thread_data['Priority'] == "sticky") ? ' style="color: red" ' : (($thread_data['Priority'] == "important") ? ' style="color: orange" ' : ''));						
				$locked = (($thread_data['Locked'] == "yes") ? ' (locked)' : '');					
				
				$content.= '<tr class="table_row">'."\n";
				$content.= '<td><p><input class="details" type = "submit" name="thread_details['.$thread_data['ThreadID'].']" value="+" /></p></td>'."\n";
				$content.= '<td><p'.$style.'>'.htmlencode($thread_data['Title']).$locked.'</p></td>'."\n";
				$content.= '<td><p>'.htmlencode($thread_data['Author']).'</p></td>'."\n";
				$content.= '<td><p>'.(($thread_data['post_count'] == null) ? 0 : $thread_data['post_count']).'</p></td>'."\n";
				$content.= '<td><p>'.$create_date.'</p></td>'."\n";
				$content.= '<td><p>'.(($thread_data['last_post'] == null) ? 'n/a' : $post_date." by ".htmlencode($thread_data['PostAuthor']).' <input class="details" type = "submit" name="thread_last_page['.$thread_data['ThreadID'].']" value="&rarr;" '.(($param['PreviousLogin'] < $time) ? ' style="border-color: red;" ' : "").'/>').'</p></td>'."\n";
				$content.= '</tr>'."\n";
			}
		}
		
		$content.= '</table>'."\n";
		
		$content.= '</div>'."\n";
		
		$content.= '<input type = "hidden" name = "CurrentSection" value = "'.$section['SectionID'].'"/>'."\n";
		
		$content.= '</div>'."\n";
					
		return $content;
	}
	
	function Generate_New_thread(array $param)
	{
		$section = $param['New_thread']['Section'];
	
		$content = "";
		
		$content.= '<div id="forum_new_edit">'."\n";
		
		$content.= '<h3>Create new thread to the section <span>'.$section['SectionName'].'</span></h3>'."\n";
		
		$content.= '<div>'."\n";	
		
		$content.= '<p>Topic:<input class="text_data" type="text" name="Title" maxlength="50" size="45" value="'.$param['New_thread']['Title'].'" /></p>'."\n";
		$content.= '<p>Priority:'."\n";
				
		$content.= '<select name="Priority">'."\n";
		$content.= "\t".'<option value="normal" selected="selected" >Normal</option>'."\n";
		if ($param['New_thread']['chng_priority'])
		{
			$content.= "\t".'<option value="important" >Important</option>'."\n";
			$content.= "\t".'<option value="sticky" >Sticky</option>'."\n";
		}
		$content.= '</select>'."\n";
		
		$content.= '</p>'."\n";
		
		$content.= '<input type = "submit" name = "create_thread" value="Create thread" />'."\n";
		$content.= '<input type = "submit" name="section_details['.$section['SectionID'].']" value="Back" />'."\n";
		$content.= '<hr/>'."\n";
		
		$content.= '<textarea name="Content" rows="10" cols="50">'.htmlencode($param['New_thread']['Content']).'</textarea>'."\n";
		
		$content.= '</div>'."\n";
		
		$content.= '<input type = "hidden" name = "CurrentSection" value = "'.$section['SectionID'].'"/>'."\n";
		
		$content.= '</div>'."\n";
							
		return $content;
	}
	
	function Generate_Thread_details(array $param)
	{	
		$content = "";
		
		$section = $param['Thread_details']['Section'];
		$thread = $param['Thread_details']['Thread'];
		$pages = $param['Thread_details']['Pages'];
		$current_page = $param['Thread_details']['CurrentPage'];
		$timezone = $param['Thread_details']['Timezone'];		
		$post_list = $param['Thread_details']['PostList'];
		$avatars = $param['Thread_details']['AvatarsList'];
		$deleted_post = $param['Thread_details']['DeletePost'];
		
		// is unlocked or you have the right to lock/unlock
		$can_modify = (($thread['Locked'] == "no") OR ($param['Thread_details']['lock_thread']));
		
		$content.= '<div id="thread_details">'."\n";
		
		$content.= '<h3>MArcomage discussion forum</h3>'."\n";
		
		$content.= '<h4>Thread details</h4>'."\n";
		
		$nav_bar = '<div class="thread_bar">'."\n";
		
		$nav_bar.= '<h5><input type = "submit" name="section_details['.$section['SectionID'].']" value="&uarr;" /><span>'.$section['SectionName'].'</span> - '.htmlencode($thread['Title']).''.(($thread['Locked'] == "yes") ? ' (Locked)' : '').'</h5>'."\n";
		
		$nav_bar.= '<p>'."\n";
		
		$lock = (($thread['Locked'] == "no") ? 'name="thread_lock" value="Lock"' : 'name="thread_unlock" value="Unlock"');
				
		if ($param['Thread_details']['lock_thread']) $nav_bar.= '<input type = "submit" '.$lock.' />'."\n";
		
		$delete = (($param['Thread_details']['Delete']) ? ' name="thread_delete_confirm" value="Confirm delete"' : ' name="thread_delete" value="Delete"');
						
		if (($param['Thread_details']['del_all_thread']) AND ($can_modify)) $nav_bar.= '<input type = "submit" '.$delete.' />'."\n";
		if (($param['Thread_details']['edit_thread']) AND ($can_modify)) $nav_bar.= '<input type = "submit" name="edit_thread" value="Edit" />'."\n";
		
		if ($current_page > 0)	$nav_bar.= '<input type = "submit" name="thread_page_jump['.($current_page - 1).']" value="&lt;" />'."\n";		
		if ($current_page < ($pages - 1)) $nav_bar.= '<input type = "submit" name="thread_page_jump['.($current_page + 1).']" value="&gt;" />'."\n";
		
		for ($i = 1; $i <= 2; $i++) $nav_bar_selector[$i] = '<select name="thread_page_selector'.$i.'">'."\n";
		$nav_bar_temp = "";
		
		for ($i = 0; $i < $pages; $i++)
		{
			$nav_bar_temp.= "\t".'<option value="'.$i.'"'.(($current_page == $i) ? ' selected="selected" ' : '').'>'.$i.'</option>'."\n";
		}
		$nav_bar_temp.= '</select>'."\n";
		
		for ($i = 1; $i <= 2; $i++) $nav_bar_submit[$i] = '<input type = "submit" name="thread_select_page'.$i.'" value="Select" />'."\n";
		
		$nav_bar_end = "";
		if (($param['Thread_details']['create_post']) AND ($thread['Locked'] == "no")) $nav_bar_end.= '<input type = "submit" name="new_post" value="New post" />'."\n";
		
		$nav_bar_end.= '</p>'."\n";
		
		$nav_bar_end.= '<div class="clear_floats"></div>'."\n";
		
		$nav_bar_end.= '</div>'."\n";
		
		for ($i = 1; $i <= 2; $i++)	$nav_bar_code[$i] = $nav_bar.$nav_bar_selector[$i].$nav_bar_temp.$nav_bar_submit[$i].$nav_bar_end;
		
		$content.= $nav_bar_code[1]; // generate upper thread navigation bar
				
		$content.= '<div id="post_list">'."\n";
		
		if ($post_list)
		{
			foreach ($post_list as $post_data)
			{
				$time = strtotime($post_data['Created']);
				$offset = abs($timezone);
				$sign = ($timezone > 0) ? '-' : (($timezone < 0) ? '+' : '');
				$create_date = ZoneTime($time, "Etc/GMT".$sign.$offset, "G:i - j. M, Y");
			
				$content.= '<div>'."\n";
				
				$content.= '<div>'."\n";
								
				$content.= "\t".'<h5>'.htmlencode($post_data['Author']).'</h5>'."\n";
				
				$content.= "\t".'<img class="avatar" height="60px" width="60px" src="img/avatars/'.htmlencode($avatars[$post_data['Author']]).'" alt="avatar" />'."\n";
				
				$content.= '<p>'."\n";
				$content.= "\t".'<input class="details" type = "submit" name = "user_details['.postencode($post_data['Author']).']" value = "i" />'."\n";
				$content.= "\t".'<input class="details" type = "submit" name = "message_create['.postencode($post_data['Author']).']" value = "m" />'."\n";
				
				$content.= '</p>'."\n";
				
				$content.= '<p'.(($param['PreviousLogin'] < $time) ? ' class="highlighted" ' : "").'>'.$create_date.'</p>'."\n";
				
				$content.= '</div>'."\n";
				
				$content.= '<div>'."\n";
				
				$content.= '<div>'."\n";
				
				$disabled = (((($param['Thread_details']['edit_all_post']) OR (($param['Thread_details']['edit_own_post']) AND ($param['PlayerName'] == $post_data['Author']))) AND ($can_modify)) ? '' : ' disabled="disabled" ');
				
				$content.= '<input type = "submit" name = "edit_post['.$post_data['PostID'].']" value="Edit" '.$disabled.'/>'."\n";
				
				$delete_button = ((($deleted_post) AND ($deleted_post == $post_data['PostID'])) ? 'name = "delete_post_confirm['.$post_data['PostID'].']" value="Confirm delete"' : 'name = "delete_post['.$post_data['PostID'].']" value="Delete"');
				
				if (($param['Thread_details']['del_all_post']) AND ($can_modify)) $content.= '<input type = "submit" '.$delete_button.' />'."\n";
								
				$content.= '</div>'."\n";
				
				$content.= "\t".'<p>'.textencode($post_data['Content']).'</p>'."\n";
				
				$content.= '</div>'."\n";
				
				$content.= '<div class="clear_floats"></div>'."\n";
				
				$content.= '</div>'."\n";
			}
		}
		
		$content.= '</div>'."\n";
		
		$content.= $nav_bar_code[2]; // generate lower thread navigation bar
		
		$content.= '<input type = "hidden" name = "CurrentSection" value = "'.$thread['Section'].'"/>'."\n";
		$content.= '<input type = "hidden" name = "CurrentThread" value = "'.$thread['ThreadID'].'"/>'."\n";
		$content.= '<input type = "hidden" name = "CurrentPage" value = "'.$current_page.'"/>'."\n";
		
		$content.= '</div>'."\n";
											
		return $content;
	}
	
	function Generate_New_post(array $param)
	{
		$thread = $param['New_post']['Thread'];
	
		$content = "";
		
		$content.= '<div id="forum_new_edit">'."\n";
		
		$content.= '<h3>New post in thread - <span>'.htmlencode($thread['Title']).'</span></h3>'."\n";
		
		$content.= '<div>'."\n";	
				
		$content.= '<input type = "submit" name = "create_post" value="Create post" />'."\n";
		$content.= '<input type = "submit" name="thread_details['.$thread['ThreadID'].']" value="Back" />'."\n";
		$content.= '<hr/>'."\n";
		
		$content.= '<textarea name="Content" rows="10" cols="50">'.htmlencode($param['New_post']['Content']).'</textarea>'."\n";
		
		$content.= '</div>'."\n";
		
		$content.= '<input type = "hidden" name = "CurrentThread" value = "'.$thread['ThreadID'].'"/>'."\n";
				
		$content.= '</div>'."\n";
							
		return $content;
	}
	
	function Generate_Edit_post(array $param)
	{
		$post = $param['Edit_post']['Post'];
		$current_page = $param['Edit_post']['CurrentPage'];
		$thread_list = $param['Edit_post']['ThreadList'];
		$thread = $param['Edit_post']['Thread'];
			
		$content = "";
		
		$content.= '<div id="forum_new_edit">'."\n";
		
		$content.= '<h3>Edit post</h3>'."\n";
		
		$content.= '<div>'."\n";	
				
		$content.= '<input type = "submit" name = "modify_post" value="Save" />'."\n";
		$content.= '<input type = "submit" name="thread_details['.$post['Thread'].']" value="Back" />'."\n";
		$content.= '<hr/>'."\n";
		
		$content.= '<textarea name="Content" rows="10" cols="50">'.htmlencode($param['Edit_post']['Content']).'</textarea>'."\n";
		
		if ($param['Edit_post']['move_post'])
		{
			$content.= '<hr/>'."\n";
			
			$content.= '<p>Current thread: <span>'.htmlencode($thread['Title']).'</span></p>'."\n";
			
			$content.= '<p>Target thread:'."\n";
					
			$content.= '<select name="thread_select">'."\n";
			foreach ($thread_list as $thread_id => $thread_name)
			{
				$content.= "\t".'<option value="'.$thread_id.'" >'.htmlencode($thread_name).'</option>'."\n";
			}		
			$content.= '</select>'."\n";
			
			$content.= '</p>'."\n";
			
			$content.= '<input type = "submit" name = "move_post" value="Change thread" />'."\n";
		}
		
		$content.= '</div>'."\n";
		
		$content.= '<input type = "hidden" name = "CurrentThread" value = "'.$post['Thread'].'"/>'."\n";
		$content.= '<input type = "hidden" name = "CurrentPage" value = "'.$current_page.'"/>'."\n";
		$content.= '<input type = "hidden" name = "CurrentPost" value = "'.$post['PostID'].'"/>'."\n";
				
		$content.= '</div>'."\n";
							
		return $content;
	}
	
	function Generate_Edit_thread(array $param)
	{			
		$section = $param['Edit_thread']['Section'];
		$thread = $param['Edit_thread']['Thread'];
		$section_list = $param['Edit_thread']['SectionList'];
	
		$content = "";
		
		$content.= '<div id="forum_new_edit">'."\n";
		
		$content.= '<h3>Edit thread</h3>'."\n";
		
		$content.= '<div>'."\n";	
		
		$content.= '<p>Topic:<input class="text_data" type="text" name="Title" maxlength="50" size="45" value="'.$thread['Title'].'" /></p>'."\n";
		$content.= '<p>Priority:'."\n";
		
		$disabled = (($param['Edit_thread']['chng_priority']) ? '' : ' disabled="disabled" ');
		
		$content.= '<select name="Priority"'.$disabled.'>'."\n";
		$content.= "\t".'<option value="normal"'.(($thread['Priority'] == "normal") ? ' selected="selected" ' : '').' >Normal</option>'."\n";
		$content.= "\t".'<option value="important"'.(($thread['Priority'] == "important") ? ' selected="selected" ' : '').' >Important</option>'."\n";
		$content.= "\t".'<option value="sticky"'.(($thread['Priority'] == "sticky") ? ' selected="selected" ' : '').' >Sticky</option>'."\n";
		$content.= '</select>'."\n";
		
		$content.= '</p>'."\n";
		
		$content.= '<input type = "submit" name = "modify_thread" value="Save" />'."\n";
		$content.= '<input type = "submit" name="thread_details['.$thread['ThreadID'].']" value="Back" />'."\n";
		
		if ($param['Edit_thread']['move_thread'])
		{
			$content.= '<hr/>'."\n";
			
			$content.= '<p>Current section: <span>'.$section['SectionName'].'</span></p>'."\n";
			
			$content.= '<p>Target section:'."\n";
					
			$content.= '<select name="section_select">'."\n";
			foreach ($section_list as $section_id => $section_name)
			{
				$content.= "\t".'<option value="'.$section_id.'" >'.$section_name.'</option>'."\n";
			}		
			$content.= '</select>'."\n";
			
			$content.= '</p>'."\n";
			
			$content.= '<input type = "submit" name = "move_thread" value="Change section" />'."\n";
		}
				
		$content.= '</div>'."\n";
		
		$content.= '<input type = "hidden" name = "CurrentThread" value = "'.$thread['ThreadID'].'"/>'."\n";
				
		$content.= '</div>'."\n";
							
		return $content;
	}
	
	function Generate_Header()
	{
		$content = "";
			
		$content.= '<?xml version="1.0" encoding="UTF-8"?>'."\n";
		$content.= '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">'."\n";
		$content.= '<html xmlns="http://www.w3.org/1999/xhtml" lang="en">'."\n";
		$content.= '<head>'."\n";
		$content.= '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />'."\n";
		$content.= '<meta name="description" content="Arcomage multiplayer on-line fantasy card game" />'."\n";
		$content.= '<meta name="author" content="Mojmír Fendek, Viktor Štujber" />'."\n";
		$content.= '<meta name="keywords" content="Arcomage, MArcomage, free, online, fantasy, card game, fantasy novels"/>'."\n";
		$content.= '<link rel="stylesheet" href="styles/general.css" type="text/css" title="standard style" />'."\n";
		$content.= '<link rel="stylesheet" href="styles/menubar.css" type="text/css" title="standard style" />'."\n";
		$content.= '<link rel="stylesheet" href="styles/decks.css" type="text/css" title="standard style" />'."\n";
		$content.= '<link rel="stylesheet" href="styles/card.css" type="text/css" title="standard style" />'."\n";
		$content.= '<link rel="stylesheet" href="styles/games.css" type="text/css" title="standard style" />'."\n";
		$content.= '<link rel="stylesheet" href="styles/players.css" type="text/css" title="standard style" />'."\n";
		$content.= '<link rel="stylesheet" href="styles/messages.css" type="text/css" title="standard style" />'."\n";
		$content.= '<link rel="stylesheet" href="styles/webpage.css" type="text/css" title="standard style" />'."\n";
		$content.= '<link rel="stylesheet" href="styles/settings.css" type="text/css" title="standard style" />'."\n";
		$content.= '<link rel="stylesheet" href="styles/novels.css" type="text/css" title="standard style" />'."\n";
		$content.= '<link rel="stylesheet" href="styles/forum.css" type="text/css" title="standard style" />'."\n";
		$content.= '<link rel="icon" href="img/favicon.png" type="image/png" />'."\n";
		$content.= '<title>MArcomage</title>'."\n";
		$content.= '</head>'."\n";
		$content.= '<body>'."\n";
		$content.= '<form action="" enctype="multipart/form-data" method="post">'."\n";
			
		return $content;
	}
	
	function Generate_LoginBox(array $param)
	{
		$content = "";
			
		$content.= '<div>'."\n";
		$content.= '<div id="login_box">'."\n";
		$content.= '<div id="lbox_float_left">'."\n";
		$content.= '<div>'."\n";

		$content.= '<p>Login name</p>'."\n";
		$content.= '<div><input class="text_data" type="text" name="Username" maxlength="20" tabindex="1" /></div>'."\n";
		$content.= '<p>Password</p>'."\n";
		$content.= '<div><input class="text_data" type="password" name="Password" maxlength="20" tabindex="2" /></div>'."\n";
		$content.= '<div><input type="hidden" name="Remember" value="yes" /></div>'."\n";
		$content.= '<div>'."\n";
		$content.= '<input type="submit" name="Login" value="Login" tabindex="3" />'."\n";
		$content.= '<input type="submit" name="Registration" value="Register" tabindex="4" />'."\n";
		$content.= '</div>'."\n";
		
		if ($param['error'] != "") $content.= '<p class="information_trans" style="color: red">'.$param['error'].'</p>'."\n";
		if ($param['warning'] != "") $content.= '<p class="information_trans" style="color: yellow">'.$param['warning'].'</p>'."\n";
		if ($param['information'] != "") $content.= '<p class="information_trans" style="color: lime">'.$param['information'].'</p>'."\n";
		else $content.= '<p style="height: 1.2em;" class="information_trans"></p>'."\n";
		
		$content.= '</div>'."\n";
		$content.= '</div>'."\n";
		
		$content.= '<div id="lbox_float_right">'."\n";
		$content.= '<div>'."\n";
		
		$content.= '<h1>MArcomage</h1>'."\n";
		$content.= '<h2>Free multiplayer on-line fantasy card game</h2>'."\n";
				
		$content.= '</div>'."\n";
		$content.= '</div>'."\n";
		
		$content.= '<div class="clear_floats"></div>'."\n";
		$content.= '</div>'."\n";
			
		return $content;
	}
	
	function Generate_NavigationBar(array $param)
	{
		$content = "";
		
		$current = $param['Current'];
			
		// user navigation bar; display only when logged in
		$content.= '<div>'."\n";
		
		$content.= '<div id="menubar">'."\n";
		$content.= '<div id="menu_float_left">'."\n";
		$content.= '<p>'.htmlencode($param['PlayerName']).'</p>'."\n";
		$content.= '</div>'."\n";
		
		$content.= '<div id="menu_float_right">'."\n";
		
		$content.= '<input type = "submit" name="Refresh['.postencode($current).']" value="Refresh" accesskey="w" />'."\n";
		$content.= '</div>'."\n";
		
		$content.= '<div id="menu_center">'."\n";
		
		$forum_sections = array(1 => "Forum", "Section_details", "New_thread", "Thread_details", "New_post", "Edit_post", "Edit_thread");
		$content.= '<input type = "submit" name="Page" value="Webpage" '.(($current == "Page") ? 'class="menuselected"' : '').' />'."\n";
		$content.= '<input type = "submit" name="Forum" value="Forum" '.((in_array($current, $forum_sections)) ? 'class="menuselected"' : '').' />'.(($param['IsSomethingNew']) ? '<img src="img/book.gif" alt="" width="18px" height="14px" />' : '')."\n";
		$content.= '<input type = "submit" name="Challenges" value="Challenges" '.(($current == "Challenges" || $current == "Message_details" || $current == "Message_new") ? 'class="menuselected"' : '').' />'.((($param['NumChallenges'] > 0) OR ($param['NumUnread'] > 0)) ? '<img src="img/new_post.gif" alt="" width="15px" height="10px" />' : '')."\n";
		$content.= '<input type = "submit" name="Players" value="Players" '.(($current == "Players" || $current == "Details") ? 'class="menuselected"' : '').' />'."\n";
		$content.= '<input type = "submit" name="Games" value="Games" '.(($current == "Games" || $current == "Game") ? 'class="menuselected"' : '').' />'.(($param['NumGames'] > 0) ? '<img src="img/battle.gif" alt="" width="20px" height="13px" />' : '')."\n";
		$content.= '<input type = "submit" name="Decks" value="Decks" '.(($current == "Decks" || $current == "Deck") ? 'class="menuselected"' : '').' />'."\n";
		$content.= '<input type = "submit" name="Novels" value="Novels" '.(($current == "Novels") ? 'class="menuselected"' : '').' />'."\n";
		$content.= '<input type = "submit" name="Settings" value="Settings" '.(($current == "Settings") ? 'class="menuselected"' : '').' />'."\n";
		$content.= '<input type = "submit" name="Logout" value="Logout" accesskey="q" />'."\n";		
		
		$content.= '</div>'."\n";
		
		$content.= '<div class="clear_floats"></div>'."\n";
		
		$content.= '</div>'."\n";
		
		$content.= '<hr />'."\n";
		
		if ($param['error'] != "") $content.= '<p class="information_line" style="color: red">'.$param['error'].'</p>'."\n";
		if ($param['warning'] != "") $content.= '<p class="information_line" style="color: yellow">'.$param['warning'].'</p>'."\n";
		if ($param['information'] != "") $content.= '<p class="information_line" style="color: lime">'.$param['information'].'</p>'."\n";
		else $content.= '<p class="blank_line"></p>'."\n";
			
		return $content;
	}
	
	function Generate_Nowhere()
	{
		$content = "";
		
		$content.= '<p class="information_trans" style = "color: yellow;">Welcome to no-man\'s land...</p>';
		$content.= '<p class="information_trans" style = "color: yellow;">How did you get here, anyway? o_o;</p>';
		$content.= '<p class="information_trans" style = "color: yellow;">This is an error in the system! Please notify us!</p>';
			
		return $content;
	}
	
	function Generate_Footer(array $param)
	{
		$content = "";		
		
		// closing tags for the navigation bar
		$content.= '</div>'."\n";
		
		/*	</section>	*/
		
		// neccessary to allow the page work without cookies
		if ($param['sessionstring'] != "") $content.= '	'.$param['sessionstring']."\n";
		
		$content.= '</form>'."\n";
		
		$querytime_end = microtime(TRUE);
		//$content.= '<p>'.'in '.(int)(1000*($querytime_end - $querytime_start)).' ms'.'</p>'."\n";
		//$content.= '<p>'.$db->queries.' queries used</p>'."\n";
		
		$content.= '</body>'."\n";
		$content.= "\n";
		$content.= '</html>'."\n";
			
		return $content;
	}	

?>
