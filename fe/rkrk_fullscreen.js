/* Vollbild über die Fullscreen-API. Neben Anzeige, sonst unten links. */
(function () {
  var enterIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M5 5h5V3H3v7h2V5zm9-2v2h5v5h2V3h-7zM5 14H3v7h7v-2H5v-5zm14 5h-5v2h7v-7h-2v5z"/></svg>';
  var exitIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M10 4H8v4H4v2h6V4zm6 4V4h-2v6h6V8h-4zM8 14H4v2h4v4h2v-6H8zm8 0v6h2v-4h4v-2h-6z"/></svg>';

  function fsElement() {
    return document.fullscreenElement || document.webkitFullscreenElement || document.msFullscreenElement || null;
  }

  function requestFs(el) {
    if (el.requestFullscreen) return el.requestFullscreen();
    if (el.webkitRequestFullscreen) return el.webkitRequestFullscreen();
    if (el.msRequestFullscreen) return el.msRequestFullscreen();
    return Promise.reject(new Error('fullscreen'));
  }

  function exitFs() {
    if (document.exitFullscreen) return document.exitFullscreen();
    if (document.webkitExitFullscreen) return document.webkitExitFullscreen();
    if (document.msExitFullscreen) return document.msExitFullscreen();
  }

  function paint(btn) {
    var on = !!fsElement();
    btn.classList.toggle('is-on', on);
    btn.innerHTML = on ? exitIcon : enterIcon;
    btn.title = on ? 'Vollbild beenden' : 'Vollbild';
    btn.setAttribute('aria-label', btn.title);
    btn.setAttribute('aria-pressed', on ? 'true' : 'false');
  }

  function mount() {
    if (!document.body || document.getElementById('rkrkFullscreen')) return;
    var style = document.createElement('style');
    style.textContent = [
      'button.rkrk-fullscreen{z-index:30 !important;width:40px !important;height:40px !important;min-width:40px !important;min-height:40px !important;padding:0 !important;margin:0 !important;border-radius:10px !important;border:1px solid rgba(20,32,24,.28) !important;background:rgba(255,255,255,.96) !important;color:#1b3552 !important;box-shadow:0 1px 4px rgba(0,0,0,.22);cursor:pointer;display:flex !important;align-items:center;justify-content:center;flex:0 0 auto !important;}',
      'button.rkrk-fullscreen svg{display:block;width:22px;height:22px;}',
      'button.rkrk-fullscreen.is-on{background:#48673A !important;color:#fff !important;}',
      'button.rkrk-fullscreen.is-docked{position:static !important;width:var(--hit,40px) !important;height:var(--hit,40px) !important;min-width:var(--hit,40px) !important;min-height:var(--hit,40px) !important;border-radius:0.45em !important;background:var(--brand-dark,#48673A) !important;color:#fff !important;border:0 !important;}',
      'button.rkrk-fullscreen.is-map{position:absolute !important;left:8px !important;bottom:8px !important;top:auto !important;}',
      '.map-wrap:has(> button.rkrk-fullscreen) .ol-attribution{left:56px;}',
      'button.rkrk-fullscreen.is-corner{position:fixed !important;left:max(8px,env(safe-area-inset-left)) !important;bottom:max(8px,env(safe-area-inset-bottom)) !important;top:auto !important;z-index:10000 !important;}'
    ].join('');
    document.head.appendChild(style);
    var btn = document.createElement('button');
    btn.type = 'button';
    btn.id = 'rkrkFullscreen';
    btn.className = 'rkrk-fullscreen';
    paint(btn);
    btn.addEventListener('click', function () {
      var pending = fsElement() ? exitFs() : requestFs(document.documentElement);
      if (pending && typeof pending.catch === 'function') pending.catch(function () {});
    });
    document.addEventListener('fullscreenchange', function () { paint(btn); });
    document.addEventListener('webkitfullscreenchange', function () { paint(btn); });
    var showBtn = document.getElementById('showBtn');
    var mapWrap = document.querySelector('.map-wrap');
    if (showBtn && showBtn.parentNode) {
      btn.classList.add('is-docked');
      showBtn.parentNode.insertBefore(btn, showBtn);
    } else if (mapWrap) {
      btn.classList.add('is-map');
      mapWrap.appendChild(btn);
    } else {
      btn.classList.add('is-corner');
      document.body.appendChild(btn);
    }
  }

  if (document.body) mount();
  else document.addEventListener('DOMContentLoaded', mount);
})();
