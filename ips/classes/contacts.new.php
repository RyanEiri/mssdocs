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
			if ($this->show) {
				$this->AlphaFilter();
				$this->contacts = $this->alphaf_array;
			}
			switch ($this->sort_field) {
			case 'customers_organization':
				$this->sort_field_2 = 'customers_name';
				$this->SortContact();
				break;
			case 'customers_name':
				$this->sort_field_2 = 'customers_organization';
				$this->SortContact();
				break;
			case 'customers_city':
				$this->sort_field_2 = 'customers_organization';
				$this->SortContact();
				break;
			case 'calendar_n':
				$this->sort_field_2 = 'customers_name';
				$this->enum = 'true';
				$this->SortContact();
				break;
			case 'calendar_a':
				$this->sort_field_2 = 'customers_name';
				$this->enum = 'true';
				$this->SortContact();
				break;			
			case 'catalog':
				$this->sort_field_2 = 'customers_name';
				$this->enum = 'true';
				$this->SortContact();
				break;
			}
			$this->chunks = array_chunk($this->sorted_contacts, 20, true);
			$this->num_chunks = sizeof($this->chunks);
			$this->SetPageAndNext();
	}

	function GrabContact(){
		$this->sql = "SELECT * FROM customers";
		$this->result = &$this->db->query($this->sql);
		$this->contacts = $this->result->fetchArray();
	}

	function ViewContact(){
		$this->getview = $_GET[view];
		$this->viewsql = "SELECT * FROM customers WHERE customers_id='$this->getview'";
		$this->shipviewsql = "SELECT * FROM shipping WHERE contact_id='$this->getview'";
		$this->viewresult = &$this->db->query($this->viewsql);
		$this->shipviewresult = &$this->db->query($this->shipviewsql);
		$this->view = $this->viewresult->fetch();
		$this->shipview = $this->shipviewresult->fetch();
		foreach ($this->sorted_contacts as $key => $value) {
			if ($value[customers_id]==$this->getview) {
				$this->nextview = $key + 1;
				$this->prevview = $key - 1;
			}
		}
		$this->nextview = $this->sorted_contacts[$this->nextview][customers_id];
		$this->prevview = $this->sorted_contacts[$this->prevview][customers_id];
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
					$this->show="/$this->show_search/i";
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


//class ViewContactObject {
//	var 	$contact_object
//		;
//	function ViewContactObject(
//		$contact_object
//		) {
//			$this->contact_object = &$contact_object;
