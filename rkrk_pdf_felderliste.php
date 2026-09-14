<?php
  session_start();
  include_once('scripts/rkrk_gen.php');
  include_once('scripts/rkrk_felddaten.php');
  require('scripts/fpdf/fpdf.php');
	if (function_exists('rkrk_pdf_maybe_emit_html_shell')) {
		rkrk_pdf_maybe_emit_html_shell('Felderliste');
	}


// ========================
// General settings 
// ========================
	$localsearchSDZ  = Array("�","�", "�?", "�","�?�", "�?�");
	$localreplaceSDZ = Array("�", "�", "�" , "�" ,"�" ,"�");

	$WarningList = Array();
	$FreiList = Array();

  $titleimage = "images/22color/202121%20Logo-RKRKZ.png";
  $MaxEntriesInTable = 290;
//Bewirtschafter anzeigen
$Bew = isset($_GET["BEW"]);
$FF = isset($_GET["FF"]);

	// Column headings
	if ($Bew) {
		if ($FF) {
			$header = array('ID', 'Felddaten', 'Flugdaten', 'Bewirtschafter', 'Flugwege');
		} else {
			$header = array('ID', 'Felddaten', 'Flugdaten', 'Bewirtschafter', 'Feldkarte');
		}
	} else {
		$header = array('ID', 'Felddaten', 'Flugdaten', 'Feldkarte', 'Flugwege');
	}

if (isset($_GET["Append"])) {
	$AppendTitle= $_GET["Append"];
} else {
	$AppendTitle= "";
}  

if (isset($_GET["From"])) {
	$From= intval($_GET["From"]);
} else {
	$From= "";
}  

if (isset($_GET["To"])) {
	$To= intval($_GET["To"]);
} else {
	$To= "";
}  



/*
if (isset($_GET["DBW"])) {
   if (strtolower($_GET["DBW"])=="on") {
 		$DebugWork= true;
	} else {
		$DebugWork= false;
	}
	SetMissionLocation();
}  
*/
SetMissionLocation();
$MissionSelectionParameter = "&MDB=" . $RKRKMissionSelected;

if (empty($_GET["Reg"])) {
	$RegionSelected = "ALL";
} else {
	$RegionSelected =$_GET["Reg"];
}

if (empty($_GET["FFilter"])) {
	$FFilter = "";
} else {
	$FFilter=$_GET["FFilter"];
}

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

// Load data
function LoadData($file)
{
	global $xmlMissionLocation, $RKRKFelderfileXML, $EnabledRegion, $OptionJaeger, $MaxSelectedEntries;
	$OptionJaeger = Array();
	return GetFeldkatalog($xmlMissionLocation.$RKRKFelderfileXML);
}

// Page header
function Header()
{
	global $titleimage, $GenIconLoc, $IconLoc, $RKRKMissionSelected, $ImageLoc, $header, $RegionSettings, $FFilter, $RegionSelected,$AppendTitle,$localsearchSDZ,$localreplaceSDZ,$WebLink_public,$MissionSelectionParameter,$IconLoc;
    // Logo
    //$this->Image($titleimage,10,6,30);//
    //$this->Image($IconLoc."/logo.jpg",175,10,22);
	$this->Image($IconLoc."/logo.jpg",175,10,22,0,'','https://drohnenrehkitzrettung.ch');
    //$this->Image($IconLoc."/logo.png",175,10,22,0,'png');
    if (file_exists($ImageLoc."/map_".$RegionSelected.".jpg")) {
		$this->Image($ImageLoc."/map_".$RegionSelected.".jpg",155,10,18,0,'',$WebLink_public ."rkrk_map.php?Reg=".$RegionSelected."&Pfad=on".$MissionSelectionParameter);
    }

	// Arial bold 15
    $this->SetFont('Arial','B',15);
    $this->Cell(50,10,"Flugkatalog  " ,0,0, 'L');
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
			
    $this->SetFont('Arial','',6);
    $this->TextWithDirection(153,28,"online Flugkatalog",'U');
    // Move to the right
    $this->SetFont('Arial','B',15);
    $this->Cell(50);
    // Title
	$Title = str_replace($localsearchSDZ, $localreplaceSDZ, $RegionSettings[$RegionSelected]["Text"]);

if ($AppendTitle!="") {
		$Title .= " " . $AppendTitle;
	}
	if ($FFilter!="") {
		$Title . " selektierte Liste";
	}
    $this->Cell(30,10,$Title ,0,0, 'C');
    // Line break
    $this->Ln(20);
    $this->SetFillColor(160,194,145);
    $this->SetDrawColor(160,194,145);
    $this->SetLineWidth(.3);
	$this->SetFont('Arial','B',10);
    // Header
    $w = array(15, 45, 55, 38, 38);
    for($i=0;$i<count($header);$i++)
        $this->Cell($w[$i],7,$header[$i],1,0,'C',true);
    $this->Ln();
 
}

// Endremark
function Endremark()
{
	global $ImageLoc,$localsearchSDZ,$localreplaceSDZ,$OptionFrei, $OptionWarning,$OptionFrei, $FreiList,$WarningList, $IconLoc;

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
			$this->Cell(10,0,str_replace($localsearchSDZ, $localreplaceSDZ, $Frei["Text"]),'',0,'L');
			$this->SetY($this->GetY()+2.5);
		}
	}
	$this->SetX($this->GetX() +$w[0]+$w[1]+$PicPos);
	$this->Image($IconLoc.'/reh.jpg',$this->GetX(), $this->GetY(), 0,4,'');	
	$this->SetY($this->GetY()+2.5);
	$this->SetX($this->GetX()+$PicPos+10);
	$this->Cell(10,0,"Rehkitzfunde",'',0,'L');
	$this->SetY($this->GetY()+2.5);

	$this->SetY($this->GetY()-(count($FreiList)+1)*5);
	$PicPos = 60;
	foreach ($OptionWarning  as $key => $Warning) {
		if (in_array($key,$WarningList)) {
			$this->SetX($this->GetX() +$w[0]+$w[1]+$PicPos);
			$this->Image($IconLoc.$Warning["PdfPic"],$this->GetX(), $this->GetY(), 0,4,''); 
			$this->SetY($this->GetY()+2.5);
			$this->SetX($this->GetX()+$PicPos+10);
			$this->Cell(10,0,str_replace($localsearchSDZ, $localreplaceSDZ, $Warning["Text"]),'',0,'L');
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
	$this->Cell($width, $height,str_replace($localsearchSDZ, $localreplaceSDZ, "M�chten Sie uns gerne Unterst�tzen? "),'I',0,'L');
	$this->SetY($this->GetY()+$linedistance);
	$this->SetX($this->GetX()+$lineoffset);
	$this->Cell($width, $height,str_replace($localsearchSDZ, $localreplaceSDZ, "Dann verwenden Sie einfach den QR Code "),'I',0,'L');
	$this->SetY($this->GetY()+$linedistance);
	$this->SetX($this->GetX()+$lineoffset);
	$this->Cell($width, $height,str_replace($localsearchSDZ, $localreplaceSDZ, "f�r eine Bank- oder TWINT Zahlung."),'I',0,'L');$this->SetY($this->GetY()+$lineoffset+3);
	$this->SetFont('Arial','BI',8);
	$lineoffset=105;
	$this->SetY($Topline);
	$this->SetX($this->GetX()+$lineoffset);
	$this->Cell($width, $height,str_replace($localsearchSDZ, $localreplaceSDZ, "IBAN: CH12 0023 9239 1639 8641 T"),'I',0,'L');
	$this->SetFont('Arial','I',7);
	$this->SetY($this->GetY()+$linedistance);
	$this->SetX($this->GetX()+$lineoffset);
	$this->Cell($width, $height,str_replace($localsearchSDZ, $localreplaceSDZ, "c/o Thomas Christen, Drohnenrehkitzrettung, "),'I',0,'L');
	$this->SetY($this->GetY()+$linedistance);
	$this->SetX($this->GetX()+$lineoffset);
	$this->Cell($width, $height,str_replace($localsearchSDZ, $localreplaceSDZ, "Baumgartenweg 7, 8934 Knonau"),'I',0,'L');
	$this->SetY($this->GetY()+$linedistance);
	$this->SetX($this->GetX()+$lineoffset);
	$this->Cell($width, $height,str_replace($localsearchSDZ, $localreplaceSDZ, "Durch Ihre Unterst�tzung k�nnen wir die"),'I',0,'L');
	$this->SetY($this->GetY()+$linedistance);
	$this->SetX($this->GetX()+$lineoffset);
	$this->Cell($width, $height,str_replace($localsearchSDZ, $localreplaceSDZ, "Drohnensuchfl�ge kostenfrei anbieten."),'I',0,'L');
	$this->SetY($this->GetY()+$linedistance);
	$this->SetX($this->GetX()+$lineoffset);
	$this->Cell($width, $height,str_replace($localsearchSDZ, $localreplaceSDZ, "Herzlichen Dank!"),'I',0,'L');
	$this->SetY($this->GetY()+$linedistance);
	$this->SetX($this->GetX()+$lineoffset);
	$this->Cell($width, $height,str_replace($localsearchSDZ, $localreplaceSDZ, "Euer Piloten - Team'"),'I',0,'L');
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
function FancyTable($header, $Feldkatalog)
{
	global $RKRKLocalLoactionMissionFiles, $RKRKExternalLoactionMissionFiles,$RegionSettings, $FluzeitStartUndLandung, $FFilter, $RegionSelected, $From, $To, $MaxEntriesInTable,$WebLink_public, $localsearchSDZ,$localreplaceSDZ,$IconLoc,$OptionFrei,$OptionWarning, $WarningList, $FreiList,$Bew, $FF ;

 // Colors, line width and bold font
    $this->SetFillColor(160,194,145);
    $this->SetDrawColor(160,194,145);
    $this->SetLineWidth(.3);
    $this->SetFont('','B');
    // Header
	$lineheight= 38;
    $w = array(15, 45, 55, $lineheight, $lineheight);
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
	//echo $RegionSelected;
	$RegList = explode(",",$RegionSettings[$RegionSelected]["JGList"]);
	//pre_R($RegList);
	foreach ($Feldkatalog as $Key => $Region) {
		foreach ($Region as $Key => $Feld) {
			//echo "<br>|" .$Num . "| F:  |" . $From . "| T:  |" . $To . "|" . (($From=="") or ($From<=$Num)). " -" . (($To=="") or ($To>=$Num));
			if (
//				($Feld["JG"]=="21")
				(in_array($Feld["JG"],$RegList) or ($RegionSelected=="ALL") or ($RegionSelected=="alle")) and
//				(($Feld["JG"]==$RegionSelected) or ($RegionSelected=="ALL")) and
				(($FFilter=="") or in_array($Feld["FeldID"] , $FFArray))
				)
				{
				$Num++;
				if (
					(($From=="") or ($From<=$Num)) and
					(($To=="") or ($To>=$Num))
					)
					{
					$NumEntry++;
					//Store Warning/FreiList
					
					foreach (explode(" ",$Feld["Freigabe"]) as $Frei) {
						$FreiList[trim($Frei)]=trim($Frei);
					}
					foreach (explode(" ",$Feld["Warnung"]) as $Warn) {
						$WarningList[trim($Warn)]=trim($Warn);
					}

					$this->SetFont('Arial','B', '9');
					$this->SetTextColor(90, 120, 90);
					$this->Cell($w[0],$lineheight,$Feld["FeldID"],'LR',0,'L',$fill,$WebLink_public ."rkrk_map.php?Reg=ALL&Pfad=on&FFilter=".$Feld["FeldID"]);
					$this->SetFont('','', '');
					$this->SetTextColor(0);
					$this->Cell($w[1],$lineheight,"",'LR',0,'LR',$fill);
					if ($Feld["Funde"] != "") {
						$this->Image($IconLoc.'/reh.jpg',$w[0], $this->GetY()+4, 5,0,'');	
					}
					$this->SetY($this->GetY() -15);
					$this->SetX($this->GetX() +$w[0]);
					$this->SetFont('','B', '');
					$this->SetTextColor(90, 120, 90);
					$this->Cell($w[1],$lineheight,str_replace($localsearchSDZ, $localreplaceSDZ, $Feld["Feldname"]),2,0,'L','',$WebLink_public ."rkrk_map.php?Reg=ALL&Pfad=on&FFilter=".$Feld["FeldID"]);
					$this->SetFont('','', '');
					$this->SetTextColor(0);
					$this->SetY($this->GetY() +5);
					$this->SetX($this->GetX() +$w[0]);
					if ($Feld["Flurname"]!="") {
						$this->Cell($w[1],$lineheight,"(".str_replace($localsearchSDZ, $localreplaceSDZ, $Feld["Flurname"]).")",2,0,'L');
					}
					$this->SetFont('Arial','',8);
					$this->SetY($this->GetY() +5);
					$this->SetX($this->GetX() +$w[0]);
					$this->Cell($w[1],$lineheight,"GIS#/GEOID: ".$Feld["GrundstueckID"],2,0,'L');
					$this->SetY($this->GetY() +5);
					$this->SetX($this->GetX() +$w[0]);
					$this->Cell($w[1],$lineheight,"Jagdbezirk: ".$Feld["JG"],2,0,'L');
					$this->SetY($this->GetY() +5);
					$this->SetX($this->GetX() +$w[0]);
					$this->Cell($w[1],$lineheight,"J�ger: ".$Feld["Jaeger"],2,0,'L');
					$this->SetY($this->GetY() +5);
					$this->SetX($this->GetX() +$w[0]);
					$this->Cell($w[1],$lineheight,"Gemeinde: ".$Feld["Gemeinde"],2,0,'L');
					//$this->Cell($w[2],$lineheight,str_replace($localsearchSDZ, $localreplaceSDZ, $Feld["Feldname"]),'LR',0,'R',$fill);
					$this->SetY($this->GetY() -10);
					$this->SetX($this->GetX() +$w[0]+$w[1]);
					$this->SetFont('Arial','',10);
					$this->Cell($w[2],$lineheight,"",'LR',0,'L',$fill);
					$this->SetFont('Arial','',8);
					$this->SetY($this->GetY() -17);
					//COL2
					$this->SetY($this->GetY() +2);
					$this->SetX($this->GetX() +$w[0]+$w[1]);
					$LFile = explode("/", str_replace($localsearchSDZ, $localreplaceSDZ, $Feld["LichtiFile"]));
					$this->Cell($w[2],$lineheight,$LFile[1],2,0,'L');
					$this->SetFont('Arial','',8);
					//$this->SetY($this->GetY() +5);
					//$this->SetX($this->GetX() +$w[0]+$w[1]);
					//$this->Cell($w[2],$lineheight,"Freigabe: ".$Feld["Freigabe"],2,0,'L');
					//$this->SetY($this->GetY() +5);
					//$this->SetX($this->GetX() +$w[0]+$w[1]);
					//$this->Cell($w[2],$lineheight,"Zu Beachten: ".$Feld["Warnung"],2,0,'L');
					$this->SetY($this->GetY() +5);
					$this->SetX($this->GetX() +$w[0]+$w[1]);
					$this->Cell($w[2],$lineheight,"Flugzeit: ". round(($FluzeitStartUndLandung+$Feld["Flugzeit"]),1) . " min",2,0,'L');
					$this->SetY($this->GetY() +5);
					$this->SetX($this->GetX() +$w[0]+$w[1]);
					$this->Cell($w[2],$lineheight,"Wegpunkte: ".$Feld["Wegpunkte"],2,0,'L');
					$this->SetY($this->GetY() +5);
					$this->SetX($this->GetX() +$w[0]+$w[1]);
					$this->Cell($w[2],$lineheight,"Distanz: ".$Feld["Distanz"],2,0,'L');
					$PicPos = 2;
					$this->SetY($this->GetY() +23);
					foreach ($OptionFrei  as $key => $Frei) {
						if (like($Feld["Freigabe"], $key)) {
							$this->SetX($this->GetX() +$w[0]+$w[1]+$PicPos);
							$this->Image($IconLoc.$OptionFrei[$key]["PdfPic"],$this->GetX(), $this->GetY(), 0,5,''); 
							$PicPos += 5;
						}
					}
					$this->SetY($this->GetY() +6);
					$PicPos = 2;
					$this->SetX($this->GetX() +$w[0]+$w[1]);
					foreach ($OptionWarning as $key => $Warning) {
						if (like($Feld["Warnung"], $key)) {
							$this->SetX($this->GetX()+$PicPos);
							$this->Image($IconLoc.$OptionWarning[$key]["PdfPic"],$this->GetX(), $this->GetY(), 0,5,''); 
							$PicPos += 5;
						}
					}
					//COL 3
					$this->SetFont('Arial','',8);
					$this->SetY($this->GetY() -29);
					if ($Bew) {
						//Bewirtschafteradresse anzeigen
						$this->SetX($this->GetX() +$w[0]+$w[1]+$w[2]);
						$this->Cell($w[3],$lineheight,"",'LR',0,'LR',$fill);
						$this->SetY($this->GetY() -10);
						$Bewirtschafter = explode("//", str_replace(array("A:", "T:", "M:",",",chr(10)), Array("//","//","//","//",""), str_replace($localsearchSDZ, $localreplaceSDZ, $Feld["Bauer"])));
						$lines=0;
						for ($i = 0; $i <= count($Bewirtschafter); $i++) {
							$this->SetX($this->GetX() +$w[0]+$w[1]+$w[2]);
							if ((trim($Bewirtschafter[$i]) <> "+") and (trim($Bewirtschafter[$i])<>"Schweiz")) {
								$this->Cell($w[3],$lineheight,$Bewirtschafter[$i],2,0,'L');
								$this->SetY($this->GetY() +5);
								$lines++;
							}	
						}	
						$this->SetY($this->GetY() -$lines*5+10);
						$this->SetX($this->GetX() +$w[0]+$w[1]+$w[2]);
					} else {
						//Feldkarte anzeigen
						//$this->Image($RKRKLocalLoactionMissionFiles.$SuchfeldBild[0], $this->GetX(), $this->GetY(), 10);
						$this->SetX($this->GetX() +$w[0]+$w[1]+$w[2]);
						$SuchfeldBild = explode(",", $Feld["SuchfeldBild"]);
						if ($SuchfeldBild[0]!="") {
							if (is_file($RKRKLocalLoactionMissionFiles.$SuchfeldBild[0])) {
								//echo $RKRKLocalLoactionMissionFiles.$SuchfeldBild[0] ."<br>";
								$ext = end(explode('.', $RKRKLocalLoactionMissionFiles.$SuchfeldBild[0]));
								if ($ext=="jpg") {
									$this->Image($RKRKLocalLoactionMissionFiles.$SuchfeldBild[0], $this->GetX(), $this->GetY(), $lineheight,0,'',$RKRKExternalLoactionMissionFiles.str_replace($localsearchSDZ, $localreplaceSDZ,$SuchfeldBild[0]));
									//$this->Image($RKRKLocalLoactionMissionFiles.$SuchfeldBild[0], $this->GetX(), $this->GetY(), $lineheight,0,'',$RKRKLocalLoactionMissionFiles.$SuchfeldBild[0]);
									//$this->Cell($w[3],$lineheight,$this->Image($RKRKLocalLoactionMissionFiles.$SuchfeldBild[0], $this->GetX(), $this->GetY(), $lineheight),'LR',0,'R',false, "https://drohnenrehkitzrettung.ch");
								} 
							} else {	
								//echo $SuchfeldBild[0] ."<br>";
							}
						}
					}
					$this->SetX($this->GetX() +$w[3]);
					if (!$Bew) {
						$FlugplanBild = explode(",", $Feld["FlugplanBild"]);
						$SelectedFlugplanBild = GetFlugplanbild($FlugplanBild,$Feld["StdFlughohe"]);
			
						if (is_file($RKRKLocalLoactionMissionFiles.$SelectedFlugplanBild)) {
							//echo $RKRKLocalLoactionMissionFiles.$FlugplanBild[0] ."<br>";
							$ext = end(explode('.', $RKRKLocalLoactionMissionFiles.$SelectedFlugplanBild));
							if ($ext=="jpg") {
								//$this->Image($IconLoc.$OptionWarning[$key]["PdfPic"],$this->GetX(), $this->GetY(), 0,5,'');
								$this->Image($RKRKLocalLoactionMissionFiles.$SelectedFlugplanBild, $this->GetX(), $this->GetY(), $lineheight,0,'',$RKRKExternalLoactionMissionFiles.str_replace($localsearchSDZ, $localreplaceSDZ,$SelectedFlugplanBild));
								//$this->Cell($w[4],$lineheight,$this->Image($RKRKLocalLoactionMissionFiles.$FlugplanBild[0], $this->GetX(), $this->GetY(), $lineheight),'LR',0,'R',false, "https://drohnenrehkitzrettung.ch");
							}
							//$this->Cell($w[4],$lineheight,"map", '','','',false, "http://drohnenrehkitzrettung.ch");
						}
					} else {
						$SuchfeldBild = explode(",", $Feld["SuchfeldBild"]);
						if ($SuchfeldBild[0]!="") {
							if (is_file($RKRKLocalLoactionMissionFiles.$SuchfeldBild[0])) {
								//echo $RKRKLocalLoactionMissionFiles.$SuchfeldBild[0] ."<br>";
								$ext = end(explode('.', $RKRKLocalLoactionMissionFiles.$SuchfeldBild[0]));
								if ($ext=="jpg") {
									$this->Image($RKRKLocalLoactionMissionFiles.$SuchfeldBild[0], $this->GetX(), $this->GetY(), $lineheight,0,'',$RKRKExternalLoactionMissionFiles.str_replace($localsearchSDZ, $localreplaceSDZ,$SuchfeldBild[0]));
									//$this->Image($RKRKLocalLoactionMissionFiles.$SuchfeldBild[0], $this->GetX(), $this->GetY(), $lineheight,0,'',$RKRKLocalLoactionMissionFiles.$SuchfeldBild[0]);
									//$this->Cell($w[3],$lineheight,$this->Image($RKRKLocalLoactionMissionFiles.$SuchfeldBild[0], $this->GetX(), $this->GetY(), $lineheight),'LR',0,'R',false, "https://drohnenrehkitzrettung.ch");
								} 
							} else {	
								//echo $SuchfeldBild[0] ."<br>";
							}
						}	
					}
					$this->Ln();
					$fill = !$fill;
					if ($NumEntry> $MaxEntriesInTable) {
						break;
					}
				}
			}	
		}
	}

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

	$Feldkatalog = GetFeldkatalog($xmlMissionLocation.$RKRKFelderfileXML);
	if (!is_array($Feldkatalog) || $Feldkatalog === array()) {
		$fileloaded = false;
		echo '        <p>Der Feldkatalog konnte nicht geladen werden</p>';
	}

$pdf = new PDF();
// Data loading
//$data = $pdf->LoadData('countries.txt');
$pdf->AliasNbPages();
$pdf->SetFont('Arial','',10);
$pdf->AddPage();
$pdf->FancyTable($header,$Feldkatalog);
$pdf->Endremark();
$Filename = date("Ymd")."_RKRK_Feldkatalog".$PilotenWahl;
if (($RegionSelected!="ALL" and $RegionSelected!="alle") or $FFilter!="")
	$Filename .= "_".$RegionSelected;
$pdf->Output("I", $Filename.".pdf");

?>