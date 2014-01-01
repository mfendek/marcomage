<?php
/*
	CCard - the representation of a card
*/
?>
<?php
	class CCards
	{
		private $db;
		private $cache; // ID -> card data
		
		public function __construct()
		{
			$this->db = false;
			$this->cache = false;
		}

		public function getDB()
		{
			// initialize on first use
			if( $this->db === false )
			{
				$this->db = new SimpleXMLElement('cards.xml', 0, TRUE);
				$this->db->registerXPathNamespace('am', 'http://arcomage.netvor.sk');
			}

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
		 * <li> 'name'     => { <search_substring> }, queries `Name` </li>
		 * <li> 'class'    => { None | Common | Uncommon | Rare }, queries `Class` </li>
		 * <li> 'keyword'  => { Any keyword | No keywords | <a specific keyword> }, queries `Keywords` </li>
		 * <li> 'cost'     => { Red | Blue | Green | Zero | Mixed }, queries `Bricks`, `Gems` and `Recruits` </li>
		 * <li> 'advanced' => { <a specific substring> }, queries `Effect` </li>
		 * <li> 'support'  => { Any keyword | No keywords | <a specific keyword> }, queries `Effect` </li>
		 * <li> 'level'    => { <specific_level> }, queries `level` </li>
		 * <li> 'level_op' => { = | <= }, additional parameter for `level` defaults to '=' </li>
		 * </ul>
		 * @param array $filters an array of chosen filters and their parameters
		 * @return string a boolean expression to be used in a card retrieval query
		*/
		private function makeFilterQuery(array $filters)
		{
			$query = "@id > 0"; // sentinel

			if( isset($filters['name']) )
			{
				$query .= " and ";
				$query .= "contains(am:name, '".preg_replace("/[^a-zA-Z0-9 ]/i", '', $filters['name'])."')"; // case-sensitive search
			}

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
				$query .= "(contains(am:effect, '".$filters['advanced']."') or contains(am:effect, '".strtolower($filters['advanced'])."'))"; //FIXME: no escaping
			}

			if( isset($filters['support']) )
			{
				$query .= " and ";
				// TODO find a better way to look for keywords in the effect (we are now searching for "<b>", becuase every keyword has them)
				switch( $filters['support'] )
				{
				case 'Any keyword': $query .= "contains(am:effect, '<b>')"; break;
				case 'No keywords': $query .= "not(contains(am:effect, '<b>'))"; break;
				default           : $query .= "contains(am:effect, '<b>".$filters['support']."')"; //FIXME: no escaping
				}
			}

			if( isset($filters['created']) )
			{
				$query .= " and am:created = '".$filters['created']."'";
			}

			if( isset($filters['modified']) )
			{
				$query .= " and am:modified = '".$filters['modified']."'";
			}

			if( isset($filters['level']) )
			{
				$operator = (isset($filters['level_op']) and in_array($filters['level_op'], array('=', '<='))) ? $filters['level_op'] : '<=';
				$query .= " and ";
				$query .= "am:level ".$operator." ".$filters['level']; //FIXME: no escaping
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
			$db = $this->getDB();

			$result = $db->xpath("/am:cards/am:card[".$this->makeFilterQuery($filters)."]/@id");
			
			if( $result === false ) return array(); // workaround for http://bugs.php.net/bug.php?id=48601
			
			$cards = array();
			foreach( $result as $card )
				$cards[] = (int)$card;
			
			return $cards;
		}

		public function CountPages(array $filters) // calculate number of pages for current card list (specified by filters)
		{
			$db = $this->getDB();

			$result = $db->xpath("/am:cards/am:card[".$this->makeFilterQuery($filters)."]/@id");

			if( $result === false ) return array(); // workaround for http://bugs.php.net/bug.php?id=48601

			return ceil(count($result) / CARDS_PER_PAGE);
		}

		/**
		 * Retrieves data for the specified card ids.
		 * Can be used in combination with CCards::GetList().
		 * The same card id may be specified multiple times.
		 * The result will use the same keys and key order as the input.
		 * @param array $ids an array of card ids to retrieve
		 * @param int $page current page number (optional parameter)
		 * @return array an array of the requested cards' data
		*/
		public function GetData(array $ids, $page = -1)
		{
			$db = $this->getDB();

			// initialize on first use
			if( $this->cache === false )
			{
				// since xpath is too slow for this task, just grab everything and process it in php
				$cards = $db->xpath("/am:cards/am:card");
				if( $cards === false ) return false;
				
				$this->cache = array();
				foreach( $cards as $card )
				{
					$data['id']       = (int)$card->attributes()->id;
					$data['name']     = (string)$card->name;
					$data['class']    = (string)$card->class;
					$data['bricks']   = (int)$card->cost->bricks;
					$data['gems']     = (int)$card->cost->gems;
					$data['recruits'] = (int)$card->cost->recruits;
					$data['modes']    = (int)$card->modes;
					$data['level']    = (int)$card->level;
					$data['keywords'] = (string)$card->keywords;
					$data['effect']   = (string)$card->effect;
					$data['code']     = (string)$card->code;
					$data['created']  = (string)$card->created;
					$data['modified'] = (string)$card->modified;
					$this->cache[$data['id']] = $data;
				}
			}

			$cards = $this->cache;
			$out = $names = array();
			foreach( $ids as $index => $id )
			{
				if( !isset($cards[$id]) )
					return NULL; // nonexistent card
				
				$out[$index] = $cards[$id];
				if ($page > -1) $names[$cards[$id]['name']] = $id; // map card names to ids
			}

			if ($page > -1) // retrieve current page of the card list
			{
				$out = array();
				ksort($names, SORT_STRING);
				$names = array_slice($names, $page * CARDS_PER_PAGE, CARDS_PER_PAGE);
				foreach ($names as $card_id) $out[] = $cards[$card_id];
			}

			return $out;
		}
		
		// returns distinct levels that are less or equal to specified level
		public function Levels($level = -1)
		{
			$levels = array();
			
			$cond = ($level >= 0) ? "[am:level <= ".$level."]" : '';
			
			$db = $this->getDB();
			$result = $db->xpath("/am:cards/am:card".$cond."/am:level");
			if( $result === false ) return $levels;

			foreach($result as $entry)
			{
				$card_level = (int)$entry;
				$levels[$card_level] = $card_level;
			}
			
			sort($levels);

			return $levels;
		}
		
		// returns all distinct keywords
		public function Keywords()
		{
			$keywords = array();
			
			$db = $this->getDB();
			$result = $db->xpath("/am:cards/am:card[am:keywords != '']/am:keywords");
			if( $result === false ) return $keywords;
			
			foreach($result as $entry)
			{
				$words = explode(",", $entry); // split individual keywords
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
		
		public function ListCreationDates() // returns list of distinct creation dates
		{
			$db = $this->getDB();
			
			$result = $db->xpath('/am:cards/am:card[@id > 0]/am:created');
			if( $result === false ) return false;
			$dates = array();
			
			foreach($result as $created) $dates[] = (string)$created;
			
			$dates = array_unique($dates);
			rsort($dates);
			
			return $dates;
		}
		
		public function ListModifyDates() // returns list of distinct modification dates
		{
			$db = $this->getDB();
			
			$result = $db->xpath('/am:cards/am:card[@id > 0]/am:modified');
			if( $result === false ) return false;
			$dates = array();
			
			foreach($result as $modified) $dates[] = (string)$modified;
			
			$dates = array_unique($dates);
			rsort($dates);
			
			return $dates;
		}
		
		// returns token keywords
		public function TokenKeywords()
		{
			return array('Alliance', 'Barbarian', 'Brigand', 'Beast', 'Burning', 'Holy', 'Mage', 'Soldier', 'Titan', 'Undead', 'Unliving');
		}
	}
	
	
	class CCard
	{
		private $Cards;
		
		public $ID;
		public $Name;
		public $Class;
		public $Bricks;
		public $Gems;
		public $Recruits;
		public $Modes;
		public $Level;
		public $Keywords;
		public $Effect;
		public $Code;
		public $Created;
		public $Modified;
		
		public function __construct($cardid, CCards &$Cards)
		{
			$this->Cards = &$Cards;
			
			$data = $Cards->GetData(array($cardid));
			if( $data === false )
				$data = array('id'=>$cardid, 'name'=>'Invalid Card', 'class'=>'None', 'bricks'=>0, 'gems'=>0, 'recruits'=>0, 'modes'=>0, 'level'=>0, 'keywords'=>'', 'effect'=>'', 'code'=>'', 'created'=>'', 'modified'=>'');

			// initialize self
			$this->SetData($data[0]);
		}
		
		public function IsPlayAgainCard()
		{
			return ($this->HasKeyWord("Quick") or $this->HasKeyWord("Swift"));
		}
		
		public function HasKeyword($keyword)
		{
			if( $keyword != "any" )
				return (strpos($this->Keywords, $keyword) !== FALSE);
			else // search for any keywords
				return ($this->Keywords != "");
		}
		
		public function GetResources($type = '')
		{
			if ($type != '')
				$resource = $this->$type;
			else
			{
				$resources = array('Bricks' => 0, 'Gems' => 0, 'Recruits' => 0);
				$resource = 0;
				foreach ($resources as $r_name => $r_value)
					$resource+= $this->$r_name;
			}
		
			return $resource;
		}
		
		public function GetData()
		{
			$data['id']       = $this->ID;
			$data['name']     = $this->Name;
			$data['class']    = $this->Class;
			$data['bricks']   = $this->Bricks;
			$data['gems']     = $this->Gems;
			$data['recruits'] = $this->Recruits;
			$data['modes']    = $this->Modes;
			$data['level']    = $this->Level;
			$data['keywords'] = $this->Keywords;
			$data['effect']   = $this->Effect;
			$data['code']     = $this->Code;
			$data['created']  = $this->Created;
			$data['modified'] = $this->Modified;

			return $data;
		}

		private function SetData($data)
		{
			$this->ID       = $data['id'];
			$this->Name     = $data['name'];
			$this->Class    = $data['class'];
			$this->Bricks   = $data['bricks'];
			$this->Gems     = $data['gems'];
			$this->Recruits = $data['recruits'];
			$this->Modes    = $data['modes'];
			$this->Level    = $data['level'];
			$this->Keywords = $data['keywords'];
			$this->Effect   = $data['effect'];
			$this->Code     = $data['code'];
			$this->Created  = $data['created'];
			$this->Modified = $data['modified'];
		}

		///
		/// Returns 'id' data field
		/// @return int card id
		public function Id()
		{
			return $this->ID;
		}

		///
		/// Returns 'class' data field
		/// @return string card class
		public function GetClass()
		{
			return $this->Class;
		}
	}
?>
