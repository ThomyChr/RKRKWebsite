<?php
declare(strict_types=1);

/**
 * Map viewport → PDF via TCPDF (preferred on tools.rehkitzrettung.ch).
 * POST JSON: { "image": "data:image/jpeg;base64,...", "filename": "optional.pdf", "title": "...",
 *              "printWidthMm": 281, "printHeightMm": 160, "scale": "1:25'000" }
 * Or form field "image" with the same data-URL / raw base64.
 */

require_once __DIR__ . '/map_env.php';
$__rkrkToolsAuth = __DIR__ . '/../rkrk_tools_auth.php';
if (is_file($__rkrkToolsAuth)) {
    require_once $__rkrkToolsAuth;
} elseif (is_file(__DIR__ . '/rkrk_tools_auth.php')) {
    require_once __DIR__ . '/rkrk_tools_auth.php';
}
unset($__rkrkToolsAuth);

function map_pdf_respond_json(int $status, array $payload): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function map_pdf_find_tcpdf(): ?string
{
    $candidates = [
        __DIR__ . '/tcpdf/tcpdf.php',
        __DIR__ . '/vendor/tecnickcom/tcpdf/tcpdf.php',
        __DIR__ . '/vendor/tcpdf/tcpdf.php',
        __DIR__ . '/../tcpdf/tcpdf.php',
        __DIR__ . '/../vendor/tecnickcom/tcpdf/tcpdf.php',
        __DIR__ . '/../vendor/tcpdf/tcpdf.php',
        __DIR__ . '/../../tcpdf/tcpdf.php',
        __DIR__ . '/../../vendor/tecnickcom/tcpdf/tcpdf.php',
        __DIR__ . '/../../lib/tcpdf/tcpdf.php',
        __DIR__ . '/../../includes/tcpdf/tcpdf.php',
    ];

    $docRoot = (string)($_SERVER['DOCUMENT_ROOT'] ?? '');
    if ($docRoot !== '') {
        $candidates[] = $docRoot . '/tcpdf/tcpdf.php';
        $candidates[] = $docRoot . '/vendor/tecnickcom/tcpdf/tcpdf.php';
        $candidates[] = $docRoot . '/vendor/tcpdf/tcpdf.php';
        $candidates[] = $docRoot . '/lib/tcpdf/tcpdf.php';
        $candidates[] = $docRoot . '/includes/tcpdf/tcpdf.php';
        $candidates[] = $docRoot . '/PHP/tcpdf/tcpdf.php';
    }

    // Optional override: place a one-line path in map/tcpdf_path.php returning string
    $override = __DIR__ . '/tcpdf_path.php';
    if (is_file($override)) {
        $path = include $override;
        if (is_string($path) && $path !== '') {
            array_unshift($candidates, $path);
        }
    }

    foreach ($candidates as $path) {
        $real = realpath($path);
        if ($real !== false && is_file($real)) {
            return $real;
        }
        if (is_file($path)) {
            return $path;
        }
    }
    return null;
}

function map_pdf_decode_image(string $raw): array
{
    $raw = trim($raw);
    if ($raw === '') {
        throw new InvalidArgumentException('Kein Bild empfangen.');
    }
    if (preg_match('#^data:image/(jpeg|jpg|png);base64,#i', $raw, $m)) {
        $fmt = strtolower($m[1]) === 'png' ? 'PNG' : 'JPEG';
        $b64 = substr($raw, strpos($raw, ',') + 1);
    } else {
        $fmt = 'JPEG';
        $b64 = $raw;
    }
    $bin = base64_decode($b64, true);
    if ($bin === false || $bin === '') {
        throw new InvalidArgumentException('Bild-Base64 ungültig.');
    }
    $info = @getimagesizefromstring($bin);
    if ($info === false || empty($info[0]) || empty($info[1])) {
        throw new InvalidArgumentException('Bild konnte nicht gelesen werden.');
    }
    return [
        'bytes' => $bin,
        'format' => $fmt,
        'width' => (int)$info[0],
        'height' => (int)$info[1],
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $tcpdf = map_pdf_find_tcpdf();
    map_pdf_respond_json(200, [
        'ok' => true,
        'tcpdf' => $tcpdf !== null,
        'engine' => $tcpdf !== null ? 'tcpdf' : 'none',
        'tools' => map_is_tools_host(),
    ]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    map_pdf_respond_json(405, ['ok' => false, 'error' => 'Method not allowed']);
}

$payload = [];
$contentType = strtolower((string)($_SERVER['CONTENT_TYPE'] ?? ''));
if (strpos($contentType, 'application/json') !== false) {
    $rawBody = file_get_contents('php://input');
    $decoded = json_decode((string)$rawBody, true);
    if (is_array($decoded)) {
        $payload = $decoded;
    }
} else {
    $payload = $_POST;
}

$imageRaw = (string)($payload['image'] ?? $payload['jpeg'] ?? '');
$filename = (string)($payload['filename'] ?? '');
$title = trim((string)($payload['title'] ?? 'Kartenansicht'));
// Erlaubt: YYYYMMDD-Titel der Seite.pdf (Leerzeichen + Umlaute ok)
if ($filename === '' || !preg_match('/^[0-9]{8}-.+\.pdf$/u', $filename) || preg_match('/[\/\\\\:*?"<>|\x00-\x1F]/', $filename)) {
    $safeTitle = preg_replace('/[\/\\\\:*?"<>|]+/u', ' ', $title);
    $safeTitle = trim(preg_replace('/\s+/u', ' ', (string)$safeTitle));
    if ($safeTitle === '') {
        $safeTitle = 'Karte';
    }
    if (function_exists('mb_substr')) {
        $safeTitle = mb_substr($safeTitle, 0, 80, 'UTF-8');
    } else {
        $safeTitle = substr($safeTitle, 0, 80);
    }
    $filename = date('Ymd') . '-' . $safeTitle . '.pdf';
}
if (strlen($title) > 120) {
    $title = substr($title, 0, 120);
}

try {
    $img = map_pdf_decode_image($imageRaw);
} catch (Throwable $e) {
    map_pdf_respond_json(400, ['ok' => false, 'error' => $e->getMessage()]);
}

$tcpdfPath = map_pdf_find_tcpdf();
if ($tcpdfPath === null) {
    map_pdf_respond_json(503, [
        'ok' => false,
        'error' => 'TCPDF nicht gefunden. Bitte tcpdf_path.php setzen oder TCPDF unter vendor/tcpdf ablegen.',
        'hint' => 'Auf tools.rehkitzrettung.ch TCPDF-Pfad in map/tcpdf_path.php als return \"/absoluter/pfad/tcpdf.php\";',
    ]);
}

require_once $tcpdfPath;

$tmp = tempnam(sys_get_temp_dir(), 'rkrkmap');
if ($tmp === false) {
    map_pdf_respond_json(500, ['ok' => false, 'error' => 'Temp-Datei fehlgeschlagen.']);
}
$ext = ($img['format'] === 'PNG') ? '.png' : '.jpg';
$tmpImg = $tmp . $ext;
@unlink($tmp);
if (file_put_contents($tmpImg, $img['bytes']) === false) {
    map_pdf_respond_json(500, ['ok' => false, 'error' => 'Temp-Bild schreiben fehlgeschlagen.']);
}

try {
    // Landscape if wider than tall
    $orient = ($img['width'] >= $img['height']) ? 'L' : 'P';
    $pdf = new TCPDF($orient, 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator(map_overlay_editor_title());
    $pdf->SetAuthor(map_is_rkrs_env() ? 'rehkitzrettung.ch' : 'drohnenrehkitzrettung.ch');
    $pdf->SetTitle($title !== '' ? $title : 'Kartenansicht');
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(8, 8, 8);
    $pdf->SetAutoPageBreak(false, 0);
    $pdf->AddPage();

    $printWmm = isset($payload['printWidthMm']) ? (float)$payload['printWidthMm'] : 0.0;
    $printHmm = isset($payload['printHeightMm']) ? (float)$payload['printHeightMm'] : 0.0;
    $pageWfull = $pdf->getPageWidth();
    $pageHfull = $pdf->getPageHeight();
    if ($printWmm >= 10.0 && $printHmm >= 10.0) {
        $imgWmm = $printWmm;
        $imgHmm = $printHmm;
        $x = ($pageWfull - $imgWmm) / 2;
        $y = ($pageHfull - $imgHmm) / 2;
    } else {
        $pageW = $pageWfull - 16;
        $pageH = $pageHfull - 16;
        $imgWmm = $pageW;
        $imgHmm = $pageW * ($img['height'] / max(1, $img['width']));
        if ($imgHmm > $pageH) {
            $imgHmm = $pageH;
            $imgWmm = $pageH * ($img['width'] / max(1, $img['height']));
        }
        $x = 8 + ($pageW - $imgWmm) / 2;
        $y = 8 + ($pageH - $imgHmm) / 2;
    }
    $pdf->Image($tmpImg, $x, $y, $imgWmm, $imgHmm, $img['format'], '', '', false, 150, '', false, false, 0, false, false, false);

    $scaleLabel = trim((string)($payload['scale'] ?? ''));
    if ($scaleLabel !== '' && preg_match("/^1:[0-9']+$/u", $scaleLabel) && ($y + $imgHmm + 7) <= $pageHfull) {
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetTextColor(17, 24, 39);
        $pdf->SetXY(8, $y + $imgHmm + 1);
        $pdf->Cell($pageWfull - 16, 4, 'Massstab ' . $scaleLabel . '  ·  A4  ·  100 % drucken', 0, 0, 'C', false, '', 0, false, 'T', 'M');
    }

    // Output download
    if (ob_get_length()) {
        @ob_end_clean();
    }
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    $pdf->Output($filename, 'D');
} catch (Throwable $e) {
    @unlink($tmpImg);
    map_pdf_respond_json(500, ['ok' => false, 'error' => 'TCPDF: ' . $e->getMessage()]);
}

@unlink($tmpImg);
exit;
