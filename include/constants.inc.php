<?php

// Google Maps

switch ($_SERVER["REMOTE_ADDR"])
{
  case '192.168.0.242': $key = 'ABQIAAAAOxdgGFlFzzfFqQmRHP5buBSkUkZlfUOJbe2nOPa-VC5--SVDOhR53yMC29HWcv9Jcko3X9UnKfFRcg';
                        break;
  default:              $key = 'ABQIAAAAE96-m4CkeGYOWZ0_07GOmxT45Q6xARrkG2OhD-eAgLQWLl68ghSsWsoNmWjRzsbCC_6Lvz_4KjeaaQ';
                        break;
}

// google maps api key gilt für example.org
define( "KEY",				'ABQIAAAAE96-m4CkeGYOWZ0_07GOmxT45Q6xARrkG2OhD-eAgLQWLl68ghSsWsoNmWjRzsbCC_6Lvz_4KjeaaQ');

define( "FOC_LONG",			"13.724445104598999"); // Longitude des Startpunktes
define( "FOC_LAT",			"51.050540367377906"); // Latitude des Startpunktes
define( "FOC_HIGH",			"12"); // Hoehe des Startpunktes

// map23
define( "map24KEY",			"FJXb003a733e79833dcc43300acec3a1X13");
?>
