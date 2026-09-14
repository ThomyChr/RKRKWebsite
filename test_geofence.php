<?php
  //session_start();
  include_once('scripts/rkrk_gen.php');
  include_once('scripts/rkrk_feld.php');
  include_once('scripts/rkrk_felddaten.php');
  include_once('scripts/rkrk_geofence.php');
  include_once('scripts/rkrk_map_functions.php');
  include_once('scripts/SwisstopoConverter.php');
	
	//check impropriate IP addresses accessing page
	CheckIPandExit();

// ==================
// Funktionen für die Rehktitzrettung
// ==================

function GetGeoData($RefPoint, $Fielddata) {
	echo "RefPoint: " . $RefPoint ."<br>";
	echo "Gde: " . $GeoGde . "<br>";
	$GeoGde = GetGdeOfPOint($RefPoint);
	echo "Gde: " . $GeoGde . "<br>";
	$GeoJgd = GetJgdOfPOint($RefPoint);
	echo "Jgd: " . $GeoJgd . "<br>";
	$GeoFlgZ = GetFlgZOfPOint($RefPoint);
	echo "FlgZ: " . $GeoFlgZ . "<br>";
	$GeoWarnFlgZ = GetWarnungFlgZOfPOint($RefPoint);
	echo "Warnung FlgZ: " . $GeoWarnFlgZ . "<br>";
	$GeoWarnSun = GetSunOfPOint($RefPoint);
	echo "Warnung Sun: " . $GeoWarnSun . "<br>";
	$GeoWarnSora = GetSoraOfPOint($Fielddata);
	echo "Warnung SORA: " . $GeoWarnSora . "<br>";
} 


//TESTING
/*
echo DiameterOfField("21-018"); 
echo "<br><br>";


$Fieldlist= Array("21-063", "21-062", "21-018", "21-053");
echo DiameterOfFieldList ($Fieldlist);
*/

//LAde Felddaten
$Feldkatalog = GetFeldkatalog($xmlMissionLocation.$RKRKFelderfileXML); 
//Pre_R($Feldkatalog);


//$Point = Array("Lat" => "47.254371557105", "Lon" => "8.4780406885091");



// TEST SUN/ SORA Annäherung
//-------------------------
/*
// 136-005 SUN
$Felddaten = $Feldkatalog["136"]["136-005"];
echo $RefPoint= trim($Felddaten["Grundstueckdaten"]["Zentrum"]);

echo "<br><br>";
echo $Felddaten["FeldID"] . "<br>";
GetGeoData($RefPoint, $Felddaten);

echo "<br><br>";
// 136-032 SORA
echo "<br><br>";
$Felddaten = $Feldkatalog["136"]["136-032"];
echo $RefPoint= trim($Felddaten["Grundstueckdaten"]["Zentrum"]);
echo $Felddaten["FeldID"] . "<br>";

GetGeoData($RefPoint,$Felddaten);

echo "<br><br>";
*/

// 28-106 JG/CTR
$Felddaten = $Feldkatalog["28"]["28-106"];
echo $RefPoint= trim($Felddaten["Grundstueckdaten"]["Zentrum"]) ."<br>";;
echo $Felddaten["FeldID"] . "<br>";
//GetGeoData($RefPoint,$Felddaten);
echo GetFlgZOfPOint($RefPoint) . "<br>";
echo "<br><br>";

// 25-050 JG/CTR
$Felddaten = $Feldkatalog["25"]["25-050"];
echo $RefPoint= trim($Felddaten["Grundstueckdaten"]["Zentrum"]) ."<br>";;
echo $Felddaten["FeldID"] . "<br>";
//GetGeoData($RefPoint,$Felddaten);
echo GetFlgZOfPOint($RefPoint) . "<br>";
echo "<br><br>";

// 28-057 JG/CTR
$Felddaten = $Feldkatalog["28"]["28-057"];
echo $RefPoint= trim($Felddaten["Grundstueckdaten"]["Zentrum"]) ."<br>";;
echo $Felddaten["FeldID"] . "<br>";
//GetGeoData($RefPoint,$Felddaten);
echo GetFlgZOfPOint($RefPoint) . "<br>";
echo "<br><br>";

/*
// 24-032 JG/CTR NGT1
$Felddaten = $Feldkatalog["24"]["24-032"];
echo $RefPoint= trim($Felddaten["Grundstueckdaten"]["Zentrum"]) ."<br>";;
echo $Felddaten["FeldID"] . "<br>";
//GetGeoData($RefPoint,$Felddaten);
echo GetFlgZOfPOint($RefPoint) . "<br>";
echo "<br><br>";

// 24-033 JG/CTR NGT1
$Felddaten = $Feldkatalog["24"]["24-033"];
echo $RefPoint= trim($Felddaten["Grundstueckdaten"]["Zentrum"]) ."<br>";;
echo $Felddaten["FeldID"] . "<br>";
//GetGeoData($RefPoint,$Felddaten);
echo GetFlgZOfPOint($RefPoint) . "<br>";
echo "<br><br>";


// 24-073 JG/CTR STP
$Felddaten = $Feldkatalog["24"]["24-073"];
echo $RefPoint= trim($Felddaten["Grundstueckdaten"]["Zentrum"]) ."<br>";;
echo $Felddaten["FeldID"] . "<br>";
//GetGeoData($RefPoint,$Felddaten);
echo GetFlgZOfPOint($RefPoint) . "<br>";
echo "<br><br>";
*/
// 28-002 JG/CTR
$Felddaten = $Feldkatalog["28"]["28-002"];
echo $RefPoint= trim($Felddaten["Grundstueckdaten"]["Zentrum"]) ."<br>";
echo $Felddaten["FeldID"] . "<br>";
//GetGeoData($RefPoint,$Felddaten);
echo GetFlgZOfPOint($RefPoint) . "<br>";
echo "<br><br>";

/*
// 32-036 JG/CT NGT
$Felddaten = $Feldkatalog["32"]["32-036"];
echo $RefPoint= trim($Felddaten["Grundstueckdaten"]["Zentrum"]) ."<br>";;
echo $Felddaten["FeldID"] . "<br>";
//GetGeoData($RefPoint,$Felddaten);
echo GetGdeOfPOint($RefPoint) . "<br>";
echo "<br><br>";

// 32-021 JG/CT NGT
$Felddaten = $Feldkatalog["32"]["32-021"];
echo $RefPoint= trim($Felddaten["Grundstueckdaten"]["Zentrum"]) ."<br>";;
echo $Felddaten["FeldID"] . "<br>";
//GetGeoData($RefPoint,$Felddaten);
echo GetGdeOfPOint($RefPoint) . "<br>";
echo "<br><br>";
*/
/*
// 28-091 JG/CTR NGT
$Felddaten = $Feldkatalog["28"]["28-091"];
echo $RefPoint= trim($Felddaten["Grundstueckdaten"]["Zentrum"]) ."<br>";;
echo $Felddaten["FeldID"] . "<br>";
//GetGeoData($RefPoint,$Felddaten);
echo GetFlgZOfPOint($RefPoint) . "<br>";
echo "<br><br>";
*/

/*
// UMWANDLUNG
//------------
//Kirchturm Steinhausen
$Point = Array("Lat" => "47.19603", "Lon" => "8.48521");
//CH1903+/LV95  2679306.6 1227759.7
//CH1903/LV03  679305.81 227759.85

echo "East:" . $LV95EastNorth["LV95East"] = WGStoCHx($Point["Lat"], $Point["Lon"]) . " <br>"; 
echo "North:" . $LV95EastNorth["LV95North"] = WGStoCHy($Point["Lat"], $Point["Lon"]) . " <br>"; 
pre_R(GetLSV95EastNorthFromLonLat ($Point));
echo "<br>.start.<br>";
//fromWGSToMN95(47.19603, 8.48521);
pre_r(fromWGSToMN95(47.19603, 8.48521));
echo "<br>.works.<br>";
*/


echo "TEST Diameter<br>"; 
echo "<br><br>ZG3-134,ZG3-112<br>"; 
echo DiameterOfFieldList(Array("ZG3-134","ZG3-112"));
echo "<br><br>ZG3-134<br>"; 
echo DiameterOfFieldList(Array("ZG3-134"));
echo "<br><br>zg3-134<br>"; 
echo DiameterOfFieldList(Array("zg3-134"));
?>