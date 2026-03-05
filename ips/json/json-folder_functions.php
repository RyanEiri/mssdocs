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

// Safety: ensure path is within USER_FILES_BASE
$real_folder     = realpath($zip_folder);
$real_files_base = realpath(USER_FILES_BASE);
if (!$real_folder || strpos($real_folder, $real_files_base) !== 0) {
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

// Write initial progress so polling can start immediately
$tmp_dir       = getcwd() . '/upload/tmp';
$progress_file = $tmp_dir . '/' . $token . '_progress.json';
file_put_contents($progress_file, json_encode([
    'percent' => 0, 'done' => false, 'count' => 0, 'total' => $count
]));

session_write_close();

// Launch background worker — returns immediately
$worker = getcwd() . '/php/zip_worker.php';
$cmd = '/usr/bin/php8.2 ' . escapeshellarg($worker)
     . ' ' . escapeshellarg($token)
     . ' ' . escapeshellarg($real_folder)
     . ' > /dev/null 2>&1 &';
exec($cmd);

echo json_encode(['success' => true, 'token' => $token, 'count' => $count]);
