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
<meta name="date" content="2018-06-06" >
<meta name="copyright" content="Ryan Johnson & Katelin Parsons 2018" >
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
if((ADMIN_STATUS) && ($_GET['users'] === 'list')){
?>
<!-- ChangeUser Modal Form -->
<div class="modal fade" id="changeUser" tabindex="-1" role="dialog"
aria-labelledby="changeUserLabel">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
		<form id="changeUserForm" method="POST" action="json/json-change_user.php" autocomplete="off">
		<div class="modal-header">
			<h5 class="modal-title">Edit User</h5>
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"
			onclick="this.form.reset(); window.location.reload();">
				<span aria-hidden="true">&times;</span>
			</button>
		</div>
		<div class="modal-body">
			<div id="UserChange">
			<input type="hidden" id="changeUserForm_id" name="change_id">
			<!-- USERNAME -->
			<div id="change_user-group" class="form-group">
				<label for="change_username">Username</label>
				<input type="text" class="form-control" id="changeUserForm_username" name="change_username"
				autocomplete="username">
				<div id="change_user-error" class="mt-3">
					<!-- errors will go here -->
				</div>
			</div>
			<!-- EMAIL -->
			<div id="change_email-group" class="form-group">
				<label for="change_email">Email</label>
				<input type="text" class="form-control" id="changeUserForm_email" name="change_email"
				autocomplete="email">
				<div id="change_email-error" class="mt-3">
					<!-- errors will go here -->
				</div>
			</div>
			<!-- PASSWORD -->
			<div id="change_pass-group" class="form-group">
				<label for="change_password">Password</label>
				<input type="password" class="form-control" id="changeUserForm_password"
				name="change_password" autocomplete="new-password">
				<div id="change_pass-error" class="mt-3">
					<!-- errors will go here -->
				</div>
			</div>
			<!-- CPASSWORD -->
			<div id="change_cpass-group" class="form-group">
				<label for="change_cpassword">Confirm Password</label>
				<input type="password" class="form-control" id="changeUserForm_cpassword"
				name="change_cpassword" autocomplete="off">
				<div id="change_cpass-error" class="mt-3">
					<!-- errors will go here -->
				</div>
			</div>
			<!-- ADMIN STATUS -->
			<div id="change_admin-group" class="form-group custom-control custom-checkbox">
				<input type="checkbox" class="form-control custom-control-input" id="changeUserForm_admin"
				name="change_admin">
				<label class="custom-control-label" for="changeUserForm_admin">Admin Status</label>
			</div>
			<!-- DATABASE ERRORS -->
			<div id="change_database-error" class="mt-3">
				<!-- errors will go here -->
			</div>
			</div>

		</div>
		<div class="modal-footer">
			<button type="button" class="btn btn-secondary" data-dismiss="modal"
			onclick="this.form.reset(); window.location.reload();">Close</button>
			<button type="submit" class="btn btn-primary">Save Changes</button>
		</div>
		</form>
		</div>
	</div>
</div>
<!-- end of ChangeUser Modal Form -->
<!-- AddUser Modal Form -->
<div class="modal fade" id="addUser" tabindex="-1" role="dialog" aria-labelledby="addUserLabel">
	<div class="modal-dialog" role="document">
	  <div class="modal-content">
		<form id="addUserForm" method="POST" action="json/json-add_user.php" autocomplete="off">
	  <div class="modal-header">
			<h5 class="modal-title">Add User</h5>
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"
			onclick="this.form.reset(); window.location.reload();">
				<span aria-hidden="true">&times;</span>
			</button>
	  </div>
	  <div class="modal-body">
		<div id="UserAdd">
		<!-- USERNAME -->
		<div id="add_user-group" class="form-group">
		  <label for="add_username">Username</label>
		  <input type="text" class="form-control" id="addUserForm_username" name="add_username"
			autocomplete="off">
			<div id="add_user-error" class="mt-3">
				<!-- errors will go here -->
			</div>
		</div>
		<!-- EMAIL -->
		<div id="add_email-group" class="form-group">
			<label for="add_email">Email</label>
			<input type="text" class="form-control" id="addUserForm_email" name="add_email"
			autocomplete="email">
			<div id="add_email-error" class="mt-3">
				<!-- errors will go here -->
			</div>
		</div>
		<!--PASSWORD -->
		<div id="add_pass-group" class="form-group">
		  <label for="add_password">Password</label>
		  <input type="password" class="form-control" id="addUserForm_password" name="add_password"
			autocomplete="new-password">
			<div id="add_pass-error" class="mt-3">
				<!-- errors will go here -->
			</div>
		</div>
		<!-- CPASSWORD -->
		<div id="add_cpass-group" class="form-group">
		  <label for="add_cpassword">Confirm Password</label>
		  <input type="password" class="form-control" id="addUserForm_cpassword" name="add_cpassword"
			autocomplete="off">
			<div id="add_cpass-error" class="mt-3">
				<!-- errors will go here -->
			</div>
		</div>
		<!-- ADMIN STATUS -->
		<div id="add_admin-group" class="form-group custom-control custom-checkbox">
			<input type="checkbox" class="form-control custom-control-input" id="addUserForm_admin"
			name="add_admin">
			<label class="custom-control-label" for="addUserForm_admin">Admin Status</label>
		</div>
		<!-- DATABASE ERRORS -->
		<div id="add_database-error" class="mt-3">
			<!-- errors will go here -->
		</div>
		</div>
	  </div>
	  <div class="modal-footer">
		<button type="button" class="btn btn-secondary" data-dismiss="modal"
		onclick="this.form.reset(); window.location.reload();">Close</button>
		<button type="submit" class="btn btn-primary">Save Changes</button>
	  </div>
	  </form>
	  </div>
	</div>
</div>
<!-- end of AddUser Modal Form -->
<!-- RemoveUser Modal Form -->
<div class="modal fade" id="removeUser" tabindex="-1" role="dialog"
aria-labelledby="removeUserLabel">
	<div class="modal-dialog" role="document">
	  <div class="modal-content">
		<form id="removeUserForm" method="POST" action="json/json-remove_user.php" autocomplete="off">
	  <div class="modal-header">
			<h5 class="modal-title">Remove User</h5>
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"
			onclick="this.form.reset(); window.location.reload();">
				<span aria-hidden="true">&times;</span>
			</button>
	  </div>
	  <div class="modal-body">
		<div id="UserRemove">
		<div id="remove_id-group" class="form-group">
		<label for="remove_id">
		<input type="hidden" id="removeUserForm_id" name="remove_id">
		Do you wish to remove this user?
		</label>
		</div>
		</div>
	  </div>
	  <div class="modal-footer">
		<button type="button" class="btn btn-secondary" data-dismiss="modal"
		onclick="this.form.reset(); window.location.reload();">Close</button>
		<button type="submit" class="btn btn-danger">Remove</button>
	  </div>
	  </form>
	  </div>
	</div>
</div>
<!-- end of RemoveUser Modal Form -->
<?php
}
?>

<header>
<!-- Fixed Navbar -->
<?php include(HTML_TEMPLATES.'navbar.php'); ?>
</header>

<main role="main" class="container">
<div class="jumbotron jumbotron-fluid">
<?php
if((ADMIN_STATUS)){
?>
<!-- DB Panel -->
<div class="container mt-3">
<div class="card">
	<div class="card-header useradmin-buttonbar">
		<div class="d-flex justify-content-between">
			<div class="flex-column align-self-center">Entries</div>
		  <!-- useradmin-buttonbar contains a button to add a user -->
	    <div class="flex-column">
	      <button type="button" class="btn btn-success btn-sm" data-toggle="modal"
				data-target="#addEntry">
					<span class="sr-only">add entry</span>
		      <span data-feather="plus-square" data-toggle="tooltip" data-placement="left"
					title="Add entry"></span>
	      </button>
	    </div>
		</div>
	</div>
<?php
$files = new FileList();
$filelist = $files->filelist;
?>
<!-- User Table -->
	<div id="usertable">
	<table class="table table-striped table-hover table-condensed">
	  <tr>
	    <th>Functions</th>
	    <th class="hidden-xs hidden-sm">ID</th>
	    <th class="hidden-xs hidden-sm">NAME</th>
	    <th>URL</th>
	  </tr>
<?php
if(!empty($filelist)){
foreach ($filelist as $value) {
?>
	  <tr>
	    <td>
	      <button type="button" class="btn btn-info btn-sm userselector" data-toggle="modal"
				data-target="#changeUser" id="changeButton_<?php echo $value['id'] ?>"
				name="<?php echo $value['id'] ?>">
					<span class="sr-only">edit user</span>
		      <span data-feather="edit" data-toggle="tooltip" data-placement="left" title="Edit user">
					</span>
	      </button>
	      <button type="button" class="btn btn-danger btn-sm removeselector" data-toggle="modal"
				data-target="#removeUser" id="removeButton_<?php echo $value['id'] ?>"
				name="<?php echo $value['id'] ?>">
					<span class="sr-only">remove user</span>
		      <span data-feather="delete" data-toggle="tooltip" data-placement="left"
					title="Remove user"></span>
	      </button>
	    </td>
	    <td class="hidden-xs hidden-sm">
		<?php echo $value['id'] ?>
	    </td>
	    <td class="hidden-xs hidden-sm">
	      <?php echo $value['name'] ?>
	    </td>
	    <td>
	      <?php echo $value['url'] ?>
	    </td>
<?php
}
} else {
	echo "Table failed to load!";
}
?>
	</table>
	</div>
<!-- end of User Table -->
</div>
<!-- end of User Panel -->
</div>
</div>
<?php
}
?>
</main>

<!-- Footer template -->
<?php include(HTML_TEMPLATES.'footer.php'); ?>

<!-- jQuery for javascript -->
<script src="<?php echo PROGRAM_JS_BASE ?>jquery.min.js"></script>

<!-- JSON scripts -->
<?php
if((ADMIN_STATUS) && ($_GET['users'])){
?>
<script src="<?php echo PROGRAM_JSON_BASE ?>json_request-user_info.js"></script>
<script src="<?php echo PROGRAM_JSON_BASE ?>json_request-form-change_user.js"></script>
<script src="<?php echo PROGRAM_JSON_BASE ?>json_request-form-add_user.js"></script>
<script src="<?php echo PROGRAM_JSON_BASE ?>json_request-form-remove_user.js"></script>
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
