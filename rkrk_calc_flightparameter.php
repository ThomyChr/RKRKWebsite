<?php
	session_start();
	include_once('scripts/rkrk_gen_ext.php');
	include_once('scripts/rkrk_dronetypes.php');
	include_once('scripts/rkrk_inputforms.php');

	headerpage("Flugplanungs-Berechnung", false);
	
	//check impropriate IP addresses accessing page
	/**
	CheckIPandExit();
	**/

	foreach ($DroneData as $key => $Drone) {
		if ($Drone["DFOV"]>0) {
			//echo $Drone["DFOV"];
			$DroneData[$key]["IRCamAngle_H"] = FOVhoriz($Drone["DFOV"], $Drone["IRCamResolution_V"],$Drone["IRCamResolution_H"]);
			$DroneData[$key]["IRCamAngle_V"] = FOVvert($Drone["DFOV"], $Drone["IRCamResolution_V"], $Drone["IRCamResolution_H"]);
		} else {
			if ($Drone["IRCamAngle_H"]>0) {
				$DroneData[$key]["DFOV"] = FOVdiag ($Drone["IRCamAngle_H"], $Drone["IRCamResolution_V"],$Drone["IRCamResolution_H"] );
				$DroneData[$key]["IRCamAngle_V"] = FOVvert($DroneData[$key]["DFOV"], $Drone["IRCamResolution_V"], $Drone["IRCamResolution_H"]);
			}
		}
	}

	if (isset($_GET["TEST"])) {
		echo "<body>";
	} else	{
		echo "<body oncontextmenu='return false;'>";
	}	
	
	if (isset($_GET["drone"])) {
		$set_drone = $_GET["drone"];
	} else	{
		$set_drone = "";
	}	
	//echo "Drohne: " . $set_drone;

	if (isset($_GET["calc"])) {
		$set_calc = $_GET["calc"];
	} else	{
		$set_calc = "";
	}	

	// check if code runs on RKRS server
	if ((strpos($ThisHost, "rehkitzrettung.ch") !== false)  or (strpos($ThisPage, "/rkrs/calc"))) {
		$onRKRS = true;
		$Layout= "2023";
	} else {
		$onRKRS = false;
	} 

	//Select Options
	$Drones[]= "";
	foreach ($DroneData as $key => $Drone) {
		if ($Drone["active"])
			$Drones[]=$key;
	}

	$CalcIconLoc = $ImageLoc ."calc/";
	
	$OptPixel = array(
					"default" => 14,
					"min" => 12,
					"max" => 30,
				);
	$OptBodenAufloesung = array(
					"default" => 8.5,
					"min" => 7.5,
					"max" => 10,
				);
	$Sichtbarkeit = array(
					"default" => 8,
					"min" => 6,
					"max" => 10,
				);
	$MaxSpeed = 6;//[m/s]
	$MaxHeight = 120;//[m]
	$cal_value = array(	
					//Flughöhe
					"fh" => array(
						"default" => 60,
						"min" => 1,
						"max" => 120
						),
					//Bodenauflösung [cm]
					"ba" => array(
						"default" => 7,
						"min" => 2,
						"max" => 20
						),
					//Maximal zulässige Bewegung [px]
					"mzb" => array(
						"default" => 1.7,
						"min" => 1,
						"max" => 2
						),
					//Integrationszeit [ms]
					"iz" => array(
						"default" => 15,
						"min" => 5,
						"max" => 20
						),
					//Zielobjekt [cm]
					"zo" => array(
						"default" => 30,
						"min" => 2,
						"max" => 150
						),
					//Fluggeschwindigkeit [m/s]
					"fs" => array(
						"default" => 4.5,
						"min" => 1,
						"max" => 20
						),
					);

	//enable/disable tables
	$logo = "rkrk";
	$copyright = "drohnenrehkitzrettung.ch";
	$setting= "PUB";
	
	if (isset($_GET["TEST"])) {
		$Infoplus= true;
		$logo = "rkrk";
		$copyright = "drohnenrehkitzrettung.ch";
		$setting= "TEST";
	}

	if (isset($_GET["RKRK"])) {
		$Infoplus= true;
		$logo = "rkrk";
		$copyright = "drohnenrehkitzrettung.ch";
		$setting= "RKRK";
	}

	if (isset($_GET["RKRS"])) {
		$Infoplus= false;
		$logo = "rkrs";
		$copyright = "rehkitzrettung.ch";
		$setting= "RKRS";
	}

	if (isset($_GET["RKRSprev"])) {
		$Infoplus= true;
		$logo = "rkrs";
		$copyright = "rehkitzrettung.ch";
		$setting= "RKRSpreview";
	}
	
	if ($onRKRS) {
		$logo = "rkrs";
	}
?>
  <style>
	th {
		background-color: #DDDDDD;
	}
	td, th {
		
	}	

	th.result {
		text-align: center;
		vertical-align: middle;
		background-color: #efefef;
		font-size: 80%;
		font-weight: normal;
		padding-top: 2px;
		padding-right: 10px;
		padding-bottom: 10px;
		padding-left: 2px;
		border-radius: 5px;
	}	

	td.result {
		text-align: center;
		vertical-align: middle;
		background-color: #f0f5ed;
		font-weight: normal;
		font-size: 120%;
		padding-top: 2px;
		padding-right: 10px;
		padding-bottom: 10px;
		padding-left: 2px;
		border-radius: 5px;
	}	

    body {
      font-family: sans-serif;
      padding: 10px;
      font-size: 14px;
      //display: flex;
      //flex-direction: column;
      //gap: 40px;
	  max-width:1000px;
    }

    .custom-range-slider {
      max-width: 500px;
    }

    label {
      display: block;
      margin-bottom: 5px;
      font-weight: bold;
    }

    .slider-wrapper {
      position: relative;
      margin-bottom: 50px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    input[type=range] {
      flex: 1;
      appearance: none;
      height: 5px;
      background: #ccc;
      border-radius: 3px;
      position: relative;
      z-index: 1;
    }

    input[type=range]::-webkit-slider-thumb {
      appearance: none;
      width: 10px;
      height: 20px;
      background: #96B588;
      border-radius: 5px;
      margin-top: -7px;
      cursor: pointer;
    }

    input[type=number] {
      width: 60px;
      padding: 4px;
    //  font-size: 14px;
	  border-radius: 6px;
	  border:1px solid #96B588;
	  text-align: right;
    }

    .bubble {
      position: absolute;
      top: 25;
      transform: translateX(-50%);
      background: #96B588;
      color: #fff;
      font-size: 12px;
      padding: 4px 8px;
      border-radius: 4px;
      white-space: nowrap;
      z-index: 2;
    }

    .range-label {
      font-size: 12px;
      color: #888;
      width: 30px;
      text-align: center;
    }

    .result {
      margin-top: 10px;
      font-size: 16px;
      font-weight: bold;
      color: #444;
    }
	
	select_drone {
      margin-top: 10px;
      font-size: 16px;
      font-weight: bold;
      color: #444;
	  size: 20;
    }
  </style>
</head>
<body>
<?php
	$tablewidth=1000;
	echo "<table max-width='".$tablewidth."'>" . chr(10);
	//ROW1
	echo "<tr><td  style='vertical-align: top; width:700'>" . chr(10);
	if (($setting<>"RKRS") and ($setting<>"RKRSplus") and ($setting<>"RKRSprev"))  {
		echo "<H1>Flugparameter-Berechnung</H1>" . chr(10);
	}echo "</td><td>";
	echo "</td ><td style='vertical-align: top; text-align: right;'>";
	if ($logo=="rkrs") {
		echo "<img src='".$CalcIconLoc."rkrs_logo.png' id='logopic' name='logopic' width='247' height='80'><br/><br/>" . chr(10);
	} elseif ($logo=="rkrk") {
		echo "<img src='".$CalcIconLoc."rkrk_logo.png' id='logopic' name='logopic' height='80'><br/><br/>" . chr(10);
	} else {
		echo "<img src='".$CalcIconLoc."void.png' id='logopic' name='logopic' witdh='100'><br/><br/>" . chr(10);
	}	
	echo "</td></tr>" . chr(10);
	echo "</table>" . chr(10);

	echo '<h2>Wie beeinflusst die Flughöhe die Qualität von Wärmebildern?</h2>';
	echo '<p>Je höher eine Drohne fliegt, desto schlechter wird die Bodenauflösung des Wärmebildes – also wie viele Zentimeter ein Pixel abdeckt. Gleichzeitig wird der Bildausschnitt breiter, man sieht also mehr Fläche.</p>';	
	echo '<p>Dieser praktische Rechner erlaubt je nach Anwendung die optimale Flughöhe zu wählen und den Bildausschnitt abzuschätzen.</p>';
	echo '<b>Beachte:</b> Die Werte sind Näherungen und können je nach Wetter, Umgebung und Drohnentyp variieren.</p>';

	echo "<span id='drone' name='drone'><i>Wähle deinen Kamera/Drohnentyp aus:</i></span>  " . chr(10);
	echo SelectJSFunctionField("Drohne", $set_drone ,$Drones , "UpdDrone", "this.value", "1", "left", "select_drone" ) . chr(10);

	echo "<br/><br/>";
	echo "<span  id='dronelist' name='dronelist' ></span>  ";
	echo "<span id='droneselected' style='display:none;'>";


///////////////////////////////////////////////
//Gruppe 1
///////////////////////////////////////////////
	echo '<h2>';
	echo "<span id='chaptpic1'></span> ";
	echo 'Bodenauflösung und Breite des Bildausschnittes';
	echo '</h2>';
	// Chapter start
	echo "<span  style='display:block' id='chapt1'>";
	echo '<p>Die meisten Wärmebilddrohnen haben ein festes Objektiv. Deshalb hängt die <b>Bildqualität (cm/Pixel) und die Breite des Bildausschnitts </b>';
	if (array_key_exists($set_drone, $DroneData)) {
		echo '<i>(bei der <span id="Drone1">'.$set_drone. '</span> - <span id="ResCamH1">' .$DroneData[$set_drone]["IRCamResolution_H"].'</span>px und ein HFOV von <span id="CamAngleH1">'.$DroneData[$set_drone]["IRCamAngle_H"].'</span>°)</i>';
	}	
	echo ' direkt <b>von der Flughöhe ab</b>.';
	echo 'Mit unserem Rechner könnt ihr das ganz einfach für verschiedene Drohnen berechnen.<p>';
	echo '
	<table>
	  <tr>
	  <td  width="80%">
	';
	echo '<h3>Bodenauflösung</h3>';
	echo '<p>Berechnung der Bodenauflösung abhängig von Flughöhe und Drohnenmodell.</p>';


	echo '
		  <div class="custom-range-slider">
			<label for="slider1">Flughöhe</label>
			<div class="slider-wrapper">
			  <input id="input1" type="number" min="'.$cal_value["fh"]["min"].'" max="'.$cal_value["fh"]["max"].'" value="'.$cal_value["fh"]["default"].'">
			  <span class="range-label" id="min1"></span>
			  <input id="slider1" type="range" min="'.$cal_value["fh"]["min"].'" max="'.$cal_value["fh"]["max"].'" value="'.$cal_value["fh"]["default"].'">
			  <span class="range-label" id="max1"></span>
			  <span class="bubble" id="bubble1"></span>
			</div>
			<table>
			  <tr>
				<th class="result">Sichtbreite Boden [m]</th>
				<th class="result">Bodenauflösung [cm]</th>
			  </tr>
			  <tr>
				<td class="result"><span id="result1" ><i>Resultat</i></span></td>
				<td class="result"><span id="result2" ><i>Resultat</i></span></td>
			  </tr>
			</table>
		  </div>
		';
	echo '
	  </td>
	  <td>
		<video autoplay loop muted playsinline height="350">
			<source src="'.$CalcIconLoc.'Bodenauflösung.mp4">   Dein Browser unterstützt das Video-Tag nicht.
		</video>
	  </td>
	  </tr>
	</table>	';
	echo '<br>';
	echo '<br>';
	// Chapter end
	echo "</span>";

///////////////////////////////////////////////
//Gruppe 2
///////////////////////////////////////////////
	echo '<h2>';
	echo "<span id='chaptpic2'></span> ";
	echo 'Maximale Geschwindigkeit';
	echo '</h2>';
	// Chapter start
	echo "<span  style='display:block' id='chapt2'>";
	echo '<p>Wärmebildkameras brauchen eine feste Zeit, um ein Bild aufzunehmen – meist '.$cal_value["iz"]["default"].' Millisekunden. Wenn die Drohne sich in dieser Zeit <b>zu schnell</b> bewegt, wird das <b>Bild unscharf</b>. Die Szene „verschiebt“ sich während der Aufnahme, und warme Objekte sind schwerer zu erkennen.</p>';
	echo '<p>Auch die Bodenauflösung spielt eine Rolle:<br>';
	echo 'Je größer die Fläche, die ein Pixel abdeckt, desto schneller darf die Drohne fliegen, ohne dass das Bild verwischt. <b>Eine Unschärfe von bis zu '.$cal_value["mzb"]["default"].' Pixeln</b> gilt meist noch als <b>akzeptabel</b>.<p>';
	echo '<h3>Fluggeschwindigkeit</h3>';
	echo '<p>Berechnung der maximal zulässigen Fluggeschwindigkeit zur Vermeidung von Bewegungsunschärfe.</p>';

	echo '
  <div class="custom-range-slider">
    <label for="slider2">Bodenauflösung [cm]</label>
    <div class="slider-wrapper">
      <input id="input2" type="number" min="'.$cal_value["ba"]["min"].'" max="'.$cal_value["ba"]["max"].'" value="'.$cal_value["ba"]["default"].'">
      <span class="range-label" id="min2"></span>
      <input id="slider2" type="range" min="'.$cal_value["ba"]["min"].'" max="'.$cal_value["ba"]["max"].'" value="'.$cal_value["ba"]["default"].'" step="0.1">
      <span class="range-label" id="max2"></span>
      <span class="bubble" id="bubble2"></span>
    </div>
    <label for="slider3">Maximal zulässige Bewegung [px]</label>
    <div class="slider-wrapper">
      <input id="input3" type="number" min="'.$cal_value["mzb"]["min"].'" max="'.$cal_value["mzb"]["max"].'" value="'.$cal_value["mzb"]["default"].'">
      <span class="range-label" id="min3"></span>
      <input id="slider3" type="range" min="'.$cal_value["mzb"]["min"].'" max="'.$cal_value["mzb"]["max"].'" value="'.$cal_value["mzb"]["default"].'" step="0.1">
      <span class="range-label" id="max3"></span>
      <span class="bubble" id="bubble3"></span>
    </div>
    <label for="slider4">Integrationszeit [ms]</label>
    <div class="slider-wrapper">
      <input id="input4" type="number" min="'.$cal_value["iz"]["min"].'" max="'.$cal_value["iz"]["max"].'" value="'.$cal_value["iz"]["default"].'">
      <span class="range-label" id="min4"></span>
      <input id="slider4" type="range" min="'.$cal_value["iz"]["min"].'" max="'.$cal_value["iz"]["max"].'" value="'.$cal_value["iz"]["default"].'">
      <span class="range-label" id="max4"></span>
      <span class="bubble" id="bubble4"></span>
    </div>
	<table>
	  <tr>
	    <th class="result">Maximale Fluggeschwindigkeit [m/s]</th>
	  </tr>
	  <tr>
	    <td class="result"><span id="result3" ><i>Resultat</i></span></td>
	  </tr>
	</table>
  </div>
  </div>
	';
	// Chapter end
	echo '<br>';
	echo '<br>';
	echo "</span>";


///////////////////////////////////////////////
//Gruppe 3
///////////////////////////////////////////////
	echo '<h2>';
	echo "<span id='chaptpic3'></span> ";
	echo 'Optimale Flughöhe';
	echo '</h2>';
	// Chapter start
	echo "<span  style='display:block' id='chapt3'>";
	echo '<p>Damit man ein Tier gut erkennen und unterscheiden kann, sollte es <b>im Wärmebild etwa '.$OptPixel["default"].' Pixel gross</b> dargestellt werden.<br>';
	echo 'Mit unserem Rechner könnt ihr für verschiedene Drohnenmodelle die passende Flughöhe und Bodenauflösung berechnen – je nach Größe des gesuchten Tieres.</p>';
	echo '<p>Zum Beispiel: Ein liegendes Rehkitz hat etwa '.$cal_value["zo"]["default"].' cm Durchmesser.</p>'; 
	echo '<p><i>Die Berechnung berücksichtigt eine horizontale Punktauflösung der <span id="Drone2">'.$set_drone. "</span> von <span id='ResCamH2'>" .$DroneData[$set_drone]["IRCamResolution_H"].'</span>px.</i></p>';

	echo '<h3>Flughöhe</h3>';
	echo '<p>Berechnung der empfohlenen Flughöhe in Abhängigkeit des Durchmessers des Zielobjektes.</p>';
	echo '<br>';

	echo '
	<table>
	  <tr>
	  <td>
		  <div class="custom-range-slider">
			<label for="slider5">Durchmesser des Zielobjektes [cm]</label>
			<div class="slider-wrapper">
			  <input id="input5" type="number" min="'.$cal_value["zo"]["min"].'" max="'.$cal_value["zo"]["max"].'" value="'.$cal_value["zo"]["default"].'">
			  <span class="range-label" id="min5"></span>
			  <input id="slider5" type="range" min="'.$cal_value["zo"]["min"].'" max="'.$cal_value["zo"]["max"].'" value="'.$cal_value["zo"]["default"].'">
			  <span class="range-label" id="max5"></span>
			  <span class="bubble" id="bubble5"></span>
			</div>
			<table>
			  <tr>
				<th class="result">Flughöhe [m]</th>
				<th class="result">Bodenauflösung [cm]</th>
			  </tr>
			  <tr>
				<td class="result"><span id="result4" ><i>Resultat</i></span></td>
				<td class="result"><span id="result5" ><i>Resultat</i></span></td>
			  </tr>
			</table>
		  </div>
	  </td>
	  </tr>
	  <tr>
	  <td>
		<video autoplay loop muted playsinline height="300">
			<source src="'.$CalcIconLoc.'2026 Bodenauflösung Pixel konstant.mp4">   Dein Browser unterstützt das Video-Tag nicht.
		</video>
	  </td>
	  </tr>
	</table>	';
	echo '<br>';
	echo '<br>';
	// Chapter end
	echo "</span>";

///////////////////////////////////////////////
//Gruppe 4
///////////////////////////////////////////////
	echo '<h2>';
	echo "<span id='chaptpic4'></span> ";
	echo 'Anzeigedauer auf dem Bildschirm';
	echo '</h2>';
	// Chapter start
	echo "<span  style='display:block' id='chapt4'>";
	echo '<p>Die <b>Sichtbarkeit</b> eines warmen Objekts – wie ein Rehkitz oder Bodenbrüter – <b>hängt von der Fluggeschwindigkeit der Drohne ab</b>.';
	echo 'Mit unserem Rechner könnt ihr für verschiedene Drohnenmodelle abschätzen, wie lange ein Tier im Bild bleibt.</p>';
	echo '<p><b>Tipp:</b> Wählt die Geschwindigkeit so, dass das Tier schon in der oberen Bildhälfte erkannt wird. So bleibt genug Zeit, die Drohne zu stoppen, bevor es aus dem Bild verschwindet.';
	echo 'Meist sind <b>'.$Sichtbarkeit["min"].' bis '.$Sichtbarkeit["max"].' Sekunden ideal</b>.</p>';
	echo '<p><i>Die Berechnung berücksichtigt eine vertikale Punktauflösung der <span id="Drone3">'.$set_drone. "</span> von <span id='ResCamV1'>" .$DroneData[$set_drone]["IRCamResolution_V"].'</span>px.</i></p>';
	echo '<h3>Anzeigedauer</h3>';
	echo '<p>Berechnung der Anzeigedauer in Abhängigkeit von Bodenauflösung und Fluggeschwindigkeit.</p>';

	echo '
  <div class="custom-range-slider">
    <label for="slider6">Bodenauflösung [cm]</label>
    <div class="slider-wrapper">
      <input id="input6" type="number" min="'.$cal_value["ba"]["min"].'" max="'.$cal_value["ba"]["max"].'" value="'.$cal_value["ba"]["default"].'">
      <span class="range-label" id="min6"></span>
      <input id="slider6" type="range" min="'.$cal_value["ba"]["min"].'" max="'.$cal_value["ba"]["max"].'" value="'.$cal_value["ba"]["default"].'" step="0.1">
      <span class="range-label" id="max6"></span>
      <span class="bubble" id="bubble6"></span>
    </div>
    <label for="slider7">Fluggeschwindigkeit (m/s)</label>
    <div class="slider-wrapper">
      <input id="input7" type="number" min="'.$cal_value["fs"]["min"].'" max="'.$cal_value["fs"]["max"].'" value="'.$cal_value["fs"]["default"].'">
      <span class="range-label" id="min7"></span>
      <input id="slider7" type="range" min="'.$cal_value["fs"]["min"].'" max="'.$cal_value["fs"]["max"].'" value="'.$cal_value["fs"]["default"].'"  step="0.1">
      <span class="range-label" id="max7"></span>
      <span class="bubble" id="bubble7"></span>
    </div>
	<table>
	  <tr>
	    <th class="result">Anzeige von Objekten auf dem Bildschirm [s]</th>
	  </tr>
	  <tr>
	    <td class="result"><span id="result6" ><i>Resultat</i></span></td>
	  </tr>
	</table>
  </div>
	';
	echo '<br>';
	echo '<br>';
	// Chapter end
	echo "</span>";

///////////////////////////////////////////////
//Gruppe 5
///////////////////////////////////////////////
	echo '<h2>';
	echo "<span id='chaptpic5'></span> ";
	echo 'Fazit';
	echo '</h2>';
	// Chapter start
	echo "<span  style='display:block' id='chapt5'>";
	echo '<p>Basierend auf den Erfahrungswerten in der Rehkitzrettung werden  die <span id="Drone4">'.$set_drone. '</span> folgende Werte empfohlen:	';
	echo '<p><i>Für die Berechnung der Empfehlungen werden folgende Werte angenommen:</p>';
	echo "<span  style='display:block' id='conditions'>Annahmen</span>";
	echo '
		<table>
		  <tr>
			<th class="result"></th><th class="result" width="150px">von</th><th class="result" width="150px">bis</th>
		  </tr>
		  <tr>
			<th class="result">Optimale Flughöhe [m]</th><td class="result"><span id="OFHmin" ><i>Resultat</i></span></td><td class="result"><span id="OFHmax" ><i>Resultat</i></span></td>
		  </tr>
		  <tr>
			<th class="result">Sichtbreite am Boden [m]</th><td class="result"><span id="SBBmin" ><i>Resultat</i></span></td><td class="result"><span id="SBBmax" ><i>Resultat</i></span></td>
		  </tr>
		  <tr>
			<th class="result">Fluggeschwindigkeit [m/s]</th><td class="result"><span id="FSmin" ><i>Resultat</i></span></td><td class="result"><span id="FSmax" ><i>Resultat</i></span></td>
		  </tr>
		 <!--
		 <tr>
			<th class="result">Fluggeschwindigkeit [m/s]</th><td class="result"><span id="FS1min" ><i>Resultat</i></span></td><td class="result"><span id="FS1max" ><i>Resultat</i></span></td>
		  </tr>
		-->  
		</table>
	';
	echo "<span  style='display:block' id='remark1'>Bemerkung1</span>";
/*
	echo '<p><b>Bemerkung:</b><br> Die Fluggeschwindikgeit und Flugghöhe hängt zusätzlich von der Erfahrung des Teams ab. Es kann auch langsamer oder tiefer geflogen werden, wenn das Team sich dabei sicherer fühlt. Geschwindigkeiten über '.$MaxSpeed.' m/s für einen Suchflug sind nicht empfohlen.</b>';
*/
	echo '<br>';
	echo '<br>';
	// Chapter end
	echo "</span>";
	echo "<span  style='display:block' id='remark2'>Bemerkung2</span>";

///////////////////////////////////
	// Drone selected
	echo "</span>";
	echo "<div align='center'> <i>© " . date("Y") ." by ".$copyright."</i></div>" . chr(10);
	echo "<div align='center' style='font-size:10'><a href='mailto:thomas.christen@drohnenrehkitzrettung.ch?subject=Rückmeldung Flugparameterberechnung'>Rückmeldungen</i></a></div>" . chr(10);
	echo "<div align='center' style='font-size:10'><br><br><a href='https://thermaldrones.de/2025/02/16/rechentools-flugplanung/' style='color:white'>Referenz</a></div>" . chr(10);
	echo "<div align='center' style='font-size:10'><i>Danksagung an thermaldrones.de für die Inspiration zu diesem Berechnungswerkzeug.</i></a></div>" . chr(10);
	
?>
  <script>
	<?php
		echo "var DroneData = ". json_encode($DroneData) . ";\n";
		echo "var CalcValues = ". json_encode($cal_value) . ";\n";
		echo "var SelectedDrone = '". $set_drone. "';\n";
		echo "var OptPixel = ". json_encode($OptPixel) . ";\n";
		echo "var OptBodenAufloesung = ". json_encode($OptBodenAufloesung) . ";\n";
		echo "var Sichtbarkeit = ". json_encode($Sichtbarkeit) . ";\n";
		echo "var iconloc =  '". $CalcIconLoc. "';\n";
		echo "var MaxSpeed =  '". $MaxSpeed. "';\n";
		echo "var MaxHeight =  '". $MaxHeight. "';\n";

	?>

const RoundTo5 = 5;
const RoundTo2 = 2;
const RoundToll = 0.2;	

window.onload = () => {
  const sliders = Array.from({ length: 7 }, (_, i) => {
    const index = i + 1;
    return {
      slider: document.getElementById(`slider${index}`),
      input: document.getElementById(`input${index}`),
      bubble: document.getElementById(`bubble${index}`),
      minLabel: document.getElementById(`min${index}`),
      maxLabel: document.getElementById(`max${index}`)
    };
  });

  const result1 = document.getElementById('result1');
  const result2 = document.getElementById('result2');
  const result3 = document.getElementById('result3');
  const result4 = document.getElementById('result4');
  const result5 = document.getElementById('result5');
  const result6 = document.getElementById('result6');
  const ResCamV1 = document.getElementById('ResCamV1');
  const ResCamH1 = document.getElementById('ResCamH1');
  const ResCamH2 = document.getElementById('ResCamH2');
  const CamAngleH1 = document.getElementById('CamAngleH1');
  const drone1 = document.getElementById('Drone1');
  const drone2 = document.getElementById('Drone2');
  const drone3 = document.getElementById('Drone3');
  const drone4 = document.getElementById('Drone4');

  let isUserChangingSlider1 = false;
  let isUserChangingSlider5 = false;

  function updateBubble(slider, bubble) {
    const { min, max, value, offsetWidth } = slider;
    const total = Number(max) - Number(min);
    const perc = (Number(value) - Number(min)) / total;
    const thumbOffset = perc * offsetWidth + 110;
    bubble.style.left = `${thumbOffset}px`;
    bubble.textContent = value;
  }

  function syncSliderInput(slider, input, bubble) {
    input.value = slider.value;
    updateBubble(slider, bubble);
  }

  function syncInputSlider(input, slider, bubble) {
    slider.value = input.value;
    updateBubble(slider, bubble);
  }

  function updateAll() {
    sliders.forEach(({ slider, input, bubble, minLabel, maxLabel }) => {
      minLabel.textContent = slider.min;
      maxLabel.textContent = slider.max;
      input.value = slider.value;
      updateBubble(slider, bubble);
    });
	
	//Text update
	ResCamV1.textContent = DroneData[SelectedDrone]["IRCamResolution_V"];
	ResCamH1.textContent = DroneData[SelectedDrone]["IRCamResolution_H"];
	ResCamH2.textContent = DroneData[SelectedDrone]["IRCamResolution_H"];
	CamAngleH1.textContent = DroneData[SelectedDrone]["IRCamAngle_H"];
	drone1.textContent = SelectedDrone;
	drone2.textContent = SelectedDrone;
	drone3.textContent = SelectedDrone;
	drone4.textContent = SelectedDrone;

    // Sichtbreite Boden/sbo [m]/ Bodenauflösung/ bal [m]
    const v_fh = Number(sliders[0].slider.value);
    const sbo = 2 * Math.tan((DroneData[SelectedDrone]["IRCamAngle_H"] / 2) * Math.PI / 180) * v_fh;
    const bal = Math.round(sbo / DroneData[SelectedDrone]["IRCamResolution_H"] * 100 * 10) / 10;
    
	// Setze slider[1] und slider[5] auf bal, wenn Benutzer sie nicht gerade verändert
    if (!isUserChangingSlider1) {
      sliders[1].slider.value = bal;
      sliders[1].input.value = bal;
      updateBubble(sliders[1].slider, sliders[1].bubble);
    }
    if (!isUserChangingSlider5) {
      sliders[5].slider.value = bal;
      sliders[5].input.value = bal;
      updateBubble(sliders[5].slider, sliders[5].bubble);
    }

    result1.textContent = sbo.toFixed(0);
    result2.textContent = bal.toFixed(1);

    const v_ba = Number(sliders[1].slider.value);
    const v_mzb = Number(sliders[2].slider.value);
    const v_iz = Number(sliders[3].slider.value);

    result3.textContent = ((v_ba / 100) * (v_mzb * 1000 / v_iz)).toFixed(1);

    const dzo = Number(sliders[4].slider.value);
    const oba = dzo / Math.sqrt(OptPixel.default);
    const ofh = oba * DroneData[SelectedDrone]["IRCamResolution_H"] / 100 / (2 * Math.tan((DroneData[SelectedDrone]["IRCamAngle_H"] / 2) * Math.PI / 180));

	result4.textContent = ofh.toFixed(0);
    result5.textContent = oba.toFixed(1);

    const v_ba2 = Number(sliders[5].slider.value);
    const v_fs = Number(sliders[6].slider.value);
    const azb = (v_ba2 * DroneData[SelectedDrone]["IRCamResolution_V"]) / 100 / v_fs;

    result6.textContent = azb.toFixed(1);
	
	
	//Fazitberechnung
	const cond = document.getElementById('conditions');
	const ofh_min = document.getElementById('OFHmin');
	const ofh_max = document.getElementById('OFHmax');
	const sbb_min = document.getElementById('SBBmin');
	const sbb_max = document.getElementById('SBBmax');
	const fs_min = document.getElementById('FSmin');
	const fs_max = document.getElementById('FSmax');
	const fs1_min = document.getElementById('FS1min');
	const fs1_max = document.getElementById('FS1max');

	conditions =  '<ul>';
	conditions += '<li>Rehkitz von der Grösse von etwa ' + CalcValues.zo.default + ' cm Durchmesser als Ziel</li>';
	conditions += '<li>Anzeige des Ziels auf etwa ' + OptPixel.default  + ' Pixel und somit eine Bodenauflösung von ca. ' + OptBodenAufloesung["default"] + ' cm</li>';
	//conditions += '<li>Integrationszeit der Wärmebildkamera von ' + CalcValues["iz"]["default"] + ' Millisekunden</li>';
	//conditions += '<li>Unschärfe von bis zu ' + $cal_value["mzb"]["default"] + ' Pixeln</li>';
	conditions += '<li>Optikgeometrie der ' + SelectedDrone + ' HFOV: ' + DroneData[SelectedDrone]["IRCamAngle_H"] + '° und VFOV: ' + DroneData[SelectedDrone]["IRCamAngle_V"] + '°</li>';
	conditions += '<li>Infrarotkameraauflösung der ' + SelectedDrone + ' Horizontal: ' + DroneData[SelectedDrone]["IRCamResolution_H"] + 'px Vertikal: ' + DroneData[SelectedDrone]["IRCamResolution_V"] + 'px</li>';
	conditions += '<li>Zeit der Sichtbarkeit auf dem Monitor von ' + Sichtbarkeit.min + ' bis ' + Sichtbarkeit.max + ' Sekunden</li>';
	conditions += '</ul>';
	cond.innerHTML = conditions;
	
  	//v_fh =  sbo/(2 * Math.tan((DroneData[SelectedDrone]["IRCamAngle_H"] / 2) * Math.PI / 180)) ;
	//sbo  =  bal * DroneData[SelectedDrone]["IRCamResolution_H"] * 100;
    //v_fh =  (bal * DroneData[SelectedDrone]["IRCamResolution_H"] * 100)/(2 * Math.tan((DroneData[SelectedDrone]["IRCamAngle_H"] / 2) * Math.PI / 180)) ;
	ofh_min.textContent = ((Math.min((OptBodenAufloesung.min * DroneData[SelectedDrone]["IRCamResolution_H"] / 100)/(2 * Math.tan((DroneData[SelectedDrone]["IRCamAngle_H"] / 2) * Math.PI / 180)),MaxHeight)/RoundTo5)).toFixed(0)*RoundTo5;
	ofh_max.textContent = ((Math.min((OptBodenAufloesung.max * DroneData[SelectedDrone]["IRCamResolution_H"] / 100)/(2 * Math.tan((DroneData[SelectedDrone]["IRCamAngle_H"] / 2) * Math.PI / 180)),MaxHeight)/RoundTo5)).toFixed(0)*RoundTo5;
	sbb_min.textContent = ((2 * Math.tan((DroneData[SelectedDrone]["IRCamAngle_H"] / 2) * Math.PI / 180) * ofh_min.textContent/RoundTo2)).toFixed(0)*RoundTo2;
	sbb_max.textContent = ((2 * Math.tan((DroneData[SelectedDrone]["IRCamAngle_H"] / 2) * Math.PI / 180) * ofh_max.textContent/RoundTo2)).toFixed(0)*RoundTo2;
	
	//fs = ((v_ba / 100) * (v_mzb * 1000 / v_iz)).toFixed(1);
	const fs_min_bal = ((OptBodenAufloesung.min / 100) * (CalcValues.mzb.default * 1000 / CalcValues.iz.default));
	const fs_max_bal = ((OptBodenAufloesung.max / 100) * (CalcValues.mzb.default * 1000 / CalcValues.iz.default));
	
	//v_fs = (v_ba2 * DroneData[SelectedDrone]["IRCamResolution_V"]) / 100 / azb;
	const fs_min_opt = ((OptBodenAufloesung.min * DroneData[SelectedDrone]["IRCamResolution_V"]) / 100 / Sichtbarkeit.max);
	const fs_max_opt = ((OptBodenAufloesung.max * DroneData[SelectedDrone]["IRCamResolution_V"]) / 100 / Sichtbarkeit.min);

	fs_max.textContent = (Math.min(fs_max_bal,fs_max_opt, MaxSpeed)).toFixed(1);
	fs_min.textContent = (Math.min(fs_min_bal,fs_min_opt)).toFixed(1);
	//fs_max.textContent = (fs_max_bal).toFixed(1);
	//fs_min.textContent = (fs_min_bal).toFixed(1);
	//fs1_max.textContent = (fs_max_opt).toFixed(1);
	//fs1_min.textContent = (fs_min_opt).toFixed(1);
	
	var Bemerkung = '<p><b>Bemerkung:</b><br> Die Fluggeschwindigkeit und Flughöhe hängt zusätzlich von der Erfahrung des Teams ab. Es kann auch langsamer oder tiefer geflogen werden, wenn das Team sich dabei sicherer fühlt.';
	
	if (Number(fs_max.textContent)==MaxSpeed) {
		 Bemerkung += ' Geschwindigkeiten über ' + MaxSpeed +' m/s für einen Suchflug sind nicht empfohlen.';
	}
	if (Number(ofh_max.textContent)==MaxHeight) {
		 Bemerkung += ' Flughöhen über ' + MaxHeight +' m sind in der Kategorie Open nicht zulässig.';
	}
	
	Bemerkung += '</p>';
	document.getElementById("remark1").innerHTML = Bemerkung;
	
	if (Number(ofh_min.textContent)!=MaxHeight) {
		document.getElementById("remark2").style.display= 'none';
		document.getElementById("remark2").innerHTML= '<b>Bemerkung: </b>Diese Drohne hat eine sehr hohe Kameraufösung die sie beim Einsatz im legalen Bereich nicht einschränken. Die Flugwerte sollen Sinnvoll gemäss der Flugerfahrung des Teams gesetzt werden.';
		document.getElementById("chapt5").style.display= 'block';
	} else {
		document.getElementById("chapt5").style.display= 'none';
		document.getElementById("remark2").style.display= 'block';
	}
  }

  sliders.forEach(({ slider, input, bubble }, index) => {
    slider.addEventListener('input', () => {
      if (index === 1) isUserChangingSlider1 = true;
      if (index === 5) isUserChangingSlider5 = true;
      syncSliderInput(slider, input, bubble);
      updateAll();
      if (index === 1) isUserChangingSlider1 = false;
      if (index === 5) isUserChangingSlider5 = false;
    });

    input.addEventListener('input', () => {
      if (index === 1) isUserChangingSlider1 = true;
      if (index === 5) isUserChangingSlider5 = true;
      syncInputSlider(input, slider, bubble);
      updateAll();
      if (index === 1) isUserChangingSlider1 = false;
      if (index === 5) isUserChangingSlider5 = false;
    });
  });

  const droneSelect = document.getElementById('Drohne');
  droneSelect.addEventListener('change', (event) => {
    SelectedDrone = event.target.value;
    updateAll();
  });

  updateAll();
  window.addEventListener('resize', updateAll);
};


// Drohnenliste - Drohne selektieren
	function GetDroneList() {
		text = "<h3>Auswahl der Kamera/Drohnen:</h3>";
		text = text +"<ul>";
		for (var key in DroneData) {
			var value = DroneData[key];
			if (value["active"]) {
				text = text+ "<li><b><a href='"+window.location.href+"&drone="+key+"'>" + key + "</a></b>: "+ value["Name"] + "</li>";
			}
		}	
		text = text +"</ul>";
		return text;
	}


	
	function UpdDrone(drone) {
		if (drone!="") {
		  //alert("Drohne geändert auf: " + drone);
		  SelectedDrone=drone;
		  document.getElementById("droneselected").style.display= 'block';
		  //document.getElementById("drone").innerHTML = "<b>"+DroneData[drone]["Name"]+"</b>";
		  document.getElementById("drone").innerHTML = "<i>Wähle deine Kamera/Drohnentyp aus:</i>";
		  //document["dronepic"].src = calciconloc+DroneData[drone]["img"];
		  document.getElementById("dronelist").innerHTML="";
		} else {
		  document.getElementById("droneselected").style.display= 'none';
		  document.getElementById("drone").innerHTML = "<i>Wähle deine Kamera/Drohnentyp aus:</i>";
		  document.getElementById("dronelist").innerHTML= GetDroneList();
		}
	}
	
	// process input settings
	if (SelectedDrone!="") {
		//alert("Drohne geändert auf: " + SelectedDrone);
		UpdDrone(SelectedDrone);
 	    document.getElementById("dronelist").innerHTML= "";
		//document.getElementById("droneselected").style.display= 'block';
		document.getElementById('Drohne').style.display= 'block';
	} else {
 	    document.getElementById("dronelist").innerHTML= GetDroneList();
	    //document.getElementById("droneselected").style.display= 'none';
		document.getElementById('Drohne').style.display= 'none';
	}
/*
	// show hide chapters
	var show_chapt1 = false;
	var show_chapt2 = false;
	var show_chapt3 = false;
	var show_chapt4 = false;
    const chapt1 = document.getElementById('chapt1');
    const chaptpic1 = document.getElementById('chaptpic1');
    const chapt2 = document.getElementById('chapt2');
    const chaptpic2 = document.getElementById('chaptpic2');
    const chapt3 = document.getElementById('chapt3');
    const chaptpic3 = document.getElementById('chaptpic3');
    const chapt4 = document.getElementById('chapt4');
    const chaptpic4 = document.getElementById('chaptpic4');

	ShowHideChapt("all");
	
	function ShowHideChapt(chapt) {
	  //alert("Change View: " + chapt);
		if ((chapt==1) || (chapt=="all")) {
			if 	(show_chapt1) {
				chaptpic1.innerHTML = " <img src='" +  iconloc + "/less.png' height='22px' title='Kaptiel verbergen' onclick='ShowHideChapt(1)'>";
				show_chapt1=false;
				chapt1.style.display= 'block';
			} else {
				chaptpic1.innerHTML = " <img src='" +  iconloc + "/more.png' height='22px' title='Kaptiel anzeigen' onclick='ShowHideChapt(1)'>";
				show_chapt1=true;
				chapt1.style.display= 'none';
			}
		}	
		if ((chapt==2) || (chapt=="all")) {
			if 	(show_chapt2) {
				chaptpic2.innerHTML = " <img src='" +  iconloc + "/less.png' height='22px' title='Kaptiel verbergen' onclick='ShowHideChapt(2)'>";
				show_chapt2=false;
				chapt2.style.display= 'block';
			} else {
				chaptpic2.innerHTML = " <img src='" +  iconloc + "/more.png' height='22px' title='Kaptiel anzeigen' onclick='ShowHideChapt(2)'>";
				show_chapt2=true;
				chapt2.style.display= 'none';
			}
		}	
		if ((chapt==3) || (chapt=="all")) {
			if 	(show_chapt3) {
				chaptpic3.innerHTML = " <img src='" +  iconloc + "/less.png' height='22px' title='Kaptiel verbergen' onclick='ShowHideChapt(3)'>";
				show_chapt3=false;
				chapt3.style.display= 'block';
			} else {
				chaptpic3.innerHTML = " <img src='" +  iconloc + "/more.png' height='22px' title='Kaptiel anzeigen' onclick='ShowHideChapt(3)'>";
				show_chapt3=true;
				chapt3.style.display= 'none';
			}
		}	
		if ((chapt==4) || (chapt=="all")) {
			if 	(show_chapt4) {
				chaptpic4.innerHTML = " <img src='" +  iconloc + "/less.png' height='22px' title='Kaptiel verbergen' onclick='ShowHideChapt(4)'>";
				show_chapt4=false;
				chapt4.style.display= 'block';
			} else {
				chaptpic4.innerHTML = " <img src='" +  iconloc + "/more.png' height='22px' title='Kaptiel anzeigen' onclick='ShowHideChapt(4)'>";
				show_chapt4=true;
				chapt4.style.display= 'none';
			}
		}		
	}
*/

  </script>

</body>
</html>
