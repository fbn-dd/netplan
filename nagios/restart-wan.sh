#!/bin/sh
#
# Event handler script for restarting a service at a server
#
# Note: This script will only restart the service if the service is
#       retried 3 times (in a "soft" state) or if the service somehow
#       manages to fall into a "hard" error state.
#
# docs: http://nagios.sourceforge.net/docs/3_0/eventhandlers.html
#

# check input parameters
if [ $# -ne 5 ]
then
	echo "Das Skript erwartet folgende Parameter: servicestate, servicestatetype, serviceattempt, server, service description. Mindestens ein Parameter fehlt."
	exit 1;
fi
# input paramters
SERVICESTATE=$1
SERVICESTATETYPE=$2
SERVICEATTEMPT=$3
SERVER=$4
SERVICE=ppp
SERVICEDESC=$5
#RESTARTCMD="/usr/bin/ssh -o BatchMode=yes -o StrictHostKeyChecking=no root@$SERVER /etc/init.d/$SERVICE restart"
RESTARTCMD="echo irgendwas machen"
MAILTARGET="whatever@example.org"

# What state is the service in?
case "$SERVICESTATE" in
OK)
	# The service just came back up
### TODO: Leitung in Multipath-Tabelle aufnehmen und statische Zuordnung wieder herstellen
	# send notification mail
        #echo "Die $SERVICEDESC an $SERVER ist wieder online. Sie wurde in den Regelbetrieb aufgenommen." | mail -s "** RECOVERY $SERVICEDESC" $MAILTARGET
	;;
WARNING)
	# We don't really care about warning states, since the service is probably still running...
        #echo "Nagios hat bei $SERVICEDESC an $SERVER einen Fehler festgestellt." | mail -s "** WARNING $SERVICEDESC" $MAILTARGET
	;;
UNKNOWN)
	# We don't know what might be causing an unknown error, so don't do anything...
        #echo "Nagios hat bei $SERVICEDESC an $SERVER einen Fehler festgestellt." | mail -s "** UNKNOWN $SERVICEDESC" $MAILTARGET
	;;
CRITICAL)
	# Aha! The service appears to have a problem - perhaps we should restart the server...

	# Is this a "soft" or a "hard" state?
	case "$SERVICESTATETYPE" in
		
	# We're in a "soft" state, meaning that Nagios is in the middle of retrying the
	# check before it turns into a "hard" state and contacts get notified...
	SOFT)
			
		# What check attempt are we on?  We don't want to restart the service on the first
		# check, because it may just be a fluke!
		case "$SERVICEATTEMPT" in
				
		# Wait until the check has been tried 3 times before restarting the service.
		# If the check fails on the 4th time (after we restart the service), the state
		# type will turn to "hard" and contacts will be notified of the problem.
		# Hopefully this will restart the service successfully, so the 4th check will
		# result in a "soft" recovery. If that happens no one gets notified because we
		# fixed the problem!
		3)
			echo -n "Restarting $SERVICE (3rd soft critical state) at $SERVER..."
### TODO: Leitung aus Multipath-Tabelle nehmen und statische Zuordnung entfernen
			# send notification mail
			#echo "Nagios hat einen Ausfall von $SERVICEDESC an $SERVER festgestellt. Sie wurde aus dem Regelbetrieb entfernt." | mail -s "** PROBLEM $SERVICEDESC" $MAILTARGET
			;;
			esac
		;;
				
	# The service somehow managed to turn into a hard error without getting fixed.
	# It should have been restarted by the code above, but for some reason it didn't.
	# Let's give it one last try, shall we?  
	# Note: Contacts have already been notified of a problem with the service at this
	# point (unless you disabled notifications for this service)
	HARD)
		echo -n "Restarting $SERVICE (hard critical state) at $SERVER..."
		# send notification mail
		#echo "Nagios hat einen Ausfall von $SERVICEDESC an $SERVER festgestellt. Sie wird wieder in Betrieb genommen, sobald sie online ist." | mail -s "** PROBLEM $SERVICEDESC" $MAILTARGET
		;;
	esac
	;;
esac

exit 0
