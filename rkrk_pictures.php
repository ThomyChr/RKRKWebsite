<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" >
<head>
<style>
  #capture {
    width: 300px;
    height: 200px;
    background-color: lightblue;
    border: 1px solid black;
  }
</style>
</head>
<body>
<?php
  //session_start();
  include_once('scripts/rkrk_gen.php');
  include_once('scripts/rkrk_felddaten.php');
  include_once('scripts/rkrk_geofence.php');
  include_once('scripts/rkrk_menu.php');
  include_once('scripts/rkrk_inputforms.php');
  include_once('rkrk_access_control.php'); 
	
	$pagetitle = "Überprüfen der Waypunkt-Bilder";
	headerpage($pagetitle , false);
	GrantAccess();
	CreateTopMenu();
	PrintRKRKHeader($pagetitle);

  
	// ===========================================
	// Vergleiche Feldkatalog mit FileListe
	// ===========================================


SetMissionLocation();
$MissionSelectionParameter = "MDB=" . $RKRKMissionSelected;

// ==================
// Funktionen für die Rehktitzrettung
// ==================

	//echo $xmlMissionLocation.$RKRKFelderfileXML; 
	$Feldkatalog = GetFeldkatalog($xmlMissionLocation.$RKRKFelderfileXML); 
	//pre_R($Feldkatalog);

	$FontSizeInPoints = 11;
	if ($FontSize== "") {
		$FontSize= "100%";
	}
	

$RegList = array_diff($RegionSettings, array('NO','ALL'));
$EnabledRegion = Array();
$RegionList = Array();
//form to change setting
echo "<i>Wähle Regionen und Felder der Tabelle und Drücke einlesen.:</i><br>";
echo '<form method="post" action="'. htmlspecialchars($_SERVER["PHP_SELF"]) . '">';
$i= 0;
$NoSelection = true;

$TableColor =  GetTableColor ("");
AlternateLineTable($TableColor['even'], $FontSize, $FontSizeInPoints, $TableColor['odd']);

echo "<table class='rkrk-filter-form' width=100%>";
	echo "<tr><td class='rkrk-filter-label'>";
		echo "Regionen: ";
		echo "</td><td>";
		$NoSelection = rkrk_print_region_pills($RegionSettings, $EnabledRegion, (string)$i, '', false);
		foreach ($EnabledRegion as $regKey => $regOn) {
			if (!empty($regOn)) {
				$RegionList[] = $regKey;
			}
		}
		$i++;
	echo "</td><td>";
	echo "<td rowspan=2>";
	//back
	//echo '<button type="button" value="Go back!" style="background-color:transparent; border-color:transparent;vertical-align: middle;" onclick="history.back()"><img src="' . $IconLoc . '/return.png" height="' . $ButtonHeight .'" title="Home"></button>';
	echo MissionLibraryButtons();
	if ($Layout!=2023) {
		echo CreateSiteLink ("Home", "");
		echo CreateSiteLink ("GoBack", "");
	}

	echo "</td>";
	echo "</tr>";
	echo "</table  width=100%>";
	echo '<input type="hidden" name="Act" id="Act" value="save" formaction="' .htmlspecialchars($_SERVER["PHP_SELF"]). '?'. $EnableDeleteSet.'">'. chr(10);
	echo '<input type="submit" name="Einlesen" value="Einlesen" formaction="' .htmlspecialchars($_SERVER["PHP_SELF"]). '?'. $EnableDeleteSet.'"> '. chr(10);
	echo '<br>';
	echo '</form>';
	echo '<br>';
	echo '<br>';

	//pre_r($RegionList);


	echo "<table>";
				// [0] KatID
				// [1] Feldname
				// [2] Flurname
				// [3] GeoID
				// [4] Heigth
				// [5] Pilot
				// [6] Year
				// [7] Corefilename
	echo "<tr>";
	echo "<th>";
	echo "Basename";
	echo "</th>";
	echo "<th>";
	echo "Suchbild";
	echo "</th>";
	echo "<th>";
	echo "Flugbild";
	echo "</th>";
	echo "<th>";
	echo "Flugbild Neu";
	echo "</th>";
	echo "<th>";
	echo "Suchbild Neu";
	echo "</th>";
	echo "</tr>";
	//pre_R($Feldkatalog["21"]["21-014"]);
	
	foreach ($Feldkatalog as $JKey => $JRegion) {
		$i=1;
		if (in_array($JKey,$RegionList)) {
			foreach ($JRegion as $Field) {
				//DO action
				if ($i<=1) {
					$PathInfo = pathinfo($Field["JasonFile"]);
					$FNMeaning = GetMeaningFilename($PathInfo["basename"]); 
					// [0] KatID
					// [1] Feldname
					// [2] Flurname
					// [3] GeoID
					// [4] Heigth
					// [5] Pilot
					// [6] Year
					// [7] Corefilename
					echo "<tr>";
					echo "<td>";
					$field="'FN".$i."'";
					echo "<span id=".$field.">".$PathInfo["basename"]. "</span>";
					echo '<br><button type="button" onclick="capture('.$field.')">Capture</button>';
					echo "</td>";
					echo "<td>";
					$field="'SB".$i."'";
					echo "<span id=".$field."><img src='" . $RKRKExternalLoactionMissionFiles.$RegionSettings[$Feld["JG"]]["Dir"]."/".$Field["SuchfeldBild"]."' width='400' height='400'></span>";
					echo '<br><button type="button" onclick="capture('.$field.')">Capture</button>';
					echo "</td>";
					echo "<td>";
					$field="'FB".$i."'";
					echo "<span id=".$field."><img src='" . $RKRKExternalLoactionMissionFiles.$RegionSettings[$Feld["JG"]]["Dir"]."/".$Field["FlugplanBild"]."' width='400' height='400'></span>";
					echo '<br><button type="button" onclick="capture('.$field.')">Capture</button>';
					echo "</td>";
					echo "<td>";
						$field="'SBN".$i."'";
						echo "<span id=".$field.">";
						// GEOMAP
						$TempContent="";
						//https://tchr-frog.synology.me/rkropen/pub/rkrk_map.php?FFilter=136-007&Reg=ALL&Strt=on&HSP=on&OVR=on&EX=NO&KA=M&MAP=1&Zoom=0.4&GA=gast
						//https://map.geo.admin.ch/#/map?lang=de&center=2669258.62,1224630.3&z=7&topic=ech&layers=ch.swisstopo.swisstlm3d-karte-farbe;KML%7Chttps://tchr-frog.synology.me/rkropen/test/rkrk_dokml.php?Reg=alle%26Strt=on%26Cntr=off%26Area=on%26Pfad=off%26WW=off%26FZ=off%26GDE=off%26JGG=off%26KT=off%26ORT=off%26Poi=off%26HSP=on%26Label=off%26GeLa=off%26Sun=off%26EG=off%26SORA=off%26WF=off%26KA=M%26Zoom=0.4%26FFilter=136-003%26SFilter=%26OVR=on%26MDB=M3T%26GA=gast%26WebAccess=test&bgLayer=ch.swisstopo.swissimage
						$parameterset = str_replace("&","%26","Reg=ALL&Strt=on&Cntr=off&Area=on&Pfad=off&GDE=off&JGG=off&Poi=off&HSP=on&OVR=on&Label=off&EX=NO&KA=M&MAP=1&Zoom=0.6&FFilter=" . $Field["FeldID"]);
						$geoweblocation = str_replace($searchURL, $replaceURL, $weblocation);
						$PicZoom = 1.5;
						$Search = "";
						$KA = 'ch.swisstopo.swissimage';
						if (($Field["Grundstueckdaten"]["Zentrum"]!="") and (trim($Field["Grundstueckdaten"]["Zentrum"])!=",")) {
							$SemPos = strpos($Field["Grundstueckdaten"]["Zentrum"], ",");
							if ($SemPos != false) {
								$Centerpoint["Lat"] = trim(explode(",", $Field["Grundstueckdaten"]["Zentrum"])[1]);
								$Centerpoint["Lon"] =  trim(explode(",", $Field["Grundstueckdaten"]["Zentrum"])[0]);
							}
							//pre_R($Centerpoint);
							$MidPointFromFFilter = GetLSV95EastNorthFromLonLat($Centerpoint);
							//$MapZoom= 6.5;
							$FieldDiameter = DiameterOfField($Field["FeldID"]);
							//$MapZoom = 6.5/400*$FieldDiameter;
							$ZoomPower =-0.13;
							$MapZoom = 6.5*pow($FieldDiameter/400,$ZoomPower);
							//echo $parameterset;
							$KartenArt =  $MapSettings["Types"]["S"]["Type"];
							//echo KartenArt($KA);
							$TempContent .= "<iframe allow='geolocation' src='https://map.geo.admin.ch/#/map?lang=de&center=" . $MidPointFromFFilter["LV95East"] . "," . $MidPointFromFFilter["LV95North"] . "&z=" . $MapZoom . "&topic=ech&layers=ch.swisstopo.swisstlm3d-karte-farbe;KML%7Chttps://tchr-frog.synology.me/rkropen/test/rkrk_dokml.php?". $parameterset . "%26WebAccess=test&bgLayer=".$KA."' width='500' height='500' frameborder='0' style='border:0'></iframe >";
						}
						echo $TempContent . chr(10);
						echo "</span>";
						echo '<br><button type="button" onclick="capture('.$field.')">Capture</button>';
					echo "</td>";
					echo "<td>";
						$field="'SBN".$i."'";
						echo "<span id=".$field.">";
						// GEOMAP
						$TempContent="";
						//https://tchr-frog.synology.me/rkropen/pub/rkrk_map.php?FFilter=136-004&Reg=ALL&Strt=on&Area=on&Pfad=on&HSP=on&FLP=on&EX=NO&KA=M&MAP=1&Zoom=0.8&GA=gast
						//https://map.geo.admin.ch/#/map?lang=de&center=2669258.62,1224630.3&z=10.269&topic=ech&layers=ch.swisstopo.swisstlm3d-karte-farbe;KML%7Chttps://tchr-frog.synology.me/rkropen/test/rkrk_dokml.php?Reg=alle%26Strt=on%26Cntr=off%26Area=on%26Pfad=on%26WW=off%26FZ=off%26GDE=off%26JGG=off%26KT=off%26ORT=off%26Poi=off%26HSP=on%26Label=off%26GeLa=off%26Sun=off%26EG=off%26SORA=off%26WF=off%26KA=M%26Zoom=0.8%26FFilter=136-003%26SFilter=%26FLP=on%26MDB=M3T%26GA=gast%26WebAccess=test&bgLayer=ch.swisstopo.swissimage

						$parameterset = str_replace("&","%26","Reg=ALL&Strt=on&Cntr=off&Area=on&Pfad=on&GDE=off&JGG=off&Poi=off&HSP=on&FLP=on&Label=off&EX=NO&KA=M&MAP=1&Zoom=0.8&FFilter=" . $Field["FeldID"]);
						$geoweblocation = str_replace($searchURL, $replaceURL, $weblocation);
						$PicZoom = 1.5;
						$Search = "";
						$KA = 'ch.swisstopo.swissimage';
						if (($Field["Grundstueckdaten"]["Zentrum"]!="") and (trim($Field["Grundstueckdaten"]["Zentrum"])!=",")) {
							$SemPos = strpos($Field["Grundstueckdaten"]["Zentrum"], ",");
							if ($SemPos != false) {
								$Centerpoint["Lat"] = trim(explode(",", $Field["Grundstueckdaten"]["Zentrum"])[1]);
								$Centerpoint["Lon"] =  trim(explode(",", $Field["Grundstueckdaten"]["Zentrum"])[0]);
							}
							//pre_R($Centerpoint);
							$MidPointFromFFilter = GetLSV95EastNorthFromLonLat($Centerpoint);
							//$MapZoom= 9.5;
							$FieldDiameter = DiameterOfField($Field["FeldID"]);
							//$MapZoom = 9.5/400*$FieldDiameter;
							$ZoomPower =-0.13;
							$MapZoom = 9.5*pow($FieldDiameter/400,$ZoomPower);
							//echo $parameterset;
							$KartenArt =  $MapSettings["Types"]["S"]["Type"];
							//echo KartenArt($KA);
							//$TempContent .= "<iframe allow='geolocation' src='https://map.geo.admin.ch/#/embed?lang=de&center=" . $MidPointFromFFilter["LV95East"] . "," . $MidPointFromFFilter["LV95North"] . "&z=" . $MapZoom . "&bgLayer=ch.swisstopo.swissimage&topic=ech&layers=ch.bfe.elektrische-anlagen_ueber_36,f;ch.bazl.luftfahrthindernis,f;ch.bazl.einschraenkungen-drohnen,f;KML%7Chttps://tchr-frog.synology.me/rkropen/test/rkrk_dokml.php?". $parameterset . "'' width='400' height='400' frameborder='0' style='border:0'></iframe >";
							
							$TempContent .= "<iframe allow='geolocation' src='https://map.geo.admin.ch/#/map?lang=de&center=" . $MidPointFromFFilter["LV95East"] . "," . $MidPointFromFFilter["LV95North"] . "&z=" . $MapZoom . "&topic=ech&layers=ch.swisstopo.swisstlm3d-karte-farbe;KML%7Chttps://tchr-frog.synology.me/rkropen/test/rkrk_dokml.php?". $parameterset . "%26WebAccess=test&bgLayer=".$KA."' width='500' height='500' frameborder='0' style='border:0'></iframe >";
							echo "https://map.geo.admin.ch/#/map?lang=de&center=" . $MidPointFromFFilter["LV95East"] . "," . $MidPointFromFFilter["LV95North"] . "&z=" . $MapZoom . "&topic=ech&layers=ch.swisstopo.swisstlm3d-karte-farbe;KML%7Chttps://tchr-frog.synology.me/rkropen/test/rkrk_dokml.php?". $parameterset . "%26WebAccess=test&bgLayer=".$KA."'";
							
						}
						echo $TempContent . chr(10);
						echo "</span>";
						echo '<br><button type="button" onclick="capture('.$field.')">Capture</button>';
						echo '<br><button type="button" onclick="getScreenshotOfElement('.$field.', 0, 0, 500, 500)">Capture</button>';
						

					echo "</td>";
					echo "</tr>";
				}
				$i++;
			}	
		}
	}
	echo "</table>";
?>
<h1 id="capture">Hellooooo</h1>
<button id="btn">Capture</button>

<script src="scripts/html2png.js"></script>
<script>

function capture(selecter) {
  const captureElement = document.querySelector(selecter);
  html2canvas(captureElement)
    .then(canvas => {
      const image = canvas.toDataURL('image/png');
	  const a = document.createElement('a');
      a.setAttribute('download', 'my-image.png');
      a.setAttribute('href', image);
      a.click();
    });
}



const btn = document.querySelector('#btn');
btn.addEventListener('click', capture('#capture'));

/*
const capture = async () => {
  const canvas = document.createElement("canvas");
  const context = canvas.getContext("2d");
  const video = document.createElement("video");

  try {
    const captureStream = await navigator.mediaDevices.getDisplayMedia();
    video.srcObject = captureStream;
    context.drawImage(video, 0, 0, window.width, window.height);
    const frame = canvas.toDataURL("image/png");
    captureStream.getTracks().forEach(track => track.stop());
    window.location.href = frame;
  } catch (err) {
    console.error("Error: " + err);
  }
};

capture();
*/

function getScreenshotOfElement(element, posX, posY, width, height, callback) {
    html2canvas(element, {
        onrendered: function (canvas) {
            var context = canvas.getContext('2d');
            var imageData = context.getImageData(posX, posY, width, height).data;
            var outputCanvas = document.createElement('canvas');
            var outputContext = outputCanvas.getContext('2d');
            outputCanvas.width = width;
            outputCanvas.height = height;

            var idata = outputContext.createImageData(width, height);
            idata.data.set(imageData);
            outputContext.putImageData(idata, 0, 0);
            callback(outputCanvas.toDataURL().replace("data:image/png;base64,", ""));
        },
        width: width,
        height: height,
        useCORS: true,
        taintTest: false,
        allowTaint: false
    });
}

</script>

</body>
</html>