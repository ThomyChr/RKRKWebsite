<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" >
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

	// ===========================================
	// check input paramter
	// ===========================================
	if (empty($_GET["Reg"])) {
		$RegionSelected = "ALL";
	} else {
		$RegionSelected =htmlspecialchars($_GET["Reg"]);
	}
	if (empty($_GET["File"])) {
		$RegionSelected = "ALL";
		$SaveFile = false;
	} else {
		$FileName =htmlspecialchars($_GET["File"]);
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

	


headerpage(false);
GetPoiKML($RegionSelected, $SaveFile, $debugoutput, $FileName);


//GetPoiKML($RegionSelected, $SaveFile, $debugoutput, $xmlMissionLocation, $xml_poifile, $kmlFilelocations);
?>
