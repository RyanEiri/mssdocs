<?php
if ($_COOKIE['login']){
        list($c_username,$cookie_hash) = split(',',$_COOKIE['login']);
                if (md5($c_username.$secret_word) == $cookie_hash)
                { } 
                else { header('Location: http://dalcoserve/index.php'); }
                }else { header('Location: http://dalcoserve/index.php');  }
?>
<html>
<body
<link rel="stylesheet" type="text/css" href="style.css"/>
<?php
require_once '/var/www/htdocs/classes/classes.php';
require_once '/var/www/htdocs/config/config.php';
$n = 0;
$fh = fopen('card.parsed', 'r') or die("can't open $php_errormsg");
while (! feof($fh)) {
	$s = rtrim(fgets($fh,1024));
	if(preg_match("/(<?>)(.*)(<?>)/", $s)) {
		$title = strip_tags($s);
		printf("%s<br>", $title);
	}
	if(preg_match("/(.*)(ph|PH)(:.?)([0-9]{3}-[0-9]{4})/", $s)) {
		$phone = preg_replace("/(.*)(ph|PH)(:.?)([0-9]{3}-[0-9]{4})/", '$4', $s);
		printf("Phone: %s<br>", $phone);
	}
	if(preg_match("/(.*)(ph|PH)(:.?)([0-9]{3} [0-9]{3}-[0-9]{4})/", $s)) {
		$long_distance = preg_replace("/(.*)(ph|PH)(:.?)([0-9]{3} [0-9]{3}-[0-9]{4})/", '$4', $s);
		printf("Phone: %s<br>", $long_distance);
	}
//	if(preg_match("/([aA-zZ]+) ([aA-zZ]+)/", $s)) {
//		$name = preg_replace("/([aA-zZ]+) ([aA-zZ]+)(.?)(.?)( [aA-zZ]+|[0-9]{3}-[0-9]{4})?/", '$1 $2', $s);
//		printf("%s<br>", $name);
//	}
		
}
?>
</body>
</html>
