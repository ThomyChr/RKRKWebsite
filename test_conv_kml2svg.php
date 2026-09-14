<?php

include_once('scripts/rkrk_kml2svg.php');

$dir = "../Missionen/_temp/";
$filename1 = "RKRK_Felder_leer (3)";
$kmlFile1 = $dir . $filename1 . ".kml";
$svgFile1 = $dir . $filename1 . ".svg";

$filename2 = "RKRK_Felder_32";
$kmlFile2 = $dir . $filename2 . ".kml";
$svgFile2 = $dir . $filename2 . ".svg";


/*
$ok_svg = kml_to_svg($kmlFile1, $svgFile1);
if ($ok_svg) {
  echo  $filename1 . ".svg" . " SUCCESSULLY created.<br>";
} else {
  echo  $filename1 . ".svg" . " creation FAILED. <br>";
}

$ok_svg = kml_to_svg($kmlFile2, $svgFile2);
if ($ok_svg) {
  echo  $filename2 . ".svg" . " SUCCESSULLY created. <br>";
} else {
  echo  $filename2 . ".svg" . " creation FAILED. <br>";
}
*/

$ok_all = kml_to_all($kmlFile1, $dir . $filename1);

if ($ok_all) {
  echo  $filename1 . " images SUCCESSULLY created. <br>";
} else {
  echo  $filename1 . " images creation FAILED. 1<br>";
}

$ok_all = kml_to_all($kmlFile2, $dir . $filename2);

if ($ok_all) {
  echo  $filename2 . " images SUCCESSULLY created. <br>";
} else {
  echo  $filename2 . " images creation FAILED. 1<br>";
}
?>