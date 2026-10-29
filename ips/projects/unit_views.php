<?php
// The Dublin Core record and the RAD description of a unit, made by the stylesheets of the file editor module (file_editor/metadata/unit-to-dc.xsl, unit-to-rad.xsl) from the unit's own
// description: {success, available, record: {id, status}, dc: [[term, value], ...], rad: XHTML}. "available" is false when PHP has no dom/xsl or the file editor module is not
// installed. The record's status is the unit's effective visibility. IPS_RAD_REPEAT_INHERITED (php/site-config.php) prints the inherited values as well (RAD footnote 1a).
chdir('..');
require_once(getcwd().'/php/start_sess.php');
require_once(getcwd().'/php/boot.php');
require_once __DIR__.'/unit_lib.php';
ips_units_user(false);
$id = isset($_GET['id']) && ctype_digit((string) $_GET['id']) ? (int) $_GET['id'] : 0;
$xml = $id ? ips_units_xml($id, ips_units_all()) : null;
if ($xml === null) {
	ips_projects_refuse(404, 'That project has no description as a unit.');
}
$lib = getcwd().'/file_editor/metadata/lib.php';
if (!is_file($lib)) {
	echo json_encode(['success' => true, 'available' => false, 'record' => null, 'dc' => [], 'rad' => null]);
	exit;
}
require_once $lib;
echo json_encode(['success' => true] + ips_fe_metadata_unit_views($xml, defined('IPS_RAD_REPEAT_INHERITED') && IPS_RAD_REPEAT_INHERITED), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
