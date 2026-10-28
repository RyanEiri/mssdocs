<?php
/* The XML validator of the file editor: checks a document against the Menota schema (RELAX NG, a customisation of TEI P5; Menota Handbook 3.0) and lists each problem with its
 * line. It never blocks a save: the editor shows it on request (the Validate button) and after each save.
 *
 * The check is done by validate_xml.py (next to this file), run by the interpreter that php/site-config.php names with IPS_FE_PYTHON (a Python with lxml). There is no default, so
 * a host has the Validate button only when someone has set it up on purpose. It is not done in PHP: DOMDocument::relaxNGValidate recompiles the 800 KB schema on every call, and
 * compiling the Menota schema takes minutes with libxml2 up to 2.14 (Debian 12 has 2.9.14, the pip wheels of lxml bundle 2.14.6) and about 4 seconds with 2.15 or newer: set
 * IPS_FE_PYTHON only to an interpreter whose lxml is built on libxml2 2.15+ (validate_xml.py refuses to run on an older one). PHP runs the script as a fixed command with no shell,
 * feeds it the text on stdin, reads one line of JSON, and gives up after IPS_FE_VALIDATE_TIMEOUT seconds; at most IPS_FE_VALIDATE_SLOTS validations run at once, and a further
 * one is told to try again. The same script is a command-line tool (see its docstring and the module README).
 *
 * The schema is not kept in this repository (menota.org states no licence for it): tools/fetch-menota-schema.sh downloads it, and the site's file_editor/schemas/ folder holds
 * menotaP5.rng and menota-entities.txt (IPS_FE_SCHEMA_FILE names another RELAX NG file). Without the schema, the interpreter, or proc_open, ips_fe_schema_available() is false and the
 * editor shows no Validate button.
 *
 * Menota text uses named character entities (&ae; &mdash; ...) that a document declares through a DTD naming menota-entities.txt. Nothing external is loaded: the script replaces
 * the names with numeric references from that list before parsing (ips_fe_substitute_entities() does the same for the save check of types/xml.php), and a name in no list is
 * reported as a problem like any other.
 */

const IPS_FE_VALIDATE_MAX_BYTES = 4194304; // 4 MB
// seconds a validation may take, and how many may run at the same time (php/site-config.php may define either before this file is loaded)
if (!defined('IPS_FE_VALIDATE_TIMEOUT')) {
	define('IPS_FE_VALIDATE_TIMEOUT', 60);
}
if (!defined('IPS_FE_VALIDATE_SLOTS')) {
	define('IPS_FE_VALIDATE_SLOTS', 2);
}
const IPS_FE_XML_ENTITIES = ['amp', 'lt', 'gt', 'quot', 'apos']; // the five every XML parser knows

function ips_fe_schema_file() {
	return defined('IPS_FE_SCHEMA_FILE') ? IPS_FE_SCHEMA_FILE : __DIR__ . '/schemas/menotaP5.rng';
}

// The interpreter that runs validate_xml.py, or null when the site has not named one (IPS_FE_PYTHON in php/site-config.php): then there is no Validate button.
function ips_fe_python() {
	return defined('IPS_FE_PYTHON') ? IPS_FE_PYTHON : null;
}

function ips_fe_schema_available() {
	$python = ips_fe_python();
	return function_exists('proc_open') && is_file(ips_fe_schema_file()) && $python !== null && is_executable($python);
}

// What the schema file says about itself, for the editor: "Menota schema (TEI P5 3.6.0, generated 2019-12-09)". The file starts with a comment naming the ODD run and the TEI edition.
function ips_fe_schema_info() {
	$head = preg_replace('/\s+/', ' ', (string) @file_get_contents(ips_fe_schema_file(), false, null, 0, 2500));
	$generated = preg_match('/Schema generated from ODD source (\d{4}-\d{2}-\d{2})/', $head, $g) ? $g[1] : null;
	$tei = preg_match('/TEI Edition: Version(?:\s|\xC2\xA0)+([0-9]+(?:\.[0-9]+)*)/', $head, $t) ? $t[1] : null; // (the file puts a no-break space after "Version")
	$label = 'Menota schema' . ($tei || $generated ? ' (' . implode(', ', array_filter([$tei ? 'TEI P5 ' . $tei : null, $generated ? 'generated ' . $generated : null])) . ')' : '');
	return ['label' => $label, 'file' => basename(ips_fe_schema_file())];
}

// name => hex code point, from menota-entities.txt next to the schema (empty when the file is not there). Lines look like: <!-- RUNE X --> <!ENTITY name "&#x16CC;">
function ips_fe_entities() {
	static $map = null;
	if ($map === null) {
		$map = [];
		$text = @file_get_contents(dirname(ips_fe_schema_file()) . '/menota-entities.txt');
		if (is_string($text) && preg_match_all('/<!ENTITY\s+([A-Za-z_][A-Za-z0-9._-]*)\s+"&#x([0-9A-Fa-f]+);"\s*>/', $text, $m, PREG_SET_ORDER)) {
			foreach ($m as $entity) {
				$map[$entity[1]] = $entity[2];
			}
		}
	}
	return $map;
}

// $text with each &name; that the Menota list knows, and the document does not declare itself, as the numeric reference it stands for. Pure; validate_xml.py has the same rule.
function ips_fe_substitute_entities($text, $entities) {
	if (!$entities || strpos($text, '&') === false) {
		return $text;
	}
	$declared = preg_match_all('/<!ENTITY\s+([A-Za-z_][A-Za-z0-9._-]*)\s/', $text, $d) ? array_flip($d[1]) : [];
	return preg_replace_callback('/&([A-Za-z_][A-Za-z0-9._-]*);/', function ($m) use ($entities, $declared) {
		$name = $m[1];
		if (in_array($name, IPS_FE_XML_ENTITIES, true) || isset($declared[$name]) || !isset($entities[$name])) {
			return $m[0];
		}
		return '&#x' . $entities[$name] . ';';
	}, $text);
}

// One of IPS_FE_VALIDATE_SLOTS lock files held for the length of a validation, or null when all are in use.
function ips_fe_validate_slot() {
	for ($i = 0; $i < IPS_FE_VALIDATE_SLOTS; $i++) {
		$handle = @fopen(sys_get_temp_dir() . '/ips-fe-validate-' . $i . '.lock', 'c');
		if ($handle && flock($handle, LOCK_EX | LOCK_NB)) {
			return $handle;
		}
		if ($handle) {
			fclose($handle);
		}
	}
	return null;
}

// Runs validate_xml.py on $text: its answer decoded, or a string with what went wrong (it did not start, answered nothing sensible, or took too long).
function ips_fe_run_validator($text) {
	$command = [ips_fe_python(), __DIR__ . '/validate_xml.py', '--schema', ips_fe_schema_file()];
	$process = @proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['file', '/dev/null', 'a']], $pipes, null, ['PATH' => '/usr/bin:/bin', 'LC_ALL' => 'C.UTF-8']);
	if (!is_resource($process)) {
		return 'The validator could not be started.';
	}
	stream_set_blocking($pipes[0], false);
	stream_set_blocking($pipes[1], false);
	$deadline = microtime(true) + IPS_FE_VALIDATE_TIMEOUT;
	$input = $text;
	$output = '';
	$timed_out = false;
	while (true) {
		if ($input === '' && $pipes[0] !== null) {
			fclose($pipes[0]);
			$pipes[0] = null;
		}
		$read = [$pipes[1]];
		$write = $pipes[0] !== null ? [$pipes[0]] : [];
		$except = null;
		$left = $deadline - microtime(true);
		if ($left <= 0) {
			$timed_out = true;
			break;
		}
		if (stream_select($read, $write, $except, 0, (int) min(200000, $left * 1000000)) === false) {
			break;
		}
		if ($write) {
			$written = fwrite($pipes[0], substr($input, 0, 65536));
			$input = $written > 0 ? substr($input, $written) : $input;
		}
		if ($read) {
			$chunk = fread($pipes[1], 65536);
			if ($chunk !== false) {
				$output .= $chunk;
			}
		}
		if (feof($pipes[1])) {
			break;
		}
	}
	if ($pipes[0] !== null) {
		fclose($pipes[0]);
	}
	fclose($pipes[1]);
	if ($timed_out) {
		proc_terminate($process, 9);
		proc_close($process);
		return 'The validator took longer than ' . IPS_FE_VALIDATE_TIMEOUT . ' seconds and was stopped.';
	}
	proc_close($process);
	$answer = json_decode($output, true);
	if (!is_array($answer)) {
		return 'The validator gave no usable answer.';
	}
	return isset($answer['error']) ? (string) $answer['error'] : $answer;
}

// The result of validating $text: ['available' => bool, 'valid' => bool, 'stage' => null|'size'|'busy'|'engine'|'parse'|'schema', 'errors' => [{line, column, message}], 'truncated' => bool,
// 'schema' => {label, file}]. The stages 'size', 'busy' and 'engine' mean it could not be checked (one error line says why); 'parse' means the text is not well-formed XML.
function ips_fe_validate_xml($text) {
	$out = ['available' => ips_fe_schema_available(), 'valid' => false, 'stage' => null, 'errors' => [], 'truncated' => false, 'schema' => null];
	if (!$out['available']) {
		return $out;
	}
	$out['schema'] = ips_fe_schema_info();
	$fail = function ($stage, $message) use ($out) {
		return ['stage' => $stage, 'errors' => [['line' => 0, 'column' => 0, 'message' => $message]]] + $out;
	};
	if (strlen($text) > IPS_FE_VALIDATE_MAX_BYTES) {
		return $fail('size', 'The document is larger than ' . (IPS_FE_VALIDATE_MAX_BYTES >> 20) . ' MB, too large to validate here.');
	}
	$slot = ips_fe_validate_slot();
	if ($slot === null) {
		return $fail('busy', 'The validator is busy with other documents; try again in a moment.');
	}
	$answer = ips_fe_run_validator($text);
	flock($slot, LOCK_UN);
	fclose($slot);
	if (is_string($answer)) {
		return $fail('engine', $answer);
	}
	return ['valid' => !empty($answer['valid']), 'stage' => $answer['stage'] ?? null, 'errors' => is_array($answer['errors'] ?? null) ? $answer['errors'] : [], 'truncated' => !empty($answer['truncated'])] + $out;
}
