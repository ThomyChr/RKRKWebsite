<?php
include_once(__DIR__ . '/rkrk_php80.php');
include_once(__DIR__ . '/rkrk_config.php');
include_once(__DIR__ . '/rkrk_favicon.php');
//
// Mission settings
//session_start();
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
	Return $_SESSION[$SessionKey][$Cname]??'';
}

//============================
// Secure URL
// ===========================
$SecUrl=false;
$UseSecUrl=false;  // preference to use encoded SecURL where implemented, does not block $_GET
$EnforceUseSecUrl=false; // to enforce use of encoded URL, does not allow anymore the use of standard $_GET
$Debugoutput="";
$WhitelistGroup ="";
$WebLocation = "";
$RkrkLocation  = "";

$Con="";
$WebFreigabe="";

$ThisHost = $Con . rkrk_server('HTTP_HOST');
$ThisPage = $Con . rkrk_server('HTTP_HOST') . rkrk_server('PHP_SELF');
$ThisPageURL = rkrk_server('SERVER_NAME') . ':' . rkrk_server('SERVER_PORT') . rkrk_server('REQUEST_URI');
$ThisURL  = rkrk_server('SERVER_NAME') . ':' . rkrk_server('SERVER_PORT');
$phpSelfDir = pathinfo(rkrk_server('PHP_SELF'), PATHINFO_DIRNAME);
$ThisPageDirectory = $Con . rkrk_server('HTTP_HOST') . rkrk_str($phpSelfDir);



//============================
// Layout
//============================

// GENERAL SWITCHES
// Layout: 
//	2022: PC layout
//	2023: optimiert für Mobile Phones und Tablets


// Layout 2023 ist Standard. Query Layout= wird ignoriert (alte 2022-Schale entfällt).
$Layout = "2023";
WriteSessionVar("Layout", $Layout);

// JG-Startansicht: Kacheln oder Kartenübersicht — Chrome bleibt 2023.
if (isset($_GET["MapView"])) {
	$mv = strtolower(trim((string)$_GET["MapView"]));
	$MapView = ($mv === "2022" || $mv === "old" || $mv === "map" || $mv === "karte") ? "2022" : "2023";
	WriteSessionVar("MapView", $MapView);
} elseif (GetSessionVar("MapView") != "") {
	$MapView = GetSessionVar("MapView");
	if ($MapView !== "2022" && $MapView !== "2023") {
		$MapView = "2023";
	}
} else {
	$MapView = "2023";
	WriteSessionVar("MapView", $MapView);
}
//echo "LayoutSetting: " . $Layout . "<br>";


//Layoutcolor
if ($SecUrl) 
	$BaseColor= strtolower($SecParamters["Color"]??'');
elseif (isset($_GET["Color"])) {
	$BaseColor = $_GET["Color"];
} else	{
	$brandingColor = 'white';
	if (function_exists('rkrk_config_branding')) {
		$brandingColor = rkrk_config_branding()['default_color'];
	}
	$BaseColor = $brandingColor;
}

//setpic directory — absolut unter /rkropen/favicon/ (relativ ../favicon/ von assess/ → 404 unter /test/favicon/)
$picdir = "../favicon/";
if (function_exists('rkrk_assess_favicon_base_web_path')) {
	$picdir = rkrk_assess_favicon_base_web_path();
} elseif (!empty($_SERVER['SCRIPT_NAME']) && preg_match('#^(.*?/rkropen)/#i', str_replace('\\', '/', (string)$_SERVER['SCRIPT_NAME']), $m)) {
	$picdir = $m[1] . '/favicon/';
}
$ImageLoc = "images/";

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
						"test27" => Array(	"key" => "test",
											"directory" => "test27/",
											"picdir" => "../favicon/Reh_FA840B_orange/",
											"WebFreigabe" => "test",
											),
						"test" => Array(	"key" => "test",
											"directory" => "test/",
											"picdir" => "../favicon/Reh_FFABAB_red/",
											"WebFreigabe" => "test",
											),
						"rkrs1" => Array(	"key" => "rkrs",
											"directory" => "rehkitzrettung.ch",
											"picdir" => "images/",
											"WebFreigabe" => "rkrs",
											),
						"rkrs2" => Array(	"key" => "rkrs",
											"directory" => "/rkrs/calc",
											"picdir" => "images/",
											"WebFreigabe" => "rkrs",
											),
						 );

	//pre_R($AccessLevels);

	//setpic directory
	//echo "<br><br><br>Layout: " . $Layout  . "<br>";
	$PStyle = "BW";
	if ($PStyle=="BW") {
		if (($Layout == "2023")) {
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

	if ( isset( $_SERVER['HTTPS'] ) ) {
		$Con =  "https://";
		//$WebLocation  = $Con . "tchr-frog.synology.me:5682/rkropen/";
		$WebLocation  = $Con . "tchr-frog.synology.me/rkropen/";
		$RkrkLocation  = $Con . "tchr-frog.synology.me/rkropen/rkrk";
	} else {
		$Con =  "http://";
		//$WebLocation  = $Con . "tchr-frog.synology.me:5681/rkropen/";
		$WebLocation  = $Con . "tchr-frog.synology.me/rkropen/";
		$RkrkLocation  = $Con . "tchr-frog.synology.me/rkropen/rkrk";
	}
	$RKRSFaviconLocation = $Con . "tchr-frog.synology.me/rkropen/favicon/";
	$hostLc = isset($_SERVER['HTTP_HOST']) ? strtolower((string)$_SERVER['HTTP_HOST']) : '';
	if ($hostLc !== '' && strpos($hostLc, 'portal.rehkitzrettung.ch') !== false) {
		// Portal: Favicons unter /rkrsplusassessment/favicon/ (nie relativ ../RKRS.ico)
		$sn = preg_replace('#/+#', '/', str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '')));
		$parts = $sn === '' ? array() : explode('/', trim($sn, '/'));
		$portalBase = '/rkrsplusassessment/favicon/';
		foreach ($parts as $i => $seg) {
			if (strcasecmp((string)$seg, 'rkrsplusassessment') === 0) {
				$portalBase = '/' . implode('/', array_slice($parts, 0, $i + 1)) . '/favicon/';
				break;
			}
		}
		$RKRSFaviconLocation = $portalBase;
	}

	$WebLink_public = $WebLocation . "pub/";

	//echo "<br><br><br>AA:" . $_SERVER['PHP_SELF'] . "  " . $key . "<br>";
	foreach ($AccessLevels as $key => $AcLevel) {
		if (strpos(rkrk_server('HTTP_HOST') . rkrk_server('PHP_SELF'), rkrk_str($AcLevel["directory"])) != false) {
			$WebLink_intern = $WebLocation . $AcLevel["directory"];
			$picdir= $AcLevel["picdir"];
			$WebFreigabe = $AcLevel["WebFreigabe"]; // WebFreigabe wird durch rkrk_access_control.php überschrieben!!!
			$DebugWork= false;
			break;
		}
	}
	if (function_exists('rkrk_favicon_apply_to_globals')) {
		rkrk_favicon_apply_to_globals();
	}

	
	
	$ThisHost = $Con . rkrk_server('HTTP_HOST');
	$ThisPage = $Con . rkrk_server('HTTP_HOST') . rkrk_server('PHP_SELF');
	$ThisPageURL = rkrk_server('SERVER_NAME') . ':' . rkrk_server('SERVER_PORT') . rkrk_server('REQUEST_URI');
	$ThisURL  = rkrk_server('SERVER_NAME') . ':' . rkrk_server('SERVER_PORT');
	$phpSelfDir = pathinfo(rkrk_server('PHP_SELF'), PATHINFO_DIRNAME);
	$ThisPageDirectory = $Con . rkrk_server('HTTP_HOST') . rkrk_str($phpSelfDir);

	// check if code runs on RKRS server
	if ($WebFreigabe=="rkrs") {
		$onRKRS = true;
		$Layout= "2023";
		$ImageLoc = "images/";
		//echo "RKRS...";
	} else {
		$onRKRS = false;
	} 


if (is_urlEncoded(rkrk_server('REQUEST_URI'))) {
	$SecParamters = GetParamters_from_Encoded_Url(rkrk_server('REQUEST_URI'));
	$SecUrl = true;
	//pre_R($SecParamters);
	//echo count($SecParamters);
}

	$KatalogBaseline = "29.04.2023";
	

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

// ==================
// Header Page
// ==================


if (!function_exists('rkrk_ci_is_rkrs')) {
function rkrk_ci_is_rkrs() {
	if (function_exists('rkrk_favicon_is_rkrs_chrome')) {
		return rkrk_favicon_is_rkrs_chrome();
	}
	if (!empty($GLOBALS['onRKRS'])) {
		return true;
	}
	$host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
	if ($host !== '' && (
		strpos($host, 'portal.rehkitzrettung.ch') !== false
		|| strpos($host, 'tools.rehkitzrettung.ch') !== false
	)) {
		return true;
	}
	$sn = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
	$sf = str_replace('\\', '/', (string)($_SERVER['SCRIPT_FILENAME'] ?? ''));
	if (preg_match('#/(rkrs)(/|$)#i', $sn) || preg_match('#/(rkrs)(/|$)#i', $sf)) {
		return true;
	}
	if (isset($GLOBALS['AddURL']) && in_array((string)$GLOBALS['AddURL'], array('RKRS', 'RKRSplus', 'RKRSprev'), true)) {
		return true;
	}
	if (isset($_GET) && is_array($_GET)) {
		foreach (array('RKRS', 'RKRSplus', 'RKRSprev') as $k) {
			if (array_key_exists($k, $_GET)) {
				return true;
			}
		}
	}
	return false;
}
}

if (!function_exists('rkrk_ci_theme_name')) {
function rkrk_ci_theme_name() {
	return rkrk_ci_is_rkrs() ? 'rkrs' : 'rkrk';
}
}

function rkrk_html_open_tag() {
	$theme = htmlspecialchars(rkrk_ci_theme_name(), ENT_QUOTES, 'UTF-8');
	echo "<html data-rkrk-theme='" . $theme . "'>\n";
}

function rkrk_theme_link_tags($diroffset = '') {
	echo "  <LINK href='" . htmlspecialchars($diroffset . 'template/rkrk_theme.css?v=20261015logo', ENT_QUOTES, 'UTF-8') . "' rel='stylesheet' type='text/css'>\n";
}

function headerpage($title ,$mobile,$diroffset="") {
	global 	$Layout, 
			$picdir, 
			$IconLoc,
			$ImageLoc,
			$onRKRS,
			$BaseColor,
			$ThisPage,
			$ThisHost,
			$RKRSFaviconLocation;

	$skipLegacyFavicon = false;

	//echo "<br><br>BaseColor: " . $BaseColor . " Layout: " .$Layout . " Auf RKRS: " . $onRKRS . "<br>";
	if ($onRKRS){
		echo "<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.01 Transitional//EN'>" . chr(10);
		rkrk_html_open_tag();
		echo "<head>" . chr(10);
		echo "<meta meta http-equiv='Content-Type' content='text/html;charset=UTF-8'>" . chr(10);
		echo "<meta http-equiv='expires' content='0'>" . chr(10);
		echo "<meta http-equiv='Pragma' content='no-cache'>" . chr(10);
		echo "<meta http-equiv='Cache-Control' content='no-cache, must-revalidate, max-age=0'>" . chr(10);
		echo "<META http-equiv=author content='Thomas Christen'>" . chr(10);
		echo "<META http-equiv=description content='" .$title. "'>" . chr(10);
		echo "<META http-equiv=keywords content='" .$title. "'>" . chr(10);
		echo "<meta name='apple-mobile-web-app-capable' content='yes' />" . chr(10);
		if (function_exists('rkrk_favicon_emit_link_tags')) {
			rkrk_favicon_emit_link_tags();
		}
		echo "  <LINK href='template/main_scratch.css' rel='stylesheet' type='text/css'>" . chr(10);
		echo "  <LINK href='template/main_green.css' rel='stylesheet' type='text/css'>" . chr(10);
		echo "  <LINK href='template/rkrk_reg_pills.css?v=20260907nodbl' rel='stylesheet' type='text/css'>" . chr(10);
		rkrk_theme_link_tags('');
		echo "<meta name='viewport' content='width=device-width, initial-scale=1'>" . chr(10);
		echo "<title>" .$title. "</title>" .chr(10);
		echo "<link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css'>" . chr(10);
		echo "  <meta name='msapplication-TileColor' content='#ffffff'>" . chr(10);
		echo "</head>" . chr(10);
		//echo "<div><br><br>ico:". $ThisHost . "/rkropen/favicon/RKRS/". "RKRS.ico</div>" . chr(10);
	} elseif ($Layout=="2023"){
		echo "<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.01 Transitional//EN'>" . chr(10);
		rkrk_html_open_tag();
		echo "<head>" . chr(10);
		echo "<meta meta http-equiv='Content-Type' content='text/html;charset=UTF-8'>" . chr(10);
		echo "<meta http-equiv='expires' content='0'>" . chr(10);
		echo "<meta http-equiv='Pragma' content='no-cache'>" . chr(10);
		echo "<meta http-equiv='Cache-Control' content='no-cache, must-revalidate, max-age=0'>" . chr(10);
		echo "<META http-equiv=author content='Thomas Christen'>" . chr(10);
		echo "<META http-equiv=description content='" .$title. "'>" . chr(10);
		echo "<META http-equiv=keywords content='" .$title. "'>" . chr(10);
		//echo "<meta name='apple-mobile-web-app-capable' content='yes' />" . chr(10);
		if (function_exists('rkrk_favicon_emit_link_tags')) {
			rkrk_favicon_emit_link_tags();
		}
		//echo "  <link rel='manifest' href='" . $picdir . "manifest.json'>" . chr(10);
		echo "  <LINK href='" . $diroffset . "template/main_scratch.css' rel='stylesheet' type='text/css'>" . chr(10);
		//if (($BaseColor=="green")) {
			echo "  <LINK href='" . $diroffset . "template/main_green.css' rel='stylesheet' type='text/css'>" . chr(10);
			echo "  <LINK href='" . $diroffset . "template/rkrk_reg_pills.css?v=20260907nodbl' rel='stylesheet' type='text/css'>" . chr(10);
			rkrk_theme_link_tags($diroffset);
		//} else {
		//	echo "  <LINK href='" . $diroffset . "template/main_white.css' rel='stylesheet' type='text/css'>" . chr(10);
		//}
		echo "<meta name='viewport' content='width=device-width, initial-scale=1'>" . chr(10);

		echo "<link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css'>" . chr(10);
		echo "  <meta name='msapplication-TileColor' content='#ffffff'>" . chr(10);
		echo "<title>" .$title. "</title>" .chr(10);
		echo "</head>" . chr(10);
		if (strpos($ThisPage, "/test/")) {
			echo "<body>";
		} else	{
			echo "<body oncontextmenu='return false;'>";
		}			
	} else {
		echo "<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.01 Transitional//EN'>" . chr(10);
		rkrk_html_open_tag();
		echo "<head>" . chr(10);
		echo "<meta meta http-equiv='Content-Type' content='text/html;charset=UTF-8'>" . chr(10);
		echo "<meta http-equiv='expires' content='0'>" . chr(10);
		echo "<meta http-equiv='Pragma' content='no-cache'>" . chr(10);
		echo "<meta http-equiv='Cache-Control' content='no-cache, must-revalidate, max-age=0'>" . chr(10);
		echo "<META http-equiv=author content='Thomas Christen'>" . chr(10);
		echo "<META http-equiv=description content='" .$title. "'>" . chr(10);
		echo "<META http-equiv=keywords content='" .$title. "'>" . chr(10);
		//echo "<meta name='apple-mobile-web-app-capable' content='yes' />" . chr(10);
	/*	if ($mobile) {
			echo "<meta id='viewport' name='viewport' content='width=device-width, initial-scale=1' />" . chr(10);
			echo "<meta http-equiv='Content-Type' content='application/xhtml+xml; charset=iso-8859-1'/>" . chr(10);
			echo "<link rel='stylesheet' href='https://code.jquery.com/mobile/1.4.5/jquery.mobile-1.4.5.min.css' />" . chr(10);
			echo "<script src='https://code.jquery.com/jquery-1.11.1.min.js'></script>" . chr(10);
			echo "<script src='https://code.jquery.com/mobile/1.4.5/jquery.mobile-1.4.5.min.js'></script>" . chr(10);
		}
	*/
		if (function_exists('rkrk_favicon_emit_link_tags')) {
			rkrk_favicon_emit_link_tags();
		}
		//echo "  <link rel='manifest' href='" . $picdir . "manifest.json'>" . chr(10);
		if (($BaseColor=="green")) {
			echo "  <LINK href='" . $diroffset . "template/main_style_green.css' rel='stylesheet' type='text/css'>" . chr(10);
		} else {
			echo "  <LINK href='" . $diroffset . "template/main_style_white.css' rel='stylesheet' type='text/css'>" . chr(10);
		}
		echo "  <LINK href='" . $diroffset . "template/rkrk_reg_pills.css?v=20260907nodbl' rel='stylesheet' type='text/css'>" . chr(10);
		echo "  <LINK href='" . $diroffset . "template/nmenu_style.css' rel='stylesheet' type='text/css'>" . chr(10);
		rkrk_theme_link_tags($diroffset);
		echo "  <meta name='msapplication-TileColor' content='#ffffff'>" . chr(10);
		echo "</head>" . chr(10);
		if (strpos($ThisPage, "/test/")) {
			echo "<body>";
		} else	{
			echo "<body oncontextmenu='return false;'>";
		}			
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


// ===========================================
// url endcoding
// ===========================================

function is_urlEncoded($url){
	$query = parse_url(rkrk_str($url), PHP_URL_QUERY);
	if ($query === null || $query === false) {
		$query = '';
	}
	$is_encoded=base64_decode(rkrk_str($query), true);
	
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
	$query = parse_url(rkrk_str($url), PHP_URL_QUERY);
	if ($query === null || $query === false) {
		$query = parse_url(rkrk_server('REQUEST_URI'), PHP_URL_QUERY);
	}
	if ($query === null || $query === false) {
		$query = '';
	}
	$decoded = base64_decode(rkrk_str($query), true);
	if ($decoded === false) {
		$decoded = '';
	}
	parse_str($decoded, $params);
	return $params[$parameter] ?? '';
}

function GetParamters_from_Encoded_Url($url){
	$query = parse_url(rkrk_str($url), PHP_URL_QUERY);
	if ($query === null || $query === false) {
		$query = parse_url(rkrk_server('REQUEST_URI'), PHP_URL_QUERY);
	}
	if ($query === null || $query === false) {
		$query = '';
	}
	$decoded = base64_decode(rkrk_str($query), true);
	if ($decoded === false) {
		$decoded = '';
	}
	parse_str($decoded, $params);
	return is_array($params) ? $params : array();
}



function pre_r($array) {
	echo "<pre>";
	print_r($array);
	echo "</pre>";
}
?>
