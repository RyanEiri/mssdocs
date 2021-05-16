<?php
chdir('..');
include getcwd().'/php/boot.php';
$login_cookie = new UserCookie();
$login_cookie->DeleteIt();
if($login_cookie->CheckIt()) {
	// Grab the current user's info.
	$username = $login_cookie->username;
	$userval = new UserGrab($username);

  // begin our ajax handling
  $errors	= array();	// array to hold validation errors
  $data		= array();	// array to pass back data

  // validate the variables ========================================
  // If any of these variables don't exist, add an error to our
  // $errors array. If they do exist add them to a variable value.

  if (empty($_POST['folder'])){
	$errors['folder'] = 'A folder name is required.';
  } elseif(isset($_POST['folder'])){
	$folder	= $_POST['folder'];
	if (strpbrk($_POST['folder'], "\\/?%*:|\"<>") === FALSE) {
	  $folder = $_POST['folder'];
	} else {
	  $errors['folder'] = 'Folder name contains illegal characters.';
	}
  }

  if (empty($_POST['path'])){
	$errors['path'] = 'Path value has not been supplied!';
  } elseif(isset($_POST['path'])){
	$path = $_POST['path'];
	$dir = USER_FILES_BASE.$path;
  }

  if (!empty($_POST['thumbnail'])) {
    if($_POST['thumbnail'] === "true") {
	$thumbnail = 1;
    } else {
	$thumbnail = 0;
    }
  }

// return a response =============================================

  // if there are any errors in our errors array,
  // return a success boolean of false
  if (!empty($errors)) {

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
	mkdir($dir.'/'.$folder, 0755);
	mkdir($dir.'/'.$folder.'/thumbnail', 0755);
	// add folder to folders table
	$path_pattern = '/files\//';
	$path_replacement = '';
	$path = preg_replace($path_pattern, $path_replacement, $path);
	$db_folder = $path.'/'.$folder;
	$folder_create = TRUE; // this is where the folder is created in db
	$folderval = new FolderGrab($db_folder, $folder_create);

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
