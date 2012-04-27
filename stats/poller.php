<?php

require_once(dirname(__FILE__) . "/../include/time_start.php");
require_once(dirname(__FILE__) . "/../include/db.php");
require_once(dirname(__FILE__) . "/../include/rrd.php");

snmp_set_quick_print(TRUE);

$oid = DB_DataObject::factory('oid');
$oid->find();
while ($oid->fetch()) {
  $oidMapping[$oid->id_oidtype][$oid->id_type][$oid->id_device] = $oid->oid;
}

$oidtype = DB_DataObject::factory('oidtype');
$oidtype->find();
while ($oidtype->fetch())
{
  $oidtypeMapping[$oidtype->ds_name] = $oidtype->toArray();
}

$node = DB_DataObject::factory('node');
$node->whereAdd('snmp_community NOT LIKE "disabled"');
$node->find();
while ($node->fetch()) {
  $nodeMapping[$node->id_node] = $node->toArray();
}

$interface = DB_DataObject::factory('interface');
$interface->find();

while ($interface->fetch()) {

  $ip = $interface->ip;
  $community =  $nodeMapping[$interface->id_node]['snmp_community'];
  $incoid = $oidMapping[1][$nodeMapping[$interface->id_node]['id_type']][$interface->id_device];
  $outoid = $oidMapping[2][$nodeMapping[$interface->id_node]['id_type']][$interface->id_device];
  $lnkoid = $oidMapping[3][$nodeMapping[$interface->id_node]['id_type']][$interface->id_device];
  $phyoid = $oidMapping[9][$nodeMapping[$interface->id_node]['id_type']][$interface->id_device];
  $rxeoid = $oidMapping[10][$nodeMapping[$interface->id_node]['id_type']][$interface->id_device];

  if (!isset($ip) || !isset($community) || $community == 'disabled' || !isset($incoid) || !isset($outoid)) continue;

  $er = error_reporting(0);

  # P2P-Slave bei Lancom
  if ($interface->id_mode == 4 && in_array($nodeMapping[$interface->id_node]['id_type'], array(2,5,9))) {
    $lnk = snmpget($ip, $community, $lnkoid,100000,3);
    $phy = snmpget($ip, $community, $phyoid,100000,3);
    $rxe = snmpget($ip, $community, $rxeoid,100000,3);
    echo "P2P Slave: ".$nodeMapping[$interface->id_node]['description']." - $ip - $lnk - $phy - $rxe\n"; 
  }
  $inc = snmpget($ip, $community, $incoid,100000,3);
  $out = snmpget($ip, $community, $outoid,100000,3);
  error_reporting($er);

  if ($inc == '' || $out == '') echo $nodeMapping[$interface->id_node]['description']." - $ip - snmp inactive\n";

  if ($interface->id_mode == 4 && in_array($nodeMapping[$interface->id_node]['id_type'], array(2,5,9)) && $lnk != '' && $phy != '') {
    $link_signal = $oidtypeMapping['link-signal'];
    $rx_phy_signal = $oidtypeMapping['rx-phy-signal'];

    rrd_update_generic($link_signal['file_prefix'].'_'.$interface->id_interface.'.rrd',array($link_signal,$rx_phy_signal), array($lnk,$phy));
  }

  if ($interface->id_mode == 4 && in_array($nodeMapping[$interface->id_node]['id_type'], array(2,5,9)) && $rxe != '') {
    $rx_errors = $oidtypeMapping['rx-errors'];

    rrd_update_generic($rx_errors['file_prefix'].'_'.$interface->id_interface.'.rrd',array($rx_errors),array($rxe));
  }

  if ($inc != '' && $out != '') {
    $traffic_in = $oidtypeMapping['traffic_in'];
    $traffic_out = $oidtypeMapping['traffic_out'];

    rrd_update_generic($traffic_in['file_prefix'].'_'.$interface->id_interface.'.rrd',array($traffic_in,$traffic_out),array($inc,$out));
  }

}

require(dirname(__FILE__) . "/../include/time_end.php");

?>
