<?php
require_once(getcwd().'/php/start_sess.php');
include getcwd().'/php/boot.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	$userval = new UserGrab($_REQUEST['username']);
	$secretword = $userval->validateUser($_REQUEST['username'],$_REQUEST['password']);
	if($secretword) {
		$login_cookie = new UserCookie();
		$login_cookie->CreateIt($_REQUEST['username'],$secretword);
//		$_SESSION['login_cookie'] = $login_cookie;
	} else {
		header('Location: ./login.php?1');
	}
} elseif ($_SERVER['REQUEST_METHOD'] == 'GET') {
	$attempt = $_SERVER['QUERY_STRING'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="ID=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Fragile Heritage Project::Internal Proofing System</title>
<meta name="author" content="Ryan Eric Johnson" >
<meta name="date" content="2019-05-18" >
<meta name="copyright" content="Fragile Heritage Project 2016-2020" >
<meta name="keywords" content="manuscript, manuscripts, description, proofing" >
<meta name="description" content="The Fragile Heritage Project aims to create a digital collection of Icelandic language manuscripts held in public and private collections in Canada and the U.S.A." >
<meta http-equiv="expires" content="0" >

<!-- Bootstrap for CSS -->
<link rel="stylesheet" href="<?php echo PROGRAM_CSS_BASE.'bootstrap/bootstrap.min.css' ?>">
<!-- IE10 CSS Viewport Workaround -->
<link rel="stylesheet" href="<?php echo PROGRAM_CSS_BASE.'ie10-viewport-bug-workaround.css' ?>">
<!-- Custom styles for signin -->
<link rel="stylesheet" href="<?php echo PROGRAM_CSS_BASE.'bootstrap/signin.css' ?>">

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>

<body class="text-center">

<form class="form-signin" method="POST" action="<?php echo $_SERVER['PHP_SELF'] ?>">
	<img class="mb-4" src="<?php echo PROGRAM_WEB_BASE."img/fhp_logo-blue.png" ?>" alt="" width="72" height="72">
	<h1 class="h3 mb-3 font-weight-normal">Please sign in</h1>
	<label for="inputUsername" class="sr-only">Username</label>
	<input type="text" name="username" id="inputUsername" class="form-control" placeholder="Username" required autofocus autocomplete="username">
	<label for="inputPassword" class="sr-only">Password</label>
	<input type="password" name="password" id="inputPassword" class="form-control" placeholder="Password" required autocomplete="current-password">
	<div class="checkbox">
	<label id="failure">
<?php
        if($attempt==1) {
		printf ("Incorrect Username/Password. Please try again.<br />");
        }   
?>	
	</label>
	<button class="btn btn-lg btn-primary btn-block" type="submit">Sign in</button>
</form>

<center>&copy; 2016-2019 Fragile Heritage Project</center>

<!-- jQuery for javascript -->
<script src="<?php echo PROGRAM_JS_BASE.'jquery.min.js' ?>"></script>
<!-- JSON scripts -->

<!-- End of JSON scripts -->
<!-- Bootstrap extensions for jQuery -->
<script src="<?php echo PROGRAM_JS_BASE.'bootstrap/bootstrap.min.js' ?>"></script>
<!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
<script src="<?php echo PROGRAM_JS_BASE.'ie10-viewport-bug-workaround.js' ?>"></script>
</body>

</html>
<?php
}
?>
