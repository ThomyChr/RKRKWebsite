(function () {
  var modal, stage, img, rect, card, drag;

  function parseCrop(raw) {
    try {
      var c = JSON.parse(raw || '');
      if (!c || !(c.w > 4) || !(c.h > 4)) return null;
      return c;
    } catch (e) {
      return null;
    }
  }

  function parts(target) {
    if (!target) return { img: null, hidden: null };
    if (target.classList.contains('nl-own-box')) {
      return { img: target.querySelector('.nl-own-preview'), hidden: target.querySelector('.nl-own-crop') };
    }
    if (target.classList.contains('nl-pic')) {
      return { img: target.querySelector('.nl-pic-preview'), hidden: target.querySelector('.nl-pic-crop') };
    }
    return { img: target.querySelector('.nl-sec-preview'), hidden: target.querySelector('[name="sec_crop[]"]') };
  }

  function applyPreview(target) {
    if (!target) return;
    var found = parts(target);
    var imgEl = found.img;
    var hidden = found.hidden;
    if (!imgEl) return;
    var c = parseCrop(hidden ? hidden.value : '');
    var frame = imgEl.closest('.nl-sec-preview-frame');
    if (!c) {
      imgEl.removeAttribute('style');
      if (frame) {
        frame.parentNode.insertBefore(imgEl, frame);
        frame.remove();
      }
      return;
    }
    var nw = imgEl.naturalWidth;
    var nh = imgEl.naturalHeight;
    if (!nw || !nh) {
      imgEl.addEventListener('load', function () { applyPreview(target); }, { once: true });
      return;
    }
    if (!frame) {
      frame = document.createElement('span');
      frame.className = 'nl-sec-preview-frame';
      imgEl.parentNode.insertBefore(frame, imgEl);
    }
    var view = frame.querySelector('.nl-crop-view');
    if (!view) {
      view = document.createElement('span');
      view.className = 'nl-crop-view';
      frame.appendChild(view);
    }
    if (imgEl.parentNode !== view) view.appendChild(imgEl);
    var fw = nw * c.w / 100;
    var fh = nh * c.h / 100;
    view.style.paddingBottom = (fh / fw * 100) + '%';
    imgEl.style.position = 'absolute';
    imgEl.style.width = (100 / (c.w / 100)) + '%';
    imgEl.style.height = (100 / (c.h / 100)) + '%';
    imgEl.style.left = (-c.x / c.w * 100) + '%';
    imgEl.style.top = (-c.y / c.h * 100) + '%';
  }

  function placeRect(c) {
    var w = img.clientWidth;
    var h = img.clientHeight;
    stage.style.width = w + 'px';
    stage.style.height = h + 'px';
    if (!c) {
      rect.hidden = true;
      return;
    }
    rect.hidden = false;
    rect.style.left = (c.x / 100 * w) + 'px';
    rect.style.top = (c.y / 100 * h) + 'px';
    rect.style.width = (c.w / 100 * w) + 'px';
    rect.style.height = (c.h / 100 * h) + 'px';
  }

  function clampRect() {
    var w = img.clientWidth;
    var h = img.clientHeight;
    var l = parseFloat(rect.style.left) || 0;
    var t = parseFloat(rect.style.top) || 0;
    var rw = parseFloat(rect.style.width) || 0;
    var rh = parseFloat(rect.style.height) || 0;
    rw = Math.max(24, Math.min(rw, w));
    rh = Math.max(24, Math.min(rh, h));
    l = Math.max(0, Math.min(l, w - rw));
    t = Math.max(0, Math.min(t, h - rh));
    rect.style.left = l + 'px';
    rect.style.top = t + 'px';
    rect.style.width = rw + 'px';
    rect.style.height = rh + 'px';
  }

  function point(ev) {
    var b = stage.getBoundingClientRect();
    return {
      x: Math.min(Math.max(0, ev.clientX - b.left), b.width),
      y: Math.min(Math.max(0, ev.clientY - b.top), b.height)
    };
  }

  function onDown(ev) {
    if (ev.button !== 0) return;
    var handle = ev.target.getAttribute && ev.target.getAttribute('data-h');
    var p = point(ev);
    if (handle) {
      drag = { mode: handle, x: p.x, y: p.y, l: parseFloat(rect.style.left) || 0, t: parseFloat(rect.style.top) || 0, w: parseFloat(rect.style.width) || 0, h: parseFloat(rect.style.height) || 0 };
    } else if (ev.target === rect) {
      drag = { mode: 'move', x: p.x, y: p.y, l: parseFloat(rect.style.left) || 0, t: parseFloat(rect.style.top) || 0, w: parseFloat(rect.style.width) || 0, h: parseFloat(rect.style.height) || 0 };
    } else {
      rect.hidden = false;
      rect.style.left = p.x + 'px';
      rect.style.top = p.y + 'px';
      rect.style.width = '0px';
      rect.style.height = '0px';
      drag = { mode: 'new', x: p.x, y: p.y };
    }
    ev.preventDefault();
  }

  function onMove(ev) {
    if (!drag) return;
    var p = point(ev);
    if (drag.mode === 'new') {
      var l = Math.min(drag.x, p.x);
      var t = Math.min(drag.y, p.y);
      rect.style.left = l + 'px';
      rect.style.top = t + 'px';
      rect.style.width = Math.abs(p.x - drag.x) + 'px';
      rect.style.height = Math.abs(p.y - drag.y) + 'px';
      return;
    }
    var dx = p.x - drag.x;
    var dy = p.y - drag.y;
    var l = drag.l;
    var t = drag.t;
    var w = drag.w;
    var h = drag.h;
    if (drag.mode === 'move') {
      l += dx;
      t += dy;
    } else {
      if (drag.mode.indexOf('w') >= 0) { l += dx; w -= dx; }
      if (drag.mode.indexOf('e') >= 0) { w += dx; }
      if (drag.mode.indexOf('n') >= 0) { t += dy; h -= dy; }
      if (drag.mode.indexOf('s') >= 0) { h += dy; }
    }
    rect.style.left = l + 'px';
    rect.style.top = t + 'px';
    rect.style.width = w + 'px';
    rect.style.height = h + 'px';
    clampRect();
  }

  function onUp() {
    if (!drag) return;
    drag = null;
    if (!rect.hidden) clampRect();
  }

  function close() {
    modal.hidden = true;
    card = null;
    img.removeAttribute('src');
  }

  function commit(clear) {
    if (!card) return;
    var hidden = parts(card).hidden;
    if (hidden) {
      if (clear || rect.hidden) {
        hidden.value = '';
      } else {
        var w = img.clientWidth;
        var h = img.clientHeight;
        var crop = {
          x: Math.round((parseFloat(rect.style.left) || 0) / w * 10000) / 100,
          y: Math.round((parseFloat(rect.style.top) || 0) / h * 10000) / 100,
          w: Math.round((parseFloat(rect.style.width) || 0) / w * 10000) / 100,
          h: Math.round((parseFloat(rect.style.height) || 0) / h * 10000) / 100
        };
        hidden.value = (crop.w < 5 || crop.h < 5) ? '' : JSON.stringify(crop);
      }
    }
    var lead = card.querySelector('.nl-pic.is-main') || card.querySelector('.nl-pic');
    if (lead) lead.setAttribute('data-crop', hidden ? hidden.value : '');
    applyPreview(card);
    if (window.nlRepaint) window.nlRepaint();
    close();
  }

  function ensure() {
    if (modal) return;
    modal = document.createElement('div');
    modal.className = 'nl-crop-modal';
    modal.hidden = true;
    var note = document.createElement('p');
    note.className = 'nl-note';
    note.style.color = '#fff';
    note.textContent = 'Rahmen auf dem Original ziehen oder verschieben. Die Bilddatei bleibt unverändert.';
    stage = document.createElement('div');
    stage.className = 'nl-crop-stage';
    img = document.createElement('img');
    img.alt = '';
    img.draggable = false;
    rect = document.createElement('div');
    rect.className = 'nl-crop-rect';
    rect.hidden = true;
    ['nw', 'ne', 'sw', 'se'].forEach(function (name) {
      var h = document.createElement('i');
      h.className = 'h-' + name;
      h.setAttribute('data-h', name);
      rect.appendChild(h);
    });
    stage.appendChild(img);
    stage.appendChild(rect);
    var bar = document.createElement('div');
    bar.className = 'nl-crop-bar';
    function btn(label, primary, fn) {
      var b = document.createElement('button');
      b.type = 'button';
      b.className = primary ? 'nl-btn' : 'nl-btn secondary';
      b.textContent = label;
      b.addEventListener('click', fn);
      bar.appendChild(b);
    }
    btn('Ausschnitt übernehmen', true, function () { commit(false); });
    btn('Ganzes Bild', false, function () { commit(true); });
    btn('Abbrechen', false, close);
    modal.appendChild(note);
    modal.appendChild(stage);
    modal.appendChild(bar);
    document.body.appendChild(modal);
    stage.addEventListener('mousedown', onDown);
    window.addEventListener('mousemove', onMove);
    window.addEventListener('mouseup', onUp);
    document.addEventListener('keydown', function (ev) {
      if (ev.key === 'Escape' && modal && !modal.hidden) close();
    });
  }

  window.nlApplySectionCrop = applyPreview;
  window.nlOpenCrop = function (target) {
    var found = parts(target);
    var srcEl = found.img;
    if (!srcEl) return;
    ensure();
    card = target;
    var hidden = found.hidden;
    var existing = parseCrop(hidden ? hidden.value : '');
    img.onload = function () { placeRect(existing); };
    img.src = srcEl.getAttribute('src');
    modal.hidden = false;
    if (img.complete && img.naturalWidth) placeRect(existing);
  };

  function boot() {
    document.querySelectorAll('#nl-sections .nl-sec, #gb-sections .nl-pic, #gb-intro .nl-pic, #gb-intro .nl-own-box, #gb-sections .nl-own-box').forEach(applyPreview);
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
  else boot();
})();
