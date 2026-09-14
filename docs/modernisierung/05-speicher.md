# Speicherung und Ordner

Stand: 2026-09-11. Die Modernisierung ändert **nicht** das Dateisystem-Modell. UI, Config und Unterhalt lesen und schreiben dieselben Orte.

Quelle der Wahrheit im Code: `SetMissionLocation()` und `$MissionDroneTypes` in `scripts/rkrk_gen.php`. Auflösung der Datenwurzel unabhängig vom PHP-CWD: Geschwisterordner der Webkopie, `../rkrk` (siehe `rkrk_unterhalt_rkrk_data_root()`, `map_env.php`, RT, Assess).

## Web vs. Daten

HTTP-Code liegt in den Webkopien. Geteilte Daten liegen **neben** ihnen unter `rkropen/`, nicht in `test27/`.

```text
rkropen/
  test27/          Webkopie Dev
  test/            Staging
  v2/              Intern
  pub/             Public
  rkrs/            Spin-off-Module (map, mapII, assess, calc)
  rkrk/            gemeinsame Daten  ← von test27 aus: ../rkrk
  Missionen/       MDB 3T (und Docs für die meisten Typen)
  Missionen Matrice 4TD/
  Missionen Matrice 30T/
  Missionen MavicII/
  Missionen Debug/
```

| Variable / Auflösung | Bedeutung |
|---|---|
| Von der Webkopie | `../rkrk/` — cwd-unabhängig über `__DIR__` (scripts → `dirname(__DIR__, 2)/rkrk`) |
| `$RKRKRelWebLocation` | `../../rkropen/` — **cwd-abhängig**; von `test27/` aus dasselbe wie `../`, in Unterordnern (assess, map) falsch |
| `$RKRKRelRKRKDataLocation` | `$RKRKRelWebLocation . "rkrk/"` — Legacy, nur Fallback |
| Unterhalt / Module | immer `../rkrk` bzw. `__DIR__/../../rkrk` |

Auswahl der Missionsbibliothek: Query/Session `MDB` (Default `3T`). `MissionLocation` und `DocLocation` sind **fest** (keine stillen Umbenennungen).

## Gemeinsamer Baum `../rkrk`

| Unterordner | Inhalt |
|---|---|
| `webdaten/` | Katalog-JSON RKRK-weit (Felder, POI, Layer, Regionen, Buchung, …). Quelle der Wahrheit seit der XML→JSON-Umstellung. `$jsonFileDirectory = 'webdaten/'` |
| `config/` | Overlay JSON (missions, regions, menu, branding, pilots, unterhalt, …) |
| `webcsv/` | Fluglisten, Kontakte, Planung (RKRK-weit, nicht missionsabhängig) |
| `data/map` | Kartenzeichnungen / Overlay (JSON, KML) — `map_env.php` |
| `data/rt` | Rechnungen |
| `data/ar` | Abrechnung / Kontobuch |
| `data/assess`, `data/liveassess` | Prüfvorlagen, Storyboards, Resultate |
| `Backups/catalogs/` | Katalog-Snapshots (Unterhalt) |
| `SiteBackups/{siteKey}/` | Website-Snapshots der jeweiligen Webkopie |

**Nicht** in `../rkrk`: Feld-JPG/KMZ (die liegen in den Missionsordnern). `rkrs/data/` ist lokal zum Spin-off und wird nicht mit den Webkopien synchronisiert.

Katalog-JSON in `Missionen/*/jsonfiles/` ist Altbestand (teilweise `jsonfiles-retired …`). Lesen/Schreiben läuft über `rkrk/webdaten/`.

## Missionsbibliotheken pro Drohnentyp

`$MissionDroneTypes` in `rkrk_gen.php`. Admin darf Name/aktiv/Freigabe overlayen (`rkrk_admin_missions.php`), **nicht** die Ordnernamen.

| `MDB` | Name | `MissionLocation` | `DocLocation` | aktiv (Ist) |
|---|---|---|---|---|
| `3T` | Mavic 3T | `Missionen/` | `Missionen/` | ja |
| `4TD` | Matrice 4TD | `Missionen Matrice 4TD/` | `Missionen/` | ja |
| `4T` | Matrice 4T | `Missionen Matrice 4T/` | `Missionen/` | im Code auskommentiert |
| `30T` | Matrice 30T | `Missionen Matrice 30T/` | `Missionen/` | `active` false |
| `M2` | Mavic 2 / Litchi | `Missionen MavicII/` | `Missionen/` | nur `release` test |
| `D` | Debug | `Missionen Debug/` | `Missionen Debug/` | ja, `release` test |

In jedem `MissionLocation`: Regionenordner (`Dir` aus `$RegionSettings`, z. B. `21_Aeugst`) mit Feld-JSON (Wegpunkte), daneben JPG/KMZ der Felder, optional restliche `jsonfiles/`.

Abgeleitete Pfade:

| Variable | Ort (Ist) |
|---|---|
| `$jsonMissionLocation` | `../rkrk/webdaten/` (RKRK-weit) |
| `$csvDocLocation` / `$csvMissionLocation` | `../rkrk/webcsv/` (RKRK-weit) |
| `$xmlFileDirectory` | `xmlfiles/` unter Mission/Doc — Legacy, `$RKRKXmlCatalogSupport = false` |
| `$historyfilelocation` | `DocLocation/_history/` (typisch `Missionen/_history/`) |
| `$logfilelocation` | `_log/` |
| `$deletefilelocation` | `_deleted/` |
| `$tempfilelocation` | `_temp/` |

## Regionen

`$RegionSettings` bindet JG-Codes an Ordnernamen (`Dir`), Kartenausschnitte und Gemeindekürzel. Overlay: `rkrk/config` — `Dir` bleibt eingefroren.

## Unterhalt (Backup / Recover / Sync)

Seite: `rkrk_admin_unterhalt.php` (nur Stufe `test`). Config: `rkrk/config/unterhalt.json`.

Drei getrennte Dienste (Money-Muster, keine Money-Klassen):

1. **Katalog** — Snapshots von `../rkrk` (`webdaten`, `config`, `webcsv`, `data` JSON/CSV/KML, ohne Bilder/Belege) **plus** Missions-JSON/KML je angehaktem MDB. Restore: Safety-Snapshot zuerst. Keine Feld-JPG/KMZ.
2. **Website** — Programmdateien der *aktuellen* Webkopie nach `../rkrk/SiteBackups/{siteKey}/`. Restore nur in dieselbe Instanz.
3. **Code-Sync** — Preview/Apply entlang `test27 → test → v2 → pub` und Teilmodule nach `rkrs/`. Route **Alle Ebenen** führt die restlichen Stufen ab der aktuellen Instanz aus (ohne rkrs, ohne Kopie auf sich selbst). Nach rkrs muss mindestens ein Modul (map, mapII, assess, calc) gewählt sein; das letzte Sync-Datum steht in `unterhalt.json` (`rkrs_last`) und in der UI. Nur neu/neuer (mtime + SHA1). Standard-Modus **Mirror** (Spiegelkopie, Extra-Dateien in der Matrix löschen); **Ergänzen** löscht nichts. Schreibt nicht nach `../rkrk` und nicht in Missionsordner.

**Steuerung:** Unterhalt schreiben von `test27` oder `test`. Katalog- und Website-Snapshots dort. Code-Sync Apply von beiden: von `test27` nach `test` (dann weiter), von `test` nach `v2` (dann `v2 → pub`). RoboCopy `v2→pub` bleibt an, bis die letzte Route mehrfach geklappt hat.

JSON-History (`_history/`, Prefix `YmdHis_`) in derselben UI; `rkrk_history_recovery.php` listet JSON, nicht mehr XML.

## Was nicht geändert werden darf

- `Missionen/` nach `missions/` umbenennen oder Leerzeichen entfernen.
- `rkrk/` verschieben (der Ordner **neben** den Webkopien bleibt).
- `MDB`-Schlüssel (`3T`, `4TD`, …) umbenennen.
- Katalog zurück auf XML als Quelle der Wahrheit stellen.

## Was erlaubt ist

- Dieselben Dateien über Admin/Unterhalt schreiben (mit `_history/` bzw. Snapshot).
- Lesepfade hinter Funktionen kapseln, **solange der aufgelöste Ort `../rkrk` bzw. der MDB-Missionsordner bleibt**.
- CWD-unabhängige Auflösung statt `realpath("../../rkropen/rkrk")`.
