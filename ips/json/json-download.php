<?php
chdir('..');
require_once(getcwd().'/php/boot.php');

$login_cookie = new UserCookie();
$login_cookie->DeleteIt();
if (!$login_cookie->CheckIt()) {
    http_response_code(403);
    exit;
}

$token    = preg_replace('/[^a-zA-Z0-9_]/',      '', $_GET['token']    ?? '');
$filename = preg_replace('/[^a-zA-Z0-9_.\-]/', '', $_GET['filename'] ?? 'download.zip');

if (!$token) {
    http_response_code(400);
    exit;
}

$zip_path      = getcwd() . '/upload/tmp/' . $token . '.zip';
$progress_file = getcwd() . '/upload/tmp/' . $token . '_progress.json';

if (!file_exists($zip_path)) {
    http_response_code(404);
    echo 'Zip not found';
    exit;
}

$filesize = filesize($zip_path);

// Hand off to nginx for the actual transfer — no PHP memory used for the file.
// nginx serves it via sendfile() and the internal /ips/upload/tmp/ location.
// Schedule cleanup: delete both files after a short delay so they survive the transfer.
// (A cron job handles final cleanup of stale files.)
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . $filesize);
header('X-Accel-Redirect: /ips/upload/tmp/' . $token . '.zip');
