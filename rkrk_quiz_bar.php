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
	
</style>	
<?php	
	// Anzeige der Antworten
	$ShowBar = true;

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

	if (isset($_POST["Status"])) {
		$Status = $_POST["Status"];
	} else	{
		$Status = "";
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

	$PicutreStyle ="border-radius: 20px;  border: none; object-fit: cover;";
	$ButtonStyle ="border-radius: 3px;  border: none;  color: white;  background: #98B489; padding: 10px;  text-align: center;  text-decoration: none;  display: inline-block;  font-size: 16px;  margin: 4px 2px;  cursor: pointer";
	$ButtonStyleRed ="border-radius: 3px;  border: none;  color: white;  background: #b30000; padding: 10px;  text-align: center;";  
	$ButtonStyleAnswer ="border-radius: 5px;  border: 1px solid white; color: white;  background: #b30000; padding: 10px;  text-align: center;width: 100%;";  
	
	$PicWidth = '600';
	$PicHeight = '400';
	$ColWidth=100;

	// ?=======================================
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
				foreach ($QSelection as $key => $Q) {
					$QSelection [$key]["Loesungsauswahl"] = explode("|", $Q["Loesungsauswahl"]);
					foreach ($QSelection [$key]["Loesungsauswahl"] as $kkey => $s) {
						$QSelection [$key]["Loesungsauswahl"][$kkey] = $s;
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
	
	$i=0;
	//echo "Setting: " . $setting . "<br>";
	foreach ($Order as $key => $Q) {
		//echo "Aud: " . $QSelection[$Q["key"]]["Audienz"] . " key: " . $Q["key"] . "<br>";
		if (
			($QSelection[$Q["key"]]["Aktiv"]=="Ja") and 
			( 
				(strpos($QSelection[$Q["key"]]["Audienz"], $setting) !== false)
				or ($QSelection[$Q["key"]]["Audienz"]=="Alle") 
				or ($setting == "TEST")
			  )
			)
		{
			$Questions[$i] = $QSelection[$Q["key"]];
			$Questions[$i]["QID"] = $Q["key"];
			$i++;
		}	
	}
	//pre_r($Questions[5]);

	// ===========================================
	// Inhalt
	// ===========================================
	
	//step 1
	//$tablewidth=1000;
	echo "<table width='".$tablewidth."'>" . chr(10);
	//ROW1
	echo "<tr><td style='vertical-align: top;'>" . chr(10);
	echo "<H1><span id='quiz-titel'>QuizTitel</span></H1>" . chr(10);
	echo "<span id='indrodution'>" . $Questionair["Einfuehrung"] . "</span>". chr(10);
	echo "</td ><td style='vertical-align: top; text-align: right;' witdh='250'>";
	if ($logo=="rkrs") {
		echo "<img src='".$CalcIconLoc."rkrs_logo.png' id='logopic' name='logopic' width='247' height='80'><br/><br/>" . chr(10);
	} elseif ($logo=="rkrk") {
		echo "<img src='".$CalcIconLoc."rkrk_logo.png' id='logopic' name='logopic' height='80'><br/><br/>" . chr(10);
	} else {
		echo "<img src='".$CalcIconLoc."void.png' id='logopic' name='logopic' witdh='100'><br/><br/>" . chr(10);
	}	
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
	echo "<p><span id='Frage_Titel'>Frage_Titel</span></p>" . chr(10);
	echo "<p><span id='Frage'>Frage</span></p>" . chr(10);
	//echo "Infrarotbild:<br>" . chr(10);  
	echo "<img src='".$CalcIconLoc."void.png' id='frage-bild' name='frage-bild' width='".$PicWidth."' height='".$PicHeight."' style='".$PicutreStyle."'><br/>" . chr(10);
	echo "</td></tr>";
	echo "<tr><td colspan=3>";
	echo "<span id='auswahl'>";  
	echo "<fieldset  style='width:".$PicWidth."'>" . chr(10);
  	echo "<legend>Deine Wahl:</legend>" . chr(10);
	//bar buttons
	echo "<button id='answer_ja' onclick='SetButtonColor(".'"Ja"'.")' style='".$ButtonStyleAnswer ."'>Ja</button>" . chr(10);
	echo "<button id='answer_nein' onclick='SetButtonColor(".'"Nein"'.")' style='".$ButtonStyleAnswer ."'>Nein</button>" . chr(10);
	echo "<button id='answer_A' onclick='SetButtonColor(" . '"A"' . ")' style='".$ButtonStyleAnswer ."'>A</button>" . chr(10);
	echo "<button id='answer_B' onclick='SetButtonColor(" . '"B"' . ")' style='".$ButtonStyleAnswer ."'>B</button>" . chr(10);
	echo "<button id='answer_C' onclick='SetButtonColor(" . '"C"' . ")' style='".$ButtonStyleAnswer ."'>C</button>" . chr(10);
	echo "<button id='answer_D' onclick='SetButtonColor(" . '"D"' . ")' style='".$ButtonStyleAnswer ."'>D</button>" . chr(10);
	echo "<button id='answer_E' onclick='SetButtonColor(" . '"E"' . ")' style='".$ButtonStyleAnswer ."'>E</button>" . chr(10);
	echo "<button id='answer_F' onclick='SetButtonColor(" . '"F"' . ")' style='".$ButtonStyleAnswer ."'>F</button>" . chr(10);
	//radio buttons
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
	echo "<form  method='post' action='". htmlspecialchars($_SERVER['REQUEST_URI']) . "' style='text-align: center;'>" . chr(10);
	echo "  <span id='Name-Label'><br>Dein Name: </span><input type='text' id='Name' name='Name' value='".$Name."'>" . chr(10);
	echo "  <input type='text' id='AnzFragen' name='AnzFragen' hidden>" . chr(10);
	echo "  <input type='text' id='RichtigBeantwortet' name='RichtigBeantwortet' hidden>" . chr(10);
	echo "  <input type='text' id='Erfuellt' name='Erfuellt' hidden>" . chr(10);
	echo "  <input type='text' id='Quiz' name='Quiz' hidden>" . chr(10);
	echo "  <input type='text' id='Status' name='Status' value='".$Status."' hidden>" . chr(10);
	echo "  <input type='submit' id='close' value='Quiz Abschliessen' style='".$ButtonStyleRed ."'>" . chr(10);
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
	echo "<img src='".$CalcIconLoc."void.png' id='loesung-bild' name='loesung-bild' width='".$PicWidth."' height='".$PicHeight."' style='".$PicutreStyle."'><br/><br/>" . chr(10);
	echo "<span id='korrektur'>Korrektur</span><br>" . chr(10);
	echo "</span><br>" . chr(10);
	echo "</td></tr>";
	echo "<tr><td colspan=3>";
	echo "<div  id='debug' name='debug' style='background-color: #f7e9e9;'></div>  ";
	echo "<div  id='debug1' name='debug1' style='background-color: #f7e9e9;'></div>  ";
	echo "<div  id='debug2' name='debug2' style='background-color: #f7e9e9;'></div>  ";
	echo "</td></tr>";
	echo "<tr><td colspan=3>";
	echo "<div align='center'> <i>© " . date("Y") ." by ".$copyright."</i></div>" . chr(10);
	echo "<div align='center' style='font-size:10'><a href='mailto:thomas.christen@drohnenrehkitzrettung.ch?subject=Rückmeldung Quiz Drohnenrehkitzrettung Knonaueramt'>Rückmeldungen</i></a></div>" . chr(10);
	echo "<div align='center' style='font-size:10'> <i><a href='".$_SERVER['PHP_SELF']."?".$urladder."'>Weitere Quiz</a></i>" . chr(10);
	echo "</td></tr>";
	echo "<tr><td colspan=3>";
	echo "</td></tr>";
	echo "</table>" . chr(10);
	
//	pre_r($QuizList);
?>
<script type='text/javascript'>
<?php
//echo json_encode($DroneData);
echo "var QuizList = ". json_encode($QuizList) . ";\n"; 
echo "var Questionair = ". json_encode($Questionair) . ";\n"; 
echo "var Questions = ". json_encode($Questions) . ";\n"; 
echo "var CalcIconLoc = ". json_encode($CalcIconLoc) . ";\n"; 
echo "var QuizIconLoc = ". json_encode($QuizIconLoc.$Questionair["DriveLocation"]) . ";\n"; 
echo "var setting = '". $setting . "';\n";
echo "var set_quiz = '". $set_quiz . "';\n";
echo "var urladder = '". $urladder . "';\n";
echo "var allquestions = '". ($mode=="Korrektur")  . "';\n";
echo "var PicHeight = ". $PicHeight  . ";\n";
echo "var NoQuiz = " . $NoQuiz . ";\n";
echo "var Status = '" . $Status . "';\n";
echo "var FragenRichtig = '" . $FragenRichtig . "';\n";
echo "var AnzFragen = '" . $AnzFragen . "';\n";
echo "var Erfuellt = '" . $Erfuellt . "';\n";
echo "var Quiz = '" . $Quiz . "';\n";
echo "var ShowBar = ".$ShowBar.";\n";


?>
var AtQuestion=1;
var url = window.location.href.split('?')[0];

if (Questions.length>0) {
	if ((Questions.length<Questionair["AnzQuestions"]) || (allquestions)) {
		var MaxQuestions= Questions.length;
	} else {
		var MaxQuestions= Questionair["AnzQuestions"];
	} 
}	else {
		var MaxQuestions= 0;
}		

if (window.screen.width<700) {
	//alert ("width: " + window.screen.width + " height " + window.screen.height);
	//document.getElementById('frage-bild').style.width = window.screen.width-100;
}
//Colors
const red = "#fc2222";
const grey = "#44444";
const green = "#67b516";

//Debug Outputs
//document.write("MaxQ:" + MaxQuestions);

//track antworten
// Select all inputs with name="response"
var response_radios = document.querySelectorAll("input[name=\"response\"]");

// Use Array.forEach to add an event listener to each radio element.
response_radios.forEach(function(response_radios) {
  response_radios.addEventListener('change', function() {
    let valueName = document.querySelector('input[name="response"]:checked').value;
    //alert("Antwort:" + valueName)
	Questions[AtQuestion]["Antwort"]=valueName;
	document.getElementById("submit_button").style.display= 'block';
	//UpdateButton (AtQuestion);
  })
});

DoSetup();

function DoSetup() {
	if (Status=='resultat') {
		document.getElementById("quiz-titel").innerHTML= Questionair["Titel"] ;
		//Alle Fragen beantwortet
		//alert("Alles Beantwortet");
		Auswertung ="Super, du hast alle Fragen beantwortet!<br><br>";
		Auswertung += "Du hast <b>" + FragenRichtig + "</b> von <b>" + AnzFragen + "</b> richtig beanwortet.<br>";
		Auswertung += "Zum Erfüllen brauchst du " + Math.floor(Erfuellt*100) + "%.<br><br>";
		//if (Math.floor(FragenRichtig/MaxQuestions)>=Erfuellt) {
		//alert ("Richtig: " + FragenRichtig + " AnzFragen: " + AnzFragen + " Erfüllt: " + Erfuellt);
		if (Math.floor(FragenRichtig/AnzFragen)>=Erfuellt) {
			// Erfüllt
			Auswertung += "Herzliche Gratulation! Du hast den Test erfolgreich absolviert!<br>";
		} else {
			// Nicht Erfüllt
			Auswertung += "Leider hat es diesmal nicht gereicht. Möchstest du das Quiz wiederholen? <a href='"+url+"?quiz="+Quiz+urladder+"'>Quiz wiederholen</a><br>";
		}
		Auswertung += "<br><br>Ich möchte ein anderes Quiz machen? <a href='"+url+"?"+urladder+"'>Neues Quiz</a><br>";

		document.getElementById("indrodution").innerHTML = Auswertung;
		document.getElementById("indrodution").style.fontSize  = "12pt";
		document.getElementById("Name").style.display= 'none';
		document.getElementById("Name-Label").style.display= 'none';

		HideQuestionFields();
	} else {
		if ((set_quiz=="") || (NoQuiz==1)) {
			//alert("show quiz");
			document.getElementById("quiz-titel").innerHTML= "Quiz Ecke Rehktizrettung Knonaueramt";
			ShowQuizList();
		} else {
			document.getElementById("quiz-titel").innerHTML= Questionair["Titel"] ;
			document.getElementById("indrodution").style.fontSize  = "10pt";

			GoToQuestion(0);
			Output="";

			//Write results buttons
			QNum=1;
			Questions.forEach((qu) => {
				//WriteButton (1, "Frage 1", red);
				//if (Questionair["AnzQuestions"]>=QNum) {
				if (MaxQuestions>=QNum) {
					Output += GetButton ((QNum-1), "Frage "+ QNum, grey);
					//WriteButton (3, "Frage 3", green);
				}
				QNum++;
			});
			document.getElementById("result").innerHTML= Output;
			document.getElementById("loesungstext").innerHTML= "";
		}
		}
		SetButtonColor("");
}

function SelectQuestion(ToQuestion) {
	GoToQuestion(ToQuestion);
	LockWahl();
	ShowResult();
}

function Submit() {
	if (Questions[AtQuestion]["Antwort"]!="") {
		Questions[AtQuestion]["Locked"]="Ja";
		document.getElementById("submit_button").style.display= 'none';
	}	
	LockWahl();
	UpdateButton (AtQuestion);
	ShowResult();
	CheckEndOfQuiz() ;
}

function NextQuestion() {
	if (AtQuestion<(MaxQuestions-1)) {AtQuestion = AtQuestion+1};
	GoToQuestion(AtQuestion);
	UpdateButton (AtQuestion);
	LockWahl();
	ShowResult();
}

function PrevQuestion() {
	if (AtQuestion>0) {AtQuestion= AtQuestion-1};
	GoToQuestion(AtQuestion);
	UpdateButton (AtQuestion);
	LockWahl();
	ShowResult();
}

function LockWahl() {
	if (Questions[AtQuestion]["Antwort"]=="") {
		document.querySelector('input[name="response"]:checked').checked = false;
	} else {
		var radios = document.getElementsByName("response");
		for (var j = 0; j < radios.length; j++) {
			if (radios[j].value == Questions[AtQuestion]["Antwort"]) {
				//alert ("SetValue: " + Questions[AtQuestion]["Antwort"]);
				radios[j].checked = true;
				break;
			}
		}
	}

	if (Questions[AtQuestion]["Locked"]=="Ja") {
		//radio button
		document.getElementById("ja").disabled = true;
		document.getElementById("nein").disabled = true;
		document.getElementById("A").disabled = true;
		document.getElementById("B").disabled = true;
		document.getElementById("C").disabled = true;
		document.getElementById("D").disabled = true;
		document.getElementById("E").disabled = true;
		document.getElementById("F").disabled = true;
		//bar button
		document.getElementById("answer_ja").disabled = true;
		document.getElementById("answer_nein").disabled = true;
		document.getElementById("answer_A").disabled = true;
		document.getElementById("answer_B").disabled = true;
		document.getElementById("answer_C").disabled = true;
		document.getElementById("answer_D").disabled = true;
		document.getElementById("answer_E").disabled = true;
		document.getElementById("answer_F").disabled = true;
	} else {
		//radio button
		document.getElementById("ja").disabled = false;
		document.getElementById("nein").disabled = false;
		document.getElementById("A").disabled = false;
		document.getElementById("B").disabled = false;
		document.getElementById("C").disabled = false;
		document.getElementById("D").disabled = false;
		document.getElementById("E").disabled = false;
		document.getElementById("F").disabled = false;
		//bar button
		document.getElementById("answer_ja").disabled = false;
		document.getElementById("answer_nein").disabled = false;
		document.getElementById("answer_A").disabled = false;
		document.getElementById("answer_B").disabled = false;
		document.getElementById("answer_C").disabled = false;
		document.getElementById("answer_D").disabled = false;
		document.getElementById("answer_E").disabled = false;
		document.getElementById("answer_F").disabled = false;
	}	
}

function SetButtonColor(answer) {
	SelectColor = "#4f8a11";
	DeselectColor = "#d9d9d9";
	NoSelectColor = "#bfbfbf";
	//alert ("Answer:" + answer);

	switch (answer) {
	  case "Ja":
		document.getElementById("answer_ja").style.backgroundColor = SelectColor;
		document.getElementById("answer_nein").style.backgroundColor = DeselectColor;
		document.getElementById("answer_A").style.backgroundColor = DeselectColor;
		document.getElementById("answer_B").style.backgroundColor = DeselectColor;
		document.getElementById("answer_C").style.backgroundColor = DeselectColor;
		document.getElementById("answer_D").style.backgroundColor = DeselectColor;
		document.getElementById("answer_E").style.backgroundColor = DeselectColor;
		document.getElementById("answer_F").style.backgroundColor = DeselectColor;
		break;
	  case "Nein":
		document.getElementById("answer_ja").style.backgroundColor = DeselectColor;
		document.getElementById("answer_nein").style.backgroundColor = SelectColor;
		document.getElementById("answer_A").style.backgroundColor = DeselectColor;
		document.getElementById("answer_B").style.backgroundColor = DeselectColor;
		document.getElementById("answer_C").style.backgroundColor = DeselectColor;
		document.getElementById("answer_D").style.backgroundColor = DeselectColor;
		document.getElementById("answer_E").style.backgroundColor = DeselectColor;
		document.getElementById("answer_F").style.backgroundColor = DeselectColor;
		break;
	  case "A":
		document.getElementById("answer_ja").style.backgroundColor = DeselectColor;
		document.getElementById("answer_nein").style.backgroundColor = DeselectColor;
		document.getElementById("answer_A").style.backgroundColor = SelectColor;
		document.getElementById("answer_B").style.backgroundColor = DeselectColor;
		document.getElementById("answer_C").style.backgroundColor = DeselectColor;
		document.getElementById("answer_D").style.backgroundColor = DeselectColor;
		document.getElementById("answer_E").style.backgroundColor = DeselectColor;
		document.getElementById("answer_F").style.backgroundColor = DeselectColor;
		break;
	  case "B":
		document.getElementById("answer_ja").style.backgroundColor = DeselectColor;
		document.getElementById("answer_nein").style.backgroundColor = DeselectColor;
		document.getElementById("answer_A").style.backgroundColor = DeselectColor;
		document.getElementById("answer_B").style.backgroundColor = SelectColor;
		document.getElementById("answer_C").style.backgroundColor = DeselectColor;
		document.getElementById("answer_D").style.backgroundColor = DeselectColor;
		document.getElementById("answer_E").style.backgroundColor = DeselectColor;
		document.getElementById("answer_F").style.backgroundColor = DeselectColor;
		break;
	  case "C":
		document.getElementById("answer_ja").style.backgroundColor = DeselectColor;
		document.getElementById("answer_nein").style.backgroundColor = DeselectColor;
		document.getElementById("answer_A").style.backgroundColor = DeselectColor;
		document.getElementById("answer_B").style.backgroundColor = DeselectColor;
		document.getElementById("answer_C").style.backgroundColor = SelectColor;
		document.getElementById("answer_D").style.backgroundColor = DeselectColor;
		document.getElementById("answer_E").style.backgroundColor = DeselectColor;
		document.getElementById("answer_F").style.backgroundColor = DeselectColor;
		break;
	  case "D":
		document.getElementById("answer_ja").style.backgroundColor = DeselectColor;
		document.getElementById("answer_nein").style.backgroundColor = DeselectColor;
		document.getElementById("answer_A").style.backgroundColor = DeselectColor;
		document.getElementById("answer_B").style.backgroundColor = DeselectColor;
		document.getElementById("answer_C").style.backgroundColor = DeselectColor;
		document.getElementById("answer_D").style.backgroundColor = SelectColor;
		document.getElementById("answer_E").style.backgroundColor = DeselectColor;
		document.getElementById("answer_F").style.backgroundColor = DeselectColor;
		break;
	  case "E":
		document.getElementById("answer_ja").style.backgroundColor = DeselectColor;
		document.getElementById("answer_nein").style.backgroundColor = DeselectColor;
		document.getElementById("answer_A").style.backgroundColor = DeselectColor;
		document.getElementById("answer_B").style.backgroundColor = DeselectColor;
		document.getElementById("answer_C").style.backgroundColor = DeselectColor;
		document.getElementById("answer_D").style.backgroundColor = DeselectColor;
		document.getElementById("answer_E").style.backgroundColor = SelectColor;
		document.getElementById("answer_F").style.backgroundColor = DeselectColor;
		break;
	  case "F":
		document.getElementById("answer_ja").style.backgroundColor = DeselectColor;
		document.getElementById("answer_nein").style.backgroundColor = DeselectColor;
		document.getElementById("answer_A").style.backgroundColor = DeselectColor;
		document.getElementById("answer_B").style.backgroundColor = DeselectColor;
		document.getElementById("answer_C").style.backgroundColor = DeselectColor;
		document.getElementById("answer_D").style.backgroundColor = DeselectColor;
		document.getElementById("answer_E").style.backgroundColor = DeselectColor;
		document.getElementById("answer_F").style.backgroundColor = SelectColor;
		break;
	  default:
		document.getElementById("answer_ja").style.backgroundColor = NoSelectColor;
		document.getElementById("answer_nein").style.backgroundColor = NoSelectColor;
		document.getElementById("answer_A").style.backgroundColor = NoSelectColor;
		document.getElementById("answer_B").style.backgroundColor = NoSelectColor;
		document.getElementById("answer_C").style.backgroundColor = NoSelectColor;
		document.getElementById("answer_D").style.backgroundColor = NoSelectColor;
		document.getElementById("answer_E").style.backgroundColor = NoSelectColor;
		document.getElementById("answer_F").style.backgroundColor = NoSelectColor;
	}	
}

function GoToQuestion(ToQuestion) {
	
	if (ToQuestion<1) {
		document.getElementById("prev_button").style.display= 'none';
	} else {
		document.getElementById("prev_button").style.display= 'block';
	}

	if (ToQuestion<(MaxQuestions-1))
	{
		document.getElementById("next_button").style.display= 'block';
	} else {
		document.getElementById("next_button").style.display= 'none';
	}

	if (Questions[ToQuestion]["Locked"]=="Ja")
	{
		document.getElementById("submit_button").style.display= 'none';
	} else {
		if (Questions[ToQuestion]["Antwort"]=="")
		{
			document.getElementById("submit_button").style.display= 'none';
		} else {
			document.getElementById("submit_button").style.display= 'block';
		}
		//document.getElementById("submit_button").style.display= 'block';
	} 
	//document.getElementById("AnzFragen").style.display= 'block';
	//document.getElementById("RichtigBeantwortet").style.display= 'block';
	document.getElementById("close").style.display= 'none';

	//select Auswahl
	if ((Questions[ToQuestion]["Loesung"]=="Ja") || (Questions[ToQuestion]["Loesung"]=="Nein")) {
		//radio button
		document.getElementById("a-ja").style.display= 'block';
		document.getElementById("a-nein").style.display= 'block';
		document.getElementById("a-a").style.display= 'none';
		document.getElementById("a-b").style.display= 'none';
		document.getElementById("a-c").style.display= 'none';
		document.getElementById("a-d").style.display= 'none';
		document.getElementById("a-e").style.display= 'none';
		document.getElementById("a-f").style.display= 'none';
		//bar button
		document.getElementById("answer_ja").style.display= 'block';
		document.getElementById("answer_nein").style.display= 'block';
		document.getElementById("answer_A").style.display= 'none';
		document.getElementById("answer_B").style.display= 'none';
		document.getElementById("answer_C").style.display= 'none';
		document.getElementById("answer_D").style.display= 'none';
		document.getElementById("answer_E").style.display= 'none';
		document.getElementById("answer_F").style.display= 'none';
	} else {
		document.getElementById("a-ja").style.display= 'none';
		document.getElementById("a-nein").style.display= 'none';
		document.getElementById("answer_ja").style.display= 'none';
		document.getElementById("answer_nein").style.display= 'none';

		if (Questions[ToQuestion]["Loesungsauswahl"][0]!=null) {
			document.getElementById("a-a").style.display= 'block';
			//document.getElementById("t-a").style.display= 'block';
			document.getElementById("labelA").innerHTML= "<b>A</b> <i>  "+Questions[ToQuestion]["Loesungsauswahl"][0]+"</i><br><br>";
			document.getElementById("answer_A").style.display= 'block';
			document.getElementById("answer_A").innerHTML = "<b>A</b> <i>  "+Questions[ToQuestion]["Loesungsauswahl"][0]+"</i>";
		} else {
			document.getElementById("a-a").style.display= 'none';
			//document.getElementById("t-a").style.display= 'none';
			document.getElementById("labelA").innerHTML= '';
			document.getElementById("answer_A").style.display= 'none';
		}
		if (Questions[ToQuestion]["Loesungsauswahl"][1]!=null) {
			document.getElementById("a-b").style.display= 'block';
			//document.getElementById("t-b").style.display= 'block';
			document.getElementById("labelB").innerHTML= "<b>B</b> <i>  "+Questions[ToQuestion]["Loesungsauswahl"][1]+"</i><br><br>";
			document.getElementById("answer_B").style.display= 'block';
			document.getElementById("answer_B").innerHTML = "<b>B</b> <i>  "+Questions[ToQuestion]["Loesungsauswahl"][1]+"</i>";
		} else {
			document.getElementById("a-b").style.display= 'none';
			//document.getElementById("t-b").style.display= 'none';
			document.getElementById("labelB").innerHTML= '';
			document.getElementById("answer_B").style.display= 'none';
		}
		if (Questions[ToQuestion]["Loesungsauswahl"][2]!=null) {
			document.getElementById("a-c").style.display= 'block';
			//document.getElementById("t-c").style.display= 'block';
			document.getElementById("labelC").innerHTML= "<b>C</b> <i>  "+Questions[ToQuestion]["Loesungsauswahl"][2]+"</i><br><br>";
			document.getElementById("answer_C").style.display= 'block';
			document.getElementById("answer_C").innerHTML = "<b>C</b> <i>  "+Questions[ToQuestion]["Loesungsauswahl"][2]+"</i>";
		} else {
			document.getElementById("a-c").style.display= 'none';
			//document.getElementById("t-c").style.display= 'none';
			document.getElementById("labelC").innerHTML= '';
			document.getElementById("answer_C").style.display= 'none';
		}
		if (Questions[ToQuestion]["Loesungsauswahl"][3]!=null) {
			document.getElementById("a-d").style.display= 'block';
			//document.getElementById("t-d").style.display= 'block';
			document.getElementById("labelD").innerHTML= "<b>D</b> <i>  "+Questions[ToQuestion]["Loesungsauswahl"][3]+"</i><br><br>";
			document.getElementById("answer_D").style.display= 'block';
			document.getElementById("answer_D").innerHTML = "<b>D</b> <i>  "+Questions[ToQuestion]["Loesungsauswahl"][3]+"</i>";
		} else {
			document.getElementById("a-d").style.display= 'none';
			//document.getElementById("t-d").style.display= 'none';
			document.getElementById("labelD").innerHTML= '';
			document.getElementById("answer_D").style.display= 'none';
		}
		if (Questions[ToQuestion]["Loesungsauswahl"][4]!=null) {
			document.getElementById("a-e").style.display= 'block';
			//document.getElementById("t-e").style.display= 'block';
			document.getElementById("labelE").innerHTML= "<b>E</b> <i>  "+Questions[ToQuestion]["Loesungsauswahl"][4]+"</i><br><br>";
			document.getElementById("answer_E").style.display= 'block';
			document.getElementById("answer_E").innerHTML = "<b>D</b> <i>  "+Questions[ToQuestion]["Loesungsauswahl"][4]+"</i>";
		} else {
			document.getElementById("a-e").style.display= 'none';
			//document.getElementById("t-e").style.display= 'none';
			document.getElementById("labelE").innerHTML= '';
			document.getElementById("answer_E").style.display= 'none';
		}
		if (Questions[ToQuestion]["Loesungsauswahl"][5]!=null) {
			document.getElementById("a-f").style.display= 'block';
			//document.getElementById("t-f").style.display= 'block';
			document.getElementById("labelF").innerHTML= "<b>F</b> <i>  "+Questions[ToQuestion]["Loesungsauswahl"][5]+"</i><br><br>";
			document.getElementById("answer_F").style.display= 'block';
			document.getElementById("answer_F").innerHTML = "<b>F</b> <i>  "+Questions[ToQuestion]["Loesungsauswahl"][5]+"</i>";
		} else {
			document.getElementById("a-f").style.display= 'none';
			//document.getElementById("t-f").style.display= 'none';
			document.getElementById("labelF").innerHTML= '';
			document.getElementById("answer_F").style.display= 'none';
		}
	document.getElementById("Status").value= status;
	}

	AtQuestion= ToQuestion;

	document.getElementById("Frage_Titel").innerHTML = "Frage "+ (ToQuestion+1);
	document.getElementById("Frage_Titel").style.fontSize = "12px";
	document.getElementById("Frage").innerHTML = "<b>" + Questions[ToQuestion]["Frage"] + "</b>";
	document.getElementById("Frage").style.fontSize = "16px";
	document["frage-bild"].style.display= 'block';
	if (Questions[ToQuestion]["Frage-Bild"]!="") {
		document["frage-bild"].src = QuizIconLoc+Questions[ToQuestion]["Frage-Bild"];
		if (Questions[ToQuestion]["Bildformat"]=="schmal") {
			document.getElementById("frage-bild").height = Math.floor(PicHeight/2);
		} else {
			document.getElementById("frage-bild").height = PicHeight;
		}
	} else {
		document["frage-bild"].style.display= 'none';
	}
	if ((status=='resultat')) {
		document.getElementById("submit_button").style.display= 'none';
		if (document.getElementById("Name").value=="") {
			document.getElementById("noname").style.display= 'block';
			document.getElementById("close").style.display= 'none';
		} else {
			document.getElementById("noname").style.display= 'none';
			document.getElementById("close").style.display= 'block';
		}
	}

	if (setting== "TEST") {
		document.getElementById("debug").innerHTML = "Frage: " +ToQuestion;
	}
}

function GetButton (nr, name, color){
	let ButtonStyle ="border-radius: 3px;  border: none;  color: white;  background: "+color+"; padding: 10px;  text-align: center;  text-decoration: none;  display: inline-block;  font-size: 16px;  margin: 4px 2px;  cursor: pointer";
	//alert ("button " +name);
	ButtonText="<button id='F"+nr+"' value='Hover' style='"+ButtonStyle+"' onclick='SelectQuestion("+nr+")'>"+name+"</button>";
	//document.getElementById(id).style.background=color;
	return ButtonText;
}

function UpdateButton (nr){
	//alert("SetButton:" + nr);
	//alert("Aktuelle Antwort: " + Questions[AtQuestion]["Antwort"]);
	
	if ((Questions[AtQuestion]["Antwort"]=="") || (Questions[AtQuestion]["Locked"]=="Nein"))
	{
		document.getElementById("F"+nr).style.background=grey;	
		//alert("Keine Antwort");
		if (setting== "TEST") {
			document.getElementById("debug2").innerHTML = "Keine Antwort";
		}	
	} else if (Questions[AtQuestion]["Antwort"]==Questions[AtQuestion]["Loesung"]) {
		document.getElementById("F"+nr).style.background=green;	
		//alert("Antwort korrekt");
	} else {
		document.getElementById("F"+nr).style.background=red;	
		//alert("Antwort nicht korrekt");
	}
	if (setting== "TEST") {
		document.getElementById("debug").innerHTML = "Frage:" + nr + " Antwort: " + Questions[AtQuestion]["Antwort"] + " Lösung " + Questions[AtQuestion]["Loesung"];
	}

}

function ShowResult (){
	if ((Questions[AtQuestion]["Antwort"]=="")|| (Questions[AtQuestion]["Locked"]=="Nein"))
	{ 
		//alert("Hide Results");
		document["loesung-bild"].src = "";
		document.getElementById("loesungstext").innerHTML = "";
		document.getElementById("loesung").style.display= 'none';
	} else {
		//alert("Show Results");
		if (Questions[AtQuestion]["Loesungs-Bild"]!="") {
			document["loesung-bild"].src = QuizIconLoc+Questions[AtQuestion]["Loesungs-Bild"];
			document["loesung-bild"].style.display= 'block';
		} else {
			document["loesung-bild"].style.display= 'none';
		}		
		document.getElementById("loesungstext").innerHTML = Questions[AtQuestion]["Loesungstext"];
		if (Questions[AtQuestion]["Antwort"]==Questions[AtQuestion]["Loesung"]) {
			document.getElementById("korrektur").innerHTML = "Gratuliere, deine Anwort ist richtig!";
		} else {	
			document.getElementById("korrektur").innerHTML = "Tut mir leid, deine Anwort ist nicht korrekt!";
		}
		document.getElementById("loesung").style.display= 'block';
	}
}

function HideQuestionFields() {
	document.getElementById("frage-bild").style.display= 'none';
	document.getElementById("Frage_Titel").style.display= 'none';
	document.getElementById("Frage").style.display= 'none';
	document.getElementById("auswahl").style.display= 'none';
	document.getElementById("prev_button").style.display= 'none';
	document.getElementById("submit_button").style.display= 'none';
	document.getElementById("next_button").style.display= 'none';
	document.getElementById("loesung").style.display= 'none';
	document.getElementById("frage-bild").style.display= 'none';
	document.getElementById("loesung-bild").style.display= 'none';
	document.getElementById("loesungstext").style.display= 'none';
	document.getElementById("korrektur").style.display= 'none';
	document.getElementById("result").style.display= 'none';
	document.getElementById("result-titel").style.display= 'none';
	document.getElementById("close").style.display= 'none';
}

function ShowQuizList() {
	QuizListText = "Willkommen in unserer Quiz-Ecke.<br><br>Wir haben hier interessante und überraschende Online-Quiz mit vielen lehrreichen Informationen über die Rehkitzrettung zusammengestellt.<br><br>Wähle dir ein Quiz und prüfe dein Wissen!<br><br>Viel Erfolg.<br><br>";
	QuizListText += "<ul>";
	
	QuizList.forEach((qs) => {
		if (((qs["Locked"]=="Nein") && (qs["Aktiv"]=="Ja")) ||(setting== "TEST")) {
			if ((qs["Locked"]=="Ja") || (qs["Aktiv"]=="Nein")) {
				remark = "<i> - Nicht freigegeben</i>";
			} else {
				remark = "";
			}	
			QuizListText += "<li>";
			QuizListText += "<a href='"+url+"?quiz="+qs["Key"]+urladder+"'>"+qs["Titel"]+"</a>" + remark +"<br>";
			QuizListText += qs["Kurzbeschreibung"]+"<br><br>";
			QuizListText += "</li>";
		}	
	});
	QuizListText += "</ul>";
	document.getElementById("indrodution").innerHTML = QuizListText;
	document.getElementById("indrodution").style.fontSize  = "12pt";
	document.getElementById("Name").style.display= 'none';
	document.getElementById("Name-Label").style.display= 'none';
	
	HideQuestionFields();
}

// this function returns a promise that resolves after n milliseconds
const wait = (n) => new Promise((resolve) => setTimeout(resolve, n));

function CheckEndOfQuiz() {
	//prüfe ob alle Fragen beantwortet und eingegeben sind?
	var AllAnswered=true;
	var Richtig=0;
	var AnzFragen=0;
	var FragenBeantwortet=0;
	var Auswertung="";

	Questions.forEach((qu) => {
	  if ((qu["Locked"]=="Nein") && (qu["Aktiv"]=="Ja")) {
			//AllAnswered=false;
			//break;
		} else {
			FragenBeantwortet++;
			if (qu["Antwort"]==qu["Loesung"]) {
				Richtig++;
			}	
		}
		AnzFragen++;
	});
	
	//alert("Beantwortet: " + Richtig + "/ " + MaxQuestions + " Fragen beantwortet: " + FragenBeantwortet);
	
	//if (AllAnswered==true) {
	document.getElementById("AnzFragen").value= FragenBeantwortet;
	document.getElementById("RichtigBeantwortet").value= Richtig;
	document.getElementById("Erfuellt").value= Questionair["Erfuellt"];
	document.getElementById("Quiz").value= set_quiz;
	status= "";

	if (FragenBeantwortet==MaxQuestions) {
		status='fertig'
		document.getElementById("prev_button").style.display= 'none';
		document.getElementById("next_button").style.display= 'none';
		document.getElementById("submit_button").style.display= 'block';
		document.getElementById("submit_button").value= 'Quiz abschliessen';
	}

	if (status=='resultat') {
	}
	if (status=='fertig') {
		document.getElementById("submit_button").style.display= 'none';
		status='resultat'
		if (document.getElementById("Name").value=="") {
			document.getElementById("noname").style.display= 'block';
			document.getElementById("close").style.display= 'none';
		} else {
			document.getElementById("noname").style.display= 'none';
			document.getElementById("close").style.display= 'block';
		}
	}
	document.getElementById("Status").value= status;
}

function NameEingeben() {
		if (document.getElementById("Name").value=="") {
			document.getElementById("noname").style.display= 'block';
			document.getElementById("close").style.display= 'none';
		} else {
			document.getElementById("noname").style.display= 'none';
			document.getElementById("close").style.display= 'block';
		}
}	
</script>

</body>
</html>
