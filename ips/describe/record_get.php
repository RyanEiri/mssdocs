<?php
// One record for the Describe page: GET dir=<records folder>&id=<record id>. Answers {success, record (id, struct, questions, open, etag, photos), preview (Dublin Core, RAD, completeness when PHP has
// dom and xsl)}. A record this module does not edit (a manuscript, a DTD...) answers 422 with the reason. For signed-in users; nothing is changed.
chdir('..');
require_once(getcwd().'/php/start_sess.php');
require_once(getcwd().'/php/boot.php');
require_once __DIR__.'/endpoint.php';
ips_describe_user(false);
$folder = ips_describe_folder($_GET['dir'] ?? '');
$path = ips_describe_record_file($folder, $_GET['id'] ?? '');
$text = (string) file_get_contents($path);
$view = ips_describe_view($folder, $_GET['id'], $text);
if (isset($view['error'])) {
	ips_describe_refuse(422, $view['error']);
}
echo json_encode(['success' => true, 'record' => $view, 'preview' => ips_describe_preview($text)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
