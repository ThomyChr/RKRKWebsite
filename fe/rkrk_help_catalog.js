/**
 * Internal help catalog for mapII / mapll.
 * Views + required inputs + all user-facing functions.
 */
window.RKRK_HELP_CATALOG = {
  pages: {
    editor: {
      label: 'DESK',
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
      label: 'MOB',
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
    'editor.waypoint': {
      page: 'editor',
      title: '4 - Prüfen',
      use: 'Schritt 4 auf DESK. Die Nummer waehlt den Wegpunkt. Im einfachen Modus zeigt der Punkt Flughoehe, Geschwindigkeit, Wegpunkt-Typ, Gierrichtung und Neigungswinkel setzen. Die Schalter Route folgen sind verborgen. Weicht ein Wert von der Mission ab, gilt er nur fuer diesen Punkt.',
      inputs: [
        { name: 'Wegpunktnummer', where: 'Schritt 4, Nummernleiste', required: true },
        { name: 'Flughoehe', where: 'Punktwert', required: true },
        { name: 'Geschwindigkeit, Wegpunkt-Typ, Gierrichtung, Neigung', where: 'einfacher und Expertenmodus', required: false }
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
      use: 'Suchabdeckung und nicht abgedeckte Flaechen. Neigung −90 oder nicht gesetzt: Streifen mittig auf der Route, Bodenbreite und Bodenlänge aus Flughöhe und Sichtfeld. Neigung ungleich −90: schräger Footprint in Gierrichtung, versetzt von der Route, blasser ab 150 m und Ende bei 320 m.',
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
      use: 'Die Route wird vom Server gerechnet. Suchabdeckung, Hoehe auf der Route, Anflug und Flugrichtung gehoeren zu diesem Schritt. Neigung −90 zeichnet den Streifen auf der Route, eine andere Neigung den schrägen Footprint in Blickrichtung. Wegpunkte der Route lassen sich waehlen, verschieben, einfuegen und loeschen.',
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
      use: 'Zusaetzliche Feldkatalogdaten nach dem Speichern: Ort, Drohne, Hoehe, Wegpunkte, Distanz. JSON herunterladen und KMZ herunterladen legen die Missionsdatei auf dieses Geraet. Abschliessen speichert dasselbe Feld nochmals und erneuert die Bilder, Fertig schliesst den Lauf. Zurueck heisst Abbrechen.',
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
      what: 'Schreibt Geometrie, Flugweg, GIS-Nummer und Stammdaten und setzt das Aenderungsdatum. JSON und KMZ werden neu geschrieben. Die bisherige Datei kommt in die History, ein Logeintrag wird geschrieben. Die Meldung nennt beide Dateinamen und sagt, wenn eine Datei fehlt. Ohne GIS-Nummer wird nicht gespeichert und nicht exportiert. Ein neues Feld landet in der bestehenden Missionsstruktur des Jagdgebiets.',
      inputs: [
        { name: 'FeldID + Name', where: 'Felddaten-Box', required: true },
        { name: 'GIS-Nummer', where: 'Schritt 1 Flaeche, nach der Umrandung', required: true },
        { name: 'Startplatz + Flaechendaten', where: 'Felddaten-Box', required: true }
      ]
    },
    {
      id: 'editor.mission',
      pages: ['editor'],
      views: ['editor.mission', 'editor.edit_path'],
      title: 'Missions- / KMZ-Parameter',
      what: 'Gemeinsame Vorgaben im einfachen Modus, solange das Feld keine lokalen Abweichungen speichert: Direkt zum Startpunkt fliegen Nein, Geschwindigkeit 4.5 m/s, Endaktion Aufgabe beenden, Gierrichtung Richtung zum naechsten Wegpunkt, Wegpunkttyp Koordinierte Drehung ueberspringt den Wegpunkt, Wendedistanz 5 m, sichere Startflughoehe 20 m, Startgeschwindigkeit aus, Neigung −90 und nicht gesetzt, Hoehenmodus ALT relativ zum Startpunkt. DESK Schritt 3 zeigt dabei Suchabdeckung, nicht abgedeckte Flaechen, Hoehenanzeige, Anflug, Geschwindigkeit, Startgeschwindigkeit aus und gesperrt, Missionshoehe und Flugrichtung. Endaktion, Gierrichtung der Mission, Wegpunkttyp, Wendedistanz, Gimbalsteuerung, Randfaktor und FOV stehen im Reiter Flugroute und bleiben im einfachen Modus verborgen. Sichere Startflughoehe und Hoehenmodus haengen in Schritt 1, Direkt zur Startposition in Schritt 2. MOB Schritt Flugpfad zeigt Suchabdeckung, Hoehe auf der Route, Abweichung von der Sollflughoehe, Anflug, Geschwindigkeit, Startgeschwindigkeit, Missionshoehe und Flugrichtung. Die Startgeschwindigkeit ist im einfachen Modus gesperrt. Expertenmodus gibt diese Missionsfelder frei und schaltet die Startgeschwindigkeit frei. Neigung ungleich −90 schreibt den Winkel an den Wegpunkt. −90 bleibt der Standard und schreibt in der KMZ keinen Winkel. Gierrichtung Definiert braucht den Richtungs-Winkel. Fokus POI braucht einen POI. Schwenkachse sperren haelt die Nase fest. Flugrichtung im Flugpfad: Vorschlag oder fester Winkel. Mit ?DEBUG steht unter dem Label der JSON- und KMZ-Name.',
      images: [
        { src: 'help/einfach-desk-flugpfad.png', alt: 'DESK einfacher Modus, Schritt Flugpfad', caption: 'DESK, einfacher Modus, Schritt Flugpfad: Geschwindigkeit 4.5 m/s, Startgeschwindigkeit aus.' },
        { src: 'help/experte-mob-flugpfad.png', alt: 'MOB Expertenmodus, Schritt Flugpfad', caption: 'MOB, Expertenmodus, Schritt Flugpfad. Die Startgeschwindigkeit bleibt aus, bis der Schalter an ist.' }
      ],
      inputs: [
        { name: 'Drohnentyp und Flughoehe', where: 'Schritt 1 Flaeche', required: true },
        { name: 'Flugrichtung', where: 'Schritt 3 Flugpfad, Regler oder Vorschlag', required: false },
        { name: 'Uebrige Missionswerte', where: 'Schritt 5, Reiter Flugroute, Expertenmodus', required: false }
      ]
    },
    {
      id: 'editor.waypoint',
      pages: ['editor'],
      views: ['editor.waypoint'],
      title: 'Wegpunktparameter',
      what: 'Schritt 4. Die Nummer waehlt den Wegpunkt. DESK im einfachen Modus zeigt am Punkt Flughoehe, Geschwindigkeit, Wegpunkt-Typ, Gierrichtung und Neigungswinkel setzen. Die Schalter Route folgen sind dort verborgen. Weicht ein Wert von der Mission ab, gilt er nur fuer diesen Punkt. MOB im einfachen Modus zeigt nur die Flughoehe dieses Punkts, plus und minus. Hat das Feld schon lokale Abweichungen, bleibt MOB im Expertenmodus. DESK im Expertenmodus zeigt dazu Latitude, Longitude, Verwende Missions-Hoehe, Verwende Missions-Geschwindigkeit, Route folgen, Wegpunkt-Typ, Wendedistanz bei koordinierter Drehung, Route folgen (Gierrichtung), Gierrichtung, Ausrichtung des Fluggeraets, Richtungs-Winkel bei Definiert oder Schwenkachse sperren, Neigungswinkel setzen und Neigung von −90 bis 30. POI-Knoepfe nur bei Fokus POI. Pfadmodus und Route folgen (Richtungsmodus) nur mit ?DEBUG. MOB im Expertenmodus zeigt dieselben Punktwerte ohne Latitude, Longitude und ohne eigene Wendedistanz: Verwende Missions-Hoehe, Hoehe, Verwende Missions-Geschwindigkeit, Geschwindigkeit, Route folgen (Wegpunkt-Typ), Wegpunkt-Typ, Route folgen (Gierrichtung), Gierrichtung, Winkel bei Definiert oder Schwenkachse, Ausrichtung, Neigungswinkel setzen und Neigung −90 bis 30. Route folgen uebernimmt den Missionswert und graut das Feld aus. Vorheriger, Naechster und Alle Wegpunkte. Punkt speichern uebernimmt die Werte, Abbrechen setzt den Stand beim Oeffnen zurueck, Loeschen entfernt den Punkt.',
      images: [
        { src: 'help/einfach-desk-wegpunkt.png', alt: 'DESK einfacher Modus am Wegpunkt', caption: 'DESK, einfacher Modus: Hoehe, Geschwindigkeit, Wegpunkt-Typ, Gierrichtung und Neigung, ohne Route folgen.' },
        { src: 'help/experte-desk-wegpunkt.png', alt: 'DESK Expertenmodus, oberer Teil', caption: 'DESK, Expertenmodus: Koordinaten und Route folgen fuer Hoehe, Geschwindigkeit und Wegpunkt-Typ.' },
        { src: 'help/experte-desk-neigung.png', alt: 'DESK Expertenmodus, Gierrichtung und Neigung', caption: 'DESK, Expertenmodus: Gierrichtung, Ausrichtung, Neigungswinkel setzen. Pfadmodus und Richtungsmodus nur mit ?DEBUG.' }
      ],
      inputs: [
        { name: 'Wegpunktnummer', where: 'Schritt 4, Nummernleiste', required: true },
        { name: 'Flughoehe', where: 'Einfacher Modus', required: true },
        { name: 'Geschwindigkeit, Wegpunkt-Typ, Gierrichtung, Neigung', where: 'auch im einfachen Modus', required: false },
        { name: 'Punkt speichern', where: 'unter dem Formular', required: true }
      ]
    },
    {
      id: 'editor.display',
      pages: ['editor'],
      views: ['editor.coverage', 'editor.edit_path', 'editor.waypoint', 'editor.mission'],
      title: 'Anzeigekontrolle',
      what: 'Wegpunkt blau (#2f6dff): Hoehe, Geschwindigkeit, Wegpunkttyp und Gierrichtung folgen der Mission, und die Neigung ist −90 oder nicht gesetzt. Wegpunkt orange (#ff8c00): lokale Abweichung bei Hoehe, Geschwindigkeit, Wegpunkttyp oder Gierrichtung, oder ein gesetzter Neigungswinkel ungleich −90. Der gewaehlte Wegpunkt ist gelb (#f5d90a), auch wenn er sonst orange waere. −90 zeigt keine Gradzahl, der Punkt bleibt blau, sofern keine andere Abweichung vorliegt. Eine spezifische Neigung steht klein in Grad neben der Flughoehe. Die Zahl ueber dem Punkt ist die Wegpunktnummer. Darunter steht die programmierte Flughoehe, in Klammern die Hoehe ab Startpunkt. Gierrichtung: Dreieck entlang des naechsten Abschnitts, bei Definiert entlang des Winkels, bei Fokus POI zum POI. Manuell ohne Dreieck. Schwenkachse sperren ist ein rotes X. Der POI ist ein eigener markierter Punkt mit Beschriftung. Suchabdeckung bei Neigung −90 oder nicht gesetzt: hellblauer Streifen mittig auf der Route. Bodenbreite und Bodenlänge kommen aus Flughöhe und Sichtfeld. Die Nase entlang der Bahn nimmt die halbe Bodenbreite, quer zur Bahn die halbe Bodenlänge. Start und Ende haben eine Kappe, eine Kurve einen äusseren Kreisausschnitt. Neigung ungleich −90 zeichnet den schrägen Footprint in Gierrichtung, versetzt von der Route. Ab 150 m Bodenabstand wird er blasser und endet bei 320 m. Punkte mit −90 behalten den einfachen Streifen. Nicht abgedeckte Flaechen, nur DESK, sind das Raster im Feld ausserhalb des Streifens. Wendekurven anzeigen zeichnet bei Koordinierte Drehung eine graue Kurve: sie verlaesst den ankommenden Abschnitt um die Wendedistanz vor dem Punkt und trifft den abgehenden Abschnitt dieselbe Distanz danach. MOB zeichnet dieselbe graue Kurve auf der Route, ohne eigenen Schalter. Neben dem Massstab stehen A− und A+. Sie aendern die Schrift der Wegpunktnummer, der Hoehe, der Gradzahl und der Klammerhoehe. Abweichung von der Sollflughoehe setzt einen roten Punkt (#e10600), wo die Hoehe ueber Grund um mehr als die Toleranz abweicht, Vorgabe ±10 m.',
      images: [
        { src: 'help/farbcodierung-desk.png', alt: 'DESK Karte mit orangen Wegpunkten, blauer Route und roten Hoehenpunkten', caption: 'DESK, TF-053: orange Wegpunkte mit Abweichung, blaue Route, hellblaue Suchabdeckung, rote Punkte der Hoehenabweichung. Gelb (#f5d90a) ist der gewaehlte Wegpunkt; in diesem Ausschnitt liegt die gelbe Marke am Start.' },
        { src: 'help/farbcodierung-mob-blau.png', alt: 'Blaue Wegpunkte und gelber gewaehlter Punkt', caption: 'MOB, Feld ohne Abweichung: Wegpunkte blau, der gewaehlte Punkt gelb. Kleine blaue Kreuze markieren die Abschnittsrichtung.' }
      ],
      inputs: [
        { name: 'Suchabdeckung', where: 'Schritt 3 Flugpfad und Anzeigekontrolle', required: false },
        { name: 'Wendekurven anzeigen', where: 'Anzeigekontrolle, Kontrollpunkte', required: false },
        { name: 'A− / A+', where: 'neben dem Massstab', required: false },
        { name: 'Hoehen auf der Route', where: 'Anzeigekontrolle', required: false }
      ]
    },
    {
      id: 'coverage.calc',
      pages: ['editor', 'rc'],
      views: ['editor.coverage', 'editor.edit_path', 'editor.waypoint', 'rc.route', 'rc.wp', 'rc.field'],
      title: 'Suchabdeckung berechnen',
      what: 'Zwei Rechnungen, je nach Neigung. Einfach, Neigung −90 oder nicht gesetzt: Die Kamera blickt senkrecht nach unten. Die Bodenbreite ist zweimal die Flughöhe mal dem Tangens des halben horizontalen Sichtfelds. Die Bodenlänge in Blickrichtung ist dieselbe Rechnung mit dem vertikalen Sichtfeld. Der hellblaue Streifen liegt mittig auf der Route. Zeigt die Nase entlang der Bahn, ist die halbe Streifenbreite die halbe Bodenbreite. Zeigt die Nase quer zur Bahn, ist sie die halbe Bodenlänge. Dazwischen werden beide Anteile gemischt. Start und Ende erhalten eine Kappe um diese halbe Länge in Blickrichtung. An einem Richtungswechsel füllt ein Kreisausschnitt die äussere Lücke. Komplex, gesetzte Neigung ungleich −90: Die Kamera blickt schräg in Gierrichtung. Der Winkel aus dem Lot ist die Neigung plus 90 Grad. Der nahe und der ferne Bildrand liegen bei Flughöhe mal Tangens aus diesem Winkel, minus und plus dem halben vertikalen Sichtfeld. Ein Strahl, der den Horizont erreicht oder darüber liegt, hat keinen Bodenpunkt. Die Breite an einem Abstand ist die Schrägentfernung mal Tangens des halben horizontalen Sichtfelds. Die Schrägentfernung ist die Wurzel aus dem Quadrat der Flughöhe plus dem Quadrat des Bodenabstands. Der Streifen sitzt in Blickrichtung vor der Drohne und folgt der Bahn versetzt. Ab 150 m Bodenabstand wird er blasser und endet bei 320 m. Wegpunkte mit −90 auf derselben Route behalten den einfachen Streifen.',
      inputs: [
        { name: 'Neigung −90', where: 'einfacher Streifen auf der Route', required: false },
        { name: 'Neigung ungleich −90', where: 'schräger Footprint in Gierrichtung, −90 bis 30', required: false },
        { name: 'Flughöhe und Sichtfeld', where: 'Drohnentyp und programmierte Höhe', required: true }
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
      what: 'Pilot-2-KMZ (template.kml + waylines.wpml). Beim Oeffnen liest der Editor den Neigungswinkel aus template.kml: nur ein Winkel ungleich -90 ist spezifisch. Definiert ist smoothTransition mit Winkel, Fokus POI ist towardPOI. Ein Platzhalter-POI 0,0,0 setzt keinen Fokus. Nutzt interne DJI-Spezifikation fuer Enums. Route folgen am Punkt setzt useGlobalHeight/Speed/Heading/Turn; sonst die gespeicherten Punktwerte.',
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
      views: ['rc.route', 'rc.wp', 'rc.field'],
      title: 'Suchabdeckung und Hoehe auf der Route',
      what: 'Suchabdeckung bei Neigung −90 oder nicht gesetzt: hellblauer Streifen zur programmierten Flughoehe, mittig auf der Route, mit Kappe an Start und Ende und Kreisausschnitt an der Kurve. Neigung ungleich −90 zeichnet den schrägen Footprint in Gierrichtung, versetzt von der Route, blasser ab 150 m und Ende bei 320 m. Punkte mit −90 behalten den einfachen Streifen. Die Zahl unter dem Punkt ist die programmierte Hoehe, in Klammern die Hoehe ab Startpunkt. Ein Neigungswinkel ungleich −90 steht klein daneben in Grad und faerbt den Punkt orange. Der gewaehlte Punkt bleibt gelb. MOB hat keinen Schalter fuer nicht abgedeckte Flaechen. Die Wendekurve bei Koordinierte Drehung ist grau und folgt der Wendedistanz. A− und A+ im Menue Anzeige aendern dieselbe Beschriftung.',
      images: [
        { src: 'help/farbcodierung-mob.png', alt: 'MOB Karte mit orangen Wegpunkten, gelbem Punkt, POI und Gradzahlen', caption: 'MOB, TF-065: orange bei Abweichung oder Neigung ungleich −90, gelb der gewaehlte Punkt, POI beschriftet, kleine Gradzahl neben der Hoehe, hellblaue Suchabdeckung.' }
      ],
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
      what: 'Punkt zurueck und vor waehlt den Wegpunkt. Die Hoehe ist die Differenz zum Startpunkt, Flughoehenmodus ALT. Einfacher Modus zeigt nur die Flughoehe dieses Punkts. Expertenmodus zeigt Verwende Missions-Hoehe, Hoehe, Verwende Missions-Geschwindigkeit, Geschwindigkeit, Route folgen (Wegpunkt-Typ), Wegpunkt-Typ, Route folgen (Gierrichtung), Gierrichtung, den Richtungs-Winkel bei Definiert oder Schwenkachse sperren, Ausrichtung, Neigungswinkel setzen und Neigung −90 bis 30. POI-Knoepfe nur bei Fokus POI. Keine eigene Wendedistanz und keine Koordinaten. Pfadmodus und Route folgen (Richtungsmodus) nur mit ?DEBUG. Route folgen graut das Feld aus. Neigung ungleich −90 steht an der Flughoehe und faerbt den Punkt orange. Liegen schon lokale Abweichungen vor, bleibt der Expertenmodus an.',
      images: [
        { src: 'help/einfach-mob.png', alt: 'MOB einfacher Modus, nur Flughoehe', caption: 'MOB, einfacher Modus, Schritt Prüfen: nur die Flughoehe dieses Punkts.' },
        { src: 'help/experte-mob-wegpunkt.png', alt: 'MOB Expertenmodus am Wegpunkt', caption: 'MOB, Expertenmodus: Route folgen fuer Hoehe, Geschwindigkeit, Wegpunkt-Typ und Gierrichtung, dazu Ausrichtung und Neigung.' }
      ],
      inputs: [
        { name: 'Wegpunkt', where: 'Schritt Wegpunkt', required: true },
        { name: 'Flughoehe', where: '20 bis 120 m, nur dieser Punkt', required: true },
        { name: 'Neigungswinkel setzen', where: 'Expertenmodus, −90 bis 30', required: false }
      ]
    },
    {
      id: 'rc.save',
      pages: ['rc'],
      views: ['rc.name'],
      title: 'Feld speichern',
      what: 'JSON & KMZ speichern schreibt das geaenderte Feld in den Katalog, setzt das Aenderungsdatum und erneuert beide Missionsdateien. Die Meldung nennt JSON und KMZ einzeln, ob sie geschrieben wurden, ob die Vorversion in der History liegt und ob der Logeintrag geschrieben wurde. Fehlt eine Datei, steht das in der Meldung. Der Knopf steht ab dem Flugpfad, auch vor dem Schritt Speichern. JSON herunterladen und KMZ herunterladen legen nur die Datei auf dieses Geraet. Ein neues Feld bekommt die Freigabe NEU. Ohne Jagdgebiet oder Gemeinde muss das vorher bestaetigt werden.',
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
      what: 'Feld einpassen, Satellit oder Karte, Relief, Route, Suchabdeckung, Hoehe auf der Route, Abweichung von der Sollflughoehe, RPAS, Strom, Hindernisse, DABS und Flugzone. Farbcodierung wie auf DESK: blau folgt der Mission, orange ist eine lokale Abweichung oder eine Neigung ungleich −90, gelb ist der gewaehlte Punkt. −90 ohne andere Abweichung bleibt blau und ohne Gradzahl. Gierrichtung als Dreieck, Manuell ohne Dreieck, Schwenkachse sperren als rotes X. Ein POI ist ein eigener Punkt mit Beschriftung. Der rote Punkt markiert die Abweichung der Hoehe ueber Grund. Die Suchabdeckung ist bei Neigung −90 der hellblaue Streifen auf der Route und bei anderer Neigung der versetzte Footprint in Blickrichtung. Bei Koordinierte Drehung liegt eine graue Wendekurve um die Wendedistanz. A− und A+ im Menue Anzeige aendern die Wegpunktbeschriftung. Die linke Leiste laesst sich einklappen und in der Breite ziehen.',
      images: [
        { src: 'help/farbcodierung-mob.png', alt: 'Farbcodierung auf der MOB-Karte', caption: 'MOB, TF-065: blaue Route, orange Wegpunkte, gelber gewaehlter Punkt, POI, Gradzahl, hellblaue Suchabdeckung.' }
      ],
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
