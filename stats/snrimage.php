<?php

require_once(dirname(__FILE__) . "/../include/db.php");
require_once(dirname(__FILE__) . "/../include/rrd.php");

if (!isset($_GET["id_node"]) || !isset($_GET["mac"])) exit(1);

$id_node = $_GET["id_node"];
$mac = $_GET["mac"];

$node = DB_DataObject::factory('node');
$node->get($id_node);

$description = str_replace('/','-',$node->description).' - SNR - '.$mac;
$plainmac = str_replace('-','',$mac);

if(isset($_COOKIE["range"]) && is_numeric($_COOKIE["range"])) {
  $range = $_COOKIE["range"];
} else {
  $range = 86400;
}
$cachestamp = floor(time() / floor($range/60)) * floor($range/60);

rrd_graph_snr('snr_'.$id_node.'_'.$plainmac.'.rrd',
                  'snr_'.$id_node.'_'.$plainmac.'-'.$range.'.png',
                  $range,
                  $description);

echo '<img src="png/snr_'.$id_node.'_'.$plainmac.'-'.$range.'.png?'.$cachestamp.'" />';
//header('Content-type: image/png');
//readfile(PNG_DIR . '/snr_'.$id_node.'_'.$plainmac.'.png');
?>
