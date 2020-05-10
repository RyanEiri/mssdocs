<?php
// Set error handling
error_reporting(E_ALL | E_STRICT);

// Per-implementation constants
define("PROGRAM_WEB_BASE_PROTOCOL", "HTTPS");
define("INSTALL_DIR", 'ips');

// Program Directories
define("HTML_TEMPLATES_BASE", 'html');
define("CSS_BASE", 'css');
define("JS_BASE", 'js');
define("JSON_BASE", 'json');

// Define hard program configuration constants.
define("CURRENT_WORKING_DIR", getcwd().'/');
define("ROOT_DIR", $_SERVER['DOCUMENT_ROOT']);
define("PROGRAM_BASE", ROOT_DIR.'/'.INSTALL_DIR.'/');
define("FUNCTIONS_BASE_DIR", 'php');
define("FUNCTIONS_DIR", PROGRAM_BASE.FUNCTIONS_BASE_DIR);
define("FUNCTIONS_FILE", 'php.php');
define("PROGRAM_PHP_FUNCTIONS", FUNCTIONS_DIR.'/'.FUNCTIONS_FILE);
define("CLASSES_BASE_DIR", 'classes');
define("CLASSES_DIR", FUNCTIONS_DIR.'/'.CLASSES_BASE_DIR.'/');
define("CLASSES_CONFIG_FILE", 'config.php');
define("PROGRAM_CLASSES_CONFIG", CLASSES_DIR.CLASSES_CONFIG_FILE);
define("PROGRAM_WEB_DIR", '/'.INSTALL_DIR.'/');
define("PROGRAM_WEB_SERVER", $_SERVER['SERVER_NAME']);
define("PROGRAM_WEB_PROTOCOL", PROGRAM_WEB_BASE_PROTOCOL.'://');
define("PROGRAM_WEB", PROGRAM_WEB_PROTOCOL.PROGRAM_WEB_SERVER.'/');
define("PROGRAM_WEB_BASE", PROGRAM_WEB_PROTOCOL.PROGRAM_WEB_SERVER.PROGRAM_WEB_DIR);

// Instantiate PHP functions.
require_once(PROGRAM_PHP_FUNCTIONS);
// Instantiate MySQL configuration.
require_once(PROGRAM_CLASSES_CONFIG);

// Define HTML constants for templates.
define("HTML_BREAK", '<br />');
define("HTML_TEMPLATES", PROGRAM_BASE.HTML_TEMPLATES_BASE.'/');
define("PROGRAM_CSS_BASE", PROGRAM_WEB_BASE.CSS_BASE.'/');
define("PROGRAM_JS_BASE", PROGRAM_WEB_BASE.JS_BASE.'/');
define("PROGRAM_JSON_BASE", PROGRAM_WEB_BASE.JSON_BASE.'/');
?>
