<?php
/*
 * The HTML files the editor can open, as JSON: a tree of folders and files under upload/files/ (or under ?dir=, which
 * must be inside it), keeping only files with the extension in ?search_ext (default: html). Paths are relative to
 * upload/ (files/html_templates/Resources.html), the form edit.php takes. Symlinks are not followed.
 */
chdir('..');
include getcwd().'/php/boot.php';
$login_cookie = new UserCookie();
$login_cookie->DeleteIt();
header('Content-Type: application/json');

if (!isset($_COOKIE['login']) || $login_cookie->Peek() === false) {
	http_response_code(401);
	echo json_encode(array('success' => false, 'errors' => array('login' => 'You must be logged in!')));
	exit;
}

$dir = ips_resolve_dir(isset($_GET['dir']) && $_GET['dir'] !== '' ? (string)$_GET['dir'] : 'files');
if ($dir === false) {
	http_response_code(403);
	echo json_encode(array('success' => false, 'errors' => array('dir' => 'Invalid path.')));
	exit;
}
$extension = strtolower(ltrim(isset($_GET['search_ext']) ? (string)$_GET['search_ext'] : 'html', '.'));
$upload = realpath(ips_upload_base());

function ips_html_scan($dir, $upload, $extension) {
	$items = array();
	foreach (scandir($dir) as $name) {
		if ($name === '' || $name[0] === '.') {
			continue; // hidden files
		}
		$full = $dir . '/' . $name;
		if (is_link($full)) {
			continue;
		}
		$relative = substr($full, strlen($upload) + 1);
		if (is_dir($full)) {
			$items[] = array('name' => $name, 'type' => 'folder', 'path' => $relative,
				'items' => ips_html_scan($full, $upload, $extension));
		} elseif ($extension === '' || strtolower(pathinfo($name, PATHINFO_EXTENSION)) === $extension) {
			$items[] = array('name' => $name, 'type' => 'file', 'path' => $relative, 'size' => filesize($full));
		}
	}
	return $items;
}

echo json_encode(array(
	'name' => basename($dir),
	'type' => 'folder',
	'path' => substr($dir, strlen($upload) + 1),
	'items' => ips_html_scan($dir, $upload, $extension),
));
