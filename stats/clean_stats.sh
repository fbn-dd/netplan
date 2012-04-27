#!/bin/sh

find /opt/netplan/trunk/stats/png/ -mtime +7 -exec rm {} \;
find /opt/netplan/trunk/stats/rrd/ -mtime +180 -exec rm {} \;
