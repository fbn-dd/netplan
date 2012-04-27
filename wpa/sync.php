<?php
require_once(dirname(__FILE__) . "/../include/db.php");

// loeschen
    $radclean = DB_DataObject::factory('radreply');
    //$radclean->query("TRUNCATE {$radclean->__table}"); // funktioniert nicht mit PEAR MDB2 v2.5.0b3
    $radclean->query("DELETE FROM {$radclean->__table}");
    $radclean->find(TRUE);
// auslesen
    $radreply = DB_DataObject::factory('radius_reply');
    $radreply->find();
    while ($radreply->fetch())
    {
    // einfuegen
        $radreply_new = DB_DataObject::factory('radreply');
        $radreply_new->UserName  = $radreply->UserName;
        $radreply_new->Attribute = $radreply->Attribute;
        $radreply_new->op        = $radreply->op;
        $radreply_new->Value     = $radreply->Value;
        $radreply_new->insert();
		unset($radreply_new);
    }
?>
