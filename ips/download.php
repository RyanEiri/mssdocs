<?php
require_once(getcwd().'/php/start_sess.php');
include getcwd().'/php/boot.php';

$file_id = isset($_GET['file_id']) ? $_GET['file_id'] : NULL;
$urlkey  = isset($_GET['urlkey'])  ? $_GET['urlkey']  : NULL;
$continue = (isset($file_id) && isset($urlkey)) ? TRUE : FALSE;

if ($continue === TRUE) {
	// Sanitize to basename only to prevent directory traversal
	$filename = basename($file_id);
	$filepath = BACKUP_BASE . $filename;

	if (!file_exists($filepath) || !is_file($filepath)) {
		die("File not found.");
	}

	$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
	$testurlkey = keymaker($ext);

	if ($testurlkey === $urlkey) {
		$file_size    = filesize($filepath);
		$content_type = ($ext === 'sql') ? 'sql' : 'zip';
		header('Pragma: public');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Cache-Control: public');
		header('Content-Description: File Transfer');
		header('Content-type: application/' . $content_type);
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		header('Content-Transfer-Encoding: binary');
		header('Content-Length: ' . $file_size);
		ob_end_flush();
		@readfile($filepath);
		exit;
	} else {
		die("Authentication error.");
	}
} else {
	echo "You don't appear to have the correct information.";
}
?>
