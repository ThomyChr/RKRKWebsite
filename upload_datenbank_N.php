<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" >
<?php

	session_start();
	include_once('scripts/rkrk_gen.php');
	include_once('scripts/rkrk_dbaccess.php');
	include_once('scripts/rkrk_felddaten.php');

	echo "<H1>DB Access Synology</H1>";


	// ===========================================
	// Definitions
	// ===========================================

	// ===========================================
	// Functions
	// ===========================================


	// ===========================================
	// Test
	// ===========================================

	$SQLSummary = Array (
		"TestDB" => Array(
				"löschen" => 
									"DROP TABLE IF EXISTS animals",
				"erstellen" => 
									"CREATE TABLE IF NOT EXISTS animals (
										id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
										name VARCHAR(50) NOT NULL,
										species VARCHAR(50) NOT NULL,
										age INT(3) NOT NULL
									)",
				"einfügen" => 
									"INSERT INTO animals (name, species, age) VALUES
										('Bella', 'Dog', 3),
										('Milo', 'Cat', 2),
										('Charlie', 'Rabbit', 1),
										('Luna', 'Dog', 4)",
		),
		"Flugfelder" => Array(
				"löschen" => 
									"DROP TABLE IF EXISTS `Flugfelder`",
				"erstellen" => 
									"CREATE TABLE `Flugfelder` (
									  `ID` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
									  `FeldID` varchar(20) NOT NULL UNIQUE KEY,
									  `JG` varchar(10) DEFAULT NULL,
									  `Feldname` varchar(150) DEFAULT NULL,
									  `Flurname` varchar(150) DEFAULT NULL,
									  `GrundstueckID` varchar(100) DEFAULT NULL,
									  `Flugzeit` float DEFAULT NULL,
									  `Flaeche` float DEFAULT NULL,
									  `Wegpunkte` int(11) DEFAULT NULL,
									  `Distanz` float DEFAULT NULL,
									  `Gemeinde` varchar(50) DEFAULT NULL,
									  `Betrieb` varchar(50) DEFAULT NULL,
									  `Freigabe` varchar(50) DEFAULT NULL,
									  `Warnung` varchar(50) DEFAULT NULL,
									  `Jaeger` varchar(50) DEFAULT NULL,
									  `Bemerkung` longtext DEFAULT NULL,
									  `Flug` varchar(150) DEFAULT NULL,
									  `Funde` varchar(150) DEFAULT NULL,
									  `StdFlughohe` int(11) DEFAULT NULL,
									  `SuchfeldBild` tinytext DEFAULT NULL,
									  `FlugplanBild` tinytext DEFAULT NULL,
									  `JasonFile` tinytext DEFAULT NULL,
									  `KMLFile` tinytext DEFAULT NULL,
									  `KMZFile` tinytext DEFAULT NULL,
									  `LichtiFile` tinytext DEFAULT NULL,
									  `Bauer` longtext DEFAULT NULL,
									  `Notiz` tinytext DEFAULT NULL,
									  `Startplatz` tinytext DEFAULT NULL,
									  `Zentrum` tinytext DEFAULT NULL,
									  `Flaechendaten` longtext DEFAULT NULL,
									  `Flugweg` longtext DEFAULT NULL,
									  `Erstellt` date DEFAULT NULL,
									  `Aenderung` date DEFAULT NULL,
									  `Update` date DEFAULT NULL,
									  `Export` enum('Y','N') DEFAULT 'Y',
									  `Select` enum('Y','N') DEFAULT 'Y'
									) ENGINE=InnoDB DEFAULT CHARSET=utf8;
									",
		),
		"KMLObjekte" => Array(
				"löschen" => 
									"DROP TABLE IF EXISTS `KMLObjekte`",
				"erstellen" => 
									"CREATE TABLE `KMLObjekte` (
									  `ID` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
									  `Name` varchar(150) DEFAULT NULL,
									  `description` longtext DEFAULT NULL,
									  `styleUrl` varchar(150)DEFAULT NULL,
									  `coordinates` longtext DEFAULT NULL,
									  `Type` varchar(150)DEFAULT NULL,
									  `Region` tinytext DEFAULT NULL,
									  `Kat` varchar(150)DEFAULT NULL,
									  `Bemkerkung` longtext DEFAULT NULL,
									  `Erstellt` date DEFAULT NULL,
									  `Enable` enum('Y','N') DEFAULT 'Y'
									) ENGINE=InnoDB DEFAULT CHARSET=utf8;
									",
/*
				"einfügen" => 
									"INSERT INTO `KMLObjekte`(`ID`, `Name`, `description`, `styleUrl`, `coordinates`, `Type`, `Region`, `Wegpunkte`, `Kat`, `Bemkerkung`, `Erstellt`, `Enable`) VALUES ('[value-1]','[value-2]','[value-3]','[value-4]','[value-5]','[value-6]','[value-7]','[value-8]','[value-9]','[value-10]','[value-11]','[value-12]')",
*/
		),

//FlugID;Datum;Zeit;Pilot;Feld;Kontrollflug;Geflogen;Fund;UAV#;Felder;Jaeger;Kontaktnummer;Landwirt;Bemerkung;Flugzeit;AnzFelder;#Funde;Einsatztag;GrundstückID;Gde;JG;Warnung;Reg;Flaeche;Status
		"Fluege" => Array(
				"löschen" => 
									"DROP TABLE IF EXISTS `Fluege`",
				"erstellen" => 
									"CREATE TABLE `Fluege` (
									  `ID` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
									  `FlugID` varchar(50) NOT NULL UNIQUE KEY,
									  `Datum` date DEFAULT NULL,
									  `Zeit` TIME DEFAULT NULL,
									  `Feld` varchar(20) DEFAULT NULL,
									  `Pilot` varchar(50) DEFAULT NULL,
									  `Kontrollflug` enum('Ja','Nein','') DEFAULT '',
									  `Geflogen` enum('Ja','Nein','') DEFAULT '',
									  `Fund` int(11) DEFAULT 0,
									  `UAV#` varchar(50) DEFAULT NULL,
									  `Felder` varchar(150) DEFAULT NULL,
									  `Jaeger` varchar(50) DEFAULT NULL,
									  `Kontaktnummer` varchar(150) DEFAULT NULL,
									  `Landwirt` varchar(150) DEFAULT NULL,
									  `Bemerkung` longtext DEFAULT NULL,
									  `Flugzeit` float DEFAULT NULL,
									  `AnzFelder` int(11) DEFAULT 0,
									  `#Funde` int(11) DEFAULT 0,
									  `Einsatztag` date DEFAULT NULL,
									  `GrundstückID` varchar(100) DEFAULT NULL,
									  `Gde` varchar(50) DEFAULT NULL,
									  `JG` varchar(10) DEFAULT NULL,
									  `Warnung` varchar(50) DEFAULT NULL,
									  `Reg` varchar(20) DEFAULT NULL,
									  `Flaeche` float DEFAULT NULL,
									  `Status` enum('locked','open','') DEFAULT '',
									  `Enable` enum('Y','N') DEFAULT 'Y'
									) ENGINE=InnoDB DEFAULT CHARSET=utf8;
									",
/*
				"einfügen" => 
									"INSERT INTO `Fluege`(`ID`, `Name`, `description`, `styleUrl`, `coordinates`, `Type`, `Region`, `Wegpunkte`, `Kat`, `Bemkerkung`, `Erstellt`, `Enable`) VALUES ('[value-1]','[value-2]','[value-3]','[value-4]','[value-5]','[value-6]','[value-7]','[value-8]','[value-9]','[value-10]','[value-11]','[value-12]')",
*/
		),

//Datum;Wetter;Temp;Plan_TC;Plan_CH;Plan_VP;Plan_MB;Plan_LF;Plan_MF;Plan_AC;Plan_NC
		"Plan" => Array(
				"löschen" => 
									"DROP TABLE IF EXISTS `Plan`",
				"erstellen" => 
									"CREATE TABLE `Plan` (
									  `Datum` date NOT NULL PRIMARY KEY,
									  `Wetter` varchar(20) DEFAULT NULL,
									  `Temp` int(11) DEFAULT 0,
									  `Plan_TC` varchar(25) DEFAULT NULL,
									  `Plan_CH` varchar(25) DEFAULT NULL,
									  `Plan_VP` varchar(25) DEFAULT NULL,
									  `Plan_MB` varchar(25) DEFAULT NULL,
									  `Plan_LF` varchar(25) DEFAULT NULL,
									  `Plan_MF` varchar(25) DEFAULT NULL,
									  `Plan_AC` varchar(25) DEFAULT NULL,
									  `Plan_NC` varchar(25) DEFAULT NULL
									) ENGINE=InnoDB DEFAULT CHARSET=utf8;
									",
/*
				"einfügen" => 
									"INSERT INTO `Plan`(`Datum`, `Wetter`, `Temp`, `Plan_TC`, `Plan_CH`, `Plan_VP`, `Plan_MB`, `Plan_LF`, `Plan_MF`, `Plan_AC`, `Plan_NC`) VALUES ('[value-1]','[value-2]','[value-3]','[value-4]','[value-5]','[value-6]','[value-7]','[value-8]','[value-9]','[value-10]','[value-11]')",
*/
		),

//ID;Initialen;Benutzername;Name;Vorname;Passwort;Passwort_hashed;Email;Webaccess;AccessRights;Regionen;Bemerkung;LetzterBesuch;Preferenz01;Preferenz02;Preferenz03;Preferenz04;RegisterDate
		"Benutzer" => Array(
				"löschen" => 
									"DROP TABLE IF EXISTS `Benutzer`",
				"erstellen" => 
									"CREATE TABLE `Benutzer` (
									  `ID` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
									  `BenutzerID` varchar(20),
									  `Initialen` varchar(20) NOT NULL UNIQUE KEY,
									  `Benutzername` varchar(50) DEFAULT NULL,
									  `Name` varchar(50) DEFAULT NULL,
									  `Vorname` varchar(50) DEFAULT NULL,
									  `Passwort` varchar(100) DEFAULT NULL,
									  `Passwort_hashed` varchar(100) DEFAULT NULL,
									  `Webaccess` varchar(50) DEFAULT NULL,
									  `AccessRights` varchar(100) DEFAULT NULL,
									  `Bemerkung` longtext DEFAULT NULL,
									  `LetzterBesuch` date DEFAULT NULL,
									  `Preferenz01` varchar(100) DEFAULT NULL,
									  `Preferenz02` varchar(100) DEFAULT NULL,
									  `Preferenz03` varchar(100) DEFAULT NULL,
									  `Preferenz04` varchar(100) DEFAULT NULL,
									  `RegisterDate` date DEFAULT NULL
									) ENGINE=InnoDB DEFAULT CHARSET=utf8;
									",
/*
				"einfügen" => 
									"INSERT INTO `Benutzer`(`ID`, `BenutzerID`, `Initialen`, `Benutzername`, `Name`, `Vorname`, `Passwort`, `Passwort_hashed`, `Webaccess`, `AccessRights`, `Bemerkung`, `LetzterBesuch`, `Preferenz01`, `Preferenz02`, `Preferenz03`, `Preferenz04`, `RegisterDate`) VALUES ('[value-1]','[value-2]','[value-3]','[value-4]','[value-5]','[value-6]','[value-7]','[value-8]','[value-9]','[value-10]','[value-11]','[value-12]','[value-13]','[value-14]','[value-15]','[value-16]','[value-17]')",
*/
		),

//ID;Initialen;JG;Jagdgebiet;Name;Kontaktnummer;P Telefon;Mail;Adresse;RKRS;Typ;Kontakt;Platform;Einsatz;GeoMapLoc;Bemerkung
		"Kontakte" => Array(
				"löschen" => 
									"DROP TABLE IF EXISTS `Kontakte`",
				"erstellen" => 
									"CREATE TABLE `Kontakte` (
									  `ID` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
									  `KontaktID` varchar(20),
									  `Initialen` varchar(20) NOT NULL UNIQUE KEY,
									  `JG` varchar(10) DEFAULT NULL,
									  `Jagdgebiet` varchar(50) DEFAULT NULL,
									  `Name` varchar(100) DEFAULT NULL,
									  `Kontaktnummer` varchar(50) DEFAULT NULL,
									  `P Telefon` varchar(50) DEFAULT NULL,
									  `Mail` varchar(150) DEFAULT NULL,
									  `Adresse` text DEFAULT NULL,
									  `RKRS` enum('Ja','Nein','-') DEFAULT '-',
									  `Typ` enum('Pilot','Pilot RKRK','Pilot Zug','Pilot Neu','Pilot inactive','Jäger','Jäger Amt','Jäger Oberfreiamt','Jäger Zug','-') DEFAULT '-',
									  `Kontakt` enum('Ja','Nein') DEFAULT 'Ja',
									  `Platform` text DEFAULT NULL,
									  `Einsatz` text DEFAULT NULL,
									  `GeoMapLoc` varchar(250) DEFAULT NULL,
									  `Bemerkung` longtext DEFAULT NULL
									) ENGINE=InnoDB DEFAULT CHARSET=utf8;
									",
/*
				"einfügen" => 
									"INSERT INTO `Kontakte`(`ID`, `KontaktID`, `Initialen`, `JG`, `Jagdgebiet`, `Name`, `Kontaktnummer`, `P Telefon`, `Mail`, `Adresse`, `RKRS`, `Typ`, `Kontakt`, `Platform`, `Einsatz`, `GeoMapLoc`, `Bemerkung`) VALUES ('[value-1]','[value-2]','[value-3]','[value-4]','[value-5]','[value-6]','[value-7]','[value-8]','[value-9]','[value-10]','[value-11]','[value-12]','[value-13]','[value-14]','[value-15]','[value-16]','[value-17]')",
*/
		),
		
//ID;Initialen;Betrieb;Jagdgebiet;Name;Kontaktnummer;P Telefon;Mail;Adresse;GeoMapLoc;Typ;Bemerkung;Felder
		"Bauern" => Array(
				"löschen" => 
									"DROP TABLE IF EXISTS `Bauern`",
				"erstellen" => 
									"CREATE TABLE `Bauern` (
									  `ID` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
									  `BauerID` varchar(20),
									  `Initialen` varchar(20) NOT NULL UNIQUE KEY,
									  `Betrieb` varchar(20) DEFAULT NULL,
									  `Jagdgebiet` varchar(50) DEFAULT NULL,
									  `Name` varchar(100) DEFAULT NULL,
									  `Kontaktnummer` varchar(50) DEFAULT NULL,
									  `P Telefon` varchar(50) DEFAULT NULL,
									  `Mail` varchar(150) DEFAULT NULL,
									  `Adresse` text DEFAULT NULL,
									  `GeoMapLoc` varchar(250) DEFAULT NULL,
									  `Typ` enum('Bauer','-') DEFAULT '-',
									  `Bemerkung` longtext DEFAULT NULL,
									  `Felder` longtext DEFAULT NULL
									) ENGINE=InnoDB DEFAULT CHARSET=utf8;
									",
/*
				"einfügen" => 
									"INSERT INTO `Bauern`(`ID`, `BauerID`, `Initialen`, `Betrieb`, `Jagdgebiet`, `Name`, `Kontaktnummer`, `P Telefon`, `Mail`, `Adresse`, `GeoMapLoc`, `Typ`, `Bemerkung`, `Felder`) VALUES ('[value-1]','[value-2]','[value-3]','[value-4]','[value-5]','[value-6]','[value-7]','[value-8]','[value-9]','[value-10]','[value-11]','[value-12]','[value-13]','[value-14]')",
*/
		),
		
//ID;Initialen;Betrieb;Jagdgebiet;Name;Kontaktnummer;P Telefon;Mail;Adresse;Typ;Bemerkung;Felder
		"Swiss3DHeight" => Array(
				"datenbank" => 
									"CREATE DATABASE `CH3DHeight`;",
				"löschen" => 
									"DROP TABLE IF EXISTS ",
				"erstellen_part1" => 
									"CREATE TABLE ",
				"erstellen_part2" => 
									"(
									  `X` int(29) NOT NULL,
									  `Y` int(29) NOT NULL,
									  `Z` float,
									  /*`file` varchar(100) DEFAULT NULL,*/
									  PRIMARY KEY (`X`, `Y`)
									) ENGINE=InnoDB DEFAULT CHARSET=utf8;
									",
/*
				"einfügen" => 
									"INSERT INTO `CH3DHeight`(`X`, `Y`, `Z`, `file`) VALUES ('[value-1]','[value-2]','[value-3]','[value-4]')",
*/
		),
	

		"KMLStyles" => Array(
				"löschen" => 
									"DROP TABLE IF EXISTS `KMLStyles`",
				"erstellen" => 
									"CREATE TABLE `KMLStyles` (
									  `ID` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
									  `Name` varchar(150) DEFAULT NULL,
									  `IconStyle` tinytext DEFAULT NULL,
									  `LabelStyle` tinytext DEFAULT NULL,
									  `LineStyle` tinytext DEFAULT NULL,
									  `PolyStyle` tinytext DEFAULT NULL,
									  `Enable` enum('Y','N') DEFAULT 'Y'
									) ENGINE=InnoDB DEFAULT CHARSET=utf8;
									",
/*
				"einfügen" => 
									"INSERT INTO `KMLStyles`(`ID`, `Name`, `Style`, `Enable`) VALUES ('[value-1]','[value-2]','[value-3]','[value-4]')",
*/
		),

	);

//ExecuteQueries ($SQLSummary["Flugfelder"]);
//ExecuteQueries ($SQLSummary["KMLObjekte"]);


// ==================
// File XML einlesen
// ==================

   function ReadLocalXMLFile($filename, $debugoutput)  {   


	//echo $filename;
		if(file_exists($filename)) {
		//  $xml = simplexml_load_file($filename);     //Lädt die XML-Datei in ein Objekt
		$xml = simplexml_load_string(htmlentities_decode(file_get_contents($filename)));
			if($xml && $debugoutput) {    //Prüfen, ob Datei Fehler enthielt
				//Print Array Content
				$objJsonDocument = json_encode($xml);
				$arrOutput = json_decode($objJsonDocument, TRUE);
 
				echo "<pre>";
				print_r($arrOutput);
				echo "</pre>";
			}	
		} else {
			echo '        <p>Die Datei names '. $filename .' konnte nicht geöffnet werden</p>';
		}
	//Return mb_convert_encoding( $xml,'UTF-16LE', 'UTF-8');
	Return $xml;
	}

//////////////////////////////////////////////////////////////////
// Löschen alte Datenbank strukturen und füge KML Objekte 
// von rkrk_poidata.xml zur Datenbank
//////////////////////////////////////////////////////////////////

function Add_KMLObjekte() {
	global $xmlMissionLocation, $xml_poifile, $SQLSummary;
	$POIFile = ReadLocalXMLFile($xmlMissionLocation.$xml_poifile, false);
	
	//pre_R($POIFile);
	$SQLCommands = Array();
	$SQLCommands["löschen"] = $SQLSummary["KMLObjekte"]["löschen"];
	$SQLCommands["erstellen"] = $SQLSummary["KMLObjekte"]["erstellen"];
	ExecuteQueries ($SQLCommands);
	//ExecuteQueries($SQLQuery);
	$SQLQueryHeader = "INSERT INTO `KMLObjekte`(`ID`, `Name`, `description`, `styleUrl`, `coordinates`, `Type`, `Region`, `Kat`, `Bemkerkung`, `Erstellt`, `Enable`) VALUES";
	$i=1;
	foreach ($POIFile as $key => $PoiPoint) {
		if (($PoiPoint["id"]>0) and (($i<3) or true)) {
			//pre_R($PoiPoint);	
			//echo $PoiPoint->description;
			echo $i . " KML Objekt " . $PoiPoint->name . " hinzugefügt.<br>";
			
			$SQLCommand = $SQLQueryHeader . " (".$i.",'".$PoiPoint->name."','".$PoiPoint->description."','".$PoiPoint->styleUrl."','".trim($PoiPoint->coordinates)."','".$PoiPoint->Type."','".$PoiPoint->Region."','".$PoiPoint->Kat."','','".date("Y-m-d")."','Y')";
			//echo $i . " " . $SQLCommand . "<br>";
			ExecuteQueries ($SQLCommand);
			$i++;
		}
	}
}	

// exectute 
//Add_KMLObjekte();

//////////////////////////////////////////////////////////////////
// Löschen alte Datenbank strukturen und füge Flugfelder hinzu
// von RKRKFelder.xml zur Datenbank
//////////////////////////////////////////////////////////////////

function Add_Felder() {

	global $xmlMissionLocation, $RKRKFelderfileXML, $SQLSummary;
	$Feldkatalog= GetFeldListArrayFromXML($xmlMissionLocation.$RKRKFelderfileXML);
	
	//pre_R($Feldkatalog);
	$SQLCommands = Array();
	$SQLCommands["löschen"] = $SQLSummary["Flugfelder"]["löschen"];
	$SQLCommands["erstellen"] = $SQLSummary["Flugfelder"]["erstellen"];
	ExecuteQueries ($SQLCommands);
	//ExecuteQueries($SQLQuery);
	$SQLQueryHeader = "INSERT INTO `Flugfelder`(`ID`, `FeldID`, `JG`, `Feldname`, `Flurname`, `GrundstueckID`, `Flugzeit`, `Flaeche`, `Wegpunkte`, `Distanz`, `Gemeinde`, `Betrieb`, `Freigabe`, `Warnung`, `Jaeger`, `Bemerkung`, `Flug`, `Funde`, `StdFlughohe`, `SuchfeldBild`, `FlugplanBild`, `JasonFile`, `KMLFile`, `KMZFile`, `LichtiFile`, `Bauer`, `Notiz`, `Startplatz`, `Zentrum`, `Flaechendaten`, `Flugweg`, `Erstellt`, `Aenderung`, `Update`, `Export`, `Select`) VALUES ";
	//('[value-1]','[value-2]','[value-3]','[value-4]','[value-5]','[value-6]','[value-7]','[value-8]','[value-9]','[value-10]','[value-11]','[value-12//]','[value-13]','[value-14]','[value-15]','[value-16]','[value-17]','[value-18]','[value-19]','[value-20]','[value-21]','[value-22]','[value-23]'//,'[value-24]','[value-25]','[value-26]','[value-27]','[value-28]','[value-29]','[value-30]','[value-31]','[value-32]','[value-33]','[value-34]','[value-35]','[value-36]')
	$i=1;
	foreach ($Feldkatalog as $JG => $JGDaten) {
		foreach ($JGDaten as $FeldID => $FeldDaten) {
			if (($i<3) or true) {
				//pre_R($FeldDaten);	
				//echo $PoiPoint->description;
				echo $i . " Feld " . $FeldDaten["FeldID"]  . " hinzugefügt.<br>";

				$SQLCommand = $SQLQueryHeader . " (" .$i. ",";
				$SQLCommand .= "'" . $FeldDaten["FeldID"] . "'" . ", ";
				$SQLCommand .= "'" . $FeldDaten["JG"] . "'" . ", ";
				$SQLCommand .= "'" . $FeldDaten["Feldname"] . "'" . ", ";
				$SQLCommand .= "'" . $FeldDaten["Flurname"] . "'" . ", ";
				$SQLCommand .= "'" . $FeldDaten["GrundstueckID"] . "'" . ", ";
				$SQLCommand .= $FeldDaten["Flugzeit"]. ", ";
				$SQLCommand .= $FeldDaten["Flaeche"] . ", ";
				$SQLCommand .= $FeldDaten["Wegpunkte"] . ", ";
				$SQLCommand .= $FeldDaten["Distanz"] . ", ";
				$SQLCommand .= "'" . $FeldDaten["Gemeinde"] . "'" . ", ";
				$SQLCommand .= "'" . $FeldDaten["Betrieb"] . "'" . ", ";
				$SQLCommand .= "'" . $FeldDaten["Freigabe"] . "'" . ", ";
				$SQLCommand .= "'" . $FeldDaten["Warnung"] . "'" . ", ";
				$SQLCommand .= "'" . $FeldDaten["Jaeger"] . "'" . ", ";
				$SQLCommand .= "'" . trim($FeldDaten["Bemerkung"]) . "'" . ", ";
				$SQLCommand .= "'" . $FeldDaten["Flug"] . "'" . ", ";
				$SQLCommand .= "'" . $FeldDaten["Funde"] . "'" . ", ";
				$SQLCommand .= $FeldDaten["StdFlughohe"] . ", ";
				$SQLCommand .= "'" . $FeldDaten["SuchfeldBild"] . "'" . ", ";
				$SQLCommand .= "'" . $FeldDaten["FlugplanBild"] . "'" . ", ";
				$SQLCommand .= "'" . $FeldDaten["JasonFile"] . "'" . ", ";
				$SQLCommand .= "'" . $FeldDaten["KMLFile"] . "'" . ", ";
				$SQLCommand .= "'" . $FeldDaten["KMZFile"] . "'" . ", ";
				$SQLCommand .= "'" . $FeldDaten["LichtiFile"] . "'" . ", ";
				$SQLCommand .= "'" . $FeldDaten["Bauer"] . "'" . ", ";
				$SQLCommand .= "'" . $FeldDaten["Notiz"] . "'" . ", ";
				$SQLCommand .= "'" . trim($FeldDaten["Grundstueckdaten"]["Startplatz"]) . "'" . ", ";
				$SQLCommand .= "'" . trim($FeldDaten["Grundstueckdaten"]["Zentrum"]) . "'" . ", ";
				$SQLCommand .= "'" . trim($FeldDaten["Flaechendaten"]) . "'" . ", ";
				$SQLCommand .= "'" . trim($FeldDaten["Flugweg"]) . "'" . ", ";
				$SQLCommand .= "'" . date_format(date_create($FeldDaten["Erstellt"]),"Y-m-d") . "'" . ", ";
				$SQLCommand .= "'" . date_format(date_create($FeldDaten["Aenderung"]),"Y-m-d") . "'" . ", ";
				$SQLCommand .= "'" . date_format(date_create($FeldDaten["Update"]),"Y-m-d") . "'" . ", ";
				$SQLCommand .= "'" . $FeldDaten["Export"] . "'" . ", ";
				$SQLCommand .= "'" . $FeldDaten["Select"] . "'";
				$SQLCommand .= ")";

				//if ($FeldDaten["FeldID"]=="25-010") {
				//	echo $i . " " . $SQLCommand . "<br>";
					ExecuteQueries ($SQLCommand);
				//}
				$i++;
			}
		}
	}
}	
// exectute 
//Add_Felder();

//////////////////////////////////////////////////////////////////
// Löschen alte Datenbank strukturen und füge Flüge hinzu
// von 20YY_RKRKFluege.csv zur Datenbank
//////////////////////////////////////////////////////////////////

function Add_Fluege() {

	global $csvDocLocation, $RKRKFlightsCSV, $SQLSummary;
	$JahresListe = Array(2021,2022,2023,2024,2025);
	//$JahresListe = Array(2021);
	$i=1;
	$SQLCommands = Array();
	$SQLCommands["löschen"] = $SQLSummary["Fluege"]["löschen"];
	$SQLCommands["erstellen"] = $SQLSummary["Fluege"]["erstellen"];
	ExecuteQueries ($SQLCommands);

	foreach ($JahresListe as $Jahr) {
		echo "Jahr: " . $Jahr . "<br>";
		$csv_file= $Jahr . $RKRKFlightsCSV;
		$csv_deliminator = ";";

		//Read CSV file
		$Fluglisten = csv_to_array($csvDocLocation.$csv_file, $csv_deliminator );
		//pre_R($Fluglisten);
		
		//ExecuteQueries($SQLQuery);
		$SQLQueryHeader = "INSERT INTO `Fluege`(`ID`, `FlugID`, `Datum`, `Zeit`, `Feld`, `Pilot`, `Kontrollflug`, `Geflogen`, `Fund`, `UAV#`, `Felder`, `Jaeger`, `Kontaktnummer`, `Landwirt`, `Bemerkung`, `Flugzeit`, `AnzFelder`, `#Funde`, `Einsatztag`, `GrundstückID`, `Gde`, `JG`, `Warnung`, `Reg`, `Flaeche`, `Status`, `Enable`) VALUES  ";

		//('[value-1]','[value-2]','[value-3]','[value-4]','[value-5]','[value-6]','[value-7]','[value-8]','[value-9]','[value-10]','[value-11]','[value-12]','[value-13]','[value-14]','[value-15]','[value-16]','[value-17]','[value-18]','[value-19]','[value-20]','[value-21]','[value-22]','[value-23]','[value-24]','[value-25]','[value-26]','[value-27]')

		foreach ($Fluglisten as $key => $Flug) {
			if (($i<5) or true) {
				//pre_R($Flug);	
				//echo $PoiPoint->description;
				$SQLCommand = $SQLQueryHeader . " (" .$i. ",";
				$SQLCommand .= "'" . $Jahr.$Flug["FlugID"] . "'" . ", ";
				$SQLCommand .= "'" . date_format(date_create($Flug["Datum"]),"Y-m-d") . "'" . ", ";
				if ($Flug["Zeit"]=="") 
					$SQLCommand .= "'00:00', ";
				else
					$SQLCommand .= "'" . $Flug["Zeit"] . "'" . ", ";
				$SQLCommand .= "'" . $Flug["Feld"] . "'" . ", ";
				$SQLCommand .= "'" . $Flug["Pilot"] . "'" . ", ";
				$SQLCommand .= "'" . $Flug["Kontrollflug"] . "'" . ", ";
				$SQLCommand .= "'" . $Flug["Geflogen"] . "'" . ", ";
				if ($Flug["Fund"]=="") 
					$SQLCommand .= "0, ";
				else
					$SQLCommand .= $Flug["Fund"] . ", ";
				$SQLCommand .= "'" . $Flug["UAV#"] . "'" . ", ";
				$SQLCommand .= "'" . $Flug["Felder"] . "'" . ", ";
				$SQLCommand .= "'" . $Flug["Jaeger"] . "'" . ", ";
				$SQLCommand .= "'" . $Flug["Kontaktnummer"] . "'" . ", ";
				$SQLCommand .= "'" . $Flug["Landwirt"] . "'" . ", ";
				$SQLCommand .= "'" . $Flug["Bemerkung"] . "'" . ", ";
				if ($Flug["Flugzeit"]=="") 
					$SQLCommand .= "0, ";
				else
					$SQLCommand .= $Flug["Flugzeit"] . ", ";
				if ($Flug["AnzFelder"]=="") 
					$SQLCommand .= "0, ";
				else
					$SQLCommand .= $Flug["AnzFelder"] . ", ";
				if ($Flug["#Funde"]=="") 
					$SQLCommand .= "0, ";
				else
					$SQLCommand .= $Flug["#Funde"] . ", ";
				$SQLCommand .= "'" . date_format(date_create($Flug["Datum"]),"Y-m-d") . "'" . ", ";
				$SQLCommand .= "'" . $Flug["GrundstückID"] . "'" . ", ";
				$SQLCommand .= "'" . $Flug["Gde"] . "'" . ", ";
				$SQLCommand .= "'" . $Flug["JG"] . "'" . ", ";
				$SQLCommand .= "'" . $Flug["Warnung"] . "'" . ", ";
				$SQLCommand .= "'" . $Flug["Reg"] . "'" . ", ";
				if ($Flug["Flaeche"]=="") 
					$SQLCommand .= "0, ";
				else
					$SQLCommand .= $Flug["Flaeche"] . ", ";
				$SQLCommand .= "'" . $Flug["Status"] . "'" . ", ";
				$SQLCommand .= "'Y'";
				$SQLCommand .= ")";

//				if (($Jahr==2023) and ($FeldDaten["FlugID"]=="FID000013")) {
				//if (($Jahr==2023)) {
					//echo $i . " " . $SQLCommand . "<br>";
					ExecuteQueries ($SQLCommand);
					//echo "Eisatztag: " . date("Y-m-d",strtotime($Flug["Einsatztag"])) . "<br>";
					echo $i . " Flug " . $Jahr.$Flug["FlugID"]  . " hinzugefügt.<br>";
				//}
				$i++;
			}
		}
	}
}	
// exectute 
//Add_Fluege();

//////////////////////////////////////////////////////////////////
// Löschen alte Datenbank strukturen und füge Plan hinzu
// von 20YY_RKRKPlan.csv zur Datenbank
//////////////////////////////////////////////////////////////////

function Add_Plan() {

	global $csvDocLocation, $RKRKWeatherCSV, $SQLSummary;
	$JahresListe = Array(2021,2022,2023,2024,2025);
	//$JahresListe = Array(2025);
	$i=1;
	$SQLCommands = Array();
	$SQLCommands["löschen"] = $SQLSummary["Plan"]["löschen"];
	$SQLCommands["erstellen"] = $SQLSummary["Plan"]["erstellen"];
	ExecuteQueries ($SQLCommands);


	foreach ($JahresListe as $Jahr) {
		echo "Jahr: " . $Jahr . "<br>";
		$csv_file= $Jahr . $RKRKWeatherCSV;
		$csv_deliminator = ";";

		//Read CSV file
		$PlanListe = csv_to_array($csvDocLocation.$csv_file, $csv_deliminator );
		//pre_R($PlanListe);
		//ExecuteQueries($SQLQuery);
		$SQLQueryHeader = "INSERT INTO `Plan`(`Datum`, `Wetter`, `Temp`, `Plan_TC`, `Plan_CH`, `Plan_VP`, `Plan_MB`, `Plan_LF`, `Plan_MF`, `Plan_AC`, `Plan_NC`) VALUES  ";

		// ('[value-1]','[value-2]','[value-3]','[value-4]','[value-5]','[value-6]','[value-7]','[value-8]','[value-9]','[value-10]','[value-11]')

		foreach ($PlanListe as $key => $Tag) {
			if (($i<5) or true) {
				//pre_R($Tag);	
				//echo $PoiPoint->description;
				$SQLCommand = $SQLQueryHeader . " (";
				$SQLCommand .= "'" . date_format(date_create($Tag["Datum"]),"Y-m-d") . "'" . ", ";
				$SQLCommand .= "'" . $Tag["Wetter"] . "'" . ", ";
				if ($Tag["Temp"]=="")
					$SQLCommand .= 0 . ", ";
				else
					$SQLCommand .= $Tag["Temp"] . ", ";
				$SQLCommand .= "'" . $Tag["Plan_TC"] . "'" . ", ";
				$SQLCommand .= "'" . $Tag["Plan_CH"] . "'" . ", ";
				$SQLCommand .= "'" . $Tag["Plan_VP"] . "'" . ", ";
				$SQLCommand .= "'" . $Tag["Plan_MB"] . "'" . ", ";
				$SQLCommand .= "'" . $Tag["Plan_LF"] . "'" . ", ";
				$SQLCommand .= "'" . $Tag["Plan_MF"] . "'" . ", ";
				$SQLCommand .= "'" . $Tag["Plan_AC"] . "'" . ", ";
				$SQLCommand .= "'" . $Tag["Plan_NC"] . "'";
				$SQLCommand .= ")";

//				if (($Jahr==2023) and ($FeldDaten["FlugID"]=="FID000013")) {
				//if (($Jahr==2023)) {
					//echo $i . " " . $SQLCommand . "<br>";
					ExecuteQueries ($SQLCommand);
					//echo "Einsatztag: " . date("Y-m-d",strtotime($Tag["Einsatztag"])) . "<br>";
					echo $i . " Tag " . $Tag["Datum"]  . " hinzugefügt.<br>";
				//}
				$i++;
			}
		}
	}
}	
// exectute 
//Add_Plan();


//////////////////////////////////////////////////////////////////
// Löschen alte Datenbank strukturen und füge Benutzer hinzu
// von Rehitzrettung - Users.csv zur Datenbank
//////////////////////////////////////////////////////////////////

function Add_Benutzer() {

	global $csvDocLocation, $RKRK_Users, $SQLSummary;
	$i=1;
	$SQLCommands = Array();
	$SQLCommands["löschen"] = $SQLSummary["Benutzer"]["löschen"];
	$SQLCommands["erstellen"] = $SQLSummary["Benutzer"]["erstellen"];
	ExecuteQueries ($SQLCommands);

	$csv_file= $RKRK_Users;
	$csv_deliminator = ";";

	//Read CSV file
	$BenutzerListe = csv_to_array($csvDocLocation.$csv_file, $csv_deliminator );
	//pre_R($BenutzerListe);
	//ExecuteQueries($SQLQuery);
	$SQLQueryHeader = "INSERT INTO `Benutzer`(`ID`, `BenutzerID`, `Initialen`, `Benutzername`, `Name`, `Vorname`, `Passwort`, `Passwort_hashed`, `Webaccess`, `AccessRights`, `Bemerkung`, `LetzterBesuch`, `Preferenz01`, `Preferenz02`, `Preferenz03`, `Preferenz04`, `RegisterDate`) VALUES ";

	//('[value-1]','[value-2]','[value-3]','[value-4]','[value-5]','[value-6]','[value-7]','[value-8]','[value-9]','[value-10]','[value-11]','[value-12]','[value-13]','[value-14]','[value-15]','[value-16]','[value-17]')

	foreach ($BenutzerListe as $key => $DataLine) {
		if (($i<5) or true) {
			//pre_R($DataLine);	
			//echo $PoiPoint->description;
			$SQLCommand = $SQLQueryHeader . " (" .$i. ",";
			$SQLCommand .= "'" . $DataLine["ID"] . "'" . ", ";
			$SQLCommand .= "'" . $DataLine["Initialen"] . "'" . ", ";
			$SQLCommand .= "'" . $DataLine["Benutzername"] . "'" . ", ";
			$SQLCommand .= "'" . $DataLine["Name"] . "'" . ", ";
			$SQLCommand .= "'" . $DataLine["Vorname"] . "'" . ", ";
			$SQLCommand .= "'" . $DataLine["Passwort"] . "'" . ", ";
			$SQLCommand .= "'" . $DataLine["Passwort_hashed"] . "'" . ", ";
			$SQLCommand .= "'" . $DataLine["Webaccess"] . "'" . ", ";
			$SQLCommand .= "'" . $DataLine["AccessRights"] . "'" . ", ";
			$SQLCommand .= "'" . $DataLine["Bemerkung"] . "'" . ", ";
			if ($DataLine["LetzterBesuch"] = "")
				$SQLCommand .= "null , ";
			else
				$SQLCommand .= "'" . date_format(date_create($DataLine["LetzterBesuch"]),"Y-m-d") . "'" . ", ";
			$SQLCommand .= "'" . $DataLine["Preferenz01"]. "'" . ", ";
			$SQLCommand .= "'" . $DataLine["Preferenz02"]. "'" . ", ";
			$SQLCommand .= "'" . $DataLine["Preferenz03"]. "'" . ", ";
			$SQLCommand .= "'" . $DataLine["Preferenz04"]. "'" . ", ";
			if ($DataLine["RegisterDate"] = "") 
				$SQLCommand .= "null";
			else
				$SQLCommand .= "'" . date_format(date_create($DataLine["RegisterDate"]),"Y-m-d") . "'";
			$SQLCommand .= ")";

//			if (($DataLine["FlugID"]=="Initialen")) {
				echo $i . " " . $SQLCommand . "<br>";
				ExecuteQueries ($SQLCommand);
				echo $i . " User " . $DataLine["Initialen"]  . " hinzugefügt.<br>";
			//}
			$i++;
		}
	}
}	
// exectute 
//Add_Benutzer();


//////////////////////////////////////////////////////////////////
// Löschen alte Datenbank strukturen und füge Kontakte hinzu
// von Rehkitzrettung Konaueramt - Kontaktnummern.csv zur Datenbank
//////////////////////////////////////////////////////////////////

function Add_Kontakte() {

	global $csvDocLocation, $RKRKContactFile, $SQLSummary;
	$i=1;
	$SQLCommands = Array();
	$SQLCommands["löschen"] = $SQLSummary["Kontakte"]["löschen"];
	$SQLCommands["erstellen"] = $SQLSummary["Kontakte"]["erstellen"];
	ExecuteQueries ($SQLCommands);

	$csv_file= $RKRKContactFile;
	$csv_deliminator = ";";

	//Read CSV file
	$Daten = csv_to_array($csvDocLocation.$csv_file, $csv_deliminator );
	//pre_R($Daten);
	//ExecuteQueries($SQLQuery);
	$SQLQueryHeader = "INSERT INTO `Kontakte`(`ID`, `KontaktID`, `Initialen`, `JG`, `Jagdgebiet`, `Name`, `Kontaktnummer`, `P Telefon`, `Mail`, `Adresse`, `RKRS`, `Typ`, `Kontakt`, `Platform`, `Einsatz`, `GeoMapLoc`, `Bemerkung`) VALUES ";

	// ('[value-1]','[value-2]','[value-3]','[value-4]','[value-5]','[value-6]','[value-7]','[value-8]','[value-9]','[value-10]','[value-11]','[value-12]','[value-13]','[value-14]','[value-15]','[value-16]','[value-17]')

	foreach ($Daten as $key => $DataLine) {
		if (($i<5) or true) {
			//pre_R($DataLine);	
			//echo $PoiPoint->description;
			$SQLCommand = $SQLQueryHeader . " (" .$i. ",";
			$SQLCommand .= "'" . $DataLine["ID"] . "'" . ", ";
			$SQLCommand .= "'" . $DataLine["Initialen"] . "'" . ", ";
			$SQLCommand .= "'" . $DataLine["JG"] . "'" . ", ";
			$SQLCommand .= "'" . $DataLine["Jagdgebiet"] . "'" . ", ";
			$SQLCommand .= "'" . $DataLine["Name"] . "'" . ", ";
			$SQLCommand .= "'" . $DataLine["Kontaktnummer"] . "'" . ", ";
			$SQLCommand .= "'" . $DataLine["P Telefon"] . "'" . ", ";
			$SQLCommand .= "'" . $DataLine["Mail"] . "'" . ", ";
			$SQLCommand .= "'" . $DataLine["Adresse"] . "'" . ", ";
			$SQLCommand .= "'" . $DataLine["RKRS"] . "'" . ", ";
			$SQLCommand .= "'" . $DataLine["Typ"]. "'" . ", ";
			$SQLCommand .= "'" . $DataLine["Kontakt"]. "'" . ", ";
			$SQLCommand .= "'" . $DataLine["Platform"]. "'" . ", ";
			$SQLCommand .= "'" . $DataLine["Einsatz"]. "'" . ", ";
			$SQLCommand .= "'" . $DataLine["GeoMapLoc"]. "'" . ", ";
			$SQLCommand .= "'" . $DataLine["Bemerkung"]. "'";
			$SQLCommand .= ")";

//			if (($DataLine["FlugID"]=="Initialen")) {
				echo $i . " " . $SQLCommand . "<br>";
				ExecuteQueries ($SQLCommand);
				echo $i . " User " . $DataLine["Initialen"]  . " hinzugefügt.<br>";
			//}
			$i++;
		}
	}
}	
// exectute 
//Add_Kontakte();



//////////////////////////////////////////////////////////////////
// Löschen alte Datenbank strukturen und füge Bauern hinzu
// von Rehkitzrettung Konaueramt - Kontaktnummern.csv zur Datenbank
//////////////////////////////////////////////////////////////////

function Add_Bauern() {

	global $csvDocLocation, $RKRK_LWB_ContactFile, $SQLSummary;
	$i=1;
	$SQLCommands = Array();
	$SQLCommands["löschen"] = $SQLSummary["Bauern"]["löschen"];
	$SQLCommands["erstellen"] = $SQLSummary["Bauern"]["erstellen"];
	ExecuteQueries ($SQLCommands);

	$csv_file= $RKRK_LWB_ContactFile;
	$csv_deliminator = ";";

	//Read CSV file
	$Daten = csv_to_array($csvDocLocation.$csv_file, $csv_deliminator );
	//pre_R($Daten);
	//ExecuteQueries($SQLQuery);
	$SQLQueryHeader = "INSERT INTO `Bauern`(`ID`, `BauerID`, `Initialen`, `Betrieb`, `Jagdgebiet`, `Name`, `Kontaktnummer`, `P Telefon`, `Mail`, `Adresse`, `GeoMapLoc`, `Typ`, `Bemerkung`, `Felder`) VALUES  ";

	//('[value-1]','[value-2]','[value-3]','[value-4]','[value-5]','[value-6]','[value-7]','[value-8]','[value-9]','[value-10]','[value-11]','[value-12]','[value-13]')

	foreach ($Daten as $key => $DataLine) {
		if (($i<5) or true) {
			//pre_R($DataLine);	
			//echo $PoiPoint->description;
			$SQLCommand = $SQLQueryHeader . " (" .$i. ",";
			$SQLCommand .= "'" . $DataLine["ID"] . "'" . ", ";
			$SQLCommand .= "'" . $DataLine["Initialen"] . "'" . ", ";
			$SQLCommand .= "'" . $DataLine["Betrieb"] . "'" . ", ";
			$SQLCommand .= "'" . $DataLine["Jagdgebiet"] . "'" . ", ";
			$SQLCommand .= "'" . $DataLine["Name"] . "'" . ", ";
			$SQLCommand .= "'" . $DataLine["Kontaktnummer"] . "'" . ", ";
			$SQLCommand .= "'" . $DataLine["P Telefon"] . "'" . ", ";
			$SQLCommand .= "'" . $DataLine["Mail"] . "'" . ", ";
			$SQLCommand .= "'" . $DataLine["Adresse"] . "'" . ", ";
			$SQLCommand .= "'" . (isset($DataLine["GeoMapLoc"]) ? $DataLine["GeoMapLoc"] : "") . "'" . ", ";
			$SQLCommand .= "'" . $DataLine["Typ"]. "'" . ", ";
			$SQLCommand .= "'" . $DataLine["Bemerkung"]. "'" . ", ";
			$SQLCommand .= "'" . $DataLine["Felder"]. "'";
			$SQLCommand .= ")";

//			if (($DataLine["FlugID"]=="Initialen")) {
				//echo $i . " " . $SQLCommand . "<br>";
				ExecuteQueries ($SQLCommand);
				echo $i . " Bauer " . $DataLine["Initialen"]  . " hinzugefügt.<br>";
			//}
			$i++;
		}
	}
}	
// exectute 
//Add_Bauern();


//////////////////////////////////////////////////////////////////
// Löschen alte Datenbank strukturen und füge Swiss3DHeight hinzu
// von Rehkitzrettung Konaueramt - Kontaktnummern.csv zur Datenbank
//////////////////////////////////////////////////////////////////


function Unzip_Swiss3DFiles($Daten, $ZipDirectory, $UnzipDirectory, $csv_deliminator ) {
	global $csvFileLocation;
	//unzip all files
	$f=1;
	foreach ($Daten as $Datenfile) {
		$ZipFile = $ZipDirectory.pathinfo($Datenfile["Filename"])['basename'];
		if (($f<=1) or !($TestOnly)) {
			echo $ZipFile;
			//$XYZDaten = csv_to_array($csvFileLocation, $csv_deliminator );
			//pre_R($XYZDaten);
			
			//unzip all datafiles
			$res=true;
			$zip = new ZipArchive;
			$res = $zip->open($ZipFile);
			if ($res === TRUE) {
			  // extract it to the path we determined above
			  $zip->extractTo($UnzipDirectory);
			  $zip->close();
			  echo "<span style='color:green'>$ZipFile extracted to $UnzipDirectory!</span><br>";
			} else {
			  echo "<span style='color:red'>File: $ZipFile not found!</span><br>";
			}
		}
		$f++;
	}	
}

function Add_Swiss3DHeight($BatchNr=1) {

	global $h3ddb, $csvDocLocation, $SQLSummary;
	$csvFileLocation = "../uav/SwissAlti3D/ch.swisstopo.swissalti3d-nTtwCnFq-Amt-AG-LU.csv";
	$ZipDirectory = "../uav/SwissAlti3D_files/";
	$UnzipDirectory = "../uav/SwissAlti3D_zip/";
	$FileBatchSize=10;
	$UploadBatchSize=1000;
	$TestOnly= false;
	
	$SQLCommands = Array();
	// needed to be executed only once to create teh additional DB!
	// ExecuteQueries ("CREATE DATABASE `CH3DHeight`;");	

	$csv_deliminator = ";";

	//Read CSV file
	$Daten = csv_to_array($csvFileLocation, $csv_deliminator );
	//pre_R($Daten);
		//('[value-1]','[value-2]','[value-3]','[value-4]')

	$FileList = array_diff(scandir($UnzipDirectory), array('..', '.', '@eaDir', 'Thumbs.db'));
	//pre_R($FileList);
	
	//Daten in DB Laden
	$f=1;
	foreach ($FileList as $key => $File) {
		//echo "File to upload: " .$UnzipDirectory.$File . "<br>";
		if (
			(($f<=3) or !($TestOnly)) 
			and (($f>=(($BatchNr-1)*$UploadBatchSize)) and ($f<=($BatchNr*$UploadBatchSize)))
			)
			{
		
			if (file_exists($UnzipDirectory.$File)) {
				//identify table name
				$FileNameSplit = multiexplode(Array(".","_"), $File);
				$TableName = $FileNameSplit[5]."_".$FileNameSplit[6];
				//echo "<b>" . $TableName . "</b>" ."<br>";

				//delete and create new table
				$SQLCommands["löschen"] = $SQLSummary["Swiss3DHeight"]["löschen"] . " `" . $TableName . "` ";
				$SQLCommands["erstellen"] = $SQLSummary["Swiss3DHeight"]["erstellen_part1"] . " `" . $TableName  . "` ". $SQLSummary["Swiss3DHeight"]["erstellen_part2"];
				$SQLQueryHeader = "INSERT INTO `".$TableName."`(`X`, `Y`, `Z`) VALUES  ";
				ExecuteQueries ($SQLCommands, $h3ddb);
				$XYZDaten = csv_to_array($UnzipDirectory.$File, ";");

				//Split datafile
				//pre_R($XYZDaten);
				$d=1;
				$SQLCommandList=Array();
				$SQLCommandText="";
				$NumOfEntries = Count($XYZDaten);
				//echo $NumOfEntries . "<br>";

				foreach ($XYZDaten as $XYZDataLine) {
					if (($d<=100) or !($TestOnly)) {
						$XYZ = explode(" ",$XYZDataLine["X Y Z"]);
						//pre_R($XYZ);
						
						//echo $PoiPoint->description;
						$SQLPrep = " (";
						$SQLPrep .= $XYZ[0] . ", ";
						$SQLPrep .= $XYZ[1] . ", ";
						$SQLPrep .= $XYZ[2];
						//$SQLPrep .= "'" . $File . "'";
						$SQLPrep .= ")" . chr(10);
						$SQLCommandList[]=$SQLPrep;
						$SQLCommandText.= $d . " XYZ: " . $XYZ[0] . " " . $XYZ[1] . " " . $XYZ[3] . " / ";
						
						if (
							(($d/$UploadBatchSize)==floor($d/$UploadBatchSize))  
							or ($d==NumOfEntries)
							)
							{
							// Upload Data
							//pre_r($SQLCommandList);
							$SQLCommand = $SQLQueryHeader . implode(", ", $SQLCommandList);
							//echo $d . " " . $SQLCommand . "<br>";
							//echo $SQLCommandText . " hinzugefügt.<br>";
							//echo "F" . sprintf('%03d', $f) . " bis " . sprintf('%06d', $d) . " hinzugefügt.<br>";
							ExecuteQueries ($SQLCommand, $h3ddb);
							unset($SQLCommandList);
							$SQLCommandText="";
						}
					}
					$d++;
				}
				//unlink($UnzipDirectory.$File);
			}
		}
		$f++;
	}
}	
// exectute 
for ($i = 5; $i <= 20; $i++) {
    //Add_Swiss3DHeight($i);
}

function Check_Swiss3DHeight () {
	
}




//----------------------------------------------------------------

//----------------------------------------------------------------
// geht noch nicht!!!!!

// Löschen alte Datenbank strukturen und füge KML Styles 
// von RKRK_ALLKMLStyles.xml zur Datenbank
// Export aus KML Datein mit allen Styles aktiviert
//////////////////////////////////////////////////////////////////

function formatXmlString($xml){
    $xml = preg_replace('/(>)(<)(\/*)/', "$1\n$2$3", $xml);
    $token      = strtok($xml, "\n");
    $result     = '';
    $pad        = 0; 
    $matches    = array();
    while ($token !== false) : 
        if (preg_match('/.+<\/\w[^>]*>$/', $token, $matches)) : 
          $indent=0;
        elseif (preg_match('/^<\/\w/', $token, $matches)) :
          $pad--;
          $indent = 0;
        elseif (preg_match('/^<\w[^>]*[^\/]>.*$/', $token, $matches)) :
          $indent=1;
        else :
          $indent = 0; 
        endif;
        $line    = str_pad($token, strlen($token)+$pad, ' ', STR_PAD_LEFT);
        $result .= $line . "\n";
        $token   = strtok("\n");
        $pad    += $indent;
    endwhile; 
    return $result;
}


function Add_KMLStyles() {
	global $xmlMissionLocation, $SQLSummary;
	$XMLFilename = "RKRK_ALLKMLStyles.xml";
	$XMLData = ReadLocalXMLFile($xmlMissionLocation.$XMLFilename, false);
	
	//pre_R($XMLData);
	$SQLCommands = Array();
	$SQLCommands["löschen"] = $SQLSummary["KMLStyles"]["löschen"];
	$SQLCommands["erstellen"] = $SQLSummary["KMLStyles"]["erstellen"];
	ExecuteQueries ($SQLCommands);
	//ExecuteQueries($SQLQuery);
	$SQLQueryHeader = "INSERT INTO `KMLStyles`(`ID`, `Name`, `IconStyle`, `LabelStyle`, `LineStyle`, `PolyStyle`,`Enable`) VALUES";
	$i=1;
	foreach ($XMLData->Document->Style as $key => $XMLEntry) {
		if (($XMLEntry["id"]>0) and (($i<3) or true)) {
			pre_R($XMLEntry);	
			echo "LineStyle: " . $XMLEntry["LineStyle"] . "<br>";;
			
			$SQLCommand = $SQLQueryHeader . " (" .$i. ",'" . $XMLEntry->id . "'";

			if (array_key_exists("IconStyle",$XMLEntry)) {
				$SQLCommand .= ", '".var_dump($XMLEntry->IconStyle)."'";
			} else {
				$SQLCommand .= ", null";
			}
			
			if (array_key_exists("LabelStyle",$XMLEntry)) {
				$SQLCommand .= ", '".$XMLEntry->LabelStyle."'";
			} else {
				$SQLCommand .= ", null";
			}
			
			if (array_key_exists("LineStyle",$XMLEntry)) {
				$SQLCommand .= ", '".$XMLEntry->LineStyle."'";
			} else {
				$SQLCommand .= ", null";
			}
			
			if (array_key_exists("PolyStyle",$XMLEntry)) {
				$SQLCommand .= ", '".$XMLEntry->PolyStyle."'";
			} else {
				$SQLCommand .= ", null";
			}

			$SQLCommand .= ",'Y')";
			echo $i . " " . $SQLCommand . "<br>";
			//ExecuteQueries ($SQLCommand);
			$i++;
		}
	}
}
	
// exectute 
//Add_KMLStyles();

//----------------------------------------------------------------


//PHPINFO;
//phpinfo();

?>