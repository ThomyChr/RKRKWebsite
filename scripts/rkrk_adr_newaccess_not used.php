<?php
$OptionRKRS=Array();
$TableSetup=Array();
$AddressTypeOptions=Array();
$AdressKey="ADR";
		


if ($AdressTableType=="Kontakt") {
	$OptionRKRS = Array(
							"-"=> 		Array( 	"Text" => "-",
												"Value" => false,
												"Options" => "On,Off",
												"Order" => "1"),
							"Ja"=> Array( 	"Text" => "Ja",
												"Value" => false,
												"Options" => "On,Off",
												"Order" => "2"),
							"Nein"=> Array( 	"Text" => "Nein",
												"Value" => false,
												"Options" => "On,Off",
												"Order" => "3"),
						);
};


if ($AdressTableType=="Kontakt") {
	$AddressTypeOptions = Array(
							"-"=> 		Array( 	"Text" => "-",
												"Value" => false,
												"Options" => "On,Off",
												"Order" => "1"),
							"Pilot"=> Array( 	"Text" => "Pilot",
												"Value" => false,
												"Options" => "On,Off",
												"Order" => "2"),
							"Pilot RKRK"=> Array( 	"Text" => "Pilot RKRK",
												"Value" => false,
												"Options" => "On,Off",
												"Order" => "3"),
							"Pilot Zug"=> Array( 	"Text" => "Pilot Zug",
												"Value" => false,
												"Options" => "On,Off",
												"Order" => "4"),
							"Pilot Neu"=> Array( 	"Text" => "Pilot Neu",
												"Value" => false,
												"Options" => "On,Off",
												"Order" => "5"),
							"Pilot inactive"=> Array( 	"Text" => "Pilot inactive",
												"Value" => false,
												"Options" => "On,Off",
												"Order" => "6"),
							"Jäger"=> Array( 	"Text" => "Jäger",
												"Value" => false,
												"Options" => "On,Off",
												"Order" => "7"),
							"Jäger Amt"=> Array( 	"Text" => "Jäger Amt",
												"Value" => false,
												"Options" => "On,Off",
												"Order" => "8"),
						);
	//pre_r($AddressTypeOptions);
} elseif ($AdressTableType=="Bauern") {
	$AddressTypeOptions = Array(
							"Bauer"=> Array( 	"Text" => "Bauer",
												"Value" => false,
												"Options" => "On,Off",
												"Order" => 1),
							"-"=> Array( 	"Text" => "-",
												"Value" => false,
												"Options" => "On,Off",
												"Order" => 2),
						);
}

	$KontaktOptions = Array(
							"Bauer"=> Array( 	"Text" => "Ja",
												"Value" => false,
												"Options" => "On,Off",
												"Order" => 1),
							"-"=> Array( 	"Text" => "Nein",
												"Value" => false,
												"Options" => "On,Off",
												"Order" => 2),
						);
	
if ($AdressTableType=="Kontakt") {
	$TableSetup = Array(
						"ID" => array ( "Field" => "ID",
											"Text" => "ID",
											"Type" => "input",
											"Length" => "7",
											"Align" => "left",
											"Options" => "",
											"Order" => 1,
											"Sort" => "ID",
											"Show" => false),
						"Initialen" => array ( "Field" => "Initialen",
											"Text" => "Initialen",
											"Type" => "input",
											"Length" => "4",
											"Align" => "left",
											"Options" => "",
											"Order" => 2,
											"Sort" => "Initialen",
											"Show" => true),
						"JG" 	=> array ( "Field" => "JG",
											"Text" => "JG",
											"Type" => "input",
											"Length" => "4",
											"Align" => "right",
											"Options" => "",
											"Order" => 3,
											"Sort" => "JG",
											"Show" => true),
						"Jagdgebiet" => array ( "Field" => "Jagdgebiet",
											"Text" => "Jagdgebiet",
											"Type" => "input",
											"Length" => "20",
											"Align" => "left",
											"Options" => "",
											"Order" => 4,
											"Sort" => "Jagdgebiet",
											"Show" => true),
						"Name" => array ( "Field" => "Name",
											"Text" => "Name",
											"Type" => "input",
											"Length" => "0",
											"Align" => "left",
											"Options" => "",
											"Order" => 5,
											"Sort" => "Name",
											"Show" => true),
						"Kontaktnummer" => array ( "Field" => "Kontaktnummer",
											"Text" => "Kontaktnummer",
											"Type" => "input",
											"Length" => "12",
											"Align" => "left",
											"Options" => "",
											"Order" => 6,
											"Sort" => "",
											"Show" => true),
						"P Telefon" => array ( "Field" => "P Telefon",
											"Text" => "P Telefon",
											"Type" => "input",
											"Length" => "12",
											"Align" => "left",
											"Options" => "",
											"Order" => 7,
											"Sort" => "",
											"Show" => false),
						"Mail" => array ( "Field" => "Mail",
											"Text" => "Mail",
											"Type" => "input",
											"Length" => "25",
											"Align" => "left",
											"Options" => "",
											"Order" => 8,
											"Sort" => "",
											"Show" => false),
						"Adresse" => array ( "Field" => "Adresse",
											"Text" => "Adresse",
											"Type" => "input",
											"Length" => "40",
											"Align" => "left",
											"Order" => 9,
											"Sort" => "",
											"Show" => true),
						"RKRS" => array ( "Field" => "RKRS",
											"Text" => "RKRS",
											"Type" => "select",
											"Length" => "5",
											"Align" => "left",
											"Options" => array_column($OptionRKRS, 'Text'),
											"Order" => 10,
											"Sort" => "",
											"Show" => true),
						"Typ" => array ( "Field" => "Typ",
											"Text" => "Typ",
											"Type" => "select",
											"Length" => "10",
											"Align" => "left",
											"Options" => array_column($AddressTypeOptions, 'Text'),
											"Order" => 11,
											"Sort" => "Typ",
											"Show" => true),
						"Kontakt" => array ( "Field" => "Kontakt",
											"Text" => "Kontakt",
											"Type" => "select",
											"Length" => "10",
											"Align" => "left",
											"Options" => array_column($KontaktOptions, 'Text'),
											"Order" => 11,
											"Sort" => "Typ",
											"Show" => true),
						"Platform" => array ( "Field" => "Platform",
											"Text" => "Platform",
											"Type" => "InputArea",
											"Length" => "30",
											"Align" => "left",
											"Order" => 12,
											"Sort" => "",
											"Show" => true),
						"Einsatz" => array ( "Field" => "Einsatz",
											"Text" => "Einsatz",
											"Type" => "InputArea",
											"Length" => "30",
											"Align" => "left",
											"Order" => 13,
											"Sort" => "",
											"Show" => true),
						"GeoMapLoc" => array ( "Field" => "GeoMapLoc",
											"Text" => "GeoMapLoc",
											"Type" => "InputArea",
											"Length" => "30",
											"Align" => "left",
											"Order" => 14,
											"Sort" => "",
											"Show" => false),
											//GeoMapLoc: LON (~8), LAT (~46)
											//In Google Map typically LAT (~46) , LON (~8) 
											//Switzerland: Latitude: 46° 48' 47.47" N · Longitude: 8° 13' 27.16" E
						"Bemerkung" => array ( "Field" => "Bemerkung",
											"Text" => "Bemerkung",
											"Type" => "InputArea",
											"Length" => "30",
											"Align" => "left",
											"Order" => 15,
											"Sort" => "",
											"Show" => true),
									);
} elseif ($AdressTableType=="Bauern") {
	$TableSetup = Array(
						"ID" => array ( "Field" => "ID",
											"Text" => "ID",
											"Type" => "input",
											"Length" => "7",
											"Align" => "left",
											"Options" => "",
											"Order" => 1,
											"Sort" => "",
											"Show" => false),
						"Initialen" => array ( "Field" => "Initialen",
											"Text" => "Initialen",
											"Type" => "input",
											"Length" => "8",
											"Align" => "left",
											"Options" => "",
											"Order" => 2,
											"Sort" => "Initialen",
											"Show" => true),
						"Betrieb" 	=> array ( "Field" => "Betrieb",
											"Text" => "Betrieb",
											"Type" => "input",
											"Length" => "8",
											"Align" => "right",
											"Options" => "",
											"Order" => 3,
											"Sort" => "Betrieb",
											"Show" => true),
						"Jagdgebiet" => array ( "Field" => "Jagdgebiet",
											"Text" => "Jagdgebiet",
											"Type" => "input",
											"Length" => "20",
											"Align" => "left",
											"Options" => "",
											"Order" => 4,
											"Sort" => "Jagdgebiet",
											"Show" => true),
						"Name" => array ( "Field" => "Name",
											"Text" => "Name",
											"Type" => "input",
											"Length" => "0",
											"Align" => "left",
											"Options" => "",
											"Order" => 5,
											"Sort" => "Name",
											"Show" => true),
						"Kontaktnummer" => array ( "Field" => "Kontaktnummer",
											"Text" => "Kontaktnummer",
											"Type" => "input",
											"Length" => "12",
											"Align" => "left",
											"Options" => "",
											"Order" => 6,
											"Sort" => "",
											"Show" => true),
						"P Telefon" => array ( "Field" => "P Telefon",
											"Text" => "P Telefon",
											"Type" => "input",
											"Length" => "12",
											"Align" => "left",
											"Options" => "",
											"Order" => 7,
											"Sort" => "",
											"Show" => false),
						"Mail" => array ( "Field" => "Mail",
											"Text" => "Mail",
											"Type" => "input",
											"Length" => "25",
											"Align" => "left",
											"Options" => "",
											"Order" => 8,
											"Sort" => "",
											"Show" => false),
						"Adresse" => array ( "Field" => "Adresse",
											"Text" => "Adresse",
											"Type" => "input",
											"Length" => "40",
											"Align" => "left",
											"Options" => "",
											"Order" => 9,
											"Sort" => "",
											"Show" => true),
						"Typ" => array ( "Field" => "Typ",
											"Text" => "Typ",
											"Type" => "select",
											"Length" => "5",
											"Align" => "left",
											"Options" => array_column($AddressTypeOptions, 'Text'),
											"Order" => 10,
											"Sort" => "Typ",
											"Show" => true),
						"Bemerkung" => array ( "Field" => "Bemerkung",
											"Text" => "Bemerkung",
											"Type" => "InputArea",
											"Length" => "50",
											"Align" => "left",
											"Options" => "",
											"Order" => 11,
											"Sort" => "",
											"Show" => true),
						"Felder" => array ( "Field" => "Felder",
											"Text" => "Felder",
											"Type" => "Text",
											"Length" => "60",
											"Align" => "left",
											"Options" => "",
											"Order" => 12,
											"Sort" => "",
											"Show" => true),
									);
}
elseif ($AdressTableType=="Users") {
	$TableSetup = Array(
						"ID" => array ( "Field" => "ID",
											"Text" => "ID",
											"Type" => "input",
											"Length" => "7",
											"Align" => "left",
											"Options" => "",
											"Order" => 1,
											"Sort" => "",
											"Show" => false),
						"Initialen" => array ( "Field" => "Initialen",
											"Text" => "Initialen",
											"Type" => "input",
											"Length" => "8",
											"Align" => "left",
											"Options" => "",
											"Order" => 2,
											"Sort" => "Initialen",
											"Show" => true),
						"Benutzername" 	=> array ( "Field" => "Benutzername",
											"Text" => "Benutzername",
											"Type" => "input",
											"Length" => "30",
											"Align" => "right",
											"Options" => "",
											"Order" => 3,
											"Sort" => "Benutzername",
											"Show" => true),
						"Name" => array ( "Field" => "Name",
											"Text" => "Name",
											"Type" => "input",
											"Length" => "20",
											"Align" => "left",
											"Options" => "",
											"Order" => 4,
											"Sort" => "Name",
											"Show" => true),
						"Vorname" => array ( "Field" => "Vorname",
											"Text" => "Vorname",
											"Type" => "input",
											"Length" => "0",
											"Align" => "left",
											"Options" => "",
											"Order" => 5,
											"Sort" => "Vorname",
											"Show" => true),
						"Passwort" => array ( "Field" => "Passwort",
											"Text" => "Passwort",
											"Type" => "input",
											"Length" => "12",
											"Align" => "left",
											"Options" => "",
											"Order" => 6,
											"Sort" => "",
											"Show" => false),
						"Passwort_hashed" => array ( "Field" => "Passwort_hashed",
											"Text" => "Passwort_hashed",
											"Type" => "input",
											"Length" => "50",
											"Align" => "left",
											"Options" => "",
											"Order" => 7,
											"Sort" => "",
											"Show" => false),
						"Email" => array ( "Field" => "Email",
											"Text" => "Email",
											"Type" => "input",
											"Length" => "20",
											"Align" => "left",
											"Options" => "",
											"Order" => 8,
											"Sort" => "Webaccess",
											"Show" => false),
						"Webaccess" => array ( "Field" => "Webaccess",
											"Text" => "Webaccess",
											"Type" => "input",
											"Length" => "20",
											"Align" => "left",
											"Options" => "",
											"Order" => 9,
											"Sort" => "Webaccess",
											"Show" => false),
						"AccessRights" => array ( "Field" => "AccessRights",
											"Text" => "AccessRights",
											"Type" => "select",
											"Length" => "15",
											"Align" => "left",
											"Options" => array_column($AccessLevels, 'key'),
											"Order" => 10,
											"Sort" => "AccessRights",
											"Show" => true),
						"Regionen" => array ( "Field" => "Regionen",
											"Text" => "Regionen",
											"Type" => "input",
											"Length" => "15",
											"Align" => "left",
											"Options" => "",
											"Order" => 11,
											"Sort" => "",
											"Show" => true),
						"RegZugriff" => array ( "Field" => "RegZugriff",
											"Text" => "Regionen Zugrff",
											"Type" => "input",
											"Length" => "15",
											"Align" => "left",
											"Options" => "",
											"Order" => 12,
											"Sort" => "",
											"Show" => false),
						"PilotInitial" => array ( "Field" => "PilotInitial",
											"Text" => "Piloten Initialen",
											"Type" => "input",
											"Length" => "5",
											"Align" => "left",
											"Options" => "",
											"Order" => 13,
											"Sort" => "",
											"Show" => false),
						"Bemerkung" => array ( "Field" => "Bemerkung",
											"Text" => "Bemerkung",
											"Type" => "InputArea",
											"Length" => "50",
											"Align" => "left",
											"Options" => "",
											"Order" => 14,
											"Sort" => "",
											"Show" => true),
						"LetzterBesuch" => array ( "Field" => "LetzterBesuch",
											"Text" => "Letzter Besuch",
											"Type" => "input",
											"Length" => "20",
											"Align" => "left",
											"Options" => "",
											"Order" => 15,
											"Sort" => "",
											"Show" => true),
						"Preferenz01" => array ( "Field" => "Preferenz01",
											"Text" => "Preferenz01",
											"Type" => "input",
											"Length" => "20",
											"Align" => "left",
											"Options" => "",
											"Order" => 16,
											"Sort" => "",
											"Show" => false),
						"Preferenz02" => array ( "Field" => "Preferenz02",
											"Text" => "Preferenz02",
											"Type" => "input",
											"Length" => "20",
											"Align" => "left",
											"Options" => "",
											"Order" => 17,
											"Sort" => "",
											"Show" => false),
						"Preferenz03" => array ( "Field" => "Preferenz03",
											"Text" => "Preferenz03",
											"Type" => "input",
											"Length" => "20",
											"Align" => "left",
											"Options" => "",
											"Order" => 18,
											"Sort" => "",
											"Show" => false),
						"Preferenz04" => array ( "Field" => "Preferenz04",
											"Text" => "Preferenz04",
											"Type" => "input",
											"Length" => "20",
											"Align" => "left",
											"Options" => "",
											"Order" => 19,
											"Sort" => "",
											"Show" => false),
						"RegisterDate" => array ( "Field" => "RegisterDate",
											"Text" => "Register Datum",
											"Type" => "input",
											"Length" => "20",
											"Align" => "left",
											"Options" => "",
											"Order" => 20,
											"Sort" => "",
											"Show" => false),
									);
}
	//pre_r($TableSetup);
	
	// ===========================================
	// Functions
	// ===========================================
function GetNextID($data) {
	global $AdressKey;
	$MaxNum = 0;
	foreach ($data as $dataline) {
		if ($MaxNum< substr($dataline["ID"],-4)) {
			$MaxNum= substr($dataline["ID"],-4);
		}
	}
	return $AdressKey. sprintf('%04d', $MaxNum+1);
}

Function GetAdrListUsers ($data, $ColSort="ID") {
	global $AddressTypeOptions,$OptionRKRS;

	$NewData = Array();
	foreach ($data as $key => $dataline) {
		$NewData[$dataline["ID"]] = $dataline;
		//$NewData[$dataline["Benutzername"]] = $dataline;
	}
	//sort data
	aasort($NewData, $ColSort);
	return $NewData;
}

Function GetAdrListKntkt ($data, $ColSort="ID") {
	global $AddressTypeOptions,$OptionRKRS;

	$NewData = Array();
	foreach ($data as $key => $dataline) {
		$NewData[$dataline["ID"]] = $dataline;
		$NewData[$dataline["ID"]]["TypOrder"]=sprintf('%04d',$AddressTypeOptions[$dataline["Typ"]]["Order"]) . sprintf('%04d',$OptionRKRS[$dataline["RKRS"]]["Order"]);
		//echo "NewDataID: " . $NewData["ID"] . " - ". $NewData[$dataline["ID"]]["TypOrder"] . "<br>";
	}
	//sort data
	aasort($NewData, $ColSort);
	//aasort($NewData, "TypOrder");
	//aasort($NewData, $ColSort);
	//$columns_1 = array_column($NewData, $ColSort);
	//$columns_2 = array_column($NewData, "TypOrder");
	//array_multisort($columns_1, SORT_ASC, $columns_2, SORT_DESC, $NewData);
	//aasort($NewData, "Typ");
	//pre_r($NewData);
	return $NewData;
}



Function GetAdrListBtr ($data, $FeldList=Array(), $ColSort="Text") {
	global $AddressTypeOptions,$searchSDZ, $replaceSDZ;
	$NewData = Array();
	foreach ($data as $key => $dataline) {
		$NewData[$dataline["ID"]] = $dataline;
		if (($NewData[$dataline["ID"]]["Betrieb"]!="") and ($NewData[$dataline["ID"]]["Betrieb"]!="-")) {
			//pre_r($FeldList[$NewData[$dataline["ID"]]]);
			//echo $dataline["ID"] ." - " .  implode(",", $FeldList[$NewData[$dataline["ID"]]["Betrieb"]]) . "<br>";
			$NewData[$dataline["ID"]]["Felder"]= implode(", ", $FeldList[$NewData[$dataline["ID"]]["Betrieb"]]);
		}
		$NewData[$dataline["ID"]]["TypOrder"]=sprintf('%04d',$AddressTypeOptions[$dataline["Typ"]]["Order"]);
		//echo "NewDataID: " . $NewData["ID"] . " - ". $NewData[$dataline["ID"]]["TypOrder"] . "<br>";
	}
	//sort data
	aasort($NewData, $ColSort);
	aasort($NewData, "TypOrder");
	//pre_r($NewData);
	return $NewData;
}

Function GetBetriebsFeldList($data) {
	global $SystemInfo,$searchSDZ, $replaceSDZ;
	$NewData = Array();
	foreach ($data as $key1 => $Reg) {
		foreach ($Reg as $key2 => $dataline) {
			// [FeldID] => 21-014
			// [Betrieb] => ZG 3008
			//pre_r($dataline);
			if ($dataline["Betrieb"]!="") {
				$NewData[str_replace($searchSDZ, $replaceSDZ,$dataline["Betrieb"])][] = $dataline["FeldID"];
			}
		$data[$key1][$key2]["Listed"]=true;
		}
	}
	//pre_R($NewData);
	return $NewData;
}

Function GetNotListedBetriebs($FeldListe, $Adresses) {
	global $searchSDZ, $replaceSDZ;
	$NewData = Array();
//			[ADR0083] => Array
//        (
//            [ID] => ADR0083
//            [Initialen] => ZG 1004
//            [Betrieb] => ZG 1004
//            [Jagdgebiet] => 
//            [Name] => Rieder David
//            [Kontaktnummer] => +41 79 259 21 87
//            [P Telefon] => 
//            [Mail] => 
//            [Adresse] => Hintergeissboden 1, 6300 Zugerberg, Schweiz
//            [Typ] => Bauer
//            [Bemerkung] => 
//            [Felder] => ZG5-015, ZG5-016, ZG5-017
//        )
	$BetriebsAdressen = Array();
	foreach ($Adresses as $key => $dataline) {
		$BetriebsAdressen[ str_replace($searchSDZ, $replaceSDZ,$dataline["Betrieb"])]["Betrieb"] = str_replace($searchSDZ, $replaceSDZ,$dataline["Betrieb"]);
		$BetriebsAdressen[$dataline["Betrieb"]]["Adresse"] = $dataline;
	}
	
	//pre_r($BetriebsAdressen);
	foreach ($FeldListe as $key1 => $Reg) {
		foreach ($Reg as $key2 => $dataline) {
		//Array
//        (
			// [FeldID] => 21-014
			// [Betrieb] => ZG 3008
//        )
			//pre_r($dataline);
			//echo "Check Betrieb: " . $dataline["Betrieb"] . "- " . $BetriebsAdressen[$dataline["Betrieb"]]["Betrieb"] . "<br>" . chr(10);
			if (($BetriebsAdressen[str_replace($searchSDZ, $replaceSDZ,$dataline["Betrieb"])]["Betrieb"]=="")
				and ($dataline["Betrieb"]!="")
				and ($dataline["Bauer"]!="")
			) {
				$NewData[ str_replace($searchSDZ, $replaceSDZ,$dataline["Betrieb"])]["Betrieb"] = str_replace($searchSDZ, $replaceSDZ,$dataline["Betrieb"]);
				$NewData[$dataline["Betrieb"]]["Feld"][] = $dataline["FeldID"];
				$NewData[$dataline["Betrieb"]]["Adresse"] = $dataline["Bauer"];
			}
		}
	}
	//pre_r($NewData);
	aasort($NewData, "Text");
	return $NewData;
}

Function GetNotListedAdresses($FeldListe, $Adresses) {
	global $searchSDZ, $replaceSDZ;
	$NewData = Array();
//			[ADR0083] => Array
//        (
//            [ID] => ADR0083
//            [Initialen] => ZG 1004
//            [Betrieb] => ZG 1004
//            [Jagdgebiet] => 
//            [Name] => Rieder David
//            [Kontaktnummer] => +41 79 259 21 87
//            [P Telefon] => 
//            [Mail] => 
//            [Adresse] => Hintergeissboden 1, 6300 Zugerberg, Schweiz
//            [Typ] => Bauer
//            [Bemerkung] => 
//            [Felder] => ZG5-015, ZG5-016, ZG5-017
//        )
	$BetriebsAdressen = Array();
	foreach ($Adresses as $key => $dataline) {
		$BetriebsAdressen[$dataline["Name"]."-".$dataline["Kontaktnummer"]] = $dataline;
	}
	
	//pre_r($BetriebsAdressen);
	//pre_r($FeldListe);
	foreach ($FeldListe as $key1 => $Reg) {
		foreach ($Reg as $key2 => $dataline) {
			//pre_R($dataline);
		//Array
//        (
			// [FeldID] => 21-014
			// [Betrieb] => ZG 3008
			// [Bauer] => Kern Hanspeter
			// A: Stockweid, 8932 Mettmenstetten, Schweiz
			// E: martin_volli@hotmail.com
			// T: 41793841284
//        )
			//$BauerKontakt = explode([chr(10) ,','], $dataline["Bauer"]);
			//echo $dataline["Bauer"]. "<br>";
			$delimiters = [chr(10), ',', "A:", "T:", "E:", "M:"];
			$BauerKontakt = explode($delimiters[0], str_replace($delimiters, $delimiters[0], $dataline["Bauer"]));
			//pre_r($BauerKontakt);
			$BName="";
			$BKontaktNr=array();
			$BAdresse="";
			$BMail="";
			foreach ($BauerKontakt as $key => $BKontaktLine) {
				//echo $dataline["Bauer"]. "<br>";
				if ($key==0) {
					$BName= trim($BKontaktLine);
					//echo "Name: " . $BKontaktLine;
				} 
				elseif ((preg_match("/(41|76|77|78|79|43|44)/i",$BKontaktLine)) 
						and !(preg_match("/(6343|6418|5643)/i",$BKontaktLine))) {
					//echo "Kontaktnummer: " . $BKontaktLine;
					$BKontaktNr[]= trim($BKontaktLine);
				}
				elseif (strpos(" " .$BKontaktLine, "@")!=false) {
					//echo "Mail: " . $BKontaktLine;
					$BMail= trim($BKontaktLine);
				} 
				elseif (($BKontaktLine !="") and ($BKontaktLine !=" ")) 
				{
					if ($BAdresse !="") $BAdresse .= ", ";
					$BAdresse .= trim($BKontaktLine);
				}
				//echo "Betriebsinhalt: " . $BetriebsAdressen[$BName."-".$BKontaktNr]["Name"] . " - " . $BName."<br>";
			}
			if (
					($BName != "")
					and ($BetriebsAdressen[$BName."-".$BKontaktNr[0]]["Name"] !=$BName)
				) 
			{
			$NewData[$BName."-".$BKontaktNr[0]]["Betrieb"]=str_replace($searchSDZ, $replaceSDZ,$dataline["Betrieb"]); 
				$NewData[$BName."-".$BKontaktNr[0]]["Name"]=$BName; 
				$NewData[$BName."-".$BKontaktNr[0]]["KontaktNr"]=implode(", ",$BKontaktNr); 
				$NewData[$BName."-".$BKontaktNr[0]]["Adresse"]=$BAdresse; 
				$NewData[$BName."-".$BKontaktNr[0]]["Mail"]=$BMail; 
				$NewData[$BName."-".$BKontaktNr[0]]["Feld"][$dataline["FeldID"]]=$dataline["FeldID"];
				$NewData[$BName."-".$BKontaktNr[0]]["Felder"]=implode(", ", $NewData[$BName."-".$BKontaktNr[0]]["Feld"]);
			}
		}
	}
	//pre_r($NewData);
	aasort($NewData, "Name");
	return $NewData;
}

Function CreateTable($data, $TableSetup, $TblOptions) {
	global $ScreenTableSetupSettings, $MissionSelectionParameter, $DebugWorkSet, $ActPage, $MaxSelectedEntries, $AnzLinesPPage, $ColSort, $TableType, $IconLoc, $ButtonHeightSmall, $AdressTableType, $EnableDelete, $WebFreigabe;

	// Definitions
	$GeoMapURL1 = "rkrk_map.php?FFilter=";
	$GeoMapURL2 ="&Reg=ALL&Strt=on&Cntr=off&Area=on&Pfad=on&GDE=off&JGG=off&Poi=on&Label=off&EX=NO&KA=S&MAP=&Zoom=0.8&".$MissionSelectionParameter;


	$i=1;
	$MaxPages = ceil($MaxSelectedEntries/$AnzLinesPPage);
	$showfrom = ($ActPage-1)*$AnzLinesPPage;
	$showto = $showfrom + $AnzLinesPPage;
	$FontSizeInPoints = 11;
	if ($FontSize== "") {
		$FontSize= "100%";
	}
	$TableColor =  GetTableColor ("");
	AlternateLineTable($TableColor['even'], $FontSize, $FontSizeInPoints, $TableColor['odd']);

		$ScreenTableSetup= $ScreenTableSetupSettings[$TableType];
		


	echo "<br>" . chr(10);
	if ($TableType=="Table") {
		echo $ScreenTableSetup["<div>"].chr(10);
		PageControl($ActPage, $MaxPages, $AnzLinesPPage, $FontSize, false, "Speichern", false, "&" . "Sortieren="  . $ColSort);
		echo $ScreenTableSetup["</div>"].chr(10);
	}
	//echo "<div class='container content line-left padding-8' style='max-width:100%' id='table'>".chr(10);
	echo $ScreenTableSetup["<table line>"];
	//echo  "<div class='container content center padding-16'>";
	//echo $ScreenTableSetup["<table>"] .chr(10);
	if ($TableType=="Flex") {
		echo $ScreenTableSetup["<tr>"] .chr(10);
		echo $ScreenTableSetup["<td line>"] .chr(10);
		PageControl($ActPage, $MaxPages, $AnzLinesPPage, $FontSize, false, "Speichern", false, "&" . "Sortieren="  . $ColSort);
		echo $ScreenTableSetup["</td>"] .chr(10);
		echo $ScreenTableSetup["</tr>"] .chr(10);
	}
	echo $ScreenTableSetup["</table>"] .chr(10);
	

	echo $ScreenTableSetup["<table>"] .chr(10);
	echo $ScreenTableSetup["<tr>"].chr(10);
	if ($EnableDelete) {
		echo $ScreenTableSetup["<th>"] .chr(10);
		echo "<img src='" . $IconLoc . "/void.png' height='" . $ButtonHeightSmall ."' title=''>".chr(10);
		echo $ScreenTableSetup["</th>"] .chr(10);
	}
	echo $ScreenTableSetup["<th>"]."###".$ScreenTableSetup["</th>"].chr(10);
	if ($AdressTableType=="Bauern") {
		echo $ScreenTableSetup["<th nowrap>"].chr(10);
		echo "<img src='" . $IconLoc . "/void.png' height='" . $ButtonHeightSmall ."' title=''>".chr(10);
		echo "<img src='" . $IconLoc . "/void.png' height='" . $ButtonHeightSmall ."' title=''>".chr(10);
		echo $ScreenTableSetup["</th>"].chr(10);
	}
	foreach ($TableSetup AS $TableEntry) {
		if ($TblOptions[$TableEntry["Text"]]["Value"]== true) {
			echo $ScreenTableSetup["<th>"] .chr(10);
			if ($TableEntry["Sort"]!="") {
				//InputField($FieldName, $Value ,$Size=10, $align="left", $type="", $class="")
				echo InputField("", $TableEntry["Text"], $TableEntry["Length"], $TableEntry["Align"], "",  "input-title");
				echo "<input type='image' value='Sortieren' src='" . $IconLoc . "/sortieren.png' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?"  . "ActPage=" . $ActPage . "&" . "Sortieren="  . $TableEntry["Sort"] . "&" . $DebugWorkSet . "&" . $EnableDeleteSet . ".' height='" . $ButtonHeightSmall . "'  title='Spalte sortieren'  style='background-color:transparent; border-color:transparent;'><br>" .chr(10);
			} else {
				echo InputField("", $TableEntry["Text"], $TableEntry["Length"] , $TableEntry["Align"], "",  "input-title");
			}//echo $TableEntry["Text"] . SetFormat($TableType, "</th>") .chr(10);
			echo $ScreenTableSetup["</th>"] .chr(10);

		}
	}

	echo $ScreenTableSetup["</tr>"] .chr(10);

	foreach ($data as $dataline) {
		//pre_R($dataline);
		//echo "ID. " . $dataline["ID"] . " .<br>";
		if ($dataline["ListNr"]!="" && 
			$dataline["ListNr"]>=$showfrom && 
			$dataline["ListNr"]<$showto){
		// Existing Data
			echo $ScreenTableSetup["<tr>"] .chr(10);
			if ($EnableDelete) {
				echo $ScreenTableSetup["<td light>"] .chr(10);
				// delete
				if (GetAccess("DeleteData")) {
					echo "<input type='image' value='1st page' src='" . $IconLoc . "/delete.png' formaction='" . htmlspecialchars($_SERVER["PHP_SELF"]) . "?ActPage=" . $ActPage . "&" . "Sortieren="  . $ColSort . "&" . "ID=" . $dataline['ID'] . "&delete=Y&EnableDelete=" . $_GET["EnableDelete"]. "&DBW=" . $_GET["DBW"]. "' height='" . $ButtonHeightSmall . "' title='Linie " . $i . " löschen'>" .chr(10);
				} else {
					echo "<input type='image' value='1st page' src='" . $IconLoc . "/delete.png' formaction='" . htmlspecialchars($_SERVER["PHP_SELF"]) . "?ActPage=" . $ActPage . "&" . "Sortieren="  . $ColSort . "&" .  "delete=Y&EnableDelete=" . $_GET["EnableDelete"]. "' height='" . $ButtonHeightSmall . "'  title='Linie " . $i . " löschen'>" .chr(10);
				}
			echo $ScreenTableSetup["</td>"] .chr(10);
			}
			
			echo $ScreenTableSetup["<td light>"] .chr(10);
			echo sprintf('%03d', $i);
			echo $ScreenTableSetup["</td>"] .chr(10);
			//echo "<div class='flex-light center' style='white-space: nowrap;'>";
			//echo "<div class='flex-light center' style='min-width:60px; white-space: nowrap;'>";
			if ($AdressTableType=="Bauern") {
				echo $ScreenTableSetup["<td nowrap>"] .chr(10);
				$FieldList = str_replace(", ", " ", $dataline["Felder"]);
				echo'<a href="' .$GeoMapURL1 . $FieldList . $GeoMapURL2 . '" target="_geomap"><img src="' . $IconLoc . '/geomap-q.png" height="' . $ButtonHeightSmall .'" title="Übersicht Felder des Landwirtschaftsbetriebs auf Geomap"> </a>' .chr(10);
				if (GetAccess("DebugDataBase")) {
					//echo $ScreenTableSetup["</td>"] .chr(10);
					//echo $ScreenTableSetup["<td light>"] .chr(10);
					echo'<a href="' .htmlspecialchars($_SERVER["PHP_SELF"]). '?' . 'ActPage=' . $ActPage . '&JSONupdate=' . $dataline['ID'] .'&DBW=' . $_GET["DBW"]. '"><img src="' . $IconLoc . '/update_json.png" height="' . $ButtonHeightSmall .'" title="Adressdaten in JSON files schreiben"> </a>' .chr(10);
				} else {
					//echo $ScreenTableSetup["</td>"] .chr(10);
					//echo $ScreenTableSetup["<td light>"] .chr(10);
					echo'<a href="' .htmlspecialchars($_SERVER["PHP_SELF"]). '?' . 'ActPage=' . $ActPage . '&JSONupdate=' . $dataline['ID'] . '"><img src="' . $IconLoc . '/update_json.png" height="' . $ButtonHeightSmall .'" title="Adressdaten in JSON files schreiben"> </a>' .chr(10);
				}
			} else
			echo $ScreenTableSetup["<td>"] .chr(10);
			echo $ScreenTableSetup["</td>"] .chr(10);
			foreach ($TableSetup AS $TableEntry) {
				if (($TblOptions[$TableEntry["Text"]]["Value"]== true) or
					($TblOptions["alle"]["Value"]== true))
					{
						// show entries
						//echo "<span class='hide-over-1000 textlabel '>";
						//echo $TableEntry["Text"];
						//echo "</span>";
						if ($TableEntry["Type"]=="input") {
							echo $ScreenTableSetup["<td>"] .chr(10);
							echo "<label class='flexlabel textlabel'>".$TableEntry["Text"].": </label>";
							echo InputField($TableEntry["Field"]."-" . $i, $dataline[$TableEntry["Field"]], $TableEntry["Length"] , $TableEntry["Align"], "", "inputfield");
							echo $ScreenTableSetup["</td>"] .chr(10);
						}
						if ($TableEntry["Type"]=="Text") {
							echo $ScreenTableSetup["<td>"] .chr(10);
							echo "<label class='flexlabel textlabel'>".$TableEntry["Text"].": </label>";
							//echo $dataline[$TableEntry["Field"]];
							echo InputField($TableEntry["Field"]."-" . $i, $dataline[$TableEntry["Field"]], $TableEntry["Length"] , $TableEntry["Align"], "","input-title wrap");
							echo $ScreenTableSetup["</td>"] .chr(10);
						}
						if ($TableEntry["Type"]=="InputArea") {
							echo $ScreenTableSetup["<td>"] .chr(10);
							echo "<label class='flexlabel textlabel'>".$TableEntry["Text"].": </label>";
							echo InputTextarea($TableEntry["Field"]."-" . $i, $dataline[$TableEntry["Field"]], $TableEntry["Length"], 1, $TableEntry["Length"] , $TableEntry["Align"], "", "inputfield");
							echo $ScreenTableSetup["</td>"] .chr(10);
						}
						if ($TableEntry["Type"]=="select") {
							echo $ScreenTableSetup["<td>"] .chr(10);
							echo "<label class='flexlabel textlabel'>".$TableEntry["Text"].": </label>";
							echo SelectField($TableEntry["Field"]."-" . $i, strval($dataline[$TableEntry["Field"]]) ,$TableEntry["Options"], "left", "inputfield" );
							echo $ScreenTableSetup["</td>"] .chr(10);
					}
				} else {
					//hidden
					echo '<input type="hidden" name="'.$TableEntry['Field'] . '-' . $i . '" id="'. $TableEntry['Field'] . '-' . $i . '" value="'. $dataline[$TableEntry['Field']] . '" />'. chr(10);
				}
			}
			
			
			echo $ScreenTableSetup["</tr>"] .chr(10);
		}
		$i++;
	}

//new data

	echo $ScreenTableSetup["<tr>"] .chr(10);
	echo $ScreenTableSetup["<td line>"] .chr(10);
	echo "<i>Neue Einträge:</i>". chr(10);
	echo $ScreenTableSetup["</td>"] .chr(10);
	echo $ScreenTableSetup["</tr>"] .chr(10);
	$MaxEntry = $i+5;
	while ($i <= $MaxEntry)  {
		if (true) {
		// Configsform empty
			echo $ScreenTableSetup["<tr>"] .chr(10);
			if ($EnableDelete) {
				echo $ScreenTableSetup["<td light>"] .chr(10);
				echo "<img src='" . $IconLoc . "/void.png' height='" . $ButtonHeightSmall ."' title=''>".chr(10);
				echo $ScreenTableSetup["</td>"] .chr(10);
			}
			echo $ScreenTableSetup["<td light>"] .chr(10);
			echo sprintf('%03d', $i);
			echo $ScreenTableSetup["</td>"] .chr(10);
			if ($AdressTableType=="Bauern") {
				echo $ScreenTableSetup["<td nowrap>"].chr(10);
				echo " <img src='" . $IconLoc . "/void.png' height='" . $ButtonHeightSmall ."' title=''>".chr(10);
				echo " <img src='" . $IconLoc . "/void.png' height='" . $ButtonHeightSmall ."' title=''>".chr(10);
				echo $ScreenTableSetup["</td>"].chr(10);
			}
			echo $ScreenTableSetup["<td light>"] .chr(10);
			echo $ScreenTableSetup["</td>"] .chr(10);
			foreach ($TableSetup AS $TableEntry) {
				if (($TblOptions[$TableEntry["Text"]]["Value"]== true) or
					($TblOptions["alle"]["Value"]== true))
					{
						// show entries
						//echo "<span class='hide-over-1000 textlabel '>";
						//echo $TableEntry["Text"];
						//echo "</span>";
						if ($TableEntry["Type"]=="input") {
							echo $ScreenTableSetup["<td>"] .chr(10);
							echo "<label class='flexlabel textlabel'>".$TableEntry["Text"].": </label>";
							echo InputField($TableEntry["Field"]."-" . $i, "", $TableEntry["Length"] , $TableEntry["Align"], "", "inputfield");
							echo $ScreenTableSetup["</td>"] .chr(10);
						}
						if ($TableEntry["Type"]=="Text") {
							echo $ScreenTableSetup["<td>"] .chr(10);
							echo "<label class='flexlabel textlabel'>".$TableEntry["Text"].": </label>";
							//echo $dataline[$TableEntry["Field"]];
							echo InputField($TableEntry["Field"]."-" . $i, "", $TableEntry["Length"] , $TableEntry["Align"], "", "input-title");
							echo $ScreenTableSetup["</td>"] .chr(10);
						}
						if ($TableEntry["Type"]=="InputArea") {
							echo $ScreenTableSetup["<td>"] .chr(10);
							echo "<label class='flexlabel textlabel'>".$TableEntry["Text"].": </label>";
							echo InputTextarea($TableEntry["Field"]."-" . $i, "", $TableEntry["Length"], 1, $TableEntry["Length"] , $TableEntry["Align"], "", "inputfield");
							echo $ScreenTableSetup["</td>"] .chr(10);
						}
						if ($TableEntry["Type"]=="select") {
							echo $ScreenTableSetup["<td>"] .chr(10);
							echo "<label class='flexlabel textlabel'>".$TableEntry["Text"].": </label>";
							echo SelectField($TableEntry["Field"]."-" . $i, "" ,$TableEntry["Options"], "left", "inputfield" );
							echo $ScreenTableSetup["</td>"] .chr(10);
					}
				} else {
					//hidden
					echo '<input type="hidden" name="'.$TableEntry['Field'] . '-' . $i . '" id="'. $TableEntry['Field'] . '-' . $i . '" value="" />'. chr(10);
				}
			}
		}
		echo $ScreenTableSetup["</tr>"] .chr(10);
		$i++;
	}

	echo $ScreenTableSetup["</table>"] .chr(10);
	echo '<input type="hidden" name="NumEntries" id="NumEntries" value="'. $i. '" />'. chr(10);

	if ($TableType=="Table") {
		echo $ScreenTableSetup["<div>"].chr(10);
		PageControl($ActPage, $MaxPages, $AnzLinesPPage, $FontSize, true, "Speichern", false, "&" . "Sortieren="  . $ColSort);
		echo $ScreenTableSetup["</div>"].chr(10);
	}
	//echo "<div class='container content line-left padding-8' style='max-width:100%' id='table'>".chr(10);
	echo $ScreenTableSetup["<table line>"];
	//echo  "<div class='container content center padding-16'>";
	//echo $ScreenTableSetup["<table>"] .chr(10);
	if ($TableType=="Flex") {
		echo $ScreenTableSetup["<tr>"] .chr(10);
		echo $ScreenTableSetup["<td line>"] .chr(10);
		PageControl($ActPage, $MaxPages, $AnzLinesPPage, $FontSize, true, "Speichern", false, "&" . "Sortieren="  . $ColSort);
		echo $ScreenTableSetup["</td>"] .chr(10);
		echo $ScreenTableSetup["</tr>"] .chr(10);
	}
	echo $ScreenTableSetup["</table>"] .chr(10);


}


?>

