<?php

//MapOptions


$MapSettings = Array(
				"Types" => Array(
						"F" => Array( 	
							"Text" => "SwissTop Farbe",
							"IconOn" => "button_F_on.png",
							"IconOff" => "button_F_off.png",
							"Type" => "ch.swisstopo.pixelkarte-farbe",
							"Value" => true,
							"Access" => "test, intern, partner, extern, pub",
							"Aktiv" => true,
						),
						"S" => Array( 	
							"Text" => "Swissimage",
							"IconOn" => "button_S_on.png",
							"IconOff" => "button_S_off.png",
							"Type" => "ch.swisstopo.swissimage",
							"Value" => false,
							"Opacity" => "",
							"Access" => "test, intern, partner, extern, pub",
							"Aktiv" => true,
						),
						"G" => Array( 	
							"Text" => "SwissTopo schwarz-weiss",
							"IconOn" => "button_G_on.png",
							"IconOff" => "button_G_off.png",
							"Type" => "ch.swisstopo.pixelkarte-grau",
							"Value" => false,
							"Access" => "test, intern, partner, extern, pub",
							"Aktiv" => true,
						),
						"M" => Array( 	
							"Text" => "SwissTLM",
							"IconOn" => "button_M_on.png",
							"IconOff" => "button_M_off.png",
							"Value" => false,
							"Type" => "ch.swisstopo.pixelkarte-farbe",
							"simple" => Array(
								"Layer" => "ch.swisstopo.swisstlm3d-karte-farbe",
								"Visibility" => "true",
								"Opacity" =>"",
								"Layer24" => "ch.swisstopo.swisstlm3d-karte-farbe",
							),
							"advanced" => Array(
								"Layer" => "ch.swisstopo-vd.geometa-gemeinde,ch.swisstopo-vd.geometa-grundbuch,ch.kantone.cadastralwebmap-farbe,ch.swisstopo.swisstlm3d-karte-farbe,ch.bazl.einschraenkungen-drohnen,ch.bazl.uas-aktivitaetszonen,",
								"Visibility" => "false,false,false,true,false,true",
								"Opacity" => "&layers_opacity=0.75,0.75,0.2,1,0.6,1",
								"Layer" => "ch.kantone.cadastralwebmap-farbe,f;ch.swisstopo.swisstlm3d-karte-farbe;ch.bazl.einschraenkungen-drohnen,f;ch.bazl.uas-aktivitaetszonen,f;ch.swisstopo-vd.geometa-gemeinde,f;ch.swisstopo-vd.geometa-grundbuch,f",
							),
							"Access" => "test, intern, partner, extern, pub",
							"Aktiv" => true,
						),
					),
				"MapLayer" => Array(
					"simple" => Array (
							"Layer" => "ch.bfe.elektrische-anlagen_ueber_36,ch.bazl.luftfahrthindernis,ch.bazl.einschraenkungen-drohnen,ch.bazl.uas-aktivitaetszonen",
							"Visibility" => "false,false,false,true",
							"Opacity" => "&layers_opacity=1,1,1,1,1",
							"Layer24" => "ch.bfe.elektrische-anlagen_ueber_36,f;ch.bazl.luftfahrthindernis,f;ch.bazl.einschraenkungen-drohnen,f;ch.bazl.uas-aktivitaetszonen,f;ch.bazl.wohngebiete-aulav,f;ch.swisstopo.swissalti3d-reliefschattierung,f;ch.swisstopo.swisssurface3d-reliefschattierung-multidirektional,f",
	),
					"advanced" => Array (
							"Layer" => "ch.swisstopo-vd.geometa-gemeinde,ch.swisstopo-vd.geometa-grundbuch,ch.kantone.cadastralwebmap-farbe,ch.bazl.einschraenkungen-drohnen,ch.bazl.uas-aktivitaetszonen,ch.bfe.elektrische-anlagen_ueber_36,ch.bazl.luftfahrthindernis,",
							"Visibility" => "false,false,false,false,false,true,false,false",
							"Opacity" => "&layers_opacity=0.75,0.75,0.1,1,0.6,1,1,1,1",
							//"Opacity" => "",
							"Layer24" => "ch.bafu.schutzgebiete-aulav_jagdbanngebiete,f;ch.pronatura.naturschutzgebiete,f;ch.swisstopo-vd.geometa-gemeinde,f;ch.kantone.cadastralwebmap-farbe,f;ch.swisstopo.swisstlm3d-karte-farbe,f;ch.bazl.einschraenkungen-drohnen,f;ch.bazl.uas-aktivitaetszonen,f;ch.bazl.wohngebiete-aulav,f;ch.swisstopo.swissalti3d-reliefschattierung,f;ch.swisstopo.swisssurface3d-reliefschattierung-multidirektional,f;ch.swisstopo.swisstlm3d-uebrigerverkehr,f;ch.bfe.elektrische-anlagen_ueber_36,f;ch.bazl.luftfahrthindernis,f",
					),
				),
				"Options" => Array(
					"Strt" => Array(
							"Text" => "Startpunkt",
							"IconOn" => "button_Start_on.png",
							"IconOff" => "button_Start_off.png",
							"Default" => "on",
							"Value" => true,
							"Options" => "on,off",
							"Access" => "test, intern, partner, extern, pub, gast",
							"Aktiv" => true,
						),	
					"Cntr" => Array(
							"Text" => "Zentrumspunkt",
							"IconOn" => "button_CPunkt_on.png",
							"IconOff" => "button_CPunkt_off.png",
							"Default" => "off",
							"Value" => false,
							"Options" => "on,off",
							"Access" => "test, intern, partner, extern, pub, gast",
							"Aktiv" => true,
						),	
					"Area" => Array(
							"Text" => "Feld",
							"IconOn" => "button_feld_on.png",
							"IconOff" => "button_feld_off.png",
							"Default" => "on",
							"Value" => true,
							"Options" => "on,off",
							"Access" => "test, intern, partner, extern, pub, gast",
							"Aktiv" => true,
						),	
					"Pfad" => Array(
							"Text" => "Flugpfad",
							"IconOn" => "button_pfad_on.png",
							"IconOff" => "button_pfad_off.png",
							"Default" => "off",
							"Value" => false,
							"Options" => "on,off",
							"Access" => "test, intern, partner, extern,gast",
							"Aktiv" => true,
						),	
					"WW" => Array(
							"Text" => "Reiseroute",
							"IconOn" => "button_WW_on.png",
							"IconOff" => "button_WW_off.png",
							"Default" => "off",
							"Value" => false,
							"Options" => "on,off",
							"Access" => "test, intern, partner",
							"Aktiv" => true,
						),	
					"FZ" => Array(
							"Text" => "Flugzone",
							"IconOn" => "button_FZ_on.png",
							"IconOff" => "button_FZ_off.png",
							"Default" => "on",
							"Value" => false,
							"Options" => "on,off",
							"Access" => "test, intern, partner,",
							"Aktiv" => true,
						),	
					"GDE" => Array(
							"Text" => "Gemeindegrenzen",
							"IconOn" => "button_gde_on.png",
							"IconOff" => "button_gde_off.png",
							"Default" => "off",
							"Value" => false,
							"Options" => "on,off",
							"Access" => "test, intern",
							"Aktiv" => true,
						),	
					"JGG" => Array(
							"Text" => "Jagdgebiet",
							"IconOn" => "button_jgg_on.png",
							"IconOff" => "button_jgg_off.png",
							"Default" => "off",
							"Value" => false,
							"Options" => "on,off",
							"Access" => "test, intern, partner, extern, gast",
							"Aktiv" => true,
						),	
					"KT" => Array(
							"Text" => "Kantone",
							"IconOn" => "button_kt_on.png",
							"IconOff" => "button_kt_off.png",
							"Default" => "off",
							"Value" => false,
							"Options" => "on,off",
							"Access" => "test",
							"Aktiv" => true,
						),	
					"ORT" => Array(
							"Text" => "Ort",
							"IconOn" => "button_ort_on.png",
							"IconOff" => "button_ort_off.png",
							"Default" => "off",
							"Value" => false,
							"Options" => "on,off",
							"Access" => "test",
							"Aktiv" => true,
						),	
					"Poi" => Array(
							"Text" => "Point of Interest",
							"IconOn" => "button_poi_on.png",
							"IconOff" => "button_poi_off.png",
							"Default" => "off",
							"Value" => false,
							"Options" => "on,off",
							"Access" => "test, intern, partner,",
							"Aktiv" => true,
						),	
					"HSP" => Array(
							"Text" => "Hochspannungsleitungen",
							"IconOn" => "button_HSP_on.png",
							"IconOff" => "button_HSP_off.png",
							"Default" => "off",
							"Value" => false,
							"Options" => "on,off",
							"Access" => "test, intern, partner,gast",
							"Aktiv" => true,
						),	
					"Label" => Array(
							"Text" => "Beschriftung",
							"IconOn" => "button_label_on.png",
							"IconOff" => "button_label_off.png",
							"Default" => "off",
							"Value" => false,
							"Options" => "on,off",
							"Access" => "test, intern, partner, gast",
							"Aktiv" => true,
						),	
					"GeLa" => Array(
							"Text" => "GeoMapLayers",
							"IconOn" => "button_geolayer_on.png",
							"IconOff" => "button_geolayer_off.png",
							"Default" => "off",
							"Value" => false,
							"Options" => "on,off",
							"Access" => "test, intern",
							"Aktiv" => true,
						),	
					"Sun" => Array(
							"Text" => "Morgensonne",
							"IconOn" => "button_sun_on.png",
							"IconOff" => "button_sun_off.png",
							"Default" => "off",
							"Value" => false,
							"Options" => "on,off",
							"Access" => "test, intern",
							"Aktiv" => true,
						),	
					"EG" => Array(
							"Text" => "Einsatzgebiete",
							"IconOn" => "button_EG_on.png",
							"IconOff" => "button_EG_off.png",
							"Default" => "off",
							"Value" => false,
							"Options" => "on,off",
							"Access" => "test, intern, partner, gast",
							"Aktiv" => true,
						),	
					"Alert" => Array(
							"Text" => "Flugeinschränkung",
							"IconOn" => "button_AL_on.png",
							"IconOff" => "button_AL_off.png",
							"Default" => "off",
							"Value" => false,
							"Options" => "on,off",
							"Access" => "test, intern, partner, gast",
							"Aktiv" => true,
						),	
					"SORA" => Array(
							"Text" => "Sora Zone",
							"IconOn" => "button_sora_on.png",
							"IconOff" => "button_sora_off.png",
							"Default" => "off",
							"Value" => false,
							"Options" => "on,off",
							"Access" => "test",
							"Aktiv" => true,
						),	
					"WF" => Array(
							"Text" => "Wasserflächen",
							"IconOn" => "button_wf_on.png",
							"IconOff" => "button_wf_off.png",
							"Default" => "off",
							"Value" => false,
							"Options" => "on,off",
							"Access" => "test",
							"Aktiv" => true,
						),	
//					"Zoom" => Array(
//							"Text" => "Zoom",
//							"Icon" => "",
//							"Value" => false,
//							"Options" => "YES,NO",
//							"Aktiv" => true,
//						),	
					),
				);

$RegionSettings["NO"] = array("Reg" => "leer",
						  "JGList" => "",
						  "RegUp" => "",
						  "Dir" => "", 
						  "Text" => "Leere Karte",
						  "NumEntries" => 0,
						  "MaxID" => 0,
						  "Aktiv" => true);


// ==================
// MAP Kartenansicht
// ==================

if (!function_exists('rkrk_map_parse_ffilter_entries')) {
	/** FFilter-Liste aus URL (Leerzeichen, +, Komma, Semikolon). */
	function rkrk_map_parse_ffilter_entries($ffilter) {
		$ffilter = trim((string)$ffilter);
		if ($ffilter === '') {
			return array();
		}
		$ffilter = str_replace(array(',', ';', '+', "\t", "\n", "\r"), ' ', $ffilter);
		$parts = preg_split('/\s+/', $ffilter, -1, PREG_SPLIT_NO_EMPTY);
		if (!is_array($parts)) {
			return array();
		}
		return array_values(array_filter(array_map('trim', $parts), function ($v) {
			return $v !== '';
		}));
	}
}

function PageButton ($ThisPage, $Reg, $EX, $FFilter) {
	global $picdir, $IconLoc, $ButtonHeight,$RegionSettings;
	$output = "";
	//$ButtonHeight= 40;
	//if ($EX=='NO') {
	//$output .= $Reg . "  ";
	$groupArea = (isset($RegionSettings[$Reg]) && is_array($RegionSettings[$Reg])) ? (string)($RegionSettings[$Reg]["GroupAreaName"] ?? '') : '';
	if ($groupArea !== '') {
		$output .= CreateSiteLink ("UAVFeldkatalog", "/".$groupArea, false, false);
	}
	$output .= CreateSiteLink ("Feldanmeldung", "");
	$output .= CreateSiteLink ("FeldListe", "Reg=" . $Reg);
	$output .= CreateSiteLink ("EditFeldliste", "Reg=" . $Reg);
	$output .= CreateSiteLink ("NeuesFeld", "NeuesFeld=N");
	//if ((($Reg!="ALL") and ($Reg!="all")) or $FFilter!="") and ($Reg!="ZG") and ($Reg!="Amt") and (count(explode(" ", trim($Reg)))==1)) {
	if (((($Reg!="ALL") and ($Reg!="all")) or $FFilter!="") and ($Reg!="ZG") and ($Reg!="Amt") and (count(explode(" ", trim($Reg)))==1)) {
		$output .= CreateSiteLink ("PdfFelder", "Reg=" . $Reg ."&FFilter=" . $FFilter);
	} else {
		//wenn mehr als eine Region gewählt wird, kann kein PDF erstellt werden.
		$output .= "<img src='" . $IconLoc . "/" . "pdf_button_disbled.png' height='" . $ButtonHeight . "px' title='PDF nur für einzelne Regionen verfügbar'>";
	}
	$output .= CreateSiteLink ("EditFeld", "");
	$output .= CreateSiteLink ("Login", "");
	$output .= CreateSiteLink ("Home", "");
	return $output;
}

// Neues Kartenmenu (ersetzt Funktion KartenMenu)
function KartenMenu2023 ($ThisPage, $Regions, $MapType, $MapOptions, $Zoom, $MissionSetting, $OtherParameters) {
	global $IconLoc, $ButtonHeight, $MapSettings, $UseSecUrl;
	//echo "Graphical GUI" ."<br>";
	
	//echo "Regions Selected: " . $Regions . "<br>";
	$output_MO ="";
	if (strpos((string)$OtherParameters, "MenuOn=") === false) {
		$OtherParameters .= "&MenuOn=on";
	}

	// ==============================
	// Map Options
	// ==============================
	
	
	foreach ($MapOptions as $key => $MapOpt) {
		$std_parameter="";
		$parameter= "&KA=" . $MapType;
		foreach ($MapOptions as $keyPar => $MapParaOpt) {
			if ($key== $keyPar) {
				// switch parameter
				if ($MapParaOpt["Value"]=="on") 
					$parameter .= "&" . $keyPar ."=off";
				else
					$parameter .= "&" . $keyPar ."=on";
			} else {
				// other parameters
				if ($MapParaOpt["Value"]=="on") 
					$parameter .= "&" . $keyPar ."=on";
				else
					$parameter .= "&" . $keyPar ."=off";
			}
			// standard parameters
			if ($MapParaOpt["Value"]=="on") 
				$std_parameter .= "&" . $keyPar ."=on";
			else
				$std_parameter .= "&" . $keyPar ."=off";
		}
		if ($MapOpt["Value"]=="on") {
			//$output_MO .= "<img src='" . $IconLoc . "/" . $MapOpt["IconOff"] . "' height='" . $ButtonHeight . "px' title='" . $MapOpt["Text"] . "'>";
			$Icon =	$MapSettings["Options"][$key]["IconOff"];
		} else {
			//$output_MO .= "<img src='" . $IconLoc . "/" . $MapOpt["IconOn"] . "' height='" . $ButtonHeight . "px' title='" . $MapOpt["Text"] . "'>";
			$Icon =	$MapSettings["Options"][$key]["IconOn"];
		}
		if ($UseSecUrl) {
			//encripted URL
			$output_MO .= "<a href='" . $ThisPage . "?" . base64_encode("Reg=" . $Regions . $parameter . $OtherParameters . "&Zoom=" . $Zoom . $MissionSetting) ."'><img src='" . $IconLoc . "/" . $Icon . "' height='" . $ButtonHeight . "px' title='". $MapOpt["Text"] . "'></a>";
		} else {
			//cleartext URL
			$output_MO .= "<a href='" . $ThisPage . "?". "Reg=" . $Regions . $parameter . $OtherParameters . "&Zoom=" . $Zoom . $MissionSetting ."'><img src='" . $IconLoc . "/" . $Icon . "' height='" . $ButtonHeight . "px' title='". $MapOpt["Text"] . "'></a>";
		}
		//$output_MO .= "Parameter " . $parameter . "<br>";
	}

	
	// ==============================
	// Map Type
	// ==============================
	$output_KO ="";
	//echo "MapType: " . $MapType . "<br>";
	foreach ($MapSettings["Types"] as $key => $MapOpt) {
		if (($MapOpt["Aktiv"]) and CheckAccess ($MapOpt["Access"])) {
			if ($MapType==$key) {
				if ($UseSecUrl) {
					//encripted URL
					$output_KO .=" <a href='" . $ThisPage . "?". base64_encode("Reg=" . $Regions . "&KA=" . $key . $std_parameter . $OtherParameters. $DebugParameter ."&Zoom=" . $Zoom) . "'><img src='" . $IconLoc . "/". $MapSettings["Types"][$key]["IconOff"] . "' height='" . $ButtonHeight . "px' title='" . $MapSettings["Types"][$key]["IconOn"] . "'></a>";
				} else {
					//cleartext URL
					$output_KO .=" <a href='" . $ThisPage . "?" . "Reg=" . $Regions . "&KA=" . $key . $std_parameter . $OtherParameters. $DebugParameter ."&Zoom=" . $Zoom . "'><img src='" . $IconLoc . "/". $MapSettings["Types"][$key]["IconOff"] . "' height='" . $ButtonHeight . "px' title='" . $MapSettings["Types"][$key]["IconOn"] . "'></a>";
				}
			} else {
				if ($UseSecUrl) {
					//encripted URL
					$output_KO .=" <a href='" . $ThisPage . "?" . base64_encode("Reg=" . $Regions . "&KA=" . $key . $std_parameter . $OtherParameters. $DebugParameter ."&Zoom=" . $Zoom) . "'><img src='" . $IconLoc . "/". $MapSettings["Types"][$key]["IconOn"]."' height='" . $ButtonHeight . "px' title='". $MapSettings["Types"][$key]["IconOn"]. "'></a>";
				} else {
					//cleartext URL
					$output_KO .=" <a href='" . $ThisPage . "?" . "Reg=" . $Regions . "&KA=" . $key . $std_parameter . $OtherParameters. $DebugParameter ."&Zoom=" . $Zoom . "'><img src='" . $IconLoc . "/". $MapSettings["Types"][$key]["IconOn"]."' height='" . $ButtonHeight . "px' title='". $MapSettings["Types"][$key]["IconOn"]. "'></a>";
				}
			}
		}
	}

	// ==============================
	// Zoom
	// ==============================
	$output_ZO= "";
	//if (GetAccess ("PilotPartners")) { 
		$ZoomUp = $Zoom + 0.2;
		$ZoomDown = $Zoom - 0.2;
		if ($ZoomUp>2) {
			$ZoomUp=2;
		}
		if ($ZoomDown<0.4) {
			$ZoomDown=0.4;
		}
		if ($UseSecUrl) {
			//encripted URL
			$output_ZO .= " <a href='" . $ThisPage . "?" . base64_encode("Reg=" . $Regions . "&KA=" . $MapType . $std_parameter . $OtherParameters . "&Zoom=" . $ZoomUp . $DebugParameter) ."'><img src='" . $IconLoc . "/plus.png' height='" . $ButtonHeight . "px' title='vergrössern'></a> ";
			$output_ZO .= " <a href='" . $ThisPage . "?" . base64_encode("Reg=" . $Regions . "&KA=" . $MapType . $std_parameter . $OtherParameters . "&Zoom=" . $ZoomDown . $DebugParameter) ."'><img src='" . $IconLoc . "/minus.png' height='" . $ButtonHeight . "px' title='verkleinern'></a> ";
		} else {
			//cleartext URL
			$output_ZO .= " <a href='" . $ThisPage . "?" . "Reg=" . $Regions . "&KA=" . $MapType . $std_parameter . $OtherParameters . "&Zoom=" . $ZoomUp . $DebugParameter ."'><img src='" . $IconLoc . "/plus.png' height='" . $ButtonHeight . "px' title='vergrössern'></a> ";
			$output_ZO .= " <a href='" . $ThisPage . "?" . "Reg=" . $Regions . "&KA=" . $MapType . $std_parameter . $OtherParameters . "&Zoom=" . $ZoomDown . $DebugParameter ."'><img src='" . $IconLoc . "/minus.png' height='" . $ButtonHeight . "px' title='verkleinern'></a> ";
		}	
		$output_ZO .=  "<span id='qrswop1'>swop</span>";
	//}
		
	// ==============================
	// DEBUGSWITCH
	// ==============================
	//$output .= "DBW: " . $WebFreigabe;

	$output_DB="";
	$output_DB .= MissionLibraryButtons("&" . "Reg=" . $Regions . "&KA=" . $MapType . $std_parameter . $OtherParameters . "&Zoom=" . $Zoom, false);
	$output= $output_KO . " " . $output_MO. " " .$output_ZO . " " .$output_DB;

	//pre_R($MapOptions);
	return $output;
}


function KartenSettings($MapType, &$KaType, &$KaLayer, &$KaVisLayer, &$KaOpacity) {
	global $MapOptions, $MapSettings;
	$KaType=$MapSettings["Types"][$MapType]["Type"];
	//pre_R($MapSettings["Types"]);
	$geLaOn = !empty($MapOptions["GeLa"]["Value"]);
	if ($geLaOn)
		$MType = "advanced";
	else
		$MType = "simple";

	// New MAP.GEO.ADMIN from 25. June 2024
	$typeLayer = $MapSettings["Types"][$MapType][$MType]["Layer"] ?? '';
	if ($typeLayer !== '')
		$KaLayer= $MapSettings["Types"][$MapType][$MType]["Layer24"] ?? '';
	else
		$KaLayer= $MapSettings["MapLayer"][$MType]["Layer24"] ?? '';

	$KaVisLayer= "";
	$KaOpacity= "";
		
	//echo "Map Type: " . $MapType . "/" . $MType . "<br>";
	//echo "Map KaLayer" . $KaLayer . "<br>";
	//echo "Map KaVisLayer" . $KaVisLayer . "<br>";
	//echo "Map KaOpacity " . $KaOpacity . "<br>";
}


?>
<script> 
<?php 
echo "var iconloc = '". $IconLoc . "';\n";
echo "var buttonsize = ". 22 . ";\n";

 ?>
function Swop(mybloc1, mybloc2, mybutton, mytext1, mytext2) {
	var b1 = document.getElementById(mybloc1);
	var b2 = document.getElementById(mybloc2);
	var b = document.getElementById(mybutton);
	if (b1.style.display === "none") {
		b1.style.display = "block";
		b2.style.display = "none";
		b.innerHTML =  mytext1;
		
	} else {
		b1.style.display = "none";
		b2.style.display = "block";
		b.innerHTML = mytext2;
	}
}

function ShowHideText(mybloc1, mybloc2) {
	var b1 = document.getElementById(mybloc1);
	var b2 = document.getElementById(mybloc2);
	if (b2.style.display === "none") {
		b1.style.display = "block";
		b2.style.display = "none";
		
	} else {
		b1.style.display = "none";
		b2.style.display = "block";
	}
}

function SwopOnImage(mybloc1, mybloc2, myimage, myimage1, myimage2) {
	var b1 = document.getElementById(mybloc1);
	var b2 = document.getElementById(mybloc2);
	var i = document.getElementById(myimage);
	if (b1.style.display === "none") {
		b1.style.display = "block";
		b2.style.display = "none";
		//i.innerHTML = "b1";
		i.src = myimage1;
		
	} else {
		b1.style.display = "none";
		b2.style.display = "block";
		//i.innerHTML = "b2";
		i.src = myimage2;
	}
}
</script>
