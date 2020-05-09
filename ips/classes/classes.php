<?php
error_reporting(E_ALL | E_STRICT);
function set_incs() {
// Load the MySQL access class.
	include 'mysql.php';
// Load the users table classes.
	include 'users_table.php';
// Load the files table classes.
	include 'files_table.php';
	return;
}

// This file loads all wanted classes from this directory.
// Load the form validation class.
// Below is a list of unused classes.
//include 'validate.php';
// Load the territory classes.
// We have no real need for this at this time.
//include 'territory.php';
// Load the Contact Class.
//include 'contacts.php';
// Load the Directory Class.
//include 'directory_class.php';
set_incs();

/**
 * function for the array_sort class.
 * this function initializes the array_sort.php class upon call.
 */
function sort_array($arr, $sort_string, $sort_function = "strcasecmp"){
  require("array_sort.php");
  $array_sort = new array_sort($arr, $sort_string, $sort_function);
  if(!$array_sort->error["flag"]) return $array_sort->get_sorted_array();
  else return $arr;
}
?>
