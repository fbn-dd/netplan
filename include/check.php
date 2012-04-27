<?php

require_once 'Net/Ping.php';

function host_alive($ip) {
  $ping = Net_Ping::factory();
  $ping->setArgs(array("count" => 1, "timeout" => 5, "size"  => 32));
  $result = $ping->ping($ip);
  if(!$result->getValue('_received')) {
    return FALSE;
  } else {
    return TRUE;
  }
}

function snmp_alive($ip, $community) {
  // ist der Host anpingbar
  if(host_alive($ip)) {
      if($ip === '10.10.10.2') {
        return TRUE; // Loadbalancer hat SNMP an, hoert aber dummerweise nicht auf die OID
      } else {
	$er = error_reporting(0);
        $array = snmpwalk($ip, $community, '.1.3.6.1.2.1.1.5', 50000, 1); // 50ms timeout
        if(is_array($array)) {
	  error_reporting($er);
          return TRUE;
        } else {
	  error_reporting($er);
          return FALSE;
        }
      }
  } else {
    return FALSE;
  }
}

?>
