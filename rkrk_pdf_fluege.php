<?php
  session_start();
  include_once('scripts/rkrk_gen.php');
  include_once('scripts/rkrk_felddaten.php');
  require('scripts/fpdf/fpdf.php');

	//check impropriate IP addresses accessing page
	CheckIPandExit();
	if (function_exists('rkrk_pdf_maybe_emit_html_shell')) {
		rkrk_pdf_maybe_emit_html_shell('Flugaufträge');
	}

// ========================
// General settings 
// ========================


  $titleimage = "images/22color/202121%20Logo-RKRKZ.png";
  $MaxEntriesInTable = 290;
// Column headings
	$header = array('Zeit', 'Flugfeld', 'Felddaten', 'Kontaktdaten', 'Checkliste');
  

if (isset($_GET["Append"])) {
	$AppendTitle= $_GET["Append"];
} else {
	$AppendTitle= "";
}  

if (isset($_GET["FromDate"])) {
	$FromDate= $_GET["FromDate"];
} else {
	$FromDate= "";
}  

if (isset($_GET["ToDate"])) {
	$ToDate= $_GET["ToDate"];
} else {
	$ToDate= "";
}  

if (isset($_GET["FeldNamenWahl"])) {
	$FeldNamenWahl = $_GET["FeldNamenWahl"];
} else	{
	$FeldNamenWahl = '';
}

if (isset($_GET["Pilot"])) {
	$PilotenWahl = $_GET["Pilot"];
} else	{
	$PilotenWahl = '';
}

if (isset($_GET["Jaeger"])) {
	$JaegerWahl = $_GET["Jaeger"];
} else	{
	$JaegerWahl = '';
}

SetMissionLocation();
$MissionSelectionParameter = "&MDB=" . $RKRKMissionSelected;

$RegionSelected = isset($RegionSelected) ? (string)$RegionSelected : '';
$FFilter = isset($FFilter) ? (string)$FFilter : '';
$Today = date('Y-m-d');
if ($FromDate === '') {
	$FromDate = $Today;
}
if ($ToDate === '') {
	$ToDate = $FromDate;
}

	$WarningList = Array();
	$FreiList = Array();
	//Write to LOGfile
	WriteToLog("do", basename($_SERVER['PHP_SELF']), "Pdf ",$RegionSelected,$FFilter,"");


class PDF extends FPDF
{

function TextWithDirection($x, $y, $txt, $direction='R')
{
    if ($direction=='R')
        $s=sprintf('BT %.2F %.2F %.2F %.2F %.2F %.2F Tm (%s) Tj ET',1,0,0,1,$x*$this->k,($this->h-$y)*$this->k,$this->_escape($txt));
    elseif ($direction=='L')
        $s=sprintf('BT %.2F %.2F %.2F %.2F %.2F %.2F Tm (%s) Tj ET',-1,0,0,-1,$x*$this->k,($this->h-$y)*$this->k,$this->_escape($txt));
    elseif ($direction=='U')
        $s=sprintf('BT %.2F %.2F %.2F %.2F %.2F %.2F Tm (%s) Tj ET',0,1,-1,0,$x*$this->k,($this->h-$y)*$this->k,$this->_escape($txt));
    elseif ($direction=='D')
        $s=sprintf('BT %.2F %.2F %.2F %.2F %.2F %.2F Tm (%s) Tj ET',0,-1,1,0,$x*$this->k,($this->h-$y)*$this->k,$this->_escape($txt));
    else
        $s=sprintf('BT %.2F %.2F Td (%s) Tj ET',$x*$this->k,($this->h-$y)*$this->k,$this->_escape($txt));
    if ($this->ColorFlag)
        $s='q '.$this->TextColor.' '.$s.' Q';
    $this->_out($s);
}

function TextWithRotation($x, $y, $txt, $txt_angle, $font_angle=0)
{
    $font_angle+=90+$txt_angle;
    $txt_angle*=M_PI/180;
    $font_angle*=M_PI/180;

    $txt_dx=cos($txt_angle);
    $txt_dy=sin($txt_angle);
    $font_dx=cos($font_angle);
    $font_dy=sin($font_angle);

    $s=sprintf('BT %.2F %.2F %.2F %.2F %.2F %.2F Tm (%s) Tj ET',$txt_dx,$txt_dy,$font_dx,$font_dy,$x*$this->k,($this->h-$y)*$this->k,$this->_escape($txt));
    if ($this->ColorFlag)
        $s='q '.$this->TextColor.' '.$s.' Q';
    $this->_out($s);
}

// Page header
function Header()
{
	global $titleimage, $IconLoc, $RKRKMissionSelected,  $header, $RegionSettings, $FFilter, $RegionSelected,$AppendTitle, $FeldNamenWahl, $JaegerWahl, $PilotenWahl;
    // Logo
    //$this->Image($titleimage,10,6,30);
	$this->Image($IconLoc."/logo.jpg",175,10,22,0,'','https://drohnenrehkitzrettung.ch');
	
    //$this->Image("202121 Logo-RKRKZ.png",175,6,25);
    // Arial bold 15
    $this->SetFont('Arial','B',15);
    $this->Cell(50,10,rkrk_pdf_text("Flugaufträge"),0,0, 'L');
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
    // Title
	if ($RegionSelected !== '' && isset($RegionSettings[$RegionSelected]["Text"])) {
		$Title = $RegionSettings[$RegionSelected]["Text"];
	} else {
		$Title = "Flugaufträge";
	}

if ($AppendTitle!="") {
		$Title .= " " . $AppendTitle;
	}
	if ($FFilter!="") {
		$Title .= " selektierte Liste";
	}

	$Filter="";
	if ($JaegerWahl !="") 
		$Filter .= " Jaeger: " .$JaegerWahl; 
	if ($PilotenWahl !="") 
		$Filter .= " Pilot: " .$PilotenWahl; 
	if ($FeldNamenWahl !="") 
		$Filter .= " Filter: " .$FeldNamenWahl; 

	$this->SetFont('Arial','',9);
    $this->Cell(30,8,trim($Filter) ,0,0, 'C');    // Line break
    $this->Ln(12);
	$this->Cell(120,6);
	if (is_file($IconLoc.'/startpnt.jpg')) {
		$this->Image($IconLoc.'/startpnt.jpg',$this->GetX()+2, $this->GetY(), 5,0,'');
	}
    $this->Cell(7,6);
	$this->SetFont('Arial','',6);
    $this->Cell(15,6,"Link zu Startpunkt auf Google Map",0,0, 'L');    // Line break
    $this->SetFont('Arial','',9);
    $this->Ln(8);
    $this->SetFillColor(160,194,145);
    $this->SetDrawColor(160,194,145);
    $this->SetLineWidth(.3);
	$this->SetFont('Arial','B',10);
    // Header
    $w = array(15, 38, 43, 50, 45);
    for($i=0;$i<count($header);$i++)
        $this->Cell($w[$i],7,$header[$i],1,0,'C',true);
    $this->Ln();
}

// Endremark
function Endremark()
{
	global $ImageLoc,$OptionFrei, $FreiList, $WarningList, $OptionWarning,$IconLoc;

	$w = array(15, 38, 43, 50, 45);
	$left = 10;
	$linedistance= 3.5;
	$lineoffset= 10;
	$lineheight= 23;
	$width= 50;
	$height= 6;
	$this->SetY($this->GetY()+2);
	$this->Line($left,$this->GetY()+$lineoffset,200,$this->GetY()+$lineoffset);
	$this->SetFont('Arial','',7);
	$this->SetY($this->GetY()+12);
	$this->Cell(15,0,"Legende:",'',0,'L');
	$this->SetY($this->GetY()+3);
	$PicPos = 2; 
	foreach ($OptionFrei  as $key => $Frei) {
		if (in_array($key,$FreiList)) {
			$this->SetX($this->GetX() +$w[0]+$w[1]+$PicPos);
			$this->Image($IconLoc.$Frei["PdfPic"],$this->GetX(), $this->GetY(), 0,4,''); 
			$this->SetY($this->GetY()+2.5);
			$this->SetX($this->GetX()+$PicPos+10);
			$this->Cell(10,0,rkrk_pdf_text($Frei["Text"]),'',0,'L');
			$this->SetY($this->GetY()+2.5);
		}
	}
	$this->SetY($this->GetY()-count($FreiList)*5);
	$PicPos = 60;
	foreach ($OptionWarning  as $key => $Warning) {
		if (in_array($key,$WarningList)) {
			$this->SetX($this->GetX() +$w[0]+$w[1]+$PicPos);
			$this->Image($IconLoc.$Warning["PdfPic"],$this->GetX(), $this->GetY(), 0,4,''); 
			$this->SetY($this->GetY()+2.5);
			$this->SetX($this->GetX()+$PicPos+10);
			$this->Cell(10,0,rkrk_pdf_text($Warning["Text"]),'',0,'L');
			$this->SetY($this->GetY()+3);
		}
	}
	$this->Ln();
	$this->Cell($left,$width,"",2,0,'L');
	$this->SetFont('Arial','I',8);
	$this->Line($left,$this->GetY()+$lineoffset,200,$this->GetY()+$lineoffset);
	$this->SetY($this->GetY()+10);
	$this->Cell($left,5,"Weitere Informationen auf unserer Website: ",'',0,'L');
	$Topline= $this->GetY()+5;
	if (file_exists($ImageLoc."qr_drohnenrekitzrettungch.jpg")) {
		//$this->Image($ImageLoc."qr_drohnenrekitzrettungch.jpg",$left,$this->GetX()+20,18);
		$this->Image($ImageLoc.'qr_drohnenrekitzrettungch.jpg', $left, $Topline, $lineheight,0,'','https://drohnenrehkitzrettung.ch'); 
		//$this->Cell($left,$lineheight,$this->Image($ImageLoc."qr_drohnenrekitzrettungch.jpg", $left, $Topline, $lineheight),'',0,'R',false, "https://drohnenrehkitzrettung.ch");
    }
	if (file_exists($ImageLoc."twint_frei.jpg")) {
		$this->Image($ImageLoc.'twint_frei.jpg', $this->GetX()+70, $Topline, $lineheight,0,'','https://www.drohnenrehkitzrettung.ch/spenden'); 
		//$this->Cell($left,$lineheight,$this->Image($ImageLoc."twint_frei.jpg", $this->GetX()+60, $Topline, $lineheight),'',0,'R',false, "https://www.drohnenrehkitzrettung.ch/spenden");
    }
	if (file_exists($ImageLoc."qr_swiss_frei.jpg")) {
		$this->Image($ImageLoc.'qr_swiss_frei.jpg', $this->GetX()+150, $Topline, $lineheight,0,'','https://www.drohnenrehkitzrettung.ch/spenden'); 
		//$this->Cell($left,$lineheight,$this->Image($ImageLoc."qr_swiss_frei.jpg", $this->GetX()+130, $Topline, $lineheight),'',0,'R',false, "https://www.drohnenrehkitzrettung.ch/spenden");
    }

	$lineoffset=25;
	$this->SetFont('Arial','B',9);
	$this->SetY($this->GetY()+10);
	$this->SetY($Topline);
	$this->SetX($this->GetX()+$lineoffset);
	$this->Cell($width, $height,"www.drohnenrehkitzrettung.ch",'',0,'L');
	$this->SetFont('Arial','I',7);
	$this->SetY($this->GetY()+10);
	$this->SetX($this->GetX()+$lineoffset);
	$this->Cell($width, $height,rkrk_pdf_text("Möchten Sie uns gerne Unterstützen? "),'I',0,'L');
	$this->SetY($this->GetY()+$linedistance);
	$this->SetX($this->GetX()+$lineoffset);
	$this->Cell($width, $height,rkrk_pdf_text("Dann verwenden Sie einfach den QR Code "),'I',0,'L');
	$this->SetY($this->GetY()+$linedistance);
	$this->SetX($this->GetX()+$lineoffset);
	$this->Cell($width, $height,rkrk_pdf_text("für eine Bank- oder TWINT Zahlung."),'I',0,'L');$this->SetY($this->GetY()+$lineoffset+3);
	$this->SetFont('Arial','BI',8);
	$lineoffset=105;
	$this->SetY($Topline);
	$this->SetX($this->GetX()+$lineoffset);
	$this->Cell($width, $height,rkrk_pdf_text("IBAN: CH12 0023 9239 1639 8641 T"),'I',0,'L');
	$this->SetFont('Arial','I',7);
	$this->SetY($this->GetY()+$linedistance);
	$this->SetX($this->GetX()+$lineoffset);
	$this->Cell($width, $height,rkrk_pdf_text("c/o Thomas Christen, Drohnenrehkitzrettung, "),'I',0,'L');
	$this->SetY($this->GetY()+$linedistance);
	$this->SetX($this->GetX()+$lineoffset);
	$this->Cell($width, $height,rkrk_pdf_text("Baumgartenweg 7, 8934 Knonau"),'I',0,'L');
	$this->SetY($this->GetY()+$linedistance);
	$this->SetX($this->GetX()+$lineoffset);
	$this->Cell($width, $height,rkrk_pdf_text("Durch Ihre Unterstützung können wir die"),'I',0,'L');
	$this->SetY($this->GetY()+$linedistance);
	$this->SetX($this->GetX()+$lineoffset);
	$this->Cell($width, $height,rkrk_pdf_text("Drohnensuchflüge kostenfrei anbieten."),'I',0,'L');
	$this->SetY($this->GetY()+$linedistance);
	$this->SetX($this->GetX()+$lineoffset);
	$this->Cell($width, $height,rkrk_pdf_text("Herzlichen Dank!"),'I',0,'L');
	$this->SetY($this->GetY()+$linedistance);
	$this->SetX($this->GetX()+$lineoffset);
	$this->Cell($width, $height,rkrk_pdf_text("Euer Piloten - Team'"),'I',0,'L');
}


// Page footer
function Footer()
{
    // Position at 1.5 cm from bottom
    $this->SetY(-15);
    // Arial italic 8
    $this->SetFont('Arial','I',8);
    // Page number
    $this->Cell(0,3,'Seite '.$this->PageNo().'/{nb}',0,0,'C');
	// Date du jour
	$date_du_jour = date('d.m.Y');
	date_default_timezone_set('Europe/Paris');
	$this->Cell(0,0,$date_du_jour,0,0,'R');
}

// Colored table
function FancyTable($header, $FlugListe)
{
	global $RKRKLocalLoactionMissionFiles, $FluzeitStartUndLandung , $FlugzeitTreshold, $searchSDZ, $replaceSDZ, $RKRKExternalLoactionMissionFiles,$PilotenWahl, $JaegerWahl, $FromDate, $ToDate, $Feldkatalog ,$MaxEntriesInTable,$WebLink_public ,$IconLoc, $FeldNamenWahl, $OptionFrei,$OptionWarning, $WarningList, $FreiList, $ManualFieldLabel, $FFilter, $Today;
	//global $RKRKLocalLoactionMissionFiles, $localsearchSDZ, $localreplaceSDZ, $searchSDZ, $replaceSDZ, $RKRKExternalLoactionMissionFiles,$PilotenWahl, $JaegerWahl, $FromDate, $ToDate, $Feldkatalog ,$MaxEntriesInTable,$WebLink_public ,$IconLoc, $FeldNamenWahl, $OptionFrei,$OptionWarning, $WarningList, $FreiList;

 // Colors, line width and bold font
    $this->SetFillColor(160,194,145);
    $this->SetDrawColor(160,194,145);
    $this->SetLineWidth(.3);
    $this->SetFont('','B');
    // Header
	$lineheight= 38;
    $w = array(15, $lineheight, 43, 50, 45);
/*
    for($i=0;$i<count($header);$i++)
        $this->Cell($w[$i],7,$header[$i],1,0,'C',true);
    $this->Ln();
*/
    // Color and font restoration
    $this->SetFillColor(225,239,217);
    $this->SetTextColor(0);
    $this->SetFont('');
	$this->SetAutoPageBreak(false);
	$FFArray = explode(" ",str_replace(",", " ", str_replace(";", " ", $FFilter)));
	//echo RegionSelected . " " . $RegionSelected . "<br>";
	//echo FFilter . " " . $FFilter . "<br>";
	//pre_R($FFArray);
    // Data
    $fill = false;
	$Num = 0;
	$NumEntry = 0;
	foreach ($FlugListe as $Key => $Flug) {
		if (	
				(
					$JaegerWahl == ""
					or ($Flug["Jaeger"] == $JaegerWahl)
				)
				and
				(
					$PilotenWahl == ""
					or ($Flug["Pilot"] == $PilotenWahl)
				)
				and
				(  
					$FeldNamenWahl == "" 
					or like($Flug["FeldID"], strval($FeldNamenWahl))
					or (strpos($FeldNamenWahl, strval($Flug["FeldID"]))!== false)
					or like($Flug["Pilot"], $FeldNamenWahl)
					or like($Flug["Kontaktnummer"], $FeldNamenWahl)
					or like($Flug["Jaeger"], $FeldNamenWahl)
					or like($Flug["Bemerkung"], $FeldNamenWahl)
					or like($Flug["Felder"], $FeldNamenWahl)
					or like($Flug["Landwirt"], $FeldNamenWahl)
				) 
				and
				(
					($Flug["Datum"] >= $FromDate) and
					($Flug["Datum"] <= $ToDate)
				)
				and
				// Ausblenden von zukünftigen Flügen mit Geflogen = Nein
				(
					!(($Flug["Datum"] >= $Today) and
					($Flug["Geflogen"] == "Nein"))
				)
			)
			{
			$Num++;
			$feldMeta = null;
			$feldId = trim((string)($Flug['Feld'] ?? $Flug['FeldID'] ?? ''));
			$flugJg = trim((string)($Flug['JG'] ?? ''));
			if ($flugJg !== '' && $feldId !== '' && isset($Feldkatalog[$flugJg][$feldId]) && is_array($Feldkatalog[$flugJg][$feldId])) {
				$feldMeta = $Feldkatalog[$flugJg][$feldId];
			}
			if (!is_array($feldMeta)) {
				$feldMeta = rkrk_feldkatalog_entry_for_flight($Feldkatalog, $Flug);
			}
			if (!is_array($feldMeta)) {
				$feldMeta = array();
			}
			
			//Store Warning/FreiList
			
			 foreach (explode(" ", (string)($feldMeta["Freigabe"] ?? '')) as $Frei) {
				$FreiList[trim($Frei)]=trim($Frei);
			 }
			 foreach (explode(" ", (string)($feldMeta["Warnung"] ?? '')) as $Warn) {
				$WarningList[trim($Warn)]=trim($Warn);
			 }
/*
Flug Array Struktur

Array
(
    [FID0001] => Array
        (
            [FlugID] => FID0001
            [Datum] => 2022-05-08
            [Zeit] => 21:30
            [Pilot] => CH
            [Feld] => 26-050
            [Kontrollflug] => Ja
            [Fund] => 1
            [UAV#] => 2022-38584
            [Felder] => Boggsmatten 224
            [J�ger] => BS
            [Kontaktnummer] => 26-Schicker Bruno, 079 295 15 44
            [Landwirt] => Boss Philip
            [Bemerkung] => 
            [Flugzeit] => 4.5
            [AnzFelder] => 1
            [#Funde] => 0
            [Einsatztag] => 8.05
            [Grundst�ckID] => 224
            [Gde] => KN
            [JG] => 26
            [Reg] => AmtZH
            [Fl�che] => 1.97
        )
*/ 

/*
Feldkatalog Array Struktur
Array
(
    [21] => Array
        (
            [21-014] => Array
                (
                    [JG] => 21
                    [Number] => 1
                    [FeldID] => 21-014
                    [Feldname] => Boden_Ost
                    [Flurname] => Ober
                    [GrundstueckID] => 1131
                    [Flugzeit] => 7.9
                    [Flaeche] => 4.46
                    [Wegpunkte] => 15
                    [Distanz] => 2133.5
                    [Gemeinde] => HaA
                    [Gde] => 
                    [JgdGebiet] => 
                    [Betrieb] => 
                    [Freigabe] => OK
                    [Warnung] => CTR
                    [Jaeger] => YM
                    [Bemerkung] => LSZN Hausen a.A.
                    [Flug] => 22
                    [Funde] => 
                    [SuchfeldBild] => 21_Aeugst/21-014_Boden_Ost_1131_00_CH_22.jpg
                    [FlugplanBild] => 21_Aeugst/21-014_Boden_Ost_1131_50_CH_22.jpg
                    [JasonFile] => 21_Aeugst/21-014_Boden_Ost_1131_50_CH_22.json
                    [KMLFile] => 21_Aeugst/21-014_Boden_Ost_1131_50_CH_22.kml
                    [LichtiFile] => 21_Aeugst/21-014_Boden_Ost_1131_50_CH_22.lchm
                    [Bauer] => 
                    [Notiz] => 
                    [Grundstueckdaten] => Array
                        (
                            [Startplatz] => 8.508722620821962, 47.26193569147305
                            [Zentrum] => 8.507420249584786, 47.26327229938576
                        )

                    [Flaechendaten] =>  8.50606305182325, 47.26463838138492    8.505108185413949, 47.264125087513456    8.50516585290777, 47.263812921501234    8.505145736340157, 47.263699159033266    8.50875196636068, 47.2619062173866    8.509732313755624, 47.262825448861875    8.50606305182325, 47.26463838138492   
                    [Flugweg] =>  8.508726644135484, 47.26193569147305, 10    8.508750120110966, 47.262042339369025, 50    8.505276099049107, 47.26375879244149, 50    8.505250258956048, 47.264005042428735, 50    8.508913197075058, 47.2621952523347, 50    8.509076274976668, 47.26234816506766, 50    8.505386211512612, 47.26417135905153, 50    8.505612541829093, 47.26429302481477, 50    8.5092393538158, 47.26250107756788, 50    8.50940243359247, 47.2626539898354, 50    8.507620653388273, 47.263534339983806, 50    8.505838873184075, 47.26441469013221, 50    8.506065205577546, 47.264536355003884, 50    8.507815359942114, 47.263671628437024, 50    8.509565514306685, 47.26280690187016, 50   
                    [Export] => Y
                    [Select] => Y
                    [ListNr] => 
                )

*/
			if (true)
				{
				$NumEntry++;
				if ($this->GetY() + $lineheight > $this->PageBreakTrigger) {
					$this->AddPage();
				}
				$rowTopY = $this->GetY();
				$col0X = $this->lMargin;
				$col1X = $col0X + $w[0];
				$col2X = $col1X + $w[1];
				$col3X = $col2X + $w[2];
				$col4X = $col3X + $w[3];
				//Col 0
				$this->SetFont('Arial','B', '10');
				$this->SetX($col0X);
				$this->Cell($w[0],$lineheight,"",'LR',0,'L',$fill);
				$this->SetY($rowTopY + 5);
				$this->SetX($col0X);
				$this->Cell($w[0],5,date("d.m.",strtotime($Flug["Datum"])),'LR',0,'L',$fill);
				$this->SetY($this->GetY() +5);
				$this->SetX($col0X);
				$this->Cell($w[0],5,$Flug["Zeit"],'LR',0,'L',$fill);
				$this->SetY($this->GetY() +10);
				$this->SetX($col0X);
				$this->Cell($w[0],5,$Flug["Pilot"],'LR',0,'L',$fill);
				$this->SetY($this->GetY() +7);
				$this->SetX($col0X);
				//Zeig Parkplatz an falls definiert, ansonsten den Startpunkt)
				$gs = (isset($feldMeta["Grundstueckdaten"]) && is_array($feldMeta["Grundstueckdaten"])) ? $feldMeta["Grundstueckdaten"] : array();
				if (!empty($gs["Parkplatz"])) {
					$Location = explode(",", (string)$gs["Parkplatz"]);
				} else {
					$Location = explode(",", (string)($gs["Startplatz"] ?? ''));
				}
				$StartLat = $Location[0] ?? '';
				$StartLon = $Location[1] ?? '';
				
				if ($StartLat != "" && $StartLon !='') {
					$this->SetFont('Arial','', '8');
					$this->SetTextColor(65, 130, 65);
					//$this->Cell($w[0],5,"Treffpunkt",'LR',0,'L',$fill,"https://maps.google.ch/?q=". trim($StartLon) . " ," . trim($StartLat) . "&t=k");
					$this->SetTextColor(0);
//					$this->Cell($w[0],8,$this->Image($WebLink_intern.$IconLoc.'/startpnt.jpg', $this->GetX(), $this->GetY(), 8, '', 'https://www.rehkitzrettung.ch'),'','','',$fill, $WebLink_intern.$IconLoc.'\startpnt.jpg');
					$this->Image($IconLoc.'/startpnt.jpg',$col0X+2, $this->GetY(), 8,0,'','https://maps.google.ch/?q='. trim($StartLon) . ' ,' . trim($StartLat) . '&t=k'); 
				}
				
				//if (is_file($WebLink_intern.$IconLoc.'/startpnt.jpg')) {
				//	$this->Cell($w[0],8,$this->Image($WebLink_intern.$IconLoc.'/startpnt.jpg', $this->GetX(), $this->GetY(), 8),'LR',0,'L',$fill,"https://www.drohnenrehkitzrettung.ch");
				//}
				$this->SetY($rowTopY);
				$this->SetX($col1X);
				$this->Cell($w[1], $lineheight, '', 'LR', 0, 'L', $fill);
				//Col 1 - Flugplanbild (wie rkrk_pdf_felderliste.php)
				$picMeta = is_array($feldMeta) ? $feldMeta : array();
				if (($picMeta['FlugplanBild'] ?? '') === '' && ($picMeta['SuchfeldBild'] ?? '') === '') {
					$altMeta = rkrk_feldkatalog_entry_for_flight($Feldkatalog, $Flug);
					if (is_array($altMeta)) {
						$picMeta = $altMeta;
					}
				}
				$FlugplanBildList = explode(",", rkrk_feld_scalar_text($picMeta["FlugplanBild"] ?? ''));
				$SelectedFlugplanBild = trim((string)GetFlugplanbild($FlugplanBildList, rkrk_feld_scalar_text($picMeta["StdFlughohe"] ?? '')));
				$SelectedFlugplanBild = str_replace('\\', '/', $SelectedFlugplanBild);
				$localPic = rkrk_resolve_mission_file_path($SelectedFlugplanBild);
				if ($localPic === '') {
					$SuchfeldBildList = explode(",", rkrk_feld_scalar_text($picMeta["SuchfeldBild"] ?? ''));
					$sfPic = trim(str_replace('\\', '/', (string)($SuchfeldBildList[0] ?? '')));
					$localPic = rkrk_resolve_mission_file_path($sfPic);
					if ($localPic !== '') {
						$SelectedFlugplanBild = $sfPic;
					}
				}
				if ($localPic !== '') {
					$ext = strtolower(pathinfo($localPic, PATHINFO_EXTENSION));
					if ($ext === 'jpg' || $ext === 'jpeg') {
						$picLink = $RKRKExternalLoactionMissionFiles.$SelectedFlugplanBild;
						$this->Image($localPic, $col1X, $rowTopY, $lineheight, $lineheight, '', $picLink);
					}
				}
				$this->SetY($rowTopY);
				$this->SetX($col2X);
				//Col 2
				$this->Cell($w[2],$lineheight,"",'LR',0,'LR',$fill);
				$this->SetFont('Arial','B', '10');
				$this->SetY($rowTopY +2);
				$this->SetX($col2X);
				$this->SetTextColor(90, 120, 90);
				$this->Cell($w[2],5,$Flug["Feld"],'',0,'L',$fill, $WebLink_public . "rkrk_map.php?Reg=ALL&Pfad=on&FFilter=".$Flug["Feld"]);
				$this->SetY($this->GetY() +5);
				$this->SetX($col2X);
				$this->Cell($w[2],5,rkrk_pdf_text($feldMeta["Feldname"]),2,0,'L',$fill, $WebLink_public . "rkrk_map.php?Reg=ALL&Pfad=on&FFilter=".$Flug["Feld"]);
				$this->SetTextColor(0);
				$this->SetFont('Arial','B', '8');
				$this->SetY($this->GetY() +4);
				$this->SetX($col2X);
				if ($feldMeta["Flurname"]!="") {
					$this->Cell($w[2],5,"(". rkrk_pdf_text($feldMeta["Flurname"]) .")",2,0,'L');
					$yoffset = -5;
				} 	elseif (($Flug["Felder"]!="")) {
					$this->Cell($w[2],5, rkrk_pdf_text($Flug["Felder"] . " "),2,0,'L');
					$yoffset = -5;
				}
				$this->SetFont('Arial','',8);
				$this->SetY($this->GetY() +4);
				$this->SetX($col2X);
				if ($Flug["Status"]=="locked") {
					$this->SetFont('Arial','B', '18');
					$this->SetTextColor(192, 0, 0);
					$this->TextWithRotation($this->GetX()+5, $this->GetY()+15,'in Planung',20,-20);
				}
				$this->SetTextColor(0);
				$this->SetFont('Arial','', '8');
				if ($Flug["Feld"]!=$ManualFieldLabel) {
					$this->Cell($w[2],5,"GIS#: ".$feldMeta["GrundstueckID"],2,0,'L');
				} else {
					$this->Cell($w[2],5,"GIS#: ".$Flug["GrundstückID"],2,0,'L');
				}	
				$this->SetY($this->GetY() +4);
				$this->SetX($col2X);
				if ($Flug["Feld"]!=$ManualFieldLabel) {
					$this->Cell($w[2],5,rkrk_pdf_text("Fläche/Dist: ".$feldMeta["Flaeche"] . "ha/ " .$feldMeta["Distanz"] . "m"),2,0,'L');
				} else {
					$this->Cell($w[2],5,rkrk_pdf_text("Fläche: ".$Flug["Flaeche"] . "ha"), 2,0,'L');
				}	
				$this->SetY($this->GetY() +4);
				$this->SetX($col2X);
				//if ($feldMeta["Flugzeit"]>$FlugzeitTreshold) {
					// Zeit in Sekunden
				///	$Flugzeit = round($FluzeitStartUndLandung+($feldMeta["Flugzeit"])/60,1);
				//} else {
					// Zeit in Minuten
					$Flugzeit = round(($FluzeitStartUndLandung+$feldMeta["Flugzeit"]),1);
				//}	

				$this->Cell($w[2],5,"Zeit: ".$Flugzeit  . "min ",2,0,'L');
				$this->SetY($this->GetY() +4);
				$this->SetX($col2X);
				if ($Flug["Feld"]!=$ManualFieldLabel) {
					$this->Cell($w[2],5,"JG/Gde: ".$feldMeta["JG"] . "/ " . rkrk_pdf_text($feldMeta["Gemeinde"]),2,0,'L');
				} else {
					$this->Cell($w[2],5,"JG/Gde: ".$Flug["JG"] . "/ " . $Flug["Gde"],2,0,'L');
				}	
				$this->SetY($this->GetY() +4);
				$this->SetX($col2X);
				$this->Cell($w[2],5,"UAV# :" . $Flug["UAV#"],2,0,'L');
				$this->SetFont('Arial','',6);
				//$this->SetY($this->GetY() +3);
				//$this->SetX($this->GetX() +$w[0]+$w[1]);
				//$this->Cell($w[2],5,$feldMeta["LichtiFile"],2,0,'L');
				//$this->SetY($this->GetY() -27+$yoffset);
				$this->SetY($rowTopY);
				$this->SetX($col3X);
				//Col 3
				$this->Cell($w[3],$lineheight,"",'LR',0,'LR',$fill);
				$this->SetFont('Arial','I',8);
				$this->SetY($rowTopY +2);
				$PicPos = 2;
				$this->SetX($col3X);
				foreach ($OptionFrei  as $key => $Frei) {
					if (like($feldMeta["Freigabe"], $key)) {
						$this->SetX($this->GetX()+$PicPos);
						$this->Image($IconLoc.$OptionFrei[$key]["PdfPic"],$this->GetX(), $this->GetY(), 0,5,''); 
						//$this->SetX($this->GetX());
						//$this->Cell(10,5,"/".$key."/",2,0,'L');
						//$this->SetX($this->GetX() -10);
						$PicPos = 5;
					}
				}
				$this->SetY($this->GetY() +6);
				$PicPos = 2;
				$this->SetX($col3X);
				foreach ($OptionWarning as $key => $Warning) {
					if (like($feldMeta["Warnung"], $key)) {
						$this->SetX($this->GetX()+$PicPos);
						$this->Image($IconLoc.$OptionWarning[$key]["PdfPic"],$this->GetX(), $this->GetY(), 0,5,''); 
						//$this->SetX($this->GetX());
						//$this->Cell(10,5,"/".$key."/",2,0,'L');
						//$this->SetX($this->GetX() -10);
						$PicPos = 6;
					}
				}
				//Next Line
				$this->SetY($this->GetY() +7);
				$this->SetX($col3X);
				$this->Cell($w[3],5,rkrk_pdf_text("Jäger: "),2,0,'L');
				$this->SetY($this->GetY() +4);
				$this->SetX($col3X);
				$this->Cell($w[3],5,rkrk_pdf_text($Flug["Kontaktnummer"]),2,0,'L');
				$this->SetY($this->GetY() +6);
				$this->SetX($col3X);
				$this->Cell($w[3],5,"Bauer: ",2,0,'L');
				$this->SetY($this->GetY() +4);
				$this->SetX($col3X);
				$this->Cell($w[3],5,rkrk_pdf_text($Flug["Landwirt"]),2,0,'L');
				$this->SetY($rowTopY);
				$this->SetX($col4X);
				//Col 4
				$this->SetFont('Arial','',8);
				$this->Cell($w[4],$lineheight,"",'LR',0,'LR',$fill);
				$this->SetY($rowTopY +2);
				$this->SetX($col4X);
				$this->Cell($w[4],5,"Mission geladen   [Ja]",'',0,'L',$fill);
				$this->SetY($this->GetY() +4);
				$this->SetX($col4X);
				$this->Cell($w[4],5,"Kontrollflug           [Ja] [Nein]",'',0,'L',$fill);
				$this->SetY($this->GetY() +4);
				$this->SetX($col4X);
				$this->Cell($w[4],5,"Funde #                [1] [2] [3] [keine]",'',0,'L',$fill);
				$this->SetY($this->GetY() +4);
				$this->SetX($col4X);
				$this->Cell($w[4],5,"Gemeldet             [Ja]",'',0,'L',$fill);
				$this->SetY($this->GetY() +4);
				$this->SetX($col4X);
				$this->Cell($w[4],5,"Bemerkungen:",'',0,'L',$fill);
				$this->SetY($rowTopY + $lineheight);
				$fill = !$fill;
				if ($NumEntry> $MaxEntriesInTable) {
					break;
				}
			}
		}
	}
	$this->SetAutoPageBreak(true, $this->bMargin);

/*
    foreach($data as $row)
    {
        $this->Cell($w[0],6,$row[0],'LR',0,'L',$fill);
        $this->Cell($w[1],6,$row[1],'LR',0,'L',$fill);
        $this->Cell($w[2],6,number_format($row[2]),'LR',0,'R',$fill);
        $this->Cell($w[3],6,number_format($row[3]),'LR',0,'R',$fill);
        $this->Ln();
        $fill = !$fill;
    }
    // Closing line
*/
   // $this->Cell(array_sum($w),0,'','T');
}
}


//////////////////////////////////////////////
// Einlesen bestehender Feldkatalog (XML)
//////////////////////////////////////////////

	if (!isset($EnabledRegion) || !is_array($EnabledRegion)) {
		$EnabledRegion = array();
	}
	$OptionJaeger = Array();
	$Feldkatalog = GetFeldkatalog($xmlMissionLocation.$RKRKFelderfileXML);
	if (!is_array($Feldkatalog)) {
		$Feldkatalog = array();
	}
	//pre_R($OptionJaeger);
	//pre_R($Feldkatalog);
	// Feldkatalog: verschachtelt [JG][FeldID] - nicht wie FlugListe sortieren!
/*
	if ($Jaeger!="") 
		aasort($Feldkatalog, "Jaeger");
	else
		aasort($Feldkatalog, "Pilot");
	aasort($Feldkatalog, "Zeit");
	aasort($Feldkatalog, "Jaeger");	
*/

//////////////////////////////////////////////
// Einlesen Flugdaten (XML)
//////////////////////////////////////////////
	//echo "From: " . $FromDate ."<br>";
	//echo "To: " . $ToDate  ."<br>";
	
	$csv_file= date('Y', strtotime($FromDate)) .$RKRKFlightsCSV;
	$FlightDataFile = $csvMissionLocation.$csv_file;
	$csv_deliminator = ";";

	//Read CSV file
	$FlugListe = csv_to_array($FlightDataFile, $csv_deliminator );
	if (!is_array($FlugListe)) {
		$FlugListe = array();
	}
	$FlugListe = FormatFlugDataArray($FlugListe);
	//pre_r($FlugListe);


$pdf = new PDF();
// Data loading
//$data = $pdf->LoadFieldData('countries.txt');
$pdf->AliasNbPages();
$pdf->SetFont('Arial','',10);
$pdf->AddPage();
$pdf->FancyTable($header,$FlugListe);
$pdf->Endremark();
$Filename = date("Ymd", strtotime($FromDate))."_RKRK_Flugplan";
if ($PilotenWahl!="")
	$Filename .= "_".$PilotenWahl;
if ($JaegerWahl!="")
	$Filename .= "_".$JaegerWahl;
$pdf->Output("I", $Filename.".pdf");
?>