<?php
function keymaker($id){ 
  $secretkey='ZDVlYzBjNWNhNDExNTRjYWQyNmU4N2M1';
  $key=md5($id.$secretkey);
  return $key;
}
?>
