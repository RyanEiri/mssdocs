<?php
chdir('..');
require_once(getcwd().'/php/boot.php');

$login_cookie = new UserCookie();
$login_cookie->DeleteIt();
if (!$login_cookie->CheckIt()) {
    header('Content-Type: application/json');
    echo json_encode(['percent' => 0, 'error' => 'not authenticated']);
    exit;
}

$token = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['token'] ?? $_GET['token'] ?? '');

header('Content-Type: application/json');

if (!$token) {
    echo json_encode(['percent' => 0, 'error' => 'no token']);
    exit;
}

$progress_file = getcwd() . '/upload/tmp/' . $token . '_progress.json';

if (file_exists($progress_file)) {
    echo file_get_contents($progress_file);
} else {
    echo json_encode(['percent' => 0, 'done' => false]);
}
