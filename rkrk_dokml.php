<?php
	//session_start();
	include_once('scripts/rkrk_gen.php');
	include_once('scripts/rkrk_kml.php');
	include_once('scripts/rkrk_felddaten.php');
	include_once('scripts/rkrk_csvfiles.php');

	if (!function_exists('rkrk_dokml_kml_coord_list')) {
		function rkrk_dokml_kml_coord_list($coords, $close = true) {
			if (!is_array($coords)) {
				return '';
			}
			$out = '';
			$first = '';
			$last = '';
			foreach ($coords as $value) {
				if (!is_array($value) || !isset($value[0]) || $value[0] === '' || $value[0] === null) {
					continue;
				}
				if (is_array($value[0])) {
					return rkrk_dokml_kml_coord_list($value, $close);
				}
				if (!isset($value[1])) {
					continue;
				}
				$pt = $value[0] . ', ' . $value[1] . '     ';
				$out .= $pt;
				if ($first === '') {
					$first = $pt;
				}
				$last = $pt;
			}
			if ($close && $first !== '' && $first !== $last) {
				$out .= $first;
			}
			return $out;
		}
	}

	if (!function_exists('rkrk_dokml_apply_mission_geometry')) {
		function rkrk_dokml_apply_mission_geometry($feld) {
			global $RKRKLocalLoactionMissionFiles;
			if (!($feld instanceof SimpleXMLElement)) {
				return;
			}
			$area = trim((string)$feld->Flaechendaten);
			$path = trim((string)$feld->Flugweg);
			$start = trim((string)$feld->Grundstueckdaten->Startplatz);
			if ($area !== '' && $path !== '' && $start !== '') {
				return;
			}
			$rawList = trim((string)$feld->JasonFile);
			if ($rawList === '') {
				return;
			}
			$files = function_exists('rkrk_explode_csv')
				? rkrk_explode_csv($rawList)
				: preg_split('/\s*,\s*/', $rawList);
			if (!is_array($files)) {
				return;
			}
			$root = rtrim(str_replace('\\', '/', (string)$RKRKLocalLoactionMissionFiles), '/');
			$data = null;
			foreach ($files as $rel) {
				$rel = ltrim(str_replace('\\', '/', (string)$rel), '/');
				if ($rel === '' || $root === '') {
					continue;
				}
				$full = $root . '/' . $rel;
				if (!is_file($full)) {
					continue;
				}
				$json = json_decode((string)file_get_contents($full), true);
				if (is_array($json)) {
					$data = $json;
					break;
				}
			}
			if (!is_array($data)) {
				return;
			}
			$features = (isset($data['geojson']['features']) && is_array($data['geojson']['features']))
				? $data['geojson']['features']
				: array();
			if ($area === '' && $features !== array()) {
				$built = rkrk_dokml_kml_coord_list($features[0]['geometry']['coordinates'] ?? null);
				if ($built === '' && isset($features[1])) {
					$built = rkrk_dokml_kml_coord_list($features[1]['geometry']['coordinates'] ?? null);
				}
				if ($built !== '') {
					$feld->Flaechendaten = $built;
				}
			}
			if ($path !== '' && $start !== '') {
				return;
			}
			$flugweg = '';
			$startplatz = $start;
			foreach ($features as $feat) {
				if (!is_array($feat)) {
					continue;
				}
				$geom = (isset($feat['geometry']) && is_array($feat['geometry'])) ? $feat['geometry'] : array();
				if (($geom['type'] ?? '') !== 'Point') {
					continue;
				}
				$c = (isset($geom['coordinates']) && is_array($geom['coordinates'])) ? $geom['coordinates'] : array();
				if (!isset($c[0], $c[1])) {
					continue;
				}
				$props = (isset($feat['properties']) && is_array($feat['properties'])) ? $feat['properties'] : array();
				$ptype = isset($props['type']) ? (string)$props['type'] : '';
				$height = isset($props['height']) ? (float)$props['height'] : 0.0;
				if ($startplatz === '' && $ptype === '6') {
					$startplatz = $c[0] . ', ' . $c[1];
				}
				if ($height > 0 && $ptype === '1') {
					$flugweg .= $c[0] . ', ' . $c[1] . ', ' . $height . '    ';
				} elseif ($startplatz === '') {
					$startplatz = $c[0] . ', ' . $c[1];
				}
			}
			if ($path === '' && $flugweg !== '') {
				$feld->Flugweg = $flugweg;
			}
			if ($start === '' && $startplatz !== '') {
				if (!isset($feld->Grundstueckdaten)) {
					$feld->addChild('Grundstueckdaten');
				}
				$feld->Grundstueckdaten->Startplatz = $startplatz;
			}
		}
	}

	if (!function_exists('rkrk_status_has')) {
		function rkrk_status_has($value, $code) {
			$value = strtoupper(trim(function_exists('rkrk_str') ? rkrk_str($value) : (string)$value));
			$code = strtoupper(trim((string)$code));
			if ($value === '' || $code === '') {
				return false;
			}
			return strpos($value, $code) !== false;
		}
	}

	if (!headers_sent()) {
		header('Content-Type: application/vnd.google-earth.kml+xml; charset=UTF-8');
		// map.geo.admin.ch liest die KML im Browser. Genau ein Access-Control-Allow-Origin: *
		// ist nötig. "*, *" blockiert die Flugzonen, ein fehlender Header ebenfalls.
		$hasOrigin = false;
		foreach (headers_list() as $sentHeader) {
			if (stripos($sentHeader, 'Access-Control-Allow-Origin:') === 0) {
				$hasOrigin = true;
				break;
			}
		}
		if (!$hasOrigin) {
			header('Access-Control-Allow-Origin: *');
		}
		header('Access-Control-Allow-Methods: GET, OPTIONS');
		header('Cache-Control: no-store');
	}
	if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
		http_response_code(204);
		exit;
	}

	
	//check impropriate IP addresses accessing page
	CheckIPandExit();
	$SaveDebugOutput = true;
	$debutoutput = true;
	$DebugText="";
	$DownloadOnly = false;
	$htmloutput = false;
	$kmloutputfile = "";

	// Vorgefertigte KML aus kmlfiles/ ausliefern (kurze URL, z.B. geflogene Flächen)
	$UseFile = '';
	if (isset($_GET['UseFile']) && trim((string)$_GET['UseFile']) !== '') {
		$UseFile = basename(preg_replace('/\.kml$/i', '', trim((string)$_GET['UseFile'])));
	} elseif (!empty($SecUrl) && isset($SecParamters['UseFile']) && trim((string)$SecParamters['UseFile']) !== '') {
		$UseFile = basename(preg_replace('/\.kml$/i', '', trim((string)$SecParamters['UseFile'])));
	}
	if ($UseFile !== '') {
		$servePath = $kmlFilelocations . $UseFile . '.kml';
		if (!is_file($servePath) && isset($kmlFilelocations)) {
			$servePath = rtrim((string)$kmlFilelocations, '/\\') . DIRECTORY_SEPARATOR . $UseFile . '.kml';
		}
		if (is_file($servePath)) {
			$dlOnly = false;
			if (isset($_GET['DL'])) {
				$dlVal = strtolower((string)$_GET['DL']);
				$dlOnly = in_array($dlVal, array('1', 'on', 'true', 'yes'), true);
			}
			if ($dlOnly) {
				header('Content-disposition: attachment; filename=' . $UseFile . '.kml');
			}
			readfile($servePath);
			exit;
		}
		header('HTTP/1.1 404 Not Found');
		echo '<!-- KML not found: ' . htmlspecialchars($UseFile) . ' -->';
		exit;
	}

	// ===========================================
	// Paramter
	// ===========================================
/*
	http://tchr-frog.synology.me:5681/rkropen/htmltest/rkrk_xml2kml.php?Reg=21&File=21_Felder&Deb=on
	http://tchr-frog.synology.me:5681/rkropen/htmltest/rkrk_dokml.php?Reg=25&Strt=On&File=25_kml&Poi=On&WebAccess='.$Webaccess.'
	http://tchr-frog.synology.me:5681/rkropen/htmltest/rkrk_dokml.php?Reg=ALL&Strt=On&Cnrt=On&Area=On&Pfad=On&Poi=On&File=ALL_kml&WebAccess='.$Webaccess.'
	Reg : selektiere Regtion z.B, 21,15,25, ZG, AG, ALL
	File : file zum speicher der Daten z.B. "21_Felder" (extension braucht es nicht!)
	Deb : enable debug output
	Html : enable html output
	Strt : Output Startpoint (on/off)
	Cntr : Output Zentrumspunkt  (on/off) 
	Area : Output Flightarea (on/off)
	Pfad : Output Flightpath (on/off)
	GDE : Output Gemeindegrenzen (on/off)
	JGG : Output Jagdgebietsgrenzen (on/off)
	HSP: Hochspannungsleitungen (on/off)
	Sun: Morgensonne (on/off)
	KT: Kantone (on/off)
	ORT: Hauptorte mit Label (on/off)
	WW: Wegweiser,Reiseroute anzeigen (on/off)
	SORA: SORA Zone (on/off)
	Label : Output Label (on/off)
	Poi : Output POI daten (on/off)
	EX: External view (YES/NO) - for showing internal data
	MAP:
	FFilter: Filter Felder (21-002 21-006)
	SFilter: Filter Suchfilter (Flurname/ Feldname)
	Zoom : Grösse der Bilder und der Schriften
	JG: Jagdgebiet
	FZ: Flugzone (on/off)
	EG: Einsatzgebiet (on/off)
	KA: Kartenart
	LAT: Lat centerpoint map
	LON: Lon centerpoint map
	ZO: Zoom Karte map
	OVR: Overviewpicture setting Suchplan
	SCOL: single color (on/off)
	FZO: Flight Zone Options
		FHGO-NF1: FHGO nur eine Nachflugzone
		FHGO-NF2: FHGO nur zwei Nachflugzonen
		FHGO-STP: FHGO gesperrt
	FLP: Overviewpicture setting Flugplanbild
	Webaccess: Zugriffslevel
	Title: Text, wenn leer dann Standardtitel
    MakeSVG: SVG Bild erstellen
    MakePNG: PNG Bild erstellen
    MakeJPG: JPG Bild erstellen
	BBoxEast  = Bildausschnitt bbox_east
	BBoxNorth = Bildausschnitt bbox_north
	BBoxZoom  = Bildausschnitt bbox_zoom

*/
	if ($htmloutput) {
		echo "<h1> Erstellen KML files</h1>";
	}
	
	// ===========================================
	// Defintions
	// ===========================================
		$fileoutput = "";
//		$kmlFilelocations  = "kmlfiles/";
//		$weblocation = "http://tchr-frog.synology.me:5681/rkropen/html/";
//		$kmlFilelocations ="kmlfiles/";
//	$RKRKFelderfileXML = 'RKRKFelder.xml';
//	$xmlMissionLocation = "xmlfiles/";


	$DataOutput = "";
	$ObjectInfo = "all";  // all: alle/ simple: ohne Links/ none: keine
	// Strt: Startpunkt,Cntr: Zentrumspunkt,Area: Feld,Pfad: Flugpfad; Prk: Parkplatz; Label: Label
	//$ObjectInfoEnable = Array("Strt","Cntr","Area","Pfad","Prk","Label");
	$ObjectInfoEnable = Array("Strt","Cntr","Area");


	// ===========================================
	// check input paramter
	// ===========================================
	//Region
	
	if ($SecUrl) {
		$keys= array_keys($SecParamters);
		parse_str($keys[0], $SecSubParamters);
		if (count($SecSubParamters)> 1) {
			// call throught map else direct call of do_kml
			$SecParamters=$SecSubParamters;
		}	
		
		foreach ($SecParamters as $key => $value)
		{
			$DebugText .= "- " . $key . " => " . $value . chr(10);
		}
		//echo $DebugText . chr(10) . chr(10);
		//echo "-->";
	}

	
	$RegionSelected = "ALL";  //default
	if ($SecUrl) {
		$regParam = trim((string)($SecParamters["Reg"] ?? ''));
		if ($regParam === '' || strcasecmp($regParam, 'alle') === 0) {
			$RegionSelected = 'ALL';
		} else {
			$RegionSelected = str_replace("_", " ", str_replace("alle", "ALL", htmlspecialchars($regParam)));
		}
	} elseif (isset($_GET["Reg"]) && trim((string)$_GET["Reg"]) !== '') {
		$RegionSelected = str_replace("alle", "ALL", htmlspecialchars($_GET["Reg"]));
	} else {
		$RegionSelected = "ALL"; // default
	}
	
	$DebugText .= "Reg: " . $RegionSelected . chr(10);
	
/*

	if (empty($_GET["Reg"])) {
		$RegionSelected = "ALL";
	} else {
		$RegionSelected =str_replace("alle","ALL",htmlspecialchars($_GET["Reg"]));
	}
*/
	
	// File
	if ($SecUrl) {
		$kmloutputfile= htmlspecialchars($SecParamters["File"]);
		$SaveFile = true;
	} elseif (isset($_GET["File"])) {
		$kmloutputfile =htmlspecialchars($_GET["File"]);
		$SaveFile = true;
	}
			$DebugText .= "- KML Outputfile  > " . $kmloutputfile . chr(10);

	
	if ($kmloutputfile=="") {
		//default
		//$kmloutputfile = "NoFile";
		$kmloutputfile = "RKRK_Felder_" .  str_replace(" ","_", $RegionSelected);
		$SaveFile = false;
	}

	$DebugText .= "File: " . $kmloutputfile . chr(10);

	// DL=1 -> nur lokaler Browser-Download, kein Speichern auf Drive/Server
	if ($SecUrl) {
		$dlVal = isset($SecParamters["DL"]) ? strtolower((string)$SecParamters["DL"]) : "";
		$DownloadOnly = in_array($dlVal, array("1","on","true","yes"), true);
	} elseif (isset($_GET["DL"])) {
		$dlVal = strtolower((string)$_GET["DL"]);
		$DownloadOnly = in_array($dlVal, array("1","on","true","yes"), true);
	}
	$DebugText .= "DownloadOnly: " . ($DownloadOnly ? "on" : "off") . chr(10);
	if (!headers_sent()) {
		$kmlDownloadName = preg_replace('/[^A-Za-z0-9._-]+/', '_', (string)$kmloutputfile);
		if ($kmlDownloadName === '' || $kmlDownloadName === null) {
			$kmlDownloadName = 'RKRK_Felder';
		}
		if ($DownloadOnly) {
			header('Content-Disposition: attachment; filename="' . $kmlDownloadName . '.kml"');
		} else {
			header('Content-Disposition: inline; filename="' . $kmlDownloadName . '.kml"');
		}
	}

/*	
	if (empty($_GET["File"])) {
		//$kmloutputfile = "NoFile";
		$kmloutputfile = "RKRK_Felder_" .  str_replace(" ","_", $RegionSelected);
		$SaveFile = false;
	} else {
		$kmloutputfile =htmlspecialchars($_GET["File"]);
		$SaveFile = true;
	}
	header("Content-disposition: attachment; filename=" . $kmloutputfile . ".kml");
*/

	// Debugging output
	$debugoutput = GetURLtoFalseTrue("Deb", "Deb");
	$DebugText .= "Debug: " . $debugoutput . chr(10);

	// enable html output
	$htmloutput = GetURLtoFalseTrue("Html", "Html");
	$DebugText .= "html: " . $htmloutput . chr(10);

	//Output Startpoint (on/off)
	$StartpunktOn = GetURLtoFalseTrue("Strt", "Strt");
	$DebugText .= "Strt: " . $StartpunktOn . chr(10);

	//Output Zentrumspunkt  (on/off))
	$ZentrumspunktOn = GetURLtoFalseTrue("Cntr", "Cntr");
	$DebugText .= "Cntr: " . $ZentrumspunktOn . chr(10);

	//Output Flightarea (on/off)
	$AreaOn = GetURLtoFalseTrue("Area", "Area");
	$DebugText .= "Area: " . $AreaOn . chr(10);

	//Output Flightpath (on/off)
	$PfadOn = GetURLtoFalseTrue("Pfad", "Pfad");
	$DebugText .= "Pfad: " . $PfadOn . chr(10);

	//GDE : Output Gemeindegrenzen (on/off)
	$GdeOn = GetURLtoFalseTrue("GDE", "GDE");
	$DebugText .= "GDE: " . $GdeOn . chr(10);

	//JGG : Output Jagdgebietsgrenzen (on/off)
	$JggOn = GetURLtoFalseTrue("JGG", "JGG");
	$DebugText .= "JGG: " . $JggOn . chr(10);

	//HSP: Hochspannungsleitungen (on/off)
	$HSPOn = GetURLtoFalseTrue("HSP", "HSP");
	$DebugText .= "HSP: " . $HSPOn . chr(10);

	//Sun: Morgensonne (on/off)
	$SunOn = GetURLtoFalseTrue("Sun", "Sun");
	$DebugText .= "Sun: " . $SunOn . chr(10);

	//KT: Kantone (on/off)
	$KTOn = GetURLtoFalseTrue("KT", "KT");
	$DebugText .= "KT: " . $KTOn . chr(10);

	//ORT: Hauptorte mit Label (on/off)
	$ORTOn = GetURLtoFalseTrue("ORT", "ORT");
	$DebugText .= "ORT: " . $ORTOn . chr(10);

	//WF Wasserflächen anzeigen (on/off)
	$WFOn = GetURLtoFalseTrue("WF", "WF");
	$DebugText .= "WF: " . $WFOn . chr(10);

	//WW: Wegweiser,Reiseroute anzeigen (on/off)
	$WWOn = GetURLtoFalseTrue("WW", "WW");
	$DebugText .= "WW: " . $WWOn . chr(10);

	//SORA: SORA Zone (on/off)
	$SORAOn = GetURLtoFalseTrue("SORA", "SORA");
	$DebugText .= "SORA: " . $SORAOn . chr(10);

	//Label : Output Label (on/off)
	$LabelOn = GetURLtoFalseTrue("Label", "Label");
	$DebugText .= "Label: " . $LabelOn . chr(10);

	//Poi : Output POI daten (on/off)
	$PoiOn = GetURLtoFalseTrue("Poi", "Poi");
	$DebugText .= "Poi: " . $PoiOn . chr(10);

	//FZ: Flugzone (on/off)
	$FZOn = GetURLtoFalseTrue("FZ", "FZ");
	$DebugText .= "FZ: " . $FZOn . chr(10);

	//EG: Einsatzgebiet (on/off)
	$EGOn = GetURLtoFalseTrue("EG", "EG");
	$DebugText .= "EG: " . $EGOn . chr(10);

	//OVR: Overviewpicture setting Suchplan
	$Overview = GetURLtoFalseTrue("OVR", "OVR");
	$DebugText .= "OVR: " . $Overview . chr(10);

	//SCOL: single color (on/off)
	$SingleColor = GetURLtoFalseTrue("SCOL", "SCOL");
	$DebugText .= "SCOL: " . $SingleColor . chr(10);
	if ($SingleColor)
		$JggOn = true;

	//FZO: Flight Zone Options
	$FlightZoneOptions = GetURLwDefault("FZO", "FZO", "", "");
	$DebugText .= "FZO: " . $FlightZoneOptions . chr(10);

	//FLP: Overviewpicture setting Flugplanbild
	$Flugplan = GetURLtoFalseTrue("FLP", "FLP");
	$DebugText .= "FLP: " . $Flugplan . chr(10);

	//Alert (on/off)
	$AlertOn = GetURLtoFalseTrue("Alert", "Alert");
	$DebugText .= "Alert: " . $AlertOn . chr(10);


	// Not simplified //
	//------------------
	
	//EX: External view (YES/NO) - for showing internal data
	//MAP:
	//FFilter: Filter Felder (21-002 21-006)
	//Zoom : Grösse der Bilder und der Schriften
	//KA: Kartenart
	//LAT: Lat centerpoint map
	//LON: Lon centerpoint map
	//ZO: Zoom Karte map
// ------------------------------------------


	//FFilter: Filter Felder (21-002 21-006)
	$FFilter = str_replace("+"," ",str_replace("_"," ",GetURLwDefault("FFilter", "FFilter", "", "")));
	$FFilterIds = array();
	if (trim((string)$FFilter) !== '') {
		$FFilterIds = preg_split('/[\s,;+|]+/', str_replace(array('%2B', '%2b'), ' ', (string)$FFilter), -1, PREG_SPLIT_NO_EMPTY);
		$FFilterIds = array_values(array_unique(array_filter(array_map('trim', $FFilterIds), function ($v) {
			return $v !== '';
		})));
	}
	$DebugText .= "FFilter: " . $FFilter . chr(10);

	// AreaOnly: nur diese Feld-Polygone zeichnen. Nicht FFilter — der zoomt und blendet nicht aus.
	$AreaOnly = GetURLtoFalseTrue("AreaOnly", "AreaOnly");
	$AreaIdsRaw = str_replace(array('%2B', '%2b', '_'), array('+', '+', ' '), GetURLwDefault("AreaIds", "AreaIds", "", ""));
	$AreaIdSet = array();
	if ($AreaOnly) {
		foreach (preg_split('/[\s,;+|]+/', (string)$AreaIdsRaw, -1, PREG_SPLIT_NO_EMPTY) as $areaId) {
			$areaId = trim((string)$areaId);
			if ($areaId !== '') {
				$AreaIdSet[$areaId] = true;
			}
		}
	}
	$DebugText .= "AreaOnly: " . ($AreaOnly ? 'on' : 'off') . " n=" . count($AreaIdSet) . chr(10);

	//SFilter: Filter Suchfilter (Flurname/ Feldname)
	$SFilter = str_replace("_"," ",GetURLwDefault("SFilter", "SFilter", "", ""));
	$DebugText .= "SFilter: " . $SFilter . chr(10);

	//Zoom : Grösse der Bilder und der Schriften
	$Zoom = str_replace("_",".",GetURLwDefault("Zoom", "Zoom", "", 1));
	$DebugText .= "Zoom: " . $Zoom . chr(10);

	//Webaccess: Zugriffslevel
	$WebFreigabe = GetURLwDefault("WebAccess", "WebAccess", "", "gast");
	$DebugText .= "WebAccess: " . $WebFreigabe . chr(10);

	//Dokumententitel (Text)
	$KMLDocTitle = GetURLwDefault("Title", "Title", "", "");
	$DebugText .= "Titel: " . $KMLDocTitle . chr(10);



	//KA (F,S,G,M)
	$MapTyp = GetURLwDefault("KA", "KA", "", "S");
	$DebugText .= "KA: " . $MapTyp . chr(10);

    // NEU: Formatwahl
    $MakeSVG = GetURLtoFalseTrue("SVG", "SVG");
    $MakePNG = GetURLtoFalseTrue("PNG", "PNG");
    $MakeJPG = GetURLtoFalseTrue("JPG", "JPG");
	$BBoxEast  = GetURLwDefault("bbox_east", "bbox_east", "", "");
	$BBoxNorth = GetURLwDefault("bbox_north", "bbox_north", "", "");
	$BBoxZoom  = GetURLwDefault("bbox_zoom", "bbox_zoom", "", "");
	$AutoMode = GetURLtoFalseTrue("auto", "auto");

	
	// ===========================================
	// Read Files
	// ===========================================
	// load Felder XML
	if (function_exists('SetMissionLocation')) {
		SetMissionLocation();
	}

	$fileloaded = true;
	$catalogPath = $xmlMissionLocation.$RKRKFelderfileXML;
	if (function_exists('ReadCatalogFile')) {
		$xml_felder = ReadCatalogFile($catalogPath, $debugoutput);
	} elseif (function_exists('rkrk_catalog_load_simplexml')) {
		$xml_felder = rkrk_catalog_load_simplexml($catalogPath);
	} else {
		$xml_felder = false;
	}
	if (!$xml_felder) {
		$fileloaded = false;
	}

	//Write to LOGfile
	WriteToLog("do", basename($_SERVER['PHP_SELF']), "kml file ",$RKRKMissionSelected, $RegionSelected,"");

//neu für Listen 02.03.2023
$RegionsSelected = rkrk_map_region_jg_list($RegionSelected);
$RegionList = is_array($RegionsSelected) ? implode(' ', $RegionsSelected) : 'ALL';

if ($fileloaded == true && $xml_felder) {

	// ===========================================
	// Print Page content
    // ===========================================
	if ($KMLDocTitle<>"")
		$addTitle = $KMLDocTitle;
	elseif ($RegionSelected=="NO") {
		$addTitle = "POI";
	} else {
		$addTitle = $RegionSelected;
	}
	$fileoutput = kmlheader($addTitle);
	$fileoutput .= "<!-- " . date("Y-m-d H:i:s") . " -->" . chr(10) . chr(10);
	$ListOfStylesUsed = Array();

	//read POI KML data
    if ($PoiOn or $JggOn or $GdeOn or $EGOn or $HSPOn or $WFOn or $FZOn or $AlertOn or $LabelOn or $SORAOn or $SunOn or $ORTOn or $KTOn) {
	  $fileoutput .= GetPoiKML($RegionSelected, false, false, $PoiOn, $HSPOn, $WFOn, $SORAOn, $SunOn, $JggOn, $GdeOn, $KTOn, $ORTOn, $FZOn, $AlertOn ,$EGOn, $LabelOn, "NoFile", $ListOfStylesUsed);
	}  
//
	$arrayLength = count($xml_felder);
	//echo $arrayLength;
	$i=0;
	//while ($i < $arrayLenght && $i<2) {

	foreach ($xml_felder as $feld) {
		$feldJg = rkrk_feld_jg_from_feld_id($feld->FeldID);
		$regionMatch = ($RegionsSelected === null) || in_array($feldJg, $RegionsSelected, true);
		$feldIdStr = trim((string)$feld->FeldID);
		$feldInFilter = ($FFilterIds === array());
		if (!$feldInFilter) {
			foreach ($FFilterIds as $wantId) {
				if (strcasecmp($feldIdStr, (string)$wantId) === 0) {
					$feldInFilter = true;
					break;
				}
			}
		}
		//next layer 
		if (
			$regionMatch
			and $feldInFilter
			and (		
					($SFilter=="")
					or like($feld ->Feldname, $SFilter)
					or like($feld ->Flurname, $SFilter)
			)
			and (		
					!$SingleColor or
					($SingleColor
					and ($feld ->Freigabe != "NO"))
			)
	//		(($RegionSelected=="ALL") or (in_array(substr($feld ->FeldID,0,2), $RegionsSelected)))
	//		(($RegionSelected=="ALL") or (in_array(substr($feld ->FeldID,0,strpos($feld ->FeldID,"-")), $RegionsSelected)))
	//		 and (($FFilter=="")  or (strpos($FFilter, strval($feld ->FeldID))!== false)) 
	//	if ((($RegionSelected=="ALL") or (substr($feld ->FeldID,0,2)==$RegionSelected))
	//		 and (($FFilter=="")  or (strpos($FFilter, strval($feld ->FeldID))!== false)) 


			) {;
			$emitField = (!$AreaOnly) || isset($AreaIdSet[$feldIdStr]);
			rkrk_dokml_apply_mission_geometry($feld);
			$Hotlinkinfo = "";
			if ($WebFreigabe !="gast") {

/* old before 01. April 2025 */
				// $Hotlinkinfo with pictures
				$Hotlinkinfo .= '<description>' .chr(10);
				$Hotlinkinfo .= '<![CDATA[' .chr(10);
				if ($ObjectInfo != "none") {
					$Hotlinkinfo .= $feld->FeldID. '  ' .$feld->Feldname . " (" . $feld->Gemeinde.  " ".$feld->GrundstueckID . ")";
					//$Hotlinkinfo .=  '    <description><![CDATA[' . $feld->FeldID. '  ' .$feld->Feldname . " (" . $feld->Gemeinde.  " ".$feld->GrundstueckID . ")" .chr(10);

					if (GetAccess("AllPilots")){
						if ($feld->Bauer!="") {
							$Hotlinkinfo .=  '<br>'. $feld->Bauer . chr(10);
						}
					}
					$Hotlinkinfo .= "<br>";
					foreach ($OptionWarning as $Key => $Wrn) {
						if (like($feld->Warnung, $Key)) {
							$Hotlinkinfo .= " " . "<img src='" . $weblocation ."/".$IconLoc . $Wrn["PicSmall"] . "' height='" . $ButtonHeightSmall ."' title='" . $Wrn["Pic"] . "'> ";
						}
					}
				}
				if (($ObjectInfo == "all") or ($ObjectInfo == "all")) {
					if (GetAccess("BookFlight")){
						$Hotlinkinfo .=  '<a href="' . $weblocation . '/rkrk_fluege.php?add=Y&AddFlugID=' . $feld->FeldID  . '&AddFlugToDate=' . date("Y-m-d", strtotime('tomorrow')). '" target="_editfluege">  Flug ' . $feld->FeldID  . ' buchen für Morgen <img src="' . $weblocation ."/".$IconLoc . '/addflug_35.png" height="' . $ButtonHeight .'" title="Flug ' . $feld->FeldID  . ' buchen für Morgen"></a>';
						//rkrk_fluege.php?add=Y&AddFlugID=AG-038&AddFlugToDate=2022-11-18
					}

					//$Hotlinkinfo .=  '<br><br><A href="' . $weblocation . "/" .$htmlfile . '#' . $feld->FeldID . '" target="_blank"><img src="' . $weblocation ."/".$IconLoc . '/feldliste.png" height="' . $ButtonHeight .'" title="Online Flugfeldkatalog">Online Flugfeldkatalog</A>';
					//https://tchr-frog.synology.me:5682/rkropen/test/rkrk_felderliste.php?Feld=136-005
					$Hotlinkinfo .=  '<A href="' .$weblocation. '/rkrk_felderliste.php?Feld=' . $feld->FeldID  . '" target="_blank">Daten zu Feld ' . $feld->FeldID  . ' <img src="' . $weblocation ."/".$IconLoc . '/feld_35.png" height="' . $ButtonHeight .'" title="Detaildaten zu Feld ' . $feld->FeldID  . '" ></A>';
						
					if (GetAccess("EditFields")){
						$Hotlinkinfo .=  '<a href="' . $weblocation . '/rkrk_edit_felddatenliste.php?Reg=alle&FeldNamenWahl=' . $feld->FeldID  . '" target="_editfieldlist">Feld ' . $feld->FeldID  . ' editiern <img src="' . $weblocation ."/".$IconLoc . '/edit_35.png" height="' . $ButtonHeight .'" title="Fluege editieren"></a>';
						//rkrk_edit_felddatenliste.php?Reg=alle&FeldNamenWahl=ZG2-110
					}

					if (GetAccess("MapDownloadKMZ")){
						$kmzList = function_exists('rkrk_explode_csv') ? rkrk_explode_csv($feld->KMZFile) : explode(",", (string)$feld->KMZFile);
						foreach ($kmzList as $key => $file) {
							if ($file<>"") {
								$Hotlinkinfo .=  '<a href="' .$RKRKExternalLoactionMissionFiles.$file . '" download> ' . $file  . ' <img src="' . $weblocation ."/".$IconLoc . '/dwnld_kmz_35.png" height="' . $ButtonHeight .'" title="KMZ File (für M3T) herunterladen"></a>';
							}
						}
					}
			//		if (GetAccess("MapDownloadKML")){
			//			foreach (explode(",", $feld->KMLFile) as $key => $file) {
			//				if ($file<>"") {
			//					$Hotlinkinfo .=  '<br><br><a href="' .$RKRKExternalLoactionMissionFiles.$file . '" download> ' . $file  . ' <img src="' . $weblocation ."/".$IconLoc . '/dwnld_kml_35.png" height="' . $ButtonHeight .'" title="KML File herunterladen"></a>';
			//				}
			//			}
			//		}

					if (GetAccess("MapDownloadJSON")){
						$jsonList = function_exists('rkrk_explode_csv') ? rkrk_explode_csv($feld->JasonFile) : explode(",", (string)$feld->JasonFile);
						foreach ($jsonList as $key => $file) {
							if ($file<>"") {
								$Hotlinkinfo .=  '<a href="' .$RKRKExternalLoactionMissionFiles.$file .'"  download>';
								$Hotlinkinfo .=   $file . ' ' ;
								$Hotlinkinfo .= '<img src="' . $weblocation ."/".$IconLoc . '/dwnld_json_35.png" height="' . $ButtonHeight .'" title="JSON File herunterladen (öffnet im Browser, muss dann speparat gespeichert werden)."> ';
								$Hotlinkinfo .=  '</a>';
							}			//https://tchr-frog.synology.me:5682/rkropen/test/rkrk_felddetail.php?Reg=AZ&Typ=intern&Feld=AZ-001&LAT=8.42406996360290&LON=47.2541697622291&MAP=11
						}
					}
				}
				
				$Hotlinkinfo .=  ']]>' . chr(10);
				$Hotlinkinfo .=  '</description>' . chr(10);
				$Hotlinkinfo = str_replace($searchSD, $replaceSD,$Hotlinkinfo);
				
/* Neu seit 01. April 2025 // Bilder/Links und CR anzeigen funktioniert nicht.
				$Hotlinkinfo .= $feld->FeldID. '<br>' .$feld->Feldname . " (" . $feld->Gemeinde.  " ".$feld->GrundstueckID . ")";

				if (GetAccess("AllPilots")){
					if ($feld->Bauer!="") {
						$Hotlinkinfo .=  '<br>' . $feld->Bauer . chr(10);
					}
				}
				$Hotlinkinfo .= "
				";
				foreach ($OptionWarning as $Key => $Wrn) {
					if (like($feld->Warnung, $Key)) {
						//$Hotlinkinfo .= " " . "&lt;img src='" . $weblocation ."/".$IconLoc . $Wrn["PicSmall"] . "' height='" . $ButtonHeightSmall ."' title='" . $Wrn["Pic"] . "'&gt ";
						//$Hotlinkinfo .= ' ' .  '<img src="https://tchr-frog.synology.me/rkropen/test/images/23color/button_ort_on.png" style="max-height:100px;">';
					}
				}
				//$Hotlinkinfo .= '<br>' . '<a target="_blank" href="https://tchr-frog.synology.me/rkropen/test/rkrk_map.php">link</a>';
				$Hotlinkinfo .= '<![CDATA[<a target="_blank" href="https://tchr-frog.synology.me/rkropen/test/rkrk_map.php">RKRK<a>]]';
/*
1
2
3
4

&lt;a target="_blank" href="https://tchr-frog.synology.me/rkropen/test/rkrk_map.php"&gt;RKRK&lt;/a&gt;

&lt;image src="https://tchr-frog.synology.me/rkropen/test/images/23color/button_geolayer_on.png" style="max-height:200px;"/&gt;
https://tchr-frog.synology.me/rkropen/test/images/23color/button_M_on.png

Blabla bla
weiter bla bla bla 

				$Hotlinkinfo = '<description>'  .chr(10) . str_replace($searchHTML, $replaceHTML, $Hotlinkinfo). '</description>'.chr(10);
/****/
			}
				
			if ($htmloutput) {
			  echo "<li>" .$feld ->FeldID . " - " . $feld ->Feldname . "</li>";
			}
			if (rkrk_status_has($feld->Freigabe, "OK")) {
				$feldstatus = "free";
			}
			if (rkrk_status_has($feld->Freigabe, "TFL")) {
				$feldstatus = "testflight";
			}
			if (rkrk_status_has($feld->Warnung, "OBJ")) {
				$feldstatus = "object";
			}
			//Richtig auch für neue Flugzonen NGT1, NGT2 ab 2024
			if (rkrk_status_has($feld->Warnung, "NGT")) {
				$feldstatus = "free_night";
			}
			if (rkrk_status_has($feld->Warnung, "NGT") && rkrk_status_has($feld->Warnung, "OBJ")){
				$feldstatus = "object_night";
			}
			if (rkrk_status_has($feld->Warnung, "SORA")) {
				$feldstatus = "150zone";
			}
			if (rkrk_status_has($feld->Warnung, "STP")) {
				$feldstatus = "restrict";
			}
			if (rkrk_status_has($feld->Freigabe, "PRF")) {
				$feldstatus = "tocheck";
			}
			if (rkrk_status_has($feld->Freigabe, "NO")) {
				$feldstatus = "retire";
			}
			if (rkrk_status_has($feld->Freigabe, "NEU")) {
				$feldstatus = "new";
			}
		// startpunkt 
			// <!-- ===== STARTPOSITION ====== -->
			// <Placemark>
			//   <name>TF-007</name>
			//   <description><![CDATA[TF-007 26_Bergli_West 149<br>    <A href='http://tchr-frog.synology.me:5681/rkropen/html/flugplaenetfext.html#TF-007' target='_blank'>Online Flugfeldkatalog</A>]]></description>
			//   <styleUrl>#startpoint</styleUrl>
			//   <Point>
			//     <coordinates>
			//         8.454534407833894, 47.22617263699161  ,0
			//     </coordinates>
			//   </Point>
			// </Placemark>

			//Parkplatz
			if (($StartpunktOn) and ($feld->Grundstueckdaten->Parkplatz<>"")) {
				$fileoutput .=  '  <!-- ===== Label ' . $feld->FeldID. '  ' .$feld->Feldname .'====== -->' . chr(10);
				$fileoutput .=  '  <Placemark id='. chr(34) . "P".$feld->FeldID . chr(34) . '>' . chr(10);
				if ($LabelOn) {
					$fileoutput .=  '    <name>' . str_replace($searchSD, $replaceSD,$feld->FeldID). '</name>' . chr(10);
				}
				//$fileoutput .=  '    <name>' . $feld->FeldID. '  ' .$feld->Feldname .'</name>' . chr(10);
				if (in_array("Prk",$ObjectInfoEnable))
					$fileoutput .= $Hotlinkinfo;
				$fileoutput .=  '  <styleUrl>#parking</styleUrl>' . chr(10);
				$ListOfStylesUsed['parking']= 'label';
				$fileoutput .=  '  <Point>' . chr(10);
				$fileoutput .=  '    <coordinates>' . chr(10);
				$fileoutput .=  $feld->Grundstueckdaten->Parkplatz . chr(10);
				$fileoutput .=  '    </coordinates>' . chr(10);
				$fileoutput .=  '  </Point>' . chr(10);
				$fileoutput .=  '  </Placemark>' . chr(10);
				$fileoutput .=  Chr(10);
			}
			//if ($StartpunktOn and $Overview==false) {
			if ($emitField && ($StartpunktOn or $Overview)) {
				$fileoutput .=  '  <!-- ===== Startpunkt ' . $feld->FeldID. '  ' .$feld->Feldname .'====== -->' . chr(10);
				$fileoutput .=  '  <Placemark id='. chr(34) . "S".$feld->FeldID . chr(34) . '>' . chr(10);
				// Name hängt am Startpunkt, nicht am Flächen-Label (Label=on/off).
				$fileoutput .=  '    <name>' . str_replace($searchSD, $replaceSD,$feld->FeldID). '</name>' . chr(10);
				//$fileoutput .=  '    <name>' . $feld->FeldID. '  ' .$feld->Feldname .'</name>' . chr(10);
				if (in_array("Strt",$ObjectInfoEnable))
					$fileoutput .= $Hotlinkinfo;
				$fileoutput .=  '  <styleUrl>#startpoint</styleUrl>' . chr(10);
				$ListOfStylesUsed['startpoint']= 'label';
				$fileoutput .=  '  <Point>' . chr(10);
				$fileoutput .=  '    <coordinates>' . chr(10);
				$fileoutput .=  $feld->Grundstueckdaten->Startplatz . chr(10);
				$fileoutput .=  '    </coordinates>' . chr(10);
				$fileoutput .=  '  </Point>' . chr(10);
				$fileoutput .=  '  </Placemark>' . chr(10);
				$fileoutput .=  Chr(10);
			}
		// Zentrumspunkt 
			if ($emitField && $ZentrumspunktOn) {
				$fileoutput .=  '  <!-- ===== Zentrumspunkt ' . $feld->FeldID. '  ' .$feld->Feldname .'====== -->' . chr(10);
				$fileoutput .=  '  <Placemark id='. chr(34) . "Z".$feld->FeldID . chr(34) . '>' . chr(10);
				// Nummer nur am Zentrum, wenn der Startpunkt aus ist. Sind beide an, trägt der Start die Nummer.
				if (!$StartpunktOn && !$Overview) {
					$fileoutput .=  '    <name>' . str_replace($searchSD, $replaceSD,$feld->FeldID). '</name>' . chr(10);
				}
				//$fileoutput .=  '    <name>' . $feld->FeldID. '  ' .$feld->Feldname .'</name>' . chr(10);
				if (in_array("Cntr",$ObjectInfoEnable))
					$fileoutput .= $Hotlinkinfo;
				$fileoutput .=  '  <styleUrl>#centerpoint</styleUrl>' . chr(10);
				$ListOfStylesUsed['centerpoint']= 'label';
				$fileoutput .=  '  <Point>' . chr(10);
				$fileoutput .=  '    <coordinates>' . chr(10);
				$fileoutput .=  $feld->Grundstueckdaten->Zentrum . chr(10);
				$fileoutput .=  '    </coordinates>' . chr(10);
				$fileoutput .=  '  </Point>' . chr(10);
				$fileoutput .=  '  </Placemark>' . chr(10);
				$fileoutput .=  Chr(10);
			}
			// Feldnummer mittig, sobald die Fläche an ist und weder Start noch Zentrum gezeigt werden.
			// Unabhängig vom Schalter Beschriftung (Label).
			if ($emitField && $AreaOn && $ZentrumspunktOn==false && $StartpunktOn==false && !$Overview) {
				$fileoutput .=  '  <!-- ===== Label ' . $feld->FeldID. '  ' .$feld->Feldname .'====== -->' . chr(10);
				$fileoutput .=  '  <Placemark id='. chr(34) . str_replace($searchSD, $replaceSD,"Z".$feld->FeldID) . chr(34) . '>' . chr(10);
				$fileoutput .=  '    <name>' . str_replace($searchSD, $replaceSD,$feld->FeldID). '</name>' . chr(10);
				//$fileoutput .=  '    <name>' . $feld->FeldID. '  ' .$feld->Feldname .'</name>' . chr(10);
				if (in_array("Label",$ObjectInfoEnable))
					$fileoutput .= $Hotlinkinfo;
				$fileoutput .=  '  <styleUrl>#label</styleUrl>' . chr(10);
				$ListOfStylesUsed['label']= 'label';
				$fileoutput .=  '  <Point>' . chr(10);
				$fileoutput .=  '    <coordinates>' . chr(10);
				$fileoutput .=  $feld->Grundstueckdaten->Zentrum . chr(10);
				$fileoutput .=  '    </coordinates>' . chr(10);
				$fileoutput .=  '  </Point>' . chr(10);
				$fileoutput .=  '  </Placemark>' . chr(10);
				$fileoutput .=  Chr(10);
			}
			// Label
			// Area 
			// <!-- ===== Flight AREA ====== -->
			//     <Placemark>
			//       <name>A TF_26_Bergli_West_149_50_CH_22</name>
			//   <description><![CDATA[TF-007 26_Bergli_West 149<br>    <A href='http://tchr-frog.synology.me:5681/rkropen/html/flugplaenetfext.html#TF-007' target='_blank'>Online Flugfeldkatalog</A><br>]]></description>
			//       <styleUrl>#fieldarea_free</styleUrl>
			//       <Polygon>
			//         <tessellate>1</tessellate>
			//         <altitudeMode>clampToGround</altitudeMode>
			//         <outerBoundaryIs>
			//           <LinearRing>
			//             <coordinates>8.454681929329713,47.22567263167184,0 8.453332778195222,47.22522635700244,0 8.452874120453675,47.225887570784366,0 8.454174991825898,47.22633566138597,0 8.454681929329713,47.22567263167184,0 8.454681929329713,47.22567263167184,0</coordinates>
			//           </LinearRing>
			//         </outerBoundaryIs>
			//       </Polygon>
			//     </Placemark>
			if ($AreaOn && $emitField) {
				$fileoutput .=  '  <!-- ===== Flight Area ====== -->' . chr(10);
				$fileoutput .=  '  <Placemark id=' . chr(34) ."A". $feld->FeldID . chr(34) . '>' . chr(10);
				$fileoutput .=  '    <name>' . str_replace($searchSD, $replaceSD,$feld->FeldID. '  ' .$feld->Feldname). '  ' .$feld->GrundstueckID .'</name>' . chr(10);
				if (in_array("Area",$ObjectInfoEnable))
					$fileoutput .= $Hotlinkinfo;
				$fileoutput .=  '  <styleUrl>#fieldarea_' . $feldstatus . '</styleUrl>' . chr(10);
				$ListOfStylesUsed['fieldarea_' . $feldstatus]= 'fieldarea_' . $feldstatus;
				$fileoutput .=  '  <Polygon>' . chr(10);
				$fileoutput .=  '    <tessellate>1</tessellate>' . chr(10);
				$fileoutput .=  '    <altitudeMode>clampToGround</altitudeMode>' . chr(10);
				$fileoutput .=  '    <outerBoundaryIs>' . chr(10);
				$fileoutput .=  '      <LinearRing>' . chr(10);
				$fileoutput .=  '      <coordinates>' . chr(10);
				$fileoutput .=  $feld->Flaechendaten . chr(10);
				$fileoutput .=  '        </coordinates>' . chr(10);
				$fileoutput .=  '      </LinearRing>' . chr(10);
				$fileoutput .=  '    </outerBoundaryIs>' . chr(10);
				$fileoutput .=  '  </Polygon>' . chr(10);
				$fileoutput .=  '  </Placemark>' . chr(10);
				$fileoutput .=  Chr(10);
			} elseif ($AreaOn && $AreaOnly && !$emitField) {
				$fitCoords = trim((string)$feld->Flaechendaten);
				if ($fitCoords !== '') {
					$fileoutput .=  '  <Placemark id=' . chr(34) . 'A' . $feld->FeldID . chr(34) . '>' . chr(10);
					$fileoutput .=  '    <visibility>0</visibility>' . chr(10);
					$fileoutput .=  '    <ExtendedData><Data name="rkrkFitOnly"><value>1</value></Data></ExtendedData>' . chr(10);
					$fileoutput .=  '  <styleUrl>#fieldarea_free</styleUrl>' . chr(10);
					$ListOfStylesUsed['fieldarea_free'] = 'fieldarea_free';
					$fileoutput .=  '  <Polygon>' . chr(10);
					$fileoutput .=  '    <tessellate>1</tessellate>' . chr(10);
					$fileoutput .=  '    <altitudeMode>clampToGround</altitudeMode>' . chr(10);
					$fileoutput .=  '    <outerBoundaryIs>' . chr(10);
					$fileoutput .=  '      <LinearRing>' . chr(10);
					$fileoutput .=  '      <coordinates>' . chr(10);
					$fileoutput .=  $fitCoords . chr(10);
					$fileoutput .=  '        </coordinates>' . chr(10);
					$fileoutput .=  '      </LinearRing>' . chr(10);
					$fileoutput .=  '    </outerBoundaryIs>' . chr(10);
					$fileoutput .=  '  </Polygon>' . chr(10);
					$fileoutput .=  '  </Placemark>' . chr(10);
					$fileoutput .=  Chr(10);
				}
			}
		// Flightpath 
			//<!-- ===== Flight PATH ====== -->
			//    <Placemark>
			//      <name>F TF_26_Bergli_West_149_50_CH_22</name>
			//  <description><![CDATA[TF-007 26_Bergli_West 149<br> <A href='http://tchr-frog.synology.me:5681/rkropen/html/flugplaenetf.html#TF-007' target='_blank'>Online Flugfeldkatalog</A><br>]]></description>
			//      <styleUrl>#flightpath_free</styleUrl>
			//      <LineString>
			//        <extrude>1</extrude>
			//        <tessellate>1</tessellate>
			//        <altitudeMode>relativeToGround</altitudeMode>
			//        <coordinates>8.454534407833894,47.22617263699161,20 8.454514479940766,47.22573579985978,52 8.453369774047037,47.2253415021444,42 8.453251222008182,47.22551241166111,42 8.45438633349859,47.225903404849475,50 8.454258186245132,47.226071009687644,49 8.453132669203434,47.22568332104694,41 8.453014115632781,47.225854230301856,40 8.454130038180383,47.2262386143743,46</coordinates>
			//      </LineString>
			//    </Placemark>
			if ($emitField && $PfadOn) {
				$fileoutput .=  '  <!-- ===== Flight Path ====== -->' . chr(10);
				$fileoutput .=  '  <Placemark id=' . chr(34) ."F". $feld->FeldID . chr(34) . '>' . chr(10);
				$fileoutput .=  '    <name>' . str_replace($searchSD, $replaceSD,$feld->FeldID. '  ' .$feld->Feldname) . '  ' .$feld->GrundstueckID .'</name>' . chr(10);
				if (in_array("Path",$ObjectInfoEnable))
					$fileoutput .= $Hotlinkinfo;
				$fileoutput .=  '  <styleUrl>#flightpath_' . $feldstatus . '</styleUrl>' . chr(10);
				$ListOfStylesUsed['flightpath_' . $feldstatus]= 'flightpath_' . $feldstatus;
				$fileoutput .=  '  <LineString>' . chr(10);
				$fileoutput .=  '    <extrude>1</extrude>' . chr(10);
				$fileoutput .=  '    <tessellate>1</tessellate>' . chr(10);
				$fileoutput .=  '    <altitudeMode>relativeToGround</altitudeMode>' . chr(10);
				$fileoutput .=  '      <coordinates>' . chr(10);
				$fileoutput .=  $feld->Flugweg . chr(10);
				$fileoutput .=  '        </coordinates>' . chr(10);
				$fileoutput .=  '  </LineString>' . chr(10);
				$fileoutput .=  '  </Placemark>' . chr(10);
				$fileoutput .=  Chr(10);
			}
		}
		$i++;
	}
	$fileoutput .=  Chr(10) . Chr(10);
	
	
	// Create Waypath
	$TravelWayPath = Array();
	$TravelWayPoints = Array();
	 if (($FFilter!="") and $WWOn) {

		$Feldkatalog= GetFeldkatalog($xmlMissionLocation.$RKRKFelderfileXML);
		//$fileoutput .=  '  <!-- ' . chr(10);
		//$fileoutput .=  $xmlMissionLocation.$RKRKFelderfileXML . chr(10);
		//$fileoutput .=  '   -->' . chr(10);

		$PointList = explode(" ", $FFilter);
		$Points="";
		foreach ($PointList as $Point) {
			//$Points .= "<!-- Waypoints: " . $Point . " -->" .  chr(10) ;
			$Points .= $Feldkatalog[explode("-",$Point)[0]][$Point]["Grundstueckdaten"]["Startplatz"] . " ";
		}
		$fileoutput .=  '  <!-- ===== Travel Waypoints ====== -->' . chr(10);
		$fileoutput .=  '  <Placemark id=' . chr(34) ."Reiseweg" . chr(34) . '>' . chr(10);
		$fileoutput .=  '    <name>' . "Reiseweg" .'</name>' . chr(10);
		$fileoutput .=  '  <styleUrl>#Travel</styleUrl>' . chr(10);
		$ListOfStylesUsed['Travel']= 'Travel';
		$fileoutput .=  '  <LineString>' . chr(10);
		$fileoutput .=  '    <extrude>1</extrude>' . chr(10);
		$fileoutput .=  '    <tessellate>1</tessellate>' . chr(10);
		$fileoutput .=  '    <altitudeMode>relativeToGround</altitudeMode>' . chr(10);
		$fileoutput .=  '      <coordinates>' . chr(10);
		$fileoutput .=  $Points . chr(10);
		$fileoutput .=  '        </coordinates>' . chr(10);
		$fileoutput .=  '  </LineString>' . chr(10);
		$fileoutput .=  '  </Placemark>' . chr(10);
		$fileoutput .=  chr(10) . chr(10);
	}


	//Add POI from Addressfile
	//Selected according to Region selected and POIon == true
	$csv_desc = ';';
	$csv_file= $RKRKContactFile;
	$data = csv_to_array($csvDocLocation.$csv_file,$csv_desc);
	//pre_r($data);	
	foreach ($data as $key => $dataline) {
		if (
				$PoiOn  
				and ($dataline["GeoMapLoc"]<>"")
				// blende Jäger ohne Kontakt aus, aber zeige alle Piloten an
				and ((($dataline["Kontakt"]=="Ja") and like($dataline["Typ"],"Jäger")) or !(like($dataline["Typ"],"Jäger")))
				
				and (
				(!like($dataline["Typ"],"Jäger") and xlike($dataline["Jagdgebiet"], str_replace(" ", ",", $RegionSelected) , ",")) 
				or (like($dataline["Typ"],"Jäger") and xlike($dataline["JG"], str_replace(" ", ",", $RegionSelected) , ","))
				or ($RegionSelected=="leer")
				or ($RegionSelected=="alle")
				or ($RegionSelected=="ALL")
				)
			){
			$GeoLocations = explode (";",$dataline["GeoMapLoc"]);
			//GeoMapLoc: LON (~8), LAT (~46)
			//In Google Map typically LAT (~46) , LON (~8) 
			//Switzerland: Latitude: 46° 48' 47.47" N · Longitude: 8° 13' 27.16" E

			$i = 0;
			foreach ($GeoLocations as $Geolocation) {
				$i++;
				$fileoutput .=  '  <!-- ===== Adresspunkte $dataline["ID"] ====== -->' . chr(10);
				$fileoutput .=  '  <Placemark id=' . chr(34) . $dataline["ID"].'-'.$i . chr(34) . '>' . chr(10);
				$fileoutput .=  '    <name>' . $dataline["JG"] . "/ " . $dataline["Name"] . "  "  . $dataline["Typ"] . '</name>' . chr(10);
				switch ($dataline["Typ"]) {
					case "Pilot RKRK":
						$fileoutput .=  '  <styleUrl>#FPLF</styleUrl>' . chr(10);
						$ListOfStylesUsed['FPLF']= 'FPLF';
						break;
					case "Pilot Zug":
						$fileoutput .=  '  <styleUrl>#FPLZ</styleUrl>' . chr(10);
						$ListOfStylesUsed['FPLZ']= 'FPLZ';
						break;
					case "Pilot Neu":
						$fileoutput .=  '  <styleUrl>#FPLN</styleUrl>' . chr(10);
						$ListOfStylesUsed['FPLN']= 'FPLN';
						break;
					case "Pilot inactive":
						$fileoutput .=  '  <styleUrl>#FPLX</styleUrl>' . chr(10);
						$ListOfStylesUsed['FPLX']= 'FPLX';
						break;
					case "Pilot":
						$fileoutput .=  '  <styleUrl>#FPLP</styleUrl>' . chr(10);
						$ListOfStylesUsed['FPLP']= 'FPLP';
						break;
					case "Jäger" or "Jäger Amt":
						$fileoutput .=  '  <styleUrl>#JGR</styleUrl>' . chr(10);
						$ListOfStylesUsed['JGR']= 'JGR';
						break;
				}
				$fileoutput .=  '<description><![CDATA['.  $dataline["Name"] . '<br>' . $dataline["Kontaktnummer"]  . '<br><br>' . $dataline["Adresse"] . '<br>' . $dataline["Platform"].']]></description>' . chr(10);
				$fileoutput .=  '  <Point>' . chr(10);
				$fileoutput .=  '    <coordinates>' . chr(10);
				$fileoutput .=  '      ' . $Geolocation . chr(10);
				$fileoutput .=  '    </coordinates>' . chr(10);
				$fileoutput .=  '  </Point>' . chr(10);
				$fileoutput .=  '  </Placemark>' . chr(10);
				$fileoutput .=  Chr(10);
			}
		}
	}
	
	//finish output file
	//$fileoutput = str_replace($searchSDZ,$replaceSDZ,$fileoutput);
	//echo $kmldata;
	if (($StartpunktOn && $ZentrumspunktOn == false) 
		and !$Overview and !$Flugplan){
		$SZLabel = true;
	}	

	$fileoutput .=  kmlstyles($Zoom, $ListOfStylesUsed); 

	//DEBUG OUTPUT
	//$fileoutput .=  '< ---------- DEBUG -------------->' . chr(10); 
	//$fileoutput .=  "FZO: " . $FlightZoneOptions . chr(10); 
	
	$fileoutput .=  kmlfooter();

	if ($SaveDebugOutput) {
		$DebugText = "<!-- Debug information" . chr(10) . chr(10) . $DebugText . chr(10) . "-->". chr(10) . chr(10) . chr(10) . chr(10);
	}

	if (!$DownloadOnly) {
		$filepath= $kmlFilelocations  . $kmloutputfile . ".kml";
		$saveOutput = $fileoutput;
		if ($SaveDebugOutput) {
			$saveOutput = $fileoutput . $DebugText;
		}
		file_put_contents($filepath, $saveOutput);
	}

	echo $fileoutput;

	// SVG/PNG/JPG-Export: nur bei expliziter Anforderung (sonst kein HTML nach </kml>!)
	if ($MakeSVG || $MakePNG || $MakeJPG) {
		require_once("scripts/rkrk_kml2svg.php");

		$outputBase = $kmlFilelocations . $kmloutputfile;

		$GLOBALS["BBoxEast"]  = $BBoxEast;
		$GLOBALS["BBoxNorth"] = $BBoxNorth;
		$GLOBALS["BBoxZoom"]  = $BBoxZoom;

		kml_to_all_text(
			$fileoutput,
			$outputBase,
			2000,
			28,
			200.0,
			$MakeSVG,
			$MakePNG,
			$MakeJPG
		);
	}

	exit;
}

// Leeres KML wenn Felder-XML nicht geladen werden konnte (Map bleibt ohne HTTP 500)
echo kmlheader('RKRK');
echo '<!-- Felder-XML konnte nicht geladen werden: ' . htmlspecialchars($xmlMissionLocation . $RKRKFelderfileXML) . ' -->' . chr(10);
echo kmlfooter();
exit;

?>
