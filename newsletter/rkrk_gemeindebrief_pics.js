(function () {
	var list = document.getElementById('gb-sections');
	var own = document.getElementById('gb-own');

	function esc(s) {
		return String(s == null ? '' : s)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
	}

	function asHtml(s) {
		s = String(s == null ? '' : s);
		if (s.indexOf('<') < 0) {
			return esc(s).replace(/\n/g, '<br>');
		}
		s = s.split('</script>').join('').split('</style>').join('');
		s = s.replace(/<script\b[^>]*>/gi, '').replace(/<style\b[^>]*>/gi, '');
		s = s.replace(/\son\w+=/gi, ' data-x=').replace(/javascript:/gi, '');
		return s;
	}

	function cropBox(raw) {
		var c;
		try { c = JSON.parse(raw || ''); } catch (e) { return null; }
		if (!c || typeof c !== 'object') return null;
		var x = Math.max(0, Math.min(100, Number(c.x) || 0));
		var y = Math.max(0, Math.min(100, Number(c.y) || 0));
		var w = Math.max(0, Math.min(100, Number(c.w) || 0));
		var h = Math.max(0, Math.min(100, Number(c.h) || 0));
		if (w < 5 || h < 5) return null;
		if (x + w > 100) w = 100 - x;
		if (y + h > 100) h = 100 - y;
		if (x < 0.4 && y < 0.4 && w > 99.2 && h > 99.2) return null;
		return { x: x, y: y, w: w, h: h };
	}

	function figure(src, cap, cls, crop, nw, nh, frame) {
		if (!src) return '';
		var safe = String(src).replace(/"/g, '');
		var alt = esc(cap);
		var fw = 0;
		var fh = 0;
		if (crop && nw && nh) {
			fw = nw * crop.w / 100;
			fh = nh * crop.h / 100;
		}
		var inner;
		if (fw >= 1 && fh >= 1) {
			var pad = (fh / fw * 100).toFixed(3);
			var imgW = (100 / (crop.w / 100)).toFixed(3);
			var imgH = (100 / (crop.h / 100)).toFixed(3);
			var left = (-crop.x / crop.w * 100).toFixed(3);
			var top = (-crop.y / crop.h * 100).toFixed(3);
			inner = '<span class="nl-crop-view" style="padding-bottom:' + pad + '%;--nl-ar:' + (fw / fh).toFixed(4) + '"><img src="' + safe + '" alt="' + alt + '" style="position:absolute;width:' + imgW + '%;height:' + imgH + '%;left:' + left + '%;top:' + top + '%;max-width:none;max-height:none;object-fit:fill;"></span>';
		} else {
			inner = '<img src="' + safe + '" alt="' + alt + '">';
		}
		var style = (frame && frame.mm > 0) ? (' style="--nl-row-w:' + frame.mm.toFixed(2) + 'mm"') : '';
		var html = '<figure' + (cls ? ' class="' + cls + '"' : '') + style + '>' + inner;
		if (cap) html += '<figcaption>' + (String(cap).indexOf('<') >= 0 ? asHtml(cap) : esc(cap)) + '</figcaption>';
		return html + '</figure>';
	}

	function rowFrames(shots) {
		var target = 48;
		var content = 150;
		var gap = 3;
		var ratios = [];
		var i;
		for (i = 0; i < shots.length; i++) {
			var s = shots[i];
			var w = s.nw || 0;
			var h = s.nh || 0;
			if (s.crop && w && h) {
				w = w * s.crop.w / 100;
				h = h * s.crop.h / 100;
			}
			if (!(w > 0) || !(h > 0)) return null;
			ratios.push(w / h);
		}
		var sum = 0;
		for (i = 0; i < ratios.length; i++) sum += ratios[i];
		var gaps = gap * Math.max(0, ratios.length - 1);
		var height = target;
		if (sum > 0 && (target * sum + gaps) > content) {
			height = (content - gaps) / sum;
		}
		if (height < 12) height = 12;
		var dims = [];
		for (i = 0; i < ratios.length; i++) dims.push({ mm: height * ratios[i] });
		return dims;
	}

	function shotsHtml(shots, row) {
		var frames = (row && shots.length >= 2) ? rowFrames(shots) : null;
		var html = '';
		shots.forEach(function (shot, i) {
			html += figure(shot.src, shot.cap, row ? 'nl-pic-row' : 'nl-pic-in', shot.crop, shot.nw, shot.nh, frames ? frames[i] : null);
		});
		return html;
	}

	function armShot(el) {
		if (!el || el.naturalWidth) return;
		if (el.getAttribute('data-nl-armed') === '1') return;
		el.setAttribute('data-nl-armed', '1');
		var done = function () {
			if (!el.naturalWidth) return;
			el.setAttribute('data-nl-armed', '2');
			if (window.nlRepaint) window.nlRepaint();
			else if (window.gbPaintTheme) window.gbPaintTheme();
		};
		el.addEventListener('load', done);
		if (el.complete) done();
	}

	function slotShot(slot) {
		var ownOn = slot.querySelector('.nl-scope-own');
		var own = ownOn && ownOn.checked;
		var hideFlag = slot.querySelector('.nl-hide-flag');
		if (!own && hideFlag && hideFlag.value === '1') return null;
		var img = slot.querySelector('.nl-pic-preview');
		var ownImg = slot.querySelector('.nl-own-preview');
		var capIn = slot.querySelector('.nl-pic-cap');
		var ownCap = slot.querySelector('.nl-own-cap');
		var imgEl = (own && ownImg && ownImg.getAttribute('src')) ? ownImg : img;
		var src = imgEl ? (imgEl.getAttribute('src') || '') : '';
		if (!src) return null;
		var cap = own && ownCap ? ownCap.value.trim() : (capIn ? capIn.value.trim() : '');
		var hidden = own ? slot.querySelector('.nl-own-crop') : slot.querySelector('.nl-pic-crop');
		var crop = (hidden && hidden.value) ? cropBox(hidden.value) : null;
		armShot(imgEl);
		var nw = imgEl.naturalWidth || parseInt(imgEl.getAttribute('data-nw'), 10) || 0;
		var nh = imgEl.naturalHeight || parseInt(imgEl.getAttribute('data-nh'), 10) || 0;
		return { src: src, cap: cap, crop: crop, nw: nw, nh: nh };
	}

	function ownSlots() {
		var slots = [];
		if (!own) return slots;
		own.querySelectorAll('.nl-pic').forEach(function (box) {
			var img = box.querySelector('.nl-pic-preview');
			var cap = box.querySelector('[name="gde_cap[]"]');
			slots.push({
				src: img ? (img.getAttribute('src') || '') : '',
				cap: cap ? cap.value.trim() : ''
			});
		});
		return slots;
	}

	window.gbPaintTheme = function () {
		var box = document.getElementById('nl-pv-theme');
		if (!box || !list) return;
		var cards = list.querySelectorAll('.nl-sec');
		var owns = ownSlots();
		var any = false;
		cards.forEach(function (card) {
			var title = card.querySelector('[name="sec_title[]"]');
			var text = card.querySelector('[name="sec_text[]"]');
			var showEl = card.querySelector('.nl-show-title');
			var showTitle = !showEl || showEl.value !== '0';
			if ((showTitle && title && title.value.trim()) || (text && text.value.trim()) || card.querySelector('.nl-pic-preview') || card.querySelector('.nl-scope-own:checked')) any = true;
		});
		var html = '';
		if (!any) {
			owns.forEach(function (slot) {
				html += figure(slot.src, slot.cap);
			});
			box.innerHTML = html;
			return;
		}
		var idx = 0;
		cards.forEach(function (card) {
			var title = card.querySelector('[name="sec_title[]"]');
			var text = card.querySelector('[name="sec_text[]"]');
			var showEl = card.querySelector('.nl-show-title');
			var showTitle = !showEl || showEl.value !== '0';
			var t = title && showTitle ? title.value.trim() : '';
			var x = text ? text.value.trim() : '';
			if (t) html += '<h2>' + esc(t) + '</h2>';
			var textHtml = x ? ('<div class="nl-block">' + (x.indexOf('<') >= 0 ? asHtml(x) : ('<p>' + esc(x).replace(/\n/g, '<br>') + '</p>')) + '</div>') : '';
			if (textHtml && window.gbFillTokens) textHtml = window.gbFillTokens(textHtml);
			var shots = [];
			card.querySelectorAll('.nl-pic').forEach(function (slot) {
				var shot = slotShot(slot);
				if (!shot) return;
				shots.push(shot);
				idx++;
			});
			if (shots.length === 1) html += '<div class="nl-sec-flow">' + shotsHtml(shots, false) + textHtml + '</div>';
			else {
				html += textHtml;
				if (shots.length) html += '<div class="nl-pics-row">' + shotsHtml(shots, true) + '</div>';
			}
		});
		for (; idx < owns.length; idx++) {
			if (!owns[idx].src) continue;
			html += figure(owns[idx].src, owns[idx].cap);
		}
		box.innerHTML = html;
	};

	function paint() {
		if (window.gbPaintTheme) window.gbPaintTheme();
	}

	function ensureSpare(card) {
		if (card && card.id === 'gb-intro') return;
		var hasMain = card.querySelector('.nl-pic-file[name="sec_file[]"]');
		var spare = card.querySelector('.nl-pic-spare-file');
		if (hasMain) {
			if (spare) spare.remove();
			return;
		}
		if (!spare) {
			spare = document.createElement('input');
			spare.type = 'file';
			spare.name = 'sec_file[]';
			spare.className = 'nl-pic-spare-file';
			spare.hidden = true;
			var slots = card.querySelector('.nl-pic-list');
			if (slots && slots.parentNode) slots.parentNode.insertBefore(spare, slots.nextSibling);
		}
	}

	window.gbIntroHtml = function () {
		var card = document.getElementById('gb-intro');
		if (!card) return '';
		var shots = [];
		card.querySelectorAll('.nl-pic').forEach(function (slot) {
			var shot = slotShot(slot);
			if (shot) shots.push(shot);
		});
		if (!shots.length) return '';
		if (shots.length === 1) return '<div class="nl-sec-flow">' + shotsHtml(shots, false) + '</div>';
		return '<div class="nl-pics-row">' + shotsHtml(shots, true) + '</div>';
	};

	function syncPics(card) {
		if (card && card.id === 'gb-intro') {
			card.querySelectorAll('.nl-pic').forEach(function (slot) {
				var path = slot.querySelector('.nl-pic-path');
				var file = slot.querySelector('.nl-pic-file');
				var capIn = slot.querySelector('.nl-pic-cap');
				var crop = slot.querySelector('.nl-pic-crop');
				if (path) path.name = 'intro_keep[]';
				if (file) file.name = 'intro_file[]';
				if (capIn) capIn.name = 'intro_cap[]';
				if (crop) crop.name = 'intro_crop[]';
				var flag = slot.querySelector('.nl-own-flag');
				if (flag) flag.name = 'own_on[intro][]';
				var hideFlag = slot.querySelector('.nl-hide-flag');
				if (hideFlag) hideFlag.name = 'own_hide[intro][]';
				var ownPath = slot.querySelector('.nl-own-path');
				if (ownPath) ownPath.name = 'own_keep[intro][]';
				var ownCap = slot.querySelector('.nl-own-cap');
				if (ownCap) ownCap.name = 'own_cap[intro][]';
				var ownFile = slot.querySelector('.nl-own-file');
				if (ownFile) ownFile.name = 'own_file[intro][]';
				var ownCrop = slot.querySelector('.nl-own-crop');
				if (ownCrop) ownCrop.name = 'own_crop[intro][]';
			});
			return;
		}
		var idEl = card.querySelector('[name="sec_id[]"]');
		var id = idEl && idEl.value ? idEl.value : 's';
		var mainHidden = card.querySelector('[name="sec_image[]"]');
		card.querySelectorAll('.nl-pic').forEach(function (slot, i) {
			var path = slot.querySelector('.nl-pic-path');
			var file = slot.querySelector('.nl-pic-file');
			var main = i === 0;
			slot.classList.toggle('is-main', main);
			if (main) {
				if (path) path.removeAttribute('name');
				if (mainHidden) mainHidden.value = path ? (path.value || '') : '';
				if (file) {
					file.name = 'sec_file[]';
					file.classList.add('nl-file-input');
				}
			} else {
				if (path) path.name = 'sec_more_keep[' + id + '][]';
				if (file) {
					file.name = 'sec_more[' + id + '][]';
					file.classList.remove('nl-file-input');
				}
			}
			var capIn = slot.querySelector('.nl-pic-cap');
			if (capIn) capIn.name = 'sec_cap[' + id + '][]';
			var flag = slot.querySelector('.nl-own-flag');
			if (flag) flag.name = 'own_on[' + id + '][]';
			var hideFlag = slot.querySelector('.nl-hide-flag');
			if (hideFlag) hideFlag.name = 'own_hide[' + id + '][]';
			var ownPath = slot.querySelector('.nl-own-path');
			if (ownPath) ownPath.name = 'own_keep[' + id + '][]';
			var ownCap = slot.querySelector('.nl-own-cap');
			if (ownCap) ownCap.name = 'own_cap[' + id + '][]';
			var ownFile = slot.querySelector('.nl-own-file');
			if (ownFile) ownFile.name = 'own_file[' + id + '][]';
			var crop = slot.querySelector('.nl-pic-crop');
			if (crop) crop.name = 'sec_crop[' + id + '][]';
			var ownCrop = slot.querySelector('.nl-own-crop');
			if (ownCrop) ownCrop.name = 'own_crop[' + id + '][]';
		});
		ensureSpare(card);
	}

	function showFile(box, file) {
		if (!file.files || !file.files[0]) return;
		var mask = box.querySelector('.nl-img-mask');
		var prev = box.querySelector('.nl-pic-preview');
		if (!prev) {
			prev = document.createElement('img');
			prev.className = 'nl-pic-preview';
			prev.alt = '';
			if (mask) mask.appendChild(prev);
		}
		prev.src = URL.createObjectURL(file.files[0]);
		var path = box.querySelector('.nl-pic-path');
		if (path) path.value = '';
		box.classList.remove('is-empty');
		var load = box.querySelector('.nl-pic-load');
		if (load) load.textContent = 'Bild ändern';
		if (!box.querySelector('.nl-crop-show')) {
			var row = box.querySelector('.nl-file-row');
			if (row) {
				var b = document.createElement('button');
				b.type = 'button';
				b.className = 'nl-btn secondary nl-crop-show';
				b.textContent = 'Ausschnitt wählen';
				b.addEventListener('click', function () { if (window.nlOpenCrop) window.nlOpenCrop(box); });
				row.appendChild(b);
			}
		}
	}

	function bindPic(box, card) {
		var load = box.querySelector('.nl-pic-load');
		var file = box.querySelector('.nl-pic-file');
		var del = box.querySelector('.nl-pic-del');
		var handle = box.querySelector('.nl-pic-handle');
		if (load && file) load.addEventListener('click', function () { file.click(); });
		var ownFlag = box.querySelector('.nl-own-flag');
		var ownBox = box.querySelector('.nl-own-box');
		var hideFlag = box.querySelector('.nl-hide-flag');
		var hideBtn = box.querySelector('.nl-pic-hide');
		function applyScope() {
			var own = box.querySelector('.nl-scope-own');
			var isOwn = own && own.checked;
			if (ownFlag) ownFlag.value = isOwn ? '1' : '0';
			if (ownBox) ownBox.hidden = !isOwn;
			if (isOwn && hideFlag) hideFlag.value = '0';
			var off = !isOwn && hideFlag && hideFlag.value === '1';
			box.classList.toggle('is-off', off);
			if (hideBtn) {
				hideBtn.hidden = isOwn;
				hideBtn.textContent = off ? 'Wieder zeigen' : 'Hier ausblenden';
			}
			paint();
		}
		box.querySelectorAll('.nl-scope-all, .nl-scope-own').forEach(function (el) {
			el.addEventListener('change', applyScope);
		});
		if (hideBtn) hideBtn.addEventListener('click', function () {
			if (!hideFlag) return;
			hideFlag.value = hideFlag.value === '1' ? '0' : '1';
			applyScope();
		});
		var ownLoad = box.querySelector('.nl-own-load');
		var ownFile = box.querySelector('.nl-own-file');
		if (ownLoad && ownFile) ownLoad.addEventListener('click', function () { ownFile.click(); });
		if (ownFile) ownFile.addEventListener('change', function () {
			if (!ownFile.files || !ownFile.files[0]) return;
			var prev = box.querySelector('.nl-own-preview');
			if (!prev) {
				prev = document.createElement('img');
				prev.className = 'nl-own-preview nl-sec-preview';
				prev.alt = '';
				if (ownBox) ownBox.insertBefore(prev, ownBox.firstChild);
			}
			prev.src = URL.createObjectURL(ownFile.files[0]);
			var ownPath = box.querySelector('.nl-own-path');
			if (ownPath) ownPath.value = '';
			var ownCrop = box.querySelector('.nl-own-crop');
			if (ownCrop) ownCrop.value = '';
			if (ownLoad) ownLoad.textContent = 'Bild hier ändern';
			if (ownBox && !ownBox.querySelector('.nl-own-crop-show')) {
				var ownRow = ownBox.querySelector('.nl-file-row');
				if (ownRow) {
					var ob = document.createElement('button');
					ob.type = 'button';
					ob.className = 'nl-btn secondary nl-own-crop-show gb-own';
					ob.textContent = 'Ausschnitt wählen';
					ob.addEventListener('click', function () { if (window.nlOpenCrop) window.nlOpenCrop(ownBox); });
					ownRow.appendChild(ob);
				}
			}
			paint();
		});
		var cropBtn = box.querySelector('.nl-crop-show');
		if (cropBtn) cropBtn.addEventListener('click', function () {
			if (window.nlOpenCrop) window.nlOpenCrop(box);
		});
		var ownCropBtn = box.querySelector('.nl-own-crop-show');
		if (ownCropBtn && ownBox) ownCropBtn.addEventListener('click', function () {
			if (window.nlOpenCrop) window.nlOpenCrop(ownBox);
		});
		if (file) file.addEventListener('change', function () {
			showFile(box, file);
			var crop = box.querySelector('.nl-pic-crop');
			if (crop) crop.value = '';
			if (card) syncPics(card);
			paint();
		});
		if (del) del.addEventListener('click', function () {
			box.remove();
			if (card) syncPics(card);
			paint();
		});
		if (!handle) return;
		handle.addEventListener('mousedown', function () { box.draggable = true; });
		box.addEventListener('dragstart', function (ev) {
			if (!box.draggable) {
				ev.preventDefault();
				return;
			}
			box.classList.add('is-drag');
			ev.dataTransfer.effectAllowed = 'move';
			ev.dataTransfer.setData('text/plain', 'pic');
			ev.stopPropagation();
			if (card) card._picDrag = box;
			else if (own) own._picDrag = box;
		});
		box.addEventListener('dragend', function () {
			box.classList.remove('is-drag');
			box.draggable = false;
			if (card) {
				card._picDrag = null;
				syncPics(card);
			} else if (own) {
				own._picDrag = null;
			}
			paint();
		});
	}

	var scopeN = 1;
	function picSlot(card) {
		var box = document.createElement('div');
		box.className = 'nl-pic is-empty';
		if (card) {
			box.innerHTML = '<span class="nl-pic-handle" title="Ziehen zum Sortieren">⋮⋮</span>'
				+ '<div class="nl-img-mask"></div>'
				+ '<input class="nl-pic-path" type="hidden" value="">'
				+ '<input class="nl-pic-file" type="file" accept="image/*" hidden>'
				+ '<div class="nl-file-row"><button type="button" class="nl-btn secondary nl-pic-load">Bild laden</button>'
				+ '<button type="button" class="nl-icon-btn nl-icon-danger nl-pic-del" title="Bild entfernen" aria-label="Bild entfernen"><svg class="nl-ico" viewBox="0 0 24 24" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" d="M4 7h16M9 7V5h6v2M8 7l1 13h6l1-13"/></svg></button></div>'
				+ '<input type="hidden" class="nl-pic-crop" value="">'
				+ '<label>Bildbeschriftung</label><input type="text" class="nl-pic-cap" value="">'
				+ '<div class="nl-pic-scope"><label><input type="radio" class="nl-scope-all" checked> Alle</label>'
				+ '<label><input type="radio" class="nl-scope-own gb-own"> Nur diese Gemeinde</label></div>'
				+ '<input type="hidden" class="nl-own-flag" value="0">'
				+ '<input type="hidden" class="nl-hide-flag" value="0">'
				+ '<button type="button" class="nl-btn secondary nl-pic-hide gb-own">Hier ausblenden</button>'
				+ '<div class="nl-own-box" hidden>'
				+ '<input type="hidden" class="nl-own-path" value="">'
				+ '<input class="nl-own-file" type="file" accept="image/*" hidden>'
				+ '<div class="nl-file-row"><button type="button" class="nl-btn secondary nl-own-load gb-own">Eigenes Bild</button></div>'
				+ '<input type="hidden" class="nl-own-crop" value="">'
				+ '<label>Beschriftung nur hier</label><input type="text" class="nl-own-cap gb-own" value="">'
				+ '</div>';
			scopeN += 1;
			var scopeName = 'pic-scope-js-' + scopeN;
			box.querySelectorAll('.nl-scope-all, .nl-scope-own').forEach(function (el) { el.name = scopeName; });
			bindPic(box, card);
		} else {
			box.innerHTML = '<span class="nl-pic-handle" title="Ziehen zum Sortieren">⋮⋮</span>'
				+ '<div class="nl-img-mask"></div>'
				+ '<input class="nl-pic-path" type="hidden" name="gde_keep[]" value="">'
				+ '<input class="nl-pic-file" type="file" name="gde_file[]" accept="image/*" hidden>'
				+ '<label>Beschriftung</label><input type="text" class="gb-own" name="gde_cap[]" value="">'
				+ '<div class="nl-file-row"><button type="button" class="nl-btn secondary nl-pic-load gb-own">Bild laden</button>'
				+ '<button type="button" class="nl-icon-btn nl-icon-danger nl-pic-del" title="Bild entfernen" aria-label="Bild entfernen"><svg class="nl-ico" viewBox="0 0 24 24" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" d="M4 7h16M9 7V5h6v2M8 7l1 13h6l1-13"/></svg></button></div>';
			bindPic(box, null);
			var cap = box.querySelector('[name="gde_cap[]"]');
			if (cap) cap.addEventListener('input', paint);
		}
		return box;
	}

	function bindListDrag(picList, card) {
		if (!picList) return;
		picList.addEventListener('dragover', function (ev) {
			var drag = card ? card._picDrag : own._picDrag;
			if (!drag) return;
			ev.preventDefault();
			ev.stopPropagation();
			var node = ev.target && ev.target.closest ? ev.target : null;
			var over = node && node.closest ? node.closest('.nl-pic') : null;
			if (!over || over === drag || over.parentNode !== picList) return;
			var box = over.getBoundingClientRect();
			var dx = ev.clientX - (box.left + box.width / 2);
			var dy = ev.clientY - (box.top + box.height / 2);
			var after = Math.abs(dx) > Math.abs(dy) ? dx > 0 : dy > 0;
			picList.insertBefore(drag, after ? over.nextSibling : over);
		});
	}

	function bindCard(card) {
		card.addEventListener('input', paint);
		var tog = card.querySelector('.nl-sec-toggle');
		if (tog) tog.addEventListener('click', function () {
			var closed = card.classList.toggle('is-collapsed');
			tog.setAttribute('aria-expanded', closed ? 'false' : 'true');
		});
		var titleIn = card.querySelector('[name="sec_title[]"]');
		if (titleIn && tog) titleIn.addEventListener('input', function () {
			tog.textContent = titleIn.value.trim() || 'Sektion';
		});
		var showBox = card.querySelector('.nl-show-title-box');
		var showHid = card.querySelector('.nl-show-title');
		if (showBox && showHid) showBox.addEventListener('change', function () {
			showHid.value = showBox.checked ? '1' : '0';
			paint();
		});
		var moreBtn = card.querySelector('.nl-more-show');
		if (moreBtn) moreBtn.addEventListener('click', function () {
			var slots = card.querySelector('.nl-pic-list');
			if (slots) slots.appendChild(picSlot(card));
			syncPics(card);
		});
		card.querySelectorAll('.nl-pic').forEach(function (box) { bindPic(box, card); });
		bindListDrag(card.querySelector('.nl-pic-list'), card);
		var del = card.querySelector('.nl-sec-del');
		if (del) del.addEventListener('click', function () {
			if (list.querySelectorAll('.nl-sec').length < 2) {
				card.querySelectorAll('input[type=text], textarea').forEach(function (el) { el.value = ''; });
				card.querySelectorAll('.nl-pic').forEach(function (n) { n.remove(); });
				var hidden = card.querySelector('[name="sec_image[]"]');
				if (hidden) hidden.value = '';
				ensureSpare(card);
				if (tog) tog.textContent = 'Sektion';
				paint();
				return;
			}
			card.remove();
			paint();
		});
		var handle = card.querySelector('.nl-sec-handle');
		if (handle) {
			handle.addEventListener('dragstart', function (ev) {
				card.classList.add('is-drag');
				ev.dataTransfer.effectAllowed = 'move';
				ev.dataTransfer.setData('text/plain', 'sec');
				list._drag = card;
			});
			handle.addEventListener('dragend', function () {
				card.classList.remove('is-drag');
				list._drag = null;
				paint();
			});
		}
	}

	var intro = document.getElementById('gb-intro');
	if (intro) {
		bindCard(intro);
		syncPics(intro);
	}
	if (list) {
		list.querySelectorAll('.nl-sec').forEach(bindCard);
		list.addEventListener('dragover', function (ev) {
			if (!list._drag) return;
			ev.preventDefault();
			var node = ev.target && ev.target.closest ? ev.target : null;
			var over = node && node.closest ? node.closest('.nl-sec') : null;
			if (!over || over === list._drag) return;
			var box = over.getBoundingClientRect();
			var after = ev.clientY > box.top + box.height / 2;
			list.insertBefore(list._drag, after ? over.nextSibling : over);
		});
	}
	var add = document.getElementById('gb-sec-add');
	if (add && list) add.addEventListener('click', function () {
		var first = list.querySelector('.nl-sec');
		if (!first) return;
		var card = first.cloneNode(true);
		card.classList.remove('is-collapsed');
		card.querySelectorAll('input[type=text], textarea, input[type=hidden]').forEach(function (el) {
			if (el.classList.contains('nl-show-title') || el.classList.contains('nl-own-flag')) return;
			el.value = '';
		});
		var showBoxNew = card.querySelector('.nl-show-title-box');
		var showHidNew = card.querySelector('.nl-show-title');
		if (showBoxNew && showHidNew) showHidNew.value = showBoxNew.checked ? '1' : '0';
		card.querySelectorAll('input[type=file]').forEach(function (el) { el.value = ''; });
		card.querySelectorAll('.nl-pic').forEach(function (n) { n.remove(); });
		ensureSpare(card);
		var id = card.querySelector('[name="sec_id[]"]');
		if (id) id.value = 's' + Date.now();
		var tog = card.querySelector('.nl-sec-toggle');
		if (tog) {
			tog.textContent = 'Sektion';
			tog.setAttribute('aria-expanded', 'true');
		}
		list.appendChild(card);
		bindCard(card);
		paint();
	});
	if (own) {
		own.querySelectorAll('.nl-pic').forEach(function (box) {
			bindPic(box, null);
			var cap = box.querySelector('[name="gde_cap[]"]');
			if (cap) cap.addEventListener('input', paint);
		});
		bindListDrag(own, null);
	}
	var ownAdd = document.getElementById('gb-own-add');
	if (ownAdd && own) ownAdd.addEventListener('click', function () {
		own.appendChild(picSlot(null));
	});
})();
