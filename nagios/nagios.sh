#!/bin/sh

NETPLAN=/opt/netplan/trunk
NAGIOS=/etc/nagios3/conf.d

php -f $NETPLAN/nagios/nagios-hosts.php >$NAGIOS/fbn-hosts.cfg.tmp
php -f $NETPLAN/nagios/nagios-hostextinfo.php >$NAGIOS/fbn-hostextinfo.cfg.tmp
php -f $NETPLAN/nagios/nagios-contacts.php >$NAGIOS/fbn-contacts.cfg.tmp
php -f $NETPLAN/nagios/nagios-hostgroups.php >$NAGIOS/fbn-hostgroups.cfg.tmp
php -f $NETPLAN/nagios/nagios-hostgroups-services.php >$NAGIOS/fbn-hostgroups-services.cfg.tmp
# 2009-Aug client service disabled
#php -f $NETPLAN/nagios/nagios-clientservice.php >$NAGIOS/services/wireless_clients_count.cfg.tmp

diff $NAGIOS/fbn-hosts.cfg.tmp $NAGIOS/fbn-hosts.cfg >/dev/null
HOST_DIFF=$?

diff $NAGIOS/fbn-hostextinfo.cfg.tmp $NAGIOS/fbn-hostextinfo.cfg >/dev/null
HOSTEXTINFO_DIFF=$?

diff $NAGIOS/fbn-contacts.cfg.tmp $NAGIOS/fbn-contacts.cfg >/dev/null
CONTACTS_DIFF=$?

diff $NAGIOS/fbn-hostgroups.cfg.tmp $NAGIOS/fbn-hostgroups.cfg >/dev/null
GROUPS_DIFF=$?

diff $NAGIOS/fbn-hostgroups-services.cfg.tmp $NAGIOS/fbn-hostgroups-services.cfg >/dev/null
GROUPS_SERVICES_DIFF=$?

# 2009-Aug client service disabled
#diff $NAGIOS/services/wireless_clients_count.cfg.tmp $NAGIOS/services/wireless_clients_count.cfg >/dev/null
#CLIENTS_DIFF=$?

# 2009-Aug client service disabled
#if (test $HOST_DIFF -ne 0 || test $HOSTEXTINFO_DIFF -ne 0 || test $CONTACTS_DIFF -ne 0 || test $GROUPS_DIFF -ne 0 || test $GROUPS_SERVICES_DIFF -ne 0 || test $CLIENTS_DIFF -ne 0)
if (test $HOST_DIFF -ne 0 || test $HOSTEXTINFO_DIFF -ne 0 || test $CONTACTS_DIFF -ne 0 || test $GROUPS_DIFF -ne 0 || test $GROUPS_SERVICES_DIFF -ne 0)
then
  # neue Nagios Konfigurations Files neu einlesen lassen
  mv $NAGIOS/fbn-hosts.cfg.tmp $NAGIOS/fbn-hosts.cfg
  mv $NAGIOS/fbn-hostextinfo.cfg.tmp $NAGIOS/fbn-hostextinfo.cfg
  mv $NAGIOS/fbn-contacts.cfg.tmp $NAGIOS/fbn-contacts.cfg
  mv $NAGIOS/fbn-hostgroups.cfg.tmp $NAGIOS/fbn-hostgroups.cfg
  mv $NAGIOS/fbn-hostgroups-services.cfg.tmp $NAGIOS/fbn-hostgroups-services.cfg
# 2009-Aug client service disabled
#  mv $NAGIOS/services/wireless_clients_count.cfg.tmp $NAGIOS/services/wireless_clients_count.cfg

  # make sure nagios can red config files
  chgrp nagios $NAGIOS/*
  # reload config
  /etc/init.d/nagios3 reload >/dev/null 
fi 
