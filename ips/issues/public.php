<?php
/* The issues for a site's own public pages: a library, not pages (the base does not decide what a public page looks like). A site includes this file and calls the functions with the
 * path of the IPS's php/classes/config.php; the database settings are read from it (the file also starts the IPS itself, so it is read, not included), as vinlandtovalinor's Links page does.
 * Everything here is for an anonymous visitor: only published issues, a published issue for signed-in readers only is listed as such and its pages are found without a snippet, and
 * nothing is ever returned from a draft or private issue. A site that knows who the visitor is may pass the name as $viewer.
 *
 *   ips_issues_public_search($config, $q, $opts = [], $viewer = null)   the pages that match (issues/search_lib.php: syntax, snippets, sort, offset, limit)
 *   ips_issues_public_list($config, $viewer = null)                      the published issues, newest first: id, series, label, issue_date, volume, number, page_count, access, readable, has_pdf, external_url
 *   ips_issues_public_find($config, $id, $viewer = null)                 one such issue, or null
 * Each returns null when the database cannot be read (the page says so; every other page of the site works without it). The address of an issue's PDF (issues/file.php?id=) is the site's to link.
 */
require_once __DIR__ . '/../php/access.php';
require_once __DIR__ . '/search_lib.php';

// The few parts of the IPS's database object that search_lib.php uses, over mysqli prepared statements.
class IpsIssuesPublicResult {
	private $rows;
	private $at = 0;
	private $id;
	public function __construct($rows, $id) { $this->rows = $rows; $this->id = $id; }
	public function size() { return count($this->rows); }
	public function fetch() { return $this->rows[$this->at++] ?? null; }
	public function fetchArray() { return $this->rows; }
	public function getId() { return $this->id; }
}

class IpsIssuesPublicDb {
	private $conn;
	public function __construct($conn) { $this->conn = $conn; }
	public function query($sql, $params = null) {
		$stmt = $this->conn->prepare($sql);
		if ($stmt === false) {
			return new IpsIssuesPublicResult([], 0);
		}
		if (is_array($params) && $params) {
			$types = '';
			$values = [];
			foreach ($params as $i => $p) {
				$types .= $p['type'];
				$values[$i] = $p['value'];
			}
			$refs = [$types];
			foreach ($values as $i => $v) {
				$refs[] = &$values[$i];
			}
			call_user_func_array([$stmt, 'bind_param'], $refs);
		}
		if (!$stmt->execute()) {
			return new IpsIssuesPublicResult([], 0);
		}
		$result = $stmt->get_result();
		$rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
		return new IpsIssuesPublicResult($rows, $stmt->insert_id);
	}
}

// The database settings of the IPS (php/classes/config.php: define("DB_HOST", "..."), and so on), or null when the file or one of them is missing.
function ips_issues_public_settings($config) {
	$text = @file_get_contents($config);
	$found = [];
	foreach (['DB_HOST', 'DB_USER', 'DB_PASS', 'DB_NAME'] as $key) {
		if ($text === false || !preg_match('/\bdefine\(\s*(["\'])' . $key . '\1\s*,\s*(["\'])((?:\\\\.|(?!\2).)*)\2\s*\)/', $text, $m)) {
			return null;
		}
		$found[$key] = stripcslashes($m[3]);
	}
	return $found;
}

// Connects ($GLOBALS['db'] becomes the adapter search_lib.php uses) and returns true, or false when the database cannot be read or the issues tables are missing.
function ips_issues_public_connect($config) {
	$settings = ips_issues_public_settings($config);
	if ($settings === null || !function_exists('mysqli_init')) {
		return false;
	}
	mysqli_report(MYSQLI_REPORT_OFF);
	$conn = mysqli_init();
	$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 3);
	if (!@$conn->real_connect($settings['DB_HOST'], $settings['DB_USER'], $settings['DB_PASS'], $settings['DB_NAME']) || !$conn->set_charset('utf8mb4')) {
		return false;
	}
	$GLOBALS['db'] = new IpsIssuesPublicDb($conn);
	return $conn->query("SELECT 1 FROM issue_pages LIMIT 1") !== false && $conn->query("SELECT 1 FROM issues LIMIT 1") !== false;
}

function ips_issues_public_search($config, $q, $opts = [], $viewer = null) {
	return ips_issues_public_connect($config) ? ips_issues_search($q, $viewer, $opts) : null;
}

function ips_issues_public_row($r, $viewer) {
	return ['id' => (int) $r['id'], 'series' => $r['series'], 'label' => $r['label'], 'issue_date' => $r['issue_date'], 'volume' => $r['volume'], 'number' => $r['number'],
		'page_count' => (int) $r['page_count'], 'access' => $r['access'], 'readable' => ips_access_can_read($viewer, $r['status'], $r['access']), 'has_pdf' => $r['pdf_sha256'] !== '',
		'external_url' => $r['external_url']];
}

function ips_issues_public_list($config, $viewer = null) {
	if (!ips_issues_public_connect($config)) {
		return null;
	}
	global $db;
	$out = [];
	$result = $db->query("SELECT id, series, label, issue_date, volume, number, page_count, access, status, pdf_sha256, external_url FROM issues WHERE status = 'published'
		ORDER BY issue_date = '', issue_date DESC, id DESC");
	while ($r = $result->fetch()) {
		$out[] = ips_issues_public_row($r, $viewer);
	}
	return $out;
}

function ips_issues_public_find($config, $id, $viewer = null) {
	if (!ips_issues_public_connect($config)) {
		return null;
	}
	global $db;
	$rows = $db->query("SELECT id, series, label, issue_date, volume, number, page_count, access, status, pdf_sha256, external_url FROM issues WHERE id = ? AND status = 'published' LIMIT 1",
		[['type' => 'i', 'value' => (int) $id]])->fetchArray();
	return $rows ? ips_issues_public_row($rows[0], $viewer) : null;
}
