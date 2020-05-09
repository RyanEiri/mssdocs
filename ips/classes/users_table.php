<?php

// UserGrab, and UserCookie classes for acting on validation of userswhen signing in, not for administrating user accounts. 
class UserGrab {
	private	$result,
		$username,
		$entered_username,
		$entered_password,
		$password,
		$secretword
		;
	public	$admin;

	function Usergrab (
		$username
		) {
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
		if(isset($password)){
			$this->password = $password;
		}
		if(isset($admin)){
			$this->admin = $admin;
		}
		if(isset($secretword)){
			$this->secretword = $secretword;
		}
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

    public	$username;
    private	$secretword;
	
	function DeleteIt(){
		if(isset($_GET['header'])){
			if ($_GET['header']==='cookieDel') {
		        	setcookie('login','',time()-86400);
				setcookie('vesturheimsrit','',time()-86400);
			        header('Location: ./login.php');
			}
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
			setcookie('vesturheimsrit','194989088');
			header('Location: ./index.php');
		} else {
			header('Location: ./login.php?1');
		}
	}

	function CheckIt(){
		if (isset($_COOKIE['login'])){
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
		$this->secretword = $query[0]['secretword'];
	}

}
	
// UserList and ActOnSingleUser classes for administration of user accounts. 
class UserList {
	function UserList (
		) {
			global $db;
			$this->sql = "SELECT * FROM users";
			$this->result = $db->query($this->sql);
			$this->userlist = $this->result->fetchArray();
		}	
}
		
// ActOnSingleUser class. For adding and changing a user account. 
class ActOnSingleUser {

        function ActOnSingleUser (
                ) {
			global $db;
			if(isset($_POST['id'])){
			  $this->id = $_POST['id'];
			  $this->sql = "SELECT id, username, password, secretword, admin FROM users WHERE id='" . $this->id ."'";
			  $this->result = $db->query($this->sql);
			  $this->user_entry = $this->result->fetchArray();
			  $this->password = $this->user_entry[0]['password'];
			  $this->secretword = $this->user_entry[0]['secretword'];
			  $this->encrypted_password = sha1($this->password . $this->secretword);
			}
                }

	function addUser(){
		global $db;
		$this->username = $_POST['username'];
		$this->username = addslashes($this->username);
		if(!isset($this->duplicate)){
		  $this->duplicateSQL = "SELECT username FROM users WHERE username='" . $this->username . "'";
		  $this->duplicateResult = $db->query($this->duplicateSQL);
		  $this->duplicate = $this->duplicateResult->fetchArray();
		  $this->duplicate = $this->duplicate[0]['username'];
		}
		$this->password = $_POST['password'];
		$this->password = addslashes($this->password);
		$this->secretword = $_POST['secretword'];
		$this->secretword = addslashes($this->secretword);
		$this->admin = $_POST['admin'];
		$this->addSQL = "INSERT INTO users (username, password, secretword, admin) VALUES ('$this->username', '$this->password', '$this->secretword', '$this->admin')";	
		//$this->addresult = $db->query($this->addsql) or die ('Insert statement failed: Entry not added');
		if($_POST['username'] === $this->duplicate){
			$this->duplicate = null;
			return false;
		} else {
			$this->addresult = $db->query($this->addSQL);
			$this->entryid = $this->addresult->getId();
		}
	}

	function changeUser(){
		global $db;
		$this->change_id = $_POST['id'];
		$this->username = $_POST['username'];
		$this->username = addslashes($this->username);
		$this->password = $_POST['password'];
		$this->password = addslashes($this->password);
		$this->secretword = $_POST['secretword'];
		$this->secretword = addslashes($this->secretword);
		$this->admin = $_POST['admin'];
		$this->changeSQL = "UPDATE users SET `username`='$this->username', `password`='$this->password', `secretword`='$this->secretword', `admin`='$this->admin' WHERE `id`='$this->id'";
		$db->query($this->changeSQL) or die ('Update statement failed: Entry not updated');
	}

	function fetchUser(){
		$query = $this->result->fetch();
		if (!$query) {
			return false;
		} else {
			return true;
		}
	}

	function removeUser(){
		global $db;
		$this->remove_id = $_POST['id'];
		$this->removeSQL = "DELETE FROM users WHERE id='" . $this->remove_id . "'";
		$db->query($this->removeSQL) or die ('Delete statement failed: Entry not deleted');
	}
}

?>
