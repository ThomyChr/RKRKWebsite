<?php 
  // mysql at hoststar
  $db_host = 'login-57.hoststar.ch';
  $db_user = 'web131';
  $db_pwd = 'Laurin05';
  $database = 'ch28643_drkr_1';

  $conn = mysql_connect($db_host, $db_user, $db_pwd);
  $db = mysql_select_db($database, $conn);
?>