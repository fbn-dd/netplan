<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <link rel="stylesheet" href="../css/gserverl.css" type="text/css" />
  <title>AP-&Uuml;bersicht</title>
</head>
<body>
  <div class="normalbox">
<?php
require_once(dirname(__FILE__) . "/../include/db.php");
require_once(dirname(__FILE__) . "/../include/snr.php");

DB_DataObject::debugLevel(5);

function scan($ip, $mac, $password = 'public')
{
  $macList = getFromSNMP("mac","walk", $ip, "$password");

  foreach ($macList as $id => $client_mac)
  {
    if ($client_mac == $mac)
    {
      $mac_id = $id;
      break;
    }
  }
  $snr = getFromSNMP("snr","walk", $ip, "$password");

  return $snr[$mac_id];
}

if ($_POST['action'] == 'chooseap')
{
  $mac = $_POST['mac'];

  $mac = ereg_replace('-','',$mac);
  $mac = ereg_replace(':','',$mac);
  $mac = ereg_replace(' ','',$mac);
  $mac = strtolower($mac);
  $mac = substr($mac,0,6).'-'.substr($mac,6,6);

  $radius = DB_DataObject::factory('Radius_postauth');
  $radius->query("SELECT DISTINCT client, nas_ip FROM {$radius->__table} WHERE user = '".$mac."' AND date < '".date("Y-m-d H:i:s",strtotime("-3 days"))."' ORDER BY client");

  while ($radius->fetch())
  {
    $aps[$radius->nas_ip] = $radius->client;
  }

  if (isset($aps)) 
  {
?>
<h1>Scanner</h1>
<p>AP ausw&auml;hlen</p>
<form action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="GET">
<?php
    foreach ($aps as $ip => $description)
    {
      echo '<input type="radio" name="ap" value="'.$ip.'">'.$description.'<br>';
    }
?>
<input type="hidden" name="action" value="measure" />
<input type="hidden" name="mac" value="<?php echo $mac ?>" />
<input type="submit" value="Abschicken" />
</form>
<?php
  }
  else
  {
    echo '<p>MAC kann keinem AP zugeordnet werden.</p><p><a href="./">Wiederholen</a></p>';
  }
}
else if ($_GET['action'] == 'measure')
{
?>
<script type="text/javascript">
setTimeout("location.reload()",5000);
</script>
<?php
  $ip = $_GET['ap'];
  $mac = $_GET['mac'];

  $do =& DB_DataObject::factory('device');
  $do->orderBy('description');
  $do->find();
  while ($do->fetch()) {
    $deviceMapping[$do->id_device] = $do->description;
  }

  $interface = DB_DataObject::factory('interface');
  $interface->ip = $ip;
  $interface->find();
  
  while ($interface->fetch())
  {
    $id_node = $interface->id_node;
  }

  $node =& DB_DataObject::factory('node');
  $node->id_node = $id_node;
  $node->find(TRUE);

  $result = scan($ip, $mac, $node->snmp_community);

  echo $node->description."-".$deviceMapping[$interface->id_device]." SNR: ".$result;
}
else
{
?>
Client-MAC:
<form method="POST" action="<?php echo $_SERVER["PHP_SELF"]; ?>">
    <input name="action" value="chooseap" type="hidden" />
    <input name="mac" type="text" />
    <input value="Abschicken" type="submit" />
</form>
<?php 
}
?>
</div>
</body>
</html>
