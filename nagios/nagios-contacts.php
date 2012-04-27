<?php

require_once(dirname(__FILE__) . "/../include/db.php");

$Gruppen = DB_DataObject::factory('Gruppen');
$Gruppen->whereAdd("gruppe in ('nagios-admin')");
$Gruppen->find();
$groupMapping=array();
while ($Gruppen->fetch()) {
  if (!isset($groupMapping[$Gruppen->BNID])) $groupMapping[$Gruppen->BNID] = array();
  array_push($groupMapping[$Gruppen->BNID], strtolower($Gruppen->Gruppe));
}

$Kommunikation = DB_DataObject::factory('Kommunikation');
$Kommunikation->whereAdd("BNID in (".implode(',',array_keys($groupMapping)).") AND  TKommunikation='email'");
$Kommunikation->find();
$emailMapping = array();
while ($Kommunikation->fetch()) {
  $emailMapping[$Kommunikation->BNID] = $Kommunikation->Value;
}

$Mitglieder = DB_DataObject::factory('Mitglieder');
$Mitglieder->whereAdd("BNID in (".implode(',',array_keys($groupMapping)).")");
$Mitglieder->find();
?>

#
# contacts and contactgroups
#

# This is an example of a single contact who will receive all alerts.
#define contact{
#        contact_name                    root
#        alias                           Root
#        service_notification_period     24x7
#        host_notification_period        24x7
#        service_notification_options    w,u,c,r
#        host_notification_options       d,r
#        service_notification_commands   notify-service-by-email
#        host_notification_commands      notify-host-by-email
#        email                           root@localhost
#        }

# Vorlage
define contact{
        name                            generic-contact
        register                        0
        host_notifications_enabled      1
        service_notifications_enabled   1
        host_notification_period        24x7
        service_notification_period     24x7
        host_notification_options       d,r
        service_notification_options    w,u,c,r
        host_notification_commands      notify-host-by-email
        service_notification_commands   notify-service-by-email
}

# irc bot
define contact{
	use				generic-contact
	contact_name			NagioServ IRC-Bot
	contactgroups			nagios-bots
	host_notification_commands	notify-host-by-irc
	service_notification_commands	notify-service-by-irc
}

# Mailingliste
define contact{
	use		generic-contact
	contact_name	Monitoring-Mailingliste
	email		monitoring@lists.example.org
	contactgroups	nagios-admin
}

define contactgroup{
        contactgroup_name       nagios-admin
        alias                   Nagios-Administratoren
}

define contactgroup{
        contactgroup_name       nagios-bots
        alias                   Bots fuer Benachrichtigungen
}

# Nutzer mit Admin-Rechten im Nagios
<?php while ($Mitglieder->fetch()): ?>
define contact{
	use		generic-contact
	contact_name	<?php echo $Mitglieder->Username."\n"; ?>
	alias		<?php echo $Mitglieder->Vorname." ".$Mitglieder->Nachname."\n"; ?>
	email		<?php echo $emailMapping[$Mitglieder->BNID]."\n"; ?>
	contactgroups	<?php echo implode(',', $groupMapping[$Mitglieder->BNID])."\n"; ?>
	# do not notify, let them use our mailinglist
	host_notifications_enabled	0
	service_notifications_enabled	0
}

<?php endwhile; ?>
