(function(){
  function gridRoot(el){
    return el ? el.closest('[data-nl-grid]') : null;
  }
  function textOf(td){
    if (!td) return '';
    var inp = td.querySelector('input:not([type=checkbox]):not([type=hidden]), select');
    if (inp) return (inp.value || '').toLowerCase();
    var cb = td.querySelector('input[type=checkbox]');
    if (cb) return cb.checked ? '1' : '0';
    return (td.getAttribute('data-sort') || td.textContent || '').toLowerCase();
  }
  function visibleCols(table){
    var cols = [];
    table.querySelectorAll('thead tr:first-child th.sortable').forEach(function(th){
      if (th.offsetParent !== null) cols.push(th.getAttribute('data-col'));
    });
    return cols;
  }
  function filterTable(table){
    var form = gridRoot(table);
    var q = '';
    var search = form ? form.querySelector('.nl-grid-search') : null;
    if (search) q = (search.value || '').toLowerCase().trim();
    var filters = {};
    table.querySelectorAll('.nl-col-filter').forEach(function(inp){
      var v = (inp.value || '').toLowerCase().trim();
      if (v) filters[inp.getAttribute('data-col')] = v;
    });
    var vis = visibleCols(table);
    var shown = 0;
    table.querySelectorAll('tbody tr[data-nl-row]').forEach(function(tr){
      var ok = true;
      if (q) {
        var blob = vis.map(function(c){
          return textOf(tr.querySelector('td[data-col="'+c+'"]'));
        }).join(' ');
        if (blob.indexOf(q) < 0) ok = false;
      }
      Object.keys(filters).forEach(function(c){
        var td = tr.querySelector('td[data-col="'+c+'"]');
        if (!td || textOf(td).indexOf(filters[c]) < 0) ok = false;
      });
      tr.style.display = ok ? '' : 'none';
      if (ok) shown++;
    });
    var cnt = form ? form.querySelector('#nl-grid-count') : document.getElementById('nl-grid-count');
    if (cnt) cnt.textContent = shown + ' Zeilen';
  }
  function sortTable(table, col, dir){
    var tb = table.tBodies[0];
    var rows = Array.prototype.slice.call(tb.querySelectorAll('tr[data-nl-row]'));
    rows.sort(function(a,b){
      var fa = a.classList.contains('nl-fav') ? 1 : 0;
      var fb = b.classList.contains('nl-fav') ? 1 : 0;
      if (fa !== fb) return fb - fa;
      var va = textOf(a.querySelector('td[data-col="'+col+'"]'));
      var vb = textOf(b.querySelector('td[data-col="'+col+'"]'));
      if (va < vb) return dir;
      if (va > vb) return -dir;
      return 0;
    });
    rows.forEach(function(r){ tb.appendChild(r); });
  }
  function serializeGrid(form){
    var rows = [];
    var del = [];
    form.querySelectorAll('tbody tr[data-nl-row]').forEach(function(tr){
      var row = {};
      tr.querySelectorAll('input, select').forEach(function(el){
        var name = el.getAttribute('name') || '';
        if (el.hasAttribute('data-nl-del') || name === 'delete[]') {
          if (el.checked || tr.getAttribute('data-nl-remove') === '1') {
            row._delete = 1;
            del.push(el.value);
          }
          return;
        }
        var m = name.match(/^rows\[\d+\]\[([^\]]+)\]$/);
        if (!m) return;
        row[m[1]] = (el.type === 'checkbox') ? (el.checked ? '1' : '') : el.value;
      });
      rows.push(row);
    });
    var json = form.querySelector('[name=rows_json]');
    var delEl = form.querySelector('[name=delete_json]');
    if (json) json.value = JSON.stringify(rows);
    if (delEl) delEl.value = JSON.stringify(del);
  }
  document.addEventListener('pointerdown', function(ev){
    var handle = ev.target.closest('.nl-col-resize');
    if (!handle) return;
    var form = gridRoot(handle);
    var table = handle.closest('table.nl-grid');
    if (!form || !table) return;
    var key = handle.getAttribute('data-col');
    var col = table.querySelector('col[data-col="'+key+'"]');
    if (!col) return;
    ev.preventDefault();
    ev.stopPropagation();
    var startX = ev.clientX;
    var startW = col.getBoundingClientRect().width;
    handle.classList.add('drag');
    if (handle.setPointerCapture) handle.setPointerCapture(ev.pointerId);
    function move(e){
      var w = Math.round(Math.max(36, Math.min(640, startW + (e.clientX - startX))));
      col.style.width = w + 'px';
      var th = table.querySelector('thead tr:first-child th[data-col="'+key+'"]');
      if (th) th.style.width = w + 'px';
      handle._nlW = w;
    }
    function end(){
      handle.classList.remove('drag');
      document.removeEventListener('pointermove', move);
      document.removeEventListener('pointerup', end);
      document.removeEventListener('pointercancel', end);
      var px = handle._nlW;
      var action = form.getAttribute('data-width-action');
      if (!px || !action) return;
      var fd = new FormData();
      fd.append('action', action);
      fd.append('ajax', '1');
      fd.append('widths['+key+']', String(px));
      form.querySelectorAll('input[type=hidden]').forEach(function(inp){
        if (inp.name && inp.name !== 'action' && inp.name !== 'rows_json' && inp.name !== 'delete_json') {
          fd.append(inp.name, inp.value);
        }
      });
      fetch(form.getAttribute('action') || '', {method:'POST', body:fd, credentials:'same-origin'});
    }
    document.addEventListener('pointermove', move);
    document.addEventListener('pointerup', end);
    document.addEventListener('pointercancel', end);
  });
  document.addEventListener('click', function(ev){
    if (ev.target.closest('.nl-col-resize')) return;
    var th = ev.target.closest('th.sortable');
    if (!th) return;
    var table = th.closest('table.nl-grid');
    if (!table) return;
    var col = th.getAttribute('data-col');
    var dir = th.getAttribute('data-dir') === '1' ? -1 : 1;
    table.querySelectorAll('th.sortable').forEach(function(x){
      x.removeAttribute('data-dir');
      var s = x.querySelector('.sort-ind');
      if (s) s.textContent = '';
    });
    th.setAttribute('data-dir', dir === 1 ? '1' : '0');
    var ind = th.querySelector('.sort-ind');
    if (ind) ind.textContent = dir === 1 ? ' ^' : ' v';
    sortTable(table, col, dir);
  });
  document.addEventListener('input', function(ev){
    if (ev.target.classList.contains('nl-grid-search') || ev.target.classList.contains('nl-col-filter')) {
      var form = gridRoot(ev.target);
      var table = form ? form.querySelector('table.nl-grid') : null;
      if (table) filterTable(table);
    }
  });
  document.addEventListener('change', function(ev){
    if (!ev.target.classList.contains('nl-sel-all')) return;
    var table = ev.target.closest('table.nl-grid');
    if (!table) return;
    table.querySelectorAll('tbody tr[data-nl-row]').forEach(function(tr){
      if (tr.style.display === 'none') return;
      var cb = tr.querySelector('input[name="delete[]"]');
      if (cb) cb.checked = ev.target.checked;
    });
  });
  document.addEventListener('click', function(ev){
    var star = ev.target.closest('.nl-fav-btn');
    if (!star) return;
    ev.preventDefault();
    var tr = star.closest('tr');
    var form = gridRoot(star);
    if (!tr || !form) return;
    var on = star.getAttribute('aria-pressed') !== 'true';
    star.setAttribute('aria-pressed', on ? 'true' : 'false');
    star.innerHTML = on
      ? '<svg class="nl-ico" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 2.6l2.7 5.6 6.2.8-4.5 4.3 1.1 6.1L12 16.8 6.5 19.4l1.1-6.1L3.1 9l6.2-.8z"/></svg>'
      : '<svg class="nl-ico" viewBox="0 0 24 24" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="1.6" d="M12 3.2l2.4 5 5.5.7-4 3.9 1 5.5L12 16.2 7.1 18.3l1-5.5-4-3.9 5.5-.7z"/></svg>';
    tr.classList.toggle('nl-fav', on);
    var hidden = tr.querySelector('input[name$="[favorit]"]');
    if (hidden) hidden.value = on ? '1' : '0';
    var tb = tr.parentNode;
    if (on) tb.insertBefore(tr, tb.querySelector('tr[data-nl-row]'));
    else {
      var plain = null;
      tb.querySelectorAll('tr[data-nl-row]').forEach(function(row){
        if (!plain && row !== tr && !row.classList.contains('nl-fav')) plain = row;
      });
      if (plain) tb.insertBefore(tr, plain);
      else tb.appendChild(tr);
    }
    var idInput = tr.querySelector('input[type=hidden][name$="[id]"]');
    var id = idInput ? (idInput.value || '').trim() : '';
    var action = form.getAttribute('data-favorit-action');
    if (!id || !action) return;
    var fd = new FormData();
    fd.append('action', action);
    fd.append('ajax', '1');
    fd.append('id', id);
    fd.append('favorit', on ? '1' : '0');
    form.querySelectorAll('input[type=hidden]').forEach(function(inp){
      if (inp.name && inp.name !== 'action' && inp.name !== 'rows_json' && inp.name !== 'delete_json' && inp.name.indexOf('rows[') !== 0) fd.append(inp.name, inp.value);
    });
    fetch(form.getAttribute('action') || '', {method:'POST', body:fd, credentials:'same-origin'});
  });
  document.addEventListener('click', function(ev){
    var btn = ev.target.closest('.nl-row-del');
    if (!btn) return;
    ev.preventDefault();
    var tr = btn.closest('tr');
    var form = gridRoot(btn);
    if (!tr || !form) return;
    var id = '';
    var cb = tr.querySelector('[data-nl-del]');
    tr.querySelectorAll('input').forEach(function(el){
      var nm = el.getAttribute('name') || '';
      if (nm.length > 4 && nm.slice(-4) === '[id]') id = (el.value || '').trim();
    });
    if (id === '' && cb) id = (cb.value || '').trim();
    if (id === '' || id.indexOf('new-') === 0) {
      tr.remove();
      var table = form.querySelector('table.nl-grid');
      if (table) filterTable(table);
      return;
    }
    if (!confirm('Diese Zeile löschen?')) return;
    tr.setAttribute('data-nl-remove', '1');
    if (cb) cb.checked = true;
    serializeGrid(form);
    var one = form.querySelector('input[name="delete_one"]');
    if (!one) {
      one = document.createElement('input');
      one.type = 'hidden';
      one.name = 'delete_one';
      form.appendChild(one);
    }
    one.value = id;
    var flag = form.querySelector('input[name="delete_checked"]');
    if (!flag) {
      flag = document.createElement('input');
      flag.type = 'hidden';
      flag.name = 'delete_checked';
      form.appendChild(flag);
    }
    flag.value = '1';
    form.submit();
  });
  document.addEventListener('click', function(ev){
    var btn = ev.target.closest('[data-nl-add-row]');
    if (!btn) return;
    var form = gridRoot(btn);
    if (!form) return;
    var tpl = form.querySelector('#nl-grid-blank');
    var tb = form.querySelector('table.nl-grid tbody');
    if (!tpl || !tb) return;
    var html = tpl.innerHTML;
    var n = tb.querySelectorAll('tr[data-nl-row]').length;
    html = html.replace(/rows\[9999\]/g, 'rows['+n+']').replace(/new-9999/g, 'new-'+n);
    var wrap = document.createElement('tbody');
    wrap.innerHTML = html.trim();
    var tr = wrap.firstElementChild;
    if (tr) {
      tb.appendChild(tr);
      var first = tr.querySelector('input[type=text], input[type=email], select');
      if (first) first.focus();
    }
  });
  document.addEventListener('keydown', function(ev){
    if (ev.key !== 'Enter' || ev.shiftKey) return;
    var cell = ev.target.closest('td.nl-col');
    if (!cell) return;
    var tr = cell.parentElement;
    var table = cell.closest('table.nl-grid');
    if (!tr || !table) return;
    ev.preventDefault();
    var col = cell.getAttribute('data-col');
    var next = tr.nextElementSibling;
    while (next && next.style.display === 'none') next = next.nextElementSibling;
    if (!next) return;
    var td = next.querySelector('td[data-col="'+col+'"]');
    var inp = td ? td.querySelector('input:not([type=hidden]), select') : null;
    if (inp) inp.focus();
  });
  document.addEventListener('submit', function(ev){
    var form = ev.target.closest('[data-nl-grid]');
    if (!form) return;
    serializeGrid(form);
  }, true);
})();
