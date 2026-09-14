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
echo "<style>
.rkrk-card-table {
  width: 100%;
  background: #fff;
}
.rkrk-card-table td,
.rkrk-card-table th {
  padding: 0.3rem 0.4rem;
  line-height: 1.15;
}
.rkrk-card-table tbody tr:nth-child(odd) {
  background: #f6f7f9;
}
.rkrk-card-table tbody tr:nth-child(even) {
  background: #eceff3;
}
@media (min-width: 1301px) {
  .rkrk-desktop-scroll {
    width: auto;
    max-width: none;
    margin-left: 0;
    margin-right: 0;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    padding-bottom: 4px;
  }
  .rkrk-desktop-scroll > table {
    min-width: 100%;
  }
}
@media (max-width: 1300px) {
  .rkrk-card-table tr.rkrk-card-header-row {
    display: none !important;
  }
  .rkrk-card-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 10px;
  }
  .rkrk-card-table tr.rkrk-card-row {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px 10px;
    margin: 0;
    padding: 8px;
    border: 1px solid #d9d9d9;
    border-radius: 10px;
    background: #f3f5f8;
  }
  .rkrk-card-table td {
    display: block;
    box-sizing: border-box;
    border: none;
    padding: 2px;
  }
  .rkrk-card-table td::before {
    content: attr(data-label);
    display: block;
    margin: 0 0 3px 0;
    font-weight: 600;
    color: #445;
    font-size: 12px;
  }
  .rkrk-card-table td.rkrk-mobile-full,
  .rkrk-card-table td[colspan] {
    grid-column: 1 / -1;
  }
  .rkrk-card-table td.rkrk-mobile-iconcell::before,
  .rkrk-card-table td.rkrk-mobile-full::before {
    content: '';
    display: none;
  }
  .rkrk-card-table td input,
  .rkrk-card-table td select,
  .rkrk-card-table td textarea,
  .rkrk-card-table td button {
    width: 100% !important;
    max-width: none !important;
    box-sizing: border-box;
    min-height: 36px;
  }
}
@media (max-width: 700px) {
  .rkrk-card-table tr.rkrk-card-row {
    grid-template-columns: 1fr;
    gap: 7px;
    padding: 7px;
  }
  .rkrk-card-table td::before {
    font-size: 13px;
  }
  .rkrk-card-table td input,
  .rkrk-card-table td select,
  .rkrk-card-table td textarea,
  .rkrk-card-table td button {
    min-height: 40px;
    font-size: 15px;
  }
}
</style>";
echo "<script>
function rkrkApplyResponsiveCardTables() {
  var tables = document.querySelectorAll('table');
  for (var t = 0; t < tables.length; t++) {
    var table = tables[t];
    if (table.dataset.rkrkResponsiveInit === '1') continue;
    var rows = table.querySelectorAll('tr');
    var headerRow = null;
    for (var i = 0; i < rows.length; i++) {
      if (rows[i].querySelectorAll('th').length > 0) {
        headerRow = rows[i];
        break;
      }
    }
    if (!headerRow) continue;
    var ths = headerRow.querySelectorAll('th');
    if (ths.length < 3) continue;
    var labels = [];
    for (var h = 0; h < ths.length; h++) {
      labels.push((ths[h].textContent || '').trim());
    }
    table.classList.add('rkrk-card-table');
    headerRow.classList.add('rkrk-card-header-row');
    for (var r = 0; r < rows.length; r++) {
      var row = rows[r];
      if (row === headerRow || row.querySelectorAll('th').length > 0) continue;
      var tds = row.querySelectorAll('td');
      if (!tds.length) continue;
      row.classList.add('rkrk-card-row');
      for (var c = 0; c < tds.length; c++) {
        var td = tds[c];
        if (td.hasAttribute('colspan')) {
          td.classList.add('rkrk-mobile-full');
          td.setAttribute('data-label', '');
          continue;
        }
        var label = (labels[c] || '').trim();
        td.setAttribute('data-label', label);
        if (label === '') td.classList.add('rkrk-mobile-iconcell');
      }
    }
    table.dataset.rkrkResponsiveInit = '1';
  }
}
function rkrkApplyDesktopTableScroll() {
  function stretchWrapperToViewport(wrap) {
    if (!wrap) return;
    var rect = wrap.getBoundingClientRect();
    var left = Math.max(0, Math.round(rect.left || 0));
    var vw = window.innerWidth || document.documentElement.clientWidth || 0;
    var width = Math.max(120, vw - left - 6);
    wrap.style.width = width + 'px';
    wrap.style.maxWidth = width + 'px';
    wrap.style.marginRight = '0';
  }
  function availableWidthFor(el) {
    var parent = el && el.parentNode ? el.parentNode.clientWidth : 0;
    var rect = el && el.getBoundingClientRect ? el.getBoundingClientRect() : null;
    var left = rect ? Math.max(0, Math.round(rect.left || 0)) : 0;
    var vw = window.innerWidth || document.documentElement.clientWidth || 0;
    var viewportRightSpace = Math.max(0, vw - left - 6);
    return Math.max(parent, viewportRightSpace);
  }
  var isNarrow = window.matchMedia('(max-width: 1300px)').matches;
  var wrappers = document.querySelectorAll('.rkrk-desktop-scroll');
  for (var w = 0; w < wrappers.length; w++) {
    var wrap = wrappers[w];
    stretchWrapperToViewport(wrap);
    var wrappedTable = wrap.querySelector(':scope > table');
    if (!wrappedTable) continue;
    if (isNarrow) {
      wrap.parentNode.insertBefore(wrappedTable, wrap);
      wrap.remove();
      continue;
    }
    var wrapAvailable = wrap.clientWidth;
    if (wrappedTable.scrollWidth <= wrapAvailable + 1) {
      wrap.parentNode.insertBefore(wrappedTable, wrap);
      wrap.remove();
    }
  }
  if (isNarrow) return;

  var tables = document.querySelectorAll('table');
  for (var i = 0; i < tables.length; i++) {
    var table = tables[i];
    if (!table || table.closest('.rkrk-desktop-scroll')) continue;
    var rowCount = table.querySelectorAll('tr').length;
    if (rowCount < 2) continue;
    if (table.scrollWidth <= availableWidthFor(table) + 1) continue;
    var wrap = document.createElement('div');
    wrap.className = 'rkrk-desktop-scroll';
    table.parentNode.insertBefore(wrap, table);
    wrap.appendChild(table);
    stretchWrapperToViewport(wrap);
  }
}
window.addEventListener('DOMContentLoaded', function() {
  rkrkApplyResponsiveCardTables();
  rkrkApplyDesktopTableScroll();
});
window.addEventListener('resize', rkrkApplyDesktopTableScroll);
</script>";

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
		if (function_exists("rkrk_adr_safe_utf8")) {
			$text = rkrk_adr_safe_utf8($text);
		}
		$text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
		$text = str_replace("\xC2\xA0", " ", $text);
		if (function_exists("rkrk_adr_preg_replace_u")) {
			$text = rkrk_adr_preg_replace_u('/[\x{00A0}\x{2007}\x{202F}]/u', ' ', $text);
			$text = rkrk_adr_preg_replace_u('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $text);
			$text = rkrk_adr_preg_replace_u('/\s+/u', ' ', trim($text));
		} else {
			$text = preg_replace('/\s+/', ' ', trim($text));
		}
		return $text;
	}

	function NormalizeUmlautText($text) {
		if (function_exists("rkrk_adr_normalize_text")) {
			return rkrk_adr_normalize_text($text);
		}
		$text = (string)$text;
		$text = html_entity_decode($text, ENT_QUOTES, "UTF-8");
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

	function FetchFarmerExportData($betriebCode, &$errorMessage, &$usedCode) {
		$errorMessage = "";
		$usedCode = "";
		$candidates = BuildBetriebCodeCandidates($betriebCode);
		foreach ($candidates as $candidateCode) {
			$urls = array(
				"https://www.rkrzug.ch/rkr_export.php?exporttype=fbfn&exportformat=json&param1=" . rawurlencode($candidateCode),
				"https://rkrzug.ch/rkr_export.php?exporttype=fbfn&exportformat=json&param1=" . rawurlencode($candidateCode)
			);
			foreach ($urls as $url) {
				$raw = false;
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

				if (($raw === false || trim((string)$raw) === "") && function_exists('curl_init')) {
					$ch = curl_init($url);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($ch, CURLOPT_TIMEOUT, 12);
					curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
					$raw = curl_exec($ch);
					curl_close($ch);
				}

				if (($raw === false || trim((string)$raw) === "") && function_exists('shell_exec')) {
					$cmd = 'curl -L --max-time 12 --connect-timeout 8 -sS ' . escapeshellarg($url) . ' 2>&1';
					$cliOut = @shell_exec($cmd);
					if (is_string($cliOut) && trim($cliOut) !== "") {
						$raw = $cliOut;
					}
				}

				if ($raw === false || trim((string)$raw) === "") {
					continue;
				}

				$response = json_decode($raw, true);
				if (!is_array($response)) {
					continue;
				}
				if (!isset($response["status"]) || strtolower((string)$response["status"]) !== "success") {
					continue;
				}
				if (!isset($response["payload"]) || !is_array($response["payload"])) {
					continue;
				}

				$usedCode = $candidateCode;
				return $response["payload"];
			}
		}

		$errorMessage = "Export-Antwort ist nicht erfolgreich für " . NormalizeBetriebKey($betriebCode);
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

	function ApplyImportRowToAddressRow($existingRow, $importRow, $effectiveCode, $appendSyncRemark = false) {
		$updatedRow = $existingRow;
		foreach ($importRow as $field => $value) {
			if (array_key_exists($field, $updatedRow)) {
				$updatedRow[$field] = $value;
			}
		}
		if (array_key_exists("Typ", $updatedRow)) {
			$updatedRow["Typ"] = "Bauer";
		}
		if (array_key_exists("Jagdgebiet", $updatedRow) && trim((string)$updatedRow["Jagdgebiet"]) === "") {
			$updatedRow["Jagdgebiet"] = strtoupper(substr($effectiveCode, 0, 2));
		}
		if ($appendSyncRemark && array_key_exists("Bemerkung", $updatedRow)) {
			$updatedRow["Bemerkung"] = trim((string)$updatedRow["Bemerkung"] . " | Sync RKR Zug: " . date("Y-m-d H:i"));
		}
		return $updatedRow;
	}

	function SaveAddressDataAndVerifyById(&$data, $rowId, $TableSetup, $filepath, $csvFile, $csvDesc, &$errorMessage) {
		$errorMessage = "";
		SaveAdressDataToCSV($data, $TableSetup, $filepath, "UTF-8", true, false, $csvFile, $csvDesc, true);
		$verifyData = csv_to_array($filepath, $csvDesc);
		if (!isset($verifyData[$rowId])) {
			$errorMessage = "Update gespeichert, aber Verifikation fehlgeschlagen.";
			return false;
		}
		$data = $verifyData;
		return true;
	}

	function FindAddressRowIdByBetrieb($betriebCode, $data) {
		$needleKeys = array();
		if (function_exists("rkrk_adr_betrieb_lookup_keys")) {
			foreach (rkrk_adr_betrieb_lookup_keys($betriebCode) as $key) {
				$needleKeys[$key] = true;
			}
		} else {
			$needle = NormalizeBetriebKey($betriebCode);
			if ($needle !== "" && function_exists("rkrk_adr_compare_betrieb_key")) {
				$needleKeys[rkrk_adr_compare_betrieb_key($needle)] = true;
			}
		}
		if (count($needleKeys) === 0) {
			return "";
		}
		foreach ($data as $rowId => $row) {
			foreach (array("Betrieb", "Initialen") as $field) {
				$rowVal = NormalizeBetriebKey((string)($row[$field] ?? ""));
				if ($rowVal === "" || $rowVal === "-") {
					continue;
				}
				if (function_exists("rkrk_adr_compare_betrieb_key")
					&& isset($needleKeys[rkrk_adr_compare_betrieb_key($rowVal)])) {
					return $rowId;
				}
			}
		}
		return "";
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
		$payload = FetchFarmerExportData($betriebCode, $errorMessage, $usedCode);
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

		$newId = GetNextID($data);
		$emptyRow = array();
		foreach ($TableSetup as $entry) {
			$field = $entry["Field"];
			$emptyRow[$field] = "";
		}
		$emptyRow["ID"] = $newId;
		$updatedRow = ApplyImportRowToAddressRow($emptyRow, $importRow, $effectiveCode, false);

		$data[$newId] = $updatedRow;
		$saveError = "";
		if (!SaveAddressDataAndVerifyById($data, $newId, $TableSetup, $filepath, $csvFile, $csvDesc, $saveError)) {
			$message = "Import nicht gespeichert. " . $saveError;
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

	function NormalizeCompareText($value) {
		if (function_exists("rkrk_adr_compare_key")) {
			return rkrk_adr_compare_key($value);
		}
		$v = NormalizeBetriebKey((string)$value);
		return function_exists('mb_strtolower') ? mb_strtolower($v, 'UTF-8') : strtolower($v);
	}

	function NormalizeSwissPhone($value) {
		if (function_exists("rkrk_adr_normalize_phone")) {
			return rkrk_adr_normalize_phone($value);
		}
		$parts = preg_split('/[,;]+/', (string)$value);
		$out = array();
		foreach ($parts as $part) {
			$digits = preg_replace('/\D+/', '', $part);
			if ($digits === "") {
				continue;
			}
			if (substr($digits, 0, 2) === "41" && strlen($digits) >= 11) {
				$digits = "0" . substr($digits, 2);
			}
			$out[] = $digits;
		}
		$out = array_values(array_unique($out));
		sort($out);
		return implode('|', $out);
	}

	function GetFarmerCredentialDiffs($localRow, $importRow) {
		$diffs = array();
		$fields = array(
			"Initialen" => "Text",
			"Betrieb" => "Text",
			"Name" => "Text",
			"Kontaktnummer" => "Phone",
			"Mail" => "Text",
			"Adresse" => "Text"
		);
		foreach ($fields as $field => $type) {
			$local = isset($localRow[$field]) ? (string)$localRow[$field] : "";
			$remote = isset($importRow[$field]) ? (string)$importRow[$field] : "";
			if ($type === "Phone") {
				$isDifferent = (NormalizeSwissPhone($local) !== NormalizeSwissPhone($remote));
			} else {
				$isDifferent = (NormalizeCompareText($local) !== NormalizeCompareText($remote));
			}
			if ($isDifferent) {
				$diffs[$field] = array(
					"local" => $local,
					"remote" => $remote
				);
			}
		}
		return $diffs;
	}

	function ApplyJsonUpdateForAddressRow($rowId, &$data, &$Feldkatalog, &$syncInfo) {
		global $xmlMissionLocation, $RKRKFelderfileXML, $historyfilelocation;
		$syncInfo = "";
		if (!isset($data[$rowId])) {
			$syncInfo = "JSON-Update nicht möglich: Datensatz " . $rowId . " nicht gefunden.";
			return false;
		}
		if (!function_exists("rkrk_adr_sync_json_from_database_row")) {
			$syncInfo = "JSON-Update nicht möglich: Sync-Funktion fehlt.";
			return false;
		}
		$ok = rkrk_adr_sync_json_from_database_row($data[$rowId], $Feldkatalog, $syncInfo);
		if (!$ok) {
			return false;
		}

		$file = $xmlMissionLocation . $RKRKFelderfileXML;
		$newfile = $historyfilelocation . date("YmdH") . "00_" . $RKRKFelderfileXML;
		if (!copy($file, $newfile)) {
			$syncInfo .= " Feldkatalog-Backup fehlgeschlagen, Aktualisierung trotzdem durchgeführt.";
		}
		SaveFlugkatalog($Feldkatalog, true);
		SaveFlugkatalog2CSV($Feldkatalog);
		return true;
	}

	function ApplyDatabaseUpdateFromJsonRow($rowId, &$data, $Feldkatalog, $TableSetup, $filepath, $csvFile, $csvDesc, &$syncInfo) {
		$syncInfo = "";
		if (!function_exists("rkrk_adr_merge_json_owner_into_db_row")) {
			$syncInfo = "Datenbank-Update nicht möglich: Sync-Funktion fehlt.";
			return false;
		}
		$ok = rkrk_adr_merge_json_owner_into_db_row($rowId, $data, $Feldkatalog, $syncInfo);
		if (!$ok) {
			return false;
		}
		$saveError = "";
		if (!SaveAddressDataAndVerifyById($data, $rowId, $TableSetup, $filepath, $csvFile, $csvDesc, $saveError)) {
			$syncInfo = "Datenbank-Update fehlgeschlagen: " . $saveError;
			return false;
		}
		return true;
	}

	function ApplyAllJsonUpdatesFromDatabase($deviations, &$data, &$Feldkatalog, &$summary) {
		$summary = "";
		$okCount = 0;
		$failCount = 0;
		$details = array();
		if (!is_array($deviations) || count($deviations) === 0) {
			$summary = "Keine abweichenden Adressen zum Aktualisieren gefunden.";
			return false;
		}
		foreach ($deviations as $abweichung) {
			$rowId = trim((string)($abweichung["id"] ?? ""));
			if ($rowId === "" || !isset($data[$rowId])) {
				$failCount++;
				$details[] = ($rowId !== "" ? $rowId : "?") . ": Datensatz nicht gefunden.";
				continue;
			}
			$msg = "";
			if (ApplyJsonUpdateForAddressRow($rowId, $data, $Feldkatalog, $msg)) {
				$okCount++;
			} else {
				$failCount++;
				if ($msg !== "") {
					$details[] = $rowId . ": " . $msg;
				}
			}
		}
		$summary = $okCount . " erfolgreich, " . $failCount . " fehlgeschlagen.";
		if (count($details) > 0) {
			$summary .= "<br>" . implode("<br>", array_map("htmlspecialchars", $details));
		}
		return ($okCount > 0);
	}

	function ApplyAllDatabaseUpdatesFromJson($deviations, &$data, $Feldkatalog, $TableSetup, $filepath, $csvFile, $csvDesc, &$summary) {
		$summary = "";
		$okCount = 0;
		$failCount = 0;
		$details = array();
		if (!is_array($deviations) || count($deviations) === 0) {
			$summary = "Keine abweichenden Adressen zum Aktualisieren gefunden.";
			return false;
		}
		if (!function_exists("rkrk_adr_merge_json_owner_into_db_row")) {
			$summary = "Datenbank-Update nicht möglich: Sync-Funktion fehlt.";
			return false;
		}
		$updatedIds = array();
		foreach ($deviations as $abweichung) {
			$rowId = trim((string)($abweichung["id"] ?? ""));
			if ($rowId === "" || !isset($data[$rowId])) {
				$failCount++;
				$details[] = ($rowId !== "" ? $rowId : "?") . ": Datensatz nicht gefunden.";
				continue;
			}
			$msg = "";
			if (rkrk_adr_merge_json_owner_into_db_row($rowId, $data, $Feldkatalog, $msg)) {
				$okCount++;
				$updatedIds[$rowId] = $rowId;
			} else {
				$failCount++;
				if ($msg !== "") {
					$details[] = $rowId . ": " . $msg;
				}
			}
		}
		if ($okCount > 0) {
			$saveError = "";
			SaveAdressDataToCSV($data, $TableSetup, $filepath, "UTF-8", true, false, $csvFile, $csvDesc, true);
			$verifyData = csv_to_array($filepath, $csvDesc);
			foreach ($updatedIds as $rowId) {
				if (!isset($verifyData[$rowId])) {
					$saveError = "CSV gespeichert, aber Verifikation für " . $rowId . " fehlgeschlagen.";
					break;
				}
			}
			if ($saveError !== "") {
				$summary = "Massen-Update teilweise fehlgeschlagen: " . htmlspecialchars($saveError);
				return false;
			}
			$data = $verifyData;
		}
		$summary = $okCount . " erfolgreich, " . $failCount . " fehlgeschlagen.";
		if (count($details) > 0) {
			$summary .= "<br>" . implode("<br>", array_map("htmlspecialchars", $details));
		}
		return ($okCount > 0);
	}

	function UpdateFarmerAddressDataById($rowId, &$data, &$Feldkatalog, $TableSetup, $filepath, $csvFile, $csvDesc, &$message) {
		$message = "";
		if (!isset($data[$rowId])) {
			$message = "Datensatz " . $rowId . " nicht gefunden.";
			return false;
		}
		$betriebCode = NormalizeBetriebKey((string)($data[$rowId]["Betrieb"] ?? ""));
		if (!preg_match('/^(ZG|ZH)/i', preg_replace('/\s+/', '', $betriebCode))) {
			$message = "Betrieb " . $betriebCode . " hat keinen gültigen ZG/ZH Betriebscode.";
			return false;
		}

		$errorMessage = "";
		$usedCode = "";
		$payload = FetchFarmerExportData($betriebCode, $errorMessage, $usedCode);
		if ($payload === false) {
			$message = $errorMessage;
			return false;
		}

		$effectiveCode = ($usedCode !== "" ? $usedCode : $betriebCode);
		$importRow = BuildFarmerImportRow($payload, $effectiveCode);
		$updatedRow = ApplyImportRowToAddressRow($data[$rowId], $importRow, $effectiveCode, true);
		$data[$rowId] = $updatedRow;
		$saveError = "";
		if (!SaveAddressDataAndVerifyById($data, $rowId, $TableSetup, $filepath, $csvFile, $csvDesc, $saveError)) {
			$message = "Betrieb " . $betriebCode . ": " . $saveError;
			return false;
		}
		$jsonSyncInfo = "";
		$jsonSyncOk = ApplyJsonUpdateForAddressRow($rowId, $data, $Feldkatalog, $jsonSyncInfo);
		if ($jsonSyncOk) {
			$message = "Betrieb " . $betriebCode . " erfolgreich aktualisiert. " . $jsonSyncInfo;
			return true;
		}
		$message = "Betrieb " . $betriebCode . " aktualisiert, aber JSON-Update unvollständig. " . $jsonSyncInfo;
		return false;
	}

	function CheckFarmerCredentialDeviations($data, &$summaryMessage) {
		$summaryMessage = "";
		$result = array();
		$checked = 0;
		foreach ($data as $rowId => $row) {
			$betriebCode = NormalizeBetriebKey((string)($row["Betrieb"] ?? ""));
			if (!preg_match('/^(ZG|ZH)/i', preg_replace('/\s+/', '', $betriebCode))) {
				continue;
			}
			$checked++;
			$errorMessage = "";
			$usedCode = "";
			$payload = FetchFarmerExportData($betriebCode, $errorMessage, $usedCode);
			if ($payload === false) {
				$result[] = array(
					"type" => "error",
					"id" => $rowId,
					"betrieb" => $betriebCode,
					"name" => (string)($row["Name"] ?? ""),
					"message" => $errorMessage
				);
				continue;
			}
			$importRow = BuildFarmerImportRow($payload, ($usedCode !== "" ? $usedCode : $betriebCode));
			$diffs = GetFarmerCredentialDiffs($row, $importRow);
			if (!empty($diffs)) {
				$result[] = array(
					"type" => "diff",
					"id" => $rowId,
					"betrieb" => $betriebCode,
					"name" => (string)($row["Name"] ?? ""),
					"diffs" => $diffs
				);
			}
		}
		$summaryMessage = "Zugangsdaten-Prüfung abgeschlossen: " . $checked . " ZG/ZH Einträge geprüft, " . count($result) . " Abweichungen/Fehler gefunden.";
		return $result;
	}

	function ApplyJsonUpdateForAddressRecord($record, &$Feldkatalog, &$syncInfo) {
		global $xmlMissionLocation, $RKRKFelderfileXML, $historyfilelocation;
		$syncInfo = "";
		if (!isset($record["Betrieb"])) {
			$syncInfo = "JSON-Update nicht möglich: Betrieb fehlt.";
			return false;
		}
		if (!function_exists("rkrk_adr_sync_json_from_database_row")) {
			$syncInfo = "JSON-Update nicht möglich: Sync-Funktion fehlt.";
			return false;
		}
		$ok = rkrk_adr_sync_json_from_database_row($record, $Feldkatalog, $syncInfo);
		if (!$ok) {
			return false;
		}

		$file = $xmlMissionLocation . $RKRKFelderfileXML;
		$newfile = $historyfilelocation . date("YmdH") . "00_" . $RKRKFelderfileXML;
		if (!copy($file, $newfile)) {
			$syncInfo .= " Feldkatalog-Backup fehlgeschlagen, Aktualisierung trotzdem durchgeführt.";
		}
		SaveFlugkatalog($Feldkatalog, true);
		SaveFlugkatalog2CSV($Feldkatalog);
		return true;
	}

	function BuildMissingAddressRecordFromFeldkatalog($betriebCode, $Feldkatalog) {
		$needle = NormalizeBetriebKey($betriebCode);
		if ($needle === "") {
			return null;
		}
		$record = array(
			"Betrieb" => $needle,
			"Name" => "",
			"Adresse" => "",
			"Kontaktnummer" => "",
			"Mail" => "",
			"Felder" => array(),
		);
		if (!is_array($Feldkatalog)) {
			return null;
		}
		foreach ($Feldkatalog as $Reg) {
			if (!is_array($Reg)) {
				continue;
			}
			foreach ($Reg as $dataline) {
				$rowBetrieb = NormalizeBetriebKey((string)($dataline["Betrieb"] ?? ""));
				if ($rowBetrieb !== $needle) {
					continue;
				}
				$bauerRaw = isset($dataline["Bauer"]) ? $dataline["Bauer"] : "";
				if (trim((string)$bauerRaw) === "") {
					continue;
				}
				try {
					$bauer = rkrk_adr_parse_bauer_block($bauerRaw);
				} catch (Throwable $e) {
					continue;
				}
				if ($record["Name"] === "" && trim((string)$bauer["Name"]) !== "") {
					$record["Name"] = NormalizeUmlautText($bauer["Name"]);
					$record["Adresse"] = NormalizeUmlautText($bauer["Adresse"]);
					$record["Kontaktnummer"] = NormalizeUmlautText(implode(", ", $bauer["KontaktNr"]));
					$record["Mail"] = NormalizeUmlautText($bauer["Mail"]);
				}
				if (!empty($dataline["FeldID"])) {
					$record["Felder"][$dataline["FeldID"]] = $dataline["FeldID"];
				}
			}
		}
		if ($record["Name"] === "" || count($record["Felder"]) === 0) {
			return null;
		}
		$record["Felder"] = implode(", ", array_values($record["Felder"]));
		if (function_exists("rkrk_adr_jagdgebiet_from_felder")) {
			$record["Jagdgebiet"] = rkrk_adr_jagdgebiet_from_felder($record["Felder"], $record["Betrieb"]);
		}
		return $record;
	}

	function BuildMissingAddressRecord($missingEntry) {
		$record = array();
		$record["Betrieb"] = NormalizeUmlautText(isset($missingEntry["Betrieb"]) ? $missingEntry["Betrieb"] : "");
		$record["Name"] = NormalizeUmlautText(isset($missingEntry["Name"]) ? $missingEntry["Name"] : "");
		$record["Adresse"] = NormalizeUmlautText(isset($missingEntry["Adresse"]) ? $missingEntry["Adresse"] : "");
		$record["Kontaktnummer"] = NormalizeUmlautText(isset($missingEntry["KontaktNr"]) ? $missingEntry["KontaktNr"] : "");
		$record["Mail"] = NormalizeUmlautText(isset($missingEntry["Mail"]) ? $missingEntry["Mail"] : "");
		// Verwende dieselbe Feldliste-Logik wie beim bestehenden JSONupdate=ADRxxxx-Pfad:
		// bevorzugt das String-Feld "Felder" (Format: "JG-001, JG-002, ...").
		$felderString = "";
		if (isset($missingEntry["Felder"]) && trim((string)$missingEntry["Felder"]) !== "") {
			$felderString = trim((string)$missingEntry["Felder"]);
		} elseif (isset($missingEntry["Feld"]) && is_array($missingEntry["Feld"])) {
			$felderString = implode(", ", array_values($missingEntry["Feld"]));
		}
		$record["Felder"] = $felderString;
		if (function_exists("rkrk_adr_jagdgebiet_from_felder")) {
			$record["Jagdgebiet"] = rkrk_adr_jagdgebiet_from_felder($felderString, $record["Betrieb"]);
		} else {
			$record["Jagdgebiet"] = "";
		}
		return $record;
	}

	function FindMissingAddressKeyByBetrieb($betriebCode, $missingAddresses) {
		$needle = NormalizeBetriebKey($betriebCode);
		foreach ($missingAddresses as $key => $entry) {
			$rowBetrieb = NormalizeBetriebKey((string)($entry["Betrieb"] ?? ""));
			if ($rowBetrieb === $needle) {
				return $key;
			}
		}
		return "";
	}

	function SyncMissingAddressRecordToCsv($record, &$data, $TableSetup, $filepath, $csvFile, $csvDesc, &$message) {
		$message = "";
		$betrieb = NormalizeBetriebKey((string)($record["Betrieb"] ?? ""));
		$name = trim((string)($record["Name"] ?? ""));
		if ($betrieb === "" && $name === "") {
			$message = "Betrieb und Name fehlen.";
			return false;
		}

		$rowId = FindAddressRowIdByBetrieb($betrieb, $data);
		if ($rowId === "" && $name !== "" && function_exists("rkrk_adr_compare_key")) {
			$nameKey = rkrk_adr_compare_key($name);
			$phoneNorm = function_exists("rkrk_adr_normalize_phone")
				? rkrk_adr_normalize_phone((string)($record["Kontaktnummer"] ?? "")) : "";
			foreach ($data as $id => $row) {
				if (rkrk_adr_compare_key((string)($row["Name"] ?? "")) !== $nameKey) {
					continue;
				}
				$rowPhone = function_exists("rkrk_adr_normalize_phone")
					? rkrk_adr_normalize_phone((string)($row["Kontaktnummer"] ?? "")) : "";
				if ($phoneNorm === "" || $rowPhone === "" || $rowPhone === $phoneNorm) {
					$rowId = $id;
					break;
				}
			}
		}

		$felder = trim((string)($record["Felder"] ?? ""));
		$jagdgebiet = "";
		if (function_exists("rkrk_adr_jagdgebiet_from_felder")) {
			$jagdgebiet = rkrk_adr_jagdgebiet_from_felder($felder, $betrieb);
		}
		if (trim((string)($record["Jagdgebiet"] ?? "")) !== "") {
			$jagdgebiet = function_exists("rkrk_adr_merge_jagdgebiet")
				? rkrk_adr_merge_jagdgebiet($jagdgebiet, (string)$record["Jagdgebiet"])
				: (string)$record["Jagdgebiet"];
		}
		if ($rowId !== "") {
			if ($betrieb !== "") {
				$data[$rowId]["Betrieb"] = $betrieb;
				$data[$rowId]["Initialen"] = $betrieb;
			}
			if ($name !== "") {
				$data[$rowId]["Name"] = $name;
			}
			if (trim((string)($record["Kontaktnummer"] ?? "")) !== "") {
				$data[$rowId]["Kontaktnummer"] = (string)$record["Kontaktnummer"];
			}
			if (trim((string)($record["Mail"] ?? "")) !== "") {
				$data[$rowId]["Mail"] = (string)$record["Mail"];
			}
			if (trim((string)($record["Adresse"] ?? "")) !== "") {
				$data[$rowId]["Adresse"] = (string)$record["Adresse"];
			}
			if ($felder !== "") {
				$existingFelder = trim((string)($data[$rowId]["Felder"] ?? ""));
				if ($existingFelder === "") {
					$data[$rowId]["Felder"] = $felder;
				} else {
					$merged = array();
					foreach (preg_split('/\s*,\s*/', $existingFelder . ", " . $felder) as $feldId) {
						$feldId = trim($feldId);
						if ($feldId !== "") {
							$merged[$feldId] = $feldId;
						}
					}
					$data[$rowId]["Felder"] = implode(", ", array_values($merged));
				}
			}
			if (trim((string)($data[$rowId]["Typ"] ?? "")) === "") {
				$data[$rowId]["Typ"] = "Bauer";
			}
			if ($jagdgebiet !== "") {
				$data[$rowId]["Jagdgebiet"] = function_exists("rkrk_adr_merge_jagdgebiet")
					? rkrk_adr_merge_jagdgebiet($data[$rowId]["Jagdgebiet"] ?? "", $jagdgebiet)
					: $jagdgebiet;
			}
		} else {
			$rowId = GetNextID($data);
			foreach ($TableSetup as $entry) {
				$data[$rowId][$entry["Field"]] = "";
			}
			$data[$rowId]["ID"] = $rowId;
			$data[$rowId]["Initialen"] = $betrieb;
			$data[$rowId]["Betrieb"] = $betrieb;
			$data[$rowId]["Name"] = $name;
			$data[$rowId]["Kontaktnummer"] = (string)($record["Kontaktnummer"] ?? "");
			$data[$rowId]["Mail"] = (string)($record["Mail"] ?? "");
			$data[$rowId]["Adresse"] = (string)($record["Adresse"] ?? "");
			$data[$rowId]["Felder"] = $felder;
			$data[$rowId]["Typ"] = "Bauer";
			$data[$rowId]["Jagdgebiet"] = $jagdgebiet;
		}

		$saveError = "";
		if (!SaveAddressDataAndVerifyById($data, $rowId, $TableSetup, $filepath, $csvFile, $csvDesc, $saveError)) {
			$message = $saveError;
			return false;
		}
		$message = "Adresse in Adressdatenbank gespeichert (" . $rowId . ").";
		return true;
	}

	function ApplyMissingAddressSync($record, &$Feldkatalog, &$data, $TableSetup, $filepath, $csvFile, $csvDesc, &$message) {
		$message = "";
		$betrieb = NormalizeBetriebKey((string)($record["Betrieb"] ?? ""));

		$csvMsg = "";
		$csvOk = SyncMissingAddressRecordToCsv($record, $data, $TableSetup, $filepath, $csvFile, $csvDesc, $csvMsg);
		if (!$csvOk) {
			$message = "Adressdatenbank: " . $csvMsg;
			return false;
		}

		$syncInfo = "";
		$jsonOk = ApplyJsonUpdateForAddressRecord($record, $Feldkatalog, $syncInfo);
		if (!$jsonOk) {
			$message = $csvMsg . " JSON-Dateien konnten nicht aktualisiert werden: " . $syncInfo;
			return false;
		}

		$message = "Adressdatenbank und JSON aktualisiert für " . $betrieb . ". " . $csvMsg . " " . $syncInfo;
		return true;
	}

	function UpdateMissingAddressToJsonByKey($missingKey, $missingAddresses, &$Feldkatalog, &$data, $TableSetup, $filepath, $csvFile, $csvDesc, &$message) {
		$message = "";
		if (!isset($missingAddresses[$missingKey])) {
			$message = "Fehlende Adresse nicht gefunden.";
			return false;
		}
		$entry = $missingAddresses[$missingKey];
		$record = BuildMissingAddressRecord($entry);
		return ApplyMissingAddressSync($record, $Feldkatalog, $data, $TableSetup, $filepath, $csvFile, $csvDesc, $message);
	}

	function UpdateMissingAddressToJsonByBetrieb($betriebCode, $missingAddresses, &$Feldkatalog, &$data, $TableSetup, $filepath, $csvFile, $csvDesc, &$message) {
		$message = "";
		$betriebCode = NormalizeBetriebKey($betriebCode);
		$resolvedKey = FindMissingAddressKeyByBetrieb($betriebCode, $missingAddresses);
		if ($resolvedKey !== "") {
			return UpdateMissingAddressToJsonByKey($resolvedKey, $missingAddresses, $Feldkatalog, $data, $TableSetup, $filepath, $csvFile, $csvDesc, $message);
		}
		$record = BuildMissingAddressRecordFromFeldkatalog($betriebCode, $Feldkatalog);
		if ($record === null) {
			$message = "Fehlende Adresse für Betrieb " . $betriebCode . " nicht gefunden.";
			return false;
		}
		return ApplyMissingAddressSync($record, $Feldkatalog, $data, $TableSetup, $filepath, $csvFile, $csvDesc, $message);
	}

	function UpdateAllMissingAddressesToJson($missingAddresses, &$Feldkatalog, &$data, $TableSetup, $filepath, $csvFile, $csvDesc, &$message) {
		$message = "";
		if (count($missingAddresses) === 0) {
			$message = "Keine fehlenden Adressen zum Aktualisieren gefunden.";
			return true;
		}
		$okCount = 0;
		$failCount = 0;
		$details = array();
		foreach ($missingAddresses as $key => $entry) {
			$entryMessage = "";
			$ok = UpdateMissingAddressToJsonByKey($key, $missingAddresses, $Feldkatalog, $data, $TableSetup, $filepath, $csvFile, $csvDesc, $entryMessage);
			if ($ok) {
				$okCount++;
			} else {
				$failCount++;
			}
			$details[] = htmlspecialchars($entryMessage);
		}
		$message = "Massen-JSON-Update fehlender Adressen: " . $okCount . " erfolgreich, " . $failCount . " fehlgeschlagen.";
		if (count($details) > 0) {
			$message .= "<br>" . implode("<br>", $details);
		}
		return ($failCount === 0);
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

	$EnableDelete = false;
	$EnableDeleteSet = "EnableDelete=off";
	$dosafe = false;
	$dodelete = false;
	$SetAll = ($_SERVER["REQUEST_METHOD"] !== "POST");
	if (isset($_POST["FeldNamenWahl"])) {
		$FeldNamenWahl = $_POST["FeldNamenWahl"];
	} else {
		$FeldNamenWahl = '';
	}
	if (isset($_GET["EnableDelete"])) {
		if (strtolower($_GET["EnableDelete"]) === "on") {
			$EnableDelete = true;
		}
	}
	if ($EnableDelete) {
		$EnableDeleteSet = "EnableDelete=on";
	}
	if (isset($_GET["DoSafe"]) && $_GET["DoSafe"] === "Y") {
		$dosafe = true;
	}
	if (isset($_GET["Sortieren"])) {
		$ColSort = $_GET["Sortieren"];
	} else {
		$ColSort = "Name";
	}

	$RegList = array_diff($RegionSettings, array('NO'));
	$RegList["ALL"]["Aktiv"] = true;
	$listFilters = rkrk_adr_parse_list_filters($RegList, $RegionSettings, $AddressTypeOptions, $SetAll);
	$SearchOption = $listFilters["SearchOption"];
	$EnabledRegion = $listFilters["EnabledRegion"];
	$RegionSelected = $listFilters["RegionSelected"];
	$NoRegionSelected = $listFilters["NoRegionSelected"];

	//$csv_file= $RKRKContactFile;

	$OptionJaeger = Array();
	$Feldkatalog= GetFeldkatalog($xmlMissionLocation.$RKRKFelderfileXML);
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
	$SyncInfo = "";
	if (isset($_GET["UpdateFarmerBetrieb"]) && trim((string)$_GET["UpdateFarmerBetrieb"]) !== "") {
		$syncResult = false;
		$syncMsg = "";
		$betriebForUpdate = NormalizeBetriebKey((string)$_GET["UpdateFarmerBetrieb"]);
		$resolvedRowId = FindAddressRowIdByBetrieb($betriebForUpdate, $data);
		if ($resolvedRowId === "") {
			$SyncInfo = "<b>Update fehlgeschlagen:</b> Betrieb " . htmlspecialchars($betriebForUpdate) . " nicht gefunden.";
		} else {
			$syncResult = UpdateFarmerAddressDataById($resolvedRowId, $data, $Feldkatalog, $TableSetup, $csvDocLocation.$csv_file, $csv_file, $csv_desc, $syncMsg);
			$SyncInfo = ($syncResult ? "<b>Update erfolgreich:</b> " : "<b>Update fehlgeschlagen:</b> ") . htmlspecialchars($syncMsg);
			$data = csv_to_array($csvDocLocation.$csv_file,$csv_desc);
		}
	}
	if (isset($_GET["UpdateFarmerID"]) && trim((string)$_GET["UpdateFarmerID"]) !== "") {
		$syncResult = false;
		$syncMsg = "";
		$rowId = trim((string)$_GET["UpdateFarmerID"]);
		$syncResult = UpdateFarmerAddressDataById($rowId, $data, $Feldkatalog, $TableSetup, $csvDocLocation.$csv_file, $csv_file, $csv_desc, $syncMsg);
		$SyncInfo = ($syncResult ? "<b>Update erfolgreich:</b> " : "<b>Update fehlgeschlagen:</b> ") . htmlspecialchars($syncMsg);
		$data = csv_to_array($csvDocLocation.$csv_file,$csv_desc);
	}
	$BulkSyncInfo = "";
	$RunCredentialCheck = (isset($_GET["CheckFarmerCredentials"]) && strtoupper((string)$_GET["CheckFarmerCredentials"]) === "Y");
	if (isset($_GET["UpdateAllDeviatingCredentials"]) && strtoupper((string)$_GET["UpdateAllDeviatingCredentials"]) === "Y") {
		$preSummary = "";
		$preCheckItems = CheckFarmerCredentialDeviations($data, $preSummary);
		$targetIds = array();
		foreach ($preCheckItems as $item) {
			if (isset($item["type"]) && $item["type"] === "diff" && isset($item["id"])) {
				$targetIds[$item["id"]] = $item["id"];
			}
		}
		if (count($targetIds) === 0) {
			$BulkSyncInfo = "<b>Massen-Update:</b> Keine abweichenden Zugangsdaten zum Aktualisieren gefunden.";
		} else {
			$okCount = 0;
			$failCount = 0;
			$detailLines = array();
			foreach ($targetIds as $targetId) {
				$syncMsg = "";
				$betriebForUpdate = NormalizeBetriebKey((string)($data[$targetId]["Betrieb"] ?? ""));
				$ok = UpdateFarmerAddressDataById($targetId, $data, $Feldkatalog, $TableSetup, $csvDocLocation.$csv_file, $csv_file, $csv_desc, $syncMsg);
				if ($ok) {
					$okCount++;
				} else {
					$failCount++;
				}
				$detailLines[] = htmlspecialchars($betriebForUpdate) . ": " . htmlspecialchars($syncMsg);
			}
			$BulkSyncInfo = "<b>Massen-Update:</b> " . $okCount . " erfolgreich, " . $failCount . " fehlgeschlagen.";
			if (count($detailLines) > 0) {
				$BulkSyncInfo .= "<br>" . implode("<br>", $detailLines);
			}
			$data = csv_to_array($csvDocLocation.$csv_file,$csv_desc);
		}
		$RunCredentialCheck = true;
	}
	$MissingAdrInfo = "";
	$MissingAdrRedirect = false;
	if (
		(isset($_GET["UpdateMissingAddressBetrieb"]) && trim((string)$_GET["UpdateMissingAddressBetrieb"]) !== "")
		|| (isset($_GET["UpdateMissingAddressKey"]) && trim((string)$_GET["UpdateMissingAddressKey"]) !== "")
		|| (isset($_GET["UpdateAllMissingAddresses"]) && strtoupper((string)$_GET["UpdateAllMissingAddresses"]) === "Y")
	) {
		if (isset($_GET["UpdateMissingAddressBetrieb"]) && trim((string)$_GET["UpdateMissingAddressBetrieb"]) !== "") {
			$missingBetrieb = NormalizeBetriebKey((string)$_GET["UpdateMissingAddressBetrieb"]);
			$missingOk = false;
			$missingMsg = "";
			$missingOk = UpdateMissingAddressToJsonByBetrieb(
				$missingBetrieb,
				array(),
				$Feldkatalog,
				$data,
				$TableSetup,
				$csvDocLocation . $csv_file,
				$csv_file,
				$csv_desc,
				$missingMsg
			);
			$MissingAdrInfo = ($missingOk ? "<b>Adressdatenbank + JSON erfolgreich:</b> " : "<b>Update fehlgeschlagen:</b> ") . $missingMsg;
			$MissingAdrRedirect = true;
			$FeldList = GetBetriebsFeldList($Feldkatalog);
		} elseif (isset($_GET["UpdateMissingAddressKey"]) && trim((string)$_GET["UpdateMissingAddressKey"]) !== "") {
			$dataForMissing = GetAdrListBtr($data, $FeldList, $ColSort);
			$missingNow = GetNotListedAdresses($Feldkatalog, $dataForMissing);
			$missingKey = (string)$_GET["UpdateMissingAddressKey"];
			$missingOk = false;
			$missingMsg = "";
			$missingOk = UpdateMissingAddressToJsonByKey(
				$missingKey,
				$missingNow,
				$Feldkatalog,
				$data,
				$TableSetup,
				$csvDocLocation . $csv_file,
				$csv_file,
				$csv_desc,
				$missingMsg
			);
			$MissingAdrInfo = ($missingOk ? "<b>Adressdatenbank + JSON erfolgreich:</b> " : "<b>Update fehlgeschlagen:</b> ") . $missingMsg;
			$MissingAdrRedirect = true;
			$FeldList = GetBetriebsFeldList($Feldkatalog);
		}
		if (isset($_GET["UpdateAllMissingAddresses"]) && strtoupper((string)$_GET["UpdateAllMissingAddresses"]) === "Y") {
			$dataForMissing = GetAdrListBtr($data, $FeldList, $ColSort);
			$missingNow = GetNotListedAdresses($Feldkatalog, $dataForMissing);
			$missingNow = rkrk_adr_filter_missing_by_regions($missingNow, $RegionSelected);
			$missingAllOk = false;
			$missingAllMsg = "";
			$missingAllOk = UpdateAllMissingAddressesToJson(
				$missingNow,
				$Feldkatalog,
				$data,
				$TableSetup,
				$csvDocLocation . $csv_file,
				$csv_file,
				$csv_desc,
				$missingAllMsg
			);
			$prefix = ($missingAllOk ? "<b>Massen-Update Adressdatenbank + JSON erfolgreich:</b> " : "<b>Massen-Update fehlgeschlagen:</b> ");
			$MissingAdrInfo = ($MissingAdrInfo !== "" ? $MissingAdrInfo . "<br>" : "") . $prefix . $missingAllMsg;
			$MissingAdrRedirect = true;
			$FeldList = GetBetriebsFeldList($Feldkatalog);
		}
	}
	$CredentialCheckInfo = "";
	$CredentialCheckResults = array();
	if ($RunCredentialCheck) {
		$checkSummary = "";
		$CredentialCheckResults = CheckFarmerCredentialDeviations($data, $checkSummary);
		$CredentialCheckInfo = "<b>Zugangsdaten-Prüfung:</b> " . htmlspecialchars($checkSummary);
	}

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
	// Parameters (pagination — rest siehe Filter oben)
	// ===========================================
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
	if (isset($_GET["DBupdate"])) {
		$DBupdate = $_GET["DBupdate"];
	} else {
		$DBupdate = "";
	}
	$UpdateAllJsonFromDb = (isset($_GET["UpdateAllJsonFromDb"]) && strtoupper((string)$_GET["UpdateAllJsonFromDb"]) === "Y");
	$UpdateAllDbFromJson = (isset($_GET["UpdateAllDbFromJson"]) && strtoupper((string)$_GET["UpdateAllDbFromJson"]) === "Y");
	$DeviationBulkRedirect = false;

// update JSON & Feldkatalog files
///////////////////////////////////////

if ($JSONupdate != "") {
	$JsonSyncInfo = "";
	$JsonSyncResult = ApplyJsonUpdateForAddressRow($JSONupdate, $data, $Feldkatalog, $JsonSyncInfo);
	if (($JsonSyncInfo ?? "") !== "") {
		$SystemInfo .= $JsonSyncInfo . "<br>";
	}
} else {
	$JsonSyncInfo = "";
}

$DbSyncInfo = "";
if ($DBupdate != "") {
	$DbSyncResult = ApplyDatabaseUpdateFromJsonRow(
		$DBupdate,
		$data,
		$Feldkatalog,
		$TableSetup,
		$csvDocLocation . $csv_file,
		$csv_file,
		$csv_desc,
		$DbSyncInfo
	);
	if (($DbSyncInfo ?? "") !== "") {
		$SystemInfo .= $DbSyncInfo . "<br>";
	}
	if ($DbSyncResult) {
		$data = csv_to_array($csvDocLocation . $csv_file, $csv_desc);
	}
}
if (($JSONupdate ?? "") !== "" || ($DBupdate ?? "") !== "") {
	$FeldList = GetBetriebsFeldList($Feldkatalog);
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

	$data = GetAdrListBtr($data, $FeldList, $ColSort);
	if (!is_array($Feldkatalog)) {
		$Feldkatalog = array();
	}
	try {
		$FehlendeBetriebeAll = GetNotListedBetriebs($Feldkatalog, $data);
		$FehlendeAdressenAll = GetNotListedAdresses($Feldkatalog, $data);
		$AdressAbweichungenAll = GetJsonDatabaseAddressDeviations($Feldkatalog, $data);
	} catch (Throwable $e) {
		$FehlendeBetriebeAll = array();
		$FehlendeAdressenAll = array();
		$AdressAbweichungenAll = array();
		$SystemInfo .= "<b>Hinweis:</b> Fehlende Einträge konnten nicht vollständig ermittelt werden.<br>";
	}
	$FehlendeBetriebe = rkrk_adr_filter_missing_by_regions($FehlendeBetriebeAll, $RegionSelected);
	$FehlendeAdressen = rkrk_adr_filter_missing_by_regions($FehlendeAdressenAll, $RegionSelected);
	$AdressAbweichungen = rkrk_adr_filter_missing_by_regions($AdressAbweichungenAll, $RegionSelected);
	$DeviationBulkInfo = "";
	if ($UpdateAllJsonFromDb || $UpdateAllDbFromJson) {
		$bulkSummary = "";
		$bulkOk = false;
		if ($UpdateAllJsonFromDb) {
			$bulkOk = ApplyAllJsonUpdatesFromDatabase($AdressAbweichungen, $data, $Feldkatalog, $bulkSummary);
			$prefix = ($bulkOk ? "<b>Massen-Update JSON aus Datenbank:</b> " : "<b>Massen-Update JSON aus Datenbank fehlgeschlagen:</b> ");
			$DeviationBulkInfo = $prefix . $bulkSummary;
		} else {
			$bulkOk = ApplyAllDatabaseUpdatesFromJson(
				$AdressAbweichungen,
				$data,
				$Feldkatalog,
				$TableSetup,
				$csvDocLocation . $csv_file,
				$csv_file,
				$csv_desc,
				$bulkSummary
			);
			$prefix = ($bulkOk ? "<b>Massen-Update Datenbank aus JSON:</b> " : "<b>Massen-Update Datenbank aus JSON fehlgeschlagen:</b> ");
			$DeviationBulkInfo = $prefix . $bulkSummary;
		}
		$DeviationBulkRedirect = true;
		$data = csv_to_array($csvDocLocation . $csv_file, $csv_desc);
		$data = GetAdrListBtr($data, $FeldList, $ColSort);
		$FeldList = GetBetriebsFeldList($Feldkatalog);
		try {
			$AdressAbweichungenAll = GetJsonDatabaseAddressDeviations($Feldkatalog, $data);
		} catch (Throwable $e) {
			$AdressAbweichungenAll = array();
		}
		$AdressAbweichungen = rkrk_adr_filter_missing_by_regions($AdressAbweichungenAll, $RegionSelected);
	}
	$FehlendeBetriebeShow = $FehlendeBetriebe;
	$FehlendeAdressenShow = $FehlendeAdressen;
	$AdressAbweichungenShow = $AdressAbweichungen;
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

	echo '<form method="post" action="'. htmlspecialchars($_SERVER["PHP_SELF"]) . '">'. chr(10);
	echo "<table class='rkrk-filter-form'>";
	echo "<tr>";
	echo "<td class='rkrk-filter-label'>";
	echo "Typ:";
	echo "</td>";
	echo "<td>";
	$EnabledOption = Array();
	$SearchOption = Array();
	$i=1;
	rkrk_echo_pills_open();
	foreach ($AddressTypeOptions as $Opt ) {
		if ((isset($_POST["Opt-".$i]) && $_POST["Opt-".$i]=="on") or $SetAll) {
			$on = true;
			$EnabledOption[$Opt["Text"]] = 1;
			$SearchOption[] = $Opt["Text"];
		} else {
			$on = false;
			$EnabledOption[$Opt["Text"]] = 0;
		}
		rkrk_echo_pill_checkbox("Opt-".$i, $Opt["Text"], $on);
		$i++;
	}
	rkrk_echo_pills_close();
	//pre_R($EnabledOption);
	//pre_R($SearchOption);
	echo "</td>";
	echo "<td rowspan=3>";
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

	echo "<tr><td class='rkrk-filter-label'>";
		echo "Region: ";
		echo "</td><td>";
		rkrk_echo_pills_open();
		foreach ($RegList as $Reg ) {
			if ($Reg["Aktiv"] && ("NO" != $Reg["Reg"])) {
				$regKey = (string)$Reg["Reg"];
				$isAll = (strcasecmp($regKey, 'ALL') === 0 || strcasecmp($regKey, 'alle') === 0);
				rkrk_echo_pill_checkbox($regKey, $regKey, !empty($EnabledRegion[$regKey]), $isAll ? array('exclusive' => true) : array());
			}
		}
		rkrk_echo_pills_close();
	echo "<tr><td class='rkrk-filter-label'>";
		echo "Felder: ";
		echo "</td><td>";
		$isPost = isset($_SERVER["REQUEST_METHOD"]) && $_SERVER["REQUEST_METHOD"] == "POST";
		rkrk_echo_pills_open();
		foreach ($TableSetup as $Feld ) {
			if ($isPost) {
				$on = isset($_POST[$Feld["Field"] . "_en"]) && $_POST[$Feld["Field"] . "_en"]=="on";
				$TableSetup[$Feld["Field"]]["Show"] = $on;
			} else {
				$on = ($Feld["Show"]==true);
			}
			rkrk_echo_pill_checkbox($Feld["Field"] . "_en", $Feld["Text"], $on);
		}
		rkrk_echo_pills_close();
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
		$dbwParam = isset($_GET["DBW"]) ? (string)$_GET["DBW"] : "";
		echo '<input type="submit" name="Einlesen" value="Einlesen" formaction="' .htmlspecialchars($_SERVER["PHP_SELF"]). '?'. '&DBW=' . htmlspecialchars($dbwParam). '"> '. chr(10);
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
			$regionMatch = false;
			$entryJgs = array();
			if (trim((string)($dataline["Jagdgebiet"] ?? "")) !== "") {
				foreach (explode(",", (string)$dataline["Jagdgebiet"]) as $jg) {
					$jg = trim($jg);
					if ($jg !== "") {
						$entryJgs[$jg] = $jg;
					}
				}
			} elseif (function_exists("rkrk_adr_missing_entry_jgs") && trim((string)($dataline["Felder"] ?? "")) !== "") {
				foreach (rkrk_adr_missing_entry_jgs(array("Felder" => (string)$dataline["Felder"])) as $jg) {
					$entryJgs[$jg] = $jg;
				}
			}
			if (count($entryJgs) > 0) {
				foreach ($RegionSelected as $regSel) {
					if (isset($entryJgs[$regSel])) {
						$regionMatch = true;
						break;
					}
				}
			} else {
				$regionMatch = (
					in_array($dataline["Jagdgebiet"], $RegionSelected) or
					(count($RegionSelected) != count(array_diff($RegionSelected, explode(",",(string)$dataline["Jagdgebiet"]))))
				);
			}
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
					and $regionMatch
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

	// Excel-Export nur bei Filter/Speichern (POST) oder wenn Datei noch fehlt
	if (GetAccess("InternPilots") && ($_SERVER["REQUEST_METHOD"] == "POST" || !file_exists($excel_csv_path))) {
		SaveAdressDataToCSV($outputdata, $TableSetup, $excel_csv_path, "UTF-16LE", true, false, $excel_csv_file, $csv_desc);
	}

	//pre_r($data);
	//echo "MaxEntries: " . $MaxSelectedEntries . "<br>";
	//echo "AnzLinesPPage: " . $AnzLinesPPage . "<br>";
	$i=1;
	$MaxPages = ceil($MaxSelectedEntries/$AnzLinesPPage);
	$showfrom = ($ActPage-1)*$AnzLinesPPage;
	$showto = $showfrom + $AnzLinesPPage;
	$delEnableDelete = isset($_GET["EnableDelete"]) ? (string)$_GET["EnableDelete"] : "off";
	$dbwParam = isset($_GET["DBW"]) ? (string)$_GET["DBW"] : "";
	$FontSizeInPoints = 11;
	if ($FontSize== "") {
		$FontSize= "100%";
	}

	PageControl($ActPage, $MaxPages, $AnzLinesPPage, $FontSize, false, "Speichern", false, "&" . "Sortieren="  . $ColSort);

	echo "<table>" .chr(10);
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
					echo "<input type='image' value='1st page' src='" . $IconLoc . "/delete.png' formaction='" . htmlspecialchars($_SERVER["PHP_SELF"]) . "?ActPage=" . $ActPage . "&" .  "Sortieren="  . $ColSort . "&" . "ID=" . $dataline['ID'] . "&delete=Y&EnableDelete=" . htmlspecialchars($delEnableDelete) . "&DBW=" . htmlspecialchars($dbwParam) . "' height='" . $ButtonHeightSmall . "' title='Linie " . $i . " löschen'>" .chr(10);
				} else {
					echo "<input type='image' value='1st page' src='" . $IconLoc . "/delete.png' formaction='" . htmlspecialchars($_SERVER["PHP_SELF"]) . "?ActPage=" . $ActPage . "&" . "Sortieren="  . $ColSort . "&". " delete=Y&EnableDelete=" . htmlspecialchars($delEnableDelete) . "' height='" . $ButtonHeightSmall . "'  title='Linie " . $i . " löschen'>" .chr(10);
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
	echo '<input type="hidden" name="NumEntries" id="NumEntries" value="'. $i. '" />'. chr(10);

//	echo '		<button type="submit" >Adressen speichern</button>'. chr(10);
//	echo "<input type='submit' value='Speichern' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?" . $MissionSelectionParameter. "&" . $EnableDeleteSet . "'>" .chr(10);
	PageControl($ActPage, $MaxPages, $AnzLinesPPage, $FontSize, true, "Speichern", false, "&" . "Sortieren="  . $ColSort);
	
	echo '</form>'. chr(10);

	if (!is_array($FehlendeBetriebeShow)) {
		$FehlendeBetriebeShow = is_array($FehlendeBetriebe ?? null) ? $FehlendeBetriebe : array();
	}
	if (!is_array($FehlendeAdressenShow)) {
		$FehlendeAdressenShow = is_array($FehlendeAdressen ?? null) ? $FehlendeAdressen : array();
	}
	if (!is_array($AdressAbweichungenShow)) {
		$AdressAbweichungenShow = is_array($AdressAbweichungen ?? null) ? $AdressAbweichungen : array();
	}
	$regionFilterHint = "";
	if (is_array($RegionSelected) && count($RegionSelected) > 0) {
		$regionFilterHint = " <i>(Region: " . htmlspecialchars(implode(", ", $RegionSelected)) . ")</i>";
	}
	$baseQuery = array(
		"ActPage" => $ActPage,
		"Sortieren" => $ColSort,
		"EnableDelete" => ($EnableDelete ? "on" : "off"),
		"MDB" => $RKRKMissionSelected
	);
	if (isset($_GET["DBW"]) && $_GET["DBW"] != "") {
		$baseQuery["DBW"] = $_GET["DBW"];
	}
	if (function_exists("rkrk_adr_query_with_regions")) {
		$baseQuery = rkrk_adr_query_with_regions($baseQuery, $EnabledRegion, $RegionSelected);
	}
	$SystemInfoDisplay = trim((string)$SystemInfo);
	if ($SystemInfoDisplay !== "") {
		$SystemInfoDisplay .= "<br><br>";
	}
	if (($DeviationBulkInfo ?? "") !== "") {
		$SystemInfoDisplay .= $DeviationBulkInfo . "<br><br>";
	}
	$adressAbweichungenCount = count($AdressAbweichungenShow);
	$jsonFilesNeedingUpdate = function_exists("rkrk_adr_collect_deviation_json_files")
		? rkrk_adr_collect_deviation_json_files($AdressAbweichungenShow)
		: array();
	$SystemInfoDisplay .= "<b>Abweichende Adressen JSON/Datenbank (" . $adressAbweichungenCount . "):</b>" . $regionFilterHint . "<br>";
	if ($adressAbweichungenCount > 0 && count($jsonFilesNeedingUpdate) > 0) {
		$SystemInfoDisplay .= "<b>JSON-Dateien mit Update-Bedarf (" . count($jsonFilesNeedingUpdate) . "):</b><br><ul style='margin:6px 0 10px 20px;'>";
		foreach ($jsonFilesNeedingUpdate as $jsonFilePath) {
			$SystemInfoDisplay .= "<li><code>" . htmlspecialchars($jsonFilePath) . "</code></li>";
		}
		$SystemInfoDisplay .= "</ul>";
	}
	if ($adressAbweichungenCount > 0) {
		$updateAllJsonQuery = $baseQuery;
		$updateAllJsonQuery["UpdateAllJsonFromDb"] = "Y";
		$updateAllJsonUrl = htmlspecialchars($_SERVER["PHP_SELF"]) . "?" . http_build_query($updateAllJsonQuery);
		$updateAllDbQuery = $baseQuery;
		$updateAllDbQuery["UpdateAllDbFromJson"] = "Y";
		$updateAllDbUrl = htmlspecialchars($_SERVER["PHP_SELF"]) . "?" . http_build_query($updateAllDbQuery);
		$SystemInfoDisplay .= "<a href='" . $updateAllJsonUrl . "' style='display:inline-block;margin:6px 10px 10px 0;padding:3px 10px;border-radius:8px;background:#6F8B5E;color:#fff;text-decoration:none;'>Alle JSON aus Datenbank aktualisieren</a>";
		$SystemInfoDisplay .= "<a href='" . $updateAllDbUrl . "' style='display:inline-block;margin:6px 0 10px 0;padding:3px 10px;border-radius:8px;background:#2F3E28;color:#fff;text-decoration:none;'>Alle Datenbank aus JSON aktualisieren</a><br>";
		$SystemInfoDisplay .= "<ul>";
		foreach ($AdressAbweichungenShow as $key => $abweichung) {
			$rowId = htmlspecialchars((string)($abweichung["id"] ?? ""));
			$betriebCode = htmlspecialchars((string)($abweichung["betrieb"] ?? ""));
			$name = htmlspecialchars((string)($abweichung["name"] ?? ""));
			$felderTxt = function_exists("rkrk_adr_format_missing_felder")
				? rkrk_adr_format_missing_felder($abweichung)
				: (isset($abweichung["Felder"]) ? (string)$abweichung["Felder"] : "");
			$jsonFilesTxt = function_exists("rkrk_adr_format_deviation_json_files")
				? rkrk_adr_format_deviation_json_files($abweichung)
				: "";
			$SystemInfoDisplay .= "<li><b>" . $rowId . " / " . $betriebCode . "</b> (" . $name . ") Felder: " . htmlspecialchars($felderTxt);
			if ($jsonFilesTxt !== "") {
				$SystemInfoDisplay .= "<br>JSON: " . htmlspecialchars($jsonFilesTxt);
			}
			$SystemInfoDisplay .= "<ul>";
			if (!empty($abweichung["diffs"]) && is_array($abweichung["diffs"])) {
				foreach ($abweichung["diffs"] as $field => $values) {
					$SystemInfoDisplay .= "<li>" . htmlspecialchars((string)$field)
						. " [Datenbank: " . htmlspecialchars((string)($values["db"] ?? ""))
						. " | JSON: " . htmlspecialchars((string)($values["json"] ?? "")) . "]</li>";
				}
			}
			$updateJsonQuery = $baseQuery;
			$updateJsonQuery["JSONupdate"] = (string)($abweichung["id"] ?? "");
			$updateJsonUrl = htmlspecialchars($_SERVER["PHP_SELF"]) . "?" . http_build_query($updateJsonQuery);
			$updateDbQuery = $baseQuery;
			$updateDbQuery["DBupdate"] = (string)($abweichung["id"] ?? "");
			$updateDbUrl = htmlspecialchars($_SERVER["PHP_SELF"]) . "?" . http_build_query($updateDbQuery);
			$SystemInfoDisplay .= "</ul> "
				. "<a href='" . $updateJsonUrl . "' style='display:inline-block;margin:4px 6px 8px 0;padding:2px 8px;border-radius:8px;background:#6F8B5E;color:#fff;text-decoration:none;'>JSON aus Datenbank aktualisieren</a>"
				. "<a href='" . $updateDbUrl . "' style='display:inline-block;margin:4px 0 8px 0;padding:2px 8px;border-radius:8px;background:#2F3E28;color:#fff;text-decoration:none;'>Datenbank aus JSON aktualisieren</a></li>";
		}
		$SystemInfoDisplay .= "</ul>";
	} else {
		$SystemInfoDisplay .= "<i>Keine abweichenden Adressen zwischen JSON und Datenbank.</i><br>";
	}
	echo "<div id='rkrk-system-info' style='background-color:#FEF9E7;margin:12px 0;padding:10px;border:1px solid #E8D98A;'>". chr(10);
	echo "<b>System Meldungen</b><br>". chr(10);
	echo $SystemInfoDisplay;
	echo "</div>". chr(10);
	if (!empty($MissingAdrRedirect) || !empty($JSONupdate) || !empty($DBupdate) || !empty($DeviationBulkRedirect)) {
		echo "<script>(function(){try{var u=new URL(window.location.href);u.searchParams.delete('UpdateMissingAddressBetrieb');u.searchParams.delete('UpdateMissingAddressKey');u.searchParams.delete('UpdateAllMissingAddresses');u.searchParams.delete('JSONupdate');u.searchParams.delete('DBupdate');u.searchParams.delete('UpdateAllJsonFromDb');u.searchParams.delete('UpdateAllDbFromJson');var clean=u.pathname+u.search+u.hash;if(clean!==window.location.pathname+window.location.search+window.location.hash){window.history.replaceState({},'',clean);}}catch(e){}})();</script>". chr(10);
	}

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
