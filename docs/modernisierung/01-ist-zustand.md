# Ist-Zustand

Kurzaufnahme des Codes in `rkropen/test27` (August 2026). Kein Wunschbild.

## Charakter

Klassische PHP-Seiten: jede Seite inkludiert Generatoren, rendert HTML selbst, mischt Logik, Markup und Konfiguration. Es gibt bereits Inseln neueren Codes (`mapII/`, `assess/`, `rkrk_quiz.php` mit CSS-Variablen).

Es gibt **kein** Root-`README`, **kein** `composer.json` für die App selbst, **kein** `.github/`-Workflow — Versionierung und Nachvollziehbarkeit müssen mit dieser Modernisierung erst eingeführt bzw. durchgesetzt werden.

## Kern-Includes

| Datei | Rolle |
|---|---|
| `rkrk.php` | Startseite |
| `rkrk_access_control.php` | Login, Session, Cookie, `GrantAccess()`, Gast `GA=gast` |
| `scripts/rkrk_gen.php` | Zentrale Konfiguration **und** Hilfsfunktionen: `MissionDroneTypes`, `RegionSettings`, `SetMissionLocation()`, Wetter, URL-Parser |
| `scripts/rkrk_gen_ext.php` | Access-Level-Verzeichnisse, `headerpage()`, `footer()`, `GetTableColor()`, Layout `2023` vs. alt, `Color=white/green` |
| `scripts/rkrk_menu.php` | `$MainMenu` (Dutzende Einträge mit `release`, Icons, Targets), GeoAdmin-KML-Links, `CreateTopMenu()` |
| `scripts/rkrk_csvfiles.php` / `rkrk_xmlfiles.php` / `rkrk_catalog_io.php` | Datei-I/O; Kataloge JSON in `rkrk/webdaten/` |
| `scripts/rkrk_felddaten.php` | Feldkatalog |
| `template/*.css` | Legacy-Styles (`main_style.css`, `main_green.css`, `main_scratch.css`, …) |

`rkrk_gen.php` enthält zugleich **Daten** (Regionen, Drohnentypen, Pfade) und **Code**. Das ist der Hauptgrund, Konfiguration später auf Admin-Seiten auszulagern — nicht weil die Funktionen weg sollen.

## Rechte und Umgebungen

In `scripts/rkrk_gen_ext.php` steuern URL-Pfade das Erscheinungsbild:

| Stufe (`WebFreigabe`) | Verzeichnis | Typische Nutzung |
|---|---|---|
| `gast` / `pub` | `pub/` | Öffentlich / Gast |
| `extern` | `ex/` | Externe Partner |
| `partner` | `v5/` | Partner |
| `intern` | `v2/` | Intern |
| `test` | `test/` | Test/Admin (dieser Workspace) |

Menüeinträge haben `release` (kommaseparierte Stufen) und optional `users` (Namensliste). Konfigurations-UI der Modernisierung hängt an **`test`** plus den bestehenden Admin-User-Listen, nicht an einem neuen Rollensystem.

Gastzugriff über Query: `GA=gast` (siehe `rkrk_access_control.php`).

## Module (Seitengruppen)

Nicht vollständig, aber die Arbeitsgrenzen für PRs:

| Gruppe | Einstieg (Beispiele) | Hinweis |
|---|---|---|
| Kern / Felder | `rkrk.php`, `rkrk_felderliste.php`, `rkrk_edit_felddaten.php` | Alltagsbetrieb |
| Karte | `rkrk_map.php`, `rkrk_dokml.php` | Viele Query-Parameter, Embed |
| Flüge | `rkrk_fluege.php`, `rkrk_flugliste.php`, `rkrk_flugstatistik.php` | Statistik auch extern |
| Checkliste / Planung | `rkrk_checklist.php`, `rkrk_planning.php` | Sehr grosse Dateien |
| Quiz | `rkrk_quiz.php` | Bereits teilweise modernisiert; öffentlich |
| JG-Summary | `rkrk_jg_summary.php` | Öffentlich |
| Pub-Komponenten | `rkrk_pubcomp.php` | Wetter/Einsatzgebiete für Partner-Sites |
| Karteneditor | `map/`, `mapII/` | `mapII` ist der neuere Strang |
| Assess / Exam | `assess/`, `exam/` | Eigenes Theme |
| Rechnung / Abrechnung | `rt/`, `ar/` | Eigene Apps, später anfassen |
| Legacy-Kopien | `* - Kopie.php`, `*_YYYYMMDD.php`, `*_not used.php` | Nicht modernisieren, später löschen |

## Layout heute

- `headerpage()` schreibt HTML 4.01, viele Favicon-Links, Font Awesome 4, `template/main_scratch.css` + `main_green.css`.
- Farbe über `Color=white|green` und Layout-Flag `2023`.
- Menü oft pro Seite mit `MenuOn=on|off` ausgeblendet (iframes).
- Viele Seiten duplizieren Header/Menu-Logik statt einer gemeinsamen Shell.

## Speicherung (kurz)

Siehe [05-speicher.md](05-speicher.md). Missionen und der gemeinsame Datenbaum liegen **neben** dem Webordner (`../rkrk`, `../Missionen/`, …), gesteuert durch `SetMissionLocation()` und `MDB`. Katalog-JSON: `rkrk/webdaten/`.

## Bereits sichtbare PHP-8-Spuren

Einzelne Dateien nutzen `??` und `session_status()` (`rkrk_access_control.php`). Gleichzeitig gibt es klassische Risiken: Zugriff auf Arrays ohne `isset`, `strpos`/`strtolower` auf potenziell `null`, `$string{$i}` in Alt-Kopien von `rkrk_gen_newaccess.php` (in PHP 8 entfernt). Details: [06-php80.md](06-php80.md).
