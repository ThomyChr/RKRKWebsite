<?php
declare(strict_types=1);

require_once __DIR__ . '/map_env.php';
$__rkrkToolsAuth = __DIR__ . '/../rkrk_tools_auth.php';
if (is_file($__rkrkToolsAuth)) {
    require_once $__rkrkToolsAuth;
} elseif (is_file(__DIR__ . '/rkrk_tools_auth.php')) {
    require_once __DIR__ . '/rkrk_tools_auth.php';
}
unset($__rkrkToolsAuth);

/**
 * Environment-based mode (kept in sync with rkrk_env_branding.js / map_env.php):
 * Mode A (RKRS): tools.rehkitzrettung.ch OR path contains /rkrs/
 * Mode B (RKRK): everything else
 */
$OnRKRS = map_is_rkrs_env();
$productTitle = function_exists('map_overlay_editor_title')
    ? map_overlay_editor_title()
    : ($OnRKRS ? 'RKRS Overlay-Editor' : 'RKRK Overlay-Editor');
$productTitleEsc = htmlspecialchars($productTitle, ENT_QUOTES, 'UTF-8');

$indexPath = __DIR__ . '/map_editor.html';
if (!is_file($indexPath)) {
    $indexPath = __DIR__ . '/index.html';
}
if (is_file($indexPath)) {
    header('Content-Type: text/html; charset=utf-8');
    $html = file_get_contents($indexPath);
    if ($html === false) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Failed to read map editor HTML.';
        exit;
    }
    if (function_exists('rkrk_tools_inject_context_menu_into_html')) {
        $html = rkrk_tools_inject_context_menu_into_html($html);
    }

    $html = preg_replace(
        '/<title>[^<]*<\/title>/i',
        '<title>' . $productTitleEsc . '</title>',
        $html,
        1
    ) ?? $html;
    $html = preg_replace(
        '/<h1\b[^>]*>[^<]*<\/h1>/i',
        '<h1>' . $productTitleEsc . '</h1>',
        $html,
        1
    ) ?? $html;

    // Strip default icons; inject site favicon (test27 orange, RKRS, …) then JS keeps it.
    $html = preg_replace(
        '/<link[^>]+rel=["\'](?:shortcut )?icon["\'][^>]*>\s*/i',
        '',
        $html,
        -1
    );
    $html = preg_replace(
        '/<link[^>]+rel=["\']apple-touch-icon["\'][^>]*>\s*/i',
        '',
        $html,
        -1
    );
    $themeName = function_exists('rkrk_ci_theme_name') ? rkrk_ci_theme_name() : ($OnRKRS ? 'rkrs' : 'rkrk');
    $themeEsc = htmlspecialchars($themeName, ENT_QUOTES, 'UTF-8');
    if (preg_match('/<html\b[^>]*>/i', $html)) {
        if (preg_match('/data-rkrk-theme=/i', $html)) {
            $html = preg_replace('/data-rkrk-theme=["\'][^"\']*["\']/', 'data-rkrk-theme="' . $themeEsc . '"', $html, 1);
        } else {
            $html = preg_replace('/<html\b/i', '<html data-rkrk-theme="' . $themeEsc . '"', $html, 1);
        }
    }
    if (function_exists('map_is_tools_host') && map_is_tools_host()) {
        $faviconHref = map_brand_asset_url('RKRS.ico', '20260907tools');
        $faviconTags = '<link rel="icon" type="image/x-icon" href="' . htmlspecialchars($faviconHref, ENT_QUOTES, 'UTF-8') . '" />' . "\n"
            . '  <link rel="shortcut icon" type="image/x-icon" href="' . htmlspecialchars($faviconHref, ENT_QUOTES, 'UTF-8') . '" />' . "\n";
        if (function_exists('rkrk_favicon_js_bootstrap')) {
            $faviconTags .= rkrk_favicon_js_bootstrap();
        }
    } elseif (function_exists('rkrk_favicon_link_tags_html')) {
        $faviconTags = rkrk_favicon_link_tags_html();
        if (function_exists('rkrk_favicon_js_bootstrap')) {
            $faviconTags .= rkrk_favicon_js_bootstrap();
        }
    } else {
        $faviconFile = $OnRKRS ? 'RKRS.ico' : 'rkrk.ico';
        $faviconHref = map_brand_asset_url($faviconFile, '20260722g');
        $faviconTags = '<link rel="icon" type="image/x-icon" href="' . htmlspecialchars($faviconHref, ENT_QUOTES, 'UTF-8') . '" />' . "\n"
            . '  <link rel="shortcut icon" type="image/x-icon" href="' . htmlspecialchars($faviconHref, ENT_QUOTES, 'UTF-8') . '" />' . "\n";
        if (!$OnRKRS) {
            $pngHref = map_brand_asset_url('rkrk-favicon-32x32.png', '20260722g');
            $appleHref = map_brand_asset_url('rkrk-apple-icon-180x180.png', '20260722g');
            $faviconTags .= '  <link rel="icon" type="image/png" sizes="32x32" href="' . htmlspecialchars($pngHref, ENT_QUOTES, 'UTF-8') . '" />' . "\n"
                . '  <link rel="apple-touch-icon" sizes="180x180" href="' . htmlspecialchars($appleHref, ENT_QUOTES, 'UTF-8') . '" />' . "\n";
        }
    }
    if (strpos($html, '<head>') !== false) {
        $html = preg_replace('/<head>/i', "<head>\n  " . $faviconTags, $html, 1);
    }

    // Ensure branding script is present early (also linked from index.html).
    $brandingTag = '<script src="rkrk_env_branding.js?v=20260912title"></script>' . "\n";
    if (stripos($html, 'rkrk_env_branding.js') === false) {
        if (strpos($html, '</head>') !== false) {
            $html = str_replace('</head>', $brandingTag . '</head>', $html);
        }
    } else {
        $html = preg_replace(
            '/src=["\']rkrk_env_branding\.js(?:\?[^"\']*)?["\']/',
            'src="rkrk_env_branding.js?v=20260912title"',
            $html,
            1
        );
    }

    $fhFlags = function_exists('map_flighthub_public_flags')
        ? map_flighthub_public_flags()
        : ['configured' => false];
    $fhJson = json_encode($fhFlags, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($fhJson === false) {
        $fhJson = '{"configured":false}';
    }
    $modeScriptTag = '<script>window.__ON_RKRS__=' . ($OnRKRS ? 'true' : 'false')
        . ';window.__RKRK_FLIGHTHUB__=' . $fhJson . ';</script>' . "\n";
    $featureScriptTag = '<script src="rkrk_map_obj_features.js?v=20261002labeldbl3"></script>' . "\n"
        . '<script src="rkrk_map_multiedit.js?v=20260916rename1"></script>' . "\n"
        . '<script src="rkrk_map_announce.js?v=20260825djihint1"></script>' . "\n"
        . '<script src="rkrk_map_viewfollow.js?v=20260825djihint1"></script>' . "\n";
    $uiTuneScriptTag = "<script>(function(){\n"
        . "  function tune(){\n"
        . "    var circlesBox=document.getElementById('objectParamsCircles');\n"
        . "    if(!circlesBox||!circlesBox.previousElementSibling){return false;}\n"
        . "    var subtitle=circlesBox.previousElementSibling;\n"
        . "    subtitle.textContent='Parameter für den gewählten Objekttyp';\n"
        . "    subtitle.style.fontSize='10px';\n"
        . "    subtitle.style.fontWeight='600';\n"
        . "    subtitle.style.lineHeight='1.2';\n"
        . "    subtitle.style.marginBottom='4px';\n"
        . "    return true;\n"
        . "  }\n"
        . "  if(!tune()){var retries=0;var timer=setInterval(function(){if(tune()||retries>40){clearInterval(timer);}retries+=1;},120);}\n"
        . "})();</script>\n";

    // Theme also applied client-side via body.rkr-brand-rkrs; keep PHP inject for early paint.
    $greenThemeTag = $OnRKRS
        ? "<style>\n"
            . "aside.panel button{background:#96B588!important;color:#fff!important;border:1px solid #527642!important;}\n"
            . "aside.panel button:hover{background:#7ea56f!important;}\n"
            . "aside.panel button.selected-flight-reference,aside.panel button:active{background:#48673A!important;border-color:#3f5933!important;color:#fff!important;}\n"
            . "</style>\n"
        : '';
    $topMenuTag = '';
    if (strpos($html, '</head>') !== false && $greenThemeTag !== '') {
        $html = str_replace('</head>', $greenThemeTag . '</head>', $html);
    }
    if (strpos($html, '<body>') !== false) {
        $html = str_replace('<body>', '<body>' . "\n" . $topMenuTag, $html);
    }
    if (strpos($html, '</body>') !== false) {
        $html = str_replace('</body>', $modeScriptTag . $featureScriptTag . $uiTuneScriptTag . '</body>', $html);
    } else {
        $html .= $modeScriptTag . $featureScriptTag . $uiTuneScriptTag;
    }
    echo $html;
    exit;
}

http_response_code(500);
header('Content-Type: text/plain; charset=utf-8');
echo 'map editor HTML not found (map_editor.html / index.html).';
