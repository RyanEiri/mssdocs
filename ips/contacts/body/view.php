<?php
require_once ('../../classes/classes.php');
require_once ('../../config/config.php');
$usurper = new UserCookie();
$usurper->DeleteIt();
$usurper->CheckIt();
session_start();
require_once ('../../functions/functions.php');
if (!$contact_object) {
	$contact_object = new ContactGrab($db, 'customers_organization');
	$serialized_contact_object = serialize($contact_object);
	$fp = fopen("/var/www/htdocs/contacts/serialized_objects/contact_object", "w");
	fwrite($fp, $serialized_contact_object);
	fclose($fp);
	$_SESSION[contact_array] = $contact_object->sorted_contacts;
} else {
	$serialized_contact_object = implode("", @file("/var/www/htdocs/contacts/serialized_objects/contact_object"));
	$contact_object = unserialize($serialized_contact_object);
}
if($_GET[view]) {
	$contact_object->ViewContact();
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
   "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>View Contact - <?php if($contact_object->view) { if($contact_object->view[customers_name] != NULL) { echo $contact_object->view[customers_name]; } else { echo $contact_object->view[customers_organization]; }} ?></title>
<style type="text/css">
	body{
		margin:10px;
		font-size:0.9em;
	}
	a{
		color:#F00;
	}
	</style>
<link rel="stylesheet" href="http://contacts/css/tab-view.css" type="text/css" media="screen">
<script type="text/javascript" src="http://contacts/functions/js/ajax.js"></script>
<script type="text/javascript" src="http://contacts/functions/js/tab-view.js"></script>
<script type="text/javascript" src="http://contacts/functions/js/ajax_hacks/http_request.js"></script>
<script type="text/javascript" src="http://contacts/functions/js/ajax_hacks/phone.js"></script>
</head>

<body>
<?php
if($_GET[view]) {
?>
	<form name="change" action="javascript:void%200" onsubmit="sendPostData('http://contacts/body/change.php');return false">
<?php
	require_once ('form.php');
?>
</form>
<script type="text/javascript">
initTabs(Array('Summary','Name','Address','Telephone','Categories','Email','Notes'),0,500,400);
</script>
<?php
} elseif($_GET[form] == 'add') {
?>
	<form name="add" action="javascript:void%200" onsubmit="sendPostData('http://contacts/body/add.php');return false"> 
<?php
        require_once ('form.php');
?>
</form>
<script type="text/javascript">
initTabs(Array('Name','Address','Telephone','Categories','Email','Notes'),0,500,400);
</script>
<?php
} else {
        echo "what are you doing?";
}
?>
</body>
</html>
