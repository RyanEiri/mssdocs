<SCRIPT language="JavaScript1.2" type="text/javascript">
<!-- Code after this will be ignored by older browsers

// Assign the user information to a Variable
var platform = navigator.platform.substr(0,3);
var browser = navigator.appName;
var version = navigator.appVersion.substr(0,1);

function alterChecks(action, variables) {
	var tmpWindow = window.open("<?php echo $_SERVER['PHP_SELF'] ?>?body=" + action + "&confirm=1&" + action + "=" + variables);
//	tmpWindow.close();
}

function popdelete() {
	var msg = "Would you like to remove the selected entries?\n";
	if (confirm(msg)) {
		var checked = [];
		for (var count = 0 ; count < document.checks.select.length ; count++) {
			if (document.checks.select[count].checked == true) {
				checked.push(document.checks.select[count].value);
			}
		}
		for (var count = 0 ; count < checked.length ; count++) {
			if (count == 0) {
				var todelete = checked[count];
			} else {
				var todelete = checked[count] + "," + todelete;
			}
		}
		if (!todelete) {
			var todelete = document.checks.select.value;
		}
		action = "delete";
		alterChecks(action, todelete);
//		location.replace("<?php echo $_SERVER['PHP_SELF'] ?>?body=delete&confirm=1&delete=" + todelete);
//		window.open("<?php echo $_SERVER['PHP_SELF'] ?>?body=delete&confirm=1&delete=" + todelete);
	} else {
	}
}

function popcopy() {
	var msg = "Would you like to copy the selected entries?\n";
	if (confirm(msg)) {
		var checked = [];
		for (var count = 0 ; count < document.checks.select.length ; count++) {
			if (document.checks.select[count].checked == true) {
				checked.push(document.checks.select[count].value);
			}
		}
		for (var count = 0 ; count < checked.length ; count++) {
			if (count == 0) {
				var tocopy = checked[count];
			} else {
				var tocopy = checked[count] + "," + tocopy;
			}
		}
		if (!tocopy) {
			var tocopy = document.checks.select.value;
		}
		action = "copy";
		alterChecks(action, tocopy);
//		location.replace("<?php echo $_SERVER['PHP_SELF'] ?>?body=copy&confirm=1&copy=" + tocopy);
//		window.open("<?php echo $_SERVER['PHP_SELF'] ?>?body=copy&amp;confirm=1&amp;copy=" + tocopy);
//		hello = window.open("<?php echo $_SERVER['PHP_SELF'] ?>?body=copy&confirm=1&copy=undefined");
//		hello.document.write(tocopy);
		
	} else {
	}
}

function pdfmenuOpen(size,type) {
	var msg = "Would you like to generate a pdf with your selection?\n";
	if (confirm(msg)) {
		var checked = [];
		for (var count = 0 ; count < document.checks.select.length ; count++) {
			if (document.checks.select[count].checked == true) {
				checked.push(document.checks.select[count].value);
			}
		}
		for (var count = 0 ; count < checked.length ; count++) {
			if (count == 0) {
				var printable = checked[count];
			} else {
				var printable = checked[count] + "," + printable;
			}
		}
		if (!printable) {
			var printable = document.checks.select.value;
		}
	//	pdfGen=window.open(resource, 'PDF_Generator', 'menubar=no,resizable=no,height=480,width=640');	
	//	pdfGen.moveTo('200','100');
		window.open('./pdf/print_' + type + '.php?pdftype=selection&pdfsize=' + size + '&print_request=' + printable);
	} else {
	}
}

function menustick(onoff) {
	if (onoff=='1') {
		var msg = "Would you like to make the side menu sticky?\n";
		if (confirm(msg)) {
			//location.replace("<?php echo $_SERVER['PHP_SELF'] ?>?body=menustick&menustick=1");
			menu = window.open("<?php echo $_SERVER['PHP_SELF'] ?>?body=menustick&menustick=1");
			menu.close();
		} else {
		}
	} else {
		var msg = "Would you like to make the side menu unsticky?\n";
		if (confirm(msg)) {
			//location.replace("<?php echo $_SERVER['PHP_SELF'] ?>?body=menustick&menustick=0");
			menu = window.open("<?php echo $_SERVER['PHP_SELF'] ?>?body=menustick&menustick=0");
			menu.close();
		} else {
		}
	}
}

function paginateon() {
	var msg = "Would you like to turn pagination on?\n";
	if (confirm(msg)) {
		location.replace("<?php echo $_SERVER['PHP_SELF'] ?>?paginate=yes");	
	} else {
		
	}
}	

function paginateoff() {
	var msg = "Would you like to turn pagination off?\n";
	if (confirm(msg)) {
		location.replace("<?php echo $_SERVER['PHP_SELF'] ?>?paginate=no");
	} else {

	}
}

var over = 'no';
var lastMenu = ' ';
var styleSheetElement;
var oldElement;

function menuOn(currentMenu) {
        over = 'yes';
        if ((browser == 'Netscape') && (version < 5)) {
                if (lastMenu != ' ') {
                        document.images[lastMenu].src = eval(lastMenu + 'Off.src');
                        eval('document.' + lastMenu + 'Menu.visibility = "hidden"');
                }
                document.images[currentMenu].src = eval(currentMenu + 'On.src');
                eval('document.' + currentMenu + 'Menu.visibility = "visible"');
                lastMenu = currentMenu;
        } else {
                if (lastMenu != ' ') {
                	eval('document.images[lastMenu].src = ' + lastMenu + 'Off.src');
	                lastMenu = lastMenu + 'Menu';
       		        oldElement = document.getElementById(lastMenu);
			oldElement.style.visibility = "hidden";
        	}
        	eval('document.images[currentMenu].src = ' + currentMenu + 'On.src');
	        var layerName = currentMenu + 'Menu';
                styleSheetElement = document.getElementById(layerName);
                styleSheetElement.style.visibility = "visible";
                lastMenu = currentMenu;
        }
}

function overChecker(currentMenu) {
        over = 'no';
        lastMenu = currentMenu;
        setTimeout("menuOff()", 300);
}

function menuOff() {
        if (over == 'no') {
                if ((browser == 'Netscape') && (version < 5)) {
                        document.images[lastMenu].src = eval(lastMenu + 'Off.src');
                        eval('document.' + lastMenu + 'Menu.visibility = "hidden"');
                } else {
                        eval('document.images[lastMenu].src = ' + lastMenu + 'Off.src');
                        styleSheetElement.style.visibility = "hidden";
                }
        }
}

// An if statement used to print out the proper css file
if ((platform == 'Mac') && (browser == 'Netscape') && (version <= 4)) {
	document.writeln("<link rel=\"stylesheet\" type=\"text/css\" href=\"css\/styles_mac_4x.css\">");
} else if ((platform == 'Win') && (browser == 'Microsoft Internet Explorer') && (version >= 4)) {
	document.writeln("<link rel=\"stylesheet\" type=\"text/css\" href=\"<?php if($_GET[body]=='form') { echo "..\/"; } ?>css\/styles_ie.css\">");
} else if ((platform == 'Lin') && (browser == 'Netscape') && (version > 4)) {
	document.writeln("<link rel=\"stylesheet\" type=\"text/css\" href=\"<?php if($_GET[body]=='form') { echo "..\/"; } ?>css\/styles_linux.css\">");
} else if ((platform == 'Lin') && (browser == 'Microsoft Internet Explorer') && (version >= 4)) {
	document.writeln("<link rel=\"stylesheet\" type=\"text/css\" href=\"<?php if($_GET[body]=='form') { echo "..\/"; } ?>css\/styles_opera.css\">");
} else if ((platform == 'Lin') && (browser == 'Opera') && (version >= 9)) {
	document.writeln("<link rel=\"stylesheet\" type=\"text/css\" href=\"<?php if($_GET[body]=='form') { echo "..\/"; } ?>css\/styles_opera.css\">");
} else if ((platform == 'Win') && (browser == 'Netscape') && (version > 4)) {
	document.writeln("<link rel=\"stylesheet\" type=\"text/css\" href=\"<?php if($_GET[body]=='form') { echo "..\/"; } ?>css\/styles_firewin.css\">");
} else {
	document.writeln("<link rel=\"stylesheet\" type=\"text/css\" href=\"<?php if($_GET[body]=='form') { echo "..\/"; } ?>css\/styles_default.css\">");
}

// Creation of the image objects
if (document.images) {
	LinksOn=new Image(71, 33);
	LinksOn.src="images/links_on.gif";

	LinksOff=new Image(71, 33);
	LinksOff.src="images/links_off.gif";

	EditOn=new Image(67, 33);
	EditOn.src="images/edit_on.gif";

	EditOff=new Image(67, 33);
	EditOff.src="images/edit_off.gif";

	PrintOn=new Image(150, 33);
	PrintOn.src="images/print_on.gif";

	PrintOff=new Image(150, 33);
	PrintOff.src="images/print_off.gif";
}

var over = 'no';
var lastMenu = ' ';
var styleSheetElement;
var oldElement;

function menuOn(currentMenu) {
	over = 'yes';
	if ((browser == 'Netscape') && (version < 5)) {
		if (lastMenu != ' ') {
			document.images[lastMenu].src = eval(lastMenu + 'Off.src');
			eval('document.' + lastMenu + 'Menu.visibility = "hidden"');
		}
		document.images[currentMenu].src = eval(currentMenu + 'On.src');
		eval('document.' + currentMenu + 'Menu.visibility = "visible"');
		lastMenu = currentMenu;
	} else {
		if (lastMenu != ' ') {
			eval('document.images[lastMenu].src = ' + lastMenu + 'Off.src');
			lastMenu = lastMenu + 'Menu';
			oldElement = document.getElementById(lastMenu);
			oldElement.style.visibility = "hidden";
		}
		eval('document.images[currentMenu].src = ' + currentMenu + 'On.src');
		var layerName = currentMenu + 'Menu';
		styleSheetElement = document.getElementById(layerName);
		styleSheetElement.style.visibility = "visible";
		lastMenu = currentMenu;
	}
}

function overChecker(currentMenu) {
	over = 'no';
	lastMenu = currentMenu;
	setTimeout("menuOff()", 300);
}

function menuOff() {
	if (over == 'no') {
		if ((browser == 'Netscape') && (version < 5)) {
			document.images[lastMenu].src = eval(lastMenu + 'Off.src');
			eval('document.' + lastMenu + 'Menu.visibility = "hidden"');
		} else {
			eval('document.images[lastMenu].src = ' + lastMenu + 'Off.src');
			styleSheetElement.style.visibility = "hidden";
		}
	}
}

function newWindow(url,cust) {
	var w = window.open(url, cust, "width=520,height=475,status=no");
	w.moveTo(100,100);
}

// Function for automatically moving the cursor from one field to the next.
// var $tochange == The form field that you are moving from.
// var $changeto == The form field that you are moving to.
// var $name == The name of the form the fields reside in.
function fieldautomove(tochange,changeto,name) {
        var length = eval('document.' + name + '.' + tochange + '.value.length');
        var maxlength = eval('document.' + name + '.' + tochange + '.maxLength');
        if (length == (maxlength - 1)) { 
                eval('document.' + name + '.' + changeto + '.focus()'); 
        }
}


// Stop hiding the code here -->
</SCRIPT>
