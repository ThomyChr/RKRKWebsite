<style>
body {font-family: Arial;}

/* Style the tab */
.tab {
  overflow: hidden;
  border: 1px solid #ccc;
  background-color: #f1f1f1;
}

/* Style the buttons inside the tab */
.tab button {
  background-color: inherit !important;
  background-image: none !important;
  color: #111 !important;
  float: left;
  border: none !important;
  outline: none;
  cursor: pointer;
  padding: 10px 12px;
  transition: 0.3s;
  font-size: 17px;
  box-shadow: none !important;
}

/* Change background color of buttons on hover */
.tab button:hover {
  background-color: #ddd !important;
  color: #111 !important;
}

/* Create an active/current tablink class */
.tab button.active {
  background-color: #ccc !important;
  color: #111 !important;
}

/* Style the tab content */
.tabcontent {
  display: none;
  padding: 2px 8px;
  border: 1px solid #ccc;
  border-top: none;
}
</style>

<?php
	session_start();
	include_once('scripts/rkrk_gen.php');
	include_once('scripts/rkrk_csvfiles.php');
	include_once('scripts/rkrk_menu.php');
	include_once('rkrk_access_control.php');
	include_once('scripts/rkrk_jg_komm.php');
	include_once('scripts/rkrk_flugflaechen.php'); 

/*
	if (isset($_GET["Reg"])) {
		$JG = $_GET["Reg"];
	} else	{
		$JG = '';
	}
*/
	$JG=  GetURLwDefault("Reg", "Reg", "Reg",""); 
	//echo "JG: " . $JG . "<br>";
	$JGArray = explode(" ",$JG);
	//pre_R($JGArray);

	if (count(explode(",",str_replace(" ", ",",$JG)))>1)
		$IsCollection= true;
	else
		$IsCollection= false;

	headerpage("Zusammenfassung Jagdgebiet " . $JG, false);
	CheckIPandExit();
	GrantAccess();
	$MenuOn = GetURLtoFalseTrue('MenuOn', 'MenuOn', '', false);
	if (!$MenuOn) {
		$_GET['MenuOn'] = 'off';
	}
	if ($MenuOn) {
		CreateTopMenu();
	}
	$JgkPreview = rkrk_jg_komm_is_preview();
	if ($JgkPreview) {
		$JgkPreviewDate = (isset($_GET['PreviewDate']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$_GET['PreviewDate'])) ? (string)$_GET['PreviewDate'] : date('Y-m-d');
		echo "<div style='background:#f59e0b;color:#1a1a1a;padding:10px;font-weight:bold'>Interne Vorschau für " . htmlspecialchars($JgkPreviewDate, ENT_QUOTES, 'UTF-8') . " – nicht nach aussen sichtbar</div>";
	}
	echo "<style>
.tab button, .tab button:hover, .tab button.active, .tab button.tablinks {
  color: #111 !important;
  background-image: none !important;
  border: none !important;
  box-shadow: none !important;
}
.tab button { background-color: inherit !important; }
.tab button:hover { background-color: #ddd !important; }
.tab button.active { background-color: #ccc !important; }
</style>\n";

	// ===========================================
	// Gobal Defintions
	// ===========================================

	//$UrlOffset="https://tchr-frog.synology.me:5682/rkropen/pub/";
	//$UrlOffset="../pub/";
	$UrlOffset="";
	$Jagdgebiet=$JG;
	$Karte="M";
	$FormatedTomorrow = date("Y-m-d", strtotime("+1 day"));
  
	$Today = date("Y-m-d");
	$Tomorrow = date("Y-m-d", strtotime('tomorrow'));
	$DayAfterTomorrow = date("Y-m-d", strtotime('tomorrow + 1day'));
	$thisyear = date("Y");
	$thisday = date("d");
	$thismonth = date("m");
	$lastyear = $thisyear - 1;
// returns the year (four digits)
	
	$csv_desc = ';';
	$csv_file= $RKRKContactFile;
	//read file (again after writing to file
	//Adressen Piloten, Jäger
	//transferGoogleSheetToCSV($csvDocLocation.$csv_file);
	$data = csv_to_array($csvDocLocation.$csv_file,$csv_desc);
	if (!is_array($data)) {
		$data = array();
	}
	
	//sorting data
	aasort($data, "ID");
	aasort($data, "Jagdgebiet");
	aasort($data, "Name");
	aasort($data, "JG");
	//pre_r($data);

	foreach ($data as $dataline) {
	if ( (($dataline['Typ']=="Pilot")
		  or ($dataline['Typ']=="Pilot RKRK")
		  or ($dataline['Typ']=="Pilot Zug")
		  or ($dataline['Typ']=="Pilot Neu"))
		and ($dataline['Kontakt']=="Ja") 
		//((strpos(" " . $dataline['Jagdgebiet'], strval($JG)))))  
		and (ElementOfArrayInArray(explode(",",str_replace(" ", ",",$dataline['Jagdgebiet'])), explode(",",str_replace(" ", ",",$JG)))))
		{                                                       
				$PilotenListe[$dataline["Name"]]= $dataline;
		}			
	if ((($dataline['Typ']=="Jäger") or
		($dataline['Typ']=="Jäger Amt")) and
		($dataline['Kontakt']=="Ja") and
		//(strpos(" " . $dataline['JG'], strval($JG))))
		(ElementOfArrayInArray(explode(",",str_replace(" ", ",",$dataline['JG'])), explode(",",str_replace(" ", ",",$JG)))))
		{
				$JaegerListe[$dataline["Name"]]= $dataline;		
		}
	}
	
	
	//pre_R($PilotenListe);
	//pre_R($JaegerListe);

	//Laden Flugpläne
	$csv_file= $thisyear . $RKRKFlightsCSV;
	$csv_deliminator = ";";
	
	//Read CSV file
	transferGoogleSheetToCSV();
	//echo "Filename: " . $csvDocLocation.$csv_file . "<br>";
	$flights = csv_to_array($csvDocLocation.$csv_file, $csv_deliminator );
	//pre_R($data);
	$TodayFieldList = Array();
	$TodaysFieldList = Array();
	$TomorrowFieldList = Array();
	$DayAfterTomorrowFieldList = Array();
	$PilotenListe = isset($PilotenListe) && is_array($PilotenListe) ? $PilotenListe : Array();
	$JaegerListe = isset($JaegerListe) && is_array($JaegerListe) ? $JaegerListe : Array();
	//pre_r($flights);

	if (is_array($flights)) {
	foreach ($flights as $flight) {
		//Flugliste heute
		//echo $JG . " -  " . $flight["JG"] . "<br>";
		if (
			($flight['Datum']==$Today) and 
			 (
			  ($JG == '') or 
		      (in_array($flight["JG"],$JGArray))
			 )
			)
		{
			//pre_R($flight);
			$TodaysFieldList[] = $flight["Feld"];
		}

		//Flugliste morgen
		if (
			($flight['Datum']==$Tomorrow) and 
			 (
			  ($JG == '') or 
		      (in_array($flight["JG"],$JGArray))
			 )
			)
		{
			//pre_R($flight);
			$TomorrowFieldList[] = $flight["Feld"];
		}

		//Flugliste übermorgen
		if (
			($flight['Datum']==$DayAfterTomorrow) and 
			 (
			  ($JG == '') or 
		      (in_array($flight["JG"],$JGArray))
			 )
			)
		{
			//pre_R($flight);
			$DayAfterTomorrowFieldList[] = $flight["Feld"];
		}
	}
	}

	//pre_R($TodaysFieldList);
	//pre_R($TomorrowFieldList);
	//pre_R($DayAfterTomorrowFieldList);
	
	$TodaysFieldList = implode(' ', $TodaysFieldList);
	$TomorrowFieldList = implode(' ', $TomorrowFieldList);
	$DayAfterTomorrowFieldList = implode(' ', $DayAfterTomorrowFieldList);

	if ($TodaysFieldList=="")
		$TodaysFieldList="keine";
	if ($TomorrowFieldList=="")
		$TomorrowFieldList="keine";
	if ($DayAfterTomorrowFieldList=="")
		$DayAfterTomorrowFieldList="keine";
	//echo "TodaysFieldList: " . $TodaysFieldList . "<br>";
	//echo "TomorrowFieldList: " . $TomorrowFieldList . "<br>";
	//echo "DayAfterTomorrowFieldList: " . $DayAfterTomorrowFieldList . "<br>";

	//*******************************

	$JgkStore = rkrk_jg_komm_effective();
	$JgkOnDate = date('Y-m-d');
	if ($JgkPreview && isset($JgkPreviewDate)) {
		$JgkOnDate = $JgkPreviewDate;
	}
	$JgkRegs = array_values(array_filter(array_map('trim', explode(',', str_replace(' ', ',', (string)$JG)))));
	$JgkKey = rkrk_jg_komm_norm_reg($JG);
	$JgkArea = rkrk_jg_komm_area($JgkStore, $JgkKey);
	if (!is_array($JgkArea) && count($JgkRegs) === 1) {
		$JgkArea = rkrk_jg_komm_area($JgkStore, $JgkRegs[0]);
	}
	$JgkShowMap = true;
	$JgkShowPhones = true;
	$JgkShowGeflogen = true;
	$JgkSms = false;
	$JgkWa = false;
	if (is_array($JgkArea)) {
		$JgkPeopleRegs = count($JgkRegs) > 1 ? $JgkRegs : array($JgkKey);
		$JaegerListe = rkrk_jg_komm_rows_for_regs($data, $JgkStore, $JgkPeopleRegs, 'jaeger');
		$PilotenListe = rkrk_jg_komm_rows_for_regs($data, $JgkStore, $JgkPeopleRegs, 'pilot');
		$JgkShowMap = !isset($JgkArea['show_map']) || !empty($JgkArea['show_map']);
		$JgkShowPhones = !isset($JgkArea['show_phones']) || !empty($JgkArea['show_phones']);
		$JgkShowGeflogen = !isset($JgkArea['show_geflogen']) || !empty($JgkArea['show_geflogen']);
		$JgkSms = !empty($JgkArea['sms']);
		$JgkWa = !empty($JgkArea['whatsapp']);
		if (trim((string)($JgkArea['title'] ?? '')) !== '') {
			echo "<h1>" . htmlspecialchars((string)$JgkArea['title'], ENT_QUOTES, 'UTF-8') . "</h1>";
		}
		if (trim((string)($JgkArea['subtitle'] ?? '')) !== '') {
			echo "<p><b>" . htmlspecialchars((string)$JgkArea['subtitle'], ENT_QUOTES, 'UTF-8') . "</b></p>";
		}
		if (trim((string)($JgkArea['info'] ?? '')) !== '') {
			echo "<p>" . nl2br(htmlspecialchars((string)$JgkArea['info'], ENT_QUOTES, 'UTF-8')) . "</p>";
		}
	} else {
		$JgkPeopleRegs = count($JgkRegs) > 1 ? $JgkRegs : array($JgkKey);
		$JaegerListe = rkrk_jg_komm_apply_public_stv($JaegerListe, $data, $JgkStore, $JgkPeopleRegs, 'jaeger');
		$PilotenListe = rkrk_jg_komm_apply_public_stv($PilotenListe, $data, $JgkStore, $JgkPeopleRegs, 'pilot');
	}
	echo "<table style='width: 700; text-align: left; vertical-align: left;'>";
	foreach ($JaegerListe as $Person) {
		echo "<tr><td>";
		echo "Jäger";
		if ($IsCollection) {
			echo "</td><td>";
			$JgkJgCell = (string)($Person["JG"] ?? '');
			if (!empty($Person['stv'])) {
				$JgkJgCell = htmlspecialchars(rkrk_jg_komm_public_jg($Person), ENT_QUOTES, 'UTF-8');
			}
			echo "<b>" . $JgkJgCell . "</b>";
		}
		echo "</td><td>";
		echo "<b>" . $Person["Name"];
		if (!$IsCollection && !empty($Person['stv'])) {
			$JgkParen = htmlspecialchars(rkrk_jg_komm_public_jg($Person), ENT_QUOTES, 'UTF-8');
			if ($JgkParen !== '') {
				echo " " . $JgkParen;
			}
		}
		echo "</b>";
		echo "</td><td>";
		echo rkrk_jg_komm_person_links($Person, $JgkShowPhones, $JgkSms, $JgkWa);
		echo "</td></tr>";
	}
	foreach ($PilotenListe as $Person) {
		echo "<tr><td>";
		echo "Pilot";
		if ($IsCollection) {
			echo "</td><td>";
			$JgkJgCell = (string)($Person["Jagdgebiet"] ?? '');
			if (!empty($Person['stv'])) {
				$JgkJgCell = htmlspecialchars(rkrk_jg_komm_public_jg($Person), ENT_QUOTES, 'UTF-8');
			}
			echo "<b>" . $JgkJgCell . "</b>";
		}
		echo "</td><td>";
		echo "<b>" . $Person["Name"];
		if (!$IsCollection && !empty($Person['stv'])) {
			$JgkParen = htmlspecialchars(rkrk_jg_komm_public_jg($Person), ENT_QUOTES, 'UTF-8');
			if ($JgkParen !== '') {
				echo " " . $JgkParen;
			}
		}
		echo "</b>";
		echo "</td><td>";
		echo rkrk_jg_komm_person_links($Person, $JgkShowPhones, $JgkSms, $JgkWa);
		echo "</td></tr>";
	}
	echo "</table>";
	
	$Width="100%";
	$MapHeight="700";
	
	//multi color

	// Page checkboxes still use the layer window. A displayed notice draws its alert anyway.
	$Alert = "Alert=off&";
	$JgkAlertZones = array();
	if (is_array($JgkArea) && function_exists('rkrk_jg_komm_active_alert_zones')) {
		$JgkAlertZones = rkrk_jg_komm_active_alert_zones($JgkArea, $JgkOnDate);
		if (!is_array($JgkAlertZones)) {
			$JgkAlertZones = array();
		}
	}
	if (function_exists('rkrk_jg_komm_notice_alert_zones')) {
		$JgkNoticeZones = rkrk_jg_komm_notice_alert_zones(isset($JgkStore) ? $JgkStore : array(), $JgkKey, $JgkOnDate);
		if (is_array($JgkNoticeZones)) {
			$JgkAlertHave = array();
			foreach ($JgkAlertZones as $JgkAlertZone) {
				$JgkHaveName = is_array($JgkAlertZone) ? (string)($JgkAlertZone['name'] ?? '') : '';
				if ($JgkHaveName !== '') {
					$JgkAlertHave[$JgkHaveName] = true;
				}
			}
			foreach ($JgkNoticeZones as $JgkNoticeZone) {
				$JgkHaveName = is_array($JgkNoticeZone) ? (string)($JgkNoticeZone['name'] ?? '') : '';
				if ($JgkHaveName !== '' && !isset($JgkAlertHave[$JgkHaveName])) {
					$JgkAlertHave[$JgkHaveName] = true;
					$JgkAlertZones[] = $JgkNoticeZone;
				}
			}
		}
	}
	if ($JgkAlertZones !== array()) {
		$JgkAlertNames = array();
		foreach ($JgkAlertZones as $JgkAlertZone) {
			$JgkAlertName = is_array($JgkAlertZone) ? (string)($JgkAlertZone['name'] ?? '') : '';
			if ($JgkAlertName !== '') {
				$JgkAlertNames[] = $JgkAlertName;
			}
		}
		if ($JgkAlertNames !== array()) {
			$Alert = 'Alert=on&AlertKml=' . implode(',', $JgkAlertNames) . '&Stichtag=' . $JgkOnDate . '&AlertDate=' . $JgkOnDate . '&';
		}
	}

	$JgkSeen = array();
	foreach ((isset($JgkStore['notices']) && is_array($JgkStore['notices'])) ? $JgkStore['notices'] : array() as $JgkNotice) {
		if (is_array($JgkNotice) && !rkrk_jg_komm_notice_enabled($JgkNotice)) {
			continue;
		}
		if (!rkrk_jg_komm_notice_matches($JgkNotice, $JgkKey, $JgkOnDate)) {
			continue;
		}
		$JgkId = (string)($JgkNotice['id'] ?? md5((string)($JgkNotice['text'] ?? '')));
		if (isset($JgkSeen[$JgkId])) {
			continue;
		}
		$JgkSeen[$JgkId] = true;
		echo rkrk_jg_komm_notice_html($JgkNotice);
	}
	//$MapSetting=$UrlOffset.'rkrk_map.php?Reg='.$Jagdgebiet.'&Strt=on&Cntr=off&Area=on&Pfad=on&EX=NO&Poi=on&KA='.$Karte.'&MAP=1&Zoom=0.6&MapZoomOut=1.2&MenuOn=off&GA=gast&Color=white';
	$MapQuery = 'Reg='.$Jagdgebiet.'&Strt=off&Cntr=off&Area=on&Pfad=off&SCOL=on&JGG=on&Label=on&JGLabel=off&'.$Alert.'KA='.$Karte.'&MAP=1&Zoom=0.9&MapZoomOut=1.2&MenuOn=off&MapButtons=off&Fit=on&GA=gast&Color=white';
	if ($UseSecUrl) {
		$MapSetting=$UrlOffset.'rkrk_map.php?'.base64_encode($MapQuery);
	} else {
		$MapSetting=$UrlOffset.'rkrk_map.php?'.$MapQuery;
	}
	
	$ListSetting=$UrlOffset.'rkrk_felderliste.php?Reg='.$Jagdgebiet.'&MenuOn=off&GA=gast&Color=white';
	$ListHeight="1500";
	
	//https://tchr-frog.synology.me:5682/rkropen/test/rkrk_flugstatistik.php?RegList=28&Color=white&Charts=Top&GA=gast;
	$StatSettingThisYear=$UrlOffset.'rkrk_flugstatistik.php?RegList='.$Jagdgebiet.'&Jahr='.$thisyear.'&Color='.$BaseColor.'&Charts=Top,Entwicklung1,Entwicklung2,Kumulativ&ChartWidth=480&GA=gast&MenuOn=off';
	//https://tchr-frog.synology.me:5682/rkropen/test/rkrk_flugstatistik.php?RegList=28&Jahr=2023&Color=white&Charts=Top&GA=gast;
	//https://tchr-frog.synology.me:5682/rkropen/test/rkrk_flugstatistik.php?RegList=28&Jahr=2023&Color=white&Charts=Top,Entwicklung1,Entwicklung2&GA=gast;
	$StatSettingLastYear=$UrlOffset.'rkrk_flugstatistik.php?RegList='.$Jagdgebiet.'&Jahr='.$lastyear.'&Color='.$BaseColor.'&Charts=Top,Entwicklung1,Entwicklung2,Kumulativ&ChartWidth=480&GA=gast&MenuOn=off';
	$StatHeight="1300";
	//$StatHeight="5000";		

	$FlightListSettingTodayAndTomorrow=$UrlOffset.'rkrk_flugliste.php?&Mode=J&Reg='.$Jagdgebiet.'&SelFrom='.$Today.'&SelTo='.$FormatedTomorrow.'&MenuOn=off&Color='.$BaseColor.'&GA=gast&HideZeit=1';
	$FlightListSettingTodayToDayAfterTomorrow=$UrlOffset.'rkrk_flugliste.php?&Mode=J&Reg='.$Jagdgebiet.'&SelFrom='.$Today.'&SelTo='.$DayAfterTomorrow.'&MenuOn=off&Color='.$BaseColor.'&GA=gast&HideZeit=1';
	//$FlightListSettingToday=$UrlOffset.'rkrk_flugliste.php?&Mode=J&Reg='.$Jagdgebiet.'&SelFrom='.$Today.'&SelTo='.$Today.'&MenuOn=off&Color=white&GA=gast';
	//$FlightListSettingTomorrow=$UrlOffset.'rkrk_flugliste.php?&Mode=J&Reg='.$Jagdgebiet.'&SelFrom='.$FormatedTomorrow.'&SelTo='.$FormatedTomorrow.'&MenuOn=off&Color=white&GA=gast';
	$FlightListHeight="1500";


	$TabKey1 = '"region"';
	$TabKey2 = '"today"';
	$TabKey3 = '"tomorrow"';
	$TabKey4 = '"dayaftertomorrow"';
	$TabKey5 = '"geflogen"';
	$SeasonFeldIds = array();
	$SeasonFlightCount = 0;
	$SeasonFromLabel = '';
	$SeasonToLabel = '';
	if ($JgkShowGeflogen) {
		$SeasonYear = date('Y', strtotime($Today));
		$SeasonFrom = GetSasionStartDate($SeasonYear);
		$SeasonTo = GetSasionEndDate($SeasonYear);
		$SeasonFromLabel = date('d.m.Y', strtotime($SeasonFrom));
		$SeasonToLabel = date('d.m.Y', strtotime($SeasonTo));
		$SeasonRegs = rkrk_flugflaechen_flightlog_regions($JG);
		$SeasonFlights = rkrk_flugflaechen_load_flights($SeasonFrom, $SeasonTo);
		$SeasonFiltered = rkrk_flugflaechen_filter_flightlog($SeasonFlights, $SeasonFrom, $SeasonTo, $SeasonRegs);
		$SeasonDrawn = rkrk_flugflaechen_drawn_stats($SeasonFiltered);
		$SeasonFeldIds = (isset($SeasonDrawn['feldIds']) && is_array($SeasonDrawn['feldIds'])) ? $SeasonDrawn['feldIds'] : array();
		$SeasonFlightCount = (int)($SeasonDrawn['flights'] ?? 0);
		$GeflogenQuery = $MapQuery . '&AreaOnly=on&AreaIds=' . rawurlencode(implode(' ', $SeasonFeldIds));
		if ($UseSecUrl) {
			$GeflogenMapSetting = $UrlOffset.'rkrk_map.php?'.base64_encode($GeflogenQuery);
		} else {
			$GeflogenMapSetting = $UrlOffset.'rkrk_map.php?'.$GeflogenQuery;
		}
	}
	$ColorJG = "#FF9033";
	$ColorFeld = "#67b516";
	$legendMap = 'Legende: <span style="color: '.$ColorFeld.'">Flugfeld <span><span style="color: '.$ColorJG.'">Jagdbebietsgrenze <span>';

	echo "<br>";
	echo "<br>";

	echo "<div class='tab'>";
	echo "  <button class='tablinks' onclick='openCity(event, ".$TabKey1.")'>Jagdgebiet</button>";
	if ($TodaysFieldList!="keine")
		echo "  <button class='tablinks' onclick='openCity(event, ".$TabKey2.")'>Flüge heute</button>";
	if ($TomorrowFieldList!="keine")
		echo "  <button class='tablinks' onclick='openCity(event, ".$TabKey3.")'>Flüge morgen</button>";
	if ($DayAfterTomorrowFieldList!="keine")
		echo "  <button class='tablinks' onclick='openCity(event, ".$TabKey4.")'>Flüge übermorgen</button>";
	if ($JgkShowGeflogen)
		echo "  <button class='tablinks' onclick='openCity(event, ".$TabKey5.")'>Geflogene Felder</button>";
	echo "</div>";

	echo "<div id='region' class='tabcontent' style='display:block'>";
	if ($JgkShowMap) {
		echo '<p><iframe width="'.$Width.'" height="'.$MapHeight.'" seamless frameborder="0" scrolling="no" src="'.htmlspecialchars($MapSetting, ENT_QUOTES, 'UTF-8').'"></iframe><br>'.$legendMap.'</p>'.chr(10);
	}
	echo "</div>";

	$todayMap = $MapSetting . '&FDY=' . rawurlencode($Today);
	if ($TodaysFieldList !== '' && strcasecmp($TodaysFieldList, 'keine') !== 0) {
		$todayMap .= '&FFilter=' . rawurlencode($TodaysFieldList);
	}
	echo "<div id='today' class='tabcontent'>";
	if ($JgkShowMap) {
		echo '<p><iframe width="'.$Width.'" height="'.$MapHeight.'" seamless frameborder="0" scrolling="no" src="about:blank" data-map-src="'.htmlspecialchars($todayMap, ENT_QUOTES, 'UTF-8').'"></iframe><br>'.$legendMap.'</p>'.chr(10);
	}
	echo "</div>";

	$tomorrowMap = $MapSetting . '&FDY=' . rawurlencode($Tomorrow);
	if ($TomorrowFieldList !== '' && strcasecmp($TomorrowFieldList, 'keine') !== 0) {
		$tomorrowMap .= '&FFilter=' . rawurlencode($TomorrowFieldList);
	}
	echo "<div id='tomorrow' class='tabcontent'>";
	if ($JgkShowMap) {
		echo '<p><iframe width="'.$Width.'" height="'.$MapHeight.'" seamless frameborder="0" scrolling="no" src="about:blank" data-map-src="'.htmlspecialchars($tomorrowMap, ENT_QUOTES, 'UTF-8').'"></iframe><br>'.$legendMap.'</p>'.chr(10);
	}
	echo "</div>";

	$dayAfterMap = $MapSetting . '&FDY=' . rawurlencode($DayAfterTomorrow);
	if ($DayAfterTomorrowFieldList !== '' && strcasecmp($DayAfterTomorrowFieldList, 'keine') !== 0) {
		$dayAfterMap .= '&FFilter=' . rawurlencode($DayAfterTomorrowFieldList);
	}
	echo "<div id='dayaftertomorrow' class='tabcontent'>";
	if ($JgkShowMap) {
		echo '<p><iframe width="'.$Width.'" height="'.$MapHeight.'" seamless frameborder="0" scrolling="no" src="about:blank" data-map-src="'.htmlspecialchars($dayAfterMap, ENT_QUOTES, 'UTF-8').'"></iframe><br>'.$legendMap.'</p>'.chr(10);
	}
	echo "</div>";
	if ($JgkShowGeflogen) {
		echo "<div id='geflogen' class='tabcontent'>";
		echo "<p>Geflogene Felder dieser Saison (" . htmlspecialchars($SeasonFromLabel, ENT_QUOTES, 'UTF-8') . " bis " . htmlspecialchars($SeasonToLabel, ENT_QUOTES, 'UTF-8') . "), Geflogen = Ja.";
		if (count($SeasonFeldIds) === 0) {
			echo " In diesem Jagdgebiet wurden in dieser Saison noch keine Felder geflogen.</p>";
		} else {
			echo " " . count($SeasonFeldIds) . " Felder, " . (int)$SeasonFlightCount . " Flüge.</p>";
		}
		if ($JgkShowMap) {
			echo '<p><iframe width="'.$Width.'" height="'.$MapHeight.'" seamless frameborder="0" scrolling="no" src="about:blank" data-map-src="'.htmlspecialchars($GeflogenMapSetting, ENT_QUOTES, 'UTF-8').'"></iframe><br>'.$legendMap.'</p>'.chr(10);
		}
		echo "</div>";
	}
	echo "<br>";
	

	echo "<table style='text-align: center; vertical-align: middle;'><tr><td>";
	//Personal page disbled - 16.05.2024
	//echo CreateSiteLink ("Pers", "");
	//echo "<br>Persönliche Einsatzseite für Jäger";
	//echo "</td><td>";
	echo CreateSiteLink ("Flugliste", "Reg=".$JG."&Color=white&MenuOn=off" );
	echo "<br>Flugliste der Region";
	echo "</td><td>";
	echo CreateSiteLink ("FeldListe", "Reg=".$JG."&Color=white" );
	echo "<br>Felderliste der Region";
	echo "</td></tr></table>";
	if (!$IsCollection) {
		echo "<b>Flugbestellungen:</b> <ul><li>Für existierende Felder reicht eine Textnachricht an den verantwortlichen Piloten mit der erwarteten Mähzeit.</li><li>Für nicht existierende Felder kann ein Bild (Screenshot) mit dem eingezeichneten Feld an den Piloten oder eine Meldung über den UAV Editor der Rehkitzrettung Schweiz gemacht werden. Hier bitte den Piloten oder unsere Gruppe angeben.</li><li>In unseren Wetterentwicklungen fliegen wir lieber einmal mehr.</li></ul><br>";
	}
	
	if ((($thismonth>3) and ($thisday>21)) or ($thismonth>4)) {
		echo "  <h2>Flugstatistik ". $thisyear. "</h2>";
		// Flugstatisik (Aktuelles Jahr)
		//document.write('<embed type="text/html" src="'+vStatSettingThisYear+'"  width="'+vWidth+'" height="'+vStatHeight+'">');
		echo '<p><iframe width="'.$Width.'" height="'.$StatHeight.'" seamless frameborder="0" scrolling="no" src="'.$StatSettingThisYear.'"></iframe></p>'.chr(10);
	} elseif ($JG!=32) {
		echo "  <h2>Flugstatistik ". $lastyear  ."</h2>";
		// Flugstatisik (Letztes Jahr)
		//document.write('<embed type="text/html" src="'+vStatSettingThisYear+'"  width="'+vWidth+'" height="'+vStatHeight+'">');
		echo '<p><iframe width="'.$Width.'" height="'.$StatHeight.'" seamless frameborder="0" scrolling="no" src="'.$StatSettingLastYear.'"></iframe></p>'.chr(10);
	}
	
	echo "  <h2>Fluglisten</h2>";
	// Flugliste
	//document.write('<embed type="text/html" src="'+vFlightListSettingTomorrow+'" width="'+vWidth+'" height="'+vFlightListHeight+'">');
	//echo $FlightListSettingTodayAndTomorrow;
	//echo '<iframe width="'.$Width.'" height="'.$FlightListHeight.'" seamless frameborder="0" scrolling="no" src="'.$FlightListSettingTodayAndTomorrow.'"></iframe>'.chr(10);
	echo '<iframe width="'.$Width.'" height="'.$FlightListHeight.'" seamless frameborder="0" scrolling="no" src="'.$FlightListSettingTodayToDayAfterTomorrow.'"></iframe>'.chr(10);



	// Flugliste
	//document.write('<embed type="text/html" src="'+vFlightListSettingTomorrow+'" width="'+vWidth+'" height="'+vFlightListHeight+'">');
	//echo '<p><iframe width="'.$Width.'" height="'.$FlightListHeight.'" seamless frameborder="0" scrolling="no" src="'.$FlightListSettingToday.'"></iframe></p>'.chr(10);

	// Flugliste
	//document.write('<embed type="text/html" src="'+vFlightListSettingTomorrow+'" width="'+vWidth+'" height="'+vFlightListHeight+'">');
	//echo '<p><iframe width="'.$Width.'" height="'.$FlightListHeight.'" seamless frameborder="0" scrolling="no" src="'.$FlightListSettingTomorrow.'"></iframe></p>'.chr(10);
?>

<script>
function openCity(evt, cityName) {
  var i, tabcontent, tablinks;
  tabcontent = document.getElementsByClassName("tabcontent");
  for (i = 0; i < tabcontent.length; i++) {
    tabcontent[i].style.display = "none";
  }
  tablinks = document.getElementsByClassName("tablinks");
  for (i = 0; i < tablinks.length; i++) {
    tablinks[i].className = tablinks[i].className.replace(" active", "");
  }
  var pane = document.getElementById(cityName);
  pane.style.display = "block";
  evt.currentTarget.className += " active";
  var iframes = pane.getElementsByTagName("iframe");
  for (i = 0; i < iframes.length; i++) {
    var f = iframes[i];
    var pending = f.getAttribute("data-map-src");
    if (pending && (!f.getAttribute("src") || f.getAttribute("src") === "about:blank")) {
      f.src = pending;
    } else {
      try {
        if (f.contentWindow && typeof f.contentWindow.rkrkUpdateMapSize === "function") {
          f.contentWindow.rkrkUpdateMapSize(true);
        }
      } catch (e) {}
    }
  }
}
</script>
