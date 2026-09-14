const API = window.RKRK_REF_LAYERS_API || 'rkrk_api_ref_layers.php';
let activeTab = 'colors';
let colors = { groups: {}, styles: {} };
let ui = { toggles: {}, filterBbox: false };

function setStatus(msg) { document.getElementById('status').textContent = msg || ''; }
function escapeHtml(s) {
  return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function hexFromCss(css) {
  const s = String(css || '').trim();
  if (/^#[0-9a-fA-F]{6}$/.test(s)) return s.toLowerCase();
  if (/^#[0-9a-fA-F]{3}$/.test(s)) return ('#' + s[1]+s[1]+s[2]+s[2]+s[3]+s[3]).toLowerCase();
  const m = s.match(/rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/i);
  if (m) {
    const h = (n) => Number(n).toString(16).padStart(2, '0');
    return ('#' + h(m[1]) + h(m[2]) + h(m[3])).toLowerCase();
  }
  return '#888888';
}

function rowHtml(kind, key, entry) {
  const stroke = entry.stroke || '#888888';
  const fill = entry.fill || 'rgba(136,136,136,0.25)';
  const width = entry.width != null ? entry.width : 2;
  const opacityPct = Math.round((entry.opacity != null ? Number(entry.opacity) : 1) * 100);
  const label = entry.label || key;
  return `<div class="row" data-kind="${kind}" data-key="${escapeHtml(key)}">
    <label title="${escapeHtml(key)}">${escapeHtml(label)}</label>
    <div style="display:flex;gap:6px;align-items:center;">
      <input type="color" data-field="strokeColor" value="${escapeHtml(hexFromCss(stroke))}" title="Stroke-Farbe waehlen" />
      <input type="text" data-field="stroke" value="${escapeHtml(stroke)}" title="Stroke CSS" />
    </div>
    <input type="text" data-field="fill" value="${escapeHtml(fill)}" title="Fill CSS (rgba empfohlen)" />
    <input type="number" data-field="width" min="0.5" max="12" step="0.5" value="${escapeHtml(width)}" title="Linienbreite" />
    <input type="number" data-field="opacity" min="0" max="100" step="5" value="${escapeHtml(opacityPct)}" title="Transparenz in %" />
    <div class="swatch" data-swatch style="--s:${escapeHtml(stroke)};--f:${escapeHtml(fill)};opacity:${Math.max(0.05, opacityPct/100)}" title="Vorschau"></div>
  </div>`;
}

function bindRow(row) {
  const syncSwatch = () => {
    const stroke = row.querySelector('[data-field="stroke"]').value;
    const fill = row.querySelector('[data-field="fill"]').value;
    const sw = row.querySelector('[data-swatch]');
    sw.style.setProperty('--s', stroke);
    sw.style.setProperty('--f', fill);
  };
  const strokeColor = row.querySelector('[data-field="strokeColor"]');
  const strokeText = row.querySelector('[data-field="stroke"]');
  strokeColor.addEventListener('input', () => { strokeText.value = strokeColor.value; syncSwatch(); });
  strokeText.addEventListener('change', () => { strokeColor.value = hexFromCss(strokeText.value); syncSwatch(); });
  row.querySelector('[data-field="fill"]').addEventListener('input', syncSwatch);
  const op = row.querySelector('[data-field="opacity"]');
  if (op) op.addEventListener('input', () => {
    const sw = row.querySelector('[data-swatch]');
    const pct = Number(op.value);
    sw.style.opacity = String(Math.max(0.05, (Number.isFinite(pct) ? pct : 100) / 100));
  });
}

function renderColors() {
  const gRoot = document.getElementById('groupsRoot');
  const sRoot = document.getElementById('stylesRoot');
  gRoot.innerHTML = Object.keys(colors.groups).map((k) => rowHtml('groups', k, colors.groups[k])).join('');
  sRoot.innerHTML = Object.keys(colors.styles).map((k) => rowHtml('styles', k, colors.styles[k])).join('');
  gRoot.querySelectorAll('.row').forEach(bindRow);
  sRoot.querySelectorAll('.row').forEach(bindRow);
}

function renderUi() {
  const root = document.getElementById('togglesRoot');
  const keys = Object.keys(ui.toggles || {});
  const labels = {};
  Object.keys(colors.groups || {}).forEach((k) => { labels[k] = colors.groups[k].label || k; });
  root.innerHTML = keys.map((k) => `
    <label title="${escapeHtml(k)}">
      <input type="checkbox" data-toggle="${escapeHtml(k)}" ${ui.toggles[k] ? 'checked' : ''} />
      ${escapeHtml(labels[k] || k)}
    </label>`).join('');
  document.getElementById('filterBbox').checked = !!ui.filterBbox;
}

function collectColors() {
  const groups = {};
  const styles = {};
  document.querySelectorAll('[data-panel="colors"] .row').forEach((row) => {
    const kind = row.getAttribute('data-kind');
    const key = row.getAttribute('data-key');
    const opacityPct = Number(row.querySelector('[data-field="opacity"]').value);
    const entry = {
      stroke: row.querySelector('[data-field="stroke"]').value.trim(),
      fill: row.querySelector('[data-field="fill"]').value.trim(),
      width: Number(row.querySelector('[data-field="width"]').value) || 2,
      opacity: Math.max(0, Math.min(1, (Number.isFinite(opacityPct) ? opacityPct : 100) / 100)),
    };
    if (kind === 'groups') {
      entry.label = (colors.groups[key] && colors.groups[key].label) || key;
      groups[key] = entry;
    } else {
      styles[key] = entry;
    }
  });
  return { groups, styles };
}

function collectUi() {
  const toggles = {};
  document.querySelectorAll('[data-toggle]').forEach((cb) => {
    toggles[cb.getAttribute('data-toggle')] = !!cb.checked;
  });
  return { toggles, filterBbox: !!document.getElementById('filterBbox').checked };
}

function setTab(name) {
  activeTab = name;
  document.querySelectorAll('.tab').forEach((t) => t.classList.toggle('is-active', t.getAttribute('data-tab') === name));
  document.querySelectorAll('.panel').forEach((p) => {
    p.hidden = p.getAttribute('data-panel') !== name;
  });
}

async function loadAll() {
  setStatus('Lade…');
  const [cRes, uRes] = await Promise.all([
    fetch(`${API}?action=colors`, { cache: 'no-store' }),
    fetch(`${API}?action=ui_defaults`, { cache: 'no-store' }),
  ]);
  const cData = await cRes.json();
  const uData = await uRes.json();
  if (!cData.ok) throw new Error(cData.message || cData.error || 'colors_failed');
  if (!uData.ok) throw new Error(uData.message || uData.error || 'ui_failed');
  colors = { groups: cData.groups || {}, styles: cData.styles || {} };
  ui = { toggles: uData.toggles || {}, filterBbox: !!uData.filterBbox };
  renderColors();
  renderUi();
  setStatus(`Farben: ${cData.source || '—'} · UI: ${uData.source || '—'}`);
}

async function saveAll() {
  setStatus('Speichere…');
  const colorBody = Object.assign({ action: 'save_colors' }, collectColors());
  const uiBody = Object.assign({ action: 'save_ui_defaults' }, collectUi());
  const [cRes, uRes] = await Promise.all([
    fetch(API, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(colorBody) }),
    fetch(API, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(uiBody) }),
  ]);
  const cData = await cRes.json();
  const uData = await uRes.json();
  if (!cData.ok) throw new Error(cData.message || cData.error || 'save_colors_failed');
  if (!uData.ok) throw new Error(uData.message || uData.error || 'save_ui_failed');
  colors = { groups: cData.groups || {}, styles: cData.styles || {} };
  ui = { toggles: uData.toggles || {}, filterBbox: !!uData.filterBbox };
  renderColors();
  renderUi();
  setStatus('Gespeichert: colors.json + ui_defaults.json');
}

async function resetActive() {
  if (activeTab === 'colors') {
    if (!confirm('Farben auf dokml-Defaults zuruecksetzen?')) return;
    const r = await fetch(`${API}?action=reset_colors`, { cache: 'no-store' });
    const data = await r.json();
    if (!data.ok) throw new Error(data.message || data.error || 'reset_failed');
    colors = { groups: data.groups || {}, styles: data.styles || {} };
    renderColors();
    setStatus('Farben-Defaults gespeichert.');
  } else {
    if (!confirm('Layer-Defaults zuruecksetzen (FZ/HSP/Alert ein)?')) return;
    const r = await fetch(`${API}?action=reset_ui_defaults`, { cache: 'no-store' });
    const data = await r.json();
    if (!data.ok) throw new Error(data.message || data.error || 'reset_failed');
    ui = { toggles: data.toggles || {}, filterBbox: !!data.filterBbox };
    renderUi();
    setStatus('UI-Defaults gespeichert.');
  }
}

document.querySelectorAll('.tab').forEach((t) => {
  t.addEventListener('click', () => setTab(t.getAttribute('data-tab')));
});
document.getElementById('saveBtn').onclick = () => saveAll().catch((e) => setStatus(String(e.message || e)));
document.getElementById('reloadBtn').onclick = () => loadAll().catch((e) => setStatus(String(e.message || e)));
document.getElementById('resetBtn').onclick = () => resetActive().catch((e) => setStatus(String(e.message || e)));
document.getElementById('allOnBtn').onclick = () => {
  document.querySelectorAll('[data-toggle]').forEach((cb) => { cb.checked = true; });
};
document.getElementById('allOffBtn').onclick = () => {
  document.querySelectorAll('[data-toggle]').forEach((cb) => { cb.checked = false; });
};
document.getElementById('presetBtn').onclick = () => {
  const keep = { FZ: 1, HSP: 1, Alert: 1 };
  document.querySelectorAll('[data-toggle]').forEach((cb) => {
    cb.checked = !!keep[cb.getAttribute('data-toggle')];
  });
};
loadAll().catch((e) => setStatus(String(e.message || e)));
