<?php
  session_start();
  include_once('scripts/rkrk_gen.php');
  include_once('scripts/rkrk_felddaten.php');
  require('scripts/fpdf/fpdf.php');

	//check impropriate IP addresses accessing page
	CheckIPandExit();
	if (function_exists('rkrk_pdf_maybe_emit_html_shell')) {
		rkrk_pdf_maybe_emit_html_shell('Flugliste');
	}

// ========================
// General settings 
// ========================
  $titleimage = "images/22color/202121%20Logo-RKRKZ.png";
  $MaxEntriesInTable = 290;
// Column headings
	$header = array('#', 'Datum', 'Zeit', 'P', 'Feld', 'Feldname', 'Flurname','Gis#','Reg', 'JG', 'Jï¿½gr', 'Gde', 'KF' ,'Funde', 'F[A]');
	$w = array(7,13,10,9,13,35,20,17,12,7,7,7,7,9,10,10);
	$totalwidth = array_sum($w) ;
 	$lineheight= 5;
	$left = 10;

 

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

	//Write to LOGfile
	WriteToLog("do", basename($_SERVER['PHP_SELF']), "Pdf ",$RegionSelected,$FFilter,"");


class PDF extends FPDF
{

// Endremark
function Endremark()
{
	global $ImageLoc,$localsearchSDZ,$localreplaceSDZ,$left,$totalwidth;

	$linedistance= 3.5;
	$lineoffset= 10;
	$lineheight= 23;
	$width= 50;
	$height= 6;
	$this->Cell($left,$width,"",2,0,'L');
	$this->SetFont('Arial','I',8);
	$this->Line($left,$this->GetY()+$lineoffset,$totalwidth,$this->GetY()+$lineoffset);
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
	$this->Cell($width, $height,str_replace($localsearchSDZ, $localreplaceSDZ, "Mï¿½chten Sie uns gerne Unterstï¿½tzen? "),'I',0,'L');
	$this->SetY($this->GetY()+$linedistance);
	$this->SetX($this->GetX()+$lineoffset);
	$this->Cell($width, $height,str_replace($localsearchSDZ, $localreplaceSDZ, "Dann verwenden Sie einfach den QR Code "),'I',0,'L');
	$this->SetY($this->GetY()+$linedistance);
	$this->SetX($this->GetX()+$lineoffset);
	$this->Cell($width, $height,str_replace($localsearchSDZ, $localreplaceSDZ, "fï¿½r eine Bank- oder TWINT Zahlung."),'I',0,'L');$this->SetY($this->GetY()+$lineoffset+3);
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
	$this->Cell($width, $height,str_replace($localsearchSDZ, $localreplaceSDZ, "Durch Ihre Unterstï¿½tzung kï¿½nnen wir die"),'I',0,'L');
	$this->SetY($this->GetY()+$linedistance);
	$this->SetX($this->GetX()+$lineoffset);
	$this->Cell($width, $height,str_replace($localsearchSDZ, $localreplaceSDZ, "Drohnensuchflï¿½ge kostenfrei anbieten."),'I',0,'L');
	$this->SetY($this->GetY()+$linedistance);
	$this->SetX($this->GetX()+$lineoffset);
	$this->Cell($width, $height,str_replace($localsearchSDZ, $localreplaceSDZ, "Herzlichen Dank!"),'I',0,'L');
	$this->SetY($this->GetY()+$linedistance);
	$this->SetX($this->GetX()+$lineoffset);
	$this->Cell($width, $height,str_replace($localsearchSDZ, $localreplaceSDZ, "Euer Piloten - Team'"),'I',0,'L');
}


// Page header
function Header()
{
	global $titleimage, $IconLoc,  $RKRKMissionSelected, $header, $RegionSettings, $FFilter, $RegionSelected,$AppendTitle, $w, $totalwidth, $FromDate, $ToDate, $PilotenWahl, $JaegerWahl, $FeldNamenWahl;
    // Logo
    //$this->Image($titleimage,10,6,30);
//    $this->Image($IconLoc."/logo.jpg",175,10,22);
	$this->Image($IconLoc."/logo.jpg",175,10,22,0,'','https://drohnenrehkitzrettung.ch');
    // Arial bold 15
    $this->SetFont('Arial','B',15);
    $this->Cell(50,10,"Flugauftrï¿½ge",0,0, 'L');
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
	$Title = $RegionSettings[$RegionSelected]["Text"];

if ($AppendTitle!="") {
		$Title .= " " . $AppendTitle;
	}
	if ($FFilter!="") {
		$Title . " selektierte Liste";
	}

	$Title .= "  " . date("d.m.Y", strtotime($FromDate));
	if ($ToDate !="") 
		$Title .= " - " . date("d.m.Y", strtotime($ToDate)); 
	$Filter="";
	if ($PilotenWahl !="") 
		$Filter .= " Pilot: " .$PilotenWahl; 
	if ($JaegerWahl !="") 
		$Filter .= " Jaeger: " .$JaegerWahl; 
	if ($FeldNamenWahl !="") 
		$Filter .= " Filter: " .$FeldNamenWahl; 

    $this->Cell(30,10,$Title ,0,0, 'C');
	$this->SetFont('Arial','',9);
	$this->SetY($this->GetY() +6);
    $this->Cell(190,8,trim($Filter) ,0,0, 'C');
    // Line break
    $this->Ln(15);
    $this->SetFillColor(160,194,145);
    $this->SetDrawColor(160,194,145);
    $this->SetLineWidth(.3);
	$this->SetFont('Arial','',9);
    // Header
    for($i=0;$i<count($header);$i++)
        $this->Cell($w[$i],7,$header[$i],1,0,'C',true);
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
    $this->Cell(0,3,'Seite '.$this->PageNo().'/{nb}',0,0,'C');
	// Date du jour
	setlocale(LC_TIME, "");
	$date_du_jour = utf8_encode(strftime('%d.%m.%Y'));
	date_default_timezone_set('Europe/Paris');
	$this->Cell(0,0,$date_du_jour,0,0,'R');
}

// Colored table
function FancyTable($header, $FlugListe)
{
	global 	$RKRKLocalLoactionMissionFiles, 
			$PilotenWahl, 
			$JaegerWahl, 
			$FeldNamenWahl, 
			$FromDate, 
			$ToDate, 
			$Feldkatalog ,
			$MaxEntriesInTable, 
			$WebLink_public ,
			$IconLoc, 
			$w,
			$totalwidth, 
			$lineheight,
			$left, 
			$ManualFieldLabel;

	$localsearchSDZ  = Array("ÃƒÂ¼","ÃƒÂ¶", "Ãƒ?", "ÃƒÂ¤", "Ãƒ?Ã‚Â¼", "Ãƒ?Ã‚Â¤");
	$localreplaceSDZ = Array("Ã¼", "Ã¶", "Ãœ" , "Ã¤" , "Ã¼"   ,"Ã¤");

 // Colors, line width and bold font
    $this->SetFillColor(160,194,145);
    $this->SetDrawColor(160,194,145);
    $this->SetLineWidth(.3);
    $this->SetFont('','B');
	$SumFlaeche = 0;
	$SumFunde = 0;

    // Header
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
	//pre_R($FlugListe );
    // Data
    $fill = false;
	$Num = 0;
	$NumEntry = 0;
	foreach ($FlugListe as $Key => $Flug) {
		//echo "aAA:" . $FeldNamenWahl . " " . $Flug["Jaeger"] . "<br>";
		if (	
				(
					$PilotenWahl == ""
					or ($Flug["Pilot"] == $PilotenWahl)
				)
				and
				(
					$JaegerWahl == ""
					or ($Flug["Jaeger"] == $JaegerWahl)
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
            [Jaeger] => BS
            [Kontaktnummer] => 26-Schicker Bruno, 079 295 15 44
            [Landwirt] => Boss Philip
            [Bemerkung] => 
            [Flugzeit] => 4.5
            [AnzFelder] => 1
            [#Funde] => 0
            [Einsatztag] => 8.05
            [GrundstÃ¼ckID] => 224
            [Gde] => KN
            [JG] => 26
            [Reg] => AmtZH
            [FlÃ¤che] => 1.97
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
				//Col 0
				$this->SetFont('Arial','', 8);
				if (($LastDate!="")
					and (date('Y-m-d',strtotime($Flug["Datum"]))!=$LastDate)) {
					$this->SetLineWidth(1);
					$this->Line($left,$this->GetY(),$totalwidth,$this->GetY()+$lineoffset);
					$this->SetLineWidth(0.2);
				}
				$this->SetX($this->GetX());
				if (
					($Flug["Geflogen"]!="Ja") and
					(date('Y-m-d',strtotime($Flug["Datum"]))<=date('Y-m-d'))
				){
					$this->SetTextColor(100,100,100);
				}
				$this->Cell($w[0],$lineheight,$NumEntry,'LR',0,'L',$fill);
				$this->Cell($w[1],$lineheight,date("d.m.y",strtotime($Flug["Datum"])),'L',0,'',$fill);
				$this->Cell($w[2],$lineheight,$Flug["Zeit"],'R',0,'L',$fill);
				$this->Cell($w[3],$lineheight,$Flug["Pilot"],'LR',0,'LR',$fill);
				$this->SetFont('Arial','B', 8);
				if ($Flug["Geflogen"]=="Ja") {
					$this->SetTextColor(65, 130, 65);
				}
				$this->Cell($w[4],$lineheight,$Flug["Feld"],'',0,'L',$fill, $WebLink_public ."rkrk_map.php?Reg=ALL&Pfad=on&FFilter=".$Flug["Feld"]);
				if ($Feldkatalog[$Flug["JG"]][$Flug["Feld"]]["Feldname"]!="") {
					$this->Cell($w[5],$lineheight,str_replace($localsearchSDZ, $localreplaceSDZ, $Feldkatalog[$Flug["JG"]][$Flug["Feld"]]["Feldname"]),2,0,'LR',$fill, $WebLink_public ."rkrk_map.php?Reg=ALL&Pfad=on&FFilter=".$Flug["Feld"]);
				} elseif (($Flug["Felder"]!="")) {
					$this->Cell($w[5],$lineheight,str_replace($localsearchSDZ, $localreplaceSDZ, $Flug["Felder"]),2,0,'LR',$fill, $WebLink_public ."rkrk_map.php?Reg=ALL&Pfad=on&FFilter=".$Flug["Feld"]);
				} else {
					$this->Cell($w[5],$lineheight,"Manuelles Feld",2,0,'LR',$fill, $WebLink_public ."rkrk_map.php?Reg=ALL&Pfad=on&FFilter=".$Flug["Feld"]);
				}
				if ($Flug["Geflogen"]=="Ja") {
					$this->SetTextColor(0);
				}
				$this->SetFont('Arial','', 8);
				$this->Cell($w[6],$lineheight,str_replace($localsearchSDZ, $localreplaceSDZ, $Feldkatalog[$Flug["JG"]][$Flug["Feld"]]["Flurname"]),'LR',0,'L',$fill);
				if ($Flug["Feld"]!=$ManualFieldLabel) {
					$this->Cell($w[7],$lineheight,$Feldkatalog[$Flug["JG"]][$Flug["Feld"]]["GrundstueckID"],'L',0,'C',$fill);
					$this->Cell($w[8],$lineheight,$Flug["Reg"],'LR',0,'L',$fill);
					$this->Cell($w[9],$lineheight,$Feldkatalog[$Flug["JG"]][$Flug["Feld"]]["JG"],'L',0,'C',$fill);
					$this->Cell($w[10],$lineheight,$Flug["Jaeger"],'',0,'L',$fill);
					$this->Cell($w[11],$lineheight,$Feldkatalog[$Flug["JG"]][$Flug["Feld"]]["Gemeinde"],'L',0,'C',$fill);
				} else {
					$this->Cell($w[7],$lineheight,$Flug["GrundstÃƒÂ¼ckID"],'L',0,'C',$fill);
					$this->Cell($w[8],$lineheight,$Flug["Reg"],'LR',0,'L',$fill);
					$this->Cell($w[9],$lineheight,$Flug["JG"],'L',0,'C',$fill);
					$this->Cell($w[10],$lineheight,$Flug["Jaeger"],'',0,'L',$fill);
					$this->Cell($w[11],$lineheight,$Flug["Gde"] ,'L',0,'C',$fill);
				}	
				$this->Cell($w[12],$lineheight,$Flug["Kontrollflug"],'LR',0,'C',$fill);
				if ($Flug["#Funde"]>0) {
					$this->Cell($w[13],$lineheight,$Flug["#Funde"],'LR',0,'C',$fill);
					$SumFunde += $Flug["#Funde"];
				} else {
					if (date('Y-m-d',strtotime($Flug["Datum"]))<=date('Y-m-d'))
						$this->Cell($w[13],$lineheight,"-",'LR',0,'C',$fill);
					else
						$this->Cell($w[13],$lineheight,"",'LR',0,'C',$fill);
				}
				if ($Flug["Feld"]!=$ManualFieldLabel) {
					$this->Cell($w[14],$lineheight,$Feldkatalog[$Flug["JG"]][$Flug["Feld"]]["Flaeche"],'LR',0,'R',$fill);
					$SumFlaeche += $Feldkatalog[$Flug["JG"]][$Flug["Feld"]]["Flaeche"];
				} else {
					$this->Cell($w[14],$lineheight,$Flug["Flaeche"],'LR',0,'R',$fill);
					$SumFlaeche += $Flug["Flaeche"];
				}
				$this->SetTextColor(0);
				//Next Line
				$this->Ln();
				$fill = !$fill;
				if ($NumEntry> $MaxEntriesInTable) {
					break;
				}
				$LastDate= date('Y-m-d',strtotime($Flug["Datum"]));
			}
		}
	}
	$this->Line($left,$this->GetY(),$totalwidth,$this->GetY()+$lineoffset);
	$this->SetFont('','BI', '');
	$this->Cell($w[0]+$w[1],$lineheight,'Total','',0,'C',$fill);
	$this->Cell($w[2]+$w[3]+$w[4]+$w[5]+$w[6]+$w[7]+$w[8]+$w[9]+$w[10]+$w[11]+$w[12]);
	if ($SumFunde!=0)
		$this->Cell($w[13],$lineheight,$SumFunde,'',0,'C',$fill);
	else
		$this->Cell($w[13]);
	if ($SumFlaeche!=0)
		$this->Cell($w[14],$lineheight,$SumFlaeche,'',0,'R',$fill);
	$this->Ln();
	$this->Line($left,$this->GetY(),$totalwidth,$this->GetY()+$lineoffset);

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

	$xmlMissionLocation.$RKRKFelderfileXML;
	$OptionJaeger = Array();
	$Feldkatalog = GetFeldkatalog($xmlMissionLocation.$RKRKFelderfileXML);
	//pre_R($OptionJaeger);
	//pre_R($Feldkatalog);

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
	$FlugListe = FormatFlugDataArray($FlugListe);
	//pre_r($FlugListe);


$pdf = new PDF();
// Data loading
//$data = $pdf->LoadFieldData('countries.txt');
$pdf->AliasNbPages();
$pdf->SetFont('Arial','',9);
$pdf->AddPage();
$pdf->FancyTable($header,$FlugListe);
$pdf->Endremark();
$Filename = date("Ymd", strtotime($FromDate))."_RKRK_Flugplan".$PilotenWahl;
if ($PilotenWahl!="")
	$Filename .= "_".$PilotenWahl;
$pdf->Output("I", $Filename.".pdf");

?>