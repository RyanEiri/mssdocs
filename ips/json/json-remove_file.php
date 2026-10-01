<?php
chdir('..');
require_once(getcwd().'/php/start_sess.php');
include getcwd().'/php/boot.php';
$login_cookie = new UserCookie();
$login_cookie->DeleteIt();
if($login_cookie->CheckIt()) {

	define("USERNAME", $login_cookie->username);

	$userval = new UserGrab(USERNAME);
	define("ADMIN_STATUS", $userval->admin);

  // begin our ajax handling
  $errors	= array();	// array to hold validation errors
  $data		= array();	// array to pass back data

if(ADMIN_STATUS){
	// validate the request ==========================================
  // If any of these variables don't exist, add an error to our
  // $errors array. If they do exist add them to a variable value.
  // If the folder value is the same as the current path, invalidate
  // and pass error.

	// Refuse paths outside upload/files/ (see ips_confine_request in files_table.php).
	ips_confine_request('remove');
  if (empty($_POST['files']) && empty($_POST['folders'])){
	$errors['files'] = 'No files or folders selected!';
  }
  if (!empty($_POST['files'])){
	$files = $_POST['files'];
  }
  if (!empty($_POST['folders'])){
	$folders = $_POST['folders'];
  }
  if (isset($files)){
    foreach($files as $key => $file) {
	$entry = USER_FILES_BASE.$file['dirname'].'/'.$file['filename'];
	if (!is_readable($entry)) {
	  if (isset($errors['files'])) {
	    $errors['files'] .= '<br />Invalid file:&nbsp;'.$entry;
	  } else {
	    $errors['files'] = 'Invalid file:&nbsp;'.$entry;
	  }
	}
    }
  }
  if (isset($folders)){
    foreach($folders as $key => $folder) {
	    		$folder = USER_FILES_BASE.$folder;
			if (!is_readable($folder)) {
			  if (isset($errors['files'])) {
			    $errors['files'] .= '<br />Invalid folder:&nbsp;'.$folder;
			  } else {
			    $errors['files'] = 'Invalid folder:&nbsp;'.$folder;
			  }
			}
			$scanned_dir = scandir($folder);
			if (count($scanned_dir) != 2) {
			  if ($scanned_dir[2] != 'thumbnail') {
			    if (isset($errors['files'])) {
			      $errors['files'] .= '<br />Unempty folder:&nbsp;'.$folder;
			    } else {
			      $errors['files'] = 'Unempty folder:&nbsp;'.$folder;
			    }
			  }
			}
    }
  }
} else {
	$errors['privilege'] = 'You do not have sufficient privileges to remove files. Please contact your administrator.';
}

// return an error response if there are errors ===================================

  if ( ! empty($errors)) {
	// if there are any errors in our errors array,
	// return a success boolean of false
	$data['success'] = false;
	// if there are items in our errors array,
	// return those errors
	$data['errors'] = $errors;
  } else {

	// if there are no errors process our form,
	// then return a message

	// DO ALL YOUR FORM PROCESSING HERE
	// THIS CAN BE WHATEVER YOU WANT TO DO
	// (LOGIN, SAVE, UPDATE, WHATEVER)
	if(isset($files)){
	  foreach ($files as $key => $file) {
		unlink(USER_FILES_BASE.$file['dirname'].'/'.$file['filename']);
		if(is_readable(USER_FILES_BASE.$file['dirname'].'/thumbnail/'.$file['filename'])){
			unlink(USER_FILES_BASE.$file['dirname'].'/thumbnail/'.$file['filename']);
		}
		if (($file['dirname'] === 'files') && ($file['dirname'] !== 'thumbnail')) {
		  $show_file = $file['filename'];
		} else {
		  //$dirname = basename($file['dirname']);
		  $dirname = preg_replace('/^.*files\/?/', '', $file['dirname'], 1);
		  $show_file = $dirname.'/'.$file['filename'];
		}
		//$data['message'] = 'show_file: ' . $show_file . '<br />';
		$fileval = new FileGrab($show_file);
		$file_id = $fileval->id;
		$remove_object = new ActOnSingleFile();
		$remove_object->removeFile($file_id);
	  }
	}
	if(isset($folders)){
	  foreach ($folders as $key => $folder) {
	    $folder = USER_FILES_BASE.$folder;
	    //$data['folder_message'] = 'folder: ' . $folder . '<br />';
	    $scanned_dir = scandir($folder);
	    //$data['scan_message'] .= 'scanned_dir: ' . $scanned_dir[2] . '<br />';
	    if (count($scanned_dir) !== 2) {
		if ($scanned_dir[2] === 'thumbnail') {
		  rmdir($folder.'/thumbnail');
		}
	    }
	    rmdir($folder);
//	    $db_folder_pattern = '/^.*files\/?/';
//	    $db_folder_replacement = '';
	    $db_folder = preg_replace('/^.*files\/?/', '', $folder);
	    $data['db_folder_message'] = 'db_folder: ' . $db_folder . '<br />'; // ##DBUG##
	    $db_create = FALSE;
	    $db_folderval = new FolderGrab($db_folder, $db_create);
	    $db_folderval->removeFolder();
	  }
	}

	// show a message of success and provide a true
	// success variable
	$data['success'] = true;
	$data['message'] = 'Success!';
  }

  // complete our ajax handling with our json output
  // return all our data to an AJAX call

  header('Content-Type: application/json');
  echo json_encode($data);
}
?>
