<?php
if ($_COOKIE['login']){
        list($c_username,$cookie_hash) = split(',',$_COOKIE['login']);
                if (md5($c_username.$secret_word) == $cookie_hash)
                { }
                else { header('Location: ./index.php'); }
                }else { header('Location: ./login.php');  }
session_start();
	$query = "SELECT * FROM customers";
	$result = $db->query($query) or die('Select statement failed: Entries not shown.');
	$_SESSION[contact_array] = $result->fetchArray();	

	if ($_GET[show]) {
		switch ($_GET[show]) {
		case 'a':
			$_SESSION[show]="/^[Aa]/";
			break;
		case 'b':
			$_SESSION[show]="/^[Bb]/";
			break;
		case 'c':
			$_SESSION[show]="/^[Cc]/";
			break;
		case 'd':
			$_SESSION[show]="/^[Dd]/";
			break;
		case 'e':
			$_SESSION[show]="/^[Ee]/";
			break;
		case 'f':
			$_SESSION[show]="/^[Ff]/";
			break;
		case 'g':
			$_SESSION[show]="/^[Gg]/";
			break;
		case 'h':
			$_SESSION[show]="/^[Hh]/";
			break;
		case 'i':
			$_SESSION[show]="/^[Ii]/";
			break;
		case 'j':
			$_SESSION[show]="/^[Jj]/";
			break;
		case 'k':
			$_SESSION[show]="/^[Kk]/";
			break;
		case 'l':
			$_SESSION[show]="/^[Ll]/";
			break;
		case 'm':
			$_SESSION[show]="/^[Mm]/";
			break;
		case 'n':
			$_SESSION[show]="/^[Nn]/";
			break;
		case 'o':
			$_SESSION[show]="/^[Oo]/";
			break;
		case 'p':
			$_SESSION[show]="/^[Pp]/";
			break;
		case 'q':
			$_SESSION[show]="/^[Qq]/";
			break;
		case 'r':
			$_SESSION[show]="/^[Rr]/";
			break;
		case 's':
			$_SESSION[show]="/^[Ss]/";
			break;
		case 't':
			$_SESSION[show]="/^[Tt]/";
			break;
		case 'u':
			$_SESSION[show]="/^[Uu]/";
			break;
		case 'v':
			$_SESSION[show]="/^[Vv]/";
			break;
		case 'w':
			$_SESSION[show]="/^[Ww]/";
			break;
		case 'x':
			$_SESSION[show]="/^[Xx]/";
			break;
		case 'y':
			$_SESSION[show]="/^[Yy]/";
			break;
		case 'z':
			$_SESSION[show]="/^[Zz]/";
			break;
		case 'all':
			$_SESSION[show]="//";
			break;
		case 'search':
			$_SESSION[show]="/$_GET[search]/i";
			break;
		}
	}
		
	if ($_GET[sort]) {
		switch ($_GET[sort]) {	
		case 'name':
			$_SESSION[sort]="name";
			break;
		case 'position':
			$_SESSION[sort]="position";
			break;
		case 'organization':
			$_SESSION[sort]="organization";
			break;
		case 'address_1':
			$_SESSION[sort]="address_1";
			break;
		case 'address_2':
			$_SESSION[sort]="address_2";
			break;
		case 'city':
			$_SESSION[sort]="city";
			break;
		case 'province':
			$_SESSION[sort]="province";
			break;
		case 'postal_code':
			$_SESSION[sort]="postal_code";
			break;
		case 'calendar_n':
			$_SESSION[sort]="calendar_n";
			break;
		case 'calendar_a':
			$_SESSION[sort]="calendar_a";
			break;
		case 'catalog':
			$_SESSION[sort]="catalog";
			break;
		}
	}
	
	if ($_SESSION[show]) {
		$contact_array_temp = alpha_filter();
		$_SESSION[contact_array] = $contact_array_temp[0];
			
	}

	if ($_GET[page]) {
		$_SESSION[page] = ($_GET[page] - 1);
	}
	$criteria = ltrim($_SESSION[show], "\/");
	$criteria = rtrim($criteria, "\/i");
	sort_order();	
	if ($_SESSION[paginate]=='1') {
	        printf("<div class=bodytable><font size=2><center><a href='%s?page=%s'>previous</a>&nbsp;&nbsp;", $_SERVER[PHP_SELF], $_SESSION[previous_page]);
		if ($_SESSION[number_of_pages]) {
			$pages_size = sizeof($_SESSION[number_of_pages]);
		        foreach($_SESSION[number_of_pages] as $value) {
       		        	printf("<a href='%s?page=%s'>%s</a>", $_SERVER[PHP_SELF], $value, $value);
       		        	if($value != $pages_size) {
       		               		echo " - ";
       	       	  		}
       		 	}
		}
        	printf("&nbsp;&nbsp;<a href='%s?page=%s'>next</a></center></font></div><br />", $_SERVER[PHP_SELF], $_SESSION[next_page]);
	}
?>
	<div class=bodytable>
	<center><?php if ($_SESSION[paginate]=='1') { ?><font size=2>page:&nbsp;<?php printf("%s", ($_SESSION[page] + 1));?>&nbsp;|&nbsp;<?php } ?>sorted by:&nbsp;<?php echo $_SESSION[sort];?>&nbsp;|&nbsp;search criteria:&nbsp;<?php echo $criteria ?></font></center>
	</div>
	<br />
<?php
	$bc_open = "<b><center>";
	$bc_close = "</center></b>";
?>
	<div class=bodytable>
	<br />
	<center>
	<form name="search" action="<?php echo $_SERVER['PHP_SELF'] ?>" method="GET">
	search: 
	<input type="text" size="50" name="search">
	<input type="hidden" name="show" value="search">
	</form>	
	<script language="Javascript" type="text/javascript">
	document.search.search.focus();
	</script>
	</center>
	</div>
	<br />
<?php
	$letters_array = range('a', 'z');
	$letters_array_size = sizeof($letters_array);
	echo "<div class=bodytable><font size=3><center>";
	for ($counter = 0; $counter < $letters_array_size; $counter++) {
		printf("<a href='%s?sort=%s&show=%s'>%s</a>", $_SERVER[PHP_SELF], $_SESSION[sort], $letters_array[$counter], $letters_array[$counter]);
		if ($letters_array_size != ($counter + 1)) {
			echo " - ";
		}
	}
	printf("<br><a href='%s?sort=%s&show=all'>all</a>", $_SERVER[PHP_SELF], $_SESSION[sort]);
	echo '</div>';
	echo '<br>';
	if(is_array($_SESSION[contact_array])) {
	echo "<table class=bodytable>";
?>
	<form name=checks>
	<tr class=bodytitle><td class=bodytitleitem>Select</td><td class=bodytitleitem><?php echo $bc_open ?><a href='<?php echo $_SERVER['PHP_SELF'] ?>?sort=name'>Name</a><?php echo $bc_close ?></a></td><td class=bodytitleitem><?php echo $bc_open ?><a href='<?php echo $_SERVER['PHP_SELF'] ?>?sort=organization'>Organization</a><?php echo $bc_close ?></td><td class=bodytitleitem><?php echo $bc_open ?><a href='<?php echo $_SERVER['PHP_SELF'] ?>?sort=city'>City</a><?php echo $bc_close ?></td><td class=bodytitleitem><?php echo $bc_open ?><a href='<?php echo $_SERVER['PHP_SELF'] ?>?sort=calendar_n&search=yes&show=search'>Cal.N.</a><?php echo $bc_close ?></td><td class=bodytitleitem><?php echo $bc_open ?><a href='<?php echo $_SERVER['PHP_SELF'] ?>?sort=calendar_a&search=yes&show=search'>Cal.A.</a><?php echo $bc_close ?></td><td class=bodytitleitem><?php echo $bc_open ?><a href='<?php echo $_SERVER['PHP_SELF'] ?>?sort=catalog&search=yes&show=search'>Cat.</a><?php echo $bc_close ?></td></tr>
<?php
	if($_SESSION[paginate]==1) {
		foreach ($_SESSION[chunk] as $value) {
			echo "<tr>";
			echo "<td class=bodyitem><center><a name=$value[customers_id]><input type=checkbox name=select id=selector value=$value[customers_id]></a></center></td><td class=bodyitem><a href='$_SERVER[PHP_SELF]?body=view&view=$value[customers_id]'>$value[customers_name]</a></td>";
			echo "<td class=bodyitem><a href='$_SERVER[PHP_SELF]?body=view&view=$value[customers_id]'>$value[customers_organization]</td>";
			echo "<td class=bodyitem>$value[customers_city]</td>";
			if ($value[calendar_n]=="YES") { echo "<td class=bodyitem>$value[calendar_n]</td>"; } else { echo "<td class=bodyitem>&nbsp;</td>"; }
			if ($value[calendar_a]=="YES") { echo "<td class=bodyitem>$value[calendar_a]</td>"; } else { echo "<td class=bodyitem>&nbsp;</td>"; }
			if ($value[catalog]=="YES") { echo "<td class=bodyitem>$value[catalog]</td>"; } else { echo "<td class=bodyitem>&nbsp;</td>"; }
			echo "<tr>";
		}
	} else {
		foreach ($_SESSION[contact_array] as $value) {
                        echo "<tr>";
                        echo "<td class=bodyitem><center><a name=$value[customers_id]><input type=checkbox name=select id=selector value=$value[customers_id]></a></center></td><td class=bodyitem><a href='$_SERVER[PHP_SELF]?body=view&view=$value[customers_id]'>$value[customers_name]</a></td>";
                        echo "<td class=bodyitem><a href='$_SERVER[PHP_SELF]?body=view&view=$value[customers_id]'>$value[customers_organization]</td>";
			echo "</a>";
                        echo "<td class=bodyitem>$value[customers_city]</td>";
                        if ($value[calendar_n]=="YES") { echo "<td class=bodyitem>$value[calendar_n]</td>"; } else { echo "<td class=bodyitem>&nbsp;</td>"; }
                        if ($value[calendar_a]=="YES") { echo "<td class=bodyitem>$value[calendar_a]</td>"; } else { echo "<td class=bodyitem>&nbsp;</td>"; }
                        if ($value[catalog]=="YES") { echo "<td class=bodyitem>$value[catalog]</td>"; } else { echo "<td class=bodyitem>&nbsp;</td>"; }
                        echo "<tr>";
                }
	}
	echo "</form>";
	echo "<table>";
	echo "</font></center><br>";
	} else {
		echo "<font size=2>no contact information matches search criteria.</font>";
	}
	echo "<div class=bodytable><font size=3><center>";
	for ($counter = 0; $counter < $letters_array_size; $counter++) {
		printf("<a href='%s?sort=%s&show=%s'>%s</a>", $_SERVER[PHP_SELF], $_SESSION[sort], $letters_array[$counter], $letters_array[$counter]);
		if ($letters_array_size != ($counter + 1)) {
			echo " - ";
		}
	}
	printf("<br><a href='%s?sort=%s&show=all'>all</a>", $_SERVER[PHP_SELF], $_SESSION[sort]);
	echo '</div>';
