<?php

// UserGrab, and UserCookie classes for acting on validation of users when signing in, not for administrating user accounts.
class UserGrab {
	private	$result,
		$username,
		$entered_username,
		$entered_password,
		$password,
		$hashed_password,
		$secretword
		;
	public	$admin;

	function __construct(
		$username
		) {
			$this->username = $username;
			$this->grabUser();
	}

	private function grabUser(){
		global $db;
		$sql = "SELECT password, admin, secretword FROM users WHERE username=? LIMIT 1";
		$params = [['type' => 's', 'value' => $this->username]];
		$this->result = $db->query($sql, $params);
		$query = $this->result->fetchArray();
		if(is_array($query)) {
			extract($query[0]);
		}
		if(isset($password)){
			$this->hashed_password = $password;
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
		if (password_verify($entered_password, $this->hashed_password)) {
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
		    setcookie('login', '', time()-86400, '/', $_SERVER['SERVER_NAME'], true, true);
				setcookie(GATE_COOKIE_NAME, '', time()-86400, '/', $_SERVER['SERVER_NAME'], true, true);
				session_destroy();
			  header('Location: ./login.php');
			  exit;
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
			session_regenerate_id(true);
			setcookie('login', $user.','.hash("sha512", $user.$this->secretword), 0, '/', $_SERVER['SERVER_NAME'], true, true);
			setcookie(GATE_COOKIE_NAME, GATE_COOKIE_VALUE, 0, '/', $_SERVER['SERVER_NAME'], true, true);
			header('Location: ./index.php');
			exit;
		} else {
			header('Location: ./login.php?1');
			exit;
		}
	}

	function CheckIt(){
		if (isset($_COOKIE['login'])){
		  list($username,$hash) = preg_split('/\,/', $_COOKIE['login']);
			$this->username = $username;
			$this->SecretWord();
	    if (hash_equals(hash("sha512", $username.$this->secretword), $hash)){
				return true;
			} else {
				return false;
			}
	  } else {
			header('Location: ./login.php');
			exit;
		}
	}

	private function SecretWord(){
		global $db;
		$sql = "SELECT secretword FROM users WHERE username=? LIMIT 1";
		$params = [['type' => 's', 'value' => $this->username]];
		$this->result = $db->query($sql, $params);
		$query = $this->result->fetchArray();
		$this->secretword = $query[0]['secretword'];
	}

}

// UserList and ActOnSingleUser classes for administration of user accounts.
class UserList {

	function __construct(
		) {
			global $db;
			$this->sql = "SELECT * FROM users";
			$this->result = $db->query($this->sql);
			$this->userlist = $this->result->fetchArray();
		}
}

// ActOnSingleUser class. For adding and changing a user account.
class ActOnSingleUser {
	private $select_sql,
		$select_result,
		$password,
		$add_sql,
		$change_sql,
		$secretword
		;
	public $id,
		$user_entry,
		$email,
		$mysqlError,
		$duplicateError
		;

	function __construct(
                ) {
		global $db;
		if(isset($_SESSION['user_id'])){
		  $this->id = (int)$_SESSION['user_id'];
		  $id_param = [['type' => 'i', 'value' => $this->id]];
		  $this->select_sql      = "SELECT id, username, email, admin FROM users WHERE id=?";
		  $this->secretword_sql  = "SELECT secretword FROM users WHERE id=?";
		  $this->password_sql    = "SELECT password FROM users WHERE id=?";
		  $this->select_result   = $db->query($this->select_sql, $id_param);
		  $this->secretword_result = $db->query($this->secretword_sql, $id_param);
		  $this->password_result = $db->query($this->password_sql, $id_param);
		  $this->user_entry      = $this->select_result->fetchArray();
		  $this->secretword_entry = $this->secretword_result->fetchArray();
		  $this->password_entry  = $this->password_result->fetchArray();
		  $this->password        = $this->password_entry[0]['password'];
		  $this->secretword      = $this->secretword_entry[0]['secretword'];
		  $this->email           = $this->user_entry[0]['email'];
		}
	}

	function addUser(){
		global $db;
		$this->username = $_SESSION['username'];
		$this->email = $_SESSION['email'];
		if(!isset($this->duplicate)){
		  $this->duplicateSQL = "SELECT username FROM users WHERE username=?";
		  $dup_param = [['type' => 's', 'value' => $this->username]];
		  $this->duplicateResult = $db->query($this->duplicateSQL, $dup_param);
		  $this->duplicate = $this->duplicateResult->fetchArray();
		  $this->duplicate = $this->duplicate[0]['username'];
		}
		$this->password = $_SESSION['password'];
		$this->password = password_hash($this->password, PASSWORD_DEFAULT);
		$this->secretword = getToken(60);
		$this->admin = (int)$_SESSION['admin'];
		$this->addSQL = "INSERT INTO users (username, email, password, secretword, admin) VALUES (?, ?, ?, ?, ?)";
		$add_params = [
			['type' => 's', 'value' => $this->username],
			['type' => 's', 'value' => $this->email],
			['type' => 's', 'value' => $this->password],
			['type' => 's', 'value' => $this->secretword],
			['type' => 'i', 'value' => $this->admin],
		];
		if($this->username === $this->duplicate){
			$this->duplicate = null;
			$this->duplicateError = true;
			return false;
		} else {
			$this->addresult = $db->query($this->addSQL, $add_params);
			$this->entryid = $this->addresult->getId();
			if($this->addresult->isError()) {
				$this->mysqlError = $this->addresult->queryErrorMessage();
				return false;
			} else {
				return true;
			}
		}
	}

	function changeUser(){
		global $db;
		$this->username = $_SESSION['username'];
		$this->email = $_SESSION['email'];
		$this->password = $_SESSION['password'];
		$this->password = password_hash($this->password, PASSWORD_DEFAULT);
		$this->secretword = getToken(60);
		$this->admin = (int)$_SESSION['admin'];
		$this->change_sql = "UPDATE users SET username=?, email=?, password=?, secretword=?, admin=? WHERE id=?";
		$change_params = [
			['type' => 's', 'value' => $this->username],
			['type' => 's', 'value' => $this->email],
			['type' => 's', 'value' => $this->password],
			['type' => 's', 'value' => $this->secretword],
			['type' => 'i', 'value' => $this->admin],
			['type' => 'i', 'value' => $this->id],
		];
		$this->changeresult = $db->query($this->change_sql, $change_params);
		if($this->changeresult->isError()) {
			$this->mysqlError = $this->changeresult->queryErrorMessage();
			return false;
		} else {
			return true;
		}
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
		$this->remove_id = (int)$_POST['id'];
		$this->removeSQL = "DELETE FROM users WHERE id=?";
		$remove_param = [['type' => 'i', 'value' => $this->remove_id]];
		$result = $db->query($this->removeSQL, $remove_param);
		if($result->isError()) {
			die('Delete statement failed: Entry not deleted');
		}
	}
}

?>
