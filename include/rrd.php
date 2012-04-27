<?php

define('RRD_DIR',  dirname(__FILE__) . '/../stats/rrd');
define('PNG_DIR',  dirname(__FILE__) . '/../stats/png');

function rrd_create_traffic($rrd) {
  $cmd = 'rrdtool create '.RRD_DIR.'/'.$rrd.
         ' --step 60'.
         ' DS:traffic_in:COUNTER:120:0:4294967295'.
         ' DS:traffic_out:COUNTER:120:0:4294967295'.
         ' RRA:AVERAGE:0.5:1:360'.
         ' RRA:AVERAGE:0.5:2:720'.
         ' RRA:AVERAGE:0.5:14:720'.
         ' RRA:AVERAGE:0.5:56:720'.
         ' RRA:AVERAGE:0.5:728:720'.
         ' RRA:AVERAGE:0.5:7280:720';
  exec($cmd);
}

function rrd_create_signal($rrd) {
  $cmd = 'rrdtool create '.RRD_DIR.'/'.$rrd.
         ' --step 60'.
         ' DS:link-signal:GAUGE:120:0:100'.
         ' DS:rx-phy-signal:GAUGE:120:0:100'.
         ' RRA:AVERAGE:0.5:1:360'.
         ' RRA:AVERAGE:0.5:2:720'.
         ' RRA:AVERAGE:0.5:14:720'.
         ' RRA:AVERAGE:0.5:56:720'.
         ' RRA:AVERAGE:0.5:728:720'.
         ' RRA:AVERAGE:0.5:7280:720';
  exec($cmd);
}

function rrd_create_rxerrors($rrd) {
  $cmd = 'rrdtool create '.RRD_DIR.'/'.$rrd.
         ' --step 60'.
         ' DS:rx-errors:COUNTER:120:0:4294967295'.
         ' RRA:AVERAGE:0.5:1:360'.
         ' RRA:AVERAGE:0.5:2:720'.
         ' RRA:AVERAGE:0.5:14:720'.
         ' RRA:AVERAGE:0.5:56:720'.
         ' RRA:AVERAGE:0.5:728:720'.
         ' RRA:AVERAGE:0.5:7280:720';
  exec($cmd);
}

function rrd_create_generic($rrd, $oidtypes, $data)
{
  foreach ($oidtypes as $index => $values)
  {
    $ds[] = "DS:".$values["ds_name"].":".$values["rrd_dst"].":".$values["rrd_heartbeat"].":".$values["rrd_min"].":".$values["rrd_max"];
  }

  $cmd = 'rrdtool create '.RRD_DIR.'/'.$rrd.
         ' --step 60'.
         ' '.implode(" ",$ds).
         ' RRA:AVERAGE:0.5:1:360'.
         ' RRA:AVERAGE:0.5:2:720'.
         ' RRA:AVERAGE:0.5:14:720'.
         ' RRA:AVERAGE:0.5:56:720'.
         ' RRA:AVERAGE:0.5:728:720'.
         ' RRA:AVERAGE:0.5:7280:720';
  exec($cmd);
}

function rrd_update_generic($rrd, $oidtype, $data = NULL)
{
  if (!file_exists((RRD_DIR.'/'.$rrd)))
    rrd_create_generic($rrd, $oidtype, $data);

  if ($data)
  {
    $cmd = 'rrdtool update '.RRD_DIR.'/'.$rrd.
           ' N:'.implode(':',$data);
    exec($cmd);
  }
}

function rrd_update_traffic($rrd,$data=array(0,0)) {
  if (!file_exists(RRD_DIR.'/'.$rrd)) 
    rrd_create_traffic($rrd);

  $cmd = 'rrdtool update '.RRD_DIR.'/'.$rrd.
         ' N:'.implode(':',$data);
  exec($cmd);
}

function rrd_update_signal($rrd,$data=array(0,0)) {
  if (!file_exists(RRD_DIR.'/'.$rrd)) 
    rrd_create_signal($rrd);

  $cmd = 'rrdtool update '.RRD_DIR.'/'.$rrd.
         ' N:'.implode(':',$data);
  exec($cmd);
}

function rrd_update_rxerrors($rrd,$data=array(0)) {
  if (!file_exists(RRD_DIR.'/'.$rrd)) 
    rrd_create_rxerrors($rrd);

  $cmd = 'rrdtool update '.RRD_DIR.'/'.$rrd.
         ' N:'.implode(':',$data);
  exec($cmd);
}

function rrd_graph_traffic($rrd,$png,$range,$title) {
  if (file_exists(PNG_DIR.'/'.$png) && filemtime(PNG_DIR.'/'.$png)+floor($range/60)>time()) return TRUE;
  $cmd = 'rrdtool graph '.PNG_DIR.'/'.$png.' -c BACK#fefaf1 -s -'.$range.' -t "'.$title.'"'.
         ' -R normal -v "Traffic in Byte/s" -w 300 -h 96'.
         ' DEF:traffic_in='.RRD_DIR.'/'.$rrd.':traffic_in:AVERAGE'.
         ' DEF:traffic_out='.RRD_DIR.'/'.$rrd.':traffic_out:AVERAGE'.
         ' CDEF:traffic_in_lm=traffic_in,0,100000000,LIMIT'.
         ' CDEF:traffic_out_lm=traffic_out,0,100000000,LIMIT'.
         ' CDEF:traffic_in_kb=traffic_in_lm,1024,/'.
         ' CDEF:traffic_out_kb=traffic_out_lm,1024,/'.
         ' AREA:traffic_in_lm#00cf00:"In  "'.
         ' GPRINT:traffic_in_kb:LAST:"Cur\:%7.2lfK"'.
         ' GPRINT:traffic_in_kb:AVERAGE:"Avg\:%7.2lfK"'.
         ' GPRINT:traffic_in_kb:MAX:"Max\:%7.2lfK\n"'.
         ' LINE1:traffic_out_lm#002a97:"Out"'.
         ' GPRINT:traffic_out_kb:LAST:"Cur\:%7.2lfK"'.
         ' GPRINT:traffic_out_kb:AVERAGE:"Avg\:%7.2lfK"'.
         ' GPRINT:traffic_out_kb:MAX:"Max\:%7.2lfK"';
  exec($cmd);
}

function rrd_graph_channel($rrd,$png,$range,$title) {
  if (file_exists(PNG_DIR.'/'.$png) && filemtime(PNG_DIR.'/'.$png)+floor($range/60)>time()) return TRUE;
  $cmd = 'rrdtool graph '.PNG_DIR.'/'.$png.' -c BACK#fefaf1 -s -'.$range.' -t "'.$title.'"'.
         ' -R normal -v "Funkkanal" -w 300 -h 96 --lower-limit 0 --upper-limit 60'.
         ' DEF:channel='.RRD_DIR.'/'.$rrd.':channel:AVERAGE'.
         ' LINE1:channel#6557d0:"Kanal"'.
         ' GPRINT:channel:LAST:"Cur\:%3.0lf"'.
	 ' COMMENT:" \n"';
  exec($cmd);
}

function rrd_graph_signal($rrd,$png,$range,$title) {
  if (file_exists(PNG_DIR.'/'.$png) && filemtime(PNG_DIR.'/'.$png)+floor($range/60)>time()) return TRUE;
  $cmd = 'rrdtool graph '.PNG_DIR.'/'.$png.' -c BACK#fefaf1 -s -'.$range.' -t "'.$title.'"'.
         ' -R normal -v "Signalstaerke" -w 300 -h 96 --lower-limit 0 --upper-limit 60'.
         ' DEF:link-signal='.RRD_DIR.'/'.$rrd.':link-signal:AVERAGE'.
         ' DEF:rx-phy-signal='.RRD_DIR.'/'.$rrd.':rx-phy-signal:AVERAGE'.
         ' AREA:link-signal#ff4105:"Link   "'.
         ' GPRINT:link-signal:LAST:"Cur\:%7.2lf"'.
         ' GPRINT:link-signal:AVERAGE:"Avg\:%7.2lf"'.
         ' GPRINT:link-signal:MAX:"Max\:%7.2lf\n"'.
         ' LINE1:rx-phy-signal#6557d0:"Rx-Phy"'.
         ' GPRINT:rx-phy-signal:LAST:"Cur\:%7.2lf"'.
         ' GPRINT:rx-phy-signal:AVERAGE:"Avg\:%7.2lf"'.
         ' GPRINT:rx-phy-signal:MAX:"Max\:%7.2lf"';
  exec($cmd);
}

function rrd_graph_rxerrors($rrd,$png,$range,$title) {
  if (file_exists(PNG_DIR.'/'.$png) && filemtime(PNG_DIR.'/'.$png)+floor($range/60)>time()) return TRUE;
  $cmd = 'rrdtool graph '.PNG_DIR.'/'.$png.' -c BACK#fefaf1 -s -'.$range.' -t "'.$title.'"'.
         ' -R normal -v "Empfangsfehler" -w 300 -h 96'.
         ' DEF:rx-errors='.RRD_DIR.'/'.$rrd.':rx-errors:AVERAGE'.
         ' AREA:rx-errors#ff4105:"Empfangsfehler"'.
         ' GPRINT:rx-errors:LAST:"Cur\:%5.1lf"'.
         ' GPRINT:rx-errors:AVERAGE:"Avg\:%5.1lf"'.
         ' GPRINT:rx-errors:MAX:"Max\:%5.1lf\n"'.
         ' COMMENT:" \n"';
  exec($cmd);
}

function rrd_graph_noise($rrd,$png,$range,$title) {
  if (file_exists(PNG_DIR.'/'.$png) && filemtime(PNG_DIR.'/'.$png)+floor($range/60)>time()) return TRUE;
  $cmd = 'rrdtool graph '.PNG_DIR.'/'.$png.' -c BACK#fefaf1 -s -'.$range.' -t "'.$title.'"'.
         ' -R normal -v "Rauschen in dB" -w 300 -h 96 --lower-limit -105 --upper-limit -70 --rigid'.
         ' DEF:noise='.RRD_DIR.'/'.$rrd.':noise:AVERAGE'.
         ' AREA:noise#6557d0:"Rauschen in dB"'.
         ' GPRINT:noise:LAST:"Cur\:%5.1lf"'.
         ' GPRINT:noise:AVERAGE:"Avg\:%5.1lf"'.
         ' GPRINT:noise:MAX:"Max\:%5.1lf\n"'.
         ' COMMENT:" \n"';
  exec($cmd);
}

function rrd_graph_chload($rrd,$png,$range,$title) {
  if (file_exists(PNG_DIR.'/'.$png) && filemtime(PNG_DIR.'/'.$png)+floor($range/60)>time()) return TRUE;
  $cmd = 'rrdtool graph '.PNG_DIR.'/'.$png.' -c BACK#fefaf1 -s -'.$range.' -t "'.$title.'"'.
         ' -R normal -v "Kanallast in %" -w 300 -h 96 --lower-limit 0 --upper-limit 100 --rigid'.
         ' DEF:chload='.RRD_DIR.'/'.$rrd.':chload:AVERAGE'.
         ' AREA:chload#6557d0:"Kanallast in %"'.
         ' GPRINT:chload:LAST:"Cur\:%5.1lf"'.
         ' GPRINT:chload:AVERAGE:"Avg\:%5.1lf"'.
         ' GPRINT:chload:MAX:"Max\:%5.1lf\n"'.
         ' COMMENT:" \n"';
  exec($cmd);
}

function rrd_graph_snr($rrd,$png,$range,$title) {
  if (file_exists(PNG_DIR.'/'.$png) && filemtime(PNG_DIR.'/'.$png)+floor($range/60)>time()) return TRUE;
  $cmd = 'rrdtool graph '.PNG_DIR.'/'.$png.' -c BACK#fefaf1 -s -'.$range.' -t "'.$title.'"'.
         ' -R normal -v "Signal-Rausch-Abstand" -w 300 -h 96 --lower-limit 0 --upper-limit 20'.
         ' DEF:snr='.RRD_DIR.'/'.$rrd.':snr:AVERAGE'.
         ' AREA:snr#ff4105:"SNR"'.
         ' GPRINT:snr:LAST:"Cur\:%5.1lfdB"'.
         ' GPRINT:snr:AVERAGE:"Avg\:%5.1lfdB"'.
         ' GPRINT:snr:MAX:"Max\:%5.1lfdB\n"';
  exec($cmd);
}

function rrd_graph_mac($rrd,$png,$range,$title) {
  if (file_exists(PNG_DIR.'/'.$png) && filemtime(PNG_DIR.'/'.$png)+floor($range/60)>time()) return TRUE;
  $cmd = 'rrdtool graph '.PNG_DIR.'/'.$png.' -c BACK#fefaf1 -s -'.$range.' -t "'.$title.'"'.
         ' -R normal -v "Clients" -w 300 -h 96'.
         ' DEF:mac='.RRD_DIR.'/'.$rrd.':mac:AVERAGE'.
         ' AREA:mac#ff4105:"Client(s) "'.
         ' GPRINT:mac:LAST:"Cur\:%5.1lf"'.
         ' GPRINT:mac:AVERAGE:"Avg\:%5.1lf"'.
         ' GPRINT:mac:MAX:"Max\:%5.1lf\n"';
  exec($cmd);
}

function rrd_graph_internet_summary($interface, $range) {
  if (file_exists(PNG_DIR.'/'.'traffic_summary-'.$range.'.png') && filemtime(PNG_DIR.'/'.'traffic_summary-'.$range.'.png')+floor($range/60)>time()) return TRUE;
  
  $add_def = array();
  $add_cdef_in = array();
  $add_cdef_out = array();
  $add_cdef_op = array();
  
  foreach ($interface as $id => $description) {
    $add_def[] = 'DEF:router_out_'.$id.'='.RRD_DIR.'/id_interface_'.$id.'.rrd:traffic_out:AVERAGE';
    $add_def[] = 'DEF:router_in_'.$id.'='.RRD_DIR.'/id_interface_'.$id.'.rrd:traffic_in:AVERAGE';
    $add_cdef_in[] = 'router_in_'.$id;
    $add_cdef_out[] = 'router_out_'.$id;
    $add_cdef_op[] = 'ADDNAN';
  }
  array_pop($add_cdef_op);

  $cmd = 'rrdtool graph '.PNG_DIR.'/traffic_summary-'.$range.'.png -c BACK#fefaf1 -s -'.$range.' -t "Trafficsumme"'.
         ' -R normal -v "Traffic in Byte/s" -w 300 -h 96 '.
         implode(" ", $add_def).
         ' CDEF:in_all='.implode(",", $add_cdef_in).','.implode(",", $add_cdef_op).',0,13000000,LIMIT'.
         ' CDEF:out_all='.implode(",", $add_cdef_out).','.implode(",", $add_cdef_op).',0,13000000,LIMIT'.
         ' CDEF:in_kb=in_all,1024,/ CDEF:out_kb=out_all,1024,/'.
         ' AREA:in_all#00cf00:"In "'.
         ' GPRINT:in_kb:LAST:"Cur\:%7.2lfK"'.
         ' GPRINT:in_kb:AVERAGE:"Avg\:%7.2lfK"'.
         ' GPRINT:in_kb:MAX:"Max\:%7.2lfK\n"'.
         ' LINE1:out_all#002a97:"Out"'.
         ' GPRINT:out_kb:LAST:"Cur\:%7.2lfK"'.
         ' GPRINT:out_kb:AVERAGE:"Avg\:%7.2lfK"'.         
         ' GPRINT:out_kb:MAX:"Max\:%7.2lfK"';
  exec($cmd);
}
?>
