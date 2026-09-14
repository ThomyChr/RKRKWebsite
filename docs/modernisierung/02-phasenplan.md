# Phasenplan — so wird vorgegangen

Grundsatz: **Schale zuerst, Kern später.** Kein Big-Bang-Rewrite. Jede Phase endet mit mergebarem Stand auf `test27`, der weiterhin fliegt.

Reihenfolge ist absichtlich so: erst Nachvollziehbarkeit und PHP-8-Stabilität, dann gemeinsame UI-Schale, dann Config-Auslagerung, dann Seitenweise Modernisierung, ganz am Schluss Aufräumen. So bleiben externe URLs und Dateipfade unangetastet, bis die Hülle steht.

```
M0 Inventar     →  M1 GitHub/CI-Basis  →  M2 PHP 8.0
        →  M3 UI-Shell  →  M4 Config-Admin  →  M5 Seiten-Wellen
        →  M6 Öffentliche Verträge härten  →  M7 Aufräumen
```

Jedes **M** ist ein GitHub-Milestone. Jedes Arbeitspaket darin ist ein Issue. Jedes Issue wird mit genau einem PR geschlossen. Katalog: [github-issues.md](github-issues.md).

---

## M0 — Inventar und Schutzlinien (kein UI-Umbau)

**Ziel:** Wissen, was man nicht zerbrechen darf.

- Liste aller HTTP-Einstiegspunkte (aktive `rkrk_*.php`, Unterordner).
- Liste der **öffentlichen Verträge** (siehe [04-externe-vertraege.md](04-externe-vertraege.md)) inkl. Beispiel-URLs von Partner-Sites.
- Liste der **Speicherpfade** und `MDB`-Mapping (siehe [05-speicher.md](05-speicher.md)) — als „Do not change“ markieren.
- Altlasten markieren: `* - Kopie`, `*_not used`, datierte Backups — **nicht** in M5 modernisieren.

**Ergebnis:** Milestone-Beschreibung + Issue-Kommentare mit Inventar. Kein Produktionsrisiko.

## M1 — GitHub-Nachvollziehbarkeit

**Ziel:** Ab jetzt ist jede Änderung einem Issue zuordenbar.

Siehe [03-github-nachvollziehbarkeit.md](03-github-nachvollziehbarkeit.md).

- Branch-Modell, Labels, Milestones, PR-Vorlage (liegt bereits unter `.github/`).
- Issues aus dem Katalog anlegen (nicht den Katalog ersetzen).
- Optional: PHP-Lint-Job (Syntax) auf geänderten Dateien — noch kein volles Test-Framework.

## M2 — PHP 8.0 kompatibel machen

**Ziel:** Dieselben Seiten laufen unter PHP 8.0 ohne Fatal/Deprecation, **ohne** Layout-Änderung.

Siehe [06-php80.md](06-php80.md).

- Zuerst aktive Kern-Includes (`rkrk_gen.php`, `rkrk_gen_ext.php`, `rkrk_access_control.php`, `rkrk_menu.php`, CSV/XML).
- Dann Einstiegspunkte der öffentlichen Seiten.
- Vendor und Backups ausklammern.
- Keine Funktionsverschiebung in diesem Milestone (sonst sind Bugs nicht zuordenbar).

## M3 — Gemeinsame UI-Schale

**Ziel:** Eine Shell für Kopf, Navigation, Inhalt, Fuss — Bedienung vereinfachen, Funktionen noch an alter Stelle.

- Neue, schlanke CSS-Variablen-Datei (Quiz ist Vorbild: `rkrk_quiz.php`).
- `headerpage()` / Top-Menü auf die Shell umstellen, **hinter Feature-Flag** oder nur auf `test/` zuerst.
- `Color=white|green` und `MenuOn=on|off` bleiben Query-Parameter; die Shell muss beide Zustände können (Vollseite vs. iframe).
- Menü: Gruppen belassen (Home, Felder, Einsätze, Info, Intern), aber weniger parallele Buttons auf der Startseite (`rkrk.php` hat heute Menü **und** Icon-Leiste).

**Nicht in M3:** Config aus `rkrk_gen.php` ziehen. Das kommt in M4, sonst vermischen sich zwei grosse Diffs.

## M4 — Konfiguration auf Admin-Seiten

**Ziel:** Dieselben Werte, anderer Ort, Rechte `test`/Admin.

Kandidaten zum Auslagern (Code bleibt Leser, Admin-Seite wird Schreiber):

| Heute in | Inhalt | Neue Admin-Oberfläche (Arbeitstitel) |
|---|---|---|
| `MissionDroneTypes` in `rkrk_gen.php` | Drohnen, `MissionLocation`, Buttons | `rkrk_admin_missions.php` |
| `RegionSettings` | JG, Gemeinden, Kartenausschnitt | `rkrk_admin_regions.php` |
| `$MainMenu` in `rkrk_menu.php` | Sichtbarkeit, Reihenfolge, Release | `rkrk_admin_menu.php` |
| Access-Level-Pfade, Default-`Color`/`Layout` | Branding, Embed-Defaults | `rkrk_admin_branding.php` |
| GeoAdmin-Layer-Strings | Karten-Defaults | Teil von Branding oder Karten-Admin |
| Backup / Sync / Pfade | Sites, Ordner-Matrix, Katalog- und Website-Snapshots, Code-Sync | `rkrk_admin_unterhalt.php` |

Speicherformat der Config: **eine JSON- oder CSV-Datei im bestehenden RKRK-/Doc-Baum** (nicht in einem neuen Cloud-Service). PHP-Arrays bleiben Fallback, bis die Admin-Seite speichert. Missions**dateien** selbst werden nicht verschoben.

Zugriff: bestehende `release => "test"` plus `users`-Listen. Keine neue Authentifizierung erfinden.

## M5 — Seiten in Wellen modernisieren

Jede Welle = eigenes Milestone oder ein Issue-Block. **Eine Welle = eine Nutzer-Reise**, nicht „alle PHP-Dateien alphabetisch“.

Vorschlag Reihenfolge (Risiko aufsteigend für Externe):

1. **Intern-Alltag:** Startseite, Felderliste, Feld editieren (nur Shell anbinden, Logik unangetastet).
2. **Einsätze:** Flugliste, Flüge, Checkliste (grosse Dateien — nur Markup/CSS, keine Speicherlogik).
3. **Karte intern:** `rkrk_map.php` — Query-Parameter-Vertrag einfrieren (Tests aus M6), dann CSS/Menu.
4. **Öffentliche Vier:** `rkrk_jg_summary.php`, `rkrk_flugstatistik.php`, `rkrk_pubcomp.php`, `rkrk_quiz.php` — **nach** internem Erfolg, mit Screenshot-/URL-Checkliste.
5. **Werkzeuge:** Conversion, Calc, Wetter.
6. **Unter-Apps** nur anbinden (gleiche Shell), nicht umschreiben: `mapII/`, `assess/`, `rt/`, `ar/`.

Pro Seite gilt: Funktionsgleichheit vor Schönheit. Wenn ein Schalter selten gebraucht wird, bleibt er erhalten — aber unter „Erweitert“ oder auf der Admin-Seite, nicht in der Hauptleiste.

## M6 — Öffentliche Verträge härten

Wenn M5 die vier öffentlichen Seiten anfasst: automatisierbare Checks.

- Feste Beispiel-URLs (dokumentiert in [04-externe-vertraege.md](04-externe-vertraege.md)) müssen 200 liefern.
- `MenuOn=off` zeigt keine Nav.
- `Color=white` bleibt hell (kein erzwungenes Grün).
- `GA=gast` umgeht Login wie bisher.

Das Milestone darf **keine** Parameter umbenennen. Neue Parameter nur additiv mit Default = altes Verhalten.

## M7 — Aufräumen

Erst wenn M5/M6 für eine Datei durch sind:

- Datierte Kopien und `not used` löschen (eigenes Issue pro Ordner, nie gemischt mit Feature-PRs).
- Tote CSS (`main_style.css` vs. `main_green.css`) zusammenführen, sobald keine Seite mehr darauf zeigt.

---

## Was bewusst parallel laufen darf

| Parallel OK | Nicht parallel |
|---|---|
| PHP-8-Fixes in Datei A und UI-Shell in Datei B | PHP-8-Fix **und** Layout-Rewrite in derselben Datei im selben PR |
| Admin-Config lesen (Fallback Array) | Speichern neuer Config **und** Pfadänderung Missionen |
| Docs/Issue-Katalog | Code, der öffentliche Parameter ändert |

## Stopp-Kriterien (PR nicht mergen)

- Missions- oder `rkrk/`-Pfad geändert, ohne dass das Issue ausdrücklich „Speicher“ ist (sollte nie vorkommen).
- Öffentliche Datei umbenannt oder Query-Parameter entfernt.
- PHP 8 Fatal auf einer der vier öffentlichen Seiten.
- Menü für `intern` sichtbar, obwohl Issue nur `test`-Admin betraf.
