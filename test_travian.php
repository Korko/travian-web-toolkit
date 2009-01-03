<?php

include('Travian3.php');

$travian = new Travian3();

// Connect to the server
$travian->connect('s5.travian.fr', 'Pseudonyme', 'test');

// Ok now hum... Look for the map !
//$travian->map();

// Ok... hum... give me... the square n° 163857 etc
var_export($travian->get_square(163857));echo("<br />");
var_export($travian->get_square(167066));echo("<br />");
var_export($travian->get_square(163057));echo("<br />");
var_export($travian->get_square(165461));echo("<br />");

/* EOF */
