<?php
/*
	CHtml - generates pages for the web section
*/
?>
<?php
	class CHtml
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
		
		public function GetSection($section)
		{
			$db = $this->db;
			$result = $db->Query('SELECT `Date`, `Title`, `Content` FROM `html` WHERE `Section` = "'.$section.'" ORDER BY `Date` DESC');
			
			$list = array();
			
			if ($result && $result->Rows())
				while ($data = $result->Next())
					$list[] = $data;
			
			return $list;
		}
		
		public function MainPage()
		{
			$content = '<h2>Welcome to MArcomage</h2>'."\n";
			$content.= '<hr />'."\n";
			
			$data = $this->GetSection("main");
			$content.= @$data[0]['Content'];
			
			return '<div>'."\n".$content."\n".'</div>'."\n";	
		}
		
		public function NewsPage()
		{
			$content = '<h2>News and update history</h2>'."\n";
			
			foreach ($this->GetSection("news") as $data)
			{
				$content.= "\n".'<hr />'."\n";
				$content.= "\n";
				$content.= '<div class="date_time">'.$data['Date'].' CET</div>'."\n";
				$content.= '<h3>'.$data['Title'].'</h3>'."\n";
				$content.= '<div>'.$data['Content'].'</div>'."\n";
			}

			$content.= "\n".'<hr />'."\n";
			
			return '<div>'."\n".$content."\n".'</div>'."\n";	
		}
		
		public function ModCardsPage()
		{
			$content = '<h2>Latest modified cards and balance changes</h2>'."\n";
			$content.= '<hr />'."\n";
			
			foreach ($this->GetSection("modified") as $data)
			{
				$content.= '<div class="date_time">'.$data['Date'].'</div>'."\n";
				$content.= '<h3>'.$data['Title'].'</h3>'."\n";
				$content.= '<div>'.$data['Content'].'</div>'."\n";
				$content.= '<hr/>'."\n";
			}
			
			return '<div>'."\n".$content."\n".'</div>'."\n";
		}
		
		public function HelpPage()
		{
			$content = '<h2>Game manual</h2>'."\n";
			$content.= '<hr />'."\n";
			
			$data = $this->GetSection("help");
			$content.= @$data[0]['Content'];
			
			return '<div>'."\n".$content."\n".'</div>'."\n";
		}
		
		public function FaqPage()
		{
			$content = '<h2>Frequently Asked Questions</h2>'."\n";
			$content.= '<hr />'."\n";
			
			$data = $this->GetSection("faq");
			$content.= @$data[0]['Content'];
			
			return '<div>'."\n".$content."\n".'</div>'."\n";
		}
		
		public function Credits()
		{
			$content = '<h2>Hall of fame</h2>'."\n";
			$content.= '<hr />'."\n";
			
			$content.= '<p style="text-align: center; font-size:large;">'."\n";
			$content.= "\t".'<img style="float: left; margin-top: 0ex" src="img/unicorn_left.jpg" alt="unicorn"/>'."\n";
			$content.= "\t".'<img style="float: right; margin-top: 0ex" src="img/unicorn_right.jpg" alt="unicorn"/>'."\n";
			$content.= 'Names of all people who contributed to this site in some way, will be forever remembered in this section.'."\n";
			$content.= '</p>'."\n";
			$content.= '<div style="clear: both;"></div>'."\n";		
			
			$data = $this->GetSection("credits");
			$content.= @$data[0]['Content'];
			
			return '<div>'."\n".$content."\n".'</div>'."\n";
		}
	}
?>
