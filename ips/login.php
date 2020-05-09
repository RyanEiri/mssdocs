<?php
$dir_base = 'ips';
$config_base = $_SERVER['DOCUMENT_ROOT'];
$config_base = $config_base.'/'.$dir_base.'/';
//require_once ('./functions/functions.php');
require_once ($config_base.'classes/classes.php');
require_once ($config_base.'config/config.php');
session_start();
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	$userval = new UserGrab($_REQUEST['username']);
	$secretword = $userval->validateUser($_REQUEST['username'],$_REQUEST['password']);
	if($secretword) {
		$login_cookie = new UserCookie();
		$login_cookie->CreateIt($_REQUEST['username'],$secretword);
		$_SESSION['login_cookie'] = $login_cookie;
	} else {
		header('Location: ./login.php?1');
	}
} else {
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="ID=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Fragile Heritage Project::Internal Proofing System</title>
<meta name="author" content="Ryan Eric Johnson" >
<meta name="date" content="2016-10-30" >
<meta name="copyright" content="Ryan Eric Johnson 2016" >
<meta name="keywords" content="manuscript, manuscripts, description, proofing" >
<meta name="description" content="The Fragile Heritage Project aims to create a digital collection of Icelandic language manuscripts held in public and private collections in Canada and the U.S.A." >
<meta http-equiv="expires" content="0" >

<!-- Bootstrap -->
<!-- Tried to load this locally and things broke
<link rel="stylesheet" href="https://vesturheimsrit.com/css/bootstrap.min.css">
-->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">

<!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
<link href="css/bootstrap/ie10-viewport-bug-workaround.css" rel="stylesheet">

<!-- Custom styles for signin -->
<link href="css/signin.css" rel="stylesheet">

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>

<body>
<?php
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
	$attempt = $_SERVER['QUERY_STRING'];
?>
<div class="container">
<?php
	if($attempt==1) {
		printf ("Incorrect Username/Password. Please try again.<br />");
	}
?>
<form class="form-signin" method="POST" action="<?php echo $_SERVER['PHP_SELF'] ?>">
	<h2 class="form-signin-heading">Please sign in</h2>
	<label for="inputUsername" class="sr-only">Username</label>
	<input type="text" name="username" id="inputUsername" class="form-control" placeholder="Username" required autofocus>
	<label for="inputPassword" class="sr-only">Password</label>
	<input type="password" name="password" id=inputPassword" class="form-control" placeholder="Password" required>
	<div class=checkbox">
	<label>
		<input type="checkbox" value="remember-me"> Remember me
	</label>
	</div>
	<button class="btn btn-lg btn-primary btn-block" type="submit">Sign in</button>
</form>

<?php
}
?>
<center>&copy;Fragile Heritage Project</center>

<!-- jQuery JS -->
<!-- Tried to load this locally and things broke
<script src="https://vesturheimsrit.com/js/jquery.min.js"></script>
-->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>

<!-- Bootstrap JS extensions -->
<!-- Tried to load this locally and things broke
<script src="https://vesturheimsrit.com/js/bootstrap.min.js"></script>
-->
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>

<!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
<script src="functions/js/bootstrap/ie10-viewport-bug-workaround.js"></script>
</body>

</html>
<?php
}
?>
