<?php
require_once __DIR__ . '/rkrk_map_obj_core.php';
return;
session_start();
$folderoffset = "../";
// Run subfolder pages with /test as working base for legacy relative paths.
@chdir(__DIR__ . '/..');
include_once(__DIR__ . '/../scripts/rkrk_gen.php');
include_once(__DIR__ . '/../scripts/rkrk_menu.php');
include_once(__DIR__ . '/../rkrk_access_control.php');
$MenuDirOffset = $folderoffset;

CheckIPandExit();

//Write to LOGfile
WriteToLog("open", basename($_SERVER['PHP_SELF']), "","","","","../");

$pagetitle = "Drohnenrehkitzrettung - Karteneditor (Objekte)";
headerpage($pagetitle, false, $folderoffset);
GrantAccess();
//PrintRKRKHeader($pagetitle);

$currentAccessLevel = '';
if (isset($SessNme_AccessLevel) && isset($_SESSION[$SessNme_AccessLevel])) {
    $currentAccessLevel = strtolower(trim((string)$_SESSION[$SessNme_AccessLevel]));
} elseif (isset($InputUserInfo['Login']['Level'])) {
    $currentAccessLevel = strtolower(trim((string)$InputUserInfo['Login']['Level']));
}
$isGuestRequest = (isset($_GET['GA']) && strtolower(trim((string)$_GET['GA'])) === 'gast');
$showTopMenu = (!$isGuestRequest && !empty($LoggedOn) && $currentAccessLevel !== 'gast');

$indexPath = __DIR__ . '/index.html';
$indexHtml = '';
$indexHeadStyle = '';
$indexHeadLinks = [];
$indexBodyContent = '';

function rkrk_scope_editor_css(string $css): string
{
    // Keep standard site CSS as default and scope editor-global selectors to editor root.
    $replacements = [
        '/(^|\})\s*:root\s*\{/i' => '$1 #rkrkMapEditorRoot {',
        '/(^|\})\s*body\s*\{/i' => '$1 #rkrkMapEditorRoot {',
        '/(^|\})\s*h1\s*\{/i' => '$1 #rkrkMapEditorRoot h1 {',
        '/(^|\})\s*p\s*\{/i' => '$1 #rkrkMapEditorRoot p {',
        '/(^|\})\s*label\s*\{/i' => '$1 #rkrkMapEditorRoot label {',
        '/(^|\})\s*input\s*,\s*select\s*,\s*button\s*\{/i' => '$1 #rkrkMapEditorRoot input, #rkrkMapEditorRoot select, #rkrkMapEditorRoot button {',
        '/(^|\})\s*input\[type="checkbox"\]\s*\{/i' => '$1 #rkrkMapEditorRoot input[type="checkbox"] {',
        '/(^|\})\s*button\s*\{/i' => '$1 #rkrkMapEditorRoot button {',
    ];
    return preg_replace(array_keys($replacements), array_values($replacements), $css) ?? $css;
}

function rkrk_mode_script(string $mode): string
{
    $modeJs = json_encode($mode, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    return <<<HTML
<script>
(function () {
  const mode = {$modeJs};
  const storagePrefix = mode === "edit" ? "map_edit__" : "map_obj__";
  const legacyStoragePrefix = mode === "edit" ? "map_edit::" : "map_obj::";
  const DEFAULT_GRID_ROTATION = 0;

  function toStoredName(name) {
    const normalized = String(name || "").trim();
    if (!normalized) return "";
    if (normalized.startsWith(storagePrefix) || normalized.startsWith(legacyStoragePrefix)) {
      return normalized.replace(legacyStoragePrefix, storagePrefix);
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

  function sanitizeStateForMode(state) {
    const nextState = state && typeof state === "object" ? state : {};
    if (mode === "edit") {
      nextState.exportType = "polygon";
      return nextState;
    }
    if (nextState.exportType === "polygon") {
      nextState.exportType = "circles";
    }
    nextState.polygon = { vertices: [] };
    delete nextState.flightPath;
    return nextState;
  }

  function keepOnlyDrawingOptionsForMode() {
    if (typeof drawingListEl === "undefined" || !drawingListEl) return;
    const values = Array.from(drawingListEl.options)
      .map((option) => option.value)
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
  }

  function closestDetails(el) {
    return el && typeof el.closest === "function" ? el.closest("details") : null;
  }

  function normalizeGridRotation(value) {
    const raw = Number(value);
    if (!Number.isFinite(raw)) {
      return DEFAULT_GRID_ROTATION;
    }
    let angle = raw % 360;
    if (angle < 0) {
      angle += 360;
    }
    return angle;
  }

  function getGridRotationAngle() {
    const angleEl = document.getElementById("gridRotationAngle");
    if (!angleEl) {
      return DEFAULT_GRID_ROTATION;
    }
    return normalizeGridRotation(angleEl.value);
  }

  function setGridRotationValue(angle) {
    const normalized = normalizeGridRotation(angle);
    const angleEl = document.getElementById("gridRotationAngle");
    const wheelEl = document.getElementById("gridRotationWheel");
    if (angleEl) {
      angleEl.value = String(Math.round(normalized));
    }
    if (wheelEl) {
      wheelEl.value = String(Math.round(normalized));
    }
  }

  function refreshGridPreviewIfVisible() {
    if (mode !== "obj") {
      return;
    }
    if (typeof previewActive === "undefined" || typeof selectedLonLat === "undefined") {
      return;
    }
    if (previewActive && selectedLonLat && typeof exportTypeEl !== "undefined" && exportTypeEl && exportTypeEl.value === "grid" && typeof drawPreview === "function") {
      drawPreview();
    }
  }

  function injectGridRotationControls() {
    if (mode !== "obj") {
      return;
    }
    const gridSettings = document.getElementById("gridSettings");
    if (!gridSettings || document.getElementById("gridRotationAngle")) {
      return;
    }
    const wrapper = document.createElement("div");
    wrapper.innerHTML = ""
      + "<label for='gridRotationAngle'>Grid rotation (deg)</label>"
      + "<input id='gridRotationAngle' type='number' min='0' max='359' step='1' value='0' />"
      + "<input id='gridRotationWheel' type='range' min='0' max='359' step='1' value='0' />"
      + "<button id='gridRulerAngleBtn' type='button'>Set Grid Angle With Ruler</button>"
      + "<p class='small' id='gridRulerStatus'>Ruler off.</p>"
      + "<p class='small'>Move grid by dragging on map, rotate with this angle.</p>";
    gridSettings.appendChild(wrapper);

    const angleEl = document.getElementById("gridRotationAngle");
    const wheelEl = document.getElementById("gridRotationWheel");
    if (angleEl && wheelEl) {
      angleEl.addEventListener("input", () => {
        setGridRotationValue(angleEl.value);
        refreshGridPreviewIfVisible();
      });
      wheelEl.addEventListener("input", () => {
        setGridRotationValue(wheelEl.value);
        refreshGridPreviewIfVisible();
      });
    }
  }

  function installGridRulerAngleTool() {
    if (mode !== "obj") {
      return;
    }
    if (typeof map === "undefined" || !map || typeof map.on !== "function") {
      return;
    }
    const btn = document.getElementById("gridRulerAngleBtn");
    const statusEl = document.getElementById("gridRulerStatus");
    if (!btn || !statusEl || map.__rkrkGridRulerInstalled) {
      return;
    }
    map.__rkrkGridRulerInstalled = true;

    let active = false;
    const rulerSource = new ol.source.Vector();
    const rulerLayer = new ol.layer.Vector({
      source: rulerSource,
      style: new ol.style.Style({
        stroke: new ol.style.Stroke({ color: "#00d4ff", width: 2, lineDash: [8, 6] })
      })
    });
    map.addLayer(rulerLayer);

    function clearRuler() {
      rulerSource.clear();
    }

    function setActive(nextActive) {
      active = !!nextActive;
      btn.classList.toggle("selected-flight-reference", active);
      statusEl.textContent = active ? "Ruler active: move mouse to set grid direction." : "Ruler off.";
      if (!active) {
        clearRuler();
      }
    }

    function hasGridContext() {
      return typeof exportTypeEl !== "undefined" && exportTypeEl && exportTypeEl.value === "grid"
        && Array.isArray(selectedLonLat) && selectedLonLat.length === 2;
    }

    function updateFromCoordinate(coord3857) {
      if (!hasGridContext() || !Array.isArray(coord3857) || coord3857.length < 2) {
        return;
      }
      const origin = lonLatToMapCoord(selectedLonLat[0], selectedLonLat[1]);
      const dxEast = Number(coord3857[0]) - Number(origin[0]);
      const dyNorth = Number(coord3857[1]) - Number(origin[1]);
      if (!Number.isFinite(dxEast) || !Number.isFinite(dyNorth)) {
        return;
      }
      const distance = Math.sqrt((dxEast * dxEast) + (dyNorth * dyNorth));
      if (distance < 5) {
        return;
      }
      let angle = Math.atan2(dyNorth, dxEast) * 180 / Math.PI;
      if (angle < 0) {
        angle += 360;
      }
      setGridRotationValue(angle);
      refreshGridPreviewIfVisible();
      statusEl.textContent = `Ruler active: angle ${Math.round(angle)} deg`;

      const line = new ol.Feature({ geometry: new ol.geom.LineString([origin, coord3857]) });
      rulerSource.clear();
      rulerSource.addFeature(line);
    }

    btn.addEventListener("click", () => {
      if (!hasGridContext()) {
        statusEl.textContent = "Select grid mode and set base point first.";
        setActive(false);
        return;
      }
      setActive(!active);
    });

    map.on("pointermove", (evt) => {
      if (!active) {
        return;
      }
      if (!hasGridContext()) {
        setActive(false);
        return;
      }
      updateFromCoordinate(evt.coordinate);
    });

    if (typeof exportTypeEl !== "undefined" && exportTypeEl) {
      exportTypeEl.addEventListener("change", () => {
        const isGrid = exportTypeEl.value === "grid";
        btn.style.display = isGrid ? "block" : "none";
        if (!isGrid) {
          setActive(false);
        }
      });
      btn.style.display = exportTypeEl.value === "grid" ? "block" : "none";
    }
  }

  function rotatedOffsetLatLon(originLat, originLon, northMeters, eastMeters) {
    const angleRad = (typeof degToRad === "function") ? degToRad(getGridRotationAngle()) : (getGridRotationAngle() * Math.PI / 180);
    const rotatedEast = (eastMeters * Math.cos(angleRad)) - (northMeters * Math.sin(angleRad));
    const rotatedNorth = (eastMeters * Math.sin(angleRad)) + (northMeters * Math.cos(angleRad));
    return offsetLatLon(originLat, originLon, rotatedNorth, rotatedEast);
  }

  function overrideGridGeometryFunctions() {
    if (mode !== "obj") {
      return;
    }
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
            const cellName = `${eastLabel(e)}${n + 1}`;

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
            const cellName = `${eastLabel(e)}${n + 1}`;
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
        kml += appendFlightPathPointsKml(elevationMap);

        return kml + kmlFooter();
      };
    }
  }

  function configureUiForMode() {
    const heading = document.querySelector("#rkrkMapEditorRoot h1");
    const intro = document.querySelector("#rkrkMapEditorRoot p");
    if (mode === "edit") {
      if (heading) heading.textContent = "RKRK Mission Editor";
      if (intro) {
        intro.textContent = "Create mission polygons, place labeled points and calculate flight paths.";
      }
      if (typeof exportTypeEl !== "undefined" && exportTypeEl) {
        Array.from(exportTypeEl.options).forEach((option) => {
          if (option.value !== "polygon") {
            option.remove();
          }
        });
        exportTypeEl.value = "polygon";
        exportTypeEl.dispatchEvent(new Event("change"));
      }
      if (typeof styleCirclesSectionEl !== "undefined" && styleCirclesSectionEl) {
        styleCirclesSectionEl.style.display = "none";
      }
      if (typeof styleGridSectionEl !== "undefined" && styleGridSectionEl) {
        styleGridSectionEl.style.display = "none";
      }
      if (typeof stylePolygonSectionEl !== "undefined" && stylePolygonSectionEl) {
        stylePolygonSectionEl.style.display = "";
      }
      if (typeof fpHeightEl !== "undefined" && fpHeightEl) {
        const details = closestDetails(fpHeightEl);
        if (details) details.open = true;
      }
      return;
    }

    if (heading) heading.textContent = "RKRK Object Editor";
    if (intro) {
      intro.textContent = "Create object overlays like colored circles and labeled N/E grids.";
    }
    if (typeof exportTypeEl !== "undefined" && exportTypeEl) {
      Array.from(exportTypeEl.options).forEach((option) => {
        if (option.value === "polygon") {
          option.remove();
        }
      });
      if (exportTypeEl.value === "polygon") {
        exportTypeEl.value = "circles";
      }
      exportTypeEl.dispatchEvent(new Event("change"));
    }
    if (typeof styleCirclesSectionEl !== "undefined" && styleCirclesSectionEl) {
      styleCirclesSectionEl.style.display = "";
    }
    if (typeof styleGridSectionEl !== "undefined" && styleGridSectionEl) {
      styleGridSectionEl.style.display = "";
    }
    if (typeof stylePolygonSectionEl !== "undefined" && stylePolygonSectionEl) {
      stylePolygonSectionEl.style.display = "none";
    }
    if (typeof startPolyBtnEl !== "undefined" && startPolyBtnEl) {
      const details = closestDetails(startPolyBtnEl);
      if (details) details.style.display = "none";
    }
    if (typeof fpHeightEl !== "undefined" && fpHeightEl) {
      const details = closestDetails(fpHeightEl);
      if (details) {
        // Object editor has no flight-path workflow at all.
        details.remove();
      }
    }
    // Fallback by title marker in case IDs change.
    Array.from(document.querySelectorAll("details")).forEach((detailsEl) => {
      const summaryEl = detailsEl.querySelector("summary");
      const summaryText = summaryEl ? String(summaryEl.textContent || "").trim() : "";
      if (summaryText.includes("Flight Path")) {
        detailsEl.remove();
      }
    });
    if (typeof calcFlightPathBtnEl !== "undefined" && calcFlightPathBtnEl) {
      calcFlightPathBtnEl.disabled = true;
      calcFlightPathBtnEl.style.display = "none";
    }
    if (typeof clearFlightPathBtnEl !== "undefined" && clearFlightPathBtnEl) {
      clearFlightPathBtnEl.style.display = "none";
    }
    if (typeof deleteFlightSelectedBtnEl !== "undefined" && deleteFlightSelectedBtnEl) {
      deleteFlightSelectedBtnEl.style.display = "none";
    }
    if (typeof circlesSettingsEl !== "undefined" && circlesSettingsEl) {
      const details = closestDetails(circlesSettingsEl);
      if (details) details.open = true;
    }
    if (typeof styleGridSectionEl !== "undefined" && styleGridSectionEl) {
      const details = closestDetails(styleGridSectionEl);
      if (details) details.open = true;
    }
  }

  if (typeof applyDrawingState === "function") {
    const originalApplyDrawingState = applyDrawingState;
    applyDrawingState = function (state) {
      const nextState = sanitizeStateForMode(state);
      const applied = originalApplyDrawingState(nextState);
      if (mode === "obj" && nextState && nextState.grid && nextState.grid.rotationAngle !== undefined) {
        setGridRotationValue(nextState.grid.rotationAngle);
      }
      return applied;
    };
  }

  if (typeof getDrawingState === "function") {
    const originalGetDrawingState = getDrawingState;
    getDrawingState = function () {
      const state = sanitizeStateForMode(originalGetDrawingState());
      if (mode === "obj") {
        delete state.flightPath;
        state.grid = state.grid || {};
        state.grid.rotationAngle = getGridRotationAngle();
      }
      return state;
    };
  }

  if (typeof refreshDrawingList === "function") {
    const originalRefreshDrawingList = refreshDrawingList;
    refreshDrawingList = async function () {
      await originalRefreshDrawingList();
      keepOnlyDrawingOptionsForMode();
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

  if (typeof drawingNameEl !== "undefined" && drawingNameEl) {
    drawingNameEl.value = toDisplayName(drawingNameEl.value);
    drawingNameEl.placeholder = mode === "edit" ? "e.g. mission-area-1" : "e.g. object-set-1";
  }
  // Apply UI cleanup first so Flight Path is removed
  // even if any optional enhancement fails later.
  configureUiForMode();

  // Keep optional object-mode enhancements isolated.
  try {
    injectGridRotationControls();
    installGridRulerAngleTool();
    overrideGridGeometryFunctions();
    setGridRotationValue(DEFAULT_GRID_ROTATION);
  } catch (err) {
    // Keep map usable even if custom enhancement fails.
    console.warn("Object editor enhancement failed:", err);
  }

  if (mode === "obj") {
    // Keep exports/object drawings free from flight-path artifacts.
    if (typeof appendFlightPathPointsKml === "function") {
      appendFlightPathPointsKml = function () { return ""; };
    }
    if (typeof flightPathPoints !== "undefined" && Array.isArray(flightPathPoints)) {
      flightPathPoints = [];
    }
    if (typeof calculateFlightPath === "function") {
      calculateFlightPath = function () {
        if (typeof flightPathStatusEl !== "undefined" && flightPathStatusEl) {
          flightPathStatusEl.textContent = "Flight path is disabled in object editor.";
        }
      };
    }
    if (typeof clearFlightPath === "function") {
      clearFlightPath = function () {};
    }
  }
  if (typeof refreshDrawingList === "function") {
    refreshDrawingList();
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
        $indexHeadStyle = rkrk_scope_editor_css($indexHeadStyle);
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
if ($showTopMenu) {
    CreateTopMenu($folderoffset);
    echo "<br><br>";
}

if ($Layout != 2023) {
    echo "<table>" . chr(10);
    echo "<tr><td width='80%'></td><td class='right' align='right'>" . chr(10);
    echo CreateSiteLink("Home", "");
    echo CreateSiteLink("GoBack", "");
    echo "</td></tr>" . chr(10);
    echo "</table>" . chr(10);
}

echo "<link href='../template/nmenu_style.css' rel='stylesheet' type='text/css'>" . chr(10);
foreach ($indexHeadLinks as $cssLinkTag) {
    echo $cssLinkTag . chr(10);
}
if ($indexHeadStyle !== '') {
    echo "<style>" . chr(10) . $indexHeadStyle . chr(10) . "</style>" . chr(10);
}
?>
<div id="rkrkMapEditorRoot">
<?php
if ($indexBodyContent !== '') {
    echo $indexBodyContent;
    echo rkrk_mode_script('obj');
} else {
    echo "<p>Map editor content could not be loaded from index.html.</p>";
}
?>
</div>
<?php
echo "</body>" . chr(10);
echo "</html>" . chr(10);
?>
