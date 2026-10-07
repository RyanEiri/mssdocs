<?php
// Deletes a link (id). The page asks twice first; the row is gone for good, so a link that may come back is better hidden (the Hidden box on the form).
chdir('..');
require_once(getcwd().'/php/start_sess.php');
require_once(getcwd().'/php/boot.php');
require_once __DIR__.'/lib.php';
ips_links_user(true);
$id = isset($_POST['id']) && ctype_digit((string) $_POST['id']) ? (int) $_POST['id'] : 0;
if (!$id || ips_links_get($id) === null) {
	ips_links_refuse(404, 'That link no longer exists.');
}
$db->query("DELETE FROM links WHERE id=?", [['type' => 'i', 'value' => $id]]);
echo json_encode(['success' => true]);
