<?php
ob_start();
require __DIR__ . '/rkrk_map_edit.php';
$html = ob_get_clean();

$fieldAnalysisScript = <<<'JS'
<script>
(function () {
  function initFieldAnalysisFeature() {
    if (typeof map === "undefined" || !map || typeof ol === "undefined") {
      return false;
    }
    if (document.getElementById("fieldAnalysisDetails")) {
      return true;
    }

    // rkrk_map_edit.php wrappt die UI in #rkrkMapEditorRoot; index.html nutzt direkt <aside class="panel">.
    const asidePanel = document.querySelector("#rkrkMapEditorRoot aside.panel")
      || document.querySelector("aside.panel");
    if (!asidePanel) {
      return false;
    }

    const details = document.createElement("details");
    details.className = "panel-details";
    details.id = "fieldAnalysisDetails";
    details.open = true;
    details.innerHTML = ""
      + "<summary>Feldanalyse</summary>"
      + "<div class='panel-details-content'>"
      + "  <fieldset class='field-analysis-group'>"
      + "    <legend>Anzeige auf der Karte</legend>"
      + "    <button id='toggleHelperParamsBtn' type='button'>Hilfsparameter ausblenden</button>"
      + "    <button id='toggleHelperLayersBtn' type='button'>Hilfselemente auf Karte ausblenden</button>"
      + "    <button id='toggleRectangleFormMapBtn' type='button'>Karte: Rechtecke &amp; Sektor-HRL ausblenden</button>"
      + "  </fieldset>"
      + "  <fieldset class='field-analysis-group'>"
      + "    <legend>Kontrollpunkte &amp; Höhenlinien</legend>"
      + "    <label for='fieldAnalysisSpacing'>Abstand Kontrollpunkte (m)</label>"
      + "    <input id='fieldAnalysisSpacing' type='number' min='5' step='5' value='20' />"
      + "    <button id='toggleFieldAnalysisPointsBtn' type='button'>Kontrollpunkte im Feld setzen</button>"
      + "    <label for='directionBandCount'>Anzahl Höhenrichtungslinien</label>"
      + "    <input id='directionBandCount' type='number' min='1' max='20' step='1' value='5' />"
      + "    <button id='toggleHeightDirectionLinesBtn' type='button'>Höhenrichtungslinien zeigen</button>"
      + "  </fieldset>"
      + "  <fieldset class='field-analysis-group'>"
      + "    <legend>Polygon-Formanalyse (Rechtecke)</legend>"
      + "    <label for='rectangleCount'>Anzahl Rechtecke</label>"
      + "    <input id='rectangleCount' type='number' min='1' max='10' step='1' value='4' />"
      + "    <button id='runPolygonRectangleAnalysisBtn' type='button'>Polygon-Formanalyse zeigen</button>"
      + "    <button id='toggleSectorDirectionGroupsBtn' type='button'>Sektoren nach Höhenrichtung zeigen</button>"
      + "    <button id='drawRectangleInnerLinesBtn' type='button'>Höhenrichtungslinie je Sektor (&gt;20%) zeigen</button>"
      + "  </fieldset>"
      + "  <fieldset class='field-analysis-group'>"
      + "    <legend>Flugpfad-Variante D / F (Karte)</legend>"
      + "    <p class='small' id='fieldAnalysisV4Explain' style='display:none;max-width:100%;line-height:1.38'>"
      + "      <strong>Variante D / F (Unterpolygone):</strong> Sektoren aus Rasterzellen mit ähnlicher Höhenrichtung (max. Abweichung ° im Flugpfad einstellbar). "
      + "      Teilflächen als Schnitt Polygon ∩ Voronoi-Zelle (gelb). Höhenrichtung je Teil; Flug mit parallelen Rand-zu-Rand-Bahnen. "
      + "      <strong>F</strong> streckt die Sehnen bei der Erzeugung stärker in End-Lücken (Feinraster), sonst wie D."
      + "    </p>"
      + "    <button id='toggleSubPolygonV4OverlayBtn' type='button' disabled>D/F · Karte (Variante D oder F wählen)</button>"
      + "  </fieldset>"
      + "  <p class='small' id='fieldAnalysisStatus'>Definiere zuerst ein Polygon.</p>"
      + "  <div id='fieldAnalysisBusy' class='small' style='display:none;align-items:center;gap:8px;'>"
      + "    <span class='field-analysis-spinner' aria-hidden='true'></span>"
      + "    <span>Berechne Höhen...</span>"
      + "  </div>"
      + "</div>";
    asidePanel.appendChild(details);

    function initAdminConfigBar() {
      if (!asidePanel || document.getElementById("adminConfigDetails")) {
        return;
      }

      const adminDetails = document.createElement("details");
      adminDetails.className = "panel-details";
      adminDetails.id = "adminConfigDetails";
      adminDetails.open = false;
      adminDetails.innerHTML = ""
        + "<summary>Admin Konfiguration</summary>"
        + "<div class='panel-details-content'>"
        + "  <p class='small'>Boxen ein-/ausblenden und per Drag&Drop sortieren.</p>"
        + "  <div id='adminConfigList' class='admin-config-list'></div>"
        + "</div>";
      asidePanel.insertBefore(adminDetails, asidePanel.firstChild);

      const listEl = adminDetails.querySelector("#adminConfigList");
      if (!listEl) {
        return;
      }

      const STORAGE_KEY = "rkrk_map_field_admin_sidebar_config_v1";
      let draggedId = "";

      function getSummaryText(detailsEl) {
        const summaryEl = detailsEl.querySelector("summary");
        const text = summaryEl ? String(summaryEl.textContent || "").trim() : "";
        return text || "Unbenannte Box";
      }

      function ensureDetailsId(detailsEl, idx) {
        if (!detailsEl.id) {
          detailsEl.id = `adminConfigTarget_${idx + 1}`;
        }
        return detailsEl.id;
      }

      function getConfigurableBoxes() {
        const all = Array.from(asidePanel.querySelectorAll(":scope > details"));
        return all.filter((el) => el.id !== "adminConfigDetails");
      }

      function saveConfig() {
        const items = Array.from(listEl.querySelectorAll(".admin-config-item"));
        const order = items.map((item) => item.dataset.targetId).filter(Boolean);
        const hidden = {};
        items.forEach((item) => {
          const targetId = item.dataset.targetId;
          const checkbox = item.querySelector("input[type='checkbox']");
          if (targetId && checkbox) {
            hidden[targetId] = !checkbox.checked;
          }
        });
        try {
          localStorage.setItem(STORAGE_KEY, JSON.stringify({ order, hidden }));
        } catch (_) {
          // Ignore storage failures.
        }
      }

      function loadConfig() {
        try {
          const raw = localStorage.getItem(STORAGE_KEY);
          if (!raw) return null;
          const parsed = JSON.parse(raw);
          if (!parsed || typeof parsed !== "object") return null;
          return parsed;
        } catch (_) {
          return null;
        }
      }

      function applyDomOrderFromList() {
        const items = Array.from(listEl.querySelectorAll(".admin-config-item"));
        items.forEach((item) => {
          const targetId = item.dataset.targetId;
          if (!targetId) return;
          const targetEl = document.getElementById(targetId);
          if (!targetEl || targetEl.id === "adminConfigDetails") return;
          asidePanel.appendChild(targetEl);
        });
      }

      function setBoxVisibility(targetId, visible) {
        const targetEl = document.getElementById(targetId);
        if (!targetEl) return;
        targetEl.style.display = visible ? "" : "none";
      }

      function createConfigRow(targetEl) {
        const targetId = targetEl.id;
        const row = document.createElement("div");
        row.className = "admin-config-item";
        row.setAttribute("draggable", "true");
        row.dataset.targetId = targetId;
        row.innerHTML = ""
          + "<span class='admin-config-handle' title='Ziehen zum Sortieren'>↕</span>"
          + "<input type='checkbox' checked />"
          + "<span class='admin-config-title'></span>";

        const titleEl = row.querySelector(".admin-config-title");
        const checkboxEl = row.querySelector("input[type='checkbox']");
        if (titleEl) {
          titleEl.textContent = getSummaryText(targetEl);
        }
        if (checkboxEl) {
          checkboxEl.checked = targetEl.style.display !== "none";
          checkboxEl.addEventListener("change", () => {
            setBoxVisibility(targetId, checkboxEl.checked);
            saveConfig();
          });
        }

        row.addEventListener("dragstart", () => {
          draggedId = targetId;
          row.classList.add("dragging");
        });
        row.addEventListener("dragend", () => {
          row.classList.remove("dragging");
        });
        row.addEventListener("dragover", (evt) => {
          evt.preventDefault();
          row.classList.add("drag-over");
        });
        row.addEventListener("dragleave", () => {
          row.classList.remove("drag-over");
        });
        row.addEventListener("drop", (evt) => {
          evt.preventDefault();
          row.classList.remove("drag-over");
          if (!draggedId || draggedId === targetId) return;
          const draggedRow = listEl.querySelector(`.admin-config-item[data-target-id="${draggedId}"]`);
          if (!draggedRow) return;
          listEl.insertBefore(draggedRow, row);
          applyDomOrderFromList();
          saveConfig();
        });

        return row;
      }

      function buildList() {
        const boxes = getConfigurableBoxes();
        boxes.forEach((box, idx) => {
          ensureDetailsId(box, idx);
        });

        const cfg = loadConfig();
        let ordered = [...boxes];
        if (cfg && Array.isArray(cfg.order) && cfg.order.length) {
          const byId = new Map(ordered.map((el) => [el.id, el]));
          const seen = new Set();
          const fromConfig = [];
          cfg.order.forEach((id) => {
            if (!byId.has(id)) return;
            fromConfig.push(byId.get(id));
            seen.add(id);
          });
          ordered.forEach((el) => {
            if (!seen.has(el.id)) fromConfig.push(el);
          });
          ordered = fromConfig;
        }

        listEl.innerHTML = "";
        ordered.forEach((targetEl) => {
          const row = createConfigRow(targetEl);
          listEl.appendChild(row);
        });

        applyDomOrderFromList();

        if (cfg && cfg.hidden && typeof cfg.hidden === "object") {
          Array.from(listEl.querySelectorAll(".admin-config-item")).forEach((row) => {
            const targetId = row.dataset.targetId;
            const checkboxEl = row.querySelector("input[type='checkbox']");
            if (!targetId || !checkboxEl) return;
            const hidden = !!cfg.hidden[targetId];
            checkboxEl.checked = !hidden;
            setBoxVisibility(targetId, !hidden);
          });
        }

        saveConfig();
      }

      buildList();

    }

    if (!document.getElementById("fieldAnalysisSpinnerStyle")) {
      const styleEl = document.createElement("style");
      styleEl.id = "fieldAnalysisSpinnerStyle";
      styleEl.textContent = ""
        + ".field-analysis-spinner{"
        + "display:inline-block;width:14px;height:14px;border:2px solid #9fb3ff;"
        + "border-top-color:#1f4dff;border-radius:50%;animation:fieldSpin 0.8s linear infinite;"
        + "}"
        + "@keyframes fieldSpin{from{transform:rotate(0deg);}to{transform:rotate(360deg);}}"
        + ".admin-config-list{display:flex;flex-direction:column;gap:6px;}"
        + ".admin-config-item{display:flex;align-items:center;gap:8px;padding:5px 6px;border:1px solid #d2d9d2;border-radius:6px;background:#f7faf7;}"
        + ".admin-config-item.drag-over{border-color:#5e7e4f;background:#eef5ea;}"
        + ".admin-config-item.dragging{opacity:0.6;}"
        + ".admin-config-handle{cursor:grab;color:#4f6f45;font-weight:bold;user-select:none;}"
        + ".field-analysis-group{border:1px solid #dfe8df;border-radius:6px;padding:10px 10px 6px;margin:0 0 12px;background:#fbfcfb;}"
        + ".field-analysis-group legend{padding:0 6px;font-size:12px;font-weight:700;color:#2d4a2a;}"
        + ".field-analysis-group button{margin-top:8px;}"
        + ".field-analysis-group label{margin-top:6px;}"
        + ".field-analysis-group input{margin-top:2px;}";
      document.head.appendChild(styleEl);
    }

    initAdminConfigBar();

    const analysisSource = new ol.source.Vector();
    const analysisLayer = new ol.layer.Vector({ source: analysisSource });
    map.addLayer(analysisLayer);
    const directionSource = new ol.source.Vector();
    const directionLayer = new ol.layer.Vector({ source: directionSource });
    map.addLayer(directionLayer);
    const rectangleSource = new ol.source.Vector();
    const rectangleLayer = new ol.layer.Vector({ source: rectangleSource });
    map.addLayer(rectangleLayer);
    const rectangleInnerLineSource = new ol.source.Vector();
    const rectangleInnerLineLayer = new ol.layer.Vector({ source: rectangleInnerLineSource, zIndex: 8 });
    map.addLayer(rectangleInnerLineLayer);
    const sectorDirectionSource = new ol.source.Vector();
    const sectorDirectionLayer = new ol.layer.Vector({ source: sectorDirectionSource, zIndex: 6 });
    map.addLayer(sectorDirectionLayer);
    const subPolygonV4Source = new ol.source.Vector();
    const subPolygonV4Layer = new ol.layer.Vector({ source: subPolygonV4Source, zIndex: 32 });
    map.addLayer(subPolygonV4Layer);

    const spacingInput = document.getElementById("fieldAnalysisSpacing");
    const toggleHelperParamsBtn = document.getElementById("toggleHelperParamsBtn");
    const toggleHelperLayersBtn = document.getElementById("toggleHelperLayersBtn");
    const togglePointsBtn = document.getElementById("toggleFieldAnalysisPointsBtn");
    const toggleDirectionBtn = document.getElementById("toggleHeightDirectionLinesBtn");
    const runRectangleAnalysisBtn = document.getElementById("runPolygonRectangleAnalysisBtn");
    const drawRectangleInnerLinesBtn = document.getElementById("drawRectangleInnerLinesBtn");
    const toggleRectangleFormMapBtn = document.getElementById("toggleRectangleFormMapBtn");
    const toggleSectorDirectionGroupsBtn = document.getElementById("toggleSectorDirectionGroupsBtn");
    const toggleSubPolygonV4OverlayBtn = document.getElementById("toggleSubPolygonV4OverlayBtn");
    const fieldAnalysisV4ExplainEl = document.getElementById("fieldAnalysisV4Explain");
    const directionBandCountInput = document.getElementById("directionBandCount");
    const rectangleCountInput = document.getElementById("rectangleCount");
    const lineExtensionSliderInput = document.getElementById("lineExtensionSlider");
    const fpLineExtensionEl = document.getElementById("fpLineExtension");
    const fpLineExtensionValueEl = document.getElementById("fpLineExtensionValue");
    const statusEl = document.getElementById("fieldAnalysisStatus");
    const busyEl = document.getElementById("fieldAnalysisBusy");
    const spacingLabelEl = document.querySelector("label[for='fieldAnalysisSpacing']");
    const directionBandCountLabelEl = document.querySelector("label[for='directionBandCount']");
    const rectangleCountLabelEl = document.querySelector("label[for='rectangleCount']");
    const helperParamElements = [
      spacingLabelEl,
      spacingInput,
      directionBandCountLabelEl,
      directionBandCountInput,
      rectangleCountLabelEl,
      rectangleCountInput
    ].filter(Boolean);
    let lastPointData = [];
    let controlPointsEnabled = false;
    let directionLinesEnabled = false;
    let lastRectangles = [];
    let rectangleAnalysisEnabled = false;
    let rectangleInnerLinesEnabled = false;
    let sectorDirectionGroupsEnabled = false;
    let lastSectorDirectionGroups = [];
    /** V4: Umriss + Höhenrichtung auf eigener Karten-Ebene ein-/ausblenden */
    let subPolygonV4OverlayEnabled = true;
    /** Formanalyse: violette Rechtecke + Sektor-Höhenrichtungslinien (rectangleInnerLineLayer) separat von V4 */
    let rectangleFormMapVisible = true;
    /** Rechtecke unter diesem Anteil der grössten Fläche werden als Flug-Sektor verworfen */
    const SECTOR_MIN_AREA_RATIO = 0.2;
    /** Standard max. Höhenrichtungsabweichung (°) pro Sektor, überschreibbar per UI #fpSectorDirectionMaxDiff */
    const SECTOR_DIRECTION_MAX_DIFF_DEG_DEFAULT = 20;
    /** Varianten-Schlüssel (wiederverwendet) für höhenliniengeführten Abdeckungsmodus */
    const HEIGHT_GUIDED_VARIANT_KEY = "rectangleDirection";
    /** B: Sektor-Bahnen, volle Abdeckung */
    const RECTANGLE_HEIGHT_GUIDED_VARIANT2_KEY = "rectangleHeightDirection2";
    /** C: kontinuierliche Parallelbahnen über das gesamte Polygon */
    const RECTANGLE_HEIGHT_GUIDED_VARIANT3_KEY = "rectangleHeightDirection3";
    /** D: Unterpolygone (Sektor-Höhenanalyse, gelb), optional Direktverbindungen */
    const RECTANGLE_HEIGHT_GUIDED_VARIANT4_KEY = "rectangleHeightDirection4";
    /** F: wie D, zusätzlich aggressive Erst-Verlängerung der Bahnpolylinie entlang der Sehne (ohne neue Auffüll-Pfade) */
    const RECTANGLE_HEIGHT_GUIDED_VARIANT5_KEY = "rectangleHeightDirection5";
    /** E: Adaptiv — Polygonanalyse: schmal → Streifen längs Form; homogen → Höhenrichtung; komplex → wie D */
    const RECTANGLE_HEIGHT_GUIDED_ADAPTIVE_KEY = "rectangleHeightDirectionAdaptive";
    /** Mindest-Winkelabstand zweier Sektoren/Rechtecke, ab dem „komplex“ (wie D) gewählt wird */
    const ADAPTIVE_COMPLEX_MIN_ANGLE_DEG = 28;
    const V4_SUBPOLY_STROKE = "#c9a000";
    const V4_SUBPOLY_FILL = "rgba(255, 230, 70, 0.32)";
    const V4_DIRECT_CONNECT_MAX_ANGLE_DEG = 12;
    /** Bis zu dieser Höhendifferenz wird kein zusätzlicher Stützpunkt eingefügt */
    const PROFILE_SUPPORT_HEIGHT_THRESHOLD_M = 10;
    let helperParamsVisible = true;
    let helperLayersVisible = true;
    let lineExtensionRatio = 0.35;
    const FIELD_SETTINGS_KEY = "rkrk_map_field_analysis_settings_v1";

    function configureFlightVariantUiLabels() {
      if (typeof fpCalcVariantEl === "undefined" || !fpCalcVariantEl) {
        return;
      }
      const stdEl = fpCalcVariantEl.querySelector("option[value=\"standard\"]");
      if (stdEl) {
        stdEl.textContent = "Standard · Parallelstreifen";
      }
      const optionEl = fpCalcVariantEl.querySelector(`option[value="${HEIGHT_GUIDED_VARIANT_KEY}"]`);
      if (optionEl) {
        optionEl.textContent = "A · Höhenliniengeführt (Startnah)";
      }
      let rectOption2El = fpCalcVariantEl.querySelector(`option[value="${RECTANGLE_HEIGHT_GUIDED_VARIANT2_KEY}"]`);
      if (!rectOption2El) {
        rectOption2El = document.createElement("option");
        rectOption2El.value = RECTANGLE_HEIGHT_GUIDED_VARIANT2_KEY;
        fpCalcVariantEl.appendChild(rectOption2El);
      }
      rectOption2El.textContent = "B · Sektor-Bahnen, volle Abdeckung";
      let rectOption3El = fpCalcVariantEl.querySelector(`option[value="${RECTANGLE_HEIGHT_GUIDED_VARIANT3_KEY}"]`);
      if (!rectOption3El) {
        rectOption3El = document.createElement("option");
        rectOption3El.value = RECTANGLE_HEIGHT_GUIDED_VARIANT3_KEY;
        fpCalcVariantEl.appendChild(rectOption3El);
      }
      rectOption3El.textContent = "C · Kontinuierliche Parallelbahnen";
      let rectOption4El = fpCalcVariantEl.querySelector(`option[value="${RECTANGLE_HEIGHT_GUIDED_VARIANT4_KEY}"]`);
      if (!rectOption4El) {
        rectOption4El = document.createElement("option");
        rectOption4El.value = RECTANGLE_HEIGHT_GUIDED_VARIANT4_KEY;
        fpCalcVariantEl.appendChild(rectOption4El);
      }
      rectOption4El.textContent = "D · Unterpolygone (Feldaufteilung)";
      let rectOption5El = fpCalcVariantEl.querySelector(`option[value="${RECTANGLE_HEIGHT_GUIDED_VARIANT5_KEY}"]`);
      if (!rectOption5El) {
        rectOption5El = document.createElement("option");
        rectOption5El.value = RECTANGLE_HEIGHT_GUIDED_VARIANT5_KEY;
        fpCalcVariantEl.appendChild(rectOption5El);
      }
      rectOption5El.textContent = "F · Unterpolygone (erweiterte Sehnen)";
      let rectOption6El = fpCalcVariantEl.querySelector(`option[value="${RECTANGLE_HEIGHT_GUIDED_ADAPTIVE_KEY}"]`);
      if (!rectOption6El) {
        rectOption6El = document.createElement("option");
        rectOption6El.value = RECTANGLE_HEIGHT_GUIDED_ADAPTIVE_KEY;
        fpCalcVariantEl.appendChild(rectOption6El);
      }
      rectOption6El.textContent = "E · Adaptiv (Polygonanalyse)";
    }

    function setToggleButtonState(buttonEl, isOn, labelOff, labelOn) {
      if (!buttonEl) {
        return;
      }
      buttonEl.textContent = isOn ? labelOn : labelOff;
      buttonEl.classList.toggle("selected-flight-reference", !!isOn);
    }

    function applyHelperParamsVisibility() {
      helperParamElements.forEach((el) => {
        el.style.display = helperParamsVisible ? "" : "none";
      });
      if (toggleHelperParamsBtn) {
        toggleHelperParamsBtn.textContent = helperParamsVisible
          ? "Hilfsparameter ausblenden"
          : "Hilfsparameter einblenden";
      }
    }

    function applyHelperLayersVisibility() {
      analysisLayer.setVisible(helperLayersVisible);
      directionLayer.setVisible(helperLayersVisible);
      rectangleLayer.setVisible(helperLayersVisible && rectangleFormMapVisible);
      rectangleInnerLineLayer.setVisible(
        helperLayersVisible && rectangleFormMapVisible && rectangleInnerLinesEnabled
      );
      sectorDirectionLayer.setVisible(helperLayersVisible);
      applySubPolygonV4LayerVisibility();
      if (toggleHelperLayersBtn) {
        toggleHelperLayersBtn.textContent = helperLayersVisible
          ? "Hilfselemente auf Karte ausblenden"
          : "Hilfselemente auf Karte einblenden";
      }
    }

    function updateLineExtensionUi() {
      if (lineExtensionSliderInput) {
        lineExtensionSliderInput.value = String(Math.round(lineExtensionRatio * 100));
      }
      if (fpLineExtensionEl) {
        fpLineExtensionEl.value = String(Math.round(lineExtensionRatio * 100));
      }
      if (fpLineExtensionValueEl) {
        fpLineExtensionValueEl.textContent = `${Math.round(lineExtensionRatio * 100)}%`;
      }
    }

    function getLineExtensionInfluenceRatio() {
      const uiPercent = fpLineExtensionEl
        ? sanitizeNumber(fpLineExtensionEl.value, lineExtensionRatio * 100)
        : (lineExtensionSliderInput ? sanitizeNumber(lineExtensionSliderInput.value, lineExtensionRatio * 100) : (lineExtensionRatio * 100));
      const pct = Math.max(0, Math.min(100, uiPercent));
      // Feinfühligere Steuerung bei kleinen Werten, volle Wirkung erst im oberen Bereich.
      // 0% -> 0.0, 50% -> ~0.45, 100% -> 1.68
      // (reduziert unnötige Überschwinger und Kreuzungen zwischen Zonen)
      const n = pct / 100;
      return Math.max(0, Math.min(1.68, (0.08 * n) + (1.6 * n * n)));
    }

    function principalDirectionAngleFromMapPoints(points, fallbackDeg) {
      const pts = Array.isArray(points) ? points : [];
      if (pts.length < 2) {
        return normalizeAngle180(Number.isFinite(fallbackDeg) ? fallbackDeg : 0);
      }
      let cx = 0;
      let cy = 0;
      pts.forEach((p) => {
        cx += Number(p[0]);
        cy += Number(p[1]);
      });
      cx /= pts.length;
      cy /= pts.length;
      let cxx = 0;
      let cyy = 0;
      let cxy = 0;
      pts.forEach((p) => {
        const dx = Number(p[0]) - cx;
        const dy = Number(p[1]) - cy;
        cxx += dx * dx;
        cyy += dy * dy;
        cxy += dx * dy;
      });
      cxx /= pts.length;
      cyy /= pts.length;
      cxy /= pts.length;
      const trace = cxx + cyy;
      const disc = Math.sqrt(Math.max(0, (trace * trace) / 4 - (cxx * cyy - cxy * cxy)));
      const lam1 = trace / 2 + disc;
      let vx;
      let vy;
      if (Math.abs(cxy) < 1e-12) {
        if (cxx >= cyy) {
          vx = 1;
          vy = 0;
        } else {
          vx = 0;
          vy = 1;
        }
      } else {
        vx = lam1 - cyy;
        vy = cxy;
        const len = Math.hypot(vx, vy);
        if (len < 1e-12) {
          vx = 1;
          vy = 0;
        } else {
          vx /= len;
          vy /= len;
        }
      }
      return normalizeAngle180((Math.atan2(vy, vx) * 180) / Math.PI);
    }

    function buildV4GroupsFromRectangles(cells) {
      const srcCells = Array.isArray(cells) ? cells : [];
      if (!srcCells.length) {
        return [];
      }
      const rects = Array.isArray(lastRectangles) ? lastRectangles : [];
      if (!rects.length) {
        return [{
          id: 1,
          meanAngle: principalDirectionAngleFromMapPoints(srcCells.map((c) => c.center), 0),
          cells: srcCells
        }];
      }
      const groups = rects.map((rect, idx) => ({
        id: idx + 1,
        meanAngle: rectangleAngleDeg(rect),
        cells: []
      }));
      srcCells.forEach((cell) => {
        let bestIdx = 0;
        let bestDist = Number.POSITIVE_INFINITY;
        for (let i = 0; i < rects.length; i += 1) {
          const d = mapDistance(cell.center, rects[i].center);
          if (d < bestDist) {
            bestDist = d;
            bestIdx = i;
          }
        }
        groups[bestIdx].cells.push(cell);
      });
      return groups
        .filter((g) => Array.isArray(g.cells) && g.cells.length)
        .map((g) => ({
          id: g.id,
          meanAngle: principalDirectionAngleFromMapPoints(g.cells.map((c) => c.center), g.meanAngle),
          cells: g.cells
        }));
    }

    /**
     * D: Rechteck-Zellen mit ähnlicher Bahnnormalenrichtung zu einem Sektor zusammenfassen
     * (z. B. ganzer linker Schenkel statt mehrerer Mini-Sektoren → weniger Kreuzungen, klarer Bahnabstand).
     */
    function mergeV4GroupsBySimilarDirection(groups, maxAngleDeg) {
      const src = Array.isArray(groups) ? groups.slice() : [];
      const maxDeg = Math.max(8, Math.min(40, Number(maxAngleDeg) || 18));
      let changed = true;
      while (changed && src.length > 1) {
        changed = false;
        outer: for (let i = 0; i < src.length; i += 1) {
          for (let j = i + 1; j < src.length; j += 1) {
            const d = angleDifference180(Number(src[i].meanAngle), Number(src[j].meanAngle));
            if (d <= maxDeg) {
              const mergedCells = (Array.isArray(src[i].cells) ? src[i].cells : []).concat(
                Array.isArray(src[j].cells) ? src[j].cells : []
              );
              const ni = Math.max(1, (src[i].cells && src[i].cells.length) || 0);
              const nj = Math.max(1, (src[j].cells && src[j].cells.length) || 0);
              const wAngle = weightedAxialMean180([
                { angleDeg: Number(src[i].meanAngle), weight: ni },
                { angleDeg: Number(src[j].meanAngle), weight: nj }
              ]);
              const newMean = principalDirectionAngleFromMapPoints(
                mergedCells.map((c) => c.center),
                wAngle
              );
              src[i] = {
                id: src[i].id,
                meanAngle: newMean,
                cells: mergedCells
              };
              src.splice(j, 1);
              changed = true;
              break outer;
            }
          }
        }
      }
      return src;
    }

    function readFieldSettings() {
      try {
        const raw = localStorage.getItem(FIELD_SETTINGS_KEY);
        if (!raw) return null;
        const parsed = JSON.parse(raw);
        if (!parsed || typeof parsed !== "object") return null;
        return parsed;
      } catch (_) {
        return null;
      }
    }

    function saveFieldSettings() {
      const lineExtensionPercent = (typeof fpLineExtensionEl !== "undefined" && fpLineExtensionEl)
        ? Math.max(0, Math.min(100, Math.round(Number(fpLineExtensionEl.value) || 0)))
        : Math.round(lineExtensionRatio * 100);
      const parallelTolerancePct = (typeof fpParallelToleranceEl !== "undefined" && fpParallelToleranceEl)
        ? Math.max(0, Math.min(50, Math.round(Number(fpParallelToleranceEl.value) || 20)))
        : 20;
      const sectorDirectionMaxDiffDeg = readSectorDirectionMaxDiffDegFromUi();
      const payload = {
        spacing: Number(spacingInput ? spacingInput.value : 20),
        directionBandCount: Number(directionBandCountInput ? directionBandCountInput.value : 5),
        rectangleCount: Number(rectangleCountInput ? rectangleCountInput.value : 4),
        lineExtensionPercent,
        parallelTolerancePct,
        sectorDirectionMaxDiffDeg,
        directionLinesEnabled: !!directionLinesEnabled,
        helperParamsVisible: !!helperParamsVisible,
        helperLayersVisible: !!helperLayersVisible,
        subPolygonV4OverlayEnabled: !!subPolygonV4OverlayEnabled,
        rectangleFormMapVisible: !!rectangleFormMapVisible
      };
      try {
        localStorage.setItem(FIELD_SETTINGS_KEY, JSON.stringify(payload));
      } catch (_) {
        // Ignore storage failures.
      }
    }

    function applySavedFieldSettings() {
      const saved = readFieldSettings();
      if (!saved) {
        applyHelperParamsVisibility();
        refreshRectangleFormMapToggleButtonState();
        return;
      }
      if (spacingInput && Number.isFinite(Number(saved.spacing))) {
        spacingInput.value = String(Number(saved.spacing));
        spacingInput.dataset.userEdited = "1";
      }
      if (directionBandCountInput && Number.isFinite(Number(saved.directionBandCount))) {
        directionBandCountInput.value = String(Number(saved.directionBandCount));
      }
      if (rectangleCountInput && Number.isFinite(Number(saved.rectangleCount))) {
        rectangleCountInput.value = String(Number(saved.rectangleCount));
      }
      if (Number.isFinite(Number(saved.lineExtensionPercent))) {
        const pct = Math.max(0, Math.min(100, Number(saved.lineExtensionPercent)));
        lineExtensionRatio = pct / 100;
      }
      if (Number.isFinite(Number(saved.parallelTolerancePct))
        && typeof fpParallelToleranceEl !== "undefined"
        && fpParallelToleranceEl) {
        const tol = Math.max(0, Math.min(50, Math.round(Number(saved.parallelTolerancePct))));
        fpParallelToleranceEl.value = String(tol);
        const parallelTolValueEl = document.getElementById("fpParallelToleranceValue");
        if (parallelTolValueEl) {
          parallelTolValueEl.textContent = `${tol}%`;
        }
      }
      if (Number.isFinite(Number(saved.sectorDirectionMaxDiffDeg))) {
        const sectorDiffEl = document.getElementById("fpSectorDirectionMaxDiff");
        if (sectorDiffEl) {
          const d = Math.max(5, Math.min(60, Math.round(Number(saved.sectorDirectionMaxDiffDeg))));
          sectorDiffEl.value = String(d);
          const sectorDiffValueEl = document.getElementById("fpSectorDirectionMaxDiffValue");
          if (sectorDiffValueEl) {
            sectorDiffValueEl.textContent = `${d}°`;
          }
        }
      }
      directionLinesEnabled = !!saved.directionLinesEnabled;
      if (typeof saved.helperParamsVisible === "boolean") {
        helperParamsVisible = saved.helperParamsVisible;
      }
      if (typeof saved.helperLayersVisible === "boolean") {
        helperLayersVisible = saved.helperLayersVisible;
      }
      if (typeof saved.subPolygonV4OverlayEnabled === "boolean") {
        subPolygonV4OverlayEnabled = saved.subPolygonV4OverlayEnabled;
      }
      if (typeof saved.rectangleFormMapVisible === "boolean") {
        rectangleFormMapVisible = saved.rectangleFormMapVisible;
      }
      updateLineExtensionUi();
      applyHelperParamsVisibility();
      applyHelperLayersVisibility();
      refreshSubPolygonV4ToggleButtonState();
      refreshRectangleFormMapToggleButtonState();
    }

    function isFlightVariantV4Selected() {
      return typeof fpCalcVariantEl !== "undefined" && fpCalcVariantEl
        && (fpCalcVariantEl.value === RECTANGLE_HEIGHT_GUIDED_VARIANT4_KEY
          || fpCalcVariantEl.value === RECTANGLE_HEIGHT_GUIDED_VARIANT5_KEY
          || fpCalcVariantEl.value === RECTANGLE_HEIGHT_GUIDED_ADAPTIVE_KEY);
    }

    function updateFieldAnalysisV4ExplainVisibility() {
      if (fieldAnalysisV4ExplainEl) {
        fieldAnalysisV4ExplainEl.style.display = isFlightVariantV4Selected() ? "block" : "none";
      }
    }

    function applySubPolygonV4LayerVisibility() {
      const n = (subPolygonV4Source && typeof subPolygonV4Source.getFeatures === "function")
        ? subPolygonV4Source.getFeatures().length
        : 0;
      const show = !!helperLayersVisible && !!subPolygonV4OverlayEnabled && n > 0 && isFlightVariantV4Selected();
      if (subPolygonV4Layer) {
        subPolygonV4Layer.setVisible(show);
      }
    }

    function refreshRectangleFormMapToggleButtonState() {
      if (!toggleRectangleFormMapBtn) {
        return;
      }
      const busy = busyEl && busyEl.style.display === "flex";
      toggleRectangleFormMapBtn.disabled = !!busy;
      const on = !!rectangleFormMapVisible;
      setToggleButtonState(
        toggleRectangleFormMapBtn,
        on,
        "Karte: Rechtecke & Sektor-HRL einblenden",
        "Karte: Rechtecke & Sektor-HRL ausblenden"
      );
    }

    function refreshSubPolygonV4ToggleButtonState() {
      if (!toggleSubPolygonV4OverlayBtn) {
        return;
      }
      const busy = busyEl && busyEl.style.display === "flex";
      if (!isFlightVariantV4Selected()) {
        toggleSubPolygonV4OverlayBtn.disabled = true;
        toggleSubPolygonV4OverlayBtn.classList.remove("selected-flight-reference");
        toggleSubPolygonV4OverlayBtn.textContent = "D/F · Karte (Variante D oder F wählen)";
        return;
      }
      const has = subPolygonV4Source && subPolygonV4Source.getFeatures().length > 0;
      toggleSubPolygonV4OverlayBtn.disabled = !!busy || !has;
      if (!has) {
        toggleSubPolygonV4OverlayBtn.classList.remove("selected-flight-reference");
        toggleSubPolygonV4OverlayBtn.textContent = "D/F · keine Darstellung (Flugpfad berechnen)";
        return;
      }
      const on = !!subPolygonV4OverlayEnabled;
      setToggleButtonState(
        toggleSubPolygonV4OverlayBtn,
        on,
        "D/F · Unterpolygone & Höhenrichtung einblenden",
        "D/F · Unterpolygone & Höhenrichtung ausblenden"
      );
    }

    function setBusy(isBusy) {
      if (busyEl) {
        busyEl.style.display = isBusy ? "flex" : "none";
      }
      if (togglePointsBtn) {
        togglePointsBtn.disabled = !!isBusy;
      }
      if (runRectangleAnalysisBtn) {
        runRectangleAnalysisBtn.disabled = !!isBusy;
      }
      if (drawRectangleInnerLinesBtn) {
        drawRectangleInnerLinesBtn.disabled = !!isBusy;
      }
      if (toggleSectorDirectionGroupsBtn) {
        toggleSectorDirectionGroupsBtn.disabled = !!isBusy;
      }
      if (toggleRectangleFormMapBtn) {
        toggleRectangleFormMapBtn.disabled = !!isBusy;
      }
      refreshSubPolygonV4ToggleButtonState();
      refreshRectangleFormMapToggleButtonState();
    }

    function getCountValue(inputEl, fallback, minValue, maxValue) {
      const raw = Number(inputEl ? inputEl.value : fallback);
      if (!Number.isFinite(raw)) {
        return fallback;
      }
      const rounded = Math.round(raw);
      return Math.max(minValue, Math.min(maxValue, rounded));
    }

    function normalizeAngle180(deg) {
      let a = Number(deg);
      if (!Number.isFinite(a)) {
        return 0;
      }
      a %= 180;
      if (a < 0) a += 180;
      return a;
    }

    function angleDifference180(a, b) {
      const aa = normalizeAngle180(a);
      const bb = normalizeAngle180(b);
      const d = Math.abs(aa - bb);
      return Math.min(d, 180 - d);
    }

    function rectangleAngleDeg(rect) {
      return normalizeAngle180((Number(rect.angleRad) * 180) / Math.PI);
    }

    function rectangleDirectionScore(angleDeg) {
      if (!Array.isArray(lastRectangles) || !lastRectangles.length) {
        return 0;
      }
      let weighted = 0;
      let weightSum = 0;
      lastRectangles.forEach((rect) => {
        const w = Math.max(1, rectangleArea(rect));
        const diff = angleDifference180(angleDeg, rectangleAngleDeg(rect));
        const score = 1 - (diff / 90);
        weighted += (score * w);
        weightSum += w;
      });
      return weightSum > 0 ? (weighted / weightSum) : 0;
    }

    function candidateAnglesFromRectangles() {
      if (!Array.isArray(lastRectangles) || !lastRectangles.length) {
        return [];
      }
      const rawAngles = lastRectangles.map((rect) => rectangleAngleDeg(rect));
      // Weighted mean direction on axial data (0..180) using doubled-angle trick.
      let sx = 0;
      let sy = 0;
      lastRectangles.forEach((rect) => {
        const w = Math.max(1, rectangleArea(rect));
        const a2 = (2 * rectangleAngleDeg(rect) * Math.PI) / 180;
        sx += Math.cos(a2) * w;
        sy += Math.sin(a2) * w;
      });
      if (Math.abs(sx) > 1e-9 || Math.abs(sy) > 1e-9) {
        const mean = normalizeAngle180((Math.atan2(sy, sx) * 180 / Math.PI) / 2);
        rawAngles.push(mean);
      }
      const unique = [];
      const seen = new Set();
      rawAngles.forEach((a) => {
        const n = normalizeAngle180(a);
        const key = String(Math.round(n));
        if (!seen.has(key)) {
          seen.add(key);
          unique.push(n);
        }
      });
      return unique;
    }

    function candidateAnglesFromHeightLines() {
      if (!directionSource || typeof directionSource.getFeatures !== "function") {
        return [];
      }
      const features = directionSource.getFeatures();
      const angles = [];
      features.forEach((feature) => {
        const geom = feature && typeof feature.getGeometry === "function" ? feature.getGeometry() : null;
        if (!geom || typeof geom.getType !== "function" || geom.getType() !== "LineString") {
          return;
        }
        const coords = typeof geom.getCoordinates === "function" ? geom.getCoordinates() : [];
        if (!Array.isArray(coords) || coords.length < 2) {
          return;
        }
        const a = coords[0];
        const b = coords[coords.length - 1];
        const dx = Number(b[0]) - Number(a[0]);
        const dy = Number(b[1]) - Number(a[1]);
        if (!Number.isFinite(dx) || !Number.isFinite(dy) || (Math.abs(dx) + Math.abs(dy) < 1e-6)) {
          return;
        }
        const angle = normalizeAngle180((Math.atan2(dy, dx) * 180) / Math.PI);
        angles.push(angle);
      });
      const seen = new Set();
      const unique = [];
      angles.forEach((angle) => {
        const key = String(Math.round(angle));
        if (!seen.has(key)) {
          seen.add(key);
          unique.push(angle);
        }
      });
      return unique;
    }

    function heightLineDirectionScore(angleDeg, referenceAngles) {
      const refs = Array.isArray(referenceAngles) ? referenceAngles : [];
      if (!refs.length) {
        return 0;
      }
      let best = 0;
      refs.forEach((ref) => {
        const diff = angleDifference180(angleDeg, ref);
        const score = Math.max(0, 1 - (diff / 90));
        if (score > best) {
          best = score;
        }
      });
      return best;
    }

    function pathLengthMetersLonLat(points) {
      if (!Array.isArray(points) || points.length < 2 || typeof lonLatDistanceApproxMeters !== "function") {
        return 0;
      }
      let length = 0;
      for (let i = 1; i < points.length; i += 1) {
        length += lonLatDistanceApproxMeters(points[i - 1], points[i]);
      }
      return length;
    }

    function getPolygonDiameterMeters() {
      if (!Array.isArray(polygonVertices) || polygonVertices.length < 2) {
        return NaN;
      }
      const coords = polygonVertices.map((v) => lonLatToMapCoord(v[0], v[1]));
      let maxDist = 0;
      for (let i = 0; i < coords.length; i += 1) {
        for (let j = i + 1; j < coords.length; j += 1) {
          const dx = Number(coords[i][0]) - Number(coords[j][0]);
          const dy = Number(coords[i][1]) - Number(coords[j][1]);
          const dist = Math.sqrt((dx * dx) + (dy * dy));
          if (Number.isFinite(dist) && dist > maxDist) {
            maxDist = dist;
          }
        }
      }
      return maxDist;
    }

    function suggestedSpacingFromPolygon() {
      const diameter = getPolygonDiameterMeters();
      if (!Number.isFinite(diameter) || diameter <= 0) {
        return 20;
      }
      const tenth = diameter / 10;
      // Keep step size aligned with input step=5.
      return Math.max(5, Math.round(tenth / 5) * 5);
    }

    function applyAutoSpacingDefault() {
      if (!spacingInput) {
        return;
      }
      const value = suggestedSpacingFromPolygon();
      spacingInput.value = String(value);
      spacingInput.dataset.autoValue = String(value);
    }

    function pointLabel(heightMetersAsl) {
      if (!Number.isFinite(heightMetersAsl)) {
        return "C(--)";
      }
      return `C(${heightMetersAsl.toFixed(1)})`;
    }

    function colorForHeight(height, minHeight, maxHeight) {
      // Blue (low) -> Yellow (high)
      if (!Number.isFinite(height) || !Number.isFinite(minHeight) || !Number.isFinite(maxHeight)) {
        return "#ffd400";
      }
      if (Math.abs(maxHeight - minHeight) < 1e-9) {
        return "#ffd400";
      }
      const tRaw = (height - minHeight) / (maxHeight - minHeight);
      const t = Math.max(0, Math.min(1, tRaw));
      const r = Math.round(255 * t);
      const g = Math.round(255 * t);
      const b = Math.round(255 * (1 - t));
      return `rgb(${r},${g},${b})`;
    }

    function renderPoints(pointData) {
      analysisSource.clear();
      const heights = pointData
        .map((item) => Number(item.asl))
        .filter((h) => Number.isFinite(h));
      const minHeight = heights.length ? Math.min(...heights) : NaN;
      const maxHeight = heights.length ? Math.max(...heights) : NaN;

      pointData.forEach((item) => {
        const mapCoord = lonLatToMapCoord(item.lon, item.lat);
        const fillColor = colorForHeight(item.asl, minHeight, maxHeight);
        const feature = new ol.Feature({
          geometry: new ol.geom.Point(mapCoord)
        });
        feature.setStyle(new ol.style.Style({
          image: new ol.style.Circle({
            radius: 11,
            fill: new ol.style.Fill({ color: fillColor }),
            stroke: new ol.style.Stroke({ color: "#333333", width: 1.5 })
          }),
          text: new ol.style.Text({
            text: pointLabel(item.asl),
            offsetY: -14,
            font: "12px Arial",
            fill: new ol.style.Fill({ color: "#111111" }),
            stroke: new ol.style.Stroke({ color: "#ffffff", width: 3 })
          })
        }));
        analysisSource.addFeature(feature);
      });
    }

    function clearDirectionLines() {
      directionSource.clear();
    }

    function renderHeightDirectionLines(pointData) {
      clearDirectionLines();
      const usable = pointData.filter((item) => Number.isFinite(item.asl));
      if (!usable.length) {
        return;
      }
      const heights = usable.map((item) => Number(item.asl));
      const minHeight = Math.min(...heights);
      const maxHeight = Math.max(...heights);
      const bandCount = getCountValue(directionBandCountInput, 5, 1, 20);
      const range = Math.max(0.0001, maxHeight - minHeight);
      const step = range / bandCount;

      for (let band = 0; band < bandCount; band += 1) {
        const low = minHeight + (band * step);
        const high = (band === bandCount - 1) ? (maxHeight + 1e-9) : (minHeight + ((band + 1) * step));
        const group = usable.filter((item) => item.asl >= low && item.asl < high);
        if (!group.length) {
          continue;
        }

        const samples = group.map((item) => ({
          mapCoord: lonLatToMapCoord(item.lon, item.lat),
          asl: Number(item.asl)
        }));
        const axis = fitHeightGradientAxis(samples);
        if (!axis) {
          continue;
        }
        const p1 = [axis.cx - (axis.vx * axis.halfLen), axis.cy - (axis.vy * axis.halfLen)];
        const p2 = [axis.cx + (axis.vx * axis.halfLen), axis.cy + (axis.vy * axis.halfLen)];
        const meanHeight = group.reduce((sum, item) => sum + Number(item.asl), 0) / group.length;
        const lineColor = colorForHeight(meanHeight, minHeight, maxHeight);

        const lineFeature = new ol.Feature({
          geometry: new ol.geom.LineString([p1, p2])
        });
        lineFeature.setStyle(new ol.style.Style({
          stroke: new ol.style.Stroke({
            color: lineColor,
            width: 3
          }),
          text: new ol.style.Text({
            text: `D${band + 1} C(${meanHeight.toFixed(1)})`,
            offsetY: -10,
            font: "12px Arial",
            fill: new ol.style.Fill({ color: "#111111" }),
            stroke: new ol.style.Stroke({ color: "#ffffff", width: 3 })
          })
        }));
        directionSource.addFeature(lineFeature);
      }
    }

    function refreshDirectionLines() {
      if (!directionLinesEnabled) {
        clearDirectionLines();
        setToggleButtonState(toggleDirectionBtn, false, "Höhenrichtungslinien zeigen", "Höhenrichtungslinien verbergen");
        saveFieldSettings();
        return;
      }
      renderHeightDirectionLines(lastPointData);
      setToggleButtonState(toggleDirectionBtn, true, "Höhenrichtungslinien zeigen", "Höhenrichtungslinien verbergen");
      saveFieldSettings();
    }

    function polygonGeometryFromVertices() {
      if (!Array.isArray(polygonVertices) || polygonVertices.length < 3) {
        return null;
      }
      const ringCoords = polygonVertices.map((v) => lonLatToMapCoord(v[0], v[1]));
      return new ol.geom.Polygon([[...ringCoords, ringCoords[0]]]);
    }

    function makeRotatedRectangle(center, halfWidth, halfHeight, angleRad) {
      const cx = Number(center[0]);
      const cy = Number(center[1]);
      const c = Math.cos(angleRad);
      const s = Math.sin(angleRad);
      const ux = [c, s];
      const uy = [-s, c];
      const p1 = [cx - (ux[0] * halfWidth) - (uy[0] * halfHeight), cy - (ux[1] * halfWidth) - (uy[1] * halfHeight)];
      const p2 = [cx + (ux[0] * halfWidth) - (uy[0] * halfHeight), cy + (ux[1] * halfWidth) - (uy[1] * halfHeight)];
      const p3 = [cx + (ux[0] * halfWidth) + (uy[0] * halfHeight), cy + (ux[1] * halfWidth) + (uy[1] * halfHeight)];
      const p4 = [cx - (ux[0] * halfWidth) + (uy[0] * halfHeight), cy - (ux[1] * halfWidth) + (uy[1] * halfHeight)];
      return {
        center: [cx, cy],
        halfWidth,
        halfHeight,
        angleRad,
        corners: [p1, p2, p3, p4]
      };
    }

    function pointInRectangle(point, rect) {
      const cx = rect.center[0];
      const cy = rect.center[1];
      const dx = Number(point[0]) - cx;
      const dy = Number(point[1]) - cy;
      const c = Math.cos(rect.angleRad);
      const s = Math.sin(rect.angleRad);
      const localX = (dx * c) + (dy * s);
      const localY = (-dx * s) + (dy * c);
      return Math.abs(localX) <= (rect.halfWidth + 1e-6) && Math.abs(localY) <= (rect.halfHeight + 1e-6);
    }

    function projectPolygon(points, axis) {
      let min = Infinity;
      let max = -Infinity;
      points.forEach((p) => {
        const t = (Number(p[0]) * axis[0]) + (Number(p[1]) * axis[1]);
        min = Math.min(min, t);
        max = Math.max(max, t);
      });
      return { min, max };
    }

    function rangesOverlap(a, b) {
      return a.max >= b.min && b.max >= a.min;
    }

    function rectanglesIntersect(rectA, rectB) {
      const axes = [];
      [rectA, rectB].forEach((rect) => {
        const c = Math.cos(rect.angleRad);
        const s = Math.sin(rect.angleRad);
        axes.push([c, s]);
        axes.push([-s, c]);
      });
      for (let i = 0; i < axes.length; i += 1) {
        const axis = axes[i];
        const pa = projectPolygon(rectA.corners, axis);
        const pb = projectPolygon(rectB.corners, axis);
        if (!rangesOverlap(pa, pb)) {
          return false;
        }
      }
      return true;
    }

    function rectangleInsideAllowedArea(rect, polygonGeom, usedRectangles) {
      for (let i = 0; i < rect.corners.length; i += 1) {
        if (!polygonGeom.intersectsCoordinate(rect.corners[i])) {
          return false;
        }
      }
      for (let i = 0; i < usedRectangles.length; i += 1) {
        if (rectanglesIntersect(rect, usedRectangles[i])) {
          return false;
        }
      }
      // Interior sampling to ensure rectangle area stays in polygon and outside already used rectangles.
      const sampleSteps = 4;
      const c = Math.cos(rect.angleRad);
      const s = Math.sin(rect.angleRad);
      for (let ix = -sampleSteps; ix <= sampleSteps; ix += 1) {
        for (let iy = -sampleSteps; iy <= sampleSteps; iy += 1) {
          const tx = (ix / sampleSteps) * rect.halfWidth;
          const ty = (iy / sampleSteps) * rect.halfHeight;
          const x = rect.center[0] + (tx * c) - (ty * s);
          const y = rect.center[1] + (tx * s) + (ty * c);
          const p = [x, y];
          if (!polygonGeom.intersectsCoordinate(p)) {
            return false;
          }
          for (let r = 0; r < usedRectangles.length; r += 1) {
            if (pointInRectangle(p, usedRectangles[r])) {
              return false;
            }
          }
        }
      }
      return true;
    }

    function rectangleArea(rect) {
      return 4 * rect.halfWidth * rect.halfHeight;
    }

    /**
     * Teilt die Polygon-Formanalyse in Flug-Sektoren: Rechtecke unter minAreaRatio
     * der grössten gefundenen Fläche werden verworfen (Rest bleibt sektorweise nutzbar).
     */
    function filterSectorsAgainstLargest(rectangles, minAreaRatio) {
      if (!Array.isArray(rectangles) || !rectangles.length) {
        return { sectors: [], dropped: 0, raw: 0 };
      }
      const raw = rectangles.length;
      const areas = rectangles.map((r) => rectangleArea(r));
      const maxA = Math.max(...areas);
      if (!Number.isFinite(maxA) || maxA <= 0) {
        return { sectors: rectangles.slice(), dropped: 0, raw };
      }
      const thr = maxA * minAreaRatio;
      const sectors = rectangles.filter((_, i) => areas[i] >= thr);
      return { sectors, dropped: raw - sectors.length, raw };
    }

    /**
     * Dominante Richtung (1. Hauptachse) der Punktwolke in Karteneinheiten — eine Höhenrichtungslinie im Feld.
     */
    function computePrincipalAxisFromMapCoords(coords) {
      if (!Array.isArray(coords) || coords.length < 2) {
        return null;
      }
      let cx = 0;
      let cy = 0;
      coords.forEach((coord) => {
        cx += Number(coord[0]);
        cy += Number(coord[1]);
      });
      cx /= coords.length;
      cy /= coords.length;
      let a = 0;
      let b = 0;
      let c = 0;
      coords.forEach((coord) => {
        const dx = Number(coord[0]) - cx;
        const dy = Number(coord[1]) - cy;
        a += dx * dx;
        b += dx * dy;
        c += dy * dy;
      });
      const angle = 0.5 * Math.atan2(2 * b, a - c);
      const vx = Math.cos(angle);
      const vy = Math.sin(angle);
      let tMin = Infinity;
      let tMax = -Infinity;
      coords.forEach((coord) => {
        const dx = Number(coord[0]) - cx;
        const dy = Number(coord[1]) - cy;
        const t = (dx * vx) + (dy * vy);
        tMin = Math.min(tMin, t);
        tMax = Math.max(tMax, t);
      });
      const span = (tMax - tMin) / 2;
      const halfLen = Math.max(30, span * 1.12, (Math.sqrt(Math.max(a, c) / coords.length)) * 2.2);
      return { cx, cy, vx, vy, halfLen };
    }

    function fitHeightGradientAxis(samples) {
      if (!Array.isArray(samples) || samples.length < 3) {
        return null;
      }
      let cx = 0;
      let cy = 0;
      let cz = 0;
      samples.forEach((s) => {
        cx += Number(s.mapCoord[0]);
        cy += Number(s.mapCoord[1]);
        cz += Number(s.asl);
      });
      cx /= samples.length;
      cy /= samples.length;
      cz /= samples.length;

      let sxx = 0;
      let syy = 0;
      let sxy = 0;
      let sxz = 0;
      let syz = 0;
      let tMin = Infinity;
      let tMax = -Infinity;
      samples.forEach((s) => {
        const dx = Number(s.mapCoord[0]) - cx;
        const dy = Number(s.mapCoord[1]) - cy;
        const dz = Number(s.asl) - cz;
        sxx += dx * dx;
        syy += dy * dy;
        sxy += dx * dy;
        sxz += dx * dz;
        syz += dy * dz;
      });
      const det = (sxx * syy) - (sxy * sxy);
      if (!Number.isFinite(det) || Math.abs(det) < 1e-9) {
        return null;
      }
      const a = ((sxz * syy) - (syz * sxy)) / det;
      const b = ((syz * sxx) - (sxz * sxy)) / det;
      const gradNorm = Math.hypot(a, b);
      if (!Number.isFinite(gradNorm) || gradNorm < 1e-9) {
        return null;
      }

      // Höhenrichtungslinie läuft entlang der Isohöhen (senkrecht zum Gradienten).
      const vx = -b / gradNorm;
      const vy = a / gradNorm;
      samples.forEach((s) => {
        const dx = Number(s.mapCoord[0]) - cx;
        const dy = Number(s.mapCoord[1]) - cy;
        const t = (dx * vx) + (dy * vy);
        tMin = Math.min(tMin, t);
        tMax = Math.max(tMax, t);
      });
      const halfLen = Math.max(18, (tMax - tMin) * 0.55);
      const angleDeg = normalizeAngle180((Math.atan2(vy, vx) * 180) / Math.PI);
      return { cx, cy, vx, vy, halfLen, angleDeg, slopeStrength: gradNorm };
    }

    function usableControlPointSamples() {
      if (!Array.isArray(lastPointData)) {
        return [];
      }
      return lastPointData
        .filter((item) => Number.isFinite(item.asl))
        .map((item) => ({
          lon: Number(item.lon),
          lat: Number(item.lat),
          asl: Number(item.asl),
          mapCoord: lonLatToMapCoord(item.lon, item.lat)
        }));
    }

    function directionColorByIndex(idx) {
      const palette = ["#7d3cff", "#0a8a2a", "#c0392b", "#006d9c", "#995f00", "#8b1f8f", "#1d4d99"];
      return palette[idx % palette.length];
    }

    function weightedAxialMean180(items) {
      let sx = 0;
      let sy = 0;
      items.forEach((item) => {
        const a = normalizeAngle180(item.angleDeg);
        const w = Math.max(1, Number(item.weight) || 1);
        const a2 = (2 * a * Math.PI) / 180;
        sx += Math.cos(a2) * w;
        sy += Math.sin(a2) * w;
      });
      if (Math.abs(sx) < 1e-9 && Math.abs(sy) < 1e-9) {
        return 0;
      }
      return normalizeAngle180((Math.atan2(sy, sx) * 180 / Math.PI) / 2);
    }

    function drawSectorDirectionLineAtCenter(center, angleDeg, halfLen, labelText, colorHex, targetSource) {
      const angRad = (normalizeAngle180(angleDeg) * Math.PI) / 180;
      const vx = Math.cos(angRad);
      const vy = Math.sin(angRad);
      const cx = Number(center[0]);
      const cy = Number(center[1]);
      const p1 = [cx - (vx * halfLen), cy - (vy * halfLen)];
      const p2 = [cx + (vx * halfLen), cy + (vy * halfLen)];
      const lineFeature = new ol.Feature({
        geometry: new ol.geom.LineString([p1, p2])
      });
      lineFeature.setStyle(new ol.style.Style({
        stroke: new ol.style.Stroke({
          color: colorHex,
          width: 3.5,
          lineDash: [12, 8]
        }),
        text: new ol.style.Text({
          text: labelText,
          offsetY: -10,
          font: "bold 11px Arial",
          fill: new ol.style.Fill({ color: "#111111" }),
          stroke: new ol.style.Stroke({ color: "#ffffff", width: 3 })
        })
      }));
      const source = targetSource || sectorDirectionSource;
      source.addFeature(lineFeature);
    }

    /** Drei parallele Linien: gemeinsame Höhenlinienrichtung (Mitte markiert, seitlich leicht versetzt). */
    function drawSubPolyHeightDirectionPair(center, angleDeg, halfLen, colorHex, targetSource, labelText, offsetMapM) {
      const angRad = (normalizeAngle180(angleDeg) * Math.PI) / 180;
      const vx = Math.cos(angRad);
      const vy = Math.sin(angRad);
      const px = -vy;
      const py = vx;
      const cx = Number(center[0]);
      const cy = Number(center[1]);
      const off = Math.max(6, Math.min(40, Number(offsetMapM) || 16));
      const hl = Math.max(20, halfLen);
      const sides = [0, -1, 1];
      sides.forEach((side) => {
        const ox = cx + (px * off * side);
        const oy = cy + (py * off * side);
        const p1 = [ox - (vx * hl), oy - (vy * hl)];
        const p2 = [ox + (vx * hl), oy + (vy * hl)];
        const lineFeature = new ol.Feature({
          geometry: new ol.geom.LineString([p1, p2])
        });
        const withText = side === 0 && labelText;
        lineFeature.setStyle(new ol.style.Style({
          stroke: new ol.style.Stroke({
            color: colorHex,
            width: side === 0 ? 4.5 : 2.5,
            lineDash: side === 0 ? [] : [11, 7]
          }),
          text: withText ? new ol.style.Text({
            text: labelText,
            offsetY: -14,
            font: "bold 11px Arial",
            fill: new ol.style.Fill({ color: "#111111" }),
            stroke: new ol.style.Stroke({ color: "#ffffff", width: 3 })
          }) : undefined
        }));
        targetSource.addFeature(lineFeature);
      });
    }

    function nearestHeightSamplesForCenter(center, allSamples, searchRadius, minCount, maxCount) {
      const byDist = allSamples.map((s) => {
        const dx = Number(s.mapCoord[0]) - Number(center[0]);
        const dy = Number(s.mapCoord[1]) - Number(center[1]);
        return { sample: s, d2: (dx * dx) + (dy * dy) };
      }).sort((a, b) => a.d2 - b.d2);
      const near = byDist
        .filter((item) => item.d2 <= (searchRadius * searchRadius))
        .slice(0, maxCount)
        .map((item) => item.sample);
      if (near.length >= minCount) {
        return near;
      }
      return byDist.slice(0, Math.min(maxCount, byDist.length)).map((item) => item.sample);
    }

    function groupCellsByDirection(cells, maxDiffDeg) {
      if (!Array.isArray(cells) || !cells.length) {
        return [];
      }
      const groups = [];
      cells.forEach((cell) => {
        let bestGroup = null;
        let bestDiff = Number.POSITIVE_INFINITY;
        for (let i = 0; i < groups.length; i += 1) {
          const diff = angleDifference180(cell.angleDeg, groups[i].meanAngle);
          if (diff <= maxDiffDeg && diff < bestDiff) {
            bestDiff = diff;
            bestGroup = groups[i];
          }
        }
        if (!bestGroup) {
          bestGroup = { id: groups.length + 1, meanAngle: cell.angleDeg, cells: [] };
          groups.push(bestGroup);
        }
        bestGroup.cells.push(cell);
        bestGroup.meanAngle = weightedAxialMean180(bestGroup.cells.map((c) => ({ angleDeg: c.angleDeg, weight: 1 })));
      });
      return groups;
    }

    function suggestedSectorCellSizeMeters() {
      const diameter = getPolygonDiameterMeters();
      if (!Number.isFinite(diameter) || diameter <= 0) {
        return 20;
      }
      // Dense enough for a visually closed partition while staying responsive.
      return Math.max(10, Math.min(35, diameter / 70));
    }

    function pointToSegmentDistanceMap(p, a, b) {
      const px = Number(p[0]);
      const py = Number(p[1]);
      const ax = Number(a[0]);
      const ay = Number(a[1]);
      const bx = Number(b[0]);
      const by = Number(b[1]);
      const abx = bx - ax;
      const aby = by - ay;
      const len2 = (abx * abx) + (aby * aby);
      if (len2 < 1e-9) {
        return Math.hypot(px - ax, py - ay);
      }
      const tRaw = ((px - ax) * abx + (py - ay) * aby) / len2;
      const t = Math.max(0, Math.min(1, tRaw));
      const qx = ax + (abx * t);
      const qy = ay + (aby * t);
      return Math.hypot(px - qx, py - qy);
    }

    function minDistanceToPolygonBoundaryMap(point, ringCoords) {
      if (!Array.isArray(ringCoords) || ringCoords.length < 2) {
        return Number.POSITIVE_INFINITY;
      }
      let best = Number.POSITIVE_INFINITY;
      for (let i = 0; i < ringCoords.length; i += 1) {
        const a = ringCoords[i];
        const b = ringCoords[(i + 1) % ringCoords.length];
        const d = pointToSegmentDistanceMap(point, a, b);
        if (d < best) {
          best = d;
        }
      }
      return best;
    }

    function buildSectorCellsFromPolygon(polygonGeom, spacingMetersOverride, borderMetersOverride) {
      const allSamples = usableControlPointSamples();
      if (allSamples.length < 3) {
        return [];
      }
      const extent = polygonGeom.getExtent();
      const cell = Number.isFinite(spacingMetersOverride) && spacingMetersOverride > 0
        ? Math.max(5, spacingMetersOverride)
        : suggestedSectorCellSizeMeters();
      const borderMeters = Number.isFinite(borderMetersOverride) ? Math.max(0, borderMetersOverride) : 0;
      const half = cell * 0.48;
      const searchRadius = Math.max(20, cell * 2.6);
      const ringCoords = Array.isArray(polygonVertices) && polygonVertices.length >= 3
        ? polygonVertices.map((v) => lonLatToMapCoord(v[0], v[1]))
        : [];
      const cells = [];

      for (let x = extent[0] + half; x <= extent[2] - half; x += cell) {
        for (let y = extent[1] + half; y <= extent[3] - half; y += cell) {
          const center = [x, y];
          if (!polygonGeom.intersectsCoordinate(center)) {
            continue;
          }
          if (borderMeters > 0 && ringCoords.length >= 3) {
            const distToEdge = minDistanceToPolygonBoundaryMap(center, ringCoords);
            if (distToEdge < borderMeters) {
              continue;
            }
          }
          const local = nearestHeightSamplesForCenter(center, allSamples, searchRadius, 3, 12);
          const axis = fitHeightGradientAxis(local);
          if (!axis) {
            continue;
          }
          cells.push({
            center,
            angleDeg: axis.angleDeg,
            halfLen: Math.max(cell * 0.6, 12)
          });
        }
      }
      return cells;
    }

    function clearSectorDirectionGroups() {
      sectorDirectionSource.clear();
      sectorDirectionGroupsEnabled = false;
      lastSectorDirectionGroups = [];
      setToggleButtonState(
        toggleSectorDirectionGroupsBtn,
        false,
        "Sektoren nach Höhenrichtung zeigen",
        "Sektoren nach Höhenrichtung verbergen"
      );
    }

    function renderSectorDirectionGroups() {
      sectorDirectionSource.clear();
      const polygonGeom = polygonGeometryFromVertices();
      if (!polygonGeom) {
        clearSectorDirectionGroups();
        return false;
      }
      const cells = buildSectorCellsFromPolygon(polygonGeom);
      if (!cells.length) {
        clearSectorDirectionGroups();
        statusEl.textContent = "Sektorbestimmung benötigt Kontrollpunkte mit Höhe im Polygon.";
        return false;
      }
      const sectorDirMaxDiff = readSectorDirectionMaxDiffDegFromUi();
      const groups = groupCellsByDirection(cells, sectorDirMaxDiff);
      if (!groups.length) {
        clearSectorDirectionGroups();
        return false;
      }
      let cellCount = 0;
      groups.forEach((group, groupIdx) => {
        const color = directionColorByIndex(groupIdx);
        group.cells.forEach((cell) => {
          const x = Number(cell.center[0]);
          const y = Number(cell.center[1]);
          const half = Math.max(6, cell.halfLen * 0.55);
          const ring = [
            [x - half, y - half],
            [x + half, y - half],
            [x + half, y + half],
            [x - half, y + half],
            [x - half, y - half]
          ];
          const cellFeature = new ol.Feature({
            geometry: new ol.geom.Polygon([ring])
          });
          cellFeature.setStyle(new ol.style.Style({
            stroke: new ol.style.Stroke({ color: "rgba(0,0,0,0)", width: 0 }),
            fill: new ol.style.Fill({ color: `${color}66` })
          }));
          sectorDirectionSource.addFeature(cellFeature);
          cellCount += 1;
        });
      });

      // Keep polygon boundary clearly visible above sector fill.
      const ringCoords = polygonVertices.map((v) => lonLatToMapCoord(v[0], v[1]));
      const boundaryFeature = new ol.Feature({
        geometry: new ol.geom.Polygon([[...ringCoords, ringCoords[0]]])
      });
      boundaryFeature.setStyle(new ol.style.Style({
        stroke: new ol.style.Stroke({ color: "#111111", width: 2.5 }),
        fill: new ol.style.Fill({ color: "rgba(0,0,0,0)" })
      }));
      sectorDirectionSource.addFeature(boundaryFeature);

      groups.forEach((group, groupIdx) => {
        const color = directionColorByIndex(groupIdx);
        const angleText = Math.round(group.meanAngle);
        const cx = group.cells.reduce((sum, c) => sum + Number(c.center[0]), 0) / Math.max(1, group.cells.length);
        const cy = group.cells.reduce((sum, c) => sum + Number(c.center[1]), 0) / Math.max(1, group.cells.length);
        const meanHalf = group.cells.reduce((sum, c) => sum + Number(c.halfLen), 0) / Math.max(1, group.cells.length);
        drawSectorDirectionLineAtCenter([cx, cy], group.meanAngle, Math.max(18, meanHalf * 1.4), `G${group.id} ${angleText}°`, color);
      });
      lastSectorDirectionGroups = groups;
      sectorDirectionGroupsEnabled = true;
      setToggleButtonState(
        toggleSectorDirectionGroupsBtn,
        true,
        "Sektoren nach Höhenrichtung zeigen",
        "Sektoren nach Höhenrichtung verbergen"
      );
      statusEl.textContent = `Sektor-Höhenrichtung: ${groups.length} Gruppen aus Original-Polygon, ${cellCount} Polygonzellen mit ähnlicher Richtung (Winkelunterschied ≤ ${sectorDirMaxDiff}°).`;
      return true;
    }

    function searchLargestRectangle(polygonGeom, usedRectangles, searchScaleMeters) {
      const extent = polygonGeom.getExtent();
      const width = Number(extent[2]) - Number(extent[0]);
      const height = Number(extent[3]) - Number(extent[1]);
      const maxDim = Math.max(width, height);
      const minHalf = Math.max(4, Math.min(20, searchScaleMeters / 25));
      let centerStep = Math.max(12, Math.min(45, searchScaleMeters / 18));
      const estimateCount = (width / centerStep) * (height / centerStep);
      if (estimateCount > 900) {
        centerStep *= Math.sqrt(estimateCount / 900);
      }
      const angleList = [];
      for (let deg = 0; deg < 180; deg += 15) {
        angleList.push(deg * Math.PI / 180);
      }

      let best = null;
      for (let x = extent[0]; x <= extent[2]; x += centerStep) {
        for (let y = extent[1]; y <= extent[3]; y += centerStep) {
          const center = [x, y];
          if (!polygonGeom.intersectsCoordinate(center)) {
            continue;
          }
          let inUsed = false;
          for (let r = 0; r < usedRectangles.length; r += 1) {
            if (pointInRectangle(center, usedRectangles[r])) {
              inUsed = true;
              break;
            }
          }
          if (inUsed) {
            continue;
          }

          for (let a = 0; a < angleList.length; a += 1) {
            const angleRad = angleList[a];
            let hw = minHalf;
            let hh = minHalf;
            let grow = Math.max(8, Math.min(35, maxDim / 16));
            let current = makeRotatedRectangle(center, hw, hh, angleRad);
            if (!rectangleInsideAllowedArea(current, polygonGeom, usedRectangles)) {
              continue;
            }
            while (grow >= 1.5) {
              let improved = false;
              const attempts = [
                makeRotatedRectangle(center, hw + grow, hh, angleRad),
                makeRotatedRectangle(center, hw, hh + grow, angleRad),
                makeRotatedRectangle(center, hw + grow, hh + grow, angleRad)
              ];
              let bestAttempt = null;
              for (let i = 0; i < attempts.length; i += 1) {
                const candidate = attempts[i];
                if (!rectangleInsideAllowedArea(candidate, polygonGeom, usedRectangles)) {
                  continue;
                }
                if (!bestAttempt || rectangleArea(candidate) > rectangleArea(bestAttempt)) {
                  bestAttempt = candidate;
                }
              }
              if (bestAttempt) {
                hw = bestAttempt.halfWidth;
                hh = bestAttempt.halfHeight;
                current = bestAttempt;
                improved = true;
              }
              if (!improved) {
                grow /= 2;
              }
            }

            if (!best || rectangleArea(current) > rectangleArea(best)) {
              best = current;
            }
          }
        }
      }
      return best;
    }

    function drawRectangle(rect, index) {
      const ring = [...rect.corners, rect.corners[0]];
      const feature = new ol.Feature({
        geometry: new ol.geom.Polygon([ring])
      });
      feature.setStyle(new ol.style.Style({
        stroke: new ol.style.Stroke({ color: "#7d3cff", width: 3 }),
        fill: new ol.style.Fill({ color: "rgba(125,60,255,0.18)" }),
        text: new ol.style.Text({
          text: `S${index}`,
          font: "bold 13px Arial",
          fill: new ol.style.Fill({ color: "#4a1d99" }),
          stroke: new ol.style.Stroke({ color: "#ffffff", width: 3 })
        })
      }));
      rectangleSource.addFeature(feature);
    }

    function clearRectangleAnalysis() {
      rectangleSource.clear();
      rectangleInnerLineSource.clear();
      sectorDirectionSource.clear();
      subPolygonV4Source.clear();
      lastRectangles = [];
      rectangleAnalysisEnabled = false;
      rectangleInnerLinesEnabled = false;
      controlPointsEnabled = false;
      setToggleButtonState(
        togglePointsBtn,
        false,
        "Kontrollpunkte im Feld setzen",
        "Kontrollpunkte im Feld entfernen"
      );
      setToggleButtonState(
        runRectangleAnalysisBtn,
        false,
        "Polygon-Formanalyse zeigen",
        "Polygon-Formanalyse verbergen"
      );
      setToggleButtonState(
        drawRectangleInnerLinesBtn,
        false,
        "Höhenrichtungslinie je Sektor (>20%) zeigen",
        "Höhenrichtungslinie je Sektor (>20%) verbergen"
      );
      clearSectorDirectionGroups();
      saveFieldSettings();
    }

    function drawSectorHeightLinesFromGroups() {
      rectangleInnerLineSource.clear();
      if (!Array.isArray(lastSectorDirectionGroups) || !lastSectorDirectionGroups.length) {
        const polygonGeom = polygonGeometryFromVertices();
        const cells = polygonGeom ? buildSectorCellsFromPolygon(polygonGeom) : [];
        const groups = cells.length ? groupCellsByDirection(cells, readSectorDirectionMaxDiffDegFromUi()) : [];
        if (!groups.length) {
          rectangleInnerLinesEnabled = false;
          setToggleButtonState(
            drawRectangleInnerLinesBtn,
            false,
            "Höhenrichtungslinie je Sektor (>20%) zeigen",
            "Höhenrichtungslinie je Sektor (>20%) verbergen"
          );
          statusEl.textContent = "Keine Sektoren vorhanden. Zuerst Sektoren nach Höhenrichtung berechnen.";
          saveFieldSettings();
          return;
        }
        lastSectorDirectionGroups = groups;
      }
      const totalCells = lastSectorDirectionGroups.reduce((sum, group) => sum + ((group && group.cells) ? group.cells.length : 0), 0);
      if (totalCells < 1) {
        rectangleInnerLinesEnabled = false;
        setToggleButtonState(
          drawRectangleInnerLinesBtn,
          false,
          "Höhenrichtungslinie je Sektor (>20%) zeigen",
          "Höhenrichtungslinie je Sektor (>20%) verbergen"
        );
        statusEl.textContent = "Keine Sektoren vorhanden.";
        saveFieldSettings();
        return;
      }
      let drawnCount = 0;
      lastSectorDirectionGroups.forEach((group) => {
        const cells = Array.isArray(group.cells) ? group.cells : [];
        const ratio = cells.length / totalCells;
        if (ratio <= SECTOR_MIN_AREA_RATIO) {
          return;
        }
        const cx = cells.reduce((sum, c) => sum + Number(c.center[0]), 0) / Math.max(1, cells.length);
        const cy = cells.reduce((sum, c) => sum + Number(c.center[1]), 0) / Math.max(1, cells.length);
        let minX = Number.POSITIVE_INFINITY;
        let maxX = Number.NEGATIVE_INFINITY;
        let minY = Number.POSITIVE_INFINITY;
        let maxY = Number.NEGATIVE_INFINITY;
        cells.forEach((c) => {
          const x = Number(c.center[0]);
          const y = Number(c.center[1]);
          const h = Number(c.halfLen) || 0;
          minX = Math.min(minX, x - h);
          maxX = Math.max(maxX, x + h);
          minY = Math.min(minY, y - h);
          maxY = Math.max(maxY, y + h);
        });
        const diag = (Number.isFinite(minX) && Number.isFinite(maxX) && Number.isFinite(minY) && Number.isFinite(maxY))
          ? Math.hypot(maxX - minX, maxY - minY)
          : 0;
        // Make line clearly visible over filled sectors.
        const halfLen = Math.max(45, diag * 0.45);
        const color = directionColorByIndex((group.id || 1) - 1);
        drawSectorDirectionLineAtCenter(
          [cx, cy],
          group.meanAngle,
          halfLen,
          `G${group.id} HRL ${(ratio * 100).toFixed(0)}%`,
          color,
          rectangleInnerLineSource
        );
        drawnCount += 1;
      });
      if (drawnCount < 1) {
        rectangleInnerLinesEnabled = false;
        setToggleButtonState(
          drawRectangleInnerLinesBtn,
          false,
          "Höhenrichtungslinie je Sektor (>20%) zeigen",
          "Höhenrichtungslinie je Sektor (>20%) verbergen"
        );
        statusEl.textContent = "Keine Sektor-Höhenrichtungslinie gezeichnet: nur Sektoren >20% der Fläche werden berücksichtigt.";
        saveFieldSettings();
        return;
      }
      rectangleInnerLinesEnabled = true;
      setToggleButtonState(
        drawRectangleInnerLinesBtn,
        true,
        "Höhenrichtungslinie je Sektor (>20%) zeigen",
        "Höhenrichtungslinie je Sektor (>20%) verbergen"
      );
      statusEl.textContent = `${drawnCount} Sektor-Höhenrichtungslinien gezeichnet (nur Sektoren >20% Fläche).`;
      saveFieldSettings();
      applyHelperLayersVisibility();
    }

    function drawRectangleHeightLinesFromControlPoints() {
      // Legacy alias: now sector-based and filtered by area share > 20%.
      drawSectorHeightLinesFromGroups();
    }

    function orderSectorCellsByDirection(cells, angleDeg, trackDistanceMeters) {
      if (!Array.isArray(cells) || !cells.length) {
        return [];
      }
      const ang = (normalizeAngle180(angleDeg) * Math.PI) / 180;
      const ux = Math.cos(ang);
      const uy = Math.sin(ang);
      const vx = -uy;
      const vy = ux;
      const bandSize = Math.max(3, Number(trackDistanceMeters) || 10);
      const enriched = cells.map((cell) => {
        const x = Number(cell.center[0]);
        const y = Number(cell.center[1]);
        const along = (x * ux) + (y * uy);
        const across = (x * vx) + (y * vy);
        const band = Math.round(across / bandSize);
        return { cell, along, band };
      });
      enriched.sort((a, b) => {
        if (a.band !== b.band) {
          return a.band - b.band;
        }
        if (a.band % 2 === 0) {
          return a.along - b.along;
        }
        return b.along - a.along;
      });
      return enriched.map((item) => item.cell);
    }

    function orderSectorCellsByDirectionNoZigZag(cells, angleDeg, trackDistanceMeters) {
      if (!Array.isArray(cells) || !cells.length) {
        return [];
      }
      const ang = (normalizeAngle180(angleDeg) * Math.PI) / 180;
      const ux = Math.cos(ang);
      const uy = Math.sin(ang);
      const vx = -uy;
      const vy = ux;
      const bandSize = Math.max(3, Number(trackDistanceMeters) || 10);
      const enriched = cells.map((cell) => {
        const x = Number(cell.center[0]);
        const y = Number(cell.center[1]);
        const along = (x * ux) + (y * uy);
        const across = (x * vx) + (y * vy);
        const band = Math.round(across / bandSize);
        return { cell, along, band };
      });
      // Keine Zick-Zack-Umkehr: jede Spur verläuft in gleicher Hauptrichtung,
      // damit längere Ecke-zu-Ecke-Abschnitte entstehen.
      enriched.sort((a, b) => {
        if (a.band !== b.band) {
          return a.band - b.band;
        }
        return a.along - b.along;
      });
      return enriched.map((item) => item.cell);
    }

    function buildContinuousParallelTargets(cells, angleDeg, trackDistanceMeters, extensionRatioInput) {
      if (!Array.isArray(cells) || !cells.length) {
        return [];
      }
      const extensionRatio = Number.isFinite(extensionRatioInput)
        ? Math.max(0, Math.min(3.2, extensionRatioInput))
        : 0.35;
      const ang = (normalizeAngle180(angleDeg) * Math.PI) / 180;
      const ux = Math.cos(ang);
      const uy = Math.sin(ang);
      const vx = -uy;
      const vy = ux;
      const bandSize = Math.max(3, Number(trackDistanceMeters) || 10);
      const enriched = cells.map((cell) => {
        const x = Number(cell.center[0]);
        const y = Number(cell.center[1]);
        return {
          cell,
          along: (x * ux) + (y * uy),
          across: (x * vx) + (y * vy),
          band: Math.round(((x * vx) + (y * vy)) / bandSize)
        };
      });
      const byBand = new Map();
      enriched.forEach((item) => {
        if (!byBand.has(item.band)) {
          byBand.set(item.band, []);
        }
        byBand.get(item.band).push(item);
      });
      const sortedBands = Array.from(byBand.keys()).sort((a, b) => a - b);
      const bandEndpoints = [];
      sortedBands.forEach((band) => {
        const bandItems = byBand.get(band);
        if (!bandItems || !bandItems.length) {
          return;
        }
        let minAlong = Number.POSITIVE_INFINITY;
        let maxAlong = Number.NEGATIVE_INFINITY;
        bandItems.forEach((item) => {
          if (item.along < minAlong) minAlong = item.along;
          if (item.along > maxAlong) maxAlong = item.along;
        });
        const span = Math.max(5, maxAlong - minAlong);
        const targetStart = minAlong - (span * extensionRatio);
        const targetEnd = maxAlong + (span * extensionRatio);
        let bestStart = bandItems[0];
        let bestEnd = bandItems[0];
        let bestStartScore = Number.POSITIVE_INFINITY;
        let bestEndScore = Number.POSITIVE_INFINITY;
        bandItems.forEach((item) => {
          const startScore = Math.abs(item.along - targetStart);
          if (startScore < bestStartScore) {
            bestStartScore = startScore;
            bestStart = item;
          }
          const endScore = Math.abs(item.along - targetEnd);
          if (endScore < bestEndScore) {
            bestEndScore = endScore;
            bestEnd = item;
          }
        });
        if (bestStart.cell === bestEnd.cell) {
          // Falls nur ein Punkt existiert, nehme den entfernten Nachbarn als Endpunkt.
          let alt = null;
          let altScore = Number.NEGATIVE_INFINITY;
          bandItems.forEach((item) => {
            const dist = Math.abs(item.along - bestStart.along);
            if (dist > altScore) {
              altScore = dist;
              alt = item;
            }
          });
          if (alt) {
            bestEnd = alt;
          }
        }
        bandEndpoints.push([bestStart.cell, bestEnd.cell]);
      });
      // Kontinuierliches Muster:
      // Spur 1: von Kante A nach Kante B,
      // Spur 2: parallel versetzt von Kante B nach Kante A, usw.
      const targets = [];
      for (let i = 0; i < bandEndpoints.length; i += 1) {
        const pair = bandEndpoints[i];
        if (!pair || pair.length < 2) {
          continue;
        }
        if (i % 2 === 0) {
          targets.push(pair[0], pair[1]);
        } else {
          targets.push(pair[1], pair[0]);
        }
      }
      return targets;
    }

    function buildParallelLanesForCells(cells, angleDeg, trackDistanceMeters, extensionRatioInput, forceContinuousBands) {
      if (!Array.isArray(cells) || !cells.length) {
        return [];
      }
      const extensionRatio = Number.isFinite(extensionRatioInput)
        ? Math.max(0, Math.min(3.2, extensionRatioInput))
        : 0.35;
      const ang = (normalizeAngle180(angleDeg) * Math.PI) / 180;
      const ux = Math.cos(ang);
      const uy = Math.sin(ang);
      const vx = -uy;
      const vy = ux;
      const bandSize = Math.max(3, Number(trackDistanceMeters) || 10);
      const enriched = cells.map((cell) => {
        const x = Number(cell.center[0]);
        const y = Number(cell.center[1]);
        const across = (x * vx) + (y * vy);
        const along = (x * ux) + (y * uy);
        return {
          cell,
          across,
          along,
          band: Math.round(across / bandSize)
        };
      });

      const byBand = new Map();
      enriched.forEach((item) => {
        if (!byBand.has(item.band)) {
          byBand.set(item.band, []);
        }
        byBand.get(item.band).push(item);
      });
      if (!byBand.size) {
        return [];
      }

      let bandOrder = Array.from(byBand.keys()).sort((a, b) => a - b);
      if (forceContinuousBands) {
        const minBand = Math.min(...bandOrder);
        const maxBand = Math.max(...bandOrder);
        bandOrder = [];
        for (let b = minBand; b <= maxBand; b += 1) {
          bandOrder.push(b);
        }
      }

      const lanes = [];
      bandOrder.forEach((band, idx) => {
        let laneItems = byBand.get(band);
        if (!laneItems || !laneItems.length) {
          // Lücke auffüllen: nutze nächste Zellen zu diesem Bandzentrum.
          const targetAcross = band * bandSize;
          laneItems = enriched
            .slice()
            .sort((a, b) => Math.abs(a.across - targetAcross) - Math.abs(b.across - targetAcross))
            .slice(0, Math.min(12, enriched.length));
          if (!laneItems.length) {
            return;
          }
        }
        let minAlong = Number.POSITIVE_INFINITY;
        let maxAlong = Number.NEGATIVE_INFINITY;
        laneItems.forEach((item) => {
          if (item.along < minAlong) minAlong = item.along;
          if (item.along > maxAlong) maxAlong = item.along;
        });
        const span = Math.max(5, maxAlong - minAlong);
        const targetStart = minAlong - (span * extensionRatio);
        const targetEnd = maxAlong + (span * extensionRatio);
        let bestStart = laneItems[0];
        let bestEnd = laneItems[0];
        let bestStartScore = Number.POSITIVE_INFINITY;
        let bestEndScore = Number.POSITIVE_INFINITY;
        laneItems.forEach((item) => {
          const s = Math.abs(item.along - targetStart);
          if (s < bestStartScore) {
            bestStartScore = s;
            bestStart = item;
          }
          const e = Math.abs(item.along - targetEnd);
          if (e < bestEndScore) {
            bestEndScore = e;
            bestEnd = item;
          }
        });
        if (bestStart.cell === bestEnd.cell) {
          let alt = null;
          let altDist = Number.NEGATIVE_INFINITY;
          laneItems.forEach((item) => {
            const d = Math.abs(item.along - bestStart.along);
            if (d > altDist) {
              altDist = d;
              alt = item;
            }
          });
          if (alt) {
            bestEnd = alt;
          }
        }
        const startCell = (idx % 2 === 0) ? bestStart.cell : bestEnd.cell;
        const endCell = (idx % 2 === 0) ? bestEnd.cell : bestStart.cell;
        const alongMean = laneItems.reduce((s, it) => s + it.along, 0) / laneItems.length;
        const acrossNom = band * bandSize;
        const anchorMapX = (alongMean * ux) + (acrossNom * vx);
        const anchorMapY = (alongMean * uy) + (acrossNom * vy);
        lanes.push({
          band,
          startCell,
          endCell,
          targetSpacing: bandSize,
          anchorMap: [anchorMapX, anchorMapY]
        });
      });
      return lanes;
    }

    function resolveSectorDirectionHints(polygonGeom, spacingMeters) {
      let groups = Array.isArray(lastSectorDirectionGroups) ? lastSectorDirectionGroups : [];
      if (!groups.length) {
        const cells = buildSectorCellsFromPolygon(polygonGeom, spacingMeters, 0);
        groups = cells.length ? groupCellsByDirection(cells, readSectorDirectionMaxDiffDegFromUi()) : [];
        if (groups.length) {
          lastSectorDirectionGroups = groups;
        }
      }
      if (!groups.length) {
        return [];
      }
      const totalCells = groups.reduce((sum, g) => sum + (((g && g.cells) ? g.cells.length : 0)), 0);
      const minRatio = Math.max(0.01, SECTOR_MIN_AREA_RATIO);
      let filtered = groups.filter((g) => {
        const count = Array.isArray(g.cells) ? g.cells.length : 0;
        return totalCells > 0 && (count / totalCells) >= minRatio;
      });
      if (!filtered.length) {
        filtered = groups;
      }
      return filtered.map((g) => {
        const cells = Array.isArray(g.cells) ? g.cells : [];
        const cx = cells.reduce((sum, c) => sum + Number(c.center[0]), 0) / Math.max(1, cells.length);
        const cy = cells.reduce((sum, c) => sum + Number(c.center[1]), 0) / Math.max(1, cells.length);
        return {
          angleDeg: normalizeAngle180(g.meanAngle),
          center: [cx, cy],
          cellCount: cells.length
        };
      });
    }

    function selectPrimarySectorHints(hints, maxCount) {
      const arr = Array.isArray(hints) ? hints.slice() : [];
      if (!arr.length) {
        return [];
      }
      const limit = Math.max(1, Math.min(4, Number(maxCount) || 2));
      arr.sort((a, b) => Number(b.cellCount || 0) - Number(a.cellCount || 0));
      return arr.slice(0, Math.min(limit, arr.length));
    }

    function nearestSectorHintAngleForPoint(mapPoint, sectorHints) {
      const hints = Array.isArray(sectorHints) ? sectorHints : [];
      if (!hints.length) {
        return NaN;
      }
      let best = hints[0];
      let bestDist = Number.POSITIVE_INFINITY;
      hints.forEach((hint) => {
        const d = mapDistance(mapPoint, hint.center);
        if (d < bestDist) {
          bestDist = d;
          best = hint;
        }
      });
      return normalizeAngle180(best.angleDeg);
    }

    function buildRectDirectionMapFromSectorHints(blocksByRect, sectorHints) {
      const angleByRect = new Map();
      lastRectangles.forEach((rect, idx) => {
        const rectCells = Array.isArray(blocksByRect[idx]) ? blocksByRect[idx] : [];
        if (rectCells.length) {
          const items = rectCells
            .map((cell) => nearestSectorHintAngleForPoint(cell.center, sectorHints))
            .filter((a) => Number.isFinite(a))
            .map((a) => ({ angleDeg: a, weight: 1 }));
          if (items.length) {
            angleByRect.set(idx, weightedAxialMean180(items));
            return;
          }
        }
        const fallback = nearestSectorHintAngleForPoint([Number(rect.center[0]), Number(rect.center[1])], sectorHints);
        angleByRect.set(idx, Number.isFinite(fallback) ? fallback : rectangleAngleDeg(rect));
      });
      return angleByRect;
    }

    function sectorCenterMap(group) {
      const cells = Array.isArray(group.cells) ? group.cells : [];
      if (!cells.length) {
        return [0, 0];
      }
      const cx = cells.reduce((sum, c) => sum + Number(c.center[0]), 0) / cells.length;
      const cy = cells.reduce((sum, c) => sum + Number(c.center[1]), 0) / cells.length;
      return [cx, cy];
    }

    function buildHeightGuidedSectorFlightPath() {
      if (
        !Array.isArray(flightStartLonLat) || flightStartLonLat.length !== 2 ||
        !Array.isArray(flightPolygonVertices) || flightPolygonVertices.length < 3
      ) {
        return null;
      }
      const sp = readFlightSpacingContextFromUi();
      const { borderMeters, effectiveTrackDistance } = sp;
      const polygonGeom = polygonGeometryFromVertices();
      if (!polygonGeom) {
        return null;
      }
      const cells = buildSectorCellsFromPolygon(polygonGeom, effectiveTrackDistance, borderMeters);
      if (!cells.length) {
        return null;
      }
      const groups = groupCellsByDirection(cells, readSectorDirectionMaxDiffDegFromUi());
      if (!groups.length) {
        return null;
      }
      lastSectorDirectionGroups = groups;

      const startMap = lonLatToMapCoord(Number(flightStartLonLat[0]), Number(flightStartLonLat[1]));
      const remaining = groups.map((group) => ({ ...group }));
      const sectorOrder = [];
      let currentCenter = [Number(startMap[0]), Number(startMap[1])];
      while (remaining.length) {
        let bestPos = 0;
        let bestDist = Number.POSITIVE_INFINITY;
        for (let i = 0; i < remaining.length; i += 1) {
          const c = sectorCenterMap(remaining[i]);
          const d = mapDistance(currentCenter, c);
          if (d < bestDist) {
            bestDist = d;
            bestPos = i;
          }
        }
        const next = remaining.splice(bestPos, 1)[0];
        sectorOrder.push(next);
        currentCenter = sectorCenterMap(next);
      }

      const sweepLonLat = [];
      sectorOrder.forEach((group) => {
        const orderedCells = orderSectorCellsByDirection(group.cells || [], group.meanAngle, effectiveTrackDistance);
        orderedCells.forEach((cell) => {
          const lonLat = ol.proj.transform([Number(cell.center[0]), Number(cell.center[1])], "EPSG:3857", "EPSG:4326");
          sweepLonLat.push([Number(lonLat[0]), Number(lonLat[1])]);
        });
      });
      if (!sweepLonLat.length) {
        return null;
      }

      const start = [Number(flightStartLonLat[0]), Number(flightStartLonLat[1])];
      const routed = [start];
      const startInside = pointInPolygonLonLat(start, flightPolygonVertices);
      let routedSegments = 0;
      sweepLonLat.forEach((target, idx) => {
        const from = routed[routed.length - 1];
        if (pointsEqualLonLat(from, target)) {
          return;
        }
        if (idx === 0 && !startInside) {
          routed.push(target);
          return;
        }
        const route = buildInPolygonRoute(from, target, flightPolygonVertices);
        if (!route || !route.length) {
          if (segmentInsidePolygonLonLat(from, target, flightPolygonVertices)) {
            routed.push(target);
          }
          return;
        }
        route.forEach((p) => {
          const last = routed[routed.length - 1];
          if (!pointsEqualLonLat(last, p)) {
            routed.push([Number(p[0]), Number(p[1])]);
          }
        });
        routedSegments += 1;
      });
      if (routed.length < 2) {
        return null;
      }
      return {
        path: routed,
        sectorCount: groups.length,
        cellCount: sweepLonLat.length,
        trackDistance: effectiveTrackDistance,
        routedSegments
      };
    }

    function estimateAslByNearestControlPoint(lonLat) {
      if (!Array.isArray(lastPointData) || !lastPointData.length) {
        return NaN;
      }
      const mapCoord = lonLatToMapCoord(Number(lonLat[0]), Number(lonLat[1]));
      let bestDist2 = Number.POSITIVE_INFINITY;
      let bestAsl = NaN;
      lastPointData.forEach((sample) => {
        if (!sample || !Number.isFinite(Number(sample.asl))) {
          return;
        }
        const sMap = lonLatToMapCoord(Number(sample.lon), Number(sample.lat));
        const dx = Number(sMap[0]) - Number(mapCoord[0]);
        const dy = Number(sMap[1]) - Number(mapCoord[1]);
        const d2 = (dx * dx) + (dy * dy);
        if (d2 < bestDist2) {
          bestDist2 = d2;
          bestAsl = Number(sample.asl);
        }
      });
      return bestAsl;
    }

    function insertProfileSupportPoints(pathLonLat, thresholdMeters) {
      if (!Array.isArray(pathLonLat) || pathLonLat.length < 2) {
        return pathLonLat;
      }
      const threshold = Number.isFinite(thresholdMeters) ? Math.max(0, thresholdMeters) : PROFILE_SUPPORT_HEIGHT_THRESHOLD_M;
      const enriched = [pathLonLat[0]];
      for (let i = 1; i < pathLonLat.length; i += 1) {
        const prev = enriched[enriched.length - 1];
        const next = pathLonLat[i];
        const prevAsl = estimateAslByNearestControlPoint(prev);
        const nextAsl = estimateAslByNearestControlPoint(next);
        if (Number.isFinite(prevAsl) && Number.isFinite(nextAsl) && Math.abs(nextAsl - prevAsl) > threshold) {
          const mid = [
            (Number(prev[0]) + Number(next[0])) / 2,
            (Number(prev[1]) + Number(next[1])) / 2
          ];
          enriched.push(mid);
        }
        enriched.push(next);
      }
      return enriched;
    }

    /**
     * Bodenabdeckung (alle Varianten): Ziel ist vollständige Abdeckung des Polygons mit dem Sensortracer
     * (Halbbreite = Swath/2 aus Flughöhe und Öffnungswinkel). Doppelüberdeckung soll möglichst gering sein
     * und wird über „max. Überdeckung %“, Bahnabstand und Parallelitätstoleranz begrenzt; bei Zielkonflikt
     * hat Vollabdeckung Vorrang. Randfaktor×Bahnabstand ist die Obergrenze für den Randabstand.
     */
    function readFlightSpacingContextFromUi() {
      const trackDistanceBase = Math.max(5, Number(fpTrackDistanceEl && fpTrackDistanceEl.value) || 30);
      const borderFactor = Math.max(0, Math.min(1, Number((fpBorderFactorEl && fpBorderFactorEl.value) || 0.4)));
      const maxBorderDistance = trackDistanceBase * borderFactor;
      const parallelTolerancePct = Math.max(
        0,
        Math.min(
          50,
          Number((typeof fpParallelToleranceEl !== "undefined" && fpParallelToleranceEl) ? fpParallelToleranceEl.value : 20) || 20
        )
      );
      const tolFactor = parallelTolerancePct / 100;
      const fh = Math.max(
        1,
        Number.isFinite(Number((typeof fpHeightEl !== "undefined" && fpHeightEl) ? fpHeightEl.value : 60))
          ? Number((typeof fpHeightEl !== "undefined" && fpHeightEl) ? fpHeightEl.value : 60)
          : 60
      );
      const opticAngle = Math.max(
        1,
        Math.min(
          179,
          Number.isFinite(Number((typeof fpOpticAngleEl !== "undefined" && fpOpticAngleEl) ? fpOpticAngleEl.value : 36))
            ? Number((typeof fpOpticAngleEl !== "undefined" && fpOpticAngleEl) ? fpOpticAngleEl.value : 36)
            : 36
        )
      );
      const swathWidthMeters = 2 * fh * Math.tan((opticAngle * Math.PI / 180) / 2);
      const maxOverlapPct = Math.max(
        0,
        Math.min(
          70,
          Number.isFinite(Number((typeof fpMaxOverlapEl !== "undefined" && fpMaxOverlapEl) ? fpMaxOverlapEl.value : 30))
            ? Number((typeof fpMaxOverlapEl !== "undefined" && fpMaxOverlapEl) ? fpMaxOverlapEl.value : 30)
            : 30
        )
      );
      const minStrideFactor = Math.max(0.3, 1 - (maxOverlapPct / 100));
      const overlapLimitedSpacingMin = Number.isFinite(swathWidthMeters) && swathWidthMeters > 0
        ? swathWidthMeters * minStrideFactor
        : 0;
      const spacingMin = Math.max(trackDistanceBase * (1 - tolFactor), overlapLimitedSpacingMin);
      const spacingMax = Math.max(spacingMin, trackDistanceBase * (1 + tolFactor));
      const effectiveTrackDistance = Math.max(spacingMin, Math.min(trackDistanceBase, spacingMax));
      const effectiveBorderDistance = Math.min(maxBorderDistance, Math.max(0, effectiveTrackDistance * 0.5));
      return {
        trackDistanceBase,
        borderMeters: effectiveBorderDistance,
        maxBorderDistance,
        parallelTolerancePct,
        spacingMin,
        spacingMax,
        maxOverlapPct,
        swathWidthMeters,
        effectiveTrackDistance
      };
    }

    function readSectorDirectionMaxDiffDegFromUi() {
      const el = document.getElementById("fpSectorDirectionMaxDiff");
      if (!el) {
        return SECTOR_DIRECTION_MAX_DIFF_DEG_DEFAULT;
      }
      const v = Math.round(Number(el.value));
      if (!Number.isFinite(v)) {
        return SECTOR_DIRECTION_MAX_DIFF_DEG_DEFAULT;
      }
      return Math.max(5, Math.min(60, v));
    }

    function buildRectangleHeightGuidedFlightPathV2() {
      if (
        !Array.isArray(flightStartLonLat) || flightStartLonLat.length !== 2 ||
        !Array.isArray(flightPolygonVertices) || flightPolygonVertices.length < 3
      ) {
        return null;
      }
      if (!Array.isArray(lastRectangles) || !lastRectangles.length) {
        return null;
      }

      const sp = readFlightSpacingContextFromUi();
      const { borderMeters, effectiveTrackDistance } = sp;

      const polygonGeom = polygonGeometryFromVertices();
      if (!polygonGeom) {
        return null;
      }
      // Vollständige Polygonabdeckung: Zellen über gesamtes Polygon.
      const cells = buildSectorCellsFromPolygon(polygonGeom, effectiveTrackDistance, borderMeters);
      if (!cells.length) {
        return null;
      }

      const sectorHintsAll = resolveSectorDirectionHints(polygonGeom, effectiveTrackDistance);
      const sectorHints = selectPrimarySectorHints(sectorHintsAll, 2);
      if (!sectorHints.length) {
        return null;
      }

      const blocksByRect = {};
      cells.forEach((cell) => {
        let assignedRect = -1;
        for (let r = 0; r < lastRectangles.length; r += 1) {
          if (pointInRectangle(cell.center, lastRectangles[r])) {
            assignedRect = r;
            break;
          }
        }
        if (assignedRect < 0) {
          // Außerhalb der Rechtecke: nächstgelegenes Rechteck als Richtungs-Hinweis.
          let bestRect = 0;
          let bestDist = Number.POSITIVE_INFINITY;
          for (let r = 0; r < lastRectangles.length; r += 1) {
            const d = mapDistance(cell.center, lastRectangles[r].center);
            if (d < bestDist) {
              bestDist = d;
              bestRect = r;
            }
          }
          assignedRect = bestRect;
        }
        if (!blocksByRect[assignedRect]) {
          blocksByRect[assignedRect] = [];
        }
        blocksByRect[assignedRect].push(cell);
      });

      const angleByRect = buildRectDirectionMapFromSectorHints(blocksByRect, sectorHints);

      const runtimeLineExtensionRatio = getLineExtensionInfluenceRatio();

      const blocks = Object.keys(blocksByRect)
        .map((k) => Number(k))
        .filter((idx) => Number.isInteger(idx) && idx >= 0 && idx < lastRectangles.length && Array.isArray(blocksByRect[idx]) && blocksByRect[idx].length)
        .map((rectIdx) => {
          const directionAngle = angleByRect.get(rectIdx);
          const orderedCells = buildContinuousParallelTargets(
            blocksByRect[rectIdx],
            directionAngle,
            effectiveTrackDistance,
            runtimeLineExtensionRatio
          );
          return {
            rectIdx,
            directionAngle,
            center: [Number(lastRectangles[rectIdx].center[0]), Number(lastRectangles[rectIdx].center[1])],
            cells: orderedCells
          };
        })
        .filter((block) => Array.isArray(block.cells) && block.cells.length > 0);

      if (!blocks.length) {
        return null;
      }

      const startMap = lonLatToMapCoord(Number(flightStartLonLat[0]), Number(flightStartLonLat[1]));
      const remaining = blocks.map((b) => ({ ...b }));
      const orderedBlocks = [];
      let current = [Number(startMap[0]), Number(startMap[1])];
      while (remaining.length) {
        let bestPos = 0;
        let bestDist = Number.POSITIVE_INFINITY;
        for (let i = 0; i < remaining.length; i += 1) {
          const d = mapDistance(current, remaining[i].center);
          if (d < bestDist) {
            bestDist = d;
            bestPos = i;
          }
        }
        const next = remaining.splice(bestPos, 1)[0];
        orderedBlocks.push(next);
        current = [Number(next.center[0]), Number(next.center[1])];
      }

      const sweepLonLat = [];
      orderedBlocks.forEach((block) => {
        block.cells.forEach((cell) => {
          const lonLat = ol.proj.transform([Number(cell.center[0]), Number(cell.center[1])], "EPSG:3857", "EPSG:4326");
          sweepLonLat.push([Number(lonLat[0]), Number(lonLat[1])]);
        });
      });
      if (!sweepLonLat.length) {
        return null;
      }

      function pointKey(lonLat) {
        return `${Number(lonLat[0]).toFixed(7)},${Number(lonLat[1]).toFixed(7)}`;
      }

      const start = [Number(flightStartLonLat[0]), Number(flightStartLonLat[1])];
      const routed = [start];
      const startInside = pointInPolygonLonLat(start, flightPolygonVertices);
      const targetKeys = new Set(sweepLonLat.map((p) => pointKey(p)));
      const reachedKeys = new Set();
      let routedSegments = 0;
      let failedSegments = 0;

      sweepLonLat.forEach((target, idx) => {
        const from = routed[routed.length - 1];
        if (pointsEqualLonLat(from, target)) {
          reachedKeys.add(pointKey(target));
          return;
        }
        if (idx === 0 && !startInside) {
          routed.push(target);
          reachedKeys.add(pointKey(target));
          return;
        }
        const route = buildInPolygonRoute(from, target, flightPolygonVertices);
        if (!route || !route.length) {
          if (segmentInsidePolygonLonLat(from, target, flightPolygonVertices)) {
            routed.push(target);
            reachedKeys.add(pointKey(target));
          } else {
            failedSegments += 1;
          }
          return;
        }
        route.forEach((p) => {
          const last = routed[routed.length - 1];
          if (!pointsEqualLonLat(last, p)) {
            routed.push([Number(p[0]), Number(p[1])]);
          }
        });
        routedSegments += 1;
        reachedKeys.add(pointKey(target));
      });

      const missedTargets = Array.from(targetKeys).filter((k) => !reachedKeys.has(k)).length;
      if (failedSegments > 0 || missedTargets > 0 || routed.length < 2) {
        return null;
      }

      const profilePath = insertProfileSupportPoints(routed, PROFILE_SUPPORT_HEIGHT_THRESHOLD_M);
      return {
        path: profilePath,
        rectangleCount: orderedBlocks.length,
        rectangleHeightCount: sectorHints.length,
        cellCount: sweepLonLat.length,
        trackDistance: effectiveTrackDistance,
        routedSegments,
        supportPointsAdded: Math.max(0, profilePath.length - routed.length)
      };
    }

    function buildRectangleHeightGuidedFlightPathV3() {
      if (
        !Array.isArray(flightStartLonLat) || flightStartLonLat.length !== 2 ||
        !Array.isArray(flightPolygonVertices) || flightPolygonVertices.length < 3
      ) {
        return null;
      }
      if (!Array.isArray(lastRectangles) || !lastRectangles.length) {
        return null;
      }

      const sp = readFlightSpacingContextFromUi();
      const {
        borderMeters,
        maxBorderDistance,
        spacingMin,
        spacingMax,
        effectiveTrackDistance,
        parallelTolerancePct,
        swathWidthMeters
      } = sp;

      const polygonGeom = polygonGeometryFromVertices();
      if (!polygonGeom) {
        return null;
      }
      const cells = buildSectorCellsFromPolygon(polygonGeom, effectiveTrackDistance, borderMeters);
      if (!cells.length) {
        return null;
      }

      const sectorHintsAll = resolveSectorDirectionHints(polygonGeom, effectiveTrackDistance);
      const sectorHints = selectPrimarySectorHints(sectorHintsAll, 2);
      if (!sectorHints.length) {
        return null;
      }

      const blocksByRect = {};
      cells.forEach((cell) => {
        let assignedRect = -1;
        for (let r = 0; r < lastRectangles.length; r += 1) {
          if (pointInRectangle(cell.center, lastRectangles[r])) {
            assignedRect = r;
            break;
          }
        }
        if (assignedRect < 0) {
          let bestRect = 0;
          let bestDist = Number.POSITIVE_INFINITY;
          for (let r = 0; r < lastRectangles.length; r += 1) {
            const d = mapDistance(cell.center, lastRectangles[r].center);
            if (d < bestDist) {
              bestDist = d;
              bestRect = r;
            }
          }
          assignedRect = bestRect;
        }
        if (!blocksByRect[assignedRect]) {
          blocksByRect[assignedRect] = [];
        }
        blocksByRect[assignedRect].push(cell);
      });

      const angleByRect = buildRectDirectionMapFromSectorHints(blocksByRect, sectorHints);

      const runtimeLineExtensionRatio = getLineExtensionInfluenceRatio();

      const blocks = Object.keys(blocksByRect)
        .map((k) => Number(k))
        .filter((idx) => Number.isInteger(idx) && idx >= 0 && idx < lastRectangles.length && Array.isArray(blocksByRect[idx]) && blocksByRect[idx].length)
        .map((rectIdx) => ({
          rectIdx,
          directionAngle: angleByRect.get(rectIdx),
          center: [Number(lastRectangles[rectIdx].center[0]), Number(lastRectangles[rectIdx].center[1])],
          lanes: buildParallelLanesForCells(
            blocksByRect[rectIdx],
            angleByRect.get(rectIdx),
            effectiveTrackDistance,
            runtimeLineExtensionRatio,
            true
          )
        }))
        .filter((block) => Array.isArray(block.lanes) && block.lanes.length > 0);

      if (!blocks.length) {
        return null;
      }

      const startMap = lonLatToMapCoord(Number(flightStartLonLat[0]), Number(flightStartLonLat[1]));
      const polygonCenter = polygonGeom.getInteriorPoint
        ? polygonGeom.getInteriorPoint().getCoordinates()
        : startMap;
      const globalAxisDeg = principalDirectionAngleFromMapPoints(
        [
          startMap,
          [Number(polygonCenter[0]), Number(polygonCenter[1])],
          ...flightPolygonVertices.map((v) => lonLatToMapCoord(Number(v[0]), Number(v[1])))
        ],
        blocks[0].directionAngle
      );
      const ga = (normalizeAngle180(globalAxisDeg) * Math.PI) / 180;
      const gux = Math.cos(ga);
      const guy = Math.sin(ga);
      const withAlong = blocks.map((b) => ({
        ...b,
        _along: (Number(b.center[0]) * gux) + (Number(b.center[1]) * guy)
      }));
      withAlong.sort((a, b) => a._along - b._along);
      const firstCenter = withAlong[0] ? withAlong[0].center : startMap;
      const lastCenter = withAlong[withAlong.length - 1] ? withAlong[withAlong.length - 1].center : startMap;
      const orderedBlocks = mapDistance(startMap, lastCenter) < mapDistance(startMap, firstCenter)
        ? withAlong.slice().reverse()
        : withAlong;

      const sweepLonLat = [];
      let currentMap = [Number(startMap[0]), Number(startMap[1])];
      orderedBlocks.forEach((block) => {
        const blockPointsA = [];
        (block.lanes || []).forEach((lane) => {
          if (!lane || !lane.startCell || !lane.endCell) {
            return;
          }
          blockPointsA.push(lane.startCell.center, lane.endCell.center);
        });
        if (!blockPointsA.length) {
          return;
        }
        const blockPointsB = blockPointsA.slice().reverse();
        const dA = mapDistance(currentMap, blockPointsA[0]);
        const dB = mapDistance(currentMap, blockPointsB[0]);
        const chosen = dB < dA ? blockPointsB : blockPointsA;
        chosen.forEach((mapP) => {
          const lonLat = ol.proj.transform([Number(mapP[0]), Number(mapP[1])], "EPSG:3857", "EPSG:4326");
          sweepLonLat.push([Number(lonLat[0]), Number(lonLat[1])]);
          currentMap = [Number(mapP[0]), Number(mapP[1])];
        });
      });
      if (!sweepLonLat.length) {
        return null;
      }

      const start = [Number(flightStartLonLat[0]), Number(flightStartLonLat[1])];
      const routed = [start];
      const startInside = pointInPolygonLonLat(start, flightPolygonVertices);
      let routedSegments = 0;
      sweepLonLat.forEach((target, idx) => {
        const from = routed[routed.length - 1];
        if (pointsEqualLonLat(from, target)) {
          return;
        }
        if (idx === 0 && !startInside) {
          routed.push(target);
          return;
        }
        const route = buildInPolygonRoute(from, target, flightPolygonVertices);
        if (!route || !route.length) {
          if (segmentInsidePolygonLonLat(from, target, flightPolygonVertices)) {
            routed.push(target);
          }
          return;
        }
        route.forEach((p) => {
          const last = routed[routed.length - 1];
          if (!pointsEqualLonLat(last, p)) {
            routed.push([Number(p[0]), Number(p[1])]);
          }
        });
        routedSegments += 1;
      });
      if (routed.length < 2) {
        return null;
      }

      // Coverage repair: Zellzentren ergänzen, die weiter als ~Sensortracer-Halbbreite (Swath/2) vom Pfad liegen.
      const routedMap = routed.map((p) => lonLatToMapCoord(Number(p[0]), Number(p[1])));
      function distanceToPath(mapPoint) {
        if (routedMap.length < 2) {
          return Number.POSITIVE_INFINITY;
        }
        let best = Number.POSITIVE_INFINITY;
        for (let i = 0; i < routedMap.length - 1; i += 1) {
          const d = pointToSegmentDistanceMap(mapPoint, routedMap[i], routedMap[i + 1]);
          if (d < best) {
            best = d;
          }
        }
        return best;
      }
      const swathHalf = Number.isFinite(swathWidthMeters) && swathWidthMeters > 0 ? swathWidthMeters * 0.5 : 0;
      const coverageThreshold = swathHalf > 0
        ? swathHalf * 0.95
        : Math.max(6, spacingMax * 0.5);
      const uncovered = cells.filter((cell) => distanceToPath(cell.center) > coverageThreshold);
      uncovered.forEach((cell) => {
        const lonLat = ol.proj.transform([Number(cell.center[0]), Number(cell.center[1])], "EPSG:3857", "EPSG:4326");
        const target = [Number(lonLat[0]), Number(lonLat[1])];
        const from = routed[routed.length - 1];
        const route = buildInPolygonRoute(from, target, flightPolygonVertices);
        if (route && route.length) {
          route.forEach((p) => {
            const last = routed[routed.length - 1];
            if (!pointsEqualLonLat(last, p)) {
              routed.push([Number(p[0]), Number(p[1])]);
            }
          });
        } else if (segmentInsidePolygonLonLat(from, target, flightPolygonVertices)) {
          routed.push(target);
        }
      });

      return {
        path: insertProfileSupportPoints(routed, PROFILE_SUPPORT_HEIGHT_THRESHOLD_M),
        rectangleCount: orderedBlocks.length,
        rectangleHeightCount: sectorHints.length,
        cellCount: cells.length,
        trackDistance: effectiveTrackDistance,
        parallelTolerancePct,
        trackSpacingMin: spacingMin,
        trackSpacingMax: spacingMax,
        maxBorderDistance,
        usedBorderDistance: borderMeters,
        routedSegments
      };
    }

    function clearSubPolygonV4Overlay() {
      subPolygonV4Source.clear();
      applySubPolygonV4LayerVisibility();
      refreshSubPolygonV4ToggleButtonState();
    }

    function mapCoordToPrimaryRectangleIndex(mapCoord) {
      for (let r = 0; r < lastRectangles.length; r += 1) {
        if (pointInRectangle(mapCoord, lastRectangles[r])) {
          return r;
        }
      }
      let best = 0;
      let bestD = Number.POSITIVE_INFINITY;
      for (let r = 0; r < lastRectangles.length; r += 1) {
        const d = mapDistance(mapCoord, lastRectangles[r].center);
        if (d < bestD) {
          bestD = d;
          best = r;
        }
      }
      return best;
    }

    function buildRectanglesMergedComponents(linkSlackMeters) {
      const n = lastRectangles.length;
      if (!n) {
        return { rectToComp: [], compCount: 0 };
      }
      const parent = Array.from({ length: n }, (_, i) => i);
      function find(a) {
        let x = a;
        while (parent[x] !== x) {
          x = parent[x];
        }
        let z = a;
        while (parent[z] !== z) {
          const pz = parent[z];
          parent[z] = x;
          z = pz;
        }
        return x;
      }
      function union(a, b) {
        const ra = find(a);
        const rb = find(b);
        if (ra === rb) {
          return;
        }
        if (ra < rb) {
          parent[rb] = ra;
        } else {
          parent[ra] = rb;
        }
      }
      const slack = Number.isFinite(linkSlackMeters) ? Math.max(0, linkSlackMeters) : 0;
      for (let i = 0; i < n; i += 1) {
        const ri = lastRectangles[i];
        const di = Math.hypot(Number(ri.halfWidth), Number(ri.halfHeight));
        for (let j = i + 1; j < n; j += 1) {
          const rj = lastRectangles[j];
          const dj = Math.hypot(Number(rj.halfWidth), Number(rj.halfHeight));
          const dc = mapDistance(ri.center, rj.center);
          if (dc <= di + dj + slack) {
            union(i, j);
          }
        }
      }
      const rootToComp = new Map();
      const rectToComp = [];
      let compCount = 0;
      for (let i = 0; i < n; i += 1) {
        const root = find(i);
        if (!rootToComp.has(root)) {
          rootToComp.set(root, compCount);
          compCount += 1;
        }
        rectToComp.push(rootToComp.get(root));
      }
      return { rectToComp, compCount };
    }

    function convexHullClosedRing(points) {
      if (!Array.isArray(points) || points.length < 3) {
        return null;
      }
      const pts = points
        .filter((p) => Number.isFinite(Number(p[0])) && Number.isFinite(Number(p[1])))
        .map((p) => [Number(p[0]), Number(p[1])])
        .sort((a, b) => (a[0] === b[0] ? a[1] - b[1] : a[0] - b[0]));
      if (pts.length < 3) {
        return null;
      }
      function cross(o, a, b) {
        return ((a[0] - o[0]) * (b[1] - o[1])) - ((a[1] - o[1]) * (b[0] - o[0]));
      }
      const lower = [];
      pts.forEach((p) => {
        while (lower.length >= 2 && cross(lower[lower.length - 2], lower[lower.length - 1], p) <= 0) {
          lower.pop();
        }
        lower.push(p);
      });
      const upper = [];
      for (let i = pts.length - 1; i >= 0; i -= 1) {
        const p = pts[i];
        while (upper.length >= 2 && cross(upper[upper.length - 2], upper[upper.length - 1], p) <= 0) {
          upper.pop();
        }
        upper.push(p);
      }
      upper.pop();
      lower.pop();
      const ring = lower.concat(upper);
      if (ring.length < 3) {
        return null;
      }
      return [...ring, ring[0]];
    }

    function openPolygonRingNoDupClose(ringClosed) {
      if (!Array.isArray(ringClosed) || ringClosed.length < 3) {
        return null;
      }
      const r = ringClosed.map((p) => [Number(p[0]), Number(p[1])]);
      const a = r[0];
      const b = r[r.length - 1];
      if (Math.abs(a[0] - b[0]) < 1e-8 && Math.abs(a[1] - b[1]) < 1e-8) {
        return r.slice(0, -1);
      }
      return r;
    }

    function closePolygonRing(openRing) {
      if (!Array.isArray(openRing) || openRing.length < 3) {
        return null;
      }
      const first = openRing[0];
      const last = openRing[openRing.length - 1];
      if (Math.abs(first[0] - last[0]) < 1e-8 && Math.abs(first[1] - last[1]) < 1e-8) {
        return openRing.map((p) => [Number(p[0]), Number(p[1])]);
      }
      return [...openRing.map((p) => [Number(p[0]), Number(p[1])]), [Number(first[0]), Number(first[1])]];
    }

    function clipOpenPolygonByHalfPlaneLe(openRing, mx, my, nx, ny) {
      const EPS = 1e-7;
      const sign = (p) => ((p[0] - mx) * nx + (p[1] - my) * ny);
      const inside = (p) => sign(p) <= EPS;
      const intersect = (s, e) => {
        const ds = sign(s);
        const de = sign(e);
        const denom = ds - de;
        if (Math.abs(denom) < 1e-14) {
          return null;
        }
        const t = ds / denom;
        return [s[0] + (t * (e[0] - s[0])), s[1] + (t * (e[1] - s[1]))];
      };
      if (!openRing || openRing.length < 3) {
        return null;
      }
      const output = [];
      let S = openRing[openRing.length - 1];
      for (let i = 0; i < openRing.length; i += 1) {
        const E = openRing[i];
        const Si = inside(S);
        const Ei = inside(E);
        if (Ei) {
          if (!Si) {
            const ix = intersect(S, E);
            if (ix) {
              output.push(ix);
            }
          }
          output.push([E[0], E[1]]);
        } else if (Si) {
          const ix = intersect(S, E);
          if (ix) {
            output.push(ix);
          }
        }
        S = E;
      }
      if (output.length < 3) {
        return null;
      }
      const dedup = [];
      for (let i = 0; i < output.length; i += 1) {
        const p = output[i];
        const prev = dedup[dedup.length - 1];
        if (!prev || Math.abs(prev[0] - p[0]) > 1e-6 || Math.abs(prev[1] - p[1]) > 1e-6) {
          dedup.push(p);
        }
      }
      if (dedup.length < 3) {
        return null;
      }
      return dedup;
    }

    function voronoiCellInPolygonOpen(mainOpenRing, siteIndex, sites) {
      if (!Array.isArray(sites) || sites.length <= 1) {
        return mainOpenRing.map((p) => [Number(p[0]), Number(p[1])]);
      }
      const Si = sites[siteIndex];
      if (!Si) {
        return null;
      }
      let ring = mainOpenRing.map((p) => [Number(p[0]), Number(p[1])]);
      for (let j = 0; j < sites.length; j += 1) {
        if (j === siteIndex) {
          continue;
        }
        const Sj = sites[j];
        const mx = (Si[0] + Sj[0]) / 2;
        const my = (Si[1] + Sj[1]) / 2;
        const nx = Sj[0] - Si[0];
        const ny = Sj[1] - Si[1];
        if ((nx * nx) + (ny * ny) < 1e-16) {
          continue;
        }
        const next = clipOpenPolygonByHalfPlaneLe(ring, mx, my, nx, ny);
        if (!next) {
          return null;
        }
        ring = next;
      }
      return ring;
    }

    function sectorGroupCentroidMap(group) {
      const cells = group && Array.isArray(group.cells) ? group.cells : [];
      if (!cells.length) {
        return null;
      }
      let cx = 0;
      let cy = 0;
      cells.forEach((cell) => {
        cx += Number(cell.center[0]);
        cy += Number(cell.center[1]);
      });
      return [cx / cells.length, cy / cells.length];
    }

    function buildV4VoronoiPartitionClosedRings(mainRingClosed, filteredGroups) {
      const groups = Array.isArray(filteredGroups) ? filteredGroups : [];
      const sites = groups.map((g) => sectorGroupCentroidMap(g)).filter(Boolean);
      if (sites.length !== groups.length || !sites.length) {
        return null;
      }
      const mainOpen = openPolygonRingNoDupClose(mainRingClosed);
      if (!mainOpen || mainOpen.length < 3) {
        return null;
      }
      const rings = [];
      for (let i = 0; i < sites.length; i += 1) {
        const openCell = voronoiCellInPolygonOpen(mainOpen, i, sites);
        const closed = closePolygonRing(openCell);
        if (!closed || closed.length < 4) {
          rings.push(null);
          continue;
        }
        try {
          const g = new ol.geom.Polygon([closed]);
          const a = Math.abs(Number(g.getArea()) || 0);
          if (a < 1e-6) {
            rings.push(null);
          } else {
            rings.push(closed);
          }
        } catch (_) {
          rings.push(null);
        }
      }
      return rings;
    }

    function intersectInfiniteLineWithEdgeParam3857(px, py, dx, dy, ax, ay, bx, by) {
      const ex = bx - ax;
      const ey = by - ay;
      const denom = (dx * ey) - (dy * ex);
      if (Math.abs(denom) < 1e-18) {
        return null;
      }
      const wx = ax - px;
      const wy = ay - py;
      const t = ((wx * ey) - (wy * ex)) / denom;
      const s = ((wx * dy) - (wy * dx)) / denom;
      if (s < -1e-7 || s > 1 + 1e-7) {
        return null;
      }
      return t;
    }

    function collectLinePolygonIntersectionTs3857(polygonGeom, px, py, dnx, dny) {
      const ring = polygonGeom.getLinearRing(0);
      const coords = ring.getCoordinates();
      const ts = [];
      for (let i = 0; i < coords.length - 1; i += 1) {
        const ax = coords[i][0];
        const ay = coords[i][1];
        const bx = coords[i + 1][0];
        const by = coords[i + 1][1];
        const t = intersectInfiniteLineWithEdgeParam3857(px, py, dnx, dny, ax, ay, bx, by);
        if (t !== null && Number.isFinite(t)) {
          ts.push(t);
        }
      }
      ts.sort((a, b) => a - b);
      const EPS = 1e-3;
      const out = [];
      for (let j = 0; j < ts.length; j += 1) {
        if (!out.length || Math.abs(ts[j] - out[out.length - 1]) > EPS) {
          out.push(ts[j]);
        }
      }
      return out;
    }

    /**
     * Maximale Strecke durch px,py entlang Einheitsrichtung (dnx,dny), die im Polygon liegt
     * (Rand zu Rand, für parallele Flugbahnen).
     */
    function chordMapThroughPointInPolygon3857(polygonGeom, px, py, ux, uy) {
      const dx = Number(ux);
      const dy = Number(uy);
      const len = Math.hypot(dx, dy);
      if (len < 1e-12) {
        return null;
      }
      const dnx = dx / len;
      const dny = dy / len;
      const ts = collectLinePolygonIntersectionTs3857(polygonGeom, px, py, dnx, dny);
      if (ts.length < 2) {
        return null;
      }
      const EPSB = 1e-6;
      for (let i = 0; i < ts.length - 1; i += 1) {
        const ta = ts[i];
        const tb = ts[i + 1];
        if (!(ta <= EPSB && tb >= -EPSB)) {
          continue;
        }
        const mid = (ta + tb) * 0.5;
        const mx = px + dnx * mid;
        const my = py + dny * mid;
        if (polygonGeom.intersectsCoordinate([mx, my])) {
          return {
            p0: [px + dnx * ta, py + dny * ta],
            p1: [px + dnx * tb, py + dny * tb]
          };
        }
      }
      return null;
    }

    /**
     * Verkürzt eine Sehne in Map-Einheiten (EPSG:3857, ≈ Meter) an beiden Enden entlang der Sehne,
     * damit der Pfad den Randabstand und die halbe Bahnbreite besser einhält und weniger überlappt.
     */
    function shortenChordMapEndsMeters3857(p0, p1, insetMeters) {
      const inset = Math.max(0, Number(insetMeters) || 0);
      if (!p0 || !p1 || inset <= 0) {
        return [p0, p1];
      }
      const x0 = Number(p0[0]);
      const y0 = Number(p0[1]);
      const x1 = Number(p1[0]);
      const y1 = Number(p1[1]);
      const dx = x1 - x0;
      const dy = y1 - y0;
      const len = Math.hypot(dx, dy);
      if (!Number.isFinite(len) || len < 1e-4) {
        return [p0, p1];
      }
      if (inset * 2 >= len * 0.92) {
        const t = len * 0.25;
        const ux = dx / len;
        const uy = dy / len;
        return [
          [x0 + ux * t, y0 + uy * t],
          [x1 - ux * t, y1 - uy * t]
        ];
      }
      const ux = dx / len;
      const uy = dy / len;
      const t = Math.min(inset, len * 0.48);
      return [
        [x0 + ux * t, y0 + uy * t],
        [x1 - ux * t, y1 - uy * t]
      ];
    }

    /** D: nur weit auseinander liegende Lücken-Ziele → weniger Umrouting, weniger Zick-Zack und Doppelabdeckung. */
    function v4PickSparseGapTargets3857(gapSortedDesc, maxPick, clusterRadiusM) {
      const list = Array.isArray(gapSortedDesc) ? gapSortedDesc : [];
      const maxN = Math.max(1, Math.floor(Number(maxPick) || 1));
      const r = Math.max(8, Number(clusterRadiusM) || 16);
      const picked = [];
      for (let i = 0; i < list.length && picked.length < maxN; i += 1) {
        const g = list[i];
        if (!g || !Array.isArray(g.center)) {
          continue;
        }
        let ok = true;
        for (let j = 0; j < picked.length; j += 1) {
          if (mapDistance(g.center, picked[j].center) < r) {
            ok = false;
            break;
          }
        }
        if (ok) {
          picked.push(g);
        }
      }
      return picked;
    }

    /** D: Abstand Zellzentrum → nächste parallele Bahnpolyline (nur Streifen, ohne Verbinder). */
    function v4DistanceToLaneLegs3857(laneLegs, mapPoint) {
      if (!Array.isArray(laneLegs) || !laneLegs.length || !mapPoint) {
        return Number.POSITIVE_INFINITY;
      }
      const px = Number(mapPoint[0]);
      const py = Number(mapPoint[1]);
      let best = Number.POSITIVE_INFINITY;
      for (let li = 0; li < laneLegs.length; li += 1) {
        const L = laneLegs[li];
        if (!L || !L.p0Map || !L.p1Map) {
          continue;
        }
        const d = pointToSegmentDistanceMap([px, py], L.p0Map, L.p1Map);
        if (d < best) {
          best = d;
        }
      }
      return best;
    }

    function v4ApplyLaneLegMapToRouted3857(lane, routed) {
      if (!lane || !Array.isArray(routed)) {
        return;
      }
      const i0 = Number(lane.i0);
      const i1 = Number(lane.i1);
      if (!Number.isFinite(i0) || !Number.isFinite(i1) || i0 < 0 || i1 >= routed.length || i0 > i1) {
        return;
      }
      const ll0 = ol.proj.transform([Number(lane.p0Map[0]), Number(lane.p0Map[1])], "EPSG:3857", "EPSG:4326");
      const ll1 = ol.proj.transform([Number(lane.p1Map[0]), Number(lane.p1Map[1])], "EPSG:3857", "EPSG:4326");
      const p0 = [Number(ll0[0]), Number(ll0[1])];
      const p1 = [Number(ll1[0]), Number(ll1[1])];
      const t0 = routed[i0];
      const t1 = routed[i1];
      if (Array.isArray(t0) && t0.length > 2) {
        p0[2] = t0[2];
      }
      if (Array.isArray(t1) && t1.length > 2) {
        p1[2] = t1[2];
      }
      routed[i0] = p0;
      routed[i1] = p1;
    }

    /**
     * D: Lücken nur durch Verlängerung bestehender, gerader Bahnpärchen (keine neuen Umrouten / Zusatzpfade).
     * Erweitert nur, wenn die Projektion der Lücke vor dem Start- oder hinter dem Endpunkt liegt (End-Lücken).
     */
    function v4TryExtendLaneLegForGap3857(lane, gap3857, coverageR, polygonVerticesLL) {
      if (!lane || !Array.isArray(gap3857) || gap3857.length < 2 || !Array.isArray(polygonVerticesLL) || polygonVerticesLL.length < 3) {
        return false;
      }
      const cov = Math.max(2, Number(coverageR) || 0);
      const gx = Number(gap3857[0]);
      const gy = Number(gap3857[1]);
      let x0 = Number(lane.p0Map[0]);
      let y0 = Number(lane.p0Map[1]);
      let x1 = Number(lane.p1Map[0]);
      let y1 = Number(lane.p1Map[1]);
      const dx = x1 - x0;
      const dy = y1 - y0;
      const L = Math.hypot(dx, dy);
      if (L < 1e-6) {
        return false;
      }
      const dnx = dx / L;
      const dny = dy / L;
      const distSeg = pointToSegmentDistanceMap([gx, gy], [x0, y0], [x1, y1]);
      if (distSeg <= cov) {
        return false;
      }
      const tRaw = ((gx - x0) * dx + (gy - y0) * dy) / (L * L);
      if (tRaw >= 0 && tRaw <= 1) {
        const qx = x0 + tRaw * dx;
        const qy = y0 + tRaw * dy;
        const perp = Math.hypot(gx - qx, gy - qy);
        if (perp > cov * 0.98) {
          return false;
        }
      }
      const step = Math.max(5, cov * 0.38);
      const maxExt = Math.max(220, cov * 90);

      function chordInsidePolygon3857(a3857, b3857) {
        const la = ol.proj.transform([Number(a3857[0]), Number(a3857[1])], "EPSG:3857", "EPSG:4326");
        const lb = ol.proj.transform([Number(b3857[0]), Number(b3857[1])], "EPSG:3857", "EPSG:4326");
        return segmentInsidePolygonLonLat(
          [Number(la[0]), Number(la[1])],
          [Number(lb[0]), Number(lb[1])],
          polygonVerticesLL
        );
      }

      if (tRaw < 0) {
        for (let s = step; s <= maxExt; s += step) {
          const q0 = [x0 - dnx * s, y0 - dny * s];
          if (!chordInsidePolygon3857(q0, [x1, y1])) {
            break;
          }
          if (pointToSegmentDistanceMap([gx, gy], q0, [x1, y1]) <= cov) {
            lane.p0Map = q0;
            return true;
          }
        }
        return false;
      }
      if (tRaw > 1) {
        for (let s = step; s <= maxExt; s += step) {
          const q1 = [x1 + dnx * s, y1 + dny * s];
          if (!chordInsidePolygon3857([x0, y0], q1)) {
            break;
          }
          if (pointToSegmentDistanceMap([gx, gy], [x0, y0], q1) <= cov) {
            lane.p1Map = q1;
            return true;
          }
        }
        return false;
      }
      return false;
    }

    /**
     * Nur Adaptiv E (komplex): Lücke liegt mit senkrechtem Abstand neben der Sehne, Projektion aber zwischen p0 und p1.
     * Verlängert das dem Lückenfuß nähere Streifenende entlang der Sehne — weiterhin keine neuen Verbinder, nur längere gerade Bahn.
     */
    function v4TryExtendAdaptiveInteriorAdjacentEnd3857(lane, gap3857, coverageR, polygonVerticesLL) {
      if (!lane || !Array.isArray(gap3857) || gap3857.length < 2 || !Array.isArray(polygonVerticesLL) || polygonVerticesLL.length < 3) {
        return false;
      }
      const cov = Math.max(2, Number(coverageR) || 0);
      const gx = Number(gap3857[0]);
      const gy = Number(gap3857[1]);
      let x0 = Number(lane.p0Map[0]);
      let y0 = Number(lane.p0Map[1]);
      let x1 = Number(lane.p1Map[0]);
      let y1 = Number(lane.p1Map[1]);
      const dx = x1 - x0;
      const dy = y1 - y0;
      const L = Math.hypot(dx, dy);
      if (L < 1e-6) {
        return false;
      }
      const dnx = dx / L;
      const dny = dy / L;
      const distSeg = pointToSegmentDistanceMap([gx, gy], [x0, y0], [x1, y1]);
      if (distSeg <= cov) {
        return false;
      }
      const tRaw = ((gx - x0) * dx + (gy - y0) * dy) / (L * L);
      if (tRaw < 0 || tRaw > 1) {
        return false;
      }
      const qx = x0 + (tRaw * dx);
      const qy = y0 + (tRaw * dy);
      const perp = Math.hypot(gx - qx, gy - qy);
      if (perp < cov * 1.02 || perp > cov * 1.38) {
        return false;
      }
      const step = Math.max(5, cov * 0.38);
      const maxExt = Math.max(280, cov * 95);

      function chordInsidePolygon3857(a3857, b3857) {
        const la = ol.proj.transform([Number(a3857[0]), Number(a3857[1])], "EPSG:3857", "EPSG:4326");
        const lb = ol.proj.transform([Number(b3857[0]), Number(b3857[1])], "EPSG:3857", "EPSG:4326");
        return segmentInsidePolygonLonLat(
          [Number(la[0]), Number(la[1])],
          [Number(lb[0]), Number(lb[1])],
          polygonVerticesLL
        );
      }

      if (tRaw < 0.5) {
        for (let s = step; s <= maxExt; s += step) {
          const q0 = [x0 - dnx * s, y0 - dny * s];
          if (!chordInsidePolygon3857(q0, [x1, y1])) {
            break;
          }
          if (pointToSegmentDistanceMap([gx, gy], q0, [x1, y1]) <= cov) {
            lane.p0Map = q0;
            return true;
          }
        }
        return false;
      }
      for (let s = step; s <= maxExt; s += step) {
        const q1 = [x1 + dnx * s, y1 + dny * s];
        if (!chordInsidePolygon3857([x0, y0], q1)) {
          break;
        }
        if (pointToSegmentDistanceMap([gx, gy], [x0, y0], q1) <= cov) {
          lane.p1Map = q1;
          return true;
        }
      }
      return false;
    }

    /**
     * F: vor dem Routing jede Bahnpolylinie entlang der Sehne gegenüber dem Feinraster strecken (nur End-Lücken, gleiche Regeln wie v4TryExtendLaneLegForGap3857).
     */
    function v5AggressivelyExtendChordEndpoints3857(p0Map, p1Map, polygonVerticesLL, probeCells, coverageR, maxIterations) {
      const p0 = [Number(p0Map[0]), Number(p0Map[1])];
      const p1 = [Number(p1Map[0]), Number(p1Map[1])];
      const lane = { p0Map: p0, p1Map: p1, i0: 0, i1: 1 };
      const probes = Array.isArray(probeCells) ? probeCells : [];
      const cov = Math.max(2, Number(coverageR) || 0);
      const maxIt = Math.max(1, Math.floor(Number(maxIterations) || 1));
      for (let it = 0; it < maxIt; it += 1) {
        let worst = null;
        let worstD = 0;
        for (let pi = 0; pi < probes.length; pi += 1) {
          const cell = probes[pi];
          const c = cell && Array.isArray(cell.center) ? cell.center : null;
          if (!c) {
            continue;
          }
          const d = pointToSegmentDistanceMap(c, lane.p0Map, lane.p1Map);
          if (d <= cov) {
            continue;
          }
          const x0 = lane.p0Map[0];
          const y0 = lane.p0Map[1];
          const x1 = lane.p1Map[0];
          const y1 = lane.p1Map[1];
          const dx = x1 - x0;
          const dy = y1 - y0;
          const L2 = (dx * dx) + (dy * dy);
          if (L2 < 1e-10) {
            continue;
          }
          const tRaw = (((c[0] - x0) * dx) + ((c[1] - y0) * dy)) / L2;
          if ((tRaw < 0 || tRaw > 1) && d > worstD) {
            worstD = d;
            worst = c;
          }
        }
        if (!worst) {
          break;
        }
        const ext = v4TryExtendLaneLegForGap3857(lane, worst, cov, polygonVerticesLL);
        if (!ext) {
          break;
        }
      }
      return [lane.p0Map, lane.p1Map];
    }

    function buildV4SectorClipGeometry3857(blockIdx, partitionRings, filteredGroups, fallbackPolygonGeom) {
      const hasCompletePartition = Array.isArray(partitionRings)
        && Array.isArray(filteredGroups)
        && partitionRings.length === filteredGroups.length
        && partitionRings.every((r) => Array.isArray(r) && r.length >= 4);
      if (!hasCompletePartition) {
        return fallbackPolygonGeom;
      }
      const ring = partitionRings && partitionRings[blockIdx];
      if (ring && ring.length >= 4) {
        try {
          return new ol.geom.Polygon([ring]);
        } catch (_) {
          // fall through
        }
      }
      return fallbackPolygonGeom;
    }

    function v4AppendRouteOrStraight(fromLL, toLL, routed) {
      const from = routed[routed.length - 1];
      if (pointsEqualLonLat(from, toLL)) {
        return 0;
      }
      if (segmentInsidePolygonLonLat(from, toLL, flightPolygonVertices)) {
        routed.push([Number(toLL[0]), Number(toLL[1])]);
        return 1;
      }
      const route = buildInPolygonRoute(from, toLL, flightPolygonVertices);
      if (route && route.length) {
        let added = 0;
        route.forEach((p) => {
          const last = routed[routed.length - 1];
          if (!pointsEqualLonLat(last, p)) {
            routed.push([Number(p[0]), Number(p[1])]);
            added += 1;
          }
        });
        return added;
      }
      if (segmentInsidePolygonLonLat(from, toLL, flightPolygonVertices)) {
        routed.push([Number(toLL[0]), Number(toLL[1])]);
        return 1;
      }
      return 0;
    }

    function collectComponentOutlinePoints(compId, rectIndicesByComp, blocksByComp) {
      const pts = [];
      (blocksByComp[compId] || []).forEach((cell) => {
        pts.push([Number(cell.center[0]), Number(cell.center[1])]);
      });
      (rectIndicesByComp[compId] || []).forEach((ri) => {
        const rect = lastRectangles[ri];
        if (rect && Array.isArray(rect.corners)) {
          rect.corners.forEach((c) => {
            pts.push([Number(c[0]), Number(c[1])]);
          });
        }
      });
      return pts;
    }

    function paintV4SectorSubPolygonsOrange(filteredGroups, vizBlocks) {
      clearSubPolygonV4Overlay();
      const groups = Array.isArray(filteredGroups) ? filteredGroups : [];
      if (!groups.length) {
        applySubPolygonV4LayerVisibility();
        refreshSubPolygonV4ToggleButtonState();
        return;
      }
      const polyGeom = polygonGeometryFromVertices();
      const mainRingClosed = polyGeom && polyGeom.getCoordinates && polyGeom.getCoordinates()[0]
        ? polyGeom.getCoordinates()[0]
        : null;
      const partitionRings = mainRingClosed
        ? buildV4VoronoiPartitionClosedRings(mainRingClosed, groups)
        : null;
      const hasCompletePartition = Array.isArray(partitionRings)
        && partitionRings.length === groups.length
        && partitionRings.every((r) => Array.isArray(r) && r.length >= 4);
      groups.forEach((group, gIdx) => {
        const cells = Array.isArray(group.cells) ? group.cells : [];
        const pts = cells.map((c) => [Number(c.center[0]), Number(c.center[1])]);
        let hullRing = hasCompletePartition && partitionRings[gIdx] ? partitionRings[gIdx] : null;
        if (!hullRing && hasCompletePartition) {
          hullRing = convexHullClosedRing(pts);
        }
        if (!hullRing && pts.length) {
          let minX = Infinity;
          let maxX = -Infinity;
          let minY = Infinity;
          let maxY = -Infinity;
          pts.forEach((p) => {
            minX = Math.min(minX, p[0]);
            maxX = Math.max(maxX, p[0]);
            minY = Math.min(minY, p[1]);
            maxY = Math.max(maxY, p[1]);
          });
          const pad = 10;
          if (Number.isFinite(minX) && maxX > minX && maxY > minY) {
            hullRing = [
              [minX - pad, minY - pad],
              [maxX + pad, minY - pad],
              [maxX + pad, maxY + pad],
              [minX - pad, maxY + pad],
              [minX - pad, minY - pad]
            ];
          }
        }
        if (hullRing && hullRing.length >= 4) {
          const hf = new ol.Feature({
            geometry: new ol.geom.Polygon([hullRing])
          });
          hf.setStyle(new ol.style.Style({
            stroke: new ol.style.Stroke({ color: V4_SUBPOLY_STROKE, width: 3 }),
            fill: new ol.style.Fill({ color: V4_SUBPOLY_FILL }),
            text: new ol.style.Text({
              text: `Teil ${gIdx + 1} (${cells.length} Zellen)`,
              font: "bold 12px Arial",
              fill: new ol.style.Fill({ color: "#5a4800" }),
              stroke: new ol.style.Stroke({ color: "#ffffff", width: 3 })
            })
          }));
          subPolygonV4Source.addFeature(hf);
        }
      });
      if (Array.isArray(vizBlocks)) {
        vizBlocks.forEach((b) => {
          const sec = b.cellCount ? ` ${b.cellCount} Zellen` : "";
          const label = `Höhe ∥ ${Math.round(normalizeAngle180(b.angleDeg))}°${sec}`;
          drawSubPolyHeightDirectionPair(
            b.center,
            b.angleDeg,
            Math.max(28, b.halfLen * 1.05),
            V4_SUBPOLY_STROKE,
            subPolygonV4Source,
            label,
            14
          );
        });
      }
      applySubPolygonV4LayerVisibility();
      refreshSubPolygonV4ToggleButtonState();
    }

    function buildRectangleHeightGuidedFlightPathV4Core(opts) {
      const o = (opts && typeof opts === "object") ? opts : {};
      const v5AggressiveChordMode = !!o.v5AggressiveChord;
      const uniformParallelLanes = o.uniformParallelLanes !== false;
      const adaptiveEComplex = !!o.adaptiveEComplex;
      if (
        !Array.isArray(flightStartLonLat) || flightStartLonLat.length !== 2 ||
        !Array.isArray(flightPolygonVertices) || flightPolygonVertices.length < 3
      ) {
        return null;
      }
      const sp = readFlightSpacingContextFromUi();
      const {
        borderMeters,
        maxBorderDistance,
        spacingMin,
        spacingMax,
        effectiveTrackDistance,
        trackDistanceBase,
        parallelTolerancePct,
        swathWidthMeters
      } = sp;
      // D: nominaler Bahnabstand aus der UI (nicht effectiveTrackDistance) — weniger „zu enge“ Bänder und Doppelüberdeckung.
      const v4LaneBandSpacing = Math.max(5, Number(trackDistanceBase) || Number(effectiveTrackDistance) || 5);
      // Rand-Ende exakt nach Randregel (kein zusätzlicher 0.5*Bahnabstand-Inset).
      const v4ChordEndInsetM = Math.max(0, Number(borderMeters) || 0);
      // Spitze Ecken / Polygon-Scheitel: Rand-Inset etwas reduzieren, damit die Sehne näher an den Rand reicht
      // und die Sensor-Halbbreite die obere Ecke noch trifft (sonst „Loch“ unter der Ecke).
      const swHalfForChordInset = Number.isFinite(swathWidthMeters) && swathWidthMeters > 0 ? swathWidthMeters * 0.5 : 0;
      let v4ChordInsetRelaxM = 0;
      if (swHalfForChordInset > 0 && v4ChordEndInsetM > 0) {
        v4ChordInsetRelaxM = Math.min(v4ChordEndInsetM * 0.24, swHalfForChordInset * 0.9);
      }
      if (adaptiveEComplex && swHalfForChordInset > 0 && v4ChordEndInsetM > 0) {
        v4ChordInsetRelaxM += Math.min(v4ChordEndInsetM * 0.22, swHalfForChordInset * 0.72);
      }
      const v4ChordEndInsetEffective = Math.max(0, v4ChordEndInsetM - v4ChordInsetRelaxM);

      const polygonGeom = polygonGeometryFromVertices();
      if (!polygonGeom) {
        return null;
      }
      // Für Unterpolygone volle Flächenabdeckung priorisieren:
      // keine zusätzliche Rand-Exklusion bei der Zellbildung.
      const cells = buildSectorCellsFromPolygon(polygonGeom, v4LaneBandSpacing, 0);
      if (!cells.length) {
        clearSubPolygonV4Overlay();
        return null;
      }
      // Feineres Raster für Lückenprüfung (Abdeckung darf nicht liegen bleiben).
      const coverageProbeSpacing = adaptiveEComplex
        ? Math.max(5, v4LaneBandSpacing * 0.36)
        : Math.max(5, v4LaneBandSpacing * 0.5);
      const coverageProbeCells = buildSectorCellsFromPolygon(
        polygonGeom,
        coverageProbeSpacing,
        0
      );

      const rawGroups = buildV4GroupsFromRectangles(cells);
      if (!rawGroups.length) {
        clearSubPolygonV4Overlay();
        return null;
      }
      const totalCells = rawGroups.reduce((sum, g) => sum + (((g && g.cells) ? g.cells.length : 0)), 0);
      const minRatio = Math.max(0.01, SECTOR_MIN_AREA_RATIO);
      let filteredGroups = rawGroups.filter((g) => {
        const count = Array.isArray(g.cells) ? g.cells.length : 0;
        return totalCells > 0 && (count / totalCells) >= minRatio;
      });
      if (!filteredGroups.length) {
        filteredGroups = rawGroups;
      }
      filteredGroups = mergeV4GroupsBySimilarDirection(filteredGroups, 22);
      lastSectorDirectionGroups = filteredGroups;

      // D: etwas längere Bahnanker → geradere Streifen je Block; Kreuzungen weiter über Randrouting begrenzt.
      const runtimeLineExtensionRatio = Math.min(getLineExtensionInfluenceRatio(), 0.58) * 0.38;
      const blocks = [];
      filteredGroups.forEach((group, idx) => {
        const gc = Array.isArray(group.cells) ? group.cells : [];
        if (!gc.length) {
          return;
        }
        let cx = 0;
        let cy = 0;
        gc.forEach((cell) => {
          cx += Number(cell.center[0]);
          cy += Number(cell.center[1]);
        });
        cx /= gc.length;
        cy /= gc.length;
        let minX = Infinity;
        let maxX = -Infinity;
        let minY = Infinity;
        let maxY = -Infinity;
        gc.forEach((cell) => {
          const x = Number(cell.center[0]);
          const y = Number(cell.center[1]);
          minX = Math.min(minX, x);
          maxX = Math.max(maxX, x);
          minY = Math.min(minY, y);
          maxY = Math.max(maxY, y);
        });
        const span = Math.max(maxX - minX, maxY - minY, v4LaneBandSpacing * 2);
        const directionAngle = normalizeAngle180(Number(group.meanAngle));
        const lanes = buildParallelLanesForCells(
          gc,
          directionAngle,
          v4LaneBandSpacing,
          runtimeLineExtensionRatio,
          uniformParallelLanes
        );
        if (!Array.isArray(lanes) || !lanes.length) {
          return;
        }
        blocks.push({
          blockIdx: idx,
          compId: idx,
          directionAngle,
          center: [cx, cy],
          lanes,
          vizHalfLen: Math.max(28, span * 0.45),
          cellCount: gc.length
        });
      });

      if (!blocks.length) {
        clearSubPolygonV4Overlay();
        return null;
      }

      const startMap = lonLatToMapCoord(Number(flightStartLonLat[0]), Number(flightStartLonLat[1]));
      const remaining = blocks.map((b) => ({ ...b }));
      const orderedBlocks = [];
      let currentCenter = [Number(startMap[0]), Number(startMap[1])];
      while (remaining.length) {
        let bestPos = 0;
        let bestDist = Number.POSITIVE_INFINITY;
        for (let i = 0; i < remaining.length; i += 1) {
          const d = mapDistance(currentCenter, remaining[i].center);
          if (d < bestDist) {
            bestDist = d;
            bestPos = i;
          }
        }
        const next = remaining.splice(bestPos, 1)[0];
        orderedBlocks.push(next);
        currentCenter = [Number(next.center[0]), Number(next.center[1])];
      }

      const mainRingClosed = polygonGeom.getCoordinates()[0];
      const partitionRings = buildV4VoronoiPartitionClosedRings(mainRingClosed, filteredGroups);

      const start = [Number(flightStartLonLat[0]), Number(flightStartLonLat[1])];
      const routed = [start];
      const startInside = pointInPolygonLonLat(start, flightPolygonVertices);
      let routedSegments = 0;
      /** D: je eine gerade Bahnpolyline [i0..i1] im Pfad (für Lücken-Reparatur nur Endverlängerung). */
      const v4ExtendableLaneLegs = [];

      function mapPairToLonLat3857(xy) {
        const ll = ol.proj.transform([Number(xy[0]), Number(xy[1])], "EPSG:3857", "EPSG:4326");
        return [Number(ll[0]), Number(ll[1])];
      }

      let prevBlockAngle = null;

      orderedBlocks.forEach((block, bi) => {
        const sectorGeom = buildV4SectorClipGeometry3857(block.compId, partitionRings, filteredGroups, polygonGeom);
        const ang = (normalizeAngle180(Number(block.directionAngle)) * Math.PI) / 180;
        const ux = Math.cos(ang);
        const uy = Math.sin(ang);
        const lanes = Array.isArray(block.lanes) ? block.lanes : [];
        if (!lanes.length) {
          return;
        }

        lanes.forEach((lane, laneIdx) => {
          if (!lane || !lane.startCell || !lane.endCell) {
            return;
          }
          const useAnchor = Array.isArray(lane.anchorMap) && lane.anchorMap.length >= 2
            && Number.isFinite(Number(lane.anchorMap[0]))
            && Number.isFinite(Number(lane.anchorMap[1]));
          let sx = (Number(lane.startCell.center[0]) + Number(lane.endCell.center[0])) * 0.5;
          let sy = (Number(lane.startCell.center[1]) + Number(lane.endCell.center[1])) * 0.5;
          if (useAnchor) {
            sx = Number(lane.anchorMap[0]);
            sy = Number(lane.anchorMap[1]);
          }
          let chordSec = chordMapThroughPointInPolygon3857(sectorGeom, sx, sy, ux, uy);
          if (!chordSec) {
            chordSec = chordMapThroughPointInPolygon3857(polygonGeom, sx, sy, ux, uy);
          }
          if (!chordSec && useAnchor) {
            sx = (Number(lane.startCell.center[0]) + Number(lane.endCell.center[0])) * 0.5;
            sy = (Number(lane.startCell.center[1]) + Number(lane.endCell.center[1])) * 0.5;
            chordSec = chordMapThroughPointInPolygon3857(sectorGeom, sx, sy, ux, uy);
            if (!chordSec) {
              chordSec = chordMapThroughPointInPolygon3857(polygonGeom, sx, sy, ux, uy);
            }
          }
          if (!chordSec) {
            return;
          }
          const shortSec = shortenChordMapEndsMeters3857(chordSec.p0, chordSec.p1, v4ChordEndInsetEffective);
          const pa = shortSec[0];
          const pb = shortSec[1];
          const paU = (pa[0] * ux) + (pa[1] * uy);
          const pbU = (pb[0] * ux) + (pb[1] * uy);
          let startMapChord = paU <= pbU ? pa : pb;
          let endMapChord = paU <= pbU ? pb : pa;
          if (laneIdx % 2 === 1) {
            const tmp = startMapChord;
            startMapChord = endMapChord;
            endMapChord = tmp;
          }
          const lastLL = routed[routed.length - 1];
          const curMap = lonLatToMapCoord(Number(lastLL[0]), Number(lastLL[1]));
          if (mapDistance(curMap, endMapChord) < mapDistance(curMap, startMapChord)) {
            const sw = startMapChord;
            startMapChord = endMapChord;
            endMapChord = sw;
          }
          if (v5AggressiveChordMode) {
            const swathHalfEarly = Number.isFinite(swathWidthMeters) && swathWidthMeters > 0 ? swathWidthMeters * 0.5 : 0;
            const covAgg = swathHalfEarly > 0
              ? Math.max(4, swathHalfEarly * 0.91)
              : Math.max(4, Math.min(spacingMax * 0.36, effectiveTrackDistance * 0.36));
            const probes = (Array.isArray(coverageProbeCells) && coverageProbeCells.length)
              ? coverageProbeCells
              : cells;
            const grown = v5AggressivelyExtendChordEndpoints3857(
              startMapChord,
              endMapChord,
              flightPolygonVertices,
              probes,
              covAgg,
              28
            );
            startMapChord = grown[0];
            endMapChord = grown[1];
          }
          const startLL = mapPairToLonLat3857(startMapChord);
          const endLL = mapPairToLonLat3857(endMapChord);
          if (pointsEqualLonLat(startLL, endLL)) {
            return;
          }

          const from0 = routed[routed.length - 1];
          if (routed.length === 1 && !startInside) {
            routed.push(startLL);
            routedSegments += 1;
          } else {
            let jumped = false;
            if (
              laneIdx === 0
              && bi > 0
              && prevBlockAngle !== null
              && angleDifference180(prevBlockAngle, block.directionAngle) <= V4_DIRECT_CONNECT_MAX_ANGLE_DEG
              && segmentInsidePolygonLonLat(from0, startLL, flightPolygonVertices)
            ) {
              routed.push(startLL);
              routedSegments += 1;
              jumped = true;
            }
            if (!jumped) {
              const n0 = v4AppendRouteOrStraight(from0, startLL, routed);
              if (n0 > 0) {
                routedSegments += 1;
              }
            }
          }

          const from1 = routed[routed.length - 1];
          if (!pointsEqualLonLat(from1, endLL)) {
            if (segmentInsidePolygonLonLat(from1, endLL, flightPolygonVertices)) {
              const i0 = routed.length - 1;
              routed.push([Number(endLL[0]), Number(endLL[1])]);
              const i1 = routed.length - 1;
              v4ExtendableLaneLegs.push({
                i0,
                i1,
                p0Map: [Number(startMapChord[0]), Number(startMapChord[1])],
                p1Map: [Number(endMapChord[0]), Number(endMapChord[1])]
              });
            } else {
              const n1 = v4AppendRouteOrStraight(from1, endLL, routed);
              if (n1 > 0) {
                routedSegments += 1;
              }
            }
          }
        });
        prevBlockAngle = block.directionAngle;
      });

      // Lücken-Reparatur D: keine neuen Verbinder/Umrouten — nur Verlängerung bestehender gerader Bahnpärchen (Streifenenden).

      const probeCellsPrimary = (Array.isArray(coverageProbeCells) && coverageProbeCells.length)
        ? coverageProbeCells
        : cells;
      const swathHalfV4 = Number.isFinite(swathWidthMeters) && swathWidthMeters > 0 ? swathWidthMeters * 0.5 : 0;
      const coverageThresholdRepair = swathHalfV4 > 0
        ? Math.max(4, swathHalfV4 * 0.91)
        : Math.max(4, Math.min(spacingMax * 0.36, effectiveTrackDistance * 0.36));
      const gapDetectThreshold = adaptiveEComplex
        ? coverageThresholdRepair * 0.94
        : coverageThresholdRepair;
      const maxGapPasses = adaptiveEComplex ? 14 : 4;
      const perPassRepairs = Math.min(
        adaptiveEComplex ? 14 : 9,
        Math.max(
          adaptiveEComplex ? 6 : 4,
          Math.round((Array.isArray(probeCellsPrimary) ? probeCellsPrimary.length : 0) * (adaptiveEComplex ? 0.048 : 0.032))
        )
      );

      for (let gapPass = 0; gapPass < maxGapPasses; gapPass += 1) {
        if (!v4ExtendableLaneLegs.length) {
          break;
        }
        const probeUse = (gapPass === maxGapPasses - 1) ? cells : probeCellsPrimary;
        let gapCandidates = (Array.isArray(probeUse) ? probeUse : [])
          .map((cell) => {
            const c = cell && Array.isArray(cell.center) ? cell.center : null;
            if (!c) {
              return null;
            }
            const d = v4DistanceToLaneLegs3857(v4ExtendableLaneLegs, c);
            return d > gapDetectThreshold ? { center: [Number(c[0]), Number(c[1])], dist: d } : null;
          })
          .filter(Boolean);
        // Polygon-Ecken (z. B. oberer Scheitel): oft fehlen dort Raster-Zellen — als Lücken-Ziele mitführen.
        if (Array.isArray(flightPolygonVertices) && flightPolygonVertices.length >= 3) {
          flightPolygonVertices.forEach((v) => {
            if (!Array.isArray(v) || v.length < 2) {
              return;
            }
            const m = lonLatToMapCoord(Number(v[0]), Number(v[1]));
            const d = v4DistanceToLaneLegs3857(v4ExtendableLaneLegs, m);
            if (d > gapDetectThreshold * 0.97) {
              gapCandidates.push({ center: [Number(m[0]), Number(m[1])], dist: d });
            }
          });
        }
        gapCandidates.sort((a, b) => b.dist - a.dist);
        if (!gapCandidates.length) {
          break;
        }
        const clusterR = Math.max(
          adaptiveEComplex ? 10 : 14,
          Math.min(adaptiveEComplex ? 32 : 38, v4LaneBandSpacing * (adaptiveEComplex ? 0.42 : 0.52))
        );
        const topGaps = v4PickSparseGapTargets3857(gapCandidates, perPassRepairs, clusterR);
        if (!topGaps.length) {
          break;
        }
        topGaps.forEach((gItem) => {
          const mapPoint = gItem && Array.isArray(gItem.center) ? gItem.center : null;
          if (!mapPoint || !polygonGeom.intersectsCoordinate(mapPoint)) {
            return;
          }
          let bestLane = null;
          let bestD = Number.POSITIVE_INFINITY;
          for (let li = 0; li < v4ExtendableLaneLegs.length; li += 1) {
            const Ln = v4ExtendableLaneLegs[li];
            if (!Ln || !Ln.p0Map || !Ln.p1Map) {
              continue;
            }
            const d = pointToSegmentDistanceMap(mapPoint, Ln.p0Map, Ln.p1Map);
            if (d < bestD) {
              bestD = d;
              bestLane = Ln;
            }
          }
          if (!bestLane) {
            return;
          }
          let extended = v4TryExtendLaneLegForGap3857(
            bestLane,
            mapPoint,
            gapDetectThreshold,
            flightPolygonVertices
          );
          if (!extended && adaptiveEComplex) {
            extended = v4TryExtendAdaptiveInteriorAdjacentEnd3857(
              bestLane,
              mapPoint,
              gapDetectThreshold,
              flightPolygonVertices
            );
          }
          if (extended) {
            v4ApplyLaneLegMapToRouted3857(bestLane, routed);
          }
        });
      }

      if (routed.length < 2) {
        clearSubPolygonV4Overlay();
        return null;
      }

      const vizBlocks = orderedBlocks.map((b) => ({
        compId: b.compId,
        compDisplay: (Number(b.compId) || 0) + 1,
        center: b.center,
        angleDeg: b.directionAngle,
        halfLen: b.vizHalfLen || 40,
        cellCount: b.cellCount || 0
      }));
      paintV4SectorSubPolygonsOrange(filteredGroups, vizBlocks);

      return {
        path: insertProfileSupportPoints(routed, PROFILE_SUPPORT_HEIGHT_THRESHOLD_M),
        rectangleCount: filteredGroups.length,
        rectangleHeightCount: filteredGroups.length,
        subPolygonCount: orderedBlocks.length,
        cellCount: cells.length,
        trackDistance: effectiveTrackDistance,
        parallelTolerancePct,
        trackSpacingMin: spacingMin,
        trackSpacingMax: spacingMax,
        maxBorderDistance,
        usedBorderDistance: borderMeters,
        routedSegments
      };
    }

    function buildRectangleHeightGuidedFlightPathV4() {
      return buildRectangleHeightGuidedFlightPathV4Core({
        v5AggressiveChord: false,
        uniformParallelLanes: true,
        adaptiveEComplex: false
      });
    }

    function buildRectangleHeightGuidedFlightPathV5() {
      return buildRectangleHeightGuidedFlightPathV4Core({
        v5AggressiveChord: true,
        uniformParallelLanes: true,
        adaptiveEComplex: false
      });
    }

    /** Adaptiv E · komplex: wie F (aggressive Sehnen) plus feinere Lückenbehandlung nur für diesen Modus. */
    function buildRectangleHeightGuidedFlightPathEAdaptiveComplex() {
      return buildRectangleHeightGuidedFlightPathV4Core({
        v5AggressiveChord: true,
        uniformParallelLanes: true,
        adaptiveEComplex: true
      });
    }

    async function runRectangleAnalysis() {
      const polygonGeom = polygonGeometryFromVertices();
      if (!polygonGeom) {
        statusEl.textContent = "Polygon mit mindestens 3 Punkten erforderlich.";
        clearRectangleAnalysis();
        return;
      }
      const searchScale = getPolygonDiameterMeters();
      if (!Number.isFinite(searchScale) || searchScale <= 0) {
        statusEl.textContent = "Polygon zu klein oder ungültig für Formanalyse.";
        clearRectangleAnalysis();
        return;
      }
      setBusy(true);
      try {
        statusEl.textContent = "Suche grösstes Innen-Rechteck und Restflächen-Rechtecke...";
        clearRectangleAnalysis();
        const usedRectanglesRaw = [];
        const maxCount = getCountValue(rectangleCountInput, 4, 1, 10); // default unchanged
        for (let i = 0; i < maxCount; i += 1) {
          const rect = searchLargestRectangle(polygonGeom, usedRectanglesRaw, searchScale);
          if (!rect || rectangleArea(rect) < 50) {
            break;
          }
          usedRectanglesRaw.push(rect);
        }
        const sectorSplit = filterSectorsAgainstLargest(usedRectanglesRaw, SECTOR_MIN_AREA_RATIO);
        const usedRectangles = sectorSplit.sectors;
        for (let s = 0; s < usedRectangles.length; s += 1) {
          drawRectangle(usedRectangles[s], s + 1);
        }
        lastRectangles = usedRectangles;
        rectangleAnalysisEnabled = usedRectangles.length > 0;
        setToggleButtonState(
          runRectangleAnalysisBtn,
          rectangleAnalysisEnabled,
          "Polygon-Formanalyse zeigen",
          "Polygon-Formanalyse verbergen"
        );
        rectangleInnerLineSource.clear();
        rectangleInnerLinesEnabled = false;
        setToggleButtonState(
          drawRectangleInnerLinesBtn,
          false,
          "Höhenrichtungslinie je Sektor (>20%) zeigen",
          "Höhenrichtungslinie je Sektor (>20%) verbergen"
        );
        const pct = Math.round(SECTOR_MIN_AREA_RATIO * 100);
        statusEl.textContent = `Formanalyse: ${usedRectangles.length} Rechtecke angezeigt (${sectorSplit.dropped} unter ${pct}% der grössten Fläche verworfen, Roh ${sectorSplit.raw}).`;
        saveFieldSettings();
        applyHelperLayersVisibility();
      } catch (_) {
        statusEl.textContent = "Fehler bei der Polygon-Formanalyse.";
      } finally {
        setBusy(false);
      }
    }

    function generateGridCandidates(spacingMeters) {
      if (!Array.isArray(polygonVertices) || polygonVertices.length < 3) {
        return [];
      }
      const ringCoords = polygonVertices.map((v) => lonLatToMapCoord(v[0], v[1]));
      const polygonGeom = new ol.geom.Polygon([[...ringCoords, ringCoords[0]]]);
      const extent = polygonGeom.getExtent();
      const candidates = [];
      for (let x = extent[0]; x <= extent[2]; x += spacingMeters) {
        for (let y = extent[1]; y <= extent[3]; y += spacingMeters) {
          if (!polygonGeom.intersectsCoordinate([x, y])) {
            continue;
          }
          const lonLat = ol.proj.transform([x, y], "EPSG:3857", "EPSG:4326");
          candidates.push([Number(lonLat[0]), Number(lonLat[1])]);
        }
      }
      return candidates;
    }

    async function setAnalysisPoints() {
      if (spacingInput && spacingInput.dataset.userEdited !== "1") {
        applyAutoSpacingDefault();
      }
      const spacingRaw = Number(spacingInput ? spacingInput.value : 20);
      const spacingMeters = Number.isFinite(spacingRaw) ? Math.max(5, spacingRaw) : 20;
      if (!Array.isArray(polygonVertices) || polygonVertices.length < 3) {
        statusEl.textContent = "Polygon mit mindestens 3 Punkten erforderlich.";
        analysisSource.clear();
        return;
      }

      const candidates = generateGridCandidates(spacingMeters);
      if (!candidates.length) {
        statusEl.textContent = "Keine Kontrollpunkte im Feld gefunden.";
        analysisSource.clear();
        return;
      }
      setBusy(true);
      try {
        statusEl.textContent = `Berechne Höhen für ${candidates.length} Kontrollpunkte...`;
        const terrainMap = await fetchTerrainElevations(
          candidates.map((p) => ({ lon: p[0], lat: p[1] }))
        );
        const pointData = candidates.map((p) => {
          // Same terrain request flow as flight points.
          const heightMetersAsl = getElevationAt(terrainMap, p[0], p[1]);
          return { lon: p[0], lat: p[1], asl: Number.isFinite(heightMetersAsl) ? heightMetersAsl : NaN };
        });
        lastPointData = pointData;
        controlPointsEnabled = true;
        setToggleButtonState(
          togglePointsBtn,
          true,
          "Kontrollpunkte im Feld setzen",
          "Kontrollpunkte im Feld entfernen"
        );
        renderPoints(pointData);
        refreshDirectionLines();
        statusEl.textContent = `${pointData.length} Kontrollpunkte gesetzt (Abstand ${spacingMeters}m).`;
        saveFieldSettings();
      } catch (err) {
        statusEl.textContent = "Fehler bei der Höhenberechnung der Kontrollpunkte.";
      } finally {
        setBusy(false);
      }
    }

    if (togglePointsBtn) {
      setToggleButtonState(
        togglePointsBtn,
        false,
        "Kontrollpunkte im Feld setzen",
        "Kontrollpunkte im Feld entfernen"
      );
      togglePointsBtn.addEventListener("click", () => {
        if (controlPointsEnabled) {
          analysisSource.clear();
          lastPointData = [];
          controlPointsEnabled = false;
          setToggleButtonState(
            togglePointsBtn,
            false,
            "Kontrollpunkte im Feld setzen",
            "Kontrollpunkte im Feld entfernen"
          );
          directionLinesEnabled = false;
          clearDirectionLines();
          setToggleButtonState(toggleDirectionBtn, false, "Höhenrichtungslinien zeigen", "Höhenrichtungslinien verbergen");
          statusEl.textContent = "Kontrollpunkte entfernt.";
          saveFieldSettings();
          return;
        }
        void setAnalysisPoints();
      });
    }

    if (spacingInput) {
      spacingInput.addEventListener("input", () => {
        spacingInput.dataset.userEdited = "1";
        saveFieldSettings();
      });
      if (!readFieldSettings()) {
        applyAutoSpacingDefault();
      }
    }
    if (directionBandCountInput) {
      directionBandCountInput.addEventListener("input", saveFieldSettings);
    }
    if (rectangleCountInput) {
      rectangleCountInput.addEventListener("input", saveFieldSettings);
    }
    function handleLineExtensionChange(rawValue) {
      const pct = Math.max(0, Math.min(100, Number(rawValue) || 0));
      lineExtensionRatio = pct / 100;
      updateLineExtensionUi();
      saveFieldSettings();
      const pathCount = (typeof window.__rkrkFlightPathPointCount === "function")
        ? window.__rkrkFlightPathPointCount()
        : 0;
      if (
        typeof calculateFlightPath === "function"
        && typeof calcFlightPathBtnEl !== "undefined"
        && calcFlightPathBtnEl
        && (!calcFlightPathBtnEl.disabled || pathCount > 1)
      ) {
        calculateFlightPath();
      }
    }
    if (lineExtensionSliderInput) {
      lineExtensionSliderInput.addEventListener("input", () => {
        handleLineExtensionChange(lineExtensionSliderInput.value);
      });
    }
    if (fpLineExtensionEl) {
      fpLineExtensionEl.addEventListener("input", () => {
        handleLineExtensionChange(fpLineExtensionEl.value);
      });
    }
    if (toggleHelperParamsBtn) {
      toggleHelperParamsBtn.addEventListener("click", () => {
        helperParamsVisible = !helperParamsVisible;
        applyHelperParamsVisibility();
        saveFieldSettings();
      });
    }
    if (toggleHelperLayersBtn) {
      toggleHelperLayersBtn.addEventListener("click", () => {
        helperLayersVisible = !helperLayersVisible;
        applyHelperLayersVisibility();
        saveFieldSettings();
      });
    }
    if (toggleRectangleFormMapBtn) {
      toggleRectangleFormMapBtn.addEventListener("click", () => {
        rectangleFormMapVisible = !rectangleFormMapVisible;
        applyHelperLayersVisibility();
        saveFieldSettings();
        refreshRectangleFormMapToggleButtonState();
      });
    }
    if (toggleDirectionBtn) {
      setToggleButtonState(
        toggleDirectionBtn,
        false,
        "Höhenrichtungslinien zeigen",
        "Höhenrichtungslinien verbergen"
      );
      toggleDirectionBtn.addEventListener("click", () => {
        directionLinesEnabled = !directionLinesEnabled;
        setToggleButtonState(
          toggleDirectionBtn,
          directionLinesEnabled,
          "Höhenrichtungslinien zeigen",
          "Höhenrichtungslinien verbergen"
        );
        refreshDirectionLines();
      });
    }

    if (toggleSubPolygonV4OverlayBtn) {
      toggleSubPolygonV4OverlayBtn.addEventListener("click", () => {
        if (!isFlightVariantV4Selected()) {
          return;
        }
        if (!(subPolygonV4Source && subPolygonV4Source.getFeatures().length > 0)) {
          return;
        }
        subPolygonV4OverlayEnabled = !subPolygonV4OverlayEnabled;
        applySubPolygonV4LayerVisibility();
        refreshSubPolygonV4ToggleButtonState();
        saveFieldSettings();
      });
    }

    if (runRectangleAnalysisBtn) {
      setToggleButtonState(
        runRectangleAnalysisBtn,
        false,
        "Polygon-Formanalyse zeigen",
        "Polygon-Formanalyse verbergen"
      );
      runRectangleAnalysisBtn.addEventListener("click", () => {
        if (rectangleAnalysisEnabled) {
          clearRectangleAnalysis();
          statusEl.textContent = "Formanalyse ausgeblendet.";
          saveFieldSettings();
          return;
        }
        void runRectangleAnalysis();
      });
    }
    if (drawRectangleInnerLinesBtn) {
      setToggleButtonState(
        drawRectangleInnerLinesBtn,
        false,
        "Höhenrichtungslinie je Sektor (>20%) zeigen",
        "Höhenrichtungslinie je Sektor (>20%) verbergen"
      );
      drawRectangleInnerLinesBtn.addEventListener("click", () => {
        if (rectangleInnerLinesEnabled) {
          rectangleInnerLineSource.clear();
          rectangleInnerLinesEnabled = false;
          setToggleButtonState(
            drawRectangleInnerLinesBtn,
            false,
            "Höhenrichtungslinie je Sektor (>20%) zeigen",
            "Höhenrichtungslinie je Sektor (>20%) verbergen"
          );
          statusEl.textContent = "Sektor-Höhenrichtungslinien ausgeblendet.";
          saveFieldSettings();
          applyHelperLayersVisibility();
          return;
        }
        drawRectangleHeightLinesFromControlPoints();
      });
    }

    if (toggleSectorDirectionGroupsBtn) {
      setToggleButtonState(
        toggleSectorDirectionGroupsBtn,
        false,
        "Sektoren nach Höhenrichtung zeigen",
        "Sektoren nach Höhenrichtung verbergen"
      );
      toggleSectorDirectionGroupsBtn.addEventListener("click", () => {
        if (sectorDirectionGroupsEnabled) {
          clearSectorDirectionGroups();
          statusEl.textContent = "Sektor-Gruppierung nach Höhenrichtung ausgeblendet.";
          saveFieldSettings();
          return;
        }
        const ok = renderSectorDirectionGroups();
        if (!ok) {
          statusEl.textContent = "Keine Sektor-Gruppierung möglich. Zuerst Polygon-Formanalyse ausführen.";
          return;
        }
        saveFieldSettings();
      });
    }

    function toMapCoordinateFromLonLat(lonLat) {
      return lonLatToMapCoord(Number(lonLat[0]), Number(lonLat[1]));
    }

    function mapDistance(a, b) {
      const dx = Number(a[0]) - Number(b[0]);
      const dy = Number(a[1]) - Number(b[1]);
      return Math.sqrt((dx * dx) + (dy * dy));
    }

    function localRectCoords(mapCoord, rect) {
      const cx = Number(rect.center[0]);
      const cy = Number(rect.center[1]);
      const dx = Number(mapCoord[0]) - cx;
      const dy = Number(mapCoord[1]) - cy;
      const c = Math.cos(rect.angleRad);
      const s = Math.sin(rect.angleRad);
      return {
        x: (dx * c) + (dy * s),
        y: (-dx * s) + (dy * c)
      };
    }

    function chooseRectangleVisitOrder(pointsByRect, startMapCoord) {
      const remaining = Object.keys(pointsByRect)
        .map((k) => Number(k))
        .filter((idx) => Number.isInteger(idx) && idx >= 0 && idx < lastRectangles.length && Array.isArray(pointsByRect[idx]) && pointsByRect[idx].length);
      const order = [];
      let current = [Number(startMapCoord[0]), Number(startMapCoord[1])];
      while (remaining.length) {
        let bestPos = 0;
        let bestDist = Number.POSITIVE_INFINITY;
        for (let i = 0; i < remaining.length; i += 1) {
          const rectIdx = remaining[i];
          const rectCenter = lastRectangles[rectIdx].center;
          const d = mapDistance(current, rectCenter);
          if (d < bestDist) {
            bestDist = d;
            bestPos = i;
          }
        }
        const nextRectIdx = remaining.splice(bestPos, 1)[0];
        order.push(nextRectIdx);
        current = [Number(lastRectangles[nextRectIdx].center[0]), Number(lastRectangles[nextRectIdx].center[1])];
      }
      return order;
    }

    function orderPointsInsideRectangle(rect, pointInfos, trackDistanceMeters) {
      if (!Array.isArray(pointInfos) || !pointInfos.length) {
        return [];
      }
      const bandSize = Math.max(3, Number(trackDistanceMeters) || 10);
      const enriched = pointInfos.map((info) => {
        const local = localRectCoords(info.mapCoord, rect);
        const band = Math.round(local.y / bandSize);
        return {
          ...info,
          localX: local.x,
          localY: local.y,
          band
        };
      });
      enriched.sort((a, b) => {
        if (a.band !== b.band) {
          return a.band - b.band;
        }
        if (a.band % 2 === 0) {
          return a.localX - b.localX;
        }
        return b.localX - a.localX;
      });
      return enriched;
    }

    function buildRectangleSequencedFlightPath(basePath) {
      if (!Array.isArray(basePath) || basePath.length < 2) {
        return basePath;
      }
      if (!Array.isArray(lastRectangles) || !lastRectangles.length) {
        return basePath;
      }
      const startLonLat = [Number(basePath[0][0]), Number(basePath[0][1])];
      const startMap = toMapCoordinateFromLonLat(startLonLat);
      const trackDistance = readFlightSpacingContextFromUi().effectiveTrackDistance;

      const pointsByRect = {};
      const unassigned = [];
      for (let i = 1; i < basePath.length; i += 1) {
        const lonLat = [Number(basePath[i][0]), Number(basePath[i][1])];
        const mapCoord = toMapCoordinateFromLonLat(lonLat);
        const info = { lonLat, mapCoord };
        let assigned = false;
        for (let r = 0; r < lastRectangles.length; r += 1) {
          if (pointInRectangle(mapCoord, lastRectangles[r])) {
            if (!pointsByRect[r]) pointsByRect[r] = [];
            pointsByRect[r].push(info);
            assigned = true;
            break;
          }
        }
        if (!assigned) {
          unassigned.push(info);
        }
      }

      const rectOrder = chooseRectangleVisitOrder(pointsByRect, startMap);
      const orderedPoints = [];

      function clonePointInfo(info) {
        return {
          lonLat: [Number(info.lonLat[0]), Number(info.lonLat[1])],
          mapCoord: [Number(info.mapCoord[0]), Number(info.mapCoord[1])]
        };
      }

      function centerFromPointInfos(infos) {
        if (!infos.length) {
          return [0, 0];
        }
        let sx = 0;
        let sy = 0;
        infos.forEach((info) => {
          sx += Number(info.mapCoord[0]);
          sy += Number(info.mapCoord[1]);
        });
        return [sx / infos.length, sy / infos.length];
      }

      function buildNearestNeighborOrder(infos, startMapCoord) {
        if (!infos.length) {
          return [];
        }
        const remaining = infos.map(clonePointInfo);
        const ordered = [];
        let current = [Number(startMapCoord[0]), Number(startMapCoord[1])];
        while (remaining.length) {
          let bestPos = 0;
          let bestDist = Number.POSITIVE_INFINITY;
          for (let i = 0; i < remaining.length; i += 1) {
            const d = mapDistance(current, remaining[i].mapCoord);
            if (d < bestDist) {
              bestDist = d;
              bestPos = i;
            }
          }
          const next = remaining.splice(bestPos, 1)[0];
          ordered.push(next);
          current = next.mapCoord;
        }
        return ordered;
      }

      function clusterUnassignedPoints(infos, threshold) {
        const pending = infos.map(clonePointInfo);
        const clusters = [];
        while (pending.length) {
          const seed = pending.pop();
          const cluster = [seed];
          let changed = true;
          while (changed) {
            changed = false;
            for (let i = pending.length - 1; i >= 0; i -= 1) {
              const candidate = pending[i];
              let near = false;
              for (let j = 0; j < cluster.length; j += 1) {
                if (mapDistance(cluster[j].mapCoord, candidate.mapCoord) <= threshold) {
                  near = true;
                  break;
                }
              }
              if (near) {
                cluster.push(candidate);
                pending.splice(i, 1);
                changed = true;
              }
            }
          }
          clusters.push(cluster);
        }
        return clusters;
      }

      const blocks = [];
      rectOrder.forEach((rectIdx, sequenceIdx) => {
        const rect = lastRectangles[rectIdx];
        const infos = orderPointsInsideRectangle(rect, pointsByRect[rectIdx] || [], trackDistance).map(clonePointInfo);
        if (!infos.length) {
          return;
        }
        blocks.push({
          id: `rect-${rectIdx}`,
          type: "rectangle",
          sequenceIdx,
          center: [Number(rect.center[0]), Number(rect.center[1])],
          points: infos
        });
      });

      const unassignedThreshold = Math.max(15, trackDistance * 2.5);
      const looseClusters = clusterUnassignedPoints(unassigned, unassignedThreshold);
      looseClusters.forEach((cluster, idx) => {
        if (!cluster.length) {
          return;
        }
        blocks.push({
          id: `free-${idx}`,
          type: "free",
          sequenceIdx: 10000 + idx,
          center: centerFromPointInfos(cluster),
          points: cluster
        });
      });

      let currentMap = [startMap[0], startMap[1]];
      while (blocks.length) {
        let bestPos = 0;
        let bestDist = Number.POSITIVE_INFINITY;
        for (let i = 0; i < blocks.length; i += 1) {
          const d = mapDistance(currentMap, blocks[i].center);
          const tieBreaker = blocks[i].sequenceIdx * 1e-6;
          const score = d + tieBreaker;
          if (score < bestDist) {
            bestDist = score;
            bestPos = i;
          }
        }
        const block = blocks.splice(bestPos, 1)[0];
        let blockOrder;
        if (block.type === "rectangle") {
          blockOrder = block.points.slice();
        } else {
          blockOrder = buildNearestNeighborOrder(block.points, currentMap);
        }
        if (!blockOrder.length) {
          continue;
        }
        const firstDist = mapDistance(currentMap, blockOrder[0].mapCoord);
        const lastDist = mapDistance(currentMap, blockOrder[blockOrder.length - 1].mapCoord);
        if (lastDist < firstDist) {
          blockOrder = blockOrder.slice().reverse();
        }
        blockOrder.forEach((item) => orderedPoints.push(item.lonLat));
        currentMap = blockOrder[blockOrder.length - 1].mapCoord;
      }

      if (!orderedPoints.length) {
        return basePath;
      }

      function pointKey(lonLat) {
        return `${Number(lonLat[0]).toFixed(7)},${Number(lonLat[1]).toFixed(7)}`;
      }

      const routed = [startLonLat];
      const startInside = pointInPolygonLonLat(startLonLat, flightPolygonVertices);
      const targetKeys = new Set(orderedPoints.map((p) => pointKey(p)));
      const reachedKeys = new Set();
      let failedSegments = 0;
      orderedPoints.forEach((target, idx) => {
        const from = routed[routed.length - 1];
        if (idx === 0 && !startInside) {
          if (!pointsEqualLonLat(from, target)) {
            routed.push(target);
          }
          reachedKeys.add(pointKey(target));
          return;
        }
        const route = buildInPolygonRoute(from, target, flightPolygonVertices);
        if (!route || !route.length) {
          // If routing fails, keep target only when direct segment stays inside polygon.
          if (segmentInsidePolygonLonLat(from, target, flightPolygonVertices)) {
            routed.push(target);
            reachedKeys.add(pointKey(target));
          } else {
            failedSegments += 1;
          }
          return;
        }
        route.forEach((p) => {
          const last = routed[routed.length - 1];
          if (!pointsEqualLonLat(last, p)) {
            routed.push([Number(p[0]), Number(p[1])]);
          }
        });
        reachedKeys.add(pointKey(target));
      });

      // Sicherheitsregel zur Flächenabdeckung:
      // Falls ein geordneter Zielpunkt nicht erreichbar ist, den Originalpfad beibehalten
      // (bewahrt volle Abdeckung) statt einen unvollständigen Pfad zurückzugeben.
      const missedTargets = Array.from(targetKeys).filter((k) => !reachedKeys.has(k)).length;
      if (failedSegments > 0 || missedTargets > 0 || routed.length < 2) {
        return basePath;
      }

      return routed;
    }

    function computePolygonPrincipalStripMetrics(vertsLonLat) {
      if (!Array.isArray(vertsLonLat) || vertsLonLat.length < 3) {
        return null;
      }
      const n = vertsLonLat.length;
      let sumLon = 0;
      let sumLat = 0;
      vertsLonLat.forEach((v) => {
        sumLon += Number(v[0]);
        sumLat += Number(v[1]);
      });
      const avgLon = sumLon / n;
      const avgLat = sumLat / n;
      const cosO = Math.max(0.1, Math.cos((avgLat * Math.PI) / 180));
      const R = 6378137;
      const pts = vertsLonLat.map((v) => {
        const x = ((Number(v[0]) - avgLon) * Math.PI / 180) * R * cosO;
        const y = ((Number(v[1]) - avgLat) * Math.PI / 180) * R;
        return [x, y];
      });
      let cx = 0;
      let cy = 0;
      pts.forEach((p) => {
        cx += p[0];
        cy += p[1];
      });
      cx /= n;
      cy /= n;
      let cxx = 0;
      let cyy = 0;
      let cxy = 0;
      pts.forEach((p) => {
        const dx = p[0] - cx;
        const dy = p[1] - cy;
        cxx += dx * dx;
        cyy += dy * dy;
        cxy += dx * dy;
      });
      cxx /= n;
      cyy /= n;
      cxy /= n;
      const trace = cxx + cyy;
      const disc = Math.sqrt(Math.max(0, (trace * trace) / 4 - (cxx * cyy - cxy * cxy)));
      const lam1 = trace / 2 + disc;
      let vx;
      let vy;
      if (Math.abs(cxy) < 1e-12) {
        if (cxx >= cyy) {
          vx = 1;
          vy = 0;
        } else {
          vx = 0;
          vy = 1;
        }
      } else {
        vx = lam1 - cyy;
        vy = cxy;
        const len = Math.hypot(vx, vy);
        if (len < 1e-12) {
          vx = 1;
          vy = 0;
        } else {
          vx /= len;
          vy /= len;
        }
      }
      const wx = -vy;
      const wy = vx;
      let minP = Infinity;
      let maxP = -Infinity;
      pts.forEach((p) => {
        const dx = p[0] - cx;
        const dy = p[1] - cy;
        const pProj = (dx * wx) + (dy * wy);
        minP = Math.min(minP, pProj);
        maxP = Math.max(maxP, pProj);
      });
      const widthM = Math.max(0, maxP - minP);
      const elongationDeg = normalizeAngle180((Math.atan2(vy, vx) * 180) / Math.PI);
      return { widthM, elongationDeg };
    }

    function mapDirectionAngleToLocalFlightDeg(angleMapDeg, vertsLonLat) {
      if (!Array.isArray(vertsLonLat) || vertsLonLat.length < 3) {
        return NaN;
      }
      let sl = 0;
      let sa = 0;
      vertsLonLat.forEach((v) => {
        sl += Number(v[0]);
        sa += Number(v[1]);
      });
      const avgLon = sl / vertsLonLat.length;
      const avgLat = sa / vertsLonLat.length;
      const c = lonLatToMapCoord(avgLon, avgLat);
      const rad = (normalizeAngle180(angleMapDeg) * Math.PI) / 180;
      const step = 90;
      const c2 = [c[0] + (Math.cos(rad) * step), c[1] + (Math.sin(rad) * step)];
      const ll2 = ol.proj.transform(c2, "EPSG:3857", "EPSG:4326");
      const R = 6378137;
      const cosO = Math.max(0.1, Math.cos((avgLat * Math.PI) / 180));
      const lx1 = ((Number(ll2[0]) - avgLon) * Math.PI / 180) * R * cosO;
      const ly1 = ((Number(ll2[1]) - avgLat) * Math.PI / 180) * R;
      return normalizeAngle180((Math.atan2(ly1, lx1) * 180) / Math.PI);
    }

    function meanHeightLineDirectionLocalDegForFlight(vertsLonLat) {
      const angles = candidateAnglesFromHeightLines();
      if (!Array.isArray(angles) || !angles.length) {
        return NaN;
      }
      const meanMap = weightedAxialMean180(angles.map((a) => ({ angleDeg: a, weight: 1 })));
      return mapDirectionAngleToLocalFlightDeg(meanMap, vertsLonLat);
    }

    function isAdaptiveComplexScenario(polygonGeom, spacingMeters) {
      if (Array.isArray(lastRectangles) && lastRectangles.length >= 2) {
        const rectAngles = lastRectangles.map((rect) => rectangleAngleDeg(rect));
        for (let i = 0; i < rectAngles.length; i += 1) {
          for (let j = i + 1; j < rectAngles.length; j += 1) {
            if (angleDifference180(rectAngles[i], rectAngles[j]) >= 24) {
              return true;
            }
          }
        }
      }
      return false;
    }

    function applyReverseFlightPathOptionIfChecked() {
      if (typeof window.__rkrkApplyReverseFlightPathOptionIfChecked === "function") {
        window.__rkrkApplyReverseFlightPathOptionIfChecked();
      }
    }

    if (typeof calculateFlightPath === "function" && !window.__rkrkFieldFlightOptimizerInstalled) {
      window.__rkrkFieldFlightOptimizerInstalled = true;
      const originalCalculateFlightPath = calculateFlightPath;
      calculateFlightPath = function () {
        try {
        if (typeof fpCalcVariantEl === "undefined" || !fpCalcVariantEl) {
          return originalCalculateFlightPath();
        }
        const activeVariant = fpCalcVariantEl.value;
        // Variante A (rectangleDirection): wieder Sweep im Polygon mit Auto-Winkel
        // „längste Bahnen“ (chooseLongestLineAngle in index.html), nicht Sektor-Höhenpfad.
        if (activeVariant === HEIGHT_GUIDED_VARIANT_KEY) {
          return originalCalculateFlightPath();
        }
        if (activeVariant === RECTANGLE_HEIGHT_GUIDED_ADAPTIVE_KEY) {
          if (
            typeof fpTrackDistanceEl === "undefined" || !fpTrackDistanceEl ||
            typeof fpDirectionModeEl === "undefined" || !fpDirectionModeEl ||
            typeof fpDirectionWheelEl === "undefined" || !fpDirectionWheelEl ||
            typeof fpDirectionAngleEl === "undefined" || !fpDirectionAngleEl
          ) {
            return originalCalculateFlightPath();
          }
          const vertsRaw = (Array.isArray(flightPolygonVertices) && flightPolygonVertices.length >= 3)
            ? flightPolygonVertices
            : polygonVertices;
          if (!Array.isArray(vertsRaw) || vertsRaw.length < 3) {
            if (typeof flightPathStatusEl !== "undefined" && flightPathStatusEl) {
              flightPathStatusEl.textContent = "Adaptiv: Flugpolygon mit mindestens 3 Punkten auswählen.";
            }
            return;
          }
          const sp = readFlightSpacingContextFromUi();
          const strip = computePolygonPrincipalStripMetrics(vertsRaw);
          if (strip && strip.widthM <= (2 * sp.effectiveTrackDistance) + 0.5) {
            window.__rkrkForcedFlightDirectionDeg = normalizeAngle180(strip.elongationDeg);
            window.__rkrkAdaptiveFlightStatusPrefix = "[Adaptiv · schmal] Parallelstreifen entlang der Längsachse (Breite ≤ 2× effektiver Bahnabstand).";
            originalCalculateFlightPath();
            return;
          }
          const polygonGeom = polygonGeometryFromVertices();
          const spacingM = Math.max(5, Number(fpTrackDistanceEl.value) || 30);
          if (isAdaptiveComplexScenario(polygonGeom, spacingM)) {
            if (!Array.isArray(lastRectangles) || !lastRectangles.length || !Array.isArray(lastPointData) || !lastPointData.length) {
              if (typeof flightPathStatusEl !== "undefined" && flightPathStatusEl) {
                flightPathStatusEl.textContent = "Adaptiv · komplex: wie Variante D — zuerst Polygon-Formanalyse (Rechtecke) und Kontrollpunkte mit Höhe.";
              }
              if (typeof flightMissingInfoEl !== "undefined" && flightMissingInfoEl) {
                flightMissingInfoEl.textContent = "Adaptiv erkennt komplexe Geometrie — gleiche Voraussetzungen wie Variante D.";
              }
              return;
            }
            const planned = buildRectangleHeightGuidedFlightPathEAdaptiveComplex();
            if (!planned || !Array.isArray(planned.path) || planned.path.length < 2) {
              clearSubPolygonV4Overlay();
              return originalCalculateFlightPath();
            }
            flightPathPoints = planned.path;
            applyReverseFlightPathOptionIfChecked();
            selectedFlightFeature = null;
            selectedFlightPointIndex = -1;
            if (typeof renderFlightPath === "function") {
              renderFlightPath();
            }
            if (typeof updateFlightPathUiState === "function") {
              updateFlightPathUiState();
            }
            if (typeof flightPathStatusEl !== "undefined" && flightPathStatusEl) {
              const extPct = Math.round((fpLineExtensionEl ? Number(fpLineExtensionEl.value) : (lineExtensionRatio * 100)) || 0);
              const subN = Number(planned.subPolygonCount) || 0;
              flightPathStatusEl.textContent = `Flugpfad berechnet (Adaptiv · komplex, erweiterte Unterpolygon-Logik): ${flightPathPoints.length} Punkte, Rechtecke=${planned.rectangleCount}, Unterpolygone=${subN}, Geometrie-Blöcke=${planned.rectangleHeightCount}, Zellen=${planned.cellCount}, Bahnabstand=${planned.trackDistance.toFixed(1)}m (±${(planned.parallelTolerancePct || 20).toFixed(0)}%), Linienverlängerung=${extPct}%, verbundene Segmente=${planned.routedSegments}. Aggressivere Sehnen-Erstreckung und Lückenkorrektur nur bei Adaptiv E.`;
            }
            if (typeof flightMissingInfoEl !== "undefined" && flightMissingInfoEl) {
              flightMissingInfoEl.textContent = "Adaptiv · komplex: Unterpolygone wie D, zusätzlich feineres Lücken-Raster, mehr Streifen-End-Verlängerungen und Innen-Nähe-Korrektur (ohne neue Umrouten) — nicht identisch mit Variante D.";
            }
            saveFieldSettings();
            return;
          }
          if (strip) {
            window.__rkrkForcedFlightDirectionDeg = normalizeAngle180(strip.elongationDeg);
            window.__rkrkAdaptiveFlightStatusPrefix = "[Adaptiv · homogen] Flugrichtung entlang Längsachse (ohne Sektor-Höhenlinien).";
          } else {
            window.__rkrkAdaptiveFlightStatusPrefix = "[Adaptiv · homogen] Auto-Winkel über längste Polygonbahn.";
          }
          originalCalculateFlightPath();
          return;
        }
        if (
          activeVariant !== RECTANGLE_HEIGHT_GUIDED_VARIANT2_KEY
          && activeVariant !== RECTANGLE_HEIGHT_GUIDED_VARIANT3_KEY
          && activeVariant !== RECTANGLE_HEIGHT_GUIDED_VARIANT4_KEY
          && activeVariant !== RECTANGLE_HEIGHT_GUIDED_VARIANT5_KEY
        ) {
          return originalCalculateFlightPath();
        }
        if (
          typeof fpTrackDistanceEl === "undefined" || !fpTrackDistanceEl ||
          typeof fpDirectionModeEl === "undefined" || !fpDirectionModeEl ||
          typeof fpDirectionWheelEl === "undefined" || !fpDirectionWheelEl ||
          typeof fpDirectionAngleEl === "undefined" || !fpDirectionAngleEl
        ) {
          return originalCalculateFlightPath();
        }

        // Rechteck-Formanalyse für B und C (nicht D).
        const needsRectangleFormAnalysis = (
          activeVariant === RECTANGLE_HEIGHT_GUIDED_VARIANT2_KEY
          || activeVariant === RECTANGLE_HEIGHT_GUIDED_VARIANT3_KEY
        );
        if (needsRectangleFormAnalysis && (!Array.isArray(lastRectangles) || !lastRectangles.length)) {
          if (typeof flightPathStatusEl !== "undefined" && flightPathStatusEl) {
            flightPathStatusEl.textContent = "Berechnung nicht startbar: zuerst Polygon-Formanalyse (Rechtecke) ausführen.";
          }
          if (typeof flightMissingInfoEl !== "undefined" && flightMissingInfoEl) {
            flightMissingInfoEl.textContent = "Variante B/C: benötigt Rechtecke aus Polygon-Formanalyse.";
          }
          return;
        }
        if (
          activeVariant === RECTANGLE_HEIGHT_GUIDED_VARIANT4_KEY
          || activeVariant === RECTANGLE_HEIGHT_GUIDED_VARIANT5_KEY
        ) {
          if (!Array.isArray(lastPointData) || !lastPointData.length) {
            if (typeof flightPathStatusEl !== "undefined" && flightPathStatusEl) {
              flightPathStatusEl.textContent = activeVariant === RECTANGLE_HEIGHT_GUIDED_VARIANT5_KEY
                ? "Berechnung nicht startbar: Variante F benötigt Kontrollpunkte mit Höhe im Feld."
                : "Berechnung nicht startbar: Variante D benötigt Kontrollpunkte mit Höhe im Feld.";
            }
            if (typeof flightMissingInfoEl !== "undefined" && flightMissingInfoEl) {
              flightMissingInfoEl.textContent = activeVariant === RECTANGLE_HEIGHT_GUIDED_VARIANT5_KEY
                ? "Variante F: wie D; Kontrollpunkte im Polygon setzen; gelbe Unterpolygone beim Berechnen."
                : "Variante D: Kontrollpunkte im Polygon setzen; Sektor-Höhenanalyse und gelbe Unterpolygone erfolgen automatisch beim Berechnen.";
            }
            return;
          }
        } else {
          const polygonGeom = polygonGeometryFromVertices();
          const hints = polygonGeom ? selectPrimarySectorHints(resolveSectorDirectionHints(polygonGeom, Math.max(5, Number(fpTrackDistanceEl.value) || 30)), 2) : [];
          if (!hints.length) {
            if (typeof flightPathStatusEl !== "undefined" && flightPathStatusEl) {
              flightPathStatusEl.textContent = "Berechnung nicht startbar: Sektor-Höhenrichtungen fehlen. Zuerst Sektoren/Höhenrichtungslinien je Sektor erzeugen.";
            }
            if (typeof flightMissingInfoEl !== "undefined" && flightMissingInfoEl) {
              flightMissingInfoEl.textContent = "Variante B/C: benötigt die Sektor-Höhenrichtungen als Hauptflugrichtungen.";
            }
            return;
          }
        }
        const planned = activeVariant === RECTANGLE_HEIGHT_GUIDED_VARIANT4_KEY
          ? buildRectangleHeightGuidedFlightPathV4()
          : (activeVariant === RECTANGLE_HEIGHT_GUIDED_VARIANT5_KEY
            ? buildRectangleHeightGuidedFlightPathV5()
            : (activeVariant === RECTANGLE_HEIGHT_GUIDED_VARIANT3_KEY
              ? buildRectangleHeightGuidedFlightPathV3()
              : buildRectangleHeightGuidedFlightPathV2()));
        if (!planned || !Array.isArray(planned.path) || planned.path.length < 2) {
          if (
            activeVariant === RECTANGLE_HEIGHT_GUIDED_VARIANT4_KEY
            || activeVariant === RECTANGLE_HEIGHT_GUIDED_VARIANT5_KEY
          ) {
            clearSubPolygonV4Overlay();
          }
          return originalCalculateFlightPath();
        }
        flightPathPoints = planned.path;
        applyReverseFlightPathOptionIfChecked();
        if (typeof window.__rkrkOptimizeFlightPathPoints === "function") {
          const opt = (activeVariant === RECTANGLE_HEIGHT_GUIDED_VARIANT4_KEY
            || activeVariant === RECTANGLE_HEIGHT_GUIDED_VARIANT5_KEY)
            ? { minSegmentMeters: 5.5, collinearToleranceMeters: 3.5 }
            : { minSegmentMeters: 2.8, collinearToleranceMeters: 1.8 };
          flightPathPoints = window.__rkrkOptimizeFlightPathPoints(
            flightPathPoints,
            flightPolygonVertices,
            opt
          );
        }
        selectedFlightFeature = null;
        selectedFlightPointIndex = -1;
        if (typeof renderFlightPath === "function") {
          renderFlightPath();
        }
        if (typeof updateFlightPathUiState === "function") {
          updateFlightPathUiState();
        }
        if (typeof flightPathStatusEl !== "undefined" && flightPathStatusEl) {
          if (activeVariant === RECTANGLE_HEIGHT_GUIDED_VARIANT4_KEY) {
            const extPct = Math.round((fpLineExtensionEl ? Number(fpLineExtensionEl.value) : (lineExtensionRatio * 100)) || 0);
            const subN = Number(planned.subPolygonCount) || 0;
            flightPathStatusEl.textContent = `Flugpfad berechnet (Variante D / Unterpolygone): ${flightPathPoints.length} Punkte, Rechtecke=${planned.rectangleCount}, Unterpolygone=${subN}, Geometrie-Blöcke=${planned.rectangleHeightCount}, Zellen=${planned.cellCount}, Bahnabstand=${planned.trackDistance.toFixed(1)}m (±${(planned.parallelTolerancePct || 20).toFixed(0)}%), Linienverlängerung=${extPct}%, verbundene Segmente=${planned.routedSegments}. Karte: Rechtecke + gelber Umriss + Richtungsachsen je Unterpolygon.`;
          } else if (activeVariant === RECTANGLE_HEIGHT_GUIDED_VARIANT5_KEY) {
            const extPct = Math.round((fpLineExtensionEl ? Number(fpLineExtensionEl.value) : (lineExtensionRatio * 100)) || 0);
            const subN = Number(planned.subPolygonCount) || 0;
            flightPathStatusEl.textContent = `Flugpfad berechnet (Variante F / Unterpolygone, erweiterte Sehnen): ${flightPathPoints.length} Punkte, Rechtecke=${planned.rectangleCount}, Unterpolygone=${subN}, Geometrie-Blöcke=${planned.rectangleHeightCount}, Zellen=${planned.cellCount}, Bahnabstand=${planned.trackDistance.toFixed(1)}m (±${(planned.parallelTolerancePct || 20).toFixed(0)}%), Linienverlängerung=${extPct}%, verbundene Segmente=${planned.routedSegments}. Wie D, zuzüglich aggressiver Erst-Streckung der Bahnpolylinien entlang der Sehne.`;
          } else if (activeVariant === RECTANGLE_HEIGHT_GUIDED_VARIANT3_KEY) {
            const extPct = Math.round((fpLineExtensionEl ? Number(fpLineExtensionEl.value) : (lineExtensionRatio * 100)) || 0);
            flightPathStatusEl.textContent = `Flugpfad berechnet (Variante C): ${flightPathPoints.length} Punkte, Rechtecke=${planned.rectangleCount}, Rechtecke mit Höhenrichtung=${planned.rectangleHeightCount}, Zellen=${planned.cellCount}, Bahnabstand=${planned.trackDistance.toFixed(1)}m (zulässig ${planned.trackSpacingMin.toFixed(1)}..${planned.trackSpacingMax.toFixed(1)}m, Toleranz ±${(planned.parallelTolerancePct || 20).toFixed(0)}%), max-Randabstand=${planned.maxBorderDistance.toFixed(1)}m, Linienverlängerung=${extPct}%, verbundene Segmente=${planned.routedSegments}.`;
          } else if (activeVariant === RECTANGLE_HEIGHT_GUIDED_VARIANT2_KEY) {
            const extPct = Math.round((fpLineExtensionEl ? Number(fpLineExtensionEl.value) : (lineExtensionRatio * 100)) || 0);
            flightPathStatusEl.textContent = `Flugpfad berechnet (Variante B): ${flightPathPoints.length} Punkte, Rechtecke=${planned.rectangleCount}, Rechtecke mit Höhenrichtung=${planned.rectangleHeightCount}, Zellen=${planned.cellCount}, Bahnabstand=${planned.trackDistance.toFixed(1)}m, verbundene Segmente=${planned.routedSegments}, Linienverlängerung=${extPct}%, Profil-Stützpunkte=${planned.supportPointsAdded || 0}.`;
          }
        }
        if (typeof flightMissingInfoEl !== "undefined" && flightMissingInfoEl) {
          if (activeVariant === RECTANGLE_HEIGHT_GUIDED_VARIANT4_KEY) {
            flightMissingInfoEl.textContent = "Variante D aktiv: Rechtecke nach Nähe zu Unterpolygonen verbunden (ohne Zerschneiden der Analyse-Rechtecke), Geometrie-Achse und Umriss je Unterpolygon auf der Karte (gelbe Ebene).";
          } else if (activeVariant === RECTANGLE_HEIGHT_GUIDED_VARIANT5_KEY) {
            flightMissingInfoEl.textContent = "Variante F aktiv: wie D; zusätzlich werden Bahnpolylinien bei der Erzeugung entlang der Sehne gegenüber dem Feinraster stärker in End-Lücken gestreckt (ohne neue Auffüll-Pfade).";
          } else if (activeVariant === RECTANGLE_HEIGHT_GUIDED_VARIANT3_KEY) {
            flightMissingInfoEl.textContent = "Variante C aktiv: kontinuierliche Parallelbahnen entlang Höhenrichtung, Bahnabstand mit einstellbarer Toleranz, komplette Polygonabdeckung und Randregel aus Randfaktor×Bahnabstand.";
          } else if (activeVariant === RECTANGLE_HEIGHT_GUIDED_VARIANT2_KEY) {
            flightMissingInfoEl.textContent = "Variante B aktiv: Hauptflugrichtungen kommen aus den Sektor-Höhenlinien; Rechtecke strukturieren die Bereiche, volle Polygonabdeckung bleibt aktiv.";
          }
        }
        saveFieldSettings();
        } catch (_) {
          // Never block standard flight-path generation due to optimizer issues.
          return originalCalculateFlightPath();
        }
      };
    }

    window.__rkrkPrepareFieldAnalysisForFlight = async function (options) {
      const opts = options && typeof options === "object" ? options : {};
      if (!Array.isArray(polygonVertices) || polygonVertices.length < 3) {
        const message = "Polygon mit mindestens 3 Punkten erforderlich.";
        statusEl.textContent = message;
        return { ok: false, message };
      }

      if (opts.ensureControlPoints !== false && (!controlPointsEnabled || !Array.isArray(lastPointData) || !lastPointData.length)) {
        await setAnalysisPoints();
      }
      if (!controlPointsEnabled || !Array.isArray(lastPointData) || !lastPointData.length) {
        const message = "Kontrollpunkte konnten nicht vorbereitet werden.";
        statusEl.textContent = message;
        return { ok: false, message };
      }

      if (opts.ensureRectangleAnalysis === true && (!Array.isArray(lastRectangles) || !lastRectangles.length)) {
        await runRectangleAnalysis();
      }
      const requireRectangleAnalysis = opts.requireRectangleAnalysis === true;
      if (requireRectangleAnalysis && (!Array.isArray(lastRectangles) || !lastRectangles.length)) {
        const message = "Polygon-Formanalyse (Rechtecke) konnte nicht vorbereitet werden.";
        statusEl.textContent = message;
        return { ok: false, message };
      }
      if (opts.ensureSectorDirections === true) {
        const ok = renderSectorDirectionGroups();
        const strictSectors = opts.requireSectorsForPrep === true;
        if (!ok && strictSectors) {
          const message = "Sektor-Höhenrichtungen konnten nicht vorbereitet werden.";
          statusEl.textContent = message;
          return { ok: false, message };
        }
        if (ok) {
          drawSectorHeightLinesFromGroups();
        }
      }

      if (opts.ensureDirectionLines !== false) {
        directionLinesEnabled = true;
        refreshDirectionLines();
      }
      let lineCount = 0;
      if (opts.ensureDirectionLines !== false) {
        lineCount = candidateAnglesFromHeightLines().length;
        if (opts.requireHeightDirectionLines === true && lineCount < 1) {
          const message = "Keine Höhenrichtungslinien verfügbar.";
          statusEl.textContent = message;
          return { ok: false, message };
        }
      }

      return {
        ok: true,
        controlPoints: lastPointData.length,
        directionAngles: lineCount,
        rectangles: Array.isArray(lastRectangles) ? lastRectangles.length : 0,
        sectorDirections: Array.isArray(lastSectorDirectionGroups) ? lastSectorDirectionGroups.length : 0
      };
    };

    window.__rkrkFieldHasHeightDirectionLines = function () {
      return candidateAnglesFromHeightLines().length > 0;
    };

    if (typeof getFlightPathMissingRequirements === "function" && !window.__rkrkFieldMissingRequirementsPatched) {
      window.__rkrkFieldMissingRequirementsPatched = true;
      const originalGetFlightPathMissingRequirements = getFlightPathMissingRequirements;
      getFlightPathMissingRequirements = function (calcVariant) {
        // Flugpfad-Rechnen soll die Analyse-Schritte selbst vorbereiten.
        // Deshalb hier nur Basisanforderungen (Start/Polygon/Höhe/Spur/...),
        // keine vorberechenbaren Analyse-Artefakte blockieren den Start.
        return originalGetFlightPathMissingRequirements(calcVariant);
      };
    }

    configureFlightVariantUiLabels();
    if (typeof fpCalcVariantEl !== "undefined" && fpCalcVariantEl) {
      fpCalcVariantEl.addEventListener("change", () => {
        if (fpCalcVariantEl.value !== RECTANGLE_HEIGHT_GUIDED_VARIANT4_KEY
          && fpCalcVariantEl.value !== RECTANGLE_HEIGHT_GUIDED_VARIANT5_KEY
          && fpCalcVariantEl.value !== RECTANGLE_HEIGHT_GUIDED_ADAPTIVE_KEY) {
          clearSubPolygonV4Overlay();
          applyHelperLayersVisibility();
        }
        updateFieldAnalysisV4ExplainVisibility();
        refreshSubPolygonV4ToggleButtonState();
        if (typeof updateFlightPathUiState === "function") {
          updateFlightPathUiState();
        }
      });
    }

    applySavedFieldSettings();
    setToggleButtonState(
      toggleDirectionBtn,
      directionLinesEnabled,
      "Höhenrichtungslinien zeigen",
      "Höhenrichtungslinien verbergen"
    );
    updateFieldAnalysisV4ExplainVisibility();
    refreshSubPolygonV4ToggleButtonState();
    refreshRectangleFormMapToggleButtonState();
    applySubPolygonV4LayerVisibility();

    return true;
  }

  let retries = 0;
  function boot() {
    const ok = initFieldAnalysisFeature();
    if (ok) {
      return;
    }
    if (retries >= 20) {
      return;
    }
    retries += 1;
    setTimeout(boot, 150);
  }
  boot();
})();
</script>
JS;

$output = str_replace('</body>', $fieldAnalysisScript . PHP_EOL . '</body>', $html);
if ($output === $html) {
    $output = $html . PHP_EOL . $fieldAnalysisScript;
}
echo $output;
?>
