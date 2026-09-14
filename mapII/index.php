<?php
require_once __DIR__ . '/mapll_env.php';
$mapllBrand = mapll_favicon_brand();
$brandLabel = htmlspecialchars((string)($mapllBrand['label'] ?? 'RKRK'), ENT_QUOTES, 'UTF-8');
?><!doctype html>
<html <?php echo mapll_html_open_attrs(['data-rkrk-layout' => 'off']); ?>>
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?php echo $brandLabel; ?> Guided Start</title>
  <?php echo mapll_favicon_link_tags($mapllBrand); ?>
  <?php echo mapll_theme_css_link(); ?>
  <link rel="stylesheet" href="rkrk_layout.css" />
  <link rel="stylesheet" href="../template/rkrk_reg_pills.css?v=20260907flicker" />
  <style>
    .container { max-width: 960px; margin: 0 auto; padding: 24px; }
    .hero { background: #fff; border: 1px solid #d9dde2; border-radius: 8px; padding: 20px; }
    .title { margin: 0 0 8px; font-size: 26px; }
    .muted { color: #5b6f86; margin: 0; font-size: 13px; line-height: 1.45; }
    .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 16px; }
    .card { background: #fff; border: 1px solid #d9dde2; border-radius: 8px; padding: 16px; }
    .card h3 { margin: 0 0 10px; font-size: 18px; color: #1b3552; }
    .step { margin: 0 0 10px; line-height: 1.45; }
    .status { margin-top: 10px; padding: 8px; border-radius: 6px; background: #f8fafb; border: 1px solid #d9dde2; }
    .ok { color: #1d6d36; font-weight: bold; }
    .bad { color: #9d1d1d; font-weight: bold; }
    .actions { display: flex; gap: 10px; margin-top: 14px; flex-wrap: wrap; }
    .btn { text-decoration: none; background: var(--brand-dark, #48673A); color: #fff; padding: 10px 14px; border-radius: 8px; display: inline-block; }
    .btn.secondary { background: var(--brand, #96B588); }
    code { background: #eef2f6; padding: 2px 6px; border-radius: 4px; }
    @media (max-width: 800px) { .grid { grid-template-columns: 1fr; } }
  </style>
</head>
<body class="rkrk-page">
  <div class="container">
    <div class="hero">
      <h1 class="rkr-brand-header title"><?php echo mapll_favicon_mark_html($mapllBrand); ?>Geführte Startseite</h1>
      <p class="muted">Systematische Rehkitz-Feldanalyse mit XML-Basisdaten und Vergleich manueller vs. algorithmischer Flugpfade.</p>
      <?php echo mapll_nav_toplinks(['analyse', 'editor', 'requirements', 'steps', 'mapping', 'layers', 'colors', 'api_fields', 'api_status']); ?>
    </div>

    <div class="grid">
      <div class="card">
        <h3>Schritt-für-Schritt</h3>
        <p class="step"><strong>1)</strong> Intern unter <code>/test/mapll</code> oder eigenständig unter <code>/rkrs/mapII</code> öffnen.</p>
        <p class="step"><strong>2)</strong> In <a href="analyse_ui.php">analyse_ui.php</a> Feld auswählen.</p>
        <p class="step"><strong>3)</strong> Planner wählen: <code>PHP</code> (schnell) oder <code>Python</code> (präziser).</p>
        <p class="step"><strong>4)</strong> Parameter prüfen (HFOV, AGL, Overlap, Randfaktor, Speed).</p>
        <p class="step"><strong>5)</strong> <code>Analyse starten</code> klicken und Metriken vergleichen.</p>
      </div>

      <div class="card">
        <h3>Systemcheck</h3>
        <div id="statusBox">
          <div class="status">Lade Systemstatus...</div>
        </div>
      </div>
    </div>
  </div>

  <script>
    async function loadStatus() {
      const box = document.getElementById('statusBox');
      try {
        const resp = await fetch('api_status.php', { cache: 'no-store' });
        const data = await resp.json();
        box.innerHTML = `
          <div class="status">XML: <span class="${data.xml_exists ? 'ok' : 'bad'}">${data.xml_exists ? 'OK' : 'Fehlt'}</span></div>
          <div class="status">Anzahl Felder: <strong>${data.field_count || 0}</strong></div>
          <div class="status">Python: <span class="${data.python_ok ? 'ok' : 'bad'}">${data.python_ok ? (data.python_version || 'Verfügbar') : 'Nicht verfügbar'}</span></div>
        `;
      } catch (e) {
        box.innerHTML = `<div class="status"><span class="bad">Status konnte nicht geladen werden.</span></div>`;
      }
    }
    loadStatus();
  </script>
  <script src="rkrk_layout.js"></script>
</body>
</html>
