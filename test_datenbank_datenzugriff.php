<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" >
<?php
	include_once('scripts/rkrk_gen.php');
	include_once('scripts/rkrk_dbaccess.php');
	include_once('scripts/rkrk_flexdisplay.php');

	// ===========================================
	// Definitions
	// ===========================================

$TableSetup = Array(
	"FeldID" => array ( "Field" => "FeldID",
						"Name" => "FeldID",
						"Type" => "text",
						"Show" => true),
	"Feldname" 	=> array ( "Field" => "Feldname",
						"Name" => "Name",
						"Type" => "text",
						"Show" => true),
	"Flurname" => array ( "Field" => "Flurname",
						"Name" => "Flurname",
						"Type" => "text",
						"Show" => true),
	"GrundstueckID" => array ( "Field" => "GrundstueckID",
						"Name" => "GeoID",
						"Type" => "text",
						"Show" => true),
	"Gemeinde" => array ( "Field" => "Gemeinde",
						"Name" => "Gde",
						"Type" => "text",
						"Show" => true),
	"Betrieb" => array ( "Field" => "Betrieb",
						"Name" => "Betrieb",
						"Type" => "text",
						"Show" => true),
	"Freigabe" => array ( "Field" => "Freigabe",
						"Name" => "Freigabe",
						"Type" => "text",
						"Show" => true),
	"Warnung" => array ( "Field" => "Warnung",
						"Name" => "Warnung",
						"Type" => "text",
						"Show" => true),
	"Jaeger" => array ( "Field" => "Jaeger",
						"Name" => "Jaeger",
						"Type" => "text",
						"Show" => true),
	"Bemerkung" => array ( "Field" => "Bemerkung",
						"Name" => "Bemerkung",
						"Type" => "text",
						"Show" => true),
	"Flug" => array ( "Field" => "Flug",
						"Name" => "Flug",
						"Type" => "text",
						"Show" => true),
	"Funde" => array ( "Field" => "Funde",
						"Name" => "Funde",
						"Type" => "text",
						"Show" => true),
	"Bauer" => array ( "Field" => "Bauer",
						"Name" => "Bauer",
						"Type" => "text",
						"Show" => true),
	"SuchfeldBild" => array ( "Field" => "SuchfeldBild",
						"Name" => "SuchfeldBild",
						"Type" => "text",
						"Show" => true),
	"FlugplanBild" => array ( "Field" => "FlugplanBild",
						"Name" => "FlugplanBild",
						"Type" => "text",
						"Show" => true),
	"JasonFile" => array ( "Field" => "JasonFile",
						"Name" => "JasonFile",
						"Type" => "text",
						"Show" => true),
	"KMLFile" => array ( "Field" => "KMLFile",
						"Name" => "KMLFile",
						"Type" => "text",
						"Show" => true),
	"KMZFile" => array ( "Field" => "KMZFile",
						"Name" => "KMZFile",
						"Type" => "text",
						"Show" => ($RKRKMissionSelected != "M2")),
	"LichtiFile" => array ( "Field" => "LichtiFile",
						"Name" => "LichtiFile",
						"Type" => "text",
						"Show" => ($RKRKMissionSelected == "M2")),
	"Export" => array ( "Field" => "Export",
						"Name" => "Export",
						"Type" => "text",
						"Show" => true),
	"Select" => array ( "Field" => "Select",
						"Name" => "Select",
						"Type" => "text",
						"Show" => true),
				);


	// ===========================================
	// Functions
	// ===========================================

function GetMySQLFeldKatalog ($FromJGList, $Suche="", $SortBy="FeldID") {
	$SQLCommand = "SELECT * FROM `Flugfelder` 
					WHERE 
						`JG` IN (".$FromJGList.")
						AND (
								'' = '".$Suche."'
							OR  `Feldname` LIKE '%".$Suche."%'
							OR  `Flurname` LIKE '%".$Suche."%'
							OR  `GrundstueckID` LIKE '%".$Suche."%'
							OR  `JG` LIKE '".$Suche."%'
							OR  `FeldID` LIKE '".$Suche."%'
							) 
						
					ORDER BY `".$SortBy."`
					;"; 


	//$result = GetQueryToArray($SQLCommand);
	$result = json_decode(json_encode(GetQueryToArray($SQLCommand)), true);
	//pre_r($result);
	return $result;
}

	//////////////////////////////////////////////
	// Main Function
	//////////////////////////////////////////////

	echo "<H1>Datenbank Zugriff</H1>";
	//include_once('rkrk_access_control.php'); 


	//check impropriate IP addresses accessing page
	//CheckIPandExit();

	//Write to LOGfile
	WriteToLog("get", basename($_SERVER['PHP_SELF']), "öffnen ","","","");

	$pagetitle = "Prüfen der Json Daten im Felddaten";
	headerpage($pagetitle , false);
	//GrantAccess();		// not required yet
	//CreateTopMenu();  // not required yet

	// ===========================================
	// Read and select Data
	// ===========================================

	$data = GetMySQLFeldKatalog ("21, 32", "Ober","GrundstueckID");
	//pre_R($data);

	// ===========================================
	// Display Data
	// ===========================================
	
	foreach ($data as $key => $dataline) {
		// desk view
		foreach ($TableSetup as $TableField ) {
			if ($TableField["Show"]) {
				echo $TableField["Name"] . ": " . $dataline[$TableField["Field"]] . "<br>";
			}
		}
		// phone view
	}

?>
<span class="hide-lower-1000" >
<table id="desk-table">
<tr>
    <td>desk-1</td>
    <td>desk-2</td>
    <td>desk-3</td>
</tr>
<tr>
    <td>td1-1</td>
    <td>td2-1</td>
    <td>td3-1</td>
</tr>
<tr>
    <td>td1-2</td>
    <td>td2-2</td>
    <td>td3-2</td>
</tr>
</table>
</span>
<span class="hide-over-1000" >
<table id="phone-table">
<tr>
    <td>phone-1</td>
    <td>td1-1</td>
</tr>
<tr>
    <td>phone-1</td>
    <td>td2-1</td>
</tr>
<tr>
    <td>phone-3</td>
    <td>td3-1</td>
</tr>
    <tr>
    <td>phone-1</td>
    <td>td1-2</td>
</tr>
<tr>
    <td>phone-1</td>
    <td>td2-2</td>
</tr>
<tr>
    <td>phone-3</td>
    <td>td3-2</td>
</tr>
</table>
</span>
<script type='text/javascript'>
<?php
echo "var host = '". $host . "';\n"; 
echo "var db = '". $db . "';\n"; 
echo "var user = '". $user . "';\n"; 
echo "var pass = '". $pass . "';\n"; 
echo "var sql = 'SELECT `FeldID`, `JG`, `Feldname`, `Flurname`, `GrundstueckID` FROM `Flugfelder`;';\n"; 

?>
/*
MySql.Execute(
    host, 
    user, 
    pass, 
    db, 
    sql, 
    function (data) {
        console.log(data)
});
*/
</script>
<?php
//PHPINFO;
//phpinfo();

?>