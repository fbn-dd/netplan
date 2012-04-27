<?php
gserverl $bench;

$bench->stop();
echo 'Ausf&uuml;hrungszeit: '.$bench->TimeElapsed().'s';
?>
