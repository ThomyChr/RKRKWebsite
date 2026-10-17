<?php
require_once __DIR__ . '/mapll_env.php';
$mapllBrand = mapll_favicon_brand();
?><!doctype html>
<html <?php echo mapll_html_open_attrs(); ?>>
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Interface Mapping Matrix</title>
  <?php echo mapll_favicon_link_tags($mapllBrand); ?>
  <?php echo mapll_theme_css_link(); ?>
  <link rel="stylesheet" href="rkrk_layout.css" />
  <link rel="stylesheet" href="../template/rkrk_reg_pills.css?v=20260907flicker" />
  <style>
    body { margin: 0; font-family: Arial, sans-serif; background: #f4f6f8; color: #1f344a; }
    .wrap { max-width: 1300px; margin: 0 auto; padding: 16px; }
    h1 { margin: 0 0 8px; font-size: 24px; }
    .small { color: #5d738a; font-size: 13px; margin-bottom: 10px; }
    .toplinks { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 12px; }
    .toplinks a { text-decoration: none; color: #9aa4ae; background: #fff; border: 1px solid #c5cdd4; padding: 2px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; }
    .card { background: #fff; border: 1px solid #d9dde2; border-radius: 8px; padding: 10px; margin-bottom: 12px; }
    .meta { display: flex; gap: 14px; flex-wrap: wrap; font-size: 13px; color: #2f4963; }
    .mono { font-family: Consolas, monospace; }
    table { width: 100%; border-collapse: collapse; background: #fff; border: 1px solid #d9dde2; border-radius: 8px; overflow: hidden; }
    th, td { border-bottom: 1px solid #edf1f5; padding: 8px; vertical-align: top; font-size: 12px; }
    th { background: #f7fafd; color: #28425e; text-align: left; }
    tr:last-child td { border-bottom: 0; }
    .ok { color: #1f7a3b; font-weight: bold; }
    .warn { color: #9a6500; font-weight: bold; }
    .error { color: #a22b2b; }
  </style>
</head>
<body>
  <div class="wrap">
    <h1>Mapping Matrix (Form ↔ UAV JSON ↔ DJI/KMZ)</h1>
    <div class="small">Abgleich der bekannten Felder inkl. Typ, Format, Transformation und Zielpfaden.</div>
    <div class="toplinks">
      <a href="field_editor.php" target="_blank" rel="noopener">Zum Field Editor</a>
      <a href="analyse_ui.php" target="_blank" rel="noopener">Zur Analyse</a>
    </div>
    <div class="card">
      <div id="meta" class="meta">Lade Mapping...</div>
    </div>
    <table>
      <thead>
        <tr>
          <th>Form-Feld</th>
          <th>Typ</th>
          <th>Format</th>
          <th>Optionen (UI)</th>
          <th>Aktive Option</th>
          <th>Angezeigt</th>
          <th>Aktiv genutzt</th>
          <th>UAV JSON Ziel</th>
          <th>DJI/KMZ Ziel</th>
          <th>Transformation</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody id="rows">
        <tr><td colspan="11">Lade...</td></tr>
      </tbody>
    </table>
  </div>
  <script>
    async function loadMapping() {
      const body = { action: 'get_mapping_schema', field: { mission_settings: {} } };
      const r = await fetch('api_mission_export.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body),
      });
      const data = await r.json();
      if (!data.ok) throw new Error(data.error || 'mapping_load_failed');
      return data.mapping || {};
    }

    function text(v) {
      if (typeof v === 'boolean') return v ? 'true' : 'false';
      if (v == null) return '';
      return String(v);
    }

    function render(mapping) {
      const metaEl = document.getElementById('meta');
      const rowsEl = document.getElementById('rows');
      const cov = mapping.coverage || {};
      metaEl.innerHTML = `
        <div><strong>Quelle:</strong> <span class="mono">${text(mapping.source)}</span></div>
        <div><strong>Gemappt:</strong> ${text(cov.fully_mapped_count)}/${text(cov.mapped_count)}</div>
        <div><strong>Koordinatenformat:</strong> <span class="mono">${text((mapping.formats || {}).coordinates)}</span></div>
      `;
      const rows = Array.isArray(mapping.rows) ? mapping.rows : [];
      rowsEl.innerHTML = rows.map((r) => {
        const statusCls = r.status === 'mapped' ? 'ok' : (r.status === 'partial' ? 'warn' : 'error');
        const optionsTxt = Array.isArray(r.ui_options) ? r.ui_options.map((x) => text(x)).join(', ') : text(r.ui_options);
        return `<tr>
          <td class="mono">${text(r.form)}</td>
          <td>${text(r.type)}</td>
          <td class="mono">${text(r.format)}</td>
          <td>${text(optionsTxt)}</td>
          <td class="mono">${text(r.active_option)}</td>
          <td>${r.displayed ? 'ja' : 'nein'}</td>
          <td>${r.active_used ? 'ja' : 'nein'}</td>
          <td class="mono">${text(r.uav)}</td>
          <td class="mono">${text(r.dji)}</td>
          <td>${text(r.transform)}</td>
          <td class="${statusCls}">${text(r.status)}</td>
        </tr>`;
      }).join('');
      if (!rows.length) rowsEl.innerHTML = '<tr><td colspan="11">Keine Mapping-Daten.</td></tr>';
    }

    (async function init() {
      try {
        const mapping = await loadMapping();
        render(mapping);
      } catch (e) {
        document.getElementById('rows').innerHTML = `<tr><td colspan="11" class="error">Fehler: ${String(e.message || e)}</td></tr>`;
      }
    })();
  </script>
  <script src="rkrk_layout.js"></script>
</body>
</html>

