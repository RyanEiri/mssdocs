<?php

// Classes for acting on files table
class FileGrab {
	private	$result,
		$sql,
		$query;
	public	$id,
		$name,
		$size,
		$type,
		$url,
		$title,
		$description,
		$date;

	function __construct(
		$name
		) {
			$this->name = $name;
			$this->grabFile();
	}

	function grabFile(){
		global $db;
		$this->sql = "SELECT id, size, type, url, title, description, date
			FROM files
			WHERE name='" . $this->name . "'
			LIMIT 1";
		$this->result = $db->query($this->sql);
		$this->query = $this->result->fetchArray();
		if(is_array($this->query)) {
			extract($this->query[0]);
		}
		if(isset($id)){
			$this->id = $id;
		}
		if(isset($size)){
			$this->size = $size;
		}
		if(isset($type)){
			$this->type = $type;
		}
		if(isset($url)){
			$this->url = $url;
		}
		if(isset($title)){
			$this->title = $title;
		}
		if(isset($description)){
			$this->description = $description;
		}
		if(isset($date)){
			$this->date = $date;
		}
	}
}

class FolderGrab {
	private	$result,
		$query,
		$sql,
		$folder_create,
		$folder_pattern;
	public	$folder_id,
		$folder_name,
		$zip_name,
		$zip_url,
		$thumbnail,
		$files;

	function __construct(
		$folder_name,
		$folder_create
		) {
			$this->folder_name = $folder_name;
			$this->folder_create = $folder_create;
			$this->folder_pattern = '/thumbnail/';
			if(preg_match($this->folder_pattern, $this->folder_name)){
			  $this->thumbnail = TRUE;
			} else {
			  $this->thumbnail = FALSE;
			}
			$this->grabFolder();
			$this->grabFolderFiles();
	}

	function grabFolder(){
		global $db;
		$this->grab_sql = "SELECT folders.folder_id, folders.folder_name, ziparchives.zip_name, ziparchives.zip_url, ziparchives.zip_date
			FROM folders
			LEFT JOIN ziparchives ON
			ziparchives.folder_id = folders.folder_id
			WHERE folders.folder_name='" . $this->folder_name . "'
			LIMIT 1";
		$this->grab_result = $db->query($this->grab_sql);
		$this->grab_query = $this->grab_result->fetchArray();
		if(is_array($this->grab_query)) {
			extract($this->grab_query[0]);
			if(isset($folder_id)){
			  $this->folder_id = $folder_id;
			}
			if(isset($zip_name)){
			  $this->zip_name = $zip_name;
			}
			if(isset($zip_url)){
			  $this->zip_url = $zip_url;
			}
			if(isset($zip_date)){
			  $this->zip_date = $zip_date;
			}
		} else {
			// check for creation flag set to true and
			// thumbnail folders are not stored in the database
			// test for them and ignore if matched
			if($this->folder_create && $this->thumbnail === FALSE){
			  $this->createFolder();
			}
		}
	}

	function createFolder(){
		global $db;
		$create_sql = "INSERT INTO folders (folder_name) VALUES ('$this->folder_name')";
		$create_result = $db->query($create_sql);
		$this->folder_id = $create_result->getId();

// The following lines allow the folder_id to be added to the files stored
// in the files table when the folders have already been created.
// This should be commented out in production.
//		$update_files_folder_id = "UPDATE files
//					SET `folder_id` =
//					'$this->folder_id' WHERE
//					`name` LIKE '%{$this->folder_name}%'
//					";
//		$update_files_result = $db->query($update_files_folder_id)
//			or die ('Update folder query failed!');
// End of folder_id code for files table

		$this->grabFolder();
	}

	function updateFolder(
		$update_folder
	){
		global $db;
		$update_folder_sql = "UPDATE folders
			SET `folder_name` =
			'$update_folder' WHERE
			`folder_id` =
			'$this->folder_id'
			";
		$db->query($update_folder_sql)
		  or die ('Update folder query failed!');
		foreach ($this->files as $key => $value) {
			$file_name = basename($value['name']);
			$file_name = $update_folder.'/'.$file_name;
			$file_url = $this->get_full_url().'/files/'.$file_name;
			$file_id = $value['id'];
			$update_files_sql = "UPDATE files
				SET `name` =
				'$file_name',
				`url` =
				'$file_url'
				WHERE `id` =
				'$file_id'
				";
			$db->query($update_files_sql)
			  or die ('Update files query failed!');
		}
	}

	function grabFolderFiles(){
		global $db;
		$sql = "SELECT files.id, files.name, files.size, files.type, files.url, files.title, files.description, files.date, folders.folder_id, folders.folder_name
				FROM files
				LEFT JOIN folders ON
				folders.folder_id = files.folder_id
				WHERE files.folder_id='" . $this->folder_id . "'
				";
		$result = $db->query($sql)
		  or die ('Select statement failed for grabbing folder files!');
		$query = $result->fetchArray();
		$this->files = $query;
	}

	function updateFolderFiles(
		$files_array
		){
		global $db;
		$id = "";
		$name = "";
		$title = "";
		$description = "";
		if(is_array($files_array)) {
		  foreach($files_array as $key => $value) {
		    $id = $value['id'];
		    if(isset($value['name'])){
		    	$name = $value['name'];
			$sql_name = "UPDATE files
			    SET `name` = '$name'
			    WHERE id='" . $id . "'
			    ";
			$db->query($sql_name)
			  or die ('Update name failed.');
		    }
		    if(isset($value['title'])){
		    	$title = $value['title'];
			$sql_title = "UPDATE files
			    SET `title` = '$title'
			    WHERE id='" . $id . "'
			    ";
			$db->query($sql_title)
			  or die ('Update title failed.');
		    }
		    if(isset($value['description'])){
		    	$description = $value['description'];
			$sql_description = "UPDATE files
			    SET `description` = '$description'
			    WHERE id='" . $id . "'
			    ";
			$db->query($sql_description)
			  or die ('Update description failed.');
		    }
		  }
		}
	}

	function removeFolder(){
		// thumbnail folders are currently not stored in the database
		// test for them and ignore if matched
		if(!preg_match($this->folder_pattern, $this->folder_name)){
		  global $db;
		  $removeZipSQL = "DELETE FROM ziparchives
			  WHERE folder_id='" . $this->folder_id . "'
			  ";
		  $db->query($removeZipSQL)
			  or die ('Delete ziparchives statement failed.');
		  $removeFolderSQL = "DELETE FROM folders
			  WHERE folder_id='" . $this->folder_id . "'
			  ";
		  $db->query($removeFolderSQL)
			  or die ('Delete folders statement failed: Entry not deleted');
		}
	}

	function addZip(
		$zip_name,
		$zip_url
		) {
			global $db;
			$this->zip_name = $zip_name;
			if(!isset($this->duplicate)){
			  $this->duplicateSQL = "SELECT folder_id, zip_name
				FROM ziparchives
				WHERE zip_name='"
				. $this->zip_name . "'
				";
			  $this->duplicateResult = $db->query($this->duplicateSQL);
			  $this->duplicate_zip = $this->duplicateResult->fetchArray();
			  $this->duplicateZipname = $this->duplicate_zip[0]['zip_name'];
			  $this->duplicateZipFolderID = $this->duplicate_zip[0]['folder_id'];
			}
			$this->zip_url = $zip_url;
			$this->date = date("Y-m-d H:i:s");
			$this->addzip_sql = "INSERT INTO ziparchives
				      (folder_id, zip_name, zip_url)
				      VALUES (
					'$this->folder_id',
					'$this->zip_name',
					'$this->zip_url'
				      )";
			$this->updatezip_sql = "UPDATE ziparchives
					SET `zip_date` =
					'$this->date' WHERE
					`folder_id`='$this->duplicateZipFolderID'
					";
			if($this->zip_name === $this->duplicateZipname){
			  $this->updatezip_result = $db->query($this->updatezip_sql)
				or die ('UPDATE statement failed');
			} else {
			  $this->addzip_result = $db->query($this->addzip_sql)
				or die ('INSERT statement failed');
			  $this->zipid = $this->addzip_result->getId();
			}
	}

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

}

// FileList and ActOnSingleFile classes for administration of files table.
class FileList {
	private $sql,
		$query,
		$result;
	public	$filelist,
		$id,
		$name,
		$size,
		$type,
		$url,
		$title,
		$description,
		$date;

	function __construct(
		) {
			global $db;
			$this->sql = "SELECT * FROM files";
			$this->result = $db->query($this->sql);
			$this->filelist = $this->result->fetchArray();
			if(is_array($this->filelist)) {
			  foreach($this->filelist as $key => $file) {
				extract($this->filelist[$key]);
				if(isset($id)){
				  $this->id[$key] = $id;
				}
				if(isset($name)){
				  $this->name[$key] = $name;
				}
				if(isset($size)){
				  $this->size[$key] = $size;
				}
				if(isset($type)){
				  $this->type[$key] = $type;
				}
				if(isset($url)){
				  $this->url[$key] = $url;
				}
				if(isset($title)){
				  $this->title[$key] = $title;
				}
				if(isset($description)){
				  $this->description[$key] = $description;
				}
				if(isset($date)){
				  $this->date[$key] = $date;
				}
			  }
			}
		}
}

// ActOnSingleFile class. For adding and changing a file entry.
class ActOnSingleFile {
	private $change_file_sql,
					$change_file_id,
					$change_file_name,
					$change_file_url,
					$change_file_folder_id
		;
	public	$change_file_mysql_error
		;

	function __construct(
	                ) {

	}

	function addFile(){
		global $db;
		$this->name = $_POST['file_name'];
		$this->name = addslashes($this->name);
		if(!isset($this->duplicate)){
		  $this->duplicateSQL = "SELECT name FROM files WHERE name='" . $this->name . "'";
		  $this->duplicateResult = $db->query($this->duplicateSQL);
		  $this->duplicate = $this->duplicateResult->fetchArray();
		  $this->duplicate = $this->duplicate[0]['name'];
		}
		$this->size = $_POST['file_size'];
		$this->type = $_POST['file_type'];
		$this->type = addslashes($this->type);
		$this->url = $_POST['file_url'];
		$this->url = addslashes($this->url);
		$this->title = $_POST['file_title'];
		$this->title = addslashes($this->title);
		$this->description = $_POST['file_description'];
		$this->description = addslashes($this->description);
		$this->addSQL = "INSERT INTO files (name, size, type, url, title, description) VALUES ('$this->name', '$this->size', '$this->type', '$this->url', '$this->title', '$this->description')";
		if($_POST['file_name'] === $this->duplicate){
			$this->duplicate = null;
			return false;
		} else {
			$this->addresult = $db->query($this->addSQL);
			$this->entryid = $this->addresult->getId();
		}
	}

	function changeFileSystemDB(
		$file_id,
		$file_name,
		$file_url,
		$file_folder_id
		){
		global $db;
		$this->change_file_id = $file_id;
		$this->change_file_name = $file_name;
		//$this->change_file_name = addslashes($name);
		$this->change_file_url = $file_url;
		//$this->change_file_url = addslashes($url);
		$this->change_file_folder_id = $file_folder_id;
		$this->change_file_sql = "UPDATE files SET `name`='$this->change_file_name', `url`='$this->change_file_url', `folder_id`='$this->change_file_folder_id' WHERE `id`='$this->change_file_id'";
		//$db->query($changeSQL) or die ('Update statement failed: Entry not updated');
		$this->change_file_result = $db->query($this->change_file_sql);
		if($this->change_file_result->isError()) {
			$this->change_file_mysql_error = $change_file_result->queryErrorMessage();
			return false;
		} else {
			return true;
		}
	}

	function changeFileDB(
		$file_id,
		$file_title,
		$file_description
		){
		global $db;
		$change_id = $file_id;
		$title = $file_title;
		$title = addslashes($title);
		$description = $file_description;
		$description = addslashes($description);
		$changeSQL = "UPDATE files SET `title`='$title', `description`='$description' WHERE `id`='$change_id'";
		$db->query($changeSQL) or die ('Update statement failed: Entry not updated');
	}

	function removeFile(
		$file_id
		){
		  global $db;
		  $remove_id = $file_id;
		  $removeSQL = "DELETE FROM files WHERE id='" . $remove_id . "'";
		  $db->query($removeSQL) or die ('Delete statement failed: Entry not deleted');
	}

	/* DEPRECATED -- Remove in future versions
	 * This function has been placed in the base functions.php file
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
}

?>
