<?php

function deleteSection($id_section) {
  $do = DB_DataObject::factory('section');
  $do->whereAdd('id_section = '.$id_section);
  return array('delete' => 1, 'html' =>  'Löschen '.($do->delete(DB_DATAOBJECT_WHEREADD_ONLY)===FALSE?'fehlgeschlagen':'erfolgreich'));
}

function deleteLocation($id_location) {
  $do = DB_DataObject::factory('location');
  $do->whereAdd('id_location = '.$id_location);
  return array('delete' => 1, 'html' =>  'Löschen '.($do->delete(DB_DATAOBJECT_WHEREADD_ONLY)===FALSE?'fehlgeschlagen':'erfolgreich'));
}

function deleteNode($id_node) {
  $do = DB_DataObject::factory('node');
  $do->whereAdd('id_node = '.$id_node);
  return array('delete' => 1, 'html' =>  'Löschen '.($do->delete(DB_DATAOBJECT_WHEREADD_ONLY)===FALSE?'fehlgeschlagen':'erfolgreich'));
}

function deleteInterface($id_interface) {
  $do = DB_DataObject::factory('interface');
  $do->whereAdd('id_interface = '.$id_interface);
  return array('delete' => 1, 'html' =>  'Löschen '.($do->delete(DB_DATAOBJECT_WHEREADD_ONLY)===FALSE?'fehlgeschlagen':'erfolgreich'));
}

function deleteLink($id_link) {
  $do = DB_DataObject::factory('link');
  $do->whereAdd('id_link = '.$id_link);
  return array('delete' => 1, 'html' =>  'Löschen '.($do->delete(DB_DATAOBJECT_WHEREADD_ONLY)===FALSE?'fehlgeschlagen':'erfolgreich'));
}

function deleteRoute($id_route) {
  $do = DB_DataObject::factory('route');
  $do->whereAdd('id_route = '.$id_route);
  return array('html' => 'Löschen '.($do->delete(DB_DATAOBJECT_WHEREADD_ONLY)===FALSE?'fehlgeschlagen':'erfolgreich'));
}

function deleteSubnet($id_subnet) {
  $do = DB_DataObject::factory('subnet');
  $do->whereAdd('id_subnet = '.$id_subnet);
  return array('html' => 'Löschen '.($do->delete(DB_DATAOBJECT_WHEREADD_ONLY)===FALSE?'fehlgeschlagen':'erfolgreich'));
}

function deleteNodeService($id) {
  $do = DB_DataObject::factory('node_has_service');
  $do->whereAdd('id = '.$id);
  /*  return array: delete item in xmltree = true, html output */
  return array('delete' => 1, 'html' => 'Löschen '.($do->delete(DB_DATAOBJECT_WHEREADD_ONLY)===FALSE?'fehlgeschlagen':'erfolgreich'));
}
?>
