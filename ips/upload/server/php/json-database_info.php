<?php
// This file is called by browser_assets/js/script.js
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

  // format bytes function
  function formatSizeUnits($bytes) {
	if ($bytes >= 1073741824) {
	  $bytes = number_format($bytes / 1073741824, 2) . ' GB';
	} elseif ($bytes >= 1048576) {
	  $bytes = number_format($bytes / 1048576, 2) . ' MB';
	} elseif ($bytes >= 1024) {
	  $bytes = number_format($bytes / 1024, 2) . ' kB';
	} elseif ($bytes > 1) {
	  $bytes = $bytes . ' bytes';
	} elseif ($bytes == 1) {
	  $bytes = $bytes . ' byte';
	} else {
	  $bytes = '0 bytes';
	}

	return $bytes;
  }

  // validate the variables ========================================
  // If any of these variables don't exist, add an error to our 
  // $errors array. If they do exist add them to a variable value.
  // If the folder value is the same as the current path, invalidate
  // and pass error.

  if (empty($_POST['context'])){
	$errors['database'] = '<br />No context provided. Unable to determine whether this is a file or a folder query!<br />';
  } else {
	$context = $_POST['context'];
  }

if(isset($context) && $context === 'file') {
  if (empty($_POST['fileName'])){
	$errors['database'] = '<br />No file provided!<br />';
  } else {
	$file_name = $_POST['fileName'];
  }

  if (empty($_POST['dirName'])){
	if(isset($errors['database'])){
	  $errors['database'] .= '<br />No folder provided!<br />';
	} else {
	  $errors['database'] = '<br />No folder provided!<br />';
	}
  } else {
	$folder = $_POST['dirName'];
	$folder_pattern = '/files/';
	$folder_replacement = '';
	$folder = preg_replace($folder_pattern, $folder_replacement, $folder);
	$folder = preg_replace('/^\//', '', $folder);
	if(!$folder) {
	  $file = $file_name;
	} else {
	  $file = $folder.'/'.$file_name;
	}
  }

// Grab all files table values and check for the necesssary values
// to provide an error about table lookup failure.
  if(isset($file)){
	$fileval = new FileGrab($file);
	if(isset($fileval->id)){
	  $file_id = $fileval->id;
	} else {
	  if(isset($errors['database'])){
	    $errors['database'] .= '<br />Table lookup failure! No ID provided by table lookup.<br />';
	  } else {
	    $errors['database'] = '<br />Table lookup failure! No ID provided by table lookup.<br />';
	  }
	}
	if(isset($fileval->name)){ 
	  $file_name = $fileval->name;	
	}  else {
	  if(isset($errors['database'])){
	    $errors['database'] .= '<br />Table lookup failure! No NAME provided by table lookup.<br />';
	  } else {
	    $errors['database'] = '<br />Table lookup failure! No NAME provided by table lookup.<br />';
	  }
	}
	if(isset($fileval->size)){ 
	  $file_size = formatSizeUnits($fileval->size); 
	} else {
	  $errors['database'] .= '<br />Table lookup failure! No SIZE provided by table lookup.<br />';
	}
	if(isset($fileval->type)){ 
	  $file_type = $fileval->type; 
	} else {
	  $errors['database'] .= '<br />Table lookup failure! No TYPE provided by table lookup.<br />';
	}
	if(isset($fileval->url)){ 
	  $file_url = $fileval->url; 
	} else {
	  $errors['database'] .= '<br />Table lookup failure! No URL provided by table lookup.<br />';
	}
	if(isset($fileval->title)){ 
	  $file_title = $fileval->title; 
	} else {
	  $errors['database'] .= '<br />Table lookup failure! No TITLE provided by table lookup.<br />';
	}
	if(isset($fileval->description)){ 
	  $file_description = $fileval->description; 
	} else {
	  $errors['database'] .= '<br />Table lookup failure! No DESCRIPTION provided by table lookup.<br />'; 
	}
	if(isset($fileval->date)) { 
	  $file_date = $fileval->date; 
	} else {
	  $errors['database'] .= '<br />Table lookup failure! No DATE provided by table lookup.<br />';
	}
  }
 } elseif(isset($context) && $context === 'folder') {
  if (empty($_POST['dirName'])){
	if(isset($errors['database'])){
	  $errors['database'] .= '<br />No folder provided!<br />';	
	} else {
	  $errors['database'] = '<br />No folder provided!<br />';
	}
  } else {
	$folder = $_POST['dirName'];
	$folder_pattern = '/files\//';
	$folder_replacement = '';
	$folder = preg_replace($folder_pattern, $folder_replacement, $folder);
	if($folder === 'files') {
	  $folder = '';
	}
	$folder_name = $folder;
  }

// Grab all folder table values and check for the necessary values
// to provide an error about table lookup failure.
  if(isset($folder)){
	$folder_create = FALSE;
	$folderval = new FolderGrab($folder, $folder_create);
	// Check if folder is a thumbnail folder,
	// if so, do not process and output error message. 
	if($folderval->thumbnail === FALSE){
	  if(isset($folderval->folder_id)){
	    $folder_id = $folderval->folder_id;
	  } else {
	    if(isset($errors['database'])){
	      $errors['database'] .= '<br />Table lookup failure! No ID provided by table lookup.<br />';
	    } else {
	      $errors['database'] = '<br />Table lookup failure! No ID provided by table lookup.<br />';
	    }
	  }
	  if(isset($folderval->zip_name)){
	    $zip_name = htmlentities($folderval->zip_name);
	  }
	  if(isset($folderval->zip_url)){
	    $zip_url = $folderval->zip_url;
	  }
	  if(isset($folderval->zip_date)){
	    $zip_date = htmlentities($folderval->zip_date);
	  }
	  if(isset($folderval->files)){
	    $folder_files = $folderval->files;
	  } 
	} else {
	  if(isset($errors['database'])){
	    $errors['database'] .= '<br />Thumbnail folders are not managed in the database.<br />';
	  } else {
	    $errors['database'] = '<br />Thumbnail folders are not managed in the database.<br />';
	  }
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
  } else {

	// if there are no errors process our form, 
	// then return a message

	// DO ALL YOUR FORM PROCESSING HERE
	// THIS CAN BE WHATEVER YOU WANT TO DO 
	// (LOGIN, SAVE, UPDATE, WHATEVER)

	// Table lookup done in error correction to allow for
	// database error to be thrown through error validation.
	// $fileval = new FileGrab($file); // this line does the lookup


	// show a message of success and provide a true 
	// success variable
	$data['success'] = true;
 if(isset($context) && $context === 'file') {
	$data['fileId'] = $file_id;
	$data['fileName'] = $file_name;
	$data['fileSize'] = $file_size;
	$data['fileType'] = $file_type;
	$data['fileUrl'] = $file_url;
	$data['fileTitle'] = $file_title;
	$data['fileDescription'] = $file_description;
	$data['fileDate'] = $file_date;
	$data['message'] = "Success grabbing info for file: $file_name with file ID: $file_id";
 } elseif(isset($context) && $context === 'folder') {
	$data['folderId'] = $folder_id;
	$data['folderName'] = $folder_name;
	if(isset($zip_name)){
	  $data['zipname'] = $zip_name;
	  $data['zipfullpath'] = 'archives/'.$zip_name;
	  if(isset($zip_url)){
	    $data['zipURL'] = $zip_url;
	  }
	  if(isset($zip_date)){
	    $data['zipDate'] = $zip_date;
	  }
	}
	if(isset($folder_files)){
	  $data['files'] = $folder_files;
	}
	$data['message'] = "Success grabbing info for folder: $folder with folder ID: $folder_id";
 }
  }
  // complete our ajax handling with our json output
  // return all our data to an AJAX call

  header('Content-Type: application/json');
  echo json_encode($data);
}
?>
