<?php
if (defined('RKRK_CATALOG_SHOT')) {
    return;
}
define('RKRK_CATALOG_SHOT', true);
?>
<script>
(function () {
  function pictureQuery(kind) {
    if (kind === 'such' || kind === 'suchfeld') {
      return 'Reg=ALL&Strt=on&Cntr=off&Area=on&Pfad=off&GDE=off&JGG=off&Poi=off&HSP=on&OVR=on&Label=off&EX=NO&KA=M&MAP=1&Zoom=0.6';
    }
    return 'Reg=ALL&Strt=on&Cntr=off&Area=on&Pfad=on&GDE=off&JGG=off&Poi=off&HSP=on&FLP=on&Label=off&EX=NO&KA=M&MAP=1&Zoom=0.8';
  }

  function catalogCropRect(canvasWidth, canvasHeight, kind) {
    const zoom = ((kind === 'such' || kind === 'suchfeld') ? 70 : 90) / 100;
    const offsetY = 0;
    const out = 600;
    const visible = out / zoom;
    const scaleX = canvasWidth / 1200;
    const scaleY = canvasHeight / 900;
    const cropW = visible * scaleX;
    const cropH = visible * scaleY;
    let x = canvasWidth / 2 - cropW / 2;
    let y = canvasHeight / 2 + offsetY * scaleY - cropH / 2;
    x = Math.max(0, Math.min(x, Math.max(0, canvasWidth - cropW)));
    y = Math.max(0, Math.min(y, Math.max(0, canvasHeight - cropH)));
    return { x: x, y: y, w: cropW, h: cropH, out: out, zoom: zoom, visible: visible, scaleX: scaleX, scaleY: scaleY };
  }

  function cropCatalogCanvas(source, kind) {
    const rect = catalogCropRect(source.width, source.height, kind);
    const canvas = document.createElement('canvas');
    canvas.width = rect.out;
    canvas.height = rect.out;
    const ctx = canvas.getContext('2d');
    ctx.drawImage(source, rect.x, rect.y, rect.w, rect.h, 0, 0, rect.out, rect.out);
    return new Promise((resolve) => {
      try {
        canvas.toBlob((blob) => resolve(blob), 'image/jpeg', 0.92);
      } catch (_) {
        resolve(null);
      }
    });
  }

  function fieldPolygon(win, feldId) {
    const geoMap = win && win.__rkrkMap27;
    if (!geoMap || typeof geoMap.getLayers !== 'function') return null;
    let feature = null;
    geoMap.getLayers().forEach((layer) => {
      const src = layer.getSource && layer.getSource();
      if (!src || typeof src.getFeatures !== 'function' || feature) return;
      src.getFeatures().some((f) => {
        const g = f.getGeometry && f.getGeometry();
        if (!g || String(g.getType()).indexOf('Polygon') < 0) return false;
        const fid = String((typeof f.getId === 'function' && f.getId()) || '');
        const name = String((f.get && (f.get('name') || f.get('Name'))) || '');
        if ((fid + ' ' + name).indexOf(feldId) === -1) return false;
        feature = f;
        return true;
      });
    });
    return feature;
  }

  function centerFieldInView(win, feldId, kind) {
    const geoMap = win && win.__rkrkMap27;
    const feature = fieldPolygon(win, feldId);
    if (!geoMap || !feature || typeof geoMap.getView !== 'function' || typeof geoMap.updateSize !== 'function') return false;
    try { geoMap.updateSize(); } catch (_) { return false; }
    const size = geoMap.getSize();
    if (!size || size[0] < 480 || size[1] < 360) return false;
    const ext = feature.getGeometry().getExtent();
    if (!ext || ext.length < 4) return false;
    const cx = (ext[0] + ext[2]) / 2;
    const cy = (ext[1] + ext[3]) / 2;
    const fill = (kind === 'such' || kind === 'suchfeld') ? 0.17 : 0.35;
    const scale = 1 / fill;
    let hw = Math.abs(ext[2] - ext[0]) / 2;
    let hh = Math.abs(ext[3] - ext[1]) / 2;
    if (!(hw > 1)) hw = 40;
    if (!(hh > 1)) hh = 40;
    const aspect = size[0] / size[1];
    let boxW = hw * 2 * scale;
    let boxH = hh * 2 * scale;
    if (boxW / boxH < aspect) boxW = boxH * aspect;
    else boxH = boxW / aspect;
    const view = geoMap.getView();
    view.fit([cx - boxW / 2, cy - boxH / 2, cx + boxW / 2, cy + boxH / 2], {
      padding: [0, 0, 0, 0],
      maxZoom: 19,
      duration: 0
    });
    view.setCenter([cx, cy]);
    return true;
  }

  function fieldCentered(win, feldId) {
    const geoMap = win && win.__rkrkMap27;
    const feature = fieldPolygon(win, feldId);
    if (!geoMap || !feature || typeof geoMap.getPixelFromCoordinate !== 'function') return null;
    const size = geoMap.getSize();
    if (!size || size[0] < 480 || size[1] < 360) return null;
    const ext = feature.getGeometry().getExtent();
    if (!ext || ext.length < 4) return null;
    const px = geoMap.getPixelFromCoordinate([(ext[0] + ext[2]) / 2, (ext[1] + ext[3]) / 2]);
    if (!px) return null;
    const a = geoMap.getPixelFromCoordinate([ext[0], ext[1]]);
    const b = geoMap.getPixelFromCoordinate([ext[2], ext[3]]);
    if (!a || !b) return null;
    const wide = Math.abs(a[0] - b[0]);
    const high = Math.abs(a[1] - b[1]);
    if (wide < 12 || high < 12) return null;
    if (Math.abs(px[0] - size[0] / 2) > 24 || Math.abs(px[1] - size[1] / 2) > 24) return null;
    return { cx: px[0], cy: px[1], size: size };
  }

  function catalogFieldPainted(win, hit) {
    const nodes = win.document.querySelectorAll('#map .ol-layer canvas');
    if (!nodes.length) return false;
    for (let i = 0; i < nodes.length; i++) {
      const canvas = nodes[i];
      if (!(canvas.width > 0) || !(canvas.height > 0)) continue;
      const prX = canvas.width / hit.size[0];
      const prY = canvas.height / hit.size[1];
      let ctx = null;
      try { ctx = canvas.getContext('2d', { willReadFrequently: true }); } catch (_) { ctx = null; }
      if (!ctx) continue;
      const x = Math.max(0, Math.min(canvas.width - 1, Math.round(hit.cx * prX)));
      const y = Math.max(0, Math.min(canvas.height - 1, Math.round(hit.cy * prY)));
      try {
        const d = ctx.getImageData(x, y, 1, 1).data;
        if (d[3] > 40 && d[0] > d[1] + 28 && d[0] > d[2] + 28) return true;
      } catch (_) {}
    }
    return false;
  }

  function compositeCatalogBitmap(win) {
    const geoMap = win && win.__rkrkMap27;
    const size = geoMap && geoMap.getSize ? geoMap.getSize() : null;
    if (!size || !(size[0] > 0) || !(size[1] > 0)) return null;
    const nodes = win.document.querySelectorAll('#map .ol-layer canvas');
    if (!nodes.length) return null;
    const base = nodes[nodes.length - 1];
    const prX = base.width / size[0];
    const prY = base.height / size[1];
    if (!(prX > 0) || !(prY > 0) || Math.abs(prX - prY) > 0.04) return null;
    const pr = (prX + prY) / 2;
    const out = document.createElement('canvas');
    out.width = Math.max(1, Math.round(size[0] * pr));
    out.height = Math.max(1, Math.round(size[1] * pr));
    const ctx = out.getContext('2d');
    if (!ctx) return null;
    Array.prototype.forEach.call(nodes, (canvas) => {
      if (!(canvas.width > 0) || !(canvas.height > 0)) return;
      const parent = canvas.parentNode;
      const opacity = parent && parent.style && parent.style.opacity !== '' ? Number(parent.style.opacity) : 1;
      ctx.globalAlpha = Number.isFinite(opacity) ? opacity : 1;
      const transform = canvas.style && canvas.style.transform ? canvas.style.transform : '';
      const matched = /^matrix\(([^)]+)\)$/.exec(transform);
      if (matched) {
        const matrix = matched[1].split(',').map((v) => Number(String(v).trim()));
        if (matrix.length === 6 && matrix.every((n) => Number.isFinite(n))) {
          ctx.setTransform(matrix[0] * pr, matrix[1] * pr, matrix[2] * pr, matrix[3] * pr, matrix[4] * pr, matrix[5] * pr);
          ctx.drawImage(canvas, 0, 0);
          return;
        }
      }
      ctx.setTransform(pr, 0, 0, pr, 0, 0);
      ctx.drawImage(canvas, 0, 0, size[0], size[1]);
    });
    ctx.globalAlpha = 1;
    ctx.setTransform(1, 0, 0, 1, 0, 0);
    return out;
  }

  function prepareCatalogFrame(win) {
    try {
      win.document.documentElement.classList.add('rkrk-map27-embed');
      win.document.body.classList.add('rkrk-map27-page');
    } catch (_) {}
    try {
      if (typeof win.rkrkUpdateMapSize === 'function') win.rkrkUpdateMapSize(false);
      else if (win.__rkrkMap27) win.__rkrkMap27.updateSize();
    } catch (_) {}
  }

  function frameUrl(mapUrl) {
    let url = String(mapUrl || '');
    if (url.indexOf('MenuOn=') < 0) url += '&MenuOn=off';
    if (url.indexOf('MapButtons=') < 0) url += '&MapButtons=off';
    return url;
  }

  function captureShot(kind, feldId, mapUrl) {
    const id = String(feldId || '').trim();
    if (!/^[A-Za-z0-9]+-\d+$/.test(id) || !mapUrl) return Promise.resolve(null);
    return new Promise((resolve) => {
      const frame = document.createElement('iframe');
      frame.setAttribute('aria-hidden', 'true');
      frame.width = '1200';
      frame.height = '900';
      frame.style.cssText = 'position:fixed;left:0;top:0;width:1200px;height:900px;border:0;opacity:0.02;pointer-events:none;z-index:-1;';
      let settled = false;
      let centered = false;
      const finish = (blob) => {
        if (settled) return;
        settled = true;
        frame.remove();
        resolve(blob && blob.size > 4000 ? blob : null);
      };
      const timer = setTimeout(() => finish(null), 20000);
      const grabWhenDrawn = () => {
        if (settled) return;
        const win = frame.contentWindow;
        if (!win) {
          setTimeout(grabWhenDrawn, 200);
          return;
        }
        prepareCatalogFrame(win);
        const geoMap = win.__rkrkMap27;
        if (geoMap && typeof geoMap.updateSize === 'function') {
          try { geoMap.updateSize(); } catch (_) {}
        }
        if (!fieldPolygon(win, id)) {
          setTimeout(grabWhenDrawn, 300);
          return;
        }
        const size = geoMap && geoMap.getSize ? geoMap.getSize() : null;
        if (!size || size[0] < 1000 || size[1] < 700) {
          setTimeout(grabWhenDrawn, 300);
          return;
        }
        if (!centered) {
          if (!centerFieldInView(win, id, kind)) {
            setTimeout(grabWhenDrawn, 300);
            return;
          }
          centered = true;
        }
        if (!fieldCentered(win, id)) {
          centered = false;
          setTimeout(grabWhenDrawn, 300);
          return;
        }
        const shoot = () => {
          setTimeout(() => {
            if (settled) return;
            if (!fieldCentered(win, id)) {
              centered = false;
              setTimeout(grabWhenDrawn, 300);
              return;
            }
            try {
              const canvas = compositeCatalogBitmap(win);
              if (!canvas) {
                setTimeout(grabWhenDrawn, 300);
                return;
              }
              cropCatalogCanvas(canvas, kind).then((blob) => {
                clearTimeout(timer);
                finish(blob);
              }).catch(() => {
                clearTimeout(timer);
                finish(null);
              });
            } catch (_) {
              clearTimeout(timer);
              finish(null);
            }
          }, 600);
        };
        let shotArmed = false;
        const arm = () => {
          if (shotArmed || settled) return;
          shotArmed = true;
          shoot();
        };
        if (geoMap && typeof geoMap.once === 'function') {
          geoMap.once('rendercomplete', arm);
          try { geoMap.renderSync(); } catch (_) { arm(); }
          setTimeout(arm, 900);
        } else {
          arm();
        }
      };
      frame.addEventListener('load', () => setTimeout(grabWhenDrawn, 200));
      document.body.appendChild(frame);
      frame.src = frameUrl(mapUrl);
    });
  }

  function guestSuffix(info) {
    try {
      const fromPage = new URLSearchParams(window.location.search).get('GA');
      const ga = fromPage || (info && info.ga) || '';
      if (ga) return '&GA=' + encodeURIComponent(ga);
    } catch (_) {}
    return '';
  }

  window.rkrkCatalogPictureQuery = pictureQuery;
  window.rkrkCatalogCropRect = catalogCropRect;
  window.rkrkCaptureCatalogShot = function (kind, feldId, mapUrl) {
    return captureShot(kind, feldId, mapUrl);
  };

  window.rkrkStoreCatalogPictures = async function (info) {
    const feldId = String(info && info.feld_id || '').trim();
    const id = String(info && info.id || '').trim();
    if (!id || !/^[A-Za-z0-9]+-\d+$/.test(feldId)) return { ok: false, skipped: true };
    const base = String(info.mapBase || 'rkrk_map.php');
    const mdb = String(info.mdb || '');
    const urlFor = (kind) => base + '?FFilter=' + encodeURIComponent(feldId) + '&MDB=' + encodeURIComponent(mdb) + '&' + pictureQuery(kind) + guestSuffix(info) + '&_=' + Date.now();
    const such = await captureShot('such', feldId, urlFor('such'));
    const flug = await captureShot('flug', feldId, urlFor('flug'));
    if (!such && !flug) return { ok: false, empty: true };
    if (info.post === false) return { ok: true, posted: false, such: such, flug: flug };
    const body = new FormData();
    body.append('id', id);
    body.append('feld_id', feldId);
    if (such) body.append('suchfeld', such, 'suchfeld.jpg');
    if (flug) body.append('flugplan', flug, 'flugplan.jpg');
    if (mdb) body.append('mdb', mdb);
    const res = await fetch(String(info.apiUrl || ''), { method: 'POST', body: body, credentials: 'same-origin' });
    const data = await res.json().catch(() => null);
    if (!data || !data.ok) return { ok: false, error: (data && (data.detail || data.error)) || 'Bilder nicht geschrieben' };
    return { ok: true, posted: true, suchfeld_bild: data.suchfeld_bild || '', flugplan_bild: data.flugplan_bild || '' };
  };
})();
</script>
