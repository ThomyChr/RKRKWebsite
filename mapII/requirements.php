<?php
require_once __DIR__ . '/mapll_env.php';
$mapllBrand = mapll_favicon_brand();
?><!doctype html>
<html <?php echo mapll_html_open_attrs(); ?>>
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Algorithmische Requirements</title>
  <?php echo mapll_favicon_link_tags($mapllBrand); ?>
  <?php echo mapll_theme_css_link(); ?>
  <link rel="stylesheet" href="rkrk_layout.css" />
  <link rel="stylesheet" href="../template/rkrk_reg_pills.css?v=20260907flicker" />
  <style>
    body { margin: 0; font-family: Arial, sans-serif; background: #f5f7fa; color: #1f2937; }
    .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
    .card { background: #fff; border: 1px solid #d9dde2; border-radius: 10px; padding: 16px; margin-bottom: 16px; }
    h1, h2 { margin: 0 0 12px; }
    .muted { color: #5f6b76; }
    table { width: 100%; border-collapse: collapse; font-size: 14px; }
    th, td { border: 1px solid #d9dde2; padding: 10px; text-align: left; vertical-align: top; }
    th { background: #eef2f6; }
    input, select, textarea, button { width: 100%; box-sizing: border-box; padding: 8px; font-size: 14px; }
    input, select, textarea { border: 1px solid #c9d1db; border-radius: 6px; }
    input:focus, select:focus, textarea:focus { outline: 2px solid #bfd5f1; border-color: #6b9bd0; }
    textarea { min-height: 260px; font-family: Consolas, monospace; }
    .row { display: grid; grid-template-columns: 1fr; gap: 16px; }
    .actions { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 12px; }
    .actions button, .actions a { width: auto; padding: 2px 10px; border: 1px solid #c5cdd4; border-radius: 999px; text-decoration: none; cursor: pointer; background:#fff; color:#9aa4ae; font-weight:600; font-size:12px; }
    .primary { background: var(--brand-dark, #48673A); color: #fff; border-color:var(--brand-dark, #48673A); border-radius:4px; padding:8px 14px; }
    .secondary { background: #fff; color: #9aa4ae; border-color:#c5cdd4; }
    .danger { background: #9d1d1d; color: #fff; }
    .status { padding: 8px; border: 1px solid #d9dde2; border-radius: 8px; background: #f8fafb; margin-top: 10px; }
    .desc-cell { min-width: 280px; }
    .desc-input { min-height: 72px; resize: vertical; line-height: 1.4; }
    .detail-box { margin-top: 12px; padding: 12px; border: 1px solid #d9dde2; border-radius: 8px; background: #f8fafb; }
    .detail-title { margin: 0 0 6px; font-size: 15px; }
    .detail-text { margin: 0; line-height: 1.55; white-space: pre-wrap; }
    .formula-details { margin-top: 10px; border: 1px solid #d9dde2; border-radius: 8px; background: #fff; }
    .formula-details > summary { cursor: pointer; padding: 10px 12px; font-weight: bold; background: #eef2f6; border-radius: 8px; }
    .formula-content { padding: 10px 12px; }
    .unused-red { color: #b42318 !important; }
    .unused-red td, .unused-red input, .unused-red textarea, .unused-red select { color: #b42318 !important; }
    .merged-strike td { text-decoration: line-through; opacity: 0.85; }
    @media (max-width: 1000px) { .row { grid-template-columns: 1fr; } }
  </style>
</head>
<body>
  <div class="container">
    <div class="card">
      <h1>Algorithmische Requirements</h1>
      <p class="muted">Hier kannst du Kernparameter und Requirements ansehen, ändern und erweitern. Änderungen werden in <code>requirements_data.json</code> gespeichert.</p>
      <div class="actions">
        <a class="secondary" href="index.php">Zur Startseite</a>
        <a class="secondary" href="analyse_ui.php">Zur Analyse</a>
        <a class="secondary" href="requirements_print.php" target="_blank" rel="noopener">Requirements als PDF/Print</a>
      </div>
      <div class="detail-box" style="margin-top:12px;">
        <h3 class="detail-title">Damit Requirements ins Berechnungsmodell gehen</h3>
        <p class="detail-text">Verwende diese Kernparameter-Keys fuer die Berechnungsbasis: HFOV_deg, DFOV_deg, target_agl_m, overlap, edge_factor, speed_m_s, turn_delay_s, agl_tolerance_m, angle_step_deg, target_step_m, max_nb_points, min_delay_s, low_relief_height_threshold_m, min_path_coverage_ratio, curve_waypoint_angle_step_deg. Danach auf "Speichern" und dann "Modell aus Kernparametern erzeugen". Rot markierte Eintraege sind aktuell nicht direkt in der Berechnung verdrahtet.</p>
      </div>
    </div>

    <div class="card">
      <h2>Basisformeln und Definitionen</h2>
      <details class="formula-details" open>
        <summary>Sichtbreite</summary>
        <div class="formula-content">
          <p class="detail-text"><strong>W = 2 * h * tan(HFOV / 2)</strong><br>W: Sichtbreite am Boden, h: Flughöhe AGL, HFOV: horizontaler Kamerawinkel.</p>
        </div>
      </details>
      <details class="formula-details">
        <summary>Spurabstand</summary>
        <div class="formula-content">
          <p class="detail-text"><strong>S = (1 - overlap) * W</strong><br>Bei 15% Überlappung gilt: S = 0.85 * W.</p>
        </div>
      </details>
      <details class="formula-details">
        <summary>Randregel</summary>
        <div class="formula-content">
          <p class="detail-text"><strong>d_rand &lt;= edge_factor * S</strong><br>Die Bahnen müssen nicht bis zum Rand gehen; der maximale Randabstand ist begrenzt.</p>
        </div>
      </details>
      <details class="formula-details">
        <summary>AGL-Hartgrenze</summary>
        <div class="formula-content">
          <p class="detail-text"><strong>max_t |AGL_path(t) - AGL_target| &lt;= agl_tolerance_m</strong><br>Entscheidend ist der kontinuierliche Abstand zum Boden entlang des Flugsegments (nicht nur die Differenz an Wegpunkten). Bei Verletzung müssen zusätzliche Wegpunkte gesetzt werden.</p>
        </div>
      </details>
      <details class="formula-details">
        <summary>Richtungsdefinition</summary>
        <div class="formula-content">
          <p class="detail-text"><strong>direction = f(height_points)</strong><br>Die Flugrichtung wird aus den Höhenpunkten des Feldes abgeleitet (konturliniennah, minimaler Höhenwechsel).</p>
        </div>
      </details>
      <details class="formula-details">
        <summary>Vergleichsdefinition</summary>
        <div class="formula-content">
          <p class="detail-text"><strong>AGL_algo = AGL_reference_field</strong><br>Algorithmus und Referenzflug werden pro Feld mit derselben Referenz-Flughöhe verglichen.</p>
        </div>
      </details>
      <details class="formula-details">
        <summary>Drehpunkt-Abdeckung (DFOV)</summary>
        <div class="formula-content">
          <p class="detail-text"><strong>D = 2 * h * tan(DFOV / 2)</strong><br>D: Kreisdurchmesser an Drehpunkten. Bandabdeckung entlang Flugweg bleibt auf W (HFOV-basiert), Drehpunktkreise werden mit DFOV berechnet.</p>
        </div>
      </details>
      <details class="formula-details">
        <summary>Pfadabdeckung ohne Leerflaechen</summary>
        <div class="formula-content">
          <p class="detail-text"><strong>coverage(path_band(W) ∪ turn_circles(D), field) >= min_path_coverage_ratio</strong><br>Wenn die Mindestabdeckung unterschritten wird, wird der Wegflugplan neu berechnet.</p>
        </div>
      </details>
    </div>

    <div class="row">
      <div class="card">
        <h2>Kernparameter</h2>
        <p class="muted" style="margin-top:0;">Hinweis: Diese Werte sind globale Defaults und koennen durch Schritt-Requirements ueberschrieben werden.</p>
        <table id="paramsTable">
          <thead>
            <tr><th>Key</th><th>Label</th><th>Wert</th><th>Einheit</th><th>Beschreibung</th><th>Aktion</th></tr>
          </thead>
          <tbody></tbody>
        </table>
        <div class="actions">
          <button id="addParamBtn" class="secondary">Parameter hinzufügen</button>
        </div>
      </div>

      <div class="card">
        <h2>Requirements</h2>
        <p class="muted" style="margin-top:0;">Tipp: Klicke in eine Zeile, um die Beschreibung unten groß und besser lesbar zu sehen.</p>
        <table id="reqTable">
          <thead>
            <tr><th>ID</th><th>Name</th><th>Priorität</th><th>Status</th><th>Formel</th><th>Beschreibung</th><th>Aktion</th></tr>
          </thead>
          <tbody></tbody>
        </table>
        <div class="detail-box">
          <h3 class="detail-title">Beschreibung (Detailansicht)</h3>
          <p id="reqDetailText" class="detail-text">Keine Zeile ausgewählt.</p>
        </div>
        <div class="actions">
          <button id="addReqBtn" class="secondary">Requirement hinzufügen</button>
        </div>
      </div>
    </div>

    <div class="card">
      <h2>Override-Pruefung (Global vs. Schritt-Requirements)</h2>
      <div id="overrideCheckBox" class="status">Pruefe Overrides...</div>
    </div>

    <div class="card">
      <h2>Direkt-JSON Editor</h2>
      <textarea id="jsonEditor"></textarea>
      <div class="actions">
        <button id="reloadBtn" class="secondary">Neu laden</button>
        <button id="syncBtn" class="secondary">Aus Tabellen in JSON übernehmen</button>
        <button id="saveBtn" class="primary">Speichern</button>
        <button id="createModelBtn" class="secondary">Modell aus Kernparametern erzeugen</button>
      </div>
      <div id="status" class="status">Bereit.</div>
    </div>
  </div>

  <script>
    const paramsBody = document.querySelector('#paramsTable tbody');
    const reqBody = document.querySelector('#reqTable tbody');
    const reqDetailText = document.getElementById('reqDetailText');
    const jsonEditor = document.getElementById('jsonEditor');
    const statusEl = document.getElementById('status');
    const overrideCheckBoxEl = document.getElementById('overrideCheckBox');
    let model = null;
    const calcUsedCoreParamKeys = new Set([
      'HFOV_deg', 'DFOV_deg', 'target_agl_m', 'overlap', 'edge_factor', 'speed_m_s',
      'turn_delay_s', 'turn_delay_skip_wp_s', 'agl_tolerance_m', 'angle_step_deg', 'target_step_m', 'max_nb_points',
      'min_delay_s', 'low_relief_height_threshold_m', 'min_path_coverage_ratio', 'curve_waypoint_angle_step_deg'
    ]);
    const calcUsedRequirementIds = new Set([
      'R-001', 'R-002', 'R-003', 'R-004', 'R-005', 'R-008', 'R-009', 'R-011', 'R-012', 'R-013',
      'R-014', 'R-015', 'R-016', 'R-017', 'R-018', 'R-019', 'R-023', 'R-024', 'R-025', 'R-026',
      'R-027', 'R-028', 'R-029', 'R-030', 'R-031', 'R-032', 'R-033', 'R-035', 'R-036', 'R-037'
    ]);

    function setStatus(msg) {
      statusEl.textContent = msg;
    }

    function tdInput(value = '') {
      const td = document.createElement('td');
      const input = document.createElement('input');
      input.value = value;
      td.appendChild(input);
      return td;
    }

    function tdDeleteButton(onDelete) {
      const td = document.createElement('td');
      const btn = document.createElement('button');
      btn.textContent = 'Löschen';
      btn.className = 'danger';
      btn.style.width = '100%';
      btn.onclick = onDelete;
      td.appendChild(btn);
      return td;
    }

    function tdDescription(value = '') {
      const td = document.createElement('td');
      td.className = 'desc-cell';
      const area = document.createElement('textarea');
      area.value = value;
      area.className = 'desc-input';
      area.addEventListener('focus', () => {
        reqDetailText.textContent = area.value || 'Leere Beschreibung.';
      });
      area.addEventListener('input', () => {
        reqDetailText.textContent = area.value || 'Leere Beschreibung.';
      });
      td.appendChild(area);
      return td;
    }

    function tdSelectPriority(value = 'should') {
      const td = document.createElement('td');
      const sel = document.createElement('select');
      ['must', 'should', 'could'].forEach(p => {
        const o = document.createElement('option');
        o.value = p;
        o.textContent = p;
        sel.appendChild(o);
      });
      sel.value = value;
      td.appendChild(sel);
      return td;
    }

    function tdSelectStatus(value = 'valid') {
      const td = document.createElement('td');
      const sel = document.createElement('select');
      ['valid', 'conditional', 'non_relevant', 'invalid'].forEach(s => {
        const o = document.createElement('option');
        o.value = s;
        o.textContent = s;
        sel.appendChild(o);
      });
      sel.value = value || 'valid';
      td.appendChild(sel);
      return td;
    }

    function renderTables() {
      paramsBody.innerHTML = '';
      reqBody.innerHTML = '';

      (model.core_parameters || []).forEach(p => {
        const tr = document.createElement('tr');
        const pKey = String(p.key || '').trim();
        if (!calcUsedCoreParamKeys.has(pKey)) tr.classList.add('unused-red');
        tr.appendChild(tdInput(p.key || ''));
        tr.appendChild(tdInput(p.label || ''));
        tr.appendChild(tdInput(String(p.value ?? '')));
        tr.appendChild(tdInput(p.unit || ''));
        tr.appendChild(tdInput(p.description || ''));
        tr.appendChild(tdDeleteButton(() => tr.remove()));
        paramsBody.appendChild(tr);
      });

      (model.requirements || []).forEach(r => {
        const tr = document.createElement('tr');
        const rid = String(r.id || '').trim();
        const status = String(r.status || 'valid').trim();
        const formula = String(r.formula || '').toLowerCase();
        const isMerged = formula.includes('merged_into(') || status === 'invalid';
        if (!calcUsedRequirementIds.has(rid)) tr.classList.add('unused-red');
        if (isMerged) tr.classList.add('merged-strike');
        tr.appendChild(tdInput(r.id || ''));
        tr.appendChild(tdInput(r.name || ''));
        tr.appendChild(tdSelectPriority(r.priority || 'should'));
        tr.appendChild(tdSelectStatus(r.status || 'valid'));
        tr.appendChild(tdInput(r.formula || ''));
        tr.appendChild(tdDescription(r.description || ''));
        tr.appendChild(tdDeleteButton(() => tr.remove()));
        tr.addEventListener('click', (ev) => {
          if (ev.target && ev.target.tagName === 'BUTTON') return;
          const desc = tr.querySelector('textarea');
          reqDetailText.textContent = desc ? (desc.value || 'Leere Beschreibung.') : 'Leere Beschreibung.';
        });
        reqBody.appendChild(tr);
      });
    }

    function tableToModel() {
      const params = [];
      [...paramsBody.querySelectorAll('tr')].forEach(tr => {
        const inputs = tr.querySelectorAll('input');
        if (inputs.length < 5) return;
        params.push({
          key: inputs[0].value.trim(),
          label: inputs[1].value.trim(),
          value: Number.isFinite(Number(inputs[2].value)) ? Number(inputs[2].value) : inputs[2].value.trim(),
          unit: inputs[3].value.trim(),
          description: inputs[4].value.trim(),
        });
      });

      const reqs = [];
      [...reqBody.querySelectorAll('tr')].forEach(tr => {
        const inputs = tr.querySelectorAll('input');
        const selects = tr.querySelectorAll('select');
        const prio = selects[0];
        const status = selects[1];
        const desc = tr.querySelector('textarea');
        if (inputs.length < 3 || !prio) return;
        reqs.push({
          id: inputs[0].value.trim(),
          name: inputs[1].value.trim(),
          priority: prio.value,
          status: status ? status.value : 'valid',
          formula: inputs[2].value.trim(),
          description: desc ? desc.value.trim() : '',
        });
      });

      model.core_parameters = params;
      model.requirements = reqs;
      jsonEditor.value = JSON.stringify(model, null, 2);
    }

    async function loadData() {
      const resp = await fetch('api_requirements.php', { cache: 'no-store' });
      model = await resp.json();
      jsonEditor.value = JSON.stringify(model, null, 2);
      renderTables();
      await renderOverrideCheck();
      setStatus('Requirements geladen.');
    }

    async function renderOverrideCheck() {
      try {
        const reqMap = {};
        (model.core_parameters || []).forEach((p) => {
          const k = String(p.key || '').trim();
          if (!k) return;
          reqMap[k] = p.value;
        });
        const stepResp = await fetch('api_step_requirements.php', { cache: 'no-store' });
        const stepData = await stepResp.json();
        const keyMap = {
          overlap: ['overlap'],
          edge_factor: ['edge_factor'],
          target_step_m: ['height_control_step_m'],
          max_nb_points: ['max_profile_points'],
          min_delay_s: ['api_min_delay_s'],
          agl_tolerance_m: ['agl_tolerance_m'],
        };
        const lines = [];
        (stepData.steps || []).forEach((step) => {
          if (!step.enabled) return;
          const local = {};
          (step.helper_parameters || []).forEach((p) => {
            const k = String(p.key || '').trim();
            if (!k) return;
            local[k] = p.value;
          });
          Object.keys(keyMap).forEach((gk) => {
            if (!(gk in reqMap)) return;
            keyMap[gk].forEach((lk) => {
              if (!(lk in local)) return;
              if (String(reqMap[gk]) !== String(local[lk])) {
                lines.push(`${step.id}: ${gk}=${reqMap[gk]} -> ${lk}=${local[lk]}`);
              }
            });
          });
        });
        if (!lines.length) {
          overrideCheckBoxEl.textContent = 'Keine aktiven Overrides zwischen globalen Kernparametern und Schritt-Requirements.';
          overrideCheckBoxEl.style.color = '#1d6d36';
        } else {
          overrideCheckBoxEl.innerHTML = lines.map((l) => `<div>${l}</div>`).join('');
          overrideCheckBoxEl.style.color = '#9d1d1d';
        }
      } catch (e) {
        overrideCheckBoxEl.textContent = 'Override-Pruefung konnte nicht geladen werden.';
        overrideCheckBoxEl.style.color = '#9d1d1d';
      }
    }

    async function saveData() {
      try {
        // Always sync table edits into JSON before saving.
        tableToModel();
        const parsed = JSON.parse(jsonEditor.value);
        const resp = await fetch('api_requirements.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(parsed),
        });
        const out = await resp.json();
        if (!resp.ok || !out.ok) {
          setStatus('Speichern fehlgeschlagen.');
          return;
        }
        setStatus(`Gespeichert. Letztes Update: ${out.last_updated}`);
        await loadData();
      } catch (e) {
        setStatus('Ungültiges JSON oder Speichern fehlgeschlagen.');
      }
    }

    document.getElementById('addParamBtn').addEventListener('click', () => {
      const tr = document.createElement('tr');
      tr.appendChild(tdInput('new_param'));
      tr.appendChild(tdInput('Neuer Parameter'));
      tr.appendChild(tdInput('0'));
      tr.appendChild(tdInput('-'));
      tr.appendChild(tdInput('Beschreibung'));
      tr.appendChild(tdDeleteButton(() => tr.remove()));
      paramsBody.appendChild(tr);
    });

    document.getElementById('addReqBtn').addEventListener('click', () => {
      const tr = document.createElement('tr');
      tr.appendChild(tdInput('R-NEW'));
      tr.appendChild(tdInput('Neues Requirement'));
      tr.appendChild(tdSelectPriority('should'));
      tr.appendChild(tdSelectStatus('valid'));
      tr.appendChild(tdInput('formula'));
      tr.appendChild(tdDescription('Beschreibung'));
      tr.appendChild(tdDeleteButton(() => tr.remove()));
      tr.addEventListener('click', () => {
        const desc = tr.querySelector('textarea');
        reqDetailText.textContent = desc ? (desc.value || 'Leere Beschreibung.') : 'Leere Beschreibung.';
      });
      reqBody.appendChild(tr);
    });

    document.getElementById('reloadBtn').addEventListener('click', loadData);
    document.getElementById('syncBtn').addEventListener('click', () => {
      tableToModel();
      setStatus('Tabellen in JSON übernommen.');
      renderOverrideCheck();
    });
    document.getElementById('saveBtn').addEventListener('click', saveData);
    document.getElementById('createModelBtn').addEventListener('click', async () => {
      const name = window.prompt('Modellname aus Requirements:', '');
      if (!name) return;
      try {
        const resp = await fetch('api_models.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ action: 'create_from_requirements', name: name.trim() })
        });
        const out = await resp.json();
        if (!resp.ok || !out.ok) {
          setStatus('Modellerzeugung fehlgeschlagen.');
          return;
        }
        setStatus(`Modell erstellt: ${out.model_id}`);
      } catch (_) {
        setStatus('Modellerzeugung fehlgeschlagen.');
      }
    });

    loadData();
  </script>
  <script src="rkrk_layout.js"></script>
</body>
</html>

