<?php
include $_SERVER['DOCUMENT_ROOT'].'/ips/php/boot.php';
session_start();
$login_cookie = new UserCookie();
$login_cookie->DeleteIt();
if($login_cookie->CheckIt()) {

	define("USERNAME", $login_cookie->username);

	$userval = new UserGrab(USERNAME);
	define("ADMIN_STATUS", $userval->admin);

	if(!empty($_GET['recursive'])){
		$recursive = $_GET['recursive'];
	} else {
		$recursive = '0';
	}
	if(!empty($_GET['dir'])){
		$dir = $_GET['dir'];
	} else {
		$dir = "../upload/files";
	}

	// This function scans the files folder recursively, and builds a large array

	function scan($dir,$recursive){

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
							"path" => $dir . '/' . $f,
							"items" => scan($dir . '/' . $f, $recursive) // Recursively get the contents of the folder
						);

					}

					else {

						// The path is a folder and request is not
						// for recursive lookup.

						$files[] = array(
							"name" => $f,
							"type" => "folder",
							"path" => $dir . '/' . $f
						);

					}

				}

				else {

					// It is a file

					$files[] = array(
						"name" => $f,
						"type" => "file",
						"path" => $dir . '/' . $f,
						"size" => filesize($dir . '/' . $f) // Gets the size of this file
					);
				}
			}

		}

		return $files;
	}

	// Run the recursive function

	$response = scan($dir,$recursive);

	// Output the directory listing as JSON

	header('Content-type: application/json');

	echo json_encode(array(
		"name" => "files",
		"type" => "folder",
		"path" => $dir,
		"items" => $response,
		"recursive" => $recursive
	));
} else {
	echo 'You must be logged in!';
}
