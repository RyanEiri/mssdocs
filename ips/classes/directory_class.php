<?php
class DirectoryGrab {
	var	$db,
		$sort_field,
		$view
		;
	function DirectoryGrab (
		$db,
		$sort_field
		) {
			$this->db = &$db;
			$this->sort_field = $sort_field;
			$this->GrabEntry();
			$this->DisplayEntries();
			if ($this->show) {
				$this->AlphaFilter();
				if (is_array($this->alphaf_array)) {
					$this->entries = $this->alphaf_array;
				} else {
					$this->error = 'nohits';
				}
			}
			switch ($this->sort_field) {
			case 'urltitle':
				$this->sort_field_2 = 'primary';
				$this->SortEntry();
				break;
			case 'featured':
				$this->sort_field_2 = 'urltitle';
				$this->SortEntry();
				break;
			case 'primary':
				$this->sort_field_2 = 'urltitle';
				$this->SortEntry();
				break;
			case 'primary_cat':
				$this->sort_field_2 = 'urltitle';
				$this->SortEntry();
				break;
			case 'secondary_cat':
				$this->sort_field_2 = 'primary_cat';
				$this->SortEntry();
				break;
			}
			$this->ViewEntry();
			$this->chunks = array_chunk($this->sorted_entries, 20, true);
			$this->num_chunks = sizeof($this->chunks);
			$this->SetPageAndNext();
	}
	function GrabEntry(){
		$this->sql = "SELECT * FROM sites";
		$this->result = &$this->db->query($this->sql);
		$this->entries = $this->result->fetchArray();
	}
	function GrabPrimaryCategory(){
		$this->sql = "SELECT id,name FROM primary_cat ORDER BY id";
		$this->result = &$this->db->query($this->sql);
		$this->primary_cat = $this->result->fetchArray();
		return $this->primary_cat;
	}
	function GrabPrimaryCatName(){
		$this->catNumber = $this->view[primary_cat];
		$this->sql = "SELECT name FROM primary_cat WHERE id='$this->catNumber' LIMIT 1";
		$this->result = &$this->db->query($this->sql);
		$this->catName = $this->result->fetch();
		$this->catName = $this->catName[name];
		return $this->catName;
	}
	// This method is used for an ajax answer to a request in getsecondary_cat.php.
	// This is used for the categories menu in the add and change dialogs.
	// Also inconveniently used on the fnp_directory.php master script. l:198
	function passedGrabSecondaryCategory($primary){
		$this->sql = "SELECT id,name FROM secondary_cat WHERE id_primary_cat='$primary' ORDER BY id";
		$this->result = &$this->db->query($this->sql);
		$this->secondary_cat = $this->result->fetchArray();
		return $this->secondary_cat;
	}
	function GrabSecondaryCategory(){
		$this->sql = "SELECT id,name FROM secondary_cat ORDER BY id";
		$this->result = &$this->db->query($this->sql);
		$this->secondary_cat = $this->result->fetchArray();
		return $this->secondary_cat;
}
	function GrabSecondaryCatName(){
		$this->catNumber = $this->view[secondary_cat];
		$this->sql = "SELECT name FROM secondary_cat WHERE id='$this->catNumber' LIMIT 1";
		$this->result = &$this->db->query($this->sql);
		$this->catName = $this->result->fetch();
		$this->catName = $this->catName[name];
		return $this->catName;
	}
	function ViewEntry(){
		if ($_GET[view]) {
			$this->getview = $_GET[view];
			$_SESSION[view] = $this->getview;
		} elseif ($_SESSION[view]) {
			$this->getview = $_SESSION[view];
		}
		if ($this->getview) {
			$this->SelectEntry();
		}
	}
	function SelectEntry(){
		foreach ($this->sorted_entries as $key => $value) {
			if ($value[primary]==$this->getview) {
				$this->nextview = $key + 1;
				$this->prevview = $key - 1;
			}
		}
		$this->nextview = $this->sorted_entries[$this->nextview][primary];
		$this->prevview = $this->sorted_entries[$this->prevview][primary];
		$this->viewsql = "SELECT * FROM sites WHERE `primary` = '$this->getview' LIMIT 1";
		$this->viewresult = &$this->db->query($this->viewsql);
		$this->view = $this->viewresult->fetch();
	}
	function ChangeEntry(){
		$this->getview = $_POST[change];
		$this->SelectEntry();
		$this->url = $_POST[url];
		$this->url = addslashes($this->url);
		$this->name = $_POST[name];
		$this->name = addslashes($this->name);
		$this->featured = $_POST[featuredSite];
		$this->short_desc = $_POST[shortDescription];
		$this->short_desc = addslashes($this->short_desc);
		$this->long_desc = $_POST[longDescription];
		$this->long_desc = addslashes($this->long_desc);
		$this->primary_cat = $_POST[primary_cat];
		$this->primary_cat = addslashes($this->primary_cat);
		$this->secondary_cat = $_POST[secondary_cat];
		$this->secondary_cat = addslashes($this->secondary_cat);
		$this->changesql = "UPDATE sites SET `url`='$this->url', `urltitle`='$this->name', `featured`='$this->featured', `short_desc`='$this->short_desc', `long_desc`='$this->long_desc', `primary_cat`='$this->primary_cat', `secondary_cat`='$this->secondary_cat' WHERE `primary`='$this->getview'";
		$this->db->query($this->changesql) or die ('Insert statement failed: Entry not updated');
		$this->SelectEntry();
	}
	function AddEntry(){
		$this->url = $_POST[url];
		$this->url = addslashes($this->url);
		$this->name = $_POST[name];
		$this->name = addslashes($this->name);
		$this->featured = $_POST[featuredSite];
		$this->short_desc = $_POST[shortDescription];
		$this->short_desc = addslashes($this->short_desc);
		$this->long_desc = $_POST[longDescription];
		$this->long_desc = addslashes($this->long_desc);
		$this->primary_cat = $_POST[primary_cat];
		$this->primary_cat = addslashes($this->primary_cat);
		$this->secondary_cat = $_POST[secondary_cat];
		$this->secondary_cat = addslashes($this->secondary_cat);
		$this->addsql = "INSERT INTO sites (url, urltitle, featured, short_desc, long_desc, primary_cat, secondary_cat) VALUES ('$this->url', '$this->name', '$this->featured', '$this->short_desc', '$this->long_desc', '$this->primary_cat', '$this->secondary_cat')";
		$this->addresult = &$this->db->query($this->addsql) or die ('Insert statement failed: Entry not added');
		$this->entryid = $this->addresult->getId();
	}
	function SortEntry(){
		if($this->sort_field_2){
		  $this->sorted_entries = sort_it_out($this->entries, $this->sort_field, $this->sort_field_2);
		} else {
		  $this->sorted_entries = sort_it_out($this->entries, $this->sort_field);
		}
		if ($this->enum==true) {
			foreach ($this->sorted_entries as $key => $value) {
				if ($value[$this->sort_field]=='YES') {
					$this->enum_entries[$key]=$value;
				}
			}
			unset($this->sorted_entries);
			$this->sorted_entries = $this->enum_entries;
		}
	}
	function AlphaFilter(){
		foreach ($this->entries as $value) {
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
	function DisplayEntries(){
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
