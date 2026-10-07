<?php
// Adds a link (id 0 or missing) or changes one: group_id, name, description, url, also (JSON [[label, url], ...]), hidden. Answers the saved link. An address already
// listed on another entry is refused, so the page never shows one twice. A link moved to another group goes to the end of that group.
chdir('..');
require_once(getcwd().'/php/start_sess.php');
require_once(getcwd().'/php/boot.php');
require_once __DIR__.'/lib.php';
$cookie = ips_links_user(true);

$id = isset($_POST['id']) && ctype_digit((string) $_POST['id']) ? (int) $_POST['id'] : 0;
$old = $id ? ips_links_get($id) : null;
if ($id && $old === null) {
	ips_links_refuse(404, 'That link no longer exists.');
}
$group_id = isset($_POST['group_id']) && ctype_digit((string) $_POST['group_id']) ? (int) $_POST['group_id'] : 0;
if (!ips_links_group_exists($group_id)) {
	ips_links_refuse(422, 'Choose a group.');
}
$name = ips_links_text($_POST['name'] ?? null, 255);
if ($name === null || $name === '') {
	ips_links_refuse(422, 'The name is required (at most 255 characters).');
}
$description = ips_links_text($_POST['description'] ?? '', 1000);
if ($description === null) {
	ips_links_refuse(422, 'The description is at most 1000 characters.');
}
$url = ips_links_url($_POST['url'] ?? null);
if ($url === null) {
	ips_links_refuse(422, 'The web address must start http:// or https:// and have no spaces.');
}
$also = ips_links_also($_POST['also'] ?? '');
if (is_string($also)) {
	ips_links_refuse(422, $also);
}
$same = ips_links_with_url($url, $id);
if ($same !== null) {
	ips_links_refuse(409, 'That address is already listed as "' . $same['name'] . '".');
}
$hidden = ($_POST['hidden'] ?? '') === '1' ? 1 : 0;
$also_json = $also ? json_encode($also, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
$by = $cookie->username;

if ($old === null) {
	$result = $db->query("INSERT INTO links (group_id, name, description, url, also, position, hidden, updated_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)", [
		['type' => 'i', 'value' => $group_id], ['type' => 's', 'value' => $name], ['type' => 's', 'value' => $description], ['type' => 's', 'value' => $url],
		['type' => 's', 'value' => $also_json], ['type' => 'i', 'value' => ips_links_next_position($group_id)], ['type' => 'i', 'value' => $hidden], ['type' => 's', 'value' => $by],
	]);
	$id = $result->getId();
} else {
	$position = $group_id === $old['group_id'] ? $old['position'] : ips_links_next_position($group_id);
	$db->query("UPDATE links SET group_id=?, name=?, description=?, url=?, also=?, position=?, hidden=?, updated_by=? WHERE id=?", [
		['type' => 'i', 'value' => $group_id], ['type' => 's', 'value' => $name], ['type' => 's', 'value' => $description], ['type' => 's', 'value' => $url],
		['type' => 's', 'value' => $also_json], ['type' => 'i', 'value' => $position], ['type' => 'i', 'value' => $hidden], ['type' => 's', 'value' => $by], ['type' => 'i', 'value' => $id],
	]);
}
echo json_encode(['success' => true, 'link' => ips_links_get($id)]);
