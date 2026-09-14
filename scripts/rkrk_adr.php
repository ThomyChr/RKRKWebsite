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
											"Length" => "20",
											"Align" => "left",
											"Options" => array_column($AccessLevels, 'key'),
											"Order" => 10,
											"Sort" => "AccessRights",
											"Show" => true),
						"Regionen" => array ( "Field" => "Regionen",
											"Text" => "Regionen",
											"Type" => "input",
											"Length" => "20",
											"Align" => "left",
											"Options" => "",
											"Order" => 11,
											"Sort" => "",
											"Show" => true),
						"Bemerkung" => array ( "Field" => "Bemerkung",
											"Text" => "Bemerkung",
											"Type" => "InputArea",
											"Length" => "50",
											"Align" => "left",
											"Options" => "",
											"Order" => 12,
											"Sort" => "",
											"Show" => true),
						"LetzterBesuch" => array ( "Field" => "LetzterBesuch",
											"Text" => "Letzter Besuch",
											"Type" => "input",
											"Length" => "20",
											"Align" => "left",
											"Options" => "",
											"Order" => 13,
											"Sort" => "",
											"Show" => true),
						"Preferenz01" => array ( "Field" => "Preferenz01",
											"Text" => "Preferenz01",
											"Type" => "input",
											"Length" => "20",
											"Align" => "left",
											"Options" => "",
											"Order" => 14,
											"Sort" => "",
											"Show" => false),
						"Preferenz02" => array ( "Field" => "Preferenz02",
											"Text" => "Preferenz02",
											"Type" => "input",
											"Length" => "20",
											"Align" => "left",
											"Options" => "",
											"Order" => 15,
											"Sort" => "",
											"Show" => false),
						"Preferenz03" => array ( "Field" => "Preferenz03",
											"Text" => "Preferenz03",
											"Type" => "input",
											"Length" => "20",
											"Align" => "left",
											"Options" => "",
											"Order" => 16,
											"Sort" => "",
											"Show" => false),
						"Preferenz04" => array ( "Field" => "Preferenz04",
											"Text" => "Preferenz04",
											"Type" => "input",
											"Length" => "20",
											"Align" => "left",
											"Options" => "",
											"Order" => 17,
											"Sort" => "",
											"Show" => false),
						"RegisterDate" => array ( "Field" => "RegisterDate",
											"Text" => "Register Datum",
											"Type" => "input",
											"Length" => "20",
											"Align" => "left",
											"Options" => "",
											"Order" => 18,
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



/**
 * Betriebscode eines Feldes aus XML-Betrieb und ggf. JSON externalNumber.
 */
function rkrk_adr_field_betrieb_code($dataline, &$jsonCache) {
	$betrieb = rkrk_adr_normalize_text(isset($dataline["Betrieb"]) ? $dataline["Betrieb"] : "");
	if ($betrieb === "" || $betrieb === "-") {
		$info = rkrk_adr_betrieb_info_from_field($dataline, $jsonCache);
		$betrieb = trim((string)($info["Betrieb"] ?? ""));
	}
	return $betrieb;
}

/**
 * Vergleichsschlüssel-Varianten für Betriebscodes (CSV/XML/JSON).
 */
function rkrk_adr_betrieb_lookup_keys($betriebCode) {
	$keys = array();
	$norm = rkrk_adr_normalize_text($betriebCode);
	if ($norm === "" || $norm === "-") {
		return array();
	}
	$candidates = array($norm);
	$compact = preg_replace('/\s+/', '', $norm);
	if ($compact !== "" && $compact !== $norm) {
		$candidates[] = $compact;
	}
	if (preg_match('/\b(ZG|ZH)\b[^\d]*([\d][\d\s]*)/iu', $norm, $m)) {
		$candidates[] = strtoupper($m[1]) . " " . trim(preg_replace('/\s+/', ' ', $m[2]));
		$candidates[] = strtoupper($m[1]) . trim(preg_replace('/\s+/', '', $m[2]));
	}
	if (preg_match('/^(ZG|ZH)(.+)$/i', $compact, $m)) {
		$candidates[] = strtoupper($m[1]) . " " . trim($m[2]);
	}
	if (preg_match('/^(\d+)\s+(.+)$/u', $norm, $m)) {
		$candidates[] = $m[1] . " " . trim($m[2]);
		$candidates[] = $m[1] . trim($m[2]);
	}
	foreach ($candidates as $candidate) {
		$key = rkrk_adr_compare_betrieb_key($candidate);
		if ($key !== "") {
			$keys[$key] = $key;
		}
	}
	return array_values($keys);
}

/**
 * Felder eines Betriebs direkt aus dem Feldkatalog (XML/JSON).
 */
function rkrk_adr_collect_felder_for_betrieb($feldkatalog, $betriebCode) {
	$needleKeys = array();
	foreach (rkrk_adr_betrieb_lookup_keys($betriebCode) as $key) {
		$needleKeys[$key] = true;
	}
	if (count($needleKeys) === 0 || !is_array($feldkatalog)) {
		return array();
	}
	$felder = array();
	$jsonCache = array();
	foreach ($feldkatalog as $reg) {
		if (!is_array($reg)) {
			continue;
		}
		foreach ($reg as $dataline) {
			if (empty($dataline["FeldID"])) {
				continue;
			}
			$fieldBetrieb = rkrk_adr_field_betrieb_code($dataline, $jsonCache);
			if ($fieldBetrieb === "") {
				continue;
			}
			$fieldKey = rkrk_adr_compare_betrieb_key($fieldBetrieb);
			if ($fieldKey === "" || !isset($needleKeys[$fieldKey])) {
				continue;
			}
			$felder[$dataline["FeldID"]] = $dataline["FeldID"];
		}
	}
	$felder = array_values($felder);
	natsort($felder);
	return array_values($felder);
}

/**
 * Felder eines Betriebs aus der per GetBetriebsFeldList() erzeugten Liste.
 */
function rkrk_adr_lookup_betrieb_felder($feldList, $betrieb) {
	if (!is_array($feldList) || $betrieb === "" || $betrieb === "-") {
		return array();
	}
	foreach (rkrk_adr_betrieb_lookup_keys($betrieb) as $key) {
		if (isset($feldList[$key]) && is_array($feldList[$key])) {
			return $feldList[$key];
		}
	}
	return array();
}

function rkrk_adr_resolve_betrieb_codes_from_row($dataline) {
	$codes = array();
	foreach (array("Betrieb", "Initialen") as $field) {
		$value = rkrk_adr_normalize_text(isset($dataline[$field]) ? $dataline[$field] : "");
		if ($value !== "" && $value !== "-") {
			$codes[$value] = $value;
		}
	}
	return array_values($codes);
}

function rkrk_adr_resolve_betrieb_code_from_row($dataline) {
	$codes = rkrk_adr_resolve_betrieb_codes_from_row($dataline);
	return (count($codes) > 0) ? $codes[0] : "";
}

function rkrk_adr_felder_for_address_row($dataline, $feldList, $feldkatalog = null) {
	$felder = array();
	if (is_array($feldkatalog)) {
		foreach (rkrk_adr_resolve_betrieb_codes_from_row($dataline) as $betriebCode) {
			foreach (rkrk_adr_collect_felder_for_betrieb($feldkatalog, $betriebCode) as $feldId) {
				$felder[$feldId] = $feldId;
			}
		}
	}
	if (count($felder) === 0) {
		foreach (rkrk_adr_resolve_betrieb_codes_from_row($dataline) as $betriebCode) {
			foreach (rkrk_adr_lookup_betrieb_felder($feldList, $betriebCode) as $feldId) {
				$felder[$feldId] = $feldId;
			}
		}
	}
	$felder = array_values($felder);
	natsort($felder);
	return array_values($felder);
}

Function GetAdrListBtr ($data, $FeldList=Array(), $ColSort="Text", $Feldkatalog=null) {
	global $AddressTypeOptions;
	$NewData = Array();
	foreach ($data as $key => $dataline) {
		$NewData[$dataline["ID"]] = $dataline;
		$felder = rkrk_adr_felder_for_address_row($NewData[$dataline["ID"]], $FeldList, $Feldkatalog);
		if (count($felder) > 0) {
			$NewData[$dataline["ID"]]["Felder"] = implode(", ", $felder);
		} elseif (rkrk_adr_resolve_betrieb_code_from_row($NewData[$dataline["ID"]]) !== "") {
			$NewData[$dataline["ID"]]["Felder"] = "";
		}
		$NewData[$dataline["ID"]]["TypOrder"]=sprintf('%04d',$AddressTypeOptions[$dataline["Typ"]]["Order"]);
	}
	aasort($NewData, $ColSort);
	aasort($NewData, "TypOrder");
	return $NewData;
}

Function GetBetriebsFeldList($data) {
	$NewData = Array();
	if (!is_array($data)) {
		return $NewData;
	}
	$jsonCache = array();
	foreach ($data as $key1 => $Reg) {
		if (!is_array($Reg)) {
			continue;
		}
		foreach ($Reg as $key2 => $dataline) {
			if (empty($dataline["FeldID"])) {
				continue;
			}
			$betriebCode = rkrk_adr_field_betrieb_code($dataline, $jsonCache);
			if ($betriebCode === "") {
				continue;
			}
			foreach (rkrk_adr_betrieb_lookup_keys($betriebCode) as $betriebKey) {
				if (!isset($NewData[$betriebKey])) {
					$NewData[$betriebKey] = array();
				}
				$NewData[$betriebKey][$dataline["FeldID"]] = $dataline["FeldID"];
			}
		}
	}
	foreach ($NewData as $betriebKey => $felder) {
		$felder = array_values($felder);
		natsort($felder);
		$NewData[$betriebKey] = array_values($felder);
	}
	return $NewData;
}

Function GetNotListedBetriebs($FeldListe, $Adresses) {
	global $searchSDZ, $replaceSDZ;
	$NewData = Array();
	// Fehlende Betriebe: Betriebscode in JSON (externalNumber), aber nicht in Adress-CSV.
	$BetriebsAdressen = Array();
	foreach ($Adresses as $key => $dataline) {
		$betriebNorm = rkrk_adr_normalize_text(isset($dataline["Betrieb"]) ? $dataline["Betrieb"] : "");
		$betriebKey = rkrk_adr_compare_key($betriebNorm);
		if ($betriebKey !== "") {
			$BetriebsAdressen[$betriebKey] = $betriebNorm;
		}
	}

	$jsonCache = array();
	if (!is_array($FeldListe)) {
		return $NewData;
	}
	foreach ($FeldListe as $key1 => $Reg) {
		if (!is_array($Reg)) {
			continue;
		}
		foreach ($Reg as $key2 => $dataline) {
			try {
				if (empty($dataline["JasonFile"])) {
					continue;
				}
				$info = rkrk_adr_betrieb_info_from_field($dataline, $jsonCache);
				if (empty($info["fromJson"])) {
					continue;
				}
				$betriebRaw = $info["Betrieb"];
				$betriebKey = rkrk_adr_compare_key($betriebRaw);
				if ($betriebKey === "" || isset($BetriebsAdressen[$betriebKey])) {
					continue;
				}
				if (!isset($NewData[$betriebKey])) {
					$NewData[$betriebKey]["Betrieb"] = $betriebRaw;
					$NewData[$betriebKey]["Name"] = $info["Name"];
					$NewData[$betriebKey]["Adresse"] = ($info["Adresse"] !== "" ? $info["Adresse"] : $info["Name"]);
					$NewData[$betriebKey]["Feld"] = array();
				}
				if (!empty($dataline["FeldID"])) {
					$NewData[$betriebKey]["Feld"][$dataline["FeldID"]] = $dataline["FeldID"];
					$NewData[$betriebKey]["Felder"] = implode(", ", array_values($NewData[$betriebKey]["Feld"]));
				}
			} catch (Throwable $e) {
				continue;
			}
		}
	}
	aasort($NewData, "Betrieb");
	return $NewData;
}

function rkrk_adr_safe_utf8($text) {
	$text = (string)$text;
	if ($text === "") {
		return "";
	}
	if (strlen($text) > 4000) {
		$text = substr($text, 0, 4000);
	}
	if (function_exists("iconv")) {
		$clean = @iconv("UTF-8", "UTF-8//IGNORE", $text);
		if ($clean !== false) {
			$text = $clean;
		}
	}
	if (function_exists("mb_convert_encoding")) {
		$text = mb_convert_encoding($text, "UTF-8", "UTF-8");
	}
	return $text;
}

function rkrk_adr_preg_replace_u($pattern, $replacement, $subject) {
	$subject = rkrk_adr_safe_utf8($subject);
	if ($subject === "") {
		return "";
	}
	try {
		$result = preg_replace($pattern, $replacement, $subject);
	} catch (Throwable $e) {
		$result = null;
	}
	if ($result === null) {
		$patternAscii = str_replace("/u", "/", $pattern);
		$result = preg_replace($patternAscii, $replacement, $subject);
		if ($result === null) {
			return $subject;
		}
	}
	return $result;
}

function rkrk_adr_preg_match_u($pattern, $subject, &$matches = null) {
	$subject = rkrk_adr_safe_utf8($subject);
	if ($subject === "") {
		return 0;
	}
	try {
		return preg_match($pattern, $subject, $matches);
	} catch (Throwable $e) {
		$patternAscii = str_replace("/u", "/", $pattern);
		return preg_match($patternAscii, $subject, $matches);
	}
}

/**
 * Mehrfach kodierte Umlaute/Mojibake reparieren (UTF-8, ISO-8859-1, HTML-Entities).
 */
function rkrk_adr_repair_mojibake($text) {
	$text = rkrk_adr_safe_utf8($text);
	if ($text === "") {
		return "";
	}
	$text = str_replace(array("\0", "\xEF\xBB\xBF"), "", $text);
	$text = html_entity_decode($text, ENT_QUOTES, "UTF-8");

	$mojibakeMap = array(
		"Ã¤" => "ä", "Ã„" => "Ä", "Ã¶" => "ö", "Ã–" => "Ö",
		"Ã¼" => "ü", "Ãœ" => "Ü", "ÃŸ" => "ß",
		"Ã©" => "é", "Ã¨" => "è", "Ãª" => "ê", "Ã«" => "ë",
		"Ã¡" => "á", "Ã " => "à", "Ã¢" => "â", "Ã£" => "ã",
		"Ã­" => "í", "Ã³" => "ó", "Ãº" => "ú", "Ã±" => "ñ",
		"Â©" => "é", "Âä" => "ä", "Âö" => "ö", "Âü" => "ü",
		"ÂÄ" => "Ä", "ÂÖ" => "Ö", "ÂÜ" => "Ü",
		"ï¿½" => "",
	);
	for ($pass = 0; $pass < 4; $pass++) {
		$prev = $text;
		if (strpos($text, "Ã") !== false || strpos($text, "Â") !== false) {
			$tryLatin = @iconv("ISO-8859-1", "UTF-8//IGNORE", $text);
			if ($tryLatin !== false && $tryLatin !== "" && strlen($tryLatin) <= strlen($text) * 2) {
				$text = $tryLatin;
			}
		}
		$text = strtr($text, $mojibakeMap);
		$text = str_replace("Â", "", $text);
		if ($text === $prev) {
			break;
		}
	}
	return $text;
}

function rkrk_adr_decode_text_entities($text) {
	$text = (string)$text;
	if ($text === "") {
		return "";
	}
	$text = str_replace(array("&#38;", "&#038;", "&amp;"), "&", $text);
	for ($i = 0; $i < 3; $i++) {
		$decoded = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, "UTF-8");
		if ($decoded === $text) {
			break;
		}
		$text = $decoded;
	}
	return $text;
}

/**
 * Anzeige-/Speicher-Text: Mojibake bereinigen, Whitespace normalisieren.
 */
function rkrk_adr_normalize_text($text) {
	global $searchSD, $replaceSD;
	$text = rkrk_adr_decode_text_entities($text);
	$text = rkrk_adr_repair_mojibake($text);
	if (strpos($text, 'Ã') !== false || strpos($text, 'Â') !== false || strpos($text, 'ï¿') !== false) {
		if (isset($searchSD, $replaceSD) && is_array($searchSD) && is_array($replaceSD)) {
			$text = str_replace($searchSD, $replaceSD, $text);
		}
		$text = rkrk_adr_repair_mojibake($text);
	}
	$text = rkrk_adr_preg_replace_u('/[\x{00A0}\x{2007}\x{202F}]/u', " ", $text);
	$text = rkrk_adr_preg_replace_u('/[\x{200B}-\x{200D}\x{FEFF}]/u', "", $text);
	$text = rkrk_adr_preg_replace_u('/\s+/u', " ", trim($text));
	return $text;
}

/**
 * Vergleichsschlüssel (Namen, Betriebe, Adressen).
 */
function rkrk_adr_compare_key($text) {
	$text = rkrk_adr_normalize_text($text);
	$text = rkrk_adr_safe_utf8($text);
	if ($text !== "" && rkrk_adr_preg_match_u('/[^\p{L}\p{N}\s\-\.@]/u', $text)) {
		$text = rkrk_adr_preg_replace_u('/[^\p{L}\p{N}\s\-\.@]/u', " ", $text);
	} else {
		$text = preg_replace('/[^a-zA-Z0-9\s\-\.@]/', " ", $text);
	}
	$text = preg_replace('/\s+/', " ", trim($text));
	if (function_exists("mb_strtolower")) {
		$lower = @mb_strtolower($text, "UTF-8");
		return ($lower !== false) ? $lower : strtolower($text);
	}
	return strtolower($text);
}

/**
 * Betriebscode für Vergleich (Leerzeichen ignorieren).
 */
function rkrk_adr_compare_betrieb_key($text) {
	return preg_replace('/\s+/', '', rkrk_adr_compare_key($text));
}

/**
 * Personenname als sortierte Wortliste (Vor-/Nachname egal, / als Trenner).
 */
function rkrk_adr_name_token_key($name) {
	$name = rkrk_adr_normalize_text($name);
	if ($name === "") {
		return "";
	}
	$name = preg_replace('/\s*\/\s*/', ' ', $name);
	$name = preg_replace('/\s*&\s*/', ' ', $name);
	$name = trim($name, " ,/-&");
	$key = rkrk_adr_compare_key($name);
	if ($key === "") {
		return "";
	}
	$parts = preg_split('/\s+/', $key);
	$parts = array_values(array_filter($parts, function ($part) {
		return $part !== "";
	}));
	sort($parts);
	return implode(" ", $parts);
}

/**
 * Personenname aus verstümmelter Bauer-Zeile extrahieren.
 */
function rkrk_adr_extract_person_name($text) {
	$text = rkrk_adr_normalize_text($text);
	$text = rkrk_adr_preg_replace_u('/[\s\-]*(?:\+?41|0)[\s\d\(\)\/]{8,}.*$/u', "", $text);
	$text = rkrk_adr_preg_replace_u('/[\s\-]*\d{3}[\s\-]\d{3}[\s\-]\d{2}[\s\-]\d{2}.*$/u', "", $text);
	$text = rkrk_adr_preg_replace_u('/(.)\1{2,}/u', '$1', $text);
	$m = array();
	if ($text !== "" && rkrk_adr_preg_match_u('/^([\p{L}\-\']+(?:\s+[\p{L}\-\']+){0,2})/u', $text, $m)) {
		return trim($m[1]);
	}
	if (preg_match('/^([A-Za-z\-\']+(?:\s+[A-Za-z\-\']+){0,2})/', $text, $m)) {
		return trim($m[1]);
	}
	return trim($text);
}

/**
 * Telefonnummern normalisieren (CH).
 */
function rkrk_adr_normalize_phone($value) {
	$value = trim((string)$value);
	if ($value === "") {
		return "";
	}
	$segments = preg_split('/[,;]+/', $value);
	$out = array();
	foreach ($segments as $segment) {
		$segment = trim($segment);
		if ($segment === "") {
			continue;
		}
		$digits = preg_replace('/\D+/', '', $segment);
		if ($digits === "") {
			continue;
		}
		if (substr($digits, 0, 2) === "41" && strlen($digits) > 2) {
			$digits = "0" . substr($digits, 2);
		} elseif (strlen($digits) === 9 && $digits[0] === "7") {
			$digits = "0" . $digits;
		}
		$out[] = $digits;
	}
	$out = array_values(array_unique($out));
	sort($out);
	return implode("|", $out);
}

/**
 * Telefonnummern-Liste aus String (Komma, Semikolon, Schrägstrich).
 */
function rkrk_adr_split_phone_list($value) {
	$parts = preg_split('/[,;\/]+/', trim((string)$value));
	$out = array();
	foreach ($parts as $part) {
		$part = trim($part);
		if ($part !== "") {
			$out[] = $part;
		}
	}
	return $out;
}

/**
 * Telefonnummern für Adressdatenbank: "+41 79 ..., +41 44 ..."
 */
function rkrk_adr_format_phones_for_db($value) {
	$value = trim((string)$value);
	if ($value === "") {
		return "";
	}
	$out = array();
	$seen = array();
	foreach (rkrk_adr_split_phone_list($value) as $part) {
		$key = rkrk_adr_normalize_phone($part);
		if ($key === "" || isset($seen[$key])) {
			continue;
		}
		$seen[$key] = true;
		if (function_exists("FormatPhoneNumber")) {
			$digits = preg_replace('/\D+/', '', $part);
			if ($digits !== "") {
				$out[] = FormatPhoneNumber($digits);
				continue;
			}
		}
		$out[] = rkrk_adr_normalize_text($part);
	}
	return implode(", ", $out);
}

/**
 * Schweizer Telefonnummer kompakt für JSON: +41796768315
 */
function rkrk_adr_phone_to_json_compact($phone) {
	$digits = preg_replace('/\D+/', '', trim((string)$phone));
	if ($digits === "") {
		return "";
	}
	if (substr($digits, 0, 2) === "41" && strlen($digits) > 2) {
		$digits = substr($digits, 0, 11);
		return "+" . $digits;
	}
	if ($digits[0] === "0" && strlen($digits) > 1) {
		$digits = "41" . substr($digits, 1, 9);
		return "+" . $digits;
	}
	if ($digits[0] === "7") {
		$digits = "41" . substr($digits, 0, 9);
		return "+" . $digits;
	}
	return "+" . $digits;
}

/**
 * Telefonnummern für JSON (UAV): "+4179...,+4144..." ohne Leerzeichen.
 */
function rkrk_adr_format_phones_for_json($value) {
	$value = trim((string)$value);
	if ($value === "") {
		return "";
	}
	$out = array();
	$seen = array();
	foreach (rkrk_adr_split_phone_list($value) as $part) {
		$part = trim($part);
		if ($part === "") {
			continue;
		}
		$clean = rkrk_adr_phone_to_json_compact($part);
		if ($clean === "" || isset($seen[$clean])) {
			continue;
		}
		$seen[$clean] = true;
		$out[] = $clean;
	}
	return implode(",", $out);
}

/**
 * Telefonnummern vergleichen (Format und leicht abgeschnittene JSON-Nummern).
 */
function rkrk_adr_phones_match($phoneA, $phoneB) {
	$keyA = rkrk_adr_normalize_phone($phoneA);
	$keyB = rkrk_adr_normalize_phone($phoneB);
	if ($keyA === "" && $keyB === "") {
		return true;
	}
	if ($keyA === $keyB) {
		return true;
	}
	if ($keyA === "" || $keyB === "") {
		return false;
	}
	$segsA = explode("|", $keyA);
	$segsB = explode("|", $keyB);
	if (count($segsA) !== count($segsB)) {
		return false;
	}
	for ($i = 0; $i < count($segsA); $i++) {
		$a = $segsA[$i];
		$b = $segsB[$i];
		if ($a === $b) {
			continue;
		}
		$minLen = min(strlen($a), strlen($b));
		$maxLen = max(strlen($a), strlen($b));
		if ($minLen >= 8 && ($maxLen - $minLen) <= 2
			&& (strpos($a, $b) === 0 || strpos($b, $a) === 0)) {
			continue;
		}
		return false;
	}
	return true;
}

function rkrk_adr_names_match($nameA, $nameB) {
	$keyA = rkrk_adr_name_token_key($nameA);
	$keyB = rkrk_adr_name_token_key($nameB);
	if ($keyA === "" || $keyB === "") {
		return false;
	}
	return ($keyA === $keyB);
}

/**
 * Prüft, ob ein Feldkatalog-Bauer in der Adress-CSV vorhanden ist (Name + optional Betrieb).
 */
function rkrk_adr_is_in_address_database($bauerName, $bauerBetrieb, $dbRow) {
	if (!is_array($dbRow)) {
		return false;
	}
	if (!rkrk_adr_names_match(isset($dbRow["Name"]) ? $dbRow["Name"] : "", $bauerName)) {
		return false;
	}
	$dbBetrieb = rkrk_adr_compare_key(rkrk_adr_normalize_text(isset($dbRow["Betrieb"]) ? $dbRow["Betrieb"] : ""));
	$feldBetrieb = rkrk_adr_compare_key(rkrk_adr_normalize_text($bauerBetrieb));
	if ($dbBetrieb !== "" && $feldBetrieb !== "" && $dbBetrieb !== $feldBetrieb) {
		return false;
	}
	return true;
}

/**
 * Passenden Adressdatenbank-Eintrag zu Feldkatalog-Bauer suchen.
 */
function rkrk_adr_find_database_row($bauerName, $bauerBetrieb, $bauerPhone, $BetriebsAdressen, $BetriebsAdressenByBetrieb) {
	$nameKey = rkrk_adr_compare_key($bauerName);
	$phoneNorm = rkrk_adr_normalize_phone($bauerPhone);
	$candidateKeys = array();
	if ($phoneNorm !== "") {
		$candidateKeys[] = $nameKey . "|" . $phoneNorm;
	}
	$candidateKeys[] = $nameKey . "|";
	$candidateKeys[] = $nameKey;
	foreach ($candidateKeys as $cKey) {
		if (isset($BetriebsAdressen[$cKey]) && rkrk_adr_is_in_address_database($bauerName, $bauerBetrieb, $BetriebsAdressen[$cKey])) {
			return $BetriebsAdressen[$cKey];
		}
	}
	$feldBetriebKey = rkrk_adr_compare_key(rkrk_adr_normalize_text($bauerBetrieb));
	if ($feldBetriebKey !== "" && isset($BetriebsAdressenByBetrieb[$feldBetriebKey])) {
		$row = $BetriebsAdressenByBetrieb[$feldBetriebKey];
		if (rkrk_adr_is_in_address_database($bauerName, $bauerBetrieb, $row)) {
			return $row;
		}
	}
	return null;
}

/**
 * XML-sicherer UTF-8-Text (keine Steuerzeichen, kein Mojibake).
 */
function rkrk_adr_sanitize_for_xml($text) {
	$text = rkrk_adr_normalize_text($text);
	$text = rkrk_adr_preg_replace_u('/[^\x09\x0A\x0D\x20-\x{D7FF}\x{E000}-\x{FFFD}]/u', "", $text);
	return $text;
}

/**
 * Bauer-Block für Feldkatalog/XML aus bereinigten Einzelteilen.
 */
function rkrk_adr_build_bauer_block($name, $adresse, $phone, $mail) {
	$name = rkrk_adr_sanitize_for_xml(rkrk_adr_extract_person_name($name));
	$adresse = rkrk_adr_sanitize_for_xml($adresse);
	$phone = rkrk_adr_sanitize_for_xml($phone);
	$mail = rkrk_adr_sanitize_for_xml($mail);
	$block = $name;
	if ($adresse !== "") {
		$block .= chr(10) . "A: " . $adresse;
	}
	if ($phone !== "") {
		$block .= chr(10) . "T: " . $phone;
	}
	if ($mail !== "") {
		$block .= chr(10) . "M: " . $mail;
	}
	return $block;
}

/**
 * Bauer-Text aus Feldkatalog parsen (Name, Tel, Adresse, Mail).
 */
function rkrk_adr_parse_bauer_block($bauerText) {
	$bauerText = rkrk_adr_repair_mojibake($bauerText);
	$delimiters = array(chr(10), ",", "A:", "T:", "E:", "M:");
	$lines = explode($delimiters[0], str_replace($delimiters, $delimiters[0], $bauerText));
	$result = array(
		"Name" => "",
		"KontaktNr" => array(),
		"Adresse" => "",
		"Mail" => "",
	);
	foreach ($lines as $idx => $line) {
		$line = trim($line);
		if ($line === "") {
			continue;
		}
		if ($idx === 0) {
			$result["Name"] = rkrk_adr_extract_person_name($line);
		} elseif ((preg_match("/(41|76|77|78|79|43|44)/i", $line))
			&& !(preg_match("/(6343|6418|5643)/i", $line))) {
			foreach (rkrk_adr_split_phone_list($line) as $phonePart) {
				$phonePart = rkrk_adr_normalize_text($phonePart);
				if ($phonePart !== "") {
					$result["KontaktNr"][] = $phonePart;
				}
			}
		} elseif (strpos(" " . $line, "@") !== false) {
			$result["Mail"] = rkrk_adr_normalize_text($line);
		} else {
			if ($result["Adresse"] !== "") {
				$result["Adresse"] .= ", ";
			}
			$result["Adresse"] .= rkrk_adr_normalize_text($line);
		}
	}
	$result["Name"] = rkrk_adr_normalize_text($result["Name"]);
	$result["Adresse"] = rkrk_adr_normalize_text($result["Adresse"]);
	$result["Mail"] = rkrk_adr_normalize_text($result["Mail"]);
	return $result;
}

/**
 * Typ- und Regionsfilter aus GET/POST (Landwirtschaftsbetriebe-Liste).
 */
function rkrk_adr_parse_list_filters($RegList, $RegionSettings, $AddressTypeOptions, $SetAll) {
	$SearchOption = array();
	$optIdx = 1;
	foreach ($AddressTypeOptions as $Opt) {
		if ((isset($_POST["Opt-" . $optIdx]) && $_POST["Opt-" . $optIdx] === "on") || $SetAll) {
			$SearchOption[] = $Opt["Text"];
		}
		$optIdx++;
	}

	$EnabledRegion = array();
	$RegionSelected = array();
	$NoRegionSelected = true;

	if ($_SERVER["REQUEST_METHOD"] === "POST") {
		foreach ($RegList as $Reg) {
			if (!empty($Reg["Aktiv"]) && $Reg["Reg"] !== "NO" && isset($_POST[$Reg["Reg"]]) && $_POST[$Reg["Reg"]] === "on") {
				$NoRegionSelected = false;
				break;
			}
		}
	} else {
		foreach ($RegList as $Reg) {
			if (!empty($Reg["Aktiv"]) && !empty($Reg["Reg"]) && $Reg["Reg"] !== "NO"
				&& isset($_GET[$Reg["Reg"]]) && $_GET[$Reg["Reg"]] === "on") {
				$NoRegionSelected = false;
				break;
			}
		}
		if (!empty($_GET["RegFilter"])) {
			$NoRegionSelected = false;
		}
	}

	if ($_SERVER["REQUEST_METHOD"] !== "POST" && !empty($_GET["RegFilter"])) {
		$EnabledRegion = array();
		$RegionSelected = array();
		foreach (explode(",", (string)$_GET["RegFilter"]) as $jg) {
			$jg = trim($jg);
			if ($jg !== "") {
				$RegionSelected[$jg] = $jg;
			}
		}
		foreach ($RegList as $Reg) {
			if (empty($Reg["Aktiv"]) || empty($Reg["Reg"]) || $Reg["Reg"] === "NO") {
				continue;
			}
			$isOn = (isset($_GET[$Reg["Reg"]]) && $_GET[$Reg["Reg"]] === "on");
			if (!$isOn && count($RegionSelected) > 0) {
				foreach (explode(",", $RegionSettings[$Reg["Reg"]]["JGList"]) as $RegSel) {
					$RegSel = trim($RegSel);
					if ($RegSel !== "" && isset($RegionSelected[$RegSel])) {
						$isOn = true;
						break;
					}
				}
			}
			if ($isOn) {
				$EnabledRegion[$Reg["Reg"]] = 1;
			} else {
				$EnabledRegion[$Reg["Reg"]] = 0;
			}
		}
		return array(
			"SearchOption" => $SearchOption,
			"EnabledRegion" => $EnabledRegion,
			"RegionSelected" => array_values($RegionSelected),
			"NoRegionSelected" => $NoRegionSelected,
		);
	}

	foreach ($RegList as $Reg) {
		if (empty($Reg["Aktiv"]) || empty($Reg["Reg"]) || $Reg["Reg"] === "NO") {
			continue;
		}
		$isOn = (isset($_POST[$Reg["Reg"]]) && $_POST[$Reg["Reg"]] === "on")
			|| (isset($_GET[$Reg["Reg"]]) && $_GET[$Reg["Reg"]] === "on")
			|| ($Reg["Reg"] === "ALL" && $NoRegionSelected);
		if ($isOn) {
			$EnabledRegion[$Reg["Reg"]] = 1;
			foreach (explode(",", $RegionSettings[$Reg["Reg"]]["JGList"]) as $RegSel) {
				$RegSel = trim($RegSel);
				if ($RegSel !== "") {
					$RegionSelected[$RegSel] = $RegSel;
				}
			}
		} else {
			$EnabledRegion[$Reg["Reg"]] = 0;
		}
	}

	return array(
		"SearchOption" => $SearchOption,
		"EnabledRegion" => $EnabledRegion,
		"RegionSelected" => array_values($RegionSelected),
		"NoRegionSelected" => $NoRegionSelected,
	);
}

/**
 * Regionsauswahl in GET-Query für Links (Update-Buttons) übernehmen.
 */
function rkrk_adr_query_with_regions($baseQuery, $EnabledRegion, $RegionSelected) {
	if (!is_array($baseQuery)) {
		$baseQuery = array();
	}
	if (is_array($RegionSelected) && count($RegionSelected) > 0) {
		$baseQuery["RegFilter"] = implode(",", array_values($RegionSelected));
	}
	if (is_array($EnabledRegion)) {
		foreach ($EnabledRegion as $regCode => $on) {
			if (!empty($on) && $regCode !== "" && $regCode !== "NO") {
				$baseQuery[$regCode] = "on";
			}
		}
	}
	return $baseQuery;
}

/**
 * Jagdgebiete aus fehlendem Eintrag (Feld-ID, optional JG-Spalte).
 */
function rkrk_adr_missing_entry_jgs($entry) {
	$jgs = array();
	if (!empty($entry["JG"])) {
		$jg = trim((string)$entry["JG"]);
		if ($jg !== "") {
			$jgs[$jg] = $jg;
		}
	}
	$felder = array();
	if (isset($entry["Feld"]) && is_array($entry["Feld"])) {
		$felder = array_values($entry["Feld"]);
	} elseif (!empty($entry["Felder"])) {
		$felder = preg_split('/\s*,\s*/', trim((string)$entry["Felder"]));
	}
	foreach ($felder as $feldId) {
		$feldId = trim((string)$feldId);
		if ($feldId === "") {
			continue;
		}
		if (strpos($feldId, "-") !== false) {
			$jg = explode("-", $feldId)[0];
		} else {
			$jg = $feldId;
		}
		$jg = trim((string)$jg);
		if ($jg !== "") {
			$jgs[$jg] = $jg;
		}
	}
	return array_values($jgs);
}

/**
 * Jagdgebiet-CSV-Wert aus Feld-IDs (z. B. ZG5-015 -> ZG5,ZG6).
 */
function rkrk_adr_jagdgebiet_from_felder($felderString, $betriebCode = "") {
	$jgs = rkrk_adr_missing_entry_jgs(array("Felder" => (string)$felderString));
	if (count($jgs) === 0 && trim((string)$betriebCode) !== "") {
		$compact = preg_replace('/\s+/', '', (string)$betriebCode);
		if (preg_match('/^(ZG|ZH)(\d+)/i', $compact, $m)) {
			$jgs[] = strtoupper($m[1]) . $m[2];
		} elseif (preg_match('/^(ZG|ZH)/i', $compact, $m)) {
			$jgs[] = strtoupper($m[1]);
		}
	}
	return implode(",", $jgs);
}

/**
 * Jagdgebiet-Feld in CSV zusammenführen.
 */
function rkrk_adr_merge_jagdgebiet($existing, $additional) {
	$existing = trim((string)$existing);
	$additional = trim((string)$additional);
	if ($additional === "") {
		return $existing;
	}
	if ($existing === "") {
		return $additional;
	}
	$merged = array();
	foreach (array_merge(explode(",", $existing), explode(",", $additional)) as $jg) {
		$jg = trim($jg);
		if ($jg !== "") {
			$merged[$jg] = $jg;
		}
	}
	return implode(",", array_values($merged));
}

/**
 * JSON-Owner aus Feldkatalog-Zeile lesen (mit Cache pro Datei).
 */
function rkrk_adr_read_json_owner($jsonRelativePath, &$cache) {
	global $RKRKLocalLoactionMissionFiles;
	$jsonRelativePath = trim((string)$jsonRelativePath);
	if ($jsonRelativePath === "") {
		return null;
	}
	if (array_key_exists($jsonRelativePath, $cache)) {
		return $cache[$jsonRelativePath];
	}
	$owner = null;
	if (function_exists("ReadJsonFile") && !empty($RKRKLocalLoactionMissionFiles)) {
		$json = ReadJsonFile($RKRKLocalLoactionMissionFiles . "/" . $jsonRelativePath);
		if (is_array($json) && isset($json["data"]["owner"]) && is_array($json["data"]["owner"])) {
			$owner = $json["data"]["owner"];
		}
	}
	$cache[$jsonRelativePath] = $owner;
	return $owner;
}

/**
 * Betriebs- und Adressdaten aus JSON (externalNumber) bzw. Feldkatalog.
 */
function rkrk_adr_betrieb_info_from_field($dataline, &$jsonCache) {
	$info = array(
		"Betrieb" => rkrk_adr_normalize_text(isset($dataline["Betrieb"]) ? $dataline["Betrieb"] : ""),
		"Name" => "",
		"Adresse" => "",
		"Mail" => "",
		"KontaktNr" => "",
		"fromJson" => false,
	);
	$jsonFiles = array();
	if (!empty($dataline["JasonFile"])) {
		$jsonFiles = preg_split('/\s*,\s*/', (string)$dataline["JasonFile"]);
	}
	foreach ($jsonFiles as $jsonFile) {
		$jsonFile = trim($jsonFile);
		if ($jsonFile === "") {
			continue;
		}
		$owner = rkrk_adr_read_json_owner($jsonFile, $jsonCache);
		if (!is_array($owner)) {
			continue;
		}
		if (!empty($owner["externalNumber"])) {
			$info["Betrieb"] = rkrk_adr_normalize_text($owner["externalNumber"]);
			$info["fromJson"] = true;
		}
		$first = trim((string)($owner["firstname"] ?? ""));
		$last = trim((string)($owner["name"] ?? ""));
		$name = trim($first . " " . $last);
		if ($name !== "") {
			$info["Name"] = rkrk_adr_normalize_text($name);
		}
		if (!empty($owner["address"]["formatted"])) {
			$info["Adresse"] = rkrk_adr_normalize_text($owner["address"]["formatted"]);
		} elseif (!empty($owner["address"]["street"])) {
			$info["Adresse"] = rkrk_adr_normalize_text(
				($owner["address"]["street"] ?? "") . ", "
				. ($owner["address"]["zip"] ?? "") . " "
				. ($owner["address"]["city"] ?? "")
			);
		}
		if (!empty($owner["email"])) {
			$info["Mail"] = rkrk_adr_normalize_text($owner["email"]);
		}
		if (!empty($owner["phone"])) {
			$info["KontaktNr"] = rkrk_adr_format_phones_for_db($owner["phone"]);
		}
		if ($info["Betrieb"] !== "") {
			break;
		}
	}
	if ($info["Name"] === "" && !empty($dataline["Bauer"])) {
		try {
			$bauer = rkrk_adr_parse_bauer_block($dataline["Bauer"]);
			if ($info["Name"] === "" && $bauer["Name"] !== "") {
				$info["Name"] = rkrk_adr_normalize_text($bauer["Name"]);
			}
			if ($info["Adresse"] === "" && $bauer["Adresse"] !== "") {
				$info["Adresse"] = rkrk_adr_normalize_text($bauer["Adresse"]);
			}
			if ($info["Mail"] === "" && $bauer["Mail"] !== "") {
				$info["Mail"] = rkrk_adr_normalize_text($bauer["Mail"]);
			}
			if ($info["KontaktNr"] === "" && !empty($bauer["KontaktNr"])) {
				$info["KontaktNr"] = rkrk_adr_normalize_text(implode(", ", $bauer["KontaktNr"]));
			}
		} catch (Throwable $e) {
		}
	}
	return $info;
}

/**
 * DB-Adresszeile in JSON-Felder street/zip/city/country zerlegen.
 * Unterstützt z. B. "5643 Alikon, Schweiz" (ohne Strasse) und "Seeboden, 8915 Hausen am Albis, Schweiz".
 */
function rkrk_adr_parse_db_address_to_json_parts($adresse) {
	$street = "";
	$zip = "";
	$city = "";
	$country = "Schweiz";
	$adresse = rkrk_adr_normalize_text($adresse);
	if ($adresse === "") {
		return array(
			"street" => $street,
			"zip" => $zip,
			"city" => $city,
			"country" => $country,
		);
	}

	$parts = array_map("trim", explode(",", $adresse));
	$parts = array_values(array_filter($parts, function ($part) {
		return $part !== "";
	}));

	if (count($parts) > 1 && preg_match('/^(?:schweiz|switzerland|ch)$/iu', $parts[count($parts) - 1])) {
		$country = $parts[count($parts) - 1];
		array_pop($parts);
	}

	if (count($parts) === 1) {
		$seg = $parts[0];
		if (preg_match('/^(\d{4,5})\s+(.+)$/', $seg, $m)) {
			$zip = trim($m[1]);
			$city = trim($m[2]);
		} else {
			$street = $seg;
		}
	} elseif (count($parts) >= 2) {
		$first = $parts[0];
		$second = implode(", ", array_slice($parts, 1));
		if (preg_match('/^(\d{4,5})\s+(.+)$/', $first, $m)) {
			$zip = trim($m[1]);
			$city = trim($m[2]);
		} elseif (preg_match('/^(\d{4,5})\s+(.+)$/', $second, $m)) {
			$street = $first;
			$zip = trim($m[1]);
			$city = trim($m[2]);
		} else {
			$street = $first;
			$city = $second;
		}
	}

	$city = trim(preg_replace('/,?\s*(?:schweiz|switzerland|ch)\s*$/iu', "", $city), " ,");
	$street = trim(preg_replace('/,?\s*(?:schweiz|switzerland|ch)\s*$/iu', "", $street), " ,");

	return array(
		"street" => $street,
		"zip" => $zip,
		"city" => $city,
		"country" => $country,
	);
}

/**
 * Formatierte JSON-Adresse ohne doppeltes Land bauen.
 */
function rkrk_adr_build_json_formatted_address($street, $zip, $city, $country) {
	$street = trim((string)$street);
	$zip = trim((string)$zip);
	$city = trim(preg_replace('/,?\s*(?:schweiz|switzerland|ch)\s*$/iu', "", (string)$city), " ,");
	$street = trim(preg_replace('/,?\s*(?:schweiz|switzerland|ch)\s*$/iu', "", $street), " ,");
	$country = trim((string)$country);
	if ($country === "") {
		$country = "Schweiz";
	}

	$line = "";
	if ($street !== "") {
		$line = $street;
	}
	$loc = trim($zip . ($zip !== "" && $city !== "" ? " " : "") . $city);
	if ($loc !== "") {
		$line = ($line !== "") ? ($line . ", " . $loc) : $loc;
	}
	if ($line === "") {
		return "";
	}
	return $line . ", " . $country;
}

/**
 * Personenname-Teil bereinigen (kein Land, kein Adress-Anhang).
 */
function rkrk_adr_clean_person_name_part($name) {
	$name = rkrk_adr_normalize_text($name);
	if ($name === "") {
		return "";
	}
	$name = preg_replace('/,?\s*(?:schweiz|switzerland|ch)\s*$/iu', "", $name);
	$name = trim($name, " ,/-");
	if (strpos($name, ",") !== false) {
		$name = trim(explode(",", $name, 2)[0]);
	}
	return trim($name);
}

/**
 * DB-Name in JSON name/firstname zerlegen (Nachname = erstes Wort).
 */
function rkrk_adr_parse_db_name_to_json_parts($name) {
	$name = rkrk_adr_clean_person_name_part($name);
	if ($name === "") {
		return array(
			"name" => "",
			"firstname" => "",
		);
	}
	$nameParts = preg_split('/\s+/', $name, 2);
	$lastName = isset($nameParts[0]) ? rkrk_adr_clean_person_name_part($nameParts[0]) : "";
	$firstName = isset($nameParts[1]) ? rkrk_adr_clean_person_name_part($nameParts[1]) : "";
	return array(
		"name" => $lastName,
		"firstname" => $firstName,
	);
}

/**
 * Adressdatenbank-Zeile für JSON-Update aufbereiten (Name/Adresse parsen).
 */
function rkrk_adr_prepare_db_row_for_json_update($row) {
	$work = is_array($row) ? $row : array();
	$nameParts = rkrk_adr_parse_db_name_to_json_parts((string)($work["Name"] ?? ""));

	$addrParts = rkrk_adr_parse_db_address_to_json_parts((string)($work["Adresse"] ?? ""));

	$work["address"] = array(
		"externalNumber" => isset($work["Betrieb"]) ? $work["Betrieb"] : "",
		"name" => $nameParts["name"],
		"firstname" => $nameParts["firstname"],
		"street" => $addrParts["street"],
		"zip" => $addrParts["zip"],
		"city" => $addrParts["city"],
		"country" => $addrParts["country"],
	);
	return $work;
}

/**
 * JSON-Dateien und Feldkatalog-Bauer aus Adressdatenbank-Zeile aktualisieren.
 * Sucht alle Felder, deren JSON externalNumber zum Betrieb passt (nicht nur CSV-Felder).
 */
function rkrk_adr_sync_json_from_database_row($row, &$Feldkatalog, &$syncInfo) {
	global $searchSDZ, $replaceSDZ;
	$syncInfo = "";
	if (function_exists("set_time_limit")) {
		@set_time_limit(180);
	}
	$work = rkrk_adr_prepare_db_row_for_json_update($row);
	$betriebCode = trim((string)($work["Betrieb"] ?? ""));
	if ($betriebCode === "") {
		$syncInfo = "JSON-Update nicht möglich: Betrieb fehlt.";
		return false;
	}
	$betriebKey = rkrk_adr_compare_betrieb_key($betriebCode);
	if ($betriebKey === "") {
		$syncInfo = "JSON-Update nicht möglich: Betrieb ungültig.";
		return false;
	}

	$jsonCache = array();
	$updatedKeys = array();
	$updatedCount = 0;
	if (!is_array($Feldkatalog)) {
		$syncInfo = "JSON-Update nicht möglich: Feldkatalog fehlt.";
		return false;
	}

	foreach ($Feldkatalog as $JG => $fields) {
		if (!is_array($fields)) {
			continue;
		}
		foreach ($fields as $FeldID => $dataline) {
			if (!is_array($dataline) || empty($dataline["JasonFile"])) {
				continue;
			}
			$jsonFiles = preg_split('/\s*,\s*/', (string)$dataline["JasonFile"]);
			$matchesBetrieb = false;
			foreach ($jsonFiles as $jsonFile) {
				$jsonFile = trim($jsonFile);
				if ($jsonFile === "") {
					continue;
				}
				$owner = rkrk_adr_read_json_owner($jsonFile, $jsonCache);
				if (!is_array($owner) || empty($owner["externalNumber"])) {
					continue;
				}
				if (rkrk_adr_compare_betrieb_key($owner["externalNumber"]) === $betriebKey) {
					$matchesBetrieb = true;
					break;
				}
			}
			if (!$matchesBetrieb) {
				continue;
			}

			$updateKey = (string)$JG . "|" . (string)$FeldID . "|" . (string)$dataline["JasonFile"];
			if (isset($updatedKeys[$updateKey])) {
				continue;
			}
			$updatedKeys[$updateKey] = true;

			if (function_exists("UpdateAdresseBauer")) {
				UpdateAdresseBauer($JG, $dataline["JasonFile"], $FeldID, $work);
			}

			$Feldkatalog[$JG][$FeldID]["Bauer"] = function_exists("rkrk_adr_build_bauer_block")
				? rkrk_adr_build_bauer_block(
					$work["Name"] ?? "",
					(string)($work["Adresse"] ?? ""),
					$work["Kontaktnummer"] ?? "",
					$work["Mail"] ?? ""
				)
				: str_replace($searchSDZ, $replaceSDZ,
					$work["address"]["name"] . " " . $work["address"]["firstname"] . chr(10) .
					"A: " . $work["address"]["street"] . ", " . $work["address"]["zip"] . " " .
					$work["address"]["city"] . ", " . $work["address"]["country"]
				);
			if (!function_exists("rkrk_adr_build_bauer_block")) {
				if (trim((string)($work["Kontaktnummer"] ?? "")) !== "") {
					$Feldkatalog[$JG][$FeldID]["Bauer"] .= chr(10) . "T: " . $work["Kontaktnummer"];
				}
				if (trim((string)($work["Mail"] ?? "")) !== "") {
					$Feldkatalog[$JG][$FeldID]["Bauer"] .= chr(10) . "M: " . $work["Mail"];
				}
			}
			$updatedCount++;
		}
	}

	if ($updatedCount === 0) {
		$syncInfo = "JSON-Update übersprungen: keine JSON-Dateien für Betrieb " . $betriebCode . " gefunden.";
		return false;
	}
	$syncInfo = "JSON/Feldkatalog für " . $updatedCount . " Feld(er) aktualisiert.";
	return true;
}

/**
 * JSON-Owner in vergleichbare Adresszeile (wie CSV) umwandeln.
 */
function rkrk_adr_json_owner_to_row($owner) {
	if (!is_array($owner)) {
		return array();
	}
	$first = trim((string)($owner["firstname"] ?? ""));
	$last = trim((string)($owner["name"] ?? ""));
	$adresse = "";
	if (!empty($owner["address"]["formatted"])) {
		$adresse = (string)$owner["address"]["formatted"];
	} elseif (!empty($owner["address"]["street"])) {
		$adresse = trim(
			($owner["address"]["street"] ?? "") . ", "
			. ($owner["address"]["zip"] ?? "") . " "
			. ($owner["address"]["city"] ?? "")
		);
	}
	if (rkrk_adr_address_is_empty_placeholder($adresse)) {
		$adresse = "";
	}
	return array(
		"Betrieb" => rkrk_adr_normalize_text(isset($owner["externalNumber"]) ? $owner["externalNumber"] : ""),
		"Name" => rkrk_adr_normalize_text(trim($first . " " . $last)),
		"Kontaktnummer" => rkrk_adr_format_phones_for_db(isset($owner["phone"]) ? $owner["phone"] : ""),
		"Mail" => rkrk_adr_normalize_text(isset($owner["email"]) ? $owner["email"] : ""),
		"Adresse" => rkrk_adr_normalize_text($adresse),
	);
}

/**
 * JSON-Owner in Adressdatenbank-Format (Name: Nachname Vorname, Adresse mit Schweiz).
 */
function rkrk_adr_json_owner_to_db_row($owner) {
	if (!is_array($owner)) {
		return array();
	}
	$last = rkrk_adr_clean_person_name_part($owner["name"] ?? "");
	$first = rkrk_adr_clean_person_name_part($owner["firstname"] ?? "");
	$name = trim($last . ($last !== "" && $first !== "" ? " " : "") . $first);

	$adresse = "";
	if (!empty($owner["address"]["formatted"]) && !rkrk_adr_address_is_empty_placeholder($owner["address"]["formatted"])) {
		$adresse = (string)$owner["address"]["formatted"];
	} elseif (function_exists("rkrk_adr_build_json_formatted_address")) {
		$adresse = rkrk_adr_build_json_formatted_address(
			$owner["address"]["street"] ?? "",
			$owner["address"]["zip"] ?? "",
			$owner["address"]["city"] ?? "",
			$owner["address"]["country"] ?? "Schweiz"
		);
	} elseif (!empty($owner["address"]["street"])) {
		$adresse = trim(
			($owner["address"]["street"] ?? "") . ", "
			. ($owner["address"]["zip"] ?? "") . " "
			. ($owner["address"]["city"] ?? "")
		);
	}
	$adresse = rkrk_adr_normalize_text($adresse);
	if ($adresse !== "" && !rkrk_adr_address_is_empty_placeholder($adresse)
		&& !preg_match('/(?:schweiz|switzerland|ch)\s*$/iu', $adresse)) {
		$adresse = rkrk_adr_normalize_text($adresse . ", Schweiz");
	}
	if (rkrk_adr_address_is_empty_placeholder($adresse)) {
		$adresse = "";
	}

	return array(
		"Betrieb" => rkrk_adr_normalize_text(isset($owner["externalNumber"]) ? $owner["externalNumber"] : ""),
		"Name" => rkrk_adr_normalize_text($name),
		"Kontaktnummer" => rkrk_adr_format_phones_for_db(isset($owner["phone"]) ? $owner["phone"] : ""),
		"Mail" => rkrk_adr_normalize_text(isset($owner["email"]) ? $owner["email"] : ""),
		"Adresse" => $adresse,
	);
}

/**
 * JSON-Owner zu einem Betrieb im Feldkatalog suchen.
 */
function rkrk_adr_find_json_owner_for_betrieb($betriebCode, $Feldkatalog) {
	$betriebKey = rkrk_adr_compare_betrieb_key($betriebCode);
	if ($betriebKey === "" || !is_array($Feldkatalog)) {
		return null;
	}
	$jsonCache = array();
	foreach ($Feldkatalog as $fields) {
		if (!is_array($fields)) {
			continue;
		}
		foreach ($fields as $dataline) {
			if (!is_array($dataline) || empty($dataline["JasonFile"])) {
				continue;
			}
			$jsonFiles = preg_split('/\s*,\s*/', (string)$dataline["JasonFile"]);
			foreach ($jsonFiles as $jsonFile) {
				$jsonFile = trim($jsonFile);
				if ($jsonFile === "") {
					continue;
				}
				$owner = rkrk_adr_read_json_owner($jsonFile, $jsonCache);
				if (!is_array($owner) || empty($owner["externalNumber"])) {
					continue;
				}
				if (rkrk_adr_compare_betrieb_key($owner["externalNumber"]) === $betriebKey) {
					return $owner;
				}
			}
		}
	}
	return null;
}

/**
 * Adressdatenbank-Zeile aus JSON-Owner aktualisieren (nur $data, kein CSV-Save).
 */
function rkrk_adr_merge_json_owner_into_db_row($rowId, &$data, $Feldkatalog, &$syncInfo) {
	$syncInfo = "";
	if (!isset($data[$rowId]) || !is_array($data[$rowId])) {
		$syncInfo = "Datenbank-Update nicht möglich: Datensatz " . $rowId . " nicht gefunden.";
		return false;
	}
	$betriebCode = trim((string)($data[$rowId]["Betrieb"] ?? ""));
	$owner = rkrk_adr_find_json_owner_for_betrieb($betriebCode, $Feldkatalog);
	if (!is_array($owner)) {
		$syncInfo = "Datenbank-Update nicht möglich: kein JSON für Betrieb " . $betriebCode . " gefunden.";
		return false;
	}
	$jsonRow = rkrk_adr_json_owner_to_db_row($owner);
	$updatedFields = array();
	foreach (array("Betrieb", "Name", "Adresse", "Kontaktnummer", "Mail") as $field) {
		if (!isset($jsonRow[$field]) || trim((string)$jsonRow[$field]) === "") {
			continue;
		}
		if ($field === "Kontaktnummer") {
			$data[$rowId][$field] = rkrk_adr_format_phones_for_db($jsonRow[$field]);
		} else {
			$data[$rowId][$field] = $jsonRow[$field];
		}
		$updatedFields[] = $field;
	}
	if (count($updatedFields) === 0) {
		$syncInfo = "Datenbank-Update übersprungen: JSON enthält keine verwertbaren Adressdaten.";
		return false;
	}
	$syncInfo = "Datenbank aus JSON aktualisiert (" . implode(", ", $updatedFields) . ").";
	return true;
}

/**
 * Prüft, ob eine Adresse keinen Strassen-/Ortsinhalt hat (nur Kommas/Land).
 */
function rkrk_adr_address_is_empty_placeholder($address) {
	$address = rkrk_adr_normalize_text($address);
	if ($address === "") {
		return true;
	}
	if (preg_match('/^[\s,]*(?:schweiz|switzerland|ch)?[\s,]*$/iu', $address)) {
		return true;
	}
	$withoutCountry = preg_replace('/,?\s*(?:schweiz|switzerland|ch)\s*$/iu', '', $address);
	$withoutCountry = preg_replace('/,+/', ',', $withoutCountry);
	$withoutCountry = trim($withoutCountry, " ,");
	if ($withoutCountry === "") {
		return true;
	}
	$parts = array_map('trim', explode(',', $withoutCountry));
	$meaningful = array();
	foreach ($parts as $part) {
		if ($part === "" || preg_match('/^\d{4,5}$/', $part)) {
			continue;
		}
		$meaningful[] = $part;
	}
	return count($meaningful) === 0;
}

/**
 * Länderzusatz am Adressende für Vergleich entfernen (Schweiz bleibt in DB/JSON).
 */
function rkrk_adr_strip_country_suffix($address) {
	$address = rkrk_adr_normalize_text($address);
	if ($address === "") {
		return "";
	}
	$address = preg_replace('/(?:,?\s*(?:schweiz|switzerland|ch)\s*)+$/iu', '', $address);
	$address = preg_replace('/,+/', ',', $address);
	return trim($address, " ,");
}

/**
 * Adresse auf vergleichbaren Inhalt reduzieren (leer/Platzhalter = kein Inhalt).
 * Beispiele ohne Inhalt: "", ", , Schweiz", ", Schweiz"
 */
function rkrk_adr_address_meaningful_key($address) {
	if (rkrk_adr_address_is_empty_placeholder($address)) {
		return "";
	}
	$key = rkrk_adr_strip_country_suffix($address);
	if ($key === "") {
		return "";
	}
	$parts = array_map('trim', explode(',', $key));
	$meaningful = array();
	foreach ($parts as $part) {
		if ($part === "") {
			continue;
		}
		if (preg_match('/^\d{4,5}$/', $part)) {
			continue;
		}
		$meaningful[] = $part;
	}
	if (count($meaningful) === 0) {
		return "";
	}
	return rkrk_adr_compare_key(implode(', ', $meaningful));
}

/**
 * Adressen vergleichen; Unterschied nur durch Länderzusatz (Schweiz) zählt nicht.
 */
function rkrk_adr_addresses_match($addressA, $addressB) {
	$keyA = rkrk_adr_address_meaningful_key($addressA);
	$keyB = rkrk_adr_address_meaningful_key($addressB);
	if ($keyA === "" && $keyB === "") {
		return true;
	}
	return ($keyA === $keyB);
}

/**
 * Adressdatenbank-Zeile mit JSON-Inhalt vergleichen.
 */
function rkrk_adr_compare_db_json_address($dbRow, $jsonRow) {
	$diffs = array();
	$fields = array(
		"Betrieb" => "betrieb",
		"Name" => "name",
		"Kontaktnummer" => "phone",
		"Mail" => "mail",
		"Adresse" => "address",
	);
	foreach ($fields as $field => $type) {
		$dbVal = rkrk_adr_normalize_text(isset($dbRow[$field]) ? $dbRow[$field] : "");
		$jsonVal = rkrk_adr_normalize_text(isset($jsonRow[$field]) ? $jsonRow[$field] : "");
		if ($type === "mail") {
			if ($dbVal === "" || $jsonVal === "") {
				continue;
			}
		} elseif ($type === "address") {
			if (rkrk_adr_address_meaningful_key($dbVal) === "" && rkrk_adr_address_meaningful_key($jsonVal) === "") {
				continue;
			}
		} elseif ($dbVal === "" && $jsonVal === "") {
			continue;
		}
		$isDifferent = false;
		if ($type === "betrieb") {
			$isDifferent = (rkrk_adr_compare_betrieb_key($dbVal) !== rkrk_adr_compare_betrieb_key($jsonVal));
		} elseif ($type === "phone") {
			$isDifferent = !rkrk_adr_phones_match($dbVal, $jsonVal);
		} elseif ($type === "name") {
			$isDifferent = !rkrk_adr_names_match($dbVal, $jsonVal);
		} elseif ($type === "address") {
			$isDifferent = !rkrk_adr_addresses_match($dbVal, $jsonVal);
		} elseif ($type === "mail") {
			$isDifferent = (rkrk_adr_compare_key($dbVal) !== rkrk_adr_compare_key($jsonVal));
		} else {
			$isDifferent = (rkrk_adr_compare_key($dbVal) !== rkrk_adr_compare_key($jsonVal));
		}
		if ($isDifferent) {
			$diffs[$field] = array(
				"db" => isset($dbRow[$field]) ? (string)$dbRow[$field] : "",
				"json" => isset($jsonRow[$field]) ? (string)$jsonRow[$field] : "",
			);
		}
	}
	return $diffs;
}

/**
 * Abweichende Adressen: in JSON und Adressdatenbank, aber Felder unterscheiden sich.
 */
function GetJsonDatabaseAddressDeviations($FeldListe, $Adresses) {
	$dbByBetrieb = array();
	foreach ($Adresses as $rowId => $row) {
		if (!is_array($row)) {
			continue;
		}
		$betriebKey = rkrk_adr_compare_betrieb_key(rkrk_adr_normalize_text(isset($row["Betrieb"]) ? $row["Betrieb"] : ""));
		if ($betriebKey !== "" && !isset($dbByBetrieb[$betriebKey])) {
			$dbByBetrieb[$betriebKey] = array(
				"id" => (string)$rowId,
				"row" => $row,
			);
		}
	}

	$jsonCache = array();
	$deviations = array();
	if (!is_array($FeldListe)) {
		return $deviations;
	}
	foreach ($FeldListe as $Reg) {
		if (!is_array($Reg)) {
			continue;
		}
		foreach ($Reg as $dataline) {
			try {
				if (empty($dataline["JasonFile"])) {
					continue;
				}
				$jsonFiles = preg_split('/\s*,\s*/', (string)$dataline["JasonFile"]);
				$owner = null;
				foreach ($jsonFiles as $jsonFile) {
					$jsonFile = trim($jsonFile);
					if ($jsonFile === "") {
						continue;
					}
					$owner = rkrk_adr_read_json_owner($jsonFile, $jsonCache);
					if (is_array($owner) && !empty($owner["externalNumber"])) {
						break;
					}
				}
				if (!is_array($owner) || empty($owner["externalNumber"])) {
					continue;
				}
				$jsonRow = rkrk_adr_json_owner_to_row($owner);
				$betriebKey = rkrk_adr_compare_betrieb_key($jsonRow["Betrieb"]);
				if ($betriebKey === "") {
					continue;
				}
				$dbEntry = null;
				foreach ($dbByBetrieb as $dbKey => $entry) {
					if ($dbKey === $betriebKey) {
						$dbEntry = $entry;
						break;
					}
				}
				if ($dbEntry === null) {
					continue;
				}
				$diffs = rkrk_adr_compare_db_json_address($dbEntry["row"], $jsonRow);
				if (count($diffs) === 0) {
					continue;
				}
				$devKey = $dbEntry["id"];
				if (!isset($deviations[$devKey])) {
					$deviations[$devKey] = array(
						"id" => $dbEntry["id"],
						"betrieb" => $jsonRow["Betrieb"],
						"name" => isset($dbEntry["row"]["Name"]) ? (string)$dbEntry["row"]["Name"] : "",
						"diffs" => $diffs,
						"Feld" => array(),
						"JsonFiles" => array(),
					);
				} else {
					foreach ($diffs as $field => $values) {
						$deviations[$devKey]["diffs"][$field] = $values;
					}
				}
				if (isset($deviations[$devKey]["diffs"]["Adresse"])) {
					$adrDiff = $deviations[$devKey]["diffs"]["Adresse"];
					if (rkrk_adr_addresses_match(
						isset($adrDiff["db"]) ? (string)$adrDiff["db"] : "",
						isset($adrDiff["json"]) ? (string)$adrDiff["json"] : ""
					)) {
						unset($deviations[$devKey]["diffs"]["Adresse"]);
					}
				}
				if (count($deviations[$devKey]["diffs"]) === 0) {
					unset($deviations[$devKey]);
					continue;
				}
				if (!empty($dataline["FeldID"])) {
					$deviations[$devKey]["Feld"][$dataline["FeldID"]] = $dataline["FeldID"];
					$deviations[$devKey]["Felder"] = implode(", ", array_values($deviations[$devKey]["Feld"]));
				}
				foreach ($jsonFiles as $jsonFileEntry) {
					$jsonFileEntry = trim((string)$jsonFileEntry);
					if ($jsonFileEntry !== "") {
						$deviations[$devKey]["JsonFiles"][$jsonFileEntry] = $jsonFileEntry;
					}
				}
				if (!empty($deviations[$devKey]["JsonFiles"])) {
					$deviations[$devKey]["JsonFilesList"] = implode(", ", array_values($deviations[$devKey]["JsonFiles"]));
				}
			} catch (Throwable $e) {
				continue;
			}
		}
	}
	if (function_exists("aasort")) {
		aasort($deviations, "betrieb");
	}
	return $deviations;
}

/**
 * Fehlende Betriebe/Adressen auf gewählte Jagdgebiete (JG aus Feld-ID) einschränken.
 */
function rkrk_adr_filter_missing_by_regions($missingEntries, $RegionSelected) {
	if (!is_array($missingEntries) || count($missingEntries) === 0) {
		return array();
	}
	if (!is_array($RegionSelected) || count($RegionSelected) === 0) {
		return $missingEntries;
	}
	$filtered = array();
	foreach ($missingEntries as $key => $entry) {
		foreach (rkrk_adr_missing_entry_jgs($entry) as $jg) {
			if (in_array($jg, $RegionSelected, true)) {
				$filtered[$key] = $entry;
				break;
			}
		}
	}
	return $filtered;
}

function rkrk_adr_format_missing_felder($entry) {
	if (isset($entry["Felder"]) && trim((string)$entry["Felder"]) !== "") {
		return (string)$entry["Felder"];
	}
	if (isset($entry["Feld"]) && is_array($entry["Feld"])) {
		return implode(", ", array_values($entry["Feld"]));
	}
	return "";
}

function rkrk_adr_format_deviation_json_files($entry) {
	if (isset($entry["JsonFilesList"]) && trim((string)$entry["JsonFilesList"]) !== "") {
		return (string)$entry["JsonFilesList"];
	}
	if (isset($entry["JsonFiles"]) && is_array($entry["JsonFiles"])) {
		$files = array_values($entry["JsonFiles"]);
		natsort($files);
		return implode(", ", $files);
	}
	return "";
}

function rkrk_adr_collect_deviation_json_files($deviations) {
	$all = array();
	if (!is_array($deviations)) {
		return $all;
	}
	foreach ($deviations as $entry) {
		if (!is_array($entry) || empty($entry["JsonFiles"]) || !is_array($entry["JsonFiles"])) {
			continue;
		}
		foreach ($entry["JsonFiles"] as $jsonFile) {
			$jsonFile = trim((string)$jsonFile);
			if ($jsonFile !== "") {
				$all[$jsonFile] = $jsonFile;
			}
		}
	}
	$all = array_values($all);
	natsort($all);
	return $all;
}

Function GetNotListedAdresses($FeldListe, $Adresses) {
	global $searchSDZ, $replaceSDZ;
	$NewData = Array();
	// Fehlende Adressen: Bauer-Daten im Feldkatalog, die nicht in der Adress-CSV stehen.
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
	$BetriebsAdressenByBetrieb = Array();
	foreach ($Adresses as $key => $dataline) {
		$name = rkrk_adr_normalize_text(isset($dataline["Name"]) ? $dataline["Name"] : "");
		$phone = rkrk_adr_normalize_phone(isset($dataline["Kontaktnummer"]) ? $dataline["Kontaktnummer"] : "");
		$idxKey = rkrk_adr_compare_key($name) . "|" . $phone;
		$BetriebsAdressen[$idxKey] = $dataline;
		if ($phone !== "") {
			$BetriebsAdressen[rkrk_adr_compare_key($name) . "|"] = $dataline;
		}
		$BetriebsAdressen[rkrk_adr_compare_key($name)] = $dataline;
		$betriebKey = rkrk_adr_compare_key(rkrk_adr_normalize_text(isset($dataline["Betrieb"]) ? $dataline["Betrieb"] : ""));
		if ($betriebKey !== "") {
			$BetriebsAdressenByBetrieb[$betriebKey] = $dataline;
		}
	}
	
	//pre_r($BetriebsAdressen);
	//pre_r($FeldListe);
	if (!is_array($FeldListe)) {
		return $NewData;
	}
	foreach ($FeldListe as $key1 => $Reg) {
		if (!is_array($Reg)) {
			continue;
		}
		foreach ($Reg as $key2 => $dataline) {
			try {
				$bauer = rkrk_adr_parse_bauer_block(isset($dataline["Bauer"]) ? $dataline["Bauer"] : "");
				$BName = $bauer["Name"];
				$BKontaktNr = $bauer["KontaktNr"];
				$BAdresse = $bauer["Adresse"];
				$BMail = $bauer["Mail"];
				if ($BName === "") {
					continue;
				}
				$dbRow = rkrk_adr_find_database_row(
					$BName,
					isset($dataline["Betrieb"]) ? $dataline["Betrieb"] : "",
					implode(", ", $BKontaktNr),
					$BetriebsAdressen,
					$BetriebsAdressenByBetrieb
				);
				if ($dbRow === null) {
					$phoneNorm = rkrk_adr_normalize_phone(implode(", ", $BKontaktNr));
					$kontaktKey = rkrk_adr_compare_key($BName) . "-" . ($phoneNorm !== "" ? $phoneNorm : "nop");
					$NewData[$kontaktKey]["Betrieb"] = rkrk_adr_normalize_text(isset($dataline["Betrieb"]) ? $dataline["Betrieb"] : "");
					$NewData[$kontaktKey]["Name"] = $BName;
					$NewData[$kontaktKey]["KontaktNr"] = implode(", ", $BKontaktNr);
					$NewData[$kontaktKey]["Adresse"] = $BAdresse;
					$NewData[$kontaktKey]["Mail"] = $BMail;
					$NewData[$kontaktKey]["Feld"][$dataline["FeldID"]] = $dataline["FeldID"];
					$NewData[$kontaktKey]["Felder"] = implode(", ", $NewData[$kontaktKey]["Feld"]);
				}
			} catch (Throwable $e) {
				continue;
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

