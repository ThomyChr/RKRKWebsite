# Rehkitzrettung Excel Importer

Imports `Abrechnung Rehkitzrettung.xlsm` into local JSON files.

## 1) Setup

```bash
python -m venv .venv
. .venv/Scripts/activate
pip install -r requirements.txt
```

## 2) Dry-Run starten

```bash
python excel_importer.py \
  --workbook "\\\\tchr-frog.synology.me@SSL@5606\\DavWWWRoot\\Rehkitzrettung\\Finanzen\\Abrechnung Rehkitzrettung.xlsm" \
  --output-dir data \
  --dry-run
```

## 3) Echtimport starten

```bash
python excel_importer.py \
  --workbook "\\\\tchr-frog.synology.me@SSL@5606\\DavWWWRoot\\Rehkitzrettung\\Finanzen\\Abrechnung Rehkitzrettung.xlsm" \
  --output-dir data
```

## Hinweise

- Es wird keine Datenbank benötigt.
- Der Import ist idempotent über Schlüssel in JSON:
  - `transactions.external_id`
  - `inventory_items.inventory_no`
  - `budget_plan_lines` via `period_date + cost_category_code`
- Validierungsprobleme landen in `data/import_issues.json`.
- Import-Läufe landen in `data/import_runs.json`.
- Erzeugte Dateien:
  - `data/cost_categories.json`
  - `data/transactions/YYYY.json` (plus `_index.json`, `_totals.json`, `_idmap.json`)
  - `data/budget_plan_lines.json`
  - `data/inventory_items.json`
  - `data/import_runs.json`
  - `data/import_issues.json`

## JSON validieren

```bash
python validate_json.py --data-dir data
```

- Exit-Code `0`: keine Fehler gefunden
- Exit-Code `1`: Validierungsfehler gefunden

Für strengere Pipelines:

```bash
python validate_json.py --data-dir data --strict
```

- Mit `--strict` gibt es auch bei Warnungen Exit-Code `1`.

## One-command (PowerShell)

```powershell
.\run_all.ps1 `
  -Workbook "\\tchr-frog.synology.me@SSL@5606\DavWWWRoot\Rehkitzrettung\Finanzen\Abrechnung Rehkitzrettung.xlsm" `
  -DataDir data `
  -Strict
```

- Führt zuerst den Import aus, danach die Validierung.
- Mit `-DryRun` wird nur testweise importiert (keine JSON-Schreiboperation).

## One-command (Batch / Doppelklick)

```bat
run_all.bat "\\tchr-frog.synology.me@SSL@5606\DavWWWRoot\Rehkitzrettung\Finanzen\Abrechnung Rehkitzrettung.xlsm" data --strict
```

Optionale Flags:
- `--dry-run`
- `--strict`

## PHP Dashboard starten

```bash
php -S localhost:8080 -t public
```

Dann im Browser:
- `http://localhost:8080`

Das Dashboard zeigt:
- KPI-Kacheln (Einnahmen, Ausgaben, Netto, Budgetvergleich)
- Monatsverlauf (Einnahmen/Ausgaben/Netto)
- Kostenarten-Auswertung
- Inventar-Kennzahlen

Weitere Seiten:
- `http://localhost:8080/inventur.php` (Inventar gemäss Excel inkl. Abschreibungsvorschau)
- `http://localhost:8080/kostenarten.php` (Kostenarten: CRUD, Sortierung, aktiv/inaktiv)
Weitere Seiten (bestehend):
- `http://localhost:8080/kontobuch.php` (klassisches Kontobuch mit Ein-/Auszahlungen, Transfer, Status, Beleg)
- `http://localhost:8080/accounts.php` (Konti: anlegen, bearbeiten, aktiv/inaktiv, löschen; Felder inkl. IBAN, Bank, Inhaber, Adressen, BIC, Kontotyp)
- `http://localhost:8080/transactions.php` (Filter + CSV Export)
- `http://localhost:8080/reports.php` (Budget-vs-Ist + Jahresauswertung)
- `http://localhost:8080/cashflow.php` (monatlicher + kumulierter Cashflow)
- `http://localhost:8080/manage.php` (Eingabe von Buchungen, Budget, Budgetkategorien, Inventur)

### Kontobuch-Funktionen

- Mehrere Konti mit eigener Währung
- Transferzahlungen zwischen Konti
- Bei unterschiedlichen Währungen: Quellbetrag **und** Zielbetrag erfassen
- Klassische Spalten:
  - Datum
  - Empfänger
  - Kostenart
  - Konto (inkl. Zielkonto bei Transfer)
  - Haben/Aus
  - Soll/Ein
  - Saldo (laufend pro Konto)
  - Währung
  - Bemerkung
  - Beleg (Upload + Link)
  - Buchungsstatus (`n`, `v`, `x`)
