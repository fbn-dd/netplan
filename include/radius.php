<?php

require_once(dirname(__FILE__) . "/db.php");

function createClientsConf() {

  $node = DB_DataObject::factory('node');
  $node->find();
  $nodeMapping = array();
  while ($node->fetch()) {
    $nodeMapping[$node->id_node] = $node->toArray();
  }
  
  $device = DB_DataObject::factory('device');
  $device->find();
  $deviceMapping = array();
  while ($device->fetch()) {
    $deviceMapping[$device->id_device] = $device->description;
  }
  
  $interface = DB_DataObject::factory('interface');
  $interface->orderBy('ip');
  $interface->find();
  $interfaceMapping = array();
  while ($interface->fetch()) {
    //$interfaceMapping[$interface->id_interface] = $interface->toArray();
    $interfaceMapping[$interface->ip] = $interface->toArray();
  }
  
  //foreach($interfaceMapping as $id_interface => $interface) {
  foreach($interfaceMapping as $ip => $interface) {
    // überspringe Interfaces ohne gültige IP
    if(empty($interface['ip']) === true) continue;
    // überspringe Interfaces mit Fake-Einträgen, vermeide doppelte RADIUS-Clients
    if(trim($interface['ip']) == '127.0.0.1') continue;
?>
# <?php echo $nodeMapping[$interface['id_node']]['description'] ?> - <?php echo $deviceMapping[$interface['id_device']] ?>

client <?php echo $interface['ip'] ?> {
  secret = <?php echo ($nodeMapping[$interface['id_node']]['radius_password']==''?"test":$nodeMapping[$interface['id_node']]['radius_password']) ?>

  shortname = <?php echo $nodeMapping[$interface['id_node']]['description'] ?>-<?php echo $deviceMapping[$interface['id_device']] ?>

  nastype = other
}

<?php }
}

function createMacTable()
{
  $mac = DB_DataObject::factory('Macs');
  $mac->whereAdd('ValidTo is null');
  $mac->whereAdd('ValidTo > NOW()', 'OR');
  $mac->find();
  $macMapping = array();
  while ($mac->fetch()) {
    $macMapping[$mac->Mac] = $mac->Mac;
  }
  unset($mac);

  $radmac = DB_DataObject::factory('radcheck_mac');
  $radmac->find();
  $radmacMapping = array();
  while ($radmac->fetch())
  {
    $radmacMapping[$radmac->UserName] = $radmac->UserName;
  }
  unset($radmac);
  
  $insert = array_diff($macMapping, $radmacMapping);
  $delete = array_diff($radmacMapping, $macMapping);

  print "Mac Table count ".count($macMapping)."\n";
  print "RadMac Table count ".count($radmacMapping)."\n";
  print "insert ".count($insert)." new macs\n";
  print_r($insert);
  print "delete ".count($delete)." old macs\n";
  print_r($delete);
  
  foreach ($insert as $mac)
  {
      $radmac = DB_DataObject::factory('radcheck_mac');
      $radmac->UserName = $mac;
      $radmac->Attribute = 'Auth-Type';
      $radmac->op = ':=';
      $radmac->Value = 'Accept';
      $radmac->insert();
      unset($radmac);
  }
  foreach ($delete as $mac)
  {
      $radmac = DB_DataObject::factory('radcheck_mac');
      $radmac->UserName = $mac;
      $radmac->delete();
      unset($radmac);
  }
}

/***
 * @TODO: https://www.example.org/projekte/view.php?id=126
 *
 */
function createUserTable()
{
  $user = DB_DataObject::factory('Mitglieder');
  $user->whereAdd('Password != "ausgetreten"');
  // mysql between schliesst Grenzen ein
  $user->whereAdd('Status BETWEEN 1600 AND 8900');
  $user->find();
  $userMapping = array();
  while ($user->fetch()) {
    $userMapping[$user->Username] = $user->Password;
  }
  unset($user);

  $raduser = DB_DataObject::factory('radcheck_user');
  $raduser->query("LOCK TABLES radcheck_user READ, radcheck_user WRITE;");
  // $raduser->query("TRUNCATE radcheck_user;"); // funktioniert nicht mit PEAR MDB2 v2.5.0b3
  $raduser->query("DELETE FROM radcheck_user;");
  foreach ($userMapping as $username => $password)
  {
    // neuer Nutzer
      $raduser2 = DB_DataObject::factory('radcheck_user');
      $raduser2->UserName = $username;
      $raduser2->Attribute = 'Cleartext-Password';
      $raduser2->op = ':=';
      $raduser2->Value = $password;
      $raduser2->insert();
      unset($raduser2);
  }

  $raduser->query("UNLOCK TABLES;");
if (PEAR::isError($raduser)) {
    die($mdb2->getMessage());
}
  unset($raduser);
}
?>
