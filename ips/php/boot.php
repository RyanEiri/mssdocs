<?php
include 'php.php';
define("FULL_URL", get_full_url());
// Set error handling
error_reporting(E_ALL);

// Per-implementation constants
// following line is no longer used per get_full_url() change !*CHANGE01*!
//define("PROGRAM_WEB_BASE_PROTOCOL", "HTTPS");
// define installation specific directories
// these are the only install specific items that should change in this file
define("INSTALL_DIR", 'ips');
define("PUBLIC_DIRS", array(
	'gallery'
));

// test for subdirectories and remove them to set BASE_URL
if (preg_match('/\/'.INSTALL_DIR.'.*/', FULL_URL)){
	define("BASE_URL", preg_replace('/\/'.INSTALL_DIR.'.*/', '', FULL_URL));
} else {
	foreach (PUBLIC_DIRS as $value){
		if (preg_match('/\/'.$value.'.*/', FULL_URL)){
			define("BASE_URL", preg_replace('/\/'.$value.'.*/', '', FULL_URL));
		}
	}
	if (!defined('BASE_URL')){
		define("BASE_URL", FULL_URL);
	}
}

// Program Directories
define("HTML_TEMPLATES_BASE", 'html');
define("CSS_BASE", 'css');
define("JS_BASE", 'js');
define("JSON_BASE", 'json');

// Define hard program configuration constants.
// altered to use getcwd() function
define("CURRENT_WORKING_DIR", getcwd().'/');
define("ROOT_DIR", preg_replace('/\/' . INSTALL_DIR . '.*/', '', CURRENT_WORKING_DIR));
//define("ROOT_DIR", $_SERVER['DOCUMENT_ROOT']);
define("PROGRAM_BASE", ROOT_DIR.'/'.INSTALL_DIR.'/');
define("FUNCTIONS_BASE_DIR", 'php');
define("FUNCTIONS_DIR", PROGRAM_BASE.FUNCTIONS_BASE_DIR);
define("FUNCTIONS_FILE", 'php.php');
define("PROGRAM_PHP_FUNCTIONS", FUNCTIONS_DIR.'/'.FUNCTIONS_FILE);
define("CLASSES_BASE_DIR", 'classes');
define("CLASSES_DIR", FUNCTIONS_DIR.'/'.CLASSES_BASE_DIR.'/');
define("CLASSES_CONFIG_FILE", 'config.php');
define("PROGRAM_CLASSES_CONFIG", CLASSES_DIR.CLASSES_CONFIG_FILE);
// new method for URL control using get_full_url() function borrowed from
// blueimp https://github.com/blueimp !*CHANGE01*!
// this allows for non-reliance of php $_SERVER['DOCUMENT_ROOT'] super global
// this super global is defined by Apache2 and does not get rewritten for
// mod_userdir
//define("PROGRAM_WEB_DIR", '/'.INSTALL_DIR.'/');
//define("PROGRAM_WEB_SERVER", $_SERVER['SERVER_NAME']);
//define("PROGRAM_WEB_PROTOCOL", PROGRAM_WEB_BASE_PROTOCOL.'://');
//define("PROGRAM_WEB", PROGRAM_WEB_PROTOCOL.PROGRAM_WEB_SERVER.'/');
//define("PROGRAM_WEB_BASE", PROGRAM_WEB_PROTOCOL.PROGRAM_WEB_SERVER.PROGRAM_WEB_DIR);
define("PROGRAM_WEB_BASE", BASE_URL.'/'.INSTALL_DIR.'/');
define("USER_FILES_URL", PROGRAM_WEB_BASE.'upload/');
define("USER_FILES_BASE", PROGRAM_BASE.'upload/');

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
