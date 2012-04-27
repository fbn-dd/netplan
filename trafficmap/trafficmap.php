<?php
require_once(dirname(__FILE__) . "/../include/db.php");

$rrd = 'gauge:/opt/netplan/trunk/stats/rrd/';
$olgraph = '/netplan/stats/rrdimage.php?id_interface=';

$options = getopt("m::");

if(isset($options['m']) and $options['m'] > 0) {
  $section = DB_DataObject::factory('section');
  $section->get($options['m']);

  $sectionMapping = array();
  $sectionMapping[$section->id_section] = $section->toArray();
  $filename = preg_replace( "/[^\w_-]/", "-", strtolower( $section->description ) );
?>
TITLE Traffic-Map <?php echo $section->description."\n"; ?>

#HTMLOUTPUTFILE traffic-<?php echo $filename; ?>.htm
#IMAGEOUTPUTFILE traffic-<?php echo $filename; ?>.png
#IMAGEURI traffic-<?php echo $filename; ?>.png

HEIGHT <?php echo $section->tm_height."\n"; ?>
WIDTH <?php echo $section->tm_width."\n"; ?>

KEYPOS  DEFAULT <?php echo ($section->tm_width)-160; ?> 40 Verbindungsauslastung
<?php
  $location = DB_DataObject::factory('location');
  $location->find();

  $locationMapping = array();
  while($location->fetch()) {
    if(array_key_exists($location->id_section, $sectionMapping)) {
      $locationMapping[$location->id_location] = $location->toArray();
    }
  }
// wenn keine Section uebergeben wurde, alles einfuegen
} else {
?>
TITLE Traffic-Map FBN-DD

HEIGHT 1000
WIDTH 900

KEYPOS  DEFAULT 0 0 Auslastung in Byte/s (mit 8 multiplizieren fuer Bit/s)
TIMEPOS 0 0 Erstellt: %d.%m.%Y %H:%M:%S
<?php } ?>

HTMLSTYLE overlib
HTMLSTYLESHEET /netplan/css/gserverl.css
KILO 1000

KEYFONT 3

SCALE DEFAULT  0  0  120 120 120
SCALE DEFAULT  0 20    0   0 255   0 255 255
SCALE DEFAULT 20 40    0 255 255   0 255   0
SCALE DEFAULT 40 70    0 255   0 255 255   0
SCALE DEFAULT 70 100 255 255   0 255   0   0

LINK DEFAULT
        BANDWIDTH 12500K
        BWLABEL bits
        OVERLIBWIDTH 395
        OVERLIBHEIGHT 153
        WIDTH 3
        OVERLIBCAPTION Datenverkehr fuer {link:this:name} 

NODE Uebersicht
        POSITION <?php echo ($section->tm_width)-82; ?> 25
        LABEL zurueck zur Uebersicht
        INFOURL index.html
<?php
$node = DB_DataObject::factory('node');
$node->whereAdd('x_coord != 0');
$node->whereAdd('y_coord != 0');
//$node->whereAdd(description != 'FBN-DD-Loadbalancer');
$node->find();
$nodeMapping = array();
if (isset($options['m']) AND $options['m'] > 0) {
  while($node->fetch()) {
    if (array_key_exists($node->id_location, $locationMapping)) {
      if ( $node->x_coord > 0  AND $node->y_coord > 0 ) {
        $nodeMapping[$node->id_node] = $node->toArray();
      }
    }
  }
} else {
  while($node->fetch()) {
    if ( $node->x_coord > 0  AND $node->y_coord > 0 ) {
        $nodeMapping[$node->id_node] = $node->toArray();
    }
  }
}

while(list($id_node, $nodes) = each($nodeMapping)) {
?>
NODE <?php echo $id_node; ?>

  POSITION <?php echo $nodes['x_coord'].' '.$nodes['y_coord']; ?>

  LABEL <?php echo str_replace('/','-',$nodes['description']); ?>


<?php
}

$channel = DB_DataObject::factory('channel');
$channel->find();
$channelMapping = array();
while($channel->fetch()) {
  $channelMapping[$channel->id_channel] = $channel->id_medium;
}

$interface = DB_DataObject::factory('interface');
$interface->find();
$interfaceMapping = array();
while($interface->fetch()) {
  $interfaceMapping[$interface->id_interface] = $interface->toArray();
}

$link = DB_DataObject::factory('link');
$link->find();
$linkMapping = array();
while($link->fetch()) {
  if (array_key_exists($interfaceMapping[$link->id_src_interface]['id_node'], $nodeMapping) AND array_key_exists($interfaceMapping[$link->id_dst_interface]['id_node'], $nodeMapping)) {
    $linkMapping[$link->id_link] = $link->toArray();
  }
}

while(list($id_link, $links) = each($linkMapping)) {
?>
LINK <?php echo str_replace('/','-',$nodeMapping[$interfaceMapping[$links['id_src_interface']]['id_node']]['description']) ?>-><?php echo str_replace('/','-',$nodeMapping[$interfaceMapping[$links['id_dst_interface']]['id_node']]['description']) ?>

  NODES <?php echo $interfaceMapping[$links['id_dst_interface']]['id_node'] ?> <?php echo $interfaceMapping[$links['id_src_interface']]['id_node'] ?>

  TARGET <?php echo $rrd."id_interface_".$links['id_dst_interface'].".rrd" ?>

  OVERLIBGRAPH <?php echo $olgraph.$links['id_dst_interface'] ?>

  BANDWIDTH <?php  switch ($channelMapping[$interfaceMapping[$links['id_dst_interface']]['id_channel']]) {
                  case 1: $bw = "12500K"; break; // LAN
                  case 2: $bw = "3M"; break; // 802.11a
                  case 3: $bw = "750K"; break; // 802.11b
                  case 4: $bw = "750K"; break; // 802.11g
                  case 5: $bw = "750K"; break; // TC
                }
                echo $bw ?>


<?php
}
?>
