<?php
	//header('Access-Control-Allow-Origin: *');
	header("Content-type: text/xml");
	//header("Content-type: application/json");
	//session_start();
	include_once('scripts/rkrk_gen.php');

// ==================
// Funktionen für die Rehktitzrettung
// ==================

// ==================
// Global variables
// ==================

$WebMapIcon = $WebLocation;

// ==================
// Header Page
// ==================
//include_once('rkrk_gen.php');

function kmlheader($addTitle ="") {
   $output= "";
   
	$output .= '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . chr(10);
	//$output .= '<kml xmlns="http://www.opengis.net/kml/2.2" xmlns:gx="http://www.google.com/kml/ext/2.2" xmlns:kml="http://www.opengis.net/kml/2.2" xmlns:atom="http://www.w3.org/2005/Atom">'. chr(10);
	
	$output .= '<kml xmlns="http://www.opengis.net/kml/2.2" xmlns:gx="http://www.google.com/kml/ext/2.2" 	xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.opengis.net/kml/2.2 https://developers.google.com/kml/schema/kml22gx.xsd">';
	
/*
    $output .=  '<kml xmlns="http://www.google.com/earth/kml/2">';
	$output .=  '<kml xmlns="http://www.opengis.net/kml/2.2" xmlns:gx="http://www.google.com/kml/ext/2.2" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.opengis.net/kml/2.2 https://developers.google.com/kml/schema/kml22gx.xsd">' . chr(10); 
*/	
	$output .=  chr(10);
	$output .=  '<Document>' . chr(10);
	$output .=  '<name>RKRK '. $addTitle . '</name>' . chr(10);
	//$output .=  '<name><![CDATA[RKRK '. $addTitle . ']]></name>' . chr(10);
	return $output;
}



function kmlfooter() {
    $output= "";
	$output .=  '</Document>'. chr(10);
	$output .=  '</kml>'. chr(10);
	return $output;
}

//======================
// KML styles
//======================

Function kml_flugfeld_style($StyleName, $AreaColor, $AreaWidth, $LineColor, $Scale, $Target, $Condition) {
	if ($Condition) {
		$output = "";
		$output .=  '  <!-- ===== STYLE ' . $StyleName. ' ====== -->' . Chr(10);
		$output .=  '    <Style id=' . Chr(34) . $StyleName . Chr(34) . '>' . Chr(10);
		$output .=  '      <LineStyle>' . Chr(10);
		$output .=  '        <color>' . $AreaColor . '</color>' . Chr(10);
		$output .=  '        <width>' . $AreaWidth . '</width>' . Chr(10);
		$output .=  '      </LineStyle>' . Chr(10);
		$output .=  '      <PolyStyle>' . Chr(10);
		$output .=  '        <color>' . $LineColor . '</color>' . Chr(10);
		$output .=  '      </PolyStyle>' . Chr(10);
		$output .=  '    </Style>' . Chr(10);
		$output .=  Chr(10) . Chr(10);
		Return $output;
	}
}

Function kml_flugpfad_style($StyleName, $LineColor, $AreaColor,  $LineWidth,  $Target,  $Condition) {
	if ($Condition) {
		$output = "";
		$output .=  '  <!-- ===== STYLE  ' . $StyleName. ' ====== -->' . Chr(10);
		$output .=  '    <Style id=' . Chr(34) . $StyleName . Chr(34) . '>' . Chr(10);
		$output .=  '      <LineStyle>' . Chr(10);
		$output .=  '        <color>' . $LineColor . '</color>' . Chr(10);
		$output .=  '        <width>' . $LineWidth . '</width>' . Chr(10);
		$output .=  '      </LineStyle>' . Chr(10);
		$output .=  '      <PolyStyle>' . Chr(10);
		$output .=  '        <color>' . $AreaColor . '</color>' . Chr(10);
		$output .=  '      </PolyStyle>' . Chr(10);
		$output .=  '    </Style>' . Chr(10);
		$output .=  Chr(10) . Chr(10);
		Return $output;
	}
}


/* new geo.map.admin.ch from 1. June 2024

	<Placemark id="drawing_feature_1717842683226">
		<name>test</name>
		<description>Das ist die Beschriftung</description>
		<Style>
			<IconStyle>
				<scale>1.125</scale>
				<Icon>
					<href>https://map.geo.admin.ch/api/icons/sets/default/icons/001-marker@1x-255,0,0.png</href>
					<gx:w>48</gx:w>
					<gx:h>48</gx:h>
				</Icon>
				<hotSpot x="24" y="6" xunits="pixels" yunits="pixels"/>
			</IconStyle>
			<LabelStyle>
				<color>ffffffff</color>
			</LabelStyle>
		</Style>
		<ExtendedData>
			<Data name="textOffset"><value>0,-42</value></Data>
			<Data name="type"><value>marker</value></Data>
		</ExtendedData>
		<Point>
			<coordinates>7.820197504612824,46.90709569687998</coordinates>
		</Point>
	</Placemark>
*/	

Function kml_POI_style($StyleName, $Icon ,$IconColor, $IconScale, $LabelColor, $Scale, $Target ,$Condition) {
	global $NewMapGeoAdminPics;
	if ($Condition) {
		$output = "";
		$output .=  '  <!-- ===== STYLE  ' . $StyleName. ' ====== -->' . Chr(10);
		$output .=  '    <Style id=' . Chr(34) . $StyleName . Chr(34) . '>' . Chr(10);
		$output .=  '      <IconStyle>' . Chr(10);
		$output .=  '      <scale>' . $IconScale . '</scale>' . Chr(10);
		If (($Target <> 'GoogleEarth') or ($NewMapGeoAdminPics)) {
			$output .=  '     <color>' . $IconColor . '</color>' . Chr(10);
		}
		if ($Icon<>'') {
			$output .=  '        <Icon>' . Chr(10);
			if (($NewMapGeoAdminPics) and true) {
				//$Markersize = round(($Scale*$IconScale)/2,1);
				$Markersize = "0.5";
				$Markertype = "001-marker";
				if ($StyleName=="label")
					$output .=  '          <href>' . $Icon . '</href>' . Chr(10);
				elseif ($StyleName=="startpoint")
					$output .=  '          <href>' . 'https://map.geo.admin.ch/api/icons/sets/default/icons/'.$Markertype.'@'.$Markersize.'x-245,177,131.png' . '</href>' . Chr(10);
				elseif ($StyleName=="centerpoint")
					$output .=  '          <href>' . 'https://map.geo.admin.ch/api/icons/sets/default/icons/'.$Markertype.'@'.$Markersize.'x-124,156,214.png' . '</href>' . Chr(10);
				elseif ($StyleName=="FPLF")
					$output .=  '          <href>' . 'https://map.geo.admin.ch/api/icons/sets/default/icons/'.$Markertype.'@'.$Markersize.'x-237,125,49.png' . '</href>' . Chr(10);
				elseif ($StyleName=="FPLS")
					$output .=  '          <href>' . 'https://map.geo.admin.ch/api/icons/sets/default/icons/'.$Markertype.'@'.$Markersize.'x-255,171,171.png' . '</href>' . Chr(10);
				elseif ($StyleName=="FPLP")
					$output .=  '          <href>' . 'https://map.geo.admin.ch/api/icons/sets/default/icons/'.$Markertype.'@'.$Markersize.'x-106,153,85.png' . '</href>' . Chr(10);
				elseif ($StyleName=="FPLN")
					$output .=  '          <href>' . 'https://map.geo.admin.ch/api/icons/sets/default/icons/'.$Markertype.'@'.$Markersize.'x-155,230,153.png' . '</href>' . Chr(10);
				elseif ($StyleName=="FPLZ")
					$output .=  '          <href>' . 'https://map.geo.admin.ch/api/icons/sets/default/icons/'.$Markertype.'@'.$Markersize.'x-175,206,235.png' . '</href>' . Chr(10);
				elseif ($StyleName=="FLPL")
					$output .=  '          <href>' . 'https://map.geo.admin.ch/api/icons/sets/default/icons/'.$Markertype.'@'.$Markersize.'x-251,216,5.png' . '</href>' . Chr(10);
				else
					$output .=  '          <href>' . 'https://map.geo.admin.ch/api/icons/sets/default/icons/'.$Markertype.'@'.$Markersize.'x-255,0,0.png' . '</href>' . Chr(10);
					//$output .=  '          <href>' . 'https://map.geo.admin.ch/api/icons/sets/default/icons/001-marker@1x-255,0,0.png' . '</href>' . Chr(10);
			} else {
				$output .=  '          <href>' . $Icon . '</href>' . Chr(10);
				//$output .=  '          <scale>'.$Scale.'</scale>' . Chr(10);
			}
			//$output .=  '<gx:w>48</gx:w>' . Chr(10);
			//$output .=  '<gx:h>48</gx:h>' . Chr(10);
			$output .=  '        </Icon>' . Chr(10);
			//$output .=  '        <hotSpot x="24" y="6" xunits="pixels" yunits="pixels"/>' . Chr(10);
		}
		$output .=  '      </IconStyle>' . Chr(10);
		$output .=  '      <LabelStyle>' . Chr(10);
		$output .=  '        <scale>' . $Scale. '</scale>' . Chr(10);
		If ($Target = 'GoogleEarth') {
			$output .=  '        <color>' . $LabelColor . '</color>' . Chr(10);
		}
		$output .=  '      </LabelStyle>' . Chr(10);
		$output .=  '    </Style>' . Chr(10);

		//$output .=  '		<ExtendedData>' . chr(10);
		//$output .=  '			<Data name="type">' . chr(10);
		//$output .=  '	 			<value>annotation</value>' . chr(10);
		//$output .=  '			</Data>' . chr(10);
		//$output .=  '			<Data name="textOffset">' . chr(10);
		//$output .=  '				<value>0,-44.75</value>' . chr(10);
		//$output .=  '			</Data>' . chr(10);
		//$output .=  '		</ExtendedData>' . chr(10);

		$output .=  Chr(10) . Chr(10);
		Return $output;
	}
}

Function kml_Area_style($StyleName,$LineColor, $LineScale, $PolyColor, $Fill, $Outline, $Target ,$Condition) {
	if ($Condition) {
		$output = "";
		$output .=  '  <!-- ===== STYLE  ' . $StyleName. ' ====== -->' . Chr(10);
		$output .=  '    <Style id=' . Chr(34) . $StyleName . Chr(34) . '>' . Chr(10);
		$output .=  '      <LineStyle>' . Chr(10);
		$output .=  '        <color>' . $LineColor . '</color>' . Chr(10);
		$output .=  '        <width>' . $LineScale . '</width>' . Chr(10);
		$output .=  '      </LineStyle>' . Chr(10);
		$output .=  '      <PolyStyle>' . Chr(10);
		$output .=  '        <color>' . $PolyColor . '</color>' . Chr(10);
		$output .=  '        <fill>' . $Fill . '</fill>' . Chr(10);
		$output .=  '        <outline>' . $Outline . '</outline>' . Chr(10);
		$output .=  '      </PolyStyle>' . Chr(10);
		if ($StyleName == "Alert") {
			$output .=  '      <IconStyle>' . Chr(10);
			$output .=  '        <scale>1</scale>' . Chr(10);
			$output .=  '        <Icon>' . Chr(10);
			$output .=  '          <href>https://map.geo.admin.ch/api/icons/sets/default/icons/024-caution@1x-255,165,0.png</href>' . Chr(10);
			$output .=  '        </Icon>' . Chr(10);
			$output .=  '      </IconStyle>' . Chr(10);
		}
		$output .=  '    </Style>' . Chr(10);
		$output .=  Chr(10) . Chr(10);
		Return $output;
	}
}


function kmlstyles($Zoom, &$ListOfStylesUsed) {
global $WebMapIcon, $MapIconStyle, $Overview, $SingleColor, $Flugplan, $MapTyp, $FlightZoneOptions; 
// definitions
//--------------
    $output = "";
	$IconLocation =  $WebMapIcon . $MapIconStyle . "";
    $RedColor = "7d2222fc";
    $DRedColor = "7d0404B8";
    $GreenColor = "7d1cd97d";
    $LGreenColor = "7d16b567";
    $MGreenColor = "7d05A037";
    $DGreenColor = "7d2d7653";
    //$YellowColor = "ff00e7fc";
    $YellowColor = "ff00D6FC";
    $OrangeColor = "7d25a8f9";
    $MOrangeColor = "7d1d85c4";
    $GreyColor = "7dbdbdbd";
    $BlueColor = "7dd18802";
    $MBlueColor = "7da66b00";
    $DOrangeColor = "7d0096FF";
    $ViolettColor = "7dE8BEE8";
  
    $BlackColor = "ff000000";
    $LRedColor = "ff040492";
    $LGreenColor = "ff079c55";
    $NGreenColor = "ff05723E";
    $LDOrangeColor = "ff00A9FF";
    $LOrangeColor = "ff1e87c8";
    $NOrangeColor = "ff186ca1";
    $LGreyColor = "ff949494";
    $LYellowColor = "7d00eaff";
    $LBlueColor = "ff8a5a02";
    $NBlueColor = "ff694400";
    $LViolettColor = "ff995999";

	if ($SingleColor) {
		$MainSingleColorLight = "3316b567";
		$MainSingleColor = "bb16b567";
		$MainBorderColor = "002d7653";
	}
	if (($Overview) or ($Flugplan)) {
		$MainSingleColor = "7d2222fc";
		$MainSingleColorLight = "444040ff";
		$MainBorderColor = "ff4040ff";
	}

// map settings
//--------------
  
  If (true) {
    $Public = True;
  } else {
    $Public = False;
  }

  
  If (true) {
    $Target = "GoogleEarth";
  }
  
  If ($Target = "GoogleEarth" Or $Public) {
    $AreaWidth = "4";
    $LineWidth = "2";
  } else {
    $AreaWidth = "1.5";
    $LineWidth = "2";
  }

//sympbols
$Mapstyle = "MapGeoAdmin";
	

    $SymGdeLabel =  $WebMapIcon . $MapIconStyle . "GdeLabel.png";
    $SymJGLabel =  $WebMapIcon . $MapIconStyle . "JGLabel.png";
    $SymKtLabel =  $WebMapIcon . $MapIconStyle . "KtLabel.png";
    $SymOrtLabel =  $WebMapIcon . $MapIconStyle . "OrtLabel.png";
	
    //$SymGdeLabel =  $WebMapIcon . $MapIconStyle . "void.png";
    //$SymJGLabel =  $WebMapIcon . $MapIconStyle . "void.png";
    //$SymKtLabel =  $WebMapIcon . $MapIconStyle . "void.png";
    //$SymOrtLabel =  $WebMapIcon . $MapIconStyle . "void.png";

switch ($Mapstyle) {
    case "GoogleEarth":
        $SymLabel =  $WebMapIcon . $MapIconStyle . "void.png";
        $SymFlugplatz =  $WebMapIcon . $MapIconStyle . "Flugplatz.png";
        $SymDrohneRKRK =  $WebMapIcon . $MapIconStyle . "DrohnenStaoRKRK.png";
        $SymDrohneRKRS =  $WebMapIcon . $MapIconStyle . "DrohnenStaoRKRS.png";
        $SymDrohneRKRP =  $WebMapIcon . $MapIconStyle . "DrohnenStaoRKRP.png";
        $SymDrohneRKRN =  $WebMapIcon . $MapIconStyle . "DrohnenStaoRKRN.png";
        $SymDrohneRKRX =  $WebMapIcon . $MapIconStyle . "DrohnenStaoRKRX.png";
        $SymDrohneZug =  $WebMapIcon . $MapIconStyle . "DrohnenStaoZug.png";
        $SymSuchfeld =  $WebMapIcon . $MapIconStyle . "Suchfeld.png";
        $SymStartplatz =  $WebMapIcon . $MapIconStyle . "Startplatz.png";
        $SymParkplatz =  $WebMapIcon . $MapIconStyle . "Parkplatz.png";
		$SymJGR = $WebMapIcon . $MapIconStyle . "jaeger.png";  // Jäger
		$SymJGR_dunkel = $WebMapIcon . $MapIconStyle . "jaeger_dark.png";  // Jäger
		
        break;
    // links stehen nicht mehr zur Verfügung (TC 13.01.2025)
	//case "GoogleMap":
    //    $SymFlugplatz = "https://earth.google.com/earth/rpc/cc/icon?color=fbc02d&amp;id=2012&amp;scale=4";
    //    $SymDrohneRKRK = "https://earth.google.com/earth/rpc/cc/icon?color=ffabab&amp;id=2157&amp;scale=4";
    //    $SymDrohneRKRS = "https://earth.google.com/earth/rpc/cc/icon?color=ed7d31&amp;id=2157&amp;scale=4";
    //    $SymDrohneRKRP = "https://earth.google.com/earth/rpc/cc/icon?color=1976d2&amp;id=2157&amp;scale=4";
    //    $SymDrohneRKRN = "https://earth.google.com/earth/rpc/cc/icon?color=ffc000&amp;id=2157&amp;scale=4";
    //    $SymDrohneRKRX = "https://earth.google.com/earth/rpc/cc/icon?color=7f7gf7f&amp;id=2157&amp;scale=4";
    //    $SymDrohneZug = "https://earth.google.com/earth/rpc/cc/icon?color=afceeb&amp;id=2157&amp;scale=4";
    //    $SymSuchfeld = "https://earth.google.com/earth/rpc/cc/icon?color=388e3c&amp;id=2157&amp;scale=4";
    //    $SymStartplatz = "https://earth.google.com/earth/rpc/cc/icon?color=1976d2&amp;id=2157&amp;scale=4";
    //    $SymParkplatz = "https://earth.google.com/earth/rpc/cc/icon?color=1976d2&amp;id=2157&amp;scale=4";
	//	$SymJGR = $WebMapIcon . $MapIconStyle . "void.png";  // Jäger
    //    break;
    case "MapGeoAdmin":
        $SymLabel =  $WebMapIcon . $MapIconStyle . "void.png";
        $SymFlugplatz =  $WebMapIcon . $MapIconStyle . "Flugplatz_60.png";
        $SymDrohneRKRK =  $WebMapIcon . $MapIconStyle . "DrohnenStaoRKRK_60.png";
        $SymDrohneRKRS =  $WebMapIcon . $MapIconStyle . "DrohnenStaoRKRS_60.png";
        $SymDrohneRKRP =  $WebMapIcon . $MapIconStyle . "DrohnenStaoRKRP_60.png";
        $SymDrohneRKRN =  $WebMapIcon . $MapIconStyle . "DrohnenStaoRKRN_60.png";
        $SymDrohneRKRX =  $WebMapIcon . $MapIconStyle . "DrohnenStaoRKRX_60.png";
        $SymDrohneZug =  $WebMapIcon . $MapIconStyle . "DrohnenStaoZug_60.png";
		$SymSuchfeld =  $WebMapIcon . $MapIconStyle . "Suchfeld.png";
        $SymStartplatz =  $WebMapIcon . $MapIconStyle . "Startplatz.png";
        $SymParkplatz =  $WebMapIcon . $MapIconStyle . "Parkplatz.png";
		$SymJGR = $WebMapIcon . $MapIconStyle . "jaeger.png";  // Jäger
		$SymJGR_dunkel = $WebMapIcon . $MapIconStyle . "jaeger_dark.png";  // Jäger
		// Grösse auf map.geo.admin.ch nicht einstellbar draum void.png
/*
    case "MapGeoAdminIntern":
		$AdminMapIcons = "https://map.geo.admin.ch/api/icons/sets/default/icons/";
		//Options: 001-marker,002-circle,005-star,008-circle-stroked,062-embassy, 078-parking, 083-police, 034-airport
		//<IconStyle>
		//	<Icon>
		//		<href>https://map.geo.admin.ch/api/icons/sets/default/icons/005-star@1x-255,0,0.png</href>
		//		<gx:w>48</gx:w>
		//		<gx:h>48</gx:h>
		//	</Icon>
		//</IconStyle>
        $SymLabel = $WebMapIcon . $MapIconStyle . "void.png";
        $SymFlugplatz =  $AdminMapIcons . "034-airport@1x-251,216,005.png";
        $SymDrohneRKRK =  $AdminMapIcons . "005-star@1x-255,171,171.png";
        $SymDrohneRKRS =  $AdminMapIcons . "005-star@1x-106,153,85.png";
        $SymDrohneRKRP =  $AdminMapIcons . "005-star@1x-248,203,173.png";
        $SymDrohneRKRN =  $AdminMapIcons . "005-star@1x-255,230,153.png";
        $SymDrohneRKRX =  $AdminMapIcons . "005-star@1x-191,191,191.png";
        $SymDrohneZug =  $AdminMapIcons . "005-star@1x-175,206,235.png";
		$SymSuchfeld =  $AdminMapIcons . "002-circle@1x-175,206,235.png";
        $SymStartplatz =  $AdminMapIcons . "001-marker@1x-245,177,131.png";
        $SymParkplatz =  $AdminMapIcons . "078-parking@1x-051,090,159.png";
		$SymJGR = $AdminMapIcons . "034-airport@1x-175,206,235.png";  // Jäger
		$SymJGR_dunkel = $AdminMapIcons . "034-airport@1x-106,153,85.png";  // Jäger
*/
}

		$output .=  Chr(10) . Chr(10);
		$output .=  Chr(10) . Chr(10);

// style settings
//------------------

//===========================
// Flugfelder, Flugwege
//===========================

	if (!$SingleColor and !$Overview and !$Flugplan) {
		If ($Public) {
			$StyleTypeList["fieldarea_free"] 		= kml_flugfeld_style("fieldarea_free", $LGreenColor, $AreaWidth, $GreenColor, 1, $Target,  true);
			//$StyleTypeList["fieldarea_night"] 	= kml_flugfeld_style("fieldarea_night", $NGreenColor, $AreaWidth, $MGreenColor, 1, $Target,  true);
			$StyleTypeList["fieldarea_free_night"] 	= kml_flugfeld_style("fieldarea_free_night", $NGreenColor, $AreaWidth, $MGreenColor, 1, $Target,  true);
			$StyleTypeList["fieldarea_restrict"] 	= kml_flugfeld_style("fieldarea_restrict", $LRedColor, $AreaWidth, $DRedColor, 1, $Target,  true);
			$StyleTypeList["fieldarea_tocheck"] 	= kml_flugfeld_style("fieldarea_tocheck", $LViolettColor, $AreaWidth, $ViolettColor, 1, $Target,  true);
			$StyleTypeList["fieldarea_testflight"] 	=  kml_flugfeld_style("fieldarea_testflight", $LOrangeColor, $AreaWidth, $OrangeColor, 1, $Target,  true);
			$StyleTypeList["fieldarea_testflight_night"] = kml_flugfeld_style("fieldarea_testflight_night", $NOrangeColor, $AreaWidth, $MOrangeColor, 1, $Target,  true);
			$StyleTypeList["fieldarea_retire"] 		= kml_flugfeld_style("fieldarea_retire", $LGreyColor, $AreaWidth, $GreyColor, 1, $Target,  true);
			$StyleTypeList["fieldarea_object"] 		= kml_flugfeld_style("fieldarea_object", $LBlueColor, $AreaWidth, $BlueColor, 1, $Target,  true);
			$StyleTypeList["fieldarea_object_night"] = kml_flugfeld_style("fieldarea_object_night", $NBlueColor, $AreaWidth, $MBlueColor, 1, $Target,  true);
			$StyleTypeList["fieldarea_new"] 		= kml_flugfeld_style("fieldarea_new", $YellowColor, $AreaWidth, $LYellowColor, 1, $Target,  true);
			$StyleTypeList["fieldarea_150zone"] 	= kml_flugfeld_style("fieldarea_150zone", $DOrangeColor, $AreaWidth, $DOrangeColor, 1, $Target,  true);
		} else {
			$StyleTypeList["fieldarea_free"] 		= kml_flugfeld_style("fieldarea_free", $GreenColor, $AreaWidth, $GreenColor, 1, $Target,  true);
			//$StyleTypeList["fieldarea_night"] 	= kml_flugfeld_style("fieldarea_night", $MGreenColor, $AreaWidth, $MGreenColor, 1, $Target,  true);
			$StyleTypeList["fieldarea_free_night"] 	= kml_flugfeld_style("fieldarea_free_night", $MGreenColor, $AreaWidth, $MGreenColor, 1, $Target,  true);
			$StyleTypeList["fieldarea_restrict"] 	= kml_flugfeld_style("fieldarea_restrict", $DRedColor, $AreaWidth, $DRedColor, 1, $Target,  true);
			$StyleTypeList["fieldarea_tocheck"] 	= kml_flugfeld_style("fieldarea_tocheck", $ViolettColor, $AreaWidth, $ViolettColor, 1, $Target,  true);
			$StyleTypeList["fieldarea_testflight"] 	= kml_flugfeld_style("fieldarea_testflight", $OrangeColor, $AreaWidth, $OrangeColor, 1, $Target,  true);
			$StyleTypeList["fieldarea_testflight_night"] = kml_flugfeld_style("fieldarea_testflight_night", $MOrangeColor, $AreaWidth, $MOrangeColor, 1, $Target,  true);
			$StyleTypeList["fieldarea_retire"] 		= kml_flugfeld_style("fieldarea_retire", $GreyColor, $AreaWidth, $GreyColor, 1, $Target,  true);
			$StyleTypeList["fieldarea_object"] 		= kml_flugfeld_style("fieldarea_object", $BlueColor, $AreaWidth, $BlueColor, 1, $Target,  true);
			$StyleTypeList["fieldarea_object_night"] = kml_flugfeld_style("fieldarea_object_night", $MBlueColor, $AreaWidth, $MBlueColor, 1, $Target,  true);
			$StyleTypeList["fieldarea_new"] 		= kml_flugfeld_style("fieldarea_new", $LYellowColor, $AreaWidth, $LYellowColor, 1, $Target,  true);
			$StyleTypeList["fieldarea_150zone"] 	= kml_flugfeld_style("fieldarea_150zone", $DOrangeColor, $AreaWidth, $DOrangeColor, 1, $Target,  true);
		}
		$StyleTypeList["flightpath_free"] 			= kml_flugpfad_style("flightpath_free", $LGreenColor, $LGreenColor, $LineWidth, $Target, true); 
		$StyleTypeList["flightpath_free_night"] 	= kml_flugpfad_style("flightpath_free_night", $NGreenColor, $NGreenColor, $LineWidth, $Target, true); 
		$StyleTypeList["flightpath_restrict"] 		= kml_flugpfad_style("flightpath_restrict", $LRedColor, $LRedColor, $LineWidth, $Target, true); 
		$StyleTypeList["flightpath_tocheck"] 		= kml_flugpfad_style("flightpath_tocheck", $LViolettColor, $LViolettColor, $LineWidth, $Target, true); 
		$StyleTypeList["flightpath_testflight"] 	= kml_flugpfad_style("flightpath_testflight", $LOrangeColor, $LOrangeColor, $LineWidth, $Target, true); 
		$StyleTypeList["flightpath_testflight_night"] = kml_flugpfad_style("flightpath_testflight_night", $NOrangeColor, $NOrangeColor, $LineWidth, $Target, true); 
		$StyleTypeList["flightpath_retire"] 		= kml_flugpfad_style("flightpath_retire", $LGreyColor, $LGreyColor, $LineWidth, $Target, true); 
		$StyleTypeList["flightpath_object"] 		= kml_flugpfad_style("flightpath_object", $LBlueColor, $LBlueColor, $LineWidth, $Target, true); 
		$StyleTypeList["flightpath_object_night"] 	= kml_flugpfad_style("flightpath_object_night", $NBlueColor, $NBlueColor, $LineWidth, $Target, true); 
		$StyleTypeList["flightpath_new"] 			= kml_flugpfad_style("flightpath_new", $YellowColor, $YellowColor, $LineWidth, $Target, true); 
		$StyleTypeList["flightpath_150zone"] 		= kml_flugpfad_style("flightpath_150zone", $LDOrangeColor, $LDOrangeColor, $LineWidth, $Target, true); 
	} elseif (($Overview) or ($SingleColor)) {
		//overview setting für Suchplanbild
		$StyleTypeList["fieldarea_free"] 		= kml_flugfeld_style("fieldarea_free", $MainSingleColor, $AreaWidth, $MainSingleColor, 0, $Target,  true);
		//$StyleTypeList["fieldarea_night"] 	= kml_flugfeld_style("fieldarea_night", $MainSingleColor, $AreaWidth, $MainSingleColor, 0, $Target,  true);
		$StyleTypeList["fieldarea_free_night"] 	= kml_flugfeld_style("fieldarea_free_night", $MainSingleColor, $AreaWidth, $MainSingleColor, 0, $Target,  true);
		$StyleTypeList["fieldarea_restrict"] 	= kml_flugfeld_style("fieldarea_restrict", $MainSingleColor, $AreaWidth, $MainSingleColor, 0, $Target,  true);
		$StyleTypeList["fieldarea_tocheck"] 	= kml_flugfeld_style("fieldarea_tocheck", $MainSingleColor, $AreaWidth, $MainSingleColor, 0, $Target,  true);
		$StyleTypeList["fieldarea_testflight"] 	= kml_flugfeld_style("fieldarea_testflight", $MainSingleColor, $AreaWidth, $MainSingleColor, 0, $Target,  true);
		$StyleTypeList["fieldarea_testflight_night"] 	= kml_flugfeld_style("fieldarea_testflight_night", $MainSingleColor, $AreaWidth, $MainSingleColor, 0, $Target,  true);
		$StyleTypeList["fieldarea_retire"] 		= kml_flugfeld_style("fieldarea_retire", $MainSingleColor, $AreaWidth, $MainSingleColor, 0, $Target,  true);
		$StyleTypeList["fieldarea_object"] 		= kml_flugfeld_style("fieldarea_object", $MainSingleColor, $AreaWidth, $MainSingleColor, 0, $Target,  true);
		$StyleTypeList["fieldarea_object_night"] 	= kml_flugfeld_style("fieldarea_object_night", $MainSingleColor, $AreaWidth, $MainSingleColor, 0, $Target,  true);
		$StyleTypeList["fieldarea_new"] 		= kml_flugfeld_style("fieldarea_new", $MainSingleColor, $AreaWidth, $MainSingleColor, 0, $Target,  true);
		$StyleTypeList["fieldarea_150zone"] 	= kml_flugfeld_style("fieldarea_150zone", $MainSingleColor, $AreaWidth, $MainSingleColor, 0, $Target,  true);
		
		$LineWidth = "1";
		$StyleTypeList["flightpath_free"] 		= kml_flugpfad_style("flightpath_free", $MainBorderColor, $MainSingleColorLight, $LineWidth, $Target, true); 
		$StyleTypeList["flightpath_free_night"]	= kml_flugpfad_style("flightpath_free_night", $MainBorderColor, $MainSingleColorLight, $LineWidth, $Target, true); 
		$StyleTypeList["flightpath_restrict"] 	= kml_flugpfad_style("flightpath_restrict", $MainBorderColor, $MainSingleColorLight, $LineWidth, $Target, true); 
		$StyleTypeList["flightpath_tocheck"] 	= kml_flugpfad_style("flightpath_tocheck", $MainBorderColor, $MainSingleColorLight, $LineWidth, $Target, true); 
		$StyleTypeList["flightpath_testflight"]	= kml_flugpfad_style("flightpath_testflight", $MainBorderColor, $MainSingleColorLight, $LineWidth, $Target, true); 
		$StyleTypeList["flightpath_testflight_night"] 	= kml_flugpfad_style("flightpath_testflight_night", $MainBorderColor, $MainSingleColorLight, $LineWidth, $Target, true); 
		$StyleTypeList["flightpath_retire"] 	= kml_flugpfad_style("flightpath_retire", $MainBorderColor, $MainSingleColorLight, $LineWidth, $Target, true); 
		$StyleTypeList["flightpath_object"] 	= kml_flugpfad_style("flightpath_object", $MainBorderColor, $MainSingleColorLight, $LineWidth, $Target, true); 
		$StyleTypeList["flightpath_object_night"] 	= kml_flugpfad_style("flightpath_object_night", $MainBorderColor, $MainSingleColorLight, $LineWidth, $Target, true); 
		$StyleTypeList["flightpath_new"] 		= kml_flugpfad_style("flightpath_new", $MainBorderColor, $MainSingleColorLight, $LineWidth, $Target, true); 
		$StyleTypeList["flightpath_150zone"] 	= kml_flugpfad_style("flightpath_150zone", $MainBorderColor, $MainSingleColorLight, $LineWidth, $Target, true); 
	} else {
		//Flugplan setting für Flugplanbild
		$StyleTypeList["fieldarea_free"] 		= kml_flugfeld_style("fieldarea_free", $MainBorderColor, $AreaWidth, $MainSingleColorLight, 0, $Target,  true);
		//$StyleTypeList["fieldarea_night"] 	= kml_flugfeld_style("fieldarea_night", $MainBorderColor, $AreaWidth, $MainSingleColorLight, 0, $Target,  true);
		$StyleTypeList["fieldarea_free_night"] 	= kml_flugfeld_style("fieldarea_free_night", $MainBorderColor, $AreaWidth, $MainSingleColorLight, 0, $Target,  true);
		$StyleTypeList["fieldarea_restrict"] 	= kml_flugfeld_style("fieldarea_restrict", $MainBorderColor, $AreaWidth, $MainSingleColorLight, 0, $Target,  true);
		$StyleTypeList["fieldarea_tocheck"] 	= kml_flugfeld_style("fieldarea_tocheck", $MainBorderColor, $AreaWidth, $MainSingleColorLight, 0, $Target,  true);
		$StyleTypeList["fieldarea_testflight"] 	= kml_flugfeld_style("fieldarea_testflight", $MainBorderColor, $AreaWidth, $MainSingleColorLight, 0, $Target,  true);
		$StyleTypeList["fieldarea_testflight_night"] 	= kml_flugfeld_style("fieldarea_testflight_night", $MainBorderColor, $AreaWidth, $MainSingleColorLight, 0, $Target,  true);
		$StyleTypeList["fieldarea_retire"] 		= kml_flugfeld_style("fieldarea_retire", $MainBorderColor, $AreaWidth, $MainSingleColorLight, 0, $Target,  true);
		$StyleTypeList["fieldarea_object"] 		= kml_flugfeld_style("fieldarea_object", $MainBorderColor, $AreaWidth, $MainSingleColorLight, 0, $Target,  true);
		$StyleTypeList["fieldarea_object_night"]	= kml_flugfeld_style("fieldarea_object_night", $MainBorderColor, $AreaWidth, $MainSingleColorLight, 0, $Target,  true);
		$StyleTypeList["fieldarea_new"] 		= kml_flugfeld_style("fieldarea_new", $MainBorderColor, $AreaWidth, $MainSingleColorLight, 0, $Target,  true);
		$StyleTypeList["fieldarea_150zone"] 	= kml_flugfeld_style("fieldarea_150zone", $MainBorderColor, $AreaWidth, $MainSingleColorLight, 0, $Target,  true);
		
		$LineWidth = "3";
		$StyleTypeList["flightpath_free"] 		= kml_flugpfad_style("flightpath_free", $MainBorderColor, $MainSingleColorLight, $LineWidth, $Target, true); 
		$StyleTypeList["flightpath_free_night"] = kml_flugpfad_style("flightpath_free_night", $MainBorderColor, $MainSingleColorLight, $LineWidth, $Target, true); 
		$StyleTypeList["flightpath_restrict"] 	= kml_flugpfad_style("flightpath_restrict", $MainBorderColor, $MainSingleColorLight, $LineWidth, $Target, true); 
		$StyleTypeList["flightpath_tocheck"] 	= kml_flugpfad_style("flightpath_tocheck", $MainBorderColor, $MainSingleColorLight, $LineWidth, $Target, true); 
		$StyleTypeList["flightpath_testflight"] = kml_flugpfad_style("flightpath_testflight", $MainBorderColor, $MainSingleColorLight, $LineWidth, $Target, true); 
		$StyleTypeList["flightpath_testflight_night"] 	= kml_flugpfad_style("flightpath_testflight_night", $MainBorderColor, $MainSingleColorLight, $LineWidth, $Target, true); 
		$StyleTypeList["flightpath_retire"] 	= kml_flugpfad_style("flightpath_retire", $MainBorderColor, $MainSingleColorLight, $LineWidth, $Target, true); 
		$StyleTypeList["flightpath_object"] 	= kml_flugpfad_style("flightpath_object", $MainBorderColor, $MainSingleColorLight, $LineWidth, $Target, true); 
		$StyleTypeList["flightpath_object_night"] 	= kml_flugpfad_style("flightpath_object_night", $MainBorderColor, $MainSingleColorLight, $LineWidth, $Target, true); 
		$StyleTypeList["flightpath_new"]	 		= kml_flugpfad_style("flightpath_new", $MainBorderColor, $MainSingleColorLight, $LineWidth, $Target, true); 
		$StyleTypeList["flightpath_150zone"] 	= kml_flugpfad_style("flightpath_150zone", $MainBorderColor, $MainSingleColorLight, $LineWidth, $Target, true); 
	}

	$StyleTypeList["Volte"] = kml_flugpfad_style("Volte", $BlackColor, $BlackColor, $LineWidth, $Target, true); 
	$StyleTypeList["Travel"] = kml_flugpfad_style("Travel", $LOrangeColor, $LOrangeColor, 6, $Target, true); 


//===========================
// Start, Zentrumspunkt
//===========================
	$POIRandColor="ff0051e6";
	$POIAreaColor="ffffffff";
	$LabeleOffRandColor="ffbbbbbb";

	switch ($MapTyp) {
		case "M":
			$LabelRandColor="00ffffff";
			$LabelAreaColor="ffdddddd";
			$FplfRandColor="00ffffff";
			$FplfAreaColor="ffdddddd";
			break;
		case "F":
			$LabelRandColor="000080dd";
			$LabelAreaColor="ff0080dd";
			$FplfRandColor="000080dd";
			$FplfAreaColor="ff0080dd";
			break;
		default:
			$LabelRandColor="ffffffff";
			$LabelAreaColor="ffffffff";
			$FplfRandColor="ffffffff";
			$FplfAreaColor="ffffffff";
	}	

	$midLabelScale = ((float)$Zoom > 0) ? $Zoom : 1;
	$output .=  kml_POI_style("label", $SymLabel, "ff0051e6", 0 , $LabelAreaColor, $midLabelScale, $Target, isset($ListOfStylesUsed["label"]));
	//if (!$Flugplan) {
	if (($Overview) or ($Flugplan)) {
		// label Ausschalten
		$StyleTypeList["startpoint"] 	= kml_POI_style("startpoint", $SymStartplatz, $POIRandColor, $Zoom , $LabelAreaColor, .1, $Target ,true);
		$StyleTypeList["centerpoint"] 	= kml_POI_style("centerpoint", $SymSuchfeld, $POIRandColor, $Zoom , $LabelAreaColor, .1, $Target ,true);
		$StyleTypeList["parking"] 		= kml_POI_style("parking", $SymParkplatz, $POIRandColor, $Zoom , $LabelAreaColor, .1, $Target ,true);
	} else {
		$StyleTypeList["startpoint"] 	= kml_POI_style("startpoint", $SymStartplatz, $POIRandColor, $Zoom , $LabelAreaColor, $Zoom, $Target ,true);
		$StyleTypeList["centerpoint"] 	= kml_POI_style("centerpoint", $SymSuchfeld, $POIRandColor, $Zoom , $LabelAreaColor, $Zoom, $Target ,true);
		$StyleTypeList["parking"] 		= kml_POI_style("parking", $SymParkplatz, $POIRandColor, $Zoom , $LabelAreaColor, $Zoom, $Target ,true);
	}

// Flugplattform RKRK
	$StyleTypeList["FPLF"] 		= kml_POI_style("FPLF", $SymDrohneRKRK, $FplfRandColor, $Zoom , $FplfAreaColor, $Zoom, $Target ,true);
// Flugplattform RKRS
	$StyleTypeList["FPLS"]	 	= kml_POI_style("FPLS", $SymDrohneRKRS, $FplfRandColor, $Zoom , $FplfAreaColor, $Zoom, $Target ,true);
// Flugplattform RKRP
	$StyleTypeList["FPLP"]	 	= kml_POI_style("FPLP", $SymDrohneRKRP, $FplfRandColor, $Zoom , $FplfAreaColor, $Zoom, $Target ,true);
// Flugplattform RKRN
	$StyleTypeList["FPLN"]	 	= kml_POI_style("FPLN", $SymDrohneRKRN, $FplfRandColor, $Zoom , $FplfAreaColor, $Zoom, $Target ,true);
// Flugplattform RKRX
	$StyleTypeList["FPLX"]	 	= kml_POI_style("FPLX", $SymDrohneRKRX, $FplfRandColor, $Zoom , $FplfAreaColor, $Zoom, $Target ,true);
// Flugplattform Zug
	$StyleTypeList["FPLZ"]	 	= kml_POI_style("FPLZ", $SymDrohneZug, $FplfRandColor, $Zoom , $FplfAreaColor, $Zoom, $Target ,true);
// Jäger
	if (($MapTyp=="F") or ($MapTyp=="M")) {
		$StyleTypeList["JGR"] 		=  kml_POI_style("JGR", $SymJGR_dunkel, $FplfRandColor, $Zoom ,$LabelAreaColor, $Zoom, $Target ,true);
	} else {
		$StyleTypeList["JGR"] 		=  kml_POI_style("JGR", $SymJGR, $FplfRandColor, $Zoom , 	$LabelAreaColor, $Zoom, $Target ,true);
	}
	
// Flugplatz
	$StyleTypeList["FLPL"]	 	= kml_POI_style("FLPL", $SymFlugplatz, "ff00d6ff", $Zoom , $FplfAreaColor, $Zoom, $Target ,true);

// Kanton_Name
	$StyleTypeList["KT_LABEL"] 		=  kml_POI_style("KT_LABEL", $SymKtLabel, $LabelRandColor, 0.8 * $Zoom , $LabelAreaColor, 1.2 * $Zoom, $Target ,true);
	$StyleTypeList["KToff_LABEL"] 	=  kml_POI_style("KToff_LABEL", $SymKtLabel, $LabeleOffRandColor, 0.8 * $Zoom , $LabelAreaColor, 1.2 * $Zoom, $Target ,true);
	$StyleTypeList["GDE_LABEL"] 	=  kml_POI_style("GDE_LABEL", $SymGdeLabel, $LabelRandColor, 0.5 * $Zoom , $LabelAreaColor, 0.8 * $Zoom, $Target ,true);
	$StyleTypeList["GDEoff_LABEL"] 	=  kml_POI_style("GDEoff_LABEL", $SymGdeLabel, $LabeleOffRandColor, 0.5 * $Zoom , $LabelAreaColor, 0.8 * $Zoom, $Target ,true);
	$StyleTypeList["JG_LABEL"] 		=  kml_POI_style("JG_LABEL", $SymJGLabel, $LabelRandColor, 0.5 * $Zoom , $LabelAreaColor, 0.8 * $Zoom, $Target ,true);
	$StyleTypeList["JGoff_LABEL"] 	=  kml_POI_style("JGoff_LABEL", $SymGdeLabel, $LabeleOffRandColor, 0.5 * $Zoom , $LabelAreaColor, 0.8 * $Zoom, $Target ,true);
	$StyleTypeList["ORT"] 			=  kml_POI_style("ORT", $SymOrtLabel, $LabelRandColor, 0.5 * $Zoom , $LabelAreaColor, 0.8 * $Zoom, $Target ,true);
	

//===========================
// Flugzonen
//===========================
	$FlugverbotRandColor="ff0000ff";
	$FlugverbotAreaColor="330000ff";
	$KtRestrictionRandColor="FF00BFFF";
	$KtRestrictionAreaColor="1100BFFF";
	$Fz70mRandColor="ff8deeff";
	$Fz70mAreaColor="668deeff";
	$Fz120mRandColor="ff8dee8d";
	$Fz120mAreaColor="668dee8d";
	$Fz150mRandColor="33fe9b59";
	$Fz150mAreaColor="33fe9b59";
	$NachtFlugRandColor="ffff7d32";
	$NachtFlugAreaColor="66ff7d32";
	$NachtFlugRandColor1="ffff7d32";
	$NachtFlugAreaColor1="66ff7d32";
	$NachtFlugRandColor2="ffffb88f";
	$NachtFlugAreaColor2="66ffb88f";
	$NaturschutzRandColor="ff66ccff";
	$NaturschutzAreaColor="6666ccff";
	
	$StyleTypeList["Flugverbotszone"] 	=  kml_Area_style("Flugverbotszone",$FlugverbotRandColor, 1, $FlugverbotAreaColor, 1, 1, $Target ,true);
	$StyleTypeList["70mFlugzone"]	 	=  kml_Area_style("70mFlugzone",$Fz70mRandColor, 1, $Fz70mAreaColor, 1, 1, $Target ,true);
	$StyleTypeList["120mFlugzone"]	 	=  kml_Area_style("120mFlugzone",$Fz120mRandColor, 1, $Fz120mAreaColor, 1, 1, $Target ,true);
	$StyleTypeList["150mFlugzone"]	 	=  kml_Area_style("150mFlugzone",$Fz150mRandColor, 1, $Fz150mAreaColor, 1, 1, $Target ,true);
	$StyleTypeList["Nachtflugzone"]	 	=  kml_Area_style("Nachtflugzone",$NachtFlugRandColor, 1, $NachtFlugAreaColor, 1, 1, $Target ,true);

//	FZO: Flight Zone Options
//		FHGO-NF1: FHGO nur eine Nachflugzone
//		FHGO-NF2: FHGO nur zwei Nachflugzonen
//		FHGO-STP: FHGO gesperrt	switch FZO {
switch ($FlightZoneOptions) {
		case "FHGO-NF1":
			$StyleTypeList["Nachtflugzone1"] 	=  kml_Area_style("Nachtflugzone1",$NachtFlugRandColor1, 1, $NachtFlugAreaColor1, 1, 1, $Target ,true);
			$StyleTypeList["Nachtflugzone2"] 	=  kml_Area_style("Nachtflugzone2",$NachtFlugRandColor1, 1, $NachtFlugAreaColor1, 1, 1, $Target ,true);
			$StyleTypeList["Nachtflugzone3"] 	=  kml_Area_style("Nachtflugzone3",$NachtFlugRandColor, 1, $NachtFlugAreaColor, 1, 1, $Target ,true);
			break;
		case "FHGO-NF2":
			$StyleTypeList["Nachtflugzone1"] 	=  kml_Area_style("Nachtflugzone1",$NachtFlugRandColor1, 1, $NachtFlugAreaColor1, 1, 1, $Target ,true);
			$StyleTypeList["Nachtflugzone2"] 	=  kml_Area_style("Nachtflugzone2",$NachtFlugRandColor2, 1, $NachtFlugAreaColor2, 1, 1, $Target ,true);
			$StyleTypeList["Nachtflugzone3"] 	=  kml_Area_style("Nachtflugzone3",$NachtFlugRandColor, 1, $NachtFlugAreaColor, 1, 1, $Target ,true);
			break;
		case "FHGO-STP":
			$StyleTypeList["Nachtflugzone1"] 	=  kml_Area_style("Nachtflugzone1",$FlugverbotRandColor, 1, $FlugverbotAreaColor, 1, 1, $Target ,true);
			$StyleTypeList["Nachtflugzone2"] 	=  kml_Area_style("Nachtflugzone2",$FlugverbotRandColor, 1, $FlugverbotAreaColor, 1, 1, $Target ,true);
			$StyleTypeList["Nachtflugzone3"] 	=  kml_Area_style("Nachtflugzone3",$FlugverbotRandColor, 1, $FlugverbotAreaColor, 1, 1, $Target ,true);
			break;
		default:
			$StyleTypeList["Nachtflugzone1"] 	=  kml_Area_style("Nachtflugzone1",$NachtFlugRandColor1, 1, $NachtFlugAreaColor1, 1, 1, $Target ,true);
			$StyleTypeList["Nachtflugzone2"] 	=  kml_Area_style("Nachtflugzone2",$NachtFlugRandColor2, 1, $NachtFlugAreaColor2, 1, 1, $Target ,true);
			$StyleTypeList["Nachtflugzone3"] 	=  kml_Area_style("Nachtflugzone3",$NachtFlugRandColor, 1, $NachtFlugAreaColor, 1, 1, $Target ,true);
	}

	$StyleTypeList["NaturschutzFlugverbotszone"]	=  kml_Area_style("NaturschutzFlugverbotszone", $NaturschutzRandColor, 1, $NaturschutzAreaColor, 1, 1, $Target ,true);
	//Kantonale Restriktionen
	$StyleTypeList["KtFlugVerbot"]	 	=  kml_Area_style("KtFlugVerbot",$FlugverbotRandColor, 1, $FlugverbotAreaColor, 1, 1, $Target ,true);
	$StyleTypeList["KtFlugRestriction"]	=  kml_Area_style("KtFlugRestriction",$KtRestrictionRandColor, 1, $KtRestrictionAreaColor, 1, 1, $Target ,true);
	//Spezialzonen
	$SoraRandColor="FF0088FF";
	$SoraAreaColor="330088FF";
	$SunRandColor="ff8deeff";
	$SunAreaColor="668deeff";
	$WasserRandColor="fff5a542";
	$WasserAreaColor="66f5a542";
	$HSPLRandColor="ff0000ff";
	$HSPLAreaColor="000000ff";
	$AlertRandColor="ff00a5ff";
	$AlertAreaColor="6600a5ff";
	$StyleTypeList["Sora"]	 	=  kml_Area_style("Sora",$SoraRandColor, 1, $SoraAreaColor, 1, 1, $Target ,true);
	$StyleTypeList["Sun"] 		=  kml_Area_style("Sun",$SunRandColor, 1, $SunAreaColor, 1, 1, $Target ,true);
	$StyleTypeList["Wasser"] 	=  kml_Area_style("Wasser",$WasserRandColor, 1, $WasserAreaColor, 1, 1, $Target ,true);
	$StyleTypeList["HSPL"] 		=  kml_Area_style("HSPL",$HSPLRandColor, 4, $HSPLAreaColor, 1, 1, $Target ,true);
	$StyleTypeList["Alert"] 	=  kml_Area_style("Alert",$AlertRandColor, 3, $AlertAreaColor, 1, 1, $Target ,true);

//===========================
// Gmeindegrenzen
//===========================

/*
	$output .=  kml_Area_style("GDE_KNAMT","ff66ccff", 3, "00d18802", 1, 1, $Target ,true);
	$output .=  kml_Area_style("GDE_ZG","ff9b5701", 3, "009b5701", 1, 1, $Target ,true);
	$output .=  kml_Area_style("GDE_OberesFreiamtAG","ffffcc99", 3, "00ffcc99", 1, 1, $Target ,true);
	$output .=  kml_Area_style("GDE_AG","ffA0A0A0", 3, "00A0A0A0", 1, 1, $Target ,true);
	$output .=  kml_Area_style("Kantone","ff80dafa", 5, "0080dafa", 1, 1, $Target ,true);
*/
	$GDERandColor="ff66ccff";
	$GDEAreaColor="00d18802";
	$KtRandColor="ff80dafa";
	$KtAreaColor="0080f1fa";
	$StyleTypeList["GDE_KNAMT"] = kml_Area_style("GDE_KNAMT",$GDERandColor, 3, $GDEAreaColor, 1, 1, $Target ,true);
	$StyleTypeList["GDE_AMT"] 	= kml_Area_style("GDE_AMT",$GDERandColor, 3, $GDEAreaColor, 1, 1, $Target ,true);
	$StyleTypeList["GDE_ZG"] 	= kml_Area_style("GDE_ZG",$GDERandColor, 3, $GDEAreaColor, 1, 1, $Target ,true);
	$StyleTypeList["GDE_OberesFreiamtAG"] 	= kml_Area_style("GDE_OberesFreiamtAG",$GDERandColor, 3, $GDEAreaColor, 1, 1, $Target ,true);
	$StyleTypeList["GDE_AG"] 	= kml_Area_style("GDE_AG",$GDERandColor, 3, $GDEAreaColor, 1, 1, $Target ,true);
	$StyleTypeList["GDE_LU"] 	= kml_Area_style("GDE_LU",$GDERandColor, 3, $GDEAreaColor, 1, 1, $Target ,true);
	$StyleTypeList["GDE_SZ"] 	= kml_Area_style("GDE_SZ",$GDERandColor, 3, $GDEAreaColor, 1, 1, $Target ,true);
	$StyleTypeList["KANTONE"] 	= kml_Area_style("KANTONE",$KtRandColor, 5, $KtAreaColor, 1, 1, $Target ,true);
	//Hochspannungsleitungen
	
//===========================
// Jagdgebiete
//===========================

/*
	$output .=  kml_Area_style("JGB_KNAMT","ff8bbd7a", 3, "008bbd7a", 1, 1, $Target ,true);
	$output .=  kml_Area_style("JGB_ZG","ff2f8b55", 3, "002f8b55", 1, 1, $Target ,true);
	$output .=  kml_Area_style("JGB_OberesFreiamtAG","ff9ed17a", 3, "009ed17a", 1, 1, $Target ,true);
	$output .=  kml_Area_style("JGB_AG","ff808080", 3, "00808080", 1, 1, $Target ,true);
*/
	if (!$SingleColor) {
		// grün
		//$output .=  kml_Area_style("JGB_KNAMT","ff8bbd7a", 3, "008bbd7a", 1, 1, $Target ,true);
		//$output .=  kml_Area_style("JGB_ZG","ff8bbd7a", 3, "008bbd7a", 1, 1, $Target ,true);
		//$output .=  kml_Area_style("JGB_OberesFreiamtAG","ff8bbd7a", 3, "008bbd7a", 1, 1, $Target ,true);
		//$output .=  kml_Area_style("JGB_AG","ff8bbd7a", 3, "008bbd7a", 1, 1, $Target ,true);
		$JGBRandColor="ff3390FF";
		$JGBAreaColor="008bbd7a";
		$StyleTypeList["JGB_KNAMT"] 	= kml_Area_style("JGB_KNAMT",$JGBRandColor, 3, $JGBAreaColor, 1, 1, $Target ,true);
		$StyleTypeList["JGB_ZG"] 		= kml_Area_style("JGB_ZG",$JGBRandColor, 3, $JGBAreaColor, 1, 1, $Target ,true);
		$StyleTypeList["JGB_OberesFreiamtAG"] 	= kml_Area_style("JGB_OberesFreiamtAG",$JGBRandColor, 3, $JGBAreaColor, 1, 1, $Target ,true);
		$StyleTypeList["JGB_AG"] 		= kml_Area_style("JGB_AG",$JGBRandColor, 3, $JGBAreaColor, 1, 1, $Target ,true);
		$StyleTypeList["JGB_LU"] 		= kml_Area_style("JGB_LU",$JGBRandColor, 3, $JGBAreaColor, 1, 1, $Target ,true);
		$StyleTypeList["JGB_SZ"] 		= kml_Area_style("JGB_SZ",$JGBRandColor, 3, $JGBAreaColor, 1, 1, $Target ,true);
	} else {
		$JGBRandColor="ff3390FF";
		$JGBAreaColor="008bbd7a";
		$StyleTypeList["JGB_KNAMT"] 	= kml_Area_style("JGB_KNAMT",$JGBRandColor, 3, $JGBAreaColor, 1, 1, $Target ,true);
		$StyleTypeList["JGB_ZG"] 		= kml_Area_style("JGB_ZG",$JGBRandColor, 3, $JGBAreaColor, 1, 1, $Target ,true);
		$StyleTypeList["JGB_OberesFreiamtAG"] 	= kml_Area_style("JGB_OberesFreiamtAG",$JGBRandColor, 3, $JGBAreaColor, 1, 1, $Target ,true);
		$StyleTypeList["JGB_AG"] 		= kml_Area_style("JGB_AG",$JGBRandColor, 3, $JGBAreaColor, 1, 1, $Target ,true);
		$StyleTypeList["JGB_LU"] 		= kml_Area_style("JGB_LU",$JGBRandColor, 3, $JGBAreaColor, 1, 1, $Target ,true);
		$StyleTypeList["JGB_SZ"] 		= kml_Area_style("JGB_SZ",$JGBRandColor, 3, $JGBAreaColor, 1, 1, $Target ,true);
	}
		//$KernRandColor="ff8dee8d";
		$KernRandColor="ff50ED50";
		$KernAreaColor="998dee8d";
		//$SupportRandColor="ff8deeff";
		$SupportRandColor="ff59E6FF";
		$SupportAreaColor="998deeff";
	$StyleTypeList["JGB_KNAMT_Kern"] 			= kml_Area_style("JGB_KNAMT_Kern",$KernRandColor, 3, $KernAreaColor, 1, 1, $Target ,true);
	$StyleTypeList["JGB_KNAMT_Support"] 		= kml_Area_style("JGB_KNAMT_Support",$SupportRandColor, 3, $SupportAreaColor, 1, 1, $Target ,true);
	$StyleTypeList["JGB_ZG_Kern"] 				= kml_Area_style("JGB_ZG_Kern",$KernRandColor, 3, $KernAreaColor, 1, 1, $Target ,true);
	$StyleTypeList["JGB_ZG_Support"] 			= kml_Area_style("JGB_ZG_Support",$SupportRandColor, 3, $SupportAreaColor, 1, 1, $Target ,true);
	$StyleTypeList["JGB_OberesFreiamtAG_Kern"] 	= kml_Area_style("JGB_ZG_Support",$SupportRandColor, 3, $SupportAreaColor, 1, 1, $Target ,true);
	$StyleTypeList["JGB_OberesFreiamtAG_Kern"] 	= kml_Area_style("JGB_OberesFreiamtAG_Kern",$KernRandColor, 3, $KernAreaColor, 1, 1, $Target ,true);
	$StyleTypeList["JGB_OberesFreiamtAG_Support"] 	= kml_Area_style("JGB_OberesFreiamtAG_Support",$SupportRandColor, 3, $SupportAreaColor, 1, 1, $Target ,true);
	$StyleTypeList["JGB_AG_Kern"] 				= kml_Area_style("JGB_AG_Kern",$KernRandColor, 3, $KernAreaColor, 1, 1, $Target ,true);
	$StyleTypeList["JGB_AG_Support"] 			= kml_Area_style("JGB_AG_Support",$SupportRandColor, 3, $SupportAreaColor, 1, 1, $Target ,true);
	$StyleTypeList["JGB_LU_Kern"] 				= kml_Area_style("JGB_LU_Kern",$KernRandColor, 3, $KernAreaColor, 1, 1, $Target ,true);
	$StyleTypeList["JGB_LU_Support"] 			= kml_Area_style("JGB_LU_Support",$SupportRandColor, 3, $SupportAreaColor, 1, 1, $Target ,true);
	$StyleTypeList["JGB_SZ_Kern"]				= kml_Area_style("JGB_SZ_Kern",$KernRandColor, 3, $KernAreaColor, 1, 1, $Target ,true);
	$StyleTypeList["JGB_SZ_Support"]			= kml_Area_style("JGB_SZ_Support",$SupportRandColor, 3, $SupportAreaColor, 1, 1, $Target ,true);
	
   foreach ($ListOfStylesUsed as $key => $value)	{
	 //$output .= "<!--  " . $key . " -->";
	 $output .= $StyleTypeList[$key] ?? '';	
	 //$output .= Chr(10) . Chr(10);
   }
 
 Return $output;
}


//Check if Gde in Regional GedList
function InGdeList($Feldname,$RegionSelected) {
	global $OptionGemeinde, $RegionSettings,$searchUML, $replaceUML;
	$InGde = false;
	//foreach explode(",", str_replace(" ", ",",$RegionSelected)) as $Reg {
	$delimiter=",";
	foreach (explode($delimiter,str_replace(" ", ",",$RegionSelected)) as $Region) {
		$Region = trim($Region);
		if ($Region === '' || !isset($RegionSettings[$Region]) || !is_array($RegionSettings[$Region])) {
			continue;
		}
		$GdeList = $RegionSettings[$Region]["GdeList"] ?? '';
		if ($GdeList !="") {
			foreach (explode($delimiter,str_replace(" ", ",",$GdeList)) as $Gde) {
				$Gde = trim($Gde);
				if ($Gde === '' || !isset($OptionGemeinde[$Gde]["Kml"])) {
					continue;
				}
				if ($OptionGemeinde[$Gde]["Kml"] == str_replace($searchUML, $replaceUML, $Feldname)) {
					$InGde = true;
				}
			}	
		} else {
			// Wenn leer, dann alle anzeigen
			$InGde = true;
		}
	}	
	Return $InGde;
}	

// ===========================================
// Add all POI to a specific region
// ===========================================

function rkrk_kml_region_row($name) {
	global $RegionSettings;
	$key = explode(' ', (string)$name)[0];
	if ($key === '' || !isset($RegionSettings[$key]) || !is_array($RegionSettings[$key])) {
		return array();
	}
	return $RegionSettings[$key];
}

function rkrk_kml_xml_text($text) {
	return htmlspecialchars((string)$text, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

function rkrk_kml_alert_date_explicit() {
	if (!function_exists('GetURLwDefault')) {
		return '';
	}
	foreach (array('Stichtag', 'AlertDate') as $key) {
		$raw = (string)GetURLwDefault($key, $key, '', '');
		if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
			return $raw;
		}
	}
	return '';
}

function rkrk_kml_alert_on_date() {
	$raw = rkrk_kml_alert_date_explicit();
	return $raw !== '' ? $raw : date('Y-m-d');
}

function rkrk_kml_alert_pick_names() {
	if (!function_exists('ref_layers_alert_kml_selection')) {
		include_once(__DIR__ . '/rkrk_ref_layers_lib.php');
	}
	if (!function_exists('ref_layers_alert_kml_selection') || !function_exists('GetURLwDefault')) {
		return array();
	}
	$raw = GetURLwDefault('AlertKml', 'AlertKml', '', '');
	return ref_layers_alert_kml_selection($raw);
}

function rkrk_kml_alert_pick_output($names, &$ListOfStylesUsed) {
	if (!is_array($names) || $names === array() || !function_exists('ref_layers_features_from_kml')) {
		return '';
	}
	$out = '';
	foreach ($names as $name) {
		$name = function_exists('ref_layers_alert_kml_safe_name') ? ref_layers_alert_kml_safe_name($name) : '';
		if ($name === '' || !function_exists('ref_layers_find_kml') || !function_exists('ref_layers_alert_kml_path_ok')) {
			continue;
		}
		$path = ref_layers_find_kml($name);
		if ($path === null || !ref_layers_alert_kml_path_ok($path)) {
			continue;
		}
		$features = ref_layers_features_from_kml($path, 'ALL,', 'Alert');
		if (!is_array($features)) {
			continue;
		}
		foreach ($features as $feat) {
			if (!is_array($feat)) {
				continue;
			}
			$props = is_array($feat['properties'] ?? null) ? $feat['properties'] : array();
			$geom = is_array($feat['geometry'] ?? null) ? $feat['geometry'] : array();
			$coords = ref_layers_coords_string($geom);
			if ($coords === '' || !preg_match('/^[0-9eE+.,\s-]+$/', $coords)) {
				continue;
			}
			$type = ref_layers_poi_type($geom['type'] ?? ($props['geomType'] ?? 'Point'));
			$style = trim((string)($props['styleUrl'] ?? ''));
			if (!preg_match('/^#[A-Za-z0-9_-]+$/', $style)) {
				$style = '#Alert';
			}
			$styleId = substr($style, 1);
			$ListOfStylesUsed[$styleId] = $styleId;
			$out .= "  <Placemark>" . chr(10);
			$out .= "    <name>" . rkrk_kml_xml_text($props['name'] ?? $name) . "</name>" . chr(10);
			$out .= "    <description>" . rkrk_kml_xml_text($props['description'] ?? '') . "</description>" . chr(10);
			$out .= "    <styleUrl>" . $style . "</styleUrl>" . chr(10);
			if ($type === 'Polygon') {
				$out .= "    <Polygon>" . chr(10);
				$out .= "      <tessellate>1</tessellate>" . chr(10);
				$out .= "      <altitudeMode>clampToGround</altitudeMode>" . chr(10);
				$out .= "      <outerBoundaryIs>" . chr(10);
				$out .= "        <LinearRing>" . chr(10);
				$out .= "          <coordinates>" . $coords . "</coordinates>" . chr(10);
				$out .= "        </LinearRing>" . chr(10);
				$out .= "      </outerBoundaryIs>" . chr(10);
				$out .= "    </Polygon>" . chr(10);
			} elseif ($type === 'Line') {
				$out .= "    <LineString>" . chr(10);
				$out .= "      <tessellate>1</tessellate>" . chr(10);
				$out .= "      <coordinates>" . $coords . "</coordinates>" . chr(10);
				$out .= "    </LineString>" . chr(10);
			} else {
				$out .= "    <Point>" . chr(10);
				$out .= "      <coordinates>" . $coords . "</coordinates>" . chr(10);
				$out .= "    </Point>" . chr(10);
			}
			$out .= "  </Placemark>" . chr(10) . chr(10);
		}
	}
	return $out;
}

function GetPoiKML ($RegionSelected, $SaveFile, $debugoutput, $PoiOn, $HSPOn, $WFOn, $SORAOn, $SunOn, $JggOn, $GdeOn, $KTOn, $ORTOn, $FZOn, $AlertOn, $EGOn, $LabelOn, $FileName, &$ListOfStylesUsed) {

	//used global variales
	global $searchUML, $replaceUML, $searchHTML, $replaceHTML;
	global $xmlMissionLocation, $xml_poifile, $kmlFilelocations, $RegionSettings;
	
//	$KMLFileSettings = ReadCatalogFile($xmlMissionLocation.$poi_settings_file, false);
	$POIFile = ReadCatalogFile($xmlMissionLocation.$xml_poifile, false);


	 IF ($debugoutput) {
		echo "<p><b>Wandle XML in KML output</b></p>";
		echo "Region ausgewählt: " . $RegionSelected;
	}

	//process data

	$DataOutput = "";
	$i= 0;
	$alertLiveNames = array();
	if ($AlertOn) {
		if (!function_exists('ref_layers_alert_files_for_date')) {
			include_once(__DIR__ . '/rkrk_ref_layers_lib.php');
		}
		$alertPick = rkrk_kml_alert_pick_names();
		$explicitDate = rkrk_kml_alert_date_explicit();
		if ($explicitDate !== '' && $alertPick !== array()) {
			$alertLiveNames = $alertPick;
		} elseif (function_exists('ref_layers_alert_files_for_date')) {
			$alertLiveNames = ref_layers_alert_files_for_date(rkrk_kml_alert_on_date(), $alertPick !== array() ? $alertPick : null);
		} else {
			$alertLiveNames = $alertPick;
		}
	}
	IF ($debugoutput) {
		echo "<p>POI hinzugefügt:</p>";
	}
	
	foreach ($POIFile as $feld) {
		//if (like($feld->Region, $RegionSelected)) {
		//if (strpos($RegionSelected, strval( $feld->Region))!==false) {
		//if (xlike("28" , "Amt, 21, 24, 25, 26, 27, 28, 32, AZ, AG, 148, 136, ZG, ZG1, ZG2, ZG3, ZG4, ZG5, ZG6, XX, TF, ALL, leer,", ", ")) {	
		//if (xlike($RegionSelected, $feld->Region, ",") or ($RegionSelected=="leer")) {	
		$kat = (string)$feld->Kat;
		$ignoreJgFilter = ($kat === 'FZ' || $kat === 'Kanton' || $kat === 'KtLabel'
			|| $kat === 'HSP' || $kat === 'Alert' || $kat === 'SORA' || $kat === 'SUN' || $kat === 'WF');
		if ($ignoreJgFilter || xlike($RegionSelected , $feld->Region, ",")) {	
		//if (xlike("136 148" , $feld->Region, ",")) {	
		//if (strpos("26, 28", strval($feld->Region))!==false) {
			
			$regRow = rkrk_kml_region_row($feld->name);
			$regGebiet = (string)($regRow["Gebiettyp"] ?? '');
			$regAktiv = !empty($regRow["Aktiv"]);
			if (
				($feld->Kat=="WF" and $WFOn) or
				($feld->Kat=="SORA" and $SORAOn) or
				($feld->Kat=="SUN" and $SunOn) or
				($feld->Kat=="HSP" and $HSPOn) or
				($feld->Kat=="POI" and $PoiOn) or
				($feld->Kat=="FZ" and $FZOn) or
			    ($feld->Kat=="JG" and $JggOn
					and (like(explode(" ",$feld->name)[0],$RegionSelected," ") or ($RegionSelected=="leer") or ($RegionSelected=="ALL"))
				)  or
				($feld->Kat=="JG" and $EGOn 
					and ($regGebiet!="") 
					and ($regAktiv)
					and (like(explode(" ",$feld->name)[0],$RegionSelected,",") or ($RegionSelected=="leer") or ($RegionSelected=="ALL"))
				)  or
				($feld->Kat=="Gde" and $GdeOn and InGdeList($feld->name,$RegionSelected)) or 
				($feld->Kat=="GdeLabel" and $GdeOn and $LabelOn and InGdeList($feld->name,$RegionSelected)) or 
				
				($feld->Kat=="Kanton" and $KTOn) or 
				($feld->Kat=="KtLabel" and $KTOn and $LabelOn) or 
				($feld->Kat=="Ort" and $ORTOn and $LabelOn) or 
				
				((($feld->Kat=="JGLabel" and $JggOn) or (
					($regGebiet!="") 
					and ($regAktiv)
					and $EGOn)) and $LabelOn 
					and (like(explode(" ",$feld->name)[0],$RegionSelected,",") or ($RegionSelected=="leer") or ($RegionSelected=="ALL"))
				)  or
				false) 	{
				IF ($debugoutput) {
					echo "<li>" . str_replace($searchUML, $replaceUML, $feld->name) . " - " . $feld->Type . "</li>";
				}
				if ($feld->Type == "Point") {
	/*
	<!-- ===== Flight PLACEMARK ====== -->
		<Placemark>
		  <name>Flugplattform 01</name>
		  <description>Pajasamaa Vesa, 079 335 59 59</description>		  <Point>
			<coordinates>
			  8.481731,47.198421,0
			</coordinates>
		  </Point>
		</Placemark>
	*/
					$DataOutput .= "  <Placemark>" . chr(10);
					$DataOutput .= "    <name>";
					$DataOutput .= str_replace($searchUML, $replaceUML, $feld->name);
					$DataOutput .= "</name>" . chr(10);
					$DataOutput .= "    <description>";
					//$DataOutput .= "    <description><![CDATA[";
					$DataOutput .= str_replace($searchHTML,$replaceHTML ,str_replace($searchUML, $replaceUML, $feld->description));
					//$DataOutput .= "]]></description>" . chr(10);
					$DataOutput .= "</description>" . chr(10);
					$DataOutput .= "    <styleUrl>";
					$DataOutput .= trim($feld->styleUrl);
					$ListOfStylesUsed[trim(substr($feld->styleUrl, 1))]=trim(substr($feld->styleUrl, 1));
					$DataOutput .= "</styleUrl>" . chr(10);
					$DataOutput .= "    <Point>" . chr(10);
					$DataOutput .= "      <coordinates>";
					$DataOutput .= trim($feld->coordinates);
					$DataOutput .= "</coordinates>" . chr(10);
					$DataOutput .= "    </Point>" . chr(10);
					$DataOutput .= "</Placemark>" . chr(10) . chr(10);
				}
				if ($feld->Type == "Polygon") {
	/*
	<!-- ===== Flight Flugzonen ====== -->
		<Placemark>
		  <name>CTR EMMEN (MIL) 1 (bis 150m)</name>
		  <description>	Der Betrieb von Modellluftfahrzeugen und Drohnen mit einem Gewicht zwischen 0,5 und 30 kg in Kontrollzonen (CTR) ist untersagt, sofern dabei eine Höhe von 150 m über Grund überstiegen wird. Ausnahmebewilligungen: Skyguide Special Flight Office</description>
		  <styleUrl>#150mFlugzone</styleUrl>
		  <Polygon>
			<outerBoundaryIs>
			  <LinearRing>
				<tessellate>1</tessellate>
				<coordinates>
				  8.262261,47.184018,0
				  8.177021,47.113429,0
				  8.185632,47.037933,0
				  8.237033,47.011226,0
				  8.264515,47.01373,0
				  8.404997,47.138752,0
				  8.414474,47.16206,0
				  8.358455,47.192165,0
				  8.262261,47.184018,0
				</coordinates>
			  </LinearRing>
			</outerBoundaryIs>
		  </Polygon>
		</Placemark>
	*/	

					$DataOutput .= "  <Placemark>" . chr(10);
					$DataOutput .= "    <name>";
					$DataOutput .= str_replace($searchUML, $replaceUML, $feld->name);
					$DataOutput .= "</name>" . chr(10);
					$DataOutput .= "    <description>";
					//$DataOutput .= "    <description><![CDATA[";
					//$DataOutput .= str_replace("<br>","\r" ,str_replace($searchUML, $replaceUML, $feld->description));
					$DataOutput .= str_replace($searchHTML,$replaceHTML ,str_replace($searchUML, $replaceUML, $feld->description));
					//$DataOutput .= "]]></description>" . chr(10);
					$DataOutput .= "</description>" . chr(10);
					$DataOutput .= "    <styleUrl>";
					$DataOutput .= trim($feld->styleUrl);
					if (($EGOn) and ($feld->Kat=="JG") 
						and ($regGebiet!="") 
						and ($regAktiv))
						{
						//Passe JG Style and Einsatzgebiet an
						$DataOutput .= 	"_".$regGebiet;
						$ListOfStylesUsed[trim(substr($feld->styleUrl, 1))."_".$regGebiet]=trim(substr($feld->styleUrl, 1))."_".$regGebiet;
					}	
					$ListOfStylesUsed[trim(substr($feld->styleUrl, 1))]=trim(substr($feld->styleUrl, 1));
					$DataOutput .= "</styleUrl>" . chr(10);
					$poiTessellate = trim((string)$feld->tessellate);
					if ($poiTessellate === "") {
						$poiTessellate = "1";
					}
					$DataOutput .= "    <Polygon>" . chr(10);
					$DataOutput .= "      <tessellate>" . $poiTessellate . "</tessellate>" . chr(10);
					$DataOutput .= "      <altitudeMode>clampToGround</altitudeMode>" . chr(10);
					$DataOutput .= "      <outerBoundaryIs>" . chr(10);
					$DataOutput .= "        <LinearRing>" . chr(10);
					$DataOutput .= "          <coordinates>";
					$DataOutput .= trim($feld->coordinates);
					$DataOutput .= "</coordinates>" . chr(10);
					$DataOutput .= "        </LinearRing>" . chr(10);
					$DataOutput .= "      </outerBoundaryIs>" . chr(10);
					$DataOutput .= "    </Polygon>" . chr(10);
					$DataOutput .= "  </Placemark>" . chr(10) . chr(10);
				}
				if ($feld->Type == "Line") {
	/*
	<!-- ===== Flight Flugzonen ====== -->
    <Placemark>
      <name>Hochspannungsleitung</name>
      <description><![CDATA[Hochspannungsleitung]]></description>
      <styleUrl>#HSPL</styleUrl>
      <LineString>
        <tessellate>1</tessellate>
        <coordinates>
          8.439552,47.26923,0
          8.439593,47.268515,0
        </coordinates>
      </LineString>
    </Placemark>

	*/	

					$DataOutput .= "  <Placemark>" . chr(10);
					$DataOutput .= "    <name>";
					$DataOutput .= str_replace($searchUML, $replaceUML, $feld->name);
					$DataOutput .= "</name>" . chr(10);
					$DataOutput .= "    <description>";
					//$DataOutput .= "    <description><![CDATA[";
					$DataOutput .= str_replace($searchHTML,$replaceHTML ,str_replace($searchUML, $replaceUML, $feld->description));
					//$DataOutput .= "]]></description>" . chr(10);
					$DataOutput .= "</description>" . chr(10);
					$DataOutput .= "    <styleUrl>";
					$DataOutput .= trim($feld->styleUrl);
					$ListOfStylesUsed[trim(substr($feld->styleUrl, 1))]=trim(substr($feld->styleUrl, 1));
					$DataOutput .= "</styleUrl>" . chr(10);
					$lineCoords = '';
					if (isset($feld->LineString->coordinates)) {
						$lineCoords = trim((string)$feld->LineString->coordinates);
					}
					if ($lineCoords === '') {
						$lineCoords = trim((string)$feld->coordinates);
					}
					$lineTess = '1';
					if (isset($feld->LineString->tessellate) && trim((string)$feld->LineString->tessellate) !== '') {
						$lineTess = trim((string)$feld->LineString->tessellate);
					} elseif (trim((string)$feld->tessellate) !== '') {
						$lineTess = trim((string)$feld->tessellate);
					}
					$DataOutput .= "    <LineString>" . chr(10);
					$DataOutput .= "      <tessellate>";
					$DataOutput .= $lineTess;
					$DataOutput .= "</tessellate>" . chr(10);
					$DataOutput .= "      <altitudeMode>clampToGround</altitudeMode>" . chr(10);
					$DataOutput .= "          <coordinates>";
					$DataOutput .= $lineCoords;
					$DataOutput .= "</coordinates>" . chr(10);
					$DataOutput .= "    </LineString>" . chr(10);
					$DataOutput .= "  </Placemark>" . chr(10) . chr(10);
				}
			}
		}
		$i++;
	}

	if ($AlertOn && $alertLiveNames !== array()) {
		$DataOutput .= rkrk_kml_alert_pick_output($alertLiveNames, $ListOfStylesUsed);
	}

	if ($SaveFile) {
		$filepath= $kmlFilelocations . $FileName . ".kml";
		$df = fopen($filepath, 'w');
		IF ($debugoutput) {
			echo "save file: ". $filepath;
		}
		fputs($df, $DataOutput);
		fclose($df);
	}

	IF ($debugoutput) {
		print $DataOutput;
	}
	return $DataOutput;
}

// ===========================================
// Add required styles to list of KML data points
// ===========================================

function GetS($fileoutput) {
	//used global variales
	global $searchUML, $replaceUML;
	global $xmlMissionLocation, $xml_poifile, $kmlFilelocations;

	$DataOutput= "";

//	$KMLFileSettings = ReadCatalogFile($xmlMissionLocation.$poi_settings_file, false);
	$StylesFile = ReadCatalogFile($xmlMissionLocation.$xml_poifile, false);
	
}

?>