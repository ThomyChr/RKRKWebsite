<?php
	session_start();
	include_once('scripts/rkrk_gen.php');
	include_once('scripts/rkrk_felddaten.php');
	include_once('scripts/rkrk_csvfiles.php');
	include_once('scripts/rkrk_flugflaechen.php');
	require('scripts/fpdf/fpdf.php');

	CheckIPandExit();
	if (function_exists('rkrk_pdf_maybe_emit_html_shell')) {
		rkrk_pdf_maybe_emit_html_shell('Geflogene Flächen');
	}

	SetMissionLocation();

	if (isset($_GET["SelFrom"]) && trim((string)$_GET["SelFrom"]) !== '') {
		$FromDate = $_GET["SelFrom"];
	} elseif (isset($_GET["FromDate"]) && trim((string)$_GET["FromDate"]) !== '') {
		$FromDate = $_GET["FromDate"];
	} else {
		$FromDate = GetSasionStartDate(date('Y'));
	}

	if (isset($_GET["SelTo"]) && trim((string)$_GET["SelTo"]) !== '') {
		$ToDate = $_GET["SelTo"];
	} elseif (isset($_GET["ToDate"]) && trim((string)$_GET["ToDate"]) !== '') {
		$ToDate = $_GET["ToDate"];
	} else {
		$ToDate = GetSasionEndDate(date('Y'));
	}

	$userHint = '';
	if (!empty($_SESSION[$SessNme_RegisteredInitialen])) {
		$userHint = (string)$_SESSION[$SessNme_RegisteredInitialen];
	}

	$allFlights = rkrk_flugflaechen_load_flights($FromDate, $ToDate);
	$filtered = rkrk_flugflaechen_filter_geflogen($allFlights, $FromDate, $ToDate, array());
	$stats = rkrk_flugflaechen_collect_stats($filtered);
	$basename = rkrk_flugflaechen_basename($FromDate, $ToDate, $userHint);

	$Feldkatalog = array();
	$mapPng = '';
	if ($stats['fields'] > 0) {
		$Feldkatalog = GetFeldkatalog($xmlMissionLocation . $RKRKFelderfileXML);
		if (!is_array($Feldkatalog)) {
			$Feldkatalog = array();
		}
		$built = rkrk_flugflaechen_build_kml(
			$Feldkatalog,
			$stats['feldIds'],
			'Geflogen ' . $FromDate . ' - ' . $ToDate
		);
		if (!empty($built['kml']) && (int)($built['count'] ?? 0) > 0) {
			rkrk_flugflaechen_save_kml($built['kml'], $basename);
			try {
				require_once('scripts/rkrk_kml2svg.php');
				$svgBase = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $basename;
				$GLOBALS["BBoxEast"] = null;
				$GLOBALS["BBoxNorth"] = null;
				$GLOBALS["BBoxZoom"] = null;
				kml_text_to_svg((string)$built['kml'], $svgBase, 1600, 1600, 22, 200.0);
				$svgPath = $svgBase . '.svg';
				$pngPath = $svgBase . '.png';
				$mapPng = rkrk_flugflaechen_raster_png_from_svg($svgPath, $pngPath);
			} catch (Throwable $e) {
				$mapPng = '';
			}
		}
	}

	if (!function_exists('rkrk_ff_pdf_txt')) {
		function rkrk_ff_pdf_txt($s) {
			$s = (string)$s;
			if (function_exists('iconv')) {
				$out = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $s);
				if ($out !== false) {
					return $out;
				}
			}
			return utf8_decode($s);
		}
	}

	WriteToLog("do", basename($_SERVER['PHP_SELF']), "Pdf", $FromDate . ".." . $ToDate, (string)$stats['fields'], "");

	class PDF_Flugflaechen extends FPDF
	{
		function Header()
		{
			global $FromDate, $ToDate, $titleimage, $ImageLoc;
			$this->SetFont('Arial', 'B', 14);
			$this->Cell(0, 8, rkrk_ff_pdf_txt('Geflogene Flächen'), 0, 1, 'L');
			$this->SetFont('Arial', '', 10);
			$this->Cell(0, 6, rkrk_ff_pdf_txt('Zeitraum: ' . $FromDate . ' – ' . $ToDate . ' (Geflogen = Ja)'), 0, 1, 'L');
			$this->Ln(2);
			$logo = '';
			if (isset($titleimage) && $titleimage !== '' && file_exists($titleimage)) {
				$logo = $titleimage;
			} elseif (isset($ImageLoc) && file_exists($ImageLoc . '202121 Logo-RKRKZ.png')) {
				$logo = $ImageLoc . '202121 Logo-RKRKZ.png';
			}
			if ($logo !== '') {
				// optional logo at top-right skipped to keep layout simple
			}
		}

		function Footer()
		{
			$this->SetY(-15);
			$this->SetFont('Arial', 'I', 8);
			$this->Cell(0, 10, rkrk_ff_pdf_txt('Seite ') . $this->PageNo() . '/{nb}  |  ' . date('Y-m-d H:i'), 0, 0, 'C');
		}
	}

	$pdf = new PDF_Flugflaechen();
	$pdf->AliasNbPages();
	$pdf->AddPage();
	$pdf->SetFont('Arial', '', 10);
	$pdf->Cell(0, 6, rkrk_ff_pdf_txt(
		'Flüge: ' . (int)$stats['flights']
		. '   |   Felder: ' . (int)$stats['fields']
		. '   |   Fläche: ' . number_format((float)$stats['flaeche'], 2, '.', "'") . ' ha'
	), 0, 1, 'L');
	$pdf->Ln(2);

	if ($mapPng !== '' && is_file($mapPng)) {
		$pdf->SetFont('Arial', 'B', 11);
		$pdf->Cell(0, 6, rkrk_ff_pdf_txt('Kartenübersicht'), 0, 1, 'L');
		$pageW = $pdf->GetPageWidth() - 20;
		$pdf->Image($mapPng, 10, $pdf->GetY(), $pageW);
		$pdf->Ln($pageW * 0.75);
		$pdf->AddPage();
	}

	$pdf->SetFont('Arial', 'B', 11);
	$pdf->Cell(0, 6, rkrk_ff_pdf_txt('Feldliste'), 0, 1, 'L');
	$pdf->SetFont('Arial', 'B', 9);
	$w = array(12, 28, 18, 22, 28, 40);
	$pdf->Cell($w[0], 6, '#', 1, 0, 'C');
	$pdf->Cell($w[1], 6, 'Feld', 1, 0, 'C');
	$pdf->Cell($w[2], 6, rkrk_ff_pdf_txt('Flüge'), 1, 0, 'C');
	$pdf->Cell($w[3], 6, rkrk_ff_pdf_txt('ha'), 1, 0, 'C');
	$pdf->Cell($w[4], 6, 'Datum', 1, 0, 'C');
	$pdf->Cell($w[5], 6, 'Pilot', 1, 1, 'C');
	$pdf->SetFont('Arial', '', 9);

	if ($stats['fields'] === 0) {
		$pdf->Cell(array_sum($w), 8, rkrk_ff_pdf_txt('Keine geflogenen Flächen in diesem Zeitraum.'), 1, 1, 'L');
	} else {
		$i = 1;
		foreach ($stats['feldIds'] as $fid) {
			$row = $stats['fieldList'][$fid] ?? array();
			if ($pdf->GetY() > 270) {
				$pdf->AddPage();
				$pdf->SetFont('Arial', 'B', 9);
				$pdf->Cell($w[0], 6, '#', 1, 0, 'C');
				$pdf->Cell($w[1], 6, 'Feld', 1, 0, 'C');
				$pdf->Cell($w[2], 6, rkrk_ff_pdf_txt('Flüge'), 1, 0, 'C');
				$pdf->Cell($w[3], 6, rkrk_ff_pdf_txt('ha'), 1, 0, 'C');
				$pdf->Cell($w[4], 6, 'Datum', 1, 0, 'C');
				$pdf->Cell($w[5], 6, 'Pilot', 1, 1, 'C');
				$pdf->SetFont('Arial', '', 9);
			}
			$ha = ((float)($row['Flaeche'] ?? 0) > 0) ? number_format((float)$row['Flaeche'], 2) : '';
			$pdf->Cell($w[0], 6, (string)$i, 1, 0, 'C');
			$pdf->Cell($w[1], 6, rkrk_ff_pdf_txt((string)$fid), 1, 0, 'L');
			$pdf->Cell($w[2], 6, (string)(int)($row['Flights'] ?? 0), 1, 0, 'C');
			$pdf->Cell($w[3], 6, $ha, 1, 0, 'R');
			$pdf->Cell($w[4], 6, (string)($row['LastDate'] ?? ''), 1, 0, 'C');
			$pdf->Cell($w[5], 6, rkrk_ff_pdf_txt((string)($row['Pilot'] ?? '')), 1, 1, 'L');
			$i++;
		}
	}

	$pdf->Output('I', 'RKRK_Geflogene_Flaechen_' . $FromDate . '_' . $ToDate . '.pdf');
	exit;
?>
