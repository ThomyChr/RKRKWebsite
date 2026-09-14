<?php

// The MIT License (MIT)
// 
// Copyright (c) 2014 Federal Office of Topography swisstopo, Wabern, CH
// 
// Permission is hereby granted, free of charge, to any person obtaining a copy
//  of this software and associated documentation files (the "Software"), to deal
//  in the Software without restriction, including without limitation the rights
//  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
//  copies of the Software, and to permit persons to whom the Software is
//  furnished to do so, subject to the following conditions:
// 
// The above copyright notice and this permission notice shall be included in
//  all copies or substantial portions of the Software.
// 
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
//  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
//  FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
//  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
//  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
//  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
//  THE SOFTWARE.
//  


// Source: http://www.swisstopo.admin.ch/internet/swisstopo/en/home/topics/survey/sys/refsys/projections.html (see PDFs under "Documentation")
// Updated 9 dec 2014
// Please validate your results with NAVREF on-line service: http://www.swisstopo.admin.ch/internet/swisstopo/en/home/apps/calc/navref.html (difference ~ 1-2m)

// Convert WGS lat/long (° dec) to CH y (LV95)
function WGStoCHy($lat, $long) {
    // Converts decimal degrees to sexagesimal seconds
    $lat = DECtoSEX($lat);
    $long = DECtoSEX($long);
    
    // Auxiliary values (% Bern)
    $lat_aux = ($lat - 169028.66) / 10000;
    $long_aux = ($long - 26782.5) / 10000;
    
    // Process Y (East) in LV03
    $y_lv03 = 600072.37 
       + 211455.93 * $long_aux 
       -  10938.51 * $long_aux * $lat_aux
       -      0.36 * $long_aux * pow($lat_aux, 2)
       -     44.54 * pow($long_aux, 3);
    
    // Convert to LV95 by adding 2,000,000
    $y_lv95 = $y_lv03 + 2000000;
       
    return $y_lv95;
}

/* replaced with new function from Mario
// Convert WGS lat/long (° dec) to CH y
function WGStoCHy($lat, $long) {

  // Converts decimal degrees sexagesimal seconds
  //$lat = DECtoSEX($lat);
  //$long = DECtoSEX($long);
  
  // Auxiliary values (% Bern)
  $lat_aux = ($lat - 169028.66)/10000;
  $long_aux = ($long - 26782.5)/10000;
  
  // Process Y
  $y = 600072.37 
     + 211455.93 * $long_aux 
     -  10938.51 * $long_aux * $lat_aux
     -      0.36 * $long_aux * pow($lat_aux,2)
     -     44.54 * pow($long_aux,3);
     
  return $y;
}
*/

// Convert WGS lat/long (° dec) to CH x (LV95)
function WGStoCHx($lat, $long) {
    // Converts decimal degrees to sexagesimal seconds
    $lat = DECtoSEX($lat);
    $long = DECtoSEX($long);
    
    // Auxiliary values (% Bern)
    $lat_aux = ($lat - 169028.66) / 10000;
    $long_aux = ($long - 26782.5) / 10000;
    
    // Process X (North) in LV03
    $x_lv03 = 200147.07
       + 308807.95 * $lat_aux 
       +   3745.25 * pow($long_aux, 2)
       +     76.63 * pow($lat_aux, 2)
       -    194.56 * pow($long_aux, 2) * $lat_aux
       +    119.79 * pow($lat_aux, 3);
    
    // Convert to LV95 by adding 1,000,000
    $x_lv95 = $x_lv03 + 1000000;
       
    return $x_lv95;
}

/* replaced with new function from Mario
// Convert WGS lat/long (° dec) to CH x
function WGStoCHx($lat, $long) {

  // Converts decimal degrees sexagesimal seconds
  $lat = DECtoSEX($lat);
  $long = DECtoSEX($long);

  // Auxiliary values (% Bern)
  $lat_aux = ($lat - 169028.66)/10000;
  $long_aux = ($long - 26782.5)/10000;
  
  // Process X
  $x = 200147.07
     + 308807.95 * $lat_aux 
     +   3745.25 * pow($long_aux,2)
     +     76.63 * pow($lat_aux,2)
     -    194.56 * pow($long_aux,2) * $lat_aux
     +    119.79 * pow($lat_aux,3);
       
  return $x;
  
}
*/

// Convert CH y/x to WGS lat
function CHtoWGSlat($y, $x) {

  // Converts military to civil and  to unit = 1000km
  // Auxiliary values (% Bern)
  $y_aux = ($y - 600000)/1000000;
  $x_aux = ($x - 200000)/1000000;
  
  // Process lat
  $lat = 16.9023892
       +  3.238272 * $x_aux
       -  0.270978 * pow($y_aux,2)
       -  0.002528 * pow($x_aux,2)
       -  0.0447   * pow($y_aux,2) * $x_aux
       -  0.0140   * pow($x_aux,3);
    
  // Unit 10000" to 1 " and converts seconds to degrees (dec)
  $lat = $lat * 100/36;
  
  return $lat;
  
}

// Convert CH y/x to WGS long
function CHtoWGSlong($y, $x) {

  // Converts military to civil and  to unit = 1000km
  // Auxiliary values (% Bern)
  $y_aux = ($y - 600000)/1000000;
  $x_aux = ($x - 200000)/1000000;
  
  // Process long
  $long = 2.6779094
        + 4.728982 * $y_aux
        + 0.791484 * $y_aux * $x_aux
        + 0.1306   * $y_aux * pow($x_aux,2)
        - 0.0436   * pow($y_aux,3);
     
  // Unit 10000" to 1 " and converts seconds to degrees (dec)
  $long = $long * 100/36;
     
  return $long;
  
}

// Convert decimal degrees to sexagesimal seconds
function DECtoSEX($angle) {
    // Get the degrees part of the angle
    $deg = floor($angle);
    // Get the minutes part of the angle
    $min = floor(($angle - $deg) * 60);
    // Get the seconds part of the angle
    $sec = ((($angle - $deg) * 60) - $min) * 60;
    // Return the sexagesimal seconds (degree * 3600 + minute * 60 + seconds)
    return $deg * 3600 + $min * 60 + $sec;
}

/* replaced with new function from Mario
// Convert DEC angle to SEX DMS
function DECtoSEX($angle) {

  // Extract DMS
  $deg = intval( $angle );
  $min = intval( ($angle-$deg)*60 );
  $sec =  ((($angle-$deg)*60)-$min)*60;   

  // Result in sexagesimal seconds
  return $sec + $min*60 + $deg*3600;
  
}
*/

?>