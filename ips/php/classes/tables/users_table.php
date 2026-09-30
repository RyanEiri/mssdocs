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
    private	$result;

	// Cookie attributes shared by sign-in and sign-out. `secure` follows SITE_ENV when a site
	// defines it (dev stacks serve plain HTTP, where browsers silently drop `secure` cookies);
	// sites that don't define it keep the historical always-secure behaviour. SameSite=Lax
	// keeps the login cookie off cross-site POSTs (it previously had no SameSite attribute).
	private static function cookieOptions($expires){
		return [
			'expires'  => $expires,
			'path'     => '/',
			'domain'   => $_SERVER['SERVER_NAME'],
			'secure'   => defined('SITE_ENV') ? SITE_ENV === 'production' : true,
			'httponly' => true,
			'samesite' => 'Lax',
		];
	}

	function DeleteIt(){
		if(isset($_GET['header'])){
			if ($_GET['header']==='cookieDel') {
		    setcookie('login', '', self::cookieOptions(time()-86400));
				setcookie(GATE_COOKIE_NAME, '', self::cookieOptions(time()-86400));
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
			setcookie('login', $user.','.hash("sha512", $user.$this->secretword), self::cookieOptions(0));
			setcookie(GATE_COOKIE_NAME, GATE_COOKIE_VALUE, self::cookieOptions(0));
			header('Location: ./index.php');
			exit;
		} else {
			header('Location: ./login.php?1');
			exit;
		}
	}

	function CheckIt(){
		if (isset($_COOKIE['login'])){
			// Peek() also rejects a cookie for a nonexistent user (whose "secretword" would
			// otherwise hash as an empty string, letting sha512(username) forge a login).
			if ($this->Peek() !== false) {
				return true;
			}
			// A stale cookie (the password was changed) or another site's cookie on the same host
			// would otherwise leave a page load blank. Send page loads to the login form;
			// POST endpoints still get false so they can answer in JSON.
			if ($_SERVER['REQUEST_METHOD'] === 'GET') {
				header('Location: ./login.php');
				exit;
			}
			return false;
	  } else {
			header('Location: ./login.php');
			exit;
		}
	}

	// Non-redirecting counterpart to CheckIt(), for endpoints that also serve anonymous
	// visitors. Returns the username only for a well-formed cookie whose user exists,
	// has a non-empty secretword, and whose hash matches.
	function Peek(){
		global $db;
		if (!isset($_COOKIE['login']) || !is_string($_COOKIE['login'])) {
			return false;
		}
		$parts = explode(',', $_COOKIE['login'], 2);
		if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
			return false;
		}
		list($username, $hash) = $parts;
		$params = [['type' => 's', 'value' => $username]];
		$result = $db->query("SELECT secretword FROM users WHERE username=? LIMIT 1", $params);
		$rows = $result->fetchArray();
		if (!is_array($rows) || !isset($rows[0]['secretword']) || $rows[0]['secretword'] === '') {
			return false;
		}
		if (!hash_equals(hash("sha512", $username.$rows[0]['secretword']), $hash)) {
			return false;
		}
		$this->username = $username;
		$this->secretword = $rows[0]['secretword'];
		return $username;
	}

	// Per-user CSRF token for state-changing endpoints. Only meaningful after a successful
	// Peek()/CheckIt(); derived from the user's secretword so it can't be guessed.
	function CsrfToken(){
		return hash_hmac('sha256', 'iv-edit-csrf', $this->secretword);
	}

	// null if this is an acceptable state-changing request, else [http status, message].
	// Shared by RequireCsrf() below and by any JSON endpoint that needs the same checks.
	static function WriteRequestProblem($token, $expect_json){
		if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
			return [405, 'POST required'];
		}
		if ($expect_json && stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== 0) {
			return [415, 'application/json required'];
		}
		$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
		$host = $origin !== '' ? parse_url($origin, PHP_URL_HOST) : null;
		$port = $origin !== '' ? parse_url($origin, PHP_URL_PORT) : null;
		$expected = $host === null || $host === false ? null : $host.($port ? ':'.$port : '');
		if ($expected === null || strcasecmp($expected, $_SERVER['HTTP_HOST'] ?? '') !== 0) {
			return [403, 'Cross-origin request refused'];
		}
		$sent = $_SERVER['HTTP_X_CSRF'] ?? '';
		if (!is_string($sent) || $sent === '' || !hash_equals($token, $sent)) {
			return [403, 'Bad CSRF token'];
		}
		return null;
	}

	// For the form-encoded user-management endpoints: refuse unless this is a same-origin POST
	// carrying this user's token (X-CSRF header). Call after CheckIt() has validated the login.
	function RequireCsrf(){
		$problem = self::WriteRequestProblem($this->CsrfToken(), false);
		if ($problem !== null) {
			http_response_code($problem[0]);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'errors' => ['csrf' => $problem[1]], 'error' => $problem[1]]);
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
	public $sql,
		$result,
		$userlist
		;

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
		$duplicateError,
		$removeError,
		$username,
		$admin,
		$duplicate,
		$duplicateSQL,
		$duplicateResult,
		$addSQL,
		$addresult,
		$entryid,
		$changeresult,
		$secretword_sql,
		$secretword_result,
		$secretword_entry,
		$password_sql,
		$password_result,
		$password_entry,
		$remove_id,
		$removeSQL
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
		  // $_SESSION['user_id'] outlives the request that set it, so it can name a user who has
		  // since been removed: these lookups then come back empty, hence the `?? null`.
		  $this->password        = $this->password_entry[0]['password'] ?? null;
		  $this->secretword      = $this->secretword_entry[0]['secretword'] ?? null;
		  $this->email           = $this->user_entry[0]['email'] ?? null;
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
		  $duplicate_rows = $this->duplicateResult->fetchArray();
		  $this->duplicate = is_array($duplicate_rows) ? $duplicate_rows[0]['username'] : null;
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
		// Empty password = keep the stored hash and secretword (loaded in the constructor),
		// so editing a user's email/role doesn't sign them out.
		if ($_SESSION['password'] !== '') {
			$this->password = password_hash($_SESSION['password'], PASSWORD_DEFAULT);
			$this->secretword = getToken(60);
		}
		$this->admin = (int)$_SESSION['admin'];
		if (!$this->admin && !empty($this->user_entry[0]['admin']) && $this->adminCount() <= 1) {
			$this->mysqlError = 'The last administrator cannot lose the administrator role.';
			return false;
		}
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

	private function adminCount(){
		global $db;
		$rows = $db->query("SELECT COUNT(*) AS n FROM users WHERE admin=1")->fetchArray();
		return is_array($rows) ? (int)$rows[0]['n'] : 0;
	}

	function removeUser(){
		global $db;
		$this->remove_id = (int)$_POST['id'];
		$target = $db->query("SELECT username, admin FROM users WHERE id=?", [['type' => 'i', 'value' => $this->remove_id]])->fetchArray();
		if (!is_array($target)) {
			$this->removeError = 'No such user.';
			return false;
		}
		if (defined('USERNAME') && $target[0]['username'] === USERNAME) {
			$this->removeError = 'You cannot remove your own account.';
			return false;
		}
		if (!empty($target[0]['admin']) && $this->adminCount() <= 1) {
			$this->removeError = 'The last administrator cannot be removed.';
			return false;
		}
		$this->removeSQL = "DELETE FROM users WHERE id=?";
		$remove_param = [['type' => 'i', 'value' => $this->remove_id]];
		$result = $db->query($this->removeSQL, $remove_param);
		if($result->isError()) {
			die('Delete statement failed: Entry not deleted');
		}
		return true;
	}
}

?>
