<?php
# Simple password protection
#
# (c) http://www.phpbuddy.com
# Author: Ranjit Kumar
# Feel free to use this script but keep this message intact!
# 
# To protect a page include this file in your PHP pages!
# Updated by TCHR, 24.04.2013

	//session_start();
	include('_private/dbi.php');
$GLOBALS['DEBUG_MODE'] = 1;
// CHANGE TO 0 TO TURN OFF DEBUG MODE
// IN DEBUG MODE, ONLY THE CAPTCHA CODE IS VALIDATED, AND NO EMAIL IS SENT

$admin_user_name = "admin"; 
$admin_password = "pass";
//you can change the username and password by changing the above two strings 

//enable password protection
$enable_pw_protection = TRUE;  /* !!!! */
$enable_captcha_protection = TRUE;  /* !!!! */
		

if ($enable_pw_protection==TRUE) 
{
  $_SESSION['ctform'] = array(); // re-initialize the form session data
  if (!isset($_SESSION['user'])) {
	if(isset($_POST['u_name'])) 
		$u_name = $_POST['u_name'];
	if(isset($_POST['u_password'])) 
		$u_password = $_POST['u_password'];
    if (isset($_POST['ct_captcha'])) 
	    $captcha = @$_POST['ct_captcha']; // the user's entry for the captcha code
	if(!isset($u_name)) {
		?>
		<HTML>
		<HEAD>
		   <LINK href="template/main_style.php" rel="stylesheet" type="text/css">
           <META HTTP-EQUIV="content-type" CONTENT="text/html; charset=ISO-8859-1">  
           <TITLE><?php echo $_SERVER['HTTP_HOST']; ?> : Authentication Required</TITLE>
		</HEAD>
		<BODY>
		<table table class='none'>
			 <TR><TD>
			 <B>(Access Restricted to Authorized Personnel)</b></td>
			 </tr></table>
		<?php
		$form_to = "http://$_SERVER[HTTP_HOST]$_SERVER[PHP_SELF]";
		//echo $form_to;
		if(isset($_SERVER["QUERY_STRING"]))
		$form_to = $form_to ."?". $_SERVER["QUERY_STRING"];
		//echo $form_to;
		//echo "Success: ".$_SESSION['captcha_success']

	?>	<body>

				<form action="<?php echo $form_to; ?>" method="POST">
					<table class='none' style='width=400'>
						<tr><td>User Name:</td><?echo $MobileLineBreak?><td><input type=text name=u_name size=20 required></td></tr>
						<tr><td>Password: </td><?echo $MobileLineBreak?><td><input type=password name=u_password size=20 required></td></tr>
<?
         IF ($enable_captcha_protection) { ?>						
						<tr><td><img id="siimage" style="border: 1px solid #000; margin-right: 15px" 
						        src="scripts/securimage/securimage_show.php?sid=<?php echo md5(uniqid()) ?>" 
								alt="CAPTCHA Image" align="left" />
    							</td><?echo $MobileLineBreak?><td><a tabindex="-1" style="border-style: none;" href="#" title="Refresh Image" 
								onclick="document.getElementById('siimage').src = 'scripts/securimage/securimage_show.php?sid=' + Math.random(); this.blur(); 
								return false">
								<img src="scripts/securimage/images/refresh.png" 
								alt="Reload Image" height="25" width="25" onclick="this.blur()" 
								align="bottom" border="0" /></a><br><strong>Enter Code*:</strong>
								<?php echo $_SESSION['captcha_error'] ?><br>
								<input type="text" name="ct_captcha" size="12" maxlength="16" required/>
						</td></tr>
        <? } ?>						
						<tr><td span="2">
						<input class="button" type="submit"  value=" Login" />
					</td></tr>  
					</td><a href='ReqNLogIn.php'>Login vergessen</a></tr>  
					</table>
				</form>
			</body>
		</html>	
		<?php
		exit;
   }
   else {
		if (isset($_SESSION['ctform']['error']) &&  $_SESSION['ctform']['error'] == true): /* The last form submission had 1 or more errors */
          echo "<span class=\"error\">There was a problem with your submission.  Errors are displayed below in red.</span><br /><br />";
        elseif ((isset($_SESSION['captcha_success']) && $_SESSION['captcha_success'] == true)  AND ($enable_captcha_protection== true)): /* form was processed successfully */
          echo "<span class=\"success\">The captcha was correct, but maybe password or username was wrong!</span><br /><br />";
        endif;

        function login_error($host,$php_self) {
			echo "<HTML><HEAD>
			<LINK href='template/main_style.php' rel='stylesheet' type='text/css'>
            <META HTTP-EQUIV='content-type' CONTENT='text/html; charset=ISO-8859-1'>  
            <TITLE>$host :  Administration</TITLE>
			</HEAD><BODY bgcolor=#ffffff>
			<table border=0 cellspacing=0 cellpadding=0 width=100%>
				 <TR><TD align=left>
				 <B>  You Need to log on to access this part of the site! </b> </td>
				 </tr></table>
			<P></P>";
		    //echo "Success: ".$_SESSION['captcha_success'];		
			echo "Error: You are not authorized to access this part of the site!
			<B><a href=$php_self>Click here</a></b> to login again.<P>
			</HTML>";
			session_unregister("adb_password");
			session_unregister("user");
			exit;
		}
		
		$user_checked_passed = false;
        
		
	// Only try to validate the captcha if the form has no errors
    // This is especially important for ajax calls
    if (sizeof($errors) == 0) {
      //require_once dirname(__FILE__) . '../../scripts/securimage/securimage.php';
      require_once 'scripts/securimage/securimage.php';
      $securimage = new Securimage();

      if (($securimage->check($captcha) == false) AND ($enable_captcha_protection== true)){
        $errors['captcha_error'] = 'Incorrect security code entered<br />';
		
      } else
	  $_SESSION['captcha_success'] = true;
    }
		
        $sql = "SELECT `User`.User_Password, `User`.email, `User`.AccessLevel,`User`.Initialen FROM `User` WHERE `User`.User_Name='$u_name';";
	    //$res = mysql_query($sql);
        $res = mysqli_query($conn,$sql);
	    if (!$res) {
          die('Ungültige Anfrage/01: (' . mysqli_errno($conn). ':' . mysqli_error($conn). ') in SQL: '. $sql);
        }	
	    $row = mysqli_fetch_array($res);
		
		if(isset($_SESSION['adb_password'])) {
			$adb_session_password = $_SESSION['adb_password'];
			$adb_session_user = $_SESSION['user'];
			if ($admin_password != $adb_session_password) 

				login_error($_SERVER['HTTP_HOST'],$_SERVER['PHP_SELF']);
			else {
				$user_checked_passed = true;
			}
		}
        if($user_checked_passed == false) {

			if(strlen($u_name)< 2) 
				login_error($_SERVER['HTTP_HOST'],$_SERVER['PHP_SELF']);
			if(isset($admin_password)) {
				if(($admin_password == $u_password) AND ($u_name == $admin_user_name)) {
				    // admin password correct
					$_SESSION["adb_password"]= $admin_password;
					$_SESSION["user"]= $u_name;
					$_SESSION["user_id"]= 'Admin';
					$_SESSION["email"]= 't.ch@chwu.ch';
					$_SESSION["access_level"]= 4;
				}
				else { 
				  // IF (($row[0] == $u_password) 
				   IF ((crypt($u_password, $row[0]) == $row[0])
				      AND (((isset($_SESSION['captcha_success']) && $_SESSION['captcha_success'] == true)) OR ($enable_captcha_protection== false))){
					// user password correct
					$_SESSION["user_password"]= $u_password;
					$_SESSION["user"]= $u_name;
					$_SESSION["access_level"]= $row[2];
					$_SESSION["email"]= $row[1];
					$_SESSION["user_id"]= $row[3];
					}
					else {
				    //password in-correct
					login_error($_SERVER['HTTP_HOST'],$_SERVER['PHP_SELF']);
					}
				}
			}
			else {
				login_error($_SERVER['HTTP_HOST'],$_SERVER['PHP_SELF']);
			}
			$page_location = $_SERVER['PHP_SELF'];
   		    if(isset($_SERVER["QUERY_STRING"])) {
			  $page_location = $page_location ."?". $_SERVER["QUERY_STRING"];
			}
			header ("Location: ". $page_location);
		}
	}	
  }
}
else
{
  echo "<p  class='small_alert'>Note: PW protection disabled!</p>";
}
?>
