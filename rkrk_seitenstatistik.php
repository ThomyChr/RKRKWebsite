<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de">
<?php
session_start();
include_once('scripts/rkrk_gen.php');
include_once('scripts/rkrk_csvfiles.php');
include_once('scripts/rkrk_inputforms.php');
include_once('scripts/rkrk_googlechart.php');
include_once('scripts/rkrk_menu.php');

CheckIPandExit();

$pagetitle = 'Seitenstatistik';
headerpage($pagetitle, false);
CreateTopMenu();
PrintRKRKHeader($pagetitle);

SetMissionLocation();
WriteToLog('get', basename($_SERVER['PHP_SELF']), 'Seitenstatistik', '', '', '');

function ss_h($v)
{
	return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function ss_ym_parts($ym)
{
	$ym = preg_replace('/\D+/', '', (string)$ym);
	if (strlen($ym) < 6) {
		return array(0, 0);
	}

	return array((int)substr($ym, 0, 4), (int)substr($ym, 4, 2));
}

function ss_quarter($month)
{
	$m = (int)$month;
	if ($m < 1 || $m > 12) {
		return 0;
	}

	return (int)ceil($m / 3);
}

function ss_js_table(array $rows)
{
	$out = array();
	foreach ($rows as $ri => $line) {
		$cells = array();
		foreach ($line as $ci => $v) {
			if ($ri === 0 || $ci === 0) {
				$cells[] = json_encode((string)$v, JSON_UNESCAPED_UNICODE);
			} else {
				$cells[] = (string)(int)round((float)$v);
			}
		}
		$out[] = '[' . implode(',', $cells) . ']';
	}

	return '[' . implode(',', $out) . ']';
}

function ss_fmt_int($n)
{
	return number_format((int)$n, 0, '.', "'");
}

$raw = function_exists('rkrk_stats_load_site_rows') ? rkrk_stats_load_site_rows() : array();
if (!is_array($raw)) {
	$raw = array();
}

$allYears = array();
$allLevels = array();
$parsed = array();
foreach ($raw as $row) {
	if (!is_array($row)) {
		continue;
	}
	list($y, $m) = ss_ym_parts($row['Monat'] ?? '');
	if ($y < 2000 || $m < 1 || $m > 12) {
		continue;
	}
	$page = trim((string)($row['Seite'] ?? ''));
	if ($page === '') {
		continue;
	}
	$page = basename(str_replace('\\', '/', $page));
	$level = strtolower(trim((string)($row['Level'] ?? '')));
	if ($level === '') {
		$level = '?';
	}
	$n = (int)($row['Anzahl'] ?? 0);
	if ($n < 0) {
		$n = 0;
	}
	$parsed[] = array(
		'y' => $y,
		'm' => $m,
		'q' => ss_quarter($m),
		'page' => $page,
		'level' => $level,
		'n' => $n,
	);
	$allYears[$y] = $y;
	$allLevels[$level] = $level;
}
krsort($allYears, SORT_NUMERIC);
ksort($allLevels, SORT_STRING);

$jahrNow = (int)date('Y');
$jahr = isset($_GET['Jahr']) ? trim((string)$_GET['Jahr']) : (string)$jahrNow;
if ($jahr === 'all' || $jahr === '0') {
	$jahr = 'all';
} else {
	$jahr = (string)(int)$jahr;
	if ((int)$jahr < 2000) {
		$jahr = isset($allYears[$jahrNow]) ? (string)$jahrNow : (string)(int)reset($allYears);
		if ($jahr === '0' || $jahr === '') {
			$jahr = (string)$jahrNow;
		}
	}
}

$quartal = isset($_GET['Quartal']) ? trim((string)$_GET['Quartal']) : 'all';
if (!in_array($quartal, array('all', '1', '2', '3', '4'), true)) {
	$quartal = 'all';
}

$levelSel = array();
if (isset($_GET['Level'])) {
	$lvIn = $_GET['Level'];
	if (is_array($lvIn)) {
		$levelSel = $lvIn;
	} else {
		$levelSel = (((string)$lvIn === '' || (string)$lvIn === 'all') ? array() : explode(',', (string)$lvIn));
	}
}
$levelSel = array_values(array_unique(array_filter(array_map('strtolower', array_map('trim', $levelSel)))));
$pageQ = isset($_GET['Seite']) ? trim((string)$_GET['Seite']) : '';

$filtered = array();
foreach ($parsed as $row) {
	if ($jahr !== 'all' && (int)$row['y'] !== (int)$jahr) {
		continue;
	}
	if ($quartal !== 'all' && (int)$row['q'] !== (int)$quartal) {
		continue;
	}
	if ($levelSel !== array() && !in_array($row['level'], $levelSel, true)) {
		continue;
	}
	if ($pageQ !== '' && stripos($row['page'], $pageQ) === false) {
		continue;
	}
	$filtered[] = $row;
}

$sumHits = 0;
$byMonth = array();
$byQuarter = array();
$byYear = array();
$byPage = array();
$byLevel = array();
$byYearQuarter = array();
foreach ($filtered as $row) {
	$sumHits += $row['n'];
	$mk = sprintf('%04d-%02d', $row['y'], $row['m']);
	$qk = $row['y'] . '-Q' . $row['q'];
	$byMonth[$mk] = (isset($byMonth[$mk]) ? $byMonth[$mk] : 0) + $row['n'];
	$byQuarter[$qk] = (isset($byQuarter[$qk]) ? $byQuarter[$qk] : 0) + $row['n'];
	$byYear[$row['y']] = (isset($byYear[$row['y']]) ? $byYear[$row['y']] : 0) + $row['n'];
	$byPage[$row['page']] = (isset($byPage[$row['page']]) ? $byPage[$row['page']] : 0) + $row['n'];
	$byLevel[$row['level']] = (isset($byLevel[$row['level']]) ? $byLevel[$row['level']] : 0) + $row['n'];
	$yq = $row['y'] . ':' . $row['q'];
	$byYearQuarter[$yq] = (isset($byYearQuarter[$yq]) ? $byYearQuarter[$yq] : 0) + $row['n'];
}
arsort($byPage, SORT_NUMERIC);
arsort($byLevel, SORT_NUMERIC);
ksort($byMonth, SORT_STRING);
ksort($byQuarter, SORT_STRING);
ksort($byYear, SORT_NUMERIC);

$nPages = count($byPage);
$nMonths = count($byMonth);
$topPage = $byPage !== array() ? (string)array_key_first($byPage) : '—';
$topPageHits = $byPage !== array() ? (int)reset($byPage) : 0;

$cGreen = array('#48673A', '#527642', '#6A9955', '#96B588', '#A0C291', '#C5E0B4');
$cRed = array('#7A201C', '#922722', '#B3322C', '#C23C35', '#F84941');
$barColors = "['" . implode("','", array_merge($cGreen, $cRed, $cGreen)) . "']";
$chartH = 340;
$chartW = 720;

$self = htmlspecialchars((string)($_SERVER['PHP_SELF'] ?? 'rkrk_seitenstatistik.php'), ENT_QUOTES, 'UTF-8');

function ss_qs(array $over = [])
{
	$base = array(
		'Jahr' => isset($_GET['Jahr']) ? (string)$_GET['Jahr'] : '',
		'Quartal' => isset($_GET['Quartal']) ? (string)$_GET['Quartal'] : 'all',
		'Seite' => isset($_GET['Seite']) ? (string)$_GET['Seite'] : '',
	);
	if (isset($_GET['Level'])) {
		$base['Level'] = $_GET['Level'];
	}
	$merged = array_merge($base, $over);
	$clean = array();
	foreach ($merged as $k => $v) {
		if ($v === null || $v === '' || $v === array()) {
			continue;
		}
		if (is_array($v)) {
			foreach ($v as $vv) {
				if ((string)$vv !== '') {
					$clean[$k][] = (string)$vv;
				}
			}
			continue;
		}
		$clean[$k] = (string)$v;
	}

	return http_build_query($clean);
}

$periodLabel = ($jahr === 'all' ? 'alle Jahre' : $jahr);
if ($quartal !== 'all') {
	$periodLabel .= ' Q' . $quartal;
} else {
	$periodLabel .= ' · ganzes Jahr';
}
?>
<style>
.ss-wrap { max-width: 1440px; }
.ss-filter {
	display: flex; flex-wrap: wrap; gap: 0.65rem 0.9rem; align-items: end;
	margin: 0 0 1rem; padding: 0.75rem 0;
}
.ss-filter label { display: flex; flex-direction: column; gap: 0.2rem; font-size: 0.88rem; }
.ss-filter select, .ss-filter input[type=text] {
	min-width: 9rem; padding: 0.35rem 0.5rem; font: inherit;
}
.ss-pills { display: flex; flex-wrap: wrap; gap: 0.35rem; align-items: center; }
.ss-pill {
	display: inline-flex; align-items: center; gap: 0.25rem;
	padding: 0.32rem 0.7rem; border: 1px solid #c9d4c5; border-radius: 999px;
	background: #fff; color: #2d4024; text-decoration: none; font-weight: 600; font-size: 0.85rem;
}
.ss-pill.is-on { background: #e8f0e4; border-color: #4d6b3e; color: #2d4024; }
.ss-hint { font-size: 0.9rem; margin: 0 0 1rem; opacity: 0.85; }
.rkrk-stat-kpis {
	display: grid; grid-template-columns: repeat(4, minmax(0, 1fr));
	gap: 12px; margin: 0 0 16px; max-width: 920px;
}
.rkrk-stat-kpi {
	background: #fff; border: 1px solid #e4e8ee; border-radius: 12px;
	padding: 14px 12px 12px; text-align: center;
}
.rkrk-stat-kpi .lbl { font-size: 13px; color: #5b6570; }
.rkrk-stat-kpi .val { font-size: 34px; font-weight: 700; line-height: 1.15; margin-top: 4px; color: #48673A; }
.rkrk-stat-kpi .sub { font-size: 12px; color: #5b6570; margin-top: 4px; word-break: break-all; }
.rkrk-stat-charts {
	display: grid; grid-template-columns: repeat(2, minmax(0, 1fr));
	gap: 20px 24px; width: 100%; max-width: 1440px; align-items: start;
}
.rkrk-stat-cell { min-width: 0; }
.rkrk-stat-cell-span { grid-column: 1 / -1; }
.ss-table-wrap { overflow: auto; max-height: 28rem; margin: 0 0 1.2rem; }
.ss-table { border-collapse: collapse; width: 100%; font-size: 0.92rem; }
.ss-table th, .ss-table td { border-bottom: 1px solid #e4e8ee; padding: 0.35rem 0.5rem; text-align: left; }
.ss-table th { position: sticky; top: 0; background: #eef5ec; }
.ss-table td.num, .ss-table th.num { text-align: right; font-variant-numeric: tabular-nums; }
.ss-table tr:nth-child(even) td { background: #f7faf6; }
@media (max-width: 960px) {
	.rkrk-stat-charts { grid-template-columns: minmax(0, 1fr); }
}
@media (max-width: 720px) {
	.rkrk-stat-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
	.rkrk-stat-kpi .val { font-size: 26px; }
}
</style>

<div class="ss-wrap">
<p class="ss-hint">Zugriffe nach <strong>Jahr</strong> und <strong>Quartal</strong>. Speicherung dauerhaft in <code>sitestats_JJJJ.csv</code> (eine Datei pro Jahr). Erfasst werden Seiten mit <code>WriteToLog</code> sowie jeder Zugriff auf den <strong>Missionenkatalog</strong> oder die <strong>Adressen</strong> (ein Eintrag pro Aufruf).</p>

<form method="get" action="<?= $self ?>" class="ss-filter" id="ss_filter">
	<label>Jahr
		<select name="Jahr" onchange="this.form.submit()">
			<option value="all"<?= $jahr === 'all' ? ' selected' : '' ?>>alle Jahre</option>
			<?php foreach ($allYears as $y): ?>
				<option value="<?= (int)$y ?>"<?= $jahr === (string)$y ? ' selected' : '' ?>><?= (int)$y ?></option>
			<?php endforeach; ?>
		</select>
	</label>
	<div>
		<div class="ss-pills" role="group" aria-label="Quartal">
			<?php foreach (array('all' => 'Ganzes Jahr', '1' => 'Q1', '2' => 'Q2', '3' => 'Q3', '4' => 'Q4') as $qk => $ql): ?>
				<a class="ss-pill<?= $quartal === $qk ? ' is-on' : '' ?>" href="<?= $self ?>?<?= ss_h(ss_qs(array('Quartal' => $qk))) ?>"><?= ss_h($ql) ?></a>
			<?php endforeach; ?>
		</div>
	</div>
	<label>Seite
		<input type="text" name="Seite" value="<?= ss_h($pageQ) ?>" placeholder="enthält…">
	</label>
	<div>
		<div class="ss-pills" aria-label="Level">
			<?php
			$levelAllOn = ($levelSel === array());
			echo '<a class="ss-pill' . ($levelAllOn ? ' is-on' : '') . '" href="' . $self . '?' . ss_h(ss_qs(array('Level' => ''))) . '">alle Level</a>';
			foreach ($allLevels as $lv) {
				$on = in_array($lv, $levelSel, true);
				$next = $levelSel;
				if ($on) {
					$next = array_values(array_diff($next, array($lv)));
				} else {
					$next[] = $lv;
				}
				echo '<a class="ss-pill' . ($on ? ' is-on' : '') . '" href="' . $self . '?' . ss_h(ss_qs(array('Level' => $next))) . '">' . ss_h($lv) . '</a>';
			}
			?>
		</div>
	</div>
	<button type="submit" class="ss-pill is-on">Filtern</button>
	<input type="hidden" name="Quartal" value="<?= ss_h($quartal) ?>">
	<?php foreach ($levelSel as $lv): ?>
		<input type="hidden" name="Level[]" value="<?= ss_h($lv) ?>">
	<?php endforeach; ?>
</form>

<?php if ($parsed === array()): ?>
	<p>Keine Statistikdatei oder noch keine Einträge unter <code><?= ss_h($csvFile) ?></code>.</p>
<?php elseif ($filtered === array()): ?>
	<p>Keine Zugriffe für <?= ss_h($periodLabel) ?><?= $pageQ !== '' ? ' / Seite «' . ss_h($pageQ) . '»' : '' ?>.</p>
<?php else: ?>
	<div class="rkrk-stat-kpis">
		<div class="rkrk-stat-kpi"><div class="lbl">Aufrufe</div><div class="val"><?= ss_h(ss_fmt_int($sumHits)) ?></div><div class="sub"><?= ss_h($periodLabel) ?></div></div>
		<div class="rkrk-stat-kpi"><div class="lbl">Seiten</div><div class="val"><?= ss_h(ss_fmt_int($nPages)) ?></div><div class="sub">mit Zugriff</div></div>
		<div class="rkrk-stat-kpi"><div class="lbl">Monate</div><div class="val"><?= ss_h(ss_fmt_int($nMonths)) ?></div><div class="sub">im Filter</div></div>
		<div class="rkrk-stat-kpi"><div class="lbl">Top-Seite</div><div class="val"><?= ss_h(ss_fmt_int($topPageHits)) ?></div><div class="sub"><?= ss_h($topPage) ?></div></div>
	</div>

	<div class="rkrk-stat-charts">
<?php
	$monthRows = array(array('Monat', 'Aufrufe'));
	if ($jahr !== 'all' && $quartal === 'all') {
		for ($m = 1; $m <= 12; $m++) {
			$mk = sprintf('%04d-%02d', (int)$jahr, $m);
			$monthRows[] = array($mk, isset($byMonth[$mk]) ? $byMonth[$mk] : 0);
		}
	} else {
		foreach ($byMonth as $mk => $n) {
			$monthRows[] = array($mk, $n);
		}
	}
	if (count($monthRows) > 1) {
		echo '<div class="rkrk-stat-cell">';
		echo '<div id="ssChartMonth" class="rkrk-chart-host" style="width:100%;max-width:' . $chartW . 'px;height:' . $chartH . 'px;"></div>';
		echo createColumnChart('ssChartMonth', 'Aufrufe pro Monat', ss_js_table($monthRows), $barColors, 'Monat', 'Aufrufe', $chartH, $chartW, false, true);
		echo '</div>';
	}

	$qRows = array(array('Quartal', 'Aufrufe'));
	if ($jahr !== 'all') {
		for ($q = 1; $q <= 4; $q++) {
			$qk = (int)$jahr . '-Q' . $q;
			$qRows[] = array('Q' . $q, isset($byQuarter[$qk]) ? $byQuarter[$qk] : 0);
		}
	} else {
		foreach ($byQuarter as $qk => $n) {
			$qRows[] = array($qk, $n);
		}
	}
	if (count($qRows) > 1) {
		echo '<div class="rkrk-stat-cell">';
		echo '<div id="ssChartQ" class="rkrk-chart-host" style="width:100%;max-width:' . $chartW . 'px;height:' . $chartH . 'px;"></div>';
		echo createColumnChart('ssChartQ', 'Aufrufe pro Quartal', ss_js_table($qRows), $barColors, 'Quartal', 'Aufrufe', $chartH, $chartW, false, true);
		echo '</div>';
	}

	if ($jahr === 'all' && $byYear !== array()) {
		$yRows = array(array('Jahr', 'Aufrufe'));
		foreach ($byYear as $y => $n) {
			$yRows[] = array((string)$y, $n);
		}
		echo '<div class="rkrk-stat-cell">';
		echo '<div id="ssChartY" class="rkrk-chart-host" style="width:100%;max-width:' . $chartW . 'px;height:' . $chartH . 'px;"></div>';
		echo createColumnChart('ssChartY', 'Aufrufe pro Jahr', ss_js_table($yRows), $barColors, 'Jahr', 'Aufrufe', $chartH, $chartW, false, true);
		echo '</div>';
	}

	if ($byLevel !== array()) {
		$lvRows = array(array('Level', 'Aufrufe'));
		foreach ($byLevel as $lv => $n) {
			$lvRows[] = array($lv, $n);
		}
		echo '<div class="rkrk-stat-cell">';
		echo '<div id="ssChartLv" class="rkrk-chart-host" style="width:100%;max-width:' . $chartW . 'px;height:' . $chartH . 'px;"></div>';
		echo createPieChart('ssChartLv', 'Aufrufe nach Level', ss_js_table($lvRows), $barColors, $chartH, $chartW);
		echo '</div>';
	}

	$topN = array_slice($byPage, 0, 18, true);
	if ($topN !== array()) {
		$pRows = array(array('Seite', 'Aufrufe'));
		foreach ($topN as $p => $n) {
			$pRows[] = array($p, $n);
		}
		$barH = max($chartH, 24 * count($pRows) + 80);
		echo '<div class="rkrk-stat-cell rkrk-stat-cell-span">';
		echo '<div id="ssChartPages" class="rkrk-chart-host" style="width:100%;max-width:1440px;height:' . $barH . 'px;"></div>';
		echo createBarChart('ssChartPages', 'Meistbesuchte Seiten', ss_js_table($pRows), $barColors, 'Aufrufe', 'Seite', $barH, 1100, false, true);
		echo '</div>';
	}
?>
	</div>

	<h2>Seiten <?= ss_h($periodLabel) ?></h2>
	<div class="ss-table-wrap">
		<table class="ss-table">
			<thead>
				<tr><th>Seite</th><th class="num">Aufrufe</th><th class="num">Anteil</th></tr>
			</thead>
			<tbody>
			<?php foreach ($byPage as $p => $n): ?>
				<tr>
					<td><?= ss_h($p) ?></td>
					<td class="num"><?= ss_h(ss_fmt_int($n)) ?></td>
					<td class="num"><?= $sumHits > 0 ? ss_h(number_format(100.0 * $n / $sumHits, 1, '.', '')) . '%' : '' ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
			<tfoot>
				<tr><th>Total</th><th class="num"><?= ss_h(ss_fmt_int($sumHits)) ?></th><th class="num">100%</th></tr>
			</tfoot>
		</table>
	</div>
<?php endif; ?>
</div>
</body>
</html>
