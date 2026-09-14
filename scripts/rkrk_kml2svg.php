<?php
// rkrk_kml2svg.php  – Drop-in Replacement, nur Browser/Canvas, kein Imagick

// -------------------------------------------------------------
// 1. KML-Farben: AABBGGRR → [r,g,b,opacity]
// -------------------------------------------------------------
function kmlColorToRgb(string $kmlColor): array {
    $kmlColor = trim($kmlColor);
    if (strlen($kmlColor) !== 8) return [0,0,0,1];
    $a = hexdec(substr($kmlColor,0,2));
    $b = hexdec(substr($kmlColor,2,2));
    $g = hexdec(substr($kmlColor,4,2));
    $r = hexdec(substr($kmlColor,6,2));
    return [$r,$g,$b,$a/255];
}

// -------------------------------------------------------------
// 2. LV95 Bounding Box → Lon/Lat (falls Funktion vorhanden)
// -------------------------------------------------------------
function convertLV95BoundingBox($east, $north, $zoom) {
    // Zoom → Radius in Metern (Feinjustierung möglich)
    $radius = 5000 / pow(2, max(1, (float)$zoom));

    if (function_exists('GetLonLatFromLSV95EastNorth')) {
        $p1 = GetLonLatFromLSV95EastNorth($east - $radius, $north - $radius);
        $p2 = GetLonLatFromLSV95EastNorth($east + $radius, $north + $radius);
        return [$p1["Lon"], $p2["Lon"], $p1["Lat"], $p2["Lat"]];
    }

    // Fallback: Dummy‑Box, damit es nicht crasht
    return [0, 1, 0, 1];
}

// -------------------------------------------------------------
// 3. KML FILE → SVG STRING
// -------------------------------------------------------------
function kml_to_svg(
    string $kmlFile,
    string $svgFile,
    int $width = 2000,
    int $height = 2000,
    int $fontSize = 28,
    float $verticalExaggeration = 200.0,
    $BBoxEast = null,
    $BBoxNorth = null,
    $BBoxZoom = null
): array {

    if (!file_exists($kmlFile)) return [];

    $dom = new DOMDocument();
    $dom->load($kmlFile);
    $xp = new DOMXPath($dom);

    // ---------- Styles ----------
    $styles = [];

    foreach ($xp->query("//*[local-name()='Style']") as $styleNode) {
        $id = $styleNode->getAttribute('id');
        if ($id === '') continue;

        $styles[$id] = [
            'lineColor'=>null,
            'lineWidth'=>2,
            'polyColor'=>null,
            'polyFill'=>true
        ];

        $lineColorNode = $xp->query(".//*[local-name()='LineStyle']/*[local-name()='color']", $styleNode)->item(0);
        $lineWidthNode = $xp->query(".//*[local-name()='LineStyle']/*[local-name()='width']", $styleNode)->item(0);
        $polyColorNode = $xp->query(".//*[local-name()='PolyStyle']/*[local-name()='color']", $styleNode)->item(0);
        $polyFillNode  = $xp->query(".//*[local-name()='PolyStyle']/*[local-name()='fill']",  $styleNode)->item(0);

        if ($lineColorNode) $styles[$id]['lineColor'] = trim($lineColorNode->nodeValue);
        if ($lineWidthNode) $styles[$id]['lineWidth'] = (float)trim($lineWidthNode->nodeValue);
        if ($polyColorNode) $styles[$id]['polyColor'] = trim($polyColorNode->nodeValue);
        if ($polyFillNode)  $styles[$id]['polyFill']  = (trim($polyFillNode->nodeValue) === '1');
    }

    // StyleMap → normal
    foreach ($xp->query("//*[local-name()='StyleMap']") as $mapNode) {
        $id = $mapNode->getAttribute('id');
        if ($id === '') continue;

        foreach ($xp->query(".//*[local-name()='Pair']", $mapNode) as $pair) {
            $keyNode  = $xp->query("./*[local-name()='key']", $pair)->item(0);
            $urlNode  = $xp->query("./*[local-name()='styleUrl']", $pair)->item(0);
            if (!$keyNode || !$urlNode) continue;
            if (trim($keyNode->nodeValue) !== 'normal') continue;

            $ref = ltrim(trim($urlNode->nodeValue), '#');
            if (isset($styles[$ref])) {
                $styles[$id] = $styles[$ref];
            }
        }
    }

    // ---------- Geometrie ----------
    $geometries = [];
    $allLon = [];
    $allLat = [];

    $parseCoords = function(string $str) use (&$allLon,&$allLat) {
        $out = [];
        $str = trim($str);
        $str = preg_replace('/\s+/', ' ', $str);
        $str = preg_replace('/,\s+/', ',', $str);
        $tokens = preg_split('/\s+/', $str);
        foreach ($tokens as $t) {
            if ($t === '') continue;
            $c = explode(',', $t);
            if (count($c) < 2) continue;
            $lon = (float)$c[0];
            $lat = (float)$c[1];
            $out[] = [$lon, $lat];
            $allLon[] = $lon;
            $allLat[] = $lat;
        }
        return $out;
    };

    foreach ($xp->query("//*[local-name()='Placemark']") as $pm) {
        $nameNode  = $xp->query("./*[local-name()='name']", $pm)->item(0);
        $styleNode = $xp->query("./*[local-name()='styleUrl']", $pm)->item(0);

        $label = $nameNode ? trim($nameNode->nodeValue) : '';
        $styleId = $styleNode ? ltrim(trim($styleNode->nodeValue), '#') : null;
        $style = $styleId && isset($styles[$styleId]) ? $styles[$styleId] : null;

        $coordNode = $xp->query(".//*[local-name()='Point']/*[local-name()='coordinates']", $pm)->item(0);
        if ($coordNode) {
            $coords = $parseCoords($coordNode->nodeValue);
            if ($coords) $geometries[] = ['type'=>'point','coords'=>$coords,'label'=>$label,'style'=>$style];
        }

        $coordNode = $xp->query(".//*[local-name()='LineString']/*[local-name()='coordinates']", $pm)->item(0);
        if ($coordNode) {
            $coords = $parseCoords($coordNode->nodeValue);
            if ($coords) $geometries[] = ['type'=>'linestring','coords'=>$coords,'label'=>$label,'style'=>$style];
        }

        $coordNode = $xp->query(".//*[local-name()='Polygon']/*[local-name()='outerBoundaryIs']/*[local-name()='LinearRing']/*[local-name()='coordinates']", $pm)->item(0);
        if ($coordNode) {
            $coords = $parseCoords($coordNode->nodeValue);
            if ($coords) $geometries[] = ['type'=>'polygon','coords'=>$coords,'label'=>$label,'style'=>$style];
        }
    }

    if (!$geometries) return [];

    // ---------- Bounding Box ----------
    if (!empty($BBoxEast) && !empty($BBoxNorth) && !empty($BBoxZoom)) {
        list($minLon, $maxLon, $minLat, $maxLat) = convertLV95BoundingBox(
            $BBoxEast,
            $BBoxNorth,
            $BBoxZoom
        );
    } else {
        $minLon = min($allLon);
        $maxLon = max($allLon);
        $minLat = min($allLat);
        $maxLat = max($allLat);
    }

    $lonRange = max($maxLon - $minLon, 1e-9);
    $latRange = max($maxLat - $minLat, 1e-9);

    // ---------- Scaling ----------
    $scaleX = ($width * 0.90) / $lonRange;
    $scaleY = ($height * 0.90) / ($latRange / $verticalExaggeration);

    $offsetX = ($width  - $lonRange * $scaleX) / 2;
    $offsetY = ($height - ($latRange / $verticalExaggeration) * $scaleY) / 2;

    $project = function(float $lon, float $lat) use ($minLon,$maxLat,$scaleX,$scaleY,$offsetX,$offsetY,$verticalExaggeration) {
        return [
            ($lon - $minLon) * $scaleX + $offsetX,
            (($maxLat - $lat) / $verticalExaggeration) * $scaleY + $offsetY
        ];
    };

    // ---------- SVG ----------
    $svg = [];
    $svg[] = '<?xml version="1.0" encoding="UTF-8"?>';
    $svg[] = '<svg xmlns="http://www.w3.org/2000/svg" width="'.$width.'" height="'.$height.'">';
    $svg[] = '<rect width="100%" height="100%" fill="white"/>';

    foreach ($geometries as $g) {
        $coords = $g['coords'];
        $label  = $g['label'];
        $style  = $g['style'];

        $stroke = "#0000ff";
        $strokeWidth = 2;
        $fill = "#008000";
        $fillOpacity = 0.3;

        if ($style) {
            if ($style['lineColor']) {
                [$r,$g2,$b,$op] = kmlColorToRgb($style['lineColor']);
                $stroke = sprintf("#%02x%02x%02x", $r,$g2,$b);
                $strokeWidth = $style['lineWidth'];
            }
            if ($style['polyColor']) {
                [$r,$g2,$b,$op] = kmlColorToRgb($style['polyColor']);
                $fill = sprintf("#%02x%02x%02x", $r,$g2,$b);
                $fillOpacity = $op;
            }
        }

        if ($g['type'] === 'point') {
            [$x,$y] = $project($coords[0][0], $coords[0][1]);
            $svg[] = '<circle cx="'.$x.'" cy="'.$y.'" r="6" fill="'.$stroke.'"/>';
            if ($label !== '') {
                $svg[] = '<text x="'.($x+8).'" y="'.($y-8).'" font-size="'.$fontSize.'" fill="black">'
                       . htmlspecialchars($label,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8')
                       . '</text>';
            }
        }

        if ($g['type'] === 'linestring') {
            $pts = [];
            foreach ($coords as [$lon,$lat]) {
                [$x,$y] = $project($lon,$lat);
                $pts[] = "$x,$y";
            }
            $svg[] = '<polyline points="'.implode(' ',$pts).'" fill="none" stroke="'.$stroke.'" stroke-width="'.$strokeWidth.'"/>';
        }

        if ($g['type'] === 'polygon') {
            $pts = [];
            foreach ($coords as [$lon,$lat]) {
                [$x,$y] = $project($lon,$lat);
                $pts[] = "$x,$y";
            }
            $svg[] = '<polygon points="'.implode(' ',$pts).'" fill="'.$fill.'" fill-opacity="'.$fillOpacity.'" stroke="'.$stroke.'" stroke-width="'.$strokeWidth.'"/>';
        }
    }

    $svg[] = '</svg>';
    $svgString = implode("\n", $svg);

    file_put_contents($svgFile, $svgString);

    return [
        'svg' => $svgString,
        'width'=>$width,
        'height'=>$height
    ];
}

// -------------------------------------------------------------
// 4. KML TEXT → SVG (Tempfile, gleiche Pipeline)
// -------------------------------------------------------------
function kml_text_to_svg(
    string $kmlText,
    string $outputBase,
    int $width = 2000,
    int $height = 2000,
    int $fontSize = 28,
    float $verticalExaggeration = 200.0
): array {

    $dom = new DOMDocument();
    $prev = libxml_use_internal_errors(true);
    $ok = $dom->loadXML($kmlText);
    libxml_clear_errors();
    libxml_use_internal_errors($prev);
    if (!$ok) {
        return [];
    }

    $tmpFile = sys_get_temp_dir() . "/kml_" . uniqid() . ".kml";
    file_put_contents($tmpFile, $kmlText);

    $BBoxEast  = $GLOBALS["BBoxEast"]  ?? null;
    $BBoxNorth = $GLOBALS["BBoxNorth"] ?? null;
    $BBoxZoom  = $GLOBALS["BBoxZoom"]  ?? null;

    $result = kml_to_svg(
        $tmpFile,
        $outputBase . ".svg",
        $width,
        $height,
        $fontSize,
        $verticalExaggeration,
        $BBoxEast,
        $BBoxNorth,
        $BBoxZoom
    );

    unlink($tmpFile);
    return $result;
}

// -------------------------------------------------------------
// 5. KML TEXT → SVG + PNG + JPG (Browser, selektiv)
// -------------------------------------------------------------
function kml_to_all_text(
    string $kmlText,
    string $outputBase,
    int $size=2000,
    int $fontSize=28,
    float $verticalExaggeration = 200.0,
    bool $MakeSVG = true,
    bool $MakePNG = false,
    bool $MakeJPG = false
): bool {

    $data = kml_text_to_svg(
        $kmlText,
        $outputBase,
        $size,
        $size,
        $fontSize,
        $verticalExaggeration
    );

    if (!$data) return false;

    echo '<canvas id="canvas" width="'.$data['width'].'" height="'.$data['height'].'" style="display:none;"></canvas>';

    $svgJS = addslashes($data['svg']);

    echo <<<HTML
<script>
const svgString = `$svgJS`;

function safeName(name) {
    return name.replace(/[()]/g,"").replace(/\\s+/g,"_").replace(/[^A-Za-z0-9_\\-]/g,"");
}

function renderSVG(callback) {
    const canvas=document.getElementById("canvas");
    const ctx=canvas.getContext("2d");
    const blob=new Blob([svgString],{type:"image/svg+xml"});
    const url=URL.createObjectURL(blob);
    const img=new Image();
    img.onload=()=>{ctx.clearRect(0,0,canvas.width,canvas.height);ctx.drawImage(img,0,0);URL.revokeObjectURL(url);callback(canvas);};
    img.src=url;
}

function autoDownload() {
    const base = safeName("$outputBase");

    if ($MakeSVG) {
        const svgBlob = new Blob([svgString], {type:"image/svg+xml"});
        const svgUrl = URL.createObjectURL(svgBlob);
        const dlSvg = document.createElement("a");
        dlSvg.href = svgUrl;
        dlSvg.download = base + ".svg";
        dlSvg.click();
    }

    renderSVG(canvas => {

        if ($MakePNG) {
            const png = canvas.toDataURL("image/png");
            const dl1 = document.createElement("a");
            dl1.href = png;
            dl1.download = base + ".png";
            dl1.click();
        }

        if ($MakeJPG) {
            const jpg = canvas.toDataURL("image/jpeg", 0.9);
            const dl2 = document.createElement("a");
            dl2.href = jpg;
            dl2.download = base + ".jpg";
            dl2.click();
        }
    });
}

window.onload=autoDownload;
</script>
HTML;

    return true;
}

// -------------------------------------------------------------
// 6. MAIN – nur bei direktem Aufruf von rkrk_kml2svg.php
// -------------------------------------------------------------
if (isset($_SERVER['SCRIPT_FILENAME'])
	&& strcasecmp(basename((string)$_SERVER['SCRIPT_FILENAME']), 'rkrk_kml2svg.php') === 0) {

// BBox aus GET übernehmen (von rkrk_dokml.php durchgereicht)
$BBoxEast  = isset($_GET['bbox_east'])  ? (float)$_GET['bbox_east']  : null;
$BBoxNorth = isset($_GET['bbox_north']) ? (float)$_GET['bbox_north'] : null;
$BBoxZoom  = isset($_GET['bbox_zoom'])  ? (float)$_GET['bbox_zoom']  : null;
$GLOBALS["BBoxEast"]  = $BBoxEast;
$GLOBALS["BBoxNorth"] = $BBoxNorth;
$GLOBALS["BBoxZoom"]  = $BBoxZoom;

// Formatwahl (wie in rkrk_dokml.php)
$MakeSVG = isset($_GET['SVG']) ? (strtolower($_GET['SVG']) === 'on') : true;
$MakePNG = isset($_GET['PNG']) ? (strtolower($_GET['PNG']) === 'on') : false;
$MakeJPG = isset($_GET['JPG']) ? (strtolower($_GET['JPG']) === 'on') : false;

// Basisname für Dateien
$baseName = isset($_GET['File']) && $_GET['File'] !== ''
    ? $_GET['File']
    : 'RKRK_Map';

// KML direkt von rkrk_dokml.php holen (gleicher Query-String)
$query = isset($_SERVER['QUERY_STRING']) ? (string)$_SERVER['QUERY_STRING'] : '';
// sicherstellen, dass wir nicht in eine Endlosschleife laufen:
$query = preg_replace('/(&|\?)SVG=on|(&|\?)PNG=on|(&|\?)JPG=on/i','',$query);

$kmlUrl = 'rkrk_dokml.php?' . $query;

// KML laden
$kmlText = @file_get_contents($kmlUrl);

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>RKRK KML → SVG/PNG/JPG</title>
</head>
<body>
<?php
if ($kmlText === false || trim($kmlText) === '') {
    echo "<p>Kein KML empfangen von: ".htmlspecialchars($kmlUrl)."</p>";
} else {
    echo "<p>KML geladen von: ".htmlspecialchars($kmlUrl)."</p>";
    kml_to_all_text(
        $kmlText,
        $baseName,
        2000,
        28,
        200.0,
        $MakeSVG,
        $MakePNG,
        $MakeJPG
    );
}
?>
</body>
</html>
<?php
} // end direct-call guard
