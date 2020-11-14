<?php
session_start();
ini_set('max_execution_time', '3600');
chdir('..');
include getcwd().'/php/boot.php';
if($_SESSION['login_cookie']) {
	$login_cookie = unserialize($_SESSION['login_cookie']);
} else {
	$login_cookie = new UserCookie();
}
$login_cookie->DeleteIt();
if($login_cookie->CheckIt()) {
	$username = $login_cookie->username;
	$userval = new UserGrab($username);
	$admin = $userval->admin;

  // initialize the zip file
  $zip = new ZipArchive();

  // initialize our ajax arrays
  $errors	= array();	// array to hold validation errors
  $data		= array();	// array to pass back data
  $zip_progress = array();	// array for progress bar callback

  // validate the variables ========================================
  // If any of these variables don't exist, add an error to our
  // $errors array. If they do exist add them to a variable value.
  // If the folder value is the same as the current path, invalidate
  // and pass error.

  // The zip_folder variable is the most important for the use of this
  // script. Initial use of this script will be to simply create a zip
  // archive based on a single folder selection. This will be broadened
  // to create archives based on multiple files and folders selections.
  if (!empty($_POST['folderToZip'])){
	$zip_folder = USER_FILES_BASE.$_POST['folderToZip'];
	//$zip_folder_basename = basename($zip_folder);
	// create zip_db_folder variable for archive
	$zip_db_folder = preg_replace('/^.*files\//', '', $zip_folder);
	$zip_filename = $zip_db_folder;
	// replace spaces with underscores
	$zip_filename = preg_replace('/\ /', '_', $zip_filename);
	// replace slashes with dashes
	$zip_filename = preg_replace('/\//', '-', $zip_filename);
	// remove all other special characters
	$zip_filename = preg_replace('/[^A-Za-z0-9\_\-]/', '', $zip_filename);
	// add .zip at the end
	$zip_filename = $zip_filename.'.zip';
	$zip_path_filename = USER_FILES_BASE.'archives/'.$zip_filename;
	$data['zipFilename'] = $zip_filename;
	if (!$zip->open($zip_path_filename, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
	  $errors['archiveFile'] = "<br />Cannot open or create " . $zip_path_filename . "<br />";
	}
  } else {
	$errors['folderToZip'] = 'No folder provided for zipping!';
  }

  // The file structure is passed from json_request-form-folder_functions
  /*if (!empty($_POST['zipFiles']) && isset($zip_folder)) {
	$zip_files = $_POST['zipFiles'];
	$data['zip_files'] = $zip_files;
	// remove the files root folder from the file entries
	// the archive should not include that folder
	foreach ($zip_files as $key => $value) {
	  $zip_files[$key] = preg_replace('/files\//', '', $value);
	}
	// remove elements from the zip2_files array
	// that contain folders that are not selected
	// for the archive
	$zip2_files = new RecursiveIteratorIterator(
	  new RecursiveDirectoryIterator($zip_folder),
	  RecursiveIteratorIterator::LEAVES_ONLY
	);
// debug
//	$data['info']['zip2_files'] = $zip2_files;
}*/

	$zip_files = new RecursiveIteratorIterator(
	  new RecursiveDirectoryIterator($zip_folder),
	  RecursiveIteratorIterator::LEAVES_ONLY
	);

  /*if (!empty($_POST['zipFolders'])) {
	$zip_folders = $_POST['zipFolders'];
	$zip2_folders = $zip_folders;
	foreach ($zip2_folders as $key => $value) {
	  // remove the files folder from the zip2_folders array
	  $zip2_folders_pattern1 = '/^files$/';
	  if(preg_match($zip2_folders_pattern1, $value)){
	    unset($zip2_folders[$key]);
	  }
	  // remove elements from the zip2_folders array
	  // that contain folders that are not selected
	  // for the archive
	  $zip2_folders_pattern2 = preg_quote($zip_folder);
	  $zip2_folders_pattern2 = '#'.$zip2_folders_pattern2.'#';
	  if(!preg_match($zip2_folders_pattern2, $value)){
	    unset($zip2_folders[$key]);
	  }
	}
	// remove the files root folder from the folder entries
	foreach ($zip2_folders as $key => $value) {
	  $zip2_folders_pattern3 = '/files\//';
	  $zip2_folders_replacement = '';
	  $zip2_folders[$key] = preg_replace($zip2_folders_pattern3, $zip2_folders_replacement, $value);
	}
//	$data['info']['zip2_folders'] = $zip2_folders;
}*/

  // Checkbox selection passed from json_request
  // !! Turning these off for now so that the code for individual
  // files is not triggered. !!
//  if (!empty($_POST['selectedFiles'])) {
//	$selected_files = $_POST['selectedFiles'];
//  }

//  if (!empty($_POST['selectedFolders'])) {
//	$selected_folders = $_POST['selectedFolders'];
//  }


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

	// This part of the script puts checkbox file selections into
	// the zip archive. This will not be utilized immediately,
	// and requires rewriting.
//	if(isset($selected_files)){
//	  foreach ($selected_files as $key => $value) {
//		$zip_file = $value;
//		$zip_folder = basename($folderToZip);
//		$unzipped_file = $value['filename'];
//		if ($zip_folder === 'files') {
//		  $zipped_file = $value['filename'];
//		  $info['zipped_file'][$key] = $zipped_file;
//		} else {
//		  $zipped_file = $zip_folder.'/'.$value['filename'];
//		  $info['zipped_file'][$key] = $zipped_file;
//		}
//		if ($value['dirname'] === 'files') {
//		  $show_file = $value['filename'];
//		  $info['show_file'][$key] = $show_file;
//		} else {
//		  $dirname = basename($value['dirname']);
//		  $show_file = $dirname.'/'.$value['filename'];
//		  $info['show_file'][$key] = $show_file;
//		}
//		if ($zip->open($zip_filename) === TRUE) {
//		  $zip->addFile($unzipped_file, $zipped_file);
//		  $zip->close();
//		  $info['zip_file'] = 'File added to zip: '.$zipped_file;
//		} else {
//		  $info['zip_file'] = 'Unable to open zip file!';
//		}
//		$thumbnail = $value['dirname'].'/thumbnail/'.$value['filename'];
//		if(is_readable($thumbnail)){
//		  if ($zip->open($zip_filename) === TRUE) {
//		    if($zip->addEmptyDir('thumbnail')) {
//			$info['thumbnail'] = 'Created thumbnail directory';
//			$zip->addFile($thumbnail);
//		    } else {
//			$zip->addFile($thumbnail);
//		    }
//		    $zip->close();
//		  } else {
//			$info['zip_filename'] = 'Unable to open zip file';
//		  }
//		}
//	  }
//	  $data['info'] = $info;
//	}


	// A single folder selection will provide the basis for an archive
	// of the images with thumbnails beneath it.
	function zipCount($zipFiles, $zip_count = 0){
		foreach ($zipFiles as $name => $zip2_file) {
			if(!$zip2_file->isDir()) {
				$zip_count++;
			}
		}
		return $zip_count;
	}
	$zip_count = zipCount($zip_files);
	if(isset($zip_folder)){
	$i = 1;
	while($i <= $zip_count) {
	    foreach ($zip_files as $name => $zip_file) {
		// Skip directories (they would be added automatically)
		if (!$zip_file->isDir())
		{
		  // Get real and relative path for current file
		  $filePath = $zip_file->getRealPath();
		  // let's change the zip's path relative to files directory
		  preg_match('/(.*)(files.*)$/i', $filePath, $relativeFilePath);
		  $relativeFilePath = $relativeFilePath[2];

		  // get the percentage of files now complete
		  // and add that percentage to tmp file
		  $percent = intval($i/$zip_count * 100);
		  $zip_progress['percent'] = $percent;
//		  $_SESSION['zip_progress'] = json_encode($zip_progress);
		  file_put_contents(USER_FILES_BASE . "tmp/" . session_id() . ".txt", json_encode($zip_progress));
		  // Add current file to archive
		  $zip->addFile($filePath, $relativeFilePath);
		  usleep(12500);
		  $i++;
		}
	    }
//	  set_time_limit(600);
//	  $zip->registerProgressCallback(0.05, function ($r) {
//	    $zip_progress['percent'] = $r * 100;
//	    file_put_contents(USER_FILES_BASE."tmp/" . session_id() . ".txt", json_encode($zip_progress));
//	  });
	}
	$zip->close();

  	  // deal with the database: correlate archive with
  	  // the folder so user can view it from folder_functions form
	  $folder_create = FALSE; // no need to create the folder
	  $zip_db = New FolderGrab($zip_db_folder, $folder_create);
	  $zip_url = USER_FILES_URL.'archives/'.$zip_filename;
	  $data['zipURL'] = $zip_url;
	  $zip_db->addZip($zip_filename, $zip_url);
	}


	// show a message of success and provide a true
	// success variable
	$data['success'] = true;
	$data['message'] = 'Success!';
  }

  // complete our ajax handling with our json output
  // return all our data to an AJAX call

  header('HTTP/1.1 200 OK');
  header('Content-Type: application/json');
  echo json_encode($data);
}
?>
