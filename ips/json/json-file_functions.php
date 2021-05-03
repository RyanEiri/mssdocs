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
	$warnings = array(); // array to hold warnings
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
//			$data['message'] = 'file to show is ' . $show_file . '<br />';
//			$data['message'] .= 'file id is ' . $file_id . ' and it is a ' . gettype($file_id) . '<br />';
//			$data['message'] .= 'db file id is ' . $db_file_id . ' and it is a ' . gettype($db_file_id) . '<br />';
		  if($file_id !== $db_file_id) {
		    $rename_file = TRUE;
//				$data['message'] .= 'rename file is TRUE and it is a ' . gettype($rename_file) . '<br />';
		  } else {
		    $rename_file = FALSE;
//				$data['message'] .= 'rename file is FALSE and it is a ' . gettype($rename_file) . '<br />';
		  }
//			$data['message'] .= 'previous file folder is ' . $previous_file_folder . '<br />';
//			$data['message'] .= 'fs dir name is ' . $fs_dir_name . '<br />';
//			$data['message'] .= 'db dir name is ' . $db_dir_name . '<br />';
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
		//$file_url = $change_object->get_full_url().'/'.$fs_dir_name.'/'.$file_name;
		//$file_url = preg_replace("/.*[\/\\]/", '', $file_url);
		$file_url = $file_url.$file_name;

		/* The location of the JSON request js script
		 * dictates the filesystem location provided.
		 */
		/*if (preg_match("/ips\/\.\.\//", $file_url)) {
 			$file_url = preg_replace("/ips\/\.\.\//", 'ips/', $file_url);
 		} elseif (preg_match("/\w+\/\.\.\//", $file_url)) {
 			$file_url = preg_replace("/ips\/\w+\/\.\.\//", 'ips/', $file_url);
 		} */
//		$data['message'] .= 'file url is ' . $file_url;

		if(($previous_file_folder !== $fs_dir_name) || $rename_file === TRUE){

			/* The files root directory does not appear in the db
			 * so we can't request it from the db. Strip it before
			 * the query.
			 */

			//$db_move_folder = preg_replace('/^\//', '', $db_move_folder);
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

		  if ($change_object->changeFileSystemDB($file_id, $change_file, $file_url, $folder_id) !== FALSE) {

				if (!rename($previous_file_folder.'/'.$previous_file_name, $fs_dir_name.'/'.$file_name)) {
					$db_errors['rename'] .= 'Rename operation failed.';
//					$data['message'] .= '<br />success executing rename file';
				} else {
//					$data['message'] .= '<br />failed executing rename file';
				}


//				$data['message'] .= '<br />success changing file in database';
			} else {
//				$data['message'] .= '<br />failed changing file in database: ' . $change_object->change_file_mysql_error;
			}

		  $thumbnail = $previous_file_folder.'/thumbnail/'.$previous_file_name;
		  if(is_readable($thumbnail)){
		    if(!file_exists($fs_dir_name.'/thumbnail/') && !is_dir($fs_dir_name.'/thumbnail/')){
					mkdir($fs_dir_name.'/thumbnail/');
		    }
		    rename($thumbnail, $fs_dir_name.'/thumbnail/'.$file_name);
		  }
		  if(!empty($data['message'])){
		    $data['message'] .= '<br />Filesystem changed.';
		  } else {
		    $data['message'] = 'Filesystem changed.';
		  }

		} else {
		  $warnings['nameEntries'] = 'Filesystem unchanged!';
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

		// Show a message of success and provide a boolean success variable set to true.
		$data['success'] = true;
		if(isset($db_errors)) {
			$data['db_errors'] = $db_errors;
		}
		if(isset($warnings)) {
		  $data['warnings'] = $warnings;
		}

  }

  // JSON Output
	header('HTTP/1.1 200 OK');
  header('Content-Type: application/json');
  echo json_encode($data);
}
?>
