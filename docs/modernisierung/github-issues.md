# Issue-Katalog (MOD-Nummern)

Verbindliche Nummerierung. GitHub-Issue-Titel immer `MOD-xxx: …`.  
Anlegen: Milestone + Labels laut Tabelle. Detailtexte für die ersten Issues: Ordner `issue-bodies/`.

Status in dieser Datei: `offen` bis die Arbeit in GitHub geführt wird.

## M0 Inventar

| ID | Titel | Labels |
|---|---|---|
| MOD-001 | Inventar aller HTTP-Einstiegspunkte | `phase-m0` |
| MOD-002 | Inventar öffentliche URL-Verträge + Partner-Beispiele | `phase-m0`, `constraint-public-api` |
| MOD-003 | Inventar Speicherpfade Missionen/RKRK/`MDB` | `phase-m0`, `constraint-storage` |
| MOD-004 | Inventar Altlasten (Kopien, not used, Datumsstände) | `phase-m0`, `cleanup` |

## M1 GitHub-Basis

| ID | Titel | Labels |
|---|---|---|
| MOD-010 | Labels, Milestones, Branch-Schutz dokumentieren/anlegen | `phase-m1` |
| MOD-011 | `.gitignore` (Secrets, `_temp`, Uploads, Vendor wenn nötig) | `phase-m1` |
| MOD-012 | Optional: PHP-Lint-Workflow auf geänderten Dateien | `phase-m1`, `php8` |

## M2 PHP 8.0

| ID | Titel | Labels |
|---|---|---|
| MOD-020 | PHP-8: Kern-Includes (`rkrk_gen.php`, `rkrk_gen_ext.php`, Menu, Access, CSV) | `phase-m2`, `php8` |
| MOD-021 | PHP-8: öffentliche Vier ohne HTML-Änderung | `phase-m2`, `php8`, `constraint-public-api` |
| MOD-022 | PHP-8: restliche aktive Einstiegspunkte (Welle A Kernseiten) | `phase-m2`, `php8` |
| MOD-023 | PHP-8: `map/` + `mapII/` aktive PHP | `phase-m2`, `php8`, `no-rewrite` |
| MOD-024 | PHP-8: `assess/` + `exam/` | `phase-m2`, `php8`, `no-rewrite` |

Nicht in M2: `rt/dompdf/vendor`, `scripts/fpdf/tutorial`, Dateien mit `Kopie` / `not used`.

## M3 UI-Shell

| ID | Titel | Labels |
|---|---|---|
| MOD-030 | Design-Tokens + Basis-CSS (eine Datei, Variablen) | `phase-m3`, `ui-shell` |
| MOD-031 | `headerpage()` / Footer auf Shell, `Color` + RKRS-Zweig erhalten | `phase-m3`, `ui-shell` |
| MOD-032 | Top-Menü vereinfachen, Gruppen behalten, `MenuOn` respektieren | `phase-m3`, `ui-shell` |
| MOD-033 | Startseite `rkrk.php`: Doppel-Navigation entfernen, Funktionen gleich | `phase-m3`, `ui-shell` |

## M4 Config-Admin (nur `test` / Admin-User)

| ID | Titel | Labels |
|---|---|---|
| MOD-040 | Config-Fallback: Arrays bleiben, optional JSON/CSV im Doc-Baum | `phase-m4`, `admin-config`, `constraint-storage` |
| MOD-041 | Admin: Missionsbibliotheken (`MDB`, Ordner) — Pfade nicht umbenennen | `phase-m4`, `admin-config`, `constraint-storage` |
| MOD-042 | Admin: `RegionSettings` | `phase-m4`, `admin-config` |
| MOD-043 | Admin: Menüsichtbarkeit / Reihenfolge (`$MainMenu`) | `phase-m4`, `admin-config` |
| MOD-044 | Admin: Branding, Default-`Color`, Embed-Defaults, GeoAdmin-Basis-URL | `phase-m4`, `admin-config`, `constraint-public-api` |

## M5 Seiten-Wellen

| ID | Titel | Labels |
|---|---|---|
| MOD-050 | Welle Felder: Liste + Edit (nur Shell, Speicher gleich) | `phase-m5`, `ui-shell`, `constraint-storage` |
| MOD-051 | Welle Flüge: `rkrk_fluege.php`, `rkrk_flugliste.php` | `phase-m5`, `ui-shell` |
| MOD-052 | Welle Checkliste (Markup/CSS, Logik unangetastet) | `phase-m5`, `ui-shell` |
| MOD-053 | Welle Karte intern `rkrk_map.php` (Query-Vertrag einfrieren) | `phase-m5`, `ui-shell`, `constraint-public-api` |
| MOD-054 | Welle öffentlich: `rkrk_jg_summary.php` | `phase-m5`, `constraint-public-api` |
| MOD-055 | Welle öffentlich: `rkrk_flugstatistik.php` | `phase-m5`, `constraint-public-api` |
| MOD-056 | Welle öffentlich: `rkrk_pubcomp.php` | `phase-m5`, `constraint-public-api` |
| MOD-057 | Welle öffentlich: `rkrk_quiz.php` (IDs/JS halten) | `phase-m5`, `constraint-public-api` |
| MOD-058 | Werkzeuge: Calc, Conversion, Wetter — Shell anbinden | `phase-m5`, `ui-shell` |
| MOD-059 | Unter-Apps nur Shell-Link: `mapII`, `assess`, `rt`, `ar` | `phase-m5`, `no-rewrite` |

## M6 Öffentliche Verträge

| ID | Titel | Labels |
|---|---|---|
| MOD-060 | Smoke-URL-Liste + manuelle/CI-Checks 200 + Menu/Color/GA | `phase-m6`, `constraint-public-api` |
| MOD-061 | `rkrk_dokml.php` Parameter-Doku (GeoAdmin) ohne URL-Bruch | `phase-m6`, `constraint-public-api` |

## M7 Aufräumen

| ID | Titel | Labels |
|---|---|---|
| MOD-070 | Löschen `* - Kopie*` im `map/` (nach Inventar MOD-004) | `phase-m7`, `cleanup` |
| MOD-071 | Löschen datierte `*_YYYYMMDD.php` und `*_not used*` (pro Ordner eigene PRs) | `phase-m7`, `cleanup` |
| MOD-072 | Unbenutztes Legacy-CSS entfernen, wenn keine Referenz mehr | `phase-m7`, `cleanup` |

## Reihenfolge zum Anlegen in GitHub

Zuerst Milestones M0–M7, dann Labels, dann Issues MOD-001 … in aufsteigender Nummer. Nicht alle M5-Issues auf einmal in Bearbeitung nehmen — Board: nur ein „In progress“ pro Person.
