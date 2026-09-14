<?php
require_once __DIR__ . "/qr.php";   // new QR class

function qr_svg($text, string $moduleColor = '#000000', string $imgCss = 'width:40mm;height:40mm;display:block;') {
    $svg = QR::svg($text, 6, 2, $moduleColor);
    $data = base64_encode($svg);
    $cssEsc = htmlspecialchars($imgCss, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    return '<img src="data:image/svg+xml;base64,' . $data . '" style="' . $cssEsc . '" alt="">';
}
