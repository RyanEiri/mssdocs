<?php
if ($_COOKIE['login']){
        list($c_username,$cookie_hash) = split(',',$_COOKIE['login']);
                if (md5($c_username.$secret_word) == $cookie_hash)
                { }
                else { header('Location: ./index.php'); }
                }else { header('Location: ./login.php');  }
session_start();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
   "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>Dalco Contact Database</title>
<!--<link rel="stylesheet" type="text/css" href="css/style.css">-->
<?php
$_SESSION[paginate] = 1; 
require_once ('../classes/classes.php');
require_once ('../config/config.php');
require_once ('../functions/functions.php');
require_once ('body/menu.php');
?>
</head>

<body>

<center>
<div id=header>
<img src="../images/web_banner.jpg" alt="CleanFlow/Dalco">
</div>



</body>
</html>
