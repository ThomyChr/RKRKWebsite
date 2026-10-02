<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$folderoffset = "../";
@chdir(__DIR__ . '/..');
include_once(__DIR__ . '/../scripts/rkrk_gen.php');
include_once(__DIR__ . '/../scripts/rkrk_menu.php');
include_once(__DIR__ . '/../rkrk_access_control.php');
$MenuDirOffset = $folderoffset;

if (function_exists('CheckIPandExit')) {
    CheckIPandExit();
}

$pagetitle = "Drohnenrehkitzrettung - Karteneditor (Objekte)";
if (function_exists('headerpage')) {
    headerpage($pagetitle, false, $folderoffset);
}
if (function_exists('GrantAccess')) {
    GrantAccess();
}

$currentAccessLevel = '';
if (isset($SessNme_AccessLevel) && isset($_SESSION[$SessNme_AccessLevel])) {
    $currentAccessLevel = strtolower(trim((string)$_SESSION[$SessNme_AccessLevel]));
} elseif (isset($InputUserInfo['Login']['Level'])) {
    $currentAccessLevel = strtolower(trim((string)$InputUserInfo['Login']['Level']));
}
$isGuestRequest = (isset($_GET['GA']) && strtolower(trim((string)$_GET['GA'])) === 'gast');
$showTopMenu = (!$isGuestRequest && !empty($LoggedOn) && $currentAccessLevel !== 'gast');

$indexPath = __DIR__ . '/map_editor.html';
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
  const ON_RKRS = false;
  const storagePrefix = "map_obj__";
  const legacyStoragePrefix = "map_obj::";
  const DEFAULT_GRID_ROTATION = 0;
  const KML_SAVE_API = "save_kml.php";
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
    const root = document.getElementById("rkrkMapObjRoot") || document.body;
    const fpEl = document.getElementById("fpCalcVariant");
    if (fpEl) {
      const det = fpEl.closest("details");
      if (det && root.contains(det)) {
        det.remove();
      }
    }
    Array.from(root.querySelectorAll("details")).forEach((detailsEl) => {
      const summary = detailsEl.querySelector("summary");
      const text = summary ? String(summary.textContent || "").trim() : "";
      if (/\bFlugpfad\b/i.test(text) || /\bFlight\s*Path\b/i.test(text)) {
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

  function streamlineLeftMenu() {
    const root = document.getElementById("rkrkMapObjRoot") || document.body;
    const panel = root.querySelector("aside.panel");
    if (panel) {
      panel.classList.add("obj-compact-panel");
    }
    const detailsList = Array.from(root.querySelectorAll("aside.panel details.panel-details"));
    detailsList.forEach((detailsEl) => {
      const summaryEl = detailsEl.querySelector("summary");
      const title = summaryEl ? String(summaryEl.textContent || "").trim().toLowerCase() : "";
      // Hide redundant/advanced boxes to keep object workflow simple.
      if (title.includes("flugfeld zeichnen")) {
        detailsEl.style.display = "none";
        return;
      }
      // Keep main workflow sections visible, but reduce clutter by default.
      detailsEl.open = (title.includes("objekt") || title.includes("object parameters") || title.includes("koordinate"));
    });
  }

  function installGridRotationControls() {
    if (document.getElementById("gridRotationAngle")) return;

    const objectDetails = (typeof exportTypeEl !== "undefined" && exportTypeEl)
      ? exportTypeEl.closest("details")
      : null;
    const objectContent = objectDetails ? objectDetails.querySelector(".panel-details-content") : null;
    if (!objectContent) return;

    const box = document.createElement("div");
    box.id = "objectParametersDetails";
    box.className = "obj-merged-params";
    box.innerHTML = ""
      + "<p class='small obj-merged-params-title'>Objekt-Parameter</p>"
      + "  <div id='objectParamsCircle' style='display:none;'>"
      + "    <label for='objSingleRadius'>Kreisradius (m)</label>"
      + "    <input id='objSingleRadius' type='number' min='1' step='1' value='100' />"
      + "    <input id='objSingleDiameter' type='hidden' value='200' />"
      + "  </div>"
      + "  <div id='objectParamsCircles' style='display:none;'>"
      + "    <label for='objDiameter1'>Kreisdurchmesser 1 (m)</label>"
      + "    <input id='objDiameter1' type='number' min='1' step='1' value='200' />"
      + "    <label for='objDiameter2'>Kreisdurchmesser 2 (m)</label>"
      + "    <input id='objDiameter2' type='number' min='1' step='1' value='400' />"
      + "    <label for='objDiameter3'>Kreisdurchmesser 3 (m)</label>"
      + "    <input id='objDiameter3' type='number' min='1' step='1' value='600' />"
      + "  </div>"
      + "  <div id='objectParamsGrid' style='display:none;'>"
      + "    <label for='objGridSize'>Rasterabstand (m)</label>"
      + "    <input id='objGridSize' type='number' min='1' step='1' value='300' />"
      + "    <label for='objNorthCount'>Felder Nord</label>"
      + "    <input id='objNorthCount' type='number' min='1' max='200' step='1' value='8' />"
      + "    <label for='objEastCount'>Felder Ost</label>"
      + "    <input id='objEastCount' type='number' min='1' max='26' step='1' value='8' />"
      + "    <label for='gridRotationAngle'>Rasterrotation (Grad)</label>"
      + "    <input id='gridRotationAngle' type='number' min='0' max='359' step='1' value='0' />"
      + "    <input id='gridRotationWheel' type='range' min='0' max='359' step='1' value='0' />"
      + "    <label class='obj-grid-fills-toggle' for='objGridShowResultFills'>"
      + "      <input id='objGridShowResultFills' type='checkbox' checked />"
      + "      Resultatflächen anzeigen"
      + "    </label>"
      + "    <button id='gridRulerBtn' type='button'>Rotation mit Lineal setzen</button>"
      + "    <p class='small' id='gridRulerStatus'>Lineal aus.</p>"
      + "  </div>"
      + "  <p class='small' id='gridParamHint'>Parameter wechseln automatisch je Objekttyp.</p>";
    objectContent.appendChild(box);

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
            ? "Raster-Parameter aktiv."
            : "Kreis-Parameter aktiv.";
        }
      };
      exportTypeEl.addEventListener("change", updateVisibility);
      updateVisibility();
    }
    setGridRotationAngle(DEFAULT_GRID_ROTATION);
  }

  function parseSearchResultLonLat(result) {
    if (!result || typeof result !== "object") {
      return null;
    }
    const attrs = (result.attrs && typeof result.attrs === "object") ? result.attrs : {};
    const lon = Number(attrs.lon ?? attrs.x ?? result.lon ?? result.x);
    const lat = Number(attrs.lat ?? attrs.y ?? result.lat ?? result.y);
    if (Number.isFinite(lon) && Number.isFinite(lat)) {
      return [lon, lat];
    }
    return null;
  }

  async function searchGeoAdminLocation(searchText) {
    const query = String(searchText || "").trim();
    if (!query) {
      return null;
    }
    const url = `https://api3.geo.admin.ch/rest/services/api/SearchServer?type=locations&searchText=${encodeURIComponent(query)}&limit=1`;
    const response = await fetch(url, { cache: "no-store" });
    if (!response.ok) {
      throw new Error(`Search ${response.status}`);
    }
    const payload = await response.json();
    const results = Array.isArray(payload && payload.results) ? payload.results : [];
    if (!results.length) {
      return null;
    }
    return parseSearchResultLonLat(results[0]);
  }

  function installPlaceSearchControl() {
    const objectDetails = (typeof exportTypeEl !== "undefined" && exportTypeEl)
      ? exportTypeEl.closest("details")
      : null;
    const objectContent = objectDetails ? objectDetails.querySelector(".panel-details-content") : null;
    if (!objectContent || document.getElementById("objPlaceSearchInput")) {
      return;
    }

    const wrapper = document.createElement("div");
    wrapper.id = "objPlaceSearchBox";
    wrapper.innerHTML = ""
      + "<label for='objPlaceSearchInput'>Ort suchen</label>"
      + "<input id='objPlaceSearchInput' type='text' placeholder='z. B. Aarau, Waldhaus Schafisheim' />"
      + "<button id='objPlaceSearchBtn' type='button'>Ort auf Karte suchen</button>"
      + "<p class='small' id='objPlaceSearchStatus'></p>";
    objectContent.insertBefore(wrapper, objectContent.firstChild);

    const inputEl = document.getElementById("objPlaceSearchInput");
    const btnEl = document.getElementById("objPlaceSearchBtn");
    const statusEl = document.getElementById("objPlaceSearchStatus");
    if (!inputEl || !btnEl || !statusEl) {
      return;
    }

    const runSearch = async () => {
      const text = String(inputEl.value || "").trim();
      if (!text) {
        statusEl.textContent = "Bitte einen Ortsnamen eingeben.";
        return;
      }
      btnEl.disabled = true;
      statusEl.textContent = "Suche läuft...";
      try {
        const lonLat = await searchGeoAdminLocation(text);
        if (!lonLat) {
          statusEl.textContent = "Kein Treffer gefunden.";
          return;
        }
        const mapCoord = lonLatToMapCoord(lonLat[0], lonLat[1]);
        selectedLonLat = [Number(lonLat[0]), Number(lonLat[1])];
        if (typeof setMarker === "function") {
          setMarker(mapCoord);
        }
        if (typeof renderCoord === "function") {
          renderCoord();
        }
        if (typeof map !== "undefined" && map && typeof map.getView === "function") {
          map.getView().animate({ center: mapCoord, zoom: 12, duration: 250 });
        }
        if (typeof drawPreview === "function" && typeof previewActive !== "undefined" && previewActive) {
          drawPreview();
        }
        statusEl.textContent = `Treffer gesetzt: ${lonLat[0].toFixed(6)}, ${lonLat[1].toFixed(6)}`;
      } catch (error) {
        const msg = (error && error.message) ? error.message : "Suche fehlgeschlagen.";
        statusEl.textContent = `Fehler: ${msg}`;
      } finally {
        btnEl.disabled = false;
      }
    };

    btnEl.addEventListener("click", () => {
      void runSearch();
    });
    inputEl.addEventListener("keydown", (evt) => {
      if (evt.key === "Enter") {
        evt.preventDefault();
        void runSearch();
      }
    });
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

    // Stop ruler editing quickly with double-click as requested.
    map.on("dblclick", (evt) => {
      if (!gridRulerActive) {
        return;
      }
      if (evt && typeof evt.preventDefault === "function") {
        evt.preventDefault();
      }
      setRulerActive(false);
      statusEl.textContent = "Ruler stopped (double-click).";
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
    if (typeof calculateFlightPath === "function") {
      calculateFlightPath = function () {
        if (typeof flightPathStatusEl !== "undefined" && flightPathStatusEl) {
          flightPathStatusEl.textContent = "Flugpfad ist im Objekt-Editor nicht verfügbar.";
        }
      };
    }
  }

  async function wgs84PointToLv95Center(lon, lat) {
    const reframeUrl = "https://geodesy.geo.admin.ch/reframe/wgs84tolv95"
      + `?easting=${encodeURIComponent(Number(lon).toFixed(7))}`
      + `&northing=${encodeURIComponent(Number(lat).toFixed(7))}`
      + "&format=json";
    const reframeResponse = await fetch(reframeUrl, { cache: "no-store" });
    if (!reframeResponse.ok) {
      throw new Error("LV95-Reframe fehlgeschlagen.");
    }
    const payload = await reframeResponse.json();
    const e = Number(payload.easting);
    const n = Number(payload.northing);
    if (!Number.isFinite(e) || !Number.isFinite(n)) {
      throw new Error("LV95-Reframe ungültig.");
    }
    return { e, n };
  }

  function objectExtentDiameterMeters() {
    const et = (typeof exportTypeEl !== "undefined" && exportTypeEl) ? exportTypeEl.value : "circles";
    if (et === "grid") {
      const spacing = sanitizeNumber(document.getElementById("spacing").value, 300);
      const northCount = sanitizeNumber(document.getElementById("northCount").value, 8);
      const eastCount = sanitizeNumber(document.getElementById("eastCount").value, 8);
      return Math.hypot(northCount * spacing, eastCount * spacing);
    }
    const r1 = sanitizeNumber(document.getElementById("radius1").value, 100);
    const r2 = sanitizeNumber(document.getElementById("radius2").value, 200);
    const r3 = sanitizeNumber(document.getElementById("radius3").value, 300);
    return Math.max(r1, r2, r3) * 2;
  }

  function swissMapZoomFromDiameter(d) {
    if (!Number.isFinite(d) || d <= 0) {
      return 11;
    }
    if (d < 450) return 13;
    if (d < 1300) return 12;
    if (d < 3800) return 11;
    if (d < 9500) return 10;
    return 9;
  }

  async function saveKmlOnServer(name, kml) {
    const response = await fetch(KML_SAVE_API, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ name, kml, on_rkrs: ON_RKRS })
    });
    const payload = await response.json().catch(() => ({}));
    if (!response.ok || !payload.ok || !payload.public_url) {
      throw new Error((payload && payload.error) ? String(payload.error) : `HTTP ${response.status}`);
    }
    return payload;
  }

  function installSwissTopoMapLinkButton() {
    const exportBtn = document.getElementById("exportBtn");
    if (!exportBtn || document.getElementById("openSwissMapBtn")) return;

    const btn = document.createElement("button");
    btn.id = "openSwissMapBtn";
    btn.type = "button";
    btn.textContent = "In map.geo.admin.ch anzeigen";
    const hint = document.createElement("p");
    hint.className = "small";
    hint.textContent = "Speichert KML auf dem Server und öffnet map.geo.admin.ch mit KML-Link.";
    exportBtn.insertAdjacentElement("afterend", btn);
    btn.insertAdjacentElement("afterend", hint);

    btn.addEventListener("click", async () => {
      if (!Array.isArray(selectedLonLat) || selectedLonLat.length !== 2) {
        alert("Bitte zuerst auf die Karte klicken, um einen Basispunkt zu setzen.");
        return;
      }
      if (typeof previewActive !== "undefined" && !previewActive) {
        alert("Bitte zuerst Auf Karte zeichnen ausführen, damit das Objekt angezeigt ist.");
        return;
      }
      if (typeof exportTypeEl === "undefined" || !exportTypeEl || exportTypeEl.value === "polygon") {
        alert("Im Objekt-Editor sind nur Kreise und Raster verfügbar.");
        return;
      }
      if (typeof fetchTerrainElevations !== "function" || typeof collectPointsForElevation !== "function") {
        alert("Kartenfunktionen noch nicht bereit.");
        return;
      }

      const lon = selectedLonLat[0];
      const lat = selectedLonLat[1];
      btn.disabled = true;
      hint.textContent = "Berechne Höhendaten, erstelle KML und speichere auf Server…";
      try {
        const elevationMap = await fetchTerrainElevations(collectPointsForElevation());
        let kml;
        let fname;
        if (exportTypeEl.value === "circles") {
          if (typeof buildCirclesKml !== "function") {
            throw new Error("buildCirclesKml fehlt");
          }
          kml = buildCirclesKml(lat, lon, elevationMap);
          fname = "rkrk_obj_kreise_mapgeo.kml";
        } else {
          if (typeof buildGridKml !== "function") {
            throw new Error("buildGridKml fehlt");
          }
          kml = buildGridKml(lat, lon, elevationMap);
          fname = "rkrk_obj_raster_mapgeo.kml";
        }
        const saved = await saveKmlOnServer(fname, kml);
        const kmlPublicUrl = String(saved.public_url);

        const { e, n } = await wgs84PointToLv95Center(lon, lat);
        const z = swissMapZoomFromDiameter(objectExtentDiameterMeters());
        const params = [
          `lang=${encodeURIComponent("de")}`,
          `center=${encodeURIComponent(`${Math.round(e)},${Math.round(n)}`)}`,
          `z=${encodeURIComponent(String(z))}`,
          "topic=ech",
          `layers=${encodeURIComponent(`KML|${kmlPublicUrl}`)}`,
          "bgLayer=ch.swisstopo.pixelkarte-farbe",
        ].join("&");
        const viewerUrl = `https://map.geo.admin.ch/#/map?${params}`;
        window.open(viewerUrl, "_blank", "noopener,noreferrer");

        hint.textContent = `KML gespeichert: ${saved.storage_path}. map.geo.admin.ch mit KML-Link geöffnet.`;
      } catch (err) {
        const msg = (err && err.message) ? err.message : String(err);
        hint.textContent = `Fehler: ${msg}`;
        alert(`Konnte nicht vorbereiten: ${msg}`);
      } finally {
        btn.disabled = false;
      }
    });
  }

  function initObjectPage() {
    removeFlightPathUi();
    forceObjectUi();
    streamlineLeftMenu();
    patchDrawingStorage();
    disableFlightPathArtifacts();
    installGridRotationControls();
    patchGridGeometryFunctions();
    installGridRulerTool();
    installPlaceSearchControl();
    installSwissTopoMapLinkButton();
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
if ($showTopMenu && function_exists('CreateTopMenu')) {
    CreateTopMenu($folderoffset);
    echo "<br><br>";
}

if ((isset($Layout) ? $Layout : null) != 2023 && function_exists('CreateSiteLink')) {
    echo "<table>" . chr(10);
    echo "<tr><td width='80%'></td><td class='right' align='right'>" . chr(10);
    echo CreateSiteLink("Home", "");
    echo CreateSiteLink("GoBack", "");
    echo "</td></tr>" . chr(10);
    echo "</table>" . chr(10);
}

echo '<link rel="icon" type="image/x-icon" href="images/favicon.ico">' . chr(10);
echo '<link rel="icon" type="image/png" sizes="32x32" href="images/favicon-32x32.png">' . chr(10);
echo '<link rel="apple-touch-icon" sizes="180x180" href="images/apple-icon-180x180.png">' . chr(10);
echo "<link href='../template/nmenu_style.css' rel='stylesheet' type='text/css'>" . chr(10);
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
  padding: 5px 8px !important;
  font-size: 12px !important;
  line-height: 1.2 !important;
  margin-top: 6px !important;
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
#rkrkMapObjRoot .obj-compact-panel {
  padding: 10px !important;
}
#rkrkMapObjRoot .obj-compact-panel .panel-details {
  margin-top: 8px !important;
}
#rkrkMapObjRoot .obj-compact-panel .panel-details > summary {
  padding: 6px 8px !important;
  font-size: 12px !important;
}
#rkrkMapObjRoot .obj-compact-panel .panel-details-content {
  padding: 0 8px 8px !important;
}
#rkrkMapObjRoot .obj-compact-panel label {
  margin: 5px 0 3px !important;
  font-size: 12px !important;
}
#rkrkMapObjRoot .obj-compact-panel input,
#rkrkMapObjRoot .obj-compact-panel select {
  padding: 6px 7px !important;
  font-size: 12px !important;
}
#rkrkMapObjRoot .obj-compact-panel .small {
  margin: 4px 0 !important;
  font-size: 11px !important;
}
#rkrkMapObjRoot .obj-merged-params {
  margin-top: 8px;
  padding-top: 6px;
  border-top: 1px solid #e2e6eb;
}
#rkrkMapObjRoot .obj-merged-params-title {
  font-weight: 700;
  margin: 4px 0 6px !important;
}
#rkrkMapObjRoot .overlay-box {
  background: rgba(255,255,255,0.94) !important;
  border: 1px solid #d8dde3 !important;
  border-radius: 8px !important;
  box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}
#rkrkMapObjRoot .overlay-box .overlay-option {
  display: grid !important;
  grid-template-columns: 18px 1fr auto !important;
  align-items: center !important;
  gap: 8px !important;
  margin: 5px 0 !important;
}
#rkrkMapObjRoot .overlay-box .overlay-option input[type='checkbox'] {
  margin: 0 !important;
}
#rkrkMapObjRoot .overlay-box .overlay-option input[type='range'] {
  width: 120px !important;
  padding: 0 !important;
}
</style>" . chr(10);
?>
<div id="rkrkMapObjRoot">
<?php
if ($indexBodyContent !== '') {
    echo $indexBodyContent;
    echo rkrk_object_mode_script();
} else {
    echo "<p>Map object editor content could not be loaded from map_editor.html.</p>";
}
?>
</div>
<?php
echo "</body>" . chr(10);
echo "</html>" . chr(10);
?>
