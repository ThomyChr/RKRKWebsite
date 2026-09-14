# RKRK-Web (test27)

PHP-Anwendung der Rehkitzrettung Knonaueramt und Zug (`rkropen/test27`).

## Modernisierung

Ziele, Constraints, Phasen und GitHub-Issues: **[docs/README.md](docs/README.md)**.

Der erste Commit auf diesem Repository ist die **Baseline**. Jede weitere Änderung läuft über ein `MOD-xxx`-Issue und einen Pull Request, siehe [docs/modernisierung/03-github-nachvollziehbarkeit.md](docs/modernisierung/03-github-nachvollziehbarkeit.md).

## Constraints (kurz)

- Speicherung und Ordner **Missionen** / **RKRK** bleiben.
- Öffentliche Dateien `rkrk_jg_summary.php`, `rkrk_flugstatistik.php`, `rkrk_pubcomp.php`, `rkrk_quiz.php` bleiben unter gleichem Namen und Query-Vertrag erreichbar.
- Ziel-Laufzeit: **PHP 8.0**.
- Konfiguration wandert schrittweise auf Admin-Seiten (`test` / Admin), Funktionen bleiben.

## Hinweis

Missionsdateien liegen ausserhalb dieses Webordners (`../../rkropen/Missionen/`, `../../rkropen/rkrk/`) und sind **nicht** Teil dieses Repos.

Grosse Medien (`kmlfiles/`, Quiz-Bilder, JPGs unter `images/`, PDFs unter `doc/`) bleiben auf dem NAS und sind per `.gitignore` aus dem Repo ausgenommen, damit GitHub die **Code-Baseline** tragen kann.
