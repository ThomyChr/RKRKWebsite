<?php
// ==================
// Funktionen für die Rehktitzrettung
// Input Forms
// ==================

	// ===========================================
	// Gobal Definitions
	// ===========================================
	$ControlButtonHeight = 20;
		

	// ===========================================
	// Form Functions
	// ===========================================


function TextField($Value ,$Size=10, $class="") {
	$output = "";
	$output .= "<font ";
	if ($Size >0) {
	  $output .= "size='" . $Size . "'"  ;
	}
	if ($class!="") {
		$output .= " class='" . $class . "' ";
	}
	$output .= " >";
	echo $Value;
	$output .= "</font>" .chr(10);
	Return $output;
}

function JSFunctionMoveOverField($FieldName, $Value, $JSFunction, $JSValue) {
	$output = "";
	$output .= "<span id='". $FieldName . "' name='" . $FieldName ."' onmouseover='".$JSFunction."(".$JSValue.")'>";
	$output .= $Value;
	$output .= "</span>" .chr(10);
	Return $output;
}


function InputTimeField($FieldName, $Value ,$Size=10, $align="left", $type="", $class="") {
	$output = "";
	$output .= "<input ";
	if ($type=="") {
		$output .= " type= 'time' ";
	} else {
		$output .= " type='" . $type . "' ";
	}
	$output .= " id='". $FieldName . "' name='" . $FieldName ."' value='" . $Value ."'";
	if ($Size >0) {
	  $output .= "size='" . $Size . "'" . " align='" . $align ."'" ;
	}
	$output .= " pattern='^(24:00)|(([01]\d|2[0-3]):([0-5]\d))$' placeholder='00:00' " .chr(10);
	if ($class!="") {
		$output .= " class='" . $class . "' ";
	}
	$output .= " >";
	Return $output;
}

function InputField($FieldName, $Value ,$Size=10, $align="left", $type="", $class="" , $action="" , $show="") {
	if (function_exists('rkrk_str')) {
		$Value = rkrk_str($Value);
		$FieldName = rkrk_str($FieldName);
	} elseif (is_array($Value) || is_object($Value)) {
		$Value = '';
	}
	$output = "";
	$output .= "<input " . $show;
	if ($type=="") {
		$output .= " type= 'text' ";
	} else {
		$output .= " type='" . $type . "' ";
	}
	$output .= " id='". $FieldName . "' name='" . $FieldName ."' value='" . $Value ."'";
	if ($Size >0) {
	  //$output .= "size='" . $Size . "'" . " align='" . $align ."'" ;
	  $output .= "size='" . $Size . "'" . " style='text-align: " . $align ."'" ;
	//style="text-align: right;"

	}
	if ($class!="") {
		$output .= " class='" . $class . "' ";
	}
	if ($type=="disabled") {
		$output .= " disabled ";
	}
	if ($action!="") 
		$output .= " " . $action . " ";
	$output .= " >" .chr(10);
	Return $output;
}


function InputJSFunctionField($FieldName, $Value, $JSFunction, $JSValue, $line , $Size=10, $align="left", $type="", $class="" , $action="" , $show="") {
	$output = "";
	$output .= "<input " . $show;
	if ($type=="") {
		$output .= " type= 'text' ";
	} else {
		$output .= " type='" . $type . "' ";
	}
	$output .= " id='". $FieldName . "' name='" . $FieldName ."' value='" . $Value ."'";
	if ($Size >0) {
	  //$output .= "size='" . $Size . "'" . " align='" . $align ."'" ;
	  $output .= "size='" . $Size . "'" . " style='text-align: " . $align ."'" ;
	}
	$output .= "  onchange='" . $JSFunction ."(". $JSValue . ", " . $line .")' " .chr(10);
	if ($class!="") {
		$output .= " class='" . $class . "' ";
	}
	if ($type=="disabled") {
		$output .= " disabled ";
	}
	if ($action!="") 
		$output .= " " . $action . " ";
	$output .= " >" .chr(10);
	Return $output;
}


function InputJSFunctionOnKeyUp($FieldName, $Value, $JSFunction, $JSValue, $Size=10, $align="left", $type="", $class="") {
	$output = "";
	$output .= "<input ";
	if ($type=="") {
		$output .= " type= 'text' ";
	} else {
		$output .= " type='" . $type . "' ";
	}
	$output .= " id='". $FieldName . "' name='" . $FieldName ."' value='" . $Value ."'";
	if ($Size >0) {
	  $output .= "size='" . $Size . "'" . " align='" . $align ."'" ;
	}
	$output .= '  onkeyup="' . $JSFunction .'('. $JSValue . ')" ' .chr(10);
	if ($class!="") {
		$output .= " class='" . $class . "' ";
	}
	$output .= " >";
	Return $output;
}


function InputTextarea($FieldName, $Value ,$Size=10, $Rows=3, $Colums=50, $align="left", $type="", $class="") {
	if (function_exists('rkrk_str')) {
		$Value = rkrk_str($Value);
		$FieldName = rkrk_str($FieldName);
	} elseif (is_array($Value) || is_object($Value)) {
		$Value = '';
	}
	$output = "";
	$output .= "<textarea ";
	if ($type=="") {
		$output .= " type= 'text' ";
	} else {
		$output .= " type='" . $type . "' ";
	}
	$output .= " id='". $FieldName . "' name='" . $FieldName ."' rows='" . $Rows ."' cols='" . $Colums ."'";
	if ($class!="") {
		$output .= " class='" . $class . "' ";
	}
	$output .= " >" .chr(10);
	$output .= $Value;
	$output .= "</textarea>" .chr(10);
	Return $output;
}


function SelectField ($FieldName, $Value ,$Options, $align="left", $class="") {
	// align="left|right|middle|top|bottom"
/*				$output .= "<select type='text' id='". $Inputfieldname . "' name='" . $Inputfieldname ."'>";
					foreach ($OptionJaeger as $opt) {
						if (like($feld->Jaeger , $opt)) {
							$output .= "<option value='". $opt . "' selected>". $opt . "</option>".chr(10);
						} else {
							$output .= "<option value='". $opt . "'>". $opt . "</option>".chr(10);
						}
					}
				$output .= "</select>";
*/
	$output = "";
	$output .= "<select ";
	if ($class!="") {
		$output .= " class='" . $class . "' ";
	}
	$output .= " type='text' id='". $FieldName . "' name='" . $FieldName ."' >";
	if (!is_array($Options)) {
		$Options = array();
	}
	if (function_exists('rkrk_str')) {
		$Value = rkrk_str($Value);
		$FieldName = rkrk_str($FieldName);
	}
	foreach ($Options as $opt) {
			$optText = function_exists('rkrk_str') ? rkrk_str($opt) : (is_array($opt) ? '' : (string)$opt);
			if ($Value == $optText) {
				$output .= "<option value='". $optText . "' selected>". $optText . "</option>".chr(10);
			} else {
				$output .= "<option value='". $optText . "'>". $optText . "</option>".chr(10);
			}
	}
	$output .= "</select>".chr(10);
	Return $output;
}	

function RadioField ($FieldName, $Value ,$Options, $class="") {
	$output = '<span class="rkrk-sels rkrk-reg-pills">';
	foreach ($Options as $Key => $Opt) {
		$checked = like($Value, $Key) ? ' checked' : '';
		$pillCls = 'rkrk-sel rkrk-reg-pill';
		if ($class !== '') {
			$pillCls .= ' ' . $class;
		}
		$output .= '<label class="' . htmlspecialchars((string)$pillCls, ENT_QUOTES, 'UTF-8') . '">';
		$output .= '<input type="radio" name="' . htmlspecialchars((string)$FieldName, ENT_QUOTES, 'UTF-8') . '" value="' . htmlspecialchars((string)$Key, ENT_QUOTES, 'UTF-8') . '"' . $checked . '>';
		$output .= ' ' . htmlspecialchars((string)$Opt, ENT_QUOTES, 'UTF-8');
		$output .= '</label>';
	}
	$output .= '</span>';
	Return $output;
}	


function SelectJSFunctionField ($FieldName, $Value, $Options, $JSFunction, $JSValue, $line , $align="left", $class="") {
	// align="left|right|middle|top|bottom"
/*				$output .= "<select type='text' id='". $Inputfieldname . "' name='" . $Inputfieldname ."'>";
					foreach ($OptionJaeger as $opt) {
						if (like($feld->Jaeger , $opt)) {
							$output .= "<option value='". $opt . "' selected>". $opt . "</option>".chr(10);
						} else {
							$output .= "<option value='". $opt . "'>". $opt . "</option>".chr(10);
						}
					}
				$output .= "</select>";
*/
//	$output .= "<select type='text' id='". $FieldName . "' name='" . $FieldName ."'>";
	$output = "";
	$output .= "<select type='text' id='". $FieldName . "' name='" . $FieldName ."' onchange='" . $JSFunction ."(". $JSValue . ", " . $line .")'";
	if ($class!="") {
		$output .= " class='" . $class . "' ";
	}
	$output .= " >";
//	$output .= "<select type='text' id='". $FieldName . "' name='" . $FieldName ."' onchange='UpdJaeger(this.value)'>";
	foreach ($Options as $opt) {
//		if (isset($opt["Opt"])) {
			//if (like($Value , $opt)) {
			if ($Value == $opt) {
				$output .= "<option value='". $opt . "' selected>". $opt . "</option>".chr(10);
			} else {
				$output .= "<option value='". $opt . "'>". $opt . "</option>".chr(10);
			}
//		}	
	}
	$output .= "</select>".chr(10);
	Return $output;
}	

function SelectFieldMultiOptArr ($FieldName, $Value ,$Options, $align="left", $class="") {
/*
				$output .= "<select type='text' id='". $Inputfieldname . "' name='" . $Inputfieldname ."'>";
					foreach ($OptionFrei as $opt) {
						if (like($feld->Freigabe , $opt["Opt"])) {
							$output .= "<option value='". $opt["Opt"] . "' selected>". $opt["Opt"] . "</option>".chr(10);
						} else {
							$output .= "<option value='". $opt["Opt"] . "'>". $opt["Opt"] . "</option>".chr(10);
						}
					}
				$output .= "</select>";
*/
	$output = "";
	$output .= "<select type='text' id='". $FieldName . "' name='" . $FieldName ."'";
	if ($class!="") {
		$output .= " class='" . $class . "' ";
	}
	$output .= " >";
	if (!is_array($Options)) {
		$Options = array();
	}
	if (function_exists('rkrk_str')) {
		$Value = rkrk_str($Value);
		$FieldName = rkrk_str($FieldName);
	} else {
		$Value = is_array($Value) || is_object($Value) ? '' : (string)($Value ?? '');
	}
		foreach ($Options as $opt) {
			if (!is_array($opt) || !isset($opt["Opt"])) {
				continue;
			}
			$optVal = function_exists('rkrk_str') ? rkrk_str($opt["Opt"]) : (string)$opt["Opt"];
			if (like($Value , $optVal)) {
				$output .= "<option value='". $optVal . "' selected>". $optVal . "</option>".chr(10);
			} else {
				$output .= "<option value='". $optVal . "'>". $optVal . "</option>".chr(10);
			}
		}
	$output .= "</select>" . chr(10);
	Return $output;
}


function DateJSFunctionField ($FieldName, $Value, $Min , $Max , $JSFunction, $JSValue, $line , $Size=10, $align="left", $type="", $class="") {
/*<label for="start">Start date:</label>

	<input type="date" id="start" name="trip-start"
       value="2018-07-22"
       min="2018-01-01" max="2018-12-31">

*/
	$output = "";
	$output .= "<input type='date' id='". $FieldName . "' name='" . $FieldName ."'";
	$output .= " value = '". $Value . "'";
	$output .= " placeholder='dd-mm-yyyy'";
	$output .= " min = '". $Min . "' max= '". $Max . "'" ;
	$output .= " onchange='" . $JSFunction ."(". $JSValue . ", " . $line .")'";
	if ($class!="") {
		$output .= " class='" . $class . "' ";
	}
	$output .= " >";
	Return $output;
}

function DateField ($FieldName, $Value, $Min , $Max , $align="left", $class="") {
/*<label for="start">Start date:</label>

	<input type="date" id="start" name="trip-start"
       value="2018-07-22"
       min="2018-01-01" max="2018-12-31">

*/
	$output = "";
	$output .= "<input type='date' id='". $FieldName . "' name='" . $FieldName ."'";
	$output .= " value = '". $Value . "'";
	$output .= " placeholder='dd-mm-yyyy'";
	$output .= " min = '". $Min . "' Min= '". $Max . "'";
	if ($class!="") {
		$output .= " class='" . $class . "' ";
	}
	$output .= " >";
	Return $output;
}

function CheckField ($FieldName, $Value, $align="left", $class="") {
/*
      <input type="checkbox" id="scales" name="scales"
             checked>
*/
	$output = "";
	$output .= "<input type='checkbox' id='". $FieldName . "' name='" . $FieldName ."'";
	if ($Value) {
		$output .= " checked";
	}
	if ($class!="") {
		$output .= " class='" . $class . "' ";
	}
	$output .= ">" . chr(10);
	Return $output;
}

    // ===========================================
	// Table Functions
	// ===========================================

function AlternateLineTable($colorTableEven, $FontSize="100%", $FontSizeInPoints=12, $backgroundTableEven="#ecf7ec") {
		$even = trim((string)$colorTableEven);
		$odd = trim((string)$backgroundTableEven);
		if ($even === '') {
			$even = '#eeeeee';
		}
		if ($odd === '') {
			$odd = '#fbfbfb';
		}
		$pt = is_numeric($FontSize)
			? ((float)$FontSize * ((float)$FontSizeInPoints / 100))
			: ((float)$FontSizeInPoints * (floatval($FontSize) / 100.0));
		if (!is_numeric($pt) || $pt < 8) {
			$pt = (float)$FontSizeInPoints;
		}
		$ptCss = round($pt, 1) . 'pt';

		echo "    <style>" .chr(10);
		echo "table {" .chr(10);
		echo "  border-collapse: collapse;" .chr(10);
		echo "  border-spacing: 0;" .chr(10);
		echo "  background-color: #fff;" .chr(10);
		echo "  font-size: " . $ptCss . ";" .chr(10);
		echo "  line-height: 1.2;" .chr(10);
		echo "}" .chr(10);
		echo "th, td {" .chr(10);
		echo "  padding: 1px 3px;" .chr(10);
		echo "  text-align: left;" .chr(10);
		echo "  vertical-align: middle;" .chr(10);
		echo "  border: none;" .chr(10);
		echo "  border-bottom: 1px solid #eee;" .chr(10);
		echo "  font-size: " . $ptCss . ";" .chr(10);
		echo "}" .chr(10);
		echo "th {" .chr(10);
		echo "  background: #f1f1f1;" .chr(10);
		echo "  color: #222;" .chr(10);
		echo "  font-weight: 700;" .chr(10);
		echo "  border-bottom: 1px solid #e2e2e2;" .chr(10);
		echo "}" .chr(10);
		echo "tr:nth-child(even) { background-color: " . $even . "; }" .chr(10);
		echo "tr:nth-child(odd) { background-color: " . $odd . "; }" .chr(10);
		echo "input, textarea, select {" .chr(10);
		echo "  background: rgba(255,255,255,0.92);" .chr(10);
		echo "  border: 1px solid #d8e4d4;" .chr(10);
		echo "  font-size: " . $ptCss . ";" .chr(10);
		echo "  padding: 0 3px;" .chr(10);
		echo "}" .chr(10);
		echo "body { font-size: " . $ptCss . "; }" .chr(10);
		echo "    </style>" .chr(10);
}

function SetTableWidth($width) {
		echo "    <style>" .chr(10);
		echo "table {" .chr(10);
		echo "  width: " . $width . ";" .chr(10);
		echo "}" .chr(10);
		echo "    </style>" .chr(10);
}
	

	//======================
	// Pagecontrol
	// =====================
	
	function PageControl($ActPage, $MaxPages, $AnzLinesPPage, $FontSize="100%" , $AnzLineField = false, $ButtonText="Speichern", $Einlesen=true, $UrlParamter="") {
		global $GenIconLoc, $AnzLines, $FontSizes, $WebFreigabe, $ControlButtonHeight;
		// =================
		// Pagecontrol
		// =================
		if ($ActPage+1<=$MaxPages) {
			$NextPage= $ActPage+1;
		} else {  
			$NextPage= 1;
		}
		if ($ActPage+5<=$MaxPages) {
			$NextFFPage= $ActPage+5;
		} else {  
			$NextFFPage= $MaxPages;
		}
		if ($ActPage>1) {
			$PrevPage= $ActPage-1;
		} else {  
			$PrevPage= $MaxPages;
		}
		if ($ActPage-5>1) {
			$PrevFFPage= $ActPage-5;
		} else {  
			$PrevFFPage= 1;
		}
		$enDelQ = (string)($_GET["EnableDelete"] ?? '');
		$dbwQ = (string)($_GET["DBW"] ?? '');
		if (GetAccess("DeleteData")) {
			$DebugParamter1 = "?EnableDelete=" . $enDelQ . "&DBW=" . $dbwQ;
			$DebugParamter2 = "&EnableDelete=" . $enDelQ . "&DBW=" . $dbwQ;
		} else {
			if ($WebFreigabe == "intern") {
			$DebugParamter1 = "?EnableDelete=" . $enDelQ;
			$DebugParamter2 = "&EnableDelete=" . $enDelQ;
			} else {
			$DebugParamter1 = "";
			$DebugParamter2 = "";
			}
		}

		if (isset($_GET["EnableNeu"])) {
			if ($DebugParamter1=="") 
				$DebugParamter1 .= "?";
			else
				$DebugParamter1 .= "&";
			if ($DebugParamter2=="") 
				$DebugParamter2 .= "&";
			else
				$DebugParamter2 .= "&";
			$DebugParamter1 .= "EnableNeu=" . $_GET["EnableNeu"];
			$DebugParamter2 .= "EnableNeu=" . $_GET["EnableNeu"];
		}
		if ($MaxPages>1) {
			$style ="";
			echo " <span style='vertical-align:middle;'>" .chr(10);
			echo "<input type='image' ". $style ." value='1st page' src='" . $GenIconLoc . "/go_left.png' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?ActPage=" . 1 . $DebugParamter2 . $UrlParamter ."' height='" . $ControlButtonHeight . "'>" .chr(10);
			echo "<input type='image' ". $style ." value='pref ff page' src='" . $GenIconLoc . "/ff_left.png' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?ActPage=" . $PrevFFPage . $DebugParamter2 . $UrlParamter ."' height='" . $ControlButtonHeight . "'>" .chr(10);
			echo "<input type='image' ". $style ." value='prev page' src='" . $GenIconLoc . "/step_left.png' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?ActPage=" . $PrevPage . $DebugParamter2 . $UrlParamter ."' height='" . $ControlButtonHeight . "'>" .chr(10);
			echo " " . $ActPage . " / " .$MaxPages . " " ;
			echo "<input type='image' ". $style ." value='next page' src='" . $GenIconLoc . "/step_right.png' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?ActPage=" . $NextPage . $DebugParamter2 . $UrlParamter ."' height='" . $ControlButtonHeight . "'>" .chr(10);
			echo "<input type='image' ". $style ." value='next ff page' src='" . $GenIconLoc . "/ff_right.png' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?ActPage=" . $NextFFPage . $DebugParamter2 . $UrlParamter ."' height='" . $ControlButtonHeight . "'>" .chr(10);
			echo "<input type='image' ". $style ." value='last page' src='" . $GenIconLoc . "/go_right.png' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?ActPage=" . $MaxPages . $DebugParamter2 . $UrlParamter ."' height='" . $ControlButtonHeight . "'>" .chr(10);
			echo "</span>" .chr(10);
		}
		if  ($AnzLineField) {
			if ($AnzLinesPPage>1) {
				echo SelectField("AnzLinesPPage", $AnzLinesPPage ,$AnzLines );
			}
			echo SelectField("FontSize", $FontSize ,$FontSizes );
		}
		if ($AnzLinesPPage<=30) {
			if ($ButtonText!="") {
				echo "<input type='submit' value='".$ButtonText."' formaction='" .htmlspecialchars($_SERVER["PHP_SELF"]). "?ActPage=" . $ActPage . "&DoSafe=Y". $DebugParamter2 .$UrlParamter ."'>" .chr(10);
				//echo "<input type='submit' value='".$ButtonText."' src='" . $GenIconLoc . '/savedata2.png" formaction="' .htmlspecialchars($_SERVER["PHP_SELF"]). '?ActPage=' . $ActPage . '&DoSafe=Y'. $DebugParamter2 .'">' .chr(10);
			}	
		}
		if ($Einlesen) {
			echo '<input type="button" name="Einlesen" value="Einlesen" formaction="' .htmlspecialchars($_SERVER["PHP_SELF"]). $DebugParamter1 .$UrlParamter .'" class="button buttonG"> '. chr(10);
			//echo '<input type="submit" name="Einlesen" value="Einlesen" src="' . $GenIconLoc . '/einlesen.png" formaction="' .htmlspecialchars($_SERVER["PHP_SELF"]). $DebugParamter1 .'"> '. chr(10);
		}

		
	}

function rkrk_pill_h($s) {
	return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function rkrk_echo_pill_checkbox($name, $label, $checked, $opts = array()) {
	rkrk_bind_exclusive_pills_script();
	$exclusive = !empty($opts['exclusive']);
	$value = array_key_exists('value', $opts) ? (string)$opts['value'] : 'on';
	$cls = 'rkrk-sel rkrk-reg-pill';
	if ($exclusive) {
		$cls .= ' rkrk-sel-exclusive rkrk-reg-pill-exclusive';
	}
	echo '<label class="' . rkrk_pill_h($cls) . '">';
	echo '<input type="checkbox" name="' . rkrk_pill_h($name) . '" value="' . rkrk_pill_h($value) . '"';
	if ($exclusive) {
		echo ' data-rkrk-exclusive="1"';
	}
	if ($checked) {
		echo ' checked';
	}
	echo '> ' . rkrk_pill_h($label);
	echo '</label>';
}

function rkrk_echo_pills_open() {
	echo '<span class="rkrk-sels rkrk-reg-pills">';
}

function rkrk_echo_pills_close() {
	echo '</span>';
}

function rkrk_bind_exclusive_pills_script() {
	static $done = false;
	if ($done) {
		return;
	}
	$done = true;
	echo "<script>
(function(){
  if (window.__rkrkExclusivePillsBound) return;
  window.__rkrkExclusivePillsBound = true;
  function isExclusive(cb){
    if (!cb) return false;
    if (cb.getAttribute && cb.getAttribute('data-rkrk-exclusive') === '1') return true;
    var lab = cb.closest && cb.closest('.rkrk-sel-exclusive, .rkrk-reg-pill-exclusive');
    if (lab) return true;
    var v = String(cb.value || '').toLowerCase();
    if (v === 'alle' || v === 'all' || v === 'leer') return true;
    var n = String(cb.name || '');
    return n === 'RegAlle' || n.indexOf('RegAlle') === 0;
  }
  document.addEventListener('change', function(ev){
    var cb = ev.target;
    if (!cb || cb.type !== 'checkbox') return;
    var root = cb.closest && cb.closest('.rkrk-reg-pills, .rkrk-sels');
    if (!root) return;
    var boxes = Array.prototype.slice.call(root.querySelectorAll('input[type=\"checkbox\"]'));
    if (!boxes.length) return;
    if (cb.checked && isExclusive(cb)) {
      boxes.forEach(function(b){ if (b !== cb) b.checked = false; });
    }
    if (cb.checked && !isExclusive(cb)) {
      boxes.forEach(function(b){ if (isExclusive(b)) b.checked = false; });
    }
  });
})();
</script>\n";
}

function rkrk_print_region_pills($RegionSettings, &$EnabledRegion, $nameSuffix = '0', $RegSelected = '', $requireAktiv = true) {
	if (!is_array($EnabledRegion)) {
		$EnabledRegion = array();
	}
	$isPost = isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST';
	$regSel = trim((string)$RegSelected);
	$regSelParts = preg_split('/[\\s,]+/', $regSel, -1, PREG_SPLIT_NO_EMPTY);
	if (!is_array($regSelParts)) {
		$regSelParts = array();
	}
	$getAlle = !$isPost && ($regSel === 'alle' || strcasecmp($regSel, 'ALL') === 0);
	$postAlle = $isPost && !empty($_POST['RegAlle']);
	$noGetReg = !$isPost && $regSel === '' && $regSelParts === array();
	$alleChecked = $getAlle || $postAlle || $noGetReg;
	$NoSelection = true;

	rkrk_echo_pills_open();
	rkrk_echo_pill_checkbox('RegAlle', 'alle', $alleChecked, array('exclusive' => true));

	if (is_array($RegionSettings)) {
		foreach ($RegionSettings as $Reg) {
			if (!is_array($Reg) || !isset($Reg['Reg'])) {
				continue;
			}
			$key = (string)$Reg['Reg'];
			if ($key === '' || $key === 'NO' || strcasecmp($key, 'ALL') === 0 || strcasecmp($key, 'leer') === 0) {
				continue;
			}
			if ($requireAktiv && empty($Reg['Aktiv'])) {
				continue;
			}
			$posted = $isPost && !empty($_POST[$key . $nameSuffix]);
			$fromGet = !$isPost && ($regSel === $key || in_array($key, $regSelParts, true));
			$enabled = $alleChecked || $posted || $fromGet;
			$EnabledRegion[$key] = $enabled ? 1 : 0;
			if ($enabled) {
				$NoSelection = false;
			}
			rkrk_echo_pill_checkbox($key . $nameSuffix, $key, (!$alleChecked && ($posted || $fromGet)));
		}
	}
	rkrk_echo_pills_close();
	rkrk_bind_exclusive_pills_script();
	return $NoSelection;
}

// Print Selection in Form
Function PrintSelection($Options, $GrpName, $OptNum, $SetAll = false, $DoPrint = true) {
	if ($SetAll && !isset($Options["alle"])) {
		$Options["alle"]["Text"] = "alle";
		$Options["alle"]["Value"] = false;
	}
	$EnabledOptions = Array();
	$i = $OptNum;
	$anySelected = false;
	foreach ($Options as $Opt) {
		if (is_array($Opt) && isset($Opt["Text"]) && !empty($Opt["Value"])) {
			$anySelected = true;
			break;
		}
	}
	$NoRegionSelected = !$anySelected;

	if ($DoPrint) {
		echo '<span class="rkrk-sels rkrk-reg-pills">';
	}
	foreach ($Options as $Opt ) {
		if (!is_array($Opt) || !isset($Opt["Text"])) {
			continue;
		}
	//foreach (array_diff($Options,Array("-")) as $Opt ) {
		$Options[$Opt["Text"]]["List"] ="";
		if (($Opt["Value"]) or
				(($Opt["Text"]=="alle")
				and $NoRegionSelected)) {
			$checked = "checked";
			$Options[$Opt["Text"]]["Value"] = 1;
			$Options["List"][$Opt["Text"]] =$Opt["Text"];
		
			$NoRegionSelected=false;
		} else {
			$checked = "";
			$Options[$Opt["Text"]]["Value"]  = 0;
		}
		if ($DoPrint) {
			$txt = (string)$Opt["Text"];
			$tl = strtolower($txt);
			$pillCls = 'rkrk-sel rkrk-reg-pill';
			if ($tl === 'alle' || $tl === 'all' || $tl === 'leer') {
				$pillCls .= ' rkrk-sel-exclusive rkrk-reg-pill-exclusive';
			}
			echo '<label class="' . $pillCls . '"><input type="checkbox" name="'. $GrpName ."-". $i . '" value="' . htmlspecialchars($txt, ENT_QUOTES, 'UTF-8') . '" ' . $checked . '> '. htmlspecialchars($txt, ENT_QUOTES, 'UTF-8') .'</label>';
		}
		$i++;
	}
	if ($DoPrint) {
		echo '</span>';
		rkrk_bind_exclusive_pills_script();
	}

	Return $Options;
}

	
?>