import argparse
import json
import sys
from datetime import date
from decimal import Decimal, InvalidOperation
from pathlib import Path
from typing import Any, Dict, List, Set, Tuple


EXPECTED_FILES = [
    "cost_categories.json",
    "budget_plan_lines.json",
    "inventory_items.json",
    "import_runs.json",
    "import_issues.json",
]


def load_json_list(path: Path) -> List[Dict[str, Any]]:
    if not path.exists():
        raise RuntimeError(f"Missing file: {path}")
    with path.open("r", encoding="utf-8") as f:
        data = json.load(f)
    if not isinstance(data, list):
        raise RuntimeError(f"Invalid JSON root in {path}; expected list")
    return data


def load_transactions(data_dir: Path) -> List[Dict[str, Any]]:
    tdir = data_dir / "transactions"
    if tdir.is_dir():
        rows: List[Dict[str, Any]] = []
        year_files = sorted(p for p in tdir.glob("*.json") if not p.name.startswith("_"))
        if not year_files and not (tdir / "_index.json").exists():
            monolith = data_dir / "transactions.json"
            if monolith.exists():
                return load_json_list(monolith)
            raise RuntimeError(f"Missing yearly transactions in {tdir}")
        for path in year_files:
            rows.extend(load_json_list(path))
        return rows
    monolith = data_dir / "transactions.json"
    if monolith.exists():
        return load_json_list(monolith)
    raise RuntimeError(f"Missing transactions: {tdir} or {monolith}")


def parse_date(value: Any) -> bool:
    if value is None:
        return False
    if not isinstance(value, str):
        return False
    try:
        date.fromisoformat(value)
    except ValueError:
        return False
    return True


def parse_decimal(value: Any) -> bool:
    if value is None:
        return False
    try:
        Decimal(str(value))
    except (InvalidOperation, ValueError):
        return False
    return True


class Validator:
    def __init__(self, data_dir: Path, strict: bool = False):
        self.data_dir = data_dir
        self.strict = strict
        self.errors: List[str] = []
        self.warnings: List[str] = []
        self.data: Dict[str, List[Dict[str, Any]]] = {}

    def add_error(self, msg: str) -> None:
        self.errors.append(msg)

    def add_warning(self, msg: str) -> None:
        self.warnings.append(msg)

    def load(self) -> None:
        for file_name in EXPECTED_FILES:
            self.data[file_name] = load_json_list(self.data_dir / file_name)
        self.data["transactions.json"] = load_transactions(self.data_dir)

    def validate_categories(self) -> Tuple[Set[str], Set[str]]:
        rows = self.data["cost_categories.json"]
        codes: Set[str] = set()
        names: Set[str] = set()
        for i, row in enumerate(rows, start=1):
            code = str(row.get("code", "")).strip()
            name = str(row.get("name", "")).strip()
            if not code:
                self.add_error(f"cost_categories[{i}] missing code")
            if not name:
                self.add_error(f"cost_categories[{i}] missing name")
            if code in codes:
                self.add_error(f"Duplicate cost category code: {code}")
            if name.lower() in names:
                self.add_error(f"Duplicate cost category name (case-insensitive): {name}")
            if code:
                codes.add(code)
            if name:
                names.add(name.lower())
        return codes, names

    def validate_transactions(self, category_codes: Set[str]) -> None:
        rows = self.data["transactions.json"]
        seen_external: Set[str] = set()
        for i, row in enumerate(rows, start=1):
            ext = str(row.get("external_id", "")).strip()
            if not ext:
                self.add_error(f"transactions[{i}] missing external_id")
            if ext in seen_external:
                self.add_error(f"Duplicate transaction external_id: {ext}")
            seen_external.add(ext)

            if not parse_date(row.get("booking_date")):
                self.add_error(f"transactions[{i}] invalid booking_date: {row.get('booking_date')}")

            amount_in = row.get("amount_in")
            amount_out = row.get("amount_out")
            if not parse_decimal(amount_in):
                self.add_error(f"transactions[{i}] invalid amount_in: {amount_in}")
            if not parse_decimal(amount_out):
                self.add_error(f"transactions[{i}] invalid amount_out: {amount_out}")
            if parse_decimal(amount_in) and parse_decimal(amount_out):
                d_in = Decimal(str(amount_in))
                d_out = Decimal(str(amount_out))
                if d_in < 0 or d_out < 0:
                    self.add_error(f"transactions[{i}] negative amount values")
                if d_in == 0 and d_out == 0:
                    self.add_error(f"transactions[{i}] both amount_in and amount_out are zero")

            category_code = row.get("cost_category_code")
            raw_name = row.get("cost_category_name_raw")
            if category_code is not None and str(category_code).strip() != "":
                if str(category_code) not in category_codes:
                    self.add_error(f"transactions[{i}] unknown cost_category_code: {category_code}")
            else:
                if not raw_name:
                    self.add_warning(
                        f"transactions[{i}] has neither cost_category_code nor cost_category_name_raw"
                    )

            bs = row.get("booking_status")
            if bs is not None and str(bs).strip() != "":
                if str(bs).strip().lower() not in ("n", "v", "x"):
                    self.add_error(
                        f"transactions[{i}] invalid booking_status (expected n, v, or x): {bs!r}"
                    )

    def validate_budget(self, category_codes: Set[str]) -> None:
        rows = self.data["budget_plan_lines.json"]
        seen_keys: Set[str] = set()
        for i, row in enumerate(rows, start=1):
            period = row.get("period_date")
            cat = str(row.get("cost_category_code", "")).strip()
            amount = row.get("amount")

            if not parse_date(period):
                self.add_error(f"budget_plan_lines[{i}] invalid period_date: {period}")
            if not cat:
                self.add_error(f"budget_plan_lines[{i}] missing cost_category_code")
            elif cat not in category_codes:
                self.add_error(f"budget_plan_lines[{i}] unknown cost_category_code: {cat}")
            if not parse_decimal(amount):
                self.add_error(f"budget_plan_lines[{i}] invalid amount: {amount}")

            key = f"{period}|{cat}"
            if key in seen_keys:
                self.add_error(f"Duplicate budget key (period+category): {key}")
            seen_keys.add(key)

    def validate_inventory(self) -> None:
        rows = self.data["inventory_items.json"]
        seen_inventory: Set[str] = set()
        for i, row in enumerate(rows, start=1):
            inv = str(row.get("inventory_no", "")).strip()
            if not inv:
                self.add_error(f"inventory_items[{i}] missing inventory_no")
            if inv in seen_inventory:
                self.add_error(f"Duplicate inventory_no: {inv}")
            seen_inventory.add(inv)

            item_name = str(row.get("item_name", "")).strip()
            if not item_name:
                self.add_error(f"inventory_items[{i}] missing item_name")

            quantity = row.get("quantity")
            if not parse_decimal(quantity):
                self.add_error(f"inventory_items[{i}] invalid quantity: {quantity}")
            else:
                if Decimal(str(quantity)) < 0:
                    self.add_error(f"inventory_items[{i}] negative quantity")

            depreciation = row.get("depreciation_rate_pct")
            if depreciation is not None:
                if not parse_decimal(depreciation):
                    self.add_error(f"inventory_items[{i}] invalid depreciation_rate_pct: {depreciation}")
                else:
                    dec = Decimal(str(depreciation))
                    if dec < 0 or dec > 100:
                        self.add_error(
                            f"inventory_items[{i}] depreciation_rate_pct out of range 0-100: {depreciation}"
                        )

    def validate_import_runs_and_issues(self) -> None:
        runs = self.data["import_runs.json"]
        issues = self.data["import_issues.json"]
        run_ids = {str(r.get("id")) for r in runs if r.get("id")}
        for i, issue in enumerate(issues, start=1):
            rid = str(issue.get("import_run_id", "")).strip()
            sev = str(issue.get("severity", "")).strip().lower()
            if rid and rid not in run_ids:
                self.add_error(f"import_issues[{i}] references unknown import_run_id: {rid}")
            if sev not in {"warning", "error"}:
                self.add_error(f"import_issues[{i}] invalid severity: {sev}")

    def run(self) -> int:
        self.load()
        category_codes, _ = self.validate_categories()
        self.validate_transactions(category_codes)
        self.validate_budget(category_codes)
        self.validate_inventory()
        self.validate_import_runs_and_issues()

        summary = {
            "data_dir": str(self.data_dir),
            "strict": self.strict,
            "errors": len(self.errors),
            "warnings": len(self.warnings),
        }
        print(json.dumps(summary, ensure_ascii=False, indent=2))

        if self.errors:
            print("\nErrors:")
            for err in self.errors:
                print(f"- {err}")
        if self.warnings:
            print("\nWarnings:")
            for warn in self.warnings:
                print(f"- {warn}")

        if self.errors:
            return 1
        if self.strict and self.warnings:
            return 1
        return 0


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Validate JSON output generated by excel_importer.py")
    parser.add_argument(
        "--data-dir",
        default="data",
        help="Directory containing JSON files (default: ./data)",
    )
    parser.add_argument(
        "--strict",
        action="store_true",
        default=False,
        help="Return exit code 1 when warnings are present",
    )
    return parser.parse_args()


def main() -> int:
    args = parse_args()
    data_dir = Path(args.data_dir)
    if not data_dir.exists():
        print(f"Data directory not found: {data_dir}", file=sys.stderr)
        return 2
    validator = Validator(data_dir, strict=args.strict)
    return validator.run()


if __name__ == "__main__":
    raise SystemExit(main())
