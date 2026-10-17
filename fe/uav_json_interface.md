# UAV Mission JSON Interface (v1)

Ziel: Austauschformat fuer UAV-kompatible Missionen aus dem `field_editor`.

## Root

```json
{
  "interface_version": "uav-mission-json-v1",
  "mission_type": "field_scan",
  "created_at": "ISO-8601",
  "field": {
    "feld_id": "26-004",
    "name": "Feldname",
    "std_agl_m": 50
  },
  "waypoints": [
    {
      "index": 1,
      "lon": 8.5001,
      "lat": 47.2601,
      "agl_m": 50,
      "useMissionDirectionMode": true,
      "useMissionPathMode": true,
      "useMissionYawDirection": true,
      "useMissionWaypointType": true,
      "useMissionSpeed": true,
      "useMissionHeight": true,
      "directionType": "followWayline",
      "yawDirection": "followWayline",
      "waypointType": "coordinateTurn",
      "pathMode": "straight",
      "curveSize": 0,
      "speed": 5,
      "height": 60
    }
  ],
  "mapping": {
    "source": "field_editor.mission_settings",
    "form_to_uav_json": {
      "msSpeed -> drone_control_settings.speed_m_s": 5
    },
    "form_to_dji_kmz": {
      "msSpeed -> wpml:autoFlightSpeed / globalTransitionalSpeed": 5
    }
  }
}
```

## DJI Pilot 2 KMZ

- Die KMZ-Datei ist ein ZIP-Archiv im DJI-WPML-Format mit **`wpmz/template.kml`** (Vorlage) und **`wpmz/waylines.wpml`** (ausfuehrbare Route).
- Spec: DJI Cloud API WPML (`template.kml`, `waylines.wpml`, Common Elements). Drohnen-/Payload-Enums und Limits kommen aus `scripts/rkrk_dji_kmz_interface.php`.
- Import in DJI Pilot 2: KMZ auf den Controller kopieren und unter **Flugroute** oeffnen.
- Die API `export_dji_kmz` liefert **`kmz_base64`** fuer den direkten Browser-Download.

## Feldbeschreibung

- `interface_version`: feste Versionskennung fuer Parser.
- `mission_type`: aktuell `field_scan`.
- `created_at`: UTC ISO-8601 Timestamp.
- `field`: Metadaten der gewaehlten Flaeche (`feld_id`, `name`, `std_agl_m`, **`mission_settings`** normalisiert).
- `drone_control_settings`: Spiegel von `field.mission_settings` fuer schnelle Nutzung im JSON-Parser.
- `waypoints`: jeder Eintrag enthaelt Position, `agl_m` sowie **CH/DJI-kompatible** Steuerfelder. `useMissionYawDirection`, `useMissionWaypointType`, `useMissionSpeed`, `useMissionHeight` sind `true`, wenn im Formular **„Route folgen“** gewaehlt ist (Missionsvorgaben gelten fuer alle Punkte); bei **„Pro Wegpunkt“** sind sie `false`, und `height` pro Punkt aus dem Triplet (`agl_m`), `speed` weiter aus der Mission — Anpassungen erfolgen dann im UAV-/Pilot-Editor wie in den `samples/`-Missionen.
- `mapping`: explizite Zuordnung der Formularwerte (`Drohnensteuerung Spec`) zu UAV JSON und DJI/KMZ Feldern.

## Hinweise

- Dieses Dokument ist die erste Interface-Version fuer JSON-Export.
- Im naechsten Schritt kann die Struktur auf bestehende Missionsdaten in `Missionen/...` angeglichen werden, sobald diese Datenbasis im aktuellen Workspace verfuegbar ist.
- Die Mapping-Information wird ebenfalls in den API-Antworten von `export_uav_json` und `export_dji_kmz` mitgeliefert.
