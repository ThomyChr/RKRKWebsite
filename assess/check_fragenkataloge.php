<?php
/**
 * Alle Fragenkataloge gegen das Schema aus rkrk_digitalassess_admin.php prüfen.
 *
 * Aufruf (ein Argument = Ordner mit AssessmentListe.csv und den Fragen-CSVs):
 *   php check_fragenkataloge.php "y:\rkropen\...\data\assess"
 */

$assessDir = isset($argv[1]) ? rtrim(str_replace('\\', '/', trim($argv[1])), '/') : '';

$questionHeaders = array(
	"Nr", "Status", "Frage", "Loesungsauswahl", "Bereich", "Herkunft", "Frage-Bild", "Bildformat", "Loesungs-Bild",
	"Loesungstext", "Loesung", "Antwort", "Drone", "Zeit", "Audienz", "Prioritaet", "Rueckmeldung", "RueckmeldungText", "Aktiv", "Locked",
);
$assessmentHeaders = array(
	"Nr", "Key", "Titel", "Kurzbeschreibung", "Bild", "Einfuehrung", "Erfuellt", "PunkteModus", "DriveLocation",
	"LernDatei", "FragenDatei", "AnzQuestions", "Audienz", "QuizTime", "Quiz", "Schulung", "AntwortMischen", "FragenBildAnzeigen", "Aktiv", "Locked",
);

function lineMsg($severity, $file, $text) {
	echo "[" . $severity . "] " . ($file !== "" ? basename($file) . ": " : "") . $text . "\n";
}

if ($assessDir === "") {
	lineMsg("HINWEIS", "", "Nutze z. B.: php check_fragenkataloge.php /pfad/zu/data/assess");
	exit(2);
}

if (!is_dir($assessDir)) {
	lineMsg("ER", "", "Ordner nicht gefunden: $assessDir");
	exit(2);
}

$listPath = $assessDir . "/AssessmentListe.csv";
if (!is_file($listPath)) {
	lineMsg("ER", "", "AssessmentListe.csv fehlt unter: $assessDir");
	exit(2);
}

include_once(dirname(__DIR__) . "/scripts/rkrk_csvfiles.php");

$assRows = csv_to_array_ascII($listPath, ";");
if ($assRows === false || !is_array($assRows)) {
	lineMsg("ER", $listPath, " konnte nicht gelesen werden.");
	exit(1);
}

$catalogsReferenced = array();
foreach ($assRows as $idx => $row) {
	if (!is_array($row)) {
		continue;
	}
	$key = isset($row["Key"]) ? trim((string)$row["Key"]) : "";
	$fq = isset($row["FragenDatei"]) ? trim((string)$row["FragenDatei"]) : "";
	if ($key === "") {
		lineMsg("WAN", $listPath, "Zeile ohne Key (Index #$idx).");
		continue;
	}
	if ($fq === "") {
		lineMsg("WAN", $listPath, "Key «$key» ohne FragenDatei.");
		continue;
	}
	$p = $assessDir . "/" . ltrim($fq, "/");
	if (isset($catalogsReferenced[$fq])) {
		lineMsg("HINWEIS", $listPath, "FragenDatei «$fq» mehrfach referenziert.");
	}
	$catalogsReferenced[$fq] = $p;

	if (!is_file($p)) {
		lineMsg("ER", $listPath, "Key «$key»: Datei fehlt: $fq");
		continue;
	}
	echo "--- Katalog {$fq} (Assessment «{$key}»)\n";

	$maxLen = 0;
	if (($fbin = fopen($p, 'rb')) !== false) {
		while (($bytes = fgets($fbin)) !== false) {
			$maxLen = max($maxLen, strlen($bytes));
		}
		fclose($fbin);
		if ($maxLen > 950) {
			lineMsg("HINWEIS", $p, "Längste Zeile ca. {$maxLen} Bytes; mit alter fgetcsv-Begrenzung 1000 konnten Zeilen gekappt werden — in rkrk_csvfiles.php nun 1 MiB.");
		}
	}

	$data = csv_to_array_ascII($p, ";");
	if ($data === false || !is_array($data)) {
		lineMsg("ER", $p, "Lesen csv_to_array_ascII schlug fehl.");
		continue;
	}

	$hdr = null;
	if (($fh = fopen($p, 'r')) !== false) {
		$hdr = fgetcsv($fh, 1048576, ";");
		if (!is_array($hdr) || count($hdr) < 5) {
			lineMsg("ER", $p, "Kopfzeile verdächtig kurz oder leer.");
			fclose($fh);
			$hdr = null;
		} else {
			$lineno = 1;
			$mismatch = 0;
			while (($row = fgetcsv($fh, 1048576, ";")) !== false) {
				$lineno++;
				if (count($row) !== count($hdr)) {
					$mismatch++;
					if ($mismatch <= 5) {
						lineMsg("ER", $p, "Zeile $lineno: " . count($row) . " Felder statt " . count($hdr) . " (z. B. ; oder Zeilenumbruch im Text ohne CSV-Anführungszeichen).");
					}
				}
			}
			fclose($fh);
			if ($mismatch > 5) {
				lineMsg("ER", $p, "… noch " . ($mismatch - 5) . " weitere Feldanzahl-Probleme.");
			}
		}
	}

	if (is_array($hdr)) {
		$missing = array();
		foreach ($questionHeaders as $h) {
			if (!in_array($h, $hdr, true)) {
				$missing[] = $h;
			}
		}
		if (count($missing) > 0) {
			lineMsg("WAN", $p, "Fehlende Spalten (beim Laden leer): " . implode(", ", $missing));
			if (in_array("Drone", $missing, true)) {
				lineMsg("HINWEIS", $p, "Empfehlung: Spalte «Drone» direkt nach «Antwort» (leer oder «Alle»).");
			}
		}
		$extra = array();
		foreach ($hdr as $col) {
			if ($col !== null && trim((string)$col) !== "" && !in_array($col, $questionHeaders, true)) {
				$extra[] = $col;
			}
		}
		if (count($extra) > 0) {
			lineMsg("HINWEIS", $p, "Zusätzliche Spalten: " . implode(", ", $extra));
		}
	}

	$rowsWithFrage = 0;
	foreach ($data as $r) {
		if (!is_array($r)) {
			continue;
		}
		if (trim((string)($r["Frage"] ?? "")) !== "") {
			$rowsWithFrage++;
		}
	}
	lineMsg("OK", $p, "Daten-Zeilen: " . count($data) . ", mit Fragetext: $rowsWithFrage");
	echo "\n";
}

$refBase = array();
foreach (array_keys($catalogsReferenced) as $relPath) {
	$refBase[basename(str_replace('\\', '/', $relPath))] = true;
}
foreach (glob($assessDir . "/*.csv") as $candidate) {
	$bn = basename($candidate);
	if (strcasecmp($bn, 'AssessmentListe.csv') === 0) {
		continue;
	}
	if (stripos($bn, 'quizlog') !== false) {
		continue;
	}
	if (!isset($refBase[$bn])) {
		lineMsg("HINWEIS", $candidate, "CSV im Assess-Ordner, aber nicht als FragenDatei in AssessmentListe verknüpft.");
	}
}

echo "\nFertig. Schema-Referenz (" . count($questionHeaders) . " Spalten): " . implode(", ", $questionHeaders) . "\n";
