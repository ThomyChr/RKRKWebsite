<?php
	session_start();
	include_once('scripts/rkrk_gen.php');
	include_once('scripts/rkrk_menu.php');
	include_once('scripts/rkrk_csvfiles.php');
	include_once('scripts/rkrk_inputforms.php');
	include_once('scripts/rkrk_felddaten.php');
	include_once('scripts/rkrk_flugflaechen.php');
	include_once('rkrk_access_control.php');

	CheckIPandExit();
	SetMissionLocation();
	$MissionSelectionParameter = "MDB=" . $RKRKMissionSelected;

	if (isset($_GET["SelFrom"])) {
		$FromDate = $_GET["SelFrom"];
	} elseif (isset($_POST["SelFrom"])) {
		$FromDate = $_POST["SelFrom"];
	} else {
		$FromDate = GetSasionStartDate(date('Y'));
	}

	if (isset($_GET["SelTo"])) {
		$ToDate = $_GET["SelTo"];
	} elseif (isset($_POST["SelTo"])) {
		$ToDate = $_POST["SelTo"];
	} else {
		$ToDate = GetSasionEndDate(date('Y'));
	}

	$Today = date("Y-m-d");
	$MinDate = "2021-01-01";
	$MaxDate = date('Y-m-d', strtotime('+1 year'));

	$Export = isset($_GET["Export"]) ? strtolower(trim((string)$_GET["Export"])) : '';
	if (isset($_POST["Export"])) {
		$Export = strtolower(trim((string)$_POST["Export"]));
	}

	$userHint = '';
	if (!empty($_SESSION[$SessNme_RegisteredInitialen])) {
		$userHint = (string)$_SESSION[$SessNme_RegisteredInitialen];
	}

	$allFlights = rkrk_flugflaechen_load_flights($FromDate, $ToDate);
	$filtered = rkrk_flugflaechen_filter_geflogen($allFlights, $FromDate, $ToDate, array());
	$stats = rkrk_flugflaechen_collect_stats($filtered);
	$basename = rkrk_flugflaechen_basename($FromDate, $ToDate, $userHint);

	$Feldkatalog = array();
	$kmlPath = '';
	$kmlText = '';
	$kmlCount = 0;
	$center = array('East' => 2678000, 'North' => 1232000, 'Zoom' => 6);
	if ($stats['fields'] > 0) {
		$Feldkatalog = GetFeldkatalog($xmlMissionLocation . $RKRKFelderfileXML);
		if (!is_array($Feldkatalog)) {
			$Feldkatalog = array();
		}
		$built = rkrk_flugflaechen_build_kml(
			$Feldkatalog,
			$stats['feldIds'],
			'Geflogen ' . $FromDate . ' - ' . $ToDate
		);
		$kmlCount = (int)($built['count'] ?? 0);
		$kmlText = (string)($built['kml'] ?? '');
		if ($kmlCount > 0 && $kmlText !== '') {
			$kmlPath = rkrk_flugflaechen_save_kml($kmlText, $basename);
			if ($kmlPath === '') {
				$SystemInfo = '<b>Hinweis:</b> KML konnte nicht unter kmlfiles/ gespeichert werden (Rechte?). Download/Karte nutzen den Direkt-/FFilter-Weg.';
			}
		}
		$center = rkrk_flugflaechen_center_lv95($Feldkatalog, $stats['feldIds']);
	}

	$selfRaw = $_SERVER["PHP_SELF"];
	$self = htmlspecialchars($selfRaw);
	$baseQs = $MissionSelectionParameter . "&SelFrom=" . rawurlencode($FromDate) . "&SelTo=" . rawurlencode($ToDate);
	if (!isset($SystemInfo)) {
		$SystemInfo = '';
	}

	if ($Export === 'kml') {
		if ($kmlText !== '' && $kmlCount > 0) {
			$fname = $basename . '.kml';
			header('Content-Type: application/vnd.google-earth.kml+xml; charset=UTF-8');
			header('Content-Disposition: attachment; filename="' . $fname . '"');
			echo $kmlText;
			exit;
		}
		$SystemInfo = "<b>Kein KML:</b> Im Zeitraum gibt es keine geflogenen Felder mit Flächendaten.";
	}

	if ($Export === 'png') {
		if ($kmlText !== '' && $kmlCount > 0) {
			require_once('scripts/rkrk_kml2svg.php');
			$tmpBase = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $basename;
			@file_put_contents($tmpBase . '.kml', $kmlText);
			$GLOBALS["BBoxEast"] = null;
			$GLOBALS["BBoxNorth"] = null;
			$GLOBALS["BBoxZoom"] = null;
			$data = kml_text_to_svg($kmlText, $tmpBase, 2000, 2000, 28, 200.0);
			$svgPath = $tmpBase . '.svg';
			$pngPath = $tmpBase . '.png';
			$made = '';
			if (!empty($data) && is_file($svgPath)) {
				$made = rkrk_flugflaechen_raster_png_from_svg($svgPath, $pngPath);
			}
			if ($made !== '' && is_file($made)) {
				header('Content-Type: image/png');
				header('Content-Disposition: attachment; filename="' . $basename . '.png"');
				readfile($made);
				@unlink($tmpBase . '.kml');
				@unlink($svgPath);
				@unlink($made);
				exit;
			}
			if (is_file($svgPath)) {
				header('Content-Type: image/svg+xml; charset=UTF-8');
				header('Content-Disposition: attachment; filename="' . $basename . '.svg"');
				readfile($svgPath);
				@unlink($tmpBase . '.kml');
				@unlink($svgPath);
				exit;
			}
			echo '<!DOCTYPE html><html lang="de"><head><meta charset="utf-8"><title>PNG Export</title></head><body>';
			echo '<p>PNG/SVG-Erzeugung fehlgeschlagen. <a href="' . $self . '?' . htmlspecialchars($baseQs) . '">Zurück</a></p>';
			echo '</body></html>';
			exit;
		}
		$SystemInfo = "<b>Kein PNG:</b> Zuerst Zeitraum mit geflogenen Flächen wählen.";
	}

	$mapUrl = '';
	$mapUrlFull = '';
	if (count($stats['feldIds']) > 0) {
		$kmlForMap = ($kmlPath !== '') ? $basename : '';
		$mapQuery = rkrk_flugflaechen_map_query($stats['feldIds'], $RKRKMissionSelected, $center, $kmlForMap);
		$mapUrl = 'rkrk_map.php?' . $mapQuery;
		$mapUrlFull = 'rkrk_map.php?' . preg_replace('/(&)?(MenuOn|Embed)=[^&]*/', '', $mapQuery);
		$mapUrlFull = preg_replace('/\?&/', '?', $mapUrlFull);
		$mapUrlFull .= (strpos($mapUrlFull, '?') === false ? '?' : '&') . 'MenuOn=on';
	}
	$pdfUrl = 'rkrk_pdf_flugflaechen.php?' . $baseQs;
	$kmlUrl = $selfRaw . '?' . $baseQs . '&Export=kml';
	$pngUrl = $selfRaw . '?' . $baseQs . '&Export=png';

	$pagetitle = "Geflogene Flächen";
	headerpage($pagetitle, false);
	GrantAccess();
	CreateTopMenu();
	PrintRKRKHeader($pagetitle);

	echo '<style>
html.rkrk-ff-page, body.rkrk-ff-page {
  height: 100%;
  overflow: hidden;
}
.rkrk-ff-split {
  display: flex;
  gap: 0;
  align-items: stretch;
  width: 100%;
  height: calc(100vh - var(--rkrk-ff-top, 130px));
  max-height: calc(100vh - var(--rkrk-ff-top, 130px));
  margin: 0;
  border: 1px solid #cfd5dc;
  background: #fff;
  overflow: hidden;
  box-sizing: border-box;
}
.rkrk-ff-left {
  width: 38%;
  min-width: 280px;
  max-width: 560px;
  display: flex;
  flex-direction: column;
  min-height: 0;
  height: 100%;
  padding: 0;
  box-sizing: border-box;
  border-right: 1px solid #cfd5dc;
  background: #f7f8fa;
  overflow: hidden;
}
.rkrk-ff-left-top {
  flex: 0 0 auto;
  padding: 10px 12px 8px;
  border-bottom: 1px solid #dde2e8;
  background: #f7f8fa;
}
.rkrk-ff-list {
  flex: 1 1 auto;
  min-height: 0;
  overflow: auto;
  padding: 8px 12px 12px;
  -webkit-overflow-scrolling: touch;
}
.rkrk-ff-list table {
  border-collapse: collapse;
  width: 100%;
  font-size: 12px;
  background: #fff;
}
.rkrk-ff-list th,
.rkrk-ff-list td {
  padding: 3px 5px;
  border: 1px solid #ccd3db;
  white-space: nowrap;
}
.rkrk-ff-list thead th {
  position: sticky;
  top: 0;
  background: #e8edf2;
  z-index: 1;
}
.rkrk-ff-right {
  flex: 1;
  min-width: 0;
  min-height: 0;
  height: 100%;
  display: flex;
  flex-direction: column;
  background: #e8ebef;
  position: relative;
  overflow: hidden;
}
.rkrk-ff-fs-float {
  position: absolute;
  top: 10px;
  right: 10px;
  z-index: 5;
  appearance: none;
  border: 0;
  border-radius: 6px;
  padding: 6px 10px;
  background: rgba(47,62,40,0.88);
  color: #fff;
  cursor: pointer;
  font: inherit;
  font-size: 13px;
}
.rkrk-ff-fs-float:hover {
  background: rgba(111,139,94,0.95);
}
.rkrk-ff-mapwrap {
  flex: 1 1 auto;
  min-height: 0;
  height: 100%;
  width: 100%;
  position: relative;
  overflow: hidden;
}
.rkrk-ff-mapwrap iframe {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  border: 0;
  background: #ddd;
  display: block;
}
.rkrk-ff-actions a {
  display: inline-block;
  margin: 4px 6px 4px 0;
  padding: 5px 10px;
  border-radius: 8px;
  color: #fff;
  text-decoration: none;
  font-size: 13px;
}
.rkrk-ff-empty {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
  color: #556;
  padding: 24px;
  text-align: center;
}
body.rkrk-ff-map-fullscreen {
  overflow: hidden !important;
}
body.rkrk-ff-map-fullscreen .rkrk-ff-left {
  display: none !important;
}
body.rkrk-ff-map-fullscreen .rkrk-ff-split {
  position: fixed;
  inset: 0;
  z-index: 9999;
  margin: 0;
  width: 100vw;
  height: 100vh !important;
  max-height: 100vh !important;
  border: 0;
}
body.rkrk-ff-map-fullscreen .rkrk-ff-right,
body.rkrk-ff-map-fullscreen .rkrk-ff-mapwrap {
  width: 100%;
  height: 100%;
}
@media (max-width: 900px) {
  html.rkrk-ff-page, body.rkrk-ff-page {
    overflow: auto;
  }
  .rkrk-ff-split {
    flex-direction: column;
    height: auto !important;
    max-height: none !important;
  }
  .rkrk-ff-left {
    width: 100%;
    max-width: none;
    height: auto;
    max-height: 42vh;
    border-right: 0;
    border-bottom: 1px solid #cfd5dc;
  }
  .rkrk-ff-right,
  .rkrk-ff-mapwrap {
    height: 52vh;
    min-height: 52vh;
  }
}
</style>';
	echo '<script>document.documentElement.classList.add("rkrk-ff-page");document.body.classList.add("rkrk-ff-page");</script>';

	echo '<div class="rkrk-ff-split" id="rkrk-ff-split">' . chr(10);
	echo '<div class="rkrk-ff-left" id="rkrk-ff-left">' . chr(10);
	echo '<div class="rkrk-ff-left-top">' . chr(10);

	echo '<form method="get" action="' . $self . '">' . chr(10);
	echo '<input type="hidden" name="MDB" value="' . htmlspecialchars($RKRKMissionSelected) . '">' . chr(10);
	echo '<table>' . chr(10);
	echo '<tr><td>Zeitraum:</td><td>';
	echo DateField("SelFrom", $FromDate, $MinDate, $MaxDate);
	echo ' – ';
	echo DateField("SelTo", $ToDate, $MinDate, $MaxDate);
	echo '</td></tr>';
	echo '<tr><td></td><td>';
	echo "<input type='image' value='minus1woche' src='" . $IconLoc . "/minus1woche.png' formaction='" . $self . "?" . $MissionSelectionParameter . "&SelFrom=" . date('Y-m-d', strtotime($FromDate . ' -1 week')) . "&SelTo=" . date('Y-m-d', strtotime($ToDate . ' -1 week')) . "' height='" . $ButtonHeight . "' title='-1 Woche' style='background-color:transparent;border-color:transparent;vertical-align:middle;'>";
	echo "<input type='image' value='minus1tag' src='" . $IconLoc . "/minus1tag.png' formaction='" . $self . "?" . $MissionSelectionParameter . "&SelFrom=" . date('Y-m-d', strtotime($FromDate . ' -1 day')) . "&SelTo=" . date('Y-m-d', strtotime($ToDate . ' -1 day')) . "' height='" . $ButtonHeight . "' title='-1 Tag' style='background-color:transparent;border-color:transparent;vertical-align:middle;'>";
	echo "<input type='image' value='plus1tag' src='" . $IconLoc . "/plus1tag.png' formaction='" . $self . "?" . $MissionSelectionParameter . "&SelFrom=" . date('Y-m-d', strtotime($FromDate . ' +1 day')) . "&SelTo=" . date('Y-m-d', strtotime($ToDate . ' +1 day')) . "' height='" . $ButtonHeight . "' title='+1 Tag' style='background-color:transparent;border-color:transparent;vertical-align:middle;'>";
	echo "<input type='image' value='plus1woche' src='" . $IconLoc . "/plus1woche.png' formaction='" . $self . "?" . $MissionSelectionParameter . "&SelFrom=" . date('Y-m-d', strtotime($FromDate . ' +1 week')) . "&SelTo=" . date('Y-m-d', strtotime($ToDate . ' +1 week')) . "' height='" . $ButtonHeight . "' title='+1 Woche' style='background-color:transparent;border-color:transparent;vertical-align:middle;'>";
	echo '</td></tr>';
	echo '<tr><td></td><td>';
	echo "<input type='image' value='heute' src='" . $IconLoc . "/heute.png' formaction='" . $self . "?" . $MissionSelectionParameter . "&SelFrom=" . $Today . "&SelTo=" . $Today . "' height='" . $ButtonHeight . "' title='Heute' style='background-color:transparent;border-color:transparent;vertical-align:middle;'>";
	$saisonFrom = GetSasionStartDate(date('Y', strtotime($FromDate)));
	$saisonTo = GetSasionEndDate(date('Y', strtotime($FromDate)));
	echo "<input type='image' value='saison' src='" . $IconLoc . "/saison.png' formaction='" . $self . "?" . $MissionSelectionParameter . "&SelFrom=" . $saisonFrom . "&SelTo=" . $saisonTo . "' height='" . $ButtonHeight . "' title='Saison' style='background-color:transparent;border-color:transparent;vertical-align:middle;'>";
	echo ' <input type="submit" name="Einlesen" value="Anzeigen"> ';
	echo CreateSiteLink("Flugliste", "SelFrom=" . $FromDate . "&SelTo=" . $ToDate);
	echo CreateSiteLink("Home", "");
	echo '</td></tr>';
	echo '</table>' . chr(10);
	echo '</form>' . chr(10);

	echo '<div style="margin:8px 0 0;padding:8px;background:#FEF9E7;border:1px solid #E8D98A;font-size:13px;">';
	echo '<b>Geflogen = Ja</b> von ' . htmlspecialchars($FromDate) . ' bis ' . htmlspecialchars($ToDate) . '<br>';
	echo 'Flüge: <b>' . (int)$stats['flights'] . '</b> &nbsp;|&nbsp; Felder: <b>' . (int)$stats['fields'] . '</b>';
	if ($stats['flaeche'] > 0) {
		echo ' &nbsp;|&nbsp; Fläche (CSV): <b>' . number_format($stats['flaeche'], 2, '.', "'") . ' ha</b>';
	}
	if ($kmlCount > 0) {
		echo ' &nbsp;|&nbsp; Polygone in KML: <b>' . (int)$kmlCount . '</b>';
	}
	echo '</div>';

	if ($SystemInfo !== '') {
		echo '<div style="margin:8px 0 0;padding:8px;background:#FDEDEC;">' . $SystemInfo . '</div>';
	}

	if ($stats['fields'] > 0) {
		echo '<p class="rkrk-ff-actions" style="margin:8px 0 0;">';
		if ($mapUrlFull !== '') {
			echo '<a href="' . htmlspecialchars($mapUrlFull) . '" target="_flugflaechenmap" style="background:#6F8B5E;">Karte in neuem Tab</a>';
		}
		echo '<a href="' . htmlspecialchars($kmlUrl) . '" style="background:#2F3E28;">KML</a>';
		echo '<a href="' . htmlspecialchars($pdfUrl) . '" target="_pdf" style="background:#445566;">PDF</a>';
		echo '<a href="' . htmlspecialchars($pngUrl) . '" style="background:#556677;">PNG</a>';
		echo '</p>';
	}

	echo '</div>' . chr(10); // left-top

	echo '<div class="rkrk-ff-list" id="rkrk-ff-list">' . chr(10);
	if ($stats['fields'] === 0) {
		echo '<p><i>Keine geflogenen Flächen in diesem Zeitraum.</i></p>';
	} else {
		echo '<table>';
		echo '<thead><tr><th>#</th><th>Feld</th><th>Flüge</th><th>ha</th><th>Datum</th><th>Pilot</th></tr></thead><tbody>';
		$i = 1;
		foreach ($stats['feldIds'] as $fid) {
			$row = $stats['fieldList'][$fid] ?? array();
			echo '<tr>';
			echo '<td>' . $i . '</td>';
			echo '<td>' . htmlspecialchars($fid) . '</td>';
			echo '<td>' . (int)($row['Flights'] ?? 0) . '</td>';
			echo '<td>' . (($row['Flaeche'] ?? 0) > 0 ? number_format($row['Flaeche'], 2) : '') . '</td>';
			echo '<td>' . htmlspecialchars((string)($row['LastDate'] ?? '')) . '</td>';
			echo '<td>' . htmlspecialchars((string)($row['Pilot'] ?? '')) . '</td>';
			echo '</tr>';
			$i++;
		}
		echo '</tbody></table>';
	}
	echo '</div>' . chr(10); // list

	echo '</div>' . chr(10); // left

	echo '<div class="rkrk-ff-right" id="rkrk-ff-right">' . chr(10);
	echo '<div class="rkrk-ff-mapwrap" id="rkrk-ff-mapwrap">';
	echo '<button type="button" class="rkrk-ff-fs-float" id="rkrk-ff-fullscreen-btn" title="Karte vollbild">Vollbild</button>';
	if ($mapUrl !== '') {
		echo '<iframe id="rkrk-ff-mapframe" allow="geolocation; fullscreen" src="' . htmlspecialchars($mapUrl) . '"></iframe>';
	} else {
		echo '<div class="rkrk-ff-empty">Keine Karte: im gewählten Zeitraum gibt es keine geflogenen Flächen mit Polygondaten.</div>';
	}
	echo '</div>';
	echo '</div>' . chr(10); // right
	echo '</div>' . chr(10); // split

	echo '<script>
(function(){
  var split = document.getElementById("rkrk-ff-split");
  function fitSplit() {
    if (!split || document.body.classList.contains("rkrk-ff-map-fullscreen")) return;
    if (window.matchMedia && window.matchMedia("(max-width: 900px)").matches) {
      split.style.height = "";
      split.style.maxHeight = "";
      return;
    }
    var top = Math.max(0, Math.round(split.getBoundingClientRect().top));
    var h = Math.max(240, window.innerHeight - top - 4);
    document.documentElement.style.setProperty("--rkrk-ff-top", top + "px");
    split.style.height = h + "px";
    split.style.maxHeight = h + "px";
  }
  window.addEventListener("resize", fitSplit);
  window.addEventListener("load", fitSplit);
  setTimeout(fitSplit, 50);
  setTimeout(fitSplit, 300);

  var btn = document.getElementById("rkrk-ff-fullscreen-btn");
  if (!btn) return;
  function syncLabel() {
    var on = document.body.classList.contains("rkrk-ff-map-fullscreen");
    btn.textContent = on ? "Split-Ansicht" : "Vollbild";
    btn.title = on ? "Zurück zur Split-Ansicht" : "Karte vollbild";
    if (!on) fitSplit();
  }
  btn.addEventListener("click", function(){
    document.body.classList.toggle("rkrk-ff-map-fullscreen");
    syncLabel();
  });
  document.addEventListener("keydown", function(e){
    if (e.key === "Escape" && document.body.classList.contains("rkrk-ff-map-fullscreen")) {
      document.body.classList.remove("rkrk-ff-map-fullscreen");
      syncLabel();
    }
  });
  syncLabel();
})();
</script>';

	WriteToLog("get", basename($_SERVER['PHP_SELF']), "öffnen", $FromDate . ".." . $ToDate, (string)$stats['fields'], "");
?>
</body>
</html>
