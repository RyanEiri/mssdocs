<?php
// One unit with what a view needs: its description, the chain of units above it, its reference code, its effective visibility (the least public along the chain), the fields it
// inherits, its children, its items (files linked with the role "description") and advice about its dates.
chdir('..');
require_once(getcwd().'/php/start_sess.php');
require_once(getcwd().'/php/boot.php');
require_once __DIR__.'/unit_lib.php';
ips_units_user(false);
$id = isset($_GET['id']) && ctype_digit((string) $_GET['id']) ? (int) $_GET['id'] : 0;
$detail = $id ? ips_units_detail($id, ips_units_all()) : null;
if ($detail === null) {
	ips_projects_refuse(404, 'That project has no description as a unit.');
}
echo json_encode(['success' => true] + $detail, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
