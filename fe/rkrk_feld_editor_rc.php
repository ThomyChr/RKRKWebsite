<?php
declare(strict_types=1);

/**
 * Feldansicht für die DJI-Fernsteuerung.
 *
 * Eigenständige Seite, parallel zu rkrk_feld_editor.php / field_editor.php.
 * Gleicher Feldkatalog über api_field_editor.php, eigene Oberfläche.
 *
 * Zielgeräte, beide quer:
 * - DJI RC: 1920×1080 auf etwa 10×7 cm. Sehr dicht, deshalb grosse Schrift und Tasten.
 * - DJI RC Plus 2: 7 Zoll. Dieselbe Bedienung, etwas mehr Luft durch die Viewport-Skalierung.
 *
 * Neues Feld: Fläche tippen, Startpunkt setzen, Name, speichern. Die Route rechnet der Server.
 * Bewusst nicht enthalten: Katalogpflege, Wegpunkteditor, Dateien, Kontakte, KML-Rohdaten.
 */

require_once __DIR__ . '/mapll_env.php';
$mapllBrand = mapll_favicon_brand();
$brandLabel = (string)($mapllBrand['label'] ?? 'RKRK');
$rkrkKat = array_key_exists('KAT', $_GET);
$rkrkAdmin = array_key_exists('ADMIN', $_GET) || strcasecmp(trim((string)($_GET['code'] ?? '')), 'ADMIN') === 0;
?>
<!doctype html>
<html <?php echo mapll_html_open_attrs(); ?>>
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover" />
  <meta name="theme-color" content="#121814" />
  <meta name="mobile-web-app-capable" content="yes" />
  <meta name="apple-mobile-web-app-capable" content="yes" />
  <meta name="apple-mobile-web-app-title" content="Feld RC" />
  <link rel="manifest" href="rkrk_feld_editor_rc_manifest.php" />
  <title><?php echo htmlspecialchars($brandLabel . ' Feld', ENT_QUOTES, 'UTF-8'); ?></title>
  <?php echo mapll_favicon_link_tags($mapllBrand); ?>
  <?php echo mapll_theme_css_link(); ?>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ol@7.5.2/ol.css" />
  <link rel="stylesheet" href="rkrk_help.css?v=20261017hilfe6" />
  <style>
    :root {
      --text: 14px;
      --title: 16px;
      --hit: 40px;
      --side: 360px;
      --ink: #f4f7f2;
      --muted: #c5d0c4;
      --bg: #121814;
      --panel: rgba(14, 20, 16, 0.92);
      --line: rgba(255, 255, 255, 0.16);
      --warn: #ffd0c8;
      --warn-bg: #8d1e1e;
    }
    * { box-sizing: border-box; }
    html, body { margin: 0; height: 100%; }
    body {
      font-family: system-ui, Segoe UI, Arial, sans-serif;
      font-size: var(--text);
      line-height: 1.15;
      color: var(--ink);
      background: var(--bg);
      overflow: hidden;
      touch-action: manipulation;
      -webkit-tap-highlight-color: transparent;
    }
    button, input { font: inherit; color: inherit; }
    button {
      width: auto;
      min-height: var(--hit);
      padding: 0 0.7em;
      border: 0;
      border-radius: 0.45em;
      background: var(--brand-dark, #48673A);
      color: #fff;
      cursor: pointer;
    }
    button:disabled { opacity: 0.4; }
    a.quiet {
      color: var(--muted);
      text-decoration: none;
      min-height: var(--hit);
      display: inline-flex;
      align-items: center;
      padding: 0 0.4em;
      white-space: nowrap;
    }
    .view { position: absolute; inset: 0; display: flex; flex-direction: column; }
    .view[hidden] { display: none !important; }
    .top {
      display: flex;
      align-items: center;
      gap: 0.45em;
      padding: calc(env(safe-area-inset-top, 0px) + 0.35em) 0.45em 0.35em;
    }
    .rkr-brand-favicon { width: 1.35em; height: 1.35em; object-fit: contain; flex: 0 0 auto; background: transparent; }
    h1 { margin: 0; font-size: var(--title); font-weight: 750; flex: 1 1 auto; }
    .search-line { display: flex; gap: 0.35em; padding: 0 0.45em 0.4em; }
    #q {
      flex: 1 1 auto;
      min-height: var(--hit);
      padding: 0 0.6em;
      border-radius: 0.45em;
      border: 2px solid var(--line);
      background: #1c2620;
      color: var(--ink);
    }
    #clearQ { flex: 0 0 auto; min-width: var(--hit); padding: 0; }
    .count { padding: 0 0.6em 0.3em; color: var(--muted); font-size: 0.72em; }
    #list { flex: 1 1 auto; overflow: auto; padding: 0 0.4em calc(env(safe-area-inset-bottom, 0px) + 0.4em); }
    .field-btn {
      display: flex;
      flex-direction: column;
      align-items: flex-start;
      justify-content: center;
      width: 100%;
      text-align: left;
      margin: 0 0 0.35em;
      padding: 0.35em 0.7em;
      background: #1c2620;
      border: 2px solid transparent;
    }
    .field-btn.active { border-color: var(--brand, #96B588); background: #24352a; }
    .field-btn .id { font-weight: 750; color: #d5e6cf; }
    .field-btn .name { color: var(--ink); }
    .field-btn .meta { color: var(--muted); font-size: 0.78em; line-height: 1.3; }
    .field-row { display: flex; align-items: stretch; gap: 0.35em; margin: 0 0 0.35em; }
    .field-row .field-btn { flex: 1 1 auto; width: auto; min-width: 0; margin: 0; }
    .field-row .field-delete-btn {
      flex: 0 0 auto;
      align-self: stretch;
      min-width: 4.6em;
      padding: 0 0.45em;
      color: #ffd6d6;
      background: #3a2424;
      border: 2px solid #8a4040;
      border-radius: 0.45em;
      font-weight: 700;
    }
    .field-delete-ask {
      position: fixed;
      inset: 0;
      z-index: 40;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1em;
      background: rgba(0, 0, 0, 0.55);
    }
    .field-delete-ask-box {
      width: min(24em, 100%);
      padding: 0.9em;
      border: 1px solid var(--line);
      border-radius: 0.5em;
      background: var(--panel, #1c2620);
      color: var(--ink);
    }
    .field-delete-ask-box p { margin: 0 0 0.8em; white-space: pre-wrap; line-height: 1.4; }
    .field-delete-ask-actions { display: flex; justify-content: flex-end; gap: 0.4em; }
    .field-delete-cancel { background: #1c2620; color: var(--ink); border: 2px solid var(--line); }
    .field-delete-confirm { background: #6b2424; color: #ffd6d6; border: 2px solid #8a4040; font-weight: 700; }
    .empty { padding: 0.8em; color: var(--muted); }
    #map { position: absolute; inset: 0; }
    .ol-zoom, .ol-rotate, .ol-attribution { display: none; }
    .map-chrome, .tools { position: absolute; z-index: 2; }
    .map-chrome {
      top: 0;
      left: 0;
      bottom: 0;
      width: var(--side);
      display: flex;
      flex-direction: column;
      background: var(--panel);
      border-right: 1px solid var(--line);
    }
    .map-chrome.is-collapsed { width: 0; border-right: 0; overflow: visible; }
    .map-chrome.is-collapsed .chrome-body,
    .map-chrome.is-collapsed .chrome-resize { display: none; }
    .chrome-body {
      flex: 1 1 auto;
      min-height: 0;
      overflow: auto;
      display: flex;
      flex-direction: column;
      gap: 0.35em;
      padding: calc(env(safe-area-inset-top, 0px) + 0.35em) 0.4em 0.4em;
    }
    .map-top {
      display: flex;
      flex-direction: column;
      align-items: stretch;
      gap: 0.35em;
    }
    #chromeToggle {
      position: absolute;
      left: 100%;
      top: calc(env(safe-area-inset-top, 0px) + 0.35em);
      min-width: 36px;
      min-height: 36px;
      padding: 0;
      border-radius: 0 0.4em 0.4em 0;
      z-index: 3;
    }
    .map-chrome.is-collapsed #chromeToggle { left: 0; }
    .chrome-resize {
      position: absolute;
      top: 0;
      right: -6px;
      width: 12px;
      height: 100%;
      cursor: ew-resize;
      touch-action: none;
    }
    #back { flex: 0 0 auto; }
    .facts { flex: 1 1 auto; min-width: 0; display: flex; flex-direction: column; justify-content: center; }
    .facts .idline { font-weight: 750; font-size: 0.92em; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .facts .idline:empty { display: none; }
    .facts .task {
      color: var(--ink);
      font-size: 0.95em;
      font-weight: 650;
      line-height: 1.25;
      margin-top: 0.15em;
    }
    .facts .task[hidden] { display: none !important; }
    .facts .meta {
      color: var(--muted);
      font-size: 0.78em;
      line-height: 1.25;
    }
    .wp-debug { flex: 0 0 auto; min-width: 0; display: flex; flex-direction: column; gap: 0.15em; color: #1a221c; }
    .wp-debug[hidden] { display: none !important; }
    .wp-debug > summary { font-size: 13px; font-weight: 750; cursor: pointer; color: var(--ink); }
    #wpDebugBtn[hidden] { display: none !important; }
    .wp-debug-scroll {
      flex: 0 0 auto;
      overflow: auto;
      height: clamp(240px, 40vh, 320px);
      min-height: 240px;
      border: 1px solid #c5cfc6;
      border-radius: 0.35em;
      background: #fff;
      color: #1a221c;
    }
    .wp-debug table { border-collapse: collapse; width: 100%; min-width: 640px; table-layout: fixed; font-size: 13px; color: #1a221c; background: #fff; }
    .wp-debug th,
    .wp-debug td,
    .wp-debug td.num { font-size: 13px; color: #1a221c; }
    .wp-debug th {
      position: sticky;
      top: 0;
      z-index: 1;
      background: #eef3ee;
      font-weight: 700;
      text-align: left;
      vertical-align: bottom;
      padding: 0.25em 0.35em;
      white-space: normal;
      line-height: 1.2;
    }
    .wp-debug td { padding: 0.2em 0.35em; border-top: 1px solid #e4e8e4; white-space: normal; overflow-wrap: anywhere; vertical-align: top; background: #fff; }
    .wp-debug td.num { font-family: ui-monospace, Consolas, monospace; text-align: right; }
    .height {
      flex: 0 0 auto;
      display: flex;
      align-items: center;
      font-weight: 750;
      font-size: 1em;
    }
    .warn {
      padding: 0.28em 0.55em;
      background: var(--warn-bg);
      color: var(--warn);
      font-weight: 750;
    }
    .warn[hidden] { display: none !important; }
    .tools {
      right: calc(env(safe-area-inset-right, 0px) + 0.35em);
      bottom: calc(env(safe-area-inset-bottom, 0px) + 0.35em);
      display: flex;
      flex-direction: column;
      align-items: flex-end;
      gap: 0.28em;
    }
    .tool-pair { display: flex; flex-direction: row; align-items: center; gap: 0.28em; }
    #mapLabelSize {
      display: none;
      flex-direction: row;
      align-items: center;
      gap: 0.28em;
    }
    #mapLabelSize[hidden] { display: none !important; }
    @media (min-width: 900px) {
      #mapLabelSize { display: flex; }
    }
    #mapLabelSize button {
      flex: 0 0 auto;
      width: 2.15em;
      min-width: 2.15em;
      min-height: 2.15em;
      padding: 0;
      text-align: center;
      font-weight: 750;
    }
    #rcHelpMapBtn {
      flex: 0 0 var(--hit);
      width: var(--hit);
      min-width: var(--hit);
      height: var(--hit);
      padding: 0;
      border-radius: 999px;
      text-align: center;
      line-height: 1;
    }
    .tools button, .tool-menu button {
      width: auto;
      min-height: var(--hit);
      padding: 0 0.6em;
      font-size: 0.92em;
      text-align: left;
      white-space: nowrap;
    }
    .tools button.off, .tool-menu button.off { opacity: 0.45; }
    .tool-menu {
      position: absolute;
      right: calc(env(safe-area-inset-right, 0px) + 0.35em);
      bottom: calc(env(safe-area-inset-bottom, 0px) + 9.5em);
      z-index: 3;
      display: flex;
      flex-direction: column;
      gap: 0.28em;
      min-width: 12em;
      max-height: calc(100% - env(safe-area-inset-top, 0px) - env(safe-area-inset-bottom, 0px) - 10.2em);
      overflow-x: hidden;
      overflow-y: auto;
      -webkit-overflow-scrolling: touch;
      overscroll-behavior: contain;
      padding: 0.35em;
      border-radius: 0.5em;
      background: rgba(18, 24, 20, 0.94);
      border: 1px solid var(--line);
    }
    .tool-menu button { flex: 0 0 auto; }
    .tool-menu[hidden] { display: none !important; }
    #basemapBar {
      position: absolute;
      z-index: 4;
      left: 50%;
      bottom: env(safe-area-inset-bottom, 0px);
      transform: translateX(-50%);
      display: flex;
      gap: 0.28em;
      padding: 0.35em;
    }
    #basemapBar button {
      min-width: 6.4em;
      text-align: center;
      background: rgba(18, 24, 20, 0.92);
      color: var(--muted);
      border: 2px solid var(--line);
    }
    #basemapBar button.is-on {
      background: var(--brand-dark, #48673A);
      color: #fff;
      border-color: var(--brand, #96B588);
    }
    .tool-title { color: var(--muted); font-size: 0.85em; padding: 0.1em 0.25em; }
    #picHost { position: fixed; left: 0; top: 0; width: 600px; height: 600px; opacity: 0; pointer-events: none; z-index: -1; }
    .height-edit { display: flex; align-items: center; gap: 0.25em; }
    .height-edit[hidden] { display: none !important; }
    .height-edit button { min-width: calc(var(--hit) * 0.7); min-height: calc(var(--hit) * 0.7); padding: 0; }
    #speedVal, #missionAltVal {
      width: 4.2em;
      min-height: calc(var(--hit) * 0.7);
      text-align: center;
      font-weight: 700;
      background: #fff;
      color: #1a2420;
    }
    .height[hidden] { display: none !important; }
    #rcModeToggle.is-expert { background: #c47b2b; color: #fff; }
    .stepbar.is-expert button.is-on .step-no { background: #c47b2b; color: #fff; border-color: transparent; }
    #expertBox {
      display: flex;
      flex-direction: column;
      gap: 0.28em;
      background: #fff;
      color: #1b3552;
      border: 1px solid #d9dde2;
      border-radius: 8px;
      padding: 0.45em;
    }
    #expertBox[hidden] { display: none !important; }
    #rcGimbalWrap {
      display: flex;
      flex-direction: column;
      gap: 0.28em;
    }
    #rcGimbalWrap[hidden] { display: none !important; }
    #rcGimbalWrap label { display: flex; align-items: center; gap: 0.35em; color: #344f6d; }
    #rcGimbalWrap input[type="checkbox"] { width: auto; }
    #rcGimbalWrap input[type="number"] {
      width: 100%;
      min-height: 32px;
      background: #fff;
      color: #1b3552;
      border: 1px solid #d9dde2;
      border-radius: 6px;
    }
    #expertBox .tool-title { color: #c47b2b; font-weight: 750; }
    #expertBox label { display: flex; align-items: center; gap: 0.35em; color: #344f6d; }
    #expertBox input[type="checkbox"] { width: auto; }
    #expertBox select, #expertBox input[type="number"] {
      width: 100%;
      min-height: 32px;
      background: #fff;
      color: #1b3552;
      border: 1px solid #d9dde2;
      border-radius: 6px;
    }
    #expertBox select:disabled, #expertBox input:disabled {
      background: #e8eef4;
      color: #5b6f86;
      opacity: 1;
    }
    #debugTfBox .meta,
    .debug-param { color: #333333; font-style: italic; font-weight: 400; }
    .debug-param { display: block; font-size: 11px; line-height: 1.25; margin: 1px 0 4px; }
    #rcAltRel { color: #5b6f86; }
    #newName {
      width: 100%;
      min-height: calc(var(--hit) * 0.85);
      margin-top: 0.15em;
      padding: 0 0.5em;
      border-radius: 0.4em;
      border: 2px solid var(--line);
      background: #1c2620;
      color: var(--ink);
    }
    #nameBlock[hidden] { display: none !important; }
    #placeBox #newName { background: #fff; color: #1a2420; min-height: 32px; }
    #nextBtn {
      width: 100%;
      margin-top: auto;
      background: #f4f7f2;
      color: #243528;
      font-weight: 750;
    }
    #nextBtn:disabled {
      background: #f4f7f2;
      color: #243528;
    }
    #nextBtn[hidden] { display: none !important; }
    #droneBtn, #droneMapBtn { width: auto; flex: 0 0 auto; padding: 0 0.6em; }
    #droneList { flex: 1 1 auto; overflow: auto; padding: 0 0.4em calc(env(safe-area-inset-bottom, 0px) + 0.4em); }
    #droneMapBtn { width: 100%; }
    .pic-thumbs { display: flex; flex-direction: column; gap: 0.35em; }
    .pic-thumbs[hidden] { display: none !important; }
    .pic-thumbs figure { margin: 0; }
    .pic-thumbs figcaption { color: var(--muted); font-size: 0.85em; }
    .pic-thumbs img { width: 100%; height: auto; border-radius: 0.35em; background: #000; display: block; }
    .facts .meta { font-size: 0.9em; }
    .flag {
      display: inline-block;
      margin-right: 0.4em;
      padding: 0 0.35em;
      border-radius: 0.3em;
      background: #8a5a12;
      color: #fff4d8;
      font-weight: 750;
    }
    .planner-head {
      display: flex;
      align-items: center;
      gap: 0.35em;
      min-width: 0;
    }
    .planner-head h1 {
      margin: 0;
      flex: 1 1 auto;
      min-width: 0;
      font-size: 12px;
      letter-spacing: -0.02em;
      white-space: nowrap;
    }
    .planner-head #back {
      width: auto;
      flex: 0 0 auto;
      min-height: 26px;
      padding: 0 0.35em;
      font-size: 11px;
    }
    .stepbar {
      display: flex;
      gap: 0.1em;
      align-items: flex-start;
    }
    .stepbar[hidden] { display: none !important; }
    .stepbar button {
      flex: 1 1 0;
      min-width: 0;
      min-height: 0;
      padding: 0;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 0.12em;
      background: transparent;
      color: var(--muted);
      border: 0;
      font-size: 0.62em;
      line-height: 1.05;
    }
    .stepbar button .step-no {
      width: 1.55rem;
      height: 1.55rem;
      border-radius: 50%;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border: 1px solid var(--line);
      font-size: 0.95rem;
      font-weight: 750;
    }
    .stepbar button {
      overflow: hidden;
    }
    .stepbar button .step-name {
      display: block;
      width: 100%;
      max-width: 100%;
      overflow: hidden;
      text-align: center;
      font-size: 10px;
      letter-spacing: -0.03em;
      line-height: 1.05;
      white-space: nowrap;
    }
    .stepbar button.is-done { color: var(--ink); }
    .stepbar button.is-done .step-no { border-color: var(--ink); }
    .stepbar button.is-on { color: var(--ink); }
    .stepbar button.is-on .step-no { background: var(--brand-dark, #48673A); color: #fff; border-color: transparent; }
    .step-nav { display: flex; gap: 0.35em; }
    .step-nav button { flex: 1 1 0; }
    .file-nav { display: flex; flex-wrap: wrap; gap: 0.35em; margin-top: 0.35em; }
    .file-nav button { flex: 1 1 30%; }
    .file-nav #saveFilesBtn { flex: 1 1 100%; }
    #fileNav[hidden], #saveFilesBtn[hidden], #jsonDownBtn[hidden] { display: none !important; }
    #prevBtn[hidden], #nextBtn[hidden], #editBtn[hidden], #kmzBtn[hidden], #closeSaveBtn[hidden], #renewPicBtn[hidden], #back[hidden], #localKmlBtn[hidden], #localJsonBtn[hidden] { display: none !important; }
    .extra { display: flex; flex-direction: column; gap: 0.28em; }
    .extra[hidden] { display: none !important; }
    .extra button.is-on { background: var(--brand-dark, #48673A); color: #fff; }
    .wp-strip {
      display: flex;
      align-items: stretch;
      gap: 0.12em;
      background: #0b0f0d;
      border-radius: 0.55em;
      padding: 0.28em 0.15em;
    }
    .wp-strip > .wp-chev {
      flex: 0 0 1.5em;
      min-width: 1.5em;
      min-height: 3.1em;
      padding: 0;
      background: transparent;
      color: #e7eee6;
      font-size: 1.45em;
      font-weight: 700;
      line-height: 1;
    }
    .wp-dots {
      flex: 1 1 auto;
      min-width: 0;
      display: flex;
      gap: 0.05em;
      overflow-x: auto;
      scrollbar-width: none;
    }
    .wp-dots::-webkit-scrollbar { display: none; }
    .wp-dot {
      flex: 0 0 auto;
      width: 3.05em;
      min-height: 0;
      padding: 0.08em 0;
      background: transparent;
      color: #d5ddd4;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 0.16em;
      border-radius: 0.3em;
    }
    .wp-dot .ball {
      width: 1.65em;
      height: 1.65em;
      border-radius: 50%;
      border: 2px solid #9aabc4;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 750;
      font-size: 0.82em;
      line-height: 1;
      color: #e8eef6;
    }
    .extra .wp-strip button.wp-dot.is-on,
    .wp-dot.is-on {
      background: transparent;
      color: #d5ddd4;
    }
    .wp-dot.is-on .ball {
      background: #1f78d1;
      border-color: #1f78d1;
      color: #fff;
    }
    .wp-dot .alt {
      font-size: 0.66em;
      line-height: 1;
      color: #c5d0c4;
      white-space: nowrap;
    }
    #dirRange, #gisInput { width: 100%; }
    #gisInput, #feldIdInput {
      min-height: 32px;
      padding: 0 0.45em;
      border-radius: 0.35em;
      border: 2px solid var(--line);
      background: #fff;
      color: #1a2420;
    }
    #feldIdBlock[hidden], #gisBlock[hidden], #nameBlock[hidden] { display: none !important; }
  </style>
</head>
<body>
  <section id="listView" class="view">
    <div class="top">
      <?php echo mapll_favicon_mark_html($mapllBrand); ?>
      <h1>Feld</h1>
      <button id="droneBtn" type="button">Drohne</button>
      <button id="rcHelpBtn" type="button" data-rkrk-help title="Hilfe zur aktuellen Sicht">?</button>
      <a class="quiet" href="rkrk_feld_editor.php<?php echo $rkrkKat ? '?KAT' : ''; ?>">Grosser Editor</a>
    </div>
    <div class="search-line">
      <button id="newField" type="button">Neu</button>
      <input id="q" type="search" inputmode="search" enterkeyhint="search" autocomplete="off" placeholder="Nummer, Name, JG, Gemeinde, GIS" />
      <button id="clearQ" type="button" aria-label="Suche leeren">×</button>
    </div>
    <div class="count">Neu startet ein Feld. Ein Eintrag öffnet das Feld.</div>
    <div id="count" class="count"></div>
    <div id="list"></div>
  </section>

  <section id="mapView" class="view" hidden>
    <div id="map"></div>
    <div class="map-chrome" id="mapChrome">
      <button id="chromeToggle" type="button" aria-label="Eingaben ein- oder ausblenden">‹</button>
      <div class="chrome-body">
        <div class="map-top">
          <div class="planner-head">
            <?php echo mapll_favicon_mark_html($mapllBrand); ?>
            <h1><?php echo htmlspecialchars($brandLabel . ' RC Feldplaner', ENT_QUOTES, 'UTF-8'); ?></h1>
            <button id="back" type="button">Felder</button>
          </div>
          <button id="editBtn" type="button" hidden>Überarbeiten</button>
          <button id="rcModeToggle" type="button" hidden>Einfacher Modus</button>
          <button id="droneMapBtn" type="button">Drohne</button>
          <div id="stepBar" class="stepbar" hidden></div>
          <div class="facts">
            <div id="idline" class="idline"></div>
            <div id="task" class="task" hidden></div>
            <div id="meta" class="meta"></div>
            <div id="placeBox" class="extra" hidden>
              <div id="placeLine" class="meta"></div>
              <div id="nameBlock" hidden>
                <div class="tool-title">Feldname</div>
                <input id="newName" type="text" enterkeyhint="done" autocomplete="off" placeholder="Feldname" />
              </div>
              <div id="feldIdBlock"<?php echo $rkrkKat ? '' : ' hidden'; ?>>
                <div class="tool-title">Feldkatalognummer</div>
                <input id="feldIdInput" autocomplete="off" placeholder="freie Nummer" />
                <div id="feldIdNote" class="meta">Frei vorgeschlagen. Eine schon vergebene Nummer muss beim Speichern bestätigt werden.</div>
              </div>
              <div id="debugTfBox" hidden>
                <div class="tool-title">TF-Testfeld</div>
                <input id="debugTfId" autocomplete="off" />
                <div id="debugTfDir" class="meta"></div>
                <button id="debugTfBtn" type="button">TF-Testfeld vorbereiten</button>
              </div>
              <div id="gisBlock">
                <div class="tool-title">GIS-Nummer</div>
                <input id="gisInput" inputmode="numeric" autocomplete="off" placeholder="Katalog oder Vorschlag" />
                <div id="gisNote" class="meta"></div>
                <button id="gisApply" type="button" hidden>Vorschlag übernehmen</button>
              </div>
            </div>
          </div>
          <details id="wpDebugTableBox" class="wp-debug" hidden open>
            <summary>Wegpunktdaten</summary>
            <div class="wp-debug-scroll">
              <table id="wpDebugTable">
                <thead>
                  <tr>
                <th>WP<br>Nr</th>
                <th>Höhe WP<br>aus SwissTopo</th>
                <th>Delta zu<br>Starthöhe</th>
                <th>Programmierte<br>Flughöhe</th>
                <th>Flughöhe Relativ<br>zum Startpunkt</th>
                <th>Gier<br>richtung</th>
                <th>Gimbal<br>neigung</th>
                  </tr>
                </thead>
                <tbody id="wpDebugTableBody"></tbody>
              </table>
            </div>
          </details>
          <div id="height" class="height"></div>
          <div id="picThumbs" class="pic-thumbs" hidden></div>
          <button id="renewPicBtn" type="button" hidden>Bilder erneuern</button>
          <div id="heightCaption" class="tool-title" hidden>Flughöhe</div>
          <div id="heightEdit" class="height-edit" hidden>
            <button id="hMinus" type="button" aria-label="Höhe weniger">−</button>
            <span id="hVal">60 m</span>
            <button id="hPlus" type="button" aria-label="Höhe mehr">+</button>
          </div>
          <div id="wpHint" class="meta" hidden>(– m)</div>
          <div id="parkBox" class="extra" hidden>
            <button id="parkBtn" type="button">🅿 Parkplatz setzen</button>
            <button id="parkClear" type="button" hidden>Parkplatz entfernen</button>
            <div id="parkNote" class="meta">Parkplatz nur, wenn er sich vom Startpunkt unterscheidet.</div>
            <button id="poiBtn" type="button">◎ POI setzen</button>
            <button id="poiClear" type="button" hidden>POI entfernen</button>
            <div id="poiNote" class="meta">POI für die Gierrichtung Fokus POI.</div>
          </div>
          <div id="routeTools" class="extra" hidden>
            <button id="covBtn" type="button">◎ Suchabdeckung</button>
            <button id="htBtn" type="button">↕ Höhe auf der Route</button>
            <button id="aglRouteBtn" type="button" class="is-on" title="Roter Punkt, wo die reale Flughöhe über Grund von der Sollflughöhe um mehr als ±10 m abweicht.">● Abweichung von der Sollflughöhe</button>
            <div class="tool-title">Anflug nach dem Start</div>
            <div class="step-nav">
              <button id="entrySecond" type="button">2.</button>
              <button id="entrySecondLast" type="button">vorletzter</button>
              <button id="entryLast" type="button">letzter</button>
            </div>
            <div class="tool-title">Fluggeschwindigkeit</div>
            <div class="height-edit">
              <button id="speedMinus" type="button" aria-label="0,5 m/s weniger">−</button>
              <input id="speedVal" type="number" min="1" max="15" step="0.5" value="4.5" inputmode="decimal" />
              <span>m/s</span>
              <button id="speedPlus" type="button" aria-label="0,5 m/s mehr">+</button>
            </div>
            <div id="startSpeedBox">
              <label><input id="rcStartSpeedActive" type="checkbox" /> Startgeschwindigkeit einschalten</label>
              <div id="rcStartSpeedRow" class="height-edit">
                <input id="rcStartSpeed" type="number" min="0.1" max="15" step="0.1" value="4.5" inputmode="decimal" />
                <span>m/s</span>
              </div>
            </div>
            <div class="tool-title">Flughöhe (Mission)</div>
            <div class="height-edit">
              <button id="missionAltMinus" type="button" aria-label="1 m weniger">−</button>
              <input id="missionAltVal" type="number" min="20" max="120" step="1" value="60" inputmode="numeric" />
              <span>m</span>
              <button id="missionAltPlus" type="button" aria-label="1 m mehr">+</button>
            </div>
            <div class="tool-title">Flugrichtung <span id="dirInline">Vorschlag</span></div>
            <input id="dirRange" type="range" min="0" max="179" step="1" value="0" />
            <button id="dirSuggest" type="button">Zurück zum Vorschlag</button>
          </div>
          <div id="wpBox" class="extra" hidden>
            <div class="wp-strip">
              <button id="wpPrev" class="wp-chev" type="button" aria-label="Vorheriger Wegpunkt">‹</button>
              <div id="wpDots" class="wp-dots"></div>
              <button id="wpNext" class="wp-chev" type="button" aria-label="Nächster Wegpunkt">›</button>
            </div>
          </div>
          <div id="pointBox" class="extra" hidden>
            <div id="pointLabel" class="meta"></div>
            <div class="step-nav">
              <button id="pointAdd" type="button">＋ Punkt</button>
              <button id="pointDelete" type="button">Löschen</button>
            </div>
          </div>
          <div id="expertBox" class="extra" hidden>
            <div class="tool-title">Expertenmodus · dieser Wegpunkt</div>
            <label><input id="rcUseH" type="checkbox" checked /> Verwende Missions-Höhe</label>
            <input id="rcAlt" type="number" min="1" max="500" step="1" />
            <div id="rcAltRel" class="meta"></div>
            <label><input id="rcUseS" type="checkbox" checked /> Verwende Missions-Geschwindigkeit</label>
            <input id="rcSpd" type="number" min="0.1" max="15" step="0.1" />
            <label><input id="rcUseT" type="checkbox" checked /> Route folgen (Wegpunkt-Typ)</label>
            <select id="rcType">
              <option value="Koordinierte Drehung, Ueberspringt den Wegpunkt">Koordinierte Drehung, überspringt den Wegpunkt</option>
              <option value="Gerade Flugroute. Fluggeraet stoppt">Gerade Flugroute. Fluggerät stoppt</option>
              <option value="Gekruemmte Route. Fluggeraet setzt fort">Gekrümmte Route. Fluggerät setzt fort</option>
              <option value="Gekruemmte Route. Fluggeraet stoppt">Gekrümmte Route. Fluggerät stoppt</option>
              <option value="Wendet vor dem Wegpunkt. Fliegt durch">Wendet vor dem Wegpunkt. Fliegt durch</option>
            </select>
            <label><input id="rcUseY" type="checkbox" checked /> Route folgen (Gierrichtung)</label>
            <select id="rcYaw">
              <option value="Manuell">Manuell</option>
              <option value="Richtung zum naechsten Wegpunkt">Richtung zum nächsten Wegpunkt</option>
              <option value="Schenkachse sperren">Schenkachse sperren</option>
              <option value="Fokus POI">Fokus POI</option>
              <option value="Definiert">Definiert</option>
            </select>
            <div id="rcPoiBox" hidden>
              <button id="rcPoiFromPark" type="button">POI am Parkplatz</button>
              <button id="rcPoiOnMap" type="button">POI auf der Karte setzen</button>
              <div id="rcPoiNote" class="meta"></div>
            </div>
            <input id="rcYawAngle" type="number" step="1" min="-180" max="180" />
            <label id="rcUsePWrap" hidden><input id="rcUseP" type="checkbox" checked /> Pfadmodus</label>
            <select id="rcPath">
              <option value="Automatisch">Automatisch</option>
              <option value="Uhrzeigersinn">Uhrzeigersinn</option>
              <option value="Gegen den Uhrzeigersinn">Gegen den Uhrzeigersinn</option>
            </select>
            <label id="rcUseDWrap" hidden><input id="rcUseD" type="checkbox" checked /> Route folgen (Richtungsmodus)</label>
            <input id="rcDir" type="number" step="1" min="-180" max="180" />
          </div>
          <div id="rcGimbalWrap" hidden>
            <label><input id="rcGimbalAngleActive" type="checkbox" /> Neigungswinkel setzen</label>
            <input id="rcGimbalPitch" type="number" step="1" min="-90" max="30" value="-90" disabled />
            <div id="rcGimbalNote" class="meta">Frei, wenn Neigungswinkel setzen an ist. Sonst bleibt −90 und die KMZ schreibt keinen Winkel.</div>
          </div>
        </div>
        <div id="warn" class="warn" hidden></div>
        <div class="step-nav">
          <button id="prevBtn" type="button" hidden>Zurück</button>
          <button id="localKmlBtn" type="button" hidden>KML</button>
          <button id="localJsonBtn" type="button" hidden>JSON</button>
          <button id="closeSaveBtn" type="button" hidden>Abschliessen</button>
          <button id="nextBtn" type="button" hidden>Weiter</button>
        </div>
        <div id="fileNav" class="file-nav" hidden>
          <button id="saveFilesBtn" type="button" hidden>JSON &amp; KMZ speichern</button>
          <button id="jsonDownBtn" type="button" hidden>JSON herunterladen</button>
          <button id="kmzBtn" type="button" hidden>KMZ herunterladen</button>
        </div>
      </div>
      <div id="chromeResize" class="chrome-resize" aria-hidden="true"></div>
    </div>
    <div id="showMenu" class="tool-menu" hidden>
      <div class="tool-title">Anzeige</div>
      <button id="fitBtn" type="button">⌖ Feld einpassen</button>
      <button id="reliefBtn" type="button" class="off">🏔 Relief</button>
      <button id="aglBtn" type="button" class="is-on" title="Roter Punkt, wo die reale Flughöhe über Grund von der Sollflughöhe um mehr als ±10 m abweicht.">● Abweichung von der Sollflughöhe</button>
      <button id="wpDebugBtn" type="button" class="is-on" hidden>Wegpunktdaten</button>
      <button id="layerRpasBtn" type="button" class="off">🛡 RPAS</button>
      <button id="layerElectricBtn" type="button" class="off">⚡ Strom</button>
      <button id="layerObstaclesBtn" type="button" class="off">📡 Hindernisse</button>
      <button id="layerDabsBtn" type="button" class="off">📅 DABS</button>
      <button id="layerFzBtn" type="button" class="off">⚠ Flugzone</button>
    </div>
    <div id="optMenu" class="tool-menu" hidden>
      <div class="tool-title">Optimieren</div>
      <button id="dirMinus" type="button">↖ Richtung −15°</button>
      <button id="dirAuto" type="button">↻ Richtung automatisch</button>
      <button id="dirPlus" type="button">↗ Richtung +15°</button>
      <button id="recomputeBtn" type="button">✈ Flugpfad neu rechnen</button>
      <div id="dirLabel" class="meta"></div>
    </div>
    <div class="tools">
      <button id="undoBtn" type="button" hidden>↩ Rückgängig</button>
      <button id="optBtn" type="button" hidden>↻ Optimieren</button>
      <div class="tool-pair">
        <button id="showBtn" type="button">☰ Anzeige</button>
        <div id="mapLabelSize" role="group" aria-label="Schriftgrösse der Kartenbeschriftung">
          <button id="labelSmaller" type="button" title="Schrift kleiner" aria-label="Schrift kleiner">A−</button>
          <button id="labelLarger" type="button" title="Schrift grösser" aria-label="Schrift grösser">A+</button>
        </div>
        <button id="rcHelpMapBtn" type="button" data-rkrk-help title="Hilfe zur aktuellen Sicht" aria-label="Hilfe">?</button>
      </div>
    </div>
    <div id="basemapBar" role="group" aria-label="Kartenhintergrund">
      <button id="basemapMapBtn" type="button" class="is-on" data-basemap="swisstopo">Karte</button>
      <button id="basemapSatBtn" type="button" data-basemap="swissimage">Satellit</button>
    </div>
  </section>

  <section id="dronePick" class="view" hidden>
    <div class="top">
      <h1>Drohne</h1>
      <button id="droneBack" type="button" hidden>Zurück</button>
    </div>
    <div class="count">Einmal wählen. Feldkatalog: Mavic 3T, Matrice 4TD und Mavic 3T Debug. Matrice 4T, Matrice 3TD und Mavic 3TA haben keinen Feldkatalog. Mavic II und Matrice 30T sind aus der Auswahl entfernt. Ein neuer Katalog wird nur in der Website-Konfiguration angelegt.</div>
    <div id="droneList"></div>
  </section>

  <div id="picHost" aria-hidden="true"></div>
  <?php include dirname(__DIR__) . '/scripts/rkrk_catalog_shot.php'; ?>
  <script src="https://cdn.jsdelivr.net/npm/ol@7.5.2/dist/ol.js"></script>
  <script src="rkrk_help_catalog.js?v=20261018abdeckung2"></script>
  <script src="rkrk_help.js?v=20261017hilfe7"></script>
  <script src="rkrk_swiss_overlays.js"></script>
  <script src="rkrk_field_shared.js?v=20261018ws"></script>
  <script>
    const API = 'api_field_editor.php';
    // Matrice 4T, Matrice 3TD und Mavic 3TA haben keinen Feldkatalog.
    // Mavic II und Matrice 30T sind keine wählbare Mission mehr.
    function missionKeyFromName(raw) {
      const name = String(raw || '').toLowerCase().replace(/[\s_-]/g, '');
      if (name === 'd' || name.includes('debug')) return 'D';
      if (name.includes('4td')) return '4TD';
      if (name.includes('30t') || name.includes('mavic2')) return '';
      if (name.includes('3ta') || name.includes('m3ta')) return '3TA';
      if (name.includes('3td') || name.includes('m3td')) return '3TD';
      if (name.includes('4t') || name.includes('m4t')) return '4T';
      if (name === '3t' || name === 'm3t' || name === 'mavic3t') return '3T';
      return '';
    }
    function missionKey() {
      return missionKeyFromName(droneType) || '3T';
    }
    function droneHasCatalog(name) {
      const key = missionKeyFromName(name);
      return key === '3T' || key === '4TD' || key === 'D';
    }
    function missionAltitudeDefaultM(name) {
      const key = String(name || '').toLowerCase().replace(/[\s_-]/g, '');
      if (key.includes('debug')) return 60;
      if (key.includes('30t')) return 60;
      if (key.includes('3ta') || key.includes('m3ta')) return 80;
      if (key.includes('4td') || key.includes('m4td')) return 75;
      if (key.includes('4t') || key.includes('m4t')) return 75;
      return 60;
    }
    function clampHalfSpeed(raw) {
      const n = Number(raw);
      if (!Number.isFinite(n)) return (window.RkrkField && RkrkField.easyMissionDefaults) ? RkrkField.easyMissionDefaults().speed_m_s : 4.5;
      const stepped = Math.round(n * 2) / 2;
      return Math.max(1, Math.min(15, stepped));
    }
    function clampMissionHeight(raw) {
      const n = Math.round(Number(raw));
      if (!Number.isFinite(n)) return missionAltitudeDefaultM(droneType);
      return Math.min(120, Math.max(20, n));
    }
    function reserveToken() {
      const key = 'rkrkFeldReserveToken';
      let token = '';
      try { token = sessionStorage.getItem(key) || ''; } catch (_) {}
      if (!/^[A-Za-z0-9_-]{8,80}$/.test(token)) {
        const bytes = new Uint8Array(16);
        if (window.crypto && crypto.getRandomValues) crypto.getRandomValues(bytes);
        token = Array.from(bytes).map((b) => b.toString(16).padStart(2, '0')).join('');
        if (!token || /^0+$/.test(token)) token = 't' + Date.now().toString(36);
        try { sessionStorage.setItem(key, token); } catch (_) {}
      }
      return token;
    }
    function apiUrl(extra) {
      const q = new URLSearchParams();
      if (window.RKRK_KAT) q.set('KAT', '');
      if (window.RKRK_ADMIN) q.set('ADMIN', '');
      q.set('MDB', missionKey());
      if (extra && typeof extra === 'object') {
        Object.keys(extra).forEach((name) => {
          if (extra[name] != null && String(extra[name]) !== '') q.set(name, String(extra[name]));
        });
      }
      return API + '?' + q.toString();
    }
    function pictureQuery(kind) {
      if (kind === 'such' || kind === 'suchfeld') {
        return 'Reg=ALL&Strt=on&Cntr=off&Area=on&Pfad=off&GDE=off&JGG=off&Poi=off&HSP=on&OVR=on&Label=off&EX=NO&KA=M&MAP=1&Zoom=0.6';
      }
      return 'Reg=ALL&Strt=on&Cntr=off&Area=on&Pfad=on&GDE=off&JGG=off&Poi=off&HSP=on&FLP=on&Label=off&EX=NO&KA=M&MAP=1&Zoom=0.8';
    }
    function releaseReservation() {
      if (!window.RKRK_KAT) return;
      const payload = JSON.stringify({ mode: 'release', token: reserveToken(), mdb: missionKey() });
      const url = apiUrl();
      try {
        const blob = new Blob([payload], { type: 'application/json' });
        if (navigator.sendBeacon && navigator.sendBeacon(url, blob)) return;
      } catch (_) {}
      fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: payload, keepalive: true, credentials: 'same-origin' }).catch(() => {});
    }
    window.RKRK_KAT = <?php echo $rkrkKat ? 'true' : 'false'; ?>;
    window.RKRK_ADMIN = <?php echo $rkrkAdmin ? 'true' : 'false'; ?>;
    const DRONE_KEY = 'rkrk-rc-drone';
    const FIELD_KEY = 'rkrk-rc-field';
    const listView = document.getElementById('listView');
    const mapView = document.getElementById('mapView');
    const dronePick = document.getElementById('dronePick');
    const listEl = document.getElementById('list');
    const countEl = document.getElementById('count');
    const qEl = document.getElementById('q');
    let rows = [];
    let activeId = '';
    let map = null;
    let polygonSource = null;
    let pathSource = null;
    let coverageSource = null;
    let heightSource = null;
    let aglWarnSource = null;
    let parkSource = null;
    let poiSource = null;
    let startSource = null;
    let imageLayer = null;
    let mapLayer = null;
    let satellite = false;
    let currentField = null;
    let vertexSource = null;
    let createMode = false;
    let createPhase = 'ring';
    let draftRing = [];
    let draftStart = null;
    let draftPath = '';
    let draftEpoch = 0;
    let draftWp = 0;
    let draftDist = 0;
    let draftPathHeight = 0;
    let draftHeight = 60;
    let missionHeightUserSet = false;
    let missionHeightFromField = false;
    let draftSpeed = (window.RkrkField && RkrkField.easyMissionDefaults) ? RkrkField.easyMissionDefaults().speed_m_s : 4.5;
    let draftStartSpeedActive = false;
    let draftStartSpeed = (window.RkrkField && RkrkField.easyMissionDefaults && Number(RkrkField.easyMissionDefaults().start_speed_m_s) > 0)
      ? Number(RkrkField.easyMissionDefaults().start_speed_m_s)
      : 5;
    let draftDirection = -1;
    let draftPark = null;
    let pickPark = false;
    let draftPois = [];
    let draftPoi = null;
    let pickMissionPoi = false;
    let pickPoi = false;
    let generatedPath = '';
    let entryMode = 'second';
    let showCoverage = true;
    let routeCoverageReady = false;
    let showRouteHeights = false;
    let showAglLimit = true;
    const rcDisplayKey = 'rkrkRcFlightPathDisplayV1';
    function readRcDisplay() {
      try {
        const raw = localStorage.getItem(rcDisplayKey);
        if (!raw) return {};
        const parsed = JSON.parse(raw);
        return parsed && typeof parsed === 'object' ? parsed : {};
      } catch (_) { return {}; }
    }
    function writeRcDisplay(partial) {
      try {
        const next = Object.assign({}, readRcDisplay(), partial || {});
        localStorage.setItem(rcDisplayKey, JSON.stringify(next));
      } catch (_) {}
    }
    let mapLabelScale = 1;
    const sharedLabelKey = 'fieldEditorFloatingUiState_v1';
    function readSharedMapLabelScale() {
      try {
        const raw = localStorage.getItem(sharedLabelKey);
        if (!raw) return NaN;
        const parsed = JSON.parse(raw);
        const dc = parsed && parsed.lastDisplayControls;
        return dc && typeof dc === 'object' ? Number(dc.mapLabelScale) : NaN;
      } catch (_) { return NaN; }
    }
    function writeSharedMapLabelScale(scale) {
      try {
        const raw = localStorage.getItem(sharedLabelKey);
        const parsed = raw ? JSON.parse(raw) : {};
        const state = parsed && typeof parsed === 'object' && !Array.isArray(parsed) ? parsed : {};
        const prev = state.lastDisplayControls;
        const dc = prev && typeof prev === 'object' && !Array.isArray(prev) ? prev : {};
        dc.mapLabelScale = scale;
        state.lastDisplayControls = dc;
        localStorage.setItem(sharedLabelKey, JSON.stringify(state));
      } catch (_) {}
    }
    function rememberRcDisplay() {
      writeRcDisplay({
        showCoverage: !!showCoverage,
        showRouteHeights: !!showRouteHeights,
        showAglLimit: !!showAglLimit,
        mapLabelScale: mapLabelScale
      });
      writeSharedMapLabelScale(mapLabelScale);
    }
    (function applyRcLabelScale() {
      const state = readRcDisplay();
      const shared = readSharedMapLabelScale();
      const local = Number(state.mapLabelScale);
      const n = Number.isFinite(shared) ? shared : local;
      mapLabelScale = Math.max(0.7, Math.min(1.8, Math.round((Number.isFinite(n) ? n : 1) * 10) / 10));
      if (window.RkrkField && typeof RkrkField.setMapLabelScale === 'function') {
        mapLabelScale = RkrkField.setMapLabelScale(mapLabelScale);
      }
    })();
    (function ensureRcAglDefaultOn() {
      const state = readRcDisplay();
      if (state.aglDefaultOnV2) {
        if (typeof state.showCoverage === 'boolean') showCoverage = state.showCoverage;
        if (typeof state.showRouteHeights === 'boolean') showRouteHeights = state.showRouteHeights;
        if (typeof state.showAglLimit === 'boolean') showAglLimit = state.showAglLimit;
        return;
      }
      showCoverage = typeof state.showCoverage === 'boolean' ? state.showCoverage : true;
      showRouteHeights = typeof state.showRouteHeights === 'boolean' ? state.showRouteHeights : false;
      showAglLimit = true;
      writeRcDisplay({
        aglDefaultOnV2: true,
        routeAglDefaultOffV1: true,
        showCoverage: showCoverage,
        showRouteHeights: showRouteHeights,
        showAglLimit: true
      });
    })();
    let aglToleranceM = 10;
    let aglTolLoaded = false;
    let aglWarnToken = 0;
    let aglWarnTimer = 0;
    let wpIndex = 0;
    let editId = '';
    let editMission = null;
    let expertMode = false;
    let terrainDelta = [];
    let terrainFor = '';
    let terrainCache = new Map();
    let savedCheck = null;
    let selectedPoint = null;
    let suppressClick = false;
    let reliefLayer = null;
    let extraLayers = {};
    let dabsCtl = null;
    let flightZoneLayer = null;
    let shotMap = null;
    let shotPoly = null;
    let shotPath = null;
    let shotStart = null;
    let shotPathLayer = null;
    const STEPS = [
      { id: 'ring', title: 'Fläche' },
      { id: 'start', title: 'Start' },
      { id: 'route', title: 'Flugpfad' },
      { id: 'wp', title: '4 - Prüfen' },
      { id: 'name', title: 'Speichern' },
      { id: 'check', title: 'Daten' }
    ];
    if (!window.RKRK_KAT) {
      const nameStep = STEPS.find((step) => step.id === 'name');
      if (nameStep) nameStep.title = 'Download';
    }
    let placeConfirmed = false;
    let pendingPlace = null;
    let draftGis = '';
    let draftFeldId = '';
    let feldIdTouched = false;
    let gisTouched = false;
    let gisSuggest = '';
    let placeBusy = false;
    let gisBusy = false;
    let placeTimer = null;
    let placeToken = 0;
    let placeFetchedKey = '';
    let gisFetchedKey = '';
    let droneType = '';
    let droneProfiles = [];
    let viewBeforeDrone = 'list';

    function readStoredDrone() {
      try { return localStorage.getItem(DRONE_KEY) || ''; } catch (_) { return ''; }
    }
    function rememberedField() {
      try { return localStorage.getItem(FIELD_KEY) || ''; } catch (_) { return ''; }
    }
    function rememberField(id) {
      try {
        if (id) localStorage.setItem(FIELD_KEY, id);
        else localStorage.removeItem(FIELD_KEY);
      } catch (_) {}
    }
    function showDroneName() {
      const label = droneType || 'Drohne';
      document.getElementById('droneBtn').textContent = label;
      document.getElementById('droneMapBtn').textContent = label;
    }
    function openDronePick() {
      viewBeforeDrone = !window.RKRK_KAT ? 'map' : (!mapView.hidden ? 'map' : 'list');
      listView.hidden = true;
      mapView.hidden = true;
      dronePick.hidden = false;
      document.getElementById('droneBack').hidden = droneType === '';
    }
    function closeDronePick() {
      dronePick.hidden = true;
      if (!window.RKRK_KAT) {
        listView.hidden = true;
        if (!map) startCreate();
        else mapView.hidden = false;
        return;
      }
      if (viewBeforeDrone === 'map') mapView.hidden = false;
      else listView.hidden = false;
    }
    function chooseDrone(name) {
      const changed = name !== droneType;
      droneType = name;
      try { localStorage.setItem(DRONE_KEY, name); } catch (_) {}
      showDroneName();
      closeDronePick();
      if (!droneHasCatalog(name)) {
        startCreate();
        return;
      }
      if (changed && createMode && !missionHeightUserSet && !missionHeightFromField) {
        draftHeight = missionAltitudeDefaultM(name);
        if (createPhase === 'route' && draftRing.length >= 3 && draftStart) computeRoute();
        else renderDraft();
      }
      if (changed && window.RKRK_KAT) loadList();
    }
    function renderDronePick() {
      const host = document.getElementById('droneList');
      host.replaceChildren();
      if (!droneProfiles.length) {
        host.innerHTML = '<div class="empty">Keine Drohnentypen geladen.</div>';
        return;
      }
      droneProfiles.forEach((profile) => {
        const name = String(profile.name || profile.key || '').trim();
        if (!name) return;
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'field-btn' + (name === droneType ? ' active' : '');
        btn.innerHTML = '<span class="name"></span><span class="meta"></span>';
        btn.querySelector('.name').textContent = name;
        const key = missionKeyFromName(name);
        btn.querySelector('.meta').textContent = (key === '3T' || key === '4TD' || key === 'D') ? 'Feldkatalog' : 'kein Feldkatalog';
        btn.addEventListener('click', () => chooseDrone(name));
        host.appendChild(btn);
      });
    }
    async function readJson(res) {
      const text = await res.text();
      try {
        const data = JSON.parse(text);
        if (data && typeof data === 'object') return data;
      } catch (_) {}
      const start = text.indexOf('{');
      if (start > 0) {
        try {
          const data = JSON.parse(text.slice(start));
          if (data && typeof data === 'object') return data;
        } catch (_) {}
      }
      const plain = text.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 180);
      throw new Error(plain || 'Die Antwort war leer');
    }
    async function initDrone() {
      const saved = readStoredDrone();
      if (!saved) openDronePick();
      try {
        const res = await fetch('api_mission_export.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          credentials: 'same-origin',
          body: JSON.stringify({ action: 'get_drone_profiles', field: {} })
        });
        const data = await readJson(res);
        if (!data.ok) throw new Error(data.detail || data.error || 'Drohnenliste fehlgeschlagen');
        droneProfiles = Array.isArray(data.profiles) ? data.profiles : [];
        const match = droneProfiles.find((profile) => profile.name === saved || profile.key === saved);
        if (match) {
          droneType = String(match.name || saved);
          showDroneName();
          if (!listView.hidden || !mapView.hidden) dronePick.hidden = true;
        } else {
          droneType = '';
          openDronePick();
        }
        renderDronePick();
      } catch (err) {
        document.getElementById('droneList').innerHTML = '<div class="empty"></div>';
        document.getElementById('droneList').querySelector('.empty').textContent = err.message;
        openDronePick();
      }
    }
    function numbers(raw) {
      const found = String(raw || '').match(/-?\d+(?:\.\d+)?/g);
      return found ? found.map(Number) : [];
    }
    function pairs(raw) {
      const nums = numbers(raw);
      const out = [];
      for (let i = 0; i + 1 < nums.length; i += 2) out.push([nums[i], nums[i + 1]]);
      return out;
    }
    function triplets(raw) {
      const nums = numbers(raw);
      const out = [];
      for (let i = 0; i + 2 < nums.length; i += 3) out.push([nums[i], nums[i + 1], nums[i + 2]]);
      return out;
    }
    function formatTriplets(pts) {
      return pts.map((p) => Number(p[0]).toFixed(6) + ', ' + Number(p[1]).toFixed(6) + ', ' + Number(p[2] || 0).toFixed(1)).join('     ');
    }
    function same6(a, b) {
      if (!a || !b) return false;
      return Number(a[0]).toFixed(6) === Number(b[0]).toFixed(6) && Number(a[1]).toFixed(6) === Number(b[1]).toFixed(6);
    }
    function distanceMeters(a, b) {
      const lat = ((Number(a[1]) + Number(b[1])) / 2) * Math.PI / 180;
      const dx = (Number(b[0]) - Number(a[0])) * 111320 * Math.cos(lat);
      const dy = (Number(b[1]) - Number(a[1])) * 110540;
      return Math.hypot(dx, dy);
    }
    function formatDist(raw) {
      const n = Number(String(raw || '').replace(',', '.'));
      if (!Number.isFinite(n) || n <= 0) return '';
      if (n >= 1000) {
        const km = (n / 1000).toFixed(1).replace('.0', '');
        return km + ' km';
      }
      return Math.round(n) + ' m';
    }
    function formatStart(raw) {
      const p = pairs(raw)[0];
      if (!p) return '';
      return p[0].toFixed(6) + ', ' + p[1].toFixed(6);
    }
    function formatPairs(pts) {
      return pts.map((p) => p[0].toFixed(6) + ', ' + p[1].toFixed(6)).join('     ');
    }
    function formatCenter(pts) {
      const list = Array.isArray(pts) ? pts : [];
      if (list.length < 1) return '';
      let lon = 0;
      let lat = 0;
      let n = 0;
      list.forEach((p) => {
        const x = Number(p[0]);
        const y = Number(p[1]);
        if (!Number.isFinite(x) || !Number.isFinite(y)) return;
        lon += x;
        lat += y;
        n += 1;
      });
      if (!n) return '';
      return (lon / n).toFixed(4) + ', ' + (lat / n).toFixed(4);
    }
    function setWarn(text) {
      const warn = document.getElementById('warn');
      warn.hidden = !text;
      warn.textContent = text || '';
    }

    function setTask(text) {
      const task = document.getElementById('task');
      if (!task) return;
      const msg = String(text || '').trim();
      task.hidden = msg === '';
      task.textContent = msg;
    }

    function listArea(raw) {
      const text = String(raw || '').trim();
      if (!text || text === '0') return '';
      if (/[a-zA-ZäöüÄÖÜ]/.test(text)) return text;
      return text + ' ha';
    }

    function listMeta(row) {
      const parts = [];
      const jg = String(row.jg_name || row.jg || '').trim();
      const gemeinde = String(row.gemeinde_name || row.gemeinde || '').trim();
      const gis = String(row.gis || '').trim();
      const flurname = String(row.flurname || '').trim();
      const freigabe = String(row.freigabe || '').trim();
      const flaeche = listArea(row.flaeche);
      if (jg) parts.push(jg);
      if (gemeinde) parts.push(gemeinde);
      if (gis) parts.push('GIS ' + gis);
      if (flurname) parts.push(flurname);
      if (freigabe) parts.push(freigabe);
      if (flaeche) parts.push(flaeche);
      return parts.join(' · ');
    }

    function askDeleteField(feldId, name) {
      const nummer = String(feldId || '').trim() || '(ohne Nummer)';
      const feldname = String(name || '').trim() || '(ohne Name)';
      return new Promise((resolve) => {
        const backdrop = document.createElement('div');
        backdrop.className = 'field-delete-ask';
        backdrop.setAttribute('role', 'alertdialog');
        backdrop.setAttribute('aria-modal', 'true');
        const box = document.createElement('div');
        box.className = 'field-delete-ask-box';
        const text = document.createElement('p');
        text.textContent = 'Feldkatalognummer ' + nummer + '\nFeldname ' + feldname + '\n\nDieses Feld aus dem Katalog löschen? Die Feldkatalognummer wird frei. Bilder (Suchfeld, Flugplan) und Flugprogramme (KMZ, KML, JSON) dieses Feldes werden gelöscht.';
        const actions = document.createElement('div');
        actions.className = 'field-delete-ask-actions';
        const cancel = document.createElement('button');
        cancel.type = 'button';
        cancel.className = 'field-delete-cancel';
        cancel.textContent = 'Abbrechen';
        const ok = document.createElement('button');
        ok.type = 'button';
        ok.className = 'field-delete-confirm';
        ok.textContent = 'Löschen';
        let done = false;
        const finish = (yes) => {
          if (done) return;
          done = true;
          document.removeEventListener('keydown', onKey);
          backdrop.remove();
          resolve(!!yes);
        };
        const onKey = (ev) => {
          if (ev.key === 'Escape') {
            ev.preventDefault();
            finish(false);
          }
        };
        cancel.addEventListener('click', () => finish(false));
        ok.addEventListener('click', () => finish(true));
        backdrop.addEventListener('click', (ev) => {
          if (ev.target === backdrop) finish(false);
        });
        actions.appendChild(cancel);
        actions.appendChild(ok);
        box.appendChild(text);
        box.appendChild(actions);
        backdrop.appendChild(box);
        document.body.appendChild(backdrop);
        document.addEventListener('keydown', onKey);
        cancel.focus();
      });
    }

    async function deleteListedField(row) {
      if (!window.RKRK_KAT) {
        setWarn('Löschen braucht ?KAT.');
        return;
      }
      const feldId = String(row.feld_id || '').trim();
      const name = String(row.name || '').trim();
      const yes = await askDeleteField(feldId, name);
      if (!yes) return;
      const res = await fetch(apiUrl(), {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          mode: 'delete',
          confirm: true,
          kat: true,
          mdb: missionKey(),
          field: { id: String(row.id || ''), feld_id: feldId, name: name },
        }),
      });
      const data = await readJson(res);
      if (!data.ok) throw new Error(data.detail || data.error || 'Löschen fehlgeschlagen');
      if (String(activeId) === String(row.id)) activeId = '';
      await loadList();
      setWarn('');
    }

    function renderList() {
      const q = qEl.value.trim().toLowerCase();
      const shown = rows.filter((row) => {
        if (!q) return true;
        const hay = [
          row.feld_id, row.name, row.jg, row.jg_name, row.gemeinde, row.gemeinde_name,
          row.gis, row.flurname, row.freigabe, row.flaeche
        ].join(' ').toLowerCase();
        return hay.includes(q);
      });
      countEl.textContent = shown.length + (shown.length === 1 ? ' Feld' : ' Felder');
      if (!shown.length) {
        listEl.innerHTML = '<div class="empty">Kein Feld gefunden.</div>';
        return;
      }
      listEl.replaceChildren();
      const frag = document.createDocumentFragment();
      shown.forEach((row) => {
        const wrap = document.createElement('div');
        wrap.className = 'field-row';
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'field-btn' + (row.id === activeId ? ' active' : '');
        btn.innerHTML = '<span class="id"></span><span class="name"></span><span class="meta"></span>';
        btn.querySelector('.id').textContent = row.feld_id || row.id;
        btn.querySelector('.name').textContent = row.name || 'ohne Name';
        const meta = listMeta(row);
        const metaEl = btn.querySelector('.meta');
        metaEl.textContent = meta;
        metaEl.hidden = meta === '';
        btn.addEventListener('click', () => openField(row.id));
        wrap.appendChild(btn);
        if (window.RKRK_KAT) {
          const del = document.createElement('button');
          del.type = 'button';
          del.className = 'field-delete-btn';
          del.textContent = 'Löschen';
          del.setAttribute('aria-label', 'Feld ' + (row.feld_id || row.id || '') + ' löschen');
          del.addEventListener('click', (ev) => {
            ev.preventDefault();
            ev.stopPropagation();
            deleteListedField(row).catch((err) => setWarn(String(err && err.message ? err.message : err)));
          });
          wrap.appendChild(del);
        }
        frag.appendChild(wrap);
      });
      listEl.appendChild(frag);
    }

    async function loadList() {
      countEl.textContent = 'Felder werden geladen …';
      try {
        const res = await fetch(apiUrl({ action: 'list' }), { credentials: 'same-origin' });
        const data = await readJson(res);
        if (!data.ok) throw new Error(data.detail || data.error || 'Liste fehlgeschlagen');
        if (data.catalog === false) {
          rows = [];
          countEl.textContent = 'Kein Feldkatalog';
          listEl.replaceChildren();
          const note = document.createElement('div');
          note.className = 'empty';
          note.textContent = data.detail || 'Dieser Drohnentyp hat keinen Feldkatalog.';
          listEl.appendChild(note);
          return;
        }
        rows = Array.isArray(data.fields) ? data.fields : [];
        renderList();
      } catch (err) {
        countEl.textContent = '';
        listEl.innerHTML = '<div class="empty"></div>';
        listEl.querySelector('.empty').textContent = 'Felder nicht geladen: ' + err.message;
      }
    }

    function ensureMap() {
      if (map) return;
      polygonSource = new ol.source.Vector();
      pathSource = new ol.source.Vector();
      coverageSource = new ol.source.Vector();
      heightSource = new ol.source.Vector();
      aglWarnSource = new ol.source.Vector();
      parkSource = new ol.source.Vector();
      poiSource = new ol.source.Vector();
      startSource = new ol.source.Vector();
      vertexSource = new ol.source.Vector();
      imageLayer = new ol.layer.Tile({
        source: new ol.source.XYZ({
          url: 'https://wmts.geo.admin.ch/1.0.0/ch.swisstopo.swissimage/default/current/3857/{z}/{x}/{y}.jpeg',
          attributions: 'Map data: <a href="https://www.swisstopo.admin.ch/" target="_blank" rel="noopener">swisstopo</a>',
          crossOrigin: 'anonymous',
          maxZoom: 19
        }),
        visible: false
      });
      mapLayer = new ol.layer.Tile({
        source: new ol.source.XYZ({
          url: 'https://wmts.geo.admin.ch/1.0.0/ch.swisstopo.pixelkarte-farbe/default/current/3857/{z}/{x}/{y}.jpeg',
          attributions: 'Map data: <a href="https://www.swisstopo.admin.ch/" target="_blank" rel="noopener">swisstopo</a>',
          crossOrigin: 'anonymous',
          maxZoom: 19
        }),
        visible: true
      });
      const polygonLayer = new ol.layer.Vector({
        source: polygonSource,
        style: new ol.style.Style({
          stroke: new ol.style.Stroke({ color: '#ff4d4d', width: 5 }),
          fill: new ol.style.Fill({ color: 'rgba(255, 70, 70, 0.18)' })
        })
      });
      const pathLayer = new ol.layer.Vector({
        source: pathSource,
        style: new ol.style.Style({
          stroke: new ol.style.Stroke({ color: '#2f6dff', width: 5 })
        })
      });
      pathLayer.set('title', 'route');
      const coverageLayer = new ol.layer.Vector({
        source: coverageSource,
        style: (feature) => new ol.style.Style({
          fill: new ol.style.Fill({
            color: String(feature.get('coverageFillColor') || 'rgba(64,196,255,0.46)')
          })
        })
      });
      const heightLayer = new ol.layer.Vector({
        source: heightSource,
        declutter: true,
        style: (feature) => new ol.style.Style({
          text: new ol.style.Text({
            text: String(feature.get('label') || ''),
            font: window.RkrkField && RkrkField.mapLabelFont ? RkrkField.mapLabelFont(12, false) : '12px sans-serif',
            fill: new ol.style.Fill({ color: '#6b7280' }),
            backgroundFill: new ol.style.Fill({ color: 'rgba(255,255,255,0.88)' }),
            padding: [1, 3, 1, 3],
            textAlign: 'center',
            offsetY: 16
          })
        })
      });
      const aglWarnLayer = new ol.layer.Vector({
        source: aglWarnSource,
        style: new ol.style.Style({
          image: new ol.style.Circle({
            radius: 7,
            fill: new ol.style.Fill({ color: '#e10600' }),
            stroke: new ol.style.Stroke({ color: '#ffffff', width: 2 })
          })
        })
      });
      const poiLayer = new ol.layer.Vector({
        source: poiSource,
        style: (feature) => new ol.style.Style({
          image: new ol.style.Circle({
            radius: 8,
            fill: new ol.style.Fill({ color: '#c2410c' }),
            stroke: new ol.style.Stroke({ color: '#ffffff', width: 2 })
          }),
          text: new ol.style.Text({
            text: String(feature.get('poiLabel') || ''),
            font: '12px sans-serif',
            offsetY: -16,
            fill: new ol.style.Fill({ color: '#9a3412' }),
            stroke: new ol.style.Stroke({ color: '#ffffff', width: 3 })
          })
        })
      });
      const parkLayer = new ol.layer.Vector({
        source: parkSource,
        style: new ol.style.Style({
          image: new ol.style.Circle({
            radius: 8,
            fill: new ol.style.Fill({ color: '#1d4ed8' }),
            stroke: new ol.style.Stroke({ color: '#ffffff', width: 2 })
          })
        })
      });
      const startLayer = new ol.layer.Vector({
        source: startSource,
        style: new ol.style.Style({
          image: new ol.style.Circle({
            radius: 10,
            fill: new ol.style.Fill({ color: '#f5d90a' }),
            stroke: new ol.style.Stroke({ color: '#1a1a1a', width: 3 })
          })
        })
      });
      const vertexLayer = new ol.layer.Vector({
        source: vertexSource,
        style: new ol.style.Style({
          image: new ol.style.Circle({
            radius: 7,
            fill: new ol.style.Fill({ color: '#ffffff' }),
            stroke: new ol.style.Stroke({ color: '#ff4d4d', width: 3 })
          })
        })
      });
      map = new ol.Map({
        target: 'map',
        layers: [imageLayer, mapLayer, polygonLayer, coverageLayer, pathLayer, aglWarnLayer, heightLayer, vertexLayer, parkLayer, poiLayer, startLayer],
        controls: [],
        view: new ol.View({
          center: ol.proj.fromLonLat(KNONAU),
          zoom: 14
        })
      });
      ensureOverlays();
      map.on('singleclick', (evt) => {
        if (suppressClick) {
          suppressClick = false;
          return;
        }
        if (!createMode || createPhase === 'confirm' || createPhase === 'check') return;
        const lonlat = ol.proj.toLonLat(evt.coordinate);
        if (pickMissionPoi && createPhase === 'start') {
          draftPois.push({
            longitude: Number(lonlat[0].toFixed(6)),
            latitude: Number(lonlat[1].toFixed(6)),
            height: Number(draftHeight) || 0
          });
          draftPoi = draftPois[0] || null;
          pickMissionPoi = false;
          renderDraft();
          return;
        }
        if (pickPoi) {
          const overrides = ensureEditOverrides();
          const key = String(wpIndex + 1);
          const prev = overrides[key] && typeof overrides[key] === 'object' ? overrides[key] : {};
          overrides[key] = Object.assign({}, prev, {
            poi: {
              longitude: Number(lonlat[0].toFixed(6)),
              latitude: Number(lonlat[1].toFixed(6)),
              height: Number(draftHeight) || 0,
            }
          });
          pickPoi = false;
          renderDraft();
          return;
        }
        const point = [Number(lonlat[0].toFixed(6)), Number(lonlat[1].toFixed(6))];
        const hit = nearestPoint(evt.pixel);
        if (createPhase === 'start' && hit && hit.kind === 'start') {
          pickPark = false;
          selectedPoint = { kind: 'start', index: 0 };
          renderDraft();
          return;
        }
        if (createPhase === 'start' && hit && hit.kind === 'park') {
          pickPark = true;
          selectedPoint = { kind: 'park', index: 0 };
          renderDraft();
          return;
        }
        if (hit && createPhase !== 'start') {
          selectedPoint = { kind: hit.kind, index: hit.index };
          if (hit.kind === 'path') wpIndex = hit.index;
          renderDraft();
          return;
        }
        const ringEdge = createPhase === 'ring' && draftRing.length >= 2
          ? segmentHit(evt.pixel, draftRing, draftRing.length >= 3)
          : null;
        const pathPts = triplets(draftPath);
        const pathEdge = (createPhase === 'route' || createPhase === 'wp') && pathPts.length >= 2
          ? segmentHit(evt.pixel, pathPts, false)
          : null;
        if (ringEdge) ringEdge.kind = 'ring';
        if (pathEdge) pathEdge.kind = 'path';
        const edge = pathEdge && (!ringEdge || pathEdge.dist < ringEdge.dist) ? pathEdge : ringEdge;
        if (edge && edge.kind === 'path') {
          insertPathPoint(edge.index, edge.lonlat);
          return;
        }
        if (edge) {
          insertRingPoint(edge.index, edge.lonlat);
          return;
        }
        placeConfirmed = false;
        if (createPhase === 'start' && pickPark) {
          setPark(point);
          return;
        }
        if (createPhase === 'ring') {
          draftRing.push(point);
          selectedPoint = { kind: 'ring', index: draftRing.length - 1 };
          clearRoute();
        } else if (createPhase === 'start') {
          draftStart = point;
          selectedPoint = { kind: 'start', index: 0 };
          if (draftPark && same6(draftPark, draftStart)) draftPark = null;
          clearRoute();
        } else {
          return;
        }
        renderDraft();
      });
      const viewport = map.getViewport();
      let hold = null;
      const setPan = (on) => {
        map.getInteractions().forEach((interaction) => {
          if (interaction instanceof ol.interaction.DragPan) interaction.setActive(on);
        });
      };
      const nearestPoint = (pixel) => {
        let best = null;
        const consider = (lonlat, kind, index) => {
          const px = map.getPixelFromCoordinate(ol.proj.fromLonLat(lonlat));
          if (!px) return;
          const dist = Math.hypot(px[0] - pixel[0], px[1] - pixel[1]);
          if (dist <= 36 && (!best || dist < best.dist)) best = { kind, index, dist };
        };
        if (createPhase !== 'start') draftRing.forEach((point, index) => consider(point, 'ring', index));
        if (draftStart) consider(draftStart, 'start', 0);
        if (draftPark) consider(draftPark, 'park', 0);
        if (createPhase === 'route' || createPhase === 'wp') {
          triplets(draftPath).forEach((point, index) => consider(point, 'path', index));
        }
        return best;
      };
      const moveHeldPoint = (ev) => {
        if (!selectedPoint) return;
        const lonlat = ol.proj.toLonLat(map.getEventCoordinate(ev));
        const point = [Number(lonlat[0].toFixed(6)), Number(lonlat[1].toFixed(6))];
        if (selectedPoint.kind === 'path') {
          const pts = triplets(draftPath);
          if (!pts[selectedPoint.index]) return;
          pts[selectedPoint.index][0] = point[0];
          pts[selectedPoint.index][1] = point[1];
          draftPath = formatTriplets(pts);
          renderDraft();
          return;
        }
        if (selectedPoint.kind === 'park') {
          setPark(point);
          return;
        }
        if (selectedPoint.kind === 'start') draftStart = point;
        else if (draftRing[selectedPoint.index]) draftRing[selectedPoint.index] = point;
        if (draftPark && draftStart && same6(draftPark, draftStart)) draftPark = null;
        clearRoute();
        placeConfirmed = false;
        pendingPlace = null;
        if (createPhase === 'name' || createPhase === 'wp') createPhase = 'route';
        renderDraft();
      };
      viewport.addEventListener('pointerdown', (ev) => {
        if (!createMode || createPhase === 'confirm') return;
        const hit = nearestPoint(map.getEventPixel(ev));
        if (!hit) {
          if (selectedPoint) {
            selectedPoint = null;
            renderDraft();
          }
          return;
        }
        if (createPhase === 'ring' && hit.kind === 'ring') {
          hold = { kind: hit.kind, index: hit.index, x: ev.clientX, y: ev.clientY, id: ev.pointerId, armed: true, moved: false };
          selectedPoint = { kind: hit.kind, index: hit.index };
          suppressClick = true;
          setPan(false);
          renderDraft();
          return;
        }
        const already = selectedPoint && selectedPoint.kind === hit.kind && selectedPoint.index === hit.index;
        hold = { kind: hit.kind, index: hit.index, x: ev.clientX, y: ev.clientY, id: ev.pointerId, armed: false, moved: false };
        if (already) {
          hold.armed = true;
          suppressClick = true;
          setPan(false);
          return;
        }
        hold.timer = setTimeout(() => {
          if (!hold) return;
          hold.armed = true;
          selectedPoint = { kind: hold.kind, index: hold.index };
          suppressClick = true;
          setPan(false);
          renderDraft();
        }, 450);
      });
      viewport.addEventListener('pointermove', (ev) => {
        if (!hold || ev.pointerId !== hold.id) return;
        const moved = Math.hypot(ev.clientX - hold.x, ev.clientY - hold.y);
        if (!hold.armed) {
          if (moved > 14) {
            clearTimeout(hold.timer);
            hold = null;
          }
          return;
        }
        if (moved > 3) {
          hold.moved = true;
          ev.preventDefault();
          ev.stopPropagation();
          moveHeldPoint(ev);
        }
      }, true);
      const endHold = (ev) => {
        if (!hold || ev.pointerId !== hold.id) return;
        clearTimeout(hold.timer);
        if (hold.armed) {
          selectedPoint = { kind: hold.kind, index: hold.index };
          suppressClick = true;
          renderDraft();
        }
        setPan(true);
        hold = null;
      };
      viewport.addEventListener('pointerup', endHold);
      viewport.addEventListener('pointercancel', endHold);
    }

    function fitField() {
      if (!map) return;
      const extent = ol.extent.createEmpty();
      [polygonSource, pathSource, startSource].forEach((src) => {
        const ext = src.getExtent();
        if (ext && Number.isFinite(ext[0])) ol.extent.extend(extent, ext);
      });
      if (!Number.isFinite(extent[0])) return;
      const size = map.getSize() || [400, 300];
      const chrome = document.getElementById('mapChrome');
      const open = chrome && !chrome.classList.contains('is-collapsed');
      const left = open ? chrome.offsetWidth + 12 : 16;
      const right = Math.round(size[0] * 0.16);
      map.getView().fit(extent, {
        padding: [16, right, 16, left],
        maxZoom: 18,
        duration: 180
      });
    }

    function drawField(field) {
      ensureMap();
      polygonSource.clear();
      pathSource.clear();
      startSource.clear();
      if (vertexSource) vertexSource.clear();
      if (coverageSource) coverageSource.clear();
      if (heightSource) heightSource.clear();
      if (aglWarnSource) aglWarnSource.clear();
      if (parkSource) parkSource.clear();
      if (poiSource) poiSource.clear();
      const ring = pairs(field.flaechendaten).map((p) => ol.proj.fromLonLat(p));
      if (ring.length >= 3) {
        const first = ring[0];
        const last = ring[ring.length - 1];
        if (first[0] !== last[0] || first[1] !== last[1]) ring.push(first.slice());
        polygonSource.addFeature(new ol.Feature(new ol.geom.Polygon([ring])));
      }
      const routePts = triplets(field.flugweg);
      const route = routePts.map((p) => ol.proj.fromLonLat(p));
      if (route.length >= 2) {
        pathSource.addFeature(new ol.Feature(new ol.geom.LineString(route)));
      }
      paintGreyTurns(routePts);
      routePts.forEach((p, index) => {
        const mark = new ol.Feature(new ol.geom.Point(ol.proj.fromLonLat(p)));
        const yaw = yawDisplayForRoutePoint(routePts, index);
        mark.set('yawMode', yaw.mode);
        mark.set('yawAngleDeg', yaw.angle);
        mark.set('yawNextLegDeg', yaw.next);
        mark.set('pathDeviates', routePointDeviates(index + 1));
        mark.setStyle((feature, resolution) => routePointStyle(index, false, feature, resolution));
        if (vertexSource) vertexSource.addFeature(mark);
      });
      const start = pairs(field.startplatz)[0];
      if (start) {
        startSource.addFeature(new ol.Feature(new ol.geom.Point(ol.proj.fromLonLat(start))));
      }
      pathSource.getFeatures().forEach((f) => f.set('kind', 'path'));
      routeCoverageReady = routePts.length >= 2;
      paintCoverage(routePts);
      paintPoi();
      const pathLayer = routeLayer();
      if (pathLayer) pathLayer.setVisible(route.length >= 2);
      map.updateSize();
      scheduleAglWarn();
      fitField();
      scheduleRcWaypointDebugTable();
    }

    function missionPictureUrl(rel) {
      const path = String(rel || '').trim();
      if (!path) return '';
      if (/^(https?:)?\/\//i.test(path)) return path;
      const clean = path.replace(/\\/g, '/').replace(/^\/+/, '');
      if (!clean || clean.includes('..')) return '';
      const folder = missionKey() === '4TD' ? 'Missionen Matrice 4TD' : (missionKey() === 'D' ? 'Missionen Debug' : 'Missionen');
      return '../../' + encodeURIComponent(folder) + '/' + clean.split('/').map((part) => encodeURIComponent(part)).join('/');
    }

    function overrideRow(index1) {
      const maps = [];
      if (editMission && editMission.waypoint_overrides && typeof editMission.waypoint_overrides === 'object') {
        maps.push(editMission.waypoint_overrides);
      }
      const stored = currentField && currentField.mission_settings && currentField.mission_settings.waypoint_overrides;
      if (stored && typeof stored === 'object') maps.push(stored);
      for (let i = 0; i < maps.length; i++) {
        const row = maps[i][String(index1)];
        if (row && typeof row === 'object') return row;
      }
      return null;
    }

    function lensForRoutePoint(routePts, index0) {
      const row = overrideRow(index0 + 1);
      let yaw = null;
      if (row && row.use_mission_yaw === false && row.heading_angle != null && row.heading_angle !== '') {
        const n = Number(row.heading_angle);
        if (Number.isFinite(n)) yaw = n;
      }
      if (window.RkrkField) {
        const coords = routePts.map((p) => ol.proj.fromLonLat([Number(p[0]), Number(p[1])]));
        return RkrkField.lensBearingDeg(coords, index0, yaw);
      }
      return routeLegBearingDeg(routePts, index0);
    }

    function flagOn(value) {
      return !(value === false || value === 0 || value === '0' || value === 'false');
    }

    async function loadMissionJson(field) {
      const rel = String(field && field.jason_file || '').trim();
      if (!rel || !field || !window.RkrkField) return false;
      const url = missionPictureUrl(rel);
      if (!url) return false;
      try {
        const res = await fetch(url, { cache: 'no-store' });
        if (!res.ok) return false;
        const doc = await res.json();
        const parsed = typeof RkrkField.readMissionDeviations === 'function'
          ? RkrkField.readMissionDeviations(doc)
          : RkrkField.readUavWaypointOverrides(doc);
        if (!parsed.count) return false;
        if (!field.mission_settings || typeof field.mission_settings !== 'object') field.mission_settings = {};
        if (Array.isArray(parsed.pois)) {
          field.mission_settings.pois = parsed.pois;
          field.mission_settings.poi = parsed.pois[0] || null;
          draftPois = parsed.pois.map((raw) => (
            raw && Number.isFinite(Number(raw.longitude)) && Number.isFinite(Number(raw.latitude))
              ? { longitude: Number(raw.longitude), latitude: Number(raw.latitude), height: Number(raw.height) || 0 }
              : null
          )).filter(Boolean);
          draftPoi = draftPois[0] || null;
          if (editMission && typeof editMission === 'object') {
            editMission.pois = JSON.parse(JSON.stringify(draftPois));
            editMission.poi = draftPoi;
          }
        }
        const kmzRel = String(field.kmz_file || '').trim();
        if (kmzRel && typeof RkrkField.templateKmlFromKmz === 'function' && typeof RkrkField.applyKmzDeviations === 'function') {
          try {
            const kmzUrl = missionPictureUrl(kmzRel);
            if (kmzUrl) {
              const kmzRes = await fetch(kmzUrl, { cache: 'no-store' });
              if (kmzRes.ok) {
                const kml = await RkrkField.templateKmlFromKmz(new Uint8Array(await kmzRes.arrayBuffer()));
                if (kml) RkrkField.applyKmzDeviations(parsed, kml);
              }
            }
          } catch (_) {}
        }
        if (typeof RkrkField.alignOverridesToPath === 'function') {
          parsed.overrides = RkrkField.alignOverridesToPath(parsed.overrides, triplets(field.flugweg));
        }
        field.mission_settings.waypoint_overrides = parsed.overrides;
        if (parsed.gimbalMode) {
          field.mission_settings.gimbal_mode = parsed.gimbalMode;
          if (editMission && typeof editMission === 'object') editMission.gimbal_mode = parsed.gimbalMode;
        }
        if (editMission && typeof editMission === 'object') {
          editMission.waypoint_overrides = JSON.parse(JSON.stringify(parsed.overrides));
        }
        expertMode = parsed.anyFalse === true || parsed.easyModeForm === false;
        setRcExpertMode(expertMode);
        const pilot = doc.missionSettings && doc.missionSettings.djiPilot2;
        const hadSpeed = Number(field.mission_settings.speed_m_s) > 0;
        if (!hadSpeed && pilot && Number(pilot.speed) > 0) draftSpeed = clampHalfSpeed(pilot.speed);
        if (typeof field.mission_settings.start_speed_active !== 'boolean' && pilot && typeof pilot.transitionalSpeedActive === 'boolean') {
          draftStartSpeedActive = pilot.transitionalSpeedActive === true;
        }
        if (!expertMode && window.RkrkField && RkrkField.easyMissionDefaults) {
          draftSpeed = RkrkField.easyMissionDefaults().speed_m_s;
          draftStartSpeedActive = RkrkField.easyMissionDefaults().start_speed_active === true;
        }
        const stdH = Number(String(field.std_flughoehe || '').replace(/[^0-9.]/g, ''));
        const hadHeight = stdH > 0 || Number(field.mission_settings.altitude_m) > 0;
        if (!hadHeight && pilot && Number(pilot.height) > 1) draftHeight = Math.round(Number(pilot.height));
        return true;
      } catch (_) {
        return false;
      }
    }

    let expertPaintLock = false;

    function ensureEditOverrides() {
      if (!editMission || typeof editMission !== 'object') editMission = {};
      if (!editMission.waypoint_overrides || typeof editMission.waypoint_overrides !== 'object') {
        editMission.waypoint_overrides = {};
      }
      return editMission.waypoint_overrides;
    }

    function missionChoice(key, fallback) {
      const stored = editMission && editMission[key];
      if (stored) return String(stored);
      const fromField = currentField && currentField.mission_settings && currentField.mission_settings[key];
      if (fromField) return String(fromField);
      return fallback;
    }

    function setChoice(el, value) {
      if (!el) return;
      const text = String(value || '');
      if (el.tagName === 'SELECT') {
        const has = Array.prototype.some.call(el.options, (opt) => opt.value === text);
        if (!has && text) {
          const opt = document.createElement('option');
          opt.value = text;
          opt.textContent = text;
          el.appendChild(opt);
        }
      }
      if (document.activeElement !== el) el.value = text;
    }

    function yawIsDefinedMode(mode) {
      const text = String(mode || '');
      return /^definiert$/i.test(text) || /^customi[sz]ed$/i.test(text);
    }

    function yawIsLockedAxis(mode) {
      const text = String(mode || '');
      return /schenk|schwenk/i.test(text) || /^fixed$/i.test(text) || /^lock$/i.test(text);
    }

    function writeExpertRow() {
      if (expertPaintLock || !expertMode || createPhase !== 'wp') return;
      const overrides = ensureEditOverrides();
      const key = String(wpIndex + 1);
      const prev = overrides[key] && typeof overrides[key] === 'object'
        ? overrides[key]
        : (overrideRow(wpIndex + 1) || {});
      const useH = !!(document.getElementById('rcUseH') && document.getElementById('rcUseH').checked);
      const useS = !!(document.getElementById('rcUseS') && document.getElementById('rcUseS').checked);
      const useT = !!(document.getElementById('rcUseT') && document.getElementById('rcUseT').checked);
      const useY = !!(document.getElementById('rcUseY') && document.getElementById('rcUseY').checked);
      let useP = !!(document.getElementById('rcUseP') && document.getElementById('rcUseP').checked);
      let useD = !!(document.getElementById('rcUseD') && document.getElementById('rcUseD').checked);
      const yawNow = useY
        ? missionYawLabel()
        : String(document.getElementById('rcYaw') && document.getElementById('rcYaw').value || '');
      if (yawIsDefinedMode(yawNow)) {
        useP = true;
        useD = true;
      }
      const row = Object.assign({}, prev, {
        use_mission_height: useH,
        use_mission_speed: useS,
        use_mission_waypoint_type: useT,
        use_mission_yaw: useY,
        use_mission_path_mode: useP,
        use_mission_direction_mode: useD
      });
      if (!useH) {
        const n = Number(document.getElementById('rcAlt') && document.getElementById('rcAlt').value);
        if (Number.isFinite(n)) {
          row.agl_m = n;
          const pts = triplets(draftPath);
          if (pts[wpIndex]) {
            pts[wpIndex][2] = n;
            draftPath = formatTriplets(pts);
          }
        }
      }
      if (!useS) {
        const n = Number(document.getElementById('rcSpd') && document.getElementById('rcSpd').value);
        if (Number.isFinite(n)) row.speed_m_s = n;
      }
      if (!useT) row.waypoint_type = String(document.getElementById('rcType') && document.getElementById('rcType').value || '');
      if (!useY) {
        row.heading_mode = String(document.getElementById('rcYaw') && document.getElementById('rcYaw').value || '');
        const yawMode = row.heading_mode;
        const rawAng = String(document.getElementById('rcYawAngle') && document.getElementById('rcYawAngle').value || '').trim();
        if (yawIsDefinedMode(yawMode) && rawAng !== '' && rawAng !== '-' && Number.isFinite(Number(rawAng))) {
          row.heading_angle = Math.max(-180, Math.min(180, Math.round(Number(rawAng))));
        } else {
          row.heading_angle = null;
        }
      }
      if (!useP) row.alignment_aircraft = String(document.getElementById('rcPath') && document.getElementById('rcPath').value || '');
      if (!useD) {
        const n = Number(document.getElementById('rcDir') && document.getElementById('rcDir').value);
        if (Number.isFinite(n)) row.direction_deg = Math.max(-180, Math.min(180, Math.round(n)));
      }
      const gimbalOn = !!(document.getElementById('rcGimbalAngleActive') && document.getElementById('rcGimbalAngleActive').checked);
      if (!editMission || typeof editMission !== 'object') editMission = {};
      editMission.gimbal_mode = gimbalOn ? 'Jeden Wegpunkt festlegen' : 'Manuell';
      const pitchEl = document.getElementById('rcGimbalPitch');
      if (!gimbalOn && pitchEl) pitchEl.value = '-90';
      const pitch = Number(pitchEl && pitchEl.value);
      row.gimbal_pitch = gimbalOn && Number.isFinite(pitch) ? Math.max(-90, Math.min(30, Math.round(pitch))) : -90;
      overrides[key] = row;
    }

    function paintParkBox() {
      const box = document.getElementById('parkBox');
      if (!box) return;
      box.hidden = !(createMode && createPhase === 'start' && expertMode);
    }

    function paintRcGimbal() {
      const wrap = document.getElementById('rcGimbalWrap');
      const check = document.getElementById('rcGimbalAngleActive');
      const pitchEl = document.getElementById('rcGimbalPitch');
      const onWp = !!(createMode && createPhase === 'wp');
      if (onWp && check && pitchEl && document.activeElement !== pitchEl && document.activeElement !== check) {
        const row = overrideRow(wpIndex + 1) || {};
        const gimbalOn = /wegpunkt|punkt|usepoint/i.test(String(editMission && editMission.gimbal_mode || ''));
        check.checked = gimbalOn;
        const n = Number(row.gimbal_pitch);
        pitchEl.value = gimbalOn && Number.isFinite(n) ? String(Math.round(n)) : '-90';
        pitchEl.disabled = !gimbalOn;
      }
      if (!wrap || !check || !pitchEl) return;
      const on = !!check.checked;
      const shown = Number(pitchEl.value);
      const deviate = on && Number.isFinite(shown) && shown !== -90;
      wrap.hidden = !(onWp && (expertMode || deviate));
    }

    function paintExpert() {
      const box = document.getElementById('expertBox');
      if (!box) return;
      const show = expertMode && createMode && createPhase === 'wp';
      box.hidden = !show;
      const hMinus = document.getElementById('hMinus');
      const hPlus = document.getElementById('hPlus');
      if (!show) {
        if (hMinus) hMinus.disabled = false;
        if (hPlus) hPlus.disabled = false;
        paintRcDebugHints(false);
        paintRcGimbal();
        return;
      }
      expertPaintLock = true;
      const row = overrideRow(wpIndex + 1) || {};
      const useH = flagOn(row.use_mission_height);
      const useS = flagOn(row.use_mission_speed);
      const useT = flagOn(row.use_mission_waypoint_type);
      const useY = flagOn(row.use_mission_yaw);
      let useP = flagOn(row.use_mission_path_mode);
      let useD = flagOn(row.use_mission_direction_mode);
      const setCheck = (id, on) => {
        const el = document.getElementById(id);
        if (el) el.checked = !!on;
      };
      setCheck('rcUseH', useH);
      setCheck('rcUseS', useS);
      setCheck('rcUseT', useT);
      setCheck('rcUseY', useY);
      setCheck('rcUseP', useP);
      setCheck('rcUseD', useD);
      const alt = document.getElementById('rcAlt');
      if (alt && document.activeElement !== alt) {
        alt.value = useH ? String(draftHeight) : String(row.agl_m != null ? row.agl_m : draftHeight);
      }
      const altRel = document.getElementById('rcAltRel');
      if (altRel) {
        const programmed = Number(alt && alt.value);
        const delta = Number(terrainDelta[wpIndex]);
        const caption = window.RkrkField && typeof RkrkField.heightCaption === 'function'
          ? (Number.isFinite(delta) ? RkrkField.heightCaption(programmed, delta, 0) : RkrkField.heightCaption(programmed, NaN, NaN))
          : { paren: '' };
        altRel.textContent = caption.paren || '';
      }
      const spd = document.getElementById('rcSpd');
      if (spd && document.activeElement !== spd) {
        spd.value = useS ? clampHalfSpeed(draftSpeed).toFixed(1) : String(row.speed_m_s != null ? row.speed_m_s : draftSpeed);
      }
      const missionType = missionChoice('waypoint_type', 'Koordinierte Drehung, Ueberspringt den Wegpunkt');
      setChoice(document.getElementById('rcType'), useT ? missionType : (row.waypoint_type || missionType));
      const yawMode = useY ? missionYawLabel() : (row.heading_mode || missionYawLabel());
      setChoice(document.getElementById('rcYaw'), yawMode);
      const definedYaw = yawIsDefinedMode(yawMode);
      if (definedYaw) {
        useP = true;
        useD = true;
        setCheck('rcUseP', true);
        setCheck('rcUseD', true);
      }
      const pathWrap = document.getElementById('rcUsePWrap');
      const dirWrap = document.getElementById('rcUseDWrap');
      const debugOn = rcDebugOn();
      if (pathWrap) pathWrap.hidden = !debugOn;
      if (dirWrap) dirWrap.hidden = !debugOn;
      const poiBox = document.getElementById('rcPoiBox');
      if (poiBox) poiBox.hidden = !/fokus\s*poi/i.test(yawMode);
      const poiNote = document.getElementById('rcPoiNote');
      if (poiNote) {
        const poi = row.poi;
        poiNote.textContent = poi && poi.longitude != null
          ? ('POI ' + poi.longitude + ', ' + poi.latitude)
          : '';
      }
      const ang = document.getElementById('rcYawAngle');
      if (ang && document.activeElement !== ang) {
        const raw = row.heading_angle;
        const n = raw != null && raw !== '' && String(raw).toLowerCase() !== 'null' ? Number(raw) : NaN;
        ang.value = Number.isFinite(n) ? String(n) : '';
        ang.hidden = !(yawIsDefinedMode(yawMode) || yawIsLockedAxis(yawMode));
      }
      const missionAlign = missionChoice('alignment_aircraft', 'Automatisch');
      const alignRaw = useP ? missionAlign : (row.alignment_aircraft || missionAlign);
      const align = /kuerzester|kürzester|followbadarc/i.test(String(alignRaw)) ? 'Automatisch' : String(alignRaw || 'Automatisch');
      setChoice(document.getElementById('rcPath'), align);
      const dir = document.getElementById('rcDir');
      if (dir && document.activeElement !== dir) {
        const n = Number(row.direction_deg);
        dir.value = Number.isFinite(n) ? String(n) : '0';
      }
      const lock = (id, on) => {
        const el = document.getElementById(id);
        if (el) el.disabled = !!on;
      };
      lock('rcAlt', useH);
      lock('rcSpd', useS);
      lock('rcType', useT);
      lock('rcYaw', useY);
      lock('rcYawAngle', useY || yawIsLockedAxis(yawMode));
      lock('rcPath', useP);
      lock('rcDir', useD);
      paintRcGimbal();
      if (hMinus) hMinus.disabled = useH;
      if (hPlus) hPlus.disabled = useH;
      expertPaintLock = false;
      paintRcDebugHints(true);
    }

    function missionYawLabel() {
      const stored = editMission && editMission.heading_mode;
      if (stored) return String(stored);
      const fromField = currentField && currentField.mission_settings && currentField.mission_settings.heading_mode;
      if (fromField) return String(fromField);
      const easy = window.RkrkField && RkrkField.easyMissionDefaults && RkrkField.easyMissionDefaults();
      return easy ? easy.heading_mode : 'Richtung zum naechsten Wegpunkt';
    }

    function rcOverridesNeedExpert() {
      const maps = [];
      if (editMission && editMission.waypoint_overrides) maps.push(editMission.waypoint_overrides);
      const stored = currentField && currentField.mission_settings && currentField.mission_settings.waypoint_overrides;
      if (stored) maps.push(stored);
      if (window.RkrkField && typeof RkrkField.overridesNeedExpert === 'function') {
        return maps.some((rows) => RkrkField.overridesNeedExpert(rows));
      }
      return false;
    }

    function setRcExpertMode(expert) {
      if (!expert && rcOverridesNeedExpert()) expert = true;
      expertMode = !!expert;
      if (editMission && typeof editMission === 'object') editMission.easy_mode_form = !expertMode;
      const btn = document.getElementById('rcModeToggle');
      if (btn) {
        btn.hidden = !(createMode || currentField);
        btn.textContent = expertMode ? 'Expertenmodus' : 'Einfacher Modus';
        btn.classList.toggle('is-expert', expertMode);
      }
      const bar = document.getElementById('stepBar');
      if (bar) bar.classList.toggle('is-expert', expertMode);
      if (!expertMode && !rcOverridesNeedExpert() && window.RkrkField && RkrkField.easyMissionDefaults) {
        draftSpeed = RkrkField.easyMissionDefaults().speed_m_s;
        draftStartSpeedActive = false;
        draftStartSpeed = draftSpeed;
      }
      paintStartSpeedBox();
      paintParkBox();
      paintExpert();
    }

    function pictureFile(raw, prefer) {
      const parts = String(raw || '').split(',').map((part) => part.trim()).filter(Boolean);
      if (!parts.length) return '';
      if (prefer) {
        const hit = parts.find((part) => part.includes(prefer));
        if (hit) return hit;
      }
      return parts[0];
    }

    function showPictureThumbs(field) {
      const box = document.getElementById('picThumbs');
      if (!box) return;
      box.replaceChildren();
      const height = String((field && field.std_flughoehe) || '').replace(/\D/g, '');
      const shots = [
        ['Suchfeld', pictureFile(field && field.suchfeld_bild, '_00_')],
        ['Flugplan', pictureFile(field && field.flugplan_bild, height ? ('_' + height + '_') : '')]
      ];
      shots.forEach(([label, rel]) => {
        const url = missionPictureUrl(rel);
        if (!url) return;
        const fresh = url + (url.indexOf('?') >= 0 ? '&' : '?') + 'v=' + Date.now();
        const fig = document.createElement('figure');
        const cap = document.createElement('figcaption');
        cap.textContent = label;
        const img = document.createElement('img');
        img.alt = label;
        img.src = fresh;
        fig.append(cap, img);
        box.appendChild(fig);
      });
      box.hidden = box.childElementCount === 0;
      paintRenewPicButton();
    }

    function showFacts(field) {
      const id = field.feld_id || field.id || '';
      const name = field.name || '';
      document.getElementById('idline').textContent = (id + '  ' + name).trim();
      const bits = [];
      const release = String(field.freigabe || '').trim();
      if (release && release.toUpperCase() !== 'OK') bits.push(release);
      if (field.gemeinde_name) bits.push(field.gemeinde_name);
      const wp = Number(field.wegpunkte || 0);
      if (wp > 0) bits.push(wp + ' WP');
      const dist = formatDist(field.distanz);
      if (dist) bits.push(dist);
      const area = String(field.flaeche || '').trim();
      if (area && /[a-zA-Z]/.test(area)) bits.push(area);
      const start = formatStart(field.startplatz);
      if (start) bits.push(start);
      const meta = document.getElementById('meta');
      meta.replaceChildren();
      if (release && release.toUpperCase() !== 'OK') {
        const flag = document.createElement('span');
        flag.className = 'flag';
        flag.textContent = release;
        meta.appendChild(flag);
      }
      meta.appendChild(document.createTextNode(bits.filter((b) => b !== release).join(' · ')));
      setTask('Überarbeiten ändert dieses Feld. Abbrechen geht zurück zur Liste.');
      const h = String(field.std_flughoehe || '').trim();
      document.getElementById('height').textContent = h ? (h + ' m') : '';
      showPictureThumbs(field);
      paintKmzButton();
      const warn = document.getElementById('warn');
      const warning = String(field.warnung || '').trim();
      warn.hidden = warning === '';
      warn.textContent = warning ? ('Warnung ' + warning) : '';
    }

    function routeLayer() {
      if (!map) return null;
      return map.getLayers().getArray().find((layer) => layer.get('title') === 'route') || null;
    }

    function wmsOverlay(layers) {
      return new ol.layer.Tile({
        visible: false,
        opacity: 0.8,
        source: new ol.source.TileWMS({
          url: 'https://wms.geo.admin.ch/',
          crossOrigin: 'anonymous',
          params: { LAYERS: layers, FORMAT: 'image/png', TRANSPARENT: true }
        })
      });
    }

    function ensureOverlays() {
      if (!map || reliefLayer) return;
      reliefLayer = new ol.layer.Tile({
        visible: false,
        opacity: 0.7,
        source: new ol.source.XYZ({
          url: 'https://wmts.geo.admin.ch/1.0.0/ch.swisstopo.swisssurface3d-reliefschattierung-multidirektional/default/current/3857/{z}/{x}/{y}.png',
          crossOrigin: 'anonymous',
          maxZoom: 18
        })
      });
      extraLayers = {
        rpas: wmsOverlay('ch.bazl.einschraenkungen-drohnen'),
        electric: wmsOverlay('ch.bfe.elektrische-anlagen_ueber_36'),
        obstacles: wmsOverlay('ch.bazl.luftfahrthindernis_line,ch.bazl.luftfahrthindernis_point,ch.bazl.luftfahrthindernis_poly')
      };
      map.addLayer(reliefLayer);
      Object.keys(extraLayers).forEach((key) => map.addLayer(extraLayers[key]));
    }

    function routeLegBearingDeg(routePts, index) {
      const n = Array.isArray(routePts) ? routePts.length : 0;
      if (n < 2 || index < 0 || index >= n) return 0;
      let from = routePts[index];
      let to = routePts[index + 1];
      if (!to && index > 0) {
        from = routePts[index - 1];
        to = routePts[index];
      }
      if (!from || !to) return 0;
      const a = ol.proj.fromLonLat([Number(from[0]), Number(from[1])]);
      const b = ol.proj.fromLonLat([Number(to[0]), Number(to[1])]);
      const dx = b[0] - a[0];
      const dy = b[1] - a[1];
      if (!(Math.hypot(dx, dy) > 1e-6)) return 0;
      return (Math.atan2(dx, dy) * 180 / Math.PI + 360) % 360;
    }

    function routePointDeviates(index1) {
      const row = overrideRow(index1);
      if (window.RkrkField && typeof RkrkField.waypointMissesMission === 'function') {
        return RkrkField.waypointMissesMission(row);
      }
      return false;
    }

    function yawDisplayForRoutePoint(routePts, index0) {
      const row = overrideRow(index0 + 1) || {};
      const missionMode = missionYawLabel();
      const yawFlag = Object.prototype.hasOwnProperty.call(row, 'use_mission_yaw') && window.RkrkField
        ? RkrkField.jsonFlag(row.use_mission_yaw)
        : null;
      const useMission = yawFlag !== false;
      const mode = useMission ? missionMode : String(row.heading_mode || missionMode);
      let angle = null;
      if (!useMission) {
        const rawAngle = row.heading_angle;
        if (rawAngle != null && rawAngle !== '' && String(rawAngle).toLowerCase() !== 'null' && Number.isFinite(Number(rawAngle))) {
          angle = Number(rawAngle);
        }
      }
      if (/poi|fokus/i.test(mode)) {
        const own = (!useMission && row.poi && typeof row.poi === 'object') ? row.poi : null;
        const poi = own || (draftPois.length === 1 ? draftPois[0] : null);
        const p = routePts && routePts[index0];
        const lon = Number(poi && poi.longitude);
        const lat = Number(poi && poi.latitude);
        if (p && Number.isFinite(lon) && Number.isFinite(lat) && window.RkrkField && typeof RkrkField.bearingDegLonLat === 'function') {
          angle = RkrkField.bearingDegLonLat(Number(p[0]), Number(p[1]), lon, lat);
        }
      }
      const coords = (routePts || []).map((p) => ol.proj.fromLonLat([Number(p[0]), Number(p[1])]));
      const next = window.RkrkField && typeof RkrkField.nextLegBearingDeg === 'function'
        ? RkrkField.nextLegBearingDeg(coords, index0)
        : routeLegBearingDeg(routePts, index0);
      return { mode: mode, angle: angle, next: next };
    }

    function cameraHeadingTriangleStyles(feature, resolution, fillColor) {
      const g = feature && feature.getGeometry && feature.getGeometry();
      if (!(g instanceof ol.geom.Point) || !window.RkrkField || typeof RkrkField.yawSymbolLayout !== 'function') return [];
      const layout = RkrkField.yawSymbolLayout(
        feature.get('yawMode'),
        feature.get('yawAngleDeg'),
        feature.get('yawNextLegDeg'),
        g.getCoordinates(),
        resolution
      );
      if (!layout || layout.kind === 'none') return [];
      if (layout.kind === 'x') {
        return (layout.lines || []).map((line) => new ol.style.Style({
          geometry: new ol.geom.LineString(line),
          stroke: new ol.style.Stroke({ color: layout.color || '#e23d2b', width: 2.4 })
        }));
      }
      if (layout.kind !== 'triangle' || !layout.ring) return [];
      return [
        new ol.style.Style({
          geometry: new ol.geom.Polygon([layout.ring]),
          fill: new ol.style.Fill({ color: fillColor || '#2f6dff' }),
          stroke: new ol.style.Stroke({ color: '#ffffff', width: 1.4 })
        })
      ];
    }

    function formatStartRelHeight(meters) {
      const n = Number(meters);
      if (!Number.isFinite(n)) return '';
      const whole = Math.abs(n - Math.round(n)) < 0.05;
      return '(' + (whole ? String(Math.round(n)) : n.toFixed(1)) + ' m)';
    }

    function waypointRelHeightM(index0) {
      const pts = createMode ? triplets(draftPath) : triplets((currentField && currentField.flugweg) || '');
      const point = pts[index0];
      const row = overrideRow(index0 + 1);
      let mission = Number(draftHeight);
      if (!(createMode && mission > 0)) {
        const raw = Number(String((currentField && currentField.std_flughoehe) || '').replace(/[^0-9.]/g, ''));
        const fromMission = Number(currentField && currentField.mission_settings && currentField.mission_settings.altitude_m);
        if (Number.isFinite(raw) && raw > 0) mission = raw;
        else if (Number.isFinite(fromMission) && fromMission > 0) mission = fromMission;
      }
      if (!Number.isFinite(mission) || !(mission > 0)) {
        const z = Number(point && point[2]);
        mission = Number.isFinite(z) ? z : 60;
      }
      if (row && !flagOn(row.use_mission_height)) {
        const local = Number(row.agl_m);
        if (Number.isFinite(local)) return local;
        const z = Number(point && point[2]);
        if (Number.isFinite(z)) return z;
      }
      return mission;
    }

    function routeHeightCaption(index0) {
      const programmed = waypointRelHeightM(index0);
      const delta = Number(terrainDelta[index0]);
      if (window.RkrkField && typeof RkrkField.heightCaption === 'function') {
        return Number.isFinite(delta)
          ? RkrkField.heightCaption(programmed, delta, 0)
          : RkrkField.heightCaption(programmed, NaN, NaN);
      }
      const main = Number.isFinite(programmed) ? (programmed + ' m') : '';
      const paren = Number.isFinite(programmed) && Number.isFinite(delta)
        ? ('(' + (Math.round((programmed + delta) * 10) / 10).toFixed(1) + ' m)')
        : '';
      return { main: main, paren: paren };
    }

    function routePointStyle(index, selected, feature, resolution) {
      const deviates = !!(feature && feature.get('pathDeviates') === true);
      const pointColor = selected ? '#f5d90a' : (deviates ? '#ff8c00' : '#2f6dff');
      const caption = routeHeightCaption(index);
      return [
        new ol.style.Style({
          image: new ol.style.Circle({
            radius: selected ? 8 : 5,
            fill: new ol.style.Fill({ color: pointColor }),
            stroke: new ol.style.Stroke({ color: '#fff', width: selected ? 2 : 1.5 })
          })
        }),
        ...cameraHeadingTriangleStyles(feature, resolution, pointColor),
        new ol.style.Style({
          text: new ol.style.Text({
            text: String(index + 1),
            offsetY: -16,
            font: window.RkrkField && RkrkField.mapLabelFont ? RkrkField.mapLabelFont(18, true) : 'bold 18px sans-serif',
            fill: new ol.style.Fill({ color: '#1f2937' }),
            stroke: new ol.style.Stroke({ color: '#fff', width: 3 }),
            textAlign: 'center'
          })
        }),
        new ol.style.Style({
          text: new ol.style.Text({
            text: caption.main || '',
            offsetY: 16,
            font: window.RkrkField && RkrkField.mapLabelFont ? RkrkField.mapLabelFont(12, false) : '12px sans-serif',
            fill: new ol.style.Fill({ color: '#1f2937' }),
            stroke: new ol.style.Stroke({ color: '#fff', width: 3 }),
            textAlign: 'center'
          })
        }),
        ...((() => {
          const row = overrideRow(index + 1);
          const label = window.RkrkField && typeof RkrkField.specificGimbalLabel === 'function'
            ? RkrkField.specificGimbalLabel(row && row.gimbal_pitch)
            : '';
          if (!label) return [];
          return [new ol.style.Style({
            text: new ol.style.Text({
              text: label,
              offsetY: 16,
              offsetX: 26,
              font: window.RkrkField && RkrkField.mapLabelFont ? RkrkField.mapLabelFont(9, false) : '9px sans-serif',
              fill: new ol.style.Fill({ color: '#1d4e89' }),
              stroke: new ol.style.Stroke({ color: '#fff', width: 2 }),
              textAlign: 'left'
            })
          })];
        })()),
        new ol.style.Style({
          text: new ol.style.Text({
            text: caption.paren || '',
            offsetY: 30,
            font: window.RkrkField && RkrkField.mapLabelFont ? RkrkField.mapLabelFont(11, false) : '11px sans-serif',
            fill: new ol.style.Fill({ color: '#6b7280' }),
            stroke: new ol.style.Stroke({ color: '#fff', width: 3 }),
            textAlign: 'center'
          })
        })
      ];
    }

    function rcHelpView() {
      if (dronePick && !dronePick.hidden) return 'rc.drone';
      if (listView && !listView.hidden) return 'rc.list';
      if (!createMode) return 'rc.field';
      const views = { ring: 'rc.ring', start: 'rc.start', route: 'rc.route', wp: 'rc.wp', name: 'rc.name', check: 'rc.check' };
      return views[createPhase] || 'rc.list';
    }

    function paintDirection() {
      const text = draftDirection < 0 ? 'Vorschlag' : (draftDirection + '°');
      const label = document.getElementById('dirLabel');
      if (label) label.textContent = draftDirection < 0 ? 'Flugrichtung automatisch' : ('Flugrichtung ' + text);
      const inline = document.getElementById('dirInline');
      if (inline) inline.textContent = text;
      const range = document.getElementById('dirRange');
      if (range && document.activeElement !== range) range.value = String(draftDirection < 0 ? 0 : draftDirection);
    }

    function midpoint(a, b) {
      return [
        Number(((Number(a[0]) + Number(b[0])) / 2).toFixed(6)),
        Number(((Number(a[1]) + Number(b[1])) / 2).toFixed(6))
      ];
    }

    function segmentHit(pixel, points, closed) {
      if (!map || !pixel || !points || points.length < 2) return null;
      const n = points.length;
      const count = closed ? n : n - 1;
      let best = null;
      for (let i = 0; i < count; i++) {
        const a = map.getPixelFromCoordinate(ol.proj.fromLonLat(points[i]));
        const b = map.getPixelFromCoordinate(ol.proj.fromLonLat(points[(i + 1) % n]));
        if (!a || !b) continue;
        const abx = b[0] - a[0];
        const aby = b[1] - a[1];
        const len2 = abx * abx + aby * aby;
        if (len2 < 4) continue;
        let t = ((pixel[0] - a[0]) * abx + (pixel[1] - a[1]) * aby) / len2;
        t = Math.max(0, Math.min(1, t));
        const x = a[0] + abx * t;
        const y = a[1] + aby * t;
        const dist = Math.hypot(pixel[0] - x, pixel[1] - y);
        if (dist > 28 || t < 0.12 || t > 0.88) continue;
        if (best && dist >= best.dist) continue;
        const lonlat = ol.proj.toLonLat(map.getCoordinateFromPixel([x, y]));
        best = {
          index: i,
          dist: dist,
          lonlat: [Number(lonlat[0].toFixed(6)), Number(lonlat[1].toFixed(6))]
        };
      }
      return best;
    }

    function insertRingPoint(index, point) {
      draftRing.splice(index + 1, 0, point);
      selectedPoint = { kind: 'ring', index: index + 1 };
      clearRoute();
      placeConfirmed = false;
      renderDraft();
    }

    function insertPathPoint(index, point) {
      const pts = triplets(draftPath);
      const alt = Number(pts[index] && pts[index][2]) || draftHeight;
      pts.splice(index + 1, 0, [point[0], point[1], alt]);
      draftPath = formatTriplets(pts);
      draftWp = pts.length;
      wpIndex = index + 1;
      selectedPoint = { kind: 'path', index: wpIndex };
      renderDraft();
    }

    function addAfterSelected() {
      if (!selectedPoint) return;
      if (selectedPoint.kind === 'ring' && draftRing[selectedPoint.index]) {
        const i = selectedPoint.index;
        const here = draftRing[i];
        const next = draftRing.length >= 2 ? draftRing[(i + 1) % draftRing.length] : null;
        const point = next && !same6(here, next)
          ? midpoint(here, next)
          : [Number((here[0] + 0.00015).toFixed(6)), here[1]];
        insertRingPoint(i, point);
        return;
      }
      if (selectedPoint.kind === 'path') {
        const pts = triplets(draftPath);
        const i = selectedPoint.index;
        const here = pts[i];
        if (!here) return;
        const next = pts[i + 1];
        const point = next && !same6(here, next)
          ? midpoint(here, next)
          : [Number((Number(here[0]) + 0.00015).toFixed(6)), Number(here[1])];
        insertPathPoint(i, point);
      }
    }

    function deleteSelected() {
      if (!selectedPoint) return;
      if (selectedPoint.kind === 'ring') {
        draftRing.splice(selectedPoint.index, 1);
        selectedPoint = draftRing.length ? { kind: 'ring', index: Math.min(selectedPoint.index, draftRing.length - 1) } : null;
        clearRoute();
        placeConfirmed = false;
        if (draftRing.length < 3) createPhase = 'ring';
      } else if (selectedPoint.kind === 'path') {
        const pts = triplets(draftPath);
        pts.splice(selectedPoint.index, 1);
        draftPath = pts.length ? formatTriplets(pts) : '';
        draftWp = pts.length;
        if (!draftPath) {
          generatedPath = '';
          selectedPoint = null;
        } else {
          wpIndex = Math.min(selectedPoint.index, pts.length - 1);
          selectedPoint = { kind: 'path', index: wpIndex };
        }
      } else if (selectedPoint.kind === 'start') {
        draftStart = null;
        selectedPoint = null;
        clearRoute();
        if (createPhase !== 'ring') createPhase = 'start';
      } else if (selectedPoint.kind === 'park') {
        draftPark = null;
        selectedPoint = null;
      }
      renderDraft();
    }

    function paintPointBox() {
      const box = document.getElementById('pointBox');
      const label = document.getElementById('pointLabel');
      const add = document.getElementById('pointAdd');
      if (!box) return;
      const on = createMode && selectedPoint && createPhase !== 'check' && createPhase !== 'confirm';
      box.hidden = !on;
      if (!on || !label) return;
      const names = { ring: 'Ecke', path: 'Wegpunkt', start: 'Startpunkt', park: 'Parkplatz' };
      const title = names[selectedPoint.kind] || 'Punkt';
      label.textContent = selectedPoint.kind === 'start' || selectedPoint.kind === 'park'
        ? (title + ' gewählt. Ziehen verschiebt, Löschen entfernt.')
        : (title + ' ' + (selectedPoint.index + 1) + ' gewählt. Ziehen verschiebt, + fügt einen Punkt ein, Löschen entfernt.');
      if (add) add.hidden = selectedPoint.kind === 'start' || selectedPoint.kind === 'park';
    }

    function setPark(point) {
      if (!point || (draftStart && same6(point, draftStart))) {
        draftPark = null;
        pickPark = false;
        if (selectedPoint && selectedPoint.kind === 'park') selectedPoint = null;
        renderDraft();
        return;
      }
      draftPark = [Number(point[0]), Number(point[1])];
      pickPark = true;
      selectedPoint = { kind: 'park', index: 0 };
      renderDraft();
    }

    function offsetLonLat(lon, lat, eastM, northM) {
      const latRad = Number(lat) * Math.PI / 180;
      return [
        Number(lon) + eastM / (111320 * Math.cos(latRad)),
        Number(lat) + northM / 110540
      ];
    }

    function meterDelta(a, b) {
      const lat = ((Number(a[1]) + Number(b[1])) / 2) * Math.PI / 180;
      return [
        (Number(b[0]) - Number(a[0])) * 111320 * Math.cos(lat),
        (Number(b[1]) - Number(a[1])) * 110540
      ];
    }

    function unitDelta(v) {
      const len = Math.hypot(v[0], v[1]);
      if (!(len > 1e-6)) return null;
      return [v[0] / len, v[1] / len];
    }

    function coverageHalfM(point) {
      const z = Number(point && point[2]);
      const agl = Number.isFinite(z) && z > 1 ? z : Number(draftHeight);
      const h = Number.isFinite(agl) && agl > 1 ? agl : 60;
      return Math.max(8, h * Math.tan((49 * Math.PI / 180) / 2));
    }

    function addCoverageRing(lonLatRing) {
      const ring = lonLatRing.map((p) => ol.proj.fromLonLat(p));
      ring.push(ring[0].slice());
      coverageSource.addFeature(new ol.Feature(new ol.geom.Polygon([ring])));
    }

    function sectorPoints(lon, lat, radiusM, a0, sweep) {
      const steps = Math.max(8, Math.ceil(Math.abs(sweep) / (Math.PI / 16)));
      const ring = [[lon, lat]];
      for (let s = 0; s <= steps; s++) {
        const a = a0 + sweep * (s / steps);
        ring.push(offsetLonLat(lon, lat, Math.cos(a) * radiusM, Math.sin(a) * radiusM));
      }
      return ring;
    }

    function addRoundCap(point, neighbor, half) {
      const outward = unitDelta(meterDelta(neighbor, point));
      if (!outward) return;
      const a0 = Math.atan2(outward[0], -outward[1]);
      addCoverageRing(sectorPoints(point[0], point[1], half, a0, -Math.PI));
    }

    function paintCoverage(points) {
      if (!coverageSource) return;
      coverageSource.clear();
      if (!routeCoverageReady || !showCoverage || !points || points.length < 2 || !window.RkrkField || !RkrkField.buildRouteCoveragePolygons) return;
      const missionAgl = Number(draftHeight) > 1 ? Number(draftHeight) : 60;
      const metrics = RkrkField.metricsFromFov(missionAgl, 49, 40, 0.15);
      const pathCoords = points.map((p) => ol.proj.fromLonLat([Number(p[0]), Number(p[1])]));
      const aglList = points.map((p, i) => {
        const row = overrideRow(i + 1);
        return typeof RkrkField.programmedFlightHeightM === 'function'
          ? RkrkField.programmedFlightHeightM(missionAgl, row, p[2])
          : RkrkField.pointAgl(p[2], missionAgl, row);
      });
      const pitchList = points.map((_, i) => {
        const row = overrideRow(i + 1);
        return row && row.gimbal_pitch;
      });
      const bearingList = points.map((_, i) => {
        const yaw = yawDisplayForRoutePoint(points, i);
        const sym = typeof RkrkField.yawSymbol === 'function'
          ? RkrkField.yawSymbol(yaw && yaw.mode, yaw && yaw.angle, yaw && yaw.next)
          : null;
        if (sym && Number.isFinite(sym.bearingDeg)) return sym.bearingDeg;
        return RkrkField.lensBearingDeg
          ? RkrkField.lensBearingDeg(pathCoords, i, yaw && yaw.next)
          : (yaw && yaw.next);
      });
      const headingModes = points.map((_, i) => {
        const yaw = yawDisplayForRoutePoint(points, i);
        return yaw && yaw.mode;
      });
      RkrkField.buildRouteCoveragePolygons(pathCoords, aglList, metrics, missionAgl, points, true, pitchList, bearingList, headingModes).forEach((item) => {
        const piece = Array.isArray(item) ? { ring: item, fill: '' } : item;
        if (!piece || !Array.isArray(piece.ring) || piece.ring.length < 4) return;
        const feat = new ol.Feature(new ol.geom.Polygon([piece.ring]));
        if (piece.fill) feat.set('coverageFillColor', piece.fill);
        coverageSource.addFeature(feat);
      });
    }

    function isCoordinateTurn(type) {
      const s = String(type || '').toLowerCase();
      return s.indexOf('koordinierte drehung') >= 0 && s.indexOf('berspringt') >= 0;
    }

    function paintGreyTurns(routePts) {
      if (!pathSource || !routePts || routePts.length < 3 || !window.RkrkField || typeof RkrkField.coordinateTurnCoords !== 'function') return;
      const missionType = missionChoice('waypoint_type', 'Koordinierte Drehung, Ueberspringt den Wegpunkt');
      const missionDist = Number(missionChoice('standard_turn_distance_m', '5'));
      const fallback = Number.isFinite(missionDist) && missionDist > 0 ? missionDist : 5;
      for (let i = 1; i + 1 < routePts.length; i++) {
        const row = overrideRow(i + 1) || {};
        const type = row.use_mission_waypoint_type === false && row.waypoint_type ? row.waypoint_type : missionType;
        if (!isCoordinateTurn(type)) continue;
        let dist = fallback;
        if (row.use_mission_turn_distance === false && Number(row.turning_distance_m) > 0) dist = Number(row.turning_distance_m);
        const line = RkrkField.coordinateTurnCoords(
          ol.proj.fromLonLat([Number(routePts[i - 1][0]), Number(routePts[i - 1][1])]),
          ol.proj.fromLonLat([Number(routePts[i][0]), Number(routePts[i][1])]),
          ol.proj.fromLonLat([Number(routePts[i + 1][0]), Number(routePts[i + 1][1])]),
          dist
        );
        if (!line || line.length < 2) continue;
        const feat = new ol.Feature(new ol.geom.LineString(line));
        feat.setStyle(new ol.style.Style({
          stroke: new ol.style.Stroke({ color: 'rgba(107,114,128,0.95)', width: 3 })
        }));
        pathSource.addFeature(feat);
      }
    }

    function routeHeightLabel(point) {
      const agl = Number(point && point[2]);
      const programmed = Number.isFinite(agl) ? agl : draftHeight;
      return programmed.toFixed(1) + 'm';
    }

    function paintHeights(points) {
      if (!heightSource) return;
      heightSource.clear();
      if (!showRouteHeights) return;
      points.forEach((p, index) => {
        const label = routeHeightLabel(p, index);
        const feature = new ol.Feature(new ol.geom.Point(ol.proj.fromLonLat(p)));
        feature.set('label', label);
        if (createPhase === 'wp' && index === wpIndex) {
          feature.setStyle(new ol.style.Style({
            image: new ol.style.Circle({
              radius: 8,
              fill: new ol.style.Fill({ color: '#f5d90a' }),
              stroke: new ol.style.Stroke({ color: '#1a1a1a', width: 2 })
            }),
            text: new ol.style.Text({
              text: label,
              font: window.RkrkField && RkrkField.mapLabelFont ? RkrkField.mapLabelFont(12, false) : '12px sans-serif',
              offsetY: 16,
              textAlign: 'center',
              fill: new ol.style.Fill({ color: '#6b7280' }),
              backgroundFill: new ol.style.Fill({ color: 'rgba(255,255,255,0.92)' }),
              padding: [1, 3, 1, 3]
            })
          }));
        }
        heightSource.addFeature(feature);
      });
    }

    function paintAglButtons() {
      ['aglBtn', 'aglRouteBtn'].forEach((id) => {
        const el = document.getElementById(id);
        if (!el) return;
        el.classList.toggle('off', !showAglLimit);
        el.classList.toggle('is-on', showAglLimit);
      });
    }

    function toggleAglLimit() {
      showAglLimit = !showAglLimit;
      rememberRcDisplay();
      paintAglButtons();
      scheduleAglWarn();
    }

    async function loadAglTolerance() {
      if (aglTolLoaded) return;
      aglTolLoaded = true;
      try {
        const res = await fetch('requirements_data.json', { cache: 'no-store' });
        const data = await res.json();
        const rows = data.core_parameters || [];
        const row = rows.find((p) => p && p.key === 'agl_display_deviation_m')
          || rows.find((p) => p && p.key === 'agl_tolerance_m');
        const val = Number(row && row.value);
        if (Number.isFinite(val) && val > 0) aglToleranceM = val;
        const title = 'Roter Punkt, wo die reale Flughöhe über Grund von der Sollflughöhe um mehr als ±' + aglToleranceM + ' m abweicht.';
        ['aglBtn', 'aglRouteBtn'].forEach((id) => {
          const el = document.getElementById(id);
          if (el) el.title = title;
        });
      } catch (_) {}
    }

    function aglPathContext() {
      if (createMode) {
        const pts = triplets(draftPath);
        return pts.length >= 2 ? { pts: pts, start: draftStart || pts[0] } : null;
      }
      if (!currentField) return null;
      const pts = triplets(currentField.flugweg || '');
      const start = pairs(currentField.startplatz)[0] || pts[0];
      return pts.length >= 2 ? { pts: pts, start: start } : null;
    }

    function aglTargetM(pts) {
      if (createMode) return Number(draftHeight) || 60;
      const raw = Number(String((currentField && currentField.std_flughoehe) || '').replace(/[^0-9.]/g, ''));
      if (Number.isFinite(raw) && raw > 0) return raw;
      const first = Number(pts && pts[0] && pts[0][2]);
      return Number.isFinite(first) && first > 0 ? first : 60;
    }

    function sampleFlightPath(pts, stepM) {
      const out = [];
      for (let i = 0; i < pts.length - 1; i++) {
        const a = pts[i];
        const b = pts[i + 1];
        const dist = Math.max(0.001, distanceMeters(a, b));
        const n = Math.max(1, Math.ceil(dist / stepM));
        const startK = out.length ? 1 : 0;
        for (let k = startK; k <= n; k++) {
          const t = k / n;
          out.push({
            lon: a[0] + (b[0] - a[0]) * t,
            lat: a[1] + (b[1] - a[1]) * t,
            seg: i,
            t: t
          });
        }
      }
      return out;
    }

    function scheduleAglWarn() {
      if (aglWarnTimer) clearTimeout(aglWarnTimer);
      aglWarnTimer = setTimeout(() => {
        aglWarnTimer = 0;
        paintAglWarnings().catch(() => {});
      }, 180);
    }

    async function terrainHeights(points) {
      const out = new Array(points.length).fill(NaN);
      const chunk = 8;
      for (let i = 0; i < points.length; i += chunk) {
        const part = points.slice(i, i + chunk);
        const vals = await Promise.all(part.map(async (p) => {
          try { return await terrainHeight(p[0], p[1]); } catch (_) { return NaN; }
        }));
        vals.forEach((h, j) => { out[i + j] = h; });
      }
      return out;
    }

    async function paintAglWarnings() {
      if (!aglWarnSource) return;
      const token = ++aglWarnToken;
      aglWarnSource.clear();
      if (!showAglLimit) return;
      const ctx = aglPathContext();
      if (!ctx || !ctx.start) return;
      await loadAglTolerance();
      const samples = sampleFlightPath(ctx.pts, 25);
      if (!samples.length) return;
      const wpGround = await terrainHeights(ctx.pts.map((p) => [p[0], p[1]]));
      if (token !== aglWarnToken) return;
      const sampleRaw = await terrainHeights(samples.map((s) => [s.lon, s.lat]));
      if (token !== aglWarnToken) return;
      samples.forEach((sample, index) => {
        const groundH = sampleRaw[index];
        const aG = Number(wpGround[sample.seg]);
        const bG = Number(wpGround[sample.seg + 1]);
        if (!Number.isFinite(groundH) || !Number.isFinite(aG) || !Number.isFinite(bG)) return;
        const neighbor = aG + (bG - aG) * sample.t;
        const diff = groundH - neighbor;
        if (!(Math.abs(diff) > aglToleranceM)) return;
        const feature = new ol.Feature(new ol.geom.Point(ol.proj.fromLonLat([sample.lon, sample.lat])));
        feature.setStyle([
          new ol.style.Style({
            image: new ol.style.Circle({
              radius: 7,
              fill: new ol.style.Fill({ color: '#e10600' }),
              stroke: new ol.style.Stroke({ color: '#ffffff', width: 2 })
            })
          }),
          new ol.style.Style({
            text: new ol.style.Text({
              text: (diff >= 0 ? '+' : '') + diff.toFixed(1) + 'm',
              font: '12px sans-serif',
              offsetY: -14,
              fill: new ol.style.Fill({ color: '#e10600' }),
              stroke: new ol.style.Stroke({ color: '#ffffff', width: 3.2 })
            })
          })
        ]);
        aglWarnSource.addFeature(feature);
      });
    }

    function paintPark() {
      if (!parkSource) return;
      parkSource.clear();
      const note = document.getElementById('parkNote');
      const clearBtn = document.getElementById('parkClear');
      const show = draftPark && (!draftStart || !same6(draftPark, draftStart));
      if (clearBtn) clearBtn.hidden = !show;
      if (note) {
        note.textContent = show
          ? ('Parkplatz ' + Number(draftPark[0]).toFixed(6) + ', ' + Number(draftPark[1]).toFixed(6))
          : (pickPark ? 'Parkplatz auf die Karte tippen' : 'Parkplatz nur, wenn er sich vom Startpunkt unterscheidet.');
      }
      if (!show) return;
      parkSource.addFeature(new ol.Feature(new ol.geom.Point(ol.proj.fromLonLat(draftPark))));
    }

    function paintPoi() {
      if (!poiSource) return;
      poiSource.clear();
      draftPoi = draftPois.length ? draftPois[0] : null;
      const note = document.getElementById('poiNote');
      const clearBtn = document.getElementById('poiClear');
      const show = draftPois.length > 0;
      if (clearBtn) clearBtn.hidden = !show;
      if (note) {
        note.textContent = show
          ? draftPois.map((poi, i) => 'POI ' + (i + 1) + ' ' + Number(poi.longitude).toFixed(6) + ', ' + Number(poi.latitude).toFixed(6)).join(' · ')
          : (pickMissionPoi ? 'POI auf die Karte tippen.' : 'POI für die Gierrichtung Fokus POI.');
      }
      draftPois.forEach((poi, i) => {
        const feature = new ol.Feature(new ol.geom.Point(ol.proj.fromLonLat([
          Number(poi.longitude),
          Number(poi.latitude)
        ])));
        feature.set('poiLabel', 'POI ' + (i + 1));
        poiSource.addFeature(feature);
      });
    }

    function applyEntry(mode) {
      const base = triplets(generatedPath || draftPath).map((p) => [p[0], p[1], p[2]]);
      if (base.length < 3) return;
      entryMode = mode;
      const home = base[0];
      let cut = 1;
      while (cut < base.length - 1 && distanceMeters(home, base[cut]) < 12) cut += 1;
      const approach = base.slice(0, cut);
      const survey = base.slice(cut);
      let ordered = survey.slice();
      if (mode === 'last') ordered = survey.slice().reverse();
      else if (mode === 'secondLast' && survey.length >= 2) {
        ordered = survey.slice(0, -1).reverse().concat([survey[survey.length - 1]]);
      }
      const next = mode === 'second' ? base : approach.concat(ordered);
      draftPath = formatTriplets(next);
      draftWp = next.length;
      renderDraft();
    }

    function paintEntry() {
      ['entrySecond', 'entrySecondLast', 'entryLast'].forEach((id) => {
        const btn = document.getElementById(id);
        if (!btn) return;
        const mode = id === 'entryLast' ? 'last' : (id === 'entrySecondLast' ? 'secondLast' : 'second');
        btn.classList.toggle('is-on', entryMode === mode);
      });
      const cov = document.getElementById('covBtn');
      const ht = document.getElementById('htBtn');
      if (cov) cov.classList.toggle('is-on', showCoverage);
      if (ht) ht.classList.toggle('is-on', showRouteHeights);
      paintAglButtons();
    }

    function startSpeedUiOn() {
      const route = document.getElementById('routeTools');
      return !!(route && !route.hidden);
    }

    function paintStartSpeedBox() {
      const box = document.getElementById('startSpeedBox');
      if (!box) return;
      const active = document.getElementById('rcStartSpeedActive');
      const speed = document.getElementById('rcStartSpeed');
      if (!expertMode) {
        draftStartSpeedActive = false;
        draftStartSpeed = draftSpeed;
      }
      if (active && document.activeElement !== active) {
        active.checked = !!draftStartSpeedActive;
        active.disabled = !expertMode;
      }
      if (speed && document.activeElement !== speed) {
        const shown = expertMode && draftStartSpeedActive ? Number(draftStartSpeed) : Number(draftSpeed);
        speed.value = Number.isFinite(shown) ? shown.toFixed(1) : '4.5';
        speed.disabled = !expertMode || !draftStartSpeedActive;
      }
    }

    function paintRcDebugHints(visible) {
      if (!window.RkrkField || typeof RkrkField.paintDebugParamHints !== 'function') return;
      const pairs = [];
      if (visible) {
        pairs.push(
          ['rcUseH', 'useMissionHeight · wpml:useGlobalHeight'],
          ['rcAlt', 'height · wpml:height (ALT, Differenz zum Start)'],
          ['rcUseS', 'useMissionSpeed · wpml:useGlobalSpeed'],
          ['rcSpd', 'speed · wpml:waypointSpeed'],
          ['rcUseT', 'useMissionWaypointType · wpml:useGlobalTurnParam'],
          ['rcType', 'waypointType · wpml:waypointTurnMode'],
          ['rcUseY', 'useMissionYawDirection · wpml:useGlobalHeadingParam'],
          ['rcYaw', 'directionType · wpml:waypointHeadingMode'],
          ['rcDir', 'direction · wpml:waypointHeadingAngle'],
          ['rcUseP', 'useMissionPathMode · kein KMZ-Tag'],
          ['rcPath', 'alignmentAircraft · wpml:waypointHeadingYawPathMode'],
          ['rcUseD', 'useMissionDirectionMode · kein KMZ-Tag']
        );
      }
      if (startSpeedUiOn()) {
        pairs.push(['rcStartSpeedActive', 'start_speed_active · transitionalSpeedActive, kein KMZ-Tag']);
        const speedRow = document.getElementById('rcStartSpeedRow');
        if (speedRow && !speedRow.hidden) {
          pairs.push(['rcStartSpeed', 'start_speed_m_s · wpml:globalTransitionalSpeed']);
        }
      }
      RkrkField.paintDebugParamHints(pairs, rcDebugOn() && (!!visible || startSpeedUiOn()));
    }

    function rcDebugOn() {
      return /DEBUG/i.test(String(window.location.search || ''));
    }

    let rcWpDebugToken = 0;

    function rcDebugHtml(text) {
      return String(text == null ? '' : text).replace(/[&<>"']/g, (ch) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
      }[ch]));
    }

    function rcDebugMeter1(value, signed) {
      const n = Number(value);
      if (!Number.isFinite(n)) return '–';
      const tenth = Math.round(n * 10) / 10;
      const text = tenth.toFixed(1);
      return signed && tenth > 0 ? ('+' + text) : text;
    }

    function rcDebugPathPoints() {
      if (createMode) return triplets(draftPath);
      return triplets((currentField && currentField.flugweg) || draftPath || '');
    }

    function rcDebugStartLonLat(pts) {
      if (Array.isArray(draftStart) && Number.isFinite(Number(draftStart[0])) && Number.isFinite(Number(draftStart[1]))) {
        return [Number(draftStart[0]), Number(draftStart[1])];
      }
      const fromField = currentField ? pairs(currentField.startplatz)[0] : null;
      if (fromField && Number.isFinite(Number(fromField[0])) && Number.isFinite(Number(fromField[1]))) {
        return [Number(fromField[0]), Number(fromField[1])];
      }
      if (pts && pts[0]) return [Number(pts[0][0]), Number(pts[0][1])];
      return null;
    }

    function rcYawFormLabel(mode) {
      const raw = String(mode || '');
      const sel = document.getElementById('rcYaw');
      if (sel && sel.options) {
        const opt = Array.from(sel.options).find((item) => item.value === raw);
        if (opt && opt.textContent) return String(opt.textContent).trim();
      }
      return raw;
    }

    function rcGimbalDebugText(row) {
      const n = Number(row && row.gimbal_pitch);
      const pitch = Number.isFinite(n) ? Math.round(n) : -90;
      return (pitch < 0 ? '\u2212' : '') + String(Math.abs(pitch)) + '\u00b0';
    }

    function rcYawDebugText(routePts, index0) {
      const yaw = yawDisplayForRoutePoint(routePts, index0);
      let label = rcYawFormLabel(yaw && yaw.mode);
      if (yawIsDefinedMode(yaw && yaw.mode) && Number.isFinite(Number(yaw.angle))) {
        const tenth = Math.round(Number(yaw.angle) * 10) / 10;
        const ang = Math.abs(tenth - Math.round(tenth)) < 0.001 ? String(Math.round(tenth)) : tenth.toFixed(1);
        label = (label || 'Definiert') + ' ' + ang + '\u00b0';
      }
      return label || '–';
    }

    function rcDebugCaption(index0, terrain, startTerrain) {
      const programmed = waypointRelHeightM(index0);
      const delta = Number.isFinite(Number(terrain)) && Number.isFinite(Number(startTerrain))
        ? (Number(terrain) - Number(startTerrain))
        : NaN;
      if (window.RkrkField && typeof RkrkField.heightCaption === 'function') {
        return Number.isFinite(delta)
          ? RkrkField.heightCaption(programmed, delta, 0)
          : RkrkField.heightCaption(programmed, NaN, NaN);
      }
      const main = Number.isFinite(programmed) ? (programmed + ' m') : '';
      const paren = Number.isFinite(programmed) && Number.isFinite(delta)
        ? ('(' + (Math.round((programmed + delta) * 10) / 10).toFixed(1) + ' m)')
        : '';
      return { main: main, paren: paren };
    }

    function rcWpDebugUserOn() {
      try { return sessionStorage.getItem('rkrkRcWpDebugOn') !== '0'; } catch (_) { return true; }
    }

    function rcWpDebugStoredOpen() {
      try { return sessionStorage.getItem('rkrkRcWpDebugOpen') !== '0'; } catch (_) { return true; }
    }

    function syncRcWaypointDebugChrome() {
      const box = document.getElementById('wpDebugTableBox');
      const btn = document.getElementById('wpDebugBtn');
      const debug = rcDebugOn();
      const on = debug && rcWpDebugUserOn();
      if (btn) {
        btn.hidden = !debug;
        btn.classList.toggle('off', !on);
        btn.classList.toggle('is-on', !!on);
      }
      if (!box) return;
      const ready = box.dataset.ready === '1';
      const wasHidden = box.hidden;
      box.hidden = !(on && ready);
      if (!box.hidden && wasHidden) box.open = rcWpDebugStoredOpen();
    }

    function scheduleRcWaypointDebugTable() {
      const box = document.getElementById('wpDebugTableBox');
      const body = document.getElementById('wpDebugTableBody');
      if (!box || !body) return;
      const pts = rcDebugPathPoints();
      if (!rcDebugOn() || pts.length < 2) {
        box.dataset.ready = '0';
        body.innerHTML = '';
        syncRcWaypointDebugChrome();
        return;
      }
      const token = ++rcWpDebugToken;
      box.dataset.ready = '1';
      syncRcWaypointDebugChrome();
      if (box.hidden) return;
      body.innerHTML = '<tr><td class="wp-debug-empty" colspan="7">…</td></tr>';
      fillRcWaypointDebugTable(pts, token);
    }

    async function fillRcWaypointDebugTable(pts, token) {
      const startLL = rcDebugStartLonLat(pts);
      let startH = NaN;
      if (startLL) {
        try { startH = await terrainHeight(startLL[0], startLL[1]); } catch (_) { startH = NaN; }
      }
      const heights = new Array(pts.length).fill(NaN);
      const chunkSize = 20;
      for (let i = 0; i < pts.length; i += chunkSize) {
        const chunk = pts.slice(i, i + chunkSize);
        const got = await Promise.all(chunk.map(async (p) => {
          try { return await terrainHeight(p[0], p[1]); } catch (_) { return NaN; }
        }));
        got.forEach((h, j) => { heights[i + j] = h; });
      }
      if (token !== rcWpDebugToken) return;
      const body = document.getElementById('wpDebugTableBody');
      const box = document.getElementById('wpDebugTableBox');
      if (!body || !box || !rcDebugOn() || !rcWpDebugUserOn()) return;
      body.innerHTML = pts.map((p, idx) => {
        const terrain = Number(heights[idx]);
        const delta = Number.isFinite(terrain) && Number.isFinite(startH) ? (terrain - startH) : NaN;
        const caption = rcDebugCaption(idx, terrain, startH);
        const programmed = waypointRelHeightM(idx);
        const rel = window.RkrkField && typeof RkrkField.relativeHeightFromStartM === 'function'
          ? RkrkField.relativeHeightFromStartM(programmed, terrain, startH)
          : (Number.isFinite(programmed) && Number.isFinite(delta) ? (programmed + delta) : NaN);
        const relText = Number.isFinite(rel) ? ((Math.round(rel * 10) / 10).toFixed(1) + ' m') : '';
        const row = overrideRow(idx + 1);
        return '<tr>'
          + '<td class="num">' + (idx + 1) + '</td>'
          + '<td class="num">' + rcDebugHtml(rcDebugMeter1(terrain, false)) + '</td>'
          + '<td class="num">' + rcDebugHtml(rcDebugMeter1(delta, true)) + '</td>'
          + '<td class="num">' + rcDebugHtml(caption.main || '–') + '</td>'
          + '<td class="num">' + rcDebugHtml(relText) + '</td>'
          + '<td>' + rcDebugHtml(rcYawDebugText(pts, idx)) + '</td>'
          + '<td class="num">' + rcDebugHtml(rcGimbalDebugText(row)) + '</td>'
          + '</tr>';
      }).join('');
    }

    function rcNextTfId() {
      const ban = { 60: true, 62: true, 63: true, 64: true, 65: true };
      const used = {};
      (rows || []).forEach((field) => {
        const match = String(field && field.feld_id || '').match(/^TF-(\d+)$/i);
        if (match) used[Number(match[1])] = true;
      });
      let n = 1;
      while (used[n] || ban[n]) n += 1;
      return 'TF-' + String(n).padStart(3, '0');
    }

    function rcTfDirectory() {
      const key = missionKey();
      if (key === '4TD') return 'Missionen Matrice 4TD\\TF_Testfelder';
      if (key === 'D') return 'Missionen Debug\\TF_Testfelder';
      if (key === '3T') return 'Missionen\\TF_Testfelder';
      return '';
    }

    function prepareDebugTf() {
      const box = document.getElementById('debugTfBox');
      const dir = rcTfDirectory();
      if (!box) return;
      box.hidden = !(rcDebugOn() && dir);
      const dirEl = document.getElementById('debugTfDir');
      if (dirEl) dirEl.textContent = dir ? ('Zielordner: ' + dir) : '';
      const input = document.getElementById('debugTfId');
      if (input && dir) input.value = rcNextTfId();
      if (dir) setWarn('TF-Testfeld ' + (input ? input.value : '') + ' in ' + dir + '. Es wurde nichts geschrieben.');
    }

    async function terrainHeight(lon, lat) {
      const key = Number(lon).toFixed(5) + ',' + Number(lat).toFixed(5);
      if (terrainCache.has(key)) return terrainCache.get(key);
      const reframeUrl = 'https://geodesy.geo.admin.ch/reframe/wgs84tolv95?easting=' + encodeURIComponent(Number(lon).toFixed(7)) + '&northing=' + encodeURIComponent(Number(lat).toFixed(7)) + '&format=json';
      const reframeResp = await fetch(reframeUrl);
      if (!reframeResp.ok) throw new Error('Höhe');
      const reframe = await reframeResp.json();
      const heightUrl = 'https://api3.geo.admin.ch/rest/services/height?easting=' + encodeURIComponent(Number(reframe.easting).toFixed(3)) + '&northing=' + encodeURIComponent(Number(reframe.northing).toFixed(3)) + '&sr=2056';
      const heightResp = await fetch(heightUrl);
      if (!heightResp.ok) throw new Error('Höhe');
      const data = await heightResp.json();
      const h = Number(data.height);
      terrainCache.set(key, Number.isFinite(h) ? h : NaN);
      return terrainCache.get(key);
    }

    async function loadTerrainDelta() {
      const pts = triplets(draftPath);
      const key = draftPath;
      if (!draftStart || pts.length < 1 || terrainFor === key) return;
      terrainFor = key;
      try {
        const startH = await terrainHeight(draftStart[0], draftStart[1]);
        const out = [];
        for (let i = 0; i < pts.length; i++) {
          try {
            const h = await terrainHeight(pts[i][0], pts[i][1]);
            out.push(Number.isFinite(h) && Number.isFinite(startH) ? (h - startH) : NaN);
          } catch (_) {
            out.push(NaN);
          }
        }
        if (terrainFor !== key) return;
        terrainDelta = out;
        if (createMode) renderDraft();
      } catch (_) {
        terrainDelta = [];
      }
    }

    function paintEditButton() {
      const btn = document.getElementById('editBtn');
      if (btn) btn.hidden = createMode || !currentField;
      paintKmzButton();
    }

    function paintRenewPicButton() {
      const btn = document.getElementById('renewPicBtn');
      if (!btn) return;
      if (!window.RKRK_KAT) {
        btn.hidden = true;
        return;
      }
      const pics = document.getElementById('picThumbs');
      const picsOn = !!(pics && !pics.hidden && pics.childElementCount > 0);
      const onCheck = createMode && createPhase === 'check' && !!(savedCheck && savedCheck.id && savedCheck.feld_id);
      const onField = !createMode && !!(currentField && currentField.id && currentField.feld_id);
      btn.hidden = !(picsOn || onCheck || onField);
    }

    function renewRcPictures() {
      const info = savedCatalogInfo();
      const btn = document.getElementById('renewPicBtn');
      if (!info) {
        setWarn('Zuerst das Feld im Katalog speichern. Danach können Suchfeld- und Flugplanbild erzeugt werden.');
        if (btn) btn.disabled = false;
        return Promise.resolve();
      }
      if (btn) btn.disabled = true;
      return generateRcPictures(info).finally(() => {
        if (btn) btn.disabled = false;
      });
    }

    function paintCloseSave() {
      const btn = document.getElementById('closeSaveBtn');
      if (!btn) return;
      const onCheck = !!(window.RKRK_KAT && createMode && createPhase === 'check' && savedCheck && savedCheck.id && savedCheck.feld_id);
      btn.hidden = !onCheck;
    }

    function paintKmzButton() {
      paintFileActions();
    }

    function fieldHasExportPath() {
      if (createMode) return triplets(draftPath).length >= 2;
      return !!(currentField && triplets(currentField.flugweg || '').length >= 2);
    }

    function paintFileActions() {
      const nav = document.getElementById('fileNav');
      const saveBtn = document.getElementById('saveFilesBtn');
      const jsonBtn = document.getElementById('jsonDownBtn');
      const kmzBtn = document.getElementById('kmzBtn');
      const editing = createMode && (createPhase === 'route' || createPhase === 'wp' || createPhase === 'name' || createPhase === 'check');
      const viewing = !createMode && !!(currentField && (currentField.flugweg || currentField.feld_id));
      const ready = fieldHasExportPath() && (editing || viewing);
      if (saveBtn) saveBtn.hidden = !(window.RKRK_KAT && createMode && ready && createPhase !== 'check');
      if (jsonBtn) jsonBtn.hidden = !(window.RKRK_KAT && ready);
      if (kmzBtn) kmzBtn.hidden = !(window.RKRK_KAT && ready);
      if (nav) nav.hidden = !((saveBtn && !saveBtn.hidden) || (jsonBtn && !jsonBtn.hidden) || (kmzBtn && !kmzBtn.hidden));
    }

    function localDownloadName(field, ext) {
      const raw = String((field && (field.name || field.feld_id)) || 'feld').trim() || 'feld';
      const base = raw.replace(/[^\w\u00C0-\u024f.-]+/g, '_').replace(/^_+|_+$/g, '') || 'feld';
      return base + '.' + ext;
    }

    function xmlEscapeText(value) {
      return String(value || '').replace(/[&<>"']/g, (ch) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&apos;' }[ch]));
    }

    function kmlCoord(lon, lat, alt) {
      const h = Number(alt);
      return Number(lon).toFixed(7) + ',' + Number(lat).toFixed(7) + ',' + (Number.isFinite(h) ? h.toFixed(1) : '0.0');
    }

    function buildLocalKml(field) {
      const poly = pairs(field.flaechendaten);
      const path = triplets(field.flugweg);
      const start = pairs(field.startplatz)[0];
      if (poly.length < 3 && path.length < 2) throw new Error('Zuerst die Fläche oder den Flugweg zeichnen.');
      let body = '';
      if (poly.length >= 3) {
        const ring = poly.slice();
        const first = ring[0];
        const last = ring[ring.length - 1];
        if (first[0] !== last[0] || first[1] !== last[1]) ring.push(first);
        body += '<Placemark><name>Fläche</name><Polygon><outerBoundaryIs><LinearRing><coordinates>'
          + ring.map((p) => kmlCoord(p[0], p[1], 0)).join(' ')
          + '</coordinates></LinearRing></outerBoundaryIs></Polygon></Placemark>';
      }
      if (start) {
        body += '<Placemark><name>Start</name><Point><coordinates>' + kmlCoord(start[0], start[1], 0) + '</coordinates></Point></Placemark>';
      }
      if (path.length >= 2) {
        body += '<Placemark><name>Flugweg</name><LineString><tessellate>1</tessellate><coordinates>'
          + path.map((p) => kmlCoord(p[0], p[1], p[2])).join(' ')
          + '</coordinates></LineString></Placemark>';
      }
      const name = field.name || 'Feld';
      return '<' + '?xml version="1.0" encoding="UTF-8"?>'
        + '<kml xmlns="http://www.opengis.net/kml/2.2"><Document><name>' + xmlEscapeText(name) + '</name>'
        + body + '</Document></kml>';
    }

    function downloadLocal(kind) {
      const field = createMode ? draftField() : exportFieldPayload();
      if (!field) throw new Error('Zuerst eine Fläche zeichnen.');
      const filename = localDownloadName(field, kind === 'kml' ? 'kml' : 'json');
      const text = kind === 'kml' ? buildLocalKml(field) : JSON.stringify(field, null, 2);
      const mime = kind === 'kml' ? 'application/vnd.google-earth.kml+xml' : 'application/json';
      downloadBlob(filename, new Blob([text], { type: mime }));
      document.getElementById('meta').textContent = (kind === 'kml' ? 'KML' : 'JSON') + ' liegt im Download-Ordner: ' + filename;
    }

    function downloadBlob(filename, blob) {
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = filename;
      document.body.appendChild(a);
      a.click();
      a.remove();
      setTimeout(() => URL.revokeObjectURL(url), 1500);
    }

    function terrainMapForExport(pathText) {
      const pts = triplets(pathText);
      const map = {};
      pts.forEach((p, i) => {
        const d = Number(terrainDelta[i]);
        if (!Number.isFinite(d)) return;
        map[Number(p[0]).toFixed(7) + ',' + Number(p[1]).toFixed(7)] = d;
      });
      return map;
    }

    function exportFieldPayload() {
      if (createMode && draftPath) {
        const field = draftField();
        const idInput = document.getElementById('feldIdInput');
        field.feld_id = (idInput && idInput.value.trim()) || draftFeldId || (savedCheck && savedCheck.feld_id) || (currentField && currentField.feld_id) || '';
        field.name = document.getElementById('newName').value.trim() || (savedCheck && savedCheck.name) || (currentField && currentField.name) || field.name;
        field.jg = (savedCheck && savedCheck.jg) || (currentField && currentField.jg) || field.jg || '';
        field.gemeinde = (savedCheck && savedCheck.gemeinde) || (currentField && currentField.gemeinde) || field.gemeinde || '';
        field.terrain_delta_by_lonlat = terrainMapForExport(field.flugweg);
        return field;
      }
      if (!currentField) return null;
      const settings = (currentField.mission_settings && typeof currentField.mission_settings === 'object')
        ? currentField.mission_settings
        : {};
      return {
        feld_id: currentField.feld_id || '',
        name: currentField.name || '',
        jg: currentField.jg || '',
        gemeinde: currentField.gemeinde || '',
        std_flughoehe: currentField.std_flughoehe || '',
        startplatz: currentField.startplatz || '',
        flaechendaten: currentField.flaechendaten || '',
        flugweg: currentField.flugweg || '',
        grundstueck_id: currentField.grundstueck_id || '',
        mission_settings: Object.assign({ drone_type: droneType || 'Mavic 3T', kmz_params_active: true }, settings)
      };
    }

    async function downloadJson() {
      const field = exportFieldPayload();
      if (!field || triplets(field.flugweg).length < 2) {
        setWarn('JSON braucht einen Flugpfad mit mindestens zwei Wegpunkten.');
        return;
      }
      if (!String(field.feld_id || '').trim()) {
        setWarn('JSON braucht die Feldkatalognummer. Zuerst speichern oder die Nummer eintragen.');
        return;
      }
      const btn = document.getElementById('jsonDownBtn');
      if (btn) btn.disabled = true;
      setWarn('');
      document.getElementById('meta').textContent = 'JSON wird in den Download-Ordner gelegt …';
      try {
        const res = await fetch('api_mission_export.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          credentials: 'same-origin',
          body: JSON.stringify({ action: 'export_uav_json', field: field, kat: !!window.RKRK_KAT })
        });
        const data = await readJson(res);
        if (!data.ok || !data.payload) throw new Error(data.detail || data.error || 'JSON nicht erzeugt');
        downloadBlob(data.filename || 'mission.json', new Blob([JSON.stringify(data.payload, null, 2)], { type: 'application/json' }));
        document.getElementById('meta').textContent = 'JSON liegt im Download-Ordner: ' + (data.filename || 'mission.json');
      } catch (err) {
        setWarn(err.message || 'JSON nicht geladen');
      } finally {
        if (btn) btn.disabled = false;
      }
    }

    async function saveChangedFiles() {
      const nameInput = document.getElementById('newName');
      let name = nameInput ? nameInput.value.trim() : '';
      if (!name && currentField && currentField.name) {
        name = String(currentField.name).trim();
        if (nameInput) nameInput.value = name;
      }
      if (!name) {
        createPhase = 'name';
        setWarn('Zuerst den Feldnamen eintragen. Speichern schreibt dann JSON und KMZ.');
        renderDraft();
        const input = document.getElementById('newName');
        if (input) input.focus();
        return;
      }
      if (triplets(draftPath).length < 2 && currentField && triplets(currentField.flugweg || '').length >= 2) {
        beginEdit();
        await loadMissionJson(currentField);
      }
      if (triplets(draftPath).length < 2) {
        setWarn('Speichern braucht einen Flugpfad mit mindestens zwei Wegpunkten.');
        return;
      }
      saveCreate();
    }

    async function downloadKmz() {
      const field = exportFieldPayload();
      if (!field || triplets(field.flugweg).length < 2) {
        setWarn('KMZ braucht einen Flugpfad mit mindestens zwei Wegpunkten.');
        return;
      }
      const btn = document.getElementById('kmzBtn');
      if (btn) btn.disabled = true;
      setWarn('');
      document.getElementById('meta').textContent = 'KMZ wird in den Download-Ordner gelegt …';
      try {
        const res = await fetch('api_mission_export.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          credentials: 'same-origin',
          body: JSON.stringify({ action: 'export_dji_kmz', field: field, kat: !!window.RKRK_KAT })
        });
        const data = await readJson(res);
        if (!data.ok || !data.kmz_base64) throw new Error(data.detail || data.error || 'KMZ nicht erzeugt');
        const bin = atob(String(data.kmz_base64));
        const bytes = new Uint8Array(bin.length);
        for (let i = 0; i < bin.length; i++) bytes[i] = bin.charCodeAt(i);
        downloadBlob(data.filename || 'mission.kmz', new Blob([bytes], { type: 'application/vnd.google-earth.kmz' }));
        let extra = '';
        if (/DEBUG/i.test(String(window.location.search || '')) && data.template_kml) {
          const kmlName = String(data.filename || 'mission.kmz').replace(/\.kmz$/i, '.kml');
          downloadBlob(kmlName, new Blob([String(data.template_kml)], { type: 'application/vnd.google-earth.kml+xml' }));
          extra = ' Dazu ' + kmlName + '.';
        }
        document.getElementById('meta').textContent = 'KMZ liegt im Download-Ordner: ' + (data.filename || 'mission.kmz') + extra;
      } catch (err) {
        setWarn(err.message || 'KMZ nicht geladen');
      } finally {
        if (btn) btn.disabled = false;
      }
    }

    function setCreateChrome(on) {
      document.getElementById('undoBtn').hidden = !on || createPhase === 'check';
      document.getElementById('nextBtn').hidden = !on;
      const opt = document.getElementById('optBtn');
      if (opt) opt.hidden = !on || (createPhase !== 'route' && createPhase !== 'name');
      document.getElementById('height').hidden = on && createPhase !== 'check';
      const routePhase = createPhase === 'route';
      document.getElementById('heightEdit').hidden = !on || createPhase === 'check' || routePhase;
      const heightCaption = document.getElementById('heightCaption');
      if (heightCaption) {
        heightCaption.hidden = !on || createPhase === 'check' || routePhase;
        heightCaption.textContent = createPhase === 'wp' ? 'Flughöhe dieses Punkts' : 'Flughöhe der Route';
      }
      const nameBlock = document.getElementById('nameBlock');
      if (nameBlock) nameBlock.hidden = !on || (createPhase !== 'name' && createPhase !== 'confirm' && createPhase !== 'check');
      paintParkBox();
      paintRcGimbal();
      document.getElementById('routeTools').hidden = !on || createPhase !== 'route';
      const modeBtn = document.getElementById('rcModeToggle');
      if (modeBtn) modeBtn.hidden = !(on || currentField);
      paintStartSpeedBox();
      document.getElementById('wpBox').hidden = !on || createPhase !== 'wp';
      document.getElementById('wpHint').hidden = !on || createPhase !== 'wp';
      paintPointBox();
      const localOn = !window.RKRK_KAT && on && fieldHasExportPath() && (createPhase === 'route' || createPhase === 'wp' || createPhase === 'name' || createPhase === 'check');
      const localKml = document.getElementById('localKmlBtn');
      const localJson = document.getElementById('localJsonBtn');
      if (localKml) localKml.hidden = !localOn;
      if (localJson) localJson.hidden = !localOn;
      if (localOn) document.getElementById('nextBtn').hidden = true;
      const backBtn = document.getElementById('back');
      if (backBtn && !window.RKRK_KAT) backBtn.hidden = true;
      document.getElementById('prevBtn').hidden = !on || createPhase === 'check';
      paintEditButton();
      paintRenewPicButton();
      paintCloseSave();
      paintEntry();
      document.getElementById('stepBar').hidden = !on;
      document.getElementById('back').textContent = on ? 'Abbrechen' : 'Felder';
      if (on && createPhase !== 'check') {
        const pics = document.getElementById('picThumbs');
        if (pics) {
          pics.hidden = true;
          pics.replaceChildren();
        }
      }
      paintDirection();
      paintPlaceBox();
      const tfBox = document.getElementById('debugTfBox');
      if (tfBox) {
        const dir = rcTfDirectory();
        tfBox.hidden = !(rcDebugOn() && dir && (createPhase === 'name' || createPhase === 'confirm' || createPhase === 'check'));
        const dirEl = document.getElementById('debugTfDir');
        if (dirEl && dir) dirEl.textContent = 'Zielordner: ' + dir;
        const input = document.getElementById('debugTfId');
        if (input && !tfBox.hidden && !String(input.value || '').trim()) input.value = rcNextTfId();
      }
    }

    function ringPlaceKey() {
      return draftRing.map((p) => Number(p[0]).toFixed(5) + ',' + Number(p[1]).toFixed(5)).join(';');
    }

    function paintPlaceBox() {
      const box = document.getElementById('placeBox');
      if (!box) return;
      const saveStep = createPhase === 'name' || createPhase === 'confirm' || createPhase === 'check';
      const show = createMode && draftRing.length >= 3 && (saveStep || createPhase !== 'check');
      box.hidden = !show;
      const repeatIds = createPhase === 'start' || createPhase === 'route' || createPhase === 'wp';
      const nameBlock = document.getElementById('nameBlock');
      const feldBlock = document.getElementById('feldIdBlock');
      const gisBlock = document.getElementById('gisBlock');
      if (nameBlock) nameBlock.hidden = !saveStep;
      if (feldBlock) feldBlock.hidden = window.RKRK_KAT ? (saveStep ? false : repeatIds) : true;
      if (gisBlock) gisBlock.hidden = saveStep ? false : repeatIds;
      ['newName', 'feldIdInput', 'gisInput'].forEach((id) => {
        const el = document.getElementById(id);
        if (el) el.disabled = false;
      });
      if (!show) return;
      const place = pendingPlace || {};
      const line = document.getElementById('placeLine');
      if (placeBusy && (window.RKRK_KAT ? !place.feld_id : !(place.jg || place.gemeinde))) {
        line.textContent = window.RKRK_KAT
          ? 'Jagdgebiet, Gemeinde und Nummer werden ermittelt …'
          : 'Jagdgebiet und Gemeinde werden ermittelt …';
      } else {
        line.textContent = [
          place.jg_name || place.jg || 'kein Jagdgebiet',
          place.gemeinde_name || place.gemeinde || 'keine Gemeinde'
        ].filter(Boolean).join(' · ');
      }
      if (gisSuggest && !gisTouched) draftGis = gisSuggest;
      const feldInput = document.getElementById('feldIdInput');
      if (window.RKRK_KAT && feldInput && document.activeElement !== feldInput) feldInput.value = draftFeldId;
      const input = document.getElementById('gisInput');
      if (input && document.activeElement !== input) input.value = draftGis;
      const note = document.getElementById('gisNote');
      const apply = document.getElementById('gisApply');
      if (apply) apply.hidden = true;
      if (gisBusy && !gisSuggest) {
        note.textContent = 'CadastralWebMap wird gelesen …';
      } else if (gisSuggest && draftGis === gisSuggest) {
        note.textContent = 'CadastralWebMap: ' + gisSuggest + '. Hier änderbar.';
      } else if (!draftGis) {
        note.textContent = 'GIS-Nummer fehlt. Sonst hier eintragen.';
      } else {
        note.textContent = '';
      }
    }

    function pointInRing(x, y, ring) {
      let inside = false;
      for (let i = 0, j = ring.length - 1; i < ring.length; j = i++) {
        const xi = ring[i][0];
        const yi = ring[i][1];
        const xj = ring[j][0];
        const yj = ring[j][1];
        if ((yi > y) !== (yj > y) && x < ((xj - xi) * (y - yi)) / ((yj - yi) || 1e-15) + xi) inside = !inside;
      }
      return inside;
    }

    function gisSamplePoints(ring) {
      const pts = ring.filter((p) => p && Number.isFinite(p[0]) && Number.isFinite(p[1]));
      if (pts.length < 3) return [];
      let area2 = 0;
      let cx = 0;
      let cy = 0;
      let minX = Infinity;
      let minY = Infinity;
      let maxX = -Infinity;
      let maxY = -Infinity;
      for (let i = 0, j = pts.length - 1; i < pts.length; j = i++) {
        const cross = pts[j][0] * pts[i][1] - pts[i][0] * pts[j][1];
        area2 += cross;
        cx += (pts[j][0] + pts[i][0]) * cross;
        cy += (pts[j][1] + pts[i][1]) * cross;
        minX = Math.min(minX, pts[i][0]);
        minY = Math.min(minY, pts[i][1]);
        maxX = Math.max(maxX, pts[i][0]);
        maxY = Math.max(maxY, pts[i][1]);
      }
      const inside = [];
      if (Math.abs(area2) > 1e-14) {
        const c = [cx / (3 * area2), cy / (3 * area2)];
        if (pointInRing(c[0], c[1], pts)) inside.push(c);
      }
      for (let r = 0; r < 5; r++) {
        for (let c = 0; c < 5; c++) {
          const x = minX + (maxX - minX) * ((c + 0.5) / 5);
          const y = minY + (maxY - minY) * ((r + 0.5) / 5);
          if (pointInRing(x, y, pts)) inside.push([x, y]);
        }
      }
      return inside.slice(0, 9);
    }

    async function cadastralParcelNumber(lon, lat) {
      const pad = 0.00045;
      const url = 'https://api3.geo.admin.ch/rest/services/api/MapServer/identify'
        + '?geometryType=esriGeometryPoint'
        + '&geometry=' + encodeURIComponent(Number(lon).toFixed(6) + ',' + Number(lat).toFixed(6))
        + '&imageDisplay=400,400,96'
        + '&mapExtent=' + encodeURIComponent([lon - pad, lat - pad, lon + pad, lat + pad].map((n) => Number(n).toFixed(6)).join(','))
        + '&tolerance=1'
        + '&layers=' + encodeURIComponent('all:ch.kantone.cadastralwebmap-farbe')
        + '&returnGeometry=false&sr=4326&lang=de&limit=1';
      const res = await fetch(url, { cache: 'no-store' });
      if (!res.ok) return '';
      const data = await res.json();
      const attrs = data && data.results && data.results[0] && data.results[0].attributes;
      return attrs ? String(attrs.number || '').trim() : '';
    }

    async function suggestGisNumber(ring) {
      const samples = gisSamplePoints(ring);
      const counts = new Map();
      let centroidNumber = '';
      for (let i = 0; i < samples.length; i++) {
        try {
          const num = await cadastralParcelNumber(samples[i][0], samples[i][1]);
          if (!num) continue;
          if (i === 0) centroidNumber = num;
          counts.set(num, (counts.get(num) || 0) + 1);
        } catch (_) {}
      }
      let best = '';
      let bestN = 0;
      counts.forEach((n, num) => {
        if (n > bestN || (n === bestN && num === centroidNumber)) {
          best = num;
          bestN = n;
        }
      });
      return best;
    }

    function resetPlaceInfo() {
      pendingPlace = null;
      draftGis = '';
      draftFeldId = '';
      feldIdTouched = false;
      gisTouched = false;
      gisSuggest = '';
      placeBusy = false;
      gisBusy = false;
      placeFetchedKey = '';
      gisFetchedKey = '';
      placeToken += 1;
    }

    function schedulePlaceInfo() {
      if (placeTimer) clearTimeout(placeTimer);
      placeTimer = setTimeout(() => {
        placeTimer = null;
        loadPlaceInfo();
      }, 300);
    }

    async function loadPlaceInfo() {
      paintPlaceBox();
      if (!createMode || draftRing.length < 3 || createPhase === 'check') return;
      const key = ringPlaceKey();
      const token = ++placeToken;
      if (key !== placeFetchedKey) {
        placeBusy = true;
        paintPlaceBox();
        try {
          const res = await fetch(apiUrl(), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({
              mode: 'preview',
              token: reserveToken(),
              mdb: missionKey(),
              field: {
                allow_missing_place: true,
                existing: !!editId,
                name: 'Vorschau',
                flaechendaten: formatPairs(draftRing),
                startplatz: draftStart ? formatPairs([draftStart]) : '',
                feld_id: editId ? '' : (draftFeldId || '')
              }
            })
          });
          const data = await readJson(res);
          if (token !== placeToken) return;
            if (data.ok) {
            if (data.reserve_note) setWarn(String(data.reserve_note));
            if (editId && currentField && !feldIdTouched) {
              data.feld_id = currentField.feld_id || data.feld_id;
              data.number = currentField.number || data.number;
            }
            pendingPlace = data;
            if (window.RKRK_KAT && !feldIdTouched) draftFeldId = data.feld_id || '';
            if (!window.RKRK_KAT) draftFeldId = '';
            placeFetchedKey = key;
            placeConfirmed = false;
          }
        } catch (_) {}
        if (token !== placeToken) return;
        placeBusy = false;
      }
      if (key !== gisFetchedKey) {
        gisBusy = true;
        gisSuggest = '';
        paintPlaceBox();
        const num = await suggestGisNumber(draftRing.slice());
        if (token !== placeToken) return;
        gisSuggest = num;
        if (num && !gisTouched) draftGis = num;
        gisFetchedKey = key;
        gisBusy = false;
      }
      paintPlaceBox();
    }

    function stepIndex() {
      const phase = createPhase === 'confirm' ? 'name' : createPhase;
      const index = STEPS.findIndex((step) => step.id === phase);
      return index < 0 ? 0 : index;
    }

    function canEnter(phase) {
      if (phase === 'ring') return true;
      if (phase === 'start') return draftRing.length >= 3;
      if (phase === 'route') return draftRing.length >= 3 && !!draftStart;
      if (phase === 'wp' || phase === 'name') return draftRing.length >= 3 && !!draftStart && !!draftPath;
      if (phase === 'check') return !!savedCheck;
      return false;
    }

    function paintSteps() {
      const bar = document.getElementById('stepBar');
      const prev = document.getElementById('prevBtn');
      if (!bar || !prev) return;
      bar.hidden = !createMode;
      bar.replaceChildren();
      const current = stepIndex();
      STEPS.forEach((step, index) => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'step' + (index === current ? ' is-on' : '') + (index < current ? ' is-done' : '');
        const no = document.createElement('span');
        no.className = 'step-no';
        no.textContent = String(index + 1);
        const label = document.createElement('span');
        label.className = 'step-name';
        label.textContent = step.title;
        btn.append(no, label);
        btn.title = step.title;
        btn.setAttribute('aria-label', 'Schritt ' + (index + 1) + ' ' + step.title);
        btn.disabled = !!savedCheck ? step.id !== 'check' : (index > current && !canEnter(step.id));
        btn.addEventListener('click', () => goStep(step.id));
        bar.appendChild(btn);
      });
      prev.hidden = !createMode;
      prev.textContent = current === 0 ? 'Abbrechen' : 'Zurück';
    }

    function clearRoute() {
      draftPath = '';
      routeCoverageReady = false;
      generatedPath = '';
      terrainFor = '';
      terrainDelta = [];
      draftWp = 0;
      draftDist = 0;
      draftPathHeight = 0;
      entryMode = 'second';
    }

    function goStep(phase) {
      if (!createMode || !canEnter(phase)) return;
      if (savedCheck && phase !== 'check') return;
      if ((phase === 'route' || phase === 'wp' || phase === 'name') && !draftPath) {
        computeRoute();
        return;
      }
      selectedPoint = null;
      createPhase = phase;
      setWarn('');
      renderDraft();
    }

    function goBack() {
      const index = stepIndex();
      if (createPhase === 'confirm') {
        createPhase = 'name';
        setWarn('');
        renderDraft();
        return;
      }
      if (index <= 0) {
        cancelCreate();
        return;
      }
      goStep(STEPS[index - 1].id);
    }

    function goForward() {
      if (createPhase === 'ring') {
        if (draftRing.length >= 3) goStep('start');
        return;
      }
      if (createPhase === 'start') {
        computeRoute();
        return;
      }
      if (createPhase === 'route') {
        if (draftPath) goStep('wp');
        else computeRoute();
        return;
      }
      if (createPhase === 'wp') {
        if (draftPath) goStep('name');
        return;
      }
      if (createPhase === 'check') {
        finishCheck();
        return;
      }
      if (createPhase === 'confirm') placeConfirmed = true;
      if (!window.RKRK_KAT) return;
      saveCreate();
    }

    function paintWpStrip(pts) {
      const host = document.getElementById('wpDots');
      if (!host) return;
      const key = pts.map((p) => Number(p[2]).toFixed(1)).join('|');
      if (host.dataset.key !== key) {
        host.dataset.key = key;
        host.replaceChildren();
        pts.forEach((pt, i) => {
          const btn = document.createElement('button');
          btn.type = 'button';
          btn.className = 'wp-dot';
          btn.dataset.i = String(i);
          const ball = document.createElement('span');
          ball.className = 'ball';
          ball.textContent = i === 0 ? 'S' : String(i + 1);
          const alt = document.createElement('span');
          alt.className = 'alt';
          const h = Number(pt[2]);
          alt.textContent = (Number.isFinite(h) ? h.toFixed(1) : '–') + 'm';
          btn.append(ball, alt);
          host.appendChild(btn);
        });
      }
      host.querySelectorAll('.wp-dot').forEach((btn) => {
        btn.classList.toggle('is-on', Number(btn.dataset.i) === wpIndex);
      });
      const on = host.querySelector('.wp-dot.is-on');
      if (!on) return;
      const left = on.offsetLeft;
      const right = left + on.offsetWidth;
      if (left < host.scrollLeft) host.scrollLeft = Math.max(0, left - 4);
      else if (right > host.scrollLeft + host.clientWidth) host.scrollLeft = right - host.clientWidth + 4;
    }

    function renderDraft() {
      ensureMap();
      polygonSource.clear();
      pathSource.clear();
      startSource.clear();
      vertexSource.clear();
      if (coverageSource) coverageSource.clear();
      if (heightSource) heightSource.clear();
      if (aglWarnSource) aglWarnSource.clear();
      if (parkSource) parkSource.clear();
      const ring = draftRing.map((p) => ol.proj.fromLonLat(p));
      ring.forEach((xy, index) => {
        const feature = new ol.Feature(new ol.geom.Point(xy));
        if (selectedPoint && selectedPoint.kind === 'ring' && selectedPoint.index === index) {
          feature.setStyle(new ol.style.Style({
            image: new ol.style.Circle({
              radius: 11,
              fill: new ol.style.Fill({ color: '#f5d90a' }),
              stroke: new ol.style.Stroke({ color: '#1a1a1a', width: 3 })
            })
          }));
        }
        vertexSource.addFeature(feature);
      });
      if (createPhase === 'ring' && draftRing.length >= 2) {
        const closed = draftRing.length >= 3;
        const count = closed ? draftRing.length : draftRing.length - 1;
        for (let i = 0; i < count; i++) {
          const mid = midpoint(draftRing[i], draftRing[(i + 1) % draftRing.length]);
          const plus = new ol.Feature(new ol.geom.Point(ol.proj.fromLonLat(mid)));
          plus.setStyle(new ol.style.Style({
            text: new ol.style.Text({
              text: '+',
              font: 'bold 16px sans-serif',
              fill: new ol.style.Fill({ color: '#6b7280' }),
              backgroundFill: new ol.style.Fill({ color: '#ffffff' }),
              padding: [0, 4, 0, 4]
            })
          }));
          vertexSource.addFeature(plus);
        }
      }
      if (ring.length >= 2) {
        const line = ring.slice();
        if (ring.length >= 3) line.push(ring[0].slice());
        polygonSource.addFeature(new ol.Feature(
          ring.length >= 3 ? new ol.geom.Polygon([line]) : new ol.geom.LineString(line)
        ));
      }
      if (draftStart) {
        const startFeature = new ol.Feature(new ol.geom.Point(ol.proj.fromLonLat(draftStart)));
        if (selectedPoint && selectedPoint.kind === 'start') {
          startFeature.setStyle(new ol.style.Style({
            image: new ol.style.Circle({
              radius: 14,
              fill: new ol.style.Fill({ color: '#f5d90a' }),
              stroke: new ol.style.Stroke({ color: '#1a1a1a', width: 3 })
            })
          }));
        }
        startSource.addFeature(startFeature);
      }
      const routePts = triplets(draftPath);
      const route = routePts.map((p) => ol.proj.fromLonLat(p));
      if (route.length >= 2) {
        pathSource.addFeature(new ol.Feature(new ol.geom.LineString(route)));
      }
      paintGreyTurns(routePts);
      if (routePts.length && (createPhase === 'route' || createPhase === 'wp' || createPhase === 'name' || createPhase === 'check')) {
        routePts.forEach((p, index) => {
          const mark = new ol.Feature(new ol.geom.Point(ol.proj.fromLonLat(p)));
          const on = selectedPoint && selectedPoint.kind === 'path' && selectedPoint.index === index;
          const yaw = yawDisplayForRoutePoint(routePts, index);
        mark.set('yawMode', yaw.mode);
        mark.set('yawAngleDeg', yaw.angle);
        mark.set('yawNextLegDeg', yaw.next);
          mark.set('pathDeviates', routePointDeviates(index + 1));
          mark.setStyle((feature, resolution) => routePointStyle(index, on, feature, resolution));
          vertexSource.addFeature(mark);
        });
      }
      if (createPhase === 'route' || createPhase === 'wp') {
        for (let i = 0; i + 1 < routePts.length; i++) {
          const mid = midpoint(routePts[i], routePts[i + 1]);
          const plus = new ol.Feature(new ol.geom.Point(ol.proj.fromLonLat(mid)));
          plus.setStyle(new ol.style.Style({
            text: new ol.style.Text({
              text: '+',
              font: 'bold 16px sans-serif',
              fill: new ol.style.Fill({ color: '#2f6dff' }),
              backgroundFill: new ol.style.Fill({ color: '#ffffff' }),
              padding: [0, 4, 0, 4]
            })
          }));
          vertexSource.addFeature(plus);
        }
      }
      paintCoverage(routePts);
      paintHeights(routePts);
      paintPark();
      paintPoi();
      scheduleAglWarn();
      if (routePts.length && terrainFor !== draftPath && (createPhase === 'route' || createPhase === 'wp' || createPhase === 'name' || createPhase === 'check' || showRouteHeights)) loadTerrainDelta();
      const pathLayer = routeLayer();
      if (pathLayer) pathLayer.setVisible(route.length >= 2);
      document.getElementById('idline').textContent = '';
      paintSteps();
      document.getElementById('hVal').textContent = draftHeight + ' m';
      const name = document.getElementById('newName').value.trim();
      const next = document.getElementById('nextBtn');
      const undo = document.getElementById('undoBtn');
      setCreateChrome(true);
      if (createPhase === 'ring') {
        const need = Math.max(0, 3 - draftRing.length);
        document.getElementById('meta').textContent = draftRing.length
          ? (draftRing.length + (draftRing.length === 1 ? ' Ecke gesetzt' : ' Ecken gesetzt'))
          : '';
        setTask(need > 0
          ? ('Tippe ' + (need === 3 ? 'mindestens 3 Ecken' : ('noch ' + need + (need === 1 ? ' Ecke' : ' Ecken'))) + ' auf die Karte. Ein Tipp auf eine Ecke wählt sie.')
          : 'Fläche steht. Prüfe Jagdgebiet und GIS, dann Startpunkt setzen. Ecken lassen sich noch verschieben.');
        next.textContent = 'Startpunkt setzen';
        next.disabled = need > 0;
      } else if (createPhase === 'start') {
        document.getElementById('meta').textContent = draftStart ? 'Startpunkt gesetzt' : 'Noch kein Startpunkt';
        setTask(pickPark
          ? 'Tippe jetzt den Parkplatz. Er gilt nur, wenn er nicht auf dem Startpunkt liegt.'
          : (draftStart
            ? 'Startpunkt steht ausserhalb der Fläche. Weiter rechnet den Flugpfad. Parkplatz nur bei einem anderen Ort.'
            : 'Tippe den Startpunkt auf die Karte, ausserhalb der Fläche. Das fügt keine Ecke hinzu.'));
        next.textContent = 'Flugpfad rechnen';
        next.disabled = !draftStart;
      } else if (createPhase === 'route') {
        const dist = formatDist(String(draftDist));
        document.getElementById('meta').textContent = draftPath
          ? ('Flugpfad · ' + draftWp + ' WP' + (dist ? ' · ' + dist : ''))
          : '';
        setTask(draftPath
          ? 'Prüfe Abdeckung, Anflug und Flugrichtung. + und − ändern die Flughöhe und rechnen neu. Danach die Wegpunkte.'
          : 'Der Flugpfad wird gerechnet. Warte, oder tippe Neu rechnen.');
        next.textContent = draftPath ? 'Prüfen' : 'Neu rechnen';
        next.disabled = !(draftPath || (draftRing.length >= 3 && draftStart));
      } else if (createPhase === 'wp') {
        const pts = triplets(draftPath);
        if (wpIndex >= pts.length) wpIndex = Math.max(0, pts.length - 1);
        const caption = routeHeightCaption(wpIndex);
        document.getElementById('hVal').textContent = caption.main || (draftHeight + ' m');
        const wpHint = document.getElementById('wpHint');
        if (wpHint) wpHint.textContent = caption.paren || '';
        document.getElementById('meta').textContent = 'Wegpunkt ' + (wpIndex + 1) + ' von ' + pts.length;
        paintWpStrip(pts);
        setTask('Die Zahl ist die programmierte Flughöhe nur für diesen Punkt. + und − ändern sie.');
        document.getElementById('wpHint').textContent = '';
        next.textContent = 'Zum Speichern';
        next.disabled = pts.length < 2;
      } else if (createPhase === 'name') {
        const local = !window.RKRK_KAT;
        document.getElementById('meta').textContent = name ? '' : 'Feldname fehlt noch';
        setTask(local
          ? (name
            ? 'Ohne ?KAT bleibt das Feld auf diesem Gerät. KML oder JSON gehen in den Downloadordner.'
            : 'Trag den Feldnamen ein. Danach KML oder JSON herunterladen.')
          : (name
            ? 'Speichern schreibt Feldkatalog, JSON, KMZ und die Bilder auf den Server. Ein neues Feld bekommt den Status NEU.'
            : 'Trag den Feldnamen ein. Danach Speichern.'));
        next.textContent = 'Speichern';
        next.disabled = name === '';
        if (local) {
          next.hidden = true;
          const kml = document.getElementById('localKmlBtn');
          const json = document.getElementById('localJsonBtn');
          if (kml) kml.disabled = name === '';
          if (json) json.disabled = name === '';
        }
      } else if (createPhase === 'confirm') {
        const place = pendingPlace || {};
        const gaps = [];
        if (!place.jg) gaps.push('kein Jagdgebiet');
        if (!place.gemeinde) gaps.push('keine Gemeinde');
        const found = [];
        if (place.jg) found.push(place.jg_name || place.jg);
        if (place.gemeinde) found.push(place.gemeinde_name || place.gemeinde);
        const nummer = window.RKRK_KAT && place.feld_id ? (' Nummer ' + place.feld_id + '.') : '';
        document.getElementById('meta').textContent = (found.length ? found.join(' · ') + '. ' : '') + nummer;
        setTask('Es fehlt ' + gaps.join(' und ') + '. Trotzdem speichern, oder Zurück und die Fläche prüfen.');
        setWarn(gaps.join(', ') + '. Trotzdem speichern?');
        document.getElementById('back').textContent = 'Zurück';
        next.textContent = 'Trotzdem speichern';
        next.disabled = false;
      } else if (createPhase === 'check') {
        const info = savedCheck || {};
        const place = [info.jg_name || info.jg || 'kein Jagdgebiet', info.gemeinde_name || info.gemeinde || 'keine Gemeinde'].join(' · ');
        const dist = formatDist(String(info.dist || draftDist || ''));
        document.getElementById('idline').textContent = ((info.feld_id || '') + '  ' + (info.name || '')).trim();
        document.getElementById('meta').textContent = [place, (info.drone || droneType || ''), (info.height || draftHeight) + ' m', (info.wp || draftWp) + ' WP', dist].filter(Boolean).join(' · ');
        setTask(info.saveNote || 'JSON oder KMZ legt die Datei auf dieses Gerät. Abschliessen speichert dieses Feld nochmals und erneuert die Bilder. Fertig schliesst diesen Lauf. Zurück heisst Abbrechen.');
        if (info.saveOk === false) setWarn(info.saveNote || 'JSON oder KMZ fehlt.');
        document.getElementById('height').textContent = (info.height || draftHeight) + ' m';
        next.textContent = 'Fertig';
        next.disabled = false;
      }
      const speedInput = document.getElementById('speedVal');
      if (speedInput && document.activeElement !== speedInput) speedInput.value = clampHalfSpeed(draftSpeed).toFixed(1);
      const altInput = document.getElementById('missionAltVal');
      if (altInput && document.activeElement !== altInput) altInput.value = String(draftHeight);
      if (createPhase === 'wp') setRcExpertMode(expertMode);
      else paintExpert();
      undo.disabled = draftRing.length === 0 && !draftStart;
      map.updateSize();
      schedulePlaceInfo();
      if (window.RkrkHelp) RkrkHelp.setView();
      scheduleRcWaypointDebugTable();
    }

    const KNONAU = [8.4617, 47.2236];

    function jumpTo(lonlat, zoom) {
      if (!map) return;
      map.getView().animate({
        center: ol.proj.fromLonLat(lonlat),
        zoom: zoom,
        duration: 250
      });
    }

    function centerOnDeviceOrKnonau() {
      jumpTo(KNONAU, 14);
      if (!navigator.geolocation) return;
      navigator.geolocation.getCurrentPosition((pos) => {
        if (!createMode || draftRing.length || draftStart) return;
        const lon = Number(pos.coords.longitude);
        const lat = Number(pos.coords.latitude);
        if (!Number.isFinite(lon) || !Number.isFinite(lat)) return;
        jumpTo([lon, lat], 15);
      }, () => {}, { enableHighAccuracy: true, timeout: 4000, maximumAge: 120000 });
    }

    function startCreate() {
      draftEpoch += 1;
      createMode = true;
      createPhase = 'ring';
      draftRing = [];
      draftStart = null;
      draftPath = '';
      routeCoverageReady = false;
      draftWp = 0;
      draftDist = 0;
      draftPathHeight = 0;
      missionHeightUserSet = false;
      missionHeightFromField = false;
      draftHeight = missionAltitudeDefaultM(droneType);
      draftSpeed = 5;
      draftStartSpeedActive = false;
      draftStartSpeed = (window.RkrkField && RkrkField.easyMissionDefaults && Number(RkrkField.easyMissionDefaults().start_speed_m_s) > 0)
        ? Number(RkrkField.easyMissionDefaults().start_speed_m_s)
        : 5;
      draftDirection = -1;
      draftPark = null;
      pickPark = false;
      draftPois = [];
      draftPoi = null;
      pickMissionPoi = false;
      generatedPath = '';
      entryMode = 'second';
      wpIndex = 0;
      editId = '';
      editMission = null;
      terrainFor = '';
      terrainDelta = [];
      savedCheck = null;
      placeConfirmed = false;
      resetPlaceInfo();
      currentField = null;
      activeId = '';
      rememberField('');
      selectedPoint = null;
      document.getElementById('newName').value = '';
      setWarn('');
      listView.hidden = true;
      mapView.hidden = false;
      ensureMap();
      centerOnDeviceOrKnonau();
      renderDraft();
    }

    function cancelCreate() {
      releaseReservation();
      createMode = false;
      createPhase = 'ring';
      draftRing = [];
      draftStart = null;
      draftPath = '';
      routeCoverageReady = false;
      draftWp = 0;
      draftDist = 0;
      draftPathHeight = 0;
      draftDirection = -1;
      draftPark = null;
      pickPark = false;
      draftPois = [];
      draftPoi = null;
      pickMissionPoi = false;
      generatedPath = '';
      editId = '';
      editMission = null;
      savedCheck = null;
      placeConfirmed = false;
      resetPlaceInfo();
      setWarn('');
      setCreateChrome(false);
      const nameBlockReset = document.getElementById('nameBlock');
      if (nameBlockReset) nameBlockReset.hidden = true;
      showList();
    }

    function undoDraft() {
      placeConfirmed = false;
      if (createPhase === 'confirm') {
        createPhase = draftPath ? 'route' : 'start';
        setWarn('');
        renderDraft();
        return;
      }
      if (createPhase === 'wp') {
        createPhase = 'route';
        setWarn('');
        renderDraft();
        return;
      }
      if (createPhase === 'route') {
        clearRoute();
        createPhase = 'start';
        setWarn('');
        renderDraft();
        return;
      }
      if (createPhase === 'start' && draftStart) {
        draftStart = null;
      } else if (draftRing.length) {
        draftRing.pop();
        if (draftRing.length < 3) createPhase = 'ring';
      }
      setWarn('');
      renderDraft();
    }

    function changeHeight(delta) {
      if (createPhase === 'wp') {
        const pts = triplets(draftPath);
        if (!pts[wpIndex]) return;
        if (expertMode) {
          const row = overrideRow(wpIndex + 1) || {};
          if (flagOn(row.use_mission_height)) return;
          const base = Number(row.agl_m != null ? row.agl_m : pts[wpIndex][2]);
          const next = Math.min(500, Math.max(1, (Number.isFinite(base) ? base : draftHeight) + delta));
          ensureEditOverrides()[String(wpIndex + 1)] = Object.assign({}, row, {
            use_mission_height: false,
            agl_m: next
          });
          pts[wpIndex][2] = next;
          draftPath = formatTriplets(pts);
          renderDraft();
          return;
        }
        pts[wpIndex][2] = Math.min(120, Math.max(20, Number(pts[wpIndex][2]) + delta));
        draftPath = formatTriplets(pts);
        renderDraft();
        return;
      }
      draftHeight = Math.min(120, Math.max(20, draftHeight + delta));
      missionHeightUserSet = true;
      document.getElementById('hVal').textContent = draftHeight + ' m';
      if (createPhase === 'route') computeRoute();
    }

    function draftField() {
      const gimbalOn = !!(document.getElementById('rcGimbalAngleActive') && document.getElementById('rcGimbalAngleActive').checked);
      const gimbalRaw = Number(document.getElementById('rcGimbalPitch') && document.getElementById('rcGimbalPitch').value);
      const gimbalPitch = gimbalOn && Number.isFinite(gimbalRaw) ? Math.max(-90, Math.min(30, Math.round(gimbalRaw))) : -90;
      const overrides = (editMission && editMission.waypoint_overrides && typeof editMission.waypoint_overrides === 'object')
        ? JSON.parse(JSON.stringify(editMission.waypoint_overrides))
        : {};
      return {
        name: document.getElementById('newName').value.trim() || 'Feld',
        std_flughoehe: String(draftHeight),
        startplatz: formatPairs([draftStart]),
        parkplatz: (draftPark && (!draftStart || !same6(draftPark, draftStart))) ? formatPairs([draftPark]) : '',
        flaechendaten: formatPairs(draftRing),
        zentrum: formatCenter(draftRing),
        flugweg: draftPath,
        wegpunkte: String(draftWp || 0),
        distanz: String(draftDist || 0),
        flugzeit: '0',
        grundstueck_id: draftGis,
        mission_settings: Object.assign({}, (editId && editMission && typeof editMission === 'object') ? editMission : {}, {
          drone_type: droneType || (editMission && editMission.drone_type) || 'Mavic 3T',
          altitude_m: draftHeight,
          flight_direction_deg: draftDirection,
          speed_m_s: draftSpeed,
          start_speed_active: !!(expertMode && draftStartSpeedActive),
          start_speed_m_s: (expertMode && draftStartSpeedActive && Number(draftStartSpeed) > 0) ? Number(draftStartSpeed) : draftSpeed,
          pois: draftPois.map((poi) => ({ longitude: Number(poi.longitude), latitude: Number(poi.latitude), height: Number(poi.height) || 0 })),
          poi: draftPois[0] && Number.isFinite(Number(draftPois[0].longitude)) ? draftPois[0] : null,
          gimbal_mode: gimbalOn ? 'Jeden Wegpunkt festlegen' : 'Manuell',
          gimbal_pitch: gimbalPitch,
          simple_mode: true,
          waypoint_overrides: overrides
        })
      };
    }

    async function computeRoute() {
      if (draftRing.length < 3 || !draftStart) return;
      const epoch = draftEpoch;
      const next = document.getElementById('nextBtn');
      next.disabled = true;
      document.getElementById('meta').textContent = 'Flugpfad wird berechnet …';
      setWarn('');
      try {
        const pathRes = await fetch('api_mission_export.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          credentials: 'same-origin',
          body: JSON.stringify({ action: 'compute_path', field: draftField() })
        });
        const pathData = await readJson(pathRes);
        if (epoch !== draftEpoch) return;
        if (!pathData.ok) throw new Error(pathData.detail || pathData.error || 'Flugpfad fehlgeschlagen');
        draftPath = pathData.flugweg_text || '';
        routeCoverageReady = triplets(draftPath).length >= 2;
        generatedPath = draftPath;
        entryMode = 'second';
        terrainFor = '';
        const metrics = pathData.metrics || {};
        draftWp = Number(metrics.waypoints || 0);
        draftDist = Math.round(Number(metrics.distance_m) || 0);
        draftPathHeight = draftHeight;
        createPhase = 'route';
        renderDraft();
        fitField();
      } catch (err) {
        setWarn(err.message);
        createPhase = 'start';
        renderDraft();
      }
    }

    async function saveCreate() {
      const epoch = draftEpoch;
      if (!window.RKRK_KAT) {
        setWarn('Speichern auf den Server braucht ?KAT. Ohne KAT das Feld als KML oder JSON herunterladen.');
        return;
      }
      const name = document.getElementById('newName').value.trim();
      if (draftRing.length < 3 || !draftStart || !name) return;
      const next = document.getElementById('nextBtn');
      next.disabled = true;
      setWarn('');
      const field = draftField();
      field.name = name;
      try {
        const preview = editId && currentField
          ? {
              ok: true,
              feld_id: currentField.feld_id || '',
              jg: currentField.jg || '',
              gemeinde: currentField.gemeinde || '',
              jg_name: currentField.jg_name || '',
              gemeinde_name: currentField.gemeinde_name || ''
            }
          : (pendingPlace && placeConfirmed ? pendingPlace : await (async () => {
          const previewRes = await fetch(apiUrl(), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ mode: 'preview', token: reserveToken(), mdb: missionKey(), field: Object.assign({ allow_missing_place: true, existing: !!editId }, field) })
          });
          return readJson(previewRes);
        })());
        if (epoch !== draftEpoch) return;
        if (!preview.ok) throw new Error(preview.detail || preview.error || 'Zuordnung fehlgeschlagen');
        if (editId) field.id = editId;
        pendingPlace = preview;
        if ((!preview.jg || !preview.gemeinde) && !placeConfirmed) {
          createPhase = 'confirm';
          renderDraft();
          return;
        }
        field.feld_id = (document.getElementById('feldIdInput').value || draftFeldId || preview.feld_id || '').trim();
        field.jg = preview.jg || '';
        field.gemeinde = preview.gemeinde || '';
        if (!preview.jg) field.confirm_missing_place = true;
        if (epoch !== draftEpoch) return;
        const keepStoredEditPath = !!(editId && triplets(draftPath).length >= 2);
        if (!keepStoredEditPath && (!draftPath || draftPathHeight !== draftHeight)) {
          document.getElementById('meta').textContent = 'Flugpfad wird berechnet …';
          const pathRes = await fetch('api_mission_export.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ action: 'compute_path', field: field })
          });
          const pathData = await readJson(pathRes);
          if (epoch !== draftEpoch) return;
          if (!pathData.ok) throw new Error(pathData.detail || pathData.error || 'Flugpfad fehlgeschlagen');
          draftPath = pathData.flugweg_text || '';
          generatedPath = draftPath;
          const metrics = pathData.metrics || {};
          draftWp = Number(metrics.waypoints || 0);
          draftDist = Math.round(Number(metrics.distance_m) || 0);
          draftPathHeight = draftHeight;
          field.flugweg = draftPath;
          field.wegpunkte = String(draftWp);
          field.distanz = String(draftDist);
        }
        if (epoch !== draftEpoch) return;
        document.getElementById('meta').textContent = 'Feldkatalog, JSON und KMZ werden gespeichert …';
        const saveBody = { mode: editId ? 'update' : 'create', field: field, kat: true, token: reserveToken(), mdb: missionKey() };
        let saveRes = await fetch(apiUrl(), {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          credentials: 'same-origin',
          body: JSON.stringify(saveBody)
        });
        let saved = await readJson(saveRes);
        if (!saved.ok && saved.error === 'feld_id_exists') {
          const useIt = window.confirm((saved.detail || 'Diese Feldkatalognummer ist schon vergeben.') + '\n\nDiese Nummer trotzdem verwenden? Dateien, die es schon unter der bisherigen Nummer gibt, werden umbenannt.');
          if (!useIt) throw new Error(saved.detail || 'Feldkatalognummer ist schon vergeben.');
          field.confirm_existing_feld_id = true;
          saveRes = await fetch(apiUrl(), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ mode: editId ? 'update' : 'create', field: field, kat: true, token: reserveToken(), mdb: missionKey() })
          });
          saved = await readJson(saveRes);
        }
        if (!saved.ok) throw new Error(saved.detail || saved.error || 'Speichern fehlgeschlagen');
        const confirm = window.RkrkField && typeof RkrkField.saveConfirmText === 'function'
          ? RkrkField.saveConfirmText(saved)
          : { ok: !!(saved.jason_file && saved.kmz_file), text: 'JSON: ' + (saved.jason_file || 'fehlt') + '. KMZ: ' + (saved.kmz_file || 'fehlt') + '.' };
        if (!saved.jason_file || !saved.kmz_file || saved.json_written === false || saved.kmz_written === false) {
          throw new Error(confirm.text);
        }
        await loadList();
        const created = rows.find((row) => row.feld_id === saved.saved_field_id);
        savedCheck = {
          id: editId || (created ? created.id : ''),
          feld_id: saved.saved_field_id || field.feld_id || '',
          name: name,
          jg: field.jg || '',
          jg_name: preview.jg_name || field.jg || '',
          gemeinde: field.gemeinde || '',
          gemeinde_name: preview.gemeinde_name || field.gemeinde || '',
          height: draftHeight,
          wp: draftWp,
          dist: draftDist,
          drone: droneType || '',
          saveNote: confirm.text,
          saveOk: confirm.ok
        };
        if (savedCheck.id) editId = String(savedCheck.id);
        placeConfirmed = true;
        createMode = true;
        createPhase = 'check';
        renderDraft();
        if (!confirm.ok) setWarn(confirm.text);
        if (savedCheck.id && savedCheck.feld_id) await generateRcPictures(savedCheck);
        else setWarn('Feld ist gespeichert. Bilder brauchen den Katalogeintrag und werden nicht erzeugt.');
      } catch (err) {
        setWarn(err.message);
        document.getElementById('meta').textContent = 'Speichern nicht abgeschlossen';
        next.disabled = false;
      }
    }

    function beginEdit() {
      if (!currentField) return;
      const field = currentField;
      editId = String(field.id || '');
      editMission = (field.mission_settings && typeof field.mission_settings === 'object')
        ? JSON.parse(JSON.stringify(field.mission_settings))
        : {};
      const storedDir = Number(editMission.flight_direction_deg);
      draftDirection = Number.isFinite(storedDir) ? storedDir : -1;
      savedCheck = null;
      createMode = true;
      draftRing = pairs(field.flaechendaten);
      draftStart = pairs(field.startplatz)[0] || null;
      const park = pairs(field.parkplatz)[0] || null;
      draftPark = park && (!draftStart || !same6(park, draftStart)) ? park : null;
      const savedList = editMission && Array.isArray(editMission.pois) ? editMission.pois : [];
      const savedOne = editMission && editMission.poi ? [editMission.poi] : [];
      draftPois = (savedList.length ? savedList : savedOne).map((raw) => (
        raw && Number.isFinite(Number(raw.longitude)) && Number.isFinite(Number(raw.latitude))
          ? { longitude: Number(raw.longitude), latitude: Number(raw.latitude), height: Number(raw.height) || 0 }
          : null
      )).filter(Boolean);
      draftPoi = draftPois[0] || null;
      pickMissionPoi = false;
      draftPath = field.flugweg || '';
      routeCoverageReady = triplets(draftPath).length >= 2;
      generatedPath = draftPath;
      terrainFor = '';
      terrainDelta = [];
      const savedStd = Number(String(field.std_flughoehe || '').replace(/[^0-9.]/g, ''));
      const savedMs = Number(field.mission_settings && field.mission_settings.altitude_m);
      if (Number.isFinite(savedStd) && savedStd > 0) {
        draftHeight = Math.round(savedStd);
        missionHeightFromField = true;
      } else if (Number.isFinite(savedMs) && savedMs > 0) {
        draftHeight = Math.round(savedMs);
        missionHeightFromField = true;
      } else {
        draftHeight = missionAltitudeDefaultM(droneType);
        missionHeightFromField = false;
      }
      missionHeightUserSet = false;
      const storedSpeed = Number(field.mission_settings && field.mission_settings.speed_m_s);
      draftSpeed = Number.isFinite(storedSpeed) && storedSpeed > 0 ? clampHalfSpeed(storedSpeed) : 5;
      draftStartSpeedActive = !!(field.mission_settings && field.mission_settings.start_speed_active);
      const storedStart = Number(field.mission_settings && field.mission_settings.start_speed_m_s);
      draftStartSpeed = Number.isFinite(storedStart) && storedStart > 0 ? storedStart : 5;
      draftWp = Number(field.wegpunkte || 0) || triplets(draftPath).length;
      draftDist = Number(field.distanz || 0) || 0;
      draftPathHeight = draftHeight;
      wpIndex = 0;
      entryMode = 'second';
      document.getElementById('newName').value = field.name || '';
      draftGis = String(field.grundstueck_id || '').trim();
      gisTouched = draftGis !== '';
      draftFeldId = String(field.feld_id || '').trim();
      feldIdTouched = draftFeldId !== '';
      gisSuggest = '';
      gisFetchedKey = '';
      placeFetchedKey = '';
      pendingPlace = {
        ok: true,
        feld_id: field.feld_id || '',
        jg: field.jg || '',
        jg_name: field.jg_name || field.jg || '',
        gemeinde: field.gemeinde || '',
        gemeinde_name: field.gemeinde_name || field.gemeinde || ''
      };
      createPhase = draftPath ? 'route' : (draftStart ? 'start' : 'ring');
      listView.hidden = true;
      mapView.hidden = false;
      ensureMap();
      renderDraft();
      fitField();
      loadMissionJson(field).then(() => { renderDraft(); });
    }

    function finishCheck() {
      const id = savedCheck && savedCheck.id;
      savedCheck = null;
      createMode = false;
      createPhase = 'ring';
      setCreateChrome(false);
      if (id) openField(id);
      else showList();
    }

    async function closeSaveField() {
      if (!savedCheck || !savedCheck.id || !savedCheck.feld_id) {
        setWarn('Zuerst das Feld im Katalog speichern. Danach können Suchfeld- und Flugplanbild erzeugt werden.');
        return;
      }
      editId = String(savedCheck.id);
      placeConfirmed = true;
      await saveCreate();
    }

    function ensureShotMap() {
      if (shotMap) return shotMap;
      const host = document.getElementById('picHost');
      shotPoly = new ol.source.Vector();
      shotPath = new ol.source.Vector();
      shotStart = new ol.source.Vector();
      shotPathLayer = new ol.layer.Vector({
        source: shotPath,
        style: new ol.style.Style({ stroke: new ol.style.Stroke({ color: '#2f6dff', width: 4 }) })
      });
      shotMap = new ol.Map({
        target: host,
        controls: [],
        interactions: [],
        layers: [
          new ol.layer.Tile({
            source: new ol.source.XYZ({
              url: 'map_tile.php?layer=swissimage&z={z}&x={x}&y={y}',
              maxZoom: 19
            })
          }),
          new ol.layer.Vector({
            source: shotPoly,
            style: new ol.style.Style({
              stroke: new ol.style.Stroke({ color: '#ff4d4d', width: 4 }),
              fill: new ol.style.Fill({ color: 'rgba(255, 70, 70, 0.16)' })
            })
          }),
          shotPathLayer,
          new ol.layer.Vector({
            source: shotStart,
            style: new ol.style.Style({
              image: new ol.style.Circle({
                radius: 8,
                fill: new ol.style.Fill({ color: '#f5d90a' }),
                stroke: new ol.style.Stroke({ color: '#1a1a1a', width: 2 })
              })
            })
          })
        ],
        view: new ol.View({ center: ol.proj.fromLonLat(KNONAU), zoom: 14 })
      });
      return shotMap;
    }

    function catalogCropRect(canvasWidth, canvasHeight, kind) {
      const zoom = ((kind === 'such' || kind === 'suchfeld') ? 70 : 90) / 100;
      const offsetY = 0;
      const out = 600;
      const visible = out / zoom;
      const scaleX = canvasWidth / 1200;
      const scaleY = canvasHeight / 900;
      const cropW = visible * scaleX;
      const cropH = visible * scaleY;
      let x = canvasWidth / 2 - cropW / 2;
      let y = canvasHeight / 2 + offsetY * scaleY - cropH / 2;
      x = Math.max(0, Math.min(x, Math.max(0, canvasWidth - cropW)));
      y = Math.max(0, Math.min(y, Math.max(0, canvasHeight - cropH)));
      return { x: x, y: y, w: cropW, h: cropH, out: out, zoom: zoom, visible: visible, scaleX: scaleX, scaleY: scaleY };
    }
    window.rkrkCatalogCropRect = catalogCropRect;

    function cropCatalogCanvas(source, kind) {
      const rect = catalogCropRect(source.width, source.height, kind);
      const canvas = document.createElement('canvas');
      canvas.width = rect.out;
      canvas.height = rect.out;
      const ctx = canvas.getContext('2d');
      ctx.drawImage(source, rect.x, rect.y, rect.w, rect.h, 0, 0, rect.out, rect.out);
      return new Promise((resolve) => {
        try {
          canvas.toBlob((blob) => resolve(blob), 'image/jpeg', 0.92);
        } catch (_) {
          resolve(null);
        }
      });
    }

    function pictureGuestSuffix() {
      try {
        const ga = new URLSearchParams(window.location.search).get('GA');
        if (ga) return '&GA=' + encodeURIComponent(ga);
      } catch (_) {}
      return '';
    }

    function savedCatalogInfo() {
      if (!window.RKRK_KAT) return null;
      if (createMode && savedCheck && savedCheck.id && /^[A-Za-z0-9]+-\d+$/.test(String(savedCheck.feld_id || ''))) {
        return savedCheck;
      }
      if (!createMode && currentField && currentField.id && /^[A-Za-z0-9]+-\d+$/.test(String(currentField.feld_id || ''))) {
        return {
          id: currentField.id,
          feld_id: currentField.feld_id,
          jg: currentField.jg,
          height: currentField.std_flughoehe,
          name: currentField.name
        };
      }
      return null;
    }

    function catalogFieldHit(win, feldId) {
      const geoMap = win && win.__rkrkMap27;
      if (!geoMap || typeof geoMap.getSize !== 'function' || typeof geoMap.getLayers !== 'function') return null;
      const size = geoMap.getSize();
      if (!size || size[0] < 480 || size[1] < 360) return null;
      let feature = null;
      geoMap.getLayers().forEach((layer) => {
        const src = layer.getSource && layer.getSource();
        if (!src || typeof src.getFeatures !== 'function' || feature) return;
        src.getFeatures().some((f) => {
          const g = f.getGeometry && f.getGeometry();
          if (!g || String(g.getType()).indexOf('Polygon') < 0) return false;
          const fid = String((typeof f.getId === 'function' && f.getId()) || '');
          const name = String((f.get && (f.get('name') || f.get('Name'))) || '');
          if ((fid + ' ' + name).indexOf(feldId) === -1) return false;
          feature = f;
          return true;
        });
      });
      if (!feature || typeof geoMap.getPixelFromCoordinate !== 'function') return null;
      const ext = feature.getGeometry().getExtent();
      if (!ext || ext.length < 4) return null;
      const a = geoMap.getPixelFromCoordinate([ext[0], ext[1]]);
      const b = geoMap.getPixelFromCoordinate([ext[2], ext[3]]);
      if (!a || !b) return null;
      const left = Math.min(a[0], b[0]);
      const right = Math.max(a[0], b[0]);
      const top = Math.min(a[1], b[1]);
      const bottom = Math.max(a[1], b[1]);
      if (right - left < 12 || bottom - top < 12) return null;
      const cx = (left + right) / 2;
      const cy = (top + bottom) / 2;
      if (cx < size[0] * 0.2 || cx > size[0] * 0.8 || cy < size[1] * 0.2 || cy > size[1] * 0.8) return null;
      return { cx: cx, cy: cy, size: size };
    }

    function catalogFieldPainted(win, hit) {
      const nodes = win.document.querySelectorAll('#map .ol-layer canvas');
      if (!nodes.length) return false;
      for (let i = 0; i < nodes.length; i++) {
        const canvas = nodes[i];
        if (!(canvas.width > 0) || !(canvas.height > 0)) continue;
        const prX = canvas.width / hit.size[0];
        const prY = canvas.height / hit.size[1];
        let ctx = null;
        try { ctx = canvas.getContext('2d', { willReadFrequently: true }); } catch (_) { ctx = null; }
        if (!ctx) continue;
        const x = Math.max(0, Math.min(canvas.width - 1, Math.round(hit.cx * prX)));
        const y = Math.max(0, Math.min(canvas.height - 1, Math.round(hit.cy * prY)));
        try {
          const d = ctx.getImageData(x, y, 1, 1).data;
          if (d[3] > 40 && d[0] > d[1] + 28 && d[0] > d[2] + 28) return true;
        } catch (_) {}
      }
      return false;
    }

    function compositeCatalogBitmap(win) {
      const geoMap = win && win.__rkrkMap27;
      const size = geoMap && geoMap.getSize ? geoMap.getSize() : null;
      if (!size || !(size[0] > 0) || !(size[1] > 0)) return null;
      const nodes = win.document.querySelectorAll('#map .ol-layer canvas');
      if (!nodes.length) return null;
      const base = nodes[nodes.length - 1];
      const prX = base.width / size[0];
      const prY = base.height / size[1];
      if (!(prX > 0) || !(prY > 0)) return null;
      const out = document.createElement('canvas');
      out.width = Math.max(1, Math.round(size[0] * prX));
      out.height = Math.max(1, Math.round(size[1] * prY));
      const ctx = out.getContext('2d');
      if (!ctx) return null;
      Array.prototype.forEach.call(nodes, (canvas) => {
        if (!(canvas.width > 0) || !(canvas.height > 0)) return;
        const parent = canvas.parentNode;
        const opacity = parent && parent.style && parent.style.opacity !== '' ? Number(parent.style.opacity) : 1;
        ctx.globalAlpha = Number.isFinite(opacity) ? opacity : 1;
        const transform = canvas.style && canvas.style.transform ? canvas.style.transform : '';
        const matched = /^matrix\(([^)]+)\)$/.exec(transform);
        if (matched) {
          const matrix = matched[1].split(',').map((v) => Number(String(v).trim()));
          if (matrix.length === 6 && matrix.every((n) => Number.isFinite(n))) {
            ctx.setTransform(matrix[0] * prX, matrix[1] * prX, matrix[2] * prX, matrix[3] * prY, matrix[4] * prX, matrix[5] * prY);
            ctx.drawImage(canvas, 0, 0);
            return;
          }
        }
        ctx.setTransform(prX, 0, 0, prY, 0, 0);
        ctx.drawImage(canvas, 0, 0, size[0], size[1]);
      });
      ctx.globalAlpha = 1;
      ctx.setTransform(1, 0, 0, 1, 0, 0);
      return out;
    }

    function prepareCatalogFrame(win) {
      try {
        win.document.documentElement.classList.add('rkrk-map27-embed');
        win.document.body.classList.add('rkrk-map27-page');
      } catch (_) {}
      try {
        if (typeof win.rkrkUpdateMapSize === 'function') win.rkrkUpdateMapSize(false);
        else if (win.__rkrkMap27) win.__rkrkMap27.updateSize();
      } catch (_) {}
    }

    function captureShot(kind, feldId) {
      const id = String(feldId || '').trim();
      if (typeof window.rkrkCaptureCatalogShot === 'function') {
        const url = '../rkrk_map.php?FFilter=' + encodeURIComponent(id) + '&MDB=' + encodeURIComponent(missionKey()) + '&' + pictureQuery(kind) + pictureGuestSuffix();
        return window.rkrkCaptureCatalogShot(kind, id, url);
      }
      if (!/^[A-Za-z0-9]+-\d+$/.test(id)) return Promise.resolve(null);
      return new Promise((resolve) => {
        const frame = document.createElement('iframe');
        frame.setAttribute('aria-hidden', 'true');
        frame.width = '1200';
        frame.height = '900';
        frame.style.cssText = 'position:fixed;left:0;top:0;width:1200px;height:900px;border:0;opacity:0.02;pointer-events:none;z-index:-1;';
        let settled = false;
        const finish = (blob) => {
          if (settled) return;
          settled = true;
          frame.remove();
          resolve(blob && blob.size > 4000 ? blob : null);
        };
        const timer = setTimeout(() => finish(null), 20000);
        const grabWhenDrawn = () => {
          if (settled) return;
          const win = frame.contentWindow;
          if (!win) {
            setTimeout(grabWhenDrawn, 200);
            return;
          }
          prepareCatalogFrame(win);
          const geoMap = win.__rkrkMap27;
          if (geoMap && typeof geoMap.updateSize === 'function') {
            try { geoMap.updateSize(); } catch (_) {}
          }
          const hit = catalogFieldHit(win, id);
          if (!hit || !catalogFieldPainted(win, hit)) {
            try {
              if (typeof win.rkrkUpdateMapSize === 'function') win.rkrkUpdateMapSize(true);
            } catch (_) {}
            setTimeout(grabWhenDrawn, 300);
            return;
          }
          const shoot = () => {
            setTimeout(() => {
              if (settled) return;
              const again = catalogFieldHit(win, id);
              if (!again || !catalogFieldPainted(win, again)) {
                setTimeout(grabWhenDrawn, 300);
                return;
              }
              try {
                const canvas = compositeCatalogBitmap(win);
                if (!canvas) {
                  clearTimeout(timer);
                  finish(null);
                  return;
                }
                cropCatalogCanvas(canvas, kind).then((blob) => {
                  clearTimeout(timer);
                  finish(blob);
                }).catch(() => {
                  clearTimeout(timer);
                  finish(null);
                });
              } catch (_) {
                clearTimeout(timer);
                finish(null);
              }
            }, 600);
          };
          if (geoMap && typeof geoMap.once === 'function') {
            geoMap.once('rendercomplete', shoot);
            try { geoMap.renderSync(); } catch (_) { shoot(); }
          } else {
            shoot();
          }
        };
        frame.addEventListener('load', () => setTimeout(grabWhenDrawn, 200));
        document.body.appendChild(frame);
        frame.src = '../rkrk_map.php?FFilter=' + encodeURIComponent(id) + '&MDB=' + encodeURIComponent(missionKey()) + '&' + pictureQuery(kind) + pictureGuestSuffix();
      });
    }

    async function generateRcPictures(info) {
      if (!info || !info.id || !/^[A-Za-z0-9]+-\d+$/.test(String(info.feld_id || ''))) {
        setWarn('Zuerst das Feld im Katalog speichern. Danach können Suchfeld- und Flugplanbild erzeugt werden.');
        return;
      }
      if (typeof window.rkrkStoreCatalogPictures !== 'function') {
        setWarn('Suchfeld- und Flugplanbild konnten nicht erzeugt werden.');
        return;
      }
      document.getElementById('meta').textContent = 'Suchfeld- und Flugplanbild werden erzeugt …';
      try {
        const data = await window.rkrkStoreCatalogPictures({
          id: info.id,
          feld_id: info.feld_id,
          mdb: missionKey(),
          mapBase: '../rkrk_map.php',
          apiUrl: apiUrl()
        });
        if (!data || !data.ok) {
          if (data && data.empty) setWarn('Bilder konnten nicht erzeugt werden. Das Feld ist gespeichert.');
          else if (data && data.error === 'unknown_jg') setWarn('Bilder brauchen ein bekanntes Jagdgebiet. Das Feld ist gespeichert.');
          else setWarn((data && data.error) || 'Bilder nicht geschrieben');
          return;
        }
        if (currentField && String(currentField.feld_id || '') === String(info.feld_id || '')) {
          currentField.suchfeld_bild = data.suchfeld_bild || currentField.suchfeld_bild || '';
          currentField.flugplan_bild = data.flugplan_bild || currentField.flugplan_bild || '';
        }
        showPictureThumbs({
          suchfeld_bild: data.suchfeld_bild || '',
          flugplan_bild: data.flugplan_bild || '',
          std_flughoehe: String(info.height || (currentField && currentField.std_flughoehe) || draftHeight)
        });
        if (createPhase === 'check') {
          const dist = formatDist(String(info.dist || ''));
          document.getElementById('meta').textContent = [info.jg_name || info.jg || 'kein Jagdgebiet', info.gemeinde_name || info.gemeinde || 'keine Gemeinde', (info.drone || ''), (info.height || '') + ' m', (info.wp || '') + ' WP', dist].filter(Boolean).join(' · ');
        } else if (!createMode && currentField) {
          showFacts(currentField);
        }
        setWarn('');
      } catch (err) {
        setWarn((err && err.message) || 'Bilder nicht erzeugt');
      }
    }

    async function openField(id) {
      if (!window.RKRK_KAT) return;
      activeId = id;
      try {
        const res = await fetch(apiUrl({ action: 'get', id: String(id) }), { credentials: 'same-origin' });
        const data = await readJson(res);
        if (!data.ok || !data.field) throw new Error(data.detail || data.error || 'Feld fehlt');
        currentField = data.field;
        rememberField(id);
        createMode = false;
        setCreateChrome(false);
        listView.hidden = true;
        mapView.hidden = false;
        showFacts(currentField);
        await loadMissionJson(currentField);
        drawField(currentField);
      } catch (err) {
        if (rememberedField() === id) rememberField('');
        countEl.textContent = err.message;
        listView.hidden = false;
        mapView.hidden = true;
      }
    }

    function showList() {
      if (!window.RKRK_KAT) return;
      rememberField('');
      mapView.hidden = true;
      listView.hidden = false;
      renderList();
      const active = listEl.querySelector('.field-btn.active');
      if (active) active.scrollIntoView({ block: 'nearest' });
    }

    window.addEventListener('pagehide', () => { if (window.RKRK_KAT) releaseReservation(); });
    if (window.RKRK_KAT) {
      setInterval(() => {
        if (!createMode || editId) return;
        if (!/^\d+-\d+$/.test(String(draftFeldId || ''))) return;
        fetch(apiUrl(), {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          credentials: 'same-origin',
          body: JSON.stringify({ mode: 'heartbeat', token: reserveToken(), mdb: missionKey() })
        }).catch(() => {});
      }, 60000);
    }
    document.getElementById('newField').addEventListener('click', startCreate);
    document.getElementById('q').addEventListener('input', renderList);
    document.getElementById('clearQ').addEventListener('click', () => {
      qEl.value = '';
      renderList();
      qEl.focus();
    });
    document.getElementById('back').addEventListener('click', () => {
      if (createMode && createPhase === 'confirm') {
        createPhase = 'start';
        placeConfirmed = false;
        setWarn('');
        renderDraft();
        return;
      }
      if (createMode) cancelCreate();
      else showList();
    });
    document.getElementById('undoBtn').addEventListener('click', undoDraft);
    document.getElementById('prevBtn').addEventListener('click', goBack);
    document.getElementById('nextBtn').addEventListener('click', goForward);
    document.getElementById('newName').addEventListener('input', () => {
      if (createMode) renderDraft();
    });
    document.getElementById('hMinus').addEventListener('click', () => changeHeight(-5));
    document.getElementById('hPlus').addEventListener('click', () => changeHeight(5));
    document.getElementById('editBtn').addEventListener('click', beginEdit);
    document.getElementById('kmzBtn').addEventListener('click', downloadKmz);
    document.getElementById('jsonDownBtn').addEventListener('click', () => {
      downloadJson().catch((err) => setWarn((err && err.message) || 'JSON nicht geladen'));
    });
    document.getElementById('saveFilesBtn').addEventListener('click', saveChangedFiles);
    document.getElementById('closeSaveBtn').addEventListener('click', () => {
      closeSaveField().catch((err) => setWarn((err && err.message) || 'Speichern nicht abgeschlossen'));
    });
    document.getElementById('renewPicBtn').addEventListener('click', () => {
      renewRcPictures().catch((err) => setWarn((err && err.message) || 'Bilder nicht erzeugt'));
    });
    document.getElementById('localKmlBtn').addEventListener('click', () => {
      try { downloadLocal('kml'); } catch (err) { setWarn(err.message || 'KML nicht geladen'); }
    });
    document.getElementById('localJsonBtn').addEventListener('click', () => {
      try { downloadLocal('json'); } catch (err) { setWarn(err.message || 'JSON nicht geladen'); }
    });
    document.getElementById('pointAdd').addEventListener('click', addAfterSelected);
    document.getElementById('pointDelete').addEventListener('click', deleteSelected);
    document.getElementById('gisInput').addEventListener('input', () => {
      draftGis = document.getElementById('gisInput').value.trim();
      gisTouched = true;
      paintPlaceBox();
    });
    document.getElementById('rcModeToggle').addEventListener('click', () => setRcExpertMode(!expertMode));
    const rcPoiPark = document.getElementById('rcPoiFromPark');
    if (rcPoiPark) rcPoiPark.addEventListener('click', () => {
      if (!draftPark) {
        setWarn('Kein Parkplatz gesetzt.');
        return;
      }
      const overrides = ensureEditOverrides();
      const key = String(wpIndex + 1);
      const prev = overrides[key] && typeof overrides[key] === 'object' ? overrides[key] : {};
      overrides[key] = Object.assign({}, prev, {
        poi: { longitude: Number(draftPark[0]), latitude: Number(draftPark[1]), height: Number(draftHeight) || 0 },
      });
      pickPoi = false;
      paintExpert();
      renderDraft();
    });
    const rcPoiMap = document.getElementById('rcPoiOnMap');
    if (rcPoiMap) rcPoiMap.addEventListener('click', () => {
      pickPoi = true;
      pickPark = false;
      setWarn('POI auf der Karte setzen. Start und Parkplatz werden nicht verschoben.');
    });
    const debugTfBtn = document.getElementById('debugTfBtn');
    if (debugTfBtn) debugTfBtn.addEventListener('click', () => { prepareDebugTf(); });
    function syncRcGimbalFromControls() {
      if (expertPaintLock) return;
      const check = document.getElementById('rcGimbalAngleActive');
      const pitchEl = document.getElementById('rcGimbalPitch');
      const gimbalOn = !!(check && check.checked);
      if (!editMission || typeof editMission !== 'object') editMission = {};
      editMission.gimbal_mode = gimbalOn ? 'Jeden Wegpunkt festlegen' : 'Manuell';
      if (!gimbalOn && pitchEl) pitchEl.value = '-90';
      if (pitchEl) pitchEl.disabled = !gimbalOn;
      const pitch = Number(pitchEl && pitchEl.value);
      const gimbalPitch = gimbalOn && Number.isFinite(pitch) ? Math.max(-90, Math.min(30, Math.round(pitch))) : -90;
      editMission.gimbal_pitch = gimbalPitch;
      const overrides = ensureEditOverrides();
      const key = String(wpIndex + 1);
      const prev = overrides[key] && typeof overrides[key] === 'object' ? overrides[key] : {};
      overrides[key] = Object.assign({}, prev, { gimbal_pitch: gimbalPitch });
      paintRcGimbal();
    }
    ['rcGimbalAngleActive', 'rcGimbalPitch'].forEach((id) => {
      const el = document.getElementById(id);
      if (!el) return;
      el.addEventListener('change', syncRcGimbalFromControls);
      if (el.type === 'number') el.addEventListener('input', syncRcGimbalFromControls);
    });
    ['rcUseH', 'rcUseS', 'rcUseT', 'rcUseY', 'rcUseP', 'rcUseD', 'rcAlt', 'rcSpd', 'rcType', 'rcYaw', 'rcYawAngle', 'rcPath', 'rcDir'].forEach((id) => {
      const el = document.getElementById(id);
      if (!el) return;
      const apply = () => {
        if (expertPaintLock) return;
        writeExpertRow();
        if (rcOverridesNeedExpert()) setRcExpertMode(true);
        renderDraft();
      };
      el.addEventListener('change', apply);
      if (el.type === 'number') el.addEventListener('input', apply);
    });
    document.getElementById('feldIdInput').addEventListener('input', () => {
      draftFeldId = document.getElementById('feldIdInput').value.trim();
      feldIdTouched = true;
    });
    document.getElementById('gisApply').addEventListener('click', () => {
      if (!gisSuggest) return;
      draftGis = gisSuggest;
      gisTouched = true;
      paintPlaceBox();
    });
    document.getElementById('parkBtn').addEventListener('click', () => {
      pickPark = true;
      pickMissionPoi = false;
      renderDraft();
    });
    document.getElementById('parkClear').addEventListener('click', () => setPark(null));
    const poiBtn = document.getElementById('poiBtn');
    if (poiBtn) poiBtn.addEventListener('click', () => {
      pickMissionPoi = true;
      pickPark = false;
      pickPoi = false;
      renderDraft();
    });
    const poiClear = document.getElementById('poiClear');
    if (poiClear) poiClear.addEventListener('click', () => {
      draftPois = [];
      draftPoi = null;
      pickMissionPoi = false;
      renderDraft();
    });
    document.getElementById('covBtn').addEventListener('click', () => {
      showCoverage = !showCoverage;
      rememberRcDisplay();
      if (createMode) renderDraft();
      else if (currentField) drawField(currentField);
    });
    document.getElementById('htBtn').addEventListener('click', () => {
      showRouteHeights = !showRouteHeights;
      rememberRcDisplay();
      renderDraft();
    });
    document.getElementById('aglBtn').addEventListener('click', toggleAglLimit);
    function stepRcLabel(delta) {
      if (!window.RkrkField || typeof RkrkField.setMapLabelScale !== 'function') return;
      const cur = typeof RkrkField.mapLabelScale === 'function' ? RkrkField.mapLabelScale() : mapLabelScale;
      const next = Math.max(0.7, Math.min(1.8, Math.round((cur + delta) * 10) / 10));
      mapLabelScale = RkrkField.setMapLabelScale(next);
      rememberRcDisplay();
      if (vertexSource) vertexSource.changed();
      if (heightSource) heightSource.changed();
      if (createMode) renderDraft();
    }
    const labelSmaller = document.getElementById('labelSmaller');
    const labelLarger = document.getElementById('labelLarger');
    if (labelSmaller) labelSmaller.addEventListener('click', () => stepRcLabel(-0.1));
    if (labelLarger) labelLarger.addEventListener('click', () => stepRcLabel(0.1));
    function syncMapLabelSize() {
      const el = document.getElementById('mapLabelSize');
      if (el) el.hidden = window.innerWidth < 900;
    }
    syncMapLabelSize();
    window.addEventListener('resize', syncMapLabelSize);
    document.getElementById('aglRouteBtn').addEventListener('click', toggleAglLimit);
    document.getElementById('entrySecond').addEventListener('click', () => applyEntry('second'));
    document.getElementById('entrySecondLast').addEventListener('click', () => applyEntry('secondLast'));
    document.getElementById('entryLast').addEventListener('click', () => applyEntry('last'));
    function commitDraftSpeed(raw) {
      draftSpeed = clampHalfSpeed(raw);
      const input = document.getElementById('speedVal');
      if (input) input.value = draftSpeed.toFixed(1);
      if (!expertMode || !draftStartSpeedActive) draftStartSpeed = draftSpeed;
      paintStartSpeedBox();
    }
    function commitMissionAltitude(raw) {
      draftHeight = clampMissionHeight(raw);
      missionHeightUserSet = true;
      const input = document.getElementById('missionAltVal');
      if (input) input.value = String(draftHeight);
      const label = document.getElementById('hVal');
      if (label && createPhase !== 'wp') label.textContent = draftHeight + ' m';
      if (createPhase === 'route') computeRoute();
    }
    document.getElementById('speedMinus').addEventListener('click', () => commitDraftSpeed(draftSpeed - 0.5));
    document.getElementById('speedPlus').addEventListener('click', () => commitDraftSpeed(draftSpeed + 0.5));
    const rcStartActive = document.getElementById('rcStartSpeedActive');
    const rcStartSpeed = document.getElementById('rcStartSpeed');
    if (rcStartActive) rcStartActive.addEventListener('change', () => {
      draftStartSpeedActive = !!rcStartActive.checked;
      paintStartSpeedBox();
      const expert = document.getElementById('expertBox');
      paintRcDebugHints(!!(expert && !expert.hidden));
    });
    if (rcStartSpeed) rcStartSpeed.addEventListener('change', () => {
      const n = Number(rcStartSpeed.value);
      if (Number.isFinite(n) && n > 0) draftStartSpeed = Math.min(15, Math.max(0.1, n));
      rcStartSpeed.value = Number(draftStartSpeed).toFixed(1);
    });
    document.getElementById('speedVal').addEventListener('change', () => commitDraftSpeed(document.getElementById('speedVal').value));
    document.getElementById('missionAltMinus').addEventListener('click', () => commitMissionAltitude(draftHeight - 1));
    document.getElementById('missionAltPlus').addEventListener('click', () => commitMissionAltitude(draftHeight + 1));
    document.getElementById('missionAltVal').addEventListener('change', () => commitMissionAltitude(document.getElementById('missionAltVal').value));
    document.getElementById('dirRange').addEventListener('change', () => {
      shiftDirection(Math.max(0, Math.min(179, Math.round(Number(document.getElementById('dirRange').value) || 0))));
    });
    document.getElementById('wpPrev').addEventListener('click', () => {
      const n = triplets(draftPath).length;
      if (!n) return;
      wpIndex = (wpIndex - 1 + n) % n;
      renderDraft();
    });
    document.getElementById('wpNext').addEventListener('click', () => {
      const n = triplets(draftPath).length;
      if (!n) return;
      wpIndex = (wpIndex + 1) % n;
      renderDraft();
    });
    document.getElementById('wpDots').addEventListener('click', (ev) => {
      const btn = ev.target.closest('.wp-dot');
      if (!btn) return;
      const n = triplets(draftPath).length;
      const i = Number(btn.dataset.i);
      if (!Number.isInteger(i) || i < 0 || i >= n) return;
      wpIndex = i;
      renderDraft();
    });
    document.getElementById('fitBtn').addEventListener('click', fitField);
    function applyBasemap(nextSatellite) {
      satellite = !!nextSatellite;
      if (imageLayer) imageLayer.setVisible(satellite);
      if (mapLayer) mapLayer.setVisible(!satellite);
      const mapBtn = document.getElementById('basemapMapBtn');
      const satBtn = document.getElementById('basemapSatBtn');
      if (mapBtn) mapBtn.classList.toggle('is-on', !satellite);
      if (satBtn) satBtn.classList.toggle('is-on', satellite);
    }
    document.getElementById('basemapMapBtn').addEventListener('click', () => applyBasemap(false));
    document.getElementById('basemapSatBtn').addEventListener('click', () => applyBasemap(true));
    document.getElementById('showBtn').addEventListener('click', () => {
      const menu = document.getElementById('showMenu');
      menu.hidden = !menu.hidden;
      document.getElementById('optMenu').hidden = true;
    });
    const wpDebugBtn = document.getElementById('wpDebugBtn');
    const wpDebugBox = document.getElementById('wpDebugTableBox');
    if (wpDebugBtn) {
      wpDebugBtn.hidden = !rcDebugOn();
      wpDebugBtn.addEventListener('click', () => {
        try { sessionStorage.setItem('rkrkRcWpDebugOn', rcWpDebugUserOn() ? '0' : '1'); } catch (_) {}
        scheduleRcWaypointDebugTable();
      });
    }
    if (wpDebugBox) {
      wpDebugBox.addEventListener('toggle', () => {
        try { sessionStorage.setItem('rkrkRcWpDebugOpen', wpDebugBox.open ? '1' : '0'); } catch (_) {}
      });
    }
    document.getElementById('optBtn').addEventListener('click', () => {
      const menu = document.getElementById('optMenu');
      menu.hidden = !menu.hidden;
      document.getElementById('showMenu').hidden = true;
      paintDirection();
    });
    document.getElementById('reliefBtn').addEventListener('click', () => {
      ensureMap();
      if (!reliefLayer) return;
      reliefLayer.setVisible(!reliefLayer.getVisible());
      document.getElementById('reliefBtn').classList.toggle('off', !reliefLayer.getVisible());
    });
    function toggleNamedLayer(key, buttonId) {
      ensureMap();
      const layer = extraLayers[key];
      if (!layer) return;
      layer.setVisible(!layer.getVisible());
      document.getElementById(buttonId).classList.toggle('off', !layer.getVisible());
    }
    document.getElementById('layerRpasBtn').addEventListener('click', () => toggleNamedLayer('rpas', 'layerRpasBtn'));
    document.getElementById('layerElectricBtn').addEventListener('click', () => toggleNamedLayer('electric', 'layerElectricBtn'));
    document.getElementById('layerObstaclesBtn').addEventListener('click', () => toggleNamedLayer('obstacles', 'layerObstaclesBtn'));
    document.getElementById('layerDabsBtn').addEventListener('click', () => {
      ensureMap();
      const btn = document.getElementById('layerDabsBtn');
      if (!window.RkrkSwissOverlays) return;
      if (!dabsCtl) dabsCtl = window.RkrkSwissOverlays.attachDabs(map, { url: 'rkrk_dabs.php' });
      const turnOn = btn.classList.contains('off');
      if (turnOn) dabsCtl.enable(0.9);
      else dabsCtl.disable();
      btn.classList.toggle('off', !turnOn);
    });
    document.getElementById('layerFzBtn').addEventListener('click', () => {
      ensureMap();
      const btn = document.getElementById('layerFzBtn');
      if (!flightZoneLayer) {
        flightZoneLayer = new ol.layer.Vector({
          source: new ol.source.Vector({
            url: '../rkrk_dokml.php?Reg=leer&Strt=off&Cntr=off&Area=off&Pfad=off&WW=off&FZ=on&GDE=off&JGG=off&KT=off&ORT=off&Poi=off&HSP=off&Label=off&GeLa=off&Sun=off&EG=off&SORA=off&WF=off&KA=S&Zoom=1&MDB=3T&GA=gast&WebAccess=test',
            format: new ol.format.KML({ extractStyles: true })
          })
        });
        map.addLayer(flightZoneLayer);
      } else {
        flightZoneLayer.setVisible(!flightZoneLayer.getVisible());
      }
      btn.classList.toggle('off', flightZoneLayer.getVisible() === false);
    });
    function shiftDirection(next) {
      draftDirection = next;
      clearRoute();
      paintDirection();
      if (createPhase === 'route' || createPhase === 'name') computeRoute();
    }
    document.getElementById('dirMinus').addEventListener('click', () => {
      const base = draftDirection < 0 ? 0 : draftDirection;
      shiftDirection((base + 345) % 360);
    });
    document.getElementById('dirPlus').addEventListener('click', () => {
      const base = draftDirection < 0 ? 0 : draftDirection;
      shiftDirection((base + 15) % 360);
    });
    document.getElementById('dirAuto').addEventListener('click', () => shiftDirection(-1));
    document.getElementById('dirSuggest').addEventListener('click', () => shiftDirection(-1));
    document.getElementById('recomputeBtn').addEventListener('click', () => {
      clearRoute();
      computeRoute();
    });
    document.getElementById('droneBtn').addEventListener('click', openDronePick);
    document.getElementById('droneMapBtn').addEventListener('click', openDronePick);
    document.getElementById('droneBack').addEventListener('click', closeDronePick);

    (function setupSidePanel() {
      const chrome = document.getElementById('mapChrome');
      const toggle = document.getElementById('chromeToggle');
      const handle = document.getElementById('chromeResize');
      if (!chrome || !toggle || !handle) return;
      const savedW = localStorage.getItem('rkrk-rc-side');
      if (savedW) chrome.style.setProperty('--side', savedW);
      if (localStorage.getItem('rkrk-rc-side-open') === '0') chrome.classList.add('is-collapsed');
      const paintToggle = () => {
        const closed = chrome.classList.contains('is-collapsed');
        toggle.textContent = closed ? '›' : '‹';
        toggle.setAttribute('aria-label', closed ? 'Eingaben einblenden' : 'Eingaben ausblenden');
      };
      paintToggle();
      toggle.addEventListener('click', () => {
        chrome.classList.toggle('is-collapsed');
        localStorage.setItem('rkrk-rc-side-open', chrome.classList.contains('is-collapsed') ? '0' : '1');
        paintToggle();
        if (map) {
          map.updateSize();
          fitField();
        }
      });
      let drag = null;
      handle.addEventListener('pointerdown', (ev) => {
        if (chrome.classList.contains('is-collapsed')) return;
        drag = { x: ev.clientX, w: chrome.getBoundingClientRect().width };
        handle.setPointerCapture(ev.pointerId);
      });
      handle.addEventListener('pointermove', (ev) => {
        if (!drag) return;
        const w = Math.max(140, Math.min(window.innerWidth * 0.72, drag.w + ev.clientX - drag.x));
        chrome.style.setProperty('--side', w + 'px');
      });
      const endDrag = () => {
        if (!drag) return;
        drag = null;
        localStorage.setItem('rkrk-rc-side', getComputedStyle(chrome).width);
        if (map) {
          map.updateSize();
          fitField();
        }
      };
      handle.addEventListener('pointerup', endDrag);
      handle.addEventListener('pointercancel', endDrag);
    })();

    if ('serviceWorker' in navigator) {
      navigator.serviceWorker.register('feld_rc_sw.js').catch(() => {});
    }

    if (!droneType) droneType = readStoredDrone();
    const droneReady = initDrone();
    if (window.RkrkHelp) {
      RkrkHelp.mount({
        page: 'rc',
        getView: rcHelpView,
        trigger: document.getElementById('rcHelpBtn')
      });
    }
    if (window.RKRK_KAT) {
      droneReady.then(() => {
        if (droneType && !droneHasCatalog(droneType)) {
          startCreate();
          return;
        }
        return loadList().then(() => {
          const id = rememberedField();
          if (!id) return;
          if (rows.some((row) => row.id === id)) openField(id);
          else rememberField('');
        });
      });
    } else if (readStoredDrone()) {
      const back = document.getElementById('back');
      if (back) back.hidden = true;
      startCreate();
    } else {
      const back = document.getElementById('back');
      if (back) back.hidden = true;
    }
  </script>
</body>
</html>
