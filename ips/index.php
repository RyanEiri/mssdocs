<?php
$dir_base = 'ips';
$html_base = basename($_SERVER['DOCUMENT_ROOT']);
$html_base = 'https://'.$html_base.'/'.$dir_base.'/';
$config_base = $_SERVER['DOCUMENT_ROOT'];
$config_base = $config_base.'/'.$dir_base.'/';
//require_once ($config_base.'functions/functions.php');
require_once ($config_base.'classes/classes.php');
require_once ($config_base.'config/config.php');
session_start();
if(isset($_SESSION['login_cookie'])) {
	$login_cookie = $_SESSION['login_cookie'];
} else {
	$login_cookie = new UserCookie();
} 
$login_cookie->DeleteIt();
if($login_cookie->CheckIt()) {
	$username = $login_cookie->username;
	$userval = new UserGrab($username);
	$admin = $userval->admin;
?>
<!DOCTYPE html>
<html lang="en">
<head> 

        <!-- Tell search engines not to index the proofing app page -->
        <meta name="robots" content="noindex">

<!-- Force latest IE rendering engine or ChromeFrame if installed -->
<!--[if IE]>
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<![endif]-->
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Fragile Heritage Project::Internal Proofing System</title>
<meta name="author" content="Ryan Eric Johnson" >
<meta name="date" content="2016-12-16" >
<meta name="copyright" content="Fragile Heritage Project 2016-2019" >
<meta name="keywords" content="manuscript, manuscripts, photographs, images, proofing, xml" >
<meta http-equiv="expires" content="0" >

<!-- Bootstrap for CSS -->
<!-- Tried loading stylesheet from local source and things broke
<link rel="stylesheet" href="https://vesturheimsrit.com/css/bootstrap.min.css">
-->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">

<!-- Uploader Generic page styles -->
<link rel="stylesheet" href="upload/css/style.css">
<!-- blueimp Gallery styles -->
<link rel="stylesheet" href="https://vesturheimsrit.com/css/blueimp-gallery.min.css">
<!-- CSS to style the file input field as button and adjust the Bootstrap progress bars -->
<link rel="stylesheet" href="upload/css/jquery.fileupload.css">
<link rel="stylesheet" href="upload/css/jquery.fileupload-ui.css">
<!-- CSS adjustments for browsers with JavaScript disabled -->
<noscript><link rel="stylesheet" href="upload/css/jquery.fileupload-noscript.css"></noscript>
<noscript><link rel="stylesheet" href="upload/css/jquery.fileupload-ui-noscript.css"></noscript>
    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

<!-- jQuery for javascript -->
<!-- Tried to load this locally and things broke 
<script src="https://vesturheimsrit.com/js/jquery.min.js"></script>
-->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
<!-- AJAX calls for forms 
Database access is handled by external PHP scripts
JSON output is created through PHP
and handled by jQuery to populate fields
Invoke the JSON scripts here -->

<!-- End of JSON scripts -->
<!-- Bootstrap extensions for jQuery -->
<!-- Tried to load this locally and things broke
<script src="https://vesturheimsrit.com/js/bootstrap.min.js"></script>
-->
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
</head>

<body>

<!-- Top navigation bar -->
<nav class="navbar navbar-default navbar-fixed-top">
	<div class="container-fluid">
	<!-- Brand and toggle get grouped for better mobile display -->
	<div class="navbar-header">
		<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#fhp-navbar-collapse-1" aria-expanded="false">
		<span class="sr-only">Toggle navigation</span>
		<span class="icon-bar"></span>
		<span class="icon-bar"></span>
		<span class="icon-bar"></span>
		</button>
		<a class="navbar-brand" href="<?php echo $html_base; ?>">FHP::Proofing System</a>
	</div>

	<!-- Collect the nav links, forms, and other content for toggling -->
	<div class="collapse navbar-collapse" id="fhp-navbar-collapse-1">
		<ul class="nav navbar-nav"> 
			<li class="active"><a href="<?php echo $html_base; ?>index.php">Main <span class="sr-only">(current)</span></a></li>
			<li><a href="<?php echo $html_base; ?>index.php?header=cookieDel">Sign Out</a></li>
<?php if($admin){ ?>
			<li class="dropdown">
			  <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Admin <span class="caret"></span></a>
			  <ul class="dropdown-menu">
			    <li><a href="<?php echo $html_base; ?>users.php?users=list">Users</a></li>
			  </ul>
			</li>
<?php } ?>
			<li class="dropdown">
			  <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Files <span class="caret"></span></a>
			  <ul class="dropdown-menu">
			    <li><a href="<?php echo $html_base; ?>upload.php">Upload</a></li>
			    <li><a href="<?php echo $html_base; ?>server/php/browser.php">Browser</a></li>
			  </ul>
			</li>
		</ul>
	</div><!-- /.navbar-collapse -->
	</div><!-- /.container-fluid -->
</nav>
<!-- end of navigation bar -->

<!-- Info Panel about FHP -->
<div class="container-fluid">
<div class="row">
<div class="col-md-1"></div>
<div class="col-md-10">
<div class="panel panel-info">
	<div class="panel-heading">
	<h3 class="panel-title">Í fótspórum Árna Magnússonar í Vesturheimi</h3>
	</div>
	<div class="panel-body">
	<h4>The internal proofing system...</h4>
	<p>Welcome to our internal proofing system web application. The goal of this application is to ease the ability to collaborate with interested parties across the North American continent, in collecting manuscript images and organizing information about them and the people and institutions that possess them.</p>
	<br />
	<h4>The file uploader</h4>
	<p>When first using the file uploader and browser, please upload some files first, so that your user directory will be properly created, then you can navigate to that directory and sort files using the browser.</p>
	</div>
</div>
</div>
<div class="col-md-1"></div>
</div>

<div class="row">
<div class="well well-sm">
<center>&copy;Fragile Heritage Project</center>
</div>
</div>
</div>

</body>

</html>
<?php
}
?>
