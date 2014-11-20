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
		
		public function getCard($cardid)
		{
			return new CCard($cardid, $this);
		}

		///
		/// Filters cards according to the provided filtering instructions.
		/// @param array $filters (optional) an array of chosen filters and their parameters
		/// Available filters are:
		/// <ul>
		/// <li> 'name'     => { <search_substring> }, queries `Name` </li>
		/// <li> 'class'    => { None | Common | Uncommon | Rare }, queries `Class` </li>
		/// <li> 'keyword'  => { Any keyword | No keywords | <a specific keyword> }, queries `Keywords` </li>
		/// <li> 'cost'     => { Red | Blue | Green | Zero | Mixed }, queries `Bricks`, `Gems` and `Recruits` </li>
		/// <li> 'advanced' => { <a specific substring> }, queries `Effect` </li>
		/// <li> 'support'  => { Any keyword | No keywords | <a specific keyword> }, queries `Effect` </li>
		/// <li> 'level'    => { <specific_level> }, queries `level` </li>
		/// <li> 'level_op' => { = | <= }, additional parameter for `level` defaults to '=' </li>
		/// </ul>
		/// @return array ids for cards that match the filters
		public function getList(array $filters = array())
		{
			// list all cards
			$cards = $this->getData();

			// remove default from cards
			unset($cards[0]);

			// filter cards
			$out = array();
			foreach ($cards as $cardId => $card) {
				// name filter
				if (isset($filters['name']) and strpos($card['name'], $filters['name']) === false) {
					continue;
				}

				// rarity filter
				if (isset($filters['class']) and $card['class'] != $filters['class']) {
					continue;
				}

				// keyword filter
				if (isset($filters['keyword'])) {
					// case 1: any keywords
					if ($filters['keyword'] == 'Any keyword') {
						if ($card['keywords'] == '') {
							continue;
						}
					}
					// case 2: no keywords
					elseif ($filters['keyword'] == 'No keywords') {
						if ($card['keywords'] != '') {
							continue;
						}
					}
					// case 3: one specific keyword
					else {
						if (strpos($card['keywords'], $filters['keyword']) === false) {
							continue;
						}
					}
				}

				// resource cost filter
				if (isset($filters['cost'])) {
					// case 1: bricks cost only
					if ($filters['cost'] == 'Red') {
						if ($card['bricks'] == 0 or $card['gems'] > 0 or $card['recruits'] > 0) {
							continue;
						}
					}
					// case 2: gems cost only
					elseif ($filters['cost'] == 'Blue') {
						if ($card['bricks'] > 0 or $card['gems'] == 0 or $card['recruits'] > 0) {
							continue;
						}
					}
					// case 3: recruits cost only
					elseif ($filters['cost'] == 'Green') {
						if ($card['bricks'] > 0 or $card['gems'] > 0 or $card['recruits'] == 0) {
							continue;
						}
					}
					// case 4: zero cost only
					elseif ($filters['cost'] == 'Zero') {
						if ($card['bricks'] > 0 or $card['gems'] > 0 or $card['recruits'] > 0) {
							continue;
						}
					}
					// case 5: mixed cost only
					elseif ($filters['cost'] == 'Mixed') {
						if ((($card['bricks'] > 0) + ($card['gems'] > 0) + ($card['recruits'] > 0)) < 2) {
							continue;
						}
					}
				}

				// advanced filter
				if (isset($filters['advanced']) and strpos($card['effect'], $filters['advanced']) === false and strpos($card['effect'], strtolower($filters['advanced'])) === false) {
					continue;
				}

				// support keyword filter
				if (isset($filters['support'])) {
					// case 1: any keywords
					if ($filters['support'] == 'Any keyword') {
						if (strpos($card['effect'], '<b>') === false) {
							continue;
						}
					}
					// case 2: no keywords
					elseif ($filters['support'] == 'No keywords') {
						if (strpos($card['effect'], '<b>') !== false) {
							continue;
						}
					}
					// case 3: one specific keyword
					else {
						if (strpos($card['effect'], '<b>'.$filters['support']) === false) {
							continue;
						}
					}
				}

				// date created filter
				if (isset($filters['created']) and $card['created'] != $filters['created']) {
					continue;
				}

				// date modified filter
				if (isset($filters['modified']) and $card['modified'] != $filters['modified']) {
					continue;
				}

				// level filter
				if (isset($filters['level'])) {
					// determine operator
					$operator = (isset($filters['level_op']) and in_array($filters['level_op'], array('=', '<='))) ? $filters['level_op'] : '<=';

					// case 1: equal
					if ($operator == '=') {
						if ($card['level'] != $filters['level']) {
							continue;
						}
					}
					// case 2: default operator (less or equal)
					else {
						if ($card['level'] > $filters['level']) {
							continue;
						}
					}
				}

				$out[] = $cardId;
			}

			return $out;
		}

		///
		/// Calculate number of pages for current card list (specified by filters)
		/// @param array $filters filters
		/// @return int pages if operation was successful, false otherwise
		public function countPages(array $filters)
		{
			$result = $this->getList($filters);
			if ($result === false) {
				return array(); // workaround for http://bugs.php.net/bug.php?id=48601
			}

			return ceil(count($result) / CARDS_PER_PAGE);
		}

		///
		/// Retrieves data for the specified card ids.
		/// Can be used in combination with Cards::GetList().
		/// The same card id may be specified multiple times.
		/// The result will use the same keys and key order as the input.
		/// @param array $ids an array of card ids to retrieve
		/// @return array an array of the requested cards' data
		public function getData(array $ids = array())
		{
			// initialize on first use
			if ($this->cache === false) {
				$db = $this->getDb();

				// since xpath is too slow for this task, just grab everything and process it in php
				$cards = $db->xpath('/am:cards/am:card');
				if ($cards === false) {
					return false;
				}

				$this->cache = $default = array();
				foreach ($cards as $card) {
					$data = array();

					// mandatory data
					$data['id']       = (int)$card->attributes()->id;
					$data['name']     = (string)$card->name;
					$data['class']    = (string)$card->class;

					// optional data
					foreach (['bricks', 'gems', 'recruits'] as $resource) {
						if (isset($card->cost->$resource)) {
							$data[$resource] = (int)$card->cost->$resource;
						}
					}

					if (isset($card->modes)) {
						$data['modes'] = (int)$card->modes;
					}

					$data['level'] = (int)$card->level;

					if (isset($card->keywords)) {
						$data['keywords'] = (string)$card->keywords;
					}

					$data['effect']   = (string)$card->effect;
					$data['code']     = (string)$card->code;
					$data['created']  = (string)$card->created;
					$data['modified'] = (string)$card->modified;

					// case 1: default card - store default data
					if ($data['id'] == 0) {
						$default = $data;
					}
					// case 2: standard card - merge data with default
					else {
						$data = array_merge($default, $data);
					}

					$this->cache[$data['id']] = $data;
				}
			}

			// return all cards in case no IDs are specified
			if (count($ids) == 0) {
				return $this->cache;
			}

			// match card data with specified card ids
			$cards = $this->cache;
			$out = $names = array();
			foreach ($ids as $index => $id) {
				// check if specified card has matching data
				if (!isset($cards[$id])) {
					return false; // nonexistent card
				}

				$out[$index] = $cards[$id];
			}

			return $out;
		}
		
		///
		/// Returns distinct levels that are less or equal to specified level which are present in the card database
		/// @param int $level (optional) level
		/// @return array levels if operation was successful, false otherwise
		public function levels($level = -1)
		{
			$filter = ($level >= 0) ? array('level' => $level) : array();

			$result = $this->getData($this->getList($filter));
			if ($result === false) {
				return array();
			}

			$levels = array();
			foreach ($result as $card) {
				$card_level = $card['level'];
				$levels[$card_level] = $card_level;
			}

			sort($levels);

			return $levels;
		}
		
		///
		/// Returns all distinct keywords
		/// @return array keywords if operation was successful, false otherwise
		public function keywords()
		{
			$result = $this->getData($this->getList(['keyword' => 'Any keyword']));
			if ($result === false) {
				return array();
			}

			$keywords = array();
			foreach ($result as $card) {
				$entry = $card['keywords'];

				// split individual keywords
				$words = explode(",", $entry);

				foreach ($words as $word) {
					// remove keyword parameter if present
					$word = preg_split("/ \(/", $word, 0);
					$word = $word[0];

					// remove duplicates
					$keywords[$word] = $word;
				}
			}

			sort($keywords);

			return $keywords;
		}
		
		///
		/// Returns list of distinct creation dates present in the card database
		/// @return array dates if operation was successful, false otherwise
		public function listCreationDates()
		{
			$result = $this->getData($this->getList());
			if ($result === false) {
				return false;
			}

			$dates = array();
			foreach ($result as $card) {
				$dates[] = $card['created'];
			}

			// remove duplicates
			$dates = array_unique($dates);
			rsort($dates);

			return $dates;
		}
		
		///
		/// Returns list of distinct modification dates present in the card database
		/// @return array dates if operation was successful, false otherwise
		public function listModifyDates()
		{
			$result = $this->getData($this->getList());
			if ($result === false) {
				return false;
			}

			$dates = array();
			foreach ($result as $card) {
				$dates[] = $card['modified'];
			}

			// remove duplicates
			$dates = array_unique($dates);
			rsort($dates);

			return $dates;
		}
		
		// returns token keywords
		public function tokenKeywords()
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
			
			$data = $Cards->getData(array($cardid));
			if( $data === false )
				$data = array('id'=>$cardid, 'name'=>'Invalid Card', 'class'=>'None', 'bricks'=>0, 'gems'=>0, 'recruits'=>0, 'modes'=>0, 'level'=>0, 'keywords'=>'', 'effect'=>'', 'code'=>'', 'created'=>'', 'modified'=>'');

			// initialize self
			$this->setData($data[0]);
		}
		
		public function isPlayAgainCard()
		{
			return ($this->hasKeyword("Quick") or $this->hasKeyword("Swift"));
		}
		
		public function hasKeyword($keyword)
		{
			if( $keyword != "any" )
				return (strpos($this->Keywords, $keyword) !== FALSE);
			else // search for any keywords
				return ($this->Keywords != "");
		}
		
		public function getResources($type = '')
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
		
		public function getData()
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

		private function setData($data)
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
		public function id()
		{
			return $this->ID;
		}

		///
		/// Returns 'class' data field
		/// @return string card class
		public function getClass()
		{
			return $this->Class;
		}
	}
?>
