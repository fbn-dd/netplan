<?php
require_once(dirname(__FILE__) . "/../include/time_start.php");
require_once(dirname(__FILE__) . "/../include/layout.php");
require_once(dirname(__FILE__) . "/scan.php");
require_once('HTML/QuickForm.php');
require_once('Net/CheckIP.php');
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"></meta>
  <link rel='stylesheet' href='../css/gserverl.css' type='text/css'></link>
  <title>Netplan v2 - LANCOM-Scan</title>
</head>

<body>
<?php printMenu(); ?>
<div class='normalbox'>

<?php
$form =& new HTML_QuickForm('scan', 'get');
$form->addElement('header', null, 'Lancom Scan');
$form->addElement('text', 'ipaddress', 'IP-Adresse:');
$form->addElement('text', 'community', 'SNMP Community:');
$form->addElement('submit', 'action', 'Scan');

if (!$_GET['action'] || $_GET['action'] != 'Scan') {
	$form->display();
}
if ($_GET['action'] == 'Scan') {
	$form->setDefaults($_GET);
	$ipaddress = $_GET['ipaddress'];
	$community = $_GET['community'];
	if (!Net_CheckIP::check_ip($ipaddress) OR !is_string($community)) {
		echo("Fehler: Die IP-Addresse entspricht nicht dem Schema W.X.Y.Z oder SNMP-Community ist keine Zeichenkette.");
		$form->display();	
		return;
	}
	$result = scan($ipaddress, $community);
	if (!$result) {
		echo("Fehler: Keine Antwort vom Gerät $ipadress erhalten. Ist die IP-Adresse korrekt?");
	}
	$form->display();	
}
?>

</div>

<div class='normalbox'><?php require(dirname(__FILE__) . "/../include/time_end.php"); ?></div>

</body>

</html>
