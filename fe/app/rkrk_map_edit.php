<?php
require_once __DIR__ . '/mapll_rkrk_boot.php';

$pagetitle = "Drohnenrehkitzrettung - Karteneditor (Mission)";
mapll_begin_page($pagetitle, $rkrkIntegrated, $folderoffset, true);

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
    nextState.flightPath = { directionMode: "autoLongest", directionAngle: 0 };
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
      if (typeof selectStartForFlightBtnEl !== "undefined" && selectStartForFlightBtnEl) {
        selectStartForFlightBtnEl.style.display = "none";
      }
      if (typeof selectPolygonForFlightBtnEl !== "undefined" && selectPolygonForFlightBtnEl) {
        selectPolygonForFlightBtnEl.style.display = "none";
      }
      if (typeof flightSelectionStatusEl !== "undefined" && flightSelectionStatusEl) {
        flightSelectionStatusEl.textContent = "Auto selection active: Startpunkt + polygon from drawing are used automatically.";
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
      if (details) details.style.display = "none";
    }
  }

  function installDoubleClickDeleteForEditMode() {
    if (mode !== "edit") {
      return;
    }
    if (typeof map === "undefined" || !map || typeof map.getViewport !== "function") {
      return;
    }
    const viewport = map.getViewport();
    if (!viewport || viewport.__rkrkDblDeleteInstalled) {
      return;
    }
    viewport.__rkrkDblDeleteInstalled = true;
    let selectedLabeledPointIndex = -1;

    function deletePointAtPixel(pixel) {
      let deletedSomething = false;

      if (typeof getLabeledPointFeatureAtPixel === "function") {
        const labeledPointFeature = getLabeledPointFeatureAtPixel(pixel);
        if (labeledPointFeature) {
          const index = Number(labeledPointFeature.get("labelPointIndex"));
          if (Number.isInteger(index) && index >= 0 && index < labeledPoints.length) {
            labeledPoints.splice(index, 1);
            if (typeof renderLabeledPoints === "function") {
              renderLabeledPoints();
            }
            if (typeof updateLabeledPointStatus === "function") {
              updateLabeledPointStatus("Point deleted.");
            }
            deletedSomething = true;
          }
        }
      }

      if (!deletedSomething && typeof getPolygonFeatureAtPixel === "function") {
        const polygonVertexFeature = getPolygonFeatureAtPixel(pixel, "vertex");
        if (polygonVertexFeature) {
          const vertexIndex = Number(polygonVertexFeature.get("vertexIndex"));
          if (Number.isInteger(vertexIndex) && vertexIndex >= 0 && vertexIndex < polygonVertices.length) {
            polygonVertices.splice(vertexIndex, 1);
            selectedVertexIndex = -1;
            highlightPolygonForFlight = false;
            flightPolygonVertices = [];
            if (typeof renderPolygonEditor === "function") {
              renderPolygonEditor();
            }
            if (polygonStatusEl) {
              polygonStatusEl.textContent = `Polygon has ${polygonVertices.length} points.`;
            }
            if (typeof updateFlightPathUiState === "function") {
              updateFlightPathUiState();
            }
            deletedSomething = true;
          }
        }
      }

      if (!deletedSomething && typeof getFlightFeatureAtPixel === "function") {
        const flightPointFeature = getFlightFeatureAtPixel(pixel, "point");
        if (flightPointFeature) {
          const pointIndex = Number(flightPointFeature.get("flightPointIndex"));
          if (Number.isInteger(pointIndex) && pointIndex >= 0 && pointIndex < flightPathPoints.length) {
            flightPathPoints.splice(pointIndex, 1);
            selectedFlightFeature = null;
            selectedFlightPointIndex = -1;
            if (typeof renderFlightPath === "function") {
              renderFlightPath();
            }
            if (typeof updateFlightPathUiState === "function") {
              updateFlightPathUiState();
            }
            deletedSomething = true;
          }
        }
      }

      return deletedSomething;
    }

    viewport.addEventListener("dblclick", (domEvt) => {
      const pixel = map.getEventPixel(domEvt);
      const deletedSomething = deletePointAtPixel(pixel);
      if (!deletedSomething) {
        return;
      }
      domEvt.preventDefault();
      domEvt.stopPropagation();
      if (typeof domEvt.stopImmediatePropagation === "function") {
        domEvt.stopImmediatePropagation();
      }
    }, true);

    viewport.addEventListener("contextmenu", (domEvt) => {
      const pixel = map.getEventPixel(domEvt);
      let deletedSomething = false;

      if (selectedLabeledPointIndex >= 0 && selectedLabeledPointIndex < labeledPoints.length) {
        labeledPoints.splice(selectedLabeledPointIndex, 1);
        selectedLabeledPointIndex = -1;
        if (typeof renderLabeledPoints === "function") {
          renderLabeledPoints();
        }
        if (typeof updateLabeledPointStatus === "function") {
          updateLabeledPointStatus("Selected labeled point deleted.");
        }
        deletedSomething = true;
      }

      if (typeof selectedFlightFeature !== "undefined" && selectedFlightFeature) {
        const type = selectedFlightFeature.get("flightType");
        if (type === "point") {
          const pointIndex = Number(selectedFlightFeature.get("flightPointIndex"));
          if (Number.isInteger(pointIndex) && pointIndex >= 0 && pointIndex < flightPathPoints.length) {
            flightPathPoints.splice(pointIndex, 1);
            selectedFlightFeature = null;
            selectedFlightPointIndex = -1;
            if (typeof renderFlightPath === "function") {
              renderFlightPath();
            }
            if (typeof updateFlightPathUiState === "function") {
              updateFlightPathUiState();
            }
            deletedSomething = true;
          }
        }
      }

      if (!deletedSomething && typeof selectedVertexIndex !== "undefined" && Number.isInteger(selectedVertexIndex) && selectedVertexIndex >= 0 && selectedVertexIndex < polygonVertices.length) {
        polygonVertices.splice(selectedVertexIndex, 1);
        selectedVertexIndex = -1;
        highlightPolygonForFlight = false;
        flightPolygonVertices = [];
        if (typeof renderPolygonEditor === "function") {
          renderPolygonEditor();
        }
        if (polygonStatusEl) {
          polygonStatusEl.textContent = `Polygon has ${polygonVertices.length} points.`;
        }
        if (typeof updateFlightPathUiState === "function") {
          updateFlightPathUiState();
        }
        deletedSomething = true;
      }

      if (!deletedSomething) {
        deletedSomething = deletePointAtPixel(pixel);
      }

      if (!deletedSomething) {
        return;
      }
      domEvt.preventDefault();
      domEvt.stopPropagation();
      if (typeof domEvt.stopImmediatePropagation === "function") {
        domEvt.stopImmediatePropagation();
      }
    }, true);

    viewport.addEventListener("click", (domEvt) => {
      const pixel = map.getEventPixel(domEvt);
      selectedLabeledPointIndex = -1;
      if (typeof getLabeledPointFeatureAtPixel !== "function") {
        return;
      }
      const labeledPointFeature = getLabeledPointFeatureAtPixel(pixel);
      if (!labeledPointFeature) {
        return;
      }
      const idx = Number(labeledPointFeature.get("labelPointIndex"));
      if (Number.isInteger(idx) && idx >= 0 && idx < labeledPoints.length) {
        selectedLabeledPointIndex = idx;
        if (typeof updateLabeledPointStatus === "function") {
          updateLabeledPointStatus(`Beschrifteter Punkt ${idx + 1} ausgewählt (Rechtsklick zum Löschen).`);
        }
      }
    }, true);
  }

  function syncFlightReferencesFromDrawing() {
    if (mode !== "edit") {
      return;
    }
    if (typeof labeledPoints !== "undefined" && Array.isArray(labeledPoints)) {
      const definedStartPoint = labeledPoints.find((point) =>
        point && point.type === "start" && Number.isFinite(point.lon) && Number.isFinite(point.lat)
      );
      if (definedStartPoint) {
        flightStartLonLat = [definedStartPoint.lon, definedStartPoint.lat];
        highlightStartForFlight = true;
      } else {
        flightStartLonLat = null;
        highlightStartForFlight = false;
      }
    }
    if (typeof polygonVertices !== "undefined" && Array.isArray(polygonVertices) && polygonVertices.length >= 3) {
      flightPolygonVertices = polygonVertices.map((point) => [point[0], point[1]]);
      highlightPolygonForFlight = true;
    } else {
      flightPolygonVertices = [];
      highlightPolygonForFlight = false;
    }
  }

  function installFlightPathControlBox() {
    if (mode !== "edit") {
      return;
    }
    if (typeof fpHeightEl === "undefined" || !fpHeightEl) {
      return;
    }

    // Keep only the original Flight Path panel and remove any duplicate
    // parameter panel that may have been injected previously.
    const legacyMirrorDetails = document.getElementById("flightPathControlDetails");
    if (legacyMirrorDetails && legacyMirrorDetails.parentNode) {
      legacyMirrorDetails.parentNode.removeChild(legacyMirrorDetails);
    }

    const originalFlightDetails = closestDetails(fpHeightEl);
    if (originalFlightDetails) {
      originalFlightDetails.style.display = "";
      originalFlightDetails.open = true;
    }
  }

  if (typeof applyDrawingState === "function") {
    const originalApplyDrawingState = applyDrawingState;
    applyDrawingState = function (state) {
      return originalApplyDrawingState(sanitizeStateForMode(state));
    };
  }

  if (typeof updateFlightPathUiState === "function") {
    const originalUpdateFlightPathUiState = updateFlightPathUiState;
    updateFlightPathUiState = function () {
      syncFlightReferencesFromDrawing();
      return originalUpdateFlightPathUiState();
    };
  }

  if (typeof calculateFlightPath === "function") {
    const originalCalculateFlightPath = calculateFlightPath;
    calculateFlightPath = function () {
      syncFlightReferencesFromDrawing();
      return originalCalculateFlightPath();
    };
  }

  if (typeof getDrawingState === "function") {
    const originalGetDrawingState = getDrawingState;
    getDrawingState = function () {
      return sanitizeStateForMode(originalGetDrawingState());
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
  configureUiForMode();
  installFlightPathControlBox();
  installDoubleClickDeleteForEditMode();
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
#rkrkMapEditorRoot button {
  background-color: #96B588 !important;
  color: #ffffff !important;
  border: 1px solid #527642 !important;
}
#rkrkMapEditorRoot button:hover {
  background-color: #7ea56f !important;
}
#rkrkMapEditorRoot button.selected-flight-reference,
#rkrkMapEditorRoot button:active {
  background-color: #48673A !important;
  border-color: #3f5933 !important;
  color: #ffffff !important;
}
</style>" . chr(10);
?>
<div id="rkrkMapEditorRoot">
<?php
if ($indexBodyContent !== '') {
    echo $indexBodyContent;
    echo rkrk_mode_script('edit');
} else {
    echo "<p>Map editor content could not be loaded from index.html.</p>";
}
?>
</div>
<?php
echo "</body>" . chr(10);
echo "</html>" . chr(10);
?>
