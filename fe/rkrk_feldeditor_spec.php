<?php
require_once __DIR__ . '/mapll_env.php';
$mapllBrand = mapll_favicon_brand();
?><!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Feldeditor: Oberfläche, Missions-JSON und KMZ</title>
  <?php echo mapll_favicon_link_tags($mapllBrand); ?>
  <style>
    body { margin: 0; font-family: Arial, sans-serif; background: #f4f6f8; color: #1f344a; line-height: 1.45; }
    .wrap { max-width: 1280px; margin: 0 auto; padding: 20px 16px 48px; }
    h1 { margin: 0 0 8px; font-size: 26px; }
    h2 { margin: 28px 0 8px; font-size: 18px; }
    p { margin: 0 0 10px; }
    .lead { color: #2f4963; }
    a { color: #1d4e89; }
    code { font-family: Consolas, monospace; font-size: 0.92em; }
    table { width: 100%; border-collapse: collapse; background: #fff; border: 1px solid #d9dde2; margin: 0 0 14px; }
    th, td { border-bottom: 1px solid #e6ebf0; padding: 8px; vertical-align: top; font-size: 13px; text-align: left; }
    th { background: #f7fafd; }
    tr.group td { background: #e8f0e4; font-weight: 700; }
    td.grp { font-weight: 700; color: #1d4e89; white-space: nowrap; }
    td.hint { color: #3d556c; }
    .none { color: #9a6500; font-weight: 700; }
    ul { margin: 6px 0 12px; padding-left: 1.2em; }
    .src { font-size: 13px; color: #3d556c; }
  </style>
</head>
<body>
  <div class="wrap">
    <h1>Feldeditor: Oberfläche, Missions-JSON und KMZ</h1>
    <p class="lead">DESK und MOB, dieselbe Missions-JSON. Kataloggeometrie aus <code>Missionen/xmlfiles/RKRKFelder.xml</code>. Flags und Wegpunktwerte aus dem JasonFile, <code>geojson.features[].properties</code>. Punkttyp 6 (Start) zählt bei den Flags nicht.</p>
    <p>Der Export schreibt <code>wpmz/template.kml</code> und <code>wpmz/waylines.wpml</code>, Namespace <code>http://www.dji.com/wpmz/1.0.2</code>. Steht bei einem Feld kein Tag, ist der Eintrag <span class="none">kein KMZ-Tag</span>.</p>
    <p class="src">DJI:
      <a href="https://developer.dji.com/doc/cloud-api-tutorial/en/api-reference/dji-wpml/template-kml.html">template.kml</a>,
      <a href="https://developer.dji.com/doc/cloud-api-tutorial/en/api-reference/dji-wpml/waylines-wpml.html">waylines.wpml</a>,
      <a href="https://developer.dji.com/doc/cloud-api-tutorial/en/api-reference/dji-wpml/common-element.html">Common Elements</a>.
    </p>

    <h2>Gierrichtung und Neigung</h2>
    <p>Die Nasenrichtung steht in der JSON unter <code>yawDirection</code>. In der KMZ heisst dasselbe Feld <code>waypointHeadingMode</code>. Die Namen sind nicht dieselben. Ein konkretes <code>directionType</code> gewinnt vor <code>yawDirection</code> <code>followWayline</code>.</p>
    <table>
      <thead>
        <tr>
          <th>Bedeutung</th>
          <th>JSON (<code>yawDirection</code>)</th>
          <th>KMZ (<code>waypointHeadingMode</code>)</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>Richtung zum nächsten Wegpunkt</td>
          <td><code>followWayline</code></td>
          <td><code>followWayline</code></td>
        </tr>
        <tr>
          <td>Definiert. Der Winkel (<code>direction</code>) ist die Nase. <code>null</code> bleibt <code>null</code> und ist nicht 0.</td>
          <td><code>customised</code></td>
          <td><code>smoothTransition</code>. In <code>waylines.wpml</code> steht <code>waypointHeadingAngleEnable</code> auf <code>1</code> und <code>waypointHeadingAngle</code> auf dem Winkel.</td>
        </tr>
        <tr>
          <td>Schwenkachse sperren. Rotes X, Winkel gesperrt.</td>
          <td><code>fixed</code></td>
          <td><code>fixed</code>. Winkel <code>0</code>, <code>waypointHeadingAngleEnable</code> <code>0</code>.</td>
        </tr>
        <tr>
          <td>Fokus POI. Nase zum markierten POI. Das ist nicht <code>smoothTransition</code>.</td>
          <td><code>smoothTransition</code></td>
          <td><code>towardPOI</code>. Ohne POI <code>followWayline</code>. Winkel <code>0</code>, <code>waypointHeadingAngleEnable</code> <code>0</code>.</td>
        </tr>
        <tr>
          <td>Manuell</td>
          <td><code>manually</code></td>
          <td><code>manually</code></td>
        </tr>
      </tbody>
    </table>
    <p>Definiert und Fokus POI sind in der KMZ nicht dasselbe. Definiert ist <code>smoothTransition</code>. Fokus POI ist <code>towardPOI</code>. Die Fernsteuerung schreibt <code>waypointPoiPoint</code> an jedem Wegpunkt, auch bei Definiert. Das ist kein Fokus POI. Fokus POI gilt nur bei <code>waypointHeadingMode</code> <code>towardPOI</code>.</p>
    <p>Lokaler Heading-Block in <code>template.kml</code> und <code>waylines.wpml</code>: <code>waypointHeadingMode</code>, <code>waypointHeadingAngle</code>, <code>waypointPoiPoint</code>, <code>waypointHeadingPathMode</code>, <code>waypointHeadingPoiIndex</code> <code>0</code>. <code>useGlobalHeadingParam</code> steht daneben, nicht im Block. <code>1</code> lässt den Block weg. Ein lokaler Punkt schreibt das Flag nicht als <code>0</code> in den Block. Die Drehrichtung steht in <code>waypointHeadingPathMode</code> (<code>followBadArc</code>, <code>clockwise</code>, <code>counterClockwise</code>). In <code>waylines.wpml</code> steht zusätzlich <code>waypointHeadingAngleEnable</code>: <code>1</code> nur bei Definiert mit gesetztem Winkel, sonst <code>0</code> und Winkel <code>0</code>.</p>
    <p>Neigungswinkel. Standard −90 ist nicht spezifisch. JSON: <code>gimbalControl</code> <code>manuel</code>, an jedem Wegpunkt <code>gimbalAngle</code> −90. KMZ: <code>gimbalPitchMode</code> <code>manual</code>, in <code>template.kml</code> kein <code>gimbalPitchAngle</code>. In <code>waylines.wpml</code> steht <code>waypointGimbalHeadingParam</code> mit <code>waypointGimbalPitchAngle</code> <code>0</code> und <code>waypointGimbalYawAngle</code> <code>0</code>.</p>
    <p>«Neigungswinkel setzen» und ein Wert ungleich −90 ist spezifisch. JSON: <code>gimbalControl</code> <code>setEachWaypoint</code>, <code>gimbalAngle</code> der gesetzte Wert (−90 bis 30). KMZ: <code>gimbalPitchMode</code> <code>usePointSetting</code>, <code>gimbalPitchAngle</code> am Wegpunkt in <code>template.kml</code>, in <code>waylines.wpml</code> derselbe Wert als <code>waypointGimbalPitchAngle</code>. Der Schalter rechnet den Flugpfad nicht neu. An der Flughöhe des Wegpunkts steht dann eine kleine Gradzahl, zum Beispiel <code>55</code> und daneben <code>−45°</code>. Der Wegpunkt ist orange, wie bei einer Abweichung von den Missionsdaten. Der gewählte Punkt bleibt gelb. Beim Standard −90 steht nur die Höhe und der Punkt bleibt blau. Speichern schreibt keine zweite KML-Datei. Die KML ist die <code>template.kml</code> in der KMZ.</p>
    <p>JSON und KMZ liegen direkt im Gebietsordner, zum Beispiel <code>TF_Testfelder/datei.json</code> und <code>datei.kmz</code>, ohne Unterordner der Katalognummer.</p>

    <h2>Suchabdeckung</h2>
    <p>Nur die Karte. <span class="none">kein KMZ-Tag</span>. Der Flugpfad wird dabei nicht neu gerechnet. Die Höhe <code>h</code> ist die programmierte Flughöhe des Punkts. <code>HFOV</code> und <code>VFOV</code> kommen vom Drohnentyp.</p>
    <p>Einfach, Neigung −90 oder nicht gesetzt. Die Kamera blickt senkrecht nach unten. Bodenbreite W = 2 · h · tan(HFOV / 2). Bodenlänge = 2 · h · tan(VFOV / 2). Der hellblaue Streifen liegt mittig auf der Route. Dieselbe Überlappung wie bei der grünen Strecke S: S = (1 − Überlappung) · W. Der Faktor heisst im Code <code>spacingM / widthM</code> und ist (1 − Überlappung). Die Überlappung ist der Missionswert, Standard 15 %, der Faktor also 0.85. Standard der gezeichneten Suchabdeckung ist S, vorwärts und seitwärts, nach der Wahl der Kante. W ist das volle Kamerafenster. Mit <code>DEBUG</code> steht in der Anzeigekontrolle unter Abdeckung die Wahl Kamerasicht (W) oder Suchabdeckung (S). Ohne DEBUG und ohne gespeicherten Wert gilt S. Bei Missionshöhe und Nase entlang der Bahn ist die Streifenbreite gleich S. Ist die Gierrichtung nicht «Richtung zum nächsten Wegpunkt» und zeigt die Nase quer zur Bahn, ist die Streifenbreite die Bodenlänge mal denselben Faktor. «Richtung zum nächsten Wegpunkt» bleibt bei der Bodenbreite mal dem Faktor. Dazwischen werden beide Anteile gemischt. Start und Ende erhalten eine Kappe um die halbe skalierte Länge in Blickrichtung. An einem Richtungswechsel füllt ein Kreisausschnitt die äussere Lücke.</p>
    <p>Komplex, gesetzte Neigung ungleich −90. Die Kamera blickt schräg in Gierrichtung. Der Winkel aus dem Lot ist τ = Neigung + 90°. Naher Bildrand = h · tan(τ − VFOV / 2), ferner Bildrand = h · tan(τ + VFOV / 2). Ein Strahl, dessen Winkel aus dem Lot 89° erreicht oder darüber liegt, hat keinen Bodenpunkt. Die halbe Breite an einem Bodenabstand r ist die Schrägentfernung √(h² + r²) mal tan(HFOV / 2). Nah, fern und diese halbe Breite werden mit demselben Faktor (1 − Überlappung) multipliziert. Ist die Gierrichtung «Richtung zum nächsten Wegpunkt», blickt die Kamera entlang der Bahn: quer zur Bahn die horizontale Breite, längs die vertikale Ausdehnung. Bei jeder anderen Gierrichtung bleibt das Fenster in der Kameraachse (HFOV quer zum Blick, VFOV in der Nickebene) und die Bahn schiebt es nur. Der Streifen sitzt in Blickrichtung vor der Drohne. Ab 150 m Bodenabstand des unskalierten Fensters wird er blasser und endet bei 320 m. Fehlt das Sichtfeld, gelten hier 49° horizontal und 40° vertikal. Wegpunkte mit −90 auf derselben Route behalten den einfachen Streifen.</p>

    <h2>Flag-Regel</h2>
    <p><code>true</code> und <code>1</code>: Missionswert, Expertenfeld ausgegraut. <code>false</code> und <code>0</code>: lokaler Wert. Im JasonFile <code>true</code> oder <code>false</code>. In der KMZ <code>1</code> oder <code>0</code>.</p>
    <p>Mit Tag: <code>useMissionHeight</code> → <code>wpml:useGlobalHeight</code>, <code>useMissionSpeed</code> → <code>wpml:useGlobalSpeed</code>, <code>useMissionWaypointType</code> → <code>wpml:useGlobalTurnParam</code>, <code>useMissionYawDirection</code> → <code>wpml:useGlobalHeadingParam</code>.</p>
    <p><span class="none">kein KMZ-Tag</span>: <code>useMissionPathMode</code>, <code>useMissionDirectionMode</code>. <code>wpml:useStraightLine</code> kommt aus dem Wegpunkt-Typ, nicht aus dem Pfadmodus. <code>wpml:waypointHeadingMode</code> ist der Giermodus, nicht das Flag.</p>
    <p>Ist eines der sechs Flags <code>false</code>, sind Punkt und Gierrichtungssymbol orange. Ein spezifischer Neigungswinkel, ungleich −90, färbt den Punkt ebenfalls orange. Der gewählte Punkt bleibt gelb. Ein fehlendes Flag färbt nicht. Das rote X der gesperrten Schwenkachse bleibt rot.</p>

    <h2>Zuordnung</h2>
    <table>
      <thead>
        <tr>
          <th>Gruppe</th>
          <th>UI, DESK und MOB</th>
          <th>JSON</th>
          <th>KMZ</th>
          <th>Hinweise</th>
        </tr>
      </thead>
      <tbody>
        <tr class="group"><td colspan="5">Flags am Wegpunkt</td></tr>
        <tr>
          <td class="grp">Höhe</td>
          <td>Verwende Missions-Höhe</td>
          <td><code>useMissionHeight</code></td>
          <td><code>wpml:useGlobalHeight</code> <code>1</code>/<code>0</code></td>
          <td class="hint"><code>true</code>: Missionshöhe, Expertenfeld ausgegraut. <code>false</code>: lokale Höhe. Auto Höhenanpassung setzt das Flag auf <code>0</code>, auch wenn die UI an ist.</td>
        </tr>
        <tr>
          <td class="grp">Geschwindigkeit</td>
          <td>Verwende Missions-Geschwindigkeit</td>
          <td><code>useMissionSpeed</code></td>
          <td><code>wpml:useGlobalSpeed</code> <code>1</code>/<code>0</code></td>
          <td class="hint"><code>waypointSpeed</code> steht in <code>template.kml</code> nur bei <code>0</code>. In <code>waylines.wpml</code> steht die Zahl immer.</td>
        </tr>
        <tr>
          <td class="grp">Wegpunkt-Typ</td>
          <td>Route folgen</td>
          <td><code>useMissionWaypointType</code></td>
          <td><code>wpml:useGlobalTurnParam</code> <code>1</code>/<code>0</code></td>
          <td class="hint">Der Turn-Block (Dämpfung, <code>useStraightLine</code>, Turn-Modus) wird immer geschrieben, auch bei <code>1</code>.</td>
        </tr>
        <tr>
          <td class="grp">Gierrichtung</td>
          <td>Route folgen (Gierrichtung)</td>
          <td><code>useMissionYawDirection</code></td>
          <td><code>wpml:useGlobalHeadingParam</code> <code>1</code>/<code>0</code></td>
          <td class="hint"><code>1</code>: kein Heading-Block. Lokal: Block ohne das Flag darin. <code>waylines.wpml</code> hat den Block immer. Ein <code>false</code> färbt Punkt und Gierrichtungssymbol orange. Der gewählte Punkt bleibt gelb.</td>
        </tr>
        <tr>
          <td class="grp">Gierrichtung</td>
          <td>Pfadmodus</td>
          <td><code>useMissionPathMode</code></td>
          <td class="none">kein KMZ-Tag</td>
          <td class="hint">Steuert nur die UI. <code>useStraightLine</code> kommt aus dem Wegpunkt-Typ, nicht aus diesem Flag.</td>
        </tr>
        <tr>
          <td class="grp">Gierrichtung</td>
          <td>Route folgen (Richtungsmodus)</td>
          <td><code>useMissionDirectionMode</code></td>
          <td class="none">kein KMZ-Tag</td>
          <td class="hint">Steuert nur die UI. <code>waypointHeadingMode</code> ist der Giermodus, nicht dieses Flag. Ein fehlendes Flag färbt den Punkt nicht.</td>
        </tr>

        <tr class="group"><td colspan="5">Wegpunktwerte</td></tr>
        <tr>
          <td class="grp">Höhe</td>
          <td>Flughöhe am Wegpunkt</td>
          <td><code>height</code></td>
          <td><code>template.kml</code>: <code>wpml:height</code> und <code>wpml:ellipsoidHeight</code>. <code>waylines.wpml</code>: <code>wpml:executeHeight</code></td>
          <td class="hint"><code>height</code> und <code>ellipsoidHeight</code> sind derselbe Wert. <code>waylines.wpml</code> schreibt <code>executeHeight</code>, nicht <code>height</code>.</td>
        </tr>
        <tr>
          <td class="grp">Geschwindigkeit</td>
          <td>Geschwindigkeit am Wegpunkt</td>
          <td><code>speed</code></td>
          <td><code>wpml:waypointSpeed</code></td>
          <td class="hint">In <code>template.kml</code> nur, wenn <code>useGlobalSpeed</code> <code>0</code> ist. In <code>waylines.wpml</code> an jedem Punkt.</td>
        </tr>
        <tr>
          <td class="grp">Wegpunkt-Typ</td>
          <td>Wegpunkt-Typ</td>
          <td><code>waypointType</code></td>
          <td><code>wpml:waypointTurnMode</code>, global <code>wpml:globalWaypointTurnMode</code></td>
          <td class="hint">Werte: <code>coordinateTurn</code>, <code>toPointAndStopWithDiscontinuityCurvature</code>, <code>toPointAndStopWithContinuityCurvature</code>, <code>toPointAndPassWithContinuityCurvature</code>. „Wendet vor dem Wegpunkt“ ist <code>toPointAndPassWithContinuityCurvature2</code> plus <code>useStraightLine</code> <code>1</code>, als Geschwister von <code>waypointTurnParam</code>. Ein unbekannter Typ wird <code>coordinateTurn</code>. Ist Route folgen aus, stehen <code>useMissionWaypointType</code> <code>false</code> und <code>useGlobalTurnParam</code> <code>0</code>.</td>
        </tr>
        <tr>
          <td class="grp">Wegpunkt-Typ</td>
          <td>Wendedistanz. DESK hat das Feld. MOB behält den gespeicherten Wert.</td>
          <td><code>curveSize</code>. Älterer Name <code>turningDistance</code>.</td>
          <td><code>wpml:waypointTurnDampingDist</code></td>
          <td class="hint">Route folgen an: die Missionsdistanz. Route folgen aus: die lokale Distanz. Am ersten und letzten Punkt und ausserhalb <code>coordinateTurn</code> steht <code>0</code>. MOB hat kein Eingabefeld.</td>
        </tr>
        <tr>
          <td class="grp">Gierrichtung</td>
          <td>Gierrichtung des Fluggeräts</td>
          <td><code>yawDirection</code></td>
          <td><code>wpml:waypointHeadingMode</code></td>
          <td class="hint">Bedeutungen und die abweichenden KMZ-Namen stehen im Abschnitt Gierrichtung und Neigung.</td>
        </tr>
        <tr>
          <td class="grp">Gierrichtung</td>
          <td>Richtungs-Winkel, −180 bis 180. Nur Definiert.</td>
          <td><code>direction</code>. <code>null</code> bleibt <code>null</code>.</td>
          <td><code>wpml:waypointHeadingAngle</code>. In <code>waylines.wpml</code> zusätzlich <code>wpml:waypointHeadingAngleEnable</code></td>
          <td class="hint">Definiert: Winkel und Enable <code>1</code>. Alle anderen Modi: Winkel <code>0</code> und Enable <code>0</code>. Ein <code>waypointPoiPoint</code> am selben Punkt macht daraus kein Fokus POI.</td>
        </tr>
        <tr>
          <td class="grp">Gierrichtung</td>
          <td>Ausrichtung des Fluggeräts. Automatisch, Uhrzeigersinn, Gegen den Uhrzeigersinn.</td>
          <td><code>alignmentAircraft</code></td>
          <td><code>wpml:waypointHeadingPathMode</code></td>
          <td class="hint">Im lokalen Heading-Block. Automatisch ist <code>followBadArc</code>. Dazu <code>clockwise</code> und <code>counterClockwise</code>. Dazu <code>waypointHeadingPoiIndex</code> <code>0</code>.</td>
        </tr>
        <tr>
          <td class="grp">Gierrichtung</td>
          <td>Pfadmodus, gerade oder Kurve</td>
          <td><code>pathMode</code> <code>straight</code> oder <code>curve</code></td>
          <td class="none">kein KMZ-Tag</td>
          <td class="hint">Gerade oder Kurve der Strecke ist <code>wpml:useStraightLine</code> im Turn-Block und <code>wpml:globalUseStraightLine</code>, beides aus dem Wegpunkt-Typ.</td>
        </tr>
        <tr>
          <td class="grp">Gierrichtung</td>
          <td>POI</td>
          <td><code>poi</code> mit <code>longitude</code>, <code>latitude</code>, <code>height</code></td>
          <td><code>wpml:waypointPoiPoint</code></td>
          <td class="hint">Fokus POI nur bei <code>waypointHeadingMode</code> <code>towardPOI</code>. Die Fernsteuerung schreibt <code>waypointPoiPoint</code> an jedem Punkt, auch bei Definiert. Das allein ist kein Fokus POI. Typ-2-Punkte im GeoJSON sind die POI-Liste. Ein Wegpunkt nimmt zuerst <code>properties.poi</code>, sonst <code>poiIndex</code>, sonst den einzigen Typ-2-Punkt. Ohne POI bleibt die Gierrichtung <code>followWayline</code>. Mit POI steht <code>waypointPoiPoint</code> als Breite,Länge,Höhe.</td>
        </tr>
        <tr>
          <td class="grp">Höhe</td>
          <td>Auto Höhenanpassung</td>
          <td><code>autoHeight</code></td>
          <td class="none">kein KMZ-Tag</td>
          <td class="hint">Die geschriebene Höhe ist Gelände des Wegpunkts minus Gelände des ersten Wegpunkts plus <code>height</code>. <code>useGlobalHeight</code> wird <code>0</code>. Ohne Gelände bleibt die gesetzte Höhe.</td>
        </tr>
        <tr>
          <td class="grp">Mission</td>
          <td>Neigungswinkel. −90 bis 30, Standard −90. Spezifisch nur mit «Neigungswinkel setzen» und Wert ungleich −90. Dann steht eine kleine Gradzahl an der Flughöhe und der Wegpunkt ist orange.</td>
          <td><code>gimbalAngle</code>. Steuerung <code>djiPilot2.gimbalControl</code></td>
          <td><code>template.kml</code>: <code>wpml:gimbalPitchMode</code> und bei spezifisch <code>wpml:gimbalPitchAngle</code>. <code>waylines.wpml</code>: <code>wpml:waypointGimbalPitchAngle</code></td>
          <td class="hint">Standard: JSON <code>manuel</code> und <code>gimbalAngle</code> −90, KMZ <code>manual</code> ohne <code>gimbalPitchAngle</code>, in <code>waylines.wpml</code> Pitch <code>0</code>. Spezifisch: JSON <code>setEachWaypoint</code> und der Winkel, KMZ <code>usePointSetting</code> und derselbe Winkel in beiden Dateien. Die Höhe bekommt die Gradzahl, der Wegpunkt wird orange. Der gewählte Punkt bleibt gelb. Der Flugpfad wird nicht neu gerechnet.</td>
        </tr>

        <tr class="group"><td colspan="5">Mission</td></tr>
        <tr>
          <td class="grp">Mission</td>
          <td>Direkt zur Startposition. Einfacher Modus: Nein.</td>
          <td><code>flyToWaylineMode</code></td>
          <td><code>wpml:flyToWaylineMode</code></td>
          <td class="hint">Aus: <code>safely</code>, erst auf die sichere Höhe. An: <code>pointToPoint</code>.</td>
        </tr>
        <tr>
          <td class="grp">Geschwindigkeit</td>
          <td>Geschwindigkeit (m/s). Einfacher Modus: 4.5.</td>
          <td><code>djiPilot2.speed</code> und am Wegpunkt <code>speed</code>, solange das Flag <code>true</code> ist</td>
          <td><code>wpml:autoFlightSpeed</code></td>
          <td class="hint">Global in beiden Dateien. Wegpunkte mit Flag <code>true</code> übernehmen diesen Wert.</td>
        </tr>
        <tr>
          <td class="grp">Geschwindigkeit</td>
          <td>Startgeschwindigkeit einschalten. Schritt 3 Flugpfad, nur im Expertenmodus bedienbar. Einfacher Modus: Schalter aus.</td>
          <td><code>start_speed_active</code>, <code>start_speed_m_s</code>. Im JasonFile <code>transitionalSpeedActive</code>, <code>transitionalSpeed</code>.</td>
          <td><code>wpml:globalTransitionalSpeed</code>. Aktiv-Flag: <span class="none">kein KMZ-Tag</span></td>
          <td class="hint">Flag an: eigene Startgeschwindigkeit, 0.1 bis 15 m/s. Flag aus, einfacher Modus und Verlassen des Expertenmodus: die Startgeschwindigkeit ist die Fluggeschwindigkeit.</td>
        </tr>
        <tr>
          <td class="grp">Mission</td>
          <td>Endaktion. Einfacher Modus: Aufgabe beenden.</td>
          <td><code>djiPilot2.endAction</code></td>
          <td><code>wpml:finishAction</code></td>
          <td class="hint">Werte: <code>noAction</code>, <code>goHome</code>, <code>autoLand</code>, <code>gotoFirstWaypoint</code>. Fest dazu, ohne UI: <code>exitOnRCLost</code> <code>executeLostAction</code> und <code>executeRCLostAction</code> <code>goBack</code>.</td>
        </tr>
        <tr>
          <td class="grp">Gierrichtung</td>
          <td>Gierrichtung, Mission. Einfacher Modus: Richtung zum nächsten Wegpunkt.</td>
          <td><code>djiPilot2.directionMode</code> fest <code>followWayline</code></td>
          <td><code>wpml:waypointHeadingMode</code> in <code>wpml:globalWaypointHeadingParam</code></td>
          <td class="hint">Global steht der Modus. Bei Fokus POI mit gesetztem POI zusätzlich <code>waypointPoiPoint</code>.</td>
        </tr>
        <tr>
          <td class="grp">Wegpunkt-Typ</td>
          <td>Wegpunkt-Typ, Mission. Einfacher Modus: Koordinierte Drehung, überspringt den Wegpunkt.</td>
          <td><code>djiPilot2.waypointType</code></td>
          <td><code>wpml:globalWaypointTurnMode</code></td>
          <td class="hint"><code>globalUseStraightLine</code> kommt aus diesem Typ, nicht aus dem Pfadmodus.</td>
        </tr>
        <tr>
          <td class="grp">Wegpunkt-Typ</td>
          <td>Standard-Wendedistanz (m). Einfacher Modus: 5.</td>
          <td><code>curveSize</code>, <code>defaultCurveSize</code></td>
          <td><code>wpml:waypointTurnDampingDist</code></td>
          <td class="hint">Gilt an den Wegpunkten, ausser die Dämpfung wird am Ende oder ausserhalb <code>coordinateTurn</code> auf <code>0</code> gesetzt.</td>
        </tr>
        <tr>
          <td class="grp">Mission</td>
          <td>Sichere Startflughöhe. Einfacher Modus: 20 m.</td>
          <td><code>takeOffSecurityHeight</code></td>
          <td><code>wpml:takeOffSecurityHeight</code></td>
          <td class="hint">In <code>waylines.wpml</code> zusätzlich <code>wpml:globalRTHHeight</code>, gleicher Wert, mindestens 2 m.</td>
        </tr>
        <tr>
          <td class="grp">Mission</td>
          <td>Gimbalsteuerung und Flag «Neigungswinkel setzen». Einfacher Modus: Manuell, Flag aus.</td>
          <td><code>djiPilot2.gimbalControl</code></td>
          <td><code>wpml:gimbalPitchMode</code></td>
          <td class="hint">Einfacher Modus: Manuell, Schalter aus. Schreiben von JSON und KMZ steht im Abschnitt Gierrichtung und Neigung.</td>
        </tr>
        <tr>
          <td class="grp">Höhe</td>
          <td>Flughöhenmodus. Einzige Option: ALT, relativ zum Startpunkt.</td>
          <td><code>flightHeightMode</code> <code>relativeToStartPoint</code></td>
          <td><code>template.kml</code>: <code>wpml:heightMode</code>. <code>waylines.wpml</code>: <code>wpml:executeHeightMode</code></td>
          <td class="hint">Dazu fest <code>coordinateMode</code> <code>WGS84</code>, <code>positioningType</code> <code>GPS</code> und <code>caliFlightEnable</code> <code>0</code>. Dafür gibt es kein UI-Feld.</td>
        </tr>
        <tr>
          <td class="grp">Höhe</td>
          <td>Flughöhe (m), Mission</td>
          <td><code>djiPilot2.height</code></td>
          <td><code>wpml:globalHeight</code></td>
          <td class="hint">Nur in <code>template.kml</code>. <code>waylines.wpml</code> nutzt <code>executeHeight</code> pro Punkt.</td>
        </tr>
        <tr>
          <td class="grp">Mission</td>
          <td>DrohnenTyp</td>
          <td><code>djiPilot2.droneType</code>, UAV-Kürzel</td>
          <td><code>wpml:droneEnumValue</code>, <code>wpml:droneSubEnumValue</code>, <code>wpml:payloadEnumValue</code>, <code>wpml:payloadSubEnumValue</code>, <code>wpml:payloadPositionIndex</code></td>
          <td class="hint">Kürzel im JSON, Enum-Zahlen in der KMZ. Bei M30 zusätzlich <code>wpml:payloadParam</code>.</td>
        </tr>
        <tr>
          <td class="grp">Mission</td>
          <td>Einfacher Modus / Expertenmodus</td>
          <td><code>easyModeForm</code></td>
          <td class="none">kein KMZ-Tag</td>
          <td class="hint">Ein geladenes Feld mit einem <code>useMission</code>-Flag <code>false</code> bleibt im Expertenmodus. Verlassen des Expertenmodus setzt die Startgeschwindigkeit auf aus.</td>
        </tr>
        <tr>
          <td class="grp">Mission</td>
          <td>KMZ-Parameter aktiv</td>
          <td>kein Name im JasonFile</td>
          <td class="none">kein KMZ-Tag</td>
          <td class="hint">Aus: der Export nimmt die Missions-Defaults. Der Drohnentyp bleibt.</td>
        </tr>
        <tr>
          <td class="grp">Pfad</td>
          <td>Flugrichtung</td>
          <td>kein Name im JasonFile. Intern <code>flight_direction_deg</code>, <code>-1</code> ist der Vorschlag.</td>
          <td class="none">kein KMZ-Tag</td>
          <td class="hint">Nur für die Bahnberechnung. Die geflogene Richtung steht in den Wegpunktkoordinaten.</td>
        </tr>
        <tr>
          <td class="grp">Pfad</td>
          <td>Überlappung (%)</td>
          <td>kein Name im JasonFile</td>
          <td class="none">kein KMZ-Tag</td>
          <td class="hint">Nur für den Abstand der Bahnen. Steht nicht in der KMZ.</td>
        </tr>
        <tr>
          <td class="grp">Pfad</td>
          <td>Max. Randfaktor (%)</td>
          <td>kein Name im JasonFile</td>
          <td class="none">kein KMZ-Tag</td>
          <td class="hint">Nur für den Rand der Bahnplanung. Steht nicht in der KMZ.</td>
        </tr>
        <tr>
          <td class="grp">Pfad</td>
          <td>FOV</td>
          <td>kein Name im JasonFile</td>
          <td class="none">kein KMZ-Tag</td>
          <td class="hint">Sichtfeld der Drohne für Bahnabstand und Suchabdeckung. Die beiden Rechnungen stehen im Abschnitt Suchabdeckung. Keines der Winkel wird als Tag geschrieben.</td>
        </tr>
        <tr>
          <td class="grp">Anzeige</td>
          <td>Abweichung von der Sollflughöhe</td>
          <td>Kernparameter <code>agl_display_deviation_m</code>, Vorgabe 10</td>
          <td class="none">kein KMZ-Tag</td>
          <td class="hint">Roter Punkt in DESK und MOB, sobald die reale Flughöhe über Grund von der Sollflughöhe um mehr als ± diesen Wert abweicht. Getrennt von <code>agl_tolerance_m</code>, das die Bahnrechnung begrenzt.</td>
        </tr>

        <tr class="group"><td colspan="5">Geometrie</td></tr>
        <tr>
          <td class="grp">Geometrie</td>
          <td>Latitude / Longitude</td>
          <td><code>geometry.coordinates</code> Breite, dann Länge</td>
          <td><code>Point/coordinates</code> Länge, Breite. <code>wpml:index</code> ab 0</td>
          <td class="hint">Die Achsenreihenfolge dreht sich zwischen JSON und KMZ. Punkttyp 6 (Start) zählt bei den Flags nicht.</td>
        </tr>
        <tr>
          <td class="grp">Geometrie</td>
          <td>Feldumrandung</td>
          <td>LineString, <code>polygonLineString</code></td>
          <td class="none">kein KMZ-Tag</td>
          <td class="hint">Bleibt im JasonFile. Die KMZ enthält nur die Wegpunkte.</td>
        </tr>
        <tr>
          <td class="grp">Geometrie</td>
          <td>Startplatz</td>
          <td>Punkt <code>type</code> 6</td>
          <td><code>waylines.wpml</code>: <code>wpml:takeOffRefPoint</code>, <code>wpml:takeOffRefPointAGLHeight</code>. <code>template.kml</code>: <span class="none">kein KMZ-Tag</span></td>
          <td class="hint"><code>takeOffRefPoint</code> ist Breite, Länge, Höhe des ersten Wegpunkts. Beide Tags nur, wenn der Startbezug gesetzt ist.</td>
        </tr>
      </tbody>
    </table>

    <h2>Speichern</h2>
    <p>Speichern eines Feldes im Feld-Editor erzeugt immer die Missions-JSON und die KMZ und registriert beide im Feldkatalog: <code>JasonFile</code> und <code>KMZFile</code>. Der Dateiname kommt aus <code>rkrk_mission_name_parts_from_field</code>. Schlägt ein Export fehl, wird der Fehler angezeigt und der Katalog nicht ohne diese Dateien gespeichert.</p>

    <h2>Einfacher Modus</h2>
    <ul>
      <li>Direkt zum Start: Nein.</li>
      <li>Geschwindigkeit: 4.5 m/s.</li>
      <li>Endaktion: Aufgabe beenden.</li>
      <li>Gierrichtung: Richtung zum nächsten Wegpunkt.</li>
      <li>Wegpunkt-Typ: Koordinierte Drehung, überspringt den Wegpunkt.</li>
      <li>Standard-Wendedistanz: 5 m.</li>
      <li>Sichere Startflughöhe: 20 m.</li>
      <li>Startgeschwindigkeit einschalten: Nein. Die Startgeschwindigkeit ist die Fluggeschwindigkeit.</li>
      <li>Gimbal: Manuell.</li>
      <li>Flughöhe: ALT, relativ zum Startpunkt.</li>
    </ul>
    <p>Ein geladenes Feld mit einem <code>useMission</code>-Flag <code>false</code> bleibt im Expertenmodus.</p>

    <h2>Kein KMZ-Tag</h2>
    <p>Geprüft am Export <code>fe/wpml_kmz_export.php</code> und <code>build_dji_pilot2_export_context</code>. Diese UI-Felder erzeugen kein eigenes WPML-Tag:</p>
    <ul>
      <li><code>useMissionPathMode</code>, <code>pathMode</code>, <code>useMissionDirectionMode</code></li>
      <li><code>autoHeight</code></li>
      <li>Aktiv-Flag der Startgeschwindigkeit (<code>start_speed_active</code> / <code>transitionalSpeedActive</code>)</li>
      <li><code>easyModeForm</code></li>
      <li>KMZ-Parameter aktiv, Flugrichtung, Überlappung, Max. Randfaktor, FOV</li>
      <li>Feldumrandung</li>
      <li>Startplatz in <code>template.kml</code>. In <code>waylines.wpml</code> stehen <code>wpml:takeOffRefPoint</code> und <code>wpml:takeOffRefPointAGLHeight</code>.</li>
    </ul>

    <h2>Debug</h2>
    <p><code>?DEBUG</code> schreibt unter dem Feld in 11px, <code>#333333</code>, kursiv den JSON-Namen und den KMZ-Namen. Der KMZ-Export lädt zusätzlich die ungezippte <code>template.kml</code>.</p>
  </div>
</body>
</html>
