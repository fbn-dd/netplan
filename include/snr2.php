<?php
/* SNR-Statistik für LANCOMs */
require_once(dirname(__FILE__) . "/db.php");

gserverl $DEBUG;
$DEBUG = true;

function clean($data, $type, $node_id)
{
  gserverl $isLancom;

  $lancom = array(2,5,9,24,27);

  # Translate Rules
  $rates = array('unknown',
                 '1 Mbps','2 Mbps','5 Mbps','5.5 Mbps','8 Mbps','11 Mbps',
                 'unknown',
                 '6 Mbps','9 Mbps','12 Mbps','18 Mbps','24 Mbps','36 Mbps','48 Mbps','54 Mbps',
                 'unknown','unknown','unknown','unknown',
                 '12 Mbps','18 Mbps','24 Mbps', '36 Mbps','48 Mbps','72 Mbps','96 Mbps','108 Mbps',
                 'ht-1-6-5m', 'ht-1-13m', 'ht-1-19-5m', 'ht-1-26m', 'ht-1-39m', 'ht-1-52m', 'ht-1-58-5m',
                 'ht-1-65m', 'ht-2-13m', 'ht-2-26m', 'ht-2-39m', 'ht-2-52m', 'ht-2-78m', 'ht-2-104m', 'ht-2-117m', 'ht-2-130m'
);

  # WPA Versions
  $wpavers = array('none', 'WPA', 'WPA2');

  # Keytypes
  $keytypes = array(0 => 'none', 1 => 'unknown', 5 => 'WEP-40', 13 => 'WEP-104',
                    16 => 'WEP-128', 64 => 'TKIP', 65 => 'AES-OCB', 66 => 'AES-CCM',
		    32 => 'WPA-PSK', 33 => '802.1X WPA', 34 => '802.1X WEP40',
		    35 => '802.1X WEP104', 36 => '802.1X WEP128');

  # Interfaces
  $interfaces = array('wlan-0', 'wlan-1', 'wlan-2');

  # Networks
  $networks = array('e1','e2', 'e3', 'e4', 'e5', 'e6', 'e7', 'e8');

  # State
  $states = array(0 => 'none', 1 => 'adhoc', 2=> 'authentifiziert', 3 => 'verbunden', 4 => 'mac-check',
                  9 => 'assoziiert', 10 => '802.1x-Aushandlung', 8 => 'key-handshake');

  # last event for WLAN station (client) in AP's log
  # newer LCOS versions (>8.00) show clients with "deauth" and "admin-deassoc" events in WLAN station tables in SNMP responses
  $staWlanStatioLastev = array(
     0 => 'none',
     1 => 'auth-success',
     2 => 'deauth',
     3 => 'assoc-success',
     4 => 'reassoc-success',
     5 => 'disassoc',
     6 => 'radius-success',
     7 => 'auth-reject',
     8 => 'assoc-reject',
     9 => 'keyhandshake-success',
    10 => 'keyhandshake-timeout',
    11 => 'keyhandshake-failure',
    12 => 'radius-reject',
    13 => 'supervision',
    14 => 'e802-1x-success',
    15 => 'e802-1x-failure',
    16 => 'idle-timeout',
    17 => 'admin-deassoc',
    18 => 'roamed'
  );

  // Ersetze
  $translate["rxrate"]    = $rates;
  $translate["txrate"]    = $rates;
  $translate["interface"] = $interfaces;
  $translate["network"]   = $networks;
  $translate["status"]    = $states;
  $translate["event"]     = $staWlanStatioLastev;
  $translate["wpa"]       = $wpavers;
  $translate["key"]       = $keytypes;
  // Entferne Datentyp-Informationen
  $data = ereg_replace('"','',$data);
  $data = ereg_replace('Hex-STRING: ','',$data);
  $data = ereg_replace('STRING: ','',$data);
  $data = ereg_replace('INTEGER: ','',$data);
  // Nachbehandlung
  switch($type)
  {
    case "mac":
      $data = ereg_replace(' ','',$data);
      $data = strtolower($data);
      $data = substr($data,0,6).'-'.substr($data,6,6);
      break;
    case "snr":
      $data = ereg_replace('Gauge32: ','',$data);
      if ($isLancom)
      {
        // Prozent zu dB (http://www.lancom-forum.de/lhtopic,384,0,0,asc,25fa0ed0155eaee63270009a9dbefbc0.html)
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
    case "eapUser":
      //$data = str_replace('.', ' ', $data);
      break;
    case "ipUser":
      //$data = str_replace('0.0.0.0', '', $data);
      break;
    case "none": /* ohne break, damit in default-Zweig gelaufen wird */
    default:
  }

  if ($isLancom && isset($translate[$type]))
  {
    $data = $translate[$type][$data];
  }
  return $data;
}

function getSNRTable($ip, $snmp_pass, $timeout, $retries, $id_mode, $oidMapping, $admin = FALSE, $id_type)
{
  // keine SNR-Anzeige/Client-Tabelle für P2P-Geräte für Mitglieder
  if (($id_mode == 3 || $id_mode == 4) && !$admin) return false;
  if (!is_array($oidMapping)) return false;

  snmp_set_quick_print(TRUE);
  snmp_set_oid_numeric_print(TRUE);
  snmp_set_enum_print(TRUE);

  if ($id_mode == 1) $isAP = true;
  if ($id_mode == 3 || $id_mode == 4) $isP2P = true;

  // Index von oidMapping ist id_oidtype in Tabellen oidtype und oid
  if ($isAP) {
    $macoid     = $oidMapping[11];
    $eapUserOid = $oidMapping[37];
    $ipUserOid  = $oidMapping[23];
    $snroid     = $oidMapping[12];
    $rxrateoid  = $oidMapping[14];
    $txrateoid  = $oidMapping[15];
    $wpaoid     = $oidMapping[20];
    $trafficoid = $oidMapping[16];
    $eventoid   = $oidMapping[39];
  }
  if ($isP2P) {
    $macoid     = $oidMapping[31];
    $snroid     = $oidMapping[32];
    $rxrateoid  = $oidMapping[34];
    $txrateoid  = $oidMapping[34];
    $trafficoid = $oidMapping[35];
    $wpaoid     = $oidMapping[36];
  }

  // SSID-Informationen
  $intoid     = $oidMapping[13];
  $networkoid = $oidMapping[18];
  $ssidoid    = $oidMapping[22];
  
  // Interface-Informationen
  $moduleoid  = $oidMapping[24];
  $firmwareoid= $oidMapping[25];
  $ifcoid     = $oidMapping[26];
  $eirpoid    = $oidMapping[27];
  $channeloid = $oidMapping[5];

  $keyoid     = $oidMapping[21];
  /* derzeitig kein Nutzen
  $vlanoid    = $oidMapping[17];
  */
  $statusoid  = $oidMapping[19];
  $p2pnameoid = $oidMapping[30];


  if (!isset($macoid) || !isset($snroid))
  {
    return false;
  }
  
  $retval = array();
  
  $mac = snmpwalk($ip, $snmp_pass, $macoid);
  if (isset($mac)) {
    $snr = snmpwalk($ip, $snmp_pass, $snroid);
    if (isset($rxrateoid) && is_array($snr))
      $rxrate = snmpwalk($ip, $snmp_pass, $rxrateoid);
    if (isset($txrateoid) && is_array($snr))
      $txrate   = snmpwalk($ip, $snmp_pass, $txrateoid);
    if (isset($trafficoid) && is_array($snr))
      $traffic  = snmpwalk($ip, $snmp_pass, $trafficoid);
    if (isset($ipUserOid) && is_array($snr))
      $ipUser      = snmpwalk($ip, $snmp_pass, $ipUserOid);

    /* derzeitig kein Nutzen
    if (isset($vlanoid))
      $vlanList     = snmpwalk($ip, $snmp_pass, $vlanoid);
    */

    // logisches Netzwerk/SSID ermitteln
    if (isset($intoid) && is_array($snr))
      $interface = snmpwalk($ip, $snmp_pass, $intoid);
    if (isset($networkoid) && is_array($interface))
      $network  = snmpwalk($ip, $snmp_pass, $networkoid);
    if (isset($ssidoid) && is_array($network))
      $retval['ssid'] = snmpwalk($ip, $snmp_pass, $ssidoid);

    // Status des WLAN-Clients ermitteln
    if (isset($statusoid))
      $status   = snmpwalk($ip, $snmp_pass, $statusoid);
    if (isset($eventoid))
      $event = snmpwalk($ip, $snmp_pass, $eventoid);

    // Verschluesselungsparameter ermitteln
    if (isset($wpaoid))
      $wpa      = snmpwalk($ip, $snmp_pass, $wpaoid);
    if (isset($keyoid) && is_array($wpa))
      $key      = snmpwalk($ip, $snmp_pass, $keyoid);
    if (isset($eapUserOid) && is_array($wpa))
      $eapUser  = snmpwalk($ip, $snmp_pass, $eapUserOid);

    if ($isP2P && is_array($snr))
      $p2pname = snmpwalk($ip, $snmp_pass, $p2pnameoid);

    // Client-Tabelle nach SNR absteigend sortieren	
    arsort($snr);
    // Daten lesbar machen
    $iter = 0;
    foreach ($snr as $i => $snr ) {
      $retval['snrtable'][$iter]['mac']       = clean($mac[$i],       'mac',     $id_type);
      $retval['snrtable'][$iter]['snr']       = clean($snr,           'snr',     $id_type);
      $retval['snrtable'][$iter]['rxrate']    = clean($rxrate[$i],    'rxrate',  $id_type);
      $retval['snrtable'][$iter]['txrate']    = clean($txrate[$i],    'txrate',  $id_type);
      $retval['snrtable'][$iter]['traffic']   = clean($traffic[$i],   'none',    $id_type);
      $retval['snrtable'][$iter]['interface'] = clean($interface[$i], 'none',    $id_type);
      $retval['snrtable'][$iter]['network']   = clean($network[$i],   'none',    $id_type);
      $retval['snrtable'][$iter]['wpa']       = clean($wpa[$i],       'wpa',     $id_type);
      $retval['snrtable'][$iter]['key']       = clean($key[$i],       'key',     $id_type);
      $retval['snrtable'][$iter]['status']    = clean($status[$i],    'status',  $id_type);
      $retval['snrtable'][$iter]['event']     = clean($event[$i],     'event',   $id_type);
      $retval['snrtable'][$iter]['name']      = clean($p2pname[$i],   'none',    $id_type);
      $retval['snrtable'][$iter]['ipUser']    = clean($ipUser[$i],    'ipUser',  $id_type);
      $retval['snrtable'][$iter]['eapUser']   = clean($eapUser[$i],   'eapUser', $id_type);
      $iter++;
   }
 
    // Auslesen der Hardware-Informationen
    if (isset($ifcoid))
      $ifc      = snmpwalk($ip, $snmp_pass, $ifcoid);
    if (isset($moduleoid) && is_array($ifc))
      $module   = snmpwalk($ip, $snmp_pass, $moduleoid);
    if (isset($firmwareoid) && is_array($module))
      $firmware = snmpwalk($ip, $snmp_pass, $firmwareoid);
    if (isset($eirpoid) && is_array($module))
      $eirp = snmpwalk($ip, $snmp_pass, $eirpoid);
    if (isset($channeloid) && is_array($ifc))
      $channel  = snmpwalk($ip, $snmp_pass, $channeloid);

    for ($i=0;$i<count($ifc);$i++) {
      $retval['ifctable'][$i]['ifc']      = clean($ifc[$i],      'none',    $id_type);
      $retval['ifctable'][$i]['module']   = clean($module[$i],   'none',    $id_type);
      $retval['ifctable'][$i]['firmware'] = clean($firmware[$i], 'none',    $id_type);
      $retval['ifctable'][$i]['eirp']     = clean($eirp[$i],     'none',    $id_type);
      $retval['ifctable'][$i]['channel']  = clean($channel[$i],  'channel', $id_type);
    }
  }
  return $retval;
}

function printSNRTable($id_node, $admin = FALSE)
{
  snmp_set_quick_print(TRUE);
  snmp_set_oid_numeric_print(TRUE);
  snmp_set_enum_print(TRUE);

  $er = error_reporting(0);

  gserverl $isLancom;
  $isLancom = false;

  $timeout = '12000';
  $retries = '5';

  $html = '<div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>'."\n";

  $interface = DB_DataObject::factory('interface');
  $interface->id_node = $id_node;
  $interface->whereAdd('id_mode in (1,3,4)');
  $interface->whereAdd('ip != ""');
  $interface->whereAdd('ip != "0.0.0.0"');
//  if (!$interface->find()) return ('Kein Interface zum Auslesen der SNR-Werte gefunden.');

  if ($interface->find()) {
    $ip = $interface->ip;
  } else {
    $node = DB_DataObject::factory('node');
    $node->get($interface->id_node);
    $location = DB_DataObject::factory('location');
    $location->get($node->id_location);
    $html .= '<p>Es wurden keine Netzwerkschnittstellen zum Auslesen der SNR-Werte gefunden.</p>';
    $html .= '<p>Zur <a href="/cgi-bin/smokeping.cgi?target=id_section_'.$location->id_section.'.id_location_'.$location->id_location.'.id_node_'.$node->id_node.'">Pingstatistik des Ger&auml;tes '.$node->description.'</a></p>';
    return $html;
  }


  $targets = array();
  $devices = array();
  while($interface->fetch())
  {
    if (!$ip) $ip = $interface->ip;
    $targets[$interface->id_mode] = array('ip' => $interface->ip, 'id_device' => $interface->id_device, 'id_mode' => $interface->id_mode);
    $devices[] = $interface->id_device;
  }

  $node = $interface->getLink('id_node', 'node');
  $isLancom = preg_match("/^LANCOM/", $node->getLink('id_type','type')->description);

  $snmp_community = $node->snmp_community;

  if ($snmp_community == 'disabled' or empty($snmp_community)) return ('Die SNMP-Abfrage ist f&uuml;r dieses Ger&auml;t deaktiviert oder es ist keine SNMP-Community eingetragen.');

  $sysdesc = snmpget($ip, $snmp_community, "1.3.6.1.2.1.1.1.0");

  if(!is_string($sysdesc))
  {
    return('Die SNMP-Abfrage ist fehlgeschlagen. Ist das Gerät online? (sys)');
  }

  $oid = DB_DataObject::factory('oid');
  $oid->id_type = $node->id_type;
  $oid->whereAdd('id_device IN ('.implode(',', $devices).')');
  $oid->find();
  $oidMapping = array();
  while ($oid->fetch()) {
    $oidMapping[$oid->id_device][$oid->id_oidtype] = $oid->oid;
  }

  if (!isset($oidMapping))
  {
    return('Keine OIDs f&uuml;r dieses Ger&auml;t/Interface gefunden.');
  }

  $data = array();
  foreach ($targets as $id_mode => $target) {
    if (($id_mode == 3 || $id_mode == 4) && !$admin) continue;
    $data[$id_mode] = getSNRTable($target['ip'], $snmp_community, $timeout, $retries, $id_mode, $oidMapping[$target['id_device']], $admin, $node->id_type);
  }
  $p2ponly = false;
  $html = '';
  $channel_text = '';
  $module_text = '';
  
  foreach ($data as $mode) {
    if ($channel_text == '' && $module_text == '') {
	  foreach ($mode['ifctable'] as $id => $ifdata) {
	    $channel_text .= $ifdata['ifc'].': Kanal '.$ifdata['channel'].' ';
		$module_text .= $ifdata['ifc'].': '.$ifdata['module'].'('.$ifdata['firmware'].', EIRP: '.$ifdata['eirp'].') ';
	  }
	}
  }
  
  if ($channel_text != '' && $module_text != '') {
    foreach(array_keys($data) as $id => $mode) {
	  $data[$mode] = array_diff_assoc($data[$mode], array('ifctable' => $data[$mode]['ifctable']));
	}
  }
  

  $html .= '<h4>'.$sysdesc.' '.$channel_text."</h4>\n\n".$module_text."\n";
  
  if (!is_array($data['1']))
  {
    $p2ponly = true;
	if (!$admin) return $html;
  }
  
  if (!$p2ponly) {

	$html .= '<h5>AP</h5>';

	if (count($data['1']['snrtable']) == 0)
	{
		$html .= '<p>Keine verbundenen Clients gefunden.</p>';
	}
	else
	{
      		$html .= '<table border="1" class="sortable">'.
               '  <thead><tr>'.
               ($data['1']['ssid']?'   <th>SSID</th>':'').
               '    <th>Mac Adresse</th>'.
               '    <th>Mitgliedsname</th>'.
               '    <th>SNR</th>'.
               ($admin?'    <th>Traffic</th>':'    <th><img src="../images/scale_snr.gif"></th>');

	if ($admin)
	{
		$html .= '<th>TX-Rate</th>';
	}
	else
	{
		$html .= '<th><abbr title="Sendegeschwindigkeit">Sendegeschw.</abbr> <abbr title="Accesspoint">AP</abbr></th>';
	}

	if ($admin)
	{
		$html .= '<th>RX-Rate</th>';
	}
	else
	{
		$html .= '<th><abbr title="Sendegeschwindigkeit">Sendegeschw.</abbr> Client</th>';
	}

	$html .= ('    <th>MAC g&uuml;ltig</th>').
               ($admin?'    <th>Status</th>':'').
               ($admin?'    <th>letztes Ereignis</th>':'').
               ($admin?'    <th>Enc/Key</th>':'').
               ($admin?'    <th>IP-Adresse</th>' : '').
               ($admin?'    <th>E-Mail</th>' : '').
               '  </tr></thead><tbody>';
    }

    $clients = $clientsSec = 0;

    foreach ($data['1']['snrtable'] as $id => $row) {
      $mac       = $row['mac'];
      $snr       = $row['snr'];
      $txrate    = (isset($row['txrate'])?$row['txrate']:false);
      $rxrate    = (isset($row['rxrate'])?$row['rxrate']:false);
      $traffic   = (isset($row['traffic'])?$row['traffic']:false);
      $interface = (isset($row['interface'])?$row['interface']:false);
      $network   = (isset($row['network'])?$row['network']:false);
      $status    = (isset($row['status'])?$row['status']:false);
      $event     = (isset($row['event'])?$row['event']:false);
      $wpaver    = (isset($row['wpa'])?$row['wpa']:false);
      $key       = (isset($row['key'])?$row['key']:false);
      $ipUser    = (isset($row['ipUser'])?$row['ipUser']:false);
      $eapUser   = (isset($row['eapUser'])?$row['eapUser']:false);

    // nur echt verbundene Clients anzeigen
    // filtere Clients deren letztes Ereignis eine Ablehnung durch den AP war
    if ($event == 'deauth') continue; // Assoziierung durch AP noch vor RADIUS-Abfrage abgelehnt
    if ($event == 'admin-deassoc') continue; // RADIUS hat Client abgelehnt

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
    // TODO: Versionsprüfung umstellen auf >= "7."
    if (strpos($sysdesc, 'LANCOM L-54 dual Wireless 7.') !== FALSE OR strpos($sysdesc, 'LANCOM L-54 dual Wireless 8.') !== FALSE) {
        $ssidmapping = array(
            // interface 1
            array (0, 2, 3, 4, 5, 6, 7, 8),
            // interface 2
            array (1, 9, 10, 11, 12, 13, 14, 15)
        );
        $ssidkey  = $ssidmapping[$interface][$network];
    }

    $ssid     = clean($data['1']['ssid'][$ssidkey],'ssid', $node->id_type);

    $macprint = ($admin?$mac:'xxxxxx-xx'.substr($mac,9,4));
		
	$macs = DB_DataObject::factory('Macs');
	$macs->Mac = $mac;
	$macs->find(TRUE);

    if (isset($macs->BNID)) {
	$validClient = true;
	$mitglieder    = $macs->getLink('BNID', 'Mitglieder', 'BNID');

	$kommunikation = DB_DataObject::factory('Kommunikation');
	$kommunikation->BNID = $macs->BNID;
	$kommunikation->TKommunikation = 'email';
	$kommunikation->find(TRUE);

	$mname = $macs->BNID == '0' ? 'FBN: ['.$macs->Bemerkung.']' : $mitglieder->Vorname.' '.$mitglieder->Nachname;
        if ($admin && $macs->BNID!='') { $mname .= ' ['.$macs->BNID.']'; }

        // Mitglied darf über seinen eigenen Client mehr Informationen erhalten
	$self = FALSE;
        if (is_string($_SERVER['PHP_AUTH_USER']) && $_SERVER['PHP_AUTH_USER'] == $mitglieder->Username) {
          $self = TRUE;
        }
        if ($macs->ValidTo)
        {
          $validto = $macs->ValidTo;
        } else {
          $validto = "unbegrenzt";
        }

        if (isset($kommunikation) && !empty($kommunikation->Value))
        {
          $mkommunikation = '<a href="mailto:'.$kommunikation->Value.'">anschreiben</a>';
        } else {
          $mkommunikation = 'keine E-Mail';
        }

    } elseif ($status == 'verbunden' && $wpaver!='none') {
        $mitglied = DB_DataObject::factory('Mitglieder');
        $mitglied->Username = $eapUser;
        $mitglied->find(TRUE);
        $mname = $mitglied->Vorname.' '.$mitglied->Nachname;
        if ($admin && $mitglied->BNID!='') { $mname .= ' ['.$mitglied->BNID.']'; }
        $validto = "via EAP";
        $mkommunikation = "via EAP";
        unset($kommunikation);
    } else {
	$validClient = false;
        $mname = "kein FBN: ".$mac;
        $validto = "ung&uuml;ltig";
        $mkommunikation = '';
        unset($kommunikation);
    }

    $limit=$snr*4;
    $color="yellow";
    if ($snr>15) $color="green";
    if ($snr<10) $color="red";
    if ($snr<1) $limit=1;
    $rx = explode(' ',$rxrate);
    $wpainfo = '';
    if ( (isset($wpaver) && $wpaver!='none') || stristr($ssid, 'WPA') || stristr($ssid, 'EAP') )
    {
        $wpainfo = '<a href="https://www.example.org/EAP" title="Anleitung: Wie richte ich eine WPA2-verschl&uuml;sselte Verbindung mit EAP ein?"><img src="../images/ssl-symbol.png" alt="WPA-verschlüsselt" width="13" height="15" align="absmiddle" /></a>';
    }
    $html .= '<tr>'.
//             ($admin && $interface?'<td>'.$interface.'</td>':'').
             ($ssid?'<td>'.$wpainfo.$ssid.'</td>':'');

    if ($admin)
    {
      $mac_split = explode('-', $mac);
      $html .= '  <td><a href="http://standards.ieee.org/cgi-bin/ouisearch?'.$mac_split[0].'" onmouseover="return overlib(\'<iframe scrolling=no marginheight=0 marginwidth=0 src=./snrimage.php?id_node='.$id_node.'&mac='.$mac.' height=175 width=397>\', VAUTO, STICKY, MOUSEOFF);" onmouseout="return nd();">'.$mac.'</td>';
    }
    else if ($self)
    {
      $html .= '  <td><a href="javascript:void(0);" onmouseover="return overlib(\'<iframe scrolling=no marginheight=0 marginwidth=0 src=./snrimage.php?id_node='.$id_node.'&mac='.$mac.' height=175 width=397>\', VAUTO, STICKY, MOUSEOFF);" onmouseout="return nd();">'.$mac.'</td>';
    }
    else
    {
      $html .= '  <td>'.$macprint.'</td>';
    }
    $html .= '  <td>'.$mname.'</td>';
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

    $html .= ($admin?'':'  <td><img border="0" src="../images/'.$color.'.png" width="'.$limit.'" height="15"></td>').
             (($admin && $traffic)?'  <td align="center">'.$traffic.'</td>':'').
             ($txrate?'  <td align="center">'.$txrate.'</td>':'').
             ($rxrate?'  <td align="center"'.($rx[0] < 11 && $rxrate!='unknown'?' bgcolor="red"':'').'>'.$rxrate.'</td>':'').
             ('  <td>'.$validto.'</td>').
             ($admin?'<td>'.$status.'</td>':'').
             ($admin?'<td>'.$event.'</td>':'').
             ((($wpaver || $key) && $admin)?'  <td>'.($wpaver?$wpaver.'/':'').$key.'</td>':'').
             ($admin?'  <td>'.$ipUser.'</td>':'').
             ($admin?'  <td>'.$mkommunikation.'</td>' : '').
             "</tr>\n";

	  // Statistik über Verschlüsselung führen
          // wenn MAC in Datenbank oder Status verbunden (fuer EAP-Nutzer ohne MAC in DB)
          if ($validClient || $status == 'verbunden')
          {
            $clients++;
            if ( (isset($wpaver) && $wpaver!='none') || stristr($ssid, 'WPA') || stristr($ssid, 'EAP') )
            { $clientsSec++; }
          }
	}
	$html .= "</tbody></table>\n";

        if ( $clients > 0 ) {
          $html .= '<p>verbundene Clients: '.$clients.', davon '.$clientsSec.' verschl&uuml;sselt ('.sprintf('%.2f',$clientsSec/$clients*100).'%)</p>'.
                   '<p>Wenn die Sendegeschwindigkeit des Clients niedriger ist als die Sendegeschwindigkeit des AP oder ihr SNR unter 10 liegt, '.
                   'deutet dies auf einen schlechten Empfang hin. Sie sollten ihre Installation/Antenne &uuml;berpr&uuml;fen.</p>';
        }
    $data = array_diff_assoc($data,array('1' => $data['1']));
  }
  
  foreach ($data as $mode => $tables) {
  	if (count($tables['snrtable']) > 0)
	{
	// nur wenn Geräte in diesem Modus verbunden
	  $html .= '<h5>P2P</h5>'.
                   '<table border="1" class="sortable">'.
                   '  <thead><tr>'.
                   '    <th>MAC-Adresse</th>'.
                   '    <th>Stationsname</th>'.
                   '    <th>SNR</th>'.
                   '    <th>Traffic</th>'.
	           '<th>TX-Rate</th>'.
                   '<th>RX-Rate</th>'.
                   "</tr></thead><tbody>\n";
			   
		foreach($tables['snrtable'] as $row) {
			$mac       = $row['mac'];
			$snr       = $row['snr'];
			$txrate    = (isset($row['txrate'])?$row['txrate']:false);
			$rxrate    = (isset($row['rxrate'])?$row['rxrate']:false);
			$traffic   = (isset($row['traffic'])?$row['traffic']:false);
			$status    = (isset($row['status'])?$row['status']:false);
			$wpaver    = (isset($row['wpa'])?$row['wpa']:false);
			$key       = (isset($row['key'])?$row['key']:false);
			$name      = (isset($row['name'])?$row['name']:false);
			
		    $color="yellow";
		    if ($snr>15) $color="green";
		    if ($snr<10) $color="red";
		    $rx = explode(' ',$rxrate);
			
			      $html .= '<tr>'.
						   '  <td>'.$mac.'</td>'.
						   '  <td>'.$name.'</td>';

		  if ($snr < '10') {
			$html .= '  <td align="center" bgcolor="red">'.$snr.'</td>';
		  } else if ($snr < '15') {
			$html .= '  <td align="center" bgcolor="yellow">'.$snr.'</td>';
		  } else {
			$html .= '  <td align="center" bgcolor="green">'.$snr.'</td>';
		  }


    $html .= '  <td align="center">'.$traffic.'</td>'.
             '  <td align="center">'.$txrate.'</td>'.
             '  <td align="center"'.($rx[0] < 11 && !is_string($rx[0]) && $rxrate!='unknown'?' bgcolor="red"':'').'>'.$rxrate.'</td>'.
             "</tr>\n";
		}
	}
	
	$html .= "</tbody></table>\n";
	
	break;
  }
  $location = DB_DataObject::factory('location');
  $location->get($node->id_location);
  $html .= '<p>Zur <a href="/cgi-bin/smokeping.cgi?target=id_section_'.$location->id_section.'.id_location_'.$location->id_location.'.id_node_'.$node->id_node.'">Pingstatistik des Ger&auml;tes '.$node->description.'</a></p>';
  $html .= '<script type="text/javascript" src="../js/sorttable.js"></script>';  
  return utf8_encode($html);
}
?>
