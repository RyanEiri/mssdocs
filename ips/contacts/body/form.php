<?php
$usurper->CheckIt();
if ($_GET[form] == 'add') {
	$form = 'add';
?>
	<input type="hidden" name="add" value="yes">
<?php
} elseif ($_GET[form] == 'change') {
	$form = 'change';
?>
	<input type="hidden" name="change" value="<?php echo $contact_object->view[customers_id] ?>">
<?php
}
?>

<div id="dhtmlgoodies_tabView">

<?php if($form != 'add'){?>
<div class="dhtmlgoodies_aTab">
<h2>Summary</h2>
<h3>Name:</h3>
<strong><?php echo $contact_object->view[customers_name] ?></strong>@<?php echo $contact_object->view[customers_organization] ?>
<br /><strong>Department: <?php echo $contact_object->view[customers_department] ?></strong>
<br><br>
<h3>Address:</h3>
<?php echo $contact_object->view[customers_address_1] ?><br><?php echo $contact_object->view[customers_address_2] ?>
<br><?php echo $contact_object->view[customers_city] ?>, <?php echo $contact_object->view[customers_province] ?>
<br><?php echo $contact_object->view[customers_postal_code] ?>
<br><br>
<h3>Telephone:</h3>
<?php if(($contact_object->phones[b_phone1] != NULL) && ($contact_object->phones[b_phone2] != NULL) && ($contact_object->phones[b_phone3] != NULL)){ ?>
Business:  (<?php echo $contact_object->phones[b_phone1] ?>) <?php echo $contact_object->phones[b_phone2] ?>-<?php echo $contact_object->phones[b_phone3] ?>
<br>
<?php } ?>
<?php if(($contact_object->phones[f_phone1] != NULL) && ($contact_object->phones[f_phone2] != NULL) && ($contact_object->phones[f_phone3] != NULL)){ ?>
Fax:  (<?php echo $contact_object->phones[f_phone1] ?>) <?php echo $contact_object->phones[f_phone2] ?>-<?php echo $contact_object->phones[f_phone3] ?>
<br>
<?php } ?>
<?php if(($contact_object->phones[h_phone1] != NULL) && ($contact_object->phones[h_phone2] != NULL) && ($contact_object->phones[h_phone3] != NULL)){ ?>
Home:  (<?php echo $contact_object->phones[h_phone1] ?>) <?php echo $contact_object->phones[h_phone2] ?>-<?php echo $contact_object->phones[h_phone3] ?>
<br>
<?php } ?>
<?php if(($contact_object->phones[toll_free_phone1] != NULL) && ($contact_object->phones[toll_free_phone2] != NULL) && ($contact_object->phones[toll_free_phone3] != NULL)){ ?>
Toll Free:  (<?php echo $contact_object->phones[toll_free_phone1] ?>) <?php echo $contact_object->phones[toll_free_phone2] ?>-<?php echo $contact_object->phones[toll_free_phone3] ?>
<?php } ?>
</div>
<?php } ?>

<div class="dhtmlgoodies_aTab">
<h2>Name</h2>
	name:<br />
	<input type="text" name="name" size="50" value="<?php echo $contact_object->view[customers_name] ?>"><br /><br />

        position:<br />
	<input type="text" name="position" size="50" value="<?php echo $contact_object->view[customers_position] ?>"><br /><br />

        organization:<br />
	<input type="text" name="organization" size="50" value="<?php echo $contact_object->view[customers_organization] ?>"><br /><br />

	department:<br />
	<input type="text" name="department" size="50" value="<?php echo $contact_object->view[customers_department] ?>"><br /><br />
	<br />
	<?php
	if ($form == 'add') {
		echo "<button type='submit'>Add Contact</button><br /><br />";
	} else {
        	echo "<button type='submit'>Change Contact</button><br /><br />";
	}
?>
</div>
<div class="dhtmlgoodies_aTab">
<h2>Address</h2>
        address line 1:<br />
<input type="text" name="address_1" size="50" value="<?php echo $contact_object->view[customers_address_1] ?>" maxlength="100"><br /><br />
        address line 2:<br />
<input type="text" name="address_2" size="50" maxlength="100" value="<?php echo $contact_object->view[customers_address_2] ?>"><br /><br />
        city:<br />
<input type="text" name="city" size="50" maxlength="100" value="<?php echo $contact_object->view[customers_city] ?>"><br /><br />
        province:<br />
        <select name="province">
	<option>------------</option>
	<option>Provinces</option>
	<option>------------</option>
<option value="MB" <?php if ($contact_object->view[customers_province]=="MB") { echo "selected"; } ?>>Manitoba</option>
<option value="ON" <?php if ($contact_object->view[customers_province]=="ON") { echo "selected"; } ?>>Ontario</option>
<option value="NT" <?php if ($contact_object->view[customers_province]=="NT") { echo "selected"; } ?>>North West Territory</option>
<option value="YT" <?php if ($contact_object->view[customers_province]=="YT") { echo "selected"; } ?>>Yukon Territory</option>
<option value="AB" <?php if ($contact_object->view[customers_province]=="AB") { echo "selected"; } ?>>Alberta</option>
<option value="SK" <?php if ($contact_object->view[customers_province]=="SK") { echo "selected"; } ?>>Saskatchewan</option>
<option value="NB" <?php if ($contact_object->view[customers_province]=="NB") { echo "selected"; } ?>>New Brunswick</option>
<option value="NS" <?php if ($contact_object->view[customers_province]=="NS") { echo "selected"; } ?>>Nova Scotia</option>
<option value="NL" <?php if ($contact_object->view[customers_province]=="NL") { echo "selected"; } ?>>Newfoundland</option>
<option value="BC" <?php if ($contact_object->view[customers_province]=="BC") { echo "selected"; } ?>>British Columbia</option>
<option value="NU" <?php if ($contact_object->view[customers_province]=="NU") { echo "selected"; } ?>>Nunavut</option>
<option value="PE" <?php if ($contact_object->view[customers_province]=="PE") { echo "selected"; } ?>>Prince Edward Island</option>
<option value="QC" <?php if ($contact_object->view[customers_province]=="QC") { echo "selected"; } ?>>Quebec</option>
	<option>------------</option>
	<option>States</option>
	<option>------------</option>
<option value="AL" <?php if ($contact_object->view[customers_province]=="AL") { echo "selected"; } ?>>Alabama</option>
<option value="AK" <?php if ($contact_object->view[customers_province]=="AK") { echo "selected"; } ?>>Alaska</option>
<option value="AS" <?php if ($contact_object->view[customers_province]=="AS") { echo "selected"; } ?>>American Samoa</option>
<option value="AZ" <?php if ($contact_object->view[customers_province]=="AZ") { echo "selected"; } ?>>Arizona</option>
<option value="AR" <?php if ($contact_object->view[customers_province]=="AR") { echo "selected"; } ?>>Arkansas</option>
<option value="CA" <?php if ($contact_object->view[customers_province]=="CA") { echo "selected"; } ?>>California</option>
<option value="CO" <?php if ($contact_object->view[customers_province]=="CO") { echo "selected"; } ?>>Colorado</option>
<option value="CT" <?php if ($contact_object->view[customers_province]=="CT") { echo "selected"; } ?>>Connecticut</option>
<option value="DE" <?php if ($contact_object->view[customers_province]=="DE") { echo "selected"; } ?>>Delaware</option>
<option value="DC" <?php if ($contact_object->view[customers_province]=="DC") { echo "selected"; } ?>>District of Columbia</option>
<option value="FM" <?php if ($contact_object->view[customers_province]=="FM") { echo "selected"; } ?>>Federated States of Micronesia</option>
<option value="FL" <?php if ($contact_object->view[customers_province]=="FL") { echo "selected"; } ?>>Florida</option>
<option value="GA" <?php if ($contact_object->view[customers_province]=="GA") { echo "selected"; } ?>>Georgia</option>
<option value="GU" <?php if ($contact_object->view[customers_province]=="GU") { echo "selected"; } ?>>Guam</option>
<option value="HI" <?php if ($contact_object->view[customers_province]=="HI") { echo "selected"; } ?>>Hawaii</option>
<option value="ID" <?php if ($contact_object->view[customers_province]=="ID") { echo "selected"; } ?>>Idaho</option>
<option value="IL" <?php if ($contact_object->view[customers_province]=="IL") { echo "selected"; } ?>>Illinois</option>
<option value="IN" <?php if ($contact_object->view[customers_province]=="IN") { echo "selected"; } ?>>Indiana</option>
<option value="IA" <?php if ($contact_object->view[customers_province]=="IA") { echo "selected"; } ?>>Iowa</option>
<option value="KS" <?php if ($contact_object->view[customers_province]=="KS") { echo "selected"; } ?>>Kansas</option>
<option value="KY" <?php if ($contact_object->view[customers_province]=="KY") { echo "selected"; } ?>>Kentucky</option>
<option value="LA" <?php if ($contact_object->view[customers_province]=="LA") { echo "selected"; } ?>>Louisiana</option>
<option value="ME" <?php if ($contact_object->view[customers_province]=="ME") { echo "selected"; } ?>>Maine</option>
<option value="MH" <?php if ($contact_object->view[customers_province]=="MH") { echo "selected"; } ?>>Marshall Islands</option>
<option value="MD" <?php if ($contact_object->view[customers_province]=="MD") { echo "selected"; } ?>>Maryland</option>
<option value="MA" <?php if ($contact_object->view[customers_province]=="MA") { echo "selected"; } ?>>Massachusetts</option>
<option value="MI" <?php if ($contact_object->view[customers_province]=="MI") { echo "selected"; } ?>>Michigan</option>
<option value="MN" <?php if ($contact_object->view[customers_province]=="MN") { echo "selected"; } ?>>Minnesota</option>
<option value="MS" <?php if ($contact_object->view[customers_province]=="MS") { echo "selected"; } ?>>Mississippi</option>
<option value="MO" <?php if ($contact_object->view[customers_province]=="MO") { echo "selected"; } ?>>Missouri</option>
<option value="MT" <?php if ($contact_object->view[customers_province]=="MT") { echo "selected"; } ?>>Montanta</option>
<option value="NE" <?php if ($contact_object->view[customers_province]=="NE") { echo "selected"; } ?>>Nebraska</option>
<option value="NV" <?php if ($contact_object->view[customers_province]=="NV") { echo "selected"; } ?>>Nevada</option>
<option value="NH" <?php if ($contact_object->view[customers_province]=="NH") { echo "selected"; } ?>>New Hampshire</option>
<option value="NJ" <?php if ($contact_object->view[customers_province]=="NJ") { echo "selected"; } ?>>New Jersey</option>
<option value="NM" <?php if ($contact_object->view[customers_province]=="NM") { echo "selected"; } ?>>New Mexico</option>
<option value="NY" <?php if ($contact_object->view[customers_province]=="NY") { echo "selected"; } ?>>New York</option>
<option value="NC" <?php if ($contact_object->view[customers_province]=="NC") { echo "selected"; } ?>>North Carolina</option>
<option value="ND" <?php if ($contact_object->view[customers_province]=="ND") { echo "selected"; } ?>>North Dakota</option>
<option value="MP" <?php if ($contact_object->view[customers_province]=="MP") { echo "selected"; } ?>>Northern Mariana Islands</option>
<option value="OH" <?php if ($contact_object->view[customers_province]=="OH") { echo "selected"; } ?>>Ohio</option>
<option value="OK" <?php if ($contact_object->view[customers_province]=="OK") { echo "selected"; } ?>>Oklahoma</option>
<option value="OR" <?php if ($contact_object->view[customers_province]=="OR") { echo "selected"; } ?>>Oregon</option>
<option value="PW" <?php if ($contact_object->view[customers_province]=="PW") { echo "selected"; } ?>>Palau</option>
<option value="PA" <?php if ($contact_object->view[customers_province]=="PA") { echo "selected"; } ?>>Pennsylvania</option>
<option value="PR" <?php if ($contact_object->view[customers_province]=="PR") { echo "selected"; } ?>>Puerto Rico</option>
<option value="RI" <?php if ($contact_object->view[customers_province]=="RI") { echo "selected"; } ?>>Rhode Island</option>
<option value="SC" <?php if ($contact_object->view[customers_province]=="SC") { echo "selected"; } ?>>South Carolina</option>
<option value="SD" <?php if ($contact_object->view[customers_province]=="SD") { echo "selected"; } ?>>South Dakota</option>
<option value="TN" <?php if ($contact_object->view[customers_province]=="TN") { echo "selected"; } ?>>Tennessee</option>
<option value="TX" <?php if ($contact_object->view[customers_province]=="TX") { echo "selected"; } ?>>Texas</option>
<option value="UT" <?php if ($contact_object->view[customers_province]=="UT") { echo "selected"; } ?>>Utah</option>
<option value="VT" <?php if ($contact_object->view[customers_province]=="VT") { echo "selected"; } ?>>Vermont</option>
<option value="VI" <?php if ($contact_object->view[customers_province]=="VI") { echo "selected"; } ?>>Virgin Islands</option>
<option value="VA" <?php if ($contact_object->view[customers_province]=="VA") { echo "selected"; } ?>>Virginia</option>
<option value="WA" <?php if ($contact_object->view[customers_province]=="WA") { echo "selected"; } ?>>Washington</option>
<option value="WV" <?php if ($contact_object->view[customers_province]=="WV") { echo "selected"; } ?>>West Virginia</option>
<option value="WI" <?php if ($contact_object->view[customers_province]=="WI") { echo "selected"; } ?>>Wisconsin</option>
<option value="WY" <?php if ($contact_object->view[customers_province]=="WY") { echo "selected"; } ?>>Wyoming</option>
        </select><br /><br />
        postal code:<br />
<input type="text" name="postal_code" size="7" maxlength="7" value="<?php echo $contact_object->view[customers_postal_code] ?>"><br /><br />
<?php
	if ($form == 'add') {
		echo "<button type='submit'>Add Contact</button><br /><br />";
	} else {
        	echo "<button type='submit'>Change Contact</button><br /><br />";
	}
?>
</div>
	<div class="dhtmlgoodies_aTab">
<h2>Telephone</h2>
        business phone:<?php if(($contact_object->phones[b_phone1] != NULL) && ($contact_object->phones[b_phone2] != NULL) && ($contact_object->phones[b_phone3] != NULL)) {
	?>
	&nbsp;(<?php echo $contact_object->phones[b_phone1]; ?>)&nbsp;<?php echo $contact_object->phones[b_phone2]; ?>-<?php echo $contact_object->phones[b_phone3]; ?>
	<?php } ?>
	<br /><div id="message_b"></div>
	<input type="text" name="b_phone" id="b_phone" size="11" maxlength="10" value="<?php echo $contact_object->phones[b_phone1] . $contact_object->phones[b_phone2] . $contact_object->phones[b_phone3]; ?>">
	<br /><br /> 


	fax phone:<?php if(($contact_object->phones[f_phone1] != NULL) && ($contact_object->phones[f_phone2] != NULL) && ($contact_object->phones[f_phone3] != NULL)) {
	?>	
	&nbsp;(<?php echo $contact_object->phones[f_phone1]; ?>)&nbsp;<?php echo $contact_object->phones[f_phone2]; ?>-<?php echo $contact_object->phones[f_phone3];?>
	<?php } ?>
	<br /><div id="message_f"></div>
	<input type="text" name="f_phone" id="f_phone" size="11" maxlength="10" value="<?php echo $contact_object->phones[f_phone1] . $contact_object->phones[f_phone2] . $contact_object->phones[f_phone3]; ?>"><br /><br /> 


	home phone:<?php if(($contact_object->phones[h_phone1] != NULL) && ($contact_object->phones[h_phone2] != NULL) && ($contact_object->phones[h_phone3] != NULL)) {
	?>
	&nbsp;(<?php echo $contact_object->phones[h_phone1]; ?>)&nbsp;<?php echo $contact_object->phones[h_phone2]; ?>-<?php echo $contact_object->phones[h_phone3];?>
	<?php } ?>
	<br /><div id="message_h"></div>
	<input type="text" name="h_phone" id="h_phone" size="11" maxlength="10" value="<?php echo $contact_object->phones[h_phone1] . $contact_object->phones[h_phone2] . $contact_object->phones[h_phone3]; ?>"><br /><br /> 


	cell phone:<?php if(($contact_object->phones[c_phone1] != NULL) && ($contact_object->phones[c_phone2] != NULL) && ($contact_object->phones[c_phone3] != NULL)) {
	?>
	&nbsp;(<?php echo $contact_object->phones[c_phone1]; ?>)&nbsp;<?php echo $contact_object->phones[c_phone2]; ?>-<?php echo $contact_object->phones[c_phone3];?>
	<?php } ?>
	<br /><div id="message_c"></div>
	<input type="text" name="c_phone" id="c_phone" size="11" maxlength="10" value="<?php echo $contact_object->phones[c_phone1] . $contact_object->phones[c_phone2] . $contact_object->phones[c_phone3]; ?>"><br /><br /> 


        toll free:<?php if(($contact_object->phones[toll_free_phone1] != NULL) && ($contact_object->phones[toll_free_phone2] != NULL) && ($contact_object->phones[toll_free_phone3] != NULL)) {
	?>
	&nbsp;(<?php echo $contact_object->phones[toll_free_phone1]; ?>)&nbsp;<?php echo $contact_object->phones[toll_free_phone2]; ?>-<?php echo $contact_object->phones[toll_free_phone3];?>
	<?php } ?>
	<br /><div id="message_toll_free"></div>
	<input type="text" name="toll_free_phone" id="toll_free_phone" size="11" maxlength="10" value="<?php echo $contact_object->phones[toll_free_phone1] . $contact_object->phones[toll_free_phone2] . $contact_object->phones[toll_free_phone3]; ?>"><br /><br /> 
	<br />
	


<?php
	if ($form == 'add') {
		echo "<button type='submit'>Add Contact</button><br /><br />";
	} else {
        	echo "<button type='submit'>Change Contact</button><br /><br />";
	}
?>
	</div>
	<div class="dhtmlgoodies_aTab">
<h2>Categories</h2>
        calendar n:<br />
        <select name="calendar_n">
<option name="YES" value="YES" <?php if ($contact_object->view[calendar_n]=="YES") { echo "selected"; } ?>>YES</option>
<option name="NO" value="NO" <?php if (($contact_object->view[calendar_n]=="NO") OR ($contact_object->view[calendar_n]==NULL))  { echo "selected"; } ?>>NO</option>
        </select><br /><br />
        calendar a:<br />
        <select name="calendar_a">
<option name="YES" value="YES" <?php if ($contact_object->view[calendar_a]=="YES") { echo "selected"; } ?>>YES</option>
<option name="NO" value="NO" <?php if (($contact_object->view[calendar_a]=="NO") OR ($contact_object->view[calendar_a]==NULL)) { echo "selected"; } ?>>NO</option>
        </select><br /><br />
        catalog:<br />
        <select name="catalog">
<option name="YES" value="YES" <?php if ($contact_object->view[catalog]=="YES") { echo "selected"; } ?>>YES</option>
<option name="NO" value="NO" <?php if (($contact_object->view[catalog]=="NO") OR ($contact_object->view[catalog]==NULL)) { echo "selected"; } ?>>NO</option>
        </select><br /><br />
        vendor:<br />
        <select name="vendor">
<option name="YES" value="YES" <?php if ($contact_object->view[vendor]=="YES") { echo "selected"; } ?>>YES</option>
<option name="NO" value="NO" <?php if (($contact_object->view[vendor]=="NO") OR ($contact_object->view[vendor]==NULL)) { echo "selected"; } ?>>NO</option>
        </select><br /><br />
	<?php
	if ($form == 'add') {
		echo "<button type='submit'>Add Contact</button><br /><br />";
	} else {
        	echo "<button type='submit'>Change Contact</button><br /><br />";
	}
?>
	</div>
<div class="dhtmlgoodies_aTab">
<h2>Email</h2>
primary e-mail address:
<input type="text" name="email" size="50" maxlength="100" value="<?php echo $contact_object->view[email] ?>"><br /><br />
<?php
	if ($form == 'add') {
		echo "<button type='submit'>Add Contact</button><br /><br />";
	} else {
        	echo "<button type='submit'>Change Contact</button><br /><br />";
	}
?>
	</div>
	<div class="dhtmlgoodies_aTab">
<h2>Notes</h2>
	contact notes:<br />
<textarea name="notes" rows="11" cols="57"><?php echo $contact_object->view[notes] ?></textarea><br /><br />
	shipping notes:<br />
<textarea name="ship_notes" rows="5" cols="57"><?php echo $contact_object->shipview[notes] ?></textarea><br />
	<br />
<?php
	if ($form == 'add') {
		echo "<button type='submit'>Add Contact</button><br /><br />";
	} else {
        	echo "<button type='submit'>Change Contact</button><br /><br />";
	}
?>
	</div>
	</div>
