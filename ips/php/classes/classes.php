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

?>
