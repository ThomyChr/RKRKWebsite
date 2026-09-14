<?php
// http://technotip.com/1808/simple-basic-pagination-navigation-php-mysql/
	// includes
	include_once('_private/dbi.php');
	include_once('_private/library.php');
	include_once('_private/const.php');
	include_once('create_pdf.php');
    
	$_SESSION['StandardSQL'] = 'yes';
	
	$FormDef = Array();
    
	//FormDef:                      0 DB Fieldname,        1 Text, 				    2 Var Name,    3 form type, 4 show, 5 show field, 6 maxlength, 7 size, 8 default, 9 options, 10 onkeyup, 11 onchange, 12 mask, 13 align, 14 witdh, 15 link page, 16 link IDfield, 17 mask, 18 mandatory
	/*00*/ array_push($FormDef,array(KontoNummer, 	'Kontonummer', 		KN, 			text, 		'yes',	'KontoNummer',		'',		'',		'',		'',		'',		'',		'',		'right', '60', '', '', '', 'yes')); 
	/*01*/ array_push($FormDef,array(Titel, 		'Titel', 			title, 			autocomplet,'yes',	'Titel',			'',		'',		'',		'',		'',		'',		'',		'left', '150', '', '', '', 'yes'));
	/*02*/ array_push($FormDef,array(Titel_Kurz,    'Kurztitel', 		shortitle,		autocomplet,'yes',	'Titel_Kurz',		'',		'',		'',		'',		'',		'',		'',		'right', '60', '', '', '', 'yes'));
	/*03*/ array_push($FormDef,array(Bezeichnung, 	'Beschreibung', 	description, 	text,		'yes',	'Bezeichnung',		'',		'',		'',		'',		'',		'',		'',		'left', '150', '', '', '', 'yes'));
	/*04*/ array_push($FormDef,array(ShortCut, 		'ShortCut', 		short_cut,  	text, 		'yes',	'ShortCut',			'10', '10',		'',		'',		'',		'',		'',		'right', '150', '', '', '', 'yes'));
	/*05*/ array_push($FormDef,array(Aktiv, 		'Aktiv', 			active,			checkbox,	'yes',	'Aktiv',			'',		'',		'',		'',		'',		'',		'',		'center', '40', '', '', '', 'yes'));
	/*06*/ array_push($FormDef,array(Verwendung, 	'Verwendung', 		usage, 			text,		'yes',	'Verwendung',		'',		'',		'',		'',		'',		'',		'',		'left', '250', '', '', '', 'yes'));
	/*07*/ array_push($FormDef,array(PerBenutzer, 	'Pro Benutzer', 	peruser, 		checkbox,	'yes',	'PerBenutzer',		'',		'',		'',		'',		'',		'',		'',		'center', '40', '', '', '', 'yes'));
	/*08*/ array_push($FormDef,array(Budget, 	    'Budget', 			budget, 		checkbox,	'yes',	'Budget',			'',		'',		'',		'',		'',		'',		'',		'left', '40', '', '', '', 'yes'));
	/*09*/ array_push($FormDef,array(BudgetKtoNr,   'Budget Number', 	budget_acc_nr, 	text,		'yes',	'BudgetKtoNr',		'',		'',		'',		'',		'',		'',		'',		'right', '60', '', '', '', 'yes'));
	/*10*/ array_push($FormDef,array(Buchungen, 	'Buchungen', 		bookings, 		checkbox,	'yes',	'Buchungen',		'',		'',		'',		'',		'',		'',		'',		'left', '40', '', '', '', 'yes'));	


	$Sql ="SELECT Titel FROM Kontenplan GROUP BY Titel ORDER BY Titel";
	$SqlRes = mySqli_query($conn,$Sql);
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
	
	// default variables
	if (!isset($_SESSION['EntryPerPage']))
	  $_SESSION['EntryPerPage'] = $DefLinePerPage;
    $TableTitle="Kontenplanliste";
	$Table="Kontenplan";
	$Icon="images\money.gif";
	$ID_FieldName="KontoNummer";
	$ID_FormRow=0;
	
	m_echo("<html>", $HtmlPg_Otpt);
	m_echo("<head>", $HtmlPg_Otpt);
	m_echo("<LINK href=\"template/main_style.php\" rel=\"stylesheet\" type=\"text/css\">", $HtmlPg_Otpt);
	m_echo("<META HTTP-EQUIV=\"content-type\" CONTENT=\"text/html; charset=ISO-8859-1\">", $HtmlPg_Otpt);
	m_echo(" <title>$TableTitle</title></head>", $HtmlPg_Otpt);
	m_echo("</head>", $HtmlPg_Otpt);
	m_echo("<body>", $HtmlPg_Otpt);
	m_echo("<H1>$TableTitle</H1>", $HtmlPg_Otpt, $Print_Pg_Otpt, $Pdf_Pg_Otpt, $Mail_Pg_Otpt);
	
	
    show_list($FormDef, $Table, '','',$TableTitle, $Icon, $ID_FormRow,  '', '', $_SESSION['line_edit']);	
?>
	

	
