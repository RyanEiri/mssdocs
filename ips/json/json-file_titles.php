<?php
/*
 * The catalogue titles of the files directly inside one folder, for the file browser's cards and list:
 *   GET json-file_titles.php?dir=files/Photographs  ->  {"titles": {"files/Photographs/a.png": "The harbour", ...}}
 * The folder must be inside upload/files/. Catalogue rows are named without the leading "files/"
 * (Photographs/a.png); only files whose title is not empty are returned. Needs a signed-in user.
 */
chdir('..');
include getcwd().'/php/boot.php';
header('Content-Type: application/json');
header('Cache-Control: no-store');

$cookie = new UserCookie();
if (!isset($_COOKIE['login']) || $cookie->Peek() === false) {
	http_response_code(401);
	echo json_encode(['error' => 'You must be logged in!']);
	exit;
}

$dir = isset($_GET['dir']) && $_GET['dir'] !== '' ? (string)$_GET['dir'] : 'files';
if (ips_resolve_dir($dir) === false) {
	http_response_code(403);
	echo json_encode(['error' => 'Invalid path.']);
	exit;
}

// "files" -> rows named "x.png"; "files/A/B" -> rows named "A/B/x.png". Escape LIKE's wildcards in the folder name.
$prefix = preg_replace('#^files/?#i', '', $dir);
$prefix = $prefix === '' ? '' : trim($prefix, '/') . '/';
$like = addcslashes($prefix, '\\%_') . '%';
$rows = $db->query('SELECT name, title FROM files WHERE name LIKE ? AND title <> ""', str_params($like))->fetchArray();

$titles = [];
foreach (is_array($rows) ? $rows : [] as $row) {
	$rest = substr($row['name'], strlen($prefix));
	if ($rest !== '' && strpos($rest, '/') === false) { // direct children only
		$titles['files/' . $prefix . $rest] = $row['title'];
	}
}
echo json_encode(['titles' => (object)$titles]);
