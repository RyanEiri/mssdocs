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
<!-- Force latest IE rendering engine or ChromeFrame if installed -->
<!--[if IE]>
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<![endif]-->
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

	<title>FHP::File browser</title>

	<!-- Bootstrap CSS -->
	<!-- Tried to load this locally and things broke
	<link rel="stylesheet" href="https://vesturheimsrit.com/css/bootstrap.min.css">
	-->
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
	<!-- Browser stylesheet -->
	<link href="browser_assets/css/styles.css" rel="stylesheet"/>

</head>
<body>

<!-- FileFunctions Modal Form -->
<div class="modal fade" id="fileFunctions" tabindex="-1" role="dialog" aria-labelledby="fileFunctionsLabel">
	<div class="modal-dialog" role="document">
	  <div class="modal-content">
	  <div class="modal-header">
		<form id="fileFunctionsForm" method="POST" action="json-file_functions.php" accept-charset="utf-8">
		<button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="window.location.reload();"><span aria-hidden="true">&times;</span></button>
		<h4 class="modal-title" id="fileFunctionsLabel">File Functions</h4>
	  </div>
	  <div class="modal-body container-fluid col-xs-12" id="fileFunctionsBody">
<div class="container-fluid col-xs-12">
	  <div class="row no-gutters">
	  <div class="container-fluid col-xs-12 col-sm-2">
	    <img data-toggle="tooltip" data-html="true" title="<em>Tooltip</em>: <b>This is the thumbnail image saved in the thumbnail sub-folder.</b>" data-placement="right" id="fileThumbnail" class="" align="center">
	    <br /><br />
	  </div>
	  <div class="container-fluid col-xs-12 col-sm-10">
	  <ul class="list-group" id="fileFilesystemList-group">
	  <li class="list-group-item active">Filesystem Entries for this File</li>
	  <li class="list-group-item">
	  <div class="form-group row">
	    <label for="dirName" class="col-sm-3 col-form-label">Folder Name</label>
	    <div class="col-sm-9">
		<select id="dirName" name="dirList" form="fileFunctionsForm" class="form-control" data-toggle="tooltip" data-html="true" title="<em>Tooltip</em>: <b>You can change the folder from here. Remember to hit the <em>Save Changes</em> button.</b>" data-placement="top" <?php if(!$admin){ echo 'disabled'; } ?>>
		</select>
		<input type="hidden" id="contextFileId" name="contextFileId">
		<input type="hidden" id="previousFileFolder" name="previousFileFolder">
		<input type="hidden" id="previousFileName" name="previousFileName">
		<input type="hidden" id="previousFileTitle" name="previousFileTitle">
		<input type="hidden" id="previousFileDescription" name="previousFileDescription">
	    </div>
	  </div>
	  <div class="form-group row">
	    <label for="fileName" class="col-sm-3 col-form-label">File Name</label>
	    <div class="col-sm-9">
		<input class="form-control" type="text" placeholder="No file" id="fileName" data-toggle="tooltip" data-html="true" title="<em>Tooltip</em>: <b>You can change the file name here. Remember to hit the <em>Save Changes</em> button. Hit the <em>Reset</em> button if you want to revert back to the original file name and start over.</b>" data-placement="bottom" <?php if(!$admin){ echo 'disabled'; } ?>>
	    </div>
	  </div>
	  </li>
	  </ul>
	  </div>
	  </div>
</div>
<div class="container-fluid col-xs-12">
	  <div class="row no-gutters">
	  <div class="container col-xs-12">
	  <ul class="list-group" id="fileDatabaseList-group">
	  <li class="list-group-item active">Database Entries for This File</li>
	  <li class="list-group-item">
	  <div class="form-group row">
	    <label for="fileSize" class="col-sm-4 col-form-label">File Size</label>
	    <div class="col-sm-8">
		<span id="fileSize"></span>
<!--		<input class="form-control form-control-sm" type="text" placeholder="No size" id="fileSize" disabled> -->
	    </div>
	  </div>
	  <div class="form-group row">
	    <label for="fileType" class="col-sm-4 col-form-label">File Type</label>
	    <div class="col-sm-8">
		<span id="fileType"></span>
<!--		<input class="form-control form-control-sm" type="text" placeholder="No Type" id="fileType"> -->
	    </div>
	  </div>
	  <div class="form-group row">
	    <label for="fileUrl" class="col-sm-4 col-form-label">File URL</label>
	    <div class="col-sm-8">
		<a href="" class="fileUrl" data-toggle="tooltip" data-html="true" title="<em>Tooltip</em>: <b>Click this link to download the full size image.</b>" data-placement="top">
		<span id="fileUrl" style="word-break: break-all;"></span>
		</a>
<!--		<input class="form-control form-control-sm" type="url" placeholder="No URL" id="fileUrl"> -->
	    </div>
	  </div>
	  <div class="form-group row">
	    <label for="fileTitle" class="col-sm-4 col-form-label">File Title</label>
	    <div class="col-sm-8">
		<input class="form-control" type="text" placeholder="No Title" id="fileTitle" data-toggle="tooltip" data-html="true" title="<em>Tooltip</em>: <b>Database entry for this file, files table, title field.</b>" data-placement="top">
	    </div>
	  </div>
	  <div class="form-group row">
	    <label for="fileDescription" class="col-sm-4 col-form-label">File Description</label>
	    <div class="col-sm-8">
		<input class="form-control" type="text" placeholder="No Description" id="fileDescription" data-toggle="tooltip" data-html="true" title="<em>Tooltip</em>: <b>Database entry for this file, files table, description field.</b>" data-placement="bottom">
	    </div>
	  </div>
	  <div class="form-group row">
	    <label for="fileDate" class="col-sm-4 col-form-label">Date and Time Modified (PST or PDT)</label>
	    <div class="col-sm-8">
		<span id="fileDate"></span>
<!--		<input class="form-control form-control-sm" type="text" placeholder="No Date" id="fileDate"> -->
	    </div>
	  </div>
	  </li>
	  </ul>
	  </div>
	  </div>
</div>

	  </div>
	  <div class="modal-footer">
		<button type="button" class="btn btn-default" data-dismiss="modal" onclick="window.location.reload();">Close</button>
		<button type="submit" class="btn btn-primary">Save Changes</button>
		<button type="reset" class="btn btn-warning" value="Reset">Reset</button>
	  </div>
	  </form>
	  </div>
	</div>
</div>
<!-- end of FileFunctions Modal Form -->

<!-- FolderFunctions Modal Form -->
<div class="modal fade col-xs-12" id="folderFunctions" tabindex="-1" role="dialog" aria-labelledby="folderFunctionsLabel">
	<div class="modal-dialog modal-lg" role="document">
	  <div class="modal-content">
	  <div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="window.location.reload();"><span aria-hidden="true">&times;</span></button>
		<h4 class="modal-title" id="folderFunctionsLabel">Folder Functions</h4>
	  </div>
	  <div class="modal-body container col-xs-12" id="folderFunctionsBody">
	  <ul class="list-group" id="folderFunctions-group">
	  <li class="list-group-item active">Folder Functions</li>
	  <li class="list-group-item">
<br />
	  <div class="row">
	  <form class="form-horizontal" id="folderFunctionsForm" class="form-error" method="POST" action="json-folder_functions.php" accept-charset="utf-8">
	    <legend class="col-xs-12 col-sm-12 col-md-12 col-lg-12 col-xl-12">Zip Folder
	    <button type="submit" class="btn btn-primary col-xs-offset-6 col-lg-offset-6" data-toggle="tooltip" data-html="true" title="<em>Tooltip</em>: <b>Click me and wait for the success message!</b>">Zip this folder now!</button>
	    </legend>
	    <div class="form-group form-check col-xs-9 col-sm-12">
		<label class="form-check-label">
		<div class="col-sm-12">
		<input class="form-check-input" type="radio" placeholder="No Folder" id="folderNameRadio" name="folderNameRadio" checked>
		<span id="folderNameText"></span>
		<div id="zipname"><br /><h5>Current zip file:</h5></div>
		</div>
		</label>
	    </div>
	  </form>
	  </div>
<br /><br />
	  <div id="files" class="row">
	  <form "form-inline" id="filesInFolderForm" class="form-error" method="POST" action="json-folder_files.php" accept-charset="utf-8">
	    <legend class="col-xs-12 col-sm-12 col-md-12 col-lg-12 col-xl-12">Files in Folder</legend>
	    <input type="hidden" id="folder_id" name="folder_id">
	    <input type="hidden" id="folder_name" name="folder_name">
		<table id="filetable" class="table table-striped table-hover table-condensed">
		  <tr>
		    <div class="form-group">
	    		<label for="file_id"><th>File ID</th></label>
			<label for="file_name"><th>File Name</th></label>
			<label for="file_title"><th>File Title</th></label>
			<label for="file_description"><th>File Description</th></label>
		    </div>
		  </tr>
		</table>
	  </div>
	  </div>
	  <div class="modal-footer">
		<button type="button" class="btn btn-default" data-dismiss="modal" onclick="window.location.reload();">Close</button>
		<button type="submit" class="btn btn-primary">Save Changes</button> 
	  </div>
  	  </form>
	  </div>
	</div>
</div>
<!-- end of FolderFunctions Modal Form -->

<!-- AddFolder Modal Form -->
<div class="modal fade" id="addFolder" tabindex="-1" role="dialog" aria-labelledby="addFolderLabel">
	<div class="modal-dialog" role="document">
	  <div class="modal-content">
	  <div class="modal-header">
	  	<form id="addFolderForm" method="POST" action="json-add_folder.php" accept-charset="utf-8">
		<button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="window.location.reload();"><span aria-hidden="true">&times;</span></button>
		<h4 class="modal-title" id="addFolderLabel">Add Folder</h4>
	  </div>
	  <div class="modal-body" id="addFolderBody">
	    <!-- FOLDER NAME -->
	    <ul class="list-group form-group" id="add_folder-group">
		<li class="list-group-item active">Folder name to be added</li>
	  	<li class="list-group-item">
		  <label for="add_folder">Folder Name</label>
		  <input type="text" class="form-control" id="addFolderForm_name" name="add_folder"> 
		</li>
		<!-- errors will go here -->
	    </ul>
	  </div>
	  <div class="modal-footer">
		<button type="button" class="btn btn-default" data-dismiss="modal" onclick="window.location.reload();">Close</button>
		<button type="submit" class="btn btn-primary">Save Changes</button>
	  </div>
	  </form>
	  </div>
	</div>
</div>
<!-- end of AddFolder Modal Form -->

<!-- MoveFileFolder Modal Form -->
<div class="modal fade" id="moveFileFolder" tabindex="-1" role="dialog" aria-labelledby="moveFileFolderLabel">
	<div class="modal-dialog" role="document">
	  <div class="modal-content">
	  <div class="modal-header">
		<form id="moveFileFolderForm" method="POST" action="json-move_file.php" accept-charset="utf-8">
		<button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="window.location.reload();"><span aria-hidden="true">&times;</span></button>
		<h4 class="modal-title" id="moveFileFolderLabel">Move File/Folder&#40;s&#41;</h4>
	  </div>
	  <div class="modal-body form-group" id="moveFileFolderBody">
	    <!-- FILES TO MOVE -->
	    <ul class="list-group" id="fileList-group">
		<li class="list-group-item active">Files to be moved</li>
		<!-- file list populated by 
		     json_request-file_info.js -->
	    </ul>
	    <!-- FOLDERS TO MOVE -->
	    <ul class="list-group" id="folderList-group">
		<li class="list-group-item active">Folders to be moved</li>
		<!-- folder list populated by
		     json_request-file_info.js -->
	    </ul>
	    <!-- FOLDER TO MOVE TO -->
	    <ul class="list-group" id="folderToList-group">
		<li class="list-group-item active">Folder to move to</li>
		<li class="list-group-item">
		  <label for="folder_list">Folder Name</label>
  	          <select id="folderListSelect" name="folder_list" form="moveFileFolderForm" class="form-control">
	          </select>
		</li>
	    </ul>
	  </div>
	  <div class="modal-footer">
		<button type="button" class="btn btn-default" data-dismiss="modal" onclick="window.location.reload();">Close</button>
		<button type="submit" class="btn btn-primary">Save Changes</button>
	  <!-- errors to go here -->
	  </div>
	  </form>
	  </div>
	</div>
</div>
<!-- end of MoveFileFolder Modal Form -->

<!-- RemoveFileFolder Modal Form -->
<div class="modal fade" id="removeFileFolder" tabindex="-1" role="dialog" aria-labelledby="removeFileFolderLabel">
	<div class="modal-dialog" role="document">
	  <div class="modal-content">
	  <div class="modal-header">
		<form id="removeFileFolderForm" method="POST" action="json-remove_file.php" accept-charset="utf-8">
		<button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="window.location.reload();"><span aria-hidden="true">&times;</span></button>
		<h4 class="modal-title" id="removeFileFolderLabel">Remove File/Folder</h4>
	  </div>
	  <div class="modal-body form-group" id="removeFileFolderBody">
            <!-- FILES TO REMOVE -->
            <ul class="list-group form-group" id="removeFileList-group">
                <li class="list-group-item active">Files to be deleted</li>
                <!-- file list populated by
                     json_request-file_info.js -->
            </ul>
	    <!-- FOLDERS TO REMOVE -->
	    <ul class="list-group form-group" id="removeFolderList-group">
		<li class="list-group-item active">Folders to be removed</li>
		<!-- folder list populated by
		     json_request-file_info.js -->
	    </ul>
	  <!-- errors to go here -->
	  </div>
	  <div class="modal-footer">
		<button type="button" class="btn btn-default" data-dismiss="modal" onclick="window.location.reload();">Close</button>
		<button type="submit" class="btn btn-primary">Save Changes</button>
	  </div>
	  </form>
	  </div>
	</div>
</div>
<!-- end of RemoveFileFolder Modal Form -->

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
<?php if($admin){ ?>
	    <li class="dropdown">
	      <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Admin <span class="caret"></span></a>
	      <ul class="dropdown-menu">
	        <li><a href="<?php echo $html_base; ?>users.php?users=list">Users</a></li>
	      </ul>
	    </li>
<?php } ?>
	    <li class="dropdown active">
	      <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Files <span class="caret"></span><span class="sr-only">(current)</span></a>
	      <ul class="dropdown-menu">
	        <li><a href="<?php echo $html_base; ?>upload.php">Upload</a></li>
	        <li class="active"><a href="<?php echo $html_base; ?>server/php/browser.php">Browser <span class="sr-only">(current)</span></a></li>
	      </ul>
	    </li>
	  </ul>
	</div><!-- /.navbar-collapse -->
	</div><!-- /.container-fluid -->
</nav>
<!-- end of navigation bar -->
<br /><br />

	<div class="filemanager">

		<div class="search">
			<input id="search" type="search" placeholder="Find a file.." />
		</div><br />

		<div class="breadcrumbs"></div>

<!-- File manager buttonbar -->
<div class="container-fluid">
<div class="btn-toolbar filebrowser-buttonbar" role="toolbar" id="buttonbar">
<?php
  if($admin){
?>
	<div class="btn-group col-xs-12 col-sm-7 col-lg-7" role="group">
	<button type="button" class="btn btn-success btn-sm addfolder-button col-xs-5 col-lg-3" data-toggle="modal" data-target="#addFolder">
	  <i class="glyphicon glyphicon-plus"></i>
	  <span>Add Folder</span>
	</button>
	<button type="button" class="btn btn-primary btn-sm move-button col-xs-3 col-lg-2" data-toggle="modal" data-target="#moveFileFolder">
	  <i class="glyphicon glyphicon-move"></i>
	  <span>Move</span>
	</button>
	<button type="button" class="btn btn-danger btn-sm remove-button col-xs-4 col-lg-2" data-toggle="modal" data-target="#removeFileFolder">
	  <i class="glyphicon glyphicon-trash"></i>
	  <span>Remove</span>
	</button>
	</div>
	<div class="btn-group col-xs-12 col-lg-7" role="group">
	<label class="btn btn-warning btn-sm col-xs-5 col-lg-3">
	<input type="checkbox" name="selectAllFiles" id="selectAllFileList" /> All Files
	</label>
	<label class="btn btn-warning btn-sm col-xs-7 col-lg-4">
	<input type="checkbox" name="selectAllFolders" id="selectAllFolderList" /> All Folders
	</label>
	</div>
<?php
  }
?>
	<div class="btn-group col-xs-12 col-lg-7" role="group">
	<button class="btn btn-info btn-sm open-folder-button col-xs-4 col-lg-3">
	  <i class="glyphicon glyphicon-folder-open"></i>
	  <span>Open Folder</span>
	</button>
	<button class="btn btn-info btn-sm open-file-button col-xs-4 col-lg-2">
	  <i class="glyphicon glyphicon-open-file"></i>
	  <span>File Info</span>
	</button>
	<button class="btn btn-info btn-sm folder-info-button col-xs-4 col-lg-2">
	  <i class="glyphicon glyphicon-folder-close"></i>
	  <span>Folder Info</span>
	</button>
	</div>
</div>
</div>
<!-- End of file manager buttonbar -->
	
		<ul class="data"></ul>

		<div class="nothingfound">
			<div class="nofiles"></div>
			<span>No files here.</span>
		</div>

	</div>

<!--
	<footer>
        <a class="tz" href="http://tutorialzine.com/2014/09/cute-file-browser-jquery-ajax-php/">Cute File Browser with jQuery, AJAX and PHP</a>
        <div id="tzine-actions"></div>
        <span class="close"></span>
    </footer>
-->

	<!-- Include our script files -->
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
	<script src="browser_assets/js/script.js"></script>
	<script src="json_request-file_info.js"></script>
	<script src="json_request-form-move_file.js"></script>
	<script src="json_request-form-add_folder.js"></script>
	<script src="json_request-form-remove_file.js"></script>
	<script src="json_request-form-file_functions.js"></script>
	<script src="json_request-form-folder_functions.js"></script>

</body>
</html>
<?php
}
?>
