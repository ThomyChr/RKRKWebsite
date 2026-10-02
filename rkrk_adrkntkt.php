<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" >
<?php
	session_start();
	include_once('scripts/rkrk_gen.php');
	include_once('scripts/rkrk_csvfiles.php');
	include_once('scripts/rkrk_inputforms.php');
	include_once('scripts/rkrk_adr_ui.php');
	$AdressTableType="Kontakt";
	include_once('scripts/rkrk_adr.php');
	include_once('scripts/rkrk_menu.php');
	include_once('rkrk_access_control.php'); 
	
	$pagetitle = "Kontaktadressen";
	headerpage($pagetitle, false);

	//check impropriate IP addresses accessing page
	CheckIPandExit();
	GrantAccess();
	CreateTopMenu();
	PrintRKRKHeader($pagetitle);
echo "<link rel='stylesheet' href='scripts/rkrk_adr_grid.css?v=20260924live2'>";
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
    if (table.classList.contains('rkrk-adr-grid')) continue;
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
    if (!table || table.classList.contains('rkrk-adr-grid') || table.closest('.rkrk-desktop-scroll') || table.closest('.rkrk-adr-grid-wrap')) continue;
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

	//Write to LOGfile
	WriteToLog("get", basename($_SERVER['PHP_SELF']), "öffnen ","","","");

	SetMissionLocation();
	$MissionSelectionParameter = "MDB=" . $RKRKMissionSelected;
	//echo "Loc: " . $RKRKMissionSelected . " - MDB: " .$csvDocLocation . "<br>";

	// ===========================================
	// Gobal Defintions
	// ===========================================

	$csv_desc = ';';
	$csv_file= $RKRKContactFile;
	$excel_csv_file = "RKRK Kontaktnummern (".$_SESSION[$SessNme_RegisteredInitialen].").csv";
	$excel_csv_path = $historyfilelocation . $excel_csv_file;

	//Read CSV file
	//transferGoogleSheetToCSV();
	$data = csv_to_array($csvDocLocation.$csv_file,$csv_desc);
	$data = GetAdrListKntkt ($data);
	//pre_r($data);

	// ===========================================
	// Defintions
	// ===========================================
		$fileoutput = "";
		$debugoutput = false;
		$UpdatePOIFile = true;


	// ===========================================
	// Paramters
	// ===========================================
	if ($_SERVER["REQUEST_METHOD"] == "POST") {
		$SetAll = false;
	} else {
		$SetAll = true;
	}
	if (isset($_POST["FeldNamenWahl"])) {
		$FeldNamenWahl = $_POST["FeldNamenWahl"];
	} else	{
		$FeldNamenWahl = '';
	}

	if (isset($_GET["EnableDelete"]) && strtolower((string)$_GET["EnableDelete"])=="on") {
		$EnableDelete= true;
	} else {
		$EnableDelete= false;
	}
	IF ($EnableDelete) {
		$EnableDeleteSet = "EnableDelete=on";
	} else {
		$EnableDeleteSet = "EnableDelete=off";
	}

	if (isset($_GET["DoSafe"]) && $_GET["DoSafe"]=="Y") {
		$dosafe= true;
	} else {
		$dosafe= false;
	}

	if (isset($_GET["delete"]) && isset($_GET["ID"]) ) { 
		if ($_GET["delete"]=="Y") {
			$dodelete= $_GET["ID"];
			$dosafe = true;
		} else {
			$dodelete= false;
		}
	} else {
		$dodelete= false;
	}

	if (isset($_GET["Sortieren"])) {
		$ColSort = $_GET["Sortieren"];
	} else {
		$ColSort = "JG";
	}

		
///////////////////////////////////////
// Process Input Data
///////////////////////////////////////
	if (
		($_SERVER["REQUEST_METHOD"] == "POST")
		) {
		// safe history
		//echo $file = $csvDocLocation . $RKRKContactFile;
		$Min =  intval(date("i")/10)*10;
		$newfile = $historyfilelocation . date("YmdH") . sprintf("%02d", $Min) . "_" . $RKRKContactFile;
		//echo "<br>History: " . $file . " to  " . $newfile;

		for ($i = 1; $i <= $_POST["NumEntries"]; $i++) {
			if (
				$_POST["Initialen-" . $i]!="" or
				//$_POST["Jagdgebiet-" . $i]!="" or
				$_POST["Name-" . $i]!=""
				) {
				//echo "ID: " . $_POST["ID-" . $i] . " - " . $_POST["Initialen-" . $i] . " - " . $_POST["Jagdgebiet-" . $i]. " - " . $_POST["RKRS-" . $i]  . " - " . $_POST["Typ-" . $i]. "<br>";
				if ($_POST["ID-" . $i]=="") {
					$IDField = GetNextID($data);
				} else { 
					$IDField = $_POST["ID-" . $i];
				}
				$previousAddress = isset($data[$IDField]["Adresse"]) ? $data[$IDField]["Adresse"] : "";
				// copy input data to internal array
				foreach ($TableSetup AS $TableEntry) {
					//echo $TableEntry["Field"] . " - " . $_POST[$TableEntry["Field"]."-" . $i] . "<br>"; 
					if ($TableEntry["Field"]=="ID") {
						$data[$IDField][$TableEntry["Field"]] =$IDField;
					} else {	
						$data[$IDField][$TableEntry["Field"]] =cleanString($_POST[$TableEntry["Field"]."-" . $i]);
					}
				}
				rkrk_adr_apply_geomaploc($data[$IDField], $previousAddress);
			}
		}	
		//pre_r($data["ADR0001"]);



		aasort($data, "Name");
		aasort($data, "JG");
		//aasort($data, "Typ");
		//pre_r($data);

		//echo "StoreLocation: " .$csvDocLocation . $csv_file . "<br>";
		$filepath= $csvDocLocation . $csv_file;
		
		$format = "UTF-8";
		//$format = "UTF-16LE"; //'UTF-16LE', 'UTF-8'
		$dosafe = true;
		$new= true;
		if ($new) {
			SaveAdressDataToCSV($data, $TableSetup, $filepath, $format, $dosafe, $dodelete, $csv_file, $csv_desc, true);
		} else {

			//new configuration, safe configuration file
			//echo "NumE: " . $_POST["NumEntries"] .  "<br>" . chr(10);
			//$dosafe= true;
			$i= 1;

			$filecontent = "";
			//$filecontent = 'ID;Initialen;JG;Jagdgebiet;Name;Kontaktnummer;P Telefon;Mail;Adresse;RKRS;Typ;Bemerkung' . chr(13) . chr(10);
			foreach ($TableSetup AS $TableEntry) {
				$filecontent .= $TableEntry["Field"] . ";";
			}
			$filecontent = substr($filecontent, 0, -1). chr(13) . chr(10); 

			foreach ($data as $dataline) {
			//ID;Initialen;JG;Jagdgebiet;Name;Kontaktnummer;P Telefon;Mail;Adresse;RKRS;Bemerkung
				//echo "ID: ". $dataline["ID"] . "<br>";
				if (
					$dataline["Initialen"] !=""
					//&& $dataline["Jagdgebiet"] !=""
					) {
					IF ($dodelete!= $dataline["ID"]) {
						// delete marked line
						
						//$rowcontent = chr(34);
						$rowcontent = "";
						//$rowcontent .= CheckForKomma($i) . $csv_desc;

						//collecting output data
						foreach ($TableSetup AS $TableEntry) {
							$rowcontent .= CheckForSemicolon($dataline[$TableEntry["Field"]]) . $csv_desc;
						}
						//cut last descriminator
						$rowcontent= substr($rowcontent, 0, -1);
						//$rowcontent .= chr(34). chr(13) . chr(10);
						$rowcontent .= chr(13) . chr(10);
						//echo $dataline["ID"] . " - " . $rowcontent . "<br>";
						$i++;
						$filecontent .= $rowcontent;
					}
				}
			}
			
			if (($dosafe)) {
				//echo "StoreLocation: " .$csvDocLocation . $csv_file . "<br>";
				$filepath= $csvDocLocation . $csv_file;

				// safe history
				$file = $filepath;
				//$newfile = $historyfilelocation . date("YmdH") . "00_" . $csv_file;
				//echo "<br>History: " . $file . " to  " . $newfile;
				if (!copy($file, $newfile)) {
					$SystemInfo .= "failed to copy $file...\n";
				}

				// safe file
				//echo "FilePath: " . $filepath . " - NumEntries:" . $i . "<br>";
				$df = fopen($filepath, 'w');
				fputs($df, $filecontent);
				fclose($df);
			} else {
				//echo "output: <br><br>" . $filecontent;

			}
		}
	}


	//read file (again after writing to file
	//transferGoogleSheetToCSV($csvDocLocation.$csv_file);
	//echo $csvDocLocation.$csv_file . "<br>";
	$data = csv_to_array($csvDocLocation.$csv_file,$csv_desc);
	$data = GetAdrListKntkt ($data, $ColSort);
	//pre_r($data);

		// ===========================================
		// Print Page content
		// ===========================================
		$output = "";
		
		// Printing all the keys and values one by one

// =====================
// Prepare Page output
///=====================


	//pre_r(array_keys($Feldkatalog));
	//control elements
	if (isset($_POST["AnzLinesPPage"])) {
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
	} else {
		$FontSize= "100%";
	}


	// ===========================================
	// FORM
	// ===========================================

    
//Initialen,JG,Jagdgebiet,Name,Kontaktnummer,P Telefon,Mail,Adresse,RKRS,Bemerkung
	$FontSizeInPoints = 11;
	if ($FontSize== "") {
		$FontSize= "100%";
	}
	$TableColor =  GetTableColor ("");
	AlternateLineTable($TableColor['even'], $FontSize, $FontSizeInPoints, $TableColor['odd']);
	SetTableWidth("100%");

	$i=1;
	$pageline = 1;

	$DataSetFound = false;

	echo '<form method="post" action="'. htmlspecialchars($_SERVER["PHP_SELF"]) . '">'. chr(10);
	echo '<div class="rkrk-adr-head">';
	echo '<div class="rkrk-adr-head-main">';
	echo '<div class="rkrk-adr-head-row" data-adr-filter="typ">';
	echo '<span class="rkrk-adr-head-label">Typ</span>';
	$EnabledOption = Array();
	$SearchOption = Array();
	$ListOfTyp="";
	$i=1;
	rkrk_echo_pills_open();
	foreach ($AddressTypeOptions as $Opt ) {
		$on = true;
		$EnabledOption[$Opt["Text"]] = 1;
		$SearchOption[] = $Opt["Text"];
		$ListOfTyp.= $Opt["Text"] .",";
		rkrk_echo_pill_checkbox("Opt-".$i, $Opt["Text"], $on);
		$i++;
	}
	rkrk_echo_pills_close();
	echo '</div></div>';
	echo '<div class="rkrk-adr-head-tools">';
	$ListOfTyp= substr($ListOfTyp,0,-1);
	if (!isset($RegionSelected) || !is_array($RegionSelected)) {
		$RegionSelected = array();
	}
	echo CreateSiteLink ("PdfAdressen", "Search=" . $FeldNamenWahl ."&TypFilter=" . $ListOfTyp . "&Sortieren=" . $ColSort);
	echo CreateSiteLink ("PdfAdressenDaten", "Search=" . $FeldNamenWahl ."&TypFilter=" . $ListOfTyp . "&RegFilter=" . implode(",", $RegionSelected) . "&Sortieren=" . $ColSort);
	if (GetAccess("InternPilots"))  {
		echo "<span id='exceldwnld'><a href='".$excel_csv_path ."' ><image src='" . $IconLoc . "/excel.png' height='" . $ButtonHeight. "'  title='Excel Adressliste herunterladen'></a></span> ";
	}
	if ($Layout!=2023) {
		echo CreateSiteLink ("Home", "");
		echo CreateSiteLink ("GoBack", "");
	}
	if (GetAccess("DeleteData")) {
		if ($EnableDelete) {
			echo "<input type='image' value='EnableDelete' src='" . $IconLoc . "/delete_on.png' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?"  . "ActPage=" . $ActPage . "&" . "Sortieren="  . $ColSort . "&" . $MissionSelectionParameter. "&EnableDelete=off' height='" . $ButtonHeight . "'  title='Delete Moduls ausschalten'  style='background-color:transparent; border-color:transparent;vertical-align: middle;'>" .chr(10);
		} else {
			echo "<input type='image' value='EnableDelete' src='" . $IconLoc . "/delete_off.png' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?"  . "ActPage=" . $ActPage . "&" . "Sortieren="  . $ColSort . "&" . $MissionSelectionParameter . "&EnableDelete=on' height='" . $ButtonHeight . "'title='Delete Moduls einschalten'  style='background-color:transparent; border-color:transparent;vertical-align: middle;'>" .chr(10);
		}
	}
	echo '</div></div>';
	//echo '		<button type="submit" >Adressen speichern</button>'. chr(10);
	//echo "<input type='submit' value='Speichern' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?" . $MissionSelectionParameter . "&" . $EnableDeleteSet . "'>" .chr(10);
	//echo "ActPage: " . $ActPage;

	//form to change setting
	$outputdata = Array();
	if ($fileloaded=true) {
		// ===========================================
		// Spezielle Filter
		// ===========================================
		//echo "FeldNamenWahl: " . $FeldNamenWahl . "<br>";
		$i=1;
		//pre_r($SearchOption);
		
		foreach ($data as $Key => $dataline) {
			$data[$Key]["ListNr"]= $i;
			$outputdata[$Key] = $dataline;
			$i++;
		}
		$MaxSelectedEntries = $i;
	}

	//save File or Exceloutput
	SaveAdressDataToCSV($outputdata, $TableSetup, $excel_csv_path, "UTF-16LE", true, false, $excel_csv_file, $csv_desc);


	//pre_r($data);
	//"MaxEntries: " . $MaxSelectedEntries . "<br>";
	$i=1;
	$MaxPages = ceil($MaxSelectedEntries/$AnzLinesPPage);
	$showfrom = ($ActPage-1)*$AnzLinesPPage;
	$showto = $showfrom + $AnzLinesPPage;
	$FontSizeInPoints = 11;
	if ($FontSize== "") {
		$FontSize= "100%";
	}

	foreach ($TableSetup as $fieldKey => $Feld) {
		$TableSetup[$fieldKey]["Show"] = true;
	}
	$adrUi = rkrk_adr_ui_load('kontakt');
	echo "<div class='rkrk-adr-grid-wrap'>" .chr(10);
	echo "<table class='rkrk-adr-grid' data-adr-grid='kontakt' data-ui='" . htmlspecialchars(json_encode($adrUi), ENT_QUOTES, 'UTF-8') . "'>" .chr(10);
	echo "<tr >" .chr(10);
	if ($EnableDelete) {
		echo "<th data-col='__del'></th>" .chr(10);
	}
	echo "<th data-col='__nr'>#</th>" .chr(10);
	foreach ($TableSetup AS $TableEntry) {
		if ($TableEntry["Show"]== true) {
			echo "<th class='rkrk-adr-sortable' data-col='" . htmlspecialchars($TableEntry["Field"], ENT_QUOTES, 'UTF-8') . "'>" . $TableEntry["Text"] . "</th>" .chr(10);

		}
	}
	echo "</tr >" .chr(10);
	
	foreach ($data as $dataline) {
		//pre_R($dataline);
		//echo "ID. " . $dataline["ID"] . " .<br>";
		if ($dataline["ListNr"]!=""){
		// Existing Data
			echo '<tr>' .chr(10);
			if ($EnableDelete) {
				$delOn = isset($_GET["EnableDelete"]) ? (string)$_GET["EnableDelete"] : "off";
				$dbwNow = isset($_GET["DBW"]) ? (string)$_GET["DBW"] : "";
				$delUrl = htmlspecialchars($_SERVER["PHP_SELF"]) . "?ActPage=" . $ActPage . "&Sortieren=" . urlencode($ColSort) . "&ID=" . urlencode($dataline['ID']) . "&delete=Y&EnableDelete=" . urlencode($delOn) . "&DBW=" . urlencode($dbwNow);
				echo '<td><a class="rkrk-adr-icon" href="' . $delUrl . '" title="Zeile löschen" onclick="return confirm(\'Diese Zeile löschen?\')">' . rkrk_adr_ico('trash') . '</a></td>' .chr(10);
			}
			

			echo '<td>' .chr(10);
			echo $i;
			echo '</td>' .chr(10);
			foreach ($TableSetup AS $TableEntry) {
				if ($TableEntry["Show"]== true) {
					// show entries
					if ($TableEntry["Type"]=="input") {
						echo '<td>' .chr(10);
						echo InputField($TableEntry["Field"]."-" . $i, $dataline[$TableEntry["Field"]], $TableEntry["Length"] , $TableEntry["Align"]);
						echo '</td>' .chr(10);
					}
					if ($TableEntry["Type"]=="Name") {
						echo '<td>' .chr(10);
						echo $dataline[$TableEntry["Field"]];
						echo InputField($TableEntry["Field"]."-" . $i, $dataline[$TableEntry["Field"]], $TableEntry["Length"] , $TableEntry["Align"], "hidden");
						echo '</td>' .chr(10);
					}
					if ($TableEntry["Type"]=="InputArea") {
						echo '<td>' .chr(10);
						echo InputTextarea($TableEntry["Field"]."-" . $i, $dataline[$TableEntry["Field"]], $TableEntry["Length"], 1, $TableEntry["Length"]);
						echo '</td>' .chr(10);
					}
					if ($TableEntry["Type"]=="select") {
						echo '<td>' .chr(10);
						echo SelectField($TableEntry["Field"]."-" . $i, strval($dataline[$TableEntry["Field"]]) ,$TableEntry["Options"] );
						echo "</td>" .chr(10);
					}
				} else {
					//hidden
					echo '<input type="hidden" name="'.$TableEntry['Field'] . '-' . $i . '" id="'. $TableEntry['Field'] . '-' . $i . '" value="'. $dataline[$TableEntry['Field']] . '" />'. chr(10);
				}
			}
			
			
		}
		$i++;
	}
	$lastIndex = $i - 1;
	echo "</table></div>" .chr(10);
	echo "<template id='rkrk-adr-new'><tr>";
	if ($EnableDelete) {
		echo "<td></td>";
	}
	echo "<td class='rkrk-adr-nr'>neu</td>";
	foreach ($TableSetup AS $TableEntry) {
		if ($TableEntry["Show"]== true) {
			if ($TableEntry["Type"]=="input" || $TableEntry["Type"]=="text" || $TableEntry["Type"]=="Name") {
				echo '<td>';
				echo InputField($TableEntry["Field"]."-__N__", "", $TableEntry["Length"] , $TableEntry["Align"]);
				echo '</td>';
			}
			if ($TableEntry["Type"]=="InputArea") {
				echo '<td>';
				echo InputTextarea($TableEntry["Field"]."-__N__", "", $TableEntry["Length"], 1, $TableEntry["Length"]);
				echo '</td>';
			}
			if ($TableEntry["Type"]=="select") {
				echo '<td>';
				echo SelectField($TableEntry["Field"]."-__N__", "" ,$TableEntry["Options"] );
				echo '</td>';
			}
		}
	}
	echo "</tr></template>" .chr(10);
	echo "<script src='scripts/rkrk_adr_grid.js?v=20260924live2'></script>" .chr(10);
	echo '<input type="hidden" name="NumEntries" id="NumEntries" value="'. $lastIndex. '" />'. chr(10);
	$saveUrl = htmlspecialchars($_SERVER["PHP_SELF"]) . "?ActPage=1&DoSafe=Y&" . $EnableDeleteSet;
	echo "<div class='rkrk-adr-actions'>";
	echo "<button type='submit' class='rkrk-adr-icon is-primary' title='Speichern' aria-label='Speichern' formaction='" . $saveUrl . "'>" . rkrk_adr_ico('save') . "</button>";
	echo "<button type='button' class='rkrk-adr-icon' id='rkrk-adr-add' title='Zeile hinzufügen' aria-label='Zeile hinzufügen'>" . rkrk_adr_ico('plus') . "</button>";
	echo "</div>";
	

	echo '</form>'. chr(10);
?>


</body>
</HTML>
