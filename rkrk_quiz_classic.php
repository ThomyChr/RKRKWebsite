<?php
	session_start();
	include_once('scripts/rkrk_gen.php');
	include_once('scripts/rkrk_inputforms.php');
	include_once('scripts/rkrk_csvfiles.php');
	
	headerpage("Rehkitzrettungs-Quiz", false);
	
	//check impropriate IP addresses accessing page
	CheckIPandExit();

?>	
<!-- styles -->
    <style>
        //table, th, td {
        	//  border: 1px solid black;
        	//  border-collapse: collapse;
        	//}
        
        	td.left	{
        		text-align: left;
        		align: left;
        	}
        
        	td.center {
        		text-align: center;
        		align: center;
        	}
        
        	td.right {
        		text-align: right;
        		align: right;
        	}
        
			table {
				max-width: 800px;
				min-width: 600px;
			}

			section { 
				margin-bottom: 2em; 
				background: #fff; 
				padding: 1em; 
				border-radius: 8px; 
				box-shadow: 0 0 5px rgba(0,0,0,0.1); 
			}

        
            .key {
              background: #f0f8ff;
              padding: 4px 8px;
              display: inline-block;
              margin: 5px 0;
            }
        
           .keywords {
              font-style: italic;
              color: #666;
              margin-bottom: 1rem;
            }
            .highlight {
              background-color: #e6f7ff;
              border-left: 4px solid #1890ff;
              padding: 0.5rem 1rem;
              margin: 1rem 0;
            }
            ul.checklist {
              list-style: none;
              padding-left: 0;
            }
            ul.checklist li::before {
              content: "✅ ";
              color: green;
            }
            section {
              margin-bottom: 3rem;
            }	
            H1,H2,H3 {
              margin-top: 3rem;
              margin-bottom: 2rem;
            }	
        
			img {
        		border-radius : 5px 15% 10px;	
			}
			
        	.lernimagelarge {
        		//align:center;
        		height:500px;
        		//box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
        		//boder-radius: 10px;
        	}
        
        	.lernimage {
        		//align:center;
        		height:300px;
        		//box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
        		//boder-radius: 10px;
        	}
        
        	.lernimagesmall {
        		//align:center;
        		height:200px;
        		//box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
        		//boder-radius: 10px;
        	}
        
        	.lernimageverysmall {
        		//align:center;
        		height:100px;
        		//box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
        		//boder-radius: 10px;
        	}
        
        	.lernimagefixwidth {
        		//align:center;
        		width:600px;
        		//box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
        		//boder-radius: 10px;
        	}
        
        	.lerncaption {
        		//align: middle;
        		font-style: italic;
        	}
        
        	a.referenz{
        		color: #666;;
        		font-style: italic;
        	}
        	
        	.referenz::before{
        		content: "🔗 ";
        	}
    </style>
<!-- TIMER balken -->
	<style>
	#timer-container,#quiztimer-container  {
		width: 100%;
		height: 20px;
		background: #ddd;
		border-radius: 4px;
		overflow: hidden;
	  }

	#timer-bar,#quiztimer-bar  {
		width: 100%;
		height: 100%;
		background: #00c853;
		position: relative;
		display: flex;
		align-items: center;
		justify-content: center;
		color: white;              /* Textfarbe */
		font-weight: bold;
		font-size: 14px;
		text-shadow: 1px 1px 2px black; /* bessere Lesbarkeit */
	}

	#timer-text,#quiztimer-text {
		position: absolute;
		z-index: 2;                /* Text über der Farbe */
		pointer-events: none;      /* Klicks gehen durch */
	}
	</style>

<?php	
	$urladder = "";
	if (isset($_GET["TEST"])) {
		echo "<body>";
		$urladder = "&TEST";
	} else	{
		echo "<body oncontextmenu='return false;'>";
	}	

	if (isset($_GET["quiz"])) {
		$set_quiz = $_GET["quiz"];
	} elseif (isset($_POST["quiz"])) {
		$set_quiz = $_POST["quiz"];
	} else	{
		$set_quiz = "";
	}	

	if (isset($_GET["mode"])) {
		$mode = $_GET["mode"];
	} else	{
		$mode = "";
	}	

	if (isset($_GET["sortiert"])) {
		$sorted = true;
	} else	{
		$sorted = false;
	}	

	if (isset($_POST["AnzFragen"])) {
		$AnzFragen = $_POST["AnzFragen"];
	} else	{
		$AnzFragen = 0;
	}	

	if (isset($_POST["RichtigBeantwortet"])) {
		$FragenRichtig = $_POST["RichtigBeantwortet"];
	} else	{
		$FragenRichtig = 0;
	}	

	if (isset($_POST["Erfuellt"])) {
		$Erfuellt = $_POST["Erfuellt"];
	} else	{
		$Erfuellt = 0;
	}	

	if (isset($_GET["Status"])) {
		$Status = $_GET["Status"];
	}	
	elseif (isset($_POST["Status"])) {
		$Status = $_POST["Status"];
	}	

	if (isset($_GET["Schulung"])) {
		$Schulung = "Schulung";
		if ($urladder<>"") {
			$urladder = $urladder . "&Schulung";
		} else {
			$urladder = "Schulung";
		}
	}	
	elseif (isset($_POST["Schulung"])) {
		$Schulung = $_POST["Schulung"];
	} else {
		$Schulung = "";
	}

	if (isset($_POST["Name"])) {
		$Name = $_POST["Name"];
	} else	{
		$Name = "";
	}	

	if (isset($_POST["Quiz"])) {
		$Quiz = $_POST["Quiz"];
	} else	{
		$Quiz = "";
	}	

	if (isset($_POST["Drone"])) {
		$Drone = $_POST["Drone"];
	} elseif (isset($_GET["Drone"])) {
		$Drone = $_GET["Drone"];
	} else {
		$Drone = "";   // noch nichts gewählt
	}

	//echo "AnzFragen: " . $AnzFragen . " Richtig: " . $FragenRichtig . " Status: " . $Status . " Quiz: " . $Quiz . "<br>";
	if ($Status=="resultat") {
		$QuizLogFile='QuizLog.csv';
		//echo "Resultat anzeigen und speichern";
		if (file_exists($csvMissionLocation.$QuizLogFile)) {
			$data = csv_to_array_ascII($csvMissionLocation.$QuizLogFile, ';');
			$i= count($data);
		} else {
			$i=0;
		}
		foreach($data as $line)
		{
			if($line["Zeit"] == '')
			{
				unset($line);
			}
		}		
		//pre_r($data);
		$data[$i]["Name"]=$Name;
		$data[$i]["Quiz"]=$set_quiz;
		$data[$i]["Zeit"]=date("d.m.Y h:m");
		$data[$i]["AnzFragen"]=$AnzFragen;
		$data[$i]["Richtig"]=$FragenRichtig;
		$data[$i]["Erfuellungsfaktor"]=$Erfuellt;
		if (($FragenRichtig/$AnzFragen)>=Erfuellt) {
			// Erfüllt
			$data[$i]["Erfuellung"]="Ja";
		} else {
			// Nicht Erfüllt
			$data[$i]["Erfuellung"]="Nein";
		}
		//pre_r($data);
		SaveArrayToCSV ($data, $QuizLogFile);
	}	

	// ===========================================
	// Definitionen
	// ===========================================
	
	//enable/disable tables
	$logo = "rkrk";
	$copyright = "drohnenrehkitzrettung.ch";
	$setting= "PUB";
	
	// TO CHANGE
	$CalcIconLoc = $ImageLoc ."calc/";
	$QuizLoc = "quiz/";
	$QuizIconLoc = "quiz/images/";
	
	if (isset($_GET["TEST"])) {
		$logo = "rkrk";
		$copyright = "drohnenrehkitzrettung.ch";
		$setting = "TEST";
	}

	if (isset($_GET["RKRK"])) {
		$logo = "rkrk";
		$copyright = "drohnenrehkitzrettung.ch";
		$setting = "RKRK";
	}

	if (isset($_GET["RKRS"])) {
		$logo = "rkrs";
		$copyright = "rehkitzrettung.ch";
		$setting = "RKRS";
	}

	if (isset($_GET["RKRSplus"])) {
		$logo = "rkrs";
		$copyright = "rehkitzrettung.ch";
		$setting = "RKRSplus";
	}
	if (isset($_GET["RKRSprev"])) {
		$logo = "rkrs";
		$copyright = "rehkitzrettung.ch";
		$setting = "RKRSprev";
	}

	$PicutreStyle ="border-radius: 20px;  border: none; object-fit: cover";
	$ButtonStyle ="border-radius: 3px;  border: none;  color: white;  background: #98B489; padding: 10px;  text-align: center;  text-decoration: none;  display: inline-block;  font-size: 16px;  margin: 4px 2px;  cursor: pointer";
	$ButtonStyleRed ="border-radius: 3px;  border: none;  color: white;  background: #b30000; padding: 10px;  text-align: center;";  
	$ButtonStyleGrey ="border-radius: 3px;  border: none;  color: white;  background: #E8E8E8; padding: 10px;  text-align: center;";  
	
	$PicWidth = '600';
	$PicHeight = '400';
	$PicHeightSmall = '200';
	$ColWidth=100;

	// ========================================
	// Read csv data
	// ========================================
	
	//pre_R($QuizSelection);
	$csv_desc= ";";
	$QuizList = csv_to_array_ascII($QuizLoc."QuizListe.csv",$csv_desc);

	//pre_R($QuizList);

	//-----------------------------------------
	//Fragenauswahl
	//-----------------------------------------
	if ($QuizList[$set_quiz]["Locked"]=="Ja") {
		//quiz nicht zugreifbar
		$set_quiz="";
	}	
	//echo "QuizSet: " . $set_quiz . "<br>";

	$i=0;
	//$QuizSelection = Array();
	//pre_R($QuizList);
	foreach($QuizList as $key => $ql) {
		//pre_r($ql);
		//echo "Set: " . $setting . " Aud: " . $ql["Audienz"] . "<br>";
		//echo $i . " Quiz: " . $key . " " . $ql["Key"] . "<br>";
		if (
			(
				($ql["Aktiv"]=="Ja") 
			and ($ql["Locked"]=="Nein")
			or (strpos($ql["Audienz"], $setting) !== false)
				or ($ql["Audienz"]=="Alle") 
				or ($setting == "TEST")
				) 
			) 
			{
			if (($set_quiz==$ql["Key"]) or (($set_quiz=="") and ($ql["Key"]="RKR"))) {
				$Questionair = $QuizList[$key];
				$QSelection = csv_to_array_ascII($QuizLoc.$ql["FragenDatei"],$csv_desc);
				//pre_r($QSelection);
				foreach ($QSelection as $key => $Q) {
					$QSelection [$key]["Loesungsauswahl"] = explode("|", $Q["Loesungsauswahl"]);
					foreach ($QSelection [$key]["Loesungsauswahl"] as $kkey => $s) {
						$QSelection [$key]["Loesungsauswahl"][$kkey] = $s;
					}
					
				if (file_exists($QuizLoc.$ql["LernDatei"]) and ($ql["LernDatei"]!="")) {
					//echo "exists: " . $QuizLoc.$ql["LernDatei"] . "<br>";
					$LearningContent =$QuizLoc.$ql["LernDatei"];
				} else {
					$LearningContent ="";
				}

				}	
			}	
			$i++;
		}	
	}
	
	if (count($QSelection)==0) {
		$NoQuiz = 1;
	} else {
		$NoQuiz = 0;
	}

	$DroneTypes = [];

	foreach ($QSelection as $q) {
		$d = trim($q["Drone"]);
		if ($d !== "" && $d !== "Alle" && $q["Aktiv"]=="Ja") {
			$DroneTypes[$d] = true;
		}
	}

	$DroneTypes = array_keys($DroneTypes);
	$HasDroneTypes = count($DroneTypes) > 0;
	$DroneFilterActive = $HasDroneTypes && $Drone !== "" && $Drone !== "Alle";

	//pre_r($DroneTypes);

	//-----------------------------------------
	//Sortiere die Fragen um
	//-----------------------------------------

	$Order = Array();
	$Questions = Array();

	//Define Order of Questions
	foreach ($QSelection as $key => $Q) {
		$Order[$key]["key"] = $key;
		$Order[$key]["random"] = rand(0,100);
	}

	if (($mode!="Korrektur") and !$sorted) {
		aasort($Order, "random");
		//pre_r($Order);
	}	
	
	//pre_R($Order);
	
		// Normalize drone strings
	function normalizeDrone($str) {
		if ($str === null) return '';

		$str = (string)$str;

		// URL-encoded → normal
		$str = urldecode($str);

		// NBSP → normales Space
		$str = str_replace("\xC2\xA0", ' ', $str);

		// Zero-width spaces entfernen
		$str = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $str);

		// Mehrfache Spaces
		$str = preg_replace('/\s+/', ' ', $str);

		// Trim
		$str = trim($str);

		return $str;
	}

	$Drone = normalizeDrone($Drone);
	// echo "Drone (normalisiert): [" . $Drone . "]<br>";

	$i = 0;

	foreach ($Order as $key => $Q) {

		// Drone aus CSV normalisieren
		$drone = normalizeDrone($QSelection[$Q["key"]]["Drone"]);
		// echo "Frage " . $Q["key"] . " Drone=[" . $drone . "]<br>";

		// Drohnen-Matching-Regeln
		if ($Drone === "" || $Drone === "Alle") {
			// Keine spezifische Drohne gewählt → nur Fragen ohne Spezifikation oder "Alle"
			$droneMatch = ($drone === "" || $drone === "Alle");
		} else {
			// Spezifische Drohne gewählt → allgemeine + passende spezifische
			$droneMatch = ($drone === "" || $drone === "Alle" || $drone === $Drone);
		}

		if (
			(strcasecmp($QSelection[$Q["key"]]["Aktiv"],"Ja") == 0) &&
			(
				(strpos($QSelection[$Q["key"]]["Audienz"], $setting) !== false) ||
				(strcasecmp($QSelection[$Q["key"]]["Audienz"],"Alle") == 0) ||
				($setting == "TEST")
			) &&
			$droneMatch
		) {
			$Questions[$i] = $QSelection[$Q["key"]];
			$Questions[$i]["QID"] = $Q["key"];
			$i++;
		}
	}
	//echo "Ql";
	//pre_r($QSelection[5]);
	//echo "Q";
	//pre_r($Questions[5]);
	//echo "Lerninhalt " . $LearningContent . "<br>";

	// ===========================================
	// Inhalt
	// ===========================================
	
	//step 1
	//$tablewidth=1000;
	echo "<table width='".$tablewidth."'>" . chr(10);
	//ROW1
	echo "<tr><td style='vertical-align: top;'>" . chr(10);
	echo "<H1><span id='quiz-titel'>QuizTitel</span></H1>" . chr(10);
	echo "<span id='indrodution'></span>". chr(10);
	
	echo "</td ><td style='vertical-align: top; text-align: right;' witdh='250'>";
	if ($logo=="rkrs") {
		echo "<img src='".$CalcIconLoc."rkrs_logo.png' id='logopic' name='logopic' width='247' height='80'><br/><br/>" . chr(10);
	} elseif ($logo=="rkrk") {
		echo "<img src='".$CalcIconLoc."rkrk_logo.png' id='logopic' name='logopic' height='80'><br/><br/>" . chr(10);
	} else {
		echo "<img src='".$CalcIconLoc."void.png' id='logopic' name='logopic' witdh='100'><br/><br/>" . chr(10);
	}	
	echo "</td></tr>" . chr(10);
	echo "<tr><td colspan=2>" . chr(10);
	echo "<span id='learning'></span>". chr(10);
	echo "<span id='submit_learn' hidden><br><br><button id='LernenAbschliessen' onclick='ShowNameFormAfterLearning()' style='".$ButtonStyle ."'>Lerninhalt abschliessen</button></span>";
	echo "</td></tr>" . chr(10);
	echo "</table>" . chr(10);

	//ROW2
	echo "<table width='".$tablewidth."'>" . chr(10);
	//COLUMN 1 (Question Selector)
	echo "<tr><td rowspan='10' style='vertical-align: top;' width='".$ColWidth."'>" . chr(10);
	//echo "<span id='result-titel'><br><br>Fragen:<br></span>" . chr(10);
	echo "<span id='result-titel'></span>" . chr(10);
	echo "<span id='result'>Resultate</span>";
	echo "</td>" . chr(10);
	echo "</tr><tr>" . chr(10);
	echo "<td style='vertical-align: top;' width='".$PicWidth."'  colspan=3>" . chr(10);
	// QuizTimer balken
	echo "<p><div id='quiztimer-container' hidden>    <span id='quiztimer-text' style='font-weight: padding:1px 0; text-align:center;'>        Deine Zeit läuft...   </span>    <div id='quiztimer-bar'></div></div></p>" . chr(10);
	echo "<p><span id='Frage_Titel'>Frage_Titel</span></p>" . chr(10);
	echo "<p><span id='Frage'>Frage</span></p>" . chr(10);
	// Timer balken
	echo "<p><div id='timer-container' hidden>    <span id='timer-text' style='font-weight: padding:1px 0; text-align:center;'>        Deine Zeit läuft...   </span>    <div id='timer-bar'></div></div></p>" . chr(10);
	//echo "Infrarotbild:<br>" . chr(10);  
	echo "<img src='".$CalcIconLoc."void.png' id='frage-bild' name='frage-bild' width='".$PicWidth."' height='".$PicHeight."' style='".$PicutreStyle."'><br/>" . chr(10);
	echo "</td></tr>";
	echo "<form  method='post' action='". htmlspecialchars($_SERVER['REQUEST_URI']) . "' style='text-align: center;'>" . chr(10);
	echo "<tr><td colspan=3>";
	echo "<span id='auswahl'>";  
	echo "<fieldset  style='width:".$PicWidth."'>" . chr(10);
  	echo "<legend>Deine Wahl<span id='mehrfach'></span>:</legend>" . chr(10);
	echo "  <span id='a-ja'>" . chr(10);
	echo "    <input type='radio' id='ja' name='response' value='Ja' />" . chr(10);
	echo "    <label for='ja'><b>Ja</b></label><br><br>" . chr(10);
	echo "  </span>" . chr(10);
	echo "  <span id='a-nein'>" . chr(10);
	echo "    <input type='radio' id='nein' name='response' value='Nein' />" . chr(10);
	echo "    <label for='nein'><b>Nein</b></label>" . chr(10);
	echo "  </span>" . chr(10);
	echo "  <span id='a-a'>" . chr(10);
	echo "    <input type='radio' id='A' name='response' value='A' />" . chr(10);
	echo "    <label id='labelA' for='A'>A</label>" . chr(10);
	//echo "    <span id='t-a'></span>" . chr(10);
	echo "  </span>" . chr(10);
	echo "  <span id='a-b'>" . chr(10);
	echo "    <input type='radio' id='B' name='response' value='B' />" . chr(10);
	echo "    <label id='labelB' for='B'>B</label>" . chr(10);
	//echo "    <span id='t-b'></span>" . chr(10);
	echo "  </span>" . chr(10);
	echo "  <span id='a-c'>" . chr(10);
	echo "    <input type='radio' id='C' name='response' value='C' />" . chr(10);
	echo "    <label id='labelC' for='C'>C</label>" . chr(10);
	//echo "    <span id='t-c'></span>" . chr(10);
	echo "  </span>" . chr(10);
	echo "  <span id='a-d'>" . chr(10);
	echo "    <input type='radio' id='D' name='response' value='D' />" . chr(10);
	echo "    <label id='labelD' for='D'>D</label>" . chr(10);
	//echo "    <span id='t-d' for='D'></span>" . chr(10);
	echo "  </span>" . chr(10);
	echo "  <span id='a-e'>" . chr(10);
	echo "    <input type='radio' id='E' name='response' value='E' />" . chr(10);
	echo "    <label id='labelE' for='E'>E</label>" . chr(10);
	//echo "    <span id='t-e' for='E'></span>" . chr(10);
	echo "  </span>" . chr(10);
	echo "  <span id='a-f'>" . chr(10);
	echo "    <input type='radio' id='F' name='response' value='F' />" . chr(10);
	echo "    <label id='labelF' for='F'>F</label>" . chr(10);
	//echo "    <span id='t-f' for='F'></span>" . chr(10);
	echo "  </span>" . chr(10);
	echo "</fieldset>" . chr(10);
	echo "</span>" . chr(10);
	echo "</td></tr>";

	// Name
	echo "<span id='Name-Label'><br>Dein Name: </span><input type='text' id='Name' name='Name' value='".$Name."'>" . chr(10);
	echo "<span id='Teilnehmerinfo' hidden>Teilnehmerinfo</span>";

	// Drohnenauswahl nur beim Start anzeigen
	if ($Status == "" && $HasDroneTypes) {
		echo "<span id='Drone-Label'><br>Deine Drohne: </span>";
		echo "<select id='Drone' name='Drone'>";
		echo "<option value=''>Bitte wählen…</option>";
		//echo "<option value='Alle'".($Drone==="Alle" ? " selected" : "").">Alle Drohnen</option>";
		foreach ($DroneTypes as $d) {
			$sel = ($Drone === $d) ? " selected" : "";
			echo "<option value='".htmlspecialchars($d,ENT_QUOTES)."'".$sel.">".htmlspecialchars($d)."</option>";
		}
		echo "</select><br><br>";
	} elseif (!$HasDroneTypes) {
		echo "<input type='hidden' id='Drone' name='Drone' value='Alle'>";
	}

	echo "  <input type='text' id='AnzFragen' name='AnzFragen' hidden>" . chr(10);
	echo "  <input type='text' id='RichtigBeantwortet' name='RichtigBeantwortet' hidden>" . chr(10);
	echo "  <input type='text' id='Erfuellt' name='Erfuellt' hidden>" . chr(10);
	echo "  <input type='text' id='Quiz' name='Quiz' hidden>" . chr(10);
	echo "  <input type='text' id='Status' name='Status' value='".$Status."' hidden>" . chr(10);
	echo "  <input type='text' id='Schulung' name='Schulung' value='".$Schulung."' hidden>" . chr(10);
	echo "  <input type='submit' id='close' value='Quiz Abschliessen' style='".$ButtonStyleRed ."' hidden>" . chr(10);
	echo "</form> " . chr(10);

	echo "<span id='noname' hidden><button id='noname_button' onclick='NameEingeben()' style='".$ButtonStyleRed ."'>Bitte Name vor dem Abschliessen eingeben.</button></span>";
	echo "</td></tr>";
	echo "</td></tr>";
	echo "<tr><td width='100' class='left'>";  
	echo "<span id='prev_button'><button id='prev' onclick='PrevQuestion()' style='".$ButtonStyle ."'>Zurück</button></span>";
	echo "</td><td width='400' class='center'>";
	echo "<span id='submit_button'><button id='submit' onclick='Submit()' style='".$ButtonStyle ."'>eingeben und auflösen</button></span>";
	echo "</td><td width='100' class='right'>";
	echo "<span id='next_button'><button id='next' onclick='NextQuestion()' style='".$ButtonStyle ."'>Weiter</button></span>";
	echo "</td></tr>";
	echo "<tr><td colspan=3>";
	echo "<span id='loesungstext'>Lösungstext</span><br>" . chr(10); 
	echo "<span id='loesung' hidden>". chr(10);
	//echo "Auflösung:<br>" . chr(10);
	//echo "<img src='".$CalcIconLoc."void.png' id='loesung-bild' name='loesung-bild' width='".$PicWidth."' height='".$PicHeight."' style='".$PicutreStyle."'><br/><br/>" . chr(10);
	echo "<img src='".$CalcIconLoc."void.png' id='loesung-bild' name='loesung-bild' width='".$PicWidth."' style='".$PicutreStyle."'><br/><br/>" . chr(10);
	echo "<span id='korrektur' hidden>Korrektur</span><br>" . chr(10);
	echo "</span><br>" . chr(10);
	echo "</td></tr>";
	echo "<tr><td colspan=3>";
	echo "<div  id='debug' name='debug' style='background-color: #f7e9e9;'></div>  ";
	echo "<div  id='debug1' name='debug1' style='background-color: #f7e9e9;'></div>  ";
	echo "<div  id='debug2' name='debug2' style='background-color: #f7e9e9;'></div>  ";
	echo "</td></tr>";
	echo "<tr><td colspan=3>";
	echo "<div align='center'> <i>© " . date("Y") ." by ".$copyright."</i></div>" . chr(10);
	if ($Schulung) {
		echo "<div align='center' style='font-size:10'><a href='mailto:thomas.christen@drohnenrehkitzrettung.ch?subject=Rückmeldung Schlungen Drohnenrehkitzrettung Knonaueramt'>Rückmeldungen</i></a></div>" . chr(10);
		echo "<div align='center' style='font-size:10'> <i><a href='".$_SERVER['PHP_SELF']."?".$urladder."'>Weitere Schulungen</a></i>" . chr(10);
	} else {	
		echo "<div align='center' style='font-size:10'><a href='mailto:thomas.christen@drohnenrehkitzrettung.ch?subject=Rückmeldung Quiz Drohnenrehkitzrettung Knonaueramt'>Rückmeldungen</i></a></div>" . chr(10);
		echo "<div align='center' style='font-size:10'> <i><a href='".$_SERVER['PHP_SELF']."?".$urladder."'>Weitere Quiz</a></i>" . chr(10);
	}		
	echo "</td></tr>";
	echo "<tr><td colspan=3><span id='buffer'></span>";
	echo "</td></tr>";
	echo "</table>" . chr(10);
	echo "<span id='debug'></span>" . chr(10);
//	pre_r($QuizList);
//	echo "Status: " . $Status . "<br>";
//	pre_r($Questions);
?>
<!-- TIMER balken --> 
<script>
let timerDuration = 20;
let timerOrange = 5;
let timerRed = 3;
let timerStart = null;
let timerActive = false;

function startTimer() {
    timerStart = null;
    timerActive = true;
    requestAnimationFrame(runTimer);
}

function stopTimer() {
    timerActive = false;
}

function resetTimerBar() {
    const bar = document.getElementById("timer-bar");
    bar.style.width = "100%";
    bar.style.backgroundColor = "#00c853";
}

function runTimer(timestamp) {
    if (!timerActive) return;

    if (!timerStart) timerStart = timestamp;
    const elapsed = (timestamp - timerStart) / 1000;
    const remaining = Math.max(timerDuration - elapsed, 0);

    const bar = document.getElementById("timer-bar");
    const progress = remaining / timerDuration;

    bar.style.width = (progress * 100) + "%";

    // Dynamische Farbschwellen
    let orangeLimit = timerDuration / 3;
    let redLimit = timerDuration / 5;

    if (remaining <= redLimit) {
        bar.style.backgroundColor = "#e60000"; // Rot
    } else if (remaining <= orangeLimit) {
        bar.style.backgroundColor = "#ff9800"; // Orange
    } else {
        bar.style.backgroundColor = "#00c853"; // Grün
    }

    if (remaining > 0) {
        requestAnimationFrame(runTimer);
    } else {
        timerActive = false;
        Submit();
    }
	
}

function runQuizTimer(timestamp) {
	document.getElementById("quiztimer-container").style.display = "block";

    if (!quizTimerActive) return;

    if (!quizTimerStart) quizTimerStart = timestamp;

    const elapsed = (timestamp - quizTimerStart) / 1000;
    const remaining = Math.max(quizTimerDuration - elapsed, 0);

    // mm:ss Format
    let mm = Math.floor(remaining / 60);
    let ss = Math.floor(remaining % 60);
    if (ss < 10) ss = "0" + ss;

    // Elemente
    const bar = document.getElementById("quiztimer-bar");
    const txt = document.getElementById("quiztimer-text");
    const cont = document.getElementById("quiztimer-container");

    // Balken sichtbar halten
    cont.style.display = "block";

    // Balkenbreite
    const progress = remaining / quizTimerDuration;
    bar.style.width = (progress * 100) + "%";

    // Farben
    let orangeLimit = quizTimerDuration / 3;
    let redLimit = quizTimerDuration / 5;

    if (remaining <= redLimit) {
        bar.style.backgroundColor = "#e60000";
    } else if (remaining <= orangeLimit) {
        bar.style.backgroundColor = "#ff9800";
    } else {
        bar.style.backgroundColor = "#00c853";
    }

    // Text
    txt.innerHTML = mm + ":" + ss;

    if (remaining > 0) {
        requestAnimationFrame(runQuizTimer);
    } else {
        quizTimerActive = false;
        document.getElementById("Status").value = "resultat";
        document.forms[0].submit();
    }
}
</script>

<script type='text/javascript'>
<?php
echo "var QuizList = ". json_encode($QuizList) . ";\n"; 
echo "var Questionair = ". json_encode($Questionair) . ";\n"; 
echo "var Questions = ". json_encode($Questions) . ";\n"; 
echo "var CalcIconLoc = ". json_encode($CalcIconLoc) . ";\n"; 
echo "var QuizIconLoc = ". json_encode($QuizIconLoc.$Questionair["DriveLocation"]) . ";\n"; 
echo "var QuizIntroduction = '". $Questionair["Einfuehrung"] . "';\n";
echo "var setting = '". $setting . "';\n";
echo "var set_quiz = '". $set_quiz . "';\n";
echo "var urladder = '". $urladder . "';\n";
echo "var allquestions = '". ($mode=="Korrektur")  . "';\n";
echo "var NoQuiz = " . $NoQuiz . ";\n";
echo "var Status = '" . $Status . "';\n";
echo "var Schulung = '" . $Schulung . "';\n";
echo "var FragenRichtig = '" . $FragenRichtig . "';\n";
echo "var AnzFragen = '" . $AnzFragen . "';\n";
echo "var Erfuellt = '" . $Erfuellt . "';\n";
echo "var LearningContent = '" . $LearningContent . "';\n";
echo "var Quiz = '" . $Quiz . "';\n";
echo "var PicHeight = ". $PicHeight  . ";\n";
echo "var PicHeightSmall = ". $PicHeightSmall  . ";\n";
echo "var PicWidth = '" . $PicWidth . "';\n";
echo "var PicutreStyle = '" . json_encode($PicutreStyle) . "';\n";
echo "var SelectedDrone = '". $Drone ."';\n";
echo "var QuizTime = '". $Questionair["QuizTime"] . "';\n";
echo "var Name = '". $Name ."';\n";
?>

var AtQuestion = 0;
var url = window.location.href.split('?')[0];

// Quiz-Gesamtzeit (in Sekunden aus CSV)
var quizTimerDuration = parseInt(QuizTime || 0);
var quizTimerStart = null;
var quizTimerActive = false;

// Timer ausblenden
if (!quizTimerActive) {
    document.getElementById("timer-container").style.display = "none";
}

// MaxQuestions bestimmen
var MaxQuestions = 0;
if (Questions.length > 0) {
    if ((Questions.length < Questionair["AnzQuestions"]) || (allquestions)) {
        MaxQuestions = Questions.length;
    } else {
        MaxQuestions = Questionair["AnzQuestions"];
    }
}

// Farben
const red = "#fc2222";
const grey = "#aaaaaa";
const green = "#67b516";

// Listener für Antworten
function attachResponseListeners() {
    document.querySelectorAll("input[name='response']").forEach(el => {
        el.addEventListener("change", () => {

            if (el.type === "checkbox") {
                let checked = [...document.querySelectorAll("input[name='response']:checked")]
                    .map(x => x.value);
                Questions[AtQuestion]["Antwort"] = checked.join("|");
            }

            if (el.type === "radio") {
                Questions[AtQuestion]["Antwort"] = el.value;
            }

            document.getElementById("submit_button").style.display = 'block';
        });
    });
}
attachResponseListeners();

// =====================================
// WICHTIG: StartQuiz() – korrigiert
// =====================================
function StartQuiz() {

    let name = document.getElementById("Name").value.trim();
    let droneElement = document.getElementById("Drone");
    let drone = droneElement ? droneElement.value : "";

    if (name === "") {
        document.getElementById("indrodution").innerHTML +=
            "<div style='color:red; margin-top:10px;'>Bitte gib deinen Namen ein.</div>";
        return;
    }

    if (droneElement && drone === "") {
        document.getElementById("indrodution").innerHTML +=
            "<div style='color:red; margin-top:10px;'>Bitte wähle deine Drohne.</div>";
        return;
    }

    // ❗ NICHT DISABLEN – sonst wird Drohne NICHT übertragen!
    // droneElement.disabled = true;

    // Status setzen
    document.getElementById("Status").value = "Quiz";

    // Formular ABSENDEN → Drohne + Name werden übertragen
    document.forms[0].submit();
}

// =====================================
// Setup
// =====================================
DoSetup();

function DoSetup() {

    if (Status == 'resultat') {
        document.getElementById("quiz-titel").innerHTML = Questionair["Titel"];
        let Auswertung = "";

        Auswertung += "Super, du hast alle Fragen beantwortet!<br><br>";
        Auswertung += "Du hast <b>" + FragenRichtig + "</b> von <b>" + AnzFragen + "</b> richtig beantwortet.<br>";
        Auswertung += "Zum Erfüllen brauchst du " + Math.floor(Erfuellt * 100) + "%.<br><br>";

        if ((FragenRichtig / AnzFragen) >= Erfuellt) {
            Auswertung += "Herzliche Gratulation! Du hast den Test erfolgreich absolviert!<br>";
        } else {
            if (Schulung) {
                Auswertung += "Leider hat es diesmal nicht gereicht. <a href='" + url + "?quiz=" + Quiz + "&" + urladder + "'>Schulung wiederholen</a><br>";
            } else {
                Auswertung += "Leider hat es diesmal nicht gereicht. <a href='" + url + "?quiz=" + Quiz + "&" + urladder + "'>Quiz wiederholen</a><br>";
            }
        }

        document.getElementById("indrodution").innerHTML = Auswertung;
        document.getElementById("indrodution").style.fontSize = "12pt";

        ShowHideQuestionFields('none');
        
		showTeilnehmerinfo();

		return;
    }

    if ((set_quiz == "") || (NoQuiz == 1)) {
        document.getElementById("quiz-titel").innerHTML = Schulung ?
            "Bibliothek Rehkitzrettung Knonaueramt" :
            "Quiz Ecke Rehkitzrettung Knonaueramt";

        ShowQuizList();
        return;
    }

    document.getElementById("quiz-titel").innerHTML = Questionair["Titel"];
    document.getElementById("indrodution").style.fontSize = "10pt";

    ShowLearninhaltOrQuestion(true);
}

// =====================================
// Name-Formular
// =====================================
function ShowNameForm() {

    let droneText = "";
    if (<?php echo $HasDroneTypes ? "true" : "false"; ?>) {
        droneText = " und deine Drohne";
    }

    document.getElementById("indrodution").innerHTML = `
        <div style="padding: 1em; background:#f0f0f0; border-radius:8px; max-width:400px;">
            <h3>Bitte gib deinen Namen${droneText} ein</h3>
            <p>${droneText ? "Beides ist notwendig, um das Quiz zu starten." : "Bitte gib deinen Namen ein, um das Quiz zu starten."}</p>
            <button onclick="StartQuiz()" 
                    style="border-radius: 3px; border: none; color: white; background: #98B489; padding: 10px; text-align: center; text-decoration: none; display: inline-block; font-size: 16px; margin: 4px 2px; cursor: pointer">
                Quiz starten
            </button>
        </div>
    `;

    document.getElementById("Name").style.display = "block";
    document.getElementById("Name-Label").style.display = "block";

    let d = document.getElementById("Drone");
    let dl = document.getElementById("Drone-Label");
    if (d) d.style.display = "block";
    if (dl) dl.style.display = "block";

    ShowHideQuestionFields('none');
}

function ShowNameFormAfterLearning() {

	ShowNameForm()
    
	// Lerninhalt ausblenden
    document.getElementById("learning").innerHTML = "";
    document.getElementById("submit_learn").style.display = "none";

    // Fragenfelder ausblenden
    ShowHideQuestionFields('none');

/*
    // Headerblock wieder anzeigen
    document.getElementById("quiz-titel").style.display = "block";
    document.getElementById("indrodution").style.display = "block";
    document.getElementById("logopic").style.display = "block";

    // Resultat-Titel ausblenden (sonst überschreibt er den Header)
    document.getElementById("result-titel").style.display = "none";
*/
}

function ShowLearninhaltOrQuestion(EnableLearning) {

    if ((LearningContent != "") && (EnableLearning) && (Status != "Quiz")) {
        ShowLearnContent(LearningContent);
        document.getElementById("submit_learn").style.display = 'block';
        Status = "Learning";
        ShowHideQuestionFields('none');
        return;
    }

	// Name MUSS zuerst eingegeben werden
	if (document.getElementById("Name").value.trim() === "") {
		ShowNameForm();
		return;
	}

	// QUIZ-TIMER STARTEN (nur einmal)
	if (!quizTimerActive && quizTimerDuration > 0) {
		quizTimerActive = true;
		quizTimerStart = null;
		requestAnimationFrame(runQuizTimer);
	}

	GoToQuestion(0);
    let Output = "";
    let QNum = 1;

    Questions.forEach((qu) => {
        if (MaxQuestions >= QNum) {
            Output += GetButton((QNum - 1), "Frage " + QNum, grey);
        }
        QNum++;
    });

    ShowHideQuestionFields('block');
    document.getElementById("result").innerHTML = Output;
    document.getElementById("loesungstext").innerHTML = "";
    document.getElementById("buffer").innerHTML = "";
    document.getElementById("submit_learn").style.display = 'none';
    document.getElementById("indrodution").innerHTML = QuizIntroduction;
    document.getElementById("learning").innerHTML = "";
}

// =====================================
// Lerninhalt laden
// =====================================
function ShowLearnContent(dateipfad) {

    fetch(dateipfad)
        .then(response => response.text())
        .then(text => {
            document.getElementById("learning").innerHTML = text;
        })
        .catch(() => {
            Status = "";
        });

    ShowHideQuestionFields('none');
    document.getElementById("Name").style.display = 'none';
    document.getElementById("Name-Label").style.display = 'none';
}

// =====================================
// Frage auswählen
// =====================================
function SelectQuestion(ToQuestion) {

    if (document.getElementById("Name").value.trim() === "") {
        alert("Bitte gib zuerst deinen Namen ein.");
        document.getElementById("Name").focus();
        return;
    }

    stopTimer();

    let qTime = parseInt(Questions[ToQuestion]["Zeit"] || 0);

    if (qTime > 0) {
        document.getElementById("timer-container").style.display = "block";
        timerDuration = qTime;
        resetTimerBar();
        startTimer();
    } else {
		if (!quizTimerActive) {
			document.getElementById("timer-container").style.display = "none";
		}
    }

    GoToQuestion(ToQuestion);
    LockWahl();
    ShowResult();
}

// =====================================
// Antwort absenden
// =====================================
function Submit() {

    stopTimer();
	if (!quizTimerActive) {
		document.getElementById("timer-container").style.display = "none";
	}

    if (Questions[AtQuestion]["Antwort"] != "") {
        Questions[AtQuestion]["Locked"] = "Ja";
        document.getElementById("submit_button").style.display = 'none';
    }

    LockWahl();
    UpdateButton(AtQuestion);
    ShowResult();
    CheckEndOfQuiz();
}

// =====================================
// Navigation
// =====================================
function NextQuestion() {
    if (AtQuestion < (MaxQuestions - 1)) AtQuestion++;
    GoToQuestion(AtQuestion);
    UpdateButton(AtQuestion);
    LockWahl();
    ShowResult();
}

function PrevQuestion() {
    if (AtQuestion > 0) AtQuestion--;
    GoToQuestion(AtQuestion);
    UpdateButton(AtQuestion);
    LockWahl();
    ShowResult();
}

// =====================================
// Antwort sperren / anzeigen
// =====================================
function LockWahl() {

    document.querySelectorAll('input[name="response"]').forEach(el => el.checked = false);

    if (Questions[AtQuestion]["Antwort"]) {

        if (Questions[AtQuestion]["Antwort"].includes("|")) {
            let parts = Questions[AtQuestion]["Antwort"].split("|");
            document.querySelectorAll('input[name="response"]').forEach(el => {
                el.checked = parts.includes(el.value);
            });
        } else {
            document.querySelectorAll('input[name="response"]').forEach(el => {
                el.checked = (el.value === Questions[AtQuestion]["Antwort"]);
            });
        }
    }

    let locked = (Questions[AtQuestion]["Locked"] == "Ja");

    document.querySelectorAll("#ja, #nein, #A, #B, #C, #D, #E, #F")
        .forEach(el => el.disabled = locked);
}

function GoToQuestion(ToQuestion) {

    AtQuestion = ToQuestion;

    stopTimer();

    let qTime = parseInt(Questions[AtQuestion]["Zeit"] || 0);

    if (qTime > 0) {
        document.getElementById("timer-container").style.display = "block";
        timerDuration = qTime;
        resetTimerBar();
        startTimer();
    } else {
		if (!quizTimerActive) {
			document.getElementById("timer-container").style.display = "none";
		}
    }

    // Navigation Buttons
    document.getElementById("prev_button").style.display = (ToQuestion < 1) ? 'none' : 'block';
    document.getElementById("next_button").style.display = (ToQuestion < (MaxQuestions - 1)) ? 'block' : 'none';

	//Telnehmerinformation
	let name = document.getElementById("Name").value.trim();
    let droneElement = document.getElementById("Drone");

	if (name !="") {
		document.getElementById("Name-Label").style.display = 'none';
		document.getElementById("Name").style.display = 'none';
		document.getElementById("Teilnehmerinfo").style.display = 'block';
//		document.getElementById("Name-Label").style.display = (ToQuestion < 1) ? 'none' : 'block';
//		document.getElementById("Name").style.display = (ToQuestion < 1) ? 'none' : 'block';
//		document.getElementById("Teilnehmerinfo").style.display = (ToQuestion < 1) ? 'block' : 'none';
		document.getElementById("Teilnehmerinfo").innerHTML = "Willkommen zum Quiz <b>" + name + "</b>";
		if (SelectedDrone!="Alle") {
			document.getElementById("Teilnehmerinfo").innerHTML += ", gewählte Drohne <b>" + SelectedDrone + "</b>";
		}
	} else {
		document.getElementById("Name-Label").style.display = (ToQuestion < 1) ? 'block' : 'none';
		document.getElementById("Name").style.display = (ToQuestion < 1) ? 'block' : 'none';
		document.getElementById("Teilnehmerinfo").style.display = (ToQuestion < 1) ? 'none' : 'block';
	}
	
    // Submit Button
    if (Questions[ToQuestion]["Locked"] == "Ja") {
        document.getElementById("submit_button").style.display = 'none';
    } else {
        if (Questions[ToQuestion]["Antwort"] == "") {
            document.getElementById("submit_button").style.display = 'none';
        } else {
            document.getElementById("submit_button").style.display = 'block';
        }
    }

    document.getElementById("close").style.display = 'none';

    // Mehrfachauswahl
    const isMulti = Questions[ToQuestion]["Loesung"].includes("|");
    document.getElementById("mehrfach").innerHTML = isMulti ? " (Mehrfachauswahl)" : "";

    ["A","B","C","D","E","F"].forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        el.type = isMulti ? "checkbox" : "radio";
    });

    // Auswahlfelder anzeigen
    if (Questions[ToQuestion]["Loesung"] == "Ja" || Questions[ToQuestion]["Loesung"] == "Nein") {

        document.getElementById("a-ja").style.display = 'block';
        document.getElementById("a-nein").style.display = 'block';

        ["a-a","a-b","a-c","a-d","a-e","a-f"].forEach(id => {
            document.getElementById(id).style.display = 'none';
        });

    } else {

        document.getElementById("a-ja").style.display = 'none';
        document.getElementById("a-nein").style.display = 'none';

        const labels = ["A","B","C","D","E","F"];

        labels.forEach((letter, idx) => {
            let span = document.getElementById("a-" + letter.toLowerCase());
            let label = document.getElementById("label" + letter);

            if (Questions[ToQuestion]["Loesungsauswahl"][idx] != null) {
                span.style.display = 'block';
                label.innerHTML = "<b>" + letter + "</b> <i>" + Questions[ToQuestion]["Loesungsauswahl"][idx] + "</i><br><br>";
            } else {
                span.style.display = 'none';
                label.innerHTML = "";
            }
        });
    }

    // Titel + Frage
    document.getElementById("Frage_Titel").innerHTML = "Frage " + (ToQuestion + 1);
    document.getElementById("Frage_Titel").style.fontSize = "12px";

    document.getElementById("Frage").innerHTML = "<b>" + Questions[ToQuestion]["Frage"] + "</b>";
    document.getElementById("Frage").style.fontSize = "16px";

    // Fragebild
    if (Questions[ToQuestion]["Frage-Bild"] != "") {
        document.getElementById("frage-bild").style.display = 'block';
        document.getElementById("frage-bild").src = QuizIconLoc + Questions[ToQuestion]["Frage-Bild"];

        if (Questions[ToQuestion]["Bildformat"] == "schmal") {
            document.getElementById("frage-bild").height = Math.floor(PicHeight / 2);
        } else {
            document.getElementById("frage-bild").height = PicHeight;
        }
    } else {
        document.getElementById("frage-bild").style.display = 'none';
    }

    // Resultatmodus
    if (Status == 'resultat') {
        document.getElementById("submit_button").style.display = 'none';

        if (document.getElementById("Name").value == "") {
            document.getElementById("noname").style.display = 'block';
            document.getElementById("close").style.display = 'none';
        } else {
            document.getElementById("noname").style.display = 'none';
            document.getElementById("close").style.display = 'block';
        }
    }

    if (setting == "TEST") {
        document.getElementById("debug").innerHTML = "Frage: " + ToQuestion;
    }
}

// =====================================
// Button erzeugen
// =====================================
function GetButton(nr, name, color) {

    let ButtonStyle = 
        "border-radius: 3px; border: none; color: white; background: " + color +
        "; padding: " + (AnzFragen > 20 ? "10px" : "5px") +
        "; text-align: center; text-decoration: none; display: inline-block; font-size: 16px; margin: 4px 2px; cursor: pointer";

    return "<button id='F" + nr + "' style='" + ButtonStyle + "' onclick='SelectQuestion(" + nr + ")'>" + name + "</button>";
}

// =====================================
// Button aktualisieren
// =====================================
function UpdateButton(nr) {

    if ((Questions[AtQuestion]["Antwort"] == "") || (Questions[AtQuestion]["Locked"] == "Nein")) {
        document.getElementById("F" + nr).style.background = grey;
    } else if (Questions[AtQuestion]["Antwort"] == Questions[AtQuestion]["Loesung"]) {
        document.getElementById("F" + nr).style.background = green;
    } else {
        document.getElementById("F" + nr).style.background = red;
    }

    if (setting == "TEST") {
        document.getElementById("debug").innerHTML =
            "Frage:" + nr + " Antwort: " + Questions[AtQuestion]["Antwort"] +
            " Lösung: " + Questions[AtQuestion]["Loesung"];
    }
}

// =====================================
// Lösung anzeigen
// =====================================
function ShowResult() {

    if ((Questions[AtQuestion]["Antwort"] == "") || (Questions[AtQuestion]["Locked"] == "Nein")) {

        document.getElementById("loesung-bild").src = "";
        document.getElementById("loesungstext").innerHTML = "";
        document.getElementById("loesung").style.display = 'none';

    } else {

        if (Questions[AtQuestion]["Loesungs-Bild"] != "") {
            document.getElementById("loesung-bild").src = QuizIconLoc + Questions[AtQuestion]["Loesungs-Bild"];
            document.getElementById("loesung-bild").style.display = 'block';
        } else {
            document.getElementById("loesung-bild").style.display = 'none';
        }

        document.getElementById("loesungstext").innerHTML = Questions[AtQuestion]["Loesungstext"];

        if (Questions[AtQuestion]["Antwort"] == Questions[AtQuestion]["Loesung"]) {
            document.getElementById("korrektur").innerHTML = "Gratuliere, deine Antwort ist richtig!";
        } else {
            document.getElementById("korrektur").innerHTML = "Tut mir leid, deine Antwort ist nicht korrekt!";
        }

        document.getElementById("loesung").style.display = 'block';
    }
}

function ShowHideQuestionFields(ShowHide) {

    document.getElementById("frage-bild").style.display = ShowHide;
    document.getElementById("Frage_Titel").style.display = ShowHide;
    document.getElementById("Frage").style.display = ShowHide;
    document.getElementById("auswahl").style.display = ShowHide;
    document.getElementById("prev_button").style.display = ShowHide;
    document.getElementById("submit_button").style.display = ShowHide;
    document.getElementById("next_button").style.display = ShowHide;
    document.getElementById("loesung").style.display = ShowHide;
    document.getElementById("loesung-bild").style.display = ShowHide;
    document.getElementById("loesungstext").style.display = ShowHide;
    document.getElementById("result").style.display = ShowHide;
    document.getElementById("result-titel").style.display = ShowHide;

    // Fehler korrigiert: 'hide' ist kein gültiger Wert
    document.getElementById("korrektur").style.display = 'none';
    document.getElementById("close").style.display = 'none';
}

function ShowQuizList() {

    let QuizListText = "";

    if (Schulung == "Schulung") {
        QuizListText = "Willkommen in unserer Wissensbibliothek.<br><br>" +
            "Wir haben hier interessante Themen mit vielen lehrreichen Informationen über die Rehkitzrettung zusammengestellt.<br><br>" +
            "Wähle dir ein Thema aus, lese dich ein und prüfe dein Wissen!<br><br>Viel Erfolg.<br><br>";
    } else {
        QuizListText = "Willkommen in unserer Quiz-Ecke.<br><br>" +
            "Wir haben hier interessante und überraschende Online-Quiz mit vielen lehrreichen Informationen über die Rehkitzrettung zusammengestellt.<br><br>" +
            "Wähle dir ein Quiz und prüfe dein Wissen!<br><br>Viel Erfolg.<br><br>";
    }

    QuizListText += "<ul>";

    QuizList.forEach((qs) => {

        let isQuiz = (qs["Quiz"] == "Ja" && Schulung == "");
        let isSchulung = (qs["Schulung"] == "Ja" && Schulung == "Schulung");

        let allowed = ((qs["Locked"] == "Nein") && (qs["Aktiv"] == "Ja")) || (setting == "TEST");

        if ((isQuiz || isSchulung) && allowed) {

            let remark = (qs["Locked"] == "Ja" || qs["Aktiv"] == "Nein")
                ? "<i><span style='color:red'><br>Nicht freigegeben</span></i>"
                : "";

            QuizListText += "<li>";
            QuizListText += "<b><a href='" + url + "?quiz=" + qs["Key"] + "&" + urladder + "'>" + qs["Titel"] + "</a></b> ";

            if (qs["LernDatei"] != "" && qs["FragenDatei"] != "") {
                QuizListText += "(<a href='" + url + "?quiz=" + qs["Key"] + "&Status=Quiz&" + urladder + "'>Quiz</a>)";
            }

            QuizListText += remark + "<br><br>";

            if (qs["Bild"] != "") {
                QuizListText += "<img src='" + QuizIconLoc + qs["Bild"] + "' width='" + PicWidth +
                    "' height='" + PicHeightSmall + "' style='" + PicutreStyle + "'>";
            }

            QuizListText += qs["Kurzbeschreibung"] + "<br><br><br>";
            QuizListText += "</li>";
        }
    });

    QuizListText += "</ul>";

    document.getElementById("indrodution").innerHTML = QuizListText;
    document.getElementById("indrodution").style.fontSize = "12pt";
    document.getElementById("learning").innerHTML = "";
    document.getElementById("Name").style.display = 'none';
    document.getElementById("Name-Label").style.display = 'none';

    ShowHideQuestionFields('none');
}

function showTeilnehmerinfo() {

    let name = document.getElementById("Name").value.trim();
    let info = document.getElementById("Teilnehmerinfo");

    if (name === "") return;

    // Name-Felder ausblenden
    document.getElementById("Name-Label").style.display = 'none';
    document.getElementById("Name").style.display = 'none';

    // Teilnehmerinfo anzeigen
    info.style.display = 'block';
    info.innerHTML = "Teilnehmer: <b>" + name + "</b>";

    // Drohne nur anzeigen, wenn NICHT "Alle"
    if (SelectedDrone !== "" && SelectedDrone !== "Alle") {
        info.innerHTML += ", gewählte Drohne: <b>" + SelectedDrone + "</b>";
    }
}


// =====================================
// Quiz-Ende prüfen
// =====================================
function CheckEndOfQuiz() {

    let FragenBeantwortet = 0;
    let Richtig = 0;

    // Antworten zählen
    Questions.forEach((qu) => {
        if (qu["Locked"] == "Ja") {
            FragenBeantwortet++;
            if (qu["Antwort"] == qu["Loesung"]) {
                Richtig++;
            }
        }
    });

    // Anzahl Fragen = MaxQuestions (nicht nur beantwortete!)
    document.getElementById("AnzFragen").value = MaxQuestions;
    document.getElementById("RichtigBeantwortet").value = Richtig;
    document.getElementById("Erfuellt").value = Questionair["Erfuellt"];
    document.getElementById("Quiz").value = set_quiz;

    let status = "";

    // Wenn alle Fragen beantwortet wurden → Abschlussphase
    if (FragenBeantwortet == MaxQuestions) {

        status = 'fertig';

        // Teilnehmerinfo anzeigen
        showTeilnehmerinfo();
		
        // Navigation ausblenden
        document.getElementById("prev_button").style.display = 'none';
        document.getElementById("next_button").style.display = 'none';

        // Abschluss-Button anzeigen
        document.getElementById("submit_button").style.display = 'block';
        document.getElementById("submit_button").value = 'Quiz abschliessen';
    }

    // Abschluss wurde bestätigt → Resultatmodus
    if (status == 'fertig') {

        document.getElementById("submit_button").style.display = 'none';
        status = 'resultat';

       // Teilnehmerinfo auch im Resultat anzeigen
        showTeilnehmerinfo();

        // Abschluss-Button sichtbar machen
        if (document.getElementById("Name").value.trim() !== "") {
            document.getElementById("noname").style.display = 'none';
            document.getElementById("close").style.display = 'block';
        } else {
            document.getElementById("noname").style.display = 'block';
            document.getElementById("close").style.display = 'none';
        }
    }

    document.getElementById("Status").value = status;
}

// =====================================
// Name prüfen
// =====================================
function NameEingeben() {

    if (document.getElementById("Name").value == "") {
        document.getElementById("noname").style.display = 'block';
        document.getElementById("close").style.display = 'none';
    } else {
        document.getElementById("noname").style.display = 'none';
        document.getElementById("close").style.display = 'block';
    }
}
</script>
</body>
</html>
