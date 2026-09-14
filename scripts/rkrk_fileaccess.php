<?php
  //session_start();
  include_once('scripts/rkrk_gen.php');
  include_once('scripts/rkrk_csvfiles.php');
  

// File access functions
function GetWeatherData() {
global $RKRKWeatherCSV,$csvDocLocation;
///////////////////////////////////////
// Load file 
///////////////////////////////////////
	$csv_deliminator = ";";
	$csv_path = function_exists('rkrk_get_weather_csv_path')
		? rkrk_get_weather_csv_path(date("Y"))
		: ($csvDocLocation . date("Y") . $RKRKWeatherCSV);
	$data = csv_to_array($csv_path, $csv_deliminator );
	if (!is_array($data)) return [];
	$data = FormatWeatherDataArray($data);
	Return $data;
 }


?>