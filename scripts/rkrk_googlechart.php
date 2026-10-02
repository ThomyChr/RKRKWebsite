<?php
 //session_start();
  include_once('rkrk_gen.php');


function GetListFromNumberArray($data, $title="") {
	$output = GetDataFromNumberArray($data, $title);
    return implode(", ", $output);
}

function GetDataFromNumberArray($data, $title="") {
	$output = array();
	if ($title!="") $output[]="'" . $title . "'";
    foreach($data as $key => $v){
        $output[] = $v;
    }
    return $output;
}

function GetListFromStrArray($data, $title="") {
	$output = GetDataFromStrArray($data, $title);
    return implode(", ", $output);
}

function GetDataFromStrArray($data, $title="") {
	$output = array();
	if ($title!="") $output[]="'" . $title . "'";
    foreach($data as $key => $v){
        $output[] = '"' . $v . '"';
    }
    return $output;
}

function GetListFromNumberArrayField($data, $Type, $title="") {
    $output[] = GetDataFromNumberArrayField($data, $Type, $title);
    return implode(", ", $output);
}

function GetDataFromNumberArrayField($data, $Type, $title="") {
	$output = array();
	if ($title!="") $output[]="'" . $title . "'" ;
    foreach($data as $key => $v){
        $output[] = $v[$Type];
    }
    return $output;
}

function GetListFromStrArrayField($data, $Type, $title="") {
    $output[] = GetDataFromStrArrayField($data, $Type, $title);
    return implode(", ", $output);
}

function GetDataFromStrArrayField($data, $Type, $title="") {
	$output = array();
	if ($title!="") $output[]="'" . $title . "'";
    foreach($data as $key => $v){
        $output[] = (is_array($v) ? ($v[$Type] ?? 0) : 0);
    }
    return $output;
}

function GetListFromStrArrayKey($data, $title="") {
	$output = GetDataFromStrArrayKey($data, $title);
    return implode(", ", $output);
}

function GetDataFromStrArrayKey($data, $title="") {
	$output = array();
	if ($title!="") $output[]="'" . $title . "'";
    foreach($data as $key => $v){
        $output[] = "'" . $key . "'";
    }
    return $output;
}


function FillEmptyEntries($data) {
	$output = array();
	$xmax = count($data)-1;
	$ymax = count($data[0])-1;
	$ymax = 0;
	$unknowntitle = ".";
	for ($x = 0; $x <= $xmax ; $x++) {
		if ((count($data[$x])-1)>$ymax) 
			$ymax = count($data[$x])-1;
	}
	for ($x = 0; $x <= $xmax ; $x++) {
		for ($y = 0; $y <= $ymax ; $y++) {
			if (($data[$x][$y] ?? '')=="") {
				if ($x==0) {
					$data[$x][$y]="'" .$unknowntitle ."'";
					$unknowntitle .= $unknowntitle;
				} else {
					$data[$x][$y]=0;
				}
			}
		}
	}
    return $data;
}

function Collapse2dArray($data) {
	$output = array();
	foreach($data as $key => $line){
        $output[] = "[ " . implode(" , ", $line) . " ]";
//        $output[] = implode(" , ", $line);
    }
    return ("[ " . implode(", ", $output) . " ]");
}

/*OLD non google chart*/
function GetOptionsWithColor($titel, $data, $linecolor, $fillcolor, $ShowValue=true) {
//	var data = google.visualization.arrayToDataTable([
//        ['Year', 'Visitations', { role: 'style' } ],
//        ['2010', 10, 'color: gray'],
//        ['2020', 14, 'color: #76A7FA'],
//        ['2030', 16, 'opacity: 0.2'],
//        ['2040', 22, 'stroke-color: #703593; stroke-width: 4; fill-color: #C5A5CF'],
//        ['2050', 28, 'stroke-color: #871B47; stroke-opacity: 0.6; stroke-width: 2; fill-color: #BC5679; fill-opacity: 0.2']
	$output = array();
	$y= 0;
	$x= 0;
    
	if ($ShowValue) {
		$f=3;
	} else {
		$f=2;
	}
    
    foreach($data as $keyline => $line){
		$c= 0;
		$x= $f;
		$output[$y][0] = "'" . $label[$y-1] . "'";
		foreach($line as $keycol => $v){
			$output[$y][$x] = $v;
			if ($ShowValue) $output[$y][$x+1] = $v;
			$color = "'stroke-color: " . $linecolor[$c] . "; stroke-opacity: 1; stroke-width: 2; fill-color: " . $fillcolor[$c] . "; fill-opacity: 0.6'";
			$output[$y][$x+$f-1] = $color;
			$x += $f;
			$c++;
		}
		$y++;
	}
	return $output;
    //pre_r($output);
	$output_line = array();
	foreach ($output as $line)
	{
		$outputline[] =  "[ " . implode(", ", $line) . " ]" . chr(10);
	}
	//pre_R($outputline);
	$output_all = "[ " . implode(", ", $outputline) . " ]";
	return $output_all;
}


/*OLD non google chart*/
function GetDataOptions($titel, $label, $data, $ShowValue=true) {
	//[ 'Year', 'Visitations',{ role: 'annotation' },  'Feedback' ,{ role: 'annotation' }, 'Checks' ,{ role: 'annotation' }],
	//[ '2010', 10 , 'a1', 8  , 'a2',  8  , 'a3'],
	//[ '2011', 14 , 'b1' , 5 , 'b2',  2  , 'b3'],
	//[ '2012', 16 , 'c1' , 4,  'c2',  15  , 'c3'],
	//[ '2013', 22 , 'c1' , 6 , 'e2',  19  , 'd3'],
	//[ '2014', 28 , 'f2' , 7 , 'f2',  10  , 'e3'],
	//]
	//add label
	$output = array();
	$y= 0;
	$x= 0;
    
	if ($ShowValue) {
		$f=2;
	} else {
		$f=1;
	}
    foreach($titel as $key => $titel){
        $output[$y][$x] = "'" . $titel . "'";
		if (($ShowValue) and ($x>1)) $output[$y][$x+1] = "{ role: 'annotation' }";
		$x += $f;
	}
	$y= 1;
    
    foreach($data as $keyline => $line){
		$x= $f;
		$output[$y][0] = "'" . $label[$y-1] . "'";
		foreach($line as $keycol => $v){
			$output[$y][$x] = $v;
			if ($ShowValue) $output[$y][$x+1] = $v;
			$x += $f;
		}
		$y++;
	}
    //pre_r($output);
	$output_line = array();
	foreach ($output as $line)
	{
		$outputline[] =  "[ " . implode(", ", $line) . " ]" . chr(10);
	}
	//pre_R($outputline);
	$output_all = "[ " . implode(", ", $outputline) . " ]";
	return $output_all;
}



/*NEW for google chart*/
function GetChartOptionsWithColor($data, $linecolor, $fillcolor, $ShowValue=true, $opacity=1, $width=2,$fillopacity=0.6) {
//	var data = google.visualization.arrayToDataTable([
//        ['Year', 'Visitations', { role: 'style' } ],
//        ['2010', 10, 'color: gray'],
//        ['2020', 14, 'color: #76A7FA'],
//        ['2030', 16, 'opacity: 0.2'],
//        ['2040', 22, 'stroke-color: #703593; stroke-width: 4; fill-color: #C5A5CF'],
//        ['2050', 28, 'stroke-color: #871B47; stroke-opacity: 0.6; stroke-width: 2; fill-color: #BC5679; fill-opacity: 0.2']
	$output = array();
	$y= 0;
	$x= 0;
    
	if ($ShowValue) {
		$f=3;
	} else {
		$f=2;
	}
    
    foreach($data as $keyline => $line){
		$c= 0;
		$x= $f;
		foreach($line as $keycol => $v){
			$output[$y][$x] = $v;
			if (($ShowValue) and ($x>$f)) $output[$y][$x+1] = $v;
			if (($ShowValue) and ($x>$f) and ($y==0)) $output[$y][$x+1] = "{ role: 'annotation' }";
			if (($ShowValue) and ($x>$f) and ($y==0)) $output[$y][$x+2] = "{ role: 'style' }";
			if (($ShowValue) and ($x>$f) and ($y>0)) {
				$color = "'stroke-color: " . $linecolor[$c] . "; stroke-opacity: " . $opacity . "; stroke-width: " . $width . "; fill-color: " . $fillcolor[$c] . "; fill-opacity: " . $fillopacity . "'";
				$output[$y][$x+2] = $color;
			}
			
			$x += $f;
			$c++;
		}
		$y++;
	}
	return $output;
}

/*NEW for google chart*/
function GetChartOptionsWithOnlyColor($data, $linecolor, $fillcolor, $ShowValue=true, $opacity=1, $width=2,$fillopacity=0.6) {
//	var data = google.visualization.arrayToDataTable([
//        ['Year', 'Visitations', { role: 'style' } ],
//        ['2010', 10, 'color: gray'],
//        ['2020', 14, 'color: #76A7FA'],
//        ['2030', 16, 'opacity: 0.2'],
//        ['2040', 22, 'stroke-color: #703593; stroke-width: 4; fill-color: #C5A5CF'],
//        ['2050', 28, 'stroke-color: #871B47; stroke-opacity: 0.6; stroke-width: 2; fill-color: #BC5679; fill-opacity: 0.2']
	$output = array();
	$y= 0;
	$x= 0;
    
	if ($ShowValue) {
		$f=2;
	} else {
		$f=1;
	}
    
    foreach($data as $keyline => $line){
		$c= 0;
		$x= $f;
		foreach($line as $keycol => $v){
			$output[$y][$x] = $v;
			if (($ShowValue) and ($x>$f)) $output[$y][$x+1] = $v;
			//if (($ShowValue) and ($x>$f) and ($y==0)) $output[$y][$x+1] = "{ role: 'annotation' }";
		if (($ShowValue) and ($x>$f) and ($y==0)) $output[$y][$x+1] = "{ role: 'style' }";
			if (($ShowValue) and ($x>$f) and ($y>0)) {
				$color = "'stroke-color: " . $linecolor[$c] . "; stroke-opacity: " . $opacity . "; stroke-width: " . $width . "; fill-color: " . $fillcolor[$c] . "; fill-opacity: " . $fillopacity . "'";
				$output[$y][$x+1] = $color;
			}
			
			$x += $f;
			$c++;
		}
		$y++;
	}
	return $output;
}



/*NEW for google chart*/
function GetChartOptions($data, $ShowValue=true) {
	//[ 'Year', 'Visitations',{ role: 'annotation' },  'Feedback' ,{ role: 'annotation' }, 'Checks' ,{ role: 'annotation' }],
	//[ '2010', 10 , 'a1', 8  , 'a2',  8  , 'a3'],
	//[ '2011', 14 , 'b1' , 5 , 'b2',  2  , 'b3'],
	//[ '2012', 16 , 'c1' , 4,  'c2',  15  , 'c3'],
	//[ '2013', 22 , 'c1' , 6 , 'e2',  19  , 'd3'],
	//[ '2014', 28 , 'f2' , 7 , 'f2',  10  , 'e3'],
	//]

	$output = array();
	$y= 0;
	$x= 0;
    
	if ($ShowValue) {
		$f=2;
	} else {
		$f=1;
	}
    
    foreach($data as $keyline => $line){
		$x= $f;
		foreach($line as $keycol => $v){
			$output[$y][$x] = $v;
			if (($ShowValue) and ($x>$f)) $output[$y][$x+1] = $v;
			if (($ShowValue) and ($x>$f) and ($y==0)) $output[$y][$x+1] = "{ role: 'annotation' }";
			$x += $f;
		}
		$y++;
	}
	return $output;
    /*
	//pre_r($output);
	$output_line = array();
	foreach ($output as $line)
	{
		$outputline[] =  "[ " . implode(", ", $line) . " ]" . chr(10);
	}
	//pre_R($outputline);
	$output_all = "[ " . implode(", ", $outputline) . " ]";
	return $output_all;
	*/
}

//////////////////////////////
// CHART.JS (same PHP signatures as former Google Charts helpers)
//////////////////////////////

function rkrk_chartjs_boot() {
	static $done = false;
	if ($done) {
		return '';
	}
	$done = true;
	return <<<'HTML'
<style>
.rkrk-chart-host {
	background: #fff;
	border: 1px solid #e4e8ee;
	border-radius: 12px;
	padding: 8px 10px 4px;
	margin: 0 0 8px;
	box-shadow: 0 1px 3px rgba(16,24,40,.06);
	box-sizing: border-box;
}
.rkrk-chart-host canvas { display: block; width: 100% !important; height: 100% !important; }
.rkrk-chart-table-wrap {
	max-width: 100%;
	max-height: calc(15 * 1.7em + 2.4em);
	overflow: auto;
	margin: 0 0 6px;
	font-size: 11px;
	line-height: 1.3;
}
table.rkrk-chart-table {
	width: auto !important;
	max-width: none;
	font-size: 11px !important;
	line-height: 1.3;
	border-collapse: separate;
	border-spacing: 0;
	margin: 0;
	table-layout: auto;
}
table.rkrk-chart-table td,
table.rkrk-chart-table th {
	padding: 2px 6px 2px 0 !important;
	color: #3d4751;
	white-space: nowrap;
	font-size: 11px !important;
	line-height: 1.3 !important;
	background-clip: padding-box;
}
table.rkrk-chart-table tr:first-child td,
table.rkrk-chart-table tr:first-child th {
	position: sticky;
	top: 0;
	z-index: 3;
	font-weight: 700;
	background: #f1f1f1 !important;
	box-shadow: 0 1px 0 #e2e2e2;
}
table.rkrk-chart-table td:first-child,
table.rkrk-chart-table th:first-child {
	position: sticky;
	left: 0;
	z-index: 2;
	font-weight: 600;
	padding-right: 8px !important;
	box-shadow: 2px 0 4px -2px rgba(16,24,40,.18);
}
table.rkrk-chart-table tr:nth-child(odd) td:first-child {
	background: var(--brand-vlight, #fbfbfb);
}
table.rkrk-chart-table tr:nth-child(even) td:first-child {
	background: #d0e5d0;
}
table.rkrk-chart-table tr:first-child td:first-child,
table.rkrk-chart-table tr:first-child th:first-child {
	z-index: 4;
	left: 0;
	top: 0;
	background: #f1f1f1 !important;
}
</style>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
(function (g) {
	if (g.RkrkCharts) return;
	function parseTable(rows) {
		if (!rows || !rows.length) return { labels: [], series: [] };
		var header = rows[0] || [];
		var colMap = [];
		var series = [];
		for (var i = 1; i < header.length; i++) {
			var h = header[i];
			if (h && typeof h === 'object' && (h.role === 'annotation' || h.role === 'style')) continue;
			colMap.push(i);
			series.push({ label: String(h == null ? '' : h), data: [] });
		}
		if (!series.length && header.length > 1) {
			colMap = [1];
			series = [{ label: String(header[1] == null ? '' : header[1]), data: [] }];
		}
		var labels = [];
		for (var r = 1; r < rows.length; r++) {
			labels.push(String(rows[r][0] == null ? '' : rows[r][0]));
			for (var s = 0; s < colMap.length; s++) {
				var v = rows[r][colMap[s]];
				var n = (typeof v === 'number') ? v : parseFloat(v);
				series[s].data.push(isFinite(n) ? n : 0);
			}
		}
		return { labels: labels, series: series };
	}
	function hexAlpha(hex, a) {
		hex = String(hex || '').replace('#', '');
		if (hex.length === 3) hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
		if (hex.length < 6) return 'rgba(72,103,58,' + a + ')';
		var r = parseInt(hex.slice(0, 2), 16);
		var g2 = parseInt(hex.slice(2, 4), 16);
		var b = parseInt(hex.slice(4, 6), 16);
		return 'rgba(' + r + ',' + g2 + ',' + b + ',' + a + ')';
	}
	function palette(colors, n) {
		var src = (colors && colors.length) ? colors : ['#48673A', '#7A201C', '#527642', '#922722', '#6A9955', '#96B588'];
		var out = [];
		for (var i = 0; i < n; i++) out.push(src[i % src.length]);
		return out;
	}
	function seriesMax(s) {
		var m = 0;
		if (!s || !s.data) return 0;
		for (var i = 0; i < s.data.length; i++) {
			if (s.data[i] > m) m = s.data[i];
		}
		return m;
	}
	function joinLabels(series, idxs, fallback) {
		var names = [];
		for (var i = 0; i < idxs.length && names.length < 3; i++) {
			var lb = series[idxs[i]] && series[idxs[i]].label ? String(series[idxs[i]].label) : '';
			if (lb) names.push(lb);
		}
		if (!names.length) return fallback;
		if (idxs.length > 3) names.push('…');
		return names.join(', ');
	}
	function splitMagnitude(series, kind) {
		if (!series || series.length < 2) return null;
		if (kind === 'line' && series.length >= 2) {
			var areaIdx = [];
			var otherIdx = [];
			for (var si = 0; si < series.length; si++) {
				if (seriesKind(series[si] && series[si].label) === 'area') areaIdx.push(si);
				else otherIdx.push(si);
			}
			if (areaIdx.length && otherIdx.length) {
				return { large: areaIdx, small: otherIdx };
			}
		}
		var maxima = series.map(seriesMax);
		var gmax = Math.max.apply(null, maxima.concat([0]));
		if (gmax <= 0) return null;
		var small = [];
		var large = [];
		var tinyLimit = 0.05;
		var mixedLimit = (kind === 'line' && series.length === 2) ? 0.5 : tinyLimit;
		for (var i = 0; i < maxima.length; i++) {
			if (maxima[i] > 0 && maxima[i] <= mixedLimit * gmax) small.push(i);
			else large.push(i);
		}
		if (kind === 'line' && series.length === 2) {
			var r = Math.min(maxima[0], maxima[1]) / gmax;
			if (r > 0 && r <= 0.5 && maxima[0] !== maxima[1]) {
				return {
					large: [maxima[0] >= maxima[1] ? 0 : 1],
					small: [maxima[0] >= maxima[1] ? 1 : 0]
				};
			}
		}
		if (!small.length || !large.length) return null;
		return { large: large, small: small };
	}
	function groupTinyPie(labels, values) {
		var sum = 0;
		for (var i = 0; i < values.length; i++) sum += Number(values[i]) || 0;
		if (sum <= 0 || values.length < 4) {
			return { labels: labels, values: values, otherParts: [] };
		}
		var keepL = [];
		var keepV = [];
		var other = 0;
		var parts = [];
		for (var j = 0; j < values.length; j++) {
			var v = Number(values[j]) || 0;
			if (v > 0 && v / sum < 0.05) {
				other += v;
				parts.push(labels[j] + ': ' + v);
			} else {
				keepL.push(labels[j]);
				keepV.push(v);
			}
		}
		if (parts.length < 2) {
			return { labels: labels, values: values, otherParts: [] };
		}
		keepL.push('Übrige (<5%)');
		keepV.push(Math.round(other * 100) / 100);
		return { labels: keepL, values: keepV, otherParts: parts };
	}
	function seriesKind(label) {
		var l = String(label || '').toLowerCase();
		if (l.indexOf('fläche') >= 0 || l.indexOf('flaeche') >= 0 || /\bha\b/.test(l)) return 'area';
		if (l.indexOf('flugzeit') >= 0 || /\bmin\b/.test(l)) return 'time';
		if (l.indexOf('fund') >= 0 || l.indexOf('kitz') >= 0) return 'find';
		if (l.indexOf('felder') >= 0) return 'fields';
		if (l.indexOf('einsatz') >= 0) return 'ops';
		return '';
	}
	function mixedMetricLabels(labels) {
		var kinds = {};
		for (var i = 0; i < (labels || []).length; i++) {
			var k = seriesKind(labels[i]);
			if (k) kinds[k] = 1;
		}
		return Object.keys(kinds).length >= 2;
	}
	function categoryMaxima(series, n) {
		var m = [];
		for (var i = 0; i < n; i++) {
			var mx = 0;
			for (var s = 0; s < series.length; s++) {
				var v = series[s].data[i];
				if (v > mx) mx = v;
			}
			m.push(mx);
		}
		return m;
	}
	function tinyCategoryIndexes(series, labels, limit) {
		var m = categoryMaxima(series, labels.length);
		var gmax = Math.max.apply(null, m.concat([0]));
		if (gmax <= 0) return [];
		var tiny = [];
		for (var i = 0; i < m.length; i++) {
			if (m[i] > 0 && m[i] < limit * gmax) tiny.push(i);
		}
		return tiny;
	}
	function axisStyle(color) {
		return {
			beginAtZero: true,
			grid: { color: 'rgba(16,24,40,.08)', drawBorder: false },
			ticks: { color: color || '#5b6570', font: { size: 11 } }
		};
	}
	function baseOptions(cfg) {
		var stacked = !!cfg.stacked;
		var noScale = cfg.kind === 'pie';
		return {
			responsive: true,
			maintainAspectRatio: false,
			interaction: { mode: 'index', intersect: false },
			plugins: {
				legend: {
					display: cfg.legend !== false,
					position: cfg.kind === 'pie' ? 'right' : 'top',
					labels: { boxWidth: 12, boxHeight: 12, font: { size: 11 }, color: '#3d4751' }
				},
				title: {
					display: !!cfg.title,
					text: cfg.title || '',
					font: { size: 15, weight: '600' },
					color: '#1f2a33',
					padding: { top: 4, bottom: 6 }
				},
				subtitle: {
					display: false,
					text: '',
					font: { size: 11 },
					color: '#5b6570',
					padding: { bottom: 8 }
				},
				tooltip: { backgroundColor: 'rgba(31,42,51,.92)', padding: 8, cornerRadius: 8 }
			},
			scales: noScale ? {} : {
				x: {
					stacked: stacked,
					title: { display: !!cfg.hTitle, text: cfg.hTitle || '', color: '#5b6570' },
					grid: { color: 'rgba(16,24,40,.06)', drawBorder: false },
					ticks: { color: '#5b6570', maxRotation: 50, autoSkip: true, font: { size: 11 } }
				},
				y: {
					stacked: stacked,
					beginAtZero: true,
					title: { display: !!cfg.vTitle, text: cfg.vTitle || '', color: '#5b6570' },
					grid: { color: 'rgba(16,24,40,.08)', drawBorder: false },
					ticks: { color: '#5b6570', font: { size: 11 } }
				}
			}
		};
	}
	function formatPieValue(v) {
		var n = Number(v);
		if (!isFinite(n)) return '';
		if (Math.abs(n - Math.round(n)) < 0.05) return String(Math.round(n));
		return String(Math.round(n * 10) / 10);
	}
	var pieValueLabels = {
		id: 'rkrkPieValues',
		afterDatasetsDraw: function (chart) {
			var type = chart.config.type;
			if (type !== 'doughnut' && type !== 'pie') return;
			var meta = chart.getDatasetMeta(0);
			var ds = chart.data.datasets[0];
			if (!meta || !ds || !meta.data) return;
			var ctx = chart.ctx;
			ctx.save();
			ctx.textAlign = 'center';
			ctx.textBaseline = 'middle';
			meta.data.forEach(function (arc, i) {
				var v = ds.data[i];
				if (v == null || Number(v) === 0 || !arc || arc.hidden) return;
				var span = Math.abs(arc.endAngle - arc.startAngle);
				if (span < 0.18) return;
				var mid = (arc.startAngle + arc.endAngle) / 2;
				var r = (arc.innerRadius + arc.outerRadius) / 2;
				var x = arc.x + Math.cos(mid) * r;
				var y = arc.y + Math.sin(mid) * r;
				var text = formatPieValue(v);
				ctx.font = (span < 0.4 ? '600 11px' : '700 13px') + ' system-ui, Segoe UI, sans-serif';
				ctx.lineWidth = 3;
				ctx.strokeStyle = 'rgba(255,255,255,.92)';
				ctx.strokeText(text, x, y);
				ctx.fillStyle = '#1f2a33';
				ctx.fillText(text, x, y);
			});
			ctx.restore();
		}
	};
	if (typeof Chart !== 'undefined' && Chart.register) Chart.register(pieValueLabels);
	g.RkrkCharts = {
		draw: function (cfg) {
			if (typeof Chart === 'undefined') return;
			var el = document.getElementById(cfg.id);
			if (!el) {
				el = document.createElement('div');
				el.id = cfg.id;
				el.className = 'rkrk-chart-host';
				var sc = document.currentScript;
				if (sc && sc.parentNode) sc.parentNode.insertBefore(el, sc);
				else document.body.appendChild(el);
			}
			el.classList.add('rkrk-chart-host');
			if (cfg.width) el.style.maxWidth = parseInt(cfg.width, 10) + 'px';
			if (cfg.height) el.style.height = parseInt(cfg.height, 10) + 'px';
			var canvas = (el.tagName === 'CANVAS') ? el : null;
			if (!canvas) {
				el.innerHTML = '';
				canvas = document.createElement('canvas');
				el.appendChild(canvas);
			}
			var parsed = parseTable(cfg.rows);
			var colors = palette(cfg.colors, Math.max(parsed.series.length, parsed.labels.length, 1));
			var kind = cfg.kind || 'column';
			var datasets;
			var pieOtherParts = [];
			var pieLabels = parsed.labels;
			if (kind === 'pie') {
				var values = parsed.series[0] ? parsed.series[0].data.slice() : [];
				var grouped = groupTinyPie(parsed.labels, values);
				pieLabels = grouped.labels;
				pieOtherParts = grouped.otherParts;
				colors = palette(cfg.colors, Math.max(pieLabels.length, 1));
				datasets = [{
					data: grouped.values,
					backgroundColor: pieLabels.map(function (_, i) { return hexAlpha(colors[i], 0.88); }),
					borderColor: '#fff',
					borderWidth: 2,
					hoverOffset: 6
				}];
			} else {
				datasets = parsed.series.map(function (s, i) {
					var ds = {
						label: s.label,
						data: s.data,
						backgroundColor: hexAlpha(colors[i], kind === 'line' ? 0.12 : 0.82),
						borderColor: colors[i],
						borderWidth: (kind === 'line') ? 2.5 : 0,
						borderRadius: (kind === 'bar' || kind === 'column' || kind === 'combo') ? 6 : 0,
						maxBarThickness: 42,
						tension: 0.28,
						fill: kind === 'line',
						pointRadius: (kind === 'line') ? 3 : 0,
						pointHoverRadius: 5
					};
					if (kind === 'combo') {
						ds.type = (i === 0) ? 'line' : 'bar';
						ds.fill = false;
						ds.borderWidth = (i === 0) ? 2.5 : 0;
						ds.pointRadius = (i === 0) ? 3 : 0;
						ds.order = (i === 0) ? 0 : 1;
						ds.backgroundColor = (i === 0) ? hexAlpha(colors[i], 0.12) : hexAlpha(colors[i], 0.82);
					}
					return ds;
				});
			}
			var options = baseOptions(cfg);
			if (kind === 'pie') {
				options.cutout = '52%';
				if (pieOtherParts.length) {
					options.plugins.subtitle.display = true;
					options.plugins.subtitle.text = 'Anteile unter 5 % als «Übrige» zusammengefasst';
				}
				options.plugins.legend.labels.generateLabels = function (chart) {
					var data = chart.data;
					var ds = data.datasets[0] || { data: [], backgroundColor: [] };
					return (data.labels || []).map(function (label, i) {
						var bg = ds.backgroundColor;
						return {
							text: label + '  ' + formatPieValue(ds.data[i]),
							fillStyle: Array.isArray(bg) ? bg[i] : bg,
							strokeStyle: '#fff',
							lineWidth: 1,
							hidden: false,
							index: i
						};
					});
				};
				options.plugins.tooltip.callbacks = {
					label: function (ctx) {
						var v = ctx.parsed;
						var sum = ctx.dataset.data.reduce(function (a, b) { return a + b; }, 0);
						var p = sum ? Math.round(v * 1000 / sum) / 10 : 0;
						var extra = (ctx.label === 'Übrige (<5%)' && pieOtherParts.length)
							? ' — ' + pieOtherParts.join('; ')
							: '';
						return ' ' + v + ' (' + p + '%)' + extra;
					}
				};
			}
			if (kind === 'bar') options.indexAxis = 'y';
			var horiz = (kind === 'bar');
			var valueAxis = horiz ? 'x' : 'y';
			var mixedCats = !cfg.absolute
				&& (kind === 'column' || kind === 'bar')
				&& !cfg.stacked
				&& parsed.series.length > 1
				&& mixedMetricLabels(parsed.labels);
			if (mixedCats) {
				var catMax = categoryMaxima(parsed.series, parsed.labels.length);
				datasets.forEach(function (ds, i) {
					ds.rawData = parsed.series[i].data.slice();
					ds.data = parsed.series[i].data.map(function (v, idx) {
						return catMax[idx] ? Math.round(v / catMax[idx] * 1000) / 10 : 0;
					});
				});
				options.scales[valueAxis].max = 100;
				options.scales[valueAxis].suggestedMax = 100;
				options.scales[valueAxis].title = {
					display: true,
					text: '% des Maximums je Kategorie',
					color: '#5b6570'
				};
				options.plugins.subtitle.display = true;
				options.plugins.subtitle.text = 'Gemischte Einheiten: Höhe relativ zur Kategorie, Absolutwerte im Tooltip';
				options.plugins.tooltip.callbacks = {
					label: function (ctx) {
						var raw = (ctx.dataset.rawData && ctx.dataset.rawData[ctx.dataIndex] != null)
							? ctx.dataset.rawData[ctx.dataIndex]
							: ctx.parsed[valueAxis];
						var pct = ctx.parsed[valueAxis];
						return ' ' + ctx.dataset.label + ': ' + raw + '  (' + pct + '% in dieser Kategorie)';
					}
				};
			}
			var split = (!cfg.absolute && !mixedCats && kind !== 'pie' && !cfg.stacked) ? splitMagnitude(parsed.series, kind) : null;
			if (split) {
				var mainId = horiz ? 'x' : 'y';
				var sideId = horiz ? 'x1' : 'y1';
				var largeColor = colors[split.large[0]] || '#5b6570';
				var smallColor = colors[split.small[0]] || '#7A201C';
				datasets.forEach(function (ds, i) {
					var onSmall = split.small.indexOf(i) !== -1;
					if (horiz) ds.xAxisID = onSmall ? sideId : mainId;
					else ds.yAxisID = onSmall ? sideId : mainId;
					if (kind === 'line') {
						ds.fill = false;
						if (onSmall) {
							ds.borderWidth = 2.8;
							ds.pointRadius = 4;
						}
					}
				});
				var mainAxis = Object.assign(axisStyle(largeColor), {
					position: horiz ? 'bottom' : 'left',
					stacked: !!cfg.stacked,
					title: {
						display: true,
						text: joinLabels(parsed.series, split.large, cfg.vTitle || ''),
						color: largeColor
					}
				});
				var sideAxis = Object.assign(axisStyle(smallColor), {
					position: horiz ? 'top' : 'right',
					grid: { drawOnChartArea: false, drawBorder: false },
					title: {
						display: true,
						text: joinLabels(parsed.series, split.small, 'kleine Werte'),
						color: smallColor
					}
				});
				if (horiz) {
					options.scales.x = mainAxis;
					options.scales.x1 = sideAxis;
				} else {
					options.scales.y = mainAxis;
					options.scales.y1 = sideAxis;
				}
				options.plugins.subtitle.display = true;
				options.plugins.subtitle.text = horiz
					? 'Kleine Reihen oben, eigene Skala'
					: 'Kleine Werte auf der rechten Achse';
			} else if (!cfg.absolute && !mixedCats && (kind === 'column' || kind === 'bar') && !cfg.stacked) {
				var tinyCats = tinyCategoryIndexes(parsed.series, parsed.labels, 0.05);
				if (tinyCats.length) {
					datasets.forEach(function (ds) {
						ds.data = ds.data.map(function (v) { return v > 0 ? v : null; });
					});
					options.scales[valueAxis].type = 'logarithmic';
					options.scales[valueAxis].beginAtZero = false;
					options.plugins.subtitle.display = true;
					options.plugins.subtitle.text = 'Logarithmische Skala: Kategorien unter 5 % des Maximums bleiben sichtbar';
				}
			}
			var nLabels = (kind === 'pie') ? pieLabels.length : parsed.labels.length;
			var catKey = (kind === 'bar') ? 'y' : 'x';
			if (kind !== 'pie' && options.scales && options.scales[catKey] && options.scales[catKey].ticks) {
				if (nLabels <= 15) {
					options.scales[catKey].ticks.autoSkip = false;
				} else {
					options.scales[catKey].ticks.maxTicksLimit = 15;
				}
			}
			new Chart(canvas, {
				type: kind === 'pie' ? 'doughnut' : (kind === 'line' ? 'line' : 'bar'),
				data: { labels: kind === 'pie' ? pieLabels : parsed.labels, datasets: datasets },
				options: options
			});
		}
	};
})(window);
</script>
HTML;
}

function rkrk_create_chartjs($kind, $vBox, $Titel, $data, $Colors, $hTitle = '', $vTitle = '', $height = 400, $witdh = 600, $Stacked = false, $absolute = false) {
	if (!is_numeric($height) && is_numeric($witdh) && func_num_args() >= 7) {
		// createPieChart was often called with dummy axis titles: Colors, "", "", height, width
	}
	$height = is_numeric($height) ? intval($height) : 400;
	$witdh = is_numeric($witdh) ? intval($witdh) : 600;
	if ($Colors === '' || $Colors === null) {
		$Colors = '[]';
	}
	$out = rkrk_chartjs_boot();
	$out .= '<script>(function(){function go(){if(!window.Chart||!window.RkrkCharts){setTimeout(go,30);return;}';
	$out .= 'RkrkCharts.draw({id:' . json_encode((string)$vBox);
	$out .= ',kind:' . json_encode((string)$kind);
	$out .= ',title:' . json_encode((string)$Titel, JSON_UNESCAPED_UNICODE);
	$out .= ',hTitle:' . json_encode((string)$hTitle, JSON_UNESCAPED_UNICODE);
	$out .= ',vTitle:' . json_encode((string)$vTitle, JSON_UNESCAPED_UNICODE);
	$out .= ',stacked:' . ($Stacked ? 'true' : 'false');
	$out .= ',absolute:' . ($absolute ? 'true' : 'false');
	$out .= ',height:' . $height;
	$out .= ',width:' . $witdh;
	$out .= ',rows:' . $data;
	$out .= ',colors:' . $Colors;
	$out .= '});}go();})();</script>';
	return $out;
}

function createPieChart($vBox, $Titel, $data, $Colors, $height = 400, $witdh = 600, $maybeHeight = null, $maybeWidth = null) {
	if (!is_numeric($height) && is_numeric($maybeHeight)) {
		$height = $maybeHeight;
		$witdh = $maybeWidth;
	}
	return rkrk_create_chartjs('pie', $vBox, $Titel, $data, $Colors, '', '', $height, $witdh, false);
}

function createBarChart($vBox, $Titel, $data, $Colors, $hTitle = "", $vTitle = "", $height = 400, $witdh = 600, $Stacked = false, $absolute = false) {
	return rkrk_create_chartjs('bar', $vBox, $Titel, $data, $Colors, $hTitle, $vTitle, $height, $witdh, $Stacked, $absolute);
}

function createColumnChart($vBox, $Titel, $data, $Colors, $hTitle = "", $vTitle = "", $height = "400", $witdh = "600", $Stacked = false, $absolute = false) {
	return rkrk_create_chartjs('column', $vBox, $Titel, $data, $Colors, $hTitle, $vTitle, $height, $witdh, $Stacked, $absolute);
}

function createLineChart($vBox, $Titel, $data, $Colors, $hTitle = "", $vTitle = "", $height = 400, $witdh = 600, $Stacked = false) {
	return rkrk_create_chartjs('line', $vBox, $Titel, $data, $Colors, $hTitle, $vTitle, $height, $witdh, $Stacked);
}

function createComboChart($vBox, $Titel, $data, $Colors, $hTitle = "", $vTitle = "", $height = 400, $witdh = 600, $Stacked = false) {
	return rkrk_create_chartjs('combo', $vBox, $Titel, $data, $Colors, $hTitle, $vTitle, $height, $witdh, $Stacked);
}

function createSactterChart($vBox, $Titel, $data, $Colors, $hTitle = "", $vTitle = "", $height = 400, $witdh = 600) {
	return rkrk_create_chartjs('line', $vBox, $Titel, $data, $Colors, $hTitle, $vTitle, $height, $witdh, false);
}

function createBarChart_google_unused($vBox, $Titel ,$data, $Colors , $hTitle="", $vTitle="", $height=400 , $witdh=600,  $Stacked=false) {
	$FunOut = "";
	$FunOut .= "<script>" .chr(10);

	$FunOut .= "// Pie chart" .chr(10);
	$FunOut .= "google.charts.load('current', {'packages':['corechart']});" .chr(10);
	$FunOut .= "google.charts.setOnLoadCallback(drawPieChart);" .chr(10);


	$FunOut .= "function drawPieChart() {" .chr(10);
	//$FunOut .= "var data = google.visualization.arrayToDataTable([ " .chr(10);
	//$FunOut .= "	[ 'Year', 'Visitations',{ role: 'annotation' },  'Feedback' ,{ role: 'annotation' }, 'Checks' ,{ role: 'annotation' }]," .chr(10);
	//$FunOut .= "	[ '2010', 10 , 'a1', 8  , 'a2',  8  , 'a3']," .chr(10);
	//$FunOut .= "	[ '2011', 14 , 'b1' , 5 , 'b2',  2  , 'b3']," .chr(10);
	//$FunOut .= "	[ '2012', 16 , 'c1' , 4,  'c2',  15  , 'c3']," .chr(10);
	//$FunOut .= "	[ '2013', 22 , 'c1' , 6 , 'e2',  19  , 'd3']," .chr(10);
	//$FunOut .= "	[ '2014', 28 , 'f2' , 7 , 'f2',  10  , 'e3']," .chr(10);
	//$FunOut .= " ]);" .chr(10);

	//$FunOut .= "var data = google.visualization.arrayToDataTable( " .chr(10);	
	//$FunOut .= "	[ [ 'Einsätze Piloten' ,'CH', 'TC', 'VP']" .chr(10);
	//$FunOut .= ", [ 'Felder' ,2.88, 5.94, 7.26]" .chr(10);
	//$FunOut .= ", [ 'Fläche  [ha]' ,2, 3, 3]" .chr(10);
	//$FunOut .= ", [ 'Einsatztage',0, 0, 0]" .chr(10);
	//$FunOut .= ", [ 'Einsatztage',1, 1, 1]" .chr(10);
	//$FunOut .= ", [ 'Rehkitzfunde' ,0, , 0]" .chr(10);
	//$FunOut .= " ]);" .chr(10);	

	$FunOut .= "var data = google.visualization.arrayToDataTable(".$data.");" .chr(10);


	$FunOut .= "var options = {" .chr(10);
	$FunOut .= "  title:'".$Titel."'," .chr(10);
  	$FunOut .= "  width: " . $witdh. "," .chr(10);
  	$FunOut .= "  height: "  . $height .  "," .chr(10);
  	$FunOut .= "  bar: {groupWidth: '95%'}," .chr(10);
  	//$FunOut .= "  legend: { position: 'none' }," .chr(10);
	if ($hTitle!="")
	  $FunOut .= "  hAxis: {title: '" . $hTitle . "'}," .chr(10);
  	if ($vTitle!="")
	$FunOut .= "  vAxis: {title: '" . $vTitle . "'}," .chr(10);
	$FunOut .= "  backgroundColor: 'transparent'," .chr(10);
  	//$FunOut .= "  is3D':true" .chr(10);
	if ($Stacked) {
		$FunOut .= "  isStacked: true," .chr(10);
	}
	//$FunOut .= "  showValueLabels: true," .chr(10);
  	//$FunOut .= "  legend: 'none'," .chr(10);
	$FunOut .= "  colors: ". $Colors .chr(10);
	$FunOut .= "};" .chr(10);

	$FunOut .= "var chart = new google.visualization.BarChart(document.getElementById('".$vBox."'));" .chr(10);
  	$FunOut .= "chart.draw(data, options);" .chr(10);
	$FunOut .= "}" .chr(10);
	$FunOut .= "	</script>" .chr(10);

	return $FunOut;
}

function createColumnChart_google_unused($vBox, $Titel ,$data, $Colors , $hTitle="", $vTitle="", $height="400" , $witdh="600", $Stacked=false) {
	$FunOut = "";
	$FunOut .= "<script>" .chr(10);

	$FunOut .= "// Pie chart" .chr(10);
	$FunOut .= "google.charts.load('current', {'packages':['corechart']});" .chr(10);
	$FunOut .= "google.charts.setOnLoadCallback(drawPieChart);" .chr(10);


	$FunOut .= "function drawPieChart() {" .chr(10);
	//$FunOut .= "var data = google.visualization.arrayToDataTable([ " .chr(10);
	//$FunOut .= "	[ 'Year', 'Visitations',{ role: 'annotation' },  'Feedback' ,{ role: 'annotation' }, 'Checks' ,{ role: 'annotation' }]," .chr(10);
	//$FunOut .= "	[ '2010', 10 , 'a1', 8  , 'a2',  8  , 'a3']," .chr(10);
	//$FunOut .= "	[ '2011', 14 , 'b1' , 5 , 'b2',  2  , 'b3']," .chr(10);
	//$FunOut .= "	[ '2012', 16 , 'c1' , 4,  'c2',  15  , 'c3']," .chr(10);
	//$FunOut .= "	[ '2013', 22 , 'c1' , 6 , 'e2',  19  , 'd3']," .chr(10);
	//$FunOut .= "	[ '2014', 28 , 'f2' , 7 , 'f2',  10  , 'e3']," .chr(10);
	//$FunOut .= " ]);" .chr(10);

	//$FunOut .= "var data = google.visualization.arrayToDataTable( " .chr(10);	
	//$FunOut .= "	[ [ 'Einsätze Piloten' ,'CH', 'TC', 'VP']" .chr(10);
	//$FunOut .= ", [ 'Felder' ,2.88, 5.94, 7.26]" .chr(10);
	//$FunOut .= ", [ 'Fläche  [ha]' ,2, 3, 3]" .chr(10);
	//$FunOut .= ", [ 'Einsatztage',0, 0, 0]" .chr(10);
	//$FunOut .= ", [ 'Einsatztage',1, 1, 1]" .chr(10);
	//$FunOut .= ", [ 'Rehkitzfunde' ,0, , 0]" .chr(10);
	//$FunOut .= " ]);" .chr(10);	

	$FunOut .= "var data = google.visualization.arrayToDataTable(".$data.");" .chr(10);


	$FunOut .= "var options = {" .chr(10);
	$FunOut .= "  title:'".$Titel."'," .chr(10);
  	$FunOut .= "  width: ".$witdh."," .chr(10);
  	$FunOut .= "  height: ".$height."," .chr(10);
  	$FunOut .= "  bar: {groupWidth: '95%'}," .chr(10);
  	//$FunOut .= "  legend: { position: 'none' }," .chr(10);
	if ($hTitle!="")
	  $FunOut .= "  hAxis: {title: '" . $hTitle . "'}," .chr(10);
  	if ($vTitle!="")
	$FunOut .= "  vAxis: {title: '" . $vTitle . "'}," .chr(10);
	$FunOut .= "  backgroundColor: 'transparent'," .chr(10);
  	//$FunOut .= "  is3D':true" .chr(10);
	if ($Stacked) {
		$FunOut .= "  isStacked: true," .chr(10);
	}
	//$FunOut .= "  showValueLabels: true," .chr(10);
  	//$FunOut .= "  legend: 'none'," .chr(10);
	$FunOut .= "  colors: ". $Colors .chr(10);
	$FunOut .= "};" .chr(10);

	$FunOut .= "var chart = new google.visualization.ColumnChart(document.getElementById('".$vBox."'));" .chr(10);
  	$FunOut .= "chart.draw(data, options);" .chr(10);
	$FunOut .= "}" .chr(10);
	$FunOut .= "	</script>" .chr(10);

	return $FunOut;
}


function createLineChart_google_unused($vBox, $Titel ,$data, $Colors, $hTitle="", $vTitle="", $height=400 , $witdh=600, $Stacked=false) {
	$FunOut = "";
	$FunOut .= "<script>" .chr(10);

	$FunOut .= "// Pie chart" .chr(10);
	$FunOut .= "google.charts.load('current', {'packages':['corechart']});" .chr(10);
	$FunOut .= "google.charts.setOnLoadCallback(drawPieChart);" .chr(10);


	$FunOut .= "function drawPieChart() {" .chr(10);
	//$FunOut .= "var data = google.visualization.arrayToDataTable([ " .chr(10);
	//$FunOut .= "	[ 'Year', 'Visitations' ]," .chr(10);
	//$FunOut .= "	[ '2010', 10 ]," .chr(10);
	//$FunOut .= "	[ '2011', 14 ]," .chr(10);
	//$FunOut .= "	[ '2012', 16 ]," .chr(10);
	//$FunOut .= "	[ '2013', 22 ]," .chr(10);
	//$FunOut .= "	[ '2014', 28 ]," .chr(10);
	//$FunOut .= " ]);" .chr(10);

	$FunOut .= "var data = google.visualization.arrayToDataTable(".$data.");" .chr(10);


	$FunOut .= "var options = {" .chr(10);
	$FunOut .= "  title:'".$Titel."'," .chr(10);
  	$FunOut .= "  width: ".$witdh."," .chr(10);
  	$FunOut .= "  height: ".$height."," .chr(10);
	$FunOut .= "  colors: ". $Colors ."," .chr(10);
    //$FunOut .= "  annotations: {" .chr(10);
    //$FunOut .= "    textStyle: {" .chr(10);
    //$FunOut .= "      fontSize: 12," .chr(10);
    //$FunOut .= "      auraColor: 'none'" .chr(10);
    //$FunOut .= "    }" .chr(10);
    //$FunOut .= "  }," .chr(10);
	if ($hTitle!="")
	  $FunOut .= "  hAxis: {title: '" . $hTitle . "'}," .chr(10);
  	if ($vTitle!="")
	$FunOut .= "  vAxis: {title: '" . $vTitle . "'}," .chr(10);
  	$FunOut .= "  backgroundColor: 'transparent'," .chr(10);
  	//$FunOut .= "  is3D':true" .chr(10);
	if ($Stacked) {
		$FunOut .= "  isStacked: true," .chr(10);
	}
  	//$FunOut .= "  legend: 'none'" .chr(10);
	$FunOut .= "};" .chr(10);

	$FunOut .= "var chart = new google.visualization.LineChart(document.getElementById('".$vBox."'));" .chr(10);
  	$FunOut .= "chart.draw(data, options);" .chr(10);
	$FunOut .= "}" .chr(10);
	$FunOut .= "	</script>" .chr(10);

	return $FunOut;
}

function createSactterChart_google_unused($vBox, $Titel ,$data, $Colors, $hTitle="", $vTitle="", $height=400 , $witdh=600) {
	$FunOut = "";
	$FunOut .= "<script>" .chr(10);

	$FunOut .= "// Pie chart" .chr(10);
	$FunOut .= "google.charts.load('current', {'packages':['corechart']});" .chr(10);
	$FunOut .= "google.charts.setOnLoadCallback(drawPieChart);" .chr(10);


	$FunOut .= "function drawPieChart() {" .chr(10);
	$FunOut .= "var data = google.visualization.arrayToDataTable([ " .chr(10);
	$FunOut .= "	[ 'Year', 'Visitations',{ role: 'annotation' },  'Feedback' ,{ role: 'annotation' }, 'Checks' ,{ role: 'annotation' }]," .chr(10);
	$FunOut .= "	[ '2010', 10 , 'a1', 8  , 'a2',  8  , 'a3']," .chr(10);
	$FunOut .= "	[ '2011', 14 , 'b1' , 5 , 'b2',  2  , 'b3']," .chr(10);
	$FunOut .= "	[ '2012', 16 , 'c1' , 4,  'c2',  15  , 'c3']," .chr(10);
	$FunOut .= "	[ '2013', 22 , 'c1' , 6 , 'e2',  19  , 'd3']," .chr(10);
	$FunOut .= "	[ '2014', 28 , 'f2' , 7 , 'f2',  10  , 'e3']," .chr(10);
	$FunOut .= " ]);" .chr(10);
	//$FunOut .= "var data = google.visualization.arrayToDataTable([ " .chr(10);
	//$FunOut .= "	[ 'Year', 'Visitations' ]," .chr(10);
	//$FunOut .= "	[ '2010', 10 ]," .chr(10);
	//$FunOut .= "	[ '2011', 14 ]," .chr(10);
	//$FunOut .= "	[ '2012', 16 ]," .chr(10);
	//$FunOut .= "	[ '2013', 22 ]," .chr(10);
	//$FunOut .= "	[ '2014', 28 ]," .chr(10);
	//$FunOut .= " ]);" .chr(10);

	//$FunOut .= "var data = google.visualization.arrayToDataTable(".$data.");" .chr(10);


	$FunOut .= "var options = {" .chr(10);
	$FunOut .= "  title:'".$Titel."'," .chr(10);
  	$FunOut .= "  width: ".$witdh."," .chr(10);
  	$FunOut .= "  height: ".$height."," .chr(10);
	$FunOut .= "  colors: ". $Colors ."," .chr(10);
	//if ($hTitle!="")
		$FunOut .= "  hAxis: {title: '" . $hTitle . "'}," .chr(10);
  	//if ($vTitle!="")
		$FunOut .= "  vAxis: {title: '" . $vTitle . "'}," .chr(10);
  	$FunOut .= "  backgroundColor: 'transparent'," .chr(10);
  	//$FunOut .= "  is3D':true" .chr(10);
  	$FunOut .= "  legend: 'none'" .chr(10);
	$FunOut .= "};" .chr(10);

	$FunOut .= "var chart = new google.visualization.ScatterChart(document.getElementById('".$vBox."'));" .chr(10);
  	$FunOut .= "chart.draw(data, options);" .chr(10);
	$FunOut .= "}" .chr(10);
	$FunOut .= "	</script>" .chr(10);

	return $FunOut;
}

function createComboChart_google_unused($vBox, $Titel ,$data, $Colors, $hTitle="", $vTitle="", $height=400 , $witdh=600, $Stacked=false) {
	$FunOut = "";
	$FunOut .= "<script>" .chr(10);

	$FunOut .= "// Pie chart" .chr(10);
	$FunOut .= "google.charts.load('current', {'packages':['corechart']});" .chr(10);
	$FunOut .= "google.charts.setOnLoadCallback(drawPieChart);" .chr(10);


	$FunOut .= "function drawPieChart() {" .chr(10);
	//$FunOut .= "var data = google.visualization.arrayToDataTable([ " .chr(10);
	//$FunOut .= "	[ 'Year', 'Visitations',{ role: 'annotation' },  'Feedback' ,{ role: 'annotation' }, 'Checks' ,{ role: 'annotation' }]," .chr(10);
	//$FunOut .= "	[ '2010', 10 , 'a1', 8  , 'a2',  8  , 'a3']," .chr(10);
	//$FunOut .= "	[ '2011', 14 , 'b1' , 5 , 'b2',  2  , 'b3']," .chr(10);
	//$FunOut .= "	[ '2012', 16 , 'c1' , 4,  'c2',  15  , 'c3']," .chr(10);
	//$FunOut .= "	[ '2013', 22 , 'c1' , 6 , 'e2',  19  , 'd3']," .chr(10);
	//$FunOut .= "	[ '2014', 28 , 'f2' , 7 , 'f2',  10  , 'e3']," .chr(10);
	//$FunOut .= " ]);" .chr(10);

	//$FunOut .= "var data = google.visualization.arrayToDataTable( " .chr(10);	
	//$FunOut .= "	[ [ 'Einsätze Piloten' ,'CH', 'TC', 'VP']" .chr(10);
	//$FunOut .= ", [ 'Felder' ,2.88, 5.94, 7.26]" .chr(10);
	//$FunOut .= ", [ 'Fläche  [ha]' ,2, 3, 3]" .chr(10);
	//$FunOut .= ", [ 'Einsatztage',0, 0, 0]" .chr(10);
	//$FunOut .= ", [ 'Einsatztage',1, 1, 1]" .chr(10);
	//$FunOut .= ", [ 'Rehkitzfunde' ,0, , 0]" .chr(10);
	//$FunOut .= " ]);" .chr(10);	

	$FunOut .= "var data = google.visualization.arrayToDataTable(".$data.");" .chr(10);


	$FunOut .= "var options = {" .chr(10);
	$FunOut .= "  title:'".$Titel."'," .chr(10);
  	$FunOut .= "  width: ".$witdh."," .chr(10);
  	$FunOut .= "  height: ".$height."," .chr(10);
  	$FunOut .= "  bar: {groupWidth: '95%'}," .chr(10);
  	//$FunOut .= "  legend: { position: 'none' }," .chr(10);
	if ($hTitle!="")
	  $FunOut .= "  hAxis: {title: '" . $hTitle . "'}," .chr(10);
  	if ($vTitle!="")
	$FunOut .= "  vAxis: {title: '" . $vTitle . "'}," .chr(10);
    $FunOut .= "             seriesType: 'bars'," .chr(10);
    $FunOut .= "             series: {0: {type: 'line', curveType: 'function'}}," .chr(10);
	$FunOut .= "	curveType: 'function', " .chr(10);
	$FunOut .= "  backgroundColor: 'transparent'," .chr(10);
  	//$FunOut .= "  is3D':true" .chr(10);
	if ($Stacked) {
		$FunOut .= "  isStacked: true," .chr(10);
	}
	//$FunOut .= "  showValueLabels: true," .chr(10);
  	//$FunOut .= "  legend: 'none'," .chr(10);
	$FunOut .= "  colors: ". $Colors .chr(10);
	$FunOut .= "};" .chr(10);

	$FunOut .= "var chart = new google.visualization.ComboChart(document.getElementById('".$vBox."'));" .chr(10);
  	$FunOut .= "chart.draw(data, options);" .chr(10);
	$FunOut .= "}" .chr(10);
	$FunOut .= "	</script>" .chr(10);

	return $FunOut;
}

function XTransform2dArray($data) {
	$output = Array();
	$x = 0;
	foreach ($data as $line) {
		$y = 0;
		foreach ($line as $entry) {
			$output[$y][$x]=$entry;
		$y++;
		}
	$x++;
	}
	return ($output);
	
}
function GetTableFromArray($data) {
	$FunOut = "";

	$FunOut .= "<div class=\"rkrk-chart-table-wrap\">";
	$FunOut .= "<table class=\"rkrk-chart-table\">";
	foreach ($data as $line) {
		$FunOut .= "<tr>";
		foreach ($line as $entry) {
		$FunOut .= "<td>";
		$FunOut .= str_replace("'", "" , $entry);
		$FunOut .= "</td>";
		}
		$FunOut .= "</tr>";
	}
	$FunOut .= "</table>";
	$FunOut .= "</div>";

	return $FunOut;
}


function createGoogleTable($vBox, $Titel ,$data, $Colors, $hTitle="", $vTitle="", $Stacked=false) {
	$FunOut = "";
	$FunOut .= "<script>" .chr(10);

	$FunOut .= "// table" .chr(10);
	$FunOut .= "google.charts.load('current', {'packages':['corechart']});" .chr(10);
	$FunOut .= "google.charts.setOnLoadCallback(drawTable);" .chr(10);


	$FunOut .= "function drawTable() {" .chr(10);
	$FunOut .= "var data = google.visualization.arrayToDataTable([ " .chr(10);
	$FunOut .= "	[ 'Year', 'Visitations' ]," .chr(10);
	$FunOut .= "	[ '2010', 10 ]," .chr(10);
	$FunOut .= "	[ '2011', 14 ]," .chr(10);
	$FunOut .= "	[ '2012', 16 ]," .chr(10);
	$FunOut .= "	[ '2013', 22 ]," .chr(10);
	$FunOut .= "	[ '2014', 28 ]," .chr(10);
	$FunOut .= " ]);" .chr(10);

	$FunOut .= "var data = google.visualization.Table(".$data.");" .chr(10);

	$FunOut .= "var chart = new google.visualization.Table(document.getElementById('".$vBox."'));" .chr(10);
    $FunOut .= "table.draw(data, document.getElementById('".$vBox."'));" .chr(10);
	$FunOut .= "}" .chr(10);
	$FunOut .= "	</script>" .chr(10);

	return $FunOut;
}

function createGoogleTable1($vBox)  {
  $FunOut = "";
  $FunOut .= "<script>" .chr(10);
  $FunOut .= "var data = google.visualization.arrayToDataTable([ " .chr(10);
  $FunOut .= "    ['Lat', 'Lon', 'Name', 'Food?']," .chr(10);
  $FunOut .= "        [51.5072, -0.1275, 'Cinematics London', true]," .chr(10);
  $FunOut .= "        [48.8567, 2.3508, 'Cinematics Paris', true]," .chr(10);
  $FunOut .= "        [55.7500, 37.6167, 'Cinematics Moscow', false]" .chr(10);
  $FunOut .= " ]);" .chr(10);
  
  $FunOut .= "var table =" .chr(10);
  $FunOut .= "          new google.visualization.Table(document.getElementById('" . $vBox . "'));" .chr(10);
 // $FunOut .= "      table.draw(geoData, {showRowNumber: false, width: '100%', height: '100%'});" .chr(10);
		
		
 // $FunOut .= "var data = new google.visualization.DataTable(document.getElementById('".$vBox."'));" .chr(10);


/*
  $FunOut .= "var data = new google.visualization.DataTable(document.getElementById('".$vBox."'));" .chr(10);
  $FunOut .= "data.addColumn('string', 'Name');" .chr(10);
  $FunOut .= "data.addColumn('number', 'Salary');" .chr(10);
  $FunOut .= "data.addColumn('boolean', 'Full Time');" .chr(10);
  $FunOut .= "data.addRows(5);" .chr(10);
  $FunOut .= "data.setCell(0, 0, 'John');" .chr(10);
  $FunOut .= "data.setCell(0, 1, 10000, '$10,000');" .chr(10);
  $FunOut .= "data.setCell(0, 2, true);" .chr(10);
  $FunOut .= "data.setCell(1, 0, 'Mary');" .chr(10);
  $FunOut .= "data.setCell(1, 1, 25000, '$25,000');" .chr(10);
  $FunOut .= "data.setCell(1, 2, true);" .chr(10);
  $FunOut .= "data.setCell(2, 0, 'Steve');" .chr(10);
  $FunOut .= "data.setCell(2, 1, 8000, '$8,000');" .chr(10);
  $FunOut .= "data.setCell(2, 2, false);" .chr(10);
  $FunOut .= "data.setCell(3, 0, 'Ellen');" .chr(10);
  $FunOut .= "data.setCell(3, 1, 20000, '$20,000');" .chr(10);
  $FunOut .= "data.setCell(3, 2, true);" .chr(10);
  $FunOut .= "data.setCell(4, 0, 'Mike');" .chr(10);
  $FunOut .= "data.setCell(4, 1, 12000, '$12,000');" .chr(10);
  $FunOut .= "data.setCell(4, 2, false);" .chr(10);
*/

  //$FunOut .= " var table = new google.visualization.Table(document.getElementById('table_div'));" .chr(10);
    $FunOut .= "table.draw(data, document.getElementById('".$vBox."'));" .chr(10);

//  $FunOut .= "google.visualization.events.addListener(table, 'select', function() {" .chr(10);
//  $FunOut .= "  var row = table.getSelection()[0].row;" .chr(10);
//  $FunOut .= "  alert('You selected ' + data.getValue(row, 0));" .chr(10);
//  $FunOut .= "});" .chr(10);
  $FunOut .= "</script>" .chr(10);
  
   return $FunOut;  
}
?>

<script>
function addTable(vBox, vTitel, vData) {
// new myTableDiv(document.getElementById(vBox), {
	var myTableDiv = document.getElementById(vBox);

  var table = document.createElement('TABLE');
  table.width  = 800;

  var tableBody = document.createElement('TBODY');
  table.appendChild(tableBody);

  for(var i=0; i<vData.length; i++) {
    var tr = document.createElement('TR');
    tableBody.appendChild(tr);

      for(var j=0; j<vData[i].length; j++){
	  if (j===0) {
	  var td = document.createElement('TD');
		td.appendChild(document.createTextNode(vTitel[i]));
		td.width = '100';
		tr.appendChild(td);
	  }	  
		var td = document.createElement('TD');
		td.width = '50';
      td.appendChild(document.createTextNode(vData[i][j]));
      tr.appendChild(td);
    }
  }
  myTableDiv.appendChild(table);
}
</script>


<?php
function addTAble($vBox, $Titel ,$data) {
	$FunOut = "";
	$FunOut .= "<script>" .chr(10);
	$FunOut .= "addTable('" .  $vBox. "', " . $Titel . ", " . $data ." ); ".chr(10);
	$FunOut .= "	</script>" .chr(10);

	return $FunOut;
}
?>
