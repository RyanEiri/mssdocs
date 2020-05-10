<?php
session_start();
include $_SERVER['DOCUMENT_ROOT'].'/ips/php/boot.php';
$login_cookie = new UserCookie();
$login_cookie->DeleteIt();
if($login_cookie->CheckIt()) {

	define("USERNAME", $login_cookie->username);

	$userval = new UserGrab(USERNAME);
	define("ADMIN_STATUS", $userval->admin);
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
<meta name="date" content="2018-06-02" >
<meta name="copyright" content="Fragile Heritage Project 2016-2019" >
<meta http-equiv="expires" content="0" >

<!-- Bootstrap for CSS -->
<link rel="stylesheet" href="<?php echo PROGRAM_CSS_BASE ?>bootstrap/bootstrap.min.css">
<!-- Custom styles for this page -->
<link rel="stylesheet" href="<?php echo PROGRAM_CSS_BASE ?>bootstrap/jumbotron.css">
<link rel="stylesheet" href="<?php echo PROGRAM_CSS_BASE ?>bootstrap/sticky-footer-navbar.css">

<!-- Internet Explorer Tweaks -->
<!-- IE10 CSS Viewport Workaround -->
<link rel="stylesheet" href="<?php echo PROGRAM_CSS_BASE ?>ie10-viewport-bug-workaround.css">
    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>

<body>

<?php include(HTML_TEMPLATES.'navbar.php'); ?>

<main role="main">
<!-- Info Panel about FHP -->
<div class="jumbotron">
<div class="container">
	<h1 class="display-4">Í fótspor Árna Magnússonar í Vesturheimi</h3>
	<p class="lead">The internal proofing system...</p>
	<p>Welcome to our internal proofing system web application. The goal of this application is to ease the ability to collaborate with interested parties across the North American continent, in collecting manuscript images and organizing information about them and the people and institutions that possess them.</p>
</div>
</div>

	<div class="container">
		<div class="row">
			<div class="col-md-4">
				<h2>File Uploader</h2>
				<p>Upload some files to create your user directory. You can then navigate to that directory and sort files using the browser.</p>
				<p><a class="btn btn-secondary" href="<?php echo PROGRAM_WEB_BASE ?>upload.php" role="button">Upload &raquo;</a></p>
			</div>
			<div class="col-md-4">
				<h2>Browse the collection</h2>
				<p>Browse the currently available images in the collection. Navigate to your user directory to sort your uploaded files.</p>
				<p><a class="btn btn-secondary" href="<?php echo PROGRAM_WEB_BASE ?>browser.php" role="button">Browse &raquo;</a></p>
			</div>
			<div class="col-md-4">
				<h2>XML Editor</h2>
				<p class="lead">Coming soon!</p>
				<p>Create a descriptive file about your manuscript.</p>
				<p><a class="btn btn-secondary" href="<?php echo PROGRAM_WEB_BASE ?>xml/" role="button">Edit &raquo;</a></p>
			</div>
			<div class="col-md-4">
				<h2>Contact Database</h2>
				<p class="lead">Coming soon!</p>
				<p>Share contact information about public and private collections of manuscripts.</p>
				<p><a class="btn btn-secondary" href="#" role="button">Contact &raquo;</a></p>
			</div>
		</div>

		<hr>

	</div> <!-- /container -->

</main>

<!-- Footer template -->
<?php include(HTML_TEMPLATES.'footer.php'); ?>

<!-- jQuery for javascript -->
<script src="<?php echo PROGRAM_JS_BASE ?>jquery.min.js"></script>
<!-- JSON scripts -->

<!-- End of JSON scripts -->
<!-- Bootstrap extensions for jQuery -->
<script src="<?php echo PROGRAM_JS_BASE ?>bootstrap/popper.min.js"></script>
<script src="<?php echo PROGRAM_JS_BASE ?>bootstrap/bootstrap.min.js"></script>
<!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
<script src="js/ie10-viewport-bug-workaround.js"></script>

</body>
</html>
<?php
}
?>
