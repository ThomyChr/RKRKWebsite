(function () {
  const form = document.getElementById("mgr-archive-form");
  if (!form) return;
  const boxes = function () { return Array.from(document.querySelectorAll(".mgr-archive-cb")); };
  const bar = document.getElementById("mgr-archive-bar");
  const countEl = document.getElementById("mgr-archive-count");
  const payloads = document.getElementById("mgr-archive-payloads");
  const sync = function () {
    const selected = boxes().filter(function (cb) { return cb.checked; });
    const n = selected.length;
    if (countEl) countEl.textContent = n === 1 ? "1 ausgewählt" : (n + " ausgewählt");
    if (bar) bar.classList.toggle("is-visible", n > 0);
    boxes().forEach(function (cb) {
      const tile = cb.closest(".mgr-tile");
      if (tile) tile.classList.toggle("is-selected", cb.checked);
    });
  };
  document.addEventListener("change", function (ev) {
    if (ev.target && ev.target.classList && ev.target.classList.contains("mgr-archive-cb")) {
      sync();
    }
  });
  const selectAllBtn = document.getElementById("mgr-archive-select-all");
  if (selectAllBtn) {
    selectAllBtn.addEventListener("click", function () {
      const all = boxes();
      const allOn = all.length > 0 && all.every(function (cb) { return cb.checked; });
      all.forEach(function (cb) { cb.checked = !allOn; });
      sync();
    });
  }
  let lastAction = "archive";
  document.addEventListener("click", function (ev) {
    const btn = ev.target && ev.target.closest ? ev.target.closest("[data-mgr-action]") : null;
    if (btn) lastAction = btn.getAttribute("data-mgr-action") || "archive";
  });
  form.addEventListener("submit", function (ev) {
    const selected = boxes().filter(function (cb) { return cb.checked; });
    if (!selected.length) {
      ev.preventDefault();
      alert("Bitte zuerst eine oder mehrere Kacheln auswählen.");
      return;
    }
    const submitter = ev.submitter || document.activeElement;
    const action = (submitter && submitter.getAttribute && submitter.getAttribute("data-mgr-action")) || lastAction || "archive";
    const isDelete = action === "delete";
    const msg = isDelete
      ? (selected.length === 1
        ? "Diese Kachel endgültig löschen? Dateien werden nicht archiviert."
        : (selected.length + " Kacheln endgültig löschen? Dateien werden nicht archiviert."))
      : (selected.length === 1
        ? "Diese Kachel archivieren? Checklisten/Exams/Reports werden nach results/archive/ verschoben und verschwinden aus der Übersicht. Zugehörige Quiz-/EXAM-Links werden wieder frei."
        : (selected.length + " Kacheln archivieren? Checklisten/Exams/Reports werden nach results/archive/ verschoben und verschwinden aus der Übersicht. Zugehörige Quiz-/EXAM-Links werden wieder frei."));
    if (!confirm(msg)) {
      ev.preventDefault();
      return;
    }
    Array.from(form.querySelectorAll('input[name="exam_admin_archive"], input[name="exam_admin_bulk_delete"], input[name="archive_items[]"], input[name="delete_items[]"]')).forEach(function (el) {
      if (el.parentNode === form || (payloads && payloads.contains(el))) {
        el.parentNode.removeChild(el);
      }
    });
    const flag = document.createElement("input");
    flag.type = "hidden";
    flag.name = isDelete ? "exam_admin_bulk_delete" : "exam_admin_archive";
    flag.value = "1";
    form.appendChild(flag);
    if (payloads) {
      payloads.innerHTML = "";
      selected.forEach(function (cb) {
        const inp = document.createElement("input");
        inp.type = "hidden";
        inp.name = isDelete ? "delete_items[]" : "archive_items[]";
        inp.value = cb.value;
        payloads.appendChild(inp);
      });
    }
  });
  sync();
})();
