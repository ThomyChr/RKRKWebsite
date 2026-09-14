 ////////////////////////////////////////////////
// include to PHP
// TC 15.03.2022

<?php
// Const //
$KA = 'F';

function KartenArt($Ansicht) {
	switch ($Ansicht) {
  case 'F':
    return 'ch.swisstopo.pixelkarte-farbe';
    break;
  case 'G':
    return 'ch.swisstopo.pixelkarte-grau';
    break;
  case 'S':
    return 'ch.swisstopo.swissimage';
  break;
  default:
    return 'ch.swisstopo.pixelkarte-farbe';
	}  
}

function SetJGGeoMapFrameSettings ($JG) {
switch ($JG) {
  case 21:
    //21
    //21 https://drive.google.com/uc?export=download&id=1-553tpCr6DJkRKpxxaHewVldzIZItXSc
    $Link=   '1-553tpCr6DJkRKpxxaHewVldzIZItXSc';
	if ($East=='' || $North=='' ) {
	  $East=2679634.05;
	  $North=1235996.10;
	}
	if ($Zoom=='') {
	  $Zoom=8;
	}
    break;
  case 25:
    //25
    //25 https://drive.google.com/uc?export=download&id=1-6uykz0HIaLOWKWJOyXh02IL1Fu8z-JK
    $Link=  '1-6uykz0HIaLOWKWJOyXh02IL1Fu8z-JK';
	if ($East=='' || $North=='' ) {
	  $East=2681584.67;
	  $North=1230882.35;
	}
	if ($Zoom=='') {
	  $Zoom=8;
	}
    break;
  case 26:
    //26
    //26 https://drive.google.com/uc?export=download&id=1-8nOtBQtFnov6b2seFu0YF26uRkT8HLi
    $Link=   '1-8nOtBQtFnov6b2seFu0YF26uRkT8HLi';
	if ($East=='' || $North=='' ) {
	  $East=2677894.19;
	  $North=1230647.47;
	}
	if ($Zoom=='') {
	  $Zoom=8;
	}
    break;
  case 27:
    //27
    //27 https://drive.google.com/uc?export=download&id=1-9v_ZlTrbZ_HRQfybiwe3HNT7G9wVdBN
    $Link=   '1-9v_ZlTrbZ_HRQfybiwe3HNT7G9wVdBN';
	if ($East=='' || $North=='' ) {
	  $East=2675091.07;
	  $North=1232055.51;
	}
	if ($Zoom=='') {
	  $Zoom=8;
	}
    break;
  case 28:
    //28
    //28 https://drive.google.com/uc?export=download&id=1-B29y5YfhU1EzBSbC1Cb7eeczYOUONqJ
    $Link=   '1-B29y5YfhU1EzBSbC1Cb7eeczYOUONqd';
	if ($East=='' || $North=='' ) {
	  $East=2677537.54;
	  $North=1233550.61;
	}
	if ($Zoom=='') {
	  $Zoom=8;
	}
    break;
  case 'AG':
    //AG
    //AG https://drive.google.com/uc?export=download&id=1-DNK4G5XNhMNlYtvmXVRiHipIjQ7ycgx
    $Link=   '1-DNK4G5XNhMNlYtvmXVRiHipIjQ7ycgx';
	if ($East=='' || $North=='' ) {
	  $East=2669759.28;
	  $North=1225295.42;
	}
	if ($Zoom=='') {
	  $Zoom=8;
	}
    break;
  case 'ZG':
    //ZG
    //ZG https://drive.google.com/uc?export=download&id=1-BvkRdIz5Tz4h2MywDXveOzRYRs0t1nJ
    $Link=   '1-BvkRdIz5Tz4h2MywDXveOzRYRs0t1nJ';
	if ($East=='' || $North=='' ) {
	  $East=2683743.17;
	  $North=1224494.71;
	}
	if ($Zoom=='') {
	  $Zoom=5;
	}
    break;
  case AZ:
    //AZ
    //AZ https://drive.google.com/uc?export=download&id=1-KG564VoDenMTsdo2WjzXe883eJ5uPQ
    $Link=  '1-KG564VoDenMTsdo2WjzXe883eJ5uPQ6';
	if ($East=='' || $North=='' ) {
	  $East=2674570.15;
	  $North=1233968.12;
	}
	if ($Zoom=='') {
	  $Zoom=8;
	}
    break;
  case 'XX':
    //XX
    //XX https://drive.google.com/uc?export=download&id=1-JjFBrNZYdEoyZJpMD0VxCzbSj0OMk6i
    $Link=  '1-JjFBrNZYdEoyZJpMD0VxCzbSj0OMk6i';
	if ($East=='' || $North=='' ) {
	  $East=2674943.56;
	  $North=1233491.73;
	}
	if ($Zoom=='') {
	  $Zoom=8;
	}
    break;
  case TF:
    //TF
    //TF https://drive.google.com/uc?export=download&id=1-Vp4gtjGg-qLVC9P5HW4TmdhRUDkuAs0
    $Link=  '1-Vp4gtjGg-qLVC9P5HW4TmdhRUDkuAs0';
	if ($East=='' || $North=='' ) {
	  $East=2676946.18;
	  $North=1230656.46;
	}
	if ($Zoom=='') {
	  $Zoom=8;
	}
    break;
  default:
    //alle
    //_FF1 https://drive.google.com/uc?export=download&id=1AQD8Qi7yCuFbY-KIni_-QvkaPzYNsv0O
    //_FF2 https://drive.google.com/uc?export=download&id=1-1EFdQDjbNTy5dH0JPs32kOmiiEBZrlt
    $Link= '1AQD8Qi7yCuFbY-KIni_-QvkaPzYNsv0O';
	if ($East=='' || $North=='' ) {
	  $East=2677473.70;
	  $North=1231853.81;
	}
	if ($Zoom=='') {
	  $Zoom=8;
	}
  }
	Return "<iframe src='https://map.geo.admin.ch/embed.html?" . $Search . "lang=de&topic=ech&bgLayer=" . KartenArt($KA) . "&layers=ch.swisstopo.zeitreihen,ch.bfs.gebaeude_wohnungs_register,ch.bav.haltestellen-oev,ch.swisstopo.swisstlm3d-wanderwege,ch.astra.wanderland-sperrungen_umleitungen,KML%7C%7Chttps:%2F%2Fdrive.google.com%2Fuc%3Fexport%3Ddownload%26id%3D". $GlgLnk ."&layers_opacity=1,1,1,0.8,0.8,1&layers_visibility=false,false,false,false,false,true&layers_timestamp=18641231,,,,,&E=" . $EAST . "&N=" . $NORTH . "&zoom=" . $ZOOM . "' width='" . $framewidth . "' height='" . $frameheight . "' frameborder='0' style='border:0'></iframe>";
}



?>