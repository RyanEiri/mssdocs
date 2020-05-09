<?php

class UserGrab {
	public	$result,
		$username,
		$admin,
		$entered_username,
		$entered_password,
		$password,
		$secretword
		;

	function Usergrab (
		$username
		) {
			//$this->db = $db;
			$this->username = $username;
			$this->grabUser();
	}
	
	function grabUser(){
		global $db;
		$sql = "SELECT password, admin, secretword FROM users WHERE username='" . $this->username . "' LIMIT 1";
		$this->result = $db->query($sql);
		$query = $this->result->fetchArray();
		if(is_array($query)) {
			extract($query[0]);
		}
		$this->password = $password;
		$this->admin = $admin;
		$this->secretword = $secretword;
	}

	function validateUser(
		$entered_username,
		$entered_password
		) {
		if ($this->password==$entered_password) {
			return $this->secretword;
		} else {
			return false;
		}
		}
}

class UserCookie {

	public	$username,
		$secretword
		;
	
	function DeleteIt(){
		if($_GET[header]=='cookieDel'){
		        setcookie('login','',time()-86400);
		        header('Location: ./login.php');
		}
	}

	function CreateIt(
		$user,
		$secret
		) {
		$this->username = $user;
		$this->SecretWord();
		if ($this->secretword==$secret) {
			setcookie('login',$user.','.md5($user.$this->secretword));
			header('Location: ./index.php');
		} else {
			header('Location: ./login.php?1');
		}
	}

	function CheckIt(){
		if ($_COOKIE['login']){
		        list($username,$hash) = preg_split('/\,/',$_COOKIE['login']);
			$this->username = $username;
			$this->SecretWord();
	                if (md5($username.$this->secretword) == $hash){
			//do nothing
				return true;
			} else { 
				return false;
			}
	        } else { 
			header('Location: ./login.php');  
		}
	}

	function SecretWord(){
		global $db;
		$sql = "SELECT secretword FROM users WHERE username='" . $this->username . "' LIMIT 1";
		$this->result = $db->query($sql);
		$query = $this->result->fetchArray();
//		extract($query[0]);
		$this->secretword = $query[0][secretword];
	}

}
	
class UserList {
	function UserList (
		) {
			global $db;
			$this->sql = "SELECT * FROM users";
			$this->result = $db->query($this->sql);
			$this->userlist = $this->result->fetchArray();
		}	
}
		
// User class. The class can be passed the basic user information from POST, and will
// allow to auto-fetch from the database in the near future.
class User {
        var     $db,
		$username,
                $password,
		$secretword,
		$admin,
		$encrypted_password,
		$userlist,
		$result
                ;

        function User(
		$db,
                $username,
                $password,
		$secretword,
		$admin
                ) {
			$this->db = &$db;
                        $this->username = $username;
                        $this->password = $password;
			$this->secretword = $secretword;
			$this->admin = $admin;
			$this->encrypted_password = sha1($this->password . $secret_word);
			$this->result = $result;
                }
	function readUser(){
		$sql = "SELECT id, username FROM user WHERE username='" . $this->username . "'";
		$this->result = &$this->db->query($sql);
	}

	function writeUser(){
		$sql = "INSERT INTO user (username, password, secretword, admin) VALUES ('" . $this->username . "','" . $this->encrypted_password . "','" . $this->secretword . "','". $this->admin . "')"; 
		$this->db->query($sql);
	}

	function fetchUser(){
		$query = $this->result->fetch();
		if (!$query) {
			return false;
		} else {
			return true;
		}
	}

}

?>
