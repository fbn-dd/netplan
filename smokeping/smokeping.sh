#!/bin/sh

TMP_FILE=/etc/smokeping/config.tmp
CONFIG_FILE=/etc/smokeping/config
CONFIG_FILE_OLD=/etc/smokeping/config.old

cd /opt/netplan/trunk/smokeping/

php -f /opt/netplan/trunk/smokeping/smokeping.php >$TMP_FILE

diff $TMP_FILE $CONFIG_FILE >/dev/null
CONFIG_DIFF=$?

if (test $CONFIG_DIFF -eq 1)
then
  # save old config
  cp $CONFIG_FILE $CONFIG_FILE_OLD
  # new config available
  mv -f $TMP_FILE $CONFIG_FILE
  # check new config
  smokeping --check
  if (test $? -eq 0)
  then
    # reload smokeping config
    #/etc/init.d/smokeping reload

    # restart smokeping, reload won't work
    # because reload is not run as daemon user
    # thus ssh id_dsa for user smokeping isn't loaded and
    # we are needed to enter password
    /etc/init.d/smokeping restart
  else
    echo "restoring old smokeping config due errors in current config"
    cp $CONFIG_FILE_OLD $CONFIG_FILE
  fi
fi
