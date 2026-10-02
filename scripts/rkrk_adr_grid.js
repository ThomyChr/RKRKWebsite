(function () {
  function boot() {
    var tables = document.querySelectorAll('table.rkrk-adr-grid');
    for (var i = 0; i < tables.length; i++) enhance(tables[i]);
  }

  function fitFeldScreen(table) {
    if (!table || !table.closest) return;
    var screen = table.closest('.rkrk-feld-screen');
    if (!screen) return;
    var wrap = screen.querySelector('.rkrk-adr-grid-wrap');
    if (!wrap) return;
    var rect = wrap.getBoundingClientRect();
    var vh = window.innerHeight || document.documentElement.clientHeight || 0;
    var vw = window.innerWidth || document.documentElement.clientWidth || 0;
    var height = Math.max(220, Math.round(vh - rect.top - 78));
    var width = Math.max(280, Math.round(vw - Math.max(0, rect.left) - 10));
    wrap.style.height = height + 'px';
    wrap.style.maxHeight = height + 'px';
    wrap.style.width = width + 'px';
    wrap.style.maxWidth = width + 'px';
  }

  function storageKey(table) {
    return 'rkrk-adr-ui-' + (table.getAttribute('data-adr-grid') || 'liste');
  }

  function filterKey(table) {
    return 'rkrk-adr-filter-' + (table.getAttribute('data-adr-grid') || 'liste');
  }

  function readUi(table) {
    var ui = { hidden: [], widths: {} };
    try { ui = JSON.parse(table.getAttribute('data-ui') || '{}') || ui; } catch (e) {}
    if (!Array.isArray(ui.hidden)) ui.hidden = [];
    if (!ui.widths || typeof ui.widths !== 'object') ui.widths = {};
    try {
      var local = JSON.parse(localStorage.getItem(storageKey(table)) || 'null');
      if (local && local.hidden) ui.hidden = local.hidden;
      if (local && local.widths) ui.widths = local.widths;
    } catch (e) {}
    return ui;
  }

  function formOf(table) {
    return table.closest('form') || table.parentNode;
  }

  function pillLabel(cb) {
    return (cb.parentNode.textContent || '').replace(/\s+/g, ' ').trim();
  }

  function checkedLabels(root) {
    if (!root) return { values: [], exclusive: false };
    var out = [];
    var exclusive = false;
    root.querySelectorAll('input[type="checkbox"]').forEach(function (cb) {
      if (!cb.checked) return;
      var lab = pillLabel(cb);
      if (cb.getAttribute('data-rkrk-exclusive') === '1' || /^all$/i.test(lab) || /^alle$/i.test(lab)) {
        exclusive = true;
      }
      out.push(lab);
    });
    return { values: out, exclusive: exclusive };
  }

  function restorePills(root, saved) {
    if (!root || !saved || !saved.length) return;
    var set = {};
    saved.forEach(function (v) { set[String(v)] = true; });
    root.querySelectorAll('input[type="checkbox"]').forEach(function (cb) {
      cb.checked = !!set[pillLabel(cb)];
    });
  }

  function enhance(table) {
    if (table.dataset.adrGridReady === '1') return;
    table.dataset.adrGridReady = '1';
    var ui = readUi(table);
    var header = table.querySelector('tr');
    if (!header) return;
    var thead = table.tHead || table.createTHead();
    if (header.parentNode !== thead) thead.appendChild(header);
    var ths = header.querySelectorAll('th');
    var colgroup = document.createElement('colgroup');
    for (var c = 0; c < ths.length; c++) {
      var col = document.createElement('col');
      var name = ths[c].getAttribute('data-col') || '';
      var w = parseInt(ui.widths[name], 10);
      if (!(w >= 24)) w = name.indexOf('__') === 0 ? 36 : 140;
      col.dataset.w = String(w);
      col.style.width = w + 'px';
      colgroup.appendChild(col);
      if (!ths[c].querySelector('.rkrk-adr-resize')) {
        var handle = document.createElement('span');
        handle.className = 'rkrk-adr-resize';
        handle.dataset.col = String(c);
        ths[c].appendChild(handle);
      }
      if (name.indexOf('__') !== 0) ths[c].classList.add('rkrk-adr-sortable');
    }
    table.insertBefore(colgroup, thead);

    var filter = document.createElement('tr');
    filter.className = 'rkrk-adr-filter';
    for (var f = 0; f < ths.length; f++) {
      var th = document.createElement('th');
      var colName = ths[f].getAttribute('data-col') || '';
      if (colName.indexOf('__') !== 0) {
        var inp = document.createElement('input');
        inp.type = 'search';
        inp.placeholder = '...';
        inp.setAttribute('aria-label', 'Filter ' + (ths[f].textContent || '').trim());
        inp.dataset.col = String(f);
        inp.addEventListener('input', function () { apply(table); });
        inp.addEventListener('keydown', function (ev) {
          if (ev.key === 'Enter') ev.preventDefault();
        });
        th.appendChild(inp);
      }
      filter.appendChild(th);
    }
    thead.appendChild(filter);

    var wrap = table.parentNode;
    var form = formOf(table);
    var existingVis = form ? form.querySelector('details.rkrk-adr-col-vis') : null;
    var preset = existingVis ? existingVis.querySelector('.rkrk-adr-cols') : null;
    var pills = preset && preset.querySelector('input') ? preset : null;
    if (!pills) {
      pills = document.createElement('div');
      pills.className = 'rkrk-adr-cols';
      for (var p = 0; p < ths.length; p++) {
        var key = ths[p].getAttribute('data-col') || '';
        if (key.indexOf('__') === 0) continue;
        var label = document.createElement('label');
        var box = document.createElement('input');
        box.type = 'checkbox';
        box.checked = ui.hidden.indexOf(key) === -1;
        box.dataset.col = key;
        label.appendChild(box);
        label.appendChild(document.createTextNode((ths[p].textContent || key).replace(/\s+/g, ' ').trim()));
        pills.appendChild(label);
      }
    }
    function syncPills() {
      pills.querySelectorAll('input').forEach(function (box) {
        if (box.parentNode && box.parentNode.classList) {
          box.parentNode.classList.toggle('is-on', !!box.checked);
        }
      });
    }
    function onColumnToggle() {
      syncPills();
      paintColumns(table);
      save(table);
    }
    pills.querySelectorAll('input').forEach(function (box) {
      if (box.dataset.col) box.checked = ui.hidden.indexOf(box.dataset.col) === -1;
      box.addEventListener('change', onColumnToggle);
    });
    pills.addEventListener('click', function (ev) {
      if (!ev.target.closest('label')) return;
      window.setTimeout(onColumnToggle, 0);
    });
    syncPills();

    function showEveryColumn() {
      pills.querySelectorAll('input').forEach(function (cb) { cb.checked = true; });
      onColumnToggle();
    }
    var showAll = existingVis ? existingVis.querySelector('.rkrk-adr-showall') : null;
    if (!showAll) {
      showAll = document.createElement('button');
      showAll.type = 'button';
      showAll.className = 'rkrk-adr-btn rkrk-adr-showall';
      showAll.textContent = 'Alle anzeigen';
    }
    if (!showAll.dataset.bound) {
      showAll.dataset.bound = '1';
      showAll.addEventListener('click', showEveryColumn);
    }
    if (wrap && wrap.classList.contains('rkrk-adr-grid-wrap')) {
      var tools = document.createElement('div');
      tools.className = 'rkrk-adr-grid-tools';
      if (!existingVis) {
        var vis = document.createElement('details');
        vis.className = 'rkrk-adr-col-vis';
        vis.open = true;
        var sum = document.createElement('summary');
        sum.textContent = 'Spalten ein-/ausblenden';
        vis.appendChild(sum);
        var note = document.createElement('p');
        note.className = 'rkrk-adr-note';
        note.textContent = 'Nicht angehakt = ausgeblendet. Die Einstellung bleibt gespeichert.';
        vis.appendChild(note);
        vis.appendChild(pills);
        vis.appendChild(showAll);
        tools.appendChild(vis);
      } else {
        existingVis.open = true;
        if (!existingVis.contains(showAll)) existingVis.appendChild(showAll);
        tools.appendChild(existingVis);
      }
      var bar = document.createElement('div');
      bar.className = 'rkrk-adr-toolbar';
      var searchLab = document.createElement('label');
      searchLab.appendChild(document.createTextNode('Suche '));
      var search = document.createElement('input');
      search.type = 'search';
      search.placeholder = 'Alle sichtbaren Spalten...';
      search.setAttribute('aria-label', 'Liste durchsuchen');
      search.addEventListener('input', function () { apply(table); });
      search.addEventListener('keydown', function (ev) {
        if (ev.key === 'Enter') ev.preventDefault();
      });
      searchLab.appendChild(search);
      var count = document.createElement('span');
      count.className = 'rkrk-adr-count';
      bar.appendChild(searchLab);
      bar.appendChild(count);
      tools.appendChild(bar);
      wrap.parentNode.insertBefore(tools, wrap);
      table._adrSearch = search;
      table._adrCount = count;
    }
    table._adrCols = pills;
    fitFeldScreen(table);

    try {
      var savedFilter = JSON.parse(localStorage.getItem(filterKey(table)) || 'null');
      if (savedFilter) {
        restorePills(form.querySelector('[data-adr-filter="typ"]'), savedFilter.types);
        restorePills(form.querySelector('[data-adr-filter="region"]'), savedFilter.regions);
        restorePills(form.querySelector('[data-adr-filter="flags"]'), savedFilter.flags);
      }
    } catch (e) {}

    paintColumns(table);

    var sortDir = {};
    header.addEventListener('click', function (ev) {
      if (suppressSort) {
        suppressSort = false;
        ev.preventDefault();
        ev.stopPropagation();
        return;
      }
      var origin = ev.target && ev.target.closest ? ev.target : (ev.target && ev.target.parentElement);
      if (!origin || !origin.closest) return;
      if (origin.closest('.rkrk-adr-resize, a, button, input')) return;
      var thClick = origin.closest('th.rkrk-adr-sortable');
      if (!thClick || thClick.parentNode !== header) return;
      var idx = Array.prototype.indexOf.call(ths, thClick);
      if (idx < 0) return;
      var colKey = thClick.getAttribute('data-col') || String(idx);
      sortDir[colKey] = sortDir[colKey] === 'asc' ? 'desc' : 'asc';
      for (var s = 0; s < ths.length; s++) ths[s].classList.remove('is-asc', 'is-desc');
      thClick.classList.add(sortDir[colKey] === 'asc' ? 'is-asc' : 'is-desc');
      var body = table.tBodies[0];
      if (!body) return;
      var rows = Array.prototype.slice.call(body.rows);
      var dir = sortDir[colKey] === 'asc' ? 1 : -1;
      rows.sort(function (a, b) {
        var ta = cellText(a.children[idx] || a);
        var tb = cellText(b.children[idx] || b);
        if (ta < tb) return -dir;
        if (ta > tb) return dir;
        return 0;
      });
      rows.forEach(function (r) { body.appendChild(r); });
    });

    try { apply(table); } catch (e) {}

    var addBtn = document.getElementById('rkrk-adr-add');
    if (addBtn && !addBtn.dataset.adrBound) {
      addBtn.dataset.adrBound = '1';
      addBtn.addEventListener('click', function () {
        var tpl = document.getElementById('rkrk-adr-new');
        var num = document.getElementById('NumEntries');
        var body = table.tBodies[0];
        if (!tpl || !num || !body) return;
        var n = (parseInt(num.value, 10) || 0) + 1;
        var holder = document.createElement('tbody');
        holder.innerHTML = tpl.innerHTML.replace(/__N__/g, String(n)).trim();
        var row = holder.firstElementChild;
        if (!row) return;
        body.appendChild(row);
        num.value = String(n);
        paintColumns(table);
        apply(table);
        var first = row.querySelector('input, textarea, select');
        if (first) first.focus();
      });
    }

    var drag = null;
    var suppressSort = false;
    table.addEventListener('mousedown', function (ev) {
      var h = ev.target.closest('.rkrk-adr-resize');
      if (!h || !table.contains(h)) return;
      ev.preventDefault();
      ev.stopPropagation();
      var idx = parseInt(h.dataset.col, 10);
      var col = colgroup.children[idx];
      if (!col) return;
      drag = { idx: idx, x: ev.clientX, w: parseInt(col.dataset.w, 10) || thWidth(table, idx), moved: false };
      document.body.style.cursor = 'col-resize';
    });
    document.addEventListener('mousemove', function (ev) {
      if (!drag) return;
      drag.moved = true;
      var name = (ths[drag.idx] && ths[drag.idx].getAttribute('data-col')) || '';
      var min = name.indexOf('__') === 0 ? 28 : 48;
      var nw = Math.max(min, Math.min(2000, Math.round(drag.w + ev.clientX - drag.x)));
      var col = colgroup.children[drag.idx];
      if (!col) return;
      col.dataset.w = String(nw);
      layoutColumns(table);
    });
    document.addEventListener('mouseup', function () {
      if (!drag) return;
      var moved = drag.moved;
      drag = null;
      document.body.style.cursor = '';
      layoutColumns(table);
      if (moved) {
        suppressSort = true;
        save(table);
        window.setTimeout(function () { suppressSort = false; }, 250);
      }
    });
    layoutColumns(table);
  }

  function columnState(table) {
    var ths = table.querySelectorAll('thead tr:first-child th');
    var hidden = [];
    var widths = {};
    var cols = table.querySelectorAll('col');
    var pills = table._adrCols ? table._adrCols.querySelectorAll('input') : [];
    for (var i = 0; i < pills.length; i++) {
      if (!pills[i].checked) hidden.push(pills[i].dataset.col);
    }
    for (var c = 0; c < ths.length; c++) {
      var name = ths[c].getAttribute('data-col') || '';
      if (!name || !cols[c]) continue;
      widths[name] = parseInt(cols[c].dataset.w, 10) || parseInt(cols[c].style.width, 10) || 0;
    }
    return { hidden: hidden, widths: widths };
  }

  function paintColumns(table) {
    var ths = table.querySelectorAll('thead tr:first-child th');
    var filters = table.querySelectorAll('thead tr.rkrk-adr-filter th');
    var hidden = {};
    var pills = table._adrCols ? table._adrCols.querySelectorAll('input') : [];
    for (var i = 0; i < pills.length; i++) {
      if (!pills[i].checked) hidden[pills[i].dataset.col] = true;
    }
    for (var c = 0; c < ths.length; c++) {
      var name = ths[c].getAttribute('data-col') || '';
      var off = !!hidden[name];
      ths[c].style.display = off ? 'none' : '';
      if (filters[c]) filters[c].style.display = off ? 'none' : '';
    }
    var rows = table.querySelectorAll('tbody tr');
    for (var r = 0; r < rows.length; r++) {
      if (rows[r].querySelector('td[colspan]')) continue;
      var cells = rows[r].children;
      for (var n = 0; n < ths.length && n < cells.length; n++) {
        var colName = ths[n].getAttribute('data-col') || '';
        cells[n].style.display = hidden[colName] ? 'none' : '';
      }
    }
    layoutColumns(table);
  }

  function thWidth(table, idx) {
    var ths = table.querySelectorAll('thead tr:first-child th');
    var th = ths[idx];
    if (!th) return 140;
    var w = Math.round(th.getBoundingClientRect().width);
    return w > 8 ? w : 140;
  }

  function layoutColumns(table) {
    var ths = table.querySelectorAll('thead tr:first-child th');
    var cols = table.querySelectorAll('col');
    var filters = table.querySelectorAll('thead tr.rkrk-adr-filter th');
    var sum = 0;
    for (var i = 0; i < ths.length; i++) {
      var hidden = ths[i].style.display === 'none';
      var desired = cols[i] ? (parseInt(cols[i].dataset.w, 10) || parseInt(cols[i].style.width, 10) || 140) : 140;
      var w = hidden ? 0 : desired;
      if (cols[i]) cols[i].style.width = w + 'px';
      ths[i].style.width = w + 'px';
      ths[i].style.maxWidth = w + 'px';
      if (filters[i]) {
        filters[i].style.width = w + 'px';
        filters[i].style.maxWidth = w + 'px';
      }
      sum += w;
    }
    table.style.width = Math.max(sum, 1) + 'px';
    var header = table.querySelector('thead tr:first-child');
    if (header) {
      var h = Math.round(header.getBoundingClientRect().height);
      if (h > 0) table.style.setProperty('--rkrk-head-h', h + 'px');
    }
  }

  function save(table) {
    var state = columnState(table);
    try { localStorage.setItem(storageKey(table), JSON.stringify(state)); } catch (e) {}
    try {
      fetch('scripts/rkrk_adr_ui.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          list: table.getAttribute('data-adr-grid') || '',
          hidden: state.hidden,
          widths: state.widths
        }),
        credentials: 'same-origin'
      });
    } catch (e) {}
  }

  function saveFilters(table) {
    var form = formOf(table);
    var types = checkedLabels(form.querySelector('[data-adr-filter="typ"]')).values;
    var regions = checkedLabels(form.querySelector('[data-adr-filter="region"]')).values;
    var flags = checkedLabels(form.querySelector('[data-adr-filter="flags"]')).values;
    try {
      localStorage.setItem(filterKey(table), JSON.stringify({ types: types, regions: regions, flags: flags }));
    } catch (e) {}
  }

  function controlText(el) {
    if (!el || !el.tagName) return null;
    var tag = el.tagName;
    if (tag === 'SELECT') {
      var parts = [];
      var opts = el.options || [];
      for (var i = 0; i < opts.length; i++) {
        if (opts[i].selected) parts.push(opts[i].text || opts[i].value || '');
      }
      return parts.join(' ');
    }
    if (tag === 'TEXTAREA') return el.value || '';
    if (tag === 'INPUT') {
      var t = (el.type || 'text').toLowerCase();
      if (t === 'hidden' || t === 'image' || t === 'button' || t === 'submit' || t === 'file') return null;
      if (t === 'checkbox' || t === 'radio') return el.checked ? (el.value || '1') : '';
      return el.value || '';
    }
    return null;
  }

  function cellText(td) {
    if (!td) return '';
    var bits = [];
    var controls = td.querySelectorAll('input, textarea, select');
    var sawVisible = false;
    for (var i = 0; i < controls.length; i++) {
      var value = controlText(controls[i]);
      if (value === null) continue;
      sawVisible = true;
      if (value) bits.push(value);
    }
    var nodes = td.childNodes;
    for (var n = 0; n < nodes.length; n++) {
      var node = nodes[n];
      if (node.nodeType === 3) {
        bits.push(node.nodeValue || '');
      } else if (!sawVisible && node.nodeType === 1) {
        bits.push(node.textContent || '');
      } else if (node.nodeType === 1 && node.tagName !== 'INPUT' && node.tagName !== 'SELECT' && node.tagName !== 'TEXTAREA' && !node.querySelector('input, textarea, select')) {
        bits.push(node.textContent || '');
      }
    }
    return bits.join(' ').toLowerCase().replace(/\s+/g, ' ').trim();
  }

  function colIndex(table, name) {
    var ths = table.querySelectorAll('thead tr:first-child th');
    for (var i = 0; i < ths.length; i++) {
      if (ths[i].getAttribute('data-col') === name) return i;
    }
    return -1;
  }

  function tokens(text) {
    return String(text || '').split(/[,;/]+/).map(function (s) { return s.trim().toLowerCase(); }).filter(Boolean);
  }

  function apply(table) {
    var form = formOf(table);
    var search = table._adrSearch ? table._adrSearch.value.toLowerCase().trim() : '';
    var filters = table.querySelectorAll('thead tr.rkrk-adr-filter input');
    var typ = checkedLabels(form.querySelector('[data-adr-filter="typ"]'));
    var typIdx = colIndex(table, 'Typ');
    var jgIdx = colIndex(table, 'Jagdgebiet');
    var visIdx = [];
    table.querySelectorAll('thead tr:first-child th').forEach(function (th, i) {
      if (th.style.display === 'none') return;
      if ((th.getAttribute('data-col') || '').indexOf('__') === 0) return;
      visIdx.push(i);
    });
    var rows = table.querySelectorAll('tbody tr');
    var shown = 0;
    for (var r = 0; r < rows.length; r++) {
      var row = rows[r];
      if (row.querySelector('td[colspan]')) {
        row.style.display = '';
        continue;
      }
      var cells = row.children;
      var ok = true;
      if (typ.values.length && typIdx >= 0) {
        var tv = cellText(cells[typIdx]);
        var hit = false;
        for (var t = 0; t < typ.values.length; t++) {
          if (tv === typ.values[t].toLowerCase() || tv.indexOf(typ.values[t].toLowerCase()) !== -1) { hit = true; break; }
        }
        if (!hit) ok = false;
      } else if (form.querySelector('[data-adr-filter="typ"]') && typ.values.length === 0) {
        ok = false;
      }
      if (ok) {
        var regionRoot = form.querySelector('[data-adr-filter="region"]');
        if (regionRoot) {
          var allowed = {};
          var specific = false;
          var exclusive = false;
          regionRoot.querySelectorAll('input[type="checkbox"]').forEach(function (cb) {
            var lab = pillLabel(cb);
            var isAll = cb.getAttribute('data-rkrk-exclusive') === '1' || /^alle$/i.test(lab) || /^all$/i.test(lab);
            if (!cb.checked) return;
            if (isAll) {
              exclusive = true;
              return;
            }
            specific = true;
            var list = cb.getAttribute('data-jg-list') || lab;
            String(list).split(/[,;]+/).forEach(function (j) {
              j = j.trim().toLowerCase();
              if (j) allowed[j] = true;
            });
          });
          if (specific) {
            var rowJg = (row.getAttribute('data-jg') || '').trim();
            var jgTok = rowJg ? tokens(rowJg) : (jgIdx >= 0 ? tokens(cellText(cells[jgIdx])) : []);
            var rhit = false;
            for (var ji = 0; ji < jgTok.length; ji++) {
              if (allowed[jgTok[ji]]) rhit = true;
            }
            if (!rhit) ok = false;
          } else if (!exclusive) {
            ok = false;
          }
        }
      }
      if (ok) {
        var flagRoot = form.querySelector('[data-adr-filter="flags"]');
        if (flagRoot) {
          var wantNew = false;
          var wantChg = false;
          flagRoot.querySelectorAll('input[type="checkbox"]:checked').forEach(function (cb) {
            var flag = (cb.getAttribute('data-flag') || '').toLowerCase();
            if (flag === 'new') wantNew = true;
            if (flag === 'changed') wantChg = true;
          });
          if (wantNew || wantChg) {
            var isNew = row.getAttribute('data-new') === '1';
            var isChg = row.getAttribute('data-changed') === '1';
            if (!((wantNew && isNew) || (wantChg && isChg))) ok = false;
          }
        }
      }
      var blob = '';
      for (var c = 0; c < visIdx.length; c++) {
        blob += ' ' + cellText(cells[visIdx[c]]);
      }
      if (ok && search && blob.indexOf(search) === -1) ok = false;
      for (var f = 0; ok && f < filters.length; f++) {
        var q = filters[f].value.toLowerCase().trim();
        if (!q) continue;
        var idx = parseInt(filters[f].dataset.col, 10);
        if (!cells[idx] || cellText(cells[idx]).indexOf(q) === -1) ok = false;
      }
      row.style.display = ok ? '' : 'none';
      if (ok) shown++;
    }
    if (table._adrCount) table._adrCount.textContent = shown + ' Zeilen';
  }

  document.addEventListener('change', function (ev) {
    var cb = ev.target;
    if (!cb || cb.type !== 'checkbox') return;
    if (cb.closest('.rkrk-adr-col-vis')) return;
    var head = cb.closest('.rkrk-adr-head');
    if (!head) return;
    var form = head.closest('form');
    var table = form ? form.querySelector('table.rkrk-adr-grid') : null;
    if (!table) return;
    window.setTimeout(function () {
      saveFilters(table);
      apply(table);
    }, 0);
  });

  document.addEventListener('keydown', function (ev) {
    if (ev.key !== 'Enter' || ev.shiftKey) return;
    var td = ev.target.closest('table.rkrk-adr-grid td');
    if (!td) return;
    var tr = td.parentElement;
    var table = td.closest('table.rkrk-adr-grid');
    if (!tr || !table) return;
    ev.preventDefault();
    var idx = Array.prototype.indexOf.call(tr.children, td);
    var next = tr.nextElementSibling;
    while (next && next.style.display === 'none') next = next.nextElementSibling;
    if (!next) return;
    var cell = next.children[idx];
    var inp = cell ? cell.querySelector('input:not([type=hidden]), textarea, select') : null;
    if (inp) inp.focus();
  });

  window.addEventListener('resize', function () {
    document.querySelectorAll('.rkrk-feld-screen table.rkrk-adr-grid').forEach(function (table) {
      fitFeldScreen(table);
      layoutColumns(table);
    });
  });

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
  else boot();
})();
