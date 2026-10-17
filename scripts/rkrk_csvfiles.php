<?php
include_once(__DIR__ . '/rkrk_php80.php');
// ==================
// Funktionen für die Rehktitzrettung
// CSV File handling
// ==================

  //include_once(__DIR__ . '/rkrk_gen.php');
  //$csvFileHelper = __DIR__ . '/rkrk_csvfile.php';
  //if (is_file($csvFileHelper)) {
  //  include_once($csvFileHelper);
  //}

	//Read CSV file
	//Contact data
	// read contacts from CSV files

if (!function_exists('rkrk_app_root')) {
	function rkrk_app_root() {
		if (defined('RKRK_APP_ROOT')) {
			return RKRK_APP_ROOT;
		}
		return dirname(__DIR__);
	}
}

if (!function_exists('rkrk_resolve_app_relative_path')) {
	/** Relativen Pfad (../../rkropen/…) vom App-Root auflösen — unabhängig vom PHP-CWD. */
	function rkrk_resolve_app_relative_path($relPath) {
		$relPath = str_replace('\\', '/', trim((string)$relPath));
		if ($relPath === '') {
			return '';
		}
		if (preg_match('/^[A-Za-z]:[\/\\\\]/', $relPath) || ($relPath[0] === '/' && (!isset($relPath[1]) || $relPath[1] !== '/'))) {
			return str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $relPath);
		}
		$combined = rkrk_app_root() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relPath);
		$parent = dirname($combined);
		$resolvedParent = @realpath($parent);
		if ($resolvedParent !== false) {
			return $resolvedParent . DIRECTORY_SEPARATOR . basename($combined);
		}
		return $combined;
	}
}

if (!function_exists('rkrk_flights_csv_file')) {
	/**
	 * Flug-CSV einer Saison — gleicher Pfad für rkrk_fluege.php und Checkliste.
	 *
	 * @return array{file:string,rel:string,path:string}
	 */
	function rkrk_flights_csv_file($year) {
		global $csvDocLocation, $RKRKFlightsCSV;
		$y = intval($year);
		if ($y < 2000 || $y > 2100) {
			$y = intval(date('Y'));
		}
		$suffix = (isset($RKRKFlightsCSV) && $RKRKFlightsCSV !== '') ? $RKRKFlightsCSV : '_RKRKFluege.csv';
		$csvFile = $y . $suffix;
		$rel = (string)$csvDocLocation . $csvFile;
		$path = rkrk_resolve_app_relative_path($rel);
		return array('file' => $csvFile, 'rel' => $rel, 'path' => $path);
	}
}

if (!function_exists('rkrk_flights_csv_resolve_path')) {
	/**
	 * Lesepfad YYYY_RKRKFluege.csv — bei mehreren Kopien die zuletzt geänderte Datei.
	 */
	function rkrk_flights_csv_resolve_path($year) {
		global $csvDocLocation, $RKRKFlightsCSV;
		$info = rkrk_flights_csv_file($year);
		$candidates = array();
		foreach (array('path', 'rel') as $key) {
			$p = trim((string)($info[$key] ?? ''));
			if ($p !== '') {
				$candidates[$p] = true;
			}
		}
		$y = intval($year);
		if ($y >= 2000 && $y <= 2100) {
			$suffix = (isset($RKRKFlightsCSV) && $RKRKFlightsCSV !== '') ? $RKRKFlightsCSV : '_RKRKFluege.csv';
			$raw = trim((string)$csvDocLocation) . $y . $suffix;
			if ($raw !== '') {
				$candidates[$raw] = true;
			}
		}
		$best = '';
		$bestMtime = -1;
		foreach (array_keys($candidates) as $p) {
			clearstatcache(true, $p);
			if (!is_file($p)) {
				continue;
			}
			$mt = @filemtime($p);
			if ($mt === false) {
				$mt = 0;
			}
			if ($mt >= $bestMtime) {
				$bestMtime = $mt;
				$best = $p;
			}
		}
		if ($best !== '') {
			return $best;
		}
		$path = trim((string)($info['path'] ?? ''));
		if ($path !== '') {
			return $path;
		}
		return trim((string)($info['rel'] ?? ''));
	}
}

if (!function_exists('rkrk_flights_csv_write_targets')) {
	/** Alle Pfade, an die Flug-CSV geschrieben werden soll (Checkliste + Flüge). */
	function rkrk_flights_csv_write_targets($year) {
		$info = rkrk_flights_csv_file($year);
		$targets = array();
		$read = rkrk_flights_csv_resolve_path($year);
		if ($read !== '') {
			$targets[] = $read;
		}
		foreach (array('path', 'rel') as $key) {
			$p = trim((string)($info[$key] ?? ''));
			if ($p !== '' && !in_array($p, $targets, true)) {
				$targets[] = $p;
			}
		}
		return $targets;
	}
}


if (!function_exists('rkrk_flights_csv_write_rows')) {
	/** CSV schreiben wie rkrk_fluege.php (fopen/fputs, CRLF). */
	function rkrk_flights_csv_write_rows($path, array $header, array $rows, $delimiter = ';') {
		$path = trim((string)$path);
		if ($path === '') {
			return false;
		}
		$dir = dirname($path);
		if (!is_dir($dir)) {
			@mkdir($dir, 0777, true);
		}
		$filecontent = implode($delimiter, array_map('strval', $header)) . chr(13) . chr(10);
		foreach ($rows as $row) {
			if (!is_array($row)) {
				continue;
			}
			$parts = array();
			foreach ($header as $key) {
				$v = isset($row[$key]) ? (string)$row[$key] : '';
				if (strpos($v, $delimiter) !== false || strpos($v, '"') !== false || strpos($v, "\n") !== false || strpos($v, "\r") !== false) {
					$v = '"' . str_replace('"', '""', $v) . '"';
				}
				$parts[] = $v;
			}
			$filecontent .= implode($delimiter, $parts) . chr(13) . chr(10);
		}
		$df = @fopen($path, 'w');
		if (!$df) {
			$bytes = @file_put_contents($path, $filecontent, LOCK_EX);
			if ($bytes === false) {
				$bytes = @file_put_contents($path, $filecontent);
			}
			return ($bytes !== false && is_file($path));
		}
		$written = @fputs($df, $filecontent);
		fclose($df);
		return ($written !== false && is_file($path));
	}
}

function transferGoogleSheetToCSV ($filename='') {
	$filename = trim((string)$filename);
	$filecontent = '';
	if ($filename === '' || !is_file($filename) || !is_readable($filename)) {
		return;
	}
	$size = @filesize($filename);
	if ($size === false || $size <= 0) {
		return;
	}
	$df = @fopen($filename, 'r');
	if (!$df) {
		return;
	}
	$filecontent = (string)@fread($df, $size);
	fclose($df);
	// only change file if the file is new from google sheet
	if ($filecontent === '' || strpos($filecontent, '""') === false) {
		return;
	}
	$filecontent = str_replace('#$$$$#', '"', str_replace('"', '', str_replace('""', ' #$$$$# ', $filecontent)));
	$fp = @fopen($filename, 'w');
	if (!$fp) {
		return;
	}
	fwrite($fp, mb_convert_encoding($filecontent, 'UTF-16LE', 'UTF-8'));
	fclose($fp);
}

/**
 * Zellen nach UTF-8; gültiges UTF-8 bleibt unverändert (RKRS und !onRKRS gleich).
 * Die frühere Umstellung „von ASCII“ hat Umlaute zerstört.
 */
if (!function_exists('rkrk_csv_combine_row')) {
	/**
	 * PHP 8 throws ValueError when header and row lengths differ.
	 * Short rows are padded; extra cells are ignored.
	 */
	function rkrk_csv_combine_row($header, $row) {
		if (!is_array($header) || !is_array($row) || $header === array()) {
			return null;
		}
		$headerCount = count($header);
		$rowCount = count($row);
		if ($rowCount < $headerCount) {
			$row = array_pad($row, $headerCount, '');
		} elseif ($rowCount > $headerCount) {
			$row = array_slice($row, 0, $headerCount);
		}
		$combined = @array_combine($header, $row);
		return is_array($combined) ? $combined : null;
	}
}

if (!function_exists('rkrk_csv_strings_to_utf8')) {
	function rkrk_csv_strings_to_utf8($data) {
		if (is_array($data)) {
			$out = array();
			foreach ($data as $k => $v) {
				$out[$k] = rkrk_csv_strings_to_utf8($v);
			}
			return $out;
		}
		if (!is_string($data) || $data === '') {
			return $data;
		}
		if (function_exists('mb_check_encoding') && mb_check_encoding($data, 'UTF-8')) {
			return $data;
		}
		if (function_exists('mb_convert_encoding')) {
			$t = @mb_convert_encoding($data, 'UTF-8', 'Windows-1252');
			if ($t !== false && (!function_exists('mb_check_encoding') || mb_check_encoding($t, 'UTF-8'))) {
				return $t;
			}
			return mb_convert_encoding($data, 'UTF-8', 'ISO-8859-1');
		}
		return $data;
	}
}

function csv_to_array_ascII($filename='', $delimiter=',') 
{
    if(!file_exists($filename) || !is_readable($filename))
        return FALSE;
	if (function_exists('rkrk_log_if_address_file')) {
		rkrk_log_if_address_file($filename);
	}

    $header = NULL;
    $data = array();
	// Längere Fragentexte: 1000 Zeichen reichen oft nicht → Spalten verrutschen / Lesen bricht ab
	$maxLineLen = 1048576;
    if (($handle = fopen($filename, 'r')) !== FALSE)
    {
        while (($row = fgetcsv($handle, $maxLineLen, $delimiter)) !== FALSE)
        {
			//pre_r($row);
            if(!$header) {
                $header = $row;
				if (isset($header[0]) && is_string($header[0])) {
					$header[0] = (string)preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
				}
			} else {
				$combined = rkrk_csv_combine_row($header, $row);
				if ($combined !== null) {
					$data[] = $combined;
				}
			}
        }
        fclose($handle);
    }
	//pre_R($data);
    return rkrk_csv_strings_to_utf8($data);
}


function csv_to_array($filename='', $delimiter=',') 
{
    if(!file_exists($filename) || !is_readable($filename))
        return FALSE;
	if (function_exists('rkrk_log_if_address_file')) {
		rkrk_log_if_address_file($filename);
	}

    $header = NULL;
    $data = array();
	$maxLineLen = 1048576;
    if (($handle = fopen($filename, 'r')) !== FALSE)
    {
        while (($row = fgetcsv($handle, $maxLineLen, $delimiter)) !== FALSE)
        {
			//pre_r($row);
            if(!$header)
                $header = $row;
            else {
				$combined = rkrk_csv_combine_row($header, $row);
				if ($combined !== null) {
					$data[] = $combined;
				}
			}
        }
        fclose($handle);
    }
	//pre_R($data);
	// str_replace() only walks the top level. Under PHP 8 a row array becomes the
	// string "Array", and ReadUserList() then fatals on $entry['Benutzername'].
	global $searchUML, $replaceUML;
	if (!isset($searchUML) || !is_array($searchUML)) {
		$searchUML = array();
	}
	if (!isset($replaceUML) || !is_array($replaceUML)) {
		$replaceUML = array();
	}
	if ($searchUML === array()) {
		return $data;
	}
	$applyUml = function ($value) use (&$applyUml, $searchUML, $replaceUML) {
		if (is_array($value)) {
			foreach ($value as $key => $cell) {
				$value[$key] = $applyUml($cell);
			}
			return $value;
		}
		if (is_string($value)) {
			return str_replace($searchUML, $replaceUML, $value);
		}
		return $value;
	};
	return $applyUml($data);
}

function CheckForDiscriminator($Text, $Discriminator) 
{
	$ReplaceDiscriminator ="/";
	$Text = str_replace($Discriminator, $ReplaceDiscriminator, $Text);
	if (strpos($Text,$Discriminator) >0) {
		//return (chr(34).chr(34) . trim($Text) . chr(34).chr(34));
		return (chr(34) . trim($Text) . chr(34));
	} else {
		return trim($Text);
	}
}	


function CheckForKomma($Text) 
{
	if (strpos($Text,",") >0) {
		//return (chr(34).chr(34) . trim($Text) . chr(34).chr(34));
		return (chr(34) . trim($Text) . chr(34));
	} else {
		return trim($Text);
	}
}	

function CheckForSemicolon($Text) 
{
	if (strpos($Text,";") >0) {
		//return (chr(34).chr(34) . trim($Text) . chr(34).chr(34));
		return (chr(34) . trim($Text) . chr(34));
	} else {
		return trim($Text);
	}
}	

function SaveArrayToCSV ($data, $csv_file) {
	global 	$csvMissionLocation, 
			$csv_desc, 
			$historyfilelocation, 
			$AccSystemInfo;
	
	$filepath= $csvMissionLocation.$csv_file;
	//echo "<br><br>Filepath: " . $filepath . "<br>";
	//pre_R($data);
	
	$filecontent = implode($csv_desc, array_keys($data[array_key_first($data)])). chr(13) . chr(10);
	foreach ($data as $dl_key => $dataline) {
		$outputdata=Array();
		//pre_r($outputdata);
		$filecontent .= implode($csv_desc, $dataline). chr(13) . chr(10);
	}
	//echo "Filecontent: [" . $filecontent. "]<br>";

	// safe history
	$file = $filepath;
	$newfile = $historyfilelocation . date("YmdH") . "00_" . $csv_file;
	//echo "<br>History: " . $file . " to  " . $newfile;
	if (is_file($file)) {
		if (!copy($file, $newfile)) {
			$AccSystemInfo .= "failed to copy $file...<br>";
		}
	}
	// safe file
	if (true) {
		//echo "FilePath: " . $filepath . " - NumEntries:" . count($data) . "<br>";
		$filecontent = mb_convert_encoding( $filecontent, 'ASCII', 'UTF-8');
		$csvDir = dirname($filepath);
		if (!is_dir($csvDir)) {
			$csvParent = dirname($csvDir);
			$csvDirName = strtolower(basename(str_replace('\\', '/', $csvDir)));
			if ($csvDirName === 'csvfiles' && is_dir($csvParent)) {
				if (!mkdir($csvDir, 0775) && !is_dir($csvDir)) {
					return;
				}
			} else {
				return;
			}
		}
		if (!is_dir($csvDir)) {
			return;
		}
		$df = fopen($filepath, 'wb');
		if ($df !== false) {
			fwrite($df, $filecontent);
			fclose($df);
		}
	} else {
		echo $filecontent ;
	}
	// remove all session variables
}

function SaveArrayToCSV_ascII ($data, $csv_file) {
	global 	$csvMissionLocation, 
			$csv_desc, 
			$historyfilelocation, 
			$AccSystemInfo;
	
	$filepath= $csvMissionLocation.$csv_file;
	//echo "<br><br>Filepath: " . $filepath . "<br>";
	//pre_R($data);
	
	$filecontent = implode($csv_desc, array_keys($data[array_key_first($data)])). chr(13) . chr(10);
	foreach ($data as $dl_key => $dataline) {
		$outputdata=Array();
		//pre_r($outputdata);
		$filecontent .= implode($csv_desc, $dataline). chr(13) . chr(10);
	}
	//echo "Filecontent: [" . $filecontent. "]<br>";

	// safe history
	$file = $filepath;
	$newfile = $historyfilelocation . date("YmdH") . "00_" . $csv_file;
	//echo "<br>History: " . $file . " to  " . $newfile;
	if (is_file($file)) {
		if (!copy($file, $newfile)) {
			$AccSystemInfo .= "failed to copy $file...<br>";
		}
	}
	// safe file
	if (true) {
		//echo "FilePath: " . $filepath . " - NumEntries:" . count($data) . "<br>";
		$filecontent = mb_convert_encoding( $filecontent, 'UTF-16LE', 'UTF-8');
		$df = fopen($filepath, 'wb');
		if ($df !== false) {
			fwrite($df, $filecontent);
			fclose($df);
		}
	} else {
		echo $filecontent ;
	}
	// remove all session variables
}

function SaveArrayToCSVToLog ($data, $csv_file) {
	global 	$logfilelocation, 
			$csv_desc, 
			$historyfilelocation, 
			$AccSystemInfo;
	
	$filepath= $logfilelocation.$csv_file;
	//echo "<br><br>Filepath: " . $filepath . "<br>";
	//pre_R($data);
	
	$filecontent = implode($csv_desc, array_keys($data[array_key_first($data)])). chr(13) . chr(10);
	foreach ($data as $dl_key => $dataline) {
		$outputdata=Array();
		//pre_r($outputdata);
		$filecontent .= implode($csv_desc, $dataline). chr(13) . chr(10);
	}
	"Filecontent: [" . $filecontent. "]<br>";

	// safe history
	$file = $filepath;
	$newfile = $historyfilelocation . date("YmdH") . "00_" . $csv_file;
	//echo "<br>History: " . $file . " to  " . $newfile;
	if (!copy($file, $newfile)) {
		$AccSystemInfo .= "failed to copy $file...<br>";
	}
	// safe file
	if (true) {
		//echo "FilePath: " . $filepath . " - NumEntries:" . count($data) . "<br>";
		$df = fopen($filepath, 'w');
		fputs($df, $filecontent);
		fclose($df);
	} else {
		echo $filecontent ;
	}
	// remove all session variables
}



function SaveArrayToCSVToHistory ($data, $csv_file) {
	global 	$logfilelocation, 
			$csv_desc, 
			$historyfilelocation, 
			$AccSystemInfo;
	
	$filepath= $historyfilelocation.$csv_file;
	//echo "<br><br>Filepath: " . $filepath . "<br>";
	//pre_R($data);
	
	$filecontent = implode($csv_desc, array_keys($data[array_key_first($data)])). chr(13) . chr(10);
	foreach ($data as $dl_key => $dataline) {
		$outputdata=Array();
		//pre_r($outputdata);
		$filecontent .= implode($csv_desc, $dataline). chr(13) . chr(10);
	}
	"Filecontent: [" . $filecontent. "]<br>";

	// safe history
	//$file = $filepath;
	//$newfile = $historyfilelocation . date("YmdH") . "00_" . $csv_file;
	//echo "<br>History: " . $file . " to  " . $newfile;
	//if (!copy($file, $newfile)) {
	//	$AccSystemInfo .= "failed to copy $file...<br>";
	//}
	// safe file
	if (true) {
		//echo "FilePath: " . $filepath . " - NumEntries:" . count($data) . "<br>";
		$df = fopen($filepath, 'w');
		fputs($df, $filecontent);
		fclose($df);
	} else {
		echo $filecontent ;
	}
	// remove all session variables
}

function SaveAdressDataToCSV($data, &$TableSetup, $filepath, $format, $dosafe, $dodelete, $csv_file ,$csv_desc, $history=false) {
	global 	$historyfilelocation;

//new configuration, safe configuration file
//echo "Filepath: " . $filepath;
//pre_r($data);
$i= 1;

$filecontent = "";
//$filecontent = 'ID;Initialen;JG;Jagdgebiet;Name;Kontaktnummer;P Telefon;Mail;Adresse;RKRS;Typ;Bemerkung' . chr(13) . chr(10);
foreach ($TableSetup AS $TableEntry) {
	$filecontent .= $TableEntry["Field"] . ";";
}
$filecontent = substr($filecontent, 0, -1). chr(13) . chr(10); 

foreach ($data as $dataline) {
	//ID;Initialen;JG;Jagdgebiet;Name;Kontaktnummer;P Telefon;Mail;Adresse;RKRS;Bemerkung
		//echo "ID: ". $dataline["ID"] . "<br>";
		if (
			$dataline["Initialen"] !=""
			//&& $dataline["Jagdgebiet"] !=""
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
				//$rowcontent .= chr(34). chr(13) . chr(10);
				$rowcontent .= chr(13) . chr(10);
				//echo $dataline["ID"] . " - " . $rowcontent . "<br>";
				$i++;
				$filecontent .= $rowcontent;
			}
		}
	}
	
	if (($dosafe)) {
		if ($history) {
			// safe history
			$file = $filepath;
			$newfile = $historyfilelocation . date("YmdH") . "00_" . $csv_file;
			//echo "<br>History: " . $file . " to  " . $newfile;
			if (!copy($file, $newfile)) {
				$SystemInfo .= "failed to copy $file...\n";
			}
		}
		// safe file
		//echo "FilePath: " . $filepath . " - NumEntries:" . $i . "<br>";
		$df = fopen($filepath, 'w');
		if ($format!='UTF-8')
			$filecontent = mb_convert_encoding( $filecontent, $format, 'UTF-8');
		fputs($df, $filecontent);
		fclose($df);
	} else {
		echo "output: <br><br>" . $filecontent;

	}
}

function SaveDataToCSV($data, &$TableSetup, $filepath, $format, $dosafe, $csv_file ,$csv_desc, $history=false) {


//echo "Filepath: " . $filepath;
//echo "csv_desc: " . $csv_desc;
//pre_r($data);
$i= 1;

$filecontent = "";
//$filecontent = 'ID;Initialen;JG;Jagdgebiet;Name;Kontaktnummer;P Telefon;Mail;Adresse;RKRS;Typ;Bemerkung' . chr(13) . chr(10);

if ($TableSetup=="") {
	$filecontent = implode($csv_desc, array_keys($data[array_key_first($data)])). chr(13) . chr(10);
} else {
	foreach ($TableSetup AS $TableEntry) {
		$filecontent .= $TableEntry . ";";
	}
	$filecontent = substr($filecontent, 0, -1). chr(13) . chr(10); 
}	

//pre_r($data);
foreach ($data as $key => $dataline) {
		$rowcontent = "";
		//$rowcontent .= CheckForKomma($i) . $csv_desc;
		if ($TableSetup=="") {
			//pre_R($dataline);
			$rowcontent = implode($csv_desc, $dataline);
			//echo $rowcontent;
		} else {
			//collecting output data
			foreach ($TableSetup AS $TableEntry) {
				
				$rowcontent .= CheckForSemicolon($dataline[$TableEntry]) . $csv_desc;
			}
			//cut last descriminator
			$rowcontent= substr($rowcontent, 0, -1);
			//$rowcontent .= chr(34). chr(13) . chr(10);
		}
		$rowcontent .= chr(13) . chr(10);
		//echo $key . " - " . $rowcontent . "<br>";
		$i++;
		$filecontent .= $rowcontent;
	}
	//echo "output: <br><br>" . $filecontent;
	
	if (($dosafe)) {
		if ($history) {
			// safe history
			$file = $filepath;
			$newfile = $historyfilelocation . date("YmdH") . "00_" . $csv_file;
			//echo "<br>History: " . $file . " to  " . $newfile;
			if (!copy($file, $newfile)) {
				$SystemInfo .= "failed to copy $file...\n";
			}
		}
		// safe file
		//echo "FilePath: " . $filepath . " - NumEntries:" . $i . "<br>";
		$df = fopen($filepath, 'w');
		if ($format!='UTF-8')
			$filecontent = mb_convert_encoding( $filecontent, $format, 'UTF-8');
		fputs($df, $filecontent);
		fclose($df);
	} else {
		echo "output: <br><br>" . $filecontent;

	}
}

function WriteToUpdFeldListe($FeldID, $Feldname, $Directory, $JSONFilename, $Suchfeld, $Flugplan) {
	global $csvMissionLocation, $SessNme_RegisteredInitialen;

	$DatenFileName = "NeueFelder.csv";
	$txt="";
	$csvMissionLocation.$DatenFileName."<br>";
	$d = Array();
	
	if (file_exists($csvMissionLocation.$DatenFileName)) {
		$data= csv_to_array($csvMissionLocation.$DatenFileName, ';');
		//$data= csv_to_array_ascII($csvMissionLocation.$DatenFileName, ';');
		//pre_r($data);
		foreach($data as $key => $dataline) {
			//echo ("key: " . $key . "/ " . $data[$key]["FeldID"] . "<br>");
			if ($key==0) {
				$keys= array_keys($dataline);
			}
			foreach ($keys as $akey => $adata) {
				$d[$data[$key]["FeldID"]][$adata] = $data[$key][$adata];
			}
		}
	}
	//pre_r($d);
	
	
	$d[$FeldID]["Zeit"] = date("Y-m-d H:i:s");
	$d[$FeldID]["FeldID"] = $FeldID;
	$d[$FeldID]["Verzeichnis"] = $Feldname;
	$d[$FeldID]["Feldname"] = $Directory;
	$d[$FeldID]["JSONDatei"] = $JSONFilename;
	$d[$FeldID]["Suchfeld"] = $Suchfeld;
	$d[$FeldID]["Flugplan"] = $Flugplan;
	$d[$FeldID]["Wer"] = $_SESSION[$SessNme_RegisteredInitialen];

	//SaveArrayToCSV_ascII ($d, $DatenFileName);
	SaveArrayToCSV($d, $DatenFileName);
}
?>