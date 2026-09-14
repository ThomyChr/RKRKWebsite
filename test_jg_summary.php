<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" >
<?php
	//session_start();
?>
<script>
//Settings
var vJagdgebietName="26 Knonau"
var vJagdgebiet=26

//var vSiteSetting="https://tchr-frog.synology.me:5682/rkropen/pub/rkrk_jg_summary.php?Reg="+vJagdgebiet+"&Color=white";
//var vSiteSetting="https://tchr-frog.synology.me/rkropen/pub/rkrk_jg_summary.php?Reg="+vJagdgebiet+"&Color=white";
var vSiteSetting="rkrk_jg_summary.php?Reg="+vJagdgebiet+"&Color=white";
var vWidth="100%"
var vHeight="3500"

//var vTableStyle= 'style="border-collapse: collapse;border-style:hidden;"'
var vTableStyle= ''
var vCenterText = 'style="text-align: center; vertical-align: middle;"'

//TableStyle
document.write('<style>');
document.write('table, th, td {');
document.write('  border: 1px solid transparent;');
document.write('  border-collapse: collapse;');
document.write('}');
document.write('</style>');


//Titel
document.write('<H1>'+vJagdgebietName+'</H1>');

// JG Zusammenfassung
//document.write('<embed type="text/html" src="'+vSiteSetting+'"  width="'+vWidth+'" height="'+vHeight+'">');
document.write('<iframe type="text/html" src="'+vSiteSetting+'"  width="'+vWidth+'" height="'+vHeight+'">');
  
</script>
