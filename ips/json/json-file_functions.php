<?php
// Change to the _ips_ directory for our base.
chdir('..');
include getcwd().'/php/boot.php';
$login_cookie = new UserCookie();
$login_cookie->DeleteIt();
if($login_cookie->CheckIt()) {
	// Change to the _upload_ directory for file manipulation.
	chdir('upload');
	define("USERNAME", $login_cookie->username);

	$userval = new UserGrab(USERNAME);
	define("ADMIN_STATUS", $userval->admin);

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
		$file_id = (int)$_POST['fileId'];
  	if (empty($_POST['dbDirName'])) {
	  	$errors['dbDirName'] = 'No database folder provided!';
  	} else {
	  	$db_dir_name = $_POST['dbDirName'];
  	}
		if (empty($_POST['fsDirName'])) {
			$errors['fsDirName'] = 'No filesystem folder provided!';
		} else {
			$fs_dir_name = $_POST['fsDirName'];
		}
		if (empty($_POST['previousFileFolder'])) {
	  	$errors['previousFileFolder'] = 'No folder specified with original file!';
  	} else {
	  	$previous_file_folder = $_POST['previousFileFolder'];
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
		if (empty($_POST['fileUrl'])) {
			$errors['fileUrl'] = 'No File URL found.';
		} else {
			$file_url = $_POST['fileUrl'];
		}
		if (empty($_POST['newFileUrl'])) {
			$errors['newFileUrl'] = 'No New File URL found.';
		} else {
			$new_file_url = $_POST['newFileUrl'];
		}
		if (empty($_POST['previousFileTitle'])) {
		  $previous_file_title = NULL;
		} else {
		  $previous_file_title = $_POST['previousFileTitle'];
		}
  	if (empty($_POST['fileTitle'])) {
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
	  	$file_description = NULL;
  	} else {
	  	$file_description = $_POST['fileDescription'];
  	}
		if (empty($errors)) {
		  // Find the file in the database and check our file id

		  // the files root directory does not appear in the db
		  // need to strip that from the file info before querying
		  $show_file = preg_replace('/.*files\//', '', $previous_file_folder);

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

		  if($file_id !== $db_file_id) {
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
		// If there are items in our errors array, return those errors.
		$data['success'] = false;
		$data['errors'] = $errors;
		$data['warnings'] = $warnings;
  } else {

		/* If there are no errors, create DB object and
		 * send variables to DB for processing.
		*/

		// Collect DB errors.
		$db_errors	= array();	// array to hold DB errors
		$change_object = new ActOnSingleFile();

		if(($previous_file_folder !== $fs_dir_name) || $rename_file === TRUE){

			/* The files root directory does not appear in the db
			 * so we can't request it from the db. Strip it before
			 * the query.
			 */

			if ($db_dir_name !== 'files') {
				$db_move_folder = preg_replace('/files\//', '', $db_dir_name);
			} else {
				$db_move_folder = $db_dir_name;
			}

			$folder_create = FALSE;
			$folderval = new FolderGrab($db_move_folder, $folder_create);
			$folder_id = $folderval->folder_id;
			if (!isset($folder_id) || $folder_id === NULL) {
				$folder_create = TRUE;
				$folderval = new FolderGrab($db_move_folder, $folder_create);
				$folder_id = $folderval->folder_id;
			}

			if(isset($db_move_folder) && $db_move_folder !== 'files') {
				$change_file = $db_move_folder.'/'.$file_name;
			} else {
				$change_file = $file_name;
			}

		  if ($change_object->changeFileSystemDB($file_id, $change_file, $new_file_url, $folder_id) !== FALSE) {

				if (!rename($previous_file_folder.'/'.$previous_file_name, $fs_dir_name.'/'.$file_name)) {
					$db_errors['rename'] .= 'Rename operation failed.';
				}
			}

		  $thumbnail = $previous_file_folder.'/thumbnail/'.$previous_file_name;
		  if(is_readable($thumbnail)){
		    if(!file_exists($fs_dir_name.'/thumbnail/') && !is_dir($fs_dir_name.'/thumbnail/')){
					mkdir($fs_dir_name.'/thumbnail/');
		    }
		    rename($thumbnail, $fs_dir_name.'/thumbnail/'.$file_name);
		  }
		  if(!empty($data['message'])){
		    $data['message'] .= '<br />Filesystem and database changed.';
		  } else {
		    $data['message'] = 'Filesystem and database changed.';
		  }

		}

		if(($previous_file_title != $file_title) || ($previous_file_description != $file_description)){
		  $change_object->changeFileDB($file_id, $file_title, $file_description);
		  if(!empty($data['message'])){
		    //$data['message'] .= '<br />Database changed.';
		  } else {
		    $data['message'] = 'Database changed.';
		  }
		}

		// Show a message of success and provide a boolean success variable set to true.
		$data['success'] = true;
		if(isset($db_errors)) {
			$data['db_errors'] = $db_errors;
		}

  }

  // JSON Output
	header('HTTP/1.1 200 OK');
  header('Content-Type: application/json');
  echo json_encode($data);
}
?>
