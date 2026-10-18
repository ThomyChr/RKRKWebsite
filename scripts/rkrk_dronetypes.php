<?php

// ===========================================
// Funktionen
// ===========================================
	
	// reference: https://www.litchiutilities.com/docs/fov.php
	
	function FOVhoriz ($FOVdiag, $aspectVert, $aspectHoriz) {
		$aspectDiag = sqrt($aspectHoriz*$aspectHoriz+$aspectVert*$aspectVert);
		$FOV= 2* atan(tan($FOVdiag/180*pi()/2)* $aspectHoriz/$aspectDiag)/pi()*180;
		return round($FOV,0);
	}	

	function FOVvert ($FOVdiag, $aspectVert, $aspectHoriz) {
		$aspectDiag = sqrt($aspectHoriz*$aspectHoriz+$aspectVert*$aspectVert);
		$FOV= 2* atan(tan($FOVdiag/180*pi()/2)* $aspectVert/$aspectDiag)/pi()*180;
		return round($FOV,0);
	}	
	
	function FOVdiag ($FOVHoriz, $aspectVert, $aspectHoriz) {
		$aspectDiag = sqrt($aspectHoriz*$aspectHoriz+$aspectVert*$aspectVert);
		$FOV= 2* atan(tan($FOVHoriz/180*pi()/2)* $aspectDiag/$aspectHoriz)/pi()*180;
		return round($FOV,0);
	}	

// ===========================================
// Drohnendaten
// ===========================================

	$DroneData = array(	
					"Mavic 3T" => array(
						"Name" => "Mavic 3T",
						"DFOV" => 61,
						"IRCamAngle_H" => 49,
						"IRCamAngle_V" => 40,
						"IRCamResolution_H" => 640,
						"IRCamResolution_V" => 512,
						"CD_CharacteristicalDistance" => 0.605, // to calculate VLOS [m]
						"img" => "M3T.png",
						"RecomType" => "highres_wideDOV", // "superhighres_lowDOV", "highres_lowDOV", "highres_wideDOV", "lowres_lowDOV", "lowres_wideDOV"
						"DefaultType" => "std", // "std", "high", "veryhigh"
						"active" => true,
						),
					"Mavic 3TA" => array(
						"Name" => "Mavic 3TA",
						"DFOV" => 41.2,
						//"IRCamAngle_H" => 48,
						//"IRCamAngle_V" => 38,
						"IRCamResolution_H" => 640,
						"IRCamResolution_V" => 512,
						"CD_CharacteristicalDistance" => 0.605, // to calculate VLOS [m]
						"img" => "M3TA.png",
						"RecomType" => "superhighres_lowDOV", // "superhighres_lowDOV", "highres_lowDOV", "highres_wideDOV", "lowres_lowDOV", "lowres_wideDOV"
						"DefaultType" => "veryhigh", // "std", "high", "veryhigh"
						"active" => true,
						),
					"Matrice 3TD" => array(
						"Name" => "Matrice 3TD",
						"DFOV" => 61,
						//"IRCamAngle_H" => 48,
						//"IRCamAngle_V" => 38,
						"IRCamResolution_H" => 640,
						"IRCamResolution_V" => 512,
						"CD_CharacteristicalDistance" => 0.74, // to calculate VLOS [m]
						"img" => "M3TD.jpg",
						"RecomType" => "highres_wideDOV", // "superhighres_lowDOV", "highres_lowDOV", "highres_wideDOV", "lowres_lowDOV", "lowres_wideDOV"
						"DefaultType" => "std", // "std", "high", "veryhigh"
						"active" => true,
						),
					"Matrice 4T" => array(
						"Name" => "Matrice 4T",
						"DFOV" => 45,
						//"IRCamAngle_H" => 36,
						//"IRCamAngle_V" => 27,
						"IRCamResolution_H" => 640,
						"IRCamResolution_V" => 512,
						"CD_CharacteristicalDistance" => 0.71, // to calculate VLOS [m]
						"img" => "M4T.png",
						"RecomType" => "highres_lowDOV", // "superhighres_lowDOV", "highres_lowDOV", "highres_wideDOV", "lowres_lowDOV", "lowres_wideDOV"
						"DefaultType" => "high", // "std", "high", "veryhigh"
						"active" => true,
						),
					"Matrice 4TD" => array(
						"Name" => "Matrice 4TD",
						"DFOV" => 45,
						//"IRCamAngle_H" => 36,
						//"IRCamAngle_V" => 27,
						"IRCamResolution_H" => 640,
						"IRCamResolution_V" => 512,
						"CD_CharacteristicalDistance" => 0.81, // to calculate VLOS [m]
						"img" => "M4TD.png",
						"RecomType" => "highres_lowDOV", // "superhighres_lowDOV", "highres_lowDOV", "highres_wideDOV", "lowres_lowDOV", "lowres_wideDOV"
						"DefaultType" => "high", // "std", "high", "veryhigh"
						"active" => true,
						),
					"Matrice 30T" => array(
						"Name" => "Matrice 30T",
						"DFOV" => 61,
						//"IRCamAngle_H" => 48,
						//"IRCamAngle_V" => 34,
						"IRCamResolution_H" => 640,
						"IRCamResolution_V" => 512,
						"CD_CharacteristicalDistance" => 1.07, // to calculate VLOS [m]
						"img" => "M30T.png",
						"RecomType" => "highres_wideDOV", // "superhighres_lowDOV", "highres_lowDOV", "highres_wideDOV", "lowres_lowDOV", "lowres_wideDOV"
						"DefaultType" => "std", // "std", "high", "veryhigh"
						"active" => true,
						),
					"Mavic 2EA" => array(
						"Name" => "Mavic 2 Enterprise Advanced",
						"DFOV" => 0, // ~61° berechnet
						"IRCamAngle_H" => 48,
						"IRCamAngle_V" => 34,
						"IRCamResolution_H" => 640,
						"IRCamResolution_V" => 512,
						"CD_CharacteristicalDistance" => 0.575, // to calculate VLOS [m]
						"img" => "M2AE.jpg",
						"RecomType" => "highres_wideDOV", // "superhighres_lowDOV", "highres_lowDOV", "highres_wideDOV", "lowres_lowDOV", "lowres_wideDOV"
						"DefaultType" => "std", // "std", "high", "veryhigh"
						"active" => true,
						),
					"Zenmuse H20T" => array( // Matrice 300 H20T
						"Name" => "Zenmuse H20T",
						"DFOV" => 40.6,
						//"IRCamAngle_H" => 40,
						//"IRCamAngle_V" => round(40*0.8,0), //berechnet
						"IRCamResolution_H" => 640,
						"IRCamResolution_V" => 512,
						"img" => "ZH20T.png",
						"RecomType" => "highres_lowDOV", // "superhighres_lowDOV", "highres_lowDOV", "highres_wideDOV", "lowres_lowDOV", "lowres_wideDOV"
						"DefaultType" => "high", // "std", "high", "veryhigh"
						"active" => true,
						),
					"Zenmuse H30T" => array(  //	 "Matrice 350 H30T",
						"Name" => "Zenmuse H30T",
						"DFOV" => 45.2,
						//"IRCamAngle_H" => 42,
						//"IRCamAngle_V" => round(42*0.8,0), //berechnet
						"IRCamResolution_H" => 1280,
						"IRCamResolution_V" => 1024,
						"img" => "ZH30T.png",
						"RecomType" => "superhighres_lowDOV", // "superhighres_lowDOV", "highres_lowDOV", "highres_wideDOV", "lowres_lowDOV", "lowres_wideDOV"
						"DefaultType" => "high", // "std", "high", "veryhigh"
						//"hint" => "FH_HP",
						"active" => true,
						),
					"EVOII640T" => array(
						"Name" => "Autel Robotics EVO II Dual 640T",
						"DFOV" => 0, // ~41.6°  berechnet
						//https://www.autelpilot.eu/collections/evo-ii-dual-640t-v3/products/2023-new-autel-robotics-evo-ii-dual-640t-rtk-v3
						"IRCamAngle_H" => 33,
						"IRCamAngle_V" => 26, 
						"IRCamResolution_H" => 640,
						"IRCamResolution_V" => 512,
						"img" => "EVOII640T.png",
						"RecomType" => "superhighres_lowDOV", // "superhighres_lowDOV", "highres_lowDOV", "highres_wideDOV", "lowres_lowDOV", "lowres_wideDOV"
						"DefaultType" => "veryhigh", // "std", "high", "veryhigh"
						"active" => true,
						),
					"EVOLight640T" => array(
						"Name" => "Autel Robotics EVO Lite 640T Enterprise",
						"DFOV" => 61, //https:www.autelpilot.eu/collections/autel-evo-lite-enterprise/products/autel-robotics-evo-lite-640t-enterprise-drone-premium-bundle
						//"IRCamAngle_H" => 83,
						//"IRCamAngle_V" => round(83*0.8,0), //berechnet
						"IRCamResolution_H" => 640,
						"IRCamResolution_V" => 512,
						"img" => "EVOL640T.png",
						"RecomType" => "highres_wideDOV", // "superhighres_lowDOV", "highres_lowDOV", "highres_wideDOV", "lowres_lowDOV", "lowres_wideDOV"
						"DefaultType" => "std", // "std", "high", "veryhigh"
						"active" => true,
						),
					"EVOMAX4T" => array(
						"Name" => "Autel Robotics EVO MAX 4T",
						"DFOV" => 45.2, //https://www.autelpilot.eu/products/autel-robotics-evo-max-4t
						//"IRCamAngle_H" => 42,
						//"IRCamAngle_V" => round(42*0.8,0), //berechnet
						"IRCamResolution_H" => 640,
						"IRCamResolution_V" => 512,
						"img" => "EVOMax4T.png",
						"RecomType" => "highres_lowDOV", // "superhighres_lowDOV", "highres_lowDOV", "highres_wideDOV", "lowres_lowDOV", "lowres_wideDOV"
						"DefaultType" => "high", // "std", "high", "veryhigh"
						"active" => true,
						),
					"EVOMAX4N" => array(
						"Name" => "Autel Robotics EVO MAX 4T",
						"DFOV" => 42, //https://www.autelpilot.eu/products/autel-robotics-evo-max-4n
						//"IRCamAngle_H" => 52,
						//"IRCamAngle_V" => round(52*0.8,0), //berechnet
						"IRCamResolution_H" => 640,
						"IRCamResolution_V" => 512,
						"img" => "EVOMax4T.png",
						"RecomType" => "highres_lowDOV", // "superhighres_lowDOV", "highres_lowDOV", "highres_wideDOV", "lowres_lowDOV", "lowres_wideDOV"
						"DefaultType" => "high", // "std", "high", "veryhigh"
						"active" => true,
						),
					"YUNEECE10T34" => array(
						"Name" => "Yuneec E10T",
						"DFOV" => 0, // ~42.8° berechnet
						"IRCamAngle_H" => 34, // Nur FOV angegeben: https://yuneec.online/e10tv/info/#specs
						"IRCamAngle_V" => round(34*0.8,0), //berechnet
						"IRCamResolution_H" => 640,
						"IRCamResolution_V" => 512,
						"img" => "YE10.png",
						"RecomType" => "superhighres_lowDOV", // "superhighres_lowDOV", "highres_lowDOV", "highres_wideDOV", "lowres_lowDOV", "lowres_wideDOV"
						"DefaultType" => "veryhigh", // "std", "high", "veryhigh"
						"active" => true,
						),
					"YUNEECE10T50" => array(
						"Name" => "Yuneec E10T",
						"DFOV" => 0, // ~63° berechnet
						"IRCamAngle_H" => 50, // Nur FOV angegeben: https://yuneec.online/e10tv/info/#specs
						"IRCamAngle_V" => round(50*0.8,0), //berechnet
						"IRCamResolution_H" => 640,
						"IRCamResolution_V" => 512,
						"img" => "YE10.png",
						"RecomType" => "highres_wideDOV", // "superhighres_lowDOV", "highres_lowDOV", "highres_wideDOV", "lowres_lowDOV", "lowres_wideDOV"
						"DefaultType" => "std", // "std", "high", "veryhigh"
						"active" => true,
						),
					"YUNEECE10Tv34" => array(
						"Name" => "Yuneec E10Tx",
						"DFOV" => 0, // ~42.8° berechnet
						"IRCamAngle_H" => 34, // Nur FOV angegeben: https://yuneec.online/e10tv/info/#specs
						"IRCamAngle_V" => round(34*0.8,0), //berechnet
						"IRCamResolution_H" => 320,
						"IRCamResolution_V" => 256,
						"img" => "YE10.png",
						"RecomType" => "lowres_lowDOV", // "superhighres_lowDOV", "highres_lowDOV", "highres_wideDOV", "lowres_lowDOV", "lowres_wideDOV"
						"DefaultType" => "std", // "std", "high", "veryhigh"
						"active" => true,
						),
					"YUNEECE10Tv50" => array(
						"Name" => "Yuneec E10Tx",
						"DFOV" => 0, // ~63° berechnet
						"IRCamAngle_H" => 50, // Nur FOV angegeben: https://yuneec.online/e10tv/info/#specs
						"IRCamAngle_V" => 0, //berechnet
						"IRCamResolution_H" => 320,
						"IRCamResolution_V" => 256,
						"img" => "YE10.png",
						"RecomType" => "lowres_wideDOV", // "superhighres_lowDOV", "highres_lowDOV", "highres_wideDOV", "lowres_lowDOV", "lowres_wideDOV"
						"DefaultType" => "std", // "std", "high", "veryhigh"
						"active" => true,
						),
					"FB320 34°" => array(
						"Name" => "FB320 34°",
						"DFOV" => 0, // ~42.8° berechnet
						"IRCamAngle_H" => 34,
						"IRCamAngle_V" => round(34*0.8,0),
						"IRCamResolution_H" => 320,
						"IRCamResolution_V" => 256,
						"img" => "flir_wb320.jpg",
						"RecomType" => "lowres_lowDOV", // "superhighres_lowDOV", "highres_lowDOV", "highres_wideDOV", "lowres_lowDOV", "lowres_wideDOV"
						"DefaultType" => "std", // "std", "high"
						"active" => true,
						),
					"FB320 50°" => array(
						"Name" => "FB320 50°",
						"DFOV" => 0, // ~63° berechnet
						"IRCamAngle_H" => 50,
						"IRCamAngle_V" => 40,
						"IRCamResolution_H" => 320,
						"IRCamResolution_V" => 256,
						"img" => "flir_wb320.jpg",
						"RecomType" => "lowres_wideDOV", // "superhighres_lowDOV", "highres_lowDOV", "highres_wideDOV", "lowres_lowDOV", "lowres_wideDOV"
						"DefaultType" => "std", // "std", "high"
						"active" => true,
						),
					"FB640" => array(
						"Name" => "Mavic 2 zoom, Bambikit 640",
						"DFOV" => 0, // ~40.4° berechnet
						"IRCamAngle_H" => 32,
						"IRCamAngle_V" => 0,
						"IRCamResolution_H" => 640,
						"IRCamResolution_V" => 512,
						"img" => "flir_wb640.jpg",
						"RecomType" => "highres_wideDOV", // "superhighres_lowDOV", "highres_lowDOV", "highres_wideDOV", "lowres_lowDOV", "lowres_wideDOV"
						"DefaultType" => "std", // "std", "high"
						"active" => true,
						),
					"XT384" => array(
						"Name" => "Mavic 2 zoom, Bambikit XT384",
						"DFOV" => 0, // ~35° berechnet
						"IRCamAngle_H" => 28,
						"IRCamAngle_V" => 0,
						"IRCamResolution_H" => 384,
						"IRCamResolution_V" => 288,
						"img" => "UAV-XT384.jpg",
						"RecomType" => "lowres_lowDOV", // "superhighres_lowDOV", "highres_lowDOV", "highres_wideDOV", "lowres_lowDOV", "lowres_wideDOV"
						"DefaultType" => "std", // "std", "high"
						"active" => true,
						),
					);	

?>
