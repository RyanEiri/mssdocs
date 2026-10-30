<?php
/* The Articles module (ips/articles/): stories with a title, byline, sanitised HTML body, section, keywords, versions and an optional place in an issue. Design and reasons:
 * docs/mssdocs-articles-design.md (webserversAC repository). Everything the endpoints share is here.
 *
 * Who may do what (the same rule as issues, decided by Ryan 2026-10-30): any signed-in user writes an article as a draft and edits a draft; only an administrator sets any other status
 * (publishes, schedules, unpublishes), edits an article that is not a draft, or removes one; the maker may remove their own draft. Every write is a same-origin POST with the user's CSRF
 * token, bound parameters only. Reading follows ips_access_can_read() (php/access.php), with a scheduled article whose time has come counted as published.
 */
require_once __DIR__ . '/../php/access.php';
require_once __DIR__ . '/../php/text_fold.php';
require_once __DIR__ . '/sanitize.php';

const IPS_ARTICLE_STATUSES = ['draft' => 'Draft', 'scheduled' => 'Scheduled', 'published' => 'Published', 'unpublished' => 'Unpublished'];
const IPS_ARTICLE_ACCESS = ['open' => 'Open to everyone', 'signed-in' => 'Signed-in readers only'];
const IPS_ARTICLE_REVISIONS_KEPT = 100;
const IPS_ARTICLE_TERMS_MAX = 20;
const IPS_ARTICLE_BODY_MAX = 1000000; // characters of HTML

function ips_articles_refuse($status, $message) {
	http_response_code($status);
	echo json_encode(['success' => false, 'error' => $message]);
	exit;
}

function ips_articles_ready() {
	global $db;
	foreach (['articles', 'article_terms', 'article_revisions'] as $table) {
		if ($db->query("SHOW TABLES LIKE '$table'")->size() === 0) {
			return false;
		}
	}
	return true;
}

// The signed-in user for a JSON endpoint (403 otherwise); for a write also a valid same-origin POST with the user's token. Returns [username, is_admin].
function ips_articles_user($write) {
	global $login_cookie;
	header('Content-Type: application/json');
	header('Cache-Control: no-store');
	$login_cookie = new UserCookie();
	if ($login_cookie->Peek() === false) {
		ips_articles_refuse(403, 'Sign in to use articles.');
	}
	if ($write) {
		$login_cookie->RequireCsrf();
	}
	if (!ips_articles_ready()) {
		ips_articles_refuse(503, 'The articles tables have not been created yet (run articles/schema.sql).');
	}
	return ips_current_user();
}

// ---- what a form sends ------------------------------------------------------------------------------------------------------------------------

function ips_articles_length($value) {
	$n = preg_match_all('/./us', $value);
	return $n === false ? null : $n;
}

function ips_articles_text_field($value, $max) {
	if (!is_string($value)) {
		return null;
	}
	$value = preg_replace('/\s+/u', ' ', str_replace("\xc2\xa0", ' ', $value));
	if ($value === null) {
		return null;
	}
	$value = trim($value);
	$length = ips_articles_length($value);
	return $length !== null && $length <= $max ? $value : null;
}

// An address for the article's image: an http(s) address or a path on the site, or ''. Null when it is something else.
function ips_articles_image($value) {
	$value = ips_articles_text_field($value, 1000);
	if ($value === null) {
		return null;
	}
	return $value === '' || ips_articles_url($value, false) !== null ? $value : null;
}

// The keywords from a form: a list or a comma/semicolon-separated string; each at most 60 characters, at most 20, once each (folded). [list of [term, key], null] or [null, message].
function ips_articles_terms($given) {
	if (is_string($given)) {
		$given = preg_split('/[;,\n]+/u', $given);
	}
	if (!is_array($given)) {
		return [null, 'The keywords are a list.'];
	}
	$out = [];
	foreach ($given as $term) {
		$term = ips_articles_text_field(is_string($term) ? $term : '', 60);
		if ($term === null) {
			return [null, 'A keyword is at most 60 characters.'];
		}
		$key = ips_fold($term);
		if ($term === '' || $key === '') {
			continue;
		}
		if (!isset($out[$key])) {
			$out[$key] = [$term, $key];
		}
	}
	if (count($out) > IPS_ARTICLE_TERMS_MAX) {
		return [null, 'At most ' . IPS_ARTICLE_TERMS_MAX . ' keywords.'];
	}
	return [array_values($out), null];
}

// A UTC date and time from "2026-11-05T14:30", "2026-11-05T14:30:00" or "2026-11-05 14:30", with an optional trailing Z: 'Y-m-d H:i:s' or null.
function ips_articles_datetime($value) {
	if (!is_string($value)) {
		return null;
	}
	$value = rtrim(trim($value), 'Zz');
	foreach (['Y-m-d\TH:i:s', 'Y-m-d\TH:i', 'Y-m-d H:i:s', 'Y-m-d H:i'] as $format) {
		$d = DateTime::createFromFormat('!' . $format, $value, new DateTimeZone('UTC'));
		$errors = DateTime::getLastErrors();
		if ($d !== false && (!$errors || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))) {
			return $d->format('Y-m-d H:i:s');
		}
	}
	return null;
}

/* The description of an article from a form: [values, null] or [null, message]. $existing is the article being changed (null for a new one). It does not touch status, access or publish_at:
 * the endpoints decide who may set those. The body is cleaned here. */
function ips_articles_fields($post, $existing) {
	global $db;
	$v = [];
	foreach (['title' => 255, 'summary' => 1000, 'section' => 80, 'authors' => 500, 'seo_title' => 255, 'seo_description' => 320] as $field => $max) {
		$value = ips_articles_text_field($post[$field] ?? ($existing[$field] ?? ''), $max);
		if ($value === null) {
			return [null, "The $field is at most $max characters."];
		}
		$v[$field] = $value;
	}
	if ($v['title'] === '') {
		return [null, 'The title is required.'];
	}
	$v['image'] = ips_articles_image($post['image'] ?? ($existing['image'] ?? ''));
	if ($v['image'] === null) {
		return [null, 'The image must be a web address (http or https) or a path on this site.'];
	}
	$body = $post['body'] ?? ($existing['body'] ?? '');
	if (!is_string($body) || strlen($body) > IPS_ARTICLE_BODY_MAX) {
		return [null, 'The body is too long, or not text.'];
	}
	$v['body'] = ips_articles_clean($body);
	$issue = $post['issue_id'] ?? ($existing['issue_id'] ?? '');
	$page = $post['issue_page'] ?? ($existing['issue_page'] ?? '');
	$v['issue_id'] = null;
	$v['issue_page'] = null;
	if ($issue !== '' && $issue !== null && $issue !== 0 && $issue !== '0') {
		if (!is_scalar($issue) || !ctype_digit((string) $issue)) {
			return [null, 'The issue must be an issue of this site.'];
		}
		if (!ips_articles_issues_available() || $db->query("SELECT id FROM issues WHERE id=? LIMIT 1", [['type' => 'i', 'value' => (int) $issue]])->size() === 0) {
			return [null, 'That issue does not exist.'];
		}
		$v['issue_id'] = (int) $issue;
		if ($page !== '' && $page !== null) {
			if (!is_scalar($page) || !ctype_digit((string) $page) || (int) $page < 1 || (int) $page > 9999) {
				return [null, 'The issue page must be a page number.'];
			}
			$v['issue_page'] = (int) $page;
		}
	}
	return [$v, null];
}

// Whether the Issues module's tables are there (an article may name an issue only then).
function ips_articles_issues_available() {
	global $db;
	return $db->query("SHOW TABLES LIKE 'issues'")->size() > 0;
}

// ---- slugs ------------------------------------------------------------------------------------------------------------------------------------

// A slug from a title: its folded words joined by hyphens, at most 80 characters, cut at a word; made unique by -2, -3... ($exclude is the article's own id).
function ips_articles_slug($title, $exclude = 0) {
	global $db;
	$words = array_values(array_filter(explode(' ', ips_fold($title)), 'strlen'));
	$slug = '';
	foreach ($words as $word) {
		if (strlen($slug) + strlen($word) + 1 > 80) {
			break;
		}
		$slug .= ($slug === '' ? '' : '-') . $word;
	}
	$slug = preg_replace('/[^a-z0-9-]/', '', $slug);
	$slug = $slug === '' ? 'article' : $slug;
	$candidate = $slug;
	for ($n = 2; $n < 1000; $n++) {
		if ($db->query("SELECT id FROM articles WHERE slug=? AND id<>? LIMIT 1", [['type' => 's', 'value' => $candidate], ['type' => 'i', 'value' => $exclude]])->size() === 0) {
			return $candidate;
		}
		$candidate = $slug . '-' . $n;
	}
	return $slug . '-' . bin2hex(random_bytes(3));
}

// ---- reading ----------------------------------------------------------------------------------------------------------------------------------

function ips_articles_iso($datetime) {
	return $datetime === null || $datetime === '' ? null : gmdate('Y-m-d\TH:i:s\Z', strtotime($datetime . ' UTC'));
}

// The status a reader sees: a scheduled article whose time has come is published. $now is a UTC timestamp (injected by tests).
function ips_articles_effective_status($status, $publish_at, $now = null) {
	if ($status === 'scheduled' && $publish_at !== null && $publish_at !== '' && strtotime($publish_at . ' UTC') <= ($now ?? time())) {
		return 'published';
	}
	return $status;
}

function ips_articles_row($r) {
	return ['id' => (int) $r['id'], 'title' => $r['title'], 'slug' => $r['slug'], 'summary' => $r['summary'], 'body' => (string) $r['body'], 'section' => $r['section'], 'authors' => $r['authors'],
		'status' => $r['status'], 'effective_status' => ips_articles_effective_status($r['status'], $r['publish_at']), 'access' => $r['access'], 'publish_at' => ips_articles_iso($r['publish_at']),
		'published_at' => ips_articles_iso($r['published_at']), 'issue_id' => $r['issue_id'] === null ? null : (int) $r['issue_id'], 'issue_page' => $r['issue_page'] === null ? null : (int) $r['issue_page'],
		'image' => $r['image'], 'seo_title' => $r['seo_title'], 'seo_description' => $r['seo_description'], 'created_by' => $r['created_by'], 'created_at' => $r['created_at'],
		'updated_by' => $r['updated_by'], 'updated_at' => $r['updated_at']];
}

function ips_articles_get($id) {
	global $db;
	$rows = $db->query("SELECT * FROM articles WHERE id=? LIMIT 1", [['type' => 'i', 'value' => $id]])->fetchArray();
	return $rows ? ips_articles_row($rows[0]) : null;
}

// The keywords of an article, in the order they were given: [term, ...].
function ips_articles_terms_of($id) {
	global $db;
	$out = [];
	$result = $db->query("SELECT term FROM article_terms WHERE article_id=? ORDER BY term_key", [['type' => 'i', 'value' => $id]]);
	while ($r = $result->fetch()) {
		$out[] = $r['term'];
	}
	return $out;
}

// Every article without its body: newest update first.
function ips_articles_all() {
	global $db;
	$out = [];
	$result = $db->query("SELECT id, title, slug, summary, '' AS body, section, authors, status, access, publish_at, published_at, issue_id, issue_page, image, seo_title, seo_description, created_by, created_at, updated_by, updated_at
		FROM articles ORDER BY updated_at DESC, id DESC");
	while ($r = $result->fetch()) {
		$out[] = ips_articles_row($r);
	}
	return $out;
}

// The sections in use, for the form's list of suggestions.
function ips_articles_sections() {
	global $db;
	$out = [];
	$result = $db->query("SELECT DISTINCT section FROM articles WHERE section <> '' ORDER BY section");
	while ($r = $result->fetch()) {
		$out[] = $r['section'];
	}
	return $out;
}

// ---- writing ----------------------------------------------------------------------------------------------------------------------------------

function ips_articles_search_text($title, $summary, $body) {
	return ips_fold($title . ' ' . $summary . ' ' . ips_articles_text($body));
}

function ips_articles_set_terms($id, $terms) {
	global $db;
	$db->query("DELETE FROM article_terms WHERE article_id=?", [['type' => 'i', 'value' => $id]]);
	foreach ($terms as [$term, $key]) {
		$db->query("INSERT INTO article_terms (article_id, term, term_key) VALUES (?, ?, ?)", [['type' => 'i', 'value' => $id], ['type' => 's', 'value' => $term], ['type' => 's', 'value' => $key]]);
	}
}

// The revisions of an article, newest first, without their bodies: [{revision, title, summary, saved_by, saved_at, note, size}].
function ips_articles_revisions($id) {
	global $db;
	$out = [];
	$result = $db->query("SELECT revision, title, summary, saved_by, saved_at, note, CHAR_LENGTH(body) AS size FROM article_revisions WHERE article_id=? ORDER BY revision DESC", [['type' => 'i', 'value' => $id]]);
	while ($r = $result->fetch()) {
		$out[] = ['revision' => (int) $r['revision'], 'title' => $r['title'], 'summary' => $r['summary'], 'saved_by' => $r['saved_by'], 'saved_at' => $r['saved_at'], 'note' => $r['note'], 'size' => (int) $r['size']];
	}
	return $out;
}

function ips_articles_revision($id, $revision) {
	global $db;
	$rows = $db->query("SELECT revision, title, summary, body, saved_by, saved_at, note FROM article_revisions WHERE article_id=? AND revision=? LIMIT 1", [['type' => 'i', 'value' => $id], ['type' => 'i', 'value' => $revision]])->fetchArray();
	return $rows ? ['revision' => (int) $rows[0]['revision'], 'title' => $rows[0]['title'], 'summary' => $rows[0]['summary'], 'body' => (string) $rows[0]['body'], 'saved_by' => $rows[0]['saved_by'],
		'saved_at' => $rows[0]['saved_at'], 'note' => $rows[0]['note']] : null;
}

/* Adds a revision when the title, summary or body differ from the latest one (a save that changes nothing makes none), and keeps the last IPS_ARTICLE_REVISIONS_KEPT. Returns the number of
 * the newest revision. */
function ips_articles_add_revision($id, $v, $user, $note = '') {
	global $db;
	$latest = $db->query("SELECT revision, title, summary, body FROM article_revisions WHERE article_id=? ORDER BY revision DESC LIMIT 1", [['type' => 'i', 'value' => $id]])->fetchArray();
	if ($latest && $latest[0]['title'] === $v['title'] && $latest[0]['summary'] === $v['summary'] && (string) $latest[0]['body'] === $v['body']) {
		return (int) $latest[0]['revision'];
	}
	$next = $latest ? (int) $latest[0]['revision'] + 1 : 1;
	$db->query("INSERT INTO article_revisions (article_id, revision, title, summary, body, saved_by, note) VALUES (?, ?, ?, ?, ?, ?, ?)", [['type' => 'i', 'value' => $id], ['type' => 'i', 'value' => $next],
		['type' => 's', 'value' => $v['title']], ['type' => 's', 'value' => $v['summary']], ['type' => 's', 'value' => $v['body']], ['type' => 's', 'value' => $user], ['type' => 's', 'value' => mb_substr($note, 0, 255)]]);
	$db->query("DELETE FROM article_revisions WHERE article_id=? AND revision <= ?", [['type' => 'i', 'value' => $id], ['type' => 'i', 'value' => $next - IPS_ARTICLE_REVISIONS_KEPT]]);
	return $next;
}

// May this user change the article: an administrator, or anyone while it is a draft.
function ips_articles_may_edit($article, $admin) {
	return $admin || $article['status'] === 'draft';
}

// What the editor's forms offer: the statuses, the access levels, the sections in use, and the issues of the Issues module when it is installed.
function ips_articles_form_options() {
	global $db;
	$options = function ($list) { $out = []; foreach ($list as $id => $label) { $out[] = ['id' => $id, 'label' => $label]; } return $out; };
	$issues = [];
	if (ips_articles_issues_available()) {
		$result = $db->query("SELECT id, label, issue_date, page_count FROM issues ORDER BY issue_date = '', issue_date DESC, id DESC LIMIT 500");
		while ($r = $result->fetch()) {
			$issues[] = ['id' => (int) $r['id'], 'label' => $r['label'], 'issue_date' => $r['issue_date'], 'page_count' => (int) $r['page_count']];
		}
	}
	return ['statuses' => $options(IPS_ARTICLE_STATUSES), 'access' => $options(IPS_ARTICLE_ACCESS), 'sections' => ips_articles_sections(), 'issues' => $issues];
}
