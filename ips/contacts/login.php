<?php
if ($_COOKIE['login']){
        list($c_username,$cookie_hash) = split(',',$_COOKIE['login']);
                if (md5($c_username.$secret_word) == $cookie_hash){
	} else { 
			header('Location: ./index.php'); 
	}
} else { 
require_once ('../classes.plzone.net/classes.php');
require_once ('../config.plzone.net/config.php');
function pc_validate($user,$pass){
	$users = mysql_query("SELECT * FROM user WHERE username='$user'");
	while ($myrow = mysql_fetch_row($users)) { 			
		if (($myrow[1]==$user) && ($myrow[2] == $pass)){
	 		$ret = $myrow[2];
	 	}else{
	 		$ret = false;
	 	}
	}
	return($ret);
}
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
$secretword = pc_validate($_REQUEST['username'],$_REQUEST['password']);
	if ($secretword != false) {
		setcookie('login',$_REQUEST['username'].','.md5($_REQUEST['username'].$secret_word));
		header('Location: ./index.php');
	} else { 
		header('Location: ./login.php?1');
	}
} else {
require_once ('../functions.plzone.net/functions.php');
?>
<head><title>PLZone Contact Database</title></head>

<!--<link rel="stylesheet" type="text/css" href="../style.css">-->
<html>
<body>
<center>
<div id=header>
<img src="./images/web_banner.jpg">
</div>
</center>
<?php
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
	$attempt = $_SERVER["QUERY_STRING"];
?>
	<div id="centermain">
	<div id="login">
	<br>
	<center>
<?php
	if($attempt==1) {
		printf ("Incorrect Username/Password. Please try again.<br><br>");
	} 
?>
	Please enter your username and password.<br><br>
	<form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="POST">
	Username: <input type="text" name="username"><br>
	Password: <input type="password" name="password"><br><br>
	<input type="submit" value="Log In">
	</form>
	</center>
	<br>
	</div>
	</div>
<?php
}
}
}
?>
</div>
</body>
</html>
