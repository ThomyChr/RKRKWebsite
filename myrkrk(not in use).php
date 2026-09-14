<?php
	session_start();
	include_once('scripts/rkrk_gen.php');
	include_once('scripts/rkrk_graphic_map_button.php');
	include_once('scripts/rkrk_csvfiles.php');
	include_once('scripts/rkrk_inputforms.php');
	include_once('scripts/rkrk_menu.php'); 
	include_once('rkrk_access_control.php'); 

	//check impropriate IP addresses accessing page
	CheckIPandExit();
	echo "" . chr(10);

	$pagetitle = "Dein persönlicher Zugang";
	headerpage($pagetitle , false);
	GrantAccess();
	CreateTopMenu();
	PrintRKRKHeader($pagetitle);
 
//////////////////////////////
// Definitions
//////////////////////////////

$ShowButton = false;

$csv_desc = ';';
$Contacts = csv_to_array($csvMissionLocation.$RKRKContactFile,$csv_desc);


///////////////////////////////
// Read entires
///////////////////////////////

//echo "User: " .	$_SESSION[$SessNme_RegisteredUser] . "<br>";
//echo "Initialen: " .	$_SESSION[$SessNme_RegisteredInitialen] . "<br>";
//echo "Name: " .	$_SESSION[$SessNme_RegisteredName] . "<br>";
//echo "Vorname: " .	$_SESSION[$SessNme_RegisteredVorname] . "<br>";
//echo "Regionen: " .	$_SESSION[$SessNme_SelRegions] . "<br>";

$PreSetUser =  $_SESSION[$SessNme_RegisteredName] . " " . $_SESSION[$SessNme_RegisteredVorname];$PreSetRegionen = $_SESSION[$SessNme_SelRegions];

//pre_R($InputUserInfo);
if (isset($_GET["Person"])) { 
	$Person= $_GET["Person"];
} elseif (isset($_POST["Person"])) { 
	$Person= $_POST["Person"];
} elseif (trim($PreSetUser)!="") {
	$Person= $PreSetUser;
} elseif ($InputUserInfo["Login"]["Benutzername"]!="") {
	$Person= $InputUserInfo["Login"]["Name"] . " " . $InputUserInfo["Login"]["Vorname"] ;
} else {
	$Person= "";
}


if (isset($_GET["Region"])) { 
	$Region= $_GET["Region"];
} elseif (isset($_POST["Region"])) { 
	$Region= $_POST["Region"];
} else {
	$Region= "";
}

if (isset($_GET["Reg"])) { 
	$Reg= $_GET["Reg"];
} elseif (isset($_POST["Reg"])) { 
	$Reg= $_POST["Reg"];
} elseif ($PreSetRegionen!="") {
	$Reg= $PreSetRegionen;
} else {
	$Reg= "";
}

if ($Reg!="") {
	foreach ($RegionSettings as $key => $line) {
		if ($Reg==$line["Reg"]) {
			$Region = $line["Text"];
		}
	}
} elseif  ($Region!="") {
	foreach ($RegionSettings as $key => $line) {
		if ($Region==$line["Text"]) {
			$Reg = $line["Reg"];
		}
	}
}


if ($Pers!="") {
	foreach ($Contacts as $key => $line) {
		if ($Pers==$line["Initialen"]) {
			$Person = $line["Name"];
		}
	}
} elseif ($Person!="") {
	foreach ($Contacts as $key => $line) {
		if ($Person==$line["Name"]) {
			$Pers = $line["Initialen"];
		}
	}
}

// Unterstüztung für vereinfachte Website-Handling
$SimpleView = ($InputUserInfo["Login"]["Level"]=="pub");

//echo "Region: ". $Region . "<br>";
//echo "Reg: ". $Reg . "<br>";
//echo "Person: ". $Person . "<br>";
//echo "Pers: ". $Pers . "<br>";

//////////////////////////////
// Load Flightplan
//////////////////////////////

	$csv_file= date('Y') .$RKRKFlightsCSV;
	$csv_deliminator = ";";

	//Read CSV file
	$Flightplans = csv_to_array($csvMissionLocation.$csv_file, $csv_deliminator );
	//pre_R($Flightplans);

	$PersFelderToday =array();
	$RegFelderToday =array();
	$PersFelderTomorrow =array();
	$RegFelderTomorrow =array();

	$Today = date("Y-m-d");
	$Tomorrow =date('Y-m-d', strtotime('tomorrow'));
	$InAWeek =date('Y-m-d', strtotime('+1 week'));

	foreach ($Flightplans as $key => $flight) {
		// HEUTE
		//echo $flight["Datum"] . " - " . $Today . " // " . $Pers. " - " . $flight["Pilot"] . " - " .$flight["Jaeger"]  . "<br>"; 
		if ($flight["Datum"] ==$Today) {
			// Nach Person
			if (
				($Pers==$flight["Pilot"])
				or ($Pers==$flight["Jaeger"])
				)
			{
				$PersFelderToday[$flight["Feld"]] = $flight["Feld"];
				//echo $flight["Datum"] . " - " . $Today . " // " . $Pers. " - " . $flight["Pilot"] . " - " .$flight["Jaeger"]  . "<br>"; 
			}
			// Nach Reg
			//echo "reg: " . $Reg; 
			//pre_R($RegionSettings[$flight["JG"]]["JGList"]);
			if (in_array($Reg,explode(",",$RegionSettings[$flight["JG"]]["JGList"]))) 
			{
				$RegFelderToday[$flight["Feld"]] = $flight["Feld"];
			}
		}
		//pre_r($PersFelderToday);
		//pre_r($RegFelderToday);
		// MORGEN
		if ($flight["Datum"] ==$Tomorrow) {
			// Nach Person
			if (
				($Pers==$flight["Pilot"])
				or ($Pers==$flight["Jaeger"])
				)
			{
				$PersFelderTomorrow[$flight["Feld"]] = $flight["Feld"];
			}
			// Nach Reg
			if (in_array($Reg,explode(",",$RegionSettings[$flight["JG"]]["JGList"]))) 
			{
				$RegFelderTomorrow[$flight["Feld"]] = $flight["Feld"];
			}
		}
	}
	$PersFelderToday = implode(" ",$PersFelderToday);
	$RegFelderToday =implode(" ",$RegFelderToday);
	$PersFelderTomorrow =implode(" ",$PersFelderTomorrow);
	$RegFelderTomorrow =implode(" ",$RegFelderTomorrow);

//////////////////////////////
// Functions
//////////////////////////////


	//Write to LOGfile
	WriteToLog("get", basename($_SERVER['PHP_SELF']), "öffnen ","","","");
	echo "<table>". chr(10);
	echo "<tr><td width=80%>". chr(10);
	if ($SimpleView) {
		echo "<i>Willkommen zu unserer Website. Gerne teilen wir relevante Information über unsere Rehkitzrettungsaktivitäten in der Region. Zur einfacheren Navigierung habe wir deine Jagdgebiete für die Dokumente eingetragen, falls du andere Gebiete sehen möchtest, kannst du sie anpassen (mache ein Komma zwischen den Jagdgebietsnummern).<br><br>Möchstest du eine Jagdgebieteskarte anschauen, klicke direkt auf die Gebietskarte.</i><br><br>".chr(10);
	} else {
		echo "<i>Willkommen zu unserer Website. Gerne teilen wir relevante Information über unsere Rehkitzrettungsaktivitäten in der Region. Zur einfacheren Navigierung, wähle bitte deinen Namen oder das Jagdgebiet an dem du interessiert bist.</i><br><br>".chr(10);
	}
	echo "</td><td>". chr(10);
	//QR code anzeigen
	if (GetAccess ("PilotPartners")) { 
		echo  "<span id='qrswop1'>swop</span>";
	}
	echo CreateSiteLink ("Home", "");
	echo CreateSiteLink ("GoBack", "");
	echo "</td></tr>". chr(10);
	echo "</table>". chr(10);



	$OptionPerson= Array();
	$OptionPerson[]="";
	foreach ($Contacts as $key => $line) {
		if (like($line["Typ"], "Jäger")) {
			$OptionPerson[$line["Name"]] = $line["Name"];
		}
	}
	if (!in_array($Person,$OptionPerson)) {
		$OptionPerson[$Person] = $Person;
	}
	//pre_r($OptionPerson);
	sort($OptionPerson);

	$OptionRegion= Array();
	$OptionRegion[]="";
	foreach ($RegionSettings as $key => $line) {
		if ($line["Aktiv"]) {
			$OptionRegion[$key] = $line["Text"];
		}
	}
	sort($OptionRegion);
	echo "<form name='form' method='POST' action='". htmlspecialchars($_SERVER["PHP_SELF"]) . "' enctype='multipart/form-data'>" . chr(10);
	echo "<table>";
	echo "<tr>";
	echo "<td>";
	echo "Mein Name: ";
	echo "</td>";
	echo "<td>";
	if ($SimpleView) {
		echo "<b>".$Person."</b>";			
	} else {	
		echo SelectField("Person", $Person ,$OptionPerson);			
	}
	echo "</td>";
	echo "<td>";
	echo "</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td>";
	echo "Meine Regionen: ";
	echo "</td>";
	echo "<td colspan=3>";
	echo InputField("Reg", $Reg, 22);			
	//echo SelectField("Region", $Region ,$OptionRegion);			
	echo "</td>";
	echo "<td>";
	echo "</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td colspan=2>";
	echo '<input type="submit" name="Einlesen" value="Einlesen" formaction="' .htmlspecialchars($_SERVER["PHP_SELF"]). '">' .chr(10);
	echo "</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td colspan=2>";
	echo "<table>";
	echo "<th></th><th>heute</th><th>morgen</th>";

	if ($Person!="")
		{
			//https://tchr-frog.synology.me:5682/rkropen/pub/rkrk_flugliste.php?Jaeger=FB&Mode=J&SelFrom=2023-06-15&SelTo=2023-06-22
			echo "<tr>";
			echo "<th>Persönlich</th>";
			echo "</tr>";
			echo "<tr>";
			echo "<td>";
			echo "Flugliste";
			echo "</td>";
			echo "<td>";
			//https://tchr-frog.synology.me:5682/rkropen/pub/rkrk_flugliste.php?Jaeger=FB&Mode=J&SelFrom=2023-06-15&SelTo=2023-06-15
			echo CreateSiteLink ("Flugliste", "Jaeger=".$Pers."&Mode=J&SelFrom=".$Today."&SelTo=".$Today);
			echo "</td>";
			echo "<td>";
			echo CreateSiteLink ("Flugliste", "Jaeger=".$Pers."&Mode=J&SelFrom=".$Tomorrow."&SelTo=".$Tomorrow);
			echo "</td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td>";
			echo "PDF Flugplan";
			echo "</td>";
			echo "<td>";
			//https://tchr-frog.synology.me:5682/rkropen/test/rkrk_pdf_fluege.php?FromDate=2023-06-15&ToDate=2023-06-15&Jaeger=FB
			echo CreateSiteLink ("PdfFlugplan", "Jaeger=".$Pers."&Mode=J&FromDate=".$Today."&ToDate=2023-06-15".$Today);
			echo "</td>";
			echo "<td>";
			echo CreateSiteLink ("PdfFlugplan", "Jaeger=".$Pers."&Mode=J&FromDate=".$Tomorrow."&ToDate=".$Tomorrow);
			echo "</td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td>";
			echo "Flugkarte";
			echo "</td>";
			echo "<td>";
			//echo $StandardSettingMap;
			//https://tchr-frog.synology.me:5682/rkropen/pub/rkrk_map.php?Reg=21&Strt=on&Area=on&Pfad=on&Poi=on&KA=S&Zoom=0.8&FFilter=21-002&SFilter=
			echo CreateSiteLink ("FeldKarte", "Reg=". $Reg . $StandardSettingMap."&FFilter=".$PersFelderToday);
			echo "</td>";
			echo "<td>";
			echo CreateSiteLink ("FeldKarte", "Reg=". $Reg . $StandardSettingMap."&FFilter=".$PersFelderTomorrow);
			echo "</td>";
			echo "</tr>";
			echo "<tr>";
		}			
	if ($Region!="")
		{
			//https://tchr-frog.synology.me:5682/rkropen/pub/rkrk_flugliste.php?Jaeger=FB&Mode=J&SelFrom=2023-06-15&SelTo=2023-06-22
			echo "<tr>";
			echo "<th>Regional</th>";
			echo "</tr>";
			echo "<tr>";
			echo "<td>";
			echo "Flugplan";
			echo "</td>";
			echo "<td>";
			echo CreateSiteLink ("FeldKarte", "Reg=". $Reg . $StandardSettingMap."&FFilter=".$RegFelderToday);
			echo "</td>";
			echo "<td>";
			echo CreateSiteLink ("FeldKarte", "Reg=". $Reg . $StandardSettingMap."&FFilter=".$RegFelderTomorrow);
			echo "</td>";
			echo "</tr>";
		}			
	echo "<tr>";
	echo "<td>";
	echo "PDF Flugliste für diese Woche";
	echo "</td>";
	echo "<td>";
	echo CreateSiteLink ("PdfFlugliste", "FromDate=".$Today."&ToDate=".$InAWeek);
	echo "</td><td></td>";
	echo "</tr>";
	echo "<td>";
	echo "Partnerinformation";
	echo "</td>";
	echo "<td>";
	echo CreateSiteLink ("Partners", "");
	echo "</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td>";
	echo "QR codes";
	echo "</td>";
	echo "<td>";
	echo CreateSiteLink ("QRcodes", "");
	echo "</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td>";
	echo "Infos über die Webseite";
	echo "</td>";
	echo "<td>";
	echo CreateSiteLink ("Info", "");
	echo "</td>";
	echo "</tr>";

	echo "</table>";
	echo "</td>";
	echo "<td>";
	echo JGMapSelection(500);

	echo "</td>";
	echo "</tr>";
	echo "</table>";
	echo "</form>";
/*	echo "<i>Drücke auf die Region deiner Wahl:</i><br>".chr(10);
	//echo '<iframe width="900" height="900" seamless frameborder="0" scrolling="no" src="scripts/rkrk_graphic_map_button.php"></iframe>';

*/
	echo "<br><br>". chr(10);
	echo "<table>". chr(10);
	echo "<tr><td>". chr(10);
	echo "<font size='2px'>" . GetNameUser() . "</font>";

// Handling QR code
$ParameterSet = "Person=" . $Person . "&" . "Reg=" . $Reg;
$QRCode = $WebLink_public . "myrkrk.php" ."?" . $ParameterSet; 
if ($QRCode!="") {
	//echo $QRCode;
	$qrcode1.= "<br>Link zu dieser Grafik:<br>";
	$qrcode1.= "<a href='".$QRCode."'><img src='https://chart.googleapis.com/chart?chs=350x350&cht=qr&chl=".$QRCode."&choe=UTF-8&chld=L' title='Link auf Fluglisten Ansicht' /></a><br>";
	//$qrcode1.= "<p style='font-size:4;width: 150px'>".$WebLink."</p>";
	$qrcode2.= "";
}
echo "<div style='display:none;top:80px;left:80px;z-index:8;background-color:white' id='qrbloc1'>" .chr(10);
echo $qrcode1;
echo "</div>";
echo "<div style='display' id='qrbloc2'>" .chr(10);
echo $qrcode2;
echo "</div>";

?>
<style>

#qrbloc1, #qrbloc2 
{
    vertical-align : top;
    position : absolute;
    background : transparent; 
    filter: Alpha(Opacity=100);
    overflow : hidden;
    height : 400px;
    width : 400px;
    z-index : 20;
    text-align : center;
	border-radius: 2%;
}

#imgbox 
{
    vertical-align : middle;
    position : absolute;
    border: 1px solid #999;
    background : #FFFFFF; 
    filter: Alpha(Opacity=100);
    visibility : hidden;
	height : 600px;
    width : 600px;
    z-index : 2;
    overflow : hidden;
    text-align : center;
	border-radius: 2%;
}

</style>

<?php
 if ($Layer)
	echo "<div  id='imgbox' style='mybox,top:10px;right:60px;z-index:1;'></div>" . chr(10);
else 
	echo "<div  id='imgbox'></div>" . chr(10);

//QR code

?>
<script> 
<?php 
echo "var iconloc = '". $IconLoc . "';\n";
echo "var buttonsize = ". 22 . ";\n";

 ?>
	qrswop1.innerHTML = "<img  id='pic2' src='" +  iconloc + "/show_qr_t.png' height='35px' title='QR code anzeigen' onclick='SwopOnImage(\"qrbloc1\", \"qrbloc2\" ,\"pic2\", \"" +  iconloc + "/hide_qr_t.png\", \"" +  iconloc + "/show_qr_t.png\" )'>";
	qrswop2.innerHTML = "<img  id='pic2' src='" +  iconloc + "/hide_qr_t.png' height='22px' title='QR code verbergen' onclick='SwopOnImage(\"qrbloc1\", \"qrbloc2\" ,\"pic2\", \"" +  iconloc + "/hide_qr_t.png\", \"" +  iconloc + "/show_qr_t.png\" )'>";
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
</script>

