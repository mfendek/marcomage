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
		
		/**
		 * Constructs a filtering query based on the specified filters.
		 * Available filters are:
		 * <ul>
		 * <li> 'class'    => { None | Common | Uncommon | Rare }, queries `Class` </li>
		 * <li> 'keyword'  => { Any keyword | No keywords | <a specific keyword> }, queries `Keywords` </li>
		 * <li> 'cost'     => { Red | Blue | Green | Zero | Mixed }, queries `Bricks`, `Gems` and `Recruits` </li>
		 * <li> 'advanced' => { <a specific substring> }, queries `Effect` </li>
		 * <li> 'support'  => { Any keyword | No keywords | <a specific keyword> }, queries `Effect` </li>
		 * </ul>
		 * @param array $filters an array of chosen filters and their parameters
		 * @return string a boolean expression to be used in a card retrieval query
		*/
		private function makeFilterQuery(array $filters)
		{
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
			
			return $query;
		}

		/**
		 * Filters cards according to the provided filtering instructions.
		 * @see CCards::makeFilterQuery()
		 * @param array $filters an array of chosen filters and their parameters
		 * @return array an array of ids for cards that match the filters
		*/
		public function GetList(array $filters)
		{
			$db = $this->db;

			$result = $db->xpath("/am:cards/am:card[".$this->makeFilterQuery($filters)."]/@id");
			
			if( $result === false ) return false;
			
			$cards = array();
			foreach( $result as $card )
				$cards[] = (int)$card;
			
			return $cards;
		}

		/**
		 * Retrieves data for the specified card ids.
		 * Can be used in combination with CCards::GetList().
		 * The same card id may be specified multiple times.
		 * The result will use the same keys and key order as the input.
		 * @param array $ids an array of card ids to retrieve
		 * @return array an array of the requested cards' data
		*/
		public function GetData(array $ids)
		{
			$db = $this->db;

			// since xpath is too slow for this task, just grab everything and process it in php
			$result = $db->xpath("/am:cards/am:card");
			if( $result === false ) return false;
			
			$cards = array();
			foreach( $result as $card )
			{
				$data['id']       = (int)$card->attributes()->id;
				$data['name']     = (string)$card->name;
				$data['class']    = (string)$card->class;
				$data['bricks']   = (int)$card->cost->bricks;
				$data['gems']     = (int)$card->cost->gems;
				$data['recruits'] = (int)$card->cost->recruits;
				$data['modes']    = (int)$card->modes;
				$data['keywords'] = (string)$card->keywords;
				$data['effect']   = (string)$card->effect;
				$cards[$data['id']] = $data;
			}

			$out = array();
			foreach( $ids as $index => $id )
			{
				if( !isset($cards[$id]) )
					return NULL; // nonexistent card
				
				$out[$index] = $cards[$id];
			}

			return $out;
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
