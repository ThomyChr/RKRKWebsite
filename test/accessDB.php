<?php
session_start();
	
// http://technotip.com/1808/simple-basic-pagination-navigation-php-mysql/
	// includes
	include_once('scripts/dbi.php');
    
	$_SESSION['StandardSQL'] = 'yes';
	echo "im on" . chr(10);
	
/*
	$FormDef = Array();
    

	$Sql ="SELECT Titel FROM Kontenplan GROUP BY Titel ORDER BY Titel";
	//$SqlRes = mySqli_query($conn,$Sql);
      if (!$SqlRes) {
        die('Ungültige Anfrage/01: ('  . mySqli_errno($conn). ':' . mySqli_error($conn). ') in Sql: '. $Sql);
      }	
      $ResArr = array();
	  while($Row = mySqli_fetch_array($SqlRes)) 
	  {
	    array_push($ResArr,$Row);	
	  }
	$FormDef[1][9] =$ResArr;
	
	$Sql ="SELECT Titel_Kurz FROM Kontenplan GROUP BY Titel_Kurz ORDER BY Titel_Kurz";
	$SqlRes = mySqli_query($conn,$Sql);
      if (!$SqlRes) {
        die('Ungültige Anfrage/01: ('  . mySqli_errno($conn). ':' . mySqli_error($conn). ') in Sql: '. $Sql);
      }	
      $ResArr = array();
	  while($Row = mySqli_fetch_array($SqlRes)) 
	  {
	    array_push($ResArr,$Row);	
	  }
	$FormDef[2][9] =$ResArr;
*/	
?>
	

	
