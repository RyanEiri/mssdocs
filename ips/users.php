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
if($_SESSION['login_cookie']) {
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
<!-- Force latest IE rendering engine or ChromeFrame if installed -->
<!--[if IE]>
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<![endif]-->
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>FHP::Users</title>
<meta name="author" content="Ryan Eric Johnson" >
<meta name="date" content="2016-10-30" >
<meta name="copyright" content="Ryan Eric Johnson 2016" >
<meta name="keywords" content="manuscript, manuscripts, description, proofing" >
<meta name="description" content="The Fragile Heritage Project aims to create a digital collection of Icelandic language manuscripts held in public and private collections in Canada and the U.S.A." >
<meta http-equiv="expires" content="0" >

<!-- Bootstrap stylesheet  -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
<!-- Uploader Generic page styles -->
<link rel="stylesheet" href="upload/css/style.css">
<!-- blueimp Gallery styles -->
<link rel="stylesheet" href="//blueimp.github.io/Gallery/css/blueimp-gallery.min.css">
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
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
<!-- AJAX calls for forms 
Database access is handled by external PHP scripts
JSON output is created through PHP
and handled by jQuery to populate fields
Invoke the JSON scripts here -->
<?php
if(($admin) && ($_GET['users'])){
?>
<script src="json_request-user_info.js"></script>
<script src="json_request-form-change_user.js"></script>
<script src="json_request-form-add_user.js"></script>
<script src="json_request-form-remove_user.js"></script>
<?php
}
?>
<!-- End of JSON scripts -->
<!-- Bootstrap extensions for jQuery -->
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
</head>

<body>

<?php
if(($admin) && ($_GET['users'] === 'list')){
?>
<!-- ChangeUser Modal Form -->
<div class="modal fade" id="changeUser" tabindex="-1" role="dialog" aria-labelledby="changeUserLabel">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
		<div class="modal-header">
			<form id="changeUserForm" method="POST" action="json-change_user.php">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="this.form.reset(); window.location.reload();"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title" id="changeUserLabel">Change User</h4>
		</div>
		<div class="modal-body">
			<div id="UserChange">
			<input type="hidden" id="changeUserForm_id" name="change_id"> 
			<!-- USERNAME -->
			<div id="change_user-group" class="form-group">
				<label for="change_username">Username</label>
				<input type="text" class="form-control" id="changeUserForm_username" name="change_username">
				<!-- errors will go here -->
			</div>
			<!-- PASSWORD -->
			<div id="change_pass-group" class="form-group">
				<label for="change_password">Password</label>
				<input type="password" class="form-control" id="changeUserForm_password" name="change_password">
				<!-- errors will go here -->
			</div>
			<!-- SECRETWORD -->
			<div id="change_secret-group" class="form-group">
				<label for="change_secretword">Secret Word</label>
				<input type="password" class="form-control" id="changeUserForm_secretword" name="change_secretword">
				<!-- errors will go here -->
			</div>
			<!-- ADMIN STATUS -->
			<div id="change_admin-group" class="form-group">
				<label>
				<input type="checkbox" class="form-control" id="changeUserForm_admin" name="change_admin"> Admin Status
				</label>
				<!-- errors will go here -->
			</div>
			</div>
		</div>
		<div class="modal-footer">
			<button type="button" class="btn btn-default" data-dismiss="modal" onclick="this.form.reset(); window.location.reload();">Close</button>
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
	  <div class="modal-header">
		<form id="addUserForm" method="POST" action="json-add_user.php">
		<button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="this.form.reset(); window.location.reload();"><span aria-hidden="true">&times;</span></button>
		<h4 class="modal-title" id="addUserLabel">Add User</h4>
	  </div>
	  <div class="modal-body">
		<div id="UserAdd">
		<!-- USERNAME -->
		<div id="add_user-group" class="form-group">
		  <label for="add_username">Username</label>
		  <input type="text" class="form-control" id="addUserForm_username" name="add_username">
		  <!-- errors will go here -->
		</div>
		<!--PASSWORD -->
		<div id="add_pass-group" class="form-group">
		  <label for="add_password">Password</label>
		  <input type="password" class="form-control" id="addUserForm_password" name="add_password">
		  <!-- errors will go here -->
		</div>
		<!-- SECRETWORD -->
		<div id="add_secret-group" class="form-group">
		  <label for="add_secretword">Secret Word</label>
		  <input type="password" class="form-control" id="addUserForm_secretword" name="add_secretword">
		  <!-- errors will go here -->
		</div>
		<!-- ADMIN STATUS -->
		<div id="add_admin-group" class="form-group">
		  <label>
		  <input type="checkbox" class="form-control" id="addUserForm_admin" name="add_admin"> Admin Status
		  </label>
		  <!-- errors will go here -->
		</div>
		</div>
	  </div>
	  <div class="modal-footer">
		<button type="button" class="btn btn-default" data-dismiss="modal" onclick="this.form.reset(); window.location.reload();">Close</button>
		<button type="submit" class="btn btn-primary">Save Changes</button>
	  </div>
	  </form>
	  </div>
	</div>
</div>
<!-- end of AddUser Modal Form -->
<!-- RemoveUser Modal Form -->
<div class="modal fade" id="removeUser" tabindex="-1" role="dialog" aria-labelledby="removeUserLabel">
	<div class="modal-dialog" role="document">
	  <div class="modal-content">
	  <div class="modal-header">
		<form id="removeUserForm" method="POST" action="json-remove_user.php">
		<button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="this.form.reset(); window.location.reload();"><span aria-hidden="true">&times;</span></button>
		<h4 class="modal-title" id="removeUserLabel">Remove User</h4>
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
		<button type="button" class="btn btn-default" data-dismiss="modal" onclick="this.form.reset(); window.location.reload();">Close</button>
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
			<li><a href="<?php echo $html_base; ?>index.php">Main</a></li>
			<li><a href="<?php echo $html_base; ?>index.php?header=cookieDel">Sign Out</a></li>
<?php if($admin){ ?>	<li class="dropdown active">
			  <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Admin <span class="caret"></span><span class="sr-only">(current)</span></a>
			  <ul class="dropdown-menu">
			    <li class="active"><a href="<?php echo $html_base; ?>users.php?users=list">Users <span class="sr-only">(current)</span></a></li>
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
<!-- end of top navigation bar -->

<div class="container-fluid">
<?php
if(($admin) && ($_GET['users']) === 'list'){
?>
<!-- User Panel -->
<div class="row">
<div class="col-md-1"></div>
<div class="col-md-10">
<div class="panel panel-primary">
	<div class="panel-heading">Users</div>
	  <!-- useradmin-buttonbar contains a button to add a user -->
	  <div class="row useradmin-buttonbar">
	    <div class="col-md-3" style="margin-left: 3px; margin-top: 3px; margin-bottom: 3px;">
	      <button type="button" class="btn btn-success" data-toggle="modal" data-target="#addUser">
		<i class="glyphicon glyphicon-plus"></i>
		<span>Add user</span>
	      </button>
	    </div>
	  </div>
<?php
$users = new UserList();
$userlist = $users->userlist;
?>
<!-- User Table -->
	<div id="usertable">
	<table class="table table-striped table-hover table-condensed">
	  <tr>
	    <th class="hidden-xs hidden-sm">Functions</th>
	    <th class="hidden-xs hidden-sm">ID</th>
	    <th class="hidden-xs hidden-sm">Date Created</th>
	    <th>Username</th><th>Admin Status</th>
	  </tr>
<?php
foreach ($userlist as $value) {
?>
	  <tr>
	    <td class="hidden-xs hidden-sm">
	      <button type="button" class="btn btn-info btn-xs userselector" data-toggle="modal" data-target="#changeUser" id="changeButton" name="<?php echo $value['id']; ?>">
	      <span class="glyphicon glyphicon-pencil" aria-hidden="true">
	      </span>
	      </button>
	      <button type="button" class="btn btn-info btn-xs removeselector" data-toggle="modal" data-target="#removeUser" id="removeButton" name="<?php echo $value['id']; ?>">
	      <span class="glyphicon glyphicon-remove" aria-hidden="true">
	      </span>
	      </button>
	    </td>
	    <td class="hidden-xs hidden-sm">
		<?php echo $value['id']; ?>
	    </td>
	    <td class="hidden-xs hidden-sm">
	      <?php echo $value['date']; ?>
	    </td>
	    <td>
	      <?php echo $value['username']; ?>
	    </td>
<?php
	if ($value['admin'] === '1') { 
?>
	    <td>
	      YES
	    </td>
<?php
	} else {
?>
	    <td>
	      NO
	    </td>
<?php
	}
}
?>
	</table>
	</div>
<!-- end of User Table -->
</div>
<!-- end of User Panel -->
</div>
<div class="col-md-1"></div>
</div>
<?php
}
?>
<!-- bottom well -->
<div class="row">
<div class="well well-sm">
<center>&copy;Fragile Heritage Project</center>
</div>
<!-- end of bottom well -->
</div>
</div>

</body>

</html>
<?php
}
?>
