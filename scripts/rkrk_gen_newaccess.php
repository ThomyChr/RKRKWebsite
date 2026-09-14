<?php
  session_start();
  include_once(__DIR__ . '/rkrk_php80.php');
  //header('Access-Control-Allow-Origin: *');
  include_once('rkrk_ipregister.php');
	//include_once('rkrk_access_control.php');

// Mission settings
$SessNme_MissionDBSetting="MissionDBSetting";

//===========================
// Cookie Handling
//===========================
$SessionKey = "RKRK";

Function WriteSessionVar($Cname, $Value) {
	global $SessionKey;
	$_SESSION[$SessionKey][$Cname]=$Value;
}

Function GetSessionVar($Cname) {
	global $SessionKey;
	Return $_SESSION[$SessionKey][$Cname];
}

//============================
// Secure URL
// ===========================
$SecUrl=false;
$UseSecUrl=false;  // prefernce to use encoded SecURL where implemented, does not block $_GET
$EnforceUseSecUrl=false; // to enforce use of encoded URL, does not allow anymore the use of standard $_GET

if (is_urlEncoded($_SERVER['REQUEST_URI'])) {
	$SecParamters = GetParamters_from_Encoded_Url($_SERVER['REQUEST_URI']);
	$SecUrl = true;
	//pre_R($SecParamters);
	//echo count($SecParamters);
}


//============================
// Layout
//============================

// GENERAL SWITCHES
// Layout: 
//	2022: PC layout
//	2023: optimiert für Mobile Phones und Tablets


//Layout
if (isset($_GET["Layout"])) {
	$Layout = $_GET["Layout"];
	WriteSessionVar("Layout",$Layout);	
} elseif (GetSessionVar("Layout")!="") {
	$Layout = GetSessionVar("Layout");
} else {
	$Layout = "2023";	
	WriteSessionVar("Layout",$Layout);
}
//echo "LayoutSetting: " . $Layout . "<br>";

//Layoutcolor
if ($SecUrl) 
	$BaseColor= strtolower($SecParamters["Color"]);
elseif (isset($_GET["Color"])) {
	$BaseColor = $_GET["Color"];
} else	{
	if ($Layout=="2023") {
		$BaseColor = 'white';
	} else {	
		$BaseColor = 'green';
	}
}
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


// ==================
// Generic Funktionen für die Rehktitzrettung
// ==================



Function SetMissionLocation() {
	global 	//$DebugWork, 
			$SessNme_MissionDBSetting,
			$MissionLocation, 
			$WebMissionLocation,  
			$RKRKMissionSelected,
			$RKRKLocalLoactionMissionFiles, 
			$RKRKLocalLoactionDocFiles,
			$RKRKExternalLoactionMissionFiles, 
			$RKRKExternalLoactionDocFiles, 
			$csvMissionLocation, 
			$csvDocLocation, 
			$xmlMissionLocation, 
			$xmlDocLocation, 
			$WebLocation, 
			$historyfilelocation, 
			$deletefilelocation, 
			$logfilelocation,
			$SecParamters,
			$SecUrl;
			
	//pre_R($SecParamters); 
	
	if ($SecUrl) 
		$RKRKMissionSelected= $SecParamters["MDB"];
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

	if (isset($_SESSION[$SessNme_MissionDBSetting]))  {
		$Debugoutput .=  $_SESSION[$SessNme_MissionDBSetting];
	}
	
	if ($RKRKMissionSelected == "3T") {
		//Mavic M3T
		$MissionLocation = "Missionen/";
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
	//if (isset($_SESSION[$SessNme_MissionDBSetting]))  {
	//	$Debugoutput .= " - SetTo: " . $_SESSION[$SessNme_MissionDBSetting] . "/ Missions: " . $MissionLocation . "/ Docs: " . $DocLocation . "<br>";
	//}
	//echo $Debugoutput;
/**/
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

	$rkrk_fix_path = function($p){
		$p = str_replace("\\", "/", (string)$p);
		while (strpos($p, "/rkropen/rkropen/") !== false) {
			$p = str_replace("/rkropen/rkropen/", "/rkropen/", $p);
		}
		return $p;
	};
	$RKRKLocalLoactionMissionFiles = $rkrk_fix_path('../../rkropen/' . $MissionLocation);
	$RKRKLocalLoactionDocFiles = $rkrk_fix_path('../../rkropen/' . $DocLocation);
	//$RKRKLocalLoactionMissionFiles = '../../../Rehkitzrettung/' . $MissionLocation; - 20221031 GEHT NICHT!!!
	$RKRKExternalLoactionMissionFiles = $WebLocation . $MissionLocation;
	$RKRKExternalLoactionDocFiles = $WebLocation . $DocLocation;
	$csvDocLocation = $rkrk_fix_path($RKRKLocalLoactionDocFiles . "csvfiles/");
	$csvMissionLocation = $rkrk_fix_path($RKRKLocalLoactionMissionFiles . "csvfiles/");
	$xmlDocLocation = $rkrk_fix_path($RKRKLocalLoactionDocFiles . "xmlfiles/");
	$xmlMissionLocation = $rkrk_fix_path($RKRKLocalLoactionMissionFiles . "xmlfiles/");
	$historyfilelocation = $rkrk_fix_path($RKRKLocalLoactionDocFiles . "_history/");
	$logfilelocation = $rkrk_fix_path($RKRKLocalLoactionDocFiles . "_log/");
	$deletefilelocation = $rkrk_fix_path($RKRKLocalLoactionDocFiles . "_deleted/");
}

// ===================
// Defintions
// ===================

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
	
	
	$ThisHost = $Con . $_SERVER['HTTP_HOST'];
	$ThisPage = $Con . $_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];
	$ThisPageURL = $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
	$ThisURL  = $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"];


	$colorTableEven = "#DEEADE";
	$MissionLocation = "";
	$WebMissionLocation  = "";
	$RKRKLocalLoactionMissionFiles = "";
	$RKRKLocalLoactionDocFiles = "";
	$RKRKExternalLoactionMissionFiles = "";
	$RKRKExternalLoactionDocFiles = "";
	$csvMissionLocation = "";
	$csvDocLocation ="";
	$xmlMissionLocation = "";
	$xmlDocLocation ="";
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
				
	$AccessLevels = Array("gast" => Array(	"key" => "gast",
											"directory" => "pub/",
											"picdir" => "../favicon/",
											"WebFreigabe" => "pub",
											),
						 "pub" => Array(	"key" => "pub",
											"directory" => "pub/",
											"picdir" => "../favicon/",
											"WebFreigabe" => "pub",
											),
						 "extern" => Array(	"key" => "extern",
											"directory" => "ex/",
											"picdir" => "../favicon/Reh_FA840B_orange/",
											"WebFreigabe" => "extern",
						 					),
						 "partner" => Array("key" => "partner",
											"directory" => "v5/",
											"picdir" => "../favicon/Reh_885833_brown/",
											"WebFreigabe" => "partner",
											), 
						 "intern" => Array(	"key" => "intern",
											"directory" => "v2/",
											"picdir" => "../favicon/Reh_AFCEEB_blue/",
											"WebFreigabe" => "intern",
											),
						"test" => Array(	"key" => "test",
											"directory" => "test/",
											"picdir" => "../favicon/Reh_FFABAB_red/",
											"WebFreigabe" => "test",
											),
						 );

	//pre_R($AccessLevels);

	//setpic directory
	$icondir="../favicon/";
	$picdir="../favicon/";
	$PStyle = "BW";
	$ImageLoc = "images/";
	if ($PStyle=="BW") {
		if ($Layout == "2023") {
			$IconLoc = $ImageLoc ."23color";
			$GenIconLoc = "../icon/23_color";
			$MapIconStyle = "icon/23_color/";
		} else {	
			$IconLoc = $ImageLoc ."22color";
			$GenIconLoc = "../icon/22_color";
			$MapIconStyle = "icon/22_color/";
		}
	} else {
		$IconLoc = $ImageLoc ."22color";
		$GenIconLoc = "../icon/22_color";
		$MapIconStyle = "icon/22_color/";
	}
	$WebLink_public = $WebLocation . "pub/";

	foreach ($AccessLevels as $key => $AcLevel) {
		if (strpos(strval($_SERVER['PHP_SELF']),$AcLevel["directory"]) != false) {
			//echo "AA:" . $_SERVER['PHP_SELF'] . "  " . $key . "  "  . $AcLevel["picdir"] . "<br>";
			$WebLink_intern = $WebLocation . $AcLevel["directory"];
			$picdir= $AcLevel["picdir"];
			$WebFreigabe = $AcLevel["WebFreigabe"]; // WebFreigabe wird durch rkrk_access_control.php überschrieben!!!
			$DebugWork= false;
			break;
		}
	}
	$GuestAccessParamter = "&GA=gast";

	$PicLoc = "../pics/";

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
									  "Access" => false,
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
									  "Access" => false,
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
									  "Access" => false,
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
									  "Access" => false,
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
									  "Access" => false,
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
									  "Access" => false,
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
									  "Access" => false,
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
									  "Access" => false,
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
									  "Access" => false,
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
									  "Access" => false,
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
									  "Access" => false,
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
									  "Access" => false,
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
									  "Access" => false,
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
									  "Access" => false,
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
									  "Access" => false,
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
									  "Access" => false,
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
									  "Access" => false,
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
									  "Access" => false,
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
									  "Access" => false,
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
									  "Access" => false,
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
									  "Access" => false,
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
									  "Access" => false,
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
									  "Access" => false,
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
									  "Gebiettyp" => "Support",
									  "NumEntries" => 0,
									  "MaxID" => 0,
									  "Access" => false,
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
									  "Access" => false,
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
									  "Access" => false,
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
									  "Access" => false,
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
									  "Access" => false,
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
									  "Access" => false,
									  "Aktiv" => false),
									  );

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
													"VisibleOn" => "test,intern,partner",
													"PV" => "tc-mxmi,tchr-mxmi",
													"plan" => true),
									"CH" => array( 	"Opt" => "CH",
													"Text" => "Chris Häfliger",
													"Access" => "intern,partner,extern,pub",
													"VisibleOn" => "test,intern,partner",
													"PV" => "ch-sk7q,chae-sk7q",
													"plan" => true),
									"VP" => array( 	"Opt" => "VP",
													"Text" => "Vesa Pajasmaa",
													"Access" => "intern,partner,extern,pub",
													"VisibleOn" => "test,intern,partner",
													"PV" => "vp-84m6",
													"plan" => true),
									"LZ" => array(	"Opt" => "LZ",
													"Text" => "Lucas Zingerli",
													"Access" => "partner,extern,pub",
													"VisibleOn" => "test,intern,partner",
													"PV" => "",
													"plan" => true),
									"MB" => array(	"Opt" => "MB",
													"Text" => "Marc Blunier",
													"Access" => "partner,extern,pub",
													"VisibleOn" => "test,intern,partner",
													"PV" => "mb-4lv7",
													"plan" => true),
									"SB" => array(	"Opt" => "SB",
													"Text" => "Sarina Blunier",
													"Access" => "partner,extern,pub",
													"VisibleOn" => "test,intern,partner",
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
									"AZ" => array(	"Opt" => "AZ",
													"Text" => "Adrian Zender",
													"Access" => "intern,extern,pub",
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

	// ===========================================
	// Gobal Defintions
	// ===========================================

	$PersonenDaten = Array("tc-mxmi" => 
							Array(	"Name" => "Christen",
									"Vorname" => "Thomas",
									"Tel" => "+41 79 510 42 65",
									"EASA_ID" => "CHE-RP-5c18p294mxmi",
									"EASA_BID" => "CHEclsc784sbqhxi",
									"EASA_exp" => "23-08-2026",
									"Kat" => "A1/A3,A2",
									"Pic" => "tc_face2.jpg",
									"QR" => "tc_easa_qr.png",
									"RKRS_MA" => "tc_mitgliederausweis_585.pdf",
									"Flugberechtigung" => "FGHO,FGHO_S,LSXN,RKRS",
									"FlugzonenOption" => "FHGO-NF2",
										//FHGO-NF1: FHGO nur eine Nachflugzone
										//FHGO-NF2: FHGO nur zwei Nachflugzonen
										//FHGO-STP: FHGO gesperrt 
									"verfied" => true,
											),
						 "tchr-mxmi" => 
							Array(	"Name" => "Christen",
									"Vorname" => "Thomas",
									"Tel" => "+41 79 510 42 65",
									"EASA_ID" => "CHE-RP-5c18p294mxmi",
									"EASA_BID" => "CHEclsc784sbqhxi",
									"EASA_exp" => "23-08-2026",
									"Kat" => "A1/A3,A2",
									"Pic" => "tc_face.jpg",
									"QR" => "tc_easa_qr.png",
									"RKRS_MA" => "tc_mitgliederausweis_585.pdf",
									"Flugberechtigung" => "FGHO,FGHO_S,LSXN,RKRS",
									"FlugzonenOption" => "FHGO-NF2",
									"verfied" => true,
											),
						 "ch-sk7q" => 
							Array(	"Name" => "Häfliger",
									"Vorname" => "Chris",
									"Tel" => "+41 77 406 13 30",
									"EASA_ID" => "CHE-RP-sj1cgmcmsk7q",
									"EASA_BID" => "CHE4t8bshnjazg0p",
									"EASA_exp" => "22-08-2026",
									"Kat" => "A1/A3,A2",
									"Pic" => "ch_face2.jpg",
									"QR" => "ch_easa_qr.png",
									"RKRS_MA" => "ch_mitgliederausweis_588.pdf",
									"Flugberechtigung" => "FGHO,FGHO_S,LSXN,RKRS",
									"FlugzonenOption" => "FHGO-NF2",
									"verfied" => true,
											),
						 "chae-sk7q" => 
							Array(	"Name" => "Häfliger",
									"Vorname" => "Chris",
									"Tel" => "+41 77 406 13 30",
									"EASA_ID" => "CHE-RP-sj1cgmcmsk7q",
									"EASA_BID" => "CHE4t8bshnjazg0p",
									"EASA_exp" => "22-08-2026",
									"Kat" => "A1/A3,A2",
									"Pic" => "ch_face.jpg",
									"QR" => "ch_easa_qr.png",
									"RKRS_MA" => "ch_mitgliederausweis_588.pdf",
									"Flugberechtigung" => "FGHO,FGHO_S,LSXN,RKRS",
									"FlugzonenOption" => "FHGO-NF2",
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
									"Flugberechtigung" => "FGHO,RKRS",
									"FlugzonenOption" => "FHGO-NF1",
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
									"RKRS_MA" => "ac_mitgliederausweis_xxx.pdf",
									"Flugberechtigung" => "FGHO",
									"FlugzonenOption" => "FHGO-STP",
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
									"RKRS_MA" => "nc_mitgliederausweis_xxx.pdf",
									"Flugberechtigung" => "FGHO",
									"FlugzonenOption" => "FHGO-STP",
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
									"Flugberechtigung" => "FGHO,RKRS",
									"FlugzonenOption" => "FHGO-NF1",
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
									"Flugberechtigung" => "FGHO,RKRS",
									"FlugzonenOption" => "FHGO-NF1",
									"verfied" => true,
											),
						 "lf-wu6w" => 
							Array(	"Name" => "Freimann",
									"Vorname" => "Lars",
									"Tel" => "",
									"EASA_ID" => "CHE-RP-krpc8ofhwu6w",
									"EASA_BID" => "",
									"EASA_exp" => "20.08.2028",
									"Kat" => "A1/A3",
									"Pic" => "lf_face.jpg",
									"QR" => "lf_easa_qr.png",
									"RKRS_MA" => "lf_mitgliederausweis_1734.pdf",
									"Flugberechtigung" => "FGHO,RKRS",
									"FlugzonenOption" => "FHGO-NF1",
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
									"Flugberechtigung" => "FGHO,RKRS",
									"FlugzonenOption" => "FHGO-NF1",
									"verfied" => true,
											),
						 );


		$OptionFlughoehe = array(
									"20" => array(	"Opt" => "20",
													"Text" => "20 Meter"),
									"30" => array(	"Opt" => "30",
													"Text" => "30 Meter"),
									"40" => array(	"Opt" => "40",
													"Text" => "40 Meter"),
									"50" => array(	"Opt" => "50",
													"Text" => "50 Meter"),
									"60" => array(	"Opt" => "60",
													"Text" => "60 Meter"),
									"70" => array(	"Opt" => "70",
													"Text" => "70 Meter"),
									"80" => array(	"Opt" => "80",
													"Text" => "80 Meter"),
									"90" => array(	"Opt" => "90",
													"Text" => "90 Meter"),
									"100" => array(	"Opt" => "100",
													"Text" => "100 Meter"),
								);
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
											"Opt" => "\u{26C5}",
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


	$JsonDrohnentyp = array(
					"DJIPilot2" => "DJI Pilot 2",
					"mavic3T" => "Mavic 3T",
					"mavic3E" => "Mavic 3E",
					"mavic3M" => "Mavic 3M",
					"mavic3D" => "Mavic 3D",
					"matrice30T" => "Matrice 30T",
					//"DJILitchi" => "DJI Litchi"
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
	$searchSD  = array('Ã¼', 'Ã¶', 'Ã?', 'Ã¤', 'Ã?Â¼', 'Â¼', 'Ã?Â¤' ,'Ã¨' , 'Ã¶', '%3F', 'Ã', 'Ã','Ã', 'Ã', 'ï¿½', '\/' ,'°', 'ÄÄÂÃÂ', 'ÄÄÂÃÂ¼r'); // '\n' ,
	$replaceSD = array('ü'	, 'ö' , 'Ü' , 'ä' , 'ü' , 'ü'   ,'ä'     , 'Ü' , 'Ü' , 'Ü' , 'Ü' , 'Ä'    ,'Ä'   , 'Ö'    , '?'  , '/','Grad', 'ä','ü');  // '; '  ,
	$searchSDZ  = array_merge(array('&'),$searchSD); // '\n' ,
	$replaceSDZ = array_merge(array('&#38;'),$replaceSD);  // '; '  ,
	$csv_desc = ';';
	$csv_desc_replace = '/';

	$searchUML  = $searchSDZ;
	$replaceUML = $replaceSDZ;
	$searchUML2  = array('ä', 'ö', 'ü', 'Ä', 'Ö', 'Ü');
	$replaceUML2 = array('a', 'o', 'u', 'A', 'O', 'U');
	$searchHTML = Array("<br>","<",">");
	$replaceHTML = Array("\r","&lt;","&gt");


    $StandardSettingMap = "&Cntr=off&Area=on&Pfad=on&GDE=off&JGG=off&Poi=off&Label=off&EX=NO&KA=S&MAP=&Zoom=0.8";
	$MapIntern = "&KA=S&Strt=on&Cntr=off&Area=on&Pfad=on&EX=NO&Poi=off&FZ=on&KA=S&MAP=1&Zoom=0.8";
	$MapExtern = "&KA=S&Strt=off&Cntr=on&Area=on&Pfad=off&Poi=off&KA=S&MAP=1&Zoom=0.8";
	$MapPersonal= "&KA=S&Strt=off&Cntr=off&Area=off&Pfad=off&WW=off&FZ=off&GDE=off&JGG=off&KT=off&ORT=off&Poi=on&HSP=off&Label=off&GeLa=off&Sun=off&EG=off&SORA=off&WF=off&EX=no&MAP=&FFilter=&Zoom=0.8";

	$FlugplanBildParameter = "&Reg=ALL&Strt=on&Cntr=off&Area=on&Pfad=on&GDE=off&JGG=off&Poi=off&HSP=on&FLP=on&Label=off&EX=NO&KA=M&MAP=1&Zoom=0.8";
	$SuchplanBildParameter = "&Reg=ALL&Strt=on&Cntr=off&Area=on&Pfad=off&GDE=off&JGG=off&Poi=off&HSP=on&OVR=on&Label=off&EX=NO&KA=M&MAP=1&Zoom=0.4";
	
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


function headerpage($title ,$mobile) {
	global 	$Layout, 
			$picdir, 
			$icondir,
			$IconLoc,
			$BaseColor;

	//echo "<br><br>BaseColor: " . $BaseColor . " Layout: " .$Layout . "<br>";
	if ($Layout=="2023") {
		echo "<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.01 Transitional//EN'>" . chr(10);
		echo "<html>" . chr(10);
		echo "<head>" . chr(10);
		echo "<meta meta http-equiv='Content-Type' content='text/html;charset=UTF-8'>" . chr(10);
		echo "<meta http-equiv='expires' content='0'>" . chr(10);
		echo "<meta http-equiv='Pragma' content='no-cache'>" . chr(10);
		echo "<meta http-equiv='Cache-Control' content='no-cache, must-revalidate, max-age=0'>" . chr(10);
		echo "<META http-equiv=author content='Thomas Christen'>" . chr(10);
		echo "<META http-equiv=description content='" .$title. "'>" . chr(10);
		echo "<META http-equiv=keywords content='" .$title. "'>" . chr(10);
		echo "<meta name='apple-mobile-web-app-capable' content='yes' />" . chr(10);
		echo "  <link rel='icon' href='" . $picdir . "rkrk.ico'>" . chr(10);
		echo "  <link rel='apple-touch-icon' sizes='57x57' href='" . $picdir . "apple-icon-57x57.png'>" . chr(10);
		echo "  <link rel='apple-touch-icon' sizes='60x60' href='" . $picdir . "apple-icon-60x60.png'>" . chr(10);
		echo "  <link rel='apple-touch-icon' sizes='72x72' href='" . $picdir . "apple-icon-72x72.png'>" . chr(10);
		echo "  <link rel='apple-touch-icon' sizes='76x76' href='" . $picdir . "apple-icon-76x76.png'>" . chr(10);
		echo "  <link rel='apple-touch-icon' sizes='114x114' href='" . $picdir . "apple-icon-114x114.png'>" . chr(10);
		echo "  <link rel='apple-touch-icon' sizes='120x120' href='" . $picdir . "apple-icon-120x120.png'>" . chr(10);
		echo "  <link rel='apple-touch-icon' sizes='144x144' href='" . $picdir . "apple-icon-144x144.png'>" . chr(10);
		echo "  <link rel='apple-touch-icon' sizes='152x152' href='" . $picdir . "apple-icon-152x152.png'>" . chr(10);
		echo "  <link rel='apple-touch-icon' sizes='180x180' href='" . $picdir . "apple-icon-180x180.png'>" . chr(10);
		echo "  <link rel='icon' type='image/png' sizes='192x192'  href='" . $picdir . "android-icon-192x192.png'>" . chr(10);
		echo "  <link rel='icon' type='image/png' sizes='32x32' href='" . $picdir . "favicon-32x32.png'>" . chr(10);
		echo "  <link rel='icon' type='image/png' sizes='96x96' href='" . $picdir . "favicon-96x96.png'>" . chr(10);
		echo "  <link rel='icon' type='image/png' sizes='16x16' href='" . $picdir . "favicon-16x16.png'>" . chr(10);
		echo "  <link rel='manifest' href='" . $icondir . "manifest.json'>" . chr(10);
		echo "  <LINK href='template/main_scratch.css' rel='stylesheet' type='text/css'>" . chr(10);
		//if (($BaseColor=="green")) {
			echo "  <LINK href='template/main_green.css' rel='stylesheet' type='text/css'>" . chr(10);
		//} else {
		//	echo "  <LINK href='template/main_white.css' rel='stylesheet' type='text/css'>" . chr(10);
		//}
		echo "<meta name='viewport' content='width=device-width, initial-scale=1'>" . chr(10);

		echo "<link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css'>" . chr(10);
		echo "  <meta name='msapplication-TileColor' content='#ffffff'>" . chr(10);
		echo "  <meta name='msapplication-TileImage' content='" . $picdir . "ms-icon-144x144.png'>" . chr(10);
		echo "  <meta name='theme-color' content='#ffffff'>" . chr(10);
		echo "</head>" . chr(10);
	} else {
		echo "<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.01 Transitional//EN'>" . chr(10);
		echo "<html>" . chr(10);
		echo "<head>" . chr(10);
		echo "<meta meta http-equiv='Content-Type' content='text/html;charset=UTF-8'>" . chr(10);
		echo "<meta http-equiv='expires' content='0'>" . chr(10);
		echo "<meta http-equiv='Pragma' content='no-cache'>" . chr(10);
		echo "<meta http-equiv='Cache-Control' content='no-cache, must-revalidate, max-age=0'>" . chr(10);
		echo "<META http-equiv=author content='Thomas Christen'>" . chr(10);
		echo "<META http-equiv=description content='" .$title. "'>" . chr(10);
		echo "<META http-equiv=keywords content='" .$title. "'>" . chr(10);
		echo "<meta name='apple-mobile-web-app-capable' content='yes' />" . chr(10);
	/*	if ($mobile) {
			echo "<meta id='viewport' name='viewport' content='width=device-width, initial-scale=1' />" . chr(10);
			echo "<meta http-equiv='Content-Type' content='application/xhtml+xml; charset=iso-8859-1'/>" . chr(10);
			echo "<link rel='stylesheet' href='https://code.jquery.com/mobile/1.4.5/jquery.mobile-1.4.5.min.css' />" . chr(10);
			echo "<script src='https://code.jquery.com/jquery-1.11.1.min.js'></script>" . chr(10);
			echo "<script src='https://code.jquery.com/mobile/1.4.5/jquery.mobile-1.4.5.min.js'></script>" . chr(10);
		}
	*/
		echo "  <link rel='icon' href='" . $picdir . "rkrk.ico'>" . chr(10);
		echo "  <link rel='apple-touch-icon' sizes='57x57' href='" . $picdir . "apple-icon-57x57.png'>" . chr(10);
		echo "  <link rel='apple-touch-icon' sizes='60x60' href='" . $picdir . "apple-icon-60x60.png'>" . chr(10);
		echo "  <link rel='apple-touch-icon' sizes='72x72' href='" . $picdir . "apple-icon-72x72.png'>" . chr(10);
		echo "  <link rel='apple-touch-icon' sizes='76x76' href='" . $picdir . "apple-icon-76x76.png'>" . chr(10);
		echo "  <link rel='apple-touch-icon' sizes='114x114' href='" . $picdir . "apple-icon-114x114.png'>" . chr(10);
		echo "  <link rel='apple-touch-icon' sizes='120x120' href='" . $picdir . "apple-icon-120x120.png'>" . chr(10);
		echo "  <link rel='apple-touch-icon' sizes='144x144' href='" . $picdir . "apple-icon-144x144.png'>" . chr(10);
		echo "  <link rel='apple-touch-icon' sizes='152x152' href='" . $picdir . "apple-icon-152x152.png'>" . chr(10);
		echo "  <link rel='apple-touch-icon' sizes='180x180' href='" . $picdir . "apple-icon-180x180.png'>" . chr(10);
		echo "  <link rel='icon' type='image/png' sizes='192x192'  href='" . $picdir . "android-icon-192x192.png'>" . chr(10);
		echo "  <link rel='icon' type='image/png' sizes='32x32' href='" . $picdir . "favicon-32x32.png'>" . chr(10);
		echo "  <link rel='icon' type='image/png' sizes='96x96' href='" . $picdir . "favicon-96x96.png'>" . chr(10);
		echo "  <link rel='icon' type='image/png' sizes='16x16' href='" . $picdir . "favicon-16x16.png'>" . chr(10);
		echo "  <link rel='manifest' href='" . $icondir . "manifest.json'>" . chr(10);
		if (($BaseColor=="green")) {
			echo "  <LINK href='template/main_style_green.css' rel='stylesheet' type='text/css'>" . chr(10);
		} else {
			echo "  <LINK href='template/main_style_white.css' rel='stylesheet' type='text/css'>" . chr(10);
		}
		echo "  <LINK href='template/nmenu_style.css' rel='stylesheet' type='text/css'>" . chr(10);
		echo "  <meta name='msapplication-TileColor' content='#ffffff'>" . chr(10);
		echo "  <meta name='msapplication-TileImage' content='" . $picdir . "ms-icon-144x144.png'>" . chr(10);
		echo "  <meta name='theme-color' content='#ffffff'>" . chr(10);
		echo "</head>" . chr(10);
	}
}


function footer() {
	echo '	<table width=100%>' . chr(10);
	echo '	<tr><td style="text-align:center; background-color:#000000">' . chr(10);
	echo '	<p style="color:#FFFFFF; font-size: large;"><b>Zusammen schaffen wir das!!!</b></p>' . chr(10);
//	echo '	<p style="color:#FFFFFF; font-size: small;"><i>Helft uns, damit wir den Rehkitzen helfen können.</i></p>' . chr(10);
//	echo '	<p style="color:#FFFFFF; font-size: x-small;">Der Verein IG "Rehkitzrettung Knonaueramt und Zug" arbeitet eng mit den Jagdgesellschaften, dem Amt für Wald und Wild und Landwirten zusammen und bietet Drohnenflüge für die Rekitzrettung in der Region an.</p><br>' . chr(10);
	echo '	</td></tr>' . chr(10);
	echo '	</table>' . chr(10);
}


// ==================
// Header Page
// ==================

function PrintRKRKHeader($title) {
	global $IconLoc,$Layout;
    //Querformat
	if ($Layout=="2023") {
		echo "<br><br>";
		echo "  <div class='container content center padding-32' style='max-width:2000px' id='title'>" .chr(10);
		echo "    <h2 id='Titel' name='Titel' class='wide'>".$title."</h2>" .chr(10);
		echo "  </div>" .chr(10);
		echo "  <div class='container padding-32' style='max-width:2000px' id='content'>" .chr(10);
	} else {
		echo "<table><tr>". chr(10);
		echo "  <td colspan=2 width='450' height='50'>" . chr(10);
		echo "  <h1>" . $title . "</h1>" . chr(10);	
		echo "  </td>";
		echo "  <td valign='center' align='right'  rowspan=2>";
		echo "IG ". chr(34) ."Rehktitzrettung Knonaueramt und Zug". chr(34) ."<br><a href='http://www.drohnenrehkitzrettung.ch' target='blank'>www.drohnenrehkitzrettung.ch</a>";
		echo "   </td>". chr(10);
		echo "   <td width='80' align='right' rowspan=2>";
		echo "<img src='" . $IconLoc . "/202121 Logo-RKRKZ.png' height='80px'>". chr(10);
		echo "   </td>". chr(10);
		echo "  </tr>". chr(10);
		echo "</table>";
	}
}

function GetTableColor ($BaseColor) {
	global $Layout;
		
	if ($BaseColor=="white") {
		$TableColor['odd'] = "#fbfbfb";
		$TableColor['even'] = "#eeeeee";
	} elseif (($BaseColor=="green") or ($Layout!="2023")) {
		$TableColor['odd'] = "#ecf7ec";
		$TableColor['even'] = "#d0e5d0 ";
	} else { 
		$TableColor['odd'] = "#fbfbfb";
		$TableColor['even'] = "#eeeeee";
	}
	return $TableColor;
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
			if (in_array("intern", explode(",",$Pilot["Access"]))) {
				$Pilots[$key]= $Pilot["Opt"] . " - " . $Pilot["Text"];
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
	global $IconLoc, $OptionGemeinde, $OptionFrei, $OptionWarning,$FlyFileSetting;
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
		echo "	 		<li><img src='" . $IconLoc . "/flugplan.png' height='25px'>  Flugplan als PDF</li>".chr(10); 
	//	echo "	 		<li><img src='" . $IconLoc . "/glrthflpl.png' height='25px'>  KML Daten mit Suchfelder für Google Earth</li>".chr(10); 
	//	echo "	 		<li><img src='" . $IconLoc . "/glrthpoi.png' height='25px'>   KML Daten mit Flugwege, POI für Google Earth)</li>".chr(10); 
		echo "	 		<li><img src='" . $IconLoc . "/geomap.png' height='25px'>  Flugpläne auf Karten von map.geo.admin.ch</li>".chr(10); 
		echo "	 		<li><img src='" . $IconLoc . "/public.png' height='25px'>  Öffentliche Flugpläne</li>".chr(10); 
	//	echo "	 		<li><img src='" . $IconLoc . "/workplan.png' height='25px'>  Online Arbeitsplan</li>".chr(10); 
		if (GetAccess("PilotPartners")) {
			echo "	 		<li><img src='" . $IconLoc . "/intern.png' height='25px'>  Online Flugplan</li>".chr(10); 
			echo "	 		<li><img src='" . $IconLoc . "/neuesfeld.png' height='25px'>  Neues Feld hinzufügen</li>".chr(10); 
			echo "	 		<li><img src='" . $IconLoc . "/edit_feld.png' height='25px'>  Feld editieren</li>".chr(10); 
			echo "	 		<li><img src='" . $IconLoc . "/edit_feldlist.png' height='25px'>  Feldliste editieren</li>".chr(10); 

		}

		echo "	 		<li><img src='" . $IconLoc . "/feldneu.png' height='25px'>  Registrieren eines neuen Feldes (nicht im Katalog)</li>".chr(10); 
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
	if (!file_exists($filename)) return false;
	$raw = file_get_contents($filename);
	if ($raw === false || $raw === '') return false;

	$xml = @simplexml_load_string($raw);
	if ($xml !== false) return $xml;

	$converted = @mb_convert_encoding($raw, 'UTF-8', 'Windows-1252,ISO-8859-1');
	if (is_string($converted) && $converted !== '') {
		$xml = @simplexml_load_string($converted);
		if ($xml !== false) return $xml;
	}

	return false;
}

   function ReadXMLFile($filename, $debugoutput)  {   


	//echo $filename;
		if(file_exists($filename)) {
		//  $xml = simplexml_load_file($filename);     //Lädt die XML-Datei in ein Objekt
		$xml = rkrk_load_xml_file_safe($filename);
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
	Return $xml;
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
		$Settings = ReadXMLFile($xmlsubfolder.$xmlMissionLocation.$RKRKRegionalSettingXML, false);
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

// ==================
// Create upper left menue
// ==================

function DoMenuMap($ThisPage, $Title, $SelRegion, $Parameterset) {
	global $xmlsubfolder, $xmlMissionLocation, $RKRKRegionalSettingXML,$searchUML, $replaceUML;
	$Settings = ReadXMLFile($xmlsubfolder.$xmlMissionLocation.$RKRKRegionalSettingXML, false);

	$output="";
	$output .= "<script src='scripts/mapgeo.js'></script>".chr(10);
	$output .= "<div id='mySidenav' class='sidenav'>".chr(10);
	$output .= "<a href='javascript:void(0)' class='closebtn' onclick='closeNav()'>&times;</a>".chr(10);
/*
	echo "<a href= 'flugplaenealle.html'>alle Flugpläne</a>".chr(10);
	echo "<a href='flugplaene21.html' >21 - Aeugst</a>".chr(10);
	echo "<a href='flugplaene25.html' >25 - Kappel</a>".chr(10);
	echo "<a href='flugplaene26.html' >26 - Knonau</a>".chr(10);
	echo "<a href='flugplaene27.html' >27 - Maschwanden</a>".chr(10);
	echo "<a href='flugplaene28.html' >28 - Mettmenstetten</a>".chr(10);
	echo "<a href='flugplaenezg.html' >ZG - Zug</a>".chr(10);
	echo "<a href='flugplaeneag.html' >AG - Aargau</a>".chr(10);
	echo "<a href='flugplaenexx.html' >XX - Andere</a>".chr(10);
	echo "<a href='flugplaeneaz.html' >AZ - Jagdbezirk Amt</a>".chr(10);
	echo "<a href='flugplaenetf.html' >TF - Testflugfelder</a>".chr(10);
*/
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

function pre_r($array) {
	echo "<pre>";
	print_r($array);
	echo "</pre>";
}

	// ===========================================
	// Array Functions
	// ===========================================
	
	// multidimensionsal sorting of array
    // aasort($your_array, "order");
	
	function aasort (&$array, $key) {
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

//////////////////////////////////////////////
// add entry to logstatistics
//////////////////////////////////////////////

function WriteToStatistic($Type, $Content, $Text1, $Text2, $Text3, $Text4, $LogArt="Site") {
global $logfilelocation, $DebugWork, $RKRKLocalLoactionMissionFiles, $CleanAgeOfFilesInDays, $WebFreigabe, $RKRKSiteStatsCSV, $RKRKIPStatsCSV,$IPUsed;

	$delimiter= ";";
	$entryfound = false;
	$Statistic = Array();
	$header = explode(";", $headerline);
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
	$myfile = fopen($filename, "w");
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
		exit( "Session killed.");
	}
	if (array_key_exists($TCPAdress, $IPUsed)) {
		//IP identified
		$User = $IPUsed[$TCPAdress]["Name"];
		$AccessLevel= $OptionPilot[$User]["Access"]; 
		$cookie_name = "user";
		$cookie_value = $IPUsed[$TCPAdress]["Name"];
		//echo "set cookie: " .  $cookie_value . "<br>";
		setcookie($cookie_name, $cookie_value, time() + (86400 * 30), "/");
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
	//echo $logfilelocation.$LogfileName;

	if (!file_exists($logfilelocation.$LogfileName)) {
		$txt = "Zeit;IP Adresse;Mac Address;Client;Device;User;Level;Typ;Seite;Text;Parameter;Text3;Text4". chr(10);
		$myfile = fopen($logfilelocation.$LogfileName, "w");
		fwrite($myfile, $txt);
		//fwrite($myfile, mb_convert_encoding( $txt, 'UTF-16LE', 'UTF-8'));
		fclose($myfile);
	}
	
	$Client = "";
	$TCPAdress = getIPAddress();
	$MacAddress = getIPConfig();
	if ( $_SESSION[$SessNme_RegisteredInitialen]!="") {
		$User=  $_SESSION[$SessNme_RegisteredInitialen];
	}
	 $txt = date("Y-m-d H:i:s") . ";" . $TCPAdress .";" . $MacAddress.";" . $Client .";" .str_replace(";",",",$_SERVER['HTTP_USER_AGENT']) . ";" . $User . ";" . $WebFreigabe  . ";" .$Type . ";" .  $Page  . ";" . $Text1  . ";" . $Text2 . ";" . $Text3 . ";" . $Text4. chr(10);
	$myfile = fopen($logfilelocation.$LogfileName, "a");
	fwrite($myfile, $txt);
	//fwrite($myfile, mb_convert_encoding( $txt,'UTF-16LE', 'UTF-8'));
	fclose($myfile);
	CleanupOldFiles($CleanAgeOfFilesInDays);
	WriteToStatistic($Type, $Page, $Text1, $Text2, $Text3, $Text4,"Site");
	WriteToStatistic($Type, $TCPAdress, $Text1, $Text2, $Text3, $Text4, "IP");
}

function CleanupOldFiles($ageInDays) {
	global $logfilelocation, $historyfilelocation, $deletefilelocation, $logfilelocation ;
	DeleteFilesOlderThan($historyfilelocation, "*.*", 10);
	DeleteFilesOlderThan($deletefilelocation, "*.*", 30);
	DeleteFilesOlderThan($logfilelocation, "*.*", 10);
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

/*
	pre_r($FilenameSplit);
	
	$PilotList = Array();
	foreach ($OptionPilot as $Pilot) {
	  $PilotList[]= $Pilot["Opt"];
	}

	$HightList = Array();
	foreach ($OptionFlughoehe as $Hight) {
	  $HightList[]= $Hight["Opt"];
	}

//	pre_R($HightList);
	
	
	// Check if standard filename used, or mixed
	// Height in POS 3, Pilot in POS 4
	// Height in POS 4, Pilot in POS 3
	$filenameSet = false;
	if (
		(in_array($FilenameSplit[3], $HightList) and
		in_array($FilenameSplit[4], $PilotList)) or
		(in_array($FilenameSplit[4], $HightList) and
		in_array($FilenameSplit[3], $PilotList))
		)
	{
		$filenameSet = true;
		$CoreFilename = $KatID . "_" . str_replace(" ", "-", $FilenameSplit[1]) . "_" . $FilenameSplit[2];
	}
	// Check with offset 1 filename used, or mixed
	// Height in POS 4, Pilot in POS 5
	// Height in POS 5, Pilot in POS 4
	$filenameSet = false;
	if (
		(in_array($FilenameSplit[4], $HightList) and
		in_array($FilenameSplit[5], $PilotList)) or
		(in_array($FilenameSplit[5], $HightList) and
		in_array($FilenameSplit[4], $PilotList))
		)
	{
		$filenameSet = true;
		// 29.01.2023 Flurname aus dem Filenamen entfernt
		//$CoreFilename = $KatID . "_" . str_replace(" ", "-", $FilenameSplit[1] . "_" . $FilenameSplit[2]) . "_" . $FilenameSplit[3];
		$CoreFilename = $KatID . "_" . str_replace(" ", "-", $FilenameSplit[1]) . "_" . $FilenameSplit[2];
	}
	// Check with offset 2 filename used, or mixed
	// Height in POS 5, Pilot in POS 6
	// Height in POS 6, Pilot in POS 5
	$filenameSet = false;
	if (
		(in_array($FilenameSplit[5], $HightList) and
		in_array($FilenameSplit[6], $PilotList)) or
		(in_array($FilenameSplit[6], $HightList) and
		in_array($FilenameSplit[5], $PilotList))
		)
	{
		$filenameSet = true;
		// 29.01.2023 Flurname aus dem Filenamen entfernt
		//$CoreFilename = $KatID . "_" . str_replace(" ", "-", $FilenameSplit[1] . "_" . $FilenameSplit[2] . "-" . $FilenameSplit[3]) . "_" . $FilenameSplit[4];
		$CoreFilename = $KatID . "_" . str_replace(" ", "-", $FilenameSplit[1] . "_" . $FilenameSplit[2]) . "_" . $FilenameSplit[4];
	}
	// Check with offset -1 filename used, or mixed (no JG information)
	// Height in POS 2, Pilot in POS 3
	// Height in POS 3, Pilot in POS 2
	$filenameSet = false;
	if (
		(in_array($FilenameSplit[2], $HightList) and
		in_array($FilenameSplit[3], $PilotList)) or
		(in_array($FilenameSplit[3], $HightList) and
		in_array($FilenameSplit[2], $PilotList))
		)
	{
		$filenameSet = true;
		$CoreFilename = $KatID . "_" . str_replace(" ", "-", $FilenameSplit[0]) . "_" . $FilenameSplit[1];
	}

	//echo "<br>CoreFilename" . $CoreFilename;
*/
	
	Return $KatID . "_"  . GetMeaningFilename($Filename)[7];

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
	$MeaningFilename=Array();
	$PathInfo = pathinfo($Filename);
	$FilenameSplit = explode("_",$PathInfo["filename"]);
	if ($newKatID!="") {
		$KatID = $newKatID;
	} else {
		$KatID = $FilenameSplit[0];
	}

	//pre_r($FilenameSplit);
	
	$PilotList = Array();
	foreach ($OptionPilot as $Pilot) {
	  $PilotList[]= $Pilot["Opt"];
	}

	$HightList = Array();
	foreach ($OptionFlughoehe as $Hight) {
	  $HightList[]= $Hight["Opt"];
	}
    $HightList[]= "00";

	$filenameSet = false;
	
	//	pre_R($HightList);
	
	// Check if standard filename used
	// Height in POS 3, Pilot in POS 4
	if ((!$filenameSet) and
		(in_array($FilenameSplit[3], $HightList)and
		in_array($FilenameSplit[4], $PilotList))
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
		$MeaningFilename[6] =  substr($FilenameSplit[5],0,2);
		$MeaningFilename[7] =  $FilenameSplit[1] . "_" . $FilenameSplit[2];
	}
	// Check if standard filename  mixed
	// Height in POS 4, Pilot in POS 3

	if ((!$filenameSet) and 
		(in_array($FilenameSplit[4], $HightList) and
		in_array($FilenameSplit[3], $PilotList))
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
		$MeaningFilename[6] =  substr($FilenameSplit[5],0,2);
		$MeaningFilename[7] =  $FilenameSplit[1] . "_" . $FilenameSplit[2];
	}

	// Check with offset 1 filename used
	// Height in POS 4, Pilot in POS 5
	if ((!$filenameSet) and
		(in_array($FilenameSplit[4], $HightList) and
		in_array($FilenameSplit[5], $PilotList))
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
		$MeaningFilename[6] =  substr($FilenameSplit[6],0,2);
		//$MeaningFilename[7] =  $FilenameSplit[1] . "_" . $FilenameSplit[2] . "_" . $FilenameSplit[3];
		$MeaningFilename[7] =  $FilenameSplit[1] . "_" . $FilenameSplit[3];
	}

	// Check with offset 1 filename mixed
	// Height in POS 5, Pilot in POS 4
	if ((!$filenameSet) and
		(in_array($FilenameSplit[5], $HightList) and
		in_array($FilenameSplit[4], $PilotList))
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
		$MeaningFilename[6] =  substr($FilenameSplit[6],0,2);
		//$MeaningFilename[7] =  $FilenameSplit[1] . "_" . $FilenameSplit[2] . "_" . $FilenameSplit[3];
		$MeaningFilename[7] =  $FilenameSplit[1] . "_" . $FilenameSplit[3];
	}

	// Check with offset 2 filename used
	// Height in POS 5, Pilot in POS 6
	if ((!$filenameSet) and
		(in_array($FilenameSplit[5], $HightList) and
		in_array($FilenameSplit[6], $PilotList))
		)
	{
		$filenameSet = true;
		$MeaningFilename[0] =  $FilenameSplit[0];
		$MeaningFilename[1] =  $FilenameSplit[1];
		$MeaningFilename[2] =  $FilenameSplit[2] ."-" . $FilenameSplit[3];
		$MeaningFilename[3] =  $FilenameSplit[4];
		$MeaningFilename[4] =  $FilenameSplit[5];
		$MeaningFilename[5] =  $FilenameSplit[6];
		$MeaningFilename[6] =  substr($FilenameSplit[7],0,2);
		//$MeaningFilename[7] =  $FilenameSplit[1] . "_" . $FilenameSplit[2] . "_" . $FilenameSplit[3];
		$MeaningFilename[7] =  $FilenameSplit[1] . "_" . $FilenameSplit[3];
	}

	// Check with offset 2 filename mixed
	// Height in POS 6, Pilot in POS 5
	if ((!$filenameSet) and
		(in_array($FilenameSplit[6], $HightList) and
		in_array($FilenameSplit[5], $PilotList))
		)
	{
		$filenameSet = true;
		$MeaningFilename[0] =  $FilenameSplit[0];
		$MeaningFilename[1] =  $FilenameSplit[1];
		$MeaningFilename[2] =  $FilenameSplit[2] ."-" . $FilenameSplit[3];
		$MeaningFilename[3] =  $FilenameSplit[4];
		$MeaningFilename[4] =  $FilenameSplit[6];
		$MeaningFilename[5] =  $FilenameSplit[5];
		$MeaningFilename[6] =  substr($FilenameSplit[7],0,2);
		//$MeaningFilename[7] =  $FilenameSplit[1] . "_" . $FilenameSplit[2] . "_" . $FilenameSplit[3];
		$MeaningFilename[7] =  $FilenameSplit[1] . "_" . $FilenameSplit[3];
	}

	// Check with offset -1 filename used (no JG information)
	// Height in POS 2, Pilot in POS 3
	if ((!$filenameSet) and
		(in_array($FilenameSplit[2], $HightList) and
		in_array($FilenameSplit[3], $PilotList))
		)
	{
		$filenameSet = true;
		$MeaningFilename[0] =  "";
		$MeaningFilename[1] =  $FilenameSplit[0];
		$MeaningFilename[2] =  "";
		$MeaningFilename[3] =  $FilenameSplit[2];
		$MeaningFilename[4] =  $FilenameSplit[3];
		$MeaningFilename[5] =  $FilenameSplit[4];
		$MeaningFilename[6] = substr( $FilenameSplit[5],0,2);
		$MeaningFilename[7] =   $FilenameSplit[2] . "_" . $FilenameSplit[3];
	}

	// Check with offset -1 filename mixed (no JG information)
	// Height in POS 3, Pilot in POS 2

	if ((!$filenameSet) and
		(in_array($FilenameSplit[3], $HightList) and
		in_array($FilenameSplit[2], $PilotList))
		)
	{
		$filenameSet = true;
		$MeaningFilename[0] =  "";
		$MeaningFilename[1] =  $FilenameSplit[0];
		$MeaningFilename[2] =  "";
		$MeaningFilename[3] =  $FilenameSplit[3];
		$MeaningFilename[4] =  $FilenameSplit[2];
		$MeaningFilename[5] =  $FilenameSplit[4];
		$MeaningFilename[6] = substr( $FilenameSplit[5],0,2);
		$MeaningFilename[7] =  $FilenameSplit[2] . "_" . $FilenameSplit[3];
	}

	//check if year has right format otherwiese reset
	if (!in_array(trim($MeaningFilename[6]), $OptionYear)) {
		$MeaningFilename[6] = date("y");
	}
	
	//echo "<br>MeaningFilename" . $CoreFilename;
	//pre_r($MeaningFilename);
	Return $MeaningFilename;

}

function rkrk_parse_access_csv($list) {
	$items = array_map('trim', explode(',', strval($list)));
	return array_values(array_filter($items, function ($v) { return $v !== ''; }));
}

function rkrk_get_current_benutzername() {
	if (session_status() === PHP_SESSION_NONE) {
		@session_start();
	}
	if (!empty($_SESSION["Benutzername"])) {
		return trim(strval($_SESSION["Benutzername"]));
	}
	return "";
}

function GetAccessToUser($AllowedUsersCsv = "", $CurrentUser = "") {
	$userItems = rkrk_parse_access_csv($AllowedUsersCsv);
	if (!$userItems) {
		return false;
	}
	$userToCheck = trim(strval($CurrentUser));
	if ($userToCheck === "") {
		$userToCheck = rkrk_get_current_benutzername();
	}
	if ($userToCheck === "") {
		return false;
	}
	return in_array($userToCheck, $userItems, true);
}

function rkrk_check_release_or_user_access($releaseList, $webFreigabe, $allowedUsersCsv = "", $currentUser = "") {
	$releaseItems = rkrk_parse_access_csv($releaseList);
	$currentRelease = trim(strval($webFreigabe));
	if ($currentRelease !== "" && in_array($currentRelease, $releaseItems, true)) {
		return true;
	}
	return GetAccessToUser($allowedUsersCsv, $currentUser);
}

Function CheckAccess ($AccessList) {
	global $WebFreigabe;
	return rkrk_check_release_or_user_access($AccessList, $WebFreigabe, "");
}


Function GetAccess ($FunctionKey) {
	global $AccessControl, $WebFreigabe;
	$allowedUsers = "";
	if (isset($AccessControl[$FunctionKey]["users"])) {
		$allowedUsers = $AccessControl[$FunctionKey]["users"];
	} elseif (isset($AccessControl[$FunctionKey]["Benutzername"])) {
		$allowedUsers = $AccessControl[$FunctionKey]["Benutzername"];
	} elseif (isset($AccessControl[$FunctionKey]["allowed_users"])) {
		$allowedUsers = $AccessControl[$FunctionKey]["allowed_users"];
	}
	return rkrk_check_release_or_user_access($AccessControl[$FunctionKey]["release"], $WebFreigabe, $allowedUsers);
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


function FormatFlugDataArray($data) {
	global $MaxFlugID;
	$MaxFlugID = 0;
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

function cleanString($text) {
	$Filter = '/[^\p{L}0-9\-.;@_]/u';
	$Filter = '/[^A-Za-z0-9\-]/';
	$Filter = '/[^\p{L}0-9\-+*ç%()={}?! ]/u';

	//leave äöüÄÖÜ
    $utf8 = array(
        '/[áàâãåª]/u'   =>   'a',
        '/[ÁÀÂÃ]/u'     =>   'A',
        '/[ÍÌÎÏ]/u'     =>   'I',
        '/[íìîï]/u'     =>   'i',
        '/[éèêë]/u'     =>   'e',
        '/[ÉÈÊË]/u'     =>   'E',
        '/[øóòôõº]/u'  =>   'o',
        '/[ÓÒÔÕ]/u'     =>   'O',
        '/[úùû]/u'      =>   'u',
        '/[ÚÙÛ]/u'      =>   'U',
        '/ç/'           =>   'c',
        '/Ç/'           =>   'C',
        '/ñ/'           =>   'n',
        '/æ/'           =>   'ae',
        '/ç/'           =>   'c',
        '/ß/'           =>   'ss',
        '/Ñ/'           =>   'N',
        '/°/'           =>   'Grad', // Gradzeichen
        '/–/'           =>   '-', // UTF-8 hyphen to "normal" hyphen
        '/[’‘‹›‚]/u'    =>   ' ', // Literally a single quote
        '/[“”«»„]/u'    =>   ' ', // Double quote
        '/ /'           =>   ' ', // nonbreaking space (equiv. to 0x160)
		// added
        '/[^\p{L}0-9\-+*ç%()={}?!:;,._ @\n]/u'   =>   '', // unwanted characters
    );
    return preg_replace(array_keys($utf8), array_values($utf8), $text);
} 



function MissionLibraryButtons($AddUrl="", $AsForm=true) {
	global 	$IconLoc,
			$ButtonHeight,
			$SessNme_MissionDBSetting,
			$UseSecUrl;
			
		$Output ="";	

//echo "Mission stored:" . $_SESSION[$SessNme_MissionDBSetting];
		
if (GetAccess("InternLinks")) 
	//if ($AsForm) $Output .= '<form name="MDB" action="' . htmlspecialchars($_SERVER["PHP_SELF"]) . '" method="POST">' . chr(10);
	// Ablauf alt (bis 14.10.2024) M3T -> M2 (-> D) -> M3T
	// Ablauf neu (ab 14.10.2024) M3T -> 30T -> M2 (-> D) -> M3T
	if ($_SESSION[$SessNme_MissionDBSetting]=="M2") {
		if (GetAccess("DebugDataBase")) {
			//M2 -> D;
			if ($AsForm) {
				if ($UseSecUrl) {
					//encripted URL
					$Output .=  "<input type='image' value='MDB' src='" . $IconLoc . "/mavic2_button.png' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?". base64_encode("MDB=D". $AddUrl) ."' height='" . $ButtonHeight . "'  title='Klick zum welchseln der Datenbanken'  style='background-color:transparent; border-color:transparent;vertical-align: middle;'>" .chr(10);
				} else {
					//cleartext URL
					$Output .=  "<input type='image' value='MDB' src='" . $IconLoc . "/mavic2_button.png' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?"."MDB=D". $AddUrl ."' height='" . $ButtonHeight . "'  title='Klick zum welchseln der Datenbanken'  style='background-color:transparent; border-color:transparent;vertical-align: middle;'>" .chr(10);
				}
			} else {
				if ($UseSecUrl) {
					//encripted URL
					$Output .= "<a href='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?". base64_encode("MDB=D". $AddUrl) ."'><image src='" . $IconLoc . "/mavic2_button.png' height='" . $ButtonHeight . "' title='Klick zum welchseln der MissionsDatenbank'></a>";
				} else {
					//cleartext URL
					$Output .= "<a href='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?"."MDB=D". $AddUrl ."'><image src='" . $IconLoc . "/mavic2_button.png' height='" . $ButtonHeight . "' title='Klick zum welchseln der MissionsDatenbank'></a>";
				}
			}	
		} else {
			//M2 -> D;
			if ($AsForm) {
				if ($UseSecUrl) {
					//encripted URL
					$Output .=  "<input type='image' value='MDB' src='" . $IconLoc . "/mavic2_button.png' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?".base64_encode("MDB=M3T". $AddUrl) ."' height='" . $ButtonHeight . "'  title='Klick zum welchseln der Datenbanken'  style='background-color:transparent; border-color:transparent;vertical-align: middle;'>" .chr(10);
				} else {
					//cleartext URL
					$Output .=  "<input type='image' value='MDB' src='" . $IconLoc . "/mavic2_button.png' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?"."MDB=M3T". $AddUrl ."' height='" . $ButtonHeight . "'  title='Klick zum welchseln der Datenbanken'  style='background-color:transparent; border-color:transparent;vertical-align: middle;'>" .chr(10);
				}
			} else {
				if ($UseSecUrl) {
					//encripted URL
					$Output .= "<a href='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?". base64_encode("MDB=M3T". $AddUrl) ."'><image src='" . $IconLoc . "/mavic2_button.png' height='" . $ButtonHeight . "' title='Klick zum welchseln der MissionsDatenbank'></a>";
				} else {
					//cleartext URL
					$Output .= "<a href='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?"." MDB=M3T". $AddUrl ."'><image src='" . $IconLoc . "/mavic2_button.png' height='" . $ButtonHeight . "' title='Klick zum welchseln der MissionsDatenbank'></a>";
				}
			}
		}
	} elseif ($_SESSION[$SessNme_MissionDBSetting]=="D") {
		if (GetAccess("DebugDataBase")) {
			//D -> M3T
			if ($AsForm) {
				if ($UseSecUrl) {
					//encripted URL
					$Output .=  "<input type='image' value='MDB' src='" . $IconLoc . "/debug_button.png' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?".base64_encode("MDB=M3T". $AddUrl) ."' height='" . $ButtonHeight . "'  title='Klick zum welchseln der Datenbanken'  style='background-color:transparent; border-color:transparent;vertical-align: middle;'>" .chr(10);
				} else {
					//cleartext URL
					$Output .=  "<input type='image' value='MDB' src='" . $IconLoc . "/debug_button.png' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?"."MDB=M3T". $AddUrl ."' height='" . $ButtonHeight . "'  title='Klick zum welchseln der Datenbanken'  style='background-color:transparent; border-color:transparent;vertical-align: middle;'>" .chr(10);
				}
			} else {
				if ($UseSecUrl) {
					//encripted URL
					$Output .= "<a href='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?". base64_encode("MDB=M3T". $AddUrl) ."'><image src='" . $IconLoc . "/debug_button.png' height='" . $ButtonHeight . "' title='Klick zum welchseln der MissionsDatenbank'></a>";
				} else {
					//cleartext URL
					$Output .= "<a href='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?"."MDB=M3T". $AddUrl ."'><image src='" . $IconLoc . "/debug_button.png' height='" . $ButtonHeight . "' title='Klick zum welchseln der MissionsDatenbank'></a>";
				}
			}
		} else {
			//M3T -> M2;
			if ($AsForm) {
				if ($UseSecUrl) {
					//encripted URL
					$Output .=  "<input type='image' value='MDB' src='" . $IconLoc . "/mavic3t_button.png' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?".base64_encode("MDB=M2". $AddUrl) ."' height='" . $ButtonHeight . "'  title='Klick zum welchseln der Datenbanken'  style='background-color:transparent; border-color:transparent;vertical-align: middle;'>" .chr(10);
				} else {
					//cleartext URL
					$Output .=  "<input type='image' value='MDB' src='" . $IconLoc . "/mavic3t_button.png' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?"."MDB=M2". $AddUrl ."' height='" . $ButtonHeight . "'  title='Klick zum welchseln der Datenbanken'  style='background-color:transparent; border-color:transparent;vertical-align: middle;'>" .chr(10);
				}
			} else {
				if ($UseSecUrl) {
					//encripted URL
					$Output .= "<a href='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?". base64_encode("MDB=M2". $AddUrl) ."'><image src='" . $IconLoc . "/mavic3t_button.png' height='" . $ButtonHeight . "' title='Klick zum welchseln der MissionsDatenbank'></a>";
				} else {
					//cleartext URL
					$Output .= "<a href='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?"."MDB=M2". $AddUrl ."'><image src='" . $IconLoc . "/mavic3t_button.png' height='" . $ButtonHeight . "' title='Klick zum welchseln der MissionsDatenbank'></a>";
				}
			}
		}
	} elseif ($_SESSION[$SessNme_MissionDBSetting]=="30T") {
		if ($AsForm) {
			if ($UseSecUrl) {
				//encripted URL
				$Output .=  "<input type='image' value='MDB' src='" . $IconLoc . "/matrice30T_button.png' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?".base64_encode("MDB=M2". $AddUrl) ."' height='" . $ButtonHeight . "'  title='Klick zum welchseln der Datenbankn'  style='background-color:transparent; border-color:transparent;vertical-align: middle;'>" .chr(10);
			} else {
				//cleartext URL
				$Output .=  "<input type='image' value='MDB' src='" . $IconLoc . "/matrice30T_button.png' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?"."MDB=M2". $AddUrl ."' height='" . $ButtonHeight . "'  title='Klick zum welchseln der Datenbankn'  style='background-color:transparent; border-color:transparent;vertical-align: middle;'>" .chr(10);
			}
		} else {
			if ($UseSecUrl) {
				//encripted URL
				$Output .= "<a href='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?". base64_encode("MDB=M2". $AddUrl) ."'><image src='" . $IconLoc . "/matrice30T_button.png' height='" . $ButtonHeight . "' title='Klick zum welchseln der MissionsDatenbank'></a>";
			} else {
				//cleartext URL
				$Output .= "<a href='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?"."MDB=M2". $AddUrl ."'><image src='" . $IconLoc . "/matrice30T_button.png' height='" . $ButtonHeight . "' title='Klick zum welchseln der MissionsDatenbank'></a>";
			}
		}
	} else {
		// Default M3T / Mavic M3T
		//M3T -> 3T0;
		if ($AsForm) {
			if ($UseSecUrl) {
				//encripted URL
				$Output .=  "<input type='image' value='MDB' src='" . $IconLoc . "/mavic3t_button.png' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?".base64_encode("MDB=30T". $AddUrl) ."' height='" . $ButtonHeight . "'  title='Klick zum welchseln der Datenbankn'  style='background-color:transparent; border-color:transparent;vertical-align: middle;'>" .chr(10);
			} else {
				//cleartext URL
				$Output .=  "<input type='image' value='MDB' src='" . $IconLoc . "/mavic3t_button.png' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?"."MDB=30T". $AddUrl ."' height='" . $ButtonHeight . "'  title='Klick zum welchseln der Datenbankn'  style='background-color:transparent; border-color:transparent;vertical-align: middle;'>" .chr(10);
			}
		} else {
			if ($UseSecUrl) {
				//encripted URL
				$Output .= "<a href='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?". base64_encode("MDB=30T". $AddUrl) ."'><image src='" . $IconLoc . "/mavic3t_button.png' height='" . $ButtonHeight . "' title='Klick zum welchseln der MissionsDatenbank'></a>";
			} else {
				//cleartext URL
				$Output .= "<a href='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?"."MDB=30T". $AddUrl ."'><image src='" . $IconLoc . "/mavic3t_button.png' height='" . $ButtonHeight . "' title='Klick zum welchseln der MissionsDatenbank'></a>";
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
	foreach ($WeatherOptions as $key => $Option) {
		//pre_r($Option);
		if ($Option["text"]==$Text) {
			return $Option["Opt"]; 
			break;
		}	
	}
	return "";
	
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
// url endcoding
// ===========================================

function is_urlEncoded($url){
	$query = parse_url($url)['query'];
	$is_encoded=base64_decode($query, true);
	
	//if ($is_encoded) {
	//	echo "<br><br>SecURL ON";
	//	echo '<br>Yes it is a base64 encoded string\n\n';
	//	pre_R($SecParamters);
	//} else {
	//	echo "<br><br>SecURL OFF";
	//	echo '<br>Not a Base64-encoded string\n\n';
	//}
	
	return $is_encoded; 
}

function GetParamter_from_Encoded_Url($url, $parameter){
	$query = parse_url($url)['query'];
	//echo $query . "<br>";
	parse_str(base64_decode(parse_url($_SERVER['REQUEST_URI'])['query']),$url);
	//pre_R($url);
	return $url[$parameter];
}

function GetParamters_from_Encoded_Url($url){
	$query = parse_url($url)['query'];
	//echo $query . "<br>";
	parse_str(base64_decode(parse_url($_SERVER['REQUEST_URI'])['query']),$url);
	//pre_R($url);
	return $url;
}


// ===========================================
// url inputs
// ===========================================
	
function GetURLtoFalseTrue($SecParameter, $GetParameter, $PostParameter="", $Default=false) {
global $SecUrl, $EnforceUseSecUrl, $SecParamters;
	//supporting on/off - true/false - "" = default
	if ($SecUrl) {
		$Value= strtolower($SecParamters[$SecParameter]);
	} elseif (isset($_GET[$GetParameter]) and !$EnforceUseSecUrl) {
		$Value =strtolower($_GET[$GetParameter]);
	} elseif (($PostParameter!="") and (isset($_POST[$PostParameter]))) {
		$Value =strtolower($_POST[$PostParameter]);
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


function GetURLwDefault($SecParameter, $GetParameter, $PostParameter="", $Default="") {
global $SecUrl, $EnforceUseSecUrl, $SecParamters;

	if ($SecUrl) {
		$Value= htmlspecialchars($SecParamters[$SecParameter]);
	} elseif (isset($_GET[$GetParameter]) and !$EnforceUseSecUrl) {
		$Value =htmlspecialchars($_GET[$GetParameter]);
	} elseif (($PostParameter!="") and (isset($_POST[$PostParameter]))) {
		$Value =htmlspecialchars($_POST[$PostParameter]);
	}
	
	if ($Value=="") {
		//default
		$Value = $Default;
	}
	return $Value;
}


function GetURLwDefault_Lower($SecParameter, $GetParameter, $PostParameter="", $Default="") {
global $SecUrl, $EnforceUseSecUrl, $SecParamters;
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

?>
