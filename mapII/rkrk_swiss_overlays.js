/**
 * Shared SwissTopo overlay helpers for map editors: obstacle WMS (no tile date),
 * height labels, and DABS polygons/circles.
 */
(function (global) {
  'use strict';

  var OBSTACLE_LAYER_ID = 'ch.bazl.luftfahrthindernis';
  var OBSTACLE_WMS_LAYERS = 'ch.bazl.luftfahrthindernis_line,ch.bazl.luftfahrthindernis_point,ch.bazl.luftfahrthindernis_poly';

  function escapeHtml(s) {
    return String(s || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function formatObstacleHeight(attrs) {
    if (!attrs || typeof attrs !== 'object') return '';
    var aglRaw = attrs.maxheightagl != null ? attrs.maxheightagl : attrs.maxHeightAgl;
    var amslRaw = attrs.topelevationamsl != null ? attrs.topelevationamsl : attrs.topElevationAmsl;
    var agl = aglRaw != null && aglRaw !== '' ? String(aglRaw).replace(/\.0$/, '') : '';
    var amsl = amslRaw != null && amslRaw !== '' ? String(amslRaw).replace(/\.0$/, '') : '';
    if (agl && amsl) return agl + ' m / ' + amsl + ' m ü.M.';
    if (agl) return agl + ' m';
    if (amsl) return amsl + ' m ü.M.';
    return '';
  }

  var OBSTACLE_TYPE_DE = {
    TRANSMISSION_LINE: 'Freileitung',
    BRIDGE: 'Brücke',
    BUILDING: 'Gebäude',
    ANTENNA: 'Antenne',
    MAST: 'Mast',
    TOWER: 'Turm',
    CRANE: 'Kran',
    WIND_TURBINE: 'Windkraftanlage',
    WINDMILL: 'Windkraftanlage',
    CHIMNEY: 'Kamin',
    TREE: 'Baum',
    CABLE: 'Seil',
    POLE: 'Mast',
    PYLON: 'Mast',
  };
  var OBSTACLE_MARKING_DE = {
    CABLE_WARNER: 'Seilwarner',
    MARKED: 'markiert',
    NONE: 'keine',
    ORANGE_MARKS: 'orange Markierung',
  };
  var OBSTACLE_LIGHTING_DE = {
    NONE: 'keine',
  };

  function prettyEnum(value, dict) {
    var s = String(value || '').trim();
    if (!s || s === '-' || s.toLowerCase() === 'null') return '';
    if (dict && dict[s]) return dict[s];
    return s.replace(/_/g, ' ');
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

  function wmsLayerIds(layerId) {
    return layerId === OBSTACLE_LAYER_ID ? OBSTACLE_WMS_LAYERS : layerId;
  }

  function createWmsLayer(layerId, opacity, zIndex) {
    var isObst = layerId === OBSTACLE_LAYER_ID;
    return new ol.layer.Tile({
      source: new ol.source.TileWMS({
        url: 'https://wms.geo.admin.ch/',
        params: {
          LAYERS: wmsLayerIds(layerId),
          FORMAT: 'image/png',
          TRANSPARENT: true,
        },
        crossOrigin: 'anonymous',
        attributions: 'Map data: <a href="https://www.swisstopo.admin.ch/" target="_blank" rel="noopener">swisstopo</a>',
      }),
      opacity: Number.isFinite(opacity) ? opacity : 1,
      zIndex: zIndex || (isObst ? 90 : 40),
    });
  }

  function attachObstacleHeights(map, options) {
    options = options || {};
    var checkboxId = options.checkboxId || 'obstacleHeights';
    var source = null;
    var layer = null;
    var timer = null;
    var token = 0;
    var moveBound = false;

    function heightsOn() {
      var el = document.getElementById(checkboxId);
      return el ? !!el.checked : true;
    }

    function styleFeature(feature, resolution) {
      var attrs = feature.get('attributes') || {};
      var label = formatObstacleHeight(attrs);
      var type = feature.getGeometry() ? feature.getGeometry().getType() : '';
      var showLabel = heightsOn() && !!label && resolution < 20;
      var text = showLabel
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

    function refresh() {
      if (!map || !source) return;
      var zoom = map.getView().getZoom();
      if (!Number.isFinite(zoom) || zoom < 8) {
        source.clear();
        return;
      }
      var size = map.getSize() || [800, 600];
      var extent = map.getView().calculateExtent(size);
      var t = ++token;
      var geom = extent.map(function (n) { return Math.round(n); }).join(',');
      var url = 'https://api3.geo.admin.ch/rest/services/api/MapServer/identify'
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
        if (t !== token || !source) return;
        var rows = Array.isArray(payload && payload.results) ? payload.results : [];
        var features = [];
        rows.forEach(function (row) {
          var attrs = row.attributes || row.attrs || {};
          if (!formatObstacleHeight(attrs)) return;
          var geomObj = geometryFromIdentify(row.geometry || row.geom);
          if (!geomObj) return;
          var f = new ol.Feature({ geometry: geomObj });
          f.set('attributes', attrs);
          f.set('name', formatObstacleHeight(attrs));
          features.push(f);
        });
        source.clear();
        if (features.length) source.addFeatures(features);
      }).catch(function () { /* keep previous */ });
    }

    function schedule() {
      if (!source) return;
      if (timer) clearTimeout(timer);
      timer = setTimeout(refresh, 280);
    }

    function onMoveEnd() { schedule(); }

    function enable(opacity) {
      disable();
      source = new ol.source.Vector();
      layer = new ol.layer.Vector({
        source: source,
        style: styleFeature,
        zIndex: 96,
        opacity: Number.isFinite(opacity) ? opacity : 1,
        declutter: true,
      });
      layer.set('rkrkObstacleVector', true);
      map.addLayer(layer);
      if (!moveBound) {
        map.on('moveend', onMoveEnd);
        moveBound = true;
      }
      var cb = document.getElementById(checkboxId);
      if (cb && !cb.getAttribute('data-rkrk-height-bound')) {
        cb.setAttribute('data-rkrk-height-bound', '1');
        cb.addEventListener('change', function () {
          if (source && typeof source.changed === 'function') source.changed();
        });
      }
      refresh();
      return layer;
    }

    function disable() {
      if (timer) {
        clearTimeout(timer);
        timer = null;
      }
      if (layer) {
        map.removeLayer(layer);
        layer = null;
      }
      source = null;
    }

    function setOpacity(op) {
      if (layer) layer.setOpacity(op);
    }

    return { enable: enable, disable: disable, setOpacity: setOpacity, getLayer: function () { return layer; } };
  }

  function dabsVersionLine(feature, meta) {
    var date = String((feature && feature.get && feature.get('dabsDate')) || (meta && meta.date) || '').trim();
    var ver = String((feature && feature.get && feature.get('dabsVersion')) || (meta && meta.version) || '').trim();
    var verNum = ver.match(/(\d+)/);
    if (verNum) {
      ver = 'Version ' + verNum[1];
    } else if (ver && !/^version\b/i.test(ver)) {
      ver = 'Version ' + ver.replace(/^[:\s]+/, '');
    }
    var parts = [];
    if (date) parts.push(date);
    if (ver) parts.push(ver);
    if (!parts.length) return '';
    return 'DABS ' + parts.join(' ');
  }

  function dabsPopupHtml(feature, meta) {
    var name = escapeHtml(feature.get('name') || 'DABS');
    var validity = escapeHtml(feature.get('validity') || '');
    var lower = escapeHtml(feature.get('lower') || '');
    var upper = escapeHtml(feature.get('upper') || '');
    var radius = escapeHtml(feature.get('radius') || '');
    var remark = escapeHtml(feature.get('remark') || feature.get('description') || '');
    var notam = escapeHtml(feature.get('notam') || '');
    var html = '<div class="pop-title">' + name + '</div>';
    var bits = [];
    if (validity) bits.push(validity + ' UTC');
    if (lower || upper) bits.push((lower || '?') + ' – ' + (upper || '?'));
    if (radius) bits.push(radius);
    if (bits.length) html += '<div class="pop-desc">' + bits.join(' · ') + '</div>';
    if (remark) html += '<div class="pop-desc"><b>Bemerkung</b><br>' + remark + '</div>';
    if (notam) html += '<div class="pop-desc">NOTAM ' + notam + '</div>';
    html += '<div class="pop-desc"><i>Nur Segment GND–120 m AGL. Quelle: Skyguide DABS, ersetzt keine NOTAMs.</i></div>';
    var verLine = dabsVersionLine(feature, meta);
    if (verLine) html += '<div class="pop-desc">' + escapeHtml(verLine) + '</div>';
    return html;
  }

  function styleDabsFeature(feature, resolution) {
    var isPoly = String(feature.get('shape') || '') === 'polygon';
    var name = String(feature.get('area') || feature.get('notam') || feature.get('name') || '');
    var res = Number(resolution);
    var showLabel = !!name && Number.isFinite(res) && res < 140;
    return new ol.style.Style({
      stroke: new ol.style.Stroke({
        color: '#cc0000',
        width: isPoly ? 2.8 : 2.2,
        lineJoin: 'round',
        lineDash: isPoly ? undefined : [7, 5],
      }),
      fill: new ol.style.Fill({ color: isPoly ? 'rgba(204,0,0,0.10)' : 'rgba(204,0,0,0.05)' }),
      text: showLabel
        ? new ol.style.Text({
            text: name,
            font: 'bold 12px sans-serif',
            fill: new ol.style.Fill({ color: '#cc0000' }),
            stroke: new ol.style.Stroke({ color: 'rgba(255,255,255,0.95)', width: 3 }),
            overflow: true,
          })
        : undefined,
    });
  }

  function dabsCircleGeometry(lon, lat, radiusM) {
    var r = Number(radiusM);
    if (!Number.isFinite(lon) || !Number.isFinite(lat) || !Number.isFinite(r) || r <= 0) {
      return null;
    }
    if (ol.geom && ol.geom.Polygon && typeof ol.geom.Polygon.circular === 'function') {
      try {
        var poly = ol.geom.Polygon.circular([lon, lat], r, 64);
        poly.transform('EPSG:4326', 'EPSG:3857');
        return poly;
      } catch (_) { /* fallback */ }
    }
    var center = ol.proj.fromLonLat([lon, lat]);
    var latM = Math.cos((lat * Math.PI) / 180);
    var radius3857 = latM > 0.2 ? (r / latM) : r;
    return ol.geom.Polygon.fromCircle(new ol.geom.Circle(center, radius3857), 64);
  }

  function dabsGeometryFromRow(row) {
    var geomObj = (row && row.geometry) || {};
    var props = (row && row.properties) || {};
    var type = String(geomObj.type || '').toLowerCase();
    if (type === 'polygon' && Array.isArray(geomObj.coordinates) && geomObj.coordinates.length) {
      try {
        var poly = new ol.geom.Polygon(geomObj.coordinates);
        poly.transform('EPSG:4326', 'EPSG:3857');
        return poly;
      } catch (_) { /* fallback */ }
    }
    var coords = Array.isArray(geomObj.coordinates) ? geomObj.coordinates : [];
    var lon = Number(coords.length ? coords[0] : props.lon);
    var lat = Number(coords.length > 1 ? coords[1] : props.lat);
    return dabsCircleGeometry(lon, lat, Number(props.radius_m));
  }

  function findPopupOverlay(map, popupEl) {
    if (!map || !popupEl) return null;
    var shared = map.get && map.get('rkrkInfoPopupOverlay');
    if (shared) return shared;
    var arr = map.getOverlays && map.getOverlays().getArray ? map.getOverlays().getArray() : [];
    for (var i = 0; i < arr.length; i++) {
      if (arr[i] && typeof arr[i].getElement === 'function' && arr[i].getElement() === popupEl) {
        return arr[i];
      }
    }
    return null;
  }

  function bindPopupClose(popupEl, overlay, closeId) {
    var closeBtn = document.getElementById(closeId || 'dabsPopupClose')
      || document.getElementById('map27PopupClose');
    if (!closeBtn || closeBtn.getAttribute('data-rkrk-info-bound')) return;
    closeBtn.setAttribute('data-rkrk-info-bound', '1');
    closeBtn.addEventListener('click', function () {
      if (popupEl) popupEl.style.display = 'none';
      if (overlay) overlay.setPosition(undefined);
    });
  }

  var UAS_LAYER_ID = 'ch.bazl.uas-aktivitaetszonen';
  // Whole-Switzerland envelope (EPSG:3857). UAS zones are nationwide and
  // must not follow the JG / region filter or the current map fit.
  var UAS_CH_EXTENT_3857 = [662000, 5748000, 1170000, 6078000];

  function attrText(attrs, keys) {
    if (!attrs) return '';
    var list = Array.isArray(keys) ? keys : [keys];
    for (var i = 0; i < list.length; i++) {
      var v = attrs[list[i]];
      if (v == null) continue;
      var s = String(v).trim();
      if (s && s !== '-' && s.toLowerCase() !== 'null') return s;
    }
    return '';
  }

  function uasPopupHtml(attrs) {
    attrs = attrs || {};
    var loc = attrText(attrs, ['routelocation_de', 'routelocation_en', 'routelocation_fr', 'routelocation']);
    var desc = attrText(attrs, ['description']);
    var validity = attrText(attrs, ['validity']);
    var start = attrText(attrs, ['startdatetime']);
    var end = attrText(attrs, ['enddatetime']);
    var lower = attrText(attrs, ['lowerlimit']);
    var upper = attrText(attrs, ['upperlimit']);
    var agl = attrText(attrs, ['heightaboveground']);
    var contact = attrText(attrs, ['coordination']);
    var detect = attrText(attrs, ['existingdetectionsystems']);
    var html = '<div class="pop-title">' + escapeHtml(loc || 'UAS Aktivitätszone') + '</div>';
    var bits = [];
    if (validity) bits.push(validity);
    if (lower || upper) bits.push((lower || 'GND') + ' – ' + (upper || '?'));
    if (agl) bits.push(agl + ' AGL');
    if (bits.length) html += '<div class="pop-desc">' + escapeHtml(bits.join(' · ')) + '</div>';
    if (start || end) {
      html += '<div class="pop-desc"><b>Gültigkeit</b><br>' + escapeHtml([start, end].filter(Boolean).join(' – ')) + '</div>';
    }
    if (desc) html += '<div class="pop-desc"><b>Beschreibung</b><br>' + escapeHtml(desc) + '</div>';
    if (contact) html += '<div class="pop-desc"><b>Kontakt</b><br>' + escapeHtml(contact) + '</div>';
    if (detect) html += '<div class="pop-desc"><b>Detektion</b><br>' + escapeHtml(detect) + '</div>';
    html += '<div class="pop-desc"><i>Quelle: BAZL / map.geo.admin.ch, UAS Aktivitätszonen.</i></div>';
    return html;
  }

  function formatUasLabel(attrs) {
    attrs = attrs || {};
    var loc = attrText(attrs, ['routelocation_de', 'routelocation_en', 'routelocation_fr', 'routelocation']);
    var agl = attrText(attrs, ['heightaboveground']);
    var validity = attrText(attrs, ['validity']);
    var lines = [];
    if (loc) lines.push(loc);
    if (agl) lines.push(agl.toLowerCase().indexOf('agl') >= 0 ? agl : agl + ' AGL');
    if (validity) lines.push(validity);
    return lines.join('\n');
  }

  function attachUasLabels(map, options) {
    options = options || {};
    var source = null;
    var layer = null;
    var token = 0;

    function styleFeature(feature) {
      var label = String(feature.get('name') || '');
      return new ol.style.Style({
        stroke: new ol.style.Stroke({ color: 'rgba(212, 85, 0, 0.95)', width: 2.4 }),
        fill: new ol.style.Fill({ color: 'rgba(230, 90, 0, 0.16)' }),
        text: label
          ? new ol.style.Text({
              text: label,
              font: 'bold 12px sans-serif',
              fill: new ol.style.Fill({ color: '#7a1f00' }),
              stroke: new ol.style.Stroke({ color: 'rgba(255,255,255,0.95)', width: 3.5 }),
              overflow: true,
              textAlign: 'center',
            })
          : undefined,
      });
    }

    function refresh() {
      if (!map || !source) return;
      var size = map.getSize() || [800, 600];
      var t = ++token;
      var geom = UAS_CH_EXTENT_3857.join(',');
      var url = 'https://api3.geo.admin.ch/rest/services/api/MapServer/identify'
        + '?geometryType=esriGeometryEnvelope'
        + '&geometry=' + encodeURIComponent(geom)
        + '&imageDisplay=' + Math.round(size[0]) + ',' + Math.round(size[1]) + ',96'
        + '&mapExtent=' + encodeURIComponent(geom)
        + '&tolerance=1'
        + '&layers=' + encodeURIComponent('all:' + UAS_LAYER_ID)
        + '&returnGeometry=true'
        + '&sr=3857'
        + '&lang=de'
        + '&limit=200';
      fetch(url, { cache: 'no-store' }).then(function (res) {
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.json();
      }).then(function (payload) {
        if (t !== token || !source) return;
        var rows = Array.isArray(payload && payload.results) ? payload.results : [];
        var features = [];
        rows.forEach(function (row) {
          var attrs = row.attributes || row.attrs || {};
          var geomObj = geometryFromIdentify(row.geometry || row.geom);
          if (!geomObj) return;
          var f = new ol.Feature({ geometry: geomObj });
          f.set('attributes', attrs);
          f.set('name', formatUasLabel(attrs));
          f.set('description', attrText(attrs, ['description']));
          features.push(f);
        });
        source.clear();
        if (features.length) source.addFeatures(features);
      }).catch(function () { /* keep previous */ });
    }

    function enable(opacity) {
      disable();
      source = new ol.source.Vector();
      layer = new ol.layer.Vector({
        source: source,
        style: styleFeature,
        zIndex: 97,
        opacity: Number.isFinite(opacity) ? opacity : 1,
        declutter: true,
      });
      layer.set('rkrkUasVector', true);
      map.addLayer(layer);
      refresh();
      return layer;
    }

    function disable() {
      if (layer) {
        map.removeLayer(layer);
        layer = null;
      }
      source = null;
    }

    function setOpacity(op) {
      if (layer) layer.setOpacity(op);
    }

    return { enable: enable, disable: disable, setOpacity: setOpacity, getLayer: function () { return layer; } };
  }

  function obstaclePopupHtml(attrs) {
    attrs = attrs || {};
    var height = formatObstacleHeight(attrs);
    var type = prettyEnum(attrText(attrs, ['obstacletype']), OBSTACLE_TYPE_DE);
    var marking = prettyEnum(attrText(attrs, ['marking']), OBSTACLE_MARKING_DE);
    var lighting = prettyEnum(attrText(attrs, ['lighting']), OBSTACLE_LIGHTING_DE);
    var airport = attrText(attrs, ['airport']);
    var reg = attrText(attrs, ['registrationnumber']);
    var effective = attrText(attrs, ['effectivedate']);
    var html = '<div class="pop-title">Luftfahrthindernis' + (type ? ': ' + escapeHtml(type) : '') + '</div>';
    if (height) html += '<div class="pop-desc"><b>Höhe</b><br>' + escapeHtml(height) + '</div>';
    if (marking) html += '<div class="pop-desc"><b>Markierung</b><br>' + escapeHtml(marking) + '</div>';
    if (lighting) html += '<div class="pop-desc"><b>Befeuerung</b><br>' + escapeHtml(lighting) + '</div>';
    if (airport) html += '<div class="pop-desc"><b>Flugplatz</b><br>' + escapeHtml(airport) + '</div>';
    if (reg) html += '<div class="pop-desc"><b>Reg.-Nr.</b><br>' + escapeHtml(reg) + '</div>';
    if (effective) html += '<div class="pop-desc"><b>Inkrafttreten</b><br>' + escapeHtml(effective) + '</div>';
    html += '<div class="pop-desc"><i>Quelle: BAZL / map.geo.admin.ch, Luftfahrthindernisse.</i></div>';
    return html;
  }

  function identifyLayerAtEvent(map, evt, layerId) {
    if (!map || !evt || !layerId) return Promise.resolve(null);
    var coord = evt.coordinate;
    var size = map.getSize() || [800, 600];
    var extent = map.getView().calculateExtent(size);
    var url = 'https://api3.geo.admin.ch/rest/services/api/MapServer/identify'
      + '?geometryType=esriGeometryPoint'
      + '&geometry=' + encodeURIComponent(Number(coord[0]).toFixed(2) + ',' + Number(coord[1]).toFixed(2))
      + '&imageDisplay=' + Math.round(size[0]) + ',' + Math.round(size[1]) + ',96'
      + '&mapExtent=' + encodeURIComponent(extent.map(function (n) { return Math.round(n); }).join(','))
      + '&tolerance=12'
      + '&layers=' + encodeURIComponent('all:' + layerId)
      + '&returnGeometry=false'
      + '&sr=3857'
      + '&lang=de'
      + '&limit=5';
    return fetch(url, { cache: 'no-store' }).then(function (res) {
      if (!res.ok) throw new Error('HTTP ' + res.status);
      return res.json();
    }).then(function (payload) {
      var rows = Array.isArray(payload && payload.results) ? payload.results : [];
      if (!rows.length) return null;
      return rows[0].attributes || rows[0].attrs || rows[0] || null;
    }).catch(function () { return null; });
  }

  function attachWmsIdentify(map, options) {
    options = options || {};
    var layerId = options.layerId;
    var htmlFn = options.htmlFn || function () { return ''; };
    var popupEl = options.popupEl || document.getElementById('dabsPopup') || document.getElementById('map27Popup');
    var popupBody = options.popupBody || document.getElementById('dabsPopupBody') || document.getElementById('map27PopupBody');
    var popupOverlay = options.popupOverlay || null;
    var enabled = false;
    var token = 0;

    function hidePopup() {
      if (popupEl) popupEl.style.display = 'none';
      if (popupOverlay) popupOverlay.setPosition(undefined);
    }

    function ensurePopupOverlay() {
      if (popupOverlay || !popupEl) return popupOverlay;
      popupOverlay = findPopupOverlay(map, popupEl);
      if (popupOverlay) {
        bindPopupClose(popupEl, popupOverlay, options.closeId);
        return popupOverlay;
      }
      popupOverlay = new ol.Overlay({
        element: popupEl,
        autoPan: { animation: { duration: 180 } },
        positioning: 'bottom-center',
        offset: [0, -8],
      });
      map.addOverlay(popupOverlay);
      if (typeof map.set === 'function') map.set('rkrkInfoPopupOverlay', popupOverlay);
      bindPopupClose(popupEl, popupOverlay, options.closeId);
      return popupOverlay;
    }

    function enable() {
      enabled = true;
      ensurePopupOverlay();
    }

    function disable() {
      enabled = false;
      hidePopup();
    }

    function showAtEvent(evt) {
      if (!enabled || !map) return Promise.resolve(false);
      ensurePopupOverlay();
      var t = ++token;
      return identifyLayerAtEvent(map, evt, layerId).then(function (attrs) {
        if (t !== token) return false;
        if (!attrs) {
          hidePopup();
          return false;
        }
        if (!popupEl || !popupBody || !popupOverlay) return false;
        popupBody.innerHTML = htmlFn(attrs);
        popupEl.style.display = 'block';
        popupOverlay.setPosition(evt.coordinate);
        return true;
      });
    }

    return {
      enable: enable,
      disable: disable,
      isEnabled: function () { return enabled; },
      showAtEvent: showAtEvent,
      hidePopup: hidePopup,
    };
  }

  function attachUasIdentify(map, options) {
    options = options || {};
    return attachWmsIdentify(map, {
      layerId: UAS_LAYER_ID,
      htmlFn: uasPopupHtml,
      popupEl: options.popupEl,
      popupBody: options.popupBody,
      popupOverlay: options.popupOverlay,
      closeId: options.closeId,
    });
  }

  function attachObstacleIdentify(map, options) {
    options = options || {};
    return attachWmsIdentify(map, {
      layerId: OBSTACLE_LAYER_ID,
      htmlFn: obstaclePopupHtml,
      popupEl: options.popupEl,
      popupBody: options.popupBody,
      popupOverlay: options.popupOverlay,
      closeId: options.closeId,
    });
  }

  function runIdentifyChain(evt, ctls) {
    var list = (ctls || []).filter(function (c) {
      return c && typeof c.isEnabled === 'function' && c.isEnabled() && typeof c.showAtEvent === 'function';
    });
    function next(i) {
      if (i >= list.length) return Promise.resolve(false);
      return Promise.resolve(list[i].showAtEvent(evt)).then(function (hit) {
        if (hit) return true;
        return next(i + 1);
      });
    }
    return next(0);
  }

  function attachDabs(map, options) {
    options = options || {};
    var url = options.url || 'rkrk_dabs.php';
    var statusEl = options.statusEl || null;
    var popupEl = options.popupEl || document.getElementById('dabsPopup');
    var popupBody = options.popupBody || document.getElementById('dabsPopupBody');
    var popupOverlay = options.popupOverlay || null;
    var source = null;
    var layer = null;
    var meta = null;

    function setStatus(text) {
      if (statusEl) statusEl.textContent = text || '';
    }

    function hidePopup() {
      if (popupEl) popupEl.style.display = 'none';
      if (popupOverlay) popupOverlay.setPosition(undefined);
    }

    function ensurePopupOverlay() {
      if (popupOverlay || !popupEl) return popupOverlay;
      popupOverlay = findPopupOverlay(map, popupEl);
      if (popupOverlay) {
        bindPopupClose(popupEl, popupOverlay, options.closeId);
        return popupOverlay;
      }
      popupOverlay = new ol.Overlay({
        element: popupEl,
        autoPan: { animation: { duration: 180 } },
        positioning: 'bottom-center',
        offset: [0, -8],
      });
      map.addOverlay(popupOverlay);
      if (typeof map.set === 'function') map.set('rkrkInfoPopupOverlay', popupOverlay);
      bindPopupClose(popupEl, popupOverlay, options.closeId);
      return popupOverlay;
    }

    function enable(opacity) {
      disable();
      ensurePopupOverlay();
      source = new ol.source.Vector();
      layer = new ol.layer.Vector({
        source: source,
        style: styleDabsFeature,
        zIndex: 95,
        opacity: Number.isFinite(opacity) ? opacity : 1,
        declutter: false,
      });
      layer.set('overlayKey', 'dabs');
      layer.set('rkrkDabsVector', true);
      map.addLayer(layer);
      load();
      return layer;
    }

    function disable() {
      hidePopup();
      if (layer) {
        map.removeLayer(layer);
        layer = null;
      }
      source = null;
      meta = null;
    }

    function setOpacity(op) {
      if (layer) layer.setOpacity(op);
    }

    function load() {
      if (!source) return;
      setStatus('DABS wird geladen …');
      var sep = String(url).indexOf('?') >= 0 ? '&' : '?';
      fetch(url + sep + 'day=today', { cache: 'no-store' }).then(function (res) {
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.json();
      }).then(function (payload) {
        if (!source) return;
        source.clear();
        meta = null;
        if (!payload || payload.ok === false) {
          setStatus('DABS: ' + ((payload && payload.error) || 'keine Daten'));
          return;
        }
        if (payload.available === false) {
          setStatus((payload.meta && payload.meta.message) || 'DABS noch nicht veröffentlicht');
          return;
        }
        meta = payload.meta || {};
        var rows = Array.isArray(payload.features) ? payload.features : [];
        var features = [];
        rows.forEach(function (row) {
          var props = (row && row.properties) || {};
          var geom = dabsGeometryFromRow(row);
          if (!geom) return;
          var f = new ol.Feature({ geometry: geom });
          Object.keys(props).forEach(function (k) { f.set(k, props[k]); });
          f.set('name', props.area || props.notam || props.name || 'DABS');
          f.set('description', props.remark || '');
          f.set('dabsDate', meta.date || '');
          f.set('dabsVersion', meta.version || '');
          features.push(f);
        });
        if (features.length) source.addFeatures(features);
        var verLine = dabsVersionLine(null, meta) || (meta.date || 'heute');
        var skipped = Number(meta.skipped_high) || 0;
        var polyN = Number(meta.polygons);
        var circN = Number(meta.circles);
        var shapeBit = features.length + ' Gebiete bis 120 m AGL';
        if (Number.isFinite(polyN) && Number.isFinite(circN) && (polyN + circN) > 0) {
          shapeBit = polyN + ' Polygone, ' + circN + ' Kreise';
        }
        setStatus(verLine + ': ' + shapeBit
          + (skipped ? ' (' + skipped + ' höher ausgeblendet)' : ''));
      }).catch(function () {
        if (!source) return;
        setStatus('DABS konnte nicht geladen werden');
      });
    }

    function showAtEvent(evt) {
      if (!map || !source) return false;
      var hit = null;
      map.forEachFeatureAtPixel(evt.pixel, function (f, lyr) {
        if (!hit && lyr && lyr.get && lyr.get('rkrkDabsVector')) hit = f;
      }, { hitTolerance: 3 });
      if (!hit) {
        hidePopup();
        return false;
      }
      ensurePopupOverlay();
      if (!popupEl || !popupBody || !popupOverlay) return false;
      popupBody.innerHTML = dabsPopupHtml(hit, meta);
      popupEl.style.display = 'block';
      popupOverlay.setPosition(evt.coordinate);
      return true;
    }

    return {
      enable: enable,
      disable: disable,
      setOpacity: setOpacity,
      showAtEvent: showAtEvent,
      hidePopup: hidePopup,
      getLayer: function () { return layer; },
    };
  }

  global.RkrkSwissOverlays = {
    OBSTACLE_LAYER_ID: OBSTACLE_LAYER_ID,
    OBSTACLE_WMS_LAYERS: OBSTACLE_WMS_LAYERS,
    UAS_LAYER_ID: UAS_LAYER_ID,
    createWmsLayer: createWmsLayer,
    wmsLayerIds: wmsLayerIds,
    attachObstacleHeights: attachObstacleHeights,
    attachDabs: attachDabs,
    attachUasIdentify: attachUasIdentify,
    attachUasLabels: attachUasLabels,
    attachObstacleIdentify: attachObstacleIdentify,
    runIdentifyChain: runIdentifyChain,
    dabsPopupHtml: dabsPopupHtml,
    uasPopupHtml: uasPopupHtml,
    obstaclePopupHtml: obstaclePopupHtml,
    formatObstacleHeight: formatObstacleHeight,
  };
})(window);
