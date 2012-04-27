<?php
require_once(dirname(__FILE__) . "/../include/db.php");
require_once "Net/CheckIP.php";

function bintocdr ($binin){
        return strlen(rtrim($binin,"0"));
}

function bintodq ($binin) {
        $binin=explode(".", chunk_split($binin,8,"."));
        for ($i=0; $i<4 ; $i++) {
                $dq[$i]=bindec($binin[$i]);
        }
        return implode(".",$dq) ;
}

function dqtobin($dqin) {
        $dq = explode(".",$dqin);
        for ($i=0; $i<4 ; $i++) {
           $bin[$i]=str_pad(decbin($dq[$i]), 8, "0", STR_PAD_LEFT);
        }
        return implode("",$bin);
}

$netmask = DB_DataObject::factory('netmask');
$netmask->find();
$netmaskMapping = array();
while ($netmask->fetch()) {
  $netmaskMapping[$netmask->id_netmask] = $netmask->description;
}

$node = DB_DataObject::factory('node');
$node->find();
$nodeMapping = array();
while ($node->fetch()) {
  $nodeMapping[$node->id_node] = $node->description;
}

$device = DB_DataObject::factory('device');
$device->find();
$deviceMapping = array();
while ($device->fetch()) {
  $deviceMapping[$device->id_device] = $device->description;
}

$interface = DB_DataObject::factory('interface');
$interface->whereAdd('ip LIKE "10%"');
$interface->whereAdd('ip NOT LIKE "10.10.%"');
$interface->groupBy('ip');
$interface->find();

$networkMapping = array();

while ($interface->fetch())
{
  $dq_host = $interface->ip;
  $bin_host=dqtobin($dq_host);

  // Netmask
  $bin_nmask=dqtobin($netmaskMapping[$interface->id_netmask]);
  $cdr_nmask=bintocdr($bin_nmask);

  // Broadcast
  $bin_bcast=(str_pad(substr($bin_host,0,$cdr_nmask),32,1));
  $dq_bcast=bintodq($bin_bcast);

  // Network
  $bin_net=(str_pad(substr($bin_host,0,$cdr_nmask),32,0));
  $dq_net=bintodq($bin_net);

  // Gateway per definition first host in network
  $bin_first=(str_pad(substr($bin_net,0,31),32,1));
  $dq_first=bintodq($bin_first);

  // number of possible hosts on this network
  $host_total=(bindec(str_pad("",(32-$cdr_nmask),1)) - 1);

  if (!array_key_exists($dq_net, $networkMapping) && $host_total > 0 && $dq_host == $dq_first)
  {
    $networkMapping[$dq_net]['gateway'] = $dq_first;
    $networkMapping[$dq_net]['broadcast'] = $dq_bcast;
    $networkMapping[$dq_net]['node'] = $nodeMapping[$interface->id_node];
    $networkMapping[$dq_net]['device'] = $deviceMapping[$interface->id_device];
	$networkMapping[$dq_net]['netmask'] = $netmaskMapping[$interface->id_netmask];

    if ( $interface->dhcp_start && $interface->dhcp_end )
    {
      $networkMapping[$dq_net]['dhcp_start'] = $interface->dhcp_start;
      $networkMapping[$dq_net]['dhcp_end'] = $interface->dhcp_end;
    } else {
      // default for 255.255.255.0
      if ($cdr_nmask == 24)
      {
        $network = explode('.', $dq_net);

        if ($network[2] < 128)
        {
          $network[3] = 130;
          $networkMapping[$dq_net]['dhcp_start'] = implode('.', $network);
          $network[3] = 230;
          $networkMapping[$dq_net]['dhcp_end'] = implode('.', $network);
        } else {
          $network[3] = 20;
          $networkMapping[$dq_net]['dhcp_start'] = implode('.', $network);
          $network[3] = 120;
          $networkMapping[$dq_net]['dhcp_end'] = implode('.', $network);
        }
      }
    }
  }
}

foreach ($networkMapping as $network => $pref)
{
  if (!isset($pref['dhcp_start']) or !is_string($pref['dhcp_start'])) 
  {
    syslog(LOG_WARNING, 'netplan/dhcp :: '.$pref['node'].' - '.$pref['device'].': no dhcp start ip address defined');
    continue;
  }

  if (!isset($pref['dhcp_end']) or !is_string($pref['dhcp_end']))
  {
    syslog(LOG_WARNING, 'netplan/dhcp :: '.$pref['node'].' - '.$pref['device'].': no dhcp end ip address defined');
    continue;
  }


  $dhcp_start = explode(';', $pref['dhcp_start']);
  $dhcp_end = explode(';', $pref['dhcp_end']);

  $invalidip = FALSE;

  foreach ($dhcp_start as $ip) 
  {
    // is this dhcp start address really an ip address
    if (!Net_CheckIP::check_ip($ip))
    {
      syslog(LOG_WARNING, 'netplan/dhcp :: '.$pref['node'].' - '.$pref['device'].': invalid dhcp start ip address ("'.$ip.'")');
      $invalidip = TRUE;
    }
    // is this dhcp start address in our network
    $dq_net_dhcp = bintodq(str_pad(substr(dqtobin($ip),0,bintocdr(dqtobin($pref['netmask']))),32,0));
	if ($network != $dq_net_dhcp)
    {
      syslog(LOG_WARNING, 'netplan/dhcp :: '.$pref['node'].' - '.$pref['device'].': dhcp start ip address ("'.$ip.'") is not in our network - Network: '.$network.'/'.bintocdr(dqtobin($pref['netmask'])));
      $invalidip = TRUE;
    } 
  }

  foreach ($dhcp_end as $ip)
  {
    // is this dhcp end address really an ip address
    if (!Net_CheckIP::check_ip($ip))
    {
      syslog(LOG_WARNING, 'netplan/dhcp :: '.$pref['node'].' - '.$pref['device'].': invalid dhcp end ip address ("'.$ip.'")');
      $invalidip = TRUE;
    }
    // is this dhcp end address in our network
    $dq_net_dhcp = bintodq(str_pad(substr(dqtobin($ip),0,bintocdr(dqtobin($pref['netmask']))),32,0));
    if ($network != $dq_net_dhcp)
    {
      syslog(LOG_WARNING, 'netplan/dhcp :: '.$pref['node'].' - '.$pref['device'].': dhcp end ip address ("'.$ip.'") is not in our network - Network: '.$network.'/'.bintocdr(dqtobin($pref['netmask'])));
      $invalidip = TRUE;
    }
  }


  if (count($dhcp_start) == count($dhcp_end) && !$invalidip)
  { 
    echo (	"##########################################\n".
    		'# '.$pref['node'].' - '.$pref['device']."\n".
	    	"##########################################\n\n".
    		'subnet '.$network.' netmask '.$pref['netmask']." {\n" );
    
    for ($i=0;$i<count($dhcp_start);$i++)
    {
      echo "  range ".$dhcp_start[$i].' '.$dhcp_end[$i].";\n";
    }

    echo (  '  option routers '.$pref['gateway'].";\n".
        	'  option broadcast-address '.$pref['broadcast'].";\n".
        	'  option subnet-mask '.$pref['netmask'].";\n}\n\n" );
  }
}
?>
