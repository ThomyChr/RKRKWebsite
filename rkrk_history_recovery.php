<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" >
<head>
<body>
<?php
  session_start();
  include_once('scripts/rkrk_gen.php');
  include_once('scripts/rkrk_inputforms.php');
  include_once('scripts/rkrk_menu.php');
  include_once('rkrk_access_control.php');
  include_once('scripts/rkrk_catalog_backup.php');

$pagetitle = "History Recovery";
headerpage($pagetitle, false);
GrantAccess();
CreateTopMenu();

PrintRKRKHeader($pagetitle);


	// ===========================================
	// JSON-History des Feldkatalogs (XML-Recovery ist tot)
	// ===========================================
	SetMissionLocation();
	$MissionSelectionParameter = "MDB=" . $RKRKMissionSelected;

$FileToRecover = "";
if (isset($_GET["FileName"]) && $_GET["FileName"] != "") {
	$FileToRecover = basename((string)$_GET["FileName"]);
}

	echo  MissionLibraryButtons("",false);
	if ($Layout!=2023) {
		echo CreateSiteLink ("Home", "");
		echo CreateSiteLink ("GoBack", "");
	}
	echo CreateSiteLink ("UnterhaltBackup", "");
	echo "<p>JSON-History. Vollständige Katalog-Snapshots und Website-Sync: <a href='rkrk_admin_unterhalt.php'>Unterhalt</a>.</p>";

	echo "<br>";
if ($FileToRecover != "") {
	$src = $historyfilelocation . $FileToRecover;
	$parsed = function_exists('rkrk_catalog_history_parse') ? rkrk_catalog_history_parse($FileToRecover) : null;
	$orig = $parsed ? $parsed['original'] : preg_replace('/^\d{14}_/', '', $FileToRecover);
	$orig = preg_replace('/\.xml$/i', '.json', $orig);
	if (!preg_match('/\.json$/i', $orig)) {
		$orig .= '.json';
	}
	$dest = rkrk_catalog_json_path($jsonMissionLocation, $orig);
	if (is_file($src) && $dest !== '') {
		WriteToLog("recover", basename($_SERVER['PHP_SELF']), "History Recovery", $FileToRecover, $dest, "");
		if (function_exists('rkrk_catalog_backup_create')) {
			rkrk_catalog_backup_create(
				function_exists('rkrk_unterhalt_user') ? rkrk_unterhalt_user() : 'recover',
				'Vor History-Recovery: ' . $orig,
				'pre-restore'
			);
		}
		copy($src, $dest);
		echo "Katalog wiederhergestellt: " . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . " → " . htmlspecialchars($dest, ENT_QUOTES, 'UTF-8') . "<br>";
	} else {
		echo "Datei nicht gefunden oder kein JSON-Ziel.<br>";
	}
}

$SearchFile = $historyfilelocation . "*.json";
$FilesList = glob($SearchFile);
if (!is_array($FilesList)) {
	$FilesList = array();
}
array_multisort($FilesList, SORT_DESC);
	$FontSizeInPoints = 11;
	if ($FontSize== "") {
		$FontSize= "100%";
	}
	$TableColor =  GetTableColor ("");
	AlternateLineTable($TableColor['even'], $FontSize, $FontSizeInPoints, $TableColor['odd']);

	echo "<table>";
	echo "<tr>";
	echo "<th>";
	echo "File";
	echo "</th>";

	foreach ($FilesList as $File) {
		$FileName = pathinfo($File);
		$base = $FileName["basename"];
		echo "<tr>";
		echo "<td>" .chr(10);
		echo " <a href='".htmlspecialchars($_SERVER["PHP_SELF"])."?FileName=" . rawurlencode($base) . "' ><img src='" . $IconLoc . "/recover.png' height='" . $ButtonHeightSmall . "px' title='recover Feldkatalog ".$base."'></a>";
		echo "</td>";
		echo "<td>";
		echo htmlspecialchars($FileName["filename"], ENT_QUOTES, 'UTF-8');
		echo "</td>";
		echo "<td>";
		echo date("F d Y H:i:s.", filemtime($File));
		echo "</td>";
		echo "<td>";
		echo "size: " . filesize($File);
		echo "</td>";
		echo "</tr>";
	}
	if ($FilesList === array()) {
		echo "<tr><td colspan='4'>Keine JSON-History-Dateien in <code>" . htmlspecialchars((string)$historyfilelocation, ENT_QUOTES, 'UTF-8') . "</code>.</td></tr>";
	}
	echo "</table>";


?>
</body>
</html>
