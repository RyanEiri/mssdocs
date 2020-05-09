<?php

class ContactGrab {
	var	$db,
		$sort_field
		;

	function ContactGrab (
		$db,
		$sort_field
		) {
			$this->db = &$db;
			$this->sort_field = $sort_field;
			$this->GrabContact();
			$this->DisplayContacts();
//			if ($this->show) {
				$this->AlphaFilter();
				if (is_array($this->alphaf_array)) {
					$this->contacts = $this->alphaf_array;
				} else {
					$this->error = 'nohits';
				}
//			}
			switch ($this->sort_field) {
			case 'contact_organization':
				$this->sort_field_2 = 'contact_name';
				$this->SortContact();
				break;
			case 'contact_name':
				$this->sort_field_2 = 'contact_organization';
				$this->SortContact();
				break;
			case 'contact_city':
				$this->sort_field_2 = 'contact_organization';
				$this->SortContact();
				break;
			case 'calendar_n':
				$this->sort_field_2 = 'contact_name';
				$this->enum = 'true';
				$this->SortContact();
				break;
			case 'calendar_a':
				$this->sort_field_2 = 'contact_name';
				$this->enum = 'true';
				$this->SortContact();
				break;			
			case 'catalog':
				$this->sort_field_2 = 'contact_name';
				$this->enum = 'true';
				$this->SortContact();
				break;
			}
			$this->chunks = array_chunk($this->sorted_contacts, 20, true);
			$this->num_chunks = sizeof($this->chunks);
			$this->SetPageAndNext();
	}

	function GrabContact(){
		$this->sql = "SELECT * FROM contacts";
		$this->result = &$this->db->query($this->sql);
		$this->contacts = $this->result->fetchArray();
	}

	function ViewContact(){
		if ($_GET[view]) {
			$this->getview = $_GET[view];
			$_SESSION[view] = $this->getview;
		} elseif ($_SESSION[view]) {
			$this->getview = $_SESSION[view];
		}
		$this->SelectContact();
	}

	function SelectContact(){
		foreach ($this->sorted_contacts as $key => $value) {
			if ($value[customers_id]==$this->getview) {
				$this->nextview = $key + 1;
				$this->prevview = $key - 1;
			}
		}
		$this->nextview = $this->sortedcontacts[$this->nextview][customers_id];
		$this->prevview = $this->sorted_contacts[$this->prevview][customers_id];
		$this->viewsql = "SELECT * FROM contacts WHERE contact_id='$this->getview' LIMIT 1";
//		$this->shipviewsql = "SELECT * FROM shipping WHERE contact_id='$this->getview' LIMIT 1";
		$this->viewresult = &$this->db->query($this->viewsql);
//		$this->shipviewresult = &$this->db->query($this->shipviewsql);
		$this->view = $this->viewresult->fetch();
//		$this->shipview = $this->shipviewresult->fetch();
		$this->phones = split_phone($this->view);
	}

	function ChangeContact(){
		$this->getview = $_POST['change'];
		$this->SelectContact();
		$this->entry_name = $_POST['name'];
		$this->entry_name = addslashes($this->entry_name);
		$this->position = $_POST['position'];
		$this->position = addslashes($this->position);
		$this->organization = $_POST['organization'];
		$this->organization = addslashes($this->organization);
		$this->department = $_POST['department'];
		$this->department = addslashes($this->department);
		$this->address_1 = $_POST['address_1'];
		$this->address_1 = addslashes($this->address_1);
		$this->address_2 = $_POST['address_2'];
		$this->address_2 = addslashes($this->address_2);
		$this->city = $_POST['city'];
		$this->city = addslashes($this->city);
		$this->province = $_POST['province'];
		$this->province = addslashes($this->province);
		$this->busphone = $_POST['b_phone'];
		$this->faxphone = $_POST['f_phone'];
		$this->homphone = $_POST['h_phone'];
		$this->celphone = $_POST['c_phone'];
		$this->tolphone = $_POST['toll_free_phone'];
		$this->email = $_POST['email'];
		$this->email = addslashes($this->email);
		$this->notes = $_POST['notes'];
		$this->notes = addslashes($this->notes);
		$this->changesql = "UPDATE contacts SET contact_name='$this->entry_name', contact_position='$this->position', contact_organization='$this->organization', contact_department='$this->department', contact_address_1='$this->address_1', contact_address_2='$this->address_2', contact_city='$this->city', contact_province='$this->province', contact_postal_code='$_POST[postal_code]', calendar_n='$_POST[calendar_n]', calendar_a='$_POST[calendar_a]', catalog='$_POST[catalog]', business_phone='$this->busphone', cell_phone='$this->celphone', fax_phone='$this->faxphone', home_phone='$this->homphone', toll_free_phone='$this->tolphone', email='$_POST[email]', notes='$this->notes', vendor='$_POST[vendor]' WHERE customers_id='$this->getview'";
		$this->db->query($this->changesql) or die ('Insert statement failed: Entry not updated');
//		if ($this->shipview[id]!=NULL) {
//			$this->changeshipsql = "UPDATE shipping SET notes='$_POST[ship_notes]' WHERE contact_id='$this->getview'";
//		} else {
//			$this->changeshipsql = "INSERT INTO shipping (contact_id, notes) VALUES ('$_POST[change]', '$_POST[ship_notes]')";
//		}
//		$this->db->query($this->changeshipsql) or die ('Ship insert statement failed: Entry not updated');
		$this->SelectContact();
	}

	function AddContact(){
		$this->busphone = $_POST[b_phone];
		$this->faxphone = $_POST[f_phone];
		$this->homphone = $_POST[h_phone];
		$this->celphone = $_POST[c_phone];
		$this->tolphone = $_POST[toll_free_phone];
		$this->email = $_POST[email];
		$this->email = addslashes($this->email);
		$this->notes = $_POST[notes];
		$this->notes = addslashes($this->notes);
		$this->addsql = "INSERT INTO customers (customers_name, customers_position, customers_organization, customers_department, customers_address_1, customers_address_2, customers_city, customers_province, customers_postal_code, calendar_n, calendar_a, catalog, business_phone, cell_phone, fax_phone, home_phone, toll_free_phone, email, notes, vendor) VALUES ('$_POST[name]', '$_POST[position]', '$_POST[organization]', '$_POST[department]', '$_POST[address_1]', '$_POST[address_2]', '$_POST[city]', '$_POST[province]', '$_POST[postal_code]', '$_POST[calendar_n]', '$_POST[calendar_a]', '$_POST[catalog]', '$this->busphone', '$this->celphone', '$this->faxphone', '$this->homphone', '$this->tolphone', '$_POST[email]', '$this->notes', '$_POST[vendor]')";
		$this->addresult = &$this->db->query($this->addsql) or die ('Insert statement failed: Entry not added');
		$this->contactid = $this->addresult->getId();
		$this->addshipsql = "INSERT INTO shipping (contact_id, notes) VALUES ('$this->contactid', '$_POST[ship_notes]')";
		$this->db->query($this->addshipsql) or die ('Shipping Insert statement failed: Shipping information wasn\'t added');		
	}

	function SortContact(){
		$this->sorted_contacts = sort_it_out($this->contacts, $this->sort_field, $this->sort_field_2);
		if ($this->enum==true) {
			foreach ($this->sorted_contacts as $key => $value) {
				if ($value[$this->sort_field]=='YES') {
					$this->enum_contacts[$key]=$value;
				}
			}
			unset($this->sorted_contacts);
			$this->sorted_contacts = $this->enum_contacts;
		}
	}

	function AlphaFilter(){
		foreach ($this->contacts as $value) {
			if (preg_match($this->show, $value[$this->sort_field])) {
				if(is_array($this->alphaf_array)) {
					$this->alphaf_array[] = $value;
				} else {
					$this->alphaf_array = array($value);
				}
			}
		}
		$this->page = 0;
	}		

	function DisplayContacts(){
		if ($_GET[show]) {
			$this->show_tmp = $_GET[show];
			$this->show_search = $_GET[search];
			$_SESSION[show] = $_GET[show];
			$_SESSION[search] = $_GET[search];
		} elseif ($_SESSION[show]) {
			$this->show_tmp = $_SESSION[show];
			$this->show_search = $_SESSION[search];
		} elseif ((!$_GET[show]) && (!$_SESSION[show])) {
			$this->show_tmp = 'all';
		}
		if ($_GET[sort]) {
			$this->sort_field = $_GET[sort];
			$_SESSION[sort] = $_GET[sort];
		} elseif ($_SESSION[sort]) {
			$this->sort_field = $_SESSION[sort];
		} 
		if ($this->show_tmp) {
			switch ($this->show_tmp) {
				case 'a':
					$this->show="/^[Aa]/";
					break;
				case 'b':
					$this->show="/^[Bb]/";
					break;
				case 'c':
					$this->show="/^[Cc]/";
					break;
				case 'd':
					$this->show="/^[Dd]/";
					break;
				case 'e':
					$this->show="/^[Ee]/";
					break;
				case 'f':
					$this->show="/^[Ff]/";
					break;
				case 'g':
					$this->show="/^[Gg]/";
					break;
				case 'h':
					$this->show="/^[Hh]/";
					break;
				case 'i':
					$this->show="/^[Ii]/";
					break;
				case 'j':
					$this->show="/^[Jj]/";
					break;
				case 'k':
					$this->show="/^[Kk]/";
					break;
				case 'l':
					$this->show="/^[Ll]/";
					break;
				case 'm':
					$this->show="/^[Mm]/";
					break;
				case 'n':
					$this->show="/^[Nn]/";
					break;
				case 'o':
					$this->show="/^[Oo]/";
					break;
				case 'p':
					$this->show="/^[Pp]/";
					break;
				case 'q':
					$this->show="/^[Qq]/";
					break;
				case 'r':
					$this->show="/^[Rr]/";
					break;
				case 's':
					$this->show="/^[Ss]/";
					break;
				case 't':
					$this->show="/^[Tt]/";
					break;
				case 'u':
					$this->show="/^[Uu]/";
					break;
				case 'v':
					$this->show="/^[Vv]/";
					break;
				case 'w':
					$this->show="/^[Ww]/";
					break;
				case 'x':
					$this->show="/^[Xx]/";
					break;
				case 'y':
					$this->show="/^[Yy]/";
					break;
				case 'z':
					$this->show="/^[Zz]/";
					break;
				case 'all':
					$this->show="//";
				case 'search':
					$this->keywords=preg_split("/[\s,]+/", $this->show_search);
					foreach($this->keywords as $value) {
						if($this->tmpstr) {
							$this->tmpstr .= '|' . $value;
						} else {
							$this->tmpstr = '(' . $value;
						}
					}
					$this->tmpstr .= ')';
					$this->show="/$this->tmpstr/i";
					unset($this->tmpstr);
					break;
			}
		}
	}

	function SetPageAndNext(){
		$this->NumberOfPages = range(1, $this->num_chunks);
		$this->PageSize = sizeof($this->NumberOfPages);
		if($_GET[page]) {
			$this->Page = $_GET[page];
			$_SESSION[page] = $this->Page;
			if($_GET[page]=='zero') {
				$this->Page = 0;
			}
		} else {
			if($_SESSION[page]) {
				$this->Page = $_SESSION[page];
			} else {
				$this->Page = 0;
			}
		}
		$this->NextPage = $this->Page + 1;
		$this->PreviousPage = $this->Page - 1;
	}
}
