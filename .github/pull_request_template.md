## Zusammenfassung

- Closes MOD-xxx / Closes #n
- Milestone: M?

## Constraint-Checkliste

- [ ] Speicherung und Ordner **Missionen** / **RKRK** unverändert (kein Rename, kein neues Backend)
- [ ] Öffentliche Dateinamen unverändert (`rkrk_jg_summary.php`, `rkrk_flugstatistik.php`, `rkrk_pubcomp.php`, `rkrk_quiz.php` und dokumentierte KML/Map-URLs)
- [ ] Query-Parameter unverändert oder nur **additiv** (Default = altes Verhalten); `Color`, `MenuOn`, `GA` getestet falls die Seite sie kennt
- [ ] PHP 8.0: betroffene Dateien syntaktisch geprüft, keine neuen Fatals
- [ ] Keine Admin-/TEST-only-Funktion für `intern` / `pub` / `gast` geöffnet
- [ ] Keine Altlasten-Löschung in demselben PR wie ein Feature

## Test

- [ ] Smoke-URL(s) aus dem Issue aufgerufen
- [ ] Bei UI: Ablauf wie ein Nutzer (nicht nur Screenshot)
- [ ] Bei öffentlichen Seiten: Varianten mit `MenuOn=off`, `Color=white`, `GA=gast`

## Risiko / Rollback

(kurz: welche Datei, wie zurück)
