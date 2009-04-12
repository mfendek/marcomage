<?php
/*
	CCard - the representation of a card
*/
?>
<?php
	class CCards
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
		
		public function GetCard($cardid)
		{
			return new CCard($cardid, $this);
		}
		
		public function GetList($class = "", $keyword = "", $cost = "", $advanced = "", $support = "")
		{
			$db = $this->db;
			
			$class_query = (($class) ? "`Class` = '".$db->Escape($class)."'" : "1");
			if ($keyword == "All keywords") $keyword_query = '`Keywords` !=""';
			elseif ($keyword == "No keywords") $keyword_query = '`Keywords` =""';
			elseif ($keyword != "none") $keyword_query = "`Keywords` LIKE '%".$db->Escape($keyword)."%'";
			else $keyword_query = "1";
			
			$advanced_query = (($advanced != "none") ? '`Effect` LIKE "%'.$db->Escape($advanced).'%"' : "1");
			
			// TODO find a better way to look for keywords in the effect (we are now searching for "<b>", becuase every keyword has them)
			if ($support == "Any keyword") $support_query = '`Effect` LIKE "%<b>%"';
			elseif ($support == "No keywords") $support_query = '`Effect` NOT LIKE "%<b>%"';
			elseif ($support != "none") $support_query = '`Effect` LIKE "%'.$db->Escape($support).'%"';
			else $support_query = "1";
			
			switch ($cost)
			{
				case "Red": $cost_query = "`Gems` = 0 AND `Recruits` = 0 AND `Bricks` > 0"; break;
				case "Blue": $cost_query = "`Recruits` = 0 AND `Bricks` = 0 AND `Gems` > 0"; break;
				case "Green": $cost_query = "`Gems` = 0 AND `Bricks` = 0 AND `Recruits` > 0"; break;
				case "Zero": $cost_query = "`Bricks` = 0 AND `Gems` = 0 AND `Recruits` = 0"; break;
				case "Mixed": $cost_query = "((`Bricks` > 0 AND `Gems` > 0 AND `Recruits` >= 0) OR (`Bricks` >= 0 AND `Gems` > 0 AND `Recruits` > 0) OR (`Bricks` > 0 AND `Gems` >= 0 AND `Recruits` > 0))"; break;
				default: $cost_query = "1"; break;
			}
			
			$result = $db->Query("SELECT `cards`.`CardID` FROM `cards` WHERE ".$class_query." AND ".$advanced_query." AND ".$keyword_query." AND ".$cost_query." AND ".$support_query." ORDER BY `Name` ASC");
			
			if (!$result) return false;
			
			$cards = array();
			for ($i = 1; $i <= $result->Rows(), $card = $result->Next(); $i++)
				$cards[$i] = $card['CardID'];
			
			return $cards;
		}
		
		// returns all distinct keywords
		public function Keywords()
		{
			$keywords = array();
			
			$db = $this->db;
			$result = $db->Query('SELECT DISTINCT `Keywords` FROM `cards`');
			if (!$result) return $keywords;
			
			while ($entry = $result->Next())
			{
				$words = preg_split("/\. ?/", $entry['Keywords'], -1, PREG_SPLIT_NO_EMPTY); // split individual keywords
				foreach($words as $word)
				{
					$word = preg_split("/ \(/", $word, 0); // remove parameter if present
					$word = $word[0];
					$keywords[$word] = $word; // removes duplicates
				}
			}
			sort($keywords);
			
			return $keywords;
		}
		
		// returns token keywords
		public function TokenKeywords()
		{
			return array('Alliance', 'Barbarian', 'Brigand', 'Beast', 'Burning', 'Holy', 'Mage', 'Soldier', 'Titan', 'Undead', 'Unliving');
		}
	}
	
	
	class CCard
	{
		private $CardID;
		private $Cards;
		public $CardData;
		
		public function __construct($cardid, CCards &$Cards)
		{
			$this->CardID = (int)$cardid; 
			
			$this->Cards = &$Cards;
			$this->CardData = new CCardData;
			
			$cd = &$this->CardData;
			
			$db = $this->Cards->getDB();
			$result = $db->Query('SELECT `Name`, `Class`, `Bricks`, `Gems`, `Recruits`, `Effect`, `Keywords`, `Modes` FROM `cards` WHERE `CardID` = '.$this->CardID.'');
			
			if( !$result || !$result->Rows() )
                $arr = array ('Invalid Card', 'None', 0, 0, 0, '', '', 0);
			else
			{
				$data = $result->Next();
				$arr = array ($data['Name'], $data['Class'], $data['Bricks'], $data['Gems'], $data['Recruits'], $data['Effect'], $data['Keywords'], $data['Modes']);
			}
			
			// initialize self
			list($cd->Name, $cd->Class, $cd->Bricks, $cd->Gems, $cd->Recruits, $cd->Effect, $cd->Keywords, $cd->Modes) = $arr;
		}
		
		public function __destruct()
		{
			$this->CardID = -1;
			$this->Cards = false;
			$this->CardData = false;
		}
		
		public function IsPlayAgainCard()
		{
			return ($this->HasKeyWord("Quick") or $this->HasKeyWord("Swift"));
		}
		
		public function HasKeyword($keyword)
		{
			if( $keyword != "any" )
				return (strpos($this->CardData->Keywords, $keyword) !== FALSE);
			else // search for any keywords
				return ($this->CardData->Keywords != "");
		}
		
		public function GetResources($type)
		{
			if ($type !="")
				$resource = $this->CardData->$type;
			else
			{
				$resources = array("Bricks" => 0, "Gems" => 0, "Recruits" => 0);
				$resource = 0;
				foreach ($resources as $r_name => $r_value)
					$resource+= $this->CardData->$r_name;
			}
		
			return $resource;
		}
		
		public function GetClass()
		{
			return $this->CardData->Class;
		}
		
		public function GetKeywords()
		{
			return $this->CardData->Keywords;
		}
		
		public function CardString($c_text, $c_img, $c_keyword, $c_oldlook)
		{
			$all_colors = array("RosyBrown"=>"#bc8f8f", "DeepSkyBlue"=>"#00bfff", "DarkSeaGreen"=>"#8fbc8f", "DarkRed"=>"#8b0000", "HotPink"=>"#ff69b4", "LightBlue"=>"#add8e6", "LightGreen"=>"#90ee90", "Gainsboro"=>"#dcdcdc", "DeepSkyBlue"=>"#00bfff", "DarkGoldenRod"=>"#b8860b");
			
			$cs = '';
		
			$card = $this->CardData;
						
			// display the proper card background color and background image, with respect to its cost and current setting
			if     (($card->Bricks == 0) and ($card->Gems == 0) and ($card->Recruits == 0))
			{
				$bgcolor = $all_colors["Gainsboro"];// no cost -> Gainsboro
				$bgimage = "zero_cost";
			}
			elseif (($card->Bricks >  0) and ($card->Gems == 0) and ($card->Recruits == 0))
			{
				$bgcolor = $all_colors["RosyBrown"];// only bricks -> RosyBrown
				$bgimage = "bricks_cost";
			}
			elseif (($card->Bricks == 0) and ($card->Gems >  0) and ($card->Recruits == 0))
			{
				$bgcolor = $all_colors["DeepSkyBlue"];// only gems -> DeepSkyBlue
				$bgimage = "gem_cost";
			}
			elseif (($card->Bricks == 0) and ($card->Gems == 0) and ($card->Recruits >  0))
			{
				$bgcolor = $all_colors["DarkSeaGreen"];// only recruits -> DarkSeaGreen
				$bgimage = "rec_cost";
			}
			else
			{
				$bgcolor = $all_colors["DarkGoldenRod"];// mixed -> DarkGoldenRod
				$bgimage = "mixed_cost";
			}
			
			if ($c_oldlook == "no") { $bgimage = " ".$bgimage; $bgstyle = "; border-style: outset;"; }
			else { $bgimage = ""; $bgstyle = "; border-style: ridge;"; }
			
			$cs.= '<div class="karta'.$bgimage.'" style="background-color: '.$bgcolor.$bgstyle.'" >'."\n";
			
			// display the cost (spheres with numbers in the center)
			if (($card->Bricks > 0) and ($card->Gems == $card->Bricks) and ($card->Recruits == $card->Bricks))
			{
				$cs.= '<div class="all">'.$card->Bricks.'</div>'."\n";
			}
			elseif (($card->Bricks == 0) and ($card->Gems == 0) and ($card->Recruits == 0))
			{
				$cs.= '<div class="null">0</div>'."\n";
			}
			else
			{
				if ($card->Recruits > 0) $cs.= '<div class="rek">'.$card->Recruits.'</div>'."\n";
				if ($card->Gems > 0) $cs.= '<div class="gemy">'.$card->Gems.'</div>'."\n";
				if ($card->Bricks > 0) $cs.= '<div class="tehla">'.$card->Bricks.'</div>'."\n";
			}
			
			// display the name
			$cs.= '<h5>'.$card->Name.'</h5>'."\n";
			
			if ($c_img == "yes")
			{
				// display the card's image and its border, with respect to the card's class
				if ($card->Class == 'Common') $border = 'Lime'; elseif ($card->Class == 'Uncommon') $border = $all_colors["DarkRed"]; elseif ($card->Class == 'Rare') $border = 'Yellow'; else $border = 'White';
				$cs.= '<img src="img/cards/g'.$this->CardID.'.jpg" width="80px" height="60px" style="border-color: '.$border.'" alt="" />'."\n";
			}
			
			if ($c_keyword == "yes")
			{		
				//display keywords
				$cs.= '<p><b>'.$card->Keywords.'</b></p>'."\n";
			}
			
			if ($c_text == "yes")
			{
				// display the card's text (with '<' '>' properly escaped)
				//FIXME: this is not such a good idea
				$effect = str_replace(array(' < ', ' > '), array(' &lt; ', ' &gt; '), $card->Effect);
				$cs.= '<p>'.$effect.'</p>'."\n";
			}
			
			$cs.= '</div>'."\n";
			
			return $cs;
		}
	}
	
	
	class CCardData
	{
		public $Name;
		public $Class;
		public $Bricks;
		public $Gems;
		public $Recruits;
		public $Effect;
		public $Keywords;
		public $Modes;
	}
?>
