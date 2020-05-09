<?php
$dir_base = 'ips';
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
	// Grab the current user's info.
	$username = $login_cookie->username;
	$userval = new UserGrab($username);
	$admin = $userval->admin;

  // begin our ajax handling
  $errors	= array();	// array to hold validation errors
  $data		= array();	// array to pass back data

  // validate the variables ========================================
  // If any of these variables don't exist, add an error to our 
  // $errors array. If they do exist add them to a variable value.
  // If the file_id value is the same as the current id, invalidate
  // and do not update filename.

  if (empty($_POST['fileId'])) {
	$errors['fileId'] = 'No File ID found! Aborting operation.';
  } else {
	$file_id = $_POST['fileId'];
  	if (empty($_POST['previousFileFolder'])) {
	  $errors['previousFileFolder'] = 'No folder specified with original file!';
  	} else {
	  $previous_file_folder = $_POST['previousFileFolder'];
  	}
  	if (empty($_POST['fileFolder'])) {
	  $errors['fileFolder'] = 'No folder provided!';
  	} else {
	  $file_folder = $_POST['fileFolder'];
  	} 
	if (empty($_POST['previousFileName'])) {
	  $errors['previousFileName'] = 'No file specified with original file!';
	} else {
	  $previous_file_name = $_POST['previousFileName'];
	}
  	if (empty($_POST['fileName'])) {
	  $errors['fileName'] = 'No File Name provided!';
  	} else {
	  $file_name = $_POST['fileName'];
  	}
	if (empty($_POST['previousFileTitle'])) {
	  $previous_file_title = NULL;
	} else {
	  $previous_file_title = $_POST['previousFileTitle'];
	}
  	if (empty($_POST['fileTitle'])) {
	  $warnings['fileTitle'] = 'No File Title provided, we recommend entering one now.';
	  $file_title = NULL;
  	} else {
	  $file_title = $_POST['fileTitle'];
  	}
	if (empty($_POST['previousFileDescription'])) {
	  $previous_file_description = NULL;
	} else {
	  $previous_file_description = $_POST['previousFileDescription'];
	}
  	if (empty($_POST['fileDescription'])) {
	  $warnings['fileDescription'] = 'No File Description provided, we recommend entering one now.';
	  $file_description = NULL;
  	} else {
	  $file_description = $_POST['fileDescription'];
  	}
	if (empty($errors)) { 
	  // Find the file in the database and check our file id
	  // the files root directory does not appear in the db
	  // need to strip that from the file info before querying
	  $show_file = preg_replace('/files/', '', $previous_file_folder);
	  $show_file = preg_replace('/^\//', '', $show_file);
	  if($show_file){
	    $show_file = $show_file.'/'.$file_name;
	  } else {
	    $show_file = $file_name;
	  }
	  // make db call to grab file info
	  // if the file info matches there is no
	  // need to update the db
	  // if the file_id does not match then
	  // the file name needs to be updated
	  $fileval = new FileGrab($show_file);
	  $db_file_id = $fileval->id;
	  if($file_id != $db_file_id) {
	    $rename_file = TRUE;
	  } else {
	    $rename_file = FALSE;
	  }
	}
  }

// return a response =============================================

	// if there are any errors in our errors array, 
	// return a success boolean of false
  if ( ! empty($errors)) {

	// if there are items in our errors array, 
	// return those errors
	$data['success'] = false;
	$data['errors'] = $errors;
	$data['warnings'] = $warnings;
  } else {

	// if there are no errors process our form, 
	// then return a message

	// DO ALL YOUR FORM PROCESSING HERE
	// THIS CAN BE WHATEVER YOU WANT TO DO 
	// (LOGIN, SAVE, UPDATE, WHATEVER)

	$change_object = new ActOnSingleFile();
	$file_url = $change_object->get_full_url().'/'.$file_folder.'/'.$file_name;

	if(($previous_file_folder != $file_folder) || $rename_file === TRUE){
  	  rename($previous_file_folder.'/'.$previous_file_name, $file_folder.'/'.$file_name);
	// The files root directory does not appear in the db
	// so we can't request it from the db. Strip it before
	// the query. 
	  $db_move_folder = preg_replace('/files/', '', $file_folder);
	  $db_move_folder = preg_replace('/^\//', '', $db_move_folder);
	  $folder_create = FALSE;
	  $folderval = new FolderGrab($db_move_folder, $folder_create);
	  $folder_id = $folderval->folder_id;
	  if($db_move_folder){
	    $change_file = $db_move_folder.'/'.$file_name;
	  } else {
	    $change_file = $file_name;
	  }
	  $change_object->changeFileSystemDB($file_id, $change_file, $file_url, $folder_id);
	  $thumbnail = $previous_file_folder.'/thumbnail/'.$previous_file_name;
	  if(is_readable($thumbnail)){
	    if(!file_exists($file_folder.'/thumbnail/') && !is_dir($file_folder.'/thumbnail/')){
		mkdir($file_folder.'/thumbnail/');	
	    }
	    rename($thumbnail, $file_folder.'/thumbnail/'.$file_name);
	  }
	  if(!empty($data['message'])){
	    $data['message'] .= '<br />Filesystem changed.';
	  } else {
	    $data['message'] = 'Filesystem changed.';
	  } 
	} else {
	  $warnings['filesystem'] = 'Filesystem unchanged!';
	  if(!empty($data['message'])){
	    $data['message'] .= '<br />Filesystem unchanged.';
	  } else {
	    $data['message'] = 'Filesystem unchanged.';
	  }
	}

	if(($previous_file_title != $file_title) || ($previous_file_description != $file_description)){
	  $change_object->changeFileDB($file_id, $file_title, $file_description);
	  if(!empty($data['message'])){
	    $data['message'] .= '<br />Database changed.';
	  } else {
	    $data['message'] = 'Database changed.';
	  }	  
	} else {
	  $warnings['database'] = 'Database unchanged!';
	  if(!empty($data['message'])){
	    $data['message'] .= '<br />Database unchanged.';
	  } else {
	    $data['message'] = 'Database unchanged.';
	  }
	}

	// show a message of success and provide a boolean 
	// success variable set to true. 
	$data['success'] = true;
	if(isset($warnings)) {
	  $data['warnings'] = $warnings;
	}
  }

  // complete our ajax handling with our json output
  // return all our data to an AJAX call

  header('Content-Type: application/json');
  echo json_encode($data);
}
?>
