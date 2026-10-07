<?php
// Moves a link one place up or down inside its group (id, dir = up|down): the group's positions are renumbered 1..n in the order shown, then the two swap.
chdir('..');
require_once(getcwd().'/php/start_sess.php');
require_once(getcwd().'/php/boot.php');
require_once __DIR__.'/lib.php';
ips_links_user(true);
$id = isset($_POST['id']) && ctype_digit((string) $_POST['id']) ? (int) $_POST['id'] : 0;
$dir = $_POST['dir'] ?? '';
$link = $id ? ips_links_get($id) : null;
if ($link === null) {
	ips_links_refuse(404, 'That link no longer exists.');
}
if ($dir !== 'up' && $dir !== 'down') {
	ips_links_refuse(400, 'dir must be up or down.');
}
$ids = [];
$result = $db->query("SELECT id FROM links WHERE group_id=? ORDER BY position, id", [['type' => 'i', 'value' => $link['group_id']]]);
while ($r = $result->fetch()) {
	$ids[] = (int) $r['id'];
}
$at = array_search($id, $ids, true);
$to = $dir === 'up' ? $at - 1 : $at + 1;
if (isset($ids[$to])) {
	[$ids[$at], $ids[$to]] = [$ids[$to], $ids[$at]];
}
foreach ($ids as $i => $each) {
	$db->query("UPDATE links SET position=? WHERE id=?", [['type' => 'i', 'value' => $i + 1], ['type' => 'i', 'value' => $each]]);
}
echo json_encode(['success' => true]);
