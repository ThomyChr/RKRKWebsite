<?php
  //session_start();
  include_once('rkrk_gen_ext.php');
  include_once('rkrk_ipregister.php');
  include_once(__DIR__ . '/rkrk_encoding.php');
  include_once(__DIR__ . '/rkrk_catalog_io.php');

// JSON-Kataloge sind die Quelle. XML-Support zum Abschalten:
// false = JSON only (weder XML lesen noch schreiben).
$RKRKXmlCatalogSupport = false;

// ================================================
//Switch für neues map.geo.admin.ch ab 25. Juni 2024
$NewMapGeoAdminPics =false; //FALSE, only fall back
// ================================================

// Old MAP.GEO.ADMIN till 30. June 2024
// ====================================
//$MapGeoAdminURL = "https://map.geo.admin.ch/embed.html?";


// New MAP.GEO.ADMIN from 25. Juni 2024
// ====================================
//$MapGeoAdminURL = "https://test.map.geo.admin.ch/#/map?";  // TEST SERVER bis 25. Juni 2024!!

//Showing Map with header
//$MapGeoAdminURL = "https://map.geo.admin.ch/#/map?";
//Showing Map embedded without a header
$MapGeoAdminURL = "https://map.geo.admin.ch/#/embed?";
if (function_exists('rkrk_config_branding')) {
	$cfgMapUrl = rkrk_config_branding()['map_geo_admin_url'];
	if ($cfgMapUrl !== '') {
		$MapGeoAdminURL = $cfgMapUrl;
	}
}


// ==================
// Generic Funktionen für die Rehktitzrettung
// ==================



Function SetMissionLocation() {
	global 	//$DebugWork, 
			$SessNme_MissionDBSetting,
			$MissionDroneTypes,
			$MissionLocation, 
			$WebMissionLocation,
			$RKRKMissionSelected,
			$RKRKRelWebLocation,
			$RKRKRelRKRKDataLocation,
			$RKRKLocalLoactionMissionFiles, 
			$RKRKLocalLoactionDocFiles,
			$RKRKExternalLoactionMissionFiles, 
			$RKRKExternalLoactionDocFiles, 
			$csvMissionLocation, 
			$csvDocLocation, 
			$csvFileDirectory,
			$xmlMissionLocation, 
			$xmlDocLocation, 
			$xmlFileDirectory,
			$jsonMissionLocation,
			$jsonDocLocation,
			$jsonFileDirectory,
			$tempfilelocation,
			$WebLocation, 
			$historyfilelocation, 
			$deletefilelocation, 
			$logfilelocation,
			$SecParamters,
			$SecUrl;
			
	//pre_R($SecParamters); 
	
	if ($SecUrl) 
		$RKRKMissionSelected= $SecParamters["MDB"]??'';
	elseif (isset($_GET["MDB"])) {
		$RKRKMissionSelected = $_GET["MDB"];
	} elseif (isset($_SESSION[$SessNme_MissionDBSetting])) {	
		$RKRKMissionSelected = $_SESSION[$SessNme_MissionDBSetting];
	}

	//default setting
	if ($RKRKMissionSelected=="") {
		$RKRKMissionSelected = "3T";
	}

	$_SESSION[$SessNme_MissionDBSetting] = $RKRKMissionSelected;
	
	//echo "<br><br><br><br>Mission Selected: " . $RKRKMissionSelected;
	//pre_R($_SESSION);

	// Initialize $Debugoutput
	$Debugoutput = '';
	if (isset($_SESSION[$SessNme_MissionDBSetting]))  {
		$Debugoutput .=  $_SESSION[$SessNme_MissionDBSetting];
	}
	
	//pre_R($MissionDroneTypes);
	$MissionLocation = $MissionDroneTypes[$RKRKMissionSelected]["MissionLocation"];
	$DocLocation = $MissionDroneTypes[$RKRKMissionSelected]["DocLocation"];
	//echo "<br><br>Mission: " . $RKRKMissionSelected . " // ". $MissionLocation . "  Doc: " . $DocLocation . "<br>";

/*	
	if ($RKRKMissionSelected == "3T") {
		//Mavic M3T
		$MissionLocation = "Missionen/";
		$DocLocation = "Missionen/";
	} elseif ($RKRKMissionSelected == "4T") {
		//Matrice 4T
		$MissionLocation = "Missionen Matrice 4T/";
		$DocLocation = "Missionen/";
	} elseif ($RKRKMissionSelected == "30T") {
		//Matrice 30T
		$MissionLocation = "Missionen Matrice 30T/";
		$DocLocation = "Missionen/";
	} elseif ($RKRKMissionSelected == "M2") {
		//Mavic II
		$MissionLocation = "Missionen MavicII/";
		$DocLocation = "Missionen/";
	} else {
		//Debug
		$MissionLocation = "Missionen Debug/";
		$DocLocation = "Missionen Debug/";
	}
*/
	//if (isset($_SESSION[$SessNme_MissionDBSetting]))  {
	//	$Debugoutput .= " - SetTo: " . $_SESSION[$SessNme_MissionDBSetting] . "/ Missions: " . $MissionLocation . "/ Docs: " . $DocLocation . "<br>";
	//}
	//echo $Debugoutput;

/*
	if ($DebugWork) {
		$MissionLocation = "Missionen Debug/";
	} else {
		$MissionLocation = "Missionen/";
	}
*/
	$WebMissionLocation  = $WebLocation. $MissionLocation;
	$WebDocLocation  = $WebLocation. $DocLocation;
	//$WebMissionLocation  = $WebLocation. "missionen/";

	$RKRKRelWebLocation = "../../rkropen/";
	$RKRKLocalLoactionMissionFiles = $RKRKRelWebLocation . $MissionLocation;
	$RKRKRelRKRKDataLocation  = $RKRKRelWebLocation . "rkrk/";
	$RKRKLocalLoactionDocFiles = $RKRKRelWebLocation . $DocLocation;
	//$RKRKRelRKRKDataLocation  = $WebDocLocation . "rkrk/";
	//$RKRKLocalLoactionDocFiles = $WebDocLocation . $DocLocation;
	//$RKRKLocalLoactionMissionFiles = '../../../Rehkitzrettung/' . $MissionLocation; - 20221031 GEHT NICHT!!!
	$RKRKExternalLoactionMissionFiles = $WebLocation . $MissionLocation;
	$RKRKExternalLoactionDocFiles = $WebLocation . $DocLocation;
	// Fluglisten, Kontakte, Planung, Wetter: RKRK-weit, nicht missionsabhängig.
	$csvFileDirectory = 'webcsv/';
	$csvDocLocation = $RKRKRelRKRKDataLocation . $csvFileDirectory;
	$csvMissionLocation = $csvDocLocation;
	if ($csvDocLocation !== '' && !is_dir($csvDocLocation)) {
		@mkdir($csvDocLocation, 0775, true);
	}
	$xmlFileDirectory="xmlfiles/";
	$xmlDocLocation = $RKRKLocalLoactionDocFiles . $xmlFileDirectory;
	$xmlMissionLocation = $RKRKLocalLoactionMissionFiles . $xmlFileDirectory;
	// Katalog-JSON (Felder, POI, Layer, Regionen, …) sind RKRK-weit, nicht missionsabhängig.
	$jsonFileDirectory = 'webdaten/';
	$jsonDocLocation = $RKRKRelRKRKDataLocation . $jsonFileDirectory;
	$jsonMissionLocation = $jsonDocLocation;
	if ($jsonMissionLocation !== '' && !is_dir($jsonMissionLocation)) {
		@mkdir($jsonMissionLocation, 0775, true);
	}
	$historyfilelocation = $RKRKLocalLoactionDocFiles . "_history/";
	$logfilelocation = $RKRKLocalLoactionDocFiles . "_log/";
	$deletefilelocation = $RKRKLocalLoactionDocFiles . "_deleted/";
	$tempfilelocation = $RKRKLocalLoactionDocFiles . "_temp/";
	if (function_exists('rkrk_regional_apply_json')) {
		rkrk_regional_apply_json();
	}
}

// ===================
// Defintions
// ===================


	$JsonDrohnentyp = array(
					"DJIPilot2" => "DJI Pilot 2",
					"mavic3T" => "Mavic 3T",
					"mavic3E" => "Mavic 3E",
					"mavic3M" => "Mavic 3M",
					"mavic3D" => "Mavic 3D",
					"matrice30T" => "Matrice 30T",
					"matrice4T" => "Matrice 4T",
					"matrice4TD" => "Matrice 4TD",
					//"DJILitchi" => "DJI Litchi"
					);


	$MissionDroneTypes =Array(
					"3T" => array(
						"Name" => "Mavic 3T",
						"img" => "M3T.png",
						"MissionLocation" => "Missionen/",
						"dependantMissions" => "4TD,30T",
						"DocLocation" => "Missionen/",
						"button" => "mavic3t_button.png",
						"button_orange" => "mavic3t_orange_button.png",
						"json_button" => "downloadjsonfromM3t.png",
						"json_button_orange" => "convertjsontoM3t.png",
						"kmz_button" => "downloadkmzfromM3t.png",
						"kmz_button_orange" => "convertkmztoM3t.png",
						"release" => "test, intern, partner, extern",
						"MissionLocation" => "Missionen/",
						"DataFile" => "SuchfeldBild,FlugplanBild,JasonFile,KMZFile",
						"active" => true,
						),
/*
					"4T" => array(
						"Name" => "Matrice 4T",
						"img" => "M4T.png",
						"MissionLocation" => "Missionen Matrice 4T/",
						//"dependantMissions" => "3T",
						"DocLocation" => "Missionen/",
						"button" => "matrice4T_button.png",
						"button_orange" => "matrice4T_orange_button.png",
						"json_button" => "downloadjsonfrom4t.png",
						"json_button_orange" => "convertjsonto4t.png",
						"kmz_button" => "downloadkmzfrom4t.png",
						"kmz_button_orange" => "convertkmzto4t.png",
						"release" => "test, intern",
						"DataFile" => "SuchfeldBild,FlugplanBild,JasonFile,KMZFile",
						"active" => false,
						),
*/
					"4TD" => array(
						"Name" => "Matrice 4TD",
						"img" => "M4TD.png",
						"MissionLocation" => "Missionen Matrice 4TD/",
						//"dependantMissions" => "3T",
						"DocLocation" => "Missionen/",
						"button" => "matrice4TD_button.png",
						"button_orange" => "matrice4TD_orange_button.png",
						"json_button" => "downloadjsonfrom4tD.png",
						"json_button_orange" => "convertjsonto4tD.png",
						"kmz_button" => "downloadkmzfrom4tD.png",
						"kmz_button_orange" => "convertkmzto4tD.png",
						"release" => "test, intern",
						"DataFile" => "SuchfeldBild,FlugplanBild,JasonFile,KMZFile",
						"active" => true,
						),
					"30T" => array(
						"Name" => "Matrice 30T",
						"img" => "M30T.png",
						"MissionLocation" => "Missionen Matrice 30T/",
						//"dependantMissions" => "M3T,4TD",
						"DocLocation" => "Missionen/",
						"button" => "matrice30T_button.png",
						"button_orange" => "matrice30T_orange_button.png",
						"json_button" => "downloadjsonfrom30t.png",
						"json_button_orange" => "convertjsonto30t.png",
						"kmz_button" => "downloadkmzfrom30t.png",
						"kmz_button_orange" => "convertkmzto30t.png",
						"release" => "test, intern",
						"DataFile" => "SuchfeldBild,FlugplanBild,JasonFile,KMLFile,LichtiFile",
						"active" => false, //Deaktiviert per Jan 2026
						),
					"M2" => array(
						"Name" => "Mavic 2, FB320 50°",
						"UAVDroneType" => "Litchi",
						"img" => "flir_wb320.jpg",
						"MissionLocation" => "Missionen MavicII/",
						"dependantMissions" => "",
						"DocLocation" => "Missionen/",
						"button" => "mavic2_button.png",
						"button_orange" => "mavic2_orange_button.png",
						"json_button" => "mavic2_button.png",
						"json_button_orange" => "mavic2_orange_button.png",
						"kmz_button" => "mavic2_button.png",
						"kmz_button_orange" => "mavic2_orange_button.png",
						"release" => "test",
						"DataFile" => "SuchfeldBild,FlugplanBild,JasonFile,KMZFile",
						"active" => false,
						),
					"D" => array(
						"Name" => "Mavic 3T - Debug",
						"img" => "M3T.jpg",
						"MissionLocation" => "Missionen Debug/",
						"dependantMissions" => "4TD",
						"DocLocation" => "Missionen Debug/",
						"button" => "debug_button.png",
						"button_orange" => "debug_orange_button.png",
						"json_button" => "downloadjsonfromD.png",
						"json_button_orange" => "convertjsontoD.png",
						"kmz_button" => "downloadkmzfromD.png",
						"kmz_button_orange" => "convertkmztoD.png",
						"release" => "test",
						"DataFile" => "SuchfeldBild,FlugplanBild,JasonFile,KMZFile",
						"active" => true,
						),
					);

	if (function_exists('rkrk_config_apply_missions')) {
		rkrk_config_apply_missions($MissionDroneTypes);
	}


	$KatalogBaseline = "29.04.2023";
	
	if ( isset( $_SERVER['HTTPS'] ) ) {
		$Con =  "https://";
		//$WebLocation  = $Con . "tchr-frog.synology.me:5682/rkropen/";
		$WebLocation  = $Con . "tchr-frog.synology.me/rkropen/";
	} else {
		$Con =  "http://";
		//$WebLocation  = $Con . "tchr-frog.synology.me:5681/rkropen/";
		$WebLocation  = $Con . "tchr-frog.synology.me/rkropen/";
	}
	
	
	$colorTableEven = "#DEEADE";
	$MissionLocation = "";
	$WebMissionLocation  = "";
	$RKRKRelWebLocation= "";
	$RKRKRelRKRKDataLocation="";
	$RKRKLocalLoactionMissionFiles = "";
	$RKRKLocalLoactionDocFiles = "";
	$RKRKExternalLoactionMissionFiles = "";
	$RKRKExternalLoactionDocFiles = "";
	$csvMissionLocation = "";
	$xmlFileDirectory = "";
	$csvFileDirectory="";
	$csvDocLocation ="";
	$xmlMissionLocation = "";
	$xmlDocLocation ="";
	$tempfilelocation ="";
	$historyfilelocation = "";
	$deletefilelocation = "";
	$CleanAgeOfFilesInDays= 30;

	$FlugzeitTreshold = 25; //[min]
	$FluzeitStartUndLandung = 1.2; //[min]

	$SessNme_RegisteredUser="Benutzername";
	$SessNme_RegisteredInitialen="Initialen";
	$SessNme_RegisteredName="Name";
	$SessNme_RegisteredVorname="Vorname";
	$SessNme_RegisteredEmail="Email";
	$SessNme_AccessLevel="UserLevel";
	$SessNme_Password="PWnnshd";
	$SessNme_PasswordHashed="PasswordHashed";
	$SessNme_Sessionstart="Sessionstart";
	$SessNme_IPAddress="IPAddress";
	$SessNme_SelRegions="SelRegions";

	$ManualFieldLabel = "MAN";
	$GuestAccessParamter = "&GA=gast";

	$PicLoc = "../pics/";

	// SET MISSION Location
	//=====================
	SetMissionLocation();
	 
	//$Regions = array("21" => "21", "25" => "25", "26" => "26", "27" => "27", "28" => "28", "AZ" => "AZ",  "AG" => "AG", "ZG" => "ZG",  "XX" => "XX", "TF" => "TF", "NO" => "NO", "ALL" => "ALL",); 
    //NEU Gebiete
	// "pilotGroupAreaName": "136 Abwil",

	$RegionSettings = array(	
						"Amt" => array("Reg" => "Amt",
									  "JGList" => "21,24,25,26,27,28,27,AZ",
									  "GdeList" => "",
									  "RegUp" => "AmtZH",
									  "Opt" => "Amt",
									  "Dir" => "Amt",
									  "Text" => "AZ-Amt Zürich",
									  "GroupAreaName" => "AZ Amt ZH",
									  "GroupAreaID" => "MaxB7ZDZJ", // from UAV Editor
									  "NumEntries" => 0,
									  "GeoMapSetting" => array(
										"East" =>2677473.70,
										"North" =>1231853.81,
										"Zoom" =>6,
									  ),
									  "Gebiettyp" => "Support",
									  "MaxID" => 0,
									  "Aktiv" => false),
						"21" => array("Reg" => "21",
									  "JGList" => "21",
									  "GdeList" => "AeU,HaA,MET,RIF",
									  "RegUp" => "AmtZH",
									  "Opt" => "21",
									  "Dir" => "21_Aeugst",
									  "Text" => "21-Aeugst",
									  "GroupAreaName" => "21 Aeugst a.A.",
									  "GroupAreaID" => "US1J5Nrae", // from UAV Editor
									  "GeoMapSetting" => array(
										"East" =>2679634.05,
										"North" =>1235996.10,
										"Zoom" =>8,
									  ),
									  "Gebiettyp" => "Kern",
									  "NumEntries" => 0,
									  "MaxID" => 0,
									  "Aktiv" => true),
						"24" => array("Reg" => "24",
									  "JGList" => "24",
									  "GdeList" => "RIF,HaA",
									  "RegUp" => "AmtZH",
									  "Opt" => "24",
									  "Dir" => "24_Hausen",
									  "Text" => "24-Hausen",
									  "GroupAreaName" => "24 Hausen a.A.",
									  "GroupAreaID" => "actTJKiuQ", // from UAV Editor
									  "GeoMapSetting" => array(
										"East" =>2682985.1,
										"North" =>1233188.9,
										"Zoom" =>6.5,
									  ),
									  "Gebiettyp" => "Kern",
									  "NumEntries" => 0,
									  "MaxID" => 0,
									  "Aktiv" => true),
						"25" => array("Reg" => "25",
									  "JGList" => "25",
									  "GdeList" => "KaA,RIF,MET,HaA",
									  "RegUp" => "AmtZH",
									  "Opt" => "25",
									  "Dir" => "25_Kappel",
									  "Text" => "25-Kappel",
									  "GroupAreaName" => "25 Kappel a.A.",
									  "GroupAreaID" => "0cNN4RFI9", // from UAV Editor
									  "GeoMapSetting" => array(
										"East" =>2681584.67,
										"North" =>1230882.35,
										"Zoom" =>7.5,
									  ),
									  "Gebiettyp" => "Kern",
									  "NumEntries" => 0,
									  "MaxID" => 0,
									  "Aktiv" => true),
						"26" => array("Reg" => "26",
									  "JGList" => "26",
									  "GdeList" => "KaA,KN,MET,",
									  "RegUp" => "AmtZH",
									  "Opt" => "26",
									  "Dir" => "26_Knonau",
									  "Text" => "26-Knonau",
									  "GroupAreaName" => "26 Knonau",
									  "GroupAreaID" => "MogVV7FRz", // from UAV Editor
									  "GeoMapSetting" => array(
										"East" =>2677894.19,
										"North" =>1230647.47,
										"Zoom" =>8,
									  ),
									  "Gebiettyp" => "Kern",
									  "NumEntries" => 0,
									  "MaxID" => 0,
									  "Aktiv" => true),
						"27" => array("Reg" => "27",
									  "JGList" => "27",
									  "GdeList" => "MA",
									  "RegUp" => "AmtZH",
									  "Opt" => "27",
									  "Dir" => "27_Maschwanden", 
									  "Text" => "27-Maschwanden",
									  "GroupAreaName" => "27 Maschwanden",
									  "GroupAreaID" => "N7lk0YTt0", // from UAV Editor
									  "GeoMapSetting" => array(
										"East" =>2675091.07,
										"North" =>1232055.51,
										"Zoom" =>7.5,
									  ),
									  "Gebiettyp" => "Kern",
									  "NumEntries" => 0,
									  "MaxID" => 0,
									  "Aktiv" => true),
						"28" => array("Reg" => "28",
									  "JGList" => "28",
									  "GdeList" => "MET,RIF,KN,KaA",
									  "RegUp" => "AmtZH",
									  "Opt" => "28",
									  "Dir" => "28_Mettmenstetten", 
									  "Text" => "28-Mettmenstetten",
									  "GroupAreaName" => "28 Mettmenstetten",
									  "GroupAreaID" => "w637Kdcce", // from UAV Editor
									  "GeoMapSetting" => array(
										"East" =>2677537.54,
										"North" =>1233550.61,
										"Zoom" =>7,
									  ),
									  "Gebiettyp" => "Kern",
									  "NumEntries" => 0,
									  "MaxID" => 0,
									  "Aktiv" => true),
						"32" => array("Reg" => "32",
									  "JGList" => "32",
									  "GdeList" => "AeU,Sta",
									  "RegUp" => "AmtZH",
									  "Opt" => "32",
									  "Dir" => "32_Stallikon_II", 
									  "Text" => "32 Stallikon II",
									  "GroupAreaName" => "32 Stallikon II",
									  "GroupAreaID" => "dz0B8q00c", // from UAV Editor
									  "GeoMapSetting" => array(
										"East" =>2680044.0,
										"North" =>1238635.0,
										"Zoom" =>7.5,
									  ),
									  "Gebiettyp" => "Kern",
									  "NumEntries" => 0,
									  "MaxID" => 0,
									  "Aktiv" => true),
						"AZ" => array("Reg" => "AZ",
									  "JGList" => "AZ",
									  "GdeList" => "",
									  "RegUp" => "AmtZH",
									  "Opt" => "AZ",
									  "Dir" => "AZ_Amt",
									  "Text" => "AZ-Amt",
									  "GroupAreaName" => "AZ Amt",
									  "GroupAreaID" => "MaxB7ZDZJ", // from UAV Editor
									  "GeoMapSetting" => array(
										"East" =>2674570.15,
										"North" =>1233968.12,
										"Zoom" =>8,
									  ),
									  "Gebiettyp" => "Support",
									  "NumEntries" => 0,
									  "MaxID" => 0,
									  "Aktiv" => true),
						"AG" => array("Reg" => "AG",
									  "JGList" => "AG,136,145,148",
									  "GdeList" => "",
									  "RegUp" => "AG",
									  "Opt" => "AG",
									  "Dir" => "AG_Aargau", 
									  "Text" => "AG-Oberes Freiamt",
									  "GroupAreaName" => "AG Oberes Freiamt AG",
									  "GroupAreaID" => "FeASijSb-", // from UAV Editor
									  "GeoMapSetting" => array(
										"East" =>2670325.0,
										"North" =>1226077.5,
										"Zoom" =>8,
									  ),
									  "Gebiettyp" => "Support",
									  "NumEntries" => 0,
									  "MaxID" => 0,
									  "Aktiv" => true),
						"136" => array("Reg" => "136",
									  "JGList" => "136",
									  "GdeList" => "SI,AB",
									  "RegUp" => "AG",
									  "Opt" => "136",
									  "Dir" => "136_Abtwil", 
									  "Text" => "136-Abtwil",
									  "GroupAreaName" => "136 Abtwil",
									  "GroupAreaID" => "ADdpeieNR", // from UAV Editor
									  "GeoMapSetting" => array(
										"East" =>2669330.0,
										"North" =>1225185.0,
										"Zoom" =>8,
									  ),
									  "Gebiettyp" => "Kern",
									  "NumEntries" => 0,
									  "MaxID" => 0,
									  "Aktiv" => true),
						"137" => array("Reg" => "137",
									  "JGList" => "137",
									  "GdeList" => "MUE,AUW,BEI",
									  "RegUp" => "AG",
									  "Opt" => "137",
									  "Dir" => "137_Auw", 
									  "Text" => "137-Auw",
									  "GroupAreaName" => "137 Auw",
									  "GroupAreaID" => "", // from UAV Editor
									  "GeoMapSetting" => array(
										"East" =>2670163.0,
										"North" =>1229552.0,
										"Zoom" =>8,
									  ),
									  "Gebiettyp" => "Kern",
									  "NumEntries" => 0,
									  "MaxID" => 0,
									  "Aktiv" => false),
						"143" => array("Reg" => "143",
									  "JGList" => "143",
									  "GdeList" => "SI,OR,DIE",
									  "RegUp" => "AG",
									  "Opt" => "143",
									  "Dir" => "143_Dietwil", 
									  "Text" => "143-Dietwil",
									  "GroupAreaName" => "143 Dietwil",
									  "GroupAreaID" => "_ZvHG6nkO", // from UAV Editor
									  "GeoMapSetting" => array(
										"East" =>2672308.4,
										"North" =>1222417.4,
										"Zoom" =>8,
									  ),
									  "Gebiettyp" => "Kern",
									  "NumEntries" => 0,
									  "MaxID" => 0,
									  "Aktiv" => true),
						"145" => array("Reg" => "145",
									  "JGList" => "145",
									  "GdeList" => "SI,OR,DIE",
									  "RegUp" => "AG",
									  "Opt" => "145",
									  "Dir" => "145_Oberrüti", 
									  "Text" => "136-Oberrüti",
									  "GroupAreaName" => "145 Oberrüti",
									  "GroupAreaID" => "7OWlwJWtQ", // from UAV Editor
									  "GeoMapSetting" => array(
										"East" =>2672385.0,
										"North" =>1224360.0,
										"Zoom" =>8,
									  ),
									  "Gebiettyp" => "Kern",
									  "NumEntries" => 0,
									  "MaxID" => 0,
									  "Aktiv" => true),
						"148" => array("Reg" => "148",
									  "JGList" => "148",
									  "GdeList" => "SI,OR,AB",
									  "RegUp" => "AG",
									  "Opt" => "148",
									  "Dir" => "148_Sins", 
									  "Text" => "148-Sins",
									  "GroupAreaName" => "148 Sins",
									  "GroupAreaID" => "ckHHxakrl", // from UAV Editor
									  "GeoMapSetting" => array(
										"East" =>2670610.0,
										"North" =>1226345.0,
										"Zoom" =>7,
									  ),
									  "Gebiettyp" => "Kern",
									  "NumEntries" => 0,
									  "MaxID" => 0,
									  "Aktiv" => true),
						"ZG" => array("Reg" => "ZG",
									  "JGList" => "ZG,ZG1,ZG2,ZG3,ZG4,ZG5,ZG6",
									  "GdeList" => "",
									  "RegUp" => "ZG",
									  "Opt" => "ZG",
									  "Dir" => "ZG_Zug",  
									  "Text" => "ZG-Zug",
									  "GroupAreaID" => "", // from UAV Editor
									  "GroupAreaName" => "",
									  "GeoMapSetting" => array(
										"East" =>2681928.78,
										"North" =>1222959.89, 
										"Zoom" =>5,
									  ),
									  "Gebiettyp" => "Support",
									  "NumEntries" => 0,
									  "MaxID" => 0,
									  "Aktiv" => true),
						"ZG1" => array("Reg" => "ZG1",
									  "JGList" => "ZG1",
									  "GdeList" => "CHA,STE,HUE,RIS,ZUG",
									  "RegUp" => "ZG",
									  "Opt" => "ZG1",
									  "Dir" => "ZG1_Zug",  
									  "Text" => "ZG1-Zug",
									  "NumEntries" => 0,
									  "GroupAreaName" => "ZG1 Zug 1",
									  "GroupAreaID" => "6CNisoLvS", // from UAV Editor
									  "GeoMapSetting" => array(
										"East" =>2676695.6,
										"North" =>1223498.9,
										"Zoom" =>6,
									  ),
									  "Gebiettyp" => "Support",
									  "MaxID" => 0,
									  "Aktiv" => true),
						"ZG2" => array("Reg" => "ZG2",
									  "JGList" => "ZG2",
									  "GdeList" => "CHA,HUE,RIS",
									  "RegUp" => "ZG",
									  "Opt" => "ZG2",
									  "Dir" => "ZG2_Zug",  
									  "Text" => "ZG2-Zug",
									  "GroupAreaName" => "ZG2 Zug 2",
									  "GroupAreaID" => "UBWHb4Iqy", // from UAV Editor
									  //"GroupAreaID" => "6CNisoLvS", // from UAV Editor
									  "GeoMapSetting" => array(
										"East" =>2675073.4,
										"North" =>1228788.9,
										"Zoom" =>6,
									  ),
									  "Gebiettyp" => "Support",
									  "NumEntries" => 0,
									  "MaxID" => 0,
									  "Aktiv" => true),
						"ZG3" => array("Reg" => "ZG3",
									  "JGList" => "ZG3",
									  "GdeList" => "STE,NEU,BAA,ZUG,MEN",
									  "RegUp" => "ZG",
									  "Opt" => "ZG3",
									  "Dir" => "ZG3_Zug",  
									  "Text" => "ZG3-Zug",
									  "GroupAreaName" => "ZG3 Zug 3",
									  "GroupAreaID" => "IHzc5mPyU", // from UAV Editor
									  "GeoMapSetting" => array(
										"East" =>2682254.5,
										"North" =>1228062.3,
										"Zoom" =>6,
									  ),
									  "Gebiettyp" => "Support",
									  "NumEntries" => 0,
									  "MaxID" => 0,
									  "Aktiv" => true),
						"ZG4" => array("Reg" => "ZG4",
									  "JGList" => "ZG4",
									  "GdeList" => "ZUG,WAL,BAA",
									  "RegUp" => "ZG",
									  "Opt" => "ZG4",
									  "Dir" => "ZG4_Zug",  
									  "Text" => "ZG4-Zug",
									  "GroupAreaName" => "ZG4 Zug 4",
									  "GroupAreaID" => "DhYxFDbOC", // from UAV Editor
									  "GeoMapSetting" => array(
										"East" =>2681336.7,
										"North" =>1221501.1,
										"Zoom" =>6,
									  ),
									  "Gebiettyp" => "Support",
									  "NumEntries" => 0,
									  "MaxID" => 0,
									  "Aktiv" => true),
						"ZG5" => array("Reg" => "ZG5",
									  "JGList" => "ZG5",
									  "GdeList" => "ZUG,WAL,BAA,UAE,MEN",
									  "RegUp" => "ZG",
									  "Opt" => "ZG5",
									  "Dir" => "ZG5_Zug",  
									  "Text" => "ZG5-Zug",
									  "GroupAreaName" => "ZG5 Zug 5",
									  "GroupAreaID" => "fAUjXpWvk", // from UAV Editor
									  "GeoMapSetting" => array(
										"East" =>2686204.5,
										"North" =>1219528.9,
										"Zoom" =>6,
									  ),
									  "Gebiettyp" => "Support",
									  "NumEntries" => 0,
									  "MaxID" => 0,
									  "Aktiv" => true),
						"ZG6" => array("Reg" => "ZG6",
									  "JGList" => "ZG6",
									  "GdeList" => "MEN,OAE,NEU,UAE",
									  "RegUp" => "ZG",
									  "Opt" => "ZG6",
									  "Dir" => "ZG6_Zug",  
									  "Text" => "ZG6_Zug",
									  "GroupAreaName" => "ZG6 Zug 6",
									  "GroupAreaID" => "4vuEk-LQe", // from UAV Editor
									  "GeoMapSetting" => array(
										"East" =>2689925.6,
										"North" =>1223180.0,
										"Zoom" =>6,
									  ),
									  "Gebiettyp" => "Support",
									  "NumEntries" => 0,
									  "MaxID" => 0,
									  "Aktiv" => true),
						"LU" => array("Reg" => "LU",
									  "JGList" => "LU,LME",
									  "GdeList" => "",
									  "RegUp" => "LU",
									  "Opt" => "LU",
									  "Dir" => "LU_Luzern",  
									  "Text" => "LU-Luzern",
									  "GroupAreaID" => "", // from UAV Editor
									  "GroupAreaName" => "",
									  "GeoMapSetting" => array(
										"East" =>2677167.41,
										"North" =>1218563.10,
										"Zoom" =>5,
									  ),
									  "Gebiettyp" => "Support",
									  "NumEntries" => 0,
									  "MaxID" => 0,
									  "Aktiv" => true),
						"LME" => array("Reg" => "LME",
									  "JGList" => "LU,LME",
									  "GdeList" => "",
									  "RegUp" => "LU",
									  "Opt" => "LME",
									  "Dir" => "LME_Meierskappel",  
									  "Text" => "LU-Meierskappel",
									  "GroupAreaID" => "eESFck71J", // from UAV Editor
									  "GroupAreaName" => "LME Meierskappel",
									  "GeoMapSetting" => array(
										"East" =>2677116.31,
										"North" => 1218544.35,
										"Zoom" =>8,
									  ),
									  "Gebiettyp" => "Kern",
									  "NumEntries" => 0,
									  "MaxID" => 0,
									  "Aktiv" => true),
						"SZ" => array("Reg" => "SZ",
									  "JGList" => "SZ",
									  "GdeList" => "",
									  "RegUp" => "SZ",
									  "Opt" => "SZ",
									  "Dir" => "SZ_Schwyz",  
									  "Text" => "SZ-Schwyz",
									  "GroupAreaID" => "", // from UAV Editor
									  "GroupAreaName" => "SZ Schwyz",
									  "GeoMapSetting" => array(
										"East" =>2677167.41,
										"North" =>1218563.10,
										"Zoom" =>5,
									  ),
									  "Gebiettyp" => "Support",
									  "NumEntries" => 0,
									  "MaxID" => 0,
									  "Aktiv" => true),
						"XX" => array("Reg" => "XX",
									  "JGList" => "XX",
									  "GdeList" => "",
									  "RegUp" => "AmtZH",
									  "Opt" => "XX",
									  "Dir" => "XX_Andere", 
									  "Text" => "XX-Andere",
									  "GroupAreaName" => "XX Andere Regionen",
									  "GroupAreaID" => "kmCKuVfSO", // from UAV Editor
									  "GeoMapSetting" => array(
										"East" =>2677473.70,
										"North" =>1231853.81,
										"Zoom" =>6,
									  ),
									  "Gebiettyp" => "Support",
									  "NumEntries" => 0,
									  "MaxID" => 0,
									  "Aktiv" => true),
						"TF" => array("Reg" => "TF",
									  "JGList" => "TF",
									  "GdeList" => "",
									  "RegUp" => "XX",
									  "Opt" => "TF",
									  "Dir" => "TF_Testfelder", 
									  "Text" => "TF-Testfelder",
									  "GroupAreaName" => "TF Testfelder",
									  "GroupAreaID" => "PjjD3kIGU", // from UAV Editor
									  "GeoMapSetting" => array(
										"East" =>2676946.18,
										"North" =>1230656.46,
										"Zoom" =>8,
									  ),
									  "Gebiettyp" => "Support",
									  "NumEntries" => 0,
									  "MaxID" => 0,
									  "Aktiv" => true),
						"NO" => array("Reg" => "leer",
									  "JGList" => "",
									  "GdeList" => "",
									  "RegUp" => "",
									  "Dir" => "", 
									  "Text" => "Leere Karte",
									  "GeoMapSetting" => array(
										   //"East" =>2677473.70,
										   //"North" =>1231853.81,
										   //"Zoom" =>6,
										  "East" =>2680313.00,
										  "North" =>1229260.00,
										  "Zoom" =>4.5,
									  ),
									  "Gebiettyp" => "Support",
									  "NumEntries" => 0,
									  "MaxID" => 0,
									  "Aktiv" => false),

						"ALL" => array("Reg" => "ALL",
									  "JGList" => "21,24,25,26,27,28,AZ,AG,136,148,ZG,ZG1,ZG2,ZG3,ZG4,ZG5,ZG6,XX,TF",
									  "GdeList" => "",
									  "RegUp" => "0",
									  "Dir" => "",
									  "Text" => "Alle Flugpläne",
									  "GeoMapSetting" => array(
										   //"East" =>2677473.70,
										   //"North" =>1231853.81,
										   //"Zoom" =>6,
										  "East" =>2680313.00,
										  "North" =>1229260.00,
										  "Zoom" =>4.5,
									  ),
									  "Gebiettyp" => "Support",
									  "NumEntries" => 0,
									  "MaxID" => 0,
									  "Aktiv" => false),
									  );

	if (function_exists('rkrk_config_apply_regions')) {
		rkrk_config_apply_regions($RegionSettings);
	}
	if (function_exists('rkrk_regional_seed_json') && !empty($xmlMissionLocation)) {
		$regXml = $xmlMissionLocation . 'rkrk_regional_settings.xml';
		$regJson = function_exists('rkrk_catalog_json_path') ? rkrk_catalog_json_path($regXml) : '';
		if ($regJson === '' || !is_file($regJson)) {
			rkrk_regional_seed_json($regXml, $RegionSettings);
		}
	}
		$OptionRegUp = array(
									"AmtZH" => array(	"Opt" => "AmtZH",
													"Text" => "Amt Zürich, ZH"),
									"ZG" => array( 	"Opt" => "ZG",
													"Text" => "Kanton Zug, ZG"),
									"AG" => array( 	"Opt" => "AG",
													"Text" => "Oberfreiamt, AG"),
									"XX" => array(	"Opt" => "XX",
													"Text" => "Andere Regionen"),
								);
		$OptionPilot = array(
									"TC" => array(	"Opt" => "TC",
													"Text" => "Thomas Christen",
													//"###Access" => "test, intern, partner, extern, pub",
													"Access" => "test,intern,partner,extern,pub",
													"VisibleOn" => "test,intern,partner,extern",
													"PV" => "tc-mxmi,tchr-mxmi,tchr-hwsr",
													"plan" => true),
									"CH" => array( 	"Opt" => "CH",
													"Text" => "Chris Häfliger",
													"Access" => "intern,partner,extern,pub",
													"VisibleOn" => "test,intern,partner,extern",
													"PV" => "ch-sk7q,chae-sk7q,ch-x1z6",
													"plan" => true),
									"VP" => array( 	"Opt" => "VP",
													"Text" => "Vesa Pajasmaa",
													"Access" => "partner,extern,pub",
													"VisibleOn" => "test,intern,partner,extern",
													"PV" => "vp-84m6",
													"plan" => true),
									"LZ" => array(	"Opt" => "LZ",
													"Text" => "Lucas Zingerli",
													"Access" => "partner,extern,pub",
													"VisibleOn" => "test,intern,partner,extern",
													"PV" => "",
													"plan" => false,
													"active" => false,
													"until" => "2022-12-31"),
									"MB" => array(	"Opt" => "MB",
													"Text" => "Marc Blunier",
													"Access" => "partner,extern,pub",
													"VisibleOn" => "test,intern,partner,extern",
													"PV" => "mb-4lv7",
													"plan" => true),
									"SB" => array(	"Opt" => "SB",
													"Text" => "Sarina Blunier",
													"Access" => "partner,extern,pub",
													"VisibleOn" => "test,intern,partner,extern",
													"PV" => "sb-r7xa",
													"plan" => false),
									"LF" => array(	"Opt" => "LF",
													"Text" => "Lars Freimann",
													"Access" => "extern,pub",
													"VisibleOn" => "test,intern,partner,extern",
													"PV" => "lf-wu6w",
													"plan" => true),
									"MF" => array(	"Opt" => "MF",
													"Text" => "Marina Freimann",
													"Access" => "extern,pub",
													"VisibleOn" => "test,intern,partner,extern",
													"PV" => "mf-wl73s",
													"plan" => true),
									"AC" => array(	"Opt" => "AC",
													"Text" => "Armon Christen",
													"Access" => "partner,extern,pub",
													"VisibleOn" => "test,intern,partner,extern",
													"PV" => "ac-2vsi",
													"plan" => true),
									"NC" => array(	"Opt" => "NC",
													"Text" => "Nando Christen",
													"Access" => "partner,extern,pub",
													"VisibleOn" => "test,intern,partner,extern",
													"PV" => "nc-i6x0",
													"plan" => true),
									"TZ" => array(	"Opt" => "TZ",
													"Text" => "Toni Zürcher",
													"Access" => "partner,extern,pub",
													"VisibleOn" => "test,intern,extern",
													"PV" => "",
													"plan" => false,
													"active" => true,
													"until" => "2025-12-31"),
									"AZ" => array(	"Opt" => "AZ",
													"Text" => "Adrian Zender",
													"Access" => "extern,pub",
													"VisibleOn" => "test,intern,partner,extern",
													"PV" => "",
													"plan" => false),
									"EX" => array(	"Opt" => "EX",
													"Text" => "Externe Piloten",
													"Access" => "extern,pub",
													"VisibleOn" => "test,intern,partner,extern",
													"PV" => "",
													"plan" => false),
								);


	if (function_exists('rkrk_config_apply_pilots')) {
		rkrk_config_apply_pilots($OptionPilot);
	}

	if (!function_exists('rkrk_pilot_normalize')) {
		function rkrk_pilot_normalize($pilot, $code = '') {
			if (!is_array($pilot)) {
				$pilot = array();
			}
			$opt = strtoupper(trim($code !== '' ? (string)$code : strval($pilot['Opt'] ?? '')));
			$pilot['Opt'] = $opt;
			$pilot['Text'] = strval($pilot['Text'] ?? '');
			$pilot['Access'] = strval($pilot['Access'] ?? '');
			$pilot['VisibleOn'] = strval($pilot['VisibleOn'] ?? '');
			$pilot['PV'] = strval($pilot['PV'] ?? '');
			$pilot['plan'] = !empty($pilot['plan']);
			if (!array_key_exists('active', $pilot)) {
				$pilot['active'] = true;
			} else {
				$v = $pilot['active'];
				$pilot['active'] = !($v === false || $v === 0 || $v === '0' || $v === 'false' || $v === '');
			}
			$until = trim(strval($pilot['until'] ?? ''));
			if ($until !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $until)) {
				$until = '';
			}
			$pilot['until'] = $until;
			return $pilot;
		}
	}

	if (!function_exists('rkrk_pilot_is_selectable')) {
		function rkrk_pilot_is_selectable($pilot, $date = null, $currentValue = '', $mode = 'assign') {
			if (!is_array($pilot)) {
				return false;
			}
			$pilot = rkrk_pilot_normalize($pilot);
			$code = $pilot['Opt'];
			if ($code === '') {
				return false;
			}
			if ($mode === 'filter') {
				return true;
			}
			$current = trim(strval($currentValue));
			if ($current !== '' && strcasecmp($current, $code) === 0) {
				return true;
			}
			if (!$pilot['active']) {
				return false;
			}
			if ($pilot['until'] === '') {
				return true;
			}
			$checkDate = ($date === null || $date === '') ? date('Y-m-d') : strval($date);
			return $checkDate <= $pilot['until'];
		}
	}

	if (!function_exists('rkrk_pilot_filter_label')) {
		function rkrk_pilot_filter_label($pilot) {
			$pilot = rkrk_pilot_normalize($pilot);
			if (rkrk_pilot_is_selectable($pilot, date('Y-m-d'), '', 'assign')) {
				return $pilot['Opt'];
			}
			return $pilot['Opt'] . ' (ehemalig)';
		}
	}

	if (!function_exists('rkrk_pilot_options_for_assign')) {
		function rkrk_pilot_options_for_assign($date = null, $currentValue = '') {
			global $OptionPilot;
			$out = array();
			if (!is_array($OptionPilot)) {
				return $out;
			}
			foreach ($OptionPilot as $key => $pilot) {
				$pilot = rkrk_pilot_normalize($pilot, is_string($key) ? $key : '');
				if (rkrk_pilot_is_selectable($pilot, $date, $currentValue, 'assign')) {
					$out[$pilot['Opt']] = $pilot;
				}
			}
			return $out;
		}
	}

	if (!function_exists('rkrk_pilot_select_codes')) {
		function rkrk_pilot_select_codes($mode = 'assign', $date = null, $currentValue = '', $visibleOnOnly = false) {
			global $OptionPilot, $WebFreigabe;
			$codes = array('');
			if (!is_array($OptionPilot)) {
				return $codes;
			}
			foreach ($OptionPilot as $key => $pilot) {
				$pilot = rkrk_pilot_normalize($pilot, is_string($key) ? $key : '');
				$code = $pilot['Opt'];
				if ($code === '') {
					continue;
				}
				if ($visibleOnOnly) {
					$vis = strval($pilot['VisibleOn'] ?? '');
					$visible = (strpos(' ' . $vis, strval($WebFreigabe)) !== false);
					if (!$visible && strcasecmp(trim(strval($currentValue)), $code) !== 0) {
						continue;
					}
				}
				if (!rkrk_pilot_is_selectable($pilot, $date, $currentValue, $mode)) {
					continue;
				}
				$codes[] = $code;
			}
			return $codes;
		}
	}

	// ===========================================
	// Gobal Defintions
	// ===========================================

	$PersonenDaten = Array("tc-mxmi" => 
							Array(	"Name" => "Christen",
									"Vorname" => "Thomas",
									"Tel" => "+41 79 510 42 65",
									"EASA_ID" => "CHE-RP-5c18p294mxmi",
									"EASA_BID" => "CHEclsc784sbqhxi",
									"EASA_exp" => "30-05-2031",
									"Kat" => "A1/A3,A2",
									"Pic" => "tc_face2.jpg",
									"QR" => "tc_easa_qr.png",
									"RKRS_MA" => "tc_mitgliederausweis_585.pdf",
									"Flugberechtigung" => "FGHO_RKRK,LSXN,RKRS,Hue",
									"FlugzonenOption" => "FHGO-NF2",
										//FHGO-NF1: FHGO nur eine Nachflugzone
										//FHGO-NF2: FHGO nur zwei Nachflugzonen
										//FHGO-STP: FHGO gesperrt 
									"titel" => "a2",
									"country" => "ch",
									"verfied" => true,
											),
						 "tchr-mxmi" => 
							Array(	"Name" => "Christen",
									"Vorname" => "Thomas",
									"Tel" => "+41 79 510 42 65",
									"EASA_ID" => "CHE-RP-5c18p294mxmi",
									"EASA_BID" => "CHEclsc784sbqhxi",
									"EASA_exp" => "30-05-2031",
									"Kat" => "A1/A3,A2",
									"Pic" => "tc_face.jpg",
									"QR" => "tc_easa_qr.png",
									"RKRS_MA" => "tc_mitgliederausweis_585.pdf",
									"Flugberechtigung" => "FGHO_RKRK,LSXN,RKRS,Hue",
									"FlugzonenOption" => "FHGO-NF2",
									"titel" => "a2",
									"country" => "ch",
									"verfied" => true,
											),
						 "tchr-hwsr" => 
							Array(	"Name" => "Christen",
									"Vorname" => "Thomas",
									"Tel" => "+41 79 510 42 65",
									"EASA_ID" => "DEU-RP-e215o819hwsr",
									"EASA_BID" => "CHEclsc784sbqhxi",
									"EASA_exp" => "08-06-2031",
									"Kat" => "A1/A3,A2,STS",
									"Pic" => "tc_face.jpg",
									"QR" => "tc_easa_de_qr.png",
									"RKRS_MA" => "tc_mitgliederausweis_585.pdf",
									"Flugberechtigung" => "FGHO_RKRK,LSXN,RKRS,Hue",
									"FlugzonenOption" => "FHGO-NF2",
									"titel" => "sts-p",
									"STSAcc" => "Akkreditierungsnachweis_Christen.pdf",
									"country" => "de",
									"verfied" => true,
											),
						 "ch-sk7q" => 
							Array(	"Name" => "Häfliger",
									"Vorname" => "Christoph",
									"Tel" => "+41 77 406 13 30",
									"EASA_ID" => "CHE-RP-sj1cgmcmsk7q",
									"EASA_BID" => "CHE4t8bshnjazg0p",
									"EASA_exp" => "30-05-2031",
									"Kat" => "A1/A3,A2",
									"Pic" => "ch_face2.jpg",
									"QR" => "ch_easa_qr.png",
									"RKRS_MA" => "ch_mitgliederausweis_588.pdf",
									"Flugberechtigung" => "FGHO_RKRK,LSXN,RKRS,Hue",
									"FlugzonenOption" => "FHGO-NF2",
									"titel" => "a2",
									"country" => "ch",
									"verfied" => true,
											),
						 "chae-sk7q" => 
							Array(	"Name" => "Häfliger",
									"Vorname" => "Christoph",
									"Tel" => "+41 77 406 13 30",
									"EASA_ID" => "CHE-RP-sj1cgmcmsk7q",
									"EASA_BID" => "CHE4t8bshnjazg0p",
									"EASA_exp" => "30-05-2031",
									"Kat" => "A1/A3,A2",
									"Pic" => "ch_face.jpg",
									"QR" => "ch_easa_qr.png",
									"RKRS_MA" => "ch_mitgliederausweis_588.pdf",
									"Flugberechtigung" => "FGHO_RKRK,LSXN,RKRS,Hue",
									"FlugzonenOption" => "FHGO-NF2",
									"titel" => "a2",
									"country" => "ch",
									"verfied" => true,
											),
						 "ch-x1z6" => 
							Array(	"Name" => "Häfliger",
									"Vorname" => "Christoph",
									"Tel" => "+41 77 406 13 30",
									"EASA_ID" => "DEU-RP-cnkfz8abx1z6",
									"EASA_BID" => "CHE4t8bshnjazg0p",
									"EASA_exp" => "08-06-2031",
									"Kat" => "A1/A3,A2,STS",
									"Pic" => "ch_face2.jpg",
									"QR" => "ch_easa_de_qr.png",
									"RKRS_MA" => "ch_mitgliederausweis_588.pdf",
									"Flugberechtigung" => "FGHO_RKRK,LSXN,RKRS,Hue",
									"FlugzonenOption" => "FHGO-NF2",
									"titel" => "sts-p",
									"STSAcc" => "Akkreditierungsnachweis_Haefliger.pdf",
									"country" => "de",
									"verfied" => true,
											),
						 "vp-84m6" => 
							Array(	"Name" => "Pajasmaa",
									"Vorname" => "Vesa",
									"Tel" => "+41 79 335 69 59",
									"EASA_ID" => "CHE-RP-1mv7ln4z84m6",
									"EASA_BID" => "CHE4t8bshnjazg0p",
									"EASA_exp" => "22-08-2026",
									"Kat" => "A1/A3,A2",
									"Pic" => "vp_face.jpg",
									"QR" => "vp_easa_qr.png",
									"RKRS_MA" => "vp_mitgliederausweis_596.pdf",
									"Flugberechtigung" => "FGHO_RKRK,LSXN,RKRS,Hue",
									"FlugzonenOption" => "FHGO-NF2",
									"titel" => "a2",
									"country" => "ch",
									"verfied" => true,
											),
						 "ac-2vsi" => 
							Array(	"Name" => "Christen",
									"Vorname" => "Armon",
									"Tel" => "",
									"EASA_ID" => "CHE-RP-an16veys2vsi",
									"EASA_BID" => "CHEuhzw0v4ceg5c1",
									"EASA_exp" => "28-12-2027",
									"Kat" => "A1/A3,A2",
									"Pic" => "ac_face.jpg",
									"QR" => "ac_easa_qr.png",
									"RKRS_MA" => "ac_mitgliederausweis_2227.pdf",
									"Flugberechtigung" => "FGHO_RKRK,RKRS,LSXN",
									"FlugzonenOption" => "FHGO-NF2",
									"titel" => "a2",
									"country" => "ch",
									"verfied" => true,
											),
						 "nc-i6x0" => 
							Array(	"Name" => "Christen",
									"Vorname" => "Nando",
									"Tel" => "",
									"EASA_ID" => "CHE-RP-uu11zl8ti6x0",
									"EASA_BID" => "CHEozble8brdtq15",
									"EASA_exp" => "28-12-2027",
									"Kat" => "A1/A3,A2",
									"Pic" => "nc_face.jpg",
									"QR" => "nc_easa_qr.png",
									"RKRS_MA" => "nc_mitgliederausweis_2226.pdf",
									"Flugberechtigung" => "FGHO_RKRK,RKRS,LSXN",
									"FlugzonenOption" => "FFHGO-NF2",
									"titel" => "a2",
									"country" => "ch",
									"verfied" => true,
											),
						 "mb-4lv7" => 
							Array(	"Name" => "Blunier",
									"Vorname" => "Marc",
									"Tel" => "",
									"EASA_ID" => "CHE-RP-8xf7fw7x4lv7",
									"EASA_BID" => "",
									"EASA_exp" => "09-05-2027",
									"Kat" => "A1/A3",
									"Pic" => "mb_face.jpg",
									"QR" => "mb_easa_qr.png",
									"RKRS_MA" => "mb_mitgliederausweis_1081.pdf",
									"Flugberechtigung" => "FGHO_RKRK,RKRS,LSXN",
									"FlugzonenOption" => "FHGO-NF1",
									"titel" => "a2",
									"country" => "ch",
									"verfied" => true,
											),
						 "sb-r7xa" => 
							Array(	"Name" => "Blunier",
									"Vorname" => "Sarina",
									"Tel" => "",
									"EASA_ID" => "CHE-RP-sj283n7kr7xa",
									"EASA_BID" => "",
									"EASA_exp" => "19-01-2028",
									"Kat" => "A1/A3",
									"Pic" => "sb_face.jpg",
									"QR" => "sb_easa_qr.png",
									"RKRS_MA" => "sb_mitgliederausweis_1791.pdf",
									"Flugberechtigung" => "FGHO_RKRK,RKRS,Hue",
									"FlugzonenOption" => "FHGO-NF2",
									"titel" => "a2",
									"country" => "ch",
									"verfied" => true,
											),
						 "lf-wu6w" => 
							Array(	"Name" => "Freimann",
									"Vorname" => "Lars",
									"Tel" => "",
									"EASA_ID" => "CHE-RP-krpc8ofhwu6w",
									"EASA_BID" => "",
									"EASA_exp" => "08.05.2030",
									"Kat" => "A1/A3,A2",
									"Pic" => "lf_face.jpg",
									"QR" => "lf_easa_qr.png",
									"RKRS_MA" => "lf_mitgliederausweis_1734.pdf",
									"Flugberechtigung" => "FGHO_RKRK,RKRS,LSXN,Hue",
									"FlugzonenOption" => "FHGO-NF2",
									"titel" => "a2",
									"country" => "ch",
									"verfied" => true,
											),
						 "mf-wl73s" => 
							Array(	"Name" => "Freimann",
									"Vorname" => "Marina",
									"Tel" => "",
									"EASA_ID" => "CHE-RP-xo3hcvjwl73s",
									"EASA_BID" => "",
									"EASA_exp" => "20.08.2028",
									"Kat" => "A1/A3",
									"Pic" => "mf_face.jpg",
									"QR" => "mf_easa_qr.png",
									"RKRS_MA" => "mf_mitgliederausweis_1735.pdf",
									"Flugberechtigung" => "FGHO_RKRK,RKRS,Hue",
									"FlugzonenOption" => "FHGO-NF2",
									"titel" => "a2",
									"country" => "ch",
									"verfied" => true,
											),
						 );


	if (function_exists('rkrk_config_apply_personen')) {
		rkrk_config_apply_personen($PersonenDaten);
	}

		$OptionFlughoehe = array(
									"20" => array(	"Opt" => "20",
													"Text" => "20 Meter"),
									"25" => array(	"Opt" => "25",
													"Text" => "25 Meter"),
									"30" => array(	"Opt" => "30",
													"Text" => "30 Meter"),
									"35" => array(	"Opt" => "35",
													"Text" => "35 Meter"),
									"40" => array(	"Opt" => "40",
													"Text" => "40 Meter"),
									"45" => array(	"Opt" => "45",
													"Text" => "45 Meter"),
									"50" => array(	"Opt" => "50",
													"Text" => "50 Meter"),
									"55" => array(	"Opt" => "55",
													"Text" => "55 Meter"),
									"60" => array(	"Opt" => "60",
													"Text" => "60 Meter"),
									"65" => array(	"Opt" => "65",
													"Text" => "65 Meter"),
									"70" => array(	"Opt" => "70",
													"Text" => "70 Meter"),
									"75" => array(	"Opt" => "75",
													"Text" => "75 Meter"),
									"80" => array(	"Opt" => "80",
													"Text" => "80 Meter"),
									"85" => array(	"Opt" => "85",
													"Text" => "85 Meter"),
									"90" => array(	"Opt" => "90",
													"Text" => "90 Meter"),
									"95" => array(	"Opt" => "95",
													"Text" => "95 Meter"),
									"100" => array(	"Opt" => "100",
													"Text" => "100 Meter"),
									"110" => array(	"Opt" => "110",
													"Text" => "110 Meter"),
									"120" => array(	"Opt" => "120",
													"Text" => "120 Meter"),
								);

$MaxFlightHeight = 120;

		$OptionFrei = array(
									"OK" => array(	"Opt" => "OK",
													"Text" => "Flugplan geprüft und freigegeben",
													"PdfPic" => "/ok.jpg",
													"Pic" => "/ok.png"),
									"TFL" => array( "Opt" => "TFL",
												 "Text" => "Testflug nötig/empfohlen",
													"PdfPic" => "/drone.jpg",
													"Pic" => "/drone.png"),
									"PRF" => array( "Opt" => "PRF",
													"Text" => "Flugplan muss geprüft werden",
													"PdfPic" => "/pruefen.jpg",
													"Pic" => "/pruefen.png"),
									"NO" => array(	"Opt" => "NO",
													"Text" => "Flugplan nicht verwenden!",
													"PdfPic" => "/gesperrt.jpg",
													"Pic" => "/gesperrt.png"),
									"NEU" => array(	"Opt" => "NEU",
													"Text" => "Neues Feld",
													"PdfPic" => "/neues_feld.jpg",
													"Pic" => "/neues_feld.png"),
								);
								

		$OptionWarning = array( 
									"CTR" => array( "Opt" => "CTR",
													"Text" => "5km Zone",
													"PdfPic" => "/airplane.jpg",
													"Pic" => "/airplane.png",
													"PicSmall" => "/airplane_35.png"),
									//Ab 2024 ersetzt miT NGT1 und NGT2
									//"NGT" => array( "Opt" => "NGT",
									//				"Text" => "Nachtflug 2200h - 0600h",
									//				"PdfPic" => "/nacht.jpg",
									//				"Pic" => "/nacht.png"),
									"NGT1" => array( "Opt" => "NGT1",
													"Text" => "Nachtflug 2200h - 0600h",
													"PdfPic" => "/nacht.jpg",
													"Pic" => "/nacht.png",
													"PicSmall" => "/nacht_35.png"),
									"NGT2" => array( "Opt" => "NGT2",
													"Text" => "Nachtflug 2200h - 0800h",
													"PdfPic" => "/daemmerung.jpg",
													"Pic" => "/daemmerung.png",
													"PicSmall" => "/daemmerung_35.png"),
									"NGT3" => array( "Opt" => "NGT3",
													"Text" => "Nachtflug 0500h - 0900h",
													"PdfPic" => "/daemmerung.jpg",
													"Pic" => "/daemmerung.png",
													"PicSmall" => "/daemmerung_35.png"),
									"STP" => array( "Opt" => "STP",
													"Text" => "Flugverbotszone",
													"PdfPic" => "/pruefen.jpg",
													"Pic" => "/pruefen.png",
													"PicSmall" => "/pruefen_35.png"),
									"OBJ" => array( "Opt" => "OBJ",
													"Text" => "Objekt im Flugfeld!",
													"PdfPic" => "/objekt.jpg",
													"Pic" => "/objekt.png",
													"PicSmall" => "/objekt_35.png"),
									"GEL" => array( "Opt" => "GEL",
													"Text" => "Kritisches Gelände (Höhe)",
													"PdfPic" => "/berg.jpg",
													"Pic" => "/berg.png",
													"PicSmall" => "/berg_35.png"),
									"NSG" => array( "Opt" => "NSG",
													"Text" => "Naturschutzgebiet (keine Flugbewilligung erforderlich)",
													"PdfPic" => "/naturschutz1.jpg",
													"Pic" => "/naturschutz1.png",
													"PicSmall" => "/naturschutz1_35.png"),
									"NSF" => array( "Opt" => "NSF",
													"Text" => "Naturschutzgebiet (Flugbewilligung erforderlich)",
													"PdfPic" => "/naturschutz2.jpg",
													"Pic" => "/naturschutz2.png",
													"PicSmall" => "/naturschutz2_35.png"),
									"FVB" => array( "Opt" => "FVB",
													"Text" => "Kantonales Flugverbot",
													"PdfPic" => "/flugverbot.jpg",
													"Pic" => "/flugverbot.png",
													"PicSmall" => "/flugverbot_35.png"),
									"USP" => array( "Opt" => "USP",
													"Text" => "Unterspannwerk (Flugbewilligung für Mitglieder Rehkitzrettung ausserhalb der Abschrankung)",
													"PdfPic" => "/Flugeinschraenkung.jpg",
													"Pic" => "/Flugeinschraenkung.png",
													"PicSmall" => "/Flugeinschraenkung_35.png"),
									"SORA" => array( "Opt" => "SORA",
													"Text" => "Flugfeld Nahe einer Siedlung (<150m)",
													"TextExt" => "SORA (Specific Operations Risk Analysis) nötig",
													"PdfPic" => "/house.jpg",
													"Pic" => "/house.png",
													"PicSmall" => "/house_35.png"),
									"SUN" => array( "Opt" => "SUN",
													"Text" => "Feld mit starker Sonneneinstrahlung am Morgen",
													"PdfPic" => "/risingsun.jpg",
													"Pic" => "/risingsun.png",
													"PicSmall" => "/risingsun_35.png"),
								);


		$OptionGemeinde= array( 
									"AaA" => array( "Opt" => "AaA",
													"Kml" => "Affoltern am Albis",
													"Text" => "Affoltern am Albis (ZH)"),
									"AB" => array( "Opt" => "AB",
													"Kml" => "Abtwil",
													"Text" => "Abtwil (AG)"),
									"AeU" => array( "Opt" => "AeU",
													"Kml" => "Aeugst am Albis",
													"Text" => "Aeugst am Albis (ZH)"),
									"HaA" => array( "Opt" => "HaA",
													"Kml" => "Hausen am Albis",
													"Text" => "Hausen am Albis (ZH)"),
									"KN" => array( 	"Opt" => "KN",
													"Kml" => "Knonau",
													"Text" => "Knonau (ZH)"),
									"KaA" => array( "Opt" => "KaA",
													"Kml" => "Kappel am Albis",
													"Text" => "Kappel am Albis (ZH)"),
									"LaA" => array( "Opt" => "LaA",
													"Kml" => "Langnau am Albis",
													"Text" => "Langnau am Albis (ZH)"),
									"MA" => array( 	"Opt" => "MA",
													"Kml" => "Maschwanden",
													"Text" => "Maschwanden (ZH)"),
									"MET" => array( 	"Opt" => "MET",
													"Kml" => "Mettmenstetten",
													"Text" => "Mettmenstetten (ZH)"),
									"OB" => array( 	"Opt" => "OB",
													"Kml" => "Obfelden",
													"Text" => "Obfelden (ZH)"),
									"OR" => array( 	"Opt" => "OR",
													"Kml" => "Oberrüti",
													"Text" => "Oberrüti (AG)"),
									"RIF" => array( 	"Opt" => "RIF",
													"Kml" => "Rifferswil",
													"Text" => "Rifferswil (ZH)"),
									"SI" => array( 	"Opt" => "SI",
													"Kml" => "Sins",
													"Text" => "Sins (AG)"),
									"DIE" => array( 	"Opt" => "DIE",
													"Kml" => "Dietwil",
													"Text" => "Dietwil (AG)"),
									"MUE" => array( 	"Opt" => "MUE",
													"Kml" => "Mühlau",
													"Text" => "Mühlau (AG)"),
									"BEI" => array( 	"Opt" => "BEI",
													"Kml" => "Beinwil",
													"Text" => "Beinwil (Freiamt)"),
									"AUW" => array( 	"Opt" => "AUW",
													"Kml" => "Auw",
													"Text" => "Auw (AG)"),
									"BAA" => array( "Opt" => "BAA",
													"Kml" => "Baar",
													"Text" => "Baar (ZG)"),
									"STE" => array( "Opt" => "STE",
													"Kml" => "Steinhausen",
													"Text" => "Steinhausen (ZG)"),
									"CHA" => array( "Opt" => "CHA",
													"Kml" => "Cham",
													"Text" => "Cham (ZG)"),
									"ZG" => array( 	"Opt" => "ZG",
													"Kml" => "Kanton Zug",
													"Text" => "Kanton Zug"),
									"RUE" => array( "Opt" => "RUE",
													"Kml" => "Rüschlikon",
													"Text" => "Rüschlikon"),
									"HUE" => array( "Opt" => "HUE",
													"Kml" => "Hünenberg",
													"Text" => "Hünenberg (ZG)"),
									"ZUG" => array( "Opt" => "ZUG",
													"Kml" => "Zug",
													"Text" => "Zug (ZG)"),
									"NEU" => array( "Opt" => "NEU",
													"Kml" => "Neuheim",
													"Text" => "Neuheim (ZG)"),
									"UAE" => array( "Opt" => "UAE",
													"Kml" => "Unterägeri",
													"Text" => "Unterägeri (ZG)"),
									"RIS" => array( "Opt" => "RIS",
													"Kml" => "Risch",
													"Text" => "Risch (ZG)"),
									"WAL" => array( "Opt" => "WAL",
													"Kml" => "Walchwil",
													"Text" => "Walchwil (ZG)"),
									"MEN" => array( "Opt" => "MEN",
													"Kml" => "Menzingen",
													"Text" => "Menzingen (ZG)"),
									"OAE" => array( "Opt" => "OAE",
													"Kml" => "Oberägeri",
													"Text" => "Oberägeri (ZG)"),
									"STA" => array( "Opt" => "STA",
													"Kml" => "Stallikon",
													"Text" => "Stallikon (ZH)"),
									"THA" => array( "Opt" => "THA",
													"Kml" => "Thalwil",
													"Text" => "Thalwil"),
/*									"BIR" => array( "Opt" => "BIR",
													"Kml" => "Birmensdorf (ZH)",
													"Text" => "Birmensdorf (ZH)"),
									"OTT" => array( "Opt" => "OTT",
													"Kml" => "Ottenbach",
													"Text" => "Ottenbach"),
									"OBE" => array( "Opt" => "OBE",
													"Kml" => "Oberrieden",
													"Text" => "Oberrieden"),
									"AES" => array( "Opt" => "AES",
													"Kml" => "Aesch (ZH)",
													"Text" => "Aesch (ZH)"),
									"HED" => array( "Opt" => "HED",
													"Kml" => "Hedingen",
													"Text" => "Hedingen"),
									"ADL" => array( "Opt" => "ADL",
													"Kml" => "Adliswil",
													"Text" => "Adliswil"),
									"KIL" => array( "Opt" => "KIL",
													"Kml" => "Kilchberg (ZH)",
													"Text" => "Kilchberg (ZH)"),
									"RUE" => array( "Opt" => "RUE",
													"Kml" => "Rüschlikon",
													"Text" => "Rüschlikon"),
									"WET" => array( "Opt" => "WET",
													"Kml" => "Wettswil am Albis",
													"Text" => "Wettswil am Albis"),
									"BON" => array( "Opt" => "BON",
													"Kml" => "Bonstetten",
													"Text" => "Bonstetten"),
									"UIT" => array( "Opt" => "UIT",
													"Kml" => "Uitikon",
													"Text" => "Uitikon"),
									"HOR" => array( "Opt" => "HOR",
													"Kml" => "Horgen",
													"Text" => "Horgen"),
*/
									"MEI" => array( "Opt" => "MEI",
													"Kml" => "Meierskappel",
													"Text" => "Meierskappel"),
									"HON" => array( "Opt" => "HON",
													"Kml" => "Honau",
													"Text" => "Honau"),
									"GIS" => array( "Opt" => "GIS",
													"Kml" => "Gisikon",
													"Text" => "Gisikon"),
									"ROO" => array( "Opt" => "ROO",
													"Kml" => "Root",
													"Text" => "Root"),
									);
		array_multisort($OptionGemeinde, SORT_ASC);


		$AvailablityOptions = Array( 
							"" => Array ("typ" => "",
											"text" => "",
											"Opt" => "",
											"slack" => ":stop_sign:"),
							"stop" => Array ("typ" => "stop",
											"text" => "nicht verfügbar",
											"Opt" => "\u{1F6D1}",
											"slack" => ":stop_sign:"),
							"question" => Array ("typ" => "question",
											"text" => "ev. verfügbar",
											"Opt" => "\u{2753}",
											"slack" => ":question:"),
							"deer" => Array ("typ" => "deer",
											"text" => "verfügbar",
											"Opt" => "	\u{1F98C}",
											"slack" => ":deer:")
							);


		$WeatherOptions = Array( 
							"" => Array ("type" => "",
											"text" => "",
											"Opt" => "",
											"slack" => ""),
							"sun" => Array ("type" => "sun",
											"text" => "Sonne",
											"Opt" => "\u{2600}\u{FE0F}",
											"slack" => ":sunny:"),
							"sun_small_cloud" => Array ("type" => "sun_small_cloud",
											"text" => "leicht bewölkt",
											"Opt" => "\u{1F324}\u{FE0F}",
											"slack" => ":white_sun_small_cloud:"),
							"sun_behind_cloud" => Array ("type" => "sun_behind_cloud",
											"text" => "teilweise sonnig",
											"Opt" => "\u{26C5}",
											"slack" => ":sun_behind_large_cloud:"),
							"sun_large_cloud" => Array ("type" => "sun_large_cloud",
											"text" => "stark bewölkt",
											"Opt" => "\u{1F325}\u{FE0F}",
											"slack" => ":sun_behind_large_cloud:"),
							"cloud" => Array ("type" => "cloud",
											"text" => "bewölkt",
											"Opt" => "\u{2601}\u{FE0F}",
											"slack" => ":cloud:"),
							"sun_behind_rain_cloud" => Array ("type" => "sun_behind_rain_cloud",
											"text" => "wechselhaft",
											"Opt" => "\u{1F326}\u{FE0F}",
											"slack" => ":sun_behind_rain_cloud:"),
							"rain" => Array ("type" => "rain",
											"text" => "Regen",
											"Opt" => "\u{1F327}\u{FE0F}",
											"slack" => ":cloud_rain:"),
							"thunderstorm" => Array ("type" => "thunderstorm",
											"text" => "Gewitter und Regen",
											"Opt" => "\u{26C8}\u{FE0F}",
											"slack" => ":thunder_cloud_rain:"),
							"lightning" => Array ("type" => "thunder",
											"text" => "Gewitter",
											"Opt" => "\u{1F329}\u{FE0F}",
											"slack" => ":thunder_cloud_rain:"),
							"fog" => Array ("type" => "fog",
											"text" => "Nebel",
											"Opt" => "\u{1F32B}\u{FE0F}",
											"slack" => ":foggy:"),
							"snow" => Array ("type" => "snow",
											"text" => "Schnee",
											"Opt" => "\u{1F328}\u{FE0F}",
											"slack" => ":cloud_snow:")
						);

	$AccessControl = Array (
			"DebugDataBase" => Array (
						"title" => "DebugDataBase",
						"release" => "test",
						),
			"DeleteData" => Array (
						"title" => "DeleteData",
						"release" => "test, intern, partner",
						),
			"Geofence" => Array (
						"title" => "Geofence",
						"release" => "test",
						),
			"InternPilots" => Array (
						"title" => "InternPilots",
						"release" => "test, intern",
						),
			"PilotPartners" => Array (
						"title" => "QRCode",
						"release" => "test, intern, partner",
						),
			"AllPilots" => Array (
						"title" => "AllPilots",
						"release" => "test, intern, partner, extern",
						),
			"FlightTableMode" => Array (
						"title" => "FlightTableMode",
						"release" => "test, intern, partner",
						),
			"NewField" => Array (
						"title" => "NewField",
						"release" => "test, intern, partner",
						),
			"Convert" => Array (
						"title" => "NewField",
						"release" => "test, intern",
						),
			"InternLinks" => Array (
						"title" => "InternLinks",
						"release" => "test, intern",
						),
			"TestOnly" => Array (
						"title" => "TestOnly",
						"release" => "test",
						),
			"EditFields" => Array (
						"title" => "EditFields",
						"release" => "test, intern, partner",
						),
			"BookFlight" => Array (
						"title" => "BookFlight",
						"release" => "test, intern, partner",
						),
			"GoogleMapLocation" => Array (
						"title" => "GoogleMapLocation",
						"release" => "test, intern, partner",
						),
			"MapDownloadLitchi" => Array (
						"title" => "MapDownloadLitchi",
						"release" => "test, intern, partner",
						),
			"MapDownloadKML" => Array (
						"title" => "MapDownloadKML",
						"release" => "test, intern, partner",
						),
			"MapDownloadKMZ" => Array (
						"title" => "MapDownloadKMZ",
						"release" => "test, intern, partner",
						),
			"MapDownloadJSON" => Array (
						"title" => "MapDownloadJSON",
						"release" => "test, intern, partner, extern",
						),
			"FlightStatistics" => Array (
						"title" => "FlightStatistics",
						"release" => "test, intern, partner",
						),
			"EditFiles" => Array (
						"title" => "EditFiles",
						"release" => "test, intern, partner",
						),
			"ExtendedMap" => Array (
						"title" => "ExtendedMapView",
						"release" => "test, intern, partner, extern",
						),
			"QRCode" => Array (
						"title" => "QRCode",
						"release" => "test, intern, partner",
						),
			"InternalQRCodes" => Array (
						"title" => "Interne QR Codes",
						"release" => "test, intern, partner",
						),
			"PilotSelInStats" => Array (
						"title" => "Piloten selekion in Statistik",
						"release" => "test, intern, partner",
						),
	);



		$FlyFileSetting = array(
		

/*							"SuchfeldBild" => array(	
												"Type" => "SuchfeldBild",
												"Text" => "Suchfeld Bild(er)",
												"Picture" => true,
												//"allowed" => array('jpg','jpeg', 'png'),
												"allowed" => array('jpg','jpeg'),
												//"allowedList" => "image/png, image/jpeg, image/jpg",
												"allowedList" => "image/jpeg, image/jpg",
												"XMLLabel" => "SuchfeldBild",
												"KatField" => "jpg",
												"FileVar" => "SuchfeldBild_File",
												"HightVar" => "SuchfeldBild_Flughoehe",
												"Show" => "edit_felddaten",
												"Access" => "",
												),
*/

							"SuchfeldBild" => array(	
												"Type" => "SuchfeldBild",
												"Text" => "Suchfeld Bild(er)",
												"Picture" => true,
												//"allowed" => array('jpg','jpeg', 'png'),
												"allowed" => array('jpg','jpeg'),
												//"allowedList" => "image/png, image/jpeg, image/jpg",
												"allowedList" => "image/jpeg, image/jpg",
												"XMLLabel" => "SuchfeldBild",
												"KatField" => "jpg",
												"FileVar" => "SuchfeldBild_File",
												"HightVar" => "SuchfeldBild_Flughoehe",
												"Show" => "edit_felddaten",
												"Access" => "",
												),
							"FlugplanBild" => array(	
												"Type" => "FlugplanBild",
												"Text" => "Flugplan Bild(er)",
												"Picture" => true,
												//"allowed" => array('jpg','jpeg', 'png'),
												"allowed" => array('jpg','jpeg'),
												//"allowedList" => "image/png, image/jpeg, image/jpg",
												"allowedList" => "image/jpeg, image/jpg",
												"XMLLabel" => "FlugplanBild",
												"KatField" => "jpg",
												"FileVar" => "FlugplanBild_File",
												"HightVar" => "FlugplanBild_Flughoehe",
												"Show" => "edit_felddaten",
												"Access" => "",
												),
							"JSON" => array(	
												"Type" => "JSON",
												"Text" => "JSON Datei(en)",
												"Picture" => false,
												"icon" => "json",
												"allowed" => array('json'),
												"allowedList" => "application/json",
												"XMLLabel" => "JasonFile",
												"KatField" => "json",
												"FileVar" => "JSON_File",
												"HightVar" => "JSON_Flughoehe",
												"Show" => "edit_felddatenliste,edit_felddaten,felderliste,felddatenliste,map,fluege",
												"Access" => "MapDownloadJSON",
												),
							"KML" => array(	
												"Type" => "KML",
												"Text" => "KML Datei(en)",
												"Picture" => false,
												"icon" => "kml",
												"allowed" => array('kml'),
												"allowedList" => ".kml",
												"XMLLabel" => "KMLFile",
												"KatField" => "kml",
												"FileVar" => "KML_File",
												"HightVar" => "KML_Flughoehe",
												"Show" => "edit_felddaten,felddatenliste,map",
												"Access" => "MapDownloadKML",
												),
							"KMZ" => array(	
												"Type" => "KMZ (Pilot2/M3T)",
												"Text" => "KMZ (Pilot2/M3T) Datei(en)",
												"Picture" => false,
												"icon" => "kmz",
												"allowed" => array('kmz'),
												"allowedList" => ".kmz",
												"XMLLabel" => "KMZFile",
												"KatField" => "kmz",
												"FileVar" => "KMZ_File",
												"HightVar" => "KMZ_Flughoehe",
												"Show" => "edit_felddatenliste,edit_felddaten,felderliste,felddatenliste,map,fluege",
												"Access" => "MapDownloadKMZ",
												),
							"LCHM" => array(	
												"Type" => "LCHM",
												"Text" => "LCHM Datei(en)",
												"Picture" => false,
												"icon" => "litchi",
												"allowed" => array('lchm'),
												"allowedList" => ".lchm",
												"XMLLabel" => "LichtiFile",
												"KatField" => "lchm",
												"FileVar" => "LCHM_File",
												"HightVar" => "LCHM_Flughoehe",
												"Show" => "edit_felddatenliste,edit_felddaten,felderliste,felddatenliste,map,fluege",
												"Access" => "MapDownloadLitchi",
											)
							);


	$DayList = array("", "Montag", "Dienstag", "Mittwoch", "Donnerstag", "Freitag", "Samstag", "Sonntag");
	$DayShortList = array("", "Mo", "Di", "Mi", "Do", "Fr", "Sa", "So");
	
	$MonthList = array(1=>"Januar",
                2=>"Februar",
                3=>"M&auml;rz",
                4=>"April",
                5=>"Mai",
                6=>"Juni",
                7=>"Juli",
                8=>"August",
                9=>"September",
                10=>"Oktober",
                11=>"November",
                12=>"Dezember");
	$MonthShortList = array(1=>"Jan",
                2=>"Feb",
                3=>"M&auml;r",
                4=>"Apr",
                5=>"Mai",
                6=>"Jun",
                7=>"Jul",
                8=>"Aug",
                9=>"Sep",
                10=>"Okt",
                11=>"Nov",
                12=>"Dez");

	$UAVLink = "https://rkrs.uaveditor.com/feldanmeldung";
	$RKRKRegionalSettingXML ='rkrk_regional_settings.xml';
	$RKRKContactFile = "Rehkitzrettung Konaueramt - Kontaktnummern.csv";
	$RKRK_LWB_ContactFile = "Rehkitzrettung Konaueramt - Landwirtschaftsbetriebe.csv";
	$RKRK_Users = "Rehitzrettung - Users.csv";
	
	$RKRKFelderfileXML = 'RKRKFelder.xml';
	$RKRKFelderfileCSV = 'RKRKFelder.csv';
	$RKRKWeatherCSV = '_RKRKPlan.csv';
	$RKRKFlightsCSV= "_RKRKFluege.csv";
	$RKRKSiteStatsCSV= "statisic.csv";
	$RKRKIPStatsCSV= "IPstatisic.csv";
	
	//$xmlsubfolder = "../test/";
	$xmlsubfolder = "";
	$poi_settings_file = 'rkrk_kmlfile_settings.xml';
	$xml_poifile = "rkrk_poidata.xml";
	$felder_file = 'RKRKFelder.xml';
	$pers_settings_file = 'pers_settings.xml';
	//$kmloutputfile = "rkrk_kml.kml";
	$kml_felderfile = "rkrk_kmlfelder.kml";
	$kmlFilelocations = "kmlfiles/";
	//$weblocation = "https://tchr-frog.synology.me:5681/rkropen/htmltest/";
	$weblocation=  dirname($ThisPage);
	$htmlfile = "rkrk_felder.php";
	$kmlReferenceFilelocations ="kmlfiles/referenzen/";
	$searchSD  = array('Ã¼', 'Ã¶', 'Ã?', 'Ã¤', 'Ã?Â¼', 'Â¼', 'Ã?Â¤', 'Ã¨', 'Ã©', 'Ãª', 'Ã«', 'Ã ', 'Ã¢', 'Ã§', 'Ã´', 'Ã¹', 'Ã»', 'Ã±', 'Ã¶', '%3F', 'Ã', 'Ã','Ã', 'Ã', 'ï¿½', '\/' ,'°', 'ÄÄÂÃÂ', 'ÄÄÂÃÂ¼r'); // '\n' ,
	$replaceSD = array('ü'	, 'ö' , 'Ü' , 'ä' , 'ü' , 'ü'   ,'ä'     , 'è' , 'é' , 'ê' , 'ë' , 'à' , 'â' , 'ç' , 'ô' , 'ù' , 'û' , 'ñ' , 'ö' , 'Ü' , 'Ü' , 'Ä'    ,'Ä'   , 'Ö'    , '?'  , '/','Grad', 'ä','ü');  // '; '  ,
	$searchSDZ  = array_merge(array('&'),$searchSD); // '\n' ,
	$replaceSDZ = array_merge(array('&#38;'),$replaceSD);  // '; '  ,
	$csv_desc = ';';
	$csv_desc_replace = '/';

	$searchUML  = $searchSDZ;
	$replaceUML = $replaceSDZ;
	$searchUML2  = array('ä', 'ö', 'ü', 'Ä', 'Ö', 'Ü');
	$replaceUML2 = array('a', 'o', 'u', 'A', 'O', 'U');
	$replaceUML21 = array('ae', 'oo', 'ue', 'Ae', 'Oe', 'Ue');

	$searchHTML = Array("<br>","<",">");
	$replaceHTML = Array("\r","&lt;","&gt");


    $StandardSettingMap = "&Cntr=off&Area=on&Pfad=on&GDE=off&JGG=off&Poi=off&Label=off&EX=NO&KA=S&MAP=&Zoom=1.0";
	$MapIntern = "&KA=S&Strt=on&Cntr=off&Area=on&Pfad=on&EX=NO&Poi=off&FZ=on&KA=S&MAP=1&Zoom=1.0";
	$MapExtern = "&KA=S&Strt=off&Cntr=on&Area=on&Pfad=off&Poi=off&KA=S&MAP=1&Zoom=1.0";
	$MapPersonal= "&KA=S&Strt=off&Cntr=off&Area=off&Pfad=off&WW=off&FZ=off&GDE=off&JGG=off&KT=off&ORT=off&Poi=on&HSP=off&Label=off&GeLa=off&Sun=off&EG=off&SORA=off&WF=off&EX=no&MAP=&FFilter=&Zoom=1.0";

	$FlugplanBildParameter = "&Reg=ALL&Strt=on&Cntr=off&Area=on&Pfad=on&GDE=off&JGG=off&Poi=off&HSP=on&FLP=on&Label=off&EX=NO&KA=M&MAP=1&Zoom=0.8";
	$SuchplanBildParameter = "&Reg=ALL&Strt=on&Cntr=off&Area=on&Pfad=off&GDE=off&JGG=off&Poi=off&HSP=on&OVR=on&Label=off&EX=NO&KA=M&MAP=1&Zoom=0.6";

if (!function_exists('rkrk_map_flugplan_url')) {
	/**
	 * Tagesflugplan-Karte (FFilter mit +, KA=S, FZ/WW wie pub/rkrk_map).
	 *
	 * @param string|array $ffilter Feld-IDs oder "143-001+148-109"
	 * @param string $mdb Mission z.B. 3T
	 * @param array{embed?:bool,ww?:bool} $options embed=1 für Checklist-Iframe; ww=false → WW=off (Team-Tag)
	 */
	function rkrk_map_flugplan_url($ffilter, $mdb = '', $options = array()) {
		global $RKRKMissionSelected;
		if (is_array($ffilter)) {
			$ffilter = implode('+', array_values(array_filter(array_map('trim', $ffilter))));
		}
		$ffilter = trim((string)$ffilter);
		$opts = is_array($options) ? $options : array();
		$embed = !empty($opts['embed']);
		$wwOn = !array_key_exists('ww', $opts) || !empty($opts['ww']);
		$mdb = trim((string)$mdb);
		if ($mdb === '') {
			$mdb = trim((string)($RKRKMissionSelected ?? ''));
		}
		if (strcasecmp($mdb, 'M3T') === 0) {
			$mdb = '3T';
		}
		$url = 'rkrk_map.php?FFilter=' . $ffilter
			. '&Reg=ALL&Strt=on&Cntr=off&Area=on&Pfad=on&GDE=off&JGG=off&FZ=on&Poi=off'
			. '&WW=' . ($wwOn ? 'on' : 'off')
			. '&Label=off&EX=NO&KA=S&MAP=&Zoom=0.8';
		if ($mdb !== '') {
			$url .= '&MDB=' . rawurlencode($mdb);
		}
		if ($embed) {
			$url .= '&MenuOn=off&MapButtons=off&Embed=1';
		}
		return $url;
	}
}
	
	//Session variables
	$PoiArraySession= "PoiArray";


//	Unicode Table
//  https://www.webatic.com/html-entities-table
//  Ä	\u00c4 
// 	Ö	\u00d6
//	Ü 	\u00dc
//  ä	\u00e4
//	ö	\u00f6
//	ü	\u00fc
	//Session variables
	$PoiArraySession= "PoiArray";


//	Unicode Table
//  https://www.webatic.com/html-entities-table
//  Ä	\u00c4 
// 	Ö	\u00d6
//	Ü 	\u00dc
//  ä	\u00e4
//	ö	\u00f6
//	ü	\u00fc

	$searchUML3 = array("\u00e4", "\u00f6", "\u00fc", "\u00c4", "\u00d6", "\u00dc");
	$replaceUML3  = array("ä", "ö", "ü", "Ä", "Ö", "Ü");

	$searchURL  = array('|', '/',  ' ');
	$replaceURL = array('%7C', '%2F', '%20');

 	$ButtonHeight = 35;  
	$ButtonHeightSmall= 22;

	//pre_r($EnabledRegion);
	$colorOK = "#DAEDDA";
	$colorOKLink = "#368536";
	$colorNOK = "#F1E2E0";
	$colorNOKLink = "#EF5E4A";
	$colorSIM = "#F8FACF";
	$colorSIMLink = "#B7BD3A";
	$colorOKtext = "#6B9E6B";
	$colorNOKtext = "#922618";
	$colorTransparent = "#ffffff00";
	$colorfontstd = "#ffffff";

	//Page list control
	$AnzLines = array(10,20,25,30,50,100,"all");
	$FontSizes = array("100%","125%","150%","200%");
		



// ==================
// Header Page
// ==================

function PrintRKRKHeader($title) {
	echo "<h1 class='rkrk-shell-page-title' id='Titel'>".$title."</h1>" .chr(10);
}



	
function FilenameDefinition () {
	global $OptionPilot;
		$output .= "		<ul>".chr(10); 
		$output .= "			<li>Suchbild Dateiname: <b>JJ-JJJ_FFFF_RRR_KKKKK_00_PP_YY_VV.jpg</b></li>".chr(10); 
		$output .= "			<li>andere Feld/Dateinamen: <b>JJ-JJJ_FFFF_RRR_KKKKK_HH_PP_YY_VV.xxx</b></li>".chr(10); 
		$output .= "		</ul>".chr(10); 
		$output .= "		<ul>".chr(10); 
		$output .= "			<li><b>JJ-JJJ</b>: Jagdrevier und Katalognummer</li>".chr(10); 
		$output .= "			<li><b>FFFF</b>: Flurname aus GIS</li>".chr(10); 
		$output .= "			<li><b>RRR</b>: Richtung f&uumlr mehrere Abschnitte eines zum Flurnamen geh&oumlrenden Feldes</li>".chr(10); 
		$output .= "			<li><b>KKKKK</b>: Katasternummer aus GIS</li>".chr(10); 
		$output .= "			<li><b>HH</b>: Flugh&oumlhe</li>".chr(10); 
		foreach ($OptionPilot as $key => $Pilot) {
			if (in_array("intern", explode(",",$Pilot["Access"])) or in_array("partner", explode(",",$Pilot["Access"]))) {
				$Pilots[$key]= "<br>    " .$Pilot["Opt"] . " - " . $Pilot["Text"];
			}	
		}
		//pre_r($Pilots);
		$output .= "			<li><b>PP</b>: Ersteller (" . implode(", " ,$Pilots) . ")</li>".chr(10); 
		$output .= "			<li><b>YY</b>: Ersteller Jahr</li>".chr(10); 
		$output .= "			<li><b>VV</b>: Version (Optional)</li>".chr(10); 
		$output .= "			<li><b>.xxx</b>: Datei extension (jpg, json, ltchi, kml)</li>".chr(10); 
		$output .= "		</ul>" .chr(10);
		return $output;

}


function definitions($MenuOn=true) {
	global $IconLoc, $OptionGemeinde, $OptionFrei, $OptionWarning,$FlyFileSetting,$Layout;
	echo "	<h1>Definitionen</h1>".chr(10); 
	echo " 		<table style='small'>".chr(10); 
	echo "   			<tr>".chr(10); 
	echo "					<td valign='top'>".chr(10); 

	echo "	<h2>Freigabe der Flugfelder</h2>".chr(10); 
	echo "		<ul>".chr(10); 
	foreach ($OptionFrei as $key => $Opt) {
		echo "			<li><img src='" . $IconLoc . "/" . $Opt["Pic"] ."' height='25px'> <b>" . $Opt["Opt"]  . "</b>: " . $Opt["Text"]  . "</li>".chr(10); 
	}
	echo "		</ul>".chr(10); 

	echo "<h2>8-Tung [!]</h2>".chr(10); 
	echo "		<ul>".chr(10); 
	foreach ($OptionWarning as $key => $Opt) {
		echo "			<li><img src='" . $IconLoc . "/" . $Opt["Pic"] ."' height='25px'> <b>" . $Opt["Opt"]  . "</b>: " . $Opt["Text"]  . "</li>".chr(10); 
	}
	echo "		</ul>".chr(10); 
	echo " <h2>Gemeinden</h2>".chr(10); 
	echo "		<table ><tr><td valign='top'>".chr(10); 

	$CountGde = count($OptionGemeinde);
	array_multisort($OptionGemeinde, SORT_ASC);
	$line = 0;

	echo "		<ul>".chr(10); 
	foreach ($OptionGemeinde as $Gemeinde) {
		$line++;
		echo "			<li><b>" . $Gemeinde["Opt"] . "</b> " . $Gemeinde["Text"] . "</li>".chr(10); 
		if ($line> $CountGde/2) {
			echo "		</ul>".chr(10); 
			echo "		</td><td valign='top'>".chr(10); 
			echo "		<ul>".chr(10); 
			$line= 0;
		}
	}
	echo "		</ul>".chr(10); 
	echo "		</td></tr></table>".chr(10); 

	echo "		</td>".chr(10); 
	echo "		<td valign='top'>".chr(10); 

	if (GetAccess("PilotPartners")) {
		echo "<h2>Dateinamen</h2>".chr(10); 
		echo FilenameDefinition();
	}

	
	echo "<h2>Links</h2>".chr(10); 
	echo "		<ul>".chr(10); 
	echo "	 		<li><img src='" . $IconLoc . "/startpnt.png' height='25px'>  Link zum Startplatz auf Google Map</li>".chr(10); 

	echo " 			<li><img src='" . $IconLoc . "/geomappos.png' height='25px'>  Link zum Feld in MapGeo.Admin.ch</li>".chr(10);
	foreach ($FlyFileSetting as $key => $FlyFile) {
		if ($FlyFile["Picture"]!=true) {
			echo " 			<li><img src='" . $IconLoc . "/" . $FlyFile["icon"] . "xx.png' height='25px'>  " . $FlyFile["Text"] . "</li>".chr(10); 
		}
	}
	//echo " 			<li><img src='" . $IconLoc . "/pdfxx.png' height='25px'>  PDF Flugplan</li>".chr(10); 
	//echo " 			<li><img src='" . $IconLoc . "/kmzxx.png' height='25px'>  KMZ Flugplan (für Mavic M3T)</li>".chr(10); 
	//echo " 			<li><img src='" . $IconLoc . "/kmlxx.png' height='25px'>  KML Flugplan (Google Earth)</li>".chr(10); 
	//echo "			<li><img src='" . $IconLoc . "/litchixx.png' height='25px'>  Litchi Daten für Litchi Mission Manager</li>".chr(10); 
	//echo "			<li><img src='" . $IconLoc . "/jsonxx.png' height='25px'>  JSON File</li>".chr(10); 
	echo "		</ul>" .chr(10);
	if ($MenuOn) {
		echo "<h2>Menu</h2>".chr(10); 
		echo "		<ul>".chr(10);
		$publicIcon = file_exists($IconLoc . "/public_small.png") ? "/public_small.png" : "/public.png";
		$internIcon = file_exists($IconLoc . "/intern_small.png") ? "/intern_small.png" : "/intern.png";
		echo "	 		<li><img src='" . $IconLoc . "/flugplan_small.png' height='25px'>  Flugplan als PDF</li>".chr(10); 
			echo "	 		<li><img src='" . $IconLoc . "/geomap_small.png' height='25px'>  Flugpläne auf Karten von map.geo.admin.ch</li>".chr(10); 
			echo "	 		<li><img src='" . $IconLoc . $publicIcon . "' height='25px'>  Öffentliche Flugpläne</li>".chr(10); 
			if (GetAccess("PilotPartners")) {
				echo "	 		<li><img src='" . $IconLoc . $internIcon . "' height='25px'>  Online Flugplan</li>".chr(10); 
				echo "	 		<li><img src='" . $IconLoc . "/neuesfeld_small.png' height='25px'>  Neues Feld hinzufügen</li>".chr(10); 
				echo "	 		<li><img src='" . $IconLoc . "/edit_feld_small.png' height='25px'>  Feld editieren</li>".chr(10); 
				echo "	 		<li><img src='" . $IconLoc . "/edit_feldlist_small.png' height='25px'>  Feldliste editieren</li>".chr(10); 

			}

			echo "	 		<li><img src='" . $IconLoc . "/feldneu_small.png' height='25px'>  Registrieren eines neuen Feldes (nicht im Katalog)</li>".chr(10); 
		echo "		</ul>" .chr(10);
		echo "	</td>".chr(10); 
		echo "		</tr>".chr(10); 
		echo "  </table>".chr(10);
	}
}


function mapcolor_legend ($Public, $fontsize="100%") {


//KML colors and icons
    $RedFontColor = "#fc2222";
    $GreenFontColor = "#7dd91c"; //7d1cd97d
    $DGreenFontColor = "#353762";  //7d2d7653
    $vMGreenColor =	"#67b516";	//"7d16b567"
	$YellowFontColor = "#fce700"; // ff00e7fc
    $OrangeFontColor = "#f9a825"; //7d25a8f9
    $GreyFontColor = "#bdbdbd";  //7dbdbdbd
    $BlueFontColor = "#0288d1";  //7dd18802
    $MBlueFontColor = "#006ba6";  //7da66b00
    $ViolettFontColor = "#ce93d8";  //7dd893ce
    $DOrangeColor =  "#ff9600"; //"7d0096FF";

	$block = html_entity_decode('█', ENT_NOQUOTES, 'UTF-8'); // full block
	$output ="";
	$output .= "<font style='font-size: " . $fontsize . ";'>" .chr(10);
	$output .= "Farbenlegende:  ".chr(10); 
	$output .= "<font style='color:".$GreenFontColor."'>".$block."</font>".chr(10); 
	$output .= " ok ".chr(10); 
	$output .= "<font style='color:".$vMGreenColor."'>".$block."</font>".chr(10); 
	$output .= " Nachtflug ".chr(10); 
	$output .= "<font style='color:".$OrangeFontColor."'>".$block."</font>".chr(10); 
	$output .= " Testflug empfohlen! ".chr(10); 
	$output .= "<font style='color:".$BlueFontColor."'>".$block."</font>".chr(10); 
	$output .= " Objekt im Feld! ".chr(10); 
	$output .= "<font style='color:".$ViolettFontColor."'>".$block."</font>".chr(10); 
	$output .= " zu prüfen! ".chr(10); 
	$output .= "<font style='color:".$DOrangeColor."'>".$block."</font>".chr(10); 
	$output .= " SORA 150m".chr(10); 
	$output .= "<font style='color:".$RedFontColor."'>".$block."</font>".chr(10); 
	$output .= " Flugeinschränkungen! ".chr(10); 
	$output .= "<font style='color:".$YellowFontColor."'>".$block."</font>".chr(10); 
	$output .= " Neues Feld! ".chr(10); 
	$output .= "<font style='color:".$GreyFontColor."'>".$block."</font>".chr(10); 
	$output .= " nicht verwenden! ".chr(10); 
	$output .= "</font>" .chr(10);
	Return ($output);
}

function fligtzone_legend ($Public, $fontsize="100%", $Platz="FGHO", $FZO="") {


//KML colors and icons
    $YellowColor = "#ffee8d"; //ff8deeff
	$GreenColor =  "#8dee8d";//ff8dee8d
    $LightBlueColor = "#327dff"; //ffff7d32
    $BlueColor = "#8fb8ff";  //ffffb88f
	$RedColor = "#ff0000"; // ff0000ff

	$block = html_entity_decode('█', ENT_NOQUOTES, 'UTF-8'); // full block
	$output ="";
	if ($Platz=="FGHO") {
		//Hausen a.A.
//	FZO: Flight Zone Options
//		FHGO-NF1: FHGO nur eine Nachflugzone
//		FHGO-NF2: FHGO nur zwei Nachflugzonen
//		FHGO-STP: FHGO gesperrt		$output .= "<font style='font-size: " . $fontsize . ";'>" .chr(10);
		$output .= "<b>Flugzonen:</b> <br><br>".chr(10); 
		$output .= "<font style='color:".$YellowColor.";'>".$block."</font>".chr(10); 
		$output .= " 70m GND Flugzone. <br>".chr(10); 
		$output .= " <i>max. 70m AGL mit FGHO Übereinkunft</i><br><br>";
		$output .= "<font style='color:".$GreenColor.";'>".$block."</font>".chr(10); 
		$output .= " 120m GND Flugzone. <br>".chr(10); 
		$output .= " <i>max. 120m AGL mit FGHO Übereinkunft</i><br><br>";
		if (($FZO=="") or ($FZO<>"FHGO-STP")) {
			$output .= "<font style='color:".$LightBlueColor."'>".$block."</font>".chr(10); 
			$output .= " Nachtflugzone 1 <br>".chr(10); 
			$output .= " <i>22:00h-06:00h max. 70m AGL <br>mit FGHO Übereinkunft<br>06:00h-22-00h Flugverbot!</i><br><br>";
		}
		if ((($FZO=="")) or ($FZO=="FHGO-NF2")) {
			$output .= "<font style='color:".$BlueColor."'>".$block."</font>".chr(10);
			$output .= " Nachtflugzone 2 <br>".chr(10); 
			$output .= " <i>22:00h-08:00h max. 70m AGL <br>mit FGHO Übereinkunft<br>08:00h-22-00h Flugverbot!</i><br><br>";
		}
		$output .= "<font style='color:".$RedColor."'>".$block."</font>".chr(10); 
		$output .= " Flugverbotszone <br>".chr(10); 
		$output .= " <i>Flugverbot ohne indiviuelle Bewilliung!</i><br><br>".chr(10); 
	}
	
	if ($Platz=="LSXN") {
		//Hausen a.A.
		$output .= "<b>Flugzonen:</b> <br><br>".chr(10); 
		$output .= "<font style='color:".$LightBlueColor."'>".$block."</font>".chr(10); 
		$output .= " Nachtflugzone<br>".chr(10); 
		$output .= " <i>21:00h-09:00h max. 70m AGL <br>mit LSXN Übereinkunft<br>09:00h-21:00h Flugverbot</i><br><br>";
	}
	$output .= "</font>" .chr(10);
	Return ($output);
}

/**
 * True if OptionWarning keys are Nachflugzonen (NGT1/2/3), shown beside the field title.
 */
function rkrk_is_nachflugzone_warning_key($key) {
	return ($key === 'NGT1' || $key === 'NGT2' || $key === 'NGT3');
}

/**
 * Echo small icons for applicable Nachflugzonen immediately after field title text.
 *
 * @param string $warnung Feld-Warnungstext (z. B. "CTR NGT1")
 * @param int    $iconHeightPx img height attribute
 */
function rkrk_echo_nachflugzone_title_icons($warnung, $iconHeightPx = 20) {
	global $OptionWarning, $IconLoc;
	if ($IconLoc === null || $IconLoc === '') {
		return;
	}
	if (!is_array($OptionWarning)) {
		return;
	}
	$h = (int)$iconHeightPx;
	if ($h < 12) {
		$h = 16;
	}
	foreach (array('NGT1', 'NGT2', 'NGT3') as $k) {
		if (!isset($OptionWarning[$k])) {
			continue;
		}
		if (!like((string)$warnung, $k)) {
			continue;
		}
		$pic = !empty($OptionWarning[$k]['PicSmall']) ? $OptionWarning[$k]['PicSmall'] : $OptionWarning[$k]['Pic'];
		$text = htmlspecialchars($OptionWarning[$k]['Text'], ENT_QUOTES, 'UTF-8');
		echo ' <img src="' . $IconLoc . $pic . '" title="' . $text . '" alt="" style="vertical-align:middle;margin-left:2px;" height="' . $h . '">';
	}
}

function region_legend ($fontsize="100%", $regions_selected="") {
global $RegionSettings;
	$block = html_entity_decode('█', ENT_NOQUOTES, 'UTF-8'); // full block
	$output ="";
	$output .=  "<font style='font-size: " . $fontsize . ";'>" .chr(10);
	$output .=  "Regionen:  " .chr(10); 
	foreach ($RegionSettings as $key => $Region) {
		if (
			($Region["Aktiv"]) 
			and (
				($regions_selected=="") 
				or (strpos(" " .$regions_selected, $Region["Opt"])!==false)
				)
			and ($key!="NO")
			)
				$RegionsArray[] =  $Region["Text"];
	}
	$output .= implode(",",$RegionsArray);
	$output .=  "</font>" .chr(10);
	Return ($output);
}

function region_list () {
global $RegionSettings;
	$list ="";
	foreach ($RegionSettings as $key => $Region) {
		if ($Region["Aktiv"])
			$list .= $Region["Reg"] .  ": " . $Region["Text"] .  chr(10);
	}
	return $list;
}

// ==================
// File XML einlesen
// ==================

function rkrk_load_xml_file_safe($filename) {
	if (!file_exists($filename)) {
		return false;
	}
	$raw = function_exists('rkrk_read_text_file')
		? rkrk_read_text_file($filename)
		: file_get_contents($filename);
	if ($raw === false || $raw === '') {
		return false;
	}
	if (function_exists('rkrk_bytes_to_utf8')) {
		$raw = rkrk_bytes_to_utf8($raw);
	}

	$attempts = array($raw);
	if (function_exists('mb_convert_encoding')) {
		$attempts[] = mb_convert_encoding($raw, 'UTF-8', 'UTF-8');
		$attempts[] = mb_convert_encoding($raw, 'UTF-8', 'Windows-1252,ISO-8859-1');
	}

	foreach ($attempts as $candidate) {
		if (!is_string($candidate) || $candidate === '') {
			continue;
		}
		$xml = @simplexml_load_string($candidate, 'SimpleXMLElement', LIBXML_NOERROR | LIBXML_NOWARNING);
		if ($xml !== false) {
			return $xml;
		}
	}

	return false;
}

function rkrk_is_valid_mission_file_link($link, $allowedExtensions = array())
{
	$link = trim((string) $link);
	if ($link === '') {
		return false;
	}
	if (function_exists('rkrk_feld_looks_corrupt_json') && rkrk_feld_looks_corrupt_json($link)) {
		return false;
	}
	if ($link[0] === '{' || $link[0] === '[') {
		return false;
	}
	if (!empty($allowedExtensions)) {
		$ext = strtolower(pathinfo($link, PATHINFO_EXTENSION));
		return in_array($ext, array_map('strtolower', $allowedExtensions), true);
	}
	return (strpos($link, '/') !== false || strpos($link, '.') !== false);
}

function rkrk_get_mission_fly_file_settings($showContext)
{
	global $FlyFileSetting, $MissionDroneTypes, $RKRKMissionSelected;
	$missionDataFiles = array_map('trim', explode(',', (string) ($MissionDroneTypes[$RKRKMissionSelected]['DataFile'] ?? '')));
	$settings = array();
	foreach ($FlyFileSetting as $key => $fileType) {
		if (!empty($fileType['Picture'])) {
			continue;
		}
		$showOn = array_map('trim', explode(',', (string) ($fileType['Show'] ?? '')));
		if (!in_array($showContext, $showOn, true)) {
			continue;
		}
		$xmlLabel = trim((string) ($fileType['XMLLabel'] ?? ''));
		if ($xmlLabel === '') {
			continue;
		}
		if (!empty($missionDataFiles) && !in_array($xmlLabel, $missionDataFiles, true)) {
			continue;
		}
		$settings[$key] = $fileType;
	}
	return $settings;
}

function rkrk_get_field_mission_file_links($feld, $xmlLabel)
{
	if (!is_array($feld)) {
		return array();
	}
	$xmlLabel = trim((string) $xmlLabel);
	if ($xmlLabel === '') {
		return array();
	}
	$raw = $feld[$xmlLabel] ?? '';
	if (function_exists('rkrk_feld_scalar_text')) {
		$raw = rkrk_feld_scalar_text($raw);
	} else {
		$raw = trim((string) $raw);
	}
	if ($raw === '') {
		return array();
	}
	$links = array();
	foreach (explode(',', $raw) as $link) {
		$link = trim((string) $link);
		if ($link !== '') {
			$links[] = $link;
		}
	}
	return $links;
}

   function ReadCatalogFile($filename, $debugoutput = false)  {
		$xml = false;
		if (function_exists('rkrk_catalog_load_simplexml')) {
			$xml = rkrk_catalog_load_simplexml($filename);
		} elseif (function_exists('rkrk_xml_catalog_support') && rkrk_xml_catalog_support() && file_exists($filename)) {
			$xml = rkrk_load_xml_file_safe($filename);
		}
		if ($xml && $debugoutput) {
			$objJsonDocument = json_encode($xml);
			$arrOutput = json_decode($objJsonDocument, TRUE);
			echo "<pre>";
			print_r($arrOutput);
			echo "</pre>";
		} elseif (!$xml) {
			$jsonPath = function_exists('rkrk_catalog_json_path') ? rkrk_catalog_json_path($filename) : '';
			$haveJson = ($jsonPath !== '' && file_exists($jsonPath));
			$haveXml = file_exists($filename);
			if (!$haveJson && !$haveXml) {
				echo '        <p>Die Datei names '. $filename .' konnte nicht geöffnet werden</p>';
			}
		}
		return $xml;
	}
	function ReadXMLFile($filename, $debugoutput = false) {
		return ReadCatalogFile($filename, $debugoutput);
	}


// ==================
// File einlesen
// ==================

	function htmlentities_decode( $string ) {
    $trans = get_html_translation_table(HTML_ENTITIES, ENT_QUOTES);
    $trans = array_flip($trans);
    return strtr($string, $trans);
}

// ==================
// Like Function
// ==================

function like($str, $searchTerm, $delmimiter=",") {
/*
    $searchTerm = strtolower($searchTerm);
    $str = strtolower($str);
    $pos = strpos($str, $searchTerm);
    if ($pos === false)
        return false;
    else
        return true;
*/
	$isfound = false;
	if (function_exists('rkrk_str')) {
		$str = rkrk_str($str);
		$searchTerm = rkrk_str($searchTerm);
	} else {
		$str = is_array($str) || is_object($str) ? '' : (string) ($str ?? '');
		$searchTerm = is_array($searchTerm) || is_object($searchTerm) ? '' : (string) ($searchTerm ?? '');
	}
	$searchTerm = strtolower($searchTerm);
	$str = strtolower($str);
	// simple search
	$pos = strpos($str, $searchTerm);
	if ($pos === false)
		$isfound= false;
	else
		$isfound= true;
	
	//complex search
	if (!$isfound) {
		$searchTerm = trim(strtolower($searchTerm));
		$searchTermArray = explode($delmimiter, $searchTerm);
		//pre_R($searchTermArray);
		$str = strtolower($str);
		$isfound == false;
		foreach ($searchTermArray as $searchTerm) {
			if (strpos(trim($searchTerm), $str) === false) {
				//$isfound = false;
			} else {
				$isfound= true;
			}
		}
	}
	return $isfound;
}

function xlike($findTerm, $searchTerm, $delmimiter=" ") {
	$searchTerm = str_replace(" ", "", strtolower($searchTerm));
	$searchTermArray = explode($delmimiter, $searchTerm);
	//echo "SearchTerm " . $searchTerm .  "<br>";
	//echo "searchTermArray " . $str .  "<br>";
	$findTerm = strtolower($findTerm);
	$findTermArray = explode($delmimiter, $findTerm);

	$isfound = false;

	foreach ($findTermArray as $findstr) {
		foreach ($searchTermArray as $searchstr) {
			if (strpos($findTerm, $searchstr) !== false) {
			$isfound = true;
			}
		}
 	}
	return $isfound;
}



// ==================
// Test input in forms
// ==================

	function test_input($data) {
		$data = trim($data);
		$data = stripslashes($data);
		$data = htmlspecialchars($data);
		return $data;
	}


// ==================
// Get Parameter of reginal seeting from XML
// ==================

	// NOT IN USE ANYMORE !!!!
	// 11.05.2024 TCHR
	function GetRegionalSetting_notinuse($SelReg, $field) {
		global $xmlsubfolder, $xmlMissionLocation, $RKRKRegionalSettingXML,$searchUML, $replaceUML;
		$Settings = ReadCatalogFile($xmlsubfolder.$xmlMissionLocation.$RKRKRegionalSettingXML, false);
		foreach ($Settings as $Set) {
			if ($SelReg == $Set->JG) {
			switch ($field):
				case "Name":
					$data = str_replace($searchUML, $replaceUML, $Set->Name);
					break;
				case "FlugplanTitel":
					$data = str_replace($searchUML, $replaceUML, $Set->FlugplanTitel);
					break;
				case "PdfKatalog":
					$data = $Set->PdfKatalog;
					break;
				case "GoogleEarth":
					$data = $Set->GoogleEarth;
					break;
				case "PublicGoogleEarth":
					$data = $Set->PublicGoogleEarth;
					break;
				case "GoogleMap":
					$data = $Set->GoogleMap;
					break;
				case "MapMenuLink":
					$data = $Set->MapMenuLink;
					break;
				case "PublicMapMenuLink":
					$data = $Set->PublicMapMenuLink;
					break;
				case "FelderMenuLink":
					$data = $Set->FelderMenuLink;
					break;
				case "PublicFelderMenuLink":
					$data = $Set->PublicFelderMenuLink;
					break;
				case "MapEast":
					$data = $Set->GeoMapSetting->East;
					break;
				case "MapNorth":
					$data = $Set->GeoMapSetting->North;
					break;
				case "MapZoom":
					$data = $Set->GeoMapSetting->Zoom;
					break;
				case "Aktiv":
					$data = $Set->Aktiv;
					break;
				default:
					$data = "";
			endswitch;
			}
		}
		return $data;
	}

// =======================
// Create upper left menue
// =======================

function DoMenuMap($ThisPage, $Title, $SelRegion, $Parameterset) {
	global $xmlsubfolder, $xmlMissionLocation, $RKRKRegionalSettingXML,$searchUML, $replaceUML;
	$Settings = ReadCatalogFile($xmlsubfolder.$xmlMissionLocation.$RKRKRegionalSettingXML, false);

	$output="";
	$output .= "<script src='scripts/mapgeo.js'></script>".chr(10);
	$output .= "<div id='mySidenav' class='sidenav'>".chr(10);
	$output .= "<a href='javascript:void(0)' class='closebtn' onclick='closeNav()'>&times;</a>".chr(10);

	$RegTitle= "";
	foreach ($Settings as $Set) {
		//echo $Set->Name;
		if ($Set->Aktiv=="ja") {
			if ($Set->Name<>"") {
				$output .= "<a href= '" . $ThisPage. "?Reg=" . $Set->JG . $Parameterset . "' style='color:#AAAAAA'>" . str_replace($searchUML, $replaceUML,$Set->Name) . "</a> ".chr(10);
			}
		}
		if ($Set->JG== $SelRegion) {
			$RegTitle = str_replace($searchUML, $replaceUML, $Set->FlugplanTitel);
		}
	}
	$output .= "</div>";
	$output .="<p><span style='font-size:30px;cursor:pointer' onclick='openNav()'>&#9776; " . $Title . "<b> " . $RegTitle . "</b>" . chr(10);
	$output .="</p>";
	return $output;
}

////////////////////////////////
// read and convert 
////////////////////////////////

function csvstring_to_array($string, $separatorChar = ',', $enclosureChar = '"', $newlineChar = "\n") {
    // @author: Klemen Nagode
    $string = function_exists('rkrk_str') ? rkrk_str($string) : (string)$string;
    $array = array();
    $size = strlen($string);
    $columnIndex = 0;
    $rowIndex = 0;
    $fieldValue="";
    $isEnclosured = false;

    for($i=0; $i<$size;$i++) {

        $char = $string[$i];
        $addChar = "";

        if($isEnclosured) {
            if($char==$enclosureChar) {

                if($i+1<$size && $string[$i+1]==$enclosureChar){
                    // escaped char
                    $addChar=$char;
                    $i++; // dont check next char
                }else{
                    $isEnclosured = false;
                }
            }else {
                $addChar=$char;
            }
        }else {
            if($char==$enclosureChar) {
                $isEnclosured = true;
            }else {

                if($char==$separatorChar) {

                    $array[$rowIndex][$columnIndex] = $fieldValue;
                    $fieldValue="";

                    $columnIndex++;
                }elseif($char==$newlineChar) {
                    echo $char;
                    $array[$rowIndex][$columnIndex] = $fieldValue;
                    $fieldValue="";
                    $columnIndex=0;
                    $rowIndex++;
                }else {
                    $addChar=$char;
                }
            }
        }
        if($addChar!=""){
            $fieldValue.=$addChar;

        }
    }

    if($fieldValue) { // save last field
        $array[$rowIndex][$columnIndex] = $fieldValue;
    }
    return $array;
}


/////////////////////////////////////
// Get TCPIP number of the device
/////////////////////////////////////

	function getTCPIP () {
		//echo "HUA: " . $_SERVER['HTTP_USER_AGENT'] . "<br>";
		//echo "RA: " . $_SERVER['REMOTE_ADDR'] . "<br>";
		//echo "CIP: " . $_SERVER['HTTP_CLIENT_IP'] . "<br>";
		//echo "XFW: " . $_SERVER['HTTP_X_FORWARDED_FOR'] . "<br>";
		//return system('arp -an');
		return $_SERVER['REMOTE_ADDR'];
	}

/////////////////////////////////////
// Get MAC number of the device
/////////////////////////////////////

	function getIPConfig () {
		$ipconfig =   shell_exec ("ifconfig -a | grep -Po 'HWaddr \K.*$'");
		$ipconfig =  explode(" ", $ipconfig);
		//echo "IPC: " . $ipconfig . "<br>";
		//pre_r($ipconfig);
    // display mac address   
		return $ipconfig[0];
	}	

function getIPAddress() {
	//echo "HUA: " . $_SERVER['HTTP_USER_AGENT'] . "<br>";
	//echo "RA: " . $_SERVER['REMOTE_ADDR'] . "<br>";
	//echo "CIP: " . $_SERVER['HTTP_CLIENT_IP'] . "<br>";
	//echo "XFW: " . $_SERVER['HTTP_X_FORWARDED_FOR'] . "<br>";

   if (isset($_SERVER['HTTP_CLIENT_IP']))
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_X_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    else if(isset($_SERVER['REMOTE_ADDR']))
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    else
        $ipaddress = 'UNKNOWN';

   return $ipaddress;
	
}

//////////////////////////////////////////////
//print formated array
//////////////////////////////////////////////


	// ===========================================
	// Array Functions
	// ===========================================
	
	// multidimensionsal sorting of array
    // aasort($your_array, "order");
	
	function aasort (&$array, $key) {
    $sorter = array();
    $ret = array();
    if (!is_array($array)) {
		$array = array();
		return;
	}
    reset($array);
		foreach ($array as $ii => $va) {
			if (!is_array($va)) {
				continue;
			}
			$sorter[$ii] = $va[$key] ?? '';
			if (is_array($sorter[$ii]) || is_object($sorter[$ii])) {
				$sorter[$ii] = '';
			}
		}
		asort($sorter);
		foreach ($sorter as $ii => $va) {
			$ret[$ii] = $array[$ii];
		}
		$array = $ret;
	}

//////////////////////////////////////////////
// add entry to logstatistics
//////////////////////////////////////////////

function OpenLogFileSafe($filename, $mode) {
	$dir = dirname($filename);
	if (!is_dir($dir)) {
		@mkdir($dir, 0777, true);
	}
	return @fopen($filename, $mode);
}

function WriteToStatistic($Type, $Content, $Text1, $Text2, $Text3, $Text4, $LogArt="Site") {
global $logfilelocation, $DebugWork, $RKRKLocalLoactionMissionFiles, $CleanAgeOfFilesInDays, $WebFreigabe, $RKRKSiteStatsCSV, $RKRKIPStatsCSV,$IPUsed, $searchUML, $replaceUML;

	$delimiter= ";";
	$entryfound = false;
	$Statistic = Array();
	if (!isset($searchUML) || !is_array($searchUML)) {
		$searchUML = array();
	}
	if (!isset($replaceUML) || !is_array($replaceUML)) {
		$replaceUML = array();
	}
	if ($LogArt=="Site") {
		//Site
		$CountTitle = "Seite";
		$headerline = "Monat;Level;" . $CountTitle . ";Anzahl";
		$filename= $logfilelocation.$RKRKSiteStatsCSV;
	} else {
		//IP log
		$CountTitle = "IPNr";
		$headerline = "Monat;Level;" . $CountTitle . ";Pers;Provider;Anzahl";
		$filename= $logfilelocation.$RKRKIPStatsCSV;
	}
	$header = explode(";", $headerline);
	if(file_exists($filename) && is_readable($filename)) {
		
		$header = NULL;
		$data = array();
		if (($handle = fopen($filename, 'r')) !== FALSE)
		{
			while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE)
			{
				//pre_r($row);
				if(!$header)
					$header = $row;
				else
				  $data[] = array_combine($header, $row);
			}
			fclose($handle);

			$Statistic = str_replace($searchUML, $replaceUML, $data);
			//pre_r($Statistic);
			//echo "Monat: " . $Statistic[0][$header[0]] . "<br>";
		}
		aasort($Statistic, "Level");
		aasort($Statistic, $CountTitle);
		aasort($Statistic, "Monat");
		//pre_R($Statistic);

	}

	$txt = $headerline . chr(10);
	$myfile = OpenLogFileSafe($filename, "w");
	if ($myfile === false) {
		return;
	}
	fwrite($myfile, $txt);
	//fwrite($myfile, mb_convert_encoding( $txt, 'UTF-16LE', 'UTF-8'));
	foreach ($Statistic as $key => $StatLine) {
		//echo $key. "<br>";
		//echo "Monat: " . $StatLine[$header[0]] . " - ";
		//echo "Level: " . $StatLine[$header[1]] . " - ";
		//echo "Seite: " . $StatLine[$header[2]] . " - ";
		//echo "Anzahl: " . $StatLine[$header[3]] . "<br>";
		//echo "<br>";
		//pre_R($StatLine);
		if (
			(trim(date("Ym"))==trim($StatLine[$header[0]])) and
			(trim($WebFreigabe)==trim($StatLine[$header[1]])) and
			(trim($Content)==trim($StatLine[$header[2]]))
			) 	
		{
			if ($LogArt=="Site") {
				//Site
				$StatLine[$header[3]] = intval($StatLine[$header[3]]) + 1;
				$txt = $StatLine[$header[0]] . ";" . $StatLine[$header[1]] .";" . $StatLine[$header[2]] .";" . $StatLine[$header[3]]  . chr(10);
			} else {
				//IP log
				$StatLine[$header[5]] = intval($StatLine[$header[5]]) + 1;
				$txt = str_replace(Array("\0"," "), Array("",""), $StatLine[$header[0]] . ";" . $StatLine[$header[1]] .";" . $StatLine[$header[2]] .";" . $StatLine[$header[3]].";" . $StatLine[$header[4]].";" . $StatLine[$header[5]]) . chr(10);
			}
			//echo "Seite erkannt: " . $txt . "<br>";
			$entryfound = true;
		} else {
			if ($LogArt=="Site") {
				//Site
				$txt = str_replace(Array("\0"," "), Array("",""), $StatLine[$header[0]] . ";" . $StatLine[$header[1]] .";" . $StatLine[$header[2]] .";" . $StatLine[$header[3]]) . chr(10);
			} else {
				//IP log
				if ($Text1=="kill") 
					$addremark = " - KILLED";
				else
					$addremark = "";
				$txt = str_replace(Array("\0"," "), Array("",""), $StatLine[$header[0]] . ";" . $StatLine[$header[1]] .";" . $StatLine[$header[2]] .";" . $StatLine[$header[3]].";" . $StatLine[$header[4]]. $addremark . ";" . $StatLine[$header[5]]) . chr(10);
			}
		}
		fwrite($myfile, $txt);
		//fwrite($myfile, mb_convert_encoding( $txt, 'UTF-16LE', 'UTF-8'));
	}
	if (!$entryfound) {
		if ($LogArt=="Site") {
			//Site
			$txt = date("Ym") . ";" . $WebFreigabe .";" . $Content .";1" . chr(10);
		} else {
			//IP log
			if ($IPUsed[$Content]["Name"]!="") {
				$Name=$IPUsed[$Content]["Name"];
				$Provdier=$IPUsed[$Content]["Provider"];
			} else {
				$Name="??";
				$Provdier="";
			}
			//IP log
			if ($Text1=="kill") 
				$addremark = " - KILLED";
			else
				$addremark = "";
			$txt = date("Ym") . ";" . $WebFreigabe .";" . $Content . ";" . $Name . ";" . $Provdier . $addremark.";1" . chr(10);
		}
		fwrite($myfile, $txt);
	}
	fclose($myfile);
}


//////////////////////////////////////////////
// add entry to logfile

//////////////////////////////////////////////
function Redirect($ALevel) {
global $AccessLevels;
	$ALevel = explode(",", $ALevel);
	//pre_r($ALevel);
	$AccessRequest = explode("/",$_SERVER['PHP_SELF']);
	//pre_r($AccessRequest);
	// pageaccesslevel in $AccessRequest[2]
	//Check if access ok
	if (in_array($AccessRequest[2], $ALevel)) {
		//access request fits access level
		//echo "access level fits access request " . $AccessRequest[2] . "<br>"; 
	} else {
		//echo "### access level DOES NOT fit access request " . $AccessRequest[2] . "<br>"; 
		//pre_r($AccessLevels);
		foreach ($AccessLevels as $key => $AcLevel) {
			//echo $AcLevel["directory"] . "<br>";
			if (in_array($key, $ALevel)) {
				//Access granted to this level
				//echo $_SERVER['PHP_SELF'] .  "  " . $AccessRequest[2]."/" . "  " .  $AcLevel["directory"] . "<br>";
				$NewPageLink = str_replace($AccessRequest[2]."/",$AcLevel["directory"],$_SERVER['REQUEST_URI']);
				//echo $NewPageLink;
				header('Location: '. $NewPageLink);
				die();
				break;
			}
		}
	}
}

//Redirect('https://example.com/', false);



function CheckIPandExit() {
	global $IPBanned,$IPBannedGroup, $IPUsed,$OptionPilot;
	//pre_r($IPBanned);
	//pre_r($IPBannedGroup);
	$TCPAdress = getIPAddress();
	//pre_r(explode(".",$TCPAdress)) . "<br>";
	$TCPIP =explode(".",$TCPAdress);
	if (ProtectFromAccess ($TCPAdress)) {
		WriteToLog("kill", basename($_SERVER['PHP_SELF']), "kill ",$TCPAdress,"","");
		if (!empty($GLOBALS['RKRK_AJAX_JSON_MODE']) && function_exists('rkrk_checklist_json_response')) {
			rkrk_checklist_json_response(['ok' => false, 'error' => 'access_denied'], 403);
		}
		exit('Session killed.');
	}
	if (array_key_exists($TCPAdress, $IPUsed)) {
		//IP identified
		$User = $IPUsed[$TCPAdress]["Name"];
		$AccessLevel= $OptionPilot[$User]["Access"]; 
		$cookie_name = "user";
		$cookie_value = $IPUsed[$TCPAdress]["Name"];
		//echo "set cookie: " .  $cookie_value . "<br>";
		if (!headers_sent()) {
			setcookie($cookie_name, $cookie_value, time() + (86400 * 30), "/");
		}
		//Redirect($AccessLevel);
	}
	

}



function GetNameUser() {
	$cookie_name = "user";
	if(!isset($_COOKIE[$cookie_name])) {
	  return "";
	} else {
	  return $_COOKIE[$cookie_name];
	}
}


function WriteToLog($Type, $Page, $Text1, $Text2, $Text3, $Text4) {
	global $logfilelocation, $DebugWork, $RKRKLocalLoactionMissionFiles, $CleanAgeOfFilesInDays, $WebFreigabe,$SessNme_RegisteredInitialen;

	$LogfileName = date("Ymd") . "_logfile.csv";
	$LogfilePath = $logfilelocation.$LogfileName;
	//echo $logfilelocation.$LogfileName;

	if (!file_exists($LogfilePath)) {
		$txt = "Zeit;IP Adresse;Mac Address;Client;Device;User;Level;Typ;Seite;Text;Parameter;Text3;Text4". chr(10);
		$myfile = OpenLogFileSafe($LogfilePath, "w");
		if ($myfile !== false) {
			fwrite($myfile, $txt);
			//fwrite($myfile, mb_convert_encoding( $txt, 'UTF-16LE', 'UTF-8'));
			fclose($myfile);
		}
	}
	
	$Client = "";
	$User = "";
	$TCPAdress = getIPAddress();
	$MacAddress = getIPConfig();
	if (isset($_SESSION[$SessNme_RegisteredInitialen]) && $_SESSION[$SessNme_RegisteredInitialen]!="") {
		$User=  $_SESSION[$SessNme_RegisteredInitialen];
	}
	$userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : "";
	 $txt = date("Y-m-d H:i:s") . ";" . $TCPAdress .";" . $MacAddress.";" . $Client .";" .str_replace(";",",",$userAgent) . ";" . $User . ";" . $WebFreigabe  . ";" .$Type . ";" .  $Page  . ";" . $Text1  . ";" . $Text2 . ";" . $Text3 . ";" . $Text4. chr(10);
	$myfile = OpenLogFileSafe($LogfilePath, "a");
	if ($myfile !== false) {
		fwrite($myfile, $txt);
		//fwrite($myfile, mb_convert_encoding( $txt,'UTF-16LE', 'UTF-8'));
		fclose($myfile);
	}
	CleanupOldFiles($CleanAgeOfFilesInDays);
	WriteToStatistic($Type, $Page, $Text1, $Text2, $Text3, $Text4,"Site");
	WriteToStatistic($Type, $TCPAdress, $Text1, $Text2, $Text3, $Text4, "IP");
}


function CleanupOldFiles($ageInDays) {
	global $logfilelocation, $historyfilelocation, $deletefilelocation, $logfilelocation, $tempfilelocation;
	DeleteFilesOlderThan($historyfilelocation, "*.*", 10);
	DeleteFilesOlderThan($deletefilelocation, "*.*", 30);
	DeleteFilesOlderThan($logfilelocation, "*.*", 10);
	DeleteFilesOlderThan($tempfilelocation, "*.*", 5);
}

function DeleteFilesOlderThan($dir , $files2delete, $ageInDays) {
	global 	$RKRKSiteStatsCSV, $RKRKIPStatsCSV;

	$filenames2delete = $dir. $files2delete;
	$files = glob($filenames2delete); // To get a list of the files in the images directory
	//echo "<br>AgeInDays: " . $ageInDays;
	
	//pre_r($files);
	//delete files //
	foreach($files as $file){ // To iterate through the files
		$now = strtotime("now");
		
		$filemtime=filemtime ($file);
		$diff = time()-filemtime($file);
		$days = round($diff/86400);
		
		//echo "Filetime: " .$filemtime . " - DeleteTime:  ". 60 * 60 * 24 * $ageInDays . " delta: " . (($now - $filemtime)/(60 * 60 * 24)) . " delete:" . ($now - $filemtime >= 60 * 60 * 24 * $ageInDays) . " - " . ($now - $filemtime >= 60 * 60 * 24 * $ageInDays) ."<br>";
		
		if(is_file($file)) {  //To check whether the filename is valid
			// files nicht zu löschen
			//echo "Basefilname : " . basename($file) . " - " . $RKRKSiteStatsCSV . "<br>";
			//echo "Basefilname : " . basename($file) . " - " . $RKRKIPStatsCSV . "<br>";
			if((basename($file)!= $RKRKSiteStatsCSV) and (basename($file)!= $RKRKIPStatsCSV)){
			 
				if ($now - $filemtime >= 60 * 60 * 24 * $ageInDays) { // 2 days
					unlink($file); // To delete the file
					//echo "deleted " . $file . "<br>";
				}
			}
		}
	}
}

//Get Corefilename
Function GetCoreFilename($Filename, $newKatID="") {
	global $OptionFlughoehe, $OptionPilot; 

	$FilenameSplit = explode("_",$Filename);
	if ($newKatID!="") {
		$KatID = $newKatID;
	} else {
		$KatID = $FilenameSplit[0];
	}

	Return $KatID . "_"  . GetMeaningFilename($Filename)[7];

}
Function ChangeHeightInFilename($Filename, $NewHeight, $ToDrone, $WithExtension=false) {
	$MeaningFileName = GetMeaningFilename(basename($Filename));
	$ext = pathinfo($Filename, PATHINFO_EXTENSION);
	//echo " Filename " . basename($Filename) . "/" . ($MeaningFileName[4]/5) . " - " . round($MeaningFileName[4]/5,0) . "<br>";
	if (((($MeaningFileName[4]??0)/5) == round(($MeaningFileName[4]??0)/5,0)) and ($MeaningFileName[4]??0)>0) {
		$NewFilename = $MeaningFileName[0] .  "_" . $MeaningFileName[7] . "_" . $NewHeight . "_" . $MeaningFileName[5] . "_" . $MeaningFileName[6];
	} else {
		$NewFilename = pathinfo($Filename,PATHINFO_FILENAME)."_". $ToDrone."_H".$NewHeight;
	}
	
	if ($WithExtension) {
		Return $NewFilename.".".$ext;
	} else {
		Return $NewFilename;
	}
}	

//Get Filname Splitted
Function GetMeaningFilename($Filename) {
	// [0] KatID
	// [1] Feldname
	// [2] Flurname
	// [3] GeoID
	// [4] Heigth
	// [5] Pilot
	// [6] Year
	// [7] Corefilename
	
	global $OptionFlughoehe, $OptionPilot; 
	$OptionYear = Array("18","19","20","21","22","23","24","25","26","27");
	$MeaningFilename=Array('', '', '', '', '', '', '', '');
	if (is_array($Filename)) {
		$Filename = reset($Filename);
	}
	if (!is_string($Filename) && !is_numeric($Filename)) {
		$Filename = '';
	}
	$Filename = trim((string)$Filename);
	if ($Filename === '') {
		return $MeaningFilename;
	}
	$PathInfo = pathinfo($Filename);
	$FilenameSplit = explode("_",$PathInfo["filename"]);
	// if ($newKatID!="") {
	// 	$KatID = $newKatID;
	// } else {
	$KatID = $FilenameSplit[0];
	// }

	//pre_r($FilenameSplit);
	
	$PilotList = Array();
	if (!is_array($OptionPilot)) {
		$OptionPilot = array();
	}
	foreach ($OptionPilot as $Pilot) {
	  if (is_array($Pilot)) {
		  $PilotList[]= (string)($Pilot["Opt"] ?? '');
	  } else {
		  $PilotList[]= (string)$Pilot;
	  }
	}

	$HightList = Array();
	if (!is_array($OptionFlughoehe)) {
		$OptionFlughoehe = array();
	}
	foreach ($OptionFlughoehe as $Hight) {
	  if (is_array($Hight)) {
		  $HightList[]= (string)($Hight["Opt"] ?? '');
	  } else {
		  $HightList[]= (string)$Hight;
	  }
	}
    $HightList[]= "00";

	$filenameSet = false;
	
	//	pre_R($HightList);
	
	// Check if standard filename used
	// Height in POS 3, Pilot in POS 4
	if ((!$filenameSet) and
		(in_array($FilenameSplit[3]??'', $HightList)and
		in_array($FilenameSplit[4]??'', $PilotList))
		)
	{
		//echo "Fileoption: Height in POS 3, Pilot in POS 4 <br>";
		$filenameSet = true;
		$MeaningFilename[0] =  $FilenameSplit[0];
		$MeaningFilename[1] =  $FilenameSplit[1];
		$MeaningFilename[2] =  "";
		$MeaningFilename[3] =  $FilenameSplit[2];
		$MeaningFilename[4] =  $FilenameSplit[3];
		$MeaningFilename[5] =  $FilenameSplit[4];
		$MeaningFilename[6] =  substr((string)($FilenameSplit[5] ?? ''),0,2);
		$MeaningFilename[7] =  $FilenameSplit[1] . "_" . $FilenameSplit[2];
	}
	// Check if standard filename  mixed
	// Height in POS 4, Pilot in POS 3

	if ((!$filenameSet) and 
		(in_array($FilenameSplit[4]??'', $HightList) and
		in_array($FilenameSplit[3]??'', $PilotList))
		)
	{
		//echo "Fileoption: Height in POS 4, Pilot in POS 3 <br>";
		$filenameSet = true;
		$MeaningFilename[0] =  $FilenameSplit[0];
		$MeaningFilename[1] =  $FilenameSplit[1];
		$MeaningFilename[2] =  "";
		$MeaningFilename[3] =  $FilenameSplit[2];
		$MeaningFilename[4] =  $FilenameSplit[4];
		$MeaningFilename[5] =  $FilenameSplit[3];
		$MeaningFilename[6] =  substr((string)($FilenameSplit[5] ?? ''),0,2);
		$MeaningFilename[7] =  $FilenameSplit[1] . "_" . $FilenameSplit[2];
	}

	// Check with offset 1 filename used
	// Height in POS 4, Pilot in POS 5
	if ((!$filenameSet) and
		(in_array($FilenameSplit[4]??'', $HightList) and
		in_array($FilenameSplit[5]??'', $PilotList))
		)
	{
		//echo "Fileoption: Height in POS 4, Pilot in POS 5 <br>";
		$filenameSet = true;
		$MeaningFilename[0] =  $FilenameSplit[0];
		$MeaningFilename[1] =  $FilenameSplit[1];
		$MeaningFilename[2] =  $FilenameSplit[2];
		$MeaningFilename[3] =  $FilenameSplit[3];
		$MeaningFilename[4] =  $FilenameSplit[4];
		$MeaningFilename[5] =  $FilenameSplit[5];
		$MeaningFilename[6] =  substr((string)($FilenameSplit[6] ?? ''),0,2);
		//$MeaningFilename[7] =  $FilenameSplit[1] . "_" . $FilenameSplit[2] . "_" . $FilenameSplit[3];
		$MeaningFilename[7] =  $FilenameSplit[1] . "_" . $FilenameSplit[3];
	}

	// Check with offset 1 filename mixed
	// Height in POS 5, Pilot in POS 4
	if ((!$filenameSet) and
		(in_array($FilenameSplit[5]??'', $HightList) and
		in_array($FilenameSplit[4]??'', $PilotList))
		)
	{
		//echo "Fileoption: Height in POS 5, Pilot in POS 4<br>";
		$filenameSet = true;
		$MeaningFilename[0] =  $FilenameSplit[0];
		$MeaningFilename[1] =  $FilenameSplit[1];
		$MeaningFilename[2] =  $FilenameSplit[2];
		$MeaningFilename[3] =  $FilenameSplit[4];
		$MeaningFilename[4] =  $FilenameSplit[5];
		$MeaningFilename[5] =  $FilenameSplit[4];
		$MeaningFilename[6] =  substr((string)($FilenameSplit[6] ?? ''),0,2);
		//$MeaningFilename[7] =  $FilenameSplit[1] . "_" . $FilenameSplit[2] . "_" . $FilenameSplit[3];
		$MeaningFilename[7] =  $FilenameSplit[1] . "_" . $FilenameSplit[3];
	}

	// Check with offset 2 filename used
	// Height in POS 5, Pilot in POS 6
	if ((!$filenameSet) and
		(in_array($FilenameSplit[5]??'', $HightList) and
		in_array($FilenameSplit[6]??'', $PilotList))
		)
	{
		$filenameSet = true;
		$MeaningFilename[0] =  $FilenameSplit[0];
		$MeaningFilename[1] =  $FilenameSplit[1];
		$MeaningFilename[2] =  $FilenameSplit[2] ."-" . $FilenameSplit[3];
		$MeaningFilename[3] =  $FilenameSplit[4];
		$MeaningFilename[4] =  $FilenameSplit[5];
		$MeaningFilename[5] =  $FilenameSplit[6];
		$MeaningFilename[6] =  substr((string)($FilenameSplit[7] ?? ''),0,2);
		//$MeaningFilename[7] =  $FilenameSplit[1] . "_" . $FilenameSplit[2] . "_" . $FilenameSplit[3];
		$MeaningFilename[7] =  $FilenameSplit[1] . "_" . $FilenameSplit[3];
	}

	// Check with offset 2 filename mixed
	// Height in POS 6, Pilot in POS 5
	if ((!$filenameSet) and
		(in_array($FilenameSplit[6]??'', $HightList) and
		in_array($FilenameSplit[5]??'', $PilotList))
		)
	{
		$filenameSet = true;
		$MeaningFilename[0] =  $FilenameSplit[0];
		$MeaningFilename[1] =  $FilenameSplit[1];
		$MeaningFilename[2] =  $FilenameSplit[2] ."-" . $FilenameSplit[3];
		$MeaningFilename[3] =  $FilenameSplit[4];
		$MeaningFilename[4] =  $FilenameSplit[6];
		$MeaningFilename[5] =  $FilenameSplit[5];
		$MeaningFilename[6] =  substr((string)($FilenameSplit[7] ?? ''),0,2);
		//$MeaningFilename[7] =  $FilenameSplit[1] . "_" . $FilenameSplit[2] . "_" . $FilenameSplit[3];
		$MeaningFilename[7] =  $FilenameSplit[1] . "_" . $FilenameSplit[3];
	}

	// Check with offset -1 filename used (no JG information)
	// Height in POS 2, Pilot in POS 3
	if ((!$filenameSet) and
		(in_array($FilenameSplit[2]??'', $HightList) and
		in_array($FilenameSplit[3]??'', $PilotList))
		)
	{
		$filenameSet = true;
		$MeaningFilename[0] =  "";
		$MeaningFilename[1] =  $FilenameSplit[0];
		$MeaningFilename[2] =  "";
		$MeaningFilename[3] =  $FilenameSplit[2];
		$MeaningFilename[4] =  $FilenameSplit[3];
		$MeaningFilename[5] =  $FilenameSplit[4];
		$MeaningFilename[6] = substr((string)($FilenameSplit[5] ?? ''),0,2);
		$MeaningFilename[7] =   $FilenameSplit[2] . "_" . $FilenameSplit[3];
	}

		// Check with offset -1 filename mixed (no JG information)
	// Height in POS 3, Pilot in POS 2

	if ((!$filenameSet) and
		(in_array($FilenameSplit[3]??'', $HightList) and
		in_array($FilenameSplit[2]??'', $PilotList))
		)
		
	{
		$filenameSet = true;
		$MeaningFilename[0] =  "";
		$MeaningFilename[1] =  $FilenameSplit[0];
		$MeaningFilename[2] =  "";
		$MeaningFilename[3] =  $FilenameSplit[3];
		$MeaningFilename[4] =  $FilenameSplit[2];
		$MeaningFilename[5] =  $FilenameSplit[4];
		$MeaningFilename[6] = substr((string)($FilenameSplit[5] ?? ''),0,2);
		$MeaningFilename[7] =  $FilenameSplit[2] . "_" . $FilenameSplit[3];
	}

	//check if year has right format otherwiese reset
	if (!in_array(trim($MeaningFilename[6]??''), $OptionYear)) {
		$MeaningFilename[6] = date("y");
	}
	
	//echo "<br>MeaningFilename" . $CoreFilename;
	//pre_r($MeaningFilename);
	Return $MeaningFilename;

}

Function CheckAccess ($AccessList) {
	global $WebFreigabe;
	if (function_exists("rkrk_check_release_or_user_access")) {
		return rkrk_check_release_or_user_access($AccessList, $WebFreigabe, "");
	}
	return ((strpos(" " . $AccessList, $WebFreigabe)!= false));
}


Function GetAccess ($FunctionKey) {
	global $AccessControl, $WebFreigabe;
	if (!is_array($AccessControl ?? null) || !isset($AccessControl[$FunctionKey]) || !is_array($AccessControl[$FunctionKey])) {
		return false;
	}
	$allowedUsers = "";
	if (isset($AccessControl[$FunctionKey]["users"])) {
		$allowedUsers = $AccessControl[$FunctionKey]["users"];
	} elseif (isset($AccessControl[$FunctionKey]["Benutzername"])) {
		$allowedUsers = $AccessControl[$FunctionKey]["Benutzername"];
	} elseif (isset($AccessControl[$FunctionKey]["allowed_users"])) {
		$allowedUsers = $AccessControl[$FunctionKey]["allowed_users"];
	}
	if (function_exists("rkrk_check_release_or_user_access")) {
		return rkrk_check_release_or_user_access($AccessControl[$FunctionKey]["release"], $WebFreigabe, $allowedUsers);
	}
	if ((strpos(" " . $AccessControl[$FunctionKey]["release"], $WebFreigabe)!= false)) {
		return true;
	}
	if ($allowedUsers === "") {
		return false;
	}
	if (session_status() === PHP_SESSION_NONE) {
		@session_start();
	}
	$currentUser = trim(strval($_SESSION["Benutzername"] ?? ""));
	if ($currentUser === "") {
		return false;
	}
	$list = array_map('trim', explode(',', strval($allowedUsers)));
	return in_array($currentUser, $list, true);
}

Function GetAccessByFunction ($FunctionKey) {
	global $MainFunctions;
	$currentUser = trim(strval($_SESSION["Benutzername"] ?? ""));
	//echo $MainFunctions[$FunctionKey]["users"] . "<br>";
	if ($currentUser === "") {
		return false;
	} else {
		return (in_array($currentUser, explode(",",$MainFunctions[$FunctionKey]["users"]), true));
	}
}

function GetFlightHeightForAltDrone($CurDrone, $NewDrone, $Flightheight, $Parameter="IRCamAngle_H", $roundto=5) {
	global $DroneSettings;
	
	if (array_key_exists($CurDrone,$DroneSettings) and array_key_exists($NewDrone,$DroneSettings)) {
		$HighFactor= (tan($DroneSettings[$CurDrone][$Parameter]/360*pi())/tan($DroneSettings[$NewDrone][$Parameter]/360*pi()));
		$NewFlightHeight = round(($HighFactor*$Flightheight)/$roundto,0)*$roundto;
		//echo $CurDrone .  "(".$DroneSettings[$CurDrone][$Parameter] . "°)" . " -> " . $NewDrone .  "(".$DroneSettings[$NewDrone][$Parameter] . "°)" ." => Factor " . round($HighFactor ,2) . " => Height: " . $Flightheight . "m -> " . $NewFlightHeight . "m";
	} else {
		$NewFlightHeight=0;
	}
	//echo (round(tan($DroneSettings[$CurDrone][$Parameter]/360*pi())/tan($DroneSettings[$NewDrone][$Parameter]/360*pi()),2)) . "  ";

	return $NewFlightHeight;
}

//TstCalcFlightHeight();
function TstCalcFlightHeight() {
	echo "Flughöhen:<br>";

	echo  "<br><br>"; 
/*
	echo "M3T -> 4TD". "<br>";
	echo "30 m: " . GetFlightHeightForAltDrone("3T", "4TD", 30) . "<br>";
	echo "40 m: " . GetFlightHeightForAltDrone("3T", "4TD", 40) . "<br>";
	echo "50 m: " . GetFlightHeightForAltDrone("3T", "4TD", 50) . "<br>";
	echo "60 m: " . GetFlightHeightForAltDrone("3T", "4TD", 60) . "<br>";
	echo "70 m: " . GetFlightHeightForAltDrone("3T", "4TD", 70) . "<br>";
	echo "80 m: " . GetFlightHeightForAltDrone("3T", "4TD", 80) . "<br>";
	echo "90 m: " . GetFlightHeightForAltDrone("3T", "4TD", 90) . "<br>";
	echo "M3T -> 30T". "<br>";
	echo "30 m: " . GetFlightHeightForAltDrone("3T", "30T", 30) . "<br>";
	echo "40 m: " . GetFlightHeightForAltDrone("3T", "30T", 40) . "<br>";
	echo "50 m: " . GetFlightHeightForAltDrone("3T", "30T", 50) . "<br>";
	echo "60 m: " . GetFlightHeightForAltDrone("3T", "30T", 60) . "<br>";
	echo "70 m: " . GetFlightHeightForAltDrone("3T", "30T", 70) . "<br>";
	echo "80 m: " . GetFlightHeightForAltDrone("3T", "30T", 80) . "<br>";
	echo "90 m: " . GetFlightHeightForAltDrone("3T", "30T", 90) . "<br>";
	echo "M4T -> 30T". "<br>";
	echo "30 m: " . GetFlightHeightForAltDrone("4T", "30T", 30) . "<br>";
	echo "50 m: " . GetFlightHeightForAltDrone("4T", "30T", 50) . "<br>";
	echo "55 m: " . GetFlightHeightForAltDrone("4T", "30T", 55) . "<br>";
	echo "60 m: " . GetFlightHeightForAltDrone("4T", "30T", 60) . "<br>";
	echo "70 m: " . GetFlightHeightForAltDrone("4T", "30T", 70) . "<br>";
	echo "80 m: " . GetFlightHeightForAltDrone("4T", "30T", 80) . "<br>";
	echo "85 m: " . GetFlightHeightForAltDrone("4T", "30T", 85) . "<br>";
	echo "90 m: " . GetFlightHeightForAltDrone("4T", "30T", 90) . "<br>";
*/
	echo "M3T -> 4TD". "<br>";
	echo GetFlightHeightForAltDrone("3T", "4TD", 30) . "<br>";
	echo GetFlightHeightForAltDrone("3T", "4TD", 40) . "<br>";
	echo GetFlightHeightForAltDrone("3T", "4TD", 50) . "<br>";
	echo GetFlightHeightForAltDrone("3T", "4TD", 60) . "<br>";
	echo GetFlightHeightForAltDrone("3T", "4TD", 70) . "<br>";
	echo GetFlightHeightForAltDrone("3T", "4TD", 80) . "<br>";
	echo GetFlightHeightForAltDrone("3T", "4TD", 90) . "<br>";
	echo "M3T -> 30T". "<br>";
	echo GetFlightHeightForAltDrone("3T", "30T", 30) . "<br>";
	echo GetFlightHeightForAltDrone("3T", "30T", 40) . "<br>";
	echo GetFlightHeightForAltDrone("3T", "30T", 50) . "<br>";
	echo GetFlightHeightForAltDrone("3T", "30T", 60) . "<br>";
	echo GetFlightHeightForAltDrone("3T", "30T", 70) . "<br>";
	echo GetFlightHeightForAltDrone("3T", "30T", 80) . "<br>";
	echo GetFlightHeightForAltDrone("3T", "30T", 90) . "<br>";
	echo "M4T -> 30T". "<br>";
	echo GetFlightHeightForAltDrone("4T", "30T", 30) . "<br>";
	echo GetFlightHeightForAltDrone("4T", "30T", 50) . "<br>";
	echo GetFlightHeightForAltDrone("4T", "30T", 55) . "<br>";
	echo GetFlightHeightForAltDrone("4T", "30T", 60) . "<br>";
	echo GetFlightHeightForAltDrone("4T", "30T", 70) . "<br>";
	echo GetFlightHeightForAltDrone("4T", "30T", 80) . "<br>";
	echo GetFlightHeightForAltDrone("4T", "30T", 85) . "<br>";
	echo GetFlightHeightForAltDrone("4T", "30T", 90) . "<br>";

}
	
function in_array_all($needles, $haystack) {
	//pre_R($needles);
	//pre_R($haystack);
	//pre_R(array_diff($needles, $haystack));
	return empty(array_diff($needles, $haystack));
}


function FormatWeatherDataArray($data) {
	foreach ($data as $key => $entry) {
		//echo $entry["Datum"] . "<br>";
		$data[$entry["Datum"]] = $entry;
		unset($data[$key]);
	}	
	array_multisort(array_column($data, 'Datum'), SORT_ASC, $data);
	return $data;
}

function rkrk_wx_home_http_get_json($url, &$httpCode=null, &$errorMsg=null) {
	$raw = '';
	$httpCode = null;
	$errorMsg = '';

	if (function_exists('curl_init')) {
		$ch = curl_init($url);
		curl_setopt_array($ch, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_CONNECTTIMEOUT => 8,
			CURLOPT_TIMEOUT => 15,
			CURLOPT_HTTPHEADER => ['Accept: application/json', 'User-Agent: RKRK-Weather/1.0'],
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
		]);
		$raw = curl_exec($ch);
		if ($raw === false) {
			$errorMsg = 'curl_error: ' . curl_error($ch);
		}
		$httpCode = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
		$ok = ($raw !== false && $httpCode >= 200 && $httpCode < 300);
		curl_close($ch);
		if ($ok) {
			$j = json_decode($raw, true);
			if (!is_array($j)) $errorMsg = 'curl_json_decode_failed';
			return is_array($j) ? $j : null;
		}
	}

	$ctx = stream_context_create([
		'http' => [
			'timeout' => 15,
			'header' => "User-Agent: RKRK-Weather/1.0\r\nAccept: application/json\r\n",
		]
	]);
	$streamErr = '';
	set_error_handler(function($errno, $errstr) use (&$streamErr) {
		$streamErr = (string)$errstr;
		return true;
	});
	$raw = file_get_contents($url, false, $ctx);
	restore_error_handler();
	if ($raw === false && $streamErr !== '') {
		$errorMsg = 'stream_error: ' . $streamErr;
	}
	if (is_array($http_response_header ?? null)) {
		foreach ($http_response_header as $h) {
			if (preg_match('/^HTTP\/\S+\s+(\d{3})/i', (string)$h, $m)) {
				$httpCode = intval($m[1]);
				break;
			}
		}
	}
	if (!is_string($raw) || trim($raw) === '') return null;
	$j = json_decode($raw, true);
	if (!is_array($j) && $errorMsg === '') $errorMsg = 'stream_json_decode_failed';
	return is_array($j) ? $j : null;
}

function rkrk_wx_home_code_to_text($code) {
	$c = intval($code);
	// Nur definierte Wettertexte aus $WeatherOptions verwenden.
	// Detailliertere Abstufung je Open-Meteo WMO code.
	switch ($c) {
		// Klar / Wolken
		case 0: return 'Sonne';
		case 1: return 'leicht bewölkt';
		case 2: return 'teilweise sonnig';
		case 3: return 'bewölkt';

		// Nebel
		case 45:
		case 48:
			return 'Nebel';

		// Niesel / leichter Niederschlag -> wechselhaft
		case 51:
		case 53:
		case 55:
		case 56:
		case 57:
			return 'wechselhaft';

		// Regen (inkl. gefrierender Regen / Schauer)
		case 61:
		case 63:
		case 65:
		case 66:
		case 67:
		case 80:
		case 81:
		case 82:
			return 'Regen';

		// Schnee
		case 71:
		case 73:
		case 75:
		case 77:
		case 85:
		case 86:
			return 'Schnee';

		// Gewitter
		case 95:
			return 'Gewitter';
		case 96:
		case 99:
			return 'Gewitter und Regen';

		default:
			return 'wechselhaft';
	}
}

/** Geschätzte Wolkenuntergrenze (LCL) in m AGL – wie Drohnen-Checkliste / rkrk_wetter.php */
function rkrk_wx_est_cloud_base_agl_m($tempC, $dewC) {
	if (!is_numeric($tempC) || !is_numeric($dewC)) {
		return null;
	}
	return max(0, floatval($tempC) - floatval($dewC)) * 125;
}

/**
 * Wettertext für Homepage: WMO + Bewölkung/Niederschlag (wie rkrk_wetter.php weatherPresentation).
 * Liefert immer einen $WeatherOptions-Text.
 */
function rkrk_wx_home_presentation_text($code, $cloudPct = null, $precipPct = null, $tempC = null, $dewC = null) {
	$base = rkrk_wx_home_code_to_text($code);
	$c = intval($code);
	$precip = is_numeric($precipPct) ? floatval($precipPct) : null;
	$cloud = is_numeric($cloudPct) ? floatval($cloudPct) : null;
	$dry = ($precip !== null && $precip < 20);
	$baseM = rkrk_wx_est_cloud_base_agl_m($tempC, $dewC);

	if ($dry && $c >= 51) {
		return ($cloud !== null && $cloud >= 60) ? 'teilweise sonnig' : 'leicht bewölkt';
	}
	if ($dry && $c === 3) {
		if ($baseM !== null && $baseM > 150) {
			return 'leicht bewölkt';
		}
		return 'teilweise sonnig';
	}
	if ($dry && $c === 2 && $cloud !== null && $cloud < 45) {
		return 'leicht bewölkt';
	}
	return $base;
}

/** Stundenindex in Open-Meteo hourly.time für lokales Datum + Stunde (z. B. 09:00). */
function rkrk_wx_hourly_index_for_day_hour($day, $hourInt, $hourlyTimes) {
	if (!is_array($hourlyTimes) || trim((string)$day) === '') {
		return -1;
	}
	$hh = str_pad((string)intval($hourInt), 2, '0', STR_PAD_LEFT);
	$candidates = array($day . 'T' . $hh . ':00', $day . 'T' . $hh . ':00:00');
	foreach ($candidates as $c) {
		$ix = array_search($c, $hourlyTimes, true);
		if ($ix !== false) {
			return intval($ix);
		}
	}
	$pfx = $day . 'T';
	foreach ($hourlyTimes as $i => $t) {
		$t = (string)$t;
		if (strpos($t, $pfx) !== 0) {
			continue;
		}
		if (preg_match('/T(\d{2}):/', $t, $m) && $m[1] === $hh) {
			return intval($i);
		}
	}
	return -1;
}

function rkrk_save_weather_csv($rows, $filepath, $delimiter = ';', $csvFile = '') {
	if (!is_array($rows) || count($rows) === 0 || trim((string)$filepath) === '') return false;
	$firstRow = reset($rows);
	if (!is_array($firstRow) || count($firstRow) === 0) return false;
	if (!function_exists('SaveDataToCSV')) return false;

	$targetFile = trim((string)$csvFile);
	if ($targetFile === '') $targetFile = basename((string)$filepath);

	$dir = dirname($filepath);
	if (!is_dir($dir)) {
		@mkdir($dir, 0777, true);
	}

	$tableSetup = "";
	SaveDataToCSV($rows, $tableSetup, $filepath, "UTF-8", true, $targetFile, $delimiter, false);
	clearstatcache(true, $filepath);
	return file_exists($filepath) && filesize($filepath) > 0;
}

function rkrk_get_weather_csv_path($year=null, $forceMainLocation=true) {
	global $RKRKWeatherCSV, $csvDocLocation;
	$y = intval($year);
	if ($y < 2000 || $y > 2100) {
		$y = intval(date("Y"));
	}
	$base = (string)$csvDocLocation;
	if ($forceMainLocation) {
		$base = str_replace("Missionen Debug/", "Missionen/", $base);
		$base = str_replace("Missionen Debug\\", "Missionen\\", $base);
	}
	$path = $base . $y . $RKRKWeatherCSV;
	// Resolve relative paths against project root (test/) to ensure writes go
	// to the same CSV regardless of webserver working directory.
	if (!preg_match('/^[A-Za-z]:[\/\\\\]/', $path) && strpos($path, '/') !== 0 && strpos($path, '\\\\') !== 0) {
		$root = dirname(__DIR__); // .../rkropen/test
		$path = $root . DIRECTORY_SEPARATOR . str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $path);
	}
	return $path;
}

function RefreshWeatherForecastCsv($days=14, $lat=47.1724, $lon=8.5174, $timezone='Europe/Zurich', $year=null) {
	global $RKRKWeatherCSV, $csvDocLocation, $rkrk_weather_refresh_debug;

	$days = intval($days);
	if ($days <= 0) $days = 14;
	if ($days > 16) $days = 16; // Open-Meteo Limit

	$yearInt = intval($year);
	if ($yearInt < 2000 || $yearInt > 2100) {
		$yearInt = intval(date("Y"));
	}
	$csv_file = $yearInt . $RKRKWeatherCSV;
	$csv_path = rkrk_get_weather_csv_path($yearInt);
	$csv_delimiter = ";";
	$rkrk_weather_refresh_debug = [
		'csv_path' => $csv_path,
		'api_ok' => false,
		'write_ok' => false,
		'http_code' => null,
		'http_error' => '',
		'rows_before' => 0,
		'rows_after' => 0,
		'reason' => ''
	];

	$existing = [];
	if (file_exists($csv_path)) {
		$tmp = csv_to_array($csv_path, $csv_delimiter);
		if (is_array($tmp)) $existing = $tmp;
	}
	$rkrk_weather_refresh_debug['rows_before'] = is_array($existing) ? count($existing) : 0;

	$baseQs = "?latitude=" . rawurlencode((string)$lat)
		. "&longitude=" . rawurlencode((string)$lon)
		. "&timezone=" . rawurlencode((string)$timezone)
		. "&forecast_days=" . rawurlencode((string)$days);
	$hourlyVars = "weather_code,temperature_2m,dew_point_2m,precipitation_probability,cloud_cover";
	$dailyVars = "weather_code,temperature_2m_max";
	$urls = array(
		"https://api.open-meteo.com/v1/forecast" . $baseQs . "&daily=" . $dailyVars . "&hourly=" . $hourlyVars,
		"https://api.open-meteo.com/v1/forecast" . $baseQs . "&daily=weathercode,temperature_2m_max&hourly=weathercode,temperature_2m,dew_point_2m,precipitation_probability,cloud_cover",
		"http://api.open-meteo.com/v1/forecast" . $baseQs . "&daily=" . $dailyVars . "&hourly=" . $hourlyVars,
		"http://api.open-meteo.com/v1/forecast" . $baseQs . "&daily=weathercode,temperature_2m_max&hourly=weathercode,temperature_2m,dew_point_2m,precipitation_probability,cloud_cover",
	);

	$j = null;
	$lastErr = '';
	$lastCode = null;
	foreach ($urls as $tryUrl) {
		$httpCodeTry = null;
		$httpErrorTry = '';
		$jTry = rkrk_wx_home_http_get_json($tryUrl, $httpCodeTry, $httpErrorTry);
		$lastCode = $httpCodeTry;
		$lastErr = $httpErrorTry;
		if (is_array($jTry) && isset($jTry['daily']['time']) && is_array($jTry['daily']['time'])) {
			$j = $jTry;
			$rkrk_weather_refresh_debug['http_code'] = $httpCodeTry;
			$rkrk_weather_refresh_debug['http_error'] = $httpErrorTry;
			$rkrk_weather_refresh_debug['api_url'] = $tryUrl;
			break;
		}
	}
	if (!is_array($j)) {
		$rkrk_weather_refresh_debug['http_code'] = $lastCode;
		$rkrk_weather_refresh_debug['http_error'] = $lastErr;
	}
	if (!is_array($j) || !isset($j['daily']['time']) || !is_array($j['daily']['time'])) {
		$rkrk_weather_refresh_debug['reason'] = 'api_failed_or_empty';
		return FormatWeatherDataArray($existing);
	}
	$rkrk_weather_refresh_debug['api_ok'] = true;

	$times = $j['daily']['time'];
	$codes = $j['daily']['weathercode'] ?? ($j['daily']['weather_code'] ?? []);
	$temps = $j['daily']['temperature_2m_max'] ?? [];
	$h = $j['hourly'] ?? array();
	$hTimes = $h['time'] ?? array();
	$hCodes = $h['weathercode'] ?? ($h['weather_code'] ?? array());
	$hCloud = $h['cloud_cover'] ?? array();
	$hPrecip = $h['precipitation_probability'] ?? array();
	$hTemp = $h['temperature_2m'] ?? array();
	$hDew = $h['dew_point_2m'] ?? array();
	$wxHour = 9;

	$byDate = [];
	foreach ($existing as $row) {
		$d = trim((string)($row['Datum'] ?? ''));
		if ($d !== '') $byDate[$d] = $row;
	}

	for ($i = 0; $i < count($times); $i++) {
		$date = (string)$times[$i];
		if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) continue;
		// Immer Forecast-Tage aktualisieren/neu anlegen.

		$row = $byDate[$date] ?? [];
		$row['Datum'] = $date;
		$hi = rkrk_wx_hourly_index_for_day_hour($date, $wxHour, $hTimes);
		$code = ($hi >= 0 && isset($hCodes[$hi])) ? $hCodes[$hi] : ($codes[$i] ?? null);
		$cloud = ($hi >= 0 && isset($hCloud[$hi])) ? $hCloud[$hi] : null;
		$precip = ($hi >= 0 && isset($hPrecip[$hi])) ? $hPrecip[$hi] : null;
		$hourTemp = ($hi >= 0 && isset($hTemp[$hi])) ? $hTemp[$hi] : null;
		$hourDew = ($hi >= 0 && isset($hDew[$hi])) ? $hDew[$hi] : null;
		$row['Wetter'] = rkrk_wx_home_presentation_text($code, $cloud, $precip, $hourTemp, $hourDew);

		$temp = $temps[$i] ?? '';
		if (is_numeric($temp)) {
			$row['Temp'] = (string)round(floatval($temp));
		} else {
			$row['Temp'] = '';
		}
		$byDate[$date] = $row;
	}

	$out = array_values($byDate);
	$out = FormatWeatherDataArray($out);
	$writeOk = rkrk_save_weather_csv($out, $csv_path, $csv_delimiter, $csv_file);
	$rkrk_weather_refresh_debug['write_ok'] = (bool)$writeOk;
	$rkrk_weather_refresh_debug['rows_after'] = count($out);
	if (!$writeOk) $rkrk_weather_refresh_debug['reason'] = 'write_failed';
	return $out;
}


function FormatFlugDataArray($data) {
	global $MaxFlugID;
	$MaxFlugID = 0;
	if (!is_array($data)) {
		return array();
	}
	foreach ($data as $key => $entry) {
		//echo $entry["FlugID"] . "<br>";
		$data[$entry["FlugID"]] = $entry;
		unset($data[$key]);
		if ($MaxFlugID < strval(substr($entry["FlugID"],-6, 6)))
			$MaxFlugID= strval(substr($entry["FlugID"],-6, 6));
	}
	array_multisort(
			array_column($data, 'Datum'), SORT_ASC,
			array_column($data, 'Pilot'), SORT_ASC,
			array_column($data, 'Zeit'),  SORT_ASC,
			array_column($data, 'Feld'),  SORT_ASC,
			$data);
	return $data;
}

function multiexplode ($delimiters,$string) {

    $ready = str_replace($delimiters, $delimiters[0], $string);
    $launch = explode($delimiters[0], $ready);
    return  $launch;
}

/**
 * Skalar aus Feldkatalog/CSV (inkl. {"0":"…"}-Artefakte) als Plain-String.
 */
function rkrk_feld_scalar_text($value) {
	if ($value === null) {
		return '';
	}
	if (is_array($value)) {
		if (isset($value[0]) && !is_array($value[0])) {
			return rkrk_feld_scalar_text($value[0]);
		}
		if (count($value) === 1) {
			return rkrk_feld_scalar_text(reset($value));
		}
		return '';
	}
	if ($value instanceof SimpleXMLElement) {
		return trim((string) $value);
	}
	$text = trim((string) $value);
	if ($text !== '' && $text[0] === '{') {
		$decoded = json_decode($text, true);
		if (is_array($decoded)) {
			if (isset($decoded[0]) && !is_array($decoded[0])) {
				return trim((string) $decoded[0]);
			}
			if (count($decoded) === 1) {
				return trim((string) reset($decoded));
			}
		}
	}
	return $text;
}

function rkrk_feld_jg_from_feld_id($feldId) {
	$feldId = trim((string)$feldId);
	if ($feldId === '') {
		return '';
	}
	$pos = strpos($feldId, '-');
	if ($pos === false) {
		return $feldId;
	}
	return substr($feldId, 0, $pos);
}

/**
 * Region-Auswahl (21, Amt, ALL …) in Jagdbezirk-Liste für KML/Map auflösen.
 * null = alle Regionen.
 */
function rkrk_map_region_jg_list($regionSelected) {
	global $RegionSettings;
	$regionSelected = trim(str_replace('_', ' ', (string)$regionSelected));
	if ($regionSelected === '' || strcasecmp($regionSelected, 'ALL') === 0 || strcasecmp($regionSelected, 'alle') === 0) {
		return null;
	}
	if (strcasecmp($regionSelected, 'leer') === 0 || strcasecmp($regionSelected, 'NO') === 0) {
		return array();
	}
	$tokens = preg_split('/[\s,;]+/', $regionSelected, -1, PREG_SPLIT_NO_EMPTY);
	$jgs = array();
	foreach ($tokens as $token) {
		$token = trim($token);
		if ($token === '') {
			continue;
		}
		if (isset($RegionSettings[$token]['JGList'])) {
			foreach (preg_split('/[\s,;]+/', (string)$RegionSettings[$token]['JGList'], -1, PREG_SPLIT_NO_EMPTY) as $jg) {
				$jg = trim($jg);
				if ($jg !== '') {
					$jgs[$jg] = $jg;
				}
			}
		} else {
			$jgs[$token] = $token;
		}
	}
	return array_values($jgs);
}

/**
 * UTF-8-Text für FPDF (Core-Fonts / ISO-8859-1) aufbereiten.
 */
function rkrk_pdf_text($text) {
	if ($text === null || $text === '') {
		return '';
	}
	$text = rkrk_feld_scalar_text($text);
	if ($text === '') {
		return '';
	}
	if (function_exists('mb_check_encoding') && !mb_check_encoding($text, 'UTF-8')) {
		$text = mb_convert_encoding($text, 'UTF-8', 'Windows-1252,ISO-8859-1,UTF-8');
	}
	if (function_exists('mb_convert_encoding')) {
		$converted = @mb_convert_encoding($text, 'ISO-8859-1', 'UTF-8');
		if ($converted !== false) {
			return $converted;
		}
	}
	if (function_exists('iconv')) {
		$converted = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text);
		if ($converted !== false) {
			return $converted;
		}
	}
	return function_exists('utf8_decode') ? utf8_decode($text) : $text;
}

/**
 * Mission-Datei (jpg/kml/…) per relativem Pfad finden – unabhängig vom PHP-CWD.
 */
function rkrk_resolve_mission_file_path($relativePath) {
	global $RKRKLocalLoactionMissionFiles, $MissionLocation;
	$relativePath = trim(str_replace('\\', '/', rkrk_feld_scalar_text($relativePath)), '/');
	if ($relativePath === '') {
		return '';
	}
	$candidates = array();
	if (!empty($RKRKLocalLoactionMissionFiles)) {
		$candidates[] = $RKRKLocalLoactionMissionFiles . $relativePath;
	}
	$rkropenRoot = realpath(dirname(__DIR__, 2));
	if ($rkropenRoot !== false) {
		if (!empty($MissionLocation)) {
			$candidates[] = $rkropenRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, trim($MissionLocation, '/') . '/' . $relativePath);
		}
		$tail = preg_replace('#^\.\./\.\./rkropen/#', '', str_replace('\\', '/', (string)$RKRKLocalLoactionMissionFiles));
		if ($tail !== '' && $tail !== (string)$RKRKLocalLoactionMissionFiles) {
			$candidates[] = $rkropenRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $tail . $relativePath);
		}
	}
	$seen = array();
	foreach ($candidates as $path) {
		$path = str_replace('/', DIRECTORY_SEPARATOR, $path);
		if (isset($seen[$path])) {
			continue;
		}
		$seen[$path] = true;
		if (is_file($path)) {
			return $path;
		}
		$real = realpath($path);
		if ($real !== false && is_file($real)) {
			return $real;
		}
	}
	return '';
}

function cleanString($text) {
	if ($text === null || $text === '') {
		return '';
	}
	$text = (string) $text;
	if (function_exists('mb_check_encoding') && !mb_check_encoding($text, 'UTF-8')) {
		$text = mb_convert_encoding($text, 'UTF-8', 'Windows-1252,ISO-8859-1,UTF-8');
	}
	// Französische/deutsche Buchstaben (é, É, à, È, ç, ä, ö, ü …) bleiben erhalten.
	$replacements = array(
		'/–/' => '-',
		'/°/' => 'Grad',
		'/[\'\‘\‹\›\‚]/u' => "'",
		'/[""«»„]/u' => '"',
		'/\x{00A0}/u' => ' ',
	);
	$text = preg_replace(array_keys($replacements), array_values($replacements), $text);
	// Steuerzeichen entfernen (XML-/Anzeige-Probleme), Buchstaben unverändert lassen.
	$text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);
	return $text;
} 


function MissionLibraryButtons($AddUrl="", $AsForm=true) {
	global 	$IconLoc,
			$ButtonHeight,
			$SessNme_MissionDBSetting,
			$UseSecUrl,
			$MissionDroneTypes,
			$WebFreigabe;
			
		$Output ="";	

//echo "Mission stored:" . $_SESSION[$SessNme_MissionDBSetting];
		
	if (GetAccess("InternLinks")) {
		$FirstInRow="";
		$NextMission="";
		$CurrentMission=$_SESSION[$SessNme_MissionDBSetting]; 

		//get next Mission according to access rights
		foreach ($MissionDroneTypes as $key => $Drone) {
			if ((strpos(" " . $Drone["release"], $WebFreigabe)!= false) and $FirstInRow=="" and $Drone["active"]) {
				$FirstInRow=$key;
			}	
			if ((strpos(" " . $Drone["release"], $WebFreigabe)!= false) and $ExitNext==true  and $Drone["active"]) {
				$NextMission=$key;
				 break; 
			}	
			if ($key==$_SESSION[$SessNme_MissionDBSetting]) {
				$ExitNext=true;
			}
		}
		if ($NextMission=="") {
			$NextMission=$FirstInRow;
		}
		//echo "next Mission:" .$NextMission . "/". $WebFreigabe. "<br>";
		
		if ($AsForm) {
			if ($UseSecUrl) {
				//encripted URL
				$Output .=  "<input type='image' value='MDB' src='" . $IconLoc . "/" . $MissionDroneTypes[$CurrentMission]["button"]. "' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?". base64_encode("MDB=". $NextMission . $AddUrl) ."' height='" . $ButtonHeight . "'  title='Klick zum welchseln der Datenbanken'  style='background-color:transparent; border-color:transparent;vertical-align: middle;'>" .chr(10);
			} else {
				//cleartext URL
				$Output .=  "<input type='image' value='MDB' src='" . $IconLoc . "/" . $MissionDroneTypes[$CurrentMission]["button"]. "' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?"."MDB=". $NextMission. $AddUrl ."' height='" . $ButtonHeight . "'  title='Klick zum welchseln der Datenbanken'  style='background-color:transparent; border-color:transparent;vertical-align: middle;'>" .chr(10);
			}
		} else {
			if ($UseSecUrl) {
				//encripted URL
				$Output .= "<a href='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?". base64_encode("MDB=". $NextMission . $AddUrl) ."'><image src='" . $IconLoc . "/" . $MissionDroneTypes[$CurrentMission]["button"]. "' height='" . $ButtonHeight . "' title='Klick zum welchseln der MissionsDatenbank'></a>";
			} else {
				//cleartext URL
				$Output .= "<a href='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?"."MDB=". $NextMission . $AddUrl ."'><image src='" . $IconLoc . "/" . $MissionDroneTypes[$CurrentMission]["button"]. "' height='" . $ButtonHeight . "' title='Klick zum welchseln der MissionsDatenbank'></a>";
			}
		}	

	} else {
		//only picture w/o link
		if ($_SESSION[$SessNme_MissionDBSetting]=="M2") {
			// Mavic II
			$Output .= "<image src='" . $IconLoc . "/mavic2_button.png' height='" . $ButtonHeight . "' title='MissionsDatenbank'>";
		} elseif ($_SESSION[$SessNme_MissionDBSetting]=="D") {
			// Debug
			$Output .= "<image src='" . $IconLoc . "/debug_button.png' height='" . $ButtonHeight . "' title='MissionsDatenbank'>";
		} else {
			// Default M3T / Mavic 3T
			$Output .= "<image src='" . $IconLoc . "/mavic3t_button.png' height='" . $ButtonHeight . "' title='MissionsDatenbank'>";
		}	
	}
	//if ($AsForm) $Output .=  '</form>' . chr(10);
	return $Output;
}

function GetSasionStartDate($Jahr)
{
	$FromDate = date("Y-m-d", strtotime("third monday " . $Jahr . "-04"));
	$AdjFromDate = date("Y-m-d", strtotime("last monday " . date()));
	//Adjust Date to current week
	if 	(($AdjFromDate>$FromDate) and ($AdjFromDate<$ToDate))
		$FromDate = $AdjFromDate;
	return $FromDate;
}

function GetSasionEndDate($Jahr)
{
	$ToDate = date("Y-m-d",strtotime("third sunday " . $Jahr . "-07"));
	return $ToDate;
}

function GetWeatherIcon ($Text) {
	global $WeatherOptions;
	$Text = trim((string)$Text);
	foreach ($WeatherOptions as $key => $Option) {
		if (($Option["text"] ?? '') === $Text) {
			return $Option["Opt"];
		}
	}
	foreach ($WeatherOptions as $key => $Option) {
		if (strcasecmp((string)($Option["text"] ?? ''), $Text) === 0) {
			return $Option["Opt"];
		}
	}
	return "\u{2754}";
}

function unsetPOST($ListOfParameters="") {
	$ParameterArray = explode(",",$ListOfParameters);
	if (count($_POST) > 0) {
		if (count($ParameterArray) > 0) {
			//clean listed paramters in $_Post
			foreach ($ParameterArray as $k) {
				unset($_POST[$k]);
			}
		} else {	
			//clean all paramters in $_Post
			foreach ($_POST as $k=>$v) {
				unset($_POST[$k]);
			}
		}
	}
}

function ElementOfArrayInArray($Arr1, $Arr2) {
	// search if element from $Arr1 is in $Arr2
	$IsIn = false;
	//pre_r($Arr2);
	if (count($Arr1)>0) {
		//echo "Is Array";
		//ist ein Array
		foreach ($Arr1 as $Element) {
			//echo $Element . "<br>";
			$IsIn = $IsIn || in_Array($Element, $Arr2);
			if ($IsIn) {
				//echo "true";
				return true;
				exit;
			}	
		}	
		//echo "false";
		return false;
	} else  {
		//echo "No Array";
		return in_Array($Arr1, $Arr2);
	}
}	



// ===========================================
// url inputs
// ===========================================



function GetURLwDefault($SecParameter, $GetParameter, $PostParameter="", $Default="") {
global $SecUrl, $UseSecUrl, $EnforceUseSecUrl, $SecParamters;
	$Value = "";

	if ($SecUrl and isset($SecParamters[$SecParameter]) and rkrk_str($SecParamters[$SecParameter])<>"") {
		$Value= rkrk_html($SecParamters[$SecParameter]);
	} elseif (isset($_GET[$GetParameter]) and !$EnforceUseSecUrl) {
		$Value = rkrk_html($_GET[$GetParameter]);
	} elseif ((rkrk_str($PostParameter)!="") and (isset($_POST[$PostParameter]))) {
		$Value = rkrk_html($_POST[$PostParameter]);
	}
		
	if ($Value=="") {
		$Value = $Default;
	}
	return $Value;
}


function GetURLwDefault_Lower($SecParameter, $GetParameter, $PostParameter="", $Default="") {
global $SecUrl, $UseSecUrl, $EnforceUseSecUrl, $SecParamters;
	$Value = "";

	if ($SecUrl and isset($SecParamters[$SecParameter]) and rkrk_str($SecParamters[$SecParameter])<>"") {
		$Value= rkrk_lower($SecParamters[$SecParameter]);
	} elseif (isset($_GET[$GetParameter]) and !$EnforceUseSecUrl) {
		$Value = rkrk_lower($_GET[$GetParameter]);
	} elseif ((rkrk_str($PostParameter)!="") and (isset($_POST[$PostParameter]))) {
		$Value = rkrk_lower($_POST[$PostParameter]);
	}
	
	if ($Value=="") {
		$Value = $Default;
	}
	return $Value;
}


// ===========================================
// url inputs
// ===========================================
	
function GetURLtoFalseTrue($SecParameter, $GetParameter, $PostParameter="", $Default=false) {
global $SecUrl, $EnforceUseSecUrl, $SecParamters;
	//supporting on/off - true/false - "" = default
	if ($SecUrl and isset($SecParamters[$SecParameter]) and rkrk_str($SecParamters[$SecParameter])<>"") {
		$Value= rkrk_lower($SecParamters[$SecParameter]);
	} elseif (isset($_GET[$GetParameter]) and !$EnforceUseSecUrl) {
		$Value = rkrk_lower($_GET[$GetParameter]);
	} elseif ((rkrk_str($PostParameter)!="") and (isset($_POST[$PostParameter]))) {
		$Value = rkrk_lower($_POST[$PostParameter]);
	} else {
		$Value ="";
	}

	if ($Value=="") {
		//default
		$Value= $Default;
	} elseif (($Value=="off") or ($Value==false)) {
		$Value = false;
	} else {
		$Value = true;
	}
	return $Value;

}
?>
