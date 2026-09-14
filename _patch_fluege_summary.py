# -*- coding: utf-8 -*-
from pathlib import Path

path = Path(r"\\tchr-frog.synology.me@ssl@5606\DavWWWRoot\web\rkropen\test\rkrk_fluege.php")
raw = path.read_bytes()
# Keep original newline style
text = raw.decode("utf-8", errors="replace")

def must_replace(src, old, new, label):
    if old not in src:
        # help debug
        snip = old[:80].replace("\n", "\\n")
        raise SystemExit(f"NOT FOUND: {label} :: {snip!r}")
    return src.replace(old, new, 1)

# CSS
old_css = (
    "  .rkrk-flight-summary-line2{\n"
    "    margin-top:4px;\n"
    "    font-size:14px;\n"
    "    line-height:1.35;\n"
    "    color:#445;\n"
    "    white-space:nowrap;\n"
    "    overflow:hidden;\n"
    "    text-overflow:ellipsis;\n"
    "  }"
)
new_css = (
    "  .rkrk-flight-summary-line2,\n"
    "  .rkrk-flight-summary-line3,\n"
    "  .rkrk-flight-summary-line4{\n"
    "    margin-top:4px;\n"
    "    font-size:14px;\n"
    "    line-height:1.35;\n"
    "    color:#445;\n"
    "  }\n"
    "  .rkrk-flight-summary-line2{\n"
    "    white-space:nowrap;\n"
    "    overflow:hidden;\n"
    "    text-overflow:ellipsis;\n"
    "  }\n"
    "  .rkrk-flight-summary-line3,\n"
    "  .rkrk-flight-summary-line4{\n"
    "    white-space:normal;\n"
    "    word-break:break-word;\n"
    "  }\n"
    "  .rkrk-flight-summary-meta{\n"
    "    color:#5b6570;\n"
    "    font-weight:500;\n"
    "  }\n"
    "  .rkrk-flight-summary-actions{\n"
    "    margin-top:8px;\n"
    "    display:flex;\n"
    "    flex-wrap:wrap;\n"
    "    align-items:center;\n"
    "    gap:8px;\n"
    "  }\n"
    "  .rkrk-flight-summary-actions a{\n"
    "    display:inline-flex;\n"
    "    align-items:center;\n"
    "  }\n"
    "  .rkrk-flight-summary-actions img{\n"
    "    height:32px;\n"
    "    width:auto;\n"
    "    vertical-align:middle;\n"
    "  }\n"
    "  .rkrk-flight-chip-kontroll{\n"
    "    background:#e3eefc;\n"
    "    color:#1a4b8c;\n"
    "  }"
)
text = must_replace(text, old_css, new_css, "css")

# Find exact JS block from file
idx = text.find("function rkrkBuildFlightSummaryHtml(line){")
if idx < 0:
    raise SystemExit("builder not found")
end = text.find("function rkrkEsc(s){", idx)
if end < 0:
    raise SystemExit("esc not found")
old_js = text[idx:end]
print("OLD JS length", len(old_js))

new_js = r'''function rkrkBuildFlightSummaryHtml(line){
  var zeit = rkrkFlightFieldValue(line, 'Zeit') || '–';
  var pilot = rkrkFlightFieldValue(line, 'Pilot') || '–';
  var feld = rkrkFlightFieldValue(line, 'Feld') || '–';
  var felder = rkrkFlightFieldValue(line, 'Felder') || '';
  var geflogen = rkrkFlightFieldValue(line, 'Geflogen');
  var fund = rkrkFlightFieldValue(line, 'Fund');
  var kontroll = rkrkFlightFieldValue(line, 'Kontrollflug');
  var jaeger = rkrkFlightFieldValue(line, 'Jaeger');
  var kontakt = rkrkFlightFieldValue(line, 'Kontaktnummer');
  var bemerkung = rkrkFlightFieldValue(line, 'Bemerkung');
  var gefClass = 'rkrk-flight-chip-geflogen-leer';
  var gefText = geflogen || '–';
  if (geflogen === 'Ja') gefClass = 'rkrk-flight-chip-geflogen-ja';
  else if (geflogen === 'Nein') gefClass = 'rkrk-flight-chip-geflogen-nein';
  var fundClass = fund ? 'rkrk-flight-chip-fund' : 'rkrk-flight-chip-fund-leer';
  var fundText = fund ? ('Fund ' + fund) : 'Fund –';
  var kontrollChip = '';
  if (kontroll) {
    kontrollChip = '<span class=\"rkrk-flight-chip rkrk-flight-chip-kontroll\">Ktrl ' + rkrkEsc(kontroll) + '</span>';
  }
  var sub = felder ? felder : '—';
  var jaegerFull = kontakt || jaeger || '—';
  var bemLine = bemerkung ? ('Bemerkung: ' + bemerkung) : '';
  return '<div class=\"rkrk-flight-summary-line1\">'
    + '<span>' + rkrkEsc(zeit) + '</span>'
    + '<span>' + rkrkEsc(pilot) + '</span>'
    + '<span>' + rkrkEsc(feld) + '</span>'
    + '<span class=\"rkrk-flight-chip ' + gefClass + '\">' + rkrkEsc(gefText) + '</span>'
    + '<span class=\"rkrk-flight-chip ' + fundClass + '\">' + rkrkEsc(fundText) + '</span>'
    + kontrollChip
    + '</div>'
    + '<div class=\"rkrk-flight-summary-line2\">' + rkrkEsc(sub) + '</div>'
    + '<div class=\"rkrk-flight-summary-line3\"><span class=\"rkrk-flight-summary-meta\">Jäger:</span> ' + rkrkEsc(jaegerFull) + '</div>'
    + (bemLine ? ('<div class=\"rkrk-flight-summary-line4\">' + rkrkEsc(bemLine) + '</div>') : '')
    + '<div class=\"rkrk-flight-summary-actions\" data-line=\"' + rkrkEsc(line) + '\"></div>';
}
function rkrkFillFlightSummaryActions(row, box){
  if (!row || !box) return;
  var host = box.querySelector('.rkrk-flight-summary-actions');
  if (!host) return;
  host.innerHTML = '';
  var seen = {};
  var sources = row.querySelectorAll('td[data-summary-action=\"1\"] a');
  for (var i = 0; i < sources.length; i++) {
    var a = sources[i];
    var href = String(a.getAttribute('href') || '');
    var key = href + '|' + String(a.getAttribute('title') || '');
    if (!href || seen[key]) continue;
    seen[key] = 1;
    var clone = a.cloneNode(true);
    clone.classList.add('rkrk-summary-action-link');
    host.appendChild(clone);
  }
}
'''
text = text[:idx] + new_js + text[end:]

# refresh
old_refresh = "  if (!box) return;\n  box.innerHTML = rkrkBuildFlightSummaryHtml(line);\n}"
new_refresh = (
    "  if (!box) return;\n"
    "  box.innerHTML = rkrkBuildFlightSummaryHtml(line);\n"
    "  rkrkFillFlightSummaryActions(row, box);\n"
    "}"
)
text = must_replace(text, old_refresh, new_refresh, "refresh")

# primaryLabels
old_labels = (
    "  var primaryLabels = {\n"
    "    'Datum*':1,'Datum':1,'Zeit':1,'Pilot':1,'Feld*':1,'Feld':1,\n"
    "    'Felder':1,'Geflogen':1,'Fund':1\n"
    "  };"
)
new_labels = (
    "  var primaryLabels = {\n"
    "    'Datum*':1,'Datum':1,'Zeit':1,'Pilot':1,'Feld*':1,'Feld':1,\n"
    "    'Felder':1,'Geflogen':1,'Fund':1,'Kontrollflug':1,\n"
    "    'Jaeger':1,'Kontaktnummer':1,'Bemerkung':1\n"
    "  };"
)
text = must_replace(text, old_labels, new_labels, "labels")

# change/input listeners – extract exact from file
# Search for the pattern as it appears in file (double-escaped d)
chg_needle = "var m = String(t.id).match(/^(Datum|Zeit|Pilot|Feld|Felder|Geflogen|Fund)-"
ci = text.find(chg_needle)
if ci < 0:
    raise SystemExit("change needle missing")
# show exact slice
print("CHANGE SLICE:", repr(text[ci:ci+120]))
old_chg = text[ci:text.find(";", ci)+1]
new_chg = "var m = String(t.id).match(/^(Datum|Zeit|Pilot|Feld|Felder|Geflogen|Fund|Kontrollflug|Jaeger|Kontaktnummer|Bemerkung)-(\\\\d+)$/);"
# Wait - need same escaping as file. If file has \\d then we need \\\\d in replacement written as...
# File content characters for the regex end: - ( \ \ d + ) $ / )
# So in Python string we want: -(\\d+)$/);  meaning chars: - ( \ \ d + ) $ / )
new_chg = "var m = String(t.id).match(/^(Datum|Zeit|Pilot|Feld|Felder|Geflogen|Fund|Kontrollflug|Jaeger|Kontaktnummer|Bemerkung)-(\\\\d+)$/);"
text = text[:ci] + new_chg + text[ci+len(old_chg):]
print("replaced change:", old_chg, "=>", new_chg)

inp_needle = "var m = String(t.id).match(/^(Zeit|Felder|Fund)-"
ii = text.find(inp_needle)
if ii < 0:
    raise SystemExit("input needle missing")
old_inp = text[ii:text.find(";", ii)+1]
new_inp = "var m = String(t.id).match(/^(Zeit|Felder|Fund|Kontaktnummer|Bemerkung)-(\\\\d+)$/);"
text = text[:ii] + new_inp + text[ii+len(old_inp):]
print("replaced input")

# click handler
old_click = (
    "  table.addEventListener('click', function(ev){\n"
    "    if (!window.matchMedia('(max-width: 1300px)').matches) return;\n"
    "    var summary = ev.target.closest ? ev.target.closest('.rkrk-flight-summary') : null;\n"
    "    if (!summary) return;\n"
    "    ev.preventDefault();\n"
    "    var row = summary.closest('.rkrk-flight-data-row');\n"
    "    if (row) rkrkOpenFlightEdit(row);\n"
    "  });"
)
new_click = (
    "  table.addEventListener('click', function(ev){\n"
    "    if (!window.matchMedia('(max-width: 1300px)').matches) return;\n"
    "    if (ev.target.closest && ev.target.closest('.rkrk-summary-action-link, .rkrk-flight-summary-actions a')) {\n"
    "      ev.stopPropagation();\n"
    "      return;\n"
    "    }\n"
    "    var summary = ev.target.closest ? ev.target.closest('.rkrk-flight-summary') : null;\n"
    "    if (!summary) return;\n"
    "    ev.preventDefault();\n"
    "    var row = summary.closest('.rkrk-flight-data-row');\n"
    "    if (row) rkrkOpenFlightEdit(row);\n"
    "  });"
)
text = must_replace(text, old_click, new_click, "click")

# PHP markers
old_start = "\t\t\techo \"<td class='rkrk-col-secondary'>\" .chr(10);\n\t\t\tif (GetAccess(\"GoogleMapLocation\")){"
new_start = "\t\t\techo \"<td class='rkrk-col-secondary' data-summary-action='1'>\" .chr(10);\n\t\t\tif (GetAccess(\"GoogleMapLocation\")){"
text = must_replace(text, old_start, new_start, "start td")

old_file = "\t\t\tforeach ($FluegeFileSettings as $FileKey => $FileType) {\n\t\t\t\techo \"<td class='rkrk-col-secondary'>\" .chr(10);"
new_file = "\t\t\tforeach ($FluegeFileSettings as $FileKey => $FileType) {\n\t\t\t\techo \"<td class='rkrk-col-secondary' data-summary-action='1'>\" .chr(10);"
text = must_replace(text, old_file, new_file, "file td")

path.write_bytes(text.encode("utf-8", errors="replace"))
print("OK")
