<?php
// set the mysql variables
define("DB_HOST", "mysql.la.cuna.ca");
define("DB_USER", "fragileheritage");
define("DB_PASS", "Z\$Y5ZJnCy6bP\$S0T");
define("DB_NAME", "vesturheimsrit_ips");

// set the main database access
// currently the upload scripts use their own mysqli access
// but the mysql constants set here are used
$db = new SQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);
?>
