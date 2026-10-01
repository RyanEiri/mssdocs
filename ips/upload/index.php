<?php
chdir('..');
require_once(getcwd().'/php/start_sess.php');
include getcwd().'/php/boot.php';
$login_cookie = new UserCookie();
$login_cookie->DeleteIt();
if($login_cookie->CheckIt()) {
	define("USERNAME", $login_cookie->username);
	$userval = new UserGrab(USERNAME);
	define("ADMIN_STATUS", $userval->admin);
/*
 * jQuery File Upload Plugin PHP
 * https://github.com/blueimp/jQuery-File-Upload
 *
 * Copyright 2010, Sebastian Tschan
 * https://blueimp.net
 *
 * Licensed under the MIT license:
 * http://www.opensource.org/licenses/MIT
 */

// Where the files go: the folder the page names in `dir` (files/..., relative to upload/, like the other endpoints), else the user's own folder.
// Anyone may name their own folder (files/<username> and below); only administrators may name another one.
function ips_upload_target() {
	if (!isset($_REQUEST['dir']) || $_REQUEST['dir'] === '') {
		return USERNAME;
	}
	$real = ips_resolve_dir($_REQUEST['dir']);
	$root = ips_files_root();
	if ($real === false || $real === $root || !(ADMIN_STATUS || ips_in_users_tree($real, USERNAME))) {
		http_response_code(403);
		header('Content-Type: application/json');
		echo json_encode(['files' => [['name' => '', 'error' => 'You can only upload into your own folder (files/' . USERNAME . ').']]]);
		exit;
	}
	return substr($real, strlen($root) + 1);
}
define('IPS_UPLOAD_TARGET', ips_upload_target());

$options = array(
	'delete_type' => 'POST',
	'db_host' => DB_HOST,
	'db_user' => DB_USER,
	'db_pass' => DB_PASS,
	'db_name' => DB_NAME,
	'db_table' => 'files',
	'user_dirs' => true
);

error_reporting(E_ALL | E_STRICT);
require('UploadHandler.php');

class CustomUploadHandler extends UploadHandler {

	protected function initialize(){
	  $this->db = new mysqli(
	    $this->options['db_host'],
	    $this->options['db_user'],
	    $this->options['db_pass'],
	    $this->options['db_name']
	  );
	  parent::initialize();
	  $this->db->close();
	}

	protected function get_user_id() {
		return IPS_UPLOAD_TARGET;
	}

	protected function handle_form_data($file, $index) {
	  // The file browser sends no title or description (those are edited afterwards); the columns don't take NULL.
	  $file->title = (string) (@$_REQUEST['title'][$index] ?? '');
	  $file->description = (string) (@$_REQUEST['description'][$index] ?? '');
	}

	protected function handle_file_upload($uploaded_file, $name, $size, $type, $error, $index = null, $content_range = null) {
	  $file = parent::handle_file_upload(
	    $uploaded_file, $name, $size, $type, $error, $index, $content_range);
	  $file->name = $this->get_user_path().$file->name;
	  $file->url = $this->get_full_url()."/files/".$file->name;
	  if (empty($file->error)) {
	    $sql = 'INSERT INTO `'.$this->options['db_table']
	      .'` (`name`, `size`, `type`, `url`, `title`, `description`)'
	      .' VALUES (?, ?, ?, ?, ?, ?)';
	    $query = $this->db->prepare($sql);
	    $query->bind_param(
	      'sissss',
	      $file->name,
	      $file->size,
	      $file->type,
	      $file->url,
	      $file->title,
	      $file->description
	    );
	    $query->execute();
	    $file->id = $this->db->insert_id;
	    $this->attach_to_folder($file->id, dirname($file->name) === '.' ? '' : dirname($file->name));
	  }
	  return $file;
	}

	// The catalogue groups files by folder row (batch edit and the folder listings read it), so link the new file to its folder's.
	private function attach_to_folder($file_id, $folder) {
	  if ($folder === '') {
	    return;
	  }
	  $find = $this->db->prepare('SELECT `folder_id` FROM `folders` WHERE `folder_name`=?');
	  $find->bind_param('s', $folder);
	  $find->execute();
	  $find->bind_result($folder_id);
	  if (!$find->fetch()) {
	    $find->close();
	    $make = $this->db->prepare('INSERT INTO `folders` (`folder_name`) VALUES (?)');
	    $make->bind_param('s', $folder);
	    $make->execute();
	    $folder_id = $this->db->insert_id;
	  } else {
	    $find->close();
	  }
	  $link = $this->db->prepare('UPDATE `files` SET `folder_id`=? WHERE `id`=?');
	  $link->bind_param('ii', $folder_id, $file_id);
	  $link->execute();
	}

	protected function set_additional_file_properties($file) {
	  parent::set_additional_file_properties($file);
	  if ($_SERVER['REQUEST_METHOD'] === 'GET') {
	    $sql = 'SELECT `id`, `type`, `title`, `description` FROM `'
	      .$this->options['db_table'].'` WHERE `name`=?';
	    $query = $this->db->prepare($sql);
	    $query->bind_param('s', $file->name);
	    $query->execute();
	    $query->bind_result(
	      $id,
	      $type,
	      $title,
	      $description
	    );
	    while ($query->fetch()) {
	      $file->id = $id;
	      $file->type = $type;
	      $file->title = $title;
	      $file->description = $description;
	    }
	  }
	}

	public function delete ($print_response = true) {
	  $response = parent::delete(false);
	  foreach ($response as $name => $deleted) {
	    if ($deleted) {
	      $name = $this->get_user_path().$name;
	      $sql = 'DELETE FROM `'
	        .$this->options['db_table'].'` WHERE `name`=?';
	      $query = $this->db->prepare($sql);
	      $query->bind_param('s', $name);
	      $query->execute();
	    }
	  }
	  return $this->generate_response($response, $print_response);
	}
}

$upload_handler = new CustomUploadHandler($options);

}
