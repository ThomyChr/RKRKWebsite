<?php
	session_start();
	include_once('scripts/rkrk_gen.php');
	include_once('scripts/rkrk_fileaccess.php');
	include_once('scripts/rkrk_functions.php');
	include_once('scripts/rkrk_graphic_map_button.php');
	include_once('scripts/rkrk_googlechart.php'); 
	include_once('scripts/rkrk_menu.php'); 

	//// LAYER SETUP ///  
	$Layer= true;
	?>
	<style>
	#myDIV.rkrk-jg-map {
	  width:100%;
	  max-width:900px;
	  position:relative;
	  height:auto;
	  overflow:visible;
	}
	#myDIV.rkrk-jg-map-tiles > div {
	  position:relative;
	}
	#myDIV.rkrk-jg-map-tiles > div > div {
	  opacity:1!important;
	  position:absolute;
	  border-radius: 2%;
	}
	#myDIV.rkrk-jg-map-overview > div {
	  position:relative;
	}
	#myDIV.rkrk-jg-map-overview img {
	  display:block;
	  max-width:100%;
	  height:auto;
	}
	.rkrk-feldkatalog-head {
	  display:flex;
	  align-items:center;
	  gap:10px;
	  flex-wrap:wrap;
	  margin:12px 0 4px;
	}
	.rkrk-feldkatalog-head h2 {
	  margin:0;
	}
	.rkrk-feldkatalog-mdb {
	  display:inline-flex;
	  align-items:center;
	}
	.rkrk-feldkatalog-mdb img {
	  height:32px;
	  width:auto;
	  vertical-align:middle;
	}
	.rkrk-feldkatalog-views {
	  display:inline-flex;
	  flex-direction:row;
	  align-items:center;
	  flex-wrap:wrap;
	  gap:6px;
	  margin:0;
	  font-size:13px;
	}
	.rkrk-feldkatalog-view {
	  display:inline-flex;
	  align-items:center;
	  gap:6px;
	  min-height:28px;
	  padding:3px 10px;
	  text-decoration:none;
	}
	.rkrk-feldkatalog-view img {
	  width:16px;
	  height:16px;
	  object-fit:contain;
	  opacity:0.45;
	  flex:0 0 auto;
	}
	.rkrk-feldkatalog-view.is-active {
	  border-color:#4d6b3e;
	  color:#3f5a32;
	  font-weight:700;
	  pointer-events:none;
	}
	.rkrk-feldkatalog-view.is-active img {
	  opacity:1;
	}
	.rkrk-jg-pick {
	  position:absolute;
	  z-index:4;
	  width:32px;
	  height:32px;
	  object-fit:contain;
	  pointer-events:none;
	  filter: drop-shadow(0 1px 2px rgba(0,0,0,.35));
	}
	</style>
	<?php

	include_once('rkrk_access_control.php'); 

	//check impropriate IP addresses accessing page
	CheckIPandExit();
	headerpage("Drohnenrehkitzrettung.ch", false);
?>

<body>
<?php
//////////////////////////////
// Definitions
//////////////////////////////

$ShowButton = false;


//////////////////////////////
// Functions
//////////////////////////////

	GrantAccess();
	CreateTopMenu();
	PrintRKRKHeader("Drohnenrekitzrettung.ch");
	
	//Write to LOGfile
	WriteToLog("get", basename($_SERVER['PHP_SELF']), "öffnen ","","","");

	if (!$LoggedOn) {
		$loginSelf = htmlspecialchars($_SERVER['PHP_SELF'] ?? 'rkrk.php', ENT_QUOTES, 'UTF-8');
		echo "<div class='darkgreen'>". chr(10);
		echo "<br><b>Für weitere Informationen musst du dich einloggen.</b><br><br>Falls du noch nicht registriert bist, bitte zuerst registrieren - danach kannst du dich einloggen.<br><br>";
		echo '<p style="margin:12px 0;"><a class="btn" href="' . $loginSelf . '?page_action=login" title="Login-Formular; Registrieren über den Button im Formular">Zum Login / Registrieren</a></p>';
		echo "</div>";
	}	

	if (($EnableAccessControl) and ($LoggedOn)) {
		echo "<font size='2px'>Willkommen! " . $_SESSION[$SessNme_RegisteredUser]. " " .$_SESSION[$SessNme_RegisteredInitialen]. "</font>";
		
	} else {
		echo "<font size='2px'>" . GetNameUser() . "</font>";
	}
	echo "<br>";
	if ($LoggedOn) {
		if ($Layout!=2023) {
			echo MissionLibraryButtons("", false);
			echo CreateSiteLink ("NeuesFeld", "NeuesFeld=N");
			echo CreateSiteLink ("FeldListe", "");
			echo CreateSiteLink ("EditFeld", "");
			echo CreateSiteLink ("EditFeldliste", "");
			echo CreateSiteLink ("Adressen", "");
			echo CreateSiteLink ("AdresseBauern", "");
			echo CreateSiteLink ("Planning", "");
			echo CreateSiteLink ("Fluege", "");
			echo CreateSiteLink ("Flugliste", "");
			echo CreateSiteLink ("EditFiles", "");
			echo CreateSiteLink ("FlugStats", "");
			echo CreateSiteLink ("Partners", "");
			//echo CreateSiteLink ("QRcodes", "");
			echo CreateSiteLink ("Info", "");
			if ($EnableAccessControl) echo CreateSiteLink ("Logout", "");
		}
		$weatherYear = intval(date("Y"));
		$weatherData = RefreshWeatherForecastCsv(14, 47.2190, 8.4560, 'Europe/Zurich', $weatherYear);
		echo GetWeather("16", 14, $weatherData);
		if (isset($_GET['wxdebug']) && $_GET['wxdebug'] == '1') {
			global $rkrk_weather_refresh_debug;
			$wd = is_array($rkrk_weather_refresh_debug ?? null) ? $rkrk_weather_refresh_debug : [];
			$wPath = (string)($wd['csv_path'] ?? '');
			$wWrite = !empty($wd['write_ok']) ? 'ok' : 'fail';
			$wApi = !empty($wd['api_ok']) ? 'ok' : 'fail';
			$wHttp = isset($wd['http_code']) ? (string)$wd['http_code'] : 'n/a';
			$wErr = (string)($wd['http_error'] ?? '');
			$wRows = intval($wd['rows_before'] ?? 0) . " -> " . intval($wd['rows_after'] ?? 0);
			echo "<div style='font-size:11px;color:#666;margin:4px 0 10px 0;'>WX DEBUG | api: {$wApi} ({$wHttp}) | write: {$wWrite} | rows: {$wRows}<br>path: ".htmlspecialchars($wPath, ENT_QUOTES, 'UTF-8')."<br>err: ".htmlspecialchars($wErr, ENT_QUOTES, 'UTF-8')."</div>";
		}
		echo '<div class="rkrk-feldkatalog-head">' .chr(10);
		echo '<h2>Feldkatalog</h2>' .chr(10);
		if ($Layout == 2023 || $Layout == "2023") {
			echo '<span class="rkrk-feldkatalog-mdb">' . MissionLibraryButtons("", false) . '</span>' .chr(10);
		}
		global $MapView, $IconLoc;
		$mapViewNow = (($MapView ?? "2023") == "2022") ? "2022" : "2023";
		$mapClass = ($mapViewNow == "2022") ? "rkrk-jg-map rkrk-jg-map-overview" : "rkrk-jg-map rkrk-jg-map-tiles";
		$icoTiles = htmlspecialchars((string)$IconLoc, ENT_QUOTES, 'UTF-8') . '/JGsel_21_off.png';
		$icoMap = htmlspecialchars((string)$IconLoc, ENT_QUOTES, 'UTF-8') . '/geomap_small.png';
		echo '<span class="rkrk-feldkatalog-views rkrk-sels">' .chr(10);
		$tileCls = 'rkrk-sel rkrk-reg-pill rkrk-feldkatalog-view' . ($mapViewNow == "2023" ? ' is-active' : '');
		$mapCls = 'rkrk-sel rkrk-reg-pill rkrk-feldkatalog-view' . ($mapViewNow == "2022" ? ' is-active' : '');
		echo '<a class="' . $tileCls . '" href="rkrk.php?MapView=2023" title="Kachelansicht"><img src="' . $icoTiles . '" alt="">Kachelansicht</a>';
		echo '<a class="' . $mapCls . '" href="rkrk.php?MapView=2022" title="Kartenübersicht"><img src="' . $icoMap . '" alt="">Kartenübersicht</a>';
		echo '</span>' .chr(10);
		echo '</div>' .chr(10);
		echo "<i>Drücke auf die Region deiner Wahl:</i><br>".chr(10);
		if ($Layer) {
			echo "<div id='myDIV' class='" . $mapClass . "'>" .chr(10);
			echo "<div style='top:0px;left:0px;z-index:0;'>";
		}
			echo JGMapButtons(900);
		if ($Layer) {
			echo "</div></div>" .chr(10);
		}

		if ($ShowButton) {
			echo '<table class="big">' .chr(10);
			echo '<th>Region</th><th colspan=2>Flugkatalog</th><th colspan=2>Flugfeldkarten</th>' .chr(10);

			if (GetAccess("AllPilots")){
				echo '<tr>' .chr(10);
				echo '<td>' .chr(10);
				echo '</td>' .chr(10);
				echo '<td>' .chr(10);
				echo 'intern' .chr(10);
				echo '</td>' .chr(10);
				echo '<td>' .chr(10);
				echo 'public' .chr(10);
				echo '</td>' .chr(10);
				echo '<td>' .chr(10);
				echo 'intern' .chr(10);
				echo '</td>' .chr(10);
				echo '<td>' .chr(10);
				echo 'public' .chr(10);
				echo '</td>' .chr(10);
				echo '</tr>' .chr(10);
				echo '<tr>' .chr(10);
				echo '<td>' .chr(10);
				echo 'Alle Felder</td><td>' .chr(10);
				$Regionstext = "Alle Felder";
				if (GetAccess("AllPilots")){
					echo '<a href="rkrk_felder.php?Reg=ALL&Typ=intern"><img src="' . $IconLoc . '/intern.png" height="' . $ButtonHeight .'" title="Intern ' . $Regionstext .'"></a>' .chr(10);
					echo '</td>' .chr(10);
					echo '<td>' .chr(10);
					echo '<a href="rkrk_felder.php?Reg=ALL"><img src="' . $IconLoc . '/public.png" height="' . $ButtonHeight .'" title="Public ' . $Regionstext .'"></a>' .chr(10);
				} else {
					echo '<a href="rkrk_felder.php?Reg=ALL"><img src="' . $IconLoc . '/public.png" height="' . $ButtonHeight .'" title="' . $Regionstext .'"></a>' .chr(10);
					echo '</td>' .chr(10);
					echo '<td>' .chr(10);
				}
				echo '</td><td>'. chr(10);
				if (GetAccess("InternPilots")){
					echo '<a href="rkrk_map.php?Reg=ALL'.$MapIntern.'"><img src="' . $IconLoc . '/geomap.png" height="' . $ButtonHeight .'" title="Intern ' . $Regionstext .'" ></a>' .chr(10);
					echo '</td>' .chr(10);
					echo '<td>' .chr(10);
					echo '<a href="rkrk_map.php?Reg=ALL'.$MapExtern.'"><img src="' . $IconLoc . '/geomap.png" height="' . $ButtonHeight .'" title="Public ' . $Regionstext .'"></a></td>' .chr(10);
				} else {
					echo '<a href="rkrk_map.php?Reg=ALL'.$MapExtern.'"><img src="' . $IconLoc . '/geomap.png" height="' . $ButtonHeight .'" title="' . $Regionstext .'"></a></td>' .chr(10);
					echo '</td>' .chr(10);
					echo '<td>' .chr(10);
				}
				echo '</tr>' .chr(10);
			}
			if ($Layer) {
					echo "</div>";
					echo "</div>";
			}

			
			foreach ($RegionSettings as $Region) {
				if ($Region["Aktiv"]) {
					echo '<tr>' .chr(10);
					echo '<td>' .chr(10);
					echo $Region["Text"] . '</td><td>' .chr(10);
					$Regionstext = $Region["Text"];
					if (GetAccess("AllPilots")){
						echo '<a href="rkrk_felder.php?Reg=' . $Region["Reg"] . '&Typ=intern"><img src="' . $IconLoc . '/intern.png" height="' . $ButtonHeight .'" title="Intern ' . $Regionstext .'"></a>' .chr(10);
						echo '</td>' .chr(10);
						echo '<td>' .chr(10);
						echo '<a href="rkrk_felder.php?Reg=' . $Region["Reg"] . '"><img src="' . $IconLoc . '/public.png" height="' . $ButtonHeight .'" title="Public ' . $Regionstext .'"></a>' .chr(10);
					} else {
						echo '<a href="rkrk_felder.php?Reg=' . $Region["Reg"] . '"><img src="' . $IconLoc . '/public.png" height="' . $ButtonHeight .'" title="' . $Regionstext .'"></a>' .chr(10);
						echo '</td>' .chr(10);
						echo '<td>' .chr(10);
					}
					echo '</td><td>'. chr(10);
					if (GetAccess("AllPilots")){
						echo '<a href="rkrk_map.php?Reg=' . $Region["Reg"] . '&Strt=on&Cntr=off&Area=on&Pfad=on&EX=NO&Poi=on&FZ=on&KA=S&MAP=1&Zoom=0.8"><img src="' . $IconLoc . '/geomap.png" height="' . $ButtonHeight .'" title="Intern ' . $Regionstext .'"></a>' .chr(10);
						echo '</td>' .chr(10);
						echo '<td>' .chr(10);
						echo '<a href="rkrk_map.php?Reg=' . $Region["Reg"] . '&Strt=off&Cntr=on&Area=on&Pfad=off&Poi=off&FZ=on&KA=S&MAP=1&Zoom=0.8"><img src="' . $IconLoc . '/geomap.png" height="' . $ButtonHeight .'" title="Public ' . $Regionstext .'"></a></td>' .chr(10);
					} else {
						echo '<a href="rkrk_map.php?Reg=' . $Region["Reg"] . '&Strt=off&Cntr=on&Area=on&Pfad=off&Poi=off&FZ=on&KA=S&MAP=1&Zoom=0.8"><img src="' . $IconLoc . '/geomap.png" height="' . $ButtonHeight .'" title="' . $Regionstext .'"></a></td>' .chr(10);
						echo '</td>' .chr(10);
						echo '<td>' .chr(10);
					}
					echo '</tr>' .chr(10);
				}
			}
			echo '</table>' .chr(10);
		}



		echo "<br><br>". chr(10);
		echo "<table>". chr(10);
		echo "<tr><td>". chr(10);

		if ($Layout!=2023) {
			echo CreateSiteLink ("Users", "");
			echo CreateSiteLink ("PilotVer", "");
			echo CreateSiteLink ("GeoDataHealth", "");
			echo CreateSiteLink ("JsonHealth", "");
			echo CreateSiteLink ("Changes", "");
			echo CreateSiteLink ("SiteStatistics", "ALL");
			echo CreateSiteLink ("Recovery", "");
			echo CreateSiteLink ("Logfiles", "");
			echo CreateSiteLink ("CheckFilesName", "");
			echo CreateSiteLink ("CompareRRKRZG", "");
			echo CreateSiteLink ("AllFields", "");
			echo CreateSiteLink ("Quiz", "");
		}
		// ==============================
		// HILFS LINKS
		// ==============================

		if (GetAccess("AllPilots")){
			if (GetAccess("InternPilots")){
			
				if (GetAccess("TestOnly")){
					echo '<h2>In Arbeit</h2>' .chr(10);
					echo '<ul>' .chr(10);
					//echo '<li><a href="rkrk_quiz.php">Quiz</a></li>' .chr(10);
					//echo '<br>';
					echo '<li><a href="rkrk_pictures.php">Test Bildspeicherung</a></li>' .chr(10);
					echo '<li><a href="2023_rkrk_adrlwbtr.php">Adressen Bauern (2023)</a></li>' .chr(10);
					echo '<li><a href="2024_rkrk_fluege.php">Flüge (2023)</a></li>' .chr(10);
					echo '<li><a href="test_flex_layout.php">FlexLayout</a></li>' .chr(10);
					echo '<li><a href="test_flex_template.php">FlexTemplate</a></li>' .chr(10);
					echo '</ul>' .chr(10);			echo '</ul>' .chr(10);
				}
			}
			echo '<h2>Hilfslinks</h2>' .chr(10);
			echo '<ul>' .chr(10);
			echo '<li><a href="rkrk_calc.php?RKRK">Flugberechnung</a></li>' .chr(10);
			echo '<li><a href="rkrk_map.php?Reg=leer&KA=F&Strt=on&Area=on&&Label=on&EG=on&WF=off&EX=no&MAP=&FFilter=&Zoom=1.0&MenuOn=off&GA=gast&Color=white&MapButtons=off">Unsere Einsatzgebiete</a></li>' .chr(10);
			if (GetAccess("TestOnly")){
				echo '<li><a href="rkrk_conversion.php?TEST">Feldkonvertierungswerkzeug (Test)</a></li>' .chr(10);
				echo '<li><a href="rkrk_calc.php?TEST">Flugberechnung (Test)</a></li>' .chr(10);
				echo '<li><a href="rkrk_xml2kml.php?Reg=26&File=26_Felder&Deb=off">generiere und speichere KML file für Region 26 (ohne Debug outputs)</a></li>' .chr(10);
				echo '<li><a href="rkrk_dokml.php?Reg=ALL&Strt=On&Cnrt=On&Area=On&Pfad=On&File=ALL_kml&Poi=Off&WebAccess='.$Webaccess.'">generiere KML Daten für alle Regionen (ohne Debug outputs)</a></li>' .chr(10);
				echo '<li><a href="rkrk_dokml.php?Reg=21&Strt=On&Cnrt=On&Area=On&Pfad=On&File=21_kml&Poi=Off&WebAccess='.$Webaccess.'">generiere KML Daten für Region 21 (ohne Debug outputs)</a></li>' .chr(10);
				echo '<li><a href="rkrk_jg_summary.php?Reg=26&Color=white">Externe Zusammenfassungsseite (z.B. JG 26)</a></li>' .chr(10);
				echo '<li><a href="rkrk_catalog_health.php">Feldkatalog Health (Überarbeitung der Felddaten)</a></li>' .chr(10);
				echo '<li><a href="test_conv_kml2svg.php">Test converting KML files to SVG/JPG/PNG pictures</a></li>' .chr(10);	
				echo '<li><a href="test_checklist.php">Test einer online Checkliste für Drohnenpiloten</a></li>' .chr(10);	
				//echo '<li><a href="rkrk_pv.php?P=tc-mxmi&Color=white">Personen Verifikation (Thomas Christen)</a>' .chr(10);
				echo '<li>Personen Verifikation:' .chr(10);
				foreach ($OptionPilot as $key => $OptPilot) {
					if ($OptPilot["PV"]<>"") {
						foreach (explode(",", $OptPilot["PV"]) as $PVPilot) {
							//echo  '<a href="rkrk_pv.php?P='. $PVPilot .'&Color=white">'.$OptPilot["Opt"].'</a> ';
							echo  '<a href="https://www.drohnenrehkitzrettung.ch/pv/verify?P='. $PVPilot . '">'.$OptPilot["Opt"].'</a> ';
						}	
					}	
				}	
				echo ' (<a href="rkrk_pv.php?P=tc-mxmi&Color=white">lokal ch</a>, <a href="rkrk_pv.php?P=tchr-hwsr&Color=white">lokal de</a>)' .chr(10);
				//echo ' - <a href="https://www.drohnenrehkitzrettung.ch/pv/verify?P=tc-mxmi"><i>flex auf DRKRK</i></a></li>' .chr(10);
				echo '<li><a href="https://www.drohnenrehkitzrettung.ch/flugkatalog1/fg?JG=ZG1%20ZG2%20ZG3&JGN=ZG%20Zug">Feldkatalog (auf drohnenrehkitzrettung.ch) - <i>flex</i></a></li>' .chr(10);
			
			
				echo '<li><a href="test_jg_summary.php">Test externe Zusammenfassungsseite (z.B. JG 26)</a></li>' .chr(10);
				echo '<li><a href="rkrk_felderliste.php?'. base64_encode("Reg=26,27") . '">Test Verschlüsselung Feldliste</a></li>' .chr(10);
			}
			//2023-08-29 Ausgeschaltet, wenn keine Überarbeitungen nötig sind am Feldkatalog 
			//echo '<li><a href="dev_maintain_feldkatalog.php">DEV: Überarbeitung des Feldkatalogs</a></li>' .chr(10);
			echo '</ul>' .chr(10);
			
			
		}

		echo "</td></tr>". chr(10);
		echo "</table>". chr(10);
	}
/*
	echo '<div id="Chart" style="width:100%; max-width:500px; height:400px;"></div>';
	echo createGoogleTable("Chart");
*/

echo "<h1>Wie bestelle ich die Rehkitzrettung?</h1>" . chr(10);
echo "  <ul>" . chr(10);
echo "    <li><a href='https://www.drohnenrehkitzrettung.ch/die-rehkitzrettung/rehkitzrettung-amt-zh' >Jagdgebiet Amt/ZH (Knonaueramt)</a></li>" . chr(10);
echo "    <li><a href='https://www.drohnenrehkitzrettung.ch/die-rehkitzrettung/rehkitzrettung-zug' >Kanton Zug</a></li>" . chr(10);
echo "    <li><a href='https://www.drohnenrehkitzrettung.ch/die-rehkitzrettung/rehkitzrettung-oberfreiamt-ag' >Oberfreiamt, Kt. Aargau</a></li>" . chr(10);
echo "    <li><a href='https://rkrs.uaveditor.com/feldanmeldung' >Andere Regionen</a></li>" . chr(10);
echo "  </ul>" . chr(10);

echo "<h1>Piloten Zuweisung zu Jagdgebieten für das Jagdgebiet Amt/ZH</h1>" . chr(10);
echo "<h2>... nach Jagdgebiet</h2>" . chr(10);
if ($Layout==2023) {
	$FrameHeight = 1100;
} else {
	$FrameHeight = 550;
}
echo "<p class='rkrk-iframe-scroll'><iframe width='100%' height='".$FrameHeight."' style='width:100%;max-width:1400px;border:0;' seamless frameborder='0' scrolling='auto' src='rkrk_adrlist.php?Table=JGListe'></iframe></p>" . chr(10);


echo "<h2>... nach Piloten</h2>" . chr(10);
if ($Layout==2023) {
	$FrameHeight = 600;
} else {
	$FrameHeight = 400;
}

echo "<p class='rkrk-iframe-scroll'><iframe width='100%' height='".$FrameHeight."' style='width:100%;max-width:900px;border:0;' seamless frameborder='0' scrolling='auto' src='rkrk_adrlist.php?Table=PilotListAmt'></iframe></p>" . chr(10);
if ($LoggedOn) {

	echo "<H2>Flugstatisik ".date("Y")."<H2>". chr(10);
	echo "<p><iframe width='700' height='2100' seamless frameborder='0' scrolling='no' src='rkrk_flugstatistik.php?ALL&Charts=FelderProRegion,Entwicklung1,Entwicklung2,EinsaetzeProTag&MenuOn=off'></iframe></p>" . chr(10);


	echo "</body>" . chr(10);
	echo "</html>" . chr(10);
}
?> 
