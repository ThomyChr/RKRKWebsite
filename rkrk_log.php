<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de">
<?php
session_start();
include_once('scripts/rkrk_gen.php');
include_once('scripts/rkrk_csvfiles.php');
include_once('scripts/rkrk_inputforms.php');
include_once('scripts/rkrk_menu.php');

CheckIPandExit();

$pagetitle = 'Logfiles';
headerpage($pagetitle, false);
CreateTopMenu();
PrintRKRKHeader($pagetitle);

SetMissionLocation();
WriteToLog('get', basename($_SERVER['PHP_SELF']), 'Logfiles', '', '', '');

function log_h($v)
{
	return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function log_cell(array $row, $names)
{
	if (!is_array($names)) {
		$names = array($names);
	}
	foreach ($names as $name) {
		if (isset($row[$name]) && trim((string)$row[$name]) !== '') {
			return trim((string)$row[$name]);
		}
	}

	return '';
}

function log_day_from_file($path)
{
	if (preg_match('/(\d{8})_logfile\.csv$/i', basename((string)$path), $m)) {
		return $m[1];
	}

	return '';
}

function log_day_label($yyyymmdd)
{
	if (!preg_match('/^(\d{4})(\d{2})(\d{2})$/', (string)$yyyymmdd, $m)) {
		return (string)$yyyymmdd;
	}

	return $m[3] . '.' . $m[2] . '.' . $m[1];
}

$files = glob($logfilelocation . '*_logfile.csv');
if (!is_array($files)) {
	$files = array();
}
$byDay = array();
foreach ($files as $f) {
	$day = log_day_from_file($f);
	if ($day !== '') {
		$byDay[$day] = $f;
	}
}
krsort($byDay, SORT_STRING);

$today = date('Ymd');
$selDay = isset($_GET['Tag']) ? preg_replace('/\D+/', '', (string)$_GET['Tag']) : $today;
if ($selDay === '' || !isset($byDay[$selDay])) {
	$selDay = $byDay !== array() ? (string)array_key_first($byDay) : $today;
}

$qSeite = isset($_GET['Seite']) ? trim((string)$_GET['Seite']) : '';
$qUser = isset($_GET['Wer']) ? trim((string)$_GET['Wer']) : '';
$qLevel = isset($_GET['Level']) ? trim((string)$_GET['Level']) : '';
$qTyp = isset($_GET['Typ']) ? trim((string)$_GET['Typ']) : '';
$qText = isset($_GET['Text']) ? trim((string)$_GET['Text']) : '';

$self = htmlspecialchars((string)($_SERVER['PHP_SELF'] ?? 'rkrk_log.php'), ENT_QUOTES, 'UTF-8');

$rows = array();
$levels = array();
$typs = array();
$selFile = isset($byDay[$selDay]) ? $byDay[$selDay] : '';
if ($selFile !== '' && is_readable($selFile)) {
	$raw = csv_to_array($selFile, ';');
	if (!is_array($raw)) {
		$raw = array();
	}
	foreach ($raw as $line) {
		if (!is_array($line)) {
			continue;
		}
		$ip = log_cell($line, array('IP Adresse', 'IPAdresse', 'TCPIP'));
		$user = log_cell($line, array('User', 'Benutzer', 'Who'));
		if ($user === '' && $ip !== '' && isset($IPUsed[$ip]['Name'])) {
			$user = trim((string)$IPUsed[$ip]['Name']);
		}
		$row = array(
			'zeit' => log_cell($line, array('Zeit')),
			'level' => log_cell($line, array('Level')),
			'ip' => $ip,
			'user' => $user,
			'typ' => log_cell($line, array('Typ', 'Type')),
			'seite' => log_cell($line, array('Seite')),
			'text' => log_cell($line, array('Text')),
			'param' => log_cell($line, array('Parameter')),
			'text3' => log_cell($line, array('Text3')),
			'device' => log_cell($line, array('Device', 'Client')),
		);
		if ($row['level'] !== '') {
			$levels[$row['level']] = $row['level'];
		}
		if ($row['typ'] !== '') {
			$typs[$row['typ']] = $row['typ'];
		}
		if ($qSeite !== '' && stripos($row['seite'], $qSeite) === false) {
			continue;
		}
		if ($qUser !== '' && stripos($row['user'] . ' ' . $row['ip'], $qUser) === false) {
			continue;
		}
		if ($qLevel !== '' && strcasecmp($row['level'], $qLevel) !== 0) {
			continue;
		}
		if ($qTyp !== '' && strcasecmp($row['typ'], $qTyp) !== 0) {
			continue;
		}
		if ($qText !== '' && stripos($row['text'] . ' ' . $row['param'] . ' ' . $row['text3'], $qText) === false) {
			continue;
		}
		$rows[] = $row;
	}
	usort($rows, static function ($a, $b) {
		return strcmp((string)$b['zeit'], (string)$a['zeit']);
	});
}
ksort($levels, SORT_STRING);
ksort($typs, SORT_STRING);

function log_qs(array $over = array())
{
	$base = array(
		'Tag' => isset($_GET['Tag']) ? (string)$_GET['Tag'] : '',
		'Seite' => isset($_GET['Seite']) ? (string)$_GET['Seite'] : '',
		'Wer' => isset($_GET['Wer']) ? (string)$_GET['Wer'] : '',
		'Level' => isset($_GET['Level']) ? (string)$_GET['Level'] : '',
		'Typ' => isset($_GET['Typ']) ? (string)$_GET['Typ'] : '',
		'Text' => isset($_GET['Text']) ? (string)$_GET['Text'] : '',
	);
	$merged = array_merge($base, $over);
	$clean = array();
	foreach ($merged as $k => $v) {
		if ($v === null || $v === '') {
			continue;
		}
		$clean[$k] = (string)$v;
	}

	return http_build_query($clean);
}
?>
<style>
.log-wrap { max-width: 1440px; color: inherit; }
.log-filter {
	display: flex; flex-wrap: wrap; gap: 0.65rem 0.9rem; align-items: end;
	margin: 0 0 1rem; padding: 0.75rem 0;
}
.log-filter select, .log-filter input[type=text] {
	min-width: 8rem; max-width: 16rem; padding: 0.35rem 0.5rem; font: inherit;
}
.log-hint { font-size: 0.9rem; margin: 0 0 0.75rem; color: var(--brand-text, #e8f0e4) !important; }
.log-filter label { display: flex; flex-direction: column; gap: 0.2rem; font-size: 0.88rem; font-weight: 600; color: var(--brand-text, #e8f0e4) !important; }
.log-pills { display: flex; flex-wrap: wrap; gap: 0.35rem; align-items: center; }
.log-pill {
	display: inline-flex; align-items: center;
	padding: 0.32rem 0.7rem; border: 1px solid #c9d4c5; border-radius: 999px;
	background: #fff; color: #2d4024; text-decoration: none; font-weight: 600; font-size: 0.85rem;
}
.log-pill.is-on { background: #e8f0e4; border-color: #4d6b3e; }
.log-table-wrap {
	overflow: auto;
	max-height: calc(100vh - 14rem);
	margin: 0 0 1.2rem;
	background: #fff;
	color: #1f2a33;
	border: 1px solid #e4e8ee;
	border-radius: 10px;
}
.log-table { border-collapse: collapse; width: max-content; min-width: 100%; font-size: 0.88rem; }
.log-table th, .log-table td {
	border-bottom: 1px solid #e4e8ee;
	padding: 0.35rem 0.55rem;
	text-align: left;
	vertical-align: top;
	white-space: nowrap;
}
.log-table th {
	position: sticky; top: 0; z-index: 2;
	background: #eef5ec; font-weight: 700;
}
.log-table td.seite, .log-table td.text, .log-table td.param, .log-table td.device {
	white-space: normal;
	max-width: 18rem;
	word-break: break-word;
}
.log-table tr:nth-child(even) td { background: #f7faf6; }
.log-table td.nr { color: #5b6570; width: 2.2rem; }
</style>

<div class="log-wrap">
<p class="log-hint">Tages-Log der Seitenzugriffe. Spalten: Zeit, Level, Benutzer, IP, Typ, Seite, Text, Parameter. Filter gilt nur für den gewählten Tag. Zugriffe auf den Missionenkatalog und die Adressen werden automatisch erfasst, auch ohne eigenen WriteToLog-Aufruf in der Seite.</p>

<form method="get" action="<?= $self ?>" class="log-filter">
	<label>Tag
		<select name="Tag" onchange="this.form.submit()">
			<?php if ($byDay === array()): ?>
				<option value="">keine Logdatei</option>
			<?php endif; ?>
			<?php foreach ($byDay as $day => $path): ?>
				<option value="<?= log_h($day) ?>"<?= $selDay === $day ? ' selected' : '' ?>><?= log_h(log_day_label($day)) ?></option>
			<?php endforeach; ?>
		</select>
	</label>
	<label>Seite
		<input type="text" name="Seite" value="<?= log_h($qSeite) ?>" placeholder="enthält…">
	</label>
	<label>Benutzer / IP
		<input type="text" name="Wer" value="<?= log_h($qUser) ?>" placeholder="enthält…">
	</label>
	<label>Level
		<select name="Level">
			<option value="">alle</option>
			<?php foreach ($levels as $lv): ?>
				<option value="<?= log_h($lv) ?>"<?= strcasecmp($qLevel, $lv) === 0 ? ' selected' : '' ?>><?= log_h($lv) ?></option>
			<?php endforeach; ?>
		</select>
	</label>
	<label>Typ
		<select name="Typ">
			<option value="">alle</option>
			<?php foreach ($typs as $tp): ?>
				<option value="<?= log_h($tp) ?>"<?= strcasecmp($qTyp, $tp) === 0 ? ' selected' : '' ?>><?= log_h($tp) ?></option>
			<?php endforeach; ?>
		</select>
	</label>
	<label>Text
		<input type="text" name="Text" value="<?= log_h($qText) ?>" placeholder="enthält…">
	</label>
	<button type="submit" class="log-pill is-on">Filtern</button>
</form>

<?php if ($selFile === ''): ?>
	<p>Keine Logdatei gefunden unter <code><?= log_h($logfilelocation) ?></code>.</p>
<?php else: ?>
	<p class="log-hint"><?= (int)count($rows) ?> Einträge · <?= log_h(log_day_label($selDay)) ?> · Datei <code><?= log_h(basename($selFile)) ?></code></p>
	<div class="log-table-wrap">
		<table class="log-table">
			<thead>
				<tr>
					<th>#</th>
					<th>Zeit</th>
					<th>Level</th>
					<th>Benutzer</th>
					<th>IP</th>
					<th>Typ</th>
					<th>Seite</th>
					<th>Text</th>
					<th>Parameter</th>
				</tr>
			</thead>
			<tbody>
			<?php if ($rows === array()): ?>
				<tr><td colspan="9">Keine Einträge für diesen Filter.</td></tr>
			<?php else: ?>
				<?php foreach ($rows as $i => $row): ?>
					<tr>
						<td class="nr"><?= (int)($i + 1) ?></td>
						<td><?= log_h($row['zeit']) ?></td>
						<td><?= log_h($row['level']) ?></td>
						<td><?= log_h($row['user']) ?></td>
						<td><?= log_h($row['ip']) ?></td>
						<td><?= log_h($row['typ']) ?></td>
						<td class="seite"><?= log_h($row['seite']) ?></td>
						<td class="text"><?= log_h($row['text']) ?></td>
						<td class="param"><?= log_h($row['param'] !== '' ? $row['param'] : $row['text3']) ?></td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
			</tbody>
		</table>
	</div>
<?php endif; ?>
</div>
</body>
</html>
