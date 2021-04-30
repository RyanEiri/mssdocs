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
		$move_folder = USER_FILES_BASE.$_POST['moveToFolder'];
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
		  $dir = USER_FILES_BASE.$value;
		  $parent_dir = dirname($dir);
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

	// FORM PROCESSING 
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
		  	$dirname = USER_FILES_BASE.$value['dirname'];
			$filename = $value['filename'];
			rename($dirname.'/'.$filename, $move_folder.'/'.$filename);

			if(isset($db_move_folder) && $db_move_folder !== 'files') {
				$db_change_file = $db_move_folder.'/'.$filename;
			} else {
				$db_change_file = $filename;
			}
			//$data['message'] .= 'db change file is: ' . $db_change_file . '<br />'; // ##DBUG##

			$show_file = preg_replace('/^.*files\/?/', '', $dirname);
//			$show_file = preg_replace('/^\//', '', $show_file);

			if ($show_file) {
			  $show_file = $show_file.'/'.$filename;
			} else {
			  $show_file = $filename;
			}
			//$data['message'] .= 'dirname: ' . $dirname . '<br />'; // ##DBUG##
			//$data['message'] .= 'show file is: ' . $show_file . '<br />'; // ##DBUG##
			$fileval = new FileGrab($show_file);
			$file_id = $fileval->id;
			$change_object = new ActOnSingleFile();
			//$data['message'] .= 'file id is: ' . $file_id . '<br />'; // ##DBUG##

			//$file_url = $change_object->get_full_url().'/'.$move_folder.'/'.$value['filename'];
			$file_url = USER_FILES_URL.$db_move_folder.'/'.$filename;
			/* The location of the JSON request js script
			 * dictates the filesystem location provided.
			 */
			if (preg_match("/ips\/\.\.\//", $file_url)) {
				$file_url = preg_replace("/ips\/\.\.\//", 'ips/', $file_url);
			} elseif (preg_match("/\w+\/\.\.\//", $file_url)) {
				$file_url = preg_replace("/ips\/\w+\/\.\.\//", 'ips/', $file_url);
			}
			//$data['message'] .= 'file url is: ' . $file_url . '<br />'; // ##DBUG##

			$change_object->changeFileSystemDB($file_id, $db_change_file, $file_url, $folder_id);

			// Check for the thumbnail and move it along with.
			// Create thumbnail dir if it does not exist.
			$thumbnail = $dirname.'/thumbnail/'.$filename;
			if(is_readable($thumbnail)){
			  if(!file_exists($move_folder.'/thumbnail/') && !is_dir($move_folder.'/thumbnail/')){
			    mkdir($move_folder.'/thumbnail/', 0755);
			  }
			  rename($thumbnail, $move_folder.'/thumbnail/'.$filename);
			}
	  }
	}

	if(isset($folders)){
	  $db_move_folder = preg_replace('/^.*files\/?/', '', $move_folder, 1);
//	  $db_move_folder = preg_replace('/^\//', '', $db_move_folder);
	  foreach ($folders as $key => $value) {
			$base_folder = basename($value);
			//$data['message'] .= 'base_folder: ' . $base_folder . '<br />'; // ##DBUG##
			$new_file_folder = $move_folder.'/'.$base_folder;
			//$data['message'] .= 'new_file_folder: ' . $new_file_folder . '<br />'; // ##DBUG##
			if(!empty($db_move_folder)){
				$new_db_folder = $db_move_folder.'/'.$base_folder;
			} else {
				$new_db_folder = $base_folder;
			}
			//$data['message'] .= 'new_db_folder: ' . $new_db_folder . '<br />'; // ##DBUG##
			rename(USER_FILES_BASE.$value, USER_FILES_BASE.$new_file_folder);
			$db_value = preg_replace('/files/', '', $value);
			$db_value = preg_replace('/^\//', '', $db_value);
			//$data['message'] .= 'db_value: ' . $db_value . '<br />'; // ##DBUG##
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
