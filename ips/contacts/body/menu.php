<span onMouseOver="menuOn('Edit'); window.status='Edit'; return true;" onMouseOut="overChecker('Edit'); window.status=' '; return true;"><img src="images/edit_off.gif" width=67 height=33 border=0 name="Edit" alt="Edit"></span>

<span onMouseOver="menuOn('Print'); window.status='Print'; return true;" onMouseOut="overChecker('Print'); window.status=' '; return true;"><img src="images/print_off.gif" width=150 height=33 border=0 name="Print" alt="Print"></span>

<div id="EditMenu" onMouseOver="over = 'yes';" onMouseOut="overChecker('Edit');">
<script language="Javascript" type="text/javascript">
	function onMouseOver() {over = 'yes';}
	function onMouseOut() {overChecker('Edit');}
</script>
<table border="1" cellpadding="2" cellspacing="0" bordercolor="#CAD142">

<tr>
<td valign="top" align="left" bgcolor="#000">
<a href=" " onclick="newWindow('body/view.php?body=form&form=add')" class="menuLink">Add A Contact</a></td>
</tr>

<tr>
<td valign="top" align="left" bgcolor="#000">
<a href=" " onclick="popdelete();" class="menuLink">Delete Selected</a></td>
</tr>

<tr>
<td valign="top" align="left" bgcolor="#000">
<a href=" " onclick="popcopy();" class="menuLink">Copy Selected</a></td>
</tr>

</table>

</div>


<div id="PrintMenu" onMouseOver="over = 'yes';" onMouseOut="overChecker('Print');">
<script language="Javascript" type="text/javascript">
	function onMouseOver() {over = 'yes';}
	function onMouseOut() {overChecker('Print');}
</script>
<table border="1" cellpadding="2" cellspacing="0" bordercolor="#CAD142">

<tr>
<td valign="top" align="left" bgcolor="#000">
<a href=" " onclick="pdfmenuOpen('9x12','env');" class="menuLink">9x12 envelope(s)</a></td>
</tr>

<tr>
<td valign="top" align="left" bgcolor="#000">
<a href=" " onclick="pdfmenuOpen('10x13','env');" class="menuLink">10x13 envelope(s)</a></td>
</tr>

<tr>
<td valign="top" align="left" bgcolor="#000">
<a href=" " onclick="pdfmenuOpen('8x11','labels');" class="menuLink">shipping label(s)</a></td>
</tr>

<tr>
<td valign="top" align="left" bgcolor="#000">
<a href="http://contacts/pdf/print_env.php?pdftype=animals" class="menuLink">animal calendars</a></td>
</tr>

<tr>
<td valign="top" align="left" bgcolor="#000">
<a href="http://contacts/pdf/print_env.php?pdftype=nudies" class="menuLink">nudie calendars</a></td>
</tr>

<tr>
<td valign="top" align="left" bgcolor="#000">
<a href="http://contacts/pdf/print_env.php?pdftype=both" class="menuLink">both calendars</a></td>
</tr>

<tr>
<td valign="top" align="left" bgcolor="#000">
<a href="http://contacts/pdf/print_env.php?pdftype=catalogs" class="menuLink">catalogs</a></td>
</tr>

</table>

</div>
