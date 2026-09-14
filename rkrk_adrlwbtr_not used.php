<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" >
<?php
	session_start();
	include_once('scripts/rkrk_gen.php');
	include_once('scripts/rkrk_csvfiles.php');
	include_once('scripts/rkrk_inputforms.php');
	include_once('scripts/rkrk_jsonfiles.php');
	include_once('scripts/rkrk_felddaten.php');
	$AdressTableType="Bauern";
	include_once('scripts/rkrk_adr.php');
	include_once('scripts/rkrk_menu.php');
	include_once('rkrk_access_control.php'); 

	//check impropriate IP addresses accessing page
	CheckIPandExit();
	
	$pagetitle = "Adressen Landwirtschaftsbetriebe";
	headerpage($pagetitle , false);
	GrantAccess();
	CreateTopMenu();
	PrintRKRKHeader($pagetitle);
	echo '<link rel="stylesheet" href="../templates/rkrk_flex.css">' . chr(10);

	function DecodeUnicodeEscapes($text) {
		if (!is_string($text)) {
			return $text;
		}
		return preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($matches) {
			return mb_convert_encoding(pack('H*', $matches[1]), 'UTF-8', 'UCS-2BE');
		}, $text);
	}

	function NormalizeBetriebKey($text) {
		$text = (string)$text;
		$text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
		// Normalize common non-breaking/Unicode spaces from XML or copy/paste.
		$text = str_replace("\xC2\xA0", " ", $text);
		$text = preg_replace('/[\x{00A0}\x{2007}\x{202F}]/u', ' ', $text);
		// Remove invisible format chars.
		$text = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $text);
		$text = preg_replace('/\s+/u', ' ', trim($text));
		return $text;
	}

	function BuildBetriebCodeCandidates($betriebCode) {
		$norm = NormalizeBetriebKey($betriebCode);
		$list = array();
		if ($norm !== "") {
			$list[] = $norm;
		}
		$compact = preg_replace('/\s+/', '', $norm);
		// Canonical candidate based on region prefix + detected number block.
		if (preg_match('/\b(ZG|ZH)\b[^\d]*([\d][\d\s]*)/iu', $norm, $m)) {
			$canonical = strtoupper($m[1]) . " " . trim(preg_replace('/\s+/', ' ', $m[2]));
			$list[] = $canonical;
			$list[] = preg_replace('/\s+/', '', $canonical);
		}
		if (preg_match('/^(ZG|ZH)(.+)$/i', $compact, $m)) {
			$list[] = NormalizeBetriebKey(strtoupper($m[1]) . " " . trim($m[2]));
		}
		$list[] = strtoupper($norm);
		$list[] = strtoupper($compact);
		return array_values(array_unique(array_filter($list, function ($v) {
			return $v !== "";
		})));
	}

	function FetchFarmerExportData($betriebCode, &$errorMessage, &$usedCode, &$debugTrace) {
		$errorMessage = "";
		$usedCode = "";
		$debugTrace = array();
		$candidates = BuildBetriebCodeCandidates($betriebCode);
		$debugTrace[] = "input=" . NormalizeBetriebKey($betriebCode);
		$debugTrace[] = "candidates=" . implode(", ", $candidates);
		foreach ($candidates as $candidateCode) {
			$urls = array(
				"https://www.rkrzug.ch/rkr_export.php?exporttype=fbfn&exportformat=json&param1=" . rawurlencode($candidateCode),
				"https://rkrzug.ch/rkr_export.php?exporttype=fbfn&exportformat=json&param1=" . rawurlencode($candidateCode)
			);
			foreach ($urls as $url) {
				$raw = false;
				$source = "file_get_contents";
				$lastErr = "";
				$err = error_get_last();
				$context = stream_context_create(array(
					'http' => array(
						'timeout' => 12,
						'ignore_errors' => true,
						'user_agent' => 'RKRK-Importer/1.0'
					),
					'ssl' => array(
						'verify_peer' => true,
						'verify_peer_name' => true
					)
				));
				$raw = @file_get_contents($url, false, $context);
				$errAfter = error_get_last();
				if ($raw === false && is_array($errAfter) && $errAfter !== $err) {
					$lastErr = (string)($errAfter["message"] ?? "");
				}

				if (($raw === false || trim((string)$raw) === "") && function_exists('curl_init')) {
					$source = "curl-ext";
					$ch = curl_init($url);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($ch, CURLOPT_TIMEOUT, 12);
					curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
					$raw = curl_exec($ch);
					if ($raw === false) {
						$lastErr = "curl-ext: " . curl_error($ch);
					}
					curl_close($ch);
				}

				if (($raw === false || trim((string)$raw) === "") && function_exists('shell_exec')) {
					$source = "curl-cli";
					$cmd = 'curl -L --max-time 12 --connect-timeout 8 -sS ' . escapeshellarg($url) . ' 2>&1';
					$cliOut = @shell_exec($cmd);
					if (is_string($cliOut) && trim($cliOut) !== "") {
						$raw = $cliOut;
					} else {
						$lastErr = ($lastErr !== "" ? $lastErr . " | " : "") . "curl-cli empty";
					}
				}

				if ($raw === false || trim((string)$raw) === "") {
					$debugTrace[] = "try[" . $candidateCode . "] url=" . $url . " source=" . $source . " empty-response" . ($lastErr !== "" ? " err=" . $lastErr : "");
					continue;
				}

				$response = json_decode($raw, true);
				if (!is_array($response)) {
					$debugTrace[] = "try[" . $candidateCode . "] url=" . $url . " invalid-json raw=" . substr(trim((string)$raw), 0, 160);
					continue;
				}
				$status = isset($response["status"]) ? (string)$response["status"] : "(missing)";
				$msg = isset($response["message"]) ? (string)$response["message"] : "";
				$debugTrace[] = "try[" . $candidateCode . "] url=" . $url . " status=" . $status . ($msg !== "" ? " msg=" . $msg : "");
				if (!isset($response["status"]) || strtolower((string)$response["status"]) !== "success") {
					continue;
				}
				if (!isset($response["payload"]) || !is_array($response["payload"])) {
					$debugTrace[] = "try[" . $candidateCode . "] url=" . $url . " payload-missing";
					continue;
				}

				$usedCode = $candidateCode;
				return $response["payload"];
			}
		}

		$errorMessage = "Export-Antwort ist nicht success für " . NormalizeBetriebKey($betriebCode);
		return false;
	}

	function BuildFarmerImportRow($payload, $betriebCode) {
		$lastName = DecodeUnicodeEscapes(trim((string)($payload["farmer_lastname"] ?? "")));
		$firstName = DecodeUnicodeEscapes(trim((string)($payload["farmer_firstname"] ?? "")));
		$name = trim($lastName . " " . $firstName);

		$street = DecodeUnicodeEscapes(trim((string)($payload["farmer_street"] ?? "")));
		$zip = trim((string)($payload["farmer_zip"] ?? ""));
		$city = DecodeUnicodeEscapes(trim((string)($payload["farmer_city"] ?? "")));
		$address = trim($street);
		if ($zip !== "" || $city !== "") {
			$address .= ($address !== "" ? ", " : "") . trim($zip . " " . $city);
		}
		if ($address !== "") {
			$address .= ", Schweiz";
		}

		$phoneList = array();
		$phone1 = trim((string)($payload["farmer_phone"] ?? ""));
		$phone2 = trim((string)($payload["farmer_phone2"] ?? ""));
		if ($phone1 !== "") {
			$phoneList[] = $phone1;
		}
		if ($phone2 !== "" && !in_array($phone2, $phoneList, true)) {
			$phoneList[] = $phone2;
		}

		return array(
			"Initialen" => NormalizeBetriebKey((string)($payload["farmer_number"] ?? $betriebCode)),
			"Betrieb" => NormalizeBetriebKey((string)($payload["farmer_number"] ?? $betriebCode)),
			"Name" => $name,
			"Kontaktnummer" => implode(", ", $phoneList),
			"Mail" => DecodeUnicodeEscapes(trim((string)($payload["farmer_email"] ?? ($payload["email"] ?? "")))),
			"Adresse" => $address,
			"Typ" => "Bauer",
			"Bemerkung" => "Import RKR Zug: " . date("Y-m-d H:i")
		);
	}

	function ImportFarmerToAddressData($betriebCode, &$data, $TableSetup, $filepath, $csvFile, $csvDesc, &$message) {
		$message = "";
		$betriebCode = NormalizeBetriebKey($betriebCode);
		if (!preg_match('/^(ZG|ZH)/i', preg_replace('/\s+/', '', $betriebCode))) {
			$message = "Import nur für Betriebscodes mit ZG/ZH möglich.";
			return false;
		}

		$errorMessage = "";
		$usedCode = "";
		$debugTrace = array();
		$payload = FetchFarmerExportData($betriebCode, $errorMessage, $usedCode, $debugTrace);
		if ($payload === false) {
			$message = $errorMessage;
			return false;
		}

		$effectiveCode = ($usedCode !== "" ? $usedCode : $betriebCode);
		$importRow = BuildFarmerImportRow($payload, $effectiveCode);
		if ($importRow["Name"] === "") {
			$message = "Import abgebrochen: Name fehlt in der Export-Antwort.";
			return false;
		}

		// Always create a new CSV entry from payload data.
		$newId = GetNextID($data);
		$updatedRow = array();
		foreach ($TableSetup as $entry) {
			$field = $entry["Field"];
			$updatedRow[$field] = "";
		}

		$updatedRow["ID"] = $newId;
		foreach ($importRow as $field => $value) {
			if (array_key_exists($field, $updatedRow)) {
				$updatedRow[$field] = $value;
			}
		}

		if (array_key_exists("Jagdgebiet", $updatedRow) && trim((string)$updatedRow["Jagdgebiet"]) === "") {
			$updatedRow["Jagdgebiet"] = strtoupper(substr($effectiveCode, 0, 2));
		}

		$data[$newId] = $updatedRow;
		SaveAdressDataToCSV($data, $TableSetup, $filepath, "UTF-8", true, false, $csvFile, $csvDesc, true);

		$verifyData = csv_to_array($filepath, $csvDesc);
		$verifyFound = false;
		foreach ($verifyData as $row) {
			if (NormalizeBetriebKey((string)($row["Betrieb"] ?? "")) === NormalizeBetriebKey((string)$updatedRow["Betrieb"])
				&& NormalizeBetriebKey((string)($row["Name"] ?? "")) === NormalizeBetriebKey((string)$updatedRow["Name"])) {
				$verifyFound = true;
				break;
			}
		}
		if (!$verifyFound) {
			$message = "Import nicht gespeichert. Bitte erneut versuchen.";
			return false;
		}

		$fullNameForCheck = (string)$payload["farmer_lastname"] . " " . (string)$payload["farmer_firstname"];
		if (preg_match('/\\\\u[0-9a-fA-F]{4}/', $fullNameForCheck)) {
			$message = "Importiert (" . $betriebCode . "), aber Unicode-Escape ist noch sichtbar. Bitte Encoding prüfen.";
		} else {
			$message = "Import erfolgreich als neuer Eintrag (" . $newId . ") für " . $betriebCode . " (" . $importRow["Name"] . ").";
		}
		return true;
	}

	//Write to LOGfile
	WriteToLog("get", basename($_SERVER['PHP_SELF']), "öffnen ","","","");

	SetMissionLocation();
	$MissionSelectionParameter = "MDB=" . $RKRKMissionSelected;
	//echo "Loc: " . $RKRKMissionSelected . " - MDB: " .$csvDocLocation . "<br>";

	// ===========================================
	// Gobal Defintions
	// ===========================================

	$csv_desc = ';';
	$csv_file= $RKRK_LWB_ContactFile;
	$excel_csv_file = "RKRK Landwirte (".$_SESSION[$SessNme_RegisteredInitialen].").csv";
	$excel_csv_path = $historyfilelocation . $excel_csv_file;


	//$csv_file= $RKRKContactFile;

	$OptionJaeger = Array();
	$Feldkatalog= GetFeldListArrayFromXML($xmlMissionLocation.$RKRKFelderfileXML);
	//pre_r($Feldkatalog);

	$FeldList = GetBetriebsFeldList($Feldkatalog);
	//pre_r($FeldList);

	//Read CSV file
	//transferGoogleSheetToCSV();
	$data = csv_to_array($csvDocLocation.$csv_file,$csv_desc);

	$ImportInfo = "";
	if (isset($_GET["ImportBetrieb"]) && trim((string)$_GET["ImportBetrieb"]) !== "") {
		$importResult = false;
		$importMsg = "";
		$importCode = trim((string)$_GET["ImportBetrieb"]);
		$importResult = ImportFarmerToAddressData($importCode, $data, $TableSetup, $csvDocLocation.$csv_file, $csv_file, $csv_desc, $importMsg);
		$ImportInfo = ($importResult ? "<b>Upload erfolgreich:</b> " : "<b>Upload fehlgeschlagen:</b> ") . htmlspecialchars($importMsg);
		$data = csv_to_array($csvDocLocation.$csv_file,$csv_desc);
	}

	$data = GetAdrListBtr ($data, $FeldList);
	$FehlendeBetriebe = GetNotListedBetriebs($Feldkatalog,$data);
	$FehlendeAdressen = GetNotListedAdresses($Feldkatalog,$data);
	$GeoMapURL1 = "rkrk_map.php?FFilter=";
	$GeoMapURL2 ="&Reg=ALL&Strt=on&Cntr=off&Area=on&Pfad=on&GDE=off&JGG=off&Poi=on&Label=off&EX=NO&KA=S&MAP=&Zoom=0.8";

	$SystemInfo = "";
	$DebugInfo = "";

	$SaveFeldkatalog = false;

	// ===========================================
	// Defintions
	// ===========================================
		$fileoutput = "";
		$debugoutput = false;
	//pre_r($TableSetup);

	$RegList = array_diff($RegionSettings, array('NO'));
	

	// ===========================================
	// Parameters
	// ===========================================
	if ($_SERVER["REQUEST_METHOD"] == "POST") {
		$SetAll = false;
	} else {
		$SetAll = true;
	}
	if (isset($_POST["FeldNamenWahl"])) {
		$FeldNamenWahl = $_POST["FeldNamenWahl"];
	} else	{
		$FeldNamenWahl = '';
	}


	if (isset($_GET["EnableDelete"])) {
	   if (strtolower($_GET["EnableDelete"])=="on") {
			//echo "EnableDelete=on";
			$EnableDelete= true;
		} else {
			//echo "EnableDelete=off";
			$EnableDelete= false;
		}
	}

	IF ($EnableDelete) {
		$EnableDeleteSet = "EnableDelete=on";
	} else {
		$EnableDeleteSet = "EnableDelete=off";
	}

	if ($_GET["DoSafe"]=="Y") {
		$dosafe= true;
	} else {
		$dosafe= false;
	}
	
	if (isset($_GET["delete"]) && isset($_GET["ID"]) ) { 
		if ($_GET["delete"]=="Y") {
			$dodelete= $_GET["ID"];
			$dosafe = true;
		} else {
			$dodelete= false;
		}
	} else {
		$dodelete= false;
	}

	if (isset($_GET["JSONupdate"])) {
		$JSONupdate = $_GET["JSONupdate"];
	} else {
		$JSONupdate = "";
	}

	if (isset($_GET["Sortieren"])) {
		$ColSort = $_GET["Sortieren"];
	} else {
		$ColSort = "Name";
	}



///////////////////////////////////////
// update JSON & Feldkatalog files
///////////////////////////////////////

if 	($JSONupdate!="") {
	//echo "update " . $JSONupdate . " - JSON files: " . $data[$JSONupdate]["Felder"] . "<br>";
	// Splitt up Adress in elements
	$data[$JSONupdate]["address"]["externalNumber"] = $data[$JSONupdate]["Betrieb"];
	$data[$JSONupdate]["address"]["name"] = explode(" " ,$data[$JSONupdate]["Name"])[0];
	$data[$JSONupdate]["address"]["firstname"] = trim(substr($data[$JSONupdate]["Name"],strlen($data[$JSONupdate]["address"]["name"])));
	$data[$JSONupdate]["address"]["street"] = trim(explode("," ,$data[$JSONupdate]["Adresse"])[0]);
	$data[$JSONupdate]["address"]["zip"] =trim(explode(" ", trim(explode("," ,$data[$JSONupdate]["Adresse"])[1]))[0]);
	$data[$JSONupdate]["address"]["city"] =trim(explode(" ", trim(explode("," ,$data[$JSONupdate]["Adresse"])[1]))[1]);
	$data[$JSONupdate]["address"]["country"] ="Schweiz";
	foreach (explode(", ",$data[$JSONupdate]["Felder"]) as $Feld) {
		//echo "update " . $JSONupdate . " - JSON file: " . $Feld . "<br>";
		$JG = explode("-",$Feld)[0];
		$JsonFilenames = $Feldkatalog[$JG][$Feld]["JasonFile"];
		UpdateAdresseBauer($JG, $JsonFilenames, $Feld, $data[$JSONupdate]);
		// update of Feldkatalog
		// [Bauer] => Schuler  Sepp
		// A: Hübscherenstrasse 4, 8914 Aeugst am Albis, Schweiz
		// T: 41794453130
		//pre_R($data[$JSONupdate]);
		//pre_R($Feldkatalog[$JG][$Feld]);
		$Feldkatalog[$JG][$Feld]["Bauer"] = 
			str_replace($searchSDZ,$replaceSDZ,
				$data[$JSONupdate]["address"]["name"] . " " . $data[$JSONupdate]["address"]["firstname"] . chr(10) .
				"A: " . $data[$JSONupdate]["address"]["street"] . ", " . $data[$JSONupdate]["address"]["zip"] . " " .
				$data[$JSONupdate]["address"]["city"] . ", " . $data[$JSONupdate]["address"]["country"]
				);
		if ($data[$JSONupdate]["Kontaktnummer"]!="") {
			$Feldkatalog[$JG][$Feld]["Bauer"] .= chr(10) . "T: " . $data[$JSONupdate]["Kontaktnummer"];
		}
		if ($data[$JSONupdate]["Mail"]!="") {
			$Feldkatalog[$JG][$Feld]["Bauer"] .= chr(10) . "M: " . $data[$JSONupdate]["Mail"];
		}
		//pre_R($Feldkatalog[$JG][$Feld]);
	}
	//pre_R($Feldkatalog)
	$SaveFeldkatalog = true;

	///////////////////////////////////
	//Safe Feldkatalog if required
	///////////////////////////////////
	if ($SaveFeldkatalog) {
		//echo "safe Flugkatalog <br>";
		// safe history
		$file = $xmlMissionLocation . $RKRKFelderfileXML;
		$newfile = $historyfilelocation . date("YmdH") . "00_" . $RKRKFelderfileXML;
		//echo "<br>History: " . $file . " to  " . $newfile;
		if (!copy($file, $newfile)) {
			$SystemInfo .= "failed to copy $file...\n";
		}
		SaveFlugkatalog2XML ($Feldkatalog, true);
		SaveFlugkatalog2CSV ($Feldkatalog);
	}
	
}

///////////////////////////////////////
// Process Input Data
///////////////////////////////////////
	if (
		($_SERVER["REQUEST_METHOD"] == "POST")
		) {
		// safe history
		$file = $csvDocLocation . $RKRK_LWB_ContactFile;
		$Min =  intval(date("i")/10)*10;
		$newfile = $historyfilelocation . date("YmdH") . sprintf("%02d", $Min) . "_" . $RKRK_LWB_ContactFile;
		//echo "<br>History: " . $file . " to  " . $newfile;

		for ($i = 1; $i <= $_POST["NumEntries"]; $i++) {
			if (
				$_POST["Initialen-" . $i]!="" or
				$_POST["Betrieb-" . $i]!="" or
				$_POST["Name-" . $i]!=""
				) {
				//echo "ID: " . $_POST["ID-" . $i] . " - " . $_POST["Initialen-" . $i] . "<br>";
				if ($_POST["ID-" . $i]=="") {
					$IDField = GetNextID($data);
				} else { 
					$IDField = $_POST["ID-" . $i];
				}
				// copy input data to internal array
				foreach ($TableSetup AS $TableEntry) {
					if ($TableEntry["Field"]=="ID") {
						$data[$IDField][$TableEntry["Field"]] =$IDField;
					} else {
						$data[$IDField][$TableEntry["Field"]] =$_POST[$TableEntry["Field"]."-" . $i];
					}
				}
			}
		}	
		//pre_r($data);

		//echo "StoreLocation: " .$csvDocLocation . $csv_file . "<br>";
		$filepath= $csvDocLocation . $csv_file;
		
		$format = "UTF-8";
		//$format = "UTF-16LE"; //'UTF-16LE', 'UTF-8'
		$dosafe = true;
		$new= true;
		
		if ($new) {
			
			SaveAdressDataToCSV($data, $TableSetup, $filepath, $format, $dosafe, $dodelete, $csv_desc, $csv_desc, true);
		} else {


			//new configuration, safe configuration file
			//echo "NumE: " . $_POST["NumEntries"] .  "<br>" . chr(10);
			$i= 1;

			$filecontent = "";
			//$filecontent = 'ID;Initialen;Betrieb;Jagdgebiet;Name;Kontaktnummer;P Telefon;Mail;Adresse;Typ;Bemerkung;Felder' . chr(13) . chr(10);
			foreach ($TableSetup AS $TableEntry) {
				$filecontent .= $TableEntry["Field"] . ";";
			}
			$filecontent = substr($filecontent, 0, -1). chr(13) . chr(10); 

			//pre_r($data);
			foreach ($data as $dataline) {
			//Initialen,Betrieb,Jagdgebiet,Name,Kontaktnummer,P Telefon,Mail,Adresse,Typ,Bemerkung,Felder
				//echo "ID: ". $dataline["ID"] . "<br>";
				if (
					$dataline["Initialen"] !="" &&
					$dataline["Betrieb"] !=""
					) {
					IF ($dodelete!= $dataline["ID"]) {
						// delete marked line
						
						//$rowcontent = chr(34);
						$rowcontent = "";
						//$rowcontent .= CheckForKomma($i) . $csv_desc;

						//collecting output data
						foreach ($TableSetup AS $TableEntry) {
							$rowcontent .= CheckForSemicolon($dataline[$TableEntry["Field"]]) . $csv_desc;
						}
						//cut last descriminator
						$rowcontent= substr($rowcontent, 0, -1);
						$rowcontent .= chr(13) . chr(10);
						//echo $dataline["ID"] . " - " . $rowcontent . "<br>";
						$i++;
						$filecontent .= $rowcontent;
					}
				}
			}
			
			if (($dosafe)) {
				$filepath= $csvDocLocation . $csv_file;

				// safe history
				$file = $filepath;
				//$newfile = $historyfilelocation . date("YmdH") . "00_" . $csv_file;
				//echo "<br>History: " . $file . " to  " . $newfile;
				if (!copy($file, $newfile)) {
					$SystemInfo .= "failed to copy $file...\n";
				}

				// safe file
				//echo "FilePath: " . $filepath . " - NumEntries:" . $i . "<br>";
				$df = fopen($filepath, 'w');
				file_put_contents($df, "");
				fputs($df, $filecontent);
				fclose($df);
			} else {
				//echo "output: <br><br>" . $filecontent;

			}
		}
	}

	//read file (again after writing to file
	//transferGoogleSheetToCSV($csvDocLocation.$csv_file);
	$data = csv_to_array($csvDocLocation.$csv_file,$csv_desc);
	$data = GetAdrListBtr ($data, $FeldList, $ColSort);
	//pre_r($data);

		// ===========================================
		// Print Page content
		// ===========================================
		$output = "";
		
		// Printing all the keys and values one by one

// =====================
// Prepare Page output
///=====================


	//pre_r(array_keys($Feldkatalog));
	//control elements
	if (isset($_POST["AnzLinesPPage"])) {
		if ($_POST["AnzLinesPPage"]=="all") {
			$AnzLinesPPage= 999;
		} else {
			$AnzLinesPPage= $_POST["AnzLinesPPage"];
		}
	} else {
		$AnzLinesPPage= 30;
	}
	if (isset($_GET["ActPage"]) and $_GET["ActPage"]!="") {
		$ActPage= $_GET["ActPage"];
	} else {
		$ActPage= 1;
	}
	if (isset($_POST["FontSize"]) and $_POST["FontSize"]!="") {
		$FontSize= $_POST["FontSize"];
	} else {
		$FontSize= "100%";
	}


	// ===========================================
	// FORM
	// ===========================================

    
//Initialen,JG,Jagdgebiet,Name,Kontaktnummer,P Telefon,Mail,Adresse,RKRS,Bemerkung
	$FontSizeInPoints = 11;
	if ($FontSize== "") {
		$FontSize= "100%";
	}
	$TableColor =  GetTableColor ("");
	AlternateLineTable($TableColor['even'], $FontSize, $FontSizeInPoints, $TableColor['odd']);
	SetTableWidth("100%");

	$i=1;
	$pageline = 1;

	$DataSetFound = false;

	echo "<div class='rkrk-flex-page'>" . chr(10);
	echo '<form class="rkrk-flex-form" method="post" action="'. htmlspecialchars($_SERVER["PHP_SELF"]) . '">'. chr(10);
	echo "<table class='rkrk-filter-table'>";
	echo "<tr>";
	echo "<td>";
	echo "Typ:";
	echo "</td>";
	echo "<td>";
	$EnabledOption = Array();
	$SearchOption = Array();
	$i=1;
	foreach ($AddressTypeOptions as $Opt ) {
	//foreach (array_diff($AddressTypeOptions,Array("-")) as $Opt["Text"] ) {
		if ((($_POST["Opt-".$i])=="on") or $SetAll) {
			$checked = "checked";
			$EnabledOption[$Opt["Text"]] = 1;
			$SearchOption[] = $Opt["Text"];
		} else {
			$checked = "";
			$EnabledOption[$Opt["Text"]] = 0;
		}
		echo '<input type="checkbox" name="'. "Opt-".$i. '" ' . $checked . '>'. $Opt["Text"]. chr(10);
		$i++;
	}
	//pre_R($EnabledOption);
	//pre_R($SearchOption);
	echo "</td>";
	echo "<td rowspan=3>";
		foreach ($RegList as $Reg ) {
			//echo $Reg["Reg"] .", ";
			//echo $RegionSettings[$Reg["Reg"]]["JGList"]. "<br>";
			if ($Reg["Aktiv"] && ("NO" != $Reg["Reg"])) {
				if ((($_POST[$Reg["Reg"] . $i])=="on") or
						(($Reg["Reg"]=="ALL")
						and $NoRegionSelected))
				{
					foreach (explode(",", $RegionSettings[$Reg["Reg"]]["JGList"]) as $RegSel) {
						$RegionSelected[]= $RegSel;
					}
				}
			}	
			if ((($_POST[$Reg["Reg"] . $i])=="on") and ($Reg["Reg"]=="ALL")) {
				$RegionSelected[]= "ALL";
			}
		}
		echo CreateSiteLink ("PdfAdressenBauern", "Search=" .$FeldNamenWahl ."&TypFilter=" . implode(",", $SearchOption) . "&RegFilter=" . implode(",", $RegionSelected) . "&Sortieren=" . $ColSort . $MissionSelectionParameter );
		if (GetAccess("InternPilots")) {
			echo "<span id='exceldwnld'><a href='". $excel_csv_path ."' ><image src='" . $IconLoc . "/excel.png' height='" . $ButtonHeight. "'  title='Excel Adressliste herunterladen'></a></span> ";
			//$csvDocLocation.$RKRK_LWB_ContactFile = "Rehkitzrettung Konaueramt - Landwirtschaftsbetriebe.csv";
			//$csvDocLocation.$RKRK_Users = "Rehitzrettung - Users.csv";
			//$csvDocLocation.$RKRKFelderfileCSV = 'RKRKFelder.csv';
			//$csvDocLocation.$RKRKWeatherCSV = '_RKRKPlan.csv';
			//$csvDocLocation.$RKRKFlightsCSV= "_RKRKFluege.csv";
		}

/*
		//pre_R($RegionSelected);
		//PDF Dokument
		echo " <a href='rkrk_pdf_adrlwbtr.php?Search=" . $FeldNamenWahl ."&TypFilter=" . implode(",", $SearchOption) . "&RegFilter=" . implode(",", $RegionSelected) . "&Sortieren=" . $ColSort. "' target='_pdfdoc'><img src='" . $IconLoc . "/pdf_button.png' height='" . $ButtonHeight ."' title='Adress-PDF kreieren'></a>    ".chr(10);
*/
	//}
	echo CreateSiteLink ("Home", "");
	echo CreateSiteLink ("GoBack", "");
	//Enable Delete
	if (GetAccess("DeleteData")) {
		if ($EnableDelete) {
			//echo "ED - ON";
			echo "<input type='image' value='EnableDelete' src='" . $IconLoc . "/delete_on.png' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?"  . "ActPage=" . $ActPage . "&" . "Sortieren="  . $ColSort . "&" . $MissionSelectionParameter . "&EnableDelete=off' height='" . $ButtonHeight . "'  title='Delete Moduls ausschalten'  style='background-color:transparent; border-color:transparent;vertical-align: middle;'>" .chr(10);
		} else {
			//echo "ED - OFF";
			echo "<input type='image' value='EnableDelete' src='" . $IconLoc . "/delete_off.png' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?"  . "ActPage=" . $ActPage . "&" . "Sortieren="  . $ColSort . "&". $MissionSelectionParameter . "&EnableDelete=on' height='" . $ButtonHeight . "'title='Delete Moduls einschalten'  style='background-color:transparent; border-color:transparent;vertical-align: middle;'>" .chr(10);
		}
	}
	//Not needed on this page
	//echo  MissionLibraryButtons("&ActPage=" . $ActPage . "&" . "Sortieren="  . $ColSort . "&" . $EnableDeleteSet . "&",true);


	echo "</td>";

	//removes what does not belong to tick list
	$EnabledRegion = Array();
	$RegList["ALL"]["Aktiv"]=true;
	$NoRegionSelected = true;
	$RegionSelected = Array();
	echo "<tr><td>";
		echo "Region: ";
		echo "</td><td>";
		foreach ($RegList as $Reg ) {
			if ($Reg["Aktiv"] && ("NO" != $Reg["Reg"])) {
				if ((($_POST[$Reg["Reg"] . $i])=="on") or
						(($Reg["Reg"]=="ALL")
						and $NoRegionSelected))
				{
					$checked = "checked";
					$EnabledRegion[$Reg["Reg"]] = 1;
					$NoSelection = false;
					foreach (explode(",", $RegionSettings[$Reg["Reg"]]["JGList"]) as $RegSel) {
						$RegionSelected[]= $RegSel;
					}
					$NoRegionSelected= false;
				} else {
					$checked = "";
					$EnabledRegion[$Reg["Reg"]] = 0;
				}
				echo '<input type="checkbox" name="'. $Reg["Reg"] . $i . '" ' . $checked . '>'. $Reg["Reg"]. chr(10);
			}	
		}
		echo '<br>'. chr(10);
		$i++;
	echo "<tr><td>";
		echo "Felder: ";
		echo "</td><td>";
		foreach ($TableSetup as $Feld ) {
			if ($_SERVER["REQUEST_METHOD"] == "POST") {
			//if (isset($_POST[$Feld["Field"] . "_en"])) {
				//echo $Feld["Field"] . " ". ($_POST[$Feld["Field"] . "_en"]) . "/ ";
				//echo "post  ";
				if (($_POST[$Feld["Field"] . "_en"])=="on") {
					$checked = "checked";
					$TableSetup[$Feld["Field"]]["Show"] = true;
				} else {
					$checked = "";
					$TableSetup[$Feld["Field"]]["Show"] = false;
				}
				echo '<input type="checkbox" name="'. $Feld["Field"] . "_en" . '" ' . $checked . '>'. $Feld["Text"]. chr(10);
			}	else {
				//echo "default settings  ";
				//echo $Feld["Field"] . " ". $Feld["Show"] . "/ ";
				if ($Feld["Show"]==true) {
					$checked = "checked";
				} else {
					$checked = "";
				}
				echo '<input type="checkbox" name="'. $Feld["Field"] . "_en" . '" ' . $checked . '>'. $Feld["Text"]. chr(10);
			}
		}
		echo '<br>'. chr(10);
		$i++;

	echo "</tr><tr>";
	echo "<td>";
	echo "Suche:";
	echo "</td>";
	echo "<td>";
	echo InputField("FeldNamenWahl", $FeldNamenWahl, 20);
	echo "</td>";
	echo "</tr>";
	echo "</table>";
	if (GetAccess("DebugDataBase")) {
		echo '<input type="submit" name="Einlesen" value="Einlesen" formaction="' .htmlspecialchars($_SERVER["PHP_SELF"]). '?'. '&DBW=' . $_GET["DBW"]. '"> '. chr(10);
	} else {
		echo '<input type="submit" name="Einlesen" value="Einlesen" formaction="' .htmlspecialchars($_SERVER["PHP_SELF"]). '?' . '"> '. chr(10);
	}
	echo "</br>";
	echo "</br>";
	//echo '		<button type="submit" >Adressen speichern</button>'. chr(10);
	//echo "<input type='submit' value='Speichern' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?" . $MissionSelectionParameter . "&" . $EnableDeleteSet . "'>" .chr(10);
	//echo "ActPage: " . $ActPage;

	//pre_r($RegionSelected);
	//form to change setting
	$outputdata = Array();
	if ($fileloaded=true) {
		// ===========================================
		// Spezielle Filter
		// ===========================================
		//echo "FeldNamenWahl: " . $FeldNamenWahl . "<br>";
		$i=1;
		//pre_r($RegionSelected);
		foreach ($data as $Key => $dataline) {
			//cho "compare array to array: " . $dataline["Jagdgebiet"] . " - " . implode(",",$RegionSelected) ." - ". (count($RegionSelected)!=count(array_diff($RegionSelected, explode(",",$dataline["Jagdgebiet"])))) . "<br>";
			//pre_R(array_diff($RegionSelected, explode(",",$dataline["Jagdgebiet"])));
			//echo $dataline["Betrieb"] . "-" . like($dataline["Betrieb"], $FeldNamenWahl). "<br>";
				
			if ( 
					($FeldNamenWahl == "" 
					or like($dataline["Name"], $FeldNamenWahl)
					or like($dataline["Betrieb"], $FeldNamenWahl)
					or like($dataline["Adresse"], $FeldNamenWahl)
					or like($dataline["Felder"], $FeldNamenWahl)
					or like($dataline["Bemerkung"], $FeldNamenWahl))
					and in_array($dataline["Typ"], $SearchOption)  
					and (
					in_array($dataline["Jagdgebiet"], $RegionSelected) or
					(count($RegionSelected) != count(array_diff($RegionSelected, explode(",",$dataline["Jagdgebiet"]))))
					//!array_diff($RegionSelected, explode(",",$dataline["Jagdgebiet"]))
					)
				) 
			{
				$data[$Key]["ListNr"]= $i;
				$outputdata[$Key] = $dataline;
				$i++;
			} else {
				$data[$Key]["ListNr"]= "";
			}
		}
		$MaxSelectedEntries = $i;
	}

	//save File or Exceloutput
	SaveAdressDataToCSV($outputdata, $TableSetup, $excel_csv_path, "UTF-16LE", true, false, $excel_csv_file, $csv_desc);

	//pre_r($data);
	//echo "MaxEntries: " . $MaxSelectedEntries . "<br>";
	//echo "AnzLinesPPage: " . $AnzLinesPPage . "<br>";
	$i=1;
	$MaxPages = ceil($MaxSelectedEntries/$AnzLinesPPage);
	$showfrom = ($ActPage-1)*$AnzLinesPPage;
	$showto = $showfrom + $AnzLinesPPage;
	$FontSizeInPoints = 11;
	if ($FontSize== "") {
		$FontSize= "100%";
	}

	PageControl($ActPage, $MaxPages, $AnzLinesPPage, $FontSize, false, "Speichern", false, "&" . "Sortieren="  . $ColSort);

	echo "<div class='rkrk-table-wrap'>" . chr(10);
	echo "<table class='rkrk-data-table'>" .chr(10);
	echo "<tr >" .chr(10);
	if ($EnableDelete) {
		echo "<th></th>" .chr(10);
	}
	echo "<th>#</th>" .chr(10);
	foreach ($TableSetup AS $TableEntry) {
		if ($TableEntry["Show"]== true) {
			echo "<th>" .chr(10);
			if ($TableEntry["Sort"]!="") {
				echo "<input type='image' value='Sortieren' src='" . $IconLoc . "/sortieren.png' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?"  . "ActPage=" . $ActPage . "&" . "Sortieren="  . $TableEntry["Sort"] . "&" . $MissionSelectionParameter . "&EnableDelete=on' height='" . $ButtonHeight*0.7 . "'  title='Spalte sortieren'  style='background-color:transparent; bOrder-color:transparent;'>" .chr(10);
			}
			echo $TableEntry["Text"] . " </th>" .chr(10);

		}
	}
	echo "</tr >" .chr(10);
	
	foreach ($data as $key => $dataline) {
		//pre_R($dataline);
		//echo "ID. " . $dataline["ID"] . " .<br>";
		if ($dataline["ListNr"]!="" && 
			$dataline["ListNr"]>=$showfrom && 
			$dataline["ListNr"]<$showto){
		// Existing Data
			echo '<tr>' .chr(10);
			if ($EnableDelete) {
				echo '<td>' .chr(10);
				// delete
				if (GetAccess("DeleteData")) {
					echo "<input type='image' value='1st page' src='" . $IconLoc . "/delete.png' formaction='" . htmlspecialchars($_SERVER["PHP_SELF"]) . "?ActPage=" . $ActPage . "&" .  "Sortieren="  . $ColSort . "&" . "ID=" . $dataline['ID'] . "&delete=Y&EnableDelete=" . $_GET["EnableDelete"]. "&DBW=" . $_GET["DBW"]. "' height='" . $ButtonHeightSmall . "' title='Linie " . $i . " löschen'>" .chr(10);
				} else {
					echo "<input type='image' value='1st page' src='" . $IconLoc . "/delete.png' formaction='" . htmlspecialchars($_SERVER["PHP_SELF"]) . "?ActPage=" . $ActPage . "&" . "Sortieren="  . $ColSort . "&". " delete=Y&EnableDelete=" . $_GET["EnableDelete"]. "' height='" . $ButtonHeightSmall . "'  title='Linie " . $i . " löschen'>" .chr(10);
				}
			echo '</td>' .chr(10);
			}
			

			echo '<td>' .chr(10);
			echo $i;
			echo '</td>' .chr(10);
			foreach ($TableSetup AS $TableEntry) {
				if ($TableEntry["Show"]== true) {
					// show entries
					if ($TableEntry["Type"]=="input") {
						echo '<td>' .chr(10);
						echo InputField($TableEntry["Field"]."-" . $i, $dataline[$TableEntry["Field"]], $TableEntry["Length"] , $TableEntry["Align"]);
						echo '</td>' .chr(10);
					}
					if ($TableEntry["Type"]=="Text") {
						echo '<td>' .chr(10);
						if (($TableEntry["Field"]=="Felder") and 
						   ($dataline[$TableEntry["Field"]]!= "")){
							//https://tchr-frog.synology.me:5682/rkropen/test/rkrk_map.php?FFilter=136-001&Reg=ALL&Strt=on&Cntr=off&Area=on&Pfad=on&GDE=off&JGG=off&Poi=on&Label=off&EX=NO&KA=S&MAP=&Zoom=0.8
							$FieldList = str_replace(", ", " ", $dataline[$TableEntry["Field"]]);
							echo'<a href="' .$GeoMapURL1 . $FieldList . $GeoMapURL2 . '" target="_geomap"><img src="' . $IconLoc . '/geomap-q.png" height="' . $ButtonHeightSmall .'" title="Übersicht Felder des Landwirtschaftsbetriebs auf Geomap"> </a>' .chr(10);
							echo'<a href="' .htmlspecialchars($_SERVER["PHP_SELF"]). '?' . 'ActPage=' . $ActPage . '&JSONupdate=' . $dataline['ID'] . "&". $MissionSelectionParameter . '"><img src="' . $IconLoc . '/update_json.png" height="' . $ButtonHeightSmall .'" title="Adressdaten in JSON files schreiben"> </a>' .chr(10);
						}
						echo $dataline[$TableEntry["Field"]];
						echo InputField($TableEntry["Field"]."-" . $i, $dataline[$TableEntry["Field"]], $TableEntry["Length"] , $TableEntry["Align"], "hidden");
						echo '</td>' .chr(10);
					}
					if ($TableEntry["Type"]=="InputArea") {
						echo '<td>' .chr(10);
						echo InputTextarea($TableEntry["Field"]."-" . $i, $dataline[$TableEntry["Field"]], $TableEntry["Length"], 1, 30);
						echo '</td>' .chr(10);
					}
					if ($TableEntry["Type"]=="select") {
						echo '<td>' .chr(10);
						echo SelectField($TableEntry["Field"]."-". $i, strval($dataline[$TableEntry["Field"]]) ,$TableEntry["Options"] );
						echo "</td>" .chr(10);
					}
				} else {
					//hidden
					echo '<input type="hidden" name="'.$TableEntry['Field'] . '-' . $i . '" id="'. $TableEntry['Field'] . '-' . $i . '" value="'. $dataline[$TableEntry['Field']] . '" />'. chr(10);
				}
			}
			
			
		}
		$i++;
	}
	//new data
	echo "<tr><td colspan='12'><i>Neue Einträge:</i><td></tr>". chr(10);
	$MaxEntry = $i+5;
	while ($i <= $MaxEntry)  {
		if (true) {
		// Configsform empty
		// Existing Data
			echo '<tr>' .chr(10);
			if ($EnableDelete) {
				echo "<td></td>" .chr(10);
			}
			echo '<td>' .chr(10);
			echo $i;
			echo '</td>' .chr(10);

			foreach ($TableSetup AS $TableEntry) {
				if ($TableEntry["Show"]== true) {
					// show entries
					if ($TableEntry["Type"]=="input") {
						echo '<td>' .chr(10);
						echo InputField($TableEntry["Field"]."-" . $i, "", $TableEntry["Length"] , $TableEntry["Align"]);
						echo '</td>' .chr(10);
					}
					if ($TableEntry["Type"]=="text") {
						echo '<td>' .chr(10);
						echo $dataline[$TableEntry["Field"]];
						echo InputField($TableEntry["Field"]."-" . $i, "", $TableEntry["Length"] , $TableEntry["Align"], "hidden");
						echo '</td>' .chr(10);
					}
					if ($TableEntry["Type"]=="InputArea") {
						echo '<td>' .chr(10);
						echo InputTextarea($TableEntry["Field"]."-" . $i, "", $TableEntry["Length"], 1, 30);
						echo '</td>' .chr(10);
					}
					if ($TableEntry["Type"]=="select") {
						if ($TableEntry["Field"]=="Typ") {
							echo '<td>' .chr(10);
							echo SelectField($TableEntry["Field"]."-". $i, "" , $TableEntry["Options"] );
							echo "</td>" .chr(10);
						}
					}
				} else {
					//hidden
					echo '<input type="hidden" name="'.$TableEntry['Field'] . '-' . $i . '" id="'. $TableEntry['Field'] . '-' . $i . '" value="" />'. chr(10);
				}
			}
		}
		$i++;
	}
	echo "</table>" .chr(10);
	echo "</div>" . chr(10);
	echo '<input type="hidden" name="NumEntries" id="NumEntries" value="'. $i. '" />'. chr(10);

//	echo '		<button type="submit" >Adressen speichern</button>'. chr(10);
//	echo "<input type='submit' value='Speichern' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?" . $MissionSelectionParameter. "&" . $EnableDeleteSet . "'>" .chr(10);
	PageControl($ActPage, $MaxPages, $AnzLinesPPage, $FontSize, true, "Speichern", false, "&" . "Sortieren="  . $ColSort);
	
	echo '</form>'. chr(10);
	$SystemInfo = "";
	if ($ImportInfo != "") {
		$SystemInfo .= $ImportInfo . "<br><br>";
	}
	$FehlendeBetriebe = GetNotListedBetriebs($Feldkatalog,$data);
	$FehlendeAdressen = GetNotListedAdresses($Feldkatalog,$data);
	if (count($FehlendeBetriebe)>0) {
		$SystemInfo .= "<b>Fehlende Betriebe: </b><br>";
		$SystemInfo .= "<ul>";
		foreach ($FehlendeBetriebe as $key => $BetriebFehlt) {
			//pre_r($BetriebFehlt["Feld"]);
			$buttonHtml = "";
			$betriebCode = NormalizeBetriebKey((string)$BetriebFehlt["Betrieb"]);
			if (preg_match('/^(ZG|ZH)/i', preg_replace('/\s+/', '', $betriebCode))) {
				$query = array(
					"ImportBetrieb" => $betriebCode,
					"ActPage" => $ActPage,
					"Sortieren" => $ColSort,
					"EnableDelete" => ($EnableDelete ? "on" : "off"),
					"MDB" => $RKRKMissionSelected
				);
				if (isset($_GET["DBW"]) && $_GET["DBW"] != "") {
					$query["DBW"] = $_GET["DBW"];
				}
				$importUrl = htmlspecialchars($_SERVER["PHP_SELF"]) . "?" . http_build_query($query);
				$buttonHtml = " <a class='rkrk-import-btn' href='" . $importUrl . "'>Credentials importieren</a>";
			}
			$SystemInfo .= "<li>" . $BetriebFehlt["Betrieb"] . ": " . $BetriebFehlt["Adresse"]. "/ Felder: " . implode(", " ,$BetriebFehlt["Feld"]) . $buttonHtml . "</li>"; 
		}
		$SystemInfo .= "</ul>";
	}
	if (count($FehlendeAdressen)>0) {
		$SystemInfo .= "<br><br><b>Fehlende Adressen: </b><br>";
		$SystemInfo .= "<ul>";
		foreach ($FehlendeAdressen as $key => $AdresseFehlt) {
			//pre_r($AdresseFehlt);
			$SystemInfo .= "<li>" . $AdresseFehlt["Betrieb"] ."(" . $key . ")" .": " . $AdresseFehlt["Name"]. ", ". $AdresseFehlt["Adresse"]. ", ". $AdresseFehlt["KontaktNr"].", ". $AdresseFehlt["Mail"]."/ Felder: " . implode(", " ,$AdresseFehlt["Feld"]) . "</li>"; 
		}
		$SystemInfo .= "</ul>";
	}
	
	if (($SystemInfo != "")) {
		echo "<div class='rkrk-system-info' style='background-color: #FEF9E7;'>". chr(10);
		echo "System Meldungen: <br>". chr(10);
		echo $SystemInfo;
		echo "</div>". chr(10);
	}
	echo "</div>" . chr(10);
/*
	if (($DebugInfo != "") && ($DebugWork)) {
		echo "<div style='background-color: #FDEDEC;'>". chr(10);
		echo "Debug Meldung: <br>". chr(10);
		echo $DebugInfo;
		echo "</div>". chr(10);
	}
	echo "<br>". chr(10);
	pre_R($data);
*/

?>


</body>
</HTML>
