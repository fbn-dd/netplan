<?php
require_once(dirname(__FILE__) . '/db.php');

function getInterfaceBySNMP($interface_array) {
/*  snmp_set_quick_print(TRUE);

  $node = DB_DataObject::factory('node');
  $node->get($interface_array['id_node']);
  $node_array = array();
  $node_array = $node->toArray();

  $oid = DB_DataObject::factory('oid');
  $oid->id_type = $node_array['id_type'];
  $oid->id_device = $interface_array['id_device'];
  $oid->find();

  $oidMapping = array();
  while( $oid->fetch() ) {
    $oidMapping[$oid->id_oidtype] = $oid->oid;
  }

  if ( !empty($oidMapping) ) {
    $ip = $interface_array['ip'];
    $community = $node_array['snmp_community'];
    $channel_oid = $oidMapping[5]; // Kanal
    $medium_oid = $oidMapping[8]; // Frequenzband

    $channel_data = snmpget($ip, $community, $channel_oid,100000,3);
    $medium_data = snmpget($ip, $community, $medium_oid,100000,3);

    echo "Channel: ".$channel_data;
    echo "Medium: ".$medium_data;

    if ($channel_data != '' AND $medium_data != '' ) {
      $channel = DB_DataObject::factory('channel');
      $channel->description = $channel_data;
      if ( $medium_data == 'e2-4ghz' ) {
        $channel->id_medium = 3; // 802.11b
      } else {
        $channel->id_medium = 2; // 802.11a
      }
      $channel->find();
      $channel_array = array();
      $channel_array = $channel->toArray();
      $interface_array['id_channel'] = $channel_array['id_channel'];
    }
  }
  print_r($interface_array); */
  return $interface_array;
}

function getSerialBySNMP($node=NULL)
{
  if (!$node)
  {
    return '';
  }

  $if = DB_DataObject::factory('interface');
  $if->id_node = $node->id_node;
  $if->find(TRUE);
  while($if->fetch())
  {
    $serial = snmpget($if->ip, $node->snmp_community, '.1.3.6.1.2.1.1.1.0', '10');
    $serial = explode('/', $serial);
    $serial = explode(' ', $serial[1]);
    $serial = $serial[2];
    if (!is_string($serial))
    {
      continue;
    }

  }
  return $serial;
}

function getIftable($interface, $node)
{
  $iftable = '';

  if (!$interface || !$node) return $iftable;

  $device = DB_DataObject::factory('device');
  $device->get($interface->id_device);

  snmp_set_quick_print(1);
  $idlist = snmpwalk($interface->ip, $node->snmp_community, 'ifIndex', 1000000);
  $descrlist = snmpwalk($interface->ip, $node->snmp_community, 'ifDescr', 1000000);
  $maclist = snmpwalk($interface->ip, $node->snmp_community, 'ifPhysAddress', 1000000);

  if (!is_array($idlist) || !is_array($descrlist) || !is_array($maclist)) return 'SNMP-Abfrage fehlgeschlagen.';

  $iftable = '<form id="editIftable" method="post" action="/netplan/ajax/server.php" onsubmit="remoteedit.onupdate(\'iftable\', HTML_AJAX.formEncode(\'editIftable\',true)); return false;" name="editIftable"><table><caption>Tabelle der Netzwerkschnittstellen für '.$node->description.'-'.$device->description.'</caption>';
  $iftable .= '<tr><th><input type="hidden" name="id_interface" value="'.$interface->id_interface.'"></th><th>Id</th><th>Beschreibung</th><th>MAC-Adresse</th></tr>';

  $checker = false;
  if ($interface->oid_ifid) $checker = true;

  if (!$checker)
  {
    $device = $interface->getLink('id_device', 'device');
  }

  foreach ($maclist as $id => $mac)
  {
    $iftable .= '<tr><td><input type="radio" id="id-'.$id.'" name="oid_ifid" value="'.$idlist[$id].'" ';

    if (($checker && $interface->oid_ifid == $idlist[$id]) || (!$checker && $device->description == $descrlist[$id])) $iftable .= 'checked="checked"';
    
    $iftable .= '></td><td><label for="id-'.$id.'">'.$idlist[$id].'</label></td><td><label for="id-'.$id.'">'.htmlspecialchars($descrlist[$id]).'</label></td><td><label for="id-'.$id.'">'.$mac.'</label></td></tr>';
  } 

  $iftable .= '</table><input type="submit" value="Speichern"></form>'; 
  return $iftable;
}
?>
