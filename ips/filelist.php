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
<!-- Force latest IE rendering engine or ChromeFrame if installed -->
<!--[if IE]>
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<![endif]-->
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>FHP::Files Table</title>
<meta name="author" content="Ryan Eric Johnson" >
<meta name="date" content="2020-05-14" >
<meta name="copyright" content="Ryan Eric Johnson & Katelin Parsons 2018-2020" >
<meta http-equiv="expires" content="0" >

<!-- Bootstrap for CSS -->
<link rel="stylesheet" href="<?php echo PROGRAM_CSS_BASE ?>bootstrap/bootstrap.min.css">
<!-- Custom styles for this page -->
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

<body class="d-flex flex-column h-100">

<?php
if((ADMIN_STATUS)){
?>

<!-- MakeChanges Modal Form -->
<div class="modal fade" id="makeChanges" tabindex="-1" role="dialog" aria-labelledby="makeChangesLabel">
	<div class="modal-dialog" role="document">
	  <div class="modal-content">
		<form id="makeChangesForm" method="POST" action="json/json-change_database.php" autocomplete="off">
		  <div class="modal-header">
				<h5 class="modal-title">Make Changes</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="this.form.reset();">
					<span aria-hidden="true">&times;</span>
				</button>
		  </div>
		  <div class="modal-body">
				<div id="ChangeDB">
					<!-- URL -->
					<div id="change_url-group" class="form-check">
					  <input type="checkbox" class="form-check-input" id="makeChangesForm_url" name="change_urls">
						<label class="form-check-label" for="makeChangesForm_url">URL</label>
						<div id="change-error" class="mt-3">
							<!-- errors will go here -->
						</div>
					</div>
					<!-- DATABASE ERRORS -->
					<div id="change_database-error" class="mt-3">
						<!-- errors will go here -->
					</div>
				</div>
		  </div>
		  <div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal" onclick="this.form.reset();">Close</button>
				<button type="submit" class="btn btn-primary">Save Changes</button>
		  </div>
	  </form>
	  </div>
	</div>
</div>
<!-- end of MakeChanges Modal Form -->

<?php
}
?>

<header>
<!-- Fixed Navbar -->
<?php include(HTML_TEMPLATES.'navbar.php'); ?>
</header>

<div class="jumbotron jumbotron-fluid">
<?php
if((ADMIN_STATUS)){
?>
<!-- DB Panel -->
	<div class="container">

				<h1 class="display-4">Entries</h1>
			  <!-- useradmin-buttonbar contains a button to add a user -->
		    <div class="flex-column">
		      <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#makeChanges">
						<span class="sr-only">make changes</span>
			      <span data-feather="database" data-toggle="tooltip" data-placement="left" title="Make changes"></span>
		      </button>
		    </div>

	<?php
	$files = new FileList();
	$filelist = $files->filelist;
	$old_filelist = $filelist;
	$new_filelist = array();

	foreach ($filelist as $key => $value) {
		$url = $value['url'];
		$name = $value['name'];
		// replace old file system hierarchy with new one
		$pattern = '/server\/php/';
		$replacement = 'upload';
		$url = preg_replace($pattern, $replacement, $url);
		// replace double slashes with stingle
		$pattern = '/([a-z])(\/\/)/i';
		$replacement = '\1/';
		$url = preg_replace($pattern, $replacement, $url);
		// replace PetriÌna_GuÃ°mundsdoÌttir with Petrína_Guðmundsdóttir
		$pattern = '/PetriÌna_GuÃ°mundsdoÌttir/';
		$replacement = 'Petrína_Guðmundsdóttir';
		$url = preg_replace($pattern, $replacement, $url);
		// replace PetriÌna-GuÃ°mundsdoÌttir with Petrína-Guðmundsdóttir
		$pattern = '/PetriÌna-GuÃ°mundsdoÌttir/';
		$replacement = 'Petrína-Guðmundsdóttir';
		$url = preg_replace($pattern, $replacement, $url);
		// replace /PetriÌna_GuÃ°mundsdoÌttir in name value with Petrína_Guðmundsdóttir
		$pattern = '/\/PetriÌna_GuÃ°mundsdoÌttir/';
		$replacement = 'Petrína_Guðmundsdóttir';
		$name = preg_replace($pattern, $replacement, $name);
		// replace "/PetriÌna-GuÃ°mundsdoÌttir" in name value with
		// "Petrína-Guðmundsdóttir"
		$pattern = '/\/PetriÌna-GuÃ°mundsdoÌttir/';
		$replacement = 'Petrína-Guðmundsdóttir';
		$name = preg_replace($pattern, $replacement, $name);
		// replace "MarÃ­a JÃ³nsdÃ³ttir - GrettisrÃ­mur frÃ¡ 15, 17 og 19 Ã¶ld"
		// with "María Jónsdóttir - Grettisrímur frá 15, 17 og 19 öld"
		// in url
		$pattern = '/MarÃ­a JÃ³nsdÃ³ttir - GrettisrÃ­mur frÃ¡ 15, 17 og 19 Ã¶ld/';
		$replacement = 'María Jónsdóttir - Grettisrímur frá 15, 17 og 19 öld';
		$url = preg_replace($pattern, $replacement, $url);
		// replace "MarÃ­a JÃ³nsdÃ³ttir - GrettisrÃ­mur frÃ¡ 15, 17 og 19 Ã¶ld"
		// with "María Jónsdóttir - Grettisrímur frá 15, 17 og 19 öld"
		// in name
		$pattern = '/MarÃ­a JÃ³nsdÃ³ttir - GrettisrÃ­mur frÃ¡ 15, 17 og 19 Ã¶ld/';
		$replacement = 'María Jónsdóttir - Grettisrímur frá 15, 17 og 19 öld';
		$name = preg_replace($pattern, $replacement, $name);
		// replace "GuÃ°jÃ³n" with "Guðjón" in url
		$pattern = '/GuÃ°jÃ³n/';
		$replacement = 'Guðjón';
		$url = preg_replace($pattern, $replacement, $url);
		// replace "GuÃ°jÃ³n" with "Guðjón" in name
		$pattern = '/GuÃ°jÃ³n/';
		$replacement = 'Guðjón';
		$name = preg_replace($pattern, $replacement, $name);
		// replace "Ãrmann" with "Ármann" in url
		$pattern = '/Ãrmann/';
		$replacement = 'Ármann';
		$url = preg_replace($pattern, $replacement, $url);
		// replace "Ãrmann" with "Ármann" in name
		$pattern = '/Ãrmann/';
		$replacement = 'Ármann';
		$name = preg_replace($pattern, $replacement, $name);
		// replace "JÃ³nsson" with "Jónsson" in url
		$pattern = '/JÃ³nsson/';
		$replacement = 'Jónsson';
		$url = preg_replace($pattern, $replacement, $url);
		// replace "JÃ³nsson" with "Jónsson" in name
		$pattern = '/JÃ³nsson/';
		$replacement = 'Jónsson';
		$name = preg_replace($pattern, $replacement, $name);
		// replace "SÃ³lveig SnorradÃ³ttir" with "Sólveig Snorradóttir" in url
		$pattern = '/SÃ³lveig SnorradÃ³ttir/';
		$replacement = 'Sólveig Snorradóttir';
		$url = preg_replace($pattern, $replacement, $url);
		// replace "SÃ³lveig SnorradÃ³ttir" with "Sólveig Snorradóttir" in name
		$pattern = '/SÃ³lveig SnorradÃ³ttir/';
		$replacement = 'Sólveig Snorradóttir';
		$name = preg_replace($pattern, $replacement, $name);
		//populate the $new_filelist array
		if ($filelist[$key]['url'] !== $url || $filelist[$key]['name'] !== $name) {
			if ($filelist[$key]['url'] !== $url) {
				$new_filelist[$key]['url'] = $url;
			} else {
				$new_filelist[$key]['url'] = $filelist[$key]['url'];
			}
			if ($filelist[$key]['name'] !== $name) {
				$new_filelist[$key]['name'] = $name;
			} else {
				$new_filelist[$key]['name'] = $filelist[$key]['name'];
			}
			$new_filelist[$key]['id'] = $filelist[$key]['id'];
			$new_filelist[$key]['folder_id'] = $filelist[$key]['folder_id'];
		}
	}
	$_SESSION['file_urls'] = $new_filelist;
	?>

	<!-- User Table -->
		<div id="usertable">
		<table class="table table-striped table-hover table-condensed">
		  <tr>
		    <th>Functions</th>
		    <th class="hidden-xs hidden-sm">NAME</th>
		    <th class="hidden-xs hidden-sm">OLD URL</th>
		    <th>NEW URL</th>
		  </tr>
	<?php
	if(!empty($_SESSION['file_urls'])){
		foreach ($_SESSION['file_urls'] as $key => $value) {
		?>
			  <tr>
			    <td>
			      <button type="button" class="btn btn-info btn-sm userselector" data-toggle="modal"
						data-target="#inactiveModal" id="inactiveButton_<?php echo $value['id'] ?>"
						name="<?php echo $value['id'] ?>">
							<span class="sr-only">deactivated</span>
				      <span data-feather="edit" data-toggle="tooltip" data-placement="left" title="Deactivated button">
							</span>
			      </button>
			    </td>
			    <td class="hidden-xs hidden-sm">
				<?php echo $value['name'] ?>
			    </td>
			    <td class="hidden-xs hidden-sm">
						<?php echo $old_filelist[$key]['url'] ?>
			    </td>
			    <td>
			      <?php echo $value['url'] ?>
			    </td>
		<?php
		}
		} else {
			echo "No entry errors detected.";
		}
	?>
		</table>
		</div>
	<!-- end of User Table -->
	</div>
<!-- end of jumbotron -->
</div>
<?php
}
?>

<!-- Footer template -->
<?php include(HTML_TEMPLATES.'footer.php'); ?>

<!-- jQuery for javascript -->
<script src="<?php echo PROGRAM_JS_BASE ?>jquery.min.js"></script>

<!-- JSON scripts -->
<?php
if((ADMIN_STATUS)){
?>
<!--
<script src="<?php //echo PROGRAM_JSON_BASE ?>json_request-user_info.js"></script>
<script src="<?php //echo PROGRAM_JSON_BASE ?>json_request-form-change_user.js"></script>
<script src="<?php //echo PROGRAM_JSON_BASE ?>json_request-form-add_user.js"></script>
<script src="<?php //echo PROGRAM_JSON_BASE ?>json_request-form-remove_user.js"></script>
-->
<script src="<?php echo PROGRAM_JSON_BASE ?>json_request-form-change_database.js"></script>
<?php
}
?>
<!-- End of JSON scripts -->

<!-- Bootstrap extensions for jQuery -->
<script src="<?php echo PROGRAM_JS_BASE ?>bootstrap/popper.min.js"></script>
<script src="<?php echo PROGRAM_JS_BASE ?>bootstrap/bootstrap.min.js"></script>
<!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
<script src="<?php echo PROGRAM_JS_BASE ?>ie10-viewport-bug-workaround.js"></script>

<!-- Icons -->
<script src="<?php echo PROGRAM_JS_BASE ?>icons/feather.min.js"></script>
<script>
	feather.replace()
</script>

</body>

</html>
<?php
}
?>
