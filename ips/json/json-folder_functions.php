<?php
ini_set('max_execution_time', '30');

chdir('..');
require_once(getcwd().'/php/start_sess.php');
include getcwd().'/php/boot.php';

header('Content-Type: application/json');

$login_cookie = new UserCookie();
if (!$login_cookie->CheckIt()) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

if (empty($_POST['folderToZip'])) {
    echo json_encode(['success' => false, 'error' => 'No folder provided']);
    exit;
}

$token = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['token'] ?? '');
if (!$token) {
    echo json_encode(['success' => false, 'error' => 'No token provided']);
    exit;
}

$zip_folder = USER_FILES_BASE . $_POST['folderToZip'];

// Safety: the folder must be inside upload/files/ (the trailing slash stops a sibling such as
// upload/files-old from passing a plain prefix test, and keeps upload/tmp out of reach).
$real_folder     = realpath($zip_folder);
$real_files_base = realpath(USER_FILES_BASE . 'files');
if (!$real_folder || !$real_files_base || strpos($real_folder . '/', $real_files_base . '/') !== 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid folder path']);
    exit;
}

// Count files for the UI
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($real_folder, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::LEAVES_ONLY
);
$count = 0;
foreach ($iterator as $file) {
    if (!$file->isDir()) $count++;
}

// Zips and progress files live in upload/tmp. Create it on first use and drop anything a day old
// so finished downloads don't pile up.
$tmp_dir = getcwd() . '/upload/tmp';
if (!is_dir($tmp_dir) && !mkdir($tmp_dir, 0770, true)) {
    echo json_encode(['success' => false, 'error' => 'Cannot create the temporary directory']);
    exit;
}
foreach (glob($tmp_dir . '/*_progress.json') as $stale_progress) {
    if (filemtime($stale_progress) < time() - 86400) {
        @unlink($stale_progress);
        @unlink(substr($stale_progress, 0, -strlen('_progress.json')) . '.zip');
    }
}

// Write initial progress so polling can start immediately
$progress_file = $tmp_dir . '/' . $token . '_progress.json';
file_put_contents($progress_file, json_encode([
    'percent' => 0, 'done' => false, 'count' => 0, 'total' => $count
]));

session_write_close();

// Launch background worker — returns immediately
$worker = getcwd() . '/php/zip_worker.php';
$php_cli = 'php';
foreach (['/usr/bin/php8.2', '/usr/bin/php', '/usr/local/bin/php'] as $candidate) {
    if (is_executable($candidate)) { $php_cli = $candidate; break; }
}
$cmd = escapeshellarg($php_cli) . ' ' . escapeshellarg($worker)
     . ' ' . escapeshellarg($token)
     . ' ' . escapeshellarg($real_folder)
     . ' > /dev/null 2>&1 &';
exec($cmd);

echo json_encode(['success' => true, 'token' => $token, 'count' => $count]);
