# Externe Schnittstellen (unveränderlicher Vertrag)

Diese Seiten werden von **aussen** aufgerufen (iframes, Partner-Websites, GeoAdmin-KML, Quiz-Links). Modernisierung darf Aussehen intern ändern, aber **Aufruf und steuerbare Optik** müssen kompatibel bleiben.

Goldene Regel: neue Parameter nur **additiv**. Default ohne Parameter = heutiges Verhalten.

## Gemeinsame Embed-Parameter

Viele Seiten teilen dasselbe Muster (Parser: `GetURLwDefault*` in `scripts/rkrk_gen.php`, Farbe in `scripts/rkrk_gen_ext.php`):

| Parameter | Werte | Wirkung |
|---|---|---|
| `GA` | `gast` | Gastzugriff ohne Login |
| `Color` | `white`, `green` | Tabellen-/Seitenfarbe (`GetTableColor`) |
| `MenuOn` | `on`, `off` | Navigation ein/aus (wichtig für iframes) |
| `MDB` | `3T`, `4TD`, `30T`, `M2`, `D` | Missionsbibliothek / Ordnerwahl |
| `Reg` | JG-Codes, Listen mit Leerzeichen | Gebietsfilter |
| `Layout` | (Session/`2023`) | Altes vs. 2023-CSS — nicht stillschweigend entfernen |

`headerpage()` und `CreateTopMenu()` müssen `MenuOn=off` und `Color=white` auch nach der Shell-Umstellung respektieren.

---

## `rkrk_jg_summary.php`

**Rolle:** Zusammenfassung Jagdgebiet (Jäger/Piloten, Flugfelder heute/morgen), oft verlinkt.

| Parameter | Bedeutung |
|---|---|
| `Reg` | Jagdgebiet oder Liste (`21` oder `21 25 26`) |

Nutzt `headerpage()` und `scripts/rkrk_menu.php`. Layout-Modernisierung: Tabellen dürfen CSS bekommen, Spalteninhalt und `Reg`-Semantik bleiben.

Smoke: `rkrk_jg_summary.php?Reg=26&GA=gast`

## `rkrk_flugstatistik.php`

**Rolle:** Charts für intern und als Embed (Google Charts).

Dokumentierte Parameter (Datei-Kopfkommentar):

| Parameter | Bedeutung |
|---|---|
| `Reg` / `RegList` | Regionen |
| `Jahr`, `SelFrom`, `SelTo` | Zeitraum |
| `Charts` | z. B. `Top,Entwicklung1,Entwicklung2` |
| `Color` | `white` für helle Einbettung |
| `ChartHeight`, `ChartWidth` | Pixel |
| `GA` | `gast` |
| `MenuOn` | `off` im iframe |
| `MDB` | Drohnenbibliothek |
| `FeldNamenWahl`, `PilotenWahl` | Filter |
| `ALL` | alle Grafiken |
| `Format` | u. a. `full` |

Beispiel-URLs aus dem Code:

```text
rkrk_flugstatistik.php?RegList=28&Color=white&Charts=Top&GA=gast
rkrk_flugstatistik.php?RegList=28&Jahr=2023&Color=white&Charts=Top,Entwicklung1,Entwicklung2&GA=gast
```

Bei UI-Umbau: Chart-IDs und die Liste `Charts=` nicht umbenennen.

## `rkrk_pubcomp.php`

**Rolle:** Bausteine für drohnenrehkitzrettung.ch (Wetter, Einsatzgebietskarte).

| Parameter | Bedeutung |
|---|---|
| `Table` | `Wetter`, `Einsatzgebiete`, oder `all` (Default) |
| `Color` | wird nach `headerpage(..., $BaseColor)` durchgereicht |

Interner Karten-iframe setzt bereits:

```text
MenuOn=off&GA=gast&Color=white&MapButtons=off
```

Das muss weiter funktionieren. `rkrk_changes.php` verlinkt historisch:

```text
rkrk_pubcomp.php?Table=Einsatzgebiete&Color=white
```

## `rkrk_quiz.php`

**Rolle:** Öffentliches/internes Quiz; Layout schon teilweise modern (CSS-Variablen).

Vertrag: Dateiname, vorhandene DOM-IDs für JS (`#frage-bild`, Timer-Container, …) nicht brechen. Admin bleibt `rkrk_quiz_admin.php` (`release` test / namentliche User).

Smoke: `rkrk_quiz.php` und bestehende Quiz-Deep-Links (falls Parameter dokumentiert sind — im PR von M5/M6 ergänzen, nicht vorher erfinden).

---

## Weitere externe Abhängigkeiten (nicht umbenennen)

Diese sind keine der vier genannten Dateien, aber gleich empfindlich:

| Datei | Wer ruft auf |
|---|---|
| `rkrk_dokml.php` | map.geo.admin.ch KML-Layer (`scripts/rkrk_menu.php` enthält lange hartcodierte URLs) |
| `rkrk_map.php` | iframes (`MenuOn`, `Embed`, `MapButtons`, `GA`, `Color`, `Reg`, Layer-Flags) |
| `rkrk_flugliste.php` | Embed mit `MenuOn=off&GA=gast&Color=white` |

Hartcodierte GeoAdmin-URLs in `rkrk_menu.php` zeigen teils auf `/rkropen/test/`. Die in M4 auf Config zu ziehen, **ohne** die öffentlichen KML-Query-Parameter zu ändern.

## Checkliste für jeden PR an diesen Dateien

1. Alte Beispiel-URL unverändert aufrufen.
2. Mit `MenuOn=off` keine Hauptnavigation.
3. Mit `Color=white` kein erzwungenes dunkles/grünes Chrome, das Partner-Seiten unlesbar macht.
4. Mit `GA=gast` kein Login-Zwang.
5. Screenshot oder kurzer PR-Kommentar: „Vertrag gehalten“.
