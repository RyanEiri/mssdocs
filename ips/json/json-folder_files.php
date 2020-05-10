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
  $warnings	= array();
  $data		= array();	// array to pass back data

  // validate the variables ========================================
  // If any of these variables don't exist, add an error to our 
  // $errors array. If they do exist add them to a variable value.
  // If the folder value is the same as the current path, invalidate
  // and pass error.

  if (empty($_POST['filesInFolder'])) {
	$errors['filesInFolder'] = 'No files array found! Aborting operation.';
  } else {
	$files_from_js = $_POST['filesInFolder'];
  }
  if (empty($_POST['folderName'])) {
	  if(!$errors){
	    $errors = 'No folder name found! Aborting operation.';
	  } else {
	    $errors .= 'No folder name found!';
	  }
	  $data['folder_name'] = 'No folder name provided by JS!';
  } else {
	  $folder_name = $_POST['folderName'];
	  $data['folder_name'] = $_POST['folderName'];
  }
  if (empty($_POST['folderId'])) {
	  if(!$errors){
	    $errors = 'No folder id found! Aborting operation.';
	  } else {
	    $errors .= 'No folder id found!'; 
	  }
  } else {
	  $folder_id = $_POST['folderId'];
  }

  foreach ($files_from_js as $key => $array) {
	if (isset($array['name'])){
  		if (strpbrk($array['name'], "\\/?%*:|\"<>") === FALSE) {
		} else {
		  $flag = 'File name "' . $array['name'] . '" contains illegal characters.';
		  if(!$errors){
		    $errors = $flag;
		  } else {
		    $errors .= '<br />'.$flag;
		  }
		}
	} else {
		if(!$errors){
			$errors = 'No folder name index provided by JS!';
		} else {
			$errors .= 'No folder name index provided by JS!';
		}
		$data['files_from_js'] = $files_from_js;
	}
  }

  if (!$admin) {
	$errors = 'Administrator access is required for these changes.';
  } 

  // pull a fresh file listing from the database to compare
  // so that we will not be updating mysql with duplicate entries
  $folder_create = FALSE; // no need to create the folder
  $folderval = new FolderGrab($folder_name, $folder_create);
  $files_from_db = $folderval->files;

  // remove keys from db query not provided as a $_POST value from JS
  $exclude_keys = array('size', 'type', 'url', 'date', 'folder_name', 'folder_id');
  function array_exclude($multi_array, Array $exclude_keys) {
    foreach($multi_array as $key => $array){
    	foreach($exclude_keys as $exclude_key){
	  unset($multi_array[$key][$exclude_key]);
	}
	$multi_array[$key]['name'] = basename($array['name']);
	// remove the id keys here so that it is not removed by the diff function
	unset($multi_array[$key]['id']);
    }
    return $multi_array;
  }
  $files_from_db = array_exclude($files_from_db, $exclude_keys);

  // The following recursive diff is based on this stackExchange
  // correspondence: https://stackoverflow.com/questions/3876435/recursive-array-diff.
  // The function that was used produces a result when the same value appears
  // in different keys. Another answer suggests this is inappropriate for diff
  // usage, though based on admittedly quirky behaviour of php's diff utility. 
  // Standard diff output is not the desired result here. And so, 
  // this suggestion was ignored, though the same comment also suggested to use non-
  // obscure names for arrays and variables, and this suggestion was taken into account
  // for the following function.
  function array_recursive_diff($arr1, $arr2) {
    $output = [];

    foreach ($arr1 as $key => $value) {
    	if (array_key_exists($key, $arr2)) {
	  if (is_array($value)) {
	    $recursive_diff = array_recursive_diff($value, $arr2[$key]);
	    if (count($recursive_diff)) { $output[$key] = $recursive_diff; }
	  } else {
	    if ($value != $arr2[$key]) {
		$output[$key] = $value;
	    }
	  }
	} else {
	  $output[$key] = $value;
	}
    }
    return $output;
  }
  $files_from_js = array_recursive_diff($files_from_js, $files_from_db);

  // remove entries with no information remaining other than the id
  foreach ($files_from_js as $key => $array) {
	  if(!array_key_exists('name', $array) 
		  AND !array_key_exists('title', $array) 
		  AND !array_key_exists('description', $array)
	  ) {
	    unset($files_from_js[$key]);
	  }
	  if(array_key_exists('name', $array)) {
	    $files_from_js[$key]['name'] = $folder_name.'/'.$array['name'];
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
	if(!empty($warnings)) {
	  $data['warnings'] = $warnings;
	}
  } else {

	// if there are no errors process our form, 
	// then return a message

	// Database processing 
	$folderval->updateFolderFiles($files_from_js);
	// Filesystem processing
	foreach ($files_from_js as $key => $array) {
	  if(isset($array['name'])) {
	    $name = 'files/'.$folder_name.'/'.$files_from_db[$key]['name'];
	    $to_name = 'files/'.$array['name'];
	    rename($name, $to_name);
	    $thumbnail = 'files/'.$folder_name.'/thumbnail/'.$files_from_db[$key]['name'];
	    $thumbnail_to_name = basename($to_name);
	    $thumbnail_to_name = 'files/'.$folder_name.'/thumbnail/'.$thumbnail_to_name;
	    if(is_readable($thumbnail)){
		rename($thumbnail, $thumbnail_to_name);
	    }
	  }
	}

	// show a message of success and provide a boolean 
	// success variable set to true. 
	$data['success'] = true;
	if(!empty($warnings)) {
	  $data['warnings'] = $warnings;
	}
	$data['message'] = 'Success!';
//	$data['files_from_js'] = $files_from_js;
  }

  // complete our ajax handling with our json output
  // return all our data to an AJAX call

  header('Content-Type: application/json');
  echo json_encode($data);
}
?>
