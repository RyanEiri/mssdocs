<?php
require_once(getcwd().'/php/start_sess.php');
include getcwd().'/php/boot.php';

$file_id = isset($_GET['file_id']) ?  $_GET['file_id'] : NULL;
$urlkey = isset($_GET['urlkey']) ? $_GET['urlkey'] : NULL;
$continue = (isset($file_id) && isset($urlkey)) ? TRUE : FALSE;

if($continue === TRUE) {
	switch ($file_id) {
		case "3556":
			$contentid = 'zip';
			$filename = 'vesturheimsrit_com-20210520.zip';
			$file_size = filesize(BACKUP_BASE.$filename);
			$testurlkey = keymaker($contentid);
			$content_type = 'zip';
			break;
		case "3557":
			$contentid = 'sql';
			$filename = 'vesturheimsrit_com-db-20210520.sql';
			$file_size = filesize(BACKUP_BASE.$filename);
			$testurlkey = keymaker($contentid);
			$content_type = 'sql';
			break;
		case "3558":
			$contentid = 'upload_files';
			$filename = 'vesturheimsrit_com-upload_files-20210519.zip';
			$file_size = filesize(BACKUP_BASE.$filename);
			$testurlkey = keymaker($contentid);
			$content_type = 'zip';
			break;
	}
	if($testurlkey === $urlkey) {
		header('Pragma: public');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Cache-Control: public');
		header('Content-Description: File Transfer');
		header('Content-type: application/"'.$content_type.'"');
		header('Content-Disposition: attachment; filename="'.$filename.'"');
		header('Content-Transfer-Encoding: binary');
		header('Content-Length: '.$file_size);
		ob_end_flush();
		@readfile(BACKUP_BASE.$filename);
		exit;
	} else {
		die("Authentication error.");
	}
} else {
	echo "You don't appear to have the correct information.";
}
?>
