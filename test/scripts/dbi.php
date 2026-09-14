<?php 
$db_host = 'login-57.hoststar.ch';
$db_user = 'ch28643_web131';
$db_pwd = 'Laurin05';
$database = 'ch28643_usr_web131_7';

  $conn = mysqli_connect($db_host, $db_user, $db_pwd);
  $db = mysqli_select_db($conn,$database);
?>