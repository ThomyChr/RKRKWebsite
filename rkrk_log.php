<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" >
<?php
	session_start();
	include_once('scripts/rkrk_gen.php');
	include_once('scripts/rkrk_csvfiles.php');
	include_once('scripts/rkrk_inputforms.php');
	include_once('scripts/rkrk_menu.php');
	
	$pagetitle = "Logfiles";
	headerpage($pagetitle , false);
	CreateTopMenu();
	PrintRKRKHeader($pagetitle);


	// ===========================================
	// Gobal Defintions
	// ===========================================



	// ===========================================
	// Defintions
	// ===========================================
		$fileoutput = "";
		$debugoutput = false;

 
	// ===========================================
	// Paramters
	// ===========================================
	SetMissionLocation();
	$MissionSelectionParameter = "MDB=" . $RKRKMissionSelected;
	//echo "Loc: " . $RKRKMissionSelected . " - MDB: " .$csvDocLocation . "<br>";


	if (isset($_GET["EnableDelete"])) {
	   if (strtolower($_GET["EnableDelete"])=="on") {
			//echo "EnableDelete=on";
			$EnableDelete= true;
		} else {
			//echo "EnableDelete=on";
			$EnableDelete= false;
		}
	}

	// ===========================================
	// Form Header
	// ===========================================

	$FontSizeInPoints = 12;
	if ($FontSize== "") {
		$FontSize= "110%";
	}
	$TableColor =  GetTableColor ("");
	AlternateLineTable($TableColor['even'], $FontSize, $FontSizeInPoints, $TableColor['odd']);

	echo "<table>";
	echo "<tr><td width=80%>";
	echo "</td><td>";
	// Home
	echo '<a href="rkrk.php"><img src="' . $IconLoc . '/home.png" height="' . $ButtonHeight .'" title="Home"></a>' .chr(10);
	// go back
	echo '<button type="button" value="Go back!" style="background-color:transparent; border-color:transparent;vertical-align: middle;" onclick="history.back()"><img src="' . $IconLoc . '/return.png" height="' . $ButtonHeight .'" title="Home"></button>';
	//Not needed on this page
	//echo  MissionLibraryButtons($EnableDeleteSet,false);
	echo "</td></tr>";
	echo "</table>";

	// ===========================================
	// Data output
	// ===========================================

	SetTableWidth("1500px");

	//read directory
	$filenames = $logfilelocation.'*_logfile.csv';
	$files = glob($filenames); // To get a list of the files in the images directory
	array_multisort(array_map('filemtime', $files), SORT_NUMERIC, SORT_DESC, $files);
	foreach ($files as $file){
		echo "<h2>File: ". $file . "</h2>";
		$data =array();
		//Read CSV file
		//$data = csv_to_array(utf8_encode($file),';');
		$data = csv_to_array($file,';');
		array_multisort(array_map('Zeit', $data), SORT_NUMERIC, SORT_DESC, $data);
		usort($data, 'Type');
		//pre_r($data);

		//display data
		echo "<table>";
		echo "<th>#</th>";
		echo "<th>Zeit</th>";
		echo "<th>Level</th>";
		echo "<th>TCPIP</th>";
		//echo "<th>MACAdr</th>";
		echo "<th>Who</th>";
		echo "<th>Type</th>";
		echo "<th>Seite</th>";
		echo "<th>Text</th>";
		echo "<th>Parameter</th>";
		
		$i=0;
		foreach ($data as $line)
		{
			$i++;
			echo "<tr>";
			echo "<td width='35'>";
			echo $i;
			echo "</td>";
			echo "<td width='220'>";
			echo $line["Zeit"];
			echo "</td>";
			echo "<td width='50'>";
			echo $line["Level"];
			echo "</td>";
			echo "<td width='150'>";
			echo $line["IP Adresse"];
			echo "</td>";
			echo "<td width='80'>";
			if ($line["User"]!="") 
				echo $line["User"];
			else
				echo $IPUsed[$line["IP Adresse"]]["Name"];
			echo "</td>";
			//echo "<td>";
			//echo $line["Mac Address"];
			//echo "</td>";
			echo "<td  width='20'>";
			echo $line["Typ"];
			echo "</td>";
			echo "<td  width='250'>";
			echo $line["Seite"];
			echo "</td>";
			echo "<td  width='150'>";
			echo str_replace($searchSDZ, $replaceSDZ,  $line["Text"]);
			echo "</td>";
			echo "<td  width='150'>";
			echo str_replace($searchSDZ, $replaceSDZ, $line["Parameter"]);
			echo "</td>";
			echo "</tr>";
		}
		echo "</table>";

	}


?>


</body>
</HTML>
