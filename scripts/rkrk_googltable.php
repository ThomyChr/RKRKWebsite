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
        $output[] = $v[$Type];
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
			if ($data[$x][$y]=="") {
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
// GOOGLE CHARTS
//////////////////////////////


function createPieChart($vBox, $Titel ,$data, $Colors) {
	// https://developers.google.com/chart/interactive/docs/gallery/piechart
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
  	$FunOut .= "  backgroundColor: 'transparent'," .chr(10);
  	//$FunOut .= "  is3D':true" .chr(10);
	$FunOut .= "	pieSliceText: 'value'," .chr(10); //'percentage' , 'value', 'label' , 'none'
	$FunOut .= "  colors: ". $Colors .chr(10);
	$FunOut .= "};" .chr(10);
	$FunOut .= "var chart = new google.visualization.PieChart(document.getElementById('".$vBox."'));" .chr(10);
  	$FunOut .= "chart.draw(data, options);" .chr(10);
	$FunOut .= "}" .chr(10);
	$FunOut .= "	</script>" .chr(10);

	return $FunOut;
}

function createBarChart($vBox, $Titel ,$data, $Colors , $hTitle="", $vTitle="", $Stacked=false) {
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
  	$FunOut .= "  width: 600," .chr(10);
  	$FunOut .= "  height: 400," .chr(10);
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

function createColumnChart($vBox, $Titel ,$data, $Colors , $hTitle="", $vTitle="", $Stacked=false) {
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
  	$FunOut .= "  width: 600," .chr(10);
  	$FunOut .= "  height: 400," .chr(10);
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


function createLineChart($vBox, $Titel ,$data, $Colors, $hTitle="", $vTitle="", $Stacked=false) {
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
  	$FunOut .= "  legend: 'none'" .chr(10);
	$FunOut .= "};" .chr(10);

	$FunOut .= "var chart = new google.visualization.LineChart(document.getElementById('".$vBox."'));" .chr(10);
  	$FunOut .= "chart.draw(data, options);" .chr(10);
	$FunOut .= "}" .chr(10);
	$FunOut .= "	</script>" .chr(10);

	return $FunOut;
}

function createSactterChart($vBox, $Titel ,$data, $Colors, $hTitle="", $vTitle="") {
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

function createComboChart($vBox, $Titel ,$data, $Colors, $hTitle="", $vTitle="", $Stacked=false) {
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
  	$FunOut .= "  width: 600," .chr(10);
  	$FunOut .= "  height: 400," .chr(10);
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

	$FunOut .= "<table>";
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
