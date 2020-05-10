<?php
if ($_COOKIE['login']){
        list($c_username,$cookie_hash) = split(',',$_COOKIE['login']);
                if (md5($c_username.$secret_word) == $cookie_hash)
                { }
                else { header('Location: ./index.php'); }
                }else { header('Location: ./login.php');  }
session_start();

if($_GET[delete]) {
	$delete = explode(",", $_GET[delete]);
	if($_GET[confirm] == 0) {
		$query = "SELECT * FROM customers WHERE customers_id='$_GET[delete]' LIMIT 1";
                $result = $db->query($query) or die ('Select statement failed: Entry not available.');
		$entry = $result->fetch();
                echo "<table class=bodytable><tr><td><center>Are you certain you wish to delete this entry?</center></td></tr></table>";
		echo "<table class=bodytable>";
		?>
		<tr class=bodytitle><td class=bodytitleitem><?php echo $_SESSION[bc_open] ?>Name<?php echo $_SESSION[bc_close] ?></a></td><td class=bodytitleitem><?php echo $_SESSION[bc_open] ?><a href='<?php echo $_SERVER['PHP_SELF'] ?>?sort=organization'>Organization</a><?php echo $_SESSION[bc_close] ?></td><td class=bodytitleitem><?php echo $_SESSION[bc_open] ?><a href='<?php echo $_SERVER['PHP_SELF'] ?>?sort=city'>City</a><?php echo $_SESSION[bc_close] ?></td><td class=bodytitleitem><?php echo $_SESSION[bc_open] ?><a href='<?php echo $_SERVER['PHP_SELF'] ?>?sort=calendar_n&search=yes&show=search'>Cal.N.</a><?php echo $_SESSION[bc_close] ?></td><td class=bodytitleitem><?php echo $_SESSION[bc_open] ?><a href='<?php echo $_SERVER['PHP_SELF'] ?>?sort=calendar_a&search=yes&show=search'>Cal.A.</a><?php echo $_SESSION[bc_close] ?></td><td class=bodytitleitem><?php echo $_SESSION[bc_open] ?><a href='<?php echo $_SERVER['PHP_SELF'] ?>?sort=catalog&search=yes&show=search'>Cat.</a><?php echo $_SESSION[bc_close] ?></td></tr>
		<?php
		echo "<tr>";
		echo "<td class=bodyitem><a href='$_SERVER[PHP_SELF]?body=view&view=$entry[customers_id]'>$entry[customers_name]</a></td>";
		echo "<td class=bodyitem><a href='$_SERVER[PHP_SELF]?body=view&view=$entry[customers_id]'>$entry[customers_organization]</td>";
		echo "<td class=bodyitem>$entry[customers_city]</td>";
		if ($entry[calendar_n]=="YES") { echo "<td class=bodyitem>$entry[calendar_n]</td>"; } else { echo "<td class=bodyitem>&nbsp;</td>"; }
		if ($entry[calendar_a]=="YES") { echo "<td class=bodyitem>$entry[calendar_a]</td>"; } else { echo "<td class=bodyitem>&nbsp;</td>"; }
		if ($entry[catalog]=="YES") { echo "<td class=bodyitem>$entry[catalog]</td>"; } else { echo "<td class=bodyitem>&nbsp;</td>"; }
		echo "<tr>";
		echo "</table>";
                printf ("<table class=bodytable><tr><td><center><a href='%s?body=delete&delete=%s&confirm=1>YES</a> or <a href=%s>NO</a></center></td></tr></table>", $_SERVER['PHP_SELF'], $_GET[delete], $_SERVER['PHP_SELF']);
	} elseif($_GET[confirm] == 1) {
		foreach($delete as $value) {
			$query = "DELETE FROM customers WHERE customers_id='$value'";
			$ship_query = "DELETE FROM shipping WHERE contact_id='$value'";
			$db->query($ship_query);
			$db->query($query);
			echo "<h1>Entry " . $value . ": Updated. Item " . $value . " DELETED!<br></h1>";
		}
		echo "<h2>All requests completed! Please close this window.</h2>";
        }
} else {
	echo "Nothing to delete!";
}
?>
