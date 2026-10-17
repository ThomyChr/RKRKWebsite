/**
 * Shared field geometry for DESK (field_editor.php) and MOB (rkrk_feld_editor_rc.php).
 * The two pages only differ in chrome. Coverage, lens bearing, and waypoint-flag
 * reading live here so both UIs call the same functions.
 */
(function (global) {
  function meterScaleAt(xy) {
    if (!Array.isArray(xy) || xy.length < 2 || !global.ol || !ol.proj) return 1;
    try {
      const lonLat = ol.proj.toLonLat(xy);
      const latRad = Number(lonLat[1]) * Math.PI / 180;
      const cosLat = Math.cos(latRad);
      if (Number.isFinite(cosLat) && cosLat > 1e-6) return 1 / cosLat;
    } catch (_) {}
    return 1;
  }

  function metricsFromFov(aglM, hfovDeg, vfovDeg, overlapFraction) {
    const agl = Number(aglM);
    const hfovRad = (Number(hfovDeg) * Math.PI) / 180;
    const vfovRad = (Number(vfovDeg) * Math.PI) / 180;
    const overlap = Number.isFinite(Number(overlapFraction))
      ? Math.min(0.95, Math.max(0, Number(overlapFraction)))
      : 0.15;
    const widthM = Number.isFinite(agl) && agl > 0 && Number.isFinite(hfovRad)
      ? (2 * agl * Math.tan(hfovRad / 2))
      : NaN;
    const lengthM = Number.isFinite(agl) && agl > 0 && Number.isFinite(vfovRad)
      ? (2 * agl * Math.tan(vfovRad / 2))
      : NaN;
    const spacingM = Number.isFinite(widthM) && widthM > 0
      ? Math.max(0, (1 - overlap) * widthM)
      : NaN;
    return { widthM: widthM, lengthM: lengthM, spacingM: spacingM };
  }

  function displayAtAgl(agl, metrics, refAgl) {
    const h = Number(agl);
    const base = Number.isFinite(h) && h > 1 ? h : (Number.isFinite(refAgl) && refAgl > 0 ? refAgl : 0);
    if (!(base > 0)) return null;
    const scale = Number.isFinite(refAgl) && refAgl > 0 ? (base / refAgl) : 1;
    const widthM = Number(metrics && metrics.widthM) * scale;
    const spacingM = Number(metrics && metrics.spacingM) * scale;
    if (!(widthM > 0) || !Number.isFinite(spacingM) || spacingM < 0) return null;
    const lookM = Number(metrics && metrics.lengthM) * scale;
    const trackM = Number.isFinite(lookM) && lookM > 0 ? lookM : widthM;
    const swathHalfM = (widthM + trackM) / 4;
    return {
      displayM: spacingM + ((widthM - spacingM) / 2),
      lookM: Number.isFinite(lookM) && lookM > 0 ? lookM : 0,
      swathHalfM: Number.isFinite(swathHalfM) && swathHalfM > 0 ? swathHalfM : (widthM * 0.5),
    };
  }

  function pointAgl(z, fallback, row) {
    if (row && row.use_mission_height === false) {
      const stored = Number(row.agl_m);
      if (Number.isFinite(stored) && stored > 1) return stored;
    }
    const h = Number(z);
    if (Number.isFinite(h) && h > 1) return h;
    const fb = Number(fallback);
    return Number.isFinite(fb) && fb > 0 ? fb : 0;
  }

  function headingChange(a, b, c) {
    const v1x = b[0] - a[0];
    const v1y = b[1] - a[1];
    const v2x = c[0] - b[0];
    const v2y = c[1] - b[1];
    const l1 = Math.hypot(v1x, v1y);
    const l2 = Math.hypot(v2x, v2y);
    if (!(l1 > 1e-3) || !(l2 > 1e-3)) return 0;
    const dot = ((v1x / l1) * (v2x / l2)) + ((v1y / l1) * (v2y / l2));
    return Math.acos(Math.max(-1, Math.min(1, dot)));
  }

  function buildCoveragePolygons(pathCoords, aglList, metrics, fallbackAgl, lonLatPts) {
    if (!Array.isArray(pathCoords) || pathCoords.length < 2) return [];
    const sized = pathCoords.map((_, i) => displayAtAgl(aglList ? aglList[i] : fallbackAgl, metrics, fallbackAgl));
    if (sized.some((s) => !s || !(s.displayM > 0))) return [];
    const rings = [];
    const halfAt = (idx) => {
      const piece = sized[idx];
      const half = Number(piece && piece.swathHalfM);
      const meters = Number.isFinite(half) && half > 0 ? half : (piece.displayM * 0.5);
      return meters * meterScaleAt(pathCoords[idx]);
    };
    const turnMin = 35 * Math.PI / 180;
    const turnAt = new Array(pathCoords.length).fill(false);
    for (let i = 1; i < pathCoords.length - 1; i++) {
      if (headingChange(pathCoords[i - 1], pathCoords[i], pathCoords[i + 1]) >= turnMin) turnAt[i] = true;
    }
    function pushQuad(a, b, halfA, halfB) {
      const dx = b[0] - a[0];
      const dy = b[1] - a[1];
      const len = Math.hypot(dx, dy);
      if (!(len > 1e-6) || !(halfA > 0) || !(halfB > 0)) return;
      const nx = -dy / len;
      const ny = dx / len;
      const ring = [
        [a[0] + nx * halfA, a[1] + ny * halfA],
        [b[0] + nx * halfB, b[1] + ny * halfB],
        [b[0] - nx * halfB, b[1] - ny * halfB],
        [a[0] - nx * halfA, a[1] - ny * halfA],
      ];
      ring.push(ring[0].slice());
      rings.push(ring);
    }
    function pushSector(center, radiusMap, a0, sweep) {
      if (!Array.isArray(center) || !(radiusMap > 0) || !(Math.abs(sweep) > 1e-3)) return;
      const steps = Math.max(6, Math.ceil(Math.abs(sweep) / (Math.PI / 14)));
      const ring = [center.slice()];
      for (let s = 0; s <= steps; s++) {
        const a = a0 + sweep * (s / steps);
        ring.push([
          center[0] + Math.cos(a) * radiusMap,
          center[1] + Math.sin(a) * radiusMap,
        ]);
      }
      ring.push(center.slice());
      rings.push(ring);
    }
    for (let i = 0; i < pathCoords.length - 1; i++) {
      const segLenM = (function () {
        const a = lonLatPts && lonLatPts[i];
        const b = lonLatPts && lonLatPts[i + 1];
        if (a && b && global.ol && ol.sphere && typeof ol.sphere.getDistance === 'function') {
          try {
            return Number(ol.sphere.getDistance([Number(a[0]), Number(a[1])], [Number(b[0]), Number(b[1])])) || 0;
          } catch (_) {}
        }
        const dx = pathCoords[i + 1][0] - pathCoords[i][0];
        const dy = pathCoords[i + 1][1] - pathCoords[i][1];
        const scale = meterScaleAt(pathCoords[i]);
        return scale > 0 ? Math.hypot(dx, dy) / scale : 0;
      })();
      const hopMax = 6;
      const turnHop = turnAt[i] && turnAt[i + 1] && segLenM <= hopMax;
      if (turnHop) continue;
      pushQuad(pathCoords[i], pathCoords[i + 1], halfAt(i), halfAt(i + 1));
    }
    for (let i = 1; i < pathCoords.length - 1; i++) {
      const prev = pathCoords[i - 1];
      const curr = pathCoords[i];
      const next = pathCoords[i + 1];
      const v1x = curr[0] - prev[0];
      const v1y = curr[1] - prev[1];
      const v2x = next[0] - curr[0];
      const v2y = next[1] - curr[1];
      const l1 = Math.hypot(v1x, v1y);
      const l2 = Math.hypot(v2x, v2y);
      if (!(l1 > 1e-3) || !(l2 > 1e-3)) continue;
      const ux = v1x / l1;
      const uy = v1y / l1;
      const cross = ux * (v2y / l2) - uy * (v2x / l2);
      const dot = ux * (v2x / l2) + uy * (v2y / l2);
      const sweep = Math.atan2(cross, dot);
      const absSweep = Math.abs(sweep);
      if (!(absSweep > 0.07)) continue;
      const radius = halfAt(i);
      const arcM = sized[i].displayM * 0.5 * absSweep;
      if (!(arcM > 0.8)) continue;
      const extNx = cross > 0 ? uy : -uy;
      const extNy = cross > 0 ? -ux : ux;
      const a0 = Math.atan2(extNy, extNx);
      pushSector(curr, radius, a0, sweep);
    }
    const last = pathCoords.length - 1;
    function addCap(fromIdx, awayIdx) {
      const extM = sized[fromIdx].lookM * 0.5;
      if (!(extM > 0)) return;
      const from = pathCoords[fromIdx];
      const away = pathCoords[awayIdx];
      const dx = from[0] - away[0];
      const dy = from[1] - away[1];
      const len = Math.hypot(dx, dy);
      if (!(len > 1e-6)) return;
      const scale = meterScaleAt(from);
      const tip = [from[0] + (dx / len) * extM * scale, from[1] + (dy / len) * extM * scale];
      pushQuad(from, tip, halfAt(fromIdx), halfAt(fromIdx));
    }
    addCap(0, 1);
    addCap(last, last - 1);
    return rings;
  }

  function groundMeters(a, b) {
    if (!a || !b) return 0;
    const dx = Number(b[0]) - Number(a[0]);
    const dy = Number(b[1]) - Number(a[1]);
    const scale = meterScaleAt(a);
    return scale > 0 ? Math.hypot(dx, dy) / scale : 0;
  }

  function crossTrackMeters(a, b, p) {
    const dx = Number(b[0]) - Number(a[0]);
    const dy = Number(b[1]) - Number(a[1]);
    const len = Math.hypot(dx, dy);
    if (!(len > 1e-6) || !p) return Infinity;
    const cross = Math.abs((Number(p[0]) - Number(a[0])) * dy - (Number(p[1]) - Number(a[1])) * dx) / len;
    const scale = meterScaleAt(p);
    return scale > 0 ? cross / scale : cross;
  }

  // Coverage uses corners and height changes. A point that sits on the straight
  // leg between its neighbours, at the interpolated height, is a filler
  // (duplicate vertex, densify sample, or the injected leg midpoint). It is
  // left out of the band. The stored waypoint list is not changed.
  function coverageSampleIndices(pathCoords, aglList) {
    const n = Array.isArray(pathCoords) ? pathCoords.length : 0;
    if (n < 1) return [];
    const keep = new Array(n).fill(false);
    keep[0] = true;
    keep[n - 1] = true;
    const turnMin = 8 * Math.PI / 180;
    for (let i = 1; i < n - 1; i++) {
      const prev = pathCoords[i - 1];
      const curr = pathCoords[i];
      const next = pathCoords[i + 1];
      if (headingChange(prev, curr, next) >= turnMin) {
        keep[i] = true;
        continue;
      }
      const off = crossTrackMeters(prev, next, curr);
      const seg = groundMeters(prev, next);
      const along = groundMeters(prev, curr);
      const t = seg > 1e-3 ? Math.max(0, Math.min(1, along / seg)) : 0;
      const z0 = Number(aglList && aglList[i - 1]);
      const z1 = Number(aglList && aglList[i + 1]);
      const z = Number(aglList && aglList[i]);
      const zHat = (Number.isFinite(z0) && Number.isFinite(z1)) ? (z0 + (z1 - z0) * t) : z;
      const heightBreak = Number.isFinite(z) && Number.isFinite(zHat) && Math.abs(z - zHat) > 0.3;
      if (!(off <= 1.5) || heightBreak) keep[i] = true;
    }
    const idx = [];
    for (let i = 0; i < n; i++) {
      if (!keep[i]) continue;
      if (idx.length && groundMeters(pathCoords[idx[idx.length - 1]], pathCoords[i]) < 0.4) {
        if (i === n - 1) idx[idx.length - 1] = i;
        continue;
      }
      idx.push(i);
    }
    return idx.length >= 2 ? idx : [0, n - 1];
  }

  function buildRouteCoveragePolygons(pathCoords, aglList, metrics, fallbackAgl, lonLatPts, routeCalculated) {
    if (!routeCalculated) return [];
    if (!Array.isArray(pathCoords) || pathCoords.length < 2) return [];
    const idx = coverageSampleIndices(pathCoords, aglList);
    const coords = idx.map((i) => pathCoords[i]);
    const agl = idx.map((i) => aglList ? aglList[i] : fallbackAgl);
    const lonLat = idx.map((i) => lonLatPts ? lonLatPts[i] : null);
    return buildCoveragePolygons(coords, agl, metrics, fallbackAgl, lonLat);
  }

  function nextLegBearingDeg(mapCoords, index0) {
    const n = Array.isArray(mapCoords) ? mapCoords.length : 0;
    if (n < 2 || index0 < 0 || index0 >= n - 1) return null;
    const from = mapCoords[index0];
    const to = mapCoords[index0 + 1];
    if (!from || !to) return null;
    const dx = Number(to[0]) - Number(from[0]);
    const dy = Number(to[1]) - Number(from[1]);
    if (!(Math.hypot(dx, dy) > 1e-6)) return null;
    return (Math.atan2(dx, dy) * 180 / Math.PI + 360) % 360;
  }

  function formatMeterLabel(meters) {
    const n = Number(meters);
    if (!Number.isFinite(n)) return '';
    const tenth = Math.round(n * 10) / 10;
    const text = Math.abs(tenth - Math.round(tenth)) < 0.001 ? String(Math.round(tenth)) : tenth.toFixed(1);
    return text + ' m';
  }

  function programmedFlightHeightM(missionHeightM, row, pathZM) {
    const flag = row && Object.prototype.hasOwnProperty.call(row, 'use_mission_height')
      ? jsonFlag(row.use_mission_height)
      : null;
    if (flag === false) {
      const local = Number(row.agl_m);
      if (Number.isFinite(local)) return local;
      const z = Number(pathZM);
      if (Number.isFinite(z)) return z;
    }
    const mission = Number(missionHeightM);
    if (Number.isFinite(mission)) return mission;
    const z = Number(pathZM);
    return Number.isFinite(z) ? z : null;
  }

  function heightAboveGroundM(relStartM, terrainM, startTerrainM) {
    const rel = Number(relStartM);
    if (!Number.isFinite(rel)) return null;
    const terrain = Number(terrainM);
    const start = Number(startTerrainM);
    if (!Number.isFinite(terrain) || !Number.isFinite(start)) return rel;
    const agl = rel - (terrain - start);
    return Number.isFinite(agl) ? agl : rel;
  }

  function relativeHeightFromStartM(programmedM, terrainM, startTerrainM) {
    const programmed = Number(programmedM);
    if (!Number.isFinite(programmed)) return null;
    const terrain = Number(terrainM);
    const start = Number(startTerrainM);
    if (!Number.isFinite(terrain) || !Number.isFinite(start)) return programmed;
    const delta = terrain - start;
    if (!Number.isFinite(delta) || delta === 0) return programmed;
    return programmed + delta;
  }

  function heightCaption(programmedM, terrainM, startTerrainM) {
    const programmed = Number(programmedM);
    if (!Number.isFinite(programmed)) return { main: '', paren: '' };
    const rel = relativeHeightFromStartM(programmed, terrainM, startTerrainM);
    return {
      main: formatMeterLabel(programmed),
      paren: '(' + formatMeterLabel(rel) + ')',
    };
  }

  function waypointMissesMission(row) {
    const keys = ['use_mission_height', 'use_mission_speed', 'use_mission_waypoint_type', 'use_mission_yaw', 'use_mission_path_mode', 'use_mission_direction_mode'];
    if (!row || typeof row !== 'object') return false;
    return keys.some((key) => Object.prototype.hasOwnProperty.call(row, key) && jsonFlag(row[key]) === false);
  }

  function bearingDegLonLat(lon1, lat1, lon2, lat2) {
    const p1 = Number(lat1) * Math.PI / 180;
    const p2 = Number(lat2) * Math.PI / 180;
    const dl = (Number(lon2) - Number(lon1)) * Math.PI / 180;
    const y = Math.sin(dl) * Math.cos(p2);
    const x = Math.cos(p1) * Math.sin(p2) - Math.sin(p1) * Math.cos(p2) * Math.cos(dl);
    return (Math.atan2(y, x) * 180 / Math.PI + 360) % 360;
  }

  function finiteYawAngle(value) {
    if (value == null || value === '') return null;
    if (typeof value === 'string' && value.trim().toLowerCase() === 'null') return null;
    const n = Number(value);
    return Number.isFinite(n) ? n : null;
  }

  function yawSymbol(mode, angleDeg, nextLegDeg) {
    const m = String(mode || '').toLowerCase();
    const angle = finiteYawAngle(angleDeg);
    const leg = finiteYawAngle(nextLegDeg);
    const hasAngle = angle != null;
    const hasLeg = leg != null;
    const norm = (deg) => ((deg % 360) + 360) % 360;
    if (/poi|fokus|smooth/.test(m)) {
      if (!hasAngle) return { kind: 'none', bearingDeg: null };
      return { kind: 'triangle', bearingDeg: norm(angle) };
    }
    if (/manuell|manual/.test(m)) return { kind: 'none', bearingDeg: null };
    if (/schenk|schwenk/.test(m) || m === 'fixed' || m === 'lock') return { kind: 'x', bearingDeg: null };
    if (/follow|richtung|wegpunkt|wayline/.test(m)) {
      if (!hasLeg) return { kind: 'none', bearingDeg: null };
      return { kind: 'triangle', bearingDeg: norm(leg) };
    }
    if (/definiert|customis/.test(m)) {
      if (!hasAngle) return { kind: 'none', bearingDeg: null };
      return { kind: 'triangle', bearingDeg: norm(angle) };
    }
    return { kind: 'none', bearingDeg: null };
  }

  function yawSymbolLayout(mode, angleDeg, nextLegDeg, center, resolution) {
    const sym = yawSymbol(mode, angleDeg, nextLegDeg);
    const unit = Number.isFinite(Number(resolution)) && Number(resolution) > 0 ? Number(resolution) : 1;
    if (!center || !Number.isFinite(Number(center[0])) || !Number.isFinite(Number(center[1]))) return { kind: 'none' };
    if (sym.kind === 'x') {
      const d = 9 * unit;
      return {
        kind: 'x',
        color: '#e23d2b',
        lines: [
          [[center[0] - d, center[1] - d], [center[0] + d, center[1] + d]],
          [[center[0] - d, center[1] + d], [center[0] + d, center[1] - d]],
        ],
      };
    }
    if (sym.kind !== 'triangle' || !Number.isFinite(sym.bearingDeg)) return { kind: 'none' };
    const rad = sym.bearingDeg * Math.PI / 180;
    const east = Math.sin(rad);
    const north = Math.cos(rad);
    const sideX = north;
    const sideY = -east;
    const length = 26 * unit;
    const halfW = 11 * unit;
    const bx = center[0] + east * length;
    const by = center[1] + north * length;
    return {
      kind: 'triangle',
      ring: [
        [center[0], center[1]],
        [bx + sideX * halfW, by + sideY * halfW],
        [bx - sideX * halfW, by - sideY * halfW],
        [center[0], center[1]],
      ],
    };
  }

  function lensBearingDeg(mapCoords, index0, yawDeg) {
    const yaw = finiteYawAngle(yawDeg);
    if (yaw != null) return (yaw % 360 + 360) % 360;
    const n = Array.isArray(mapCoords) ? mapCoords.length : 0;
    if (n < 2 || index0 < 0 || index0 >= n) return 0;
    let from = mapCoords[index0];
    let to = mapCoords[index0 + 1];
    if (!to && index0 > 0) {
      from = mapCoords[index0 - 1];
      to = mapCoords[index0];
    }
    if (!from || !to) return 0;
    const dx = Number(to[0]) - Number(from[0]);
    const dy = Number(to[1]) - Number(from[1]);
    if (!(Math.hypot(dx, dy) > 1e-6)) return 0;
    return (Math.atan2(dx, dy) * 180 / Math.PI + 360) % 360;
  }

  function easyMissionDefaults() {
    return {
      direct_to_start_position: false,
      speed_m_s: 4.5,
      end_action: 'Aufgabe beenden',
      heading_mode: 'Richtung zum naechsten Wegpunkt',
      waypoint_type: 'Koordinierte Drehung, Ueberspringt den Wegpunkt',
      standard_turn_distance_m: 5,
      safe_start_altitude_m: 20,
      start_speed_active: false,
      start_speed_m_s: 5,
      gimbal_mode: 'Manuell',
      altitude_mode: 'ALT (Relativ zum Startpunkt)',
    };
  }

  function overridesNeedExpert(overrides) {
    const keys = ['use_mission_height', 'use_mission_speed', 'use_mission_waypoint_type', 'use_mission_yaw', 'use_mission_path_mode', 'use_mission_direction_mode'];
    return Object.values(overrides || {}).some((row) => row && keys.some((key) => row[key] === false || row[key] === 0 || row[key] === '0'));
  }

  function jsonFlag(value) {
    if (value === true || value === 1 || value === '1' || value === 'true') return true;
    if (value === false || value === 0 || value === '0' || value === 'false') return false;
    return null;
  }

  function headingModeFromUav(value) {
    const mode = String(value || '').toLowerCase();
    if (!mode || mode === 'none' || mode === 'null') return '';
    if (mode === 'manually' || mode === 'manual') return 'Manuell';
    if (mode === 'towardpoi' || mode === 'poi' || mode === 'smoothtransition' || mode === 'smooth') return 'Fokus POI';
    if (mode === 'customised' || mode === 'customized') return 'Definiert';
    if (mode === 'fixed' || mode === 'lock') return 'Schenkachse sperren';
    if (mode === 'followwayline') return 'Richtung zum naechsten Wegpunkt';
    return '';
  }

  function headingModeFromKmz(value) {
    const mode = String(value || '').toLowerCase();
    if (!mode || mode === 'none' || mode === 'null') return '';
    if (mode === 'manually' || mode === 'manual') return 'Manuell';
    if (mode === 'towardpoi' || mode === 'poi') return 'Fokus POI';
    if (mode === 'smoothtransition' || mode === 'smooth') return 'Definiert';
    if (mode === 'fixed' || mode === 'lock') return 'Schenkachse sperren';
    if (mode === 'followwayline') return 'Richtung zum naechsten Wegpunkt';
    return '';
  }

  function headingModeForWaypoint(props, yawFlag) {
    const follow = 'Richtung zum naechsten Wegpunkt';
    const fromType = headingModeFromUav(props && props.directionType);
    const fromYaw = headingModeFromUav(props && props.yawDirection);
    const concreteType = fromType && fromType !== follow ? fromType : '';
    if (concreteType) return concreteType;
    const concreteYaw = fromYaw && fromYaw !== follow ? fromYaw : '';
    if (yawFlag === false) return concreteYaw || fromYaw || fromType || follow;
    return concreteYaw || fromYaw || fromType || follow;
  }

  function shownByFlag(flag, local, mission) {
    const state = jsonFlag(flag);
    const numeric = Number(local);
    const hasLocal = (typeof local === 'string' && local !== '')
      || (local != null && local !== '' && !Number.isNaN(numeric) && Number.isFinite(numeric))
      || (local != null && typeof local !== 'number' && typeof local !== 'string');
    if (state === true) return mission;
    if (state === false) return hasLocal ? local : mission;
    return hasLocal ? local : mission;
  }

  function waypointTypeFromUav(value) {
    const mode = String(value || '');
    if (mode === 'coordinateTurn') return 'Koordinierte Drehung, Ueberspringt den Wegpunkt';
    if (mode === 'toPointAndStopWithDiscontinuityCurvature') return 'Gerade Flugroute. Fluggeraet stoppt';
    if (mode === 'toPointAndPassWithContinuityCurvature2') return 'Wendet vor dem Wegpunkt. Fliegt durch';
    if (mode === 'toPointAndPassWithContinuityCurvature') return 'Gekruemmte Route. Fluggeraet setzt fort';
    if (mode === 'toPointAndStopWithContinuityCurvature') return 'Gekruemmte Route. Fluggeraet stoppt';
    return '';
  }

  function alignmentFromUav(value) {
    const mode = String(value || '');
    if (mode === 'clockwise') return 'Uhrzeigersinn';
    if (mode === 'counterClockwise') return 'Gegen den Uhrzeigersinn';
    if (mode === 'followBadArc' || mode === 'Kuerzester Drehweg' || mode === 'Automatisch') return 'Automatisch';
    return '';
  }

  function poiRecord(lon, lat, height, sea) {
    const longitude = Number(lon);
    const latitude = Number(lat);
    if (!Number.isFinite(longitude) || !Number.isFinite(latitude)) return null;
    const record = {
      longitude: longitude,
      latitude: latitude,
      height: Number.isFinite(Number(height)) ? Number(height) : 0,
    };
    if (sea != null && sea !== '' && String(sea).toLowerCase() !== 'null' && Number.isFinite(Number(sea))) {
      record.sea_m = Number(sea);
    }
    return record;
  }

  function poisFromUavFeatures(features) {
    const pois = [];
    (Array.isArray(features) ? features : []).forEach((feature) => {
      const geom = feature && feature.geometry;
      const props = feature && feature.properties;
      if (!geom || geom.type !== 'Point' || !props || Number(props.type) !== 2) return;
      const coords = Array.isArray(geom.coordinates) ? geom.coordinates : [];
      const seaRaw = props.aboveSea;
      const sea = seaRaw && typeof seaRaw === 'object' ? seaRaw.value : seaRaw;
      const poi = poiRecord(coords[0], coords[1], props.height, sea);
      if (poi) pois.push(poi);
    });
    return pois;
  }

  function poiAtIndex(pois, raw) {
    const list = Array.isArray(pois) ? pois : [];
    const n = Number(raw);
    if (!list.length || !Number.isFinite(n)) return null;
    const idx = Math.trunc(n);
    if (idx >= 0 && idx < list.length) return list[idx];
    if (idx >= 1 && idx <= list.length) return list[idx - 1];
    return null;
  }

  function poiFromUav(props) {
    if (!props || typeof props !== 'object') return null;
    const raw = props.poi || props.waypointPoiPoint || props.poiPoint || null;
    if (raw && typeof raw === 'object') {
      const lon = Number(raw.longitude != null ? raw.longitude : raw.lng);
      const lat = Number(raw.latitude != null ? raw.latitude : raw.lat);
      const height = Number(raw.height);
      if (Number.isFinite(lon) && Number.isFinite(lat)) {
        return { longitude: lon, latitude: lat, height: Number.isFinite(height) ? height : 0 };
      }
    }
    if (typeof raw === 'string' && raw.indexOf(',') >= 0) {
      const parts = raw.split(',').map((part) => Number(String(part).trim()));
      if (parts.length >= 2 && Number.isFinite(parts[0]) && Number.isFinite(parts[1])) {
        return {
          latitude: parts[0],
          longitude: parts[1],
          height: Number.isFinite(parts[2]) ? parts[2] : 0,
        };
      }
    }
    return null;
  }

  function readUavWaypointOverrides(doc) {
    const features = doc && doc.geojson && Array.isArray(doc.geojson.features) ? doc.geojson.features : [];
    const pois = poisFromUavFeatures(features);
    const overrides = {};
    let anyFalse = false;
    let n = 0;
    features.forEach((feature) => {
      const geom = feature && feature.geometry;
      const props = feature && feature.properties;
      if (!geom || geom.type !== 'Point' || !props || typeof props !== 'object') return;
      if (Number(props.type) !== 1) return;
      n += 1;
      const flags = {
        use_mission_height: jsonFlag(props.useMissionHeight),
        use_mission_speed: jsonFlag(props.useMissionSpeed),
        use_mission_waypoint_type: jsonFlag(props.useMissionWaypointType),
        use_mission_yaw: jsonFlag(props.useMissionYawDirection),
        use_mission_path_mode: jsonFlag(props.useMissionPathMode),
        use_mission_direction_mode: jsonFlag(props.useMissionDirectionMode),
      };
      const row = {};
      Object.keys(flags).forEach((key) => {
        if (flags[key] === true || flags[key] === false) row[key] = flags[key];
      });
      if (Object.values(flags).some((flag) => flag === false)) anyFalse = true;
      if (Number.isFinite(Number(props.height))) row.agl_m = Number(props.height);
      if (Number.isFinite(Number(props.speed))) row.speed_m_s = Number(props.speed);
      const wpType = waypointTypeFromUav(props.waypointType);
      if (wpType) row.waypoint_type = wpType;
      if (row.use_mission_waypoint_type === true || row.use_mission_waypoint_type === false) {
        row.use_mission_turn_distance = row.use_mission_waypoint_type;
      }
      if (Number.isFinite(Number(props.turningDistance))) row.turning_distance_m = Number(props.turningDistance);
      else if (Number.isFinite(Number(props.curveSize)) && Number(props.curveSize) > 0) row.turning_distance_m = Number(props.curveSize);
      let heading = headingModeForWaypoint(props, flags.use_mission_yaw);
      const dirRaw = props.direction;
      const dirText = dirRaw == null ? '' : String(dirRaw).trim().toLowerCase();
      if (dirRaw != null && dirText !== '' && dirText !== 'null' && Number.isFinite(Number(dirRaw))) {
        row.heading_angle = Number(dirRaw);
        row.direction_deg = Number(dirRaw);
      }
      const seaRaw = props.aboveSea;
      const sea = seaRaw && typeof seaRaw === 'object' ? Number(seaRaw.value) : Number(seaRaw);
      if (Number.isFinite(sea)) row.sea_m = sea;
      if (typeof props.pathMode === 'string' && props.pathMode) row.path_mode = props.pathMode;
      const align = alignmentFromUav(props.alignmentAircraft);
      if (align) row.alignment_aircraft = align;
      let poi = poiFromUav(props);
      const wantsPoi = heading === 'Fokus POI';
      if (!poi && wantsPoi) poi = poiAtIndex(pois, props.poiIndex) || (pois.length === 1 ? pois[0] : null);
      if (wantsPoi && !poi) {
        heading = 'Richtung zum naechsten Wegpunkt';
        delete row.heading_angle;
        delete row.direction_deg;
      }
      if (heading) row.heading_mode = heading;
      if (poi && wantsPoi) row.poi = { longitude: Number(poi.longitude), latitude: Number(poi.latitude), height: Number(poi.height) || 0 };
      if (props.autoHeight === false || props.autoHeight === 0 || props.autoHeight === '0' || props.autoHeight === 'false') row.auto_height = false;
      else if (props.autoHeight === true || props.autoHeight === 1 || props.autoHeight === '1' || props.autoHeight === 'true') row.auto_height = true;
      overrides[String(n)] = row;
    });
    let easyModeForm = null;
    const pilot = doc && doc.missionSettings && doc.missionSettings.djiPilot2;
    const easyPilot = jsonFlag(pilot && pilot.easyModeForm);
    if (easyPilot === true || easyPilot === false) easyModeForm = easyPilot;
    else {
      const last = doc && doc.lastAutoGenerateSettings && doc.lastAutoGenerateSettings.settings;
      const easyLast = jsonFlag(last && last.easyModeForm);
      if (easyLast === true || easyLast === false) easyModeForm = easyLast;
    }
    return { overrides: overrides, anyFalse: anyFalse, easyModeForm: easyModeForm, count: n, pois: pois };
  }

  function differs(a, b) {
    const x = Number(a);
    const y = Number(b);
    if (!Number.isFinite(x) || !Number.isFinite(y)) return false;
    return Math.abs(x - y) >= 0.05;
  }

  function wpmlTag(block, tag) {
    const m = String(block || '').match(new RegExp('<wpml:' + tag + '>\\s*([^<]*)<\\/wpml:' + tag + '>', 'i'));
    return m ? String(m[1]).trim() : null;
  }

  function flagIsOff(value) {
    const flag = jsonFlag(value);
    if (flag === false) return true;
    const text = String(value == null ? '' : value).trim().toLowerCase();
    return text === '0' || text === 'false';
  }

  function applyKmzDeviations(parsed, kmlText) {
    const xml = String(kmlText || '');
    if (!parsed || !xml || xml.indexOf('Placemark') < 0) return parsed;
    const globalHeight = Number(wpmlTag(xml, 'globalHeight'));
    const globalSpeed = Number(wpmlTag(xml, 'autoFlightSpeed'));
    const globalHeading = headingModeFromKmz(wpmlTag(xml, 'waypointHeadingMode'));
    const blocks = xml.match(/<Placemark\b[^>]*>[\s\S]*?<\/Placemark>/gi) || [];
    let seq = 0;
    blocks.forEach((block) => {
      if (!/<coordinates>\s*[-0-9.]+\s*,\s*[-0-9.]+/i.test(block)) return;
      const indexMatch = block.match(/<wpml:index>\s*(\d+)\s*<\/wpml:index>/i);
      const idx = indexMatch ? (Number(indexMatch[1]) + 1) : (seq + 1);
      seq += 1;
      if (!parsed.overrides[String(idx)]) parsed.overrides[String(idx)] = {};
      const row = parsed.overrides[String(idx)];
      const heightFlag = wpmlTag(block, 'useGlobalHeight');
      const kmzHeight = Number(wpmlTag(block, 'height'));
      if (flagIsOff(heightFlag)) {
        row.use_mission_height = false;
        parsed.anyFalse = true;
        if (Number.isFinite(kmzHeight)) row.rel_height_m = kmzHeight;
      } else if (heightFlag == null && differs(kmzHeight, globalHeight)) {
        row.use_mission_height = false;
        parsed.anyFalse = true;
        row.rel_height_m = kmzHeight;
      }
      const speedFlag = wpmlTag(block, 'useGlobalSpeed');
      const kmzSpeed = Number(wpmlTag(block, 'waypointSpeed'));
      if (flagIsOff(speedFlag)) {
        row.use_mission_speed = false;
        parsed.anyFalse = true;
        if (Number.isFinite(kmzSpeed)) row.speed_m_s = kmzSpeed;
      } else if (speedFlag == null && differs(kmzSpeed, globalSpeed)) {
        row.use_mission_speed = false;
        parsed.anyFalse = true;
        row.speed_m_s = kmzSpeed;
      }
      const turnFlag = wpmlTag(block, 'useGlobalTurnParam');
      const turnMode = waypointTypeFromUav(wpmlTag(block, 'waypointTurnMode'));
      const damp = Number(wpmlTag(block, 'waypointTurnDampingDist'));
      if (flagIsOff(turnFlag)) {
        row.use_mission_waypoint_type = false;
        parsed.anyFalse = true;
        if (turnMode) row.waypoint_type = turnMode;
        if (Number.isFinite(damp)) row.turning_distance_m = damp;
      } else if (turnFlag == null && turnMode) {
        row.use_mission_waypoint_type = false;
        parsed.anyFalse = true;
        row.waypoint_type = turnMode;
        if (Number.isFinite(damp)) row.turning_distance_m = damp;
      }
      const yawFlag = wpmlTag(block, 'useGlobalHeadingParam');
      const yawMode = headingModeFromKmz(wpmlTag(block, 'waypointHeadingMode'));
      const yawAngleText = wpmlTag(block, 'waypointHeadingAngle');
      const yawAngle = yawAngleText == null || String(yawAngleText).trim() === '' ? null : Number(yawAngleText);
      const poiText = wpmlTag(block, 'waypointPoiPoint');
      if (flagIsOff(yawFlag)) {
        row.use_mission_yaw = false;
        parsed.anyFalse = true;
        if (yawMode) row.heading_mode = yawMode;
        if (yawAngle != null && Number.isFinite(yawAngle)) {
          row.heading_angle = yawAngle;
          row.direction_deg = yawAngle;
        }
      } else if (yawFlag == null && yawMode && yawMode !== globalHeading) {
        row.use_mission_yaw = false;
        parsed.anyFalse = true;
        row.heading_mode = yawMode;
        if (yawAngle != null && Number.isFinite(yawAngle)) {
          row.heading_angle = yawAngle;
          row.direction_deg = yawAngle;
        }
      }
      if (poiText) {
        const parts = poiText.split(',').map((part) => Number(String(part).trim()));
        if (parts.length >= 2 && Number.isFinite(parts[0]) && Number.isFinite(parts[1])) {
          row.poi = {
            latitude: parts[0],
            longitude: parts[1],
            height: Number.isFinite(parts[2]) ? parts[2] : 0,
          };
          row.use_mission_yaw = false;
          row.heading_mode = 'Fokus POI';
          parsed.anyFalse = true;
        }
      }
      parsed.count = Math.max(parsed.count || 0, idx);
    });
    return parsed;
  }

  function noteLocalValueDeviations(parsed, doc) {
    if (!parsed || !parsed.overrides) return parsed;
    const pilot = doc && doc.missionSettings && doc.missionSettings.djiPilot2;
    const missionHeight = Number(pilot && pilot.height);
    const missionSpeed = Number(pilot && pilot.speed);
    const missionHeading = headingModeFromUav(pilot && (pilot.directionMode || pilot.headingMode || pilot.yawDirection));
    const missionType = waypointTypeFromUav(pilot && pilot.waypointType);
    const missionPath = String(pilot && (pilot.pathModus || pilot.pathMode) || '');
    Object.keys(parsed.overrides).forEach((key) => {
      const row = parsed.overrides[key];
      if (!row) return;
      const heightFlag = jsonFlag(row.use_mission_height);
      if (heightFlag !== false && differs(row.agl_m, missionHeight)) {
        row.use_mission_height = false;
        parsed.anyFalse = true;
      }
      const speedFlag = jsonFlag(row.use_mission_speed);
      if (speedFlag == null && differs(row.speed_m_s, missionSpeed)) {
        row.use_mission_speed = false;
        parsed.anyFalse = true;
      }
      const typeFlag = jsonFlag(row.use_mission_waypoint_type);
      if (typeFlag == null && row.waypoint_type && missionType && row.waypoint_type !== missionType) {
        row.use_mission_waypoint_type = false;
        row.use_mission_turn_distance = false;
        parsed.anyFalse = true;
      }
      const yawFlag = jsonFlag(row.use_mission_yaw);
      if (yawFlag == null && row.heading_mode && missionHeading && row.heading_mode !== missionHeading) {
        row.use_mission_yaw = false;
        parsed.anyFalse = true;
      }
      if (yawFlag == null && row.poi && row.poi.longitude != null) {
        row.use_mission_yaw = false;
        row.heading_mode = row.heading_mode || 'Fokus POI';
        parsed.anyFalse = true;
      }
      const pathFlag = jsonFlag(row.use_mission_path_mode);
      if (pathFlag == null && row.path_mode && missionPath && String(row.path_mode) !== missionPath) {
        row.use_mission_path_mode = false;
        parsed.anyFalse = true;
      }
    });
    return parsed;
  }

  function readMissionDeviations(doc) {
    return noteLocalValueDeviations(readUavWaypointOverrides(doc), doc);
  }

  async function templateKmlFromKmz(buffer) {
    if (!buffer) return '';
    const bytes = buffer instanceof Uint8Array ? buffer : new Uint8Array(buffer);
    const view = new DataView(bytes.buffer, bytes.byteOffset, bytes.byteLength);
    let offset = 0;
    while (offset + 30 <= bytes.length) {
      if (view.getUint32(offset, true) !== 0x04034b50) break;
      const method = view.getUint16(offset + 8, true);
      const compSize = view.getUint32(offset + 18, true);
      const nameLen = view.getUint16(offset + 26, true);
      const extraLen = view.getUint16(offset + 28, true);
      const nameStart = offset + 30;
      const name = new TextDecoder().decode(bytes.subarray(nameStart, nameStart + nameLen));
      const dataStart = nameStart + nameLen + extraLen;
      const data = bytes.subarray(dataStart, dataStart + compSize);
      if (/template\.kml$/i.test(name)) {
        if (method === 0) return new TextDecoder().decode(data);
        if (method === 8 && typeof DecompressionStream === 'function') {
          const stream = new Blob([data]).stream().pipeThrough(new DecompressionStream('deflate-raw'));
          return await new Response(stream).text();
        }
      }
      offset = dataStart + compSize;
    }
    return '';
  }

  global.RkrkField = {
    meterScaleAt: meterScaleAt,
    metricsFromFov: metricsFromFov,
    displayAtAgl: displayAtAgl,
    pointAgl: pointAgl,
    headingChange: headingChange,
    buildCoveragePolygons: buildCoveragePolygons,
    buildRouteCoveragePolygons: buildRouteCoveragePolygons,
    nextLegBearingDeg: nextLegBearingDeg,
    formatMeterLabel: formatMeterLabel,
    programmedFlightHeightM: programmedFlightHeightM,
    heightAboveGroundM: heightAboveGroundM,
    relativeHeightFromStartM: relativeHeightFromStartM,
    heightCaption: heightCaption,
    waypointMissesMission: waypointMissesMission,
    bearingDegLonLat: bearingDegLonLat,
    yawSymbol: yawSymbol,
    yawSymbolLayout: yawSymbolLayout,
    lensBearingDeg: lensBearingDeg,
    easyMissionDefaults: easyMissionDefaults,
    overridesNeedExpert: overridesNeedExpert,
    jsonFlag: jsonFlag,
    shownByFlag: shownByFlag,
    headingModeFromUav: headingModeFromUav,
    headingModeFromKmz: headingModeFromKmz,
    readUavWaypointOverrides: readUavWaypointOverrides,
    readMissionDeviations: readMissionDeviations,
    templateKmlFromKmz: templateKmlFromKmz,
    paintDebugParamHints: paintDebugParamHints,
  };

  function paintDebugParamHints(pairs, visible) {
    document.querySelectorAll('.debug-param').forEach((el) => el.remove());
    if (!visible || !Array.isArray(pairs)) return;
    pairs.forEach((pair) => {
      const id = pair && pair[0];
      const text = pair && pair[1];
      const el = id ? document.getElementById(id) : null;
      if (!el || !text) return;
      const anchor = el.closest('label') || el;
      if (!anchor.parentNode) return;
      const hint = document.createElement('div');
      hint.className = 'debug-param';
      hint.style.cssText = 'display:block;font-size:11px;line-height:1.25;color:#333333;font-weight:400;font-style:italic;margin:1px 0 4px;';
      hint.textContent = text;
      if (anchor.nextSibling) anchor.parentNode.insertBefore(hint, anchor.nextSibling);
      else anchor.parentNode.appendChild(hint);
    });
  }
})(window);
