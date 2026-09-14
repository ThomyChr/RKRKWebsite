<?php
	session_start();
	include_once('scripts/rkrk_gen.php');
	include_once('scripts/rkrk_fileaccess.php');
 

// File access functions
function GetWeather($fontsize="", $numDays=14, $weatherData=null) {
global $RKRKWeatherCSV,$csvDocLocation,$DayShortList;
	$data = is_array($weatherData) ? $weatherData : GetWeatherData();
	//pre_r($data);
    if ($fontsize=="") 
		$style = "";
	else
		$style ="style='font-size: ".$fontsize."px;'";
	
	$numDays = intval($numDays);
	if ($numDays <= 0) $numDays = 14;

	$FromDate = date('Y-m-d', strtotime('-2 days'));
	$ToDate = date('Y-m-d', strtotime($FromDate . ' +' . ($numDays - 1) . ' days'));
	$Week = date('W',strtotime($FromDate ));
	$today = date('Y-m-d');
	$weatherdata = [];
	foreach ($data as $key => $dataline) {
		if (($dataline["Datum"] >= $FromDate) and ($dataline["Datum"] <= $ToDate) and ($dataline["Wetter"]!="")) {
			$weatherdata[] = $dataline;
		}	
	}
	$Output="";
	if (count($weatherdata)>0) {
		$Output .= '<style>
		.wx-legend-wrap{display:inline-block; position:relative; margin-left:6px; vertical-align:middle;}
		.wx-legend-i{display:inline-flex; align-items:center; justify-content:center; width:16px; height:16px; border:1px solid #6b7280; border-radius:50%; color:#374151; font-size:11px; font-weight:700; cursor:pointer; background:#fff;}
		.wx-legend-pop{display:none; position:absolute; left:22px; top:-6px; z-index:999; min-width:250px; max-width:320px; background:#fff; border:1px solid #d1d5db; border-radius:8px; padding:8px 10px; box-shadow:0 8px 20px rgba(0,0,0,.15); font-size:12px; line-height:1.4;}
		.wx-legend-wrap:hover .wx-legend-pop{display:block;}
		.wx-legend-wrap:focus-within .wx-legend-pop{display:block;}
		.wx-legend-title{font-weight:700; margin-bottom:4px;}
		.wx-legend-line{margin:2px 0;}
		</style>' . chr(10);
		$Output .= '<h2>Wetteraussichten'
			. '<span class="wx-legend-wrap">'
			. '<button type="button" class="wx-legend-i" aria-label="Wetter Legende anzeigen" title="Legende anzeigen">i</button>'
			. '<span class="wx-legend-pop">'
			. '<div class="wx-legend-title">Legende Wetter-Abstufung</div>'
			. '<div class="wx-legend-line">☀️ Sonne: klarer Himmel</div>'
			. '<div class="wx-legend-line">🌤️ leicht bewölkt: meist sonnig</div>'
			. '<div class="wx-legend-line">⛅ teilweise sonnig: Sonne/Wolken gemischt</div>'
			. '<div class="wx-legend-line">☁️ bewölkt: überwiegend Wolken</div>'
			. '<div class="wx-legend-line">🌦️ wechselhaft: Niesel/kurze Schauer möglich</div>'
			. '<div class="wx-legend-line">🌧️ Regen: Regen oder Regenschauer</div>'
			. '<div class="wx-legend-line">🌨️ Schnee: Schneefall/Schneeschauer</div>'
			. '<div class="wx-legend-line">⛈️ Gewitter / Gewitter und Regen</div>'
			. '<div class="wx-legend-line">🌫️ Nebel: eingeschränkte Sicht</div>'
			. '<div class="wx-legend-line" style="margin-top:6px;color:#666;font-size:11px">Symbole: Open-Meteo um 09:00 Uhr (WMO + Bewölkung/Regen). Hohe Bewölkung bei trockenem Wetter kann trotzdem sonnig wirken.</div>'
			. '</span>'
			. '</span>'
			. '</h2>' .chr(10);
		//echo "Start Date: " . $FromDate . chr(10);
		//echo "Woche: " . $Week  . chr(10);
		//pre_R($weatherdata);
		$Line1 = "<tr>". "<td>"."<b><i>KW". $Week . "</i></b>"."</td>" ;
		$Line2 = "<tr>". "<td>"."Wetter:"."</td>";
		$Line3 = "<tr>". "<td>"."Temperatur:"."</td>";
		$Num = 0;
		$WeekOffset = 1;
		foreach ($weatherdata as $key => $dataline) {
			if ($dataline["Temp"]!="") {
				$isToday = ((string)$dataline['Datum'] === $today);
				$cellStyle = $isToday ? " style='background:#eaf7ea;'" : "";
				$Line1 .= "<td align='center'{$cellStyle}>".  $DayShortList[date('N',strtotime($dataline['Datum']))] .  " " . date("d.m.",strtotime($dataline["Datum"]))."</td>";
				$wxText = htmlspecialchars((string)($dataline["Wetter"] ?? ''), ENT_QUOTES, 'UTF-8');
				$wxIcon = GetWeatherIcon($dataline["Wetter"]);
				$Line2 .= "<td align='center'{$cellStyle}><span title='".$wxText."'>".$wxIcon."</span></td>";
				$Line3 .= "<td align='center'{$cellStyle}>". $dataline["Temp"]."</td>";
				if (($DayShortList[date('N',strtotime($dataline['Datum']))])=="So") {
					$Line1 .= "<td>"."<b><i>KW". ($Week + $WeekOffset) . "</i></b>"."</td>" ;
					$Line2 .= "<td></td>";
					$Line3 .= "<td></td>";
					++$WeekOffset;
				}
				++$Num;
				if ($Num>=$numDays) break;
			}
		}	
		$Output .= "<table ".$style. ">" .chr(10);
		$Output .= $Line1 . "</tr>" . chr(10);
		$Output .= $Line2 . "</tr>" . chr(10);
		$Output .= $Line3 . "</tr>" . chr(10);
		$Output .= "</table>" . chr(10);
		return $Output;
	}	
}


?>