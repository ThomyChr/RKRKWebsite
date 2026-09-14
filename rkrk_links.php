<?php
  //session_start();
  include_once('scripts/rkrk_gen.php');
  include_once('scripts/rkrk_menu.php');
  include_once('rkrk_access_control.php'); 
 
	headerpage("Drohnenrehkitzrettung", false);
	PrintRKRKHeader("Drohnenrekitzrettung - Links");
	GrantAccess();


	//Write to LOGfile
	WriteToLog("get", basename($_SERVER['PHP_SELF']), "links ","","","");
	$LinkSearch = array("&","/",":");
	$LinkReplace = array("%26","%2F","%3A");

	echo "<table>". chr(10);
	echo "<tr><td width=90%></td><td>". chr(10);
	// Home
	echo '<a href="rkrk.php"><img src="' . $IconLoc . '/home.png" height="' . $ButtonHeight .'" title="Home"></a>' .chr(10);
	// go back
	echo '<button type="button" value="Go back!" style="background-color:transparent; border-color:transparent;" onclick="history.back()"><img src="' . $IconLoc . '/return.png" height="' . $ButtonHeight .'" title="Home"></button>';
	echo "</td></tr>". chr(10);
	echo "</table>". chr(10);

	$WebLink_Intern = $WebLink_intern;
	$WebLink_Public = $WebLocation . "pub/";
	$MapParameter_Intern = $MapIntern;
	$MapParameter_Public = $MapExtern;
	//if (GetAccess("InternLinks")) {
		echo "<h2>Links zu internen Websites (mit QR Code)</h2>";
		echo "<table>". chr(10);
		echo "<tr>" . chr(10);
		echo "<th width=20%>Region</th>". chr(10);
		echo "<th width=30%>Map Intern</th>". chr(10);
		if (GetAccess ("InternalQRCodes")) {
			echo "<th width=30%>Map Public</th>". chr(10);
		}
		echo "</tr>" . chr(10);
		foreach ($RegionSettings as $Key => $Reg) {
		echo "<tr>" . chr(10);
		echo "<td>" . chr(10);
		echo "<a href='" . $link.  "'  target='_geomap'>" . chr(10);
		echo "<b>" . $Reg["Text"] ."</b><br>". chr(10);
		echo "</a>" . chr(10);
		//https://tchr-frog.synology.me:5682/rkropen/test/rkrk_map.php?Reg=24&Strt=on&Cntr=off&Area=on&Pfad=on&EX=NO&Poi=on&KA=S&MAP=1&Zoom=0.8
		$link  = $WebLink_Intern . "rkrk_map.php?Reg=" . $Reg["Reg"] . $MapParameter_Intern;
		$qrlink  = str_replace($LinkSearch ,$LinkReplace,$link );
		if (GetAccess ("InternalQRCodes")) {
			echo "<td>" . chr(10);
			echo "<br>" . chr(10);
			echo "<a href='" . $link.  "'  target='_geomap'>" . chr(10);
			echo "<b>" . $Reg["Text"] ."</b><br>". chr(10);
			echo "</a>" . chr(10);
			echo "<a href='" . $link.  "'  target='_geomap'>" . chr(10);
			echo '<img src="https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl='.$qrlink.'&choe=UTF-8&chld=M" title="Geomap '. $Reg["Text"] .'" />';
			echo "</a>" . chr(10);
			echo "</td>". chr(10);
		}
		
		//https://tchr-frog.synology.me:5682/rkropen/pub/rkrk_map.php?Reg=136&Strt=on&Area=on&KA=S&MAP=&Zoom=0.8
		$link  = $WebLink_Public . "rkrk_map.php?Reg=" . $Reg["Reg"] . $MapParameter_Public;
		$qrlink  = str_replace($LinkSearch ,$LinkReplace,$link );
		echo "<td>" . chr(10);
		echo "<br>" . chr(10);
		echo "<a href='" . $link.  "'  target='_geomap'>" . chr(10);
		echo "<b>" . $Reg["Text"] ."</b><br>". chr(10);
		echo "</a>" . chr(10);
		echo "<a href='" . $link.  "'  target='_geomap'>" . chr(10);
		echo '<img src="https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl='.$qrlink.'&choe=UTF-8&chld=M" title="Geomap '. $Reg["Text"] .'" />';
		echo "</a>" . chr(10);
		echo "</td>". chr(10);
		echo "</tr>" . chr(10);
		}
		echo "</table>". chr(10);
	//}

?></body>
</html>