<?php
/*include $_SERVER['DOCUMENT_ROOT'].'/ips/php/boot.php';
session_start();
$login_cookie = new UserCookie();
$login_cookie->DeleteIt();
if($login_cookie->CheckIt()) {
	define("USERNAME", $login_cookie->username);
	$userval = new UserGrab(USERNAME);
	define("ADMIN_STATUS", $userval->admin); */
// Initialize GET variables
if(!empty($_GET['recursive'])){
	$recursive = $_GET['recursive'];
} else {
	$recursive = '0';
}
if(!empty($_GET['dir'])){
	$dir = $_GET['dir'];
} else {
	$dir = "ips/server/php/files";
}
if(!empty($_GET['search_ext'])){
	$search_ext = $_GET['search_ext'];
} else {
	$search_ext = '0';
}

// This function scans the files folder recursively, and builds a large array
function scan($dir,$recursive,$search_ext){

	$files = array();
	// comparison string for get variables
	$compare = '0';

	// Is there actually such a folder/file?

	if(file_exists($dir)){
	
		foreach(scandir($dir) as $f) {
		
			if(!$f || $f[0] == '.') {
				continue; // Ignore hidden files
			}
			
			if($f === 'thumbnail') {
				continue; // Ignore thumbnail folders
			}

			if(is_dir($dir . '/' . $f)) {

				// The path is a folder and request is for
				// recursive lookup. 
				if(strcmp($recursive, $compare) !== 0){
					$files[] = array(
						"name" => $f,
						"type" => "folder",
						"path" => $dir . '/' . $f,
						"items" => scan($dir . '/' . $f,$recursive,$search_ext) // Recursively get the contents of the folder
					);
				} else {
					
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
				// Search for requested items
				if(strcmp($search_ext, $compare) !== 0){
					$regex = "([a-zA-Z0-9\s_\\.\-\(\):])+(.".$search_ext.")$";
					if(preg_match("/".$regex."/i", $f)){
						$files[] = array(
							"name" => $f,
							"type" => "file",
							"path" => $dir . '/' . $f,
							"size" => filesize($dir .'/' . $f)
						);
					}
				} else {
					$files[] = array(
						"name" => $f,
						"type" => "file",
						"path" => $dir . '/' . $f,
						"size" => filesize($dir . '/' . $f) // Gets the size of this file
					);
				}
			}
		}
	}
	return $files;
}

// Run the scan function 
$response = scan($dir,$recursive,$search_ext);
	
// Output the directory listing as JSON
header('Content-type: application/json');

echo json_encode(array(
	"name" => "files",
	"type" => "folder",
	"path" => $dir,
	"items" => $response
));
//}
?>