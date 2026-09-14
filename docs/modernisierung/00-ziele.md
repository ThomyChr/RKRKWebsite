# Ziele und Grenzen

Stand: 2026-09-11. Gilt für den Code unter `rkropen/test27`.

## Was modernisiert werden soll

1. **Layout und Bedienung** entschlacken: weniger Buttons, weniger parallele Menüs, klarere Navigation, einheitliches Erscheinungsbild (nicht mehr HTML-4-Tabellenlayout als Standard).
2. **Funktionen erhalten**: Feldkatalog, Karten, Flüge, Checklisten, Quiz, Assessments, Editoren, PDFs usw. bleiben nutzbar.
3. **Konfiguration aus dem Alltags-UI nehmen**: Schalter, Pfade, Drohnen-Bibliotheken, Regionsdefinitionen, die heute in PHP-Arrays in `scripts/rkrk_gen.php` / `scripts/rkrk_menu.php` liegen, wandern auf **Konfigurationsseiten** mit Zugriff nur für `test` / Admin-Benutzer.
4. **PHP 8.0** als Ziel-Laufzeit (Warnungen und Fatals beseitigen, ohne die Datei-Speicherung umzubauen).
5. **Externe Einbettungen** bleiben kompatibel (Dateiname, Query-Parameter, Menu an/aus, Farbgebung).

## Harte Constraints (nicht verhandelbar in dieser Modernisierung)

| Constraint | Bedeutung |
|---|---|
| Speicherung | CSV/JSON/KMZ-Dateien bleiben das Backend. Keine Pflicht-Migration auf eine SQL-Datenbank. XML ist Legacy (`$RKRKXmlCatalogSupport = false`). |
| Ordner Missionen | `Missionen/`, `Missionen Matrice 4TD/`, `Missionen Matrice 30T/`, `Missionen MavicII/`, `Missionen Debug/` und die Zuordnung über `MDB` (`3T`, `4TD`, `30T`, `M2`, `D`) bleiben. |
| Ordner RKRK | Gemeinsamer Datenordner **neben** den Webkopien: `rkropen/rkrk/` (von `test27/` aus `../rkrk`). Nicht verschieben. `$RKRKRelRKRKDataLocation = ../../rkropen/rkrk/` ist nur der cwd-abhängige Alias. |
| Öffentliche Dateinamen | `rkrk_jg_summary.php`, `rkrk_flugstatistik.php`, `rkrk_pubcomp.php`, `rkrk_quiz.php` bleiben unter genau diesen Namen erreichbar. |
| Query-Vertrag | Bestehende Parameter (`Reg`, `Color`, `MenuOn`, `GA`, `Charts`, `MDB`, …) dürfen nicht stillschweigend umbenannt oder entfernt werden. |
| Rechte | Bestehende Stufen `gast`, `pub`, `extern`, `partner`, `intern`, `test` bleiben; Admin-Konfig nur `test` (und explizit benannte Admin-User). |

## Was bewusst nicht Ziel ist

- Neuschreiben als SPA/Framework (React, Laravel, …) in einem Rutsch.
- Umbenennen oder Verschieben der Missionsdateien auf der Disk.
- Öffentliche Partner-Websites (z. B. drohnenrehkitzrettung.ch iframes) zu einem Breaking Change zwingen.
- Vendor-Code (`rt/dompdf/vendor`, FPDF-Tutorials) als Erstes anfassen.
- Kopien und Datums-Backups (`* - Kopie.php`, `*_20260428.php`) in derselben Welle „schön“ machen — die werden später gelöscht, nicht modernisiert.

## Erfolgsbild

Nach Abschluss:

- Alltag: wenige klare Menüpunkte, eine Shell (Kopf, Navigation, Inhalt), mobil bedienbar.
- Test/Admin: Konfiguration (Regionen, Drohnenpfade, Menü, Farben, Embed-Defaults) auf eigenen Seiten.
- Partner/extern: dieselben URLs, dieselben Parameter, gleiches sichtbares Verhalten (Menu, Farbe).
- Server: PHP 8.0 ohne Deprecation-Fatals auf den produktiven Einstiegspunkten.
- GitHub: jede Phase als Milestone, jedes Arbeitspaket als Issue, jede Änderung als PR mit Checkliste.
