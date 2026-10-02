/**
 * View-dependent help for the overlay editor.
 * Icons match the toolbar buttons in rkrk_map_obj_features.js.
 */
window.RKRK_HELP_CATALOG = {
  pages: {
    map: {
      title: "Overlay-Editor",
      file: "rkrk_map_overlay.php",
      summary: "Objekte auf der Karte zeichnen, fixieren, speichern und als KML, DJI-Layer oder PDF weitergeben. Kein Export ist Live."
    }
  },
  icons: {
    draw: '<svg viewBox="0 0 24 24"><path fill="currentColor" d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>',
    stop: '<svg viewBox="0 0 24 24"><path fill="currentColor" d="M6 6h12v12H6z"/></svg>',
    clear: '<svg viewBox="0 0 24 24"><path fill="currentColor" d="M6 19a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>',
    fix: '<svg viewBox="0 0 24 24"><path fill="currentColor" d="M9 16.2 4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4z"/></svg>',
    discard: '<svg viewBox="0 0 24 24"><path fill="currentColor" d="M12.5 8c-2.65 0-5.05.99-6.9 2.6L2 7v9h9l-3.62-3.62c1.39-1.16 3.16-1.88 5.12-1.88 3.54 0 6.55 2.31 7.6 5.5l2.37-.78C21.08 11.03 17.15 8 12.5 8z"/></svg>',
    undo: '<svg viewBox="0 0 24 24"><path fill="currentColor" d="M12.5 8c-2.65 0-5.05.99-6.9 2.6L2 7v9h9l-3.62-3.62c1.39-1.16 3.16-1.88 5.12-1.88 3.54 0 6.55 2.31 7.6 5.5l2.37-.78C21.08 11.03 17.15 8 12.5 8z"/></svg>',
    redo: '<svg viewBox="0 0 24 24"><path fill="currentColor" d="M18.4 10.6A7.5 7.5 0 0 0 5.5 12.9l2.37.78C8.95 10.99 11.96 8.68 15.5 8.68c1.96 0 3.73.72 5.12 1.88L17 14.18h9V5.18l-3.6 3.6z"/></svg>',
    kml: '<svg viewBox="0 0 24 24"><path fill="currentColor" d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm4 18H6V4h7v5h5v11z"/></svg>',
    kmlLoad: '<svg viewBox="0 0 24 24"><path fill="currentColor" d="M5 20h14v-2H5v2zm7-18L5.33 9h3.84v4h5.66V9h3.84L12 2z"/></svg>',
    map: '<svg viewBox="0 0 24 24"><path fill="currentColor" d="M20.5 3l-.16.03L15 5.1 9 3 3.36 4.9c-.21.07-.36.25-.36.48V20.5c0 .28.22.5.5.5l.16-.03L9 18.9l6 2.1 5.64-1.9c.21-.07.36-.25.36-.48V3.5c0-.28-.22-.5-.5-.5zM15 19l-6-2.11V5l6 2.11V19z"/></svg>',
    pdf: '<svg viewBox="0 0 24 24"><path fill="currentColor" d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm-1.5 14H8v-2h4.5a1 1 0 0 0 0-2H10a1 1 0 0 0-1 1v4h5.5a1 1 0 0 0 0-2H12.5v1zM14 9V3.5L19.5 9H14z"/></svg>',
    mbtiles: '<svg viewBox="0 0 24 24"><path fill="currentColor" d="M3 5h8v6H3V5zm10 0h8v6h-8V5zM3 13h8v6H3v-6zm10 0h8v6h-8v-6z"/></svg>',
    hub2: '<svg viewBox="0 0 24 24"><path fill="currentColor" d="M19.35 10.04A7.49 7.49 0 0 0 12 4C9.11 4 6.6 5.64 5.35 8.04A5.994 5.994 0 0 0 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM12 12l-4 4h3v4h2v-4h3l-4-4z"/></svg>',
    hub2send: '<svg viewBox="0 0 24 24"><path fill="currentColor" d="M2.01 21 23 12 2.01 3 2 10l15 2-15 2z"/></svg>',
    link: '<svg viewBox="0 0 24 24"><path fill="currentColor" d="M3.9 12a5 5 0 0 1 5-5h3v2h-3a3 3 0 1 0 0 6h3v2h-3a5 5 0 0 1-5-5zm7-1h6v2h-6v-2zm2-4h3a5 5 0 0 1 0 10h-3v-2h3a3 3 0 1 0 0-6h-3V7z"/></svg>',
    point: '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="5" fill="currentColor"/></svg>',
    text: '<svg viewBox="0 0 24 24"><path fill="currentColor" d="M5 4v3h5.5v12h3V7H19V4H5z"/></svg>',
    freehand: '<svg viewBox="0 0 24 24"><path fill="currentColor" d="M3 17c3-1 5-4 7-4s3 2 5 2 4-3 6-3v2c-2 0-4 3-6 3s-3-2-5-2-4 3-7 4v-2z"/></svg>',
    pfeil: '<svg viewBox="0 0 24 24"><path fill="currentColor" d="M3 11h12.17l-3.58-3.59L13 6l6 6-6 6-1.41-1.41L15.17 13H3v-2z"/></svg>',
    grid: '<svg viewBox="0 0 24 24"><path fill="currentColor" d="M3 3h8v8H3V3zm10 0h8v8h-8V3zM3 13h8v8H3v-8zm10 0h8v8h-8v-8z"/></svg>',
    circle: '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="8" fill="none" stroke="currentColor" stroke-width="2"/></svg>',
    circles: '<svg viewBox="0 0 24 24"><circle cx="9" cy="12" r="5.5" fill="none" stroke="currentColor" stroke-width="1.8"/><circle cx="15" cy="12" r="5.5" fill="none" stroke="currentColor" stroke-width="1.8"/></svg>',
    polygon: '<svg viewBox="0 0 24 24"><path fill="currentColor" d="M12 3l8 5.5v7L12 21l-8-5.5v-7L12 3zm0 2.4L6.5 9.2v5.6L12 18.6l5.5-3.8V9.2L12 5.4z"/></svg>',
    show: '<svg viewBox="0 0 24 24"><path fill="currentColor" d="M12 5c-5 0-9.27 3.11-11 7 1.73 3.89 6 7 11 7s9.27-3.11 11-7c-1.73-3.89-6-7-11-7zm0 12a5 5 0 1 1 0-10 5 5 0 0 1 0 10zm0-8a3 3 0 1 0 0 6 3 3 0 0 0 0-6z"/></svg>',
    hide: '<svg viewBox="0 0 24 24"><path fill="currentColor" d="M2.1 3.51 3.51 2.1l18.39 18.39-1.41 1.41-3.13-3.13A12.3 12.3 0 0 1 12 19c-5 0-9.27-3.11-11-7a13.4 13.4 0 0 1 4.35-5.13L2.1 3.51zM12 7a5 5 0 0 1 4.9 6.07l-1.52-1.52A3 3 0 0 0 12.45 8.6L12 7zm0 10c1.4 0 2.72-.3 3.92-.84l-1.56-1.56A5 5 0 0 1 8.4 9.64L6.84 8.08A11.4 11.4 0 0 0 1 12c1.73 3.89 6 7 11 7z"/></svg>',
    fills: '<svg viewBox="0 0 24 24"><path fill="#00aa00" d="M3 3h8v8H3z"/><path fill="#ff2222" d="M13 3h8v8h-8z"/><path fill="#ffd400" d="M3 13h8v8H3z"/><path fill="#888888" d="M13 13h8v8h-8z"/></svg>',
    satellite: '<svg viewBox="0 0 24 24"><path fill="currentColor" d="M11 6.5A5.5 5.5 0 0 1 16.5 12H19A8 8 0 0 0 11 4v2.5zM6.5 13A5.5 5.5 0 0 1 12 7.5V5A8 8 0 0 0 4 13h2.5zM12 13a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/></svg>',
    search: '<svg viewBox="0 0 24 24"><path fill="currentColor" d="M15.5 14h-.79l-.28-.27A6.47 6.47 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>',
    save: '<svg viewBox="0 0 24 24"><path fill="currentColor" d="M17 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V7l-4-4zm-5 16a3 3 0 1 1 0-6 3 3 0 0 1 0 6zm3-10H5V5h10v4z"/></svg>',
    color: '<svg viewBox="0 0 24 24"><path fill="currentColor" d="M12 3a9 9 0 0 0 0 18c.83 0 1.5-.67 1.5-1.5 0-.39-.15-.74-.39-1.01-.23-.26-.36-.61-.36-.99a1.5 1.5 0 0 1 1.5-1.5H16a5 5 0 0 0 0-10h-4zm-5.5 9a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm3-4a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3z"/></svg>',
    ruler: '<svg viewBox="0 0 24 24"><path fill="currentColor" d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>',
    live: '<svg viewBox="0 0 24 24"><path fill="currentColor" d="M12 5c4.97 0 9 3.58 9 8s-4.03 8-9 8-9-3.58-9-8 4.03-8 9-8zm0 3a5 5 0 1 0 .01 10.01A5 5 0 0 0 12 8zm0 2.5a2.5 2.5 0 1 1 0 5 2.5 2.5 0 0 1 0-5z"/></svg>'
  },
  views: {
    "map.overview": {
      page: "map",
      title: "Übersicht",
      icon: "draw",
      use: "Objekttyp wählen, zeichnen, fixieren. Die Karte bleibt zum Verschieben frei, solange Zeichnen nicht aktiv ist.",
      tools: [
        { icon: "grid", name: "Objekttyp", text: "Links unter Objekt: Raster, Polygon, Linie, Pfeil, Punkte, Text, Kreis." },
        { icon: "draw", name: "Zeichnen", text: "Startet das Setzen auf der Karte. Ohne diesen Knopf bewegt ein Klick nur die Karte." },
        { icon: "fix", name: "Fixieren", text: "Schliesst die Bearbeitung ab und schreibt das Objekt fest in die Liste (Badge «fix»). Danach keine versehentlichen Änderungen mehr." },
        { icon: "discard", name: "Verwerfen", text: "Nimmt den aktuellen Entwurf oder die letzten Änderungen zurück." },
        { icon: "clear", name: "Löschen", text: "Entfernt das angewählte Objekt." }
      ],
      steps: [
        { icon: "grid", text: "Objekttyp wählen." },
        { icon: "draw", text: "Zeichnen einschalten, dann auf die Karte klicken oder ziehen." },
        { icon: "fix", text: "Fixieren, wenn Form und Lage stimmen — Objekt ist danach «fertig»." }
      ]
    },
    "map.grid": {
      page: "map",
      title: "Raster",
      icon: "grid",
      use: "Beschriftetes Suchraster (N/E) auf die Karte setzen. Zuerst Zeichnen, dann die Südwest-Ecke klicken.",
      tools: [
        { icon: "draw", name: "Zeichnen", text: "Einschalten, danach ein Klick setzt die Südwest-Ecke." },
        { icon: "stop", name: "Stop", text: "Zeichnen aus — Karte wieder verschieben und zoomen." },
        { icon: "fix", name: "Fixieren", text: "Bearbeitung abschliessen. Danach Badge «fix»; Winkel und Rahmen sind nicht mehr aktiv." },
        { icon: "color", name: "Kachelfarbe", text: "Doppelklick oder Rechtsklick auf die Beschriftung: grün → rot → orange → gelb → grau → durchsichtig." },
        { icon: "fills", name: "Resultatflächen", text: "Häkchen «Resultatflächen anzeigen» oder Farb-Icon in der Objektliste blendet die Kachelfarben ein/aus. Die Farben bleiben gespeichert." },
        { icon: "ruler", name: "Lineal / Winkel", text: "Nur während der Bearbeitung: Rand greifen oder Lineal drehen. Am fixierten Raster ist der Winkel ausgeblendet." }
      ],
      steps: [
        { icon: "draw", text: "Zeichnen starten." },
        { icon: "grid", text: "Klick auf die Karte = Südwest-Ecke. Ausserhalb einer Zelle erneut klicken zum Neu-Setzen." },
        { icon: "color", text: "Kacheln einfärben per Doppelklick oder Rechtsklick-Menü." },
        { icon: "fix", text: "Fixieren, wenn Abstand und Drehung stimmen." }
      ]
    },
    "map.draw_grid": {
      page: "map",
      title: "Raster zeichnen",
      icon: "grid",
      use: "Zeichnen ist aktiv. Ein Klick setzt die Südwest-Ecke. Die Karte lässt sich erst nach Stop wieder frei schieben.",
      tools: [
        { icon: "grid", name: "Klick", text: "Setzt oder verschiebt die Südwest-Ecke (nicht in eine bestehende Zelle klicken)." },
        { icon: "stop", name: "Stop", text: "Zeichnen beenden, Karte navigieren." },
        { icon: "fix", name: "Fixieren", text: "Raster übernehmen." }
      ],
      steps: [
        { icon: "grid", text: "Klick = Südwest-Ecke." },
        { icon: "ruler", text: "Rand greifen und ziehen, um das Raster zu verschieben oder zu drehen." },
        { icon: "stop", text: "Stop, dann Karte verschieben." }
      ]
    },
    "map.polygon": {
      page: "map",
      title: "Polygon",
      icon: "polygon",
      use: "Fläche mit Eckpunkten zeichnen. Punkte lassen sich danach verschieben, einfügen oder löschen.",
      tools: [
        { icon: "draw", name: "Zeichnen", text: "Startet das Setzen von Eckpunkten." },
        { icon: "stop", name: "Stop", text: "Zeichnen beenden. Das Polygon bleibt editierbar, bis Sie fixieren." },
        { icon: "fix", name: "Fixieren", text: "Bearbeitung abschliessen und Polygon fest in der Liste speichern (Badge «fix»)." },
        { icon: "fills", name: "Flächenfärbung", text: "Häkchen «Flächenfärbung anzeigen» oder Farb-Icon in der Objektliste blendet die Füllfarbe ein/aus. Die Farbe bleibt gespeichert." }
      ],
      steps: [
        { icon: "draw", text: "Zeichnen starten, dann Eckpunkte per Klick setzen." },
        { icon: "polygon", text: "Doppelklick schliesst die Fläche (oder Stop)." },
        { icon: "draw", text: "Punkt ziehen = verschieben. + auf der Kante = Punkt einfügen. Doppelklick auf Punkt = löschen." }
      ]
    },
    "map.draw_polygon": {
      page: "map",
      title: "Polygon zeichnen",
      icon: "polygon",
      use: "Zeichnen ist aktiv. Jeder Linksklick setzt einen Eckpunkt.",
      tools: [
        { icon: "polygon", name: "Klick", text: "Neuer Eckpunkt." },
        { icon: "stop", name: "Stop / Doppelklick", text: "Fläche schliessen." }
      ],
      steps: [
        { icon: "polygon", text: "Mindestens 3 Punkte setzen." },
        { icon: "stop", text: "Doppelklick oder Stop beendet das Zeichnen." }
      ]
    },
    "map.freehand": {
      page: "map",
      title: "Freihandlinie",
      icon: "freehand",
      use: "Linie frei auf die Karte zeichnen — halten und ziehen oder per Klick Punkte setzen.",
      tools: [
        { icon: "freehand", name: "Zeichnen", text: "Einschalten, dann auf der Karte halten oder klicken." },
        { icon: "stop", name: "Stop", text: "Zeichnen aus, Karte wieder verschieben." },
        { icon: "fix", name: "Fixieren", text: "Linie übernehmen." }
      ],
      steps: [
        { icon: "draw", text: "Zeichnen starten." },
        { icon: "freehand", text: "Maustaste halten und ziehen, oder einzelne Punkte klicken." },
        { icon: "fix", text: "Fixieren." }
      ]
    },
    "map.draw_freehand": {
      page: "map",
      title: "Freihand zeichnen",
      icon: "freehand",
      use: "Zeichnen ist aktiv. Halten zeichnet durchgehend, Klicks setzen Stützpunkte.",
      tools: [
        { icon: "freehand", name: "Halten", text: "Linie entlang der Bewegung." },
        { icon: "point", name: "Klick", text: "Einzelner Stützpunkt." },
        { icon: "stop", name: "Stop", text: "Zeichnen beenden." }
      ]
    },
    "map.pfeil": {
      page: "map",
      title: "Pfeil",
      icon: "pfeil",
      use: "Richtungspfeil: zuerst Start, dann Ende auf der Karte setzen.",
      tools: [
        { icon: "pfeil", name: "Zeichnen", text: "Einschalten, dann Startpunkt und Endpunkt klicken." },
        { icon: "fix", name: "Fixieren", text: "Pfeil übernehmen." }
      ],
      steps: [
        { icon: "draw", text: "Zeichnen starten." },
        { icon: "point", text: "Erster Klick = Start, zweiter Klick = Spitze." },
        { icon: "fix", text: "Fixieren." }
      ]
    },
    "map.draw_pfeil": {
      page: "map",
      title: "Pfeil zeichnen",
      icon: "pfeil",
      use: "Zeichnen ist aktiv. Zwei Klicks: Start und Spitze.",
      tools: [
        { icon: "point", name: "1. Klick", text: "Start des Pfeils." },
        { icon: "pfeil", name: "2. Klick", text: "Spitze / Ende." },
        { icon: "stop", name: "Stop", text: "Zeichnen beenden." }
      ]
    },
    "map.points": {
      page: "map",
      title: "Punkte",
      icon: "point",
        use: "Beschriftete Punkte setzen. Farbe im Typ-Menü wählen (Start, Parkplatz und weitere Grundfarben). Die Schriftfarbe folgt der Punktfarbe; in den Stileinstellungen bleibt eine manuelle Schriftfarbe möglich.",
      tools: [
        { icon: "point", name: "Punkt setzen", text: "Knopf klicken, dann auf die Karte klicken." },
        { icon: "text", name: "Beschriftung", text: "Text links wählen. Schriftfarbe = Punktfarbe. Doppelklick ändert den Text." },
        { icon: "fix", name: "Fixieren", text: "Punkt übernehmen, danach den nächsten setzen." }
      ],
      steps: [
        { icon: "point", text: "Typ und Text wählen, dann Punkt-Knopf." },
        { icon: "map", text: "Klick auf die Karte setzt den Punkt." },
        { icon: "fix", text: "Fixieren, bevor Sie den nächsten setzen." }
      ]
    },
    "map.textfields": {
      page: "map",
      title: "Textfeld",
      icon: "text",
      use: "Freier Text ohne Punktsymbol. Klick setzt das Feld, Doppelklick ändert den Inhalt.",
      tools: [
        { icon: "text", name: "Text setzen", text: "Knopf, dann Klick auf die Karte." },
        { icon: "fix", name: "Fixieren", text: "Textfeld übernehmen." }
      ],
      steps: [
        { icon: "text", text: "Text eingeben, Knopf klicken, auf die Karte klicken." },
        { icon: "draw", text: "Doppelklick auf das Feld zum Bearbeiten." }
      ]
    },
    "map.circle": {
      page: "map",
      title: "Einzelkreis",
      icon: "circle",
      use: "Ein Kreis um einen Mittelpunkt. Radius links in Metern, dann Zeichnen und Klick auf die Karte.",
      tools: [
        { icon: "draw", name: "Zeichnen", text: "Einschalten, Klick setzt den Mittelpunkt." },
        { icon: "circle", name: "Radius", text: "Wert in Metern im Objekt-Panel." },
        { icon: "fix", name: "Fixieren", text: "Kreis übernehmen." }
      ],
      steps: [
        { icon: "circle", text: "Radius einstellen." },
        { icon: "draw", text: "Zeichnen, dann Mittelpunkt klicken." },
        { icon: "fix", text: "Fixieren." }
      ]
    },
    "map.draw_circle": {
      page: "map",
      title: "Kreis setzen",
      icon: "circle",
      use: "Zeichnen ist aktiv. Ein Klick setzt den Mittelpunkt des Kreises.",
      tools: [
        { icon: "circle", name: "Klick", text: "Mittelpunkt." },
        { icon: "stop", name: "Stop", text: "Zeichnen beenden." }
      ]
    },
    "map.circles": {
      page: "map",
      title: "3 farbige Kreise",
      icon: "circles",
      use: "Drei konzentrische Kreise (rot / grün / blau) um denselben Mittelpunkt. Radien links einstellen.",
      tools: [
        { icon: "draw", name: "Zeichnen", text: "Einschalten, Klick setzt den gemeinsamen Mittelpunkt." },
        { icon: "circles", name: "Radien", text: "Drei Werte in Metern im Objekt-Panel." },
        { icon: "fix", name: "Fixieren", text: "Kreise übernehmen." }
      ]
    },
    "map.draw_circles": {
      page: "map",
      title: "Kreise setzen",
      icon: "circles",
      use: "Zeichnen ist aktiv. Ein Klick setzt den Mittelpunkt aller drei Kreise.",
      tools: [
        { icon: "circles", name: "Klick", text: "Gemeinsamer Mittelpunkt." },
        { icon: "stop", name: "Stop", text: "Zeichnen beenden." }
      ]
    },
    "map.viewer": {
      page: "map",
      title: "Sync-Gerät (Zuschauen)",
      icon: "show",
      use: "Sie sehen die Live-Karte. Zeichnen ist aus. Oben rechts: Hilfe (?) ganz aussen, daneben «Folge dem Master».",
      tools: [
        { icon: "show", name: "Folge dem Master ◎", text: "Weiss: folgt dem Ausschnitt. Klick → eigener Zoom (rot ●). Nochmal → wieder folgen." },
        { icon: "live", name: "Hilfe ?", text: "Immer ganz rechts oben — erklärt die aktuelle Sicht." },
        { icon: "map", name: "Karte", text: "Verschieben/zoomen löst das Folgen (rot), bis Sie wieder folgen." }
      ],
      steps: [
        { icon: "show", text: "◎ = folgt dem Broadcast/Master." },
        { icon: "show", text: "● rot = eigener Zoom; Klick bringt Sie zurück zum Folgen." }
      ]
    },
    "map.kos_host": {
      page: "map",
      title: "KOS / Master (Live)",
      icon: "live",
      use: "Mit URL-Code KOS, ADMIN oder INSTR: Live senden, Multi-Edit, Nachrichten und Follow-Fokus steuern.",
      tools: [
        { icon: "live", name: "Broadcast", text: "Rechts oben (links neben ?): Live starten/stoppen. Rot = sendet." },
        { icon: "link", name: "Link / QR", text: "Teil-Link kopieren oder QR für Sync-Geräte und Co-Editoren." },
        { icon: "text", name: "Nachricht", text: "Broadcast-Text an alle Geräte; Empfänger müssen bestätigen." },
        { icon: "show", name: "Follow-Fokus ◎/◉", text: "Weiss = folgt. Grün ◉ = Sie sind Fokus-Master. Rot ● = eigener Zoom." },
        { icon: "live", name: "Editoren", text: "Badge «Editoren»: Sub-Editoren auf Share-only setzen oder trennen." }
      ],
      steps: [
        { icon: "live", text: "Broadcast starten, Link/QR teilen." },
        { icon: "show", text: "Follow-Fokus übernehmen (grün), wenn alle Ihren Ausschnitt sehen sollen." },
        { icon: "fix", text: "Objekte zeichnen und fixieren — Subs sehen den Stand live." }
      ]
    },
    "map.kos_sub": {
      page: "map",
      title: "KOS Co-Editor (Sub)",
      icon: "live",
      use: "Sie bearbeiten mit. Master heisst KOS. Follow-Fokus und Locks beachten.",
      tools: [
        { icon: "draw", name: "Zeichnen", text: "Gleiche Objektwerkzeuge wie der Master; nur ein Objekt gleichzeitig." },
        { icon: "show", name: "Follow-Fokus", text: "Weiss folgen, rot eigener Zoom, Klick kann Fokus übernehmen (grün)." },
        { icon: "live", name: "Locks", text: "Objekt von jemand anderem gesperrt? Warten oder anderes wählen." }
      ]
    }
  },
  functions: [
    {
      id: "map.basemap",
      pages: ["map"],
      views: [],
      roles: ["editor", "kos"],
      icon: "satellite",
      title: "Basiskarte",
      what: "Oben links zwischen Pixelkarte, Satellit und Schwarz-weiss umschalten.",
      tools: [
        { icon: "map", name: "Karte", text: "SwissTopo Pixelkarte (Farbe)." },
        { icon: "satellite", name: "Satellit", text: "Swissimage Luftbild." },
        { icon: "map", name: "S/W", text: "SwissTopo schwarz-weiss." }
      ]
    },
    {
      id: "map.sidebar",
      pages: ["map"],
      views: [],
      roles: ["editor", "kos"],
      icon: "draw",
      title: "Seitenleiste ein-/ausklappen",
      what: "Schmaler Knopf am Rand der linken Leiste (<< / >>). Breite per Ziehen am Rand.",
      tools: [{ icon: "draw", name: "<< / >>", text: "Leiste verstecken oder wieder einblenden." }]
    },
    {
      id: "map.search",
      pages: ["map"],
      views: [],
      roles: ["editor", "kos"],
      icon: "search",
      title: "Ort suchen & Standort",
      what: "Unter Koordinate: Ortsname suchen oder «Zum aktuellen Standort (5x5 km)».",
      tools: [
        { icon: "search", name: "Ort suchen", text: "z. B. Aarau — Karte springt zum Treffer." },
        { icon: "point", name: "Standort", text: "GPS-Zentrum mit 5×5 km Ausschnitt." }
      ]
    },
    {
      id: "map.fix_discard",
      pages: ["map"],
      views: [],
      roles: ["editor", "kos"],
      icon: "fix",
      title: "Fixieren, verwerfen, Undo",
      what: "Fixieren schliesst die Bearbeitung ab: das Objekt steht danach fest in der Liste (Badge «fix») und ändert sich nicht mehr versehentlich. Nur ein Objekt gleichzeitig in Bearbeitung. Ein fixiertes Objekt öffnen Sie wieder mit Bearbeiten.",
      tools: [
        { icon: "fix", name: "Fixieren", text: "Bearbeitung beenden und Objekt festschreiben. Doppelklick auf der Karte kann dasselbe tun." },
        { icon: "discard", name: "Verwerfen", text: "Letzte Änderungen am aktuellen Objekt rückgängig." },
        { icon: "undo", name: "Rückgängig", text: "← für fixierte Objekte (Ctrl+Z)." },
        { icon: "redo", name: "Wiederholen", text: "↪ (Ctrl+Y)." },
        { icon: "clear", name: "Löschen", text: "Angewähltes Objekt entfernen." }
      ]
    },
    {
      id: "map.object_list",
      pages: ["map"],
      views: [],
      roles: ["editor", "kos"],
      icon: "show",
      title: "Objektliste",
      what: "Alle fixierten und offenen Objekte. Sichtbarkeit, Bearbeiten, Flächenfarben, Gruppe, Reihenfolge. Badge «fix» = abgeschlossen, «Bearb.» = in Bearbeitung.",
      tools: [
        { icon: "show", name: "Auge", text: "Objekt auf der Karte ein-/ausblenden." },
        { icon: "fills", name: "Flächenfarben", text: "Beim Raster: Resultatflächen. Beim Polygon: Füllfarbe. Ein/aus, Farben bleiben gespeichert." },
        { icon: "draw", name: "Bearbeiten", text: "Fixiertes Objekt wieder zum Ändern öffnen." },
        { icon: "fix", name: "Fixieren", text: "Offenes Objekt aus der Liste abschliessen." },
        { icon: "text", name: "Name", text: "Name anklicken zum Umbenennen. Enter übernimmt, Esc bricht ab." },
        { icon: "grid", name: "Ziehen", text: "Reihenfolge ändern oder in eine Gruppe legen." }
      ]
    },
    {
      id: "map.groups",
      pages: ["map"],
      views: [],
      roles: ["editor", "kos"],
      icon: "grid",
      title: "Gruppen",
      what: "Mehrere Objekte markieren und gruppieren. Gruppe klappen, Transparenz und Sichtbarkeit steuern.",
      tools: [
        { icon: "grid", name: "Gruppe", text: "Objekte per Checkbox wählen, dann gruppieren." },
        { icon: "show", name: "Gruppe sichtbar", text: "Ganze Gruppe auf der Karte ausblenden." },
        { icon: "color", name: "Transparenz", text: "Schieberegler an der Gruppe." }
      ]
    },
    {
      id: "map.context",
      pages: ["map"],
      views: [],
      roles: ["editor", "kos"],
      icon: "draw",
      title: "Kontextmenü auf der Karte",
      what: "Rechtsklick auf ein Objekt: Ebene nach vorne/hinten, bearbeiten, löschen, bei Raster Farben.",
      tools: [
        { icon: "draw", name: "Ebene vor/zurück", text: "Zeichnungsreihenfolge ändern." },
        { icon: "color", name: "Rasterfarbe", text: "Am Raster auch Doppelklick auf die Kachel-Beschriftung." }
      ]
    },
    {
      id: "map.styles",
      pages: ["map"],
      views: [],
      roles: ["editor", "kos"],
      icon: "color",
      title: "Stileinstellungen",
      what: "Schwebendes Fenster: Farbe, Linienstärke, Füllung, Schrift — je nach Objekttyp.",
      tools: [{ icon: "color", name: "Stil", text: "Änderungen gelten sofort am bearbeiteten Objekt." }]
    },
    {
      id: "map.grid_color",
      pages: ["map"],
      views: ["map.grid", "map.draw_grid", "map.overview"],
      roles: ["editor", "kos"],
      icon: "color",
      title: "Rasterkacheln einfärben",
      what: "Farbe bleibt nach Fixieren und im geo.admin-Export erhalten. Im Raster-Formular oder in der Objektliste können die Resultatflächen ein- und ausgeblendet werden; die Farben bleiben gespeichert.",
      tools: [
        { icon: "color", name: "Doppelklick", text: "grün → rot → orange → gelb → grau → durchsichtig." },
        { icon: "color", name: "Rechtsklick", text: "Farbmenü direkt wählen." },
        { icon: "fills", name: "Resultatflächen", text: "Häkchen beim Raster oder Farb-Icon in der Objektliste blendet die Kachelfarben ein/aus." }
      ]
    },
    {
      id: "map.polygon_fill",
      pages: ["map"],
      views: ["map.polygon", "map.draw_polygon", "map.overview"],
      roles: ["editor", "kos"],
      icon: "fills",
      title: "Polygon-Flächenfärbung",
      what: "Die Füllfarbe bleibt gespeichert. Ausblenden zeigt nur den Rahmen — sinnvoll, wenn die Karte darunter lesbar bleiben soll.",
      tools: [
        { icon: "fills", name: "Häkchen", text: "«Flächenfärbung anzeigen» im Polygon-Formular." },
        { icon: "fills", name: "Objektliste", text: "Farb-Icon am Polygon blendet dieselbe Füllung ein/aus." }
      ]
    },
    {
      id: "map.overlays",
      pages: ["map"],
      views: [],
      roles: ["editor", "kos"],
      icon: "show",
      title: "SwissTopo-Overlays & KML-Layer",
      what: "Schwebendes Fenster: Strom, Grenzen, RPAS, Hindernisse, swissALTI3D und swissSURFACE3D Relief u. a. mit Transparenz. AuLaV und beide Relief-Ebenen sind standard aus. Unten: KML als Layer laden. Fenster am Titel greifen und verschieben (auch Tablet). Doppelklick auf den Titel dockt es klein oben rechts an; erneuter Doppelklick holt es zurück.",
      tools: [
        { icon: "show", name: "Overlay", text: "Häkchen + Schieberegler für Deckkraft." },
        { icon: "kmlLoad", name: "KML laden", text: "Fremde KML ein-/ausschaltbar unter den Overlays." },
        { icon: "draw", name: "Andocken", text: "Doppelklick auf den Fenstertitel: klein neben die runden Knöpfe oben rechts." }
      ]
    },
    {
      id: "map.export",
      pages: ["map"],
      views: [],
      roles: ["editor", "kos"],
      icon: "kml",
      title: "Export & Teilen",
      what: "Sektion Export & Teilen. Nichts davon ist Live: jede Datei ist ein Snapshot. KML für Earth/geo.admin, DJI Layer als MBTiles auf die RC (Profil → Karteneinstellungen → Custom Layer), SwissTopo, PDF, Ansicht-Link.",
      whatUnlock: "Mit URL ?ADMIN oder ?HUB zusätzlich: Hub 2 KML (Annotationen in FlightHub 2 importieren, Ordner an Pilot 2 verteilen) und optional Nach Hub 2 senden. Kein Live.",
      urlUnlock: ["ADMIN", "HUB"],
      tools: [
        { icon: "kml", name: "KML", text: "Google Earth / map.geo.admin." },
        { icon: "hub2", name: "Hub 2 KML", text: "Nur mit URL ?ADMIN oder ?HUB. Annotationen für FlightHub 2. In Hub 2 importieren, als Projekt-Admin den Ordner an Pilot 2 verteilen. RC: Pilot 2 im selben Projekt anmelden, Annotationen-Layer. Kein Live.", urlUnlock: ["ADMIN", "HUB"] },
        { icon: "hub2send", name: "Nach Hub 2 senden", text: "Nur mit URL ?ADMIN oder ?HUB. Optionaler einmaliger Push (Organization Key + Projekt-UUID auf dem Server). Danach trotzdem Ordner verteilen. Kein Live.", urlUnlock: ["ADMIN", "HUB"] },
        { icon: "mbtiles", name: "DJI Layer", text: "MBTiles auf die RC: Pilot 2 → Profil → Karteneinstellungen → Custom Layer. Sieht aus wie die Zeichnung. Kein FlightHub-Konto nötig. Kein Live." },
        { icon: "map", name: "SwissTopo", text: "In map.geo.admin.ch öffnen." },
        { icon: "pdf", name: "PDF", text: "Massstab Ansicht / 1:10'000 / 1:25'000 / 1:50'000, Druck 100 %." },
        { icon: "link", name: "Ansicht-Link", text: "Speichert den Stand; erneutes Klicken aktualisiert denselben Link (kein Broadcast zu DJI)." }
      ]
    },
    {
      id: "map.save",
      pages: ["map"],
      views: [],
      roles: ["editor", "kos"],
      icon: "save",
      title: "Zeichnung speichern / laden",
      what: "Unter Objektliste → Zeichnungen: Name, speichern, laden, löschen. Speicherfrist 30/90 Tage.",
      whatUnlock: "Mit URL ?ADMIN zusätzlich: Overlay als Datei auf dem Gerät speichern und laden (PC/USB).",
      urlUnlock: ["ADMIN"],
      tools: [
        { icon: "save", name: "Speichern", text: "Aktuelle Objekte unter einem Namen ablegen." },
        { icon: "kmlLoad", name: "Laden", text: "Gespeicherte Zeichnung zurückholen." },
        { icon: "save", name: "Datei speichern", text: "Nur mit URL ?ADMIN. Overlay lokal als .rkrkoverlay speichern.", urlUnlock: ["ADMIN"] },
        { icon: "kmlLoad", name: "Datei laden", text: "Nur mit URL ?ADMIN. Overlay von einer lokalen Datei laden.", urlUnlock: ["ADMIN"] }
      ]
    },
    {
      id: "map.title_scale",
      pages: ["map"],
      views: [],
      roles: ["editor", "kos", "viewer"],
      icon: "text",
      title: "Projekttitel & Massstab",
      what: "Titel: Doppelklick oben auf der Karte. Massstab unten links: Doppelklick (z. B. 1:15'000). Beide auf Karte, PDF und geo.admin.",
      tools: [
        { icon: "text", name: "Titel", text: "Doppelklick auf den Kartentitel." },
        { icon: "ruler", name: "Massstab", text: "Doppelklick auf die Leiste unten links." }
      ]
    },
    {
      id: "map.flight",
      pages: ["map"],
      views: ["map.overview", "map.polygon"],
      roles: ["editor", "kos"],
      icon: "pfeil",
      title: "Flugpfad (optional)",
      what: "Sektion Flugpfad: Startpunkt und Polygon wählen, Variante/Parameter setzen, berechnen. Abdeckung optional einblenden.",
      tools: [
        { icon: "point", name: "Start wählen", text: "Aktuellen Startpunkt für den Flug setzen." },
        { icon: "polygon", name: "Polygon wählen", text: "Aktuelles Polygon als Flugfeld." },
        { icon: "pfeil", name: "Berechnen", text: "Wenn Start und Polygon gesetzt sind." }
      ]
    },
    {
      id: "map.broadcast",
      pages: ["map"],
      views: ["map.live", "map.kos", "map.overview"],
      roles: ["kos", "kos_host", "kos_ready"],
      icon: "live",
      title: "Live-Broadcast starten",
      what: "Nur mit URL ?KOS, ?ADMIN, ?INSTR oder code=. Knöpfe oben rechts (links neben der Hilfe ?).",
      tools: [
        { icon: "live", name: "Broadcast", text: "Start/Stop. Rot = live. Orange = fortsetzen möglich." },
        { icon: "link", name: "Link kopieren", text: "Share-URL in die Zwischenablage." },
        { icon: "grid", name: "QR", text: "QR-Code für Sync-Geräte / Handys." }
      ]
    },
    {
      id: "map.announce",
      pages: ["map"],
      views: ["map.live", "map.kos"],
      roles: ["kos", "kos_host", "kos_sub"],
      icon: "text",
      title: "Broadcast-Nachricht",
      what: "Knopf «Nachricht» in der Live-Leiste. Empfänger müssen die Meldung bestätigen.",
      tools: [
        { icon: "text", name: "Schreiben", text: "Text senden an alle verbundenen Geräte." },
        { icon: "fix", name: "Bestätigen", text: "Auf Empfänger-Geräten Pflicht-Bestätigung." }
      ]
    },
    {
      id: "map.follow",
      pages: ["map"],
      views: ["map.live", "map.kos", "map.viewer"],
      roles: ["kos", "kos_host", "kos_sub", "viewer"],
      icon: "show",
      title: "Folge dem Master / Follow-Fokus",
      what: "Kreis-Knopf oben rechts (links neben Hilfe). Weiss ◎ folgt, rot ● eigener Zoom, grün ◉ Fokus-Master.",
      tools: [
        { icon: "show", name: "◎ Folgen", text: "Ausschnitt folgt dem Fokus-Master bzw. Broadcast." },
        { icon: "show", name: "● Losgelöst", text: "Eigener Zoom — Klick: wieder folgen." },
        { icon: "show", name: "◉ Master", text: "Sie steuern den Ausschnitt für alle (nicht auf reinem Sync-Gerät)." }
      ],
      steps: [
        { icon: "show", text: "Klick-Zyklus Editor: rot → weiss → grün." },
        { icon: "show", text: "Sync-Gerät: nur folgen ↔ eigener Zoom, kein Fokus übernehmen." }
      ]
    },
    {
      id: "map.multiedit",
      pages: ["map"],
      views: ["map.live", "map.kos"],
      roles: ["kos", "kos_host", "kos_sub"],
      icon: "live",
      title: "Multi-Edit (KOS)",
      what: "Mehrere Editoren am gleichen Live-Stand. Master = KOS. Objekte können gesperrt sein, während jemand sie bearbeitet.",
      tools: [
        { icon: "live", name: "Co-Editor-Link", text: "Share-URL mit edit + KOS/ADMIN/INSTR." },
        { icon: "draw", name: "Gleichzeitig zeichnen", text: "Eigene Objekte anlegen; fremde Locks beachten." },
        { icon: "live", name: "Editoren-Badge", text: "Master: Subs auf Share-only setzen oder trennen." }
      ]
    },
    {
      id: "map.viewer_help",
      pages: ["map"],
      views: ["map.viewer"],
      roles: ["viewer"],
      icon: "show",
      title: "Zuschauen ohne Menü",
      what: "Keine Seitenleiste. Karte vollflächig. Hilfe ? ganz rechts, Follow links daneben.",
      tools: [
        { icon: "show", name: "Folge dem Master", text: "Ausschnitt der Leitung folgen oder eigenen Zoom." },
        { icon: "live", name: "Nachrichten", text: "Eingehende Broadcast-Texte bestätigen." }
      ]
    },
    {
      id: "map.help_btn",
      pages: ["map"],
      views: [],
      roles: ["editor", "kos", "viewer"],
      icon: "live",
      title: "Hilfe-Knopf ?",
      what: "Immer ganz oben rechts. Taste F1 oder ?. Inhalt folgt Objekttyp und KOS-/Zuschauer-Rolle.",
      tools: [{ icon: "live", name: "?", text: "Aktuelle Sicht + Werkzeuge mit denselben Icons wie in der Leiste." }]
    }
  ]
};
