<?php

require_once(dirname(__FILE__) . "/../include/db.php");

/*
# A list of your web servers
define hostgroup {
	hostgroup_name	http-servers
	alias		HTTP servers
	members		localhost,Eris,Kallisto,Intranet,otrs,Europa,Chaski
}
*/

/* node mapping */
$node = DB_DataObject::factory('node');
$node->find();
$nodeMapping = array();
while ($node->fetch())
{
  if (!empty($node->description))
    $nodeMapping[$node->id_node] = $node->description;
}

/* service mapping */
$service = DB_DataObject::factory('service');
$service->find();
$serviceMapping = array();
while ($service->fetch())
{
  // service description empty? -> service value as description
  $service->description?$service->description:'Service '.$service->value;
  if (!empty($service->description) AND isset($service->id_service))
    $serviceMapping[$service->id_service] = array(
      'value' => $service->value,
      'description' => $service->description
    );
}

/* node_has_service mapping */
$nhs = DB_DataObject::factory('node_has_service');
$nhs->find();
$nhsMapping = array();
while ($nhs->fetch())
{
  $nhsMapping[$nhs->id_service][] = $nodeMapping[$nhs->id_node];
}
?>
# this file is dynamically created and links hostgroups to static services
# see /etc/nagios3/conf.d/services_nagios3.cfg for services
# "services-<value>" needs an corresponding entry for <value> in services table in database

<?php
foreach ($serviceMapping as $id_service => $serviceArray) {
?>
define hostgroup{
	hostgroup_name	service-<?php echo $serviceArray['value']; ?>

	alias		<?php echo $serviceArray['description']; ?>

<?php if ( isset($nhsMapping[$id_service]) and count($nhsMapping[$id_service]) > 0 ) { ?>
	members		<?php echo implode(",", $nhsMapping[$id_service]) ?>
<?php } ?>

}


<?php
}
?>
