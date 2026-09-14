<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" >
<?php

	session_start();
	include_once('scripts/rkrk_gen.php');
	include_once('scripts/rkrk_kmz.php');
	include_once('scripts/rkrk_dji_kmz_interface.php');

	echo "<H1>Zip Test 2025.05</H1>";

	// ===========================================
	// Definitions
	// ===========================================
	$TestFile = "21_Aeugst/21-001_Boosch_2152_50_CH_24.kmz";


	// ===========================================
	// Test
	// ===========================================

	UnzipAndStoreFile($RKRKLocalLoactionMissionFiles.$TestFile,$tempfilelocation);
	$ZipDirectory = $tempfilelocation.pathinfo($TestFile, PATHINFO_FILENAME)."/".$SubDirKMZ;
	$KmzFile = $ZipDirectory.$KmzFileName;

	// EXTRACT KMZ data to Array
	// -------------------------
	$KMZData = GetArrayFromKMZ($KmzFile);
	//pre_r($KMZData);
	
	ChangeDroneTypeAndHeightInKMZData("3T","4T",$KMZData);

	// CREATE KML data from array
	// --------------------------
	$XmlFileContent = arrayToXml($KMZData);
	//var_dump($XmlFileContent);
	
	// CREATE DIRECTORY
	// ----------------
	$NewHeight = GetFlightHeightForAltDrone("3T", "4TD", GetMeaningFilename($TestFile)[4], "IRCamAngle_H", 5);
	$TargetZipFile = ChangeHeightInFilename($TestFile, $NewHeight, "4TD", false);
	$TargetDirectory = $tempfilelocation.$TargetZipFile."/".$SubDirKMZ;
	$OutputKmzFile = $TargetDirectory.$KmzFileName;
	//echo "TargetDirectory " . $TargetDirectory . " OutputKmz: " . $OutputKmzFile . "<br>";

	CreateDirectoryAndSaveFile($TargetDirectory, $OutputKmzFile, $XmlFileContent);
	CreateZipFile($tempfilelocation, $TargetZipFile, $OutputKmzFile, "zip", true);
	CreateZipFile($tempfilelocation, $TargetZipFile, $OutputKmzFile, "kmz", true);
	
	//pre_r($DJI_DroneInformation);
	//pre_r($DJI_Commands);
?>