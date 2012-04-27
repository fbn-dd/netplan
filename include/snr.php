<?php
/* SNR-Statistik für alle sonstigen Geräte */
require_once(dirname(__FILE__) . "/db.php");

function clean($data, $type, $node_id)
{
  $lancom = array(2,5,9,24);

  # Translate Rules
  $rates = array('unknown',
                 '1 Mbps','2 Mbps','5 Mbps','5.5 Mbps','8 Mbps','11 Mbps',
                 'unknown',
                 '6 Mbps','9 Mbps','12 Mbps','18 Mbps','24 Mbps','36 Mbps','48 Mbps','54 Mbps',
                 'unknown','unknown','unknown','unknown',
                 '12 Mbps','18 Mbps','24 Mbps', '36 Mbps','48 Mbps','72 Mbps','96 Mbps','108 Mbps');

  # WPA Versions
  $wpavers = array('none', 'WPA', 'WPA2');

  # Keytypes
  $keytypes = array(0 => 'none', 1 => 'unknown', 5 => 'WEP-40', 13 => 'WEP-104',
                    16 => 'WEP-128', 64 => 'TKIP', 65 => 'AES-OCB', 66 => 'AES-CCM');

  # Interfaces
  $interfaces = array('wlan-0', 'wlan-1', 'wlan-2');

  # Networks
  $networks = array('e1','e2', 'e3', 'e4', 'e5', 'e6', 'e7', 'e8');

  # State
  $states = array(0 => 'none', 1 => 'adhoc', 2=> 'authenticated', 3 => 'connected', 4 => 'mac-check',
                  9 => 'associated', 10 => 'e1x-negotiation', 8 => 'key-handshake');

  $translate["rxrate"] = $rates;
  $translate["txrate"] = $rates;
  $translate["interface"] = $interfaces;
  $translate["network"] = $networks;
  $translate["status"] = $states;
  $translate["wpa"] = $wpavers;
  $translate["key"] = $keytypes;
   
  $data = ereg_replace('"','',$data);
  $data = ereg_replace('Hex-STRING: ','',$data);
  $data = ereg_replace('STRING: ','',$data);
  $data = ereg_replace('INTEGER: ','',$data);

  switch($type)
  {
    case "mac":
      $data = ereg_replace(' ','',$data);
      $data = strtolower($data);
      $data = substr($data,0,6).'-'.substr($data,6,6);
      break;
    case "snr":
      $data = ereg_replace('Gauge32: ','',$data);
      $data = ereg_replace('Integer: ','',$data);
      if (in_array($node_id, $lancom))
      {
        // Prozent zu DB (http://www.lancom-forum.de/lhtopic,384,0,0,asc,25fa0ed0155eaee63270009a9dbefbc0.html)
        $data = round($data * 0.64);
      }
      break;
    case "ssid":
      $data = str_replace('www.example.org/', '', $data);
      $data = str_replace('www.example.org_', '', $data);
      $data = str_replace('www.example.org ', '', $data);
      $data = str_replace('www.fbn-ftl.de_', '', $data);
      $data = str_replace('www.fbn-ftl.de/', '', $data);
      break;
    case "noise":
      $data = str_replace(' dBm', '', $data);
      break;
  }

  if (in_array($node_id, $lancom) && isset($translate[$type]))
  {
    $data = $translate[$type][$data];
  }
  return $data;
}

function getSNRTable($ip, $snmp_community, $timeout, $mode, $oidMapping)
{
  snmp_set_quick_print(TRUE);
  snmp_set_oid_numeric_print(TRUE);
  snmp_set_enum_print(TRUE);

  $retval = array();
  return $retval;
}

function printSNRTable($id_node, $admin = FALSE)
{
  snmp_set_quick_print(TRUE);
  snmp_set_oid_numeric_print(TRUE);
  snmp_set_enum_print(TRUE);

  $er = error_reporting(0);


  $timeout = '12000';
  $retries = '5';

  $html = '<div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>'."\n";

  $interface = DB_DataObject::factory('interface');
  $interface->id_node = $id_node;
  $interface->whereAdd('id_mode in (1,3)');
  $interface->whereAdd('ip != ""');
  $interface->whereAdd('ip != "0.0.0.0"');
  $interface->find();

  // sicherstellen, dass wir ein ap interface nehmen, wenn eins vorhanden ist
  while($interface->fetch())
  {
    if ($interface->id_mode == 1) break;
  }

  if ($interface->ip) {
    $ip = $interface->ip;
  } else {
    $node = DB_DataObject::factory('node');
    $node->get($interface->id_node);
    $location = DB_DataObject::factory('location');
    $location->get($node->id_location);
    $html .= '<p>Eine SNR-Anzeige erhalten Sie nur f&uuml;r APs.</p>';
    $html .= '<p>Zur <a href="/cgi-bin/smokeping.cgi?target=id_section_'.$location->id_section.'.id_location_'.$location->id_location.'.id_node_'.$node->id_node.'">Pingstatistik des Ger&auml;tes '.$node->description.'</a></p>';
    return $html;
  }

  $node = $interface->getLink('id_node', 'node');
  $snmp_pass = $node->snmp_community;

  if ($snmp_pass != 'disabled')
  {
    $sysdesc = snmpget($ip, $snmp_pass, ".1.3.6.1.2.1.1.1.0");
  }

  if(!isset($sysdesc))
  {
    return('SNMP Abfrage fehlgeschlagen.(sys)');
  }

  $oid = DB_DataObject::factory('oid');
  $oid->id_type = $node->id_type;
  $oid->id_device = $interface->id_device;
  $oid->find();
  $oidMapping = array();
  while ($oid->fetch()) {
    $oidMapping[$oid->id_oidtype] = $oid->oid;
  }

  if (!isset($oidMapping))
  {
    return('Keine OIDs f&uuml;r dieses Ger&auml;t gefunden.');
  }

  $isAP = $interface->id_mode == 1;
  $isP2PMaster = $interface->id_mode == 3;

  $channeloid = $oidMapping[5];
  $macoid     = ($isAP ? $oidMapping[11] : $oidMapping[31]);
  $snroid     = ($isAP ? $oidMapping[12] : $oidMapping[32]);
  $intoid     = $oidMapping[13];
  $rxrateoid  = ($isAP ? $oidMapping[14] : $oidMapping[34]);
  $txrateoid  = ($isAP ? $oidMapping[15] : $oidMapping[33]);
  $trafficoid = $oidMapping[16];
  $vlanoid    = $oidMapping[17];
  $networkoid = $oidMapping[18];
  $statusoid  = $oidMapping[19];
  $wpaoid     = $oidMapping[20];
  $keyoid     = $oidMapping[21];
  $ssidoid    = $oidMapping[22];
  $moduleoid  = $oidMapping[24];
  $firmwareoid= $oidMapping[25];
  $ifcoid     = $oidMapping[26];
  $eirpoid    = $oidMapping[27];
  $p2pnameoid = $oidMapping[30];

// return var_export($oidMapping,1);

  if (!isset($macoid) || !isset($snroid))
  {
    return('Keine MAC OID und SNR OID f&uuml;r das Ger&auml;t definiert.');
  }

  $macList = snmpwalk($ip, $snmp_pass, $macoid);

  if ($isP2PMaster)
    $p2pnameList = snmpwalk($ip, $snmp_pass, $p2pnameoid);

  if (isset($macList)) {
    $snrList = snmpwalk($ip, $snmp_pass, $snroid);

    if (isset($intoid))     $interfaceList= snmpwalk($ip, $snmp_pass, $intoid);
    if (isset($rxrateoid))            $rxrateList   = snmpwalk($ip, $snmp_pass, $rxrateoid);
    if (isset($txrateoid))            $txrateList   = snmpwalk($ip, $snmp_pass, $txrateoid);
    if (isset($trafficoid) && $admin) $trafficList  = snmpwalk($ip, $snmp_pass, $trafficoid);
    if (isset($vlanoid) && $admin)    $vlanList     = snmpwalk($ip, $snmp_pass, $vlanoid);
    if (isset($networkoid) && is_array($interfaceList)) $networkList  = snmpwalk($ip, $snmp_pass, $networkoid);
    if (isset($statusoid) && $admin)  $statusList   = snmpwalk($ip, $snmp_pass, $statusoid);
    if (isset($wpaoid))               $wpaList      = snmpwalk($ip, $snmp_pass, $wpaoid);
    if (isset($keyoid) && $admin)     $keyList      = snmpwalk($ip, $snmp_pass, $keyoid);
    if (isset($ssidoid) && is_array($networkList)) $ssidList = snmpwalk($ip, $snmp_pass, $ssidoid);
    if (isset($channeloid))           $channelList  = snmpwalk($ip, $snmp_pass, $channeloid);
    if (isset($ifcoid))               $ifcList      = snmpwalk($ip, $snmp_pass, $ifcoid);
    if (isset($moduleoid)) $moduleList   = snmpwalk($ip, $snmp_pass, $moduleoid);
    if (isset($firmwareoid) && is_array($moduleList)) $firmwareList = snmpwalk($ip, $snmp_pass, $firmwareoid);
    if (isset($eirpoid) && is_array($moduleList)) $eirpList = snmpwalk($ip, $snmp_pass, $eirpoid);

    $sortIdList = array();
    foreach ($snrList as $key => $snr)
    {
      $sortIdList[sprintf("%03d%03d",clean($snr, "snr", $node->id_type),$key)] = $key;
    }
    krsort($sortIdList);

    if ($sysdesc)
    {
      $html .= '<h4>'.$sysdesc;
      //if (isset($channelList) && isset($noiseList))
      if (isset($channelList))
      {
        foreach ($channelList as $index => $value)
        {
          //$html .= ' WLAN-'.($index+1).': [ Channel: '.$value.' | Noise: '.clean($noiseList[$index], "noise", $node->id_type).'dB ] ';
          $html .= ' WLAN-'.($index+1).': [Channel: '.$value.'] ';
        }
      }

      $html .= '</h4>';
    }

    if($admin && is_array($moduleList) && is_array($firmwareList))
    {
      foreach ($moduleList as $index => $module)
      {
        if($ifcList)
        {
          $html .= clean($ifcList[$index], "none", $node->id_type).': ';
        }
        $html .= clean($module, "none", $node->id_type).'(Firmware: '.clean($firmwareList[$index], "none", $node->id_type).($eirpList?', EIRP: 
'.clean($eirpList[$index], "none", $node->id_type):'').')  ';
      }
      $html .= "<br /><br />";
    }

    if (count($sortIdList) == 0)
    {
      $html .= 'Keine verbundenen Clients gefunden.';
    }
    else
    {
      $html .= '<table border="1" class="sortable">'.
               '  <tr>'.
               ($ssidList?'   <th>SSID</th>':'').
               '    <th>MAC-Adresse</th>'.
               '    <th>Mitgliedsname</th>'.
               '    <th>SNR</th>'.
               ($admin?'':'    <th><img src="../images/scale_snr.gif"></th>').
               ($trafficList?'    <th>Traffic</th>':'');

      if ($txrateList)
      {
        if ($admin)
        {
          $html .= '<th>TX-Rate</th>';
        }
        else
        {
          $html .= '<th><abbr title="Sendegeschwindigkeit">Sendegeschw.</abbr> <abbr title="Accesspoint">AP</abbr></th>';
        }
      }

      if ($rxrateList)
      {
        if ($admin)
        {
          $html .= '<th>RX-Rate</th>';
        }
        else
        {
          $html .= '<th><abbr title="Sendegeschwindigkeit">Sendegeschw.</abbr> Client</th>';
        }
      }

      $html .= ($isAP ? '    <th>g&uuml;ltig</th>' : '').
               ($statusList?'    <th>Status</th>':'').
               ((($wpaList || $keyList) && $admin)?'    <th>'.($wpaList?'Enc/':'').'Key</th>':'').
               ($isAP && $admin? '    <th>per EMail</th>' : '').
               '  </tr>';
    }
  }
  else
  {
    return('Fehler beim Abfrage der MAC-Tabelle.');
  }

  $clients = $clientsSec = 0;

  foreach ($sortIdList as $key) {

    $mac       = clean($macList[$key], "mac", $node->id_type);
    $snr       = clean($snrList[$key], "snr", $node->id_type);
    $txrate    = (isset($txrateList[$key])?clean($txrateList[$key], "txrate", $node->id_type):false);
    $rxrate    = (isset($rxrateList[$key])?clean($rxrateList[$key], "rxrate", $node->id_type):false);
    $traffic   = ($trafficList[$key]?clean($trafficList[$key], "none", $node->id_type):false);
    $interface = (isset($interfaceList[$key])?clean($interfaceList[$key], "none", $node->id_type):false);
    $network   = (isset($networkList[$key])?clean($networkList[$key], "none", $node->id_type):false);
    $status    = (isset($statusList[$key])?clean($statusList[$key], "status", $node->id_type):false);
    $wpaver    = (isset($wpaList[$key])?clean($wpaList[$key], "wpa", $node->id_type):false);
    $mname     = clean($p2pnameList[$key]);
    $key       = (isset($keyList[$key])?clean($keyList[$key], "key", $node->id_type):false);

    // $ssidList ist Liste mit max 16 SSIDs (Index 0-15)
    // $interface = 0 oder 1, $network = 0-7
    // SSID fuer WLAN-1   = Index 0*8+0
    // SSID fuer WLAN-1-X = Index 0*8+X
    // SSID fuer WLAN-2   = Index 1*8+0
    // SSID fuer WLAN-2-X = Index 1*8+X
    $ssidkey   = ($interface*8)+$network;

    // wenn Lancom dual mit 7er firmware, dann andere Sortierung der ssids:
    // WLAN-2 rueckt an 2. Stelle der Liste und alles andere eins runter
    // d.h. WLAN-1, WLAN-2, WLAN-1-2, WLAN-1-X, WLAN-2-2, WLAN-2-X
    if (strpos($sysdesc, 'LANCOM L-54 dual Wireless 7.') !== FALSE) {
        $ssidmapping = array(
            // interface 1
            array (0, 2, 3, 4, 5, 6, 7, 8),
            // interface 2
            array (1, 9, 10, 11, 12, 13, 14, 15)
        );
        $ssidkey  = $ssidmapping[$interface][$network];
    }

    $ssid     = clean($ssidList[$ssidkey], "ssid", $node->id_type);

    $macprint = ($admin?$mac:'xxxxxx-xx'.substr($mac,9,4));

    if ($mac == "000000-000000") {
      $mname = "SNIFFER <i>Netstumbler, Kismet, ...</i>";
      $validto = "ung&uuml;ltig";

    } else if ($isAP) {

      $macs = DB_DataObject::factory('Macs');
      $macs->Mac = $mac;
      $macs->find(TRUE);

      if (isset($macs->BNID)) {

        $mitglieder    = $macs->getLink('BNID', 'Mitglieder', 'BNID');

        $kommunikation = DB_DataObject::factory('Kommunikation');
        $kommunikation->BNID = $macs->BNID;
        $kommunikation->TKommunikation = 'email';
        $kommunikation->find(TRUE);

        $mname = $macs->BNID == '0' ? 'FBN: ['.$macs->Bemerkung.']' : $mitglieder->Vorname.' '.$mitglieder->Nachname;

        $self = FALSE;
        if (is_string($_SERVER['PHP_AUTH_USER']) && $_SERVER['PHP_AUTH_USER'] == $mitglieder->Username) {
          $self = TRUE;
        }

        $validto = $macs->ValidTo;
        $clients++;

      } else {

        $mname = "kein FBN: ".$mac;
        $validto = "ung&uuml;ltig";
        unset($kommunikation);
      }

    }

    $limit=$snr*4;
    $color="yellow";
    if ($snr>15) $color="green";
    if ($snr<10) $color="red";
    if ($snr<1) $limit=1;
    $rx = explode(' ',$rxrate);
    $wpainfo = '';
    if ( $wpaver || $key || stristr($ssid, 'WPA') || stristr($ssid, 'EAP') )
    {
        $clientsSec++;
        $wpainfo = '<a href="https://www.example.org/EAP" title="Anleitung: Wie richte ich eine WPA2-verschl&uuml;sselte Verbindung mit EAP ein?"><img src="../images/ssl-symbol.png" alt="WPA-verschlüsselt" width="13" height="15" align="absmiddle" /></a>';
    }
    $html .= '<tr>'.
//             ($admin && $interface?'<td>'.$interface.'</td>':'').
             ($ssid?'<td>'.$wpainfo.$ssid.'</td>':'');

    if ($admin)
    {
      $mac_split = explode('-', $mac);
      $html .= '  <td><a href="http://standards.ieee.org/cgi-bin/ouisearch?'.$mac_split[0].'" '.($isAP?'onmouseover="return overlib(\'<iframe scrolling=no marginheight=0 marginwidth=0 src=./snrimage.php?id_node='.$id_node.'&mac='.$mac.' height=175 width=397>\', VAUTO, STICKY, MOUSEOFF);" onmouseout="return nd();"':'').'>'.$mac.'</td>';
    }
    else if ($self)
    {
      $html .= '  <td><a href="javascript:void(0);" onmouseover="return overlib(\'<iframe scrolling=no marginheight=0 marginwidth=0 src=./snrimage.php?id_node='.$id_node.'&mac='.$mac.' height=175 width=397>\', VAUTO, STICKY, MOUSEOFF);" onmouseout="return nd();">'.$mac.'</td>';
    }
    else
    {
      $html .= '  <td>'.$macprint.'</td>';
    }

    $html .= '  <td>'.$mname.(($admin && $macs->BNID!='')? ' ['.$macs->BNID.']' : '').'</td>';
// warum Farben nur fuer Admins? weil Mitglieder von sowas negativ beeinflusst werden und einen schoenen Balken sehen
            if ($admin) {
              if ($snr < '10') {
                $html .= '  <td align="center" bgcolor="red">'.$snr.'</td>';
              } else if ($snr < '15') {
                $html .= '  <td align="center" bgcolor="yellow">'.$snr.'</td>';
              } else {
                $html .= '  <td align="center" bgcolor="green">'.$snr.'</td>';
              }
            } else {
              $html .= '  <td align="center">'.$snr.'</td>';
            }

    $html .= ($admin?'':'  <td><img BORDER="0" src="../images/'.$color.'.png" WIDTH="'.$limit.'" HEIGHT="15"></td>').
             ($traffic?'  <td align="center">'.$traffic.'</td>':'').
             ($txrate?'  <td align="center">'.$txrate.'</td>':'').
             ($rxrate?'  <td align="center"'.($rx[0] < 11 && $rxrate!='unknown'?' bgcolor="red"':'').'>'.$rxrate.'</td>':'').
             ($isAP?'  <td>'.($validto==''?'unbegrenzt':$validto).'</td>':'').
             ($status?'<td>'.$status.'</td>':'').
             ((($wpaver || $key) && $admin)?'<td>'.($wpaver?$wpaver.'/':'').$key.'</td>':'').
             ($isAP && $admin? '  <td>'.((isset($kommunikation) && !empty($kommunikation->Value))?'<a 
href="mailto:'.$kommunikation->Value.'">anschreiben</a>':'keine E-Mail').'</td>' : '').
             "</tr>\n";
  }

  $html .= "</table>\n";

  if ( $clients > 0) {
    $html .= '<p>verbundene Clients: '.$clients.', davon '.$clientsSec.' verschl&uuml;sselt ('.sprintf('%.2f',$clientsSec/$clients*100).'%)</p>'.
             '<p>Wenn die Sendegeschwindigkeit des Clients niedriger ist, als die Sendegeschwindigkeit des AP oder ihr SNR unter 10 liegt, '.
             'deutet dies auf einen schlechten Empfang hin.  Sie sollten ihre Installation/Antenne &uuml;berpr&uuml;fen.</p>';
  }

  $location = DB_DataObject::factory('location');
  $location->get($node->id_location);
  $html .= '<p>Zur <a href="/cgi-bin/smokeping.cgi?target=id_section_'.$location->id_section.'.id_location_'.$location->id_location.'.id_node_'.$node->id_node.'">Pingstatistik des Ger&auml;tes '.$node->description.'</a></p>';

  return utf8_encode($html);
}

?>
