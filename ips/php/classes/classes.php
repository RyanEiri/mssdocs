<?php

function set_incs() {
// Load the MySQL access class.
	include 'mysql_class.php';
// Load the table classes.
	include 'tables/users_table.php';
	include 'tables/files_table.php';

	return;
}

set_incs();

/*
 * function for the array_sort class.
 * this function initializes the array_sort.php class upon call.
 * This is an old class that was used with the Dalco contact database.
 * Might be useful in the future. Hoping to incorporate some of the classes
 * from the Dalco codebase. Additional classes are still available at
 * https://vesturheimsrit/ips/classes/ 

function sort_array($arr, $sort_string, $sort_function = "strcasecmp"){
  require("array_sort.php");
  $array_sort = new array_sort($arr, $sort_string, $sort_function);
  if(!$array_sort->error["flag"]) return $array_sort->get_sorted_array();
  else return $arr;
}
*/
?>
