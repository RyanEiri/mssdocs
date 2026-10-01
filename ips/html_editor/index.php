<?php
chdir('..');
include getcwd().'/php/boot.php';
$login_cookie = new UserCookie();
$login_cookie->DeleteIt();
require_once(getcwd().'/php/start_sess.php');
if($login_cookie->CheckIt()) {
	define("USERNAME", $login_cookie->username);
	$userval = new UserGrab(USERNAME);
	define("ADMIN_STATUS", $userval->admin);
	$module = PROGRAM_WEB_BASE . basename(__DIR__) . '/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo SITE_NAME ?>::Internal Proofing System::HTML Files</title>
<meta name="copyright" content="<?php echo COPYRIGHT_HOLDER . ' ' . COPYRIGHT_YEARS ?>" >
<meta http-equiv="expires" content="0" >

<!-- Bootstrap for CSS -->
<link rel="stylesheet" href="<?php echo PROGRAM_CSS_BASE ?>bootstrap/bootstrap.min.css">
<!-- Custom styles for this page -->
<link rel="stylesheet" href="<?php echo PROGRAM_CSS_BASE ?>bootstrap/dashboard.css">
<!-- IE10 CSS Viewport Workaround -->
<link rel="stylesheet" href="<?php echo PROGRAM_CSS_BASE ?>ie10-viewport-bug-workaround.css">
</head>

<body>
<?php include(HTML_TEMPLATES.'navbar.php'); ?>

<div class="container-fluid">
	<div class="row">
		<main role="main" class="col-lg-6 m-auto pt-4">
			<h2>HTML Files</h2>
			<div id="allfiles" class="table-responsive filemanager">
				<table class="table table-striped table-sm">
					<thead>
						<tr><th>Name</th><th>Folder</th><th>Size</th></tr>
					</thead>
					<tbody class="data"></tbody>
				</table>
			</div>
		</main>
	</div>
</div>
<?php include(HTML_TEMPLATES.'footer.php'); ?>

<!-- jQuery for javascript -->
<script src="<?php echo PROGRAM_JS_BASE ?>jquery.min.js"></script>
<!-- JSON scripts -->
<script src="<?php echo htmlspecialchars($module) ?>json_request-file_info.js"></script>
<!-- Bootstrap extensions for jQuery -->
<script src="<?php echo PROGRAM_JS_BASE ?>bootstrap/popper.min.js"></script>
<script src="<?php echo PROGRAM_JS_BASE ?>bootstrap/bootstrap.min.js"></script>
<!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
<script src="<?php echo PROGRAM_JS_BASE ?>ie10-viewport-bug-workaround.js"></script>
</body>
</html>
<?php
}
?>
