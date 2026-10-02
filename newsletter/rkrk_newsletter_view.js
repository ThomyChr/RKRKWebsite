(function(){
  function ico(name){
    var paths = {
      minus: '<path d="M5 12h14" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/>',
      plus: '<path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/>',
      fit: '<path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/>'
    };
    return '<svg class="nl-ico" viewBox="0 0 24 24" aria-hidden="true">' + (paths[name] || '') + '</svg>';
  }
  function btn(name, title){
    var b = document.createElement('button');
    b.type = 'button';
    b.className = 'nl-icon-btn';
    b.title = title;
    b.setAttribute('aria-label', title);
    b.innerHTML = ico(name);
    return b;
  }
  function visibleSheets(root){
    var nodes = root.querySelectorAll('.nl-a4');
    var pages = [];
    var i;
    for (i = 0; i < nodes.length; i++) {
      if (nodes[i].style.display === 'none') continue;
      pages.push(nodes[i]);
    }
    return pages;
  }
  function stampList(pages){
    var total = pages.length;
    var i;
    for (i = 0; i < pages.length; i++) {
      var mark = null;
      var kids = pages[i].children;
      var k;
      for (k = 0; k < kids.length; k++) {
        if (kids[k].classList && kids[k].classList.contains('nl-page-no')) { mark = kids[k]; break; }
      }
      if (!mark) {
        mark = document.createElement('div');
        mark.className = 'nl-page-no';
        pages[i].appendChild(mark);
      }
      mark.textContent = (i + 1) + '/' + total;
    }
  }
  function stampPages(root){
    var docs = root.querySelectorAll('.nl-a4-doc');
    var seen = [];
    var loose = [];
    var all, pages, d, i;
    for (d = 0; d < docs.length; d++) {
      pages = visibleSheets(docs[d]);
      for (i = 0; i < pages.length; i++) seen.push(pages[i]);
      stampList(pages);
    }
    all = visibleSheets(root);
    for (i = 0; i < all.length; i++) {
      if (seen.indexOf(all[i]) >= 0) continue;
      loose.push(all[i]);
    }
    stampList(loose);
  }
  function flowingKids(letter){
    var kids = [];
    var i;
    for (i = 0; i < letter.children.length; i++) {
      var el = letter.children[i];
      if (!el.classList) continue;
      if (el.classList.contains('nl-inv-head') || el.classList.contains('nl-letter-address') || el.classList.contains('nl-letter-date') || el.classList.contains('nl-page-no')) continue;
      kids.push(el);
    }
    return kids;
  }
  function tooTall(el){
    return el.scrollHeight > el.clientHeight + 4;
  }
  function measurePage(el){
    el.style.height = '297mm';
    el.style.maxHeight = '297mm';
    el.style.overflow = 'auto';
  }
  function releasePage(el){
    if (tooTall(el)) {
      el.style.height = 'auto';
      el.style.maxHeight = 'none';
      el.style.overflow = 'visible';
      return;
    }
    el.style.overflow = 'hidden';
  }
  function paginate(letter){
    if (!letter || !letter.closest('.nl-view')) return;
    measurePage(letter);
    var guard = 0;
    while (letter && tooTall(letter) && guard++ < 12) {
      var next = document.createElement('article');
      next.className = 'nl-a4 nl-letter nl-letter-next';
      measurePage(next);
      var moved = [];
      var safety = 0;
      while (tooTall(letter) && safety++ < 80) {
        var kids = flowingKids(letter);
        if (!kids.length) break;
        var last = kids[kids.length - 1];
        if (kids.length === 1 && last.scrollHeight > letter.clientHeight) {
          if (!(last.children && last.children.length > 1)) break;
          var inner = [];
          var c;
          for (c = 0; c < last.children.length; c++) inner.push(last.children[c]);
          for (c = inner.length - 1; c >= 0; c--) last.parentNode.insertBefore(inner[c], last.nextSibling);
          last.parentNode.removeChild(last);
          continue;
        }
        moved.unshift(last);
        letter.removeChild(last);
      }
      if (!moved.length) break;
      var j;
      for (j = 0; j < moved.length; j++) next.appendChild(moved[j]);
      var slip = letter.parentNode.querySelector('.nl-slip-sheet');
      if (slip && slip.parentNode === letter.parentNode) letter.parentNode.insertBefore(next, slip);
      else if (letter.nextSibling) letter.parentNode.insertBefore(next, letter.nextSibling);
      else letter.parentNode.appendChild(next);
      releasePage(letter);
      letter = next;
    }
    if (letter) releasePage(letter);
  }
  function reflowDoc(doc){
    var first = doc.querySelector('.nl-a4.nl-letter');
    if (!first) return;
    var oldMarks = doc.querySelectorAll('.nl-letter > .nl-page-no');
    var m;
    for (m = 0; m < oldMarks.length; m++) {
      if (oldMarks[m].parentNode) oldMarks[m].parentNode.removeChild(oldMarks[m]);
    }
    var extras = doc.querySelectorAll('.nl-letter-next');
    var i;
    for (i = 0; i < extras.length; i++) {
      while (extras[i].firstChild) first.appendChild(extras[i].firstChild);
      if (extras[i].parentNode) extras[i].parentNode.removeChild(extras[i]);
    }
    paginate(first);
  }
  function reflowLetter(view){
    var docs = view.querySelectorAll('.nl-a4-doc');
    var d;
    if (!docs.length) {
      stampPages(view);
      return;
    }
    for (d = 0; d < docs.length; d++) reflowDoc(docs[d]);
    stampPages(view);
  }
  document.querySelectorAll('.nl-view').forEach(function(view){
    var sheet = view.querySelector('.nl-a4-doc') || view.querySelector('.nl-letter, .nl-mail');
    if (!sheet || view.querySelector('.nl-view-stage')) return;
    if (sheet.classList.contains('nl-mail')) {
      view.classList.add('nl-view-mail');
      return;
    }
    var bar = document.createElement('div');
    bar.className = 'nl-view-bar';
    var heading = view.querySelector('h2');
    if (heading) bar.appendChild(heading);
    var out = btn('minus', 'Kleiner');
    var label = document.createElement('span');
    label.className = 'nl-view-zoom';
    var inn = btn('plus', 'Grösser');
    var fitBtn = btn('fit', 'Ganzes Blatt einpassen');
    bar.appendChild(out);
    bar.appendChild(label);
    bar.appendChild(inn);
    bar.appendChild(fitBtn);
    view.insertBefore(bar, view.firstChild);
    var stage = document.createElement('div');
    stage.className = 'nl-view-stage';
    stage.title = 'Ziehen verschiebt das Blatt, das Mausrad zoomt';
    var pan = document.createElement('div');
    pan.className = 'nl-view-pan';
    stage.appendChild(pan);
    pan.appendChild(sheet);
    view.appendChild(stage);
    var page = sheet.querySelector ? (sheet.querySelector('.nl-letter') || sheet) : sheet;
    reflowLetter(view);
    window.addEventListener('load', function(){ reflowLetter(view); });
    var scale = 1;
    var x = 16;
    var y = 12;
    var store = 'nl-ansicht-zoom';
    function apply(){
      pan.style.transform = 'translate(' + x + 'px,' + y + 'px) scale(' + scale + ')';
      label.textContent = Math.round(scale * 100) + '%';
    }
    window.nlShowInView = function(el){
      if (!el || !stage) return;
      var sr = stage.getBoundingClientRect();
      var er = el.getBoundingClientRect();
      if (!(er.height > 8)) return;
      y -= (er.top - sr.top - 8);
      var lw = (page.offsetWidth || er.width) * scale;
      x = Math.round((stage.clientWidth - lw) / 2);
      apply();
    };
    function center(){
      var lw = page.offsetWidth * scale;
      x = Math.round((stage.clientWidth - lw) / 2);
      y = 12;
    }
    function fit(){
      var lw = page.offsetWidth || 1;
      var lh = page.offsetHeight || 1;
      var sw = Math.max(40, stage.clientWidth - 24);
      var sh = Math.max(40, stage.clientHeight - 24);
      scale = Math.max(0.3, Math.min(2.5, Math.min(sw / lw, sh / lh)));
      center();
      apply();
      try { localStorage.setItem(store, 'fit'); } catch (e) {}
    }
    function zoomAt(next, clientX, clientY){
      next = Math.max(0.3, Math.min(2.5, next));
      var rect = stage.getBoundingClientRect();
      var px = clientX - rect.left;
      var py = clientY - rect.top;
      var wx = (px - x) / scale;
      var wy = (py - y) / scale;
      scale = next;
      x = px - wx * scale;
      y = py - wy * scale;
      apply();
      try { localStorage.setItem(store, String(scale)); } catch (e) {}
    }
    out.addEventListener('click', function(){ zoomAt(scale / 1.12, stage.clientWidth / 2 + stage.getBoundingClientRect().left, stage.clientHeight / 2 + stage.getBoundingClientRect().top); });
    inn.addEventListener('click', function(){ zoomAt(scale * 1.12, stage.clientWidth / 2 + stage.getBoundingClientRect().left, stage.clientHeight / 2 + stage.getBoundingClientRect().top); });
    fitBtn.addEventListener('click', fit);
    label.addEventListener('dblclick', function(){ zoomAt(1, stage.clientWidth / 2 + stage.getBoundingClientRect().left, stage.clientHeight / 2 + stage.getBoundingClientRect().top); });
    stage.addEventListener('wheel', function(ev){
      ev.preventDefault();
      zoomAt(scale * (ev.deltaY < 0 ? 1.08 : 1 / 1.08), ev.clientX, ev.clientY);
    }, {passive: false});
    var drag = null;
    stage.addEventListener('pointerdown', function(ev){
      if (ev.button !== 0 || ev.target.closest('a, button, input, textarea, select')) return;
      drag = {x: ev.clientX, y: ev.clientY, ox: x, oy: y};
      stage.classList.add('is-pan');
      if (stage.setPointerCapture) stage.setPointerCapture(ev.pointerId);
    });
    stage.addEventListener('pointermove', function(ev){
      if (!drag) return;
      x = drag.ox + (ev.clientX - drag.x);
      y = drag.oy + (ev.clientY - drag.y);
      apply();
    });
    function endDrag(){
      drag = null;
      stage.classList.remove('is-pan');
    }
    stage.addEventListener('pointerup', endDrag);
    stage.addEventListener('pointercancel', endDrag);
    var keep = window.nlAnsichtKeep;
    if (keep && typeof keep.scale === 'number' && keep.scale >= 0.3 && keep.scale <= 2.5) {
      scale = keep.scale;
      if (typeof keep.x === 'number') x = keep.x;
      if (typeof keep.y === 'number') y = keep.y;
      apply();
      return;
    }
    var saved = '';
    try { saved = localStorage.getItem(store) || ''; } catch (e) {}
    if (saved && saved !== 'fit') {
      var n = parseFloat(saved);
      if (n >= 0.3 && n <= 2.5) {
        scale = n;
        center();
        apply();
        return;
      }
    }
    fit();
  });
})();
