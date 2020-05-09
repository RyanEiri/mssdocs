<?php
include $_SERVER['DOCUMENT_ROOT'].'/ips/php/boot.php';
session_start();
$login_cookie = new UserCookie();
$login_cookie->DeleteIt();
if($login_cookie->CheckIt()) {
	define("USERNAME", $login_cookie->username);
	$userval = new UserGrab(USERNAME);
	define("ADMIN_STATUS", $userval->admin);
// Initialize GET variables
if(!empty($_GET['file']) && ADMIN_STATUS){
	$file = $_GET['file'];
} else {
	$file = '0';
}
?>
<html lang="en">
<head>
<!-- Force latest IE rendering engine or ChromeFrame if installed -->
<!--[if IE]>
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<![endif]-->
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Fragile Heritage Project :: TEI:XML Parsing</title>
<meta name="author" content="Ryan Eric Johnson" >
<meta name="date" content="2018-07-24" >
<meta name="copyright" content="Fragile Heritage Project 2018" >
<meta name="keywords" content="manuscript, manuscripts, description, proofing" >
<meta name="description" content="The Fragile Heritage Project aims to create a digital collection of Icelandic language manuscripts held in public and private collections in Canada and the U.S.A." >
<meta http-equiv="expires" content="0" >

<!-- Bootstrap for CSS -->
<link rel="stylesheet" href="<?php echo PROGRAM_CSS_BASE ?>bootstrap/bootstrap.min.css">
<!-- Custom styles for this page -->
<link rel="stylesheet" href="<?php echo PROGRAM_CSS_BASE ?>bootstrap/dashboard.css">

<!-- Internet Explorer Tweaks -->
<!-- IE10 CSS Viewport Workaround -->
<link rel="stylesheet" href="<?php echo PROGRAM_CSS_BASE ?>ie10-viewport-bug-workaround.css">
    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>

<body>

<?php //include(HTML_TEMPLATES.'navbar.php'); ?>

		<!-- BEGIN NAV -->
    <nav class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0">
      <a class="navbar-brand col-sm-3 col-md-2 mr-0" href="<?php echo PROGRAM_WEB_BASE ?>xml/">XML Editor</a>
      <input class="form-control form-control-dark w-100" type="text" placeholder="Inactive Search Bar" aria-label="Search">
      <ul class="navbar-nav px-3">
        <li class="nav-item text-nowrap">
          <a class="nav-link" href="<?php echo PROGRAM_WEB_BASE ?>">Main</a>
        </li>
      </ul>
    </nav>

    <div class="container-fluid">
      <div class="row">
        <nav class="col-md-2 d-none d-md-block bg-light sidebar">
          <div class="sidebar-sticky">
            <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-4 mt-1 mb-1 text-muted">
              <span>XML Files</span>
              <a class="d-flex align-items-center text-muted" href="#">
                <span data-feather="plus-circle"></span>
              </a>
            </h6>
            <ul class="nav flex-column my-0">
              <li class="nav-item">
                <a class="nav-link" href="#allfiles">
                  <span data-feather="file-text"></span>
                  All files
                </a>
              </li>
            </ul>
						<h6 class="sidebar-heading d-flex justify-content-between align-items-center px-4 mt-3 mb-1 text-muted">
							<span>Arrays</span>
							<a class="d-flex align-items-center text-muted" href="#">
								<span data-feather="codepen"></span>
							</a>
						</h6>
						<ul class="nav flex-column my-0">
							<li class="nav-item"><a class="nav-link" href="#sex"><span data-feather="grid"></span>
								sex
							</a></li>
							<li class="nav-item"><a class="nav-link" href="#attributes"><span data-feather="grid"></span>
								attributes
							</a></li>							
							<li class="nav-item mt-0 mb-0"><a class="nav-link" href="#person"><span data-feather="grid"></span>
								person
							</a></li>
							<li class="nav-item"><a class="nav-link" href="#persName"><span data-feather="grid"></span>
								persName
							</a></li>
							<li class="nav-item"><a class="nav-link" href="#persName_forename"><span data-feather="grid"></span>
								persName_forename
							</a></li>
							<li class="nav-item"><a class="nav-link" href="#persName_surname"><span data-feather="grid"></span>
								persName_surname
							</a></li>
							<li class="nav-item"><a class="nav-link" href="#persName_addName"><span data-feather="grid"></span>
								persName_addName
							</a></li>
							<li class="nav-item"><a class="nav-link" href="#name"><span data-feather="grid"></span>
								name
							</a></li>
							<li class="nav-item"><a class="nav-link" href="#birth_name_attributes"><span data-feather="grid"></span>
								birth_name_attributes
							</a></li>
							<li class="nav-item"><a class="nav-link" href="#birth"><span data-feather="grid"></span>
								birth
							</a></li>
							<li class="nav-item"><a class="nav-link" href="#birth_name"><span data-feather="grid"></span>
								birth_name
							</a></li>
							<li class="nav-item"><a class="nav-link" href="#death"><span data-feather="grid"></span>
								death
							</a></li>
							<li class="nav-item"><a class="nav-link" href="#placeName"><span data-feather="grid"></span>
								placeName
							</a></li>
							<li class="nav-item"><a class="nav-link" href="#residence"><span data-feather="grid"></span>
								residence
							</a></li>
							<li class="nav-item"><a class="nav-link" href="#occupation"><span data-feather="grid"></span>
								occupation
							</a></li>
							<li class="nav-item"><a class="nav-link" href="#education"><span data-feather="grid"></span>
								education
							</a></li>
							<li class="nav-item"><a class="nav-link" href="#affiliation"><span data-feather="grid"></span>
								affiliation
							</a></li>
							<li class="nav-item"><a class="nav-link" href="#state"><span data-feather="grid"></span>
								state
							</a></li>
							<li class="nav-item"><a class="nav-link" href="#state_attributes"><span data-feather="grid"></span>
								state_attributes
							</a></li>
							<li class="nav-item"><a class="nav-link" href="#event"><span data-feather="grid"></span>
								event
							</a></li>
							<li class="nav-item"><a class="nav-link" href="#event_attributes"><span data-feather="grid"></span>
								event_attributes
							</a></li>
							<li class="nav-item"><a class="nav-link" href="#note"><span data-feather="grid"></span>
								note
							</a></li>
							<li class="nav-item"><a class="nav-link" href="#bibl_ref"><span data-feather="grid"></span>
								bibl_ref
							</a></li>
							<li class="nav-item"><a class="nav-link" href="#bibl_biblScope"><span data-feather="grid"></span>
								bibl_biblScope
							</a></li>
							<li class="nav-item"><a class="nav-link" href="#bibl"><span data-feather="grid"></span>
								bibl
							</a></li>
          </div>
        </nav>
				<!-- END NAV -->

				<main role="main" class="col-md-10 ml-auto col-lg-10 pt-3 px-4">

<?php
	// Declare functions
	
	// This function formulates the $person array
	// a node at a time. 
	function sxiToArray($sxi){
  	$a = array();
  	for( $sxi->rewind(); $sxi->valid(); $sxi->next() ) {
			// Grab the initial attributes table for the person
			if($sxi->attributes() && $sxi->hasChildren()){
				$a['attributes'] = (array)$sxi->attributes();
				$a['attributes'] = $a['attributes']['@attributes'];
			}
			// Get node attributes from xml namespace. 
			// This appears to grab all of the needed xml namespace attributes
			// needed thus far, including what should be child nodes. 
			if($sxi->attributes('xml', TRUE)){
				$attributes = (array)$sxi->attributes('xml', TRUE);
				$a['attributes']['xml'] = $attributes['@attributes'];
			}
  		if(!array_key_exists($sxi->key(), $a)){
  			$a[$sxi->key()] = array();
			}		
			// Get current node attributes from default namespace
			if($sxi->current()->attributes()){
				$attributes = (array)$sxi->current()->attributes();
				$a[$sxi->key()][]['attributes'] = $attributes['@attributes'];
			}
  		if($sxi->hasChildren()){
				if(strval($sxi->current())){
					$xml_string = $sxi->current()->asXML();
					$xml_string = preg_replace('/(^<[^>]*>|<\/[^>]*>$)/', '', $xml_string);
					$a[$sxi->key()][]['xml_string'] = html_entity_decode($xml_string, ENT_NOQUOTES, 'UTF-8');
				}
				$a[$sxi->key()][] = sxiToArray($sxi->current());	
			} else {
				if($sxi->current()->attributes()){
					end($a[$sxi->key()]);
      		$a[$sxi->key()][key($a[$sxi->key()])]['string'] = strval($sxi->current());
				} else {
					$a[$sxi->key()]['string'] = strval($sxi->current());
				}
    	}
  	}
  	return $a;
	}
	
	// function to test for a subsequent array key
	// This function returns true if key exists and
	// also has a false value. The next() function,
	// on the other hand, returns both false if the 
	// key exists and when the key has a false value. 
	function has_next(array $_array) {
  	return next($_array) !== false ?: key($_array) !== null;
	}
	
	// search array for values based on key and value
	// used for searching for duplicates in array
	function search($array, $key, $value) {
    $results = array();

    if (is_array($array)) {
        if (isset($array[$key]) && $array[$key] == $value) {
            $results[] = $array;
        }
        foreach ($array as $subarray) {
            $results = array_merge($results, search($subarray, $key, $value));
        }
    }
    return $results;
	}
	
	// This function populates multiple arrays from peopleArray
	// upon completion of the array, i.e. after while loop. These arrays 
	// can be used to form MySQL entries.
	function table_cell($data) {
		
		// Initialize arrays.
		$attributes = array();
		$person_table = array();
		$persName = array();
		$persName_forename = array();
		$persName_surname = array();
		$persName_addName = array();
		$birth = array();
		$birth_name = array();
		$death = array();
		$placeName = array();
		$residence = array();
		$occupation = array();
		$education = array();
		$affiliation = array();
		$state = array();
		$event = array();
		$note = array();
		$bibl_ref = array();
		$bibl_biblScope = array();
		$bibl = array();
		
		// Initialize indices for arrays.
		$attributes_index = 0;
		$persName_index = 0;
		$persName_forename_index = 0;
		$persName_surname_index = 0;
		$persName_addName_index = 0;
		$birth_index = 0;
		$birth_name_index = 0;
		$death_index = 0;
		$placeName_index = 0;
		$residence_index = 0;
		$occupation_index = 0;
		$education_index = 0;
		$affiliation_index = 0;
		$state_index = 0;
		$event_index = 0;
		$note_index = 0;
		$bibl_ref_index = 0;
		$bibl_biblScope_index = 0;
		$bibl_index = 0;
		
		// Initialize return string. 
		$return = "";

		// Form our arrays for the HTML tables. 
  	foreach ($data as $key => $value) {
			foreach ($value as $person_key => $person) {
				$primary_id = $person_key;				
				$person_table[$person_key]['primary_id'] = $primary_id;
				$person_table[$person_key]['xml_id'] = NULL;
				$person_table[$person_key]['sex'] = NULL;
				
				/*if(!empty($person['attributes'])){
					$return .= "<h3>".$person['attributes']['xml']['id']."</h3>";
				} else {
					$return .= "<h3>No person_attributes array</h3>";
				}*/
				
				foreach ($person as $table_key => $table) {
					if(!array_key_exists('attributes', $person)){
						$xml_id = NULL;
						$sex = NULL;
					}
					switch($table_key) {
						case 'attributes':
							foreach($table as $attribute_table_key => $attribute_table_value){
								if($attribute_table_key === 'xml'){
									$namespace = 'xml';
									$attribute = key($attribute_table_value);
									$attribute_value = $attribute_table_value[$attribute];
								} else {
									$namespace = 'default';
									$attribute = $attribute_table_key;
									$attribute_value = $attribute_table_value;
								}
								$duplicate_attribute = search($attributes, 'attribute', $attribute);
								$duplicate_attribute_and_value = search($duplicate_attribute, 'value', $attribute_value);
								if(empty($duplicate_attribute_and_value)){
									$attributes[$attributes_index]['attribute_id'] = $attributes_index;
									$attributes[$attributes_index]['namespace'] = $namespace;
									$attributes[$attributes_index]['attribute'] = $attribute;
									$attributes[$attributes_index]['value'] = $attribute_value;	
									$attributes_index++;									
								} else {
									//var_export($duplicate_attribute_and_value);
								}
							}
							$xml_id = $table['xml']['id'];
							$sex = $table['sex'];
							$person_table[$person_key]['xml_id'] = $xml_id;
							$person_table[$person_key]['sex'] = $sex;
							
							/*$return .= "<h4>attributes</h4>";
							$return .= "<table border='1'>";
							$return .= "<td>".var_export($table, true)."</td>";
							$return .= "</table>";*/
							
							break;
						case 'persName':
							
							/*$return .= "<h4>persName</h4>";
							$return .= "<table border='1'><tr>";
							$return .= "<td>".var_export($table, true)."</td>";
							$return .= "</table>";*/
							
							foreach ($table as $sub_table_key => $sub_table) {
								$persName[$persName_index]['persName_id'] = $persName_index;
								$persName[$persName_index]['xml_id'] = $xml_id;
								$persName[$persName_index]['primary_id'] = $primary_id;
								$return .= "<tr>";
								foreach ($sub_table as $sub_sub_table_key => $sub_sub_table) {								
									switch($sub_sub_table_key){
										case 'attributes':
											if(key($sub_sub_table) === 'xml'){
												$namespace = 'xml';
												$attribute = key($sub_sub_table[$namespace]);
												$attribute_value = $sub_sub_table[$namespace][$attribute];
											} else {
												$namespace = 'default';
												$attribute = key($sub_sub_table);
												$attribute_value = $sub_sub_table[$attribute];
											}
											$duplicate_attribute = search($attributes, 'attribute', $attribute);
											$duplicate_attribute_and_value = search($duplicate_attribute, 'value', $attribute_value);
											if(empty($duplicate_attribute_and_value)){
												$attributes[$attributes_index]['attribute_id'] = $attributes_index;
												$attributes[$attributes_index]['namespace'] = $namespace;
												$attributes[$attributes_index]['attribute'] = $attribute;
												$attributes[$attributes_index]['value'] = $attribute_value;	
												$persName[$persName_index]['attribute_id'] = $attributes_index;
												$attributes_index++;									
											} else {
												$persName[$persName_index]['attribute_id'] = $duplicate_attribute_and_value[0]['attribute_id'];
											}
											break;
										case 'forename':
											foreach ($sub_sub_table as $key => $forename) {
												if($forename['attributes']){
													$namespace = 'default';
													$attribute = key($forename['attributes']);
													$attribute_value = $forename['attributes'][$attribute];
													$duplicate_attribute = search($attributes, 'attribute', $attribute);
													$duplicate_attribute_and_value = search($duplicate_attribute, 'value', $attribute_value);
													if(empty($duplicate_attribute_and_value)){
														$attributes[$attributes_index]['attribute_id'] = $attributes_index;
														$attributes[$attributes_index]['namespace'] = $namespace;
														$attributes[$attributes_index]['attribute'] = $attribute;
														$attributes[$attributes_index]['value'] = $attribute_value;
														$persName_forename[$persName_forename_index]['attribute_id'] = $attributes_index;
														$attributes_index++;									
													} else {
														$persName_forename[$persName_forename_index]['attribute_id'] = $duplicate_attribute_and_value[0]['attribute_id'];
													}
												}
												$persName_forename[$persName_forename_index]['forename'] = $forename['string'];
												$persName_forename[$persName_forename_index]['persName_id'] = $persName_index;
												$persName_forename_index++;
											}
											break;
										case 'surname':
											foreach ($sub_sub_table as $key => $surname) {
												if($surname['attributes']){
													$namespace = 'default';
													$attribute = key($surname['attributes']);
													$attribute_value = $surname['attributes'][$attribute];
													$duplicate_attribute = search($attributes, 'attribute', $attribute);
													$duplicate_attribute_and_value = search($duplicate_attribute, 'value', $attribute_value);
													if(empty($duplicate_attribute_and_value)){
														$attributes[$attributes_index]['attribute_id'] = $attributes_index;
														$attributes[$attributes_index]['namespace'] = $namespace;
														$attributes[$attributes_index]['attribute'] = $attribute;
														$attributes[$attributes_index]['value'] = $attribute_value;
														$persName_surname[$persName_surname_index]['attribute_id'] = $attributes_index;
														$attributes_index++;
													} else {
														$persName_surname[$persName_surname_index]['attribute_id'] = $duplicate_attribute_and_value[0]['attribute_id'];
													}
												}
												$persName_surname[$persName_surname_index]['surname'] = $surname['string'];
												$persName_surname[$persName_surname_index]['persName_id'] = $persName_index;
												$persName_surname_index++;
											}
											break;
										case 'addName':
											foreach ($sub_sub_table as $key => $addName) {
												if($addName['attributes']){
													$attribute = key($addName['attributes']);
													$attribute_value = $addName['attributes'][$attribute];
													$namespace = 'default';
													$duplicate_attribute = search($attributes, 'attribute', $attribute);
													$duplicate_attribute_and_value = search($duplicate_attribute, 'value', $attribute_value);
													if(empty($duplicate_attribute_and_value)){
														$attributes[$attributes_index]['attribute_id'] = $attributes_index;
														$attributes[$attributes_index]['namespace'] = $namespace;
														$attributes[$attributes_index]['attribute'] = $attribute;
														$attributes[$attributes_index]['value'] = $attribute_value;
														$persName_addName[$persName_addName_index]['attribute_id'] = $attributes_index;
														$attributes_index++;
													} else {
														$persName_addName[$persName_addName_index]['attribute_id'] = $duplicate_attribute_and_value[0]['attribute_id'];
													}
												}
												if(!empty($addName['string'])){
													$persName_addName[$persName_addName_index]['addName'] = $addName['string'];
												} else {
													$persName_addName[$persName_addName_index]['addName'] = NULL;
												}
												$persName_addName[$persName_addName_index]['persName_id'] = $persName_index;
												$persName_addName_index++;
											}
									}
								}
								$persName_index++;
							}
							break;
						case 'birth':
							
							/*$return .= "<h4>birth</h4>";
							$return .= "<table border='1'><tr>";
							$return .= "<td>".var_export($table, true)."</td>";
							$return .= "</table>";*/
							
							foreach ($table as $sub_table_key => $sub_table) {
								foreach ($sub_table as $sub_sub_table_key => $sub_sub_table) {
									$birth[$birth_index]['xml_id'] = $xml_id;
									$birth[$birth_index]['primary_id'] = $primary_id;										
									switch($sub_sub_table_key){										
										case 'attributes':
											if(key($sub_sub_table) === 'xml'){
												$namespace = 'xml';
												$attribute = key($sub_sub_table[$namespace]);
												$attribute_value = $sub_sub_table[$namespace][$attribute];
											} else {
												$namespace = 'default';
												$attribute = key($sub_sub_table);
												$attribute_value = $sub_sub_table[$attribute];
											}
											$duplicate_attribute = search($attributes, 'attribute', $attribute);
											$duplicate_attribute_and_value = search($duplicate_attribute, 'value', $attribute_value);
											if(empty($duplicate_attribute_and_value)){
												$attributes[$attributes_index]['attribute_id'] = $attributes_index;
												$attributes[$attributes_index]['namespace'] = $namespace;
												$attributes[$attributes_index]['attribute'] = $attribute;
												$attributes[$attributes_index]['value'] = $attribute_value;	
												$birth[$birth_index]['attribute_id'] = $attributes_index;
												$attributes_index++;									
											} else {
												$birth[$birth_index]['attribute_id'] = $duplicate_attribute_and_value[0]['attribute_id'];
											}
											break;
										case 'xml_string':
											$birth[$birth_index]['value'] = $sub_sub_table;
											break;
										case 'name':
											$name_index = 0;
											foreach ($sub_sub_table as $name_key => $name){
												$duplicate_name = search($birth_name, 'value', $name['string']);
												if(empty($duplicate_name)){
													if($name['attributes']){
														foreach($name['attributes'] as $placename_attribute_key => $placename_attribute_value){
															$attribute = $placename_attribute_key;
															$attribute_value = $placename_attribute_value;
															$namespace = 'default';
															$duplicate_attribute = search($attributes, 'attribute', $attribute);
															$duplicate_attribute_and_value = search($duplicate_attribute, 'value', $attribute_value);
															if(empty($duplicate_attribute_and_value)){
																$attributes[$attributes_index]['attribute_id'] = $attributes_index;
																$attributes[$attributes_index]['namespace'] = $namespace;
																$attributes[$attributes_index]['attribute'] = $attribute;
																$attributes[$attributes_index]['value'] = $attribute_value;
																$birth_name[$birth_name_index]['attributes'][$placename_attribute_key] = $attributes_index;
																$attributes_index++;
															} else {
																$birth_name[$birth_name_index]['attributes'][$placename_attribute_key] = $duplicate_attribute_and_value[0]['attribute_id'];
															}
														}
													} 
													if($name['string']){
														$birth_name[$birth_name_index]['value'] = $name['string'];
														$birth_name[$birth_name_index]['name_id'] = $birth_name_index;
														$birth[$birth_index]['name_id'][$name_index] = $birth_name_index;
														$birth_name_index++;
														$name_index++;
													}
												} else {
													$birth[$birth_index]['name_id'][$name_index] = $duplicate_name[0]['name_id'];
													$name_index++;
												}
											}
										$birth_index++;
										break;
									}
								}
							}
							break;
						case 'death':
							
							/*$return .= "<h4>death</h4>";
							$return .= "<table border='1'><tr>";
							$return .= "<td>".var_export($table, true)."</td>";
							$return .= "</table>";*/
							
							foreach ($table as $sub_table) {
								$death[$death_index]['primary_id'] = $primary_id;
								$death[$death_index]['xml_id'] = $xml_id;
								if(is_array($sub_table)){
									foreach($sub_table as $key => $value){
										switch($key){
											case 'attributes':
												foreach($value as $attribute_key => $attribute_core){
													if($attribute_key === 'xml'){
														$namespace = 'xml';
														$attribute = $attribute_core[$namespace];
														$attribute_value = $attribute_core[$namespace][$attribute];
													} else {
														$namespace = 'default';
														$attribute = $attribute_key;
														$attribute_value = $attribute_core;
													}
													$duplicate_attribute = search($attributes, 'attribute', $attribute);
													$duplicate_attribute_and_value = search($duplicate_attribute, 'value', $attribute_value);
													if(empty($duplicate_attribute_and_value)){
														$attributes[$attributes_index]['attribute_id'] = $attributes_index;
														$attributes[$attributes_index]['namespace'] = $namespace;
														$attributes[$attributes_index]['attribute'] = $attribute;
														$attributes[$attributes_index]['value'] = $attribute_value;	
														$death[$death_index]['attribute_id'] = $attributes_index;
														$attributes_index++;									
													} else {
														$death[$death_index]['attribute_id'] = $duplicate_attribute_and_value[0]['attribute_id'];
													}
												}
												break;
											case 'string':
												$death[$death_index]['string'] = $value;
										}
									}
								} else {
									$death[$death_index]['attribute_id'] = NULL;
									$death[$death_index]['string'] = NULL;
								}
								$death_index++;
								
							}
							break;
						case 'residence':
							
							/*$return .= "<h4>residence</h4>";
							$return .= "<table border='1'><tr>";
							$return .= "<td>".var_export($table, true)."</td>";
							$return .= "</table>";*/
							
							foreach ($table as $sub_table_key => $sub_table) {
								foreach ($sub_table as $sub_sub_table_key => $sub_sub_table) {
									$residence[$residence_index]['primary_id'] = $primary_id;
									$residence[$residence_index]['xml_id'] = $xml_id;
									switch($sub_sub_table_key){
										case 'placeName':
											foreach ($sub_sub_table as $placeName_table_key => $placeName_table){
												foreach ($placeName_table as $place_key => $place_table){
													foreach ($place_table as $place){
														if(isset($place['attributes'])){
															if(key($place['attributes']) === 'xml'){
																$namespace = 'xml';
																$attribute = key($place['attributes'][$namespace]);
																$attribute_value = $place['attributes'][$namespace][$attribute];
															} else {
																$namespace = 'default';
																$attribute = key($place['attributes']);
																$attribute_value = $place['attributes'][$attribute];
															}
															$duplicate_attribute = search($attributes, 'attribute', $attribute);
															$duplicate_attribute_and_value = search($duplicate_attribute, 'value', $attribute_value);
															if(empty($duplicate_attribute_and_value)){
																$attributes[$attributes_index]['attribute_id'] = $attributes_index;
																$attributes[$attributes_index]['namespace'] = $namespace;
																$attributes[$attributes_index]['attribute'] = $attribute;
																$attributes[$attributes_index]['value'] = $attribute_value;	
																$placeName[$placeName_index]['attribute_id'] = $attributes_index;
																$attributes_index++;									
															} else {
																$placeName[$placeName_index]['attribute_id'] = $duplicate_attribute_and_value[0]['attribute_id'];
															}
														} 
														if(isset($place['string'])){
															$placeName[$placeName_index]['placeName_node'] = $place_key;
															$placeName[$placeName_index]['placeName_string'] = $place['string'];
															$residence[$residence_index]['placeName_id'][$placeName_index] = $placeName_index;
															$placeName_index++;
														}
													}
												}
											}
									}
									$residence_index++;
								}
							}

							break;
						case 'occupation':

							/*$return .= "<h4>occupation</h4>";
							$return .= "<table border='1'><tr>";
							$return .= "<td>".var_export($table, true)."</td>";
							$return .= "</table>";*/

							foreach ($table as $sub_table_key => $sub_table_value) {
								$occupation[$occupation_index]['primary_id'] = $primary_id;
								$occupation[$occupation_index]['xml_id'] = $xml_id;
								switch($sub_table_key){
									case 'string':
										if(!empty($sub_table_value)){
											$occupation[$occupation_index]['string'] = $sub_table_value;
										} else {
											$occupation[$occupation_index]['string'] = NULL;
										}
										$occupation_index++;
										break;
								}
							}
							break;
						case 'education':

							/*$return .= "<h4>education</h4>";
							$return .= "<table border='1'><tr>";
							$return .= "<td>".var_export($table, true)."</td>";
							$return .= "</table>";*/

							foreach ($table as $sub_table_key => $sub_table_value) {
								$education[$education_index]['primary_id'] = $primary_id;
								$education[$education_index]['xml_id'] = $xml_id;
								if(is_array($sub_table_value)){
								foreach ($sub_table_value as $education_table_key => $education_table_value) {
									switch($education_table_key){
										case 'attributes':
											foreach($education_table_value as $attribute_key => $attribute_core){
												if($attribute_key === 'xml'){
													$namespace = 'xml';
													$attribute = $attribute_core[$namespace];
													$attribute_value = $attribute_core[$namespace][$attribute];
												} else {
													$namespace = 'default';
													$attribute = $attribute_key;
													$attribute_value = $attribute_core;
												}
												$duplicate_attribute = search($attributes, 'attribute', $attribute);
												$duplicate_attribute_and_value = search($duplicate_attribute, 'value', $attribute_value);
												if(empty($duplicate_attribute_and_value)){
													$attributes[$attributes_index]['attribute_id'] = $attributes_index;
													$attributes[$attributes_index]['namespace'] = $namespace;
													$attributes[$attributes_index]['attribute'] = $attribute;
													$attributes[$attributes_index]['value'] = $attribute_value;	
													$education[$education_index]['attribute_id'] = $attributes_index;
													$attributes_index++;									
												} else {
													$education[$education_index]['attribute_id'] = $duplicate_attribute_and_value[0]['attribute_id'];
												}
											}
											break;										
										case 'string':
											if(!is_array($education_table_value)){
												$education[$education_index]['string'] = $education_table_value;
											}
											if(has_next($table)){
												$education_index++;
											}
											break;
									}
								}
								}
								switch($sub_table_key){
									case 'string':
										if(!is_array($sub_table_value)){
											$education[$education_index]['string'] = $sub_table_value;
										}
										if(has_next($table)){
												$education_index++;
										}
										break;
								}
								$education_index++;
							}
							break;							
						case 'affiliation':
							
							/*$return .= "<h4>affiliation</h4>";
							$return .= "<table border='1'><tr>";
							$return .= "<td>".var_export($table, true)."</td>";
							$return .= "</table>";*/

							foreach ($table as $sub_table_key => $sub_table) {
								$affiliation[$affiliation_index]['primary_id'] = $primary_id;
								$affiliation[$affiliation_index]['xml_id'] = $xml_id;								
								if(is_array($sub_table)){
									foreach ($sub_table as $key => $value){
										switch($key){
											case 'attributes':
												foreach($value as $attribute_key => $attribute_core){
													if($attribute_key === 'xml'){
														$namespace = 'xml';
														$attribute = $attribute_core[$namespace];
														$attribute_value = $attribute_core[$namespace][$attribute];
													} else {
														$namespace = 'default';
														$attribute = $attribute_key;
														$attribute_value = $attribute_core;
													}
													$duplicate_attribute = search($attributes, 'attribute', $attribute);
													$duplicate_attribute_and_value = search($duplicate_attribute, 'value', $attribute_value);
													if(empty($duplicate_attribute_and_value)){
														$attributes[$attributes_index]['attribute_id'] = $attributes_index;
														$attributes[$attributes_index]['namespace'] = $namespace;
														$attributes[$attributes_index]['attribute'] = $attribute;
														$attributes[$attributes_index]['value'] = $attribute_value;	
														$affiliation[$affiliation_index]['attribute_id'] = $attributes_index;
														$attributes_index++;									
													} else {
														$affiliation[$affiliation_index]['attribute_id'] = $duplicate_attribute_and_value[0]['attribute_id'];
													}													
												}											
												break;
											case 'string':
												$affiliation[$affiliation_index]['string'] = $value;
												break;
										}
									}
									$affiliation_index++;
								}
							}
							break;
						case 'state':
							
							/*$return .= "<h4>state</h4>";
							$return .= "<table border='1'><tr>";
							$return .= "<td>".var_export($table, true)."</td>";
							$return .= "</table>";*/

							foreach ($table as $sub_table_key => $sub_table) {
								$state[$state_index]['primary_id'] = $primary_id;
								$state[$state_index]['xml_id'] = $xml_id;								
								if(is_array($sub_table)){								
									foreach ($sub_table as $key => $value){
										switch($key){
											case 'attributes':
												foreach($value as $attribute_key => $attribute_core){
													if($attribute_key === 'xml'){
														$namespace = 'xml';
														$attribute = $attribute_core[$namespace];
														$attribute_value = $attribute_core[$namespace][$attribute];
													} else {
														$namespace = 'default';
														$attribute = $attribute_key;
														$attribute_value = $attribute_core;
													}
													$duplicate_attribute = search($attributes, 'attribute', $attribute);
													$duplicate_attribute_and_value = search($duplicate_attribute, 'value', $attribute_value);
													if(empty($duplicate_attribute_and_value)){
														$attributes[$attributes_index]['attribute_id'] = $attributes_index;
														$attributes[$attributes_index]['namespace'] = $namespace;
														$attributes[$attributes_index]['attribute'] = $attribute;
														$attributes[$attributes_index]['value'] = $attribute_value;	
														$state[$state_index]['attributes'][$attribute] = $attributes_index;
														$attributes_index++;									
													} else {
														$state[$state_index]['attributes'][$attribute] = $duplicate_attribute_and_value[0]['attribute_id'];
													}													
												}
												break;
											case 'label':
												$state[$state_index]['label'] = $value['string'];
												if(!isset($sub_table['note'])){
													$state_index++;
												}
												break;
											case 'note':
												$state[$state_index]['note'] = $value['string'];
												$state_index++;
												break;
										}
									}
								}
							}				
							break;
						case 'event':
							
							/*$return .= "<h4>event</h4>";
							$return .= "<table border='1'><tr>";
							$return .= "<td>".var_export($table, true)."</td>";
							$return .= "</table>";*/
							
							foreach ($table as $sub_table_key => $sub_table) {
								$event[$event_index]['primary_id'] = $primary_id;
								$event[$event_index]['xml_id'] = $xml_id;
								foreach ($sub_table as $sub_sub_table_key => $sub_sub_table) {
									switch($sub_sub_table_key){
										case 'attributes':
											end($attributes);
											if($sub_sub_table !== current($attributes)){
												foreach($sub_sub_table as $attribute_key => $attribute_core){
													if($attribute_key === 'xml'){
														$namespace = 'xml';
														$attribute = $attribute_core[$namespace];
														$attribute_value = $attribute_core[$namespace][$attribute];
													} else {
														$namespace = 'default';
														$attribute = $attribute_key;
														$attribute_value = $attribute_core;
													}
													$duplicate_attribute = search($attributes, 'attribute', $attribute);
													$duplicate_attribute_and_value = search($duplicate_attribute, 'value', $attribute_value);
													if(empty($duplicate_attribute_and_value)){
														$attributes[$attributes_index]['attribute_id'] = $attributes_index;
														$attributes[$attributes_index]['namespace'] = $namespace;
														$attributes[$attributes_index]['attribute'] = $attribute;
														$attributes[$attributes_index]['value'] = $attribute_value;	
														$event[$event_index]['attributes'][$attribute] = $attributes_index;
														$attributes_index++;									
													} else {
														$event[$event_index]['attributes'][$attribute] = $duplicate_attribute_and_value[0]['attribute_id'];
													}													
												}												
											}
											break;
										case 'p':
											$event[$event_index]['p'] = $sub_sub_table;
											$event_index++;
											break;
									}
								}
							}
							break;
						case 'note':
							
							/*$return .= "<h4>note</h4>";
							$return .= "<table border='1'><tr>";
							$return .= "<td>".var_export($table, true)."</td>";
							$return .= "</table>";*/

							foreach ($table as $sub_table_key => $sub_table_value) {
								$note[$note_index]['primary_id'] = $primary_id;
								$note[$note_index]['xml_id'] = $xml_id;								
								switch($sub_table_key){
									case 'string':
										$note[$note_index]['string'] = $sub_table_value;
										$note_index++;										
										break;
								}
							}
							break;
						case 'bibl':
							
							/*$return .= "<h4>bibl</h4>";
							$return .= "<table border='1'><tr>";
							$return .= "<td>".var_export($table, true)."</td>";
							$return .= "</table>";*/
							
							foreach ($table as $sub_table_key => $sub_table) {
								foreach ($sub_table as $sub_sub_table_key => $sub_sub_table) {
									$bibl[$bibl_index]['primary_id'] = $primary_id;
									$bibl[$bibl_index]['xml_id'] = $xml_id;										
									switch($sub_sub_table_key){
										case 'ref':
											//if(is_array($sub_sub_table)){
											foreach ($sub_sub_table as $ref_key => $ref_value) {
												foreach ($ref_value as $key => $value) {
													switch($key){
														case 'attributes':
															foreach($value as $attribute_key => $attribute_core){
																if($attribute_key === 'xml'){
																	$namespace = 'xml';
																	$attribute = $attribute_core[$namespace];
																	$attribute_value = $attribute_core[$namespace][$attribute];
																} else {
																	$namespace = 'default';
																	$attribute = $attribute_key;
																	$attribute_value = $attribute_core;
																}
																$duplicate_attribute = search($attributes, 'attribute', $attribute);
																$duplicate_attribute_and_value = search($duplicate_attribute, 'value', $attribute_value);
																if(empty($duplicate_attribute_and_value)){
																	$attributes[$attributes_index]['attribute_id'] = $attributes_index;
																	$attributes[$attributes_index]['namespace'] = $namespace;
																	$attributes[$attributes_index]['attribute'] = $attribute;
																	$attributes[$attributes_index]['value'] = $attribute_value;	
																	$bibl_ref[$bibl_ref_index]['attribute_id'] = $attributes_index;
																	$attributes_index++;									
																} else {
																	$bibl_ref[$bibl_ref_index]['attribute_id'] = $duplicate_attribute_and_value[0]['attribute_id'];
																}																
															}
															break;
														case 'string':
															$bibl_ref[$bibl_ref_index]['string'] = $value;
															break;														
													}
												}
												$bibl[$bibl_index]['ref_id'] = 	$bibl_ref_index;
												$bibl_ref[$bibl_ref_index]['bibl_id'] = $bibl_index;
												$bibl_ref_index++;
											}
											//}
											break;
										case 'biblScope':
											foreach ($sub_sub_table as $biblScope_key => $biblScope_value) {
												foreach ($biblScope_value as $key => $value) {
													switch($key){
														case 'attributes':
															foreach($value as $attribute_key => $attribute_core){
																if($attribute_key === 'xml'){
																	$namespace = 'xml';
																	$attribute = $attribute_core[$namespace];
																	$attribute_value = $attribute_core[$namespace][$attribute];
																} else {
																	$namespace = 'default';
																	$attribute = $attribute_key;
																	$attribute_value = $attribute_core;
																}
																$duplicate_attribute = search($attributes, 'attribute', $attribute);
																$duplicate_attribute_and_value = search($duplicate_attribute, 'value', $attribute_value);
																if(empty($duplicate_attribute_and_value)){
																	$attributes[$attributes_index]['attribute_id'] = $attributes_index;
																	$attributes[$attributes_index]['namespace'] = $namespace;
																	$attributes[$attributes_index]['attribute'] = $attribute;
																	$attributes[$attributes_index]['value'] = $attribute_value;	
																	$bibl_biblScope[$bibl_biblScope_index]['attribute_id'] = $attributes_index;
																	$attributes_index++;									
																} else {
																	$bibl_biblScope[$bibl_biblScope_index]['attribute_id'] = $duplicate_attribute_and_value[0]['attribute_id'];
																}
														}
														break;
													case 'string':
														$bibl_biblScope[$bibl_biblScope_index]['string'] = $value;
														break;														
													}
												}
												$bibl[$bibl_index]['biblScope_id'] = 	$bibl_biblScope_index;
												$bibl_biblScope[$bibl_biblScope_index]['bibl_id'] = $bibl_index;
												$bibl_biblScope_index++;
											}
											break;
									}
								}
								$bibl_index++;
							}
							break;
					}
				}
  		}
			
			// BEGIN DATABASE
			$return .= "<h2>Database to be Created</h2>";
			$return .= "<div id='database' class='table-responsive database_manage px-1 py-1'>";
			$return .= "<h4>database&nbsp;<button type='button' id='databaseCreate' class='btn btn-primary btn-sm mx-1'>Create Database</button></h4>Database name: mih";			
			$return .= "<hr>";

			// BEGIN TABLES
			$return .= "<h3>Tables to be Created</h3>";
			$return .= "<div id='sex' class='table-responsive sex_manage px-1 py-1'>";
			$return .= "<h4>sex&nbsp;<button type='button' id='sexTableCreate' class='btn btn-primary btn-sm mx-1 my-1'>Create sex Table</button><button type='button' id='sexTableDrop' class='btn btn-danger btn-sm mx-1 my-1'>Drop sex Table</button><button type='button' id='sexTableInsert' class='btn btn-success btn-sm mx-1 my-1'>Insert sex Entries</button></h4>";
			$_SESSION['sex'] = array();
			$return .= "<table class='table table-striped table-sm'>";
			$return .= "<tr><th>sex_id</th><th>gender</th><th>code</th></tr>";
			$_SESSION['sex'][0]['sex_id'] = 0; 
			$_SESSION['sex'][0]['gender'] = 'male';
			$_SESSION['sex'][0]['code'] = 1;
			$_SESSION['sex'][1]['sex_id'] = 1; 
			$_SESSION['sex'][1]['gender'] = 'female';
			$_SESSION['sex'][1]['code'] = 2;
			foreach($_SESSION['sex'] as $sex){
				$return .= "<tr><td>".$sex['sex_id']."</td><td>".$sex['gender']."</td><td>".$sex['code']."</td></tr>";
			}
			$return .= "</table>";
			$return .= "</div>";
			
			$return .= "<div id='attributes' class='table-responsive attributes_manage'>";
			$return .= "<h4>attributes&nbsp;<button type='button' id='attributesTableCreate' class='btn btn-primary btn-sm mx-1 my-1'>Create attributes Table</button><button type='button' id='attributesTableDrop' class='btn btn-danger btn-sm mx-1 my-1'>Drop attributes Table</button><button type='button' id='attributesTableInsert' class='btn btn-success btn-sm mx-1 my-1'>Insert attributes Entries</button></h4>";
			$_SESSION['attributes'] = array();
			$return .= "<table class='table table-striped table-sm'>";
			$return .= "<tr><th>attribute_id</th><th>namespace</th><th>attribute</th><th>value</th></tr>";
			foreach ($attributes as $attribute_key => $attribute_value) {
				$_SESSION['attributes'][$attribute_key]['attribute_id'] 
					= $attribute_value['attribute_id'];
				$_SESSION['attributes'][$attribute_key]['namespace'] 
					= $attribute_value['namespace'];
				$_SESSION['attributes'][$attribute_key]['attribute'] 
					= $attribute_value['attribute'];
				$_SESSION['attributes'][$attribute_key]['value'] 
					= $attribute_value['value'];
				$return .= "<tr><td>".$_SESSION['attributes'][$attribute_key]['attribute_id']."</td><td>".$_SESSION['attributes'][$attribute_key]['namespace']."</td><td>".$attribute_value['attribute']."</td><td>".$_SESSION['attributes'][$attribute_key]['value']."</td></tr>";
			}
			$return .= "</table>";
			$return .= "</div>";			

			$return .= "<div id='person' class='table-responsive person_manage'>";
			$return .= "<h4>person&nbsp;<button type='button' id='personTableCreate' class='btn btn-primary btn-sm mx-1 my-1'>Create person Table</button><button type='button' id='personTableDrop' class='btn btn-danger btn-sm mx-1 my-1'>Drop person Table</button><button type='button' id='personTableInsert' class='btn btn-success btn-sm mx-1 my-1'>Insert person Entries</button></h4>";
			$_SESSION['person'] = array();
			$return .= "<table class='table table-striped table-sm'>";
			$return .= "<tr><th>person_id</th><th>xml_id</th><th>sex</th></tr>";
			foreach ($person_table as $person_table_key => $person_table_value) {
				$_SESSION['person'][$person_table_key]['person_id'] 
					= $person_table_value['primary_id'];
				$_SESSION['person'][$person_table_key]['xml_id'] 
					= $person_table_value['xml_id'];
				$_SESSION['person'][$person_table_key]['sex'] 
					= $person_table_value['sex'];
				$return .= "<tr><td>".$_SESSION['person'][$person_table_key]['person_id']."</td><td>".$_SESSION['person'][$person_table_key]['xml_id']."</td><td>".$_SESSION['person'][$person_table_key]['sex']."</td></tr>";
			}
			$return .= "</table>";
			$return .= "</div>";
			
			$return .= "<div id='persName' class='table-responsive persName_manage'>";
			$return .= "<h4>persName&nbsp;<button type='button' id='persNameTableCreate' class='btn btn-primary btn-sm mx-1 my-1'>Create persName Table</button><button type='button' id='persNameTableDrop' class='btn btn-danger btn-sm mx-1 my-1'>Drop persName Table</button><button type='button' id='persNameTableInsert' class='btn btn-success btn-sm mx-1 my-1'>Insert persName Entries</button></h4>";
			$_SESSION['persName'] = array();
			$return .= "<table class='table table-striped table-sm'>";
			$return .= "<tr><th>persName_id</th><th>person_id</th><th>xml_id</th><th>attribute_id</th></tr>";
			foreach($persName as $persName_key => $persName_value){
				$_SESSION['persName'][$persName_key]['persName_id'] 
					= $persName_value['persName_id'];
				$_SESSION['persName'][$persName_key]['person_id'] 
					= $persName_value['primary_id'];
				$_SESSION['persName'][$persName_key]['xml_id'] 
					= $persName_value['xml_id'];
				$_SESSION['persName'][$persName_key]['attribute_id'] 
					= $persName_value['attribute_id'];
				$return .= "<tr><td>".$_SESSION['persName'][$persName_key]['persName_id']."</td><td>".$_SESSION['persName'][$persName_key]['person_id']."</td><td>".$_SESSION['persName'][$persName_key]['xml_id']."</td><td>".$_SESSION['persName'][$persName_key]['attribute_id']."</td>";
			}
			$return .= "</table>";
			$return .= "</div>";
			
			$return .= "<div id='persName_forename' class='table-responsive persName_forename_manage'>";
			$return .= "<h4>persName_forename&nbsp;<button type='button' id='persName_forenameTableCreate' class='btn btn-primary btn-sm mx-1 my-1'>Create persName_forename Table</button><button type='button' id='persName_forenameTableDrop' class='btn btn-danger btn-sm mx-1 my-1'>Drop persName_forename Table</button><button type='button' id='persName_forenameTableInsert' class='btn btn-success btn-sm mx-1 my-1'>Insert persName_forename Entries</button></h4>";
			$_SESSION['persName_forename'] = array();
			$return .= "<table class='table table-striped table-sm'>";
			$return .= "<tr><th>persName_forename_id</th><th>persName_id</th><th>attribute_id</th><th>forename</th></tr>";
			foreach ($persName_forename as $persName_forename_key => $persName_forename_value) {
				$_SESSION['persName_forename'][$persName_forename_key]['persName_forename_id'] 
					= $persName_forename_key;
				$_SESSION['persName_forename'][$persName_forename_key]['persName_id'] 
					= $persName_forename_value['persName_id'];
				$_SESSION['persName_forename'][$persName_forename_key]['attribute_id'] 
					= $persName_forename_value['attribute_id'];
				$_SESSION['persName_forename'][$persName_forename_key]['forename'] 
					= $persName_forename_value['forename'];
				$return .= "<tr><td>".$_SESSION['persName_forename'][$persName_forename_key]['persName_forename_id']."</td><td>".$_SESSION['persName_forename'][$persName_forename_key]['persName_id']."</td><td>".$_SESSION['persName_forename'][$persName_forename_key]['attribute_id']."</td><td>".$_SESSION['persName_forename'][$persName_forename_key]['forename']."</td></tr>";
			}
			$return .= "</table>";
			$return .= "</div>";
			
			$return .= "<div id='persName_surname' class='table-responsive persName_surname_manage'>";
			$return .= "<h4>persName_surname&nbsp;<button type='button' id='persName_surnameTableCreate' class='btn btn-primary btn-sm mx-1 my-1'>Create persName_surname Table</button><button type='button' id='persName_surnameTableDrop' class='btn btn-danger btn-sm mx-1 my-1'>Drop persName_surname Table</button><button type='button' id='persName_surnameTableInsert' class='btn btn-success btn-sm mx-1 my-1'>Insert persName_surname Entries</button></h4>";
			$_SESSION['persName_surname'] = array();
			$return .= "<table class='table table-striped table-sm'>";
			$return .= "<tr><th>persName_surname_id</th><th>persName_id</th><th>attribute_id</th><th>surname</th></tr>";
			foreach ($persName_surname as $persName_surname_key => $persName_surname_value) {
				$_SESSION['persName_surname'][$persName_surname_key]['persName_surname_id'] 
					= $persName_surname_key;
				$_SESSION['persName_surname'][$persName_surname_key]['persName_id'] 
					= $persName_surname_value['persName_id'];
				$_SESSION['persName_surname'][$persName_surname_key]['attribute_id'] 
					= $persName_surname_value['attribute_id'];
				$_SESSION['persName_surname'][$persName_surname_key]['surname'] 
					= $persName_surname_value['surname'];
				$return .= "<tr><td>".$_SESSION['persName_surname'][$persName_surname_key]['persName_surname_id']."</td><td>".$_SESSION['persName_surname'][$persName_surname_key]['persName_id']."</td><td>".$_SESSION['persName_surname'][$persName_surname_key]['attribute_id']."</td><td>".$_SESSION['persName_surname'][$persName_surname_key]['surname']."</td></tr>";
			}
			$return .= "</table>";
			$return .= "</div>";
			
			$return .= "<div id='persName_addName' class='table-responsive persName_addName_manage'>";
			$return .= "<h4>persName_addName&nbsp;<button type='button' id='persName_addNameTableCreate' class='btn btn-primary btn-sm mx-1 my-1'>Create persName_addName Table</button><button type='button' id='persName_addNameTableDrop' class='btn btn-danger btn-sm mx-1 my-1'>Drop persName_addName Table</button><button type='button' id='persName_addNameTableInsert' class='btn btn-success btn-sm mx-1 my-1'>Insert persName_addName Entries</button></h4>";
			$_SESSION['persName_addName'] = array();
			$return .= "<table class='table table-striped table-sm'>";
			$return .= "<tr><th>addName_id</th><th>persName_id</th><th>attribute_id</th><th>addName</th></tr>";
			foreach ($persName_addName as $persName_addName_key => $persName_addName_value) {
				$_SESSION['persName_addName'][$persName_addName_key]['persName_addName_id'] 
					= $persName_addName_key;
				$_SESSION['persName_addName'][$persName_addName_key]['persName_id'] 
					= $persName_addName_value['persName_id'];
				$_SESSION['persName_addName'][$persName_addName_key]['attribute_id'] 
					= $persName_addName_value['attribute_id'];
				$_SESSION['persName_addName'][$persName_addName_key]['addName'] 
					= $persName_addName_value['addName'];
				$return .= "<tr><td>".$_SESSION['persName_addName'][$persName_addName_key]['persName_addName_id']."</td><td>".$_SESSION['persName_addName'][$persName_addName_key]['persName_id']."</td><td>".$_SESSION['persName_addName'][$persName_addName_key]['attribute_id']."</td><td>".$_SESSION['persName_addName'][$persName_addName_key]['addName']."</td></tr>";
			}
			$return .= "</table>";
			$return .= "</div>";
			
			$return .= "<div id='name' class='table-responsive name_manage'>";
			$return .= "<h4>name</h4>";
			$_SESSION['name'] = array();
			$return .= "<table class='table table-striped table-sm'>";
			$return .= "<tr><th>name_id</th><th>name</th></tr>";
			foreach ($birth_name as $name_key => $name_value) {
				$_SESSION['name'][$name_key]['name_id']
					= $name_value['name_id'];
				$_SESSION['name'][$name_key]['name']
					= $name_value['value'];
				$return .= "<tr><td>".$_SESSION['name'][$name_key]['name_id']."</td><td>".$_SESSION['name'][$name_key]['name']."</td></tr>";
			}
			$return .= "</table>";
			$return .= "</div>";
			
			$return .= "<div id='birth_name_attributes' class='table-responsive birth_name_attributes_manage'>";
			$return .= "<h4>birth_name_attributes</h4>";
			$_SESSION['birth_name_attributes'] = array();
			$return .= "<table class='table table-striped table-sm'>";
			$return .= "<tr><th>birth_name_attributes_id</th><th>birth_name_id</th><th>attribute_id</th></tr>";
			foreach ($birth_name as $birth_name_key => $birth_name_value){
				$_SESSION['birth_name_attributes'][$birth_name_key]['birth_name_id'] 
					= $birth_name_key;
				if(isset($birth_name_value['attributes'])){
					$_SESSION['birth_name_attributes'][$birth_name_key]['attributes'] 
						= array();
					foreach ($birth_name_value['attributes'] as $birth_name_attribute_key => $birth_name_attribute_value) {
						if(!isset($birth_name_attributes_index)){
							$birth_name_attributes_index = 0;
						}
						$_SESSION['birth_name_attributes'][$birth_name_key]['attributes'][$birth_name_attributes_index]['birth_name_attribute_id'] 
							= $birth_name_attributes_index;
						$_SESSION['birth_name_attributes'][$birth_name_key]['attributes'][$birth_name_attributes_index]['attribute_id'] 
							= $birth_name_attribute_value;
						$return .= "<tr><td>".$_SESSION['birth_name_attributes'][$birth_name_key]['attributes'][$birth_name_attributes_index]['birth_name_attribute_id']."</td><td>".$_SESSION['birth_name_attributes'][$birth_name_key]['birth_name_id']."</td><td>".$_SESSION['birth_name_attributes'][$birth_name_key]['attributes'][$birth_name_attributes_index]['attribute_id']."</td>";
						$birth_name_attributes_index++;
					}
				}
			}
			unset($name_attributes_index);
			$return .= "</table>";
			$return .= "</div>";
							
			$return .= "<div id='birth' class='table-responsive birth_manage'>";
			$return .= "<h4>birth</h4>";
			$_SESSION['birth'] = array();
			$return .= "<table class='table table-striped table-sm'>";
			$return .= "<tr><th>birth_id</th><th>person_id</th><th>xml_id</th><th>attribute_id</th><th>birth</th></tr>";
			foreach ($birth as $birth_key => $birth_value) {
				$_SESSION['birth'][$birth_key]['birth_id']
					= $birth_key;
				$_SESSION['birth'][$birth_key]['person_id']
					= $birth_value['primary_id'];
				$_SESSION['birth'][$birth_key]['xml_id']
					= $birth_value['xml_id'];
				$_SESSION['birth'][$birth_key]['attribute_id']
					= $birth_value['attribute_id'];
				$_SESSION['birth'][$birth_key]['birth']
					= $birth_value['value'];
				$return .= "<tr><td>".$_SESSION['birth'][$birth_key]['birth_id']."</td><td>".$_SESSION['birth'][$birth_key]['person_id']."</td><td>".$_SESSION['birth'][$birth_key]['xml_id']."</td><td>".$_SESSION['birth'][$birth_key]['attribute_id']."</td><td>".$_SESSION['birth'][$birth_key]['birth']."</td></tr>";
			}
			$return .= "</table>";
			$return .= "</div>";

			$return .= "<div id='birth_name' class='table-responsive birth_name_manage'>";
			$return .= "<h4>birth_name</h4>";
			$_SESSION['birth_name'] = array();
			$return .= "<table class='table table-striped table-sm'>";
			$return .= "<tr><th>birth_name_id</th><th>birth_id</th><th>name_id</th></tr>";
			foreach ($birth as $birth_key => $birth_value) {
				$_SESSION['birth_name'][$birth_key]['birth_id'] 
					= $birth_key;
				$_SESSION['birth_name'][$birth_key]['names'] 
					= array();
				foreach ($birth_value['name_id'] as $birth_value_name_id_key => $birth_value_name_id_value){
					if(!isset($birthplace_index)){
						$birthplace_index = 0;
					}
					$_SESSION['birth_name'][$birth_key]['names'][$birthplace_index]['birth_name_id']
						= $birthplace_index;
					$_SESSION['birth_name'][$birth_key]['names'][$birthplace_index]['name_id']
						= $birth_value_name_id_value;
					$return .= "<tr><td>".$_SESSION['birth_name'][$birth_key]['names'][$birthplace_index]['birth_name_id']."</td><td>".$_SESSION['birth_name'][$birth_key]['birth_id']."</td><td>".$_SESSION['birth_name'][$birth_key]['names'][$birthplace_index]['name_id']."</td></tr>";
					$birthplace_index++;
				}
			}
			unset($birthplace_index);
			$return .= "</table>";
			$return .= "</div>";

			$return .= "<div id='death' class='table-responsive death_manage'>";
			$return .= "<h4>death</h4>";
			$_SESSION['death'] = array();
			$return .= "<table class='table table-striped table-sm'>";
			$return .= "<tr><th>death_id</th><th>person_id</th><th>xml_id</th><th>attribute_id</th><th>death</th></tr>";
			foreach ($death as $death_key => $death_value){
				$_SESSION['death'][$death_key]['death_id']
					= $death_key;
				$_SESSION['death'][$death_key]['person_id']
					= $death_value['primary_id'];
				$_SESSION['death'][$death_key]['xml_id']
					= $death_value['xml_id'];
				$_SESSION['death'][$death_key]['attribute_id']
					= $death_value['attribute_id'];
				$_SESSION['death'][$death_key]['death']
					= $death_value['string'];
				$return .= "<tr><td>".$_SESSION['death'][$death_key]['death_id']."</td><td>".$_SESSION['death'][$death_key]['person_id']."</td><td>".$_SESSION['death'][$death_key]['xml_id']."</td><td>".$_SESSION['death'][$death_key]['attribute_id']."</td><td>".$_SESSION['death'][$death_key]['death']."</td></tr>";
			}
			$return .= "</table>";
			$return .= "</div>";

			$return .= "<div id='placeName' class='table-responsive placeName_manage'>";
			$return .= "<h4>placeName</h4>";
			$_SESSION['placeName'] = array();
			$return .= "<table class='table table-striped table-sm'>";
			$return .= "<tr><th>placeName_id</th><th>attribute_id</th><th>placeName_node</th><th>placeName</th></tr>";
			foreach($placeName as $placeName_key => $placeName_value){
				$_SESSION['placeName'][$placeName_key]['placeName_id']
					= $placeName_key;
				$_SESSION['placeName'][$placeName_key]['attribute_id']
					= $placeName_value['attribute_id'];
				$_SESSION['placeName'][$placeName_key]['placeName_node']
					= $placeName_value['placeName_node'];
				$_SESSION['placeName'][$placeName_key]['placeName']
					= $placeName_value['placeName_string'];
				$return .= "<tr><td>".$_SESSION['placeName'][$placeName_key]['placeName_id']."</td><td>".$_SESSION['placeName'][$placeName_key]['attribute_id']."</td><td>".$_SESSION['placeName'][$placeName_key]['placeName_node']."</td><td>".$_SESSION['placeName'][$placeName_key]['placeName']."</td></tr>";
			}
			$return .= "</table>";
			$return .= "</div>";
			
			$return .= "<div id='residence' class='table-responsive residence_manage'>";
			$return .= "<h4>residence</h4>";
			$_SESSION['residence'] = array();
			$return .= "<table class='table table-striped table-sm'>";
			$return .= "<tr><th>residence_id</th><th>person_id</th><th>xml_id</th><th>placeName_id</th></tr>";
			foreach ($residence as $residence_key => $residence_value){
				$_SESSION['residence'][$residence_key]['residence_id']
					= $residence_key;
				$_SESSION['residence'][$residence_key]['person_id']
					= $residence_value['primary_id'];
				$_SESSION['residence'][$residence_key]['xml_id']
					= $residence_value['xml_id'];
				if(isset($residence_value['placeName_id'])){
					$_SESSION['residence'][$residence_key]['placeNames'] = array();
					foreach ($residence_value['placeName_id'] as $placeName_id_key => $placeName_id_value){
						$_SESSION['residence'][$residence_key]['placeNames'][$placeName_id_key]['placeName_id']
							= $placeName_id_value;
						$return .= "<tr><td>".$_SESSION['residence'][$residence_key]['residence_id']."</td><td>".$_SESSION['residence'][$residence_key]['person_id']."</td><td>".$_SESSION['residence'][$residence_key]['xml_id']."</td><td>".$_SESSION['residence'][$residence_key]['placeNames'][$placeName_id_key]['placeName_id']."</td></tr>";
					}
				}
			}
			$return .= "</table>";
			$return .= "</div>";

			$return .= "<div id='occupation' class='table-responsive occupation_manage'>";
			$return .= "<h4>occupation</h4>";
			$_SESSION['occupation'] = array();
			$return .= "<table class='table table-striped table-sm'>";
			$return .= "<tr><th>occupation_id</th><th>person_id</th><th>xml_id</th><th>occupation</th></tr>";
			foreach ($occupation as $occupation_key => $occupation_value){
				$_SESSION['occupation'][$occupation_key]['occupation_id']
					= $occupation_key;
				$_SESSION['occupation'][$occupation_key]['person_id']
					= $occupation_value['primary_id'];
				$_SESSION['occupation'][$occupation_key]['xml_id']
					= $occupation_value['xml_id'];
				$_SESSION['occupation'][$occupation_key]['occupation']
					= $occupation_value['string'];
				$return .= "<tr><td>".$_SESSION['occupation'][$occupation_key]['occupation_id']."</td><td>".$_SESSION['occupation'][$occupation_key]['person_id']."</td><td>".$_SESSION['occupation'][$occupation_key]['xml_id']."</td><td>".$_SESSION['occupation'][$occupation_key]['occupation']."</td></tr>";
			}
			$return .= "</table>";
			$return .= "</div>";
			
			$return .= "<div id='education' class='table-responsive education_manage'>";
			$return .= "<h4>education</h4>";
			$_SESSION['education'] = array();
			$return .= "<table class='table table-striped table-sm'>";
			$return .= "<tr><th>education_id</th><th>person_id</th><th>xml_id</th><th>attribute_id</th><th>education</th></tr>";
			foreach ($education as $education_key => $education_value){
				$_SESSION['education'][$education_key]['education_id']
					= $education_key;
				$_SESSION['education'][$education_key]['person_id']
					= $education_value['primary_id'];
				$_SESSION['education'][$education_key]['xml_id']
					= $education_value['xml_id'];
				if(isset($education_value['attribute_id'])){
					$_SESSION['education'][$education_key]['attribute_id']
						= $education_value['attribute_id'];
				} else {
					$_SESSION['education'][$education_key]['attribute_id']
						= NULL;
				}
				$_SESSION['education'][$education_key]['education']
					= $education_value['string'];
				$return .= "<tr><td>".$_SESSION['education'][$education_key]['education_id']."</td><td>".$_SESSION['education'][$education_key]['person_id']."</td><td>".$_SESSION['education'][$education_key]['xml_id']."</td><td>".$_SESSION['education'][$education_key]['attribute_id']."</td><td>".$_SESSION['education'][$education_key]['education']."</td></tr>";
			}
			$return .= "</table>";
			$return .= "</div>";

			$return .= "<div id='affiliation' class='table-responsive affiliation_manage'>";
			$return .= "<h4>affiliation</h4>";
			$_SESSION['affiliation'] = array();
			$return .= "<table class='table table-striped table-sm'>";
			$return .= "<tr><th>affiliation_id</th><th>person_id</th><th>xml_id</th><th>attribute_id</th><th>affiliation</th></tr>";
			foreach ($affiliation as $affiliation_key => $affiliation_value){
				$_SESSION['affiliation'][$affiliation_key]['affiliation_id']
					= $affiliation_key;
				$_SESSION['affiliation'][$affiliation_key]['person_id']
					= $affiliation_value['primary_id'];
				$_SESSION['affiliation'][$affiliation_key]['xml_id']
					= $affiliation_value['xml_id'];
				$_SESSION['affiliation'][$affiliation_key]['attribute_id']
					= $affiliation_value['attribute_id'];
				$_SESSION['affiliation'][$affiliation_key]['affiliation']
					= $affiliation_value['string'];
				$return .= "<tr><td>".$_SESSION['affiliation'][$affiliation_key]['affiliation_id']."</td><td>".$_SESSION['affiliation'][$affiliation_key]['person_id']."</td><td>".$_SESSION['affiliation'][$affiliation_key]['xml_id']."</td><td>".$_SESSION['affiliation'][$affiliation_key]['attribute_id']."</td><td>".$_SESSION['affiliation'][$affiliation_key]['affiliation']."</td></tr>";
			}
			$return .= "</table>";
			$return .= "</div>";

			$return .= "<div id='state' class='table-responsive state_manage'>";
			$return .= "<h4>state</h4>";
			$_SESSION['state'] = array();
			$return .= "<table class='table table-striped table-sm'>";
			$return .= "<tr><th>state_id</th><th>person_id</th><th>xml_id</th><th>label</th><th>note</th></tr>";
			foreach ($state as $state_key => $state_value){
				$_SESSION['state'][$state_key]['state_id']
					= $state_key;
				$_SESSION['state'][$state_key]['person_id']
					= $state_value['primary_id'];
				$_SESSION['state'][$state_key]['xml_id']
					= $state_value['xml_id'];
				$_SESSION['state'][$state_key]['label']
					= $state_value['label'];
				if(isset($state_value['note'])){
					$_SESSION['state'][$state_key]['note']
						= $state_value['note'];
				} else {
					$_SESSION['state'][$state_key]['note']
						= NULL;
				}
				$return .= "<tr><td>".$_SESSION['state'][$state_key]['state_id']."</td><td>".$_SESSION['state'][$state_key]['person_id']."</td><td>".$_SESSION['state'][$state_key]['xml_id']."</td><td>".$_SESSION['state'][$state_key]['label']."</td><td>".$_SESSION['state'][$state_key]['note']."</td></tr>";				
			}
			$return .= "</table>";
			$return .= "</div>";
			
			$return .= "<div id='state_attributes' class='table-responsive state_attributes_manage'>";
			$return .= "<h4>state_attributes</h4>";
			$_SESSION['state_attributes'] = array();
			$return .= "<table class='table table-striped table-sm'>";
			$return .= "<tr><th>state_attributes_id</th><th>state_id</th><th>attribute_id</th></tr>";
			foreach ($state as $state_key => $state_value){
				$_SESSION['state_attributes'][$state_key]['state_id']
					= $state_key;
				if(isset($state_value['attributes'])){
					$_SESSION['state_attributes'][$state_key]['attributes'] = array();
					foreach ($state_value['attributes'] as $state_attribute_key => $state_attribute_value) {
						if(!isset($state_attributes_index)){
							$state_attributes_index = 0;
						}
						$_SESSION['state_attributes'][$state_key]['state_attributes_id']
							= $state_attributes_index;
						$_SESSION['state_attributes'][$state_key]['attribute_id'][$state_attribute_key]
							= $state_attribute_value;
						$return .= "<tr><td>".$_SESSION['state_attributes'][$state_key]['state_attributes_id']."</td><td>".$_SESSION['state_attributes'][$state_key]['state_id']."</td><td>".$_SESSION['state_attributes'][$state_key]['attribute_id'][$state_attribute_key]."</td>";
						$state_attributes_index++;
					}
				}
			}
			unset($state_attributes_index);
			$return .= "</table>";
			$return .= "</div>";
			
			$return .= "<div id='event' class='table-responsive event_manage'>";
			$return .= "<h4>event</h4>";
			$_SESSION['event'] = array();
			$return .= "<table class='table table-striped table-sm'>";
			$return .= "<tr><th>event_id</th><th>person_id</th><th>xml_id</th><th>p</th></tr>";
			foreach ($event as $event_key => $event_value){
				$_SESSION['event'][$event_key]['event_id']
					= $event_key;
				$_SESSION['event'][$event_key]['person_id']
					= $event_value['primary_id'];
				$_SESSION['event'][$event_key]['xml_id']
					= $event_value['xml_id'];
				if(isset($event_value['p'])){
					if(isset($event_value['p'][0])){
						foreach ($event_value['p'] as $event_value_value) {
							if(isset($event_value_value['xml_string'])){
								$_SESSION['event'][$event_key]['event']
									= $event_value_value['xml_string'];
							}
						}
					} elseif(isset($event_value['p']['string'])){
						$_SESSION['event'][$event_key]['event']
							= $event_value['p']['string'];
					}
				}
				$return .= "<tr><td>".$_SESSION['event'][$event_key]['event_id']."</td><td>".$_SESSION['event'][$event_key]['person_id']."</td><td>".$_SESSION['event'][$event_key]['xml_id']."</td><td>".$_SESSION['event'][$event_key]['event']."</td></tr>";				
			}
			$return .= "</table>";
			$return .= "</div>";
			
			$return .= "<div id='event_attributes' class='table-responsive event_attributes_manage'>";
			$return .= "<h4>event_attributes</h4>";
			$_SESSION['event_attributes'] = array();
			$return .= "<table class='table table-striped table-sm'>";
			$return .= "<tr><th>event_attributes_id</th><th>event_id</th><th>attribute_id</th></tr>";
			foreach ($event as $event_key => $event_value){
				if(isset($event_value['attributes'])){
					if(!isset($event_attributes_index)){
						$event_attributes_index = 0;
					}
					$_SESSION['event_attributes'][$event_key]['event_id']
						= $event_key;
					foreach ($event_value['attributes'] as $event_attribute_key => $event_attribute_value) {
						$_SESSION['event_attributes'][$event_key]['attributes'][$event_attribute_key]['event_attributes_id']
							= $event_attributes_index;
						$_SESSION['event_attributes'][$event_key]['attributes'][$event_attribute_key]['attribute_id']
							= $event_attribute_value;
						$return .= "<tr><td>".$_SESSION['event_attributes'][$event_key]['attributes'][$event_attribute_key]['event_attributes_id']."</td><td>".$_SESSION['event_attributes'][$event_key]['event_id']."</td><td>".$_SESSION['event_attributes'][$event_key]['attributes'][$event_attribute_key]['attribute_id']."</td>";
						$event_attributes_index++;
					}
				}
			}
			unset($event_attributes_index);
			$return .= "</table>";
			$return .= "</div>";
			
			$return .= "<div id='note' class='table-responsive note_manage'>";
			$return .= "<h4>note</h4>";
			$_SESSION['note'] = array();
			$return .= "<table class='table table-striped table-sm'>";
			$return .= "<tr><th>note_id</th><th>person_id</th><th>xml_id</th><th>note</th></tr>";
			foreach ($note as $note_key => $note_value){
				if(isset($note_value['string'])){
					$_SESSION['note'][$note_key]['note_id']
						= $note_key;
					$_SESSION['note'][$note_key]['person_id']
						= $note_value['primary_id'];
					$_SESSION['note'][$note_key]['xml_id']
						= $note_value['xml_id'];
					if(is_array($note_value['string'])){
						$_SESSION['note'][$note_key]['note']
							= $note_value['string']['xml_string'];
					} else {
						$_SESSION['note'][$note_key]['note']
							= $note_value['string'];
					}
					$return .= "<tr><td>".$_SESSION['note'][$note_key]['note_id']."</td><td>".$_SESSION['note'][$note_key]['person_id']."</td><td>".$_SESSION['note'][$note_key]['xml_id']."</td><td>".$_SESSION['note'][$note_key]['note']."</td></tr>";					
				}
			}
			$return .= "</table>";
			$return .= "</div>";
			
			$return .= "<div id='bibl_ref' class='table-responsive bibl_ref_manage'>";
			$return .= "<h4>bibl_ref</h4>";
			$_SESSION['bibl_ref'] = array();
			$return .= "<table class='table table-striped table-sm'>";
			$return .= "<tr><th>bibl_ref_id</th><th>bibl_id</th><th>attribute_id</th><th>bibl_ref</th></tr>";
			foreach ($bibl_ref as $bibl_ref_key => $bibl_ref_value){
				$_SESSION['bibl_ref'][$bibl_ref_key]['bibl_ref_id']
					= $bibl_ref_key;
				$_SESSION['bibl_ref'][$bibl_ref_key]['bibl_id']
					= $bibl_ref_value['bibl_id'];
				$_SESSION['bibl_ref'][$bibl_ref_key]['attribute_id']
					= $bibl_ref_value['attribute_id'];
				$_SESSION['bibl_ref'][$bibl_ref_key]['bibl_ref']
					= $bibl_ref_value['string'];
				$return .= "<tr><td>".$_SESSION['bibl_ref'][$bibl_ref_key]['bibl_ref_id']."</td><td>".$_SESSION['bibl_ref'][$bibl_ref_key]['bibl_id']."</td><td>".$_SESSION['bibl_ref'][$bibl_ref_key]['attribute_id']."</td><td>".$_SESSION['bibl_ref'][$bibl_ref_key]['bibl_ref']."</td></tr>";
			}
			$return .= "</table>";
			$return .= "</div>";
			
			$return .= "<div id='bibl_biblScope' class='table-responsive bibl_biblScope_manage'>";
			$return .= "<h4>bibl_biblScope</h4>";
			$_SESSION['bibl_biblScope'] = array();
			$return .= "<table class='table table-striped table-sm'>";
			$return .= "<tr><th>bibl_biblScope_id</th><th>bibl_id</th><th>attribute_id</th><th>bibl_biblScope</th></tr>";
			foreach ($bibl_biblScope as $bibl_biblScope_key => $bibl_biblScope_value){
				$_SESSION['bibl_biblScope'][$bibl_biblScope_key]['bibl_biblScope_id']
					= $bibl_biblScope_key;
				$_SESSION['bibl_biblScope'][$bibl_biblScope_key]['bibl_id']
					= $bibl_biblScope_value['bibl_id'];
				$_SESSION['bibl_biblScope'][$bibl_biblScope_key]['attribute_id']
					= $bibl_biblScope_value['attribute_id'];
				$_SESSION['bibl_biblScope'][$bibl_biblScope_key]['bibl_biblScope']
					= $bibl_biblScope_value['string'];
				$return .= 
					"<tr><td>".$_SESSION['bibl_biblScope'][$bibl_biblScope_key]['bibl_biblScope_id']."</td><td>".$_SESSION['bibl_biblScope'][$bibl_biblScope_key]['bibl_id']."</td><td>".$_SESSION['bibl_biblScope'][$bibl_biblScope_key]['attribute_id']."</td><td>".$_SESSION['bibl_biblScope'][$bibl_biblScope_key]['bibl_biblScope']."</td></tr>";
			}
			$return .= "</table>";
			$return .= "</div>";
			
			$return .= "<div id='bibl' class='table-responsive bibl_manage'>";
			$return .= "<h4>bibl</h4>";
			$_SESSION['bibl'] = array();
			$return .= "<table class='table table-striped table-sm'>";
			$return .= "<tr><th>bibl_id</th><th>person_id</th><th>xml_id</th></tr>";
			foreach ($bibl as $bibl_key => $bibl_value){
				$_SESSION['bibl'][$bibl_key]['bibl_id']
					= $bibl_key;
				$_SESSION['bibl'][$bibl_key]['person_id']
					= $bibl_value['primary_id'];
				$_SESSION['bibl'][$bibl_key]['xml_id']
					= $bibl_value['xml_id'];
				$return .= "<tr><td>".$_SESSION['bibl'][$bibl_key]['bibl_id']."</td><td>".$_SESSION['bibl'][$bibl_key]['person_id']."</td><td>".$_SESSION['bibl'][$bibl_key]['xml_id']."</td></tr>";
			}
			$return .= "</table>";
			$return .= "</div>";				
			
		}
		return($return);
	}

	// Open file and get to work
	if($file !== 0) {
		if (file_exists($file)) {
			
			$xml = new XMLReader;
			$xml->open($file);

			// now that we're at the right depth, hop to the next <person/> until the end of the tree
			while ($xml->read()){
    		
				if($xml->nodeType === XMLReader::ELEMENT && $xml->name === 'person'){

					$node = preg_replace('~\s*(<([^-->]*)>[^<]*<!--\2-->|<[^>]*>)[\t|\n|\r]*~','$1',$xml->readOuterXML());
    			$node = new SimpleXMLIterator($node);
				
					$peopleArray['person'][] = sxiToArray($node);
				
				}
				
			}
			//echo '<pre>'; print_r($peopleArray); echo '</pre>';
			$table = table_cell($peopleArray);
			echo $table;		
			
		} else {
			exit('Failed to open:&nbsp;'.$file);
		}
	}
?>
        </main>
      </div>
    </div>
	
	<!-- jQuery for javascript -->
	<script src="<?php echo PROGRAM_JS_BASE ?>jquery.min.js"></script>
	<!-- SESSION scripts -->
	<script src="buttons/000-buttons-sex.js"></script>
	<script src="buttons/001-buttons-attributes.js"></script>
	<script src="buttons/002-buttons-person.js"></script>
	<script src="buttons/003-buttons-persName.js"></script>
	<script src="buttons/004-buttons-persName_forename.js"></script>
	<script src="buttons/005-buttons-persName_surname.js"></script>
	<script src="buttons/006-buttons-persName_addName.js"></script>
	<!-- End of JSON scripts -->
	<!-- Bootstrap extensions for jQuery -->
	<script src="<?php echo PROGRAM_JS_BASE ?>bootstrap/popper.min.js"></script>
	<script src="<?php echo PROGRAM_JS_BASE ?>bootstrap/bootstrap.min.js"></script>
	<!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
	<script src="<?php echo PROGRAM_JS_BASE ?>ie10-viewport-bug-workaround.js"></script>

	<!-- Icons -->
  <script src="<?php echo PROGRAM_JS_BASE ?>icons/feather.min.js"></script>
  <script>
    feather.replace()
  </script>	

</body>
</html>
<?php
}
?>