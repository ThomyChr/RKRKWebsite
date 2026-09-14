<?php

/////////////////////////
// General Setttings
/////////////////////////


	$ScreenTableSetupSettings = Array(
						"Table" => Array(
										"<table>" => "<table >",
										"<table left>" => "<table >",
										"<tr>" => "<tr>",
										"<th>" => "<th >",
										"<th nowrap>" => "<th width='60px'>",
										"</th>" => "</th>",
										"<td light>" => "<td>",
										"<td 2>" => "<td colspan=2>",
										"<td 3>" => "<td colspan=3>",
										"<td 5>" => "<td colspan=5>",
										"<td 10>" => "<td colspan=10>",
										"<td line>" => "<td colspan=20>",
										"<td wrap>" => "<td  width='60px'>",
										"<td nowrap>" => "<td>",
										"<td>" => "<td>",
										"</td>" => "</td>",
										"</tr>" => "</tr>",
										"</table>" => "</table>",
										"<div>" => "",
										"</div>" => "",
										),
						"Flex" => Array(
										"<table>" => "<div class='container content left padding-32' style='width=100%' id='table'>",
										"<table line>" => "<div class='container content line-left padding-8' style='max-width:100%'>",
										"<tr>" => "<div class='flex-container left'>",
										//"<tr>" => "<div class='box'>",
										// <div class='third margin-bottom'>
										"<th>" => "<div class='flex-light hide-lower-1500 center'>",
										"<th nowrap>" => "<div class='flex-light center hide-lower-1500' style='min-width:60px; white-space: nowrap;'>",
										//"<th>" => "<div class='box'>",
										"</th>" => "</div>",
										"<td light>" => "<div class='flex-light center'>",
										"<td 10>" => "<div class='line-left'>",
										"<td wrap>" => "<div class='flex-light center wrap'>",
										//flex-light center' ='min-width: 60px;
										"<td nowrap>" => "<div class='flex-light center' style='min-width:60px; white-space: nowrap;'>",
										"<td line>" => "<div class='line-left'>",
										"<td>" => "<div class='flex-vlight left'>",
										"</td>" => "</div>",
										//"<td>" => "",
										//"</td>" => "",
										"</tr>" => "</div>",
										"</table>" => "</div>",
										"<div>" => "",
										"</div>" => "",
										"<div>" => "<div class='flex-container'><div class='flex-white'>",
										"</div>" => "</div></div>",
										),
						);

/////////////////////////
// Functions
/////////////////////////

function SetFormat($TableType, $Label ,$Style="") {
	global $ScreenTableSetupSettings;
	if ($Style=="") {
		$ReturnFormat= $ScreenTableSetupSettings[$TableType][$Label];
	} else {
		$ReturnFormat= substr($ScreenTableSetupSettings[$TableType][$Label],0,-1);
		$ReturnFormat.= " style='". $Style . "' ";
		$ReturnFormat.=  substr($ScreenTableSetupSettings[$TableType][$Label],-1);
	}
	Return $ReturnFormat;
}

/////////////////////////
// Style
/////////////////////////

?>
<style>
:root {
	--vdark_green: #48673A;
	--dark_green: #527642;
	--mid_green: #96B588;
	--light_green: #C5E0B4;
	--vlight_green: #ECF7EC;
	--light_grey: #EEE;
	--white: #FFFFFF;
}

* {
  box-sizing: border-box;
}


.flex-container {
  display: flex;
  flex-direction: row;
  //flex-wrap: wrap;
  font-size: 15px;
  text-align: left;
}
.flex-container>* {
	flex: 200 1 1 px;
}

.flex-mid {
  background-color: var(--mid_green);
  padding: 2px;
  flex: 40%;
}

.flex-light {
  background-color: var(--light_green);
  padding: 2px;
  flex: 50%;

}
.flex-light-grey {
  background-color: var(--light_grey);
  padding: 2px;
  flex: 50%;

}

.flex-vlight {
  background-color: var(--vlight_green);
  padding: 2px;
  flex: 50%;
}

.vlight {
  background-color: var(--vlight_green);
  padding: 2px;
  flex: 50%;
}
.white {
  background-color: var(--white);
  padding: 2px;
  flex: 50%;
}

.inputfield {
  background: rgba(255,255,255,0.7);
  border: none;
<!--  font-size: 15;-->
}

.textlabel {
	font-size:.7em; 
	font-style:italic;
	background-color:  var(--vlight_green);!important;
}

.hide-over-1000 {
	display: none;
}

.hide-lower-1000 {
	display: inline;
}

.hide-over-1500 {
	display: none;
}

.hide-lower-1500 {
	display: inline;
}

.flexlabel {
	display: none;
}

/* Responsive layout - makes a one column-layout instead of two-column layout */
@media (max-width: 1500px) {
	.flex-container {
		display: flex;
		flex-wrap: wrap;

	}

@media (max-width: 1000px) {
	.flex-container {
		flex-direction: column;
	}

	.hide-over-1000 {
		display: inline;
	}

	.hide-lower-1000 {
		display: none;
	}


}

@media (max-width: 1500px) {
	.flexlabel {
		display: inline-block;
		width: 100px;
		text-align: right;
	}


	.hide-over-1500 {
		display: inline;
	}

	.hide-lower-1500 {
		display: none;
	}

}
@media (orientation:landscape) {
    .hide-over-portrait {
        display: none;
    }
}

@media (orientation:portrait) {
    .hide-over-landscape {
        display: none;
    }
}
}


</style>