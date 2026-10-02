<?php
	session_start();
	include_once('scripts/rkrk_gen_ext.php');
	include_once('scripts/rkrk_dronetypes.php');
	include_once('scripts/rkrk_inputforms.php');

	headerpage("Bahnbreiten-Berechnung", false);
	
	//check impropriate IP addresses accessing page
	/**
	CheckIPandExit();
	**/
	
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

	//echo $set_calc;
	
	// ===========================================
	// Definitionen
	// ===========================================
	
	$drawwidth=650;
	$drawheight=280;
	$trainingmaterial="";



	$LineDefaults = Array(
		"fh" =>3,
		"fhq" =>3,
		"bq" =>3,
		"aw" =>5,
		"wr" =>6,
		"ws" =>8,
	);	

	$FlightSettings = array(
			"superhighres_lowDOV" => Array(
				//super high resolution 1280x1024, narrow DFOV ~45°
				"recommendations" => Array(
					"fh" => Array("flightheight" => Array(80,85,90,95,100)),
					"fhq" => Array("flightheight" => Array(75,80,85,90,95,100)),
					"bq" => Array("flightheight" => Array(60,65,70,75,80,85,90), "angle" => Array(50,55,60)),
					"aw" => Array("flightheight" => Array(50,55,60,65,70,80,90), "angle" => Array(40,45,50)),
					"wr" => Array("flightheight" => Array(40,45,50,60,70), "angle" => Array(35,40)),
					"ws" => Array("flightheight" => Array(80,85,90,95,100,105,110), "angle" => Array(50,55,60),"treeheight" => Array(30,40)),
				),
				"hint" => "FH_SHHP",
			),
			"highres_lowDOV" => Array(
				//high resolution 640x512, narrow DFOV ~45°
				"recommendations" => Array(
					"fh" => Array("flightheight" => Array(70,75,80,85,90)),
					"fhq" => Array("flightheight" => Array(65,70,75,80,85,90)),
					"bq" => Array("flightheight" => Array(60,65,70,75,80), "angle" => Array(50,55,60)),
					"aw" => Array("flightheight" => Array(50,55,60,65,70,80), "angle" => Array(40,45,50)),
					"wr" => Array("flightheight" => Array(25,40,45), "angle" => Array(30,35,40)),
					"ws" => Array("flightheight" => Array(80,85,90,95,100), "angle" => Array(50,55,60),"treeheight" => Array(30,40)),
				),
				"hint" => "FH_HHP",
			),
			"highres_wideDOV" => Array(
				//high resolution 640x512, wide DFOV ~60°
				"recommendations" => Array(
					"fh" => Array("flightheight" => Array(50,55,60,65,70)),
					"fhq" => Array("flightheight" => Array(50,55,60,65,70)),
					"bq" => Array("flightheight" => Array(50,55,60), "angle" => Array(50,55,60)),
					"aw" => Array("flightheight" => Array(40,45,50), "angle" => Array(40,45,50)),
					"wr" => Array("flightheight" => Array(25,30,35), "angle" => Array(30,35,40)),
					"ws" => Array("flightheight" => Array(70,75,80,85,90), "angle" => Array(50,55,60),"treeheight" => Array(30,40)),
				),
				"hint" => "FH_HP",
			),
			"lowres_lowDOV" => Array(
				//low resolution 320x256, narrow HFOV ~34°
				"recommendations" => Array(
					"fh" => Array("flightheight" => Array(40,45,50,55,60)),
					"fhq" => Array("flightheight" => Array(40,45,50,55,60)),
					"bq" => Array("flightheight" => Array(50,55,60), "angle" => Array(50,55,60)),
					"aw" => Array("flightheight" => Array(40,45,50), "angle" => Array(40,45,50)),
					"wr" => Array("flightheight" => Array(25,30,35), "angle" => Array(30,35,40)),
					"ws" => Array("flightheight" => Array(70,75,80,85,90), "angle" => Array(50,55,60),"treeheight" => Array(30,40)),
				),
				"hint" => "FH_LP",
			),
			"lowres_wideDOV" => Array(
				//low resolution 320x256, wide HFOV ~40-50°
				"recommendations" => Array(
					"fh" => Array("flightheight" => Array(40,45,50,55)),
					"fhq" => Array("flightheight" => Array(40,45,50,55,60)),
					"bq" => Array("flightheight" => Array(50,55,60), "angle" => Array(50,55,60)),
					"aw" => Array("flightheight" => Array(40,45,50), "angle" => Array(40,45,50)),
					"wr" => Array("flightheight" => Array(25,30,35), "angle" => Array(30,35,40)),
					"ws" => Array("flightheight" => Array(70,75,80,85,90), "angle" => Array(50,55,60),"treeheight" => Array(30,40)),
				),
				"hint" => "FH_LLP",
			),
		);

	$DefaultSettings = Array(
		"std" => Array(
				"fh" => Array( 		//4 lines
					"flightheights" => Array(40,50,60,70,80),
				),
				"fhq" => Array(  	//4 lines
					"flightheights" => Array(40,50,60,70,80),
				),
				"bq" => Array( 		//5x2 = 10 lines
					"flightheights" => Array(40,50,60,70,80),
					"camangle" => Array(50,60),
				),
				"aw" => Array( 		//5x3 = 15 lines
					"flightheights" => Array(30,40,50,60),
					"camangle" => Array(40,50,60),
				),
				"wr" => Array( 		//4x2x1 = 8 lines
					"flightheights" => Array(20,30,40),
					"camangle" => Array(25,30,35,40),
					"searchdist" => Array(10),
				),
				"ws" => Array( 		//4x4x1 = 16 lines
					"flightheights" => Array(60,70,80,90,100),
					"camangle" => Array(50,60,70),
					"treeheight" => Array(30),
				),
			),
		"high" => Array(
				"fh" => Array( 		//4 fields
					"flightheights" => Array(60,70,80,90,100),
				),
				"fhq" => Array(  	//4 fields
					"flightheights" => Array(60,70,80,90,100),
				),
				"bq" => Array( 		//5x2 = 10 lines
					"flightheights" => Array(60,70,80,90,100),
					"camangle" => Array(50,55),
				),
				"aw" => Array( 		//5x3 = 15 lines
					"flightheights" => Array(40,50,60,70,80),
					"camangle" => Array(40,50,60),
				),
				"wr" => Array( 		//4x2x1 = 8 lines
					"flightheights" => Array(30,40,50,60),
					"camangle" =>  Array(25,30,35,40),
					"searchdist" => Array(10),
				),
				"ws" => Array( 		//4x4x1 = 16 lines
					"flightheights" => Array(90,100,110,120),
					"camangle" => Array(50,60,70),
					"treeheight" => Array(30),
				),
			),
		"veryhigh" => Array(
				"fh" => Array( 		//4 fields
					"flightheights" => Array(70,80,90,100,110),
				),
				"fhq" => Array(  	//4 fields
					"flightheights" => Array(70,80,90,100,110),
				),
				"bq" => Array( 		//5x2 = 10 lines
					"flightheights" => Array(70,80,90,100,110),
					"camangle" => Array(50,55),
				),
				"aw" => Array( 		//5x3 = 15 lines
					"flightheights" => Array(50,60,70,80,90),
					"camangle" => Array(40,50,60),
				),
				"wr" => Array( 		//4x2x1 = 8 lines
					"flightheights" => Array(40,50,60,70),
					"camangle" =>  Array(25,30,35,40),
					"searchdist" => Array(10),
				),
				"ws" => Array( 		//4x4x1 = 16 lines
					"flightheights" => Array(90,100,110,120),
					"camangle" => Array(50,60,70),
					"treeheight" => Array(30),
				),
			),
	);
	

    //Berechne Höhen und Vertikalwinkel
	//echo "M3T: 48,512,640 :" . FOVdiag(48,512,640) . "<br>"; 
	//echo "M4T: 61,512,640 :" . FOVdiag(36,512,640) . "<br>"; 
	//echo "EVOII640T: 33,512,640 :" . FOVdiag(33,512,640) . "<br>"; 
	//echo "YUNEECE10Tv34: 34,512,640 :" . FOVdiag(34,512,640) . "<br>"; 
	//echo "H30T: 34,1024,1280 :" . FOVdiag(36,1024,1280) . "<br>"; 
	//echo "FB320 34: 34,256,320 :" . FOVdiag(34,256,320) . "<br>"; 
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


	$CalcOptions = array(
						"Feldsuche vorwärts" => array (
							"Name" => "Feldsuche vorwärts",
							"img" => "bahnabstand.jpg",
							"key" => "fh",
							"show" => true,
							"active" => true,
						),
						"Flug seitwärts" => array (
							"Name" => "Flug seitwärts",
							"img" => "querflug.jpg",
							"key" => "fhq",
							"show" => true,
							"active" => true,
						),
						"Feldsuche seitwärts" => array (
							"Name" => "Feldsuche seitwärts",
							//"img" => "bahnabstand quer.jpg",
							"img" => "bahnabstand quer links.jpg",
							"key" => "bq",
							"show" => false,
							"active" => true,
						),
						"Feld am Waldrand" => array (
							"Name" => "Feld am Waldrand",
							//"img" => "waldrandabstand.jpg",
							"img" => "waldrandabstand links.jpg",
							"key" => "aw",
							"show" => false,
							"active" => true,
						),
						"Suche im Wald" => array (
							"Name" => "Suche im Wald",
							//"img" => "waldrandsuche.jpg",
							"img" => "waldrandsuche links.jpg",
							"key" => "wr",
							"show" => false,
							"active" => true,
						),
						"Waldsuche seitwärts" => array (
							"Name" => "Walsuche seitwärts",
							//"img" => "waldrandsuche.jpg",
							"img" => "bahnabstand quer links.jpg",
							"key" => "ws",
							"show" => false,
							"active" => true,
						),
					);


	$CalcIconLoc = $ImageLoc ."calc/";


	$PrefParameters = Array( 
						// Feldsuche vorwärts
						"fh" => Array(
								"flightheights" => Array(20,25,30,35,40,45,50,55,60,65,70,75,80,85,90,95,100,110,120),
								"video" => "Wegpunktflug.mp4",
								"table" => Array(
									Array(
										"title" => "",
										"key" => "draw-",
										"js_exe" => true,
										"show" => true,
										),
									Array(
										"title" => "Flughöhe<br>[m]",
										"key" => "flightheight-",
										"js_exe" => true,
										"show" => true,
										),
									Array(
										"title" => "Sichtbreite<br>[m]",
										"key" => "SBmU_m_",
										"js_exe" => false,
										"show" => true,
										),
									Array(
										"title" => "Überlapppung<br>(pro Seite)<br>[m]",
										"key" => "SBpS_m_",
										"js_exe" => false,
										"show" => true,
										),
									Array(
										"title" => "Suchbreite/<br>Abstand der<br>Flugbahn<br>[m]",
										"key" => "SuBmU_m_",
										"js_exe" => false,
										"show" => true,
										),
									//Array(
									//	"title" => "Abstand der<br>Flugbahn<br>[m]",
									//	"key" => "AdFb_m_",
									//	"js_exe" => false,
									//	"show" => true,
									//	),
									Array(
										"title" => "Empfohlene<br>Flugbahnbreite<br>[m]",
										"key" => "EFbB_m_",
										"js_exe" => false,
										"show" => true,
										),
									Array(
										"title" => "Empfohlener<br>Randabstand<br>[m]",
										"key" => "ERA_m_",
										"js_exe" => false,
										"show" => true,
										),
									Array(
										"title" => "UAV<br>Bahnbreite<br>[m]",
										"key" => "UBB_m_",
										"js_exe" => false,
										"show" => true,
										),
									Array(
										"title" => "UAV<br>Randabstand<br>[m]",
										"key" => "URA_m_",
										"js_exe" => false,
										"show" => true,
										),
								),
							),
						// Flug seitwärts
						"fhq" => Array(
								"flightheights" => Array(20,25,30,35,40,45,50,55,60,65,70,75,80,85,90,95,100,110,120),
								"video" => "Wegpunktflug.mp4",
								"table" => Array(
									Array(
										"title" => "",
										"key" => "draw_q-",
										"js_exe" => true,
										"show" => true,
										),
									Array(
										"title" => "Flughöhe<br>[m]",
										"key" => "flightheight_q-",
										"js_exe" => true,
										"show" => true,
										),
									Array(
										"title" => "Sichtbreite<br>[m]",
										"key" => "SBmU_q_m_",
										"js_exe" => false,
										"show" => true,
										),
									Array(
										"title" => "Überlapppung<br>(pro Seite)<br>[m]",
										"key" => "SBpS_q_m_",
										"js_exe" => false,
										"show" => true,
										),
									Array(
										"title" => "Suchbreite/<br>Abstand der<br>Flugbahn<br>[m]",
										"key" => "SuBmU_q_m_",
										"js_exe" => false,
										"show" => true,
										),
									//Array(
									//	"title" => "Abstand der<br>Flugbahn<br>[m]",
									//	"key" => "AdFb_q_m_",
									//	"js_exe" => false,
									//	"show" => true,
									//	),
									Array(
										"title" => "Empfohlene<br>Flugbahnbreite<br>[m]",
										"key" => "EFbB_q_m_",
										"js_exe" => false,
										"show" => true,
										),
									Array(
										"title" => "Empfohlener<br>Randabstand<br>[m]",
										"key" => "ERA_q_m_",
										"js_exe" => false,
										"show" => true,
										),
									Array(
										"title" => "UAV<br>Bahnbreite<br>[m]",
										"key" => "UBB_q_m_",
										"js_exe" => false,
										"show" => true,
										),
									Array(
										"title" => "UAV<br>Randabstand<br>[m]",
										"key" => "URA_q_m_",
										"js_exe" => false,
										"show" => true,
										),
								),
							),
						// Feldsuche seitwärts
						"bq" => Array(
								"flightheights" => Array(20,25,30,35,40,45,50,55,60,65,70,75,80,85,90,95,100,110,120),
								"camangle" => Array(25,30,35,40,45,50,55,60,65,70,75,80,85,90),
								"video" => "Wegpunktflug quer.mp4",
								"table" => Array(
									Array(
										"title" => "",
										"key" => "draw_bq-",
										"js_exe" => true,
										"show" => true,
										),
									Array(
										"title" => "Flug-<br>höhe<br>[m]",
										"key" => "flightheight_bq-",
										"js_exe" => true,
										"show" => true,
										),
									Array(
										"title" => "Kamera-<br>winkel<br>[°]",
										"key" => "oangle_bq-",
										"js_exe" => true,
										"show" => true,
										),
									Array(
										"title" => "Sicht-<br>breite<br>[m]",
										"key" => "SBmU_bq_",
										"js_exe" => false,
										"show" => true,
										),
									Array(
										"title" => "Über-<br>lappung<br>[m]",
										"key" => "UeAS_bq_",
										"js_exe" => false,
										"show" => true,
										),
									Array(
										"title" => "Such-<br>breite<br>[m]",
										"key" => "SuBmU_bq_",
										"js_exe" => false,
										"show" => true,
										),
									Array(
										"title" => "Offset-<br>zum<br>Mittel-<br>punkt<br>[m]",
										"key" => "OMP_bq_",
										"js_exe" => false,
										"show" => true,
										),
									Array(
										"title" => "Mittlere<br>Distanz<br>[m]",
										"key" => "MiD_bq_",
										"js_exe" => false,
										"show" => true,
										),
									Array(
										"title" => "Maximale<br>Distanz<br>[m]",
										"key" => "MaD_bq_",
										"js_exe" => false,
										"show" => true,
										),
									Array(
										"title" => "Empfohlene<br>Bahnbreite<br>[m]",
										"key" => "EFBA_bq_",
										"js_exe" => false,
										"show" => true,
										),
									Array(
										"title" => "UAV<br>Bahn-<br>breite<br>[m]",
										"key" => "FBA_bq_",
										"js_exe" => false,
										"show" => true,
										),
									Array(
										"title" => "UAV<br>Feld-<br>[offset<br>[m]",
										"key" => "FO_bq_",
										"js_exe" => false,
										"show" => true,
										),
									Array(
										"title" => "UAV<br>Flugpfad-<br>offset<br>[m]",
										"key" => "FPof_bq_",
										"js_exe" => false,
										"show" => true,
										),
									//31.10.2025 Keinen Randabstand für Winkelflüge
									//Array(
									//	"title" => "UAV<br>Randabstand<br>[m]",
									//	"key" => "URA_bq_",
									//	"js_exe" => false,
									//	"show" => false,
									//	),
								),
							),
						// Feld am Waldrand
						"aw" => Array(
								"flightheights" => Array(20,25,30,35,40,45,50,55,60,65,70,75,80,85,90,95,100,110,120),
								"camangle" => Array(25,30,35,40,45,50,55,60),
								"video" => "Suche am Waldrand.mp4",
								"table" => Array(
									Array(
										"title" => "",
										"key" => "draw_aw-",
										"js_exe" => true,
										"show" => true,
										),
									Array(
										"title" => "Flug-<br>höhe<br>[m]",
										"key" => "flightheight_aw-",
										"js_exe" => true,
										"show" => true,
										),
									Array(
										"title" => "Kamera-<br>winkel<br>[°]",
										"key" => "oangle_aw-",
										"js_exe" => true,
										"show" => true,
										),
									Array(
										"title" => "Sicht-<br>breite<br>[m]",
										"key" => "SBmU_w_",
										"js_exe" => false,
										"show" => true,
										),
									Array(
										"title" => "Überlappung<br>(pro Seite)<br>[m]",
										"key" => "UeAS_w_",
										"js_exe" => false,
										"show" => true,
										),
									Array(
										"title" => "Such-<br>[breite<br>[m]",
										"key" => "SuBmU_w_",
										"js_exe" => false,
										"show" => true,
										),
									Array(
										"title" => "Offset zum<br>Mittelpunkt<br>[m]",
										"key" => "OMP_w_",
										"js_exe" => false,
										"show" => true,
										),
									Array(
										"title" => "Mittlere<br>Distanz<br>[m]",
										"key" => "MD_w_",
										"js_exe" => false,
										"show" => true,
										),
									Array(
										"title" => "Empfohlene<br>Suchdistanz<br>[m]",
										"key" => "EFBB_w_",
										"js_exe" => false,
										"show" => true,
										),
									//31.10.2025 Keinen Randabstand für Winkelflüge
									//Array(
									//	"title" => "Empfohlener<br>Randabstand<br>[m]",
									//	"key" => "ERA_w_",
									//	"js_exe" => false,
									//	"show" => true,
									//	),
									Array(
										"title" => "UAV<br>Feld-<br>offset<br>[m]",
										"key" => "FO_w_",
										"js_exe" => false,
										"show" => true,
										),
									Array(
										"title" => "UAV<br>Such-<br>distanz<br>[m]",
										"key" => "UBB_w_",
										"js_exe" => false,
										"show" => true,
										),
									//31.10.2025 Keinen Randabstand für Winkelflüge
									//Array(
									//	"title" => "UAV<br>Randabstand<br>[m]",
									//	"key" => "URA_w_",
									//	"js_exe" => false,
									//	"show" => true,
									//	),
								),
							),
						// Feldsuche seitwärts
						"bq" => Array(
								"flightheights" => Array(20,25,30,35,40,45,50,55,60,65,70,75,80,85,90,95,100,110,120),
								"camangle" => Array(25,30,35,40,45,50,55,60,65,70,75,80,85,90),
								"video" => "Wegpunktflug quer.mp4",
								"table" => Array(
									Array(
										"title" => "",
										"key" => "draw_bq-",
										"js_exe" => true,
										"show" => true,
										),
									Array(
										"title" => "Flug-<br>höhe<br>[m]",
										"key" => "flightheight_bq-",
										"js_exe" => true,
										"show" => true,
										),
									Array(
										"title" => "Kamera-<br>winkel<br>[°]",
										"key" => "oangle_bq-",
										"js_exe" => true,
										"show" => true,
										),
									Array(
										"title" => "Sicht-<br>breite<br>[m]",
										"key" => "SBmU_bq_",
										"js_exe" => false,
										"show" => true,
										),
									Array(
										"title" => "Über-<br>lappung<br>[m]",
										"key" => "UeAS_bq_",
										"js_exe" => false,
										"show" => true,
										),
									Array(
										"title" => "Such-<br>breite<br>[m]",
										"key" => "SuBmU_bq_",
										"js_exe" => false,
										"show" => true,
										),
									Array(
										"title" => "Offset-<br>zum<br>Mittel-<br>punkt<br>[m]",
										"key" => "OMP_bq_",
										"js_exe" => false,
										"show" => true,
										),
									Array(
										"title" => "Mittlere<br>Distanz<br>[m]",
										"key" => "MiD_bq_",
										"js_exe" => false,
										"show" => true,
										),
									Array(
										"title" => "Maximale<br>Distanz<br>[m]",
										"key" => "MaD_bq_",
										"js_exe" => false,
										"show" => true,
										),
									Array(
										"title" => "Empfohlene<br>Bahnbreite<br>[m]",
										"key" => "EFBA_bq_",
										"js_exe" => false,
										"show" => true,
										),
									Array(
										"title" => "UAV<br>Bahn-<br>breite<br>[m]",
										"key" => "FBA_bq_",
										"js_exe" => false,
										"show" => true,
										),
									Array(
										"title" => "UAV<br>Feld-<br>[offset<br>[m]",
										"key" => "FO_bq_",
										"js_exe" => false,
										"show" => true,
										),
									Array(
										"title" => "UAV<br>Flugpfad-<br>offset<br>[m]",
										"key" => "FPof_bq_",
										"js_exe" => false,
										"show" => true,
										),
									//31.10.2025 Keinen Randabstand für Winkelflüge
									//Array(
									//	"title" => "UAV<br>Randabstand<br>[m]",
									//	"key" => "URA_bq_",
									//	"js_exe" => false,
									//	"show" => false,
									//	),
								),
							),
						// Suche im Wald
						"wr" => Array(
								"flightheights"=> Array(20,25,30,35,40,45,50,55,60,65,70,75,80,85,90,95,100,110,120),
								"camangle" => Array(25,30,35,40,45,50,55,60),
								"searchdist" => Array(5,10,15,20,25),
								"video" => "Absuche des Waldrandes.mp4",
								"table" => Array(
									Array(
										"title" => "",
										"key" => "draw_wr-",
										"js_exe" => true,
										"show" => true,
										),
									Array(
										"title" => "Flughöhe<br>[m]",
										"key" => "flightheight_wr-",
										"js_exe" => true,
										"show" => true,
										),
									Array(
										"title" => "Kamera-<br>winkel<br>[°]",
										"key" => "oangle_wr-",
										"js_exe" => true,
										"show" => true,
										),
									Array(
										"title" => "Äussere<br>Suchdistanz<br>[m]",
										"key" => "sdist_wr-",
										"js_exe" => true,
										"show" => true,
										),
									Array(
										"title" => "Über-<br>lappung<br>[m]",
										"key" => "UeAS_wr_",
										"js_exe" => false,
										"show" => true,
										),
									Array(
										"title" => "Flugpfad<br>Offset<br>[m]",
										"key" => "MA_wr_",
										"js_exe" => false,
										"show" => true,
										),
									Array(
										"title" => "Suchhöhe<br>[m]",
										"key" => "SH_wr_",
										"js_exe" => false,
										"show" => true,
										),
									Array(
										"title" => "Empfohlener<br>Abstand<br>Waldrand<br>[m]",
										"key" => "UAFW_wr_",
										"js_exe" => false,
										"show" => true,
										),
									Array(
										"title" => "UAV<br>Feldoffset<br>[m]",
										"key" => "FO_wr_",
										"js_exe" => false,
										"show" => true,
										),
									Array(
										"title" => "UAV Abstand<br>Waldrand-/<br>Flugbahn<br>[m]",
										"key" => "AFB_wr_",
										"js_exe" => false,
										"show" => true,
										),

								),
							),
						// Waldsuche seitwärts
						"ws" => Array(
								"flightheights" => Array(20,25,30,35,40,45,50,55,60,65,70,75,80,85,90,95,100,110,120),
								"camangle" => Array(25,30,35,40,45,50,55,60,65,70,75,80,85,90),
								"treeheight" => Array(10,15,20,25,30,35,40),
								"video" => "Wegpunktflug quer.mp4",
								"table" => Array(
									Array(
										"title" => "",
										"key" => "draw_ws-",
										"js_exe" => true,
										"show" => true,
										),
									Array(
										"title" => "Flug-<br>höhe<br>[m]",
										"key" => "flightheight_ws-",
										"js_exe" => true,
										"show" => true,
										),
									Array(
										"title" => "Kamera-<br>winkel<br>[°]",
										"key" => "oangle_ws-",
										"js_exe" => true,
										"show" => true,
										),
									Array(
										"title" => "Baum-<br>höhe<br>[m]",
										"key" => "treeheight_ws-",
										"js_exe" => true,
										"show" => true,
										),
									Array(
										"title" => "Sicht-<br>breite<br>[m]",
										"key" => "SBmU_ws_",
										"js_exe" => false,
										"show" => true,
										),
									Array(
										"title" => "Über-<br>lappung<br>[m]",
										"key" => "UeAS_ws_",
										"js_exe" => false,
										"show" => true,
										),
									Array(
										"title" => "Such-<br>breite<br>[m]",
										"key" => "SuBmU_ws_",
										"js_exe" => false,
										"show" => true,
										),
									Array(
										"title" => "Offset-<br>zum<br>Mittel-<br>punkt<br>[m]",
										"key" => "OMP_ws_",
										"js_exe" => false,
										"show" => true,
										),
									Array(
										"title" => "Mittlere<br>Distanz<br>[m]",
										"key" => "MiD_ws_",
										"js_exe" => false,
										"show" => true,
										),
									Array(
										"title" => "Maximale<br>Distanz<br>[m]",
										"key" => "MaD_ws_",
										"js_exe" => false,
										"show" => true,
										),
									Array(
										"title" => "Empfohlene<br>Bahnbreite<br>[m]",
										"key" => "EFBA_ws_",
										"js_exe" => false,
										"show" => true,
										),
									Array(
										"title" => "UAV<br>Bahn-<br>breite<br>[m]",
										"key" => "FBA_ws_",
										"js_exe" => false,
										"show" => true,
										),
									Array(
										"title" => "UAV<br>Feld-<br>[offset<br>[m]",
										"key" => "FO_ws_",
										"js_exe" => false,
										"show" => true,
										),
									Array(
										"title" => "UAV<br>Flugpfad-<br>offset<br>[m]",
										"key" => "FPof_ws_",
										"js_exe" => false,
										"show" => true,
										),
									//31.10.2025 Keinen Randabstand für Winkelflüge
									//Array(
									//	"title" => "UAV<br>Randabstand<br>[m]",
									//	"key" => "URA_ws_",
									//	"js_exe" => false,
									//	"show" => false,
									//	),
								),
							),

		);

	
	//Zuweisung der Anzahl Spalten
	$PrefParameters ["fh"]["#Col"] = count($PrefParameters ["fh"]["table"])-2;
	$PrefParameters ["fhq"]["#Col"] = count($PrefParameters ["fhq"]["table"])-2;
	$PrefParameters ["bq"]["#Col"] = count($PrefParameters ["bq"]["table"])-3;
	$PrefParameters ["aw"]["#Col"] = count($PrefParameters ["aw"]["table"])-3;
	$PrefParameters ["wr"]["#Col"] = count($PrefParameters ["wr"]["table"])-4;
	$PrefParameters ["ws"]["#Col"] = count($PrefParameters ["ws"]["table"])-4;
	
	$lgrey = "#e5e7e9";

	//enable/disable tables
	$logo = "rkrk";
	$copyright = "drohnenrehkitzrettung.ch";
	$setting= "PUB";
	
	if (isset($_GET["TEST"])) {
		$CalcOptions["Feldsuche vorwärts"]["show"] = true;
		$CalcOptions["Flug seitwärts"]["show"] = true;
		$CalcOptions["Feldsuche seitwärts"]["show"] = true;
		$CalcOptions["Feld am Waldrand"]["show"] = true;
		$CalcOptions["Suche im Wald"]["show"] = true;
		$CalcOptions["Waldsuche seitwärts"]["show"] = true;
		$Infoplus= true;
		$logo = "rkrk";
		$copyright = "drohnenrehkitzrettung.ch";
		$trainingmaterial = "2025 Bahnbreitenberechnung RKRSplus V1.1.pdf";
		$setting= "TEST";
		$graph="show";
	}

	if (isset($_GET["RKRK"])) {
		$CalcOptions["Feldsuche vorwärts"]["show"] = true;
		$CalcOptions["Flug seitwärts"]["show"] = true;
		$CalcOptions["Feldsuche seitwärts"]["show"] = true;
		$CalcOptions["Feld am Waldrand"]["show"] = true;
		$CalcOptions["Suche im Wald"]["show"] = true;
		$CalcOptions["Waldsuche seitwärts"]["show"] = false;
		$Infoplus= true;
		$logo = "rkrk";
		$copyright = "drohnenrehkitzrettung.ch";
		$trainingmaterial = "2025 Bahnbreitenberechnung RKRSplus V1.1.pdf";
		$setting= "RKRK";
		$graph="show";
	}

	if (isset($_GET["RKRS"])) {
		$CalcOptions["Feldsuche vorwärts"]["show"] = true;
		$CalcOptions["Flug seitwärts"]["show"] = true;
		$CalcOptions["Feldsuche seitwärts"]["show"] = false;
		$CalcOptions["Feld am Waldrand"]["show"] = false;
		$CalcOptions["Suche im Wald"]["show"] = false;
		$CalcOptions["Waldsuche seitwärts"]["show"] = false;
		$Infoplus= false;
		$logo = "rkrs";
		$copyright = "rehkitzrettung.ch";
		$trainingmaterial = "2025 Bahnbreitenberechnung RKRS V1.0.pdf";
		$setting= "RKRS";
		$graph="show";
	}

	if (isset($_GET["RKRSplus"])) {
		$CalcOptions["Feldsuche vorwärts"]["show"] = true;
		$CalcOptions["Flug seitwärts"]["show"] = true;
		$CalcOptions["Feldsuche seitwärts"]["show"] = true;
		$CalcOptions["Feld am Waldrand"]["show"] = true;
		$CalcOptions["Suche im Wald"]["show"] = true;
		$CalcOptions["Waldsuche seitwärts"]["show"] = false;
		$Infoplus= true;
		$logo = "rkrs";
		$trainingmaterial = "2025 Bahnbreitenberechnung RKRSplus V1.1.pdf";
		$copyright = "rehkitzrettung.ch";
		$setting= "RKRSplus";
		$graph="show";
	}
	if (isset($_GET["RKRSprev"])) {
		$CalcOptions["Feldsuche vorwärts"]["show"] = true;
		$CalcOptions["Flug seitwärts"]["show"] = true;
		$CalcOptions["Feldsuche seitwärts"]["show"] = true;
		$CalcOptions["Feld am Waldrand"]["show"] = true;
		$CalcOptions["Suche im Wald"]["show"] = true;
		$CalcOptions["Waldsuche seitwärts"]["show"] = false;
		$Infoplus= true;
		$logo = "rkrs";
		$trainingmaterial = "2025 Bahnbreitenberechnung RKRSplus V1.1.pdf";
		$copyright = "rehkitzrettung.ch";
		$setting= "RKRSpreview";
		$graph="show";
	}
	
	if ($onRKRS) {
		$logo = "rkrs";
	}
	
	//Write to LOGfile
	/**
	WriteToLog("get", basename($_SERVER['PHP_SELF']), "öffnen ", $setting ,$_GET["drone"],$_GET["calc"]);
	**/
	
	
	//Select Options
	$Drones[]= "";
	foreach ($DroneData as $key => $Drone) {
		if ($Drone["active"])
			$Drones[]=$key;
	}

	$Calcs[]= "";
	foreach ($CalcOptions as $key => $Calc) {
		if (($Calc["active"]) && ($Calc["show"]))
			$Calcs[]=$key;
	}
	
	$Hints["Hang"] = "In <b><a href='images/calc/20250303 Einfluss von coupiertem Gelände auf den Bahnabstand.mp4'><img src='images/calc/movie.png' height=15> Hanglagen</a></b> muss <br> a) Bei kleiner Steigung (bis 30%) die <b>Bahnbreite jeweils um die Hangsteilheit in % reduziert</b> werden (z.B. bei einem Hang von 30% Steilheit, wir die die Bahnbreite um 30% reduziert) <br> b) Bei mittlerer bis grosser Steigung (ab 30%) die <b>Bahnbreite jeweils um die Hälfte der Hangsteilheit in % reduziert</b> und der Kamerawinkel nachgestellt werden<br>... so wird die Überdeckung auch in steilem Gelände sichergestellt.</b>";
	$Hints["Wald"] = "Bei der Suche im <b>Wald</b> muss sichergestellt werden, dass Bäume von mindesten zwei Seiten abgesucht werden. Die kann:<br> a) bei <a href='images/calc/20250303 Blinde Flecken bei Suchflug vorwärts.mp4'><img src='images/calc/movie.png' height=15><b> Vorwärtsflug</b></a> durch eine <b>Überdeckung der Suchbahnen von über 50%</b> erreicht werden (Reduktion der Flugbahnbriete auf max. 50%).<br> b) bei <a href='images/calc/20250303 Blinde Flecken beim Suchflug seitwärts.mp4'><img src='images/calc/movie.png' height=15> <b>Seitwärtsflug</b></a> durch das Abfliegen der Fläche aus <b>zwei entgegengesetzen Seiten</b><br>.. erreich werden.";
	$Hints["VFW_Wald"] = "Der empfohlenen Vorwärtsflugwinkel bei der Waldsuche ist <b>40-45°</b>, bei der Suche im freien Feld eher <b>50-60°</b>.";
	$Hints["VFW_Waldrand"] = "Die empfohlenen Werte für eine Waldrandabsuche betragen:<br>Flughöhe <b>45 m</b>, Objektvwinkel <b>30°</b>, Abstand vom Waldrand <b> 10m</b> .";
	$Hints["VFW_ImWald"] = "Der empfohlenen Waldtyp oder Belaubungsgrad muss der Gimbalwinkel angepasst werden. <br>- Bei Nadelbäumen und hohem Belaubungsgrad ist ein höherer Winkel empfehlenswert: <b>60-70°</b>. <br>- Bei geringer Belaubung (zwischen November und Mai) eher ein kleinerer Winkel: <b>40-60°</b>." . "<br><br>" . $Hints["Hang"] . "<br><br>" . $Hints["Wald"];
	$Hints["FH_SHHP"] = "Die empfohlene Flughöhe für die Rehktizrettung beträgt zwischen <b>70 und 80</b> Meter.<br>Flughöhen oberhalb erhöhen das Risiko junge Rehktize (z.B. bei Nässe, hohem Gras) zu übersehen.";
	$Hints["FH_HHP"] = "Die empfohlene Flughöhe für die Rehktizrettung beträgt zwischen <b>70 und 80</b> Meter.<br>Flughöhen oberhalb erhöhen das Risiko junge Rehktize (z.B. bei Nässe, hohem Gras) zu übersehen.";
	$Hints["FH_HP"] = "Die empfohlene Flughöhe für die Rehktizrettung beträgt zwischen <b>50 und max 70</b> Meter.<br>Flughöhen unterhalb erhöhen die Gefahr für Kollisionen mit Objekten (Bäume, Telefonmasten).<br>Flughöhen oberhalb erhöhen das Risiko junge Rehktize (z.B. bei Nässe, hohem Gras) zu übersehen.";
	$Hints["FH_LP"] = "Die empfohlene Flughöhe für die Rehktizrettung beträgt <b>50m bis max. 60</b> Meter.<br>Flughöhen unterhalb erhöhen die Gefahr für Kollisionen mit Objekten (Bäume, Telefonmasten).<br> Flughöhen oberhalb erhöhen das Risiko junge Rehktize (z.B. bei Nässe, hohem Gras) zu übersehen.";
	$Hints["FH_LLP"] = "Die empfohlene Flughöhe für die Rehktizrettung beträgt <b>40m bis max. 50</b> Meter.<br>Flughöhen unterhalb 50m erhöhen die Gefahr für Kollisionen mit Objekten (Bäume, Telefonmasten).<br> Flughöhen oberhalb erhöhen das Risiko junge Rehktize (z.B. bei Nässe, hohem Gras) zu übersehen.";

	// ===========================================
	// Functions
	// ===========================================


function WriteTitleLine($TableData) {
	global $lgrey;

	// write title lines
	$output = "<tr><th witdh='300' style='background-color: ".$lgrey.";'>" . chr(10);
	$FieldNr = 1;
	foreach ($TableData as $key => $TableLine) {
		if ($TableLine["show"]) {
			if ($FieldNr>1)
				$output .= "</th><th style='background-color: ".$lgrey.";'>" . chr(10);
			$output .= $TableLine["title"] . chr(10);
		}
		$FieldNr++;
	}
	$output .= "<th></tr>" . chr(10);
	return $output;
}

function WriteTableLine($Tablekey, $TableData, $Line) {
	global  $set_drone, $Drones;
	// write tablelines
	$output="";
	$FieldNr = 1;
	foreach ($TableData as $key => $TableLine) {
		if (!$TableLine["js_exe"]) {
			if ($TableLine["show"]) {
				//standard line
				$output .= "</td><td id='".$Tablekey.$FieldNr."-".$Line."' style='text-align: center;'>". chr(10);
				$output .= "<span id='".$TableLine["key"].$Line."' name='".$TableLine["key"].$Line."'>-</span>" . chr(10);
			}
			$FieldNr++;
			//echo $FieldNr;
		}	
	}
	return $output;
}
 	
	// ===========================================
	// Inhalt
	// ===========================================
	
	//step 1
	$tablewidth=1000;
	echo "<table width='".$tablewidth."'>" . chr(10);
	//ROW1
	echo "<tr><td witdh='450' style='vertical-align: top;'>" . chr(10);
	if (($setting<>"RKRS") and ($setting<>"RKRSplus") and ($setting<>"RKRSprev"))  {
		echo "<H2>Bahnbreiten-Berechnung</H2>" . chr(10);
	}echo "</td witdh='400'><td>";
	echo "</td ><td style='vertical-align: top; text-align: right;'>";
	if ($logo=="rkrs") {
		echo "<img src='".$CalcIconLoc."rkrs_logo.png' id='logopic' name='logopic' width='247' height='80'><br/><br/>" . chr(10);
	} elseif ($logo=="rkrk") {
		echo "<img src='".$CalcIconLoc."rkrk_logo.png' id='logopic' name='logopic' height='80'><br/><br/>" . chr(10);
	} else {
		echo "<img src='".$CalcIconLoc."void.png' id='logopic' name='logopic' witdh='100'><br/><br/>" . chr(10);
	}	
	echo "</td></tr>" . chr(10);
	//ROW2
	echo "<tr><td style='vertical-align: top;'>" . chr(10);
	echo "<span id='drone' name='drone'><i>Wähle deinen Kamera/Drohnentyp aus:</i></span>  " . chr(10);
	echo SelectJSFunctionField("Drohne", $set_drone ,$Drones , "UpdDrone", "this.value", "1" ) . chr(10);
	echo "<br/><br/>";
	echo "<span id='dfov' name='dfov'></span>" . chr(10);
	echo "<span id='droneangle_H' name='droneangle_H'></span> " . chr(10);
	echo "<span id='droneangle_V' name='droneangle_V'></span>" . chr(10);
	echo "<span id='droneresolution' name='droneresolution'></span><br/>" . chr(10);
	echo "<span id='dalos' name='dalos'></span><br/>" . chr(10);
	echo "<span  id='dronelist' name='dronelist' ></span>  ";
	echo "</td><td style='vertical-align: top;' >";
	echo "<img src='".$CalcIconLoc."void.png' id='dronepic' name='dronepic' height='150'><br/><br/>" . chr(10);
	echo "</td><td witdh='100' rowspan=2>";
	echo "<img src='".$CalcIconLoc."void.png' id='calcpic' name='calcpic' height='320'><br/><br/>" . chr(10);
	echo "</td></tr>";
	//ROW3
	echo "<tr><td colspan=2>";
	//echo "<canvas id='flightsetup' width='".$drawwidth."' height='".$drawheight."' style='border:1px solid #BBBBBB;display:none' ></canvas>";
	echo "<canvas ondblclick='ChangeFocus()' id='flightsetup' width='".$drawwidth."' height='".$drawheight."' style='display:none' ></canvas>";
	echo "</td></tr>";
	//ROW4
	echo "<tr><td colspan=3>";
	echo "<canvas ondblclick='ChangeFocus()' id='flightsetupbig' width='".$drawwidth."' height='".$drawheight."' style='display:none' ></canvas>";
	echo "</td></tr>";
	echo "</table>" . chr(10);
	//step 2.1
	echo "<table width='".$tablewidth."'>" . chr(10);
	echo "<tr><td witdh='500' style='vertical-align: top;'>";
	echo "<span id='step2' name='step2'  style='display: none;'>";
	echo "<span id='calc' name='calc'><i>Wähle aus, was du berechnen möchtest:</i></span>  ";
	echo SelectJSFunctionField("Berechnung", $set_calc ,$Calcs , "UpdCalc", "this.value", "1" );
	echo "<br/><br/>";
	//step 3
	foreach ($CalcOptions as $key => $Calc) {
		if (($Calc["show"]) and ($Calc["active"])) {
			echo "<span id='step_".$Calc["key"]."' name='step_".$Calc["key"]."'  style='display: none'>" . chr(10);
			//echo "<i>Bahnberechnung</i>";
			echo "<table>" . chr(10);
			echo WriteTitleLine($PrefParameters[$Calc["key"]]["table"]);

			//Line1
			$Line = 1;
			//echo "BQ" .$Calc["key"];
			$DefType = "std";
			switch ($Calc["key"]) {
			  case  "fh":
				foreach ($DefaultSettings[$DefType][$Calc["key"]]["flightheights"] as $fheight) {
					echo "<tr><td>" . chr(10);
					//if ($Line==3) { 
					if ($Line==$LineDefaults[$Calc["key"]])
					{ 
						echo JSFunctionMoveOverField("draw-".$Line, "<img src='".$CalcIconLoc."dot_full.png' height=20>", "UpdateDrawing", $Line);
						$Input_Values[$Calc["key"]]["graphshown"]=$Line;
					} else {
						echo JSFunctionMoveOverField("draw-".$Line, "<img src='".$CalcIconLoc."dot_empty.png' height=20>", "UpdateDrawing", $Line);
					}
					$Input_Values[$Calc["key"]][$Line]["flightheights"]=$fheight;
					//echo "Flightheight: " . $fheight . "<br>";
					echo "</td><td>";
					echo SelectJSFunctionField("flightheight-".$Line, $fheight ,$PrefParameters[$Calc["key"]]["flightheights"] , "CalcFieldSearchForwards ", $Line, $Line );
					echo WriteTableLine($Calc["key"], $PrefParameters[$Calc["key"]]["table"], $Line);
					echo "<td></tr>";
					++$Line;
				}
				break;
			  case  "fhq":
				foreach ($DefaultSettings[$DefType][$Calc["key"]]["flightheights"] as $fheight) {
					echo "<tr><td>" . chr(10);
					//if ($Line==3) { 
					if ($Line==$LineDefaults[$Calc["key"]]) 
					{ 
						echo JSFunctionMoveOverField("draw_fhq-".$Line, "<img src='".$CalcIconLoc."dot_full.png' height=20>", "UpdateDrawing", $Line);
						$Input_Values[$Calc["key"]]["graphshown"]=$Line;
					} else {
						echo JSFunctionMoveOverField("draw_fhq-".$Line, "<img src='".$CalcIconLoc."dot_empty.png' height=20>", "UpdateDrawing", $Line);
					}
					$Input_Values[$Calc["key"]][$Line]["flightheights"]=$fheight;
					// Flughöhe
					echo "</td><td>";
					echo SelectJSFunctionField("flightheight_fhq-".$Line, $fheight ,$PrefParameters[$Calc["key"]]["flightheights"] , "CalcFlightSidewards", $Line, $Line );
					echo WriteTableLine($Calc["key"], $PrefParameters[$Calc["key"]]["table"], $Line);
					echo "<td></tr>";
					++$Line;
				}
				break;  
			  case  "bq":
				foreach ($DefaultSettings[$DefType][$Calc["key"]]["flightheights"] as $fheight) {
					foreach ($DefaultSettings[$DefType][$Calc["key"]]["camangle"] as $oangle) {
						echo "<tr><td>" . chr(10);
						//if ($Line==3) { 
						if ($Line==$LineDefaults[$Calc["key"]]) 
						{ 
							echo JSFunctionMoveOverField("draw_bq-".$Line, "<img src='".$CalcIconLoc."dot_full.png' height=20>", "UpdateDrawing", $Line);
							$Input_Values[$Calc["key"]]["graphshown"]=$Line;
						} else {
							echo JSFunctionMoveOverField("draw_bq-".$Line, "<img src='".$CalcIconLoc."dot_empty.png' height=20>", "UpdateDrawing", $Line);
						}
						$Input_Values[$Calc["key"]][$Line]["flightheights"]=$fheight;
						$Input_Values[$Calc["key"]][$Line]["camangle"]=$oangle;
						// Flughöhe
						echo "</td><td>";
						echo SelectJSFunctionField("flightheight_bq-".$Line, $fheight ,$PrefParameters[$Calc["key"]]["flightheights"] , "CalcFieldSearchSidewards",  $Line, '"fh"');
						// Kamerawinkel [°]";
						echo "</td><td>";
						echo SelectJSFunctionField("oangle_bq-".$Line, $oangle ,$PrefParameters[$Calc["key"]]["camangle"] , "CalcFieldSearchSidewards", $Line, '"kw"' );
						echo WriteTableLine($Calc["key"], $PrefParameters[$Calc["key"]]["table"], $Line);
						echo "<td></tr>";
						++$Line;
					}
				}
				break;  
			  case  "aw":
				foreach ($DefaultSettings[$DefType][$Calc["key"]]["flightheights"] as $fheight) {
					foreach ($DefaultSettings[$DefType][$Calc["key"]]["camangle"] as $oangle) {
						echo "<tr><td>" . chr(10);
						//if ($Line==5) { 
						if ($Line==$LineDefaults[$Calc["key"]])
						{ 
							echo JSFunctionMoveOverField("draw_aw-".$Line, "<img src='".$CalcIconLoc."dot_full.png' height=20>", "UpdateDrawing", $Line);
							$Input_Values[$Calc["key"]]["graphshown"]=$Line;
						} else {
							echo JSFunctionMoveOverField("draw_aw-".$Line, "<img src='".$CalcIconLoc."dot_empty.png' height=20>", "UpdateDrawing", $Line);
						}
						$Input_Values[$Calc["key"]][$Line]["flightheights"]=$fheight;
						$Input_Values[$Calc["key"]][$Line]["camangle"]=$oangle;
						// Flughöhe  
						echo "</td><td>";
						echo SelectJSFunctionField("flightheight_aw-".$Line, $fheight ,$PrefParameters[$Calc["key"]]["flightheights"] , "CalcFieldSearchNearForest",  $Line , '"fh"');
						// Kamerawinkel [°]";
						echo "</td><td>";
						echo SelectJSFunctionField("oangle_aw-".$Line, $oangle ,$PrefParameters[$Calc["key"]]["camangle"] , "CalcFieldSearchNearForest", $Line , '"kw"' );
						echo WriteTableLine($Calc["key"], $PrefParameters[$Calc["key"]]["table"], $Line);
						echo "<td></tr>";
						++$Line;
					}
				}
				break;  
			  case  "wr":  
				foreach ($DefaultSettings[$DefType][$Calc["key"]]["flightheights"] as $fheight) {
					foreach ($DefaultSettings[$DefType][$Calc["key"]]["camangle"] as $oangle) {
						foreach ($DefaultSettings[$DefType][$Calc["key"]]["searchdist"] as $sdist) {
							echo "<tr><td>" . chr(10);
							//if ($Line==6) { 
							if ($Line==$LineDefaults[$Calc["key"]])  
							{ 
								echo JSFunctionMoveOverField("draw_wr-".$Line, "<img src='".$CalcIconLoc."dot_full.png' height=20>", "UpdateDrawing", $Line);
								$Input_Values[$Calc["key"]]["graphshown"]=$Line;
							} else {
								echo JSFunctionMoveOverField("draw_wr-".$Line, "<img src='".$CalcIconLoc."dot_empty.png' height=20>", "UpdateDrawing", $Line);
							}
							$Input_Values[$Calc["key"]][$Line]["flightheights"]=$fheight;
							$Input_Values[$Calc["key"]][$Line]["camangle"]=$oangle;
							$Input_Values[$Calc["key"]][$Line]["searchdist"]=$sdist;
							// Flughöhe  
							echo "</td><td>";
							echo SelectJSFunctionField("flightheight_wr-".$Line, $fheight ,$PrefParameters[$Calc["key"]]["flightheights"] , "CalcSearchInForest", $Line , '"fh"' );
							// Objektivwinkel [°]";
							echo "</td><td>";
							echo SelectJSFunctionField("oangle_wr-".$Line, $oangle ,$PrefParameters[$Calc["key"]]["camangle"] , "CalcSearchInForest", $Line , '"ow"' );
							// Äussere Suchdistanz [m]";
							echo "</td><td>";
							echo SelectJSFunctionField("sdist_wr-".$Line, $sdist ,$PrefParameters[$Calc["key"]]["searchdist"] , "CalcSearchInForest", $Line , '"sd"' );
							echo WriteTableLine($Calc["key"], $PrefParameters[$Calc["key"]]["table"], $Line);
							echo "<td></tr>";
							++$Line;
						}
					}
				}
				break;  
			  case  "ws":  
				foreach ($DefaultSettings[$DefType][$Calc["key"]]["flightheights"] as $fheight) {
					foreach ($DefaultSettings[$DefType][$Calc["key"]]["camangle"] as $oangle) {
						foreach ($DefaultSettings[$DefType][$Calc["key"]]["treeheight"] as $theight) {
							echo "<tr><td>" . chr(10);
							//if ($Line==8) { 
							if ($Line==$LineDefaults[$Calc["key"]])  
							{ 
								echo JSFunctionMoveOverField("draw_ws-".$Line, "<img src='".$CalcIconLoc."dot_full.png' height=20>", "UpdateDrawing", $Line);
								$Input_Values[$Calc["key"]]["graphshown"]=$Line;
							} else {
								echo JSFunctionMoveOverField("draw_ws-".$Line, "<img src='".$CalcIconLoc."dot_empty.png' height=20>", "UpdateDrawing", $Line);
							}
							$Input_Values[$Calc["key"]][$Line]["flightheights"]=$fheight;
							$Input_Values[$Calc["key"]][$Line]["camangle"]=$oangle;
							$Input_Values[$Calc["key"]][$Line]["treeheight"]=$theight;
							// Flughöhe  
							echo "</td><td>";
							echo SelectJSFunctionField("flightheight_ws-".$Line, $fheight ,$PrefParameters[$Calc["key"]]["flightheights"] , "CalcForestSearchSidewards", $Line , '"fh"' );
							// Objektivwinkel [°]";
							echo "</td><td>";
							echo SelectJSFunctionField("oangle_ws-".$Line, $oangle ,$PrefParameters[$Calc["key"]]["camangle"] , "CalcForestSearchSidewards", $Line , '"ow"' );
							// Baumhöhe [m]";
							echo "</td><td>";
							echo SelectJSFunctionField("treeheight_ws-".$Line, $theight ,$PrefParameters[$Calc["key"]]["treeheight"] , "CalcForestSearchSidewards", $Line , '"th"' );
							echo WriteTableLine($Calc["key"], $PrefParameters[$Calc["key"]]["table"], $Line);
							echo "<td></tr>";
							++$Line;
						}
					}
				}
				break;
			}	
			echo "</table>" . chr(10);
			echo "</span>  ";
		}
	}	
	//end step 3
	
	//step 2.2
	//echo "</td><td>";
	//echo "<img src='".$CalcIconLoc."void.png' id='calcpic' name='calcpic' height='400'><br/><br/>" . chr(10);
	echo "</td></tr>";
	echo "</table>";
	echo "</span>  ";
	//step 2 end
	echo "<br>";
	echo "<table width='".$tablewidth."'>" . chr(10);
	echo "</td></tr>" . chr(10);
	echo "<tr><td colspan=2>" . chr(10);
	echo "<div  id='info' name='info' style='background-color: #fef9e7;'></div>  ";
	echo "</td></tr>" . chr(10);
	echo "<tr><td colspan=2>" . chr(10);
	echo "<div  id='hint' name='hint' style='background-color: #e9f7ef;'></div>  ";
	echo "</td></tr>" . chr(10);
	echo "<tr><td style='vertical-align:top'>" . chr(10);
	echo "<div  id='video' name='video' align='center'></div>  ";
	echo "</td><td style='width: 100px; vertical-align:top; text-align:center'>" . chr(10);
	if ($trainingmaterial!="") {
		echo "<h4>Trainingsmaterial</h4>";
		//echo "<a href='doc/2025 Bahnbreitenberechnung V1.0.pdf'><img src='".$CalcIconLoc."powerpoint.webp' height='60'><br><font size='1'>Bahnbreitenberechnug V1.0</font></a>". chr(10);
		echo "<a href='doc/".$trainingmaterial."'><img src='".$CalcIconLoc."powerpoint.webp' height='60'><br><font size='1'>".$trainingmaterial."</font></a>". chr(10);
	}
	echo "</td></tr>" . chr(10);
	echo "<tr><td colspan=2>" . chr(10);
	echo "<div  id='footnote' name='footnote'></div>  ";
	echo "</td></tr>" . chr(10);
	echo "<tr><td colspan=2>" . chr(10);
	echo "<div align='center'> <i>© " . date("Y") ." by ".$copyright."</i></div>" . chr(10);
	echo "<div align='center' style='font-size:10'><a href='mailto:thomas.christen@drohnenrehkitzrettung.ch?subject=Rückmeldung Flugpfadberechnung'>Rückmeldungen</i></a></div>" . chr(10);
	echo "</td></tr>" . chr(10);
	echo "</table>" . chr(10);
	echo "<div  id='debug' name='debug' style='background-color: #f7e9e9;'></div>  ";
	echo "<div  id='debug1' name='debug1' style='background-color: #f7e9e9;'></div>  ";
	


?>
<script type='text/javascript'>
<?php
//echo json_encode($DroneData);
echo "var PrefParameters = ". json_encode($PrefParameters) . ";\n"; 
echo "var DefaultSettings = ". json_encode($DefaultSettings) . ";\n"; 
echo "var FlightSettings = ". json_encode($FlightSettings) . ";\n"; 
echo "var LineDefaults = ". json_encode($LineDefaults) . ";\n"; 
echo "var Hints = ". json_encode($Hints) . ";\n"; 
echo "var DroneData = ". json_encode($DroneData) . ";\n";
echo "var CalcData = ". json_encode($CalcOptions) . ";\n";
echo "var Input_Values = ". json_encode($Input_Values) . ";\n";
echo "var calciconloc = '". $CalcIconLoc . "';\n";
echo "var setting = '". $setting . "';\n";
echo "var SelectedDrone = '". $set_drone. "';\n";
echo "var SelectedCalc = '". $set_calc. "';\n";
echo "var HAngle = '';\n";
echo "var VAngle = '';\n";
echo "var graph = '".($graph ?? '')."';\n";
echo "var drawwidth = '".$drawwidth."';\n";
echo "var drawheight = '".$drawheight."';\n";
echo "var infoplus = '".($Infoplus ?? '')."';\n";

?>

// table colors
	var lgreen = "#e9f7ef";
	var lred = "#fdedec";
	var lyellow = "#fef9e7";
	var draw=[];
	var flighsetupzoom="s";
	var flighsetup = "flightsetup";


// Draw colors
	blue = '#0000FF';
	red = '#FF0000';
	lgrey = '#DDDDDD';
	grey = '#BBBBBB';
	dgrey = '#333333';
	black = '#000000';
	green = '#008000';
	dgreen = '#006400';
	orange = '#FF7F50';
	dorange = '#ff6b50';
	violet = '#7F00FF';
	brown = '#895129';

function round(number, form) {
	if (form=="up") {
		offset=0.5;
	} else if (form=="down") {
		offset=-0.5;
	} else {
		offset=0;
	}

	if (number>100) {
		round_factor=10;
	} else if (number>40) {
		round_factor=5;
	} else if (number>22) {
		round_factor=2;
	} else {
		round_factor=1;
	}
	return Math.round((number/round_factor)+offset)*round_factor;
}

// Functions
// =========



function ChangeFocus() {    
	//alert("change focus: "+flighsetupzoom);

	var canvas = "";  
    if (flighsetupzoom=="s") {
		//make big
		flighsetup="flightsetupbig";
		document.getElementById("flightsetup").style.display= 'none';
		document.getElementById("flightsetupbig").style.display= 'block';

		canvas = document.getElementById(flighsetup);  
		canvas.width = 1.5*drawwidth;
		canvas.height = 1.5*drawheight;
		document.getElementById("calcpic").style.display= 'none';
		//document.getElementById("dronepic").style.display= 'none';
		flighsetupzoom="b";
	} else {
		//make small
		flighsetup="flightsetup";
		document.getElementById("flightsetup").style.display= 'block';
		document.getElementById("flightsetupbig").style.display= 'none';

		canvas = document.getElementById(flighsetup);  
		canvas.width = drawwidth;
		canvas.height = drawheight;
		document.getElementById("calcpic").style.display= 'block';
		//document.getElementById("dronepic").style.display= 'block';
		flighsetupzoom="s";
	}
	update_drawing() ;
}

function selectElement(id, valueToSelect) {    
    let element = document.getElementById(id);
    element.value = valueToSelect;
}

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

function UpdDrone(drone, line) {
  //alert("Drohne geändert auf: " + drone);
  document.getElementById("drone").innerHTML = "<b>"+DroneData[drone]["Name"]+"</b>";
  document["dronepic"].src = calciconloc+DroneData[drone]["img"];
  document.getElementById("step2").style.display= 'block';
  HAngle=DroneData[drone]["IRCamAngle_H"];
  VAngle=DroneData[drone]["IRCamAngle_V"];
	if (DroneData[drone]["DFOV"]==0) {
		document.getElementById("dfov").style.display= 'none';
	} else {
		document.getElementById("dfov").style.display= 'block';
		document.getElementById("dfov").innerHTML = "Diagonal Field of View (DFOV): <b>"+DroneData[drone]["DFOV"]+"</b>°";
	}
  Resolution=DroneData[drone]["IRCamResolution_H"] + "x" + DroneData[drone]["IRCamResolution_V"] ;
  if (DroneData[drone]["CD_CharacteristicalDistance"]>0) {
	DalosDistance=round(DroneData[drone]["CD_CharacteristicalDistance"]*327+20);
  } else {
	DalosDistance=0;
  }
  document.getElementById("droneangle_H").innerHTML = "Winkel Objektiv (horziontal): <b>"+DroneData[drone]["IRCamAngle_H"]+"</b>°";
	if (DroneData[drone]["IRCamAngle_V"]==0) {
		document.getElementById("droneangle_V").style.display= 'none';
	} else {
		document.getElementById("droneangle_V").style.display= 'block';
		document.getElementById("droneangle_V").innerHTML = "Winkel Objektiv (vertikal): <b>"+DroneData[drone]["IRCamAngle_V"]+"</b>°";
	}
	document.getElementById("droneresolution").innerHTML = "Auflösung Wärmebildkamera: <b>"+Resolution+"</b> px";
	if (DalosDistance!=0) {
		document.getElementById("dalos").innerHTML = "ALOS (Attitude Line of Sight): <b>"+DalosDistance+"</b> m";
	} else {
		document.getElementById("dalos").innerHTML = "";
	}
	
  SelectedDrone = drone; 
  document.getElementById("dronelist").innerHTML= "";

  UpdCalc(SelectedCalc, line);
}

function CalcFieldSearchForwards (line, dontuse) {
	//alert("Berechne Feldsuche vorwärts/ fh: Zeile: " + line + " Höhe: " + height);
	height = document.getElementById("flightheight-"+line).options[document.getElementById("flightheight-"+line).selectedIndex].text;
	
	FieldSearchForwards (height, line);
	UpdateDrawing(line);
}

function FieldSearchForwards (height, line) {
	//alert("Berechne Feldsuche vorwärts/ fh: Höhe = " + height + ", Zeile: " + line);

	//update inputs values
	Input_Values["fh"][line]["flightheights"]=height;
	
	//Inputs
	fh = parseInt(height);							// Flughöhe
	oa = DroneData[SelectedDrone]["IRCamAngle_H"];  //Objektivwinkel
	
	//04.08.2025 Korrektur Überlapuuntswinkel bei grossen Höhen
	//k_fh=60;
	//if (fh>k_fh) {
	//	kf=80;
	//	k_ulw=0.15/(1+((fh-k_fh)/kf));
	//} else {
	//	k_ulw=0.15;
	//}	
	//ua = oa*k_ulw;  									// Überlappungswinkel

	//19.11.2025 Korrektur Überlapuuntswinkel bei grossen Höhen
	formula='new';
	
	if(formula=='old') {
		k_fh=60;
		kf=80;
	} else {
		k_fh=50;
		kf=65;
	}
	if (fh>k_fh) {
		k_ulw=0.15/(1+((fh-k_fh)/kf));
	} else {
		k_ulw=0.15;
	}	
	ua = oa*k_ulw;  									// Überlappungswinkel
	
	//old 30.01.2025
	//bf_t = 2.0;										// Bahnfaktor tief
	//bf_h = 2.5;										// Bahnfaktor hoch
	//bf_m = (bf_t+bf_h)/2;							// Bahnfaktor mitte
	//rf_t = 1.5;										// Randfaktor tief
	//rf_h = 2.0;										// Randfaktor hoch
	//new 30.01.2025
	bf_t = 1.0;										// Bahnfaktor tief
	bf_h = 1.3;										// Bahnfaktor hoch
	rf_t = 0.4;										// Randfaktor tief
	rf_h = 0.5;										// Randfaktor hoch

	sib = 2*Math.tan((oa/2)*Math.PI/180)*fh;		//Sichtbreite mit Überlappung [m
	sub = 2*Math.tan((oa/2-ua)*Math.PI/180)*fh;		//Suchbreite ohne Überlappung [m]
	ul = (Math.tan((oa/2)*Math.PI/180)-Math.tan((oa/2-ua)*Math.PI/180))*fh; //Überlappung [m]

	if(formula=='old') {
		//new 30.01.2025
		bbuav = round(sib-(bf_t+bf_h)*(ul+ul)/2,"down");				// Bahnbreite UAV
	} else {
		//new 19.11.2025
		bbuav = round(2*fh*Math.tan((oa/2-ua)*Math.PI/180),"down");				// Bahnbreite UAV
	}
	
	//bbuav = (2*Math.tan((oa/2)*Math.PI/180)*fh-(bf_t+bf_h)*(2*(Math.tan((oa/2)*Math.PI/180)-Math.tan((oa/2-ua)*Math.PI/180))*fh)/2);
	
	bb = round(sib-bf_h*(ul+ul),"down") + " - " + round(sib-bf_t*(ul+ul),"down");	// Empfohlene Bahnbreite
	rauav = round(bbuav*(rf_t+rf_h)/2,"down");		 					// Randabstand UAV
	ra = round(bbuav*rf_t,"down") + " - " + round(bbuav*rf_h,"down"); 	// Randabstand

	//Resultate ausgeben
	//Sichtbreite [m]
	document.getElementById("SBmU_m_"+line).innerHTML=round(sib);
	//Überlappung[m]
	document.getElementById("SBpS_m_"+line).innerHTML=round(ul,"");
	//Suchbreite ohne Überlappung[m]
	document.getElementById("SuBmU_m_"+line).innerHTML=round(sub,"");
	//Abstand der Flubahn [m]
	document.getElementById("EFbB_m_"+line).innerHTML=bb;
	//Empfohlener Randabstand [m]
	document.getElementById("ERA_m_"+line).innerHTML=ra;
	//UAV Bahnbreite [m]
	document.getElementById("UBB_m_"+line).innerHTML="<b>"+bbuav+"</b>";
	//UAV Randabstand[m]
	document.getElementById("URA_m_"+line).innerHTML="<b>"+rauav+"</b>";


	if (FlightSettings[DroneData[SelectedDrone]["RecomType"]]["recommendations"]["fh"]["flightheight"].includes(parseInt(height)))
	{
		ColorLine("fh", line, lgreen);
	} else {
		ColorLine("fh", line, "");
	}	

	if (FlightSettings[DroneData[SelectedDrone]["RecomType"]]["hint"]!="") {
		document.getElementById("hint").innerHTML= Hints[FlightSettings[DroneData[SelectedDrone]["RecomType"]]["hint"]];
	} else {
		document.getElementById("hint").innerHTML= "";
	}

	if (setting== "TEST") {
		document.getElementById("debug").innerHTML = "fh: Drohne: " + SelectedDrone + " RecomType: " + DroneData[SelectedDrone]["RecomType"] + " rec heights " + FlightSettings[DroneData[SelectedDrone]["RecomType"]]["recommendations"]["fh"]["flightheight"].toString();
	}
	
	var drawdata=[];
	drawdata[0]= "Feldsuche vorwärts"; //Configuration
	drawdata[1]= fh; //flighheight
	drawdata[2]= oa; //objective angle
	drawdata[3]= 90; //camera angle
	drawdata[4]= k_ulw; //overlapp
	drawdata[5]= Math.round(bbuav); //trackdistance

	if ((graph=="show") && (line==LineDefaults["fh"])) {
		draw = drawdata;
		//draw[0]= "Feldsuche vorwärts"; //Configuration
		//draw[1]= fh; //flighheight
		//draw[2]= oa; //objective angle
		//draw[3]= 90; //camera angle
		//draw[4]= 0.15; //overlapp
		//draw[5]= Math.round(bbuav); //trackdistance
		//update_drawing();
	}

	return drawdata;
}

function CalcFlightSidewards (line, dontuse) {
	height = document.getElementById("flightheight_fhq-"+line).options[document.getElementById("flightheight_fhq-"+line).selectedIndex].text;
	//alert("Berechne Flug seitwärts/ fhq: Zeile: " + line + " Höhe: " + height);
	
	FlightSidewards (height, line);
	UpdateDrawing(line);
}

function FlightSidewards (height, line ) {
	//alert("Berechne Flug seitwärts/ fhq, Höhe = " + height + ", Zeile: " + line);

	//update inputs values
	Input_Values["fhq"][line]["flightheights"]=height;

	//Inputs
	fh = parseInt(height);							// Flughöhe
	oa = DroneData[SelectedDrone]["IRCamAngle_V"];  //Objektivwinkel
	ua = oa*0.15;  									// Überlappungswinkel
	//new 30.01.2025
	bf_t = 1.0;										// Bahnfaktor tief
	bf_h = 1.3;										// Bahnfaktor hoch
	rf_t = 0.4;										// Randfaktor tief
	rf_h = 0.5;										// Randfaktor hoch

	sib = 2*Math.tan((oa/2)*Math.PI/180)*fh;		//Sichtbreite mit Überlappung [m
	sub = 2*Math.tan((oa/2-ua)*Math.PI/180)*fh;		//Suchbreite ohne Überlappung [m]
	ul = (Math.tan((oa/2)*Math.PI/180)-Math.tan((oa/2-ua)*Math.PI/180))*fh; //Überlappung [m]

	//new 30.01.2025
	bbuav = round(sib-(bf_t+bf_h)*(ul+ul)/2,"down");				// Bahnbreite UAV
	bb = round(sib-bf_h*(ul+ul),"down") + " - " + round(sib-bf_t*(ul+ul),"down");	// Empfohlene Bahnbreite
	rauav = round(bbuav*(rf_t+rf_h)/2,"down");		 					// Randabstand UAV
	ra = round(bbuav*rf_t,"down") + " - " + round(bbuav*rf_h,"down"); 	// Randabstand

	//Resultate ausgeben
	//Sichtbreite [m]
	document.getElementById("SBmU_q_m_"+line).innerHTML=round(sib);
	//Überlappung[m]
	document.getElementById("SBpS_q_m_"+line).innerHTML=round(ul,"");
	//Suchbreite ohne Überlappung[m]
	document.getElementById("SuBmU_q_m_"+line).innerHTML=round(sub,"");
	//Abstand der Flubahn [m]
	document.getElementById("EFbB_q_m_"+line).innerHTML=bb;
	//Empfohlener Randabstand [m]
	document.getElementById("ERA_q_m_"+line).innerHTML=ra;
	//UAV Bahnbreite [m]
	document.getElementById("UBB_q_m_"+line).innerHTML="<b>"+bbuav+"</b>";
	//UAV Randabstand[m]
	document.getElementById("URA_q_m_"+line).innerHTML="<b>"+rauav+"</b>";


	if (FlightSettings[DroneData[SelectedDrone]["RecomType"]]["recommendations"]["fhq"]["flightheight"].includes(parseInt(height)))
	{
	   ColorLine("fhq", line, lgreen);
	} else {
	   ColorLine("fhq", line, "");
	}

	if (FlightSettings[DroneData[SelectedDrone]["RecomType"]]["hint"]!="") {
		document.getElementById("hint").innerHTML= Hints[FlightSettings[DroneData[SelectedDrone]["RecomType"]]["hint"]];
	} else {
		document.getElementById("hint").innerHTML= "";
	}

	if (setting== "TEST") {
		document.getElementById("debug").innerHTML = "fhq: Drohne: " + SelectedDrone + " RecomType: " + DroneData[SelectedDrone]["RecomType"] + " rec heights " + FlightSettings[DroneData[SelectedDrone]["RecomType"]]["recommendations"]["fhq"]["flightheight"].toString();
	}

	var drawdata=[];
	drawdata[0]= "Flug seitwärts"; //Configuration
	drawdata[1]= fh; //flighheight
	drawdata[2]= oa; //objective angle
	drawdata[3]= 90; //camera angle
	drawdata[4]= 0.15; //overlapp
	drawdata[5]= bbuav; //trackdistance

	if ((graph=="show") && (line==LineDefaults["fhq"])) {
		draw=drawdata;
		//draw[0]= "Flug seitwärts"; //Configuration
		//draw[1]= fh; //flighheight
		//draw[2]= oa; //objective angle
		//draw[3]= 90; //camera angle
		//draw[4]= 0.15; //overlapp
		//draw[5]= bbuav; //trackdistance
	}
	return drawdata;
}			

function CalcFieldSearchSidewards (line, feld) {
	height = document.getElementById("flightheight_bq-"+line).options[document.getElementById("flightheight_bq-"+line).selectedIndex].text;
	angle = document.getElementById("oangle_bq-"+line).options[document.getElementById("oangle_bq-"+line).selectedIndex].text;
	//alert("Berechne Feldsuche seitwärts/ bq: Zeile: " + line + " Höhe: " + height + " Winkel: " + angle);
	Input_Values["bq"][line]["flightheights"]=height;
	Input_Values["bq"][line]["camangle"]=angle;
	
	FieldSearchSidewards (height, angle, feld, line);
	UpdateDrawing(line, feld);
}

function FieldSearchSidewards (height, angle, feld, line) {
	//alert("Berechne Feldsuche seitwärts/ bq: Höhe = " + height + " Kamerawinkel = " + angle + ", Zeile: " + line);

	//update inputs values
	//Input_Values["bq"][line]["flightheights"]=height;
	//Input_Values["bq"][line]["camangle"]=angle;

	//Inputs
	fh = parseInt(height);							// Flughöhe
	aa = parseInt(angle);							// Gimbal-/Kamerawinkel
	oa = DroneData[SelectedDrone]["IRCamAngle_V"];  //Objektivwinkel
	ua = oa*0.15;  									// Überlappungswinkel
	submax = 70;									// Bahnbreitengrenze
	submaxf = 0.2;									// Bahnbreitengrenzen Faktor
	bb_t = 0.9;										// Bahnbreitenfaktor tief
	bb_h = 1.0;										// Bahnbreitenfaktor hoch

	fo = Math.tan((90-aa-oa/2+ua)*Math.PI/180)*fh;				//Feldoffset [m]
	sifo = Math.tan((90-aa-oa/2)*Math.PI/180)*fh;				//Sichfeldoffset [m]
	sib = Math.tan((90-aa+oa/2)*Math.PI/180)*fh-sifo;			//Sichtbreite (mit Überlappung) [m]
	sub = (Math.tan((90-aa+oa/2-ua)*Math.PI/180)-Math.tan((90-aa-oa/2+ua)*Math.PI/180))*fh;	//Suchbreite (ohne Überlappung
	//Suchbreite (ohne Überlappung
	ulv = (Math.tan((90-aa+oa/2)*Math.PI/180)-Math.tan((90-aa+oa/2-ua)*Math.PI/180))*fh;	//Überlappung vorne
	ulh = (Math.tan((90-aa-oa/2+ua)*Math.PI/180)-Math.tan((90-aa-oa/2)*Math.PI/180))*fh;	//Überlappung hinten
	fbo = Math.tan((90-aa)*Math.PI/180)*fh;						//Offset zum Mittelpunkt [m]
	mid = fh/Math.cos((90-aa)*Math.PI/180);						// Mittlere Distanz [m]
	//mid = Math.sqrt(fh*fh+fbo*fbo);							// Mittlere Distanz [m] (alternativ)
	mad = fh/Math.cos((90-aa+oa/2)*Math.PI/180);				// Maximale Distanz [m]
	//mad = Math.sqrt(fh*fh+(sifo+sib)*(sifo+sib));				// Maximale Distanz [m] (alternativ)
	if (sub>submax) {
		bbuav = ((sub-submax)*submaxf)+submax;						// UAV Empfohlener Bahnabstand [m] > submax
	} else {
		bbuav = sub;											// UAV Empfohlener Bahnabstand [m] <= submax
    }
	bb = round(bbuav*bb_t,"down") + " - " + round(bbuav*bb_h,"down");	// Empfohlene Bahnbreite

	fbouav = fbo;	// UAV Flugpfadoffset [m]
	rauav = 1.5*ulh;	// UAV Randabstand [m]
			
	//Resultate ausgeben
	//Sichtbreite (mit Überlappung) [m]
	document.getElementById("SBmU_bq_"+line).innerHTML=round(sib,"");

	//Suchbreite (ohne Überlappung) [m]
	document.getElementById("SuBmU_bq_"+line).innerHTML=round(sub,"");
	
	//Überlappung [m]
	document.getElementById("UeAS_bq_"+line).innerHTML="V:" + round(ulv,"") + " H:" + round(ulh,"");

	// Feldoffset [m]
	document.getElementById("FO_bq_"+line).innerHTML="<b>"+round(fo,"")+"</b>";

	// Offset zum Mittelpunkt [m]
	document.getElementById("OMP_bq_"+line).innerHTML=round(fbo,"");

	// Mittlere Distanz [m]
	document.getElementById("MiD_bq_"+line).innerHTML=round(mid,"");

	// Maximale Distanz [m]
	document.getElementById("MaD_bq_"+line).innerHTML=round(mad,"");

	// UAV Empfohlener Bahnabstand [m]
	document.getElementById("EFBA_bq_"+line).innerHTML=bb;

	// UAV Bahnabstand [m]
	document.getElementById("FBA_bq_"+line).innerHTML="<b>"+round(bbuav,"down")+"</b>";

	// UAV Flugpfadoffset [m]
	document.getElementById("FPof_bq_"+line).innerHTML="<b>"+round(fbouav,"down")+"</b>";
	
	// UAV Randabstand [m]
	//31.10.2025 Keinen Randabstand für Winkelflüge								
	//document.getElementById("URA_bq_"+line).innerHTML="<b>"+round(rauav,"down")+"</b>";


	if ((FlightSettings[DroneData[SelectedDrone]["RecomType"]]["recommendations"]["bq"]["flightheight"].includes(parseInt(height))) &&
		(FlightSettings[DroneData[SelectedDrone]["RecomType"]]["recommendations"]["bq"]["angle"].includes(parseInt(angle))))
	{
	   ColorLine("bq", line, lgreen);
	} else {
	   ColorLine("bq", line, "");
	}
	document.getElementById("hint").innerHTML= Hints["VFW_Quer"];

	if (setting== "TEST") {
		document.getElementById("debug").innerHTML = "bq: Drohne: " + SelectedDrone + " RecomType: " + DroneData[SelectedDrone]["RecomType"] + " rec heights " + FlightSettings[DroneData[SelectedDrone]["RecomType"]]["recommendations"]["bq"]["flightheight"].toString() + " rec angle " + FlightSettings[DroneData[SelectedDrone]["RecomType"]]["recommendations"]["bq"]["angle"].toString();
	}
	
	var drawdata=[];
	drawdata[0]= "Feldsuche seitwärts"; //Configuration
	drawdata[1]= fh; //flighheight
	drawdata[2]= oa; //objective angle
	drawdata[3]= angle; //camera angle
	drawdata[4]= 0.15; //overlapp
	drawdata[5]= round(bbuav,"down"); //trackdistance
	
	if ((graph=="show") && (line==LineDefaults["bq"])) {
		draw= drawdata;
		//draw[0]= "Feldsuche seitwärts"; //Configuration
		//draw[1]= fh; //flighheight
		//draw[2]= oa; //objective angle
		//draw[3]= angle; //camera angle
		//draw[4]= 0.15; //overlapp
		//draw[5]= round(bbuav,"down"); //trackdistance
	}
	return drawdata;
}			

function CalcForestSearchSidewards (line, feld) {
	height = document.getElementById("flightheight_ws-"+line).options[document.getElementById("flightheight_ws-"+line).selectedIndex].text;
	angle = document.getElementById("oangle_ws-"+line).options[document.getElementById("oangle_ws-"+line).selectedIndex].text;
	treeheight = document.getElementById("treeheight_ws-"+line).options[document.getElementById("treeheight_ws-"+line).selectedIndex].text;
	//alert("Berechne Waldsuche seitwärts/ ws: Zeile: " + line + " Höhe: " + height + " Winkel: " + angle + " Bauhöhe: " + treeheight);

	Input_Values["ws"][line]["flightheights"]=height;
	Input_Values["ws"][line]["camangle"]=angle;
	Input_Values["ws"][line]["treeheight"]=treeheight;

	ForestSearchSidewards (height, angle, treeheight, feld, line);
	UpdateDrawing(line, feld);
}

function ForestSearchSidewards (height, angle, treeheight, feld, line) {
	//alert("Berechne Feldsuche seitwärts/ bq: Höhe = " + height + " Kamerawinkel = " + angle + ", Zeile: " + line);

	//update inputs values
	//Input_Values["ws"][line]["flightheights"]=height;
	//Input_Values["ws"][line]["camangle"]=angle;

	//Inputs
	fh = parseInt(height);							// Flughöhe
	aa = parseInt(angle);							// Gimbal-/Kamerawinkel
	th = parseInt(treeheight);						// Baumhöhe
	fhoo = fh-th;									// Flughöhe oberhalb Objekt
	oa = DroneData[SelectedDrone]["IRCamAngle_V"];  //Objektivwinkel
	ua = oa*0.15;  									// Überlappungswinkel
	submax = 70;									// Bahnbreitengrenze
	submaxf = 0.2;									// Bahnbreitengrenzen Faktor
	bb_t = 0.9;										// Bahnbreitenfaktor tief
	bb_h = 1.0;										// Bahnbreitenfaktor hoch

	fo = Math.tan((90-aa-oa/2+ua)*Math.PI/180)*fhoo;				//Feldoffset [m]
	sifo = Math.tan((90-aa-oa/2)*Math.PI/180)*fhoo;				//Sichfeldoffset [m]
	sib = Math.tan((90-aa+oa/2)*Math.PI/180)*fhoo-sifo;			//Sichtbreite (mit Überlappung) [m]
	sub = (Math.tan((90-aa+oa/2-ua)*Math.PI/180)-Math.tan((90-aa-oa/2+ua)*Math.PI/180))*fhoo;	//Suchbreite (ohne Überlappung
	//Suchbreite (ohne Überlappung
	ulv = (Math.tan((90-aa+oa/2)*Math.PI/180)-Math.tan((90-aa+oa/2-ua)*Math.PI/180))*fhoo;	//Überlappung vorne
	ulh = (Math.tan((90-aa-oa/2+ua)*Math.PI/180)-Math.tan((90-aa-oa/2)*Math.PI/180))*fhoo;	//Überlappung hinten
	fbo = Math.tan((90-aa)*Math.PI/180)*fhoo;						//Offset zum Mittelpunkt [m]
	mid = fhoo/Math.cos((90-aa)*Math.PI/180);						// Mittlere Distanz [m]
	//mid = Math.sqrt(fhoo*fhoo+fbo*fbo);							// Mittlere Distanz [m] (alternativ)
	mad = fhoo/Math.cos((90-aa+oa/2)*Math.PI/180);				// Maximale Distanz [m]
	//mad = Math.sqrt(fhoo*fhoo+(sifo+sib)*(sifo+sib));				// Maximale Distanz [m] (alternativ)
	if (sub>submax) {
		bbuav = ((sub-submax)*submaxf)+submax;						// UAV Empfohlener Bahnabstand [m] > submax
	} else {
		bbuav = sub;											// UAV Empfohlener Bahnabstand [m] <= submax
    }
	bb = round(bbuav*bb_t,"down") + " - " + round(bbuav*bb_h,"down");	// Empfohlene Bahnbreite

	fbouav = fbo;	// UAV Flugpfadoffset [m]
	rauav = 1.5*ulh;	// UAV Randabstand [m]
			
	//Resultate ausgeben
	//Sichtbreite (mit Überlappung) [m]
	document.getElementById("SBmU_ws_"+line).innerHTML=round(sib,"");

	//Suchbreite (ohne Überlappung) [m]
	document.getElementById("SuBmU_ws_"+line).innerHTML=round(sub,"");
	
	//Überlappung [m]
	document.getElementById("UeAS_ws_"+line).innerHTML="V:" + round(ulv,"") + " H:" + round(ulh,"");

	// Feldoffset [m]
	document.getElementById("FO_ws_"+line).innerHTML="<b>"+round(fo,"")+"</b>";

	// Offset zum Mittelpunkt [m]
	document.getElementById("OMP_ws_"+line).innerHTML=round(fbo,"");

	// Mittlere Distanz [m]
	document.getElementById("MiD_ws_"+line).innerHTML=round(mid,"");

	// Maximale Distanz [m]
	document.getElementById("MaD_ws_"+line).innerHTML=round(mad,"");

	// UAV Empfohlener Bahnabstand [m]
	document.getElementById("EFBA_ws_"+line).innerHTML=bb;

	// UAV Bahnabstand [m]
	document.getElementById("FBA_ws_"+line).innerHTML="<b>"+round(bbuav,"down")+"</b>";

	// UAV Flugpfadoffset [m]
	document.getElementById("FPof_ws_"+line).innerHTML="<b>"+round(fbouav,"down")+"</b>";
	
	// UAV Randabstand [m]
	//31.10.2025 Keinen Randabstand für Winkelflüge								
	//document.getElementById("URA_ws_"+line).innerHTML="<b>"+round(rauav,"down")+"</b>";


	if ((FlightSettings[DroneData[SelectedDrone]["RecomType"]]["recommendations"]["ws"]["flightheight"].includes(parseInt(height))) &&
		(FlightSettings[DroneData[SelectedDrone]["RecomType"]]["recommendations"]["ws"]["angle"].includes(parseInt(angle))))
	{
	   ColorLine("ws", line, lgreen);
	} else {
	   ColorLine("ws", line, "");
	}
	document.getElementById("hint").innerHTML= Hints["VFW_ImWald"];

	if (setting== "TEST") {
		document.getElementById("debug").innerHTML = "ws: Drohne: " + SelectedDrone + " RecomType: " + DroneData[SelectedDrone]["RecomType"] + " rec heights " + FlightSettings[DroneData[SelectedDrone]["RecomType"]]["recommendations"]["ws"]["flightheight"].toString() + " rec angle " + FlightSettings[DroneData[SelectedDrone]["RecomType"]]["recommendations"]["ws"]["angle"].toString();
	}
	
	var drawdata=[];
	drawdata[0]= "Waldsuche seitwärts"; //Configuration
	drawdata[1]= fh; //flighheight
	drawdata[2]= oa; //objective angle
	drawdata[3]= angle; //camera angle
	drawdata[4]= 0.15; //overlapp
	drawdata[5]= round(bbuav,"down"); //trackdistance
	drawdata[9]= th; //baumhöhe
	
	if ((graph=="show") && (line==LineDefaults["ws"])) {
		draw= drawdata;
		//draw[0]= "Feldsuche seitwärts"; //Configuration
		//draw[1]= fh; //flighheight
		//draw[2]= oa; //objective angle
		//draw[3]= angle; //camera angle
		//draw[4]= 0.15; //overlapp
		//draw[5]= round(bbuav,"down"); //trackdistance
	}
	return drawdata;
}			



function CalcFieldSearchNearForest (line, feld) {
	height = document.getElementById("flightheight_aw-"+line).options[document.getElementById("flightheight_aw-"+line).selectedIndex].text;
	angle = document.getElementById("oangle_aw-"+line).options[document.getElementById("oangle_aw-"+line).selectedIndex].text;
	//alert("Berechne Feld am Waldrand/ aw: Zeile: " + line + " Höhe: " + height + " Winkel: " + angle + " Feld: " + feld);

	Input_Values["aw"][line]["flightheights"]=height;
	Input_Values["aw"][line]["camangle"]=angle;
	
	FieldSearchNearForest (height, angle, feld, line);
	UpdateDrawing(line, feld);
}

function FieldSearchNearForest (height, angle, feld, line ) {
	//alert("Berechne Feld am Waldrand/ aw: Höhe = " + height + " Kamerawinkel = " + angle + ", Zeile: " + line);
	//Objektivwinkel - document.getElementById("SBmU_q_m_"+line).innerHTML=DroneData[SelectedDrone]["IRCamAngle_V"];

	//update inputs values
	//Input_Values["aw"][line]["flightheights"]=height;
	//Input_Values["aw"][line]["camangle"]=angle;


	//Inputs
	fh = parseInt(height);							// Flughöhe
	aa = parseInt(angle);							// Gimbal-/Kamerawinkel
	oa = DroneData[SelectedDrone]["IRCamAngle_V"];  //Objektivwinkel
	ua = oa*0.15;  									// Überlappungswinkel
	//ua = oa*0.25;  								// Überlappungswinkel
	sd_t = 0.9;										// Suchdistanzfaktor tief
	sd_h = 1.0;										// Suchdistanzfaktor hoch
	rf_t = 1.5;										// Randfaktor tief
	rf_h = 2.0;										// Randfaktor hoch

	fo = Math.tan((90-aa-oa/2+ua)*Math.PI/180)*fh;				//Feldoffset [m]
	sifo = Math.tan((90-aa-oa/2)*Math.PI/180)*fh;				//Sichfeldoffset [m]
	sib = Math.tan((90-aa+oa/2)*Math.PI/180)*fh-sifo;			//Sichtbreite (mit Überlappung) [m]
	sub = (Math.tan((90-aa+oa/2-ua)*Math.PI/180)-Math.tan((90-aa-oa/2+ua)*Math.PI/180))*fh;	//Suchbreite (ohne Überlappung
	//Suchbreite (ohne Überlappung
	ulv = (Math.tan((90-aa+oa/2)*Math.PI/180)-Math.tan((90-aa+oa/2-ua)*Math.PI/180))*fh;	//Überlappung vorne
	ulh = (Math.tan((90-aa-oa/2+ua)*Math.PI/180)-Math.tan((90-aa-oa/2)*Math.PI/180))*fh;	//Überlappung hinten
	mid = fh/Math.cos((90-aa)*Math.PI/180);						// Mittlere Distanz [m]
	//mid = Math.sqrt(fh*fh+fbo*fbo);							// Mittlere Distanz [m] (alternativ)
	mad = fh/Math.cos((90-aa+oa/2)*Math.PI/180);				// Maximale Distanz [m]
	//mad = Math.sqrt(fh*fh+(sifo+sib)*(sifo+sib));				// Maximale Distanz [m] (alternativ)
	fbo = Math.tan((90-aa)*Math.PI/180)*fh;						//Offset zum Mittelpunkt [m]
	sd = Math.tan((90-aa+oa/2-ua)*Math.PI/180)*fh;				//Suchdistanz

	sduav = round(sd*sd_t,"down") + " - " + round(sd*sd_h,"down");	// Empfohlene Suchdistanzfaktor

	bbuav = round(sd,"");										// Bahnbreite UAV
	ra	  = round(ulh*rf_t,"") + " - " + round(ulh*rf_h,"");	// Randabstand
	rauav = ulh*(rf_t+rf_h)/2;		 							// Randabstand UAV
				
	//Resultate ausgeben
	//Sichtbreite (mit Überlappung) [m]
	document.getElementById("SBmU_w_"+line).innerHTML=round(sib,"");

	//Suchbreite (ohne Überlappung) [m]
	document.getElementById("SuBmU_w_"+line).innerHTML=round(sub,"");
	//Überlappung [m]
	document.getElementById("UeAS_w_"+line).innerHTML="V:" + round(ulv,"") + " H:" + round(ulh,"");

	// Feldoffset [m]
	document.getElementById("FO_w_"+line).innerHTML="<b>"+round(fo,"")+"</b>";

	// Offset zum Mittelpunkt [m]
	document.getElementById("OMP_w_"+line).innerHTML=round(fbo,"");

	// Mittlere Distanz [m]
	document.getElementById("MD_w_"+line).innerHTML=round(mid,"");

	// Empfohlene Suchdistanz [m]
	document.getElementById("EFBB_w_"+line).innerHTML=sduav;

	// UAV Empfohlener Randabstand [m]
	//31.10.2025 Keinen Randabstand für Winkelflüge
	//document.getElementById("ERA_w_"+line).innerHTML=ra;

	// UAV Bahnbreite [m]
	document.getElementById("UBB_w_"+line).innerHTML="<b>"+round(bbuav,"down")+"</b>";

	// UAV Randabstand [m]
	//31.10.2025 Keinen Randabstand für Winkelflüge
	//document.getElementById("URA_w_"+line).innerHTML="<b>"+round(rauav,"down")+"</b>";

	if ((FlightSettings[DroneData[SelectedDrone]["RecomType"]]["recommendations"]["aw"]["flightheight"].includes(parseInt(height))) &&
		(FlightSettings[DroneData[SelectedDrone]["RecomType"]]["recommendations"]["aw"]["angle"].includes(parseInt(angle))))
	{
	   ColorLine("aw", line, lgreen);
	} else {
	   ColorLine("aw", line, "");
	}	
	document.getElementById("hint").innerHTML= Hints["VFW_Wald"];

	if (setting== "TEST") {
		document.getElementById("debug").innerHTML = "aw: Drohne: " + SelectedDrone + " RecomType: " + DroneData[SelectedDrone]["RecomType"] + " rec heights " + FlightSettings[DroneData[SelectedDrone]["RecomType"]]["recommendations"]["aw"]["flightheight"].toString() + " rec angle " + FlightSettings[DroneData[SelectedDrone]["RecomType"]]["recommendations"]["aw"]["angle"].toString();
	}

	var drawdata=[];
	drawdata[0]= "Feld am Waldrand"; //Configuration
	drawdata[1]= fh; //flighheight
	drawdata[2]= oa; //objective angle
	drawdata[3]= angle; //camera angle
	drawdata[4]= 0.15; //overlapp
	drawdata[5]= Math.round(bbuav); //trackdistance
	drawdata[6]= round(sd,""); //search distance
	drawdata[7]= round(fo,""); //field offset

	if ((graph=="show") && (line==LineDefaults["aw"])) {
		draw=drawdata;
		//draw[0]= "Feld am Waldrand"; //Configuration
		//draw[1]= fh; //flighheight
		//draw[2]= oa; //objective angle
		//draw[3]= angle; //camera angle
		//draw[4]= 0.15; //overlapp
		//draw[5]= Math.round(bbuav); //trackdistance
		//draw[6]= round(sd,""); //search distance
		//draw[7]= round(fo,""); //field offset
	}
	return drawdata;
}

function CalcSearchInForest (line, feld) {
	//alert("Berechne Suche im Wald/ wr: Zeile: " + line );
	height = document.getElementById("flightheight_wr-"+line).options[document.getElementById("flightheight_wr-"+line).selectedIndex].text;
	angle = document.getElementById("oangle_wr-"+line).options[document.getElementById("oangle_wr-"+line).selectedIndex].text;
	sdist = document.getElementById("sdist_wr-"+line).options[document.getElementById("sdist_wr-"+line).selectedIndex].text;
	//alert("Berechne Suche im Wald/ wr: Zeile: " + line + " Höhe: " + height + " Winkel: " + angle + " Feld: " + feld);
	
	Input_Values["wr"][line]["flightheights"]=height;
	Input_Values["wr"][line]["camangle"]=angle;
	Input_Values["wr"][line]["searchdist"]=sdist;
	//SearchInForest(height, angle, sdist, feld, line);
	UpdateDrawing(line, feld);
}



function SearchInForest (height, angle, sdist, feld, line) {
	//alert("Berechne Suche im Wald/ wr: Höhe = " + height + " Objektivwinkel = " + angle + " Suchdistanz = " + sdist +", Zeile: " + line);

	//update inputs values
	//Input_Values["wr"][line]["flightheights"]=height;
	//Input_Values["wr"][line]["camangle"]=angle;
	//Input_Values["wr"][line]["searchdist"]=sdist;

	//Inputs
	fh = parseInt(height);							// Flughöhe
	aa = parseInt(angle);							// Gimbal/Kamerawinkel
	sd = parseInt(sdist);							// Suchdistanz (aussen)
	oa = DroneData[SelectedDrone]["IRCamAngle_V"];  // Objektivwinkel
	ua = oa*0.2;  									// Überlappungswinkel
	ua = oa*0.15;  									// Überlappungswinkel
	a_l = 0.9;										// Abstandfaktor tief
	a_h = 1.1;										// Abstandfaktor hoch


	
	fo = Math.tan((90-aa-oa/2+ua)*Math.PI/180)*fh; 	//FeldOffset [m]
	ul = (Math.tan((90-aa-oa/2+ua)*Math.PI/180)-Math.tan((90-aa-oa/2)*Math.PI/180))*fh; //Überlappung [m]
	
	sfo = Math.tan((90-aa-oa/2)*Math.PI/180)*fh; 	//Offset Sichtfeld
	aw  = fo + sd;									//Abstand Waldrand
	
	mad = Math.sqrt(fh*fh+aw*aw);
	suh = Math.tan((aa-oa/2+ua)*Math.PI/180)*aw;			//Suchhöhe [m]
	sih = Math.tan((aa-oa/2)*Math.PI/180)*aw;				//Sichhöhe [m]
	suh = Math.tan((aa-oa/2+ua)*Math.PI/180)*aw;			//Suchhöhe [m]

	rauav = round(aw*a_l,"") + " - " + round(aw*a_h,"down");	// Randabstand UAV
	
	//Resultate ausgeben
	// Abstand Waldrand [m]
	document.getElementById("AFB_wr_"+line).innerHTML="<b>"+round(aw,"")+"</b>";
	// Überlappung [m]
	document.getElementById("UeAS_wr_"+line).innerHTML=round(ul,"");
	// Suchhöhe [m]
	document.getElementById("SH_wr_"+line).innerHTML=round(suh,"");
	// Empfohlener Randabstand [m]
	document.getElementById("UAFW_wr_"+line).innerHTML=rauav;	
	// Flugpfad Offset [m]
	document.getElementById("MA_wr_"+line).innerHTML=round(mad,"");
	// Feldoffset [m]

	if ((FlightSettings[DroneData[SelectedDrone]["RecomType"]]["recommendations"]["wr"]["flightheight"].includes(parseInt(height))) &&
		(FlightSettings[DroneData[SelectedDrone]["RecomType"]]["recommendations"]["wr"]["angle"].includes(parseInt(angle))))
	{
	   ColorLine("wr", line, lgreen);
	} else {
	   ColorLine("wr", line, "");
	}	
	
	document.getElementById("hint").innerHTML= Hints["VFW_Waldrand"];

	if (setting== "TEST") {
		document.getElementById("debug").innerHTML = "wr: Drohne: " + SelectedDrone + " RecomType: " + DroneData[SelectedDrone]["RecomType"] + " rec heights " + FlightSettings[DroneData[SelectedDrone]["RecomType"]]["recommendations"]["wr"]["flightheight"].toString() + " rec angle " + FlightSettings[DroneData[SelectedDrone]["RecomType"]]["recommendations"]["wr"]["angle"].toString();
	} else {
		document.getElementById("debug").style.display= 'none';
		document.getElementById("debug1").style.display= 'none';
	}

	var drawdata=[];
	drawdata[0]= "Suche im Wald"; //Configuration
	drawdata[1]= fh; //flighheight
	drawdata[2]= oa; //objective angle
	drawdata[3]= angle; //camera angle
	drawdata[4]= 0.15; //overlapp
	drawdata[5]= 0; //trackdistance
	drawdata[6]= round(aw,""); //forest distance
	drawdata[7]= round(fo,""); //field offset
	drawdata[8]= round(sd,""); //search distance

	if ((graph=="show") && (line==LineDefaults["wr"])) {
	//if ((graph=="show") && (line==3)) {
		draw = drawdata;
		//draw[0]= "Suche im Wald"; //Configuration
		//draw[1]= fh; //flighheight
		//draw[2]= oa; //objective angle
		//draw[3]= angle; //camera angle
		//draw[4]= 0.15; //overlapp
		//draw[5]= 0; //trackdistance
		//draw[6]= round(aw,""); //forest distance
		//draw[7]= round(fo,""); //field offset
		//draw[8]= round(sd,""); //search distance
	}
	return drawdata;
}



function ColorLine(tab, line, color) {
	//var tables = {fh:8, fhq:8, bq:10, aw: 10, wr:6};
	//alert("Zeile einfärben: " + tab + " / " + line +  " / " + tables[tab] + " / " + color);
	 
	for (let step = 1; step <= PrefParameters[tab]["#Col"]; step++) {	
	//for (let step = 1; step <= tables[tab]; step++) {	
		document.getElementById(tab+step+"-"+line).style.backgroundColor= color;		
	}	
}


function UpdateDrawing(Line, feld) {
	atLine=1;
	CalcOption = SelectedCalc;
  	DefType = DroneData[SelectedDrone]["DefaultType"];

	if(CalcOption=="Feldsuche vorwärts") {
		DefaultSettings[DefType][CalcData[CalcOption]["key"]]["flightheights"].forEach((hl) => {
			if (atLine==Line) {
				LineDefaults[CalcData[CalcOption]["key"]]=Line;
				document.getElementById("draw-"+atLine).innerHTML="<img src='"+calciconloc+"dot_full.png' height=20>";
				//Input_Values[CalcData[CalcOption]["key"]]["graphshown"]=Line;
				CleanDrawing();
				//alert("Zeichen Flug vorwärts: " + Input_Values["fh"][Line]["flightheights"] + " / " + line);
				draw = FieldSearchForwards (Input_Values["fh"][Line]["flightheights"], Line);
				//draw = FieldSearchForwards (hl, Line);
				DrawFlighConfiguration (draw[0], draw[1], draw[2], draw[3], draw[4], draw[5], draw[6], draw[7], draw[8]);
			} else {
				document.getElementById("draw-"+atLine).innerHTML="<img src='"+calciconloc+"dot_empty.png' height=20>";
			}	
			++atLine;
		});
	}	
	
	if(CalcOption=="Flug seitwärts") {
		DefaultSettings[DefType][CalcData[CalcOption]["key"]]["flightheights"].forEach((hl) => {
			if (atLine==Line) {
				LineDefaults[CalcData[CalcOption]["key"]]=Line;
				document.getElementById("draw_fhq-"+atLine).innerHTML="<img src='"+calciconloc+"dot_full.png' height=20>";
				//Input_Values[CalcData[CalcOption]["key"]]["graphshown"]=Line;
				CleanDrawing();
				draw = FlightSidewards(Input_Values["fhq"][Line]["flightheights"], Line);
				DrawFlighConfiguration (draw[0], draw[1], draw[2], draw[3], draw[4], draw[5], draw[6], draw[7], draw[8]);
			} else {
				document.getElementById("draw_fhq-"+atLine).innerHTML="<img src='"+calciconloc+"dot_empty.png' height=20>";
			}	
			++atLine;
		});
	}				

	if(CalcOption=="Feldsuche seitwärts") {
		DefaultSettings[DefType][CalcData[CalcOption]["key"]]["flightheights"].forEach((hl) => {
			DefaultSettings[DefType][CalcData[CalcOption]["key"]]["camangle"].forEach((ca) => {
				if (atLine==Line) {
					LineDefaults[CalcData[CalcOption]["key"]]=Line;
					document.getElementById("draw_bq-"+atLine).innerHTML="<img src='"+calciconloc+"dot_full.png' height=20>";
					//Input_Values[CalcData[CalcOption]["key"]]["graphshown"]=Line;
					CleanDrawing();
					draw = FieldSearchSidewards(Input_Values["bq"][Line]["flightheights"], Input_Values["bq"][Line]["camangle"], feld, Line);
					DrawFlighConfiguration (draw[0], draw[1], draw[2], draw[3], draw[4], draw[5], draw[6], draw[7], draw[8]);
				} else {
					document.getElementById("draw_bq-"+atLine).innerHTML="<img src='"+calciconloc+"dot_empty.png' height=20>";
				}	
				++atLine;
			});
		});
	}				

	if(CalcOption=="Feld am Waldrand") {
		DefaultSettings[DefType][CalcData[CalcOption]["key"]]["flightheights"].forEach((hl) => {
			DefaultSettings[DefType][CalcData[CalcOption]["key"]]["camangle"].forEach((ca) => {
				if (atLine==Line) {
					LineDefaults[CalcData[CalcOption]["key"]]=Line;
					document.getElementById("draw_aw-"+atLine).innerHTML="<img src='"+calciconloc+"dot_full.png' height=20>";
					//Input_Values[CalcData[CalcOption]["key"]]["graphshown"]=Line;
					CleanDrawing();
					draw = FieldSearchNearForest(Input_Values["aw"][Line]["flightheights"], Input_Values["aw"][Line]["camangle"], feld, Line);
					DrawFlighConfiguration (draw[0], draw[1], draw[2], draw[3], draw[4], draw[5], draw[6], draw[7], draw[8]);
				} else {
					document.getElementById("draw_aw-"+atLine).innerHTML="<img src='"+calciconloc+"dot_empty.png' height=20>";
				}	
				++atLine;
			});
		});
	}				

	if(CalcOption=="Suche im Wald") {
		DefaultSettings[DefType][CalcData[CalcOption]["key"]]["flightheights"].forEach((hl) => {
			DefaultSettings[DefType][CalcData[CalcOption]["key"]]["camangle"].forEach((ca) => {
				DefaultSettings[DefType][CalcData[CalcOption]["key"]]["searchdist"].forEach((sd) => {
					if (atLine==Line) {
						LineDefaults[CalcData[CalcOption]["key"]]=Line;
						document.getElementById("draw_wr-"+atLine).innerHTML="<img src='"+calciconloc+"dot_full.png' height=20>";
						//Input_Values[CalcData[CalcOption]["key"]]["graphshown"]=Line;
						CleanDrawing();
						draw = SearchInForest(Input_Values["wr"][Line]["flightheights"], Input_Values["wr"][Line]["camangle"], Input_Values["wr"][Line]["searchdist"], feld, Line);
						DrawFlighConfiguration (draw[0], draw[1], draw[2], draw[3], draw[4], draw[5], draw[6], draw[7], draw[8]);
					} else {
						document.getElementById("draw_wr-"+atLine).innerHTML="<img src='"+calciconloc+"dot_empty.png' height=20>";
					}	
					++atLine;
				});
			});
		});
	}				

	if(CalcOption=="Waldsuche seitwärts") {
		DefaultSettings[DefType][CalcData[CalcOption]["key"]]["flightheights"].forEach((hl) => {
			DefaultSettings[DefType][CalcData[CalcOption]["key"]]["camangle"].forEach((ca) => {
				DefaultSettings[DefType][CalcData[CalcOption]["key"]]["treeheight"].forEach((sd) => {
					if (atLine==Line) {
						LineDefaults[CalcData[CalcOption]["key"]]=Line;
						document.getElementById("draw_ws-"+atLine).innerHTML="<img src='"+calciconloc+"dot_full.png' height=20>";
						Input_Values[CalcData[CalcOption]["key"]]["graphshown"]=Line;
						CleanDrawing();
						draw = ForestSearchSidewards(Input_Values["ws"][Line]["flightheights"], Input_Values["ws"][Line]["camangle"], Input_Values["ws"][Line]["treeheight"], feld, Line);
						DrawFlighConfiguration (draw[0], draw[1], draw[2], draw[3], draw[4], draw[5], 0, 0, 0, draw[9]);
					} else {
						document.getElementById("draw_ws-"+atLine).innerHTML="<img src='"+calciconloc+"dot_empty.png' height=20>";
					}	
					++atLine;
				});
			});
		});
	}				
}



function UpdCalc(val, line) {
	SelectedCalc= val;
	//alert("Calc geändert auf: " + val + line);
	document["calcpic"].style.display = 'none';
  
  	DefType = DroneData[SelectedDrone]["DefaultType"];
  
	if ((val != "Feldsuche vorwärts") && (val != "Flug seitwärts") && (val != "Feldsuche seitwärts") && (val != "Feld am Waldrand") && (val != "Suche im Wald") && (val != "Waldsuche seitwärts")) {
	//if ((val == "")) {
	    document.getElementById("calc").innerHTML = "<i>Bitte Berechnung auswählen: ";
		document.getElementById("info").innerHTML="";
		document.getElementById("hint").innerHTML="";
		document.getElementById("video").innerHTML="";
		document.getElementById("step_"+"fh").style.display= 'none';
		document.getElementById("step_"+"fhq").style.display= 'none';
		document.getElementById("step_"+"bq").style.display= 'none';
		document.getElementById("step_"+"aw").style.display= 'none';
		document.getElementById("step_"+"wr").style.display= 'none';
		document.getElementById("step_"+"ws").style.display= 'none';
		document.getElementById("flightsetup").style.display= 'none';
		document.getElementById("flightsetupbig").style.display= 'none';
		document.getElementById("video").style.display= 'none';
	} else {
		document.getElementById("video").style.display= 'block';

		//document.getElementById("calc").innerHTML = "<b>"+CalcData[val]["Name"]+"</b>";
		document.getElementById("calc").innerHTML = "";
		if (graph=="show") {
			if (flighsetupzoom=="s") {	
				document.getElementById("flightsetup").style.display= 'block';
				document.getElementById("flightsetupbig").style.display= 'none';
			} else {
				document.getElementById("flightsetup").style.display= 'none';
				document.getElementById("flightsetupbig").style.display= 'block';
			}
		} else {
			document.getElementById("flightsetup").style.display= 'none';
			document.getElementById("flightsetupbig").style.display= 'none';
		}
	}
	

  
	if ((val != "Feldsuche vorwärts") && (VAngle==0)) {
		document.getElementById("info").style.display= 'block';
		document.getElementById("info").innerHTML = "Diese Funktion kann leider nicht berechnet werden!";
		document["calcpic"].style.display = 'none';
	} else {
		if (flighsetupzoom=="s") {
			document["calcpic"].src = calciconloc+CalcData[val]["img"];
			document["calcpic"].style.display = 'block';
		} else {
			document["calcpic"].style.display = 'none';
		}			
		document.getElementById("info").style.display= 'none';
	}

	document.getElementById("debug1").innerHTML= "DefType: " + DefType;

	CalcOption = "Feldsuche vorwärts";
	if (CalcData[CalcOption]["show"]) {
		if ((val == CalcOption)){
			document.getElementById("debug1").innerHTML=document.getElementById("debug1").innerHTML + " " + CalcData[CalcOption]["key"] + ": " + DefaultSettings[DefType][CalcData[CalcOption]["key"]]["flightheights"].toString();
			line = 1;
			DefaultSettings[DefType][CalcData[CalcOption]["key"]]["flightheights"].forEach((hl) => {
				selectElement('flightheight-'+line, hl);
				FieldSearchForwards(hl, line);
				//Input_Values[CalcData[CalcOption]["key"]][line]["flightheights"]=hl;
				if (graph=="show") {
					document.getElementById("draw-"+line).style.display= 'block';
				} else {
					document.getElementById("draw-"+line).style.display= 'none';
				}
				line++;
			});
			$videosource ="<h4>Lernvideo</h4><br></td></td><video height='300' controls><source src='"+calciconloc+PrefParameters[CalcData[CalcOption]["key"]]["video"]+"' type=video/mp4></video>";
			document.getElementById("step_"+CalcData[CalcOption]["key"]).style.display= 'block';
		} else {
			document.getElementById("step_"+CalcData[CalcOption]["key"]).style.display= 'none';
		}
	}


	CalcOption = "Flug seitwärts";
	if (CalcData[CalcOption]["show"]) {
		if ((val == CalcOption) && (VAngle>0)) {
			document.getElementById("debug1").innerHTML=document.getElementById("debug1").innerHTML + " " + CalcData[CalcOption]["key"] + ": " + DefaultSettings[DefType][CalcData[CalcOption]["key"]]["flightheights"].toString();
			line = 1;
			DefaultSettings[DefType][CalcData[CalcOption]["key"]]["flightheights"].forEach((hl) => {
				selectElement('flightheight_fhq-'+line, hl);
				FlightSidewards(hl, line);
				//Input_Values[CalcData[CalcOption]["key"]][line]["flightheights"]=hl;
				if (graph=="show") {
					document.getElementById("draw_fhq-"+line).style.display= 'block';
				} else {
					document.getElementById("draw_fhq-"+line).style.display= 'none';
				}
				line++;
			});
			$videosource ="<h4>Lernvideo</h4><br></td></td><video height='300' controls><source src='"+calciconloc+PrefParameters[CalcData[CalcOption]["key"]]["video"]+"' type=video/mp4></video>";
			document.getElementById("step_"+CalcData[CalcOption]["key"]).style.display= 'block';
		} else {
			document.getElementById("step_"+CalcData[CalcOption]["key"]).style.display= 'none';
		}
	}

	CalcOption = "Feldsuche seitwärts";
	if (CalcData[CalcOption]["show"]) {
		if ((val == CalcOption) && (VAngle>0)) {
			document.getElementById("debug1").innerHTML=document.getElementById("debug1").innerHTML + " " + CalcData[CalcOption]["key"] + ": " + DefaultSettings[DefType][CalcData[CalcOption]["key"]]["flightheights"].toString();
			line = 1;
			DefaultSettings[DefType][CalcData[CalcOption]["key"]]["flightheights"].forEach((hl) => {
				DefaultSettings[DefType][CalcData[CalcOption]["key"]]["camangle"].forEach((ca) => {
					selectElement('flightheight_bq-'+line, hl);
					selectElement('oangle_bq-'+line, ca);
					FieldSearchSidewards(hl, ca, "", line);
					//Input_Values[CalcData[CalcOption]["key"]][line]["flightheights"]=hl;
					//Input_Values[CalcData[CalcOption]["key"]][line]["camangle"]=ca;
					if (graph=="show") {
						document.getElementById("draw_bq-"+line).style.display= 'block';
					} else {
						document.getElementById("draw_bq-"+line).style.display= 'none';
					}
					line++;
				});
			});
			$videosource ="<h4>Lernvideo</h4><br></td></td><video height='300' controls><source src='"+calciconloc+PrefParameters[CalcData[CalcOption]["key"]]["video"]+"' type=video/mp4></video>";
			document.getElementById("step_"+CalcData[CalcOption]["key"]).style.display= 'block';
		} else {
			document.getElementById("step_"+CalcData[CalcOption]["key"]).style.display= 'none';
		}
	}
		
	CalcOption = "Feld am Waldrand";
	if (CalcData[CalcOption]["show"]) {
		if ((val == CalcOption) && (VAngle>0)) {
			document.getElementById("debug1").innerHTML=document.getElementById("debug1").innerHTML + " " + CalcData[CalcOption]["key"] + ": " + DefaultSettings[DefType][CalcData[CalcOption]["key"]]["flightheights"].toString();
			line = 1;
			DefaultSettings[DefType][CalcData[CalcOption]["key"]]["flightheights"].forEach((hl) => {
				DefaultSettings[DefType][CalcData[CalcOption]["key"]]["camangle"].forEach((ca) => {
					selectElement('flightheight_aw-'+line, hl);
					selectElement('oangle_aw-'+line, ca);
					FieldSearchNearForest(hl, ca, "", line);
					//Input_Values[CalcData[CalcOption]["key"]][line]["flightheights"]=hl;
					//Input_Values[CalcData[CalcOption]["key"]][line]["camangle"]=ca;
					if (graph=="show") {
						document.getElementById("draw_aw-"+line).style.display= 'block';
					} else {
						document.getElementById("draw_aw-"+line).style.display= 'none';
					}
					line++;
				});
			});

			ColorLine("aw", 4);
			$videosource ="<h4>Lernvideo</h4><br></td></td><video height='300' controls><source src='"+calciconloc+PrefParameters[CalcData[CalcOption]["key"]]["video"]+"' type=video/mp4></video>";
			document.getElementById("step_"+CalcData[CalcOption]["key"]).style.display= 'block';
		} else {
			document.getElementById("step_"+CalcData[CalcOption]["key"]).style.display= 'none';
		}
	}

	CalcOption = "Suche im Wald";
	if (CalcData[CalcOption]["show"]) {
		if ((val == CalcOption) && (VAngle>0)) {
			document.getElementById("debug1").innerHTML=document.getElementById("debug1").innerHTML + " " + CalcData[CalcOption]["key"] + ": " + DefaultSettings[DefType][CalcData[CalcOption]["key"]]["flightheights"].toString();
			line = 1;
			DefaultSettings[DefType][CalcData[CalcOption]["key"]]["flightheights"].forEach((hl) => {
				DefaultSettings[DefType][CalcData[CalcOption]["key"]]["camangle"].forEach((ca) => {
					DefaultSettings[DefType][CalcData[CalcOption]["key"]]["searchdist"].forEach((sd) => {
						selectElement('flightheight_wr-'+line, hl);
						selectElement('oangle_wr-'+line, ca);
						selectElement('sdist_wr-'+line, sd);
						SearchInForest(hl, ca, sd, "", line);
						//Input_Values[CalcData[CalcOption]["key"]][line]["flightheights"]=hl;
						//Input_Values[CalcData[CalcOption]["key"]][line]["camangle"]=ca;
						//Input_Values[CalcData[CalcOption]["key"]][line]["searchdist"]=sd;
						if (graph=="show") {
							document.getElementById("draw_wr-"+line).style.display= 'block';
						} else {
							document.getElementById("draw_wr-"+line).style.display= 'none';
						}
						line++;
					});
				});
			});
			$videosource ="<h4>Lernvideo</h4><br></td></td><video height='300' controls><source src='"+calciconloc+PrefParameters[CalcData[CalcOption]["key"]]["video"]+"' type=video/mp4></video>";
			document.getElementById("step_"+CalcData[CalcOption]["key"]).style.display= 'block';
		} else {
			document.getElementById("step_"+CalcData[CalcOption]["key"]).style.display= 'none';
		}
	}
	
	CalcOption = "Waldsuche seitwärts";
	if (CalcData[CalcOption]["show"]) {
		//alert(CalcOption + " " + CalcData[CalcOption]["key"]);
		if ((val == CalcOption) && (VAngle>0)) {
			document.getElementById("debug1").innerHTML=document.getElementById("debug1").innerHTML + " " + CalcData[CalcOption]["key"] + ": " + DefaultSettings[DefType][CalcData[CalcOption]["key"]]["flightheights"].toString();
			line = 1;
			DefaultSettings[DefType][CalcData[CalcOption]["key"]]["flightheights"].forEach((hl) => {
				DefaultSettings[DefType][CalcData[CalcOption]["key"]]["camangle"].forEach((ca) => {
					DefaultSettings[DefType][CalcData[CalcOption]["key"]]["treeheight"].forEach((th) => {
						selectElement('flightheight_ws-'+line, hl);
						selectElement('oangle_ws-'+line, ca);
						selectElement('treeheight_ws-'+line, th);
						ForestSearchSidewards(hl, ca, th, "", line);
						if (graph=="show") {
							document.getElementById("draw_ws-"+line).style.display= 'block';
						} else {
							document.getElementById("draw_ws-"+line).style.display= 'none';
						}
						line++;
					});
				});
			});
			$videosource ="<h4>Lernvideo</h4><br></td></td><video height='300' controls><source src='"+calciconloc+PrefParameters[CalcData[CalcOption]["key"]]["video"]+"' type=video/mp4></video>";
			document.getElementById("step_"+CalcData[CalcOption]["key"]).style.display= 'block';
		} else {
			document.getElementById("step_"+CalcData[CalcOption]["key"]).style.display= 'none';
		}
	}
	
	document.getElementById("video").innerHTML=$videosource;
	if (setting!= "TEST") {
		document.getElementById("debug").style.display= 'none';
		document.getElementById("debug1").style.display= 'none';
	}	
	update_drawing();
}
// process input settings
if (SelectedDrone!="") {
	//alert("Drohne geändert auf: " + SelectedDrone);
	selectElement("Drohne", SelectedDrone);
	UpdDrone(SelectedDrone,1);
} else {
  if (infoplus) {
	document.getElementById("dronelist").innerHTML= GetDroneList() + "<br><H5>Suche am Hang</H5>" + "<small>" +Hints["Hang"] + "</small>" + "<br><H5>Suche im Wald</H5>" + "<small>" +  Hints["Wald"] + "</small>" ;
  } else {
	document.getElementById("dronelist").innerHTML= GetDroneList();
  }
 //document.getElementById("dronelist").innerHTML= "BLAs";
}


function DrawLine(px1, py1, px2, py2, type, width, color, endpoint_x=false, endpoint_y=false) {
	const canvas = document.getElementById(flighsetup);
	const ctx = canvas.getContext("2d");
	ctx.beginPath();
 	if (type=="dotted") {
		ctx.setLineDash([5, 5]);
	} else if (type=="dashed"){
		ctx.setLineDash([6, 3]);
	} else if (type=="center"){
		ctx.setLineDash([6, 3 , 1, 3]);
	} else {	
		ctx.setLineDash([]);
	}	
	
	ctx.moveTo(px1,py1);
	ctx.lineTo(px2,py2);
	ctx.lineWidth = width;
	ctx.strokeStyle = color;
	ctx.stroke();

	length = 3
	if (endpoint_x) {
		
		ctx.moveTo(px1-length,py1);
		ctx.lineTo(px1+length,py1);
		ctx.lineWidth = width;
		ctx.strokeStyle = color;
		ctx.stroke();
		
		ctx.moveTo(px2-length,py2);
		ctx.lineTo(px2+length,py2);
		ctx.lineWidth = width;
		ctx.strokeStyle = color;
		ctx.stroke();
	}	
	if (endpoint_y) {
		
		ctx.moveTo(px1,py1-length);
		ctx.lineTo(px1,py1+length);
		ctx.lineWidth = width;
		ctx.strokeStyle = color;
		ctx.stroke();
		
		ctx.moveTo(px2,py2-length);
		ctx.lineTo(px2,py2+length);
		ctx.lineWidth = width;
		ctx.strokeStyle = color;
		ctx.stroke();
	}	
}

function setLineDash(ctx, type) {
	const dashStyles = {
		dotted: [5, 5],
		dashed: [6, 3],
		center: [6, 3, 1, 3]
	};
	ctx.setLineDash(dashStyles[type] || []);
}

function DrawBeam(px1, py1, heightx, widthx, angle, type, width, color) {
	const ctx = document.getElementById(flighsetup).getContext("2d");
	ctx.beginPath();
	setLineDash(ctx, type);

	const rad = angle * Math.PI / 180;
	const lengthx = heightx * Math.tan(rad);

	ctx.moveTo(px1, py1);
	if (lengthx > widthx && widthx > 0) {
		const lengthy = widthx / Math.tan(rad);
		ctx.lineTo(px1 + widthx, py1 + lengthy);
	} else {
		ctx.lineTo(px1 + lengthx, py1 + heightx);
	}

	ctx.lineWidth = width;
	ctx.strokeStyle = color;
	ctx.stroke();
}

function DrawBaseLine(px1, py1, heightx, anglefrom, angleto, maxx, type, width, color, endpoint_x = false, endpoint_y = false) {
	const ctx = document.getElementById(flighsetup).getContext("2d");
	ctx.beginPath();
	setLineDash(ctx, type);

	const radFrom = anglefrom * Math.PI / 180;
	const radTo = angleto * Math.PI / 180;
	const start_x = heightx * Math.tan(radFrom);
	let to_x = heightx * Math.tan(radTo);
	if (to_x > maxx && maxx > 0) to_x = maxx;

	ctx.moveTo(px1 + start_x, py1 + heightx);
	ctx.lineTo(px1 + to_x, py1 + heightx);
	ctx.lineWidth = width;
	ctx.strokeStyle = color;
	ctx.stroke();

	const length = 3;
	const drawEndpoints = (x, y, horizontal) => {
		ctx.beginPath();
		if (horizontal) {
			ctx.moveTo(x - length, y);
			ctx.lineTo(x + length, y);
		} else {
			ctx.moveTo(x, y - length);
			ctx.lineTo(x, y + length);
		}
		ctx.stroke();
	};

	if (endpoint_x) {
		drawEndpoints(px1 + start_x, py1 + heightx, true);
		drawEndpoints(px1 + to_x, py1 + heightx, true);
	}
	if (endpoint_y) {
		drawEndpoints(px1 + start_x, py1 + heightx, false);
		drawEndpoints(px1 + to_x, py1 + heightx, false);
	}
}


function DrawCircle(px, py, diameter, pstart, pend, type, linewidth, linecolor, fillcolor) {
	const ctx = document.getElementById(flighsetup).getContext("2d");
	ctx.beginPath();
	setLineDash(ctx, type);

	const radStart = (90 + pstart) * Math.PI / 180;
	const radEnd = (90 + pend) * Math.PI / 180;
	ctx.arc(px, py, diameter, radStart, radEnd);

	if (fillcolor !== "") {
		ctx.fillStyle = fillcolor;
		ctx.fill();
	}

	ctx.lineWidth = linewidth;
	ctx.strokeStyle = linecolor;
	ctx.stroke();
}


function DrawText(px, py, text, font, color, align) {
	const ctx = document.getElementById(flighsetup).getContext("2d");
	ctx.font = font;
	ctx.fillStyle = color;
	ctx.textBaseline = align;
	ctx.fillText(text, px, py);
}


function DrawRect(px1, py1, px2, py2, color, fillcolor) {
	const ctx = document.getElementById(flighsetup).getContext("2d");

	if (color !== "transparent") {
		ctx.fillStyle = color;
		ctx.rect(px1, py1, px2, py2);
	}
	if (fillcolor !== "transparent") {
		ctx.globalAlpha = 0.2;
		ctx.fillStyle = fillcolor;
		ctx.fillRect(px1, py1, px2, py2);
	}
	ctx.globalAlpha = 1;
}

function DrawTree(px, py, width, height, color_top, color_trunk, linewidth) {
	const ctx = document.getElementById(flighsetup).getContext("2d");

	const tw = width / 3;
	const th = height / 5;
	const anz = 6;
	const step = height / (anz + 1);
	const spread = 3;
	const stretch = 1.3;

	// trunk
	ctx.beginPath();
	ctx.lineWidth = linewidth;
	ctx.strokeStyle = color_trunk;
	ctx.moveTo(px - tw, py + height);
	ctx.lineTo(px, py + height - th);
	ctx.lineTo(px + tw, py + height);
	ctx.stroke();

	// top
	for (let i = 1; i < anz; i++) {
		ctx.beginPath();
		ctx.strokeStyle = color_top;
		ctx.lineWidth = linewidth;
		const y = py + height - i * step;
		ctx.moveTo(px - spread * tw, y);
		ctx.lineTo(px, y - stretch * th);
		ctx.lineTo(px + spread * tw, y);
		ctx.stroke();
	}
}



function CleanDrawing() {
	const canvas = document.getElementById(flighsetup);
	const ctx = canvas.getContext("2d");
	ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);
}


function DrawOneDrone(name, fheight, angle, kangle, ol , offset ,track, forest_dist, fieldoffset, search_dist, treeheight) {
	const start = [];
	const canvas = document.getElementById(flighsetup);
	const ctx = canvas.getContext("2d");
	
    if (flighsetupzoom=="s") {
		//small
		thickness = 1;
		fontsize = "10px Arial";
		lineoffset = 5;

		zoom= ((ctx.canvas.height-100)/fheight);
		//if (zoom>6.5) {zoom=6.5}

		px0=200;
		py0=ctx.canvas.height-zoom*fheight-60;
	} else {
		//big
		thickness = 2;
		fontsize = "12px Arial";
		lineoffset = 6;

		zoom= ((ctx.canvas.height-125)/fheight);
		//if (zoom>6.5) {zoom=6.5}

		px0=220;
		py0=ctx.canvas.height-zoom*fheight-70;
	}

	start[0] = px0; 		// 0 = X
	start[1] = py0; 	 	// 1 = Y
	//fheight = 60;
	//kangle = 80;
	//zoom= 2;
	//angle=48;
	overlap=angle*ol;
	offset=Math.round(offset);
	piczoom= fheight/50;
	
	//Baumhöhe
	if (treeheight>0) {
		// fhao: flight height above object
		fhao = fheight-treeheight;
		yoo = zoom*treeheight;
		DrawBaseLine(start[0]+zoom*offset, start[1]+yoo, zoom*fhao, 0, (90-kangle+angle/2), zoom*forest_dist, "solid", thickness, grey);
		if (track==1) {
			startx = Math.round(offset+fhao*Math.tan((90-kangle-angle/2-overlap)*Math.PI/180));
			endx = Math.round(offset+fhao*Math.tan((90-kangle+angle/2-overlap)*Math.PI/180))-startx;
		} else {
			startx = Math.round(offset+fhao*Math.tan((90-kangle-angle/2+overlap)*Math.PI/180));
			endx = Math.round(offset+fhao*Math.tan((90-kangle+angle/2+overlap)*Math.PI/180))-startx;
		}
		DrawRect(start[0]+zoom*(startx),start[1]+zoom*(fheight-treeheight),zoom*(endx),yoo,dgreen, dgreen);
		//AnzTree=8;
		//for (t=1;t<AnzTree;t++) {
		//	DrawTree(start[0]+zoom*(startx+t*(endx)/AnzTree), start[1]+zoom*(fheight-treeheight), zoom*treeheight/AnzTree, zoom*treeheight, dgreen, brown, thickness*2);
		//}	
		DrawText(start[0]+zoom*(startx)+20, start[1]+zoom*(fheight-treeheight)+yoo/2,  "Wald", fontsize, dgreen, "top");
	} else {
		fhao = fheight;
		yoo = 0;
	}


	//Wald
	if (forest_dist>0) {
		DrawLine(start[0]+zoom*forest_dist, start[1], start[0]+zoom*forest_dist, start[1]+zoom*fhao, "solid", thickness, dgreen);
		DrawText(start[0]+zoom*forest_dist-lineoffset, start[1]-lineoffset, forest_dist, fontsize, dgreen, "middle");
	}

	//Mittellinie
	DrawBeam(start[0]+zoom*offset, start[1], zoom*fhao, zoom*forest_dist, (90-kangle), "center", thickness, lgrey);
	if (kangle!=90) {
		DrawLine(start[0]+zoom*offset, start[1], start[0]+zoom*(offset)+16*lineoffset, start[1], "solid", thickness, lgrey);
		diameter = piczoom*20;
		heightx = Math.round((diameter+2)*Math.sin((kangle/2)*Math.PI/180));
		heighty = Math.round((diameter+2)*Math.cos((kangle/2)*Math.PI/180));
		DrawCircle(start[0]+zoom*offset, start[1], zoom*diameter, (-90), (-90+kangle), "dashed", thickness, violet, "")
		//DrawText(start[0]+zoom*(offset+22), start[1]+zoom*5, kangle+"°", "10px Arial", blue, "middle");
		DrawText(start[0]+zoom*(offset+heighty)+lineoffset, start[1]+zoom*(heightx)+lineoffset, kangle+"°", fontsize, violet, "middle");
	} else {
		DrawLine(start[0]+zoom*offset, start[1], start[0]+zoom*(offset)+7*lineoffset, start[1], "solid", thickness, lgrey);
	}

	//Sichtlinie vorne
	DrawBeam(start[0]+zoom*offset, start[1], zoom*fhao, zoom*forest_dist, (90-kangle-angle/2), "dashed", thickness, grey);
	heightx = Math.round(offset+fhao*Math.tan((90-kangle-angle/2)*Math.PI/180));
	DrawText(start[0]+zoom*heightx-2*lineoffset, start[1]+zoom*fhao+3*lineoffset, heightx, fontsize, dgrey, "middle");			

	//Sichtlinie hinten
	DrawBeam(start[0]+zoom*offset, start[1], zoom*fhao, zoom*forest_dist, (90-kangle+angle/2), "dashed", thickness, grey);
	heightx = Math.round(offset+fhao*Math.tan((90-kangle+angle/2)*Math.PI/180));
	//heighty = Math.round(forest_dist/Math.tan((90-kangle+angle/2)*Math.PI/180));
	if ((heightx>forest_dist) && (forest_dist>0)) {
		DrawText(start[0]+zoom*forest_dist-2*lineoffset, start[1]+zoom*fhao+3*lineoffset, forest_dist, fontsize, dgrey, "middle");			
	} else {
		DrawText(start[0]+zoom*heightx-2*lineoffset, start[1]+zoom*fhao+3*lineoffset, heightx, fontsize, dgrey, "middle");			
	}

	//Suchlinie vorne
	DrawBeam(start[0]+zoom*offset, start[1], zoom*fhao, zoom*forest_dist, (90-kangle-angle/2+overlap), "solid", thickness, dgrey);
	heightx = Math.round(fhao*Math.tan((90-kangle-angle/2+overlap)*Math.PI/180));
	DrawText(start[0]+zoom*heightx-2*lineoffset, start[1]+zoom*fhao+3*lineoffset, heightx, fontsize, dgrey, "middle");			
	
	//Suchlinie hinten
	DrawBeam(start[0]+zoom*offset, start[1], zoom*fhao, zoom*forest_dist, (90-kangle+angle/2-overlap), "solid", thickness, dgrey);
	heightx = Math.round(offset+fhao*Math.tan((90-kangle+angle/2-overlap)*Math.PI/180));
	if ((heightx>forest_dist) && (forest_dist>0)) {
		DrawText(start[0]+zoom*forest_dist-2*lineoffset, start[1]+zoom*fhao+3*lineoffset, forest_dist, fontsize, dgrey, "middle");
		distance_forest = forest_dist;
	} else {
		DrawText(start[0]+zoom*heightx-2*lineoffset, start[1]+zoom*fhao+3*lineoffset, heightx, fontsize, dgrey, "middle");			
		distance_forest = heightx;
	}

	//Drohne
	DrawText(start[0]+zoom*offset-3*lineoffset, start[1]-2*lineoffset, name, fontsize, blue, "middle");
	DrawCircle(start[0]+zoom*offset, start[1], 4, 0, 360, "solid", thickness, blue, blue)
	DrawLine(start[0]+zoom*offset, start[1], start[0]+zoom*offset, start[1]+zoom*fheight, "dashed", thickness, blue, true, false);
	DrawText(start[0]+zoom*offset-4, start[1]+zoom*fheight+3*lineoffset, offset, fontsize, dgrey, "middle");			
	DrawText(start[0]+zoom*offset+5*lineoffset, start[1]-lineoffset, fheight, fontsize, dgrey, "middle");			

	//Grundlinie
	if (track!=1) {
		//dont draw on if not track 1
		DrawBaseLine(start[0]+zoom*offset, start[1], zoom*fhao, (90-kangle+angle/2), (90-kangle+angle/2)+overlap, 0, "solid", thickness, green); 
	}
	if (track!=2) {
		//dont draw on if not track 2
		DrawBaseLine(start[0]+zoom*offset, start[1], zoom*fhao, (90-kangle-angle/2), (90-kangle-angle/2)-overlap, 0, "solid", thickness, green);
	}
	
	//Bahnbreite
	if (track==2) {
		yoffset = piczoom*6;
		DrawLine(start[0], start[1]-zoom*yoffset, start[0]+zoom*offset, start[1]-zoom*yoffset, "solid", thickness, blue, false, true);
		DrawText(start[0]+zoom*offset/2, start[1]-zoom*yoffset-lineoffset, offset, fontsize, blue, "middle");			
	}

	//Suchbereich
	DrawBaseLine(start[0]+zoom*offset, start[1], zoom*fhao, (90-kangle-angle/2+overlap), (90-kangle+angle/2-overlap), zoom*forest_dist, "solid", thickness, orange);
	//DrawBaseLine(start[0]+zoom*offset, start[1], zoom*fhao, (90-kangle-angle/2+overlap), (90-kangle+angle/2-overlap), 0, "solid", thickness, orange);
	if (track==1) {
		yoffset = 6*lineoffset;
	}
	if (track==2) {
		yoffset = 5*lineoffset;
	}
	DrawBaseLine(start[0]+zoom*offset, start[1]+yoffset+yoo, zoom*fhao, (90-kangle-angle/2+overlap), (90-kangle+angle/2-overlap), zoom*forest_dist, "solid", thickness, orange, false, true);
	middlex = Math.round((fhao)*Math.tan((90-kangle)*Math.PI/180));
	heightxh = Math.round((fhao)*Math.tan((90-kangle+angle/2-overlap)*Math.PI/180));
	heightxv = Math.round((fhao)*Math.tan((90-kangle-angle/2+overlap)*Math.PI/180));
	if ((heightxh>forest_dist) && (forest_dist>0)) {
		bb = round(forest_dist-(fhao)*Math.tan((90-kangle-angle/2+overlap)*Math.PI/180));
		DrawText(start[0]+zoom*(offset+heightxv+(forest_dist-heightxv)/2), start[1]+zoom*(fhao)+yoffset+8+yoo, bb, fontsize, orange, "middle");			
	} else {
		bb = round((fhao)*(Math.tan((90-kangle+angle/2-overlap)*Math.PI/180)-Math.tan((90-kangle-angle/2+overlap)*Math.PI/180)));
		DrawText(start[0]+zoom*(offset+middlex), start[1]+zoom*(fhao)+yoffset+8+yoo, bb, fontsize, orange, "middle");			
	}

	//Sichtbereich
	if (track!=1) {
		//dont draw on if not track 1
		DrawBaseLine(start[0]+zoom*offset, start[1], zoom*fhao, (90-kangle+angle/2), (90-kangle+angle/2-overlap), 0, "solid", thickness, red) 
	}	
	if (track!=2) {
		//dont draw on if not track 2
		DrawBaseLine(start[0]+zoom*offset, start[1], zoom*fhao, (90-kangle-angle/2), (90-kangle-angle/2+overlap), 0, "solid", thickness, red) 
	}
	
	//Abstand (Wald)
	if (forest_dist>0) {
		yoffset = 5*lineoffset;
		DrawBaseLine(start[0]+zoom*offset, start[1]+yoffset, zoom*fhao, 0, (90-kangle+angle/2-overlap), zoom*forest_dist, "dashed", thickness, orange, false, true);
		DrawText(start[0]+2*lineoffset, start[1]+zoom*(fhao)+yoffset+8, distance_forest, fontsize, orange, "middle");			
	}

	//Bahnoffset
	if ((kangle!=90) && (track==1)) { 
		yoffset = 9*lineoffset;
		DrawBaseLine(start[0]+zoom*offset, start[1]+yoffset+yoo, zoom*fhao, 0, (90-kangle), zoom*forest_dist, "dotted", thickness, blue, false, true);
		trackoffset= round((fhao)*Math.tan((90-kangle)*Math.PI/180),"");
		DrawText(start[0]+2*lineoffset, start[1]+zoom*(fhao)+yoffset+2*lineoffset+yoo, trackoffset, fontsize, blue, "middle");			
	}
	
	//Objektivwinkel
	diameter = piczoom*2*lineoffset;
	DrawCircle(start[0]+zoom*offset, start[1], zoom*diameter, (-90+kangle-angle/2), (-90+kangle+angle/2), "solid", thickness, violet, "")
	heightx = Math.round((diameter)*Math.sin((kangle)*Math.PI/180));
	heighty = Math.round((diameter)*Math.cos((kangle)*Math.PI/180));
	DrawText(start[0]+zoom*(offset+heighty)+lineoffset, start[1]+zoom*(heightx)+2*lineoffset, angle+"°", fontsize, violet, "middle");
}



function DrawFlighConfiguration(constellation, fheight, angle, kangle, ol, trackdist) {
	const canvas = document.getElementById(flighsetup);
	const ctx = canvas.getContext("2d");
	ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);

	const config = draw[0];
	const commonParams = [draw[1], draw[2], draw[3], draw[4]];

	const twoDroneConfigs = [
		"Feldsuche vorwärts",
		"Flug seitwärts",
		"Feldsuche seitwärts"
	];

	if (twoDroneConfigs.includes(config)) {
		["Bahn 1", "Bahn 2"].forEach((label, i) => {
			const offset = i === 0 ? 0 : draw[5];
			DrawOneDrone(label, ...commonParams, offset, i + 1);
		});
	} else if (config === "Feld am Waldrand") {
		DrawOneDrone("Bahn", ...commonParams, 0, 1, draw[6], draw[7]);
	} else if (config === "Suche im Wald") {
		DrawOneDrone("Bahn", ...commonParams, 0, 1, draw[6], draw[7], draw[8]);
	} else if (config === "Waldsuche seitwärts") {
		["Bahn 1", "Bahn 2"].forEach((label, i) => {
			const offset = i === 0 ? 0 : draw[5];
			DrawOneDrone(label, ...commonParams, offset, i + 1, 0, 0, 0, draw[9]);
		});
	}

	// Legend setup
	const legend = [10, 25];
	const line_len = 20;
	const text_dist = 10;
	const isSmall = flighsetupzoom === "s";
	const thickness = isSmall ? 1 : 2;
	const fontsize = isSmall ? "10px Arial" : "12px Arial";
	const line_dist = isSmall ? 10 : 14;

	DrawText(legend[0], legend[1], config, "15px Arial", dgrey, "middle");

	const legendLines = [
		["dashed", grey, "Sichtgrenze"],
		["solid", dgrey, "Suchgrenze"],
		["dashed", blue, "Flughöhe"],
		["solid", orange, "Suchbereich"],
		["solid", red, "Sichtbereich"],
		["solid", green, "Feld"],
		["solid", blue, "Bahnabstand"],
		["solid", violet, "Objektwinkel"],
		["dashed", violet, "Gimbel-/Kamerawinkel"]
	];

	legendLines.forEach(([style, color, label], idx) => {
		const y = legend[1] + (idx + 2) * line_dist;
		DrawLine(legend[0], y, legend[0] + line_len, y, style, thickness, color);
		DrawText(legend[0] + line_len + text_dist, y, label, fontsize, dgrey, "middle");
	});

	let i = 11;
	if (["Suche im Wald", "Feld am Waldrand"].includes(config)) {
		const y = legend[1] + i * line_dist;
		DrawLine(legend[0], y, legend[0] + line_len, y, "dashed", thickness, orange);
		DrawText(legend[0] + line_len + text_dist, y, "Suchdistanz/Abstand", fontsize, dgrey, "middle");
		i++;
	}
	if (draw[2] !== 90) {
		const y1 = legend[1] + i * line_dist;
		DrawLine(legend[0], y1, legend[0] + line_len, y1, "dotted", thickness, blue);
		DrawText(legend[0] + line_len + text_dist, y1, "Flugpfadoffset", fontsize, dgrey, "middle");
		i++;
		const y2 = legend[1] + i * line_dist;
		DrawText(legend[0], y2, "Angaben in [m]", fontsize, dgrey, "middle");
	}
}

//DrawFlighsetupTest() ;



function update_drawing() {
	//alert("Zeichung updaten auf: " + draw[0]);
	if (graph=="show") {
		//to be replaced
		//DrawFlighConfiguration ("Feldsuche vorwärts", 60, 48, 90, 0.15 , 40);
		CleanDrawing();
		//DrawFlighConfiguration (draw[0], draw[1], draw[2], draw[3], draw[4], draw[5], draw[6], draw[7], draw[8]);
		DrawFlighConfiguration (draw);
	}
}



</script>
</body>
</html>
