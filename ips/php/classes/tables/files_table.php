<?php

// Every statement in this file binds its values (see SQL::query) rather than splicing them into the SQL string.
// str_params() turns plain values into the ['type' => 's', 'value' => ...] list that query() expects.
function str_params(...$values) {
	return array_map(function($value) {
		return ['type' => 's', 'value' => $value];
	}, $values);
}

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
			WHERE name=?
			LIMIT 1";
		$this->result = $db->query($this->sql, str_params($this->name));
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
		$files,
		$subfolders,
		$subfolders_files,
		$new_subfolder_base,
		$subfolder_pattern;

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
			$this->grabFolderSubfolders();
	}

	function grabFolder(){
		global $db;
		$this->grab_sql = "SELECT folders.folder_id, folders.folder_name, ziparchives.zip_name, ziparchives.zip_url, ziparchives.zip_date
			FROM folders
			LEFT JOIN ziparchives ON
			ziparchives.folder_id = folders.folder_id
			WHERE folders.folder_name=?
			LIMIT 1";
		$this->grab_result = $db->query($this->grab_sql, str_params($this->folder_name));
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
		$create_sql = "INSERT INTO folders (folder_name) VALUES (?)";
		$create_result = $db->query($create_sql, str_params($this->folder_name));
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
			SET `folder_name` = ?
			WHERE `folder_id` = ?";
		$db->query($update_folder_sql, str_params($update_folder, $this->folder_id))
		  or die ('Update folder query failed!');
		if(is_array($this->files)){
			foreach ($this->files as $key => $value) {
				$file_name = basename($value['name']);
				$file_name = $update_folder.'/'.$file_name;
				$file_url = USER_FILES_URL.$file_name;
				$file_id = $value['id'];
				$update_files_sql = "UPDATE files
					SET `name` = ?,
					`url` = ?
					WHERE `id` = ?";
				$db->query($update_files_sql, str_params($file_name, $file_url, $file_id))
				  or die ('Folder file update query failed.');
			}
		}
		if(is_array($this->subfolders)){
			foreach ($this->subfolders as $key => $value) {
				$sql = "SELECT files.id, files.name, files.size, files.type, files.url, files.title, files.description, files.date, folders.folder_id, folders.folder_name
						FROM `files`
						LEFT JOIN `folders` ON
						folders.folder_id = files.folder_id
						WHERE files.folder_id=?
						ORDER BY files.name";
				$result = $db->query($sql, str_params($value['folder_id']))
					or die ('Subfolder files SELECT statement failed.');
				$query = $result->fetchArray();
				if(is_array($this->subfolders_files)){
					$this->subfolders_files = array_merge($this->subfolders_files, $query);
				} else {
					$this->subfolders_files = $query;
				}
			}
		}
		if(is_array($this->subfolders_files)){
			foreach ($this->subfolders_files as $key => $value) {
				$subfolder_pattern = '/'.preg_quote($this->folder_name, '/').'\//';
				$new_subfolder_file_base = preg_replace($subfolder_pattern, '', $value['name']);
				$file_name = $update_folder.'/'.$new_subfolder_file_base;
				$update_subfolder = dirname($file_name);
				$file_url = USER_FILES_URL.$file_name;
				$file_id = $value['id'];
				$update_sql = "UPDATE `files`
					SET 	`name` 	= ?,
								`url` 	= ?
					WHERE `id` 		= ?";
				$db->query($update_sql, str_params($file_name, $file_url, $file_id))
					or die ('Subfolder file update query failed.');
				$old_subfolder = dirname($value['name']);
				$subfolder_grab_id_sql = "SELECT `folder_id`, `folder_name`
					FROM `folders`
					WHERE `folder_name`=?
					LIMIT 1";
				$subfolder_grab_id_result = $db->query($subfolder_grab_id_sql, str_params($old_subfolder));
				$subfolder_grab_id_query = $subfolder_grab_id_result->fetchArray();
				if(is_array($subfolder_grab_id_query)) {
					extract($subfolder_grab_id_query[0]);
				}
				if(isset($folder_id) && $folder_name !== $update_subfolder){
					$subfolder_push_new_sql = "UPDATE `folders`
						SET `folder_name` = ?
						WHERE `folder_id` = ?";
					$db->query($subfolder_push_new_sql, str_params($update_subfolder, $folder_id))
						or die ('Update subfolder query failed!');
				}
			}
		}
	}

	function grabFolderFiles(){
		global $db;
		$sql = "SELECT files.id, files.name, files.size, files.type, files.url, files.title, files.description, files.date, folders.folder_id, folders.folder_name
				FROM files
				LEFT JOIN folders ON
				folders.folder_id = files.folder_id
				WHERE files.folder_id=?
				ORDER BY files.name";
		$result = $db->query($sql, str_params($this->folder_id))
		  or die ('Select statement failed for grabbing folder files.');
		$query = $result->fetchArray();
		$this->files = $query;
	}

	function grabFolderSubfolders(){
		global $db;
		// A folder name can contain % or _ (html_templates), which LIKE would read as wildcards.
		$parent_folder_search = addcslashes($this->folder_name, '\\%_').'/%';
		$sql = "SELECT * FROM folders WHERE folder_name LIKE ?";
		$result = $db->query($sql, str_params($parent_folder_search))
			or die ('Select statement failed for grabbing folder subfolders.');
		$query = $result->fetchArray();
		$this->subfolders = $query;
	}

	function updateFolderFiles(
		$files_array
		){
		global $db;
		$id = "";
		$url = "";
		$name = "";
		$title = "";
		$description = "";
		if(is_array($files_array)) {
		  foreach($files_array as $key => $value) {
		    $id = $value['id'];
				if(isset($value['url'])){
					$url = $value['url'];
					$sql_url = "UPDATE files SET `url` = ? WHERE id=?";
					$db->query($sql_url, str_params($url, $id))
				or die ('Update url failed.');
				}
		    if(isset($value['name'])){
		    	$name = $value['name'];
					$sql_name = "UPDATE files SET `name` = ? WHERE id=?";
					$db->query($sql_name, str_params($name, $id))
			  or die ('Update name failed.');
		    }
		    if(isset($value['title'])){
		    	$title = $value['title'];
					$sql_title = "UPDATE files SET `title` = ? WHERE id=?";
					$db->query($sql_title, str_params($title, $id))
			  or die ('Update title failed.');
		    }
		    if(isset($value['description'])){
		    	$description = $value['description'];
					$sql_description = "UPDATE files SET `description` = ? WHERE id=?";
					$db->query($sql_description, str_params($description, $id))
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
		  $removeZipSQL = "DELETE FROM ziparchives WHERE folder_id=?";
		  $db->query($removeZipSQL, str_params($this->folder_id))
			  or die ('Delete ziparchives statement failed.');
		  $removeFolderSQL = "DELETE FROM folders WHERE folder_id=?";
		  $db->query($removeFolderSQL, str_params($this->folder_id))
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
				WHERE folder_id=?";
			  $this->duplicateResult = $db->query($this->duplicateSQL, str_params($this->folder_id));
			  $this->duplicate_zip = $this->duplicateResult->fetchArray();
			  $this->duplicateZipname = $this->duplicate_zip[0]['zip_name'];
			  $this->duplicateZipFolderID = $this->duplicate_zip[0]['folder_id'];
			}
			$this->zip_url = $zip_url;
			$this->date = date("Y-m-d H:i:s");
			$this->addzip_sql = "
				      INSERT INTO ziparchives
				      (folder_id, zip_name, zip_url)
				      VALUES (?, ?, ?)";
			$this->updatezip_sql = "
					UPDATE ziparchives SET
					`zip_date` = ?,
					`zip_url` = ?,
					`zip_name` = ?
					WHERE `folder_id`=?";
			if($this->zip_name === $this->duplicateZipname){
			  $this->updatezip_result = $db->query($this->updatezip_sql, str_params($this->date, $this->zip_url, $this->zip_name, $this->duplicateZipFolderID))
				or die ('UPDATE statement failed');
			} else {
			  $this->addzip_result = $db->query($this->addzip_sql, str_params($this->folder_id, $this->zip_name, $this->zip_url))
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
		if(!isset($this->duplicate)){
		  $this->duplicateSQL = "SELECT name FROM files WHERE name=?";
		  $this->duplicateResult = $db->query($this->duplicateSQL, str_params($this->name));
		  $this->duplicate = $this->duplicateResult->fetchArray();
		  $this->duplicate = $this->duplicate[0]['name'];
		}
		$this->size = $_POST['file_size'];
		$this->type = $_POST['file_type'];
		$this->url = $_POST['file_url'];
		$this->title = $_POST['file_title'];
		$this->description = $_POST['file_description'];
		$this->addSQL = "INSERT INTO files (name, size, type, url, title, description) VALUES (?, ?, ?, ?, ?, ?)";
		if($_POST['file_name'] === $this->duplicate){
			$this->duplicate = null;
			return false;
		} else {
			$this->addresult = $db->query($this->addSQL, str_params($this->name, $this->size, $this->type, $this->url, $this->title, $this->description));
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
		$this->change_file_url = $file_url;
		$this->change_file_folder_id = $file_folder_id;
		$this->change_file_sql = "UPDATE files SET `name`=?, `url`=?, `folder_id`=? WHERE `id`=?";
		$this->change_file_result = $db->query($this->change_file_sql, str_params($this->change_file_name, $this->change_file_url, $this->change_file_folder_id, $this->change_file_id));
		if($this->change_file_result->isError()) {
			$this->change_file_mysql_error = $this->change_file_result->queryErrorMessage();
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
		$description = $file_description;
		$changeSQL = "UPDATE files SET `title`=?, `description`=? WHERE `id`=?";
		$db->query($changeSQL, str_params($title, $description, $change_id)) or die ('Update statement failed: Entry not updated');
	}

	function removeFile(
		$file_id
		){
		  global $db;
		  $remove_id = $file_id;
		  $removeSQL = "DELETE FROM files WHERE id=?";
		  $db->query($removeSQL, str_params($remove_id)) or die ('Delete statement failed: Entry not deleted');
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
