<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" >
<?php
	session_start();
	include_once('scripts/rkrk_gen.php');
	include_once('scripts/rkrk_csvfiles.php');
	include_once('scripts/rkrk_inputforms.php');
	
	//check impropriate IP addresses accessing page
	CheckIPandExit();

	//Write to LOGfile
	WriteToLog("get", basename($_SERVER['PHP_SELF']), "öffnen ","","","");

	// ===========================================
	// Gobal Defintions
	// ===========================================
	
	$csv_desc = ';';
	$csv_file= $RKRKContactFile;
	$csv_file_LWB = $RKRK_LWB_ContactFile;
	//$csv_file= $RKRKContactFile;


	// ===========================================
	// Defintions
	// ===========================================
		
		$fileoutput = "";
		$debugoutput = false;

 
	// ===========================================
	// Paramters
	// ===========================================

	if (isset($_GET["Table"])) {
		$Tablelist = explode(",",$_GET["Table"]);
	} else	{
		$Tablelist = 'all';
	}

	
	//read file (again after writing to file
	//Adressen Piloten, Jäger
	//transferGoogleSheetToCSV($csvDocLocation.$csv_file);
	$data = csv_to_array($csvDocLocation.$csv_file,$csv_desc);
	
	//sorting data
	aasort($data, "ID");
	aasort($data, "Jagdgebiet");
	aasort($data, "Name");
	aasort($data, "JG");
	//pre_r($data);

	headerpage("Adressen Kontakte", false, $BaseColor);

	// ===========================================
	// TABLE
	// ===========================================

    
//Initialen,JG,Jagdgebiet,Name,Kontaktnummer,P Telefon,Mail,Adresse,RKRS,Bemerkung

	$FontSizeInPoints = 11;
	if ($FontSize== "") {
		$FontSize= "110%";
	}
	$TableColor =  GetTableColor ($BaseColor);
	AlternateLineTable($TableColor['even'], $FontSize, $FontSizeInPoints, $TableColor['odd']);
	SetTableWidth("100%");
	echo "<style>
html, body { margin:0; padding:4px 0; background:#fff; color:#23384d; font-family: Verdana, \"Segoe UI\", Arial, Helvetica, sans-serif; overflow-x:auto; -webkit-overflow-scrolling:touch; }
.top, .bar, #navDemo, #debuginfo { display:none !important; }
.rkrk-list-scroll { overflow-x:auto; -webkit-overflow-scrolling:touch; max-width:100%; }
table.rkrk-list-table {
  border: none !important;
  border-collapse: collapse;
  border-spacing: 0;
  width: max-content;
  min-width: 100%;
  max-width: none;
  background: #fff;
  font-family: inherit;
  font-size: 14px;
  line-height: 1.25;
  table-layout: auto;
}
table.rkrk-list-table th,
table.rkrk-list-table td {
  border: none !important;
  padding: 4px 8px;
  text-align: left;
  vertical-align: top;
  white-space: nowrap;
  width: 1%;
  background: transparent;
}
@media (max-width: 800px) {
  table.rkrk-list-table { font-size: 12px; }
  table.rkrk-list-table th,
  table.rkrk-list-table td { padding: 3px 5px; }
  table.rkrk-list-table .rkrk-col-optional { display: none; }
  table.rkrk-list-table .rkrk-col-mail { white-space: normal; word-break: break-word; }
}
table.rkrk-list-table th {
  font-weight: 700;
  color: #23384d;
  background: #fff !important;
}
table.rkrk-list-table tr:nth-child(odd) { background-color: #ffffff !important; }
table.rkrk-list-table tr:nth-child(even) { background-color: #f2f2f2 !important; }
table.rkrk-list-table a { color: #1d4ed8; text-decoration: underline; }
</style>\n";
	$rkrkMailCell = function ($mail) {
		$mail = trim((string)$mail);
		if ($mail === '') {
			return '';
		}
		$safe = htmlspecialchars($mail, ENT_QUOTES, 'UTF-8');
		return '<a href="mailto:' . $safe . '">' . $safe . '</a>';
	};
	$OptionRKRKS = Array("Ja"=>"Ja","Nein"=>"Nein","-"=>"-");
	$DataSetFound = false;

	if 	(in_array("JGListe",$Tablelist) or
		 ($Tablelist == "all"))
		{

		$i= 1;
		echo "<table class='rkrk-list-table'>" .chr(10);
		echo "<tr >" .chr(10);
		echo "<th>JG</th>" .chr(10);
		echo "<th>Jagdgebiet</th>" .chr(10);
		echo "<th>Name</th>" .chr(10);
		echo "<th>KontaktNr</th>" .chr(10);
		echo "<th>Mail</th>" .chr(10);
		echo "<th class='rkrk-col-optional'>Pilot</th>" .chr(10);
		echo "</tr >" .chr(10);

		foreach ($data as $dataline) {
			if ((($dataline['Typ']=="Jäger") or
				($dataline['Typ']=="Jäger Amt")) 
				and ($dataline['Kontakt']=="Ja")
				//and (strval($dataline['JG']) == intval(strval($dataline['JG'])))
				 ){
			//echo "IntVal " . intval(strval($dataline['JG'])) .  " " . strval($dataline['JG']);
			//pre_r($dataline);
			// Existing Data
				echo '<tr>' .chr(10);
				echo '<td>' .chr(10);
				echo $dataline['JG'];
				echo '</td>' .chr(10);
				echo '<td>' .chr(10);
				echo $dataline['Jagdgebiet'];
				echo '</td>' .chr(10);
				echo '<td>' .chr(10);
				echo $dataline['Name'];
				echo "</td>" .chr(10);
				echo '<td>' .chr(10);
				echo $dataline['Kontaktnummer'];
				echo "</td>" .chr(10);
				echo '<td class="rkrk-col-mail">' .chr(10);
				echo $rkrkMailCell($dataline['Mail'] ?? '');
				echo '</td>' .chr(10);
				echo '<td class="rkrk-col-optional">' .chr(10);
				$PilotList = "";
				$Pilot=array();
				foreach ($data as $pilotline) {
					$JGList = explode(", ", $pilotline['Jagdgebiet']);
					//pre_R($JGList);
					if (in_array($dataline['JG'], $JGList , true))
						{
						$Pilot[] = $pilotline['Name'];
					}
				}
				$PilotList = implode(", ", $Pilot);
				echo $PilotList;
				echo '</td>' .chr(10);
				echo "</tr>" .chr(10);
			}
			$i++;
		}
		echo "</table>" .chr(10);
	}

	if 	(in_array("JGListeAmt",$Tablelist) or
		 ($Tablelist == "all"))
		{

		$i= 1;
		echo "<table class='rkrk-list-table'>" .chr(10);
		echo "<tr >" .chr(10);
		echo "<th>JG</th>" .chr(10);
		echo "<th>Jagdgebiet</th>" .chr(10);
		echo "<th>Name</th>" .chr(10);
		echo "<th>KontaktNr</th>" .chr(10);
		echo "<th>Mail</th>" .chr(10);
		echo "<th class='rkrk-col-optional'>Pilot</th>" .chr(10);
		echo "</tr >" .chr(10);

		foreach ($data as $dataline) {
			if (($dataline['Typ']=="Jäger Amt") and
				($dataline['Kontakt']=="Ja") and
				(strval($dataline['JG']) == intval(strval($dataline['JG']))) ){
					
			// Existing Data
				echo '<tr>' .chr(10);
				echo '<td>' .chr(10);
				echo $dataline['JG'];
				echo '</td>' .chr(10);
				echo '<td>' .chr(10);
				echo $dataline['Jagdgebiet'];
				echo '</td>' .chr(10);
				echo '<td>' .chr(10);
				echo $dataline['Name'];
				echo "</td>" .chr(10);
				echo '<td>' .chr(10);
				echo $dataline['Kontaktnummer'];
				echo "</td>" .chr(10);
				echo '<td class="rkrk-col-mail">' .chr(10);
				echo $rkrkMailCell($dataline['Mail'] ?? '');
				echo '</td>' .chr(10);
				echo '<td class="rkrk-col-optional">' .chr(10);
				$PilotList = "";
				$Pilot=array();
				foreach ($data as $pilotline) {
					$JGList = explode(", ", $pilotline['Jagdgebiet']);
					//pre_R($JGList);
					if (in_array($dataline['JG'], $JGList , true))
						{
						$Pilot[] = $pilotline['Name'];
					}
				}
				$PilotList = implode(", ", $Pilot);
				echo $PilotList;
				echo '</td>' .chr(10);
				echo "</tr>" .chr(10);
			}
			$i++;
		}
		echo "</table>" .chr(10);
	}

	
	aasort($data, "ID");
	aasort($data, "Jagdgebiet");
	aasort($data, "Name");
	//pre_r($data);
	
	if 	(in_array("PilotListAmt",$Tablelist) or
		 ($Tablelist == "all"))
		{

		$i= 1;
		echo "<table class='rkrk-list-table'>" .chr(10);
		echo "<tr >" .chr(10);
		//echo "<th>#</th>" .chr(10);
		echo "<th>Init</th>" .chr(10);
		echo "<th>Name</th>" .chr(10);
		echo "<th>KontaktNr</th>" .chr(10);
		echo "<th>Jagdgebiet</th>" .chr(10);
		echo "</tr >" .chr(10);
		foreach ($data as $dataline) {
			if ((($dataline['Typ']=="Pilot") or
				($dataline['Typ']=="Pilot Zug") or
				($dataline['Typ']=="Pilot RKRK")) and
				($dataline['Kontakt']=="Ja") and
				($dataline['Jagdgebiet']!="")){
			// Existing Data
				echo '<tr>' .chr(10);
				//echo '<td>' .chr(10);
				//echo $i;
				//echo '</td>' .chr(10);
				echo '<td>' .chr(10);
				echo $dataline['Initialen'];
				echo '</td>' .chr(10);
				echo '<td>' .chr(10);
				echo $dataline['Name'];
				echo "</td>" .chr(10);
				echo '<td>' .chr(10);
				echo $dataline['Kontaktnummer'];
				echo "</td>" .chr(10);
				echo '<td>' .chr(10);
				echo $dataline['Jagdgebiet'];
				echo '</td>' .chr(10);
				echo "</tr>" .chr(10);
			}
			$i++;
		}
		echo "</table>" .chr(10);
	}

	//Adressen Bauern
	//Read CSV file
	//transferGoogleSheetToCSV();
	//transferGoogleSheetToCSV($csvDocLocation.$csv_file_LWB);
	//echo $csvDocLocation.$csv_file_LWB;
	$data_LWB = csv_to_array($csvDocLocation.$csv_file_LWB,$csv_desc);

	//sorting data
	//aasort($data_LWB, "Name");
	//pre_r($data_LWB);


	if 	(in_array("BList",$Tablelist) or
		 ($Tablelist == "all"))
		{

		$i= 1;
		echo "<table class='rkrk-list-table'>" .chr(10);
		echo "<tr >" .chr(10);
		echo "<th width='15px'>#</th>" .chr(10);
		echo "<th width='120px'>Betrieb</th>" .chr(10);
		echo "<th width='200px'>Name</th>" .chr(10);
		echo "<th width='160px'>KontaktNr</th>" .chr(10);
		echo "<th>Felder</th>" .chr(10);
		echo "</tr >" .chr(10);

		foreach ($data_LWB as $dataline) {
			if ($dataline['Typ']=="Bauer") {
			// Existing Data
				echo '<tr>' .chr(10);
				echo '<td>' .chr(10);
				echo $i;
				echo '</td>' .chr(10);
				echo '<td>' .chr(10);
				echo $dataline['Betrieb'];
				echo '</td>' .chr(10);
				echo '<td>' .chr(10);
				echo $dataline['Name'];
				echo "</td>" .chr(10);
				echo '<td>' .chr(10);
				echo $dataline['Kontaktnummer'];
				echo "</td>" .chr(10);
				echo '<td>' .chr(10);
				echo $dataline['Felder'];
				echo '</td>' .chr(10);
				echo "</tr>" .chr(10);
			}
			$i++;
		}
		echo "</table>" .chr(10);
	}



	if 	(in_array("PilotList",$Tablelist) or
		 ($Tablelist == "all"))
		{

		echo "<table class='rkrk-list-table'>" .chr(10);
		echo "<tr >" .chr(10);
		echo "<th>#</th>" .chr(10);
		echo "<th>Init</th>" .chr(10);
		echo "<th>JG</th>" .chr(10);
		echo "<th>Jagdgebiet</th>" .chr(10);
		echo "<th>Name</th>" .chr(10);
		echo "<th>KontaktNr</th>" .chr(10);
		echo "<th>P Telefon</th>" .chr(10);
		echo "<th>Mail</th>" .chr(10);
		echo "<th>Adresse</th>" .chr(10);
		echo "<th>RKRS</th>" .chr(10);
		echo "<th>Bemerkung</th>" .chr(10);
		echo "</tr >" .chr(10);

		foreach ($data as $dataline) {
			if (true) {
			// Existing Data
				echo '<tr>' .chr(10);
				echo '<td>' .chr(10);
				echo $i;
				echo '</td>' .chr(10);
				echo '<td>' .chr(10);
				echo $dataline['Initialen'];
				echo '</td>' .chr(10);
				echo '<td>' .chr(10);
				echo $dataline['JG'];
				echo '</td>' .chr(10);
				echo '<td>' .chr(10);
				echo $dataline['Jagdgebiet'];
				echo '</td>' .chr(10);
				echo '<td>' .chr(10);
				echo $dataline['Name'];
				echo "</td>" .chr(10);
				echo '<td>' .chr(10);
				echo $dataline['Kontaktnummer'];
				echo "</td>" .chr(10);
				echo '<td>' .chr(10);
				echo $dataline['P Telefon'];
				echo "</td>" .chr(10);
				echo '<td>' .chr(10);
				echo $dataline['Mail'];
				echo "</td>" .chr(10);
				echo '<td>' .chr(10);
				echo $dataline['Adresse'];
				echo "</td>" .chr(10);
				echo '<td>' .chr(10);
				echo $dataline['RKRS'];
				echo "</td>" .chr(10);
				echo '<td>' .chr(10);
				echo $dataline['Bemerkung'];
				echo "</td>" .chr(10);
				echo "</tr>" .chr(10);
			}
			$i++;
		}
		echo "</table>" .chr(10);
		}

?>


</body>
</HTML>
