/**
 * rkrk_map27 — OpenLayers map with SwissTopo WMTS/WMS overlays + RKRK KML.
 */
(function (global) {
  'use strict';

  function selectedPdfScaleDenom() {
    try {
      const sel = document.getElementById('mapPdfScaleSelect');
      const n = sel ? Number(sel.value) : 0;
      return (n === 10000 || n === 25000 || n === 50000) ? n : 0;
    } catch (_) {
      return 0;
    }
  }
  global.selectedPdfScaleDenom = selectedPdfScaleDenom;

  const cfg = global.RKRK_MAP27 || {};
  const STORAGE_OVERLAYS = 'rkrk_map27_overlays_v1';
  const OBSTACLE_LAYER_ID = 'ch.bazl.luftfahrthindernis';
  const OBSTACLE_WMS_LAYERS = 'ch.bazl.luftfahrthindernis_line,ch.bazl.luftfahrthindernis_point,ch.bazl.luftfahrthindernis_poly';

  const overlayDefinitions = {
    electrical: {
      label: 'Elektrische Anlagen',
      fixedLayerIds: ['ch.bfe.elektrische-anlagen_ueber_36'],
    },
    boundaries: {
      label: 'Kantons, Gemeindegrenzen',
      fixedLayerIds: ['ch.swisstopo.swissboundaries3d-gemeinde-flaeche.fill'],
    },
    hunting: {
      label: 'Jagdbanngebiete AuLaV',
      fixedLayerIds: ['ch.bafu.schutzgebiete-aulav_jagdbanngebiete'],
    },
    rpas: {
      label: 'RPAS Karte',
      fixedLayerIds: ['ch.bazl.einschraenkungen-drohnen'],
    },
    uasZones: {
      label: 'UAS Aktivitätszonen',
      fixedLayerIds: ['ch.bazl.uas-aktivitaetszonen'],
    },
    aulavWohngebiete: {
      label: 'Wohngebiete nach AuLaV',
      fixedLayerIds: ['ch.bazl.wohngebiete-aulav'],
    },
    alti3dRelief: {
      label: 'swissALTI3D Relief',
      fixedLayerIds: ['ch.swisstopo.swissalti3d-reliefschattierung'],
    },
    surface3dRelief: {
      label: 'swissSURFACE3D Relief',
      fixedLayerIds: ['ch.swisstopo.swisssurface3d-reliefschattierung-multidirektional'],
    },
    dabs: {
      label: 'DABS bis 200 m AGL',
      kind: 'dabs',
    },
    obstacles: {
      label: 'Luftfahrthindernisse',
      fixedLayerIds: [OBSTACLE_LAYER_ID],
    },
    landregister: {
      label: 'Grundbuchinformationen',
      fixedLayerIds: ['ch.swisstopo-vd.geometa-grundbuch'],
    },
    cadastralwebmap: {
      label: 'CadastralWebMap',
      fixedLayerIds: ['ch.kantone.cadastralwebmap-farbe'],
    },
    nature: {
      label: 'Naturschutzgebiete',
      fixedLayerIds: ['ch.pronatura.naturschutzgebiete'],
    },
    roadnames: {
      label: 'Strassennamen',
      fixedLayerIds: ['ch.astra.hauptstrassennetz', 'ch.swisstopo.amtliches-strassenverzeichnis'],
    },
    cableways: {
      label: 'Seilbahnen',
      fixedLayerIds: ['ch.swisstopo.bahnen-winter', 'ch.swisstopo.swisstlm3d-uebrigerverkehr'],
    },
    wanderwege: {
      label: 'Wanderwege',
      fixedLayerIds: ['ch.swisstopo.swisstlm3d-wanderwege'],
    },
    tlm: {
      label: 'SwissTLM',
      fixedLayerIds: ['ch.swisstopo.swisstlm3d-karte-farbe'],
    },
  };

  const baseLayerIds = {
    F: 'ch.swisstopo.pixelkarte-farbe',
    S: 'ch.swisstopo.swissimage',
    G: 'ch.swisstopo.pixelkarte-grau',
    M: 'ch.swisstopo.pixelkarte-farbe',
  };

  const overlayLayersByKey = {};
  const overlayApplyTokenByKey = {};
  let map = null;
  let baseLayer = null;
  let tlmLayer = null;
  let kmlLayer = null;
  let popupOverlay = null;
  let obstacleVectorSource = null;
  let obstacleRefreshTimer = null;
  let obstacleRefreshToken = 0;
  let dabsVectorSource = null;
  let dabsMeta = null;
  let uasCtl = null;
  let uasLabelCtl = null;
  let obstInfoCtl = null;

  function $(sel, root) {
    return (root || document).querySelector(sel);
  }

  function readStoredOverlays() {
    try {
      const raw = localStorage.getItem(STORAGE_OVERLAYS);
      if (!raw) return {};
      const parsed = JSON.parse(raw);
      return parsed && typeof parsed === 'object' ? parsed : {};
    } catch (_) {
      return {};
    }
  }

  function writeStoredOverlays(state) {
    try {
      localStorage.setItem(STORAGE_OVERLAYS, JSON.stringify(state || {}));
    } catch (_) { /* noop */ }
  }

  function getOverlayOpacity(key) {
    const el = document.querySelector('[data-overlay-opacity-key="' + key + '"]');
    if (!el) return 1;
    const n = Number(el.value);
    if (!Number.isFinite(n)) return 1;
    return Math.max(0, Math.min(1, n / 100));
  }

  function setOpacityLabel(key) {
    const el = document.querySelector('[data-overlay-opacity-label="' + key + '"]');
    const range = document.querySelector('[data-overlay-opacity-key="' + key + '"]');
    if (el && range) el.textContent = String(range.value) + '%';
  }

  function createBaseLayer(layerId) {
    return new ol.layer.Tile({
      source: new ol.source.XYZ({
        url: 'https://wmts.geo.admin.ch/1.0.0/' + layerId + '/default/current/3857/{z}/{x}/{y}.jpeg',
        attributions: '© <a href="https://www.swisstopo.admin.ch/" target="_blank" rel="noopener">swisstopo</a>',
        maxZoom: 19,
        crossOrigin: 'anonymous',
      }),
      zIndex: 0,
    });
  }

  function createGeoAdminOverlayLayer(layerId, opacity) {
    return new ol.layer.Tile({
      source: new ol.source.TileWMS({
        url: 'https://wms.geo.admin.ch/',
        params: {
          LAYERS: layerId === OBSTACLE_LAYER_ID ? OBSTACLE_WMS_LAYERS : layerId,
          FORMAT: 'image/png',
          TRANSPARENT: true,
        },
        crossOrigin: 'anonymous',
        attributions: 'Map data: <a href="https://www.swisstopo.admin.ch/" target="_blank" rel="noopener">swisstopo</a>',
      }),
      opacity: Number.isFinite(opacity) ? opacity : 1,
      zIndex: 40,
    });
  }

  function createGeoAdminWmtsPngLayer(layerId, opacity, zIndex) {
    return new ol.layer.Tile({
      source: new ol.source.XYZ({
        url: 'https://wmts.geo.admin.ch/1.0.0/' + layerId + '/default/current/3857/{z}/{x}/{y}.png',
        attributions: 'Map data: <a href="https://www.swisstopo.admin.ch/" target="_blank" rel="noopener">swisstopo</a>',
        maxZoom: 19,
        crossOrigin: 'anonymous',
      }),
      opacity: Number.isFinite(opacity) ? opacity : 1,
      zIndex: Number.isFinite(Number(zIndex)) ? Number(zIndex) : 45,
    });
  }

  function formatObstacleHeight(attrs) {
    if (!attrs || typeof attrs !== 'object') return '';
    const aglRaw = attrs.maxheightagl != null ? attrs.maxheightagl : attrs.maxHeightAgl;
    const amslRaw = attrs.topelevationamsl != null ? attrs.topelevationamsl : attrs.topElevationAmsl;
    const agl = aglRaw != null && aglRaw !== '' ? String(aglRaw).replace(/\.0$/, '') : '';
    const amsl = amslRaw != null && amslRaw !== '' ? String(amslRaw).replace(/\.0$/, '') : '';
    if (agl && amsl) return agl + ' m / ' + amsl + ' m ü.M.';
    if (agl) return agl + ' m';
    if (amsl) return amsl + ' m ü.M.';
    return '';
  }

  function geometryFromIdentify(g) {
    if (!g) return null;
    if (Number.isFinite(Number(g.x)) && Number.isFinite(Number(g.y))) {
      return new ol.geom.Point([Number(g.x), Number(g.y)]);
    }
    if (Array.isArray(g.paths) && g.paths.length) {
      return g.paths.length === 1
        ? new ol.geom.LineString(g.paths[0])
        : new ol.geom.MultiLineString(g.paths);
    }
    if (Array.isArray(g.rings) && g.rings.length) {
      return new ol.geom.Polygon(g.rings);
    }
    return null;
  }

  function obstacleHeightsEnabled() {
    const el = document.getElementById('obstacleHeights');
    return el ? !!el.checked : true;
  }

  function styleObstacleFeature(feature, resolution) {
    const attrs = feature.get('attributes') || {};
    const label = formatObstacleHeight(attrs);
    const type = feature.getGeometry() ? feature.getGeometry().getType() : '';
    const showLabel = obstacleHeightsEnabled() && !!label && resolution < 20;
    const text = showLabel
      ? new ol.style.Text({
          text: label,
          font: 'bold 11px sans-serif',
          fill: new ol.style.Fill({ color: '#1a1a1a' }),
          stroke: new ol.style.Stroke({ color: 'rgba(255,255,255,0.92)', width: 3 }),
          offsetY: type.indexOf('Point') >= 0 ? -14 : -8,
        })
      : undefined;
    return new ol.style.Style({
      stroke: new ol.style.Stroke({ color: 'rgba(0,0,0,0)', width: 8 }),
      fill: new ol.style.Fill({ color: 'rgba(0,0,0,0)' }),
      image: new ol.style.Circle({
        radius: 10,
        fill: new ol.style.Fill({ color: 'rgba(0,0,0,0)' }),
        stroke: new ol.style.Stroke({ color: 'rgba(0,0,0,0)', width: 0 }),
      }),
      text: text,
    });
  }

  function styleDabsFeature(feature, resolution) {
    const isPoly = String(feature.get('shape') || '') === 'polygon';
    const name = String(feature.get('area') || feature.get('notam') || feature.get('name') || '');
    const res = Number(resolution);
    const showLabel = !!name && Number.isFinite(res) && res < 140;
    return new ol.style.Style({
      stroke: new ol.style.Stroke({
        color: '#8b0000',
        width: isPoly ? 3.4 : 2.8,
        lineJoin: 'round',
        lineDash: isPoly ? undefined : [7, 5],
      }),
      fill: new ol.style.Fill({ color: isPoly ? 'rgba(180,0,0,0.36)' : 'rgba(180,0,0,0.26)' }),
      text: showLabel
        ? new ol.style.Text({
            text: name,
            font: 'bold 13px sans-serif',
            fill: new ol.style.Fill({ color: '#7a0000' }),
            stroke: new ol.style.Stroke({ color: 'rgba(255,255,255,0.95)', width: 4 }),
            overflow: true,
          })
        : undefined,
    });
  }

  function createDabsLayers(opacity) {
    dabsVectorSource = new ol.source.Vector();
    const vector = new ol.layer.Vector({
      source: dabsVectorSource,
      style: styleDabsFeature,
      zIndex: 95,
      opacity: Number.isFinite(opacity) ? opacity : 1,
      declutter: false,
    });
    if (typeof vector.set === 'function') vector.set('overlayKey', 'dabs');
    vector.set('rkrkDabsVector', true);
    return [vector];
  }

  function dabsCircleGeometry(lon, lat, radiusM) {
    const r = Number(radiusM);
    if (!Number.isFinite(lon) || !Number.isFinite(lat) || !Number.isFinite(r) || r <= 0) {
      return null;
    }
    if (ol.geom && ol.geom.Polygon && typeof ol.geom.Polygon.circular === 'function') {
      try {
        const poly = ol.geom.Polygon.circular([lon, lat], r, 64);
        poly.transform('EPSG:4326', 'EPSG:3857');
        return poly;
      } catch (_) { /* fallback */ }
    }
    const center = ol.proj.fromLonLat([lon, lat]);
    const latM = Math.cos((lat * Math.PI) / 180);
    const radius3857 = latM > 0.2 ? (r / latM) : r;
    return ol.geom.Polygon.fromCircle(new ol.geom.Circle(center, radius3857), 64);
  }

  function dabsGeometryFromRow(row) {
    const geomObj = (row && row.geometry) || {};
    const props = (row && row.properties) || {};
    const type = String(geomObj.type || '').toLowerCase();
    if (type === 'polygon' && Array.isArray(geomObj.coordinates) && geomObj.coordinates.length) {
      try {
        const poly = new ol.geom.Polygon(geomObj.coordinates);
        poly.transform('EPSG:4326', 'EPSG:3857');
        return poly;
      } catch (_) { /* fallback circle */ }
    }
    const coords = Array.isArray(geomObj.coordinates) ? geomObj.coordinates : [];
    const lon = Number(coords.length ? coords[0] : props.lon);
    const lat = Number(coords.length > 1 ? coords[1] : props.lat);
    return dabsCircleGeometry(lon, lat, Number(props.radius_m));
  }

  function loadDabsOverlay() {
    if (!dabsVectorSource) return;
    const url = String(cfg.dabsUrl || 'rkrk_dabs.php') + (String(cfg.dabsUrl || '').indexOf('?') >= 0 ? '&' : '?') + 'day=today';
    setStatus('DABS wird geladen …');
    fetch(url, { cache: 'no-store' }).then(function (res) {
      if (!res.ok) throw new Error('HTTP ' + res.status);
      return res.json();
    }).then(function (payload) {
      if (!dabsVectorSource) return;
      dabsVectorSource.clear();
      dabsMeta = null;
      if (!payload || payload.ok === false) {
        setStatus('DABS: ' + ((payload && payload.error) || 'keine Daten'));
        return;
      }
      if (payload.available === false) {
        setStatus((payload.meta && payload.meta.message) || 'DABS noch nicht veröffentlicht');
        return;
      }
      const meta = payload.meta || {};
      dabsMeta = meta;
      const rows = Array.isArray(payload.features) ? payload.features : [];
      const features = [];
      rows.forEach(function (row) {
        const props = (row && row.properties) || {};
        const geom = dabsGeometryFromRow(row);
        if (!geom) return;
        const f = new ol.Feature({ geometry: geom });
        Object.keys(props).forEach(function (k) { f.set(k, props[k]); });
        f.set('name', props.area || props.notam || props.name || 'DABS');
        f.set('description', props.remark || '');
        f.set('dabsDate', meta.date || '');
        f.set('dabsVersion', meta.version || '');
        features.push(f);
      });
      if (features.length) dabsVectorSource.addFeatures(features);
      const ver = [meta.date, meta.version].filter(Boolean).join(' · ');
      const skipped = Number(meta.skipped_high) || 0;
      const polyN = Number(meta.polygons);
      const circN = Number(meta.circles);
      let shapeBit = features.length + ' Gebiete bis 200 m AGL';
      if (Number.isFinite(polyN) && Number.isFinite(circN) && (polyN + circN) > 0) {
        shapeBit = polyN + ' Polygone, ' + circN + ' Kreise bis 200 m AGL';
      }
      setStatus('DABS ' + (ver || 'heute') + ': ' + shapeBit
        + (skipped ? ' (' + skipped + ' höher ausgeblendet)' : ''));
    }).catch(function (err) {
      if (!dabsVectorSource) return;
      setStatus('DABS konnte nicht geladen werden');
    });
  }

  function escapePopupText(s) {
    return String(s || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function dabsVersionLine(feature) {
    const date = String((feature && feature.get && feature.get('dabsDate')) || (dabsMeta && dabsMeta.date) || '').trim();
    let ver = String((feature && feature.get && feature.get('dabsVersion')) || (dabsMeta && dabsMeta.version) || '').trim();
    const verNum = ver.match(/(\d+)/);
    if (verNum) {
      ver = 'Version ' + verNum[1];
    } else if (ver && !/^version\b/i.test(ver)) {
      ver = 'Version ' + ver.replace(/^[:\s]+/, '');
    }
    const parts = [];
    if (date) parts.push(date);
    if (ver) parts.push(ver);
    if (!parts.length) return '';
    return 'DABS ' + parts.join(' ');
  }

  function dabsPopupHtml(feature) {
    const name = escapePopupText(feature.get('name') || 'DABS');
    const validity = escapePopupText(feature.get('validity') || '');
    const lower = escapePopupText(feature.get('lower') || '');
    const upper = escapePopupText(feature.get('upper') || '');
    const radius = escapePopupText(feature.get('radius') || '');
    const remark = escapePopupText(feature.get('remark') || feature.get('description') || '');
    const notam = escapePopupText(feature.get('notam') || '');
    let html = '<div class="pop-title">' + name + '</div>';
    const bits = [];
    if (validity) bits.push(validity + ' UTC');
    if (lower || upper) bits.push((lower || '?') + ' – ' + (upper || '?'));
    if (radius) bits.push(radius);
    if (bits.length) html += '<div class="pop-desc">' + bits.join(' · ') + '</div>';
    if (remark) html += '<div class="pop-desc"><b>Bemerkung</b><br>' + remark + '</div>';
    if (notam) html += '<div class="pop-desc">NOTAM ' + notam + '</div>';
    html += '<div class="pop-desc"><i>Nur Segment GND–200 m AGL. Quelle: Skyguide DABS, ersetzt keine NOTAMs.</i></div>';
    const verLine = dabsVersionLine(feature);
    if (verLine) {
      html += '<div class="pop-desc">' + escapePopupText(verLine) + '</div>';
    }
    return html;
  }

  function createObstacleLayers(opacity) {
    obstacleVectorSource = new ol.source.Vector();
    const vector = new ol.layer.Vector({
      source: obstacleVectorSource,
      style: styleObstacleFeature,
      zIndex: 96,
      opacity: Number.isFinite(opacity) ? opacity : 1,
      declutter: true,
    });
    if (typeof vector.set === 'function') vector.set('overlayKey', 'obstacles');
    vector.set('rkrkObstacleVector', true);
    return [vector];
  }

  function refreshObstacleVectors() {
    if (!map || !obstacleVectorSource) return;
    const zoom = map.getView().getZoom();
    if (!Number.isFinite(zoom) || zoom < 8) {
      obstacleVectorSource.clear();
      return;
    }
    const size = map.getSize() || [800, 600];
    const extent = map.getView().calculateExtent(size);
    const token = ++obstacleRefreshToken;
    const geom = extent.map(function (n) { return Math.round(n); }).join(',');
    const url = 'https://api3.geo.admin.ch/rest/services/api/MapServer/identify'
      + '?geometryType=esriGeometryEnvelope'
      + '&geometry=' + encodeURIComponent(geom)
      + '&imageDisplay=' + Math.round(size[0]) + ',' + Math.round(size[1]) + ',96'
      + '&mapExtent=' + encodeURIComponent(geom)
      + '&tolerance=1'
      + '&layers=' + encodeURIComponent('all:' + OBSTACLE_LAYER_ID)
      + '&returnGeometry=true'
      + '&sr=3857'
      + '&lang=de'
      + '&limit=200';
    fetch(url, { cache: 'no-store' }).then(function (res) {
      if (!res.ok) throw new Error('HTTP ' + res.status);
      return res.json();
    }).then(function (payload) {
      if (token !== obstacleRefreshToken || !obstacleVectorSource) return;
      const rows = Array.isArray(payload && payload.results) ? payload.results : [];
      const features = [];
      rows.forEach(function (row) {
        const attrs = row.attributes || row.attrs || {};
        if (!formatObstacleHeight(attrs)) return;
        const geomObj = geometryFromIdentify(row.geometry || row.geom);
        if (!geomObj) return;
        const f = new ol.Feature({ geometry: geomObj });
        f.set('attributes', attrs);
        f.set('name', formatObstacleHeight(attrs));
        features.push(f);
      });
      obstacleVectorSource.clear();
      if (features.length) obstacleVectorSource.addFeatures(features);
    }).catch(function () {
      if (token !== obstacleRefreshToken) return;
    });
  }

  function scheduleObstacleRefresh() {
    if (!obstacleVectorSource) return;
    if (obstacleRefreshTimer) clearTimeout(obstacleRefreshTimer);
    obstacleRefreshTimer = setTimeout(refreshObstacleVectors, 280);
  }

  function setStatus(text) {
    const el = $('#overlayStatus');
    if (el) el.textContent = text || '';
  }

  function setLoad(on, text) {
    const el = $('#map27Load');
    if (!el) return;
    el.hidden = !on;
    if (text) el.textContent = text;
  }

  function syncObstacleHeightsSwitch(obstaclesOn) {
    const on = !!obstaclesOn;
    const heights = document.getElementById('obstacleHeights');
    const row = document.getElementById('obstacleHeightsRow');
    if (heights) heights.disabled = !on;
    if (row) row.classList.toggle('is-disabled', !on);
  }

  async function setOverlayEnabled(key, enabled) {
    const definition = overlayDefinitions[key];
    if (!definition || !map) return;
    overlayApplyTokenByKey[key] = (overlayApplyTokenByKey[key] || 0) + 1;
    const applyToken = overlayApplyTokenByKey[key];
    const existing = overlayLayersByKey[key] || [];
    existing.forEach((layer) => map.removeLayer(layer));
    overlayLayersByKey[key] = [];
    if (key === 'obstacles') {
      obstacleVectorSource = null;
      syncObstacleHeightsSwitch(!!enabled);
      if (!enabled && obstInfoCtl) obstInfoCtl.disable();
    }
    if (key === 'uasZones') {
      if (!enabled && uasLabelCtl) uasLabelCtl.disable();
      if (!enabled && uasCtl) uasCtl.disable();
    }
    if (key === 'dabs') {
      dabsVectorSource = null;
      dabsMeta = null;
    }
    if (!enabled) {
      return;
    }
    const opacity = getOverlayOpacity(key);
    let layers = [];
    if (key === 'obstacles') {
      const symbols = createGeoAdminOverlayLayer(OBSTACLE_LAYER_ID, opacity);
      symbols.setZIndex(90);
      if (typeof symbols.set === 'function') symbols.set('overlayKey', key);
      layers = [symbols].concat(createObstacleLayers(opacity));
    } else if (definition.kind === 'dabs') {
      layers = createDabsLayers(opacity);
    } else {
      const layerIds = definition.fixedLayerIds || [];
      if (!layerIds.length) {
        setStatus('Keine Ebene für ' + definition.label);
        return;
      }
      layers = layerIds.map((id) => {
        const layer = createGeoAdminOverlayLayer(id, opacity);
        if (typeof layer.set === 'function') layer.set('overlayKey', key);
        return layer;
      });
    }
    if (overlayApplyTokenByKey[key] !== applyToken) return;
    if (key === 'uasZones' || key === 'rpas') {
      const z = key === 'uasZones' ? 88 : 85;
      layers.forEach(function (layer) { layer.setZIndex(z); });
    }
    layers.forEach((layer) => map.addLayer(layer));
    overlayLayersByKey[key] = layers;
    setStatus(definition.label + ' aktiv');
    if (key === 'obstacles') scheduleObstacleRefresh();
    if (key === 'dabs') loadDabsOverlay();
    if (key === 'uasZones') {
      if (window.RkrkSwissOverlays && typeof window.RkrkSwissOverlays.attachUasLabels === 'function') {
        if (!uasLabelCtl) uasLabelCtl = window.RkrkSwissOverlays.attachUasLabels(map, {});
        if (uasLabelCtl) uasLabelCtl.enable(opacity);
      }
      if (uasCtl) uasCtl.enable();
    }
    if (key === 'obstacles') {
      if (window.RkrkSwissOverlays && typeof window.RkrkSwissOverlays.attachObstacleIdentify === 'function') {
        if (!obstInfoCtl) {
          obstInfoCtl = window.RkrkSwissOverlays.attachObstacleIdentify(map, {
            popupEl: $('#map27Popup'),
            popupBody: $('#map27PopupBody'),
            closeId: 'map27PopupClose',
            popupOverlay: popupOverlay,
          });
        }
        if (obstInfoCtl) obstInfoCtl.enable();
      }
    }
  }

  function persistOverlayUi() {
    const state = {};
    document.querySelectorAll('.overlay-toggle').forEach((cb) => {
      const key = cb.getAttribute('data-overlay-key');
      if (!key) return;
      const range = document.querySelector('[data-overlay-opacity-key="' + key + '"]');
      state[key] = {
        on: !!cb.checked,
        opacity: range ? Number(range.value) : 100,
      };
    });
    const heights = document.getElementById('obstacleHeights');
    if (heights) {
      if (!state.obstacles) state.obstacles = { on: false, opacity: 100 };
      state.obstacles.heights = !!heights.checked;
    }
    writeStoredOverlays(state);
  }

  function readSharedOverlayParam() {
    let params;
    try {
      params = new URLSearchParams(location.search || '');
    } catch (_) {
      return null;
    }
    if (!params.has('ovl')) return null;
    const raw = String(params.get('ovl') || '').trim();
    const state = {};
    if (!raw) return state;
    raw.split(',').forEach(function (part) {
      const bits = String(part || '').split(':');
      const key = String(bits[0] || '').trim();
      if (!key || !overlayDefinitions[key]) return;
      const opacity = Number(bits[1]);
      state[key] = {
        on: true,
        opacity: Number.isFinite(opacity) ? Math.max(0, Math.min(100, opacity)) : 100,
      };
    });
    return state;
  }

  function applyOverlayState(state, clearUnchecked) {
    if (clearUnchecked) {
      document.querySelectorAll('.overlay-toggle').forEach(function (cb) {
        cb.checked = false;
      });
    }
    Object.keys(state || {}).forEach(function (key) {
      const cb = document.querySelector('.overlay-toggle[data-overlay-key="' + key + '"]');
      const range = document.querySelector('[data-overlay-opacity-key="' + key + '"]');
      const entry = state[key] || {};
      if (cb && typeof entry.on === 'boolean') cb.checked = entry.on;
      if (range && Number.isFinite(Number(entry.opacity))) {
        range.value = String(entry.opacity);
        setOpacityLabel(key);
      }
    });
  }

  function applyStoredOverlayUi() {
    const shared = readSharedOverlayParam();
    if (shared) {
      applyOverlayState(shared, true);
      return;
    }
    const stored = readStoredOverlays();
    Object.keys(stored).forEach((key) => {
      const cb = document.querySelector('.overlay-toggle[data-overlay-key="' + key + '"]');
      const range = document.querySelector('[data-overlay-opacity-key="' + key + '"]');
      if (cb && typeof stored[key].on === 'boolean') cb.checked = stored[key].on;
      if (range && Number.isFinite(Number(stored[key].opacity))) {
        range.value = String(stored[key].opacity);
        setOpacityLabel(key);
      }
    });
    const heights = document.getElementById('obstacleHeights');
    if (heights && stored.obstacles && typeof stored.obstacles.heights === 'boolean') {
      heights.checked = stored.obstacles.heights;
    }
  }

  function bindOverlayUi() {
    document.querySelectorAll('.overlay-toggle').forEach((cb) => {
      cb.addEventListener('change', () => {
        const key = cb.getAttribute('data-overlay-key');
        setOverlayEnabled(key, cb.checked);
        persistOverlayUi();
      });
    });
    document.querySelectorAll('[data-overlay-opacity-key]').forEach((range) => {
      const key = range.getAttribute('data-overlay-opacity-key');
      setOpacityLabel(key);
      range.addEventListener('input', () => {
        setOpacityLabel(key);
        const layers = overlayLayersByKey[key] || [];
        const op = getOverlayOpacity(key);
        layers.forEach((layer) => layer.setOpacity(op));
        if (key === 'uasZones' && uasLabelCtl) uasLabelCtl.setOpacity(op);
        persistOverlayUi();
      });
    });
    const heights = document.getElementById('obstacleHeights');
    if (heights) {
      heights.addEventListener('change', function () {
        persistOverlayUi();
        if (obstacleVectorSource && typeof obstacleVectorSource.changed === 'function') {
          obstacleVectorSource.changed();
        }
        const layers = overlayLayersByKey.obstacles || [];
        layers.forEach(function (layer) {
          if (layer && typeof layer.changed === 'function') layer.changed();
        });
      });
    }
    const obstaclesCb = document.querySelector('.overlay-toggle[data-overlay-key="obstacles"]');
    syncObstacleHeightsSwitch(!!(obstaclesCb && obstaclesCb.checked));
  }

  function switchBasemap(mapType) {
    const id = baseLayerIds[mapType] || baseLayerIds.S;
    if (!map) return;
    if (baseLayer) map.removeLayer(baseLayer);
    if (tlmLayer) {
      map.removeLayer(tlmLayer);
      tlmLayer = null;
    }
    if (mapType === 'M') {
      baseLayer = createGeoAdminWmtsPngLayer('ch.swisstopo.swisstlm3d-karte-farbe', 1, 0);
    } else {
      baseLayer = createBaseLayer(id);
    }
    map.getLayers().insertAt(0, baseLayer);
    const wrap = document.querySelector('.rkrk-map27-wrap .map-wrap');
    if (wrap) wrap.style.background = mapType === 'M' ? '#ffffff' : '';
  }

  function featureLabel(feature) {
    if (!feature || typeof feature.get !== 'function') return { name: '', description: '' };
    const name = feature.get('name') || feature.get('Name') || '';
    const desc = feature.get('description') || '';
    return { name: String(name || ''), description: String(desc || '') };
  }

  function showPopup(coord, html) {
    const el = $('#map27Popup');
    if (!el || !popupOverlay) return;
    $('#map27PopupBody').innerHTML = html;
    el.style.display = 'block';
    popupOverlay.setPosition(coord);
  }

  function hidePopup() {
    const el = $('#map27Popup');
    if (el) el.style.display = 'none';
    if (popupOverlay) popupOverlay.setPosition(undefined);
  }

  function flattenKmlGeometry(feature) {
    const geom = feature && feature.getGeometry ? feature.getGeometry() : null;
    if (!geom || typeof geom.getLayout !== 'function') return;
    const layout = String(geom.getLayout() || '');
    if (layout === 'XY' || layout === 'XYM') return;
    try {
      const type = geom.getType();
      const strip = function (coords, depth) {
        if (!Array.isArray(coords)) return coords;
        if (depth <= 0) return [Number(coords[0]), Number(coords[1])];
        return coords.map(function (c) { return strip(c, depth - 1); });
      };
      let depth = 0;
      if (type === 'LineString' || type === 'MultiPoint') depth = 1;
      else if (type === 'Polygon' || type === 'MultiLineString') depth = 2;
      else if (type === 'MultiPolygon') depth = 3;
      geom.setCoordinates(strip(geom.getCoordinates(), depth), 'XY');
    } catch (_) { /* keep original geometry */ }
  }

  function kmlFallbackStyle(feature) {
    const geom = feature.getGeometry();
    const type = geom ? geom.getType() : '';
    if (type.indexOf('Polygon') >= 0) {
      return new ol.style.Style({
        fill: new ol.style.Fill({ color: 'rgba(34, 160, 80, 0.32)' }),
        stroke: new ol.style.Stroke({ color: '#1b7a3c', width: 2 }),
      });
    }
    if (type.indexOf('Line') >= 0) {
      return new ol.style.Style({
        stroke: new ol.style.Stroke({ color: '#1f6feb', width: 2.5 }),
      });
    }
    return new ol.style.Style({
      image: new ol.style.Circle({
        radius: 6,
        fill: new ol.style.Fill({ color: '#1f6feb' }),
        stroke: new ol.style.Stroke({ color: '#fff', width: 1.5 }),
      }),
    });
  }

  function styleHasDraw(styles) {
    const arr = Array.isArray(styles) ? styles : (styles ? [styles] : []);
    return arr.some(function (st) {
      if (!st) return false;
      const fill = st.getFill && st.getFill();
      const stroke = st.getStroke && st.getStroke();
      return !!(fill || stroke);
    });
  }

  function styleHasPointImage(styles) {
    const arr = Array.isArray(styles) ? styles : (styles ? [styles] : []);
    return arr.some(function (st) {
      return !!(st && st.getImage && st.getImage());
    });
  }

  function captureKmlStyle(feature) {
    flattenKmlGeometry(feature);
    let st = feature && feature.getStyle ? feature.getStyle() : null;
    if (typeof st === 'function') {
      try {
        st = st.call(feature, feature, 1);
      } catch (_) {
        st = null;
      }
    }
    const geom = feature.getGeometry();
    const type = geom ? geom.getType() : '';
    const isPoint = type.indexOf('Point') >= 0;
    if (!st
      || ((type.indexOf('Polygon') >= 0 || type.indexOf('Line') >= 0) && !styleHasDraw(st))
      || (isPoint && !styleHasPointImage(st))) {
      st = kmlFallbackStyle(feature);
    }
    if (!labelsEnabled()) {
      st = stripTextFromStyles(Array.isArray(st) ? st : (st ? [st] : []));
    }
    feature.set('rkrkOrigStyle', st);
    feature.setStyle(null);
  }

  const CSS_FONT = 'system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif';

  function labelScale() {
    const n = Number(cfg.labelScale);
    return Number.isFinite(n) && n > 0 ? n : 1;
  }

  function cssTextFont(weight, sizePx) {
    const size = Math.max(11, Math.round(Number(sizePx) || 13));
    return String(weight || 600) + ' ' + size + 'px ' + CSS_FONT;
  }

  function applyCssText(textStyle, sizePx) {
    if (!textStyle || typeof textStyle.setFont !== 'function') return;
    textStyle.setFont(cssTextFont(600, sizePx));
  }

  function labelsEnabled() {
    return !!cfg.showLabels;
  }

  function stripTextFromStyles(styles) {
    const arr = Array.isArray(styles) ? styles : (styles ? [styles] : []);
    arr.forEach(function (st) {
      if (st && typeof st.setText === 'function') st.setText(null);
    });
    return styles;
  }

  function attachPointLabel(styles, feature, factor, res) {
    if (!labelsEnabled()) return styles;
    const catLabel = catalogLabelStyle(feature, factor, res);
    const nameLabel = catLabel ? null : pointNameLabelStyle(feature, factor);
    const label = catLabel || nameLabel;
    if (!label) return styles;
    if (styles.length && styles[0] && typeof styles[0].clone === 'function') {
      const withText = styles[0].clone();
      withText.setText(label);
      styles[0] = withText;
    } else {
      styles.push(new ol.style.Style({ text: label }));
    }
    return styles;
  }

  function scaleKmlStyles(styles, factor, hideText) {
    const arr = Array.isArray(styles) ? styles : (styles ? [styles] : []);
    const scale = labelScale();
    const strokeMul = Math.max(0.55, scale);
    return arr.map(function (st) {
      if (!st || typeof st.clone !== 'function') return st;
      const clone = st.clone();
      const img = clone.getImage && clone.getImage();
      if (img && typeof img.setScale === 'function') {
        const base = Number(img.getScale());
        img.setScale((Number.isFinite(base) && base > 0 ? base : 1) * factor);
      }
      if (img && typeof img.setRadius === 'function') {
        const r = Number(img.getRadius());
        if (Number.isFinite(r) && r > 0) {
          img.setRadius(r * Math.max(0.7, factor));
        }
      }
      const stroke = clone.getStroke && clone.getStroke();
      if (stroke && typeof stroke.setWidth === 'function') {
        const w = Number(stroke.getWidth());
        if (Number.isFinite(w) && w > 0) {
          stroke.setWidth(Math.max(0.9, w * strokeMul));
        }
      }
      if (hideText) {
        clone.setText(null);
      } else {
        const txt = clone.getText && clone.getText();
        if (txt) {
          applyCssText(txt, 16 * scale);
          if (typeof txt.setScale === 'function') {
            const tBase = Number(txt.getScale());
            txt.setScale((Number.isFinite(tBase) && tBase > 0 ? tBase : 1) * Math.max(0.9, scale));
          }
        }
      }
      return clone;
    });
  }

  function catalogNumberFromFeature(feature) {
    const name = String((feature && (feature.get('name') || feature.get('Name'))) || '').trim();
    const m = name.match(/(\d{2,3}-\d{3})/);
    if (m) return m[1];
    const id = String((feature && typeof feature.getId === 'function' && feature.getId()) || '');
    const idm = id.match(/(\d{2,3}-\d{3})/);
    return idm ? idm[1] : '';
  }

  function catalogLabelStyle(feature, factor, resolution) {
    if (!labelsEnabled()) return null;
    const cat = catalogNumberFromFeature(feature);
    if (!cat) return null;
    const res = Number(resolution);
    if (Number.isFinite(res) && res > 80) return null;
    const size = Math.max(13, Math.round(16 * (Number(factor) > 0 ? Number(factor) : 1) * labelScale()));
    return new ol.style.Text({
      text: cat,
      font: cssTextFont(700, size),
      fill: new ol.style.Fill({ color: '#ffffff' }),
      stroke: new ol.style.Stroke({ color: 'rgba(28,28,28,0.92)', width: 4 }),
      offsetX: 20,
      offsetY: 0,
      overflow: true,
      textAlign: 'left',
      textBaseline: 'middle',
    });
  }

  function isBareFieldLabel(feature) {
    const styleUrl = String((feature && feature.get && (feature.get('styleUrl') || feature.get('StyleUrl'))) || '');
    return /#label\b/i.test(styleUrl);
  }

  function isJgAreaLabel(feature) {
    const styleUrl = String((feature && feature.get && (feature.get('styleUrl') || feature.get('StyleUrl'))) || '');
    return /#JG_LABEL\b/i.test(styleUrl);
  }

  function centeredFieldLabel(feature, resolution) {
    if (!labelsEnabled()) return null;
    const cat = catalogNumberFromFeature(feature);
    if (!cat) return null;
    const res = Number(resolution);
    if (Number.isFinite(res) && res > 80) return null;
    const size = Math.max(13, Math.round(16 * labelScale()));
    return new ol.style.Text({
      text: cat,
      font: cssTextFont(700, size),
      fill: new ol.style.Fill({ color: '#ffffff' }),
      stroke: new ol.style.Stroke({ color: 'rgba(28,28,28,0.92)', width: 4 }),
      textAlign: 'center',
      textBaseline: 'middle',
      offsetX: 0,
      offsetY: 0,
      overflow: true,
    });
  }

  function pointNameLabelStyle(feature, factor) {
    if (!labelsEnabled()) return null;
    const name = String((feature && (feature.get('name') || feature.get('Name'))) || '').trim();
    if (!name) return null;
    const size = Math.max(13, Math.round(16 * (Number(factor) > 0 ? Math.min(1.2, Number(factor) + 0.45) : 1) * labelScale()));
    return new ol.style.Text({
      text: name,
      font: cssTextFont(600, size),
      fill: new ol.style.Fill({ color: '#ffffff' }),
      stroke: new ol.style.Stroke({ color: 'rgba(28,28,28,0.9)', width: 3 }),
      offsetX: 15,
      offsetY: 0,
      overflow: true,
      textAlign: 'left',
      textBaseline: 'middle',
    });
  }

  function fieldAnchorKind(feature) {
    if (!feature || typeof feature.get !== 'function') return '';
    const styleUrl = String(feature.get('styleUrl') || feature.get('StyleUrl') || '');
    if (/#(startpoint|startplatz)\b/i.test(styleUrl)) return 'start';
    if (/#(centerpoint|zentrum)\b/i.test(styleUrl)) return 'center';
    const orig = feature.get('rkrkOrigStyle');
    const arr = Array.isArray(orig) ? orig : (orig ? [orig] : []);
    for (let i = 0; i < arr.length; i++) {
      const img = arr[i] && arr[i].getImage && arr[i].getImage();
      const src = img && typeof img.getSrc === 'function' ? String(img.getSrc() || '') : '';
      if (/startplatz|startpnt|startpoint/i.test(src)) return 'start';
      if (/suchfeld|centerpoint|zentrum/i.test(src)) return 'center';
    }
    return '';
  }

  function fieldAnchorTargetPx(res) {
    const refRes = 12;
    const refPx = 21;
    let px = refPx;
    if (Number.isFinite(res) && res > 0) {
      px = refPx * (refRes / res);
    }
    px = Math.max(12, Math.min(27, px));
    return px * Math.max(0.7, labelScale());
  }

  function nativeIconSize(img) {
    const size = img && typeof img.getSize === 'function' ? img.getSize() : null;
    const w = size && Number(size[0]) > 0 ? Number(size[0]) : 0;
    const h = size && Number(size[1]) > 0 ? Number(size[1]) : 0;
    if (w > 0 && h > 0) return [w, h];
    return [689, 895];
  }

  function equalizeFieldAnchorIcons(styles, res) {
    const arr = Array.isArray(styles) ? styles : (styles ? [styles] : []);
    const targetW = Math.round(fieldAnchorTargetPx(res));
    arr.forEach(function (st) {
      const img = st && st.getImage && st.getImage();
      if (!img) return;
      const native = nativeIconSize(img);
      const targetH = Math.round(targetW * native[1] / native[0]);
      if (typeof img.setWidth === 'function' && typeof img.setHeight === 'function') {
        img.setWidth(targetW);
        img.setHeight(targetH);
        if (typeof img.setScale === 'function') img.setScale(1);
        return;
      }
      if (typeof img.setScale === 'function') {
        img.setScale(native[0] > 0 ? (targetW / native[0]) : 0.05);
      }
    });
  }

  function pointIconFactor(feature, res) {
    let factor = 1.25;
    if (Number.isFinite(res)) {
      if (res > 80) factor = 0.95;
      else if (res > 38) factor = 1.05;
      else if (res > 19) factor = 1.15;
      else if (res > 9.5) factor = 1.25;
      else if (res > 4.8) factor = 1.35;
      else factor = 1.45;
    }
    return factor * Math.max(1, labelScale());
  }

  function kmlStyleForZoom(feature, resolution) {
    if (cfg.hideJgLabel && isJgAreaLabel(feature)) return [];
    const geom = feature.getGeometry();
    const type = geom ? geom.getType() : '';
    const orig = feature.get('rkrkOrigStyle') || kmlFallbackStyle(feature);
    const hideText = !labelsEnabled();
    if (type.indexOf('Point') < 0) {
      if ((type.indexOf('Polygon') >= 0 || type.indexOf('Line') >= 0) && !styleHasDraw(orig)) {
        return hideText ? stripTextFromStyles(kmlFallbackStyle(feature)) : kmlFallbackStyle(feature);
      }
      return scaleKmlStyles(orig, Math.max(1, labelScale()), hideText);
    }
    const res = Number(resolution);
    if (isBareFieldLabel(feature)) {
      const text = centeredFieldLabel(feature, res);
      if (!text || hideText) return [];
      return [new ol.style.Style({ text: text })];
    }
    const anchorKind = fieldAnchorKind(feature);
    if (anchorKind) {
      let styles = scaleKmlStyles(orig, 1, true);
      if (!styleHasPointImage(styles)) {
        styles = scaleKmlStyles(kmlFallbackStyle(feature), 1, true);
      }
      equalizeFieldAnchorIcons(styles, res);
      attachPointLabel(styles, feature, 1, res);
      return hideText ? stripTextFromStyles(styles) : styles;
    }
    const factor = pointIconFactor(feature, res);
    let styles = scaleKmlStyles(orig, factor, true);
    if (!styleHasPointImage(styles)) {
      styles = scaleKmlStyles(kmlFallbackStyle(feature), factor, true);
    }
    attachPointLabel(styles, feature, factor < 0.4 ? 0.75 : 1, res);
    return hideText ? stripTextFromStyles(styles) : styles;
  }

  function isFieldAreaFeature(feature) {
    if (!feature || typeof feature.getGeometry !== 'function') return false;
    const geom = feature.getGeometry();
    const type = geom ? geom.getType() : '';
    if (type.indexOf('Polygon') < 0) return false;
    const styleUrl = String(feature.get('styleUrl') || feature.get('StyleUrl') || '');
    if (/#fieldarea_/i.test(styleUrl)) return true;
    const id = String((typeof feature.getId === 'function' && feature.getId()) || '');
    if (/^A\d{2,3}-\d{3}/i.test(id)) return true;
    const name = String(feature.get('name') || feature.get('Name') || '');
    return /\b\d{2,3}-\d{3}\b/.test(name);
  }

  function fFilterIds() {
    const raw = String(cfg.fFilter || '').replace(/%2[bB]/g, ' ').replace(/[+_]/g, ' ');
    if (!raw.trim()) return [];
    return raw.split(/[\s,;|]+/).map(function (s) { return s.trim(); }).filter(Boolean);
  }

  function featureMatchesFFilter(feature) {
    const ids = fFilterIds();
    if (!ids.length) return true;
    const id = String((feature && typeof feature.getId === 'function' && feature.getId()) || '');
    const name = String((feature && feature.get && (feature.get('name') || feature.get('Name'))) || '');
    const blob = id + ' ' + name;
    return ids.some(function (fid) { return blob.indexOf(fid) !== -1; });
  }

  function featuresForFit(source) {
    const all = (source && typeof source.getFeatures === 'function') ? source.getFeatures() : [];
    const areas = all.filter(function (f) {
      return isFieldAreaFeature(f) && featureMatchesFFilter(f);
    });
    if (areas.length) return areas;
    const polys = all.filter(function (f) {
      const g = f && f.getGeometry && f.getGeometry();
      const t = g ? g.getType() : '';
      return t.indexOf('Polygon') >= 0 && featureMatchesFFilter(f) && !fieldAnchorKind(f);
    });
    if (polys.length) return polys;
    return all.filter(function (f) {
      return !!fieldAnchorKind(f) && featureMatchesFFilter(f);
    });
  }

  function featureCenterXY(feature) {
    const geom = feature.getGeometry();
    if (!geom) return null;
    if (typeof geom.getInteriorPoint === 'function') {
      try {
        const pt = geom.getInteriorPoint();
        const c = pt && pt.getCoordinates ? pt.getCoordinates() : null;
        if (c && c.length >= 2) return [c[0], c[1]];
      } catch (_) { /* use extent */ }
    }
    if (typeof geom.getExtent !== 'function') return null;
    const e = geom.getExtent();
    if (!e || e.length < 4) return null;
    return [(e[0] + e[2]) / 2, (e[1] + e[3]) / 2];
  }

  function unionExtents(exts) {
    let out = null;
    exts.forEach(function (e) {
      if (!e || e.length < 4) return;
      if (!out) {
        out = e.slice();
        return;
      }
      if (ol.extent && typeof ol.extent.extend === 'function') {
        ol.extent.extend(out, e);
      } else {
        out[0] = Math.min(out[0], e[0]);
        out[1] = Math.min(out[1], e[1]);
        out[2] = Math.max(out[2], e[2]);
        out[3] = Math.max(out[3], e[3]);
      }
    });
    return out;
  }

  function fieldExtentKeepMost(source, keepRatio) {
    const ratio = Number.isFinite(keepRatio) ? keepRatio : 0.95;
    const target = String(cfg.fitTarget || 'fields');
    let pool;
    if (target === 'polygons' || target === 'eg') {
      const all = (source && typeof source.getFeatures === 'function') ? source.getFeatures() : [];
      pool = all.filter(function (f) {
        const g = f && f.getGeometry && f.getGeometry();
        const t = g ? g.getType() : '';
        return t.indexOf('Polygon') >= 0 && featureMatchesFFilter(f);
      });
    } else {
      pool = featuresForFit(source);
    }
    const items = [];
    pool.forEach(function (f) {
      const geom = f.getGeometry();
      if (!geom || typeof geom.getExtent !== 'function') return;
      const c = featureCenterXY(f);
      if (!c) return;
      items.push({ ext: geom.getExtent(), c: c });
    });
    const n = items.length;
    if (!n) return null;
    const keep = Math.max(1, Math.ceil(n * ratio));
    if (keep >= n) return unionExtents(items.map(function (i) { return i.ext; }));
    const xs = items.map(function (i) { return i.c[0]; }).sort(function (a, b) { return a - b; });
    const ys = items.map(function (i) { return i.c[1]; }).sort(function (a, b) { return a - b; });
    const mx = xs[Math.floor(xs.length / 2)];
    const my = ys[Math.floor(ys.length / 2)];
    items.forEach(function (i) {
      const dx = i.c[0] - mx;
      const dy = i.c[1] - my;
      i.d = dx * dx + dy * dy;
    });
    items.sort(function (a, b) { return a.d - b.d; });
    return unionExtents(items.slice(0, keep).map(function (i) { return i.ext; }));
  }

  function fitKeepRatio() {
    const r = Number(cfg.fitKeepRatio);
    if (Number.isFinite(r) && r > 0 && r <= 1) return r;
    return 0.95;
  }

  function mapSizeOk() {
    const size = map && typeof map.getSize === 'function' ? map.getSize() : null;
    return !!(size && Number(size[0]) >= 280 && Number(size[1]) >= 280);
  }

  function fitCropName() {
    const named = String(cfg.fitCrop || '').toLowerCase();
    if (named === 'flugplan' || named === 'suchfeld') return named;
    if (cfg.flp || cfg.pfad) return 'flugplan';
    if (cfg.ovr) return 'suchfeld';
    return 'suchfeld';
  }

  function fieldFillFrac() {
    return fitCropName() === 'flugplan' ? 0.35 : 0.17;
  }

  function expandExtentToFill(ext, fillFrac) {
    const frac = Math.max(0.08, Math.min(0.42, Number(fillFrac) || fieldFillFrac()));
    const scale = 1 / frac;
    const cx = (ext[0] + ext[2]) / 2;
    const cy = (ext[1] + ext[3]) / 2;
    let hw = Math.abs(ext[2] - ext[0]) / 2;
    let hh = Math.abs(ext[3] - ext[1]) / 2;
    if (!(hw > 0)) hw = 80;
    if (!(hh > 0)) hh = 80;
    return [cx - hw * scale, cy - hh * scale, cx + hw * scale, cy + hh * scale];
  }

  function fitPadding() {
    if (cfg.fitTarget === 'polygons' || cfg.fitTarget === 'eg') {
      return [18, 18, 18, 18];
    }
    if (fFilterIds().length) {
      return [16, 16, 16, 16];
    }
    return [48, 48, 48, 48];
  }

  function fitVectorExtent(source) {
    if (!map || !source) return;
    try {
      map.updateSize();
      if (!mapSizeOk()) return false;
      let ext = fieldExtentKeepMost(source, fitKeepRatio());
      if (!ext || (ol.extent && typeof ol.extent.isEmpty === 'function' && ol.extent.isEmpty(ext))) {
        return false;
      }
      if (fFilterIds().length) {
        ext = expandExtentToFill(ext, fieldFillFrac());
      }
      map.getView().fit(ext, {
        padding: fitPadding(),
        maxZoom: 17,
        duration: 0,
        nearest: false,
      });
      return true;
    } catch (_) {
      return false;
    }
  }

  function scheduleFieldFit(source) {
    if (!source) return;
    let tries = 0;
    function go() {
      if (fitVectorExtent(source) || tries >= 15) return;
      tries += 1;
      setTimeout(go, 80);
    }
    go();
  }

  function loadKml() {
    const url = String(cfg.kmlUrl || '');
    if (!url || !map) return;
    setLoad(true, 'Felder werden geladen …');
    if (kmlLayer) {
      map.removeLayer(kmlLayer);
      kmlLayer = null;
    }
    fetch(url, { credentials: 'same-origin', cache: 'no-store' }).then(function (res) {
      if (!res.ok) throw new Error('HTTP ' + res.status);
      return res.text();
    }).then(function (text) {
      if (!text || text.indexOf('<kml') === -1) {
        throw new Error('Keine KML-Antwort');
      }
      const xmlAt = text.indexOf('<?xml');
      const kmlAt = text.indexOf('<kml');
      const cut = xmlAt >= 0 ? xmlAt : kmlAt;
      if (cut > 0) {
        text = text.slice(cut);
      }
      const format = new ol.format.KML({
        extractStyles: true,
        showPointNames: labelsEnabled(),
      });
      const features = format.readFeatures(text, {
        dataProjection: 'EPSG:4326',
        featureProjection: 'EPSG:3857',
      });
      features.forEach(captureKmlStyle);
      const source = new ol.source.Vector({ features: features });
      kmlLayer = new ol.layer.Vector({
        source: source,
        style: kmlStyleForZoom,
        zIndex: 80,
        declutter: false,
        updateWhileAnimating: true,
        updateWhileInteracting: true,
        renderBuffer: 300,
      });
      map.addLayer(kmlLayer);
      setLoad(false);
      const types = {};
      features.forEach(function (f) {
        const t = f.getGeometry() ? f.getGeometry().getType() : 'none';
        types[t] = (types[t] || 0) + 1;
      });
      const n = features.length;
      const poly = (types.Polygon || 0) + (types.MultiPolygon || 0);
      const line = (types.LineString || 0) + (types.MultiLineString || 0);
      const pt = (types.Point || 0) + (types.MultiPoint || 0);
      if (!n) {
        setStatus('Keine KML-Objekte');
      } else {
        setStatus(n + ' KML-Objekte'
          + (poly ? (', ' + poly + ' Flächen') : '')
          + (line ? (', ' + line + ' Pfade') : '')
          + (pt && !poly && !line ? (' (' + pt + ' Punkte)') : ''));
      }
      if (cfg.fitExtent && n) {
        scheduleFieldFit(source);
      }
    }).catch(function () {
      setLoad(false);
      setStatus('KML konnte nicht geladen werden');
    });
  }

  function geoAdminZoomToOl(z) {
    const n = Number(z);
    if (!Number.isFinite(n)) return 12;
    return Math.max(6, Math.min(19, n + 6.5));
  }

  function isExclusiveRegValue(val) {
    const v = String(val || '').toLowerCase();
    return v === 'alle' || v === 'all' || v === 'leer';
  }

  function bindRegionExclusive(form) {
    const boxes = Array.from(form.querySelectorAll('input[name="RegSel[]"]'));
    if (!boxes.length) return;
    const alle = boxes.find(function (b) {
      const v = String(b.value || '').toLowerCase();
      return v === 'alle' || v === 'all';
    });
    const leer = boxes.find(function (b) {
      return String(b.value || '').toLowerCase() === 'leer';
    });

    function syncExclusive() {
      const exclusiveOn = (alle && alle.checked) || (leer && leer.checked);
      boxes.forEach(function (b) {
        if (isExclusiveRegValue(b.value)) return;
        b.disabled = false;
        if (exclusiveOn) b.checked = false;
      });
    }

    boxes.forEach(function (cb) {
      cb.addEventListener('change', function () {
        const v = String(cb.value || '').toLowerCase();
        if (cb.checked && (v === 'alle' || v === 'all')) {
          if (leer) leer.checked = false;
        }
        if (cb.checked && v === 'leer') {
          if (alle) alle.checked = false;
        }
        if (cb.checked && !isExclusiveRegValue(cb.value)) {
          if (alle) alle.checked = false;
          if (leer) leer.checked = false;
        }
        syncExclusive();
      });
    });
    syncExclusive();
  }

  function collectFormParams(form, skipCamera) {
    const params = new URLSearchParams();
    const keep = form.querySelectorAll('input[type="hidden"][data-keep="1"]');
    keep.forEach((inp) => {
      if (inp.name && inp.value !== '') params.set(inp.name, inp.value);
    });
    const regs = Array.from(form.querySelectorAll('input[name="RegSel[]"]:checked')).map((i) => i.value);
    const hasAlle = regs.some(function (r) {
      const v = String(r || '').toLowerCase();
      return v === 'all' || v === 'alle';
    });
    const hasLeer = regs.some(function (r) {
      return String(r || '').toLowerCase() === 'leer';
    });
    if (hasAlle) {
      params.set('Reg', 'ALL');
    } else if (hasLeer) {
      params.set('Reg', 'leer');
    } else if (regs.length) {
      params.set('Reg', regs.join(' '));
    } else {
      params.set('Reg', 'leer');
    }
    const ka = form.querySelector('input[name="KA"]:checked');
    params.set('KA', ka ? ka.value : (cfg.mapType || 'S'));
    form.querySelectorAll('input[data-map-opt="1"]').forEach((cb) => {
      params.set(cb.getAttribute('data-opt-key'), cb.checked ? 'on' : 'off');
    });
    const ff = form.querySelector('[name="FFilter"]');
    params.set('FFilter', ff ? String(ff.value || '') : '');
    const zoom = form.querySelector('[name="Zoom"]');
    if (zoom) params.set('Zoom', zoom.value);
    const dbw = form.querySelector('[name="DBW"]');
    if (dbw) params.set('DBW', dbw.checked ? 'on' : 'off');
    const ex = form.querySelector('[name="EXselect"]');
    params.set('EX', ex && ex.checked ? 'yes' : 'no');
    if (map && !skipCamera) {
      try {
        const view = map.getView();
        const center = view.getCenter();
        if (center && center.length >= 2) {
          const ll = ol.proj.toLonLat(center);
          if (ll && Number.isFinite(ll[0]) && Number.isFinite(ll[1])) {
            params.set('LON', String(Math.round(ll[0] * 1e6) / 1e6));
            params.set('LAT', String(Math.round(ll[1] * 1e6) / 1e6));
          }
        }
        const z = Number(view.getZoom());
        if (Number.isFinite(z)) {
          params.set('ZO', String(Math.round((z - 6.5) * 100) / 100));
        }
      } catch (_) { /* noop */ }
    }
    return params;
  }

  function formActionUrl(form, skipCamera) {
    const path = String(location.pathname || 'rkrk_map.php');
    return path + '?' + collectFormParams(form, skipCamera).toString();
  }

  function applyForm(form, skipCamera) {
    location.assign(formActionUrl(form, skipCamera));
  }

  function isClientOnlySetting(el) {
    if (!el) return false;
    if (el.classList && el.classList.contains('overlay-toggle')) return true;
    if (el.getAttribute && el.getAttribute('data-overlay-opacity-key')) return true;
    if (el.id === 'obstacleHeights') return true;
    if (el.id === 'mapPdfScaleSelect' || el.id === 'map27ScaleReadout') return true;
    return false;
  }

  function installForm() {
    const form = document.getElementById('map27Form');
    if (!form) return;
    bindRegionExclusive(form);
    form.addEventListener('submit', function (ev) {
      ev.preventDefault();
      applyForm(form);
    });
    form.addEventListener('change', function (ev) {
      const t = ev.target;
      if (!t || isClientOnlySetting(t)) return;
      if (t.name === 'KA') {
        switchBasemap(String(t.value || cfg.mapType || 'S').toUpperCase());
        try { history.replaceState(null, '', formActionUrl(form)); } catch (_) { /* noop */ }
        return;
      }
      const skipCamera = t.name === 'RegSel[]' || t.name === 'FFilter';
      applyForm(form, skipCamera);
    });
    const ff = form.querySelector('[name="FFilter"]');
    if (ff) {
      let filterTimer = null;
      ff.addEventListener('input', function () {
        if (filterTimer) clearTimeout(filterTimer);
        filterTimer = setTimeout(function () { applyForm(form, true); }, 450);
      });
    }
  }

  function setShareStatus(text) {
    const el = document.getElementById('sharePanelStatus');
    if (el) el.textContent = text || '';
  }

  function wgs84ToLv95(lat, lon) {
    const phi = ((Number(lat) * 3600) - 169028.66) / 10000;
    const lam = ((Number(lon) * 3600) - 26782.5) / 10000;
    const e = 2600072.37
      + (211455.93 * lam)
      - (10938.51 * lam * phi)
      - (0.36 * lam * phi * phi)
      - (44.54 * lam * lam * lam);
    const n = 1200147.07
      + (308807.95 * phi)
      + (3745.25 * lam * lam)
      + (76.63 * phi * phi)
      - (194.56 * lam * lam * phi)
      + (119.79 * phi * phi * phi);
    return { e: Math.round(e), n: Math.round(n) };
  }

  function isLv95(e, n) {
    return Number.isFinite(e) && Number.isFinite(n)
      && e >= 2450000 && e <= 2900000
      && n >= 1050000 && n <= 1350000;
  }

  function currentBasemapId() {
    const form = document.getElementById('map27Form');
    const kaEl = form ? form.querySelector('input[name="KA"]:checked') : null;
    const ka = String((kaEl && kaEl.value) || cfg.mapType || 'S').toUpperCase();
    return baseLayerIds[ka] || baseLayerIds.F;
  }

  function collectActiveGeoAdminLayers() {
    const parts = [];
    document.querySelectorAll('.overlay-toggle:checked').forEach(function (cb) {
      const key = cb.getAttribute('data-overlay-key');
      const def = overlayDefinitions[key];
      if (!def || !Array.isArray(def.fixedLayerIds)) return;
      const opacity = getOverlayOpacity(key);
      const op = String(Math.round(opacity * 100) / 100).replace(/\.00$/, '');
      def.fixedLayerIds.forEach(function (id) {
        if (!id) return;
        parts.push(id + ',t,' + op);
      });
    });
    return parts;
  }

  function publicKmlUrl() {
    const rel = String(cfg.kmlUrl || '').trim();
    if (!rel) return '';
    const abs = new URL(rel, location.href);
    if (abs.protocol === 'http:' && /synology\.me$|rehkitzrettung\.ch$/i.test(abs.hostname)) {
      abs.protocol = 'https:';
    }
    return abs.href;
  }

  function encodeGeoAdminKmlLayer(kmlUrl) {
    const enc = String(kmlUrl || '')
      .replace(/\|/g, '%7C')
      .replace(/\?/g, '%3F')
      .replace(/&/g, '%26')
      .replace(/#/g, '%23');
    return 'KML%7C' + enc;
  }

  function currentGeoAdminZoom() {
    const view = map && map.getView ? map.getView() : null;
    let olZ = view && typeof view.getZoom === 'function' ? Number(view.getZoom()) : NaN;
    const sel = document.getElementById('mapPdfScaleSelect');
    const denom = sel ? Number(sel.value) : 0;
    if (denom > 0 && view && typeof view.getCenter === 'function') {
      const center = view.getCenter();
      if (center && ol.proj && typeof ol.proj.toLonLat === 'function') {
        const ll = ol.proj.toLonLat(center);
        const lat = (Number(ll[1]) * Math.PI) / 180;
        const cos = Math.max(0.2, Math.cos(lat));
        const res = (denom * 0.0254) / (96 * cos);
        if (res > 0) olZ = Math.log(156543.03392804097 / res) / Math.LN2;
      }
    }
    if (!Number.isFinite(olZ)) olZ = 12;
    return Math.max(0, Math.min(13, Math.round(olZ - 6.5)));
  }

  function buildSwissTopoUrl() {
    if (!map || !map.getView) throw new Error('Karte ist noch nicht bereit.');
    const view = map.getView();
    const center = view.getCenter();
    if (!center || !ol.proj || typeof ol.proj.toLonLat !== 'function') {
      throw new Error('Kein Kartenzentrum.');
    }
    const ll = ol.proj.toLonLat(center);
    const lv = wgs84ToLv95(ll[1], ll[0]);
    if (!isLv95(lv.e, lv.n)) {
      throw new Error('Zentrum liegt ausserhalb der Schweizer Karte.');
    }
    const kmlUrl = publicKmlUrl();
    if (!/^https:\/\//i.test(kmlUrl)) {
      throw new Error('Keine öffentliche HTTPS-Adresse für den Karteninhalt.');
    }
    const layers = [encodeGeoAdminKmlLayer(kmlUrl)].concat(collectActiveGeoAdminLayers());
    const params = [
      'lang=de',
      'center=' + lv.e + ',' + lv.n,
      'z=' + currentGeoAdminZoom(),
      'topic=ech',
      'layers=' + layers.join(';'),
      'bgLayer=' + currentBasemapId(),
    ].join('&');
    return 'https://map.geo.admin.ch/#/map?' + params;
  }

  function overlayShareValue() {
    const bits = [];
    document.querySelectorAll('.overlay-toggle:checked').forEach(function (cb) {
      const key = cb.getAttribute('data-overlay-key');
      if (!key) return;
      const range = document.querySelector('[data-overlay-opacity-key="' + key + '"]');
      const opacity = range ? Math.round(Number(range.value) || 100) : 100;
      bits.push(key + ':' + opacity);
    });
    return bits.join(',');
  }

  function publicMapShareUrl(path) {
    const url = new URL(path, 'https://tchr-frog.synology.me/rkropen/pub/rkrk_map.php');
    url.protocol = 'https:';
    url.hostname = 'tchr-frog.synology.me';
    url.port = '';
    const parts = url.pathname.split('/');
    const rk = parts.indexOf('rkropen');
    if (rk >= 0 && parts[rk + 1]) {
      parts[rk + 1] = 'pub';
      url.pathname = parts.join('/');
    } else {
      url.pathname = '/rkropen/pub/rkrk_map.php';
    }
    return url;
  }

  function readShareScaleDenom() {
    try {
      if (typeof selectedPdfScaleDenom === 'function') {
        const n = Number(selectedPdfScaleDenom());
        return (n === 10000 || n === 25000 || n === 50000) ? n : 0;
      }
    } catch (_) { /* Massstab darf den QR nicht verstecken */ }
    try {
      const sel = document.getElementById('mapPdfScaleSelect');
      const n = sel ? Number(sel.value) : 0;
      return (n === 10000 || n === 25000 || n === 50000) ? n : 0;
    } catch (_) {
      return 0;
    }
  }

  function buildShareUrl() {
    const form = document.getElementById('map27Form');
    const path = form ? formActionUrl(form, false) : (location.pathname + location.search);
    const url = publicMapShareUrl(path);
    url.searchParams.set('ovl', overlayShareValue());
    url.searchParams.set('CV', '1');
    url.searchParams.delete('Fit');
    const denom = readShareScaleDenom();
    if (denom > 0) url.searchParams.set('SCL', String(denom));
    else url.searchParams.delete('SCL');
    return url.href;
  }

  function showShareQr(url) {
    const panel = document.getElementById('map27SharePanel');
    const box = document.getElementById('shareQrBox');
    const img = document.getElementById('shareQrImg');
    const link = document.getElementById('shareQrLink');
    const safe = String(url || '');
    if (panel) panel.open = true;
    if (link) {
      link.href = safe;
      link.textContent = safe;
      link.hidden = false;
      link.style.display = 'block';
    }
    if (img) {
      const primary = 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&ecc=M&margin=8&data=' + encodeURIComponent(safe);
      const backup = 'https://quickchart.io/qr?size=180&margin=1&text=' + encodeURIComponent(safe);
      img.hidden = false;
      img.style.display = 'block';
      img.style.visibility = 'visible';
      img.alt = 'QR-Code der Kartenansicht';
      img.onerror = function () {
        if (img.getAttribute('data-qr-fallback') === '1') return;
        img.setAttribute('data-qr-fallback', '1');
        img.src = backup;
      };
      img.removeAttribute('data-qr-fallback');
      if (!img.getAttribute('src') || img.getAttribute('src') === primary) {
        img.removeAttribute('src');
      }
      img.src = primary;
    }
    if (box) {
      box.hidden = false;
      box.style.display = 'block';
      box.style.visibility = 'visible';
    }
  }

  async function copyText(text) {
    try {
      if (navigator.clipboard && navigator.clipboard.writeText) {
        await navigator.clipboard.writeText(text);
        return true;
      }
    } catch (_) { /* fallback */ }
    try {
      const ta = document.createElement('textarea');
      ta.value = text;
      ta.setAttribute('readonly', 'readonly');
      ta.style.position = 'fixed';
      ta.style.left = '-9999px';
      document.body.appendChild(ta);
      ta.select();
      const ok = document.execCommand('copy');
      ta.remove();
      return !!ok;
    } catch (_) {
      return false;
    }
  }

  async function downloadCurrentKml() {
    const rel = String(cfg.kmlUrl || '').trim();
    if (!rel) throw new Error('Kein Karteninhalt.');
    const url = new URL(rel, location.href);
    url.searchParams.set('DL', '1');
    const res = await fetch(url.href, { credentials: 'same-origin', cache: 'no-store' });
    const text = await res.text();
    if (!res.ok || text.indexOf('<kml') === -1) {
      throw new Error('Die Antwort ist keine KML-Datei.');
    }
    const name = String(cfg.kmlDownloadName || 'karte.kml');
    const blob = new Blob([text], { type: 'application/vnd.google-earth.kml+xml;charset=utf-8' });
    const a = document.createElement('a');
    const objectUrl = URL.createObjectURL(blob);
    a.href = objectUrl;
    a.download = name;
    document.body.appendChild(a);
    a.click();
    a.remove();
    setTimeout(function () { URL.revokeObjectURL(objectUrl); }, 1500);
  }

  let scaleApplyLock = 0;

  function formatSwissScaleDenom(denom) {
    const n = Math.max(1, Math.round(Number(denom) || 1));
    const s = String(n);
    let out = '';
    for (let i = 0; i < s.length; i += 1) {
      if (i && ((s.length - i) % 3 === 0)) out += "'";
      out += s.charAt(i);
    }
    return '1:' + out;
  }

  function roundDisplayScaleDenom(raw) {
    const n = Number(raw);
    if (!(n > 0) || !Number.isFinite(n)) return 0;
    const standards = [10000, 25000, 50000];
    for (let i = 0; i < standards.length; i += 1) {
      if (Math.abs(n - standards[i]) / standards[i] < 0.03) return standards[i];
    }
    if (n >= 20000) return Math.round(n / 500) * 500;
    if (n >= 5000) return Math.round(n / 100) * 100;
    return Math.max(1, Math.round(n / 50) * 50);
  }

  function currentViewScaleDenom() {
    if (!map || !map.getView || !ol.proj || typeof ol.proj.getPointResolution !== 'function') return 0;
    const view = map.getView();
    const center = view.getCenter();
    const res = view.getResolution();
    if (!center || !(res > 0)) return 0;
    const mPerUnit = ol.proj.getPointResolution(view.getProjection(), 1, center);
    if (!(mPerUnit > 0)) return 0;
    return roundDisplayScaleDenom((res * mPerUnit) / 0.00028);
  }

  function paperDenomForWidth(imgWmm, mapWpx) {
    if (!map || !map.getView || !(Number(imgWmm) > 0) || !(Number(mapWpx) > 0)) return 0;
    if (!ol.proj || typeof ol.proj.getPointResolution !== 'function') return currentViewScaleDenom();
    const view = map.getView();
    const center = view.getCenter();
    const res = view.getResolution();
    if (!center || !(res > 0)) return 0;
    const mPerUnit = ol.proj.getPointResolution(view.getProjection(), 1, center);
    if (!(mPerUnit > 0)) return 0;
    return roundDisplayScaleDenom((res * mPerUnit * Number(mapWpx)) / (Number(imgWmm) / 1000));
  }

  function applyMapScaleDenom(denom) {
    const d = Number(denom);
    if (!(d === 10000 || d === 25000 || d === 50000)) return false;
    if (!map || !map.getView || !ol.proj || typeof ol.proj.getPointResolution !== 'function') return false;
    const view = map.getView();
    const center = view.getCenter();
    if (!center) return false;
    const mPerUnit = ol.proj.getPointResolution(view.getProjection(), 1, center);
    if (!(mPerUnit > 0)) return false;
    const res = (d * 0.00028) / mPerUnit;
    if (!(res > 0) || !Number.isFinite(res)) return false;
    scaleApplyLock += 1;
    try {
      view.setResolution(res);
    } catch (_) {
      scaleApplyLock -= 1;
      return false;
    }
    scaleApplyLock -= 1;
    return true;
  }

  function applyPaperScale(denom, imgWmm, mapWpx) {
    const d = Number(denom);
    const paperMm = Number(imgWmm);
    const px = Number(mapWpx);
    if (!(d > 0) || !(paperMm > 0) || !(px > 0) || !map || !map.getView) return false;
    if (!ol.proj || typeof ol.proj.getPointResolution !== 'function') return false;
    const view = map.getView();
    const center = view.getCenter();
    if (!center) return false;
    const mPerUnit = ol.proj.getPointResolution(view.getProjection(), 1, center);
    if (!(mPerUnit > 0)) return false;
    const nextRes = ((paperMm * d / 1000) / px) / mPerUnit;
    if (!(nextRes > 0) || !Number.isFinite(nextRes)) return false;
    scaleApplyLock += 1;
    try {
      view.setResolution(nextRes);
    } catch (_) {
      scaleApplyLock -= 1;
      return false;
    }
    scaleApplyLock -= 1;
    return true;
  }

  function ensureScaleReadout() {
    if (document.getElementById('map27ScaleCss')) {
      /* already injected */
    } else {
      const style = document.createElement('style');
      style.id = 'map27ScaleCss';
      style.textContent = ''
        + '.rkrk-map27-scale{position:absolute;left:10px;bottom:28px;z-index:24;margin:0;'
        + 'padding:4px 8px;border:1px solid rgba(0,0,0,.28);border-radius:6px;'
        + 'background:rgba(255,255,255,.92);color:#111827;font:600 12px/1.2 Arial,Helvetica,sans-serif;'
        + 'cursor:pointer;box-shadow:0 1px 6px rgba(0,0,0,.16);}'
        + '.rkrk-map27-scale:hover{outline:1px dashed #96B588;outline-offset:2px;}';
      document.head.appendChild(style);
    }
    let el = document.getElementById('map27ScaleReadout');
    if (el) return el;
    const wrap = document.querySelector('.map-wrap');
    if (!wrap) return null;
    el = document.createElement('button');
    el.type = 'button';
    el.id = 'map27ScaleReadout';
    el.className = 'rkrk-map27-scale';
    el.title = 'Massstab der aktuellen Karte. Klick setzt den Fokus auf die Massstab-Auswahl.';
    wrap.appendChild(el);
    el.addEventListener('click', function () {
      const sel = document.getElementById('mapPdfScaleSelect');
      if (sel) sel.focus();
    });
    return el;
  }

  function updateScaleReadout() {
    const el = ensureScaleReadout();
    if (!el) return;
    const selected = selectedPdfScaleDenom();
    const denom = selected || currentViewScaleDenom();
    el.textContent = denom ? ('Massstab ' + formatSwissScaleDenom(denom)) : 'Massstab';
    el.setAttribute('aria-label', el.textContent);
  }

  function installScaleControl() {
    const sel = document.getElementById('mapPdfScaleSelect');
    if (sel && !sel.__rkrkBound) {
      sel.__rkrkBound = true;
      const saved = Number(cfg.scaleDenom);
      if (saved === 10000 || saved === 25000 || saved === 50000) {
        sel.value = String(saved);
        applyMapScaleDenom(saved);
      }
      sel.addEventListener('change', function (ev) {
        if (ev && ev.stopPropagation) ev.stopPropagation();
        const denom = selectedPdfScaleDenom();
        if (denom > 0) {
          const ok = applyMapScaleDenom(denom);
          setShareStatus(ok
            ? ('Massstab ' + formatSwissScaleDenom(denom) + ' auf die aktuelle Kartenmitte gesetzt.')
            : 'Massstab konnte nicht gesetzt werden.');
        } else {
          const live = currentViewScaleDenom();
          setShareStatus(live
            ? ('Massstab: aktuelle Ansicht ' + formatSwissScaleDenom(live) + '.')
            : 'Massstab: aktuelle Ansicht.');
        }
        updateScaleReadout();
      });
    }
    if (map && map.getView && !map.__rkrkScaleListen) {
      map.__rkrkScaleListen = true;
      map.getView().on('change:resolution', function () {
        if (scaleApplyLock) return;
        const box = document.getElementById('mapPdfScaleSelect');
        if (box && box.value !== '0') box.value = '0';
        updateScaleReadout();
      });
    }
    updateScaleReadout();
  }

  function chooseA4Layout(canvasW, canvasH) {
    const landscape = canvasW >= canvasH;
    const pageW = landscape ? 297 : 210;
    const pageH = landscape ? 210 : 297;
    const maxW = pageW - 16;
    const maxH = pageH - 16;
    const ar = Number(canvasW) / Math.max(1, Number(canvasH));
    let w = maxW;
    let h = w / ar;
    if (h > maxH) {
      h = maxH;
      w = h * ar;
    }
    return { pageWmm: pageW, pageHmm: pageH, imgWmm: w, imgHmm: h };
  }

  function stampScaleOnCanvas(canvas, label) {
    const ctx = canvas.getContext('2d');
    if (!ctx || !label) return;
    const fontSize = Math.max(14, Math.round(Math.min(canvas.width, canvas.height) * 0.018));
    ctx.save();
    ctx.setTransform(1, 0, 0, 1, 0, 0);
    ctx.font = '600 ' + fontSize + 'px Arial, Helvetica, sans-serif';
    const pad = Math.round(fontSize * 0.55);
    const textW = Math.ceil(ctx.measureText(label).width);
    const boxW = textW + pad * 2;
    const boxH = fontSize + pad * 2;
    const x = Math.round(fontSize);
    const y = canvas.height - boxH - Math.round(fontSize);
    ctx.fillStyle = 'rgba(255,255,255,0.92)';
    ctx.strokeStyle = 'rgba(0,0,0,0.35)';
    ctx.lineWidth = 1;
    ctx.fillRect(x, y, boxW, boxH);
    ctx.strokeRect(x, y, boxW, boxH);
    ctx.fillStyle = '#111827';
    ctx.textBaseline = 'middle';
    ctx.fillText(label, x + pad, y + boxH / 2);
    ctx.restore();
  }

  function captureMapCanvas() {
    return new Promise(function (resolve, reject) {
      if (!map || typeof map.once !== 'function') {
        reject(new Error('Karte ist noch nicht bereit.'));
        return;
      }
      let settled = false;
      const finish = function () {
        if (settled) return;
        settled = true;
        try {
          const size = map.getSize();
          if (!size || !(size[0] > 0) || !(size[1] > 0)) {
            reject(new Error('Kartengroesse ungueltig.'));
            return;
          }
          const nodes = map.getViewport().querySelectorAll('.ol-layer canvas, canvas.ol-layer');
          if (!nodes.length) {
            reject(new Error('Kein Kartenbild.'));
            return;
          }
          let pr = (window.devicePixelRatio || 1);
          const cssW0 = nodes[0].clientWidth || parseFloat(nodes[0].style.width) || 0;
          if (cssW0 > 0 && nodes[0].width > 0) pr = nodes[0].width / cssW0;
          if (!(pr > 0)) pr = 1;
          const out = document.createElement('canvas');
          out.width = Math.max(1, Math.round(size[0] * pr));
          out.height = Math.max(1, Math.round(size[1] * pr));
          const ctx = out.getContext('2d');
          if (!ctx) {
            reject(new Error('Canvas nicht verfuegbar.'));
            return;
          }
          ctx.fillStyle = '#ffffff';
          ctx.fillRect(0, 0, out.width, out.height);
          Array.prototype.forEach.call(nodes, function (canvas) {
            if (!canvas || !(canvas.width > 0) || !(canvas.height > 0)) return;
            const parent = canvas.parentNode;
            const opacity = parent && parent.style && parent.style.opacity !== '' ? Number(parent.style.opacity) : 1;
            ctx.globalAlpha = Number.isFinite(opacity) ? opacity : 1;
            const transform = canvas.style && canvas.style.transform ? canvas.style.transform : '';
            const matched = /^matrix\(([^)]+)\)$/.exec(transform);
            if (matched) {
              const matrix = matched[1].split(',').map(function (v) { return Number(String(v).trim()); });
              if (matrix.length === 6 && matrix.every(function (n) { return Number.isFinite(n); })) {
                ctx.setTransform(matrix[0] * pr, matrix[1] * pr, matrix[2] * pr, matrix[3] * pr, matrix[4] * pr, matrix[5] * pr);
                ctx.drawImage(canvas, 0, 0);
                return;
              }
            }
            const cssW = parseFloat(canvas.style.width) || (canvas.width / pr);
            const cssH = parseFloat(canvas.style.height) || (canvas.height / pr);
            ctx.setTransform(pr, 0, 0, pr, 0, 0);
            ctx.drawImage(canvas, 0, 0, cssW, cssH);
          });
          ctx.globalAlpha = 1;
          ctx.setTransform(1, 0, 0, 1, 0, 0);
          const denom = selectedPdfScaleDenom();
          const label = denom ? ('Massstab ' + formatSwissScaleDenom(denom)) : 'Aktuelle Kartenansicht';
          stampScaleOnCanvas(out, label);
          try {
            out.toDataURL('image/jpeg', 0.92);
          } catch (err) {
            reject(new Error('Kartenbild ist gesperrt (CORS).'));
            return;
          }
          resolve(out);
        } catch (err) {
          reject(err);
        }
      };
      const timer = setTimeout(finish, 5000);
      map.once('rendercomplete', function () {
        clearTimeout(timer);
        finish();
      });
      try {
        map.renderSync();
      } catch (err) {
        clearTimeout(timer);
        reject(err);
      }
    });
  }

  function canvasToJpegBytes(canvas) {
    const dataUrl = canvas.toDataURL('image/jpeg', 0.92);
    const b64 = dataUrl.indexOf(',') >= 0 ? dataUrl.slice(dataUrl.indexOf(',') + 1) : dataUrl;
    const bin = atob(b64);
    const out = new Uint8Array(bin.length);
    for (let i = 0; i < bin.length; i += 1) out[i] = bin.charCodeAt(i) & 255;
    return out;
  }

  function jpegBytesToPdfBlob(jpegBytes, imgW, imgH, layout) {
    const w = Math.max(1, Math.round(Number(imgW) || 1));
    const h = Math.max(1, Math.round(Number(imgH) || 1));
    const mmToPt = 72 / 25.4;
    const pageW = Number(layout.pageWmm) * mmToPt;
    const pageH = Number(layout.pageHmm) * mmToPt;
    const drawW = Number(layout.imgWmm) * mmToPt;
    const drawH = Number(layout.imgHmm) * mmToPt;
    const drawX = (pageW - drawW) / 2;
    const drawY = (pageH - drawH) / 2;
    const fmt = function (n) { return Number(n).toFixed(2); };
    const encoder = new TextEncoder();
    const parts = [];
    const offsets = [0];
    let offset = 0;
    const push = function (chunk) {
      const bytes = typeof chunk === 'string' ? encoder.encode(chunk) : chunk;
      parts.push(bytes);
      offset += bytes.length;
    };
    const pushObj = function (body) {
      offsets.push(offset);
      push(body);
    };
    push('%PDF-1.4\n');
    pushObj('1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n');
    pushObj('2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n');
    pushObj('3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ' + fmt(pageW) + ' ' + fmt(pageH) + '] /Resources << /XObject << /Im0 4 0 R >> >> /Contents 5 0 R >>\nendobj\n');
    offsets.push(offset);
    push('4 0 obj\n<< /Type /XObject /Subtype /Image /Width ' + w + ' /Height ' + h + ' /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length ' + jpegBytes.length + ' >>\nstream\n');
    push(jpegBytes);
    push('\nendstream\nendobj\n');
    const content = 'q\n' + fmt(drawW) + ' 0 0 ' + fmt(drawH) + ' ' + fmt(drawX) + ' ' + fmt(drawY) + ' cm\n/Im0 Do\nQ\n';
    pushObj('5 0 obj\n<< /Length ' + content.length + ' >>\nstream\n' + content + 'endstream\nendobj\n');
    const xrefStart = offset;
    let xref = 'xref\n0 ' + offsets.length + '\n0000000000 65535 f \n';
    for (let i = 1; i < offsets.length; i += 1) {
      xref += String(offsets[i]).padStart(10, '0') + ' 00000 n \n';
    }
    push(xref);
    push('trailer\n<< /Size ' + offsets.length + ' /Root 1 0 R >>\nstartxref\n' + xrefStart + '\n%%EOF\n');
    const total = parts.reduce(function (n, p) { return n + p.length; }, 0);
    const out = new Uint8Array(total);
    let pos = 0;
    for (let j = 0; j < parts.length; j += 1) {
      out.set(parts[j], pos);
      pos += parts[j].length;
    }
    return new Blob([out], { type: 'application/pdf' });
  }

  function downloadBlob(filename, blob) {
    const a = document.createElement('a');
    const objectUrl = URL.createObjectURL(blob);
    a.href = objectUrl;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    a.remove();
    setTimeout(function () { URL.revokeObjectURL(objectUrl); }, 1500);
  }

  function pdfFilename(scaleLabel) {
    const now = new Date();
    const y = now.getFullYear();
    const m = String(now.getMonth() + 1).padStart(2, '0');
    const d = String(now.getDate()).padStart(2, '0');
    const scaleBit = scaleLabel ? ('-' + scaleLabel.replace(/:/g, '').replace(/'/g, '')) : '';
    return y + m + d + '-Flugfelder' + scaleBit + '.pdf';
  }

  function snapshotMapView() {
    if (!map || !map.getView) return null;
    const view = map.getView();
    const center = view.getCenter();
    return {
      center: center ? center.slice() : null,
      resolution: view.getResolution(),
    };
  }

  function restoreMapView(snap) {
    if (!snap || !map || !map.getView) return;
    const view = map.getView();
    scaleApplyLock += 1;
    try {
      if (snap.center) view.setCenter(snap.center);
      if (snap.resolution > 0) view.setResolution(snap.resolution);
    } catch (_) { /* noop */ }
    scaleApplyLock -= 1;
  }

  async function exportCurrentMapPdf() {
    if (!map || !map.getView) throw new Error('Karte ist noch nicht bereit.');
    const size = map.getSize();
    if (!size || !(size[0] > 0) || !(size[1] > 0)) throw new Error('Kartengroesse ungueltig.');
    const paperGuess = chooseA4Layout(size[0], size[1]);
    const denom = selectedPdfScaleDenom();
    const snap = snapshotMapView();
    const scaled = denom > 0 && applyPaperScale(denom, paperGuess.imgWmm, size[0]);
    let canvas;
    try {
      canvas = await captureMapCanvas();
    } finally {
      if (scaled) restoreMapView(snap);
    }
    const paper = scaled ? paperGuess : chooseA4Layout(canvas.width, canvas.height);
    const labelDenom = scaled ? denom : paperDenomForWidth(paper.imgWmm, size[0]);
    const scaleLabel = labelDenom ? formatSwissScaleDenom(labelDenom) : '';
    const filename = pdfFilename(scaleLabel);
    const dataUrl = canvas.toDataURL('image/jpeg', 0.92);
    try {
      const res = await fetch('map/export_map_pdf.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          image: dataUrl,
          filename: filename,
          title: 'Flugfelder',
          printWidthMm: paper.imgWmm,
          printHeightMm: paper.imgHmm,
          scale: scaleLabel,
        }),
      });
      const ctype = String(res.headers.get('content-type') || '').toLowerCase();
      if (res.ok && ctype.indexOf('application/pdf') !== -1) {
        const blob = await res.blob();
        if (blob && blob.size > 64) {
          downloadBlob(filename, blob);
          return filename;
        }
      }
    } catch (_) { /* lokaler PDF-Fallback */ }
    const jpeg = canvasToJpegBytes(canvas);
    downloadBlob(filename, jpegBytesToPdfBlob(jpeg, canvas.width, canvas.height, paper));
    return filename;
  }

  function removeMapPrintSheet() {
    const sheet = document.getElementById('rkrkPrintSheet');
    if (sheet && sheet.parentNode) sheet.parentNode.removeChild(sheet);
    if (document.body && document.body.classList) {
      document.body.classList.remove('rkrk-print-ready');
      document.body.classList.remove('rkrk-print-land');
      document.body.classList.remove('rkrk-print-port');
    }
  }

  function mountMapPrintSheet(canvas) {
    removeMapPrintSheet();
    const landscape = canvas.width >= canvas.height;
    const sheet = document.createElement('div');
    sheet.id = 'rkrkPrintSheet';
    const img = document.createElement('img');
    img.alt = 'Flugfelder';
    img.src = canvas.toDataURL('image/jpeg', 0.92);
    sheet.appendChild(img);
    document.body.appendChild(sheet);
    document.body.classList.add('rkrk-print-ready');
    document.body.classList.add(landscape ? 'rkrk-print-land' : 'rkrk-print-port');
    return img;
  }

  function openMapPrintDialog(allowPdfFallback) {
    if (allowPdfFallback !== false) allowPdfFallback = true;
    let denom = 0;
    try { denom = selectedPdfScaleDenom(); } catch (_) { denom = 0; }
    let scaled = false;
    let snap = null;
    try {
      const size = map && map.getSize ? map.getSize() : null;
      if (denom > 0 && size && size[0] > 0 && size[1] > 0) {
        const paper = chooseA4Layout(size[0], size[1]);
        snap = snapshotMapView();
        scaled = !!applyPaperScale(denom, paper.imgWmm, size[0]);
      }
    } catch (_) { scaled = false; }

    const finishStatus = function () {
      setShareStatus(denom
        ? ('Druckdialog, Massstab ' + formatSwissScaleDenom(denom) + '.')
        : 'Druckdialog der aktuellen Ansicht.');
    };
    const pdfFallback = function (err) {
      removeMapPrintSheet();
      if (!allowPdfFallback) {
        setShareStatus((err && err.message) ? err.message : 'Drucken fehlgeschlagen.');
        return;
      }
      exportCurrentMapPdf().then(function (name) {
        setShareStatus('PDF gespeichert: ' + name);
      }).catch(function () {
        setShareStatus((err && err.message) ? err.message : 'Drucken fehlgeschlagen.');
      });
    };
    const restoreScaled = function () {
      if (!scaled) return;
      scaled = false;
      try { restoreMapView(snap); } catch (_) { /* noop */ }
    };
    const startPrint = function () {
      let cleaned = false;
      const cleanup = function () {
        if (cleaned) return;
        cleaned = true;
        window.removeEventListener('afterprint', cleanup);
        removeMapPrintSheet();
      };
      window.addEventListener('afterprint', cleanup);
      setTimeout(cleanup, 120000);
      setTimeout(function () {
        try {
          window.print();
          finishStatus();
        } catch (err) {
          cleanup();
          pdfFallback(err);
        }
      }, 80);
    };

    setShareStatus('Druck der Karte wird vorbereitet …');
    captureMapCanvas().then(function (canvas) {
      restoreScaled();
      let img = null;
      try {
        img = mountMapPrintSheet(canvas);
      } catch (err) {
        pdfFallback(err);
        return;
      }
      if (img && !img.complete) {
        img.onload = function () { startPrint(); };
        img.onerror = function () {
          pdfFallback(new Error('Kartenbild konnte nicht geladen werden.'));
        };
        return;
      }
      startPrint();
    }).catch(function (err) {
      restoreScaled();
      pdfFallback(err);
    });
  }

  function installMapShareActions() {
    const swissBtn = document.getElementById('openSwissMapBtn');
    const shareBtn = document.getElementById('shareViewLinkBtn');
    const kmlBtn = document.getElementById('mapKmlBtn');
    const pdfBtn = document.getElementById('mapPdfBtn');
    const printBtn = document.getElementById('mapPrintBtn');
    installScaleControl();
    if (printBtn && !printBtn.__rkrkBound) {
      printBtn.__rkrkBound = true;
      printBtn.addEventListener('click', function (ev) {
        if (ev && ev.preventDefault) ev.preventDefault();
        if (ev && ev.stopPropagation) ev.stopPropagation();
        openMapPrintDialog();
      });
    }
    if (pdfBtn && !pdfBtn.__rkrkBound) {
      pdfBtn.__rkrkBound = true;
      pdfBtn.addEventListener('click', function () {
        pdfBtn.disabled = true;
        const denom = selectedPdfScaleDenom();
        setShareStatus(denom
          ? ('PDF wird erstellt, Massstab ' + formatSwissScaleDenom(denom) + ' …')
          : 'PDF der aktuellen Kartenansicht wird erstellt …');
        exportCurrentMapPdf().then(function (name) {
          setShareStatus('PDF gespeichert: ' + name);
        }).catch(function (err) {
          const msg = (err && err.message) ? err.message : 'PDF fehlgeschlagen.';
          setShareStatus(msg + ' Druckdialog der Karte.');
          openMapPrintDialog(false);
        }).then(function () {
          pdfBtn.disabled = false;
        });
      });
    }
    if (swissBtn && !swissBtn.__rkrkBound) {
      swissBtn.__rkrkBound = true;
      swissBtn.addEventListener('click', function () {
        try {
          const url = buildSwissTopoUrl();
          const opened = window.open(url, '_blank', 'noopener');
          setShareStatus(opened ? 'SwissTopo mit aktuellem Karteninhalt geöffnet.' : 'Popup blockiert — Link steht unten.');
          if (!opened) showShareQr(url);
        } catch (err) {
          setShareStatus((err && err.message) ? err.message : 'SwissTopo konnte nicht geöffnet werden.');
        }
      });
    }
    if (shareBtn && !shareBtn.__rkrkBound) {
      shareBtn.__rkrkBound = true;
      shareBtn.addEventListener('click', function () {
        let url = '';
        try {
          url = buildShareUrl();
        } catch (_) {
          url = '';
        }
        if (!url) {
          try {
            const form = document.getElementById('map27Form');
            const path = form ? formActionUrl(form, false) : (location.pathname + location.search);
            url = publicMapShareUrl(path).href;
          } catch (err) {
            setShareStatus((err && err.message) ? err.message : 'Teilen-Link konnte nicht erstellt werden.');
            return;
          }
        }
        showShareQr(url);
        copyText(url).then(function (ok) {
          setShareStatus(ok
            ? 'Kartenansicht kopiert: Mitte, Massstab, Ebenen, Region und Felder.'
            : 'Kartenansicht steht unter dem QR-Code.');
        });
      });
    }
    if (kmlBtn && !kmlBtn.__rkrkBound) {
      kmlBtn.__rkrkBound = true;
      kmlBtn.addEventListener('click', function () {
        kmlBtn.disabled = true;
        setShareStatus('KML wird erstellt …');
        downloadCurrentKml().then(function () {
          setShareStatus('KML mit dem aktuellen Karteninhalt heruntergeladen.');
        }).catch(function (err) {
          setShareStatus((err && err.message) ? err.message : 'KML-Download fehlgeschlagen.');
        }).then(function () {
          kmlBtn.disabled = false;
        });
      });
    }
  }

  function boot() {
    document.documentElement.classList.add('rkrk-map27');
    document.body.classList.add('rkrk-map27-page');
    if (cfg.menuOn) {
      document.body.classList.add('rkrk-map27-has-header');
    } else {
      document.documentElement.classList.add('rkrk-map27-embed');
      document.documentElement.setAttribute('data-rkrk-layout', 'off');
    }

    const lon = Number(cfg.lon);
    const lat = Number(cfg.lat);
    const center = ol.proj.fromLonLat([
      Number.isFinite(lon) ? lon : 8.45,
      Number.isFinite(lat) ? lat : 47.22,
    ]);
    const zoom = geoAdminZoomToOl(cfg.zoom);

    popupOverlay = new ol.Overlay({
      element: $('#map27Popup'),
      autoPan: { animation: { duration: 180 } },
      positioning: 'bottom-center',
      offset: [0, -8],
    });

    map = new ol.Map({
      target: 'map',
      view: new ol.View({
        projection: 'EPSG:3857',
        center: center,
        zoom: zoom,
        maxZoom: 19,
      }),
      overlays: [popupOverlay],
    });
    try {
      map.addControl(new ol.control.ScaleLine({ units: 'metric' }));
    } catch (_) { /* noop */ }

    switchBasemap(String(cfg.mapType || 'S').toUpperCase());
    global.__rkrkMap27 = map;
    global.rkrkUpdateMapSize = function (refit) {
      try { map.updateSize(); } catch (_) { /* noop */ }
      const shouldFit = (refit || !!fFilterIds().length) && cfg.fitExtent && kmlLayer && kmlLayer.getSource;
      if (shouldFit) {
        fitVectorExtent(kmlLayer.getSource());
      }
    };

    applyStoredOverlayUi();
    bindOverlayUi();
    document.querySelectorAll('.overlay-toggle').forEach((cb) => {
      if (cb.checked) setOverlayEnabled(cb.getAttribute('data-overlay-key'), true);
    });

    if (window.RkrkSwissOverlays && typeof window.RkrkSwissOverlays.attachUasIdentify === 'function') {
      uasCtl = window.RkrkSwissOverlays.attachUasIdentify(map, {
        popupEl: $('#map27Popup'),
        popupBody: $('#map27PopupBody'),
        closeId: 'map27PopupClose',
        popupOverlay: popupOverlay,
      });
      const uasToggle = document.querySelector('.overlay-toggle[data-overlay-key="uasZones"]');
      if (uasToggle && uasToggle.checked) uasCtl.enable();
      if (uasToggle) {
        uasToggle.addEventListener('change', function () {
          if (uasToggle.checked) uasCtl.enable();
          else uasCtl.disable();
        });
      }
    }
    const obstaclesToggle = document.querySelector('.overlay-toggle[data-overlay-key="obstacles"]');
    if (obstaclesToggle && obstaclesToggle.checked && obstInfoCtl) obstInfoCtl.enable();

    map.on('moveend', scheduleObstacleRefresh);

    const closeBtn = $('#map27PopupClose');
    if (closeBtn) closeBtn.addEventListener('click', hidePopup);
    map.on('click', function (evt) {
      hidePopup();
      const hits = [];
      map.forEachFeatureAtPixel(evt.pixel, function (f, layer) {
        hits.push({ feature: f, layer: layer });
      }, {
        layerFilter: function (layer) {
          return layer === kmlLayer
            || (layer && layer.get && (layer.get('rkrkObstacleVector') || layer.get('rkrkDabsVector') || layer.get('rkrkUasVector')));
        },
        hitTolerance: 3,
      });
      let dabsHit = null;
      let obstacleHit = null;
      let uasHit = null;
      let kmlHit = null;
      hits.forEach(function (h) {
        if (h.layer && h.layer.get && h.layer.get('rkrkDabsVector')) {
          if (!dabsHit) dabsHit = h.feature;
        } else if (h.layer && h.layer.get && h.layer.get('rkrkUasVector')) {
          if (!uasHit) uasHit = h.feature;
        } else if (h.layer && h.layer.get && h.layer.get('rkrkObstacleVector')) {
          if (!obstacleHit) obstacleHit = h.feature;
        } else if (!kmlHit) {
          kmlHit = h.feature;
        }
      });
      if (dabsHit) {
        showPopup(evt.coordinate, dabsPopupHtml(dabsHit));
        return;
      }
      if (uasHit) {
        const attrs = uasHit.get('attributes') || {};
        const html = (window.RkrkSwissOverlays && typeof window.RkrkSwissOverlays.uasPopupHtml === 'function')
          ? window.RkrkSwissOverlays.uasPopupHtml(attrs)
          : ('<div class="pop-title">' + (uasHit.get('name') || 'UAS Aktivitätszone') + '</div>');
        showPopup(evt.coordinate, html);
        return;
      }
      if (obstacleHit) {
        const attrs = obstacleHit.get('attributes') || {};
        const html = (window.RkrkSwissOverlays && typeof window.RkrkSwissOverlays.obstaclePopupHtml === 'function')
          ? window.RkrkSwissOverlays.obstaclePopupHtml(attrs)
          : ('<div class="pop-title">' + (obstacleHit.get('name') || 'Luftfahrthindernis') + '</div>');
        showPopup(evt.coordinate, html);
        return;
      }
      if (kmlHit) {
        const geomType = kmlHit.getGeometry() ? String(kmlHit.getGeometry().getType()) : '';
        const kmlIsPoly = geomType.indexOf('Polygon') >= 0;
        if (!kmlIsPoly || !(uasCtl && uasCtl.isEnabled())) {
          const info = featureLabel(kmlHit);
          let html = '';
          if (info.name) html += '<div class="pop-title">' + info.name + '</div>';
          if (info.description) html += '<div class="pop-desc">' + info.description + '</div>';
          if (!html) html = '<div>Objekt</div>';
          showPopup(evt.coordinate, html);
          return;
        }
      }
      const afterIdentify = function (hit) {
        if (hit || !kmlHit) return;
        const info = featureLabel(kmlHit);
        let html = '';
        if (info.name) html += '<div class="pop-title">' + info.name + '</div>';
        if (info.description) html += '<div class="pop-desc">' + info.description + '</div>';
        if (!html) html = '<div>Objekt</div>';
        showPopup(evt.coordinate, html);
      };
      if (window.RkrkSwissOverlays && typeof window.RkrkSwissOverlays.runIdentifyChain === 'function') {
        window.RkrkSwissOverlays.runIdentifyChain(evt, [uasCtl, obstInfoCtl]).then(afterIdentify);
      } else {
        afterIdentify(false);
      }
    });

    loadKml();
    installForm();
    installMapShareActions();
    window.addEventListener('resize', function () {
      if (typeof global.rkrkUpdateMapSize === 'function') {
        global.rkrkUpdateMapSize(!!fFilterIds().length);
      }
    });
    setTimeout(function () {
      if (typeof global.rkrkUpdateMapSize === 'function') {
        global.rkrkUpdateMapSize(true);
      }
    }, 200);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})(window);
