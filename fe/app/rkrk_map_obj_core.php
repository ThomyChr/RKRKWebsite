<?php
require_once __DIR__ . '/mapll_rkrk_boot.php';

$pagetitle = "Drohnenrehkitzrettung - Karteneditor (Objekte)";
mapll_begin_page($pagetitle, $rkrkIntegrated, $folderoffset, false);

$indexPath = __DIR__ . '/index.html';
$indexHtml = '';
$indexHeadStyle = '';
$indexHeadLinks = [];
$indexBodyContent = '';

function rkrk_scope_editor_css_obj(string $css): string
{
    $replacements = [
        '/(^|\})\s*:root\s*\{/i' => '$1 #rkrkMapObjRoot {',
        '/(^|\})\s*body\s*\{/i' => '$1 #rkrkMapObjRoot {',
        '/(^|\})\s*h1\s*\{/i' => '$1 #rkrkMapObjRoot h1 {',
        '/(^|\})\s*p\s*\{/i' => '$1 #rkrkMapObjRoot p {',
        '/(^|\})\s*label\s*\{/i' => '$1 #rkrkMapObjRoot label {',
        '/(^|\})\s*input\s*,\s*select\s*,\s*button\s*\{/i' => '$1 #rkrkMapObjRoot input, #rkrkMapObjRoot select, #rkrkMapObjRoot button {',
    ];
    return preg_replace(array_keys($replacements), array_values($replacements), $css) ?? $css;
}

function rkrk_object_mode_script(): string
{
    return <<<'HTML'
<script>
(function () {
  const storagePrefix = "map_obj__";
  const legacyStoragePrefix = "map_obj::";
  const DEFAULT_GRID_ROTATION = 0;
  let gridRulerActive = false;

  function toStoredName(name) {
    const normalized = String(name || "").trim();
    if (!normalized) return "";
    if (normalized.startsWith(storagePrefix)) return normalized;
    if (normalized.startsWith(legacyStoragePrefix)) {
      return storagePrefix + normalized.slice(legacyStoragePrefix.length);
    }
    return storagePrefix + normalized;
  }

  function toDisplayName(name) {
    const normalized = String(name || "").trim();
    if (normalized.startsWith(storagePrefix)) {
      return normalized.slice(storagePrefix.length);
    }
    if (normalized.startsWith(legacyStoragePrefix)) {
      return normalized.slice(legacyStoragePrefix.length);
    }
    return normalized;
  }

  function normalizeAngle(value) {
    const raw = Number(value);
    if (!Number.isFinite(raw)) return DEFAULT_GRID_ROTATION;
    let angle = raw % 360;
    if (angle < 0) angle += 360;
    return angle;
  }

  function getGridRotationAngle() {
    const input = document.getElementById("gridRotationAngle");
    if (!input) return DEFAULT_GRID_ROTATION;
    return normalizeAngle(input.value);
  }

  function gridCellName(eastIndex, northIndex) {
    return `${eastLabel(eastIndex)}${northIndex + 1}`;
  }

  function setGridRotationAngle(value) {
    const normalized = normalizeAngle(value);
    const input = document.getElementById("gridRotationAngle");
    const wheel = document.getElementById("gridRotationWheel");
    if (input) input.value = String(Math.round(normalized));
    if (wheel) wheel.value = String(Math.round(normalized));
  }

  function refreshGridPreview() {
    if (typeof drawPreview !== "function") return;
    if (typeof exportTypeEl === "undefined" || !exportTypeEl || exportTypeEl.value !== "grid") return;
    if (typeof selectedLonLat === "undefined" || !Array.isArray(selectedLonLat) || selectedLonLat.length !== 2) return;
    if (typeof previewActive !== "undefined" && previewActive) {
      drawPreview();
    }
  }

  function removeFlightPathUi() {
    Array.from(document.querySelectorAll("#rkrkMapObjRoot details")).forEach((detailsEl) => {
      const summary = detailsEl.querySelector("summary");
      const text = summary ? String(summary.textContent || "").trim() : "";
      if (text.includes("Flight Path")) {
        detailsEl.remove();
      }
    });
  }

  function forceObjectUi() {
    if (typeof exportTypeEl !== "undefined" && exportTypeEl) {
      Array.from(exportTypeEl.options).forEach((option) => {
        if (option.value === "polygon") option.remove();
      });
      if (exportTypeEl.value === "polygon") {
        exportTypeEl.value = "circles";
      }
      exportTypeEl.dispatchEvent(new Event("change"));
    }
    if (typeof startPolyBtnEl !== "undefined" && startPolyBtnEl) {
      const details = startPolyBtnEl.closest("details");
      if (details) details.style.display = "none";
    }
    if (typeof stylePolygonSectionEl !== "undefined" && stylePolygonSectionEl) {
      stylePolygonSectionEl.style.display = "none";
    }
  }

  function installGridRotationControls() {
    if (document.getElementById("gridRotationAngle")) return;

    const asidePanel = document.querySelector("#rkrkMapObjRoot aside.panel");
    const objectSettingsDetails = document.querySelector("#rkrkMapObjRoot details .panel-details-content #gridSettings")?.closest("details");

    const details = document.createElement("details");
    details.className = "panel-details";
    details.open = true;
    details.id = "objectParametersDetails";
    details.innerHTML = ""
      + "<summary>Object Parameters</summary>"
      + "<div class='panel-details-content'>"
      + "  <div id='objectParamsCircles'>"
      + "    <label for='objDiameter1'>Circle diameter 1 (m)</label>"
      + "    <input id='objDiameter1' type='number' min='1' step='1' value='200' />"
      + "    <label for='objDiameter2'>Circle diameter 2 (m)</label>"
      + "    <input id='objDiameter2' type='number' min='1' step='1' value='400' />"
      + "    <label for='objDiameter3'>Circle diameter 3 (m)</label>"
      + "    <input id='objDiameter3' type='number' min='1' step='1' value='600' />"
      + "  </div>"
      + "  <div id='objectParamsGrid' style='display:none;'>"
      + "    <label for='objGridSize'>Grid size / spacing (m)</label>"
      + "    <input id='objGridSize' type='number' min='1' step='1' value='300' />"
      + "    <label for='objNorthCount'>Number of fields north</label>"
      + "    <input id='objNorthCount' type='number' min='1' max='200' step='1' value='8' />"
      + "    <label for='objEastCount'>Number of fields east</label>"
      + "    <input id='objEastCount' type='number' min='1' max='26' step='1' value='8' />"
      + "    <label for='gridRotationAngle'>Grid rotation (deg)</label>"
      + "    <input id='gridRotationAngle' type='number' min='0' max='359' step='1' value='0' />"
      + "    <input id='gridRotationWheel' type='range' min='0' max='359' step='1' value='0' />"
      + "    <button id='gridRulerBtn' type='button'>Set Grid Angle With Ruler</button>"
      + "    <p class='small' id='gridRulerStatus'>Ruler off.</p>"
      + "  </div>"
      + "  <p class='small' id='gridParamHint'>Parameters follow selected object type.</p>"
      + "</div>";

    if (objectSettingsDetails && objectSettingsDetails.parentNode) {
      objectSettingsDetails.parentNode.insertBefore(details, objectSettingsDetails.nextSibling);
    } else if (asidePanel) {
      asidePanel.appendChild(details);
    }

    const input = document.getElementById("gridRotationAngle");
    const wheel = document.getElementById("gridRotationWheel");
    const objDiameter1 = document.getElementById("objDiameter1");
    const objDiameter2 = document.getElementById("objDiameter2");
    const objDiameter3 = document.getElementById("objDiameter3");
    const objGridSize = document.getElementById("objGridSize");
    const objNorthCount = document.getElementById("objNorthCount");
    const objEastCount = document.getElementById("objEastCount");
    const radius1 = document.getElementById("radius1");
    const radius2 = document.getElementById("radius2");
    const radius3 = document.getElementById("radius3");
    const spacing = document.getElementById("spacing");
    const northCount = document.getElementById("northCount");
    const eastCount = document.getElementById("eastCount");
    if (input) {
      input.addEventListener("input", () => {
        setGridRotationAngle(input.value);
        refreshGridPreview();
      });
    }
    if (wheel) {
      wheel.addEventListener("input", () => {
        setGridRotationAngle(wheel.value);
        refreshGridPreview();
      });
    }
    if (radius1 && objDiameter1) objDiameter1.value = String(Math.max(1, Math.round(Number(radius1.value || 100) * 2)));
    if (radius2 && objDiameter2) objDiameter2.value = String(Math.max(1, Math.round(Number(radius2.value || 200) * 2)));
    if (radius3 && objDiameter3) objDiameter3.value = String(Math.max(1, Math.round(Number(radius3.value || 300) * 2)));
    if (spacing && objGridSize) objGridSize.value = String(Math.max(1, Math.round(Number(spacing.value || 300))));
    if (northCount && objNorthCount) objNorthCount.value = String(Math.max(1, Math.round(Number(northCount.value || 8))));
    if (eastCount && objEastCount) objEastCount.value = String(Math.max(1, Math.round(Number(eastCount.value || 8))));

    function bindDiameter(objInput, radiusInput) {
      if (!objInput || !radiusInput) return;
      objInput.addEventListener("input", () => {
        const d = Number(objInput.value);
        const diameter = Number.isFinite(d) ? Math.max(1, Math.round(d)) : 1;
        objInput.value = String(diameter);
        radiusInput.value = String(Math.max(1, diameter / 2));
        refreshGridPreview();
      });
    }
    bindDiameter(objDiameter1, radius1);
    bindDiameter(objDiameter2, radius2);
    bindDiameter(objDiameter3, radius3);

    if (objGridSize && spacing) {
      objGridSize.addEventListener("input", () => {
        const s = Number(objGridSize.value);
        const value = Number.isFinite(s) ? Math.max(1, Math.round(s)) : 1;
        objGridSize.value = String(value);
        spacing.value = String(value);
        refreshGridPreview();
      });
    }

    if (objNorthCount && northCount) {
      objNorthCount.addEventListener("input", () => {
        const n = Number(objNorthCount.value);
        const value = Number.isFinite(n) ? Math.max(1, Math.min(200, Math.round(n))) : 1;
        objNorthCount.value = String(value);
        northCount.value = String(value);
        refreshGridPreview();
      });
    }
    if (objEastCount && eastCount) {
      objEastCount.addEventListener("input", () => {
        const e = Number(objEastCount.value);
        const value = Number.isFinite(e) ? Math.max(1, Math.min(26, Math.round(e))) : 1;
        objEastCount.value = String(value);
        eastCount.value = String(value);
        refreshGridPreview();
      });
    }
    if (typeof exportTypeEl !== "undefined" && exportTypeEl) {
      const detailsEl = document.getElementById("objectParametersDetails");
      const hintEl = document.getElementById("gridParamHint");
      const circlesSection = document.getElementById("objectParamsCircles");
      const gridSection = document.getElementById("objectParamsGrid");
      const updateVisibility = () => {
        const isGrid = exportTypeEl.value === "grid";
        if (detailsEl) detailsEl.style.display = "";
        if (circlesSection) circlesSection.style.display = isGrid ? "none" : "";
        if (gridSection) gridSection.style.display = isGrid ? "" : "none";
        if (hintEl) {
          hintEl.textContent = isGrid
            ? "Grid parameters active."
            : "Circle diameter parameters active.";
        }
      };
      exportTypeEl.addEventListener("change", updateVisibility);
      updateVisibility();
    }
    setGridRotationAngle(DEFAULT_GRID_ROTATION);
  }

  function rotatedOffsetLatLon(originLat, originLon, northMeters, eastMeters) {
    const angleRad = (typeof degToRad === "function")
      ? degToRad(getGridRotationAngle())
      : getGridRotationAngle() * Math.PI / 180;
    const rotatedEast = (eastMeters * Math.cos(angleRad)) - (northMeters * Math.sin(angleRad));
    const rotatedNorth = (eastMeters * Math.sin(angleRad)) + (northMeters * Math.cos(angleRad));
    return offsetLatLon(originLat, originLon, rotatedNorth, rotatedEast);
  }

  function patchGridGeometryFunctions() {
    if (typeof drawGridPreview === "function") {
      drawGridPreview = function (lat, lon) {
        const style = getEditorStyles().grid;
        const spacing = sanitizeNumber(document.getElementById("spacing").value, 300);
        const northCount = Math.min(200, Math.max(1, Math.round(sanitizeNumber(document.getElementById("northCount").value, 8))));
        const eastCount = Math.min(26, Math.max(1, Math.round(sanitizeNumber(document.getElementById("eastCount").value, 8))));

        for (let n = 0; n < northCount; n += 1) {
          for (let e = 0; e < eastCount; e += 1) {
            const sw = rotatedOffsetLatLon(lat, lon, n * spacing, e * spacing);
            const se = rotatedOffsetLatLon(lat, lon, n * spacing, (e + 1) * spacing);
            const ne = rotatedOffsetLatLon(lat, lon, (n + 1) * spacing, (e + 1) * spacing);
            const nw = rotatedOffsetLatLon(lat, lon, (n + 1) * spacing, e * spacing);
            const center = rotatedOffsetLatLon(lat, lon, (n + 0.5) * spacing, (e + 0.5) * spacing);
            const cellName = gridCellName(e, n);

            const ring = [sw, se, ne, nw, sw].map((p) => lonLatToMapCoord(p[1], p[0]));
            const polygonFeature = new ol.Feature({ geometry: new ol.geom.Polygon([ring]) });
            polygonFeature.set("previewType", "grid-cell");
            polygonFeature.setStyle(new ol.style.Style({
              stroke: new ol.style.Stroke({ color: style.lineColor, width: style.lineWidth }),
              fill: new ol.style.Fill({ color: hexToRgba(style.fillColor, style.fillOpacity) })
            }));
            previewSource.addFeature(polygonFeature);

            const textFeature = new ol.Feature({
              geometry: new ol.geom.Point(lonLatToMapCoord(center[1], center[0]))
            });
            textFeature.setStyle(new ol.style.Style({
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

    if (typeof buildGridKml === "function") {
      buildGridKml = function (lat, lon, elevationMap = new Map()) {
        const spacing = sanitizeNumber(document.getElementById("spacing").value, 300);
        const northCount = Math.min(200, Math.max(1, Math.round(sanitizeNumber(document.getElementById("northCount").value, 8))));
        const eastCount = Math.min(26, Math.max(1, Math.round(sanitizeNumber(document.getElementById("eastCount").value, 8))));
        const styles = getEditorStyles();
        const grid = styles.grid;
        const labelScale = (grid.fontSize / 12).toFixed(2);

        let kml = kmlHeader("grid_export");
        kml += `
    <Style id="grid-cell">
      <LineStyle><color>${hexToKmlColor(grid.lineColor, "ff")}</color><width>${grid.lineWidth}</width></LineStyle>
      <PolyStyle><color>${hexToKmlColor(grid.fillColor, opacityToAlphaHex(grid.fillOpacity))}</color></PolyStyle>
    </Style>
    <Style id="grid-label">
      <LabelStyle><scale>${labelScale}</scale><color>${hexToKmlColor(grid.labelColor, "ff")}</color></LabelStyle>
      <IconStyle><scale>0</scale></IconStyle>
    </Style>`;

        for (let n = 0; n < northCount; n += 1) {
          for (let e = 0; e < eastCount; e += 1) {
            const sw = rotatedOffsetLatLon(lat, lon, n * spacing, e * spacing);
            const se = rotatedOffsetLatLon(lat, lon, n * spacing, (e + 1) * spacing);
            const ne = rotatedOffsetLatLon(lat, lon, (n + 1) * spacing, (e + 1) * spacing);
            const nw = rotatedOffsetLatLon(lat, lon, (n + 1) * spacing, e * spacing);
            const center = rotatedOffsetLatLon(lat, lon, (n + 0.5) * spacing, (e + 0.5) * spacing);
            const cellName = gridCellName(e, n);
            const polygonCoords = [
              [sw[1], sw[0], 0],
              [se[1], se[0], 0],
              [ne[1], ne[0], 0],
              [nw[1], nw[0], 0],
              [sw[1], sw[0], 0]
            ];

            kml += `
    <Placemark>
      <name>${cellName}</name>
      <styleUrl>#grid-cell</styleUrl>
      <Polygon>
        <outerBoundaryIs>
          <LinearRing>
            <coordinates>${polygonCoordinatesText(polygonCoords)}</coordinates>
          </LinearRing>
        </outerBoundaryIs>
      </Polygon>
    </Placemark>
    <Placemark>
      <name>${cellName}</name>
      <styleUrl>#grid-label</styleUrl>
      <Point><coordinates>${center[1]},${center[0]},0</coordinates></Point>
    </Placemark>`;
          }
        }

        kml += `
    <Style id="grid-origin-style">
      <IconStyle><color>${hexToKmlColor(styles.startPointColor, "ff")}</color></IconStyle>
    </Style>
    <Placemark>
      <name>Grid Origin (SW)</name>
      <styleUrl>#grid-origin-style</styleUrl>
      ${elevationDataKml(getElevationAt(elevationMap, lon, lat), 0)}
      <Point><coordinates>${lon},${lat},${(getElevationAt(elevationMap, lon, lat) || 0).toFixed(2)}</coordinates></Point>
    </Placemark>`;

        kml += appendLabeledPointsKml(elevationMap);
        kml += appendPolygonVertexPointsKml(elevationMap);
        return kml + kmlFooter();
      };
    }
  }

  function installGridRulerTool() {
    if (typeof map === "undefined" || !map || typeof map.on !== "function") return;
    const btn = document.getElementById("gridRulerBtn");
    const statusEl = document.getElementById("gridRulerStatus");
    if (!btn || !statusEl || map.__rkrkObjRulerInstalled) return;
    map.__rkrkObjRulerInstalled = true;

    const rulerSource = new ol.source.Vector();
    const rulerLayer = new ol.layer.Vector({
      source: rulerSource,
      style: new ol.style.Style({
        stroke: new ol.style.Stroke({ color: "#00d4ff", width: 2, lineDash: [8, 6] })
      })
    });
    map.addLayer(rulerLayer);

    function hasGridContext() {
      return typeof exportTypeEl !== "undefined" && exportTypeEl && exportTypeEl.value === "grid"
        && Array.isArray(selectedLonLat) && selectedLonLat.length === 2;
    }

    function setRulerActive(active) {
      gridRulerActive = !!active;
      btn.classList.toggle("selected-flight-reference", gridRulerActive);
      statusEl.textContent = gridRulerActive ? "Ruler active: move mouse for direction." : "Ruler off.";
      if (!gridRulerActive) rulerSource.clear();
    }

    btn.addEventListener("click", () => {
      if (!hasGridContext()) {
        statusEl.textContent = "Select grid + set base point first.";
        setRulerActive(false);
        return;
      }
      setRulerActive(!gridRulerActive);
    });

    map.on("pointermove", (evt) => {
      if (!gridRulerActive) return;
      if (!hasGridContext()) {
        setRulerActive(false);
        return;
      }
      const origin = lonLatToMapCoord(selectedLonLat[0], selectedLonLat[1]);
      const dx = Number(evt.coordinate[0]) - Number(origin[0]);
      const dy = Number(evt.coordinate[1]) - Number(origin[1]);
      const dist = Math.sqrt((dx * dx) + (dy * dy));
      if (!Number.isFinite(dist) || dist < 5) return;

      let angle = Math.atan2(dy, dx) * 180 / Math.PI;
      if (angle < 0) angle += 360;
      setGridRotationAngle(angle);
      refreshGridPreview();
      statusEl.textContent = `Ruler active: angle ${Math.round(angle)} deg`;

      rulerSource.clear();
      rulerSource.addFeature(new ol.Feature({
        geometry: new ol.geom.LineString([origin, evt.coordinate])
      }));
    });

    if (typeof exportTypeEl !== "undefined" && exportTypeEl) {
      const updateVisibility = () => {
        const isGrid = exportTypeEl.value === "grid";
        btn.style.display = isGrid ? "block" : "none";
        if (!isGrid) setRulerActive(false);
      };
      exportTypeEl.addEventListener("change", updateVisibility);
      updateVisibility();
    }
  }

  function patchDrawingStorage() {
    if (typeof drawingNameEl !== "undefined" && drawingNameEl) {
      drawingNameEl.value = toDisplayName(drawingNameEl.value);
      drawingNameEl.placeholder = "e.g. object-set-1";
    }

    if (typeof getDrawingState === "function") {
      const originalGetDrawingState = getDrawingState;
      getDrawingState = function () {
        const state = originalGetDrawingState();
        if (state && typeof state === "object") {
          state.exportType = state.exportType === "polygon" ? "circles" : state.exportType;
          state.polygon = { vertices: [] };
          delete state.flightPath;
          state.grid = state.grid || {};
          state.grid.rotationAngle = getGridRotationAngle();
        }
        return state;
      };
    }

    if (typeof applyDrawingState === "function") {
      const originalApplyDrawingState = applyDrawingState;
      applyDrawingState = function (state) {
        const nextState = (state && typeof state === "object") ? { ...state } : {};
        nextState.exportType = nextState.exportType === "polygon" ? "circles" : nextState.exportType;
        nextState.polygon = { vertices: [] };
        delete nextState.flightPath;
        const applied = originalApplyDrawingState(nextState);
        if (nextState.grid && nextState.grid.rotationAngle !== undefined) {
          setGridRotationAngle(nextState.grid.rotationAngle);
        }
        const objNorthCount = document.getElementById("objNorthCount");
        const objEastCount = document.getElementById("objEastCount");
        const objDiameter1 = document.getElementById("objDiameter1");
        const objDiameter2 = document.getElementById("objDiameter2");
        const objDiameter3 = document.getElementById("objDiameter3");
        const northCount = document.getElementById("northCount");
        const eastCount = document.getElementById("eastCount");
        const radius1 = document.getElementById("radius1");
        const radius2 = document.getElementById("radius2");
        const radius3 = document.getElementById("radius3");
        if (objNorthCount && northCount) objNorthCount.value = String(Math.max(1, Math.round(Number(northCount.value || 8))));
        if (objEastCount && eastCount) objEastCount.value = String(Math.max(1, Math.round(Number(eastCount.value || 8))));
        if (objDiameter1 && radius1) objDiameter1.value = String(Math.max(1, Math.round(Number(radius1.value || 100) * 2)));
        if (objDiameter2 && radius2) objDiameter2.value = String(Math.max(1, Math.round(Number(radius2.value || 200) * 2)));
        if (objDiameter3 && radius3) objDiameter3.value = String(Math.max(1, Math.round(Number(radius3.value || 300) * 2)));
        return applied;
      };
    }

    if (typeof refreshDrawingList === "function") {
      const originalRefreshDrawingList = refreshDrawingList;
      refreshDrawingList = async function () {
        await originalRefreshDrawingList();
        if (typeof drawingListEl === "undefined" || !drawingListEl) return;
        const values = Array.from(drawingListEl.options)
          .map((opt) => opt.value)
          .filter((value) => value && (value.startsWith(storagePrefix) || value.startsWith(legacyStoragePrefix)));
        drawingListEl.innerHTML = "";
        if (!values.length) {
          const option = document.createElement("option");
          option.value = "";
          option.textContent = "(keine gespeicherten Zeichnungen)";
          drawingListEl.appendChild(option);
          return;
        }
        values.forEach((storedName) => {
          const option = document.createElement("option");
          option.value = storedName;
          option.textContent = toDisplayName(storedName);
          drawingListEl.appendChild(option);
        });
      };
    }

    if (typeof saveDrawing === "function") {
      const originalSaveDrawing = saveDrawing;
      saveDrawing = async function () {
        if (typeof drawingNameEl !== "undefined" && drawingNameEl) {
          drawingNameEl.value = toStoredName(drawingNameEl.value);
        }
        await originalSaveDrawing();
        if (typeof drawingNameEl !== "undefined" && drawingNameEl) {
          drawingNameEl.value = toDisplayName(drawingNameEl.value);
        }
        if (typeof refreshDrawingList === "function") {
          await refreshDrawingList();
        }
      };
    }

    if (typeof loadSelectedDrawing === "function") {
      const originalLoadSelectedDrawing = loadSelectedDrawing;
      loadSelectedDrawing = async function () {
        await originalLoadSelectedDrawing();
        if (typeof drawingNameEl !== "undefined" && drawingNameEl) {
          drawingNameEl.value = toDisplayName(drawingNameEl.value);
        }
      };
    }
  }

  function disableFlightPathArtifacts() {
    if (typeof appendFlightPathPointsKml === "function") {
      appendFlightPathPointsKml = function () { return ""; };
    }
    if (typeof flightPathPoints !== "undefined" && Array.isArray(flightPathPoints)) {
      flightPathPoints = [];
    }
  }

  function initObjectPage() {
    removeFlightPathUi();
    forceObjectUi();
    patchDrawingStorage();
    disableFlightPathArtifacts();
    installGridRotationControls();
    patchGridGeometryFunctions();
    installGridRulerTool();
    setGridRotationAngle(DEFAULT_GRID_ROTATION);
    if (typeof refreshDrawingList === "function") {
      refreshDrawingList();
    }
  }

  try {
    initObjectPage();
  } catch (err) {
    console.warn("Object page initialization warning:", err);
  }
})();
</script>
HTML;
}

if (is_file($indexPath)) {
    $indexHtml = file_get_contents($indexPath) ?: '';

    if (preg_match_all('/<link\b[^>]*rel=["\']stylesheet["\'][^>]*>/i', $indexHtml, $matches)) {
        $indexHeadLinks = $matches[0];
    }

    if (preg_match('/<style\b[^>]*>(.*?)<\/style>/is', $indexHtml, $styleMatch)) {
        $indexHeadStyle = $styleMatch[1];
        $indexHeadStyle = rkrk_scope_editor_css_obj($indexHeadStyle);
    }

    if (preg_match('/<body\b[^>]*>(.*)<\/body>/is', $indexHtml, $bodyMatch)) {
        $indexBodyContent = $bodyMatch[1];
    } else {
        $indexBodyContent = $indexHtml;
    }
}
?>
<body>
<?php
if ($rkrkIntegrated && $showTopMenu && function_exists('CreateTopMenu')) {
    CreateTopMenu($folderoffset);
    echo "<br><br>";
}

if ($rkrkIntegrated && (($Layout ?? 2023) != 2023) && function_exists('CreateSiteLink')) {
    echo "<table>" . chr(10);
    echo "<tr><td width='80%'></td><td class='right' align='right'>" . chr(10);
    echo CreateSiteLink("Home", "");
    echo CreateSiteLink("GoBack", "");
    echo "</td></tr>" . chr(10);
    echo "</table>" . chr(10);
}

if ($rkrkIntegrated) {
    echo "<link href='" . htmlspecialchars($folderoffset, ENT_QUOTES, 'UTF-8') . "template/nmenu_style.css' rel='stylesheet' type='text/css'>" . chr(10);
}
foreach ($indexHeadLinks as $cssLinkTag) {
    echo $cssLinkTag . chr(10);
}
if ($indexHeadStyle !== '') {
    echo "<style>" . chr(10) . $indexHeadStyle . chr(10) . "</style>" . chr(10);
}
echo "<style>
#rkrkMapObjRoot button {
  background-color: #96B588 !important;
  color: #ffffff !important;
  border: 1px solid #527642 !important;
}
#rkrkMapObjRoot button:hover {
  background-color: #7ea56f !important;
}
#rkrkMapObjRoot button.selected-flight-reference,
#rkrkMapObjRoot button:active {
  background-color: #48673A !important;
  border-color: #3f5933 !important;
  color: #ffffff !important;
}
</style>" . chr(10);
?>
<div id="rkrkMapObjRoot">
<?php
if ($indexBodyContent !== '') {
    echo $indexBodyContent;
    echo rkrk_object_mode_script();
} else {
    echo "<p>Map object editor content could not be loaded from index.html.</p>";
}
?>
</div>
<?php
echo "</body>" . chr(10);
echo "</html>" . chr(10);
?>
