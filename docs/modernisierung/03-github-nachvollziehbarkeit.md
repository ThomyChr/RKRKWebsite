# GitHub — jeder Schritt nachvollziehbar

Ziel: In einem Jahr soll man in GitHub sehen **warum** etwas geändert wurde, **was** betroffen war, und **dass** die Constraints (Speicher, öffentliche URLs, PHP 8) geprüft wurden.

Der Issue-Katalog ist die Arbeitliste: [github-issues.md](github-issues.md). Die Vorlagen liegen in [`.github/`](../../.github/).

## Modell

```
Milestone (Phase M0–M7)
    └── Issue (ein Arbeitspaket)
            └── Branch `mod/<issue-nr>-kurzname`
                    └── Pull Request (schliesst das Issue)
                            └── Commits (klein, ein Thema)
```

Keine Direkt-Commits auf den geschützten Hauptbranch, sobald das Repo auf GitHub liegt. Keine „Sammel-PRs“ über mehrere Milestones.

## Branches

| Branch | Zweck |
|---|---|
| `main` (oder der heutige Produktions-/Test-Stand) | Immer lauffähig unter PHP 8.0-Ziel; speicherkompatibel |
| `mod/012-ui-shell` | Ein Issue, ein Branch |
| `hotfix/…` | Nur Produktionsbrände, eigener Milestone |

Namensschema: `mod/<dreistellige-katalognummer>-<slug>`  
Beispiel: Issue MOD-012 → Branch `mod/012-ui-shell`.

## Commits

- Eine logische Änderung pro Commit.
- Message auf Deutsch oder Englisch, aber **Issue-Referenz** zwingend: `MOD-012: gemeinsame headerpage-Shell ohne MenuOn-Änderung`.
- Kein `git commit --amend` auf bereits gepushte Commits (siehe Projektrichtlinie).
- Keine Secrets (CSV mit Passwort-Hashes, User-Listen) in Git, falls sie im Workspace liegen — `.gitignore` in M1 setzen.

## Pull Requests

Jeder PR verwendet `.github/pull_request_template.md` und muss die Constraint-Checkliste ausfüllen:

- [ ] Speicherung / Ordner Missionen & RKRK unverändert
- [ ] Öffentliche Dateinamen und Query-Parameter unverändert (oder additiv dokumentiert)
- [ ] PHP 8.0: betroffene Dateien gelintet
- [ ] Rechte: keine Admin-Funktion für intern/pub/gast geöffnet
- [ ] Issue schliesst mit `Closes #n` / `Closes MOD-0xx`

Review-Minimum: Diff lesen + die im Issue genannte Smoke-URL öffnen. UI-Änderungen: betroffene Seite im Browser durchklicken, nicht nur Screenshot.

## Labels (in GitHub anlegen)

| Label | Farbe-Idee | Bedeutung |
|---|---|---|
| `phase-m0` … `phase-m7` | grau | Milestone-Zugehörigkeit (zusätzlich zum Milestone-Objekt) |
| `constraint-storage` | rot | Darf Speicher/Pfade nur mit Extra-Review anfassen |
| `constraint-public-api` | orange | Öffentliche PHP-Dateien / Query-Vertrag |
| `php8` | lila | PHP-8-Kompatibilität |
| `ui-shell` | blau | Layout/Navigation |
| `admin-config` | grün | Auslagerung auf TEST/ADMIN-Seiten |
| `no-rewrite` | gelb | Nur anbinden, nicht neu schreiben (`mapII`, `assess`, …) |
| `cleanup` | braun | Löschen von Kopien |

## Milestones

Namen exakt so (finden sich in Issues wieder):

1. `M0 Inventar`
2. `M1 GitHub-Basis`
3. `M2 PHP 8.0`
4. `M3 UI-Shell`
5. `M4 Config-Admin`
6. `M5 Seiten-Wellen`
7. `M6 Öffentliche Verträge`
8. `M7 Aufräumen`

## Issues anlegen

Sobald ein GitHub-Remote existiert:

```text
gh issue create --title "MOD-012: Gemeinsame UI-Shell (headerpage + Menü)" --body-file docs/modernisierung/issue-bodies/MOD-012.md --label ui-shell --milestone "M3 UI-Shell"
```

Bis dahin gilt der Katalog in `github-issues.md` als verbindliche Nummerierung. **Keine stillen Extra-Issues ohne MOD-Nummer**, sonst bricht die Nachvollziehbarkeit.

## Was nicht nachvollziehbar wäre (vermeiden)

- Layout, PHP-8-Fix und Config-Auslagerung in einem PR.
- Dateien umbenennen ohne Issue mit Label `constraint-public-api`.
- „Kleine Fixes nebenbei“ auf fremden Branches.
- Kopieren ganzer Seiten zu `*_new.php` als Dauerzustand (erzeugt wieder Datumsleichen).
