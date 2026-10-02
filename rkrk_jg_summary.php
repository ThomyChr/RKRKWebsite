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
	$csv_file_LWB = $RKRK_LWB_ContactFile;

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

	echo "<table style='width: 700; text-align: left; vertical-align: left;'>";
	foreach ($JaegerListe as $Person) {
		echo "<tr><td>";
		echo "Jäger";
		if ($IsCollection) {
			echo "</td><td>";
			echo "<b>" . $Person["JG"] . "</b>";
		}
		echo "</td><td>";
		echo "<b>" . $Person["Name"] . "</b>";
		echo "</td><td>";
		echo "<b>" . $Person["Kontaktnummer"] . "</b>";
		echo "</td></tr>";
	}
	foreach ($PilotenListe as $Person) {
		echo "<tr><td>";
		echo "Pilot";
		if ($IsCollection) {
			echo "</td><td>";
			echo "<b>" . $Person["Jagdgebiet"] . "</b>";
		}
		echo "</td><td>";
		echo "<b>" . $Person["Name"] . "</b>";
		echo "</td><td>";
		echo "<b>" . $Person["Kontaktnummer"] . "</b>";
		echo "</td></tr>";
	}
	echo "</table>";
	
	$Width="100%";
	$MapHeight="700";
	
	//multi color

	// alert off
	  $Alert = "Alert=off&";
	  $AlertMessage = "";
	  $InfoMessage = "";
	  $GreenMessage="";
	  $OrangeMessage="";

	
	//Nachrichtern:
	// 1. August bis 1. April
	if  (
			(($Today >= date("Y")."-07-15") and ($Today < date("Y")+1 ."-04-1")) 
			or
			(($Today >= date("Y")-1 ."-07-15") and ($Today < date("Y")."-04-1")) 
		){
	//if  (($Today >= "2025-07-15") and ($Today <= "2026-04-01")){
		$InfoMessage = "<br><br>Die Rehkitzrettungsaison geht von Ende April bis Mitte Juli, zur Zeit werden keine Suchflüge angeboten.Für Fragen stehen wir aber gerne zur Verfügung. -> <a href='mailto:thomas.christen@drohnenrehkitzrettung.ch?subject=Fragen zur Rehkitzrettung mit Drohnen'>Anfrage schicken</a><br>";
	}

	// 1. April bis 15. April
	if  (($Today >= date("Y")."-04-01") and ($Today < date("Y")."-04-20")) {
	//if  (($Today >= "2026-04-01") and ($Today <= "2026-04-20")){
		$InfoMessage = "<br><br><b>Bald geht es los!</b><br>Die Rehkitzrettungsaison geht von Ende April bis Mitte Juli. Für Fragen stehen wir aber gerne zur Verfügung. -> <a href='mailto:thomas.christen@drohnenrehkitzrettung.ch?subject=Fragen zur Rehkitzrettung mit Drohnen'>Anfrage schicken</a><br>";
	}
	// 17. Juni bis 25. Juni
	if  (($Today >= date("Y")."-06-17") and ($Today <= date("Y")."-06-25")) {
	//if  (($Today >= "2026-06-17") and ($Today <= "2026-06-25")){
		$OrangeMessage = "<br>Der grosse Schnitt vom 15. ist gemacht. <br><b>Für die bestehenden Felder lohnt sich eine Meldung immer noch</b>. Im letzten Jahr wurde uns im unserem Gebiet sogar am 15. Juli ein junges Rehkitz als vermäht gemeldet.<br>";
	}
	// 25. Juni bis 5. Juli
	if  (($Today >= date("Y")."-06-25") and ($Today < date("Y")."-07-15")) {
	//if  (($Today >= "2025-06-25") and ($Today <= "2025-07-15")){
		$InfoMessage = "<br><br><b>Herzlichen Dank</b><br>Die Rehkitzrettungssaison mit Drohnen ist in unseren Regionen grossmehrheitlich vorbei. Die jungen Rehkitze sind meist mobile und stark genug. Für die tolle Zusammenarbeit in der Saison ".date("Y").". Wir hoffen, dass wir einen guten Service liefern konnten. Rückmeldungen sind herzlich Willkommen. -> <a href='mailto:thomas.christen@drohnenrehkitzrettung.ch?subject=Rehkitzrettung mit Drohnen - Rückmeldung Saison ".date("Y")."'>Mail schicken</a><br>";
	}
	
	//Alerthandling:
		// Spezifisch Do 18. Juni 2026 bis Sa 20. Juni 2026
	if  ((($Today >= "2026-06-15") and ($Today <= "2026-06-22"))
		 //and (in_array($JG, array("21","24","25","26","27","28","32","AZ","136","143","148","145","ZG1","ZG2","ZG3","ZG4","ZG5","ZG6","LU","LME","SZ")))
		 and (ElementOfArrayInArray(explode(",",str_replace(" ", ",",$JG)), Array("21","24","25","26","27","28","32","AZ","136","143","148","145","ZG1","ZG2","ZG3","ZG4","ZG5","ZG6","LU","LME","SZ")))
		){
			if (false) {
			// alert on
				$Alert = "Alert=on&";
				//$AlertMessage = "<br>Aufgrund des internationalen Treffens auf dem Bürgenstock können Luftraumeinschränkungen <b>vom 18. Juni 2026 und 20. Juni 2026</b> im Umkreis von 25 NM/46.3km um den Bürgenstock angekündigt werden (Entscheid des Bundesrates vom 17. Juni 2026)</b>. Details über die konkrete Einschränkungen für den Drohnenflug werden in den <a href='https://www.skybriefing.com/de/dabs' target='_blank'>NOTAM</a> jeweils um 0900, 1300, 1600 Lokalzeit publiziert.<br>";
				$AlertMessage = "<br>Aufgrund des internationalen Treffens auf dem Bürgenstock können Luftraumeinschränkungen <b>vom 18. Juni 2026 und 20. Juni 2026</b> im Umkreis von 25 NM/46.3km um den Bürgenstock angekündigt werden (Gelber Kreis/Entscheid des Bundesrates vom 17. Juni 2026).<br>";
				//$GreenMessage= "Rehkitzrettung Schweiz hat mit den entsprechenden Kantonspolizeien abgesprochen, dass alle Drohnenflüge von Mitgliedern von Rehkitzrettung Schweiz, die zwischen 04:00 und eine halbe Stunde nach Sonnenaufgang stattfinden, ohne weitere Bewilligungen stattfinden können.<br></br><b>Falls ihr Flugbedürfnisse habt, bitte normal anmelden.</b></br><br>";
				$InfoMessage= "Das DABS 2026 JUN 20 Version 3 aktivert die Flugverbotszone ab 20.06.2026 08:00 - 22:00h CET. In dieser Zeit sind Drohnenflüge untersagt. Dies Betrifft den Einsatz der Rehktizrettung nicht. <br><b>Die Flüge können wie geplant durchgeführt werden.</b><br><i>TC 19.06.2026 16:30h</i><br>";				
			} else {
				$Alert = "";
				//$AlertMessage = "<br><br><b>Flugeinschränkung sind aufgehoben!</b><br>Wir danken für das Verständnis und entschuldigen uns für die Aufregung.</br>";
				$InfoMessage= "Im DABS 2026 JUN 22 Version 1 ist die Flugverbotszone 46.3km um den Bürgenstock aktiv, betrifft den Drohnenflug unsere Jagdgebiete jedoch nicht.<br><b>Die Flüge können wie geplant durchgeführt werden.</b><br><i>TC 21.06.2026 16:30h</i><br>";				
			}
		} 

	
	// Spezifisch 24. Mai 2024 bis 27. Mai 2024 oder 12. Juni 2024 bis 17. Juni 2024
	if  ((($Today >= "2024-05-24") and ($Today <= "2024-05-27") or
		($Today >= "2024-06-12") and ($Today <= "2024-06-17"))
		 //and (in_array($JG, array("25","26","27","28","136","143","148","145","ZG1","ZG2","ZG3","ZG4","ZG5","ZG6")))
		 and (ElementOfArrayInArray(explode(",",str_replace(" ", ",",$JG)), Array("25","26","27","28","136","143","148","145","ZG1","ZG2","ZG3","ZG4","ZG5","ZG6")))
		){
			if (false) {
			// alert on
			  $Alert = "Alert=on&";
			  $AlertMessage = "<br><br><b>Beachte:</b> Aufgrund der Friedenskonferenz auf dem Bürgenstock ist der Luftraum 15 NM/27.8km um den Bürgenstock <b>vom 13. Juni 2024 und 17. Juni 2024 für sämtliche zivilen Drohnenflüge voraussichtlich gesperrt</b>. Darum können während dieser Zeit keine Drohnenrehkitzrettungsflüge mit Drohnen angeboten werden. <br><br>
	<!--
				Verbindliche Sperrzeiten werden im <a href='https://www.skybriefing.com/de/dabs' target='_blank'>NOTAM</a>  jeweils bis 1600h am Vortag publiziert.<br><br>Wir danken für das Verständnis. Bis am 13. Juni und vom 18. Juni stehen wir gerne zur Verfügung.<a href='https://www.drohnenrehkitzrettung.ch/blog/flugeinschr%C3%A4nkung-w%C3%A4hrend-wichtige-information-friedenskonferenz-auf-dem-b%C3%BCrgenstock-nw' target='_blank'> ...mehr</a><br><br>
			  <b>AKTUELLE SPERRZEITEN<br>

			  - 13. Juni 2024 von 13:30h bis 17:00h,<br>
			  - 14. Juni 2024 von 12:00h bis 23:59h,<br>
			  - 15. Juni 2024 von 00:00h bis 23:59h<br>
			  - 16. Juni 2024 von 00:00h bis 23:59h<br>
			  - 17. Juni 2024 von 00:00h bis 20:00h<br></b><br><br>
	-->
				<b>Leider ist der Luftraum am 17. Juni erst wieder ab 20:00h.</b> Flüge können ab Dienstag 18. Juni 2024 weider wie gewohnt ausgeführt werden. Wir freuen uns auf den Normalbetrieb! <br><br>
			  Die Flüge der letzten Tage haben gezeigt, dass wir im Unterland immernoch Runde Geissen und frisch Kitze haben. Ein <b>Abfliegen der Felder lohnt sich </b> also immernoch.
			  
	<!--
			  an anderen Zeiten können Flüge in Absprache mit unserer Gruppe in Betracht gezogen werden.
	-->
				";
			} else {
				$Alert = "";
				$AlertMessage = "<br><br><b>Flugeinschränkung seit 17. Juni 00:00h aufgehoben!</b> <a href= 'https://www.bazl.admin.ch/bazl/de/home/infrastruktur/flugsicherung-und-luftraum/ukraine_conference_buergenstock.html'>...mehr</a> <br>Wir danken für das Verständnis und euren Einsatz über die letzten Tage. <br><br>Ab diesem Zeitpunkt können wir den Flubetrieb wieder wie gewohnt aufnehmen. Wir danken für die Frühzeitige Meldung der Felder, die am nächsten Tag zu fliegen sind.</br><br>
			  Die Flüge der letzten Tage haben gezeigt, dass wir im Unterland immernoch Runde Geissen und frisch Kitze haben. Ein <b>Abfliegen der Felder lohnt sich </b> also immernoch.
				<i>Eure Drohnenrehkitzrettungsteam</i>
				";
			}	
		} 
	if ( $AlertMessage!="") {
		echo "<p style='color:#D11111'>" . $AlertMessage . "</p>";
	}
	if ( $GreenMessage!="") {
		echo "<p style='color:#419043'>" . $GreenMessage . "</p>";
	}
	if ( $OrangeMessage!="") {
		echo "<p style='color:#FF7F50'>" . $OrangeMessage . "</p>";
	}
	if ( $InfoMessage!="") {
		echo "<p style='color:#527642'>" . $InfoMessage . "</p>";
	}
	//$MapSetting=$UrlOffset.'rkrk_map.php?Reg='.$Jagdgebiet.'&Strt=on&Cntr=off&Area=on&Pfad=on&EX=NO&Poi=on&KA='.$Karte.'&MAP=1&Zoom=0.6&MapZoomOut=1.2&MenuOn=off&GA=gast&Color=white';
	if ($UseSecUrl) {
		$MapSetting=$UrlOffset.'rkrk_map.php?'.base64_encode('Reg='.$Jagdgebiet.'&Strt=off&Cntr=off&Area=on&Pfad=off&SCOL=on&JGG=on&Label=on&JGLabel=off&'.$Alert.'KA='.$Karte.'&MAP=1&Zoom=0.9&MapZoomOut=1.2&MenuOn=off&MapButtons=off&Fit=on&GA=gast&Color=white');
	} else {
		$MapSetting=$UrlOffset.'rkrk_map.php?'.'Reg='.$Jagdgebiet.'&Strt=off&Cntr=off&Area=on&Pfad=off&SCOL=on&JGG=on&Label=on&JGLabel=off&'.$Alert.'KA='.$Karte.'&MAP=1&Zoom=0.9&MapZoomOut=1.2&MenuOn=off&MapButtons=off&Fit=on&GA=gast&Color=white';
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

	$FlightListSettingTodayAndTomorrow=$UrlOffset.'rkrk_flugliste.php?&Mode=J&Reg='.$Jagdgebiet.'&SelFrom='.$Today.'&SelTo='.$FormatedTomorrow.'&MenuOn=off&Color='.$BaseColor.'&GA=gast';
	$FlightListSettingTodayToDayAfterTomorrow=$UrlOffset.'rkrk_flugliste.php?&Mode=J&Reg='.$Jagdgebiet.'&SelFrom='.$Today.'&SelTo='.$DayAfterTomorrow.'&MenuOn=off&Color='.$BaseColor.'&GA=gast';
	//$FlightListSettingToday=$UrlOffset.'rkrk_flugliste.php?&Mode=J&Reg='.$Jagdgebiet.'&SelFrom='.$Today.'&SelTo='.$Today.'&MenuOn=off&Color=white&GA=gast';
	//$FlightListSettingTomorrow=$UrlOffset.'rkrk_flugliste.php?&Mode=J&Reg='.$Jagdgebiet.'&SelFrom='.$FormatedTomorrow.'&SelTo='.$FormatedTomorrow.'&MenuOn=off&Color=white&GA=gast';
	$FlightListHeight="1500";


	$TabKey1 = '"region"';
	$TabKey2 = '"today"';
	$TabKey3 = '"tomorrow"';
	$TabKey4 = '"dayaftertomorrow"';
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
	echo "</div>";

	echo "<div id='region' class='tabcontent' style='display:block'>";
	echo '<p><iframe width="'.$Width.'" height="'.$MapHeight.'" seamless frameborder="0" scrolling="no" src="'.htmlspecialchars($MapSetting, ENT_QUOTES, 'UTF-8').'"></iframe><br>'.$legendMap.'</p>'.chr(10);
	echo "</div>";

	$todayMap = $MapSetting . '&FDY=' . rawurlencode($Today);
	if ($TodaysFieldList !== '' && strcasecmp($TodaysFieldList, 'keine') !== 0) {
		$todayMap .= '&FFilter=' . rawurlencode($TodaysFieldList);
	}
	echo "<div id='today' class='tabcontent'>";
	echo '<p><iframe width="'.$Width.'" height="'.$MapHeight.'" seamless frameborder="0" scrolling="no" src="about:blank" data-map-src="'.htmlspecialchars($todayMap, ENT_QUOTES, 'UTF-8').'"></iframe><br>'.$legendMap.'</p>'.chr(10);
	echo "</div>";

	$tomorrowMap = $MapSetting . '&FDY=' . rawurlencode($Tomorrow);
	if ($TomorrowFieldList !== '' && strcasecmp($TomorrowFieldList, 'keine') !== 0) {
		$tomorrowMap .= '&FFilter=' . rawurlencode($TomorrowFieldList);
	}
	echo "<div id='tomorrow' class='tabcontent'>";
	echo '<p><iframe width="'.$Width.'" height="'.$MapHeight.'" seamless frameborder="0" scrolling="no" src="about:blank" data-map-src="'.htmlspecialchars($tomorrowMap, ENT_QUOTES, 'UTF-8').'"></iframe><br>'.$legendMap.'</p>'.chr(10);
	echo "</div>";

	$dayAfterMap = $MapSetting . '&FDY=' . rawurlencode($DayAfterTomorrow);
	if ($DayAfterTomorrowFieldList !== '' && strcasecmp($DayAfterTomorrowFieldList, 'keine') !== 0) {
		$dayAfterMap .= '&FFilter=' . rawurlencode($DayAfterTomorrowFieldList);
	}
	echo "<div id='dayaftertomorrow' class='tabcontent'>";
	echo '<p><iframe width="'.$Width.'" height="'.$MapHeight.'" seamless frameborder="0" scrolling="no" src="about:blank" data-map-src="'.htmlspecialchars($dayAfterMap, ENT_QUOTES, 'UTF-8').'"></iframe><br>'.$legendMap.'</p>'.chr(10);
	echo "</div>";
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
