<?php
//Save in cookie to access with $_COOKIE["screenXYZ"];

//SetScreenResToCookie.php
if (isset($_POST)) {
    //echo "success";
    setcookie("screenW", $_POST["screenWidth"], time() + (86400 * 30), '/');
    setcookie("screenH", $_POST["screenHeight"], time() + (86400 * 30), '/');
} else {
    //echo "Error. $" . "_POST array is not set!";
}
  
//where u need the Screen res(only a example):
  
//header.php
//The function 

function screenResToCookie()
{
 if (isset($_SERVER["HTTP_X_FORWARDED_PROTO"])) {
        $http = $_SERVER["HTTP_X_FORWARDED_PROTO"];
    } else {
        $http = $_SERVER["REQUEST_SCHEME"];
    }
    $url = $http . "://" . $_SERVER["HTTP_HOST"];
    echo "<script>
        var damn = setInterval(() => {
            var formData = {
                screenWidth: screen.width,
                screenHeight: screen.height
            };

            $.ajax({
                url: '" . $url ."/.../SetJScookie.php?" . time() ."',//This needs to be the URL on the domain u access the website from. ajax dont allow HTTPS
                type: 'POST',//because 'SetScreenResToCookie.php' use POST Array
                data: formData, //data in json format
                async: false, //enable or disable async (optional, but suggested as false if you need to populate data afterwards)
                success: function(response, textStatus, jqXHR) {
                    console.log(response);
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.log(jqXHR);
                    console.log(textStatus);
                    console.log(errorThrown);
                }
            });
        }, 1000);
    </script>";
}

function ReturnHeader($..., $...) {
  $Mobile = false;//If we dont wanne use Bootstrap or @media
  screenResToCookie();//set the cookie for us
  if (intval($_COOKIE["screenW"]) < 735) {
        $Mobile = true;
    }
  
  if ($Mobile) {
	//print header with mobile style(Dropdowns added or whatever)
  } else {
   //print header with Computer/Tablet style 
  }
}
?>
