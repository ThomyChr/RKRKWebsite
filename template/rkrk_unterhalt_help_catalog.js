/**
 * View-dependent help for Unterhalt (backups and code-sync).
 * Own catalog — not the Overlay-Editor help.
 */
window.RKRK_HELP_CATALOG = {
  storageKey: "rkrk_unterhalt_help_drawer_width_px",
  pages: {
    unterhalt: {
      title: "Unterhalt",
      file: "rkrk_admin_unterhalt.php",
      summary: "Katalog sichern, diese Website sichern und Programmcode entlang test27 → test → v2 → pub spiegeln. Schreiben nur von test27 oder test."
    }
  },
  icons: {
    overview: '<svg viewBox="0 0 24 24"><path fill="currentColor" d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>',
    dirs: '<svg viewBox="0 0 24 24"><path fill="currentColor" d="M10 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-8l-2-2z"/></svg>',
    catalog: '<svg viewBox="0 0 24 24"><path fill="currentColor" d="M19 3H5a2 2 0 0 0-2 2v14l4-2 4 2 4-2 4 2V5a2 2 0 0 0-2-2zm-2 12H7v-2h10v2zm0-4H7V9h10v2z"/></svg>',
    history: '<svg viewBox="0 0 24 24"><path fill="currentColor" d="M13 3a9 9 0 1 0 8.94 8H20a7 7 0 1 1-2.05-4.95L15 9h6V3l-2.05 2.05A8.96 8.96 0 0 0 13 3zm-1 5v5l4.28 2.54.72-1.21-3.5-2.08V8H12z"/></svg>',
    site: '<svg viewBox="0 0 24 24"><path fill="currentColor" d="M4 6h16v2H4V6zm0 5h16v2H4v-2zm0 5h10v2H4v-2z"/></svg>',
    sync: '<svg viewBox="0 0 24 24"><path fill="currentColor" d="M12 4V1L8 5l4 4V6c3.31 0 6 2.69 6 6 0 1.01-.25 1.97-.7 2.8l1.46 1.46A7.93 7.93 0 0 0 20 12c0-4.42-3.58-8-8-8zm0 14c-3.31 0-6-2.69-6-6 0-1.01.25-1.97.7-2.8L5.24 7.74A7.93 7.93 0 0 0 4 12c0 4.42 3.58 8 8 8v3l4-4-4-4v3z"/></svg>',
    save: '<svg viewBox="0 0 24 24"><path fill="currentColor" d="M17 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V7l-4-4zm-5 16a3 3 0 1 1 0-6 3 3 0 0 1 0 6zm3-10H5V5h10v4z"/></svg>',
    restore: '<svg viewBox="0 0 24 24"><path fill="currentColor" d="M12.5 8c-2.65 0-5.05.99-6.9 2.6L2 7v9h9l-3.62-3.62c1.39-1.16 3.16-1.88 5.12-1.88 3.54 0 6.55 2.31 7.6 5.5l2.37-.78C21.08 11.03 17.15 8 12.5 8z"/></svg>',
    warn: '<svg viewBox="0 0 24 24"><path fill="currentColor" d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>',
    zip: '<svg viewBox="0 0 24 24"><path fill="currentColor" d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm-1 9h-2v2h2v2h-2v2h2v2h-2v2h2.5a.5.5 0 0 0 .5-.5V15h-2v-2h2v-2h-2V9h2V7h-2V5h2V3.5L18.5 8H13v3z"/></svg>'
  },
  views: {
    "unterhalt.overview": {
      page: "unterhalt",
      title: "Übersicht",
      icon: "overview",
      use: "Steuerung nur auf test27 oder test. Sichern und Synchronisieren läuft in der Kette weiter: test27 → test → v2 → pub. Katalog-Daten und Website-Code sind getrennte Wiederherstellungen.",
      tools: [
        { icon: "sync", name: "Code-Sync", text: "Dateien spiegeln. Mirror löscht Extra-Dateien im Ziel; Ergänzen nicht." },
        { icon: "dirs", name: "Verzeichnisse", text: "Sites, Datenpfad, Missionsbibliotheken und welche Ordner wohin dürfen." },
        { icon: "catalog", name: "Katalog", text: "Gemeinsame Daten (JSON) plus Missions-JSON. Kein Code-Sync." },
        { icon: "site", name: "Website", text: "Programmstand nur dieser Instanz." }
      ],
      steps: [
        { icon: "overview", text: "Prüfen, dass Sie auf test27 oder test stehen." },
        { icon: "site", text: "Vor einem Mirror diese Instanz sichern." },
        { icon: "sync", text: "Route wählen, Vorschau, dann Synchronisieren." }
      ]
    },
    "unterhalt.dirs": {
      page: "unterhalt",
      title: "Verzeichnisse und Ordner",
      icon: "dirs",
      use: "Pfade relativ zum rkropen-Root. Jede Site hat Label, Ordnername und Aktiv. Die Sync-Matrix legt fest, welche Unterordner nach test, v2, pub oder rkrs dürfen.",
      tools: [
        { icon: "dirs", name: "Sites", text: "Label und relativer Ordner. Statuspill zeigt, ob der absolute Pfad existiert." },
        { icon: "catalog", name: "Datenpfad", text: "Gemeinsamer Katalog neben den Webkopien (nicht pro Instanz). Unterordner webdaten/config/webcsv/data einzeln in die Sicherung nehmen." },
        { icon: "save", name: "Missionsbibliotheken", text: "Pro Drohnentyp (MDB). Pfade sind fest; Häkchen «an» nimmt die Bibliothek in die Katalog-Sicherung." },
        { icon: "sync", name: "rkrs-Quelle", text: "Global test27 oder test, optional pro Ordner überschreiben." }
      ],
      steps: [
        { icon: "dirs", text: "Ziel-Sites und Ordnermatrix prüfen." },
        { icon: "save", text: "Verzeichnisse speichern, bevor Sie sichern oder synchronisieren." }
      ]
    },
    "unterhalt.catalog": {
      page: "unterhalt",
      title: "Katalog-Sicherungen",
      icon: "catalog",
      use: "Snapshot des gemeinsamen Datenbaums plus Missions-JSON. Restore ersetzt den aktuellen Katalog; vorher entsteht automatisch eine Sicherheitssicherung. Beim Öffnen der Seite kann ein täglicher Auto-Snapshot laufen.",
      tools: [
        { icon: "save", name: "Jetzt sichern", text: "Snapshot mit optionaler Notiz anlegen." },
        { icon: "restore", name: "Wiederherstellen", text: "Gewählten Stand zurückspielen. Bestätigung nötig." },
        { icon: "zip", name: "ZIP", text: "Herunterladen oder ZIP hochladen und wiederherstellen (wenn ZipArchive da ist)." }
      ],
      steps: [
        { icon: "save", text: "Vor riskanten Änderungen Katalog sichern." },
        { icon: "restore", text: "Nur bewusst wiederherstellen — der Live-Katalog wird ersetzt." }
      ]
    },
    "unterhalt.history": {
      page: "unterhalt",
      title: "Datei-Historie (JSON)",
      icon: "history",
      use: "Automatische Kopien einzelner JSON-/Config-Dateien vor dem Speichern unter _history/. XML-Recovery gilt nach der JSON-Umstellung nicht mehr.",
      tools: [
        { icon: "restore", name: "Zurücksetzen", text: "Eine History-Datei an die Originalposition zurückspielen." }
      ],
      steps: [
        { icon: "history", text: "Datei und Zeitstempel wählen." },
        { icon: "restore", text: "Zurücksetzen bestätigt die einzelne Datei, nicht den ganzen Katalog." }
      ]
    },
    "unterhalt.site": {
      page: "unterhalt",
      title: "Website-Sicherung",
      icon: "site",
      use: "Programmdateien nur dieser Instanz nach rkrk/SiteBackups/<site>. Ohne Katalog-JSON, Missions-Medien, DAV, vendor. Restore nur in dieselbe Instanz. Vor dem Sync diese Instanz sichern.",
      tools: [
        { icon: "save", name: "Jetzt sichern", text: "Snapshot dieser Website mit optionaler Notiz." },
        { icon: "restore", name: "Wiederherstellen", text: "Ersetzt den Programmstand dieser Instanz." },
        { icon: "zip", name: "ZIP", text: "Herunterladen oder ZIP einspielen." }
      ],
      steps: [
        { icon: "site", text: "Vor Mirror oder grösseren Syncs sichern." },
        { icon: "restore", text: "Restore trifft nur diese Kopie, nicht die anderen Stufen." }
      ]
    },
    "unterhalt.sync": {
      page: "unterhalt",
      title: "Code-Sync",
      icon: "sync",
      use: "Kopiert neue oder neuere Dateien (mtime + SHA1) entlang der Kette. Standard Mirror: Spiegel der gewählten Ordner, Extra-Dateien im Ziel werden gelöscht. Ergänzen kopiert nur. Alle Ebenen | = restliche Stufen ab hier, ohne Kopie auf sich selbst, ohne rkrs. test27 → rkrs nur mit eigener Route und angehaktem Modul.",
      tools: [
        { icon: "sync", name: "Route", text: "Einzelhop oder Alle Ebenen | (| trennt die nicht enthaltene Route test27 → rkrs)." },
        { icon: "sync", name: "rkrs-Modul", text: "Nur sichtbar bei Route test27 → rkrs: map, mapII, assess oder calc. Assess landet in rkrs/assess/public/. Ohne Auswahl kein Sync." },
        { icon: "warn", name: "Mirror", text: "Löscht Dateien, die nur im Ziel existieren (nur Sync-Matrix, nicht Katalog/Missionen)." },
        { icon: "save", name: "Ergänzen", text: "Nur kopieren, nichts löschen." },
        { icon: "overview", name: "Vorschau", text: "Zeigt Kopierliste und — bei Mirror — die Löschliste. Spätere Stufen als Ist-Stand." }
      ],
      steps: [
        { icon: "site", text: "Diese Instanz sichern." },
        { icon: "sync", text: "Route wählen. Nach rkrs: Modul(e) anhaken, letztes Datum prüfen, Vorschau." },
        { icon: "warn", text: "Synchronisieren nur von test27 oder test, wenn Apply freigegeben ist. RoboCopy v2 → pub erst abschalten, wenn Mirror auf dieser Route mehrfach geklappt hat." }
      ]
    }
  },
  functions: [
    {
      id: "unterhalt.control",
      pages: ["unterhalt"],
      views: ["unterhalt.overview"],
      roles: ["admin"],
      icon: "overview",
      title: "Wer darf schreiben",
      what: "POST, Sync-Apply, Katalog-Auto-Daily und Website-Sicherung nur von test27 oder test. v2 und pub sind nur lesend."
    },
    {
      id: "unterhalt.save_dirs",
      pages: ["unterhalt"],
      views: ["unterhalt.dirs"],
      roles: ["admin"],
      icon: "save",
      title: "Verzeichnisse speichern",
      what: "Schreibt Sites, Datenpfad, Includes, Missions-Häkchen, rkrs-Quelle und die Ordner-Zielmatrix."
    },
    {
      id: "unterhalt.catalog_snap",
      pages: ["unterhalt"],
      views: ["unterhalt.catalog"],
      roles: ["admin"],
      icon: "catalog",
      title: "Katalog sichern / wiederherstellen",
      what: "Gemeinsamer Baum ../rkrk plus Missions-JSON. Feld-JPG/KMZ bleiben draussen. Restore legt zuerst eine Sicherheitssicherung an."
    },
    {
      id: "unterhalt.history_restore",
      pages: ["unterhalt"],
      views: ["unterhalt.history"],
      roles: ["admin"],
      icon: "history",
      title: "Einzelne JSON zurücksetzen",
      what: "Eine History-Kopie vor einem Speichern zurückspielen. Nicht dasselbe wie ein ganzer Katalog-Restore."
    },
    {
      id: "unterhalt.site_snap",
      pages: ["unterhalt"],
      views: ["unterhalt.site"],
      roles: ["admin"],
      icon: "site",
      title: "Website sichern / wiederherstellen",
      what: "Nur der Programmstand dieser Instanz. Restore nie auf eine andere Stufe."
    },
    {
      id: "unterhalt.sync_run",
      pages: ["unterhalt"],
      views: ["unterhalt.sync"],
      roles: ["admin"],
      icon: "sync",
      title: "Vorschau und Synchronisieren",
      what: "jsonfiles, Missions-Medien, rkrs data/, results/ (Reports) und Live-Personel-JSON (pilotenliste, pilotlistfull, teilnehmerliste_config) werden nicht kopiert — die kommen von rehkitzrettung.ch. Assess nach rkrs geht nach assess/public/. Nach rkrs immer ein Modul wählen; Alle Ebenen nimmt rkrs nicht mit. Das letzte Sync-Datum steht unter der Pille. Mirror bestätigt extra, weil Extra-Dateien gelöscht werden."
    }
  ]
};
