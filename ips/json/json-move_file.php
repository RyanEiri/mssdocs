<?php
session_start();
chdir('..');
include getcwd().'/php/boot.php';
$login_cookie = new UserCookie();
$login_cookie->DeleteIt();
if($login_cookie->CheckIt()) {

	define("USERNAME", $login_cookie->username);

	$userval = new UserGrab(USERNAME);
	define("ADMIN_STATUS", $userval->admin);

	$_POST = json_decode(file_get_contents('php://input'), true);

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
  }

  if (!empty($_POST['folders'])){
		$folders = $_POST['folders'];
  }

  if (empty($_POST['moveToFolder'])) {
		$errors['moveToFolder'] = 'No folders available!';
  } else {
		$move_folder = $_POST['moveToFolder'];
  }

	if (empty($_POST['dbMoveToFolder'])) {
		$errors['moveToFolder'] = 'No db move to folder available!';
	} else {
		$db_move_folder = $_POST['dbMoveToFolder'];
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
		$move_folder	= $_POST['moveToFolder'];
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

		if ($db_move_folder !== 'files') {
			$db_move_folder = preg_replace('/files\//', '', $db_move_folder);
		}
		//$data['message'] = 'db move folder is: ' . $db_move_folder . '<br />'; // ##DBUG##

		$folder_create = FALSE;
		$folderval = new FolderGrab($db_move_folder, $folder_create);
		$folder_id = $folderval->folder_id;
		if (!isset($folder_id) || $folder_id === NULL) {
			$folder_create = TRUE;
			$folderval = new FolderGrab($db_move_folder, $folder_create);
			$folder_id = $folderval->folder_id;
		}
		//$data['message'] .= 'folder id is: ' . $folder_id . '<br />'; // ##DBUG##

	  foreach ($files as $key => $value) {
			rename($value['dirname'].'/'.$value['filename'], $move_folder.'/'.$value['filename']);

			if(isset($db_move_folder) && $db_move_folder !== 'files') {
				$change_file = $db_move_folder.'/'.$value['filename'];
			} else {
				$change_file = $value['filename'];
			}
			//$data['message'] .= 'change file is: ' . $change_file . '<br />'; // ##DBUG##

			$show_file = preg_replace('/.*files/', '', $value['dirname']);
			$show_file = preg_replace('/^\//', '', $show_file);

			if ($show_file) {
			  $show_file = $show_file.'/'.$value['filename'];
			} else {
			  $show_file = $value['filename'];
			}
			//$data['message'] .= 'show file is: ' . $show_file . '<br />'; // ##DBUG##
			$fileval = new FileGrab($show_file);
			$file_id = $fileval->id;
			$change_object = new ActOnSingleFile();
			//$data['message'] .= 'file id is: ' . $file_id . '<br />'; // ##DBUG##

			$file_url = $change_object->get_full_url().'/'.$move_folder.'/'.$value['filename'];
			/* The location of the JSON request js script
			 * dictates the filesystem location provided.
			 */
			if (preg_match("/ips\/\.\.\//", $file_url)) {
				$file_url = preg_replace("/ips\/\.\.\//", 'ips/', $file_url);
			} elseif (preg_match("/\w+\/\.\.\//", $file_url)) {
				$file_url = preg_replace("/ips\/\w+\/\.\.\//", 'ips/', $file_url);
			}
			//$data['message'] .= 'file url is: ' . $file_url . '<br />'; // ##DBUG##

			$change_object->changeFileSystemDB($file_id, $change_file, $file_url, $folder_id);

			// Check for the thumbnail and move it along with.
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
	  $db_move_folder = preg_replace('/files/', '', $move_folder);
	  $db_move_folder = preg_replace('/^\//', '', $db_move_folder);
	  foreach ($folders as $key => $value) {
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
	$data['message'] .= 'Success!';
  }

  // complete our ajax handling with our json output
  // return all our data to an AJAX call

  header('Content-Type: application/json');
  echo json_encode($data);
}
?>
