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
SERVICE=freeradius
SERVICEDESC=$5
RESTARTCMD="/usr/bin/ssh -o BatchMode=yes -o StrictHostKeyChecking=no root@$SERVER /etc/init.d/$SERVICE restart"

# What state is the service in?
case "$SERVICESTATE" in
OK)
	# The service just came back up, so don't do anything...
	# but send notification mail
        #echo "Nagios hat den Dienst $SERVICEDESC auf $SERVER erfolgreich neugestartet." | mail -s "** RECOVERY Service-Neustart: $SERVICEDESC" monitoring@lists.example.org
	;;
WARNING)
	# We don't really care about warning states, since the service is probably still running...
	;;
UNKNOWN)
	# We don't know what might be causing an unknown error, so don't do anything...
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
			# Call the init script to restart the service
			$RESTARTCMD
			# send notification mail
			#echo "Nagios hat einen Ausfall des Dienstes $SERVICEDESC auf $SERVER festgestellt und versucht jetzt diesen Dienst neuzustarten." | mail -s "** PROBLEM Service-Ausfall: $SERVICEDESC" monitoring@lists.example.org
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
		# Call the init script to restart the service
		$RESTARTCMD
		# send notification mail
		echo "Nagios hat einen Ausfall des Dienstes $SERVICEDESC auf $SERVER festgestellt und alle Versuche diesen Dienst neuzustarten schlugen fehl." | mail -s "** PROBLEM Service-Ausfall: $SERVICEDESC" monitoring@lists.example.org	
		;;
	esac
	;;
esac

exit 0
