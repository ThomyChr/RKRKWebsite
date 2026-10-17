/**
 * Internal help catalog for mapII / mapll.
 * Views + required inputs + all user-facing functions.
 */
window.RKRK_HELP_CATALOG = {
  pages: {
    editor: {
      title: 'Feld-Editor',
      file: 'rkrk_feld_editor.php',
      summary: 'Feld anlegen oder laden, Flugparameter setzen, Trajektorie rechnen und KMZ/UAV exportieren.'
    },
    analyse: {
      title: 'Algo-Analysator',
      file: 'analyse_ui.php',
      summary: 'Manuellen Flugpfad mit Algorithmus vergleichen, Abdeckung und Metriken pruefen, Pfadoptimierung steuern.'
    },
    rc: {
      title: 'Feld-Editor RC',
      file: 'rkrk_feld_editor_rc.php',
      summary: 'Feldplanung auf der Fernsteuerung: Flaeche, Start, Flugpfad, 4 - Prüfen, Speichern, Daten. Gleicher Feldkatalog wie der grosse Editor.'
    }
  },
  views: {
    'editor.overview': {
      page: 'editor',
      title: 'Uebersicht Editor',
      use: 'Feldplanung in sechs Schritten, gleich wie auf der Fernsteuerung: Flaeche, Start, Flugpfad, 4 - Prüfen, Speichern, Daten. Die GIS-Nummer folgt erst aus der gezeichneten Flaeche.',
      inputs: [
        { name: 'Feld', where: 'Liste links oder Schnellzugriff', required: true },
        { name: 'Feldrand', where: 'Karte / Flaechendaten', required: true },
        { name: 'Startpunkt', where: 'Karte oder Startplatz', required: true }
      ]
    },
    'editor.field_select': {
      page: 'editor',
      title: 'Feldauswahl',
      use: 'Bestehendes Feld laden oder Schnellslot setzen.',
      inputs: [
        { name: 'Suchtext', where: 'Feld suchen', required: false },
        { name: 'Feldklick', where: 'Feldliste', required: true }
      ]
    },
    'editor.draw': {
      page: 'editor',
      title: 'Feldrand zeichnen',
      use: 'Neues Polygon als Feldgrenze setzen.',
      inputs: [
        { name: 'Mindestens 3 Eckpunkte', where: 'Linksklick in die Karte', required: true },
        { name: 'Abschluss', where: 'Doppelklick auf letzten Punkt', required: true }
      ]
    },
    'editor.edit_polygon': {
      page: 'editor',
      title: 'Feldrand bearbeiten',
      use: 'Ecken verschieben, einfuegen oder loeschen.',
      inputs: [
        { name: 'Vorhandener Feldrand', where: 'Karte', required: true },
        { name: 'Ziehen', where: 'Eckpunkt', required: false },
        { name: '+ zwischen Kanten', where: 'Mittelpunkt-Griff', required: false },
        { name: 'Doppelklick', where: 'Punkt loeschen', required: false }
      ]
    },
    'editor.edit_path': {
      page: 'editor',
      title: 'Flugpfad bearbeiten',
      use: 'Wegpunkte des aktuellen Pfads verschieben oder einfuegen.',
      inputs: [
        { name: 'Flugweg mit >= 2 Punkten', where: 'Pfad berechnen oder XML', required: true },
        { name: 'Ziehen / + / Doppelklick', where: 'Karte', required: false }
      ]
    },
    'editor.start': {
      page: 'editor',
      title: 'Startpunkt setzen',
      use: 'Startplatz fuer Anflug und sichere Starthoehe.',
      inputs: [
        { name: 'Klick in die Karte', where: 'Moeglichst ausserhalb des Feldes', required: true }
      ]
    },
    'editor.mission': {
      page: 'editor',
      title: 'Missions-Einstellungen',
      use: 'Parameter fuer Trajektorie und KMZ (DJI Pilot 2).',
      inputs: [
        { name: 'KMZ-Parameter aktiv', where: 'Checkbox oben', required: true },
        { name: 'Drohnentyp', where: 'Missions-Box', required: true },
        { name: 'Flughoehe (m)', where: 'Missions-Box', required: true },
        { name: 'Geschwindigkeit (m/s)', where: 'Missions-Box', required: true },
        { name: 'Ueberlappung (%)', where: 'Missions-Box', required: true },
        { name: 'Max. Randfaktor (%)', where: 'Missions-Box', required: true },
        { name: 'Flugrichtung', where: '-1 = automatisch', required: false },
        { name: 'Endaktion / Gier / Wegpunkttyp', where: 'KMZ-Export', required: false }
      ]
    },
    'editor.coverage': {
      page: 'editor',
      title: 'Abdeckung & Anzeige',
      use: 'Suchabdeckung und nicht abgedeckte Flaechen in der Routenoptimierung. Kleine Richtungswechsel bekommen einen aeusseren Kreisausschnitt, sobald die Luecke sichtbar ist.',
      inputs: [
        { name: 'Suchabdeckung', where: 'Schritt 3 Flugpfad', required: true },
        { name: 'Nicht abgedeckte Flaechen', where: 'Schritt 3 Flugpfad, unter der Suchabdeckung', required: false },
        { name: 'Flughoehe und Drohnentyp', where: 'Schritt 1 Flaeche', required: true }
      ]
    },
    'editor.compute': {
      page: 'editor',
      title: 'Pfadberechnung',
      use: 'Algorithmus erzeugt oder aktualisiert den Flugweg.',
      inputs: [
        { name: 'FeldID', where: 'geladenes Feld', required: true },
        { name: 'Feldrand + Startpunkt', where: 'Karte', required: true },
        { name: 'Berechnungsmodus', where: 'einfach / umfaenglich', required: true },
        { name: 'Hoehe, Overlap, Randfaktor, Richtung', where: 'Missions-Einstellungen', required: true }
      ]
    },
    'editor.export': {
      page: 'editor',
      title: 'Export',
      use: 'UAV-JSON oder DJI-KMZ fuer den Flug erzeugen.',
      inputs: [
        { name: 'FeldID, Name, Start, Flaeche, Flugweg', where: 'Felddaten / Karte', required: true },
        { name: 'KMZ-Parameter', where: 'Missions-Einstellungen', required: true }
      ]
    },
    'editor.fielddata': {
      page: 'editor',
      title: 'Felddaten prüfen',
      use: 'Schritt 6 der Feldplanung. Stammdaten prüfen, bevor ein neues Feld in den Katalog und die Missionsstruktur geschrieben wird.',
      inputs: [
        { name: 'FeldID *', where: 'Felddaten-Box', required: true },
        { name: 'Feldname *', where: 'Felddaten-Box', required: true },
        { name: 'Startplatz lon,lat', where: 'Felddaten-Box', required: true },
        { name: 'Flaechendaten lon,lat ...', where: 'Felddaten-Box', required: true }
      ]
    },
    'rc.list': {
      page: 'rc',
      title: 'Feldliste',
      use: 'Bestehendes Feld oeffnen oder ein neues starten. Die Karte eines neuen Feldes springt auf die Position der Fernsteuerung, sonst auf das Zentrum von Knonau.',
      inputs: [
        { name: 'Suchtext', where: 'Nummer oder Name', required: false },
        { name: 'Feldtipp oder Neu', where: 'Liste', required: true }
      ]
    },
    'rc.ring': {
      page: 'rc',
      title: '1 Flaeche',
      use: 'Umrandung tippen. Ein Tipp auf einen Punkt waehlt ihn, langes Druecken oder Ziehen verschiebt ihn. Ein + auf der Kante oder der Knopf Punkt fuegt einen Eckpunkt ein, Loeschen entfernt ihn.',
      inputs: [
        { name: 'Mindestens 3 Eckpunkte', where: 'Tipp in die Karte', required: true }
      ]
    },
    'rc.start': {
      page: 'rc',
      title: '2 Start',
      use: 'Startpunkt tippen. Parkplatz nur setzen, wenn er sich vom Startpunkt unterscheidet. Start und Parkplatz lassen sich wie die Eckpunkte verschieben und loeschen.',
      inputs: [
        { name: 'Startpunkt', where: 'Tipp in die Karte', required: true },
        { name: 'Parkplatz', where: 'Parkplatz setzen, nur wenn ungleich Start', required: false }
      ]
    },
    'rc.route': {
      page: 'rc',
      title: '3 Flugpfad',
      use: 'Die Route wird vom Server gerechnet. Suchabdeckung, Hoehe auf der Route, Anflug und Flugrichtung gehoeren zu diesem Schritt. Wegpunkte der Route lassen sich waehlen, verschieben, einfuegen und loeschen.',
      inputs: [
        { name: 'Umrandung und Start', where: 'Schritte 1 und 2', required: true },
        { name: 'Flughoehe', where: 'Hoehe plus und minus', required: true },
        { name: 'Flugrichtung', where: 'Regler oder Optimieren, -1 ist der Vorschlag', required: false }
      ]
    },
    'rc.wp': {
      page: 'rc',
      title: '4 Wegpunkt',
      use: 'Ein Wegpunkt nach dem anderen. Die Hoehe gilt nur fuer den gewaehlten Punkt. In der Klammer steht die Hoehe ab Startpunkt.',
      inputs: [
        { name: 'Wegpunkt', where: 'Punkt zurueck und vor', required: true },
        { name: 'Flughoehe dieses Punkts', where: 'Hoehe plus und minus, 20 bis 120 m', required: true }
      ]
    },
    'rc.name': {
      page: 'rc',
      title: '5 Speichern',
      use: 'Feldname setzen und speichern. Fehlen Jagdgebiet oder Gemeinde, kommt zuerst eine Bestaetigung. Ein neues Feld bekommt die Freigabe NEU. Ueberarbeiten schreibt dasselbe Katalogfeld fort.',
      inputs: [
        { name: 'Feldname', where: 'Eingabe links', required: true },
        { name: 'Umrandung, Start und Route', where: 'vorherige Schritte', required: true }
      ]
    },
    'rc.check': {
      page: 'rc',
      title: '6 Daten',
      use: 'Zusaetzliche Feldkatalogdaten nach dem Speichern: Ort, Drohne, Hoehe, Wegpunkte, Distanz. Zeile: KMZ herunterladen, Abschliessen speichert dasselbe Feld nochmals und erneuert die Bilder, Fertig schliesst den Lauf. Zurueck heisst Abbrechen.',
      inputs: [
        { name: 'Gespeichertes Feld', where: 'nach Speichern', required: true }
      ]
    },
    'rc.field': {
      page: 'rc',
      title: 'Feld ansehen',
      use: 'Geladenes Katalogfeld auf der Karte. Ueberarbeiten oeffnet dieselben sechs Schritte und speichert spaeter in denselben Katalogeintrag.',
      inputs: [
        { name: 'Feld aus der Liste', where: 'Feldliste', required: true }
      ]
    },
    'rc.drone': {
      page: 'rc',
      title: 'Drohne',
      use: 'Drohnentyp einmal waehlen. Die Wahl bleibt auf dieser Fernsteuerung gespeichert.',
      inputs: [
        { name: 'Drohnentyp', where: 'Liste Drohne', required: true }
      ]
    },
    'analyse.overview': {
      page: 'analyse',
      title: 'Uebersicht Analyse',
      use: 'Feld laden, Planner waehlen, Analyse starten, Original vs. Algorithmus vergleichen.',
      inputs: [
        { name: 'Feld', where: 'Suche / Schnellzugriff', required: true },
        { name: 'Planner', where: 'PHP schnell oder Python praeziser', required: true }
      ]
    },
    'analyse.run': {
      page: 'analyse',
      title: 'Analyse starten',
      use: 'Algorithmus gegen den gespeicherten manuellen Pfad rechnen.',
      inputs: [
        { name: 'FeldID', where: 'Feldwahl', required: true },
        { name: 'Planner + Modellversion', where: 'linke Leiste', required: true },
        { name: 'XML-Felddaten mit Flugweg', where: 'RKRKFelder.xml', required: true }
      ]
    },
    'analyse.coverage': {
      page: 'analyse',
      title: 'Abdeckung vergleichen',
      use: 'W/S, Luecken, Doppelueberflug und Charakteristik lesen.',
      inputs: [
        { name: 'Analyse gelaufen', where: 'Button Analyse starten', required: true },
        { name: 'Flugpfad sichtbar', where: 'Anzeigekontrolle', required: true },
        { name: 'Abdeckung an', where: 'Anzeigekontrolle', required: true }
      ]
    },
    'analyse.metrics': {
      page: 'analyse',
      title: 'Metriken & Schritte',
      use: 'Entscheidung, Impact, Schritt-Resultate und ausfuehrbares Modell pruefen.',
      inputs: [
        { name: 'Analyse gelaufen', where: 'linke Leiste', required: true },
        { name: 'Analyse-Boxen ein', where: 'Analyse-Boxen rechts', required: false }
      ]
    }
  },
  functions: [
    {
      id: 'nav.pages',
      pages: ['editor', 'analyse'],
      views: ['editor.overview', 'analyse.overview'],
      title: 'Seitenwechsel Editor / Analyse',
      what: 'Zwischen Feld-Editor (anlegen, Parameter, Export) und Analysator (Vergleich, Optimierung) wechseln.',
      inputs: [{ name: 'Link Analyse bzw. Feld-Editor', where: 'oben links', required: false }]
    },
    {
      id: 'editor.quickslot',
      pages: ['editor', 'analyse'],
      views: ['editor.field_select', 'analyse.overview'],
      title: 'Schnellzugriff Felder',
      what: 'Vier Slots fuer haeufige Felder. Klick laedt, Alt+Klick speichert das aktuelle Feld in den Slot.',
      inputs: [{ name: 'Aktuelles Feld', where: 'fuer Alt+Klick', required: false }]
    },
    {
      id: 'editor.search',
      pages: ['editor'],
      views: ['editor.field_select'],
      title: 'Feld suchen und laden',
      what: 'Liste nach ID oder Name filtern, Eintrag klicken laedt Geometrie und Flugweg auf die Karte.',
      inputs: [{ name: 'Suchtext', where: 'Feld suchen', required: false }]
    },
    {
      id: 'editor.draw_polygon',
      pages: ['editor'],
      views: ['editor.draw'],
      title: 'Feldrand zeichnen',
      what: 'Ohne bestehendes Polygon: Eckpunkte setzen, Doppelklick schliesst. Danach Bearbeiten aktiv. RPAS, Elektrische Anlagen, Luftfahrthindernisse, DABS und die Flugzone lassen sich einschalten und gegen die Umrandung pruefen.',
      inputs: [
        { name: '3+ Punkte', where: 'Karte', required: true },
        { name: 'Button Feldrand bearbeiten', where: 'Bedienmenue', required: true }
      ]
    },
    {
      id: 'editor.edit_polygon',
      pages: ['editor'],
      views: ['editor.edit_polygon'],
      title: 'Feldrandpunkte aendern',
      what: 'Punkte ziehen, + auf der Kante einfuegen, Doppelklick loescht. Undo macht den letzten Schritt rueckgaengig.',
      inputs: [{ name: 'Gezeichnetes oder geladenes Polygon', where: 'Karte', required: true }]
    },
    {
      id: 'editor.edit_path',
      pages: ['editor'],
      views: ['editor.edit_path'],
      title: 'Flugpfad manuell justieren',
      what: 'Im Schritt Flugpfad: Wegpunkte ziehen, + setzt einen neuen, Doppelklick loescht. Die Route wird dabei nicht neu gerechnet. Neu gerechnet wird sie nur mit Route neu rechnen, Feldpfad rechnen oder Flugpfad neu rechnen. Ein Klick auf den Punkt oeffnet die Wegpunktwerte erst in Schritt 4.',
      inputs: [{ name: 'Flugweg', where: 'Schritt 3 Flugpfad', required: true }]
    },
    {
      id: 'editor.start',
      pages: ['editor'],
      views: ['editor.start'],
      title: 'Startpunkt setzen oder verschieben',
      what: 'Klick setzt den Startplatz. Ziehen verschiebt ihn. Abheben am Start, dann zum ersten Feldpunkt. Die Route wird dabei nicht neu gerechnet. Neu gerechnet wird sie nur mit Route neu rechnen, Feldpfad rechnen oder Flugpfad neu rechnen.',
      inputs: [{ name: 'Kartenklick', where: 'Startpunkt-Button danach Karte', required: true }]
    },
    {
      id: 'editor.undo',
      pages: ['editor'],
      views: ['editor.draw', 'editor.edit_polygon', 'editor.edit_path'],
      title: 'Undo',
      what: 'Letzten Bearbeitungsschritt (Zeichnen, Verschieben, Loeschen) zuruecknehmen.',
      inputs: []
    },
    {
      id: 'editor.clear',
      pages: ['editor'],
      views: ['editor.edit_polygon'],
      title: 'Feldrand loeschen',
      what: 'Polygon von der Karte entfernen. Speichern schreibt das in die XML, solange nicht neu gezeichnet.',
      inputs: []
    },
    {
      id: 'editor.find_place',
      pages: ['editor'],
      views: ['editor.overview'],
      title: 'Ort suchen',
      what: 'Lupe oben rechts: Ortsname, Karte zentriert darauf (fuer neues Feld).',
      inputs: [{ name: 'Ortsname', where: 'Dialog der Lupe', required: true }]
    },
    {
      id: 'editor.compute',
      pages: ['editor'],
      views: ['editor.compute'],
      title: 'Flugpfad berechnen',
      what: 'PHP-Planner erzeugt den neuen Flugweg: Startpunkt (0 m AGL), senkrecht auf Flughoehe, dann zum naechsten Feld-Wegpunkt, danach Streifen. Parameter: Hoehe, FOV, Overlap, Randfaktor, Richtung. Modus einfach/umfaenglich aendert die Korrekturtiefe.',
      inputs: [
        { name: 'FeldID', where: 'geladenes Feld', required: true },
        { name: 'Feldrand + Start', where: 'Karte', required: true },
        { name: 'Flughoehe, Overlap, Randfaktor', where: 'Missions-Einstellungen', required: true },
        { name: 'Berechnungsmodus', where: 'Anzeigekontrolle', required: false },
        { name: 'Offene Flaechen forcieren', where: 'Anzeigekontrolle', required: false }
      ]
    },
    {
      id: 'editor.new_field',
      pages: ['editor'],
      views: ['editor.fielddata'],
      title: 'Neues Feld anlegen',
      what: 'Schritt 1 Flaeche: bestehendes Feld laden oder neues Feld starten, Ort suchen, Umrandung zeichnen. Die GIS-Nummer wird erst gelesen, wenn die Flaeche gezeichnet ist. Fehlt sie im Katalog, schlaegt die CadastralWebMap die Parzelle im Feldinnern vor. Jagdgebiet, Gemeinde und die naechste freie Katalognummer folgen aus der Umrandung. Schritt 5 Speichern schreibt das Feld, Schritt 6 Daten zeigt die zusaetzlichen Feldkatalogdaten.',
      inputs: [
        { name: 'Bestehendes Feld oder Neues Feld', where: 'Schritt 1 Flaeche', required: true },
        { name: 'GIS-Nummer', where: 'Schritt 1 Flaeche, nach der Umrandung', required: true },
        { name: 'Feldname', where: 'Schritt 5 Speichern', required: true },
        { name: 'Umrandung und Startpunkt', where: 'Schritte 1 und 2', required: true }
      ]
    },
    {
      id: 'editor.save',
      pages: ['editor'],
      views: ['editor.fielddata'],
      title: 'Aktuelles Feld speichern',
      what: 'Schreibt Geometrie, Flugweg, GIS-Nummer und Stammdaten. Ohne GIS-Nummer wird nicht gespeichert und nicht exportiert. Ein neues Feld landet in der bestehenden Missionsstruktur des Jagdgebiets.',
      inputs: [
        { name: 'FeldID + Name', where: 'Felddaten-Box', required: true },
        { name: 'GIS-Nummer', where: 'Schritt 1 Flaeche, nach der Umrandung', required: true },
        { name: 'Startplatz + Flaechendaten', where: 'Felddaten-Box', required: true }
      ]
    },
    {
      id: 'editor.mission',
      pages: ['editor'],
      views: ['editor.mission', 'editor.waypoint'],
      title: 'Missions- / KMZ-Parameter',
      what: 'Flughoehe, Hoehenmodus und sichere Starthoehe stehen in Schritt 1 Flaeche. Die Flugrichtung ist im Flugpfad ein Regler: Vorschlag oder ein fester Winkel, Zurueck zum Vorschlag setzt wieder automatisch. Die uebrigen Missionswerte (Speed, Ueberlappung, Rand, Endaktion, Gier, Wegpunkttyp) stehen im Expertenmodus unter Flugroute in Schritt 4 Wegpunkt. Wenn KMZ-Parameter inaktiv sind, gelten DJI-Referenz-Defaults. Einfacher Modus gilt gleich auf DESK und MOB, nur die Oberflaeche unterscheidet sich. Standardparameter Einfacher Modus: Direkt zum Startpunkt fliegen: Nein. Geschwindigkeit: 4.5 m/s. Endaktion: Aufgabe beenden. Gierrichtung des Fluggeraetes: Richtung zum naechsten Wegpunkt. Wegpunkt Typ: Koordinierte Drehung, Ueberspringt den Wegpunkt. Standard-Wendedistanz: 5 m. Sichere Startflughoehe: 20 m. Startgeschwindigkeit aktiv: Nein. Gimbalsteuerung: Manuell. Flughoehenmodus: ALT (Relativ zum Startpunkt). Gierrichtung: Richtung zum naechsten Wegpunkt zeichnet ein Dreieck, breite Basis in Flugrichtung zum naechsten Wegpunkt, Spitze auf dem Punkt. Manuell ohne Dreieck. Definiert zeichnet das Dreieck entlang des Richtungs-Winkels, der Winkel bleibt sichtbar und ist bei useMissionYawDirection gesperrt. Fokus POI ist nicht unterstuetzt und zeichnet kein Dreieck. Schenkachse sperren zeichnet kein Dreieck, sondern ein rotes X. useMissionYawDirection entspricht wpml:useGlobalHeadingParam, wpml:waypointHeadingMode ist nur der Giermodus. Mit ?DEBUG zeigt das DESK-Expertenformular unter dem Label in 7px den verknuepften JSON/KMZ-Namen, und der KMZ-Export liefert zusaetzlich die ungezippte template-KML unter dem KMZ-Dateinamen mit Endung .kml.',
      inputs: [
        { name: 'Drohnentyp und Flughoehe', where: 'Schritt 1 Flaeche', required: true },
        { name: 'Flugrichtung', where: 'Schritt 3 Flugpfad, Regler oder Vorschlag', required: false },
        { name: 'Uebrige Missionswerte', where: 'Schritt 5, Reiter Flugroute, Expertenmodus', required: false }
      ]
    },
    {
      id: 'editor.waypoint',
      pages: ['editor'],
      views: ['editor.waypoint', 'editor.edit_path'],
      title: 'Wegpunktparameter',
      what: 'Schritt 5 im Menue. Die Nummer waehlt den Wegpunkt. Einfacher Modus: nur die Flughoehe dieses Punkts. Expertenmodus: alle Punktwerte, dazu Vorheriger, Naechster und Alle Wegpunkte. Punkt speichern uebernimmt die Werte dieses Punkts, Abbrechen setzt sie auf den Stand beim Oeffnen zurueck, Loeschen entfernt den Punkt. Route folgen uebernimmt Hoehe, Speed, Wegpunkttyp und Gier aus der Mission.',
      inputs: [
        { name: 'Wegpunktnummer', where: 'Schritt 5, Nummernleiste', required: true },
        { name: 'Flughoehe', where: 'Einfacher Modus, nur dieser Punkt', required: true },
        { name: 'Weitere Punktwerte', where: 'Expertenmodus', required: false },
        { name: 'Punkt speichern', where: 'unter dem Formular', required: true }
      ]
    },
    {
      id: 'editor.display',
      pages: ['editor'],
      views: ['editor.coverage', 'editor.edit_path'],
      title: 'Anzeigekontrolle',
      what: 'Nur mit ADMIN in der Adresse. Im Feld-Editor gibt es einen Flugpfad. Suchabdeckung und nicht abgedeckte Flaechen schaltet Schritt 4; eine eigene Ueberlappung gibt es dort nicht. Hoehen liegen auf der Route oder im Feld. In der Anzeigekontrolle bleiben Ueberlappung, Korrekturpunkte und Wendekurven zusaetzlich schaltbar.',
      inputs: [
        { name: 'Flugpfad', where: 'Anzeigekontrolle, nur ADMIN', required: true }
      ]
    },
    {
      id: 'editor.overlays',
      pages: ['editor'],
      views: ['editor.overview'],
      title: 'Layersteuerung',
      what: 'Schwebende Box wie im Overlay-Editor: Kartenansicht Karte, Satellit, S/W oder OSM, darunter SwissTopo-Overlays mit Deckkraft. Optional Strom, Grenzen, Jagdbanngebiete AuLaV, RPAS, UAS, Wohngebiete nach AuLaV, swissALTI3D und swissSURFACE3D Relief, DABS, Hindernisse, Grundbuch, Naturschutz, Seilbahnen, Strassennamen, Wanderwege. AuLaV und beide Relief-Ebenen sind standard aus.',
      inputs: [{ name: 'Kartenbasis', where: 'Swisstopo-Box', required: false }]
    },
    {
      id: 'editor.export_uav',
      pages: ['editor'],
      views: ['editor.export'],
      title: 'UAV JSON exportieren',
      what: 'Missions-JSON herunterladen. lineString ist der Flugpfad. polygonLineString und die GeoJSON-Linie sind die offene Feldumrandung, ohne den Flugpfad und ohne wiederholten Schlusspunkt. Der Dateiname ist Katalognummer_Feldname_GIS_Flughoehe_Pilot_Jahr.',
      inputs: [
        { name: 'Feld, GIS-Nummer und Flugweg', where: 'Flaeche, nach der Umrandung, und Karte', required: true }
      ]
    },
    {
      id: 'editor.export_kmz',
      pages: ['editor'],
      views: ['editor.export'],
      title: 'DJI KMZ exportieren',
      what: 'Pilot-2-KMZ (template.kml + waylines.wpml). Nutzt interne DJI-Spezifikation fuer Enums. Route folgen am Punkt setzt useGlobalHeight/Speed/Heading/Turn; sonst die gespeicherten Punktwerte.',
      inputs: [
        { name: 'FeldID, Start, Flaeche, Flugweg', where: 'Felddaten', required: true },
        { name: 'Missions-Einstellungen', where: 'KMZ-Parameter', required: true }
      ]
    },
    {
      id: 'editor.setup_boxes',
      pages: ['editor'],
      views: ['editor.overview'],
      title: 'Boxen ein-/ausblenden',
      what: 'Zahnrad: Bedienmenue, Overlays, Flugvorgaben, Feldcharakteristik, Felddaten-Box.',
      inputs: []
    },
    {
      id: 'editor.characteristics',
      pages: ['editor'],
      views: ['editor.coverage', 'editor.compute'],
      title: 'Feldcharakteristik',
      what: 'Pfadlaenge, Steigen/Sinken, Flugzeit aus aktuellem Pfad und Speed.',
      inputs: [{ name: 'Berechneter oder geladener Pfad', where: 'Karte', required: true }]
    },
    {
      id: 'analyse.run',
      pages: ['analyse'],
      views: ['analyse.run', 'analyse.overview'],
      title: 'Analyse starten',
      what: 'Laedt das XML-Feld, rechnet den Algorithmus (PHP oder Python), vergleicht mit dem gespeicherten manuellen Flugweg, fuellt Metriken und Karte.',
      inputs: [
        { name: 'Feld', where: 'Suche oder Slot', required: true },
        { name: 'Planner', where: 'PHP / Python', required: true },
        { name: 'Modellversion', where: 'Dropdown', required: false }
      ]
    },
    {
      id: 'analyse.planner',
      pages: ['analyse'],
      views: ['analyse.run'],
      title: 'Planner und Modell',
      what: 'PHP = schnell im Browser-Server. Python = praeziser (StdLib). Modellversion waehlt Parameter-Satz. Settings reset setzt UI auf Defaults.',
      inputs: [
        { name: 'Planner', where: 'linke Leiste', required: true },
        { name: 'Modellversion', where: 'linke Leiste', required: false }
      ]
    },
    {
      id: 'analyse.compare',
      pages: ['analyse'],
      views: ['analyse.coverage'],
      title: 'Original vs. neuer Flugplan',
      what: 'Beide Pfade einblendbar. Abdeckung kann auf Original oder Algorithmus bezogen werden. Grundmuster, Sektorrichtungen, Korrekturpunkte zeigen, wie der Algo arbeitet.',
      inputs: [
        { name: 'Analyse-Resultat', where: 'nach Start', required: true },
        { name: 'Pfad-Checkboxen', where: 'Anzeigekontrolle', required: true }
      ]
    },
    {
      id: 'analyse.coverage',
      pages: ['analyse'],
      views: ['analyse.coverage'],
      title: 'Abdeckungs- und W/S-Anzeige',
      what: 'Feldcharakteristik: Abdeckung %, Doppelueberflug, HFOV-Breite W, DFOV am Drehpunkt, Distanz, Hoehen-Deltas. Box h/W/S erklaert Spurabstand.',
      inputs: [
        { name: 'Abdeckung an + sichtbarer Pfad', where: 'Anzeigekontrolle', required: true },
        { name: 'Referenzflughoehe optional', where: 'Analysemodus', required: false }
      ]
    },
    {
      id: 'analyse.boxes',
      pages: ['analyse'],
      views: ['analyse.metrics'],
      title: 'Analyse-Textboxen',
      what: 'Systemstatus, Spezialfaelle, Feldbewertung, Metriken, Impact, effektive Parameter, Schritt-Resultate, ausfuehrbares Modell. Alle an/aus.',
      inputs: [{ name: 'Analyse gelaufen', where: 'Button', required: true }]
    },
    {
      id: 'analyse.nav',
      pages: ['analyse'],
      views: ['analyse.overview'],
      title: 'Feld vor/zurueck',
      what: 'Unten rechts durch die XML-Liste blaettern. Default-Feld speichern merkt die Wahl.',
      inputs: []
    },
    {
      id: 'rc.search',
      pages: ['rc'],
      views: ['rc.list'],
      title: 'Feld suchen und oeffnen',
      what: 'Liste nach Nummer oder Name filtern. Ein Tipp oeffnet das Feld auf der Karte, mit Kennzahlen und Bildern, wenn sie vorhanden sind.',
      inputs: [{ name: 'Suchtext', where: 'Nummer oder Name', required: false }]
    },
    {
      id: 'rc.new',
      pages: ['rc'],
      views: ['rc.list', 'rc.ring'],
      title: 'Neues Feld starten',
      what: 'Neu oeffnet die sechs Schritte: Flaeche, Start, Flugpfad, 4 - Prüfen, Speichern, Daten. Ohne Geraeteposition zentriert die Karte auf Knonau, sonst auf die Position der Fernsteuerung.',
      inputs: [{ name: 'Neu', where: 'Feldliste', required: true }]
    },
    {
      id: 'rc.draw',
      pages: ['rc'],
      views: ['rc.ring'],
      title: 'Umrandung setzen',
      what: 'Tipp in die leere Karte setzt den naechsten Eckpunkt. Mindestens drei Punkte, dann Weiter zum Start. Rueckgaengig nimmt den letzten Punkt zurueck.',
      inputs: [
        { name: '3+ Eckpunkte', where: 'Karte', required: true },
        { name: 'Weiter', where: 'unten links', required: true }
      ]
    },
    {
      id: 'rc.points',
      pages: ['rc'],
      views: ['rc.ring', 'rc.start', 'rc.route', 'rc.wp'],
      title: 'Punkte verschieben, einfuegen, loeschen',
      what: 'Tipp waehlt den Punkt. Langes Druecken oder ein zweites Ziehen verschiebt ihn. Ein + auf der Kante oder der Knopf Punkt fuegt einen Punkt ein, Loeschen entfernt den gewaehlten. Bei der Umrandung und beim Start wird die Route nicht neu gerechnet. Neu gerechnet wird sie nur mit Route neu rechnen, Feldpfad rechnen oder Flugpfad neu rechnen. Ein verschobener Wegpunkt behaelt die uebrige Route.',
      inputs: [
        { name: 'Gewaehlter Punkt', where: 'Karte', required: true },
        { name: 'Punkt oder Loeschen', where: 'linke Leiste, nach der Auswahl', required: false }
      ]
    },
    {
      id: 'rc.start',
      pages: ['rc'],
      views: ['rc.start'],
      title: 'Startpunkt',
      what: 'Tipp setzt den Start. Ziehen verschiebt ihn. Weiter rechnet den Flugpfad nicht. Neu gerechnet wird er nur mit Route neu rechnen, Feldpfad rechnen oder Flugpfad neu rechnen.',
      inputs: [{ name: 'Karten-Tipp', where: 'Schritt Start', required: true }]
    },
    {
      id: 'rc.park',
      pages: ['rc'],
      views: ['rc.start'],
      title: 'Parkplatz',
      what: 'Parkplatz setzen, dann den Ort tippen. Er wird nur gespeichert, wenn er sich in sechs Nachkommastellen vom Startpunkt unterscheidet. Parkplatz entfernen loescht ihn.',
      inputs: [{ name: 'Abweichender Punkt', where: 'Schritt Start', required: false }]
    },
    {
      id: 'rc.route',
      pages: ['rc'],
      views: ['rc.route'],
      title: 'Flugpfad',
      what: 'Der Server rechnet die Streifenroute nur mit Route neu rechnen, Feldpfad rechnen oder Flugpfad neu rechnen. Hoehe plus und minus aendert die Missionshoehe und rechnet nicht neu. Optimieren dreht die Richtung um 15 Grad oder setzt sie automatisch.',
      inputs: [
        { name: 'Umrandung und Start', where: 'Schritte 1 und 2', required: true },
        { name: 'Flughoehe', where: 'Hoehe plus und minus', required: true }
      ]
    },
    {
      id: 'rc.coverage',
      pages: ['rc'],
      views: ['rc.route'],
      title: 'Suchabdeckung und Hoehe auf der Route',
      what: 'Suchabdeckung zeichnet den Streifen zur aktuellen Flughoehe. Hoehe auf der Route zeigt an jedem Punkt die Hoehe ab Startpunkt.',
      inputs: [
        { name: 'Suchabdeckung', where: 'Schritt Flugpfad', required: false },
        { name: 'Hoehe auf der Route', where: 'Schritt Flugpfad', required: false }
      ]
    },
    {
      id: 'rc.entry',
      pages: ['rc'],
      views: ['rc.route'],
      title: 'Anflug und Flugrichtung',
      what: 'Anflug nach dem Start: zweiter, vorletzter oder letzter Feldpunkt. Die Flugrichtung ist ein Regler von 0 bis 179 Grad oder der automatische Vorschlag.',
      inputs: [
        { name: 'Anflug', where: '2. / vorletzter / letzter', required: false },
        { name: 'Flugrichtung', where: 'Regler oder Optimieren', required: false }
      ]
    },
    {
      id: 'rc.waypoint',
      pages: ['rc'],
      views: ['rc.wp'],
      title: 'Wegpunkthoehe',
      what: 'Punkt zurueck und vor waehlt den Wegpunkt. Hoehe plus und minus aendert nur diesen Punkt. Die Hoehe ist immer die Differenz zum Startpunkt, Flughoehenmodus ALT, auch wenn die Missionshoehe verwendet wird.',
      inputs: [
        { name: 'Wegpunkt', where: 'Schritt Wegpunkt', required: true },
        { name: 'Flughoehe', where: '20 bis 120 m, nur dieser Punkt', required: true }
      ]
    },
    {
      id: 'rc.save',
      pages: ['rc'],
      views: ['rc.name'],
      title: 'Feld speichern',
      what: 'Speichern legt ein neues Katalogfeld an oder, nach Ueberarbeiten, schreibt das geladene Feld fort. Ein neues Feld bekommt die Freigabe NEU. Ohne Jagdgebiet oder Gemeinde muss das vorher bestaetigt werden. Die Missionsdatei und die Bilder folgen, sobald das Jagdgebiet bekannt ist.',
      inputs: [
        { name: 'Feldname', where: 'Schritt Speichern', required: true },
        { name: 'Route', where: 'Schritt Flugpfad', required: true }
      ]
    },
    {
      id: 'rc.check',
      pages: ['rc'],
      views: ['rc.check'],
      title: 'Feldkatalogdaten',
      what: 'Nach dem Speichern: Jagdgebiet, Gemeinde, Drohne, Hoehe, Wegpunkte und Distanz. Suchfeld- und Flugplanbild werden unsichtbar erzeugt. Abschliessen speichert dasselbe Feld nochmals und erneuert die Bilder. Fertig schliesst den Lauf. Zurueck heisst Abbrechen.',
      inputs: [{ name: 'Gespeichertes Feld', where: 'Schritt Daten', required: true }]
    },
    {
      id: 'rc.edit',
      pages: ['rc'],
      views: ['rc.field'],
      title: 'Feld ueberarbeiten',
      what: 'Ueberarbeiten laedt Umrandung, Start, Parkplatz, Route und Hoehe in die Schritte. Speichern aktualisiert denselben Katalogeintrag und legt kein zweites Feld an.',
      inputs: [{ name: 'Geladenes Feld', where: 'Karte', required: true }]
    },
    {
      id: 'rc.display',
      pages: ['rc'],
      views: ['rc.field', 'rc.ring', 'rc.start', 'rc.route', 'rc.wp', 'rc.check'],
      title: 'Anzeige',
      what: 'Feld einpassen, Satellit oder Karte, Relief, Route, RPAS, Strom, Hindernisse, DABS und Flugzone. Die linke Leiste laesst sich einklappen und in der Breite ziehen.',
      inputs: [{ name: 'Anzeige', where: 'Knopf unten rechts', required: false }]
    },
    {
      id: 'rc.drone',
      pages: ['rc'],
      views: ['rc.drone', 'rc.list'],
      title: 'Drohnentyp',
      what: 'Einmal waehlen. Die Fernsteuerung merkt sich den Typ. Feldkatalog gibt es fuer Mavic 3T, Matrice 4TD und Mavic 3T Debug. Matrice 4T, Matrice 3TD und Mavic 3TA haben keinen Feldkatalog. Mavic II und Matrice 30T sind aus der Auswahl entfernt. Ein neuer Katalog wird nur in der Website-Konfiguration unter Missionsbibliotheken angelegt.',
      inputs: [{ name: 'Drohnentyp', where: 'Drohne', required: true }]
    },
    {
      id: 'analyse.requirements',
      pages: ['analyse'],
      views: ['analyse.metrics'],
      title: 'Schritt-Requirements',
      what: 'Externe Seite steuert, welche Algorithmus-Schritte (Richtung, Abdeckung, Wegpunkte) gelten.',
      inputs: [{ name: 'Link Schritt-Requirements', where: 'linke Leiste', required: false }]
    }
  ]
};
