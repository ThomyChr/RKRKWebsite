# -*- coding: utf-8 -*-
"""
Waypoint Planner (nur Python Standard Library).
"""

from __future__ import annotations

import hashlib
import json
import math
import time
from dataclasses import dataclass
from typing import Dict, List, Optional, Tuple
from urllib import parse, request


def _deci2sexa_seconds(angle_deg: float) -> float:
    deg = int(angle_deg)
    minutes_full = (angle_deg - deg) * 60.0
    minute = int(minutes_full)
    sec = (minutes_full - minute) * 60.0
    return deg * 3600.0 + minute * 60.0 + sec


def wgs84_to_lv95(lat_deg: float, lon_deg: float) -> Tuple[float, float]:
    lat = _deci2sexa_seconds(lat_deg)
    lon = _deci2sexa_seconds(lon_deg)
    lat_aux = (lat - 169028.66) / 10000.0
    lon_aux = (lon - 26782.5) / 10000.0

    n = (
        200147.07
        + 308807.95 * lat_aux
        + 3745.25 * (lon_aux**2)
        + 76.63 * (lat_aux**2)
        - 194.56 * (lon_aux**2) * lat_aux
        + 119.79 * (lat_aux**3)
    )
    e = (
        600072.37
        + 211455.93 * lon_aux
        - 10938.51 * lon_aux * lat_aux
        - 0.36 * lon_aux * (lat_aux**2)
        - 44.54 * (lon_aux**3)
    )
    return e + 2000000.0, n + 1000000.0


def lv95_to_wgs84(e: float, n: float) -> Tuple[float, float]:
    y_aux = (e - 2600000.0) / 1000000.0
    x_aux = (n - 1200000.0) / 1000000.0
    lat = (
        16.9023892
        + 3.238272 * x_aux
        - 0.270978 * (y_aux**2)
        - 0.002528 * (x_aux**2)
        - 0.0447 * (y_aux**2) * x_aux
        - 0.0140 * (x_aux**3)
    )
    lon = (
        2.6779094
        + 4.728982 * y_aux
        + 0.791484 * y_aux * x_aux
        + 0.1306 * y_aux * (x_aux**2)
        - 0.0436 * (y_aux**3)
    )
    return lat * 100.0 / 36.0, lon * 100.0 / 36.0


@dataclass
class GeoAdminClient:
    base: str = "https://api3.geo.admin.ch/rest/services"
    sr: int = 2056
    timeout_s: int = 30
    min_delay_s: float = 0.05
    _last_call: float = 0.0
    _cache_profile: Optional[Dict[str, List[Dict[str, float]]]] = None

    def __post_init__(self) -> None:
        if self._cache_profile is None:
            self._cache_profile = {}

    def _throttle(self) -> None:
        dt = time.time() - self._last_call
        if dt < self.min_delay_s:
            time.sleep(self.min_delay_s - dt)
        self._last_call = time.time()

    def _http_post_form(self, url: str, data: Dict[str, str]) -> str:
        self._throttle()
        body = parse.urlencode(data).encode("utf-8")
        req = request.Request(url, data=body, method="POST")
        req.add_header("Content-Type", "application/x-www-form-urlencoded")
        with request.urlopen(req, timeout=self.timeout_s) as resp:
            return resp.read().decode("utf-8")

    def get_profile(self, coords: List[Tuple[float, float]], nb_points: int) -> List[Dict[str, float]]:
        geom = {"type": "LineString", "coordinates": [[float(e), float(n)] for e, n in coords]}
        seed = json.dumps({"geom": geom, "sr": self.sr, "nb_points": nb_points}, sort_keys=True).encode("utf-8")
        key = hashlib.sha256(seed).hexdigest()
        if key in self._cache_profile:
            return self._cache_profile[key]

        txt = self._http_post_form(
            f"{self.base}/profile.json",
            {"geom": json.dumps(geom), "sr": str(self.sr), "nb_points": str(int(nb_points))},
        )
        raw = json.loads(txt)
        pts: List[Dict[str, float]] = []
        for p in raw:
            alts = p.get("alts", {})
            z = None
            if isinstance(alts, dict) and alts:
                z = alts.get("DTM2", alts.get("COMB", None))
                if z is None:
                    z = list(alts.values())[0]
            else:
                z = p.get("alt", None)
            pts.append(
                {
                    "dist": float(p.get("dist", 0.0)),
                    "E": float(p.get("easting", p.get("east", p.get("x", 0.0)))),
                    "N": float(p.get("northing", p.get("north", p.get("y", 0.0)))),
                    "z": float(z) if z is not None else float("nan"),
                }
            )
        self._cache_profile[key] = pts
        return pts


def polygon_centroid(poly: List[Tuple[float, float]]) -> Tuple[float, float]:
    a = 0.0
    cx = 0.0
    cy = 0.0
    n = len(poly)
    for i in range(n):
        x0, y0 = poly[i]
        x1, y1 = poly[(i + 1) % n]
        cross = x0 * y1 - x1 * y0
        a += cross
        cx += (x0 + x1) * cross
        cy += (y0 + y1) * cross
    a *= 0.5
    if abs(a) < 1e-9:
        return (sum(p[0] for p in poly) / n, sum(p[1] for p in poly) / n)
    return (cx / (6.0 * a), cy / (6.0 * a))


def rotate_points(points: List[Tuple[float, float]], angle_deg: float, origin: Tuple[float, float]) -> List[Tuple[float, float]]:
    ox, oy = origin
    a = math.radians(angle_deg)
    ca, sa = math.cos(a), math.sin(a)
    out: List[Tuple[float, float]] = []
    for x, y in points:
        tx, ty = x - ox, y - oy
        out.append((tx * ca - ty * sa + ox, tx * sa + ty * ca + oy))
    return out


def segment_length(a: Tuple[float, float], b: Tuple[float, float]) -> float:
    return math.hypot(b[0] - a[0], b[1] - a[1])


def bbox(points: List[Tuple[float, float]]) -> Tuple[float, float, float, float]:
    return (
        min(p[0] for p in points),
        min(p[1] for p in points),
        max(p[0] for p in points),
        max(p[1] for p in points),
    )


def line_intersections_with_polygon_horizontal(poly: List[Tuple[float, float]], y: float) -> List[float]:
    xs: List[float] = []
    n = len(poly)
    for i in range(n):
        x0, y0 = poly[i]
        x1, y1 = poly[(i + 1) % n]
        if abs(y1 - y0) < 1e-12:
            continue
        if (y0 <= y < y1) or (y1 <= y < y0):
            t = (y - y0) / (y1 - y0)
            xs.append(x0 + t * (x1 - x0))
    xs.sort()
    return xs


def horizontal_segments_inside_polygon(
    poly: List[Tuple[float, float]], y: float
) -> List[Tuple[Tuple[float, float], Tuple[float, float]]]:
    xs = line_intersections_with_polygon_horizontal(poly, y)
    segs: List[Tuple[Tuple[float, float], Tuple[float, float]]] = []
    for i in range(0, len(xs) - 1, 2):
        a = (xs[i], y)
        b = (xs[i + 1], y)
        if segment_length(a, b) > 0.5:
            segs.append((a, b))
    return segs


def inset_segments(
    segs: List[Tuple[Tuple[float, float], Tuple[float, float]]], inset: float
) -> List[Tuple[Tuple[float, float], Tuple[float, float]]]:
    if inset <= 0:
        return segs
    out: List[Tuple[Tuple[float, float], Tuple[float, float]]] = []
    for a, b in segs:
        length = segment_length(a, b)
        if length <= 2 * inset:
            continue
        ux = (b[0] - a[0]) / length
        uy = (b[1] - a[1]) / length
        na = (a[0] + ux * inset, a[1] + uy * inset)
        nb = (b[0] - ux * inset, b[1] - uy * inset)
        out.append((na, nb))
    return out


def build_track_rows(min_y: float, max_y: float, spacing: float, max_edge_dist: float) -> List[float]:
    span = max(0.0, max_y - min_y)
    if spacing <= 1e-9 or span <= 1e-9:
        return [(min_y + max_y) / 2.0]

    max_edge_dist = max(0.0, max_edge_dist)
    min_lanes = max(1, int(math.ceil((span - 2.0 * max_edge_dist) / spacing)) + 1)
    max_lanes = max(1, int(math.floor(span / spacing)) + 1)
    lanes = min(max(min_lanes, 1), max_lanes)
    covered = (lanes - 1) * spacing
    margin = max(0.0, (span - covered) / 2.0)
    return [min_y + margin + i * spacing for i in range(lanes)]


def max_linear_error(profile: List[Dict[str, float]], i0: int, i1: int) -> Tuple[float, int]:
    d0, d1 = profile[i0]["dist"], profile[i1]["dist"]
    z0, z1 = profile[i0]["z"], profile[i1]["z"]
    if d1 <= d0:
        return 0.0, -1
    max_err = 0.0
    idx = -1
    for k in range(i0 + 1, i1):
        dk = profile[k]["dist"]
        zk = profile[k]["z"]
        if math.isnan(zk) or math.isnan(z0) or math.isnan(z1):
            continue
        t = (dk - d0) / (d1 - d0)
        zlin = z0 + t * (z1 - z0)
        err = abs(zlin - zk)
        if err > max_err:
            max_err = err
            idx = k
    return max_err, idx


def support_points(profile: List[Dict[str, float]], tol: float) -> List[int]:
    if not profile:
        return []
    if len(profile) == 1:
        return [0]
    keep = set()

    def rec(i0: int, i1: int) -> None:
        keep.add(i0)
        keep.add(i1)
        err, idx = max_linear_error(profile, i0, i1)
        if idx != -1 and err > tol:
            rec(i0, idx)
            rec(idx, i1)

    rec(0, len(profile) - 1)
    return sorted(keep)


def height_roughness(profile: List[Dict[str, float]]) -> float:
    s = 0.0
    for i in range(1, len(profile)):
        z0 = profile[i - 1]["z"]
        z1 = profile[i]["z"]
        if not (math.isnan(z0) or math.isnan(z1)):
            s += abs(z1 - z0)
    return s


@dataclass
class PlannerParams:
    HFOV_deg: float
    h_agl_m: float
    overlap: float = 0.15
    edge_factor: float = 0.40
    agl_tol_m: float = 10.0
    angle_step_deg: int = 5
    target_step_m: float = 2.0
    max_nb_points: int = 5000
    fixed_theta_deg: Optional[float] = None
    alpha: float = 1.0
    beta: float = 30.0
    gamma: float = 0.5
    delta: float = 1.0


@dataclass
class MissionResult:
    theta_deg: float
    waypoints: List[Dict[str, float]]
    metrics: Dict[str, float]


def swath_width(params: PlannerParams) -> float:
    return 2.0 * params.h_agl_m * math.tan(math.radians(params.HFOV_deg / 2.0))


def track_spacing(params: PlannerParams) -> float:
    return (1.0 - params.overlap) * swath_width(params)


def plan_waypoints_for_field(
    field_latlon: List[Tuple[float, float]],
    start_latlon: Tuple[float, float],
    params: PlannerParams,
    api: GeoAdminClient,
) -> MissionResult:
    poly_lv95 = [wgs84_to_lv95(lat, lon) for (lat, lon) in field_latlon]
    start_lv95 = wgs84_to_lv95(start_latlon[0], start_latlon[1])
    if poly_lv95[0] != poly_lv95[-1]:
        poly_lv95.append(poly_lv95[0])
    centroid = polygon_centroid(poly_lv95)

    s = track_spacing(params)
    edge_margin = params.edge_factor * s

    best_j = float("inf")
    best_theta: Optional[float] = None
    best_wps: List[Dict[str, float]] = []
    best_metrics: Dict[str, float] = {}

    if params.fixed_theta_deg is not None:
        theta_values = [int(round(params.fixed_theta_deg)) % 180]
    else:
        theta_values = list(range(0, 180, params.angle_step_deg))

    for theta in theta_values:
        poly_r = rotate_points(poly_lv95, -theta, centroid)
        start_r = rotate_points([start_lv95], -theta, centroid)[0]
        _, miny, _, maxy = bbox(poly_r)
        ys = build_track_rows(miny, maxy, s, edge_margin)

        endpoint_inset = max(0.0, min(edge_margin, s * 0.5))
        segments_r: List[Tuple[Tuple[float, float], Tuple[float, float]]] = []
        for y in ys:
            base = horizontal_segments_inside_polygon(poly_r, y)
            segments_r.extend(inset_segments(base, endpoint_inset))
        if not segments_r:
            continue

        segments_r.sort(key=lambda seg: seg[0][1])
        ordered_r: List[Tuple[Tuple[float, float], Tuple[float, float]]] = []
        current = start_r
        for i, (a, b) in enumerate(segments_r):
            if i % 2 == 1:
                a, b = b, a
            da = (current[0] - a[0]) ** 2 + (current[1] - a[1]) ** 2
            db = (current[0] - b[0]) ** 2 + (current[1] - b[1]) ** 2
            if db < da:
                a, b = b, a
            ordered_r.append((a, b))
            current = b

        waypoints_theta: List[Dict[str, float]] = []
        l_total = 0.0
        h_var = 0.0
        n_wp = 0

        for a_r, b_r in ordered_r:
            l = segment_length(a_r, b_r)
            l_total += l
            a_lv95 = rotate_points([a_r], theta, centroid)[0]
            b_lv95 = rotate_points([b_r], theta, centroid)[0]
            nb = min(params.max_nb_points, max(2, int(math.ceil(l / params.target_step_m))))
            profile = api.get_profile([a_lv95, b_lv95], nb_points=nb)
            if len(profile) < 2:
                continue
            h_var += height_roughness(profile)
            idx = support_points(profile, params.agl_tol_m)
            n_wp += len(idx)
            for k in idx:
                lat, lon = lv95_to_wgs84(profile[k]["E"], profile[k]["N"])
                waypoints_theta.append({"lat": lat, "lon": lon, "alt_abs_m": profile[k]["z"] + params.h_agl_m})

        if not waypoints_theta:
            continue
        first_lv95 = wgs84_to_lv95(waypoints_theta[0]["lat"], waypoints_theta[0]["lon"])
        deadhead = math.hypot(first_lv95[0] - start_lv95[0], first_lv95[1] - start_lv95[1])
        j = params.alpha * l_total + params.beta * n_wp + params.gamma * h_var + params.delta * deadhead
        if j < best_j:
            best_j = j
            best_theta = float(theta)
            best_wps = waypoints_theta
            best_metrics = {
                "J": best_j,
                "theta_deg": best_theta,
                "track_spacing_m": s,
                "edge_margin_m": edge_margin,
                "total_length_m": l_total,
                "deadhead_m": deadhead,
                "n_waypoints": float(n_wp),
                "height_roughness": h_var,
            }

    if best_theta is None:
        raise RuntimeError("Keine gültige Mission erzeugt.")
    return MissionResult(theta_deg=best_theta, waypoints=best_wps, metrics=best_metrics)


def _selftest() -> None:
    lat, lon = 47.3769, 8.5417
    e, n = wgs84_to_lv95(lat, lon)
    lat2, lon2 = lv95_to_wgs84(e, n)
    assert abs(lat - lat2) < 0.0004, "lat roundtrip too large"
    assert abs(lon - lon2) < 0.0004, "lon roundtrip too large"
    poly = [(0.0, 0.0), (10.0, 0.0), (10.0, 8.0), (0.0, 8.0), (0.0, 0.0)]
    segs = horizontal_segments_inside_polygon(poly, 4.0)
    assert len(segs) == 1 and abs(segs[0][0][0] - 0.0) < 1e-9 and abs(segs[0][1][0] - 10.0) < 1e-9
    print("selftest ok")


if __name__ == "__main__":
    _selftest()
    print("Planer-Modul ist syntaktisch und mit Basistests geprüft.")

