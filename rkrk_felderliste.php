<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" >
<?php
	session_start();
	include_once('scripts/rkrk_gen.php');
	include_once('scripts/rkrk_menu.php');
	include_once('scripts/rkrk_flexdisplay.php');
	include_once('scripts/rkrk_csvfiles.php');
	include_once('scripts/rkrk_inputforms.php');
	include_once('scripts/rkrk_felddaten.php');
	include_once('rkrk_access_control.php');
	include_once('scripts/rkrk_channels.php'); 

	// ===========================================
	// Gobal Defintions
	// ===========================================

		global $RegionSettings;
		//$AnzLines = array(10,20,25,30,50,100,"all");
		$FontSizes = array("100%","125%","150%","200%");
		$TableType= "Table"; //Table, Flex
		
		$ScreenTableSetup= $ScreenTableSetupSettings[$TableType];
	// ===========================================
	// Defintions
	// ===========================================
		$fileoutput = "";

	// ===========================================
	// Paramters
	// ===========================================
	//echo "<br><br><br>PHP SELF" . $_SERVER['REQUEST_URI'] . chr(10);
	//if (is_urlEncoded($_SERVER['REQUEST_URI'])) {
	//  echo "URL encoded" . "<br>";
	//} else {
	//  echo "URL NOT encoded" . "<br>";
	//}


	//Region
	$RegionSelected = "ALL";  //default
	if ($SecUrl) 
		$RegionSelected =str_replace("_"," ",str_replace("alle","ALL",htmlspecialchars($SecParamters["Reg"])));
	elseif (isset($_GET["Reg"]))
		$RegionSelected =str_replace("alle","ALL",htmlspecialchars($_GET["Reg"]));
	else 
		$RegionSelected = "ALL"; // default

/*
	if (is_urlEncoded($_SERVER['REQUEST_URI'])) {
		$RegionSelected = htmlspecialchars(GetParamter_from_Encoded_Url($_SERVER['REQUEST_URI'], "Reg"));
	} elseif (isset($_GET["Reg"])) {
		$RegionSelected =htmlspecialchars($_GET["Reg"]);
	} elseif (isset($_POST["Reg"])) {
		$RegionSelected =htmlspecialchars($_POST["Reg"]);
	} else {
		$RegionSelected = "ALL";
	}
*/	
	
	//FeldNamenWahl: FFilter
	$FeldNamenWahl = str_replace("_"," ",GetURLwDefault("FFilter", "FFilter", "", ""));
	$FFilter = $FeldNamenWahl;
	
/*
	if (isset($_GET["FFilter"])) {
		$FeldNamenWahl = $_GET["FFilter"];
	} else {
	//if (isset($_POST["FeldNamenWahl"])) {
	//	$FeldNamenWahl = $_POST["FeldNamenWahl"];
	//} else {
		$FeldNamenWahl = "";
	}
*/	

	$ListType = str_replace("_"," ",GetURLwDefault("Typ", "Typ", "", "table"));
/*
	if (empty($_GET["Typ"])) {
		$ListType = "table";
	} else {
		$ListType =htmlspecialchars($_GET["Typ"]);
	}
*/

	$FieldSelected = str_replace("_"," ",GetURLwDefault("Feld", "Feld", "", ""));
/*
	if (empty($_GET["Feld"])) {
		$FieldSelected = "";
	} else {
		$FieldSelected =htmlspecialchars($_GET["Feld"]);
	}
*/


	$MenuOn = GetURLtoFalseTrue("MenuOn", "MenuOn", "", true);
	$TopMenuOn = GetURLtoFalseTrue("Top", "Top", "", true);
/*
	if (empty($_GET["MenuOn"])) {
		$MenuOn = true;
	} else {
		if (htmlspecialchars($_GET["MenuOn"])=="off") {
			$MenuOn = false;
		} else {
			$MenuOn = true;
		}
	}
*/

SetMissionLocation();
$MissionSelectionParameter = "MDB=" . $RKRKMissionSelected;
//echo "Loc: " . $RKRKMissionSelected . " - MDB: " .$csvDocLocation . "<br>";	
	//$PageTitle= GetRegionalSetting($RegionSelected, "Name");
	$PageTitle= $RegionSettings[$RegionSelected]["Text"];


  
	//check impropriate IP addresses accessing page
	CheckIPandExit();
	$ImageHeight = 80;
	$ButtonHeightSmall =30;
	$PicSize=25;
	$Layer=true;	

// Example to call the site with options:
//https://tchr-frog.synology.me:5682/rkropen/test/test2023_flex_template_rkrk.php?Reg=21,25,28&Area=on&Poi=on&FFilter=28-029%2021-031#map

	$pagetitle="Feldlisten";
	headerpage($pagetitle, false);
	GrantAccess();
	if ($TopMenuOn) {
		CreateTopMenu();
	}
	
	PrintRKRKHeader($pagetitle);
	//$MenuTree = CreateMenuTree("");
	//DoMenu($MenuTree);		



		//WARNUNG!!
		//echo "<span style='background-color: #FDEDEC;'>". chr(10);
		//echo "<i><Funktion nicht freigegeben! Nur zum Testen. Freigabeprozesse gegenüber den Jägern müsste noch gelöst werden, bevor einsetztbar!</i><br>". chr(10);
		//echo "</span>". chr(10);

	$OptionJaeger = Array();
	$Feldkatalog= GetFeldkatalog($xmlMissionLocation.$RKRKFelderfileXML);
	$FeldkatalogVorhanden = is_array($Feldkatalog) && $Feldkatalog !== array();
	//pre_r($Feldkatalog);
	$FeldkatalogList = GetFeldKatalogList ($Feldkatalog);
	aasort($FeldkatalogList, "FeldID");
	//aasort($FeldkatalogList, $ColSort);	//pre_r($FeldkatalogList);
	//pre_R(array_keys($FeldkatalogList));
	//pre_R($FeldkatalogList["136-002"]);
	
	
	//Read CSV file
	$csv_file= $RKRKContactFile;
	transferGoogleSheetToCSV();
	$Adresskatalog = csv_to_array($csvMissionLocation.$csv_file,';');


	foreach ($RegionSettings as $Reg) {
		$RegDataArray[$Reg['Reg']]['Reg']= $Reg['Reg'];
		$RegDataArray[$Reg['Reg']]['RegUp']= $Reg['RegUp'];
		foreach ($Adresskatalog as $Person) {
			if ((strpos(" " . $Person['Jagdgebiet'], strval($Reg['Reg'])) !== false) and
				(strpos(" " . $Person['Jagdgebiet'], "(". strval($Reg['Reg']) .")" ) == false)) {
				$PilotDataArray[$Reg['Reg']]['Reg']=$Reg['Reg'];
				$PilotDataArray[$Reg['Reg']]['Pilot']=$Person['Initialen'];
			}
		}
	}
	//pre_R($RegDataArray);
	//pre_R($PilotDataArray);
	


	// ===========================================
	// Variables
	// ===========================================

	$InputParamterSettings = Array(
				"Options" => Array(
					"DBW" => Array(
							"Text" => "Select Debug Database",
							"Icon" => "",
							"Value" => false,
							"Options" => "ON,OFF"
						),	
					),	
				);


Function CreateOutputLine($dataline, $TableSetup, $line, $MaxLine, $Type="data") {
	global $FlugzeitTreshold, $FluzeitStartUndLandung, $ScreenTableSetup, $MainMenu, $RKRKLocalLoactionMissionFiles, $ImageHeight,$OptionFrei,$OptionWarning,$IconLoc,$GenIconLoc, $Flughoehe, $OptionFlughoehe, $ButtonHeightSmall,$Adresskatalog,$FlyFileSetting, $RegionSelected, $searchUML, $replaceUML, $ListType, $TopMenuOn;
	$FonzSize2 = $FonzSize2 ?? '';
	$FonzSize3 = $FonzSize3 ?? '';
	$FonzSize4 = $FonzSize4 ?? '';
	while ($line <= $MaxLine) {
		//pre_R($dataline);
		//echo $line . " " . $dataline["Datum"]. " " . $dataline["Pilot"]. " " . $dataline["Feld"] . "<br>";
		echo $ScreenTableSetup["<tr>"] .chr(10);
		echo $ScreenTableSetup["<td>"] .chr(10);
		echo "<b>" . $dataline['FeldID'] . "</b><br>" .chr(10);
		echo "<a id='" . $dataline['FeldID']."' href='";
		if (GetAccess("ExtendedMap")) // intern
			echo $MainMenu['FeldKarteIntern']['site']."?".$MainMenu['FeldKarteIntern']['parameter'];
		else
			echo $MainMenu['FeldKarteExtern']['site']."?".$MainMenu['FeldKarteExtern']['parameter'];
		echo "&FFilter=" . $dataline['FeldID'] . "' target='" .  $MainMenu['FeldKarteExtern']['target']. "'>";
		echo "<p style='font-size:1em'>". chr(10);
		echo $dataline['Feldname']. "</b> " . chr(10);
		if ($dataline['Flurname'] != "") {
			echo  " ("  . $dataline['Flurname'] .  ") " . $dataline['GrundstueckID']; 
		} else {
			echo " - " . $dataline['GrundstueckID']; 
		}
		//echo "<b> " . $dataline['FeldID'] . "</b>" . chr(10);
		echo "</p></a>" . chr(10);
		echo "<p style='font-size:"  . $FonzSize3 . "'>" . chr(10);
		if ($dataline['Gemeinde']!="") {
			echo "Gde: " . $dataline['Gemeinde'];
		}
		if (($dataline['GrundstueckID'] ?? '') !== '') {
			echo ", Gis#: " . $dataline['GrundstueckID'];
		}
		if ($dataline['Jaeger']!="") {
			echo ", Jgr: " . $dataline['Jaeger'];
		}  
		if ($dataline['Funde']!="") {
		  echo ", Funde: " . $dataline['Funde'];
		}  
		echo "<br>" . chr(10);
		echo $dataline['Flaeche'] . "ha, ";

		//if ($dataline['Flugzeit']>$FlugzeitTreshold) {
			// Zeit in Sekunden
		//    echo round($FluzeitStartUndLandung+($dataline['Flugzeit'])/60,1) . "min, ";
		//} else {
			// Zeit in Minuten
		    echo $FluzeitStartUndLandung+$dataline['Flugzeit'] . "min, ";
		//}	
		echo $dataline['Wegpunkte'] . "WP, ";
		echo $dataline['Distanz'] . "m ";
		echo "</p>" . chr(10);
		echo $ScreenTableSetup["</td>"] .chr(10);
		echo $ScreenTableSetup["<td>"] .chr(10);
		echo "<p style='font-size:"  . $FonzSize2 . "'>" . chr(10);
		if ($dataline['Funde'] != "") {
			echo  " <img src='" . $IconLoc . "/reh.png' title='Rehkitz gerettet' height='" . $ButtonHeightSmall ."'>" ; 
		}
		foreach ($OptionFrei  as $key => $Frei) {
			if (like($dataline['Freigabe'], $key)) {
				echo  " <img src='" . $IconLoc . $Frei["Pic"] . "' title='" . $Frei["Text"] . "' height='" . $ButtonHeightSmall ."'>" . chr(10); 
			}
		}
		foreach ($OptionWarning as $key => $Warning) {
			if (like($dataline['Warnung'], $key)) {
				echo  " <img src='" . $IconLoc . $Warning["Pic"] . "' title='" . $Warning["Text"] . "' height='" . $ButtonHeightSmall ."'>" . chr(10); 
			}
		}
		echo "</p>" . chr(10);
		// Interne Angaben
		if (GetAccess("ExtendedMap")) {
			echo "<p style='font-size:"  . $FonzSize4 . "'>" . chr(10);
			if ($dataline['Bemerkung']!="") {
				echo str_replace(",", ", <br>", str_replace($searchUML, $replaceUML, $dataline['Bemerkung'])) . ", ";
				echo "</br>" . chr(10);
			}
			if ($dataline['Bauer']!="") {
				echo str_replace("T:", "<br>T: ", str_replace("M:", "<br>M: ", str_replace(",", ", <br>", str_replace($searchUML, $replaceUML, $dataline['Bauer'])))) . ", ";
				echo "</br>" . chr(10);
			}
			if ($dataline['Notiz']!="") {
				echo str_replace(",", ", <br>", str_replace($searchUML, $replaceUML, $dataline['Notiz'])) . ",";
			}
			echo "</p>" . chr(10);
		}
		echo $ScreenTableSetup["</td>"] .chr(10);
		echo $ScreenTableSetup["<td>"] .chr(10);
		echo "<p>" . chr(10);
		if (GetAccess("AllPilots")){
			echo " <a href='rkrk_fluege.php?add=Y&AddFlugID=" . $dataline['FeldID'] . "&AddFlugToDate=" . date("Y-m-d", strtotime('tomorrow')). "' target='_editfluege'><img src='" . $IconLoc . "/addflug.png' height='" . $ButtonHeightSmall . "px' title='Flugfeld " . $dataline['FeldID'] . " für Morgen hinzugfügen'></a>  ";
		}
		// Nehme Parkplatz falls definiert, ansonsten den Startpunkt
		if (($dataline['Grundstueckdaten']['Parkplatz'] ?? '')!="") {
			$StartLat = explode(",", $dataline['Grundstueckdaten']['Parkplatz'])[0];
			$StartLon = explode(",", $dataline['Grundstueckdaten']['Parkplatz'])[1];
		}
		elseif (($dataline['Grundstueckdaten']['Startplatz'] ?? '')!="") {
			$StartLat = explode(",", $dataline['Grundstueckdaten']['Startplatz'])[0];
			$StartLon = explode(",", $dataline['Grundstueckdaten']['Startplatz'])[1];
		}
		if ($StartLat != "" && $StartLon !='') {
			echo "<!--startpunkt--><a href='https://maps.google.ch/?q=". trim($StartLon) . " ," . trim($StartLat) . "&t=k'><img src='" . $IconLoc . "\startpnt.png' height='" . $ButtonHeightSmall ."'></a> " . chr(10);
			echo "<!--geomap--><a href='rkrk_map.php?Reg=" . $RegionSelected . "&Strt=on&Cntr=off&Area=on&Pfad=on&Poi=off&EX=NO&KA=S&MAP=1&Zoom=0.8&LAT=" . trim($StartLat) . "&LON=" . trim($StartLon) ."&MAP=9" . ($TopMenuOn ? "" : "&Top=off&Side=off") . "' target='_geomap'><!--Start auf MAPGEO--><img src='" . $IconLoc . "\geomappos.png' height='" . $ButtonHeightSmall ."'></a> " . chr(10);
		}
		echo "<p>" . chr(10);
		echo "</p>" . chr(10);
/**/
		foreach ($FlyFileSetting as $FileKey => $FileType) {
			//echo "echo File ". $dataline[$FileType["XMLLabel"]];
			if (GetAccess($FileType["Access"]) and
				in_array("felderliste", explode(",",$FileType["Show"]))){
				$LinkData = explode(",", $dataline[$FileType["XMLLabel"]]);
				foreach ($LinkData as $Link) {
					//echo $Link . "<br>";
					$Flightheight= GetMeaningFilename($Link)[4];
					//echo $IconLoc . "/" . $FileType["icon"] . $Flightheight . ".png";
					if (file_exists($IconLoc . "/" . $FileType["icon"] . $Flightheight . ".png")) {
						$Picture= $IconLoc . "/" . $FileType["icon"] . $Flightheight . ".png";
					} else {
						$Picture= $IconLoc . "/" . $FileType["icon"] . "xx" . ".png";
					}	
/*
					foreach ($OptionFlughoehe as $Flughoehe) {
						if (strpos($Link,"_" . $Flughoehe["Opt"] . "_") != false) {
							$Flightheight= $Flughoehe["Opt"];
							$Picture =  $IconLoc . chr(92) . $FileType["icon"] . $Flughoehe["Opt"] . ".png";
						}
					}
*/
					if ($Link !="") {
						$href = $RKRKLocalLoactionMissionFiles.$Link;
						if (($FileType["XMLLabel"] ?? '') === "KMZFile") {
							$convHref = rkrk_kmz_convert_download_href($Link);
							if ($convHref !== '') {
								$href = $convHref;
							}
						}
						echo "<a href='" . $href . "' download><img src='" . $Picture . "' height='" . $ButtonHeightSmall ."' title='" .  $FileType["Text"] . " " . $dataline["FeldID"] . " - " . $Link . "'></a> <wbr>" . chr(10);
					}
				}
			}
		}
/**/
/*
		if (GetAccess("MapDownloadLitchi")){
			$LinkData = explode(",", $dataline["LichtiFile"]);
			foreach ($LinkData as $Link) {
				foreach ($OptionFlughoehe as $Flughoehe) {
					if (strpos($Link,"_" . $Flughoehe["Opt"] . "_") != false) {
						$Flightheight= $Flughoehe["Opt"];
						$Picture =  $IconLoc . "\litchi" . $Flughoehe["Opt"] . ".png";
					}
				}
				if ($Link !="") {
					echo "<a href='" . $RKRKLocalLoactionMissionFiles.$Link . "'><img src='" . $Picture . "' height='" . $ButtonHeightSmall ."' title='Litchi File " . $dataline["FeldID"] . " - " . $Link . "'></a> <wbr>" . chr(10);
				}
			}
		}
		// Download KMZ
		if (GetAccess("MapDownloadKMZ")){
			$LinkData = explode(",", $dataline["KMZFile"]);
			foreach ($LinkData as $Link) {
				foreach ($OptionFlughoehe as $Flughoehe) {
					if (strpos($Link,"_" . $Flughoehe["Opt"] . "_") != false) {
						$Flightheight= $Flughoehe["Opt"];
						$Picture =  $IconLoc . "\kmz" . $Flughoehe["Opt"] . ".png";
					}
				}
				if ($Link !="") {
					echo "<a href='" . $RKRKLocalLoactionMissionFiles.$Link . "'  download target='_blank'><img src='" . $Picture . "' height='" . $ButtonHeightSmall ."' title='Jason File " . $dataline["FeldID"] . " - " . $Link . "'></a> <wbr>" . chr(10);
				}
			}
		}
		// Download KMZ
		if (GetAccess("MapDownloadJSON")){
			$LinkData = explode(",", $dataline["JasonFile"]);
			foreach ($LinkData as $Link) {
				foreach ($OptionFlughoehe as $Flughoehe) {
					if (strpos($Link,"_" . $Flughoehe["Opt"] . "_") != false) {
						$Flightheight= $Flughoehe["Opt"];
						$Picture =  $IconLoc . "\json" . $Flughoehe["Opt"] . ".png";
					}
				}
				if ($Link !="") {
					echo "<a href='" . $RKRKLocalLoactionMissionFiles.$Link . "'  download target='_blank'><img src='" . $Picture . "' height='" . $ButtonHeightSmall ."' title='Jason File " . $dataline["FeldID"] . " - " . $Link . "'></a> <wbr>" . chr(10);
				}
			}
		}
/**/
		echo "</p>" . chr(10);
		echo $ScreenTableSetup["</td>"] .chr(10);
		echo $ScreenTableSetup["<td>"] .chr(10);
		echo "<p>" . chr(10);
		// Contact Pilot
		if (GetAccess("PilotPartners") and
			($dataline['JG']!="")) {
			$Feld = $dataline['FeldID'] . "  " .str_replace($searchUML, $replaceUML, $dataline['Feldname']) . chr(10);
			if ($dataline['Flurname'] != "") {
				$Feld .= " ("  . str_replace($searchUML, $replaceUML, $dataline['Flurname']) .  ") " . $dataline['GrundstueckID']; 
			}
			$Betreff = "Rehkitzrettung Feld " . $Feld;
			$Text= "Wir möchten das Feld " . $Feld . " wie folgt für die Rehkitzrettung mit Drohnen abfliegen lassen: ";
			foreach ($Adresskatalog as $data) {
				if (in_array($dataline['JG'],explode(",",$data["Jagdgebiet"])) and
					("Pilot"==$data["Typ"])
					) {
					
					echo "<br>Pilot: " . $data["Name"] . "<br>";
					echo "<a href='tel:" . $data["Kontaktnummer"] . "'><img src='" . $GenIconLoc . "/call.png' height='" . $ButtonHeightSmall ."'></a> " . chr(10);
					echo rkrk_whatsapp_anchor($data["Kontaktnummer"], $Feld, $GenIconLoc . "/whatsapp.png", $ButtonHeightSmall) . chr(10);
					echo " <a href='mailto:"  . $data["Mail"]. "?subject=". $Betreff . "&body=" . $Text . "'><img src='" . $GenIconLoc . "/mail.png' height='" . $ButtonHeightSmall ."'></a> " . chr(10);
					if ($ListType =="intern" or $ListType =="listintern") {
						echo "Kontakt:  " . $data["Kontaktnummer"] . "<wbr>";
						echo ", Mail:  <a href='mailto:'"  . $data["Mail"]. "?subject=". $Betreff . "&body=" . $Text . "'>"  . $data["Mail"]. "</a>" . chr(10);
					}
					//break;
				}
			}
		}
		// Contact Jaeger
		//echo $dataline['Jaeger'] . "<br>";
		if (GetAccess("PilotPartners") and
			($dataline['Jaeger'] !=""))
			{
			$Feld = $dataline['FeldID'] . "  " .str_replace($searchUML, $replaceUML, $dataline['Feldname']) . chr(10);
			if ($dataline['Flurname'] != "") {
				$Feld .= " ("  . str_replace($searchUML, $replaceUML, $dataline['Flurname']) .  ") " . $dataline['GrundstueckID']; 
			}
			$Betreff = "Rehkitzrettung Feld " . $Feld;
			$Text= "Hoi ";
			foreach ($Adresskatalog as $data) {
				if (in_array($dataline['Jaeger'],explode(",",$data["Initialen"])) and
					(("Jäger"==$data["Typ"]) or ("Jäger Amt"==$data["Typ"]))) 
					{
					echo "<br>Jäger: " . $data["Name"] . "<br>";
					echo "<a href='tel:" . $data["Kontaktnummer"] . "'><img src='" . $GenIconLoc . "/call.png' height='" . $ButtonHeightSmall ."'></a> " . chr(10);
					echo rkrk_whatsapp_anchor($data["Kontaktnummer"], $Feld, $GenIconLoc . "/whatsapp.png", $ButtonHeightSmall) . chr(10);
					echo " <a href='mailto:"  . $data["Mail"]. "?subject=". $Betreff . "&body=" . $Text . "'><img src='" . $GenIconLoc . "/mail.png' height='" . $ButtonHeightSmall ."'></a> " . chr(10);
					//echo "<b>Jäger:  ". $data[$Nmecol] . "</b><br>" .chr(10);
					if ($ListType =="intern" or $ListType =="listintern") {
						echo "Kontakt:  " . $data["Kontaktnummer"] . "<wbr>" .chr(10);
						echo ", Mail:  <a href='mailto:'"  . $data["Mail"]. "?subject=". $Betreff . "&body=" . $Text . "'>"  . $data["Mail"]. "</a>".chr(10);
					}
					//break;
				}
			}
		}
		echo "</p>" . chr(10);
		echo $ScreenTableSetup["</td>"] .chr(10);
		echo $ScreenTableSetup["<td>"] .chr(10);
		//echo "<i>Suchfeld:</i><br>" . chr(10);
		$SuchfeldLink = $dataline["SuchfeldBild"];
		$SuchfeldBild = explode(",", $SuchfeldLink);
		echo "<a href='" . $RKRKLocalLoactionMissionFiles.$SuchfeldBild[0]. "'><img src='" . $RKRKLocalLoactionMissionFiles.$SuchfeldBild[0]. "' id='thumpnail' height='" . $ImageHeight ."' width='" . $ImageHeight ."' onmouseover='Large(this)'></a>";
		echo $ScreenTableSetup["</td>"] .chr(10);
		echo $ScreenTableSetup["<td>"] .chr(10);
		//echo "<i>FlugplanBild:</i><br>" . chr(10);
		$FlugplanBilddLink = $dataline["FlugplanBild"];
		$FlugplanBild = explode(",", $FlugplanBilddLink);
		$SelectedFlugplanBild = GetFlugplanbild($FlugplanBild,$dataline["StdFlughohe"]);
													
		echo "<a href='" . $RKRKLocalLoactionMissionFiles.$SelectedFlugplanBild. "'><img src='" . $RKRKLocalLoactionMissionFiles.$SelectedFlugplanBild. "' id='thumpnail' height='" . $ImageHeight ."' width='" . $ImageHeight ."' onmouseover='Large(this)'></a>";
		echo $ScreenTableSetup["</td>"] .chr(10);
		$line++;
		echo $ScreenTableSetup["</tr>"] .chr(10);
	}
}


Function CreateTitleField($TableEntry) {
	global $ScreenTableSetup;
	//if (($TableEntry["Show"]== true) or ($debugmode))
	//{
		echo $ScreenTableSetup["<th>"] .chr(10);
		echo "Name" .chr(10);
		echo $ScreenTableSetup["</th>"] .chr(10);
		echo $ScreenTableSetup["<th>"] .chr(10);
		echo "Info" .chr(10);
		echo $ScreenTableSetup["<th>"] .chr(10);
		echo "Links" .chr(10);
		echo $ScreenTableSetup["</th>"] .chr(10);
		echo $ScreenTableSetup["<th>"] .chr(10);
		echo "Kontakte" .chr(10);
		echo $ScreenTableSetup["</th>"] .chr(10);
		echo $ScreenTableSetup["<th>"] .chr(10);
		echo "Suchfeld" .chr(10);
		echo $ScreenTableSetup["</th>"] .chr(10);
		echo $ScreenTableSetup["<th>"] .chr(10);
		echo "Flugplan" .chr(10);
	//}
}



Function CreateTitleLine($TableSetup) {
	global $ScreenTableSetup;
	echo $ScreenTableSetup["<tr>"] .chr(10);
	//echo "Title" . "<br>";
	//foreach ($TableSetup AS $TableEntry) {
	CreateTitleField(array());
	//}
	echo $ScreenTableSetup["</tr>"] .chr(10);
}



// Update Flightdata
if (!isset($FromDate) || $FromDate === '') {
	$FromDate = date('Y-m-d');
}
$Year = date('Y', strtotime($FromDate));
UpdateFlightsAndSaveKatalog(strval($Year));


		// ===========================================
		// Print Page content
		// ===========================================
		$output = "";
		
		// Printing all the keys and values one by one

// =====================
// Prepare Page output
///=====================

	//pre_r(array_keys($Feldkatalog));
	//control elements
	// Page control disabled
	if (isset($_POST["AnzLinesPPage"])) {
		if ($_POST["AnzLinesPPage"]=="all") {
			$AnzLinesPPage= 999;
		} else {
			$AnzLinesPPage= $_POST["AnzLinesPPage"];
		}
	} else {
		$AnzLinesPPage= 25;
	}
	if (isset($_GET["ActPage"]) and $_GET["ActPage"]!="") {
		$ActPage= $_GET["ActPage"];
	} else {
		$ActPage= 1;
	}
	if (isset($_POST["FontSize"]) and $_POST["FontSize"]!="") {
		$FontSize= $_POST["FontSize"];
	} else {
		$FontSize= "100%";
	}


	// ===========================================
	// FORM
	// ===========================================

    
//Initialen,JG,Jagdgebiet,Name,Kontaktnummer,P Telefon,Mail,Adresse,RKRS,Bemerkung

	if ($FontSize== "") {
		$FontSize= "100%";
	}	
	
	SetTableWidth("100%");
	//page

	$OptionRKRKS = Array("Ja"=>"Ja","Nein"=>"Nein","-"=>"-");
	$i= 1;
	$DataSetFound = false;

// ===========================================
// HEADER FORM
// ===========================================
?>
<style>
#planung 
{
    vertical-align : top;
    position : absolute;
    background : transparent; 
    filter: Alpha(Opacity=100);
    overflow : hidden;
    height : 70px;
    width : 350px;
    z-index : 5;
}

#imgbox 
{
    vertical-align : middle;
    position : absolute;
    border: 1px solid #999;
    background : #FFFFFF; 
    filter: Alpha(Opacity=100);
    visibility : hidden;
	height : 600px;
    width : 600px;
	z-index : 2;
    overflow : hidden;
    text-align : center;
	border-radius: 2%;
}

#thumpnail 
{
    height : 200px;
    width : 200px;
	border-radius: 5%;
}
</style>

<?php

$EnabledRegion = Array();

//form to change setting
$formAction = htmlspecialchars($_SERVER["PHP_SELF"], ENT_QUOTES, 'UTF-8');
if (!$TopMenuOn) {
	$formAction .= '?Top=off';
}
echo '<form method="post" action="'. $formAction . '">';


$i= 0;
$NoSelection = true;

if ($MenuOn) {
	echo "<span  id='menu'>" .chr(10);
} else {
	echo "<span hidden id='menu'>" .chr(10);
}	


//echo $i . " " . $KMLFileSettings->name . chr(10)
	if ($Layout!="2023") {
		echo MissionLibraryButtons("", false);
		echo CreateSiteLink ("EditFeld", "");
		echo CreateSiteLink ("EditFeldliste", "");
		echo CreateSiteLink ("Fluege", "");
		echo CreateSiteLink ("FeldKarte", "Reg=ALL" . $StandardSettingMap);
		echo CreateSiteLink ("Home", "");
	}

	echo "<table width=100%>";

		// ===========================================
		// Spezielle Filter
		// ===========================================
		$i=1;
		//Write to LOGfile
		WriteToLog("get", basename($_SERVER['PHP_SELF']), "Edit Fluege ", $FeldID ?? '', $RKRKMissionSelected,"");

	echo "<tr><td>";
	echo "Regionen: ";
	echo "</td><td colspan=2>";
	$GrpName = "Opt";
	$Gruppe = 300;
	$i= $Gruppe;
	//process Regions preselections
	if ($RegionSelected!="") {
		$GetRegions = multiexplode(array(" ", ","),$RegionSelected);
		foreach ($GetRegions as $Region) {
			$TmpArr[$Region] = $Region;
		}
		$GetRegions = $TmpArr;
	} else
		$GetRegions = array();
/*
	if (isset($_GET["Reg"])) {
		$GetRegions = multiexplode(array(" ", ","),$_GET["Reg"]);
		foreach ($GetRegions as $Region) {
			$TmpArr[$Region] = $Region;
		}
		$GetRegions = $TmpArr;
	} else
		$GetRegions= "";
*/
	//process Regions form
	//pre_r($RegionSettings);
	$ListOfRegions = "";
	$activeRegCount = 0;
	Foreach ($RegionSettings as $Reg) {
		if ($Reg["Aktiv"]) {
			$RegOptions[$Reg["Reg"]]["Text"]= $Reg["Reg"];
			if (!empty($GetRegions[$Reg["Reg"]])) { 
				$RegOptions[$Reg["Reg"]]["Value"]=1;
				$ListOfRegions .= $Reg["Reg"] . ",";
			} elseif (isset($_POST[$GrpName."-".$i])) {
				$RegOptions[$Reg["Reg"]]["Value"]= $_POST[$GrpName."-".$i];
				$ListOfRegions .= $Reg["Reg"] . ",";
			} else
				$RegOptions[$Reg["Reg"]]["Value"]= false;
			$i++;
			$activeRegCount++;
		}	
	}
	$ListOfRegions = substr($ListOfRegions,0, -1);
	$leerOptIndex = $Gruppe + $activeRegCount;
	$leerOn = false;
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$leerOn = isset($_POST[$GrpName . '-' . $leerOptIndex]) && strcasecmp((string)$_POST[$GrpName . '-' . $leerOptIndex], 'leer') === 0;
	} else {
		$regTrim = trim((string)$RegionSelected);
		$leerOn = (strcasecmp($regTrim, 'leer') === 0 || strcasecmp($regTrim, 'NO') === 0);
	}
	$RegOptions['leer'] = array('Text' => 'leer', 'Value' => $leerOn ? 1 : false);
	$EnabledOptions["Regions"] = PrintSelection($RegOptions , $GrpName ,$Gruppe, true);
	// JG/Region selection
	if ($ListOfRegions !== '' && $ListOfRegions !== false) {
	foreach (explode(",",$ListOfRegions) as $Reg) {
		if (!isset($RegionSettings[$Reg]["JGList"])) {
			continue;
		}
		$JGs2Select = explode(",", $RegionSettings[$Reg]["JGList"]);
		foreach ($JGs2Select as $JGtoSelect)
		{
			$RegionsSelected[$JGtoSelect] = $JGtoSelect;
			$RegOptions[$JGtoSelect]["Value"]=1;
			//$EnabledOptions["Regions"]["List"][$JGtoSelect] =$JGtoSelect;
		}
	}
	}
	echo '<br>'. chr(10);
	$i=1;
	echo "</td>";
	//if ($Layout !== "2023") {
	//	$logo= "<td valign='top' align='right'  rowspan=1>";
	//	$logo.= "IG ". chr(34) ."Rehktitzrettung Knonaueramt und Zug". chr(34) ."<br><a href='http://www.drohnenrehkitzrettung.ch' target='blank'>www.drohnenrehkitzrettung.ch</a>";
	//	$logo.= "   </td>". chr(10);
	//	$logo.= "   <td width='80' align='right' valign='top' rowspan=1>";
	//	$logo.= "<img src='" . $IconLoc . "/202121 Logo-RKRKZ.png' height='80px'>". chr(10);
	//	$logo.= "</td>". chr(10);
	//	echo $logo;
	//}
	if ($fileloaded=true) {
		foreach ($FeldkatalogList as $Key => $Feld) {
			// selected to show
			if (	
					(  
						$FeldNamenWahl == "" 
						or like($Feld["FeldID"], strval($FeldNamenWahl))
						or (strpos($FeldNamenWahl, strval($Feld["FeldID"]))!== false)
						or like($Feld["Kontaktnummer"], $FeldNamenWahl)
						or like($Feld["Jaeger"], $FeldNamenWahl)
						or like($Feld["Bemerkung"], $FeldNamenWahl)
						or like($Feld["Felder"], $FeldNamenWahl)
						or like($Feld["Landwirt"], $FeldNamenWahl)
					) 
					and 
					(
						$FieldSelected == "" or
						$FieldSelected==$Feld["FeldID"]
						
					)
					and
					(
						(in_array($Feld["JG"], $EnabledOptions["Regions"]["List"])
						or 	(in_array("alle", $EnabledOptions["Regions"]["List"])))
					)
				) 
			{
				//echo $FieldSelected . " - " .$Feld["FeldID"];
				$FeldkatalogList[$Feld["FeldID"]]["ListNr"]= $i;
				//echo "A: " . $Feld["FeldID"] . "  " . $i . "/  "; 
				$i++;
			} else {
				$FeldkatalogList[$Feld["FeldID"]]["ListNr"]= "";
				//echo "B: " . $Feld["FeldID"] . "/  "; 
			}
			//pre_r($Flug);
		}
		$MaxSelectedEntries = $i;
	}
	//pre_r($FeldkatalogList);


	//Tabellen Modus


	echo "</td>";
	echo "</tr>";
	echo "<tr><td>";
	echo "  Suche: " .chr(10);
	echo "</td><td>";
	$TableName ="'myTB'";
	$FeldName ="'FeldNamenWahl'";
	//echo '<input type="text" id="FeldNamenWahl" onkeyup="FilterListe('.$FeldName.','.$ListName.')" placeholder="Suche Feldname" title="Feldname eingeben">';
	echo '<input type="text" name="FeldNamenWahl" id="FeldNamenWahl" value="'.$FeldNamenWahl.'" onkeyup="FilterTable('.$FeldName.','.$TableName.')" placeholder="Suche FeldID, Feldname oder GEOID" title="In eingeben" size="40">';
	
	//function InputJSFunctionOnKeyUp($FieldName, $Value, $JSFunction, $JSValue, $Size=10, $align="left", $type="", $class="") {
	echo "</td><td>";
	if (((($ListOfRegions!="ALL") and ($ListOfRegions!="") and ($ListOfRegions!="all")) or $FFilter!="") and ($ListOfRegions!="ZG") and ($ListOfRegions!="Amt") and (count(explode(" ", trim($ListOfRegions)))==1)) {
		echo CreateSiteLink ("PdfFelder", "Reg=" . $ListOfRegions ."&FFilter=" . $FFilter);
		echo CreateSiteLink ("PdfFelderAdr", "Reg=" . $ListOfRegions ."&FFilter=" . $FFilter."&BEW");
	} else {
		//wenn mehr als eine Region gewählt wird, kann kein PDF erstellt werden.
		echo "<img src='" . $IconLoc . "/" . "pdf_button_disbled.png' height='" . $ButtonHeight . "px' title='PDF nur für einzelne Regionen verfügbar'>";
	}
	echo  MissionLibraryButtons("",true);


	echo "</td></tr>";
	echo "</table  width=100%>";

	$FontSizeInPoints = 11;
	//if ($FontSize== "") {
		$FontSize= "100%";
	//}
	$TableColor =  GetTableColor ($BaseColor);
	AlternateLineTable($TableColor['even'], $FontSize, 12, $TableColor['odd']);

	echo "<input type='submit' name='Einlesen' value='Einlesen' formaction='" . $formAction . "'> ". chr(10);
	echo "<br>";
	echo "</span>" .chr(10);

	echo '<span z-index:0;>';
	$i=1;
	// page control disabled
	//$pageline = 1;
	//$MaxPages = ceil($MaxSelectedEntries/$AnzLinesPPage);
	//$showfrom = ($ActPage-1)*$AnzLinesPPage - 1;
	//$showto = $showfrom + $AnzLinesPPage;
	//echo "AnzLinesPPage: " .$AnzLinesPPage . "<br>";
	//echo "Show: " .$showfrom . " - " . $showto ."<br>";

	echo '<form method="post" action="'. $formAction . '">'. chr(10);
	echo "<br>" . chr(10);
	// page control disabled
	//if ($TableType=="Table") {
	//	AlternateLineTable($colorTableEven, $FontSize);
	//	echo $ScreenTableSetup["<div>"].chr(10);
	//	PageControl($ActPage, $MaxPages, $AnzLinesPPage, $FontSize, false);
	//	echo $ScreenTableSetup["</div>"].chr(10);
	//}
	//echo "<div class='container content line-left padding-8' style='max-width:100%' id='table'>".chr(10);
	echo ($ScreenTableSetup["<table line>"] ?? '');
	//echo  "<div class='container content center padding-16'>";
	//echo $ScreenTableSetup["<table>"] .chr(10);

	// page control disabled
	//if ($TableType=="Flex") {
	//	echo $ScreenTableSetup["<tr>"] .chr(10);
	//	echo $ScreenTableSetup["<td line>"] .chr(10);
	//	PageControl($ActPage, $MaxPages, $AnzLinesPPage, $FontSize, false, "Einlesen", false);
	//	echo $ScreenTableSetup["</td>"] .chr(10);
	//	echo $ScreenTableSetup["</tr>"] .chr(10);
	//}
	//echo $ScreenTableSetup["</table>"] .chr(10);

	
	//form to change setting
	echo "<table id='myTB'>";
	if (empty($FeldkatalogVorhanden)) {
		$droneName = (string)($RKRKMissionSelected ?? '');
		if (isset($MissionDroneTypes[$droneName]["Name"])) {
			$droneName = (string)$MissionDroneTypes[$droneName]["Name"];
		}
		if ($droneName === '') {
			$droneName = 'diesen Drohnentyp';
		}
		echo "<p>Feldkatalog für " . htmlspecialchars($droneName, ENT_QUOTES, 'UTF-8') . " nicht vorhanden.</p>" . chr(10);
	}
	//echo $ScreenTableSetup["<table>"] .chr(10);
	CreateTitleLine(array());

	// Extisting Entries
	$PrevPilot ="";
	$PrevJaeger ="";
	$ActDate =$FromDate;
	$Tomorrow = date('Y-m-d', strtotime('tomorrow'));
	$PrevDate =$Tomorrow;
//https://tchr-frog.synology.me:5682/rkropen/test/rkrk_map.php?FFilter=25-048+25-049+21-001+21-004+21-007&Reg=ALL&Strt=on&Cntr=off&Area=on&Pfad=on&GDE=off&JGG=off&Poi=on&Label=off&EX=NO&KA=S&MAP=&Zoom=0.8
	$GeoMapURL1 = "rkrk_map.php?FFilter=";
	$GeoMapURL2 ="&Reg=ALL&Strt=on&Cntr=off&Area=on&Pfad=on&GDE=off&JGG=off&Poi=on&Label=off&EX=NO&KA=S&MAP=&Zoom=0.8";
	foreach ($FeldkatalogList as $dataline) {
		//pre_R($dataline);
		if ($dataline["ListNr"]!=""
			//Page control disabled
			//$dataline["ListNr"]>=$showfrom && 
			//$dataline["ListNr"]<=$showto
			){
			// Separator
/*			if (
				(($Mode=="P") and
				(($PrevPilot !=$dataline['Pilot'])) or
				($PrevDate !=$dataline['Datum']))
				or
				(($Mode=="J") and
				(($PrevJaeger !=$dataline['Jaeger'])) or
				($PrevDate !=$dataline['Datum']))
				)			
			{
				if ($PrevDate=="") {
					$defaultvalues["Datum"] = $Tomorrow;
				} else {
					$defaultvalues["Datum"] = $PrevDate;
				}
				//$ActPilot = $dataline['Pilot'];
				$DayFieldList = array();
				$MaxEntry = $i+1;
				$PilotDayFieldList =array();
				$JaegerDayFieldList =array();
				$defaultvalues["Pilot"] = $PrevPilot;
				$defaultvalues["Jaeger"] = $PrevJaeger;
				$NewLines= true;	
				//get link to map
				foreach ($data as $dl) {
					if ($dl['Datum']==$dataline['Datum']) {
						$DayFieldList[] =$dl['Feld'];
						$c[] =$dl['Feld'];
						if ($dl['Pilot']==$dataline['Pilot'] ) {
							$PilotDayFieldList[] =$dl['Feld'];
						}
						if ($dl['Jaeger']==$dataline['Jaeger'] ) {
							$JaegerDayFieldList[] =$dl['Feld'];
						}
					}
				}
				$PilotDayFieldList = implode('+', $PilotDayFieldList);
				//pre_R($JaegerDayFieldList);
				$JaegerDayFieldList = implode('+', $JaegerDayFieldList);
				$DayFieldList = implode('+', $DayFieldList);
				//echo "JD:" .$JaegerDayFieldList;
				//echo "<tr><td colspan='10'>";
				echo $ScreenTableSetup["<tr>"] . chr(10);
				echo $ScreenTableSetup["<td>"] . chr(10);
				if ($Mode=="J") {
					//Jäger
					echo "<b><i>" . $dataline['Datum'] . "/ " . $dataline['Jaeger'] . "</i></b>". chr(10);
				} else	{
					//Pilot
					echo "<b><i>" . $dataline['Datum'] . "/ " . $dataline['Pilot'] . "</i></b>". chr(10);
				}
				echo $ScreenTableSetup["</td>"] . chr(10);
				echo $ScreenTableSetup["<td line>"] . chr(10);
				if ($Mode=="J") {
					//Jäger
					// GeoMap Link
					echo "<i>Tagesflugplan </i>" . chr(10);
					echo'<a href="rkrk_pdf_fluege.php?FromDate='.$dataline['Datum'].'&ToDate='.$dataline['Datum'].'&Jaeger='. $dataline['Jaeger'] . '" target="_pdfplan"><img src="' . $IconLoc . '/pdf_jaeger.png" height="' . $ButtonHeightSmall .'" title="Tagesflugplan des Jägers auf PDF" ></a>' .chr(10);
					echo'<a href="' .$GeoMapURL1 . $JaegerDayFieldList . $GeoMapURL2 . '" target="_geomap"><img src="' . $IconLoc . '/geomapbutton_jaeger.png" height="' . $ButtonHeightSmall .'" title="Tagesflugplan des Jägers auf Geomap"></a>' .chr(10);
					echo " <i> des Jägers </i>". chr(10);
				} else	{
					//Pilot
					// GeoMap Link
					echo "<i>Tagesflugplan </i>" . chr(10);
					echo'<a href="rkrk_pdf_fluege.php?FromDate='.$dataline['Datum'].'&ToDate='.$dataline['Datum'].'&Pilot='. $dataline['Pilot'] . '" target="_pdfplan"><img src="' . $IconLoc . '/pdf_pilot.png" height="' . $ButtonHeightSmall .'" title="Tagesflugplan des Piloten auf PDF" ></a>' .chr(10);
					echo'<a href="' .$GeoMapURL1 . $PilotDayFieldList . $GeoMapURL2 . '" target="_geomap"><img src="' . $IconLoc . '/geomapbutton_pilot.png" height="' . $ButtonHeightSmall .'" title="Tagesflugplan des Piloten auf Geomap"></a>' .chr(10);
					echo " <i> des Piloten </i>". chr(10);
					echo'<a href="rkrk_pdf_fluege.php?FromDate='.$dataline['Datum'].'&ToDate='.$dataline['Datum'].'" target="_pdfplan"><img src="' . $IconLoc . '/pdf_tag.png" height="' . $ButtonHeightSmall .'" title="Tagesflugplan des Teams auf PDF"></a>' .chr(10);
					echo'<a href="' .$GeoMapURL1 . $DayFieldList . $GeoMapURL2 . '" target="_geomap"><img src="' . $IconLoc . '/geomapbutton_day.png" height="' . $ButtonHeightSmall .'" title="Tagesflugplan des Teams auf Geomap"></a>' .chr(10);
					echo " <i> des Teams </i>". chr(10);
				}
				echo " ". chr(10);
				echo $ScreenTableSetup["</td>"] . chr(10);
				echo $ScreenTableSetup["</tr>"] . chr(10);
			}
*/			// Existing Data
			CreateOutputLine($dataline, array(), $i, $i);
			$PrevPilot =$dataline['Pilot'] ?? '';
			$PrevJaeger =$dataline['Jaeger'] ?? '';
			$PrevDate =$dataline['Datum'] ?? '';
			$ActDate =$PrevDate;

		}
		$i++;
	}

	echo $ScreenTableSetup["</table>"] .chr(10);
	echo '</span> ';

	echo '<input type="hidden" name="NumEntries" id="NumEntries" value="'. $i. '" />'. chr(10);

	if ($TableType=="Table") {
		echo $ScreenTableSetup["<div>"].chr(10);
		//PageControl($ActPage, $MaxPages, $AnzLinesPPage, $FontSize, false, "Einlesen", false);
		echo $ScreenTableSetup["</div>"].chr(10);
	}
	//echo "<div class='container content line-left padding-8' style='max-width:100%' id='table'>".chr(10);
	echo ($ScreenTableSetup["<table line>"] ?? '');
	//echo  "<div class='container content center padding-16'>";
	//echo $ScreenTableSetup["<table>"] .chr(10);
	
	// Pagecontrol disabled
	//if ($TableType=="Flex") {
	//	echo $ScreenTableSetup["<tr>"] .chr(10);
	//	echo $ScreenTableSetup["<td line>"] .chr(10);
	//	PageControl($ActPage, $MaxPages, $AnzLinesPPage, $FontSize, false, "Einlesen", false);
	//	echo $ScreenTableSetup["</td>"] .chr(10);
	//	echo $ScreenTableSetup["</tr>"] .chr(10);
	//}
	//echo $ScreenTableSetup["</table>"] .chr(10);



	echo '</form>'. chr(10);
    echo '<br>'. chr(10);
	//echo $ListType;
	definitions($MenuOn);

 if ($Layer)
	echo "<div  id='imgbox' style='mybox,top:10px;right:60px;z-index:1;'></div>" . chr(10);
else 
	echo "<div  id='imgbox'></div>" . chr(10);



?>


<script>

function FilterListe(Feldname,List) {
    var input, filter, ul, li, a, i, txtValue;
    input = document.getElementById(Feldname);
    filter = input.value.toUpperCase();
    ul = document.getElementById(List);
    li = ul.getElementsByTagName("li");
    for (i = 0; i < li.length; i++) {
        a = li[i].getElementsByTagName("a")[0];
        txtValue = a.textContent || a.innerText;
        if (txtValue.toUpperCase().indexOf(filter) > -1) {
            li[i].style.display = "";
        } else {
            li[i].style.display = "none";
        }
    }
}


function FilterTable(Feldname, tablename) {
  var input, filter, table, tr, td, i, txtValue;
  input = document.getElementById(Feldname);
  filter = input.value.toUpperCase();
  table = document.getElementById(tablename);
  tr = table.getElementsByTagName("tr");
  for (i = 0; i < tr.length; i++) {
    td = tr[i].getElementsByTagName("td")[0];
    if (td) {
      txtValue = td.textContent || td.innerText;
      if (txtValue.toUpperCase().indexOf(filter) > -1) {
        tr[i].style.display = "";
      } else {
        tr[i].style.display = "none";
      }
    }       
  }
}


function getElementLeft(elm) 
{
    var x = 0;

    //set x to elm’s offsetLeft
    x = elm.offsetLeft;

    //set elm to its offsetParent
    elm = elm.offsetParent;

    //use while loop to check if elm is null
    // if not then add current elm’s offsetLeft to x
    //offsetTop to y and set elm to its offsetParent

    while(elm != null)
    {
        x = parseInt(x) + parseInt(elm.offsetLeft);
        elm = elm.offsetParent;
    }
    return x;
}

function getElementTop(elm) 
{
    var y = 0;

    //set x to elm’s offsetLeft
    y = elm.offsetTop;

    //set elm to its offsetParent
    elm = elm.offsetParent;

    //use while loop to check if elm is null
    // if not then add current elm’s offsetLeft to x
    //offsetTop to y and set elm to its offsetParent

    while(elm != null)
    {
        y = parseInt(y) + parseInt(elm.offsetTop);
        elm = elm.offsetParent;
    }

    return y;
}

function Large(obj)
{
    var imgbox=document.getElementById("imgbox");
    imgbox.style.visibility='visible';
    var img = document.createElement("img");
    img.src=obj.src;
    img.style.width='600px';
    img.style.height='600px';
    
    if(img.addEventListener){
        img.addEventListener('mouseout',Out,false);
    } else {
        img.attachEvent('onmouseout',Out);
    }             
    imgbox.innerHTML='';
    imgbox.appendChild(img);
    imgbox.style.left=(getElementLeft(obj)-400) +'px';
    imgbox.style.top=(getElementTop(obj)-350) + 'px';
}

function Out()
{
    document.getElementById("imgbox").style.visibility='hidden';
}


</script>

</body>
</HTML>
