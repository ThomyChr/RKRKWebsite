const API = window.RKRK_REF_LAYERS_API || 'rkrk_api_ref_layers.php';
const KAT_ORDER = ['FZ','HSP','Alert','POI','Gde','GdeLabel','JG','JGLabel','Kanton','KtLabel','Ort','SORA','SUN','WF','DEV'];
const POINT_KATS = ['POI','Ort','GdeLabel','JGLabel','KtLabel'];
const LINE_KATS = ['HSP'];
const REGION_GROUPS = [
  { title: 'Karten-View ALL / leer', keys: ['ALL', 'leer'] },
  { title: 'Missionen / JG-Nummern', keys: ['21','24','25','26','27','28','32','136','137','143','145','148'] },
  { title: 'Kantone / Gebiete', keys: ['Amt','AZ','AG','ZG','ZG1','ZG2','ZG3','ZG4','ZG5','ZG6','LU','LME','SZ'] },
  { title: 'Sonstiges', keys: ['XX','TF'] },
];
const TILE_COLORS = {
  FZ: { fill: 'rgba(255,238,141,0.55)', stroke: '#e6c84a' },
  HSP: { fill: 'rgba(255,80,80,0.18)', stroke: '#ff0000' },
  Alert: { fill: 'rgba(255,165,0,0.40)', stroke: '#ffa500' },
  POI: { fill: 'rgba(230,81,0,0.35)', stroke: '#e65100' },
  Gde: { fill: 'rgba(255,204,102,0.35)', stroke: '#e6a93a' },
  JG: { fill: 'rgba(102,204,255,0.35)', stroke: '#3aa3d6' },
  SORA: { fill: 'rgba(255,136,0,0.30)', stroke: '#ff8800' },
  SUN: { fill: 'rgba(255,238,141,0.55)', stroke: '#e6c84a' },
  WF: { fill: 'rgba(66,165,245,0.40)', stroke: '#1e88e5' },
  Kanton: { fill: 'rgba(250,218,128,0.45)', stroke: '#e0b84a' },
  Ort: { fill: 'rgba(0,128,221,0.28)', stroke: '#0080dd' },
  GdeLabel: { fill: 'rgba(0,128,221,0.22)', stroke: '#0080dd' },
  JGLabel: { fill: 'rgba(0,128,221,0.22)', stroke: '#0080dd' },
  KtLabel: { fill: 'rgba(0,128,221,0.22)', stroke: '#0080dd' },
  DEV: { fill: 'rgba(136,136,136,0.28)', stroke: '#888888' },
};

function tileStyle(key, entry) {
  const fallback = TILE_COLORS[key] || { fill: 'rgba(232,238,244,0.95)', stroke: '#9fb0c2' };
  const stroke = (entry && entry.stroke) || fallback.stroke;
  let fill = (entry && entry.fill) || fallback.fill;
  if (/rgba\(\s*[\d.]+\s*,\s*[\d.]+\s*,\s*[\d.]+\s*,\s*0(?:\.0+)?\s*\)/i.test(String(fill))) {
    fill = fallback.fill;
  }
  return { fill, stroke };
}

let model = { files: [], regions: [], kats: [], settings_path: '', cache: {}, colors: {}, styles: {} };
let map = null;
let editSource = null;
let editLayer = null;
let modifyIx = null;
let selectIx = null;
let translateIx = null;
let snapIx = null;
let drawIx = null;
let mapFile = null;
let mapKat = 'POI';
let mapDirty = false;
let selectedEdit = null;

function setStatus(msg) { document.getElementById('status').textContent = msg || ''; }
function escapeHtml(s) {
  return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function parseRegions(csv) {
  return String(csv || '').split(',').map((s) => s.trim()).filter(Boolean);
}
function regionChecked(fileRegion, key) {
  return parseRegions(fileRegion).includes(key);
}
function listedRegionKeys() {
  const known = new Set(model.regions || []);
  const keys = [];
  const seen = new Set();
  REGION_GROUPS.forEach((g) => {
    g.keys.forEach((k) => {
      if (seen.has(k)) return;
      if (known.has(k) || k === 'ALL' || k === 'leer') {
        seen.add(k);
        keys.push(k);
      }
    });
  });
  (model.regions || []).forEach((r) => {
    if (!seen.has(r)) { seen.add(r); keys.push(r); }
  });
  return keys;
}
function regChipsHtml(idx, f) {
  return `<div class="reg-chips">${listedRegionKeys().map((r) => {
    const on = regionChecked(f.region, r);
    const cls = 'reg-chip' + (on ? ' is-on' : '') + (r === 'ALL' ? ' is-all' : '');
    const t = escapeHtml(r) + (on ? ' an' : ' aus');
    return `<button type="button" class="${cls}" data-reg-chip="${escapeHtml(r)}" data-idx="${idx}" title="${t}" aria-label="Region ${t}" aria-pressed="${on ? 'true' : 'false'}">${escapeHtml(r)}</button>`;
  }).join('')}</div>`;
}
function toggleRegionChip(idx, key) {
  const f = model.files[idx];
  if (!f) return;
  const cb = document.querySelector(`input[data-idx="${idx}"][data-reg="${CSS.escape(key)}"]`);
  if (cb) {
    cb.checked = !cb.checked;
    syncFileFromDom(idx);
  } else {
    const regs = parseRegions(f.region);
    const i = regs.indexOf(key);
    if (i >= 0) regs.splice(i, 1); else regs.push(key);
    f.region = regs.length ? (regs.join(',') + ',') : '';
  }
  refreshFileSummary(idx);
}
function defaultDrawType(kat) {
  if (POINT_KATS.includes(kat)) return 'Point';
  if (LINE_KATS.includes(kat)) return 'LineString';
  return 'Polygon';
}
function katChipHtml(kat) {
  const c = model.cache[kat];
  const st = tileStyle(kat, model.colors[kat]);
  const kb = c && c.cached ? `${(c.bytes / 1024).toFixed(0)} KB` : 'leer';
  return `<span class="kat-chip" style="background:${st.fill};border-color:${st.stroke}">${escapeHtml(kat)} · ${escapeHtml(kb)}</span>`;
}

function refreshFileSummary(idx) {
  const f = model.files[idx];
  const row = document.querySelector(`tr.file-row [data-edit="${idx}"]`);
  const host = row ? row.closest('tr.file-row') : null;
  if (!f || !host) return;
  const miss = f.kml_exists ? 'ok' : 'fehlt';
  const cls = f.kml_exists ? 'ok' : 'missing';
  const kmlEl = host.querySelector('.file-kml');
  if (kmlEl) kmlEl.innerHTML = `<span class="${cls}">${miss}</span>`;
  host.querySelectorAll('[data-reg-chip]').forEach((btn) => {
    const key = btn.getAttribute('data-reg-chip');
    const on = regionChecked(f.region, key);
    btn.classList.toggle('is-on', on);
    btn.setAttribute('aria-pressed', on ? 'true' : 'false');
    const t = key + (on ? ' an' : ' aus');
    btn.title = t;
    btn.setAttribute('aria-label', 'Region ' + t);
  });
}

function sortedFiles() {
  const order = {};
  KAT_ORDER.forEach((k, i) => { order[k] = i; });
  return (model.files || []).slice().sort((a, b) => {
    const ka = order[a.kat] != null ? order[a.kat] : 99;
    const kb = order[b.kat] != null ? order[b.kat] : 99;
    if (ka !== kb) return ka - kb;
    return String(a.name).localeCompare(String(b.name), 'de');
  });
}

function collectRegions(idx) {
  const regs = [];
  document.querySelectorAll(`input[data-idx="${idx}"][data-reg]`).forEach((cb) => {
    if (cb.checked) regs.push(cb.getAttribute('data-reg'));
  });
  return regs.length ? (regs.join(',') + ',') : '';
}

function syncFileFromDom(idx) {
  const f = model.files[idx];
  if (!f) return;
  const katEl = document.querySelector(`select[data-idx="${idx}"][data-kat]`);
  const aktEl = document.querySelector(`input[data-idx="${idx}"][data-aktiv]`);
  if (katEl) f.kat = katEl.value;
  if (aktEl) f.aktiv = !!aktEl.checked;
  f.region = collectRegions(idx);
}

function renderRegionPanel(idx, f) {
  const known = new Set(model.regions || []);
  const opts = (model.kats || []).map((k) => `<option value="${k}" ${f.kat === k ? 'selected' : ''}>${k}</option>`).join('');
  const miss = f.kml_exists ? '<span class="ok">KML ok</span>' : '<span class="missing">KML fehlt</span>';
  const groups = REGION_GROUPS.map((g) => {
    const keys = g.keys.filter((k) => known.has(k) || k === 'ALL' || k === 'leer');
    if (!keys.length) return '';
    const cells = keys.map((r) => {
      const checked = regionChecked(f.region, r) ? 'checked' : '';
      const cls = r === 'ALL' ? ' is-all' : '';
      return `<label class="${cls}"><input type="checkbox" data-idx="${idx}" data-reg="${escapeHtml(r)}" ${checked} />${escapeHtml(r)}</label>`;
    }).join('');
    return `<div class="reg-group"><h4>${escapeHtml(g.title)}</h4><div class="reg-grid">${cells}</div></div>`;
  }).join('');
  const extra = (model.regions || []).filter((r) => !REGION_GROUPS.some((g) => g.keys.includes(r)));
  const extraHtml = extra.length
    ? `<div class="reg-group"><h4>Weitere</h4><div class="reg-grid">${extra.map((r) => {
        const checked = regionChecked(f.region, r) ? 'checked' : '';
        return `<label><input type="checkbox" data-idx="${idx}" data-reg="${escapeHtml(r)}" ${checked} />${escapeHtml(r)}</label>`;
      }).join('')}</div></div>`
    : '';
  return `<div class="reg-panel" data-reg-panel="${idx}">
    <div class="reg-tools">
      <span class="file-kml">${miss}</span>
      <select class="kat" data-idx="${idx}" data-kat>${opts}</select>
      <button type="button" class="btn-ghost" data-reg-preset="check-all" data-idx="${idx}">Alle Regionen</button>
      <button type="button" class="btn-ghost" data-reg-preset="ALL" data-idx="${idx}">Nur ALL</button>
      <button type="button" class="btn-ghost" data-reg-preset="none" data-idx="${idx}">Keine</button>
    </div>
    ${groups}${extraHtml}
  </div>`;
}

function render() {
  const root = document.getElementById('groupsRoot');
  const files = sortedFiles();
  if (!files.length) {
    root.innerHTML = '<p class="muted">Keine Dateien. «Aus XML» oder KML unter <code>kmlfiles/referenzen/</code> ablegen.</p>';
    return;
  }
  const byKat = {};
  files.forEach((f) => {
    const kat = f.kat || 'POI';
    if (!byKat[kat]) byKat[kat] = [];
    byKat[kat].push(f);
  });
  const kats = Object.keys(byKat).sort((a, b) => {
    const ia = KAT_ORDER.indexOf(a); const ib = KAT_ORDER.indexOf(b);
    return (ia < 0 ? 99 : ia) - (ib < 0 ? 99 : ib);
  });
  root.innerHTML = kats.map((kat) => {
    const rows = byKat[kat];
    const activeN = rows.filter((r) => r.aktiv).length;
    const st = tileStyle(kat, model.colors[kat]);
    const body = rows.map((f) => {
      const idx = model.files.findIndex((x) => x.name === f.name);
      const onMap = mapFile && mapFile === f.name ? ' is-on-map' : '';
      return `<tr class="file-row${onMap}" data-name="${escapeHtml(f.name)}">
          <td class="file-name col-name" title="${escapeHtml(f.name)}">${escapeHtml(f.name)}</td>
          <td class="col-aktiv"><label class="aktiv"><input type="checkbox" data-idx="${idx}" data-aktiv ${f.aktiv ? 'checked' : ''} /></label></td>
          <td class="col-regs">${regChipsHtml(idx, f)}</td>
          <td class="col-edit"><button type="button" class="icon-btn icon-sm btn-edit" data-edit="${idx}" aria-expanded="false" title="Regionen-Presets" aria-label="Regionen-Presets">&#127991;</button></td>
          <td class="col-map"><button type="button" class="icon-btn icon-sm btn-map" data-map="${idx}" ${f.kml_exists ? '' : 'disabled'} title="Auf der Karte bearbeiten" aria-label="Auf der Karte bearbeiten">&#127757;</button></td>
        </tr>
        <tr class="file-edit-row">
          <td colspan="5">${renderRegionPanel(idx, f)}</td>
        </tr>`;
    }).join('');
    return `<section class="kat-block" data-kat-block="${escapeHtml(kat)}">
      <div class="kat-head" style="border-left-color:${st.stroke};background:#eef4fa">
        <div class="kat-head-title">${katChipHtml(kat)}<span class="meta">${rows.length} Dateien · ${activeN} aktiv</span></div>
        <div class="kat-actions">
          <button type="button" class="icon-btn icon-sm btn-ghost" data-kat-all="${escapeHtml(kat)}" data-on="1" title="Alle in dieser Gruppe aktiv" aria-label="Alle in dieser Gruppe aktiv">&#10003;</button>
          <button type="button" class="icon-btn icon-sm btn-ghost" data-kat-all="${escapeHtml(kat)}" data-on="0" title="Alle in dieser Gruppe aus" aria-label="Alle in dieser Gruppe aus">&#10005;</button>
        </div>
      </div>
      <table class="file-table">
        <thead>
          <tr>
            <th class="col-name">Datei</th>
            <th class="col-aktiv">Aktiv</th>
            <th class="col-regs">Regionen</th>
            <th class="col-edit"></th>
            <th class="col-map"></th>
          </tr>
        </thead>
        <tbody>${body}</tbody>
      </table>
    </section>`;
  }).join('');

  root.querySelectorAll('[data-kat-all]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const kat = btn.getAttribute('data-kat-all');
      const on = btn.getAttribute('data-on') === '1';
      root.querySelectorAll(`[data-kat-block="${kat}"] [data-aktiv]`).forEach((cb) => {
        cb.checked = on;
        const i = Number(cb.getAttribute('data-idx'));
        if (model.files[i]) model.files[i].aktiv = on;
      });
    });
  });
  root.querySelectorAll('[data-reg-preset]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const idx = Number(btn.getAttribute('data-idx'));
      const preset = btn.getAttribute('data-reg-preset');
      const panel = root.querySelector(`[data-reg-panel="${idx}"]`);
      if (!panel) return;
      panel.querySelectorAll('[data-reg]').forEach((cb) => {
        if (preset === 'none') cb.checked = false;
        else if (preset === 'check-all') cb.checked = true;
        else cb.checked = cb.getAttribute('data-reg') === preset;
      });
      syncFileFromDom(idx);
      refreshFileSummary(idx);
    });
  });
  root.querySelectorAll('[data-edit]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const idx = Number(btn.getAttribute('data-edit'));
      const block = btn.closest('tr.file-row');
      if (!block) return;
      const open = !block.classList.contains('is-editing');
      root.querySelectorAll('tr.file-row.is-editing').forEach((el) => {
        el.classList.remove('is-editing');
        const b = el.querySelector('[data-edit]');
        if (b) {
          b.setAttribute('aria-expanded', 'false');
          b.innerHTML = '&#127991;';
          b.title = 'Regionen';
          b.setAttribute('aria-label', 'Regionen');
        }
      });
      if (open) {
        block.classList.add('is-editing');
        btn.setAttribute('aria-expanded', 'true');
        btn.innerHTML = '&#10003;';
        btn.title = 'Regionen schliessen';
        btn.setAttribute('aria-label', 'Regionen schliessen');
      }
    });
  });
  root.querySelectorAll('[data-map]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const idx = Number(btn.getAttribute('data-map'));
      const f = model.files[idx];
      if (!f) return;
      openKmlOnMap(f).catch((e) => setStatus(String(e.message || e)));
    });
  });
  root.querySelectorAll('[data-kat], [data-aktiv], [data-reg]').forEach((el) => {
    el.addEventListener('change', () => {
      const idx = Number(el.getAttribute('data-idx'));
      syncFileFromDom(idx);
      refreshFileSummary(idx);
    });
  });
  root.querySelectorAll('[data-reg-chip]').forEach((btn) => {
    btn.addEventListener('click', () => {
      toggleRegionChip(Number(btn.getAttribute('data-idx')), btn.getAttribute('data-reg-chip'));
    });
  });
}

function collectFromDom() {
  return model.files.map((f, idx) => {
    const katEl = document.querySelector(`select[data-idx="${idx}"][data-kat]`);
    const aktEl = document.querySelector(`input[data-idx="${idx}"][data-aktiv]`);
    return {
      name: f.name,
      region: collectRegions(idx) || f.region,
      kat: katEl ? katEl.value : f.kat,
      aktiv: aktEl ? !!aktEl.checked : !!f.aktiv,
    };
  });
}

function clamp01(v, fallback) {
  const n = Number(v);
  if (!Number.isFinite(n)) return fallback;
  return Math.max(0, Math.min(1, n));
}
function featureEditColors(feature) {
  const group = model.colors[mapKat] || TILE_COLORS[mapKat] || {};
  let stroke = group.stroke || '#355b7c';
  let fill = group.fill || 'rgba(53,91,124,0.22)';
  let width = group.width != null && Number(group.width) > 0 ? Number(group.width) : 2;
  let opacity = group.opacity != null ? clamp01(group.opacity, 1) : 1;
  const styleUrl = String((feature && feature.get('styleUrl')) || '').replace(/^#/, '').trim();
  const s = styleUrl && model.styles ? model.styles[styleUrl] : null;
  if (s) {
    if (s.stroke) stroke = s.stroke;
    if (s.fill) fill = s.fill;
    if (s.width != null && Number(s.width) > 0) width = Number(s.width);
    if (s.opacity != null) opacity = clamp01(s.opacity, opacity);
  }
  return { stroke, fill, width, opacity };
}
function editStyle(feature) {
  const cols = featureEditColors(feature);
  const g = feature.getGeometry();
  const type = g ? g.getType() : '';
  const selected = selectIx && selectIx.getFeatures().getArray().indexOf(feature) >= 0;
  if (type === 'Point' || type === 'MultiPoint') {
    return new ol.style.Style({
      image: new ol.style.Circle({
        radius: selected ? 8 : 6,
        fill: new ol.style.Fill({ color: cols.stroke }),
        stroke: new ol.style.Stroke({ color: '#fff', width: selected ? 2.5 : 1.5 }),
      }),
      text: new ol.style.Text({
        text: String(feature.get('name') || ''),
        offsetY: -12,
        font: '11px Arial',
        fill: new ol.style.Fill({ color: cols.stroke }),
        stroke: new ol.style.Stroke({ color: 'rgba(255,255,255,0.95)', width: 3 }),
      }),
    });
  }
  const isLine = type === 'LineString' || type === 'MultiLineString';
  const baseW = cols.width || (isLine ? 3.2 : 2.4);
  return new ol.style.Style({
    stroke: new ol.style.Stroke({
      color: cols.stroke,
      width: selected ? baseW + 1.6 : baseW,
    }),
    fill: (type === 'Polygon' || type === 'MultiPolygon')
      ? new ol.style.Fill({ color: cols.fill })
      : undefined,
  });
}

function markDirty() {
  mapDirty = true;
  const lab = document.getElementById('mapEditFile');
  if (lab && mapFile && lab.textContent.indexOf('*') < 0) lab.textContent = mapFile + ' *';
}

function stopDraw() {
  if (drawIx && map) {
    map.removeInteraction(drawIx);
    drawIx = null;
  }
  if (modifyIx) modifyIx.setActive(true);
  if (selectIx) selectIx.setActive(true);
  if (translateIx) translateIx.setActive(true);
  const addBtn = document.getElementById('mapAddBtn');
  addBtn.innerHTML = '&#10010;';
  addBtn.title = 'Neues Objekt zeichnen';
  addBtn.setAttribute('aria-label', 'Neues Objekt zeichnen');
}

function setSelectedFeature(f) {
  selectedEdit = f || null;
  const nameEl = document.getElementById('mapFeatName');
  nameEl.value = f ? String(f.get('name') || '') : '';
  if (editSource) editSource.changed();
}

function initMap() {
  editSource = new ol.source.Vector();
  editLayer = new ol.layer.Vector({
    source: editSource,
    style: (f) => editStyle(f),
    zIndex: 20,
  });
  map = new ol.Map({
    target: 'map',
    layers: [
      new ol.layer.Tile({
        source: new ol.source.XYZ({
          url: 'https://wmts.geo.admin.ch/1.0.0/ch.swisstopo.pixelkarte-farbe/default/current/3857/{z}/{x}/{y}.jpeg',
        }),
      }),
      editLayer,
    ],
    view: new ol.View({ center: ol.proj.fromLonLat([8.54, 47.28]), zoom: 10 }),
  });
  selectIx = new ol.interaction.Select({
    layers: [editLayer],
    hitTolerance: 6,
  });
  selectIx.on('select', (evt) => {
    setSelectedFeature(evt.selected[0] || null);
  });
  modifyIx = new ol.interaction.Modify({ source: editSource });
  modifyIx.on('modifyend', markDirty);
  translateIx = new ol.interaction.Translate({
    features: selectIx.getFeatures(),
  });
  translateIx.on('translateend', markDirty);
  snapIx = new ol.interaction.Snap({ source: editSource });
  map.addInteraction(selectIx);
  map.addInteraction(modifyIx);
  map.addInteraction(translateIx);
  map.addInteraction(snapIx);
  modifyIx.setActive(false);
  selectIx.setActive(false);
  translateIx.setActive(false);
}

async function openKmlOnMap(file) {
  if (mapDirty && mapFile && mapFile !== file.name) {
    if (!confirm('Ungespeicherte KML-Änderungen verwerfen?')) return;
  }
  stopDraw();
  const r = await fetch(`${API}?action=kml_geojson&file=${encodeURIComponent(file.name)}`, { cache: 'no-store' });
  const data = await r.json();
  if (!data.ok) throw new Error(data.message || data.error || 'kml_load_failed');
  const format = new ol.format.GeoJSON();
  const features = format.readFeatures(data, {
    dataProjection: 'EPSG:4326',
    featureProjection: map.getView().getProjection(),
  });
  editSource.clear();
  editSource.addFeatures(features);
  mapFile = file.name;
  mapKat = data.kat || file.kat || 'POI';
  mapDirty = false;
  selectedEdit = null;
  document.getElementById('mapFeatName').value = '';
  document.getElementById('mapEditFile').textContent = file.name;
  document.getElementById('mapDrawType').value = defaultDrawType(mapKat);
  document.body.classList.add('is-map-editing');
  setMapOpen(true);
  modifyIx.setActive(true);
  selectIx.setActive(true);
  translateIx.setActive(true);
  setStatus(`${file.name}: ${features.length} Objekte auf der Karte. Ecken ziehen, dann Speichern.`);
  document.querySelectorAll('tr.file-row').forEach((el) => {
    el.classList.toggle('is-on-map', el.getAttribute('data-name') === file.name);
  });
}

function closeMapEdit(force) {
  if (!force && mapDirty && !confirm('Ungespeicherte KML-Änderungen verwerfen?')) return;
  stopDraw();
  editSource.clear();
  mapFile = null;
  mapDirty = false;
  selectedEdit = null;
  document.body.classList.remove('is-map-editing');
  setMapOpen(false);
  modifyIx.setActive(false);
  selectIx.setActive(false);
  translateIx.setActive(false);
  document.querySelectorAll('tr.file-row.is-on-map').forEach((el) => el.classList.remove('is-on-map'));
  setStatus('Kartenbearbeitung beendet.');
}

function startDraw() {
  if (!mapFile) return;
  if (drawIx) {
    stopDraw();
    return;
  }
  const type = document.getElementById('mapDrawType').value || 'Polygon';
  modifyIx.setActive(false);
  selectIx.setActive(false);
  translateIx.setActive(false);
  drawIx = new ol.interaction.Draw({ source: editSource, type });
  drawIx.on('drawend', (evt) => {
    evt.feature.set('name', 'Neu');
    evt.feature.set('kat', mapKat);
    markDirty();
    stopDraw();
    selectIx.getFeatures().clear();
    selectIx.getFeatures().push(evt.feature);
    setSelectedFeature(evt.feature);
  });
  map.addInteraction(drawIx);
  const addBtn = document.getElementById('mapAddBtn');
  addBtn.innerHTML = '&#10005;';
  addBtn.title = 'Zeichnen abbrechen';
  addBtn.setAttribute('aria-label', 'Zeichnen abbrechen');
  setStatus('Zeichnen: Klicks setzen Punkte, Doppelklick beendet.');
}

function deleteSelected() {
  if (!selectedEdit) {
    setStatus('Kein Objekt gewählt.');
    return;
  }
  editSource.removeFeature(selectedEdit);
  selectIx.getFeatures().clear();
  setSelectedFeature(null);
  markDirty();
}

async function saveMapKml() {
  if (!mapFile) return;
  const format = new ol.format.GeoJSON();
  const gj = format.writeFeaturesObject(editSource.getFeatures(), {
    dataProjection: 'EPSG:4326',
    featureProjection: map.getView().getProjection(),
  });
  setStatus('Speichere KML…');
  const r = await fetch(API, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'save_kml', file: mapFile, features: gj.features || [] }),
  });
  const data = await r.json();
  if (!data.ok) throw new Error(data.message || data.error || 'save_kml_failed');
  mapDirty = false;
  document.getElementById('mapEditFile').textContent = mapFile;
  setStatus(`Gespeichert: ${data.path || mapFile}.kml · ${data.count} Objekte · Cache ${mapKat} aktualisiert.`);
  await loadCache();
  render();
}

async function loadCache() {
  const [gRes, cRes] = await Promise.all([
    fetch(`${API}?action=groups`, { cache: 'no-store' }),
    fetch(`${API}?action=colors`, { cache: 'no-store' }),
  ]);
  const data = await gRes.json();
  if (!data.ok) return;
  model.cache = {};
  (data.cache || []).forEach((c) => { model.cache[c.key] = c; });
  try {
    const cData = await cRes.json();
    if (cData && cData.ok) {
      if (cData.groups) model.colors = cData.groups;
      if (cData.styles) model.styles = cData.styles;
    }
  } catch (_) { /* defaults */ }
}

async function loadSettings() {
  setStatus('Lade Einstellungen…');
  const r = await fetch(`${API}?action=settings`, { cache: 'no-store' });
  const data = await r.json();
  if (!data.ok) throw new Error(data.message || data.error || 'load_failed');
  model.files = data.files || [];
  model.regions = data.regions || [];
  model.kats = data.kats || [];
  model.settings_path = data.settings_path || '';
  await loadCache();
  render();
  document.getElementById('sourceInfo').textContent =
    'KML-Quellen: ' + ((data.sources || []).join(' · ') || '—');
  setStatus(`${model.files.length} Quellen · Reg = Regionen · Karte = auf der Karte bearbeiten.`);
}

async function saveSettings() {
  const files = collectFromDom();
  setStatus('Speichere JSON…');
  const r = await fetch(API, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'save_settings', files }),
  });
  const data = await r.json();
  if (!data.ok) throw new Error(data.message || data.error || 'save_failed');
  model.files = data.files || files;
  render();
  setStatus('Gespeichert: rkrk_kmlfile_settings.json (Regionen pro Quelle erhalten).');
}

async function resetSettings() {
  if (!confirm('Regionen aus rkrk_kmlfile_settings.xml wiederherstellen?')) return;
  const r = await fetch(`${API}?action=reset_settings`, { cache: 'no-store' });
  const data = await r.json();
  if (!data.ok) throw new Error(data.message || data.error || 'reset_failed');
  model.files = data.files || [];
  render();
  setStatus(`Wiederhergestellt von ${data.seeded_from || 'Mission-XML'}.`);
  await loadCache();
}

async function buildLayers() {
  await saveSettings();
  setStatus('Erzeuge GeoJSON-Layer…');
  const r = await fetch(`${API}?action=build_from_kml`, { cache: 'no-store' });
  const data = await r.json();
  const lines = [];
  if (data.ok === false && data.error) lines.push('Fehler: ' + (data.message || data.error));
  (data.processed || []).forEach((p) => {
    lines.push(p.ok ? `OK ${p.name}: ${p.count} → ${p.kat}` : `FEHLER ${p.name}: ${p.error || 'Fehler'}`);
  });
  (data.groups || []).forEach((g) => lines.push(`→ ${g.group}.geojson (${g.count} Features)`));
  (data.errors || []).forEach((e) => lines.push(`! ${e}`));
  if (!lines.length) lines.push(data.ok ? 'Fertig.' : 'Build fehlgeschlagen.');
  setStatus(lines.join('\n'));
  await loadCache();
  render();
}

document.getElementById('mapFeatName').addEventListener('change', () => {
  if (!selectedEdit) return;
  selectedEdit.set('name', document.getElementById('mapFeatName').value);
  markDirty();
  if (editSource) editSource.changed();
});
document.getElementById('mapAddBtn').onclick = startDraw;
document.getElementById('mapDelBtn').onclick = deleteSelected;
document.getElementById('mapSaveBtn').onclick = () => saveMapKml().catch((e) => setStatus(String(e.message || e)));
document.getElementById('mapCloseBtn').onclick = () => closeMapEdit(false);
document.addEventListener('keydown', (ev) => {
  if (ev.key !== 'Delete' && ev.key !== 'Backspace') return;
  if (!document.body.classList.contains('is-map-open')) return;
  const t = ev.target;
  if (t && (t.tagName === 'INPUT' || t.tagName === 'TEXTAREA' || t.tagName === 'SELECT')) return;
  ev.preventDefault();
  deleteSelected();
});

function setMapOpen(open) {
  document.body.classList.toggle('is-map-open', !!open);
  const btn = document.getElementById('mapToggleBtn');
  btn.innerHTML = open ? '&lt;&lt;' : '&gt;&gt;';
  btn.title = open ? 'Karte ausblenden' : 'Karte einblenden';
  btn.setAttribute('aria-label', btn.title);
  btn.setAttribute('aria-expanded', open ? 'true' : 'false');
  requestAnimationFrame(() => {
    if (!map) return;
    map.updateSize();
    if (open && editSource && editSource.getFeatures().length) {
      const extent = editSource.getExtent();
      if (extent && ol.extent.getWidth(extent) > 0) {
        map.getView().fit(extent, { padding: [48, 48, 48, 48], duration: 250, maxZoom: 16 });
      }
    }
  });
}
document.getElementById('mapToggleBtn').onclick = () => {
  const open = !document.body.classList.contains('is-map-open');
  setMapOpen(open);
  if (open && !mapFile) setStatus('Karte ein. Datei mit Karten-Icon laden.');
};

document.getElementById('reloadBtn').onclick = () => loadSettings().catch((e) => setStatus(String(e.message || e)));
document.getElementById('saveBtn').onclick = () => saveSettings().catch((e) => setStatus(String(e.message || e)));
document.getElementById('resetBtn').onclick = () => resetSettings().catch((e) => setStatus(String(e.message || e)));
document.getElementById('buildBtn').onclick = () => buildLayers().catch((e) => setStatus(String(e.message || e)));
initMap();
loadSettings().catch((e) => setStatus(String(e.message || e)));
