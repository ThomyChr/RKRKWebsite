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
    }
  },
  views: {
    'editor.overview': {
      page: 'editor',
      title: 'Uebersicht Editor',
      use: 'Feld waehlen, auf der Karte pruefen, Parameter anpassen, Pfad rechnen, exportieren.',
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
      use: 'Pruefen, ob der Pfad das Feld abdeckt; Luecken und Ueberlappung sehen.',
      inputs: [
        { name: 'Mindestens ein sichtbarer Flugpfad', where: 'Anzeigekontrolle', required: true },
        { name: 'Abdeckung an', where: 'Anzeigekontrolle', required: true },
        { name: 'Flughoehe + FOV', where: 'Missions-Einstellungen / Drohne', required: true }
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
      title: 'Felddaten / XML',
      use: 'Interne Stammdaten speichern oder neues Feld anlegen.',
      inputs: [
        { name: 'FeldID *', where: 'Felddaten-Box', required: true },
        { name: 'Feldname *', where: 'Felddaten-Box', required: true },
        { name: 'Startplatz lon,lat', where: 'Felddaten-Box', required: true },
        { name: 'Flaechendaten lon,lat ...', where: 'Felddaten-Box', required: true }
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
      what: 'Ohne bestehendes Polygon: Eckpunkte setzen, Doppelklick schliesst. Danach Bearbeiten aktiv.',
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
      what: 'Nach Berechnung oder Laden: Wegpunkte verschieben, einfuegen, loeschen. Aenderungen gehen in den Export.',
      inputs: [{ name: 'Flugweg', where: 'Pfad-Button oder XML', required: true }]
    },
    {
      id: 'editor.start',
      pages: ['editor'],
      views: ['editor.start'],
      title: 'Startpunkt setzen oder verschieben',
      what: 'Klick setzt den Startplatz. Ziehen verschiebt ihn. Danach wird der Flugpfad neu berechnet: Abheben am Start, dann zum ersten Feldpunkt.',
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
      what: 'Dialog: Pflichtfelder, dann als neuen XML-Eintrag speichern. Geometrie kann vorher gezeichnet sein.',
      inputs: [
        { name: 'FeldID, Name', where: 'Popup', required: true },
        { name: 'Start + Flaeche', where: 'Karte oder Formular', required: true }
      ]
    },
    {
      id: 'editor.save',
      pages: ['editor'],
      views: ['editor.fielddata'],
      title: 'Aktuelles Feld speichern',
      what: 'Schreibt Geometrie, Flugweg, Stammdaten und Missions-Einstellungen zurueck nach RKRKFelder.xml.',
      inputs: [
        { name: 'FeldID + Name', where: 'Felddaten-Box', required: true },
        { name: 'Startplatz + Flaechendaten', where: 'Felddaten-Box', required: true }
      ]
    },
    {
      id: 'editor.mission',
      pages: ['editor'],
      views: ['editor.mission'],
      title: 'Missions- / KMZ-Parameter',
      what: 'Steuern Trajektorie und DJI-Export: Drohne (FOV), Hoehe, Speed, Overlap, Rand, Richtung, Endaktion, Gier, Wegpunkttyp, Wendedistanz, sichere Starthoehe, Gimbal, Hoehenmodus. Wenn KMZ-Parameter inaktiv, gelten DJI-Referenz-Defaults.',
      inputs: [
        { name: 'KMZ-Parameter aktiv', where: 'Checkbox', required: true },
        { name: 'Drohnentyp', where: 'aus DJI-Spezifikation', required: true },
        { name: 'Flughoehe / Speed / Overlap / Randfaktor', where: 'Missions-Box', required: true }
      ]
    },
    {
      id: 'editor.waypoint',
      pages: ['editor'],
      views: ['editor.waypoint', 'editor.edit_path'],
      title: 'Punktparameter / Route folgen',
      what: 'Pro Wegpunkt: «Route folgen» bzw. «Verwende Missions-…» übernimmt Hoehe, Speed, Wegpunkttyp, Wendedistanz und Gier aus der Mission (KMZ: useGlobal*=1). Ausgeschaltet gelten die gespeicherten Punktwerte (useGlobal*=0). Tab «Alle Wegpunkte»: Aenderungen gelten fuer alle Punkte gleichzeitig (ohne Lat/Lng).',
      inputs: [
        { name: 'Route folgen / Missionswerte', where: 'rechte Schublade Punktparameter', required: false },
        { name: 'Alle Wegpunkte', where: 'Tab in der Schublade', required: false }
      ]
    },
    {
      id: 'editor.display',
      pages: ['editor'],
      views: ['editor.coverage'],
      title: 'Anzeigekontrolle',
      what: 'Feld, Originalpfad, neuer Pfad, Abdeckungsband, Ueberlappung, Luecken, Korrekturpunkte, Wendekurven, Kontrollpunkte. Abdeckung nur wenn ein Pfad sichtbar ist.',
      inputs: [
        { name: 'Pfad-Checkbox', where: 'Anzeigekontrolle', required: true }
      ]
    },
    {
      id: 'editor.overlays',
      pages: ['editor'],
      views: ['editor.overview'],
      title: 'Karte & SwissTopo-Overlays',
      what: 'Basis Swisstopo Farbe, schwarz-weiss (S/W), Swissimage oder OSM. Optional Strom, Grenzen, Jagdbanngebiete AuLaV, RPAS, UAS, Wohngebiete nach AuLaV, swissALTI3D und swissSURFACE3D Relief (multidirektional), DABS, Hindernisse, Grundbuch, Naturschutz, Seilbahnen. AuLaV und beide Relief-Ebenen sind standard aus.',
      inputs: [{ name: 'Kartenbasis', where: 'Swisstopo-Box', required: false }]
    },
    {
      id: 'editor.export_uav',
      pages: ['editor'],
      views: ['editor.export'],
      title: 'UAV JSON exportieren',
      what: 'Internes Missions-JSON (UAV-kompatibel) herunterladen.',
      inputs: [
        { name: 'Feld + Flugweg', where: 'Karte/Felddaten', required: true }
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
      id: 'analyse.requirements',
      pages: ['analyse'],
      views: ['analyse.metrics'],
      title: 'Schritt-Requirements',
      what: 'Externe Seite steuert, welche Algorithmus-Schritte (Richtung, Abdeckung, Wegpunkte) gelten.',
      inputs: [{ name: 'Link Schritt-Requirements', where: 'linke Leiste', required: false }]
    }
  ]
};
