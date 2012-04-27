<?php

require_once(dirname(__FILE__) . '/edit.php');       // edit Forms fuer alle Objekte
require_once(dirname(__FILE__) . '/delete.inc.php'); // Loeschen aller Objekte
require_once(dirname(__FILE__) . '/update.inc.php'); // Insert und Update alle Objekte

class remoteedit {

  function onedit($id) {
    $array =  explode('_',$id);
    if (isset($array[0])) $key = $array[0];
    if (isset($array[1])) $value = $array[1];
    if (isset($array[2])) {
     $parent = $array[2];
    } else {
     $parent = '';
    }
    switch ($key) {
      case 'section':     return editSection($value, $parent);
      case 'location':    return editLocation($value, $parent);
      case 'node':        return editNode($value, $parent);
      case 'interface':   return editInterface($value, $parent);
      case 'link':        return editLink($value, $parent);
      case 'nodeService': return editNodeService($value, $parent);
    }
  }

  function onupdate($key, $value) {
    switch ($key) {
      case 'section':     return updateSection($value);
      case 'location':    return updateLocation($value);
      case 'node':        return updateNode($value);
      case 'interface':   return updateInterface($value);
      case 'link':        return updateLink($value);
      case 'route':       return updateRoute($value);
      case 'nodeService': return updateNodeService($value);
      case 'iftable':	  return updateIfid($value);
    }
  }

  function ondelete($key, $value) {
    switch ($key) {
      case 'section':     return deleteSection($value);
      case 'location':    return deleteLocation($value);
      case 'node':        return deleteNode($value);
      case 'interface':   return deleteInterface($value);
      case 'link':        return deleteLink($value);
      case 'route':       return deleteRoute($value);
      case 'nodeService': return deleteNodeService($value);
    }
  }
}

?>
