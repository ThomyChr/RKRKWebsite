# -*- coding: utf-8 -*-
import pathlib

p = pathlib.Path(r"\\tchr-frog.synology.me@ssl@5606\DavWWWRoot\web\rkropen\test\rkrk_checklist.php")
text = p.read_text(encoding="utf-8")

replacements = [
    (
        "$perField[$fid] = array('flown' => false, 'fund' => 0, 'remark' => '', 'touched' => false);",
        "$perField[$fid] = array('flown' => false, 'not_flown' => false, 'fund' => 0, 'remark' => '', 'touched' => false);",
        2,
    ),
    (
        """\t\t\tif (!empty($st['flown'])) {
\t\t\t\t$out[$nk]['flown'] = true;
\t\t\t}
\t\t\t$out[$nk]['fund'] = max(intval($out[$nk]['fund'] ?? 0), intval($st['fund'] ?? 0));""",
        """\t\t\tif (!empty($st['flown'])) {
\t\t\t\t$out[$nk]['flown'] = true;
\t\t\t}
\t\t\tif (!empty($st['not_flown'])) {
\t\t\t\t$out[$nk]['not_flown'] = true;
\t\t\t}
\t\t\t$out[$nk]['fund'] = max(intval($out[$nk]['fund'] ?? 0), intval($st['fund'] ?? 0));""",
        1,
    ),
    (
        """\t\t\tif (in_array('nicht geflogen (r)', $vals, true)) {
\t\t\t\t$perField[$fid]['flown'] = false;
\t\t\t\t$result['entries'][$ekey]['flown'] = false;
\t\t\t}""",
        """\t\t\tif (in_array('nicht geflogen (r)', $vals, true)) {
\t\t\t\t$perField[$fid]['flown'] = false;
\t\t\t\t$perField[$fid]['not_flown'] = true;
\t\t\t\t$result['entries'][$ekey]['flown'] = false;
\t\t\t}""",
        1,
    ),
    (
        """\t\t\t$perField[$fid] = array('flown' => false, 'fund' => 0, 'remark' => '', 'touched' => false);
\t\t\t}
\t\t\t$perField[$fid]['touched'] = true;
\t\t\t$checked = (isset($row['checked']) && is_array($row['checked'])) ? $row['checked'] : array();""",
        """\t\t\t$perField[$fid] = array('flown' => false, 'not_flown' => false, 'fund' => 0, 'remark' => '', 'touched' => false);
\t\t\t}
\t\t\t$perField[$fid]['touched'] = true;
\t\t\t$checked = (isset($row['checked']) && is_array($row['checked'])) ? $row['checked'] : array();""",
        1,
    ),
    (
        """\t\t\tif (in_array('nicht geflogen (r)', $checked, true)) {
\t\t\t\t$perField[$fid]['flown'] = false;
\t\t\t}""",
        """\t\t\tif (in_array('nicht geflogen (r)', $checked, true)) {
\t\t\t\t$perField[$fid]['flown'] = false;
\t\t\t\t$perField[$fid]['not_flown'] = true;
\t\t\t}""",
        1,
    ),
    (
        """\t\t$fundN = intval($pf['fund'] ?? 0);
\t\t$flown = !empty($pf['flown']);
\t\tif (!$flown && $fundN > 0) {
\t\t\t$flown = true;
\t\t}
\t\t$hasResult = !empty($pf['touched']) || $flown || $fundN > 0 || trim((string)($pf['remark'] ?? '')) !== '';
\t\tif (!$hasResult) {
\t\t\treturn false;
\t\t}
\t\t$matched++;
\t\t$rows[$idx]['Geflogen'] = $flown ? 'Ja' : 'Nein';""",
        """\t\t$fundN = intval($pf['fund'] ?? 0);
\t\t$flown = !empty($pf['flown']);
\t\t$notFlown = !empty($pf['not_flown']);
\t\tif (!$flown && $fundN > 0) {
\t\t\t$flown = true;
\t\t}
\t\t$hasResult = !empty($pf['touched']) || $flown || $notFlown || $fundN > 0 || trim((string)($pf['remark'] ?? '')) !== '';
\t\tif (!$hasResult) {
\t\t\treturn false;
\t\t}
\t\t$matched++;
\t\tif ($flown) {
\t\t\t$rows[$idx]['Geflogen'] = 'Ja';
\t\t} elseif ($notFlown) {
\t\t\t$rows[$idx]['Geflogen'] = 'Nein';
\t\t}""",
        1,
    ),
]

for old, new, count in replacements:
    if text.count(old) < count:
        raise SystemExit(f'pattern not found ({count} expected): {old[:80]!r}...')
    text = text.replace(old, new, count)

p.write_text(text, encoding='utf-8')
print('patched ok')
