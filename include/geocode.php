<?php

require_once 'HTTP/Client.php';
require_once (dirname(__FILE__) . "/constants.inc.php");

/*
 * Beispiel:
 * print_r(getLatLng('Heinrich-Heine-Str.7,01445,Radebeul');
 * return: array(longitude,latitude,altitude)
 */
function getCoordinates($address) {

  $output = array();
  $url = 'http://maps.google.com/maps/geo';
  $data = array( 'q'      => $address,
                 'output' => 'xml',
                 'key'    => KEY );

  $c = new HTTP_Client();
  $rc = $c->get ($url, $data);

  if ($rc == 200) {
    $r = $c->currentResponse();
    $parser = xml_parser_create();
    xml_parse_into_struct($parser, $r['body'], $vals, $index);
    xml_parser_free($parser);
    if ($vals[$index['CODE'][0]]['value'] == 200) {
      $coords = explode(',',$vals[$index['COORDINATES'][0]]['value']);
    }
  }
  return $coords;
}

/*
 * Berechnen der Entfernung zweier Koordinaten array(longitude,latitude,altitude)
 * return: Entfernung in Meter
 */
function getDistance($src, $dst) {
  $lon1 = $src[0];
  $lat1 = $src[1];
  $lon2 = $dst[0];
  $lat2 = $dst[1];
  return (6378137*3.141592653589793*sqrt(($lat2-$lat1)*($lat2-$lat1) + cos($lat2/57.29578)*cos($lat1/57.29578)*($lon2-$lon1)*($lon2-$lon1))/180);
}

?>
