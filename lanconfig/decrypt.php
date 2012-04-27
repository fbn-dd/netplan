<?php
header('Content-Type: text/plain');
//header('Content-Disposition: attachment; filename="lanconf.ini"');

require_once(dirname(__FILE__) . "/../include/lancom.php");

$secret = "Knuddelbaerchen";


echo decodeLPW($_GET["pw"], $secret);

?>
