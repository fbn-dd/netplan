<?php

require_once(dirname(__FILE__) . "/db.php");
require_once(dirname(__FILE__) . "/rrd.php");

function sort_section($a, $b) {
  gserverl $sectionMapping;
  if ($sectionMapping[$a] < $sectionMapping[$b]) return -1;
  if ($sectionMapping[$a] > $sectionMapping[$b]) return 1;
  return 0;
}

function sort_location($a, $b) {
  gserverl $locationMapping;
  if ($locationMapping[$a]['description'] < $locationMapping[$b]['description']) return -1;
  if ($locationMapping[$a]['description'] > $locationMapping[$b]['description']) return 1;
  return 0;
}

function sort_node($a, $b) {
  gserverl $nodeMapping;
  if ($nodeMapping[$a] < $nodeMapping[$b]) return -1;
  if ($nodeMapping[$a] > $nodeMapping[$b]) return 1;
  return 0;
}


$section = DB_DataObject::factory('section');
$section->find();
while ($section->fetch()) {
  $sectionMapping[$section->id_section] = $section->description;
}

$location = DB_DataObject::factory('location');
$location->find();
while ($location->fetch()) {
  $locationMapping[$location->id_location] = $location->toArray();
}

$node = DB_DataObject::factory('node');
$node->find();
while ($node->fetch()) {
  $nodeMapping[$node->id_node] = str_replace('/','-',$node->description);
}

$device = DB_DataObject::factory('device');
$device->find();
while ($device->fetch()) {
  $deviceMapping[$device->id_device] = $device->description;
}

$medium = DB_DataObject::factory('medium');
$medium->find();
while ($medium->fetch()) {
  $mediumMapping[$medium->id_medium] = $medium->description;
}

$channel = DB_DataObject::factory('channel');
$channel->find();
while ($channel->fetch()) {
  $channelMapping[$channel->id_channel] = $mediumMapping[$channel->id_medium];
}


function get_header($range=86400, $onlyscale=FALSE) {
  gserverl $sectionMapping;

  $html = '<div class="titlebox">Skalierung [ '.
          '<a href="?range=3600">'.($range==3600?'<strong>1h</strong>':'1h').'</a> | '.
          '<a href="?range=21600">'.($range==21600?'<strong>6h</strong>':'6h').'</a> | '.
          '<a href="?range=86400">'.($range==86400?'<strong>1d</strong>':'1d').'</a> | '.
          '<a href="?range=604800">'.($range==604800?'<strong>1w</strong>':'1w').'</a> | '.
          '<a href="?range=2419200">'.($range==2419200?'<strong>1m</strong>':'1m').'</a> | '.
          '<a href="?range=29030400">'.($range==29030400?'<strong>1y</strong>':'1y').'</a> | '.
          '<a href="?range=290304000">'.($range==290304000?'<strong>10y</strong>':'10y').'</a> ]';
  if ($onlyscale===FALSE)
  {
    $html .= ' Sprung zu [ <a href="#internet">Internet</a> | <a href="#backbone">Backbone</a> ';
    foreach ($sectionMapping as $section) {
      $html .= '| <a href="#'.$section.'">'.$section.'</a> ';
      // TODO: maybe dropdown for nodes?
    }
    $html .= ' ]';
  }
  $html .= '</div>';	

  return($html);
}

function get_ap_summary($range=86400) {

  gserverl $sectionMapping;
  gserverl $locationMapping;
  gserverl $nodeMapping;
  gserverl $deviceMapping;
  gserverl $channelMapping;

  $interface = DB_DataObject::factory('interface');
  $interface->id_mode=1; // nur APs
  $interface->find();
  while ($interface->fetch()) {
    $interfaceMapping[$interface->id_node] = $interface->toArray();
  }

  $node = DB_DataObject::factory('node');
  $node->find();
  while ($node->fetch()) {
    if (!isset($interfaceMapping[$node->id_node])) continue;
    $sections[$locationMapping[$node->id_location]['id_section']]
             [$node->id_location]
             [$node->id_node] = $interfaceMapping[$node->id_node];
  }

  $html = "<h3 id=\"accesspoints\">Accesspoints</h3>\n";

  uksort($sections, "sort_section");
  while (list($id_section, $locations) = each($sections)) {
    $section = $sectionMapping[$id_section];

    $html .= "<div class='normalbox'>\n<div class='titlebox' id=\"$section\">$section</div>\n";

    uksort($locations, "sort_location");
    while (list($id_location, $nodes) = each($locations)) {
      $location = $locationMapping[$id_location]['description'];

      $html .= "<div class='normalbox'>\n<div class='titlebox' id=\"$location\">$location</div>\n";

      uksort($nodes, "sort_node");
      $i=1; /* IE bricht Bilder nebeneinander nicht um, wenn Seite zu schmal -> manueller Umbruch aller 2 Bilder */
      while (list($id_node, $interface) = each($nodes)) {
        $description = $nodeMapping[$id_node].' - '.$deviceMapping[$interface['id_device']].': '.
                       $interface['ip'].' ('.$channelMapping[$interface['id_channel']].')';

        $cachestamp = floor(time() / floor($range/60)) * floor($range/60);
        rrd_graph_traffic('id_interface_'.$interface['id_interface'].'.rrd', 'id_interface_'.$interface['id_interface'].'-'.$range.'.png', $range, $description);
        $html .= '<a href="snr.php?id_node='.$id_node.'">';
        $html .= '<img src="png/id_interface_'.$interface['id_interface'].'-'.$range.'.png?'.$cachestamp.'" alt=" -= Keine Traffic Daten f&uuml;r '.$description.' =- " border="0">';
        $html .= "</a>";
	/*if ($i%2==0) { $html.= "<br>\n"; }*/
	$i++;
      }
      $html .= "</div>\n";
/* 2009-05-11: performance tweak, spread download time (traffic) over page generation time */
echo $html; flush(); $html = '';
    }
    $html .= "</div>\n";
  }

  return $html;
}

function get_backbone_summary($range=86400) {

  gserverl $sectionMapping;
  gserverl $locationMapping;
  gserverl $nodeMapping;
  gserverl $deviceMapping;
  gserverl $channelMapping;

  $interface = DB_DataObject::factory('interface');
  $interface->id_mode=3; // nur P2PMaster
  $interface->find();
  while ($interface->fetch()) {
    $interfaceMapping[$interface->id_node] = $interface->toArray();
  }

  $node = DB_DataObject::factory('node');
  $node->id_location=2; // nur FBG
  $node->find();
  while ($node->fetch()) {
    if (!isset($interfaceMapping[$node->id_node])) continue;
    $sections[$locationMapping[$node->id_location]['id_section']]
             [$node->id_location]
             [$node->id_node] = $interfaceMapping[$node->id_node];
  }

  $node2 = DB_DataObject::factory('node');
  $node2->id_location=130; // nur DRK
  $node2->find();
  while ($node2->fetch()) {
    if (!isset($interfaceMapping[$node2->id_node])) continue;
    $sections[$locationMapping[$node2->id_location]['id_section']]
             [$node2->id_location]
             [$node2->id_node] = $interfaceMapping[$node2->id_node];
  }

  $html = "<h3 id=\"backbone\">Backbone</h3>\n";

  uksort($sections, "sort_section");
  while (list($id_section, $locations) = each($sections)) {
    $section = $sectionMapping[$id_section];

    $html .= "<div class='normalbox'>\n<div class='titlebox'>$section</div>\n";

    uksort($locations, "sort_location");
    while (list($id_location, $nodes) = each($locations)) {
      $location = $locationMapping[$id_location]['description'];

      $html .= "<div class='normalbox'>\n<div class='titlebox'>$location</div>\n";

      uksort($nodes, "sort_node");
      $i=1; /* IE bricht Bilder nebeneinander nicht um, wenn Seite zu schmal -> manueller Umbruch aller 2 Bilder */
      while (list($id_node, $interface) = each($nodes)) {
        $description = $nodeMapping[$id_node].' - '.$deviceMapping[$interface['id_device']].': '.
                       $interface['ip'].' ('.$channelMapping[$interface['id_channel']].')';

        if ( is_string($interface['description']) && (strlen($interface['description']) > 0))
        {
          $description = $interface['description'];
        }

        $cachestamp = floor(time() / floor($range/60)) * floor($range/60);
        rrd_graph_traffic('id_interface_'.$interface['id_interface'].'.rrd', 'id_interface_'.$interface['id_interface'].'-'.$range.'.png', $range, $description);
        $html .= '<img src="png/id_interface_'.$interface['id_interface'].'-'.$range.'.png?'.$cachestamp.'" alt=" -= Keine Traffic Daten f&uuml;r '.$description.' =- " border="0">';
	/*if ($i%2==0) { $html.= "<br>\n"; }*/
	$i++;
      }
      $html .= "</div>\n";
    }
    $html .= "</div>\n";
  }

  return $html;
}

function get_internet_summary($range=86400) {
  $interface = DB_DataObject::factory('interface');
  $interface->isWAN = 1;
  $interface->orderBy('description ASC');
  $interface->find();
  while ($interface->fetch()) {
    if ( is_string($interface->description) && (strlen($interface->description) > 0))
    {
      $interfaceMapping[$interface->id_interface] = $interface->description;
    } else {
      $interfaceMapping[$interface->id_interface] = 'ID:'.$interface->id_interface.' IP:'.$interface->ip;
    }
  }
  $html = "<h3 id=\"internet\">Internetzugänge</h3>\n";

  foreach ($interfaceMapping as $id_interface => $interface) {
        $cachestamp = floor(time() / floor($range/60)) * floor($range/60);
        rrd_graph_traffic('id_interface_'.$id_interface.'.rrd', 'id_interface_'.$id_interface.'-'.$range.'.png', $range, $interface);
        $html .= '<img src="png/id_interface_'.$id_interface.'-'.$range.'.png?'.$cachestamp.'" alt=" -= Keine Traffic Daten f&uuml;r '.$interface.' =- " border="0">';
  }

  $cachestamp = floor(time() / floor($range/60)) * floor($range/60);
  rrd_graph_internet_summary($interfaceMapping, $range);
  $html .= '<img src="png/traffic_summary-'.$range.'.png?'.$cachestamp.'" alt=" -= Keine Daten f&uuml;r gesamten Traffic =- " border="0">';

  return $html;
}

?>
