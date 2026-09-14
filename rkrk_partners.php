<?php
  //session_start();
  include_once('scripts/rkrk_gen.php');
  include_once('scripts/rkrk_menu.php');

	$pagetitle = "Drohnenrekitzrettung - Partners";
	headerpage($pagetitle , false);
	CreateTopMenu();
	PrintRKRKHeader($pagetitle);

?>
<body>
<?php
	echo "<table>". chr(10);
	echo "<tr><td width='80%'></td><td class='right' align='right'>". chr(10);
	if ($Layout!=2023) {
		echo CreateSiteLink ("Home", "");
		echo CreateSiteLink ("GoBack", "");
	}
	echo "</td></tr>". chr(10);
	echo "</table>". chr(10);

	echo "<table>". chr(10);
	echo "<tr><td>". chr(10);
	$style  = "font-size:40px; line-height: 120%;";
	echo "<span style='" . $style . "'><br>".chr(10);
	echo "<b><a href='https://rkrs.uaveditor.com/feldanmeldung?initialData=%7B%22properties%22%3A%7B%22orderData%22%3A%7B%22recommendedPilot%22%3A%22c-Cc16iQStW1%22%7D%7D%2C%22mapCenter%22%3A%7B%22lat%22%3A47.23993%2C%22lng%22%3A8.46005%7D%7D'  target='_blank'>Feldanmeldungen auf dem UAV Editor</a></b><br><br>".chr(10);
	echo "</span>".chr(10);
	$style  = "font-size:30px; line-height: 120%;";
	echo "<span style='" . $style . "'><br>".chr(10);
	echo "Möchtest du ein neues Feld direkt über den UAV Edtior an unser Team melden?<br>" .chr(10);
	echo "Dann kannst du den Link oben oder auch den QR code rechts benutzen.<br>".chr(10);
	echo "</span>".chr(10);
	echo "</td>". chr(10);
	echo "<td>". chr(10);
	echo "</td>". chr(10);
	echo "<td>". chr(10);
	echo "<a href='https://rkrs.uaveditor.com/feldanmeldung?initialData=%7B%22properties%22%3A%7B%22orderData%22%3A%7B%22recommendedPilot%22%3A%22c-Cc16iQStW1%22%7D%7D%2C%22mapCenter%22%3A%7B%22lat%22%3A47.23993%2C%22lng%22%3A8.46005%7D%7D' target='_blank'><img src='" . $ImageLoc ."UAVQRcode_RKRK.jpg'   height='225'></a>". chr(10);
	echo "<br><br>". chr(10);
	echo "</td></tr>". chr(10);
	echo "<tr><td>". chr(10);
	$style  = "font-size:40px; line-height: 120%;";
	echo "<span style='" . $style . "'><br>".chr(10);
	echo "<b><a href='https://www.drohnenrehkitzrettung.ch'  target='_blank'>www.drohnenrehkitzrettung.ch</a></b><br><br>".chr(10);
	echo "</span>".chr(10);
	$style  = "font-size:30px; line-height: 120%;";
	echo "<span style='" . $style . "'><br>".chr(10);
	echo "Möchten Sie uns gerne Unterstützen? <br>" .chr(10);
	echo "Dann verwenden Sie einfach den QR Code für eine Bank- <br>".chr(10);
	echo "oder TWINT Zahlung.<br>".chr(10);
	echo "</span>".chr(10);
	echo "</td>". chr(10);
	echo "<td>". chr(10);
	echo "</td>". chr(10);
	echo "<td>". chr(10);
	echo "<a href='https://drohnenrehkitzrettung.ch' target='_blank'><img src='" . $ImageLoc ."qr_drohnenrekitzrettungch.jpg'   height='225'></a>". chr(10);
	echo "<br><br>". chr(10);
	echo "</td></tr>". chr(10);
	echo "<tr><td>". chr(10);
	$style  = "font-size:28px; line-height: 120%;";
	echo "<span style='" . $style . "'><br>".chr(10);
	echo "UBS Switzerland AG, Bahnhofstrasse 45, <br>Postfach, CH-8001 Zürich , BIC: UBSWCHZH80A<br>".chr(10);
	echo "<b>IBAN: CH12 0023 9239 1639 8641 T</b><br>".chr(10);
	echo "<i>drohnenrehkitzrettung.ch c/o Thomas Christen, <br>".chr(10);
	echo "Baumgartenweg 7, 8934 Knonau</i><br><br>" . chr(10);
	echo "Herzlichen Dank!<br><br>". chr(10);
	echo "Durch Ihre Unterstützung können wir weiterhin die<br>".chr(10);
	echo "<b>Drohnensuchflüge kostenfrei </b> anbieten.<br><br>".chr(10);
	echo "<i>Euer Team von der IG 'Drohnenrehkitzrettung Knonaueramt</i>'".chr(10);
	echo "</span>".chr(10);
	echo "</td>". chr(10);
	echo "<td>". chr(10);
	echo "<a href='https://www.drohnenrehkitzrettung.ch/spenden' target='_blank'><img src='" . $ImageLoc."twint_frei.jpg'  height='300'></a>". chr(10);
	echo "</td>". chr(10);
	echo "<td>". chr(10);
	echo "<a href='https://www.drohnenrehkitzrettung.ch/spenden' target='_blank'><img src='" . $ImageLoc."qr_swiss_frei.jpg'  height='300'></a>". chr(10);
	echo "</td></tr>". chr(10);
	echo "</table>". chr(10);

?></body>
</html>