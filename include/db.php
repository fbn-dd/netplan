<?php

require_once 'DB/DataObject.php';

// the simple examples use parse_ini_file, which is fast and efficient.
// however you could as easily use wddx, xml or your own configuration array.
$config = parse_ini_file(dirname(__FILE__).'/../ini/netplan2.ini',TRUE);
$config['DB_DataObject']['schema_location'] = dirname(__FILE__).'/DataObjects';
$config['DB_DataObject']['class_location']  = dirname(__FILE__).'/DataObjects';

foreach ($config as $class => $values) {
  // this  the code used to load and store DataObjects Configuration. 
  $options =& PEAR::getStaticProperty($class, 'options'); 

  // because PEAR::getstaticProperty was called with and & (get by reference)
  // this will actually set the variable inside that method (a quasi static variable)
  $options = $config[$class];
}

?>
