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
  /* === Modern responsive layout (keeps existing IDs & JS hooks) === */
  :root{
    --brand:#98B489;
    --danger:#b30000;
    --muted:#E8E8E8;
    --bg:#f6f7f6;
    --card:#ffffff;
    --text:#1f2a1f;
    --border:#e4e7e4;
    --radius:14px;
    --shadow: 0 6px 18px rgba(0,0,0,.06);
  }

  body{ margin:0; font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; background:var(--bg); color:var(--text); }

  /* Preserve legacy helper classes (used previously on <td>) */
  .left{ text-align:left; }
  .center{ text-align:center; }
  .right{ text-align:right; }

  /* Responsive container */
  .rkrk-wrap{ max-width:1100px; margin:0 auto; padding:14px; }
  .rkrk-card{
    background:var(--card);
    border:1px solid var(--border);
    border-radius:var(--radius);
    box-shadow:var(--shadow);
    padding:14px 16px;
  }

  /* Header */
  .rkrk-topbar{ display:flex; gap:12px; align-items:flex-start; justify-content:space-between; }
  .rkrk-title h1{ margin:0; font-size:20px; line-height:1.2; }
  .rkrk-title #indrodution{ display:block; margin-top:6px; opacity:.8; }
  .rkrk-logo img{ height:56px; width:auto; object-fit:contain; display:block; }

  /* Learning */
  .rkrk-learning{ margin-top:14px; }

  /* Main grid */
  .rkrk-grid{ display:grid; gap:14px; margin-top:14px; grid-template-columns:1fr; }
  @media (min-width: 900px){
    .rkrk-grid{ grid-template-columns: 320px 1fr; align-items:start; }
    .rkrk-sidebar{ position:sticky; top:12px; }
  }

  .rkrk-main {   width: 100%; max-width: 100%; box-sizing: border-box; }
  

  /* Sidebar */
  .rkrk-sidebar #result{ display:block; margin-top:8px; }

  /* Question */
  .rkrk-qtitle{ font-weight:700; margin:10px 0 6px; font-size:16px; }
  .rkrk-qtext{ margin:0 0 12px; line-height:1.45; }

  /* Timers */
  #quiztimer-container, #timer-container{ width:100%; margin:8px 0; }
  #quiztimer-text, #timer-text{ display:block; text-align:center; font-weight:500; margin-bottom:6px; }
  #quiztimer-bar, #timer-bar{ height:10px; width:0%; background:#00c853; border-radius:999px; transition: width .15s linear, background-color .2s linear; }
  #quiztimer-container, #timer-container{ background:#eef1ee; border-radius:999px; padding:8px 10px; box-sizing:border-box; }
 
 /* Images responsive */
  #frage-bild, #loesung-bild{ max-width:100%; height:auto; }

  /* Answer area */
  .rkrk-fieldset{ border:1px solid var(--border); border-radius:12px; padding:12px; }
  .rkrk-fieldset legend{ padding:0 8px; font-weight:700; }

  /* Make each answer option tappable */
  .rkrk-options{ display:grid; gap:10px; grid-template-columns:1fr; margin-top:10px; }
  @media (min-width: 560px){ .rkrk-options{ grid-template-columns:1fr 1fr; } }
  .rkrk-opt{ display:flex; gap:10px; align-items:flex-start; padding:10px 10px; border:1px solid var(--border); border-radius:12px; background:#fff; }
  .rkrk-opt input{ margin-top:3px; transform: scale(1.2); }
  .rkrk-opt label{ cursor:pointer; line-height:1.25; }

  /* Name + drone */
  .rkrk-user{ display:grid; gap:10px; margin-top:12px; }
  .rkrk-field label{ display:block; margin-bottom:6px; font-weight:700; }
  .rkrk-field input, .rkrk-field select{
    width:100%; box-sizing:border-box; padding:11px 12px;
    border-radius:10px; border:1px solid var(--border);
    font-size:16px; background:#fff;
  }

  /* Navigation buttons */
  .rkrk-nav{ display:flex; gap:10px; flex-wrap:wrap; justify-content:space-between; margin-top:14px; }
  .rkrk-nav > span{ flex:1; min-width:160px; }
  .rkrk-nav button{ width:100%; }

  /* Solution */
  .rkrk-solution{ margin-top:14px; padding-top:12px; border-top:1px dashed var(--border); }

  /* Footer */
  .rkrk-footer{ margin-top:14px; font-size:13px; opacity:.85; }
  .rkrk-footer a{ color:#2a5b2a; text-decoration:none; }
  .rkrk-footer a:hover{ text-decoration:underline; }

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
	

	// ===========================================
	// Inhalt (responsive, gleiche IDs & JS-Hooks)
	// ===========================================

	echo "<div class='rkrk-wrap'>" . chr(10);

	// Topbar
	echo "<header class='rkrk-topbar rkrk-card'>" . chr(10);
	echo "  <div class='rkrk-title'>" . chr(10);
	echo "    <h1><span id='quiz-titel'>QuizTitel</span></h1>" . chr(10);
	echo "    <span id='indrodution'></span>" . chr(10);
	echo "  </div>" . chr(10);
	echo "  <div class='rkrk-logo'>" . chr(10);
	if ($logo=="rkrs") {
		echo "<img src='".$CalcIconLoc."rkrs_logo.png' id='logopic' name='logopic' width='247' height='80'>" . chr(10);
	} elseif ($logo=="rkrk") {
		echo "<img src='".$CalcIconLoc."rkrk_logo.png' id='logopic' name='logopic' height='80'>" . chr(10);
	} else {
		echo "<img src='".$CalcIconLoc."void.png' id='logopic' name='logopic' width='100'>" . chr(10);
	}
	echo "  </div>" . chr(10);
	echo "</header>" . chr(10);

	// Learning content
	echo "<section class='rkrk-card rkrk-learning'  id='rkrk-card'>" . chr(10);
	echo "  <span id='learning'></span>" . chr(10);
	echo "  <span id='submit_learn' hidden><br><br><button id='LernenAbschliessen' onclick='ShowNameFormAfterLearning()' style='".$ButtonStyle ."'>Lerninhalt abschliessen</button></span>" . chr(10);
	echo "</section>" . chr(10);

	// Main grid
	echo "<div class='rkrk-grid'>" . chr(10);

	// Sidebar / Resultate
	echo "<aside class='rkrk-card rkrk-sidebar'  id='rkrk-sidebar'>" . chr(10);
	echo "  <span id='result-titel'></span>" . chr(10);
	echo "  <span id='result'>Resultate</span>" . chr(10);
	echo "</aside>" . chr(10);

	// Main content
	echo "<main class='rkrk-card rkrk-main' id='rkrk-main'>" . chr(10);
	//Teilnehmerinfo
	echo "    <span id='Teilnehmerinfo' hidden>Teilnehmerinfo</span>" . chr(10);

	// Timers + Question
	echo "<div class='rkrk-question'>" . chr(10);
	echo "  <div id='quiztimer-container' hidden><span id='quiztimer-text'>Deine Zeit läuft...</span><div id='quiztimer-bar'></div></div>" . chr(10);
	echo "  <p class='rkrk-qtitle'><span id='Frage_Titel'>Frage_Titel</span></p>" . chr(10);
	echo "  <p class='rkrk-qtext'><span id='Frage'>Frage</span></p>" . chr(10);
	echo "  <div id='timer-container' hidden><span id='timer-text'>Deine Zeit läuft...</span><div id='timer-bar'></div></div>" . chr(10);
	echo "  <img src='".$CalcIconLoc."void.png' id='frage-bild' name='frage-bild' width='".$PicWidth."' height='".$PicHeight."' style='".$PicutreStyle."'><br/>" . chr(10);
	echo "</div>" . chr(10);

	// Form (Antworten + Hidden fields + Close submit)
	echo "<form method='post' action='". htmlspecialchars($_SERVER['REQUEST_URI']) . "' class='rkrk-form' style='text-align: center;'>" . chr(10);
	echo "  <span id='auswahl'>" . chr(10);
	echo "  <fieldset class='rkrk-fieldset'>" . chr(10);
	echo "    <legend>Deine Wahl<span id='mehrfach'></span>:</legend>" . chr(10);
	echo "    <div class='rkrk-options'>" . chr(10);

	// Ja/Nein
	echo "      <span id='a-ja' class='rkrk-opt'>" . chr(10);
	echo "        <input type='radio' id='ja' name='response' value='Ja' />" . chr(10);
	echo "        <label for='ja'><b>Ja</b></label>" . chr(10);
	echo "      </span>" . chr(10);
	echo "      <span id='a-nein' class='rkrk-opt'>" . chr(10);
	echo "        <input type='radio' id='nein' name='response' value='Nein' />" . chr(10);
	echo "        <label for='nein'><b>Nein</b></label>" . chr(10);
	echo "      </span>" . chr(10);

	// A-F
	echo "      <span id='a-a' class='rkrk-opt'>" . chr(10);
	echo "        <input type='radio' id='A' name='response' value='A' />" . chr(10);
	echo "        <label id='labelA' for='A'>A</label>" . chr(10);
	echo "      </span>" . chr(10);
	echo "      <span id='a-b' class='rkrk-opt'>" . chr(10);
	echo "        <input type='radio' id='B' name='response' value='B' />" . chr(10);
	echo "        <label id='labelB' for='B'>B</label>" . chr(10);
	echo "      </span>" . chr(10);
	echo "      <span id='a-c' class='rkrk-opt'>" . chr(10);
	echo "        <input type='radio' id='C' name='response' value='C' />" . chr(10);
	echo "        <label id='labelC' for='C'>C</label>" . chr(10);
	echo "      </span>" . chr(10);
	echo "      <span id='a-d' class='rkrk-opt'>" . chr(10);
	echo "        <input type='radio' id='D' name='response' value='D' />" . chr(10);
	echo "        <label id='labelD' for='D'>D</label>" . chr(10);
	echo "      </span>" . chr(10);
	echo "      <span id='a-e' class='rkrk-opt'>" . chr(10);
	echo "        <input type='radio' id='E' name='response' value='E' />" . chr(10);
	echo "        <label id='labelE' for='E'>E</label>" . chr(10);
	echo "      </span>" . chr(10);
	echo "      <span id='a-f' class='rkrk-opt'>" . chr(10);
	echo "        <input type='radio' id='F' name='response' value='F' />" . chr(10);
	echo "        <label id='labelF' for='F'>F</label>" . chr(10);
	echo "      </span>" . chr(10);

	echo "    </div>" . chr(10);
	echo "  </fieldset>" . chr(10);
	echo "  </span>" . chr(10);

	// User info
	echo "  <div class='rkrk-user'>" . chr(10);
	echo "    <div class='rkrk-field'>" . chr(10);
	echo "      <label for='Name' id='Name-Label'>Dein Name:</label>" . chr(10);
	echo "      <input type='text' id='Name' name='Name' value='".$Name."'>" . chr(10);
	echo "    </div>" . chr(10);

	// Drohnenauswahl nur beim Start anzeigen
	if ($Status == "" && $HasDroneTypes) {
		echo "    <div class='rkrk-field'>" . chr(10);
		echo "      <label for='Drone' id='Drone-Label'>Deine Drohne:</label>" . chr(10);
		echo "      <select id='Drone' name='Drone'>" . chr(10);
		echo "        <option value=''>Bitte wählen…</option>" . chr(10);
		foreach ($DroneTypes as $d) {
			$sel = ($Drone === $d) ? " selected" : "";
			echo "        <option value='".htmlspecialchars($d,ENT_QUOTES)."'".$sel.">".htmlspecialchars($d)."</option>" . chr(10);
		}
		echo "      </select>" . chr(10);
		echo "    </div>" . chr(10);
	} elseif (!$HasDroneTypes) {
		echo "<input type='hidden' id='Drone' name='Drone' value='Alle'>" . chr(10);
	}

	echo "  </div>" . chr(10);

	// Hidden fields
	echo "  <input type='text' id='AnzFragen' name='AnzFragen' hidden>" . chr(10);
	echo "  <input type='text' id='RichtigBeantwortet' name='RichtigBeantwortet' hidden>" . chr(10);
	echo "  <input type='text' id='Erfuellt' name='Erfuellt' hidden>" . chr(10);
	echo "  <input type='text' id='Quiz' name='Quiz' hidden>" . chr(10);
	echo "  <input type='text' id='Status' name='Status' value='".$Status."' hidden>" . chr(10);
	echo "  <input type='text' id='Schulung' name='Schulung' value='".$Schulung."' hidden>" . chr(10);

	// Abschluss
	echo "  <input type='submit' id='close' value='Quiz Abschliessen' style='".$ButtonStyleRed ."' hidden>" . chr(10);
	echo "</form>" . chr(10);

	// Name fehlt Hinweis
	echo "<span id='noname' hidden><button id='noname_button' onclick='NameEingeben()' style='".$ButtonStyleRed ."'>Bitte Name vor dem Abschliessen eingeben.</button></span>" . chr(10);

	// Navigation
	echo "<div class='rkrk-nav'>" . chr(10);
	echo "  <span id='prev_button'><button id='prev' onclick='PrevQuestion()' style='".$ButtonStyle ."'>Zurück</button></span>" . chr(10);
	echo "  <span id='submit_button'><button id='submit' onclick='Submit()' style='".$ButtonStyle ."'>eingeben und auflösen</button></span>" . chr(10);
	echo "  <span id='next_button'><button id='next' onclick='NextQuestion()' style='".$ButtonStyle ."'>Weiter</button></span>" . chr(10);
	echo "</div>" . chr(10);

	// Lösung
	echo "<section class='rkrk-solution' id='rkrk-solution'>" . chr(10);
	echo "  <span id='loesungstext'>Lösungstext</span><br>" . chr(10);
	echo "  <span id='loesung' hidden>" . chr(10);
	echo "    <img src='".$CalcIconLoc."void.png' id='loesung-bild' name='loesung-bild' width='".$PicWidth."' style='".$PicutreStyle."'><br/><br/>" . chr(10);
	echo "    <span id='korrektur' hidden>Korrektur</span><br>" . chr(10);
	echo "  </span><br>" . chr(10);
	echo "</section>" . chr(10);

	// Debug
	echo "<section class='rkrk-solution' id='rkrk-debug'>" . chr(10);
	echo "  <div id='debug' name='debug' style='background-color: #f7e9e9;'></div>" . chr(10);
	echo "  <div id='debug1' name='debug1' style='background-color: #f7e9e9;'></div>" . chr(10);
	echo "  <div id='debug2' name='debug2' style='background-color: #f7e9e9;'></div>" . chr(10);
	echo "</section>" . chr(10);
	echo "</main>" . chr(10);

	echo "</div>" . chr(10); // grid
	// Footer
	echo "<section class='rkrk-solution' id='rkrk-debug'>" . chr(10);
	echo "<footer class='rkrk-footer'>" . chr(10);
	echo "  <div align='center'> <i>© " . date("Y") ." by ".$copyright."</i></div>" . chr(10);
	if ($Schulung) {
		echo "<div align='center' style='font-size:10'><a href='mailto:thomas.christen@drohnenrehkitzrettung.ch?subject=Rückmeldung Schlungen Drohnenrehkitzrettung Knonaueramt'>Rückmeldungen</a></div>" . chr(10);
		echo "<div align='center' style='font-size:10'> <i><a href='".$_SERVER['PHP_SELF']."?".$urladder."'>Weitere Schulungen</a></i></div>" . chr(10);
	} else {
		echo "<div align='center' style='font-size:10'><a href='mailto:thomas.christen@drohnenrehkitzrettung.ch?subject=Rückmeldung Quiz Drohnenrehkitzrettung Knonaueramt'>Rückmeldungen</a></div>" . chr(10);
		echo "<div align='center' style='font-size:10'> <i><a href='".$_SERVER['PHP_SELF']."?".$urladder."'>Weitere Quiz</a></i></div>" . chr(10);
	}
	echo "  <span id='buffer'></span>" . chr(10);
	echo "</footer>" . chr(10);
	echo "</section>" . chr(10);
	echo "</div>" . chr(10); // wrap


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

		document.getElementById("rkrk-card").style.display = "none";
		document.getElementById("rkrk-sidebar").style.display = "none";
		document.getElementById("rkrk-solution").style.display = "none";
		if (setting != "TEST") {
			document.getElementById("rkrk-debug").style.display = "none";
		}

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

    document.getElementById("rkrk-main").style.display = "block";
    document.getElementById("Name").style.display = "block";
    document.getElementById("Name-Label").style.display = "block";

    document.getElementById("rkrk-card").style.display = "none";
    document.getElementById("rkrk-sidebar").style.display = "none";
	document.getElementById("rkrk-solution").style.display = "none";
    if (setting != "TEST") {
		document.getElementById("rkrk-debug").style.display = "none";
	}
	
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
		document.getElementById("rkrk-sidebar").style.display = "none";
        Status = "Learning";
        ShowHideQuestionFields('none');
        return;
    } else {
		document.getElementById("rkrk-sidebar").style.display = "block";
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
	document.getElementById("rkrk-main").style.display = 'none';
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

	// Learning section    
	document.getElementById("rkrk-card").style.display = "none";
	document.getElementById("rkrk-solution").style.display = "none";

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
        document.getElementById("rkrk-solution").style.display = 'none';
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

        document.getElementById("rkrk-solution").style.display = 'block';
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
		document.getElementById("rkrk-card").style.display = "none";
		document.getElementById("rkrk-sidebar").style.display = "none";
		document.getElementById("rkrk-solution").style.display = "none";
		document.getElementById("rkrk-main").style.display = 'none';
		if (setting != "TEST") {
			document.getElementById("rkrk-debug").style.display = "none";
		}
    } else {
        QuizListText = "Willkommen in unserer Quiz-Ecke.<br><br>" +
            "Wir haben hier interessante und überraschende Online-Quiz mit vielen lehrreichen Informationen über die Rehkitzrettung zusammengestellt.<br><br>" +
            "Wähle dir ein Quiz und prüfe dein Wissen!<br><br>Viel Erfolg.<br><br>";
		document.getElementById("rkrk-card").style.display = "none";
		document.getElementById("rkrk-sidebar").style.display = "none";
		document.getElementById("rkrk-solution").style.display = "none";
		document.getElementById("rkrk-debug").style.display = "none";
		document.getElementById("rkrk-main").style.display = 'none';
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

            QuizListText += "<br><p>" + qs["Kurzbeschreibung"] + "</p><br><br><br>";
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
