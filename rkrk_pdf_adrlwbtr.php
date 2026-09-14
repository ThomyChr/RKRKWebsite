<?php
	session_start();
	include_once('scripts/rkrk_gen.php');
	include_once('scripts/rkrk_csvfiles.php');
	require('scripts/fpdf/fpdf.php');

	//check impropriate IP addresses accessing page
	CheckIPandExit();
	if (function_exists('rkrk_pdf_maybe_emit_html_shell')) {
		rkrk_pdf_maybe_emit_html_shell('Bewirtschafter');
	}


// ========================
// Functions
// ========================
	// multidimensional sorting of array
    // aasort($your_array, "order");

	
function aasort_loc (&$array, $key) {
    $sorter = array();
    $ret = array();
    reset($array);
		foreach ($array as $ii => $va) {
			$sorter[$ii] = $va[$key];
		}
		asort($sorter);
		foreach ($sorter as $ii => $va) {
			$ret[$ii] = $array[$ii];
		}
		$array = $ret;
	}

Function SortAdrList ($data, $ColSort="Name") {
	$NewData = Array();
	foreach ($data as $key => $dataline) {
		$NewData[$dataline["ID"]] = $dataline;
	}
	//sort data
	aasort_loc($NewData, "Typ");
	aasort_loc($NewData, $ColSort);
	//pre_r($NewData);
	return $NewData;
}


// ========================
// General settings 
// ========================

	//$csv_desc = ';';
	$csv_file= $RKRK_LWB_ContactFile;

	$titleimage = "images/22color/202121%20Logo-RKRKZ.png";
	$MaxEntriesInTable = 290;
// Column headings
	$PageColumn = Array(
						"ID" => array ( "Field" => "ID",
											"Name" => "ID",
											"Witdh" => "10",
											"Show" => false),
						"Initialen" => array ( "Field" => "Initialen",
											"Name" => "Initialen",
											"Witdh" => "10",
											"Show" => false),
						"Betrieb" 	=> array ( "Field" => "Betrieb",
											"Name" => "Betrieb",
											"Witdh" => "20",
											"Show" => true),
						"Jagdgebiet" => array ( "Field" => "Jagdgebiet",
											"Name" => "JG",
											"Witdh" => "12",
											"Show" => true),
						"Name" => array ( "Field" => "Name",
											"Name" => "Name",
											"Witdh" => "40",
											"Show" => true),
						"Kontaktnummer" => array ( "Field" => "Kontaktnummer",
											"Name" => "Kontaktnummer",
											"Witdh" => "30",
											"Show" => true),
						"P Telefon" => array ( "Field" => "P Telefon",
											"Name" => "P Telefon",
											"Witdh" => "",
											"Show" => false),
						"Mail" => array ( "Field" => "Mail",
											"Name" => "Mail",
											"Witdh" => "25",
											"Show" => false),
						"Adresse" => array ( "Field" => "Adresse",
											"Name" => "Adresse",
											"Witdh" => "75",
											"Show" => true),
						"Typ" => array ( "Field" => "Typ",
											"Name" => "Typ",
											"Witdh" => "15",
											"Show" => false),
						"Bemerkung" => array ( "Field" => "Bemerkung",
											"Name" => "Bemerkung",
											"Witdh" => "38",
											"Show" => false),
						"Felder" => array ( "Field" => "Felder",
											"Name" => "Felder",
											"Witdh" => "100",
											"Show" => true),
									);
	$header = array('Initialen', 'Betrieb', 'Name', 'Kontaktdaten', 'Adresse', 'Felder');
  

if (isset($_GET["TypFilter"])) {
	$TypFilter= $_GET["TypFilter"];
} else {
	$TypFilter= "";
}  

if (isset($_GET["RegFilter"])) {
	$RegFilter= $_GET["RegFilter"];
} else {
	$RegFilter= "";
}  

if (isset($_GET["Search"])) {
	$Search= $_GET["Search"];
} else {
	$Search= "";
}  

if (isset($_GET["Sortieren"])) {
	$ColSort = $_GET["Sortieren"];
} else {
	$ColSort = "Name";
}
	
	SetMissionLocation();
	$MissionSelectionParameter = "&MDB=" . $RKRKMissionSelected;

	//Read CSV file
	//transferGoogleSheetToCSV();
	$data = csv_to_array($csvMissionLocation.$csv_file,$csv_desc);
	$data = SortAdrList($data, $ColSort);
	//pre_r($data);


	//Write to LOGfile
	WriteToLog("do", basename($_SERVER['PHP_SELF']), "Pdf ",$RegionSelected,$FFilter,"");


class PDF extends FPDF
{


// Page header
function Header()
{
	global $titleimage, $IconLoc, $RKRKMissionSelected, $PageColumn, $RegFilter, $Search;
    // Logo
    //$this->Image($titleimage,10,6,30);
    $this->Image($IconLoc."/logo.jpg",265,10,22);
    //$this->Image("202121 Logo-RKRKZ.png",175,6,25);
    // Arial bold 15
    $this->SetFont('Arial','B',13);
    $this->Cell(50,10,"Adressen Landwirte",0,0, 'L');
	if ($RKRKMissionSelected == "3T") {
		if (file_exists($IconLoc."/mavic3T.jpg")) {
			$this->Image($IconLoc."/mavic3T.jpg",$this->GetX()-50,20,10,0,'');
		}
	}
	if ($RKRKMissionSelected == "M2") {
		if (file_exists($IconLoc."/mavic2.jpg")) {
			$this->Image($IconLoc."/mavic2.jpg",$this->GetX()-50,20,10,0,'');
		}
	}
	if ($RKRKMissionSelected == "D") {
		if (file_exists($IconLoc."/debug.jpg")) {
			$this->Image($IconLoc."/debug.jpg",$this->GetX()-50,20,10,0,'');
		}
	}	
    // Move to the right
    $this->Cell(50);
    // Line break
    $this->Ln(20);
	$this->SetFillColor(160,194,145);
	$this->SetDrawColor(160,194,145);
	$this->SetLineWidth(.3);
	$this->SetFont('Arial','B',8);
	// Header
	foreach ($PageColumn as $Col) {
	//for($i=0;$i<count($header);$i++)
		if ($Col["Show"]) {
			$this->Cell($Col["Witdh"],5,$Col["Name"],1,0,'C',true);
		}
    }
	$this->Ln();
}

// Page footer
function Footer()
{
    // Position at 1.5 cm from bottom
    $this->SetY(-15);
    // Arial italic 8
    $this->SetFont('Arial','I',8);
    // Page number
    $this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
}

// Colored table
function FancyTable($header, $data)
{
	global $PageColumn , $Search, $RegFilter, $TypFilter,  $MaxEntriesInTable;
	$localsearchSDZ  = Array("Ã¼","Ã¶", "Ã?", "Ã¤", "Ã?Â¼", "Ã?Â¤");
	$localreplaceSDZ = Array("ü", "ö", "Ü" , "ä" , "ü"   ,"ä");

 // Colors, line width and bold font
    $this->SetFillColor(160,194,145);
    $this->SetDrawColor(160,194,145);
    $this->SetLineWidth(.3);
    $this->SetFont('','B');
    // Header
	$lineheight= 5;
/*
    for($i=0;$i<count($header);$i++)
        $this->Cell($w[$i],7,$header[$i],1,0,'C',true);
    $this->Ln();
*/
    // Color and font restoration
    $this->SetFillColor(225,239,217);
    $this->SetTextColor(0);
    $this->SetFont('');
	$FFArray = explode(" ",str_replace(",", " ", str_replace(";", " ", $FFilter)));
	//echo RegionSelected . " " . $RegionSelected . "<br>";
	//echo FFilter . " " . $FFilter . "<br>";
	//pre_R($FFArray);
    // Data
    $fill = false;
	$Num = 0;
	$NumEntry = 0;
	//pre_r(explode(",",$RegFilter));
	//echo $Search;
	foreach ($data as $Key => $dataline) {
		if ( 
				($Search == "" 
				or like($dataline["Name"], $Search)
				or like($dataline["Betrieb"], $Search)
				or like($dataline["Adresse"], $Search)
				or like($dataline["Felder"], $Search)
				or like($dataline["Bemerkung"], $Search)) 
				//and in_array($dataline["Typ"], $Search)
				and (
				($RegFilter=="") 
				or in_array($dataline["Jagdgebiet"], explode(",",$RegFilter))
				or (count(explode(",",$RegFilter)) != count(array_diff(explode(",",$RegFilter), explode(",",$dataline["Jagdgebiet"]))))
				//or !array_diff(explode(",",$RegFilter), explode(",",$dataline["Jagdgebiet"]))
				or in_array("ALL", explode(",",$RegFilter))
				)
				and (($TypFilter=="")  
				or (in_array($dataline["Typ"], explode(",",$TypFilter))
				or  in_array("ALL", explode(",",$TypFilter))))

			) 
			{

			$Num++;
			//echo "Debug: ". $dataline["ID"] . " - " .  $dataline["Name"] . "<br>";
//Array
//(
//    [0] => Array
//        (
//            [ID] => ADR0002
//            [Initialen] => ZG 8042
//            [Betrieb] => ZG 8042
//            [Jagdgebiet] => ZG4
//            [Name] => Abegg Markus
//            [Kontaktnummer] => +41 79 639 05 01
//            [P Telefon] => 
//            [Mail] => 
//           [Adresse] => Unterschwä®¤i 2, 6318 Walchwil, Schweiz
//            [Typ] => Bauer
//            [Bemerkung] => 
//            [Felder] => ZG4-006, ZG4-007
//        )
//
//    [1] => Array
//        (
//            [ID] => ADR0001
//            [Initialen] => ZG 6006
//            [Betrieb] => ZG 6006
//            [Jagdgebiet] => ZG6
//            [Name] => Abä£¨erli Sonja
//            [Kontaktnummer] => +41 79 574 32 30
//            [P Telefon] => 
//            [Mail] => 
//            [Adresse] => Hinter-Grod, 6315 Alosen, Schweiz
//            [Typ] => Bauer
//            [Bemerkung] => 
//            [Felder] => ZG6-116
//        )
//
			if (true)
				{
					$NumEntry++;
					//Col 0
					$this->SetFont('Arial','', '8');
					// Table
					foreach ($PageColumn as $Col) {
						if ($Col["Show"]) {
							$this->Cell($Col["Witdh"],$lineheight,str_replace($localsearchSDZ, $localreplaceSDZ,$dataline[$Col["Field"]]),'LR',0,'L',$fill);
							//$this->Cell($Col["Witdh"],5,str_replace($localsearchSDZ, $localreplaceSDZ,$dataline[$Col["Field"]]),1,0,'L',true);
							//$this->SetX($this->GetX() +$Col["Witdh"]);
							//	$this->Cell($w[0],$lineheight,"",'LR',0,'L',$fill);
							// $this->SetY($this->GetY() +12);
							//$this->Cell($w[0],5,date("d.m.",strtotime($Flug["Datum"])),'LR',0,'L',$fill);
						}
					}
				}
				//Next Line
				$this->Ln();
				$fill = !$fill;
				if ($NumEntry> $MaxEntriesInTable) {
					break;
				}
			}
		}
	}
}


//////////////////////////////////////////////
// Einlesen bestehender Feldkatalog (XML)
//////////////////////////////////////////////


	$OptionJaeger = Array();

	//$Feldkatalog = TransferFeldKatFromXMLtoArr ($xml_felder, $EnabledRegion, $OptionJaeger, $MaxSelectedEntries);
	//pre_R($Feldkatalog);

//////////////////////////////////////////////
// Einlesen Flugdaten (XML)
//////////////////////////////////////////////
	

$format=array(298, 210); 
$pdf=new PDF('L','mm',$format);
//$pdf = new PDF();
// Data loading
//$data = $pdf->LoadFieldData('countries.txt');
$pdf->AliasNbPages();
$pdf->SetFont('Arial','',10);
$pdf->AddPage();
$pdf->FancyTable($header,$data);
$Filename = date("Ymd")."_RKRK_Adressen_Landwirte";
$pdf->Output("I", $Filename.".pdf");

?>