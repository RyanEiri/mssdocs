<?php
chdir('..');
include getcwd().'/php/boot.php';
$login_cookie = new UserCookie();
$login_cookie->DeleteIt();
if($login_cookie->CheckIt()) {

	define("USERNAME", $login_cookie->username);

	$userval = new UserGrab(USERNAME);
	define("ADMIN_STATUS", $userval->admin);

	if(!empty($_POST['recursive'])){
		$recursive = $_POST['recursive'];
	} else {
		$recursive = '0';
	}

	// Refuse paths outside upload/files/ (see ips_confine_request in files_table.php).
	ips_confine_request('file_info');
	$dir = (isset($_POST['dir'])) ? USER_FILES_BASE.$_POST['dir']
	 : $dir = USER_FILES_BASE."files";
	if(isset($dir)){
		$path = preg_replace('/.*ips\/upload\/?/', '', $dir);
	}

	// This function scans the files folder recursively, and builds a large array

	function scan($dir,$recursive,$path){

		$files = array();

		// Is there actually such a folder/file?

		if(file_exists($dir)){
			foreach(scandir($dir) as $f) {

				if(!$f || $f[0] == '.') {
					continue; // Ignore hidden files
				}

				if(is_dir($dir . '/' . $f)) {

					// The path is a folder and request is for
					// recursive lookup.
					$compare = '1';
					if(strcmp($recursive, $compare) === 0){

						$files[] = array(
							"name" => $f,
							"type" => "folder",
							"path" => $path . '/' . $f,
							"modified" => filemtime($dir . '/' . $f),
							"public" => ips_dir_is_public(realpath($dir . '/' . $f)),
							"marked" => ips_dir_marked_public(realpath($dir . '/' . $f)),
							"items" => scan($dir . '/' . $f, $recursive,$path . '/' . $f) // Recursively get the contents of the folder
						);

					}

					else {

						// The path is a folder and request is not
						// for recursive lookup.

						$files[] = array(
							"name" => $f,
							"type" => "folder",
							"path" => $path . '/' . $f,
							"modified" => filemtime($dir . '/' . $f),
							"public" => ips_dir_is_public(realpath($dir . '/' . $f)),
							"marked" => ips_dir_marked_public(realpath($dir . '/' . $f)),
							"items" => count(scandir($dir . '/' . $f))-2
						);

					}

				}

				else {

					// It is a file

					$files[] = array(
						"name" => $f,
						"type" => "file",
						"path" => $path . '/' . $f,
						"modified" => filemtime($dir . '/' . $f),
						"size" => filesize($dir . '/' . $f) // Gets the size of this file
					);
				}
			}

		}

		return $files;
	}

	// Run the recursive function

	$response = scan($dir,$recursive,$path);

	// Output the directory listing as JSON

	header('Content-type: application/json');

	echo json_encode(array(
		"name" => "files",
		"type" => "folder",
		"path" => $path,
		"items" => $response,
		"recursive" => $recursive
	));
} else {
	echo 'You must be logged in!';
}
