<?php
// This script creates a zip archive based on a single folder selection.

// Increase time to complete script. Zip files can take a long time to save.
ini_set('max_execution_time', '3600');
ini_set('max_input_time', '3600');

// Perform application bootstrap.
chdir('..');
require_once(getcwd().'/php/start_sess.php');
include getcwd().'/php/boot.php';

// User authentication.
if($_SESSION['login_cookie']) {
	$login_cookie = unserialize($_SESSION['login_cookie']);
} else {
	$login_cookie = new UserCookie();
}
$login_cookie->DeleteIt();

// Everything between this if statement assumes an authenticated user.
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
  if (!empty($_POST['folderToZip'])){
	$zip_folder = USER_FILES_BASE.$_POST['folderToZip'];
	$zip_db_folder = preg_replace('/^.*files\//', '', $zip_folder);
	$zip_filename = $zip_db_folder;
	// replace spaces with underscores
	$zip_filename = preg_replace('/\ /', '_', $zip_filename);
	// replace slashes with dashes
	$zip_filename = preg_replace('/\//', '-', $zip_filename);
	// remove all other special characters
	$zip_filename = preg_replace('/[^A-Za-z0-9\_\-]/', 'o', $zip_filename);
	// add .zip at the end
	$zip_filename = $zip_filename.'.zip';
	$zip_path_filename = USER_FILES_BASE.'archives/'.$zip_filename;
	$data['zipFilename'] = $zip_filename;
	if (!$zip->open($zip_path_filename, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
	  $errors['archiveFile'] = "Cannot open or create: " . $zip_path_filename;
	}
  } else {
	$errors['folderToZip'] = 'No folder provided for zipping!';
  }

	$zip_files = new RecursiveIteratorIterator(
	  new RecursiveDirectoryIterator($zip_folder),
	  RecursiveIteratorIterator::LEAVES_ONLY
	);

// return a response =============================================

	// If there are any errors in our errors array, return a success boolean of false.
  if ( ! empty($errors)) {

	// If there are items in our errors array, return those errors.
	$data['success'] = false;
	$data['errors'] = $errors;
  } else {


	// FORM PROCESSING HERE
	// If there are no errors, process our form, then return a success message upon completion.

	// A single folder selection will provide the basis for an archive of the images with thumbnails
	// beneath it.
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
					// Skip directories (they will be added automatically)
					if (!$zip_file->isDir())
					{
					  // Get real and relative path for current file
					  $filePath = $zip_file->getRealPath();
					  // let's change the zip's path relative to files directory
					  preg_match('/(.*)(files.*)$/i', $filePath, $relativeFilePath);
					  $relativeFilePath = $relativeFilePath[2];

					  // Get the percentage of files now complete and add that percentage to a user unique
						// tmp file.
					  $percent = intval($i/$zip_count * 100);
					  $zip_progress['percent'] = $percent;
					  file_put_contents(USER_FILES_BASE . "tmp/" . session_id() . ".txt",
							json_encode($zip_progress));

					  // Add current file to archive
					  $zip->addFile($filePath, $relativeFilePath);

						// Pad time for progress update.
					  //usleep(12500);

						// Increase the while statement counter.
					  $i++;
					}
		    }

		}

	// Save the zip file.
	$zip->close();

  // Update database.
  $folder_create = FALSE; // no need to create the folder
  $zip_db = New FolderGrab($zip_db_folder, $folder_create);
  $zip_url = USER_FILES_URL.'archives/'.$zip_filename;
  $data['zipURL'] = $zip_url;
  $zip_db->addZip($zip_filename, $zip_url);
	}


	// show a message of success and provide a true success variable
	$data['success'] = true;
	$data['message'] = 'Success!';
  }

  // Return all our data to an AJAX call using JSON.
  // Declare document header.
  header('HTTP/1.1 200 OK');
  header('Content-Type: application/json');
  echo json_encode($data);
}
?>
