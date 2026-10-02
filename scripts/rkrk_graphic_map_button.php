<?php
include_once('scripts/rkrk_gen.php');
///////////////////////////////////////
// Definitions
////////////////////////////////////////

	$JGMap2022 = array(
					array(
						"name" => "JG Amt ZH",
						"coords" => "572,21, 671,171, 840,134, 875,170, 921,466, 812,566, 869,610, 584,212, 466,175",
						"JG" => "AZ",
						"image" => "jgmap_restJGAmt.png",
						"remark" => "Urdorf Amt ZH"
					),
					array(
						"name" => "JG Amt ZH",
						"coords" => "1002,633, 987,525, 921,466, 812,566, 601,840, 639,1070, 749,1054, 741,978, 785,948, 812,983, 902,879, 974,861, 972,770, 1000,733, 979,719, 980,690",
						"JG" => "AZ",
						"image" => "jgmap_restJGAmt.png",
						"remark" => "JG Amt Nord Nord"
					),
					array(
						"name" => "JG Amt ZH",
						"coords" => "1002,633, 1082,554, 1382,980, 1572,1041, 1852,1320, 1722,1460, 1764,1560, 1602,1517, 1459,1357, 1372,1270, 1320,1237, 1113,950, 1032,885",
						"JG" => "AZ",
						"image" => "jgmap_restJGAmt.png",
						"remark" => "See"
					),
					array(
						"name" => "21 Aeugst",
						"coords" => " 974,861, 1032,885, 1113,950, 976,1025, 812,983",
						"JG" => "21",
						"image" => "jgmap_21.png",
						"remark" => "21 Aeugst"
					),
					array(
						"name" => "24 Hausen a.A.",
						"coords" => "1113,950, 1320,1237, 1264,1212, 1088,1113, 985,1211, 976,1025",
						"JG" => "24",
						"image" => "jgmap_24.png",
						"remark" => "24 Hausen a.A."
					),
					array(
						"name" => "25 Kappel a.A.",
						"coords" => "1164,1212, 950,1281, 946,1192, 985,1211, 1088, 1113",
						"JG" => "25",
						"image" => "jgmap_25.png",
						"remark" => "25 Kappel a.A."
					),
					array(
						"name" => "26 Knonau",
						"coords" => "950,1281, 877,1269, 776,1216, 769,1143, 867,1203, 946,1192",
						"JG" => "26",
						"image" => "jgmap_26.png",
						"remark" => "26 Knonau"
					),
					array(
						"name" => "27 Maschwanden",
						"coords" => "787,1216, 677,1202, 649,1070, 749,1054, 769,1143",
						"JG" => "27",
						"image" => "jgmap_27.png",
						"remark" => "27 Maschwanden"
					),
					array(
						"name" => "28 Mettmenstetten",
						"coords" => "769,1143, 749,1054, 741,978, 785,948, 812,983, 976,1025, 985,1211, 946,1192, 867,1203",
						"JG" => "28",
						"image" => "jgmap_28.png",
						"remark" => "28 Mettmenstetten"
					),
					// NEU 2024
					array(
						"name" => "32 Stallikon II",
						"coords" => "1032,885, 1002,633, 990,690, 989,729, 1000,733, 972,770, 974,861",
						//"coords" => "1000,865, 968,713, 970,613, 948,670, 947,699, 968,713, 940,750, 942,841",
						"JG" => "32",
						"image" => "jgmap_32.png",
						"remark" => "32 Stallikon II"
					),
					array(
						"name" => "136 Abtwil",
						"coords" => "376,1438, 462,1436, 480,1487, 441,1597 ",
						"JG" => "136",
						"image" => "jgmap_136.png",
						"remark" => "136 Abtwil"
					),
					array(
						"name" => "148 Sins",
						"coords" => "332,1338, 545,1310, 549,1237, 590,1235, 608,1450, 541,1501, 539,1578, 504,1692, 441,1597, 480,1487, 462,1436, 376,1438",
						"JG" => "148",
						"image" => "jgmap_148.png",
						"remark" => "148 Sins"
					),
					array(
						"name" => "145 Oberrüti",
						"coords" => "527, 1590,  639, 1615,  637, 1499,  596, 1462,  529, 1513",
						"JG" => "145",
						"image" => "jgmap_145.png",
						"remark" => "145 Oberrüti"
					),
					array(
						"name" => "143 Dietwil",
						"coords" => "527, 1590,  639, 1615,  651, 1678,  619, 1731,  492, 1704",
						"JG" => "143",
						"image" => "jgmap_143.png",
						"remark" => "143 Dietwil"
					),
					array(
						"name" => "Oberfreiamt AG",
						"coords" => "332,1338, 549,1237, 32,790, 123,765, 109,570, 292,520, 422,656, 477,605, 611,840, 649,1070, 586,1178, 590,1235, 549,1237, 545,1310",
						"JG" => "AG",
						"image" => "jgmap_restAG.png",
						"remark" => "Oberfreiamt Nord"
					),
					array(
						"name" => "ZG1 Zug",
						"coords" => "865,1281  ,910,1487  ,805,1502  ,815,1662  ,873,1687  ,848,1817  ,805,1845  ,775,1747  ,653,1797  ,651,1678  ,718,1617  ,740,1446",
						//"coords" => "877,1269, 922,1475, 817,1490, 827,1380, 885,1675 860,1805, 817,1833, 787,1735, 665,1785, 663,1666, 730,1605, 752,1434",
						"JG" => "ZG1",
						"image" => "jgmap_ZG1.png",
						//"image" => "images/22color/jgmap_ZG.png",
						"remark" => "JG 1 Zug"
					),
					array(
						"name" => "ZG2 Zug",
						"coords" => "649,1070, 677,1202, 787,1216, 877,1269, 752,1434, 730,1605, 663,1666, 651,1603, 649,1487, 608,1450, 590,1235, 586,1178",
						"JG" => "ZG2",
						"image" => "jgmap_ZG2.png",
						//"image" => "images/22color/jgmap_ZG.png",
						"remark" => "JG 2 Zug"
					),
					array(
						"name" => "ZG3 Zug",
						"coords" => "877,1269, 950,1281, 1164,1212, 1320,1237, 1372,1270, 1255,1493, 1202,1460, 1129,1505, 1062,1454, 982,1486, 922,1475",
						"JG" => "ZG3",
						"image" => "jgmap_ZG3.png",
						//"image" => "images/22color/jgmap_ZG.png",
						"remark" => "JG 3 Zug"
					),
					array(
						"name" => "ZG4 Zug",
						"coords" => "982,1486, 1062,1454, 1129,1505, 1115,1733, 1131,1854, 1222,1973, 1082,1989, 982,1792, 1050,1570",
						"JG" => "ZG4",
						"image" => "jgmap_ZG4.png",
						//"image" => "images/22color/jgmap_ZG.png",
						"remark" => "JG 4 Zug"
					),
					array(
						"name" => "ZG5 Zug",
						"coords" => "1129,1505, 1202,1460, 1255,1493, 1247,1620, 1326,1715, 1562,1922, 1536,1975, 1247,2052, 1322,1973, 1131,1854, 1115,1733",
						"JG" => "ZG5",
						"image" => "jgmap_ZG5.png",
						//"image" => "images/22color/jgmap_ZG.png",
						"remark" => "JG5 Zug"
					),
					array(
						"coords" => "1372,1270, 1459,1357, 1602,1517, 1764,1560, 1717,1800, 1536,1975, 1562,1922, 1326,1715, 1247,1620, 1255,1493",
						"name" => "ZG6 Zug",
						"JG" => "ZG6",
						"image" => "jgmap_ZG6.png",
						//"image" => "images/22color/jgmap_ZG.png",
						"remark" => "JG6 Zug"
					),
					array(
						"coords" => "715,1870, 862,1842, 961,1879, 862,1842",
						"name" => "Meierskappel LU",
						"JG" => "LME",
						"image" => "jgmap_LME.png",
						"remark" => "Meierskappel LU"
					),
					array(
						"coords" => "715,1870, 666,1962, 663,1816, 795,1771",
						"name" => "Meierskappel LU",
						"JG" => "LME",
						"image" => "jgmap_LME.png",
						"remark" => "Meierskappel LU"
					),
					array(
						//"coords" => "637,1082  ,665,1214  ,775,1228  ,865,1281  ,938,1293  ,1152,1224  ,1308,1249  ,1360,1282  ,1447,1369  ,1590,1529  ,1752,1572  ,1705,1812  ,1524,1987  ,1235,2064  ,1210,1985  ,1070,1991  ,970,1804  ,848,1817  ,805,1845  ,775,1747  ,653,1797  ,651,1678  ,639,1615  ,637,1499  ,596,1462  ,578,1247  ,574,1190",
						"coords" => "",
						"name" => "ZG Zug",
						"JG" => "ZG",
						"image" => "jgmap_ZG.png",
						"remark" => "ZG Zug"
					),
					array(
						"name" => "JG Amt, ZH",
						"coords" => "510,0,  1978,0,  1978,1450,  1845, 1359,  1563, 1071, 644,1096, 1564,1072, 1377,1011, 1170,591, 1000,666, 981,543, 918,480, 900,242, 800,162, 645,195, 564,50, 510,0",
						"JG" => "Amt",
						"image" => "jgmap_JGAmt.png",
						"remark" => "ganzes JG Amt ZH"
					),
					array(
						"name" => "Oberfreiamt AG",
						"coords" => "0,0,  510,0, 654,50, 465,207, 590,250, 660,642, 811,604, 600,861, 468,624, 370,670, 283,538, 111,582, 121,820, 27,811, 47,870, 0, 900",
						//"coords" => "372,0,  1982,0,  1982,1607,  1763,1650,  644,1096,  565,847",
						"JG" => "AG,137,148,136,145,143",
						"image" => "jgmap_AG.png",
						"remark" => "Kanton Argau"
					),
					array(
						"name" => "Kanton Zug",
						//"coords" => "1982,1607,  1763,1650,  644,1096,  652,1720,  1982,2136",
						"coords" => "1058,1990, 978,1822, 1047,1547, 990,1516, 990,1516, 919,1504, 813,1525, 828,1713, 883,1713, 850,1771, 862,1842, 910,1809, 961,1879, 961,1954",
						"JG" => "ZG,ZG1,ZG2,ZG3,ZG4,ZG5,ZG6",
						"image" => "jgmap_ZG.png",
						"remark" => "Kanton Zug"
					),
					array(
						"name" => "Kanton Luzern",
						"coords" => "0,900, 47,870, 187,1003, 327,1371, 363,1471, 439,1632, 505,1716, 657,1732, 663,1816, 666,1962, 540,2134",
						"JG" => "LU,LME",
						"image" => "jgmap_LU.png",
						"remark" => "Kanton Luzern"
					),
					array(
						"name" => "Kanton Schwyz",
						"coords" => "540,2134, 666,1962, 715,1870, 862,1842, 916,1954, 1058,1990, 1089,2025, 1246,2017, 1243,2095, 1546,2014, 1737,1821, 1804,1672, 1732, 1596, 1720,1494, 1845,1359, 1978,1450, 1978,2134",
						"JG" => "SZ",
						"image" => "jgmap_SZ.png",
						"remark" => "Kanton Schwyz"
					),
				);

/* 2023 */
	$JGButton2023 = array(
					"21" => array(
							"name" => "21",
							"JG" => "21",
							"X-loc" => 1,
							"Y-loc" => -2,
							"image_on" => "JGsel_21_on.png",
							"image_off" => "JGsel_21_off.png",
							"remark" => "Aeugst a.A."
						),
					"24" => array(
							"name" => "24",
							"JG" => "24",
							"X-loc" => 1.5,
							"Y-loc" => -1,
							"image_on" => "JGsel_24_on.png",
							"image_off" => "JGsel_24_off.png",
							"remark" => "Hausen a.A."
						),
					"25" => array(
							"name" => "25",
							"JG" => "25",
							"X-loc" => 0.5,
							"Y-loc" => -1,
							"image_on" => "JGsel_25_on.png",
							"image_off" => "JGsel_25_off.png",
							"remark" => "Kappel a.A."
						),
					"26" => array(
							"name" => "26",
							"JG" => "26",
							"X-loc" => -0.5,
							"Y-loc" => -1,
							"image_on" => "JGsel_26_on.png",
							"image_off" => "JGsel_26_off.png",
							"remark" => "Hünenberg, Risch"
						),
					"27" => array(
							"name" => "27",
							"JG" => "27",
							"X-loc" => -1,
							"Y-loc" => -2,
							"image_on" => "JGsel_27_on.png",
							"image_off" => "JGsel_27_off.png",
							"remark" => "Maschwanden"
						),
					"28" => array(
							"name" => "28",
							"JG" => "28",
							"X-loc" => 0,
							"Y-loc" => -2,
							"image_on" => "JGsel_28_on.png",
							"image_off" => "JGsel_28_off.png",
							"remark" => "Mettmenstetten"
						),
					"32" => array(
							"name" => "32",
							"JG" => "32",
							"X-loc" => 0.5,
							"Y-loc" => -3,
							"image_on" => "JGsel_32_on.png",
							"image_off" => "JGsel_32_off.png",
							"remark" => "Stallikon II"
						),
					"136" => array(
							"name" => "136",
							"JG" => "136",
							"X-loc" => -2,
							"Y-loc" => 0,
							"image_on" => "JGsel_136_on.png",
							"image_off" => "JGsel_136_off.png",
							"remark" => "Abtwil"
						),
					"143" => array(
							"name" => "143",
							"JG" => "143",
							"X-loc" => -1.5,
							"Y-loc" => 1,
							"image_on" => "JGsel_143_on.png",
							"image_off" => "JGsel_143_off.png",
							"remark" => "Dietwil"
						),
					"145" => array(
							"name" => "145",
							"JG" => "145",
							"X-loc" => -.5,
							"Y-loc" => 1,
							"image_on" => "JGsel_145_on.png",
							"image_off" => "JGsel_145_off.png",
							"remark" => "Oberrüti"
						),
					"148" => array(
							"name" => "148",
							"JG" => "148",
							"X-loc" => -1,
							"Y-loc" => 0,
							"image_on" => "JGsel_148_on.png",
							"image_off" => "JGsel_148_off.png",
							"remark" => "Sins",
						),
					"ZG1" => array(
							"name" => "ZG1",
							"JG" => "ZG1",
							"X-loc" => 0.5,
							"Y-loc" => 1,
							"image_on" => "JGsel_ZG1_on.png",
							"image_off" => "JGsel_ZG1_off.png",
							"remark" => "Hünenberg, Risch"
						),
					"ZG2" => array(
							"name" => "ZG2",
							"JG" => "ZG2",
							"X-loc" => 0,
							"Y-loc" => 0,
							"image_on" => "JGsel_ZG2_on.png",
							"image_off" => "JGsel_ZG2_off.png",
							"remark" => "Hünenberg, Risch"
						),
					"ZG3" => array(
							"name" => "ZG3",
							"JG" => "ZG3",
							"X-loc" => 1,
							"Y-loc" => 0,
							"image_on" => "JGsel_ZG3_on.png",
							"image_off" => "JGsel_ZG3_off.png",
							"remark" => "Steinhausen, Baar"
						),
					"ZG4" => array(
							"name" => "ZG4",
							"JG" => "ZG4",
							"X-loc" => 1.5,
							"Y-loc" => 1,
							"image_on" => "JGsel_ZG4_on.png",
							"image_off" => "JGsel_ZG4_off.png",
							"remark" => "Zug, Walchwil",
						),
					"ZG5" => array(
							"name" => "ZG5",
							"JG" => "ZG5",
							"X-loc" => 2.5,
							"Y-loc" => 1,
							"image_on" => "JGsel_ZG5_on.png",
							"image_off" => "JGsel_ZG5_off.png",
							"remark" => "Zug, Walchwil",
						),
					"ZG6" => array(
							"name" => "ZG6",
							"JG" => "ZG6",
							"X-loc" => 2,
							"Y-loc" => 0,
							"image_on" => "JGsel_ZG6_on.png",
							"image_off" => "JGsel_ZG6_off.png",
							"remark" => "Zug",
						),
					"TF" => array(
							"name" => "TF",
							"JG" => "TF",
							"X-loc" => 2.85,
							"Y-loc" => -2,
							"image_on" => "JGsel_TF_on.png",
							"image_off" => "JGsel_TF_off.png",
							"remark" => "Testfelder",
						),
					"XX" => array(
							"name" => "XX",
							"JG" => "XX",
							"X-loc" => 2.35,
							"Y-loc" => -3,
							"image_on" => "JGsel_XX_on.png",
							"image_off" => "JGsel_XX_off.png",
							"remark" => "Andere Regionen",
						),
					"LME" => array(
							"name" => "LME",
							"JG" => "LME",
							"X-loc" => 0,
							"Y-loc" => 2,
							"image_on" => "JGsel_LME_on.png",
							"image_off" => "JGsel_LME_off.png",
							"remark" => "Meierskappel (LU)",
						),
					"SZ" => array(
							"name" => "SZ",
							"JG" => "SZ",
							"X-loc" => 1,
							"Y-loc" => 2,
							"image_on" => "JGsel_SZ_on.png",
							"image_off" => "JGsel_SZ_off.png",
							"remark" => "Kt. Schwyz",
						),
				);

	$RegMap2023 = array(
					"Amt" => array(
							"name" => "Amt ZH",
							"JG" => "21,24,25,26,27,28,32",
							"coords" => "0,0, 2416,0, 2416,770, 1624,1262, 1422,1140, 1215,1262, 1011,1140, 815,1260 , 0,770",
							"remark" => "Amt Zürich"
						),
					"ZG" => array(
							"name" => "ZG",
							"JG" => "ZG1,ZG2,ZG3,ZG4,ZG5,ZG6",
							//"coords" => "2416,770, 1624,1262, 1422,1140, 1215,1262, 1011,1140, 815,1260 , 815,1462,  1013,1591, 1013,2186 , 2416,2186",
							"coords" => "2416,770, 1624,1262, 1422,1140, 1215,1262, 1011,1140, 815,1260 , 815,1462,  1013,1591, 1022,1802, 1223,1923, 1628,1928, 1628,1928, 1631,2304",
							"remark" => "Kanton Zug"
						),
					"AG" => array(
							"name" => "AG",
							"JG" => "136,143,145,148",
							//"coords" => "0,2186, 0,770, 815,1260 , 815,1462,  1013,1591, 1013,2186",
							"coords" => "0,2186, 0,770, 815,1260 , 815,1462,  1022,1802, 240,2304",
							"remark" => "Oberfrieamt"
						),
					"LU" => array(
							"name" => "LU",
							"JG" => "LME,LU",
							"coords" => "240,2304, 1022,1802, 1223,1923, 1223,2304",
							"remark" => "Kanton Luzern"
						),
					"SZ" => array(
							"name" => "SZ",
							"JG" => "SZ",
							"coords" => "1223,2304, 1223,1923, 1427,1802, 1628,1928, 1631,2304",
							"remark" => "Kanton Schwyz"
						),
				);


?>
<script>
<?php
echo "var iconloc = '". $IconLoc . "';\n";
?>

function SetPicture(pic) {
	var src = pic;
	if (pic.indexOf('/') === -1) {
		src = iconloc + '/' + pic;
	}
	document.getElementById("jgmappic").src = src;
}

function SetSelection(jg,pic) {
	SetPicture(pic)
	document.getElementById("Region").value = jg;
	//alert(jg);  
}

function SetPicture2(picid,pic) {
	//alert(iconloc+'/'+pic);  
	document.getElementById(picid).src = iconloc+'/'+pic;
}

function SetPictureFromArray(item, index, arr) {
	document.getElementById(item).src = iconloc +'/'+ pic;
	alert(item + "/ " + iconloc +'/'+ pic);  
}

function SetGroupPicOn(Grp) {
<?php 
	foreach ($RegMap2023 as $Key => $Reg) {
		echo "if (Grp == '".$Key ."') {\n";	
		//skallieren
		$JGs = explode(",", $Reg["JG"]);
		foreach ($JGs as  $JG) {
			if (!isset($JGButton2023[$JG]["image_on"])) {
				continue;
			}
			echo "SetPicture2('".$JG."','".$JGButton2023[$JG]["image_on"]."');\n";	
		}
		echo "}\n";	
	}
?>	
	//alert(Grp);
}

function SetGroupPicOff(Grp) {
<?php 
	foreach ($RegMap2023 as $Key => $Reg) {
		echo "if (Grp == '".$Key ."') {\n";	
		//skallieren
		$JGs = explode(",", $Reg["JG"]);
		foreach ($JGs as  $JG) {
			if (!isset($JGButton2023[$JG]["image_off"])) {
				continue;
			}
			echo "SetPicture2('".$JG."','".$JGButton2023[$JG]["image_off"]."');\n";	
		}
		echo "}\n";	
	}
?>	
	//alert(Grp);
}
</script>
<?php 


////////////////////////////////////////
// Functions
////////////////////////////////////////

function rkrk_jgmap_2022_dir() {
	static $dir = null;
	if ($dir !== null) {
		return $dir;
	}
	global $IconLoc, $ImageLoc;
	$root = dirname(__DIR__);
	$iconWeb = rtrim(str_replace('\\', '/', (string)($IconLoc ?? '')), '/') . '/';
	$imageWeb = rtrim(str_replace('\\', '/', (string)(($ImageLoc ?? '') !== '' ? $ImageLoc : 'images/')), '/') . '/';
	$checks = array(
		array($root . '/images/23color/jgmap.png', 'images/23color/'),
		array($root . '/' . ltrim($iconWeb, './') . 'jgmap.png', $iconWeb),
		array($root . '/' . ltrim($imageWeb, './') . '23color/jgmap.png', $imageWeb . '23color/'),
		array($root . '/images/22color/jgmap.png', 'images/22color/'),
	);
	foreach ($checks as $pair) {
		if (is_file($pair[0])) {
			$dir = $pair[1];
			return $dir;
		}
	}
	$dir = ($iconWeb !== '/' ? $iconWeb : 'images/23color/');
	return $dir;
}

function JGMapButtons2023($PicWidth) {
	global $ThisPage, $MapIntern, $MapExtern, $IconLoc, $JGButton2023, $RegMap2023, $UseSecUrl;
	
	$pic["width"] = 2416;
	$pic["height"] = 2202;
	$button_on["width"] = 439;
	$button_on["height"] = 490;
	$button_off["width"] = 405;
	$button_off["height"] = 458;
	$Reference["Y"] = 1142;
	$Reference["X"] = 819;
	
	$MapScale = $PicWidth/$pic["width"];
	$PicHeight = round($MapScale*$pic["height"]);
	$button["width"] = $button_off["width"]*$MapScale*0.99;
	$button["height"] = $button_off["height"]*$MapScale;
	$button["line"] = $button_off["height"]*$MapScale*0.71;
	//rotation clockwise (form top
	$ButtonMap[0]["X"] = $button["width"]/2;
	$ButtonMap[0]["Y"] = 0;
	$ButtonMap[1]["X"] = $button["width"];
	$ButtonMap[1]["Y"] = 1*($button["height"]/4);
	$ButtonMap[2]["X"] = $button["width"];
	$ButtonMap[2]["Y"] = 3*($button["height"]/4);
	$ButtonMap[3]["X"] = $button["width"]/2;
	$ButtonMap[3]["Y"] = $button["height"];
	$ButtonMap[4]["X"] = 0;
	$ButtonMap[4]["Y"] = 3*($button["height"]/4);
	$ButtonMap[5]["X"] = 0;
	$ButtonMap[5]["Y"] = 1*($button["height"]/4);

	$output = "";
	
	//$output .=  $ThisPage;
	
	$PageRef1 = 'rkrk_map.php?';
	if (GetAccess("AllPilots")){
		//internal
		$PageRef2 = $MapIntern;
	} else {
		//public
		$PageRef2 = $MapExtern;
	}
	
	$output .= '<img  id= "jgmappic"  src="'.$IconLoc .'/'.'void.png" alt="JGmap" usemap="#jgmap" height="'.$PicHeight.'" width="'. $PicWidth .'">' .chr(10);
	$output .= '<div id="myMENU">' .chr(10);
	$output .= '</div>';	
	foreach ($JGButton2023 as $map) {
		// print buttons
		$output .= '<div style="top:'.round($Reference["Y"]*$MapScale+($map["Y-loc"]*$button["line"])).'px;left:'.round($Reference["X"]*$MapScale+($map["X-loc"]*$button["width"])).'px;text-align: center;z-index:1;">';
		$output .= '<img id="'.$map["JG"].'" src="'.$IconLoc ."/".$map["image_off"].'" width="'.$button["width"].'">' .chr(10);
		//$output .= '<img id="'.$map["JG"].'" src="'.$map["image_off"].'" width="'.$button["width"].'">' .chr(10);
		$output .= '</div>';	

	}	
	$output .= '<div style="top:0px;left:0px;text-align: center;z-index:1;">';
	$output .= '<img  id= "jgmappic"  src="'.$IconLoc ."/".'JGsel_frame.png" alt="JGmap" usemap="#jgmap" width="'. $PicWidth .'">' .chr(10);
	$output .= '<map name="jgmap">' .chr(10);

	$coords="";
	foreach ($JGButton2023 as $map) {
		//buttons zeichenen und map machen
		$coords ="";
		foreach ($ButtonMap as $ButtonPoint) {
			//X
			$coords .=   round(($Reference["X"]*$MapScale+($map["X-loc"]*$button["width"]))+$ButtonPoint["X"]) . ",  ";
			//Y
			$coords .=   round(($Reference["Y"]*$MapScale+($map["Y-loc"]*$button["line"]))+$ButtonPoint["Y"]) . ", ";
		}
		//echo "coordinates: ". $coords;
		//echo $map["name"]. ":  " . $coords . "<br>"; 
		$moveoverlink = "SetPicture2('".$map["JG"]."','" . $map["image_on"] . "')";
		$moveleavelink = "SetPicture2('".$map["JG"]."','" . $map["image_off"] . "')";
		if ($UseSecUrl) {
			$output .= '<area shape="poly" coords="' . $coords .'" alt="' . $map["name"] .'" title="' . $map["name"] .'" href="' . $PageRef1 . base64_encode("Reg=" . $map["JG"] . $PageRef2) . '" onmouseover="' . $moveoverlink . '"  onmouseleave="'.$moveleavelink.'" target="_geomap">' . chr(10);
		} else {
			$output .= '<area shape="poly" coords="' . $coords .'" alt="' . $map["name"] .'" title="' . $map["name"] .'" href="' . $PageRef1 . "Reg=" . $map["JG"] . $PageRef2 . '" onmouseover="' . $moveoverlink . '"  onmouseleave="'.$moveleavelink.'" target="_geomap">' . chr(10);
		}
		//echo'<area shape="poly" coords="' . $map["coords"] .'" alt="' . $map["name"] .'" title="' . $map["name"] .'" href="' . $PageRef1 . $map["JG"] . $PageRef2 . '" >' . chr(10);
	}
	//map für regionen holen

	foreach ($RegMap2023 as $Key => $Reg) {
		//skallieren
		$coords = explode(",", $Reg["coords"]);
		foreach ($coords as $key => $point) {
			$coords[$key] = round($point * $MapScale);
		}
		$coords=  implode("," ,$coords);
		$JGs = explode(",", $Reg["JG"]);
		//$picIDs = array();
		//$picOn = array();
		//$picOff = array();
		//foreach ($JGs as  $JG) {
		//	$picIDs[$JG] = $JG;
		//	$picOn[$JG] = $JGButton2023[$JG]["image_on"];
		//	$picOff[$JG] = $JGButton2023[$JG]["image_off"];
		//}
		//pre_R($picOn);
		//$picIDArray = '["'.implode('","', $picIDs).'"]';
		//$picOnArray = '["'.implode('","', $picOn).'"]';
		//$picOffArray = '["'.implode('","', $picOff).'"]';
		//echo $picOffArray. "<br>";
		$moveoverlink = "SetGroupPicOn('".$Key."')";
		$moveleavelink = "SetGroupPicOff('".$Key."')";
		if ($UseSecUrl) {
			$output .= '<area shape="poly" coords="' . $coords .'" alt="' . $Reg["name"] .'" title="' . $Reg["name"] .'" href="' . $PageRef1 . base64_encode("Reg=" .$Reg["JG"] . $PageRef2) . '" onmouseover="' . $moveoverlink . '"  onmouseleave="'.$moveleavelink.'" target="_geomap">' . chr(10);
		} else {
			$output .= '<area shape="poly" coords="' . $coords .'" alt="' . $Reg["name"] .'" title="' . $Reg["name"] .'" href="' . $PageRef1 . "Reg=" .$Reg["JG"] . $PageRef2 . '" onmouseover="' . $moveoverlink . '"  onmouseleave="'.$moveleavelink.'" target="_geomap">' . chr(10);
		}
	}
	$output .= '</map>' .chr(10);
	$output .= '</div>' .chr(10);
	//$output .=  CreateSiteLinkScaled ("Pers", "", 60);
	//pre_R($ButtonMap);
	//pre_R($JGButton2023);
	return $output;
}


function JGMapButtons2022($width) {
	global $ThisPage, $MapIntern, $MapExtern, $JGMap2022, $UseSecUrl;
	
	$picwidth = 1982;
	$picheight = 2036;

				
    //ScaleMap				
	$output = "";
	//$output .=  $ThisPage;
	
	$PageRef1 = 'rkrk_map.php?';
	if (GetAccess("AllPilots")){
		//internal
		$PageRef2 = $MapIntern;
	} else {
		//public
		$PageRef2 = $MapExtern;
	}
	
	$imgDir = rkrk_jgmap_2022_dir();
	$output .=  '<img  id= "jgmappic"  src="'. htmlspecialchars($imgDir . 'jgmap.png', ENT_QUOTES, 'UTF-8') .'" alt="JGmap" usemap="#jgmap" width="'. $width .'">' .chr(10);
	$output .= '<map name="jgmap">' .chr(10);

	foreach ($JGMap2022 as $map) {
		$coords=   $map["coords"];
		$coords = explode(",", $coords);
		$i=0;
		foreach ($coords as $key => $point) {
			$coords[$key] = round($point * $width/$picwidth);
			$i++;
		}
		$coords=  implode("," ,$coords);
		$hoverSrc = $imgDir . basename(str_replace('\\', '/', (string)$map["image"]));
		$functionlink = "SetPicture('" . $hoverSrc . "')";
		if ($UseSecUrl) {
			// secure URL
			$output .= '<area shape="poly" coords="' . $coords .'" alt="' . $map["name"] .'" title="' . $map["name"] .'" href="' . $PageRef1 . base64_encode("Reg=" . $map["JG"] . $PageRef2) . '" onmouseover="' . $functionlink . '"  target="_geomap">' . chr(10);
		} else {
			$output .= '<area shape="poly" coords="' . $coords .'" alt="' . $map["name"] .'" title="' . $map["name"] .'" href="' . $PageRef1 . "Reg=" . $map["JG"] . $PageRef2 . '" onmouseover="' . $functionlink . '"  target="_geomap">' . chr(10);
		}
		//echo'<area shape="poly" coords="' . $map["coords"] .'" alt="' . $map["name"] .'" title="' . $map["name"] .'" href="' . $PageRef1 . $map["JG"] . $PageRef2 . '" >' . chr(10);
	}

	// button map
	$output .= '</map>' .chr(10);
	return $output;
}

function JGMapSelection($width) {
	global $ThisPage, $MapIntern, $MapExtern, $JGMap2022, $Person;
	
	$picwidth = 1982;
	$picheight = 2036;

				
    //ScaleMap				

	$output = "";
	//$output .=  $ThisPage;
	
	$PageRef1 = $ThisPage .'?Reg=';
	$PageRef2 = '&Person='. $Person;
	
	$imgDir = rkrk_jgmap_2022_dir();
	$output .=  '<img  id= "jgmappic"  src="'. htmlspecialchars($imgDir . 'jgmap.png', ENT_QUOTES, 'UTF-8') .'" alt="JGmap" usemap="#jgmap" width="'. $width .'">' .chr(10);
	$output .= '<map name="jgmap">' .chr(10);

	foreach ($JGMap2022 as $map) {
		$coords=   $map["coords"];
		$coords = explode(",", $coords);
		$i=0;
		foreach ($coords as $key => $point) {
			$coords[$key] = round($point * $width/$picwidth);
			//if($i%2==0) {
				//even
			//	$coords[$key] = round($point * $width/$picwidth)+32;
			//} else {
				//odd
			//	$coords[$key] = round($point * $width/$picwidth)+20;
			//}
			$i++;
		}
		$coords=  implode("," ,$coords);
		//echo $map["name"]. ":  " . $coords . "<br>"; 
		$functionlink = "SetSelection('". $map["JG"] . "','" . $map["image"] . "')";
		$output .= '<area shape="poly" coords="' . $coords .'" alt="' . $map["name"] .'" title="' . $map["name"] .'" href="' . $PageRef1 . $map["JG"] . $PageRef2 . '" onmouseover="' . $functionlink . '"  target="_geomap">' . chr(10);
		//echo'<area shape="poly" coords="' . $map["coords"] .'" alt="' . $map["name"] .'" title="' . $map["name"] .'" href="' . $PageRef1 . $map["JG"] . $PageRef2 . '" >' . chr(10);
	}

// button map
	$output .= '</map>' .chr(10);
	return $output;
}

function JGMapButtons($width) {
	global $MapView;
	if (($MapView ?? "2023") == "2022" || ($MapView ?? "") == 2022) {
		return JGMapButtons2022($width);
	}
	return JGMapButtons2023($width);
}
?>

