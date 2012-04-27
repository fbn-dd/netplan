#!/bin/bash


REPO_DIR="/opt/config/trunk/switches"


# get configs

cd ${REPO_DIR}

scp root@192.168.0.12:/cfg/startup-config switch-hp-2810-48g-startup-config.cfg
scp root@192.168.0.12:/cfg/running-config switch-hp-2810-48g-running-config.cfg

scp root@192.168.0.13:/cfg/startup-config switch-hp-2810-24g-startup-config.cfg
scp root@192.168.0.13:/cfg/running-config switch-hp-2810-24g-running-config.cfg

SVN_STATU=$(svn status)
if [ "${SVN_STATUS}" != "" ]; then
  
  echo "${SVN_STATUS}\n\nhttps://www.example.org/netplan/websvn/listing.php?repname=Backups+LANCOM" | mail -s "HP-Switch Config change detected" technik@lists.example.org

  svn ci -m "$(basename) found changes"
fi
