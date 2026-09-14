<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" >
<?php
	session_start();
	include_once('scripts/rkrk_gen.php');
	include_once('scripts/rkrk_functions.php');
	include_once('scripts/rkrk_inputforms.php');
	include_once('rkrk_access_control.php');

	CheckIPandExit();
	WriteToLog("get", basename($_SERVER['PHP_SELF']), "öffnen ","","","");

	if (isset($_GET["Table"])) {
		$Tablelist = explode(",", $_GET["Table"]);
	} else {
		$Tablelist = 'all';
	}

	$einsatzOnly = is_array($Tablelist)
		&& in_array("Einsatzgebiete", $Tablelist, true)
		&& count($Tablelist) === 1;

	$MenuOn = false;
	$_GET['MenuOn'] = 'off';

	headerpage($einsatzOnly ? "Einsatzgebiete" : "", false, $BaseColor);
	if (function_exists('GrantAccess')) {
		GrantAccess();
	}

	$UrlOffset = "";

	if (in_array("Wetter", is_array($Tablelist) ? $Tablelist : array(), true) || $Tablelist == "all") {
		$FontSizeInPoints = 11;
		if ($FontSize == "") {
			$FontSize = "110%";
		}
		$TableColor = GetTableColor($BaseColor);
		AlternateLineTable($TableColor['even'], $FontSize, $FontSizeInPoints, $TableColor['odd']);
		echo GetWeather("16");
	}

	if (in_array("Einsatzgebiete", is_array($Tablelist) ? $Tablelist : array(), true) || $Tablelist == "all") {
		$MapSetting = $UrlOffset . 'rkrk_map.php?Reg=leer&Strt=off&Cntr=off&Area=off&Pfad=off&WW=off&FZ=off&GDE=off&JGG=off&KT=off&ORT=off&Poi=off&HSP=off&GeLa=off&Sun=off&SORA=off&WF=off&EX=NO&Label=on&EG=on&KA=F&MAP=1&Zoom=1.2&Fit=on';
		$ExternalSettings = '&MenuOn=off&GA=gast&Color=white&MapButtons=off';
		$src = htmlspecialchars($MapSetting . $ExternalSettings, ENT_QUOTES, 'UTF-8');

		echo '<link rel="stylesheet" href="template/rkrk_pubcomp.css?v=20260831m" />' . "\n";
		echo '<script>document.documentElement.classList.add("rkrk-pubcomp");</script>' . "\n";
		echo '<div class="rkrk-pubcomp-page">' . "\n";
		echo '  <iframe class="rkrk-pubcomp-map" title="Einsatzgebiete" src="' . $src . '" loading="eager"></iframe>' . "\n";
		echo '  <p class="rkrk-pubcomp-legend"><span class="rkrk-pubcomp-legend-title">Legende:</span> ';
		echo '<span class="rkrk-pubcomp-legend-kern">Kernzone</span> ';
		echo '<span class="rkrk-pubcomp-legend-support">Supportzone</span></p>' . "\n";
		echo '</div>' . "\n";
	}
?>
</body>
</HTML>
