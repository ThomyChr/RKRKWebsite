<!--
  Funktion zum Anzeigen der Felddaten und identifizieren von neuen Files im Flugkatalog
  
--->
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" >
<?php
  //session_start();
  include_once('scripts/rkrk_gen.php');
  include_once('scripts/rkrk_inputforms.php');
  include_once('scripts/rkrk_felddaten.php');
  include_once('scripts/rkrk_xmlfiles.php');
  include_once('scripts/rkrk_jsonfiles.php');
  include_once('scripts/rkrk_menu.php'); 
  include_once('rkrk_access_control.php'); 

	$pagetitle = "Dateien umbenennen";
	headerpage($pagetitle , false);
	GrantAccess();
	CreateTopMenu();
	PrintRKRKHeader($pagetitle);
echo "<style>
.rkrk-card-table {
  width: 100%;
  background: #fff;
}
.rkrk-card-table td,
.rkrk-card-table th {
  padding: 0.3rem 0.4rem;
  line-height: 1.15;
}
.rkrk-card-table tbody tr:nth-child(odd) {
  background: #f6f7f9;
}
.rkrk-card-table tbody tr:nth-child(even) {
  background: #eceff3;
}
@media (min-width: 1301px) {
  .rkrk-desktop-scroll {
    width: auto;
    max-width: none;
    margin-left: 0;
    margin-right: 0;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    padding-bottom: 4px;
  }
  .rkrk-desktop-scroll > table {
    min-width: 100%;
  }
}
@media (max-width: 1300px) {
  .rkrk-card-table tr.rkrk-card-header-row {
    display: none !important;
  }
  .rkrk-card-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 10px;
  }
  .rkrk-card-table tr.rkrk-card-row {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px 10px;
    margin: 0;
    padding: 8px;
    border: 1px solid #d9d9d9;
    border-radius: 10px;
    background: #f3f5f8;
  }
  .rkrk-card-table td {
    display: block;
    box-sizing: border-box;
    border: none;
    padding: 2px;
  }
  .rkrk-card-table td::before {
    content: attr(data-label);
    display: block;
    margin: 0 0 3px 0;
    font-weight: 600;
    color: #445;
    font-size: 12px;
  }
  .rkrk-card-table td.rkrk-mobile-full,
  .rkrk-card-table td[colspan] {
    grid-column: 1 / -1;
  }
  .rkrk-card-table td.rkrk-mobile-iconcell::before,
  .rkrk-card-table td.rkrk-mobile-full::before {
    content: '';
    display: none;
  }
  .rkrk-card-table td input,
  .rkrk-card-table td select,
  .rkrk-card-table td textarea,
  .rkrk-card-table td button {
    width: 100% !important;
    max-width: none !important;
    box-sizing: border-box;
    min-height: 36px;
  }
}
@media (max-width: 700px) {
  .rkrk-card-table tr.rkrk-card-row {
    grid-template-columns: 1fr;
    gap: 7px;
    padding: 7px;
  }
  .rkrk-card-table td::before {
    font-size: 13px;
  }
  .rkrk-card-table td input,
  .rkrk-card-table td select,
  .rkrk-card-table td textarea,
  .rkrk-card-table td button {
    min-height: 40px;
    font-size: 15px;
  }
}
</style>";
echo "<script>
function rkrkApplyResponsiveCardTables() {
  var tables = document.querySelectorAll('table');
  for (var t = 0; t < tables.length; t++) {
    var table = tables[t];
    if (table.dataset.rkrkResponsiveInit === '1') continue;
    var rows = table.querySelectorAll('tr');
    var headerRow = null;
    for (var i = 0; i < rows.length; i++) {
      if (rows[i].querySelectorAll('th').length > 0) {
        headerRow = rows[i];
        break;
      }
    }
    if (!headerRow) continue;
    var ths = headerRow.querySelectorAll('th');
    if (ths.length < 3) continue;
    var labels = [];
    for (var h = 0; h < ths.length; h++) {
      labels.push((ths[h].textContent || '').trim());
    }
    table.classList.add('rkrk-card-table');
    headerRow.classList.add('rkrk-card-header-row');
    for (var r = 0; r < rows.length; r++) {
      var row = rows[r];
      if (row === headerRow || row.querySelectorAll('th').length > 0) continue;
      var tds = row.querySelectorAll('td');
      if (!tds.length) continue;
      row.classList.add('rkrk-card-row');
      for (var c = 0; c < tds.length; c++) {
        var td = tds[c];
        if (td.hasAttribute('colspan')) {
          td.classList.add('rkrk-mobile-full');
          td.setAttribute('data-label', '');
          continue;
        }
        var label = (labels[c] || '').trim();
        td.setAttribute('data-label', label);
        if (label === '') td.classList.add('rkrk-mobile-iconcell');
      }
    }
    table.dataset.rkrkResponsiveInit = '1';
  }
}
function rkrkApplyDesktopTableScroll() {
  function stretchWrapperToViewport(wrap) {
    if (!wrap) return;
    var rect = wrap.getBoundingClientRect();
    var left = Math.max(0, Math.round(rect.left || 0));
    var vw = window.innerWidth || document.documentElement.clientWidth || 0;
    var width = Math.max(120, vw - left - 6);
    wrap.style.width = width + 'px';
    wrap.style.maxWidth = width + 'px';
    wrap.style.marginRight = '0';
  }
  function availableWidthFor(el) {
    var parent = el && el.parentNode ? el.parentNode.clientWidth : 0;
    var rect = el && el.getBoundingClientRect ? el.getBoundingClientRect() : null;
    var left = rect ? Math.max(0, Math.round(rect.left || 0)) : 0;
    var vw = window.innerWidth || document.documentElement.clientWidth || 0;
    var viewportRightSpace = Math.max(0, vw - left - 6);
    return Math.max(parent, viewportRightSpace);
  }
  var isNarrow = window.matchMedia('(max-width: 1300px)').matches;
  var wrappers = document.querySelectorAll('.rkrk-desktop-scroll');
  for (var w = 0; w < wrappers.length; w++) {
    var wrap = wrappers[w];
    stretchWrapperToViewport(wrap);
    var wrappedTable = wrap.querySelector(':scope > table');
    if (!wrappedTable) continue;
    if (isNarrow) {
      wrap.parentNode.insertBefore(wrappedTable, wrap);
      wrap.remove();
      continue;
    }
    var wrapAvailable = wrap.clientWidth;
    if (wrappedTable.scrollWidth <= wrapAvailable + 1) {
      wrap.parentNode.insertBefore(wrappedTable, wrap);
      wrap.remove();
    }
  }
  if (isNarrow) return;

  var tables = document.querySelectorAll('table');
  for (var i = 0; i < tables.length; i++) {
    var table = tables[i];
    if (!table || table.closest('.rkrk-desktop-scroll')) continue;
    var rowCount = table.querySelectorAll('tr').length;
    if (rowCount < 2) continue;
    if (table.scrollWidth <= availableWidthFor(table) + 1) continue;
    var wrap = document.createElement('div');
    wrap.className = 'rkrk-desktop-scroll';
    table.parentNode.insertBefore(wrap, table);
    wrap.appendChild(table);
    stretchWrapperToViewport(wrap);
  }
}
window.addEventListener('DOMContentLoaded', function() {
  rkrkApplyResponsiveCardTables();
  rkrkApplyDesktopTableScroll();
});
window.addEventListener('resize', rkrkApplyDesktopTableScroll);
</script>";

	// ===========================================
	// Definitions
	// ===========================================
		$fileoutput = "";
		$debugoutput = true;
		global $RegionSettings;
		$FormSet = Array(
							"NewOnly" => array ( "Field" => "NewOnly",
												"Name" => "Nur neue Felder",
												"Type" => "text",
												"Set" => true),
							"Changed" => array ( "Field" => "Changed",
												"Name" => "Geänderte Felder",
												"Type" => "text",
												"Set" => true),
										);

	$xml_file= "RKRKFelder_N.xml";

		
	// ===========================================
	// Functions
	// ============================================

//======================================
// Read in paramters for selection
//======================================

if ($_SERVER["REQUEST_METHOD"] == "POST") {
	if (isset($_POST["FeldNamenWahl"])) {
		$FeldNamenWahl = $_POST["FeldNamenWahl"];
	} else	{
		$FeldNamenWahl = '';
	}
}
//echo "Namenwahl: " . $FeldNamenWahl . "<br>";

if (isset($_GET["EnableDelete"])) {
   if (strtolower($_GET["EnableDelete"])=="on") {
		//echo "EnableDelete=on";
 		$EnableDelete= true;
	} else {
		//echo "EnableDelete=on";
		$EnableDelete= false;
		$dodelete= false;
	}
}

IF ($EnableDelete) {
	$EnableDeleteSet = "EnableDelete=on";
} else {
	$EnableDeleteSet = "EnableDelete=on";
}

if (isset($_GET["DoSafe"])) { 
	if ($_GET["DoSafe"]=="Y") {
		$dosafe= true;
	} else {
		$dosafe= false;
	}
} else {
	$dosafe= false;
}
if (isset($_GET["delete"]) && isset($_GET["FeldID"]) ) { 
	if ($_GET["delete"]=="Y") {
		$dodelete= $_GET["FeldID"];
		$dosafe = true;
	} else {
		$dodelete= false;
	}
} else {
	$dodelete= false;
}

if (isset($_GET["TM"])) {
	$TableMode= $_GET["TM"];
} else {
	$TableMode= "B";
}

// internal Legend
function Legends() {
	global $OptionFrei, $OptionWarning, $OptionGemeinde;
	//pre_r($Feldkatalog);
	echo "</br>";
	echo "<table>";
	echo "<tr valign='top'><td>";
	echo "<h3>Freigabe</h3>" .chr(10);
	echo "</td><td>";
	echo "<h3>Warnung</h3>" .chr(10);
	echo "</td><td colspan=5>";
	echo "<h3>Gemeinden</h3>" .chr(10);
	echo "</td></tr>";
	echo "<tr valign='top'><td>";
	echo "<ul>" . chr(10);
	foreach ($OptionFrei as $Key => $Entry) {
		echo "<li> <b>" . $Entry["Opt"] . "</b>  -  ". $Entry["Text"] . "</li>";
	}
	echo "</ul>" . chr(10);
	echo "</td><td>";
	echo "<ul>" . chr(10);
	foreach ($OptionWarning as $Key => $Entry) {
		echo "<li> <b>" . $Entry["Opt"] . "</b>  -  ". $Entry["Text"] . "</li>";
	}
	echo "</td><td>";
	echo "</ul>" . chr(10);
	echo "<ul>" . chr(10);
	$i= 0;
	foreach ($OptionGemeinde as $Key => $Entry) {
		echo "<li> <b>" . $Entry["Opt"] . "</b>  -  ". $Entry["Text"] . "</li>";
		//echo $i%5 . "<br>";
		if (($i%6)==5) {
			echo "</td><td>";
		}
		$i++;
	}
	echo "</ul>" . chr(10);
	echo "</td></tr>";
	echo "</table>";
	
}


// ===========================================
// HEADER FORM
// ===========================================

//removes what does not belong to tick list
$RegList = array_diff($RegionSettings, array('NO','ALL'));
$EnabledRegion = Array();
//form to change setting
echo "<i>Wähle Regionen und Felder der Tabelle und Drücke einlesen.:</i><br>";
echo '<form method="post" action="'. htmlspecialchars($_SERVER["PHP_SELF"]) . '">';
$i= 0;
$NoSelection = true;
//echo $i . " " . $KMLFileSettings->name . chr(10)
echo "<table class='rkrk-filter-form' width=100%>";
	echo "<tr><td class='rkrk-filter-label'>";
		echo "Regionen: ";
		echo "</td><td>";
		$NoSelection = rkrk_print_region_pills($RegionSettings, $EnabledRegion, (string)$i, '', true);
		$i++;
	echo "</td><td>";
	if ($Layout!=2023) {
		echo CreateSiteLink ("NeuesFeld", "NeuesFeld=N");
		echo CreateSiteLink ("EditFeld", "");
		echo CreateSiteLink ("Fluege", "");
		echo CreateSiteLink ("EditFeldliste", "");
		echo CreateSiteLink ("FeldKarte", "Reg=ALL" . $StandardSettingMap);
		echo CreateSiteLink ("FeldListe", "Reg=ALL&Typ=intern");
		echo CreateSiteLink ("Home", "");
	}
	echo "</td></tr>";
	echo "</tr>";
	echo "<tr><td class='rkrk-filter-label'>";
		echo "Einstellungen: ";
		echo "</td><td>";
		$isPost = isset($_SERVER["REQUEST_METHOD"]) && $_SERVER["REQUEST_METHOD"] == "POST";
		rkrk_echo_pills_open();
		foreach ($FormSet as $Feld ) {
			if ($isPost) {
				$on = !empty($_POST[$Feld["Field"] . "_en"]);
			} else {
				$on = !empty($Feld["Set"]);
			}
			$FormSet[$Feld["Field"]]["Set"] = $on;
			rkrk_echo_pill_checkbox($Feld["Field"] . "_en", $Feld["Name"], $on);
		}
		rkrk_echo_pills_close();
		$i++;
	echo "</td>";
	echo "<td rowspan=2>";
	//Tabellenmodus

	//Enable Delete
	if ((GetAccess("DeleteData")) && ($TableMode=="B")) {
		if ($EnableDelete) {
			//echo "ED - ON";
			echo "<input type='image' value='EnableDelete' src='" . $IconLoc . "/delete_on.png' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?" . "EnableDelete=on&TM=" . $TableMode . "' height='" . $ButtonHeight . "'  title='Delete Moduls ausschalten'  style='background-color:transparent; border-color:transparent;vertical-align: middle;'>" .chr(10);
		} else {
			//echo "ED - OFF";
			echo "<input type='image' value='EnableDelete' src='" . $IconLoc . "/delete_off.png' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?" . "EnableDelete=ON&TM=" . $TableMode . "' height='" . $ButtonHeight . "'title='Delete Moduls einschalten'  style='background-color:transparent; border-color:transparent;vertical-align: middle;'>" .chr(10);
		}
	}
	echo MissionLibraryButtons("&". $EnableDeleteSet);

	echo "</td>";
	
	echo "</tr>";
	echo "<tr><td>";
	echo "  Suche: " .chr(10);
	echo "</td><td>";
	echo InputField("FeldNamenWahl", $FeldNamenWahl, 20);
	echo "</td></tr>";
echo "</table  width=100%>";
if (GetAccess("DebugDataBase")) {
	echo '<input type="hidden" name="Act" id="Act" value="save" formaction="' .htmlspecialchars($_SERVER["PHP_SELF"]). '?'. $EnableDeleteSet.'&DBW=' . $_GET["DBW"]. '&TM=' . $TableMode . '">'. chr(10);
	echo '<input type="submit" name="Einlesen" value="Einlesen" formaction="' .htmlspecialchars($_SERVER["PHP_SELF"]). '?'. $EnableDeleteSet.'&DBW=' . $_GET["DBW"]. '&TM=' . $TableMode . '"> '. chr(10);
} else {
	echo '<input type="hidden" name="Act" id="Act" value="save" formaction="' .htmlspecialchars($_SERVER["PHP_SELF"]). '?'. $EnableDeleteSet.'&TM=' . $TableMode . '">'. chr(10);
	echo '<input type="submit" name="Einlesen" value="Einlesen" formaction="' .htmlspecialchars($_SERVER["PHP_SELF"]). '?'. $EnableDeleteSet.'&TM=' . $TableMode . '"> '. chr(10);
}

echo '<br>';
//echo '</form>';
echo '<br>';
//pre_r($EnabledRegion);
//pre_r($RegionSettings);
//pre_r($TableSetup);
//pre_r($FormSet);


//////////////////////////////////////////////
// Einlesen bestehender Feldkatalog (XML)
//////////////////////////////////////////////

// echo "XML location:" . $xmlMissionLocation . " " . $RKRKFelderfileXML. "<br>";

$OptionJaeger = Array();
$Feldkatalog= GetFeldkatalog($xmlMissionLocation.$RKRKFelderfileXML);


///////////////////////////////////////
// Process Input Data
///////////////////////////////////////


if ($_SERVER["REQUEST_METHOD"] == "POST") {
	if ($_POST["NumEntries"]>0) {
		//new configuration, safe configuration file
		//echo "NumE: " . $_POST["NumEntries"] .  "<br>" . chr(10);

		//Write to LOGfile
		WriteToLog("save", basename($_SERVER['PHP_SELF']), "Felddatenliste ",strval($_POST["JG" . $i]),$file, $RKRKMissionSelected,"");		
		for ($i = 1; $i < $_POST["NumEntries"]; $i++) {
			//echo $i . "<br>";
			//echo  $i . "  " . $_POST["FeldID" . $i] . " " . $_POST["Feldname" . $i] . " " . $_POST["GrundstueckID" . $i];
	}




// =================================
// EDIT MODE
// upload information to xml file
// =================================
	//echo "<br>Postentries: " . $_POST["NumEntries"]. "<br><br>";
	$Transfer = array();
	if ($_POST["NumEntries"]>0) {
		//new configuration, safe configuration file
		//echo "NumE: " . $_POST["NumEntries"] .  "<br>" . chr(10);
		for ($i = 1; $i < $_POST["NumEntries"]; $i++) {
			//echo $i . "<br>";
			//echo  $i . "  " . $_POST["FeldID" . $i] . " " . $_POST["Feldname" . $i] . " " . $_POST["GrundstueckID" . $i];
			if (true) {		
				// READ Input data to array
				// Tabellenfelder
				//echo "Round: " . $i . "<br>";
				// Basisttabelle erstellen
				if (
					(
					 ((isset($_POST["JG" . $i])) || (isset($_POST["JG_New" . $i]))) ||
				     ((isset($_POST["Feldname" . $i])) || (isset($_POST["Feldname_New" . $i]))) ||
				     ((isset($_POST["Flurname" . $i])) || (isset($_POST["Flurname_New" . $i])))  ||
				     ((isset($_POST["GrundstueckID" . $i])) || (isset($_POST["GrundstueckID_New" . $i])))  ||
				     ((isset($_POST["GrundstueckID" . $i])) || (isset($_POST["GrundstueckID_New" . $i])))
					 ) && (
					 (strval($_POST["JG" . $i]) != (strval($_POST["JG_New" . $i]))) ||
					 (strval($_POST["Feldname" . $i]) != (strval($_POST["Feldname_New" . $i])))  ||
					 (strval($_POST["Flurname" . $i]) != (strval($_POST["Flurname_New" . $i])))  ||
				     (strval($_POST["GrundstueckID" . $i]) != (strval($_POST["GrundstueckID_New" . $i])))  ||
					 (strval($_POST["FeldID" . $i]) != (strval($_POST["FeldID_New" . $i]))) 
					 )
				   ) {
					  //echo (strval($_POST["JG_New" . $i])) . " -" ;
					  //echo (strval($_POST["Feldname_New" . $i]))  . " -" ; 
					  //echo (strval($_POST["Flurname_New" . $i]))  . " -" ;
					  //echo (strval($_POST["GrundstueckID_New" . $i])) . " -" ;
					  //echo (strval($_POST["FeldID_New" . $i])) . " -" ;

						$Transfer[$i]["JG"]["Old"] = strval($_POST["JG" . $i]);
						$Transfer[$i]["JG"]["New"] = strval($_POST["JG_New" . $i]);
						$Transfer[$i]["Feldname"]["Old"] =strval($_POST["Feldname" . $i]);
						$Transfer[$i]["Feldname"]["New"] =str_replace("_","-",strval($_POST["Feldname_New" . $i]));
						$Transfer[$i]["Flurname"]["Old"] =strval($_POST["Flurname" . $i]);
						$Transfer[$i]["Flurname"]["New"] =strval(str_replace("_","-",str_replace(" ","-",$_POST["Flurname_New" . $i])));
						$Transfer[$i]["GrundstueckID"]["Old"] = strval($_POST["GrundstueckID" . $i]);
						$Transfer[$i]["GrundstueckID"]["New"] = strval($_POST["GrundstueckID_New" . $i]);
						//echo "Next KatID: " . $_POST["Region"] . "-" . sprintf("%03d", $RegionSettings[$JG]["MaxID"]+1)  . " (" .$RegionSettings[$JG]["MaxID"] . ")<br>" . chr(10);
						$NewFeldID = $_POST["JG_New" . $i] . "-" . substr($_POST["FeldID_New" . $i], -3);
						$KatMaxNum = $_POST["JG_New" . $i] . "-" . sprintf("%03d", $RegionSettings[$_POST["JG_New" . $i]]["MaxID"]+1);
						$Transfer[$i]["FeldID"]["Old"] =strval($_POST["FeldID" . $i]);
						//echo "JG old ". $Transfer[$i]["JG"]["Old"] . "<br>";
						//echo "JG new ". $Transfer[$i]["JG"]["New"] . "<br>";
						//echo "FeldID old ". $Transfer[$i]["FeldID"]["Old"] . "<br>";
						//echo "FeldID new ". $NewFeldID . "<br>";
						if ( 
							($Transfer[$i]["JG"]["Old"] == $Transfer[$i]["JG"]["New"]) and
							($Transfer[$i]["FeldID"]["Old"] == strval($NewFeldID))
							) {
								//echo "FeldID unchanged <br>";
								// FeldID unchanged
								$Transfer[$i]["FeldID"]["New"] =$NewFeldID;
							} else {
							if (
									($Feldkatalog[$_POST["JG_New" . $i]][$NewFeldID]["FeldID"]!="") 
									or ($_POST["FeldID_New" . $i] =="")
								)	
								{
								//echo "GetMaxID : " . $KatMaxNum . "<br>";
								// Number exists already, so use Max number_format
								$Transfer[$i]["FeldID"]["New"] =$KatMaxNum;
								$RegionSettings[$_POST["JG_New" . $i]]["MaxID"]++;
								$KatMaxNum++;
							} else {
								// FeldID does not exist yet
								$Transfer[$i]["FeldID"]["New"] =$NewFeldID;
							}
						}
						$Transfer[$i]["update"] =true;

				}

				//Write to LOGfile
				WriteToLog("save", basename($_SERVER['PHP_SELF']), "Filename ",$FeldID,$file, $RKRKMissionSelected,"");

			} // true
		}  // i NumEntries
	} // POST Numentries
	//pre_r($Transfer);

	// ========================
	//Process Transformation
	// ========================
	foreach ($Transfer as $TransferEntry) {
//		if ($Transfer["update"]==true) {
			
			// 1) rename Files
			// ---------------
			$FilesToSearch = $RKRKLocalLoactionMissionFiles.$RegionSettings[$TransferEntry["JG"]["Old"]]["Dir"]."/".$TransferEntry["FeldID"]["Old"]."*.*";
			//echo "File to search: " . $FilesToSearch;
			
			$FileList = glob($FilesToSearch);
			//pre_R($FileList);

			//Reset Data
			$SuchfeldBild = "";
			$FlugplanBild = "";
			$JasonFile = "";
			$KMLFile = "";
			$KMZFile = "";
			$LichtiFile = "";

			foreach ($FileList as $FileManage) {
				//echo "Filename: " . $FileManage . "<br>";
				$path_parts = pathinfo($FileManage);
				//echo $path_parts['dirname'], "\n";
				//echo $path_parts['basename'], "\n";
				//echo $path_parts['extension'], "\n";
				//echo $path_parts['filename'], "\n";

				$FilenameMeaning = GetMeaningFilename($path_parts['basename']);
					// [0] KatID
					// [1] Feldname
					// [2] Flurname
					// [3] GeoID
					// [4] Heigth
					// [5] Pilot
					// [6] Year
					// [7] Corefilename
				//echo "<br>FilenameMeaning: " . $FilenameMeaning[7];

				//pre_r($FilenameMeaning);
				//echo "<br>CoreFilename: " . $Corefilename;
				$NewCorefilename = $RegionSettings[$TransferEntry["JG"]["New"]]["Dir"]."/";
				if ($TransferEntry["Flurname"]["New"]!="") {
					//echo " with Flurname <br>";
					$NewCorefilename .= $TransferEntry["FeldID"]["New"] . "_" . $TransferEntry["Feldname"]["New"] . "_" . $TransferEntry["Flurname"]["New"] . "_". $TransferEntry["GrundstueckID"]["New"];
				} else {
					//echo " without Flurname <br>";
					$NewCorefilename .= $TransferEntry["FeldID"]["New"] . "_" . $TransferEntry["Feldname"]["New"] . "_" . $TransferEntry["GrundstueckID"]["New"];
				}
				// replace Spaces with "-"
				$NewCorefilename = str_replace(" ", "-", $NewCorefilename);
				$NewFilename = $NewCorefilename . "_" .$FilenameMeaning[4] . "_" . $FilenameMeaning[5] . "_" . $FilenameMeaning[6].".".$path_parts['extension'];
				$OldFileInfo = pathinfo($FileManage );
				$NewFileInfo = pathinfo($NewFilename);
				//echo "Oldfilename: ". $FileManage . "<br>";
				//pre_R($OldFileInfo);
				//echo "NewBasefilename: ". $NewFilename . "<br>";
				//pre_R($NewFileInfo);
				// Transfer filesize

				// prepare XML entries
				if ($path_parts['extension']=="jpg") {
					//echo "jpg type" . "<br>";
					if (strval($FilenameMeaning[4])=="00") {
						if 	($SuchfeldBild != "")
							$SuchfeldBild .= ", ";
						$SuchfeldBild .= $NewFilename;
					} else {
						if 	($FlugplanBild != "")
							$FlugplanBild .= ", ";
						$FlugplanBild .= $NewFilename;
					} 
				}
				elseif ($path_parts['extension']=="kml") {
					//echo "kml type" . "<br>";
					if 	($KMLFile != "")
						$KMLFile .= ", ";
					$KMLFile .= $NewFilename;;
				}
				elseif ($path_parts['extension']=="kmz") {
					//echo "kmz type" . "<br>";
					if 	($KMZFile != "")
						$KMZFile .= ", ";
					$KMZFile .= $NewFilename;;
				}
				elseif ($path_parts['extension']=="lchm") {
					//echo "lchm type" . "<br>";
					if 	($LichtiFile != "")
						$LichtiFile .= ", ";
					$LichtiFile .= $NewFilename;
				}
				elseif ($path_parts['extension']=="json") {
					//echo "json type" . "<br>";
					if 	($JasonFile != "")
						$JasonFile .= ", ";
					$JasonFile .= $NewFilename;
					// upload JSON File
					echo "UpdateJson: " . $OldFileInfo['basename'] . " - ".  $TransferEntry["JG"]["Old"]  . " - " . $NewFileInfo['basename'] . " - ".  $TransferEntry["JG"]["New"] . "<br>";
					//UpdateFilenameInJson($OldFileInfo['basename'], basename($NewFileInfo['basename']) , $TransferEntry["JG"]["Old"]);
					UpdateFilenameAndFeldIDInJson($OldFileInfo['basename'], basename($NewFileInfo['basename']) ,$TransferEntry["JG"]["Old"], $TransferEntry["JG"]["New"], $TransferEntry["FeldID"]["New"]);
					//UpdateFeldkatalogNummerInJson($NewFileInfo['basename'], $TransferEntry["FeldID"]["New"]);
					//UpdateGruppengebietInJson($NewFileInfo['basename'], $TransferEntry["JG"]["New"]);
					//echo "-  done";
				}

				WriteToLog("move", basename($_SERVER['PHP_SELF']), "datafile ",$OldFileInfo['basename']  . " => ". $NewFileInfo['basename'],$file, $RKRKMissionSelected,"");
				//echo "Move File  " . $FileManage . " => ".  $RKRKLocalLoactionMissionFiles.$NewFilename . "<br>"; 
				//echo $path_parts['extension'] . "<br>";

			
				// delete new file if file exists
				//echo "Delete File: " . $RKRKLocalLoactionMissionFiles.$NewFilename . "<br>";
				if (file_exists($RKRKLocalLoactionMissionFiles.$NewFilename))
					unlink($RKRKLocalLoactionMissionFiles.$NewFilename);
				
				// Move file finally
				//echo "Rename File: " . $FileManage . " => " . $RKRKLocalLoactionMissionFiles.$NewFilename . "<br>";
				rename($FileManage , $RKRKLocalLoactionMissionFiles.$NewFilename);
			}

			
			// 3) update data in Feldkatalog
			// ---------------
			//echo "SuchflugBild: " .$SuchfeldBild . "<br>";
			//echo "FlugplanBild: " .$FlugplanBild  . "<br>";
			//echo "JasonFile: " .$JasonFile . "<br>";
			//echo "KMLFile: " .$KMLFile . "<br>";
			//echo "LichtiFile: " .$LichtiFile . "<br>";


			// Create New Array Entry if JG or FeldID changed
			$JG = $TransferEntry["JG"]["New"];
			$FeldID = $TransferEntry["FeldID"]["New"];
			if (($TransferEntry["FeldID"]["Old"] != $TransferEntry["FeldID"]["New"]) ||
				($TransferEntry["JG"]["Old"] != $TransferEntry["JG"]["New"]) )
				{ 
					//echo "Make new Array Entry " . $TransferEntry["JG"]["New"]. "-" . $TransferEntry["FeldID"]["New"] . " and delet old one " . $TransferEntry["JG"]["Old"]. "-" . $TransferEntry["FeldID"]["Old"] . "<br>";
					$Feldkatalog[$TransferEntry["JG"]["New"]][$TransferEntry["FeldID"]["New"]] = $Feldkatalog[$TransferEntry["JG"]["Old"]][$TransferEntry["FeldID"]["Old"]];
					//lösche alten Eintrag
					//pre_r($Feldkatalog[$TransferEntry["JG"]["New"]][$TransferEntry["FeldID"]["New"]]);
					unset($Feldkatalog[$TransferEntry["JG"]["Old"]][$TransferEntry["FeldID"]["Old"]]);
				}
			//echo "FeldID: " . $FeldID . "<br>" . chr(10);
			//echo "JG: " . $Feldkatalog[$JG][$FeldID]["JG"] . "<br>" . chr(10);
			$Feldkatalog[$JG][$FeldID]["JG"]= $TransferEntry["JG"]["New"];
			$Feldkatalog[$JG][$FeldID]["FeldID"]= $TransferEntry["FeldID"]["New"];
			$Feldkatalog[$JG][$FeldID]["Feldname"]= $TransferEntry["Feldname"]["New"];
			//echo "Feldname: " . $Feldkatalog[$JG][$FeldID]["Feldname"] . "<br>" . chr(10);
			$Feldkatalog[$JG][$FeldID]["Flurname"]= $TransferEntry["Flurname"]["New"];
			//echo "Flur: " . $Feldkatalog[$JG][$FeldID]["Flurname"] . "<br>" . chr(10);
			$Feldkatalog[$JG][$FeldID]["GrundstueckID"]= $TransferEntry["GrundstueckID"]["New"];
			//echo "GstkID: " . $Feldkatalog[$JG][$FeldID]["GrundstueckID"] . "<br>" . chr(10);
			// Field need to be updated with new filenames
			$Feldkatalog[$JG][$FeldID]["SuchfeldBild"]= $SuchfeldBild;
			$Feldkatalog[$JG][$FeldID]["FlugplanBild"]= $FlugplanBild;
			//echo "FlugplanBild: " . $Feldkatalog[$JG][$FeldID]["FlugplanBild"] . "<br>" . chr(10);
			
			//echo "JasonFile: " . $RegionSettings[$JG]["Dir"]."/".$NewFileName. "." . $FileExtension . "<br>" . chr(10);
			$Feldkatalog[$JG][$FeldID]["JasonFile"]= $JasonFile; 
			$Feldkatalog[$JG][$FeldID]["KMLFile"]= $KMLFile ;
			$Feldkatalog[$JG][$FeldID]["KMZFile"]= $KMZFile ;
			$Feldkatalog[$JG][$FeldID]["LichtiFile"]= $LichtiFile;
			//pre_r($Feldkatalog[$JG][$FeldID]);
//		} // Transfer update true
	} //foreach
	// update XML
	
	SaveFlugkatalog($Feldkatalog, $dosafe);
	SaveFlugkatalog2CSV ($Feldkatalog); 

	//pre_r(array_keys($Feldkatalog));
	//pre_r($Feldkatalog);
	//control elements

	// safe history
	$file = $xmlMissionLocation . $RKRKFelderfileXML;
	$newfile = $historyfilelocation . date("YmdH") . "00_" . $RKRKFelderfileXML;
	//echo "<br>History: " . $file . " to  " . $newfile;
	if (!copy($file, $newfile)) {
		$SystemInfo .= "failed to copy $file...\n";
	}
}	

if (isset($_POST["AnzLinesPPage"])) {
$AnzLinesPPage= 30;
	if ($_POST["AnzLinesPPage"]=="all") {
		$AnzLinesPPage= 999;
	} else {
		$AnzLinesPPage= $_POST["AnzLinesPPage"];
	}
} else {
	$AnzLinesPPage= 30;
}

if (isset($_GET["ActPage"]) and $_GET["ActPage"]!="") {
	$ActPage= $_GET["ActPage"];
} else {
	$ActPage= 1;
}
if (isset($_POST["FontSize"]) and $_POST["FontSize"]!="") {
	$FontSize= $_POST["FontSize"];
}

//Erneutes Einlesen der Felddaten (nach Änderung)
$Feldkatalog= GetFeldkatalog($xmlMissionLocation.$RKRKFelderfileXML);
//pre_R($Feldkatalog);

$KatFileList = ReadMissionFiles ($RegionSettings, $EnabledRegion);

	// JG/Region selection
	//pre_r($EnabledRegion);
	foreach ($EnabledRegion as $key => $Reg) {
		if ($Reg==1)
		$JGs2Select = explode(",", $RegionSettings[$key]["JGList"]);
		foreach ($JGs2Select as $JGtoSelect)
		{
		  $RegionsSelected[$JGtoSelect] = $JGtoSelect;
		}
		//pre_R($JGs2Select);
	}
	//pre_R($RegionsSelected);
    //pre_r($Feldkatalog);

	if ($fileloaded=true) {
		// ===========================================
		// Spezielle Filter
		// ===========================================
		$i=1;
		//Write to LOGfile
		WriteToLog("get", basename($_SERVER['PHP_SELF']), "Feldfileliste ",$FeldID,$file, $RKRKMissionSelected,"");
		//echo "Feldnamenwahl: " . $FeldNamenWahl . "<br>";
		if (($FormSet["NewOnly"]["Set"]== true) 
			or ($FormSet["Changed"]["Set"]== true)
			or ($FeldNamenWahl != "")
			 ) {
			foreach ($Feldkatalog as $Key => $Region) {
				//if ($EnabledRegion[$Key]==1) {
				if  (in_array($Key, $RegionsSelected)) {
					foreach ($Region as $Key => $Feld) {
						//echo "Region " . $Key . "<br>";
						// selected to show
						if (	
								$FeldNamenWahl == "" 
								or like($Feld["FeldID"], strval($FeldNamenWahl))
								or (strpos($FeldNamenWahl, strval($Feld["FeldID"]))!== false)
								or like($Feld["Feldname"], $FeldNamenWahl)
								or like($Feld["Flurname"], $FeldNamenWahl)
							) 
						{
							//echo $Feld["Feldname"] . " - ";
							if (	$FeldNamenWahl != "" 
									or
									(
										($FormSet["NewOnly"]["Set"]== true)  
										and ($KatFileList[$Feld["JG"]][$Feld["FeldID"]]["FeldID"] != $Feld["FeldID"])
									) or
								(
										($FormSet["Changed"]["Set"]== true) 
											and (
											($KatFileList[$Feld["JG"]][$Feld["FeldID"]]["Feldname"] != $Feld["Feldname"]) 
											or ($KatFileList[$Feld["JG"]][$Feld["FeldID"]]["Flurname"] != $Feld["Flurname"])
										)
									)
								)
								{
									$Feldkatalog[$Feld["JG"]][$Feld["FeldID"]]["ListNr"]= $i;
									$i++;
								} else {
									$Feldkatalog[$Feld["JG"]][$Feld["FeldID"]]["ListNr"]= "";
								}
							} else {
									$Feldkatalog[$Feld["JG"]][$Feld["FeldID"]]["ListNr"]= "";
						}
					}
				}
			}
		}
		//pre_r($Feldkatalog);

		if ($NoSelection==false) {
			$i=1;
			$pageline = 1;
			$MaxPages = ceil($MaxSelectedEntries/$AnzLinesPPage);
			$showfrom = ($ActPage-1)*$AnzLinesPPage - 1;
			$showto = $showfrom + $AnzLinesPPage;
			$FontSizeInPoints = 11;
			if ($FontSize== "") {
				$FontSize= "100%";
			}
			$TableColor =  GetTableColor ("");
			AlternateLineTable($TableColor['even'], $FontSize, $FontSizeInPoints, $TableColor['odd']);

			echo "<br>" .chr(10);
			PageControl($ActPage, $MaxPages, $AnzLinesPPage, $FontSize, false, "Dateien ändern");
			echo "<br>" .chr(10);
			//page
			echo "<div id='alle' class='tabcontent'>" . chr(10);
			echo "<table>" .chr(10);
			echo "<tr >" .chr(10);
			if ($TableMode=="B") {
				// Titel Bearbeitungsmodus
				echo "<th>#</th>" .chr(10);
				foreach ($TableSetup as $Feld ) {
					if ($Feld["Show"]== true) {
						echo "<th>";
						echo $Feld["Name"];
						echo "</th>";
					}	
				}
			} else {
				// Titel Umbenennungsmodus
				echo "<th>#</th>" .chr(10);
				echo "<th></th>" .chr(10);
				echo "<th>JG</th>";
				echo "<th>FeldID</th>";
				echo "<th>Name</th>";
				echo "<th>Flurname</th>";
				echo "<th>GeoID</th>";
				echo "<th>Gde</th>";
				echo "<th>Corefilename</th>";
				echo "<th>#</th>";
			}
			//echo '<form method="post" action="'. htmlspecialchars($_SERVER["PHP_SELF"]) . '">'. chr(10);
			//echo $showfrom . ", " . $showto . ", " . $MaxSelectedEntries . ", " . $AnzLinesPPage . ", " . $MaxPages;
			foreach ($Feldkatalog as $Key => $Region) {
				//echo ($Key) . "<br>";
				//pre_r($Region);
				if  (in_array($Key, $RegionsSelected)) {
				//if ($EnabledRegion[$Key]==1) {
					foreach ($Region as $Key => $Feld) {
						//echo ($Key) . "<br>";
						//pre_r($Feld);
						if ($Feld["ListNr"]!="" &&
							$Feld["ListNr"]>=$showfrom && 
							$Feld["ListNr"]<=$showto){
								//page show control

							$CoreFileName = $Feld["CorName"];
							$CoreFileNameWoULM = str_replace($searchSDZ2, $replaceSDZ2 ,$Feld["CorName"]);
							$LenFeldID = strlen($Feld["FeldID"]);
								// ----------------------
								// Start Liste Umbenennen
								// ----------------------
								echo "<tr class='rkrkedit'>" .chr(10);
								// Number
								echo "<td>" . chr(10);
								if ($EnableDelete) {
									// delete
									if (GetAccess("DeleteData")) {
										echo "<input type='image' value='1st page' src='" . $IconLoc . "/delete.png' formaction='" . htmlspecialchars($_SERVER["PHP_SELF"]) . "?ActPage=" . $ActPage . "&FeldID=" . $Feld["FeldID"] . "&delete=Y&EnableDelete=" . $_GET["EnableDelete"]. "&DBW=" . $_GET["DBW"]. "' height='" . $ButtonHeightSmall . "' title='Feld " . $Feld["FeldID"] . " löschen'>" .chr(10);
									} else {
										echo "<input type='image' value='1st page' src='" . $IconLoc . "/delete.png' formaction='" . htmlspecialchars($_SERVER["PHP_SELF"]) . "?ActPage=" . $ActPage . "&FeldID=" . $Feld["FeldID"] . "&delete=Y&EnableDelete=" . $_GET["EnableDelete"]. "' height='" . $ButtonHeightSmall . "'  title='Feld " . $Feld["FeldID"] . " löschen'>" .chr(10);
									}
								}
								$Inputfieldname = "Number".$i;
								echo "<b>" . $pageline . "<b>" ;
								echo "<td>";
								echo "<br>NEU" ;
								echo "</td>";
								echo InputField($Inputfieldname, $i, 0, "left", "hidden");
								// JG
								echo "<td>";
								echo $Feld["JG"];
								$Inputfieldname = "JG".$i;
								echo InputField($Inputfieldname, $Feld["JG"], 0, "left", "hidden");
								echo "<br>";
								$Inputfieldname = "JG_New".$i;
								echo SelectFieldMultiOptArr($Inputfieldname, $Feld["JG"] ,$RegionSettings );
								echo "</td>";
								// FeldID
								echo "<td>";
								echo $Feld["FeldID"];
								$Inputfieldname = "FeldID".$i;
								echo InputField($Inputfieldname, $Feld["FeldID"], 0, "left", "hidden");
								echo "<br>";
								$Inputfieldname = "FeldID_New".$i;
								echo InputField($Inputfieldname, $Feld["FeldID"], 5);
								echo "</td>" . chr(10);
								// Feldname
								echo "<td>" . chr(10);
								$Inputfieldname = "Feldname".$i;
								echo InputField($Inputfieldname, $Feld["Feldname"], 0, "left", "hidden");
								echo $Feld["Feldname"];
								echo "<br>";
								$Inputfieldname = "Feldname_New".$i;
								echo InputField($Inputfieldname,$Feld["Feldname"], 30);
								echo "</td>";
								// Flurname
								echo "<td>";
								$Inputfieldname = "Flurname".$i;
								echo InputField($Inputfieldname, $Feld["Flurname"],  0,"left", "hidden");
								echo $Feld["Flurname"];
								echo "<br>";
								$Inputfieldname = "Flurname_New".$i;
								echo InputField($Inputfieldname, $Feld["Flurname"], 30);
								echo "</td>" . chr(10);
								// GrundstückID
								echo "<td>" . chr(10);
								$Inputfieldname = "GrundstueckID".$i;
								echo InputField($Inputfieldname, $Feld["GrundstueckID"], 0, "left", "hidden");
								echo $Feld["GrundstueckID"];
								echo "<br>";
								$Inputfieldname = "GrundstueckID_New".$i;
								echo InputField($Inputfieldname, $Feld["GrundstueckID"], 6);
								echo "</td>" . chr(10);
								// Gemeinde
								echo "<td>" .chr(10);
								echo $Feld["Gemeinde"];
								echo "</td>" . chr(10);
								// Corefilename
								echo "<td>" .chr(10);
								echo $CoreFileName;
								echo "</td>" . chr(10);
								// Listernummer
								echo "<td>" . chr(10);
								echo "<b>" . $pageline . "<b>" ;
								echo "</td>" . chr(10);
								
								// ----------------------
								// Ende Liste Umbenennen
								// ----------------------
							}
						$i++;
						$pageline++;
						}
					}
				}
			}
			echo "</table>" .chr(10);
			// end tab
			echo "</div>" . chr(10);
			echo "<div id='Info' class='tabcontent'>" . chr(10);
			echo "</div>" . chr(10);
			echo "<div id='Freigabe' class='tabcontent'>" . chr(10);
			echo "</div>" . chr(10);
			echo "<div id='Notizen' class='tabcontent'>" . chr(10);
			echo "</div>" . chr(10);
			echo "<table>" .chr(10);
			echo "<tr><td>" .chr(10);
			
			echo '<input type="hidden" name="NumEntries" id="NumEntries" value="'. $i. '" />'. chr(10);
			PageControl($ActPage, $MaxPages, $AnzLinesPPage, $FontSize, true, "Dateien ändern");
			echo "</td></tr>" .chr(10);
			echo "</table>" .chr(10);
		} else	{
			echo "<i>Region wählen und einlesen dücken!</i>" .chr(10);
		}
		echo "</form>" .chr(10);
	}
	Legends();
echo "</br>";
?>

