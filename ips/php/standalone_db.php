<?php
/* A site's own public pages are not part of the IPS and cannot start it (config.php also starts a session and the login machinery), so the public libraries (issues/public.php,
 * articles/public.php) read the IPS's database settings out of php/classes/config.php as text and talk to the database over mysqli. This is the adapter: it has the few methods of the IPS's own
 * database object that the shared code uses (query($sql, $typed_params) with fetch(), fetchArray(), size(), getId()), so one implementation serves both. A failed query returns an empty result. */

class IpsStandaloneResult {
	private $rows;
	private $at = 0;
	private $id;
	public function __construct($rows, $id) { $this->rows = $rows; $this->id = $id; }
	public function size() { return count($this->rows); }
	public function fetch() { return $this->rows[$this->at++] ?? null; }
	public function fetchArray() { return $this->rows; }
	public function getId() { return $this->id; }
}

class IpsStandaloneDb {
	private $conn;
	public function __construct($conn) { $this->conn = $conn; }
	public function query($sql, $params = null) {
		$stmt = $this->conn->prepare($sql);
		if ($stmt === false) {
			return new IpsStandaloneResult([], 0);
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
			return new IpsStandaloneResult([], 0);
		}
		$result = $stmt->get_result();
		$rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
		return new IpsStandaloneResult($rows, $stmt->insert_id);
	}
}

// The database settings of the IPS (php/classes/config.php: define("DB_HOST", "..."), and so on), or null when the file or one of them is missing.
function ips_standalone_settings($config) {
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

/* Connects (utf8mb4, session time zone UTC, so a timestamp column reads as UTC) and makes the adapter $GLOBALS['db']. True when it connected and every table in $tables can be read. */
function ips_standalone_connect($config, $tables) {
	$settings = ips_standalone_settings($config);
	if ($settings === null || !function_exists('mysqli_init')) {
		return false;
	}
	mysqli_report(MYSQLI_REPORT_OFF);
	$conn = mysqli_init();
	$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 3);
	if (!@$conn->real_connect($settings['DB_HOST'], $settings['DB_USER'], $settings['DB_PASS'], $settings['DB_NAME']) || !$conn->set_charset('utf8mb4') || !$conn->query("SET time_zone = '+00:00'")) {
		return false;
	}
	$GLOBALS['db'] = new IpsStandaloneDb($conn);
	foreach ($tables as $table) {
		if ($conn->query("SELECT 1 FROM `$table` LIMIT 1") === false) {
			return false;
		}
	}
	return true;
}
