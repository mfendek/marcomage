<?php
/*
	CLogin - authentication class
*/
?>
<?php
	class CLogin
	{
		private $db;
		public $status;
		
		// magic constants, adjust as needed :\
		private $sessiontimeout = 604800; // server-side session expiry time (in seconds)
		private $cookietimeout = 604800; // client-side session cookie expiry time (in seconds)
		
		public function __construct(CDatabase &$database)
		{
			// use the provided object to communicate with the database
			$this->db = &$database;
			$this->status = 'READY';
		}
		
		private function validate_login($username, $password)
		{
			$db = &$this->db;
			$status = &$this->status;
		
			// retrieve corresponding user info from the database and compare
			
			$result = $db->Query('SELECT `Password` FROM `logins` WHERE `Username` = "'.$db->Escape($username).'"');
			if (!$result) { $status = $db->status; return false; };
			
			if (!$result->Rows()) { $status = 'ERROR_NO_SUCH_USER'; return false; };
			
			$data = $result->Next();
			
			if (md5($password) != $data['Password']) { $status = 'ERROR_WRONG_PASSWORD'; return false; };
			
			$status = 'SUCCESS';
			return true;
		}
		
		private function validate_session($username, $sessionid)
		{
			$db = &$this->db;
			$status = &$this->status;
			
			// retrieve corresponding session info from the database and check if the session is still valid
			
			$sessionid = (int)$sessionid; // makes sure that the input is a number
			if ($sessionid == 0) { $status = 'ERROR_NO_SUCH_SESSION'; return false; }; // 0 is not a valid session id -_-`
			
			$result = $db->Query('SELECT `Last IP`, `Last Query` FROM `logins` WHERE `Username` = "'.$db->Escape($username).'" AND `SessionID` = '.$db->Escape($sessionid));
			if (!$result) { $status = $db->status; return false; };
			
			if (!$result->Rows()) { $status = 'ERROR_NO_SUCH_SESSION'; return false; };
			
			$data = $result->Next();
			
			if ($_SERVER['REMOTE_ADDR'] != $data['Last IP']) { $status = 'ERROR_WRONG_IP'; return false; };
			
			if ($_SERVER['REQUEST_TIME'] - strtotime($data['Last Query']) > $this->sessiontimeout) { $status = 'ERROR_SESSION_EXPIRED'; return false; };
			
			$status = 'SUCCESS';
			return true;
		}
		
		private function begin_session($username, $sessionid, $cookies)
		{
			$db = &$this->db;
			$status = &$this->status;
			
			// produce a session object with valid values for the specified user
			
			// first, retrieve the real, case-sensitive `Username`
			$result = $db->Query('SELECT `Username` FROM `logins` WHERE `Username` = "'.$db->Escape($username).'"');
			if (!$result) { $status = $db->status; return false; };
			if (!$result->Rows()) { $status = 'ERROR_NO_SUCH_USER'; return false; };
			$data = $result->Next();
			$username = $data['Username'];
			
			// test if a new session is needed
			if ($sessionid == 0)
			{
				// generate and store a new unitialized session for the user
				$sessionid = mt_rand(1, pow(2,31)-1);
				$result = $db->Query('UPDATE `logins` SET `SessionID` = '.$db->Escape($sessionid).', `Notification` = `Last Query` WHERE `Username` = "'.$db->Escape($username).'"');
				if (!$result) { $status = $db->status; return false; };
				
				//if ($result->Rows() == 0) { $status = 'ERROR_NO_SUCH_USER'; return false; };  // not yet implemented for UPDATE queries :|
			}
			
			// store current `Last IP` and `Last Query`, refresh cookies
			$now = time();
			$addr = $_SERVER["REMOTE_ADDR"];
			$result = $db->Query('UPDATE `logins` SET `Last IP` = "'.$db->Escape($addr).'", `Last Query` = FROM_UNIXTIME('.$db->Escape($now).') WHERE `Username` = "'.$db->Escape($username).'" AND `SessionID` = '.$db->Escape($sessionid));
			if (!$result) { $status = $db->status; return false; };
			
			//if ($result->Rows() == 0) { $status = 'ERROR_NO_SUCH_SESSION'; }; // still not implemented for UPDATE queries :(
			
			if ($cookies == 'yes' or $cookies == 'maybe') // try even if not sure
			{
				$timeout = $now + $this->cookietimeout;
				setcookie('Username', $username, $timeout);
				setcookie('SessionID', $sessionid, $timeout);
			}
			
			$hascookies = ($cookies == 'yes'); // (yes -> 1, maybe -> 0, no -> 0)
			
			$status = 'SUCCESS';
			return new CSession($username, $sessionid, $hascookies);
		}
		
		private function end_session(CSession &$session)
		{
			$db = &$this->db;
			$status = &$this->status;
			
			// remove the database entry and any stored values
			
			$result = $db->Query('UPDATE `logins` SET `SessionID` = 0 WHERE `Username` = "'.$db->Escape($session->Username()).'" AND `SessionID` = '.$db->Escape($session->SessionID()));
			if (!$result) { $status = $db->status; return false; };
			
			unset($_POST['Username']); unset($_COOKIE['Username']); setcookie('Username', false);
			unset($_POST['SessionID']); unset($_COOKIE['SessionID']); setcookie('SessionID', false);
			
			$session->__destruct();
			$session = false;
			
			$status = 'SUCCESS';
			return true;
		}
		
		public function Register($username, $password)
		{
			$db = &$this->db;
			$status = &$this->status;
			
			// insert a new user login entry into the database
			
			if ($username == '' || $password == '') { $status = 'ERROR_INVALID_VALUES'; return false; };
			
			// security: store useful information about the user NOW instead of waiting until the user logs in
			$now = time();
			$addr = $_SERVER["REMOTE_ADDR"];
			
			// TODO: flood prevention - limits the frequency of account creations per ip 
			
			$result = $db->Query('INSERT INTO `logins` (`Username`, `Password`, `Last IP`, `Last Query`) VALUES ("'.$db->Escape($username).'", "'.md5($password).'", "'.$db->Escape($addr).'", '.$db->Escape($now).')');
			if (!$result) { $status = 'ERROR_ALREADY_REGISTERED'; return false; }; // or db failure, but whatever
			
			$status = 'SUCCESS';
			return true;
		}
		
		public function Unregister($username)
		{
			$db = &$this->db;
			$status = &$this->status;
			
			// deletes the specified user from the database
			
			$result = $db->Query('DELETE FROM `logins` WHERE `Username` = "'.$db->Escape($username).'"');
			if (!$result) { $status = $db->status; return false; };
			
//			if (!$result->Rows()) { $status = 'ERROR_NO_SUCH_USER - DB_FAILURE'; return false; }; // NOT good if this happens +_+  // not yet implemented
			
			$status = 'SUCCESS';
			return true;
		}

		public function ChangePassword($username, $password)
		{
			$db = &$this->db;
			$status = &$this->status;
			
			//change password
			if ($username == '' || $password == '') { $status = 'ERROR_INVALID_VALUES'; return false; };
			$result = $db->Query('UPDATE `logins` SET `Password` = "'.md5($password).'" WHERE `Username` = "'.$db->Escape($username).'"');
			if (!$result) { $status = 'DB_ERROR'; return false; };
			
			$status = 'SUCCESS';
			return true;
		}
		
		public function Login()
		{
			// [case 1] the user is providing a name/sessionid pair via cookies
			if (isset($_COOKIE['Username']) && isset($_COOKIE['SessionID']))
			{
				//echo 'Trying to log in via session cookie ('.$_COOKIE['Username'].','.$_COOKIE['SessionID'].')...'.'<br />';
				
				// verify if the input passes the security check
				if ($this->validate_session($_COOKIE['Username'], $_COOKIE['SessionID']))
				{
					//echo 'Session is valid. Trying to resume...'.'<br />';
					return $this->begin_session($_COOKIE['Username'], $_COOKIE['SessionID'], 'yes'); // cookies are working
				}
				else
				{
					//echo 'Session invalid or internal failure, error: '.$this->status.'<br />';
					
					// something failed or the session is invalid; do not proceed
					unset($_POST["Username"]); unset($_COOKIE["Username"]); setcookie("Username", false);
					unset($_POST["SessionID"]); unset($_COOKIE["SessionID"]); setcookie("SessionID", false);
					return false;
				}
			}
			else // [case 2] the user is providing a name/sessionid pair via POST, probably because he/she doesn't use cookies >_>
			if (isset($_POST['Username']) && isset($_POST['SessionID']))
			{
				//echo 'Trying to log in via username/sessionid ('.$_POST['Username'].','.$_POST['SessionID'].')...'.'<br />';
				
				// compare the values against the database to see if they are valid
				if ($this->validate_session($_POST['Username'], $_POST['SessionID']))
				{
					//echo 'Session is valid. Trying to resume...'.'<br />';
					return $this->begin_session($_POST['Username'], $_POST['SessionID'], 'no'); // assume cookies are disabled
				}
				else
				{
					//echo 'Session invalid or internal failure, error: '.$this->status.'<br />';
					
					// something failed or the session is invalid; do not proceed
					unset($_POST["Username"]);
					unset($_POST["SessionID"]);
					return false;
				}
			}
			else // [case 3] the user is providing a username/password pair for a new session
			if (isset($_POST['Username']) && isset($_POST['Password']))
			{
				//echo 'Trying to log in via username/password ('.$_POST['Username'].','.$_POST['Password'].')...'.'<br />';
				
				// compare the values against the database to see if they are valid
				if ($this->validate_login($_POST['Username'], $_POST['Password']))
				{
					//echo 'Login accepted. Will try to generate a sesion...'.'<br />';
					/*
					if (isset($_POST['Remember']) && $_POST['Remember'] == 'yes')
						$hascookies = 'maybe'; // if allowed, begin_session() will probe the browser for cookie capability
					else
						$hascookies = 'no'; // cookie usage is explicitly disabled
					*/
					$hascookies = 'maybe';
					
					return $this->begin_session($_POST['Username'], 0, $hascookies);
				}
				else
				{
					//echo 'Login invalid or internal failure, error: '.$this->status.'<br />';
					
					// if not, a step in the authentication process failed; do not proceed
					return false;
				}
			}
			
			else // [case 4] the user did not provide any means of identification
			{
				// fails by definition
				return false;
			}
		}
		
		public function Logout(CSession &$session)
		{
			$this->end_session($session);
		}
				
	} // class CLogin
	
	
	class CSession
	{
		private $Username;
		private $SessionID;
		private $hasCookies;
		
		public function __construct($username, $sessionid, $cookies)
		{
			$this->Username = $username;
			$this->SessionID = $sessionid;
			$this->hasCookies = $cookies;
		}
		
		public function __destruct()
		{
			$this->Username = '';
			$this->SessionID = 0;
		}
		
		public function Username() { return $this->Username; }
		public function SessionID() { return $this->SessionID; }
		public function hasCookies() { return $this->hasCookies; }
	}
?>
