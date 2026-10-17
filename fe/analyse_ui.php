<?php
require_once __DIR__ . '/mapll_env.php';
$mapllBrand = mapll_favicon_brand();
?><!doctype html>
<html <?php echo mapll_html_open_attrs(); ?>>
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?php echo htmlspecialchars(($mapllBrand['label'] ?? 'RKRK') . ' Feldanalyse', ENT_QUOTES, 'UTF-8'); ?></title>
  <?php echo mapll_favicon_link_tags($mapllBrand); ?>
  <?php echo mapll_theme_css_link(); ?>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ol@7.5.2/ol.css" />
  <link rel="stylesheet" href="rkrk_help.css" />
  <link rel="stylesheet" href="rkrk_layout.css" />
  <link rel="stylesheet" href="../template/rkrk_reg_pills.css?v=20260907nodbl" />
  <style>
    body { margin: 0; font-family: Arial, sans-serif; background: #f4f6f8; color: #23384d; }
    .wrap { display: grid; grid-template-columns: 380px 1fr; height: 100vh; }
    .map-wrap { min-width: 0; height: 100vh; }
    .left { height: 100%; box-sizing: border-box; }
    h2 { margin-top: 0; }
    .rkr-brand-header { display: flex; align-items: center; gap: 10px; margin-top: 0; }
    .rkr-brand-favicon { width: 28px; height: 28px; border-radius: 6px; object-fit: contain; flex: 0 0 auto; background: #fff; }
    .left { padding: 14px; overflow: auto; border-right: 1px solid #d9dde2; background: #fff; }
    .row { margin-bottom: 10px; }
    .row.compact { margin-bottom: 6px; }
    label { display: block; font-size: 12px; margin-bottom: 4px; color: #344f6d; }
    input, select, button {
      width: 100%;
      padding: 8px;
      box-sizing: border-box;
      border: 1px solid #d9dde2;
      border-radius: 6px;
    }
    .field-select-compact {
      padding: 6px !important;
      font-size: 12px;
    }
    button { background: var(--brand-dark, #48673A); color: #fff; border: 0; cursor: pointer; }
    button:hover { background: #2c4d6a; }
    .kpi { background: #f8fafb; border: 1px solid #d9dde2; padding: 8px; margin: 8px 0; font-size: 13px; }
    .step-details { border: 1px solid #d9dde2; border-radius: 8px; background: #fff; margin: 8px 0; }
    .step-details > summary { cursor: pointer; padding: 8px; background: #eef2f6; font-weight: bold; }
    .step-content { padding: 8px; font-size: 12px; }
    .map-wrap { position: relative; width: 100%; height: 100vh; }
    .map { width: 100%; height: 100vh; }
    .floating-box {
      user-select: none;
    }
    .floating-box-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 8px;
      cursor: move;
      margin: -2px -2px 6px -2px;
      padding: 2px 2px 4px 2px;
      border-bottom: 1px solid rgba(120, 140, 160, 0.25);
    }
    .floating-box-title {
      font-weight: bold;
      color: #1b3552;
      font-size: 12px;
      line-height: 1.2;
    }
    .floating-box-toggle {
      width: auto;
      min-width: 24px;
      padding: 1px 6px;
      border: 1px solid #9fb0c2;
      border-radius: 6px;
      background: #f2f6fb;
      color: #1b3552;
      font-size: 12px;
      line-height: 1.2;
      cursor: pointer;
    }
    .floating-box-content {
      user-select: text;
    }
    .floating-box.collapsed .floating-box-content {
      display: none;
    }
    .map-legend {
      position: absolute;
      left: auto;
      right: 278px;
      top: 14px;
      z-index: 20;
      background: rgba(255,255,255,0.95);
      border: 1px solid #cfd6de;
      border-radius: 8px;
      padding: 8px;
      width: 180px;
      box-shadow: 0 1px 4px rgba(0,0,0,0.15);
      font-size: 12px;
      color: #222;
    }
    .legend-title { font-weight: bold; margin-bottom: 6px; }
    .legend-scale {
      height: 14px;
      border-radius: 6px;
      border: 1px solid #bbb;
      background: linear-gradient(to right, rgb(255,0,0), rgb(255,255,0), rgb(0,255,0));
      margin-bottom: 6px;
    }
    .legend-scale-wrap {
      display: grid;
      grid-template-columns: 16px 1fr 16px;
      align-items: center;
      gap: 4px;
      margin-bottom: 6px;
    }
    .legend-sign {
      font-weight: bold;
      color: #23384d;
      text-align: center;
      line-height: 1;
    }
    .legend-row { display: flex; justify-content: space-between; gap: 8px; }
    .ol-scale-line {
      left: 14px !important;
      bottom: 14px !important;
      background: rgba(255,255,255,0.9) !important;
      border: 1px solid #cfd6de;
      border-radius: 6px;
      padding: 2px 6px;
    }
    .ol-scale-line-inner {
      border-color: #23384d !important;
      color: #23384d !important;
      font-weight: bold;
    }
    .scale-ws-box {
      position: absolute;
      left: 14px;
      bottom: 58px;
      z-index: 20;
      background: rgba(255,255,255,0.92);
      border: 1px solid #cfd6de;
      border-radius: 6px;
      padding: 6px 8px;
      font-size: 12px;
      color: #23384d;
      min-width: 140px;
    }
    .display-control-box {
      position: absolute;
      right: 14px;
      top: 14px;
      z-index: 20;
      background: rgba(255,255,255,0.95);
      border: 1px solid #cfd6de;
      border-radius: 8px;
      padding: 8px 10px;
      box-shadow: 0 1px 4px rgba(0,0,0,0.15);
      font-size: 12px;
      color: #222;
      min-width: 230px;
    }
    .display-control-box .title {
      font-weight: bold;
      margin-bottom: 6px;
    }
    .display-control-box label:not(.rkrk-sel) {
      display: flex;
      align-items: center;
      gap: 6px;
      margin: 4px 0;
      font-size: 12px;
      color: #222;
    }
    .display-control-box .group-title {
      margin-top: 8px;
      margin-bottom: 3px;
      font-size: 11px;
      font-weight: bold;
      color: #1b3552;
      text-transform: uppercase;
      letter-spacing: 0.02em;
    }
    .display-control-box .group-title:first-of-type {
      margin-top: 2px;
    }
    .display-control-box .subgroup-title {
      margin-top: 4px;
      margin-bottom: 2px;
      font-size: 11px;
      font-weight: bold;
      color: #3a5877;
    }
    .display-control-box .group-note {
      margin: 2px 0 4px;
      font-size: 11px;
      color: #5b6f86;
    }
    .display-control-box .control-divider {
      border-top: 1px solid #e1e7ef;
      margin: 6px 0;
    }
    .display-control-box label.is-disabled {
      opacity: 0.55;
    }
    .display-control-box input[type="checkbox"] {
      width: auto;
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    .rkrk-dabs-popup {
      position: absolute;
      z-index: 40;
      background: rgba(255, 255, 255, 0.97);
      border: 1px solid #cfd6de;
      border-radius: 8px;
      padding: 10px 28px 10px 12px;
      min-width: 220px;
      max-width: 420px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.16);
      font-size: 12px;
      color: #1b3552;
    }
    .rkrk-dabs-popup .pop-title { font-weight: 700; margin-bottom: 4px; }
    .rkrk-dabs-popup .pop-desc { line-height: 1.35; margin-top: 6px; overflow-wrap: anywhere; }
    .rkrk-dabs-popup .pop-close {
      position: absolute;
      top: 4px;
      right: 6px;
      width: auto;
      padding: 0;
      border: 0;
      background: transparent;
      color: #1b3552;
      cursor: pointer;
      font-size: 16px;
      line-height: 1;
    }
    #dabsStatus { margin: 2px 0 0; font-size: 11px; color: #5b6f86; }
    .analysis-control-box {
      position: absolute;
      right: 14px;
      top: 220px;
      z-index: 20;
      background: rgba(255,255,255,0.95);
      border: 1px solid #cfd6de;
      border-radius: 8px;
      padding: 10px 10px 8px;
      box-shadow: 0 1px 4px rgba(0,0,0,0.15);
      width: 220px;
      font-size: 12px;
      color: #23384d;
    }
    .analysis-control-box .title {
      font-weight: bold;
      margin: 0 0 6px;
      color: #1b3552;
    }
    .analysis-control-box label:not(.rkrk-sel) {
      display: flex;
      align-items: center;
      gap: 6px;
      margin: 3px 0;
      font-size: 12px;
      color: #222;
    }
    .analysis-control-box input[type="checkbox"] {
      width: auto;
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    .analysis-control-actions {
      display: flex;
      gap: 6px;
      margin-top: 6px;
    }
    .analysis-control-actions button {
      width: 100%;
      padding: 5px 6px;
      border: 1px solid #9fb0c2;
      border-radius: 6px;
      background: #f2f6fb;
      color: #1b3552;
      cursor: pointer;
      font-size: 11px;
    }
    .coverage-result-box {
      position: absolute;
      left: 90px;
      top: 14px;
      z-index: 20;
      background: rgba(255,255,255,0.95);
      border: 1px solid #cfd6de;
      border-radius: 8px;
      padding: 10px;
      box-shadow: 0 1px 4px rgba(0,0,0,0.15);
      width: 290px;
      font-size: 12px;
      color: #23384d;
    }
    .coverage-result-box .title {
      font-weight: bold;
      margin-bottom: 6px;
      color: #1b3552;
    }
    .coverage-result-box .line {
      display: flex;
      justify-content: space-between;
      gap: 8px;
      margin: 2px 0;
    }
    .field-nav-box {
      position: absolute;
      right: 14px;
      bottom: 14px;
      z-index: 20;
      display: flex;
      gap: 8px;
      background: rgba(255,255,255,0.95);
      border: 1px solid #cfd6de;
      border-radius: 8px;
      padding: 8px;
      box-shadow: 0 1px 4px rgba(0,0,0,0.15);
    }
    .field-nav-btn {
      width: auto;
      min-width: 88px;
      padding: 6px 10px;
      border: 1px solid #9fb0c2;
      border-radius: 6px;
      background: #f2f6fb;
      color: #1b3552;
      cursor: pointer;
      font-size: 12px;
      font-weight: bold;
    }
    .field-nav-btn:hover {
      background: #e5eef8;
    }
    .map-hover-tip {
      position: absolute;
      z-index: 40;
      pointer-events: none;
      display: none;
      max-width: 320px;
      padding: 6px 8px;
      border-radius: 6px;
      border: 1px solid #cfd6de;
      background: rgba(255,255,255,0.96);
      color: #1b3552;
      font-size: 12px;
      box-shadow: 0 1px 4px rgba(0,0,0,0.15);
      white-space: normal;
    }
    .map-quick-help {
      position: absolute;
      top: 50px;
      left: 14px;
      z-index: 22;
      background: rgba(255,255,255,0.96);
      border: 1px solid #d9dde2;
      border-radius: 6px;
      padding: 6px 8px;
      font-size: 12px;
      color: #2e4158;
      display: block;
      width: 280px;
      box-shadow: 0 1px 4px rgba(0,0,0,0.15);
    }
    .quick-slot-box {
      position: absolute;
      right: 14px;
      bottom: 70px;
      z-index: 20;
      background: rgba(255,255,255,0.95);
      border: 1px solid #cfd6de;
      border-radius: 8px;
      padding: 8px;
      box-shadow: 0 1px 4px rgba(0,0,0,0.15);
      width: 240px;
      font-size: 12px;
      color: #23384d;
    }
    .quick-slot-title {
      font-weight: bold;
      margin-bottom: 6px;
    }
    .quick-slot-help {
      font-size: 11px;
      color: #4b5d72;
      margin-bottom: 6px;
    }
    .quick-slot-grid {
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      gap: 6px;
    }
    .quick-slot-panel {
      border: 1px solid #cfd6de;
      border-radius: 8px;
      padding: 8px;
      background: #f8fbff;
      margin-top: 0;
    }
    .left-text-section { margin-top: 10px; }
    .left-text-section > summary {
      cursor: pointer;
      background: #eef2f6;
      border: 1px solid #d9dde2;
      border-radius: 8px;
      padding: 8px;
      font-weight: bold;
      font-size: 13px;
      color: #1b3552;
    }
    .left-text-content { padding-top: 6px; }
    .quick-slot-btn {
      width: 100%;
      padding: 6px 4px;
      border: 1px solid #9fb0c2;
      border-radius: 6px;
      background: #f2f6fb;
      color: #1b3552;
      cursor: pointer;
      font-size: 11px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .quick-slot-btn:hover {
      background: #e5eef8;
    }
    .small { font-size: 12px; color: #5b6f86; }
    .good { color: #1d6d36; font-weight: bold; }
    .bad { color: #a22b2b; font-weight: bold; }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="left" id="analyseLeftPanel">
      <h2 class="rkr-brand-header"><?php echo mapll_favicon_mark_html($mapllBrand); ?>Feldanalyse</h2>
      <div class="small">Web-gesteuerter Vergleich: manuell optimierter Flugweg vs. Algorithmus</div>
      <div class="row compact rkrk-sels rkrk-reg-pills" style="display:flex;gap:6px;flex-wrap:wrap;">
        <a href="rkrk_help.php" data-rkrk-help class="rkrk-sel rkrk-reg-pill" title="Hilfe zur aktuellen Sicht (F1)">Hilfe</a>
        <a href="rkrk_feld_editor.php" class="rkrk-sel rkrk-reg-pill">Feld-Editor</a>
      </div>
      <div class="row">
        <div class="quick-slot-panel">
          <div class="quick-slot-title">Schnellzugriff Felder</div>
          <div class="quick-slot-help">Klick: laden · Alt+Klick: aktuelles Feld speichern</div>
          <div id="quickSlotGridPanel" class="quick-slot-grid"></div>
        </div>
      </div>
      <div class="row">
        <label>Feldname suchen</label>
        <input id="fieldNameInput" class="field-select-compact" list="fieldNameList" placeholder="Feldname oder Feld-ID eingeben..." />
        <datalist id="fieldNameList"></datalist>
      </div>
      <select id="fieldSelect" style="display:none;"></select>
      <div class="row compact">
        <button id="setDefaultFieldBtn" type="button">Aktuelles Feld als Default speichern</button>
      </div>
      <div class="row">
        <label>Planner</label>
        <select id="plannerMode">
          <option value="php">PHP (schnell)</option>
          <option value="python">Python StdLib (präziser)</option>
        </select>
      </div>
      <div class="row">
        <label>Modellversion</label>
        <select id="modelSelect"></select>
      </div>
      <div class="row">
        <button id="resetSettingsBtn" type="button">Settings reset</button>
      </div>
      <div class="row">
        <a href="step_requirements.php" style="display:block;padding:8px;background:var(--brand-dark,#48673A);color:#fff;text-decoration:none;text-align:center;border-radius:6px;">Schritt-Requirements steuern</a>
      </div>
      <div class="row">
        <a href="field_editor.php?from=analyse_ui.php" target="_blank" rel="noopener" style="display:block;padding:8px;background:var(--brand-dark,#48673A);color:#fff;text-decoration:none;text-align:center;border-radius:6px;">Zum Feld-Editor (vor)</a>
      </div>
      <div class="row">
        <label>Kartenbasis</label>
        <select id="baseMapMode">
          <option value="osm">OpenStreetMap</option>
          <option value="swisstopo" selected>map.geo.admin.ch (swisstopo)</option>
          <option value="swissimage">map.geo.admin.ch (Satellit / SWISSIMAGE)</option>
        </select>
      </div>
      <div class="row"><button id="runBtn">Analyse starten</button></div>
      <details class="left-text-section" id="leftTextSection">
        <summary>Textteil unten (einklappbar)</summary>
        <div class="left-text-content">
          <div id="statusBox"></div>
          <div id="specialCasesBox"></div>
          <div id="decisionBox"></div>
      <div id="metrics"></div>
          <div id="impactBox"></div>
          <div id="effectiveParamsBox"></div>
          <div id="stepResultsBox"></div>
          <div id="executionModelBox"></div>
    </div>
      </details>
    </div>
    <div class="map-wrap">
      <div id="map" class="map"></div>
      <div id="dabsPopup" class="rkrk-dabs-popup" style="display:none">
        <button type="button" class="pop-close" id="dabsPopupClose" aria-label="Schliessen">×</button>
        <div id="dabsPopupBody"></div>
      </div>
      <button type="button" class="rkrk-help-fab" data-rkrk-help title="Hilfe zur aktuellen Sicht (F1 / ?)" aria-expanded="false">?</button>
      <button id="mapSetupBtn" class="map-setup-btn rkrk-setup-btn" title="Setup: Boxen ein-/ausblenden" type="button">⚙</button>
      <div id="mapSetupPanel" class="map-setup-panel rkrk-sels rkrk-reg-pills">
        <div class="title">Boxen anzeigen</div>
        <label class="rkrk-sel rkrk-reg-pill" title="Anzeigekontrolle fuer Pfade und Abdeckung"><input type="checkbox" checked data-rkrk-box-for="display" /> Anzeigekontrolle</label>
        <label class="rkrk-sel rkrk-reg-pill" title="Kontext-Hilfe zur aktuellen Analyse"><input type="checkbox" data-rkrk-box-for="context" /> Kontext-Hilfe</label>
        <label class="rkrk-sel rkrk-reg-pill" title="Farbskala AGL"><input type="checkbox" checked data-rkrk-box-for="legend" /> Farbskala AGL</label>
        <label class="rkrk-sel rkrk-reg-pill" title="Feldcharakteristik"><input type="checkbox" checked data-rkrk-box-for="characteristics" /> Feldcharakteristik</label>
        <label class="rkrk-sel rkrk-reg-pill" title="W/S/H Hinweise"><input type="checkbox" checked data-rkrk-box-for="scale" /> W/S/H Hinweise</label>
        <label class="rkrk-sel rkrk-reg-pill" title="Analyse-Boxen auf der Karte"><input type="checkbox" checked data-rkrk-box-for="analysis" /> Analyse-Boxen</label>
        <label class="rkrk-sel rkrk-reg-pill" title="Schnellzugriff Felder"><input type="checkbox" checked data-rkrk-box-for="quickslots" /> Schnellzugriff</label>
        <label class="rkrk-sel rkrk-reg-pill" title="Feldnavigation"><input type="checkbox" checked data-rkrk-box-for="fieldnav" /> Feldnavigation</label>
      </div>
      <div id="mapHoverTip" class="map-hover-tip"></div>
      <div id="mapQuickHelp" class="map-quick-help" data-rkrk-box="context" data-rkrk-box-label="❓ Kontext-Hilfe" data-rkrk-box-default="off" style="display:none">
        <strong>Kontext-Hilfe</strong><br>
        Feld laden, Analyse starten, Anzeigekontrolle nutzen. Ausfuehrliche Hilfe: ? / F1.
      </div>
      <div id="mapLegend" class="map-legend" data-rkrk-box="legend" data-rkrk-box-label="🎨 Farbskala AGL">
        <div class="legend-title">Farbskala AGL</div>
        <div class="legend-scale-wrap">
          <span class="legend-sign">-</span>
          <div class="legend-scale"></div>
          <span class="legend-sign">+</span>
        </div>
        <div class="legend-row"><span>Tief</span><span id="legendMin">-</span></div>
        <div class="legend-row"><span>Mitte</span><span id="legendMid">-</span></div>
        <div class="legend-row"><span>Hoch</span><span id="legendMax">-</span></div>
      </div>
      <div id="coverageResultBox" class="coverage-result-box" data-rkrk-box="characteristics" data-rkrk-box-label="📈 Feldcharakteristik">
        <div class="title">Feldcharakteristik</div>
        <div class="line"><span>Abdeckungsquelle</span><span id="covSourceValue">-</span></div>
        <div class="line"><span>Abdeckung Feld</span><span id="covFieldValue">-</span></div>
        <div class="line"><span>Doppelt überflogen</span><span id="covDoubleValue">-</span></div>
        <div class="line"><span>HFOV-Breite auf Strecke (W)</span><span id="covHfovWidthValue">-</span></div>
        <div class="line"><span>DFOV-Durchmesser am Drehpunkt (D)</span><span id="covDfovDiameterValue">-</span></div>
        <div class="line"><span>Distanz Flugpfad</span><span id="covDistanceValue">-</span></div>
        <div class="line"><span>Delta Höhe hoch</span><span id="covClimbValue">-</span></div>
        <div class="line"><span>Delta Höhe runter</span><span id="covDescentValue">-</span></div>
        <div class="line"><span>Höchster Punkt (AGL)</span><span id="covMaxAglValue">-</span></div>
        <div class="line"><span>Tiefster Punkt Flugpfad</span><span id="covMinPathValue">-</span></div>
      </div>
      <div id="scaleWsBox" class="scale-ws-box" data-rkrk-box="scale" data-rkrk-box-label="📏 W/S/H Hinweise">
        <div><strong>h:</strong> <span id="scaleHValue">-</span></div>
        <div class="small">h = aktuelle Flughöhe für W/S-Berechnung</div>
        <div><strong>W:</strong> <span id="scaleWValue">-</span></div>
        <div class="small">W = Sichtbreite der Kamera am Boden</div>
        <div><strong>S:</strong> <span id="scaleSValue">-</span></div>
        <div class="small">S = Spurabstand zwischen zwei Bahnen</div>
      </div>
      <div id="displayControlBox" class="display-control-box" data-rkrk-box="display" data-rkrk-box-label="Anzeigekontrolle">
        <div class="title">Anzeigekontrolle</div>
        <div class="group-title">Flugpfade</div>
        <div class="rkrk-sels rkrk-reg-pills">
        <label class="rkrk-sel rkrk-reg-pill"><input id="showManualPath" type="checkbox" checked /> Flugplan (orignal)</label>
        <label class="rkrk-sel rkrk-reg-pill"><input id="showAlgorithmPath" type="checkbox" checked /> Flugplan (neu)</label>
        </div>

        <div class="control-divider"></div>
        <div class="group-title">Abdeckung</div>
        <div class="group-note">Abdeckung ist nur aktiv, wenn mindestens ein Flugpfad sichtbar ist.</div>
        <div class="rkrk-sels rkrk-reg-pills">
        <label class="rkrk-sel rkrk-reg-pill"><input id="showCoverageCircles" type="checkbox" /> Abdeckung</label>
        <label class="rkrk-sel rkrk-reg-pill"><input id="showCoverageOnManualPath" type="checkbox" /> Quelle: Original-Flugpfad</label>
        <label class="rkrk-sel rkrk-reg-pill"><input id="showUncoveredAreas" type="checkbox" checked /> Nicht abgedeckte Flaechen</label>
        </div>

        <div class="control-divider"></div>
        <div class="group-title">Algorithmus-Zusatzinfos</div>
        <div class="rkrk-sels rkrk-reg-pills">
        <label class="rkrk-sel rkrk-reg-pill"><input id="showBasePatterns" type="checkbox" checked /> Grundmuster</label>
        <label class="rkrk-sel rkrk-reg-pill"><input id="showSectionDirections" type="checkbox" checked /> Flugrichtung pro Sektor</label>
        <label class="rkrk-sel rkrk-reg-pill"><input id="showCorrectionPoints" type="checkbox" checked /> Korrekturpunkte markieren</label>
        </div>

        <div class="control-divider"></div>
        <div class="group-title">Kontrollpunkte</div>
        <div class="rkrk-sels rkrk-reg-pills">
        <label class="rkrk-sel rkrk-reg-pill"><input id="showControlPoints" type="checkbox" checked /> In Flugbahn</label>
        <label class="rkrk-sel rkrk-reg-pill"><input id="showFieldControlPoints" type="checkbox" checked /> im Feld</label>
        </div>

        <div class="control-divider"></div>
        <div class="group-title">Analysemodus</div>
        <div class="rkrk-sels rkrk-reg-pills">
        <label class="rkrk-sel rkrk-reg-pill"><input id="useFieldReferenceAgl" type="checkbox" checked /> Feld-Referenzflughöhe verwenden</label>
        </div>
        <div class="control-divider"></div>
        <div class="group-title">DABS</div>
        <div class="rkrk-sels rkrk-reg-pills">
        <label class="rkrk-sel rkrk-reg-pill" title="Skyguide DABS bis 200 m AGL. Klick auf ein Gebiet zeigt die Bemerkung."><input id="showDabsLayer" type="checkbox" /> DABS bis 200 m AGL</label>
        </div>
        <div id="dabsStatus"></div>
      </div>
      <div id="analysisControlBox" class="analysis-control-box" data-rkrk-box="analysis" data-rkrk-box-label="Analyse-Boxen">
        <div class="title">Analyse-Boxen</div>
        <div class="rkrk-sels rkrk-reg-pills">
        <label class="rkrk-sel rkrk-reg-pill"><input id="showBlockStatus" type="checkbox" checked /> Systemstatus</label>
        <label class="rkrk-sel rkrk-reg-pill"><input id="showBlockSpecialCases" type="checkbox" checked /> Spezialfaelle Report</label>
        <label class="rkrk-sel rkrk-reg-pill"><input id="showBlockDecision" type="checkbox" checked /> Feldbewertung</label>
        <label class="rkrk-sel rkrk-reg-pill"><input id="showBlockMetrics" type="checkbox" checked /> Metriken</label>
        <label class="rkrk-sel rkrk-reg-pill"><input id="showBlockImpact" type="checkbox" checked /> Impact</label>
        <label class="rkrk-sel rkrk-reg-pill"><input id="showBlockEffectiveParams" type="checkbox" checked /> Effektive Parameter</label>
        <label class="rkrk-sel rkrk-reg-pill"><input id="showBlockStepResults" type="checkbox" checked /> Schritt-Resultate</label>
        <label class="rkrk-sel rkrk-reg-pill"><input id="showBlockExecutionModel" type="checkbox" checked /> Ausfuehrbares Modell</label>
        </div>
        <div class="analysis-control-actions">
          <button id="hideAllBlocksBtn" type="button">Alle aus</button>
          <button id="showAllBlocksBtn" type="button">Alle an</button>
        </div>
      </div>
      <div id="quickSlotBox" class="quick-slot-box" data-rkrk-box="quickslots" data-rkrk-box-label="⚡ Schnellzugriff">
        <div class="quick-slot-title">Schnellzugriff Felder</div>
        <div class="quick-slot-help">Klick: laden · Alt+Klick: aktuelles Feld speichern</div>
        <div id="quickSlotGridMap" class="quick-slot-grid"></div>
      </div>
      <div id="fieldNavBox" class="field-nav-box" data-rkrk-box="fieldnav" data-rkrk-box-label="⇄ Feldnavigation">
        <button id="prevFieldBtn" type="button" class="field-nav-btn">Zurueck</button>
        <button id="nextFieldBtn" type="button" class="field-nav-btn">Weiter</button>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/ol@7.5.2/dist/ol.js"></script>
  <script src="rkrk_swiss_overlays.js?v=20261008dabs"></script>
  <script src="rkrk_help_catalog.js?v=20260907help"></script>
  <script src="rkrk_help.js"></script>
  <script src="rkrk_layout.js"></script>
  <script>
    const fieldSelect = document.getElementById('fieldSelect');
    const fieldNameInput = document.getElementById('fieldNameInput');
    const fieldNameList = document.getElementById('fieldNameList');
    const runBtn = document.getElementById('runBtn');
    const setDefaultFieldBtn = document.getElementById('setDefaultFieldBtn');
    const resetSettingsBtn = document.getElementById('resetSettingsBtn');
    const prevFieldBtn = document.getElementById('prevFieldBtn');
    const nextFieldBtn = document.getElementById('nextFieldBtn');
    const quickSlotGridMap = document.getElementById('quickSlotGridMap');
    const quickSlotGridPanel = document.getElementById('quickSlotGridPanel');
    const metricsEl = document.getElementById('metrics');
    const statusBoxEl = document.getElementById('statusBox');
    const specialCasesBoxEl = document.getElementById('specialCasesBox');
    const decisionBoxEl = document.getElementById('decisionBox');
    const effectiveParamsBoxEl = document.getElementById('effectiveParamsBox');
    const impactBoxEl = document.getElementById('impactBox');
    const stepResultsBoxEl = document.getElementById('stepResultsBox');
    const executionModelBoxEl = document.getElementById('executionModelBox');
    const showBlockStatusEl = document.getElementById('showBlockStatus');
    const showBlockSpecialCasesEl = document.getElementById('showBlockSpecialCases');
    const showBlockDecisionEl = document.getElementById('showBlockDecision');
    const showBlockMetricsEl = document.getElementById('showBlockMetrics');
    const showBlockImpactEl = document.getElementById('showBlockImpact');
    const showBlockEffectiveParamsEl = document.getElementById('showBlockEffectiveParams');
    const showBlockStepResultsEl = document.getElementById('showBlockStepResults');
    const showBlockExecutionModelEl = document.getElementById('showBlockExecutionModel');
    const hideAllBlocksBtnEl = document.getElementById('hideAllBlocksBtn');
    const showAllBlocksBtnEl = document.getElementById('showAllBlocksBtn');
    const showManualPathEl = document.getElementById('showManualPath');
    const showAlgorithmPathEl = document.getElementById('showAlgorithmPath');
    const showBasePatternsEl = document.getElementById('showBasePatterns');
    const showSectionDirectionsEl = document.getElementById('showSectionDirections');
    const showCoverageCirclesEl = document.getElementById('showCoverageCircles');
    const showCoverageOnManualPathEl = document.getElementById('showCoverageOnManualPath');
    const showUncoveredAreasEl = document.getElementById('showUncoveredAreas');
    const showControlPointsEl = document.getElementById('showControlPoints');
    const showFieldControlPointsEl = document.getElementById('showFieldControlPoints');
    const showCorrectionPointsEl = document.getElementById('showCorrectionPoints');
    const useFieldReferenceAglEl = document.getElementById('useFieldReferenceAgl');
    const mapLegendEl = document.getElementById('mapLegend');
    const mapQuickHelpEl = document.getElementById('mapQuickHelp');
    const mapHoverTipEl = document.getElementById('mapHoverTip');
    const mapWrapEl = document.querySelector('.map-wrap');
    const coverageResultBoxEl = document.getElementById('coverageResultBox');
    const scaleWsBoxEl = document.getElementById('scaleWsBox');
    const displayControlBoxEl = document.getElementById('displayControlBox');
    const analysisControlBoxEl = document.getElementById('analysisControlBox');
    const quickSlotBoxEl = document.getElementById('quickSlotBox');
    const fieldNavBoxEl = document.getElementById('fieldNavBox');
    const legendMinEl = document.getElementById('legendMin');
    const legendMidEl = document.getElementById('legendMid');
    const legendMaxEl = document.getElementById('legendMax');
    const covFieldValueEl = document.getElementById('covFieldValue');
    const covSourceValueEl = document.getElementById('covSourceValue');
    const covDoubleValueEl = document.getElementById('covDoubleValue');
    const covHfovWidthValueEl = document.getElementById('covHfovWidthValue');
    const covDfovDiameterValueEl = document.getElementById('covDfovDiameterValue');
    const covDistanceValueEl = document.getElementById('covDistanceValue');
    const covClimbValueEl = document.getElementById('covClimbValue');
    const covDescentValueEl = document.getElementById('covDescentValue');
    const covMaxAglValueEl = document.getElementById('covMaxAglValue');
    const covMinPathValueEl = document.getElementById('covMinPathValue');
    const scaleHValueEl = document.getElementById('scaleHValue');
    const scaleWValueEl = document.getElementById('scaleWValue');
    const scaleSValueEl = document.getElementById('scaleSValue');
    const plannerModeEl = document.getElementById('plannerMode');
    const baseMapModeEl = document.getElementById('baseMapMode');
    const modelSelectEl = document.getElementById('modelSelect');
    const sectionDirectionLabelDefault = 'Flugrichtung pro Sektor';
    const analysisBlockToggles = {
      status: { el: statusBoxEl, input: showBlockStatusEl },
      specialCases: { el: specialCasesBoxEl, input: showBlockSpecialCasesEl },
      decision: { el: decisionBoxEl, input: showBlockDecisionEl },
      metrics: { el: metricsEl, input: showBlockMetricsEl },
      impact: { el: impactBoxEl, input: showBlockImpactEl },
      effectiveParams: { el: effectiveParamsBoxEl, input: showBlockEffectiveParamsEl },
      stepResults: { el: stepResultsBoxEl, input: showBlockStepResultsEl },
      executionModel: { el: executionModelBoxEl, input: showBlockExecutionModelEl },
    };
    let fieldsById = {};
    let fieldSearchEntries = [];
    let modelMap = {};
    const DEFAULT_FIELD_KEY = 'rkrk.defaultFieldId';
    const UI_SETTINGS_KEY = 'rkrk.analyseUiSettings.v1';
    const QUICK_SLOT_COUNT = 12;
    const terrainHeightCache = new Map();
    let pendingUiSettings = null;
    let collapseState = {};
    let quickFieldSlots = new Array(QUICK_SLOT_COUNT).fill('');
    let floatingBoxesState = {};
    const TURN_POINT_MIN_ANGLE_DEG = 8.0;
    const COVERAGE_GRID_N = 140;

    const osm = new ol.layer.Tile({
      source: new ol.source.OSM(),
      visible: false,
    });
    const swisstopo = new ol.layer.Tile({
      source: new ol.source.XYZ({
        url: 'https://wmts.geo.admin.ch/1.0.0/ch.swisstopo.pixelkarte-farbe/default/current/3857/{z}/{x}/{y}.jpeg',
        attributions: 'Map data: <a href="https://www.swisstopo.admin.ch/" target="_blank" rel="noopener">swisstopo</a>',
        maxZoom: 19,
      }),
      visible: true,
    });
    const swissimage = new ol.layer.Tile({
      source: new ol.source.XYZ({
        url: 'https://wmts.geo.admin.ch/1.0.0/ch.swisstopo.swissimage/default/current/3857/{z}/{x}/{y}.jpeg',
        attributions: 'Map data: <a href="https://www.swisstopo.admin.ch/" target="_blank" rel="noopener">swisstopo</a>',
        maxZoom: 19,
      }),
      visible: false,
    });
    const source = new ol.source.Vector();
    const vector = new ol.layer.Vector({ source });
    const map = new ol.Map({
      target: 'map',
      layers: [osm, swisstopo, swissimage, vector],
      view: new ol.View({ center: ol.proj.fromLonLat([8.54, 47.28]), zoom: 13 }),
    });
    window.rkrkUpdateMapSize = function () {
      if (map && typeof map.updateSize === 'function') map.updateSize();
    };
    map.addControl(new ol.control.ScaleLine({
      units: 'metric',
      bar: true,
      steps: 4,
      text: true,
      minWidth: 140,
    }));
    map.on('pointermove', (ev) => {
      if (!mapHoverTipEl) return;
      const feature = map.forEachFeatureAtPixel(ev.pixel, (f) => f);
      const reason = feature ? String(feature.get('hover_reason') || '') : '';
      const title = feature ? String(feature.get('hover_title') || '') : '';
      if (!reason) {
        mapHoverTipEl.style.display = 'none';
        return;
      }
      mapHoverTipEl.style.display = '';
      mapHoverTipEl.style.left = `${ev.pixel[0] + 16}px`;
      mapHoverTipEl.style.top = `${ev.pixel[1] + 16}px`;
      mapHoverTipEl.innerHTML = `<strong>${title || 'Korrekturpunkt'}</strong><br>${reason}`;
    });
    let dabsCtl = null;
    function syncAnalyseDabs() {
      const box = document.getElementById('showDabsLayer');
      const on = !!(box && box.checked);
      if (!window.RkrkSwissOverlays || typeof window.RkrkSwissOverlays.attachDabs !== 'function') return;
      if (!dabsCtl) {
        dabsCtl = window.RkrkSwissOverlays.attachDabs(map, {
          url: 'rkrk_dabs.php',
          statusEl: document.getElementById('dabsStatus'),
        });
      }
      if (!dabsCtl) return;
      if (on) dabsCtl.enable(0.75);
      else dabsCtl.disable();
    }
    const showDabsLayerEl = document.getElementById('showDabsLayer');
    if (showDabsLayerEl) {
      showDabsLayerEl.addEventListener('change', () => {
        syncAnalyseDabs();
        saveUiSettings();
      });
    }
    map.on('singleclick', (evt) => {
      if (dabsCtl && dabsCtl.showAtEvent(evt)) return;
    });

    function applyBaseMapMode() {
      const mode = baseMapModeEl.value;
      const useSwisstopo = mode === 'swisstopo';
      const useSwissimage = mode === 'swissimage';
      osm.setVisible(!useSwisstopo && !useSwissimage);
      swisstopo.setVisible(useSwisstopo);
      swissimage.setVisible(useSwissimage);
    }

    function styleColor(color) {
      return new ol.style.Style({
        stroke: new ol.style.Stroke({ color, width: 3 }),
        image: new ol.style.Circle({ radius: 3, fill: new ol.style.Fill({ color }) }),
      });
    }

    function lineFeature(path, color) {
      const coords = path.map(p => ol.proj.fromLonLat([p[0], p[1]]));
      const feat = new ol.Feature({ geometry: new ol.geom.LineString(coords) });
      feat.setStyle(styleColor(color));
      return feat;
    }

    function colorForRelativeHeight(t) {
      // Red (low) -> Yellow (mid) -> Green (high)
      const clamped = Math.max(0, Math.min(1, t));
      let r, g, b = 0;
      if (clamped <= 0.5) {
        const k = clamped / 0.5;
        r = 255;
        g = Math.round(255 * k);
      } else {
        const k = (clamped - 0.5) / 0.5;
        r = Math.round(255 * (1 - k));
        g = 255;
      }
      return `rgb(${r},${g},${b})`;
    }

    function pointFeatures(path, relHeights) {
      const pts = path || [];
      const rels = (relHeights || [])
        .map((v) => Number(v))
        .filter((v) => Number.isFinite(v));
      const minRel = rels.length ? Math.min(...rels) : NaN;
      const maxRel = rels.length ? Math.max(...rels) : NaN;
      const span = Number.isFinite(minRel) && Number.isFinite(maxRel) ? Math.max(1e-9, maxRel - minRel) : NaN;

      return pts.map((p, idx) => {
        const relVal = Number(relHeights && relHeights[idx]);
        const relText = Number.isFinite(relVal) ? `${relVal >= 0 ? '+' : ''}${relVal.toFixed(1)}m` : '';
        const t = (Number.isFinite(relVal) && Number.isFinite(span)) ? (relVal - minRel) / span : 0.5;
        const pointColor = colorForRelativeHeight(t);
        const feat = new ol.Feature({ geometry: new ol.geom.Point(ol.proj.fromLonLat([p[0], p[1]])) });
        feat.setStyle(new ol.style.Style({
          image: new ol.style.Circle({
            radius: 4,
            fill: new ol.style.Fill({ color: pointColor }),
            stroke: new ol.style.Stroke({ color: '#ffffff', width: 1 }),
          }),
          text: relText ? new ol.style.Text({
            text: relText,
            font: '12px Arial',
            offsetY: -12,
            fill: new ol.style.Fill({ color: '#6b7280' }),
            stroke: new ol.style.Stroke({ color: '#ffffff', width: 3 }),
          }) : undefined,
        }));
        return feat;
      });
    }

    function buildPathControlPointSamples(basePath, stepMeters = 25) {
      const pts = Array.isArray(basePath) ? basePath : [];
      if (pts.length < 2) return pts.map((p) => [Number(p[0]), Number(p[1])]).filter((p) => Number.isFinite(p[0]) && Number.isFinite(p[1]));
      const firstXY = ol.proj.fromLonLat([pts[0][0], pts[0][1]]);
      const lonLat = ol.proj.toLonLat(firstXY);
      const latRad = Number(lonLat[1]) * Math.PI / 180;
      const cosLat = Math.cos(latRad);
      const scale = (Number.isFinite(cosLat) && cosLat > 1e-6) ? (1 / cosLat) : 1;
      const stepMap = Math.max(8, Number(stepMeters) || 25) * scale;
      const out = [];
      for (let i = 0; i < pts.length - 1; i++) {
        const a = ol.proj.fromLonLat([pts[i][0], pts[i][1]]);
        const b = ol.proj.fromLonLat([pts[i + 1][0], pts[i + 1][1]]);
        const dx = b[0] - a[0];
        const dy = b[1] - a[1];
        const segLen = Math.hypot(dx, dy);
        if (segLen <= 1e-6) continue;
        const segments = Math.max(1, Math.ceil(segLen / stepMap));
        for (let s = 0; s < segments; s++) {
          if (i > 0 && s === 0) continue;
          const t = s / segments;
          const xy = [a[0] + dx * t, a[1] + dy * t];
          const ll = ol.proj.toLonLat(xy);
          if (!Array.isArray(ll) || ll.length < 2) continue;
          out.push([Number(ll[0]), Number(ll[1])]);
        }
      }
      const last = pts[pts.length - 1];
      if (Array.isArray(last) && last.length >= 2) {
        out.push([Number(last[0]), Number(last[1])]);
      }
      return out;
    }

    async function computeWaypointFlightData(path) {
      const pts = Array.isArray(path) ? path : [];
      if (!pts.length) return [];
      const startAlt = Number(pts[0] && pts[0][2]);
      let startTerrain = NaN;
      try {
        startTerrain = await fetchGeoAdminTerrainHeight(pts[0][0], pts[0][1]);
      } catch (_) {
        startTerrain = NaN;
      }
      const out = new Array(pts.length).fill(null);
      const chunkSize = 20;
      for (let i = 0; i < pts.length; i += chunkSize) {
        const chunk = pts.slice(i, i + chunkSize);
        const vals = await Promise.all(
          chunk.map(async (p) => {
            const alt = Number(p && p[2]);
            try {
              const terrain = await fetchGeoAdminTerrainHeight(p[0], p[1]);
              // Top: current flight height over local terrain (AGL at waypoint).
              const top = Number.isFinite(alt)
                ? alt
                : NaN;
              // Bottom: local delta to field startpoint (terrain delta).
              const bottom = (Number.isFinite(terrain) && Number.isFinite(startTerrain))
                ? (terrain - startTerrain)
                : NaN;
              return { top, bottom };
            } catch (_) {
              return { top: NaN, bottom: NaN };
            }
          })
        );
        vals.forEach((v, j) => {
          out[i + j] = v;
        });
      }
      return out;
    }

    function waypointFeatures(path, flightData, color, timingData = []) {
      const pts = Array.isArray(path) ? path : [];
      const data = Array.isArray(flightData) ? flightData : [];
      const timing = Array.isArray(timingData) ? timingData : [];
      return pts.map((p, idx) => {
        const d = data[idx] || {};
        const t = timing[idx] || {};
        const top = Number(d.top);
        const bottom = Number(d.bottom);
        const cumS = Number(t.cumulative_time_s);
        const segS = Number(t.segment_time_s);
        const turnS = Number(t.turn_delay_s);
        const turnDeg = Number(t.turn_angle_deg);
        const isTurn = !!t.is_turn;
        const topText = Number.isFinite(top) ? `${top >= 0 ? '+' : ''}${top.toFixed(1)}m` : '-';
        const bottomText = Number.isFinite(bottom) ? `${bottom >= 0 ? '+' : ''}${bottom.toFixed(1)}m` : '-';
        const feat = new ol.Feature({ geometry: new ol.geom.Point(ol.proj.fromLonLat([p[0], p[1]])) });
        const hoverParts = [];
        if (Number.isFinite(cumS)) hoverParts.push(`Zeit kumulativ: ${(cumS / 60).toFixed(2)} min (${cumS.toFixed(1)} s)`);
        if (Number.isFinite(segS)) hoverParts.push(`Segment: ${segS.toFixed(1)} s`);
        if (Number.isFinite(turnS) && turnS > 0) hoverParts.push(`Drehzuschlag: +${turnS.toFixed(1)} s`);
        if (Number.isFinite(turnDeg) && isTurn) hoverParts.push(`Drehwinkel: ${turnDeg.toFixed(1)}°`);
        if (hoverParts.length) {
          feat.set('hover_title', `Wegpunkt ${idx + 1}`);
          feat.set('hover_reason', hoverParts.join('<br>'));
        }
        feat.setStyle([
          new ol.style.Style({
            text: new ol.style.Text({
              text: topText,
              font: '11px Arial',
              offsetY: -14,
              fill: new ol.style.Fill({ color }),
              stroke: new ol.style.Stroke({ color: '#ffffff', width: 3 }),
            }),
          }),
          new ol.style.Style({
            text: new ol.style.Text({
              text: bottomText,
              font: '11px Arial',
              offsetY: 12,
              fill: new ol.style.Fill({ color }),
              stroke: new ol.style.Stroke({ color: '#ffffff', width: 3 }),
            }),
          }),
        ]);
        return feat;
      });
    }

    function flightPathSegments(path) {
      const pts = Array.isArray(path) ? path : [];
      if (pts.length < 2) return [];
      const segs = [];
      // Use all consecutive flight segments starting at mission start point.
      for (let i = 0; i + 1 < pts.length; i++) {
        segs.push([pts[i], pts[i + 1]]);
      }
      return segs;
    }

    function computeWaypointTiming(path, speedMps, turnDelayS = 1.5, minTurnDeg = TURN_POINT_MIN_ANGLE_DEG) {
      const pts = Array.isArray(path) ? path : [];
      if (!pts.length || !Number.isFinite(speedMps) || speedMps <= 0) return [];
      const out = new Array(pts.length).fill(null);
      let cum = 0;
      out[0] = {
        index: 0,
        segment_time_s: 0,
        turn_delay_s: 0,
        turn_angle_deg: 0,
        is_turn: false,
        cumulative_time_s: 0,
      };
      for (let i = 1; i < pts.length; i++) {
        const a = ol.proj.fromLonLat([pts[i - 1][0], pts[i - 1][1]]);
        const b = ol.proj.fromLonLat([pts[i][0], pts[i][1]]);
        const dx = b[0] - a[0];
        const dy = b[1] - a[1];
        const segS = Math.sqrt(dx * dx + dy * dy) / speedMps;
        cum += segS;
        let turnAngleDeg = 0;
        let isTurn = false;
        let addTurn = 0;
        if (i < pts.length - 1) {
          const c = ol.proj.fromLonLat([pts[i + 1][0], pts[i + 1][1]]);
          const v1x = b[0] - a[0];
          const v1y = b[1] - a[1];
          const v2x = c[0] - b[0];
          const v2y = c[1] - b[1];
          const n1 = Math.hypot(v1x, v1y);
          const n2 = Math.hypot(v2x, v2y);
          if (n1 > 1e-9 && n2 > 1e-9) {
            const dot = Math.max(-1, Math.min(1, (v1x * v2x + v1y * v2y) / (n1 * n2)));
            turnAngleDeg = Math.acos(dot) * 180 / Math.PI;
            isTurn = turnAngleDeg >= minTurnDeg;
          }
          if (isTurn && Number.isFinite(turnDelayS) && turnDelayS > 0) {
            addTurn = turnDelayS;
            cum += addTurn;
          }
        }
        out[i] = {
          index: i,
          segment_time_s: segS,
          turn_delay_s: addTurn,
          turn_angle_deg: turnAngleDeg,
          is_turn: isTurn,
          cumulative_time_s: cum,
        };
      }
      return out;
    }

    function coveragePathInsideField(path, fieldPolygonLonLat) {
      const pts = Array.isArray(path) ? path : [];
      const field = Array.isArray(fieldPolygonLonLat) ? fieldPolygonLonLat : [];
      if (pts.length < 2 || field.length < 3) return [];
      const polyXY = field.map((p) => ol.proj.fromLonLat([p[0], p[1]]));
      const inside = pts.map((p) => {
        const xy = ol.proj.fromLonLat([p[0], p[1]]);
        return pointInPolygonXY(xy, polyXY);
      });
      let firstIn = -1;
      let lastIn = -1;
      for (let i = 0; i < inside.length; i++) {
        if (inside[i]) {
          firstIn = i;
          break;
        }
      }
      for (let i = inside.length - 1; i >= 0; i--) {
        if (inside[i]) {
          lastIn = i;
          break;
        }
      }
      if (firstIn < 0 || lastIn < firstIn) return [];
      return pts.slice(firstIn, lastIn + 1);
    }

    function coverageCircleFeatures(path, radiusM, color = 'rgba(30,144,255,0.22)') {
      const pts = Array.isArray(path) ? path : [];
      if (!Number.isFinite(radiusM) || radiusM <= 0) return [];
      return pts.map((p) => {
        const center = ol.proj.fromLonLat([p[0], p[1]]);
        const feat = new ol.Feature({ geometry: new ol.geom.Circle(center, radiusM) });
        feat.setStyle(new ol.style.Style({
          stroke: new ol.style.Stroke({ color: 'rgba(30,144,255,0.65)', width: 1.2 }),
          fill: new ol.style.Fill({ color }),
        }));
        return feat;
      });
    }

    function turningPointCenters(path, forwardLookM = 0) {
      const pts = Array.isArray(path) ? path : [];
      if (!pts.length) return [];
      const latVals = pts.map((p) => Number(p[1])).filter((v) => Number.isFinite(v));
      const latRef = latVals.length ? (latVals.reduce((a, b) => a + b, 0) / latVals.length) : 47.0;
      const mercatorScale = 1 / Math.max(1e-6, Math.cos((latRef * Math.PI) / 180.0));
      const halfForward = Math.max(0, Number(forwardLookM) * 0.5 * mercatorScale);
      const shiftForward = (p, ux, uy) => [p[0] + ux * halfForward, p[1] + uy * halfForward];
      const centers = [];
      // Start point: camera looks half VFOV ahead before continuing.
      if (pts.length >= 2 && halfForward > 0) {
        const p0 = ol.proj.fromLonLat([pts[0][0], pts[0][1]]);
        const p1 = ol.proj.fromLonLat([pts[1][0], pts[1][1]]);
        const dx = p1[0] - p0[0], dy = p1[1] - p0[1];
        const n = Math.hypot(dx, dy);
        const ux = n > 1e-9 ? dx / n : 0;
        const uy = n > 1e-9 ? dy / n : 0;
        centers.push(shiftForward(p0, ux, uy));
      } else {
        centers.push(ol.proj.fromLonLat([pts[0][0], pts[0][1]]));
      }
      if (pts.length < 3) return centers;
      for (let i = 1; i + 1 < pts.length; i++) {
        const p0 = ol.proj.fromLonLat([pts[i - 1][0], pts[i - 1][1]]);
        const p1 = ol.proj.fromLonLat([pts[i][0], pts[i][1]]);
        const p2 = ol.proj.fromLonLat([pts[i + 1][0], pts[i + 1][1]]);
        const v1x = p1[0] - p0[0], v1y = p1[1] - p0[1];
        const v2x = p2[0] - p1[0], v2y = p2[1] - p1[1];
        const n1 = Math.hypot(v1x, v1y), n2 = Math.hypot(v2x, v2y);
        if (n1 < 1e-9 || n2 < 1e-9) continue;
        const dot = Math.max(-1, Math.min(1, (v1x * v2x + v1y * v2y) / (n1 * n2)));
        const ang = Math.acos(dot) * 180 / Math.PI;
        if (ang < TURN_POINT_MIN_ANGLE_DEG) continue;
        // Turn phase starts after looking ahead in incoming flight direction.
        const uxIn = v1x / n1;
        const uyIn = v1y / n1;
        centers.push(shiftForward(p1, uxIn, uyIn));
      }
      // Ensure DFOV is also represented at mission endpoint.
      if (pts.length >= 2) {
        const pe = ol.proj.fromLonLat([pts[pts.length - 1][0], pts[pts.length - 1][1]]);
        const pp = ol.proj.fromLonLat([pts[pts.length - 2][0], pts[pts.length - 2][1]]);
        const dx = pe[0] - pp[0], dy = pe[1] - pp[1];
        const n = Math.hypot(dx, dy);
        const ux = n > 1e-9 ? dx / n : 0;
        const uy = n > 1e-9 ? dy / n : 0;
        centers.push(shiftForward(pe, ux, uy));
      }
      return centers;
    }

    function turningPointCircleFeatures(path, diameterM, forwardLookM = 0, color = 'rgba(30,144,255,0.12)', strokeColor = 'rgba(30,144,255,0.30)') {
      const pts = Array.isArray(path) ? path : [];
      const d = Number(diameterM);
      if (pts.length < 1 || !Number.isFinite(d) || d <= 0) return [];
      const latVals = pts.map((p) => Number(p[1])).filter((v) => Number.isFinite(v));
      const latRef = latVals.length ? (latVals.reduce((a, b) => a + b, 0) / latVals.length) : 47.0;
      const mercatorScale = 1 / Math.max(1e-6, Math.cos((latRef * Math.PI) / 180.0));
      const r = d * 0.5 * mercatorScale;
      return turningPointCenters(pts, forwardLookM).map((center) => {
        const feat = new ol.Feature({ geometry: new ol.geom.Circle(center, r) });
        feat.setStyle(new ol.style.Style({
          stroke: new ol.style.Stroke({ color: strokeColor, width: 0.8 }),
          fill: new ol.style.Fill({ color }),
        }));
        return feat;
      });
    }

    function widthGuideFeatures(path, widthM, color = 'rgba(0,90,190,0.95)', label = null) {
      const segs = flightPathSegments(path);
      if (!segs.length || !Number.isFinite(widthM) || widthM <= 0) return [];
      const seg = segs[0];
      const a = ol.proj.fromLonLat([seg[0][0], seg[0][1]]);
      const b = ol.proj.fromLonLat([seg[1][0], seg[1][1]]);
      const dx = b[0] - a[0];
      const dy = b[1] - a[1];
      const n = Math.hypot(dx, dy);
      if (n < 1e-9) return [];
      const nx = -dy / n;
      const ny = dx / n;
      const mid = [(a[0] + b[0]) * 0.5, (a[1] + b[1]) * 0.5];
      const latVals = (path || []).map((p) => Number(p[1])).filter((v) => Number.isFinite(v));
      const latRef = latVals.length ? (latVals.reduce((s, v) => s + v, 0) / latVals.length) : 47.0;
      const mercatorScale = 1 / Math.max(1e-6, Math.cos((latRef * Math.PI) / 180.0));
      const len = widthM * mercatorScale;
      const p0 = [mid[0] - nx * len * 0.5, mid[1] - ny * len * 0.5];
      const p1 = [mid[0] + nx * len * 0.5, mid[1] + ny * len * 0.5];
      const line = new ol.Feature({ geometry: new ol.geom.LineString([p0, p1]) });
      const textLabel = label || `HFOV W ${widthM.toFixed(1)}m`;
      line.setStyle(new ol.style.Style({
        stroke: new ol.style.Stroke({ color, width: 2 }),
          text: new ol.style.Text({
            text: textLabel,
          font: '12px Arial',
          offsetY: -12,
          fill: new ol.style.Fill({ color }),
          stroke: new ol.style.Stroke({ color: '#ffffff', width: 3 }),
        }),
      }));
      return [line];
    }

    function turningDiameterGuideFeatures(path, diameterM, forwardLookM = 0, color = 'rgba(20,120,220,0.95)', label = null) {
      const pts = Array.isArray(path) ? path : [];
      const d = Number(diameterM);
      if (pts.length < 1 || !Number.isFinite(d) || d <= 0) return [];
      const latVals = pts.map((p) => Number(p[1])).filter((v) => Number.isFinite(v));
      const latRef = latVals.length ? (latVals.reduce((s, v) => s + v, 0) / latVals.length) : 47.0;
      const mercatorScale = 1 / Math.max(1e-6, Math.cos((latRef * Math.PI) / 180.0));
      const rMap = d * 0.5 * mercatorScale;
      const centers = turningPointCenters(pts, forwardLookM);
      if (!centers.length) return [];
      let center = centers[0];
      let nx = 0;
      let ny = 0;
      for (let i = 1; i + 1 < pts.length; i++) {
        const p0 = ol.proj.fromLonLat([pts[i - 1][0], pts[i - 1][1]]);
        const p1 = ol.proj.fromLonLat([pts[i][0], pts[i][1]]);
        const p2 = ol.proj.fromLonLat([pts[i + 1][0], pts[i + 1][1]]);
        const v1x = p1[0] - p0[0], v1y = p1[1] - p0[1];
        const v2x = p2[0] - p1[0], v2y = p2[1] - p1[1];
        const n1 = Math.hypot(v1x, v1y), n2 = Math.hypot(v2x, v2y);
        if (n1 < 1e-9 || n2 < 1e-9) continue;
        const dot = Math.max(-1, Math.min(1, (v1x * v2x + v1y * v2y) / (n1 * n2)));
        const ang = Math.acos(dot) * 180 / Math.PI;
        if (ang < TURN_POINT_MIN_ANGLE_DEG) continue;
        center = p1;
        const ux = v2x / n2;
        const uy = v2y / n2;
        nx = -uy;
        ny = ux;
        break;
      }
      if (Math.abs(nx) < 1e-9 && Math.abs(ny) < 1e-9 && pts.length >= 2) {
        const a0 = ol.proj.fromLonLat([pts[0][0], pts[0][1]]);
        const b0 = ol.proj.fromLonLat([pts[1][0], pts[1][1]]);
        const dx = b0[0] - a0[0];
        const dy = b0[1] - a0[1];
        const n = Math.hypot(dx, dy);
        if (n > 1e-9) {
          nx = -dy / n;
          ny = dx / n;
          center = a0;
        }
      }
      if (Math.abs(nx) < 1e-9 && Math.abs(ny) < 1e-9) return [];
      const a = [center[0] - nx * rMap, center[1] - ny * rMap];
      const b = [center[0] + nx * rMap, center[1] + ny * rMap];
      const line = new ol.Feature({ geometry: new ol.geom.LineString([a, b]) });
      line.setStyle(new ol.style.Style({
        stroke: new ol.style.Stroke({ color, width: 2.8 }),
        text: new ol.style.Text({
          text: label || `DFOV D ${d.toFixed(1)}m`,
          font: '12px Arial',
          offsetY: -12,
          fill: new ol.style.Fill({ color }),
          stroke: new ol.style.Stroke({ color: '#ffffff', width: 3 }),
        }),
      }));
      return [line];
    }

    function coverageCorridorFeatures(path, widthM, color = 'rgba(30,144,255,0.18)', edgeColor = 'rgba(30,144,255,0.30)') {
      const pts = Array.isArray(path) ? path : [];
      if (pts.length < 2 || !Number.isFinite(widthM) || widthM <= 0) return [];
      const latVals = path.map((p) => Number(p[1])).filter((v) => Number.isFinite(v));
      const latRef = latVals.length ? (latVals.reduce((a, b) => a + b, 0) / latVals.length) : 47.0;
      const mercatorScale = 1 / Math.max(1e-6, Math.cos((latRef * Math.PI) / 180.0));
      const widthMapM = widthM * mercatorScale;
      const coords = pts.map((p) => ol.proj.fromLonLat([p[0], p[1]]));
      const feat = new ol.Feature({ geometry: new ol.geom.LineString(coords) });
      feat.setStyle((_, resolution) => {
        const innerW = Math.max(1, widthMapM / Math.max(1e-9, resolution));
        const outerW = innerW + 1.2;
        return [
          new ol.style.Style({
            stroke: new ol.style.Stroke({
              color: edgeColor,
              width: outerW,
              lineCap: 'butt',
              lineJoin: 'round',
            }),
          }),
          new ol.style.Style({
            stroke: new ol.style.Stroke({
              color,
              width: innerW,
              lineCap: 'butt',
              lineJoin: 'round',
            }),
          }),
        ];
      });
      return [feat];
    }

    function vfovTerminalFeatures(path, widthM, forwardLookM, color = 'rgba(30,144,255,0.12)', edgeColor = 'rgba(30,144,255,0.30)') {
      const pts = Array.isArray(path) ? path : [];
      const w = Number(widthM);
      const fwd = Number(forwardLookM);
      if (pts.length < 2 || !Number.isFinite(w) || w <= 0 || !Number.isFinite(fwd) || fwd <= 0) return [];
      const latVals = pts.map((p) => Number(p[1])).filter((v) => Number.isFinite(v));
      const latRef = latVals.length ? (latVals.reduce((a, b) => a + b, 0) / latVals.length) : 47.0;
      const mercatorScale = 1 / Math.max(1e-6, Math.cos((latRef * Math.PI) / 180.0));
      const halfW = (w * 0.5) * mercatorScale;
      const halfF = (fwd * 0.5) * mercatorScale;

      const mkRect = (center, ux, uy, tMin, tMax) => {
        const nx = -uy, ny = ux;
        const pA = [center[0] + ux * tMin + nx * halfW, center[1] + uy * tMin + ny * halfW];
        const pB = [center[0] + ux * tMax + nx * halfW, center[1] + uy * tMax + ny * halfW];
        const pC = [center[0] + ux * tMax - nx * halfW, center[1] + uy * tMax - ny * halfW];
        const pD = [center[0] + ux * tMin - nx * halfW, center[1] + uy * tMin - ny * halfW];
        const feat = new ol.Feature({ geometry: new ol.geom.Polygon([[pA, pB, pC, pD, pA]]) });
        feat.setStyle(new ol.style.Style({
          stroke: new ol.style.Stroke({ color: edgeColor, width: 0.7 }),
          fill: new ol.style.Fill({ color }),
        }));
        return feat;
      };

      const s0a = ol.proj.fromLonLat([pts[0][0], pts[0][1]]);
      const s0b = ol.proj.fromLonLat([pts[1][0], pts[1][1]]);
      const d0x = s0b[0] - s0a[0], d0y = s0b[1] - s0a[1];
      const n0 = Math.hypot(d0x, d0y);
      if (n0 < 1e-9) return [];
      const u0x = d0x / n0, u0y = d0y / n0;

      const sla = ol.proj.fromLonLat([pts[pts.length - 2][0], pts[pts.length - 2][1]]);
      const slb = ol.proj.fromLonLat([pts[pts.length - 1][0], pts[pts.length - 1][1]]);
      const dlx = slb[0] - sla[0], dly = slb[1] - sla[1];
      const nl = Math.hypot(dlx, dly);
      if (nl < 1e-9) return [];
      const ulx = dlx / nl, uly = dly / nl;

      return [
        mkRect(s0a, u0x, u0y, -halfF, halfF), // start sees full VFOV rectangle
        mkRect(slb, ulx, uly, 0, halfF),      // end sees half VFOV forward
      ];
    }

    function pointInPolygonXY(point, poly) {
      let inside = false;
      for (let i = 0, j = poly.length - 1; i < poly.length; j = i++) {
        const xi = poly[i][0], yi = poly[i][1];
        const xj = poly[j][0], yj = poly[j][1];
        const intersect = ((yi > point[1]) !== (yj > point[1]))
          && (point[0] < (xj - xi) * (point[1] - yi) / ((yj - yi) || 1e-12) + xi);
        if (intersect) inside = !inside;
      }
      return inside;
    }

    function pointToSegmentDistSq(px, py, ax, ay, bx, by) {
      const abx = bx - ax;
      const aby = by - ay;
      const ab2 = abx * abx + aby * aby;
      if (ab2 <= 1e-12) {
        const dx = px - ax;
        const dy = py - ay;
        return dx * dx + dy * dy;
      }
      let t = ((px - ax) * abx + (py - ay) * aby) / ab2;
      t = Math.max(0, Math.min(1, t));
      const qx = ax + t * abx;
      const qy = ay + t * aby;
      const dx = px - qx;
      const dy = py - qy;
      return dx * dx + dy * dy;
    }

    function classifyDerivedWaypointReasons(path, fieldPolygonLonLat) {
      const pts = Array.isArray(path) ? path : [];
      const field = Array.isArray(fieldPolygonLonLat) ? fieldPolygonLonLat : [];
      if (pts.length < 3 || field.length < 3) return [];
      const polyXY = field.map((p) => ol.proj.fromLonLat([p[0], p[1]]));
      const items = [];
      const edgeDistanceM = (xy) => {
        let best = Number.POSITIVE_INFINITY;
        for (let i = 0; i < polyXY.length; i++) {
          const a = polyXY[i];
          const b = polyXY[(i + 1) % polyXY.length];
          const d2 = pointToSegmentDistSq(xy[0], xy[1], a[0], a[1], b[0], b[1]);
          if (d2 < best) best = d2;
        }
        return Math.sqrt(best);
      };

      for (let i = 1; i + 1 < pts.length; i++) {
        const pPrev = ol.proj.fromLonLat([pts[i - 1][0], pts[i - 1][1]]);
        const p = ol.proj.fromLonLat([pts[i][0], pts[i][1]]);
        const pNext = ol.proj.fromLonLat([pts[i + 1][0], pts[i + 1][1]]);
        const v1x = p[0] - pPrev[0], v1y = p[1] - pPrev[1];
        const v2x = pNext[0] - p[0], v2y = pNext[1] - p[1];
        const n1 = Math.hypot(v1x, v1y), n2 = Math.hypot(v2x, v2y);
        if (n1 < 1e-9 || n2 < 1e-9) continue;
        const dot = Math.max(-1, Math.min(1, (v1x * v2x + v1y * v2y) / (n1 * n2)));
        const ang = Math.acos(dot) * 180 / Math.PI;
        const dEdge = edgeDistanceM(p);

        let tag = '';
        let reason = '';
        if (dEdge <= 2.0 && ang >= 8.0) {
          tag = 'field_boundary_guard';
          reason = 'Punkt zur Verhinderung von Flug ausserhalb Feldgrenze.';
        } else if (ang <= 7.0) {
          tag = 'height_rule_support';
          reason = 'Punkt als Stuetzpunkt fuer Hoehenregel/AGL-Fuehrung.';
        } else {
          continue;
        }
        items.push({
          lon: Number(pts[i][0]),
          lat: Number(pts[i][1]),
          tag,
          reason,
        });
      }
      return items;
    }

    function derivedWaypointReasonFeatures(items) {
      const list = Array.isArray(items) ? items : [];
      return list.map((it, idx) => {
        const lon = Number(it && it.lon);
        const lat = Number(it && it.lat);
        if (!Number.isFinite(lon) || !Number.isFinite(lat)) return null;
        const tag = String(it.tag || '');
        const color = tag === 'field_boundary_guard'
          ? 'rgba(255,140,0,0.95)'
          : 'rgba(40,120,255,0.95)';
        const title = tag === 'field_boundary_guard'
          ? `Grenzschutz-Punkt ${idx + 1}`
          : `Hoehenregel-Punkt ${idx + 1}`;
        const feat = new ol.Feature({ geometry: new ol.geom.Point(ol.proj.fromLonLat([lon, lat])) });
        feat.set('hover_title', title);
        feat.set('hover_reason', String(it.reason || 'Abgeleiteter Regelpunkt'));
        feat.setStyle(new ol.style.Style({
          image: new ol.style.Circle({
            radius: 4.2,
            fill: new ol.style.Fill({ color }),
            stroke: new ol.style.Stroke({ color: '#ffffff', width: 1.1 }),
          }),
        }));
        return feat;
      }).filter(Boolean);
    }

    function computeCoverageRatioByCircles(fieldPolygonLonLat, waypoints, radiusM, gridN = 70) {
      const field = Array.isArray(fieldPolygonLonLat) ? fieldPolygonLonLat : [];
      const wps = Array.isArray(waypoints) ? waypoints : [];
      if (field.length < 3 || wps.length < 1 || !Number.isFinite(radiusM) || radiusM <= 0) return NaN;
      const polyXY = field.map((p) => ol.proj.fromLonLat([p[0], p[1]]));
      const xs = polyXY.map((p) => p[0]);
      const ys = polyXY.map((p) => p[1]);
      const minX = Math.min(...xs), maxX = Math.max(...xs);
      const minY = Math.min(...ys), maxY = Math.max(...ys);
      const circles = wps.map((p) => {
        const c = ol.proj.fromLonLat([p[0], p[1]]);
        return { x: c[0], y: c[1], r2: radiusM * radiusM };
      });
      let inField = 0;
      let covered = 0;
      for (let ix = 0; ix < gridN; ix++) {
        const x = minX + (ix + 0.5) * (maxX - minX) / gridN;
        for (let iy = 0; iy < gridN; iy++) {
          const y = minY + (iy + 0.5) * (maxY - minY) / gridN;
          const pt = [x, y];
          if (!pointInPolygonXY(pt, polyXY)) continue;
          inField++;
          for (let k = 0; k < circles.length; k++) {
            const dx = x - circles[k].x;
            const dy = y - circles[k].y;
            if (dx * dx + dy * dy <= circles[k].r2) {
              covered++;
              break;
            }
          }
        }
      }
      if (inField <= 0) return NaN;
      return Math.max(0, Math.min(1, covered / inField));
    }

    function computeCoverageRatioByPathWidth(fieldPolygonLonLat, waypoints, widthM, gridN = 70) {
      const field = Array.isArray(fieldPolygonLonLat) ? fieldPolygonLonLat : [];
      const wps = Array.isArray(waypoints) ? waypoints : [];
      const segs = flightPathSegments(wps);
      if (field.length < 3 || !segs.length || !Number.isFinite(widthM) || widthM <= 0) return NaN;
      const polyXY = field.map((p) => ol.proj.fromLonLat([p[0], p[1]]));
      const pathSegXY = segs.map((seg) => seg.map((p) => ol.proj.fromLonLat([p[0], p[1]])));
      const xs = polyXY.map((p) => p[0]);
      const ys = polyXY.map((p) => p[1]);
      const minX = Math.min(...xs), maxX = Math.max(...xs);
      const minY = Math.min(...ys), maxY = Math.max(...ys);
      const latVals = field.map((p) => Number(p[1])).filter((v) => Number.isFinite(v));
      const latRef = latVals.length ? (latVals.reduce((a, b) => a + b, 0) / latVals.length) : 47.0;
      const mercatorScale = 1 / Math.max(1e-6, Math.cos((latRef * Math.PI) / 180.0));
      const radiusMapM = (widthM * 0.5) * mercatorScale;
      const r2 = radiusMapM * radiusMapM;
      let inField = 0;
      let covered = 0;
      for (let ix = 0; ix < gridN; ix++) {
        const x = minX + (ix + 0.5) * (maxX - minX) / gridN;
        for (let iy = 0; iy < gridN; iy++) {
          const y = minY + (iy + 0.5) * (maxY - minY) / gridN;
          const pt = [x, y];
          if (!pointInPolygonXY(pt, polyXY)) continue;
          inField++;
          for (let k = 0; k < pathSegXY.length; k++) {
            const a = pathSegXY[k][0];
            const b = pathSegXY[k][1];
            if (pointToSegmentDistSq(x, y, a[0], a[1], b[0], b[1]) <= r2) {
              covered++;
              break;
            }
          }
        }
      }
      if (inField <= 0) return NaN;
      return Math.max(0, Math.min(1, covered / inField));
    }

    function computeCoverageStatsByPathWidth(fieldPolygonLonLat, waypoints, widthM, turnDiameterM = 0, gridN = 80, forwardLookM = 0) {
      const field = Array.isArray(fieldPolygonLonLat) ? fieldPolygonLonLat : [];
      const wps = Array.isArray(waypoints) ? waypoints : [];
      const segs = flightPathSegments(wps);
      if (field.length < 3 || !segs.length || !Number.isFinite(widthM) || widthM <= 0) {
        return { coveredRatio: NaN, doubleRatio: NaN };
      }
      const polyXY = field.map((p) => ol.proj.fromLonLat([p[0], p[1]]));
      const pathSegXY = segs.map((seg) => seg.map((p) => ol.proj.fromLonLat([p[0], p[1]])));
      const xs = polyXY.map((p) => p[0]);
      const ys = polyXY.map((p) => p[1]);
      const minX = Math.min(...xs), maxX = Math.max(...xs);
      const minY = Math.min(...ys), maxY = Math.max(...ys);
      const latVals = field.map((p) => Number(p[1])).filter((v) => Number.isFinite(v));
      const latRef = latVals.length ? (latVals.reduce((a, b) => a + b, 0) / latVals.length) : 47.0;
      const mercatorScale = 1 / Math.max(1e-6, Math.cos((latRef * Math.PI) / 180.0));
      const halfW = (widthM * 0.5) * mercatorScale;
      const halfW2 = halfW * halfW;
      const halfForward = Math.max(0, Number(forwardLookM) * 0.5 * mercatorScale);

      const segModels = pathSegXY.map((seg) => {
        const a = seg[0];
        const b = seg[1];
        const dx = b[0] - a[0];
        const dy = b[1] - a[1];
        const len = Math.hypot(dx, dy);
        const ux = len > 1e-9 ? dx / len : 0;
        const uy = len > 1e-9 ? dy / len : 0;
        const nx = -uy;
        const ny = ux;
        return { a, b, len, ux, uy, nx, ny };
      });

      const turnR = Number.isFinite(turnDiameterM) && turnDiameterM > 0 ? (turnDiameterM * 0.5 * mercatorScale) : 0;
      const turnR2 = turnR * turnR;
      const turnPts = turnR > 0 ? turningPointCenters(wps, forwardLookM) : [];

      let inField = 0;
      let covered = 0;
      let doubleCovered = 0;
      const uncoveredCells = [];
      const doubleCoveredCells = [];
      const cellW = (maxX - minX) / gridN;
      const cellH = (maxY - minY) / gridN;
      for (let ix = 0; ix < gridN; ix++) {
        const x = minX + (ix + 0.5) * (maxX - minX) / gridN;
        for (let iy = 0; iy < gridN; iy++) {
          const y = minY + (iy + 0.5) * (maxY - minY) / gridN;
          const pt = [x, y];
          if (!pointInPolygonXY(pt, polyXY)) continue;
          inField++;
          let hits = 0;
          // Moving phase: HFOV width with VFOV forward look (segment + halfForward).
          for (let k = 0; k < segModels.length; k++) {
            const s = segModels[k];
            if (s.len < 1e-9) continue;
            const vx = x - s.a[0];
            const vy = y - s.a[1];
            const tAlong = vx * s.ux + vy * s.uy;
            const dPerp = Math.abs(vx * s.nx + vy * s.ny);
            if (dPerp * dPerp <= halfW2 && tAlong >= 0 && tAlong <= s.len + halfForward) hits++;
          }
          // Start phase: static footprint rectangle at start point.
          if (halfForward > 0 && segModels.length) {
            const s0 = segModels[0];
            const vx0 = x - s0.a[0];
            const vy0 = y - s0.a[1];
            const t0 = vx0 * s0.ux + vy0 * s0.uy;
            const d0 = Math.abs(vx0 * s0.nx + vy0 * s0.ny);
            if (d0 * d0 <= halfW2 && t0 >= -halfForward && t0 <= halfForward) hits++;
          }
          // End phase: stop footprint half VFOV forward from endpoint.
          if (halfForward > 0 && segModels.length) {
            const sl = segModels[segModels.length - 1];
            const end = sl.b;
            const vx1 = x - end[0];
            const vy1 = y - end[1];
            const t1 = vx1 * sl.ux + vy1 * sl.uy;
            const d1 = Math.abs(vx1 * sl.nx + vy1 * sl.ny);
            if (d1 * d1 <= halfW2 && t1 >= 0 && t1 <= halfForward) hits++;
          }
          if (turnR > 0) {
            for (let t = 0; t < turnPts.length; t++) {
              const dx = x - turnPts[t][0];
              const dy = y - turnPts[t][1];
              if (dx * dx + dy * dy <= turnR2) hits++;
            }
          }
          if (hits >= 1) covered++;
          if (hits >= 2) {
            doubleCovered++;
            doubleCoveredCells.push({ x, y, w: cellW, h: cellH });
          }
          if (hits === 0) {
            uncoveredCells.push({ x, y, w: cellW, h: cellH });
          }
        }
      }
      if (inField <= 0) return { coveredRatio: NaN, doubleRatio: NaN, uncoveredCells: [], doubleCoveredCells: [] };
      return {
        coveredRatio: Math.max(0, Math.min(1, covered / inField)),
        doubleRatio: Math.max(0, Math.min(1, doubleCovered / inField)),
        uncoveredCells,
        doubleCoveredCells,
      };
    }

    function computeCoverageStatsNewModel(fieldPolygonLonLat, waypoints, widthM, turnDiameterM = 0, forwardLookM = 0) {
      return computeCoverageStatsByPathWidth(
        fieldPolygonLonLat,
        waypoints,
        widthM,
        turnDiameterM,
        COVERAGE_GRID_N,
        forwardLookM
      );
    }

    function uncoveredAreaFeatures(cells, color = 'rgba(255,0,0,0.16)') {
      const list = Array.isArray(cells) ? cells : [];
      return list.map((c) => {
        const x0 = c.x - c.w * 0.5;
        const x1 = c.x + c.w * 0.5;
        const y0 = c.y - c.h * 0.5;
        const y1 = c.y + c.h * 0.5;
        const feat = new ol.Feature({
          geometry: new ol.geom.Polygon([[
            [x0, y0], [x1, y0], [x1, y1], [x0, y1], [x0, y0]
          ]])
        });
        feat.setStyle(new ol.style.Style({
          stroke: new ol.style.Stroke({ color: 'rgba(255,0,0,0.12)', width: 0.4 }),
          fill: new ol.style.Fill({ color }),
        }));
        return feat;
      });
    }

    function doubleCoveredAreaFeatures(cells, color = 'rgba(20,90,190,0.20)') {
      const list = Array.isArray(cells) ? cells : [];
      return list.map((c) => {
        const x0 = c.x - c.w * 0.5;
        const x1 = c.x + c.w * 0.5;
        const y0 = c.y - c.h * 0.5;
        const y1 = c.y + c.h * 0.5;
        const feat = new ol.Feature({
          geometry: new ol.geom.Polygon([[
            [x0, y0], [x1, y0], [x1, y1], [x0, y1], [x0, y0]
          ]])
        });
        feat.setStyle(new ol.style.Style({
          stroke: new ol.style.Stroke({ color: 'rgba(20,90,190,0.10)', width: 0.3 }),
          fill: new ol.style.Fill({ color }),
        }));
        return feat;
      });
    }

    function correctionWaypointFeatures(points) {
      const list = Array.isArray(points) ? points : [];
      return list.map((p, idx) => {
        const lon = Number(p && p.lon);
        const lat = Number(p && p.lat);
        if (!Number.isFinite(lon) || !Number.isFinite(lat)) return null;
        const reason = String(p.reason || 'Korrekturpunkt fuer Abdeckungsloch');
        const feat = new ol.Feature({ geometry: new ol.geom.Point(ol.proj.fromLonLat([lon, lat])) });
        feat.set('hover_reason', reason);
        feat.set('hover_title', `Korrekturpunkt ${idx + 1}`);
        feat.setStyle(new ol.style.Style({
          image: new ol.style.Circle({
            radius: 5,
            fill: new ol.style.Fill({ color: 'rgba(210,30,30,0.92)' }),
            stroke: new ol.style.Stroke({ color: '#ffffff', width: 1.4 }),
          }),
        }));
        return feat;
      }).filter(Boolean);
    }

    function applyCoverageCorrections(fieldPolygonLonLat, basePath, widthM, turnDiameterM, forwardLookM = 0, edgeAllowanceM = NaN) {
      const field = Array.isArray(fieldPolygonLonLat) ? fieldPolygonLonLat : [];
      let path = Array.isArray(basePath) ? basePath.map((p) => [Number(p[0]), Number(p[1]), Number(p[2])]) : [];
      if (field.length < 3 || path.length < 2 || !Number.isFinite(widthM) || widthM <= 0) {
        return {
          path,
          corrections: [],
          coverage: computeCoverageStatsNewModel(field, path, widthM, turnDiameterM, forwardLookM),
        };
      }

      const polyXY = field.map((p) => ol.proj.fromLonLat([p[0], p[1]]));
      const latVals = field.map((p) => Number(p[1])).filter((v) => Number.isFinite(v));
      const latRef = latVals.length ? (latVals.reduce((a, b) => a + b, 0) / latVals.length) : 47.0;
      const mercatorScale = 1 / Math.max(1e-6, Math.cos((latRef * Math.PI) / 180.0));
      const minSpacing = Math.max(0.8, widthM * 0.35 * mercatorScale);
      const minSpacingSq = minSpacing * minSpacing;
      const edgeAllowanceMap = (Number.isFinite(edgeAllowanceM) && edgeAllowanceM > 0) ? edgeAllowanceM * mercatorScale : NaN;
      const corrections = [];
      let stats = computeCoverageStatsNewModel(field, path, widthM, turnDiameterM, forwardLookM);
      const edgeDistanceMap = (xy) => {
        let best = Number.POSITIVE_INFINITY;
        for (let i = 0; i < polyXY.length; i++) {
          const a = polyXY[i];
          const b = polyXY[(i + 1) % polyXY.length];
          const d2 = pointToSegmentDistSq(xy[0], xy[1], a[0], a[1], b[0], b[1]);
          if (d2 < best) best = d2;
        }
        return Math.sqrt(best);
      };
      const insertionTurnAngleDeg = (pathXY, segIdx, candXY) => {
        const prev = pathXY[segIdx];
        const next = pathXY[segIdx + 1];
        const v1x = candXY[0] - prev[0];
        const v1y = candXY[1] - prev[1];
        const v2x = next[0] - candXY[0];
        const v2y = next[1] - candXY[1];
        const n1 = Math.hypot(v1x, v1y);
        const n2 = Math.hypot(v2x, v2y);
        if (n1 < 1e-9 || n2 < 1e-9) return 180.0;
        const dot = Math.max(-1, Math.min(1, (v1x * v2x + v1y * v2y) / (n1 * n2)));
        return Math.acos(dot) * 180.0 / Math.PI;
      };

      const maxCorrections = 10;
      for (let step = 0; step < maxCorrections; step++) {
        const cells = Array.isArray(stats.uncoveredCells) ? stats.uncoveredCells : [];
        if (!cells.length) break;

        const pathXY = path.map((p) => ol.proj.fromLonLat([p[0], p[1]]));
        let candidate = null;
        let candidateEdgeDist = NaN;
        let candidateScore = Number.POSITIVE_INFINITY;
        for (let ci = 0; ci < cells.length; ci++) {
          const c = cells[ci];
          if (!pointInPolygonXY([c.x, c.y], polyXY)) continue;
          let nearExisting = false;
          let minD2Path = Number.POSITIVE_INFINITY;
          for (let k = 0; k < pathXY.length; k++) {
            const dx = c.x - pathXY[k][0];
            const dy = c.y - pathXY[k][1];
            const d2 = dx * dx + dy * dy;
            if (d2 < minD2Path) minD2Path = d2;
            if (d2 <= minSpacingSq) {
              nearExisting = true;
              break;
            }
          }
          if (nearExisting) continue;
          const eDist = edgeDistanceMap([c.x, c.y]);
          const edgePriority = Number.isFinite(edgeAllowanceMap) && eDist <= edgeAllowanceMap * 1.25 ? -0.25 : 0.0;
          const score = minD2Path + edgePriority * (widthM * mercatorScale) * (widthM * mercatorScale);
          if (score < candidateScore) {
            candidate = c;
            candidateEdgeDist = eDist;
            candidateScore = score;
          }
        }
        if (!candidate) break;

        let best = { segIdx: 0, score: Number.POSITIVE_INFINITY, t: 0, turnDeg: 180.0 };
        for (let i = 0; i + 1 < pathXY.length; i++) {
          const a = pathXY[i];
          const b = pathXY[i + 1];
          const abx = b[0] - a[0];
          const aby = b[1] - a[1];
          const ab2 = abx * abx + aby * aby;
          let t = 0;
          if (ab2 > 1e-12) {
            t = ((candidate.x - a[0]) * abx + (candidate.y - a[1]) * aby) / ab2;
            t = Math.max(0, Math.min(1, t));
          }
          const qx = a[0] + t * abx;
          const qy = a[1] + t * aby;
          const dx = candidate.x - qx;
          const dy = candidate.y - qy;
          const d2 = dx * dx + dy * dy;
          const turnDeg = insertionTurnAngleDeg(pathXY, i, [candidate.x, candidate.y]);
          const rotationPenalty = turnDeg * turnDeg * 0.08;
          const score = d2 + rotationPenalty;
          if (score < best.score) best = { segIdx: i, score, t, turnDeg };
        }

        const lonLat = ol.proj.toLonLat([candidate.x, candidate.y]);
        const pA = path[best.segIdx];
        const pB = path[best.segIdx + 1];
        const altA = Number(pA && pA[2]);
        const altB = Number(pB && pB[2]);
        const alt = (Number.isFinite(altA) && Number.isFinite(altB))
          ? (altA + (altB - altA) * best.t)
          : (Number.isFinite(altA) ? altA : (Number.isFinite(altB) ? altB : 0));

        const edgeAware = Number.isFinite(edgeAllowanceMap) && Number.isFinite(candidateEdgeDist) && candidateEdgeDist <= edgeAllowanceMap * 1.25;
        const lowRotation = best.turnDeg <= 35.0;
        const reason = edgeAware
          ? `Korrektur: Randabstand beruecksichtigt (max ${Number(edgeAllowanceM).toFixed(1)}m), Restflaeche am Rand geschlossen.`
          : (lowRotation
          ? 'Korrektur: zusaetzlicher Punkt mit moeglichst geringer Rotation zur Schliessung der Restflaeche.'
          : ((best.segIdx <= 1 || best.segIdx >= Math.max(1, path.length - 3))
          ? 'Korrektur: bestehende Bahn lokal verlaengert, um Restflaeche abzudecken.'
          : 'Korrektur: zusaetzlicher Eckpunkt fuer kleine nicht abgedeckte Ecke.'));

        const insertPoint = [Number(lonLat[0]), Number(lonLat[1]), Number(alt)];
        path.splice(best.segIdx + 1, 0, insertPoint);
        corrections.push({ lon: insertPoint[0], lat: insertPoint[1], reason });
        stats = computeCoverageStatsNewModel(field, path, widthM, turnDiameterM, forwardLookM);
      }

      return { path, corrections, coverage: stats };
    }

    async function fetchGeoAdminTerrainHeight(lon, lat) {
      const key = `${Number(lon).toFixed(7)},${Number(lat).toFixed(7)}`;
      if (terrainHeightCache.has(key)) {
        return terrainHeightCache.get(key);
      }
      const reframeUrl = `https://geodesy.geo.admin.ch/reframe/wgs84tolv95?easting=${encodeURIComponent(Number(lon).toFixed(7))}&northing=${encodeURIComponent(Number(lat).toFixed(7))}&format=json`;
      const reframeResponse = await fetch(reframeUrl, { cache: "no-store" });
      if (!reframeResponse.ok) throw new Error(`REFRAME ${reframeResponse.status}`);
      const reframePayload = await reframeResponse.json();
      const easting = Number(reframePayload.easting);
      const northing = Number(reframePayload.northing);
      if (!Number.isFinite(easting) || !Number.isFinite(northing)) throw new Error("Invalid LV95 from REFRAME");

      const heightUrl = `https://api3.geo.admin.ch/rest/services/height?easting=${encodeURIComponent(easting.toFixed(3))}&northing=${encodeURIComponent(northing.toFixed(3))}&sr=2056`;
      const heightResponse = await fetch(heightUrl, { cache: "no-store" });
      if (!heightResponse.ok) throw new Error(`HEIGHT ${heightResponse.status}`);
      const heightPayload = await heightResponse.json();
      const terrain = Number(heightPayload.height);
      const result = Number.isFinite(terrain) ? terrain : NaN;
      terrainHeightCache.set(key, result);
      return result;
    }

    async function computeControlPointRelativeAgl(controlPoints, startLonLat) {
      const points = Array.isArray(controlPoints) ? controlPoints : [];
      if (!points.length || !Array.isArray(startLonLat) || startLonLat.length < 2) {
        return points.map(() => NaN);
      }

      let startTerrain = NaN;
      try {
        startTerrain = await fetchGeoAdminTerrainHeight(startLonLat[0], startLonLat[1]);
      } catch (_) {
        startTerrain = NaN;
      }
      if (!Number.isFinite(startTerrain)) {
        return points.map(() => NaN);
      }

      const out = new Array(points.length).fill(NaN);
      const chunkSize = 20;
      for (let i = 0; i < points.length; i += chunkSize) {
        const chunk = points.slice(i, i + chunkSize);
        const vals = await Promise.all(
          chunk.map(async (p) => {
            try {
              return await fetchGeoAdminTerrainHeight(p[0], p[1]);
            } catch (_) {
              return NaN;
            }
          })
        );
        vals.forEach((terrain, j) => {
          const idx = i + j;
          out[idx] = Number.isFinite(terrain) ? (terrain - startTerrain) : NaN;
        });
      }
      return out;
    }

    function polyFeature(poly) {
      const coords = poly.map(p => ol.proj.fromLonLat([p[0], p[1]]));
      if (coords.length > 2) {
        const first = coords[0];
        const last = coords[coords.length - 1];
        if (first[0] !== last[0] || first[1] !== last[1]) {
          coords.push([first[0], first[1]]);
        }
      }
      const feat = new ol.Feature({ geometry: new ol.geom.Polygon([coords]) });
      feat.setStyle(new ol.style.Style({
        stroke: new ol.style.Stroke({ color: 'rgba(185,35,35,0.95)', width: 2.2 }),
        fill: new ol.style.Fill({ color: 'rgba(185,35,35,0.14)' }),
      }));
      return feat;
    }

    function formSectionFeature(poly) {
      const coords = (poly || []).map(p => ol.proj.fromLonLat([p[0], p[1]]));
      if (coords.length < 3) return null;
      const first = coords[0];
      const last = coords[coords.length - 1];
      if (first[0] !== last[0] || first[1] !== last[1]) {
        coords.push([first[0], first[1]]);
      }
      const feat = new ol.Feature({ geometry: new ol.geom.Polygon([coords]) });
      feat.setStyle(new ol.style.Style({
        stroke: new ol.style.Stroke({ color: 'rgba(70,170,255,0.95)', width: 2 }),
        fill: new ol.style.Fill({ color: 'rgba(70,170,255,0.25)' }),
      }));
      return feat;
    }

    function sectionDirectionFeature(section) {
      const poly = Array.isArray(section && section.polygon) ? section.polygon : [];
      const heading = Number(section && section.heading_deg);
      if (poly.length < 3 || !Number.isFinite(heading)) return null;
      const pts = poly.slice(0, poly.length - 1).filter((p) => Array.isArray(p) && p.length >= 2);
      if (pts.length < 3) return null;
      const lonVals = pts.map((p) => Number(p[0])).filter(Number.isFinite);
      const latVals = pts.map((p) => Number(p[1])).filter(Number.isFinite);
      if (lonVals.length < 3 || latVals.length < 3) return null;
      const lon0 = lonVals.reduce((a, b) => a + b, 0) / lonVals.length;
      const lat0 = latVals.reduce((a, b) => a + b, 0) / latVals.length;
      const toM = (lon, lat) => {
        const mPerDegLat = 111320.0;
        const mPerDegLon = 111320.0 * Math.cos((lat0 * Math.PI) / 180.0);
        return [(lon - lon0) * mPerDegLon, (lat - lat0) * mPerDegLat];
      };
      const xy = pts.map((p) => toM(Number(p[0]), Number(p[1])));
      const minX = Math.min(...xy.map((p) => p[0]));
      const maxX = Math.max(...xy.map((p) => p[0]));
      const minY = Math.min(...xy.map((p) => p[1]));
      const maxY = Math.max(...xy.map((p) => p[1]));
      const diag = Math.hypot(maxX - minX, maxY - minY);
      const halfLen = Math.max(8.0, diag * 0.22);
      const ang = (heading * Math.PI) / 180.0;
      const dx = Math.cos(ang) * halfLen;
      const dy = Math.sin(ang) * halfLen;
      const mPerDegLat = 111320.0;
      const mPerDegLon = 111320.0 * Math.cos((lat0 * Math.PI) / 180.0);
      const a = [lon0 - dx / mPerDegLon, lat0 - dy / mPerDegLat];
      const b = [lon0 + dx / mPerDegLon, lat0 + dy / mPerDegLat];
      const feat = new ol.Feature({
        geometry: new ol.geom.LineString([ol.proj.fromLonLat(a), ol.proj.fromLonLat(b)])
      });
      feat.setStyle(new ol.style.Style({
        stroke: new ol.style.Stroke({
          color: 'rgba(24,92,173,0.95)',
          width: 3.5,
          lineDash: [9, 7],
        }),
      }));
      return feat;
    }

    function measurementGuideFeatures(fieldPolygon, widthW, spacingS) {
      const poly = Array.isArray(fieldPolygon) ? fieldPolygon : [];
      if (poly.length < 3) return [];
      const w = Number(widthW);
      const s = Number(spacingS);
      if (!Number.isFinite(w) || !Number.isFinite(s) || w <= 0 || s <= 0) return [];
      const polyXY = poly.map((p) => ol.proj.fromLonLat([p[0], p[1]]));
      const xs = polyXY.map((p) => p[0]);
      const ys = polyXY.map((p) => p[1]);
      const minX = Math.min(...xs), maxX = Math.max(...xs);
      const minY = Math.min(...ys), maxY = Math.max(...ys);
      const spanX = maxX - minX;
      const spanY = maxY - minY;
      if (spanX <= 0 || spanY <= 0) return [];
      const latVals = poly.map((p) => Number(p[1])).filter((v) => Number.isFinite(v));
      const latRef = latVals.length ? (latVals.reduce((a, b) => a + b, 0) / latVals.length) : 47.0;
      const mercatorScale = 1 / Math.max(1e-6, Math.cos((latRef * Math.PI) / 180.0));

      // Place W/S guides clearly outside the field (below lower boundary).
      const x0 = minX + spanX * 0.08;
      const yBase = minY - spanY * 0.10;
      const yTop = yBase - spanY * 0.06;

      const mkMeasure = (len, y, label, color) => {
        const lenMap = len * mercatorScale;
        const x1 = x0 + lenMap;
        const line = new ol.Feature({ geometry: new ol.geom.LineString([[x0, y], [x1, y]]) });
        line.setStyle(new ol.style.Style({
          stroke: new ol.style.Stroke({ color, width: 3 }),
          text: new ol.style.Text({
            text: label,
            font: '12px Arial',
            offsetY: -10,
            fill: new ol.style.Fill({ color: '#6b7280' }),
            stroke: new ol.style.Stroke({ color: '#ffffff', width: 3 }),
          }),
        }));
        const tickA = new ol.Feature({ geometry: new ol.geom.LineString([[x0, y - 6], [x0, y + 6]]) });
        const tickB = new ol.Feature({ geometry: new ol.geom.LineString([[x1, y - 6], [x1, y + 6]]) });
        const tickStyle = new ol.style.Style({ stroke: new ol.style.Stroke({ color, width: 2 }) });
        tickA.setStyle(tickStyle);
        tickB.setStyle(tickStyle);
        return [line, tickA, tickB];
      };

      return [
        ...mkMeasure(s, yBase, `S ${s.toFixed(1)}m`, 'rgba(0,150,70,0.95)'),
        ...mkMeasure(w, yTop, `W ${w.toFixed(1)}m`, 'rgba(0,90,190,0.95)'),
      ];
    }

    function metricLine(label, value, cls='') {
      return `<div class="kpi ${cls}"><strong>${label}:</strong> ${value}</div>`;
    }

    function collapsibleSection(title, contentHtml, stateKey, defaultOpen = false) {
      const isOpen = Object.prototype.hasOwnProperty.call(collapseState, stateKey)
        ? !!collapseState[stateKey]
        : defaultOpen;
      return `
        <details class="step-details" data-collapse-key="${stateKey}" ${isOpen ? 'open' : ''}>
          <summary>${title}</summary>
          <div class="step-content">${contentHtml}</div>
        </details>
      `;
    }

    function updateMapLegend(values) {
      const finite = (values || []).map((v) => Number(v)).filter((v) => Number.isFinite(v));
      if (!finite.length) {
        if (mapLegendEl) mapLegendEl.style.display = 'none';
        legendMinEl.textContent = '-';
        legendMidEl.textContent = '-';
        legendMaxEl.textContent = '-';
        return;
      }
      if (mapLegendEl) mapLegendEl.style.display = '';
      const minV = Math.min(...finite);
      const maxV = Math.max(...finite);
      const midV = (minV + maxV) / 2;
      const fmt = (v) => `${Math.abs(v).toFixed(1)} m`;
      legendMinEl.textContent = fmt(minV);
      legendMidEl.textContent = fmt(midV);
      legendMaxEl.textContent = fmt(maxV);
    }

    function renderEffectiveParams(data) {
      const ep = data.effective_params || {};
      const src = data.effective_param_sources || {};
      const rp = data.requested_params || {};
      const sourceText = (k) => String(src[k] || 'default');
      const deltaLine = (label, key, unit = '') => {
        const req = Number(rp[key]);
        const eff = Number(ep[key]);
        const reqTxt = Number.isFinite(req) ? req.toFixed(3).replace(/\.?0+$/, '') : '-';
        const effTxt = Number.isFinite(eff) ? eff.toFixed(3).replace(/\.?0+$/, '') : '-';
        const changed = (Number.isFinite(req) && Number.isFinite(eff) && Math.abs(req - eff) > 1e-9);
        return metricLine(`${label} Impact`, `${reqTxt}${unit} -> ${effTxt}${unit} (Quelle: ${sourceText(key)})`, changed ? 'good' : '');
      };
      impactBoxEl.innerHTML =
        collapsibleSection(
          'Impact',
          deltaLine('HFOV', 'hfov', '°') +
          deltaLine('Overlap', 'overlap', '') +
          deltaLine('Randfaktor', 'edge_factor', '') +
          deltaLine('Speed', 'speed', ' m/s'),
          'impact',
          false
        );

      effectiveParamsBoxEl.innerHTML =
        collapsibleSection(
          'Effektive Parameter',
          metricLine('Effektiv HFOV', `${Number(ep.hfov || 0).toFixed(2)}° (Quelle: ${sourceText('hfov')})`) +
          metricLine('Effektiv Overlap', `${Number(ep.overlap || 0).toFixed(3)} (Quelle: ${sourceText('overlap')})`) +
          metricLine('Effektiv Randfaktor', `${Number(ep.edge_factor || 0).toFixed(3)} (Quelle: ${sourceText('edge_factor')})`) +
          metricLine('Effektiv Speed', `${Number(ep.speed || 0).toFixed(2)} m/s (Quelle: ${sourceText('speed')})`) +
          metricLine('Effektiv Profilschritt', `${Number(ep.target_step_m || 0).toFixed(1)} m (Quelle: ${sourceText('target_step_m')})`) +
          metricLine('Effektiv AGL-Toleranz', `${Number(ep.agl_tolerance_m || 0).toFixed(1)} m (Quelle: ${sourceText('agl_tolerance_m')})`),
          'effectiveParams',
          false
        );
    }

    function renderFieldDecision(data) {
      const a = data.field_assessment || {};
      const cls = String(a.classification || '-');
      const dec = String(a.decision || '-');
      const sec = Number(a.sections_count || 0);
      const cov = Number(a.primary_coverage_ratio || 0);
      const covRaw = Number(a.primary_coverage_ratio_raw || cov);
      const covInvalid = !!a.invalid_primary_coverage;
      const elong = Number(a.elongation_ratio || 0);
      const ddeg = Number(a.max_section_direction_delta_deg || 0);
      const thr = Number(a.section_split_threshold_deg || 20);
      const whyForm = String(a.reason_form || '-');
      const whyDir = String(a.reason_direction || '-');
      const txt = String(a.summary || 'Keine Bewertung verfügbar.');
      const okSingle = dec === 'einzelnes_gedrehtes_hauptmuster';
      decisionBoxEl.innerHTML = collapsibleSection(
        'Resultatbox: Feldbewertung',
        metricLine('Klassifikation', cls) +
        metricLine('Entscheid', dec, okSingle ? 'good' : 'bad') +
        metricLine('Sektionen', String(sec)) +
        metricLine('Abdeckung Hauptmuster', `${(cov * 100).toFixed(1)} % (raw ${(covRaw * 100).toFixed(1)} %)`, covInvalid ? 'bad' : '') +
        metricLine('Laengen/Breiten-Faktor', `${elong.toFixed(2)}`) +
        metricLine('Max Richtungsdifferenz', `${ddeg.toFixed(1)}° (Schwelle ${thr.toFixed(1)}°)`) +
        metricLine('Warum Form', whyForm) +
        metricLine('Warum Richtung', whyDir) +
        metricLine('Begruendung', txt),
        'fieldDecision',
        true
      );
    }

    function renderSpecialCasesReport(data) {
      const items = Array.isArray(data && data.special_cases_report) ? data.special_cases_report : [];
      if (!items.length) {
        specialCasesBoxEl.innerHTML = collapsibleSection(
          'Spezialfaelle Report',
          metricLine('Status', 'Keine Spezialfaelle aktiv'),
          'specialCases',
          false
        );
        return;
      }
      const html = items.map((it, idx) =>
        metricLine(
          `${idx + 1}. ${String(it.title || 'Spezialfall')}`,
          `${String(it.field_id || '-')} - ${String(it.detail || '-')}`
        )
      ).join('');
      specialCasesBoxEl.innerHTML = collapsibleSection(
        'Spezialfaelle Report',
        html,
        'specialCases',
        false
      );
    }

    function renderStepResults(data) {
      const steps = data.step_results || [];
      if (!steps.length) {
        stepResultsBoxEl.innerHTML = collapsibleSection('Schritt-für-Schritt Resultate', metricLine('Status', 'Keine Daten'), 'stepResults', false);
        return;
      }
      const html = steps.map((s, idx) => {
        const inRows = Object.entries(s.inputs || {}).map(([k, v]) => `<div><strong>${k}:</strong> ${v}</div>`).join('');
        const outRows = Object.entries(s.result || {}).map(([k, v]) => `<div><strong>${k}:</strong> ${v}</div>`).join('');
        return `
          <details class="step-details" ${idx === 0 ? 'open' : ''}>
            <summary>${s.id} - ${s.title} ${s.active ? '(aktiv)' : '(inaktiv)'}</summary>
            <div class="step-content">
              <div><strong>Inputs</strong></div>
              ${inRows || '<div>-</div>'}
              <div style="margin-top:6px;"><strong>Resultat</strong></div>
              ${outRows || '<div>-</div>'}
            </div>
          </details>
        `;
      }).join('');
      stepResultsBoxEl.innerHTML = collapsibleSection('Schritt-für-Schritt Resultate', html, 'stepResults', false);
    }

    function renderExecutionModel(data) {
      const ex = data.execution_model || {};
      const steps = ex.steps || [];
      const reqs = ex.global_requirements || [];
      const stepsHtml = steps.map((s) => {
        const reqList = (s.requirements || []).join(', ') || '-';
        return `<div class="kpi"><strong>${s.id}</strong> (${s.active ? 'aktiv' : 'inaktiv'})<br><span class="small">Requirements: ${reqList}</span></div>`;
      }).join('');
      const unmapped = reqs.filter((r) => r.status && r.status !== 'mapped');
      const reqSummary = metricLine(
        'Global Requirements Mapping',
        `${reqs.length} erfasst, ${unmapped.length} nicht zugeordnet`,
        unmapped.length ? 'bad' : 'good'
      );
      executionModelBoxEl.innerHTML = collapsibleSection('Ausführbares Modell', `${reqSummary}${stepsHtml}`, 'executionModel', false);
    }

    async function loadStatus() {
      try {
        const resp = await fetch('api_status.php', { cache: 'no-store' });
        const data = await resp.json();
        statusBoxEl.innerHTML = collapsibleSection(
          'Systemstatus',
          metricLine('Systemstatus XML', data.xml_exists ? 'OK' : 'Fehlt', data.xml_exists ? 'good' : 'bad') +
          metricLine('Anzahl Felder', String(data.field_count || 0)) +
          metricLine('Python verfügbar', data.python_ok ? (data.python_version || 'Ja') : 'Nein', data.python_ok ? 'good' : 'bad'),
          'status',
          false
        );
      } catch (e) {
        statusBoxEl.innerHTML = collapsibleSection('Systemstatus', metricLine('Fehler', 'Fehler beim Laden', 'bad'), 'status', false);
      }
    }

    async function loadModels() {
      const resp = await fetch('api_models.php', { cache: 'no-store' });
      const data = await resp.json();
      modelSelectEl.innerHTML = '';
      modelMap = {};
      (data.models || []).forEach((m) => {
        modelMap[m.id] = m;
        const opt = document.createElement('option');
        opt.value = m.id;
        opt.textContent = `${m.name} (${m.id})`;
        modelSelectEl.appendChild(opt);
      });
      if (!modelSelectEl.value && modelMap.default) {
        modelSelectEl.value = 'default';
      }
      if (pendingUiSettings && pendingUiSettings.modelId && modelMap[pendingUiSettings.modelId]) {
        modelSelectEl.value = pendingUiSettings.modelId;
      }
    }

    async function loadFields() {
      const resp = await fetch('api_fields.php', { cache: 'no-store' });
      const data = await resp.json();
      fieldSelect.innerHTML = '';
      fieldNameList.innerHTML = '';
      fieldsById = {};
      fieldSearchEntries = [];
      const savedDefault = localStorage.getItem(DEFAULT_FIELD_KEY) || '';
      const sortedFields = [...(data.fields || [])].sort((a, b) => {
        const ka = `${String(a.feld_id || '').toLowerCase()} ${String(a.name || '').toLowerCase()}`;
        const kb = `${String(b.feld_id || '').toLowerCase()} ${String(b.name || '').toLowerCase()}`;
        return ka.localeCompare(kb, 'de');
      });
      sortedFields.forEach(f => {
        fieldsById[f.id] = f;
        const opt = document.createElement('option');
        opt.value = f.id;
        fieldSelect.appendChild(opt);

        const label = `${f.feld_id} - ${f.name}`;
        const d = document.createElement('option');
        d.value = label;
        fieldNameList.appendChild(d);
        fieldSearchEntries.push({ id: f.id, label, feldId: String(f.feld_id || ''), name: String(f.name || '') });
      });
      if (savedDefault && fieldsById[savedDefault]) {
        fieldSelect.value = savedDefault;
      }
      if (pendingUiSettings && pendingUiSettings.fieldId && fieldsById[pendingUiSettings.fieldId]) {
        fieldSelect.value = pendingUiSettings.fieldId;
      }
      refreshFieldOptionLabels();
      syncFieldNameInput();
      renderQuickSlots();
    }

    function saveUiSettings() {
      const payload = {
        fieldId: fieldSelect.value || '',
        planner: plannerModeEl.value || 'php',
        modelId: modelSelectEl.value || 'default',
        baseMapMode: baseMapModeEl.value || 'swisstopo',
        showControlPoints: !!showControlPointsEl.checked,
        showManualPath: !!showManualPathEl.checked,
        showAlgorithmPath: !!showAlgorithmPathEl.checked,
        showBasePatterns: !!showBasePatternsEl.checked,
        showSectionDirections: !!showSectionDirectionsEl.checked,
        showCoverageCircles: !!showCoverageCirclesEl.checked,
        showCoverageOnManualPath: !!showCoverageOnManualPathEl.checked,
        showUncoveredAreas: !!showUncoveredAreasEl.checked,
        showFieldControlPoints: !!showFieldControlPointsEl.checked,
        showCorrectionPoints: !!showCorrectionPointsEl.checked,
        useFieldReferenceAgl: !!useFieldReferenceAglEl.checked,
        showDabs: !!(document.getElementById('showDabsLayer') && document.getElementById('showDabsLayer').checked),
        analysisBlocks: Object.keys(analysisBlockToggles).reduce((acc, k) => {
          const cfg = analysisBlockToggles[k];
          acc[k] = !!(cfg && cfg.input && cfg.input.checked);
          return acc;
        }, {}),
        collapseState: collapseState,
        quickFieldSlots: quickFieldSlots,
        floatingBoxes: floatingBoxesState,
        contextHelpVisibleV2: !!(document.querySelector('[data-rkrk-box-for="context"]') && document.querySelector('[data-rkrk-box-for="context"]').checked),
      };
      localStorage.setItem(UI_SETTINGS_KEY, JSON.stringify(payload));
    }

    function loadUiSettings() {
      try {
        const raw = localStorage.getItem(UI_SETTINGS_KEY);
        if (!raw) return;
        const cfg = JSON.parse(raw);
        if (!cfg || typeof cfg !== 'object') return;
        pendingUiSettings = cfg;
        if (cfg.planner === 'php' || cfg.planner === 'python') {
          plannerModeEl.value = cfg.planner;
        }
        if (cfg.baseMapMode === 'osm' || cfg.baseMapMode === 'swisstopo' || cfg.baseMapMode === 'swissimage') {
          baseMapModeEl.value = cfg.baseMapMode;
        }
        if (typeof cfg.showControlPoints === 'boolean') {
          showControlPointsEl.checked = cfg.showControlPoints;
        }
        if (cfg.analysisBlocks && typeof cfg.analysisBlocks === 'object') {
          Object.keys(analysisBlockToggles).forEach((k) => {
            if (typeof cfg.analysisBlocks[k] === 'boolean') {
              analysisBlockToggles[k].input.checked = cfg.analysisBlocks[k];
            }
          });
        } else if (typeof cfg.hideAnalysisBlocks === 'boolean' && cfg.hideAnalysisBlocks) {
          Object.keys(analysisBlockToggles).forEach((k) => {
            analysisBlockToggles[k].input.checked = false;
          });
        }
        if (typeof cfg.showManualPath === 'boolean') {
          showManualPathEl.checked = cfg.showManualPath;
        }
        if (typeof cfg.showAlgorithmPath === 'boolean') {
          showAlgorithmPathEl.checked = cfg.showAlgorithmPath;
        }
        if (typeof cfg.showBasePatterns === 'boolean') {
          showBasePatternsEl.checked = cfg.showBasePatterns;
        }
        if (typeof cfg.showSectionDirections === 'boolean') {
          showSectionDirectionsEl.checked = cfg.showSectionDirections;
        }
        if (typeof cfg.showCoverageCircles === 'boolean') {
          showCoverageCirclesEl.checked = cfg.showCoverageCircles;
        }
        if (typeof cfg.showCoverageOnManualPath === 'boolean') {
          showCoverageOnManualPathEl.checked = cfg.showCoverageOnManualPath;
        }
        if (typeof cfg.showUncoveredAreas === 'boolean') {
          showUncoveredAreasEl.checked = cfg.showUncoveredAreas;
        }
        if (typeof cfg.showFieldControlPoints === 'boolean') {
          showFieldControlPointsEl.checked = cfg.showFieldControlPoints;
        }
        if (typeof cfg.showCorrectionPoints === 'boolean') {
          showCorrectionPointsEl.checked = cfg.showCorrectionPoints;
        }
        if (typeof cfg.useFieldReferenceAgl === 'boolean') {
          useFieldReferenceAglEl.checked = cfg.useFieldReferenceAgl;
        }
        if (typeof cfg.showDabs === 'boolean') {
          const dabsBox = document.getElementById('showDabsLayer');
          if (dabsBox) dabsBox.checked = cfg.showDabs;
        }
        syncAnalyseDabs();
        if (cfg.collapseState && typeof cfg.collapseState === 'object') {
          collapseState = cfg.collapseState;
        }
        if (Array.isArray(cfg.quickFieldSlots)) {
          quickFieldSlots = new Array(QUICK_SLOT_COUNT).fill('').map((_, i) => String(cfg.quickFieldSlots[i] || ''));
        }
        if (cfg.floatingBoxes && typeof cfg.floatingBoxes === 'object') {
          floatingBoxesState = cfg.floatingBoxes;
        }
        const contextHelpCb = document.querySelector('[data-rkrk-box-for="context"]');
        if (contextHelpCb) {
          contextHelpCb.checked = typeof cfg.contextHelpVisibleV2 === 'boolean' ? cfg.contextHelpVisibleV2 : false;
        }
      } catch (_) {
        pendingUiSettings = null;
      }
    }

    function refreshFieldOptionLabels() {
      const savedDefault = localStorage.getItem(DEFAULT_FIELD_KEY) || '';
      Array.from(fieldSelect.options).forEach((opt) => {
        const f = fieldsById[opt.value];
        if (!f) return;
        const base = `${f.feld_id} - ${f.name}`;
        opt.textContent = opt.value === savedDefault ? `${base} [Default]` : base;
      });
    }

    function syncFieldNameInput() {
      const f = fieldsById[fieldSelect.value];
      fieldNameInput.value = f ? `${f.feld_id} - ${f.name}` : '';
    }

    function applyAnalysisBlocksVisibility() {
      Object.keys(analysisBlockToggles).forEach((k) => {
        const cfg = analysisBlockToggles[k];
        if (!cfg || !cfg.el || !cfg.input) return;
        cfg.el.style.display = cfg.input.checked ? '' : 'none';
      });
    }

    function enforceCoverageDisplayConstraints() {
      const manualVisible = !!showManualPathEl.checked;
      const algoVisible = !!showAlgorithmPathEl.checked;
      // If no path is visible, coverage display is not allowed.
      if (!manualVisible && !algoVisible) {
        showCoverageCirclesEl.checked = false;
      }
      // If selected source is manual but manual path is hidden, fallback to visible source.
      if (showCoverageOnManualPathEl.checked && !manualVisible) {
        if (algoVisible) {
          showCoverageOnManualPathEl.checked = false;
        } else {
          showCoverageCirclesEl.checked = false;
        }
      }
      // If selected source is algorithm but algorithm path is hidden, fallback to manual.
      if (!showCoverageOnManualPathEl.checked && !algoVisible) {
        if (manualVisible) {
          showCoverageOnManualPathEl.checked = true;
        } else {
          showCoverageCirclesEl.checked = false;
        }
      }

      // UI affordance: disable controls that are currently invalid.
      showCoverageCirclesEl.disabled = !manualVisible && !algoVisible;
      showCoverageOnManualPathEl.disabled = !manualVisible || !algoVisible;
      if (showCoverageCirclesEl.parentElement) {
        showCoverageCirclesEl.parentElement.classList.toggle('is-disabled', !!showCoverageCirclesEl.disabled);
      }
      if (showCoverageOnManualPathEl.parentElement) {
        showCoverageOnManualPathEl.parentElement.classList.toggle('is-disabled', !!showCoverageOnManualPathEl.disabled);
      }
    }

    function applySectionDirectionConstraint(data) {
      const lowReliefBlocked = !!(data && data.field_assessment && data.field_assessment.no_section_direction_due_low_relief);
      const label = showSectionDirectionsEl && showSectionDirectionsEl.parentElement ? showSectionDirectionsEl.parentElement : null;
      if (!showSectionDirectionsEl || !label) return;
      if (lowReliefBlocked) {
        showSectionDirectionsEl.checked = false;
        showSectionDirectionsEl.disabled = true;
        label.classList.add('is-disabled');
        label.title = 'Kein Flugrichtung pro Sektor - Hoehendifferenz kleiner Low-Relief-Schwelle';
        label.childNodes[label.childNodes.length - 1].textContent = ' Kein Flugrichtung pro Sektor - Hoehendifferenz kleiner Low-Relief-Schwelle';
      } else {
        showSectionDirectionsEl.disabled = false;
        label.classList.remove('is-disabled');
        label.title = '';
        label.childNodes[label.childNodes.length - 1].textContent = ` ${sectionDirectionLabelDefault}`;
      }
    }

    const FLOATING_BOX_DEFAULTS = {
      mapLegend: { title: 'Farbskala AGL', titleSelector: '.legend-title' },
      coverageResultBox: { title: 'Feldcharakteristik', titleSelector: '.title' },
      scaleWsBox: { title: 'W/S/H Hinweise' },
      displayControlBox: { title: 'Anzeigekontrolle', titleSelector: '.title' },
      analysisControlBox: { title: 'Analyse-Boxen', titleSelector: '.title' },
      quickSlotBox: { title: 'Schnellzugriff Felder', titleSelector: '.quick-slot-title' },
      fieldNavBox: { title: 'Feldnavigation' },
      mapQuickHelp: { title: 'Kontext-Hilfe' },
    };

    function getFloatingBoxElements() {
      return {
        mapLegend: mapLegendEl,
        coverageResultBox: coverageResultBoxEl,
        scaleWsBox: scaleWsBoxEl,
        displayControlBox: displayControlBoxEl,
        analysisControlBox: analysisControlBoxEl,
        quickSlotBox: quickSlotBoxEl,
        fieldNavBox: fieldNavBoxEl,
        mapQuickHelp: mapQuickHelpEl,
      };
    }

    function getRelativeBoxPosition(el) {
      if (!el || !mapWrapEl) return { left: 0, top: 0 };
      const wrapRect = mapWrapEl.getBoundingClientRect();
      const boxRect = el.getBoundingClientRect();
      return {
        left: Math.max(0, boxRect.left - wrapRect.left),
        top: Math.max(0, boxRect.top - wrapRect.top),
      };
    }

    function setFloatingBoxCollapsed(el, boxKey, collapsed) {
      if (!el) return;
      const toggleBtn = el.querySelector('.floating-box-toggle');
      el.classList.toggle('collapsed', !!collapsed);
      if (toggleBtn) toggleBtn.textContent = collapsed ? '+' : '-';
      if (!floatingBoxesState[boxKey]) floatingBoxesState[boxKey] = {};
      floatingBoxesState[boxKey].collapsed = !!collapsed;
    }

    function enhanceFloatingBox(el, boxKey, cfg) {
      if (!el || el.dataset.floatingReady === '1') return;
      el.dataset.floatingReady = '1';
      el.classList.add('floating-box');

      const titleSelector = cfg && cfg.titleSelector ? cfg.titleSelector : '';
      let titleText = cfg && cfg.title ? cfg.title : '';
      if (titleSelector) {
        const titleEl = el.querySelector(titleSelector);
        if (titleEl) {
          titleText = String(titleEl.textContent || '').trim() || titleText;
          titleEl.remove();
        }
      }

      const header = document.createElement('div');
      header.className = 'floating-box-header';
      const title = document.createElement('div');
      title.className = 'floating-box-title';
      title.textContent = titleText || boxKey;
      const toggleBtn = document.createElement('button');
      toggleBtn.type = 'button';
      toggleBtn.className = 'floating-box-toggle';
      toggleBtn.textContent = '-';
      toggleBtn.title = 'Ein-/Ausklappen';
      header.appendChild(title);
      const headerActions = document.createElement('div');
      headerActions.style.cssText = 'display:flex;gap:4px;flex:0 0 auto;align-items:center;';
      if (boxKey === 'mapQuickHelp') {
        const hideBtn = document.createElement('button');
        hideBtn.type = 'button';
        hideBtn.className = 'floating-box-toggle';
        hideBtn.textContent = 'Ausblenden';
        hideBtn.title = 'Kontext-Hilfe ausblenden';
        hideBtn.addEventListener('click', (ev) => {
          ev.preventDefault();
          ev.stopPropagation();
          const cb = document.querySelector('[data-rkrk-box-for="context"]');
          if (cb) cb.checked = false;
          if (window.RkrkLayout && typeof window.RkrkLayout.applyBoxes === 'function') {
            window.RkrkLayout.applyBoxes();
          }
          saveUiSettings();
        });
        headerActions.appendChild(hideBtn);
      }
      headerActions.appendChild(toggleBtn);
      header.appendChild(headerActions);

      const content = document.createElement('div');
      content.className = 'floating-box-content';
      while (el.firstChild) {
        content.appendChild(el.firstChild);
      }
      el.appendChild(header);
      el.appendChild(content);
      el.insertBefore(header, content);

      toggleBtn.addEventListener('click', (ev) => {
        ev.preventDefault();
        ev.stopPropagation();
        const nextCollapsed = !el.classList.contains('collapsed');
        setFloatingBoxCollapsed(el, boxKey, nextCollapsed);
        saveUiSettings();
      });

      let dragActive = false;
      let dragDx = 0;
      let dragDy = 0;
      const beginDrag = (clientX, clientY) => {
        const pos = getRelativeBoxPosition(el);
        dragDx = clientX - pos.left;
        dragDy = clientY - pos.top;
        dragActive = true;
      };
      const moveDrag = (clientX, clientY) => {
        if (!dragActive || !mapWrapEl) return;
        const wrapRect = mapWrapEl.getBoundingClientRect();
        const boxRect = el.getBoundingClientRect();
        const maxLeft = Math.max(0, wrapRect.width - boxRect.width);
        const maxTop = Math.max(0, wrapRect.height - boxRect.height);
        const left = Math.max(0, Math.min(maxLeft, clientX - dragDx));
        const top = Math.max(0, Math.min(maxTop, clientY - dragDy));
        el.style.left = `${left}px`;
        el.style.top = `${top}px`;
        el.style.right = 'auto';
        el.style.bottom = 'auto';
      };
      const endDrag = () => {
        if (!dragActive) return;
        dragActive = false;
        const pos = getRelativeBoxPosition(el);
        if (!floatingBoxesState[boxKey]) floatingBoxesState[boxKey] = {};
        floatingBoxesState[boxKey].left = Number(pos.left.toFixed(1));
        floatingBoxesState[boxKey].top = Number(pos.top.toFixed(1));
        saveUiSettings();
      };

      header.addEventListener('mousedown', (ev) => {
        if (ev.button !== 0) return;
        if (ev.target instanceof HTMLElement && ev.target.closest('button,input,label,a')) return;
        ev.preventDefault();
        beginDrag(ev.clientX, ev.clientY);
      });
      window.addEventListener('mousemove', (ev) => moveDrag(ev.clientX, ev.clientY));
      window.addEventListener('mouseup', () => endDrag());
    }

    function initFloatingBoxes() {
      const all = getFloatingBoxElements();
      Object.keys(FLOATING_BOX_DEFAULTS).forEach((boxKey) => {
        const el = all[boxKey];
        const cfg = FLOATING_BOX_DEFAULTS[boxKey];
        if (!el) return;
        enhanceFloatingBox(el, boxKey, cfg);
      });
      applyFloatingBoxesState();
      if (window.RkrkLayout && typeof window.RkrkLayout.applyBoxes === 'function') {
        window.RkrkLayout.applyBoxes();
      }
    }

    function applyFloatingBoxesState() {
      const all = getFloatingBoxElements();
      Object.keys(FLOATING_BOX_DEFAULTS).forEach((boxKey) => {
        const el = all[boxKey];
        if (!el) return;
        const st = floatingBoxesState && floatingBoxesState[boxKey] ? floatingBoxesState[boxKey] : null;
        if (st && Number.isFinite(Number(st.left)) && Number.isFinite(Number(st.top))) {
          el.style.left = `${Number(st.left)}px`;
          el.style.top = `${Number(st.top)}px`;
          el.style.right = 'auto';
          el.style.bottom = 'auto';
        }
        setFloatingBoxCollapsed(el, boxKey, !!(st && st.collapsed));
      });
    }

    function quickSlotLabel(fieldId, idx) {
      const f = fieldsById[fieldId];
      if (!f) return `#${idx + 1}`;
      const base = `${f.feld_id}`;
      return base.length > 10 ? base.slice(0, 10) : base;
    }

    function renderQuickSlots() {
      const containers = [quickSlotGridPanel, quickSlotGridMap].filter(Boolean);
      containers.forEach((container) => {
        container.innerHTML = '';
      });
      for (let i = 0; i < QUICK_SLOT_COUNT; i++) {
        const fid = quickFieldSlots[i] || '';
        const f = fieldsById[fid];
        containers.forEach((container) => {
          const btn = document.createElement('button');
          btn.type = 'button';
          btn.className = 'quick-slot-btn';
          btn.textContent = quickSlotLabel(fid, i);
          btn.title = f ? `Slot ${i + 1}: ${f.feld_id} - ${f.name}` : `Slot ${i + 1}: leer`;
          btn.addEventListener('click', (ev) => {
            if (ev.altKey) {
              quickFieldSlots[i] = fieldSelect.value || '';
              saveUiSettings();
              renderQuickSlots();
              return;
            }
            if (!fid || !fieldsById[fid]) return;
            fieldSelect.value = fid;
            syncFieldNameInput();
            saveUiSettings();
            refreshFieldOptionLabels();
            runAnalysis();
          });
          container.appendChild(btn);
        });
      }
    }

    function applyFieldSearchValue(raw) {
      const q = String(raw || '').trim().toLowerCase();
      if (!q) return false;
      let match = fieldSearchEntries.find((e) => e.label.toLowerCase() === q);
      if (!match) {
        match = fieldSearchEntries.find((e) =>
          e.name.toLowerCase().startsWith(q) ||
          e.feldId.toLowerCase().startsWith(q) ||
          e.label.toLowerCase().includes(q)
        );
      }
      if (!match || !fieldsById[match.id]) {
        return false;
      }
      fieldSelect.value = match.id;
      syncFieldNameInput();
      saveUiSettings();
      refreshFieldOptionLabels();
      runAnalysis();
      return true;
    }

    function navigateField(offset) {
      const opts = Array.from(fieldSelect.options);
      if (!opts.length) return;
      const idx = Math.max(0, opts.findIndex((o) => o.value === fieldSelect.value));
      const nextIdx = (idx + offset + opts.length) % opts.length;
      fieldSelect.value = opts[nextIdx].value;
      syncFieldNameInput();
      saveUiSettings();
      refreshFieldOptionLabels();
      runAnalysis();
    }

    async function runAnalysis() {
      const q = new URLSearchParams({
        field_id: fieldSelect.value,
        planner: plannerModeEl.value,
        model_id: modelSelectEl.value,
        use_field_reference_agl: useFieldReferenceAglEl.checked ? '1' : '0',
      });
      const resp = await fetch(`api_analyze.php?${q.toString()}`, { cache: 'no-store' });
      const data = await resp.json();
      renderFieldDecision(data);
      renderSpecialCasesReport(data);
      renderEffectiveParams(data);
      renderStepResults(data);
      renderExecutionModel(data);
      applySectionDirectionConstraint(data);
      scaleHValueEl.textContent = Number.isFinite(Number(data.formula && data.formula.agl_used_for_ws_m))
        ? `${Number(data.formula.agl_used_for_ws_m).toFixed(1)} m`
        : '-';
      scaleWValueEl.textContent = Number.isFinite(Number(data.formula && data.formula.W)) ? `${Number(data.formula.W).toFixed(2)} m` : '-';
      scaleSValueEl.textContent = Number.isFinite(Number(data.formula && data.formula.S)) ? `${Number(data.formula.S).toFixed(2)} m` : '-';

      source.clear();
      source.addFeature(polyFeature(data.field.polygon));
      const coverageWidthM = Number(data.formula && data.formula.W) > 0 ? Number(data.formula.W) : NaN;
      const turnDiameterM = Number(data.formula && data.formula.D) > 0 ? Number(data.formula.D) : 0;
      const hfovDeg = Number(
        (data.effective_params && data.effective_params.hfov)
        ?? (data.requested_params && data.requested_params.hfov)
        ?? (data.formula && data.formula.hfov)
      );
      const dfovDeg = Number(
        (data.effective_params && data.effective_params.dfov)
        ?? (data.requested_params && data.requested_params.dfov)
      );
      const aglUsedM = Number(data.formula && data.formula.agl_used_for_ws_m);
      let vfovDeg = Number(
        (data.effective_params && data.effective_params.vfov)
        ?? (data.requested_params && data.requested_params.vfov)
        ?? (data.formula && data.formula.vfov)
      );
      if ((!Number.isFinite(vfovDeg) || vfovDeg <= 0) && Number.isFinite(hfovDeg) && Number.isFinite(dfovDeg) && dfovDeg > hfovDeg) {
        const th = Math.tan((hfovDeg * Math.PI / 180.0) * 0.5);
        const td = Math.tan((dfovDeg * Math.PI / 180.0) * 0.5);
        const tv2 = Math.max(0, td * td - th * th);
        vfovDeg = 2.0 * Math.atan(Math.sqrt(tv2)) * 180.0 / Math.PI;
      }
      const forwardLookM = (Number.isFinite(aglUsedM) && Number.isFinite(vfovDeg) && vfovDeg > 0)
        ? (2.0 * aglUsedM * Math.tan((vfovDeg * Math.PI / 180.0) * 0.5))
        : 0;
      const speedMps = Number(
        (data.effective_params && data.effective_params.speed)
        ?? (data.requested_params && data.requested_params.speed)
        ?? 5
      );
      const turnDelayS = Number(
        (data.effective_params && data.effective_params.turn_delay_s)
        ?? (data.requested_params && data.requested_params.turn_delay_s)
        ?? 1.5
      );
      const edgeFactorVal = Number(
        (data.effective_params && data.effective_params.edge_factor)
        ?? (data.requested_params && data.requested_params.edge_factor)
      );
      const spacingS = Number(data.formula && data.formula.S);
      const edgeAllowanceM = (Number.isFinite(edgeFactorVal) && Number.isFinite(spacingS))
        ? (edgeFactorVal * spacingS)
        : NaN;
      const usePathCorrections = !!showCorrectionPointsEl.checked;
      const correctedAlgorithm = usePathCorrections
        ? applyCoverageCorrections(
            data.field && data.field.polygon,
            data.algorithm && data.algorithm.path,
            coverageWidthM,
            turnDiameterM,
            forwardLookM,
            edgeAllowanceM
          )
        : {
            path: Array.isArray(data.algorithm && data.algorithm.path) ? data.algorithm.path : [],
            corrections: [],
            coverage: computeCoverageStatsNewModel(
              data.field && data.field.polygon,
              data.algorithm && data.algorithm.path,
              coverageWidthM,
              turnDiameterM,
              forwardLookM
            ),
          };
      const algorithmPathForRender = correctedAlgorithm.path;
      const algorithmCorrectionPoints = correctedAlgorithm.corrections;
      const correctedManual = usePathCorrections
        ? applyCoverageCorrections(
            data.field && data.field.polygon,
            data.manual && data.manual.path,
            coverageWidthM,
            turnDiameterM,
            forwardLookM,
            edgeAllowanceM
          )
        : {
            path: Array.isArray(data.manual && data.manual.path) ? data.manual.path : [],
            corrections: [],
            coverage: computeCoverageStatsNewModel(
              data.field && data.field.polygon,
              data.manual && data.manual.path,
              coverageWidthM,
              turnDiameterM,
              forwardLookM
            ),
          };
      const manualPathForCoverage = correctedManual.path;
      const manualCorrectionPoints = correctedManual.corrections;
      const fieldCode = String(data.field && data.field.feld_id || '');
      const useFullCoverageFromStart = fieldCode === '26-077';
      const manualCoveragePathInside = useFullCoverageFromStart
        ? (Array.isArray(manualPathForCoverage) ? manualPathForCoverage : [])
        : coveragePathInsideField(manualPathForCoverage, data.field && data.field.polygon);
      const algorithmCoveragePathInside = useFullCoverageFromStart
        ? (Array.isArray(algorithmPathForRender) ? algorithmPathForRender : [])
        : coveragePathInsideField(algorithmPathForRender, data.field && data.field.polygon);
      const preferManualCoverage = showCoverageOnManualPathEl.checked || (!!showManualPathEl.checked && !showAlgorithmPathEl.checked);
      const coveragePath = preferManualCoverage ? manualCoveragePathInside : algorithmCoveragePathInside;
      const coverageSourceLabel = preferManualCoverage ? 'Alter Flugpfad' : 'Neuer Flugpfad';
      const coverageStats = computeCoverageStatsNewModel(
        data.field && data.field.polygon,
        coveragePath,
        coverageWidthM,
        turnDiameterM,
        forwardLookM
      );
      if (showBasePatternsEl.checked) {
        (data.field_form_sections || []).forEach((s) => {
          const f = formSectionFeature(s.polygon || []);
          if (f) source.addFeature(f);
        });
      }
      if (showSectionDirectionsEl.checked && !showSectionDirectionsEl.disabled) {
        (data.field_form_sections || []).forEach((s) => {
          const f = sectionDirectionFeature(s);
          if (f) source.addFeature(f);
        });
      }
      measurementGuideFeatures(data.field && data.field.polygon, data.formula && data.formula.W, data.formula && data.formula.S)
        .forEach((f) => source.addFeature(f));
      if (showManualPathEl.checked) {
      source.addFeature(lineFeature(data.manual.path, '#1d8a3a'));
        const manualFlightData = await computeWaypointFlightData(data.manual.path || []);
        const manualTiming = computeWaypointTiming(data.manual.path || [], speedMps, turnDelayS);
        waypointFeatures(data.manual.path || [], manualFlightData, '#1d8a3a', manualTiming).forEach((f) => source.addFeature(f));
        if (showCorrectionPointsEl.checked) {
          correctionWaypointFeatures(manualCorrectionPoints || []).forEach((f) => source.addFeature(f));
        }
      }
      if (showAlgorithmPathEl.checked) {
        source.addFeature(lineFeature(algorithmPathForRender || [], '#2160d6'));
        const algoFlightData = await computeWaypointFlightData(algorithmPathForRender || []);
        const algoTiming = computeWaypointTiming(algorithmPathForRender || [], speedMps, turnDelayS);
        waypointFeatures(algorithmPathForRender || [], algoFlightData, '#2160d6', algoTiming).forEach((f) => source.addFeature(f));
        if (showCorrectionPointsEl.checked) {
          const ruleDerivedPoints = classifyDerivedWaypointReasons(algorithmPathForRender || [], data.field && data.field.polygon);
          derivedWaypointReasonFeatures(ruleDerivedPoints).forEach((f) => source.addFeature(f));
        }
        if (showCorrectionPointsEl.checked) {
          correctionWaypointFeatures(algorithmCorrectionPoints || []).forEach((f) => source.addFeature(f));
        }
      }
      if (showCoverageCirclesEl.checked) {
        const originalCoverageFill = 'rgba(35,125,235,0.16)';
        const originalCoverageEdge = 'rgba(35,125,235,0.36)';
        const newCoverageFill = 'rgba(35,145,255,0.08)';
        const newCoverageEdge = 'rgba(60,150,255,0.30)';
        if (showManualPathEl.checked) {
          // Keep forward camera view (VFOV) exactly same colors as coverage band.
          coverageCorridorFeatures(manualCoveragePathInside, coverageWidthM, originalCoverageFill, originalCoverageEdge).forEach((f) => source.addFeature(f));
          vfovTerminalFeatures(manualCoveragePathInside, coverageWidthM, forwardLookM, originalCoverageFill, originalCoverageEdge).forEach((f) => source.addFeature(f));
          widthGuideFeatures(manualCoveragePathInside, coverageWidthM, 'rgba(35,125,235,0.95)', `HFOV W ${Number(coverageWidthM || 0).toFixed(1)}m (Original)`).forEach((f) => source.addFeature(f));
          turningDiameterGuideFeatures(manualCoveragePathInside, turnDiameterM, forwardLookM, originalCoverageFill, `DFOV D ${Number(turnDiameterM || 0).toFixed(1)}m (Original)`).forEach((f) => source.addFeature(f));
        }
        if (showAlgorithmPathEl.checked) {
          // Keep forward camera view (VFOV) exactly same colors as coverage band.
          coverageCorridorFeatures(algorithmCoveragePathInside, coverageWidthM, newCoverageFill, newCoverageEdge).forEach((f) => source.addFeature(f));
          vfovTerminalFeatures(algorithmCoveragePathInside, coverageWidthM, forwardLookM, newCoverageFill, newCoverageEdge).forEach((f) => source.addFeature(f));
          widthGuideFeatures(algorithmCoveragePathInside, coverageWidthM, 'rgba(60,150,255,0.92)', `HFOV W ${Number(coverageWidthM || 0).toFixed(1)}m (Neu)`).forEach((f) => source.addFeature(f));
          turningDiameterGuideFeatures(algorithmCoveragePathInside, turnDiameterM, forwardLookM, newCoverageFill, `DFOV D ${Number(turnDiameterM || 0).toFixed(1)}m (Neu)`).forEach((f) => source.addFeature(f));
        }
      }
      let relAglValuesPath = [];
      let relAglValuesField = [];
      if (showControlPointsEl.checked) {
        const pathCpPts = [];
        if (showManualPathEl.checked) {
          const manualBase = Array.isArray(data.manual && data.manual.path) ? data.manual.path : [];
          buildPathControlPointSamples(manualBase, 25).forEach((p) => pathCpPts.push(p));
        }
        if (showAlgorithmPathEl.checked) {
          if (Array.isArray(algorithmPathForRender) && algorithmPathForRender.length >= 2) {
            buildPathControlPointSamples(algorithmPathForRender, 25).forEach((p) => pathCpPts.push(p));
          } else {
            const fallback = data.algorithm.control_points_path || data.algorithm.control_points || [];
            fallback.forEach((p) => pathCpPts.push(p));
          }
        }
        relAglValuesPath = await computeControlPointRelativeAgl(pathCpPts, data.field.start);
        pointFeatures(pathCpPts, relAglValuesPath).forEach((f) => source.addFeature(f));
      }
      if (showFieldControlPointsEl.checked) {
        relAglValuesField = await computeControlPointRelativeAgl(
          data.algorithm.control_points_field || [],
          data.field.start
        );
        pointFeatures(data.algorithm.control_points_field || [], relAglValuesField).forEach((f) => source.addFeature(f));
      }
      updateMapLegend([...(relAglValuesPath || []), ...(relAglValuesField || [])]);
      map.getView().fit(source.getExtent(), { padding: [40, 40, 40, 40], maxZoom: 18 });

      const d = data.improvement.distance_pct;
      const t = data.improvement.time_pct;
      const h = data.improvement.height_delta_pct;
      const reqChecks = (data.requirement_checks || []).map((r) =>
        metricLine(`Check ${r.id}`, `${r.ok ? 'OK' : 'NICHT OK'} - ${r.detail}`, r.ok ? 'good' : 'bad')
      ).join('');
      const cpPreview = Array.isArray(relAglValuesPath) && relAglValuesPath.length
        ? relAglValuesPath.slice(0, 8).map((rel, i) => {
            return `CP${i + 1}: ${Number.isFinite(rel) ? `${rel >= 0 ? '+' : ''}${rel.toFixed(1)}m` : '-'}`;
          }).join(' | ')
        : 'Keine Kontrollpunkte';
      const fieldCpCount = Array.isArray(data.algorithm.control_points_field) ? data.algorithm.control_points_field.length : 0;
      const coverageRatio = coverageStats.coveredRatio;
      const doubleRatio = coverageStats.doubleRatio;
      if (showCoverageCirclesEl.checked) {
        doubleCoveredAreaFeatures(coverageStats.doubleCoveredCells || []).forEach((f) => source.addFeature(f));
      }
      if (showUncoveredAreasEl.checked) {
        uncoveredAreaFeatures(coverageStats.uncoveredCells || []).forEach((f) => source.addFeature(f));
      }
      covSourceValueEl.textContent = coverageSourceLabel;
      covFieldValueEl.textContent = Number.isFinite(coverageRatio) ? `${(coverageRatio * 100).toFixed(1)} %` : '-';
      covDoubleValueEl.textContent = Number.isFinite(doubleRatio) ? `${(doubleRatio * 100).toFixed(1)} %` : '-';
      covHfovWidthValueEl.textContent = Number.isFinite(coverageWidthM) ? `${coverageWidthM.toFixed(1)} m` : '-';
      covDfovDiameterValueEl.textContent = Number.isFinite(turnDiameterM) && turnDiameterM > 0 ? `${turnDiameterM.toFixed(1)} m` : '-';
      const coverageDistanceM = preferManualCoverage
        ? Number(data.manual && data.manual.metrics && data.manual.metrics.distance_m)
        : Number(data.algorithm && data.algorithm.metrics && data.algorithm.metrics.distance_m);
      covDistanceValueEl.textContent = Number.isFinite(coverageDistanceM)
        ? `${coverageDistanceM.toFixed(1)} m`
        : '-';
      let dfovFormulaText = 'DFOV = 2*atan(sqrt(tan(HFOV/2)^2 + tan(VFOV/2)^2)); VFOV-Wert fehlt in den aktuellen Daten.';
      if (Number.isFinite(hfovDeg) && Number.isFinite(vfovDeg) && hfovDeg > 0 && vfovDeg > 0) {
        const hRad = hfovDeg * Math.PI / 180.0;
        const vRad = vfovDeg * Math.PI / 180.0;
        const dfovRad = 2.0 * Math.atan(Math.sqrt(Math.tan(hRad * 0.5) ** 2 + Math.tan(vRad * 0.5) ** 2));
        const dfovDegFromHv = dfovRad * 180.0 / Math.PI;
        dfovFormulaText =
          `DFOV = 2*atan(sqrt(tan(HFOV/2)^2 + tan(VFOV/2)^2)); `
          + `mit HFOV=${hfovDeg.toFixed(1)}deg, VFOV=${vfovDeg.toFixed(1)}deg => DFOV=${dfovDegFromHv.toFixed(1)}deg`;
      }
      const coverageFlightDataForStats = await computeWaypointFlightData(Array.isArray(coveragePath) ? coveragePath : []);
      const topVals = coverageFlightDataForStats.map((v) => Number(v && v.top)).filter((v) => Number.isFinite(v));
      const aglVals = (Array.isArray(coveragePath) ? coveragePath : []).map((p) => Number(p && p[2])).filter((v) => Number.isFinite(v));
      let climb = 0;
      let descent = 0;
      for (let i = 0; i + 1 < topVals.length; i++) {
        const d = topVals[i + 1] - topVals[i];
        if (d > 0) climb += d;
        else descent += -d;
      }
      covClimbValueEl.textContent = `${climb.toFixed(1)} m`;
      covDescentValueEl.textContent = `${descent.toFixed(1)} m`;
      covMaxAglValueEl.textContent = aglVals.length ? `${Math.max(...aglVals).toFixed(1)} m` : '-';
      covMinPathValueEl.textContent = topVals.length ? `${Math.min(...topVals).toFixed(1)} m` : '-';
      metricsEl.innerHTML = collapsibleSection(
        'Analysewerte',
        metricLine('Berechnungsmodus', data.planner === 'python' ? 'Python StdLib' : 'PHP') +
        metricLine('Abdeckungsmodell', 'HFOV-Band + VFOV-Vorblick + DFOV-Drehpunkte') +
        metricLine('Modell', data.model_used ? `${data.model_used.name} (${data.model_used.id})` : 'Kein Modell-Override') +
        metricLine('Schritt-Requirements aktiv', (data.step_requirements_applied || []).join(', ') || 'Keine') +
        metricLine('Feldform (Schritt 2)', `${data.field_form_analysis && data.field_form_analysis.is_simple ? 'einfach' : 'zusammengesetzt'} / Sektionen: ${Array.isArray(data.field_form_sections) ? data.field_form_sections.length : 0}`) +
        metricLine('Richtung (höhenbasiert)', `${data.direction_used || 'auto'}${data.fixed_theta_deg !== null ? ` / ${Number(data.fixed_theta_deg).toFixed(1)}°` : ''}`) +
        metricLine('Startpunkt als Relativ-0', `Ja (Start-Referenzhöhe: ${Number(data.start_alt_reference_m || 0).toFixed(2)} m)`) +
        metricLine('Feld-Referenzflughöhe', data.use_field_reference_agl ? 'aktiv' : 'inaktiv') +
        metricLine('Vergleichs-AGL (Referenz)', `${Number(data.agl_used_m || 0).toFixed(1)} m`) +
        metricLine('Sichtbreite W', `${data.formula.W.toFixed(2)} m`) +
        metricLine('Spurabstand S', `${data.formula.S.toFixed(2)} m`) +
        metricLine('DFOV-Formel', dfovFormulaText) +
        metricLine('Schmalfeld-Regel', `${data.algorithm && data.algorithm.narrow_field ? 'aktiv' : 'inaktiv'} (Breite ${Number(data.algorithm && data.algorithm.field_width_m || 0).toFixed(2)} m / Schwelle ${Number(data.algorithm && data.algorithm.narrow_field_threshold_m || 0).toFixed(2)} m)`) +
        metricLine('Manuell Distanz', `${data.manual.metrics.distance_m.toFixed(1)} m`) +
        metricLine('Algorithmus Distanz', `${data.algorithm.metrics.distance_m.toFixed(1)} m`) +
        metricLine('Verbesserung Distanz', `${d.toFixed(2)} %`, d >= 0 ? 'good' : 'bad') +
        metricLine('Verbesserung Flugzeit', `${t.toFixed(2)} %`, t >= 0 ? 'good' : 'bad') +
        metricLine('Verbesserung Höhendifferenz', `${h.toFixed(2)} %`, h >= 0 ? 'good' : 'bad') +
        metricLine('Wegpunkte manuell / algo', `${data.manual.metrics.waypoints} / ${data.algorithm.metrics.waypoints}`) +
        metricLine('Abdeckung (entlang Flugweg, Breite W)', Number.isFinite(coverageRatio) ? `${(coverageRatio * 100).toFixed(1)} %` : '-') +
        metricLine('Doppelt überflogene Fläche', Number.isFinite(doubleRatio) ? `${(doubleRatio * 100).toFixed(1)} %` : '-') +
        metricLine('Abdeckungsquelle', coverageSourceLabel) +
        metricLine('Korrekturpunkte Abdeckung', `${preferManualCoverage ? (Array.isArray(manualCorrectionPoints) ? manualCorrectionPoints.length : 0) : (Array.isArray(algorithmCorrectionPoints) ? algorithmCorrectionPoints.length : 0)}`) +
        metricLine('Kontrollpunkte Flugbahn (Auszug)', cpPreview) +
        metricLine('Kontrollpunkte Feldraster (Step 1)', String(fieldCpCount)) +
        reqChecks,
        'analysisValues',
        false
      );
    }

    runBtn.addEventListener('click', runAnalysis);
    showBasePatternsEl.addEventListener('change', () => { saveUiSettings(); runAnalysis(); });
    Object.keys(analysisBlockToggles).forEach((k) => {
      const cfg = analysisBlockToggles[k];
      if (!cfg || !cfg.input) return;
      cfg.input.addEventListener('change', () => {
        applyAnalysisBlocksVisibility();
        saveUiSettings();
      });
    });
    hideAllBlocksBtnEl.addEventListener('click', () => {
      Object.keys(analysisBlockToggles).forEach((k) => {
        analysisBlockToggles[k].input.checked = false;
      });
      applyAnalysisBlocksVisibility();
      saveUiSettings();
    });
    showAllBlocksBtnEl.addEventListener('click', () => {
      Object.keys(analysisBlockToggles).forEach((k) => {
        analysisBlockToggles[k].input.checked = true;
      });
      applyAnalysisBlocksVisibility();
      saveUiSettings();
    });
    showManualPathEl.addEventListener('change', () => { enforceCoverageDisplayConstraints(); saveUiSettings(); runAnalysis(); });
    showAlgorithmPathEl.addEventListener('change', () => { enforceCoverageDisplayConstraints(); saveUiSettings(); runAnalysis(); });
    showSectionDirectionsEl.addEventListener('change', () => { saveUiSettings(); runAnalysis(); });
    showCoverageCirclesEl.addEventListener('change', () => { enforceCoverageDisplayConstraints(); saveUiSettings(); runAnalysis(); });
    showCoverageOnManualPathEl.addEventListener('change', () => { enforceCoverageDisplayConstraints(); saveUiSettings(); runAnalysis(); });
    showUncoveredAreasEl.addEventListener('change', () => { saveUiSettings(); runAnalysis(); });
    showControlPointsEl.addEventListener('change', () => { saveUiSettings(); runAnalysis(); });
    showFieldControlPointsEl.addEventListener('change', () => { saveUiSettings(); runAnalysis(); });
    showCorrectionPointsEl.addEventListener('change', () => { saveUiSettings(); runAnalysis(); });
    useFieldReferenceAglEl.addEventListener('change', () => { saveUiSettings(); runAnalysis(); });
    setDefaultFieldBtn.addEventListener('click', () => {
      if (!fieldSelect.value) return;
      localStorage.setItem(DEFAULT_FIELD_KEY, fieldSelect.value);
      saveUiSettings();
      refreshFieldOptionLabels();
      runAnalysis();
    });
    modelSelectEl.addEventListener('change', () => {
      saveUiSettings();
      runAnalysis();
    });
    fieldSelect.addEventListener('change', () => {
      saveUiSettings();
      refreshFieldOptionLabels();
      syncFieldNameInput();
      runAnalysis();
    });
    fieldNameInput.addEventListener('change', () => {
      if (!applyFieldSearchValue(fieldNameInput.value)) {
        syncFieldNameInput();
      }
    });
    fieldNameInput.addEventListener('keydown', (ev) => {
      if (ev.key !== 'Enter') return;
      ev.preventDefault();
      if (!applyFieldSearchValue(fieldNameInput.value)) {
        syncFieldNameInput();
      }
    });
    baseMapModeEl.addEventListener('change', () => {
      saveUiSettings();
      applyBaseMapMode();
      runAnalysis();
    });
    plannerModeEl.addEventListener('change', () => {
      saveUiSettings();
      runAnalysis();
    });
    prevFieldBtn.addEventListener('click', () => navigateField(-1));
    nextFieldBtn.addEventListener('click', () => navigateField(1));
    resetSettingsBtn.addEventListener('click', () => {
      localStorage.removeItem(UI_SETTINGS_KEY);
      localStorage.removeItem(DEFAULT_FIELD_KEY);
      plannerModeEl.value = 'php';
      baseMapModeEl.value = 'swisstopo';
      showControlPointsEl.checked = true;
      showManualPathEl.checked = true;
      showAlgorithmPathEl.checked = true;
      showBasePatternsEl.checked = true;
      showSectionDirectionsEl.checked = true;
      showCoverageCirclesEl.checked = false;
      showCoverageOnManualPathEl.checked = false;
      showUncoveredAreasEl.checked = true;
      showFieldControlPointsEl.checked = true;
      showCorrectionPointsEl.checked = true;
      useFieldReferenceAglEl.checked = true;
      Object.keys(analysisBlockToggles).forEach((k) => {
        analysisBlockToggles[k].input.checked = true;
      });
      collapseState = {};
      quickFieldSlots = new Array(QUICK_SLOT_COUNT).fill('');
      floatingBoxesState = {};
      const contextHelpCb = document.querySelector('[data-rkrk-box-for="context"]');
      if (contextHelpCb) contextHelpCb.checked = false;
      if (window.RkrkLayout && typeof window.RkrkLayout.applyBoxes === 'function') {
        window.RkrkLayout.applyBoxes();
      }
      if (modelMap.default) {
        modelSelectEl.value = 'default';
      }
      pendingUiSettings = null;
      refreshFieldOptionLabels();
      syncFieldNameInput();
      renderQuickSlots();
      applyAnalysisBlocksVisibility();
      applyBaseMapMode();
      enforceCoverageDisplayConstraints();
      applyFloatingBoxesState();
      runAnalysis();
    });
    document.addEventListener('toggle', (ev) => {
      const el = ev.target;
      if (!(el instanceof HTMLDetailsElement)) return;
      const key = el.getAttribute('data-collapse-key');
      if (!key) return;
      collapseState[key] = el.open;
      saveUiSettings();
    }, true);
    window.addEventListener('beforeunload', () => {
      saveUiSettings();
    });
    loadUiSettings();
    enforceCoverageDisplayConstraints();
    initFloatingBoxes();
    const contextHelpCbInit = document.querySelector('[data-rkrk-box-for="context"]');
    if (contextHelpCbInit) {
      contextHelpCbInit.addEventListener('change', () => saveUiSettings());
    }
    applyAnalysisBlocksVisibility();
    applyBaseMapMode();
    if (window.RkrkHelp) {
      RkrkHelp.mount({
        page: 'analyse',
        getView: () => {
          if (showCoverageCirclesEl && showCoverageCirclesEl.checked) return 'analyse.coverage';
          return 'analyse.overview';
        },
      });
    }
    loadStatus();
    Promise.all([loadModels(), loadFields()]).then(() => {
      saveUiSettings();
      syncFieldNameInput();
      renderQuickSlots();
      runAnalysis();
    });
  </script>
</body>
</html>

