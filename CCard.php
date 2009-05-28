<?php
/*
	CCard - the representation of a card
*/
?>
<?php
	class CCards
	{
		private $db;
		
		public function __construct()
		{
			$this->db = new SimpleXMLElement('cards.xml', 0, TRUE);
			$this->db->registerXPathNamespace('am', 'http://arcomage.netvor.sk');
		}
		
		public function __destruct()
		{
			$this->db = false;
		}

		public function GetDB()
		{
			return $this->db;
		}
		
		public function GetCard($cardid)
		{
			return new CCard($cardid, $this);
		}
		
		/// Filters cards according to the provided filtering instructions.
		/// Available filters are:
		///   'class'    => { None | Common | Uncommon | Rare }, queries `Class`
		///   'keyword'  => { Any keyword | No keywords | <a specific keyword> }, queries `Keywords`
		///   'cost'     => { Red | Blue | Green | Zero | Mixed }, queries `Bricks`, `Gems` and `Recruits`
		///   'advanced' => { <a specific substring> }, queries `Effect`
		///   'support'  => { Any keyword | No keywords | <a specific keyword> }, queries `Effect`
		/// @param filters an array of chosen filters and their parameters
		/// @return an array of matching card ids
		public function GetList(array $filters)
		{
			$db = $this->db;
			
			$query = "@id > 0"; // sentinel

			if( isset($filters['class']) )
			{
				$query .= " and ";
				$query .= "am:class = '".$filters['class']."'"; //FIXME: no escaping
			}

			if( isset($filters['keyword']) )
			{
				$query .= " and ";
				switch( $filters['keyword'] )
				{
				case 'Any keyword': $query .= "am:keywords != ''"; break;
				case 'No keywords': $query .= "am:keywords = ''"; break;
				default           : $query .= "contains(am:keywords, '".$filters['keyword']."')"; break; //FIXME: no escaping
				}
			}

			if( isset($filters['cost']) )
			{
				$query .= " and ";
				switch( $filters['cost'] )
				{
				case 'Red'  : $query .= "am:cost/am:gems = 0 and am:cost/am:recruits = 0 and am:cost/am:bricks > 0"; break;
				case 'Blue' : $query .= "am:cost/am:recruits = 0 and am:cost/am:bricks = 0 and am:cost/am:gems > 0"; break;
				case 'Green': $query .= "am:cost/am:gems = 0 and am:cost/am:bricks = 0 and am:cost/am:recruits > 0"; break;
				case 'Zero' : $query .= "am:cost/am:bricks = 0 and am:cost/am:gems = 0 and am:cost/am:recruits = 0"; break;
				case 'Mixed': $query .= "(am:cost/am:bricks > 0) + (am:cost/am:gems > 0) + (am:cost/am:recruits > 0) >= 2"; break;
				default     : $query .= "true"; //FIXME: should never happen
				}
			}

			if( isset($filters['advanced']) )
			{
				$query .= " and ";
				$query .= "contains(am:effect, '".$filters['advanced']."')"; //FIXME: no escaping
			}

			if( isset($filters['support']) )
			{
				$query .= " and ";
				// TODO find a better way to look for keywords in the effect (we are now searching for "<b>", becuase every keyword has them)
				switch( $filters['support'] )
				{
				case 'Any keyword': $query .= "contains(am:effect, '<b>')"; break;
				case 'No keywords': $query .= "!contains(am:effect, '<b>')"; break;
				default           : $query .= "contains(am:effect, '".$filters['support']."')"; //FIXME: no escaping
				}
			}
			
			//FIXME: cannot sort by name... do the sorting elsewhere (." ORDER BY `Name` ASC")
			$result = $db->xpath("/am:cards/am:card[$query]/@id");
			
			if( $result === false ) return false;
			
			$cards = array();
			foreach( $result as $card )
				$cards[] = (int)$card;
			
			return $cards;
		}
		
		// returns all distinct keywords
		public function Keywords()
		{
			$keywords = array();
			
			$db = $this->db;
			$result = $db->xpath('/am:cards/am:card/am:keywords');
			if( $result === false ) return $keywords;
			
			foreach($result as $entry)
			{
				$words = preg_split("/\. ?/", (string)$entry, -1, PREG_SPLIT_NO_EMPTY); // split individual keywords
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
			$result = $db->xpath("/am:cards/am:card[@id={$this->CardID}]");
			
			if( $result === false || count($result) == 0 )
                $arr = array ('Invalid Card', 'None', 0, 0, 0, '', '', 0);
			else
			{
				$data = &$result[0];
				$arr = array ((string)$data->name, (string)$data->class, (int)$data->cost->bricks, (int)$data->cost->gems, (int)$data->cost->recruits, (string)$data->effect, (string)$data->keywords, (int)$data->modes);
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
