<?php

// Form Validation class. It passes true to your variable if the item passes the regex. 
// If it doesn't it passes false. This class doesn't handle error handling. Do that with
// a case statement in your page code. Plans are to have the error correction handled in
// the class, but it isn't that way yet.
// 05/29/07 - Removed print statements. Error handling being done compeletely within POST
// change script. This is dirty, and doesn't offer much flexibility. It would be nice to
// have this changed so that error messages could be more accurate. For example, if the
// email validation returns false, it can't tell whether it's due to the domain not exist-
// ing or if the address was just formatted incorrectly.
class Validate {

//        function check_email($form_variable){
//                if(!ereg("^.+@.+\..+$", $form_variable)){
//                        return false;
//		}
//        }

	function check_email($email){
		// Check syntax
		$validEmailExpr =	"^[0-9a-z~!#$%&_-]([.]?[0-9a-z~!#$%&_-])*" .
					"@[0-9a-z~!#$%&_-]([.]?[0-9a-z~!#$%&_-])*$";

//		$validEmailExpr =	"\w[-.\w]*" .
//					"\@\ *" .
//					"[a-a-z0-9]+(\.[-a-z0-9]+)*\.(com|edu|gov\Int|mil|net|org|biz|info|name|museum|coop|aero|[a-z][a-z])$";

		// Validate the email
		if(empty($email)){
//			print "the email field cannot be blank";
			return false;
		} elseif(!eregi($validEmailExpr, $email)){
//			print "The email must be in the name@domain format.";
			return false;
		} elseif(strlen($email) > 30){
//			print "The email address can be no longer than 30 characters.";
			return false;
		} elseif(function_exists("getmxrr") && function_exists("gethostbyname")){
			// Extract the domain of the email address
			$maildomain = substr(strstr($email, '@'), 1);

			if(!(getmxrr($maildomain, $temp) || gethostbyname($maildomain != $maildomain))){
//				print "The domain does not exist.";
				return false;
			}
		}
		return true;
	}

//        function check_postal_code($form_variable){
//                if(ereg("([aA-zZ][0-9][aA-zZ][0-9][aA-zZ][0-9])|[0-9]5|([0-9]5\-[0-9]4)", $form_variable))
//                        return (true);
//                else
//                        return (false);
//        }

	function check_postal_code($country, $code){
		switch ($country){
			case "Austria":
			case "Australia":
			case "Belgium":
			case "Canada":
				if(!ereg("([A-Z][0-9][A-Z]\ [0-9][A-Z][0-9])|^([0-9]{5}|[0-9]{5}-[0-9]{4})$", $code)){
//					print 	"The postcode/zipcode must be of the format A9A A9A.
//						A is any letter and 9 is any number.";
					return false;
				}
				break;
			case "Denmark":
			case "Norway":
			case "Portugal":
			case "Switzerland":
				if(!ereg("^[0-9]{4}$", $code)){
//					print "The postcode/zipcode must be 4 digits in length";
					return false;
				}
				break;
			case "Finland":
			case "France":
			case "Germany":
			case "Italy":
			case "Spain":
			case "USA":
				if (!ereg("^([0-9]{5}|[0-9]{5}-[0-9]{4})$", $code)){
//					print "The postcode/zipcode must be 5 digits in length";
					return false;
				}
				break;
			case "Greece":
				if (!ereg("^[0-9]{3}[ ][0-9]{2}$", $code)){
//					print "The postcode must have 3 digits, a space, and then 2 digits";
					return false;
				}
				break;
			case "Netherlands":
				if (!ereg("^[0-9]{4}[ ][A-Z]{2}$", $code)){
//					print "The postcode must have 4 digits, a space, and then 2 letters";
					return false;
				}
				break;
			case "Poland":
				if (!ereg("^[0-9]{2}-[0-9]{3}$", $code)){
//					print "The postcode must have 2 digits, a dash, and then 3 digits";
					return false;
				}
				break;
			case "Sweden":
				if(!ereg("^[0-9]{3}[ ][0-9]{2}$", $code)){
//					print "the postcode must have 3 digits, a space, and then 2 digits";
					return false;
				}
				break;
			case "United Kingdon":
				if(!ereg(	"^(([A-Z][0-9]{1,2})|([A-Z]{2}[0-9]{1,2})|" .
						"([A-Z]{2}[0-9][A-Z])|([A-Z][0-9][A-Z])|" .
						"([A-Z]{3}))[ ][0-9][A-Z]{2}$", $code)){
//					print 	"The postcode must begin with a string of the format
//						A9, A99, AA9, AA99, AA9A, A9A, or AAA,
//						and then be followed by a space and a string
//						of the form 9AA.
//						A is any letter and 9 is any number.";
					return false;
				}
				break;
			default:
				// No validation
		}
		return true;
	}

        function check_if_empty($form_variable){
                if(!ereg("^.+$", $form_variable)){
//			print "This field cannot be empty";
                        return false;
                } else {
                        return true;
        	}
	}

	function check_username($form_variable){
		if(!ereg("(^[aA-zZ]|^[0-9])([^+|-|\.|\(|\)|\*|\%|\$|\@|\!]*)([aA-zZ]$|[0-9]$)", $form_variable)){
//			print "Incorrectly entered username.";
			return false;
		} else {
			return true;
		}
	}

	function check_phone($form_variable){
//		if(!ereg("^[0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9]", $form_variable)){
		if(!ereg("^[0-9]{10}", $form_variable)){
//			print "Incorrectly entered phone number.";
			return false;
		} else {
			return true;
		}
	}

	function check_city($form_variable){
		if(!ereg("^[aA-zZ].+[aA-zZ]$", $form_variable)){
//			print "Incorrectly entered city.";
			return false;
		} else {
			return true;
		}	
	}

//	function check_address($form_variable){
//		if(!ereg("(^Box\ [0-9].+|^P\.O\.\ Box\ [0-9].+|^[0-9].+|^General\ Delivery|^Unit|^Suite)", $form_variable)){
//			print "Incorrectly entered address.";
//			return false;
//		} else {
//			return true;
//		}
//	}

	function check_address($form_variable){
		if(!ereg("^Box\ [0-9].*|^P\.O\.\ Box\ [0-9].*|^[0-9].+|^BOX\ [0-9].*|^P\.O\.\ BOX\ [0-9].*|^box\ [0-9].*|^p.o.\ box\ [0-9].*|^General Delivery|^Unit|^Suite|^Perimeter\ Hwy|^MM\ R.*|^Junction.*|^Site.*|^Hwy\..*|^RR.*|Road$|Rd\.$|RD$|rd$|RD\.$|rd\.$", $form_variable)){
			return false;
		} else {
			return true;
		}
	}

}

?>
