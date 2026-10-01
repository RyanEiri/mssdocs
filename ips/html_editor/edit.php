<?php
chdir('..');
include getcwd().'/php/boot.php';
require_once __DIR__.'/html_files.php';
$login_cookie = new UserCookie();
$login_cookie->DeleteIt();
require_once(getcwd().'/php/start_sess.php');
if($login_cookie->CheckIt()) {
	define("USERNAME", $login_cookie->username);
	$userval = new UserGrab(USERNAME);
	define("ADMIN_STATUS", $userval->admin);

	$html_file = isset($_GET['html_file']) && is_string($_GET['html_file']) ? $_GET['html_file'] : '';
	$module = PROGRAM_WEB_BASE . basename(__DIR__) . '/';
	$has_ckeditor = is_file(__DIR__ . '/ckeditor/ckeditor.js');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo SITE_NAME ?>::Internal Proofing System::HTML Editor</title>
<meta name="copyright" content="<?php echo COPYRIGHT_HOLDER . ' ' . COPYRIGHT_YEARS ?>" >
<meta http-equiv="expires" content="0" >

<!-- Bootstrap for CSS -->
<link rel="stylesheet" href="<?php echo PROGRAM_CSS_BASE ?>bootstrap/bootstrap.min.css">
<!-- Custom styles for this page -->
<link rel="stylesheet" href="<?php echo PROGRAM_CSS_BASE ?>bootstrap/dashboard.css">
<!-- IE10 CSS Viewport Workaround -->
<link rel="stylesheet" href="<?php echo PROGRAM_CSS_BASE ?>ie10-viewport-bug-workaround.css">
<?php if ($has_ckeditor) { ?>
<!-- CKEditor (third-party: see README.md) -->
<script src="<?php echo htmlspecialchars($module) ?>ckeditor/ckeditor.js"></script>
<?php } ?>
</head>

<body>
<form action="html_save.php" method="post">
	<input type="hidden" name="csrf" value="<?php echo htmlspecialchars($login_cookie->CsrfToken()) ?>">
	<input type="hidden" id="html_file" name="html_file" value="<?php echo htmlspecialchars($html_file) ?>">

	<nav class="navbar navbar-dark sticky-top bg-dark d-flex flex-md-nowrap p-0">
		<a class="navbar-brand col-sm-3 col-md-2 mr-0" href="<?php echo htmlspecialchars($module) ?>">File Listing</a>
		<div class="d-flex flex-grow-1">
			<ul class="navbar-nav px-3">
				<li class="nav-item text-nowrap"><a class="nav-link" href="<?php echo PROGRAM_WEB_BASE ?>">Home</a></li>
			</ul>
		</div>
		<div class="d-flex mr-1">
			<button type="submit" class="btn btn-primary m-auto"<?php echo $has_ckeditor ? '' : ' disabled' ?>>Save</button>
		</div>
	</nav>

	<div class="container-fluid">
		<main role="main" class="col-lg-12 pt-3">
<?php if (!$has_ckeditor) { ?>
			<div class="alert alert-warning">CKEditor is not installed. Put CKEditor 4 in <code><?php echo htmlspecialchars(basename(__DIR__)) ?>/ckeditor/</code> (see the README in this folder).</div>
<?php } ?>
			<textarea name="editor1" id="editor1" rows="600" cols="800"><?php echo htmlspecialchars(ips_html_read($html_file), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></textarea>
<?php if ($has_ckeditor) { ?>
			<script>
				// Turn off the automatic editor creation, then replace the <textarea id="editor1"> with a CKEditor instance.
				CKEDITOR.disableAutoInline = true;
				CKEDITOR.replace('editor1');
			</script>
<?php } ?>
			<div class="row justify-content-end m-3">
				<button type="submit" class="btn btn-primary"<?php echo $has_ckeditor ? '' : ' disabled' ?>>Save</button>
			</div>
		</main>
	</div>
</form>
</body>
</html>
<?php
}
?>
