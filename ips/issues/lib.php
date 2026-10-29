<?php
/* The Issues module (ips/issues/): a publication's issues as PDFs, with their text extracted page by page for search. Design and reasons: docs/mssdocs-issues-design.md (webserversAC repository).
 * Everything the endpoints share is here: who may do what, the checks on what a form sends, the PDF store, and the queries.
 *
 * Who may do what (decided by Ryan, 2026-10-30): any signed-in user adds an issue and edits a draft; only an administrator publishes (sets status published or private), edits a published issue
 * and sets its access. Every write is a same-origin POST with the user's CSRF token, bound parameters only. Reading a PDF follows ips_access_can_read() (php/access.php).
 */
require_once __DIR__ . '/../php/access.php';
require_once __DIR__ . '/../php/iso_date.php';
require_once __DIR__ . '/../php/text_fold.php';
require_once __DIR__ . '/extract.php';

const IPS_ISSUE_STATUSES = ['draft' => 'Draft', 'published' => 'Published', 'private' => 'Private'];
const IPS_ISSUE_ACCESS = ['open' => 'Open to everyone', 'signed-in' => 'Signed-in readers only'];
if (!defined('IPS_ISSUES_MAX_BYTES')) {
	define('IPS_ISSUES_MAX_BYTES', 209715200); // 200 MB for one PDF
}

function ips_issues_refuse($status, $message) {
	http_response_code($status);
	echo json_encode(['success' => false, 'error' => $message]);
	exit;
}

function ips_issues_ready() {
	global $db;
	foreach (['issues', 'issue_pages', 'issue_toc'] as $table) {
		if ($db->query("SHOW TABLES LIKE '$table'")->size() === 0) {
			return false;
		}
	}
	return true;
}

// The signed-in user for a JSON endpoint (403 otherwise); for a write also a valid same-origin POST with the user's token (RequireCsrf answers 403/405 itself). Returns [username, is_admin].
function ips_issues_user($write) {
	global $login_cookie;
	header('Content-Type: application/json');
	header('Cache-Control: no-store');
	$login_cookie = new UserCookie();
	if ($login_cookie->Peek() === false) {
		ips_issues_refuse(403, 'Sign in to use issues.');
	}
	if ($write) {
		$login_cookie->RequireCsrf();
	}
	if (!ips_issues_ready()) {
		ips_issues_refuse(503, 'The issues tables have not been created yet (run issues/schema.sql).');
	}
	return ips_current_user();
}

// The signed-in user's name for a page that must not redirect or refuse a visitor (a PDF that is open to everyone), or null for an anonymous visitor.
function ips_issues_viewer() {
	global $login_cookie;
	$login_cookie = new UserCookie();
	$name = $login_cookie->Peek();
	return $name === false ? null : ($login_cookie->username ?? null);
}

// ---- what a form sends ------------------------------------------------------------------------------------------------------------------------

function ips_issues_length($value) {
	$n = preg_match_all('/./us', $value);
	return $n === false ? null : $n;
}

// One line of plain text, whitespace collapsed, at most $max characters, or null.
function ips_issues_text($value, $max) {
	if (!is_string($value)) {
		return null;
	}
	$value = preg_replace('/\s+/u', ' ', str_replace("\xc2\xa0", ' ', $value));
	if ($value === null) {
		return null;
	}
	$value = trim($value);
	$length = ips_issues_length($value);
	return $length !== null && $length <= $max ? $value : null;
}

function ips_issues_long_text($value, $max) {
	if (!is_string($value)) {
		return null;
	}
	$value = trim(preg_replace("/[ \t]+\n/", "\n", str_replace(["\r\n", "\r"], "\n", $value)));
	$length = ips_issues_length($value);
	return $length !== null && $length <= $max ? $value : null;
}

/* The fields of an issue from a form: [values, null] or [null, message]. $existing is the issue being changed (null for a new one). Does not touch status or access: the endpoint decides
 * who may set those. An external address must be an http or https address. */
function ips_issues_fields($post, $existing) {
	$v = [];
	foreach (['series' => 80, 'label' => 255, 'volume' => 40, 'number' => 40] as $field => $max) {
		$value = ips_issues_text($post[$field] ?? ($existing[$field] ?? ''), $max);
		if ($value === null) {
			return [null, "The $field is at most $max characters."];
		}
		$v[$field] = $value;
	}
	$date = $post['issue_date'] ?? ($existing['issue_date'] ?? '');
	if (!is_string($date) || (($date = trim($date)) !== '' && ips_iso_date_bounds($date) === null)) {
		return [null, 'The date must be ISO 8601: YYYY, YYYY-MM or YYYY-MM-DD (or empty).'];
	}
	$v['issue_date'] = $date;
	$url = ips_issues_text($post['external_url'] ?? ($existing['external_url'] ?? ''), 1000);
	if ($url === null || ($url !== '' && !preg_match('#^https?://[^\s<>"\']+$#i', $url))) {
		return [null, 'The external address must be a web address starting with http:// or https://.'];
	}
	$v['external_url'] = $url;
	$notes = ips_issues_long_text($post['notes'] ?? ($existing['notes'] ?? ''), 5000);
	if ($notes === null) {
		return [null, 'The notes are at most 5000 characters.'];
	}
	$v['notes'] = $notes;
	return [$v, null];
}

// ---- the PDF store ----------------------------------------------------------------------------------------------------------------------------

// The folder of the issues' PDFs, inside upload/archives/ (signed-in only at the web server; reading goes through issues/file.php).
function ips_issues_dir() {
	return rtrim(ips_upload_base(), '/') . '/archives/issues';
}

function ips_issues_pdf_path($sha) {
	return ips_issues_dir() . '/' . substr($sha, 0, 2) . '/' . $sha . '.pdf';
}

// ['real' => the real path] of a PDF in the file store for a "files/A/b.pdf" path, or a string with the problem: it must exist inside upload/files/, no segment hidden or a thumbnail folder, and be a PDF.
function ips_issues_resolve_source($path) {
	if (!is_string($path) || strncmp($path, 'files/', 6) !== 0 || strpbrk($path, "\\\0") !== false) {
		return 'That is not a file in the file store.';
	}
	foreach (explode('/', substr($path, 6)) as $segment) {
		if ($segment === '' || $segment[0] === '.' || preg_match(IPS_VERSION_DIR, $segment)) {
			return 'That is not a file in the file store.';
		}
	}
	$root = ips_files_root();
	$real = $root === false ? false : realpath($root . '/' . substr($path, 6));
	if ($real === false || !is_file($real) || strpos($real, $root . '/') !== 0) {
		return 'That file is not in the file store.';
	}
	if (strtolower(pathinfo($real, PATHINFO_EXTENSION)) !== 'pdf') {
		return 'That file is not a PDF.';
	}
	return ['real' => $real];
}

/* Copies a PDF into the store under its SHA-256 and returns ['sha' => ..., 'size' => ...], or a string with the problem. The same file twice is stored once; the copy is written beside its
 * place and renamed into it, so a half-written file is never there. A file that does not start with %PDF- is refused. */
function ips_issues_store_pdf($real) {
	$size = filesize($real);
	if ($size === false || $size < 8) {
		return 'The PDF is empty.';
	}
	if ($size > IPS_ISSUES_MAX_BYTES) {
		return 'The PDF is larger than ' . (IPS_ISSUES_MAX_BYTES >> 20) . ' MB.';
	}
	$head = file_get_contents($real, false, null, 0, 8);
	if (strncmp((string) $head, '%PDF-', 5) !== 0) {
		return 'That file is not a PDF (it does not start with %PDF-).';
	}
	$sha = hash_file('sha256', $real);
	$target = ips_issues_pdf_path($sha);
	if (is_file($target)) {
		return ['sha' => $sha, 'size' => $size];
	}
	$dir = dirname($target);
	if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
		return 'The issues folder cannot be made on the server.';
	}
	$temporary = $dir . '/.' . $sha . '.' . bin2hex(random_bytes(4)) . '.part';
	if (!@copy($real, $temporary) || !@rename($temporary, $target)) {
		@unlink($temporary);
		return 'The PDF could not be copied into the issues folder (is there room?).';
	}
	@chmod($target, 0664);
	return ['sha' => $sha, 'size' => $size];
}

// Starts the background worker for issue $id and returns at once; the state is read from the issue.
function ips_issues_start_worker($id) {
	$worker = dirname(__DIR__) . '/php/issue_worker.php';
	$php_cli = 'php';
	foreach (['/usr/bin/php8.2', '/usr/bin/php', '/usr/local/bin/php'] as $candidate) {
		if (is_executable($candidate)) {
			$php_cli = $candidate;
			break;
		}
	}
	exec(escapeshellarg($php_cli) . ' ' . escapeshellarg($worker) . ' ' . (int) $id . ' > /dev/null 2>&1 &');
}

// ---- the issues -------------------------------------------------------------------------------------------------------------------------------

function ips_issues_row($r) {
	return ['id' => (int) $r['id'], 'series' => $r['series'], 'label' => $r['label'], 'issue_date' => $r['issue_date'], 'volume' => $r['volume'], 'number' => $r['number'],
		'page_count' => (int) $r['page_count'], 'has_pdf' => $r['pdf_sha256'] !== '', 'pdf_size' => (int) $r['pdf_size'], 'original_name' => $r['original_name'],
		'external_url' => $r['external_url'], 'status' => $r['status'], 'access' => $r['access'], 'extraction' => $r['extraction'], 'extraction_note' => $r['extraction_note'],
		'extraction_at' => $r['extraction_at'], 'notes' => (string) $r['notes'], 'created_by' => $r['created_by'], 'created_at' => $r['created_at'], 'updated_by' => $r['updated_by'],
		'updated_at' => $r['updated_at']];
}

function ips_issues_get($id) {
	global $db;
	$rows = $db->query("SELECT * FROM issues WHERE id=? LIMIT 1", [['type' => 'i', 'value' => $id]])->fetchArray();
	return $rows ? ips_issues_row($rows[0]) : null;
}

// The sha of an issue's PDF (not part of ips_issues_row: nothing outside the server needs it), or ''.
function ips_issues_sha($id) {
	global $db;
	$rows = $db->query("SELECT pdf_sha256 FROM issues WHERE id=? LIMIT 1", [['type' => 'i', 'value' => $id]])->fetchArray();
	return $rows ? $rows[0]['pdf_sha256'] : '';
}

// Every issue, newest first (by date, then id); an issue with no date is last.
function ips_issues_all() {
	global $db;
	$out = [];
	$result = $db->query("SELECT * FROM issues ORDER BY issue_date = '', issue_date DESC, id DESC");
	while ($r = $result->fetch()) {
		$out[] = ips_issues_row($r);
	}
	return $out;
}

// How many pages of the issue have text, have a correction, and are in all.
function ips_issues_page_counts($id) {
	global $db;
	$rows = $db->query("SELECT COUNT(*) AS pages, SUM(CASE WHEN CHAR_LENGTH(search_text) > 0 THEN 1 ELSE 0 END) AS with_text, SUM(CASE WHEN text_corrected IS NOT NULL THEN 1 ELSE 0 END) AS corrected
		FROM issue_pages WHERE issue_id=?", [['type' => 'i', 'value' => $id]])->fetchArray();
	return ['pages' => (int) $rows[0]['pages'], 'with_text' => (int) $rows[0]['with_text'], 'corrected' => (int) $rows[0]['corrected']];
}
