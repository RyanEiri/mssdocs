<?php
ini_set('post_max_size', '250M');
ini_set('max_input_vars', '10000');
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
	$_POST = json_decode(file_get_contents('php://input'), true);
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
  // If the folder value is the same as the current path, invalidate
  // and pass error.

  if (empty($_POST['files']) && empty($_POST['folders'])){
	$errors['files'] = 'No files or folders selected!';
  }
  if (!empty($_POST['files'])){
	$files	= $_POST['files'];

/*
	foreach ($files as $key => $value) {

	$show_folder = $_POST['moveToFolder'];
	$show_folder = basename($show_folder);
	if ($show_folder === 'files') {
	  $goto = $value['filename'];
	} else {
	  $goto = $show_folder.'/'.$value['filename'];
	}
	if ($value['dirname'] === 'files') {
	  $show_file = $value['filename'];
	} else {
	  $dirname = basename($value['dirname']);
	  $show_file = $dirname.'/'.$value['filename'];
	}
	$errors['files'] = $show_file;
	$errors['moveToFolder'] = $goto;
	$fileval = new FileGrab($show_file);
	$file_id = $fileval->id;
	$errors['files'] .= ': '.$file_id;
	}

	$simple_object = new ActOnSingleFile();
	$errors['files'] .= $simple_object->get_full_url().'/'.$_POST['moveToFolder'];
*/

  }

  if (!empty($_POST['folders'])){
	$folders = $_POST['folders'];
  }

  if (empty($_POST['moveToFolder'])) {
	$errors['moveToFolder'] = 'No folders available!';
  } else {
	$move_folder = $_POST['moveToFolder'];
  } 

  if (!empty($_POST['files'])) {
	if (!empty($move_folder)) {
	  foreach ($files as $key => $value) {
	    if($value['dirname'] === $move_folder) {
	      $errors['moveToFolder'] = 'Folder to be moved to cannot be the same as the current folder! Please choose a different folder.';
	      break;
	    }
	  }
	}
  }
  if (!empty($_POST['folders'])) {
	$move_folder = $_POST['moveToFolder'];
	foreach ($folders as $key => $value) {
	  if($value === $move_folder) {
	    $errors['moveToFolder'] = 'Folder to be moved to cannot be the same as the folder being moved! Please choose a different folder.';
	    break;
	  }
	  $parent_dir = dirname($value);
	  if($parent_dir === $move_folder) {
	    $errors['moveToFolder'] = 'Folder cannot be moved to the same folder it is currently in! Please choose a different folder.';
	    break;
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
	if(isset($files)){
	  $db_move_folder = preg_replace('/files/', '', $move_folder);
	  $db_move_folder = preg_replace('/^\//', '', $db_move_folder);
	  $folder_create = FALSE;
	  $folderval = new FolderGrab($db_move_folder, $folder_create);
	  $folder_id = $folderval->folder_id;
	  foreach ($files as $key => $value) {
		rename($value['dirname'].'/'.$value['filename'], $move_folder.'/'.$value['filename']);
		if($db_move_folder){
		  $change_file = $db_move_folder.'/'.$value['filename'];
		} else {
		  $change_file = $value['filename'];
		}
		$show_file = preg_replace('/files/', '', $value['dirname']);
		$show_file = preg_replace('/^\//', '', $show_file);
		if($show_file){
		  $show_file = $show_file.'/'.$value['filename'];
		} else {
		  $show_file = $value['filename'];
		}
		$fileval = new FileGrab($show_file);
		$file_id = $fileval->id;
		$change_object = new ActOnSingleFile();
		$file_url = $change_object->get_full_url().'/'.$move_folder.'/'.$value['filename'];
		$change_object->changeFileSystemDB($file_id, $change_file, $file_url, $folder_id);
		// Check for the thumbnail and move along with.
		// Create thumbnail dir if it does not exist.
		$thumbnail = $value['dirname'].'/thumbnail/'.$value['filename'];
		if(is_readable($thumbnail)){
		  if(!file_exists($move_folder.'/thumbnail/') && !is_dir($move_folder.'/thumbnail/')){
		    mkdir($move_folder.'/thumbnail/', 0755);
		  }
		  rename($thumbnail, $move_folder.'/thumbnail/'.$value['filename']);
		}
	  }
	}
	if(isset($folders)){
      //$mark = false;
	  $db_move_folder = preg_replace('/files/', '', $move_folder);
	  $db_move_folder = preg_replace('/^\//', '', $db_move_folder);
	  foreach ($folders as $key => $value) {
        // check for sub-folders
        /*foreach(scandir($value) as $f) {
            if(!$f || $f[0] == '.') {
				continue; // Ignore hidden files
			}
            if(is_dir($dir . '/' . $f)) {

				// The path is a folder
				$files[] = array(
					"name" => $f,
					"type" => "folder",
					"path" => $dir . '/' . $f,
					"items" => scan($dir . '/' . $f) // Recursively get the contents of the folder
				);
                
			} else {
				// It is a file
                // mark the main folder for processing
                $mark = true;
			}
        }*/
		$base_folder = basename($value);
		$new_file_folder = $move_folder.'/'.$base_folder;
		$new_db_folder = $db_move_folder.'/'.$base_folder;
		rename($value, $new_file_folder);
		$db_value = preg_replace('/files/', '', $value);
		$db_value = preg_replace('/^\//', '', $db_value);
	  	$folder_create = FALSE;
	  	$folderval = new FolderGrab($db_value, $folder_create);
		$folderval->updateFolder($new_db_folder);
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