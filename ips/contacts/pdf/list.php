<?php
require_once ('/var/www/htdocs/classes/classes.php');
require_once ('/var/www/htdocs/config/config.php');

	$querypop="SELECT * FROM customers WHERE catalog='YES'";
	$resultpop=$db->query($querypop) or die('select statement failed!');
	$num_cat=$resultpop->size();
	$catalog_env = $resultpop->fetchArray();
	print_r($catalog_env);
	echo "loser";

?>
