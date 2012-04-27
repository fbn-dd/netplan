<?php

function snmptable($host, $community, $oid) {
	$mapper['staWlanInterfTable'] = array('staWlanInterfIfc', 'staWlanInterfOperatin');
	$mapper['staLanInterfacesTable'] = array('staLanInterfacesIfc', 'staLanInterfacesLin');
	$mapper['staWlanWlanpaTable'] = array('staWlanWlanpaIfc', 'staWlanWlanpaRadiob', 'staWlanWlanpaRadioc', 'staWlanWlanpa108mbp');
	$mapper['staTcpipNetworkTable'] = array('staTcpipNetworkIpa', 'staTcpipNetworkIpn', 'staTcpipNetworkVla', 'staTcpipNetworkInt', 'staTcpipNetworkSrc');
	$mapper['staWlanInterpAccTable'] = array('staWlanInterpAccIndex', 'staWlanInterpAccMacadd', 'staWlanInterpAccAntenn', 'staWlanInterpAccIdenti');
	$mapper['setWanIplistTable'] = array('setWanIplistIpadd', 'setWanIplistPeer', 'setWanIplistIpnet', 'setWanIplistGatew');
	$mapper['firVerTable'] = array('firVerMod', 'firVerVer', 'firVerSer');

	$retval = array();

    	foreach ($mapper[$oid] as $target) {
		$retval[$target] = snmpwalk($host, $community, $target, $timeout);
	}

	return($retval);
}

function scan($ipaddress, $community) {
	snmp_set_quick_print(1);
	snmp_set_valueretrieval(SNMP_VALUE_LIBRARY);

	gserverl $timeout;
	$timeout = '5000';

	$lancom['.1.3.6.1.4.1.2356.600.2.54'] = "lancom-l54g";
	$lancom['.1.3.6.1.4.1.2356.600.3.54'] = "lancom-l54ag";
	$lancom['.1.3.6.1.4.1.2356.600.3.55'] = "lancom-l54-dual";

	$sysObjectId = snmpget($ipaddress, $community, 'sysObjectId', $timeout);
	if (!$sysObjectId OR !array_key_exists($sysObjectId, $lancom)) {
		return false;
	}
	echo "sysObjectId: $sysObjectId Geraet: $lancom[$sysObjectId]";

	if(!snmp_read_mib("./mib/$lancom[$sysObjectId].mib")) {
		echo "Fehler: Die MIB-Datei konnte nicht gelesen werden.";
		return false;
	}

	$output = array();
	$output['staWlanInterfTable'] = snmptable($ipaddress, $community, 'staWlanInterfTable');
	$output['staLanInterfacesTable'] = snmptable($ipaddress, $community, 'staLanInterfacesTable');
	$output['staWlanWlanpaTable'] = snmptable($ipaddress, $community, 'staWlanWlanpaTable');
	$output['staTcpipNetworkTable'] = snmptable($ipaddress, $community, 'staTcpipNetworkTable');
	$output['staWlanInterpAccTable'] = snmptable($ipaddress, $community, 'staWlanInterpAccTable');
	$output['setWanIplistTable'] = snmptable($ipaddress, $community, 'setWanIplistTable');
	$output['firVerTable'] = snmptable($ipaddress, $community, 'firVerTable');

	echo "<pre>".print_r($output,1)."</pre>";

	return true;
}
?>
