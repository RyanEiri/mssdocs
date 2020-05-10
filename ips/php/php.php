<?php

/*
 * gets the url for a given entity
 */
function get_full_url() {
	  $https = !empty($_SERVER['HTTPS']) && strcasecmp($_SERVER['HTTPS'], 'on') === 0 ||
	  !empty($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
	    strcasecmp($_SERVER['HTTP_X_FORWARDED_PROTO'], 'https') === 0;
	  return
	  ($https ? 'https://' : 'http://').
	  (!empty($_SERVER['REMOTE_USER']) ? $_SERVER['REMOTE_USER'].'@' : '').
	  (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : ($_SERVER['SERVER_NAME'].
	  ($https && $_SERVER['SERVER_PORT'] === 443 ||
	  $_SERVER['SERVER_PORT'] === 80 ? '' : ':'.$_SERVER['SERVER_PORT']))).
	  substr($_SERVER['SCRIPT_NAME'],0, strrpos($_SERVER['SCRIPT_NAME'], '/'));
}

/*
 * provides a key for sharing files
 * currently uses md5 but another hash type should be considered
 */
function keymaker($id){
  $secretkey='ZDVlYzBjNWNhNDExNTRjYWQyNmU4N2M1';
  $key=md5($id.$secretkey);
  return $key;
}

/*
 * Token generator functions.
 * Used for user secretword.
 * Need to replace keymaker function with this.
 */
function crypto_rand_secure($min, $max) {
     $range = $max - $min;
     if ($range < 1) return $min; // not so random...
     $log = ceil(log($range, 2));
     $bytes = (int) ($log / 8) + 1; // length in bytes
     $bits = (int) $log + 1; // length in bits
     $filter = (int) (1 << $bits) - 1; // set all lower bits to 1
     do {
         $rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
         $rnd = $rnd & $filter; // discard irrelevant bits
     } while ($rnd > $range);
     return $min + $rnd;
}

function getToken($length) {
    $token = "";
    $codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $codeAlphabet.= "abcdefghijklmnopqrstuvwxyz";
    $codeAlphabet.= "0123456789";
    $max = strlen($codeAlphabet); // edited

    for ($i=0; $i < $length; $i++) {
        $token .= $codeAlphabet[crypto_rand_secure(0, $max-1)];
    }

    return $token;
}

/*
 * Form valdiation Functions
 * test_input(): initial test for all input data
 */
function test_input($data) {
   $data = trim($data);
   $data = stripslashes($data);
   $data = htmlspecialchars($data);
   return $data;
}

/*
 * autorotate image based on exif data
 */
function autorotate(Imagick $image)
{
    switch ($image->getImageOrientation()) {
    case Imagick::ORIENTATION_TOPLEFT:
        break;
    case Imagick::ORIENTATION_TOPRIGHT:
        $image->flopImage();
        break;
    case Imagick::ORIENTATION_BOTTOMRIGHT:
        $image->rotateImage("#000", 180);
        break;
    case Imagick::ORIENTATION_BOTTOMLEFT:
        $image->flopImage();
        $image->rotateImage("#000", 180);
        break;
    case Imagick::ORIENTATION_LEFTTOP:
        $image->flopImage();
        $image->rotateImage("#000", -90);
        break;
    case Imagick::ORIENTATION_RIGHTTOP:
        $image->rotateImage("#000", 90);
        break;
    case Imagick::ORIENTATION_RIGHTBOTTOM:
        $image->flopImage();
        $image->rotateImage("#000", 90);
        break;
    case Imagick::ORIENTATION_LEFTBOTTOM:
        $image->rotateImage("#000", -90);
        break;
    default: // Invalid orientation
        break;
    }
    $image->setImageOrientation(Imagick::ORIENTATION_TOPLEFT);
    return $image;
}

/*
 * orders a multidimensional array on the base of a label-key
 *
 * @param $arr, the array to be ordered
 * @param $l the "label" identifying the field
 * @param $f the ordering function to be used,
 *    strnatcasecmp() by default
 * @return  TRUE on success, FALSE on failure.

function array_key_multi_sort(&$arr, $l , $f='strnatcasecmp') {
       return usort($arr, create_function('$a, $b', "return $f(\$a['$l'], \$b['$l']);"));
}
*/

/*
 * takes the contact's associative array returned by the mysql class
 * and converts it to an indexed array for sorting with the array_sort class
 * then reconverts it back to an associative array
 *
 * currently this function only works with the associative keys for the contacts
 * table, but this function could easily be extended to support any associative
 * array produced by the mysql class.
 *
 * currently only suitable for a 3 dimensional array.
 *
 *  @param $array, the array to be converted
 *  @param $sort_by, the associative key to sort by
 *  @param $sort_by_2, the second associative key to sort by

function sort_it_out($array, $sort_by, $sort_by_2) {
       foreach ($array as $key => $value) {
              $directory_array[$key][0]=$value[primary];
              $directory_array[$key][1]=$value[url];
              $directory_array[$key][2]=$value[urltitle];
              $directory_array[$key][3]=$value[featured];
              $directory_array[$key][4]=$value[short_desc];
              $directory_array[$key][5]=$value[long_desc];
              $directory_array[$key][6]=$value[primary_cat];
              $directory_array[$key][7]=$value[secondary_cat];
       }
       $sort_by_array = array(
				   'primary' => '0',
				   'url' => '1',
				   'urltitle' => '2',
				   'featured' => '3',
				   'short_desc' => '4',
				   'long_desc' => '5',
				   'primary_cat' => '6',
				   'secondary_cat' => '7',
			    );
       foreach($sort_by_array as $key => $value) {
	      if($key==$sort_by){
		     $sort_by_number = $value;
	      }
	      if($key==$sort_by_2){
		     $sort_by_number_2 = $value;
	      }
       }
       $sort_string = "<[".$sort_by_number."]>|<[".$sort_by_number_2."]><asc>";
       $directory_array = sort_array($directory_array, $sort_string);
       foreach ($directory_array as $key => $value) {
              $new_directory_array[$key][primary]=$value[0];
              $new_directory_array[$key][url]=$value[1];
              $new_directory_array[$key][urltitle]=$value[2];
              $new_directory_array[$key][featured]=$value[3];
              $new_directory_array[$key][short_desc]=$value[4];
              $new_directory_array[$key][long_desc]=$value[5];
              $new_directory_array[$key][primary_cat]=$value[6];
              $new_directory_array[$key][secondary_cat]=$value[7];
       }
       return $new_directory_array;
}
*/

/*
 * sorts an array using the array_key_multi_sort function
 * and divides it into pages
 * an array is returned including the chunked array and the appropriate key
 * for the requested page
 *
 * moving towards the use of Matthias Rothe's Advanced Array Sort Class,
 * and away from array_key_multi_sort function.
 *
 * @param $sort_field, the field to sort $_SESSION[contact_array] by

function pagisort($sort_field) {
	global $db;
	      $_SESSION[directory_object] = new DirectoryGrab($db, $sort_field);
	      $_SESSION[directory_array] = $_SESSION[directory_object]->sorted_entries;
	if($_SESSION[paginate]==1) {

		$chunkact_array = $_SESSION[directory_object]->chunks;

		$chunkact_array_size = $_SESSION[directory_object]->num_chunks;

		$_SESSION[number_of_pages] = range(1, $chunkact_array_size);
		if($_SESSION[page]) {
			$chunkact_key = $_SESSION[page];
		} else {
			$chunkact_key = 0;
		}
		if($chunkact_key == -1) {
			$chunkact_key = 0;
		}
		if($_SESSION[page] > $chunkact_array_size) {
			$chunkact_key = 0;
		}
		if($chunkact_key == 0) {
			$_SESSION[previous_page] = 1;
			$_SESSION[next_page] = ($chunkact_key + 2);
		} elseif($chunkact_key == ($chunkact_array_size - 1)) {
			$_SESSION[previous_page] = $chunkact_key;
			$_SESSION[next_page] = $chunkact_array_size;
		} else {
			$_SESSION[previous_page] = $chunkact_key;
			$_SESSION[next_page] = ($chunkact_key + 2);
		}
		$chunk = array($chunkact_array, $chunkact_key);
		$_SESSION[chunk] = $chunkact_array[$chunkact_key];
	}
}
*/

/*
 * case statement for sorting the contact_array session array.
 * no arguments are necessary. simply call the function and it
 * will sort the array by the sort session variable.

function sort_order() {
	if ($_SESSION[sort]) {
		switch ($_SESSION[sort]) {
		case 'name':
			if(is_array($_SESSION[contact_array])) {
				pagisort('customers_name');
			}
			break;
		case 'position':
			if(is_array($_SESSION[contact_array])) {
				pagisort('customers_position');
			}
			break;
		case 'organization':
			if(is_array($_SESSION[contact_array])) {
				pagisort('customers_organization');
			}
			break;
		case 'address_1':
			if(is_array($_SESSION[contact_array])) {
				pagisort('customers_address_1');
			}
			break;
		case 'address_2':
			if(is_array($_SESSION[contact_array])) {
				pagisort('customers_address_2');
			}
			break;
		case 'city':
			if(is_array($_SESSION[contact_array])) {
				pagisort('customers_city');
			}
			break;
		case 'province':
			if(is_array($_SESSION[contact_array])) {
				pagisort('customers_province');
			}
			break;
		case 'postal_code':
			if(is_array($_SESSION[contact_array])) {
				pagisort('customers_postal_code');
			}
			break;
		case 'calendar_n':
			if(is_array($_SESSION[contact_array])) {
				pagisort('calendar_n');
			}
			break;
		case 'calendar_a':
			if(is_array($_SESSION[contact_array])) {
				pagisort('calendar_a');
			}
			break;
		case 'catalog':
			if(is_array($_SESSION[contact_array])) {
				pagisort('catalog');
			}
			break;
		case 'vendor':
			if(is_array($_SESSION[contact_array])) {
				pagisort('vendor');
			}
			break;
		}
	} else {
		if(is_array($_SESSION[contact_array])) {
			array_key_multi_sort($_SESSION[contact_array], 'customers_name');
			$_SESSION[sort] = 'organization';
			if($_SESSION[paginate]==1) {
				pagisort('customers_organization');
			}
		}
	}
}
*/

/*
 * takes an array and splits 10 digit phone numbers into
 * first 3 digits, next 3 digits, last 4 digits
 * returns the new array

function split_phone($array) {
        $new_array[b_phone1] = substr($array[business_phone], 0, 3);
        $new_array[b_phone2] = substr($array[business_phone], 3, 3);
        $new_array[b_phone3] = substr($array[business_phone], 6, 4);
        $new_array[f_phone1] = substr($array[fax_phone], 0, 3);
        $new_array[f_phone2] = substr($array[fax_phone], 3, 3);
        $new_array[f_phone3] = substr($array[fax_phone], 6, 4);
        $new_array[h_phone1] = substr($array[home_phone], 0, 3);
        $new_array[h_phone2] = substr($array[home_phone], 3, 3);
        $new_array[h_phone3] = substr($array[home_phone], 6, 4);
        $new_array[c_phone1] = substr($array[cell_phone], 0, 3);
        $new_array[c_phone2] = substr($array[cell_phone], 3, 3);
        $new_array[c_phone3] = substr($array[cell_phone], 6, 4);
        $new_array[toll_free_phone1] = substr($array[toll_free_phone], 0, 3);
        $new_array[toll_free_phone2] = substr($array[toll_free_phone], 3, 3);
        $new_array[toll_free_phone3] = substr($array[toll_free_phone], 6, 4);
	return $new_array;
}
*/

?>
