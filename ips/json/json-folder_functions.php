<?php
// This script creates a zip archive based on a single folder selection.

// Increase time to complete script. Zip files can take a long time to save.
ini_set('max_execution_time', '3600');
ini_set('max_input_time', '3600');

// Perform application bootstrap.
chdir('..');
include getcwd().'/php/boot.php';
define("PROGRESS_FILE", USER_FILES_BASE . "tmp/" . session_id() . ".txt");

// User authentication.
$login_cookie = new UserCookie();
//$login_cookie->DeleteIt();

// Everything between this if statement assumes an authenticated user.
if($login_cookie->CheckIt()) {
	$username = $login_cookie->username;
	$userval = new UserGrab($username);
	$admin = $userval->admin;

  // Initialize the zip file.
  $zip = new ZipArchive();

  // Initialize our JSON arrays.
  $errors	= array();	// array to hold validation errors
  $data		= array();	// array to pass back data
  $zip_progress = array();	// array for progress bar callback

  // validate the variables ========================================
  // If any of these variables don't exist, add an error to our
  // $errors array. If they do exist add them to a variable value.
  if (!empty($_POST['folderToZip'])){
		$zip_folder = USER_FILES_BASE.$_POST['folderToZip'];

		// do some regex magic
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
		// Zip full path and filename for archive creation.
		$zip_path_filename = USER_FILES_BASE.'archives/'.$zip_filename;

		// Provide Zip Filename in data.
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

	// Fail if there are any errors in our errors array.
  if ( ! empty($errors)) {
		$data['success'] = FALSE; // Return a FALSE success value.
		$data['errors'] = $errors; // If there are items in our errors array, return those errors.
  } else {
		// FORM PROCESSING HERE
		// If there are no errors, process our form and return a success message.

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
					  file_put_contents(PROGRESS_FILE, json_encode($zip_progress));
					  // Add current file to archive
					  $zip->addFile($filePath, $relativeFilePath);

						// Pad time for progress update.
					  //usleep(4500);

					  $i++;
					}
		    }
			}
		}

	  // Unlock the session for large archives
		/* fastcgi_finish_request for large archives
		*  when fastcgi support is enabled
		*/
		session_write_close();
		if (function_exists('fastcgi_finish_request')) {
			fastcgi_finish_request();
		}
		// Save the zip file.
		$zip->close();

		// Put together Zip URL for db update.
		$zip_url = USER_FILES_URL.'archives/'.$zip_filename;
	  // Update database.
	  $folder_create = FALSE; // no need to create the folder
	  $zip_db = New FolderGrab($zip_db_folder, $folder_create);
	  $zip_db->addZip($zip_filename, $zip_url);

		// Provide Zip URL in data.
		$data['zipURL'] = $zip_url;
		// Provide success value of true in data.
		$data['success'] = true;
		$data['message'] = 'Success!';
	}

  // JSON output
  /* encode data as _response_: deprecated in favour of _progress_file_ for large archive support
  *  header('HTTP/1.1 200 OK');
  *  header('Content-Type: application/json');
  *  echo json_encode($data);
	*/

	// encode data as a file
	file_put_contents(PROGRESS_FILE, json_encode($data));
}
?>
