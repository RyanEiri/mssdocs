<?php
chdir('..');
require_once(getcwd().'/php/start_sess.php');
include getcwd().'/php/boot.php';

// Initialize constants
define("BASE_IMAGE_PATH", 'files/');

// Initialize GET variables
if(!empty($_GET['image'])){
	$image_file = BASE_IMAGE_PATH.$_GET['image'];
}
if(!empty($_GET['thumbnail'])){
  $thumbnail = $_GET['thumbnail'];
}

$image = new Imagick($image_file);
if(!isset($thumbnail)){
  $geo=$image->getImageGeometry();
  if($geo['width'] > $geo['height']){
    $image->adaptiveResizeImage(1024, 768);
  } else {
    $image->adaptiveResizeImage(768, 1024);
  }
}
autorotate($image);

header('Content-Type: image/'.$image->getImageFormat());
echo $image->getImageBlob();

session_unset();
?>
