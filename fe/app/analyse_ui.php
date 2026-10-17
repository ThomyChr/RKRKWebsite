<?php
?><!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>RKRK Feldanalyse</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ol@7.5.2/ol.css" />
  <style>
    body { margin: 0; font-family: Arial, sans-serif; background: #f4f6f8; }
    .wrap { display: grid; grid-template-columns: 380px 1fr; height: 100vh; }
    .left { padding: 14px; overflow: auto; border-right: 1px solid #d9dde2; background: #fff; }
    .row { margin-bottom: 10px; }
    label { display: block; font-size: 12px; margin-bottom: 4px; color: #333; }
    input, select, button { width: 100%; padding: 8px; box-sizing: border-box; }
    button { background: #577a47; color: #fff; border: 0; cursor: pointer; }
    button:hover { background: #47663a; }
    .kpi { background: #f8fafb; border: 1px solid #d9dde2; padding: 8px; margin: 8px 0; font-size: 13px; }
    .map { width: 100%; height: 100vh; }
    .small { font-size: 12px; color: #666; }
    .good { color: #1d6d36; font-weight: bold; }
    .bad { color: #9d1d1d; font-weight: bold; }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="left">
      <h2 style="margin-top:0;">Feldanalyse</h2>
      <div class="small">Web-gesteuerter Vergleich: manuell optimierter Flugweg vs. Algorithmus</div>
      <div class="row">
        <label>Feld</label>
        <select id="fieldSelect"></select>
      </div>
      <div class="row">
        <label>Planner</label>
        <select id="plannerMode">
          <option value="php">PHP (schnell)</option>
          <option value="python">Python StdLib (präziser)</option>
        </select>
      </div>
      <div class="row"><label>HFOV (Grad)</label><input id="hfov" type="number" value="48" /></div>
      <div class="row"><label>Ziel-AGL (m)</label><input id="agl" type="number" value="50" /></div>
      <div class="row"><label>Überlappung (0..1)</label><input id="overlap" type="number" step="0.01" value="0.15" /></div>
      <div class="row"><label>Randfaktor</label><input id="edgeFactor" type="number" step="0.01" value="0.40" /></div>
      <div class="row"><label>Geschwindigkeit (m/s)</label><input id="speed" type="number" step="0.1" value="8" /></div>
      <div class="row"><button id="runBtn">Analyse starten</button></div>
      <div id="metrics"></div>
    </div>
    <div id="map" class="map"></div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/ol@7.5.2/dist/ol.js"></script>
  <script>
    const fieldSelect = document.getElementById('fieldSelect');
    const runBtn = document.getElementById('runBtn');
    const metricsEl = document.getElementById('metrics');
    const hfovEl = document.getElementById('hfov');
    const aglEl = document.getElementById('agl');
    const overlapEl = document.getElementById('overlap');
    const edgeFactorEl = document.getElementById('edgeFactor');
    const speedEl = document.getElementById('speed');
    const plannerModeEl = document.getElementById('plannerMode');

    const osm = new ol.layer.Tile({ source: new ol.source.OSM() });
    const source = new ol.source.Vector();
    const vector = new ol.layer.Vector({ source });
    const map = new ol.Map({
      target: 'map',
      layers: [osm, vector],
      view: new ol.View({ center: ol.proj.fromLonLat([8.54, 47.28]), zoom: 13 }),
    });

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
        stroke: new ol.style.Stroke({ color: '#1e3d59', width: 2 }),
        fill: new ol.style.Fill({ color: 'rgba(30,61,89,0.1)' }),
      }));
      return feat;
    }

    function metricLine(label, value, cls='') {
      return `<div class="kpi ${cls}"><strong>${label}:</strong> ${value}</div>`;
    }

    async function loadFields() {
      const resp = await fetch('api_fields.php', { cache: 'no-store' });
      const data = await resp.json();
      fieldSelect.innerHTML = '';
      data.fields.forEach(f => {
        const opt = document.createElement('option');
        opt.value = f.id;
        opt.textContent = `${f.feld_id} - ${f.name}`;
        fieldSelect.appendChild(opt);
      });
    }

    async function runAnalysis() {
      const q = new URLSearchParams({
        field_id: fieldSelect.value,
        hfov: hfovEl.value,
        agl: aglEl.value,
        overlap: overlapEl.value,
        edge_factor: edgeFactorEl.value,
        speed: speedEl.value,
        planner: plannerModeEl.value,
      });
      const resp = await fetch(`api_analyze.php?${q.toString()}`, { cache: 'no-store' });
      const data = await resp.json();

      source.clear();
      source.addFeature(polyFeature(data.field.polygon));
      source.addFeature(lineFeature(data.manual.path, '#a12d2d'));
      source.addFeature(lineFeature(data.algorithm.path, '#1d6d36'));
      map.getView().fit(source.getExtent(), { padding: [40, 40, 40, 40], maxZoom: 18 });

      const d = data.improvement.distance_pct;
      const t = data.improvement.time_pct;
      const h = data.improvement.height_delta_pct;
      metricsEl.innerHTML =
        metricLine('Berechnungsmodus', data.planner === 'python' ? 'Python StdLib' : 'PHP') +
        metricLine('Sichtbreite W', `${data.formula.W.toFixed(2)} m`) +
        metricLine('Spurabstand S', `${data.formula.S.toFixed(2)} m`) +
        metricLine('Manuell Distanz', `${data.manual.metrics.distance_m.toFixed(1)} m`) +
        metricLine('Algorithmus Distanz', `${data.algorithm.metrics.distance_m.toFixed(1)} m`) +
        metricLine('Verbesserung Distanz', `${d.toFixed(2)} %`, d >= 0 ? 'good' : 'bad') +
        metricLine('Verbesserung Flugzeit', `${t.toFixed(2)} %`, t >= 0 ? 'good' : 'bad') +
        metricLine('Verbesserung Höhendifferenz', `${h.toFixed(2)} %`, h >= 0 ? 'good' : 'bad') +
        metricLine('Wegpunkte manuell / algo', `${data.manual.metrics.waypoints} / ${data.algorithm.metrics.waypoints}`);
    }

    runBtn.addEventListener('click', runAnalysis);
    loadFields().then(runAnalysis);
  </script>
</body>
</html>

