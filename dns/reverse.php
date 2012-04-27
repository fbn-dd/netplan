<?php

require_once(dirname(__FILE__) . "/../include/db.php");

$ip = false;
if (isset($_GET["ip"])) {
  $ip = intval($_GET["ip"]);
}

switch ($ip) {
  case 10:  $ip = '10.';      break;
  case 192: $ip = '192.168.'; break;
  /* failsafe */
  default:  $ip = '192.168.';
}



$node = DB_DataObject::factory('node');
$node->find();
$nodeMapping = array();
while ($node->fetch()) {
  $nodeMapping[$node->id_node] = str_replace(array("/", "_"),"-",$node->description);
  $nodeMapping[$node->id_node] = str_replace(" ","-",$nodeMapping[$node->id_node]);
  $nodeMapping[$node->id_node] = strtolower($nodeMapping[$node->id_node]);
}

$interface = DB_DataObject::factory('interface');
$interface->whereAdd('ip != "0.0.0.0"');
$interface->whereAdd('ip != "127.0.0.1"');
$interface->whereAdd('ip != "255.255.255.255"');
$interface->whereAdd('ip != ""');
$interface->whereAdd('ip LIKE "'.$ip.'%"');
$interface->groupBy('ip');
$interface->find();

$hosts = array();

while ($interface->fetch()) {
  $ip = array();

  if (empty($interface->ip)) continue;
  $ip = explode('.', $interface->ip);

  $hosts[$ip[0]][$ip[1]][$ip[2]][$ip[3]]['description'] = $nodeMapping[$interface->id_node];
  $hosts[$ip[0]][$ip[1]][$ip[2]][$ip[3]]['id_mode'] = $interface->id_mode;
}

foreach ($hosts as $blocka => $resta) {
  foreach ($resta as $blockb => $restb) {
    foreach ($restb as $blockc => $restd) {
/* Generate weggelassen, damit kein roundrobin bei der reverse aufloesung gemacht wird 
      echo '$GENERATE 1-255 $.'.$blockc.'.'.$blockb.' PTR '.$blocka.'-'.$blockb.'-'.$blockc.'-$.example.org.'."\n";
*/
      foreach ($restd as $blockd => $restd) {
        switch($restd['id_mode']) {
          /* Interface ist WLAN-AP*/
          case 1: $sub = '.ap'; break;
          /* alle anderen Interfaces */
          default: $sub = '.bb';
        }
        echo $blockd.'.'.$blockc.'.'.$blockb.(strlen($blockd.'.'.$blockc.'.'.$blockb)<8?"\t\t":"\t")."IN\tPTR\t".$restd['description'].$sub.'.example.org.'."\n";
      }
    }
  }
}
?>
