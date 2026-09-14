(function () {
  const ON_RKRS = !!(window.__RKR_BRAND__ ? window.__RKR_BRAND__.isRkrs : window.__ON_RKRS__);
  const KML_SAVE_API = "save_kml.php";
  const MBTILES_EXPORT_API = "export_mbtiles.php";
  const FLIGHTHUB_EXPORT_API = "export_flighthub.php";
  const DEFAULT_GRID_ROTATION = 0;
  let gridRulerActive = false;
  let managedObjectSource = null;
  let managedObjectLayer = null;
  let managedObjects = [];
  let managedObjectIdSeq = 1;
  let managedObjectGroups = [];
  let managedObjectGroupIdSeq = 1;
  let managedListOrder = [];
  let objListSelectedIds = new Set();
  let selectedManagedObjectId = null;
  let managedEditPaused = false;
  let dragManagedState = null;
  let contextMenuTargetObjectId = null;
  let contextMenuFillTarget = null;
  let deactivateGridRuler = null;
  let refreshManagedObjectList = () => {};
  let beginEditManagedObject = () => {};
  let syncEditingObjectFromUi = () => {};
  let deleteManagedObjectById = () => {};
  let editBaselineSnapshot = null;
  let fixedUndoStack = [];
  let fixedRedoStack = [];
  let fixedHistoryApplying = false;
  let fixedHistoryCursorJson = "";
  const FIXED_HISTORY_MAX = 40;
  let freehandDraftCoords = [];
  let freehandDrawing = false;
  let freehandVertexDragIndex = -1;
  let freehandSuppressClick = false;
  let freehandPointerSession = null;
  let discardCurrentObjectChanges = () => {};
  let finalizeCurrentObjectAsFixedRef = () => {};
  let moveManagedObjectZOrder = () => {};
  let refreshObjectActionButtons = () => {};
  const OBJ_TOOL_ICONS = {
    draw: '<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>',
    edit: '<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>',
    stop: '<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M6 6h12v12H6z"/></svg>',
    clear: '<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M6 19a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>',
    fix: '<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M9 16.2 4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4z"/></svg>',
    discard: '<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M12.5 8c-2.65 0-5.05.99-6.9 2.6L2 7v9h9l-3.62-3.62c1.39-1.16 3.16-1.88 5.12-1.88 3.54 0 6.55 2.31 7.6 5.5l2.37-.78C21.08 11.03 17.15 8 12.5 8z"/></svg>',
    kml: '<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm4 18H6V4h7v5h5v11z"/></svg>',
    kmlLoad: '<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M5 20h14v-2H5v2zm7-18L5.33 9h3.84v4h5.66V9h3.84L12 2z"/></svg>',
    map: '<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M20.5 3l-.16.03L15 5.1 9 3 3.36 4.9c-.21.07-.36.25-.36.48V20.5c0 .28.22.5.5.5l.16-.03L9 18.9l6 2.1 5.64-1.9c.21-.07.36-.25.36-.48V3.5c0-.28-.22-.5-.5-.5zM15 19l-6-2.11V5l6 2.11V19z"/></svg>',
    pdf: '<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm-1.5 14H8v-2h4.5a1 1 0 0 0 0-2H10a1 1 0 0 0-1 1v4h5.5a1 1 0 0 0 0-2H12.5v1zM14 9V3.5L19.5 9H14z"/></svg>',
    mbtiles: '<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M3 5h8v6H3V5zm10 0h8v6h-8V5zM3 13h8v6H3v-6zm10 0h8v6h-8v-6z"/></svg>',
    hub2: '<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M19.35 10.04A7.49 7.49 0 0 0 12 4C9.11 4 6.6 5.64 5.35 8.04A5.994 5.994 0 0 0 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM12 12l-4 4h3v4h2v-4h3l-4-4z"/></svg>',
    hub2send: '<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M2.01 21 23 12 2.01 3 2 10l15 2-15 2z"/></svg>',
    link: '<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M3.9 12a5 5 0 0 1 5-5h3v2h-3a3 3 0 1 0 0 6h3v2h-3a5 5 0 0 1-5-5zm7-1h6v2h-6v-2zm2-4h3a5 5 0 0 1 0 10h-3v-2h3a3 3 0 1 0 0-6h-3V7z"/></svg>',
    point: '<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><circle cx="12" cy="12" r="5" fill="currentColor"/></svg>',
    text: '<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M5 4v3h5.5v12h3V7H19V4H5z"/></svg>',
    freehand: '<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M3 17c3-1 5-4 7-4s3 2 5 2 4-3 6-3v2c-2 0-4 3-6 3s-3-2-5-2-4 3-7 4v-2z"/></svg>',
    pfeil: '<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M3 11h12.17l-3.58-3.59L13 6l6 6-6 6-1.41-1.41L15.17 13H3v-2z"/></svg>',
    grid: '<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M3 3h8v8H3V3zm10 0h8v8h-8V3zM3 13h8v8H3v-8zm10 0h8v8h-8v-8z"/></svg>',
    circle: '<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><circle cx="12" cy="12" r="8" fill="none" stroke="currentColor" stroke-width="2"/></svg>',
    circles: '<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><circle cx="9" cy="12" r="5.5" fill="none" stroke="currentColor" stroke-width="1.8"/><circle cx="15" cy="12" r="5.5" fill="none" stroke="currentColor" stroke-width="1.8"/></svg>',
    polygon: '<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M12 3l8 5.5v7L12 21l-8-5.5v-7L12 3zm0 2.4L6.5 9.2v5.6L12 18.6l5.5-3.8V9.2L12 5.4z"/></svg>',
    line: '<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M3.5 18.5 18.5 3.5l2 2L5.5 20.5l-2-2z"/></svg>',
    show: '<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M12 5c-5 0-9.27 3.11-11 7 1.73 3.89 6 7 11 7s9.27-3.11 11-7c-1.73-3.89-6-7-11-7zm0 12a5 5 0 1 1 0-10 5 5 0 0 1 0 10zm0-8a3 3 0 1 0 0 6 3 3 0 0 0 0-6z"/></svg>',
    hide: '<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M2.1 3.51 3.51 2.1l18.39 18.39-1.41 1.41-3.13-3.13A12.3 12.3 0 0 1 12 19c-5 0-9.27-3.11-11-7a13.4 13.4 0 0 1 4.35-5.13L2.1 3.51zM12 7a5 5 0 0 1 4.9 6.07l-1.52-1.52A3 3 0 0 0 12.45 8.6L12 7zm0 10c1.4 0 2.72-.3 3.92-.84l-1.56-1.56A5 5 0 0 1 8.4 9.64L6.84 8.08A11.4 11.4 0 0 0 1 12c1.73 3.89 6 7 11 7z"/></svg>'
  };
  function setObjToolButton(btn, iconKey, title) {
    if (!btn) return;
    const svg = OBJ_TOOL_ICONS[iconKey] || "";
    btn.classList.add("obj-icon-btn");
    btn.title = title || "";
    btn.setAttribute("aria-label", title || "");
    if (svg) btn.innerHTML = svg;
    else btn.textContent = title || "";
  }
  function managedObjectTypeIconKey(obj) {
    const t = String((obj && obj.type) || "").toLowerCase();
    if (t === "grid") return "grid";
    if (t === "circle") return "circle";
    if (t === "circles") return "circles";
    if (t === "polygon") return "polygon";
    if (t === "points") return "point";
    if (t === "textfields" || t === "text") return "text";
    if (t === "freehand") return "freehand";
    if (t === "pfeil" || t === "arrow") return "pfeil";
    if (t === "line" || t === "polyline") return "line";
    return "draw";
  }
  function managedObjectTypeIconHtml(obj) {
    const key = managedObjectTypeIconKey(obj);
    return OBJ_TOOL_ICONS[key] || OBJ_TOOL_ICONS.draw || "";
  }


  function q(sel, root) { return (root || document).querySelector(sel); }

  function normalizeAngle(value) {
    const n = Number(value);
    if (!Number.isFinite(n)) return DEFAULT_GRID_ROTATION;
    let a = n % 360;
    if (a < 0) a += 360;
    return a;
  }
  let gridRotationSyncing = false;
  let updateRulerLineVisual = function () {};
  let gridRotationDragActive = false;

  function getGridRotationAngle() {
    const input = document.getElementById("gridRotationAngle");
    return input ? normalizeAngle(input.value) : DEFAULT_GRID_ROTATION;
  }

  function setGridRotationAngle(value) {
    const a = Math.round(normalizeAngle(value));
    const input = document.getElementById("gridRotationAngle");
    const wheel = document.getElementById("gridRotationWheel");
    if (input) input.value = String(a);
    if (wheel) wheel.value = String(a);
    return a;
  }
  /** Single source of truth: update Grad, Lineal-Grafik und Raster gemeinsam. */

  function applyGridRotation(angleDeg, options) {
    const opts = options || {};
    if (gridRotationSyncing && !opts.force) return Math.round(getGridRotationAngle());
    gridRotationSyncing = true;
    let a = DEFAULT_GRID_ROTATION;
    try {
      a = setGridRotationAngle(angleDeg);
      if (typeof updateGridRotationLiveLabel === "function") updateGridRotationLiveLabel(a);
      if (opts.skipRedraw) {
        if (typeof renderGridRotationOverlay === "function") renderGridRotationOverlay(a);
      } else {
        if (typeof redrawGridPreviewFromRotation === "function") {
          redrawGridPreviewFromRotation(!!opts.immediate);
        } else if (typeof renderGridRotationOverlay === "function") {
          renderGridRotationOverlay(a);
        }
      }
      if (typeof updateRulerLineVisual === "function") updateRulerLineVisual(a);
      if (selectedManagedObjectId) {
        const obj = managedObjects.find((o) => o.id === selectedManagedObjectId);
        if (obj && obj.type === "grid" && !obj.fixed) obj.rotation = a;
      }
    } finally {
      gridRotationSyncing = false;
    }
    return a;
  }

  function applyCompactCss() {
    let style = document.getElementById("objModeCompactCss");
    if (!style) {
      style = document.createElement("style");
      style.id = "objModeCompactCss";
      document.head.appendChild(style);
    }
    style.textContent = `
      aside.panel { padding: 10px !important; }
      aside.panel .panel-details { margin-top: 8px !important; }
      aside.panel .panel-details > summary { padding: 6px 8px !important; font-size: 12px !important; }
      aside.panel .panel-details-content { padding: 0 8px 8px !important; }
      aside.panel label { margin: 5px 0 3px !important; font-size: 12px !important; }
      aside.panel input, aside.panel select, aside.panel button { font-size: 12px !important; padding: 6px 7px !important; }
      aside.panel button { padding: 5px 8px !important; line-height: 1.2 !important; margin-top: 6px !important; }
      aside.panel .obj-action-bar,
      aside.panel .obj-action-toggle {
        display: flex;
        flex-wrap: wrap;
        align-items: stretch;
        gap: 4px;
        margin: 4px 0 6px;
        width: 100%;
        max-width: 100%;
        border: none;
        border-radius: 0;
        overflow: visible;
      }
      aside.panel .obj-action-bar > button,
      aside.panel .obj-action-toggle > button {
        flex: 1 1 auto;
        min-width: calc(25% - 4px);
        margin: 0 !important;
        border-radius: 4px !important;
        padding: 4px 7px !important;
        font-size: 11px !important;
        line-height: 1.15 !important;
        white-space: nowrap;
      }
      aside.panel .obj-action-bar > button + button,
      aside.panel .obj-action-toggle > button + button {
        border-left: none !important;
      }
      aside.panel .obj-action-bar > button[hidden],
      aside.panel .obj-action-bar > button[style*="display: none"],
      aside.panel .obj-action-toggle > button[hidden],
      aside.panel .obj-action-toggle > button[style*="display: none"] {
        display: none !important;
      }
      aside.panel #objManagedStatus:empty,
      aside.panel #objManagedStatus.obj-status-idle {
        display: none !important;
        margin: 0 !important;
        height: 0 !important;
        overflow: hidden !important;
      }
      aside.panel .small { margin: 4px 0 !important; font-size: 11px !important; }
      aside.panel #objectParamsCircle > p.small:first-child,
      aside.panel #objectParamsCircles > p.small:first-child,
      aside.panel #objectParamsGrid > p.small:first-child,
      aside.panel #objectParamsPolygon > p.small:first-child,
      aside.panel #objectParamsFreehand > p.small:first-child {
        display: none !important;
      }
      /* Legacy objectActionHost: leer lassen (Export sitzt in #shareActionHost) */
      #objectActionHost:empty { display: none !important; }
      #objectActionHost:not(:has(button)) { display: none !important; }
      /* Teilen/Export-Sektion */
      #sharePanelDetails .panel-details-content {
        display: flex !important;
        flex-direction: column !important;
        gap: 6px !important;
      }
      #shareActionHost {
        display: flex !important;
        flex-wrap: wrap !important;
        align-items: center !important;
        gap: 4px !important;
      }
      #shareActionHost > button {
        flex: 0 0 auto !important;
        margin-top: 0 !important;
        min-width: 34px !important;
        width: 34px !important;
        height: 34px !important;
        padding: 6px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        line-height: 1 !important;
      }
      #shareActionHost > button svg {
        display: block !important;
        width: 16px !important;
        height: 16px !important;
      }
      #shareActionHost > button#shareViewLinkBtn.is-live {
        border-color: #166534 !important;
        color: #166534 !important;
        background: #ecfdf5 !important;
      }
      #sharePanelHint {
        margin: 0 !important;
        font-size: 11px !important;
      }
      #shareActionHost > button#kmlImportShareBtn {
        border-style: dashed !important;
      }
      #shareActionHost > button#exportMbtilesBtn {
        background: #0f172a !important;
        border-color: #0369a1 !important;
        color: #e0f2fe !important;
      }
      #shareActionHost > button#exportMbtilesBtn:hover {
        background: #1e293b !important;
      }
      #shareActionHost > select#mapPdfScaleSelect {
        flex: 1 1 108px !important;
        min-width: 108px !important;
        max-width: 148px !important;
        height: 34px !important;
        margin: 0 !important;
        padding: 4px 6px !important;
        font-size: 11px !important;
        line-height: 1.2 !important;
        box-sizing: border-box !important;
      }
      /* Ort suchen: Input und Button untereinander */
      #objPlaceSearchRow {
        display: flex !important;
        flex-direction: column !important;
        gap: 4px !important;
        align-items: stretch !important;
        margin-top: 4px !important;
      }
      #objPlaceSearchRow input {
        width: 100% !important;
        box-sizing: border-box !important;
        margin: 0 !important;
      }
      #objPlaceSearchRow button {
        width: 100% !important;
        box-sizing: border-box !important;
        margin: 0 !important;
        white-space: nowrap !important;
      }
      /* Zeichnungen: Button-Paare nebeneinander */
      #drawingsPanelDetails .panel-details-content {
        display: grid !important;
        grid-template-columns: 1fr 1fr !important;
        gap: 4px !important;
        align-items: center !important;
      }
      #drawingsPanelDetails .panel-details-content > label,
      #drawingsPanelDetails .panel-details-content > input,
      #drawingsPanelDetails .panel-details-content > select,
      #drawingsPanelDetails .panel-details-content > p {
        grid-column: 1 / -1 !important;
      }
      #drawingsPanelDetails .panel-details-content > button {
        margin-top: 0 !important;
        width: 100% !important;
      }
      /* Beschriftete Punkte: Buttons nebeneinander */
      #objEmbeddedLabeledPoints {
        display: grid !important;
        grid-template-columns: 1fr 1fr !important;
        gap: 4px !important;
        align-items: center !important;
      }
      #objEmbeddedLabeledPoints > label,
      #objEmbeddedLabeledPoints > input,
      #objEmbeddedLabeledPoints > select,
      #objEmbeddedLabeledPoints > p {
        grid-column: 1 / -1 !important;
      }
      #objEmbeddedLabeledPoints > button {
        margin-top: 0 !important;
        width: 100% !important;
        font-size: 11px !important;
        padding: 5px 6px !important;
      }
      .overlay-box { background: rgba(255,255,255,0.95) !important; border: 1px solid #d8dde3 !important; border-radius: 8px !important; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
      .overlay-box > summary { font-size: 13px !important; font-weight: 700 !important; }
      .overlay-box .overlay-box-content { max-height: 250px !important; overflow-y: auto !important; padding-right: 4px !important; }
      .overlay-box .overlay-option { display: flex !important; flex-wrap: nowrap !important; align-items: center !important; justify-content: space-between !important; gap: 6px 8px !important; margin: 5px 0 !important; }
      .overlay-box .overlay-option > label.rkrk-sel { flex: 1 1 0 !important; min-width: 0 !important; justify-content: flex-start !important; overflow: hidden !important; text-overflow: ellipsis !important; }
      .overlay-box .overlay-option .overlay-opacity { display: inline-flex !important; align-items: center !important; gap: 6px !important; flex: 0 0 auto !important; justify-content: flex-end !important; margin-left: 4px !important; }
      .overlay-box .overlay-option .overlay-opacity input[type="range"] { width: 78px !important; padding: 0 !important; accent-color: #48673A !important; }
      .overlay-box .overlay-option .overlay-opacity span { min-width: 30px !important; text-align: right !important; font-size: 11px !important; color: #4b5563 !important; }
      #objSidebarResizeHandle { position: fixed; top: 0; bottom: 0; width: 8px; cursor: col-resize; z-index: 1900; background: transparent; }
      #objSidebarResizeHandle:hover { background: rgba(90,110,140,0.12); }
      #objSidebarToggleBtn {
        position: fixed;
        top: 50%;
        transform: translateY(-50%);
        z-index: 1901;
        width: 20px !important;
        min-width: 20px !important;
        max-width: 20px !important;
        height: 40px !important;
        padding: 0 !important;
        margin: 0 !important;
        border-radius: 6px !important;
        font-size: 11px !important;
        line-height: 1 !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        writing-mode: horizontal-tb !important;
        letter-spacing: -1px;
      }
      #objObjectContextMenu {
        position: fixed;
        z-index: 2500;
        min-width: 170px;
        border: 1px solid #cfd7e3;
        border-radius: 8px;
        background: rgba(255,255,255,0.98);
        box-shadow: 0 8px 20px rgba(0,0,0,0.16);
        padding: 6px;
        display: none;
      }
      #objObjectContextMenu button {
        width: 100%;
        margin-top: 4px !important;
        text-align: left;
      }
      #objObjectContextMenu button:first-child {
        margin-top: 0 !important;
      }
      #objObjectContextMenu .obj-ctx-sep {
        margin: 8px 2px 4px;
        padding-top: 6px;
        border-top: 1px solid #e5e7eb;
        font-size: 11px;
        font-weight: 700;
        color: #64748b;
      }
      #objObjectContextMenu .obj-ctx-fill-btn {
        display: flex !important;
        align-items: center;
        gap: 8px;
      }
      #objObjectContextMenu .obj-ctx-swatch {
        width: 12px;
        height: 12px;
        border-radius: 2px;
        border: 1px solid rgba(0,0,0,.2);
        flex: 0 0 auto;
      }
      #objManagedToolsTop .obj-icon-btn,
      aside.panel .obj-action-bar > button.obj-icon-btn {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        min-width: 34px !important;
        width: 34px !important;
        padding: 6px !important;
        font-size: 16px !important;
        line-height: 1 !important;
        font-weight: 700 !important;
      }
      /* Icon-Regel oben überschreibt sonst display:none — versteckte Buttons (z. B. Polygon-Zeichnen bei Raster) bleiben sichtbar */
      #objManagedToolsTop .obj-icon-btn[hidden],
      #objManagedToolsTop .obj-icon-btn[style*="display: none"],
      aside.panel .obj-action-bar > button.obj-icon-btn[hidden],
      aside.panel .obj-action-bar > button.obj-icon-btn[style*="display: none"] {
        display: none !important;
      }
      #objManagedToolsTop .obj-icon-btn svg,
      aside.panel .obj-action-bar > button.obj-icon-btn svg {
        display: block;
        width: 16px;
        height: 16px;
      }
      #objManagedListPanel { margin-top: 4px !important; }
      #objManagedListPanel > summary { padding: 4px 6px !important; }
      #objManagedListPanel .panel-details-content { padding: 0 6px 4px !important; }
      #objManagedListEmpty { margin: 2px 0 !important; font-size: 10px !important; }
      #objManagedList {
        list-style: none;
        margin: 0;
        padding: 0;
        max-height: 180px;
        overflow-y: auto;
      }
      #objManagedList li {
        display: flex;
        flex-wrap: nowrap;
        align-items: center;
        gap: 4px;
        padding: 2px 2px;
        border-bottom: 1px solid #e5e7eb;
        font-size: 10px !important;
        line-height: 1.2;
        min-height: 0;
      }
      #objManagedList li.obj-list-active {
        background: rgba(245, 158, 11, 0.12);
      }
      #objManagedList li.obj-list-hidden {
        opacity: 0.45;
      }
      #objManagedListToolbar {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        margin: 2px 0 6px;
        align-items: center;
      }
      #objManagedListToolbar button {
        font-size: 10px !important;
        padding: 3px 6px !important;
        margin: 0 !important;
        line-height: 1.2 !important;
        cursor: pointer;
      }
      #objManagedList li.obj-list-group {
        background: #f1f5f9;
        border-bottom: 1px solid #cbd5e1;
        font-weight: 600;
      }
      #objManagedList li.obj-list-group.obj-list-hidden {
        opacity: 0.45;
      }
      #objManagedList li.obj-list-group.obj-list-collapsed {
        border-bottom-color: #94a3b8;
      }
      #objManagedList .obj-group-collapse {
        flex: 0 0 auto;
        width: 22px !important;
        min-width: 22px !important;
        height: 22px !important;
        padding: 0 !important;
        margin: 0 !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: 3px;
        background: #fff !important;
        color: #334155 !important;
        font-size: 11px !important;
        line-height: 1 !important;
        cursor: pointer;
      }
      #objManagedList li.obj-list-in-group {
        padding-left: 14px;
      }
      #objManagedList .obj-list-select {
        flex: 0 0 auto;
        margin: 0;
      }
      #objManagedList .obj-group-opacity {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        flex: 0 0 auto;
        font-size: 9px;
        color: #64748b;
        font-weight: 500;
      }
      #objManagedList .obj-group-opacity input[type="range"] {
        width: 64px;
        margin: 0;
        padding: 0;
      }
      #objManagedList li.obj-list-dragging {
        opacity: 0.55;
      }
      #objManagedList li.obj-list-drag-over {
        border-top: 2px solid #2563eb;
      }
      #objManagedList .obj-list-meta {
        display: flex;
        flex-wrap: nowrap;
        align-items: center;
        gap: 4px;
        flex: 1 1 auto;
        min-width: 0;
        overflow: hidden;
        cursor: grab;
      }
      #objManagedList .obj-list-name {
        flex: 0 1 auto;
        min-width: 0;
        font-weight: 600;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        cursor: text;
      }
      #objManagedList .obj-list-name-input {
        flex: 1 1 auto;
        min-width: 48px;
        max-width: 100%;
        box-sizing: border-box;
        margin: 0 !important;
        padding: 0 3px !important;
        height: 16px;
        font-size: 10px !important;
        font-weight: 600;
        line-height: 1.2 !important;
        border: 1px solid #94a3b8;
        border-radius: 2px;
        background: #fff;
        color: #0f172a;
      }
      #objMapHoverTip {
        position: fixed;
        z-index: 10050;
        display: none;
        pointer-events: none;
        background: rgba(15, 23, 42, 0.94);
        color: #fff;
        font-size: 12px;
        font-weight: 600;
        line-height: 1.25;
        padding: 5px 9px;
        border-radius: 6px;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.28);
        max-width: min(280px, calc(100vw - 24px));
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
      }
      #objMapHoverTip.show { display: block; }
      #objManagedList .obj-list-badge {
        flex: 0 0 auto;
        font-size: 9px;
        padding: 0 4px;
        border-radius: 3px;
        border: 1px solid #cbd5e1;
        color: #334155;
        background: #f8fafc;
        line-height: 1.35;
        white-space: nowrap;
      }
      #objManagedList .obj-list-badge.is-editing {
        border-color: #b45309;
        color: #fff;
        background: #c2410c;
        font-weight: 700;
      }
      #objManagedList .obj-list-badge.is-fixed {
        border-color: #3f6212;
        color: #fff;
        background: #4d7c0f;
        font-weight: 700;
      }
      #objManagedList .obj-list-type-icon {
        flex: 0 0 auto;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 16px;
        height: 16px;
        color: #475569;
        opacity: 0.9;
      }
      #objManagedList .obj-list-type-icon svg {
        width: 14px;
        height: 14px;
        display: block;
      }
      #objManagedList .obj-list-actions {
        display: flex;
        flex-wrap: nowrap;
        align-items: center;
        gap: 3px;
        flex: 0 0 auto;
        margin-left: auto;
      }
      #objManagedList button,
      #objManagedList .obj-list-actions button {
        margin-top: 0 !important;
        margin-bottom: 0 !important;
        padding: 2px 5px !important;
        font-size: 10px !important;
        line-height: 1.15 !important;
        white-space: nowrap;
      }
      #objManagedList .obj-list-actions button.obj-icon-btn {
        width: 26px !important;
        min-width: 26px !important;
        height: 26px !important;
        padding: 0 !important;
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
      }
      #objManagedList .obj-list-actions button.obj-icon-btn svg {
        width: 14px;
        height: 14px;
        display: block;
        pointer-events: none;
      }
      #objManagedList .obj-list-actions button.obj-list-fix {
        background: #3f6212 !important;
        border-color: #365314 !important;
        color: #fff !important;
        font-weight: 700 !important;
      }
      #objManagedList .obj-list-actions button.obj-list-edit {
        background: #3f6212 !important;
        border-color: #365314 !important;
        color: #fff !important;
        font-weight: 700 !important;
      }
      #objManagedList .obj-list-actions button.obj-list-delete,
      #objManagedList .obj-list-actions button:not(.obj-list-fix):not(.obj-list-edit):not(.obj-list-discard) {
        background: #44403c !important;
        border-color: #292524 !important;
        color: #fff !important;
        font-weight: 600 !important;
      }
      #objManagedList .obj-list-actions button.obj-list-discard {
        background: #7f1d1d !important;
        border-color: #450a0a !important;
        color: #fff !important;
        font-weight: 600 !important;
      }
      #objManagedList .obj-list-actions button.obj-list-visibility {
        color: #334155 !important;
        border-color: #cbd5e1 !important;
        background: #f8fafc !important;
      }
      aside.panel .obj-action-bar > button#objAddManagedBtn,
      aside.panel .obj-action-bar > button.obj-tool-fix {
        background: #3f6212 !important;
        border-color: #365314 !important;
        color: #fff !important;
        font-weight: 700 !important;
      }
      aside.panel .obj-action-bar > button#objDiscardBtn,
      aside.panel .obj-action-bar > button.obj-tool-discard {
        background: #7f1d1d !important;
        border-color: #450a0a !important;
        color: #fff !important;
        font-weight: 600 !important;
      }
      aside.panel .obj-action-bar > button#clearBtn,
      aside.panel .obj-action-bar > button.obj-tool-clear {
        background: #44403c !important;
        border-color: #292524 !important;
        color: #fff !important;
        font-weight: 600 !important;
      }
      aside.panel .obj-action-bar > button#drawBtn,
      aside.panel .obj-action-bar > button.obj-tool-draw {
        background: #1e3a5f !important;
        border-color: #0f2744 !important;
        color: #fff !important;
        font-weight: 600 !important;
      }
      aside.panel .obj-action-bar > button#drawBtn.selected-flight-reference {
        background: #b45309 !important;
        border-color: #92400e !important;
      }
    `;
  }

  function installObjectContextMenu() {
    if (document.getElementById("objObjectContextMenu")) return;
    const menu = document.createElement("div");
    menu.id = "objObjectContextMenu";
    menu.innerHTML = ""
      + "<button id='objCtxEditBtn' type='button'>Bearbeiten / Verschieben</button>"
      + "<button id='objCtxForwardBtn' type='button'>Eine Ebene nach vorne</button>"
      + "<button id='objCtxBackwardBtn' type='button'>Eine Ebene nach hinten</button>"
      + "<button id='objCtxDeleteBtn' type='button'>Lösen / Löschen</button>"
      + "<div id='objCtxFillSection' hidden>"
      + "<div class='obj-ctx-sep' id='objCtxFillLabel'>Farbe</div>"
      + "<button type='button' class='obj-ctx-fill-btn' data-obj-ctx-fill='0'><span class='obj-ctx-swatch' style='background:#00aa00'></span>Grün</button>"
      + "<button type='button' class='obj-ctx-fill-btn' data-obj-ctx-fill='1'><span class='obj-ctx-swatch' style='background:#ff2222'></span>Rot</button>"
      + "<button type='button' class='obj-ctx-fill-btn' data-obj-ctx-fill='2'><span class='obj-ctx-swatch' style='background:#ff8800'></span>Orange</button>"
      + "<button type='button' class='obj-ctx-fill-btn' data-obj-ctx-fill='3'><span class='obj-ctx-swatch' style='background:#ffd400'></span>Gelb</button>"
      + "<button type='button' class='obj-ctx-fill-btn' data-obj-ctx-fill='4'><span class='obj-ctx-swatch' style='background:#888888'></span>Grau</button>"
      + "<button type='button' class='obj-ctx-fill-btn' data-obj-ctx-fill='neutral'><span class='obj-ctx-swatch' style='background:linear-gradient(135deg,#eee 50%,#ddd 50%)'></span>Standard / Durchsichtig</button>"
      + "</div>";
    document.body.appendChild(menu);
    function hideMenu() {
      menu.style.display = "none";
      contextMenuTargetObjectId = null;
      contextMenuFillTarget = null;
      const fillSec = document.getElementById("objCtxFillSection");
      if (fillSec) fillSec.hidden = true;
    }
    window.__rkrkHideObjectContextMenu = hideMenu;
    window.__rkrkShowObjectContextMenu = function (clientX, clientY, objectId, fillTarget) {
      contextMenuTargetObjectId = objectId;
      contextMenuFillTarget = fillTarget || null;
      const fillSec = document.getElementById("objCtxFillSection");
      const fillLabel = document.getElementById("objCtxFillLabel");
      if (fillSec) {
        fillSec.hidden = !fillTarget;
        if (fillLabel && fillTarget) {
          if (fillTarget.kind === "managed-grid-cell") {
            const cell = (Number.isInteger(fillTarget.n) && Number.isInteger(fillTarget.e)
              && typeof eastLabel === "function")
              ? `${eastLabel(fillTarget.e)}${fillTarget.n + 1}`
              : "";
            fillLabel.textContent = cell ? `Kachelfarbe (${cell})` : "Kachelfarbe";
          } else if (fillTarget.kind === "managed-freehand") {
            fillLabel.textContent = "Linienfarbe";
          } else if (fillTarget.kind === "managed-point") {
            fillLabel.textContent = "Punktfarbe";
          } else {
            fillLabel.textContent = "Füllfarbe";
          }
        }
      }
      if (typeof hideGridCellFillMenu === "function") {
        try { hideGridCellFillMenu(); } catch (_) { /* noop */ }
      }
      menu.style.display = "block";
      const menuW = 210;
      const menuH = fillTarget ? 340 : 160;
      const left = Math.min(window.innerWidth - menuW - 8, Math.max(8, clientX));
      const top = Math.min(window.innerHeight - menuH - 8, Math.max(8, clientY));
      menu.style.left = `${left}px`;
      menu.style.top = `${top}px`;
      managedStatusText(`Objekt ${objectId}: Kontextmenü geöffnet.`);
    };
    const editBtn = document.getElementById("objCtxEditBtn");
    const delBtn = document.getElementById("objCtxDeleteBtn");
    const fwdBtn = document.getElementById("objCtxForwardBtn");
    const backBtn = document.getElementById("objCtxBackwardBtn");
    if (editBtn) {
      editBtn.addEventListener("click", () => {
        if (!contextMenuTargetObjectId) return;
        beginEditManagedObject(contextMenuTargetObjectId);
        hideMenu();
      });
    }
    if (fwdBtn) {
      fwdBtn.addEventListener("click", () => {
        if (!contextMenuTargetObjectId) return;
        moveManagedObjectZOrder(contextMenuTargetObjectId, 1);
        hideMenu();
      });
    }
    if (backBtn) {
      backBtn.addEventListener("click", () => {
        if (!contextMenuTargetObjectId) return;
        moveManagedObjectZOrder(contextMenuTargetObjectId, -1);
        hideMenu();
      });
    }
    if (delBtn) {
      delBtn.addEventListener("click", () => {
        if (!contextMenuTargetObjectId) return;
        const target = managedObjects.find((o) => o.id === contextMenuTargetObjectId);
        if (!target) {
          hideMenu();
          return;
        }
        if (!window.confirm(`«${managedObjectDisplayName(target)}» löschen?`)) {
          hideMenu();
          return;
        }
        deleteManagedObjectById(contextMenuTargetObjectId);
        hideMenu();
      });
    }
    menu.addEventListener("click", (ev) => {
      const btn = ev.target.closest("[data-obj-ctx-fill]");
      if (!btn || !contextMenuFillTarget) return;
      ev.preventDefault();
      ev.stopPropagation();
      const v = btn.getAttribute("data-obj-ctx-fill");
      const t = contextMenuFillTarget;
      if (t.kind === "managed-grid-cell" && typeof window.__rkrkApplyManagedGridCellFill === "function") {
        window.__rkrkApplyManagedGridCellFill(t.objectId, t.n, t.e, v);
      } else if (t.kind === "managed-polygon" && typeof window.__rkrkApplyManagedPolygonFill === "function") {
        window.__rkrkApplyManagedPolygonFill(t.objectId, v);
      } else if (t.kind === "managed-freehand" && typeof window.__rkrkApplyManagedFreehandColor === "function") {
        window.__rkrkApplyManagedFreehandColor(t.objectId, v);
      } else if (t.kind === "managed-point" && typeof window.__rkrkApplyManagedPointColor === "function") {
        window.__rkrkApplyManagedPointColor(t.objectId, t.labelIndex, v);
      }
      hideMenu();
    });
    document.addEventListener("click", (evt) => {
      if (!menu.contains(evt.target)) {
        hideMenu();
      }
    });
    window.addEventListener("blur", hideMenu);
  }

  function renderFreehandDraftPreview() {
    if (typeof previewSource === "undefined" || !previewSource) return;
    previewSource.clear();
    if (!Array.isArray(freehandDraftCoords) || freehandDraftCoords.length < 1) return;
    const pfeilMode = exportTypeEl && normalizeObjectType(exportTypeEl.value) === "pfeil";
    const colorEl = document.getElementById(pfeilMode ? "objPfeilColor" : "objFreehandColor");
    const widthEl = document.getElementById(pfeilMode ? "objPfeilWidth" : "objFreehandWidth");
    const color = colorEl ? String(colorEl.value || "#e11d48") : "#e11d48";
    const width = widthEl ? Math.max(1, Number(widthEl.value) || 3) : 3;
    // Offene Punktreihe — niemals schliessen (kein Ring / kein Polygon / kein Raster)
    const openCoords = freehandDraftCoords.map((p) => [Number(p[0]), Number(p[1])]);
    if (openCoords.length >= 3) {
      const a = openCoords[0];
      const b = openCoords[openCoords.length - 1];
      if (Math.abs(a[0] - b[0]) < 1e-9 && Math.abs(a[1] - b[1]) < 1e-9) {
        openCoords.pop();
      }
    }
    const mapCoords = openCoords.map((p) => lonLatToMapCoord(Number(p[0]), Number(p[1])));
    if (mapCoords.length >= 2) {
      const line = new ol.Feature({ geometry: new ol.geom.LineString(mapCoords) });
      line.setStyle(new ol.style.Style({
        stroke: new ol.style.Stroke({ color: "#f59e0b", width: Math.max(width, 3), lineDash: [8, 6] })
      }));
      const tipStyles = (pfeilMode && typeof buildPfeilEndOlStyles === "function")
        ? buildPfeilEndOlStyles(mapCoords, color, width, (document.getElementById("objPfeilEnd") || {}).value || "arrow", 2)
        : [];
      const lineColor = new ol.Feature({ geometry: new ol.geom.LineString(mapCoords) });
      lineColor.setStyle([
        new ol.style.Style({
          stroke: new ol.style.Stroke({ color, width, lineDash: [8, 6] })
        })
      ].concat(tipStyles));
      previewSource.addFeature(lineColor);
      previewSource.addFeature(line);
    }
    // Stützpunkte / Handles zum Verschieben
    mapCoords.forEach((c, idx) => {
      const pt = new ol.Feature({ geometry: new ol.geom.Point(c) });
      pt.set("freehandVertexIndex", idx);
      pt.set("editorType", "freehand-vertex");
      const active = idx === freehandVertexDragIndex;
      pt.setStyle(new ol.style.Style({
        image: new ol.style.Circle({
          radius: active ? 8 : (idx === 0 || idx === mapCoords.length - 1 ? 6 : 5),
          fill: new ol.style.Fill({
            color: active ? "#f59e0b"
              : (idx === 0 ? "#16a34a" : (idx === mapCoords.length - 1 ? "#dc2626" : color))
          }),
          stroke: new ol.style.Stroke({ color: "#ffffff", width: 2 })
        })
      }));
      previewSource.addFeature(pt);
    });
    if (typeof previewActive !== "undefined") previewActive = true;
  }
  window.__rkrkRenderFreehandPreview = renderFreehandDraftPreview;

  function setFreehandDragPanEnabled(enabled) {
    if (typeof map === "undefined" || !map || typeof map.getInteractions !== "function") return;
    map.getInteractions().forEach((interaction) => {
      if (interaction instanceof ol.interaction.DragPan) {
        interaction.setActive(!!enabled);
      }
    });
  }

  function resetFreehandInteractionState() {
    freehandDraftCoords = [];
    freehandDrawing = false;
    freehandVertexDragIndex = -1;
    freehandPointerSession = null;
    freehandSuppressClick = false;
  }

  function updateFreehandStatus(msg) {
    const pfeil = exportTypeEl && normalizeObjectType(exportTypeEl.value) === "pfeil";
    const st = document.getElementById(pfeil ? "objPfeilStatus" : "objFreehandStatus");
    if (st && msg) st.textContent = msg;
    else if (st) {
      const armed = !!(typeof drawingArmed !== "undefined" && drawingArmed);
      if (pfeil) {
        st.textContent = freehandDraftCoords.length >= 2
          ? "Pfeil gesetzt — Endpunkte verschieben oder Fixieren."
          : (freehandDraftCoords.length === 1
            ? "Start gesetzt — Endpunkt auf der Karte klicken."
            : (armed
              ? "Zeichnen aktiv: Startpunkt, dann Endpunkt setzen."
              : "Zuerst «Zeichnen», dann Start- und Endpunkt auf die Karte setzen."));
      } else {
        st.textContent = freehandDraftCoords.length >= 2
          ? `Linie mit ${freehandDraftCoords.length} Punkten — Punkte verschieben oder Fixieren.`
          : (freehandDraftCoords.length === 1
            ? "1 Punkt — klicken oder halten zum Fortsetzen."
            : (armed
              ? "Zeichnen aktiv: halten & ziehen oder klicken."
              : "Zuerst «Zeichnen», dann halten & ziehen oder klicken."));
      }
    }
  }

  function installFreehandDrawing(retry) {
    if (typeof map === "undefined" || !map) {
      const n = Number(retry) || 0;
      if (n < 40) setTimeout(() => installFreehandDrawing(n + 1), 120);
      return;
    }
    if (map.__objFreehandInstalled) return;
    map.__objFreehandInstalled = true;
    const STROKE_MOVE_PX = 5;
    const toLonLatFromPixel = (pixel) => {
      const coord = map.getCoordinateFromPixel(pixel);
      if (!coord) return null;
      const ll = ol.proj.transform(coord, map.getView().getProjection(), "EPSG:4326");
      return [Number(ll[0]), Number(ll[1])];
    };
    const isFreehandMode = () => {
      if (!exportTypeEl) return false;
      const m = normalizeObjectType(exportTypeEl.value);
      return m === "freehand" || m === "pfeil";
    };
    const isPfeilMode = () => exportTypeEl && normalizeObjectType(exportTypeEl.value) === "pfeil";
    const isEditingUnfixedFreehand = () => {
      if (!selectedManagedObjectId) return false;
      const obj = managedObjects.find((o) => o && Number(o.id) === Number(selectedManagedObjectId));
      return !!(obj && !obj.fixed && (obj.type === "freehand" || obj.type === "pfeil"));
    };
    const isFreehandArmed = () => !!(typeof drawingArmed !== "undefined" && drawingArmed);
    /** Neu zeichnen nur mit «Zeichnen»; Endpunkte einer Bearbeitung verschieben auch ohne. */
    const canEditFreehandVertices = () => isFreehandArmed() || isEditingUnfixedFreehand();
    const canStartFreehandStroke = () => isFreehandArmed();
    const hitFreehandVertex = (pixel) => {
      if (!previewSource || !Array.isArray(freehandDraftCoords) || freehandDraftCoords.length < 1) return -1;
      let found = -1;
      map.forEachFeatureAtPixel(pixel, (feature) => {
        const idx = feature.get("freehandVertexIndex");
        if (Number.isInteger(idx) && idx >= 0 && idx < freehandDraftCoords.length) {
          found = idx;
          return feature;
        }
        return null;
      }, {
        hitTolerance: 10,
        layerFilter: (layer) => layer && typeof layer.getSource === "function" && layer.getSource() === previewSource
      });
      return found;
    };
    const appendPoint = (lonLat, opts) => {
      if (!lonLat) return false;
      const skipRender = opts && opts.skipRender;
      const last = freehandDraftCoords[freehandDraftCoords.length - 1];
      if (!last || Math.abs(last[0] - lonLat[0]) > 1e-7 || Math.abs(last[1] - lonLat[1]) > 1e-7) {
        if (isPfeilMode()) {
          if (freehandDraftCoords.length >= 2) {
            freehandDraftCoords[1] = [Number(lonLat[0]), Number(lonLat[1])];
          } else {
            freehandDraftCoords.push([Number(lonLat[0]), Number(lonLat[1])]);
          }
        } else {
          freehandDraftCoords.push([Number(lonLat[0]), Number(lonLat[1])]);
        }
        if (!skipRender) renderFreehandDraftPreview();
        return true;
      }
      return false;
    };
    const syncFreehandEditIfNeeded = () => {
      if (selectedManagedObjectId) {
        try { syncEditingObjectFromUi(); } catch (_) { /* noop */ }
        try { renderManagedObjects(); } catch (_) { /* noop */ }
      }
      try { refreshObjectActionButtons(); } catch (_) { /* noop */ }
    };
    const endVertexDrag = () => {
      if (freehandVertexDragIndex < 0) return;
      freehandVertexDragIndex = -1;
      setFreehandDragPanEnabled(true);
      if (map.getTargetElement()) map.getTargetElement().style.cursor = "";
      renderFreehandDraftPreview();
      updateFreehandStatus();
      syncFreehandEditIfNeeded();
      managedStatusText("Freihandpunkt verschoben.");
    };
    const endStroke = () => {
      const hadStroke = freehandDrawing;
      const session = freehandPointerSession;
      freehandPointerSession = null;
      if (freehandVertexDragIndex >= 0) {
        endVertexDrag();
        return;
      }
      if (!hadStroke && !session) return;
      freehandDrawing = false;
      setFreehandDragPanEnabled(true);
      if (map.getTargetElement()) map.getTargetElement().style.cursor = "";
      if (hadStroke) {
        freehandSuppressClick = true;
        updateFreehandStatus();
        syncFreehandEditIfNeeded();
        managedStatusText(freehandDraftCoords.length >= 2
          ? "Freihandlinie bereit — Punkte verschieben oder Fixieren."
          : "Freihandlinie zu kurz.");
      }
      // Click-add happens on singleclick when no stroke was drawn
    };
    const onPointerDown = (evt) => {
      if (!isFreehandMode()) return false;
      if (evt.button != null && evt.button !== 0) return false;
      const pixel = map.getEventPixel(evt);
      const vIdx = hitFreehandVertex(pixel);
      if (vIdx >= 0) {
        if (!canEditFreehandVertices()) return false; // Karte navigieren
        freehandVertexDragIndex = vIdx;
        freehandPointerSession = null;
        freehandDrawing = false;
        freehandSuppressClick = true;
        setFreehandDragPanEnabled(false);
        if (map.getTargetElement()) map.getTargetElement().style.cursor = "grabbing";
        renderFreehandDraftPreview();
        updateFreehandStatus(`Punkt ${vIdx + 1} verschieben…`);
        if (evt.cancelable) evt.preventDefault();
        if (typeof evt.stopPropagation === "function") evt.stopPropagation();
        return true;
      }
      // Neu zeichnen nur wenn «Zeichnen» aktiv — sonst Pan/Zoom
      if (!canStartFreehandStroke()) return false;
      freehandPointerSession = {
        startPixel: pixel.slice(),
        startLonLat: toLonLatFromPixel(pixel),
        stroked: false
      };
      freehandDrawing = false;
      setFreehandDragPanEnabled(false);
      if (map.getTargetElement()) map.getTargetElement().style.cursor = "crosshair";
      if (evt.cancelable) evt.preventDefault();
      return true;
    };
    const onPointerMove = (evt) => {
      if (!isFreehandMode()) return;
      const pixel = map.getEventPixel(evt);
      if (freehandVertexDragIndex >= 0) {
        const ll = toLonLatFromPixel(pixel);
        if (ll && freehandDraftCoords[freehandVertexDragIndex]) {
          freehandDraftCoords[freehandVertexDragIndex] = ll;
          renderFreehandDraftPreview();
        }
        if (evt.cancelable) evt.preventDefault();
        return;
      }
      if (!freehandPointerSession) return;
      const sx = freehandPointerSession.startPixel[0];
      const sy = freehandPointerSession.startPixel[1];
      const dx = pixel[0] - sx;
      const dy = pixel[1] - sy;
      const dist2 = dx * dx + dy * dy;
      const thr2 = STROKE_MOVE_PX * STROKE_MOVE_PX;
      if (!freehandPointerSession.stroked) {
        if (dist2 < thr2) return;
        // Hold-to-draw: dense points along path (append to shared draft)
        freehandPointerSession.stroked = true;
        freehandDrawing = true;
        freehandSuppressClick = true;
        if (freehandPointerSession.startLonLat) {
          appendPoint(freehandPointerSession.startLonLat, { skipRender: true });
        }
        appendPoint(toLonLatFromPixel(pixel));
        updateFreehandStatus("Zeichne…");
        managedStatusText("Freihandlinie: halten & ziehen — oder klicken für Punkte.");
        if (evt.cancelable) evt.preventDefault();
        return;
      }
      appendPoint(toLonLatFromPixel(pixel));
      if (evt.cancelable) evt.preventDefault();
    };
    // Viewport DOM events are more reliable than map pointer* alone (DragPan race).
    const viewport = map.getViewport();
    if (viewport) {
      viewport.addEventListener("pointerdown", onPointerDown, { passive: false });
      viewport.addEventListener("pointermove", onPointerMove, { passive: false });
      viewport.addEventListener("pointerup", endStroke);
      viewport.addEventListener("pointercancel", endStroke);
      viewport.addEventListener("lostpointercapture", endStroke);
    }
    document.addEventListener("pointerup", endStroke);
    document.addEventListener("mouseup", endStroke);
    map.on("pointerdrag", (evt) => {
      if (!isFreehandMode()) return;
      if (freehandVertexDragIndex >= 0) {
        const ll = toLonLatFromPixel(evt.pixel);
        if (ll && freehandDraftCoords[freehandVertexDragIndex]) {
          freehandDraftCoords[freehandVertexDragIndex] = ll;
          renderFreehandDraftPreview();
        }
        return;
      }
      if (!freehandDrawing) return;
      appendPoint(toLonLatFromPixel(evt.pixel));
    });
    // Click-to-add vertex; never set grid basepoint in freehand mode
    map.on("singleclick", (evt) => {
      if (!isFreehandMode()) return;
      if (!canStartFreehandStroke()) return; // ohne «Zeichnen»: Karte navigieren
      if (evt && typeof evt.stopPropagation === "function") evt.stopPropagation();
      if (evt && typeof evt.preventDefault === "function") evt.preventDefault();
      if (freehandDrawing || freehandVertexDragIndex >= 0) return;
      if (freehandSuppressClick) {
        freehandSuppressClick = false;
        return;
      }
      // Don't add when clicking an existing handle (select/drag only)
      if (hitFreehandVertex(evt.pixel) >= 0) return;
      const ll = toLonLatFromPixel(evt.pixel);
      if (!ll) return;
      appendPoint(ll);
      updateFreehandStatus();
      syncFreehandEditIfNeeded();
      managedStatusText(isPfeilMode()
        ? (freehandDraftCoords.length >= 2
          ? "Pfeil: Endpunkt gesetzt — Punkte verschieben oder Fixieren."
          : "Pfeil: Startpunkt gesetzt — Endpunkt setzen.")
        : (freehandDraftCoords.length >= 2
          ? "Freihand: Punkt hinzugefügt — ziehen zum Verschieben, Fixieren zum Abschluss."
          : "Freihand: erster Punkt gesetzt — weiter klicken oder halten & ziehen."));
    });
    if (viewport) {
      viewport.addEventListener("click", (evt) => {
        if (!isFreehandMode()) return;
        if (!canStartFreehandStroke() && freehandVertexDragIndex < 0 && !freehandDrawing) return;
        evt.stopPropagation();
      }, true);
    }
    // «Zeichnen» toggelt drawingArmed (wie Raster/Kreis) — sonst Karte navigieren
    const drawBtn = document.getElementById("drawBtn");
    if (drawBtn && !drawBtn.__objFreehandArm) {
      drawBtn.__objFreehandArm = true;
      drawBtn.addEventListener("click", (evt) => {
        if (!isFreehandMode()) return;
        if (evt && typeof evt.preventDefault === "function") evt.preventDefault();
        if (evt && typeof evt.stopImmediatePropagation === "function") evt.stopImmediatePropagation();
        drawingArmed = !drawingArmed;
        freehandDrawing = false;
        freehandVertexDragIndex = -1;
        freehandPointerSession = null;
        freehandSuppressClick = false;
        setFreehandDragPanEnabled(true);
        if (map.getTargetElement()) map.getTargetElement().style.cursor = "";
        if (drawingArmed) {
          freehandDraftCoords = [];
          if (typeof clearPreview === "function") clearPreview();
          if (isPfeilMode()) {
            updateFreehandStatus("Zeichnen aktiv: Startpunkt, dann Endpunkt setzen.");
            managedStatusText("Pfeil: Zeichnen aktiv — auf die Karte klicken.");
          } else {
            updateFreehandStatus("Zeichnen aktiv: halten & ziehen oder klicken.");
            managedStatusText("Freihand: Zeichnen aktiv — halten oder klicken.");
          }
        } else {
          updateFreehandStatus("Zeichnen gestoppt — Karte navigieren.");
          managedStatusText("Zeichnen gestoppt.");
        }
        try { refreshObjectActionButtons(); } catch (_) { /* noop */ }
      }, true);
    }
    try {
      window.isFreehandDrawBlockingPick = function () {
        if (!isFreehandMode()) return false;
        return !!(isFreehandArmed() || freehandDrawing || freehandVertexDragIndex >= 0 || freehandPointerSession);
      };
    } catch (_) { /* noop */ }
  }

  function installSidebarResizeAndCollapse() {
    const panel = document.querySelector("aside.panel");
    if (!panel || panel.dataset.objSidebarInstalled === "1") return;
    panel.dataset.objSidebarInstalled = "1";
    const bodyEl = document.body;
    const mapEl = document.getElementById("map");
    const STORAGE_WIDTH = "obj_sidebar_width_px_v1";
    const STORAGE_COLLAPSED = "obj_sidebar_collapsed_v1";
    const minW = 260;
    const maxW = 680;
    const defaultW = 360;
    const handle = document.createElement("div");
    handle.id = "objSidebarResizeHandle";
    document.body.appendChild(handle);
    const toggleBtn = document.createElement("button");
    toggleBtn.id = "objSidebarToggleBtn";
    toggleBtn.type = "button";
    document.body.appendChild(toggleBtn);
    function clamp(v) {
      const n = Number(v);
      if (!Number.isFinite(n)) return defaultW;
      return Math.max(minW, Math.min(maxW, Math.round(n)));
    }
    function refreshMapSizeWithRetry(retry = 0) {
      if (typeof map !== "undefined" && map && typeof map.updateSize === "function") {
        map.updateSize();
        requestAnimationFrame(() => {
          if (typeof map !== "undefined" && map && typeof map.updateSize === "function") {
            map.updateSize();
          }
        });
        return;
      }
      if (retry < 12) {
        setTimeout(() => refreshMapSizeWithRetry(retry + 1), 120);
      }
    }
    function applyLayout(width, collapsed) {
      const w = clamp(width);
      try {
        bodyEl.classList.toggle("obj-sidebar-collapsed", !!collapsed);
        document.documentElement.classList.toggle("obj-sidebar-collapsed", !!collapsed);
      } catch (_) { /* noop */ }
      if (collapsed) {
        bodyEl.style.gridTemplateColumns = `1fr`;
        panel.style.display = "none";
        if (mapEl) {
          mapEl.style.gridColumn = "1 / -1";
          mapEl.style.width = "100%";
          mapEl.style.height = "100vh";
        }
        handle.style.left = "0px";
        toggleBtn.textContent = ">>";
        toggleBtn.style.left = "0px";
      } else {
        bodyEl.style.gridTemplateColumns = `${w}px 1fr`;
        panel.style.display = "";
        if (mapEl) {
          mapEl.style.gridColumn = "";
          mapEl.style.width = "100%";
          mapEl.style.height = "100vh";
        }
        handle.style.left = `${w - 4}px`;
        toggleBtn.textContent = "<<";
        toggleBtn.style.left = `${w - 8}px`;
      }
      // OpenLayers needs explicit resize after layout changes, including
      // the case where the sidebar is already collapsed during startup.
      refreshMapSizeWithRetry(0);
    }
    let width = clamp(localStorage.getItem(STORAGE_WIDTH) || defaultW);
    let collapsed = localStorage.getItem(STORAGE_COLLAPSED) === "1";
    applyLayout(width, collapsed);
    refreshMapSizeWithRetry(0);
    let dragActive = false;
    function onMove(evt) {
      if (!dragActive || collapsed) return;
      width = clamp(evt.clientX);
      applyLayout(width, false);
    }
    function onUp() {
      if (!dragActive) return;
      dragActive = false;
      localStorage.setItem(STORAGE_WIDTH, String(width));
      window.removeEventListener("mousemove", onMove);
      window.removeEventListener("mouseup", onUp);
      document.body.style.userSelect = "";
    }
    handle.addEventListener("mousedown", (evt) => {
      if (collapsed) return;
      evt.preventDefault();
      dragActive = true;
      document.body.style.userSelect = "none";
      window.addEventListener("mousemove", onMove);
      window.addEventListener("mouseup", onUp);
    });
    toggleBtn.addEventListener("click", () => {
      collapsed = !collapsed;
      localStorage.setItem(STORAGE_COLLAPSED, collapsed ? "1" : "0");
      if (!collapsed) {
        width = clamp(localStorage.getItem(STORAGE_WIDTH) || width);
      }
      applyLayout(width, collapsed);
    });
    window.addEventListener("resize", () => applyLayout(width, collapsed));
  }

  function isObjectInEditMode() {
    if (!selectedManagedObjectId) return false;
    const obj = managedObjects.find((o) => o.id === selectedManagedObjectId);
    return !!(obj && !obj.fixed);
  }

  /** Objekttyp im Menü gewählt = zum Zeichnen markiert. */
  function isDrawingMarkedForStyle() {
    const mode = normalizeObjectType(exportTypeEl ? exportTypeEl.value : "");
    return mode === "grid" || mode === "circle" || mode === "circles"
      || mode === "polygon" || mode === "freehand" || mode === "pfeil"
      || mode === "points" || mode === "textfields";
  }

  function currentStyleEditMode() {
    // Nur wenn ein Objekt aktiv in Bearbeitung (Bearbeiten / unfixiert markiert)
    if (isObjectInEditMode()) {
      const obj = managedObjects.find((o) => o.id === selectedManagedObjectId);
      return normalizeObjectType(obj && obj.type);
    }
    return "";
  }

  function ensureStyleBoxSections() {
    const box = document.getElementById("styleBox");
    if (!box) return;
    const content = box.querySelector(".style-box-content");
    if (!content) return;
    if (!document.getElementById("stylePointSection")) {
      const pointInput = document.getElementById("objPointColor");
      if (pointInput) {
        const wrap = document.createElement("div");
        wrap.id = "stylePointSection";
        const title = Array.from(content.querySelectorAll(".style-section-title"))
          .find((el) => /punkt/i.test(el.textContent || ""));
        const row = pointInput.closest(".style-row");
        if (title && row) {
          content.insertBefore(wrap, title);
          wrap.appendChild(title);
          wrap.appendChild(row);
        }
      }
    }
    if (!document.getElementById("styleLabelSection")) {
      const textInput = document.getElementById("objLabelTextColor");
      const fontInput = document.getElementById("objLabelFontSize");
      if (textInput) {
        const wrap = document.createElement("div");
        wrap.id = "styleLabelSection";
        const title = Array.from(content.querySelectorAll(".style-section-title"))
          .find((el) => /textfeld/i.test(el.textContent || ""));
        const row = textInput.closest(".style-row");
        const rowFont = fontInput ? fontInput.closest(".style-row-compact, .style-row") : null;
        if (title && row) {
          content.insertBefore(wrap, title);
          wrap.appendChild(title);
          wrap.appendChild(row);
          if (rowFont) wrap.appendChild(rowFont);
        }
      }
    }
    if (!document.getElementById("styleFreehandSection")) {
      const wrap = document.createElement("div");
      wrap.id = "styleFreehandSection";
      wrap.style.display = "none";
      wrap.innerHTML = ""
        + "<div class='style-section-title'>Freihand</div>"
        + "<div class='style-row'>"
        + "  <label for='styleFreehandColor' style='margin:0;'>Linienfarbe</label>"
        + "  <input id='styleFreehandColor' class='style-color-input' type='color' value='#e11d48' />"
        + "  <span class='swatch'></span>"
        + "</div>"
        + "<div class='style-row-compact'>"
        + "  <label for='styleFreehandWidth' style='margin:0;'>Linienstärke (px)</label>"
        + "  <input id='styleFreehandWidth' type='number' min='1' max='20' step='1' value='3' />"
        + "</div>";
      const circles = document.getElementById("styleCirclesSection");
      if (circles) content.insertBefore(wrap, circles);
      else content.appendChild(wrap);
    }
    if (!document.getElementById("stylePfeilSection")) {
      const wrap = document.createElement("div");
      wrap.id = "stylePfeilSection";
      wrap.style.display = "none";
      wrap.innerHTML = ""
        + "<div class='style-section-title'>Pfeil</div>"
        + "<div class='style-row'>"
        + "  <label for='stylePfeilColor' style='margin:0;'>Farbe</label>"
        + "  <input id='stylePfeilColor' class='style-color-input' type='color' value='#e11d48' />"
        + "  <span class='swatch'></span>"
        + "</div>"
        + "<div class='style-row-compact'>"
        + "  <label for='stylePfeilWidth' style='margin:0;'>Dicke (px)</label>"
        + "  <input id='stylePfeilWidth' type='number' min='1' max='20' step='1' value='3' />"
        + "</div>"
        + "<div class='style-row-compact'>"
        + "  <label for='stylePfeilEnd' style='margin:0;'>Enden</label>"
        + "  <select id='stylePfeilEnd'>"
        + "    <option value='arrow'>Einseitig (Pfeil)</option>"
        + "    <option value='arrows'>Beidseitig</option>"
        + "    <option value='dots'>Punkte</option>"
        + "  </select>"
        + "</div>";
      const circles = document.getElementById("styleCirclesSection");
      if (circles) content.insertBefore(wrap, circles);
      else content.appendChild(wrap);
    }
    ensureStyleParamLiveSync();
  }

  /** Stileinstellungen sofort in Parametermenu + Kartenanzeige (auch beim Bearbeiten). */
  function applyUiStylesToEditingObject(opts) {
    const sourceId = opts && opts.sourceId ? String(opts.sourceId) : "";
    const pairs = [
      ["styleFreehandColor", "objFreehandColor"],
      ["styleFreehandWidth", "objFreehandWidth"]
    ];
    pairs.forEach(([styleId, paramId]) => {
      const styleEl = document.getElementById(styleId);
      const paramEl = document.getElementById(paramId);
      if (!styleEl || !paramEl) return;
      if (sourceId === paramId) {
        if (styleEl.value !== paramEl.value) styleEl.value = paramEl.value;
      } else if (sourceId === styleId || !sourceId) {
        if (paramEl.value !== styleEl.value) paramEl.value = styleEl.value;
      } else if (styleEl.value !== paramEl.value) {
        // Unbekanntes Feld: Style-Box als Quelle
        paramEl.value = styleEl.value;
      }
    });

    // Punkte/Text: aktuelle Label-Styles aus UI in labeledPoints schreiben
    try {
      const modeNow = normalizeObjectType(
        (typeof exportTypeEl !== "undefined" && exportTypeEl) ? exportTypeEl.value : ""
      );
      if ((modeNow === "points" || modeNow === "textfields")
          && Array.isArray(labeledPoints) && labeledPoints.length) {
        const colorEl = document.getElementById("objPointColor");
        const textColorEl = document.getElementById("objLabelTextColor");
        const fontEl = document.getElementById("objLabelFontSize") || document.getElementById("objLabelFontSizeLeft");
        const idx = (typeof selectedLabelPointIndex === "number" && selectedLabelPointIndex >= 0)
          ? selectedLabelPointIndex
          : 0;
        const lp = labeledPoints[idx];
        if (lp) {
          if (colorEl && colorEl.value) lp.color = String(colorEl.value);
          if (sourceId === "objPointColor" && colorEl && textColorEl && !isTextOnlyLabelType(lp.type)) {
            textColorEl.value = colorEl.value;
            lp.textColor = String(colorEl.value);
          } else if (textColorEl && textColorEl.value) {
            lp.textColor = String(textColorEl.value);
          }
          if (fontEl && Number.isFinite(Number(fontEl.value))) {
            lp.fontSize = Math.max(8, Math.min(72, Number(fontEl.value)));
          }
        }
      }
    } catch (_) { /* noop */ }

    try { syncEditingObjectFromUi(); } catch (_) { /* noop */ }

    const mode = normalizeObjectType(
      (typeof currentStyleEditMode === "function" ? currentStyleEditMode() : null)
      || ((typeof exportTypeEl !== "undefined" && exportTypeEl) ? exportTypeEl.value : "")
      || ""
    );
    try {
      if (mode === "freehand" || mode === "pfeil") renderFreehandDraftPreview();
      else if (mode === "polygon" && typeof renderPolygonEditor === "function") renderPolygonEditor();
      else if ((mode === "points" || mode === "textfields")) {
        const renderFn = (typeof renderLabeledPoints === "function")
          ? (renderLabeledPoints.__objListSyncOrig || renderLabeledPoints)
          : null;
        if (renderFn) renderFn();
      } else if ((mode === "grid" || mode === "circle" || mode === "circles")
          && typeof drawPreview === "function"
          && typeof previewActive !== "undefined" && previewActive
          && Array.isArray(selectedLonLat) && selectedLonLat.length === 2) {
        drawPreview();
      } else if (typeof refreshMapStyles === "function") {
        refreshMapStyles();
      }
    } catch (_) { /* noop */ }
    try { renderManagedObjects(); } catch (_) { /* noop */ }
    try { if (typeof updateSwatches === "function") updateSwatches(); } catch (_) { /* noop */ }
    try { if (typeof window.notifyLiveShareChange === "function") window.notifyLiveShareChange(); } catch (_) { /* noop */ }
  }
  window.applyUiStylesToEditingObject = applyUiStylesToEditingObject;

  function ensureStyleParamLiveSync() {
    const bindPair = (aId, bId) => {
      const a = document.getElementById(aId);
      const b = document.getElementById(bId);
      if (!a || !b) return false;
      if (a.dataset.styleLiveBound === "1" && b.dataset.styleLiveBound === "1") return true;
      a.dataset.styleLiveBound = "1";
      b.dataset.styleLiveBound = "1";
      const onA = () => applyUiStylesToEditingObject({ sourceId: aId });
      const onB = () => applyUiStylesToEditingObject({ sourceId: bId });
      a.addEventListener("input", onA);
      a.addEventListener("change", onA);
      b.addEventListener("input", onB);
      b.addEventListener("change", onB);
      if (b.value && !a.value) a.value = b.value;
      else if (a.value && a.value !== b.value) b.value = a.value;
      return true;
    };
    bindPair("styleFreehandColor", "objFreehandColor");
    bindPair("styleFreehandWidth", "objFreehandWidth");
    bindPair("stylePfeilColor", "objPfeilColor");
    bindPair("stylePfeilWidth", "objPfeilWidth");
    bindPair("stylePfeilEnd", "objPfeilEnd");
  }

  function applyStyleSectionsForMode(mode) {
    ensureStyleBoxSections();
    const m = normalizeObjectType(mode || "");
    const setDisp = (id, on) => {
      const el = document.getElementById(id);
      if (el) el.style.display = on ? "block" : "none";
    };
    setDisp("stylePointSection", m === "points");
    setDisp("styleLabelSection", m === "points" || m === "textfields");
    setDisp("styleFreehandSection", m === "freehand");
    setDisp("stylePfeilSection", m === "pfeil");
    setDisp("styleCirclesSection", m === "circle" || m === "circles");
    setDisp("styleGridSection", m === "grid");
    setDisp("stylePolygonSection", m === "polygon");
    try { ensureCircleLabelAndStyleUi(m); } catch (_) { /* noop */ }
  }

  function getCircleLabelTextFromUi() {
    const a = document.getElementById("objCircleLabelText");
    const b = document.getElementById("c1LabelText");
    let v = "";
    if (a && String(a.value || "").trim()) v = String(a.value).trim();
    else if (b && String(b.value || "").trim()) v = String(b.value).trim();
    else v = "Kreis";
    return v.slice(0, 80);
  }
  try { window.__rkrkGetCircleLabelTextFromUi = getCircleLabelTextFromUi; } catch (_) {}

  function setCircleLabelTextToUi(text) {
    const v = String(text || "Kreis").trim().slice(0, 80) || "Kreis";
    const a = document.getElementById("objCircleLabelText");
    const b = document.getElementById("c1LabelText");
    if (a) a.value = v;
    if (b) b.value = v;
  }

  /** Einzelkreis: nur Stil für einen Kreis; Bezeichnung editierbar. */
  function ensureCircleLabelAndStyleUi(modeHint) {
    const section = document.getElementById("styleCirclesSection");
    if (!section) return;
    const mode = normalizeObjectType(modeHint || (exportTypeEl ? exportTypeEl.value : "") || "");

    // Multi-Kreise (2+3) in eigenen Block packen
    if (!document.getElementById("styleCirclesMultiOnly")) {
      const titles = Array.from(section.querySelectorAll(":scope > .style-section-title"));
      const mid = titles.find((t) => /mittel/i.test(t.textContent || ""));
      const outer = titles.find((t) => /au(ss|ß)en|aussen/i.test(t.textContent || ""));
      if (mid) {
        const wrap = document.createElement("div");
        wrap.id = "styleCirclesMultiOnly";
        const moveUntil = (startEl) => {
          let n = startEl;
          while (n) {
            const next = n.nextSibling;
            wrap.appendChild(n);
            // stop before nothing — collect all remaining after mid
            n = next;
            if (!n) break;
          }
        };
        // Move mid and everything after it into wrap
        const first = mid;
        const parent = first.parentNode;
        const nodes = [];
        let cur = first;
        while (cur) {
          nodes.push(cur);
          cur = cur.nextSibling;
        }
        parent.insertBefore(wrap, first);
        nodes.forEach((n) => wrap.appendChild(n));
      }
    }

    // Ersten Titel finden / setzen
    const firstTitle = section.querySelector(":scope > .style-section-title");
    if (firstTitle) {
      firstTitle.id = firstTitle.id || "styleCircleSingleTitle";
      firstTitle.textContent = (mode === "circle") ? "Kreis" : "Kreis klein";
    }

    // Beschriftungstext im Stil (neben Farbe)
    if (!document.getElementById("c1LabelText")) {
      const colorRow = document.getElementById("c1LabelColor");
      const row = colorRow ? colorRow.closest(".style-row") : null;
      if (row && row.parentNode) {
        const textRow = document.createElement("div");
        textRow.className = "style-row-compact";
        textRow.id = "styleCircleLabelTextRow";
        textRow.innerHTML = ""
          + "<label for='c1LabelText' style='margin:0;'>Bezeichnung</label>"
          + "<input id='c1LabelText' type='text' maxlength='80' value='Kreis' "
          + "style='flex:1;min-width:0;padding:4px 6px;border:1px solid #cbd5e1;border-radius:6px;'/>";
        row.insertAdjacentElement("afterend", textRow);
        const inp = document.getElementById("c1LabelText");
        if (inp && !inp.__objCircleLabelBound) {
          inp.__objCircleLabelBound = true;
          const sync = () => {
            const v = getCircleLabelTextFromUi();
            setCircleLabelTextToUi(v);
            try {
              if (typeof syncEditingObjectFromUi === "function") syncEditingObjectFromUi();
            } catch (_) { /* noop */ }
            try {
              if (typeof renderManagedObjects === "function") renderManagedObjects();
            } catch (_) { /* noop */ }
            try {
              if (typeof drawPreview === "function" && typeof previewActive !== "undefined" && previewActive) {
                drawPreview();
              }
            } catch (_) { /* noop */ }
            try {
              if (typeof notifyLiveShareChange === "function") notifyLiveShareChange();
            } catch (_) { /* noop */ }
          };
          inp.addEventListener("input", sync);
          inp.addEventListener("change", sync);
        }
      }
    }

    // Schriftgrösse für Einzelkreis ergänzen falls fehlend
    if (!document.getElementById("c1FontSize")) {
      const labelRow = document.getElementById("styleCircleLabelTextRow")
        || (document.getElementById("c1LabelColor") && document.getElementById("c1LabelColor").closest(".style-row"));
      if (labelRow && labelRow.parentNode) {
        const fontRow = document.createElement("div");
        fontRow.className = "style-row-compact";
        fontRow.id = "styleCircleFontSizeRow";
        fontRow.innerHTML = ""
          + "<label for='c1FontSize' style='margin:0;'>Schriftgrösse (px)</label>"
          + "<input id='c1FontSize' type='number' min='8' max='48' step='1' value='12'/>";
        labelRow.insertAdjacentElement("afterend", fontRow);
      }
    }

    // Objekt-Parameter: Bezeichnung
    const params = document.getElementById("objectParamsCircle");
    if (params && !document.getElementById("objCircleLabelText")) {
      const lab = document.createElement("label");
      lab.htmlFor = "objCircleLabelText";
      lab.innerHTML = "<strong>Bezeichnung</strong>";
      const inp = document.createElement("input");
      inp.id = "objCircleLabelText";
      inp.type = "text";
      inp.maxLength = 80;
      inp.value = "Kreis";
      inp.style.cssText = "display:block;width:100%;max-width:220px;";
      const rad = document.getElementById("objSingleRadius");
      if (rad && rad.parentNode === params) {
        // after radius
        const radLab = params.querySelector("label[for='objSingleRadius']");
        if (rad.nextSibling) {
          params.insertBefore(lab, rad.nextSibling);
          params.insertBefore(inp, lab.nextSibling);
        } else {
          params.appendChild(lab);
          params.appendChild(inp);
        }
      } else {
        params.appendChild(lab);
        params.appendChild(inp);
      }
      if (!inp.__objCircleLabelBound) {
        inp.__objCircleLabelBound = true;
        const sync = () => {
          const v = getCircleLabelTextFromUi();
          setCircleLabelTextToUi(v);
          try {
            if (typeof syncEditingObjectFromUi === "function") syncEditingObjectFromUi();
          } catch (_) { /* noop */ }
          try {
            if (typeof renderManagedObjects === "function") renderManagedObjects();
          } catch (_) { /* noop */ }
          try {
            if (typeof drawPreview === "function" && typeof previewActive !== "undefined" && previewActive) {
              drawPreview();
            }
          } catch (_) { /* noop */ }
        };
        inp.addEventListener("input", sync);
        inp.addEventListener("change", sync);
      }
    }

    // Sichtbarkeit Multi vs Single
    const multi = document.getElementById("styleCirclesMultiOnly");
    if (multi) multi.style.display = (mode === "circles") ? "" : "none";
    const labelTextRow = document.getElementById("styleCircleLabelTextRow");
    if (labelTextRow) labelTextRow.style.display = (mode === "circle" || mode === "circles") ? "" : "none";
    // Bei 3 Kreisen Bezeichnung nur für C1 im Stil behalten; Text-Feld bei Einzelkreis prominent
    if (firstTitle && mode === "circles") firstTitle.textContent = "Kreis klein";

    // Bind Bezeichnung / Farbe / Schrift immer (auch wenn Felder schon im DOM sind)
    ["objCircleLabelText", "c1LabelText", "c1LabelColor", "c1FontSize"].forEach((id) => {
      const el = document.getElementById(id);
      if (!el || el.__objCircleLabelBound) return;
      el.__objCircleLabelBound = true;
      const sync = () => {
        try {
          if (id === "objCircleLabelText" || id === "c1LabelText") {
            setCircleLabelTextToUi(getCircleLabelTextFromUi());
          }
        } catch (_) { /* noop */ }
        try { if (typeof syncEditingObjectFromUi === "function") syncEditingObjectFromUi(); } catch (_) {}
        try { if (typeof renderManagedObjects === "function") renderManagedObjects(); } catch (_) {}
        try {
          if (typeof drawPreview === "function" && typeof previewActive !== "undefined" && previewActive) drawPreview();
        } catch (_) {}
        try { if (typeof notifyLiveShareChange === "function") notifyLiveShareChange(); } catch (_) {}
      };
      el.addEventListener("input", sync);
      el.addEventListener("change", sync);
    });

    if (firstTitle && mode === "circle") firstTitle.textContent = "Kreis";
  }
  try { window.__rkrkEnsureCircleLabelAndStyleUi = ensureCircleLabelAndStyleUi; } catch (_) {}

  function updateStyleBoxVisibility() {
    const box = document.getElementById("styleBox");
    if (!box) return;
    ensureStyleBoxSections();
    const mode = currentStyleEditMode();
    const show = !!mode;
    const editId = (show && selectedManagedObjectId != null) ? String(selectedManagedObjectId) : "";
    const prevEditId = String(box.dataset.styleEditFor || "");
    box.style.display = show ? "" : "none";
    box.setAttribute("aria-hidden", show ? "false" : "true");
    if (!show) {
      try { box.open = false; } catch (_) { /* noop */ }
      box.dataset.styleEditFor = "";
      return;
    }
    applyStyleSectionsForMode(mode);
    ensureStyleParamLiveSync();
    // Beim Start einer Bearbeitung öffnen; danach manuell schliessbar (kein Re-Open bei jedem Refresh)
    if (editId && editId !== prevEditId) {
      try { box.open = true; } catch (_) { /* noop */ }
      box.dataset.styleEditFor = editId;
    }
    try {
      const rect = box.getBoundingClientRect();
      if (rect.bottom < 40 || rect.top > window.innerHeight - 40
        || rect.right < 40 || rect.left > window.innerWidth - 40) {
        box.style.left = "374px";
        box.style.top = "auto";
        box.style.bottom = "10px";
      }
    } catch (_) { /* noop */ }
  }

  function installMovableFloatingBoxes() {
    const boxes = [
      { id: "overlayBox", key: "obj_float_overlay_pos_v1" },
      { id: "styleBox", key: "obj_float_style_pos_v1" }
    ];
    boxes.forEach(({ id, key }) => {
      const box = document.getElementById(id);
      if (!box || box.dataset.objMovableInstalled === "1") return;
      const summary = box.querySelector("summary");
      if (!summary) return;
      box.dataset.objMovableInstalled = "1";
      summary.style.cursor = "move";
      const clampPos = (x, y) => {
        const margin = 6;
        const rect = box.getBoundingClientRect();
        const maxX = Math.max(margin, window.innerWidth - rect.width - margin);
        const maxY = Math.max(margin, window.innerHeight - rect.height - margin);
        return {
          x: Math.max(margin, Math.min(maxX, Math.round(x))),
          y: Math.max(margin, Math.min(maxY, Math.round(y)))
        };
      };
      const applyPos = (x, y) => {
        const p = clampPos(x, y);
        box.style.left = `${p.x}px`;
        box.style.top = `${p.y}px`;
        box.style.bottom = "auto";
      };
      const savedRaw = localStorage.getItem(key);
      if (savedRaw) {
        try {
          const saved = JSON.parse(savedRaw);
          if (Number.isFinite(saved.x) && Number.isFinite(saved.y)) {
            applyPos(saved.x, saved.y);
          }
        } catch (_) { /* ignore */ }
      }
      let drag = null;
      let moved = false;
      let suppressClick = false;
      const onMove = (evt) => {
        if (!drag) return;
        const x = evt.clientX - drag.dx;
        const y = evt.clientY - drag.dy;
        if (Math.abs(x - drag.startX) > 2 || Math.abs(y - drag.startY) > 2) moved = true;
        applyPos(x, y);
      };
      const onUp = () => {
        if (!drag) return;
        window.removeEventListener("mousemove", onMove);
        window.removeEventListener("mouseup", onUp);
        if (moved) {
          suppressClick = true;
          setTimeout(() => { suppressClick = false; }, 0);
          const left = parseInt(box.style.left || "0", 10);
          const top = parseInt(box.style.top || "0", 10);
          localStorage.setItem(key, JSON.stringify({ x: left, y: top }));
        }
        drag = null;
        moved = false;
        document.body.style.userSelect = "";
      };
      summary.addEventListener("mousedown", (evt) => {
        if (evt.button !== 0) return;
        const rect = box.getBoundingClientRect();
        drag = {
          dx: evt.clientX - rect.left,
          dy: evt.clientY - rect.top,
          startX: rect.left,
          startY: rect.top
        };
        moved = false;
        document.body.style.userSelect = "none";
        window.addEventListener("mousemove", onMove);
        window.addEventListener("mouseup", onUp);
      });
      summary.addEventListener("click", (evt) => {
        if (!suppressClick) return;
        evt.preventDefault();
        evt.stopPropagation();
      }, true);
      window.addEventListener("resize", () => {
        const rect = box.getBoundingClientRect();
        applyPos(rect.left, rect.top);
      });
    });
  }

  function ensureStandortBasemapVisible() {
    // Top basemap bar must stay visible (Karte / Satellit).
    let top = document.getElementById("basemapTopBar");
    if (!top) {
      const panel = document.querySelector("aside.panel");
      const h1 = panel ? panel.querySelector("h1") : null;
      if (panel && h1) {
        top = document.createElement("div");
        top.id = "basemapTopBar";
        top.style.margin = "4px 0 6px";
        top.className = "basemap-toggle";
        top.title = "Basiskarte";
        top.innerHTML = ""
          + "<button id=\"basemapMapBtn\" type=\"button\" class=\"basemap-seg selected-flight-reference\">Karte</button>"
          + "<button id=\"basemapSatelliteBtn\" type=\"button\" class=\"basemap-seg\">Satellit</button>"
          + "<span id=\"basemapStatus\" class=\"basemap-status-sr\" aria-live=\"polite\">Pixelkarte</span>";
        h1.insertAdjacentElement("afterend", top);
        const mapBtn = document.getElementById("basemapMapBtn");
        const satBtn = document.getElementById("basemapSatelliteBtn");
        if (mapBtn && typeof window.__rkrkSetBasemapMode === "function") {
          mapBtn.addEventListener("click", () => window.__rkrkSetBasemapMode("map"));
        }
        if (satBtn && typeof window.__rkrkSetBasemapMode === "function") {
          satBtn.addEventListener("click", () => window.__rkrkSetBasemapMode("satellite"));
        }
      }
    }
    if (top) {
      top.style.display = "";
      top.style.visibility = "visible";
    }
    // Standort-Sektion ausblenden — Button lebt unter Koordinate.
    Array.from(document.querySelectorAll("aside.panel details.panel-details")).forEach((d) => {
      const t = d.querySelector("summary") ? String(d.querySelector("summary").textContent || "").trim().toLowerCase() : "";
      if (t === "standort" || (t.includes("standort") && !t.includes("koordinate"))) {
        d.style.display = "none";
      }
    });
  }

  function findPanelBySummary(matchFn) {
    return Array.from(document.querySelectorAll("aside.panel details.panel-details")).find((d) => {
      const s = d.querySelector("summary");
      const t = s ? String(s.textContent || "").trim().toLowerCase() : "";
      return matchFn(t, d);
    }) || null;
  }

  function moveLocateIntoCoordinates() {
    const coordPanel = document.getElementById("coordPanelDetails")
      || findPanelBySummary((t) => t === "koordinate" || t.includes("koordinate"));
    if (!coordPanel) return;
    const content = coordPanel.querySelector(".panel-details-content") || coordPanel;
    let btn = document.getElementById("locateBtn");
    let st = document.getElementById("locateStatus");
    if (!btn) {
      btn = document.createElement("button");
      btn.id = "locateBtn";
      btn.type = "button";
      btn.textContent = "Zum aktuellen Standort (5x5 km)";
    }
    if (!st) {
      st = document.createElement("p");
      st.id = "locateStatus";
      st.className = "small";
    }
    if (btn.parentNode !== content) content.appendChild(btn);
    if (st.parentNode !== content) content.appendChild(st);
  }

  function placeHelpPanelAtBottom() {
    const panel = document.querySelector("aside.panel");
    if (!panel) return;
    const help = document.getElementById("objEditorHelpDetails")
      || findPanelBySummary((t) => t === "hilfe" || t.includes("hilfe"));
    const copyright = document.getElementById("rkrCopyright");
    if (help) {
      panel.appendChild(help);
      help.open = false;
    }
    if (copyright) panel.appendChild(copyright);
  }

  function isOrtDefined() {
    return Array.isArray(selectedLonLat) && selectedLonLat.length === 2
      && Number.isFinite(Number(selectedLonLat[0])) && Number.isFinite(Number(selectedLonLat[1]));
  }

  /** Wenn Ort gesetzt und Objekt-Modus aktiv: Koordinate zuklappen, Objekt offen lassen. */
  function collapseCoordPanelWhenOrtDefined() {
    const coordPanel = document.getElementById("coordPanelDetails")
      || findPanelBySummary((t) => t === "koordinate" || t.includes("koordinate"));
    const objectPanel = document.getElementById("objectPanelDetails")
      || findPanelBySummary((t) => t === "objekt");
    if (!coordPanel) return;
    if (!isOrtDefined()) {
      coordPanel.open = true;
      return;
    }
    coordPanel.open = false;
    if (objectPanel) {
      objectPanel.open = true;
      objectPanel.style.display = "";
    }
  }

  function installCoordCollapseOnObjectFocus() {
    if (window.__rkrkCoordCollapseInstalled) return;
    window.__rkrkCoordCollapseInstalled = true;
    const objectPanel = document.getElementById("objectPanelDetails")
      || findPanelBySummary((t) => t === "objekt");
    if (!objectPanel) return;
    objectPanel.addEventListener("toggle", () => {
      if (objectPanel.open) collapseCoordPanelWhenOrtDefined();
    });
  }
  function placeObjectPanelsBelowCoordinates() {
    const panel = document.querySelector("aside.panel");
    if (!panel) return;
    moveLocateIntoCoordinates();
    const brandHeader = document.getElementById("rkrBrandHeader");
    const h1 = panel.querySelector("h1");
    const basemap = document.getElementById("basemapTopBar");
    const coordPanel = document.getElementById("coordPanelDetails")
      || findPanelBySummary((t) => t === "koordinate" || t.includes("koordinate"));
    const objectPanel = document.getElementById("objectPanelDetails")
      || findPanelBySummary((t) => t === "objekt");
    const drawingsPanel = document.getElementById("drawingsPanelDetails")
      || findPanelBySummary((t) => t.includes("zeichnungen"));
    const listPanel = document.getElementById("objManagedListPanel");
    const sharePanel = document.getElementById("sharePanelDetails")
      || findPanelBySummary((t) => t === "teilen" || t.includes("teilen") || t.includes("export"));
    const help = document.getElementById("objEditorHelpDetails")
      || findPanelBySummary((t) => t === "hilfe" || t.includes("hilfe"));
    const ordered = [];
    if (brandHeader) ordered.push(brandHeader);
    else if (h1) ordered.push(h1);
    if (basemap) ordered.push(basemap);
    if (coordPanel) {
      // Ort schon gesetzt → Koordinate zu, Fokus auf Objekt
      coordPanel.open = !isOrtDefined();
      ordered.push(coordPanel);
    }
    if (objectPanel) {
      objectPanel.open = true;
      objectPanel.style.display = "";
      ordered.push(objectPanel);
    }
    if (listPanel) {
      listPanel.open = true;
      listPanel.style.display = "";
      ordered.push(listPanel);
      nestDrawingsPanelUnderObjects();
    } else if (drawingsPanel) {
      // Fallback until Objekte-Panel existiert
      drawingsPanel.style.display = "";
      ordered.push(drawingsPanel);
    }
    if (sharePanel) {
      sharePanel.style.display = "";
      ordered.push(sharePanel);
    }
    Array.from(panel.querySelectorAll(":scope > details.panel-details")).forEach((d) => {
      if (ordered.includes(d)) return;
      const t = d.querySelector("summary") ? String(d.querySelector("summary").textContent || "").trim().toLowerCase() : "";
      if (t === "hilfe" || t.includes("hilfe")) return;
      if (t === "standort" || t.includes("standort")) {
        d.style.display = "none";
        return;
      }
      if (d.style.display === "none") return;
      ordered.push(d);
    });
    if (help) ordered.push(help);
    const copyright = document.getElementById("rkrCopyright");
    if (copyright) ordered.push(copyright);
    ordered.forEach((el) => {
      if (el) panel.appendChild(el);
    });
  }

  function removeFlightPathUi() {
    const fp = document.getElementById("fpCalcVariant");
    if (fp) {
      const d = fp.closest("details");
      if (d) d.remove();
    }
    Array.from(document.querySelectorAll("details")).forEach((d) => {
      const t = d.querySelector("summary") ? String(d.querySelector("summary").textContent || "").trim().toLowerCase() : "";
      if (t.includes("flugpfad") || t.includes("flight path")) d.remove();
      // Flugfeld-Hilfssektion ausblenden, Standort (Karte/Satellit) sichtbar lassen.
      if (t.includes("flugfeld zeichnen")) d.style.display = "none";
    });
    if (typeof appendFlightPathPointsKml === "function") {
      appendFlightPathPointsKml = function () { return ""; };
    }
    if (typeof flightPathPoints !== "undefined" && Array.isArray(flightPathPoints)) {
      flightPathPoints = [];
    }
  }

  function forceObjectMode() {
    if (typeof exportTypeEl !== "undefined" && exportTypeEl) {
      if (!exportTypeEl.querySelector('option[value="points"]')) {
        const opt = document.createElement("option");
        opt.value = "points";
        opt.textContent = "Punkte";
        exportTypeEl.appendChild(opt);
      }
      if (!exportTypeEl.querySelector('option[value="freehand"]')) {
        const opt = document.createElement("option");
        opt.value = "freehand";
        opt.textContent = "Freihandlinie";
        exportTypeEl.appendChild(opt);
      }
      if (!exportTypeEl.querySelector('option[value="pfeil"]')) {
        const opt = document.createElement("option");
        opt.value = "pfeil";
        opt.textContent = "Pfeil";
        exportTypeEl.appendChild(opt);
      }
      if (!exportTypeEl.querySelector('option[value="textfields"]')) {
        const opt = document.createElement("option");
        opt.value = "textfields";
        opt.textContent = "Textfeld";
        exportTypeEl.appendChild(opt);
      }
      if (!exportTypeEl.querySelector('option[value="circle"]')) {
        const opt = document.createElement("option");
        opt.value = "circle";
        opt.textContent = "Einzelkreis";
        exportTypeEl.appendChild(opt);
      }
      if (!exportTypeEl.querySelector('option[value="circles"]')) {
        const opt = document.createElement("option");
        opt.value = "circles";
        opt.textContent = "3 farbige Kreise";
        exportTypeEl.appendChild(opt);
      }
      // Reihenfolge: … Textfeld, dann Einzelkreis / 3 farbige Kreise ganz unten
      const circleOpt = exportTypeEl.querySelector('option[value="circle"]');
      const circlesOpt = exportTypeEl.querySelector('option[value="circles"]');
      if (circleOpt) exportTypeEl.appendChild(circleOpt);
      if (circlesOpt) exportTypeEl.appendChild(circlesOpt);
      const polyOpt = exportTypeEl.querySelector('option[value="polygon"]');
      if (polyOpt) polyOpt.textContent = "Polygon";
      const pointsOpt = exportTypeEl.querySelector('option[value="points"]');
      if (pointsOpt) pointsOpt.textContent = "Punkte";
      const textOpt = exportTypeEl.querySelector('option[value="textfields"]');
      if (textOpt) textOpt.textContent = "Textfeld";
      if (circleOpt) circleOpt.textContent = "Einzelkreis";
      if (circlesOpt) circlesOpt.textContent = "3 farbige Kreise";
      const gridOpt = exportTypeEl.querySelector('option[value="grid"]');
      if (gridOpt && !exportTypeEl.dataset.objDefaultGridSet) {
        exportTypeEl.dataset.objDefaultGridSet = "1";
        exportTypeEl.value = "grid";
      }
      if (!exportTypeEl.__objModeSwitchInstalled) {
        exportTypeEl.__objModeSwitchInstalled = true;
        exportTypeEl.addEventListener("change", () => {
          const mode = normalizeObjectType(exportTypeEl.value || "grid");
          if (String(exportTypeEl.value || "") !== mode) exportTypeEl.value = mode;
          if (window.__applyingDrawingState || window.__applyingManagedObjectToUi) {
            try { refreshObjectActionButtons(); } catch (_) { /* noop */ }
            return;
          }
          // Ensure old grid/circle preview is not left behind when switching modes.
          drawingArmed = false;
          if (typeof clearPreview === "function") {
            clearPreview();
          } else if (typeof previewSource !== "undefined" && previewSource && typeof previewSource.clear === "function") {
            previewSource.clear();
          }
          if (mode === "polygon") {
            if (typeof renderPolygonEditor === "function") renderPolygonEditor();
          } else if (mode === "points" || mode === "textfields") {
            if (typeof renderLabeledPoints === "function") renderLabeledPoints();
          } else if (mode === "freehand" || mode === "pfeil") {
            if (typeof previewSource !== "undefined" && previewSource) previewSource.clear();
            renderFreehandDraftPreview();
            if (typeof clearGridRotationOverlay === "function") clearGridRotationOverlay();
          } else if ((mode === "grid" || mode === "circles" || mode === "circle")
            && typeof previewActive !== "undefined" && previewActive
            && Array.isArray(selectedLonLat) && selectedLonLat.length === 2
            && typeof drawPreview === "function") {
            drawPreview();
          }
          try { refreshObjectActionButtons(); } catch (_) { /* noop */ }
        });
      }
      exportTypeEl.dispatchEvent(new Event("change"));
    }
  }

  function rotatedOffsetLatLon(originLat, originLon, northMeters, eastMeters, angleDeg) {
    const angle = Number.isFinite(Number(angleDeg)) ? Number(angleDeg) : getGridRotationAngle();
    const a = (typeof degToRad === "function" ? degToRad(angle) : angle * Math.PI / 180);
    const re = (eastMeters * Math.cos(a)) - (northMeters * Math.sin(a));
    const rn = (eastMeters * Math.sin(a)) + (northMeters * Math.cos(a));
    return offsetLatLon(originLat, originLon, rn, re);
  }
  window.__rkrkRotatedOffsetLatLon = rotatedOffsetLatLon;

  function patchGridFunctions() {
    if (typeof drawGridPreview === "function") {
      drawGridPreview = function (lat, lon) {
        const style = getEditorStyles().grid;
        const spacing = readNumberInput(["spacing", "objGridSize"], 333);
        const northCount = Math.min(200, Math.max(1, Math.round(readNumberInput(["northCount", "objNorthCount"], 8))));
        const eastCount = Math.min(26, Math.max(1, Math.round(readNumberInput(["eastCount", "objEastCount"], 8))));
        if (typeof pruneGridCellFillCycleForCounts === "function") {
          pruneGridCellFillCycleForCounts(northCount, eastCount);
        }
        for (let n = 0; n < northCount; n += 1) {
          for (let e = 0; e < eastCount; e += 1) {
            const sw = rotatedOffsetLatLon(lat, lon, n * spacing, e * spacing);
            const se = rotatedOffsetLatLon(lat, lon, n * spacing, (e + 1) * spacing);
            const ne = rotatedOffsetLatLon(lat, lon, (n + 1) * spacing, (e + 1) * spacing);
            const nw = rotatedOffsetLatLon(lat, lon, (n + 1) * spacing, e * spacing);
            const center = rotatedOffsetLatLon(lat, lon, (n + 0.5) * spacing, (e + 0.5) * spacing);
            const cellName = `${eastLabel(e)}${n + 1}`;
            const fillRgba = (typeof getGridCellFillRgbaForPreview === "function")
              ? getGridCellFillRgbaForPreview(style, n, e)
              : hexToRgba(style.fillColor, style.fillOpacity);
            const ring = [sw, se, ne, nw, sw].map((p) => lonLatToMapCoord(p[1], p[0]));
            const polygonFeature = new ol.Feature({ geometry: new ol.geom.Polygon([ring]) });
            polygonFeature.set("previewType", "grid-cell");
            polygonFeature.set("gridCellN", n);
            polygonFeature.set("gridCellE", e);
            polygonFeature.setStyle(new ol.style.Style({
              zIndex: 1,
              stroke: new ol.style.Stroke({ color: style.lineColor, width: style.lineWidth, lineDash: [8, 6] }),
              fill: new ol.style.Fill({ color: fillRgba })
            }));
            previewSource.addFeature(polygonFeature);
            const textFeature = new ol.Feature({ geometry: new ol.geom.Point(lonLatToMapCoord(center[1], center[0])) });
            textFeature.set("previewType", "grid-cell-label");
            textFeature.set("gridCellN", n);
            textFeature.set("gridCellE", e);
            textFeature.setStyle(new ol.style.Style({
              zIndex: 2,
              text: new ol.style.Text({
                text: cellName,
                font: `${style.fontSize}px Arial`,
                fill: new ol.style.Fill({ color: style.labelColor }),
                stroke: new ol.style.Stroke({ color: "#ffffff", width: 3 })
              })
            }));
            previewSource.addFeature(textFeature);
          }
        }
      };
    }
  }

  function ensureManagedObjectLayer() {
    if (managedObjectSource && managedObjectLayer) return;
    if (typeof ol === "undefined" || typeof map === "undefined" || !map) return;
    managedObjectSource = new ol.source.Vector();
    managedObjectLayer = new ol.layer.Vector({ source: managedObjectSource, zIndex: 18 });
    map.addLayer(managedObjectLayer);
  }

  function cloneLonLat(p) {
    return [Number(p[0]), Number(p[1])];
  }

  function normalizeObjectType(type) {
    const t = String(type || "").trim().toLowerCase();
    if (t === "circle" || t === "circles" || t === "grid" || t === "polygon" || t === "points" || t === "textfields" || t === "freehand" || t === "pfeil" || t === "arrow") {
      return (t === "arrow") ? "pfeil" : t;
    }
    return "grid";
  }

  function objectTypeLabel(type) {
    const t = normalizeObjectType(type);
    if (t === "grid") return "Raster";
    if (t === "circle") return "Kreis";
    if (t === "circles") return "Kreise";
    if (t === "polygon") return "Polygon";
    if (t === "points") return "Punkte";
    if (t === "textfields") return "Textfeld";
    if (t === "freehand") return "Freihandlinie";
    if (t === "pfeil") return "Pfeil";
    return t;
  }

  function defaultManagedObjectName(obj) {
    return `${objectTypeLabel(obj && obj.type)} #${obj && obj.id != null ? obj.id : "?"}`;
  }

  function managedObjectDisplayName(obj) {
    const custom = obj && typeof obj.name === "string" ? obj.name.trim() : "";
    return custom || defaultManagedObjectName(obj);
  }


  function normalizePfeilEndStyle(v) {
    const s = String(v || "").toLowerCase();
    if (s === "arrows" || s === "both" || s === "double") return "arrows";
    if (s === "dots" || s === "points" || s === "dot") return "dots";
    return "arrow";
  }

  function getPfeilStyleFromUi() {
    const colorEl = document.getElementById("objPfeilColor") || document.getElementById("stylePfeilColor");
    const widthEl = document.getElementById("objPfeilWidth") || document.getElementById("stylePfeilWidth");
    const endEl = document.getElementById("objPfeilEnd") || document.getElementById("stylePfeilEnd");
    return {
      lineColor: colorEl ? String(colorEl.value || "#e11d48") : "#e11d48",
      lineWidth: widthEl ? Math.max(1, Number(widthEl.value) || 3) : 3,
      endStyle: normalizePfeilEndStyle(endEl ? endEl.value : "arrow")
    };
  }

  function setPfeilStyleToUi(obj) {
    if (!obj) return;
    const color = obj.lineColor || "#e11d48";
    const width = Number(obj.lineWidth) || 3;
    const end = normalizePfeilEndStyle(obj.endStyle);
    ["objPfeilColor", "stylePfeilColor"].forEach((id) => {
      const el = document.getElementById(id);
      if (el) el.value = String(color);
    });
    ["objPfeilWidth", "stylePfeilWidth"].forEach((id) => {
      const el = document.getElementById(id);
      if (el) el.value = String(width);
    });
    ["objPfeilEnd", "stylePfeilEnd"].forEach((id) => {
      const el = document.getElementById(id);
      if (el) el.value = end;
    });
  }

  /** Map-space arrow tip / dots for LineString mapCoords [[x,y],...]. */
  function buildPfeilEndOlStyles(mapCoords, color, lineWidth, endStyle, zIndex) {
    const styles = [];
    if (!Array.isArray(mapCoords) || mapCoords.length < 2) return styles;
    const lw = Math.max(1, Number(lineWidth) || 3);
    const es = normalizePfeilEndStyle(endStyle);
    const tipR = Math.max(8, lw * 2.8);
    const addArrow = (fromPt, toPt) => {
      const dx = Number(toPt[0]) - Number(fromPt[0]);
      const dy = Number(toPt[1]) - Number(fromPt[1]);
      const angle = Math.atan2(dy, dx);
      styles.push(new ol.style.Style({
        zIndex: (Number(zIndex) || 0) + 1,
        geometry: new ol.geom.Point(toPt),
        image: new ol.style.RegularShape({
          points: 3,
          radius: tipR,
          fill: new ol.style.Fill({ color: color || "#e11d48" }),
          stroke: new ol.style.Stroke({ color: color || "#e11d48", width: 1 }),
          rotation: -angle + Math.PI / 2,
          rotateWithView: true
        })
      }));
    };
    const addDot = (pt) => {
      styles.push(new ol.style.Style({
        zIndex: (Number(zIndex) || 0) + 1,
        geometry: new ol.geom.Point(pt),
        image: new ol.style.Circle({
          radius: Math.max(4, lw * 1.4),
          fill: new ol.style.Fill({ color: color || "#e11d48" }),
          stroke: new ol.style.Stroke({ color: "#ffffff", width: 1.5 })
        })
      }));
    };
    const a = mapCoords[0];
    const b = mapCoords[mapCoords.length - 1];
    if (es === "dots") {
      addDot(a);
      addDot(b);
    } else if (es === "arrows") {
      addArrow(b, a);
      addArrow(a, b);
    } else {
      addArrow(a, b);
    }
    return styles;
  }

  /** Lon/lat triangle tip for KML (AABBGGRR via PolyStyle). tip=[lon,lat], from=[lon,lat] */
  function kmlArrowHeadRing(fromLonLat, tipLonLat, sizeM) {
    const flat = Math.max(8, Number(sizeM) || 20);
    const lon0 = Number(fromLonLat[0]);
    const lat0 = Number(fromLonLat[1]);
    const lon1 = Number(tipLonLat[0]);
    const lat1 = Number(tipLonLat[1]);
    const dx = lon1 - lon0;
    const dy = lat1 - lat0;
    const len = Math.sqrt(dx * dx + dy * dy) || 1e-9;
    const ux = dx / len;
    const uy = dy / len;
    // rough meters→deg at tip lat
    const mLat = 1 / 111320;
    const mLon = 1 / (111320 * Math.max(0.2, Math.cos((lat1 * Math.PI) / 180)));
    const bx = -uy;
    const by = ux;
    const backLon = lon1 - ux * flat * mLon;
    const backLat = lat1 - uy * flat * mLat;
    const half = flat * 0.45;
    const p1 = [backLon + bx * half * mLon, backLat + by * half * mLat];
    const p2 = [backLon - bx * half * mLon, backLat - by * half * mLat];
    return [
      [lon1, lat1, 0],
      [p1[0], p1[1], 0],
      [p2[0], p2[1], 0],
      [lon1, lat1, 0]
    ];
  }

  function kmlDotRing(lonLat, radiusM) {
    const lon = Number(lonLat[0]);
    const lat = Number(lonLat[1]);
    const r = Math.max(4, Number(radiusM) || 8);
    const mLat = 1 / 111320;
    const mLon = 1 / (111320 * Math.max(0.2, Math.cos((lat * Math.PI) / 180)));
    const pts = [];
    for (let i = 0; i <= 16; i += 1) {
      const a = (i / 16) * Math.PI * 2;
      pts.push([lon + Math.cos(a) * r * mLon, lat + Math.sin(a) * r * mLat, 0]);
    }
    return pts;
  }

  function strokeForManagedObject(obj, baseColor, baseWidth) {
    // Vorschau / in Bearbeitung = gestrichelt; fixiert = durchgehende Linie.
    const unfixed = obj && !obj.fixed;
    if (unfixed) {
      return new ol.style.Stroke({
        color: "#f59e0b",
        width: Math.max(3, Number(baseWidth) || 2),
        lineDash: [8, 6]
      });
    }
    return new ol.style.Stroke({
      color: baseColor,
      width: Number(baseWidth) || 2
      // no lineDash = durchgehende Linie
    });
  }

  function isTextOnlyLabelType(type) {
    const t = String(type || "").toLowerCase();
    return t === "text" || t.indexOf("text_") === 0;
  }

  function getObjectLabelFontSize() {
    const el = document.getElementById("objLabelFontSize");
    const n = Number(el ? el.value : 12);
    if (!Number.isFinite(n)) return 12;
    return Math.max(8, Math.min(72, Math.round(n)));
  }

  function isPointColorNameText(text) {
    return /^(Orange|Rot|Gelb|Schwarz|Weiss|Weiß|Grau|Violett|Cyan|Magenta|Braun|Pink|Others|Other)$/i.test(String(text || "").trim());
  }

  function pointTypeColorLabel(type) {
    const t = String(type || "");
    if (t === "start") return "Grün";
    if (t === "parking") return "Blau";
    if (t === "other") return "Orange";
    if (t === "red") return "Rot";
    if (t === "yellow") return "Gelb";
    if (t === "black") return "Schwarz";
    if (t === "white") return "Weiss";
    if (t === "gray" || t === "grey") return "Grau";
    if (t === "purple" || t === "violet") return "Violett";
    if (t === "cyan") return "Cyan";
    if (t === "magenta") return "Magenta";
    if (t === "brown") return "Braun";
    if (t === "pink") return "Pink";
    const el = document.getElementById("labelPointType");
    if (el) {
      const opt = Array.from(el.options || []).find((o) => o.value === t);
      if (opt) {
        const label = String(opt.textContent || "").trim();
        if (label) return label.replace(/\s*\(.*\)\s*$/, "").trim() || label;
      }
    }
    return "Punkt";
  }

  function buildManagedLabelFromPoint(p) {
    const defaults = (typeof labelPointDefaultsByType === "function")
      ? labelPointDefaultsByType(String((p && p.type) || "other"))
      : { color: "#ff8c00", textColor: "#111111", pointRadius: 7 };
    const type = String((p && p.type) || "other");
    let text = String((p && p.text) || "");
    if (!isTextOnlyLabelType(type) && isPointColorNameText(text)) text = "";
    return {
      lon: Number(p.lon),
      lat: Number(p.lat),
      type,
      text,
      color: String((p && p.color) || defaults.color || "#ff8c00"),
      textColor: String((p && p.textColor) || defaults.textColor || defaults.color || "#111111"),
      pointRadius: isTextOnlyLabelType(type)
        ? 0
        : Math.max(0, Number.isFinite(Number(p && p.pointRadius))
          ? Number(p.pointRadius)
          : Number(defaults.pointRadius ?? 7)),
      fontSize: Math.max(8, Math.min(72, Number.isFinite(Number(p && p.fontSize))
        ? Number(p.fontSize)
        : getObjectLabelFontSize()))
    };
  }

  function labelObjectTypeForPoint(label, modeHint) {
    if (isTextOnlyLabelType(label && label.type) || modeHint === "textfields") return "textfields";
    return "points";
  }

  function displayNameForLabelObject(objType, label, id) {
    const textName = String((label && label.text) || "").trim();
    if (objType === "textfields") {
      return textName ? `Textfeld: ${textName}` : defaultManagedObjectName({ type: objType, id });
    }
    if (textName && !isPointColorNameText(textName)) return textName;
    return pointTypeColorLabel(label && label.type) || defaultManagedObjectName({ type: objType, id });
  }

  /** Jeder Punkt / jedes Textfeld = ein eigenes managedObject (labels.length === 1). */
  function splitMultiLabelManagedObjects() {
    if (!Array.isArray(managedObjects) || !managedObjects.length) return false;
    const next = [];
    let changed = false;
    managedObjects.forEach((obj) => {
      if (!obj || (obj.type !== "points" && obj.type !== "textfields")
          || !Array.isArray(obj.labels) || obj.labels.length <= 1) {
        next.push(obj);
        return;
      }
      changed = true;
      obj.labels.forEach((lp, i) => {
        if (!lp) return;
        const label = buildManagedLabelFromPoint(lp);
        const objType = labelObjectTypeForPoint(label, obj.type);
        const newId = (i === 0 && Number.isFinite(Number(obj.id))) ? Number(obj.id) : managedObjectIdSeq++;
        const keepName = i === 0 && obj.name && String(obj.name).trim()
          && !/\(\d+\)\s*$/.test(String(obj.name))
          && !/^Punkt$/i.test(String(obj.name).trim())
          && !/^Textfeld$/i.test(String(obj.name).trim())
          && !/^Punkte\b/i.test(String(obj.name).trim());
        next.push({
          id: newId,
          fixed: obj.fixed !== false,
          type: objType,
          labels: [label],
          name: keepName ? String(obj.name).trim() : displayNameForLabelObject(objType, label, newId)
        });
      });
    });
    if (!changed) return false;
    managedObjects = next;
    const maxId = managedObjects.reduce((m, o) => Math.max(m, Number(o.id) || 0), 0);
    managedObjectIdSeq = Math.max(managedObjectIdSeq || 1, maxId + 1);
    if (selectedManagedObjectId != null
        && !managedObjects.some((o) => o.id === selectedManagedObjectId)) {
      selectedManagedObjectId = null;
    }
    return true;
  }

  function createManagedObjectsFromLabels(labels, modeHint, opts) {
    const fixed = !(opts && opts.fixed === false);
    const created = [];
    (Array.isArray(labels) ? labels : []).forEach((p) => {
      if (!p || !Number.isFinite(Number(p.lon)) || !Number.isFinite(Number(p.lat))) return;
      const newId = managedObjectIdSeq++;
      const label = buildManagedLabelFromPoint(p);
      const objType = labelObjectTypeForPoint(label, modeHint);
      const obj = {
        id: newId,
        fixed,
        type: objType,
        labels: [label],
        name: displayNameForLabelObject(objType, label, newId)
      };
      touchManagedObjectSyncMeta(obj);
      managedObjects.push(obj);
      created.push(obj);
    });
    return created;
  }

  function isEditingLabelManagedObject() {
    if (!selectedManagedObjectId) return false;
    const sel = managedObjects.find((o) => o.id === selectedManagedObjectId);
    return !!(sel && !sel.fixed && (sel.type === "points" || sel.type === "textfields"));
  }

  /**
   * Höchstens ein Objekt gleichzeitig in Bearbeitung.
   * @param {number|null|undefined} editId - Objekt-ID zum Bearbeiten, oder null = alle fixieren
   * @param {{ sync?: boolean }} opts
   */
  function ensureSingleEditingObject(editId, opts) {
    const wantId = (editId == null || editId === "") ? null : Number(editId);
    const doSync = !(opts && opts.sync === false);
    managedObjects.forEach((o) => {
      if (!o) return;
      if (wantId != null && Number(o.id) === wantId) return;
      if (!o.fixed) {
        if (doSync && selectedManagedObjectId != null && Number(o.id) === Number(selectedManagedObjectId)) {
          try { syncEditingObjectFromUi(); } catch (_) { /* noop */ }
        }
        o.fixed = true;
      }
    });
    if (wantId == null) {
      selectedManagedObjectId = null;
      managedEditPaused = false;
      return null;
    }
    const target = managedObjects.find((o) => Number(o.id) === wantId);
    if (!target) {
      selectedManagedObjectId = null;
      return null;
    }
    target.fixed = false;
    selectedManagedObjectId = target.id;
    managedEditPaused = false;
    return target;
  }

  /** true = Entwurf-Punkt wartet auf Fixieren (kein zweiter Punkt erlaubt). */
  function hasPendingPointDraft() {
    const mode = normalizeObjectType(exportTypeEl ? exportTypeEl.value : "grid");
    if (mode !== "points" && mode !== "textfields") return false;
    if (isEditingLabelManagedObject()) return false;
    return !!(typeof labeledPoints !== "undefined" && Array.isArray(labeledPoints) && labeledPoints.length > 0);
  }
  try { window.__rkrkHasPendingPointDraft = hasPendingPointDraft; } catch (_) {}

  /**
   * Punkte/Text: Entwurf bleibt lokal bis «Fixieren».
   * Kein Auto-Fix — erst Fixieren macht das Objekt final; danach neuer Punkt möglich.
   */
  function promoteOrSyncDraftLabeledPoints() {
    if (window.__applyingDrawingState || window.__applyingManagedObjectToUi) return;
    if (promoteOrSyncDraftLabeledPoints._busy) return;
    if (typeof labeledPoints === "undefined" || !Array.isArray(labeledPoints) || !labeledPoints.length) return;
    promoteOrSyncDraftLabeledPoints._busy = true;
    try {
      const modeFromUi = normalizeObjectType(exportTypeEl ? exportTypeEl.value : "grid");
      const mode = (modeFromUi === "points" || modeFromUi === "textfields")
        ? modeFromUi
        : (labeledPoints.some((lp) => lp && isTextOnlyLabelType(lp.type)) ? "textfields" : "points");

      // Mehrere Drafts → nur den letzten behalten (ein Punkt nach dem anderen)
      if (!isEditingLabelManagedObject() && labeledPoints.length > 1) {
        labeledPoints = [labeledPoints[labeledPoints.length - 1]];
        if (typeof selectedLabelPointIndex !== "undefined") selectedLabelPointIndex = 0;
        managedStatusText("Zuerst «Fixieren», dann neuen Punkt setzen.");
        if (typeof renderLabeledPoints === "function") {
          const fn = renderLabeledPoints.__objListSyncOrig || renderLabeledPoints;
          fn();
        }
        try { refreshObjectActionButtons(); } catch (_) { /* noop */ }
        return;
      }

      if (isEditingLabelManagedObject()) {
        const sel = managedObjects.find((o) => o.id === selectedManagedObjectId);
        if (!sel) return;
        const keep = labeledPoints[0] || null;
        if (labeledPoints.length > 1) {
          labeledPoints = keep ? [keep] : [];
          managedStatusText("Während der Bearbeitung nur ein Punkt — zuerst Abschliessen/Fixieren.");
        }
        if (keep) {
          sel.labels = [buildManagedLabelFromPoint(keep)];
          sel.type = labelObjectTypeForPoint(keep, mode);
          const textName = String(keep.text || "").trim();
          const curName = String(sel.name || "").trim();
          if (textName && (!curName || curName === "Text" || /^Textfeld:/i.test(curName) || /^Punkt:/i.test(curName))) {
            sel.name = displayNameForLabelObject(sel.type, keep, sel.id);
          }
          try { touchManagedObjectSyncMeta(sel); } catch (_) { /* noop */ }
        }
        if (typeof selectedLabelPointIndex !== "undefined") {
          selectedLabelPointIndex = labeledPoints.length ? 0 : -1;
        }
        if (typeof renderLabeledPoints === "function") {
          const fn = renderLabeledPoints.__objListSyncOrig || renderLabeledPoints;
          fn();
        }
        ensureManagedObjectLayer();
        renderManagedObjects();
        refreshManagedObjectList();
        try { refreshObjectActionButtons(); } catch (_) { /* noop */ }
        if (typeof scheduleSessionDraftSave === "function") scheduleSessionDraftSave();
        return;
      }

      // Neuer Entwurf: nur Status/Buttons — noch nicht in Objektliste / nicht syncen als fix
      managedStatusText(mode === "textfields"
        ? "Text gesetzt — «Fixieren» zum Übernehmen, danach neuer Text möglich."
        : "Punkt gesetzt — «Fixieren» zum Übernehmen, danach neuer Punkt möglich.");
      try { refreshObjectActionButtons(); } catch (_) { /* noop */ }
      if (typeof scheduleSessionDraftSave === "function") scheduleSessionDraftSave();
    } finally {
      promoteOrSyncDraftLabeledPoints._busy = false;
    }
  }

  function installLabelPointsObjectListSync() {
    if (window.__objLabelListSyncInstalled) return;
    window.__objLabelListSyncInstalled = true;
    const bump = () => {
      try { promoteOrSyncDraftLabeledPoints(); } catch (_) { /* noop */ }
    };
    // Neuer Punkt nur nach Fixieren des Entwurfs
    const guardAdd = (ev) => {
      try {
        if (typeof hasPendingPointDraft === "function" && hasPendingPointDraft()) {
          if (ev) {
            if (typeof ev.preventDefault === "function") ev.preventDefault();
            if (typeof ev.stopImmediatePropagation === "function") ev.stopImmediatePropagation();
          }
          managedStatusText("Zuerst «Fixieren», dann neuen Punkt setzen.");
          try { if (typeof isAddLabelPointMode !== "undefined") isAddLabelPointMode = false; } catch (_) {}
          return true;
        }
      } catch (_) { /* noop */ }
      return false;
    };
    const addBtn = document.getElementById("addLabelPointBtn");
    if (addBtn && !addBtn.__objFixFirstGuard) {
      addBtn.__objFixFirstGuard = true;
      addBtn.addEventListener("click", (ev) => { guardAdd(ev); }, true);
    }
    if (typeof map !== "undefined" && map && !map.__objFixFirstPointGuard) {
      map.__objFixFirstPointGuard = true;
      map.on("singleclick", (evt) => {
        try {
          if (typeof isAddLabelPointMode === "undefined" || !isAddLabelPointMode) return;
          if (typeof hasPendingPointDraft === "function" && hasPendingPointDraft()
              && Array.isArray(labeledPoints) && labeledPoints.length > 0) {
            // bereits ein Entwurf vorhanden → Klick nicht als zweiten Punkt werten
            if (evt && typeof evt.stopPropagation === "function") evt.stopPropagation();
            try { isAddLabelPointMode = false; } catch (_) {}
            managedStatusText("Zuerst «Fixieren», dann neuen Punkt setzen.");
          }
        } catch (_) { /* noop */ }
      }, true);
    }
    if (typeof map !== "undefined" && map && !map.__objLabelListSyncClick) {
      map.__objLabelListSyncClick = true;
      map.on("singleclick", () => setTimeout(bump, 0));
    }
    if (typeof renderLabeledPoints === "function" && !renderLabeledPoints.__objListSync) {
      const orig = renderLabeledPoints;
      renderLabeledPoints = function () {
        const ret = orig.apply(this, arguments);
        setTimeout(bump, 0);
        return ret;
      };
      renderLabeledPoints.__objListSync = true;
      renderLabeledPoints.__objListSyncOrig = orig;
      try { window.renderLabeledPoints = renderLabeledPoints; } catch (_) { /* noop */ }
    }
  }


  window.applyLabelPointTypeFromUi = function applyLabelPointTypeFromUi(opts) {
    if (window.__applyingManagedObjectToUi || window.__applyingDrawingState) return;
    const labelTypeEl = document.getElementById("labelPointType");
    if (!labelTypeEl || typeof labelPointDefaultsByType !== "function") return;
    const type = String(labelTypeEl.value || "other");
    const defaults = labelPointDefaultsByType(type) || {};
    const textOnly = isTextOnlyLabelType(type);
    const textColor = String(defaults.textColor || defaults.color || "#111111");
    const pointColor = String(defaults.color || "#ff8a00");
    const pointRadius = textOnly
      ? 0
      : (Number.isFinite(Number(defaults.pointRadius)) ? Number(defaults.pointRadius) : 9);
    const textColorEl = document.getElementById("objLabelTextColor");
    const pointColorEl = document.getElementById("objPointColor");
    const textEl = document.getElementById("labelPointText");
    if (textColorEl) textColorEl.value = textColor;
    if (pointColorEl && !textOnly) pointColorEl.value = pointColor;
    try { if (typeof updateSwatches === "function") updateSwatches(); } catch (_) { /* noop */ }

    const patchPoint = (pt) => {
      if (!pt || typeof pt !== "object") return;
      const prevType = String(pt.type || "");
      const prevDefaults = (typeof labelPointDefaultsByType === "function")
        ? (labelPointDefaultsByType(prevType) || {})
        : {};
      pt.type = type;
      pt.textColor = textColor;
      if (!textOnly) {
        pt.color = pointColor;
        pt.pointRadius = pointRadius;
      } else {
        pt.color = pointColor;
        pt.textColor = textColor;
        pt.pointRadius = 0;
      }
      const curText = String(pt.text || "").trim();
      const prevDefault = String(prevDefaults.defaultText || "").trim();
      if (!curText || curText === prevDefault || isPointColorNameText(curText)
          || /^(Startpunkt|Parkplatz|Text)$/i.test(curText)) {
        if (defaults.defaultText) {
          pt.text = textOnly ? String(defaults.defaultText) : "";
          if (textOnly && textEl) textEl.value = String(defaults.defaultText);
        }
      }
    };

    // Draft aus gewähltem Objekt laden, falls leer
    if ((!Array.isArray(labeledPoints) || !labeledPoints.length) && selectedManagedObjectId) {
      const sel = managedObjects.find((o) => o.id === selectedManagedObjectId);
      if (sel && (sel.type === "points" || sel.type === "textfields") && Array.isArray(sel.labels) && sel.labels.length) {
        labeledPoints = sel.labels.map((p) => ({ ...p }));
        if (typeof selectedLabelPointIndex !== "undefined") selectedLabelPointIndex = 0;
      }
    }

    if (typeof labeledPoints !== "undefined" && Array.isArray(labeledPoints) && labeledPoints.length) {
      const onlySelected = !!(opts && opts.onlySelected);
      const selIdx = (typeof selectedLabelPointIndex === "number") ? selectedLabelPointIndex : -1;
      if (onlySelected && selIdx >= 0 && labeledPoints[selIdx]) {
        patchPoint(labeledPoints[selIdx]);
      } else if (onlySelected && labeledPoints[0]) {
        patchPoint(labeledPoints[0]);
      } else {
        labeledPoints.forEach(patchPoint);
      }
    }

    // Gewähltes / in Bearbeitung befindliches Objekt aktualisieren
    if (selectedManagedObjectId) {
      const mObj = managedObjects.find((o) => o.id === selectedManagedObjectId);
      if (mObj && (mObj.type === "points" || mObj.type === "textfields") && Array.isArray(mObj.labels)) {
        mObj.labels.forEach(patchPoint);
        if (mObj.labels.length === 1) {
          const lab = buildManagedLabelFromPoint(mObj.labels[0]);
          mObj.labels = [lab];
          const nextType = labelObjectTypeForPoint(lab, textOnly ? "textfields" : "points");
          mObj.type = nextType;
          if (exportTypeEl && normalizeObjectType(exportTypeEl.value) !== nextType) {
            window.__applyingManagedObjectToUi = true;
            try {
              exportTypeEl.value = nextType;
              exportTypeEl.dispatchEvent(new Event("change"));
            } finally {
              window.__applyingManagedObjectToUi = false;
            }
          }
          const curName = String(mObj.name || "").trim();
          if (!curName || /^Punkt/i.test(curName) || /^Textfeld/i.test(curName)
              || /^(Startpunkt|Parkplatz|Orange|Rot|Gelb|Others|Grün|Blau|Schwarz|Weiss|Grau|Violett|Cyan|Magenta|Braun|Pink)$/i.test(curName)
              || isPointColorNameText(curName)) {
            mObj.name = displayNameForLabelObject(nextType, lab, mObj.id);
          }
        }
        if (!mObj.fixed) {
          try { syncEditingObjectFromUi(); } catch (_) { /* noop */ }
        }
      }
    }

    const renderFn = (typeof renderLabeledPoints === "function")
      ? (renderLabeledPoints.__objListSyncOrig || renderLabeledPoints)
      : null;
    if (renderFn) renderFn();
    renderManagedObjects();
    try { refreshManagedObjectList(); } catch (_) { /* noop */ }
    try { refreshObjectActionButtons(); } catch (_) { /* noop */ }
    if (typeof notifyLiveShareChange === "function") notifyLiveShareChange();
    if (typeof scheduleSessionDraftSave === "function") scheduleSessionDraftSave();
  };
  window.__rkrkInstallEditParamSync = function () {
    if (window.__objEditParamSyncInstalled) return;
    window.__objEditParamSyncInstalled = true;
    const bindLabel = (id) => {
      const el = document.getElementById(id);
      if (!el || el.__objLabelParamSyncBound) return;
      el.__objLabelParamSyncBound = true;
      el.addEventListener(id === "labelPointType" ? "change" : "input", () => {
        if (window.__applyingManagedObjectToUi || window.__applyingDrawingState) return;
        if (id === "labelPointType") {
          try { window.applyLabelPointTypeFromUi({ onlySelected: true }); } catch (_) { /* noop */ }
          return;
        }
        if (typeof labeledPoints === "undefined" || !Array.isArray(labeledPoints) || !labeledPoints.length) {
          if (selectedManagedObjectId) {
            const sel = managedObjects.find((o) => o.id === selectedManagedObjectId);
            if (sel && (sel.type === "points" || sel.type === "textfields") && Array.isArray(sel.labels) && sel.labels.length) {
              labeledPoints = sel.labels.map((p) => ({ ...p }));
              if (typeof selectedLabelPointIndex !== "undefined") selectedLabelPointIndex = 0;
            }
          }
        }
        if (typeof labeledPoints === "undefined" || !Array.isArray(labeledPoints) || !labeledPoints.length) return;
        const textEl = document.getElementById("labelPointText");
        const textColorEl = document.getElementById("objLabelTextColor");
        const pointColorEl = document.getElementById("objPointColor");
        const fontEl = document.getElementById("objLabelFontSize") || document.getElementById("objLabelFontSizeLeft");
        const idx = (typeof selectedLabelPointIndex === "number" && selectedLabelPointIndex >= 0) ? selectedLabelPointIndex : 0;
        const pt = labeledPoints[idx] || labeledPoints[0];
        if (!pt) return;
        if (textEl) pt.text = String(textEl.value || "");
        if (pointColorEl && !isTextOnlyLabelType(pt.type) && id === "objPointColor") {
          pt.color = String(pointColorEl.value || "");
          if (textColorEl) {
            textColorEl.value = pt.color;
            pt.textColor = pt.color;
          }
        } else {
          if (textColorEl) pt.textColor = String(textColorEl.value || "");
          if (pointColorEl && !isTextOnlyLabelType(pt.type)) pt.color = String(pointColorEl.value || "");
        }
        if (fontEl) pt.fontSize = Math.max(8, Math.min(72, Math.round(Number(fontEl.value) || 12)));
        if (selectedManagedObjectId) {
          const sel = managedObjects.find((o) => o.id === selectedManagedObjectId);
          if (sel && (sel.type === "points" || sel.type === "textfields") && Array.isArray(sel.labels) && sel.labels[0]) {
            Object.assign(sel.labels[0], pt);
            if (!sel.fixed) {
              try { syncEditingObjectFromUi(); } catch (_) { /* noop */ }
            }
          }
        }
        const renderFn = (typeof renderLabeledPoints === "function")
          ? (renderLabeledPoints.__objListSyncOrig || renderLabeledPoints) : null;
        if (renderFn) renderFn();
        renderManagedObjects();
        if (typeof notifyLiveShareChange === "function") notifyLiveShareChange();
      });
    };
    ["labelPointText", "labelPointType", "objLabelFontSize", "objLabelFontSizeLeft", "objLabelTextColor", "objPointColor"].forEach(bindLabel);
    const bump = () => {
      if (window.__applyingManagedObjectToUi || window.__applyingDrawingState) return;
      if (!selectedManagedObjectId) return;
      const sel = managedObjects.find((o) => o.id === selectedManagedObjectId);
      if (!sel || sel.fixed) return;
      if (sel.type === "points" || sel.type === "textfields") return;
      clearTimeout(window.__rkrkEditParamSyncT);
      window.__rkrkEditParamSyncT = setTimeout(() => {
        try { syncEditingObjectFromUi(); } catch (_) { /* noop */ }
        try { renderManagedObjects(); } catch (_) { /* noop */ }
        if (sel.type === "freehand" || sel.type === "pfeil") { try { renderFreehandDraftPreview(); } catch (_) { /* noop */ } }
        else if ((sel.type === "grid" || sel.type === "circles" || sel.type === "circle") && typeof drawPreview === "function") {
          try { drawPreview(); } catch (_) { /* noop */ }
        } else if (sel.type === "polygon" && typeof renderPolygonEditor === "function") {
          try { renderPolygonEditor(); } catch (_) { /* noop */ }
        }
        if (typeof notifyLiveShareChange === "function") notifyLiveShareChange();
      }, 60);
    };
    document.addEventListener("input", bump, true);
    document.addEventListener("change", bump, true);
  };

  function setInputValueIfPresent(id, value) {
    const el = document.getElementById(id);
    if (!el || value === undefined || value === null) return;
    el.value = String(value);
  }

  function readNumberInput(ids, fallback) {
    const list = Array.isArray(ids) ? ids : [ids];
    for (let i = 0; i < list.length; i += 1) {
      const el = document.getElementById(list[i]);
      if (!el) continue;
      const n = (typeof sanitizeNumber === "function")
        ? sanitizeNumber(el.value, fallback)
        : Number(el.value);
      if (Number.isFinite(n)) return n;
    }
    return fallback;
  }

  function cloneCellFillsMap(src) {
    const out = {};
    if (!src || typeof src !== "object") return out;
    Object.keys(src).forEach((key) => {
      const v = Number(src[key]);
      if (Number.isInteger(v) && v >= 0) out[key] = v;
    });
    return out;
  }

  function currentCellFillsFromUi() {
    if (typeof gridCellFillCycleMap !== "undefined" && gridCellFillCycleMap && typeof gridCellFillCycleMap === "object") {
      return cloneCellFillsMap(gridCellFillCycleMap);
    }
    return {};
  }

  function applyCellFillsToUi(cellFills) {
    if (typeof gridCellFillCycleMap === "undefined") return;
    gridCellFillCycleMap = cloneCellFillsMap(cellFills);
  }

  function installNewGridTransparentCells() {
    if (window.__rkrkNewGridClearFills) return;
    window.__rkrkNewGridClearFills = true;
    const drawBtn = document.getElementById("drawBtn");
    if (drawBtn) {
      drawBtn.addEventListener("click", () => {
        const mode = (typeof exportTypeEl !== "undefined" && exportTypeEl) ? exportTypeEl.value : "";
        if (mode !== "grid") return;
        // Nur beim Aktivieren von Zeichnen leeren — nicht bei jedem Kartenklick (sonst Doppelklick-Farben kaputt)
        setTimeout(() => {
          if (!drawingArmed) return;
          if (isEditingUnfixedGrid()) return;
          resetGridCellFillsForNewRaster();
          try {
            if (typeof drawPreview === "function" && Array.isArray(selectedLonLat) && selectedLonLat.length === 2) {
              drawPreview();
            }
          } catch (_) { /* noop */ }
        }, 0);
      });
    }
  }
  function resetGridCellFillsForNewRaster() {
    try { clearCellFillsUi(); } catch (_) {
      if (typeof gridCellFillCycleMap !== "undefined") gridCellFillCycleMap = {};
    }
  }

  function isEditingUnfixedGrid() {
    if (!selectedManagedObjectId) return false;
    const obj = managedObjects.find((o) => o && Number(o.id) === Number(selectedManagedObjectId));
    return !!(obj && obj.type === "grid" && !obj.fixed);
  }
  try { window.__rkrkIsEditingUnfixedGrid = isEditingUnfixedGrid; } catch (_) {}
  function clearCellFillsUi() {
    if (typeof gridCellFillCycleMap === "undefined") return;
    gridCellFillCycleMap = {};
  }

  function hexToKmlColorLocal(hexColor, alpha) {
    if (typeof hexToKmlColor === "function") {
      try { return hexToKmlColor(hexColor, alpha); } catch (_) { /* fall through */ }
    }
    let cleaned = String(hexColor || "#000000").replace("#", "").trim();
    if (/^[0-9a-fA-F]{3}$/.test(cleaned)) {
      cleaned = cleaned.split("").map((c) => c + c).join("");
    } else if (/^[0-9a-fA-F]{8}$/.test(cleaned)) {
      cleaned = cleaned.slice(2);
    }
    if (!/^[0-9a-fA-F]{6}$/.test(cleaned)) cleaned = "000000";
    const r = cleaned.slice(0, 2);
    const g = cleaned.slice(2, 4);
    const b = cleaned.slice(4, 6);
    const a = String(alpha || "80").replace("#", "").toLowerCase().padStart(2, "0").slice(-2);
    return (a + b + g + r).toLowerCase();
  }

  function kmlPolyStyleXmlLocal(hexColor, alphaHex, filled) {
    if (typeof kmlPolyStyleXml === "function") {
      return kmlPolyStyleXml(hexColor, alphaHex, filled !== false);
    }
    const fill = (filled === false) ? "0" : "1";
    return `<PolyStyle><color>${hexToKmlColorLocal(hexColor, alphaHex)}</color><fill>${fill}</fill><outline>1</outline></PolyStyle>`;
  }

  function getGridCellFillPaletteForKml() {
    if (typeof GRID_CELL_FILL_PALETTE !== "undefined" && Array.isArray(GRID_CELL_FILL_PALETTE) && GRID_CELL_FILL_PALETTE.length) {
      return GRID_CELL_FILL_PALETTE.map((p) => {
        const alpha = (p && p.kmlAlpha) || (typeof GRID_CELL_FILL_KML_ALPHA !== "undefined" ? GRID_CELL_FILL_KML_ALPHA : "99");
        return Object.assign({}, p, { kmlAlpha: alpha });
      });
    }
    const a = 0.6;
    const ka = (typeof GRID_CELL_FILL_KML_ALPHA !== "undefined") ? GRID_CELL_FILL_KML_ALPHA : "99";
    return [
      { hex: "#00aa00", fillAlpha: a, kmlAlpha: ka },
      { hex: "#ff2222", fillAlpha: a, kmlAlpha: ka },
      { hex: "#ff8800", fillAlpha: a, kmlAlpha: ka },
      { hex: "#ffd400", fillAlpha: a, kmlAlpha: ka },
      { hex: "#888888", fillAlpha: a, kmlAlpha: ka }
    ];
  }

  function gridCellFillRgba(style, cellFills, n, e, editing) {
    const key = `${n},${e}`;
    const fills = (cellFills && typeof cellFills === "object") ? cellFills : null;
    const idx = fills && Object.prototype.hasOwnProperty.call(fills, key) ? Number(fills[key]) : undefined;
    if (Number.isInteger(idx) && idx >= 0) {
      const pal = getGridCellFillPaletteForKml();
      if (pal[idx]) {
        const entry = pal[idx];
        return hexToRgba(entry.hex, entry.fillAlpha);
      }
    }
    // Kein Kachel-Eintrag = durchsichtig (Standard für neue Raster)
    const op = Number(style && style.fillOpacity) || 0;
    if (op > 0) {
      return hexToRgba((style && style.fillColor) || "#ffd400", op);
    }
    const pickA = (typeof GRID_NEUTRAL_FILL_PICK_ALPHA === "number") ? GRID_NEUTRAL_FILL_PICK_ALPHA : 0.012;
    return hexToRgba("#ffffff", pickA);
  }

  function currentObjectPayloadFromUi() {
    const type = normalizeObjectType(exportTypeEl ? exportTypeEl.value : "grid");
    const refLonLat = (Array.isArray(selectedLonLat) && selectedLonLat.length === 2)
      ? cloneLonLat(selectedLonLat)
      : null;
    if (type === "polygon") {
      const editorStyles = (typeof getEditorStyles === "function") ? getEditorStyles() : null;
      const polygonStyle = (editorStyles && editorStyles.polygon) ? editorStyles.polygon : {
        lineColor: "#ff8c00", lineWidth: 2, fillColor: "#ff8c00", fillOpacity: 0.1, labelColor: "#0000ff", fontSize: 12
      };
      return {
        type,
        polygonVertices: Array.isArray(polygonVertices) ? polygonVertices.map(cloneLonLat) : [],
        polygonStyle: {
          lineColor: String(polygonStyle.lineColor || "#ff8c00"),
          lineWidth: Number(polygonStyle.lineWidth) || 2,
          fillColor: String(polygonStyle.fillColor || "#ff8c00"),
          fillOpacity: Number(polygonStyle.fillOpacity) || 0.1,
          labelColor: String(polygonStyle.labelColor || "#0000ff"),
          fontSize: Number(polygonStyle.fontSize) || 12
        }
      };
    }
    if (type === "points" || type === "textfields") {
      return {
        type,
        labels: Array.isArray(labeledPoints)
          ? labeledPoints.map((p) => {
            const defaults = (typeof labelPointDefaultsByType === "function")
              ? labelPointDefaultsByType(String(p.type || "other"))
              : { color: "#ff8c00", textColor: "#111111", pointRadius: 7 };
            return {
              lon: Number(p.lon),
              lat: Number(p.lat),
              type: String(p.type || "other"),
              text: String(p.text || ""),
              color: String(p.color || defaults.color || "#ff8c00"),
              textColor: String(p.textColor || defaults.textColor || defaults.color || "#111111"),
              pointRadius: Math.max(0, Number.isFinite(Number(p.pointRadius)) ? Number(p.pointRadius) : Number(defaults.pointRadius ?? 7)),
              fontSize: Math.max(8, Math.min(72, Number.isFinite(Number(p.fontSize)) ? Number(p.fontSize) : getObjectLabelFontSize()))
            };
          })
          : []
      };
    }
    if (type === "freehand" || type === "pfeil") {
      let coords = Array.isArray(freehandDraftCoords) ? freehandDraftCoords.map((p) => [Number(p[0]), Number(p[1])]) : [];
      // Offene Reihe: Schlusspunkt entfernen falls versehentlich geschlossen
      if (coords.length >= 3) {
        const a = coords[0];
        const b = coords[coords.length - 1];
        if (Math.abs(a[0] - b[0]) < 1e-9 && Math.abs(a[1] - b[1]) < 1e-9) coords = coords.slice(0, -1);
      }
      if (type === "pfeil" && coords.length > 2) coords = [coords[0], coords[coords.length - 1]];
      if (coords.length < 2) return null;
      if (type === "pfeil") {
        const st = getPfeilStyleFromUi();
        return {
          type: "pfeil",
          coordinates: coords,
          lineColor: st.lineColor,
          lineWidth: st.lineWidth,
          endStyle: st.endStyle
        };
      }
      const colorEl = document.getElementById("objFreehandColor");
      const widthEl = document.getElementById("objFreehandWidth");
      return {
        type: "freehand",
        coordinates: coords,
        lineColor: colorEl ? String(colorEl.value || "#e11d48") : "#e11d48",
        lineWidth: widthEl ? Math.max(1, Number(widthEl.value) || 3) : 3
      };
    }
    if (!refLonLat) {
      return null;
    }
    if (type === "circle") {
      if (!refLonLat) return null;
      const editorStyles = (typeof getEditorStyles === "function") ? getEditorStyles() : null;
      const s = (editorStyles && Array.isArray(editorStyles.circles) && editorStyles.circles[0])
        ? editorStyles.circles[0]
        : { lineColor: "#ff0000", fillColor: "#ff0000", fillOpacity: 0, labelColor: "#0000ff", fontSize: 12, lineWidth: 2 };
      const radEl = document.getElementById("objSingleRadius");
      const diamEl = document.getElementById("objSingleDiameter");
      const r1El = document.getElementById("radius1");
      const radius = radEl
        ? Math.max(1, sanitizeNumber(radEl.value, 100))
        : Math.max(1, sanitizeNumber(r1El ? r1El.value : 100, 100));
      const diameter = Math.max(2, radius * 2);
      if (r1El) r1El.value = String(Math.round(radius));
      if (radEl) radEl.value = String(Math.round(radius));
      if (diamEl) diamEl.value = String(Math.round(diameter));
      const labelText = (typeof getCircleLabelTextFromUi === "function")
        ? getCircleLabelTextFromUi()
        : "Kreis";
      const fontEl = document.getElementById("c1FontSize");
      const fontSize = fontEl
        ? Math.max(8, Math.min(48, Number(fontEl.value) || 12))
        : (Number(s.fontSize) || 12);
      return {
        type: "circle",
        anchor: refLonLat,
        r: radius,
        diameter: diameter,
        circleStyle: {
          lineColor: String(s.lineColor || "#ff0000"),
          fillColor: String(s.fillColor || "#ff0000"),
          fillOpacity: Number(s.fillOpacity) || 0,
          labelColor: String(s.labelColor || "#0000ff"),
          labelText: labelText,
          fontSize: fontSize,
          lineWidth: Number(s.lineWidth) || 2
        }
      };
    }
    if (type === "grid") {
      const editorStyles = (typeof getEditorStyles === "function") ? getEditorStyles() : null;
      const gridStyle = (editorStyles && editorStyles.grid) ? editorStyles.grid : {
        lineColor: "#ffd400",
        lineWidth: 2,
        fillColor: "#ffd400",
        fillOpacity: 0,
        labelColor: "#0000ff",
        fontSize: 11
      };
      return {
        type,
        anchor: refLonLat,
        spacing: Math.max(1, readNumberInput(["spacing", "objGridSize"], 333)),
        northCount: Math.max(1, Math.min(200, Math.round(readNumberInput(["northCount", "objNorthCount"], 8)))),
        eastCount: Math.max(1, Math.min(26, Math.round(readNumberInput(["eastCount", "objEastCount"], 8)))),
        rotation: getGridRotationAngle(),
        cellFills: currentCellFillsFromUi(),
        gridStyle: {
          lineColor: String(gridStyle.lineColor || "#ffd400"),
          lineWidth: Number(gridStyle.lineWidth) || 2,
          fillColor: String(gridStyle.fillColor || "#ffd400"),
          fillOpacity: Number(gridStyle.fillOpacity) || 0,
          labelColor: String(gridStyle.labelColor || "#0000ff"),
          fontSize: Number(gridStyle.fontSize) || 11
        }
      };
    }
    if (type !== "circles") return null;
    const editorStyles = (typeof getEditorStyles === "function") ? getEditorStyles() : null;
    const circlesStyles = (editorStyles && Array.isArray(editorStyles.circles) && editorStyles.circles.length)
      ? editorStyles.circles
      : [
        { lineColor: "#00aa00", fillColor: "#00aa00", fillOpacity: 0, labelColor: "#0000ff", fontSize: 12, lineWidth: 2 },
        { lineColor: "#ffcc00", fillColor: "#ffcc00", fillOpacity: 0, labelColor: "#0000ff", fontSize: 12, lineWidth: 2 },
        { lineColor: "#ff0000", fillColor: "#ff0000", fillOpacity: 0, labelColor: "#0000ff", fontSize: 12, lineWidth: 2 }
      ];
    return {
      type: "circles",
      anchor: refLonLat,
      r1: Math.max(1, sanitizeNumber(document.getElementById("radius1").value, 100)),
      r2: Math.max(1, sanitizeNumber(document.getElementById("radius2").value, 200)),
      r3: Math.max(1, sanitizeNumber(document.getElementById("radius3").value, 300)),
      circlesStyles: circlesStyles.map((s) => ({
        lineColor: String(s.lineColor || "#00aa00"),
        fillColor: String(s.fillColor || "#00aa00"),
        fillOpacity: Number(s.fillOpacity) || 0,
        labelColor: String(s.labelColor || "#0000ff"),
        fontSize: Number(s.fontSize) || 12,
        lineWidth: Number(s.lineWidth) || 2
      }))
    };
  }

  function canFixCurrentObject() {
    if (selectedManagedObjectId) {
      const sel = managedObjects.find((o) => o.id === selectedManagedObjectId);
      // Nur während aktiver Bearbeitung (unfixiert) über die Auswahl fixieren
      if (sel && !sel.fixed) return true;
    }
    const payload = currentObjectPayloadFromUi();
    if (!payload) return false;
    if (payload.type === "polygon") {
      return Array.isArray(payload.polygonVertices) && payload.polygonVertices.length >= 3;
    }
    if (payload.type === "points" || payload.type === "textfields") {
      return Array.isArray(payload.labels) && payload.labels.length > 0;
    }
    if (payload.type === "freehand" || payload.type === "pfeil") {
      return Array.isArray(payload.coordinates) && payload.coordinates.length >= 2;
    }
    return !!(payload.anchor);
  }

  function canDiscardCurrentChanges() {
    if (selectedManagedObjectId && editBaselineSnapshot) return true;
    const mode = normalizeObjectType(exportTypeEl ? exportTypeEl.value : "grid");
    if (mode === "polygon") {
      const verts = (typeof polygonVertices !== "undefined" && Array.isArray(polygonVertices)) ? polygonVertices : [];
      const drawing = (typeof isPolygonDrawing !== "undefined") && !!isPolygonDrawing;
      return verts.length > 0 || drawing;
    }
    if (mode === "points" || mode === "textfields") {
      return Array.isArray(labeledPoints) && labeledPoints.length > 0;
    }
    if (mode === "freehand" || mode === "pfeil") {
      return Array.isArray(freehandDraftCoords) && freehandDraftCoords.length > 0;
    }
    return Array.isArray(selectedLonLat) && selectedLonLat.length === 2;
  }

  function clearFixedHistory() {
    fixedUndoStack = [];
    fixedRedoStack = [];
    fixedHistoryCursorJson = "";
  }

  function snapshotFixedWorld() {
    return {
      managedObjects: JSON.parse(JSON.stringify(managedObjects)),
      managedObjectGroups: JSON.parse(JSON.stringify(managedObjectGroups)),
      managedListOrder: JSON.parse(JSON.stringify(managedListOrder)),
      managedObjectIdSeq,
      managedObjectGroupIdSeq,
      selectedManagedObjectId
    };
  }

  function markFixedHistoryCursor() {
    fixedHistoryCursorJson = JSON.stringify({
      managedObjects: managedObjects,
      managedObjectGroups: managedObjectGroups,
      managedListOrder: managedListOrder,
      managedObjectIdSeq,
      managedObjectGroupIdSeq
    });
    try { refreshObjectActionButtons(); } catch (_) { /* noop */ }
  }
  /** Call before a change that alters the set/order of fixed objects. */

  function rememberFixedHistory() {
    if (fixedHistoryApplying) return;
    const snap = snapshotFixedWorld();
    const json = JSON.stringify({
      managedObjects: snap.managedObjects,
      managedObjectGroups: snap.managedObjectGroups,
      managedListOrder: snap.managedListOrder,
      managedObjectIdSeq: snap.managedObjectIdSeq,
      managedObjectGroupIdSeq: snap.managedObjectGroupIdSeq
    });
    if (json === fixedHistoryCursorJson) return;
    fixedUndoStack.push(snap);
    while (fixedUndoStack.length > FIXED_HISTORY_MAX) fixedUndoStack.shift();
    fixedRedoStack = [];
  }

  function applyFixedSnapshot(snap) {
    if (!snap) return;
    fixedHistoryApplying = true;
    try {
      managedObjects = Array.isArray(snap.managedObjects)
        ? JSON.parse(JSON.stringify(snap.managedObjects))
        : [];
      managedObjectGroups = Array.isArray(snap.managedObjectGroups)
        ? JSON.parse(JSON.stringify(snap.managedObjectGroups))
        : [];
      managedListOrder = Array.isArray(snap.managedListOrder)
        ? JSON.parse(JSON.stringify(snap.managedListOrder))
        : [];
      managedObjectIdSeq = Number(snap.managedObjectIdSeq) || (managedObjects.reduce((m, o) => Math.max(m, Number(o.id) || 0), 0) + 1);
      managedObjectGroupIdSeq = Number(snap.managedObjectGroupIdSeq)
        || (managedObjectGroups.reduce((m, g) => Math.max(m, Number(g && g.id) || 0), 0) + 1);
      syncManagedListOrder();
      selectedManagedObjectId = null;
      managedEditPaused = false;
      editBaselineSnapshot = null;
      drawingArmed = false;
      if (typeof clearPreview === "function") clearPreview();
      if (typeof deactivateGridRuler === "function") deactivateGridRuler();
      if (typeof clearGridRotationOverlay === "function") clearGridRotationOverlay();
      freehandDraftCoords = [];
      if (typeof polygonVertices !== "undefined") polygonVertices = [];
      if (typeof labeledPoints !== "undefined") labeledPoints = [];
      selectedLonLat = null;
      if (typeof setMarker === "function") {
        try { setMarker(null); } catch (_) { /* noop */ }
      }
      if (typeof renderCoord === "function") renderCoord();
      if (typeof renderPolygonEditor === "function") renderPolygonEditor();
      if (typeof renderLabeledPoints === "function") renderLabeledPoints();
      renderManagedObjects();
      markFixedHistoryCursor();
    } finally {
      fixedHistoryApplying = false;
      try { refreshObjectActionButtons(); } catch (_) { /* noop */ }
    }
  }

  function undoFixedStep() {
    if (!fixedUndoStack.length) {
      managedStatusText("Kein Schritt zurück möglich.");
      return;
    }
    const current = snapshotFixedWorld();
    fixedRedoStack.push(current);
    const prev = fixedUndoStack.pop();
    applyFixedSnapshot(prev);
    managedStatusText("Rückgängig: letzter fixierter Stand.");
  }

  function redoFixedStep() {
    if (!fixedRedoStack.length) {
      managedStatusText("Kein Schritt vorwärts möglich.");
      return;
    }
    fixedUndoStack.push(snapshotFixedWorld());
    while (fixedUndoStack.length > FIXED_HISTORY_MAX) fixedUndoStack.shift();
    const next = fixedRedoStack.pop();
    applyFixedSnapshot(next);
    managedStatusText("Wiederholen: fixierter Stand.");
  }
  window.__rkrkAllowAnchorPreview = function () {
    if (drawingArmed) return true;
    if (selectedManagedObjectId) {
      const obj = managedObjects.find((o) => o.id === selectedManagedObjectId);
      if (obj && !obj.fixed) return true;
    }
    // Nach erstem gesetzten Basispunkt: Vorschau darf weiter aktualisiert werden (auch bei Stop)
    if ((typeof previewActive !== "undefined") && previewActive
        && Array.isArray(selectedLonLat) && selectedLonLat.length === 2) {
      return true;
    }
    return false;
  };

  function managedStatusText(text) {
    const el = document.getElementById("objManagedStatus");
    if (el) {
      const msg = String(text == null ? "" : text).trim();
      el.textContent = msg;
      el.classList.toggle("obj-status-idle", !msg);
    }
    try { refreshObjectActionButtons(); } catch (_) { /* noop */ }
  }

  function ensureCompactObjectActionBar() {
    const objectDetails = document.getElementById("objectPanelDetails")
      || ((typeof exportTypeEl !== "undefined" && exportTypeEl) ? exportTypeEl.closest("details") : null);
    const objectContent = objectDetails ? objectDetails.querySelector(".panel-details-content") : null;
    if (!objectContent) return null;
    let bar = document.getElementById("objManagedToolsTop");
    if (!bar) {
      bar = document.createElement("div");
      bar.id = "objManagedToolsTop";
    }
    bar.className = "obj-action-bar";
    bar.style.display = "";
    const typeSel = document.getElementById("exportType");
    const insertAfter = (typeSel && objectContent.contains(typeSel)) ? typeSel : null;
    if (bar.parentNode !== objectContent) {
      if (insertAfter) insertAfter.insertAdjacentElement("afterend", bar);
      else objectContent.insertAdjacentElement("afterbegin", bar);
    } else if (insertAfter && bar.previousElementSibling !== insertAfter) {
      insertAfter.insertAdjacentElement("afterend", bar);
    }
    const ensureBtn = (id, label, title) => {
      let btn = document.getElementById(id);
      if (!btn) {
        btn = document.createElement("button");
        btn.id = id;
        btn.type = "button";
        btn.textContent = label;
      }
      if (title) btn.title = title;
      if (btn.parentNode !== bar) bar.appendChild(btn);
      return btn;
    };
    const moveIntoBar = (id) => {
      const el = document.getElementById(id);
      if (el && el.parentNode !== bar) bar.appendChild(el);
      return el;
    };
    // Preferred order in one dense row (only visible ones take space)
    moveIntoBar("drawBtn");
    moveIntoBar("clearBtn");
    moveIntoBar("objStartPolyBtn");
    moveIntoBar("objClearPolyBtn");
    moveIntoBar("objClearFreehandBtn");
    ensureBtn("objAddManagedBtn", "Fixieren", "Objekt fixieren");
    ensureBtn("objDiscardBtn", "Verwerfen", "Änderungen verwerfen");
    const undoBtn = ensureBtn("objEditUndoBtn", "←", "Rückgängig (fixierte Objekte)");
    const redoBtn = ensureBtn("objEditRedoBtn", "↪", "Wiederholen (fixierte Objekte)");
    undoBtn.classList.add("obj-icon-btn");
    redoBtn.classList.add("obj-icon-btn");
    undoBtn.setAttribute("aria-label", "Rückgängig");
    redoBtn.setAttribute("aria-label", "Wiederholen");
    undoBtn.textContent = "←";
    redoBtn.textContent = "↪";
    // Hidden until there is something to undo/redo (updateVisibility toggles).
    if (!fixedUndoStack.length) undoBtn.style.display = "none";
    if (!fixedRedoStack.length) redoBtn.style.display = "none";
    const finishPoly = moveIntoBar("objFinishPolyBtn");
    if (finishPoly) finishPoly.style.display = "none";
    [
      "drawBtn", "clearBtn", "objStartPolyBtn", "objClearPolyBtn",
      "objClearFreehandBtn", "objAddManagedBtn", "objDiscardBtn",
      "objEditUndoBtn", "objEditRedoBtn", "objFinishPolyBtn"
    ].forEach((id) => {
      const el = document.getElementById(id);
      if (el && el.parentNode === bar) bar.appendChild(el);
    });
    let status = document.getElementById("objManagedStatus");
    if (!status) {
      status = document.createElement("p");
      status.className = "small obj-status-idle";
      status.id = "objManagedStatus";
      status.textContent = "";
    } else {
      status.classList.add("small");
      if (!String(status.textContent || "").trim()
          || /Fixieren speichert das Objekt/i.test(status.textContent || "")) {
        status.textContent = "";
        status.classList.add("obj-status-idle");
      }
    }
    if (status.parentNode !== objectContent || status.previousElementSibling !== bar) {
      bar.insertAdjacentElement("afterend", status);
    }
    // Collapse legacy poly toggle — buttons live in the top bar
    const polyToggle = document.getElementById("objPolyActionToggle");
    if (polyToggle && polyToggle !== bar) {
      polyToggle.style.display = "none";
      while (polyToggle.firstChild) bar.appendChild(polyToggle.firstChild);
      [
        "drawBtn", "clearBtn", "objStartPolyBtn", "objClearPolyBtn",
        "objClearFreehandBtn", "objAddManagedBtn", "objDiscardBtn",
        "objEditUndoBtn", "objEditRedoBtn", "objFinishPolyBtn"
      ].forEach((id) => {
        const el = document.getElementById(id);
        if (el && el.parentNode === bar) bar.appendChild(el);
      });
    }
    return bar;
  }

  function managedFeatureZIndex(objIdx, offset) {
    return (Number(objIdx) || 0) * 100 + (Number(offset) || 0);
  }

  function tagManagedFeature(feature, objId, zIndex) {
    feature.set("managedObjectId", objId);
    feature.set("managedZIndex", zIndex);
    return feature;
  }

  function getManagedObjectGroup(groupId) {
    if (groupId == null || groupId === "") return null;
    const id = Number(groupId);
    if (!Number.isFinite(id)) return null;
    return managedObjectGroups.find((g) => g && Number(g.id) === id) || null;
  }

  function syncManagedListOrder() {
    if (!Array.isArray(managedListOrder)) managedListOrder = [];
    const groupIds = new Set(
      (Array.isArray(managedObjectGroups) ? managedObjectGroups : [])
        .filter((g) => g)
        .map((g) => Number(g.id))
    );
    const ungroupedIds = [];
    const ungroupedSet = new Set();
    (Array.isArray(managedObjects) ? managedObjects : []).forEach((o) => {
      if (!o) return;
      const gid = o.groupId;
      if (gid != null && getManagedObjectGroup(gid)) return;
      const id = Number(o.id);
      if (!Number.isFinite(id) || ungroupedSet.has(id)) return;
      ungroupedSet.add(id);
      ungroupedIds.push(id);
    });
    const next = [];
    const seenG = new Set();
    const seenO = new Set();
    managedListOrder.forEach((e) => {
      if (!e) return;
      const id = Number(e.id);
      if (e.kind === "group" && groupIds.has(id) && !seenG.has(id)) {
        next.push({ kind: "group", id });
        seenG.add(id);
      } else if (e.kind === "object" && ungroupedSet.has(id) && !seenO.has(id)) {
        next.push({ kind: "object", id });
        seenO.add(id);
      }
    });
    managedObjectGroups.forEach((g) => {
      if (!g) return;
      const id = Number(g.id);
      if (!seenG.has(id)) next.push({ kind: "group", id });
    });
    ungroupedIds.forEach((id) => {
      if (!seenO.has(id)) next.push({ kind: "object", id });
    });
    managedListOrder = next;
    return managedListOrder;
  }

  function syncManagedObjectsOrderFromListOrder() {
    syncManagedListOrder();
    const byId = new Map();
    (Array.isArray(managedObjects) ? managedObjects : []).forEach((o) => {
      if (o) byId.set(Number(o.id), o);
    });
    const next = [];
    const used = new Set();
    managedListOrder.forEach((e) => {
      if (!e) return;
      if (e.kind === "group") {
        managedObjects.forEach((o) => {
          if (!o || Number(o.groupId) !== Number(e.id)) return;
          const id = Number(o.id);
          if (used.has(id)) return;
          next.push(o);
          used.add(id);
        });
      } else if (e.kind === "object") {
        const o = byId.get(Number(e.id));
        if (!o) return;
        const id = Number(o.id);
        if (used.has(id)) return;
        next.push(o);
        used.add(id);
      }
    });
    managedObjects.forEach((o) => {
      if (!o) return;
      const id = Number(o.id);
      if (used.has(id)) return;
      next.push(o);
      used.add(id);
    });
    managedObjects = next;
  }

  function moveManagedListOrderBefore(dragKind, dragId, targetKind, targetId) {
    syncManagedListOrder();
    const dK = String(dragKind || "");
    const dId = Number(dragId);
    const tK = String(targetKind || "");
    const tId = Number(targetId);
    if (!dK || !Number.isFinite(dId)) return false;
    if (dK === tK && dId === tId) return false;
    const fromIdx = managedListOrder.findIndex((e) => e && e.kind === dK && Number(e.id) === dId);
    if (fromIdx < 0) return false;
    rememberFixedHistory();
    const [item] = managedListOrder.splice(fromIdx, 1);
    let toIdx = managedListOrder.findIndex((e) => e && e.kind === tK && Number(e.id) === tId);
    if (toIdx < 0) toIdx = managedListOrder.length;
    managedListOrder.splice(toIdx, 0, item);
    syncManagedObjectsOrderFromListOrder();
    markFixedHistoryCursor();
    renderManagedObjects();
    try { refreshManagedObjectList(); } catch (_) { /* noop */ }
    try { if (typeof notifyLiveShareChange === "function") notifyLiveShareChange(); } catch (_) { /* noop */ }
    return true;
  }

  function insertGroupIntoListOrder(groupId, memberIds) {
    syncManagedListOrder();
    const gid = Number(groupId);
    const memberSet = new Set((Array.isArray(memberIds) ? memberIds : []).map((x) => Number(x)));
    let insertAt = managedListOrder.findIndex((e) => e && e.kind === "object" && memberSet.has(Number(e.id)));
    if (insertAt < 0) insertAt = managedListOrder.length;
    managedListOrder = managedListOrder.filter((e) => {
      if (!e) return false;
      if (e.kind === "object" && memberSet.has(Number(e.id))) return false;
      if (e.kind === "group" && Number(e.id) === gid) return false;
      return true;
    });
    if (insertAt > managedListOrder.length) insertAt = managedListOrder.length;
    managedListOrder.splice(insertAt, 0, { kind: "group", id: gid });
    syncManagedObjectsOrderFromListOrder();
  }

  function resolveListDropTarget(kind, id) {
    if (kind === "group") return { kind: "group", id: Number(id) };
    const obj = managedObjects.find((o) => o && Number(o.id) === Number(id));
    if (obj && obj.groupId != null && getManagedObjectGroup(obj.groupId)) {
      return { kind: "group", id: Number(obj.groupId) };
    }
    return { kind: "object", id: Number(id) };
  }

  function handleManagedListDrop(dragKind, dragId, targetKind, targetId) {
    const drag = { kind: String(dragKind || ""), id: Number(dragId) };
    const target = { kind: String(targetKind || ""), id: Number(targetId) };
    if (!drag.kind || !Number.isFinite(drag.id) || !target.kind || !Number.isFinite(target.id)) return false;
    if (drag.kind === target.kind && drag.id === target.id) return false;

    if (drag.kind === "group") {
      const t = resolveListDropTarget(target.kind, target.id);
      const ok = moveManagedListOrderBefore("group", drag.id, t.kind, t.id);
      if (ok) managedStatusText("Gruppe verschoben.");
      return ok;
    }

    if (drag.kind === "object" && target.kind === "group") {
      // Objekt auf Gruppe: in die Gruppe aufnehmen
      const obj = managedObjects.find((o) => o && Number(o.id) === drag.id);
      const g = getManagedObjectGroup(target.id);
      if (!obj || !g) return false;
      rememberFixedHistory();
      obj.groupId = Number(target.id);
      touchManagedObjectSyncMeta(obj);
      syncManagedListOrder();
      syncManagedObjectsOrderFromListOrder();
      markFixedHistoryCursor();
      renderManagedObjects();
      try { refreshManagedObjectList(); } catch (_) { /* noop */ }
      try { if (typeof notifyLiveShareChange === "function") notifyLiveShareChange(); } catch (_) { /* noop */ }
      managedStatusText(`«${managedObjectDisplayName(obj)}» → Gruppe «${g.name}».`);
      return true;
    }

    if (drag.kind === "object" && target.kind === "object") {
      const dragObj = managedObjects.find((o) => o && Number(o.id) === drag.id);
      const targetObj = managedObjects.find((o) => o && Number(o.id) === target.id);
      if (!dragObj || !targetObj) return false;
      const sameGroup = dragObj.groupId != null
        && Number(dragObj.groupId) === Number(targetObj.groupId)
        && getManagedObjectGroup(dragObj.groupId);
      if (sameGroup) {
        const fromIdx = managedObjects.findIndex((o) => Number(o.id) === drag.id);
        const toIdx = managedObjects.findIndex((o) => Number(o.id) === target.id);
        if (fromIdx < 0 || toIdx < 0 || fromIdx === toIdx) return false;
        reorderManagedObject(fromIdx, toIdx);
        managedStatusText("Reihenfolge geändert.");
        return true;
      }
      // Aus Gruppe heraus / zwischen ungruppierten: Listenreihenfolge
      if (dragObj.groupId != null) {
        rememberFixedHistory();
        delete dragObj.groupId;
        touchManagedObjectSyncMeta(dragObj);
      }
      const t = resolveListDropTarget("object", target.id);
      const ok = moveManagedListOrderBefore("object", drag.id, t.kind, t.id);
      if (ok) managedStatusText("Reihenfolge geändert.");
      return ok;
    }
    return false;
  }


  function isManagedObjectMapHidden(obj) {
    if (!obj) return true;
    if (obj.hidden) return true;
    const g = getManagedObjectGroup(obj.groupId);
    return !!(g && g.hidden);
  }

  function getManagedObjectGroupOpacity(obj) {
    const g = getManagedObjectGroup(obj && obj.groupId);
    if (!g) return 1;
    const op = Number(g.opacity);
    return Number.isFinite(op) ? Math.max(0, Math.min(1, op)) : 1;
  }

  function scaleColorAlpha(color, factor) {
    const f = Math.max(0, Math.min(1, Number(factor)));
    if (!Number.isFinite(f) || f >= 0.999) return color;
    if (Array.isArray(color)) {
      const out = color.slice();
      out[3] = (out.length > 3 ? Number(out[3]) || 1 : 1) * f;
      return out;
    }
    const s = String(color || "");
    const m = s.match(/^rgba?\(\s*([\d.]+)\s*,\s*([\d.]+)\s*,\s*([\d.]+)\s*(?:,\s*([\d.]+)\s*)?\)$/i);
    if (m) {
      const a = (m[4] != null && m[4] !== "" ? Number(m[4]) : 1) * f;
      return `rgba(${m[1]},${m[2]},${m[3]},${Math.max(0, Math.min(1, a))})`;
    }
    if (typeof hexToRgba === "function") {
      try { return hexToRgba(s, f); } catch (_) { /* noop */ }
    }
    return color;
  }

  function dimOlStyle(style, factor) {
    if (!style || typeof style.clone !== "function") return style;
    const f = Math.max(0, Math.min(1, Number(factor)));
    if (!Number.isFinite(f) || f >= 0.999) return style;
    try {
      const cloned = style.clone();
      const stroke = cloned.getStroke && cloned.getStroke();
      if (stroke && typeof stroke.setColor === "function") {
        stroke.setColor(scaleColorAlpha(stroke.getColor(), f));
      }
      const fill = cloned.getFill && cloned.getFill();
      if (fill && typeof fill.setColor === "function") {
        fill.setColor(scaleColorAlpha(fill.getColor(), f));
      }
      const text = cloned.getText && cloned.getText();
      if (text) {
        const tf = text.getFill && text.getFill();
        if (tf && typeof tf.setColor === "function") tf.setColor(scaleColorAlpha(tf.getColor(), f));
        const ts = text.getStroke && text.getStroke();
        if (ts && typeof ts.setColor === "function") ts.setColor(scaleColorAlpha(ts.getColor(), f));
      }
      const image = cloned.getImage && cloned.getImage();
      if (image) {
        try {
          const imf = image.getFill && image.getFill();
          if (imf && typeof imf.setColor === "function") imf.setColor(scaleColorAlpha(imf.getColor(), f));
        } catch (_) { /* noop */ }
        try {
          const ims = image.getStroke && image.getStroke();
          if (ims && typeof ims.setColor === "function") ims.setColor(scaleColorAlpha(ims.getColor(), f));
        } catch (_) { /* noop */ }
      }
      return cloned;
    } catch (_) {
      return style;
    }
  }

  function dimManagedFeaturesForObject(objId, factor) {
    if (!managedObjectSource) return;
    const f = Number(factor);
    if (!Number.isFinite(f) || f >= 0.999) return;
    managedObjectSource.getFeatures().forEach((feat) => {
      if (Number(feat.get("managedObjectId")) !== Number(objId)) return;
      const st = feat.getStyle();
      if (!st) return;
      if (Array.isArray(st)) feat.setStyle(st.map((s) => dimOlStyle(s, f)));
      else feat.setStyle(dimOlStyle(st, f));
    });
  }

  function createManagedObjectGroup(name, objectIds) {
    rememberFixedHistory();
    const id = managedObjectGroupIdSeq++;
    const group = {
      id,
      name: String(name || "").trim() || ("Gruppe " + id),
      hidden: false,
      opacity: 1,
      collapsed: false
    };
    managedObjectGroups.push(group);
    const ids = new Set((Array.isArray(objectIds) ? objectIds : []).map((x) => Number(x)));
    managedObjects.forEach((o) => {
      if (o && ids.has(Number(o.id))) {
        o.groupId = id;
        touchManagedObjectSyncMeta(o);
      }
    });
    insertGroupIntoListOrder(id, Array.from(ids));
    markFixedHistoryCursor();
    try { if (typeof notifyLiveShareChange === "function") notifyLiveShareChange(); } catch (_) { /* noop */ }
    return group;
  }

  function dissolveManagedObjectGroup(groupId) {
    const gid = Number(groupId);
    const idx = managedObjectGroups.findIndex((g) => g && Number(g.id) === gid);
    if (idx < 0) return false;
    rememberFixedHistory();
    syncManagedListOrder();
    const memberIds = managedObjects
      .filter((o) => o && Number(o.groupId) === gid)
      .map((o) => Number(o.id));
    const orderIdx = managedListOrder.findIndex((e) => e && e.kind === "group" && Number(e.id) === gid);
    managedObjects.forEach((o) => {
      if (o && Number(o.groupId) === gid) {
        delete o.groupId;
        touchManagedObjectSyncMeta(o);
      }
    });
    managedObjectGroups.splice(idx, 1);
    if (orderIdx >= 0) {
      managedListOrder.splice(orderIdx, 1);
      const inserts = memberIds.map((id) => ({ kind: "object", id }));
      managedListOrder.splice(orderIdx, 0, ...inserts);
    }
    syncManagedListOrder();
    syncManagedObjectsOrderFromListOrder();
    markFixedHistoryCursor();
    try { if (typeof notifyLiveShareChange === "function") notifyLiveShareChange(); } catch (_) { /* noop */ }
    return true;
  }

  function toggleManagedObjectGroupHidden(groupId) {
    const g = getManagedObjectGroup(groupId);
    if (!g) return false;
    rememberFixedHistory();
    g.hidden = !g.hidden;
    markFixedHistoryCursor();
    renderManagedObjects();
    try { refreshManagedObjectList(); } catch (_) { /* noop */ }
    try { if (typeof notifyLiveShareChange === "function") notifyLiveShareChange(); } catch (_) { /* noop */ }
    managedStatusText(g.hidden
      ? `«${g.name}»: Gruppe auf Karte unterdrückt.`
      : `«${g.name}»: Gruppe auf Karte angezeigt.`);
    return true;
  }

  function setManagedObjectGroupOpacity(groupId, opacity, opts) {
    const g = getManagedObjectGroup(groupId);
    if (!g) return false;
    const op = Math.max(0, Math.min(1, Number(opacity)));
    if (!Number.isFinite(op)) return false;
    const live = !!(opts && opts.live);
    const commit = !!(opts && opts.commit);
    const prev = Number(g.opacity);
    if (commit && (!Number.isFinite(prev) || Math.abs(prev - op) >= 0.001)) {
      rememberFixedHistory();
    }
    g.opacity = op;
    renderManagedObjects({ skipListRefresh: live || !commit });
    if (commit && (!Number.isFinite(prev) || Math.abs(prev - op) >= 0.001)) {
      markFixedHistoryCursor();
      try { if (typeof notifyLiveShareChange === "function") notifyLiveShareChange(); } catch (_) { /* noop */ }
      try { refreshManagedObjectList(); } catch (_) { /* noop */ }
    }
    return true;
  }

  function groupSelectedManagedObjects() {
    const ids = Array.from(objListSelectedIds).map((x) => Number(x)).filter((id) =>
      managedObjects.some((o) => o && Number(o.id) === id));
    if (ids.length < 1) {
      managedStatusText("Objekte in der Liste anwählen (Checkbox), dann Gruppieren.");
      return null;
    }
    const group = createManagedObjectGroup(null, ids);
    objListSelectedIds.clear();
    renderManagedObjects();
    try { refreshManagedObjectList(); } catch (_) { /* noop */ }
    managedStatusText(`Gruppe «${group.name}» mit ${ids.length} Objekt(en) erstellt.`);
    return group;
  }


  function renderManagedObjects() {
    if (!managedObjectSource) return;
    managedObjectSource.clear();
    const styles = (typeof getEditorStyles === "function") ? getEditorStyles() : null;
    managedObjects.forEach((obj, objIdx) => {
      if (!isManagedObjectVisibleToMe(obj)) return;
      if (isManagedObjectMapHidden(obj)) return;
      if (obj.id === selectedManagedObjectId && !obj.fixed) {
        return;
      }
      const editing = !obj.fixed;
      const selectedFixed = obj.id === selectedManagedObjectId && !!obj.fixed;
      const selectionStroke = strokeForManagedObject(
        obj,
        editing ? "#f59e0b" : (selectedFixed ? "#2563eb" : "#475569"),
        editing || selectedFixed ? 3 : 2
      );
      const zBase = managedFeatureZIndex(objIdx, 0);
      const zLabel = managedFeatureZIndex(objIdx, 1);
      if (obj.type === "circle" && obj.anchor) {
        const centerLon = Number(obj.anchor[0]);
        const centerLat = Number(obj.anchor[1]);
        const center = lonLatToMapCoord(centerLon, centerLat);
        const s = (obj.circleStyle && typeof obj.circleStyle === "object")
          ? obj.circleStyle
          : ((styles && Array.isArray(styles.circles) && styles.circles[0])
            ? styles.circles[0]
            : { lineColor: "#ff0000", fillColor: "#ff0000", fillOpacity: 0, labelColor: "#0000ff", fontSize: 12, lineWidth: 2 });
        const radius = Math.max(1, Number(obj.r) || 1);
        const coords = (typeof createCircleCoordinates === "function")
          ? createCircleCoordinates(centerLat, centerLon, radius, 96)
          : [];
        const ring = coords.length
          ? coords.map((p) => lonLatToMapCoord(Number(p[0]), Number(p[1])))
          : ol.geom.Polygon.fromCircle(new ol.geom.Circle(center, radius), 96).getCoordinates()[0];
        const f = new ol.Feature({ geometry: new ol.geom.Polygon([ring]) });
        tagManagedFeature(f, obj.id, zBase);
        f.setStyle(new ol.style.Style({
          zIndex: zBase,
          stroke: strokeForManagedObject(obj, s.lineColor, Number(s.lineWidth) || 2),
          fill: new ol.style.Fill({
            color: hexToRgba(s.fillColor, editing ? Math.max(0.08, Number(s.fillOpacity) || 0) : (Number(s.fillOpacity) || 0))
          })
        }));
        managedObjectSource.addFeature(f);
        const labelPos = (typeof offsetLatLon === "function")
          ? offsetLatLon(centerLat, centerLon, radius + 8, 0)
          : [centerLat, centerLon];
        const lf = new ol.Feature({ geometry: new ol.geom.Point(lonLatToMapCoord(Number(labelPos[1]), Number(labelPos[0]))) });
        tagManagedFeature(lf, obj.id, zLabel);
        lf.setStyle(new ol.style.Style({
          zIndex: zLabel,
          text: new ol.style.Text({
            text: String((s && s.labelText) || obj.labelText || "Kreis").slice(0, 80),
            font: `${Number(s.fontSize) || 12}px Arial`,
            fill: new ol.style.Fill({ color: editing ? "#f59e0b" : (s.labelColor || "#0000ff") }),
            stroke: new ol.style.Stroke({ color: "#ffffff", width: 3 })
          })
        }));
        managedObjectSource.addFeature(lf);
      } else if (obj.type === "circles" && obj.anchor) {
        const centerLon = Number(obj.anchor[0]);
        const centerLat = Number(obj.anchor[1]);
        const center = lonLatToMapCoord(centerLon, centerLat);
        const circleStyles = (obj.circlesStyles && Array.isArray(obj.circlesStyles) && obj.circlesStyles.length)
          ? obj.circlesStyles
          : ((styles && Array.isArray(styles.circles) && styles.circles.length)
          ? styles.circles
          : [
            { lineColor: "#00aa00", fillColor: "#00aa00", fillOpacity: 0, labelColor: "#0000ff", fontSize: 12, lineWidth: 2 },
            { lineColor: "#ffcc00", fillColor: "#ffcc00", fillOpacity: 0, labelColor: "#0000ff", fontSize: 12, lineWidth: 2 },
            { lineColor: "#ff0000", fillColor: "#ff0000", fillOpacity: 0, labelColor: "#0000ff", fontSize: 12, lineWidth: 2 }
          ]);
        [obj.r1, obj.r2, obj.r3].forEach((r, idx) => {
          const radius = Math.max(1, Number(r) || 1);
          const coords = (typeof createCircleCoordinates === "function")
            ? createCircleCoordinates(centerLat, centerLon, radius, 96)
            : [];
          const ring = coords.length
            ? coords.map((p) => lonLatToMapCoord(Number(p[0]), Number(p[1])))
            : ol.geom.Polygon.fromCircle(new ol.geom.Circle(center, radius), 96).getCoordinates()[0];
          const s = circleStyles[Math.min(idx, circleStyles.length - 1)] || circleStyles[0];
          const f = new ol.Feature({ geometry: new ol.geom.Polygon([ring]) });
          tagManagedFeature(f, obj.id, zBase);
          f.setStyle(new ol.style.Style({
            zIndex: zBase,
            stroke: strokeForManagedObject(obj, s.lineColor, Number(s.lineWidth) || 2),
            fill: new ol.style.Fill({ color: hexToRgba(s.fillColor, editing ? Math.max(0.08, Number(s.fillOpacity) || 0) : (Number(s.fillOpacity) || 0)) })
          }));
          managedObjectSource.addFeature(f);
          const labelPos = (typeof offsetLatLon === "function")
            ? offsetLatLon(centerLat, centerLon, radius + 8, 0)
            : [centerLat, centerLon];
          const lf = new ol.Feature({ geometry: new ol.geom.Point(lonLatToMapCoord(Number(labelPos[1]), Number(labelPos[0]))) });
          tagManagedFeature(lf, obj.id, zLabel);
          lf.setStyle(new ol.style.Style({
            zIndex: zLabel,
            text: new ol.style.Text({
              text: `C${idx + 1}`,
              font: `${Number(s.fontSize) || 12}px Arial`,
              fill: new ol.style.Fill({ color: editing ? "#f59e0b" : (s.labelColor || "#0000ff") }),
              stroke: new ol.style.Stroke({ color: "#ffffff", width: 3 })
            })
          }));
          managedObjectSource.addFeature(lf);
        });
      } else if (obj.type === "grid" && obj.anchor) {
        const style = (obj.gridStyle && typeof obj.gridStyle === "object")
          ? obj.gridStyle
          : ((styles && styles.grid) ? styles.grid : {
          lineColor: "#ffd400", lineWidth: 2, fillColor: "#ffd400", fillOpacity: 0, labelColor: "#0000ff", fontSize: 11
          });
        for (let n = 0; n < Number(obj.northCount || 0); n += 1) {
          for (let e = 0; e < Number(obj.eastCount || 0); e += 1) {
            const rot = Number(obj.rotation) || 0;
            const sw = rotatedOffsetLatLon(obj.anchor[1], obj.anchor[0], n * obj.spacing, e * obj.spacing, rot);
            const se = rotatedOffsetLatLon(obj.anchor[1], obj.anchor[0], n * obj.spacing, (e + 1) * obj.spacing, rot);
            const ne = rotatedOffsetLatLon(obj.anchor[1], obj.anchor[0], (n + 1) * obj.spacing, (e + 1) * obj.spacing, rot);
            const nw = rotatedOffsetLatLon(obj.anchor[1], obj.anchor[0], (n + 1) * obj.spacing, e * obj.spacing, rot);
            const center = rotatedOffsetLatLon(obj.anchor[1], obj.anchor[0], (n + 0.5) * obj.spacing, (e + 0.5) * obj.spacing, rot);
            const ring = [sw, se, ne, nw, sw].map((p) => lonLatToMapCoord(p[1], p[0]));
            const cell = new ol.Feature({ geometry: new ol.geom.Polygon([ring]) });
            tagManagedFeature(cell, obj.id, zBase);
            cell.set("previewType", "grid-cell");
            cell.set("gridCellN", n);
            cell.set("gridCellE", e);
            cell.setStyle(new ol.style.Style({
              zIndex: zBase,
              stroke: strokeForManagedObject(obj, style.lineColor, Number(style.lineWidth) || 2),
              fill: new ol.style.Fill({
                color: gridCellFillRgba(style, obj.cellFills, n, e, editing)
              })
            }));
            managedObjectSource.addFeature(cell);
            const cellName = `${eastLabel(e)}${n + 1}`;
            const labelFeature = new ol.Feature({ geometry: new ol.geom.Point(lonLatToMapCoord(center[1], center[0])) });
            tagManagedFeature(labelFeature, obj.id, zLabel);
            labelFeature.set("previewType", "grid-cell-label");
            labelFeature.set("gridCellN", n);
            labelFeature.set("gridCellE", e);
            labelFeature.setStyle(new ol.style.Style({
              zIndex: zLabel,
              text: new ol.style.Text({
                text: cellName,
                font: `${Number(style.fontSize) || 11}px Arial`,
                fill: new ol.style.Fill({ color: editing ? "#f59e0b" : (style.labelColor || "#0000ff") }),
                stroke: new ol.style.Stroke({ color: "#ffffff", width: 3 })
              })
            }));
            managedObjectSource.addFeature(labelFeature);
          }
        }
      } else if (obj.type === "polygon" && Array.isArray(obj.polygonVertices) && obj.polygonVertices.length >= 3) {
        const polyStyle = (obj.polygonStyle && typeof obj.polygonStyle === "object")
          ? obj.polygonStyle
          : ((styles && styles.polygon) ? styles.polygon : {
          lineColor: "#ff8c00", lineWidth: 2, fillColor: "#ff8c00", fillOpacity: 0.1
          });
        const ring = [...obj.polygonVertices, obj.polygonVertices[0]].map((p) => lonLatToMapCoord(p[0], p[1]));
        const f = new ol.Feature({ geometry: new ol.geom.Polygon([ring]) });
        tagManagedFeature(f, obj.id, zBase);
        f.set("previewType", "polygon");
        f.setStyle(new ol.style.Style({
          zIndex: zBase,
          stroke: strokeForManagedObject(obj, polyStyle.lineColor || "#ff8c00", Number(polyStyle.lineWidth) || 2),
          fill: new ol.style.Fill({
            color: hexToRgba(polyStyle.fillColor || "#ff8c00", editing ? Math.max(0.12, Number(polyStyle.fillOpacity) || 0.1) : (Number(polyStyle.fillOpacity) || 0.1))
          })
        }));
        managedObjectSource.addFeature(f);
      } else if ((obj.type === "freehand" || obj.type === "pfeil") && Array.isArray(obj.coordinates) && obj.coordinates.length >= 2) {
        const mapCoords = obj.coordinates.map((p) => lonLatToMapCoord(Number(p[0]), Number(p[1])));
        const f = new ol.Feature({ geometry: new ol.geom.LineString(mapCoords) });
        tagManagedFeature(f, obj.id, zBase);
        f.set("previewType", obj.type === "pfeil" ? "pfeil" : "freehand");
        const lc = obj.lineColor || "#e11d48";
        const lw = Number(obj.lineWidth) || 3;
        const styles = [
          new ol.style.Style({
            zIndex: zBase,
            stroke: strokeForManagedObject(obj, lc, lw)
          })
        ];
        if (obj.type === "pfeil") {
          buildPfeilEndOlStyles(mapCoords, editing ? "#f59e0b" : lc, lw, obj.endStyle, zBase)
            .forEach((s) => styles.push(s));
        }
        f.setStyle(styles);
        managedObjectSource.addFeature(f);
      } else if ((obj.type === "points" || obj.type === "textfields") && Array.isArray(obj.labels)) {
        obj.labels.forEach((lp, labelIdx) => {
          const f = new ol.Feature({ geometry: new ol.geom.Point(lonLatToMapCoord(lp.lon, lp.lat)) });
          tagManagedFeature(f, obj.id, zBase);
          f.set("previewType", "label-point");
          f.set("labelIndex", labelIdx);
          const defaults = (typeof labelPointDefaultsByType === "function") ? labelPointDefaultsByType(lp.type) : { color: "#ff8c00" };
          const pointRadius = Math.max(0, Number.isFinite(Number(lp.pointRadius)) ? Number(lp.pointRadius) : Number(defaults.pointRadius ?? 7));
          const pointColor = lp.color || defaults.color || "#ff8c00";
          const textColor = lp.textColor || defaults.textColor || defaults.color || "#111111";
          const fontSize = Math.max(8, Math.min(72, Number.isFinite(Number(lp.fontSize)) ? Number(lp.fontSize) : getObjectLabelFontSize()));
          f.setStyle(new ol.style.Style({
            zIndex: zBase,
            image: pointRadius > 0 ? new ol.style.Circle({
              radius: pointRadius,
              fill: new ol.style.Fill({ color: pointColor }),
              stroke: selectionStroke
            }) : undefined,
            text: new ol.style.Text({
              text: String(lp.text || defaults.defaultText || "Text"),
              // Gleiche Position wie beim Bearbeiten (map_editor renderLabeledPoints)
              offsetX: pointRadius > 0 ? 14 : 0,
              offsetY: 0,
              textAlign: "left",
              textBaseline: "middle",
              font: `${fontSize}px Arial`,
              fill: new ol.style.Fill({ color: textColor }),
              stroke: new ol.style.Stroke({
                color: (typeof contrastStrokeForPointFill === "function")
                  ? contrastStrokeForPointFill(textColor)
                  : "#ffffff",
                width: 3
              })
            })
          }));
          managedObjectSource.addFeature(f);
        });
      }
      const __gAlpha = getManagedObjectGroupOpacity(obj);
      if (__gAlpha < 0.999) dimManagedFeaturesForObject(obj.id, __gAlpha);
    });
    if (!(arguments[0] && arguments[0].skipListRefresh)) {
      refreshManagedObjectList();
    }
    try { refreshObjectActionButtons(); } catch (_) { /* noop */ }
  }

  function isPolygonDrawBlockingPick() {
    // While polygon drawing is active, or polygon draft vertices exist (not yet fixiert),
    // do not select/activate managed rasters/grids under the cursor.
    if (typeof isPolygonDrawing !== "undefined" && isPolygonDrawing) return true;
    if (exportTypeEl && normalizeObjectType(exportTypeEl.value) === "polygon"
        && typeof polygonVertices !== "undefined"
        && Array.isArray(polygonVertices)
        && polygonVertices.length > 0) {
      return true;
    }
    return false;
  }

  function pickManagedFeatureAtPixel(pixel, opts) {
    if (!map || !managedObjectSource || !pixel) return null;
    const hitTol = (opts && Number(opts.hitTolerance) > 0) ? Number(opts.hitTolerance) : 14;
    let best = null;
    let bestZ = -Infinity;
    let bestLabel = null;
    let bestLabelZ = -Infinity;
    map.forEachFeatureAtPixel(pixel, (feature) => {
      const id = Number(feature.get("managedObjectId"));
      if (!Number.isInteger(id)) return undefined;
      let z = Number(feature.get("managedZIndex"));
      if (!Number.isFinite(z)) {
        const st = feature.getStyle();
        const resolved = typeof st === "function" ? st(feature, 1) : st;
        const one = Array.isArray(resolved) ? resolved[0] : resolved;
        z = (one && typeof one.getZIndex === "function") ? (Number(one.getZIndex()) || 0) : 0;
      }
      const ptype = String(feature.get("previewType") || "");
      if (ptype === "grid-cell-label") {
        if (z >= bestLabelZ) {
          bestLabelZ = z;
          bestLabel = feature;
        }
      } else if (z >= bestZ) {
        bestZ = z;
        best = feature;
      }
      return undefined;
    }, {
      hitTolerance: hitTol,
      layerFilter: (layer) => layer && typeof layer.getSource === "function" && layer.getSource() === managedObjectSource
    });
    // Kachelschrift hat Vorrang vor der Zellfläche (sonst trifft man oft nur das Polygon)
    return bestLabel || best;
  }

  function pickManagedGridCellLabelAtPixel(pixel) {
    if (!map || !managedObjectSource || !pixel) return null;
    let best = null;
    let bestZ = -Infinity;
    map.forEachFeatureAtPixel(pixel, (feature) => {
      if (String(feature.get("previewType") || "") !== "grid-cell-label") return undefined;
      const id = Number(feature.get("managedObjectId"));
      if (!Number.isInteger(id)) return undefined;
      let z = Number(feature.get("managedZIndex"));
      if (!Number.isFinite(z)) z = 0;
      if (z >= bestZ) {
        bestZ = z;
        best = feature;
      }
      return undefined;
    }, {
      hitTolerance: 18,
      layerFilter: (layer) => layer && typeof layer.getSource === "function" && layer.getSource() === managedObjectSource
    });
    return best;
  }

  function applyManagedObjectToUi(obj) {
    if (!obj || !exportTypeEl) return;
    window.__applyingManagedObjectToUi = true;
    try {
      exportTypeEl.value = normalizeObjectType(obj.type);
      exportTypeEl.dispatchEvent(new Event("change"));
      if (obj.type === "circle" && obj.anchor) {
        selectedLonLat = cloneLonLat(obj.anchor);
        const radius = Math.max(1, Number(obj.r) || (Number(obj.diameter) / 2) || 100);
        const r1 = document.getElementById("radius1");
        if (r1) r1.value = String(radius);
        const rEl = document.getElementById("objSingleRadius");
        if (rEl) rEl.value = String(Math.round(radius));
        const d = document.getElementById("objSingleDiameter");
        if (d) d.value = String(Math.max(1, Math.round(Number(obj.diameter) || (radius * 2))));
        if (obj.circleStyle && typeof obj.circleStyle === "object") {
          setInputValueIfPresent("c1LineColor", obj.circleStyle.lineColor);
          setInputValueIfPresent("c1FillColor", obj.circleStyle.fillColor);
          setInputValueIfPresent("c1FillOpacity", obj.circleStyle.fillOpacity);
          setInputValueIfPresent("c1LabelColor", obj.circleStyle.labelColor);
          setInputValueIfPresent("c1FontSize", obj.circleStyle.fontSize);
          setInputValueIfPresent("c1LineWidth", obj.circleStyle.lineWidth);
          try {
            setCircleLabelTextToUi(obj.circleStyle.labelText || obj.labelText || "Kreis");
          } catch (_) {
            setInputValueIfPresent("c1LabelText", obj.circleStyle.labelText || "Kreis");
            setInputValueIfPresent("objCircleLabelText", obj.circleStyle.labelText || "Kreis");
          }
        } else {
          try { setCircleLabelTextToUi(obj.labelText || "Kreis"); } catch (_) { /* noop */ }
        }
        try { ensureCircleLabelAndStyleUi("circle"); } catch (_) { /* noop */ }
        if (typeof setMarker === "function") setMarker(lonLatToMapCoord(obj.anchor[0], obj.anchor[1]));
        if (typeof renderCoord === "function") renderCoord();
      } else if (obj.type === "circles" && obj.anchor) {
        selectedLonLat = cloneLonLat(obj.anchor);
        document.getElementById("radius1").value = String(obj.r1);
        document.getElementById("radius2").value = String(obj.r2);
        document.getElementById("radius3").value = String(obj.r3);
        setInputValueIfPresent("objRadius1", obj.r1);
        setInputValueIfPresent("objRadius2", obj.r2);
        setInputValueIfPresent("objRadius3", obj.r3);
        setInputValueIfPresent("objDiameter1", Math.max(1, Number(obj.r1) || 100) * 2);
        setInputValueIfPresent("objDiameter2", Math.max(1, Number(obj.r2) || 200) * 2);
        setInputValueIfPresent("objDiameter3", Math.max(1, Number(obj.r3) || 300) * 2);
        if (Array.isArray(obj.circlesStyles)) {
          const s1 = obj.circlesStyles[0] || {};
          const s2 = obj.circlesStyles[1] || {};
          const s3 = obj.circlesStyles[2] || {};
          setInputValueIfPresent("c1LineColor", s1.lineColor); setInputValueIfPresent("c1FillColor", s1.fillColor); setInputValueIfPresent("c1FillOpacity", s1.fillOpacity); setInputValueIfPresent("c1LabelColor", s1.labelColor); setInputValueIfPresent("c1FontSize", s1.fontSize); setInputValueIfPresent("c1LineWidth", s1.lineWidth);
          setInputValueIfPresent("c2LineColor", s2.lineColor); setInputValueIfPresent("c2FillColor", s2.fillColor); setInputValueIfPresent("c2FillOpacity", s2.fillOpacity); setInputValueIfPresent("c2LabelColor", s2.labelColor); setInputValueIfPresent("c2FontSize", s2.fontSize); setInputValueIfPresent("c2LineWidth", s2.lineWidth);
          setInputValueIfPresent("c3LineColor", s3.lineColor); setInputValueIfPresent("c3FillColor", s3.fillColor); setInputValueIfPresent("c3FillOpacity", s3.fillOpacity); setInputValueIfPresent("c3LabelColor", s3.labelColor); setInputValueIfPresent("c3FontSize", s3.fontSize); setInputValueIfPresent("c3LineWidth", s3.lineWidth);
        }
        if (typeof setMarker === "function") setMarker(lonLatToMapCoord(obj.anchor[0], obj.anchor[1]));
        if (typeof renderCoord === "function") renderCoord();
      } else if (obj.type === "grid" && obj.anchor) {
        selectedLonLat = cloneLonLat(obj.anchor);
        const sp = document.getElementById("spacing");
        const nc = document.getElementById("northCount");
        const ec = document.getElementById("eastCount");
        if (sp) sp.value = String(obj.spacing);
        if (nc) nc.value = String(obj.northCount);
        if (ec) ec.value = String(obj.eastCount);
        setInputValueIfPresent("objGridSize", obj.spacing);
        setInputValueIfPresent("objNorthCount", obj.northCount);
        setInputValueIfPresent("objEastCount", obj.eastCount);
        applyCellFillsToUi(obj.cellFills);
        applyGridRotation(obj.rotation || 0, { immediate: true, force: true });
        if (obj.gridStyle && typeof obj.gridStyle === "object") {
          setInputValueIfPresent("gridLineColor", obj.gridStyle.lineColor);
          setInputValueIfPresent("gridLineWidth", obj.gridStyle.lineWidth);
          setInputValueIfPresent("gridFillColor", obj.gridStyle.fillColor);
          setInputValueIfPresent("gridFillOpacity", obj.gridStyle.fillOpacity);
          setInputValueIfPresent("gridLabelColor", obj.gridStyle.labelColor);
          setInputValueIfPresent("gridFontSize", obj.gridStyle.fontSize);
        }
        if (typeof setMarker === "function") setMarker(lonLatToMapCoord(obj.anchor[0], obj.anchor[1]));
        if (typeof renderCoord === "function") renderCoord();
      } else if (obj.type === "polygon") {
        polygonVertices = Array.isArray(obj.polygonVertices) ? obj.polygonVertices.map(cloneLonLat) : [];
        if (obj.polygonStyle && typeof obj.polygonStyle === "object") {
          setInputValueIfPresent("polyLineColor", obj.polygonStyle.lineColor);
          setInputValueIfPresent("polyLineWidth", obj.polygonStyle.lineWidth);
          setInputValueIfPresent("polyFillColor", obj.polygonStyle.fillColor);
          setInputValueIfPresent("polyFillOpacity", obj.polygonStyle.fillOpacity);
          setInputValueIfPresent("polyLabelColor", obj.polygonStyle.labelColor);
          setInputValueIfPresent("polyFontSize", obj.polygonStyle.fontSize);
        }
        if (typeof renderPolygonEditor === "function") renderPolygonEditor();
      } else if (obj.type === "freehand" || obj.type === "pfeil") {
        freehandDraftCoords = Array.isArray(obj.coordinates) ? obj.coordinates.map((p) => [Number(p[0]), Number(p[1])]) : [];
        if (obj.type === "pfeil") {
          setPfeilStyleToUi(obj);
          const st = document.getElementById("objPfeilStatus");
          if (st) st.textContent = freehandDraftCoords.length >= 2
            ? "Pfeil geladen — Endpunkte verschieben oder Fixieren."
            : "Pfeil unvollständig.";
        } else {
          const colorEl = document.getElementById("objFreehandColor");
          const widthEl = document.getElementById("objFreehandWidth");
          const st = document.getElementById("objFreehandStatus");
          if (colorEl && obj.lineColor) colorEl.value = String(obj.lineColor);
          if (widthEl && obj.lineWidth) widthEl.value = String(obj.lineWidth);
          setInputValueIfPresent("styleFreehandColor", obj.lineColor || (colorEl && colorEl.value));
          setInputValueIfPresent("styleFreehandWidth", obj.lineWidth || (widthEl && widthEl.value));
          if (st) st.textContent = freehandDraftCoords.length
            ? `Linie mit ${freehandDraftCoords.length} Punkten.`
            : "Keine Linie.";
        }
        renderFreehandDraftPreview();
      } else if (obj.type === "points" || obj.type === "textfields") {
        labeledPoints = Array.isArray(obj.labels) ? obj.labels.map((p) => ({ ...p })) : [];
        if (typeof selectedLabelPointIndex !== "undefined") {
          selectedLabelPointIndex = labeledPoints.length ? 0 : -1;
        }
        const lp = labeledPoints[0] || null;
        if (lp) {
          const typeEl = document.getElementById("labelPointType");
          const textEl = document.getElementById("labelPointText");
          if (typeEl && lp.type) {
            typeEl.value = String(lp.type);
            if (typeEl.value !== String(lp.type)) {
              const o = document.createElement("option");
              o.value = String(lp.type);
              o.textContent = String(lp.type);
              typeEl.appendChild(o);
              typeEl.value = String(lp.type);
            }
          }
          if (textEl) textEl.value = String(lp.text || "");
          if (typeof lastLabelPointDefaultText !== "undefined") {
            lastLabelPointDefaultText = String(lp.text || "");
          }
          if (lp.textColor) setInputValueIfPresent("objLabelTextColor", lp.textColor);
          if (lp.color) setInputValueIfPresent("objPointColor", lp.color);
          const fs = Number(lp.fontSize);
          if (Number.isFinite(fs)) {
            const n = String(Math.max(8, Math.min(72, Math.round(fs))));
            setInputValueIfPresent("objLabelFontSize", n);
            setInputValueIfPresent("objLabelFontSizeLeft", n);
          }
        }
        const renderFn = (typeof renderLabeledPoints === "function")
          ? (renderLabeledPoints.__objListSyncOrig || renderLabeledPoints)
          : null;
        if (renderFn) renderFn();
      }
      try { if (typeof updateStyleBoxVisibility === "function") updateStyleBoxVisibility(); } catch (_) { /* noop */ }
      try { if (typeof updateSwatches === "function") updateSwatches(); } catch (_) { /* noop */ }
    } finally {
      window.__applyingManagedObjectToUi = false;
    }
  }

  function shiftLonLatByMapDelta(lonLat, dx, dy) {
    const c = lonLatToMapCoord(lonLat[0], lonLat[1]);
    const moved = ol.proj.transform([Number(c[0]) + Number(dx), Number(c[1]) + Number(dy)], "EPSG:3857", "EPSG:4326");
    return [Number(moved[0]), Number(moved[1])];
  }

  function shiftManagedObject(obj, dx, dy) {
    if (!obj) return;
    if ((obj.type === "circle" || obj.type === "circles" || obj.type === "grid") && obj.anchor) {
      obj.anchor = shiftLonLatByMapDelta(obj.anchor, dx, dy);
      if (obj.id === selectedManagedObjectId) {
        selectedLonLat = cloneLonLat(obj.anchor);
        if (typeof setMarker === "function") setMarker(lonLatToMapCoord(obj.anchor[0], obj.anchor[1]));
        if (typeof renderCoord === "function") renderCoord();
        if (typeof previewActive !== "undefined") previewActive = true;
        if (typeof drawPreview === "function") drawPreview();
      }
    } else if (obj.type === "polygon" && Array.isArray(obj.polygonVertices)) {
      obj.polygonVertices = obj.polygonVertices.map((p) => shiftLonLatByMapDelta(p, dx, dy));
    } else if ((obj.type === "freehand" || obj.type === "pfeil") && Array.isArray(obj.coordinates)) {
      obj.coordinates = obj.coordinates.map((p) => shiftLonLatByMapDelta(p, dx, dy));
      if (obj.id === selectedManagedObjectId) {
        freehandDraftCoords = obj.coordinates.map((p) => [Number(p[0]), Number(p[1])]);
        renderFreehandDraftPreview();
      }
    } else if ((obj.type === "points" || obj.type === "textfields") && Array.isArray(obj.labels)) {
      obj.labels = obj.labels.map((p) => {
        const next = shiftLonLatByMapDelta([p.lon, p.lat], dx, dy);
        return { ...p, lon: next[0], lat: next[1] };
      });
    }
  }



  function isSubEditorSession() {
    try {
      if (document.body && (
        document.body.classList.contains("rkrk-share-sub")
        || document.body.classList.contains("rkrk-share-subeditor")
        || document.body.classList.contains("rkrk-share-coeditor")
      )) return true;
      // Host nie als Sub behandeln
      if (document.body && document.body.classList.contains("rkrk-share-host")
          && !document.body.classList.contains("rkrk-share-sub")) {
        return false;
      }
      // Fallback: Share+Edit-URL ohne Host-Klasse
      try {
        const q = new URLSearchParams(window.location.search || "");
        const tok = q.get("share") || q.get("token");
        const edit = String(q.get("edit") || "").toLowerCase();
        if (tok && (edit === "1" || edit === "true" || edit === "yes")) return true;
      } catch (_) { /* noop */ }
      return false;
    } catch (_) {
      return false;
    }
  }
  try { window.__rkrkIsSubEditorSession = isSubEditorSession; } catch (_) {}

  function isGridCellLabelAtPixel(pixel) {
    if (!pixel) return false;
    try {
      if (typeof getGridCellLabelFeatureAtPixel === "function") {
        const lf = getGridCellLabelFeatureAtPixel(pixel);
        if (lf && typeof parseGridCellIndicesFromFeature === "function") {
          const ix = parseGridCellIndicesFromFeature(lf);
          if (ix && Number.isInteger(ix.n) && Number.isInteger(ix.e)) return true;
        } else if (lf) return true;
      }
    } catch (_) { /* noop */ }
    try {
      if (!map || !managedObjectSource) return false;
      let hit = false;
      map.forEachFeatureAtPixel(pixel, (feature) => {
        if (String(feature.get("previewType") || "") === "grid-cell-label") {
          hit = true;
          return feature;
        }
        return undefined;
      }, {
        hitTolerance: 14,
        layerFilter: (layer) => layer && typeof layer.getSource === "function" && layer.getSource() === managedObjectSource
      });
      return hit;
    } catch (_) {
      return false;
    }
  }

  function guardCycleGridCellFillForSub() {
    try {
      const orig = window.cycleGridCellFill;
      if (typeof orig !== "function" || orig.__rkrkSubGuard) return;
      const guarded = function (n, e) {
        if (typeof isSubEditorSession === "function" && isSubEditorSession()) return;
        return orig(n, e);
      };
      guarded.__rkrkSubGuard = true;
      window.cycleGridCellFill = guarded;
    } catch (_) { /* noop */ }
  }

  function installManagedObjectHoverTooltip() {
    if (typeof map === "undefined" || !map || map.__objHoverTipInstalled) return;
    map.__objHoverTipInstalled = true;
    let tip = document.getElementById("objMapHoverTip");
    if (!tip) {
      tip = document.createElement("div");
      tip.id = "objMapHoverTip";
      tip.setAttribute("role", "tooltip");
      document.body.appendChild(tip);
    }
    let lastName = "";
    map.on("pointermove", (evt) => {
      try {
        if (evt.dragging || dragManagedState) {
          tip.classList.remove("show");
          return;
        }
        const f = pickManagedFeatureAtPixel(evt.pixel);
        const targetEl = map.getTargetElement ? map.getTargetElement() : null;
        if (!f) {
          tip.classList.remove("show");
          lastName = "";
          if (targetEl && targetEl.dataset && targetEl.dataset.objHoverCursor === "1") {
            targetEl.style.cursor = "";
            delete targetEl.dataset.objHoverCursor;
          }
          return;
        }
        const id = f.get("managedObjectId");
        const obj = managedObjects.find((o) => o && Number(o.id) === Number(id));
        if (!obj || (typeof isManagedObjectVisibleToMe === "function" && !isManagedObjectVisibleToMe(obj))) {
          tip.classList.remove("show");
          return;
        }
        const name = managedObjectDisplayName(obj);
        if (name !== lastName) {
          tip.textContent = name;
          lastName = name;
        }
        tip.classList.add("show");
        const oe = evt.originalEvent;
        const x = oe && Number.isFinite(oe.clientX) ? oe.clientX : 0;
        const y = oe && Number.isFinite(oe.clientY) ? oe.clientY : 0;
        tip.style.left = Math.max(6, x + 14) + "px";
        tip.style.top = Math.max(6, y + 16) + "px";
        if (targetEl) {
          targetEl.style.cursor = "pointer";
          targetEl.dataset.objHoverCursor = "1";
        }
      } catch (_) {
        try { tip.classList.remove("show"); } catch (__) {}
      }
    });
    map.getViewport().addEventListener("mouseleave", () => {
      tip.classList.remove("show");
      lastName = "";
      try {
        const targetEl = map.getTargetElement ? map.getTargetElement() : null;
        if (targetEl && targetEl.dataset && targetEl.dataset.objHoverCursor === "1") {
          targetEl.style.cursor = "";
          delete targetEl.dataset.objHoverCursor;
        }
      } catch (_) { /* noop */ }
    });
  }

  function installManagedObjectInteractions(retry) {
    if (typeof map === "undefined" || !map) {
      const n = Number(retry) || 0;
      if (n < 40) setTimeout(() => installManagedObjectInteractions(n + 1), 120);
      return;
    }
    if (map.__managedObjInteractionsInstalled) return;
    map.__managedObjInteractionsInstalled = true;
    try { installManagedObjectHoverTooltip(); } catch (_) { /* noop */ }
    try { guardCycleGridCellFillForSub(); } catch (_) { /* noop */ }
    try { setTimeout(guardCycleGridCellFillForSub, 800); } catch (_) { /* noop */ }
    const handleManagedObjectClick = (evt) => {
      if (isPolygonDrawBlockingPick()) return;
      if (typeof isFreehandDrawBlockingPick === "function" && isFreehandDrawBlockingPick()) return;
      const f = pickManagedFeatureAtPixel(evt.pixel);
      if (!f) {
        // Klick ins Leere: Fix-Auswahl aufheben (nicht während aktiver Bearbeitung)
        if (selectedManagedObjectId) {
          const cur = managedObjects.find((o) => o.id === selectedManagedObjectId);
          if (cur && cur.fixed) {
            selectedManagedObjectId = null;
            renderManagedObjects();
            try { refreshObjectActionButtons(); } catch (_) { /* noop */ }
          }
        }
        return;
      }
      const id = f.get("managedObjectId");
      if (!Number.isInteger(id)) return;
      const obj = managedObjects.find((o) => o.id === id);
      if (!obj) return;
      if (obj.fixed) {
        // Fixiertes Objekt nur anwählen — laufende Bearbeitung anderer Objekte beenden
        ensureSingleEditingObject(null, { sync: true });
        selectedManagedObjectId = id;
        managedEditPaused = false;
        applyManagedObjectToUi(obj);
        renderManagedObjects();
        try { refreshManagedObjectList(); } catch (_) { /* noop */ }
        try { refreshObjectActionButtons(); } catch (_) { /* noop */ }
        managedStatusText(`«${managedObjectDisplayName(obj)}» gewählt — Löschen oder Bearbeiten.`);
        return;
      }
      beginEditManagedObject(id);
    };
    // Raster: Auswahl nur am Rahmen; Beschriftung = Doppelklick-Farbe (Klick verzögern)
    const isNearGridCellFrame = (feature, coordinate) => {
      if (!feature || !coordinate) return false;
      const geom = feature.getGeometry && feature.getGeometry();
      if (!geom) return false;
      const tolPx = (typeof GRID_EDGE_STRIP_PX === "number") ? GRID_EDGE_STRIP_PX : 11;
      if (typeof isCoordinateNearPolygonEdge === "function") {
        return !!isCoordinateNearPolygonEdge(coordinate, geom, tolPx);
      }
      try {
        const res = map.getView().getResolution();
        if (!res || !Number.isFinite(res)) return false;
        const tol = tolPx * res;
        const closest = geom.getClosestPoint(coordinate);
        const dx = Number(coordinate[0]) - Number(closest[0]);
        const dy = Number(coordinate[1]) - Number(closest[1]);
        // Innerhalb der Zelle: Abstand zum Rand = Distanz zur Aussenkante nur wenn Punkt innen nah am Rand
        if (!geom.intersectsCoordinate(coordinate)) {
          return (dx * dx + dy * dy) <= (tol * tol);
        }
        // Punkt innen: Nähe zur Aussengrenze
        const ring = (typeof geom.getLinearRing === "function") ? geom.getLinearRing(0) : null;
        if (!ring) return false;
        const coords = ring.getCoordinates();
        let minSq = Infinity;
        const x = coordinate[0];
        const y = coordinate[1];
        for (let i = 0; i < coords.length - 1; i += 1) {
          const a = coords[i];
          const b = coords[i + 1];
          const abx = b[0] - a[0];
          const aby = b[1] - a[1];
          const apx = x - a[0];
          const apy = y - a[1];
          const ab2 = abx * abx + aby * aby;
          const t = ab2 > 0 ? Math.max(0, Math.min(1, (apx * abx + apy * aby) / ab2)) : 0;
          const cx = a[0] + t * abx;
          const cy = a[1] + t * aby;
          const ddx = x - cx;
          const ddy = y - cy;
          minSq = Math.min(minSq, ddx * ddx + ddy * ddy);
        }
        return minSq <= (tol * tol);
      } catch (_) {
        return false;
      }
    };

    // Native dblclick frueh abfangen → verzoegerter Klick startet nicht / laeuft nicht nach
    try {
      const vp = map.getViewport && map.getViewport();
      if (vp && !vp.__objDblClickCapture) {
        vp.__objDblClickCapture = true;
        vp.addEventListener("dblclick", (ev) => {
          try { clearTimeout(map.__objManagedClickTimer); } catch (_) {}
          map.__objManagedClickTimer = null;
          try {
            if (typeof isSubEditorSession === "function" && isSubEditorSession() && ev) {
              const pixel = map.getEventPixel ? map.getEventPixel(ev) : null;
              if (pixel && typeof isGridCellLabelAtPixel === "function" && isGridCellLabelAtPixel(pixel)) {
                ev.preventDefault();
                ev.stopPropagation();
                if (typeof ev.stopImmediatePropagation === "function") ev.stopImmediatePropagation();
              }
            }
          } catch (_) { /* noop */ }
        }, true);
      }
    } catch (_) { /* noop */ }

    map.on("click", (evt) => {
      const f = evt && evt.pixel ? pickManagedFeatureAtPixel(evt.pixel) : null;
      const hitType = f ? String(f.get("previewType") || "") : "";
      clearTimeout(map.__objManagedClickTimer);
      // Beschriftung: kein Anwählen — Farbe nur per Doppelklick
      if (hitType === "grid-cell-label") {
        return;
      }
      // Kachelfläche: nur am Rahmen markieren; Innenfläche ignorieren
      if (hitType === "grid-cell") {
        if (!isNearGridCellFrame(f, evt.coordinate)) {
          return;
        }
        const pixel = evt.pixel && evt.pixel.slice ? evt.pixel.slice() : evt.pixel;
        map.__objManagedClickTimer = setTimeout(() => {
          handleManagedObjectClick({ pixel: pixel, coordinate: evt.coordinate });
        }, 320);
        return;
      }
      handleManagedObjectClick(evt);
    });
    map.on("pointerdown", (evt) => {
      if (managedEditPaused) return;
      if (exportTypeEl && (normalizeObjectType(exportTypeEl.value) === "freehand" || normalizeObjectType(exportTypeEl.value) === "pfeil")) return;
      if (isPolygonDrawBlockingPick()) return;
      if (typeof isFreehandDrawBlockingPick === "function" && isFreehandDrawBlockingPick()) return;
      const f = pickManagedFeatureAtPixel(evt.pixel);
      if (!f) return;
      const hitType = String(f.get("previewType") || "");
      // Kachelschrift: kein Drag — sonst blockiert Doppelklick den Farbwechsel
      if (hitType === "grid-cell-label") return;
      if (hitType === "grid-cell" && !isNearGridCellFrame(f, evt.coordinate)) return;
      const id = Number(f.get("managedObjectId"));
      if (!Number.isInteger(id)) return;
      if (selectedManagedObjectId && id !== selectedManagedObjectId) return;
      dragManagedState = {
        id,
        last: evt.coordinate.slice()
      };
      if (map.getTargetElement()) map.getTargetElement().style.cursor = "grabbing";
    });
    map.on("pointermove", (evt) => {
      if (!dragManagedState) return;
      const dx = Number(evt.coordinate[0]) - Number(dragManagedState.last[0]);
      const dy = Number(evt.coordinate[1]) - Number(dragManagedState.last[1]);
      dragManagedState.last = evt.coordinate.slice();
      const obj = managedObjects.find((o) => o.id === dragManagedState.id);
      if (obj && obj.fixed) return;
      shiftManagedObject(obj, dx, dy);
      try { touchManagedObjectSyncMeta(obj); } catch (_) { /* noop */ }
      renderManagedObjects();
    });
    map.on("pointerup", () => {
      const wasDragging = !!dragManagedState;
      const draggedId = wasDragging ? dragManagedState.id : null;
      dragManagedState = null;
      if (map.getTargetElement()) map.getTargetElement().style.cursor = "";
      if (wasDragging) {
        try { syncEditingObjectFromUi(); } catch (_) { /* noop */ }
        try {
          const obj = managedObjects.find((o) => o && Number(o.id) === Number(draggedId));
          if (obj) touchManagedObjectSyncMeta(obj);
        } catch (_) { /* noop */ }
        try {
          if (typeof window.__rkrkScheduleLiveSharePush === "function") window.__rkrkScheduleLiveSharePush();
          else if (typeof scheduleLiveSharePush === "function") scheduleLiveSharePush();
        } catch (_) { /* noop */ }
      }
    });
    const viewport = map.getViewport();
    if (viewport) {
      viewport.addEventListener("contextmenu", (evt) => {
        if (typeof liveShareViewerMode !== "undefined" && liveShareViewerMode) return;
        const pixel = map.getEventPixel(evt);
        const f = pickManagedFeatureAtPixel(pixel);
        if (!f) return;
        const hitType = String(f.get("previewType") || "");
        const mid = f.get("managedObjectId");
        const mObj = managedObjects.find((o) => o.id === mid);
        evt.preventDefault();
        evt.stopPropagation();
        let fillTarget = null;
        if (mObj && mObj.type === "grid" && (hitType === "grid-cell" || hitType === "grid-cell-label")) {
          const n = Number(f.get("gridCellN"));
          const e = Number(f.get("gridCellE"));
          if (Number.isInteger(n) && Number.isInteger(e)) {
            fillTarget = { kind: "managed-grid-cell", objectId: mid, n, e };
          }
        } else if (mObj && mObj.type === "polygon" && (hitType === "polygon" || !hitType)) {
          fillTarget = { kind: "managed-polygon", objectId: mid };
        } else if (mObj && (mObj.type === "freehand" || mObj.type === "pfeil") && (hitType === "freehand" || hitType === "pfeil" || !hitType)) {
          fillTarget = { kind: "managed-freehand", objectId: mid };
        } else if (mObj && (mObj.type === "points" || mObj.type === "textfields")
            && (hitType === "label-point" || !hitType)) {
          const labelIndex = Number(f.get("labelIndex"));
          fillTarget = {
            kind: "managed-point",
            objectId: mid,
            labelIndex: Number.isInteger(labelIndex) ? labelIndex : -1
          };
        }
        if (typeof window.__rkrkShowObjectContextMenu === "function") {
          window.__rkrkShowObjectContextMenu(evt.clientX, evt.clientY, mid, fillTarget);
        } else {
          contextMenuTargetObjectId = mid;
          contextMenuFillTarget = fillTarget;
          const menu = document.getElementById("objObjectContextMenu");
          if (!menu) return;
          menu.style.display = "block";
          menu.style.left = `${Math.min(window.innerWidth - 210, Math.max(8, evt.clientX))}px`;
          menu.style.top = `${Math.min(window.innerHeight - 160, Math.max(8, evt.clientY))}px`;
        }
      });
    }
    map.on("dblclick", (evt) => {
      if (gridRulerActive) return;
      // Verzoegerten Einfachklick abbrechen (sonst wird Raster zur Bearbeitung markiert)
      try { clearTimeout(map.__objManagedClickTimer); } catch (_) { /* noop */ }
      map.__objManagedClickTimer = null;
      // Doppelklick-Zoom nie wieder aktivieren (Stabilitaet)
      try {
        map.getInteractions().forEach((interaction) => {
          if (interaction && interaction.constructor && interaction.constructor.name === "DoubleClickZoom") {
            map.removeInteraction(interaction);
          }
          try {
            if (typeof ol !== "undefined" && ol.interaction && interaction instanceof ol.interaction.DoubleClickZoom) {
              map.removeInteraction(interaction);
            }
          } catch (_) { /* noop */ }
        });
      } catch (_) { /* noop */ }
      const stopEvt = () => {
        if (evt && typeof evt.preventDefault === "function") evt.preventDefault();
        if (evt && typeof evt.stopPropagation === "function") evt.stopPropagation();
        try {
          if (evt && evt.originalEvent) {
            if (typeof evt.originalEvent.preventDefault === "function") evt.originalEvent.preventDefault();
            if (typeof evt.originalEvent.stopPropagation === "function") evt.originalEvent.stopPropagation();
            if (typeof evt.originalEvent.stopImmediatePropagation === "function") {
              evt.originalEvent.stopImmediatePropagation();
            }
          }
        } catch (_) { /* noop */ }
        return false;
      };
      try { guardCycleGridCellFillForSub(); } catch (_) { /* noop */ }
      // Sub-Editor: Doppelklick auf Kachelschrift = kein Farbwechsel (frueh abbrechen)
      try {
        if (typeof isSubEditorSession === "function" && isSubEditorSession()
            && evt && evt.pixel && typeof isGridCellLabelAtPixel === "function"
            && isGridCellLabelAtPixel(evt.pixel)) {
          return stopEvt();
        }
      } catch (_) { /* noop */ }
      const cyclePreviewCell = (n, e) => {
        if (typeof isSubEditorSession === "function" && isSubEditorSession()) {
          stopEvt();
          return true;
        }
        if (typeof window.cycleGridCellFill !== "function") return false;
        window.cycleGridCellFill(n, e);
        try { if (typeof drawPreview === "function") drawPreview(); } catch (_) { /* noop */ }
        try { syncEditingObjectFromUi(); } catch (_) { /* noop */ }
        try {
          if (typeof window.applyUiStylesToEditingObject === "function") {
            window.applyUiStylesToEditingObject({});
          }
        } catch (_) { /* noop */ }
        try { if (typeof window.notifyLiveShareChange === "function") window.notifyLiveShareChange(); } catch (_) { /* noop */ }
        managedStatusText(`Kachel ${eastLabel(e)}${n + 1}: Farbe gewechselt.`);
        stopEvt();
        return true;
      };
      // Farbe NUR bei Doppelklick auf die Kachel-Beschriftung (nicht auf die Flaeche)
      // Sub-Editor: Doppelklick auf Schrift = kein Effekt
      const subNoLabelFx = (typeof isSubEditorSession === "function") && isSubEditorSession();
      // 1) Vorschau-Raster (auch wenn Typ kurz anders steht — Label-Hit entscheidet)
      if (evt && evt.pixel) {
        const labelFeat = (typeof getGridCellLabelFeatureAtPixel === "function")
          ? getGridCellLabelFeatureAtPixel(evt.pixel)
          : null;
        if (labelFeat && typeof parseGridCellIndicesFromFeature === "function") {
          const ix = parseGridCellIndicesFromFeature(labelFeat);
          if (ix && Number.isInteger(ix.n) && Number.isInteger(ix.e)) {
            if (subNoLabelFx) return stopEvt();
            if (cyclePreviewCell(ix.n, ix.e)) return false;
          }
        }
      }
      if (evt && evt.pixel && (!exportTypeEl || exportTypeEl.value === "grid")) {
        // Doppelklick auf Kachelflaeche: weder Farbe noch Bearbeiten
        if (typeof getGridCellFeatureAtPixel === "function") {
          const cellFeat = getGridCellFeatureAtPixel(evt.pixel);
          if (cellFeat && parseGridCellIndicesFromFeature(cellFeat)
              && !(typeof getGridCellLabelFeatureAtPixel === "function" && getGridCellLabelFeatureAtPixel(evt.pixel))) {
            return stopEvt();
          }
        }
      }
      // 2) Gemanagtes Raster — nur Label (extra Hit-Toleranz, Label vor Flaeche)
      const managedLabelHit = (typeof pickManagedGridCellLabelAtPixel === "function")
        ? pickManagedGridCellLabelAtPixel(evt.pixel)
        : null;
      const managedHit = managedLabelHit || pickManagedFeatureAtPixel(evt.pixel, { hitTolerance: 16 });
      const hitType = managedHit ? String(managedHit.get("previewType") || "") : "";
      if (managedHit && hitType === "grid-cell-label") {
        if (subNoLabelFx) return stopEvt();
        const mid = Number(managedHit.get("managedObjectId"));
        const mObj = managedObjects.find((o) => Number(o.id) === mid);
        if (mObj && mObj.type === "grid") {
          const n = Number(managedHit.get("gridCellN"));
          const e = Number(managedHit.get("gridCellE"));
          if (Number.isInteger(n) && Number.isInteger(e)) {
            if (!mObj.fixed && Number(mObj.id) === Number(selectedManagedObjectId)
                && typeof window.cycleGridCellFill === "function") {
              if (cyclePreviewCell(n, e)) return false;
            }
            if (mObj.fixed) {
              if (typeof isSubEditorSession === "function" && isSubEditorSession()) return stopEvt();
              rememberFixedHistory();
              if (!mObj.cellFills || typeof mObj.cellFills !== "object") mObj.cellFills = {};
              const key = `${n},${e}`;
              const cur = mObj.cellFills[key];
              const pal = (typeof getGridCellFillPaletteForKml === "function")
                ? getGridCellFillPaletteForKml()
                : null;
              const palLen = (Array.isArray(pal) && pal.length) ? pal.length
                : ((typeof GRID_CELL_FILL_PALETTE !== "undefined" && Array.isArray(GRID_CELL_FILL_PALETTE))
                  ? GRID_CELL_FILL_PALETTE.length : 5);
              const cycleLen = (typeof GRID_CELL_FILL_CYCLE_LEN === "number" && GRID_CELL_FILL_CYCLE_LEN > 0)
                ? Math.min(GRID_CELL_FILL_CYCLE_LEN, palLen)
                : palLen;
              if (cur === undefined || !Number.isInteger(cur) || cur < 0) mObj.cellFills[key] = 0;
              else if (cur >= cycleLen - 1) delete mObj.cellFills[key];
              else mObj.cellFills[key] = cur + 1;
              try { touchManagedObjectSyncMeta(mObj); } catch (_) { /* noop */ }
              renderManagedObjects();
              markFixedHistoryCursor();
              try { if (typeof window.notifyLiveShareChange === "function") window.notifyLiveShareChange(); } catch (_) { /* noop */ }
              managedStatusText(`Kachel ${eastLabel(e)}${n + 1}: Farbe gewechselt.`);
              return stopEvt();
            }
            if (typeof window.cycleGridCellFill === "function") {
              if (cyclePreviewCell(n, e)) return false;
            }
            return stopEvt();
          }
        }
        return stopEvt();
      }
      // Doppelklick auf Kachelflaeche (managed): nicht bearbeiten
      if (managedHit && hitType === "grid-cell") {
        stopEvt();
        return false;
      }
      const ok = (typeof finalizeCurrentObjectAsFixedRef === "function")
        ? finalizeCurrentObjectAsFixedRef()
        : false;
      if (ok) {
        if (evt && typeof evt.preventDefault === "function") evt.preventDefault();
        if (evt && typeof evt.stopPropagation === "function") evt.stopPropagation();
        managedStatusText("Objekt per Doppelklick fixiert.");
        return;
      }
      if (isPolygonDrawBlockingPick()) return;
      if (typeof isFreehandDrawBlockingPick === "function" && isFreehandDrawBlockingPick()) return;
      const f = pickManagedFeatureAtPixel(evt.pixel);
      if (f) {
        const ft = String(f.get("previewType") || "");
        // Raster nie per Doppelklick in Bearbeitung nehmen
        if (ft === "grid-cell" || ft === "grid-cell-label") {
          stopEvt();
          return false;
        }
        const id = f.get("managedObjectId");
        beginEditManagedObject(id);
        if (evt && typeof evt.preventDefault === "function") evt.preventDefault();
      }
    });
  }

  function applyPaletteFillToPolygonStyle(style, value) {
    const base = (style && typeof style === "object") ? { ...style } : {
      lineColor: "#ff8c00", lineWidth: 2, fillColor: "#ff8c00", fillOpacity: 0.1,
      labelColor: "#0000ff", fontSize: 12
    };
    const pal = getGridCellFillPaletteForKml();
    if (value === "neutral") {
      base.fillOpacity = 0;
      return base;
    }
    const idx = Number(value);
    if (!Number.isInteger(idx) || idx < 0 || idx >= pal.length) return base;
    base.fillColor = pal[idx].hex;
    base.fillOpacity = Number(pal[idx].fillAlpha) || 0.6;
    return base;
  }

  function notifyAfterManagedColorChange() {
    renderManagedObjects();
    if (typeof markFixedHistoryCursor === "function") {
      try { markFixedHistoryCursor(); } catch (_) { /* noop */ }
    }
    if (typeof notifyLiveShareChange === "function") notifyLiveShareChange();
    if (typeof scheduleSessionDraftSave === "function") scheduleSessionDraftSave();
  }

  window.__rkrkApplyManagedGridCellFill = function (objectId, n, e, value) {
    const mObj = managedObjects.find((o) => o.id === objectId);
    if (!mObj || mObj.type !== "grid") return;
    if (typeof rememberFixedHistory === "function") {
      try { rememberFixedHistory(); } catch (_) { /* noop */ }
    }
    if (!mObj.cellFills || typeof mObj.cellFills !== "object") mObj.cellFills = {};
    const key = `${n},${e}`;
    if (value === "neutral") delete mObj.cellFills[key];
    else {
      const idx = Number(value);
      if (Number.isInteger(idx) && idx >= 0) mObj.cellFills[key] = idx;
    }
    notifyAfterManagedColorChange();
    if (typeof eastLabel === "function" && Number.isInteger(n) && Number.isInteger(e)) {
      managedStatusText(`Kachel ${eastLabel(e)}${n + 1}: Farbe gesetzt.`);
    }
  };

  window.__rkrkApplyManagedPolygonFill = function (objectId, value) {
    const mObj = managedObjects.find((o) => o.id === objectId);
    if (!mObj || mObj.type !== "polygon") return;
    if (typeof rememberFixedHistory === "function") {
      try { rememberFixedHistory(); } catch (_) { /* noop */ }
    }
    if (value === "neutral") {
      mObj.polygonStyle = applyPaletteFillToPolygonStyle(mObj.polygonStyle, "neutral");
      mObj._fillCycleIdx = undefined;
    } else {
      const idx = Number(value);
      mObj.polygonStyle = applyPaletteFillToPolygonStyle(mObj.polygonStyle, idx);
      mObj._fillCycleIdx = idx;
    }
    notifyAfterManagedColorChange();
    managedStatusText("Polygon-Füllfarbe gesetzt.");
  };

  window.__rkrkApplyManagedFreehandColor = function (objectId, value) {
    const mObj = managedObjects.find((o) => o.id === objectId);
    if (!mObj || (mObj.type !== "freehand" && mObj.type !== "pfeil")) return;
    if (typeof rememberFixedHistory === "function") {
      try { rememberFixedHistory(); } catch (_) { /* noop */ }
    }
    const pal = getGridCellFillPaletteForKml();
    if (value === "neutral") {
      mObj.lineColor = "#e11d48";
      mObj._fillCycleIdx = undefined;
    } else {
      const idx = Number(value);
      if (Number.isInteger(idx) && idx >= 0 && idx < pal.length) {
        mObj.lineColor = pal[idx].hex;
        mObj._fillCycleIdx = idx;
      }
    }
    notifyAfterManagedColorChange();
    managedStatusText("Freihand-Linienfarbe gesetzt.");
  };

  window.__rkrkApplyManagedPointColor = function (objectId, labelIndex, value) {
    const mObj = managedObjects.find((o) => o.id === objectId);
    if (!mObj || (mObj.type !== "points" && mObj.type !== "textfields")) return;
    if (!Array.isArray(mObj.labels) || !mObj.labels.length) return;
    if (typeof rememberFixedHistory === "function") {
      try { rememberFixedHistory(); } catch (_) { /* noop */ }
    }
    const pal = getGridCellFillPaletteForKml();
    const applyOne = (lp) => {
      if (!lp || typeof lp !== "object") return;
      if (value === "neutral") {
        const defaults = (typeof labelPointDefaultsByType === "function")
          ? labelPointDefaultsByType(lp.type)
          : { color: "#ff8c00", textColor: "#111111" };
        lp.color = defaults.color || "#ff8c00";
        if (mObj.type === "textfields" || lp.text) {
          lp.textColor = defaults.textColor || defaults.color || "#111111";
        }
        return;
      }
      const idx = Number(value);
      if (!Number.isInteger(idx) || idx < 0 || idx >= pal.length) return;
      lp.color = pal[idx].hex;
      if (mObj.type === "textfields" || (lp.text && String(lp.text).trim())) {
        lp.textColor = pal[idx].hex;
      }
    };
    if (Number.isInteger(labelIndex) && labelIndex >= 0 && labelIndex < mObj.labels.length) {
      applyOne(mObj.labels[labelIndex]);
    } else {
      mObj.labels.forEach(applyOne);
    }
    notifyAfterManagedColorChange();
    managedStatusText("Punktfarbe gesetzt.");
  };

  function ensureCircleRadiusField() {
    let panel = document.getElementById("objectParamsCircle");
    const objectDetails = (typeof exportTypeEl !== "undefined" && exportTypeEl)
      ? exportTypeEl.closest("details")
      : null;
    const objectContent = objectDetails ? objectDetails.querySelector(".panel-details-content") : null;
    if (!panel) {
      panel = document.createElement("div");
      panel.id = "objectParamsCircle";
      panel.style.display = "none";
      panel.innerHTML = ""
        + "<p class='small'>Karte klicken, Radius setzen, oben «Kreis fixieren».</p>"
        + "<label for='objSingleRadius'>Kreisradius (m)</label>"
        + "<input id='objSingleRadius' type='number' min='1' step='1' value='100'/>"
        + "<input id='objSingleDiameter' type='hidden' value='200'/>"
        + "<label for='objCircleLabelText'>Bezeichnung</label>"
        + "<input id='objCircleLabelText' type='text' maxlength='80' value='Kreis'/>";
      const after = document.getElementById("objManagedStatus")
        || document.getElementById("objManagedToolsTop")
        || document.getElementById("objectParamsCircles")
        || (objectContent ? objectContent.querySelector("p.small") : null);
      if (after && after.parentNode) {
        after.insertAdjacentElement("afterend", panel);
      } else if (objectContent) {
        objectContent.appendChild(panel);
      } else {
        return null;
      }
    }
    let rad = document.getElementById("objSingleRadius");
    if (!rad) {
      const label = document.createElement("label");
      label.htmlFor = "objSingleRadius";
      label.textContent = "Kreisradius (m)";
      rad = document.createElement("input");
      rad.id = "objSingleRadius";
      rad.type = "number";
      rad.min = "1";
      rad.step = "1";
      rad.value = "100";
      panel.appendChild(label);
      panel.appendChild(rad);
    }
    // Always show radius input visibly when present (don't leave type=hidden by mistake)
    rad.type = "number";
    rad.style.display = "";
    if (!document.getElementById("objSingleDiameter")) {
      const hid = document.createElement("input");
      hid.id = "objSingleDiameter";
      hid.type = "hidden";
      hid.value = String(Math.max(2, Number(rad.value || 100) * 2));
      panel.appendChild(hid);
    }
    // Bind radius <-> radius1 if not yet
    const r1 = document.getElementById("radius1");
    if (rad && r1 && !rad.__objBound) {
      rad.__objBound = true;
      const sync = () => {
        const r = Math.max(1, Math.round(Number(r1.value || 100)));
        rad.value = String(r);
        const d = document.getElementById("objSingleDiameter");
        if (d) d.value = String(r * 2);
      };
      sync();
      rad.addEventListener("input", () => {
        const r = Math.max(1, Math.round(Number(rad.value || 100)));
        rad.value = String(r);
        r1.value = String(r);
        const d = document.getElementById("objSingleDiameter");
        if (d) d.value = String(r * 2);
        if (typeof drawPreview === "function" && typeof previewActive !== "undefined" && previewActive) drawPreview();
      });
      r1.addEventListener("input", sync);
    }
    return panel;
  }

  function ensureCirclesFixPanel() {
    let panel = document.getElementById("objectParamsCircles");
    const objectDetails = (typeof exportTypeEl !== "undefined" && exportTypeEl)
      ? exportTypeEl.closest("details") : null;
    const objectContent = objectDetails ? objectDetails.querySelector(".panel-details-content") : null;
    if (!panel && objectContent) {
      panel = document.createElement("div");
      panel.id = "objectParamsCircles";
      panel.style.display = "none";
      panel.innerHTML = ""
        + "<label for='objRadius1'>Kreisradius 1 (m)</label><input id='objRadius1' type='number' min='1' step='1' value='100'/>"
        + "<label for='objRadius2'>Kreisradius 2 (m)</label><input id='objRadius2' type='number' min='1' step='1' value='200'/>"
        + "<label for='objRadius3'>Kreisradius 3 (m)</label><input id='objRadius3' type='number' min='1' step='1' value='300'/>"
        + "<input id='objDiameter1' type='hidden' value='200'/>"
        + "<input id='objDiameter2' type='hidden' value='400'/>"
        + "<input id='objDiameter3' type='hidden' value='600'/>"
        + "<p class='small' id='objCircleSelectedInfo'>Selektiert: Radius 1</p>";
      const after = document.getElementById("objectParamsCircle") || document.getElementById("objManagedStatus");
      if (after && after.parentNode) after.insertAdjacentElement("afterend", panel);
      else objectContent.appendChild(panel);
    }
    return panel;
  }

  function ensureTopFixBar() {
    ensureCompactObjectActionBar();
  }

  function injectObjectParamsIntoObjectBox() {
    const objectDetails = document.getElementById("objectPanelDetails")
      || ((typeof exportTypeEl !== "undefined" && exportTypeEl) ? exportTypeEl.closest("details") : null);
    const objectContent = objectDetails ? objectDetails.querySelector(".panel-details-content") : null;
    if (!objectContent) {
      managedStatusText("Objekt-Fenster nicht gefunden — Parameter können nicht geladen werden.");
      return;
    }
    const paramsComplete = !!(
      document.getElementById("gridRotationAngle")
      && document.getElementById("objectParamsGrid")
      && document.getElementById("objectParamsCircle")
      && document.getElementById("objAddManagedBtn")
    );
    if (paramsComplete) {
      ensureTopFixBar();
      ensureCircleRadiusField();
      ensureCirclesFixPanel();
    }
    if (!paramsComplete) {
      // Keep index.html action buttons; move them out before tearing down wrappers
      ["drawBtn", "clearBtn", "exportBtn"].forEach((id) => {
        const el = document.getElementById(id);
        if (el && objectContent.contains(el)) objectContent.appendChild(el);
      });
      // Remove stale partial param nodes inside Objekt panel only
      ["objManagedToolsTop", "objManagedStatus", "objectParamsCircle", "objectParamsCircles",
       "objectParamsGrid", "objectParamsPolygon", "objectParamsFreehand", "objectParamsLabels",
       "gridParamHint", "objectActionHost", "objInjectedParamsRoot"].forEach((id) => {
        const el = document.getElementById(id);
        if (el && objectContent.contains(el)) el.remove();
      });
      // Also remove orphan title nodes we inject
      Array.from(objectContent.querySelectorAll("p.small")).forEach((p) => {
        if (String(p.textContent || "").indexOf("Objekt-Parameter") >= 0) p.remove();
      });
    const box = document.createElement("div");
    box.id = "objInjectedParamsRoot";
    box.innerHTML = ""
      + "<div id='objManagedToolsTop' class='obj-action-bar'></div>"
      + "<p class='small obj-status-idle' id='objManagedStatus'></p>"
      + "<p class='small' style='font-weight:700;margin-top:6px;'>Objekt-Parameter</p>"
      + "<div id='objectParamsCircle' style='display:none;'>"
      + "  <p class='small'>«Zeichnen» starten, Karte klicken, Radius setzen, oben «Kreis fixieren» (oder Doppelklick).</p>"
      + "  <label for='objSingleRadius'><strong>Kreisradius (m)</strong></label>"
      + "  <input id='objSingleRadius' type='number' min='1' step='1' value='100' style='display:block;width:100%;max-width:220px;'/>"
      + "  <input id='objSingleDiameter' type='hidden' value='200'/>"
      + "  <label for='objCircleLabelText'><strong>Bezeichnung</strong></label>"
      + "  <input id='objCircleLabelText' type='text' maxlength='80' value='Kreis' style='display:block;width:100%;max-width:220px;'/>"
      + "</div>"
      + "<div id='objectParamsCircles' style='display:none;'>"
      + "  <p class='small'>«Zeichnen» starten, Karte klicken, Radien setzen, oben «Kreise fixieren».</p>"
      + "  <label for='objRadius1'>Kreisradius 1 (m)</label><input id='objRadius1' type='number' min='1' step='1' value='100'/>"
      + "  <label for='objRadius2'>Kreisradius 2 (m)</label><input id='objRadius2' type='number' min='1' step='1' value='200'/>"
      + "  <label for='objRadius3'>Kreisradius 3 (m)</label><input id='objRadius3' type='number' min='1' step='1' value='300'/>"
      + "  <input id='objDiameter1' type='hidden' value='200'/>"
      + "  <input id='objDiameter2' type='hidden' value='400'/>"
      + "  <input id='objDiameter3' type='hidden' value='600'/>"
      + "  <p class='small' id='objCircleSelectedInfo'>Selektiert: Radius 1</p>"
      + "</div>"
      + "<div id='objectParamsGrid' style='display:none;'>"
      + "  <p class='small'>«Zeichnen» starten, Karte klicken (SW-Ecke), dann oben «Raster fixieren» (oder Doppelklick).</p>"
      + "  <label for='objGridSize'>Rasterabstand (m)</label><input id='objGridSize' type='number' min='1' step='1' value='333'/>"
      + "  <label for='objNorthCount'>Felder Nord</label><input id='objNorthCount' type='number' min='1' max='200' step='1' value='8'/>"
      + "  <label for='objEastCount'>Felder Ost</label><input id='objEastCount' type='number' min='1' max='26' step='1' value='8'/>"
      + "  <label for='gridRotationAngle'>Rasterrotation (Grad)</label><input id='gridRotationAngle' type='number' min='0' max='359' step='1' value='0'/>"
      + "  <input id='gridRotationWheel' type='range' min='0' max='359' step='1' value='0'/>"
      + "  <p class='small' id='gridRotationLiveLabel' style='font-weight:700;'>Rotation: 0°</p>"
      + "  <button id='gridRulerBtn' type='button' style='display:none;'>Lineal</button>"
      + "  <p class='small' id='gridRulerStatus'>Lineal aus.</p>"
      + "</div>"
      + "<div id='objectParamsPolygon' style='display:none;'>"
      + "  <p class='small'>Zeichnen starten, mind. 3 Punkte setzen, oben «Polygon fixieren» (oder Doppelklick).</p>"
      + "  <button id='objStartPolyBtn' type='button' title='Zeichnen starten'>Zeichnen</button>"
      + "  <button id='objFinishPolyBtn' type='button' style='display:none'>Polygon abschliessen</button>"
      + "  <button id='objClearPolyBtn' type='button' style='display:none' title='Polygon löschen'>Löschen</button>"
      + "  <p class='small' id='objPolygonStatusMirror'>Noch nicht gestartet.</p>"
      + "</div>"
      + "<div id='objectParamsFreehand' style='display:none;'>"
      + "  <p class='small'>Offene Punktreihe: halten &amp; ziehen oder klicken; Punkte verschieben. Oben «Freihandlinie fixieren» (oder Doppelklick).</p>"
      + "  <label for='objFreehandWidth'>Linienstärke (px)</label><input id='objFreehandWidth' type='number' min='1' max='20' step='1' value='3'/>"
      + "  <label for='objFreehandColor'>Linienfarbe</label><input id='objFreehandColor' type='color' value='#e11d48'/>"
      + "  <button id='objClearFreehandBtn' type='button' style='display:none;' title='Linie löschen'>Löschen</button>"
      + "  <p class='small' id='objFreehandStatus'>Zuerst «Zeichnen», dann halten &amp; ziehen oder klicken.</p>"
      + "</div>"
      + "<div id='objectParamsPfeil' style='display:none;'>"
      + "  <p class='small'>Zwei Klicks: Start, dann Ende. Endpunkte verschieben. Oben «Pfeil fixieren».</p>"
      + "  <label for='objPfeilWidth'>Dicke (px)</label><input id='objPfeilWidth' type='number' min='1' max='20' step='1' value='3'/>"
      + "  <label for='objPfeilColor'>Farbe</label><input id='objPfeilColor' type='color' value='#e11d48'/>"
      + "  <label for='objPfeilEnd'>Enden</label>"
      + "  <select id='objPfeilEnd'>"
      + "    <option value='arrow' selected>Einseitig (Pfeil am Ende)</option>"
      + "    <option value='arrows'>Beidseitig</option>"
      + "    <option value='dots'>Punkte an beiden Enden</option>"
      + "  </select>"
      + "  <button id='objClearPfeilBtn' type='button' style='display:none;' title='Pfeil löschen'>Löschen</button>"
      + "  <p class='small' id='objPfeilStatus'>Zuerst «Zeichnen», dann Start- und Endpunkt auf die Karte setzen.</p>"
      + "</div>"
      + "<div id='objectParamsLabels' style='display:none;'>"
      + "  <label for='objLabelFontSizeLeft'>Textgrösse (px)</label><input id='objLabelFontSizeLeft' type='number' min='8' max='72' step='1' value='12'/>"
      + "  <p class='small' style='font-weight:700;margin:4px 0 6px;'>Beschriftete Punkte</p>"
      + "  <div id='objEmbeddedLabeledPoints'></div>"
      + "</div>"
      + "<p class='small' id='gridParamHint'></p>";
    objectContent.appendChild(box);
    } // end !paramsComplete DOM create
    ensureTopFixBar();
    ensureCircleRadiusField();
    ensureCirclesFixPanel();
    if (!document.getElementById("objAddManagedBtn")) ensureTopFixBar();
    // Alle Objekt-Aktionen in eine kompakte Top-Bar ziehen
    (function normalizeActionButtonDom() {
      ensureCompactObjectActionBar();
    })();
    const embeddedLabelHost = document.getElementById("objEmbeddedLabeledPoints");
    const labelTypeEl = document.getElementById("labelPointType");
    if (embeddedLabelHost && labelTypeEl) {
      const oldLabelDetails = labelTypeEl.closest("details");
      const oldLabelContent = labelTypeEl.closest(".panel-details-content");
      if (oldLabelContent) {
        while (oldLabelContent.firstChild) {
          embeddedLabelHost.appendChild(oldLabelContent.firstChild);
        }
      }
      if (oldLabelDetails) {
        oldLabelDetails.style.display = "none";
      }
    }
    // Export-Buttons gehören in die Sektion «Export & Teilen», nicht ins Objekt-Panel
    try {
      ensureSharePanelSection();
      moveExportButtonsToShareHost();
    } catch (_) {
      const exportBtn = document.getElementById("exportBtn");
      const shareHost = document.getElementById("shareActionHost");
      if (exportBtn && shareHost && exportBtn.parentNode !== shareHost) {
        shareHost.appendChild(exportBtn);
      }
    }
    const resetDraftAfterFix = (typeHint) => {
      const mode = normalizeObjectType(typeHint || (exportTypeEl ? exportTypeEl.value : "grid"));
      drawingArmed = false;
      if (typeof deactivateGridRuler === "function") deactivateGridRuler();
      clearGridRotationOverlay();
      if (typeof clearPreview === "function") {
        clearPreview();
      } else if (typeof previewSource !== "undefined" && previewSource && typeof previewSource.clear === "function") {
        previewSource.clear();
      }
      if (mode === "polygon") {
        polygonVertices = [];
        if (typeof isPolygonDrawing !== "undefined") isPolygonDrawing = false;
        if (typeof selectedVertexIndex !== "undefined") selectedVertexIndex = -1;
        if (typeof renderPolygonEditor === "function") renderPolygonEditor();
      } else if (mode === "points" || mode === "textfields") {
        labeledPoints = [];
        if (typeof selectedLabelPointIndex !== "undefined") selectedLabelPointIndex = -1;
        if (typeof renderLabeledPoints === "function") renderLabeledPoints();
      } else if (mode === "freehand" || mode === "pfeil") {
        freehandDraftCoords = [];
        freehandDrawing = false;
        const st = document.getElementById("objFreehandStatus");
        if (st) st.textContent = "Keine Linie.";
      } else {
        if (mode === "grid") clearCellFillsUi();
        selectedLonLat = null;
        if (typeof renderCoord === "function") renderCoord();
        if (typeof setMarker === "function") {
          try { setMarker(null); } catch (_) { /* noop */ }
        }
      }
    };
    const finalizeCurrentObjectAsFixed = () => {
      if (selectedManagedObjectId) {
        const idxSel = managedObjects.findIndex((o) => o.id === selectedManagedObjectId);
        const selObj = idxSel >= 0 ? managedObjects[idxSel] : null;
        // Fix-Auswahl (bereits fixiert): Draft als neues Objekt speichern, Auswahl nicht überschreiben
        if (selObj && selObj.fixed) {
          selectedManagedObjectId = null;
          managedEditPaused = false;
          editBaselineSnapshot = null;
        } else if (idxSel >= 0 && selObj && !selObj.fixed) {
          rememberFixedHistory();
          const payload = currentObjectPayloadFromUi();
          if (payload && (payload.type === "points" || payload.type === "textfields")
              && Array.isArray(payload.labels) && payload.labels.length) {
            const keep = payload.labels[0];
            const extras = payload.labels.slice(1);
            const keepLabel = buildManagedLabelFromPoint(keep);
            const objType = labelObjectTypeForPoint(keepLabel, payload.type);
            managedObjects[idxSel] = {
              ...managedObjects[idxSel],
              type: objType,
              labels: [keepLabel],
              fixed: true,
              id: managedObjects[idxSel].id,
              name: managedObjects[idxSel].name || displayNameForLabelObject(objType, keepLabel, managedObjects[idxSel].id)
            };
            touchManagedObjectSyncMeta(managedObjects[idxSel]);
            if (extras.length) createManagedObjectsFromLabels(extras, payload.type, { fixed: true });
          } else if (payload) {
            managedObjects[idxSel] = {
              ...managedObjects[idxSel],
              ...payload,
              fixed: true,
              id: managedObjects[idxSel].id,
              name: managedObjects[idxSel].name
            };
            if (managedObjects[idxSel].type === "circle") {
              delete managedObjects[idxSel].r1;
              delete managedObjects[idxSel].r2;
              delete managedObjects[idxSel].r3;
              delete managedObjects[idxSel].circlesStyles;
            }
            touchManagedObjectSyncMeta(managedObjects[idxSel]);
          } else {
            managedObjects[idxSel].fixed = true;
            touchManagedObjectSyncMeta(managedObjects[idxSel]);
          }
          splitMultiLabelManagedObjects();
          const finalizedType = managedObjects[idxSel] ? managedObjects[idxSel].type : (payload && payload.type);
          selectedManagedObjectId = null;
          managedEditPaused = false;
          editBaselineSnapshot = null;
          if (finalizedType === "freehand" || finalizedType === "pfeil") freehandDraftCoords = [];
          if (finalizedType === "grid") clearCellFillsUi();
          resetDraftAfterFix(finalizedType);
          ensureManagedObjectLayer();
          renderManagedObjects();
          markFixedHistoryCursor();
          try {
            if (managedObjects[idxSel]) touchManagedObjectSyncMeta(managedObjects[idxSel]);
          } catch (_) { /* noop */ }
          const doneObj = managedObjects[idxSel];
          managedStatusText(doneObj
            ? `«${managedObjectDisplayName(doneObj)}» fixiert. Bearbeitung abgeschlossen — neues Objekt möglich.`
            : "Objekt fixiert.");
          try { if (typeof notifyLiveShareChange === "function") notifyLiveShareChange(); } catch (_) { /* noop */ }
          try { if (typeof updateStyleBoxVisibility === "function") updateStyleBoxVisibility(); } catch (_) { /* noop */ }
          try { if (typeof refreshManagedObjectList === "function") refreshManagedObjectList(); } catch (_) { /* noop */ }
          return true;
        }
      }
      const payload = currentObjectPayloadFromUi();
      if (!payload) {
        const miss = normalizeObjectType(exportTypeEl ? exportTypeEl.value : "grid");
        managedStatusText(
          miss === "grid" ? "Raster: zuerst «Zeichnen», dann SW-Ecke auf die Karte klicken, dann fixieren."
            : (miss === "circle" ? "Einzelkreis: zuerst «Zeichnen», dann auf die Karte klicken, dann «Kreis fixieren»."
              : (miss === "circles" ? "Kreise: zuerst «Zeichnen», dann auf die Karte klicken, dann «Kreise fixieren»."
                : "Objekt kann nicht fixiert werden (fehlender Basispunkt)."))
        );
        return false;
      }
      if (payload.type === "polygon" && (!payload.polygonVertices || payload.polygonVertices.length < 3)) {
        managedStatusText("Polygon benötigt mindestens 3 Punkte.");
        return false;
      }
      if ((payload.type === "points" || payload.type === "textfields") && (!payload.labels || !payload.labels.length)) {
        managedStatusText("Mindestens einen Punkt/Text setzen vor dem Fixieren.");
        return false;
      }
      rememberFixedHistory();
      // Punkte/Textfelder immer als Einzelobjekte fixieren
      if (payload.type === "points" || payload.type === "textfields") {
        const created = createManagedObjectsFromLabels(payload.labels, payload.type, { fixed: true });
        selectedManagedObjectId = null;
        managedEditPaused = false;
        editBaselineSnapshot = null;
        resetDraftAfterFix(payload.type);
        ensureManagedObjectLayer();
        renderManagedObjects();
        markFixedHistoryCursor();
        const n = created.length;
        managedStatusText(n === 1
          ? `«${managedObjectDisplayName(created[0])}» fixiert. Neues Objekt kann erstellt werden.`
          : `${n} Punkte/Textfelder als Einzelobjekte fixiert.`);
        try { if (typeof notifyLiveShareChange === "function") notifyLiveShareChange(); } catch (_) { /* noop */ }
        try { if (typeof updateStyleBoxVisibility === "function") updateStyleBoxVisibility(); } catch (_) { /* noop */ }
          try { if (typeof refreshManagedObjectList === "function") refreshManagedObjectList(); } catch (_) { /* noop */ }
          return true;
      }
      const newId = managedObjectIdSeq++;
      const obj = {
        id: newId,
        fixed: true,
        ...payload,
        name: defaultManagedObjectName({ type: payload.type, id: newId })
      };
      // Guard: Einzelkreis must never carry multi-circle radii
      if (obj.type === "circle") {
        delete obj.r1; delete obj.r2; delete obj.r3; delete obj.circlesStyles;
      }
      touchManagedObjectSyncMeta(obj);
      managedObjects.push(obj);
      selectedManagedObjectId = null;
      managedEditPaused = false;
      editBaselineSnapshot = null;
      if (payload.type === "freehand" || payload.type === "pfeil") freehandDraftCoords = [];
      if (payload.type === "grid") clearCellFillsUi();
      resetDraftAfterFix(payload.type);
      ensureManagedObjectLayer();
      renderManagedObjects();
      markFixedHistoryCursor();
      managedStatusText(`«${managedObjectDisplayName(obj)}» fixiert. Neues Objekt kann erstellt werden.`);
      try { if (typeof notifyLiveShareChange === "function") notifyLiveShareChange(); } catch (_) { /* noop */ }
      try { if (typeof updateStyleBoxVisibility === "function") updateStyleBoxVisibility(); } catch (_) { /* noop */ }
          try { if (typeof refreshManagedObjectList === "function") refreshManagedObjectList(); } catch (_) { /* noop */ }
          return true;
    };
    const addManagedBtn = document.getElementById("objAddManagedBtn");
    if (addManagedBtn && !addManagedBtn.__objFixBound) {
      addManagedBtn.__objFixBound = true;
      addManagedBtn.addEventListener("click", () => {
        finalizeCurrentObjectAsFixed();
      });
    }
    [
      ["objFixGridBtn", "grid"],
      ["objFixCircleBtn", "circle"],
      ["objFixCirclesBtn", "circles"],
      ["objFixFreehandBtn", "freehand"]
    ].forEach(([btnId, type]) => {
      const btn = document.getElementById(btnId);
      if (!btn || btn.__objFixBound) return;
      btn.__objFixBound = true;
      btn.addEventListener("click", () => {
        if (exportTypeEl && normalizeObjectType(exportTypeEl.value) !== type) {
          exportTypeEl.value = type;
          exportTypeEl.dispatchEvent(new Event("change"));
        }
        const ok = finalizeCurrentObjectAsFixed();
        if (!ok) {
          if (type === "grid") managedStatusText("Raster: zuerst «Zeichnen», dann SW-Ecke klicken, dann fixieren.");
          if (type === "circle") managedStatusText("Einzelkreis: zuerst «Zeichnen», dann Karte klicken, dann fixieren.");
          if (type === "circles") managedStatusText("Kreise: zuerst «Zeichnen», dann Karte klicken, dann fixieren.");
        } else {
          managedStatusText(type === "circle" ? "Kreis fixiert."
            : (type === "circles" ? "Kreise fixiert." : "Objekt fixiert."));
        }
      });
    });
    finalizeCurrentObjectAsFixedRef = finalizeCurrentObjectAsFixed;
    window.__objFinalizeInstalled = true;
    discardCurrentObjectChanges = function () {
      const mode = normalizeObjectType(exportTypeEl ? exportTypeEl.value : "grid");
      drawingArmed = false;
      if (selectedManagedObjectId && editBaselineSnapshot) {
        const idx = managedObjects.findIndex((o) => o.id === selectedManagedObjectId);
        if (idx >= 0) {
          managedObjects[idx] = JSON.parse(JSON.stringify(editBaselineSnapshot));
          managedObjects[idx].fixed = true;
        }
        selectedManagedObjectId = null;
        managedEditPaused = false;
        editBaselineSnapshot = null;
        freehandDraftCoords = [];
        freehandDrawing = false;
        resetDraftAfterFix(managedObjects[idx] ? managedObjects[idx].type : mode);
        renderManagedObjects();
        managedStatusText("Änderungen verworfen — letzter fixierter Stand wiederhergestellt.");
        return;
      }
      // Draft never fixed: cancel cleanly
      selectedManagedObjectId = null;
      managedEditPaused = false;
      editBaselineSnapshot = null;
      freehandDraftCoords = [];
      freehandDrawing = false;
      resetDraftAfterFix(mode);
      renderManagedObjects();
      managedStatusText("Entwurf verworfen.");
      try { if (typeof updateStyleBoxVisibility === "function") updateStyleBoxVisibility(); } catch (_) { /* noop */ }
      try { if (typeof refreshObjectActionButtons === "function") refreshObjectActionButtons(); } catch (_) { /* noop */ }
    };
    const discardBtn = document.getElementById("objDiscardBtn");
    if (discardBtn && !discardBtn.__objDiscardBound) {
      discardBtn.__objDiscardBound = true;
      discardBtn.addEventListener("click", () => discardCurrentObjectChanges());
    }
    const clearBtnEl = document.getElementById("clearBtn");
    if (clearBtnEl && !clearBtnEl.__objFixedDeleteBound) {
      clearBtnEl.__objFixedDeleteBound = true;
      clearBtnEl.addEventListener("click", (evt) => {
        const obj = selectedManagedObjectId
          ? managedObjects.find((o) => o.id === selectedManagedObjectId)
          : null;
        if (!obj) return;
        if (evt && typeof evt.preventDefault === "function") evt.preventDefault();
        if (evt && typeof evt.stopImmediatePropagation === "function") evt.stopImmediatePropagation();
        if (!window.confirm(`«${managedObjectDisplayName(obj)}» löschen?`)) return;
        deleteManagedObjectById(obj.id);
      }, true);
    }
    const undoBtnEl = document.getElementById("objEditUndoBtn");
    if (undoBtnEl && !undoBtnEl.__objUndoBound) {
      undoBtnEl.__objUndoBound = true;
      undoBtnEl.addEventListener("click", () => undoFixedStep());
    }
    const redoBtnEl = document.getElementById("objEditRedoBtn");
    if (redoBtnEl && !redoBtnEl.__objRedoBound) {
      redoBtnEl.__objRedoBound = true;
      redoBtnEl.addEventListener("click", () => redoFixedStep());
    }
    const clearFh = document.getElementById("objClearFreehandBtn");
    if (clearFh && !clearFh.__bound) {
      clearFh.__bound = true;
      clearFh.addEventListener("click", () => {
        freehandDraftCoords = [];
        freehandDrawing = false;
        if (typeof clearPreview === "function") clearPreview();
        const st = document.getElementById("objFreehandStatus");
        if (st) st.textContent = "Keine Linie.";
        refreshObjectActionButtons();
      });
    }
    const clearPf = document.getElementById("objClearPfeilBtn");
    if (clearPf && !clearPf.__bound) {
      clearPf.__bound = true;
      clearPf.addEventListener("click", () => {
        freehandDraftCoords = [];
        freehandDrawing = false;
        if (typeof clearPreview === "function") clearPreview();
        const st = document.getElementById("objPfeilStatus");
        if (st) st.textContent = "Zuerst «Zeichnen», dann Start- und Endpunkt auf die Karte setzen.";
        refreshObjectActionButtons();
      });
    }
    ["objPfeilColor", "objPfeilWidth", "objPfeilEnd", "stylePfeilColor", "stylePfeilWidth", "stylePfeilEnd"].forEach((id) => {
      const el = document.getElementById(id);
      if (!el || el.__pfeilBound) return;
      el.__pfeilBound = true;
      const refresh = () => {
        try { renderFreehandDraftPreview(); } catch (_) { /* noop */ }
        try { if (selectedManagedObjectId) syncEditingObjectFromUi(); } catch (_) { /* noop */ }
        try { renderManagedObjects(); } catch (_) { /* noop */ }
      };
      el.addEventListener("input", refresh);
      el.addEventListener("change", refresh);
    });
    const bind = (srcId, dstId, fn) => {
      const src = document.getElementById(srcId);
      const dst = document.getElementById(dstId);
      if (!src || !dst) return;
      src.value = String(fn(Number(dst.value || src.value)));
      src.addEventListener("input", () => {
        const v = fn(Number(src.value));
        src.value = String(v);
        dst.value = String(v);
        if (typeof drawPreview === "function" && typeof previewActive !== "undefined" && previewActive) drawPreview();
      });
    };
    const singleR = document.getElementById("objSingleRadius");
    const singleD = document.getElementById("objSingleDiameter");
    const r1Single = document.getElementById("radius1");
    if (singleR && r1Single && !singleR.__objBound) {
      singleR.__objBound = true;
      const syncFromRadius1 = () => {
        const r = Math.max(1, Math.round(Number(r1Single.value || 100)));
        singleR.value = String(r);
        if (singleD) singleD.value = String(r * 2);
      };
      syncFromRadius1();
      singleR.addEventListener("input", () => {
        const r = Math.max(1, Math.round(Number(singleR.value || 100)));
        singleR.value = String(r);
        r1Single.value = String(r);
        if (singleD) singleD.value = String(r * 2);
        if (typeof drawPreview === "function" && typeof previewActive !== "undefined" && previewActive) drawPreview();
      });
      r1Single.addEventListener("input", syncFromRadius1);
    }
    const bindRadiusPair = (objId, hiddenDiamId, radiusId, infoIdx) => {
      const src = document.getElementById(objId);
      const dst = document.getElementById(radiusId);
      const diam = document.getElementById(hiddenDiamId);
      if (!src || !dst) return;
      const sync = () => {
        const r = Math.max(1, Math.round(Number(dst.value || src.value || 100)));
        src.value = String(r);
        dst.value = String(r);
        if (diam) diam.value = String(r * 2);
      };
      sync();
      src.addEventListener("input", () => {
        const r = Math.max(1, Math.round(Number(src.value || 100)));
        src.value = String(r);
        dst.value = String(r);
        if (diam) diam.value = String(r * 2);
        setCircleSelectedInfo(infoIdx);
        if (typeof drawPreview === "function" && typeof previewActive !== "undefined" && previewActive) drawPreview();
      });
      dst.addEventListener("input", sync);
      ["focus", "click"].forEach((ev) => src.addEventListener(ev, () => setCircleSelectedInfo(infoIdx)));
    };
    const circleInfoEl = document.getElementById("objCircleSelectedInfo");
    const setCircleSelectedInfo = (idx) => {
      if (!circleInfoEl) return;
      const map = {
        1: "Selektiert: Radius 1",
        2: "Selektiert: Radius 2",
        3: "Selektiert: Radius 3"
      };
      circleInfoEl.textContent = map[idx] || "Selektiert: Radius 1";
    };
    bindRadiusPair("objRadius1", "objDiameter1", "radius1", 1);
    bindRadiusPair("objRadius2", "objDiameter2", "radius2", 2);
    bindRadiusPair("objRadius3", "objDiameter3", "radius3", 3);
    setCircleSelectedInfo(1);
    bind("objGridSize", "spacing", (v) => Math.max(1, Math.round(v)));
    bind("objNorthCount", "northCount", (v) => Math.max(1, Math.min(200, Math.round(v))));
    bind("objEastCount", "eastCount", (v) => Math.max(1, Math.min(26, Math.round(v))));
    const objStart = document.getElementById("objStartPolyBtn");
    const objFinish = document.getElementById("objFinishPolyBtn");
    const objClear = document.getElementById("objClearPolyBtn");
    const mirror = document.getElementById("objPolygonStatusMirror");
    const setPolyMirror = (msg) => {
      if (mirror) mirror.textContent = msg;
      if (typeof polygonStatusEl !== "undefined" && polygonStatusEl) polygonStatusEl.textContent = msg;
    };
    if (objStart && !objStart.__objPolyBound) {
      objStart.__objPolyBound = true;
      objStart.addEventListener("click", () => {
        if (exportTypeEl && normalizeObjectType(exportTypeEl.value) !== "polygon") {
          exportTypeEl.value = "polygon";
          exportTypeEl.dispatchEvent(new Event("change"));
        }
        const drawing = (typeof isPolygonDrawing !== "undefined") && !!isPolygonDrawing;
        if (drawing) {
          if (typeof isPolygonDrawing !== "undefined") isPolygonDrawing = false;
          if (typeof selectedVertexIndex !== "undefined") selectedVertexIndex = -1;
          if (typeof renderPolygonEditor === "function") renderPolygonEditor();
          setPolyMirror("Zeichnen gestoppt — Punkte bleiben; oben fixieren oder löschen.");
          managedStatusText("Polygonzeichnen gestoppt.");
          refreshObjectActionButtons();
          return;
        }
        if (typeof isPolygonDrawing !== "undefined") isPolygonDrawing = true;
        if (typeof selectedVertexIndex !== "undefined") selectedVertexIndex = -1;
        if (typeof startPolyBtnEl !== "undefined" && startPolyBtnEl) {
          try { startPolyBtnEl.click(); } catch (_) { /* noop */ }
        }
        if (typeof renderPolygonEditor === "function") renderPolygonEditor();
        setPolyMirror("Zeichnen aktiv: mind. 3 Punkte, dann oben «Polygon fixieren» oder Doppelklick.");
        managedStatusText("Polygonzeichnen gestartet.");
        refreshObjectActionButtons();
      });
    }
    if (objFinish && !objFinish.__objPolyBound) {
      objFinish.__objPolyBound = true;
      objFinish.addEventListener("click", () => {
        if (exportTypeEl && normalizeObjectType(exportTypeEl.value) !== "polygon") {
          exportTypeEl.value = "polygon";
          exportTypeEl.dispatchEvent(new Event("change"));
        }
        if (typeof isPolygonDrawing !== "undefined") isPolygonDrawing = false;
        if (typeof selectedVertexIndex !== "undefined") selectedVertexIndex = -1;
        const verts = (typeof polygonVertices !== "undefined" && Array.isArray(polygonVertices)) ? polygonVertices : [];
        if (verts.length < 3) {
          setPolyMirror(`Zu wenig Punkte (${verts.length}/3). Weiter klicken, dann fixieren.`);
          managedStatusText("Polygon benötigt mindestens 3 Punkte.");
          if (typeof renderPolygonEditor === "function") renderPolygonEditor();
          return;
        }
        if (typeof renderPolygonEditor === "function") renderPolygonEditor();
        // Fixieren BEVOR Draft geleert wird
        const fixed = finalizeCurrentObjectAsFixed();
        if (fixed) {
          setPolyMirror("Polygon fixiert.");
          managedStatusText("Polygon fixiert.");
        } else {
          setPolyMirror("Fixieren fehlgeschlagen — Punkte prüfen und erneut versuchen.");
        }
      });
    }
    if (objClear && !objClear.__objPolyBound) {
      objClear.__objPolyBound = true;
      objClear.addEventListener("click", () => {
        if (typeof polygonVertices !== "undefined") polygonVertices = [];
        if (typeof isPolygonDrawing !== "undefined") isPolygonDrawing = false;
        if (typeof selectedVertexIndex !== "undefined") selectedVertexIndex = -1;
        if (typeof clearPolyBtnEl !== "undefined" && clearPolyBtnEl) {
          try { clearPolyBtnEl.click(); } catch (_) { /* noop */ }
        }
        if (typeof renderPolygonEditor === "function") renderPolygonEditor();
        if (typeof clearPreview === "function") clearPreview();
        setPolyMirror("Polygon gelöscht.");
        managedStatusText("Polygon gelöscht.");
        refreshObjectActionButtons();
      });
    }
    const labelFontSizeEl = document.getElementById("objLabelFontSize");
    const labelFontSizeLeftEl = document.getElementById("objLabelFontSizeLeft");
    const labelTypeElForSync = document.getElementById("labelPointType");
    const pointColorElForSync = document.getElementById("objPointColor");
    const textColorElForSync = document.getElementById("objLabelTextColor");
    const syncStyleSettingsFromLeft = () => {
      if (!labelTypeElForSync) return;
      const type = String(labelTypeElForSync.value || "other");
      if (typeof labelPointDefaultsByType !== "function") return;
      const defaults = labelPointDefaultsByType(type) || {};
      if (pointColorElForSync && !isTextOnlyLabelType(type) && typeof defaults.color === "string" && defaults.color) {
        pointColorElForSync.value = defaults.color;
      }
      const resolvedTextColor = String(defaults.textColor || defaults.color || "");
      if (textColorElForSync && resolvedTextColor) {
        textColorElForSync.value = resolvedTextColor;
      }
    };
    const syncLabelFontSizeControls = (nextValue) => {
      const n = Math.max(8, Math.min(72, Math.round(Number(nextValue) || 12)));
      if (labelFontSizeEl) labelFontSizeEl.value = String(n);
      if (labelFontSizeLeftEl) labelFontSizeLeftEl.value = String(n);
      return n;
    };
    syncLabelFontSizeControls(getObjectLabelFontSize());
    if (labelFontSizeEl) {
      labelFontSizeEl.addEventListener("input", () => {
        syncLabelFontSizeControls(labelFontSizeEl.value);
        if (typeof renderLabeledPoints === "function") renderLabeledPoints();
        renderManagedObjects();
      });
    }
    if (labelFontSizeLeftEl) {
      labelFontSizeLeftEl.addEventListener("input", () => {
        syncLabelFontSizeControls(labelFontSizeLeftEl.value);
        if (typeof renderLabeledPoints === "function") renderLabeledPoints();
        renderManagedObjects();
      });
    }
    if (labelTypeElForSync && !labelTypeElForSync.__objTypeApplyBound) {
      labelTypeElForSync.__objTypeApplyBound = true;
      labelTypeElForSync.addEventListener("change", () => {
        syncStyleSettingsFromLeft();
        try { window.applyLabelPointTypeFromUi({ onlySelected: true }); } catch (_) { /* noop */ }
      });
      syncStyleSettingsFromLeft();
    }
    const pointColorEl = document.getElementById("objPointColor");
    if (pointColorEl) {
      pointColorEl.addEventListener("input", () => {
        const typeEl = document.getElementById("labelPointType");
        const typeNow = String((typeEl && typeEl.value) || "other");
        const textColorFollow = document.getElementById("objLabelTextColor");
        if (textColorFollow && !isTextOnlyLabelType(typeNow)) {
          textColorFollow.value = pointColorEl.value;
          try { if (typeof updateSwatches === "function") updateSwatches(); } catch (_) { /* noop */ }
        }
        if (typeof renderLabeledPoints === "function") renderLabeledPoints();
        renderManagedObjects();
      });
    }
    const labelTextColorEl = document.getElementById("objLabelTextColor");
    if (labelTextColorEl) {
      labelTextColorEl.addEventListener("input", () => {
        if (typeof renderLabeledPoints === "function") renderLabeledPoints();
        renderManagedObjects();
      });
    }
    const updateVisibility = () => {
      ensureCompactObjectActionBar();
      const mode = normalizeObjectType(exportTypeEl ? exportTypeEl.value : "grid");
      const isGrid = mode === "grid";
      const isPolygon = mode === "polygon";
      const isPoints = mode === "points";
      const isTextfields = mode === "textfields";
      const isCircle = mode === "circle";
      const isCircles = mode === "circles";
      const isFreehand = mode === "freehand";
      const isPfeil = mode === "pfeil";
      const c1 = document.getElementById("objectParamsCircle");
      const c = document.getElementById("objectParamsCircles");
      const g = document.getElementById("objectParamsGrid");
      const p = document.getElementById("objectParamsPolygon");
      const fh = document.getElementById("objectParamsFreehand");
      const pf = document.getElementById("objectParamsPfeil");
      const lbl = document.getElementById("objectParamsLabels");
      const h = document.getElementById("gridParamHint");
      const toolsTop = document.getElementById("objManagedToolsTop");
      const fixBtn = document.getElementById("objAddManagedBtn");
      const discardBtn = document.getElementById("objDiscardBtn");
      const drawBtn = document.getElementById("drawBtn");
      const clearBtn = document.getElementById("clearBtn");
      if (isCircle) ensureCircleRadiusField();
      if (isCircles) ensureCirclesFixPanel();
      const c1b = document.getElementById("objectParamsCircle");
      if (c1b) c1b.style.display = isCircle ? "" : "none";
      if (c1) c1.style.display = isCircle ? "" : "none";
      const c3 = document.getElementById("objectParamsCircles");
      if (c3) c3.style.display = isCircles ? "" : "none";
      if (c) c.style.display = isCircles ? "" : "none";
      // Typ-spezifische Fix-Buttons entfernt — nur noch der obere Fixieren-Button
      ["objFixCircleBtn", "objFixCirclesBtn", "objFixGridBtn", "objFixFreehandBtn"].forEach((id) => {
        const el = document.getElementById(id);
        if (el) el.style.display = "none";
      });
      const finishPoly = document.getElementById("objFinishPolyBtn");
      if (finishPoly) finishPoly.style.display = "none";
      // Radius-Feld bei Einzelkreis immer sichtbar machen
      const radShow = document.getElementById("objSingleRadius");
      if (radShow && isCircle) {
        radShow.type = "number";
        radShow.style.display = "";
        const lab = document.querySelector("label[for='objSingleRadius']");
        if (lab) lab.style.display = "";
      }
      const circlesSettingsEl = document.getElementById("circlesSettings");
      if (circlesSettingsEl) {
        circlesSettingsEl.style.display = isCircles ? "block" : "none";
      }
      if (g) g.style.display = isGrid ? "" : "none";
      if (p) p.style.display = isPolygon ? "" : "none";
      if (fh) fh.style.display = isFreehand ? "" : "none";
      if (pf) pf.style.display = isPfeil ? "" : "none";
      if (lbl) lbl.style.display = (isPoints || isTextfields) ? "" : "none";
      const showFix = canFixCurrentObject();
      const showDiscard = canDiscardCurrentChanges();
      const hasMapPreview = (typeof previewActive !== "undefined" && !!previewActive)
        || (Array.isArray(selectedLonLat) && selectedLonLat.length === 2);
      const polyVerts = (typeof polygonVertices !== "undefined" && Array.isArray(polygonVertices)) ? polygonVertices : [];
      const polyDrawing = (typeof isPolygonDrawing !== "undefined") && !!isPolygonDrawing;
      const hasFreehand = Array.isArray(freehandDraftCoords) && freehandDraftCoords.length > 0;
      const hasPfeil = isPfeil && hasFreehand;
      if (fixBtn) {
        fixBtn.style.display = showFix ? "" : "none";
        const fixTitle = isGrid ? "Raster fixieren"
          : (isPolygon ? "Polygon fixieren"
            : (isCircle ? "Kreis fixieren"
              : (isCircles ? "Kreise fixieren"
                : (isFreehand ? "Freihandlinie fixieren"
                  : (isPfeil ? "Pfeil fixieren"
                    : (isPoints ? "Punkte fixieren"
                      : (isTextfields ? "Text fixieren" : "Objekt fixieren")))))));
        setObjToolButton(fixBtn, "fix", fixTitle);
      }
      if (discardBtn) {
        discardBtn.style.display = showDiscard ? "" : "none";
        const discardTitle = (selectedManagedObjectId && editBaselineSnapshot)
          ? "Änderungen verwerfen"
          : "Entwurf verwerfen";
        setObjToolButton(discardBtn, "discard", discardTitle);
      }
      const undoBtn = document.getElementById("objEditUndoBtn");
      const redoBtn = document.getElementById("objEditRedoBtn");
      if (undoBtn) {
        const canUndo = fixedUndoStack.length > 0;
        undoBtn.style.display = canUndo ? "" : "none";
        undoBtn.disabled = !canUndo;
        undoBtn.textContent = "←";
        undoBtn.title = "Rückgängig (fixierte Objekte, Ctrl+Z)";
        undoBtn.classList.add("obj-icon-btn");
      }
      if (redoBtn) {
        const canRedo = fixedRedoStack.length > 0;
        redoBtn.style.display = canRedo ? "" : "none";
        redoBtn.disabled = !canRedo;
        redoBtn.textContent = "↪";
        redoBtn.title = "Wiederholen (fixierte Objekte, Ctrl+Y)";
        redoBtn.classList.add("obj-icon-btn");
      }
      // Polygon: Start/Stop + Löschen in derselben Top-Bar
      const polyStart = document.getElementById("objStartPolyBtn");
      const polyClear = document.getElementById("objClearPolyBtn");
      const polyToggle = document.getElementById("objPolyActionToggle");
      if (polyStart) {
        polyStart.style.display = isPolygon ? "" : "none";
        setObjToolButton(polyStart, polyDrawing ? "stop" : "draw", polyDrawing ? "Zeichnen stoppen" : "Zeichnen");
        polyStart.classList.toggle("selected-flight-reference", polyDrawing);
      }
      if (polyClear) {
        polyClear.style.display = "none";
        polyClear.textContent = "Löschen";
        polyClear.title = "Polygon löschen";
      }
      if (polyToggle) polyToggle.style.display = "none";
      // Freihand: Löschen nur bei vorhandener Linie
      const clearFhBtn = document.getElementById("objClearFreehandBtn");
      if (clearFhBtn) {
        clearFhBtn.style.display = "none";
        clearFhBtn.textContent = "Löschen";
        clearFhBtn.title = "Linie löschen";
      }
      // Raster-Lineal: nur wenn Raster-Vorschau gesetzt
      const rulerBtn = document.getElementById("gridRulerBtn");
      if (rulerBtn) {
        const hasGridPreview = isGrid && Array.isArray(selectedLonLat) && selectedLonLat.length === 2;
        rulerBtn.style.display = hasGridPreview ? "" : "none";
        rulerBtn.textContent = gridRulerActive ? "Lineal aus" : "Lineal";
        rulerBtn.title = gridRulerActive ? "Lineal deaktivieren" : "Rotation mit Lineal setzen";
        rulerBtn.classList.toggle("selected-flight-reference", !!gridRulerActive);
      }
      if (drawBtn) {
        const showDraw = !isPolygon;
        const armed = !!drawingArmed;
        drawBtn.style.display = showDraw ? "" : "none";
        if (isPoints) {
          setObjToolButton(drawBtn, "point", "Punkt auf Karte setzen");
          drawBtn.classList.remove("selected-flight-reference");
        } else if (isTextfields) {
          setObjToolButton(drawBtn, "text", "Text auf Karte setzen");
          drawBtn.classList.remove("selected-flight-reference");
        } else if (isFreehand || isPfeil) {
          const armTitle = armed
            ? "Zeichnen stoppen (Karte navigieren)"
            : (isPfeil ? "Pfeil zeichnen (Start, dann Ende)" : "Freihand zeichnen (halten oder klicken)");
          setObjToolButton(drawBtn, armed ? "stop" : (isPfeil ? "pfeil" : "freehand"), armTitle);
          drawBtn.classList.toggle("selected-flight-reference", armed);
        } else if (isGrid || isCircle || isCircles) {
          setObjToolButton(drawBtn, armed ? "stop" : "draw", armed ? "Zeichnen stoppen" : "Zeichnen");
          drawBtn.classList.toggle("selected-flight-reference", armed);
        } else {
          setObjToolButton(drawBtn, "draw", "Zeichnen");
          drawBtn.classList.remove("selected-flight-reference");
        }
      }
      if (clearBtn) {
        // Angewähltes Objekt löschen (fixiert oder in Bearbeitung) — kein reiner Entwurf ohne Objekt
        const selectedObj = selectedManagedObjectId
          ? managedObjects.find((o) => o.id === selectedManagedObjectId)
          : null;
        const showClear = !!selectedObj;
        clearBtn.style.display = showClear ? "" : "none";
        const clearTitle = showClear
          ? `«${managedObjectDisplayName(selectedObj)}» löschen`
          : "Zuerst Objekt anwählen oder bearbeiten";
        setObjToolButton(clearBtn, "clear", clearTitle);
      }
      const showBar = !!(toolsTop && (
        (drawBtn && drawBtn.style.display !== "none")
        || (clearBtn && clearBtn.style.display !== "none")
        || (polyStart && polyStart.style.display !== "none")
        || (polyClear && polyClear.style.display !== "none")
        || (clearFhBtn && clearFhBtn.style.display !== "none")
        || showFix || showDiscard
        || (undoBtn && undoBtn.style.display !== "none")
        || (redoBtn && redoBtn.style.display !== "none")
      ));
      if (toolsTop) {
        toolsTop.classList.add("obj-action-bar");
        toolsTop.style.display = showBar ? "flex" : "none";
      }
      const labelTypeEl = document.getElementById("labelPointType");
      if (labelTypeEl) {
        Array.from(labelTypeEl.options).forEach((opt) => {
          const textOnly = isTextOnlyLabelType(opt.value);
          opt.hidden = isTextfields ? !textOnly : textOnly;
        });
        if (isTextfields) {
          if (!isTextOnlyLabelType(labelTypeEl.value)) {
            labelTypeEl.value = "text_black";
            labelTypeEl.dispatchEvent(new Event("change"));
          }
        } else if (isPoints && isTextOnlyLabelType(labelTypeEl.value)) {
          labelTypeEl.value = "other";
          labelTypeEl.dispatchEvent(new Event("change"));
        }
      }
      if (h) {
        // Short / often empty — long tips live in button titles
        h.textContent = "";
        h.style.display = "none";
      }
      try { updateStyleBoxVisibility(); } catch (_) { /* noop */ }
    };
    refreshObjectActionButtons = updateVisibility;
    try { window.refreshObjectActionButtons = updateVisibility; } catch (_) { /* noop */ }
    if (typeof exportTypeEl !== "undefined" && exportTypeEl) {
      exportTypeEl.addEventListener("change", updateVisibility);
      updateVisibility();
    }
    // Nach Kartenaktionen Buttons aktualisieren (ohne globale Funktionen zu überschreiben)
    if (typeof map !== "undefined" && map && !map.__objBtnRefreshHook) {
      map.__objBtnRefreshHook = true;
      const bump = () => { try { refreshObjectActionButtons(); } catch (_) { /* noop */ } };
      map.on("singleclick", () => setTimeout(bump, 0));
      map.on("dblclick", () => setTimeout(bump, 0));
      map.on("pointerup", () => setTimeout(bump, 0));
    }
    const drawBtnEl = document.getElementById("drawBtn");
    if (drawBtnEl && !drawBtnEl.__objPointsOverrideInstalled) {
      drawBtnEl.__objPointsOverrideInstalled = true;
      drawBtnEl.addEventListener("click", (evt) => {
        const mode = normalizeObjectType(exportTypeEl ? exportTypeEl.value : "grid");
        if (mode !== "points" && mode !== "textfields") return;
        if (evt && typeof evt.preventDefault === "function") evt.preventDefault();
        if (evt && typeof evt.stopImmediatePropagation === "function") evt.stopImmediatePropagation();
        if (typeof hasPendingPointDraft === "function" && hasPendingPointDraft()) {
          managedStatusText(mode === "textfields"
            ? "Zuerst Text «Fixieren», dann neuen setzen."
            : "Zuerst Punkt «Fixieren», dann neuen setzen.");
          return;
        }
        const addBtnEl = document.getElementById("addLabelPointBtn");
        const labelTypeEl = document.getElementById("labelPointType");
        const labelStatusEl = document.getElementById("labelPointStatus");
        if (addBtnEl) {
          if (labelTypeEl) {
            labelTypeEl.value = (mode === "textfields") ? "text_black" : (labelTypeEl.value || "other");
            labelTypeEl.dispatchEvent(new Event("change"));
          }
          addBtnEl.click();
          if (labelStatusEl) {
            labelStatusEl.textContent = mode === "textfields"
              ? "Textmodus aktiv: nächster Kartenklick setzt das Textfeld."
              : "Punktmodus aktiv: nächster Kartenklick setzt den Punkt.";
          }
          managedStatusText(mode === "textfields"
            ? "Textmodus aktiv: nächster Klick setzt Text."
            : "Punktmodus aktiv: nächster Klick setzt einen Punkt mit Text.");
        } else {
          managedStatusText("Punktmodus nicht verfügbar.");
        }
      }, true);
    }
  }

  function extendLabelPointTypes() {
    const labelTypeEl = document.getElementById("labelPointType");
    if (!labelTypeEl) return;
    const pointColorOptions = [
      ["start", "Grün (Startpunkt)"],
      ["parking", "Blau (Parkplatz)"],
      ["other", "Orange"],
      ["red", "Rot"],
      ["yellow", "Gelb"],
      ["purple", "Violett"],
      ["cyan", "Cyan"],
      ["magenta", "Magenta"],
      ["pink", "Pink"],
      ["brown", "Braun"],
      ["black", "Schwarz"],
      ["gray", "Grau"],
      ["white", "Weiss"]
    ];
    const textColorOptions = [
      ["text_black", "Text Schwarz"],
      ["text_red", "Text Rot"],
      ["text_orange", "Text Orange"],
      ["text_yellow", "Text Gelb"],
      ["text_green", "Text Grün"],
      ["text_blue", "Text Blau"],
      ["text_white", "Text Weiss"]
    ];
    const keepValue = String(labelTypeEl.value || "start");
    const existingTextOpts = Array.from(labelTypeEl.options || []).filter((o) => isTextOnlyLabelType(o.value));
    labelTypeEl.innerHTML = "";
    pointColorOptions.forEach(([value, label]) => {
      const o = document.createElement("option");
      o.value = value;
      o.textContent = label;
      labelTypeEl.appendChild(o);
    });
    textColorOptions.forEach(([value, label]) => {
      if (existingTextOpts.some((o) => o.value === value)) return;
      const o = document.createElement("option");
      o.value = value;
      o.textContent = label;
      labelTypeEl.appendChild(o);
    });
    existingTextOpts.forEach((opt) => {
      if (!labelTypeEl.querySelector(`option[value="${opt.value}"]`)) {
        labelTypeEl.appendChild(opt);
      }
    });
    if (labelTypeEl.querySelector(`option[value="${keepValue}"]`)) {
      labelTypeEl.value = keepValue;
    }
    const origDefaults = (typeof window.labelPointDefaultsByType === "function")
      ? window.labelPointDefaultsByType
      : ((typeof labelPointDefaultsByType === "function") ? labelPointDefaultsByType : null);
    if (typeof origDefaults === "function" && !window.__objLabelDefaultsExtended) {
      window.__objLabelDefaultsExtended = true;
      const wrapped = function (type) {
        const t = String(type || "other");
        if (t === "text" || t === "text_black") return { defaultText: "Text", color: "#000000", textColor: "#000000", pointRadius: 0 };
        if (t === "text_red") return { defaultText: "Text", color: "#ff2d2d", textColor: "#ff2d2d", pointRadius: 0 };
        if (t === "text_orange") return { defaultText: "Text", color: "#ff8a00", textColor: "#ff8a00", pointRadius: 0 };
        if (t === "text_yellow") return { defaultText: "Text", color: "#ffd400", textColor: "#ffd400", pointRadius: 0 };
        if (t === "text_green") return { defaultText: "Text", color: "#00aa00", textColor: "#00aa00", pointRadius: 0 };
        if (t === "text_blue") return { defaultText: "Text", color: "#0066ff", textColor: "#0066ff", pointRadius: 0 };
        if (t === "text_white") return { defaultText: "Text", color: "#ffffff", textColor: "#ffffff", pointRadius: 0 };
        const base = origDefaults(t) || {};
        const color = base.color || "#ff8a00";
        return {
          defaultText: base.defaultText || "",
          color,
          textColor: base.textColor || color,
          pointRadius: Number.isFinite(Number(base.pointRadius)) ? Number(base.pointRadius) : 9
        };
      };
      try { window.labelPointDefaultsByType = wrapped; } catch (_) { /* noop */ }
      try { labelPointDefaultsByType = wrapped; } catch (_) { /* noop */ }
    }
    if (typeof appendLabeledPointsKml === "function" && !window.__objLabelKmlExtended) {
      window.__objLabelKmlExtended = true;
      appendLabeledPointsKml = function (elevationMap = new Map()) {
        const typeKeys = pointColorOptions.map((p) => p[0]).concat(textColorOptions.map((p) => p[0]));
        const typeDefs = typeKeys.map((type) => [type, labelPointDefaultsByType(type)]);
        let kml = "";
        typeDefs.forEach(([type, defaults]) => {
          const color = defaults.color || "#111111";
          const textColor = defaults.textColor || color;
          const hideIcon = Number(defaults.pointRadius) <= 0;
          const styleId = `label-point-style-${String(type)}`;
          const labelScale = (getObjectLabelFontSize() / 12).toFixed(2);
          kml += `
    <Style id="${styleId}">
      <IconStyle>
        <color>${hexToKmlColor(color, "ff")}</color>
        <scale>${hideIcon ? "0" : "1.1"}</scale>
        ${hideIcon ? "" : `<Icon><href>https://maps.google.com/mapfiles/kml/shapes/placemark_circle.png</href></Icon>`}
      </IconStyle>
      <LabelStyle><color>${hexToKmlColor(textColor, "ff")}</color><scale>${labelScale}</scale></LabelStyle>
    </Style>`;
        });
        labeledPoints.forEach((point) => {
          const defaults = labelPointDefaultsByType(point.type);
          const label = point.text || defaults.defaultText;
          const terrain = getElevationAt(elevationMap, point.lon, point.lat) || 0;
          const styleId = `label-point-style-${String(point.type || "start")}`;
          kml += `
    <Placemark>
      <name>${escapeXml(label)}</name>
      <styleUrl>#${styleId}</styleUrl>
      ${elevationDataKml(terrain, 0)}
      <Point><coordinates>${point.lon},${point.lat},${terrain.toFixed(2)}</coordinates></Point>
    </Placemark>`;
        });
        return kml;
      };
    }
    const syncLabelPointTypeSwatch = () => {
      let sw = document.getElementById("labelPointTypeSwatch");
      if (!sw) {
        sw = document.createElement("span");
        sw.id = "labelPointTypeSwatch";
        sw.setAttribute("aria-hidden", "true");
        sw.style.cssText = "display:inline-block;width:18px;height:18px;border-radius:50%;border:1px solid rgba(0,0,0,.25);vertical-align:middle;margin:2px 0 8px 2px;box-shadow:0 0 0 1px #fff inset;";
        labelTypeEl.insertAdjacentElement("afterend", sw);
      }
      const type = String(labelTypeEl.value || "other");
      const defaults = (typeof labelPointDefaultsByType === "function")
        ? labelPointDefaultsByType(type)
        : { color: "#ff8a00" };
      sw.style.background = String(defaults.color || "#ff8a00");
      sw.style.display = isTextOnlyLabelType(type) ? "none" : "inline-block";
    };
    if (!labelTypeEl.__objStyleSyncInstalled) {
      labelTypeEl.__objStyleSyncInstalled = true;
      const syncStyleControlsToLabelType = () => {
        const pointColorEl = document.getElementById("objPointColor");
        const textColorEl = document.getElementById("objLabelTextColor");
        const type = String(labelTypeEl.value || "other");
        const defaults = (typeof labelPointDefaultsByType === "function")
          ? labelPointDefaultsByType(type)
          : { color: "#ff8a00", textColor: "#111111" };
        if (pointColorEl && !isTextOnlyLabelType(type) && typeof defaults.color === "string" && defaults.color) {
          pointColorEl.value = defaults.color;
        }
        const resolvedTextColor = String(defaults.textColor || defaults.color || "");
        if (textColorEl && resolvedTextColor) {
          textColorEl.value = resolvedTextColor;
        }
        syncLabelPointTypeSwatch();
      };
      labelTypeEl.addEventListener("change", syncStyleControlsToLabelType);
      syncStyleControlsToLabelType();
    } else {
      syncLabelPointTypeSwatch();
    }
  }
  let gridRotationOverlaySource = null;
  let gridRotationOverlayLayer = null;
  let gridRotationRedrawRaf = null;

  function ensureGridRotationOverlay() {
    if (gridRotationOverlaySource && gridRotationOverlayLayer) return;
    if (typeof ol === "undefined" || typeof map === "undefined" || !map) return;
    gridRotationOverlaySource = new ol.source.Vector();
    gridRotationOverlayLayer = new ol.layer.Vector({
      source: gridRotationOverlaySource,
      zIndex: 25
    });
    map.addLayer(gridRotationOverlayLayer);
  }

  function updateGridRotationLiveLabel(angleDeg) {
    const el = document.getElementById("gridRotationLiveLabel");
    if (!el) return;
    const a = Math.round(normalizeAngle(angleDeg));
    el.textContent = `Rotation: ${a}°`;
  }

  function renderGridRotationOverlay(angleDeg) {
    ensureGridRotationOverlay();
    if (!gridRotationOverlaySource) return;
    gridRotationOverlaySource.clear();
    if (!exportTypeEl || exportTypeEl.value !== "grid") return;
    if (!Array.isArray(selectedLonLat) || selectedLonLat.length !== 2) return;
    const angle = normalizeAngle(angleDeg);
    updateGridRotationLiveLabel(angle);
    const origin = lonLatToMapCoord(selectedLonLat[0], selectedLonLat[1]);
    const axisLen = Math.max(80, Number(sanitizeNumber(document.getElementById("spacing") ? document.getElementById("spacing").value : 333, 333)) * 1.2);
    const aRad = (typeof degToRad === "function" ? degToRad(angle) : angle * Math.PI / 180);
    // East axis of rotated grid
    const eastEnd = [
      Number(origin[0]) + Math.cos(aRad) * axisLen,
      Number(origin[1]) + Math.sin(aRad) * axisLen
    ];
    // North axis of rotated grid
    const northEnd = [
      Number(origin[0]) + Math.cos(aRad + Math.PI / 2) * axisLen,
      Number(origin[1]) + Math.sin(aRad + Math.PI / 2) * axisLen
    ];
    const eastLine = new ol.Feature({ geometry: new ol.geom.LineString([origin, eastEnd]) });
    eastLine.setStyle(new ol.style.Style({
      stroke: new ol.style.Stroke({ color: "#2563eb", width: 3, lineDash: [10, 6] })
    }));
    const northLine = new ol.Feature({ geometry: new ol.geom.LineString([origin, northEnd]) });
    northLine.setStyle(new ol.style.Style({
      stroke: new ol.style.Stroke({ color: "#16a34a", width: 3, lineDash: [10, 6] })
    }));
    const label = new ol.Feature({ geometry: new ol.geom.Point(origin) });
    label.setStyle(new ol.style.Style({
      text: new ol.style.Text({
        text: `${Math.round(angle)}°`,
        offsetY: -18,
        font: "bold 14px Arial",
        fill: new ol.style.Fill({ color: "#111827" }),
        stroke: new ol.style.Stroke({ color: "#ffffff", width: 4 }),
        backgroundFill: new ol.style.Fill({ color: "rgba(255,255,255,0.85)" }),
        padding: [3, 5, 2, 5]
      }),
      image: new ol.style.Circle({
        radius: 5,
        fill: new ol.style.Fill({ color: "#f59e0b" }),
        stroke: new ol.style.Stroke({ color: "#ffffff", width: 2 })
      })
    }));
    const eastLbl = new ol.Feature({ geometry: new ol.geom.Point(eastEnd) });
    eastLbl.setStyle(new ol.style.Style({
      text: new ol.style.Text({
        text: "E",
        font: "bold 12px Arial",
        fill: new ol.style.Fill({ color: "#2563eb" }),
        stroke: new ol.style.Stroke({ color: "#ffffff", width: 3 })
      })
    }));
    const northLbl = new ol.Feature({ geometry: new ol.geom.Point(northEnd) });
    northLbl.setStyle(new ol.style.Style({
      text: new ol.style.Text({
        text: "N",
        font: "bold 12px Arial",
        fill: new ol.style.Fill({ color: "#16a34a" }),
        stroke: new ol.style.Stroke({ color: "#ffffff", width: 3 })
      })
    }));
    gridRotationOverlaySource.addFeature(eastLine);
    gridRotationOverlaySource.addFeature(northLine);
    gridRotationOverlaySource.addFeature(label);
    gridRotationOverlaySource.addFeature(eastLbl);
    gridRotationOverlaySource.addFeature(northLbl);
  }

  function clearGridRotationOverlay() {
    if (gridRotationOverlaySource) gridRotationOverlaySource.clear();
    updateGridRotationLiveLabel(0);
  }

  function redrawGridPreviewFromRotation(immediate) {
    const run = () => {
      gridRotationRedrawRaf = null;
      if (!exportTypeEl || exportTypeEl.value !== "grid") {
        clearGridRotationOverlay();
        return;
      }
      const angle = getGridRotationAngle();
      if (typeof previewActive !== "undefined") previewActive = true;
      if (typeof drawPreview === "function"
        && Array.isArray(selectedLonLat) && selectedLonLat.length === 2) {
        drawPreview();
      }
      renderGridRotationOverlay(angle);
      if (typeof updateRulerLineVisual === "function") updateRulerLineVisual(angle);
      if (selectedManagedObjectId) {
        const obj = managedObjects.find((o) => o.id === selectedManagedObjectId);
        if (obj && obj.type === "grid" && !obj.fixed) {
          obj.rotation = angle;
        }
      }
    };
    if (immediate) {
      if (gridRotationRedrawRaf) {
        cancelAnimationFrame(gridRotationRedrawRaf);
        gridRotationRedrawRaf = null;
      }
      run();
      return;
    }
    if (gridRotationRedrawRaf) return;
    gridRotationRedrawRaf = requestAnimationFrame(run);
  }

  function patchRotatedGridHitTests() {
    if (typeof isMapCoordinateInsideGridRaster === "function" && !isMapCoordinateInsideGridRaster.__objRotatedPatched) {
      isMapCoordinateInsideGridRaster = function (mapCoord) {
        if (exportTypeEl.value !== "grid" || !previewActive || !selectedLonLat || !mapCoord) return false;
        const lon = selectedLonLat[0];
        const lat = selectedLonLat[1];
        const spacing = sanitizeNumber(document.getElementById("spacing").value, 333);
        const northCount = Math.min(200, Math.max(1, Math.round(sanitizeNumber(document.getElementById("northCount").value, 8))));
        const eastCount = Math.min(26, Math.max(1, Math.round(sanitizeNumber(document.getElementById("eastCount").value, 8))));
        const rot = getGridRotationAngle();
        const x = mapCoord[0];
        const y = mapCoord[1];
        for (let n = 0; n < northCount; n += 1) {
          for (let e = 0; e < eastCount; e += 1) {
            const sw = rotatedOffsetLatLon(lat, lon, n * spacing, e * spacing, rot);
            const se = rotatedOffsetLatLon(lat, lon, n * spacing, (e + 1) * spacing, rot);
            const ne = rotatedOffsetLatLon(lat, lon, (n + 1) * spacing, (e + 1) * spacing, rot);
            const nw = rotatedOffsetLatLon(lat, lon, (n + 1) * spacing, e * spacing, rot);
            const ring = [sw, se, ne, nw, sw].map((p) => lonLatToMapCoord(p[1], p[0]));
            if (typeof isMapXYInsideClosedRing === "function" && isMapXYInsideClosedRing(x, y, ring)) return true;
            try {
              if (new ol.geom.Polygon([ring]).intersectsCoordinate(mapCoord)) return true;
            } catch (_) { /* noop */ }
          }
        }
        return false;
      };
      isMapCoordinateInsideGridRaster.__objRotatedPatched = true;
    }
    if (typeof isMapCoordinateNearAnyGridCellBorder === "function" && !isMapCoordinateNearAnyGridCellBorder.__objRotatedPatched) {
      const originalNear = isMapCoordinateNearAnyGridCellBorder;
      isMapCoordinateNearAnyGridCellBorder = function (mapCoord) {
        if (exportTypeEl.value !== "grid" || !previewActive || !selectedLonLat || !mapCoord) return false;
        const lon = selectedLonLat[0];
        const lat = selectedLonLat[1];
        const spacing = sanitizeNumber(document.getElementById("spacing").value, 333);
        const northCount = Math.min(200, Math.max(1, Math.round(sanitizeNumber(document.getElementById("northCount").value, 8))));
        const eastCount = Math.min(26, Math.max(1, Math.round(sanitizeNumber(document.getElementById("eastCount").value, 8))));
        const rot = getGridRotationAngle();
        const threshold = (typeof GRID_EDGE_GRAB_PX === "number") ? GRID_EDGE_GRAB_PX : 14;
        for (let n = 0; n < northCount; n += 1) {
          for (let e = 0; e < eastCount; e += 1) {
            const sw = rotatedOffsetLatLon(lat, lon, n * spacing, e * spacing, rot);
            const se = rotatedOffsetLatLon(lat, lon, n * spacing, (e + 1) * spacing, rot);
            const ne = rotatedOffsetLatLon(lat, lon, (n + 1) * spacing, (e + 1) * spacing, rot);
            const nw = rotatedOffsetLatLon(lat, lon, (n + 1) * spacing, e * spacing, rot);
            const ring = [sw, se, ne, nw, sw].map((p) => lonLatToMapCoord(p[1], p[0]));
            for (let i = 0; i < ring.length - 1; i += 1) {
              const a = ring[i];
              const b = ring[i + 1];
              const dx = b[0] - a[0];
              const dy = b[1] - a[1];
              const len2 = dx * dx + dy * dy;
              if (len2 < 1e-6) continue;
              let t = ((mapCoord[0] - a[0]) * dx + (mapCoord[1] - a[1]) * dy) / len2;
              t = Math.max(0, Math.min(1, t));
              const px = a[0] + t * dx;
              const py = a[1] + t * dy;
              const dist = Math.hypot(mapCoord[0] - px, mapCoord[1] - py);
              if (dist <= threshold) return true;
            }
          }
        }
        return false;
      };
      isMapCoordinateNearAnyGridCellBorder.__objRotatedPatched = true;
    }
  }


  function patchSingleCirclePreviewLabel() {
    if (typeof drawSingleCirclePreview !== "function" || drawSingleCirclePreview.__objLabelPatched) return;
    const orig = drawSingleCirclePreview;
    drawSingleCirclePreview = function (lat, lon) {
      const ret = orig.apply(this, arguments);
      try {
        // Replace last text feature label if previewSource available
        if (typeof previewSource !== "undefined" && previewSource && typeof previewSource.getFeatures === "function") {
          const feats = previewSource.getFeatures();
          const label = (typeof getCircleLabelTextFromUi === "function") ? getCircleLabelTextFromUi() : "Kreis";
          const colorEl = document.getElementById("c1LabelColor");
          const fontEl = document.getElementById("c1FontSize");
          const labelColor = colorEl ? String(colorEl.value || "#0000ff") : "#0000ff";
          const fontSize = fontEl ? Math.max(8, Number(fontEl.value) || 12) : 12;
          for (let i = feats.length - 1; i >= 0; i--) {
            const f = feats[i];
            const st = f.getStyle && f.getStyle();
            const txt = st && st.getText && st.getText();
            if (txt && typeof txt.setText === "function") {
              const cur = String(txt.getText && txt.getText() || "");
              if (cur === "Kreis" || cur === label || !cur) {
                txt.setText(label);
                if (txt.getFill) {
                  const fill = txt.getFill();
                  if (fill && fill.setColor) fill.setColor(labelColor);
                }
                if (typeof txt.setFont === "function") txt.setFont(fontSize + "px Arial");
                f.changed();
                break;
              }
            }
          }
        }
      } catch (_) { /* noop */ }
      return ret;
    };
    drawSingleCirclePreview.__objLabelPatched = true;
    try { window.drawSingleCirclePreview = drawSingleCirclePreview; } catch (_) {}
  }

  function patchDrawPreviewForRotationOverlay() {
    if (typeof drawPreview !== "function" || drawPreview.__objRotationOverlayPatched) return;
    const original = drawPreview;
    drawPreview = function () {
      const result = original.apply(this, arguments);
      if (exportTypeEl && exportTypeEl.value === "grid"
        && Array.isArray(selectedLonLat) && selectedLonLat.length === 2) {
        renderGridRotationOverlay(getGridRotationAngle());
      } else {
        clearGridRotationOverlay();
      }
      return result;
    };
    drawPreview.__objRotationOverlayPatched = true;
  }

  function installGridRotationControls() {
    const angleEl = document.getElementById("gridRotationAngle");
    const wheelEl = document.getElementById("gridRotationWheel");
    if (!angleEl || !wheelEl) return;
    if (!document.getElementById("gridRotationLiveLabel") && wheelEl.parentNode) {
      const live = document.createElement("p");
      live.className = "small";
      live.id = "gridRotationLiveLabel";
      live.style.fontWeight = "700";
      live.textContent = "Rotation: 0°";
      wheelEl.insertAdjacentElement("afterend", live);
    }
    if (window.__objGridRotationControlsInstalled) {
      updateGridRotationLiveLabel(getGridRotationAngle());
      return;
    }
    window.__objGridRotationControlsInstalled = true;
    const onAngleInput = () => {
      if (gridRotationSyncing) return;
      applyGridRotation(angleEl.value, { immediate: false });
    };
    const onWheelInput = () => {
      if (gridRotationSyncing) return;
      applyGridRotation(wheelEl.value, { immediate: false });
    };
    angleEl.addEventListener("input", onAngleInput);
    angleEl.addEventListener("change", onAngleInput);
    wheelEl.addEventListener("input", onWheelInput);
    wheelEl.addEventListener("change", onWheelInput);
    applyGridRotation(getGridRotationAngle(), { immediate: true, force: true });
  }

  function installRuler() {
    if (typeof map === "undefined" || !map || map.__objRulerInstalled) return;
    const btn = document.getElementById("gridRulerBtn");
    const statusEl = document.getElementById("gridRulerStatus");
    if (!btn || !statusEl) return;
    map.__objRulerInstalled = true;
    const source = new ol.source.Vector();
    const layer = new ol.layer.Vector({
      source,
      zIndex: 26,
      style: new ol.style.Style({
        stroke: new ol.style.Stroke({ color: "#00d4ff", width: 3, lineDash: [8, 6] }),
        image: new ol.style.Circle({
          radius: 7,
          fill: new ol.style.Fill({ color: "#00d4ff" }),
          stroke: new ol.style.Stroke({ color: "#ffffff", width: 2 })
        })
      })
    });
    map.addLayer(layer);
    const hasGrid = () => exportTypeEl && exportTypeEl.value === "grid" && Array.isArray(selectedLonLat) && selectedLonLat.length === 2;
    const selectedObj = () => selectedManagedObjectId ? managedObjects.find((o) => o.id === selectedManagedObjectId) : null;
    const canEditRotation = () => {
      if (!hasGrid()) return false;
      const obj = selectedObj();
      if (obj && obj.type === "grid" && obj.fixed) return false;
      return true;
    };
    const axisLenForAngle = () => Math.max(120, Number(sanitizeNumber(document.getElementById("spacing") ? document.getElementById("spacing").value : 333, 333)) * 1.5);
    updateRulerLineVisual = function (angleDeg) {
      source.clear();
      if (!hasGrid()) return;
      const angle = normalizeAngle(angleDeg === undefined ? getGridRotationAngle() : angleDeg);
      const origin = lonLatToMapCoord(selectedLonLat[0], selectedLonLat[1]);
      const len = axisLenForAngle();
      const aRad = (typeof degToRad === "function" ? degToRad(angle) : angle * Math.PI / 180);
      const tip = [
        Number(origin[0]) + Math.cos(aRad) * len,
        Number(origin[1]) + Math.sin(aRad) * len
      ];
      const line = new ol.Feature({ geometry: new ol.geom.LineString([origin, tip]) });
      line.set("gridRotationHandle", true);
      const tipPt = new ol.Feature({ geometry: new ol.geom.Point(tip) });
      tipPt.set("gridRotationHandle", true);
      tipPt.setStyle(new ol.style.Style({
        image: new ol.style.Circle({
          radius: 8,
          fill: new ol.style.Fill({ color: "#00d4ff" }),
          stroke: new ol.style.Stroke({ color: "#ffffff", width: 2 })
        }),
        text: new ol.style.Text({
          text: `${Math.round(angle)}°`,
          offsetY: -14,
          font: "bold 12px Arial",
          fill: new ol.style.Fill({ color: "#0ea5e9" }),
          stroke: new ol.style.Stroke({ color: "#ffffff", width: 3 })
        })
      }));
      source.addFeature(line);
      source.addFeature(tipPt);
    };
    const setActive = (active) => {
      gridRulerActive = !!active;
      btn.classList.toggle("selected-flight-reference", gridRulerActive);
      btn.textContent = gridRulerActive ? "Lineal aus" : "Rotation mit Lineal setzen";
      statusEl.textContent = gridRulerActive
        ? "Lineal aktiv: ziehen zum Drehen (Grad + Raster folgen)."
        : "Lineal: Griff auf der Karte ziehen oder Grad eingeben.";
      updateRulerLineVisual(getGridRotationAngle());
      try { refreshObjectActionButtons(); } catch (_) { /* noop */ }
    };
    deactivateGridRuler = () => {
      gridRulerActive = false;
      gridRotationDragActive = false;
      btn.classList.toggle("selected-flight-reference", false);
      btn.textContent = "Rotation mit Lineal setzen";
      if (statusEl) statusEl.textContent = "Lineal: Griff auf der Karte ziehen oder Grad eingeben.";
      updateRulerLineVisual(getGridRotationAngle());
      try { refreshObjectActionButtons(); } catch (_) { /* noop */ }
    };
    btn.addEventListener("click", () => {
      if (!exportTypeEl || exportTypeEl.value !== "grid") return;
      if (!Array.isArray(selectedLonLat) || selectedLonLat.length !== 2) {
        statusEl.textContent = "Zuerst Raster setzen oder Objekt zum Bearbeiten anwählen.";
        return;
      }
      const obj = selectedObj();
      if (obj && obj.type === "grid" && obj.fixed) {
        setActive(false);
        statusEl.textContent = "Raster ist fixiert. Mit Klick anwählen, dann drehen.";
        return;
      }
      if (typeof previewActive !== "undefined") previewActive = true;
      if (typeof drawPreview === "function") drawPreview();
      setActive(!gridRulerActive);
      applyGridRotation(getGridRotationAngle(), { immediate: true, force: true });
    });
    const angleFromMapCoord = (coordinate) => {
      const origin = lonLatToMapCoord(selectedLonLat[0], selectedLonLat[1]);
      const dx = Number(coordinate[0]) - Number(origin[0]);
      const dy = Number(coordinate[1]) - Number(origin[1]);
      const dist = Math.hypot(dx, dy);
      if (!Number.isFinite(dist) || dist < 5) return null;
      let angle = Math.atan2(dy, dx) * 180 / Math.PI;
      if (angle < 0) angle += 360;
      return angle;
    };
    const hitRotationHandle = (pixel) => {
      if (!map || !source) return false;
      let hit = false;
      map.forEachFeatureAtPixel(pixel, (feature) => {
        if (feature && feature.get("gridRotationHandle")) hit = true;
        return hit ? feature : null;
      }, {
        hitTolerance: 12,
        layerFilter: (ly) => ly === layer
      });
      return hit;
    };
    map.on("pointerdown", (evt) => {
      if (!canEditRotation()) return;
      if (!(gridRulerActive || hitRotationHandle(evt.pixel))) return;
      const ang = angleFromMapCoord(evt.coordinate);
      if (ang === null) return;
      gridRotationDragActive = true;
      gridRulerActive = true;
      btn.classList.toggle("selected-flight-reference", true);
      if (map.getTargetElement()) map.getTargetElement().style.cursor = "grabbing";
      applyGridRotation(ang, { immediate: true });
      if (evt && typeof evt.preventDefault === "function") evt.preventDefault();
    });
    map.on("pointermove", (evt) => {
      if (!gridRotationDragActive && !gridRulerActive) {
        if (canEditRotation() && hitRotationHandle(evt.pixel) && map.getTargetElement()) {
          map.getTargetElement().style.cursor = "grab";
        }
        return;
      }
      if (!canEditRotation()) return;
      if (!(gridRotationDragActive || gridRulerActive)) return;
      const ang = angleFromMapCoord(evt.coordinate);
      if (ang === null) return;
      applyGridRotation(ang, { immediate: false });
    });
    map.on("pointerup", () => {
      if (!gridRotationDragActive) return;
      gridRotationDragActive = false;
      if (map.getTargetElement()) map.getTargetElement().style.cursor = "";
      applyGridRotation(getGridRotationAngle(), { immediate: true, force: true });
      statusEl.textContent = "Lineal: Griff ziehen oder Grad eingeben — alle synchron.";
    });
    map.on("dblclick", (evt) => {
      if (!gridRulerActive && !gridRotationDragActive) return;
      if (evt && typeof evt.preventDefault === "function") evt.preventDefault();
      if (evt && typeof evt.stopPropagation === "function") evt.stopPropagation();
      deactivateGridRuler();
    });
    // Keep line visible whenever a grid preview exists.
    updateRulerLineVisual(getGridRotationAngle());
  }

  function installPolygonDoubleClickDelete() {
    if (typeof map === "undefined" || !map || map.__objPolyDeleteInstalled) return;
    map.__objPolyDeleteInstalled = true;
    map.on("dblclick", (evt) => {
      if (gridRulerActive) {
        return;
      }
      if (!exportTypeEl || exportTypeEl.value !== "polygon") {
        return;
      }
      if (pickManagedFeatureAtPixel(evt.pixel)) {
        return;
      }
      if (!Array.isArray(polygonVertices) || polygonVertices.length < 1 || !polygonSource) {
        return;
      }
      const pixel = evt.pixel;
      const vertexFeature = map.forEachFeatureAtPixel(pixel, (feature) => {
        const idx = feature.get("vertexIndex");
        return Number.isInteger(idx) ? feature : null;
      }, {
        hitTolerance: 10,
        layerFilter: (layer) => layer && typeof layer.getSource === "function" && layer.getSource() === polygonSource
      });
      if (!vertexFeature) {
        return;
      }
      const idx = vertexFeature.get("vertexIndex");
      if (!Number.isInteger(idx) || idx < 0 || idx >= polygonVertices.length) {
        return;
      }
      if (evt && typeof evt.preventDefault === "function") {
        evt.preventDefault();
      }
      polygonVertices.splice(idx, 1);
      if (typeof selectedVertexIndex !== "undefined") {
        selectedVertexIndex = -1;
      }
      if (typeof renderPolygonEditor === "function") {
        renderPolygonEditor();
      }
      if (typeof updatePolygonStatus === "function") {
        updatePolygonStatus(`Polygonpunkt ${idx + 1} gelöscht.`);
      }
    });
  }
  async function saveKmlOnServer(name, kml) {
    const res = await fetch(KML_SAVE_API, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ name, kml, on_rkrs: ON_RKRS })
    });
    const payload = await res.json().catch(() => ({}));
    if (!res.ok || !payload.ok || !payload.public_url) throw new Error((payload && payload.error) ? String(payload.error) : `HTTP ${res.status}`);
    return payload;
  }

  function buildAllManagedObjectsKml(elevationMap = new Map()) {
    const styles = (typeof getEditorStyles === "function") ? getEditorStyles() : null;
    let kml = kmlHeader("all_objects_export");
    managedObjects.forEach((obj, objIdx) => {
      if (!obj || isManagedObjectMapHidden(obj)) return;
      const gOp = getManagedObjectGroupOpacity(obj);
      const opacityToAlphaHex = (op) => {
        const v = Math.max(0, Math.min(1, (Number(op) || 0) * gOp));
        return Math.round(v * 255).toString(16).padStart(2, "0");
      };
      const hexToKmlColor = (hexColor, alpha) => {
        let a = alpha;
        if (a == null || a === "ff" || a === "FF") a = opacityToAlphaHex(1);
        else if (typeof a === "string" && /^[0-9a-fA-F]{2}$/.test(a) && gOp < 0.999) {
          a = opacityToAlphaHex(parseInt(a, 16) / 255);
        }
        return (typeof hexToKmlColorLocal === "function")
          ? hexToKmlColorLocal(hexColor, a)
          : String(hexColor || "");
      };
      const objTitle = managedObjectDisplayName(obj);
      const objTitleXml = (typeof escapeXml === "function") ? escapeXml(objTitle) : objTitle;
      if (obj.type === "circle" && obj.anchor) {
        const s = (obj.circleStyle && typeof obj.circleStyle === "object")
          ? obj.circleStyle
          : ((styles && Array.isArray(styles.circles) && styles.circles[0])
            ? styles.circles[0]
            : { lineColor: "#ff0000", fillColor: "#ff0000", fillOpacity: 0, labelColor: "#0000ff", fontSize: 12, lineWidth: 2 });
        const lat = Number(obj.anchor[1]);
        const lon = Number(obj.anchor[0]);
        const styleId = `managed-single-circle-style-${objIdx + 1}`;
        kml += `
    <Style id="${styleId}">
      <LabelStyle><color>${hexToKmlColor(s.labelColor || "#0000ff", "ff")}</color><scale>${((Number(s.fontSize) || 12) / 12).toFixed(2)}</scale></LabelStyle>
      <LineStyle><color>${hexToKmlColor(s.lineColor, "ff")}</color><width>${Number(s.lineWidth) || 2}</width></LineStyle>
      ${kmlPolyStyleXmlLocal(s.fillColor, opacityToAlphaHex(Number(s.fillOpacity) || 0), (Number(s.fillOpacity) || 0) > 0)}
    </Style>`;
        const radius = Math.max(1, Number(obj.r) || 1);
        const coords = createCircleCoordinates(lat, lon, radius, 96);
        kml += `
    <Placemark>
      <name>${objTitleXml}</name>
      <styleUrl>#${styleId}</styleUrl>
      <Polygon><outerBoundaryIs><LinearRing><coordinates>${polygonCoordinatesText(coords)}</coordinates></LinearRing></outerBoundaryIs></Polygon>
    </Placemark>`;
      } else if (obj.type === "circles" && obj.anchor) {
        const circlesStyles = (obj.circlesStyles && Array.isArray(obj.circlesStyles) && obj.circlesStyles.length)
          ? obj.circlesStyles
          : ((styles && Array.isArray(styles.circles) && styles.circles.length)
            ? styles.circles
            : [
              { lineColor: "#00aa00", fillColor: "#00aa00", fillOpacity: 0, labelColor: "#0000ff", fontSize: 12, lineWidth: 2 },
              { lineColor: "#ffcc00", fillColor: "#ffcc00", fillOpacity: 0, labelColor: "#0000ff", fontSize: 12, lineWidth: 2 },
              { lineColor: "#ff0000", fillColor: "#ff0000", fillOpacity: 0, labelColor: "#0000ff", fontSize: 12, lineWidth: 2 }
            ]);
        const lat = Number(obj.anchor[1]);
        const lon = Number(obj.anchor[0]);
        [obj.r1, obj.r2, obj.r3].forEach((r, idx) => {
          const s = circlesStyles[Math.min(idx, circlesStyles.length - 1)] || circlesStyles[0];
          const styleId = `managed-circle-style-${objIdx + 1}-${idx + 1}`;
          kml += `
    <Style id="${styleId}">
      <LabelStyle><color>${hexToKmlColor(s.labelColor || "#0000ff", "ff")}</color><scale>${((Number(s.fontSize) || 12) / 12).toFixed(2)}</scale></LabelStyle>
      <LineStyle><color>${hexToKmlColor(s.lineColor, "ff")}</color><width>${Number(s.lineWidth) || 2}</width></LineStyle>
      ${kmlPolyStyleXmlLocal(s.fillColor, opacityToAlphaHex(Number(s.fillOpacity) || 0), (Number(s.fillOpacity) || 0) > 0)}
    </Style>`;
          const radius = Math.max(1, Number(r) || 1);
          const coords = createCircleCoordinates(lat, lon, radius, 96);
          kml += `
    <Placemark>
      <name>${(typeof escapeXml === "function") ? escapeXml(`${objTitle} C${idx + 1}`) : `${objTitle} C${idx + 1}`}</name>
      <styleUrl>#${styleId}</styleUrl>
      <Polygon><outerBoundaryIs><LinearRing><coordinates>${polygonCoordinatesText(coords)}</coordinates></LinearRing></outerBoundaryIs></Polygon>
    </Placemark>`;
        });
      } else if (obj.type === "grid" && obj.anchor) {
        const gridStyle = (obj.gridStyle && typeof obj.gridStyle === "object")
          ? obj.gridStyle
          : ((styles && styles.grid) ? styles.grid : {
            lineColor: "#ffcc00", lineWidth: 2, fillColor: "#ffcc00", fillOpacity: 0, labelColor: "#0000ff", fontSize: 11
          });
        const gridStyleId = `managed-grid-style-${objIdx + 1}`;
        const gridLabelStyleId = `managed-grid-label-style-${objIdx + 1}`;
        const cellFills = (obj.cellFills && typeof obj.cellFills === "object") ? obj.cellFills : {};
        kml += `
    <Style id="${gridStyleId}">
      <LineStyle><color>${hexToKmlColor(gridStyle.lineColor || "#ffcc00", "ff")}</color><width>${Number(gridStyle.lineWidth) || 2}</width></LineStyle>
      ${kmlPolyStyleXmlLocal(gridStyle.fillColor || "#ffcc00", opacityToAlphaHex(Number(gridStyle.fillOpacity) || 0), (Number(gridStyle.fillOpacity) || 0) > 0)}
    </Style>
    <Style id="${gridLabelStyleId}">
      <IconStyle><scale>0</scale></IconStyle>
      <LabelStyle><scale>${((Number(gridStyle.fontSize) || 11) / 12).toFixed(2)}</scale><color>${hexToKmlColor(gridStyle.labelColor || "#0000ff", "ff")}</color></LabelStyle>
    </Style>`;
        const fillPalette = getGridCellFillPaletteForKml();
        fillPalette.forEach((p, i) => {
          kml += `
    <Style id="managed-grid-fill-${objIdx + 1}-${i}">
      <LineStyle><color>${hexToKmlColor(gridStyle.lineColor || "#ffcc00", "ff")}</color><width>${Number(gridStyle.lineWidth) || 2}</width></LineStyle>
      ${kmlPolyStyleXmlLocal(p.hex, p.kmlAlpha || "99", true)}
    </Style>`;
        });
        const lat = Number(obj.anchor[1]);
        const lon = Number(obj.anchor[0]);
        const spacing = Math.max(1, Number(obj.spacing) || 333);
        const northCount = Math.max(1, Math.min(200, Number(obj.northCount) || 8));
        const eastCount = Math.max(1, Math.min(26, Number(obj.eastCount) || 8));
        const rot = Number(obj.rotation) || 0;
        for (let n = 0; n < northCount; n += 1) {
          for (let e = 0; e < eastCount; e += 1) {
            const sw = rotatedOffsetLatLon(lat, lon, n * spacing, e * spacing, rot);
            const se = rotatedOffsetLatLon(lat, lon, n * spacing, (e + 1) * spacing, rot);
            const ne = rotatedOffsetLatLon(lat, lon, (n + 1) * spacing, (e + 1) * spacing, rot);
            const nw = rotatedOffsetLatLon(lat, lon, (n + 1) * spacing, e * spacing, rot);
            const center = rotatedOffsetLatLon(lat, lon, (n + 0.5) * spacing, (e + 0.5) * spacing, rot);
            const cellName = `${eastLabel(e)}${n + 1}`;
            const polygonCoords = [
              [sw[1], sw[0], 0], [se[1], se[0], 0], [ne[1], ne[0], 0], [nw[1], nw[0], 0], [sw[1], sw[0], 0]
            ];
            const fillIdx = Number(cellFills[`${n},${e}`]);
            const styleRef = (Number.isInteger(fillIdx) && fillIdx >= 0 && fillIdx < fillPalette.length)
              ? `managed-grid-fill-${objIdx + 1}-${fillIdx}`
              : gridStyleId;
            kml += `
    <Placemark>
      <name>${(typeof escapeXml === "function") ? escapeXml(`${objTitle} ${cellName}`) : `${objTitle} ${cellName}`}</name>
      <styleUrl>#${styleRef}</styleUrl>
      <Polygon><outerBoundaryIs><LinearRing><coordinates>${polygonCoordinatesText(polygonCoords)}</coordinates></LinearRing></outerBoundaryIs></Polygon>
    </Placemark>
    <Placemark>
      <name>${cellName}</name>
      <styleUrl>#${gridLabelStyleId}</styleUrl>
      <Point><coordinates>${center[1]},${center[0]},0</coordinates></Point>
    </Placemark>`;
          }
        }
      } else if (obj.type === "polygon" && Array.isArray(obj.polygonVertices) && obj.polygonVertices.length >= 3) {
        const polyStyle = (obj.polygonStyle && typeof obj.polygonStyle === "object")
          ? obj.polygonStyle
          : ((styles && styles.polygon) ? styles.polygon : {
            lineColor: "#ff8c00", lineWidth: 2, fillColor: "#ff8c00", fillOpacity: 0.1
          });
        const polyStyleId = `managed-polygon-style-${objIdx + 1}`;
        kml += `
    <Style id="${polyStyleId}">
      <LineStyle><color>${hexToKmlColor(polyStyle.lineColor || "#ff8c00", "ff")}</color><width>${Number(polyStyle.lineWidth) || 2}</width></LineStyle>
      ${kmlPolyStyleXmlLocal(polyStyle.fillColor || "#ff8c00", opacityToAlphaHex(Number(polyStyle.fillOpacity) || 0.1), (Number(polyStyle.fillOpacity) || 0.1) > 0)}
    </Style>`;
        const ring = [...obj.polygonVertices, obj.polygonVertices[0]].map((p) => [Number(p[0]), Number(p[1]), 0]);
        kml += `
    <Placemark>
      <name>${objTitleXml}</name>
      <styleUrl>#${polyStyleId}</styleUrl>
      <Polygon><outerBoundaryIs><LinearRing><coordinates>${polygonCoordinatesText(ring)}</coordinates></LinearRing></outerBoundaryIs></Polygon>
    </Placemark>`;
      } else if (obj.type === "freehand" && Array.isArray(obj.coordinates) && obj.coordinates.length >= 2) {
        const styleId = `managed-freehand-style-${objIdx + 1}`;
        const lw = Number(obj.lineWidth) || 3;
        const lc = obj.lineColor || "#e11d48";
        kml += `
    <Style id="${styleId}">
      <LineStyle><color>${hexToKmlColor(lc, "ff")}</color><width>${lw}</width></LineStyle>
    </Style>
    <Placemark>
      <name>${objTitleXml}</name>
      <styleUrl>#${styleId}</styleUrl>
      <LineString><coordinates>${obj.coordinates.map((p) => `${Number(p[0])},${Number(p[1])},0`).join(" ")}</coordinates></LineString>
    </Placemark>`;
      } else if (obj.type === "pfeil" && Array.isArray(obj.coordinates) && obj.coordinates.length >= 2) {
        const styleId = `managed-pfeil-style-${objIdx + 1}`;
        const tipStyleId = `managed-pfeil-tip-${objIdx + 1}`;
        const lw = Number(obj.lineWidth) || 3;
        const lc = obj.lineColor || "#e11d48";
        const es = normalizePfeilEndStyle(obj.endStyle);
        const coords = obj.coordinates.map((p) => [Number(p[0]), Number(p[1])]);
        const a = coords[0];
        const b = coords[coords.length - 1];
        const tipM = Math.max(12, lw * 6);
        kml += `
    <Style id="${styleId}">
      <LineStyle><color>${hexToKmlColor(lc, "ff")}</color><width>${lw}</width></LineStyle>
    </Style>
    <Style id="${tipStyleId}">
      <LineStyle><color>${hexToKmlColor(lc, "ff")}</color><width>1</width></LineStyle>
      ${kmlPolyStyleXmlLocal(lc, "ff", true)}
    </Style>
    <Placemark>
      <name>${objTitleXml}</name>
      <styleUrl>#${styleId}</styleUrl>
      <LineString><coordinates>${coords.map((p) => `${p[0]},${p[1]},0`).join(" ")}</coordinates></LineString>
    </Placemark>`;
        const tips = [];
        if (es === "dots") {
          tips.push(kmlDotRing(a, tipM * 0.35), kmlDotRing(b, tipM * 0.35));
        } else if (es === "arrows") {
          tips.push(kmlArrowHeadRing(b, a, tipM), kmlArrowHeadRing(a, b, tipM));
        } else {
          tips.push(kmlArrowHeadRing(a, b, tipM));
        }
        tips.forEach((ring, ti) => {
          kml += `
    <Placemark>
      <name>${objTitleXml} tip${ti + 1}</name>
      <styleUrl>#${tipStyleId}</styleUrl>
      <Polygon><outerBoundaryIs><LinearRing><coordinates>${polygonCoordinatesText(ring)}</coordinates></LinearRing></outerBoundaryIs></Polygon>
    </Placemark>`;
        });
      } else if ((obj.type === "points" || obj.type === "textfields") && Array.isArray(obj.labels)) {
        obj.labels.forEach((lp, labelIdx) => {
          const defaults = (typeof labelPointDefaultsByType === "function")
            ? labelPointDefaultsByType(lp.type)
            : { defaultText: "Text", color: "#ff8c00" };
          const color = lp.color || defaults.color || "#ff8c00";
          const textColor = lp.textColor || defaults.textColor || color;
          const styleId = `managed-label-style-${objIdx + 1}-${labelIdx + 1}`;
          const hideIcon = Math.max(0, Number.isFinite(Number(lp.pointRadius)) ? Number(lp.pointRadius) : Number(defaults.pointRadius ?? 7)) <= 0;
          const labelScale = (Math.max(8, Math.min(72, Number.isFinite(Number(lp.fontSize)) ? Number(lp.fontSize) : getObjectLabelFontSize())) / 12).toFixed(2);
          const iconHref = hideIcon
            ? ""
            : "https://maps.google.com/mapfiles/kml/shapes/placemark_circle.png";
          kml += `
    <Style id="${styleId}">
      <IconStyle>
        <color>${hexToKmlColor(color, "ff")}</color>
        <scale>${hideIcon ? "0" : "1.1"}</scale>
        ${hideIcon ? "" : `<Icon><href>${iconHref}</href></Icon>`}
      </IconStyle>
      <LabelStyle><color>${hexToKmlColor(textColor, "ff")}</color><scale>${labelScale}</scale></LabelStyle>
    </Style>
    <Placemark>
      <name>${escapeXml(lp.text || defaults.defaultText || "Text")}</name>
      <styleUrl>#${styleId}</styleUrl>
      ${elevationDataKml(getElevationAt(elevationMap, lp.lon, lp.lat) || 0, 0)}
      <Point><coordinates>${Number(lp.lon)},${Number(lp.lat)},0</coordinates></Point>
    </Placemark>`;
        });
      }
    });
    return kml + kmlFooter();
  }

  function buildCurrentPolygonOnlyKml() {
    if (!Array.isArray(polygonVertices) || polygonVertices.length < 3) return "";
    const styles = (typeof getEditorStyles === "function") ? getEditorStyles() : null;
    const polyStyle = (styles && styles.polygon) ? styles.polygon : {
      lineColor: "#ff8c00", lineWidth: 2, fillColor: "#ff8c00", fillOpacity: 0.1, labelColor: "#0000ff", fontSize: 12
    };
    const ring = [...polygonVertices, polygonVertices[0]].map((p) => [Number(p[0]), Number(p[1]), 0]);
    const fillOp = Number(polyStyle.fillOpacity);
    const fillOpacity = Number.isFinite(fillOp) ? fillOp : 0.1;
    return `${kmlHeader("polygon_export")}
    <Style id="draft-polygon-style">
      <LineStyle><color>${hexToKmlColor(polyStyle.lineColor || "#ff8c00", "ff")}</color><width>${Number(polyStyle.lineWidth) || 2}</width></LineStyle>
      ${kmlPolyStyleXmlLocal(polyStyle.fillColor || "#ff8c00", opacityToAlphaHex(fillOpacity), fillOpacity > 0)}
      <LabelStyle><color>${hexToKmlColor(polyStyle.labelColor || "#0000ff", "ff")}</color><scale>${((Number(polyStyle.fontSize) || 12) / 12).toFixed(2)}</scale></LabelStyle>
    </Style>
    <Placemark>
      <name>Polygon</name>
      <styleUrl>#draft-polygon-style</styleUrl>
      <Polygon><outerBoundaryIs><LinearRing><coordinates>${polygonCoordinatesText(ring)}</coordinates></LinearRing></outerBoundaryIs></Polygon>
    </Placemark>
${kmlFooter()}`;
  }

  function extractKmlDocumentBody(kml) {
    const src = String(kml || "");
    const m = src.match(/<Document>([\s\S]*?)<\/Document>/i);
    let body = m ? String(m[1] || "") : "";
    // Avoid duplicate <name> nodes when merging multiple KML documents.
    body = body.replace(/^\s*<name>[\s\S]*?<\/name>\s*/i, "");
    // Drop per-doc title overlays (re-added once via outer kmlHeader).
    body = body.replace(/<Folder[^>]*id=["']rkrk-project-title["'][\s\S]*?<\/Folder>/gi, "");
    body = body.replace(/<ScreenOverlay>[\s\S]*?<\/ScreenOverlay>/gi, "");
    return body;
  }

  function composeKmlFromDocuments(name, docs) {
    try { patchKmlHeaderForProjectTitle(); } catch (_) { /* noop */ }
    const body = (Array.isArray(docs) ? docs : [])
      .map((d) => extractKmlDocumentBody(d))
      .filter((d) => String(d).trim() !== "")
      .join("\n");
    return `${kmlHeader(name)}${body}${kmlFooter()}`;
  }
  /**
   * Build layers= value for map.geo.admin.ch.
   * web-mapviewer only encodes "|" (as %7C); do NOT encodeURIComponent the whole
   * "KML|url" string — that over-encodes https:// and breaks layer parsing.
   * @see https://github.com/geoadmin/web-mapviewer/blob/develop/packages/mapviewer/src/router/storeSync/layersParamParser.js
   */

  function encodeGeoAdminKmlLayerParam(kmlUrl) {
    const url = String(kmlUrl || "").trim();
    // web-mapviewer encodes only "|". Also percent-encode query separators so
    // ? and & inside the KML URL cannot split the viewer's query string.
    // @style=default = KML-eigene Farben (Google-Earth-Stil), nicht geo.admin-Rot.
    const encUrl = url
      .replace(/\|/g, "%7C")
      .replace(/\?/g, "%3F")
      .replace(/&/g, "%26")
      .replace(/#/g, "%23");
    return `KML%7C${encUrl}@style=default`;
  }
  /** map.geo.admin.ch `center` must be LV95 (EPSG:2056), never Web Mercator 3857. */

  function isValidGeoAdminLv95(easting, northing) {
    const e = Number(easting);
    const n = Number(northing);
    return Number.isFinite(e) && Number.isFinite(n)
      && e >= 2450000 && e <= 2900000
      && n >= 1050000 && n <= 1350000;
  }

  function isLikelyWgs84LonLat(lon, lat) {
    const x = Number(lon);
    const y = Number(lat);
    return Number.isFinite(x) && Number.isFinite(y)
      && Math.abs(x) <= 180 && Math.abs(y) <= 90;
  }

  function isLikelyWebMercatorXY(x, y) {
    const a = Number(x);
    const b = Number(y);
    return Number.isFinite(a) && Number.isFinite(b)
      && (Math.abs(a) > 180 || Math.abs(b) > 90)
      && Math.abs(a) <= 20037508.34 && Math.abs(b) <= 20037508.34;
  }

  function olZoomToGeoAdminZ(olZoom) {
    if (!Number.isFinite(Number(olZoom))) return 11;
    // Empirical: geo.admin z (0..13) ≈ Web Mercator OL zoom ≥ 7.
    return Math.max(0, Math.min(13, Math.round(Number(olZoom) - 7)));
  }
  /** OpenLayers map view center as WGS84 lon/lat (+ approx geo.admin zoom). */

  function getMapViewCenterLonLat() {
    if (typeof map === "undefined" || !map || typeof map.getView !== "function") return null;
    if (typeof ol === "undefined" || !ol.proj || typeof ol.proj.transform !== "function") return null;
    const view = map.getView();
    const c = view.getCenter();
    if (!Array.isArray(c) || c.length < 2) return null;
    const proj = view.getProjection ? view.getProjection() : "EPSG:3857";
    const ll = ol.proj.transform([Number(c[0]), Number(c[1])], proj, "EPSG:4326");
    const lon = Number(ll[0]);
    const lat = Number(ll[1]);
    if (!isLikelyWgs84LonLat(lon, lat)) return null;
    const olZ = (typeof view.getZoom === "function") ? Number(view.getZoom()) : NaN;
    return { lon, lat, z: olZoomToGeoAdminZ(olZ) };
  }
  /**
   * Convert WGS84 lon/lat → LV95 via swisstopo reframe.
   * Also recovers if callers accidentally pass EPSG:3857 meters.
   */
  async function wgs84LonLatToLv95(lon, lat) {
    let x = Number(lon);
    let y = Number(lat);
    if (isLikelyWebMercatorXY(x, y)
      && typeof ol !== "undefined" && ol.proj && typeof ol.proj.transform === "function") {
      const ll = ol.proj.transform([x, y], "EPSG:3857", "EPSG:4326");
      x = Number(ll[0]);
      y = Number(ll[1]);
    }
    if (!isLikelyWgs84LonLat(x, y)) {
      throw new Error("Kartenzentrum ist keine gueltige WGS84-Koordinate.");
    }
    const reframeUrl = `https://geodesy.geo.admin.ch/reframe/wgs84tolv95?easting=${encodeURIComponent(x.toFixed(7))}&northing=${encodeURIComponent(y.toFixed(7))}&format=json`;
    const rf = await fetch(reframeUrl, { cache: "no-store" });
    if (!rf.ok) throw new Error(`Koordinaten-Umrechnung fehlgeschlagen (HTTP ${rf.status})`);
    const rp = await rf.json();
    const e = Number(rp.easting);
    const n = Number(rp.northing);
    if (!isValidGeoAdminLv95(e, n)) {
      throw new Error("LV95-Zentrum ungueltig (EPSG:3857 oder andere Projektion erkannt).");
    }
    return { e, n };
  }

  function formatGeoAdminOpacity(op) {
    const n = Math.max(0, Math.min(1, Number(op)));
    if (!Number.isFinite(n)) return "1";
    // Keep compact but valid (0..1).
    const s = (Math.round(n * 100) / 100).toFixed(2);
    return s.replace(/\.00$/, "").replace(/(\.[0-9])0$/, "$1");
  }

  function buildGeoAdminLayersParam(kmlPublicUrl) {
    const parts = [];
    if (kmlPublicUrl) {
      parts.push(encodeGeoAdminKmlLayerParam(kmlPublicUrl));
    }
    let overlays = [];
    try {
      if (typeof window.__rkrkCollectGeoAdminOverlayLayers === "function") {
        overlays = window.__rkrkCollectGeoAdminOverlayLayers() || [];
      }
    } catch (_) { overlays = []; }
    const seen = {};
    overlays.forEach((o) => {
      const id = String((o && o.id) || "").trim();
      if (!id || seen[id]) return;
      seen[id] = true;
      const visible = (o && o.visible === false) ? "f" : "t";
      const opacity = formatGeoAdminOpacity(o && o.opacity);
      // New geo.admin syntax: id,visibility,opacity - multiple layers via ";"
      parts.push(`${id},${visible},${opacity}`);
    });
    return parts.join(";");
  }

  function buildGeoAdminMapViewerUrl(kmlPublicUrl, easting, northing, zoom) {
    const e = Math.round(Number(easting));
    const n = Math.round(Number(northing));
    // Guard: never emit Web Mercator (or any non-LV95) as center=.
    if (!isValidGeoAdminLv95(e, n)) {
      throw new Error(`Ungueltiges map.geo.admin center (kein LV95): ${e},${n}`);
    }
    const z = Number.isFinite(Number(zoom)) ? Math.max(0, Math.min(13, Math.round(Number(zoom)))) : 11;
    let bg = (typeof window.__rkrkGetBasemapLayerId === "function")
      ? String(window.__rkrkGetBasemapLayerId() || "ch.swisstopo.pixelkarte-farbe")
      : "ch.swisstopo.pixelkarte-farbe";
    // Viewer bgLayer enum is limited; swissimage is accepted in practice, keep as-is.
    // center = LV95 easting,northing (docs.geo.admin.ch); z = viewer zoom 0..13.
    const params = [
      "lang=de",
      `center=${e},${n}`,
      `z=${z}`,
      "topic=ech",
      `layers=${buildGeoAdminLayersParam(kmlPublicUrl)}`,
      `bgLayer=${bg}`,
      "featureInfo=default"
    ].join("&");
    return `https://map.geo.admin.ch/#/map?${params}`;
  }

  function buildSingleCircleDraftKml(lat, lon, elevationMap = new Map()) {
    if (!Number.isFinite(Number(lat)) || !Number.isFinite(Number(lon))) return "";
    const styles = (typeof getEditorStyles === "function") ? getEditorStyles() : null;
    const s = (styles && Array.isArray(styles.circles) && styles.circles[0])
      ? styles.circles[0]
      : { lineColor: "#ff0000", fillColor: "#ff0000", fillOpacity: 0, labelColor: "#0000ff", fontSize: 12, lineWidth: 2 };
    const diamEl = document.getElementById("objSingleDiameter");
    const r = diamEl
      ? Math.max(1, sanitizeNumber(diamEl.value, 200) / 2)
      : Math.max(1, sanitizeNumber(document.getElementById("radius1").value, 100));
    let kml = kmlHeader("circle_export");
    const styleId = "draft-single-circle";
    kml += `
    <Style id="${styleId}">
      <LabelStyle><color>${hexToKmlColor(s.labelColor || "#0000ff", "ff")}</color><scale>${((Number(s.fontSize) || 12) / 12).toFixed(2)}</scale></LabelStyle>
      <LineStyle><color>${hexToKmlColor(s.lineColor, "ff")}</color><width>${Number(s.lineWidth) || 2}</width></LineStyle>
      ${kmlPolyStyleXmlLocal(s.fillColor, opacityToAlphaHex(Number(s.fillOpacity) || 0), (Number(s.fillOpacity) || 0) > 0)}
    </Style>`;
    const coords = createCircleCoordinates(Number(lat), Number(lon), r, 96);
    kml += `
    <Placemark>
      <name>Kreis</name>
      <styleUrl>#${styleId}</styleUrl>
      <Polygon><outerBoundaryIs><LinearRing><coordinates>${polygonCoordinatesText(coords)}</coordinates></LinearRing></outerBoundaryIs></Polygon>
    </Placemark>`;
    return kml + kmlFooter();
  }

  function buildDraftObjectKml(elevationMap = new Map()) {
    // Draft = current UI content not yet fixed as a managed object.
    // When an object is selected for edit, syncEditingObjectFromUi already wrote it into managedObjects.
    if (selectedManagedObjectId) return "";
    const mode = normalizeObjectType(exportTypeEl ? exportTypeEl.value : "grid");
    const lon = Array.isArray(selectedLonLat) && selectedLonLat.length === 2 ? Number(selectedLonLat[0]) : NaN;
    const lat = Array.isArray(selectedLonLat) && selectedLonLat.length === 2 ? Number(selectedLonLat[1]) : NaN;
    if (mode === "points" || mode === "textfields") {
      if (!Array.isArray(labeledPoints) || !labeledPoints.length) return "";
      return `${kmlHeader(mode === "textfields" ? "textfields_export" : "points_export")}${appendLabeledPointsKml(elevationMap)}${kmlFooter()}`;
    }
    if (mode === "polygon") {
      return buildCurrentPolygonOnlyKml();
    }
    if (!(typeof previewActive !== "undefined" && previewActive)) return "";
    if (!Number.isFinite(lon) || !Number.isFinite(lat)) return "";
    if (mode === "grid" && typeof buildGridKml === "function") return buildGridKml(lat, lon, elevationMap);
    if (mode === "circle") return buildSingleCircleDraftKml(lat, lon, elevationMap);
    if (mode === "circles" && typeof buildCirclesKml === "function") return buildCirclesKml(lat, lon, elevationMap);
    if (mode === "pfeil" && Array.isArray(freehandDraftCoords) && freehandDraftCoords.length >= 2) {
      const st = getPfeilStyleFromUi();
      const coords = freehandDraftCoords.map((p) => [Number(p[0]), Number(p[1])]);
      const a = coords[0];
      const b = coords[coords.length - 1];
      const tipM = Math.max(12, st.lineWidth * 6);
      let tips = "";
      const tipRings = [];
      if (st.endStyle === "dots") tipRings.push(kmlDotRing(a, tipM * 0.35), kmlDotRing(b, tipM * 0.35));
      else if (st.endStyle === "arrows") tipRings.push(kmlArrowHeadRing(b, a, tipM), kmlArrowHeadRing(a, b, tipM));
      else tipRings.push(kmlArrowHeadRing(a, b, tipM));
      tipRings.forEach((ring, ti) => {
        tips += `
    <Placemark>
      <name>Pfeil tip${ti + 1}</name>
      <styleUrl>#draft-pfeil-tip</styleUrl>
      <Polygon><outerBoundaryIs><LinearRing><coordinates>${polygonCoordinatesText(ring)}</coordinates></LinearRing></outerBoundaryIs></Polygon>
    </Placemark>`;
      });
      return `${kmlHeader("pfeil_export")}
    <Style id="draft-pfeil">
      <LineStyle><color>${hexToKmlColor(st.lineColor, "ff")}</color><width>${st.lineWidth}</width></LineStyle>
    </Style>
    <Style id="draft-pfeil-tip">
      <LineStyle><color>${hexToKmlColor(st.lineColor, "ff")}</color><width>1</width></LineStyle>
      ${kmlPolyStyleXmlLocal(st.lineColor, "ff", true)}
    </Style>
    <Placemark>
      <name>Pfeil</name>
      <styleUrl>#draft-pfeil</styleUrl>
      <LineString><coordinates>${coords.map((p) => `${p[0]},${p[1]},0`).join(" ")}</coordinates></LineString>
    </Placemark>${tips}
${kmlFooter()}`;
    }
    if (mode === "freehand" && Array.isArray(freehandDraftCoords) && freehandDraftCoords.length >= 2) {
      const colorEl = document.getElementById("objFreehandColor");
      const widthEl = document.getElementById("objFreehandWidth");
      const lc = colorEl ? String(colorEl.value || "#e11d48") : "#e11d48";
      const lw = widthEl ? Math.max(1, Number(widthEl.value) || 3) : 3;
      return `${kmlHeader("freehand_export")}
    <Style id="draft-freehand">
      <LineStyle><color>${hexToKmlColor(lc, "ff")}</color><width>${lw}</width></LineStyle>
    </Style>
    <Placemark>
      <name>Freihandlinie</name>
      <styleUrl>#draft-freehand</styleUrl>
      <LineString><coordinates>${freehandDraftCoords.map((p) => `${Number(p[0])},${Number(p[1])},0`).join(" ")}</coordinates></LineString>
    </Placemark>
${kmlFooter()}`;
    }
    return "";
  }

  function serializeImportedKmlLayerFeatures(entry) {
    if (!entry || !entry.layer || typeof ol === "undefined" || !ol.format || typeof ol.format.KML !== "function") {
      return "";
    }
    if (typeof map === "undefined" || !map || !map.getView) return "";
    try {
      const source = entry.layer.getSource && entry.layer.getSource();
      const features = source && typeof source.getFeatures === "function" ? source.getFeatures() : [];
      if (!features.length) return "";
      const format = new ol.format.KML({ extractStyles: true, showPointNames: true });
      const proj = map.getView().getProjection();
      const written = format.writeFeatures(features, {
        dataProjection: "EPSG:4326",
        featureProjection: proj
      });
      const body = extractKmlDocumentBody(written);
      if (!String(body || "").trim()) return String(written || "");
      const folderName = (typeof escapeXml === "function")
        ? escapeXml(entry.name || "KML-Layer")
        : String(entry.name || "KML-Layer");
      return `${kmlHeader(entry.name || "imported_kml")}
    <Folder>
      <name>${folderName}</name>
${body}
    </Folder>
${kmlFooter()}`;
    } catch (_) {
      return "";
    }
  }

  function buildImportedKmlLayerExportDoc(entry) {
    if (!entry || entry.visible === false) return "";
    // Prefer original file text (styles/farben), sonst Features serialisieren.
    const raw = String(entry.kmlText || "").replace(/^\uFEFF/, "").trim();
    if (raw) {
      let body = extractKmlDocumentBody(raw);
      if (!String(body || "").trim()) {
        // KML ohne <Document>: roh übernehmen, sofern Placemarks vorhanden
        if (/<Placemark[\s>]/i.test(raw) || /<(Point|LineString|Polygon|MultiGeometry)[\s>]/i.test(raw)) {
          body = raw
            .replace(/<\/?kml\b[^>]*>/gi, "")
            .replace(/^\s*<name>[\s\S]*?<\/name>\s*/i, "");
        }
      }
      if (String(body || "").trim()) {
        const folderName = (typeof escapeXml === "function")
          ? escapeXml(entry.name || "KML-Layer")
          : String(entry.name || "KML-Layer");
        // Style-IDs pro Layer uniquifizieren, damit Merges nicht kollidieren
        const prefix = String(entry.id || "kml").replace(/[^a-zA-Z0-9_-]/g, "_") + "-";
        body = body.replace(/\bid=(["'])([^"']+)\1/gi, (m, q, id) => `id=${q}${prefix}${id}${q}`);
        body = body.replace(/<styleUrl>\s*#([^<]+)<\/styleUrl>/gi, (m, id) => {
          const clean = String(id || "").trim();
          if (!clean) return m;
          return `<styleUrl>#${prefix}${clean}</styleUrl>`;
        });
        return `${kmlHeader(entry.name || "imported_kml")}
    <Folder>
      <name>${folderName}</name>
${body}
    </Folder>
${kmlFooter()}`;
      }
    }
    return serializeImportedKmlLayerFeatures(entry);
  }

  function collectImportedKmlExportDocs() {
    if (!Array.isArray(importedKmlLayers) || !importedKmlLayers.length) return [];
    return importedKmlLayers
      .map((entry) => buildImportedKmlLayerExportDoc(entry))
      .filter((d) => String(d || "").trim() !== "");
  }

  function buildCompleteMapExportKml(elevationMap = new Map()) {
    syncEditingObjectFromUi();
    const docs = [];
    if (Array.isArray(managedObjects) && managedObjects.length > 0) {
      docs.push(buildAllManagedObjectsKml(elevationMap));
    }
    const draft = buildDraftObjectKml(elevationMap);
    if (draft) docs.push(draft);
    // Loose labeled points/texts on the map that are not part of the current draft export
    // and not the currently edited managed points object (already synced into managedObjects).
    const editingPoints = !!(selectedManagedObjectId && managedObjects.some((o) =>
      o.id === selectedManagedObjectId && (o.type === "points" || o.type === "textfields")));
    const mode = normalizeObjectType(exportTypeEl ? exportTypeEl.value : "grid");
    const draftIsPoints = !selectedManagedObjectId && (mode === "points" || mode === "textfields");
    if (!editingPoints && !draftIsPoints
      && Array.isArray(labeledPoints) && labeledPoints.length > 0
      && typeof appendLabeledPointsKml === "function") {
      docs.push(`${kmlHeader("labels_export")}${appendLabeledPointsKml(elevationMap)}${kmlFooter()}`);
    }
    // Sichtbare importierte KML-Layer (Checkbox an) mitexportieren
    try {
      const importedDocs = collectImportedKmlExportDocs();
      for (let i = 0; i < importedDocs.length; i += 1) docs.push(importedDocs[i]);
    } catch (_) { /* noop */ }
    if (!docs.length) return "";
    if (docs.length === 1) return docs[0];
    return composeKmlFromDocuments("complete_export", docs);
  }

  window.__rkrkGetManagedObjectsForExport = function () {
    return Array.isArray(managedObjects) ? managedObjects.slice() : [];
  };


  /* ========== MBTiles for DJI Pilot 2 Custom Layer ==========
   * Matches SAR_*.mbtiles: PNG/RGBA overlay, TMS tile_row, metadata type=overlay.
   * Import on RC: Pilot 2 → Profile → Map Settings → Custom Layer.
   */
  function mbtilesLonToTileX(lon, z) {
    const n = 1 << z;
    let x = Math.floor((Number(lon) + 180) / 360 * n);
    if (x < 0) x = 0;
    if (x >= n) x = n - 1;
    return x;
  }
  function mbtilesLatToTileYxyz(lat, z) {
    const n = 1 << z;
    const latRad = Number(lat) * Math.PI / 180;
    const y = Math.floor((1 - Math.log(Math.tan(latRad) + 1 / Math.cos(latRad)) / Math.PI) / 2 * n);
    return Math.max(0, Math.min(n - 1, y));
  }
  function mbtilesXyzToTmsY(z, yxyz) {
    return ((1 << z) - 1) - Number(yxyz);
  }
  function mbtilesLonLatToWorldPx(lon, lat, z) {
    const scale = (1 << z) * 256;
    const x = (Number(lon) + 180) / 360 * scale;
    const sin = Math.sin(Number(lat) * Math.PI / 180);
    const y = (0.5 - Math.log((1 + sin) / (1 - sin)) / (4 * Math.PI)) * scale;
    return [x, y];
  }
  function mbtilesNormLonLatPair(a, b) {
    const x = Number(a);
    const y = Number(b);
    if (!Number.isFinite(x) || !Number.isFinite(y)) return null;
    // Prefer lon,lat (this app stores lon first for freehand/polygon)
    if (Math.abs(x) <= 180 && Math.abs(y) <= 90) return [x, y];
    if (Math.abs(y) <= 180 && Math.abs(x) <= 90) return [y, x];
    return [x, y];
  }
  function mbtilesExpandBounds(b, lon, lat, pad) {
    if (!Number.isFinite(lon) || !Number.isFinite(lat)) return;
    const p = Number(pad) || 0;
    b.w = Math.min(b.w, lon - p);
    b.s = Math.min(b.s, lat - p);
    b.e = Math.max(b.e, lon + p);
    b.n = Math.max(b.n, lat + p);
  }
  function mbtilesCollectDrawItems() {
    const items = [];
    const bounds = { w: Infinity, s: Infinity, e: -Infinity, n: -Infinity };
    const addRing = (ringLonLat, style) => {
      const ring = [];
      (ringLonLat || []).forEach((p) => {
        const ll = Array.isArray(p) ? mbtilesNormLonLatPair(p[0], p[1]) : null;
        if (!ll) return;
        ring.push(ll);
        mbtilesExpandBounds(bounds, ll[0], ll[1], 0);
      });
      if (ring.length >= 2) items.push({ kind: "poly", ring, style: style || {} });
    };
    const addLine = (ptsLonLat, style) => {
      const pts = [];
      (ptsLonLat || []).forEach((p) => {
        const ll = Array.isArray(p) ? mbtilesNormLonLatPair(p[0], p[1]) : null;
        if (!ll) return;
        pts.push(ll);
        mbtilesExpandBounds(bounds, ll[0], ll[1], 0);
      });
      if (pts.length >= 2) items.push({ kind: "line", pts, style: style || {} });
    };
    const addPoint = (lon, lat, style, text) => {
      const ll = mbtilesNormLonLatPair(lon, lat);
      if (!ll) return;
      mbtilesExpandBounds(bounds, ll[0], ll[1], 0);
      items.push({ kind: "point", lon: ll[0], lat: ll[1], style: style || {}, text: text || "" });
    };

    (managedObjects || []).forEach((obj) => {
      if (!obj) return;
      try {
        if (typeof isManagedObjectMapHidden === "function" && isManagedObjectMapHidden(obj)) return;
      } catch (_) { /* noop */ }
      const gs = (obj.gridStyle && typeof obj.gridStyle === "object") ? obj.gridStyle : {};
      const cs = (obj.circleStyle && typeof obj.circleStyle === "object") ? obj.circleStyle : {};
      const ps = (obj.polygonStyle && typeof obj.polygonStyle === "object") ? obj.polygonStyle : {};
      const fh = (obj.freehandStyle && typeof obj.freehandStyle === "object") ? obj.freehandStyle : {};
      if (obj.type === "polygon" && Array.isArray(obj.polygonVertices)) {
        addRing(obj.polygonVertices, {
          stroke: ps.lineColor || "#ff8c00",
          width: Number(ps.lineWidth) || 2,
          fill: ps.fillColor || "#ff8c00",
          fillA: Math.max(0.15, Number(ps.fillOpacity) || 0.25)
        });
      } else if (obj.type === "circle" && obj.anchor && typeof createCircleCoordinates === "function") {
        const coords = createCircleCoordinates(Number(obj.anchor[1]), Number(obj.anchor[0]), Math.max(1, Number(obj.r) || 1), 64);
        addRing(coords, {
          stroke: cs.lineColor || "#ff0000",
          width: Number(cs.lineWidth) || 2,
          fill: cs.fillColor || "#ff0000",
          fillA: Math.max(0.08, Number(cs.fillOpacity) || 0.15)
        });
        const label = (cs.labelText || obj.labelText || managedObjectDisplayName(obj) || "").slice(0, 40);
        addPoint(obj.anchor[0], obj.anchor[1], { color: cs.labelColor || "#0000ff" }, label);
      } else if (obj.type === "circles" && obj.anchor && typeof createCircleCoordinates === "function") {
        const styles = Array.isArray(obj.circlesStyles) ? obj.circlesStyles : [];
        [obj.r1, obj.r2, obj.r3].forEach((r, i) => {
          if (!Number.isFinite(Number(r)) || Number(r) <= 0) return;
          const s = styles[i] || styles[0] || {};
          const coords = createCircleCoordinates(Number(obj.anchor[1]), Number(obj.anchor[0]), Number(r), 64);
          addRing(coords, {
            stroke: s.lineColor || "#ffcc00",
            width: Number(s.lineWidth) || 2,
            fill: s.fillColor || "#ffcc00",
            fillA: Math.max(0.05, Number(s.fillOpacity) || 0.1)
          });
        });
      } else if (obj.type === "grid" && obj.anchor && typeof rotatedOffsetLatLon === "function") {
        const lat = Number(obj.anchor[1]);
        const lon = Number(obj.anchor[0]);
        const nc = Math.max(1, Number(obj.northCount) || 1);
        const ec = Math.max(1, Number(obj.eastCount) || 1);
        const sp = Math.max(1, Number(obj.spacing) || 1);
        const rot = Number(obj.rotation) || 0;
        const fills = (obj.cellFills && typeof obj.cellFills === "object") ? obj.cellFills : {};
        const pal = (typeof getGridCellFillPaletteForKml === "function") ? getGridCellFillPaletteForKml() : [];
        for (let n = 0; n < nc; n += 1) {
          for (let e = 0; e < ec; e += 1) {
            const sw = rotatedOffsetLatLon(lat, lon, n * sp, e * sp, rot);
            const se = rotatedOffsetLatLon(lat, lon, n * sp, (e + 1) * sp, rot);
            const ne = rotatedOffsetLatLon(lat, lon, (n + 1) * sp, (e + 1) * sp, rot);
            const nw = rotatedOffsetLatLon(lat, lon, (n + 1) * sp, e * sp, rot);
            const center = rotatedOffsetLatLon(lat, lon, (n + 0.5) * sp, (e + 0.5) * sp, rot);
            const ring = [[sw[1], sw[0]], [se[1], se[0]], [ne[1], ne[0]], [nw[1], nw[0]], [sw[1], sw[0]]];
            const key = n + "," + e;
            const idx = fills[key];
            let fillA = 0.05;
            let fill = gs.fillColor || "#ffd400";
            if (Number.isInteger(Number(idx)) && pal[Number(idx)]) {
              fill = pal[Number(idx)].hex || fill;
              fillA = Number(pal[Number(idx)].fillAlpha) || 0.45;
            } else if (Number(gs.fillOpacity) > 0) {
              fillA = Number(gs.fillOpacity) || 0.1;
            }
            addRing(ring, {
              stroke: gs.lineColor || "#ffd400",
              width: Number(gs.lineWidth) || 2,
              fill,
              fillA
            });
            const cellName = (typeof eastLabel === "function")
              ? (eastLabel(e) + String(n + 1))
              : (String.fromCharCode(65 + (e % 26)) + String(n + 1));
            addPoint(center[1], center[0], { color: gs.labelColor || "#0000ff", font: Number(gs.fontSize) || 11 }, cellName);
          }
        }
      } else if ((obj.type === "freehand" || obj.type === "pfeil") && Array.isArray(obj.freehandCoords)) {
        addLine(obj.freehandCoords, {
          stroke: fh.lineColor || obj.lineColor || "#e11d48",
          width: Number(fh.lineWidth || obj.lineWidth) || 3
        });
      } else if ((obj.type === "points" || obj.type === "textfields") && Array.isArray(obj.labels)) {
        obj.labels.forEach((lp) => {
          if (!lp || !Array.isArray(lp.lonLat)) return;
          addPoint(lp.lonLat[0], lp.lonLat[1], {
            color: lp.textColor || lp.color || "#ff8c00",
            font: Number(lp.fontSize) || 12,
            pointColor: lp.color || "#ff8c00"
          }, String(lp.text || "").slice(0, 48));
        });
      }
    });

    // Unfixed drafts
    try {
      if (typeof polygonVertices !== "undefined" && Array.isArray(polygonVertices) && polygonVertices.length >= 3) {
        addRing(polygonVertices, { stroke: "#ff8c00", width: 2, fill: "#ff8c00", fillA: 0.2 });
      }
    } catch (_) { /* noop */ }
    try {
      if (typeof freehandDraftCoords !== "undefined" && Array.isArray(freehandDraftCoords) && freehandDraftCoords.length >= 2) {
        addLine(freehandDraftCoords, { stroke: "#e11d48", width: 3 });
      }
    } catch (_) { /* noop */ }

    if (!Number.isFinite(bounds.w)) return { items: [], bounds: null };
    // ~25 m pad in degrees
    const pad = 0.00025;
    bounds.w -= pad; bounds.s -= pad; bounds.e += pad; bounds.n += pad;
    return { items, bounds };
  }

  function mbtilesChooseZoomRange(bounds) {
    let viewZ = 15;
    try {
      if (typeof map !== "undefined" && map && map.getView) {
        const z = map.getView().getZoom();
        if (Number.isFinite(z)) viewZ = Math.round(z);
      }
    } catch (_) { /* noop */ }
    const span = Math.max(bounds.e - bounds.w, bounds.n - bounds.s);
    let minZ = Math.max(10, viewZ - 2);
    let maxZ = Math.min(19, Math.max(minZ + 1, viewZ + 2));
    if (span > 0.05) { minZ = Math.min(minZ, 12); maxZ = Math.min(maxZ, 16); }
    if (span < 0.005) { maxZ = Math.min(20, Math.max(maxZ, 18)); }
    return { minZ, maxZ };
  }

  function mbtilesTileHasContent(ctx) {
    try {
      const data = ctx.getImageData(0, 0, 256, 256).data;
      for (let i = 3; i < data.length; i += 16) {
        if (data[i] > 8) return true;
      }
    } catch (_) {
      return true;
    }
    return false;
  }

  function mbtilesRenderTilePng(items, z, x, yxyz) {
    const canvas = document.createElement("canvas");
    canvas.width = 256;
    canvas.height = 256;
    const ctx = canvas.getContext("2d", { willReadFrequently: true });
    if (!ctx) return null;
    ctx.clearRect(0, 0, 256, 256);
    const originX = x * 256;
    const originY = yxyz * 256;
    const toPx = (lon, lat) => {
      const w = mbtilesLonLatToWorldPx(lon, lat, z);
      return [w[0] - originX, w[1] - originY];
    };
    items.forEach((it) => {
      if (it.kind === "poly" && it.ring && it.ring.length >= 3) {
        ctx.beginPath();
        it.ring.forEach((ll, i) => {
          const p = toPx(ll[0], ll[1]);
          if (i === 0) ctx.moveTo(p[0], p[1]);
          else ctx.lineTo(p[0], p[1]);
        });
        ctx.closePath();
        const fa = Number(it.style.fillA);
        if (Number.isFinite(fa) && fa > 0) {
          ctx.fillStyle = it.style.fill || "#ffcc00";
          ctx.globalAlpha = Math.max(0.05, Math.min(0.85, fa));
          ctx.fill();
          ctx.globalAlpha = 1;
        }
        ctx.strokeStyle = it.style.stroke || "#ffcc00";
        ctx.lineWidth = Math.max(1, Number(it.style.width) || 2);
        ctx.stroke();
      } else if (it.kind === "line" && it.pts && it.pts.length >= 2) {
        ctx.beginPath();
        it.pts.forEach((ll, i) => {
          const p = toPx(ll[0], ll[1]);
          if (i === 0) ctx.moveTo(p[0], p[1]);
          else ctx.lineTo(p[0], p[1]);
        });
        ctx.strokeStyle = it.style.stroke || "#e11d48";
        ctx.lineWidth = Math.max(1, Number(it.style.width) || 3);
        ctx.lineJoin = "round";
        ctx.lineCap = "round";
        ctx.stroke();
      } else if (it.kind === "point") {
        const p = toPx(it.lon, it.lat);
        if (p[0] < -40 || p[0] > 296 || p[1] < -40 || p[1] > 296) return;
        const pc = it.style.pointColor || it.style.color || "#ff8c00";
        ctx.fillStyle = pc;
        ctx.beginPath();
        ctx.arc(p[0], p[1], 4, 0, Math.PI * 2);
        ctx.fill();
        const text = String(it.text || "").trim();
        if (text) {
          const fs = Math.max(9, Math.min(18, Number(it.style.font) || 11));
          ctx.font = "600 " + fs + "px Arial,Helvetica,sans-serif";
          ctx.lineWidth = 3;
          ctx.strokeStyle = "#ffffff";
          ctx.fillStyle = it.style.color || "#0000ff";
          ctx.strokeText(text, p[0] + 6, p[1] - 6);
          ctx.fillText(text, p[0] + 6, p[1] - 6);
        }
      }
    });
    if (!mbtilesTileHasContent(ctx)) return null;
    const dataUrl = canvas.toDataURL("image/png");
    const b64 = dataUrl.indexOf(",") >= 0 ? dataUrl.split(",")[1] : "";
    return b64 || null;
  }

  function mbtilesBuildTileList(bounds, minZ, maxZ, maxTiles) {
    const list = [];
    const cap = Math.max(50, Number(maxTiles) || 2500);
    for (let z = minZ; z <= maxZ; z += 1) {
      const x0 = mbtilesLonToTileX(bounds.w, z);
      const x1 = mbtilesLonToTileX(bounds.e, z);
      const y0 = mbtilesLatToTileYxyz(bounds.n, z);
      const y1 = mbtilesLatToTileYxyz(bounds.s, z);
      for (let x = x0; x <= x1; x += 1) {
        for (let y = y0; y <= y1; y += 1) {
          list.push({ z, x, yxyz: y });
          if (list.length > cap) return { list, truncated: true };
        }
      }
    }
    return { list, truncated: false };
  }

  async function buildMbtilesPayloadForDji() {
    try { syncEditingObjectFromUi(); } catch (_) { /* noop */ }
    const collected = mbtilesCollectDrawItems();
    if (!collected.bounds || !collected.items.length) {
      throw new Error("Keine Objekte zum Rendern (Raster/Kreis/Polygon/Linie/Punkt).");
    }
    const zr = mbtilesChooseZoomRange(collected.bounds);
    const planned = mbtilesBuildTileList(collected.bounds, zr.minZ, zr.maxZ, 2500);
    const tiles = [];
    const batch = planned.list;
    for (let i = 0; i < batch.length; i += 1) {
      const t = batch[i];
      const png = mbtilesRenderTilePng(collected.items, t.z, t.x, t.yxyz);
      if (!png) continue;
      tiles.push({
        z: t.z,
        x: t.x,
        y: mbtilesXyzToTmsY(t.z, t.yxyz),
        png
      });
      if (i % 40 === 0) {
        await new Promise((r) => setTimeout(r, 0));
        try {
          setSharePanelStatus("MBTiles: Kachel " + (i + 1) + "/" + batch.length + " …");
        } catch (_) { /* noop */ }
      }
    }
    if (!tiles.length) throw new Error("Keine sichtbaren Kacheln erzeugt.");
    const b = collected.bounds;
    const centerLon = (b.w + b.e) / 2;
    const centerLat = (b.s + b.n) / 2;
    let title = (typeof window.rkrOverlayEditorTitle === "function")
      ? window.rkrOverlayEditorTitle()
      : "RKRK Overlay";
    try {
      if (typeof getOverlayEditorTitle === "function") {
        const tt = String(getOverlayEditorTitle() || "").trim();
        if (tt) title = tt;
      }
    } catch (_) { /* noop */ }
    const product = (typeof window.rkrOverlayEditorTitle === "function")
      ? window.rkrOverlayEditorTitle()
      : ((window.__RKR_BRAND__ && window.__RKR_BRAND__.isRkrs) || window.__ON_RKRS__
        ? "RKRS Overlay-Editor"
        : "RKRK Overlay-Editor");
    return {
      name: title,
      description: product + " — DJI Pilot 2 Custom Layer (PNG overlay)",
      bounds: [b.w, b.s, b.e, b.n].map((n) => Number(n).toFixed(6)).join(","),
      center: [centerLon, centerLat, zr.maxZ].map((n) => Number(n).toFixed(5)).join(",").replace(/\.00000/, ""),
      minzoom: zr.minZ,
      maxzoom: zr.maxZ,
      tiles,
      truncated: planned.truncated,
      tilePlan: batch.length
    };
  }

  async function onMbtilesExportClick() {
    const btn = document.getElementById("exportMbtilesBtn");
    if (btn) btn.disabled = true;
    try {
      setSharePanelStatus("MBTiles wird gerendert (DJI Pilot 2 Overlay)…");
      const payload = await buildMbtilesPayloadForDji();
      setSharePanelStatus("MBTiles-Paket wird gebaut (" + payload.tiles.length + " Kacheln)…");
      const res = await fetch(MBTILES_EXPORT_API, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload)
      });
      const ctype = String(res.headers.get("Content-Type") || "");
      if (!res.ok) {
        let err = "HTTP " + res.status;
        try {
          const j = await res.json();
          if (j && j.error) err = j.error;
        } catch (_) { /* noop */ }
        throw new Error(err);
      }
      if (ctype.indexOf("json") >= 0) {
        const j = await res.json();
        throw new Error((j && j.error) || "Server lieferte JSON statt MBTiles");
      }
      const blob = await res.blob();
      downloadBlob(stampExportFilename("dji", "mbtiles"), blob);
      const note = payload.truncated
        ? " (Zoom begrenzt wegen Kachel-Limit)"
        : "";
      setSharePanelStatus(
        "MBTiles gespeichert (" + payload.tiles.length + " Kacheln, z" + payload.minzoom + "–" + payload.maxzoom + ")"
        + note + ". Auf RC: Pilot 2 → Profil → Karteneinstellungen → Custom Layer → MBTiles-Datei wählen (KML dort nicht als Overlay)."
      );
    } catch (err) {
      const msg = "MBTiles fehlgeschlagen: " + ((err && err.message) ? err.message : String(err));
      try { setSharePanelStatus(msg); } catch (_) { /* noop */ }
      alert(msg);
    } finally {
      if (btn) btn.disabled = false;
    }
  }

  function ensureMbtilesExportButton() {
    let btn = document.getElementById("exportMbtilesBtn");
    if (!btn) {
      btn = document.createElement("button");
      btn.id = "exportMbtilesBtn";
      btn.type = "button";
    }
    setObjToolButton(btn, "mbtiles", "DJI Layer");
    btn.title = "Für DJI Pilot 2: Objekte als MBTiles-Overlay. RC: Profil → Karteneinstellungen → Custom Layer";
    if (!btn.__rkrkMbtilesBound) {
      btn.__rkrkMbtilesBound = true;
      btn.addEventListener("click", (ev) => {
        ev.preventDefault();
        onMbtilesExportClick();
      });
    }
    return btn;
  }

  const SHARE_PANEL_HINT = "KML = Earth/geo.admin. DJI Layer = MBTiles auf die RC: Profil → Karteneinstellungen → Custom Layer (kein Live). SwissTopo, PDF (Massstab 1:10'000 / 1:25'000 / 1:50'000 auf A4) und Ansicht-Link. Geladene (aktive) KML-Layer gehen in KML-/SwissTopo-Export und PDF-Ansicht mit. PDF im Druckdialog auf 100 % / tatsächliche Grösse stellen.";
  const SHARE_PANEL_HINT_HUB2 = "KML = Earth/geo.admin. Hub 2 KML = Annotationen in FlightHub 2 importieren, dann Ordner an Pilot 2 verteilen (kein Live). DJI Layer = MBTiles auf die RC: Profil → Karteneinstellungen → Custom Layer (kein Live). SwissTopo, PDF (Massstab 1:10'000 / 1:25'000 / 1:50'000 auf A4) und Ansicht-Link. Geladene (aktive) KML-Layer gehen in KML-/SwissTopo-Export und PDF-Ansicht mit. PDF im Druckdialog auf 100 % / tatsächliche Grösse stellen.";
  const OBJ_EDITOR_HELP_EXPORT = "8. Sektion Export & Teilen: nichts davon ist Live (Snapshot). KML = Earth/geo.admin. DJI Layer = MBTiles direkt auf die RC (Profil → Karteneinstellungen → Custom Layer). SwissTopo, PDF (Massstab Ansicht / 1:10'000 / 1:25'000 / 1:50'000 auf A4, 100 % drucken) und Ansicht-Link (kein Broadcast zu DJI).";
  const OBJ_EDITOR_HELP_EXPORT_HUB2 = "8. Sektion Export & Teilen: nichts davon ist Live (Snapshot). KML = Earth/geo.admin. Hub 2 KML = Annotationen in FlightHub 2 importieren, Ordner als Projekt-Admin an Pilot 2 verteilen; RC im selben Projekt anmelden. DJI Layer = MBTiles direkt auf die RC (Profil → Karteneinstellungen → Custom Layer), kein Hub-2-Objekt. Optional Nach Hub 2 senden, wenn der Server Key/Projekt hat. SwissTopo, PDF (Massstab Ansicht / 1:10'000 / 1:25'000 / 1:50'000 auf A4, 100 % drucken) und Ansicht-Link (kein Broadcast zu DJI).";

  function hub2UiAllowed() {
    try {
      const u = new URL(window.location.href);
      if (u.searchParams.has("ADMIN") || u.searchParams.has("HUB")) return true;
      const code = String(u.searchParams.get("code") || "").trim().toUpperCase();
      return code === "ADMIN" || code === "HUB";
    } catch (_) {
      return false;
    }
  }

  function sharePanelHintText() {
    return hub2UiAllowed() ? SHARE_PANEL_HINT_HUB2 : SHARE_PANEL_HINT;
  }

  function syncObjEditorExportHelp() {
    const el = document.getElementById("objEditorHelp");
    if (!el) return;
    const html = String(el.innerHTML || "");
    const next = hub2UiAllowed() ? OBJ_EDITOR_HELP_EXPORT_HUB2 : OBJ_EDITOR_HELP_EXPORT;
    if (/8\.\s*Sektion/.test(html)) {
      el.innerHTML = html.replace(/8\.\s*Sektion[\s\S]*?(?=<br\s*\/?>\s*9\.|$)/i, next + "<br>");
    }
  }

  function hub2IsConfigured() {
    try {
      return !!(window.__RKRK_FLIGHTHUB__ && window.__RKRK_FLIGHTHUB__.configured);
    } catch (_) {
      return false;
    }
  }

  function hub2NormColor(hex) {
    let c = String(hex || "#ff8a00").trim();
    if (c.charAt(0) !== "#") c = `#${c}`;
    if (/^#[0-9a-fA-F]{3}$/.test(c)) {
      c = `#${c[1]}${c[1]}${c[2]}${c[2]}${c[3]}${c[3]}`;
    }
    return /^#[0-9a-fA-F]{6}$/.test(c) ? c.toLowerCase() : "#ff8a00";
  }

  function hub2CloseRing(ring) {
    const pts = (ring || []).map((p) => [Number(p[0]), Number(p[1])]).filter((p) => Number.isFinite(p[0]) && Number.isFinite(p[1]));
    if (pts.length < 3) return pts;
    const a = pts[0];
    const b = pts[pts.length - 1];
    if (Math.abs(a[0] - b[0]) > 1e-9 || Math.abs(a[1] - b[1]) > 1e-9) pts.push([a[0], a[1]]);
    return pts;
  }

  function collectHub2AnnotationItems() {
    const items = [];
    const xmlName = (s) => String(s || "Objekt").trim() || "Objekt";
    const addPoint = (lon, lat, name, color, hideIcon) => {
      const x = Number(lon);
      const y = Number(lat);
      if (!Number.isFinite(x) || !Number.isFinite(y)) return;
      items.push({
        kind: "point",
        name: xmlName(name),
        color: hub2NormColor(color),
        lon: x,
        lat: y,
        hideIcon: !!hideIcon
      });
    };
    const addLine = (ptsLonLat, name, color) => {
      const pts = [];
      (ptsLonLat || []).forEach((p) => {
        const x = Number(Array.isArray(p) ? p[0] : NaN);
        const y = Number(Array.isArray(p) ? p[1] : NaN);
        if (Number.isFinite(x) && Number.isFinite(y)) pts.push([x, y]);
      });
      if (pts.length < 2) return;
      items.push({ kind: "line", name: xmlName(name), color: hub2NormColor(color), pts });
    };
    const addPoly = (ringLonLat, name, color) => {
      const ring = hub2CloseRing(ringLonLat);
      if (ring.length < 4) return;
      items.push({ kind: "polygon", name: xmlName(name), color: hub2NormColor(color), ring });
    };

    (managedObjects || []).forEach((obj) => {
      if (!obj) return;
      try {
        if (typeof isManagedObjectMapHidden === "function" && isManagedObjectMapHidden(obj)) return;
      } catch (_) { /* noop */ }
      const title = (typeof managedObjectDisplayName === "function") ? managedObjectDisplayName(obj) : (obj.name || "Objekt");
      const gs = (obj.gridStyle && typeof obj.gridStyle === "object") ? obj.gridStyle : {};
      const cs = (obj.circleStyle && typeof obj.circleStyle === "object") ? obj.circleStyle : {};
      const ps = (obj.polygonStyle && typeof obj.polygonStyle === "object") ? obj.polygonStyle : {};
      if (obj.type === "polygon" && Array.isArray(obj.polygonVertices)) {
        addPoly(obj.polygonVertices, title, ps.fillColor || ps.lineColor || "#ff8c00");
      } else if (obj.type === "circle" && obj.anchor && typeof createCircleCoordinates === "function") {
        const coords = createCircleCoordinates(Number(obj.anchor[1]), Number(obj.anchor[0]), Math.max(1, Number(obj.r) || 1), 64);
        addPoly(coords, title, cs.fillColor || cs.lineColor || "#ff0000");
      } else if (obj.type === "circles" && obj.anchor && typeof createCircleCoordinates === "function") {
        const styles = Array.isArray(obj.circlesStyles) ? obj.circlesStyles : [];
        [obj.r1, obj.r2, obj.r3].forEach((r, i) => {
          if (!Number.isFinite(Number(r)) || Number(r) <= 0) return;
          const s = styles[i] || styles[0] || {};
          const coords = createCircleCoordinates(Number(obj.anchor[1]), Number(obj.anchor[0]), Number(r), 64);
          addPoly(coords, `${title} C${i + 1}`, s.fillColor || s.lineColor || "#ffcc00");
        });
      } else if (obj.type === "grid" && obj.anchor && typeof rotatedOffsetLatLon === "function") {
        const lat = Number(obj.anchor[1]);
        const lon = Number(obj.anchor[0]);
        const nc = Math.max(1, Number(obj.northCount) || 1);
        const ec = Math.max(1, Number(obj.eastCount) || 1);
        const sp = Math.max(1, Number(obj.spacing) || 1);
        const rot = Number(obj.rotation) || 0;
        const fills = (obj.cellFills && typeof obj.cellFills === "object") ? obj.cellFills : {};
        const pal = (typeof getGridCellFillPaletteForKml === "function") ? getGridCellFillPaletteForKml() : [];
        let filledAny = false;
        for (let n = 0; n < nc; n += 1) {
          for (let e = 0; e < ec; e += 1) {
            const fillIdx = Number(fills[`${n},${e}`]);
            if (!Number.isInteger(fillIdx) || fillIdx < 0 || !pal[fillIdx] || !pal[fillIdx].hex) continue;
            filledAny = true;
            const sw = rotatedOffsetLatLon(lat, lon, n * sp, e * sp, rot);
            const se = rotatedOffsetLatLon(lat, lon, n * sp, (e + 1) * sp, rot);
            const ne = rotatedOffsetLatLon(lat, lon, (n + 1) * sp, (e + 1) * sp, rot);
            const nw = rotatedOffsetLatLon(lat, lon, (n + 1) * sp, e * sp, rot);
            const cellName = `${typeof eastLabel === "function" ? eastLabel(e) : String.fromCharCode(65 + e)}${n + 1}`;
            addPoly(
              [[sw[1], sw[0]], [se[1], se[0]], [ne[1], ne[0]], [nw[1], nw[0]]],
              `${title} ${cellName}`,
              pal[fillIdx].hex
            );
          }
        }
        if (!filledAny) {
          const sw = rotatedOffsetLatLon(lat, lon, 0, 0, rot);
          const se = rotatedOffsetLatLon(lat, lon, 0, ec * sp, rot);
          const ne = rotatedOffsetLatLon(lat, lon, nc * sp, ec * sp, rot);
          const nw = rotatedOffsetLatLon(lat, lon, nc * sp, 0, rot);
          addPoly(
            [[sw[1], sw[0]], [se[1], se[0]], [ne[1], ne[0]], [nw[1], nw[0]]],
            title,
            gs.lineColor || gs.fillColor || "#ffcc00"
          );
        }
      } else if ((obj.type === "freehand" || obj.type === "pfeil") && Array.isArray(obj.coordinates)) {
        addLine(obj.coordinates, title, obj.lineColor || "#e11d48");
      } else if ((obj.type === "points" || obj.type === "textfields") && Array.isArray(obj.labels)) {
        obj.labels.forEach((lp) => {
          if (!lp) return;
          const defaults = (typeof labelPointDefaultsByType === "function")
            ? labelPointDefaultsByType(lp.type)
            : { color: "#ff8a00" };
          const hideIcon = obj.type === "textfields"
            || (typeof isTextOnlyLabelType === "function" && isTextOnlyLabelType(lp.type));
          const color = hideIcon
            ? (lp.textColor || lp.color || defaults.textColor || defaults.color || "#ff8a00")
            : (lp.color || lp.textColor || defaults.color || "#ff8a00");
          const nm = String(lp.text || "").trim()
            || (typeof pointTypeColorLabel === "function" ? pointTypeColorLabel(lp.type) : "")
            || title;
          addPoint(lp.lon, lp.lat, nm, color, hideIcon);
        });
      }
    });

    if (typeof labeledPoints !== "undefined" && Array.isArray(labeledPoints)) {
      labeledPoints.forEach((lp) => {
        if (!lp) return;
        const already = items.some((it) => it.kind === "point"
          && Math.abs(it.lon - Number(lp.lon)) < 1e-8
          && Math.abs(it.lat - Number(lp.lat)) < 1e-8);
        if (already) return;
        const defaults = (typeof labelPointDefaultsByType === "function")
          ? labelPointDefaultsByType(lp.type)
          : { color: "#ff8a00" };
        const hideIcon = (typeof isTextOnlyLabelType === "function" && isTextOnlyLabelType(lp.type));
        const color = hideIcon
          ? (lp.textColor || lp.color || defaults.textColor || defaults.color || "#ff8a00")
          : (lp.color || lp.textColor || defaults.color || "#ff8a00");
        const nm = String(lp.text || "").trim()
          || (typeof pointTypeColorLabel === "function" ? pointTypeColorLabel(lp.type) : "")
          || "Punkt";
        addPoint(lp.lon, lp.lat, nm, color, hideIcon);
      });
    }
    return items;
  }

  function buildHub2Kml(items) {
    const title = (typeof getOverlayEditorTitle === "function")
      ? getOverlayEditorTitle()
      : "Overlay";
    const esc = (s) => (typeof escapeXml === "function" ? escapeXml(s) : escapeXmlText(s));
    const toKml = (hex, alpha) => {
      if (typeof hexToKmlColor === "function") {
        try { return hexToKmlColor(hex, alpha || "ff"); } catch (_) { /* fall through */ }
      }
      return (typeof hexToKmlColorLocal === "function") ? hexToKmlColorLocal(hex, alpha || "ff") : "ff0000ff";
    };
    const header = `<?xml version="1.0" encoding="UTF-8"?>
<kml xmlns="http://www.opengis.net/kml/2.2">
  <Document>
    <name>${esc(title)}</name>`;
    const footer = `
  </Document>
</kml>`;
    let kml = header;
    kml += `\n    <Folder>\n      <name>${esc(title)}</name>\n      <description>FlightHub 2 Annotationen. Kein Live — nach Änderungen neu exportieren und in Hub 2 importieren, Ordner an Pilot 2 verteilen.</description>`;
    (items || []).forEach((it, idx) => {
      const styleId = `hub2-style-${idx + 1}`;
      const color = hub2NormColor(it.color);
      const kmlCol = toKml(color, "ff");
      const fillCol = toKml(color, "99");
      if (it.kind === "point") {
        kml += `
      <Style id="${styleId}">
        <IconStyle>
          <color>${kmlCol}</color>
          <scale>${it.hideIcon ? "0" : "1.1"}</scale>
          ${it.hideIcon ? "" : "<Icon><href>https://maps.google.com/mapfiles/kml/shapes/placemark_circle.png</href></Icon>"}
        </IconStyle>
        <LabelStyle><color>${kmlCol}</color><scale>1</scale></LabelStyle>
      </Style>
      <Placemark>
        <name>${esc(it.name)}</name>
        <styleUrl>#${styleId}</styleUrl>
        <Point><coordinates>${Number(it.lon)},${Number(it.lat)},0</coordinates></Point>
      </Placemark>`;
      } else if (it.kind === "line") {
        kml += `
      <Style id="${styleId}">
        <LineStyle><color>${kmlCol}</color><width>3</width></LineStyle>
      </Style>
      <Placemark>
        <name>${esc(it.name)}</name>
        <styleUrl>#${styleId}</styleUrl>
        <LineString><coordinates>${it.pts.map((p) => `${p[0]},${p[1]},0`).join(" ")}</coordinates></LineString>
      </Placemark>`;
      } else if (it.kind === "polygon") {
        const ringTxt = it.ring.map((p) => `${p[0]},${p[1]},0`).join(" ");
        kml += `
      <Style id="${styleId}">
        <LineStyle><color>${kmlCol}</color><width>2</width></LineStyle>
        <PolyStyle><color>${fillCol}</color><fill>1</fill><outline>1</outline></PolyStyle>
        <LabelStyle><color>${kmlCol}</color><scale>1</scale></LabelStyle>
      </Style>
      <Placemark>
        <name>${esc(it.name)}</name>
        <styleUrl>#${styleId}</styleUrl>
        <Polygon><outerBoundaryIs><LinearRing><coordinates>${ringTxt}</coordinates></LinearRing></outerBoundaryIs></Polygon>
      </Placemark>`;
      }
    });
    kml += `\n    </Folder>`;
    return kml + footer;
  }

  function hub2ItemsToOpenApiElements(items) {
    return (items || []).map((it) => {
      const color = hub2NormColor(it.color);
      const base = {
        element_source: 1,
        name: String(it.name || "Objekt").slice(0, 120),
        desc: "",
        resource: { type: 0, content: { type: "Feature", properties: { color, clampToGround: true }, geometry: {} } }
      };
      if (it.kind === "line") {
        base.resource.type = 1;
        base.resource.content.geometry = { type: "LineString", coordinates: it.pts.map((p) => [p[0], p[1]]) };
      } else if (it.kind === "polygon") {
        base.resource.type = 2;
        base.resource.content.geometry = { type: "Polygon", coordinates: [it.ring.map((p) => [p[0], p[1]])] };
      } else {
        base.resource.type = 0;
        base.resource.content.geometry = { type: "Point", coordinates: [Number(it.lon), Number(it.lat), 0] };
      }
      return base;
    });
  }

  function onHub2KmlExportClick() {
    try {
      if (!hub2UiAllowed()) return;
      try { syncEditingObjectFromUi(); } catch (_) { /* noop */ }
      const items = collectHub2AnnotationItems();
      if (!items.length) {
        setSharePanelStatus("Keine Overlay-Objekte für Hub 2. Zuerst zeichnen und fixieren.");
        return;
      }
      const kml = buildHub2Kml(items);
      const filename = stampExportFilename("hub2", "kml");
      if (typeof downloadKml === "function") {
        downloadKml(filename, kml);
      } else {
        downloadBlob(filename, new Blob([kml], { type: "application/vnd.google-earth.kml+xml;charset=utf-8" }));
      }
      setSharePanelStatus(
        `Hub 2 KML gespeichert (${items.length} Annotationen). In FlightHub 2: Annotationen → KML importieren, Ordner an Pilot 2 verteilen. Kein Live. RC: Pilot 2 im selben Projekt anmelden.`
      );
    } catch (err) {
      const msg = "Hub 2 KML fehlgeschlagen: " + ((err && err.message) ? err.message : String(err));
      setSharePanelStatus(msg);
      alert(msg);
    }
  }

  async function onHub2PushClick() {
    const btn = document.getElementById("exportHub2PushBtn");
    if (btn) btn.disabled = true;
    try {
      if (!hub2UiAllowed()) {
        setSharePanelStatus("Hub 2 nur mit URL ?ADMIN oder ?HUB.");
        return;
      }
      if (!hub2IsConfigured()) {
        setSharePanelStatus("Hub 2 nicht konfiguriert (Organization Key / Projekt-UUID auf dem Server fehlen).");
        return;
      }
      try { syncEditingObjectFromUi(); } catch (_) { /* noop */ }
      const items = collectHub2AnnotationItems();
      if (!items.length) {
        setSharePanelStatus("Keine Overlay-Objekte zum Senden. Zuerst zeichnen und fixieren.");
        return;
      }
      setSharePanelStatus(`Sende ${items.length} Annotation(en) an FlightHub 2…`);
      const res = await fetch(FLIGHTHUB_EXPORT_API, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          name: (typeof getOverlayEditorTitle === "function") ? getOverlayEditorTitle() : "Overlay",
          elements: hub2ItemsToOpenApiElements(items)
        })
      });
      const payload = await res.json().catch(() => ({}));
      if (!res.ok || payload.ok === false) {
        throw new Error((payload && payload.error) ? String(payload.error) : `HTTP ${res.status}`);
      }
      const n = Number(payload.created != null ? payload.created : items.length);
      setSharePanelStatus(
        `${n} Annotation(en) an FlightHub 2 gesendet (kein Live). In Hub 2 Ordner an Pilot 2 verteilen. RC: Pilot 2, gleiches Projekt, Annotationen-Layer.`
      );
    } catch (err) {
      const msg = "Hub 2 Senden fehlgeschlagen: " + ((err && err.message) ? err.message : String(err));
      setSharePanelStatus(msg);
      alert(msg);
    } finally {
      if (btn) btn.disabled = !hub2UiAllowed() || !hub2IsConfigured();
    }
  }

  function ensureHub2KmlButton() {
    let btn = document.getElementById("exportHub2KmlBtn");
    if (!hub2UiAllowed()) {
      if (btn && btn.parentNode) btn.parentNode.removeChild(btn);
      return null;
    }
    if (!btn) {
      btn = document.createElement("button");
      btn.id = "exportHub2KmlBtn";
      btn.type = "button";
    }
    btn.style.display = "";
    setObjToolButton(btn, "hub2", "Hub 2 KML");
    btn.title = "KML für FlightHub 2 Annotationen. Import in Hub 2, Ordner an Pilot 2 verteilen. Kein Live. Nur mit URL ?ADMIN oder ?HUB.";
    if (!btn.__rkrkHub2KmlBound) {
      btn.__rkrkHub2KmlBound = true;
      btn.addEventListener("click", (ev) => {
        ev.preventDefault();
        onHub2KmlExportClick();
      });
    }
    return btn;
  }

  function ensureHub2PushButton() {
    let btn = document.getElementById("exportHub2PushBtn");
    if (!hub2UiAllowed()) {
      if (btn && btn.parentNode) btn.parentNode.removeChild(btn);
      return null;
    }
    if (!btn) {
      btn = document.createElement("button");
      btn.id = "exportHub2PushBtn";
      btn.type = "button";
    }
    btn.style.display = "";
    setObjToolButton(btn, "hub2send", "Nach Hub 2 senden");
    const configured = hub2IsConfigured();
    btn.disabled = !configured;
    btn.title = configured
      ? "Einmaliger Push der Annotationen nach FlightHub 2 (kein Live). Danach Ordner an Pilot 2 verteilen. Nur mit URL ?ADMIN oder ?HUB."
      : "Deaktiviert: Organization Key und Projekt-UUID fehlen auf dem Server (map/flighthub.local.php oder Umgebungsvariablen).";
    if (!btn.__rkrkHub2PushBound) {
      btn.__rkrkHub2PushBound = true;
      btn.addEventListener("click", (ev) => {
        ev.preventDefault();
        onHub2PushClick();
      });
    }
    return btn;
  }

  function installSwissMapButton() {
    ensureSharePanelSection();
    const exportBtn = document.getElementById("exportBtn");
    if (!exportBtn || document.getElementById("openSwissMapBtn")) {
      try { moveExportButtonsToShareHost(); } catch (_) {}
      return;
    }
    setObjToolButton(exportBtn, "kml", "KML");
    exportBtn.title = "Karte als KML herunterladen";
    const btn = document.createElement("button");
    btn.id = "openSwissMapBtn";
    btn.type = "button";
    setObjToolButton(btn, "map", "SwissTopo");
    btn.title = "In map.geo.admin.ch (SwissTopo) öffnen";
    const hint = document.createElement("p");
    hint.className = "small";
    hint.id = "swissMapHint";
    hint.style.flex = "1 1 100%";
    const actionHost = document.getElementById("shareActionHost") || document.getElementById("objectActionHost");
    if (actionHost) {
      actionHost.appendChild(btn);
      actionHost.appendChild(hint);
    } else {
      exportBtn.insertAdjacentElement("afterend", btn);
      btn.insertAdjacentElement("afterend", hint);
    }
    btn.addEventListener("click", async () => {
      // Fenster SOFORT öffnen (User-Geste) — sonst blockiert der Browser nach await.
      let viewerWin = null;
      try {
        viewerWin = window.open("about:blank", "_blank");
        if (viewerWin) {
          try {
            viewerWin.document.write("<!doctype html><title>SwissTopo…</title><body style='font-family:sans-serif;padding:1.5rem'>KML wird erstellt und map.geo.admin.ch geöffnet…</body>");
            viewerWin.document.close();
          } catch (_) { /* noop */ }
        }
      } catch (_) { viewerWin = null; }
      btn.disabled = true;
      hint.textContent = "Erstelle und speichere KML...";
      try { setSharePanelStatus(hint.textContent); } catch (_) {}
      try {
        syncEditingObjectFromUi();
        try { flushGridCellFillsForShare(); } catch (_) { /* noop */ }
        // SwissTopo: ohne Terrain-Fetch (schnell, weniger Fehlerquellen auf tools.*)
        const elevationMap = new Map();
        const lon = Array.isArray(selectedLonLat) && selectedLonLat.length === 2 ? selectedLonLat[0] : null;
        const lat = Array.isArray(selectedLonLat) && selectedLonLat.length === 2 ? selectedLonLat[1] : null;
        const kml = buildCompleteMapExportKml(elevationMap);
        if (!kml) {
          throw new Error("Kein exportierbares Objekt vorhanden (Raster, Kreise, Punkte/Texte oder Polygon).");
        }
        const saved = await saveKmlOnServer("rkrk_obj_alle_objekte_mapgeo.kml", kml);
        const viewCenter = getMapViewCenterLonLat();
        let centerLon = viewCenter ? Number(viewCenter.lon) : NaN;
        let centerLat = viewCenter ? Number(viewCenter.lat) : NaN;
        let geoZ = viewCenter ? Number(viewCenter.z) : 11;
        if (!isLikelyWgs84LonLat(centerLon, centerLat)) {
          centerLon = Number(lon);
          centerLat = Number(lat);
        }
        if (!isLikelyWgs84LonLat(centerLon, centerLat)) {
          if (Array.isArray(labeledPoints) && labeledPoints.length > 0) {
            centerLon = Number(labeledPoints[0].lon);
            centerLat = Number(labeledPoints[0].lat);
          } else if (Array.isArray(polygonVertices) && polygonVertices.length > 0) {
            centerLon = Number(polygonVertices[0][0]);
            centerLat = Number(polygonVertices[0][1]);
          } else if (Array.isArray(managedObjects) && managedObjects.length > 0) {
            for (let i = 0; i < managedObjects.length; i += 1) {
              const first = managedObjects[i];
              if (first && Array.isArray(first.anchor) && first.anchor.length === 2) {
                centerLon = Number(first.anchor[0]);
                centerLat = Number(first.anchor[1]);
                break;
              }
              if (first && Array.isArray(first.polygonVertices) && first.polygonVertices.length > 0) {
                centerLon = Number(first.polygonVertices[0][0]);
                centerLat = Number(first.polygonVertices[0][1]);
                break;
              }
              if (first && Array.isArray(first.labels) && first.labels.length > 0) {
                centerLon = Number(first.labels[0].lon);
                centerLat = Number(first.labels[0].lat);
                break;
              }
              if (first && Array.isArray(first.coordinates) && first.coordinates.length > 0) {
                centerLon = Number(first.coordinates[0][0]);
                centerLat = Number(first.coordinates[0][1]);
                break;
              }
            }
          } else if (Array.isArray(freehandDraftCoords) && freehandDraftCoords.length > 0) {
            centerLon = Number(freehandDraftCoords[0][0]);
            centerLat = Number(freehandDraftCoords[0][1]);
          }
        }
        if (!Number.isFinite(centerLon) || !Number.isFinite(centerLat)) {
          throw new Error("Kein Kartenzentrum verfügbar.");
        }
        const { e, n } = await wgs84LonLatToLv95(centerLon, centerLat);
        const kmlPublicUrl = String(saved.public_url || saved.local_url || "");
        if (!kmlPublicUrl || !/^https:\/\//i.test(kmlPublicUrl)) {
          throw new Error("Keine HTTPS-KML-URL für map.geo.admin.ch verfügbar.");
        }
        if (!Number.isFinite(geoZ)) geoZ = 11;
        const viewerUrl = buildGeoAdminMapViewerUrl(kmlPublicUrl, e, n, geoZ);
        let opened = false;
        if (viewerWin && !viewerWin.closed) {
          try { viewerWin.location.href = viewerUrl; opened = true; } catch (_) { opened = false; }
        }
        if (!opened) {
          const w2 = window.open(viewerUrl, "_blank", "noopener,noreferrer");
          opened = !!w2;
        }
        const hostHint = (saved.hosting === "geoadmin")
          ? "KML auf public.geo.admin.ch hinterlegt und Karte geöffnet."
          : "KML lokal bereitgestellt und map.geo.admin.ch geöffnet.";
        if (opened) {
          hint.textContent = hostHint;
        } else {
          hint.innerHTML = hostHint + " Popup blockiert — <a href=\"" + viewerUrl.replace(/"/g, "&quot;") + "\" target=\"_blank\" rel=\"noopener\">map.geo.admin.ch öffnen</a>";
        }
        try { setSharePanelStatus(hint.textContent); } catch (_) {}
      } catch (err) {
        const msg = `Fehler: ${(err && err.message) ? err.message : String(err)}`;
        hint.textContent = msg;
        try {
          if (viewerWin && !viewerWin.closed) {
            viewerWin.document.write("<!doctype html><title>Fehler</title><body style='font-family:sans-serif;padding:1.5rem;color:#b91c1c'>" + msg.replace(/</g, "&lt;") + "</body>");
            viewerWin.document.close();
          }
        } catch (_) { /* noop */ }
      } finally {
        btn.disabled = false;
      }
    });
  }
  /**
   * Compose OpenLayers layer canvases into one bitmap of the current map view.
   * Requires tile/WMS sources with crossOrigin (already set for swisstopo).
   */

  function getMapInlayImageSrc() {
    const brand = window.__RKR_BRAND__ || {};
    if (brand.mapInlaySrc) return brand.mapInlaySrc;
    const asset = typeof window.rkrBrandAsset === "function" ? window.rkrBrandAsset : null;
    if (brand.isRkrs || window.__ON_RKRS__) {
      return asset ? asset("RKRS Logo rund.png") : "images/RKRS%20Logo%20rund.png";
    }
    return asset ? asset("RKRK Logo grey.png") : "images/RKRK%20Logo%20grey.png";
  }

  function loadImageForPdf(src) {
    return new Promise((resolve, reject) => {
      if (!src) {
        reject(new Error("Inlay-Logo: leerer Pfad"));
        return;
      }
      const img = new Image();
      img.decoding = "async";
      img.onload = () => resolve(img);
      img.onerror = () => reject(new Error("Inlay-Logo nicht ladbar: " + src));
      img.src = src + (src.indexOf("?") >= 0 ? "&" : "?") + "v=20260722r";
    });
  }

  async function resolvePdfBrandImage() {
    const ready = (el) => el && el.tagName === "IMG" && el.complete && el.naturalWidth > 0;
    const fromDom = [document.getElementById("rkrMapInlayImg"), document.getElementById("rkrBrandLogo")]
      .find(ready);
    if (fromDom) return fromDom;
    const brand = window.__RKR_BRAND__ || {};
    const candidates = [];
    if (brand.mapInlaySrc) candidates.push(brand.mapInlaySrc);
    if (brand.logoSrc) candidates.push(brand.logoSrc);
    candidates.push(getMapInlayImageSrc());
    const seen = {};
    for (let i = 0; i < candidates.length; i += 1) {
      const src = String(candidates[i] || "").trim();
      if (!src || seen[src]) continue;
      seen[src] = true;
      try {
        return await loadImageForPdf(src);
      } catch (_) { /* try next */ }
    }
    return null;
  }

  const PROJECT_TITLE_KEY = "rkrk_project_title_v1";
  let projectTitleOverride = null;

  function getDefaultOverlayEditorTitle() {
    if (typeof window.rkrOverlayEditorTitle === "function") {
      return window.rkrOverlayEditorTitle();
    }
    const brand = window.__RKR_BRAND__ || {};
    if (brand.heading) return String(brand.heading);
    if (brand.title) return String(brand.title);
    const isRkrs = !!(brand.isRkrs || window.__ON_RKRS__ || (typeof ON_RKRS !== "undefined" && ON_RKRS));
    return isRkrs ? "RKRS Overlay-Editor" : "RKRK Overlay-Editor";
  }

  function loadPersistedProjectTitle() {
    try {
      const t = String(localStorage.getItem(PROJECT_TITLE_KEY) || "").trim();
      if (t) return t.slice(0, 120);
    } catch (_) { /* noop */ }
    return "";
  }

  function getOverlayEditorTitle() {
    if (projectTitleOverride && String(projectTitleOverride).trim()) {
      return String(projectTitleOverride).trim().slice(0, 120);
    }
    const persisted = loadPersistedProjectTitle();
    if (persisted) return persisted;
    return getDefaultOverlayEditorTitle();
  }

  function escapeXmlText(s) {
    return String(s || "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  function getProjectTitleHeadingEl() {
    return document.querySelector("#rkrBrandHeader h1")
      || document.querySelector("aside.panel .rkr-brand-header h1")
      || document.querySelector("aside.panel h1");
  }

  /** Title overlay top-left inside the map (edit + broadcast viewer). */
  function ensureMapProjectTitleOverlay() {
    const mapEl = document.getElementById("map");
    if (!mapEl) return null;
    let el = document.getElementById("rkrkProjectTitleOverlay");
    if (!el) {
      el = document.createElement("div");
      el.id = "rkrkProjectTitleOverlay";
    }
    if (el.parentNode !== mapEl) mapEl.appendChild(el);
    let style = document.getElementById("rkrkProjectTitleCss");
    if (!style) {
      style = document.createElement("style");
      style.id = "rkrkProjectTitleCss";
      document.head.appendChild(style);
    }
    style.textContent = ""
      + "#map{position:relative!important;}"
      + "#rkrkProjectTitleOverlay{display:block!important;position:absolute!important;"
      + "top:12px!important;left:52px!important;right:auto!important;bottom:auto!important;"
      + "z-index:25!important;max-width:min(70%,420px);padding:6px 10px;border-radius:8px;"
      + "background:rgba(255,255,255,.92);border:1px solid rgba(0,0,0,.18);"
      + "box-shadow:0 2px 10px rgba(0,0,0,.12);color:#111827;font:600 13px/1.25 Arial,Helvetica,sans-serif;"
      + "pointer-events:auto;cursor:text;text-align:left;user-select:none;"
      + "overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}"
      + "#rkrkProjectTitleOverlay:hover{outline:1px dashed #96B588;outline-offset:2px;white-space:normal;overflow:visible;}"
      + "body.rkrk-share-viewer #rkrkProjectTitleOverlay{pointer-events:none;cursor:default;}"
      + "body.rkrk-share-viewer #rkrkProjectTitleOverlay:hover{outline:none;white-space:nowrap;overflow:hidden;}";
    el.textContent = getOverlayEditorTitle();
    el.title = document.body.classList.contains("rkrk-share-viewer")
      ? ""
      : "Doppelklick: Titel \u00e4ndern (PDF, KML, map.geo.admin.ch)";
    return el;
  }

  function promptEditProjectTitle() {
    const cur = getOverlayEditorTitle();
    const next = window.prompt("Kartentitel (PDF, KML, map.geo.admin.ch):", cur);
    if (next === null) return;
    setProjectTitle(next);
    if (typeof managedStatusText === "function") {
      managedStatusText(`Projekttitel: \u00ab${getOverlayEditorTitle()}\u00bb`);
    }
  }

  function applyProjectTitleToUi() {
    const product = getDefaultOverlayEditorTitle();
    const title = getOverlayEditorTitle();
    // Menue: Produktname je Laufort. Projekttitel nur auf der Karte.
    const h1 = getProjectTitleHeadingEl();
    if (h1) {
      h1.textContent = product;
      h1.removeAttribute("title");
      h1.style.cursor = "";
    }
    const wrap = document.getElementById("rkrBrandHeader");
    if (wrap) {
      wrap.removeAttribute("title");
      wrap.style.cursor = "";
    }
    try {
      document.title = (title && title !== product) ? (title + " \u00b7 " + product) : product;
    } catch (_) { /* noop */ }
    ensureMapProjectTitleOverlay();
  }

  function setProjectTitle(next) {
    const cleaned = String(next || "").trim().slice(0, 120);
    if (!cleaned) {
      projectTitleOverride = null;
      try { localStorage.removeItem(PROJECT_TITLE_KEY); } catch (_) { /* noop */ }
    } else {
      projectTitleOverride = cleaned;
      try { localStorage.setItem(PROJECT_TITLE_KEY, cleaned); } catch (_) { /* noop */ }
    }
    applyProjectTitleToUi();
    if (typeof scheduleSessionDraftSave === "function") scheduleSessionDraftSave();
    if (typeof notifyLiveShareChange === "function") notifyLiveShareChange();
    else if (typeof scheduleLiveSharePush === "function") scheduleLiveSharePush();
  }

  function bindMapProjectTitleDblClick(el) {
    if (!el || el.dataset.projectTitleInstalled === "1") return;
    el.dataset.projectTitleInstalled = "1";
    el.style.cursor = "pointer";
    el.addEventListener("dblclick", (ev) => {
      if (document.body.classList.contains("rkrk-share-viewer")) return;
      ev.preventDefault();
      ev.stopPropagation();
      promptEditProjectTitle();
    });
  }

  function installProjectTitleEditor() {
    const persisted = loadPersistedProjectTitle();
    if (persisted) projectTitleOverride = persisted;
    applyProjectTitleToUi();
    bindMapProjectTitleDblClick(ensureMapProjectTitleOverlay());
    // Map ggf. spaeter bereit — Overlay nachziehen
    let tries = 0;
    const timer = setInterval(() => {
      tries += 1;
      const el = ensureMapProjectTitleOverlay();
      if (el) bindMapProjectTitleDblClick(el);
      if (el || tries > 40) clearInterval(timer);
    }, 120);
  }

  function buildTitleOverlayIconUrl(title) {
    try {
      const u = new URL("title_overlay_svg.php", window.location.href);
      u.searchParams.set("t", String(title || getOverlayEditorTitle() || "Titel"));
      return u.toString();
    } catch (_) {
      return `title_overlay_svg.php?t=${encodeURIComponent(String(title || "Titel"))}`;
    }
  }

  function getMapViewNwLonLatInset() {
    try {
      if (typeof map === "undefined" || !map || !map.getView) return null;
      if (typeof ol === "undefined" || !ol.proj || typeof ol.proj.transform !== "function") return null;
      const view = map.getView();
      const size = (typeof map.getSize === "function") ? map.getSize() : null;
      if (!Array.isArray(size) || size.length < 2 || !(size[0] > 0) || !(size[1] > 0)) return null;
      // ~3% inset from top-left of the current viewport
      const px = [Math.max(8, size[0] * 0.03), Math.max(8, size[1] * 0.03)];
      const coord = map.getCoordinateFromPixel(px);
      if (!Array.isArray(coord) || coord.length < 2) return null;
      const proj = view.getProjection ? view.getProjection() : "EPSG:3857";
      const ll = ol.proj.transform([Number(coord[0]), Number(coord[1])], proj, "EPSG:4326");
      const lon = Number(ll[0]);
      const lat = Number(ll[1]);
      if (!Number.isFinite(lon) || !Number.isFinite(lat)) return null;
      if (Math.abs(lon) > 180 || Math.abs(lat) > 90) return null;
      return { lon, lat };
    } catch (_) {
      return null;
    }
  }

  function buildProjectTitleKmlExtras() {
    const title = getOverlayEditorTitle();
    const name = escapeXmlText(title);
    // Kein ScreenOverlay mit tools.rehkitzrettung.ch-URL:
    // map.geo.admin.ch laedt externe Icons nur mit CORS — sonst wirken KML-Farben/Styles kaputt.
    let extra = `
    <Folder id="rkrk-project-title">
      <name>${name}</name>`;
    const nw = getMapViewNwLonLatInset();
    if (nw) {
      extra += `
      <Placemark>
        <name>${name}</name>
        <Style>
          <IconStyle><scale>0</scale></IconStyle>
          <LabelStyle><color>ff111827</color><scale>1.05</scale></LabelStyle>
        </Style>
        <Point><coordinates>${nw.lon.toFixed(7)},${nw.lat.toFixed(7)},0</coordinates></Point>
      </Placemark>`;
    }
    extra += `
    </Folder>`;
    return extra;
  }

  function patchKmlHeaderForProjectTitle() {
    if (typeof kmlHeader !== "function") return false;
    if (kmlHeader.__projectTitlePatched) return true;
    const original = kmlHeader;
    kmlHeader = function (name) {
      const title = getOverlayEditorTitle() || name || "export";
      return original(escapeXmlText(title)) + buildProjectTitleKmlExtras();
    };
    kmlHeader.__projectTitlePatched = true;
    try { window.kmlHeader = kmlHeader; } catch (_) { /* noop */ }
    return true;
  }

  const MAP_PDF_MARGIN_MM = 8;
  const MAP_PDF_PAGE = { L: { w: 297, h: 210 }, P: { w: 210, h: 297 } };
  const MAP_SCREEN_PX_MM = 0.28; // OGC-Standardpixel für Bildschirm-Massstab
  // Papier-Kalibrierung: 1 km-Raster mass 9.55–9.6 cm statt 10 cm (1:10'000)
  // bzw. 3.85–3.9 cm statt 4 cm (1:25'000) — typisch «An Seite anpassen».
  const MAP_PDF_SCALE_GAIN = 1.044;

  function formatSwissScaleDenom(denom) {
    const n = Math.max(1, Math.round(Number(denom) || 1));
    const s = String(n);
    let out = "";
    for (let i = 0; i < s.length; i += 1) {
      if (i && ((s.length - i) % 3 === 0)) out += "'";
      out += s.charAt(i);
    }
    return "1:" + out;
  }

  function formatScaleBarDistance(meters) {
    const m = Number(meters);
    if (!(m > 0)) return "0 m";
    if (m >= 1000 && Math.abs(m / 1000 - Math.round(m / 1000)) < 0.05) {
      return String(Math.round(m / 1000)) + " km";
    }
    if (m >= 1000) return (m / 1000).toFixed(1).replace(".", ",") + " km";
    return String(Math.round(m)) + " m";
  }

  function niceScaleBarMeters(targetM) {
    if (!(targetM > 0) || !Number.isFinite(targetM)) return 100;
    const mag = Math.pow(10, Math.floor(Math.log10(targetM)));
    const n = targetM / mag;
    const nice = n < 1.5 ? 1 : n < 3.5 ? 2 : n < 7.5 ? 5 : 10;
    return nice * mag;
  }

  function roundDisplayScaleDenom(raw) {
    const n = Number(raw);
    if (!(n > 0) || !Number.isFinite(n)) return 0;
    const standards = [10000, 25000, 50000];
    for (let i = 0; i < standards.length; i += 1) {
      const s = standards[i];
      if (Math.abs(n - s) / s < 0.03) return s;
    }
    if (n >= 20000) return Math.round(n / 500) * 500;
    if (n >= 5000) return Math.round(n / 100) * 100;
    return Math.max(1, Math.round(n / 50) * 50);
  }

  function parseScaleDenomInput(raw) {
    let s = String(raw == null ? "" : raw).trim();
    if (!s) return 0;
    s = s.replace(/\s+/g, "").replace(/[''`´’]/g, "");
    s = s.replace(/^1:/i, "");
    s = s.replace(/,/g, ".");
    const n = Math.round(Number(s));
    if (!Number.isFinite(n) || n < 500 || n > 2000000) return 0;
    return n;
  }

  function selectedPdfScaleDenom() {
    const sel = document.getElementById("mapPdfScaleSelect");
    return parseScaleDenomInput(sel && sel.value);
  }

  function setPdfScaleSelectValue(denom) {
    const sel = ensurePdfScaleSelect();
    if (!sel) return;
    const n = parseScaleDenomInput(denom);
    if (!(n > 0)) {
      sel.value = "0";
      try { updatePrintExtentPreview(); } catch (_) { /* noop */ }
      return;
    }
    const v = String(n);
    if (!sel.querySelector('option[value="' + v + '"]')) {
      const opt = document.createElement("option");
      opt.value = v;
      opt.textContent = formatSwissScaleDenom(n);
      sel.appendChild(opt);
    }
    sel.value = v;
    try { updatePrintExtentPreview(); } catch (_) { /* noop */ }
  }

  function applyViewForScreenScale(scaleDenom) {
    const denom = Number(scaleDenom);
    if (!(denom > 0) || !Number.isFinite(denom)) return false;
    if (typeof map === "undefined" || !map || typeof map.getView !== "function") return false;
    if (typeof ol === "undefined" || !ol.proj || typeof ol.proj.getPointResolution !== "function") return false;
    try {
      const view = map.getView();
      const center = view.getCenter();
      if (!Array.isArray(center)) return false;
      const proj = view.getProjection ? view.getProjection() : "EPSG:3857";
      const mPerUnit = Number(ol.proj.getPointResolution(proj, 1, center));
      if (!(mPerUnit > 0) || !Number.isFinite(mPerUnit)) return false;
      const mPerPx = denom * (MAP_SCREEN_PX_MM / 1000);
      const nextRes = mPerPx / mPerUnit;
      if (!(nextRes > 0) || !Number.isFinite(nextRes)) return false;
      if (typeof view.setMinZoom === "function") view.setMinZoom(0);
      if (typeof view.setMaxZoom === "function") view.setMaxZoom(28);
      view.setResolution(nextRes);
      try { map.render(); } catch (_) { /* noop */ }
      return true;
    } catch (_) {
      return false;
    }
  }

  function promptEditMapScale() {
    if (document.body.classList.contains("rkrk-share-viewer")) return;
    if (document.body.classList.contains("rkrk-share-sub")) return;
    const mPerPx = getMapMetersPerPixel();
    const current = (mPerPx > 0) ? roundDisplayScaleDenom(mPerPx / (MAP_SCREEN_PX_MM / 1000)) : 25000;
    const next = window.prompt("Massstab eingeben (z.B. 1:25'000 oder 25000):", formatSwissScaleDenom(current));
    if (next === null) return;
    const denom = parseScaleDenomInput(next);
    if (!(denom > 0)) {
      window.alert("Ungültiger Massstab. Beispiel: 1:25'000 (erlaubt 1:500 bis 1:2'000'000).");
      return;
    }
    const ok = applyViewForScreenScale(denom);
    if (!ok) {
      window.alert("Massstab konnte nicht gesetzt werden.");
      return;
    }
    try { setPdfScaleSelectValue(denom); } catch (_) { /* noop */ }
    try { updateMapScaleBar(); } catch (_) { /* noop */ }
  }

  function wgs84ToLv95Approx(lon, lat) {
    const lonN = Number(lon);
    const latN = Number(lat);
    if (!Number.isFinite(lonN) || !Number.isFinite(latN)) return null;
    if (Math.abs(lonN) > 180 || Math.abs(latN) > 90) return null;
    const phi = ((latN * 3600) - 169028.66) / 10000;
    const lam = ((lonN * 3600) - 26782.5) / 10000;
    const east = 2600072.37
      + 211455.93 * lam
      - 10938.51 * lam * phi
      - 0.36 * lam * phi * phi
      - 44.54 * lam * lam * lam;
    const north = 1200147.07
      + 308807.95 * phi
      + 3745.25 * lam * lam
      + 76.63 * phi * phi
      - 194.56 * lam * lam * phi
      + 119.79 * phi * phi * phi;
    if (!Number.isFinite(east) || !Number.isFinite(north)) return null;
    return [east, north];
  }

  function getMapLv95SpanMeters(horizontal) {
    try {
      if (typeof map === "undefined" || !map || typeof map.getSize !== "function") return 0;
      if (typeof ol === "undefined" || !ol.proj) return 0;
      const size = map.getSize();
      if (!Array.isArray(size) || !(size[0] > 0) || !(size[1] > 0)) return 0;
      const aPx = horizontal ? [0, size[1] / 2] : [size[0] / 2, 0];
      const bPx = horizontal ? [size[0], size[1] / 2] : [size[0] / 2, size[1]];
      const a = map.getCoordinateFromPixel(aPx);
      const b = map.getCoordinateFromPixel(bPx);
      if (!Array.isArray(a) || !Array.isArray(b)) return 0;
      const proj = map.getView().getProjection ? map.getView().getProjection() : "EPSG:3857";
      const llA = ol.proj.toLonLat(a, proj);
      const llB = ol.proj.toLonLat(b, proj);
      if (!llA || !llB) return 0;
      const A = wgs84ToLv95Approx(llA[0], llA[1]);
      const B = wgs84ToLv95Approx(llB[0], llB[1]);
      if (!A || !B) return 0;
      const d = Math.hypot(B[0] - A[0], B[1] - A[1]);
      return (Number.isFinite(d) && d > 0 && d < 2e7) ? d : 0;
    } catch (_) {
      return 0;
    }
  }

  function getMapMetersPerPixel() {
    try {
      if (typeof map === "undefined" || !map || typeof map.getView !== "function") return 0;
      const size = (typeof map.getSize === "function") ? map.getSize() : null;
      const span = getMapLv95SpanMeters(true);
      if (span > 0 && Array.isArray(size) && size[0] > 0) return span / size[0];
      if (typeof ol === "undefined" || !ol.proj) return 0;
      const view = map.getView();
      const center = view.getCenter();
      const res = Number(view.getResolution());
      if (!Array.isArray(center) || !(res > 0)) return 0;
      const proj = view.getProjection ? view.getProjection() : "EPSG:3857";
      if (typeof ol.proj.getPointResolution === "function") {
        const pr = Number(ol.proj.getPointResolution(proj, res, center));
        return pr > 0 ? pr : 0;
      }
      const ll = ol.proj.toLonLat(center, proj);
      const lat = Number(ll && ll[1]);
      if (!Number.isFinite(lat)) return res;
      return res * Math.cos(lat * Math.PI / 180);
    } catch (_) {
      return 0;
    }
  }

  function choosePdfPaperMm(canvasW, canvasH) {
    const landscape = Number(canvasW) >= Number(canvasH);
    const page = landscape ? MAP_PDF_PAGE.L : MAP_PDF_PAGE.P;
    const maxW = page.w - 2 * MAP_PDF_MARGIN_MM;
    const maxH = page.h - 2 * MAP_PDF_MARGIN_MM;
    const ar = Number(canvasW) / Math.max(1, Number(canvasH));
    let w = maxW;
    let h = w / ar;
    if (h > maxH) {
      h = maxH;
      w = h * ar;
    }
    return {
      orient: landscape ? "L" : "P",
      pageWmm: page.w,
      pageHmm: page.h,
      imgWmm: w,
      imgHmm: h
    };
  }

  function snapshotMapView() {
    if (typeof map === "undefined" || !map || typeof map.getView !== "function") return null;
    const view = map.getView();
    const center = view.getCenter();
    return {
      center: Array.isArray(center) ? center.slice() : null,
      resolution: Number(view.getResolution()),
      rotation: Number(view.getRotation()) || 0,
      minZoom: (typeof view.getMinZoom === "function") ? Number(view.getMinZoom()) : NaN,
      maxZoom: (typeof view.getMaxZoom === "function") ? Number(view.getMaxZoom()) : NaN
    };
  }

  function restoreMapView(snap) {
    if (!snap || typeof map === "undefined" || !map || typeof map.getView !== "function") return;
    const view = map.getView();
    try {
      if (Number.isFinite(snap.minZoom) && typeof view.setMinZoom === "function") view.setMinZoom(snap.minZoom);
      if (Number.isFinite(snap.maxZoom) && typeof view.setMaxZoom === "function") view.setMaxZoom(snap.maxZoom);
      if (Array.isArray(snap.center)) view.setCenter(snap.center);
      if (Number.isFinite(snap.resolution) && snap.resolution > 0) view.setResolution(snap.resolution);
      if (Number.isFinite(snap.rotation)) view.setRotation(snap.rotation);
    } catch (_) { /* noop */ }
  }

  function applyViewForPaperScale(scaleDenom, imgWmm, mapWpx) {
    const denom = Number(scaleDenom);
    const paperMm = Number(imgWmm);
    const px = Number(mapWpx);
    if (!(denom > 0) || !(paperMm > 0) || !(px > 0)) return false;
    if (typeof map === "undefined" || !map || typeof map.getView !== "function") return false;
    if (typeof ol === "undefined" || !ol.proj || typeof ol.proj.getPointResolution !== "function") return false;
    try {
      const view = map.getView();
      const center = view.getCenter();
      if (!Array.isArray(center)) return false;
      const proj = view.getProjection ? view.getProjection() : "EPSG:3857";
      const targetGroundM = (paperMm * denom / 1000) / MAP_PDF_SCALE_GAIN;
      const mPerUnit = Number(ol.proj.getPointResolution(proj, 1, center));
      if (!(mPerUnit > 0) || !Number.isFinite(mPerUnit) || !(targetGroundM > 0)) return false;
      const nextRes = (targetGroundM / px) / mPerUnit;
      if (!(nextRes > 0) || !Number.isFinite(nextRes)) return false;
      // 1:50'000 zooms OUT — do not clamp to a tight maxResolution
      if (typeof view.setMinZoom === "function") view.setMinZoom(0);
      if (typeof view.setMaxZoom === "function") view.setMaxZoom(28);
      view.setResolution(nextRes);
      try { map.render(); } catch (_) { /* noop */ }
      return true;
    } catch (_) {
      return false;
    }
  }

  function getMapScaleStampInfo(canvasW, canvasH) {
    const w = Number(canvasW);
    const h = Number(canvasH);
    if (!(w > 0) || !(h > 0)) return null;
    const paper = choosePdfPaperMm(w, h);
    const spanM = getMapLv95SpanMeters(true);
    const mPerPx = (spanM > 0) ? (spanM / w) : getMapMetersPerPixel();
    if (!(mPerPx > 0)) return null;
    const computed = (mPerPx * w) / (paper.imgWmm / 1000);
    const selected = selectedPdfScaleDenom();
    let denom = selected || roundDisplayScaleDenom(computed);
    if (selected && computed > 0 && Math.abs(computed - selected) / selected > 0.08) {
      denom = roundDisplayScaleDenom(computed);
    }
    const barMm = 40;
    const targetGround = barMm * denom / 1000;
    const niceM = niceScaleBarMeters(targetGround);
    const niceMm = (denom > 0) ? (niceM * 1000 / denom) : 0;
    const nicePxFromPaper = (niceMm > 0) ? (niceMm * w / paper.imgWmm) : 0;
    const nicePxFromGround = niceM / mPerPx;
    const nicePx = (nicePxFromGround > 8) ? nicePxFromGround : nicePxFromPaper;
    return {
      denom,
      mPerPx,
      niceM,
      nicePx,
      paper,
      selected: !!selected
    };
  }

  function stampMapScaleOnCanvas(canvas, ctx, margin, cornerR) {
    if (!canvas || !ctx) return;
    const info = getMapScaleStampInfo(canvas.width, canvas.height);
    if (!info || !(info.nicePx > 8)) return;
    const fontSize = Math.max(11, Math.round(Math.min(canvas.width, canvas.height) * 0.016));
    const padX = Math.max(8, Math.round(fontSize * 0.7));
    const padY = Math.max(6, Math.round(fontSize * 0.45));
    const barH = Math.max(7, Math.round(fontSize * 0.7));
    const maxBarPx = Math.min(info.nicePx, canvas.width * 0.42);
    const label = "Massstab " + formatSwissScaleDenom(info.denom);
    ctx.save();
    ctx.setTransform(1, 0, 0, 1, 0, 0);
    ctx.font = "600 " + fontSize + "px Arial, Helvetica, sans-serif";
    const labelW = Math.ceil(ctx.measureText(label).width);
    const dist = formatScaleBarDistance(info.niceM);
    ctx.font = "500 " + Math.max(10, fontSize - 1) + "px Arial, Helvetica, sans-serif";
    const zeroW = Math.ceil(ctx.measureText("0").width);
    const distW = Math.ceil(ctx.measureText(dist).width);
    const barW = Math.max(maxBarPx, zeroW + distW + 16);
    const boxW = Math.max(labelW, barW) + padX * 2;
    const boxH = padY + fontSize + 4 + barH + 4 + Math.max(10, fontSize - 1) + padY;
    const x = margin;
    const y = canvas.height - margin - boxH;
    ctx.fillStyle = "rgba(255,255,255,0.94)";
    ctx.strokeStyle = "rgba(0,0,0,0.28)";
    ctx.lineWidth = Math.max(1, Math.round(fontSize * 0.08));
    ctx.beginPath();
    if (typeof ctx.roundRect === "function") ctx.roundRect(x, y, boxW, boxH, cornerR || 5);
    else ctx.rect(x, y, boxW, boxH);
    ctx.fill();
    ctx.stroke();
    ctx.fillStyle = "#111827";
    ctx.textAlign = "left";
    ctx.textBaseline = "top";
    ctx.font = "600 " + fontSize + "px Arial, Helvetica, sans-serif";
    ctx.fillText(label, x + padX, y + padY);
    const barX = x + padX;
    const barY = y + padY + fontSize + 4;
    const segs = 4;
    const segW = barW / segs;
    ctx.strokeStyle = "#111827";
    ctx.lineWidth = 1;
    for (let i = 0; i < segs; i += 1) {
      ctx.fillStyle = (i % 2 === 0) ? "#111827" : "#ffffff";
      ctx.fillRect(barX + i * segW, barY, segW, barH);
    }
    ctx.strokeRect(barX, barY, barW, barH);
    ctx.fillStyle = "#111827";
    ctx.font = "500 " + Math.max(10, fontSize - 1) + "px Arial, Helvetica, sans-serif";
    ctx.textBaseline = "top";
    ctx.textAlign = "left";
    ctx.fillText("0", barX, barY + barH + 3);
    ctx.textAlign = "right";
    ctx.fillText(dist, barX + barW, barY + barH + 3);
    ctx.restore();
  }

  function ensureMapScaleBarCss() {
    let style = document.getElementById("rkrkMapScaleBarCss");
    if (!style) {
      style = document.createElement("style");
      style.id = "rkrkMapScaleBarCss";
      document.head.appendChild(style);
    }
    style.textContent = ""
      + "#rkrkMapScaleBar{display:block;position:absolute;left:10px;bottom:10px;z-index:24;"
      + "padding:5px 8px 4px;border-radius:6px;background:rgba(255,255,255,.92);"
      + "border:1px solid rgba(0,0,0,.28);color:#111827;"
      + "font:600 12px/1.2 Arial,Helvetica,sans-serif;pointer-events:auto;cursor:pointer;"
      + "box-shadow:0 1px 6px rgba(0,0,0,.16);-webkit-print-color-adjust:exact;print-color-adjust:exact;"
      + "user-select:none;}"
      + "#rkrkMapScaleBar:hover{outline:1px dashed #96B588;outline-offset:2px;}"
      + "#rkrkMapScaleBar .rkrk-scale-ratio{display:block;margin:0 0 3px;white-space:nowrap;}"
      + "#rkrkMapScaleBar .rkrk-scale-track{display:flex;height:8px;border:1px solid #111827;width:100%;box-sizing:border-box;}"
      + "#rkrkMapScaleBar .rkrk-scale-track span{flex:1 1 0;background:#111827;}"
      + "#rkrkMapScaleBar .rkrk-scale-track span:nth-child(even){background:#fff;}"
      + "#rkrkMapScaleBar .rkrk-scale-caps{display:flex;justify-content:space-between;margin-top:2px;"
      + "font:500 10px/1.1 Arial,Helvetica,sans-serif;color:#111827;}"
      + "@media print{#rkrkMapScaleBar{display:block!important;left:8px!important;bottom:8px!important;pointer-events:none!important;outline:none!important;}}"
      + "body.rkrk-share-viewer #rkrkMapScaleBar{bottom:42px;pointer-events:none;cursor:default;}"
      + "body.rkrk-share-viewer #rkrkMapScaleBar:hover{outline:none;}"
      + "#rkrkPrintExtentFrame{display:block;position:absolute;z-index:22;pointer-events:none;box-sizing:border-box;"
      + "border:2px solid #dc2626;background:rgba(220,38,38,0.07);"
      + "box-shadow:0 0 0 1px rgba(255,255,255,.75) inset;}"
      + "#rkrkPrintExtentFrame .rkrk-print-frame-cap{position:absolute;left:-2px;top:-2px;padding:2px 8px;"
      + "background:#dc2626;color:#fff;font:600 11px/1.3 Arial,Helvetica,sans-serif;white-space:nowrap;}"
      + "#rkrkPrintExtentFrame.is-overflow{border-style:dashed;}"
      + "@media print{#rkrkPrintExtentFrame{display:none!important;}}"
      + "body.rkrk-share-viewer #rkrkPrintExtentFrame{display:none!important;}";
  }

  function updateMapScaleBar() {
    const el = document.getElementById("rkrkMapScaleBar");
    if (!el) return;
    const mPerPx = getMapMetersPerPixel();
    if (!(mPerPx > 0)) {
      el.hidden = true;
      return;
    }
    el.hidden = false;
    const screenScale = mPerPx / (MAP_SCREEN_PX_MM / 1000);
    const denom = roundDisplayScaleDenom(screenScale);
    const targetPx = 110;
    const niceM = niceScaleBarMeters(mPerPx * targetPx);
    const barPx = Math.max(48, Math.min(160, niceM / mPerPx));
    el.style.width = Math.round(barPx) + "px";
    const ratio = el.querySelector(".rkrk-scale-ratio");
    const distEl = el.querySelector(".rkrk-scale-dist");
    if (ratio) ratio.textContent = "Massstab " + formatSwissScaleDenom(denom);
    if (distEl) distEl.textContent = formatScaleBarDistance(niceM);
    try { updatePrintExtentPreview(); } catch (_) { /* noop */ }
  }

  function updatePrintExtentPreview() {
    const frame = document.getElementById("rkrkPrintExtentFrame");
    if (!frame) return;
    if (document.body.classList.contains("rkrk-share-viewer")
      || document.body.classList.contains("rkrk-share-sub")) {
      frame.hidden = true;
      return;
    }
    const denom = selectedPdfScaleDenom();
    if (!(denom > 0)) {
      frame.hidden = true;
      return;
    }
    if (typeof map === "undefined" || !map || typeof map.getSize !== "function") {
      frame.hidden = true;
      return;
    }
    const size = map.getSize();
    if (!Array.isArray(size) || !(size[0] > 0) || !(size[1] > 0)) {
      frame.hidden = true;
      return;
    }
    const mPerPx = getMapMetersPerPixel();
    if (!(mPerPx > 0)) {
      frame.hidden = true;
      return;
    }
    const paper = choosePdfPaperMm(size[0], size[1]);
    const groundW = (paper.imgWmm * denom / 1000) / MAP_PDF_SCALE_GAIN;
    const groundH = (paper.imgHmm * denom / 1000) / MAP_PDF_SCALE_GAIN;
    const pxW = groundW / mPerPx;
    const pxH = groundH / mPerPx;
    if (!(pxW > 2) || !(pxH > 2) || !Number.isFinite(pxW) || !Number.isFinite(pxH)) {
      frame.hidden = true;
      return;
    }
    frame.hidden = false;
    const overflow = pxW >= (size[0] - 8) && pxH >= (size[1] - 8);
    frame.classList.toggle("is-overflow", overflow);
    const cap = frame.querySelector(".rkrk-print-frame-cap");
    const label = "A4 " + formatSwissScaleDenom(denom);
    if (cap) cap.textContent = overflow ? (label + " — Ausschnitt grösser als Ansicht") : label;
    if (overflow) {
      frame.style.left = "6px";
      frame.style.top = "6px";
      frame.style.width = Math.max(0, size[0] - 12) + "px";
      frame.style.height = Math.max(0, size[1] - 12) + "px";
    } else {
      frame.style.left = ((size[0] - pxW) / 2) + "px";
      frame.style.top = ((size[1] - pxH) / 2) + "px";
      frame.style.width = pxW + "px";
      frame.style.height = pxH + "px";
    }
  }

  function installMapScaleBar() {
    const mapEl = document.getElementById("map");
    if (!mapEl) return;
    ensureMapScaleBarCss();
    let el = document.getElementById("rkrkMapScaleBar");
    if (!el) {
      el = document.createElement("div");
      el.id = "rkrkMapScaleBar";
      el.setAttribute("aria-label", "Massstab");
      el.title = "Doppelklick: Massstab eingeben";
      el.innerHTML = ""
        + "<span class='rkrk-scale-ratio'>Massstab</span>"
        + "<div class='rkrk-scale-track' aria-hidden='true'><span></span><span></span><span></span><span></span></div>"
        + "<div class='rkrk-scale-caps'><span>0</span><span class='rkrk-scale-dist'></span></div>";
    }
    if (el.parentNode !== mapEl) mapEl.appendChild(el);
    let frame = document.getElementById("rkrkPrintExtentFrame");
    if (!frame) {
      frame = document.createElement("div");
      frame.id = "rkrkPrintExtentFrame";
      frame.hidden = true;
      frame.setAttribute("aria-hidden", "true");
      frame.innerHTML = "<span class='rkrk-print-frame-cap'></span>";
    }
    if (frame.parentNode !== mapEl) mapEl.appendChild(frame);
    if (!el.__rkrkScaleEditBound) {
      el.__rkrkScaleEditBound = true;
      el.title = document.body.classList.contains("rkrk-share-viewer")
        ? ""
        : "Doppelklick: Massstab eingeben";
      const stop = (ev) => { ev.stopPropagation(); };
      el.addEventListener("pointerdown", stop);
      el.addEventListener("mousedown", stop);
      el.addEventListener("click", stop);
      el.addEventListener("dblclick", (ev) => {
        ev.preventDefault();
        ev.stopPropagation();
        promptEditMapScale();
      });
    }
    let tries = 0;
    const boot = () => {
      tries += 1;
      updateMapScaleBar();
      if (typeof map !== "undefined" && map && map.getView && !map.__rkrkScaleBarBound) {
        map.__rkrkScaleBarBound = true;
        try { map.getView().on("change:resolution", updateMapScaleBar); } catch (_) {}
        try { map.getView().on("change:center", updateMapScaleBar); } catch (_) {}
        try { map.on("moveend", updateMapScaleBar); } catch (_) {}
        window.addEventListener("beforeprint", updateMapScaleBar);
        return true;
      }
      return typeof map !== "undefined" && map && map.__rkrkScaleBarBound;
    };
    if (!boot()) {
      const timer = setInterval(() => {
        if (boot() || tries > 50) clearInterval(timer);
      }, 120);
    }
  }

  async function stampMapInlayOnCanvas(canvas) {
    if (!canvas) return canvas;
    const ctx = canvas.getContext("2d");
    if (!ctx) return canvas;
    let img = null;
    try {
      img = await resolvePdfBrandImage();
    } catch (err) {
      console.warn(err);
      img = null;
    }
    const title = getOverlayEditorTitle();
    const margin = Math.max(10, Math.round(Math.min(canvas.width, canvas.height) * 0.018));
    const logoSize = Math.max(18, Math.round(Math.min(canvas.width, canvas.height) * 0.039));
    const pad = Math.max(6, Math.round(logoSize * 0.14));
    const gap = Math.max(6, Math.round(logoSize * 0.14));
    const fontSize = Math.max(11, Math.round(logoSize * 0.22));
    ctx.save();
    ctx.globalAlpha = 1;
    ctx.setTransform(1, 0, 0, 1, 0, 0);
    ctx.font = "600 " + fontSize + "px Arial, Helvetica, sans-serif";
    const textW = Math.ceil(ctx.measureText(title).width);
    const hasLogo = !!(img && (img.naturalWidth || img.width));
    const boxH = (hasLogo ? logoSize : Math.max(fontSize + 4, 22)) + pad * 2;
    const boxW = pad + (hasLogo ? (logoSize + gap) : 0) + textW + pad;
    // PDF / export stamp: top-left
    const x = margin;
    const y = margin;
    ctx.fillStyle = "rgba(255,255,255,0.94)";
    ctx.strokeStyle = "rgba(0,0,0,0.28)";
    ctx.lineWidth = Math.max(1, Math.round(logoSize * 0.03));
    const r = Math.max(5, Math.round(logoSize * 0.1));
    ctx.beginPath();
    if (typeof ctx.roundRect === "function") {
      ctx.roundRect(x, y, boxW, boxH, r);
    } else {
      ctx.rect(x, y, boxW, boxH);
    }
    ctx.fill();
    ctx.stroke();
    let textX = x + pad;
    if (hasLogo) {
      try {
        ctx.drawImage(img, x + pad, y + pad, logoSize, logoSize);
        textX = x + pad + logoSize + gap;
      } catch (drawErr) {
        console.warn(drawErr);
      }
    }
    ctx.fillStyle = "#111827";
    ctx.textAlign = "left";
    ctx.textBaseline = "middle";
    ctx.fillText(title, textX, y + boxH / 2);
    try { stampMapScaleOnCanvas(canvas, ctx, margin, r); } catch (_) { /* noop */ }
    // PDF: Datum/Zeit unten rechts
    const now = new Date();
    const padN = (n) => String(n).padStart(2, "0");
    const stampText = `${padN(now.getDate())}.${padN(now.getMonth() + 1)}.${now.getFullYear()} ${padN(now.getHours())}:${padN(now.getMinutes())}`;
    const stampFont = Math.max(11, Math.round(logoSize * 0.24));
    ctx.font = "600 " + stampFont + "px Arial, Helvetica, sans-serif";
    const stampPadX = Math.max(8, Math.round(stampFont * 0.7));
    const stampPadY = Math.max(6, Math.round(stampFont * 0.55));
    const stampW = Math.ceil(ctx.measureText(stampText).width) + stampPadX * 2;
    const stampH = stampFont + stampPadY * 2;
    const stampX = canvas.width - margin - stampW;
    const stampY = canvas.height - margin - stampH;
    ctx.fillStyle = "rgba(255,255,255,0.94)";
    ctx.strokeStyle = "rgba(0,0,0,0.28)";
    ctx.lineWidth = Math.max(1, Math.round(logoSize * 0.03));
    ctx.beginPath();
    if (typeof ctx.roundRect === "function") ctx.roundRect(stampX, stampY, stampW, stampH, r);
    else ctx.rect(stampX, stampY, stampW, stampH);
    ctx.fill();
    ctx.stroke();
    ctx.fillStyle = "#111827";
    ctx.textAlign = "center";
    ctx.textBaseline = "middle";
    ctx.fillText(stampText, stampX + stampW / 2, stampY + stampH / 2);
    ctx.restore();
    return canvas;
  }

  function captureMapViewportCanvas(timeoutMs) {
    return new Promise((resolve, reject) => {
      if (typeof map === "undefined" || !map || typeof map.once !== "function") {
        reject(new Error("Karte nicht bereit."));
        return;
      }
      let settled = false;
      const settle = (fn, arg) => {
        if (settled) return;
        settled = true;
        clearTimeout(timer);
        fn(arg);
      };
      const finish = () => {
        try {
          const size = map.getSize();
          if (!Array.isArray(size) || size.length < 2 || !(size[0] > 0) || !(size[1] > 0)) {
            settle(reject, new Error("Kartengroesse ungueltig."));
            return;
          }
          const out = document.createElement("canvas");
          out.width = Math.round(size[0]);
          out.height = Math.round(size[1]);
          const ctx = out.getContext("2d");
          if (!ctx) {
            settle(reject, new Error("Canvas nicht verfuegbar."));
            return;
          }
          ctx.fillStyle = "#ffffff";
          ctx.fillRect(0, 0, out.width, out.height);
          const layerCanvases = map.getViewport().querySelectorAll(".ol-layer canvas, canvas.ol-layer");
          Array.prototype.forEach.call(layerCanvases, (canvas) => {
            if (!canvas || !(canvas.width > 0) || !(canvas.height > 0)) return;
            const parent = canvas.parentNode;
            const opacity = parent && parent.style ? parent.style.opacity : "";
            ctx.globalAlpha = opacity === "" ? 1 : Number(opacity);
            if (!Number.isFinite(ctx.globalAlpha)) ctx.globalAlpha = 1;
            const transform = canvas.style && canvas.style.transform ? canvas.style.transform : "";
            let matrix = null;
            const m = /^matrix\(([^)]+)\)$/.exec(transform);
            if (m) {
              matrix = m[1].split(",").map((v) => Number(String(v).trim()));
            }
            if (matrix && matrix.length === 6 && matrix.every((n) => Number.isFinite(n))) {
              CanvasRenderingContext2D.prototype.setTransform.apply(ctx, matrix);
            } else {
              const cssW = parseFloat(canvas.style.width) || canvas.width;
              const cssH = parseFloat(canvas.style.height) || canvas.height;
              ctx.setTransform(cssW / canvas.width, 0, 0, cssH / canvas.height, 0, 0);
            }
            ctx.drawImage(canvas, 0, 0);
          });
          ctx.globalAlpha = 1;
          ctx.setTransform(1, 0, 0, 1, 0, 0);
          Promise.resolve(stampMapInlayOnCanvas(out))
            .then((stamped) => {
              try { stamped.toDataURL("image/jpeg", 0.92); } catch (e) { /* CORS check */ }
              settle(resolve, stamped);
            })
            .catch((err) => settle(reject, err));
        } catch (err) {
          settle(reject, err);
        }
      };
      const timer = setTimeout(() => {
        // 1:50'000 lädt viele neue Kacheln — lieber unvollständiges Bild als Abbruch
        finish();
      }, timeoutMs || 8000);
      map.once("rendercomplete", finish);
      try {
        map.renderSync();
      } catch (err) {
        settle(reject, err);
      }
    });
  }

  function jpegBytesToPdfBlob(jpegBytes, imgW, imgH, layout) {
    const w = Math.max(1, Math.round(Number(imgW) || 1));
    const h = Math.max(1, Math.round(Number(imgH) || 1));
    const mmToPt = 72 / 25.4;
    let pageW;
    let pageH;
    let drawW;
    let drawH;
    let drawX;
    let drawY;
    if (layout && Number(layout.imgWmm) > 0 && Number(layout.imgHmm) > 0) {
      pageW = Number(layout.pageWmm || 297) * mmToPt;
      pageH = Number(layout.pageHmm || 210) * mmToPt;
      drawW = Number(layout.imgWmm) * mmToPt;
      drawH = Number(layout.imgHmm) * mmToPt;
      drawX = (pageW - drawW) / 2;
      drawY = (pageH - drawH) / 2;
    } else {
      pageW = Math.round(w * 72 / 96);
      pageH = Math.round(h * 72 / 96);
      drawW = pageW;
      drawH = pageH;
      drawX = 0;
      drawY = 0;
    }
    const fmt = (n) => Number(n).toFixed(2);
    const encoder = new TextEncoder();
    const parts = [];
    const offsets = [0];
    let offset = 0;
    const push = (chunk) => {
      const bytes = typeof chunk === "string" ? encoder.encode(chunk) : chunk;
      parts.push(bytes);
      offset += bytes.length;
    };
    const pushObj = (body) => {
      offsets.push(offset);
      push(body);
    };
    push("%PDF-1.4\n");
    pushObj("1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n");
    pushObj("2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n");
    pushObj(
      `3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ${fmt(pageW)} ${fmt(pageH)}] `
      + `/Resources << /XObject << /Im0 4 0 R >> >> /Contents 5 0 R >>\nendobj\n`
    );
    const imgHeader =
      `4 0 obj\n<< /Type /XObject /Subtype /Image /Width ${w} /Height ${h} `
      + `/ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode `
      + `/Length ${jpegBytes.length} >>\nstream\n`;
    offsets.push(offset);
    push(imgHeader);
    push(jpegBytes);
    push("\nendstream\nendobj\n");
    const content = `q\n${fmt(drawW)} 0 0 ${fmt(drawH)} ${fmt(drawX)} ${fmt(drawY)} cm\n/Im0 Do\nQ\n`;
    pushObj(`5 0 obj\n<< /Length ${content.length} >>\nstream\n${content}endstream\nendobj\n`);
    const xrefStart = offset;
    let xref = `xref\n0 ${offsets.length}\n0000000000 65535 f \n`;
    for (let i = 1; i < offsets.length; i += 1) {
      xref += `${String(offsets[i]).padStart(10, "0")} 00000 n \n`;
    }
    push(xref);
    push(
      `trailer\n<< /Size ${offsets.length} /Root 1 0 R >>\n`
      + `startxref\n${xrefStart}\n%%EOF\n`
    );
    const total = parts.reduce((n, p) => n + p.length, 0);
    const out = new Uint8Array(total);
    let pos = 0;
    for (let i = 0; i < parts.length; i += 1) {
      out.set(parts[i], pos);
      pos += parts[i].length;
    }
    return new Blob([out], { type: "application/pdf" });
  }

  function canvasToJpegBytes(canvas, quality) {
    const dataUrl = canvas.toDataURL("image/jpeg", quality == null ? 0.92 : quality);
    const b64 = dataUrl.split(",")[1];
    if (!b64) throw new Error("JPEG-Export fehlgeschlagen.");
    const bin = atob(b64);
    const bytes = new Uint8Array(bin.length);
    for (let i = 0; i < bin.length; i += 1) bytes[i] = bin.charCodeAt(i);
    return bytes;
  }

  function exportFilenameStamp() {
    const d = new Date();
    const pad = (n) => String(n).padStart(2, "0");
    return `${d.getFullYear()}${pad(d.getMonth() + 1)}${pad(d.getDate())}_${pad(d.getHours())}${pad(d.getMinutes())}`;
  }

  function exportDrawingNameSlug() {
    const nameEl = document.getElementById("drawingName");
    let raw = nameEl ? String(nameEl.value || "").trim() : "";
    if (!raw) {
      const list = document.getElementById("drawingList");
      const sel = list ? String(list.value || "").trim() : "";
      if (sel && !/^share_/i.test(sel)) raw = sel;
    }
    if (!raw) return "";
    return raw
      .replace(/[äÄ]/g, "ae")
      .replace(/[öÖ]/g, "oe")
      .replace(/[üÜ]/g, "ue")
      .replace(/ß/g, "ss")
      .replace(/[^A-Za-z0-9._-]+/g, "_")
      .replace(/_+/g, "_")
      .replace(/^_+|_+$/g, "")
      .replace(/^\d{4}(_?\d{2}){1,2}(?=_|$)/, "")
      .replace(/^_+|_+$/g, "")
      .slice(0, 48);
  }

  function stampExportFilename(kind, ext) {
    const parts = [exportFilenameStamp()];
    const drawing = exportDrawingNameSlug();
    if (drawing) parts.push(drawing);
    const k = String(kind || "").replace(/[^A-Za-z0-9_-]+/g, "").replace(/^_+|_+$/g, "");
    if (k) parts.push(k);
    const e = String(ext || "").replace(/^\./, "") || "dat";
    return `${parts.join("_")}.${e}`;
  }

  function downloadBlob(filename, blob) {
    const url = URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = filename;
    a.rel = "noopener";
    document.body.appendChild(a);
    a.click();
    a.remove();
    setTimeout(() => URL.revokeObjectURL(url), 1500);
  }

  function stampPdfFilename() {
    return stampExportFilename("", "pdf");
  }

  function openMapPrintFallback(hintEl, reason) {
    if (hintEl) {
      hintEl.textContent = reason
        ? `PDF-Direkt-Download nicht moeglich (${reason}). Druckdialog: «Als PDF speichern», Massstab auf dem Ausdruck.`
        : "Druckdialog: «Als PDF speichern» (Kartenansicht mit Massstab).";
    }
    window.print();
  }

  function ensurePdfScaleSelect() {
    let sel = document.getElementById("mapPdfScaleSelect");
    if (!sel) {
      sel = document.createElement("select");
      sel.id = "mapPdfScaleSelect";
      sel.setAttribute("aria-label", "PDF-Massstab");
      sel.innerHTML = ""
        + "<option value=\"0\">Massstab: Ansicht</option>"
        + "<option value=\"10000\">1:10'000</option>"
        + "<option value=\"25000\">1:25'000</option>"
        + "<option value=\"50000\">1:50'000</option>";
    } else if (!sel.querySelector('option[value="50000"]')) {
      const keep = sel.value;
      sel.innerHTML = ""
        + "<option value=\"0\">Massstab: Ansicht</option>"
        + "<option value=\"10000\">1:10'000</option>"
        + "<option value=\"25000\">1:25'000</option>"
        + "<option value=\"50000\">1:50'000</option>";
      if (keep) sel.value = keep;
    }
    sel.title = "PDF-/Druckmassstab auf A4. Feste Massstäbe zoomen um die aktuelle Mitte. Druck: 100 % / tatsächliche Grösse.";
    if (!sel.__rkrkPrintFrameBound) {
      sel.__rkrkPrintFrameBound = true;
      sel.addEventListener("change", () => {
        try { updatePrintExtentPreview(); } catch (_) { /* noop */ }
      });
    }
    try { updatePrintExtentPreview(); } catch (_) { /* noop */ }
    return sel;
  }

  function installMapPdfButton() {
    ensureSharePanelSection();
    if (document.getElementById("mapPdfBtn")) {
      installShareViewLinkButton();
      return;
    }
    const exportBtn = document.getElementById("exportBtn");
    const swissBtn = document.getElementById("openSwissMapBtn");
    const btn = document.createElement("button");
    btn.id = "mapPdfBtn";
    btn.type = "button";
    setObjToolButton(btn, "pdf", "PDF");
    btn.title = "Karte als A4-PDF (Massstab wählbar: Ansicht / 1:10'000 / 1:25'000 / 1:50'000)";
    const hint = document.createElement("p");
    hint.className = "small";
    hint.id = "mapPdfHint";
    hint.textContent = "";
    hint.style.flex = "1 1 100%";
    const actionHost = document.getElementById("shareActionHost") || document.getElementById("objectActionHost");
    const anchor = swissBtn || exportBtn;
    if (actionHost) {
      actionHost.appendChild(btn);
      actionHost.appendChild(hint);
    } else if (anchor) {
      anchor.insertAdjacentElement("afterend", btn);
      btn.insertAdjacentElement("afterend", hint);
    } else {
      return;
    }
    async function downloadPdfViaTcpdf(canvas, filename, paper, scaleLabel) {
      const dataUrl = canvas.toDataURL("image/jpeg", 0.92);
      const title = getOverlayEditorTitle();
      const res = await fetch("export_map_pdf.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          image: dataUrl,
          filename: filename,
          title: title,
          printWidthMm: paper && paper.imgWmm,
          printHeightMm: paper && paper.imgHmm,
          scale: scaleLabel || ""
        })
      });
      const ctype = (res.headers.get("content-type") || "").toLowerCase();
      if (!res.ok) {
        let errMsg = "TCPDF-Export fehlgeschlagen (" + res.status + ").";
        if (ctype.includes("application/json")) {
          const j = await res.json().catch(() => null);
          if (j && j.error) errMsg = j.error;
        }
        throw new Error(errMsg);
      }
      if (ctype.includes("application/json")) {
        const j = await res.json();
        throw new Error((j && j.error) || "TCPDF nicht verfügbar.");
      }
      const blob = await res.blob();
      if (!blob || blob.size < 64) throw new Error("Leere PDF-Antwort.");
      downloadBlob(filename, blob);
    }
    function preferTcpdfPdf() {
      const brand = window.__RKR_BRAND__ || {};
      if (brand.isToolsHost) return true;
      const host = String(location.hostname || "").toLowerCase();
      return host.indexOf("tools.rehkitzrettung.ch") !== -1;
    }
    btn.addEventListener("click", async () => {
      btn.disabled = true;
      const viewSnap = snapshotMapView();
      let restoreView = false;
      try { if (typeof window.refreshMapInlayTime === "function") window.refreshMapInlayTime(); } catch (_) {}
      hint.textContent = "Kartenansicht wird erfasst...";
      try {
        const size = (typeof map !== "undefined" && map && typeof map.getSize === "function") ? map.getSize() : null;
        const mapW = (Array.isArray(size) && size[0] > 0) ? size[0] : 0;
        const mapH = (Array.isArray(size) && size[1] > 0) ? size[1] : 0;
        const paperGuess = (mapW && mapH) ? choosePdfPaperMm(mapW, mapH) : choosePdfPaperMm(1200, 800);
        const wantScale = selectedPdfScaleDenom();
        if (wantScale && mapW) {
          hint.textContent = "Massstab " + formatSwissScaleDenom(wantScale) + " wird gesetzt...";
          try {
            restoreView = !!applyViewForPaperScale(wantScale, paperGuess.imgWmm, mapW);
          } catch (scaleErr) {
            console.warn(scaleErr);
            restoreView = false;
          }
          if (!restoreView) {
            hint.textContent = "Massstab konnte nicht gesetzt werden, aktuelle Ansicht wird verwendet...";
          } else {
            try { map.renderSync(); } catch (_) { /* noop */ }
            await new Promise((r) => setTimeout(r, 250));
          }
        }
        const captureMs = (wantScale >= 50000) ? 22000 : (restoreView ? 14000 : 8000);
        const canvas = await captureMapViewportCanvas(captureMs);
        const paper = choosePdfPaperMm(canvas.width, canvas.height);
        const stamp = getMapScaleStampInfo(canvas.width, canvas.height);
        const scaleLabel = stamp ? formatSwissScaleDenom(stamp.denom) : "";
        const filename = stampPdfFilename();
        if (preferTcpdfPdf()) {
          hint.textContent = "PDF wird mit TCPDF erzeugt...";
          try {
            await downloadPdfViaTcpdf(canvas, filename, paper, scaleLabel);
            hint.textContent = `PDF (TCPDF) ${scaleLabel || ""} gespeichert — A4, Druck 100 %.`.replace("  ", " ");
            return;
          } catch (tcpErr) {
            hint.textContent = "TCPDF nicht nutzbar, lokaler PDF-Fallback...";
            console.warn(tcpErr);
          }
        }
        const jpeg = canvasToJpegBytes(canvas, 0.92);
        const pdf = jpegBytesToPdfBlob(jpeg, canvas.width, canvas.height, paper);
        downloadBlob(filename, pdf);
        hint.textContent = `PDF ${scaleLabel || ""} gespeichert — A4, Druck 100 %.`.replace("  ", " ");
      } catch (err) {
        const msg = (err && err.message) ? err.message : String(err);
        const corsLikely = /tainted|SecurityError|cross-origin|CORS/i.test(msg)
          || (err && err.name === "SecurityError");
        try { updateMapScaleBar(); } catch (_) {}
        openMapPrintFallback(hint, corsLikely ? "Kachel-CORS / Canvas gesperrt" : msg);
        restoreView = false;
        const restoreLater = viewSnap;
        let restored = false;
        const done = () => {
          if (restored) return;
          restored = true;
          window.removeEventListener("afterprint", done);
          restoreMapView(restoreLater);
          try { updateMapScaleBar(); } catch (_) {}
        };
        window.addEventListener("afterprint", done);
        setTimeout(done, 60000);
        return;
      } finally {
        if (restoreView) {
          restoreMapView(viewSnap);
          try { updateMapScaleBar(); } catch (_) {}
        }
        btn.disabled = false;
      }
    });
    installShareViewLinkButton();
  }


  const importedKmlLayers = [];
  let importedKmlSeq = 0;

  const IMPORTED_KML_STORAGE_KEY = "rkrk_imported_kml_layers_v1";
  const IMPORTED_KML_DELETE_MS = 90 * 24 * 60 * 60 * 1000;
  let importedKmlPersistTimer = null;
  let importedKmlRestoreDone = false;

  function importedKmlLayerAgeMs(savedAt) {
    const t = Date.parse(String(savedAt || ""));
    if (!Number.isFinite(t)) return 0;
    return Math.max(0, Date.now() - t);
  }

  function pruneImportedKmlLayersByAge(layers) {
    const list = Array.isArray(layers) ? layers : [];
    const kept = [];
    list.forEach((e) => {
      if (!e) return;
      const age = importedKmlLayerAgeMs(e.savedAt);
      if (age > IMPORTED_KML_DELETE_MS) return; // >90 Tage: löschen
      kept.push(e);
    });
    return kept;
  }

  function schedulePersistImportedKmlLayers() {
    if (importedKmlPersistTimer) clearTimeout(importedKmlPersistTimer);
    importedKmlPersistTimer = setTimeout(() => {
      persistImportedKmlLayers();
    }, 250);
  }

  function persistImportedKmlLayers() {
    try {
      if (liveShareViewerMode || liveShareViewOnly) return false;
      try {
        if (document.body && document.body.classList.contains("rkrk-share-sub")) return false;
      } catch (_) { /* noop */ }
      const layers = pruneImportedKmlLayersByAge(
        importedKmlLayers
          .filter((e) => e && String(e.kmlText || "").trim())
          .map((e) => ({
            id: e.id,
            name: e.name || "KML",
            visible: e.visible !== false,
            opacity: Number.isFinite(Number(e.opacity)) ? Math.max(0, Math.min(1, Number(e.opacity))) : 1,
            kmlText: String(e.kmlText || ""),
            featureCount: Number(e.featureCount) || 0,
            savedAt: e.savedAt || new Date().toISOString()
          }))
      );
      if (!layers.length) {
        try { localStorage.removeItem(IMPORTED_KML_STORAGE_KEY); } catch (_) { /* noop */ }
        return true;
      }
      localStorage.setItem(IMPORTED_KML_STORAGE_KEY, JSON.stringify({
        version: 1,
        savedAt: new Date().toISOString(),
        seq: importedKmlSeq,
        layers
      }));
      return true;
    } catch (err) {
      try {
        setKmlImportStatus("KML konnte nicht gespeichert werden (Browser-Speicher voll?).");
      } catch (_) { /* noop */ }
      return false;
    }
  }

  function restoreImportedKmlLayersFromStorage() {
    if (importedKmlRestoreDone) return false;
    if (importedKmlLayers.length) {
      importedKmlRestoreDone = true;
      return false;
    }
    try {
      if (liveShareViewerMode || liveShareViewOnly) {
        importedKmlRestoreDone = true;
        return false;
      }
      if (document.body && document.body.classList.contains("rkrk-share-sub")) {
        importedKmlRestoreDone = true;
        return false;
      }
    } catch (_) { /* noop */ }
    if (typeof map === "undefined" || !map || typeof ol === "undefined") return false;
    let payload = null;
    try {
      const raw = localStorage.getItem(IMPORTED_KML_STORAGE_KEY);
      if (!raw) {
        importedKmlRestoreDone = true;
        return false;
      }
      payload = JSON.parse(raw);
    } catch (_) {
      importedKmlRestoreDone = true;
      return false;
    }
    const layers = pruneImportedKmlLayersByAge(
      payload && Array.isArray(payload.layers) ? payload.layers : []
    );
    if (!layers.length) {
      try { localStorage.removeItem(IMPORTED_KML_STORAGE_KEY); } catch (_) { /* noop */ }
      importedKmlRestoreDone = true;
      return false;
    }
    importedKmlRestoreDone = true;
    if (Number.isFinite(Number(payload.seq))) {
      importedKmlSeq = Math.max(importedKmlSeq, Number(payload.seq) || 0);
    }
    let restored = 0;
    layers.forEach((saved) => {
      if (!saved || !String(saved.kmlText || "").trim()) return;
      try {
        const features = parseKmlTextToFeatures(saved.kmlText);
        if (!features.length) return;
        addImportedKmlLayer(saved.name || "KML", features, saved.kmlText, {
          id: saved.id,
          visible: saved.visible !== false,
          opacity: saved.opacity,
          savedAt: saved.savedAt,
          skipFit: true,
          skipPersist: true,
          quiet: true
        });
        restored += 1;
      } catch (_) { /* skip broken layer */ }
    });
    if (restored > 0) {
      renderImportedKmlLayerRows();
      setKmlImportStatus(restored + " KML-Layer nach Reload wiederhergestellt.");
      try {
        const box = document.getElementById("overlayBox");
        if (box) box.open = true;
      } catch (_) { /* noop */ }
      try {
        if (!window.__applyingDrawingState && !window.__rkrkPreserveMapViewOnApply) {
          fitViewToLoadedObjects({ animate: true });
        }
      } catch (_) { /* noop */ }
    }
    return restored > 0;
  }


  function setKmlImportStatus(msg) {
    const el = document.getElementById("kmlImportLayerStatus");
    if (el) el.textContent = msg || "";
    try { setSharePanelStatus(msg || ""); } catch (_) { /* noop */ }
  }

  function ensureKmlImportDom() {
    let list = document.getElementById("kmlImportLayerList");
    let fileInput = document.getElementById("kmlImportLayerFile");
    let loadBtn = document.getElementById("kmlImportLayerBtn");
    const overlayContent = document.querySelector("#overlayBox .overlay-box-content");
    if (overlayContent && !document.getElementById("overlayKmlSection")) {
      const section = document.createElement("div");
      section.className = "overlay-kml-section";
      section.id = "overlayKmlSection";
      section.innerHTML = ""
        + '<div class="overlay-kml-title">KML-Layer</div>'
        + '<div class="overlay-kml-actions">'
        + '  <button type="button" id="kmlImportLayerBtn">KML laden</button>'
        + '  <input type="file" id="kmlImportLayerFile" accept=".kml,application/vnd.google-earth.kml+xml,text/xml" hidden />'
        + '</div>'
        + '<div id="kmlImportLayerList"></div>'
        + '<p class="small" id="kmlImportLayerStatus"></p>';
      const status = document.getElementById("overlayStatus");
      if (status && status.parentNode === overlayContent) {
        overlayContent.insertBefore(section, status);
      } else {
        overlayContent.appendChild(section);
      }
      list = document.getElementById("kmlImportLayerList");
      fileInput = document.getElementById("kmlImportLayerFile");
      loadBtn = document.getElementById("kmlImportLayerBtn");
    }
    return { list, fileInput, loadBtn };
  }

  function renderImportedKmlLayerRows() {
    const { list } = ensureKmlImportDom();
    if (!list) return;
    list.innerHTML = "";
    importedKmlLayers.forEach((entry) => {
      const row = document.createElement("div");
      row.className = "overlay-kml-row";
      row.dataset.kmlLayerId = entry.id;

      const label = document.createElement("label");
      label.className = "rkrk-sel rkrk-reg-pill";
      const cb = document.createElement("input");
      cb.type = "checkbox";
      cb.checked = entry.visible !== false;
      cb.title = "Layer ein-/ausschalten";
      cb.addEventListener("change", () => {
        entry.visible = !!cb.checked;
        if (entry.layer) entry.layer.setVisible(entry.visible);
        schedulePersistImportedKmlLayers();
      });
      const nameSpan = document.createElement("span");
      nameSpan.className = "kml-layer-name";
      nameSpan.textContent = entry.name || "KML";
      nameSpan.title = entry.name || "KML";
      label.appendChild(cb);
      label.appendChild(nameSpan);

      const opacityWrap = document.createElement("span");
      opacityWrap.className = "overlay-opacity";
      const range = document.createElement("input");
      range.type = "range";
      range.min = "0";
      range.max = "100";
      range.step = "5";
      range.value = String(Math.round((entry.opacity != null ? entry.opacity : 1) * 100));
      const pct = document.createElement("span");
      pct.textContent = range.value + "%";
      range.addEventListener("input", () => {
        const op = Math.max(0, Math.min(100, Number(range.value) || 0)) / 100;
        entry.opacity = op;
        pct.textContent = Math.round(op * 100) + "%";
        if (entry.layer) entry.layer.setOpacity(op);
        schedulePersistImportedKmlLayers();
      });
      opacityWrap.appendChild(range);
      opacityWrap.appendChild(pct);

      const removeBtn = document.createElement("button");
      removeBtn.type = "button";
      removeBtn.className = "kml-layer-remove";
      removeBtn.textContent = "Entfernen";
      removeBtn.title = "KML-Layer entfernen";
      removeBtn.addEventListener("click", () => removeImportedKmlLayer(entry.id));

      row.appendChild(label);
      row.appendChild(opacityWrap);
      row.appendChild(removeBtn);
      list.appendChild(row);
    });
  }

  function removeImportedKmlLayer(id) {
    const idx = importedKmlLayers.findIndex((e) => e && e.id === id);
    if (idx < 0) return;
    const entry = importedKmlLayers[idx];
    try {
      if (entry.layer && typeof map !== "undefined" && map) {
        map.removeLayer(entry.layer);
      }
    } catch (_) { /* noop */ }
    importedKmlLayers.splice(idx, 1);
    renderImportedKmlLayerRows();
    setKmlImportStatus(importedKmlLayers.length
      ? (importedKmlLayers.length + " KML-Layer geladen.")
      : "Kein KML-Layer geladen.");
    persistImportedKmlLayers();
  }

  function addImportedKmlLayer(name, features, kmlText, opts) {
    opts = opts || {};
    if (typeof ol === "undefined" || typeof map === "undefined" || !map) {
      throw new Error("Karte nicht bereit.");
    }
    if (!Array.isArray(features) || !features.length) {
      throw new Error("Keine darstellbaren Objekte in der KML.");
    }
    let id = String(opts.id || "").trim();
    if (id) {
      const m = id.match(/(\d+)\s*$/);
      if (m) importedKmlSeq = Math.max(importedKmlSeq, Number(m[1]) || 0);
    } else {
      importedKmlSeq += 1;
      id = "kml_imp_" + importedKmlSeq;
    }
    const opacity = Number.isFinite(Number(opts.opacity))
      ? Math.max(0, Math.min(1, Number(opts.opacity)))
      : 1;
    const visible = opts.visible !== false;
    const source = new ol.source.Vector({ features });
    const layer = new ol.layer.Vector({
      source,
      zIndex: 12,
      opacity,
      visible,
      properties: { rkrkImportedKml: true, rkrkKmlLayerId: id, name: name || "KML" }
    });
    map.addLayer(layer);
    const entry = {
      id,
      name: name || ("KML " + importedKmlSeq),
      layer,
      visible,
      opacity,
      featureCount: features.length,
      kmlText: String(kmlText || ""),
      savedAt: String(opts.savedAt || new Date().toISOString())
    };
    importedKmlLayers.push(entry);
    if (!opts.quiet) renderImportedKmlLayerRows();

    if (!opts.skipFit) {
      try {
        const extent = source.getExtent();
        if (extent && extent.every((v) => Number.isFinite(v))) {
          const view = map.getView();
          const size = (typeof map.getSize === "function") ? map.getSize() : null;
          if (view && size) {
            view.fit(extent, { size, padding: [48, 48, 48, 48], maxZoom: 17, duration: 350 });
          }
        }
      } catch (_) { /* noop */ }
    }

    if (!opts.quiet) {
      setKmlImportStatus(entry.name + ": " + features.length + " Objekt(e) als Layer geladen.");
      try {
        const box = document.getElementById("overlayBox");
        if (box) box.open = true;
      } catch (_) { /* noop */ }
    }
    if (!opts.skipPersist) persistImportedKmlLayers();
    return entry;
  }

  function parseKmlTextToFeatures(text) {
    if (typeof ol === "undefined" || !ol.format || typeof ol.format.KML !== "function") {
      throw new Error("OpenLayers KML-Parser nicht verfügbar.");
    }
    if (typeof map === "undefined" || !map || !map.getView) {
      throw new Error("Karte nicht bereit.");
    }
    const raw = String(text || "").replace(/^\uFEFF/, "");
    if (!raw.trim()) throw new Error("Leere KML-Datei.");
    const format = new ol.format.KML({
      extractStyles: true,
      showPointNames: true
    });
    const proj = map.getView().getProjection();
    let features = format.readFeatures(raw, {
      dataProjection: "EPSG:4326",
      featureProjection: proj
    });
    if (!Array.isArray(features)) features = [];
    // Fallback: manche KMLs ohne explizite Projection lesen
    if (!features.length) {
      features = format.readFeatures(raw) || [];
      features.forEach((f) => {
        try {
          const g = f.getGeometry();
          if (g && typeof g.transform === "function") {
            g.transform("EPSG:4326", proj);
          }
        } catch (_) { /* noop */ }
      });
    }
    return features.filter((f) => f && f.getGeometry && f.getGeometry());
  }

  async function loadKmlFileAsLayer(file) {
    if (!file) return;
    const name = String(file.name || "kml").replace(/\.kml$/i, "") || "KML";
    setKmlImportStatus("Lade " + (file.name || "KML") + "…");
    const text = await new Promise((resolve, reject) => {
      const reader = new FileReader();
      reader.onload = () => resolve(String(reader.result || ""));
      reader.onerror = () => reject(new Error("Datei konnte nicht gelesen werden."));
      reader.readAsText(file);
    });
    const features = parseKmlTextToFeatures(text);
    addImportedKmlLayer(name, features, text);
  }

  function openKmlImportFilePicker() {
    const { fileInput } = ensureKmlImportDom();
    if (!fileInput) return;
    try { fileInput.value = ""; } catch (_) { /* noop */ }
    fileInput.click();
  }

  function installKmlImportLayerUi() {
    const { fileInput, loadBtn } = ensureKmlImportDom();
    if (loadBtn && !loadBtn.__rkrkKmlImportBound) {
      loadBtn.__rkrkKmlImportBound = true;
      loadBtn.addEventListener("click", (ev) => {
        ev.preventDefault();
        openKmlImportFilePicker();
      });
    }
    if (fileInput && !fileInput.__rkrkKmlImportBound) {
      fileInput.__rkrkKmlImportBound = true;
      fileInput.addEventListener("change", async () => {
        const file = fileInput.files && fileInput.files[0];
        if (!file) return;
        try {
          await loadKmlFileAsLayer(file);
        } catch (err) {
          const msg = "KML laden fehlgeschlagen: " + ((err && err.message) ? err.message : String(err));
          setKmlImportStatus(msg);
          try { showBroadcastToast(msg, 4500); } catch (_) { /* noop */ }
        } finally {
          try { fileInput.value = ""; } catch (_) { /* noop */ }
        }
      });
    }

    // Zusätzlicher Button neben KML-Export in «Export & Teilen»
    try {
      ensureSharePanelSection();
      const host = moveExportButtonsToShareHost() || document.getElementById("shareActionHost");
      if (host) {
        let shareBtn = document.getElementById("kmlImportShareBtn");
        if (!shareBtn) {
          shareBtn = document.createElement("button");
          shareBtn.id = "kmlImportShareBtn";
          shareBtn.type = "button";
          const exportBtn = document.getElementById("exportBtn");
          if (exportBtn && exportBtn.parentNode === host) {
            exportBtn.insertAdjacentElement("afterend", shareBtn);
          } else {
            host.appendChild(shareBtn);
          }
        }
        setObjToolButton(shareBtn, "kmlLoad", "KML laden");
        shareBtn.title = "KML mit Objekten als Layer laden (ein-/ausschaltbar unter SwissTopo-Overlays)";
        if (!shareBtn.__rkrkKmlImportBound) {
          shareBtn.__rkrkKmlImportBound = true;
          shareBtn.addEventListener("click", (ev) => {
            ev.preventDefault();
            openKmlImportFilePicker();
          });
        }
      }
    } catch (_) { /* noop */ }

    renderImportedKmlLayerRows();
    if (!importedKmlLayers.length) {
      setKmlImportStatus("");
    }
    const tryRestore = () => {
      try { restoreImportedKmlLayersFromStorage(); } catch (_) { /* noop */ }
    };
    tryRestore();
    if (!importedKmlRestoreDone) {
      setTimeout(tryRestore, 300);
      setTimeout(tryRestore, 1200);
    }
  }

  function ensureSharePanelSection() {
    const panel = document.querySelector("aside.panel");
    if (!panel) return null;
    let box = document.getElementById("sharePanelDetails");
    if (!box) {
      box = document.createElement("details");
      box.className = "panel-details";
      box.id = "sharePanelDetails";
      box.open = true;
      box.innerHTML = ""
        + "<summary>Export &amp; Teilen</summary>"
        + "<div class='panel-details-content'>"
        + "  <div id='shareActionHost'></div>"
        + "  <p class='small' id='sharePanelHint'>" + sharePanelHintText() + "</p>"
        + "  <p class='small' id='sharePanelStatus'></p>"
        + "</div>";
      const objectPanel = document.getElementById("objectPanelDetails");
      const listPanel = document.getElementById("objManagedListPanel");
      if (listPanel && listPanel.parentNode === panel) {
        listPanel.insertAdjacentElement("afterend", box);
      } else if (objectPanel && objectPanel.parentNode === panel) {
        objectPanel.insertAdjacentElement("afterend", box);
      } else {
        panel.appendChild(box);
      }
    } else {
      const sum = box.querySelector("summary");
      if (sum) sum.innerHTML = "Export &amp; Teilen";
      const hint = document.getElementById("sharePanelHint");
      if (hint) {
        hint.textContent = sharePanelHintText();
      }
      try { syncObjEditorExportHelp(); } catch (_) { /* noop */ }
    }
    try { syncObjEditorExportHelp(); } catch (_) { /* noop */ }
    return box;
  }

  function setSharePanelStatus(msg) {
    const el = document.getElementById("sharePanelStatus");
    if (el) el.textContent = msg || "";
  }

  function moveExportButtonsToShareHost() {
    const box = ensureSharePanelSection();
    if (!box) return null;
    const host = document.getElementById("shareActionHost");
    if (!host) return null;
    const exportBtn = document.getElementById("exportBtn");
    const leftoverDjiBtn = document.getElementById("exportDjiKmlBtn");
    const hub2KmlBtn = ensureHub2KmlButton();
    const hub2PushBtn = ensureHub2PushButton();
    const mbtilesBtn = ensureMbtilesExportButton();
    const kmlLoadBtn = document.getElementById("kmlImportShareBtn");
    const swissBtn = document.getElementById("openSwissMapBtn");
    const pdfBtn = document.getElementById("mapPdfBtn");
    const pdfHint = document.getElementById("mapPdfHint");
    const linkBtn = document.getElementById("shareViewLinkBtn");
    // Preferred order: KML → Hub 2 KML → Nach Hub 2 senden → DJI Layer → KML laden → SwissTopo → PDF → Ansicht-Link
    if (leftoverDjiBtn && leftoverDjiBtn.parentNode) leftoverDjiBtn.parentNode.removeChild(leftoverDjiBtn);
    if (exportBtn) {
      setObjToolButton(exportBtn, "kml", "KML");
      exportBtn.title = "KML für Google Earth / map.geo.admin";
      host.appendChild(exportBtn);
    }
    if (hub2KmlBtn) {
      host.appendChild(hub2KmlBtn);
    }
    if (hub2PushBtn) {
      host.appendChild(hub2PushBtn);
    }
    if (mbtilesBtn) {
      setObjToolButton(mbtilesBtn, "mbtiles", "DJI Layer");
      mbtilesBtn.title = "Für DJI Pilot 2 Custom Layer (MBTiles). RC: Profil → Karteneinstellungen → Custom Layer. Kein Live. Kein Hub-2-Annotationen-Import.";
      host.appendChild(mbtilesBtn);
    }
    if (kmlLoadBtn) {
      setObjToolButton(kmlLoadBtn, "kmlLoad", "KML laden");
      kmlLoadBtn.title = "KML mit Objekten als Layer laden (ein-/ausschaltbar unter SwissTopo-Overlays)";
      host.appendChild(kmlLoadBtn);
    }
    if (swissBtn) {
      setObjToolButton(swissBtn, "map", "SwissTopo");
      swissBtn.title = "In map.geo.admin.ch (SwissTopo) öffnen";
      host.appendChild(swissBtn);
    }
    // Drop orphan swiss status paragraphs that lived under objectActionHost
    const oldHost = document.getElementById("objectActionHost");
    if (oldHost) {
      Array.from(oldHost.querySelectorAll("p.small")).forEach((p) => {
        if (p.id === "mapPdfHint") return;
        if (!String(p.id || "").trim()) p.remove();
      });
    }
    if (pdfBtn) {
      setObjToolButton(pdfBtn, "pdf", "PDF");
      pdfBtn.title = "Karte als A4-PDF (Massstab wählbar: Ansicht / 1:10'000 / 1:25'000 / 1:50'000)";
      host.appendChild(pdfBtn);
    }
    const scaleSel = ensurePdfScaleSelect();
    if (scaleSel) host.appendChild(scaleSel);
    if (linkBtn) host.appendChild(linkBtn);
    if (pdfHint) {
      pdfHint.style.flex = "1 1 100%";
      host.appendChild(pdfHint);
    }
    return host;
  }

  function installShareViewLinkButton() {
    try {
      if (document.body && document.body.classList.contains("rkrk-share-sub")) return;
    } catch (_) { /* noop */ }
    const box = ensureSharePanelSection();
    if (!box) return;
    const host = moveExportButtonsToShareHost() || document.getElementById("shareActionHost");
    if (!host) return;

    let btn = document.getElementById("shareViewLinkBtn");
    if (!btn) {
      btn = document.createElement("button");
      btn.id = "shareViewLinkBtn";
      btn.type = "button";
    }
    setObjToolButton(btn, "link", "Ansicht-Link");
    btn.title = "Aktuellen Stand teilen (Objekte, Rasterfarben, Ansicht). Erneut klicken aktualisiert denselben Link — kein Live-Broadcast.";
    if (!btn.__rkrkViewShareClickBound) {
      btn.__rkrkViewShareClickBound = true;
      btn.addEventListener("click", async () => {
        btn.disabled = true;
        try {
          ensureBroadcastCss();
          const res = await copyStaticMapViewShareLink();
          btn.classList.add("is-live");
          const updated = !!(res && res.updated);
          const msg = updated
            ? "Ansicht-Link aktualisiert — aktueller Stand gespeichert (gleicher Link)."
            : (res.copied
              ? "Ansicht-Link kopiert — aktueller Stand gespeichert."
              : `Link: ${res.url}`);
          setSharePanelStatus(updated ? "Link aktualisiert." : (res.copied ? "Ansicht-Link kopiert." : String(res.url || "")));
          showBroadcastToast(msg, 4500);
        } catch (err) {
          setSharePanelStatus("Link fehlgeschlagen.");
          showBroadcastToast(`Link fehlgeschlagen: ${(err && err.message) ? err.message : String(err)}`, 4000);
        } finally {
          btn.disabled = false;
        }
      });
    }
    // Keep order: after PDF
    const pdfBtn = document.getElementById("mapPdfBtn");
    if (pdfBtn && pdfBtn.parentNode === host) {
      const pdfHint = document.getElementById("mapPdfHint");
      if (pdfHint && pdfHint.parentNode === host) host.insertBefore(btn, pdfHint);
      else pdfBtn.insertAdjacentElement("afterend", btn);
    } else if (btn.parentNode !== host) {
      host.appendChild(btn);
    }
    moveExportButtonsToShareHost();
  }

  function installKmlExportOverride() {
    const exportBtn = document.getElementById("exportBtn");
    if (!exportBtn || exportBtn.__objTextfieldsExportInstalled) return;
    exportBtn.__objTextfieldsExportInstalled = true;
    exportBtn.addEventListener("click", async (evt) => {
      if (!exportTypeEl) return;
      syncEditingObjectFromUi();
      evt.preventDefault();
      evt.stopImmediatePropagation();
      try {
        const elevationMap = await fetchTerrainElevations(collectPointsForElevation());
        const completeKml = buildCompleteMapExportKml(elevationMap);
        if (completeKml) {
          downloadKml(stampExportFilename("kml", "kml"), completeKml);
          return;
        }
        const mode = normalizeObjectType(exportTypeEl.value);
        let lon = Array.isArray(selectedLonLat) && selectedLonLat.length === 2 ? Number(selectedLonLat[0]) : NaN;
        let lat = Array.isArray(selectedLonLat) && selectedLonLat.length === 2 ? Number(selectedLonLat[1]) : NaN;
        if ((!Number.isFinite(lon) || !Number.isFinite(lat)) && map && map.getView) {
          const center = map.getView().getCenter();
          if (Array.isArray(center) && center.length === 2) {
            const ll = ol.proj.transform(center, map.getView().getProjection(), "EPSG:4326");
            lon = Number(ll[0]); lat = Number(ll[1]);
          }
        }
        const isGrid = mode === "grid";
        const isCircles = mode === "circles";
        const isCircle = mode === "circle";
        const isPolygon = mode === "polygon";
        const isPoints = mode === "points" || mode === "textfields";
        if ((isGrid || isCircles || isCircle) && (!Number.isFinite(lon) || !Number.isFinite(lat))) {
          alert("Kein exportierbares Objekt verfügbar.");
          return;
        }
        const exportName = isGrid ? "grid_export"
          : (isCircle ? "circle_export" : (isCircles ? "circles_export" : (mode === "textfields" ? "textfields_export" : (isPoints ? "points_export" : (mode === "pfeil" ? "pfeil_export" : (mode === "freehand" ? "freehand_export" : "polygon_export"))))));
        const labelsKml = `${kmlHeader("labels_export")}${appendLabeledPointsKml(elevationMap)}${kmlFooter()}`;
        const baseKml = isGrid
          ? buildGridKml(lat, lon, elevationMap)
          : (isCircles
            ? buildCirclesKml(lat, lon, elevationMap)
            : (isPolygon ? buildCurrentPolygonOnlyKml() : `${kmlHeader(exportName)}${appendLabeledPointsKml(elevationMap)}${kmlFooter()}`));
        const importedDocs = (typeof collectImportedKmlExportDocs === "function")
          ? collectImportedKmlExportDocs()
          : [];
        const kml = isPoints
          ? (importedDocs.length
            ? composeKmlFromDocuments(`${exportName}_with_imports`, [baseKml].concat(importedDocs))
            : baseKml)
          : composeKmlFromDocuments(`${exportName}_with_labels`, [baseKml, labelsKml].concat(importedDocs));
        if (!kml) {
          alert("Kein Polygon vorhanden.");
          return;
        }
        downloadKml(stampExportFilename("kml", "kml"), kml);
      } catch (_) {
        alert("Textfelder-KML konnte nicht erzeugt werden.");
      }
    }, true);
  }

  function parseSearchResultLonLat(result) {
    const attrs = result && result.attrs && typeof result.attrs === "object" ? result.attrs : {};
    const lon = Number(attrs.lon ?? attrs.x ?? (result && result.lon));
    const lat = Number(attrs.lat ?? attrs.y ?? (result && result.lat));
    return (Number.isFinite(lon) && Number.isFinite(lat)) ? [lon, lat] : null;
  }
  async function searchGeoAdminLocation(text) {
    const q = String(text || "").trim();
    if (!q) return null;
    const url = `https://api3.geo.admin.ch/rest/services/api/SearchServer?type=locations&searchText=${encodeURIComponent(q)}&limit=1`;
    const resp = await fetch(url, { cache: "no-store" });
    if (!resp.ok) throw new Error(`Search ${resp.status}`);
    const payload = await resp.json();
    const r = Array.isArray(payload && payload.results) ? payload.results : [];
    return r.length ? parseSearchResultLonLat(r[0]) : null;
  }

  function installPlaceSearch() {
    if (document.getElementById("objPlaceSearchInput")) return;
    const coordDisplayEl = document.getElementById("coordDisplay");
    const coordDetails = coordDisplayEl ? coordDisplayEl.closest("details") : null;
    const content = coordDetails ? coordDetails.querySelector(".panel-details-content") : null;
    if (!content) return;
    const box = document.createElement("div");
    box.innerHTML = "<label for='objPlaceSearchInput'>Ort suchen</label><div id='objPlaceSearchRow'><input id='objPlaceSearchInput' type='text' placeholder='z. B. Aarau'/><button id='objPlaceSearchBtn' type='button' title='Ort auf Karte suchen'>Suchen</button></div><p class='small' id='objPlaceSearchStatus'></p>";
    content.appendChild(box);
    const input = document.getElementById("objPlaceSearchInput");
    const btn = document.getElementById("objPlaceSearchBtn");
    const st = document.getElementById("objPlaceSearchStatus");
    const run = async () => {
      const text = input.value.trim();
      if (!text) { st.textContent = "Bitte Ort eingeben."; return; }
      btn.disabled = true; st.textContent = "Suche läuft...";
      try {
        const ll = await searchGeoAdminLocation(text);
        if (!ll) { st.textContent = "Kein Treffer."; return; }
        selectedLonLat = [ll[0], ll[1]];
        const c = lonLatToMapCoord(ll[0], ll[1]);
        if (typeof setMarker === "function") setMarker(c);
        if (typeof renderCoord === "function") renderCoord();
        if (map && map.getView) map.getView().animate({ center: c, zoom: 12, duration: 250 });
        if (typeof drawPreview === "function" && typeof previewActive !== "undefined" && previewActive) drawPreview();
        st.textContent = `Treffer: ${ll[0].toFixed(6)}, ${ll[1].toFixed(6)}`;
        try { collapseCoordPanelWhenOrtDefined(); } catch (_) {}
      } catch (e) {
        st.textContent = `Fehler: ${(e && e.message) ? e.message : "Suche fehlgeschlagen"}`;
      } finally { btn.disabled = false; }
    };
    btn.addEventListener("click", () => { void run(); });
    input.addEventListener("keydown", (ev) => { if (ev.key === "Enter") { ev.preventDefault(); void run(); } });
  }

  function moveObjectPanelToBottom() {
    // Preferred aside order: Koordinate → Objekt → Objektliste (inkl. Zeichnungen) → … → Hilfe
    placeObjectPanelsBelowCoordinates();
    placeHelpPanelAtBottom();
  }

  function nestDrawingsPanelUnderObjects() {
    const listPanel = document.getElementById("objManagedListPanel");
    const drawingsPanel = document.getElementById("drawingsPanelDetails")
      || findPanelBySummary((t) => t.includes("zeichnungen"));
    if (!listPanel || !drawingsPanel) return;
    const content = listPanel.querySelector(".panel-details-content");
    if (!content) return;
    if (drawingsPanel.parentElement === content) return;
    drawingsPanel.open = false;
    drawingsPanel.style.display = "";
    content.appendChild(drawingsPanel);
  }


  function ensureManagedObjectListToolbar() {
    const panel = document.getElementById("objManagedListPanel");
    if (!panel) return;
    let bar = document.getElementById("objManagedListToolbar");
    const content = panel.querySelector(".panel-details-content");
    if (!bar && content) {
      bar = document.createElement("div");
      bar.id = "objManagedListToolbar";
      bar.innerHTML = ""
        + "<button type='button' id='objGroupSelectedBtn' title='Angewählte Objekte gruppieren'>Gruppieren</button>"
        + "<button type='button' id='objGroupClearSelBtn' title='Auswahl leeren'>Auswahl leeren</button>";
      const empty = document.getElementById("objManagedListEmpty");
      if (empty) content.insertBefore(bar, empty);
      else content.insertBefore(bar, content.firstChild);
    }
    const groupBtn = document.getElementById("objGroupSelectedBtn");
    if (groupBtn && !groupBtn.__rkrkBound) {
      groupBtn.__rkrkBound = true;
      groupBtn.addEventListener("click", (ev) => {
        ev.preventDefault();
        groupSelectedManagedObjects();
      });
    }
    const clearBtn = document.getElementById("objGroupClearSelBtn");
    if (clearBtn && !clearBtn.__rkrkBound) {
      clearBtn.__rkrkBound = true;
      clearBtn.addEventListener("click", (ev) => {
        ev.preventDefault();
        objListSelectedIds.clear();
        try { refreshManagedObjectList(); } catch (_) { /* noop */ }
      });
    }
    let hint = document.getElementById("objManagedListHint");
    if (!hint && content) {
      hint = document.createElement("p");
      hint.className = "small";
      hint.id = "objManagedListHint";
      hint.textContent = "Checkbox → Gruppieren. Gruppen per Drag zwischen Objekte sortieren; ein/aus + Transparenz.";
      const list = document.getElementById("objManagedList");
      if (list) content.insertBefore(hint, list);
      else content.appendChild(hint);
    }
  }

  function createManagedObjectGroupRow(group, memberCount) {
    const collapsed = !!group.collapsed;
    const li = document.createElement("li");
    li.className = "obj-list-group"
      + (group.hidden ? " obj-list-hidden" : "")
      + (collapsed ? " obj-list-collapsed" : "");
    li.dataset.groupId = String(group.id);

    const collapseBtn = document.createElement("button");
    collapseBtn.type = "button";
    collapseBtn.className = "obj-group-collapse";
    collapseBtn.textContent = collapsed ? "▸" : "▾";
    collapseBtn.title = collapsed ? "Gruppe aufklappen" : "Gruppe einklappen";
    collapseBtn.setAttribute("aria-expanded", collapsed ? "false" : "true");
    collapseBtn.addEventListener("click", (ev) => {
      ev.preventDefault();
      ev.stopPropagation();
      group.collapsed = !group.collapsed;
      try { refreshManagedObjectList(); } catch (_) { /* noop */ }
    });
    collapseBtn.addEventListener("mousedown", (e) => e.stopPropagation());

    const meta = document.createElement("div");
    meta.className = "obj-list-meta";
    const name = document.createElement("span");
    name.className = "obj-list-name";
    const count = Number(memberCount) || 0;
    name.textContent = String(group.name || ("Gruppe " + group.id))
      + (collapsed ? ` (${count})` : "");
    name.title = "Gruppe umbenennen";
    name.addEventListener("click", (ev) => {
      ev.preventDefault();
      ev.stopPropagation();
      if (name.dataset.renaming === "1") return;
      name.dataset.renaming = "1";
      const input = document.createElement("input");
      input.type = "text";
      input.className = "obj-list-name-input";
      input.value = String(group.name || "");
      name.replaceWith(input);
      input.focus();
      input.select();
      let finished = false;
      const finish = (save) => {
        if (finished) return;
        finished = true;
        if (save) {
          rememberFixedHistory();
          group.name = String(input.value || "").trim() || ("Gruppe " + group.id);
          markFixedHistoryCursor();
          try { if (typeof notifyLiveShareChange === "function") notifyLiveShareChange(); } catch (_) { /* noop */ }
        }
        refreshManagedObjectList();
      };
      input.addEventListener("keydown", (e) => {
        if (e.key === "Enter") { e.preventDefault(); finish(true); }
        else if (e.key === "Escape") { e.preventDefault(); finish(false); }
      });
      input.addEventListener("blur", () => finish(true));
      input.addEventListener("mousedown", (e) => e.stopPropagation());
    });
    meta.appendChild(name);

    const opacityWrap = document.createElement("span");
    opacityWrap.className = "obj-group-opacity";
    const range = document.createElement("input");
    range.type = "range";
    range.min = "0";
    range.max = "100";
    range.step = "5";
    range.value = String(Math.round((Number(group.opacity) || 1) * 100));
    range.title = "Gruppentransparenz";
    const pct = document.createElement("span");
    pct.textContent = range.value + "%";
    range.addEventListener("input", () => {
      const op = Math.max(0, Math.min(100, Number(range.value) || 0)) / 100;
      pct.textContent = Math.round(op * 100) + "%";
      setManagedObjectGroupOpacity(group.id, op, { live: true });
    });
    range.addEventListener("change", () => {
      const op = Math.max(0, Math.min(100, Number(range.value) || 0)) / 100;
      setManagedObjectGroupOpacity(group.id, op, { commit: true });
    });
    range.addEventListener("mousedown", (e) => e.stopPropagation());
    opacityWrap.appendChild(range);
    opacityWrap.appendChild(pct);

    const actions = document.createElement("div");
    actions.className = "obj-list-actions";
    const visBtn = document.createElement("button");
    visBtn.type = "button";
    visBtn.className = "obj-list-visibility";
    setObjToolButton(visBtn, group.hidden ? "hide" : "show",
      group.hidden ? "Gruppe anzeigen" : "Gruppe unterdrücken");
    visBtn.addEventListener("click", (ev) => {
      ev.preventDefault();
      ev.stopPropagation();
      toggleManagedObjectGroupHidden(group.id);
    });
    const dissolveBtn = document.createElement("button");
    dissolveBtn.type = "button";
    dissolveBtn.className = "obj-list-delete";
    dissolveBtn.textContent = "Auflösen";
    dissolveBtn.title = "Gruppe auflösen (Objekte behalten)";
    dissolveBtn.addEventListener("click", (ev) => {
      ev.preventDefault();
      ev.stopPropagation();
      if (!window.confirm(`Gruppe «${group.name}» auflösen?`)) return;
      dissolveManagedObjectGroup(group.id);
      renderManagedObjects();
      try { refreshManagedObjectList(); } catch (_) { /* noop */ }
      managedStatusText("Gruppe aufgelöst.");
    });
    actions.appendChild(visBtn);
    actions.appendChild(dissolveBtn);
    actions.addEventListener("mousedown", (e) => e.stopPropagation());

    li.draggable = true;
    li.addEventListener("dragstart", (ev) => {
      if (ev.target && ev.target.closest && ev.target.closest(".obj-list-actions, .obj-list-name-input, .obj-group-opacity, input, button")) {
        ev.preventDefault();
        return;
      }
      li.classList.add("obj-list-dragging");
      try {
        ev.dataTransfer.effectAllowed = "move";
        ev.dataTransfer.setData("text/plain", "group:" + String(group.id));
      } catch (_) { /* noop */ }
      window.__rkrkObjListDrag = { kind: "group", id: Number(group.id) };
      window.__rkrkObjListDragId = null;
    });
    li.addEventListener("dragend", () => {
      li.classList.remove("obj-list-dragging");
      window.__rkrkObjListDrag = null;
      window.__rkrkObjListDragId = null;
      const listEl = document.getElementById("objManagedList");
      if (listEl) listEl.querySelectorAll("li.obj-list-drag-over").forEach((el) => el.classList.remove("obj-list-drag-over"));
    });
    li.addEventListener("dragover", (ev) => {
      ev.preventDefault();
      try { ev.dataTransfer.dropEffect = "move"; } catch (_) { /* noop */ }
      const listEl = document.getElementById("objManagedList");
      if (listEl) {
        listEl.querySelectorAll("li.obj-list-drag-over").forEach((el) => {
          if (el !== li) el.classList.remove("obj-list-drag-over");
        });
      }
      li.classList.add("obj-list-drag-over");
    });
    li.addEventListener("dragleave", () => { li.classList.remove("obj-list-drag-over"); });
    li.addEventListener("drop", (ev) => {
      ev.preventDefault();
      ev.stopPropagation();
      li.classList.remove("obj-list-drag-over");
      let drag = window.__rkrkObjListDrag;
      try {
        const raw = String(ev.dataTransfer.getData("text/plain") || "");
        if (raw.indexOf("group:") === 0) drag = { kind: "group", id: Number(raw.slice(6)) };
        else if (raw.indexOf("object:") === 0) drag = { kind: "object", id: Number(raw.slice(7)) };
        else if (raw && window.__rkrkObjListDragId == null) drag = { kind: "object", id: Number(raw) };
      } catch (_) { /* noop */ }
      if (!drag && window.__rkrkObjListDragId != null) drag = { kind: "object", id: Number(window.__rkrkObjListDragId) };
      if (!drag) return;
      handleManagedListDrop(drag.kind, drag.id, "group", group.id);
    });

    li.appendChild(collapseBtn);
    li.appendChild(meta);
    li.appendChild(opacityWrap);
    li.appendChild(actions);
    return li;
  }

  function installManagedObjectListPanel() {
    const panel = document.querySelector("aside.panel");
    if (!panel || document.getElementById("objManagedListPanel")) {
      nestDrawingsPanelUnderObjects();
      return;
    }
    const box = document.createElement("details");
    box.className = "panel-details";
    box.id = "objManagedListPanel";
    box.open = true;
    box.innerHTML = ""
      + "<summary>Objektliste</summary>"
      + "<div class='panel-details-content'>"
      + "<div id='objManagedListToolbar'>"
      + "  <button type='button' id='objGroupSelectedBtn' title='Angewählte Objekte gruppieren'>Gruppieren</button>"
      + "  <button type='button' id='objGroupClearSelBtn' title='Auswahl leeren'>Auswahl leeren</button>"
      + "</div>"
      + "<p class='small' id='objManagedListEmpty'>Noch keine Objekte. Zeichnen und «Objekt fixieren».</p>"
      + "<p class='small' id='objManagedListHint'>Checkbox → Gruppieren. Gruppen per Drag zwischen Objekte sortieren; ein/aus + Transparenz.</p>"
      + "<ul id='objManagedList'></ul>"
      + "</div>";
    panel.appendChild(box);
    nestDrawingsPanelUnderObjects();
    try { ensureManagedObjectListToolbar(); } catch (_) { /* noop */ }
  }
  refreshManagedObjectList = function () {
    const list = document.getElementById("objManagedList");
    const empty = document.getElementById("objManagedListEmpty");
    if (!list) return;
    // Punkte/Textfelder immer als Einzelobjekte führen
    if (splitMultiLabelManagedObjects()) {
      ensureManagedObjectLayer();
      renderManagedObjects();
    }
    // Sicherheit: maximal ein Objekt gleichzeitig in Bearbeitung
    const editing = managedObjects.filter((o) => o && !o.fixed);
    if (editing.length > 1) {
      const keepId = (selectedManagedObjectId != null
        && editing.some((o) => Number(o.id) === Number(selectedManagedObjectId)))
        ? Number(selectedManagedObjectId)
        : Number(editing[0].id);
      ensureSingleEditingObject(keepId, { sync: false });
    }
    managedObjects.forEach((obj) => {
      if (!obj || obj.type === "textfields" || !Array.isArray(obj.labels) || !obj.labels.length) return;
      if (obj.type === "points" && obj.labels.every((lp) => lp && isTextOnlyLabelType(lp.type))) {
        obj.type = "textfields";
        const t0 = String((obj.labels[0] && obj.labels[0].text) || "").trim();
        if (!obj.name || !String(obj.name).trim() || /^Punkt\b/i.test(String(obj.name))) {
          obj.name = t0 ? `Textfeld: ${t0}` : defaultManagedObjectName(obj);
        }
      }
    });
    list.innerHTML = "";
    if (!managedObjects.length) {
      if (empty) empty.style.display = "";
      return;
    }
    const visibleObjects = managedObjects.filter((o) => isManagedObjectVisibleToMe(o));
    if (!visibleObjects.length) {
      if (empty) empty.style.display = "";
      return;
    }
    if (empty) empty.style.display = "none";
    try { ensureManagedObjectListToolbar(); } catch (_) { /* noop */ }
    const appendObjRow = (obj, inGroup) => {
      const li = document.createElement("li");
      li.dataset.objectId = String(obj.id);
      const classes = [];
      if (obj.id === selectedManagedObjectId) classes.push("obj-list-active");
      if (isManagedObjectMapHidden(obj)) classes.push("obj-list-hidden");
      if (inGroup) classes.push("obj-list-in-group");
      if (classes.length) li.className = classes.join(" ");
      li.draggable = true;
      li.addEventListener("dragstart", (ev) => {
        if (ev.target && ev.target.closest && ev.target.closest(".obj-list-actions, .obj-list-name-input, input, button")) {
          ev.preventDefault();
          return;
        }
        li.classList.add("obj-list-dragging");
        try {
          ev.dataTransfer.effectAllowed = "move";
          ev.dataTransfer.setData("text/plain", "object:" + String(obj.id));
        } catch (_) { /* noop */ }
        window.__rkrkObjListDrag = { kind: "object", id: Number(obj.id) };
        window.__rkrkObjListDragId = obj.id;
      });
      li.addEventListener("dragend", () => {
        li.classList.remove("obj-list-dragging");
        window.__rkrkObjListDrag = null;
        window.__rkrkObjListDragId = null;
        list.querySelectorAll("li.obj-list-drag-over").forEach((el) => el.classList.remove("obj-list-drag-over"));
      });
      li.addEventListener("dragover", (ev) => {
        ev.preventDefault();
        try { ev.dataTransfer.dropEffect = "move"; } catch (_) { /* noop */ }
        list.querySelectorAll("li.obj-list-drag-over").forEach((el) => {
          if (el !== li) el.classList.remove("obj-list-drag-over");
        });
        li.classList.add("obj-list-drag-over");
      });
      li.addEventListener("dragleave", () => { li.classList.remove("obj-list-drag-over"); });
      li.addEventListener("drop", (ev) => {
        ev.preventDefault();
        ev.stopPropagation();
        li.classList.remove("obj-list-drag-over");
        let drag = window.__rkrkObjListDrag;
        try {
          const raw = String(ev.dataTransfer.getData("text/plain") || "");
          if (raw.indexOf("group:") === 0) drag = { kind: "group", id: Number(raw.slice(6)) };
          else if (raw.indexOf("object:") === 0) drag = { kind: "object", id: Number(raw.slice(7)) };
          else if (raw) drag = { kind: "object", id: Number(raw) };
        } catch (_) { /* noop */ }
        if (!drag && window.__rkrkObjListDragId != null) drag = { kind: "object", id: Number(window.__rkrkObjListDragId) };
        if (!drag) return;
        handleManagedListDrop(drag.kind, drag.id, "object", obj.id);
      });
      const selectCb = document.createElement("input");
      selectCb.type = "checkbox";
      selectCb.className = "obj-list-select";
      selectCb.title = "Für Gruppe auswählen";
      selectCb.checked = objListSelectedIds.has(Number(obj.id));
      selectCb.addEventListener("click", (ev) => ev.stopPropagation());
      selectCb.addEventListener("mousedown", (ev) => ev.stopPropagation());
      selectCb.addEventListener("change", () => {
        if (selectCb.checked) objListSelectedIds.add(Number(obj.id));
        else objListSelectedIds.delete(Number(obj.id));
      });
      const meta = document.createElement("div");
      meta.className = "obj-list-meta";
      const typeIcon = document.createElement("span");
      typeIcon.className = "obj-list-type-icon";
      typeIcon.setAttribute("aria-hidden", "true");
      typeIcon.title = String(obj.type || "Objekt");
      typeIcon.innerHTML = managedObjectTypeIconHtml(obj);
      const name = document.createElement("span");
      name.className = "obj-list-name";
      name.textContent = managedObjectDisplayName(obj);
      name.title = "Umbenennen";
      const startRename = (ev) => {
        if (ev) {
          ev.preventDefault();
          ev.stopPropagation();
        }
        if (name.dataset.renaming === "1") return;
        name.dataset.renaming = "1";
        const input = document.createElement("input");
        input.type = "text";
        input.className = "obj-list-name-input";
        input.value = managedObjectDisplayName(obj);
        input.title = "Umbenennen";
        input.setAttribute("aria-label", "Umbenennen");
        name.replaceWith(input);
        input.focus();
        input.select();
        let finished = false;
        const finish = (save) => {
          if (finished) return;
          finished = true;
          if (save) {
            const next = String(input.value || "").trim();
            obj.name = next || defaultManagedObjectName(obj);
            managedStatusText(`«${managedObjectDisplayName(obj)}» umbenannt.`);
          }
          refreshManagedObjectList();
        };
        input.addEventListener("keydown", (e) => {
          if (e.key === "Enter") {
            e.preventDefault();
            e.stopPropagation();
            finish(true);
          } else if (e.key === "Escape") {
            e.preventDefault();
            e.stopPropagation();
            finish(false);
          }
        });
        input.addEventListener("blur", () => finish(true));
        input.addEventListener("click", (e) => e.stopPropagation());
        input.addEventListener("mousedown", (e) => e.stopPropagation());
      };
      name.addEventListener("click", startRename);
      name.addEventListener("dblclick", startRename);
      const badge = document.createElement("span");
      badge.className = "obj-list-badge " + (obj.fixed ? "is-fixed" : "is-editing");
      badge.textContent = obj.fixed ? "fix" : "Bearb.";
      badge.title = obj.fixed ? "fixiert" : "in Bearbeitung";
      meta.appendChild(typeIcon);
      meta.appendChild(name);
      meta.appendChild(badge);
      const actions = document.createElement("div");
      actions.className = "obj-list-actions";
      const visBtn = document.createElement("button");
      visBtn.type = "button";
      visBtn.className = "obj-list-visibility";
      setObjToolButton(visBtn, obj.hidden ? "hide" : "show", obj.hidden ? "Auf Karte anzeigen" : "Auf Karte unterdrücken");
      visBtn.addEventListener("click", (ev) => {
        ev.preventDefault();
        ev.stopPropagation();
        toggleManagedObjectHidden(obj.id);
      });
      visBtn.addEventListener("mousedown", (e) => e.stopPropagation());
      actions.appendChild(visBtn);
      // In Objekte-Liste immer: Bearbeiten (fixiert) bzw. Abschliessen/Fixieren (in Bearbeitung)
      if (obj.fixed) {
        const editBtn = document.createElement("button");
        editBtn.type = "button";
        editBtn.className = "obj-list-edit";
        setObjToolButton(editBtn, "edit", "Objekt bearbeiten");
        try {
          const pointishList = (obj.type === "points" || obj.type === "textfields");
          if (!pointishList && typeof window.__rkrkObjectLockedByOther === "function" && window.__rkrkObjectLockedByOther(obj.id)) {
            editBtn.disabled = true;
            const who = (typeof window.__rkrkObjectLockName === "function") ? window.__rkrkObjectLockName(obj.id) : "";
            const lockTitle = who ? ("In Bearbeitung von " + who) : "Von anderem Editor gesperrt";
            editBtn.title = lockTitle;
            editBtn.setAttribute("aria-label", lockTitle);
          }
        } catch (_) { /* noop */ }
        editBtn.addEventListener("click", (ev) => {
          ev.preventDefault();
          ev.stopPropagation();
          if (editBtn.disabled) return;
          beginEditManagedObject(obj.id);
          try { refreshObjectActionButtons(); } catch (_) { /* noop */ }
        });
        actions.appendChild(editBtn);
        const delBtn = document.createElement("button");
        delBtn.type = "button";
        delBtn.className = "obj-list-delete";
        setObjToolButton(delBtn, "clear", "Löschen");
        delBtn.addEventListener("click", (ev) => {
          ev.preventDefault();
          ev.stopPropagation();
          if (!window.confirm(`«${managedObjectDisplayName(obj)}» löschen?`)) return;
          deleteManagedObjectById(obj.id);
        });
        actions.appendChild(delBtn);
      } else {
        const finishBtn = document.createElement("button");
        finishBtn.type = "button";
        finishBtn.className = "obj-list-fix";
        setObjToolButton(finishBtn, "fix", "Fixieren / Bearbeitung abschliessen");
        finishBtn.addEventListener("click", (ev) => {
          ev.preventDefault();
          ev.stopPropagation();
          // Sicherstellen, dass dieses Objekt aktiv ist und UI-Parameter übernommen werden
          if (Number(selectedManagedObjectId) !== Number(obj.id)) {
            ensureSingleEditingObject(obj.id, { sync: true });
            selectedManagedObjectId = obj.id;
            applyManagedObjectToUi(obj);
          } else {
            try { syncEditingObjectFromUi(); } catch (_) { /* noop */ }
          }
          const ok = (typeof finalizeCurrentObjectAsFixedRef === "function")
            ? finalizeCurrentObjectAsFixedRef()
            : false;
          if (!ok) {
            managedStatusText("Abschliessen nicht möglich — Objekt prüfen (Punkte/Linie/Anker).");
          }
          try { refreshManagedObjectList(); } catch (_) { /* noop */ }
          try { refreshObjectActionButtons(); } catch (_) { /* noop */ }
        });
        actions.appendChild(finishBtn);
        const delEditBtn = document.createElement("button");
        delEditBtn.type = "button";
        delEditBtn.className = "obj-list-delete";
        setObjToolButton(delEditBtn, "clear", "Objekt löschen");
        delEditBtn.addEventListener("click", (ev) => {
          ev.preventDefault();
          ev.stopPropagation();
          if (!window.confirm(`«${managedObjectDisplayName(obj)}» löschen?`)) return;
          deleteManagedObjectById(obj.id);
          try { refreshManagedObjectList(); } catch (_) { /* noop */ }
          try { refreshObjectActionButtons(); } catch (_) { /* noop */ }
        });
        actions.appendChild(delEditBtn);
        if (typeof canDiscardCurrentChanges === "function" && canDiscardCurrentChanges()) {
          const discardBtn = document.createElement("button");
          discardBtn.type = "button";
          discardBtn.className = "obj-list-discard";
          setObjToolButton(discardBtn, "discard", "Änderungen verwerfen");
          discardBtn.addEventListener("click", (ev) => {
            ev.preventDefault();
            ev.stopPropagation();
            if (Number(selectedManagedObjectId) !== Number(obj.id)) {
              selectedManagedObjectId = obj.id;
            }
            try { discardCurrentObjectChanges(); } catch (_) { /* noop */ }
            try { refreshManagedObjectList(); } catch (_) { /* noop */ }
            try { refreshObjectActionButtons(); } catch (_) { /* noop */ }
          });
          actions.appendChild(discardBtn);
        } else {
          // Falls kein Baseline-Discard: trotzdem Bearbeiten/Fokus erlauben
          const focusBtn = document.createElement("button");
          focusBtn.type = "button";
          focusBtn.className = "obj-list-edit";
          setObjToolButton(focusBtn, "edit", "Zum Bearbeiten anwählen");
          focusBtn.addEventListener("click", (ev) => {
            ev.preventDefault();
            ev.stopPropagation();
            beginEditManagedObject(obj.id);
            try { refreshObjectActionButtons(); } catch (_) { /* noop */ }
          });
          actions.appendChild(focusBtn);
        }
      }
      actions.addEventListener("mousedown", (e) => e.stopPropagation());
      li.appendChild(selectCb);
      li.appendChild(meta);
      li.appendChild(actions);
      list.appendChild(li);
    };
    const renderedIds = new Set();
    syncManagedListOrder();
    managedListOrder.forEach((entry) => {
      if (!entry) return;
      if (entry.kind === "group") {
        const g = getManagedObjectGroup(entry.id);
        if (!g) return;
        const members = visibleObjects.filter((o) => Number(o.groupId) === Number(g.id));
        list.appendChild(createManagedObjectGroupRow(g, members.length));
        members.forEach((obj) => {
          renderedIds.add(Number(obj.id));
          if (!g.collapsed) appendObjRow(obj, true);
        });
        return;
      }
      if (entry.kind === "object") {
        const obj = visibleObjects.find((o) => Number(o.id) === Number(entry.id));
        if (!obj) return;
        if (obj.groupId != null && getManagedObjectGroup(obj.groupId)) return;
        renderedIds.add(Number(obj.id));
        appendObjRow(obj, false);
      }
    });
    visibleObjects.forEach((obj) => {
      if (renderedIds.has(Number(obj.id))) return;
      if (obj.groupId != null && !getManagedObjectGroup(obj.groupId)) {
        try { delete obj.groupId; } catch (_) { obj.groupId = null; }
      }
      if (obj.groupId != null && getManagedObjectGroup(obj.groupId)) return;
      appendObjRow(obj, false);
    });
  };
  syncEditingObjectFromUi = function () {
    if (!selectedManagedObjectId) return false;
    if (window.__applyingManagedObjectToUi || window.__applyingDrawingState) return false;
    const idx = managedObjects.findIndex((o) => o.id === selectedManagedObjectId);
    if (idx < 0) return false;
    const prev = managedObjects[idx];
    // Nur während aktiver Bearbeitung (unfixiert) UI zurückschreiben — kein Duplikat.
    if (prev && prev.fixed) return false;
    const payload = currentObjectPayloadFromUi();
    if (!payload) return false;
    if (payload.type === "polygon" && (!payload.polygonVertices || payload.polygonVertices.length < 3)) return false;
    if ((payload.type === "points" || payload.type === "textfields") && (!payload.labels || !payload.labels.length)) return false;
    if ((payload.type === "freehand" || payload.type === "pfeil") && (!payload.coordinates || payload.coordinates.length < 2)) return false;
    const merged = {
      ...prev,
      ...payload,
      fixed: false,
      id: prev.id,
      name: prev.name
    };
    if (merged.type === "grid") {
      if (!merged.anchor && prev.anchor) merged.anchor = cloneLonLat(prev.anchor);
      // UI-Fills mit bisherigen mergen (mehrere Kacheln behalten)
      {
        const fromUi = (merged.cellFills && typeof merged.cellFills === "object") ? merged.cellFills : {};
        const fromPrev = (prev.cellFills && typeof prev.cellFills === "object") ? prev.cellFills : {};
        if (Object.keys(fromUi).length || Object.keys(fromPrev).length) {
          merged.cellFills = Object.assign({}, fromPrev, fromUi);
        }
      }
      if (!Number.isFinite(Number(merged.rotation)) && Number.isFinite(Number(prev.rotation))) {
        merged.rotation = prev.rotation;
      }
      if (prev.gridStyle && !payload.gridStyle) merged.gridStyle = prev.gridStyle;
    }
    managedObjects[idx] = merged;
    touchManagedObjectSyncMeta(managedObjects[idx]);
    if (managedObjects[idx].type === "circle") {
      delete managedObjects[idx].r1;
      delete managedObjects[idx].r2;
      delete managedObjects[idx].r3;
      delete managedObjects[idx].circlesStyles;
    }
    // Mehrere Labels nie in einem Objekt belassen
    if ((managedObjects[idx].type === "points" || managedObjects[idx].type === "textfields")
        && Array.isArray(managedObjects[idx].labels) && managedObjects[idx].labels.length > 1) {
      const keep = managedObjects[idx].labels[0];
      const extras = managedObjects[idx].labels.slice(1);
      const keepLabel = buildManagedLabelFromPoint(keep);
      const objType = labelObjectTypeForPoint(keepLabel, managedObjects[idx].type);
      managedObjects[idx] = {
        ...managedObjects[idx],
        type: objType,
        labels: [keepLabel],
        id: prev.id,
        name: prev.name
      };
      createManagedObjectsFromLabels(extras, objType, { fixed: true });
      labeledPoints = [keep];
      if (typeof selectedLabelPointIndex !== "undefined") selectedLabelPointIndex = 0;
    }
    return true;
  };
  deleteManagedObjectById = function (id) {
    rememberFixedHistory();
    const gone = managedObjects.find((o) => o.id === id);
    const label = gone ? managedObjectDisplayName(gone) : `Objekt ${id}`;
    managedObjects = managedObjects.filter((o) => o.id !== id);
    if (selectedManagedObjectId === id || Number(selectedManagedObjectId) === Number(id)) {
      selectedManagedObjectId = null;
      managedEditPaused = false;
      editBaselineSnapshot = null;
      if (typeof clearPreview === "function") clearPreview();
      try {
        const mode = normalizeObjectType(exportTypeEl ? exportTypeEl.value : "grid");
        resetDraftAfterFix(mode);
      } catch (_) { /* noop */ }
    }
    renderManagedObjects();
    try { refreshManagedObjectList(); } catch (_) { /* noop */ }
    try { refreshObjectActionButtons(); } catch (_) { /* noop */ }
    markFixedHistoryCursor();
    managedStatusText(`«${label}» gelöscht.`);
    try { if (typeof notifyLiveShareChange === "function") notifyLiveShareChange(); } catch (_) { /* noop */ }
  };
  beginEditManagedObject = function (id) {
    const obj = managedObjects.find((o) => o.id === id);
    if (!obj) return;
    if (obj.fixed) {
      const name = managedObjectDisplayName(obj);
      if (!window.confirm(`«${name}» ist fixiert.\nBearbeiten / ändern?`)) return;
    }
    // Vorheriges Objekt speichern/fixieren — nur dieses eine bleibt in Bearbeitung
    ensureSingleEditingObject(obj.id, { sync: true });
    editBaselineSnapshot = JSON.parse(JSON.stringify(obj));
    obj.fixed = false;
    try { touchManagedObjectSyncMeta(obj); } catch (_) { /* noop */ }
    selectedManagedObjectId = obj.id;
    managedEditPaused = false;
    applyManagedObjectToUi(obj);
    if (obj.type === "grid" || obj.type === "circles" || obj.type === "circle") {
      if (typeof previewActive !== "undefined") previewActive = true;
      if (typeof drawPreview === "function" && Array.isArray(selectedLonLat) && selectedLonLat.length === 2) {
        drawPreview();
      }
      if (obj.type === "grid") applyGridRotation(getGridRotationAngle(), { immediate: true, force: true });
    } else if (obj.type === "polygon") {
      if (typeof renderPolygonEditor === "function") renderPolygonEditor();
    } else if (obj.type === "freehand" || obj.type === "pfeil") {
      renderFreehandDraftPreview();
    } else if (obj.type === "points" || obj.type === "textfields") {
      const renderFn = (typeof renderLabeledPoints === "function")
        ? (renderLabeledPoints.__objListSyncOrig || renderLabeledPoints)
        : null;
      if (renderFn) renderFn();
    }
    renderManagedObjects();
    try { refreshManagedObjectList(); } catch (_) { /* noop */ }
    try { if (typeof updateStyleBoxVisibility === "function") updateStyleBoxVisibility(); } catch (_) { /* noop */ }
    try { if (typeof updateSwatches === "function") updateSwatches(); } catch (_) { /* noop */ }
    managedStatusText(`«${managedObjectDisplayName(obj)}»: in Bearbeitung — Parameter ändern, dann Fixieren.`);
  };
  function reorderManagedObject(fromIdx, toIdx) {
    const from = Number(fromIdx);
    const to = Number(toIdx);
    if (!Number.isInteger(from) || !Number.isInteger(to)) return false;
    if (from < 0 || to < 0 || from >= managedObjects.length || to >= managedObjects.length) return false;
    if (from === to) return false;
    rememberFixedHistory();
    const [item] = managedObjects.splice(from, 1);
    managedObjects.splice(to, 0, item);
    touchManagedObjectSyncMeta(item);
    renderManagedObjects();
    markFixedHistoryCursor();
    try { refreshManagedObjectList(); } catch (_) { /* noop */ }
    try { if (typeof notifyLiveShareChange === "function") notifyLiveShareChange(); } catch (_) { /* noop */ }
    return true;
  }

  function toggleManagedObjectHidden(id) {
    const idx = managedObjects.findIndex((o) => Number(o.id) === Number(id));
    if (idx < 0) return false;
    rememberFixedHistory();
    const obj = managedObjects[idx];
    obj.hidden = !obj.hidden;
    touchManagedObjectSyncMeta(obj);
    renderManagedObjects();
    markFixedHistoryCursor();
    try { refreshManagedObjectList(); } catch (_) { /* noop */ }
    try { if (typeof notifyLiveShareChange === "function") notifyLiveShareChange(); } catch (_) { /* noop */ }
    const label = managedObjectDisplayName(obj);
    managedStatusText(obj.hidden
      ? `«${label}»: auf Karte unterdrückt.`
      : `«${label}»: auf Karte angezeigt.`);
    return true;
  }

  moveManagedObjectZOrder = function (id, direction) {
    const idx = managedObjects.findIndex((o) => o.id === id);
    if (idx < 0) return;
    const target = idx + (Number(direction) > 0 ? 1 : -1);
    if (target < 0 || target >= managedObjects.length) {
      managedStatusText(direction > 0 ? "Bereits ganz vorne." : "Bereits ganz hinten.");
      return;
    }
    reorderManagedObject(idx, target);
    const label = managedObjectDisplayName(managedObjects[target]);
    managedStatusText(direction > 0
      ? `«${label}»: eine Ebene nach vorne.`
      : `«${label}»: eine Ebene nach hinten.`);
  };


  function touchManagedObjectSyncMeta(obj) {
    if (!obj || typeof obj !== "object") return obj;
    obj.sync_at = new Date().toISOString();
    try {
      if (typeof window.__rkrkGetMultiEditorId === "function") {
        const ed = String(window.__rkrkGetMultiEditorId() || "");
        if (ed) obj.updated_by = ed;
      }
    } catch (_) { /* noop */ }
    return obj;
  }


  function isPointLikeManagedObject(obj) {
    if (!obj) return false;
    const t = String(obj.type || "");
    return t === "points" || t === "textfields";
  }
  try { window.__rkrkIsPointLikeManagedObject = isPointLikeManagedObject; } catch (_) {}

  /** Unfixierte Objekte nur beim Origin-Editor anzeigen. */
  function isManagedObjectVisibleToMe(obj) {
    if (!obj) return false;
    if (obj.fixed !== false) return true;
    let myId = "";
    try {
      if (typeof window.__rkrkGetMultiEditorId === "function") {
        myId = String(window.__rkrkGetMultiEditorId() || "");
      }
    } catch (_) { /* noop */ }
    const by = String(obj.updated_by || "");
    if (!myId) {
      // Kein Multi-Edit / Solo: lokale Entwuerfe anzeigen
      return true;
    }
    if (!by) return true; // noch ungestempelt = lokal
    return by === myId;
  }
  try { window.__rkrkIsManagedObjectVisibleToMe = isManagedObjectVisibleToMe; } catch (_) {}

  function pushLonLatPoint(pts, lon, lat) {
    const x = Number(lon);
    const y = Number(lat);
    if (!Number.isFinite(x) || !Number.isFinite(y)) return;
    pts.push([x, y]);
  }

  function collectManagedObjectLonLats(obj) {
    const pts = [];
    if (!obj || isManagedObjectMapHidden(obj)) return pts;
    if (Array.isArray(obj.anchor) && obj.anchor.length >= 2) {
      pushLonLatPoint(pts, obj.anchor[0], obj.anchor[1]);
      const t = String(obj.type || "");
      if ((t === "circle" || t === "circles") && typeof createCircleCoordinates === "function") {
        const lat = Number(obj.anchor[1]);
        const lon = Number(obj.anchor[0]);
        const radii = t === "circles"
          ? [obj.r1, obj.r2, obj.r3].map((r) => Math.max(1, Number(r) || 1))
          : [Math.max(1, Number(obj.r) || 1)];
        radii.forEach((radius) => {
          const ring = createCircleCoordinates(lat, lon, radius, 24);
          (ring || []).forEach((p) => pushLonLatPoint(pts, p[0], p[1]));
        });
      }
      if (t === "grid" && typeof rotatedOffsetLatLon === "function") {
        const lat = Number(obj.anchor[1]);
        const lon = Number(obj.anchor[0]);
        const spacing = Math.max(1, Number(obj.spacing) || 333);
        const northCount = Math.max(1, Math.min(200, Number(obj.northCount) || 8));
        const eastCount = Math.max(1, Math.min(26, Number(obj.eastCount) || 8));
        const rot = Number(obj.rotation) || 0;
        const corners = [
          rotatedOffsetLatLon(lat, lon, 0, 0, rot),
          rotatedOffsetLatLon(lat, lon, 0, eastCount * spacing, rot),
          rotatedOffsetLatLon(lat, lon, northCount * spacing, eastCount * spacing, rot),
          rotatedOffsetLatLon(lat, lon, northCount * spacing, 0, rot)
        ];
        corners.forEach((c) => {
          if (Array.isArray(c) && c.length >= 2) pushLonLatPoint(pts, c[1], c[0]);
        });
      }
    }
    if (Array.isArray(obj.polygonVertices)) {
      obj.polygonVertices.forEach((p) => {
        if (Array.isArray(p) && p.length >= 2) pushLonLatPoint(pts, p[0], p[1]);
      });
    }
    if (Array.isArray(obj.labels)) {
      obj.labels.forEach((lp) => {
        if (lp) pushLonLatPoint(pts, lp.lon, lp.lat);
      });
    }
    if (Array.isArray(obj.coordinates)) {
      obj.coordinates.forEach((p) => {
        if (Array.isArray(p) && p.length >= 2) pushLonLatPoint(pts, p[0], p[1]);
      });
    }
    return pts;
  }

  function isFiniteExtent(ext) {
    return Array.isArray(ext) && ext.length === 4 && ext.every((v) => Number.isFinite(v))
      && !(typeof ol !== "undefined" && ol.extent && typeof ol.extent.isEmpty === "function" && ol.extent.isEmpty(ext));
  }

  function unionExtents(a, b) {
    if (!isFiniteExtent(a)) return isFiniteExtent(b) ? b.slice() : null;
    if (!isFiniteExtent(b)) return a.slice();
    if (typeof ol !== "undefined" && ol.extent && typeof ol.extent.extend === "function") {
      const out = a.slice();
      ol.extent.extend(out, b);
      return out;
    }
    return [
      Math.min(a[0], b[0]),
      Math.min(a[1], b[1]),
      Math.max(a[2], b[2]),
      Math.max(a[3], b[3])
    ];
  }

  function extentFromLonLatPoints(lonLatPoints) {
    if (!Array.isArray(lonLatPoints) || !lonLatPoints.length) return null;
    const mapCoords = lonLatPoints.map((p) => {
      if (typeof lonLatToMapCoord === "function") return lonLatToMapCoord(p[0], p[1]);
      return (typeof ol !== "undefined" && ol.proj) ? ol.proj.fromLonLat([p[0], p[1]]) : p;
    }).filter((c) => Array.isArray(c) && c.length >= 2 && Number.isFinite(c[0]) && Number.isFinite(c[1]));
    if (!mapCoords.length) return null;
    const xs = mapCoords.map((c) => c[0]);
    const ys = mapCoords.map((c) => c[1]);
    return [Math.min(...xs), Math.min(...ys), Math.max(...xs), Math.max(...ys)];
  }

  function collectLoadedObjectExtent() {
    let extent = null;
    try {
      if (managedObjectSource && typeof managedObjectSource.getFeatures === "function"
          && managedObjectSource.getFeatures().length > 0
          && typeof managedObjectSource.getExtent === "function") {
        const srcExtent = managedObjectSource.getExtent();
        if (isFiniteExtent(srcExtent)) extent = unionExtents(extent, srcExtent);
      }
    } catch (_) { /* noop */ }

    if (!extent) {
      const lonLatPoints = [];
      (Array.isArray(managedObjects) ? managedObjects : []).forEach((obj) => {
        collectManagedObjectLonLats(obj).forEach((p) => lonLatPoints.push(p));
      });
      extent = unionExtents(extent, extentFromLonLatPoints(lonLatPoints));
    }

    try {
      (Array.isArray(importedKmlLayers) ? importedKmlLayers : []).forEach((entry) => {
        if (!entry || entry.visible === false || !entry.layer) return;
        const src = typeof entry.layer.getSource === "function" ? entry.layer.getSource() : null;
        if (!src || typeof src.getExtent !== "function") return;
        const kmlExt = src.getExtent();
        if (isFiniteExtent(kmlExt)) extent = unionExtents(extent, kmlExt);
      });
    } catch (_) { /* noop */ }

    return isFiniteExtent(extent) ? extent : null;
  }

  function fitViewToLoadedObjects(opts) {
    if (typeof map === "undefined" || !map || typeof map.getView !== "function") return false;
    const animate = !(opts && opts.animate === false);
    const retry = Number((opts && opts.retry) || 0);
    let extent = collectLoadedObjectExtent();
    if (!extent) return false;

    try { map.updateSize(); } catch (_) { /* noop */ }
    const size = (typeof map.getSize === "function") ? map.getSize() : null;
    const w = Array.isArray(size) ? Number(size[0]) : 0;
    const h = Array.isArray(size) ? Number(size[1]) : 0;
    if (!(w > 8 && h > 8) && retry < 8) {
      setTimeout(function () {
        try { fitViewToLoadedObjects({ animate, retry: retry + 1 }); } catch (_) { /* noop */ }
      }, 80);
      return true;
    }

    try { window.__rkrkSuppressAutoLocate = true; } catch (_) { /* noop */ }
    const view = map.getView();
    const padX = Math.max(28, Math.round((w > 8 ? w : 800) * 0.08));
    const padY = Math.max(28, Math.round((h > 8 ? h : 600) * 0.08));
    const minSpan = 40;
    if (Math.abs(extent[2] - extent[0]) < minSpan) {
      const mid = (extent[0] + extent[2]) / 2;
      extent[0] = mid - minSpan / 2;
      extent[2] = mid + minSpan / 2;
    }
    if (Math.abs(extent[3] - extent[1]) < minSpan) {
      const mid = (extent[1] + extent[3]) / 2;
      extent[1] = mid - minSpan / 2;
      extent[3] = mid + minSpan / 2;
    }
    const fitOpts = {
      size: (w > 8 && h > 8) ? [w, h] : size,
      padding: [padY, padX, padY, padX],
      maxZoom: 17,
      nearest: false
    };
    if (animate) fitOpts.duration = 320;
    try {
      view.fit(extent, fitOpts);
    } catch (_) {
      try {
        view.setCenter([(extent[0] + extent[2]) / 2, (extent[1] + extent[3]) / 2]);
        if (typeof view.setZoom === "function") view.setZoom(15);
      } catch (__) { /* noop */ }
    }
    return true;
  }
  try { window.__rkrkFitViewToLoadedObjects = fitViewToLoadedObjects; } catch (_) {}

  function patchDrawingStorageForManagedObjects() {
    if (typeof getDrawingState === "function" && !getDrawingState.__objManagedPatched) {
      const originalGet = getDrawingState;
      getDrawingState = function () {
        syncEditingObjectFromUi();
        const state = originalGet();
        if (state && typeof state === "object") {
          state.managedObjects = managedObjects.map((o) => JSON.parse(JSON.stringify(o)));
          state.managedObjectGroups = managedObjectGroups.map((g) => JSON.parse(JSON.stringify(g)));
          state.managedListOrder = managedListOrder.map((e) => JSON.parse(JSON.stringify(e)));
          state.managedObjectIdSeq = managedObjectIdSeq;
          state.managedObjectGroupIdSeq = managedObjectGroupIdSeq;
          state.selectedManagedObjectId = selectedManagedObjectId;
          state.projectTitle = getOverlayEditorTitle();
        }
        return state;
      };
      getDrawingState.__objManagedPatched = true;
    }
    if (typeof applyDrawingState === "function" && !applyDrawingState.__objManagedPatched) {
      const originalApply = applyDrawingState;
      applyDrawingState = function (state) {
        window.__applyingDrawingState = true;
        try {
          // Soft-Merge (Live-Sync): UI/Auswahl nicht zuruecksetzen — nur managedObjects mergen
          if (window.__rkrkSoftMergingManaged) {
            if (state && Array.isArray(state.managedObjects)
                && typeof window.__rkrkReplaceManagedObjectsSoft === "function") {
              window.__rkrkReplaceManagedObjectsSoft(state.managedObjects);
              const maxId = managedObjects.reduce((m, o) => Math.max(m, Number(o.id) || 0), 0);
              managedObjectIdSeq = Math.max(Number(state.managedObjectIdSeq) || 1, maxId + 1);
            }
            if (state && Array.isArray(state.managedObjectGroups)) {
              managedObjectGroups = state.managedObjectGroups.map((g) => JSON.parse(JSON.stringify(g || {})));
              const maxG = managedObjectGroups.reduce((m, g) => Math.max(m, Number(g && g.id) || 0), 0);
              managedObjectGroupIdSeq = Math.max(Number(state.managedObjectGroupIdSeq) || 1, maxG + 1);
            }
            if (state && Array.isArray(state.managedListOrder)) {
              managedListOrder = state.managedListOrder.map((e) => JSON.parse(JSON.stringify(e || {})));
            }
            syncManagedListOrder();
            return;
          }
          // Broadcast-/View-Updates: Ausschnitt konstant — kein Sprung auf Homepunkt/Fit
          const preserveMapView = !!(window.__rkrkPreserveMapViewOnApply
            || (liveShareViewerMode && liveShareViewerViewReady)
            || (liveShareViewOnly && liveShareViewerViewReady));
          let savedView = null;
          if (preserveMapView && typeof map !== "undefined" && map && map.getView) {
            try {
              const v = map.getView();
              const c = v.getCenter();
              savedView = {
                center: Array.isArray(c) ? [Number(c[0]), Number(c[1])] : null,
                resolution: Number(v.getResolution()),
                zoom: Number(v.getZoom())
              };
            } catch (_) { savedView = null; }
          }
          try { window.__rkrkSuppressAutoLocate = true; } catch (_) { /* noop */ }
          if (preserveMapView) {
            try { window.__rkrkPreserveMapViewOnApply = true; } catch (_) { /* noop */ }
          } else {
            try { window.__rkrkDeferFitToManaged = true; } catch (_) { /* noop */ }
          }
          originalApply(state);
          if (state && state.projectTitle && typeof setProjectTitle === "function") {
            setProjectTitle(state.projectTitle);
          }
          if (state && Array.isArray(state.managedObjects)) {
            managedObjects = state.managedObjects.map((o) => {
              const copy = JSON.parse(JSON.stringify(o || {}));
              copy.fixed = o.fixed !== false;
              if (copy.type === "grid") {
                copy.cellFills = cloneCellFillsMap(copy.cellFills || (o && o.cellFills));
              }
              if (!copy.name || !String(copy.name).trim()) {
                copy.name = defaultManagedObjectName(copy);
              }
              return copy;
            });
            const maxId = managedObjects.reduce((m, o) => Math.max(m, Number(o.id) || 0), 0);
            managedObjectIdSeq = Math.max(Number(state.managedObjectIdSeq) || 1, maxId + 1);
            splitMultiLabelManagedObjects();
          } else {
            managedObjects = [];
          }
          if (state && Array.isArray(state.managedObjectGroups)) {
            managedObjectGroups = state.managedObjectGroups.map((g) => JSON.parse(JSON.stringify(g || {})));
            const maxG = managedObjectGroups.reduce((m, g) => Math.max(m, Number(g && g.id) || 0), 0);
            managedObjectGroupIdSeq = Math.max(Number(state.managedObjectGroupIdSeq) || 1, maxG + 1);
          } else {
            managedObjectGroups = [];
          }
          if (state && Array.isArray(state.managedListOrder)) {
            managedListOrder = state.managedListOrder.map((e) => JSON.parse(JSON.stringify(e || {})));
          } else {
            managedListOrder = [];
          }
          syncManagedListOrder();
          selectedManagedObjectId = null;
          managedEditPaused = false;
          if (typeof previewActive !== "undefined" && state && state.previewActive
            && Array.isArray(selectedLonLat) && selectedLonLat.length === 2
            && typeof drawPreview === "function"
            && exportTypeEl && (exportTypeEl.value === "grid" || exportTypeEl.value === "circles" || exportTypeEl.value === "circle")) {
            previewActive = true;
            drawPreview();
          }
          renderManagedObjects();
          if (preserveMapView) {
            // Ausschnitt wiederherstellen (originalApply ruft sonst fitViewToCurrentDrawing)
            if (savedView && typeof map !== "undefined" && map && map.getView) {
              try {
                const v = map.getView();
                if (savedView.center && Number.isFinite(savedView.center[0]) && Number.isFinite(savedView.center[1])) {
                  v.setCenter(savedView.center);
                }
                if (Number.isFinite(savedView.resolution) && savedView.resolution > 0) {
                  v.setResolution(savedView.resolution);
                } else if (Number.isFinite(savedView.zoom)) {
                  v.setZoom(savedView.zoom);
                }
              } catch (_) { /* noop */ }
            }
          } else {
            // Loaded objects should fill the map; saved mapView only if nothing to fit.
            let focused = false;
            try { focused = !!fitViewToLoadedObjects({ animate: true }); } catch (_) { focused = false; }
            if (!focused && state && state.mapView && typeof applySharedMapView === "function") {
              try {
                applySharedMapView(state, { animate: true });
                focused = true;
                try { window.__rkrkSuppressAutoLocate = true; } catch (_) { /* noop */ }
              } catch (_) { /* noop */ }
            }
            if (!focused && typeof fitViewToCurrentDrawing === "function") {
              try { fitViewToCurrentDrawing(); } catch (_) { /* noop */ }
            }
          }
          managedStatusText(managedObjects.length
            ? `${managedObjects.length} Objekt(e) geladen.`
            : "Zeichnung geladen (keine fixierten Objekte).");
        } finally {
          try { window.__rkrkPreserveMapViewOnApply = false; } catch (_) { /* noop */ }
          try { window.__rkrkDeferFitToManaged = false; } catch (_) { /* noop */ }
          window.__applyingDrawingState = false;
        }
      };
      applyDrawingState.__objManagedPatched = true;
    }
  }

  function patchManagedObjectInteractionsForEdit() {
    /* click handler already uses beginEditManagedObject */
  }

  function installEditHistoryHooks() {
    if (window.__objEditHistoryHooksInstalled) return;
    window.__objEditHistoryHooksInstalled = true;
    document.addEventListener("keydown", (evt) => {
      const key = String(evt.key || "").toLowerCase();
      const mod = evt.ctrlKey || evt.metaKey;
      if (!mod) return;
      const tag = (evt.target && evt.target.tagName) ? String(evt.target.tagName).toLowerCase() : "";
      if (tag === "input" || tag === "textarea" || tag === "select") return;
      if (key === "z" && !evt.shiftKey) {
        evt.preventDefault();
        undoFixedStep();
      } else if (key === "y" || (key === "z" && evt.shiftKey)) {
        evt.preventDefault();
        redoFixedStep();
      }
    });
  }


  const SHARE_API = "share_drawing.php";
  let liveShareToken = "";
  let liveShareActive = false;
  let liveSharePushTimer = null;
  let liveShareHeartbeatTimer = null;
  let liveShareLastUpdated = "";
  let liveShareLastHeartbeat = "";
  let liveShareViewerTimer = null;
  let liveShareViewerMode = false;
  /** Nach erstem Broadcast-Apply: Ausschnitt nicht mehr per Objekt-Update aendern. */
  let liveShareViewerViewReady = false;
  let liveShareViewerBroken = false;
  let liveShareViewerCount = 0;
  let liveShareEditorCount = 0;
  const LIVE_SHARE_TOKEN_KEY = "rkrk_live_share_token_v1";
  const LIVE_SHARE_VIEWER_ID_KEY = "rkrk_live_share_viewer_id_v1";
  const VIEW_SHARE_TOKEN_KEY = "rkrk_view_share_token_v1";
  let liveShareViewOnly = false;

  function loadPersistedShareToken() {
    try {
      const t = String(localStorage.getItem(LIVE_SHARE_TOKEN_KEY) || "").trim().toLowerCase();
      if (/^[a-z0-9]{8,64}$/.test(t)) return t;
    } catch (_) { /* noop */ }
    return "";
  }

  function persistShareToken(token) {
    const t = String(token || "").trim().toLowerCase();
    if (!/^[a-z0-9]{8,64}$/.test(t)) return;
    liveShareToken = t;
    try { localStorage.setItem(LIVE_SHARE_TOKEN_KEY, t); } catch (_) { /* noop */ }
  }

  function getOrCreateViewerId() {
    try {
      let id = String(localStorage.getItem(LIVE_SHARE_VIEWER_ID_KEY) || "").trim().toLowerCase();
      if (/^[a-z0-9_-]{8,64}$/.test(id)) return id;
      const rnd = (typeof crypto !== "undefined" && crypto.getRandomValues)
        ? Array.from(crypto.getRandomValues(new Uint8Array(12)), (b) => b.toString(16).padStart(2, "0")).join("")
        : String(Date.now()) + Math.random().toString(16).slice(2);
      id = ("v" + rnd).replace(/[^a-z0-9_-]/g, "").slice(0, 32);
      if (id.length < 8) id = (id + "xxxxxxxx").slice(0, 16);
      localStorage.setItem(LIVE_SHARE_VIEWER_ID_KEY, id);
      return id;
    } catch (_) {
      return "viewer" + String(Date.now()).slice(-10);
    }
  }


  /** Broadcast/Multi-Edit-Host nur mit URL-Code ADMIN, KOS oder INSTR (Flag oder code=). */
  function hasMapEditorShareUnlock() {
    try {
      const u = new URL(window.location.href);
      if (u.searchParams.has("ADMIN") || u.searchParams.has("KOS") || u.searchParams.has("INSTR")) return true;
      const code = String(u.searchParams.get("code") || "").trim().toUpperCase();
      return code === "ADMIN" || code === "KOS" || code === "INSTR";
    } catch (_) {
      return false;
    }
  }
  try { window.__rkrkHasMapEditorShareUnlock = hasMapEditorShareUnlock; } catch (_) {}

  function appendShareUnlockToUrl(u) {
    try {
      const src = new URL(window.location.href);
      if (src.searchParams.has("ADMIN")) {
        const v = src.searchParams.get("ADMIN");
        u.searchParams.set("ADMIN", v == null ? "" : v);
      }
      if (src.searchParams.has("KOS")) {
        const v = src.searchParams.get("KOS");
        u.searchParams.set("KOS", v == null ? "" : v);
      }
      if (src.searchParams.has("INSTR")) {
        const v = src.searchParams.get("INSTR");
        u.searchParams.set("INSTR", v == null ? "" : v);
      }
      if (src.searchParams.has("HUB")) {
        const v = src.searchParams.get("HUB");
        u.searchParams.set("HUB", v == null ? "" : v);
      }
      const code = String(src.searchParams.get("code") || "").trim();
      if (/^(ADMIN|KOS|INSTR|HUB)$/i.test(code)) u.searchParams.set("code", code.toUpperCase());
    } catch (_) { /* noop */ }
    return u;
  }
  function getShareTokenFromUrl() {
    try {
      const u = new URL(window.location.href);
      return String(u.searchParams.get("share") || "").trim();
    } catch (_) {
      return "";
    }
  }

  function buildShareUrl(token) {
    const tok = String(token || "").trim();
    if (!tok) return "";
    try {
      // Immer Overlay-Viewer-URL (Karte + Objekte + Einstellungen + Ansicht), ohne Host-Unlock-Params
      const u = new URL("rkrk_map_overlay.php", window.location.href);
      u.search = "";
      u.hash = "";
      u.searchParams.set("share", tok);
      return u.toString();
    } catch (_) {
      try {
        const origin = String(window.location.origin || "");
        const path = String(window.location.pathname || "").replace(/[^/]+$/, "");
        return `${origin}${path}rkrk_map_overlay.php?share=${encodeURIComponent(tok)}`;
      } catch (__) {
        return `rkrk_map_overlay.php?share=${encodeURIComponent(tok)}`;
      }
    }
  }

  /** Raster-Kachelfarben vor Share/Link in managedObjects + grid.cellFills schreiben. */
  function flushGridCellFillsForShare() {
    try {
      if (typeof syncEditingObjectFromUi === "function") syncEditingObjectFromUi();
    } catch (_) { /* noop */ }
    const uiFills = (typeof currentCellFillsFromUi === "function") ? currentCellFillsFromUi() : {};
    (Array.isArray(managedObjects) ? managedObjects : []).forEach((o) => {
      if (!o || o.type !== "grid") return;
      const prev = (o.cellFills && typeof o.cellFills === "object") ? o.cellFills : {};
      const isEditing = !o.fixed && selectedManagedObjectId != null
        && Number(o.id) === Number(selectedManagedObjectId);
      if (isEditing) {
        o.cellFills = Object.assign({}, cloneCellFillsMap(prev), uiFills);
      } else {
        o.cellFills = cloneCellFillsMap(prev);
      }
    });
    return uiFills;
  }

  function collectCurrentShareDrawing(opts) {
    if (typeof getDrawingState !== "function") {
      throw new Error("Zeichnungszustand nicht verfügbar.");
    }
    const viewSnapshot = !!(opts && opts.viewSnapshot);
    const uiFills = flushGridCellFillsForShare();
    const drawing = getDrawingState();
    try {
      if (drawing && typeof drawing === "object") {
        if (!drawing.grid || typeof drawing.grid !== "object") drawing.grid = {};
        const fromState = (drawing.grid.cellFills && typeof drawing.grid.cellFills === "object")
          ? cloneCellFillsMap(drawing.grid.cellFills) : {};
        drawing.grid.cellFills = Object.assign({}, fromState, uiFills);
        if (Array.isArray(drawing.managedObjects)) {
          drawing.managedObjects = drawing.managedObjects.map((o) => {
            const copy = JSON.parse(JSON.stringify(o));
            if (viewSnapshot) copy.fixed = true;
            if (copy.type === "grid") {
              copy.cellFills = cloneCellFillsMap(copy.cellFills);
            }
            return copy;
          });
        }
      }
    } catch (_) { /* noop */ }
    try {
      if (drawing && typeof map !== "undefined" && map && map.getView) {
        const view = map.getView();
        const center = view.getCenter();
        const zoom = view.getZoom();
        if (Array.isArray(center) && center.length >= 2) {
          try { map.updateSize(); } catch (_) { /* noop */ }
          const resolution = (typeof view.getResolution === "function") ? Number(view.getResolution()) : NaN;
          const size = (typeof map.getSize === "function") ? map.getSize() : null;
          const dpr = (typeof window !== "undefined" && Number(window.devicePixelRatio)) || 1;
          drawing.mapView = {
            center: [Number(center[0]), Number(center[1])],
            zoom: Number(zoom),
            resolution: Number.isFinite(resolution) ? resolution : undefined,
            size: (Array.isArray(size) && size.length >= 2)
              ? [Number(size[0]) || 0, Number(size[1]) || 0]
              : undefined,
            pixelRatio: Number.isFinite(dpr) && dpr > 0 ? dpr : 1
          };
        }
      }
    } catch (_) { /* noop */ }
    try {
      if (drawing) drawing.mapChrome = collectMapChromeState();
    } catch (_) { /* noop */ }
    return drawing;
  }

  /** Statische Ansicht teilen — berührt Broadcast/Live-Follow nicht.
   *  Erneuter Klick: gleicher Token/Link, Zeichnung wird ersetzt (aktualisiert). */
  async function copyStaticMapViewShareLink() {
    const drawing = collectCurrentShareDrawing({ viewSnapshot: true });
    let token = "";
    try {
      token = String(localStorage.getItem(VIEW_SHARE_TOKEN_KEY) || "").trim().toLowerCase();
      if (!/^[a-z0-9]{8,64}$/.test(token)) token = "";
    } catch (_) {
      token = "";
    }
    // Broadcast-Token nicht mit view_only überschreiben — eigener Ansicht-Token
    if (token && liveShareToken && token === String(liveShareToken).toLowerCase()) token = "";
    const reused = !!token;

    const res = await fetch(SHARE_API, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        token: token || undefined,
        active: true,
        view_only: true,
        drawing
      }),
      cache: "no-store"
    });
    const payload = await res.json().catch(() => ({}));
    if (!res.ok || !payload.ok || !payload.token) {
      throw new Error((payload && payload.error) ? String(payload.error) : `HTTP ${res.status}`);
    }
    const nextTok = String(payload.token || "").trim().toLowerCase();
    try {
      if (/^[a-z0-9]{8,64}$/.test(nextTok)) localStorage.setItem(VIEW_SHARE_TOKEN_KEY, nextTok);
    } catch (_) { /* noop */ }
    const url = buildShareUrl(nextTok);
    const copied = await copyTextToClipboard(url);
    return { url, copied, token: nextTok, updated: reused && nextTok === token };
  }
  try { window.__rkrkCopyStaticMapViewShareLink = copyStaticMapViewShareLink; } catch (_) {}

  async function copyCurrentShareViewLink() {
    const saved = await pushLiveShareState(true);
    const token = String((saved && saved.token) || liveShareToken || "").trim();
    if (!token) throw new Error("Kein Share-Token");
    const url = buildShareUrl(token);
    const btn = document.getElementById("liveBroadcastBtn");
    if (btn) setBroadcastButtonUi(btn, true);
    startLiveShareHeartbeat();
    try { document.body.classList.add("rkrk-share-host"); } catch (_) { /* noop */ }
    updateConnectedDevicesUi(
      (saved && saved.viewer_count) || liveShareViewerCount || 0,
      (saved && saved.editor_count) || liveShareEditorCount || 0
    );
    const copied = await copyTextToClipboard(url);
    scheduleLiveSharePush();
    return { url, copied, token };
  }
  try { window.__rkrkCopyCurrentShareViewLink = copyCurrentShareViewLink; } catch (_) {}
  try { window.__rkrkBuildShareUrl = buildShareUrl; } catch (_) {}

  function formatViewerCountLabel(viewerCount, editorCount) {
    const viewers = Math.max(0, Number(viewerCount) || 0);
    const editors = Math.max(0, Number(editorCount) || 0);
    const total = viewers + editors;
    const geraet = (n) => (n === 1 ? "1 Ger\u00e4t" : (n + " Ger\u00e4te"));
    if (total <= 0) return "0 Ger\u00e4te verbunden";
    if (editors > 0 && viewers > 0) {
      return geraet(total) + " verbunden ("
        + (editors === 1 ? "1 Editor" : (editors + " Editoren"))
        + ", "
        + (viewers === 1 ? "1 Zuschauer" : (viewers + " Zuschauer"))
        + ")";
    }
    if (editors > 0) {
      return geraet(total) + " verbunden ("
        + (editors === 1 ? "1 Editor" : (editors + " Editoren"))
        + ")";
    }
    return geraet(total) + " verbunden";
  }

  function updateConnectedDevicesUi(viewerCount, editorCount) {
    if (viewerCount != null) liveShareViewerCount = Math.max(0, Number(viewerCount) || 0);
    if (editorCount != null) liveShareEditorCount = Math.max(0, Number(editorCount) || 0);
    const badge = document.getElementById("liveShareDeviceCount");
    const qrPanel = document.getElementById("liveShareQrPanel");
    const qrOpen = !!(qrPanel && qrPanel.classList.contains("show"));
    const label = formatViewerCountLabel(liveShareViewerCount, liveShareEditorCount);
    if (badge) {
      const show = liveShareActive && !liveShareViewerMode && !qrOpen;
      badge.style.display = show ? "block" : "none";
      badge.textContent = label;
      badge.title = "Zuschauer + Editoren (alle verbundenen Ger\u00e4te)";
    }
    const qrCount = document.getElementById("liveShareQrCount");
    if (qrCount) qrCount.textContent = label;
  }
  try { window.updateConnectedDevicesUi = updateConnectedDevicesUi; } catch (_) {}

  async function copyTextToClipboard(text) {
    try {
      if (navigator.clipboard && navigator.clipboard.writeText) {
        await navigator.clipboard.writeText(text);
        return true;
      }
    } catch (_) { /* fallback */ }
    try {
      const ta = document.createElement("textarea");
      ta.value = text;
      ta.style.position = "fixed";
      ta.style.left = "-9999px";
      document.body.appendChild(ta);
      ta.select();
      const ok = document.execCommand("copy");
      ta.remove();
      return ok;
    } catch (_) {
      return false;
    }
  }

  function ensureBroadcastCss() {
    if (document.getElementById("rkrkBroadcastCss")) return;
    const style = document.createElement("style");
    style.id = "rkrkBroadcastCss";
    style.textContent = ""
      + "#liveBroadcastBtn{position:fixed;top:12px;right:12px;z-index:40;width:40px;height:40px;min-width:40px;min-height:40px;max-height:40px;box-sizing:border-box;"
      + "padding:0;margin:0;display:inline-flex;align-items:center;justify-content:center;"
      + "border-radius:999px;border:1px solid #527642;background:#96B588;color:#fff;"
      + "line-height:1;box-shadow:0 2px 10px rgba(0,0,0,.18);cursor:pointer;}"
      + "body.rkrk-share-sub #liveBroadcastBtn{display:none!important;}"
      + "body.rkrk-share-sub #liveShareCopyLinkBtn{display:none!important;}"
      + "body.rkrk-share-sub #liveQrShowBtn{display:none!important;}"
      + "body.rkrk-share-sub #shareViewLinkBtn{display:none!important;}"
      + "#liveBroadcastBtn:hover{background:#7ea56f;}"
      + "#liveBroadcastBtn.is-live{background:#b91c1c;border-color:#7f1d1d;}"
      + "#liveBroadcastBtn.is-live:hover{background:#991b1b;}"
      + "#liveBroadcastBtn.can-resume{background:#b45309;border-color:#9a3412;}"
      + "#liveBroadcastBtn.can-resume:hover{background:#c2410c;}"
      + "#liveQrShowBtn{position:fixed;top:12px;right:108px;z-index:40;width:40px;height:40px;min-width:40px;min-height:40px;max-height:40px;box-sizing:border-box;"
      + "padding:0;margin:0;display:inline-flex;align-items:center;justify-content:center;"
      + "border-radius:999px;border:1px solid #527642;background:#fff;color:#527642;"
      + "line-height:1;box-shadow:0 2px 10px rgba(0,0,0,.18);cursor:pointer;}"
      + "#liveQrShowBtn:hover{background:#f0f7ed;}"
      + "#liveQrShowBtn.is-live{border-color:#166534;color:#166534;}"
      + "#liveShareCopyLinkBtn{position:fixed;top:12px;right:60px;z-index:40;width:40px;height:40px;min-width:40px;min-height:40px;max-height:40px;box-sizing:border-box;"
      + "padding:0;margin:0;display:inline-flex;align-items:center;justify-content:center;"
      + "border-radius:999px;border:1px solid #527642;background:#fff;color:#527642;"
      + "line-height:1;box-shadow:0 2px 10px rgba(0,0,0,.18);cursor:pointer;}"
      + "#liveShareCopyLinkBtn:hover{background:#f0f7ed;}"
      + "#liveShareCopyLinkBtn.is-live{border-color:#166534;color:#166534;background:#ecfdf5;}"
      + "#liveBroadcastBtn,#liveQrShowBtn,#liveShareCopyLinkBtn,#liveShareAnnounceOpenBtn,#liveShareViewFollowBtn{"
      + "height:40px!important;min-height:40px!important;max-height:40px!important;"
      + "box-sizing:border-box!important;line-height:1!important;"
      + "display:inline-flex!important;align-items:center!important;justify-content:center!important;}"
      + "#liveShareAnnounceComposer.collapsed{top:12px!important;height:40px;align-items:center;}"
      + "#liveShareAnnounceComposer:not(.collapsed){top:60px!important;right:12px;}"
      + "#liveShareDeviceCount{position:fixed;top:60px;right:12px;z-index:41;display:none;"
      + "background:rgba(15,23,42,.92);color:#fff;font-size:12px;line-height:1.35;"
      + "padding:6px 10px;border-radius:999px;box-shadow:0 2px 10px rgba(0,0,0,.18);}"
      + "#liveBroadcastToast{position:fixed;top:100px;right:12px;z-index:41;max-width:280px;"
      + "background:rgba(15,23,42,.92);color:#fff;font-size:12px;line-height:1.35;"
      + "padding:8px 10px;border-radius:8px;display:none;}"
      + "#liveBroadcastToast.show{display:block;}"
      + "#liveShareQrPanel{position:fixed;top:60px;right:12px;z-index:120;width:240px;"
      + "background:#fff;color:#0f172a;border:1px solid #cbd5e1;border-radius:12px;"
      + "box-shadow:0 8px 28px rgba(0,0,0,.22);padding:12px;display:none;}"
      + "#liveShareQrPanel.show{display:block!important;}"
      + "#liveShareQrPanel h3{margin:0 0 8px;font-size:13px;font-weight:600;}"
      + "#liveShareQrPanel #liveShareQrMedia{display:flex;justify-content:center;margin:0 0 8px;}#liveShareQrPanel canvas,#liveShareQrPanel img{display:block;width:196px;height:196px;margin:0 auto;background:#fff;}"
      + "#liveShareQrPanel .qr-meta{font-size:11px;color:#475569;text-align:center;margin:0 0 8px;}"
      + "#liveShareQrPanel .qr-actions{display:flex;gap:6px;}"
      + "#liveShareQrPanel button{flex:1;font-size:11px;padding:6px 8px;border-radius:8px;"
      + "border:1px solid #cbd5e1;background:#f8fafc;color:#0f172a;cursor:pointer;}"
      + "#liveShareQrPanel button.primary{background:#96B588;border-color:#527642;color:#fff;}"

      + "#liveShareViewerBadge{position:fixed;top:12px;left:50%;transform:translateX(-50%);"
      + "z-index:42;background:#0f172a;color:#fff;font-size:12px;padding:6px 12px;border-radius:999px;"
      + "box-shadow:0 2px 10px rgba(0,0,0,.2);display:none;}"
      + "#liveShareViewerBadge.is-live{background:#166534;}"
      + "#liveShareViewerBadge.is-ended{background:#7f1d1d;}"
      + "#liveShareViewerBadge.is-broken{background:#9a3412;}"
      + "html.rkrk-share-viewer,body.rkrk-share-viewer{margin:0!important;padding:0!important;width:100%!important;height:100%!important;height:100dvh!important;overflow:hidden!important;grid-template-columns:1fr!important;}"
      + "body.rkrk-share-viewer aside.panel,"
      + "body.rkrk-share-viewer .overlay-box,"
      + "body.rkrk-share-viewer .style-box,"
      + "body.rkrk-share-viewer #objSidebarToggleBtn,"
      + "body.rkrk-share-viewer #objSidebarResizeHandle,"
      + "body.rkrk-share-viewer #objManagedListPanel,"
      + "body.rkrk-share-viewer #sharePanelDetails,"
      + "body.rkrk-share-viewer #objManagedToolsTop,"
      + "body.rkrk-share-viewer #gridCellFillMenu,"
      + "body.rkrk-share-viewer #objObjectContextMenu,"
      + "body.rkrk-share-viewer #basemapTopBar,"
      + "body.rkrk-share-viewer .ol-zoom,"
      + "body.rkrk-share-viewer .ol-attribution,"
      + "body.rkrk-share-viewer #liveBroadcastBtn,"
      + "body.rkrk-share-viewer #liveQrShowBtn,"
      + "body.rkrk-share-viewer #liveShareDeviceCount,"
      + "body.rkrk-share-viewer #liveShareQrPanel{display:none!important;}"
      + "body.rkrk-share-viewer #map{position:fixed!important;inset:0!important;grid-column:1/-1!important;width:100%!important;width:100dvw!important;height:100%!important;height:100dvh!important;margin:0!important;padding:0!important;}"
      + "body.rkrk-share-viewer #liveShareViewerBadge{display:block!important;}";
    document.head.appendChild(style);
  }

  function showBroadcastToast(text, ms) {
    let toast = document.getElementById("liveBroadcastToast");
    if (!toast) {
      toast = document.createElement("div");
      toast.id = "liveBroadcastToast";
      document.body.appendChild(toast);
    }
    toast.textContent = text;
    toast.classList.add("show");
    clearTimeout(showBroadcastToast._t);
    showBroadcastToast._t = setTimeout(() => toast.classList.remove("show"), ms || 3200);
  }

  function ensureDeviceCountBadge() {
    let badge = document.getElementById("liveShareDeviceCount");
    if (!badge) {
      badge = document.createElement("div");
      badge.id = "liveShareDeviceCount";
      document.body.appendChild(badge);
    }
    return badge;
  }

  function hideHostShareQrPanel() {
    const panel = document.getElementById("liveShareQrPanel");
    if (panel) {
      panel.classList.remove("show");
      panel.style.display = "none";
    }
    updateConnectedDevicesUi(liveShareViewerCount, liveShareEditorCount);
  }

  function resolveQrCodeApi() {
    const cand = [
      window.QRCode,
      window.qrcode,
      (window.QRCode && window.QRCode.default)
    ];
    for (let i = 0; i < cand.length; i += 1) {
      const api = cand[i];
      if (api && typeof api.toCanvas === "function") return api;
    }
    return null;
  }

  function loadQrCodeLib() {
    const ready = resolveQrCodeApi();
    if (ready) return Promise.resolve(ready);
    return new Promise((resolve, reject) => {
      const finish = () => {
        const api = resolveQrCodeApi();
        if (api) resolve(api);
        else reject(new Error("QR-Bibliothek fehlgeschlagen"));
      };
      const existing = document.querySelector("script[data-rkrk-qrcode]");
      if (existing) {
        if (existing.dataset.loaded === "1") {
          finish();
          return;
        }
        existing.addEventListener("load", () => { existing.dataset.loaded = "1"; finish(); });
        existing.addEventListener("error", () => reject(new Error("QR-Bibliothek fehlgeschlagen")));
        return;
      }
      const s = document.createElement("script");
      s.src = "https://cdn.jsdelivr.net/npm/qrcode@1.5.4/build/qrcode.min.js";
      s.async = true;
      s.dataset.rkrkQrcode = "1";
      s.onload = () => { s.dataset.loaded = "1"; finish(); };
      s.onerror = () => reject(new Error("QR-Bibliothek fehlgeschlagen"));
      document.head.appendChild(s);
    });
  }

  function buildQrImageUrl(shareUrl) {
    return "https://api.qrserver.com/v1/create-qr-code/?size=196x196&ecc=M&margin=8&data="
      + encodeURIComponent(String(shareUrl || ""));
  }

  async function renderShareQrMedia(shareUrl) {
    const media = document.getElementById("liveShareQrMedia");
    if (!media) return false;
    media.innerHTML = "";
    const url = String(shareUrl || "");
    if (!url) return false;

    const img = document.createElement("img");
    img.id = "liveShareQrImg";
    img.width = 196;
    img.height = 196;
    img.alt = "Broadcast QR-Code";
    img.decoding = "async";
    img.src = buildQrImageUrl(url);
    media.appendChild(img);

    try {
      const QRCode = await loadQrCodeLib();
      const canvas = document.createElement("canvas");
      canvas.id = "liveShareQrCanvas";
      canvas.width = 196;
      canvas.height = 196;
      await QRCode.toCanvas(canvas, url, {
        width: 196,
        margin: 1,
        color: { dark: "#0f172a", light: "#ffffff" }
      });
      media.innerHTML = "";
      media.appendChild(canvas);
      return true;
    } catch (_) {
      return true;
    }
  }

  async function showHostShareQrPanel(url) {
    ensureBroadcastCss();
    ensureDeviceCountBadge();
    const shareUrl = String(url || "");
    let panel = document.getElementById("liveShareQrPanel");
    if (!panel) {
      panel = document.createElement("div");
      panel.id = "liveShareQrPanel";
      panel.innerHTML = ""
        + "<h3>Ansicht teilen</h3>"
        + '<p class="qr-meta" style="margin-top:0">Direkter Link: aktuelle Karte, Overlay-Einstellungen und Kartenansicht.</p>'
        + '<div id="liveShareQrMedia"></div>'
        + '<p class="qr-meta" id="liveShareQrCount">0 Geraete verbunden</p>'
        + '<p class="qr-meta" id="liveShareQrUrl" style="word-break:break-all;font-size:10px;"></p>'
        + '<div class="qr-actions">'
        + '<button type="button" class="primary" id="liveShareQrCopyBtn">Link kopieren</button>'
        + '<button type="button" id="liveShareQrCloseBtn">Schliessen</button>'
        + "</div>";
      document.body.appendChild(panel);
      panel.querySelector("#liveShareQrCloseBtn").addEventListener("click", hideHostShareQrPanel);
      panel.querySelector("#liveShareQrCopyBtn").addEventListener("click", async () => {
        const u = panel.dataset.shareUrl || "";
        const ok = u ? await copyTextToClipboard(u) : false;
        showBroadcastToast(ok ? "Link kopiert." : "Kopieren fehlgeschlagen.", 2200);
      });
    } else if (!document.getElementById("liveShareQrMedia")) {
      const oldCanvas = document.getElementById("liveShareQrCanvas");
      const media = document.createElement("div");
      media.id = "liveShareQrMedia";
      if (oldCanvas && oldCanvas.parentNode) oldCanvas.parentNode.replaceChild(media, oldCanvas);
      else {
        const countEl = panel.querySelector("#liveShareQrCount");
        if (countEl) panel.insertBefore(media, countEl);
        else panel.appendChild(media);
      }
    }
    if (!document.getElementById("liveShareQrUrl")) {
      const urlP = document.createElement("p");
      urlP.className = "qr-meta";
      urlP.id = "liveShareQrUrl";
      urlP.style.wordBreak = "break-all";
      urlP.style.fontSize = "10px";
      const actions = panel.querySelector(".qr-actions");
      if (actions) panel.insertBefore(urlP, actions);
      else panel.appendChild(urlP);
    }
    panel.dataset.shareUrl = shareUrl;
    const urlEl = document.getElementById("liveShareQrUrl");
    if (urlEl) urlEl.textContent = shareUrl;
    panel.classList.add("show");
    panel.style.display = "block";
    updateConnectedDevicesUi(liveShareViewerCount, liveShareEditorCount);
    const ok = await renderShareQrMedia(shareUrl);
    if (!ok) {
      showBroadcastToast("QR-Code konnte nicht erzeugt werden — Link unten / kopieren.", 3500);
    }
  }
  async function ensureBroadcastAndShowQr() {
    const btn = document.getElementById("liveBroadcastBtn");
    const res = await copyCurrentShareViewLink();
    const url = res.url;
    if (btn) setBroadcastButtonUi(btn, true);
    const copyBtn = document.getElementById("liveShareCopyLinkBtn");
    if (copyBtn) copyBtn.classList.add("is-live");
    await showHostShareQrPanel(url);
    showBroadcastToast(res.copied
      ? "Broadcast aktiv — Ansicht-Link kopiert / QR anzeigen."
      : `Broadcast aktiv — Link: ${url}`, 4500);
    const qrBtn = document.getElementById("liveQrShowBtn");
    if (qrBtn) qrBtn.classList.add("is-live");
    return url;
  }
  function setBroadcastButtonUi(btn, active) {
    try {
      const isSub = document.body && document.body.classList.contains("rkrk-share-sub");
      if (isSub && btn) {
        btn.style.display = "none";
        btn.setAttribute("hidden", "true");
        return;
      }
      if (btn) btn.removeAttribute("hidden");
    } catch (_) { /* noop */ }
    if (!btn) return;
    btn.classList.toggle("is-live", !!active);
    btn.classList.toggle("can-resume", !active && !!liveShareToken);
    btn.setAttribute("aria-pressed", active ? "true" : "false");
    let title = "Broadcast starten (Live-Follow Link)";
    if (active) title = "Broadcast stoppen";
    else if (liveShareToken) title = "Broadcast fortsetzen (gleicher Link)";
    btn.title = title;
    btn.setAttribute("aria-label", title);
    btn.innerHTML = active
      ? '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><rect x="6" y="6" width="12" height="12" rx="1.5" fill="currentColor"/></svg>'
      : '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M12 14a2 2 0 1 0 0-4 2 2 0 0 0 0 4zm-5.3-1a5.3 5.3 0 0 1 10.6 0h-2.1a3.2 3.2 0 0 0-6.4 0H6.7zm-3.5 0a8.8 8.8 0 0 1 17.6 0h-2.1a6.7 6.7 0 0 0-13.4 0H3.2z"/></svg>';
  }

  function setViewerBadge(state, text) {
    let badge = document.getElementById("liveShareViewerBadge");
    if (!badge) {
      badge = document.createElement("div");
      badge.id = "liveShareViewerBadge";
      document.body.appendChild(badge);
    }
    badge.style.display = "block";
    badge.classList.remove("is-live", "is-ended", "is-broken");
    if (state) badge.classList.add(state);
    badge.textContent = text;
  }


  function restoreShareEditorChrome() {
    liveShareViewerMode = false;
    try {
      if (window.__rkrkShareViewerMapOnlyT1) clearTimeout(window.__rkrkShareViewerMapOnlyT1);
      if (window.__rkrkShareViewerMapOnlyT2) clearTimeout(window.__rkrkShareViewerMapOnlyT2);
      window.__rkrkShareViewerMapOnlyT1 = null;
      window.__rkrkShareViewerMapOnlyT2 = null;
    } catch (_) {}
    const htmlEl = document.documentElement;
    const bodyEl = document.body;
    if (htmlEl) {
      htmlEl.classList.remove("rkrk-share-viewer");
      htmlEl.style.height = "";
      htmlEl.style.overflow = "";
    }
    if (bodyEl) {
      bodyEl.classList.remove("rkrk-share-viewer");
      bodyEl.classList.add("rkrk-share-coeditor");
      bodyEl.style.gridTemplateColumns = "";
      bodyEl.style.margin = "";
      bodyEl.style.height = "";
      bodyEl.style.overflow = "";
    }
    const panel = document.querySelector("aside.panel");
    if (panel) panel.style.display = "";
    ["overlayBox", "styleBox", "objManagedListPanel", "objSidebarToggleBtn", "objSidebarResizeHandle", "objManagedToolsTop", "basemapTopBar"].forEach((id) => {
      const el = document.getElementById(id);
      if (el) el.style.display = "";
    });
    const mapEl = document.getElementById("map");
    if (mapEl) {
      mapEl.style.position = "";
      mapEl.style.inset = "";
      mapEl.style.gridColumn = "";
      mapEl.style.width = "";
      mapEl.style.height = "";
      mapEl.style.margin = "";
      mapEl.style.padding = "";
    }
    const badge = document.getElementById("liveShareViewerBadge");
    if (badge) badge.style.display = "none";
    try {
      if (typeof map !== "undefined" && map && typeof map.updateSize === "function") map.updateSize();
    } catch (_) { /* noop */ }
    setTimeout(() => {
      try {
        if (typeof map !== "undefined" && map && typeof map.updateSize === "function") map.updateSize();
      } catch (_) { /* noop */ }
    }, 120);
    try { reapplyObjSidebarAfterRestore(); } catch (_) {}
  }

  function reapplyObjSidebarAfterRestore() {
    try {
      const collapsed = localStorage.getItem("obj_sidebar_collapsed_v1") === "1";
      document.body.classList.toggle("obj-sidebar-collapsed", !!collapsed);
      document.documentElement.classList.toggle("obj-sidebar-collapsed", !!collapsed);
      const panel = document.querySelector("aside.panel");
      const mapEl = document.getElementById("map");
      if (collapsed) {
        document.body.style.gridTemplateColumns = "1fr";
        if (panel) panel.style.display = "none";
        if (mapEl) {
          mapEl.style.gridColumn = "1 / -1";
          mapEl.style.width = "100%";
          mapEl.style.height = "100vh";
          mapEl.style.position = "";
          mapEl.style.inset = "";
        }
      } else {
        const w = Math.max(260, Math.min(680, Number(localStorage.getItem("obj_sidebar_width_px_v1")) || 360));
        document.body.style.gridTemplateColumns = w + "px 1fr";
        if (panel) panel.style.display = "";
        if (mapEl) {
          mapEl.style.gridColumn = "";
          mapEl.style.width = "100%";
          mapEl.style.height = "100vh";
          mapEl.style.position = "";
          mapEl.style.inset = "";
        }
      }
      if (typeof map !== "undefined" && map && typeof map.updateSize === "function") map.updateSize();
    } catch (_) { /* noop */ }
  }
  try { window.__rkrkReapplyObjSidebar = reapplyObjSidebarAfterRestore; } catch (_) {}

  try { window.__rkrkRestoreShareEditorChrome = restoreShareEditorChrome; } catch (_) {}

  function ensureShareViewerMapOnly() {
    try { window.__rkrkEnsureShareViewerMapOnly = ensureShareViewerMapOnly; } catch (_) {}

    // Sub-Editor / Co-Editor: Menü nicht kaputtmachen
    if (!liveShareViewerMode) return;
    if (document.body && document.body.classList.contains("rkrk-share-coeditor")) return;
    const htmlEl = document.documentElement;
    const bodyEl = document.body;
    const panel = document.querySelector("aside.panel");
    const mapEl = document.getElementById("map");
    if (htmlEl) {
      htmlEl.classList.add("rkrk-share-viewer");
      htmlEl.style.height = "100%";
      htmlEl.style.overflow = "hidden";
    }
    if (bodyEl) {
      bodyEl.style.gridTemplateColumns = "1fr";
      bodyEl.style.margin = "0";
      bodyEl.style.height = "100%";
      bodyEl.style.overflow = "hidden";
    }
    if (panel) panel.style.display = "none";
    ["overlayBox", "styleBox", "objManagedListPanel", "objSidebarToggleBtn", "objSidebarResizeHandle", "objManagedToolsTop", "basemapTopBar"].forEach((id) => {
      const el = document.getElementById(id);
      if (el) el.style.display = "none";
    });
    if (mapEl) {
      mapEl.style.position = "fixed";
      mapEl.style.inset = "0";
      mapEl.style.gridColumn = "1 / -1";
      mapEl.style.width = "100%";
      mapEl.style.height = "100%";
      mapEl.style.margin = "0";
      mapEl.style.padding = "0";
    }
    const bump = () => {
      if (typeof map !== "undefined" && map && typeof map.updateSize === "function") {
        try { map.updateSize(); } catch (_) { /* noop */ }
      }
      if (liveShareLastMapView) {
        try {
          // force: auch bei Ansicht-Link Layout-Bump mit dem *gehaltenen* Ausschnitt
          applySharedMapView({ mapView: liveShareLastMapView }, { animate: false, force: true });
        } catch (_) { /* noop */ }
      }
    };
    bump();
    requestAnimationFrame(bump);
    if (!window.__rkrkShareViewerResizeBound) {
      window.__rkrkShareViewerResizeBound = true;
      const onViewerLayout = () => {
        if (!liveShareViewerMode) return;
        clearTimeout(window.__rkrkShareViewerResizeT);
        window.__rkrkShareViewerResizeT = setTimeout(bump, 100);
      };
      window.addEventListener("resize", onViewerLayout);
      window.addEventListener("orientationchange", () => {
        if (!liveShareViewerMode) return;
        setTimeout(bump, 250);
        setTimeout(bump, 700);
      });
      if (window.visualViewport && typeof window.visualViewport.addEventListener === "function") {
        window.visualViewport.addEventListener("resize", onViewerLayout);
      }
    }
  }

  let liveShareLastMapChromeJson = "";
  let liveShareLastMapView = null;

  function collectMapChromeState() {
    let basemap = "map";
    try {
      if (typeof window.__rkrkGetBasemapMode === "function") {
        basemap = window.__rkrkGetBasemapMode() === "satellite" ? "satellite" : "map";
      } else {
        const satBtn = document.getElementById("basemapSatelliteBtn");
        if (satBtn && satBtn.classList.contains("selected-flight-reference")) basemap = "satellite";
      }
    } catch (_) { /* noop */ }
    const overlays = {};
    Array.from(document.querySelectorAll(".overlay-toggle")).forEach((el) => {
      const key = el.getAttribute("data-overlay-key");
      if (!key) return;
      const opacityEl = document.querySelector(`[data-overlay-opacity-key="${key}"]`);
      const raw = opacityEl ? Number(opacityEl.value) : 100;
      overlays[key] = {
        enabled: !!el.checked,
        opacity: Number.isFinite(raw) ? Math.max(0, Math.min(100, raw)) : 100
      };
    });
    return { basemap, overlays };
  }

  async function applyMapChromeState(drawing) {
    if (!drawing || !drawing.mapChrome || typeof drawing.mapChrome !== "object") return;
    const chrome = drawing.mapChrome;
    const json = JSON.stringify(chrome);
    if (json === liveShareLastMapChromeJson) return;
    liveShareLastMapChromeJson = json;

    if (chrome.basemap && typeof window.__rkrkSetBasemapMode === "function") {
      try { window.__rkrkSetBasemapMode(chrome.basemap); } catch (_) { /* noop */ }
    }

    const overlays = (chrome.overlays && typeof chrome.overlays === "object") ? chrome.overlays : {};
    const keys = Object.keys(overlays);
    for (let i = 0; i < keys.length; i += 1) {
      const key = keys[i];
      const cfg = overlays[key] || {};
      const opacityEl = document.querySelector(`[data-overlay-opacity-key="${key}"]`);
      if (opacityEl && cfg.opacity != null) {
        const nextOp = String(cfg.opacity);
        if (opacityEl.value !== nextOp) {
          opacityEl.value = nextOp;
          opacityEl.dispatchEvent(new Event("input", { bubbles: true }));
        }
      }
      const toggle = document.querySelector(`.overlay-toggle[data-overlay-key="${key}"]`);
      if (!toggle) continue;
      const want = !!cfg.enabled;
      if (toggle.checked !== want) {
        toggle.checked = want;
        toggle.dispatchEvent(new Event("change", { bubbles: true }));
        // setOverlayEnabled is async — give it a moment between layers
        await new Promise((r) => setTimeout(r, 80));
      }
    }
  }

  async function pushLiveShareState(forceActive) {
    if (!liveShareActive && forceActive !== true) return null;
    if (typeof getDrawingState !== "function") {
      throw new Error("Zeichnungszustand nicht verfügbar.");
    }
    try { flushGridCellFillsForShare(); } catch (_) { /* noop */ }
    const drawing = getDrawingState();
    try {
      if (drawing && drawing.grid && typeof drawing.grid === "object") {
        const uiFills = (typeof currentCellFillsFromUi === "function") ? currentCellFillsFromUi() : {};
        const fromState = (drawing.grid.cellFills && typeof drawing.grid.cellFills === "object")
          ? cloneCellFillsMap(drawing.grid.cellFills) : {};
        drawing.grid.cellFills = Object.assign({}, fromState, uiFills);
      }
      if (drawing && Array.isArray(drawing.managedObjects)) {
        drawing.managedObjects = drawing.managedObjects.map((o) => {
          const copy = JSON.parse(JSON.stringify(o));
          if (copy && copy.type === "grid") copy.cellFills = cloneCellFillsMap(copy.cellFills);
          return copy;
        });
      }
    } catch (_) { /* noop */ }
    try {
      if (drawing && typeof map !== "undefined" && map && map.getView) {
        const view = map.getView();
        const center = view.getCenter();
        const zoom = view.getZoom();
        if (Array.isArray(center) && center.length >= 2) {
          try { map.updateSize(); } catch (_) { /* noop */ }
          const resolution = (typeof view.getResolution === "function") ? Number(view.getResolution()) : NaN;
          const size = (typeof map.getSize === "function") ? map.getSize() : null;
          const dpr = (typeof window !== "undefined" && Number(window.devicePixelRatio)) || 1;
          drawing.mapView = {
            center: [Number(center[0]), Number(center[1])],
            zoom: Number(zoom),
            resolution: Number.isFinite(resolution) ? resolution : undefined,
            size: (Array.isArray(size) && size.length >= 2)
              ? [Number(size[0]) || 0, Number(size[1]) || 0]
              : undefined,
            pixelRatio: Number.isFinite(dpr) && dpr > 0 ? dpr : 1
          };
        }
      }
    } catch (_) { /* noop */ }
    try {
      if (drawing) drawing.mapChrome = collectMapChromeState();
    } catch (_) { /* noop */ }
    const body = {
      token: liveShareToken || undefined,
      active: true,
      drawing
    };
    try {
      if (typeof window.__rkrkGetMultiEditorId === "function") {
        const eid = String(window.__rkrkGetMultiEditorId() || "");
        if (eid) {
          body.editor_id = eid;
          if (typeof window.__rkrkGetMultiEditorName === "function") {
            body.editor_name = String(window.__rkrkGetMultiEditorName() || "KOS");
          } else {
            body.editor_name = "KOS";
          }
          if (!(document.body && document.body.classList.contains("rkrk-share-sub"))) {
            body.is_host = true;
          }
        }
      }
    } catch (_) { /* noop */ }
    const res = await fetch(SHARE_API, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(body),
      cache: "no-store"
    });
    const payload = await res.json().catch(() => ({}));
    if (!res.ok || !payload.ok || !payload.token) {
      throw new Error((payload && payload.error) ? String(payload.error) : `HTTP ${res.status}`);
    }
    persistShareToken(payload.token);
    liveShareLastUpdated = String(payload.updated_at || "");
    liveShareLastHeartbeat = String(payload.heartbeat_at || payload.updated_at || "");
    liveShareActive = true;
    // Multi-Edit: gemergten Server-Stand uebernehmen (Host sonst blind fuer Subs)
    try {
      if (payload.drawing && typeof window.__rkrkApplyRemoteDrawingSoft === "function") {
        window.__rkrkApplyRemoteDrawingSoft(payload.drawing);
      }
    } catch (_) { /* noop */ }
    try {
      if (!(document.body && document.body.classList.contains("rkrk-share-sub"))) {
        document.body.classList.add("rkrk-share-host");
      }
      if (typeof window.__rkrkRefreshAnnounceUi === "function") window.__rkrkRefreshAnnounceUi();
      if (payload && typeof window.__rkrkIngestShareAnnounces === "function") {
        window.__rkrkIngestShareAnnounces(payload);
      }
    } catch (_) { /* noop */ }
    if (payload.viewer_count != null || payload.editor_count != null) {
      updateConnectedDevicesUi(payload.viewer_count, payload.editor_count);
    }
    return payload;
  }

  async function pingLiveShareHeartbeat() {
    if (!liveShareActive || liveShareViewerMode || !liveShareToken) return;
    try {
      const res = await fetch(SHARE_API, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ token: liveShareToken, ping: true, active: true }),
        cache: "no-store"
      });
      const payload = await res.json().catch(() => ({}));
      if (res.ok && payload.ok) {
        liveShareLastHeartbeat = String(payload.heartbeat_at || "");
        if (payload.viewer_count != null || payload.editor_count != null) {
          updateConnectedDevicesUi(payload.viewer_count, payload.editor_count);
        }
      }
    } catch (_) { /* keep trying */ }
  }

  function startLiveShareHeartbeat() {
    stopLiveShareHeartbeat();
    liveShareHeartbeatTimer = setInterval(pingLiveShareHeartbeat, 5000);
  }

  function stopLiveShareHeartbeat() {
    if (liveShareHeartbeatTimer) {
      clearInterval(liveShareHeartbeatTimer);
      liveShareHeartbeatTimer = null;
    }
  }

  function scheduleLiveSharePush() {
    if (!liveShareActive || liveShareViewerMode) return;
    // Vor Push: Selektion + eigene Entwuerfe stempeln (Fix-Stempel setzt finalize selbst)
    try {
      let myId = "";
      try {
        if (typeof window.__rkrkGetMultiEditorId === "function") {
          myId = String(window.__rkrkGetMultiEditorId() || "");
        }
      } catch (_) { /* noop */ }
      if (Array.isArray(managedObjects)) {
        managedObjects.forEach((o) => {
          if (!o) return;
          const mine = !o.updated_by || String(o.updated_by) === String(myId);
          const selected = selectedManagedObjectId != null && Number(o.id) === Number(selectedManagedObjectId);
          if (selected || (mine && o.fixed === false)) touchManagedObjectSyncMeta(o);
        });
      }
    } catch (_) { /* noop */ }
    if (liveSharePushTimer) clearTimeout(liveSharePushTimer);
    liveSharePushTimer = setTimeout(() => {
      pushLiveShareState(true).catch(() => { /* keep broadcasting */ });
    }, 900);
  }

  window.notifyLiveShareChange = function () {
    scheduleLiveSharePush();
  };

  async function stopLiveShareBroadcast() {
    liveShareActive = false;
    try { document.body.classList.remove("rkrk-share-host"); } catch (_) { /* noop */ }
    try { if (typeof window.__rkrkRefreshAnnounceUi === "function") window.__rkrkRefreshAnnounceUi(); } catch (_) { /* noop */ }
    stopLiveShareHeartbeat();
    hideHostShareQrPanel();
    updateConnectedDevicesUi(0, 0);
    if (liveSharePushTimer) {
      clearTimeout(liveSharePushTimer);
      liveSharePushTimer = null;
    }
    if (liveShareToken && typeof getDrawingState === "function") {
      try {
        await fetch(SHARE_API, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            token: liveShareToken,
            active: false,
            drawing: getDrawingState()
          }),
          cache: "no-store"
        });
      } catch (_) { /* noop */ }
    }
  }

  try { window.__rkrkApplySharedMapView = function (d, o) { applySharedMapView(d, o); }; } catch (_) {}
  function applySharedMapView(drawing, opts) {
    try {
      if (typeof window.__rkrkShouldFollowSharedMapView === "function"
          && !window.__rkrkShouldFollowSharedMapView()) {
        return;
      }
    } catch (_) { /* noop */ }
    // Ansicht-Link (shared link): nach erstem Laden Ausschnitt konstant halten
    if (liveShareViewOnly && liveShareViewerViewReady && !(opts && opts.force)) {
      return;
    }
    if (window.__rkrkPreserveMapViewOnApply && !(opts && opts.force)) {
      return;
    }
    if (!drawing || !drawing.mapView || typeof map === "undefined" || !map || !map.getView) return;
    const mv = drawing.mapView;
    if (!Array.isArray(mv.center) || mv.center.length < 2) return;
    liveShareLastMapView = {
      center: [Number(mv.center[0]), Number(mv.center[1])],
      zoom: Number(mv.zoom),
      resolution: Number(mv.resolution),
      size: Array.isArray(mv.size) ? [Number(mv.size[0]), Number(mv.size[1])] : null,
      pixelRatio: Number(mv.pixelRatio) || null
    };
    try { map.updateSize(); } catch (_) { /* noop */ }
    const view = map.getView();
    const center = [Number(mv.center[0]), Number(mv.center[1])];
    const zoom = Number(mv.zoom);
    if (!Number.isFinite(center[0]) || !Number.isFinite(center[1])) return;
    try { window.__rkrkSuppressAutoLocate = true; } catch (_) { /* noop */ }
    const hostRes = Number(mv.resolution);
    const hostW = Array.isArray(mv.size) ? Number(mv.size[0]) : NaN;
    const hostH = Array.isArray(mv.size) ? Number(mv.size[1]) : NaN;
    const mapSize = (typeof map.getSize === "function") ? map.getSize() : null;
    const viewerW = (Array.isArray(mapSize) && mapSize.length >= 2) ? Number(mapSize[0]) : NaN;
    const viewerH = (Array.isArray(mapSize) && mapSize.length >= 2) ? Number(mapSize[1]) : NaN;
        const animate = !(opts && opts.animate === false);
    // Sub-Editor: nur center+zoom — fit() jittert bei anderer Fenster-/Sidebar-Groesse
    const editorSimple = !!(opts && opts.editorSimple)
      || !!(document.body && document.body.classList.contains("rkrk-share-coeditor"));
    if (editorSimple && Number.isFinite(zoom)) {
      try {
        if (animate && typeof view.animate === "function") {
          view.animate({ center: center, zoom: zoom, duration: 280 });
        } else {
          view.setCenter(center);
          view.setZoom(zoom);
        }
      } catch (_) {
        try { view.setCenter(center); view.setZoom(zoom); } catch (__) {}
      }
      return;
    } const hasHostFrame = Number.isFinite(hostRes) && hostRes > 0
      && Number.isFinite(hostW) && hostW > 0
      && Number.isFinite(hostH) && hostH > 0;
    const hasViewerFrame = Number.isFinite(viewerW) && viewerW > 0
      && Number.isFinite(viewerH) && viewerH > 0;

    // Host-Ausschnitt (geografisches Rechteck) auf Viewer-Auflösung fitten → ähnliche Übersicht.
    if (hasHostFrame && hasViewerFrame && typeof view.fit === "function") {
      const halfW = (hostW * hostRes) / 2;
      const halfH = (hostH * hostRes) / 2;
      const extent = [
        center[0] - halfW,
        center[1] - halfH,
        center[0] + halfW,
        center[1] + halfH
      ];
      try {
        view.fit(extent, {
          size: [viewerW, viewerH],
          padding: [0, 0, 0, 0],
          nearest: false,
          duration: animate ? 220 : 0
        });
        return;
      } catch (_) { /* fallback below */ }
    }

    // Fallback: beide Achsen skalieren, max → mindestens Host-Ausschnitt sichtbar
    let resolution = null;
    if (hasHostFrame && hasViewerFrame) {
      const resH = hostRes * (hostH / viewerH);
      const resW = hostRes * (hostW / viewerW);
      resolution = Math.max(resH, resW);
    } else if (Number.isFinite(hostRes) && hostRes > 0) {
      resolution = hostRes;
    }
    const target = { center };
    if (resolution != null && Number.isFinite(resolution) && resolution > 0) {
      target.resolution = resolution;
    } else if (Number.isFinite(zoom)) {
      target.zoom = zoom;
    } else {
      return;
    }
    if (animate) {
      target.duration = 250;
      view.animate(target);
    } else {
      view.setCenter(center);
      if (target.resolution != null) view.setResolution(target.resolution);
      else if (target.zoom != null) view.setZoom(target.zoom);
    }
  }

  function heartbeatAgeMs(iso) {
    if (!iso) return Infinity;
    const t = Date.parse(iso);
    if (!Number.isFinite(t)) return Infinity;
    return Math.max(0, Date.now() - t);
  }

  async function pollLiveShareViewer() {
    if (!liveShareToken) return;
    try {
      const viewerId = getOrCreateViewerId();
      const url = `${SHARE_API}?token=${encodeURIComponent(liveShareToken)}`
        + (liveShareLastUpdated ? `&since=${encodeURIComponent(liveShareLastUpdated)}` : "")
        + `&viewer_id=${encodeURIComponent(viewerId)}`;
      const res = await fetch(url, { cache: "no-store" });
      if (res.status === 404) {
        liveShareViewerBroken = true;
        setViewerBadge("is-ended", "Broadcast beendet — Share nicht mehr verfügbar");
        return;
      }
      const payload = await res.json().catch(() => ({}));
      if (!res.ok || !payload.ok) {
        liveShareViewerBroken = true;
        setViewerBadge("is-broken", "Broadcast unterbrochen — warte auf Fortsetzung…");
        return;
      }
      liveShareLastHeartbeat = String(payload.heartbeat_at || payload.updated_at || liveShareLastHeartbeat);
      if (payload.view_only) {
        liveShareViewOnly = true;
        if (!payload.unchanged && payload.drawing && typeof applyDrawingState === "function") {
          if (payload.updated_at && payload.updated_at !== liveShareLastUpdated) {
            const firstView = !liveShareViewerViewReady;
            liveShareLastUpdated = String(payload.updated_at);
            try { window.__rkrkPreserveMapViewOnApply = !firstView; } catch (_) { /* noop */ }
            let keptView = null;
            try {
              if (!firstView && map && map.getView) {
                const v = map.getView();
                const c = v.getCenter();
                keptView = {
                  center: Array.isArray(c) ? [Number(c[0]), Number(c[1])] : null,
                  resolution: Number(v.getResolution()),
                  zoom: Number(v.getZoom())
                };
                if (typeof v.cancelAnimations === "function") v.cancelAnimations();
              }
            } catch (_) { keptView = null; }
            applyDrawingState(payload.drawing);
            if (firstView) applySharedMapView(payload.drawing, { force: true, animate: true });
            try { window.__rkrkPreserveMapViewOnApply = !firstView; } catch (_) { /* noop */ }
            await applyMapChromeState(payload.drawing);
            if (!firstView && keptView && map && map.getView) {
              try {
                const v = map.getView();
                if (typeof v.cancelAnimations === "function") v.cancelAnimations();
                if (keptView.center) v.setCenter(keptView.center);
                if (Number.isFinite(keptView.resolution) && keptView.resolution > 0) {
                  v.setResolution(keptView.resolution);
                } else if (Number.isFinite(keptView.zoom)) {
                  v.setZoom(keptView.zoom);
                }
                liveShareLastMapView = {
                  center: keptView.center,
                  zoom: keptView.zoom,
                  resolution: keptView.resolution,
                  size: (typeof map.getSize === "function") ? map.getSize() : null,
                  pixelRatio: (typeof window !== "undefined" && Number(window.devicePixelRatio)) || 1
                };
              } catch (_) { /* noop */ }
            }
            liveShareViewerViewReady = true;
            try { window.__rkrkPreserveMapViewOnApply = false; } catch (_) { /* noop */ }
            if (payload.drawing && payload.drawing.projectTitle && typeof setProjectTitle === "function") {
              setProjectTitle(payload.drawing.projectTitle);
            }
            if (typeof ensureMapProjectTitleOverlay === "function") ensureMapProjectTitleOverlay();
          }
        }
        liveShareViewerBroken = false;
        setViewerBadge("is-live", "Geteilte Kartenansicht");
        return;
      }
      const countLabel = (payload.viewer_count != null || payload.editor_count != null)
        ? formatViewerCountLabel(payload.viewer_count, payload.editor_count)
        : "Live-Follow aktiv";
      if (payload.active === false) {
        if (!payload.unchanged && payload.drawing && typeof applyDrawingState === "function") {
          if (payload.updated_at && payload.updated_at !== liveShareLastUpdated) {
            const firstView = !liveShareViewerViewReady;
            liveShareLastUpdated = String(payload.updated_at);
            try { window.__rkrkPreserveMapViewOnApply = !firstView; } catch (_) { /* noop */ }
            applyDrawingState(payload.drawing);
            if (firstView) applySharedMapView(payload.drawing);
            await applyMapChromeState(payload.drawing);
            liveShareViewerViewReady = true;
          }
        }
        liveShareViewerBroken = true;
        setViewerBadge("is-ended", "Broadcast beendet — warte auf Fortsetzung…");
        showBroadcastToast("Broadcast beendet.", 4000);
        return;
      }
      if (heartbeatAgeMs(liveShareLastHeartbeat) > 18000) {
        liveShareViewerBroken = true;
        setViewerBadge("is-broken", "Broadcast unterbrochen — warte auf Fortsetzung…");
        return;
      }
      if (payload.unchanged || !payload.drawing) {
        try {
          if (typeof window.__rkrkIngestViewLeader === "function") window.__rkrkIngestViewLeader(payload);
        } catch (_) { /* noop */ }
        const wasBroken = liveShareViewerBroken;
        liveShareViewerBroken = false;
        setViewerBadge("is-live", `Live-Follow aktiv — ${countLabel}`);
        if (wasBroken) showBroadcastToast("Broadcast fortgesetzt.", 2500);
        return;
      }
      if (payload.updated_at && payload.updated_at === liveShareLastUpdated) {
        liveShareViewerBroken = false;
        setViewerBadge("is-live", `Live-Follow aktiv — ${countLabel}`);
        return;
      }
      const firstView = !liveShareViewerViewReady;
      liveShareLastUpdated = String(payload.updated_at || "");
      try { window.__rkrkPreserveMapViewOnApply = !firstView; } catch (_) { /* noop */ }
      if (typeof applyDrawingState === "function") {
        applyDrawingState(payload.drawing);
      }
      if (payload.drawing && payload.drawing.projectTitle && typeof setProjectTitle === "function") {
        setProjectTitle(payload.drawing.projectTitle);
      }
      // Erstes Laden: Ausschnitt setzen. Updates: Ausschnitt konstant (Follow nur via viewfollow)
      if (firstView) applySharedMapView(payload.drawing);
      liveShareViewerViewReady = true;
      await applyMapChromeState(payload.drawing);
      if (typeof ensureMapProjectTitleOverlay === "function") ensureMapProjectTitleOverlay();
      try {
        if (typeof window.__rkrkIngestViewLeader === "function") window.__rkrkIngestViewLeader(payload);
      } catch (_) { /* noop */ }
      const wasBroken = liveShareViewerBroken;
      liveShareViewerBroken = false;
      setViewerBadge("is-live", `Live-Follow aktiv — ${countLabel}`);
      if (wasBroken) showBroadcastToast("Broadcast fortgesetzt.", 2500);
      try {
        if (typeof window.__rkrkIngestShareAnnounces === "function") window.__rkrkIngestShareAnnounces(payload);
      } catch (_) { /* noop */ }
    } catch (_) {
      liveShareViewerBroken = true;
      setViewerBadge("is-broken", "Broadcast unterbrochen — warte auf Fortsetzung…");
    }
  }

  function applyShareViewerReadOnly() {
    liveShareViewerMode = true;
    document.documentElement.classList.add("rkrk-share-viewer");
    document.body.classList.add("rkrk-share-viewer");
    ensureBroadcastCss();
    ensureShareViewerMapOnly();
    try {
      const bumpCopy = function () {
        try {
          if (typeof window.ensureMapCopyrightCss === "function") window.ensureMapCopyrightCss();
        } catch (_) { /* noop */ }
        try {
          if (typeof window.ensureRkrMapCopyright === "function") window.ensureRkrMapCopyright(window.__RKR_BRAND__);
          else if (typeof window.applyRkrBrandEnv === "function") window.applyRkrBrandEnv();
        } catch (_) { /* noop */ }
      };
      bumpCopy();
      setTimeout(bumpCopy, 250);
      setTimeout(bumpCopy, 900);
    } catch (_) { /* noop */ }
    if (typeof ensureMapProjectTitleOverlay === "function") ensureMapProjectTitleOverlay();
    setViewerBadge("is-live", "Live-Follow wird verbunden…");
    try {
      if (window.__rkrkShareViewerMapOnlyT1) clearTimeout(window.__rkrkShareViewerMapOnlyT1);
      if (window.__rkrkShareViewerMapOnlyT2) clearTimeout(window.__rkrkShareViewerMapOnlyT2);
    } catch (_) {}
    window.__rkrkShareViewerMapOnlyT1 = setTimeout(ensureShareViewerMapOnly, 200);
    window.__rkrkShareViewerMapOnlyT2 = setTimeout(ensureShareViewerMapOnly, 800);
  }

function stopLiveShareViewerPoll() {
    if (liveShareViewerTimer) {
      clearInterval(liveShareViewerTimer);
      liveShareViewerTimer = null;
    }
  }
  try { window.__rkrkStopLiveShareViewerPoll = stopLiveShareViewerPoll; } catch (_) {}
  async function startLiveShareViewer(token) {
    liveShareToken = token;
    liveShareViewOnly = false;
    liveShareViewerViewReady = false;
    try { window.__rkrkSuppressAutoLocate = true; } catch (_) { /* noop */ }
    applyShareViewerReadOnly();
    await pollLiveShareViewer();
    if (liveShareViewOnly) {
      showBroadcastToast("Geteilte Kartenansicht geladen.", 3000);
      setViewerBadge("is-live", "Geteilte Kartenansicht");
    } else {
      showBroadcastToast("Live-Follow aktiv — Ansicht folgt dem Host.", 3500);
    }
    if (liveShareViewerTimer) clearInterval(liveShareViewerTimer);
    // Many viewers may poll the same token — server is read-only GET.
    liveShareViewerTimer = setInterval(pollLiveShareViewer, 2500);
  }

  function installBroadcastButton() {
    ensureBroadcastCss();
    if (document.getElementById("liveBroadcastBtn")) return;

    const shareTok = getShareTokenFromUrl();
    // Live-Follow / Co-Editor per Share-Link: ohne Host-Buttons möglich
    if (shareTok) {
      const btn = document.createElement("button");
      btn.id = "liveBroadcastBtn";
      btn.type = "button";
      btn.style.display = "none";
      document.body.appendChild(btn);
      setBroadcastButtonUi(btn, false);
      startLiveShareViewer(shareTok);
      return;
    }

    // Host Share/Edit nur mit ?ADMIN oder ?KOS
    if (!hasMapEditorShareUnlock()) {
      return;
    }

    const btn = document.createElement("button");
    btn.id = "liveBroadcastBtn";
    btn.type = "button";
    document.body.appendChild(btn);
    ensureDeviceCountBadge();

    // Link-Button: aktuellen Stand pushen + direkten Overlay-Link kopieren
    if (!document.getElementById("liveShareCopyLinkBtn")) {
      const copyBtn = document.createElement("button");
      copyBtn.id = "liveShareCopyLinkBtn";
      copyBtn.type = "button";
      copyBtn.title = "Ansicht-Link kopieren (Karte, Overlays, Zoom/Position)";
      copyBtn.setAttribute("aria-label", "Ansicht-Link kopieren");
      copyBtn.innerHTML = '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M3.9 12a5 5 0 0 1 5-5h3v2h-3a3 3 0 1 0 0 6h3v2h-3a5 5 0 0 1-5-5zm7-1h6v2h-6v-2zm2-4h3a5 5 0 0 1 0 10h-3v-2h3a3 3 0 1 0 0-6h-3V7z"/></svg>';
      copyBtn.addEventListener("click", async () => {
        copyBtn.disabled = true;
        try {
          const res = await copyCurrentShareViewLink();
          copyBtn.classList.add("is-live");
          const qrShowBtn = document.getElementById("liveQrShowBtn");
          if (qrShowBtn) qrShowBtn.classList.add("is-live");
          await showHostShareQrPanel(res.url);
          showBroadcastToast(res.copied
            ? "Ansicht-Link kopiert — Karte, Einstellungen und Ansicht sind enthalten."
            : `Link: ${res.url}`, 4500);
        } catch (err) {
          showBroadcastToast(`Link fehlgeschlagen: ${(err && err.message) ? err.message : String(err)}`, 4000);
        } finally {
          copyBtn.disabled = false;
        }
      });
      document.body.appendChild(copyBtn);
    }

    // QR anzeigen → Broadcast-Verbindung für andere Geräte
    if (!document.getElementById("liveQrShowBtn")) {
      const qrBtn = document.createElement("button");
      qrBtn.id = "liveQrShowBtn";
      qrBtn.type = "button";
      qrBtn.title = "QR-Code anzeigen (Broadcast verbinden)";
      qrBtn.setAttribute("aria-label", "QR-Code anzeigen (Broadcast verbinden)");
      qrBtn.innerHTML = '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M3 3h8v8H3V3zm2 2v4h4V5H5zm8-2h8v8h-8V3zm2 2v4h4V5h-4zM3 13h8v8H3v-8zm2 2v4h4v-4H5zm12-2h2v2h-2v-2zm-2 2h2v2h-2v-2zm2 2h2v2h-2v-2zm2-2h2v2h-2v-2zm-4 4h2v2h-2v-2zm2 2h2v2h-2v-2zm2-2h2v2h-2v-2zm2 2h2v2h-2v-2z"/></svg>';
      qrBtn.addEventListener("click", async () => {
        qrBtn.disabled = true;
        try {
          await ensureBroadcastAndShowQr();
        } catch (err) {
          showBroadcastToast(`QR-Code fehlgeschlagen: ${(err && err.message) ? err.message : String(err)}`, 4000);
        } finally {
          qrBtn.disabled = false;
        }
      });
      document.body.appendChild(qrBtn);
    }

    // Vorherigen Host-Token laden → Broadcast fortsetzen mit gleichem Link.
    if (!liveShareToken) {
      const persisted = loadPersistedShareToken();
      if (persisted) liveShareToken = persisted;
    }
    setBroadcastButtonUi(btn, false);

    btn.addEventListener("click", async () => {
      btn.disabled = true;
      try {
        if (liveShareActive) {
          await stopLiveShareBroadcast();
          // Token bleibt erhalten → gleicher Link kann fortgesetzt werden.
          setBroadcastButtonUi(btn, false);
          hideHostShareQrPanel();
          const qrShowBtnOff = document.getElementById("liveQrShowBtn");
          if (qrShowBtnOff) qrShowBtnOff.classList.remove("is-live");
          showBroadcastToast("Broadcast gestoppt — Klick zum Fortsetzen (gleicher Link).", 3500);
          return;
        }
        const resuming = !!liveShareToken;
        const saved = await pushLiveShareState(true);
        const url = buildShareUrl(String(saved.token));
        const copied = await copyTextToClipboard(url);
        setBroadcastButtonUi(btn, true);
        startLiveShareHeartbeat();
        try { document.body.classList.add("rkrk-share-host"); } catch (_) { /* noop */ }
        try { if (typeof window.__rkrkRefreshAnnounceUi === "function") window.__rkrkRefreshAnnounceUi(); } catch (_) { /* noop */ }
        updateConnectedDevicesUi(saved.viewer_count || 0, saved.editor_count || 0);
        showHostShareQrPanel(url).catch(() => { /* noop */ });
        const qrShowBtn = document.getElementById("liveQrShowBtn");
        if (qrShowBtn) qrShowBtn.classList.add("is-live");
        if (resuming) {
          showBroadcastToast(copied
            ? "Broadcast fortgesetzt — QR-Code anzeigen / Link kopiert."
            : `Broadcast fortgesetzt — Link: ${url}`, 4500);
        } else {
          showBroadcastToast(copied
            ? "Broadcast aktiv — QR-Code anzeigen oder Link teilen."
            : `Broadcast aktiv — Link: ${url}`, 4500);
        }
        scheduleLiveSharePush();
      } catch (err) {
        showBroadcastToast(`Broadcast fehlgeschlagen: ${(err && err.message) ? err.message : String(err)}`, 4500);
        setBroadcastButtonUi(btn, false);
        liveShareActive = false;
        stopLiveShareHeartbeat();
        hideHostShareQrPanel();
        updateConnectedDevicesUi(0, 0);
      } finally {
        btn.disabled = false;
      }
    });

    document.addEventListener("change", () => scheduleLiveSharePush(), true);
    document.addEventListener("input", (ev) => {
      const t = ev && ev.target;
      if (!t) return;
      if (t.matches && (t.matches("[data-overlay-opacity-key]") || t.matches(".overlay-toggle"))) {
        scheduleLiveSharePush();
      }
    }, true);
    document.addEventListener("mouseup", () => scheduleLiveSharePush(), true);
    if (typeof window.__rkrkSetBasemapMode === "function" && !window.__rkrkSetBasemapMode.__liveShareWrapped) {
      const origBasemap = window.__rkrkSetBasemapMode;
      window.__rkrkSetBasemapMode = function (mode) {
        const out = origBasemap(mode);
        scheduleLiveSharePush();
        return out;
      };
      window.__rkrkSetBasemapMode.__liveShareWrapped = true;
    }
    ["basemapMapBtn", "basemapSatelliteBtn"].forEach((id) => {
      const el = document.getElementById(id);
      if (el) el.addEventListener("click", () => scheduleLiveSharePush());
    });
    if (typeof map !== "undefined" && map && typeof map.on === "function") {
      try {
        map.on("moveend", () => scheduleLiveSharePush());
      } catch (_) { /* noop */ }
    }
    window.addEventListener("beforeunload", () => {
      // Broadcast nicht hart beenden (Refresh → Fortsetzen möglich).
      if (typeof saveSessionDraftNow === "function") {
        try { saveSessionDraftNow(); } catch (_) { /* noop */ }
      }
    });
  }


  const SESSION_DRAFT_KEY = "rkrk_map_session_draft_v1";
  let sessionDraftTimer = null;
  let sessionRestoreDone = false;

  function sessionDraftHasContent(drawing) {
    if (!drawing || typeof drawing !== "object") return false;
    if (Array.isArray(drawing.managedObjects) && drawing.managedObjects.length) return true;
    if (drawing.previewActive) return true;
    if (drawing.polygon && Array.isArray(drawing.polygon.vertices) && drawing.polygon.vertices.length >= 2) return true;
    if (Array.isArray(drawing.labeledPoints) && drawing.labeledPoints.length) return true;
    if (drawing.freehand && Array.isArray(drawing.freehand.coordinates) && drawing.freehand.coordinates.length >= 2) return true;
    if (drawing.broadcast && drawing.broadcast.token) return true;
    if (drawing.selectedLonLat && Array.isArray(drawing.selectedLonLat) && drawing.selectedLonLat.length === 2) return true;
    return false;
  }

  function buildSessionDraftPayload() {
    if (typeof getDrawingState !== "function") return null;
    if (window.__applyingDrawingState) return null;
    if (liveShareViewerMode) return null;
    try {
      if (typeof syncEditingObjectFromUi === "function") syncEditingObjectFromUi();
    } catch (_) { /* noop */ }
    const drawing = getDrawingState();
    if (!drawing) return null;
    try {
      if (typeof map !== "undefined" && map && map.getView) {
        const view = map.getView();
        const center = view.getCenter();
        const zoom = view.getZoom();
        if (Array.isArray(center) && center.length >= 2) {
          drawing.mapView = { center: [Number(center[0]), Number(center[1])], zoom: Number(zoom) };
        }
      }
    } catch (_) { /* noop */ }
    try {
      if (typeof collectMapChromeState === "function") {
        drawing.mapChrome = collectMapChromeState();
      }
    } catch (_) { /* noop */ }
    const token = liveShareToken || loadPersistedShareToken() || "";
    drawing.broadcast = {
      token: token || "",
      wasActive: !!liveShareActive,
      shareUrl: token ? buildShareUrl(token) : ""
    };
    if (!sessionDraftHasContent(drawing)) return null;
    return {
      version: 1,
      savedAt: new Date().toISOString(),
      drawing
    };
  }

  function saveSessionDraftNow() {
    try {
      const payload = buildSessionDraftPayload();
      if (!payload) return false;
      localStorage.setItem(SESSION_DRAFT_KEY, JSON.stringify(payload));
      return true;
    } catch (_) {
      return false;
    }
  }

  function scheduleSessionDraftSave() {
    if (liveShareViewerMode || window.__applyingDrawingState) return;
    if (sessionDraftTimer) clearTimeout(sessionDraftTimer);
    sessionDraftTimer = setTimeout(() => {
      saveSessionDraftNow();
    }, 700);
  }

  function clearSessionDraft() {
    try { localStorage.removeItem(SESSION_DRAFT_KEY); } catch (_) { /* noop */ }
  }

  function readSessionDraft() {
    try {
      const raw = localStorage.getItem(SESSION_DRAFT_KEY);
      if (!raw) return null;
      const parsed = JSON.parse(raw);
      if (!parsed || !parsed.drawing || typeof parsed.drawing !== "object") return null;
      return parsed;
    } catch (_) {
      return null;
    }
  }

  function formatSessionDraftTime(iso) {
    try {
      const d = new Date(iso);
      if (!Number.isFinite(d.getTime())) return String(iso || "");
      return d.toLocaleString("de-CH", { dateStyle: "short", timeStyle: "short" });
    } catch (_) {
      return String(iso || "");
    }
  }

  async function restoreSessionDraft(draft) {
    if (!draft || !draft.drawing) return false;
    window.__applyingDrawingState = true;
    try {
      if (typeof applyDrawingState === "function") {
        applyDrawingState(draft.drawing);
      }
      if (typeof applySharedMapView === "function") {
        applySharedMapView(draft.drawing);
      }
      if (typeof applyMapChromeState === "function") {
        await applyMapChromeState(draft.drawing);
      }
      const bc = draft.drawing.broadcast || {};
      const token = String(bc.token || "").trim().toLowerCase();
      if (/^[a-z0-9]{8,64}$/.test(token)) {
        persistShareToken(token);
        const btn = document.getElementById("liveBroadcastBtn");
        if (btn) setBroadcastButtonUi(btn, false);
        if (bc.wasActive) {
          try {
            const saved = await pushLiveShareState(true);
            startLiveShareHeartbeat();
            const b2 = document.getElementById("liveBroadcastBtn");
            if (b2) setBroadcastButtonUi(b2, true);
            updateConnectedDevicesUi(
              saved && saved.viewer_count != null ? saved.viewer_count : 0,
              saved && saved.editor_count != null ? saved.editor_count : 0
            );
            const url = buildShareUrl(token);
            showHostShareQrPanel(url).catch(() => { /* noop */ });
            showBroadcastToast("Broadcast fortgesetzt (gleicher Link).", 3500);
          } catch (_) {
            showBroadcastToast("Daten geladen — Broadcast bitte manuell fortsetzen.", 4000);
          }
        } else {
          showBroadcastToast("Sitzung geladen — Broadcast-Link bereit zum Fortsetzen.", 3500);
        }
      } else {
        if (typeof managedStatusText === "function") managedStatusText("Sitzung wiederhergestellt.");
        else if (typeof showBroadcastToast === "function") showBroadcastToast("Sitzung wiederhergestellt.", 3000);
      }
      return true;
    } finally {
      window.__applyingDrawingState = false;
      scheduleSessionDraftSave();
    }
  }

  function sessionDraftHasObjects(drawing) {
    if (!drawing || typeof drawing !== "object") return false;
    if (Array.isArray(drawing.managedObjects) && drawing.managedObjects.length) return true;
    if (drawing.previewActive) return true;
    if (drawing.polygon && Array.isArray(drawing.polygon.vertices) && drawing.polygon.vertices.length >= 2) return true;
    if (Array.isArray(drawing.labeledPoints) && drawing.labeledPoints.length) return true;
    if (drawing.freehand && Array.isArray(drawing.freehand.coordinates) && drawing.freehand.coordinates.length >= 2) return true;
    return false;
  }

  async function offerSessionRestoreOnBoot() {
    if (sessionRestoreDone) return;
    sessionRestoreDone = true;
    if (getShareTokenFromUrl()) return; // Viewer: Host-State per Share, kein lokaler Draft
    const draft = readSessionDraft();
    if (!draft || !sessionDraftHasObjects(draft.drawing)) {
      return;
    }
    const objs = Array.isArray(draft.drawing.managedObjects) ? draft.drawing.managedObjects.length : 0;
    const bc = draft.drawing.broadcast || {};
    const hasLink = !!(bc.token || bc.shareUrl);
    const when = formatSessionDraftTime(draft.savedAt);
    const msg = "Gespeicherte Sitzung gefunden"
      + (when ? ` (${when})` : "")
      + `.\n${objs} Objekt(e)`
      + (hasLink ? ", Broadcast-Link vorhanden" : "")
      + ".\n\nAlte Daten laden?";
    if (!window.confirm(msg)) {
      clearSessionDraft();
      return;
    }
    await restoreSessionDraft(draft);
    try { collapseCoordPanelWhenOrtDefined(); } catch (_) {}
  }

  function installSessionDraftPersistence() {
    if (window.__rkrkSessionDraftInstalled) return;
    window.__rkrkSessionDraftInstalled = true;
    document.addEventListener("change", () => scheduleSessionDraftSave(), true);
    document.addEventListener("input", () => scheduleSessionDraftSave(), true);
    document.addEventListener("mouseup", () => scheduleSessionDraftSave(), true);
    if (typeof map !== "undefined" && map && typeof map.on === "function") {
      try { map.on("moveend", () => scheduleSessionDraftSave()); } catch (_) { /* noop */ }
    }
    window.addEventListener("beforeunload", () => {
      try { saveSessionDraftNow(); } catch (_) { /* noop */ }
    });
    const origNotify = window.notifyLiveShareChange;
    window.notifyLiveShareChange = function () {
      if (typeof origNotify === "function") origNotify();
      scheduleSessionDraftSave();
    };
  }

  function boot() {
    if (typeof exportTypeEl === "undefined" || !exportTypeEl) return false;
    ensureManagedObjectLayer();
    applyCompactCss();
    installObjectContextMenu();
    installSidebarResizeAndCollapse();
    installMovableFloatingBoxes();
    try { updateStyleBoxVisibility(); } catch (_) { /* noop */ }
    removeFlightPathUi();
    ensureStandortBasemapVisible();
    forceObjectMode();
    extendLabelPointTypes();
    injectObjectParamsIntoObjectBox();
    ensureCircleRadiusField();
    ensureCirclesFixPanel();
    ensureTopFixBar();
    installManagedObjectListPanel();
    installLabelPointsObjectListSync();
    if (typeof window.__rkrkInstallEditParamSync === "function") {
      try { window.__rkrkInstallEditParamSync(); } catch (_) { /* noop */ }
      setTimeout(() => {
        try { window.__rkrkInstallEditParamSync(); } catch (_) { /* noop */ }
      }, 300);
    }
    if (typeof window.applyRkrBrandEnv === "function") {
      try { window.applyRkrBrandEnv(); } catch (_) {}
    }
    installProjectTitleEditor();
    patchKmlHeaderForProjectTitle();
    setTimeout(() => {
      try { applyProjectTitleToUi(); } catch (_) { /* noop */ }
      try { patchKmlHeaderForProjectTitle(); } catch (_) { /* noop */ }
    }, 80);
    moveObjectPanelToBottom();
    installCoordCollapseOnObjectFocus();
    try { collapseCoordPanelWhenOrtDefined(); } catch (_) {}
    setTimeout(() => { try { collapseCoordPanelWhenOrtDefined(); } catch (_) {} }, 400);
    // Parameter-Sichtbarkeit nach Move nochmals setzen
    if (typeof exportTypeEl !== "undefined" && exportTypeEl) {
      exportTypeEl.dispatchEvent(new Event("change"));
    }
    patchGridFunctions();
    patchRotatedGridHitTests();
    patchDrawPreviewForRotationOverlay();
    try { patchSingleCirclePreviewLabel(); } catch (_) {}
    installGridRotationControls();
    installRuler();
    installPolygonDoubleClickDelete();
    installNewGridTransparentCells();
    installPlaceSearch();
    installSwissMapButton();
    try {
      ensureMbtilesExportButton();
      ensureHub2KmlButton();
      ensureHub2PushButton();
      moveExportButtonsToShareHost();
      setTimeout(function () {
        try {
          ensureMbtilesExportButton();
          ensureHub2KmlButton();
          ensureHub2PushButton();
          moveExportButtonsToShareHost();
        } catch (_) {}
      }, 400);
      if (hub2UiAllowed()) {
        fetch(FLIGHTHUB_EXPORT_API).then((r) => r.json()).then((data) => {
          window.__RKRK_FLIGHTHUB__ = { configured: !!(data && data.configured) };
          try {
            ensureHub2PushButton();
            moveExportButtonsToShareHost();
          } catch (_) {}
        }).catch(() => {});
      }
      try { syncObjEditorExportHelp(); } catch (_) { /* noop */ }
    } catch (_) { /* noop */ }
    try {
      ensureSharePanelSection();
      moveExportButtonsToShareHost();
      setTimeout(function () {
        try {
          moveExportButtonsToShareHost();
        } catch (_) { /* noop */ }
      }, 400);
    } catch (_) { /* noop */ }
    try { installMapScaleBar(); } catch (_) {}
    installMapPdfButton();
    installShareViewLinkButton();
    setTimeout(() => { try { installShareViewLinkButton(); } catch (_) {} }, 250);
    setTimeout(() => { try { installShareViewLinkButton(); } catch (_) {} }, 800);
    installKmlExportOverride();
    installKmlImportLayerUi();
    installManagedObjectInteractions();
    try { installManagedObjectHoverTooltip(); } catch (_) {}
    patchManagedObjectInteractionsForEdit();
    installEditHistoryHooks();
    installBroadcastButton();
    installFreehandDrawing();
    patchDrawingStorageForManagedObjects();
    installSessionDraftPersistence();

  // Hooks für rkrk_map_multiedit.js
  try {
    window.__rkrkIsLocalObjectDragging = function () {
      try {
        if (dragManagedState) return true;
        if (typeof isDraggingGrid !== "undefined" && isDraggingGrid) return true;
        if (typeof isDraggingMarker !== "undefined" && isDraggingMarker) return true;
        if (typeof isDraggingVertex !== "undefined" && isDraggingVertex) return true;
        if (typeof isDraggingLabeledPoint !== "undefined" && isDraggingLabeledPoint) return true;
        if (typeof freehandDrawing !== "undefined" && freehandDrawing) return true;
        if (typeof freehandVertexDragIndex !== "undefined" && freehandVertexDragIndex >= 0) return true;
      } catch (_) { /* noop */ }
      return false;
    };
    window.__rkrkSyncEditingObjectFromUi = function () {
      try { return syncEditingObjectFromUi(); } catch (_) { return false; }
    };
    window.__rkrkReplaceManagedObjectsSoft = function (mergedList) {
      // Soft-Merge: managedObjects ersetzen, Auswahl + lokale Unfixed behalten, UI-Draft nicht zuruecksetzen
      const keepId = selectedManagedObjectId;
      const unfixedSnaps = (Array.isArray(managedObjects) ? managedObjects : [])
        .filter((o) => o && o.fixed === false && isManagedObjectVisibleToMe(o))
        .map((o) => JSON.parse(JSON.stringify(o)));
      const list = Array.isArray(mergedList) ? mergedList.filter((o) => {
        if (!o) return false;
        if (o.fixed === false && !isManagedObjectVisibleToMe(o)) return false;
        return true;
      }).map((o) => {
        const copy = Object.assign({}, o);
        // fixed nur erzwingen wenn explizit gesetzt — Soft-Merge darf Unfixed behalten
        if (copy.fixed !== false && copy.fixed !== true) copy.fixed = true;
        else copy.fixed = !!copy.fixed;
        return copy;
      }) : [];
      // Lokale Unfixed immer ueber remote legen
      unfixedSnaps.forEach((snap) => {
        const id = Number(snap.id);
        const idx = list.findIndex((o) => o && Number(o.id) === id);
        if (idx >= 0) list[idx] = snap;
        else list.push(snap);
      });
      managedObjects = list;
      if (keepId != null) {
        selectedManagedObjectId = keepId;
        const still = managedObjects.find((o) => o && Number(o.id) === Number(keepId));
        if (still) still.fixed = false;
      }
      try { renderManagedObjects(); } catch (_) { /* noop */ }
      try { refreshManagedObjectList(); } catch (_) { /* noop */ }
    };
    window.__rkrkGetManagedObjects = function () { return managedObjects; };
    window.__rkrkGetSelectedManagedObjectId = function () { return selectedManagedObjectId; };
    window.__rkrkSetSelectedManagedObjectId = function (id) { selectedManagedObjectId = id; };
    window.__rkrkRefreshManagedObjectList = function () { try { refreshManagedObjectList(); } catch (_) {} };
    window.__rkrkGetLiveShareToken = function () { return liveShareToken; };
    window.__rkrkSetLiveShareToken = function (t) { liveShareToken = String(t || ""); };
    window.__rkrkIsLiveShareViewer = function () { return !!liveShareViewerMode; };
    window.__rkrkIsLiveShareActive = function () { return !!liveShareActive; };
    window.__rkrkSetLiveShareActive = function (v) { liveShareActive = !!v; };
    window.__rkrkSetLiveShareViewerMode = function (v) { liveShareViewerMode = !!v; };
    window.__rkrkEnsureShareViewerMapOnly = function () { try { ensureShareViewerMapOnly(); } catch (_) {} };
    window.__rkrkGetLiveShareLastUpdated = function () { return liveShareLastUpdated; };
    window.__rkrkSetLiveShareLastUpdated = function (v) { liveShareLastUpdated = String(v || ""); };
    window.__rkrkPushLiveShareState = function (force) { return pushLiveShareState(force); };
    window.__rkrkScheduleLiveSharePush = function () { try { scheduleLiveSharePush(); } catch (_) {} };
    window.__rkrkStartLiveShareHeartbeat = function () { try { startLiveShareHeartbeat(); } catch (_) {} };
    window.__rkrkBuildShareUrlOrig = function (token) { return buildShareUrl(token); };
    window.__rkrkShowBroadcastToast = function (msg, ms) { try { showBroadcastToast(msg, ms); } catch (_) {} };
    window.__rkrkManagedStatusText = function (msg) { try { managedStatusText(msg); } catch (_) {} };
    window.__rkrkApplyManagedObjectToUi = function (obj) { try { applyManagedObjectToUi(obj); } catch (_) {} };
    window.__rkrkRenderManagedObjects = function () { try { renderManagedObjects(); } catch (_) {} };
    window.__rkrkApplyDrawingState = function (d) { if (typeof applyDrawingState === "function") applyDrawingState(d); };
    window.__rkrkGetDrawingState = function () { return (typeof getDrawingState === "function") ? getDrawingState() : null; };
    window.__rkrkGetBeginEdit = function () { return beginEditManagedObject; };
    window.__rkrkSetBeginEdit = function (fn) { beginEditManagedObject = fn; };
    window.__rkrkGetDeleteManaged = function () { return deleteManagedObjectById; };
    window.__rkrkSetDeleteManaged = function (fn) { deleteManagedObjectById = fn; };
    window.__rkrkGetFinalizeFixedRef = function () { return finalizeCurrentObjectAsFixedRef; };
    window.__rkrkSetFinalizeFixedRef = function (fn) { finalizeCurrentObjectAsFixedRef = fn; };
    window.__rkrkGetDiscardChanges = function () { return (typeof discardCurrentObjectChanges === "function") ? discardCurrentObjectChanges : null; };
    window.__rkrkSetDiscardChanges = function (fn) { try { discardCurrentObjectChanges = fn; } catch (_) {} };
    window.__rkrkPersistShareToken = function (t) { try { persistShareToken(t); } catch (_) {} };
    window.__rkrkCopyText = function (t) { return copyTextToClipboard(t); };
    window.__rkrkRenderShareQr = function (u) { return renderShareQrMedia(u); };
    window.__rkrkEnsureBroadcastCss = function () { try { ensureBroadcastCss(); } catch (_) {} };
    window.__rkrkManagedObjectDisplayName = function (o) { return managedObjectDisplayName(o); };
  } catch (_) { /* noop */ }
    refreshManagedObjectList();
    markFixedHistoryCursor();
    setTimeout(() => { offerSessionRestoreOnBoot().catch(() => {}); }, 200);
    return true;
  }
  let retries = 0;
  (function tryBoot() {
    if (boot()) return;
    if (retries > 30) return;
    retries += 1;
    setTimeout(tryBoot, 120);
  })();
})();
