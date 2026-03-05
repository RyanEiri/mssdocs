<?php
// Must be run from CLI — block any web access
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit(1);
}

ini_set('max_execution_time', '0'); // no limit for CLI
ini_set('memory_limit', '128M');

$token      = preg_replace('/[^a-zA-Z0-9_]/', '', $argv[1] ?? '');
$zip_folder = $argv[2] ?? '';

if (!$token || !$zip_folder || !is_dir($zip_folder)) {
    exit(1);
}

$ips_root      = dirname(__DIR__); // zip_worker.php lives in ips/php/
$tmp_dir       = $ips_root . '/upload/tmp';
$progress_file = $tmp_dir . '/' . $token . '_progress.json';
$zip_path      = $tmp_dir . '/' . $token . '.zip';

// Collect all files
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($zip_folder, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::LEAVES_ONLY
);
$all_files = [];
foreach ($iterator as $file) {
    if (!$file->isDir() && !in_array('thumbnail', explode('/', $file->getRealPath()))) $all_files[] = $file->getRealPath();
}
$total = count($all_files);
$base  = basename($zip_folder);

file_put_contents($progress_file, json_encode([
    'percent' => 0, 'done' => false, 'count' => 0, 'total' => $total
]));

$zip = new ZipArchive();
if (!$zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
    file_put_contents($progress_file, json_encode([
        'percent' => 0, 'done' => false, 'error' => 'Cannot create zip'
    ]));
    exit(1);
}

// addFile() never loads file contents into PHP memory — safe for large collections.
// CM_STORE skips compression (images are already compressed).
$i = 0;
foreach ($all_files as $filePath) {
    $rel = $base . '/' . substr($filePath, strlen($zip_folder) + 1);
    $zip->addFile($filePath, $rel);
    $zip->setCompressionName($rel, ZipArchive::CM_STORE);
    $i++;
    $percent = ($total > 0) ? intval($i / $total * 100) : 100;
    file_put_contents($progress_file, json_encode([
        'percent' => $percent, 'done' => false, 'count' => $i, 'total' => $total
    ]));
}

// Signal that we are finalizing (close() does all the actual I/O)
file_put_contents($progress_file, json_encode([
    'percent' => 100, 'done' => false,
    'count' => $total, 'total' => $total, 'finalizing' => true
]));

$zip->close();

// Build the same clean filename the main script would
$zip_db_folder = preg_replace('/^.*files\//', '', $zip_folder);
$zip_filename  = preg_replace('/ /',  '_', $zip_db_folder);
$zip_filename  = preg_replace('/\//', '-', $zip_filename);
$zip_filename  = preg_replace('/[^\d\w\s\_\-]/', '', $zip_filename);
$zip_filename .= '.zip';

file_put_contents($progress_file, json_encode([
    'percent'  => 100,
    'done'     => true,
    'count'    => $total,
    'total'    => $total,
    'token'    => $token,
    'filename' => $zip_filename,
    'filesize' => filesize($zip_path),
]));
