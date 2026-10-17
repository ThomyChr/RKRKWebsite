<?php
  //session_start();
  include_once('scripts/rkrk_gen.php');
  include_once('scripts/rkrk_menu.php');
  include_once('scripts/rkrk_map_functions.php');
  include_once('rkrk_access_control.php');

	function rkrk_info_access_matches($accessCsv) {
		global $WebFreigabe;
		$needle = trim(strval($WebFreigabe));
		if ($needle === '') {
			return false;
		}
		$parts = array_map('trim', explode(',', strval($accessCsv)));
		return in_array($needle, $parts, true);
	}

	function rkrk_info_echo_icon_row($iconFile, $title, $htmlText) {
		global $IconLoc, $ButtonHeight;
		echo "<tr><td>". chr(10);
		echo '<img src="' . $IconLoc . '/' . $iconFile . '" height="' . $ButtonHeight .'" title="' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '">' . chr(10);
		echo "</td><td>" . $htmlText . "<td>". chr(10);
		echo "</td></tr>". chr(10);
	}

	$pagetitle = "Drohnenrekitzrettung - Infos/Help";
	headerpage($pagetitle , false);
	GrantAccess();
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

	$loginUrl = "https://tchr-frog.synology.me/rkropen/pub/rkrk.php";
	echo "<h2>Login auf der Website</h2>" . chr(10);
	echo "Auf unserer internen Website (" . $loginUrl . ") ein Login, bei welchem sich unsere Benutzer registrieren müssen, bevor sie die auf die Daten zugreifen können. Ich habe versucht dies sehr einfach zu gestalten, so dass wir unsere Partner nicht erschrecken oder verhindern, dass sie die Daten benutzen.<br>". chr(10);	
	echo "Jeder der nicht registriert ist kann dies direkt machen und bekommen den Public Zugriff (wie bis anhin), beim nächsten Einloggen ist dann meist das Passwort schon gespeichert und es ist nur ein Klick mehr und man ist drin.<br><br>". chr(10);	

	echo "Warum ein Login?<br>". chr(10);	
	echo "<li>Für uns ist es wichtig, dass wir wissen wer auf unsere Daten zugreift und diese auch nutzt</li>". chr(10);	
	echo "<li>Mit den Benutzerdaten und Präferenzen ist es Möglich die Webseite weiter zu vereinfachen und den Nutzern direkt das anzuzeigen, was ihnen nutzt</li>". chr(10);	
	if (GetAccess("InternLinks")) {
		echo "<li>Bisher hatten wir fünf unterschiedliche URL, die die Funktionen freischaltete, dies ist jetzt nicht mehr nötig, da da die Funktionen über den User freigeschaltet werden.</li>". chr(10);	
		echo "<li>Dies bring somit nicht nur Sicherheit (z.B. beim Teilen von Links) sondern auch eine grössere Flexibilität beim zuweisen von Benutzerrechten (Vorteil für mich beim Unterhalt)</li>". chr(10);	
		echo "<li>Mit dem Wechsel werden wir nur noch drei URL unterhalten: Entwicklung, Interne Seite für Test und Public (für alle). Wenn ihr nicht etwas spezifisches Testen wollt (z.B. intern), dann könnt ihr eure Links auf " . $loginUrl . " konzentrieren. Ihr bekommt da alle Funktionen aufgeschaltet.</li>". chr(10);	
	}

	if (GetAccess("InternLinks")) {
		echo "<h2>Zu Beachten</h2>";
		echo FilenameDefinition();

//	2. Filename: JJ_NNN_FFFF_RRR_KKKKK_HH_PP_YY_VV.jpg
//* JJ: Jagdrevier
//* NNN: Katalognummer (Optional für das Erstellen des Feldes, wenn nicht eingefügt, wird dies vom Mission  Manager gemacht und das Feld umbenannt)
//* FFFF: Flurname aus GIS (Mehrerer Worte mit "-" verbinden)
//* RRR: Richtung für mehrere Abschnitte eines zum Flurnamen gehörenden Feldes
//* KKKKK: Katasternummer aus GIS
//* HH: Flughöhe
//* PP: Ersteller (TC - Thomy, VP - Vesa, CH - Chris, LZ - Lucas)
//* YY: Ersteller Jahr
//* VV: Version (Optional)
	}
	echo "<h2>Legende zu Karten und Felder</h2>";
	definitions();
	echo "<h2>Funktionen</h2>";
	echo "<table>". chr(10);
	aasort($MainMenu, "sort");
	$helpGroups = array(
		'Felder' => 'Felder und Karten',
		'Edit' => 'Daten bearbeiten',
		'Einsätze' => 'Einsätze und Flugplanung',
		'Info' => 'Info und Lernen',
		'Unterhalt' => 'Katalog und Unterhalt',
		'Intern' => 'Interne Werkzeuge',
		'' => 'PDF und weitere Funktionen',
	);
	$helpSkip = array('Home', 'Info', 'GoBack', 'Logout');
	foreach ($helpGroups as $groupKey => $groupTitle) {
		$any = false;
		foreach ($MainMenu as $key => $Menu) {
			if (empty($Menu["activ"]) || in_array($key, $helpSkip, true)) {
				continue;
			}
			$mm = strval($Menu["main_menu"] ?? '');
			if ($mm !== $groupKey) {
				continue;
			}
			if (!MenuEntryAccessGranted($key)) {
				continue;
			}
			if (!$any) {
				echo "<tr><td colspan=3>". chr(10);
				echo "<h3>" . htmlspecialchars($groupTitle, ENT_QUOTES, 'UTF-8') . "</h3>" . chr(10);
				echo "</td></tr>". chr(10);
				$any = true;
			}
			CreateSiteLinkInfoTableEntry($key, "");
		}
	}

	if (MenuEntryAccessGranted('Fluege')) {
		echo "<tr><td colspan=3>". chr(10);
		echo "<h3>Tipps zu den Flugbuchungen</h3>" . chr(10);
		echo "</td></tr>". chr(10);
		echo "<tr><td></td><td>";
		echo "Bei parallelem Arbeiten den Pilotenfilter einschalten oder <i>SaveLine</i> verwenden.<br>";
		echo "Karten, KMZ und JSON über den Pfeil neben Kontrollflug einblenden.<br>";
		echo "Fläche, JG, Gde und Flugzeit sind nur bei Feld <i>MAN</i> editierbar.<br>";
		echo "Checkliste und Tages-PDF (auch für vergangene Einsatztage) stehen pro Pilotentag bereit.";
		echo "<td></td></tr>". chr(10);
	}

	echo "<tr><td colspan=3>". chr(10);
	echo "<h3>Funktionen in den Edit-Seiten</h3>" . chr(10);
	echo "</td></tr>". chr(10);
	echo "<tr><td>". chr(10);

	if (GetAccess("TestOnly")) {
		echo '<img src="' . $IconLoc . '/delete_off.png" height="' . $ButtonHeight .'" title="EnableDelete">' . chr(10);
		echo "</td><td><b>Delete</b></br>Aktivieren der Löschfunktion in der Seite (zum Verhindern von ungewollten Löschfunktionen).<td>". chr(10);
		echo "</td></tr>". chr(10);
		echo "<tr><td>". chr(10);
	}
	if (GetAccess("InternLinks")) {
		echo '<img src="' . $IconLoc . '/debug_off.png" height="' . $ButtonHeight .'" title="EnableDebug">' . chr(10);
		echo "</td><td><b>Debug</b></br>Umschalten auf die den Debug Missionsktatlog zum Testen von Funktionen.<td>". chr(10);
		echo "</td></tr>". chr(10);
	}
	echo "<tr><td colspan=3>". chr(10);

	echo "<h3>Funktionen in den Map-Seiten</h3>" . chr(10);
	echo "</td></tr>". chr(10);
	echo "<tr><td></td><td colspan=2>". chr(10);
	echo "<p>Kartengrund und Felder/KML: der aktive Eintrag zeigt den Namen neben dem Icon. Bei den anderen erscheint der Name nur beim Darüberfahren — die Buttonreihe verschiebt sich dabei nicht.</p>" . chr(10);
	echo "<p>SwissTopo-Ebenen (standard ausgeschaltet, Transparenz per Schieber): <b>Wohngebiete nach AuLaV</b> (BAZL), <b>swissALTI3D Relief</b> und <b>swissSURFACE3D Relief</b> (multidirektionale Reliefschattierung von swisstopo). Dieselben Ebenen und die Kartenbasis S/W (schwarz-weiss) gibt es im Overlay Editor und im Feld-Editor.</p>" . chr(10);
	echo "</td></tr>". chr(10);
	if (isset($MapSettings['Types']) && is_array($MapSettings['Types'])) {
		foreach ($MapSettings['Types'] as $mapType) {
			if (empty($mapType['Aktiv']) || !rkrk_info_access_matches($mapType['Access'] ?? '')) {
				continue;
			}
			rkrk_info_echo_icon_row($mapType['IconOn'], $mapType['Text'], '<b>' . htmlspecialchars($mapType['Text'], ENT_QUOTES, 'UTF-8') . '</b>');
		}
	}
	if (isset($MapSettings['Options']) && is_array($MapSettings['Options'])) {
		foreach ($MapSettings['Options'] as $mapOpt) {
			if (empty($mapOpt['Aktiv']) || !rkrk_info_access_matches($mapOpt['Access'] ?? '')) {
				continue;
			}
			rkrk_info_echo_icon_row($mapOpt['IconOn'], $mapOpt['Text'], '<b>' . htmlspecialchars($mapOpt['Text'], ENT_QUOTES, 'UTF-8') . '</b> anzeigen');
		}
	}
	rkrk_info_echo_icon_row('show_qr_t.png', 'QR Code anzeigen', '<b>QR code</b> anzeigen für den Link auf die MAP (für Mobiltelefongebrauch)');
	echo "</table>". chr(10);

	echo '<br>' .chr(10);
	if (GetAccess("InternLinks")) {
		echo "<h2>Interne Instruktionen</h2>";
		echo "<h3>Eintragen der Einsatztage resp. Freitage</h3>";
		echo "<p>Eintragen der Einsatztage die dir als Pilot nicht gehen (zur Information des Teams und zum Einschätzen der Einsatzkapazität).</p>";
		echo "<video width='320' height='240' controls>". chr(10);
		echo "<source src='..\help\RKRK Blockierte Tage eintragen.mp4' type='video/mp4'>". chr(10);
		echo "</video>". chr(10);	
		echo '<br>' .chr(10);
		echo "<h3>Hinzufügen eines neuen Feldes</h3>";
		echo "<p>Hinzufügen eines neuen Feldes durch hochladen der relevanten Files (Übersichtsbild, Flugplan, JSON- und KMZ-Datei).</p>";
		echo "<video width='320' height='240' controls>". chr(10);
		echo "<source src='..\help\RKRK Laden der Daten eines neuen Feldes.mp4' type='video/mp4'>". chr(10);
		echo "</video>". chr(10);	
		echo '<br>' .chr(10);
		echo "<h3>Buchen und Bearbeiten von Flügen</h3>";
		echo "<p>Buchen und Bearbeiten von Flügen. Eintragen der Flugzeiten, des Piloten und Jägers. Ansicht des Tagesflugkarte und des Tagesplanes (dieser kann pro Pilot oder für das ganze Team generiert werden.</p>";
		echo "<video width='320' height='240' controls>". chr(10);
		echo "<source src='..\help\RKRK Buchen und Bearbeiten von Flügen.mp4' type='video/mp4'>". chr(10);
		echo "</video>". chr(10);	
		echo "<p>KMZ- und JSON-Dateien für den Einsatz direkt in den Flugbuchungen über die Download-Icons am Pilotentag herunterladen.</p>";
        echo "<h3>Löschen eines neuen Feldes</h3>";
		echo "<p>Löschen eines Feldes inkl. den zughörigen Dateien und dem Katalogeintrag.</p>";
        echo "<video width='320' height='240' controls>". chr(10);
		echo "<source src='..\help\RKRK Löschen eines Feldes.mp4' type='video/mp4'>". chr(10);
		echo "</video>". chr(10);	
		echo '<br>' .chr(10);
		if (GetAccess("TestOnly")) {
			echo "<h3>Umbenennen eines Feldes</h3>";
			echo "<p>Umbenennen eines Feldes oder ändern der Region und anderer Feldnamen relevanten Daten.</p>";
			echo "<video width='320' height='240' controls>". chr(10);
			echo "<source src='..\help\RKRK Umbenennen eines Feldes.mp4' type='video/mp4'>". chr(10);
			echo "</video>". chr(10);	
			echo '<br>' .chr(10);
			//Nicht mehr nötig 27.02.2023
			//echo "<h3>Updaten der Daten im Google Sheet</h3>";
			//echo "<video width='320' height='240' controls>". chr(10);
			//echo "<source src='..\help\Einlesen des CSV files ins GoogleSheet.mp4' type='video/mp4'>". chr(10);
			//echo "</video>". chr(10);	
			//echo '<br>' .chr(10);
		}
	}

?></body>
</html>