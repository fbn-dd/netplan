<?php
/* @TODO: code obsolete
 * Distanz clientseitig über GMaps-API berechnet,
 * Adressmarker und Links von AP zu Adressmarker in map.php gesetzt
 * siehe map.php function get_aps_near_address(address)
 */
require_once(dirname(__FILE__) . "/db.php");
require_once(dirname(__FILE__) . "/geocode.php");

class remotemap {

  function get_aps_near_point($point) {

    $src = array($point['x'],$point['y']);
    $location = DB_DataObject::factory('location');
    $location->find();
    while ($location->fetch()) {
      $distance = getDistance($src, array($location->longitude, $location->latitude));
      if ( $distance <= 2500.0 ) {
        $html[$distance] = sprintf('%s = %.0fm', $location->description, $distance);
        $addLinks .= "aplink.push(new GPolyline([point, location_".$location->id_location."],'#00FF00', 3));";
      }
    }
    ksort($html);
    $result = "var html = '".implode('<br/>',$html)."';\n".
              "var point = new GPoint(".$point['x'].",".$point['y'].");\n".
              "aplink = new Array();\n".
              $addLinks;
    return($result);
  }

}

?>
