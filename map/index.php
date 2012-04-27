<?php
/* GET parameter maponly -> do not show menu */
$maponly = ( (isset($_GET['maponly']) && $_GET['maponly']==true) ? true : false );

require_once(dirname(__FILE__) . "/../include/map.php");

googleMapHeader('AP-Karte');
googleMapViewJs();
if ($maponly)
{
    googleMapBody();
    exit;
}
googleMapSearchBody();

?>
