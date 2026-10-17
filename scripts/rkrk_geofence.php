<?php
  //session_start();
  include_once('rkrk_gen.php');
  include_once('WGS84-LV03.php');
  include_once('SwisstopoConverter.php');

// ==================
// Funktionen für die Rehktitzrettung
// ==================

$DefaultNumOfPointsChecked = 10000;
//$NumOfAreasToCheck = 100;
$SoraPointStep = 1;   // not effective!



// =====================
// Defintions
// =====================
/* obsolete with SwissConverter.php 31.03.2023
	// Reference Points
	$LV95PointReferences = array (
		// Reference 1:
		array("ID" => 0, "Name" => "Kirchturm Neuheim", "Lon" => 8.57561, "Lat" => 47.20561, "LV95East" => 2686146.8, "LV95North" => 1228920.5),
		//array("ID" => 0, "Name" => "Kirchturm Rorschach", "Lon" => 9.49139, "Lat" => 47.47443, "LV95East" => 2754727.8, "LV95North" => 1260208.3),
		// Reference 2:
		array("ID" => 1, "Name" => "Kirchturm Udligenswil", "Lon" => 8.39936, "Lat" => 47.09109, "LV95East" => 2672944.2, "LV95North" => 1216011.5),
		//array("ID" => 1, "Name" => "Kirchturm Interlaken Matten", "Lon" => 7.86964, "Lat" => 46.67720, "LV95East" => 2632977.7, "LV95North" => 1169643.1),
		// Internal Reference Points
		array("ID" => 2, "Name" => "Kirchturm Mettmenstetten", "Lon" => 8.46372, "Lat" => 47.24285, "LV95East" => 2677610.2, "LV95North" => 1232943.3),
		array("ID" => 3, "Name" => "Kirchturm St Michael Zug", "Lon" => 8.51383, "Lat" => 47.17174, "LV95East" => 2681512.0, "LV95North" => 1225089.6),
		array("ID" => 4, "Name" => "Kirchturm Cham", "Lon" => 8.45964, "Lat" => 47.17921, "LV95East" => 2677393.0, "LV95North" => 1225864.8),
		array("ID" => 5, "Name" => "Kirchturm Abtwil", "Lon" => 8.35503, "Lat" => 47.17496, "LV95East" => 2669469.4, "LV95North" => 1225294.5),
		array("ID" => 6, "Name" => "Kirchturm Unterägeri", "Lon" => 8.58271, "Lat" => 47.13844, "LV95East" => 2686787.2, "LV95North" => 1221461.0),
		array("ID" => 7, "Name" => "Kirchturm Oberdorf Affoltern", "Lon" => 8.45503, "Lat" => 47.27809, "LV95East" => 2676901.4, "LV95North" => 1236852.2),
		array("ID" => 7, "Name" => "Kirchturm Menzingen", "Lon" => 8.59034, "Lat" => 47.17880, "LV95East" => 2689300.0, "LV95North" => 1225956.1),
		array("ID" => 8, "Name" => "Kirchturm Oberägeri", "Lon" => 8.61430, "Lat" => 47.13470, "LV95East" => 2689189.5, "LV95North" => 1221080.6),
		array("ID" => 9, "Name" => "Kirchturm Boswil", "Lon" => 8.31195, "Lat" => 47.29899, "LV95East" => 2666050.4, "LV95North" => 1239045.7),
		array("ID" => 10, "Name" => "Kirchturm Aeugst a.A.", "Lon" => 8.48822, "Lat" => 47.26711, "LV95East" => 2679428.5, "LV95North" => 1235664.3),
		array("ID" => 11, "Name" => "Kirchturm Meierskappel", "Lon" => 8.44258, "Lat" => 47.12526, "LV95East" => 2676177.0, "LV95North" => 1219850.3),
		array("ID" => 12, "Name" => "Kirchturm Arth", "Lon" => 8.52314, "Lat" => 47.06358, "LV95East" => 2682384.0, "LV95North" => 123075.3),
		array("ID" => 14, "Name" => "Kirchturm Küssnacht am Rigi", "Lon" => 8.44016, "Lat" => 47.08043, "LV95East" => 2676057.0, "LV95North" => 1214864.3),
	); 
	//pre_R($LV95PointReferences);
*/

// Assignment to internal References
 $KMLList = array( 
				"Gde" => array(
							 //Array(	"Name" 	=> 	"Knonau",  --> Name im KML
							 //		"Label" => 	"#GDE_KNAMT",  --> KML label 
							 //		"ID" 	=>	"KN"),         --> ID
							 Array(	"Name" 	=> 	"Knonau",
									"Label" => 	"#GDE_KNAMT",
									"ID" 	=>	"KN"),
							 //Array(	"Name" 	=> 	 "Knonau/ #GDE_AMT D: 0.80 NP: 2056",
							 Array(	"Name" 	=> 	 "Knonau",
									"Label" => 	"#GDE_AMT",
									"ID" 	=>	"KN"),
							 //Array(	"Name" 	=> 	 "Maschwanden/ #GDE_KNAMT D: 2.55 NP: 680",
							 Array(	"Name" 	=> 	 "Maschwanden",
									"Label" => 	"#GDE_KNAMT",
									"ID" 	=>	"MA"),
							 //Array(	"Name" 	=> 	 "Maschwanden/ #GDE_AMT D: 2.55 NP: 680",
							 Array(	"Name" 	=> 	 "Maschwanden",
									"Label" => 	"#GDE_AMT",
									"ID" 	=>	"MA"),
							 //Array(	"Name" 	=> 	 "Baar/ #GDE_ZG D: 3.38 NP: 82",
							 Array(	"Name" 	=> 	 "Baar",
									"Label" => 	"#GDE_ZG",
									"ID" 	=>	"BAA"),
							 //Array(	"Name" 	=> 	 "Steinhausen/ #GDE_ZG D: 3.38 NP: 32",
							 Array(	"Name" 	=> 	 "Steinhausen",
									"Label" => 	"#GDE_ZG",
									"ID" 	=>	"STE"),
							 //Array(	"Name" 	=> 	 "Hünenberg/ #GDE_ZG D: 3.90 NP: 57",
							 Array(	"Name" 	=> 	 "Hünenberg",
									"Label" => 	"#GDE_ZG",
									"ID" 	=>	"HUE"),
							 //Array(	"Name" 	=> 	 "Mettmenstetten/ #GDE_KNAMT D: 4.38 NP: 4221",
							 Array(	"Name" 	=> 	 "Mettmenstetten",
									"Label" => 	"#GDE_KNAMT",
									"ID" 	=>	"MET"),
							 //Array(	"Name" 	=> 	 "Mettmenstetten/ #GDE_AMT D: 4.38 NP: 4221",
							 Array(	"Name" 	=> 	 "Mettmenstetten",
									"Label" => 	"#GDE_AMT",
									"ID" 	=>	"MET"),
							 //Array(	"Name" 	=> 	 "Sins/ #GDE_OberesFreiamtAG D: 4.68 NP: 58",
							 Array(	"Name" 	=> 	 "Sins",
									"Label" => 	"#GDE_OberesFreiamtAG",
									"ID" 	=>	"SI"),
							 //Array(	"Name" 	=> 	 "Cham/ #GDE_ZG D: 5.07 NP: 86",
							 Array(	"Name" 	=> 	 "Cham",
									"Label" => 	"#GDE_ZG",
									"ID" 	=>	"CHA"),
							 //Array(	"Name" 	=> 	 "Zug/ #GDE_ZG D: 5.07 NP: 86",
							 Array(	"Name" 	=> 	 "Zug",
									"Label" => 	"#GDE_ZG",
									"ID" 	=>	"ZUG"),
							 //Array(	"Name" 	=> 	 "Rifferswil/ #GDE_KNAMT D: 5.22 NP: 2108",
							 Array(	"Name" 	=> 	 "Rifferswil",
									"Label" => 	"#GDE_KNAMT",
									"ID" 	=>	"RIF"),
							 //Array(	"Name" 	=> 	 "Rifferswil/ #GDE_AMT D: 5.22 NP: 2108",
							 Array(	"Name" 	=> 	 "Rifferswil",
									"Label" => 	"#GDE_AMT",
									"ID" 	=>	"RIF"),
							 //Array(	"Name" 	=> 	 "Merenschwand/ #GDE_OberesFreiamtAG D: 5.35 NP: 47",
							 Array(	"Name" 	=> 	 "Merenschwand",
									"Label" => 	"#GDE_OberesFreiamtAG",
									"ID" 	=>	"MER"),
							 //Array(	"Name" 	=> 	 "Mühlau/ #GDE_OberesFreiamtAG D: 6.08 NP: 26",
							 Array(	"Name" 	=> 	 "Mühlau",
									"Label" => 	"#GDE_OberesFreiamtAG",
									"ID" 	=>	"MUE"),
							 //Array(	"Name" 	=> 	 "Auw/ #GDE_OberesFreiamtAG D: 6.43 NP: 31",
							 Array(	"Name" 	=> 	 "Auw",
									"Label" => 	"#GDE_OberesFreiamtAG",
									"ID" 	=>	"AUW"),
							 //Array(	"Name" 	=> 	 "Kappel a. A./ #GDE_KNAMT D: 6.46 NP: 1891",
							 Array(	"Name" 	=> 	 "Kappel a. A.",
									"Label" => 	"#GDE_KNAMT",
									"ID" 	=>	"KaA"),
							 //Array(	"Name" 	=> 	 "Kappel am Albis/ #GDE_AMT D: 6.46 NP: 1891",
							 Array(	"Name" 	=> 	 "Kappel am Albis",
									"Label" => 	"#GDE_AMT",
									"ID" 	=>	"KaA"),
							 //Array(	"Name" 	=> 	 "Ottenbach/ #GDE_KNAMT D: 6.47 NP: 1207",
							 Array(	"Name" 	=> 	 "Ottenbach",
									"Label" => 	"#GDE_KNAMT",
									"ID" 	=>	"OTT"),
							 //Array(	"Name" 	=> 	 "Ottenbach/ #GDE_AMT D: 6.47 NP: 1207",
							 Array(	"Name" 	=> 	 "Ottenbach",
									"Label" => 	"#GDE_AMT",
									"ID" 	=>	"OTT"),
							 //Array(	"Name" 	=> 	 "Obfelden/ #GDE_KNAMT D: 6.50 NP: 2307",
							 Array(	"Name" 	=> 	 "Obfelden",
									"Label" => 	"#GDE_KNAMT",
									"ID" 	=>	"OB"),
							 //Array(	"Name" 	=> 	 "Obfelden/ #GDE_AMT D: 6.50 NP: 2307",
							 Array(	"Name" 	=> 	 "Obfelden",
									"Label" => 	"#GDE_AMT",
									"ID" 	=>	"OB"),
							 //Array(	"Name" 	=> 	 "Hausen a. A./ #GDE_KNAMT D: 7.07 NP: 1709",
							 Array(	"Name" 	=> 	 "Hausen a. A.",
									"Label" => 	"#GDE_KNAMT",
									"ID" 	=>	"HaA"),
							 //Array(	"Name" 	=> 	 "Hausen am Albis/ #GDE_AMT D: 7.07 NP: 1709",
							 Array(	"Name" 	=> 	 "Hausen am Albis",
									"Label" => 	"#GDE_AMT",
									"ID" 	=>	"HaA"),
							 //Array(	"Name" 	=> 	 "Oberrüti/ #GDE_OberesFreiamtAG D: 7.47 NP: 18",
							 Array(	"Name" 	=> 	 "Oberrüti",
									"Label" => 	"#GDE_OberesFreiamtAG",
									"ID" 	=>	"OR"),
							 //Array(	"Name" 	=> 	 "Aeugst a. A./ #GDE_KNAMT D: 7.68 NP: 1545",
							 Array(	"Name" 	=> 	 "Aeugst a. A.",
									"Label" => 	"#GDE_KNAMT",
									"ID" 	=>	"AeU"),
							 //Array(	"Name" 	=> 	 "Aeugst am Albis/ #GDE_AMT D: 7.68 NP: 1545",
							 Array(	"Name" 	=> 	 "Aeugst am Albis",
									"Label" => 	"#GDE_AMT",
									"ID" 	=>	"AeU"),
							 //Array(	"Name" 	=> 	 "Hedigen/ #GDE_KNAMT D: 8.22 NP: 1180",
							 Array(	"Name" 	=> 	 "Hedigen",
									"Label" => 	"#GDE_KNAMT",
									"ID" 	=>	"HED"),
							 //Array(	"Name" 	=> 	 "Hedingen",
							 Array(	"Name" 	=> 	 "Hedingen",
									"Label" => 	"#GDE_AMT",
									"ID" 	=>	"HED"),
							 //Array(	"Name" 	=> 	 "Affoltern am Albis/ #GDE_AMT D: 8.26 NP: 3411",
							 Array(	"Name" 	=> 	 "Affoltern am Albis",
									"Label" => 	"#GDE_AMT",
									"ID" 	=>	"AaA"),
							 //Array(	"Name" 	=> 	 "Hedigen/ #GDE_KNAMT D: 8.26 NP: 3411",
							 Array(	"Name" 	=> 	 "Hedigen",
									"Label" => 	"#GDE_KNAMT",
									"ID" 	=>	"HED"),
							 //Array(	"Name" 	=> 	 "Beinwil (Freiamt)/ #GDE_OberesFreiamtAG D: 8.41 NP: 28",
							 Array(	"Name" 	=> 	 "Beinwil (Freiamt)",
									"Label" => 	"#GDE_OberesFreiamtAG",
									"ID" 	=>	"BEI"),
							 //Array(	"Name" 	=> 	 "Abtwil/ #GDE_OberesFreiamtAG D: 8.47 NP: 17",
							 Array(	"Name" 	=> 	 "Abtwil",
									"Label" => 	"#GDE_OberesFreiamtAG",
									"ID" 	=>	"AB"),
							 //Array(	"Name" 	=> 	 "Neuheim/ #GDE_ZG D: 8.68 NP: 58",
							 Array(	"Name" 	=> 	 "Neuheim",
									"Label" => 	"#GDE_ZG",
									"ID" 	=>	"NEU"),
							 //Array(	"Name" 	=> 	 "Dietwil/ #GDE_OberesFreiamtAG D: 8.87 NP: 20",
							 Array(	"Name" 	=> 	 " Dietwil",
									"Label" => 	"#GDE_OberesFreiamtAG",
									"ID" 	=>	"DIE"),
							 //Array(	"Name" 	=> 	 "Langnau am Albis/ #GDE_AMT D: 10.01 NP: 2724",
							  Array(	"Name" 	=> 	 "Langnau am Albis",
									"Label" => 	"#GDE_AMT",
									"ID" 	=>	"LaA"),
							 //Array(	"Name" 	=> 	 "Rottenschwil/ #GDE_OberesFreiamtAG D: 10.64 NP: 21",
							 Array(	"Name" 	=> 	 "Rottenschwil",
									"Label" => 	"#GDE_OberesFreiamtAG",
									"ID" 	=>	"ROT"),
							 //Array(	"Name" 	=> 	 "Geltwil/ #GDE_OberesFreiamtAG D: 10.76 NP: 16",
							 Array(	"Name" 	=> 	 "Geltwil",
									"Label" => 	"#GDE_OberesFreiamtAG",
									"ID" 	=>	"GEL"),
							 //Array(	"Name" 	=> 	 "Bonsetten/ #GDE_KNAMT D: 11.13 NP: 1223",
							 Array(	"Name" 	=> 	 "Bonsetten",
									"Label" => 	"#GDE_KNAMT",
									"ID" 	=>	"BON"),
							 //Array(	"Name" 	=> 	 "Bonstetten/ #GDE_AMT D: 11.13 NP: 1223",
							 Array(	"Name" 	=> 	 "Bonstetten",
									"Label" => 	"#GDE_AMT",
									"ID" 	=>	"BON"),
							 //Array(	"Name" 	=> 	 "Oberrieden/ #GDE_AMT D: 11.15 NP: 1274",
							 Array(	"Name" 	=> 	 "Oberrieden",
									"Label" => 	"#GDE_AMT",
									"ID" 	=>	"OBE"),
							 //Array(	"Name" 	=> 	 "Unterägeri/ #GDE_ZG D: 11.22 NP: 61",
							 Array(	"Name" 	=> 	 "Unterägeri",
									"Label" => 	"#GDE_ZG",
									"ID" 	=>	"UAE"),
							 //Array(	"Name" 	=> 	 "Adliswil/ #GDE_AMT D: 11.26 NP: 1870",
							 Array(	"Name" 	=> 	 "Adliswil",
									"Label" => 	"#GDE_AMT",
									"ID" 	=>	"ADL"),
							 //Array(	"Name" 	=> 	 "Horgen/ #GDE_AMT D: 11.32 NP: 4991",
							 Array(	"Name" 	=> 	 "Horgen",
									"Label" => 	"#GDE_AMT",
									"ID" 	=>	"HOR"),
							 //Array(	"Name" 	=> 	 "Rüschlikon/ #GDE_AMT D: 11.45 NP: 2513",
							 Array(	"Name" 	=> 	 "Rüschlikon",
									"Label" => 	"#GDE_AMT",
									"ID" 	=>	"RUE"),
							 //Array(	"Name" 	=> 	 "Thalwil/ #GDE_AMT D: 11.45 NP: 4392",
							 Array(	"Name" 	=> 	 "Thalwil",
									"Label" => 	"#GDE_AMT",
									"ID" 	=>	"THA"),
							 //Array(	"Name" 	=> 	 " Aristau/ #GDE_OberesFreiamtAG D: 11.64 NP: 32",
							 Array(	"Name" 	=> 	 "Aristau",
									"Label" => 	"#GDE_OberesFreiamtAG",
									"ID" 	=>	"ARI"),
							 //Array(	"Name" 	=> 	 "Risch/ #GDE_ZG D: 11.66 NP: 38",
							 Array(	"Name" 	=> 	 "Risch",
									"Label" => 	"#GDE_ZG",
									"ID" 	=>	"RIS"),
							 //Array(	"Name" 	=> 	 "Aesch (ZH)/ #GDE_AMT D: 12.19 NP: 928",
							 Array(	"Name" 	=> 	 "Aesch (ZH)",
									"Label" => 	"#GDE_AMT",
									"ID" 	=>	"AES"),
							 //Array(	"Name" 	=> 	 "Muri/ #GDE_OberesFreiamtAG D: 12.19 NP: 30",
							 Array(	"Name" 	=> 	 "Muri",
									"Label" => 	"#GDE_OberesFreiamtAG",
									"ID" 	=>	"MUR"),
							 //Array(	"Name" 	=> 	 "Buttwil/ #GDE_OberesFreiamtAG D: 12.48 NP: 19",
							 Array(	"Name" 	=> 	 "Buttwil",
									"Label" => 	"#GDE_OberesFreiamtAG",
									"ID" 	=>	"BUT"),
							 //Array(	"Name" 	=> 	 "Walchwil/ #GDE_ZG D: 13.11 NP: 20",
							 Array(	"Name" 	=> 	 "Walchwil",
									"Label" => 	"#GDE_ZG",
									"ID" 	=>	"WAL"),
							 //Array(	"Name" 	=> 	 "Menzingen/ #GDE_ZG D: 13.24 NP: 54",
							 Array(	"Name" 	=> 	 "Menzingen",
									"Label" => 	"#GDE_ZG",
									"ID" 	=>	"MEN"),
							 //Array(	"Name" 	=> 	 "Besenbühren/ #GDE_OberesFreiamtAG D: 13.50 NP: 16",
							 Array(	"Name" 	=> 	 "Besenbühren",
									"Label" => 	"#GDE_OberesFreiamtAG",
									"ID" 	=>	"BES"),
							 //Array(	"Name" 	=> 	 "Wetswil a. A./ #GDE_KNAMT D: 13.71 NP: 3477",
							 Array(	"Name" 	=> 	 "Wetswil a. A.",
									"Label" => 	"#GDE_KNAMT",
									"ID" 	=>	"WaA"),
							 //Array(	"Name" 	=> 	 "Wettswil am Albis/ #GDE_AMT D: 13.71 NP: 3477",
							 Array(	"Name" 	=> 	 "Wettswil am Albis",
									"Label" => 	"#GDE_AMT",
									"ID" 	=>	"WaA"),
							 //Array(	"Name" 	=> 	 "Birmensdorf (ZH)/ #GDE_AMT D: 14.35 NP: 2635",
							 Array(	"Name" 	=> 	 "Birmensdorf (ZH)",
									"Label" => 	"#GDE_AMT",
									"ID" 	=>	"BIR"),
							 //Array(	"Name" 	=> 	 "Stallikon/ #GDE_KNAMT D: 14.35 NP: 3535",
							 Array(	"Name" 	=> 	 "Stallikon",
									"Label" => 	"#GDE_KNAMT",
									"ID" 	=>	"STA"),
							 //Array(	"Name" 	=> 	 "Stallikon/ #GDE_AMT D: 14.35 NP: 3535",
							 Array(	"Name" 	=> 	 "Stallikon",
									"Label" => 	"#GDE_AMT",
									"ID" 	=>	"STA"),
							 //Array(	"Name" 	=> 	 "Uitikon/ #GDE_AMT D: 14.35 NP: 1358",
							 Array(	"Name" 	=> 	 "Uitikon",
									"Label" => 	"#GDE_AMT",
									"ID" 	=>	"UIT"),
							 //Array(	"Name" 	=> 	 "Bünzen/ #GDE_OberesFreiamtAG D: 15.44 NP: 28",
							 Array(	"Name" 	=> 	 "Bünzen",
									"Label" => 	"#GDE_OberesFreiamtAG",
									"ID" 	=>	"BUE"),
							 //Array(	"Name" 	=> 	 "Boswil/ #GDE_OberesFreiamtAG D: 16.19 NP: 38",
							 Array(	"Name" 	=> 	 "Boswil",
									"Label" => 	"#GDE_OberesFreiamtAG",
									"ID" 	=>	"BOS"),
							 //Array(	"Name" 	=> 	 "Bettwil/ #GDE_OberesFreiamtAG D: 16.36 NP: 16",
							 Array(	"Name" 	=> 	 "Bettwil",
									"Label" => 	"#GDE_OberesFreiamtAG",
									"ID" 	=>	"BET"),
							 //Array(	"Name" 	=> 	 "Kallern/ #GDE_OberesFreiamtAG D: 16.75 NP: 21",
							 Array(	"Name" 	=> 	 "Kallern",
									"Label" => 	"#GDE_OberesFreiamtAG",
									"ID" 	=>	"KAL"),
							 //Array(	"Name" 	=> 	 "Oberägeri/ #GDE_ZG D: 17.42 NP: 124",
							 Array(	"Name" 	=> 	 "Oberägeri",
									"Label" => 	"#GDE_ZG",
									"ID" 	=>	"OAE"),
							 //Array(	"Name" 	=> 	 "Waltenschwil/ #GDE_OberesFreiamtAG D: 17.84 NP: 25",
							 Array(	"Name" 	=> 	 "Waltenschwil",
									"Label" => 	"#GDE_OberesFreiamtAG",
									"ID" 	=>	"WAL"),
							 //GDE Kt Luzern
							 Array(	"Name" 	=> 	 "Meierskappel",
									"Label" => 	"#GDE_LU",
									"ID" 	=>	"MEI"),
							 Array(	"Name" 	=> 	 "Honau",
									"Label" => 	"#GDE_LU",
									"ID" 	=>	"HON"),
							 Array(	"Name" 	=> 	 "Gisikon",
									"Label" => 	"#GDE_LU",
									"ID" 	=>	"GIS"),
							 Array(	"Name" 	=> 	 "Root",
									"Label" => 	"#GDE_LU",
									"ID" 	=>	"ROO"),
							 //GDE Kt Luzern
							 Array(	"Name" 	=> 	 "Meierskappel",
									"Label" => 	"#GDE_LU",
									"ID" 	=>	"MEI"),
							 //GDE Kt Schwyz
							 //Array(	"Name" 	=> 	 "60 Wohlen",
							 //		"Label" => 	"#JGB_AG",
							 //		"ID" 	=>	"60"),

												),
//Jagdgebiete
			"Jgd" =>  array(
				
				//"Jgd" => 	 Array(	"Name" 	=> 	 "26 Knonau/ #JGB_KNAMT D: 1.98 NP: 265",
					 Array(	"Name" 	=> 	 "26 Knonau",
									"Label" => 	"#JGB_KNAMT",
									"ID" 	=>	"26"),
							 //Array(	"Name" 	=> 	 "ZG Jagdgebiet 02/ #JGB_ZG D: 2.98 NP: 130",
							 Array(	"Name" 	=> 	 "ZG2 Jagdgebiet",
									"Label" => 	"#JGB_ZG",
									"ID" 	=>	"ZG2"),
							 //Array(	"Name" 	=> 	 "27 Maschwanden/ #JGB_KNAMT D: 3.03 NP: 285",
							 Array(	"Name" 	=> 	 "27 Maschwanden",
									"Label" => 	"#JGB_KNAMT",
									"ID" 	=>	"27"),
							 //Array(	"Name" 	=> 	 "25 Kappel/ #JGB_KNAMT D: 3.42 NP: 398",
							 Array(	"Name" 	=> 	 "25 Kappel",
									"Label" => 	"#JGB_KNAMT",
									"ID" 	=>	"25"),
							 //Array(	"Name" 	=> 	 "28 Mettmenstetten/ #JGB_KNAMT D: 4.12 NP: 423",
							 Array(	"Name" 	=> 	 "28 Mettmenstetten",
									"Label" => 	"#JGB_KNAMT",
									"ID" 	=>	"28"),
							 //Array(	"Name" 	=> 	 "148 Sins/ #JGB_OberesFreiamtAG D: 4.63 NP: 960",
							 Array(	"Name" 	=> 	 "148 Sins",
									"Label" => 	"#JGB_OberesFreiamtAG",
									"ID" 	=>	"148"),
							 //Array(	"Name" 	=> 	 "ZG Jagdgebiet 04/ #JGB_ZG D: 5.08 NP: 51",
							 Array(	"Name" 	=> 	 "ZG4 Jagdgebiet",
									"Label" => 	"#JGB_ZG",
									"ID" 	=>	"ZG4"),
							 //Array(	"Name" 	=> 	 "137 Auw/ #JGB_OberesFreiamtAG D: 5.23 NP: 638",
							 Array(	"Name" 	=> 	 "137 Auw",
									"Label" => 	"#JGB_OberesFreiamtAG",
									"ID" 	=>	"137"),
							 //Array(	"Name" 	=> 	 "ZG Jagdgebiet 01/ #JGB_ZG D: 5.75 NP: 55",
							 Array(	"Name" 	=> 	 "ZG1 Jagdgebiet",
									"Label" => 	"#JGB_ZG",
									"ID" 	=>	"ZG1"),
							 //Array(	"Name" 	=> 	 "ZG Jagdgebiet 03/ #JGB_ZG D: 6.22 NP: 123",
							 Array(	"Name" 	=> 	 "ZG3 Jagdgebiet",
									"Label" => 	"#JGB_ZG",
									"ID" 	=>	"ZG3"),
							 //Array(	"Name" 	=> 	 "29 Obfelden/ #JGB_KNAMT D: 6.28 NP: 269",
							 Array(	"Name" 	=> 	 "29 Obfelden",
									"Label" => 	"#JGB_KNAMT",
									"ID" 	=>	"29"),
							 //Array(	"Name" 	=> 	 "30 Ottenbach/ #JGB_KNAMT D: 6.28 NP: 246",
							 Array(	"Name" 	=> 	 "30 Ottenbach",
									"Label" => 	"#JGB_KNAMT",
									"ID" 	=>	"30"),
							 //Array(	"Name" 	=> 	 "145 Oberrüti/ #JGB_OberesFreiamtAG D: 6.38 NP: 275",
							 Array(	"Name" 	=> 	 "145 Oberrüti",
									"Label" => 	"#JGB_OberesFreiamtAG",
									"ID" 	=>	"145"),
							 //Array(	"Name" 	=> 	 "21 Aeugst a. A./ #JGB_KNAMT D: 6.75 NP: 364",
							 Array(	"Name" 	=> 	 "21 Aeugst a. A.",
									"Label" => 	"#JGB_KNAMT",
									"ID" 	=>	"21"),
							 //Array(	"Name" 	=> 	 "24 Hausen a. A./ #JGB_KNAMT D: 6.89 NP: 528",
							 Array(	"Name" 	=> 	 "24 Hausen a. A.",
									"Label" => 	"#JGB_KNAMT",
									"ID" 	=>	"24"),
							 //Array(	"Name" 	=> 	 "46 Langnau a.A./ #JGB_KNAMT D: 7.22 NP: 1330",
							 Array(	"Name" 	=> 	 "46 Langnau a.A.",
									"Label" => 	"#JGB_KNAMT",
									"ID" 	=>	"46"),
							 //Array(	"Name" 	=> 	 "22 Affoltern a. A./ #JGB_KNAMT D: 8.26 NP: 577",
							 Array(	"Name" 	=> 	 "22 Affoltern a. A.",
									"Label" => 	"#JGB_KNAMT",
									"ID" 	=>	"22"),
							 //Array(	"Name" 	=> 	 "143 Dietwil/ #JGB_OberesFreiamtAG D: 8.61 NP: 361",
							 Array(	"Name" 	=> 	 "143 Dietwil",
									"Label" => 	"#JGB_OberesFreiamtAG",
									"ID" 	=>	"143"),
							 //Array(	"Name" 	=> 	 "136 Abtwil/ #JGB_OberesFreiamtAG D: 9.01 NP: 255",
							 Array(	"Name" 	=> 	 "136 Abtwil",
									"Label" => 	"#JGB_OberesFreiamtAG",
									"ID" 	=>	"136"),
							 //Array(	"Name" 	=> 	 "ZG Jagdgebiet 05/ #JGB_ZG D: 9.37 NP: 62",
							 Array(	"Name" 	=> 	 "ZG5 Jagdgebiet",
									"Label" => 	"#JGB_ZG",
									"ID" 	=>	"ZG5"),
							 //Array(	"Name" 	=> 	 "46 Langnau a.A./ #JGB_KNAMT D: 9.90 NP: 1765",
							 Array(	"Name" 	=> 	 "46 Langnau a.A.",
									"Label" => 	"#JGB_KNAMT",
									"ID" 	=>	"46"),
							 //Array(	"Name" 	=> 	 "51 Jonen/ #JGB_AG D: 10.10 NP: 532",
							 Array(	"Name" 	=> 	 "51 Jonen",
									"Label" => 	"#JGB_AG",
									"ID" 	=>	"51"),
							 //Array(	"Name" 	=> 	 "138 Beinwil Freiamt/ #JGB_OberesFreiamtAG D: 10.29 NP: 310",
							 Array(	"Name" 	=> 	 "138 Beinwil Freiamt/ #JGB_OberesFreiamtAG D: 10.29 NP: 310",
									"Label" => 	"#JGB_OberesFreiamtAG",
									"ID" 	=>	"138"),
							 //Array(	"Name" 	=> 	 "43 Horgen I/ #JGB_KNAMT D: 10.96 NP: 844",
							 Array(	"Name" 	=> 	 "43 Horgen I",
									"Label" => 	"#JGB_KNAMT",
									"ID" 	=>	"43"),
							 //Array(	"Name" 	=> 	 "32 Stallikon Uetliberg IIÂ / #JGB_KNAMT D: 11.20 NP: 516",
							 Array(	"Name" 	=> 	 "32 Stallikon Uetliberg IIÂ",
									"Label" => 	"#JGB_KNAMT",
									"ID" 	=>	"32"),
							 //Array(	"Name" 	=> 	 "142 Muri/ #JGB_OberesFreiamtAG D: 11.45 NP: 676",
							 Array(	"Name" 	=> 	 "142 Muri",
									"Label" => 	"#JGB_OberesFreiamtAG",
									"ID" 	=>	"142"),
							 //Array(	"Name" 	=> 	 "42 Hirzel/ #JGB_KNAMT D: 11.53 NP: 455",
							 Array(	"Name" 	=> 	 "42 Hirzel",
									"Label" => 	"#JGB_KNAMT",
									"ID" 	=>	"42"),
							 //Array(	"Name" 	=> 	 "0 50 Thalwil/Oberrieden/ #JGB_KNAMT D: 11.59 NP: 616",
							 Array(	"Name" 	=> 	 "50 Thalwil/Oberrieden",
									"Label" => 	"#JGB_KNAMT",
									"ID" 	=>	"50"),
							 //Array(	"Name" 	=> 	 "0 ZG Jagdgebiet 06/ #JGB_ZG D: 11.77 NP: 230",
							 Array(	"Name" 	=> 	 "ZG6 Jagdgebiet",
									"Label" => 	"#JGB_ZG",
									"ID" 	=>	"ZG6"),
							 //Array(	"Name" 	=> 	 "146 Reusstal/ #JGB_OberesFreiamtAG D: 11.93 NP: 843",
							 Array(	"Name" 	=> 	 "146 Reusstal",
									"Label" => 	"#JGB_OberesFreiamtAG",
									"ID" 	=>	"146"),
							 //Array(	"Name" 	=> 	 "58 Unterlunkhofen/ #JGB_AG D: 12.06 NP: 330",
							 Array(	"Name" 	=> 	 "58 Unterlunkhofen",
									"Label" => 	"#JGB_AG",
									"ID" 	=>	"58"),
							 //Array(	"Name" 	=> 	 "0 42 Arni-Islisberg/ #JGB_AG D: 12.11 NP: 456",
							 Array(	"Name" 	=> 	 "42 Arni-Islisberg",
									"Label" => 	"#JGB_AG",
									"ID" 	=>	"42"),
							 //Array(	"Name" 	=> 	 "23 Bonstetten/ #JGB_KNAMT D: 12.56 NP: 613",
							 Array(	"Name" 	=> 	 "23 Bonstetten",
									"Label" => 	"#JGB_KNAMT",
									"ID" 	=>	"23"),
							 //Array(	"Name" 	=> 	 "41 Adliswil/ #JGB_KNAMT D: 13.08 NP: 598",
							 Array(	"Name" 	=> 	 "41 Adliswil",
									"Label" => 	"#JGB_KNAMT",
									"ID" 	=>	"41"),
							 //Array(	"Name" 	=> 	 "51 Wädenswil/ #JGB_KNAMT D: 13.12 NP: 989",
							 Array(	"Name" 	=> 	 "51 Wädenswil",
									"Label" => 	"#JGB_KNAMT",
									"ID" 	=>	"51"),
							 //Array(	"Name" 	=> 	 "01 Aesch/ #JGB_KNAMT D: 13.82 NP: 619",
							 Array(	"Name" 	=> 	 "01 Aesch",
									"Label" => 	"#JGB_KNAMT",
									"ID" 	=>	"01"),
							 //Array(	"Name" 	=> 	 "53 Oberwil Lieli/ #JGB_AG D: 14.10 NP: 437",
							 Array(	"Name" 	=> 	 "53 Oberwil Lieli",
									"Label" => 	"#JGB_AG",
									"ID" 	=>	"53"),
							 //Array(	"Name" 	=> 	 "31 Stallikon Uetliberg I/ #JGB_KNAMT D: 14.45 NP: 360",
							 Array(	"Name" 	=> 	 "31 Stallikon Uetliberg I",
									"Label" => 	"#JGB_KNAMT",
									"ID" 	=>	"31"),
							 //Array(	"Name" 	=> 	 "49 Besenbüren/ #JGB_OberesFreiamtAG D: 15.15 NP: 574",
							 Array(	"Name" 	=> 	 "49 Besenbüren",
									"Label" => 	"#JGB_OberesFreiamtAG",
									"ID" 	=>	"49"),
							 //Array(	"Name" 	=> 	 "61 Zufikon/ #JGB_AG D: 15.86 NP: 331",
							 Array(	"Name" 	=> 	 "61 Zufikon",
									"Label" => 	"#JGB_AG",
									"ID" 	=>	"61"),
							 //Array(	"Name" 	=> 	 "02 Birmensdorf/ #JGB_KNAMT D: 15.98 NP: 590",
							 Array(	"Name" 	=> 	 "02 Birmensdorf",
									"Label" => 	"#JGB_KNAMT",
									"ID" 	=>	"02"),
							 //Array(	"Name" 	=> 	 "140 Boswil/ #JGB_OberesFreiamtAG D: 15.98 NP: 500",
							 Array(	"Name" 	=> 	 "140 Boswil",
									"Label" => 	"#JGB_OberesFreiamtAG",
									"ID" 	=>	"140"),
							 //Array(	"Name" 	=> 	 "49 Schönenberg/ #JGB_KNAMT D: 16.27 NP: 417",
							 Array(	"Name" 	=> 	 "49 Schönenberg",
									"Label" => 	"#JGB_KNAMT",
									"ID" 	=>	"49"),
							 //Array(	"Name" 	=> 	 "139 Bettwil/ #JGB_OberesFreiamtAG D: 16.29 NP: 371",
							 Array(	"Name" 	=> 	 "139 Bettwil",
									"Label" => 	"#JGB_OberesFreiamtAG",
									"ID" 	=>	"139"),
							 //Array(	"Name" 	=> 	 "149 Waltenschwil/ #JGB_OberesFreiamtAG D: 16.38 NP: 597",
							 Array(	"Name" 	=> 	 "149 Waltenschwil",
									"Label" => 	"#JGB_OberesFreiamtAG",
									"ID" 	=>	"149"),
							 //Array(	"Name" 	=> 	 "09 Urdorf/ #JGB_KNAMT D: 17.32 NP: 332",
							 Array(	"Name" 	=> 	 "09 Urdorf",
									"Label" => 	"#JGB_KNAMT",
									"ID" 	=>	"09"),
							 //Array(	"Name" 	=> 	 "07 Uititkon/ #JGB_KNAMT D: 17.37 NP: 349",
							 Array(	"Name" 	=> 	 "07 Uititkon",
									"Label" => 	"#JGB_KNAMT",
									"ID" 	=>	"07"),
							 //Array(	"Name" 	=> 	 "43 Friedlisberg/ #JGB_AG D: 17.47 NP: 505",
							 Array(	"Name" 	=> 	 "43 Friedlisberg",
									"Label" => 	"#JGB_AG",
									"ID" 	=>	"43"),
							 //Array(	"Name" 	=> 	 "45 Hütten/ #JGB_KNAMT D: 17.47 NP: 353",
							 Array(	"Name" 	=> 	 "45 Hütten",
									"Label" => 	"#JGB_KNAMT",
									"ID" 	=>	"45"),
							 //Array(	"Name" 	=> 	 "61 Zufikon/ #JGB_AG D: 17.54 NP: 610",
							 Array(	"Name" 	=> 	 "61 Zufikon",
									"Label" => 	"#JGB_AG",
									"ID" 	=>	"61"),
							 //Array(	"Name" 	=> 	 "47 Richterswil/ #JGB_KNAMT D: 17.57 NP: 556",
							 Array(	"Name" 	=> 	 "47 Richterswil",
									"Label" => 	"#JGB_KNAMT",
									"ID" 	=>	"47"),
							 //Array(	"Name" 	=> 	 "57 Uezwil/ #JGB_OberesFreiamtAG D: 18.03 NP: 246",
							 Array(	"Name" 	=> 	 "57 Uezwil",
									"Label" => 	"#JGB_OberesFreiamtAG",
									"ID" 	=>	"57"),
							 //Array(	"Name" 	=> 	 "149 Waltenschwil/ #JGB_OberesFreiamtAG D: 18.98 NP: 289",
							 Array(	"Name" 	=> 	 "149 Waltenschwil",
									"Label" => 	"#JGB_OberesFreiamtAG",
									"ID" 	=>	"149"),
							 //Array(	"Name" 	=> 	 "06 Schlieren/ #JGB_KNAMT D: 19.79 NP: 276",
							 Array(	"Name" 	=> 	 "06 Schlieren",
									"Label" => 	"#GDE_KNAMT",
									"ID" 	=>	"06"),
							 //Array(	"Name" 	=> 	 "55 Sarmenstorf/ #JGB_OberesFreiamtAG D: 20.17 NP: 406",
							 Array(	"Name" 	=> 	 "55 Sarmenstorf",
									"Label" => 	"#JGB_OberesFreiamtAG",
									"ID" 	=>	"55"),
							 //Array(	"Name" 	=> 	 "44 Bremgarten/ #JGB_AG D: 20.51 NP: 671",
							 Array(	"Name" 	=> 	 "44 Bremgarten",
									"Label" => 	"#JGB_AG",
									"ID" 	=>	"44"),
							 //Array(	"Name" 	=> 	 "03 Dietikon/ #JGB_AG D: 22.25 NP: 525",
							 Array(	"Name" 	=> 	 "03 Dietikon",
									"Label" => 	"#GDE_KNAMT",
									"ID" 	=>	"03"),
							 //Array(	"Name" 	=> 	 "Jagdbezirk Amt/ #GDE_KNAMT D: 22.72 NP: 4610",
							 Array(	"Name" 	=> 	 "Jagdbezirk Amt",
									"Label" => 	"#GDE_KNAMT",
									"ID" 	=>	"AZ"),
							 //Array(	"Name" 	=> 	 "50 Villmergen/ #JGB_AG D: 23.53 NP: 717",
							 Array(	"Name" 	=> 	 "50 Villmergen",
									"Label" => 	"#JGB_AG",
									"ID" 	=>	"50"),
							 //Array(	"Name" 	=> 	 "60 Wohlen/ #JGB_AG D: 23.53 NP: 1033",
							 Array(	"Name" 	=> 	 "60 Wohlen",
									"Label" => 	"#JGB_AG",
									"ID" 	=>	"60"),
							 //JG Kt Luzern
							 Array(	"Name" 	=> 	 "LME Meierskappel",
									"Label" => 	"#JGB_LU",
									"ID" 	=>	"LME"),
							 //JG Kt Schwyz
							 //Array(	"Name" 	=> 	 "60 Wohlen",
							 //		"Label" => 	"#JGB_AG",
							 //		"ID" 	=>	"60"),
												),
//Flugzonen
			"FlgZ" => array(
							 //Array(	"Name" 	=> 	 "FGHO Alblis - 70m GND Flugzone/ #70mFlugzone D: 3.57 NP: 72",
							 Array(	"Name" 	=> 	 "FGHO Albis - 70m GND Flugzone",
									"Label" => 	"#70mFlugzone",
									"Warnung" => "CTR",
									"ID" 	=>	"FGHO Albis 70"),
							 //Array(	"Name" 	=> 	 "FGHO Alblis - 120m GND Flugzone/ #120mFlugzone D: 4.41 NP: 7",
							 Array(	"Name" 	=> 	 "FGHO Alblis - 120m GND Flugzone",
									"Label" => 	"#120mFlugzone",
									"Warnung" => "CTR",
									"ID" 	=>	"FGHO Albis 120"),
							 //Array(	"Name" 	=> 	 "FGHO Albis - Nachtflugzone1/ #Nachtflugzone1 D: 6.08 NP: 26",
							 Array(	"Name" 	=> 	 "FGHO Albis - Nachtflugzone 1a",
									"Label" => 	"#Nachtflugzone1",
									"Warnung" => "CTR NGT1",
									"ID" 	=>	"FGHO Albis NF 22-06"),
							 Array(	"Name" 	=> 	 "FGHO Albis - Nachtflugzone 1b",
									"Label" => 	"#Nachtflugzone1",
									"Warnung" => "CTR NGT1",
									"ID" 	=>	"FGHO Albis NF 22-06"),
							 //Array(	"Name" 	=> 	 "FGHO Alblis - Nachtflugzone2/ #Nachtflugzone2 D: 6.08 NP: 26",
							 Array(	"Name" 	=> 	 "FGHO Albis - Nachtflugzone 2a",
									"Label" => 	"#Nachtflugzone2",
									"Warnung" => "CTR NGT2",
									"ID" 	=>	"FGHO Albis NF 22-08"),
							 Array(	"Name" 	=> 	 "FGHO Albis - Nachtflugzone 2b",
									"Label" => 	"#Nachtflugzone2",
									"Warnung" => "CTR NGT2",
									"ID" 	=>	"FGHO Albis NF 22-08"),
							 Array(	"Name" 	=> 	 "FGHO Albis - Nachtflugzone 2c",
									"Label" => 	"#Nachtflugzone2",
									"Warnung" => "CTR NGT2",
									"ID" 	=>	"FGHO Albis NF 22-08"),
							 //Array(	"Name" 	=> 	 "FGHO Alblis - Flugverbotszone/ #Flugverbotszone D: 4.35 NP: 8",
							 Array(	"Name" 	=> 	 "FGHO Albis - Flugverbotszone",
									"Label" => 	"#Flugverbotszone",
									"Warnung" => "CTR STP",
									"ID" 	=>	"FGHO Albis FV"),
							 //Array(	"Name" 	=> 	 "LSME Emmen/ #Flugverbotszone D: 12.75 NP: 43",
							 Array(	"Name" 	=> 	 "LSME Emmen",
									"Label" => 	"#Flugverbotszone",
									"Warnung" => "CTR",
									"ID" 	=>	"LSME Emmen FV"),
							 //Array(	"Name" 	=> 	 "LSXN Haltikon - 70m GND Flugzone/ Nachtflugzone3",
							 Array(	"Name" 	=> 	 "LSXN Haltikon - Nachtflugzone 3",
									"Label" => 	"#Nachtflugzone3",
									"Warnung" => "CTR NGT3",
									"ID" 	=>	"LSXN Haltikon NF 05-09"),
							 //Array(	"Name" 	=> 	 "LSXN Haltikon/ #Flugverbotszone D: 12.75 NP: 40",
							 //Array(	"Name" 	=> 	 "LSXN Haltikon",
							 //		"Label" => 	"#Flugverbotszone",
							 //		"Warnung" => "CTR",
							 //		"ID" 	=>	"LSXN Haltikon FV"),
							 //Array(	"Name" 	=> 	 "LSZU Buttwil/ #Flugverbotszone D: 14.00 NP: 15",
							 Array(	"Name" 	=> 	 "LSZU Buttwil",
									"Label" => 	"#Flugverbotszone",
									"Warnung" => "CTR",
									"ID" 	=>	"LSZU Buttwil FV"),
							 //Array(	"Name" 	=> 	 "Flugverbotszone Buttwil/ #Flugverbotszone D: 14.00 NP: 15",
							 Array(	"Name" 	=> 	 "Flugverbotszone Buttwil",
									"Label" => 	"#GDE_KNAMT",
									"Warnung" => "CTR",
									"ID" 	=>	"Buttwil FV"),
							 //Array(	"Name" 	=> 	 "LSZO Luzern-Beromünster/ #Flugverbotszone D: 14.66 NP: 37",
							 Array(	"Name" 	=> 	 "LSZO Luzern-Beromünster",
									"Label" => 	"#Flugverbotszone",
									"Warnung" => "CTR",
									"ID" 	=>	"LSZO Luzern-Beromünster FV"),
							 //Array(	"Name" 	=> 	 "CTR EMMEN (MIL) 1 (bis 150m)/ #150mFlugzone D: 15.48 NP: 9",
							 // Seit 1.1.2023 nicht mehr relevant, da max Flughöhe 120m ist
							 //Array(	"Name" 	=> 	 "CTR EMMEN (MIL) 1 (bis 150m)",
							//		"Label" => 	"#150mFlugzone",
							//		"Warnung" => "CTR",
							//		"ID" 	=>	"CTR EMMEN (MIL) 1 (bis 150m)"),
							 //Array(	"Name" 	=> 	 "CTR ZURICH 1 (bis 150m)/ #150mFlugzone D: 17.03 NP: 34",
							 // Seit 1.1.2023 nicht mehr relevant, da max Flughöhe 120m ist
							 //Array(	"Name" 	=> 	 "CTR ZURICH 1 (bis 150m)",
							//		"Label" => 	"#150mFlugzone",
							//		"Warnung" => "CTR",
							//		"ID" 	=>	"CTR ZURICH 1 (bis 150m)"),
							 //Array(	"Name" 	=> 	 "CTR ZURICH 1 (bis 150m)/ #150mFlugzone D: 17.03 NP: 34",
							 // Seit 1.1.2023 nicht mehr relevant, da max Flughöhe 120m ist
							 //Array(	"Name" 	=> 	 "CTR ZURICH 1 (bis 150m)",
							//		"Label" => 	"#150mFlugzone",
							//		"Warnung" => "CTR",
							//		"ID" 	=>	"CTR ZURICH 1 (bis 150m)"),
							 //Array(	"Name" 	=> 	 "LSXS Schindellegi/ #Flugverbotszone D: 19.22 NP: 34",
							 Array(	"Name" 	=> 	 "LSXS Schindellegi",
									"Label" => 	"#Flugverbotszone",
									"Warnung" => "CTR",
									"ID" 	=>	"LSXS Schindellegi FV"),
							 //Array(	"Name" 	=> 	 "CTR ZURICH 2 (bis 150m)/ #150mFlugzone D: 24.41 NP: 8",
							 // Seit 1.1.2023 nicht mehr relevant, da max Flughöhe 120m ist
							 //Array(	"Name" 	=> 	 "CTR ZURICH 2 (bis 150m)",
							//		"Label" => 	"#150mFlugzone",
							//		"Warnung" => "CTR",
							//		"ID" 	=>	"CTR ZURICH 2 (bis 150m)"),
							 //Array(	"Name" 	=> 	 "CTR ZURICH 2 (bis 150m)/ #150mFlugzone D: 24.41 NP: 8",
							 // Seit 1.1.2023 nicht mehr relevant, da max Flughöhe 120m ist
							 //Array(	"Name" 	=> 	 "CTR ZURICH 2 (bis 150m)",
							//		"Label" => 	"#150mFlugzone",
							//		"Warnung" => "CTR",
							//		"ID" 	=>	"CTR ZURICH 2 (bis 150m)"),
							 //Array(	"Name" 	=> 	 "CTR ALPNACH (MIL) (bis 150m)/ #150mFlugzone D: 26.40 NP: 7",
							 //Array(	"Name" 	=> 	 "CTR ALPNACH (MIL) (bis 150m)/ #150mFlugzone D: 26.40 NP: 7",
							//		"Label" => 	"#150mFlugzone",
							//		"Warnung" => "CTR",
							//		"ID" 	=>	"CTR ALPNACH (MIL) (bis 150m)"),
							 //Array(	"Name" 	=> 	 "CTR BUOCHS (MIL/CIV) (bis 150m)/ #150mFlugzone D: 26.40 NP: 7",
							 // Seit 1.1.2023 nicht mehr relevant, da max Flughöhe 120m ist
							 //Array(	"Name" 	=> 	 "CTR BUOCHS (MIL/CIV) (bis 150m)",
							//		"Label" => 	"#150mFlugzone",
							//		"Warnung" => "CTR",
							//		"ID" 	=>	"CTR BUOCHS (MIL/CIV) (bis 150m)"),
							 //Array(	"Name" 	=> 	 "CTR EMMEN (MIL) 2 (bis 150m)/ #150mFlugzone D: 26.90 NP: 8",
							 Array(	"Name" 	=> 	 "CTR EMMEN (MIL) 2 (bis 150m)",
									"Label" => 	"#150mFlugzone",
									"Warnung" => "CTR",
									"ID" 	=>	"CTR EMMEN (MIL) 2 (bis 150m)"),
							 //Array(	"Name" 	=> 	 "LSZC Buochs/ #Flugverbotszone D: 27.62 NP: 31",
							 Array(	"Name" 	=> 	 "LSZC Buochs",
									"Label" => 	"#Flugverbotszone",
									"Warnung" => "CTR",
									"ID" 	=>	"LSZC Buochs FV"),
							 //Array(	"Name" 	=> 	 "LSMD Dübendorf/ #Flugverbotszone D: 28.08 NP: 37",
							 Array(	"Name" 	=> 	 "LSMD Dübendorf",
									"Label" => 	"#Flugverbotszone",
									"Warnung" => "CTR",
									"ID" 	=>	"LSMD Dübendorf FV"),
							 //Array(	"Name" 	=> 	 "LSMD Dübendorf/ #Flugverbotszone D: 28.08 NP: 37",
							 Array(	"Name" 	=> 	 "LSMD Dübendorf",
									"Label" => 	"#Flugverbotszone",
									"Warnung" => "CTR",
									"ID" 	=>	"LSMD Dübendorf FV"),
							 //Array(	"Name" 	=> 	 "LSMA Alpnach/ #Flugverbotszone D: 28.20 NP: 33",
							 Array(	"Name" 	=> 	 "LSMA Alpnach",
									"Label" => 	"#Flugverbotszone",
									"Warnung" => "CTR",
									"ID" 	=>	"LSMA Alpnach FV"),
							 //Array(	"Name" 	=> 	 "LSZH Zürich/ #Flugverbotszone D: 30.80 NP: 42",
							 Array(	"Name" 	=> 	 "LSZH Zürich",
									"Label" => 	"#Flugverbotszone",
									"Warnung" => "CTR",
									"ID" 	=>	"LSZH Zürich FV"),
							 //Array(	"Name" 	=> 	 "LSZH Zürich/ #Flugverbotszone D: 30.80 NP: 42",
							 Array(	"Name" 	=> 	 "LSZH Zürich",
									"Label" => 	"#Flugverbotszone",
									"Warnung" => "CTR",
									"ID" 	=>	"LSZH Zürich FV"),
							 //Array(	"Name" 	=> 	 "LSZK Speck-Fehraltorf/ #Flugverbotszone D: 30.83 NP: 15",
							 Array(	"Name" 	=> 	 "LSZK Speck-Fehraltorf",
									"Label" => 	"#Flugverbotszone",
									"Warnung" => "CTR",
									"ID" 	=>	"LSZK Speck-Fehraltorf FV"),
							 //Array(	"Name" 	=> 	 "LSZK Speck-Fehraltorf/ #Flugverbotszone D: 30.83 NP: 15",
							 Array(	"Name" 	=> 	 "LSZK Speck-Fehraltorf",
									"Label" => 	"#Flugverbotszone",
									"Warnung" => "CTR",
									"ID" 	=>	"LSZK Speck-Fehraltorf FV"),
							 //Array(	"Name" 	=> 	 "CTR DUEBENDORF (bis 150m)/ #150mFlugzone D: 33.10 NP: 7",
							 // Seit 1.1.2023 nicht mehr relevant, da max Flughöhe 120m ist
							 //Array(	"Name" 	=> 	 "CTR DUEBENDORF (bis 150m)",
							//		"Label" => 	"#150mFlugzone",
							//		"Warnung" => "CTR",
							//		"ID" 	=>	"CTR DUEBENDORF (bis 150m) FV"),
							 //Array(	"Name" 	=> 	 "CTR DUEBENDORF (bis 150m)/ #150mFlugzone D: 33.10 NP: 7",
							 // Seit 1.1.2023 nicht mehr relevant, da max Flughöhe 120m ist
							 //Array(	"Name" 	=> 	 "CTR DUEBENDORF (bis 150m)",
							//		"Label" => 	"#150mFlugzone",
							//		"Warnung" => "CTR",
							//		"ID" 	=>	"CTR DUEBENDORF (bis 150m) FV"),
							 //Array(	"Name" 	=> 	 "LSZF Birrfeld/ #Flugverbotszone D: 34.57 NP: 23",
							 Array(	"Name" 	=> 	 "LSZF Birrfeld",
									"Label" => 	"#Flugverbotszone",
									"Warnung" => "CTR",
									"ID" 	=>	"LSZF Birrfeld FV"),
							 //Array(	"Name" 	=> 	 "LSZF Birrfeld/ #Flugverbotszone D: 34.57 NP: 23",
							 Array(	"Name" 	=> 	 "LSZF Birrfeld",
									"Label" => 	"#Flugverbotszone",
									"Warnung" => "CTR",
									"ID" 	=>	"LSZF Birrfeld FV"),
							 //Array(	"Name" 	=> 	 "LSPV Wangen-Lachen und LSPW Wangen/ #Flugverbotszone D: 35.82 NP: 77",
							 Array(	"Name" 	=> 	 "LSPV Wangen-Lachen und LSPW Wangen",
									"Label" => 	"#Flugverbotszone",
									"Warnung" => "CTR",
									"ID" 	=>	"LSPV Wangen-Lachen und LSPW Wangen FV"),
							 //Array(	"Name" 	=> 	 "LSPG Kägiswil/ #Flugverbotszone D: 36.04 NP: 37",
							 Array(	"Name" 	=> 	 "LSPG Kägiswil",
									"Label" => 	"#Flugverbotszone",
									"Warnung" => "CTR",
									"ID" 	=>	"LSPG Kägiswil FV"),
							 //Array(	"Name" 	=> 	 "LSPH Winterthur (Flugverbot)/ #Flugverbotszone D: 41.34 NP: 11",
							 Array(	"Name" 	=> 	 "LSPH Winterthur (Flugverbot)",
									"Label" => 	"#Flugverbotszone",
									"Warnung" => "CTR",
									"ID" 	=>	"LSPH Winterthur (Flugverbot) FV"),
							 //Array(	"Name" 	=> 	 "LSPH Winterthur (Flugverbot)/ #Flugverbotszone D: 41.34 NP: 11",
							 Array(	"Name" 	=> 	 "LSPH Winterthur (Flugverbot)",
									"Label" => 	"#Flugverbotszone",
									"Warnung" => "CTR",
									"ID" 	=>	"LSPH Winterthur (Flugverbot) FV"),
							//Kantonale Flugrestriktionen
							 Array(	"Name" 	=> 	 "Sicherheitsstützpunkt_Biberbrugg_600m",
									"Label" => 	"#KtFlugVerbot",
									"Warnung" => "FVB",
									"ID" 	=>	"Sicherheitsstützpunkt_Biberbrugg_600m"),
							
							 Array(	"Name" 	=> 	 "Unterspannwerk_Samstagern_600m",
									"Label" => 	"#KtFlugRestriction",
									"Warnung" => "USP",
									"ID" 	=>	"Unterspannwerk_Samstagern_600m"),
							
							 Array(	"Name" 	=> 	 "Strafanstalt_Menzingen_600m",
									"Label" => 	"#KtFlugVerbot",
									"Warnung" => "FVB",
									"ID" 	=>	"Strafanstalt_Menzingen_600m"),
							
							 Array(	"Name" 	=> 	 "Unterspannwerk_Höfen_600m",
									"Label" => 	"#KtFlugRestriction",
									"Warnung" => "USP",
									"ID" 	=>	"Unterspannwerk_Höfen_600m"),
							
							 Array(	"Name" 	=> 	 "Unterspannwerk Mettlen_600m",
									"Label" => 	"#KtFlugRestriction",
									"Warnung" => "USP",
									"ID" 	=>	"Unterspannwerk Mettlen_600m"),
							
							 Array(	"Name" 	=> 	 "Unterspannwerk_Obfelden_600m",
									"Label" => 	"#KtFlugRestriction",
									"Warnung" => "USP",
									"ID" 	=>	"Unterspannwerk_Obfelden_600m"),
							
							 Array(	"Name" 	=> 	 "Polizeiposten Affoltern_600m",
									"Label" => 	"#KtFlugVerbot",
									"Warnung" => "FVB",
									"ID" 	=>	"Polizeiposten Affoltern_600m"),
							
							 Array(	"Name" 	=> 	 "Bezirksgericht_Horgen_600m",
									"Label" => 	"#KtFlugVerbot",
									"Warnung" => "FVB",
									"ID" 	=>	"Bezirksgericht_Horgen_600m"),
							
							 Array(	"Name" 	=> 	 "Strafanstalt_Zug_600m",
									"Label" => 	"#KtFlugVerbot",
									"Warnung" => "FVB",
									"ID" 	=>	"Strafanstalt_Zug_600m"),
							
							 Array(	"Name" 	=> 	 "Benzinlager Rotkreuz",
									"Label" => 	"#KtFlugVerbot",
									"Warnung" => "FVB",
									"ID" 	=>	"Benzinlager Rotkreuz"),
												),

//Morgensonne
			"Sun" => array(
							Array(	"Name" 	=> 	 "SUN Kalleren",
									"Label" => 	"#Sun",
									"Warnung" => "SUN",
									"ID" 	=>	"Kalleren"),
							Array(	"Name" 	=> 	 "SUN Lindenberg",
									"Label" => 	"#Sun",
									"Warnung" => "SUN",
									"ID" 	=>	"Lindenberg"),
							Array(	"Name" 	=> 	 "SUN Mühlau",
									"Label" => 	"#Sun",
									"Warnung" => "SUN",
									"ID" 	=>	"Mühlau"),
							Array(	"Name" 	=> 	 "SUN Dietwil",
									"Label" => 	"#Sun",
									"Warnung" => "SUN",
									"ID" 	=>	"Dietwil"),
							Array(	"Name" 	=> 	 "SUN Muri",
									"Label" => 	"#Sun",
									"Warnung" => "SUN",
									"ID" 	=>	"Muri"),
							Array(	"Name" 	=> 	 "SUN Benzenschwil",
									"Label" => 	"#Sun",
									"Warnung" => "SUN",
									"ID" 	=>	"Benzenschwil"),
							Array(	"Name" 	=> 	 "SUN Albis",
									"Label" => 	"#Sun",
									"Warnung" => "SUN",
									"ID" 	=>	"Albis"),
							Array(	"Name" 	=> 	 "SUN Meierskappel",
									"Label" => 	"#Sun",
									"Warnung" => "SUN",
									"ID" 	=>	"Meierskappel"),
							Array(	"Name" 	=> 	 "SUN Zugerberg",
									"Label" => 	"#Sun",
									"Warnung" => "SUN",
									"ID" 	=>	"Zugerberg"),
							Array(	"Name" 	=> 	 "SUN Menzingen",
									"Label" => 	"#Sun",
									"Warnung" => "SUN",
									"ID" 	=>	"Menzingen"),
												),

//SORA
			"Sora" => array(
							Array(	"Name" 	=> 	 "SORA Hauptikon",
									"Label" => 	"#Sora",
									"Warnung" => "SORA",
									"ID" 	=>	"Hauptikon"),
							Array(	"Name" 	=> 	 "SORA Uerzlikon",
									"Label" => 	"#Sora",
									"Warnung" => "SORA",
									"ID" 	=>	"Uerzlikon"),
							Array(	"Name" 	=> 	 "SORA Rossau",
									"Label" => 	"#Sora",
									"Warnung" => "SORA",
									"ID" 	=>	"Rossau"),
							Array(	"Name" 	=> 	 "SORA Baar",
									"Label" => 	"#Sora",
									"Warnung" => "SORA",
									"ID" 	=>	"Baar"),
							Array(	"Name" 	=> 	 "SORA Zug",
									"Label" => 	"#Sora",
									"Warnung" => "SORA",
									"ID" 	=>	"Zug"),
							Array(	"Name" 	=> 	 "SORA Steinhausen",
									"Label" => 	"#Sora",
									"Warnung" => "SORA",
									"ID" 	=>	"Steinhausen"),
							Array(	"Name" 	=> 	 "SORA Cham",
									"Label" => 	"#Sora",
									"Warnung" => "SORA",
									"ID" 	=>	"Cham"),
							Array(	"Name" 	=> 	 "SORA Hünenberg",
									"Label" => 	"#Sora",
									"Warnung" => "SORA",
									"ID" 	=>	"Hünenberg"),
							Array(	"Name" 	=> 	 "SORA Oberwil",
									"Label" => 	"#Sora",
									"Warnung" => "SORA",
									"ID" 	=>	"Oberwil"),
							Array(	"Name" 	=> 	 "SORA Walchwil",
									"Label" => 	"#Sora",
									"Warnung" => "SORA",
									"ID" 	=>	"Walchwil"),
							Array(	"Name" 	=> 	 "SORA Unterägeri",
									"Label" => 	"#Sora",
									"Warnung" => "SORA",
									"ID" 	=>	"Unterägeri"),
							Array(	"Name" 	=> 	 "SORA Oberägeri",
									"Label" => 	"#Sora",
									"Warnung" => "SORA",
									"ID" 	=>	"Oberägeri"),
							Array(	"Name" 	=> 	 "SORA Menzingen",
									"Label" => 	"#Sora",
									"Warnung" => "SORA",
									"ID" 	=>	"Menzingen"),
							Array(	"Name" 	=> 	 "SORA Edlibach",
									"Label" => 	"#Sora",
									"Warnung" => "SORA",
									"ID" 	=>	"Edlibach"),
							Array(	"Name" 	=> 	 "SORA Neuheim",
									"Label" => 	"#Sora",
									"Warnung" => "SORA",
									"ID" 	=>	"Neuheim"),
							Array(	"Name" 	=> 	 "SORA Allenwinden",
									"Label" => 	"#Sora",
									"Warnung" => "SORA",
									"ID" 	=>	"Allenwinden"),
							Array(	"Name" 	=> 	 "SORA Bösch",
									"Label" => 	"#Sora",
									"Warnung" => "SORA",
									"ID" 	=>	"Bösch"),
							Array(	"Name" 	=> 	 "SORA Rotkreuz",
									"Label" => 	"#Sora",
									"Warnung" => "SORA",
									"ID" 	=>	"Rotkreuz"),
							Array(	"Name" 	=> 	 "SORA Buonas",
									"Label" => 	"#Sora",
									"Warnung" => "SORA",
									"ID" 	=>	"Buonas"),
							Array(	"Name" 	=> 	 "SORA Risch",
									"Label" => 	"#Sora",
									"Warnung" => "SORA",
									"ID" 	=>	"Risch"),
							Array(	"Name" 	=> 	 "SORA Oberrüti",
									"Label" => 	"#Sora",
									"Warnung" => "SORA",
									"ID" 	=>	"Oberrüti"),
							Array(	"Name" 	=> 	 "SORA Sins",
									"Label" => 	"#Sora",
									"Warnung" => "SORA",
									"ID" 	=>	"Sins"),
							Array(	"Name" 	=> 	 "SORA Aettenschwil",
									"Label" => 	"#Sora",
									"Warnung" => "SORA",
									"ID" 	=>	"Aettenschwil"),
							Array(	"Name" 	=> 	 "SORA Auw",
									"Label" => 	"#Sora",
									"Warnung" => "SORA",
									"ID" 	=>	"Auw"),
							Array(	"Name" 	=> 	 "SORA Dietwil",
									"Label" => 	"#Sora",
									"Warnung" => "SORA",
									"ID" 	=>	"Dietwil"),
							Array(	"Name" 	=> 	 "SORA Abtwil",
									"Label" => 	"#Sora",
									"Warnung" => "SORA",
									"ID" 	=>	"Abtwil"),
							Array(	"Name" 	=> 	 "SORA Hagendorn",
									"Label" => 	"#Sora",
									"Warnung" => "SORA",
									"ID" 	=>	"Hagendorn"),
							Array(	"Name" 	=> 	 "SORA Lindencham",
									"Label" => 	"#Sora",
									"Warnung" => "SORA",
									"ID" 	=>	"Lindencham"),
							Array(	"Name" 	=> 	 "SORA Langacker",
									"Label" => 	"#Sora",
									"Warnung" => "SORA",
									"ID" 	=>	"Langacker"),
							Array(	"Name" 	=> 	 "SORA Knonau",
									"Label" => 	"#Sora",
									"Warnung" => "SORA",
									"ID" 	=>	"Knonau"),
							Array(	"Name" 	=> 	 "SORA Maschwanden",
									"Label" => 	"#Sora",
									"Warnung" => "SORA",
									"ID" 	=>	"Maschwanden"),
							Array(	"Name" 	=> 	 "SORA Mettmenstten",
									"Label" => 	"#Sora",
									"Warnung" => "SORA",
									"ID" 	=>	"Mettmenstten"),
							Array(	"Name" 	=> 	 "SORA Rifferswil",
									"Label" => 	"#Sora",
									"Warnung" => "SORA",
									"ID" 	=>	"Rifferswil"),
							Array(	"Name" 	=> 	 "SORA Hausen a.A.",
									"Label" => 	"#Sora",
									"Warnung" => "SORA",
									"ID" 	=>	"Hausen a.A."),
							Array(	"Name" 	=> 	 "SORA Ebertswil",
									"Label" => 	"#Sora",
									"Warnung" => "SORA",
									"ID" 	=>	"Ebertswil"),
							Array(	"Name" 	=> 	 "SORA Hirzwangen",
									"Label" => 	"#Sora",
									"Warnung" => "SORA",
									"ID" 	=>	"Hirzwangen"),
							Array(	"Name" 	=> 	 "SORA Kappel a.A.",
									"Label" => 	"#Sora",
									"Warnung" => "SORA",
									"ID" 	=>	"Kappel a.A."),
							Array(	"Name" 	=> 	 "SORA Sihlbrugg",
									"Label" => 	"#Sora",
									"Warnung" => "SORA",
									"ID" 	=>	"Sihlbrugg"),
							Array(	"Name" 	=> 	 "SORA Aeugst a.A.",
									"Label" => 	"#Sora",
									"Warnung" => "SORA",
									"ID" 	=>	"Aeugst a.A."),
							Array(	"Name" 	=> 	 "SORA Affoltern a.A.",
									"Label" => 	"#Sora",
									"Warnung" => "SORA",
									"ID" 	=>	"Affoltern a.A."),
							Array(	"Name" 	=> 	 "SORA Obfelden",
									"Label" => 	"#Sora",
									"Warnung" => "SORA",
									"ID" 	=>	"Obfelden"),
							Array(	"Name" 	=> 	 "SORA Mühlau",
									"Label" => 	"#Sora",
									"Warnung" => "SORA",
									"ID" 	=>	"Mühlau"),
							Array(	"Name" 	=> 	 "SORA Fenkenrieden",
									"Label" => 	"#Sora",
									"Warnung" => "SORA",
									"ID" 	=>	"Fenkenrieden"),
							Array(	"Name" 	=> 	 "SORA Alikon",
									"Label" => 	"#Sora",
									"Warnung" => "SORA",
									"ID" 	=>	"Alikon"),
							Array(	"Name" 	=> 	 "SORA Gisikon",
									"Label" => 	"#Sora",
									"Warnung" => "SORA",
									"ID" 	=>	"Gisikon"),
							Array(	"Name" 	=> 	 "SORA Meierskappel",
									"Label" => 	"#Sora",
									"Warnung" => "SORA",
									"ID" 	=>	"Meierskappel"),
							Array(	"Name" 	=> 	 "SORA Rüstenschwil",
									"Label" => 	"#Sora",
									"Warnung" => "SORA",
									"ID" 	=>	"Rüstenschwil"),
							Array(	"Name" 	=> 	 "SORA Beinwil",
									"Label" => 	"#Sora",
									"Warnung" => "SORA",
									"ID" 	=>	"Beinwil"),
							Array(	"Name" 	=> 	 "SORA Dachlissen",
									"Label" => 	"#Sora",
									"Warnung" => "SORA",
									"ID" 	=>	"Dachlissen"),
												),
											);


// =====================
// Functions
// =====================

/* obsolete with SwissConverter.php 31.03.2023

// Sucht den nächsten Referenzpunkt in Referenzpunktliste
function GetIDofClosestReference($Point) {
	global $LV95PointReferences;
	
	$i=0;
	$ClosestID=0;
	$ClosestDistance=1000000;

	foreach ($LV95PointReferences as $RefPoint) {
		//pre_R($RefPoint);
		$Distance = twopoints_on_earth($RefPoint,$Point);
		//echo "Distance: ". $i . " " . $Distance . "<br>";
	
		if ($Distance < $ClosestDistance) {
			$ClosestDistance = $Distance;
			$ClosestID = $i;
		}
		$i++;

	}
	//echo "CloseID: ". $ClosestID . "<br>";
	return ($LV95PointReferences[$ClosestID]);
}
*/ 

// Berechnet die Distanz zwischen zwei Punkten LON/LAT in km
 function twopoints_on_earth($PointFrom, $PointTo)
{
// This code is contributed by akash1295
// https://auth.geeksforgeeks.org/user/akash1295/articles
// MyHome
// LAT 47.225174336450436
// Long 8.458050163460864


   if (!is_array($PointFrom) || !is_array($PointTo)) {
	   return 0;
   }
   $long1 = deg2rad((float)($PointFrom["Lon"] ?? 0));
   $long2 = deg2rad((float)($PointTo["Lon"] ?? 0));
   $lat1 = deg2rad((float)($PointFrom["Lat"] ?? 0));
   $lat2 = deg2rad((float)($PointTo["Lat"] ?? 0));
	 
   //Haversine Formula
   $dlong = $long2 - $long1;
   $dlati = $lat2 - $lat1;
	 
   $val = pow(sin($dlati/2),2)+cos($lat1)*cos($lat2)*pow(sin($dlong/2),2);
	 
   $res = 2 * asin(sqrt($val));
	 
   $radius = 3958.756;
   $milesToKm = 1.60934;
	 
   return ($milesToKm*$res*$radius);

}

  

// ==================
// Geofencing
// ==================

//Bestimmt das Vorzeichen einer Zahl
function Sign($Num) {
	if ($Num<0) {
		return (-1);
	}
	if ($Num>0) {
		return (1);
	}
	return (0);
}

// Berechnet den Winkel Gamma eines Dreiecks
function GetGammaAngleFromTriangle($A1, $A2, $B1, $B2, $C1, $C2) {
	// Triangle with Points A, B, C calulating the angle C
	// Position of the Points A, B, C are defined in Coordinates (A -> A1, A2) ...

	$ZoomFactor = 1;
	$va1 = ($A1 - $C1)*$ZoomFactor;
	$va2 = ($A2 - $C2)*$ZoomFactor;
	$vb1 = ($B1 - $C1)*$ZoomFactor;
	$vb2 = ($B2 - $C2)*$ZoomFactor;

	//Winkelbestimmung
	$den = (sqrt( pow($va1, 2)+ pow($va2, 2)) * sqrt( pow($vb1, 2)+pow($vb2, 2)));
	if ($den == 0 || !is_finite($den)) {
		return 0;
	}
	$COSC = (($va1 * $vb1)+($va2 * $vb2)) / $den;
	if (!is_finite($COSC)) {
		return 0;
	}
	if ($COSC > 1) { $COSC = 1; }
	if ($COSC < -1) { $COSC = -1; }
	//Kreuzprodukt zur Richtungsbestimmung
	$KPC = (($va1 * $vb2) - ($vb1 * $va2));

	//echo "(" . $va1 . "," . $va2 .")" . " (" . $vb1 . "," . $vb2 .")" ." COSC ". $COSC . " KPC ". $KPC . " Sign ". (Sign($KPC)) ."<br>";
	
	Return (-1* Sign($KPC)*(acos($COSC)/pi()*180));
	
}

//Schaut ob ein Punkt innerhalb eines Polynoms ist
//Rückgabewert: in/ out/ on
//Format Punkt: P[Lon] X/LON, P[Lat] - Y/LAT
//Area: List of points (Lon, Lat, height)
function PointInArea($PointToCheck, $Area, $step=1) {
	// Summe aller Winkel der Dreicke = 2Pi -> innerhalb, = 0 -> ausserhalb
	// B ist der Punkt gesucht
	// A und B sind folgende Flächenpunkt
	
	//pre_r($Area);
	
	$i=0;
	$B1=0;
	$B2=0;
	$C1=$PointToCheck["Lon"];
	$C2=$PointToCheck["Lat"];
	$TotAngle = 0;

	if (!is_array($Area)) {
		return "Out";
	}
	$stepper = 0;
	foreach ($Area as $Point) {
	  if ($stepper >= $step) {
		  $stepper= 0;
	  }	  
	  if ($stepper==0) {
		  $A1= $B1;
		  $A2= $B2;
		  if (is_array($Point)) {
			  $PointData = $Point;
		  } else {
			  $PointData = explode(",", (string)$Point);
		  }
		  $B1= $PointData[0] ?? 0;
		  $B2= $PointData[1] ?? 0;

		  if ($i>0) {
			  $AngleC = GetGammaAngleFromTriangle($A1, $A2, $B1, $B2, $C1, $C2);
			  $TotAngle= $TotAngle + $AngleC;
			  //echo $i .": (" . $A1 . "," . $A2 .")" . " (" . $B1 . "," . $B2 .")" ." (" . $C1 . ","  .  $C2 .")"  . " AngleC: " . $AngleC . " / " . $TotAngle . "<br>";
		  }
		  $i++;
	  }
      $stepper++;
	}
	$TotAngle = abs($TotAngle);
	// inside
	//echo "Winkel: " . $TotAngle . "<br>";
	if ($TotAngle>359.08) {
		Return ("In");
	}
	if ($TotAngle<0.02) {
		Return ("Out");
	}
	Return ("On");
	
}



//Schaut ob ein Punkt innerhalb eines Polynoms ist
//Rückgabewert: in/ out/ on
//Format Punkt: P[0] X/LON, P[1] - Y/LAT
function PointInPolygon($PointToCheck, $Area, $step=1) {
	// Summe aller Winkel der Dreicke = 2Pi -> innerhalb, = 0 -> ausserhalb
	// B ist der Punkt gesucht
	// A und B sind folgende Flächenpunkt

	$i=0;
	$B1=0;
	$B2=0;
	$C1=$PointToCheck[0];
	$C2=$PointToCheck[1];
	$TotAngle = 0;

	$stepper = 0;
	foreach ($Area as $Point) {
	  if ($stepper >= $step) {
		  $stepper= 0;
	  }	  
	  if ($stepper==0) {
		  $A1= $B1;
		  $A2= $B2;
		  $B1= $Point[0];
		  $B2= $Point[1];

		  if ($i>0) {
			  $AngleC = GetGammaAngleFromTriangle($A1, $A2, $B1, $B2, $C1, $C2);
			  $TotAngle= $TotAngle + $AngleC;
			  //echo $i .": (" . $A1 . "," . $A2 .")" . " (" . $B1 . "," . $B2 .")" ." (" . $C1 . ","  .  $C2 .")"  . " AngleC: " . $AngleC . " / " . $TotAngle . "<br>";
		  }
		  $i++;
	  }
      $stepper++;
	}
	
	// inside
	if ($TotAngle>359.999) {
		Return ("In");
	}
	if ($TotAngle<0.001) {
		Return ("Out");
	}
	Return ("On");
	
}

//Testet die PointInPolygon und die Distanz Funktion
Function Test_GeoFormulas($Title, $PointToCheck, $RefPoint ,$Area) {
	//In/ Out or On
	$TotAngle = PointInPolygon( $PointToCheck, $Area);
	echo $Title . " (In/Out/On): " . $TotAngle . "<br>";
	//Distance
	$Distance= twopoints_on_earth( $PointToCheck[1], $PointToCheck[0], $RefPoint[1],   $RefPoint[0]);
	echo "Distance " . $Distance .' '. 'km' . '<br>';
}
// Berechnet einen LON/LAT Punkt den LSV95 Koordinaten mit einer formel von GeoMap
Function GetLSV95EastNorthFromLonLat ($Point) {
	//echo "Point LAT: ". $Point["Lat"] . ", ,LON:". $Point["Lon"] ."<br>";
	$LV95EastNorth["LV95East"] = WGStoCHx($Point["Lat"], $Point["Lon"]); 
	$LV95EastNorth["LV95North"] = WGStoCHy($Point["Lat"], $Point["Lon"]); 
	$LV95EastNorth= fromWGSToMN95($Point["Lat"], $Point["Lon"]);
	//echo " LV95East, LV95North: " . $LV95EastNorth["LV95East"] .", " .  $LV95EastNorth["LV95North"] . "<br>";

	Return($LV95EastNorth);
}

/*
// Nähert einen LON/LAT Punkt den LSV95 Koordinaten über eine Referenzpunktliste an
Function GetLSV95EastNorthFromLonLat ($Point) {
	global $LV95PointReferences;
	
	echo "Point LAT, LON: ". $Point["Lat"] . ", ". $Point["Lon"] ."<br>";
	$FactorEast = ($LV95PointReferences[0]["LV95East"] - $LV95PointReferences[1]["LV95East"])/($LV95PointReferences[0]["Lon"] - $LV95PointReferences[1]["Lon"]);
	$FactorNorth = ($LV95PointReferences[0]["LV95North"] - $LV95PointReferences[1]["LV95North"])/($LV95PointReferences[0]["Lat"] - $LV95PointReferences[1]["Lat"]);

	$LV95RefPoint = GetIDofClosestReference($Point);
	//pre_R($LV95RefPoint);
	
	//echo "dEast: " . ($LV95PointReferences[1]["LV95East"] - $LV95PointReferences[0]["LV95East"]) . " dLong :" . ($LV95PointReferences[1]["Lon"] - $LV95PointReferences[0]["Lon"]). "  FactorEast :" .$FactorEast ."<br>";
	//echo "dNorth: " . ($LV95PointReferences[1]["LV95North"] - $LV95PointReferences[0]["LV95North"]) . " dLAT :" . ($LV95PointReferences[1]["Lat"] - $LV95PointReferences[0]["Lat"]). "  FactorNorth :" . $FactorNorth ."<br>";
	
	//echo  "<br>";

	//echo "Lon: " . $Point["Lon"] . " LV95East: " . ($LV95PointReferences[0]["LV95East"] + (($Point["Lon"] - $LV95PointReferences[0]["Lon"]) * $FactorEast)) . "<br>";
	//echo "Lat: " . $Point["Lat"] . " LV95North: " . ($LV95PointReferences[0]["LV95North"] + (($Point["Lat"] - $LV95PointReferences[0]["Lat"])* $FactorNorth)) . "<br>";
	//echo " LV95East, LV95North: " . ($LV95PointReferences[0]["LV95East"] + (($Point["Lon"] - $LV95PointReferences[0]["Lon"]) * $FactorEast)) .", " .  ($LV95PointReferences[0]["LV95North"] + (($Point["Lat"] - $LV95PointReferences[0]["Lat"])* $FactorNorth)) . "<br>";

	$LV95EastNorth["LV95East"] = ($LV95RefPoint["LV95East"] + (($Point["Lon"] - $LV95RefPoint["Lon"]) * $FactorEast)); 
	$LV95EastNorth["LV95North"] = ($LV95RefPoint["LV95North"] + (($Point["Lat"] - $LV95RefPoint["Lat"])* $FactorNorth)); 

	//pre_r($LV95EastNorth);
	//echo " LV95East, LV95North: " . $LV95EastNorth[0] .", " .  $LV95EastNorth[1] . "<br>";
	Return($LV95EastNorth);

}
*/



// Einlesen bestehender POI Daten (JSON)
function GetPoiArray($catalogPath) {
	$PoiArray = array();
	if (function_exists('rkrk_poi_load')) {
		$loaded = rkrk_poi_load($catalogPath);
		if (is_array($loaded) && $loaded !== array()) {
			return $loaded;
		}
	}
	if (function_exists('rkrk_catalog_load_simplexml')) {
		$xml_felder = rkrk_catalog_load_simplexml($catalogPath);
		if ($xml_felder) {
			$decoded = json_decode(json_encode($xml_felder), true);
			if (is_array($decoded)) {
				return $decoded;
			}
		}
	}
	return $PoiArray;
}

function GetPoiArrayFromXML($xmlMissionLocation) {
	return GetPoiArray($xmlMissionLocation);
}


// Pürfe in welche Area (Gde, JG etc.) ein Punkt fällt
// $Type: Gde, Jgd, Kt
// $Point [0] - LAT, [1] - LON


function GetAreaOfPoint ($Type, $PointToCheck, $PoiArray ,$NumPointsToCheck=500) {
global $searchSDZ, $replaceSDZ,$AreaStopDistance, $AreaStopDistance_SORA;
	// Set StyleURL search
	if (!is_array($PoiArray)) {
		return null;
	}
	$TypSearch=array();
	switch ($Type) {
	case "Gde":
		$TypSearch=Array("#GDE_KNAMT","#GDE_AMT", "#GDE_OberesFreiamtAG", "#GDE_ZG", "#GDE_SZ", "#GDE_LU");
		break;
	case "Jgd":
		$TypSearch=Array("#JGB_KNAMT","#JGB_AG","#JGB_OberesFreiamtAG","#JGB_ZG","#JGB_SZ","#JGB_LU");
		break;
	case "FlgZ":
		$TypSearch=Array("#Flugverbotszone","#Nachtflugzone","#Nachtflugzone1","#Nachtflugzone2","#Nachtflugzone3","#70mFlugzone","#120mFlugzone","#KtFlugVerbot","#KtFlugRestriction", "NaturschutzFlugverbotszone");
		break;
	case "Kt":
		$TypSearch=array();
		break;
	case "Sun":
		$TypSearch=Array("#Sun");
		break;
	case "Sora":
		$TypSearch=Array("#Sora");
		break;
	}
	// Check distance 
	$i=0;
	$PoiDistanceList = array();
	foreach ($PoiArray as $key => $PoiList) {
		if (!is_array($PoiList)) {
			continue;
		}
		foreach ($PoiList as $key => $PoiEntry) {
			if (!is_array($PoiEntry)) {
				continue;
			}
			//pre_r($PoiEntry);
			//echo $i . " " .$PoiEntry["styleUrl"] . "<br>";
			$styleUrl = function_exists('rkrk_str') ? rkrk_str($PoiEntry["styleUrl"] ?? '') : (string)($PoiEntry["styleUrl"] ?? '');
			if ((in_array($styleUrl, $TypSearch)) or false) {
				//pre_r($PoiEntry);
				//echo $i . " " .$PoiEntry["styleUrl"] .  " " .$PoiEntry["name"] . "<br>";
				$coordRaw = $PoiEntry["coordinates"] ?? '';
				if (is_array($coordRaw) || is_object($coordRaw)) {
					$coordRaw = implode(' ', array_map(function ($c) {
						return is_array($c) ? implode(',', $c) : (string)$c;
					}, (array)$coordRaw));
				} else {
					$coordRaw = (string)$coordRaw;
				}
				$coordNorm = trim((string)preg_replace('!\s+!', ' ', $coordRaw));
				$PoiDistanceList[$i]["Num"] = $key;
				$poiName = $PoiEntry["name"] ?? '';
				if (is_array($poiName) || is_object($poiName)) {
					$poiName = '';
				}
				$PoiDistanceList[$i]["name"] = function_exists('rkrk_text_utf8')
					? rkrk_text_utf8((string)$poiName)
					: (string)$poiName;
				$PoiDistanceList[$i]["styleUrl"] = $styleUrl;
				//echo $i . "  " . substr($coordNorm,0, 100) . "<br>";
				
				$AreaPoints = explode("  ", substr($coordNorm, 0,50));
				//$AreaPoints = explode("  ", substr(trim($PoiEntry["coordinates"]), 0,50));
				//pre_r($AreaPoints);
				$PoiDistanceList[$i]["NumPoints"] = count($AreaPoints = explode(" ", $coordNorm));
				//echo $i . " " .$AreaPoints["coordinates"] . "<br>";
				$PoiDistanceList[$i]["Points"] = explode(" ", $coordNorm);
				
				$CheckPoint = explode(",", (string)($AreaPoints[0] ?? ''));
				//echo "Point " . $i . ":   " . $PointToCheck["Lon"] . ", " . $PointToCheck["Lat"] . "<br>";
				//echo "CheckPoint " . $i . ":   " . $CheckPoint[0] . ", " . $CheckPoint[1] . "<br>";
				$cpLon = $CheckPoint[0] ?? '';
				$cpLat = $CheckPoint[1] ?? '';
				if ($cpLon === '' || $cpLat === '' || !is_array($PointToCheck)) {
					$PoiDistanceList[$i]["Distance"] = 99999;
				} else {
					$PoiDistanceList[$i]["Distance"] = number_format(twopoints_on_earth($PointToCheck, array("Lon" => $cpLon, "Lat" => $cpLat)),2);
				}
				//echo $i . " " .$PoiEntry["styleUrl"] . " Distance: " . $PoiDistanceList[$i]["Distance"] ."<br>";
			}
			$i++;
		}
	}
	if (count($PoiDistanceList) === 0) {
		return null;
	}
	$distance_col = array_column($PoiDistanceList, "Distance");
	$name_col = array_column($PoiDistanceList, "name");
	array_multisort($distance_col, SORT_ASC, $name_col, SORT_ASC, $PoiDistanceList);
	
	//Pre_R($PoiDistanceList);
	//echo "PointToCheck: " . "Lon " . $PointToCheck["Lon"] ." Lat " . $PointToCheck["Lat"] . "<br>";
	$CheckedAreas = 0;
	foreach ($PoiDistanceList as $PoiEntry) {
		//echo $key . " " . $PoiEntry["name"] . "/ " . $PoiEntry["styleUrl"] . " D: " . $PoiEntry["Distance"] . " NP: " . $PoiEntry["NumPoints"] . "<br>";
		$Step = intval($PoiEntry["NumPoints"]/$NumPointsToCheck);
		//echo "Interval: " . $Step . "<br>";
		if ($Step<1) 
			$Step=1;
		$IsIn = PointInArea($PointToCheck , $PoiEntry["Points"], $Step);
		
		
		//echo $key . " " . $IsIn . "  - " . $PoiEntry["name"] . "  - " . $PoiEntry["Warnung"] . "<br>";
		//if (($IsIn=="In") || ($IsIn=="On")) { //korrigiert am 19.05.2024
		if (($IsIn=="In")) {
			Return Array("Status" => $IsIn, "Name" => $PoiEntry["name"]); 
			break;
		}
	}
}


// Liest PoiDaten ein und gibt Rückmeldung über POI Characteristics:
//Gemeinde, Flugzone, Jagdgebiet
// Point To Check Array (Lat/ Lon)
Function GetPoiOfPointCharacteristics($PointToCheck, $CheckOptions) {
	
	global $PoiArraySession, $xmlMissionLocation, $xml_poifile, $KMLList, $DefaultNumOfPointsChecked;

	if (function_exists('SetMissionLocation')) {
		SetMissionLocation();
	}
	if (!isset($PoiArraySession) || $PoiArraySession === '') {
		$PoiArraySession = 'PoiArray';
	}
	if (!isset($_SESSION[$PoiArraySession]) || !is_array($_SESSION[$PoiArraySession])) {
		$_SESSION[$PoiArraySession] = function_exists('GetPoiArray')
			? GetPoiArray($xmlMissionLocation.$xml_poifile)
			: array();
	}
	$PoiArray = is_array($_SESSION[$PoiArraySession]) ? $_SESSION[$PoiArraySession] : array();
	if (!isset($KMLList) || !is_array($KMLList)) {
		$KMLList = array();
	}
	//Pre_r($KMLList);
	//echo "POIARRAY " . $xmlMissionLocation.$xml_poifile . " <br>";
	//pre_r($PoiArray);
	//pre_R($CheckOptions);
	$Return = Array();
	if (in_array("Gde", $CheckOptions)) {
		$Return["Gde"] = GetAreaOfPoint ("Gde", $PointToCheck, $PoiArray , $DefaultNumOfPointsChecked );
		if (is_array($Return["Gde"]) && isset($Return["Gde"]["Name"])) {
			$gdeList = isset($KMLList["Gde"]) && is_array($KMLList["Gde"]) ? $KMLList["Gde"] : array();
			foreach ($gdeList as $Entry) {
				//echo $Entry["Name"] . "<br>";
				if ($Entry["Name"] == $Return["Gde"]["Name"]) {
					$Return["Gde"]["ID"] = $Entry["ID"];
					break;
				}
			}
		} else {
			$Return["Gde"] = array("Status" => "", "Name" => "", "ID" => "");
		}
	}
	if (in_array("Jgd", $CheckOptions)) {
		$Return["Jgd"] = GetAreaOfPoint ("Jgd", $PointToCheck, $PoiArray, $DefaultNumOfPointsChecked );
		if (is_array($Return["Jgd"]) && isset($Return["Jgd"]["Name"])) {
			$jgdList = isset($KMLList["Jgd"]) && is_array($KMLList["Jgd"]) ? $KMLList["Jgd"] : array();
			foreach ($jgdList as $Entry) {
				if ($Entry["Name"] == $Return["Jgd"]["Name"]) {
					$Return["Jgd"]["ID"] = $Entry["ID"];
					break;
				}
			}
		} else {
			$Return["Jgd"] = array("Status" => "", "Name" => "", "ID" => "");
		}
	}
	if (in_array("FlgZ", $CheckOptions)) {
		$Return["FlgZ"] = GetAreaOfPoint ("FlgZ", $PointToCheck, $PoiArray, $DefaultNumOfPointsChecked );
		if (is_array($Return["FlgZ"]) && isset($Return["FlgZ"]["Name"])) {
			$Return["FlgZ"]["Bem"] = "aaa";
			$flgList = isset($KMLList["FlgZ"]) && is_array($KMLList["FlgZ"]) ? $KMLList["FlgZ"] : array();
			foreach ($flgList as $Entry) {
				//echo "FlgZ check " .$Return["FlgZ"]["Name"]. "/ ". $Entry["Name"] . "<br>";
				if ($Entry["Name"] == $Return["FlgZ"]["Name"]) {
					$Return["FlgZ"]["ID"] = $Entry["ID"];
					$Return["FlgZ"]["Warnung"] = $Entry["Warnung"];
					break;
				}
			}
		} else {
			$Return["FlgZ"] = array("Status" => "", "Name" => "", "ID" => "", "Warnung" => "");
		}
	}
	if (in_array("Sun", $CheckOptions)) {
		$Return["Sun"] = GetAreaOfPoint ("Sun", $PointToCheck, $PoiArray, $DefaultNumOfPointsChecked );
		if (is_array($Return["Sun"]) && isset($Return["Sun"]["Name"])) {
			$sunList = isset($KMLList["Sun"]) && is_array($KMLList["Sun"]) ? $KMLList["Sun"] : array();
			foreach ($sunList as $Entry) {
				if ($Entry["Name"] == $Return["Sun"]["Name"]) {
					$Return["Sun"]["ID"] = $Entry["ID"];
					$Return["Sun"]["Warnung"] = $Entry["Warnung"];
					break;
				}
			}
		} else {
			$Return["Sun"] = array("Status" => "", "Name" => "", "ID" => "", "Warnung" => "");
		}
	}
	if (in_array("Sora", $CheckOptions)) {
		$Return["Sora"] = GetAreaOfPoint ("Sora", $PointToCheck, $PoiArray, $DefaultNumOfPointsChecked );
		if (is_array($Return["Sora"]) && isset($Return["Sora"]["Name"])) {
			$soraList = isset($KMLList["Sora"]) && is_array($KMLList["Sora"]) ? $KMLList["Sora"] : array();
			foreach ($soraList as $Entry) {
				if ($Entry["Name"] == $Return["Sora"]["Name"]) {
					$Return["Sora"]["ID"] = $Entry["ID"];
					$Return["Sora"]["Warnung"] = $Entry["Warnung"];
					break;
				}
			}
		} else {
			$Return["Sora"] = array("Status" => "", "Name" => "", "ID" => "", "Warnung" => "");
		}
	}
	//Pre_r($Return);
	return $Return;
}

function rkrk_poi_characteristic($poiResult, $type, $field, $default = "") {
	if (!is_array($poiResult) || !isset($poiResult[$type]) || !is_array($poiResult[$type])) {
		return $default;
	}
	return $poiResult[$type][$field] ?? $default;
}

// Gde from Point
// PointToCheck: Lon,Lat(,Height)
Function GetGdeOfPOint($PointToCheck) {
	$PointToCheck = rkrk_geofence_point_text($PointToCheck);
	if ($PointToCheck === "" || $PointToCheck === ",") {
		return "";
	}
	$Point = explode(",", $PointToCheck);
	if (count($Point) < 2 || trim($Point[0]) === "" || trim($Point[1]) === "") {
		return "";
	}
	//pre_r($Point);
	$PoiGde = GetPoiOfPointCharacteristics(Array("Lon" => $Point[0],"Lat" => $Point[1]), Array("Gde"));
	//pre_r($PoiGde);
	return rkrk_poi_characteristic($PoiGde, "Gde", "ID", "");
}
// Jagdgebiet from Point
Function GetJgdOfPOint($PointToCheck) {
	$PointToCheck = rkrk_geofence_point_text($PointToCheck);
	if ($PointToCheck === "" || $PointToCheck === ",") {
		return "";
	}
	$Point = explode(",", $PointToCheck);
	if (count($Point) < 2 || trim($Point[0]) === "" || trim($Point[1]) === "") {
		return "";
	}
	//pre_r($Point);
	$PoiJgd = GetPoiOfPointCharacteristics(Array("Lon" => $Point[0],"Lat" => $Point[1]), Array("Jgd"));
	//pre_r($PoiJgd);
	return rkrk_poi_characteristic($PoiJgd, "Jgd", "ID", "");
}
// Flugzone from Point
Function GetFlgZOfPOint($PointToCheck) {
	$PointToCheck = rkrk_geofence_point_text($PointToCheck);
	if ($PointToCheck === "" || $PointToCheck === ",") {
		return "";
	}
	$Point = explode(",", $PointToCheck);
	if (count($Point) < 2 || trim($Point[0]) === "" || trim($Point[1]) === "") {
		return "";
	}
	$PoiFlgZ = GetPoiOfPointCharacteristics(Array("Lon" => $Point[0],"Lat" => $Point[1]), Array("FlgZ"));
	//pre_r($PoiFlgZ);
	return rkrk_poi_characteristic($PoiFlgZ, "FlgZ", "ID", "");
}
// Warnung Flugzone from Point
Function GetWarnungFlgZOfPOint($PointToCheck) {
	$PointToCheck = rkrk_geofence_point_text($PointToCheck);
	if ($PointToCheck === "" || $PointToCheck === ",") {
		return "";
	}
	$Point = explode(",", $PointToCheck);
	if (count($Point) < 2 || trim($Point[0]) === "" || trim($Point[1]) === "") {
		return "";
	}
	$PoiFlgZ = GetPoiOfPointCharacteristics(Array("Lon" => $Point[0],"Lat" => $Point[1]), Array("FlgZ"));
	//pre_r($PoiFlgZ);
	return rkrk_poi_characteristic($PoiFlgZ, "FlgZ", "Warnung", "");
}

// Sun from Point
Function GetSunOfPOint($PointToCheck) {
	$PointToCheck = rkrk_geofence_point_text($PointToCheck);
	if ($PointToCheck === "" || $PointToCheck === ",") {
		return "";
	}
	$Point = explode(",", $PointToCheck);
	if (count($Point) < 2 || trim($Point[0]) === "" || trim($Point[1]) === "") {
		return "";
	}
	$PoiSun = GetPoiOfPointCharacteristics(Array("Lon" => $Point[0],"Lat" => $Point[1]), Array("Sun"));
	//pre_r($PoiSun);
	return rkrk_poi_characteristic($PoiSun, "Sun", "Warnung", "");
}
// SORA from Point
if (!function_exists('rkrk_geofence_point_text')) {
	function rkrk_geofence_point_text($PointToCheck) {
		if (is_array($PointToCheck)) {
			if (isset($PointToCheck['Lon']) || isset($PointToCheck['Lat'])) {
				$lon = function_exists('rkrk_str') ? rkrk_str($PointToCheck['Lon'] ?? '') : (string)($PointToCheck['Lon'] ?? '');
				$lat = function_exists('rkrk_str') ? rkrk_str($PointToCheck['Lat'] ?? '') : (string)($PointToCheck['Lat'] ?? '');
				$PointToCheck = trim($lon) . ', ' . trim($lat);
			} else {
				$parts = array();
				foreach ($PointToCheck as $item) {
					$s = function_exists('rkrk_str') ? rkrk_str($item) : (is_array($item) ? '' : (string)$item);
					$s = trim($s);
					if ($s !== '') {
						$parts[] = $s;
					}
				}
				$PointToCheck = implode(', ', $parts);
			}
		} elseif (function_exists('rkrk_str')) {
			$PointToCheck = rkrk_str($PointToCheck);
		} else {
			$PointToCheck = is_object($PointToCheck) ? '' : (string)($PointToCheck ?? '');
		}
		return trim($PointToCheck);
	}
}

Function GetSoraOfPOint(&$Feld) {
	//Für SORA wird jeder Feldpunkt geprüft, ob er innerhalb des Feldes liegt
	$flaechen = is_array($Feld) ? ($Feld["Flaechendaten"] ?? '') : '';
	if (is_array($flaechen)) {
		$parts = array();
		foreach ($flaechen as $item) {
			$s = function_exists('rkrk_str') ? rkrk_str($item) : (is_array($item) ? '' : (string)$item);
			$s = trim($s);
			if ($s !== '') {
				$parts[] = $s;
			}
		}
		$flaechen = implode('   ', $parts);
	} elseif (function_exists('rkrk_str')) {
		$flaechen = rkrk_str($flaechen);
	} else {
		$flaechen = (string)($flaechen ?? '');
	}
	$FieldPoints = explode("   ", $flaechen);
	//pre_R($FieldPoints);
	$PoiSora = array();
	foreach ($FieldPoints as $PointToCheck) {
		$PointToCheck = trim($PointToCheck);
		if ($PointToCheck === "" || $PointToCheck === ",") {
			continue;
		}
		$Point = explode(",", $PointToCheck);
		if (count($Point) < 2 || trim($Point[0]) === "" || trim($Point[1]) === "") {
			continue;
		}
		$PoiSora = GetPoiOfPointCharacteristics(Array("Lon" => $Point[0],"Lat" => $Point[1]), Array("Sora"));
		//pre_r($PoiSora);
		if (rkrk_poi_characteristic($PoiSora, "Sora", "Status", "") === "In") {
			return rkrk_poi_characteristic($PoiSora, "Sora", "Warnung", "");
		}
	}
	return rkrk_poi_characteristic($PoiSora, "Sora", "Warnung", "");
}


//Errechnet den (potenziellen) Durchmesser eins Feldes
// $Centerpoint "Lon, Lat"
// $Area, List of Poiints "Lon, Lat, Space(s)"
// Rückgabewert d in m

function rkrk_geofence_lonlat_pairs($text) {
	$pairs = array();
	if (!preg_match_all('/(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)/', (string)$text, $matches, PREG_SET_ORDER)) {
		return $pairs;
	}
	foreach ($matches as $match) {
		$pairs[] = array('Lon' => (float)$match[1], 'Lat' => (float)$match[2]);
	}
	return $pairs;
}

function DiameterOfArea($Centerpoint, $Area) {
	$pairs = rkrk_geofence_lonlat_pairs($Centerpoint);
	$center = $pairs[0] ?? null;
	if (!is_array($center)) {
		return 0.0;
	}
	$maxDistance = 0.0;
	foreach (rkrk_geofence_lonlat_pairs($Area) as $point) {
		$distance = twopoints_on_earth($center, $point);
		if (is_numeric($distance) && (float)$distance > $maxDistance) {
			$maxDistance = (float)$distance;
		}
	}
	return 2.0 * $maxDistance;
}


function DiameterOfFieldFromFieldData($Felddata) {
	$Centerpoint = trim($Felddata["Grundstueckdaten"]["Zentrum"]);
	$Area = trim($Felddata["Flaechendaten"]);
	//$Centerpoint = trim($Feldkatalog[substr($FeldID,0,2)][$FeldID]["Grundstueckdaten"]["Zentrum"]);
	//$Area = trim($Feldkatalog[substr($FeldID,0,2)][$FeldID]["Flaechendaten"]);
	//echo "CP " . $Centerpoint . "<br>";
	//echo "AR" . $Area . "<br>";
	$Diameter = 1000*DiameterOfArea($Centerpoint, $Area);
	Return ($Diameter);
}


function DiameterOfField($FeldID) {
global $xmlMissionLocation, $RKRKFelderfileXML, $colorOKtext, $colorNOKtext, $colorNOK;
	$Feldkatalog = GetFeldkatalog($xmlMissionLocation.$RKRKFelderfileXML); 
	$jg = (strpos($FeldID, '-') !== false) ? substr($FeldID, 0, strpos($FeldID, '-')) : $FeldID;
	$row = (is_array($Feldkatalog) && isset($Feldkatalog[$jg][$FeldID]) && is_array($Feldkatalog[$jg][$FeldID]))
		? $Feldkatalog[$jg][$FeldID]
		: array();
	$zRaw = (isset($row["Grundstueckdaten"]) && is_array($row["Grundstueckdaten"]))
		? ($row["Grundstueckdaten"]["Zentrum"] ?? '')
		: '';
	$Centerpoint = is_array($zRaw) ? trim(implode(',', $zRaw)) : trim((string)$zRaw);
	$Area = trim((string)($row["Flaechendaten"] ?? ''));
	//$Centerpoint = trim($Feldkatalog[substr($FeldID,0,2)][$FeldID]["Grundstueckdaten"]["Zentrum"]);
	//$Area = trim($Feldkatalog[substr($FeldID,0,2)][$FeldID]["Flaechendaten"]);
	//echo "CP " . $Centerpoint . "<br>";
	//echo "AR" . $Area . "<br>";
	$Diameter = 1000*DiameterOfArea($Centerpoint, $Area);
	Return ($Diameter);
}

function DiameterOfFieldList($FeldList) {
global $xmlMissionLocation, $RKRKFelderfileXML, $colorOKtext, $colorNOKtext, $colorNOK;
	$Feldkatalog = GetFeldkatalog($xmlMissionLocation.$RKRKFelderfileXML); 
	//pre_R($Feldkatalog);
	//substr($FeldID,0,strpos($FeldID,"-"));
	$Centerpoints = "";
	$lat_min = 10000.0;
	$lon_min = 10000.0;
	$lat_max = 0.0;
	$lon_max = 0.0;
	$count= 0;
	// Mittelpunkt
	Foreach ($FeldList as $key => $FeldID) {
		$count++;
		$jg = (strpos((string)$FeldID, '-') !== false) ? substr((string)$FeldID, 0, strpos((string)$FeldID, '-')) : (string)$FeldID;
		$row = (is_array($Feldkatalog) && isset($Feldkatalog[$jg][$FeldID]) && is_array($Feldkatalog[$jg][$FeldID]))
			? $Feldkatalog[$jg][$FeldID]
			: array();
		$zRaw = (isset($row["Grundstueckdaten"]) && is_array($row["Grundstueckdaten"]))
			? ($row["Grundstueckdaten"]["Zentrum"] ?? '')
			: '';
		$zentrum = is_array($zRaw) ? trim(implode(',', $zRaw)) : trim((string)$zRaw);
		$Centerpoints .= $zentrum . " ";
		$Area = trim((string)($row["Flaechendaten"] ?? ''));
		$zParts = ($zentrum !== '') ? explode(",", $zentrum) : array('', '');
		$CP_lat =  trim((string)($zParts[0] ?? ''));
		$CP_lon =  trim((string)($zParts[1] ?? ''));
		if (($CP_lat>0) and $lat_min > $CP_lat) 
			$lat_min = $CP_lat;
		if (($CP_lon>0) and $lon_min > $CP_lon) 
			$lon_min = $CP_lon;
		if ($lat_max < $CP_lat) 
			$lat_max = $CP_lat;
		if ($lon_max < $CP_lon) 
			$lon_max = $CP_lon;
		$Centerpoints =$lat_min . ", " . $lon_min . "  " .$lat_max . ", " . $lon_max;
	}
	//echo "LAT: min " . $lat_min . " max " . $lat_max . "LON: min " . $lon_min  . " max " .  $lon_max. "<br>";
	$centerlat = ($lat_min + $lat_max) / 2;
	$centerlon = ($lon_min + $lon_max) / 2;
	// Schwerpunktrechung
	/*
	Foreach ($FeldList as $key => $FeldID) {
		echo $FeldID . " - " . $Feldkatalog[substr($FeldID,0,strpos($FeldID,"-"))][$FeldID]["Grundstueckdaten"]["Zentrum"] . "<br>";
		$count++;
		$Centerpoints .= trim($Feldkatalog[substr($FeldID,0,strpos($FeldID,"-"))][$FeldID]["Grundstueckdaten"]["Zentrum"]) . " ";
		$Area = trim($Feldkatalog[substr($FeldID,0,strpos($FeldID,"-"))][$FeldID]["Flaechendaten"]);
		$CP_lat =  trim(explode(",",$Feldkatalog[substr($FeldID,0,strpos($FeldID,"-"))][$FeldID]["Grundstueckdaten"]["Zentrum"])[0]);
		$CP_lon =  trim(explode(",",$Feldkatalog[substr($FeldID,0,strpos($FeldID,"-"))][$FeldID]["Grundstueckdaten"]["Zentrum"])[1]);
		$lat += $CP_lat;
		$lon += $CP_lon;
	}
	$centerlat = $lat / $count;
	$centerlon = $lon / $count;
	*/
	$Centerpoint =$centerlat . ", " . $centerlon;
	//echo "CP " . $Centerpoint . "<br>";
	//echo "AR " . $Centerpoints . "<br>";
	$Diameter = 1000*DiameterOfArea($Centerpoint, $Centerpoints);
	//Output: LAT, LON, HEIGHT, DIAMETER
	//echo $centerlat . "," . $centerlon . ", 0 , " . $Diameter . "<br>";
	Return ($centerlat . "," . $centerlon . ", 0 , " . $Diameter);
}

?>