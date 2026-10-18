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
    return {
      widthM: widthM,
      lengthM: lengthM,
      spacingM: spacingM,
      hfovDeg: Number(hfovDeg),
      vfovDeg: Number(vfovDeg),
    };
  }

  // Same factor as the green S line: S = (1 − overlap) · W, so S / W.
  // coverageWindow "W" is the full camera window. Missing or "S" is the default.
  function laneScale(metrics) {
    if (metrics && metrics.coverageWindow === 'W') return 1;
    const widthM = Number(metrics && metrics.widthM);
    const spacingM = Number(metrics && metrics.spacingM);
    if (Number.isFinite(widthM) && widthM > 0 && Number.isFinite(spacingM) && spacingM >= 0) {
      return spacingM / widthM;
    }
    return 1;
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

  function nadirPitch(pitch) {
    if (pitch == null || pitch === '') return true;
    const n = Number(pitch);
    if (!Number.isFinite(n)) return true;
    return Math.round(n) === -90;
  }

  // Ground range along the look direction. τ = pitch+90 is the tilt from nadir.
  // A ray at or above the horizon (|angle from nadir| ≥ 89°) does not hit the ground.
  var COVERAGE_FADE_START_M = 150;
  var COVERAGE_FADE_END_M = 320;

  function coverageFade(rangeM) {
    const r = Math.abs(Number(rangeM));
    if (!Number.isFinite(r) || r <= COVERAGE_FADE_START_M) return 1;
    if (r >= COVERAGE_FADE_END_M) return 0;
    return (COVERAGE_FADE_END_M - r) / (COVERAGE_FADE_END_M - COVERAGE_FADE_START_M);
  }

  // Near/far ground distances of a tilted view, and the cross-look half-width.
  // Pitch −90 is not handled here. Pitch 0 still has a near edge (lower half of the VFOV).
  function obliqueSpan(heightM, pitchDeg, hfovDeg, vfovDeg) {
    const H = Number(heightM);
    const pitch = Number(pitchDeg);
    if (!(H > 1) || !Number.isFinite(pitch)) return null;
    const tau = pitch + 90;
    const av = Number(vfovDeg) / 2;
    const ah = Number(hfovDeg) / 2;
    if (!(av > 0) || !(ah > 0)) return null;
    const horizon = 89;
    const rangeAt = (angleDeg) => {
      if (!(angleDeg > -horizon && angleDeg < horizon)) return null;
      return H * Math.tan(angleDeg * Math.PI / 180);
    };
    let near = rangeAt(tau - av);
    let far = rangeAt(tau + av);
    if (near == null && far == null) return null;
    // A ray at or above the horizon has no ground edge. Do not invent one at the fade limit.
    if (far == null) return null;
    if (near == null) near = Math.min(0, far - 8);
    if (far < near) {
      const swap = near;
      near = far;
      far = swap;
    }
    if (!(far > near + 0.5)) return null;
    const halfAt = (rangeM) => {
      const slant = Math.hypot(H, rangeM);
      return Math.max(1, slant * Math.tan(ah * Math.PI / 180));
    };
    return { near: near, far: far, halfAt: halfAt };
  }

  // −90, missing, or null. One quad per leg, full length, centered on the route.
  // Adjacent legs share the corner edge when the heading does not change.
  // A turn adds the outer sector whose radii lie on those edges, so the band
  // does not break and the sector is not a detached disc. The ground window
  // is scaled by S/W, the same factor as the green S line. Across-track
  // half-width is that scaled HFOV ground half when the leg is flown along
  // Gierrichtung, and half the scaled near–far ground length when it is flown sideways.
  function buildNadirCoveragePolygons(pathCoords, aglList, metrics, fallbackAgl, lonLatPts, caps, bearingList) {
    if (!Array.isArray(pathCoords) || pathCoords.length < 2) return [];
    const capStart = !caps || caps.start !== false;
    const capEnd = !caps || caps.end !== false;
    const sized = pathCoords.map((_, i) => displayAtAgl(aglList ? aglList[i] : fallbackAgl, metrics, fallbackAgl));
    if (sized.some((piece) => !piece || !(piece.displayM > 0))) return [];
    const rings = [];
    function lookOf(index, tx, ty) {
      let bearing = Number(bearingList && bearingList[index]);
      if (!Number.isFinite(bearing)) bearing = nextLegBearingDeg(pathCoords, index);
      if (!Number.isFinite(bearing)) return { x: tx, y: ty };
      const rad = ((bearing % 360) + 360) % 360 * Math.PI / 180;
      return { x: Math.sin(rad), y: Math.cos(rad) };
    }
    function halfMap(idx, tx, ty, look) {
      const h = Number(aglList && aglList[idx]);
      const agl = Number.isFinite(h) && h > 1 ? h : Number(fallbackAgl);
      const ref = Number(fallbackAgl);
      const k = (Number.isFinite(ref) && ref > 0 && agl > 1) ? (agl / ref) : 1;
      const factor = laneScale(metrics);
      const widthM = Number(metrics && metrics.widthM) * k * factor;
      const lengthM = Number(metrics && metrics.lengthM) * k * factor;
      const hfovHalf = Number.isFinite(widthM) && widthM > 0 ? widthM * 0.5 : 0;
      const depthHalf = Number.isFinite(lengthM) && lengthM > 0 ? lengthM * 0.5 : hfovHalf;
      const piece = sized[idx];
      if (!(hfovHalf > 0)) {
        const fallbackHalf = Number(piece && piece.swathHalfM);
        const meters = Number.isFinite(fallbackHalf) && fallbackHalf > 0 ? fallbackHalf : (piece.displayM * 0.5);
        return meters * meterScaleAt(pathCoords[idx]);
      }
      const along = Math.abs(look.x * tx + look.y * ty);
      const across = Math.abs(look.x * (-ty) + look.y * tx);
      return Math.max(along * hfovHalf + across * depthHalf, 0.5) * meterScaleAt(pathCoords[idx]);
    }
    function alongMap(idx, tx, ty, look) {
      const h = Number(aglList && aglList[idx]);
      const agl = Number.isFinite(h) && h > 1 ? h : Number(fallbackAgl);
      const ref = Number(fallbackAgl);
      const k = (Number.isFinite(ref) && ref > 0 && agl > 1) ? (agl / ref) : 1;
      const factor = laneScale(metrics);
      const widthM = Number(metrics && metrics.widthM) * k * factor;
      const lengthM = Number(metrics && metrics.lengthM) * k * factor;
      const hfovHalf = Number.isFinite(widthM) && widthM > 0 ? widthM * 0.5 : 0;
      const depthHalf = Number.isFinite(lengthM) && lengthM > 0 ? lengthM * 0.5 : 0;
      const along = Math.abs(look.x * tx + look.y * ty);
      const across = Math.abs(look.x * (-ty) + look.y * tx);
      return Math.max(across * hfovHalf + along * depthHalf, 0) * meterScaleAt(pathCoords[idx]);
    }
    function edgeAt(center, tx, ty, half) {
      const nx = -ty;
      const ny = tx;
      return {
        center: center,
        left: [center[0] + nx * half, center[1] + ny * half],
        right: [center[0] - nx * half, center[1] - ny * half],
      };
    }
    function pushRing(ring) {
      if (!ring || ring.length < 4) return;
      ring.push(ring[0].slice());
      rings.push(ring);
    }
    function pushJoin(center, inEdge, outEdge, sweep) {
      if (!(Math.abs(sweep) > 0.02)) return;
      const outer0 = sweep > 0 ? inEdge.right : inEdge.left;
      const outer1 = sweep > 0 ? outEdge.right : outEdge.left;
      const r0 = Math.hypot(outer0[0] - center[0], outer0[1] - center[1]);
      const r1 = Math.hypot(outer1[0] - center[0], outer1[1] - center[1]);
      if (!(r0 > 0.2) || !(r1 > 0.2)) return;
      if (Math.hypot(outer0[0] - outer1[0], outer0[1] - outer1[1]) < 0.05) return;
      const a0 = Math.atan2(outer0[1] - center[1], outer0[0] - center[0]);
      const steps = Math.max(3, Math.ceil(Math.abs(sweep) / (Math.PI / 14)));
      const ring = [center.slice(), outer0.slice()];
      for (let s = 1; s < steps; s++) {
        const t = s / steps;
        const a = a0 + sweep * t;
        const r = r0 + (r1 - r0) * t;
        ring.push([center[0] + Math.cos(a) * r, center[1] + Math.sin(a) * r]);
      }
      ring.push(outer1.slice());
      pushRing(ring);
    }
    const segs = [];
    for (let i = 0; i < pathCoords.length - 1; i++) {
      const a = pathCoords[i];
      const b = pathCoords[i + 1];
      if (!a || !b) continue;
      const dx = b[0] - a[0];
      const dy = b[1] - a[1];
      const len = Math.hypot(dx, dy);
      if (!(len > 1e-3)) continue;
      const tx = dx / len;
      const ty = dy / len;
      const look = lookOf(i, tx, ty);
      const ha = halfMap(i, tx, ty, look);
      const hb = halfMap(i + 1, tx, ty, look);
      if (!(ha > 0) || !(hb > 0)) continue;
      segs.push({
        tx: tx,
        ty: ty,
        e0: edgeAt(a, tx, ty, ha),
        e1: edgeAt(b, tx, ty, hb),
        along0: alongMap(i, tx, ty, look),
        along1: alongMap(i + 1, tx, ty, look),
      });
    }
    segs.forEach((seg, i) => {
      const e0 = seg.e0;
      const e1 = seg.e1;
      pushRing([e0.left.slice(), e1.left.slice(), e1.right.slice(), e0.right.slice()]);
      const next = segs[i + 1];
      if (!next) return;
      const turn = seg.tx * next.ty - seg.ty * next.tx;
      const dot = seg.tx * next.tx + seg.ty * next.ty;
      pushJoin(e1.center, e1, next.e0, Math.atan2(turn, dot));
    });
    function pushCap(edge, tx, ty, sign, lengthMap) {
      if (!(lengthMap > 0.4)) return;
      const dx = tx * lengthMap * sign;
      const dy = ty * lengthMap * sign;
      pushRing([
        edge.left.slice(),
        [edge.left[0] + dx, edge.left[1] + dy],
        [edge.right[0] + dx, edge.right[1] + dy],
        edge.right.slice(),
      ]);
    }
    if (segs.length && capStart) pushCap(segs[0].e0, segs[0].tx, segs[0].ty, -1, segs[0].along0);
    if (segs.length && capEnd) {
      const last = segs[segs.length - 1];
      pushCap(last.e1, last.tx, last.ty, 1, last.along1);
    }
    return rings;
  }

  // Tilted gimbal only. The window stays in the camera axes (HFOV across the
  // look, VFOV along the look). A leg only slides that window along the
  // segment. −90 points are not drawn here. Fade 150–320 m applies here.
  function buildCoveragePolygons(pathCoords, aglList, metrics, fallbackAgl, lonLatPts, pitchList, bearingList, headingModeList) {
    if (!Array.isArray(pathCoords) || pathCoords.length < 2) return [];
    let anyTilt = false;
    for (let i = 0; i < pathCoords.length; i++) {
      if (!nadirPitch(pitchList && pitchList[i])) { anyTilt = true; break; }
    }
    if (!anyTilt) return buildNadirCoveragePolygons(pathCoords, aglList, metrics, fallbackAgl, lonLatPts, null, bearingList);
    const nadirRings = [];
    let nadirRun = null;
    for (let i = 0; i < pathCoords.length - 1; i++) {
      if (nadirPitch(pitchList && pitchList[i])) {
        if (!nadirRun) {
          nadirRun = [i];
          nadirRings.push(nadirRun);
        }
        nadirRun.push(i + 1);
      } else {
        nadirRun = null;
      }
    }
    const lastIdx = pathCoords.length - 1;
    const ringsOut = [];
    nadirRings.forEach((points) => {
      if (points.length < 2) return;
      buildNadirCoveragePolygons(
        points.map((i) => pathCoords[i]),
        points.map((i) => (aglList ? aglList[i] : fallbackAgl)),
        metrics,
        fallbackAgl,
        points.map((i) => (lonLatPts ? lonLatPts[i] : null)),
        { start: points[0] === 0, end: points[points.length - 1] === lastIdx },
        points.map((i) => (bearingList ? bearingList[i] : null))
      ).forEach((ring) => ringsOut.push(ring));
    });
    const hfovDegEarly = Number(metrics && metrics.hfovDeg);
    const vfovDegEarly = Number(metrics && metrics.vfovDeg);
    const hfovUse = Number.isFinite(hfovDegEarly) && hfovDegEarly > 1 ? hfovDegEarly : 49;
    const vfovUse = Number.isFinite(vfovDegEarly) && vfovDegEarly > 1 ? vfovDegEarly : 40;
    const rings = [];
    function lookOf(index) {
      let bearing = Number(bearingList && bearingList[index]);
      if (!Number.isFinite(bearing)) bearing = nextLegBearingDeg(pathCoords, index);
      if (!Number.isFinite(bearing)) bearing = nextLegBearingDeg(pathCoords, Math.max(0, index - 1));
      if (!Number.isFinite(bearing)) bearing = 0;
      const rad = ((bearing % 360) + 360) % 360 * Math.PI / 180;
      return { x: Math.sin(rad), y: Math.cos(rad) };
    }
    function followsNextWaypoint(mode) {
      const m = String(mode || '').toLowerCase();
      if (!m) return false;
      if (/definiert|custom|manuell|manual|schenk|schwenk|poi|fokus|fixed|lock|sperr/.test(m)) return false;
      return /follow|richtung|wegpunkt|wayline/.test(m);
    }
    function aglOf(index) {
      const h = Number(aglList && aglList[index]);
      return Number.isFinite(h) && h > 1 ? h : Number(fallbackAgl);
    }
    function pitchOf(index) {
      const pitch = Number(pitchList && pitchList[index]);
      return Number.isFinite(pitch) ? pitch : -90;
    }
    // HFOV is across the look, VFOV is along the look. The corners are not
    // rotated onto the leg.
    function windowCorners(xy, agl, pitch, look) {
      if (!xy || !look || nadirPitch(pitch)) return null;
      const span = obliqueSpan(agl, pitch, hfovUse, vfovUse);
      if (!span) return null;
      const factor = laneScale(metrics);
      const scaleRaw = meterScaleAt(xy);
      const scale = scaleRaw > 0 ? scaleRaw : 1;
      const near = span.near;
      const far = Math.min(span.far, COVERAGE_FADE_END_M);
      if (!(far > near + 0.4) || near >= COVERAGE_FADE_END_M) return null;
      const fade = coverageFade((near + far) / 2);
      if (!(fade > 0.04)) return null;
      const px = -look.y;
      const py = look.x;
      const corner = (rangeM, sign) => {
        const half = span.halfAt(rangeM) * factor * scale * sign;
        const along = rangeM * factor * scale;
        return [xy[0] + look.x * along + px * half, xy[1] + look.y * along + py * half];
      };
      return {
        fade: fade,
        corners: [corner(near, 1), corner(far, 1), corner(far, -1), corner(near, -1)],
      };
    }
    function hull(points) {
      const pts = points.filter((p) => p && Number.isFinite(p[0]) && Number.isFinite(p[1]));
      if (pts.length < 3) return null;
      pts.sort((a, b) => (a[0] - b[0]) || (a[1] - b[1]));
      const turn = (o, a, b) => (a[0] - o[0]) * (b[1] - o[1]) - (a[1] - o[1]) * (b[0] - o[0]);
      const lower = [];
      const upper = [];
      pts.forEach((p) => {
        while (lower.length >= 2 && turn(lower[lower.length - 2], lower[lower.length - 1], p) <= 0) lower.pop();
        lower.push(p);
      });
      for (let i = pts.length - 1; i >= 0; i--) {
        const p = pts[i];
        while (upper.length >= 2 && turn(upper[upper.length - 2], upper[upper.length - 1], p) <= 0) upper.pop();
        upper.push(p);
      }
      lower.pop();
      upper.pop();
      const ring = lower.concat(upper);
      return ring.length >= 3 ? ring : null;
    }
    function fillOf(fade) {
      return 'rgba(64,196,255,' + (0.46 * fade).toFixed(3) + ')';
    }
    function pushRing(ring, fade) {
      if (!ring || ring.length < 3) return;
      ring.push(ring[0].slice());
      rings.push({ ring: ring, fill: fillOf(fade) });
    }
    for (let i = 0; i < pathCoords.length - 1; i++) {
      const a = pathCoords[i];
      const b = pathCoords[i + 1];
      if (!a || !b || nadirPitch(pitchList && pitchList[i])) continue;
      const dx = b[0] - a[0];
      const dy = b[1] - a[1];
      const len = Math.hypot(dx, dy);
      if (!(len > 1e-3)) continue;
      const tx = dx / len;
      const ty = dy / len;
      const follow = followsNextWaypoint(headingModeList && headingModeList[i]);
      const look0 = follow ? { x: tx, y: ty } : lookOf(i);
      const endFollow = followsNextWaypoint(headingModeList && headingModeList[i + 1]);
      const look1 = follow ? { x: tx, y: ty } : (endFollow ? { x: tx, y: ty } : lookOf(i + 1));
      const h0 = aglOf(i);
      const h1 = nadirPitch(pitchList && pitchList[i + 1]) ? h0 : aglOf(i + 1);
      const p0 = pitchOf(i);
      const p1 = nadirPitch(pitchList && pitchList[i + 1]) ? p0 : pitchOf(i + 1);
      const yaw0 = Math.atan2(look0.x, look0.y);
      const yaw1 = Math.atan2(look1.x, look1.y);
      let dYaw = yaw1 - yaw0;
      while (dYaw > Math.PI) dYaw -= Math.PI * 2;
      while (dYaw < -Math.PI) dYaw += Math.PI * 2;
      const steps = (!follow && (Math.abs(dYaw) > 0.14 || Math.abs(h1 - h0) > 2 || Math.abs(p1 - p0) > 1)) ? 4 : 1;
      const pts = [];
      let fade = 1;
      for (let s = 0; s <= steps; s++) {
        const t = s / steps;
        const xy = [a[0] + dx * t, a[1] + dy * t];
        const yaw = yaw0 + dYaw * t;
        const look = { x: Math.sin(yaw), y: Math.cos(yaw) };
        const pose = windowCorners(xy, h0 + (h1 - h0) * t, p0 + (p1 - p0) * t, look);
        if (!pose) continue;
        fade = Math.min(fade, pose.fade);
        pose.corners.forEach((c) => pts.push(c));
      }
      const ring = hull(pts);
      if (ring) pushRing(ring, fade);
    }
    rings.forEach((ring) => ringsOut.push(ring));
    return ringsOut;
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
  function coverageSampleIndices(pathCoords, aglList, pitchList, bearingList) {
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
      if (!nadirPitch(pitchList && pitchList[i])) {
        keep[i] = true;
        continue;
      }
      if (headingChange(prev, curr, next) >= turnMin) {
        keep[i] = true;
        continue;
      }
      const bear = Number(bearingList && bearingList[i]);
      const bearPrev = Number(bearingList && bearingList[i - 1]);
      if (Number.isFinite(bear) && Number.isFinite(bearPrev)) {
        let delta = Math.abs(bear - bearPrev) % 360;
        if (delta > 180) delta = 360 - delta;
        if (delta > 18) {
          keep[i] = true;
          continue;
        }
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

  function buildRouteCoveragePolygons(pathCoords, aglList, metrics, fallbackAgl, lonLatPts, routeCalculated, pitchList, bearingList, headingModeList) {
    if (!routeCalculated) return [];
    if (!Array.isArray(pathCoords) || pathCoords.length < 2) return [];
    let tilted = false;
    if (Array.isArray(pitchList)) {
      for (let i = 0; i < pathCoords.length; i++) {
        if (!nadirPitch(pitchList[i])) { tilted = true; break; }
      }
    }
    const idx = coverageSampleIndices(pathCoords, aglList, tilted ? pitchList : null, tilted ? bearingList : null);
    const coords = idx.map((i) => pathCoords[i]);
    const agl = idx.map((i) => aglList ? aglList[i] : fallbackAgl);
    const lonLat = idx.map((i) => lonLatPts ? lonLatPts[i] : null);
    const pitch = idx.map((i) => pitchList ? pitchList[i] : null);
    const bearing = idx.map((i) => bearingList ? bearingList[i] : null);
    const heading = idx.map((i) => headingModeList ? headingModeList[i] : null);
    return buildCoveragePolygons(coords, agl, metrics, fallbackAgl, lonLat, pitch, bearing, heading);
  }

  // Coordinated turn that skips the waypoint: leave the incoming leg one
  // turn-distance before the point and meet the next leg the same distance after.
  function coordinateTurnCoords(prevXY, currXY, nextXY, distanceM) {
    const D = Number(distanceM);
    if (!prevXY || !currXY || !nextXY || !(D > 0.05)) return null;
    const scale = meterScaleAt(currXY);
    const mapD = D * (scale > 0 ? scale : 1);
    const inX = Number(currXY[0]) - Number(prevXY[0]);
    const inY = Number(currXY[1]) - Number(prevXY[1]);
    const outX = Number(nextXY[0]) - Number(currXY[0]);
    const outY = Number(nextXY[1]) - Number(currXY[1]);
    const inLen = Math.hypot(inX, inY);
    const outLen = Math.hypot(outX, outY);
    if (!(inLen > mapD + 1e-3) || !(outLen > mapD + 1e-3)) return null;
    const u1x = inX / inLen;
    const u1y = inY / inLen;
    const u2x = outX / outLen;
    const u2y = outY / outLen;
    const dot = Math.max(-1, Math.min(1, u1x * u2x + u1y * u2y));
    const phi = Math.acos(dot);
    if (!(phi > 0.05) || !(Math.PI - phi > 0.02)) return null;
    const pin = [Number(currXY[0]) - u1x * mapD, Number(currXY[1]) - u1y * mapD];
    const pout = [Number(currXY[0]) + u2x * mapD, Number(currXY[1]) + u2y * mapD];
    const cross = u1x * u2y - u1y * u2x;
    const left = cross >= 0;
    const R = mapD / Math.tan(phi / 2);
    if (!(R > 0)) return null;
    const px = -u1y;
    const py = u1x;
    const sign = left ? 1 : -1;
    const center = [pin[0] + sign * R * px, pin[1] + sign * R * py];
    const a0 = Math.atan2(pin[1] - center[1], pin[0] - center[0]);
    let sweep = Math.atan2(pout[1] - center[1], pout[0] - center[0]) - a0;
    if (left) {
      while (sweep <= 0) sweep += Math.PI * 2;
      if (sweep > Math.PI + 0.2) sweep -= Math.PI * 2;
    } else {
      while (sweep >= 0) sweep -= Math.PI * 2;
      if (sweep < -Math.PI - 0.2) sweep += Math.PI * 2;
    }
    const steps = Math.max(8, Math.ceil(Math.abs(sweep) / (Math.PI / 18)));
    const line = [];
    for (let s = 0; s <= steps; s++) {
      const a = a0 + sweep * (s / steps);
      line.push([center[0] + Math.cos(a) * R, center[1] + Math.sin(a) * R]);
    }
    line[0] = pin.slice();
    line[line.length - 1] = pout.slice();
    return line;
  }

  let mapLabelScaleValue = 1;
  function mapLabelScale() {
    return mapLabelScaleValue;
  }
  function setMapLabelScale(value) {
    const n = Number(value);
    mapLabelScaleValue = Number.isFinite(n) ? Math.max(0.7, Math.min(2.2, Math.round(n * 10) / 10)) : 1;
    return mapLabelScaleValue;
  }
  function mapLabelFont(px, bold) {
    const size = Math.max(8, Math.round(Number(px) * mapLabelScaleValue));
    return (bold ? 'bold ' : '') + size + 'px Arial, Helvetica, sans-serif';
  }

  function saveConfirmText(data) {
    const d = data && typeof data === 'object' ? data : {};
    const base = (path) => {
      const text = String(path || '').replace(/\\/g, '/').trim();
      const cut = text.lastIndexOf('/');
      return cut >= 0 ? text.slice(cut + 1) : text;
    };
    const jsonName = base(d.jason_file);
    const kmzName = base(d.kmz_file);
    const jsonOk = d.json_written === true && jsonName !== '';
    const kmzOk = d.kmz_written === true && kmzName !== '';
    const parts = [];
    parts.push(jsonOk
      ? ('JSON ' + jsonName + ' geschrieben und angepasst')
      : ('JSON nicht geschrieben' + (jsonName ? (': ' + jsonName) : '')));
    parts.push(kmzOk
      ? ('KMZ ' + kmzName + ' geschrieben und angepasst')
      : ('KMZ nicht geschrieben' + (kmzName ? (': ' + kmzName) : '')));
    if (d.aenderung) parts.push('Änderungsdatum ' + d.aenderung);
    const hist = [];
    if (d.history_json === true) hist.push('JSON');
    if (d.history_kmz === true) hist.push('KMZ');
    if (hist.length) parts.push('Vorversion ' + hist.join(' und ') + ' in der History');
    else if (d.history_json === false || d.history_kmz === false) parts.push('History-Kopie fehlgeschlagen');
    if (d.log_written === true) parts.push('Logeintrag geschrieben');
    else if (d.log_written === false) parts.push('Logeintrag nicht geschrieben');
    return { ok: jsonOk && kmzOk, text: parts.join('. ') + '.' };
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
    if (!Number.isFinite(terrain) || !Number.isFinite(start)) return null;
    const rel = programmed + (terrain - start);
    return Number.isFinite(rel) ? rel : null;
  }

  function specificGimbalLabel(pitch) {
    const n = Number(pitch);
    if (!Number.isFinite(n)) return '';
    const rounded = Math.round(n);
    if (rounded === -90) return '';
    return String(rounded) + '°';
  }

  function gimbalModeFromUav(control) {
    const mode = String(control || '').toLowerCase();
    if (!mode || mode === 'none' || mode === 'null') return '';
    if (/seteach|usepoint|wegpunkt/.test(mode)) return 'Jeden Wegpunkt festlegen';
    if (/manuel|manual/.test(mode)) return 'Manuell';
    return '';
  }

  function heightCaption(programmedM, terrainM, startTerrainM) {
    const programmed = Number(programmedM);
    if (!Number.isFinite(programmed)) return { main: '', paren: '' };
    const rel = relativeHeightFromStartM(programmed, terrainM, startTerrainM);
    const tenth = Number.isFinite(rel) ? (Math.round(rel * 10) / 10) : NaN;
    return {
      main: formatMeterLabel(programmed),
      paren: Number.isFinite(tenth) ? ('(' + tenth.toFixed(1) + ' m)') : '',
    };
  }

  function waypointMissesMission(row) {
    const keys = ['use_mission_height', 'use_mission_speed', 'use_mission_waypoint_type', 'use_mission_yaw', 'use_mission_path_mode', 'use_mission_direction_mode'];
    if (!row || typeof row !== 'object') return false;
    if (specificGimbalLabel(row.gimbal_pitch) !== '') return true;
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
      if (Number.isFinite(Number(props.gimbalAngle))) row.gimbal_pitch = Math.round(Number(props.gimbalAngle));
      const coord = geom.coordinates;
      if (Array.isArray(coord) && Number.isFinite(Number(coord[0])) && Number.isFinite(Number(coord[1]))) {
        row.lon = Number(coord[0]);
        row.lat = Number(coord[1]);
      }
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
    const gimbalMode = gimbalModeFromUav(pilot && pilot.gimbalControl);
    return { overrides: overrides, anyFalse: anyFalse, easyModeForm: easyModeForm, count: n, pois: pois, gimbalMode: gimbalMode };
  }

  function alignOverridesToPath(overrides, triplets) {
    const path = Array.isArray(triplets) ? triplets : [];
    const rows = Object.keys(overrides || {}).map((key) => overrides[key]).filter((row) => (
      row && Number.isFinite(Number(row.lon)) && Number.isFinite(Number(row.lat))
    ));
    if (path.length < 1 || !rows.length) return overrides || {};
    const next = {};
    path.forEach((point, index) => {
      const lon = Number(point && point[0]);
      const lat = Number(point && point[1]);
      if (!Number.isFinite(lon) || !Number.isFinite(lat)) return;
      let best = null;
      let bestD = Infinity;
      rows.forEach((row) => {
        const dlon = Number(row.lon) - lon;
        const dlat = Number(row.lat) - lat;
        const d = dlon * dlon + dlat * dlat;
        if (d < bestD) {
          bestD = d;
          best = row;
        }
      });
      if (!best || bestD > 2.5e-8) return;
      const copy = Object.assign({}, best);
      delete copy.lon;
      delete copy.lat;
      next[String(index + 1)] = copy;
    });
    return Object.keys(next).length ? next : (overrides || {});
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
      const coordMatch = block.match(/<coordinates>\s*([-0-9.]+)\s*,\s*([-0-9.]+)/i);
      if (coordMatch) {
        row.lon = Number(coordMatch[1]);
        row.lat = Number(coordMatch[2]);
      }
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
      const angleEnable = wpmlTag(block, 'waypointHeadingAngleEnable');
      const poiText = wpmlTag(block, 'waypointPoiPoint');
      const storeHeading = (mode) => {
        if (mode) row.heading_mode = mode;
        const defined = mode === 'Definiert';
        const enabled = angleEnable == null || flagIsOff(angleEnable) === false;
        if (defined && enabled && yawAngle != null && Number.isFinite(yawAngle)) {
          row.heading_angle = yawAngle;
          row.direction_deg = yawAngle;
        }
      };
      if (flagIsOff(yawFlag)) {
        row.use_mission_yaw = false;
        parsed.anyFalse = true;
        storeHeading(yawMode);
      } else if (yawFlag == null && yawMode && yawMode !== globalHeading) {
        row.use_mission_yaw = false;
        parsed.anyFalse = true;
        storeHeading(yawMode);
      }
      if (yawMode === 'Fokus POI' && poiText) {
        const parts = poiText.split(',').map((part) => Number(String(part).trim()));
        const nonzero = parts.length >= 2 && (Math.abs(parts[0]) > 1e-7 || Math.abs(parts[1]) > 1e-7);
        if (nonzero && Number.isFinite(parts[0]) && Number.isFinite(parts[1])) {
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
      const pitchText = wpmlTag(block, 'gimbalPitchAngle');
      if (pitchText != null && String(pitchText).trim() !== '') {
        const pitch = Math.round(Number(pitchText));
        if (Number.isFinite(pitch) && Math.abs(pitch - (-90)) >= 0.5) {
          row.gimbal_pitch = Math.max(-90, Math.min(30, pitch));
          parsed.gimbalMode = 'Jeden Wegpunkt festlegen';
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
      if (yawFlag == null && row.heading_mode === 'Fokus POI' && row.poi && row.poi.longitude != null) {
        row.use_mission_yaw = false;
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
    coordinateTurnCoords: coordinateTurnCoords,
    mapLabelScale: mapLabelScale,
    setMapLabelScale: setMapLabelScale,
    mapLabelFont: mapLabelFont,
    saveConfirmText: saveConfirmText,
    nextLegBearingDeg: nextLegBearingDeg,
    formatMeterLabel: formatMeterLabel,
    programmedFlightHeightM: programmedFlightHeightM,
    heightAboveGroundM: heightAboveGroundM,
    relativeHeightFromStartM: relativeHeightFromStartM,
    heightCaption: heightCaption,
    specificGimbalLabel: specificGimbalLabel,
    gimbalModeFromUav: gimbalModeFromUav,
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
    applyKmzDeviations: applyKmzDeviations,
    alignOverridesToPath: alignOverridesToPath,
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
