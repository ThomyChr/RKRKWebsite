<style>
//body {font-family: Arial;}
body {. font-family: "Yu Gothic";. src: local("Yu Gothic Medium");. font-weight: 100;. }

/* Style the tab content */
.title {
    font-size: 12px;
	color: #d6dbdf;
}

.title1 {
    font-size: 22px;
	color: #000000;
}

.title2 {
    font-size: 22px;
	color: #000000;
}

.content {
    font-size: 22px;
	color: #000000;
}

.red {
    font-size: 22px;
	color: #DE1E1A;
}
	
table, td, th {
  border: 0px;
  //border: 1px solid black;
}

.center {
	text-align: center;
}

#table1 {
  //border-collapse: separate;
  border-spacing: 15px;
}

#table2 {
  //border-collapse: separate;
  border-spacing: 2px;
  white-space: nowrap;
}
#table3,#table4,#table5 {
  border-spacing: 10px;
  //white-space: nowrap;
}

</style>

<?php
	session_start();
	include_once('scripts/rkrk_gen.php');
	include_once('scripts/rkrk_csvfiles.php');
	include_once('scripts/rkrk_menu.php'); 

	if (isset($_GET["P"])) {
		$Pers = $_GET["P"];
	} else	{
		$Pers = '';
	}
	//echo "P:" . $Pers . chr(10);
	
	headerpage("Personen Verifikation", false);

	$PersImageLoc = $ImageLoc ."pers/";
	$DocLoc = "doc/";

	// ===========================================
	// Inhalt
	// ===========================================
	
	echo "<H2> Personen Verifikation <b>drohnenrehkitzrettung.ch</b></H2></br>";
	//pre_r($PersonenDaten[$Pers]);
	
	if (count($PersonenDaten[$Pers])>0) {
		echo "<table id='table1'>";
		echo "<tr>";
		echo "<td rowspan='10'>";
		if ($PersonenDaten[$Pers]['Pic']<>"") {
			echo "<img src='" . $PersImageLoc . $PersonenDaten[$Pers]['Pic'] ."' height='200px' style='border-radius: 10px;'> ";
		}
		//2nd column
		echo "</td>";
		echo "<td>";
		echo "<span class='title'><b>Vorname</b> (First name)" . "</span></br>";
		echo "<span class='content'>" . $PersonenDaten[$Pers]['Vorname'] . "</span></br";
		echo "</td>";
		echo "<td>";
		echo "<span class='title'><b>Nachname</b> (Last name)" . "</span></br>";
		echo "<span class='content'>" . $PersonenDaten[$Pers]['Name'] . "</span></br";
		echo "</td>";
		echo "<td>";
		if ($PersonenDaten[$Pers]['verfied']) {
			echo "<img src='" . $PersImageLoc ."verifi.gif' height='100px' > ";
		} else {
			echo "<img src='" . $PersImageLoc ."reject.gif' height='100px' > ";
			echo "<br><span class='red'>!!! Ungültig !!!</span> ";
		}
		echo "</td>";
		echo "</tr>";
		//row 3
		echo "<tr>";
		echo "<td colspan='2'>";
		if ($PersonenDaten[$Pers]['Tel']<>"") {
			echo "<span class='title'><b>Telefon</b> (mobile phone)" . "</span></br>";
			echo "<span class='content'>" . $PersonenDaten[$Pers]['Tel'] . "</span></br";
			//echo "<span class='title'><b>Identifizierungsnummer</b> (Identification number)" . "</span></br>";
			//echo "<span class='content'>" . $PersonenDaten[$Pers]['EASA_ID'] . "</span></br";
		}
		echo "</td>";
		echo "<td>";
		//echo "<span class='title'><b>Ablaufdatum</b> (Expiration date)" . "</span></br>";
		//echo "<span class='content'>" . $PersonenDaten[$Pers]['EASA_exp'] . "</span></br";
		echo "</td>";
		echo "</tr>";
		//row 4
		echo "<tr>";
		echo "<td>";
		if ($PersonenDaten[$Pers]['EASA_BID']<>"") {
			echo "<span class='title'><b>EASA Betreiber ID</b> (operator registration ID,)" . "</span></br>";
			echo "<span class='content'>" . $PersonenDaten[$Pers]['EASA_BID'] . "</span></br";
		}	
		echo "</td>";
		echo "</tr>";
		echo "</table>";
		echo "<br><hr>";
		echo "<h2>Pilotenzeugnis EASA</h2>";
		echo "<br><br>";

		echo "<table id='table2'>";
		//row 1
		echo "<tr>";
		echo "<td colspan='3'>";
		echo "<img src='" . $PersImageLoc ."easa_logo.png' height='80px' > ";
		echo "</td>";
		echo "<td>";
		if ($PersonenDaten[$Pers]['country']=="ch")
			echo "<img src='" . $PersImageLoc ."bazl_logo.png' height='80px' > ";
		if ($PersonenDaten[$Pers]['country']=="de")
			echo "<img src='" . $PersImageLoc ."lba_logo.png' height='80px'><br><br> ";
		echo "</td>";
		echo "</tr>";
		//row 2
		echo "<tr>";
		echo "<td rowspan='3' colspan='1'>";
		if ($PersonenDaten[$Pers]['country']=="ch")
			echo "<img src='" . $PersImageLoc ."swiss_flag.png' height='80px' style='border-radius: 10px;'> ";
		if ($PersonenDaten[$Pers]['country']=="de")
			echo "<img src='" . $PersImageLoc ."de-eu_flag.png' height='80px' style='border-radius: 10px;'>";
		echo "</td>";
		echo "<td rowspan='3' colspan='2'>";
		echo "<img src='" . $PersImageLoc ."easa_kopter.png' height='180px'> ";
		echo "</td>";
		echo "<td>";
		if (in_array("A1/A3", explode(",",$PersonenDaten[$Pers]['Kat']))) {
			echo "<img src='" . $PersImageLoc ."easa_a1a3.png' height='80px' style='border-radius: 10px;'> ";
		}
		echo "</td>";
		echo "</tr>";
		//row 3
		echo "<tr>";
		echo "<td>";
		if (in_array("A2", explode(",",$PersonenDaten[$Pers]['Kat']))) {
			echo "<img src='" . $PersImageLoc ."easa_a2.png' height='80px' style='border-radius: 10px;'> ";
		}
		echo "</td>";
		echo "</tr>";
		//row 4
		echo "<tr>";
		echo "<td>";
		if (in_array("STS", explode(",",$PersonenDaten[$Pers]['Kat']))) {
			echo "<img src='" . $PersImageLoc ."easa_sts.png' height='80px' style='border-radius: 10px;'> ";
		}
		echo "</td>";
		echo "</tr>";
		//row 5
		echo "<tr>";
		echo "<td colspan='4'>";
		echo "<div class='center'>";
		if ($PersonenDaten[$Pers]['titel']=="a2") { 
			echo "<span class='title1'>Fernpiloten-Zeugnis" . "</span><br>";
			echo "<span class='title2'>REMOTE PILOT CERTIFICATE OF COMPETENCY" . "</span><br>";
		}
		if ($PersonenDaten[$Pers]['titel']=="sts-t") { 
			echo "<span class='title1'>Fernpiloten-Zeugnis über Theoriekenntnisse" . "</span><br>";
			echo "<span class='title2'>REMOTE PILOT CERTIFICATE OF THEORETICAL KNOWLEDGE" . "</span><br>";
		}
		if ($PersonenDaten[$Pers]['titel']=="sts-p") { 
			echo "<span class='title1'>Fernpiloten-Zeugnis" . "</span><br>";
			echo "<span class='title2'>REMOTE PILOT CERTIFICATE" . "</span><br>";
		}
		echo "</div>";
		echo "</td>";
		echo "</tr>";
		//row 6
		echo "<tr>";
		echo "<td colspan='2'>";
		echo "<span class='title'><b>Vorname</b> (First name)" . "</span></br>";
		echo "<span class='content'>" . $PersonenDaten[$Pers]['Vorname'] . "</span></br";
		echo "</td>";
		echo "<td>";
		echo "<span class='title'><b>Nachname</b> (Last name)" . "</span></br>";
		echo "<span class='content'>" . $PersonenDaten[$Pers]['Name'] . "</span></br";
		echo "</td>";
		echo "<td rowspan='2'>";
		if ($PersonenDaten[$Pers]['QR']<>"") {
			echo "<img src='" . $PersImageLoc . $PersonenDaten[$Pers]['QR'] ."' height='150px' > ";
		}	
		echo "</td>";
		echo "</tr>";
		//row 7
		echo "<tr>";
		echo "<td colspan='2'>";
		if ($PersonenDaten[$Pers]['EASA_ID']<>"") {
			echo "<span class='title'><b>Identifizierungsnummer</b> (Identification number)" . "</span></br>";
			echo "<span class='content'>" . $PersonenDaten[$Pers]['EASA_ID'] . "</span></br";
		}
		echo "</td>";
		echo "<td>";
		if ($PersonenDaten[$Pers]['EASA_exp']<>"") {
			echo "<span class='title'><b>Ablaufdatum</b> (Expiration date)" . "</span></br>";
			echo "<span class='content'>" . $PersonenDaten[$Pers]['EASA_exp'] . "</span></br";
		}
		echo "</td>";
		echo "</tr>";

		echo "</table>";
		if (file_exists($PersImageLoc . $PersonenDaten[$Pers]['STSAcc'])) {
			echo "<br><hr>";
			echo "<h2>Accreditierung der praktischen STS Prüfung</h2>";
			echo "<div id='STSACC'>";
			$PAWidth= 680;
			$PAHeight= 730;
			$PAZoom=30;
			echo "<iframe name='myiframe' id='myiframe' src='".$PersImageLoc . $PersonenDaten[$Pers]['STSAcc']."#toolbar=0&zoom=".$PAZoom."' width='".$PAWidth."' height='".$PAHeight."' style='overflow: hidden; overflow-x: hidden; overflow-y: hidden;' type='application/pdf'></iframe>";
			echo "</div>";
		}
		
		if (file_exists($PersImageLoc . $PersonenDaten[$Pers]['RKRS_MA'])) {
			echo "<br><hr>";
			echo "<h2>Pilotenausweis Rehkitzrettung Schweiz</h2>";
			echo "<div id='PARKRS'>";
			$PAWidth= 510;
			$PAHeight= 650;
			$PAZoom=150;
			echo "<iframe name='myiframe' id='myiframe' src='".$PersImageLoc . $PersonenDaten[$Pers]['RKRS_MA']."#toolbar=0&zoom=".$PAZoom."' width='".$PAWidth."' height='".$PAHeight."' style='overflow: hidden; overflow-x: hidden; overflow-y: hidden;' type='application/pdf'></iframe>";
			echo "</div>";
		}
		
		//Flugberechtigungen
		$Width="1000";
		$MapWidth = "600";
		$ColWidth = "400";
		$MapHeight="600";
		
		echo "<br><hr>";
		echo "<h2>Flugberechtigungen</h2>";
		echo "Folgende spezifische Flugberechtiungen sind zusätzlich gültig:<br>";
		if ((in_array("FGHO_RKRK", explode(",",$PersonenDaten[$Pers]['Flugberechtigung'])))
			or (in_array("FGHO_RKRS", explode(",",$PersonenDaten[$Pers]['Flugberechtigung']))))
		{
			echo "<h3>FGHO/ Hausen am Albis</h3>";

			$CenterLocation = "center=2682069.16,1232735.4&z=5";
			$Map = "KML%7C".$ThisPageDirectory."/rkrk_dokml.php?Reg=leer%26Strt=on%26Cntr=off%26Area=on%26Pfad=on%26WW=off%26FZ=on%26GDE=off%26JGG=off%26KT=off%26ORT=off%26Poi=off%26HSP=off%26Label=off%26GeLa=off%26Sun=off%26EG=off%26SORA=off%26WF=off%26KA=S%26Zoom=1%26FFilter=%26SFilter=%26MDB=3T%26GA=gast";
			$Add = "%26FZO=".$PersonenDaten[$Pers]['FlugzonenOption']."%26WebAccess=test";
			
			$MapSetting = "https://map.geo.admin.ch/#/embed?lang=de&".$CenterLocation."&bgLayer=ch.swisstopo.pixelkarte-grau&topic=ech&layers=ch.bfe.elektrische-anlagen_ueber_36,f;ch.bazl.luftfahrthindernis,f;ch.bazl.einschraenkungen-drohnen,f;ch.bazl.uas-aktivitaetszonen,f;".$Map."&featureInfo=default";
			
			echo "<table id='table3' style='width:".$Width."'>";
			echo "<tr>";
			echo "<td style='width:".$MapWidth."'>";
			//echo "Setting: " . $MapSetting . "&FDY=" . $Today . "<br>";
			echo '<iframe width="'.$MapWidth.'" height="'.$MapHeight.'" seamless frameborder="0" scrolling="no" src="'.$MapSetting . "&FDY=" . $Today . '"></iframe>'.chr(10);
			echo "</td>";
			echo "<td style='width:".$ColWidth."'>";
			echo fligtzone_legend(true, "100%", "FGHO", $PersonenDaten[$Pers]['FlugzonenOption']);
			echo "<br>";
			if (in_array("FGHO_RKRK", explode(",",$PersonenDaten[$Pers]['Flugberechtigung']))) {
				echo "<a href='".$DocLoc."2026 Flugbewilliung RKRK.pdf'><img src='".$IconLoc."/pdfdoc.png' height='".$ButtonHeight."'>Flugerberechtigung FGHO 2026" ."</a><br>";
			}
			if (in_array("FGHO_RKRS", explode(",",$PersonenDaten[$Pers]['Flugberechtigung']))) {
				echo "<a href='".$DocLoc."2026 Flugbewilliung RKRS.pdf'><img src='".$IconLoc."/pdfdoc.png' height='".$ButtonHeight."'>Spezialvereinbarung FGHO 2026" ."</a><br>";
			}
			echo "</td>";
			echo "</tr>";
			echo "</table>";
		}
		//
		
		if (in_array("LSXN", explode(",",$PersonenDaten[$Pers]['Flugberechtigung']))) 
		{
			echo "<h3>LSXN Haltikon</h3>";
		
			$CenterLocation = "center=2674376.48,1215932.74&z=5";
			//$Map = "KML%7C".$ThisPageDirectory."/rkrk_dokml.php?Reg=leer%26Strt=on%26Cntr=off%26Area=on%26Pfad=on%26WW=off%26FZ=on%26GDE=off%26JGG=off%26KT=off%26ORT=off%26Poi=off%26HSP=off%26Label=off%26GeLa=off%26Sun=off%26EG=off%26SORA=off%26WF=off%26KA=S%26Zoom=1%26FFilter=%26SFilter=%26MDB=3T%26GA=gast";
			//$Add = "%26FZO=".$PersonenDaten[$Pers]['FlugzonenOption']."%26WebAccess=test";
			
			$MapSetting = "https://map.geo.admin.ch/#/embed?lang=de&".$CenterLocation."&bgLayer=ch.swisstopo.pixelkarte-grau&topic=ech&layers=ch.bfe.elektrische-anlagen_ueber_36,f;ch.bazl.luftfahrthindernis,f;ch.bazl.einschraenkungen-drohnen,f;".$Map."&featureInfo=default";
			
			
			echo "<table id='table4' style='width:".$Width."'>";
			echo "<tr>";
			echo "<td style='width:".$MapWidth."'>";
			//echo "Setting: " . $MapSetting . "&FDY=" . $Today . "<br>";
			echo '<iframe width="'.$MapWidth.'" height="'.$MapHeight.'" seamless frameborder="0" scrolling="no" src="'.$MapSetting . "&FDY=" . $Today . '"></iframe>'.chr(10);
			echo "</td>";
			echo "<td style='width:".$ColWidth."'>";
			echo fligtzone_legend(true, "100%", "LSXN");
			echo "<a href='".$DocLoc."Rehkitzrettung Jagdverein Meierskappel_2026.pdf'><img src='".$IconLoc."/pdfdoc.png' height='".$ButtonHeight."'>LZSN Haltikon" ."</a><br>";
			
			echo "</td>";
			echo "</tr>";
			echo "</table>";
		}

		if (in_array("RKRS", explode(",",$PersonenDaten[$Pers]['Flugberechtigung']))) 
		{
			echo "<h3>Gesperrte Flugzonen der Swissgrid AG</h3>";
			echo "Der Verein Rehkitzrettung Schweiz hat mit der Swissgrid AG eine Rahmenbewilligung für Drohnen-flüge innerhalb der Flugverbotszonen vereinbart. Damit ist es Pilot:innen unter den untenstehenden Bedingungen erlaubt, innerhalb der eingeschränkten Flugzonen eine Drohne zu betreiben.<br><br>";

			$MapSetting = "https://map.geo.admin.ch/#/embed?lang=de&center=2651716,1226295&z=9&bgLayer=ch.swisstopo.pixelkarte-farbe&topic=aviation&crosshair=marker&layers=ch.bazl.einschraenkungen-drohnen&catalogNodes=aviation,1379";

			echo "<table id='table5' style='width:".$Width."'>";
			echo "<tr>";
			echo "<td style='width:".$MapWidth."'>";
			//echo "Setting: " . $MapSetting . "&FDY=" . $Today . "<br>";
			echo '<iframe width="'.$MapWidth.'" height="'.$MapHeight.'" seamless frameborder="0" scrolling="no" src="'.$MapSetting . "&FDY=" . $Today . '"></iframe>'.chr(10);
			echo "</td>";
			echo "<td>";
			echo "<b>Beispiel</b> 'Unterwerk Sursee'<br>";
		    echo "<img src='".$DocLoc."Swissgrid-Beispiel-Sursee.jpg' width=300><br><br>";
			echo "<b>Erklärung: </b>Innerhalb des blauen Bereiches ist es mit der Rahmenbewilligung erlaubt, eine Drohne zu betreiben. Der rot umrandete Bereich des Unterwerks darf nicht überflogen werden.";
			echo "<ul>";
			echo "<li>Rahmenbedingungen: <a href='".$DocLoc."2026_PT1032X_Rehkitzrettung_signed.pdf'>Bewilligung für das Jahr 2026</a>.</li>";
			echo "<li><a href='https://portal.rehkitzrettung.ch/memberarea/personal/'>Mitgliederausweis von Rehkitzrettung</a> Schweiz, da diese Bewilliiung eine gültige Mitgliedschaft als Grundlage hat.</li>";
			echo "</ul>";
			echo "</td>";
			echo "</tr>";
			echo "</table>";
		}

		if (in_array("Hue", explode(",",$PersonenDaten[$Pers]['Flugberechtigung']))) 
		{
			echo "<h3>Fahrbewilligung Gemeindestrassen Hühnenberg, ZG</h3>";
			echo "Bewilligung zum Befahren von Gemeindestrassen der Gemeinde Hünenberg mit immatrikulierten Motorfahrzeugen.<br><br>";
			echo "<img src='".$DocLoc."2026 Fahrbewilligung Hünenberg.jpg' width='700px' ><br><br>";
		}

	} else {
		// Person nicht gefunden
		echo "<img src='" . $PersImageLoc ."reject.gif' height='100px' > ";
		echo "<br><span class='red'>!!! Ungültig !!!</span>";
		echo "<br><span class='red'>Kein Pilot unter diesem Namen registriert.</span> ";
	}
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
  document.getElementById(cityName).style.display = "block";
  evt.currentTarget.className += " active";
}
</script>
