<?php
ini_set('max_execution_time', '3600');
require_once(getcwd().'/php/start_sess.php');
include getcwd().'/php/boot.php';
$login_cookie = new UserCookie();
$login_cookie->DeleteIt();
if($login_cookie->CheckIt()) {
	$_SESSION['login_cookie'] = serialize($login_cookie);
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
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

	<title>FHP::File browser</title>

	<!-- Bootstrap for CSS -->
	<link rel="stylesheet" href="<?php echo PROGRAM_CSS_BASE ?>bootstrap/bootstrap.min.css">
	<!-- Browser stylesheet -->
	<link rel="stylesheet" href="<?php echo PROGRAM_CSS_BASE ?>browser.css">
	<!-- Custom styles for this page -->
	<link rel="stylesheet" href="<?php echo PROGRAM_CSS_BASE ?>bootstrap/sticky-footer-navbar.css">

	<!-- File icons -->
        <link rel="stylesheet" href="<?php echo PROGRAM_CSS_BASE ?>fileicon.css/fileicon.css">

</head>
<body class="d-flex flex-column h-100">

<!-- FileFunctions Modal Form -->
<div class="modal fade" id="fileFunctions" tabindex="-1" role="dialog" aria-labelledby="fileFunctionsLabel">
	<div class="modal-dialog" role="document">
	  <div class="modal-content">
		<form id="fileFunctionsForm" method="POST" action="json-file_functions.php" accept-charset="utf-8">
			<div class="modal-header">
				<h5 class="modal-title">File Info</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="this.form.reset(); window.location.reload();">
					<span aria-hidden="true">&times;</span>
				</button>
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
				<input type="hidden" id="contextFileId" name="contextFileId">
				<select id="dbDirName" name="dirList" form="fileFunctionsForm" class="form-control" data-toggle="tooltip" data-html="true" title="<em>Tooltip</em>: <b>You can change the folder from here. Remember to hit the <em>Save Changes</em> button.</b>" data-placement="top" <?php if(!ADMIN_STATUS){ echo 'disabled'; } ?>>
				</select>
				<input type="hidden" id="fsDirName" name="fsDirName">
				<input type="hidden" id="previousFileFolder" name="previousFileFolder">
				<input type="hidden" id="previousFileName" name="previousFileName">
				<input type="hidden" id="previousFileTitle" name="previousFileTitle">
				<input type="hidden" id="previousFileDescription" name="previousFileDescription">
	    </div>
			<div id="folder-error" class="mt-3">
				<!-- errors will go here -->
			</div>
	  </div>
	  <div class="form-group row">
	    <label for="fileName" class="col-sm-3 col-form-label">File Name</label>
	    <div class="col-sm-9">
		<input class="form-control" type="text" placeholder="No file" id="fileName" data-toggle="tooltip" data-html="true" title="<em>Tooltip</em>: <b>You can change the file name here. Remember to hit the <em>Save Changes</em> button. Hit the <em>Reset</em> button if you want to revert back to the original file name and start over.</b>" data-placement="bottom" <?php if(!ADMIN_STATUS){ echo 'disabled'; } ?>>
	    </div>
			<div id="file-error" class="mt-3">
				<!-- errors will go here -->
			</div>
	  </div>
	  </li>
	  </ul>
		<div id="filesystem-error" class="mt-3">
			<!-- errors will go here -->
		</div>
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
			<div id="file_title-warning" class="mt-3">
				<!-- warnings will go here -->
			</div>
	  </div>
	  <div class="form-group row">
	    <label for="fileDescription" class="col-sm-4 col-form-label">File Description</label>
	    <div class="col-sm-8">
		<input class="form-control" type="text" placeholder="No Description" id="fileDescription" data-toggle="tooltip" data-html="true" title="<em>Tooltip</em>: <b>Database entry for this file, files table, description field.</b>" data-placement="bottom">
	    </div>
			<div id="file_description-warning" class="mt-3">
				<!-- warnings will go here -->
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
		<div id="database-error" class="mt-3">
			<!-- errors will go here -->
		</div>
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
		<div class="progress progress-style">
			<div id="zip_progress" class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
		</div>
		<div id="zip_message" class="mt-3"></div>
		<div id="zipname" class="mt-3"><h5>Current zip file:</h5></div>
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
			<div id="remove_file_folder-messages" class="mt-3">
				<!-- status messages will go here -->
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
<!-- end of RemoveFileFolder Modal Form -->

<header>
<!-- Fixed Navbar -->
<?php include(HTML_TEMPLATES.'navbar.php'); ?>
</header>

<main role="main" class="container">
<div class="container mt-3">
<!-- File manager buttonbar -->
<div class="container filebrowser-buttonbar" id="buttonbar">
<div class="row">
		<div class="btn-toolbar" role="toolbar">
		<div class="btn-group col-auto my-1" role="group">
		<button type="button" class="btn btn-success btn-sm addfolder-button" data-toggle="modal" data-target="#addFolder">
		  <i class="glyphicon glyphicon-plus"></i>
		  <span>Add Folder</span>
		</button>
		<button type="button" class="btn btn-primary btn-sm move-button" data-toggle="modal" data-target="#moveFileFolder">
		  <i class="glyphicon glyphicon-move"></i>
		  <span>Move</span>
		</button>
		<?php
		if(ADMIN_STATUS){
		?>
		<button type="button" class="btn btn-danger btn-sm remove-button" data-toggle="modal" data-target="#removeFileFolder">
		  <i class="glyphicon glyphicon-trash"></i>
		  <span>Remove</span>
		</button>
		</div>
		<?php
		}
		?>
		<div class="btn-group mr-2 col-auto my-1" role="group">
		<button class="btn btn-info btn-sm open-folder-button">
		  <i class="glyphicon glyphicon-folder-open"></i>
		  <span>Open Folder</span>
		</button>
		<button class="btn btn-info btn-sm open-file-button">
		  <i class="glyphicon glyphicon-open-file"></i>
		  <span>File Info</span>
		</button>
		<button class="btn btn-info btn-sm folder-info-button">
		  <i class="glyphicon glyphicon-folder-close"></i>
		  <span>Folder Info</span>
		</button>
		</div>
		<div class="input-group align-items-center" role="group">
		<!-- <p class="col m-0 p-0"><label class="btn btn-sm m-0">
		<input type="checkbox" name="selectAllFiles" id="selectAllFileList" />
			<span>All Files</span>
		</label></p> -->
		<div class="col-auto my-1">
			<div class="custom-control custom-switch mr-sm-2">
			  <input type="checkbox" class="custom-control-input" name="selectAllFiles" id="selectAllFileList" aria-label="Checkbox to select all files">
			  <label class="custom-control-label" for="selectAllFileList">All files</label>
			</div>
		</div>
		<!-- <p class="col m-0 p-0"><label class="btn btn-sm m-0">
		<input type="checkbox" name="selectAllFolders" id="selectAllFolderList" />
			<span>All Folders</span>
		</label></p> -->
		<div class="col-auto my-1">
			<div class="custom-control custom-switch mr-sm-2">
			  <input type="checkbox" class="custom-control-input" name="selectAllFolders" id="selectAllFolderList" aria-label="Checkbox to select all folders">
			  <label class="custom-control-label" for="selectAllFolderList">All folders</label>
			</div>
		</div>
		</div>
	</div>
	</div>
</div>
<!-- End of file manager buttonbar -->

	<div class="filemanager m-0 p-0">

		<!--<div class="search m-0 p-0">
			<input type="search" placeholder="Find a file..." />
		</div><br />-->

		<div class="breadcrumbs m-0 p-0"></div>

		<ul class="data m-0 p-0"></ul>

		<div class="nothingfound">
			<div class="nofiles"></div>
			<span>No files here.</span>
		</div>

	</div>
</div>
</main>
<!--
	<footer>
        <a class="tz" href="http://tutorialzine.com/2014/09/cute-file-browser-jquery-ajax-php/">Cute File Browser with jQuery, AJAX and PHP</a>
        <div id="tzine-actions"></div>
        <span class="close"></span>
    </footer>
-->

	<!-- jQuery for javascript -->
	<script src="<?php echo PROGRAM_JS_BASE ?>jquery.min.js"></script>
	<!-- JSON scripts -->

	<!-- End of JSON scripts -->
	<!-- Bootstrap extensions for jQuery -->
	<script src="<?php echo PROGRAM_JS_BASE ?>bootstrap/popper.min.js"></script>
	<script src="<?php echo PROGRAM_JS_BASE ?>bootstrap/bootstrap.min.js"></script>
	<!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
	<script src="<?php echo PROGRAM_JS_BASE ?>ie10-viewport-bug-workaround.js"></script>
	<script src="<?php echo PROGRAM_JS_BASE ?>browser.js"></script>
	<script src="<?php echo PROGRAM_JSON_BASE ?>json_request-file_info.js"></script>
	<script src="<?php echo PROGRAM_JSON_BASE ?>json_request-form-move_file.js"></script>
	<script src="<?php echo PROGRAM_JSON_BASE ?>json_request-form-add_folder.js"></script>
	<script src="<?php echo PROGRAM_JSON_BASE ?>json_request-form-remove_file.js"></script>
	<script src="<?php echo PROGRAM_JSON_BASE ?>json_request-form-file_functions.js"></script>
	<script src="<?php echo PROGRAM_JSON_BASE ?>json_request-form-folder_functions.js"></script>

</body>
</html>
<?php
}
?>
