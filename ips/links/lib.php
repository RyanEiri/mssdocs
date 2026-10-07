<?php
/* The Links module (ips/links/): the entries of a site's Links page, kept in the tables of schema.sql (link_groups, links) and edited through a form. Everything
 * the endpoints share is here: who may edit, what a link may hold, and the queries. Any signed-in user may add, change, move and delete links (the site's public
 * page is what they publish; there is no per-user folder to confine them to). Every write is a same-origin POST with the user's CSRF token, bound parameters only.
 */

const IPS_LINKS_MAX_ALSO = 10;

function ips_links_refuse($status, $message) {
	http_response_code($status);
	echo json_encode(['success' => false, 'error' => $message]);
	exit;
}

// The signed-in user for a JSON endpoint (403 otherwise), and for a write, a valid same-origin POST with the user's token (RequireCsrf answers 403/405 itself).
function ips_links_user($write) {
	header('Content-Type: application/json');
	$cookie = new UserCookie();
	if ($cookie->Peek() === false) {
		ips_links_refuse(403, 'Sign in to edit the links.');
	}
	if ($write) {
		$cookie->RequireCsrf();
	}
	if (!ips_links_ready()) {
		ips_links_refuse(503, 'The links tables have not been created yet (run links/schema.sql).');
	}
	return $cookie;
}

function ips_links_ready() {
	global $db;
	return $db->query("SHOW TABLES LIKE 'links'")->size() > 0 && $db->query("SHOW TABLES LIKE 'link_groups'")->size() > 0;
}

// Text from a form field: one line of plain text, whitespace collapsed, at most $max characters, or null when it is not a string or too long.
function ips_links_text($value, $max) {
	if (!is_string($value)) {
		return null;
	}
	$value = trim(preg_replace('/\s+/u', ' ', str_replace("\xc2\xa0", ' ', $value)));
	return $value === '' || mb_strlen($value, 'UTF-8') <= $max ? $value : null;
}

// A web address: http or https, a host, no spaces, at most 2048 characters. Returns it trimmed, or null.
function ips_links_url($value) {
	if (!is_string($value)) {
		return null;
	}
	$value = trim($value);
	$parts = parse_url($value);
	if ($value === '' || strlen($value) > 2048 || preg_match('/[\s<>"]/', $value) || !is_array($parts)
		|| !in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true) || ($parts['host'] ?? '') === '') {
		return null;
	}
	return $value;
}

// The other addresses of an entry, from the form's JSON ([[label, url], ...]): each with a label and a valid address, at most IPS_LINKS_MAX_ALSO. Returns the
// cleaned list, or a string with the problem.
function ips_links_also($json) {
	$list = is_string($json) && $json !== '' ? json_decode($json, true) : [];
	if (!is_array($list) || count($list) > IPS_LINKS_MAX_ALSO) {
		return 'Other addresses: at most ' . IPS_LINKS_MAX_ALSO . '.';
	}
	$out = [];
	foreach ($list as $pair) {
		$label = is_array($pair) ? ips_links_text($pair[0] ?? null, 255) : null;
		$url = is_array($pair) ? ips_links_url($pair[1] ?? null) : null;
		if ($label === null || $label === '' || $url === null) {
			return 'Each other address needs a label and a web address starting http:// or https://.';
		}
		$out[] = [$label, $url];
	}
	return $out;
}

// One row as the endpoints and the page see it.
function ips_links_row($r) {
	$also = $r['also'] === null || $r['also'] === '' ? [] : json_decode($r['also'], true);
	return ['id' => (int) $r['id'], 'group_id' => (int) $r['group_id'], 'name' => $r['name'], 'description' => $r['description'], 'url' => $r['url'],
		'also' => is_array($also) ? $also : [], 'position' => (int) $r['position'], 'hidden' => (int) $r['hidden'] === 1, 'updated_at' => $r['updated_at'], 'updated_by' => $r['updated_by']];
}

function ips_links_groups() {
	global $db;
	$out = [];
	$result = $db->query("SELECT id, name FROM link_groups ORDER BY position, id");
	while ($r = $result->fetch()) {
		$out[] = ['id' => (int) $r['id'], 'name' => $r['name']];
	}
	return $out;
}

// Every link in the page's order (group, then position), hidden ones included.
function ips_links_all() {
	global $db;
	$out = [];
	$result = $db->query("SELECT l.* FROM links l JOIN link_groups g ON g.id = l.group_id ORDER BY g.position, g.id, l.position, l.id");
	while ($r = $result->fetch()) {
		$out[] = ips_links_row($r);
	}
	return $out;
}

function ips_links_get($id) {
	global $db;
	$rows = $db->query("SELECT * FROM links WHERE id=? LIMIT 1", [['type' => 'i', 'value' => $id]])->fetchArray();
	return $rows ? ips_links_row($rows[0]) : null;
}

function ips_links_group_exists($id) {
	global $db;
	return $db->query("SELECT id FROM link_groups WHERE id=? LIMIT 1", [['type' => 'i', 'value' => $id]])->size() > 0;
}

// The next free position at the end of a group.
function ips_links_next_position($group_id) {
	global $db;
	$rows = $db->query("SELECT COALESCE(MAX(position), 0) + 1 AS p FROM links WHERE group_id=?", [['type' => 'i', 'value' => $group_id]])->fetchArray();
	return (int) $rows[0]['p'];
}

// The entry that already has this address (other than $except_id), or null: an address is listed once.
function ips_links_with_url($url, $except_id) {
	global $db;
	$rows = $db->query("SELECT * FROM links WHERE url=? AND id<>? LIMIT 1", [['type' => 's', 'value' => $url], ['type' => 'i', 'value' => $except_id]])->fetchArray();
	return $rows ? ips_links_row($rows[0]) : null;
}
