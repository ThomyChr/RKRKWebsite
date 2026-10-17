<?php
require_once __DIR__ . '/mapll_env.php';
$mapllBrand = mapll_favicon_brand();
?><!doctype html>
<html <?php echo mapll_html_open_attrs(); ?>>
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Schrittweise Requirements</title>
  <?php echo mapll_favicon_link_tags($mapllBrand); ?>
  <?php echo mapll_theme_css_link(); ?>
  <link rel="stylesheet" href="rkrk_layout.css" />
  <link rel="stylesheet" href="../template/rkrk_reg_pills.css?v=20260907flicker" />
  <style>
    body { margin: 0; font-family: Arial, sans-serif; background: #f4f6f8; color: #1f2937; }
    .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
    .card { background: #fff; border: 1px solid #d9dde2; border-radius: 10px; padding: 14px; margin-bottom: 12px; }
    .panel-details { border: 1px solid #d9dde2; border-radius: 8px; background: #fff; margin-bottom: 12px; }
    .panel-details > summary { cursor: pointer; padding: 10px 12px; font-weight: bold; background: #eef2f6; border-radius: 8px; }
    .panel-details-content { padding: 12px; }
    fieldset { border: 1px solid #d9dde2; border-radius: 8px; margin-bottom: 10px; }
    legend { padding: 0 6px; font-weight: bold; }
    table { width: 100%; border-collapse: collapse; font-size: 14px; }
    th, td { border: 1px solid #d9dde2; padding: 8px; vertical-align: top; }
    th { background: #f7f9fb; }
    input, select, textarea, button { width: 100%; box-sizing: border-box; padding: 8px; }
    .actions { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 10px; }
    .actions button, .actions a { width: auto; text-decoration: none; border: 1px solid #c5cdd4; border-radius: 999px; padding: 2px 10px; cursor: pointer; background:#fff; color:#9aa4ae; font-weight:600; font-size:12px; }
    .primary { background: var(--brand-dark, #48673A); color: #fff; border-color:var(--brand-dark, #48673A); border-radius:4px; padding:8px 12px; }
    .secondary { background: #fff; color: #9aa4ae; }
    .danger { background: #9d1d1d; color: #fff; }
    .status { margin-top: 12px; padding: 8px; background: #f8fafb; border: 1px solid #d9dde2; border-radius: 8px; }
    .small { color: #5f6b76; font-size: 12px; }
  </style>
</head>
<body>
  <div class="container">
    <div class="card">
      <h1 style="margin:0 0 6px;">Schrittweise Requirements & Hilfsparameter</h1>
      <p class="small" style="margin:0;">Jeder Berechnungsschritt hat sein eigenes Requirement-Set. Aenderungen wirken direkt auf die Algorithmik bei der naechsten Analyse.</p>
      <div class="actions">
        <a class="secondary" href="index.php">Startseite</a>
        <a class="secondary" href="analyse_ui.php">Analyse</a>
        <a class="secondary" href="requirements_print.php" target="_blank" rel="noopener">Requirements als PDF/Print</a>
        <a class="secondary" href="rkrk_feldeditor_spec.php">Spec</a>
      </div>
    </div>

    <div id="stepsRoot"></div>

    <div class="card">
      <div class="actions">
        <button id="reloadBtn" class="secondary">Neu laden</button>
        <button id="saveBtn" class="primary">Speichern</button>
      </div>
      <div id="status" class="status">Bereit.</div>
    </div>
  </div>

  <script>
    const stepsRoot = document.getElementById('stepsRoot');
    const statusEl = document.getElementById('status');
    let model = null;
    let globalCoreMap = {};

    function setStatus(msg) { statusEl.textContent = msg; }

    function inputCell(value) {
      const td = document.createElement('td');
      const inp = document.createElement('input');
      inp.value = value ?? '';
      td.appendChild(inp);
      return td;
    }

    function render() {
      stepsRoot.innerHTML = '';
      (model.steps || []).forEach((step, idx) => {
        const details = document.createElement('details');
        details.className = 'panel-details';
        details.open = true;
        details.innerHTML = `<summary>${step.id} - ${step.name}</summary>`;
        const content = document.createElement('div');
        content.className = 'panel-details-content';

        const fsHead = document.createElement('fieldset');
        fsHead.innerHTML = `
          <legend>Schritt-Konfiguration</legend>
          <div style="display:grid;grid-template-columns:120px 1fr 120px;gap:8px;align-items:center;">
            <label>Aktiv</label>
            <input type="checkbox" ${step.enabled ? 'checked' : ''} data-k="enabled" />
            <span></span>
            <label>Name</label>
            <input value="${(step.name || '').replace(/"/g, '&quot;')}" data-k="name" />
            <span></span>
            <label>Beschreibung</label>
            <textarea data-k="description" style="min-height:72px;">${step.description || ''}</textarea>
            <span></span>
          </div>
        `;

        const fsParams = document.createElement('fieldset');
        fsParams.innerHTML = `<legend>Hilfsparameter</legend>
          <table><thead><tr><th>Key</th><th>Label</th><th>Wert</th><th>Einheit</th><th>Aktion</th></tr></thead><tbody></tbody></table>
          <div class="small" style="margin-top:8px;" data-role="overrideHint">Keine globalen Overrides erkannt.</div>
          <div class="actions"><button type="button" class="secondary">Parameter hinzufuegen</button></div>`;
        const pBody = fsParams.querySelector('tbody');
        (step.helper_parameters || []).forEach((p) => {
          const tr = document.createElement('tr');
          tr.appendChild(inputCell(p.key));
          tr.appendChild(inputCell(p.label));
          tr.appendChild(inputCell(p.value));
          tr.appendChild(inputCell(p.unit));
          const tdDel = document.createElement('td');
          tdDel.innerHTML = `<button type="button" class="danger">Loeschen</button>`;
          tdDel.querySelector('button').onclick = () => tr.remove();
          tr.appendChild(tdDel);
          tr.querySelectorAll('input').forEach((inp) => {
            inp.addEventListener('input', () => updateOverrideHint(fsParams, step));
          });
          pBody.appendChild(tr);
        });
        fsParams.querySelector('.actions button').onclick = () => {
          const tr = document.createElement('tr');
          tr.appendChild(inputCell('new_key'));
          tr.appendChild(inputCell('Neuer Parameter'));
          tr.appendChild(inputCell(''));
          tr.appendChild(inputCell('-'));
          const tdDel = document.createElement('td');
          tdDel.innerHTML = `<button type="button" class="danger">Loeschen</button>`;
          tdDel.querySelector('button').onclick = () => tr.remove();
          tr.appendChild(tdDel);
          pBody.appendChild(tr);
          updateOverrideHint(fsParams, step);
        };

        const fsReq = document.createElement('fieldset');
        fsReq.innerHTML = `<legend>Requirements</legend>
          <table><thead><tr><th>ID</th><th>Prioritaet</th><th>Formel</th><th>Beschreibung</th><th>Aktion</th></tr></thead><tbody></tbody></table>
          <div class="actions"><button type="button" class="secondary">Requirement hinzufuegen</button></div>`;
        const rBody = fsReq.querySelector('tbody');
        (step.requirements || []).forEach((r) => {
          const tr = document.createElement('tr');
          tr.appendChild(inputCell(r.id));
          const tdPrio = document.createElement('td');
          tdPrio.innerHTML = `<select><option>must</option><option>should</option><option>could</option></select>`;
          tdPrio.querySelector('select').value = r.priority || 'should';
          tr.appendChild(tdPrio);
          tr.appendChild(inputCell(r.formula));
          tr.appendChild(inputCell(r.description));
          const tdDel = document.createElement('td');
          tdDel.innerHTML = `<button type="button" class="danger">Loeschen</button>`;
          tdDel.querySelector('button').onclick = () => tr.remove();
          tr.appendChild(tdDel);
          rBody.appendChild(tr);
        });
        fsReq.querySelector('.actions button').onclick = () => {
          const tr = document.createElement('tr');
          tr.appendChild(inputCell(`S${idx + 1}-RNEW`));
          const tdPrio = document.createElement('td');
          tdPrio.innerHTML = `<select><option>must</option><option>should</option><option>could</option></select>`;
          tr.appendChild(tdPrio);
          tr.appendChild(inputCell('formula'));
          tr.appendChild(inputCell('Beschreibung'));
          const tdDel = document.createElement('td');
          tdDel.innerHTML = `<button type="button" class="danger">Loeschen</button>`;
          tdDel.querySelector('button').onclick = () => tr.remove();
          tr.appendChild(tdDel);
          rBody.appendChild(tr);
        };

        content.appendChild(fsHead);
        content.appendChild(fsParams);
        content.appendChild(fsReq);
        details.appendChild(content);
        stepsRoot.appendChild(details);
        updateOverrideHint(fsParams, step);
      });
    }

    function mapGlobalToStepKeys() {
      // Mapping between global core parameter keys and step helper keys.
      return {
        overlap: ['overlap'],
        edge_factor: ['edge_factor'],
        target_step_m: ['height_control_step_m'],
        max_nb_points: ['max_profile_points'],
        min_delay_s: ['api_min_delay_s'],
        agl_tolerance_m: ['agl_tolerance_m']
      };
    }

    function updateOverrideHint(fsParams, step) {
      const hintEl = fsParams.querySelector('[data-role="overrideHint"]');
      if (!hintEl) return;
      const rows = [...fsParams.querySelectorAll('tbody tr')];
      const localMap = {};
      rows.forEach((tr) => {
        const i = tr.querySelectorAll('input');
        if (i.length < 3) return;
        const k = i[0].value.trim();
        const raw = i[2].value.trim();
        if (!k) return;
        const n = Number(raw);
        localMap[k] = Number.isFinite(n) && raw !== '' ? n : raw;
      });

      const pairs = [];
      const map = mapGlobalToStepKeys();
      Object.keys(map).forEach((gk) => {
        const gVal = globalCoreMap[gk];
        if (gVal === undefined) return;
        map[gk].forEach((lk) => {
          if (!(lk in localMap)) return;
          const lVal = localMap[lk];
          if (String(gVal) !== String(lVal)) {
            pairs.push(`${gk}=${gVal} -> ${lk}=${lVal}`);
          }
        });
      });

      if (!pairs.length) {
        hintEl.textContent = 'Keine globalen Overrides erkannt.';
        hintEl.style.color = '#5f6b76';
      } else {
        hintEl.textContent = `Override aktiv (${step.id}): ${pairs.join(' | ')}`;
        hintEl.style.color = '#9d1d1d';
      }
    }

    function collect() {
      const detailsEls = [...stepsRoot.querySelectorAll('.panel-details')];
      model.steps = detailsEls.map((detailsEl, idx) => {
        const step = model.steps[idx];
        const enabled = detailsEl.querySelector('[data-k="enabled"]').checked;
        const name = detailsEl.querySelector('[data-k="name"]').value.trim();
        const description = detailsEl.querySelector('[data-k="description"]').value.trim();

        const paramRows = [...detailsEl.querySelectorAll('fieldset:nth-of-type(2) tbody tr')];
        const helper_parameters = paramRows.map((tr) => {
          const i = tr.querySelectorAll('input');
          const rawVal = i[2].value.trim();
          const asNum = Number(rawVal);
          return {
            key: i[0].value.trim(),
            label: i[1].value.trim(),
            value: Number.isFinite(asNum) && rawVal !== '' ? asNum : rawVal,
            unit: i[3].value.trim()
          };
        });

        const reqRows = [...detailsEl.querySelectorAll('fieldset:nth-of-type(3) tbody tr')];
        const requirements = reqRows.map((tr) => {
          const i = tr.querySelectorAll('input');
          const s = tr.querySelector('select');
          return {
            id: i[0].value.trim(),
            priority: s ? s.value : 'should',
            formula: i[1].value.trim(),
            description: i[2].value.trim()
          };
        });

        return { ...step, enabled, name, description, helper_parameters, requirements };
      });
      return model;
    }

    async function loadData() {
      const resp = await fetch('api_step_requirements.php', { cache: 'no-store' });
      model = await resp.json();
      try {
        const reqResp = await fetch('api_requirements.php', { cache: 'no-store' });
        const reqData = await reqResp.json();
        globalCoreMap = {};
        (reqData.core_parameters || []).forEach((p) => {
          const k = String(p.key || '').trim();
          if (!k) return;
          globalCoreMap[k] = p.value;
        });
      } catch (_) {
        globalCoreMap = {};
      }
      render();
      setStatus('Schritt-Requirements geladen.');
    }

    async function saveData() {
      const payload = collect();
      const resp = await fetch('api_step_requirements.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });
      const out = await resp.json();
      if (!resp.ok || !out.ok) {
        setStatus('Speichern fehlgeschlagen.');
        return;
      }
      setStatus(`Gespeichert. Letztes Update: ${out.last_updated}`);
      await loadData();
    }

    document.getElementById('reloadBtn').addEventListener('click', loadData);
    document.getElementById('saveBtn').addEventListener('click', saveData);
    loadData();
  </script>
  <script src="rkrk_layout.js"></script>
</body>
</html>

