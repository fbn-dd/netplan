<?php
require_once(dirname(__FILE__) . "/../include/db.php");
/* füge statische Konfiguration ein */
require_once(dirname(__FILE__) . "/config");

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

$interface = DB_DataObject::factory('interface');
$interface->find();
while ($interface->fetch()) {
  if ($interface->ip != '')
    $ipMapping[$interface->id_node] = $interface->ip;
}

$node = DB_DataObject::factory('node');
$node->find();
while ($node->fetch()) {
  $nodeMapping[$node->id_node] = $node->description;
  $sections[$locationMapping[$node->id_location]['id_section']]
           [$node->id_location]
           [$node->id_node] = $ipMapping[$node->id_node];
}

uksort($sections, "sort_section");
while (list($id_section, $locations) = each($sections)) { 
  $section = $sectionMapping[$id_section];
?>
+ id_section_<?php echo $id_section."\n"; ?>
probe = FPing
menu = <?php echo $section."\n"; ?>
title = <?php echo $section."\n"; ?>
remark = W&auml;hle links im Men&uuml; einen Standort aus.

<?php
  uksort($locations, "sort_location");
  while (list($id_location, $nodes) = each($locations)) { 
    $location = $locationMapping[$id_location]['description'];
?>
++ id_location_<?php echo $id_location."\n"; ?>
menu = <?php echo $location."\n"; ?>
title = <?php echo $location."\n"; ?>
remark = W&auml;hle links im Men&uuml; oder unten in der &Uuml;bersicht ein Ger&auml;t aus um einen anderen Zeitbereich auszuw&auml;hlen.<br /> \
         zur <a href="/netplan/stats/index.php?standort=<?php echo $location; ?>">Trafficstatistik des Standortes <?php echo $location; ?></a> 

<?php
    uksort($nodes, "sort_node");
    while (list($id_node, $ip) = each($nodes)) { 
      if (isset($ip)) {
        $node = $nodeMapping[$id_node];
?>
+++ id_node_<?php echo $id_node."\n"; ?>
menu = <?php echo str_replace("/", "-", $node)."\n"; ?>
title = <?php echo str_replace("/", "-", $node)."\n"; ?>
remark = Klicke auf die Grafik um selber einen anderen Zeitbereich auszuw&auml;hlen.<br /> \
         zur <a href="/netplan/stats/index.php?ap=<?php echo $node; ?>">Trafficstatistik des Ger&auml;tes <?php echo $node; ?></a> 
host = <?php echo $ip."\n\n"; ?>
<?php
      }
    }
  }
}

?>
