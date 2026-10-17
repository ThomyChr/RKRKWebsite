<?php
	// ===========================================
	// Paramter
	// ===========================================
/*
	http://tchr-frog.synology.me:5681/rkropen/htmltest/rkrk_xml2kml.php?Reg=21&File=21_Felder&Deb=on
	Reg : selektiere Regtion z.B, 21,15,25, ZG, AG, ALL
	File : file zum speicher der Daten z.B. "21_Felder" (extension braucht es nicht!)
	Deb : enable debug output
*/
  //session_start();
  include_once('scripts/rkrk_gen.php');
  include_once('scripts/rkrk_kml.php');




	// ===========================================
	// Defintions
	// ===========================================
/*
	$RKRKFelderfileXML = 'RKRKFelder.xml';
	$xmlMissionLocation = "xmlfiles/";
	$poi_settings_file = 'rkrk_kmlfile_settings.xml';
	$xml_poifile = "rkrk_kmldata.xml";
	$felder_file = 'RKRKFelder.xml';
	$kml_felderfile = "rkrk_kmlfelder.kml";
	$kmlFilelocations = "kmlfiles/";
	//$weblocation = "http://tchr-frog.synology.me:5681/rkropen/html/";
	$kmlReferenceFilelocations ="kmlfiles/referenzen/";
*/
	$DataOutput = "";
	$debugoutput = true;
	$FileName = "";
	$SaveFile = false;

	// ===========================================
	// check input paramter
	// ===========================================
	if (empty($_GET["Reg"])) {
		$RegionSelected = "ALL";
	} else {
		$RegionSelected =htmlspecialchars($_GET["Reg"]);
	}
	if (empty($_GET["File"])) {
		$SaveFile = false;
	} else {
		$FileName = htmlspecialchars($_GET["File"]);
		$SaveFile = true;
	}
	if (empty($_GET["Deb"])) {
		$debugoutput = false;
	} else {
		if (htmlspecialchars($_GET["Deb"])=="off") {
			$debugoutput = false;
		} else {
			$debugoutput = true;
		}
	}

	


headerpage("KML", false);
$ListOfStylesUsed = array();
GetPoiKML(
	$RegionSelected,
	$SaveFile,
	$debugoutput,
	true,
	true,
	true,
	true,
	true,
	true,
	true,
	true,
	true,
	true,
	false,
	true,
	true,
	$FileName,
	$ListOfStylesUsed
);
if (!$debugoutput) {
	if ($SaveFile) {
		echo "<p>KML gespeichert: " . htmlspecialchars($kmlFilelocations . $FileName . ".kml", ENT_QUOTES, "UTF-8") . "</p>";
	} else {
		echo "<p>Kein Dateiname. Aufruf mit ?File=26_Felder</p>";
	}
}
