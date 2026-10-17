import argparse
import json
import traceback

from waypoint_planner_stdlib import GeoAdminClient, PlannerParams, plan_waypoints_for_field, swath_width, track_spacing


def main() -> int:
    parser = argparse.ArgumentParser(description="Run stdlib waypoint planner from JSON input")
    parser.add_argument("--input", required=True, help="Input JSON file path")
    parser.add_argument("--output", required=True, help="Output JSON file path")
    args = parser.parse_args()

    try:
        with open(args.input, "r", encoding="utf-8") as f:
            payload = json.load(f)

        field = payload["field"]
        params_obj = payload["params"]
        params = PlannerParams(
            HFOV_deg=float(params_obj.get("HFOV_deg", 48.0)),
            h_agl_m=float(params_obj.get("h_agl_m", 50.0)),
            overlap=float(params_obj.get("overlap", 0.15)),
            edge_factor=float(params_obj.get("edge_factor", 0.40)),
            agl_tol_m=float(params_obj.get("agl_tol_m", 10.0)),
            angle_step_deg=int(params_obj.get("angle_step_deg", 5)),
            target_step_m=float(params_obj.get("target_step_m", 2.0)),
            max_nb_points=int(params_obj.get("max_nb_points", 5000)),
            fixed_theta_deg=(float(params_obj["fixed_theta_deg"]) if "fixed_theta_deg" in params_obj and params_obj["fixed_theta_deg"] is not None else None),
        )
        api = GeoAdminClient(sr=2056, min_delay_s=float(params_obj.get("min_delay_s", 0.05)))
        result = plan_waypoints_for_field(
            field_latlon=[(float(p[1]), float(p[0])) for p in field["polygon"]],
            start_latlon=(float(field["start"][1]), float(field["start"][0])),
            params=params,
            api=api,
        )
        out = {
            "ok": True,
            "path": [[wp["lon"], wp["lat"], wp["alt_abs_m"]] for wp in result.waypoints],
            "metrics": result.metrics,
            "formula": {"W": swath_width(params), "S": track_spacing(params)},
        }
    except Exception as exc:
        out = {"ok": False, "error": str(exc), "trace": traceback.format_exc()}

    with open(args.output, "w", encoding="utf-8") as f:
        json.dump(out, f, ensure_ascii=False)
    return 0


if __name__ == "__main__":
    raise SystemExit(main())

