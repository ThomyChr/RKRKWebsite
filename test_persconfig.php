<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" >
<?php
	//session_start();
	include_once('scripts/rkrk_gen.php');
	include_once('scripts/rkrk_feld.php');
	headerpage("Persönliche Einstellungen", false);

	// ===========================================
	// Defintions
	// ===========================================
		$fileoutput = "";
		$debugoutput = false;

		//echo "<pre>";
		//print_r($PersConfig);
		//echo "</pre>";
	
/*
	function CreateConfigArray(){
		$arr =  array(
			"Config id=1"=>array(
						"ID"=>1,
						"Name"=>"Thomas Christen",
						"DeviceName"=>"PC ubxLT-250",
						"LitchiFolder"=>"",
						"MacID"=>"00:11:32:62:7F:A5 00:11:32:62:7F:A5 00:11:32:62:7F:A6",
						"Aktiv"=>"Ja",
					),
			"Buchung id=2"=>array(
						"ID"=>2,
						"Name"=>"06:30",
						"LitchiFolder"=>"",
						"DeviceName"=>"06:30",
						"MacCID"=>"09.05.2022",
						"Aktiv"=>"Ja",
					)
				);
		//echo "<pre>";
		//print_r($arr);
		//echo "</pre>";
		return $arr;
	}
*/

///////////////////////////////////////
// Process and safe data from form in XML
///////////////////////////////////////


	if ($_SERVER["REQUEST_METHOD"] == "POST") {
		//new configuration, safe configuration file
		//echo "NumE: " . $_POST["NumEntries"] .  "<br>" . chr(10);
		$dosafe= true;
		$i= 1;

		$filecontent = "";
		$filecontent .= AddXMLHeader();
		$filecontent .= "<Configsettings>" . CHR(10);
		for ($i = 1; $i <= $_POST["NumEntries"]; $i++) {
//		foreach ($PersConfig as $data) {
			if ($_POST["Username-" . $i] <>"" ) {
				$filecontent .= "  <Config>". chr(10);
				$filecontent .= "    <ID>";
				$filecontent .= $_POST["ID-" . $i];
				$filecontent .= "</ID>" . chr(10);
				$filecontent .= "    <name>";
				$filecontent .= $_POST["Username-" . $i];
				$filecontent .= "</name>" . chr(10);
				$filecontent .= "    <DeviceName>";
				$filecontent .= $_POST["DeviceName-" . $i];
				$filecontent .= "</DeviceName>" . chr(10);
				$filecontent .= "    <LitchiFolder>";
				$filecontent .= $_POST["LitchiFolder-" . $i];
				$filecontent .= "</LitchiFolder>" . chr(10);
				$filecontent .= "    <MacID>";
				$filecontent .= $_POST["MacID-" . $i];
				$filecontent .= "</MacID>" . chr(10);
				$filecontent .= "    <Aktiv>";
				$filecontent .= $_POST["Aktiv-" . $i];
				$filecontent .= "</Aktiv>" . chr(10);
				$filecontent .= "  </Config>" . chr(10);
			}
		}
		$filecontent .= "</Configsettings>" . CHR(10);
		$filecontent .= AddXMLFooter();
		if ($dosafe) {
			$filepath= $xmlMissionLocation . $pers_settings_file;
			if (function_exists('rkrk_write_utf8_file')) {
				rkrk_write_utf8_file($filepath, $filecontent);
			} else {
				$df = fopen($filepath, 'w');
				fputs($df, $filecontent);
				fclose($df);
			}
			if (function_exists('rkrk_catalog_sync_json')) {
				rkrk_catalog_sync_json($filepath);
			}
		}
		//Store File data
	
}

	//read file
	$PersConfig = ReadCatalogFile($xmlMissionLocation.$pers_settings_file, $debugoutput);

		// ===========================================
		// Print Page content
		// ===========================================
		$output = "";
		
		// Printing all the keys and values one by one

		//$searchUML  = array('Ã¼', 'Ã¶', 'Ã?', 'Ã¤', 'Ã?Â¼', 'Ã?Â¤');
		//$replaceUML = array('ü', 'ö', 'Ü', 'ä', 'ü','ä');


	// ===========================================
	// FORM
	// ===========================================

	//form to change setting
	echo "<p><b>Persönliche Einstellungen:</b></p>";

?>
<!-- includes using forms -->
<link type="text/css" href="../jqm-datebox/jqm-datebox-1.4.5.min.css" rel="stylesheet" />
<link type="text/css" href="../jqm-datebox/jquery.mobile.simpledialog.min.css" rel="stylesheet" />
<script type="text/javascript" src="../jqm-datebox/jqm-datebox-1.4.5.core.min.js"></script>
<script type="text/javascript" src="../jqm-datebox/jqm-datebox-1.4.5.mode.calbox.min.js"></script>
<script type="text/javascript" src="../jqm-datebox/jqm-datebox-1.4.5.mode.datebox.min.js"></script>
<script type="text/javascript" src="../jqm-datebox/jqm-datebox-1.4.5.mode.flipbox.min.js"></script>
<script type="text/javascript" src="../jqm-datebox/jqm-datebox-1.4.5.mode.slidebox.min.js"></script>
<script type="text/javascript" src="../jqm-datebox/jquery.mobile.datebox.i18n.de.utf8.min.js"></script>

<!-- Form -->
<?php 
		echo "<meta id='viewport' name='viewport' content='width=device-width, initial-scale=1' />" . chr(10);
		echo "<meta http-equiv='Content-Type' content='application/xhtml+xml; charset=iso-8859-1'/>" . chr(10);
		echo "<link rel='stylesheet' href='https://code.jquery.com/mobile/1.4.5/jquery.mobile-1.4.5.min.css' />" . chr(10);
		echo "<script src='https://code.jquery.com/jquery-1.11.1.min.js'></script>" . chr(10);
		echo "<script src='https://code.jquery.com/mobile/1.4.5/jquery.mobile-1.4.5.min.js'></script>" . chr(10);

$Y=date("Y");
$m=date("m");
$d=date("d");
$d1=date("d.m.Y"); 
$t1=date("H:i");


/*
		echo "<pre>";
		print_r($PersConfig);
		echo "</pre>";

// for debugging of array access
$keys = array_keys($PersConfig);
for($i = 0; $i < count($PersConfig); $i++) {
    echo $keys[$i] . "{<br>";
    foreach($PersConfig[$keys[$i]] as $key => $value) {
        echo $key . " : " . $value . "<br>";
    }
    echo "}<br>";
}
		
    foreach($PersConfig as $PersConf) {
        echo  $PersConf['ID']. "<br>";
    }
    echo "}<br>";
*/
// documenation and examples
// exclude form https://demos.jquerymobile.com/1.4.5/filterable/#&ui-state=dialog
	$i= 1;
	$MacID = getIPConfig() . "<br>". chr(10);
	$DataSetFound = false;
	echo '<form method="post" action="'. htmlspecialchars($_SERVER["PHP_SELF"]) . '">'. chr(10);
	foreach($PersConfig as $PersConf) {
		//echo "$i<br>" . (strval($MacID) == strval($PersConf->MacID));
		if (strval("Thomas Christen") == strval($PersConf->name) or true) {
		//if (trim(strval($MacID)) == trim(strval($PersConf->MacID))) {
			$DataSetFound = true;
			//echo "<pre>";
			//print_r($PersConf);
			//echo "</pre>";

		// Configsform
			echo '			<div data-role="fieldcontain">'. chr(10);
			echo '				<label for="Name' . $i . '">Username:</label>'.chr(10);
			echo '				<input name="Username-' . $i . '" id="Username-' . $i . '"  value="' . $PersConf->name . '"><br>'.chr(10);
			echo '				<label for="DeviceName-' . $i . '">DeviceName:</label>'.chr(10);
			echo '				<input name="DeviceName-' . $i . '" id="DeviceName-' . $i . '"  value="' . $PersConf->DeviceName . '"><br>'.chr(10);
			echo '				<label for="LitchiFolder-' . $i . '">LitchiFolder:</label>'.chr(10);
			echo '				<input name="LitchiFolder-' . $i . '" id="LitchiFolder-' . $i . '"  value="' . $PersConf->LitchiFolder . '"><br>'.chr(10);
			echo '				<label for="MacID-' . $i . '">MacID:</label>'.chr(10);
			echo '				' . $MacID. chr(10);
//			echo '				<input name="MacID-' . $i . '" id="MacID-' . $i . '"  value="' . $PersConf->MacID . '">'.chr(10);
			echo '				<input type="hidden" name="MacID" id="MacID" value="' . $PersConf->DeviceName . '" /><br>'. chr(10);
			echo '				<label for="Aktiv-' . $i . '" class="fieldlabel">Aktiv:</label>'. chr(10);
			echo '				<select name="Aktiv-' . $i . '" id="Aktiv-' . $i . '" data-role="flipswitch">'. chr(10);
			if ($PersConf->Aktiv=="Nein") {
				echo '					<option value="Nein" selected>Nein</option>'. chr(10);
				echo '					<option value="Ja">Ja</option>'. chr(10);
			} else {
				echo '					<option value="Nein" >Nein</option>'. chr(10);
				echo '					<option value="Ja" selected>Ja</option>'. chr(10);
			}
			echo '				</select><br>'. chr(10);
			echo '			</div>'. chr(10);
			echo '				<input type="hidden" name="ID" id="ID" value="'. $PersConf->ID . '" />'. chr(10);
		}
		$i++;
	}
	if ($DataSetFound == false or true) {
		echo "<br><i>Neuer Eintrag:</i><br>". chr(10);
		// Configsform empty
		echo '			<div data-role="fieldcontain">'. chr(10);
		echo '				<label for="Name' . $i . '">Username:</label>'.chr(10);
		echo '				<input name="Username-' . $i . '" id="Username-' . $i . '"  value=""><br>'.chr(10);
		echo '				<label for="DeviceName-' . $i . '">DeviceName:</label>'.chr(10);
		echo '				<input name="DeviceName-' . $i . '" id="DeviceName-' . $i . '"  value=""><br>'.chr(10);
		echo '				<label for="LitchiFolder-' . $i . '">LitchiFolder:</label>'.chr(10);
		echo '				<input name="LitchiFolder-' . $i . '" id="LitchiFolder-' . $i . '"  value=""><br>'.chr(10);
		echo '				<label for="MacID-' . $i . '">MacID:</label>'.chr(10);
		echo '				' . $MacID. chr(10);
//		echo '				<input name="MacID-' . $i . '" id="MacID-' . $i . '"  value="' . $PersConf->MacID . '">'.chr(10);
		echo '				<input type="hidden" name="MacID" id="MacID" value="" /><br>'. chr(10);
		echo '				<label for="Aktiv-' . $i . '" class="fieldlabel">Aktiv:</label>'. chr(10);
		echo '				<select name="Aktiv-' . $i . '" id="Aktiv-' . $i . '" data-role="flipswitch">'. chr(10);
		echo '					<option value="Nein" >Nein</option>'. chr(10);
		echo '					<option value="Ja" selected>Ja</option>'. chr(10);
		echo '				</select><br>'. chr(10);
		echo '				<input type="hidden" name="ID" id="ID" value="'.  $i . '" />'. chr(10);
		echo '			</div>'. chr(10);
	}
	echo '				<input type="hidden" name="NumEntries" id="NumEntries" value="'. $i. '" />'. chr(10);
	echo '		<button type="submit" >Setting speichern</button>'. chr(10);
	echo '</form>'. chr(10);
?>

<a href="index.html" data-role="button" data-icon="delete" data-iconpos="notext">Delete</a>

<script type="text/javascript">
    var macAddress = "";
    var user_address_ip = "";
    var your_computernm = "";
    var wmi = GetObject("winmgmts:{impersonationLevel=impersonate}");
    e = new Enumerator(wmi.ExecQuery("SELECT * FROM Win32_NetworkAdapterConfiguration WHERE IPEnabled = True"));
    for(; !e.atEnd(); e.moveNext()) {
        var s = e.item();
        macAddress = s.MACAddress;
        user_address_ip = s.IPAddress(0);
        your_computernm = s.DNSHostName;
    }
</script>

	</div><!-- /page -->
<input type="text" id="resultsMac" />
<input type="text" id="resultsIp" />
<input type="text" id="resultscname" />

<script type="text/javascript">
    document.getElementById("resultsMac").value = unescape(macAddress);
    document.getElementById("resultsIp").value = unescape(user_address_ip);
    document.getElementById("resultscname").value = unescape(your_computernm);
</script>


</body>
</HTML>
