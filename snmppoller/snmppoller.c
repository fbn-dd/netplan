#include "mysql.h"
#include "snmp.h"
#include "rrdtool.h"
#include "snmppoller.h"
#include <libgen.h>

int main (int argc, char **argv)
{
	MYSQL mysql;
	tInterfaceList interfaceList;
	tRrdList *rrdList = NULL;
	tOidList *oidList = NULL;

    unsigned int i = 0;
   	unsigned int r = 0;
   	unsigned int o = 0;
   	unsigned int w = 0;

	openlog(basename(argv[0]), LOG_PID, LOG_USER);

#ifdef DEBUG
	setlogmask(LOG_UPTO(LOG_DEBUG));
	syslog(LOG_INFO, "started %s (debug) ...", basename(argv[0]));
#else
	setlogmask(LOG_UPTO(LOG_INFO));
	syslog(LOG_INFO, "started %s (production) ...", basename(argv[0]));
#endif

	dbConnect(&mysql);
	dbGetInterfaceList(&mysql, &interfaceList);
	dbClose(&mysql);

	i = 0;
	while (i < interfaceList.count) {
		syslog(LOG_DEBUG, "mysql: %s", interfaceList.interface[i].ip);
        rrdList = &interfaceList.interface[i].rrdList;
    	r = 0;
		while (r < rrdList->count) {
			syslog(LOG_DEBUG, "mysql: * %s", rrdList->rrd[r].filePrefix);
        	oidList = &rrdList->rrd[r].oidList;
    		o = 0;
			while (o < oidList->count) {
				syslog(LOG_DEBUG, "mysql: * * %s (%s)", oidList->oid[o].oidName, oidList->oid[o].dsName);
				o++;
			}
			r++;
		}
		i++;
	}  

	// allocate session memory
	sessions = malloc(interfaceList.count * sizeof(tSession));

	// snmp stuff
	snmpInitialize(&interfaceList);
	snmpAsynchronous(&interfaceList);

    i = 0;
	while (i < interfaceList.count) {
		syslog(LOG_DEBUG, "snmp: %s", interfaceList.interface[i].ip);
        rrdList = &interfaceList.interface[i].rrdList;
    	r = 0;
		while (r < rrdList->count) {
        	oidList = &rrdList->rrd[r].oidList;
			syslog(LOG_DEBUG, "snmp: * %s (%d)", rrdList->rrd[r].filePrefix, oidList->count);
    		o = 0;
			while (o < oidList->count) {
				if (oidList->oid[o].doWalk) {
					w = 0;
					syslog(LOG_DEBUG, "snmp: * * walk (%d)", oidList->oid[o].resultWalkCount);
					while (w < oidList->oid[o].resultWalkCount) {
						syslog(LOG_DEBUG, "snmp: * * %s = %s (%s)", oidList->oid[o].oidName, oidList->oid[o].resultWalk[w], oidList->oid[o].dsName);
						w++;
					}
				} else {
					syslog(LOG_DEBUG, "snmp: * * %s = %s (%s)", oidList->oid[o].oidName, oidList->oid[o].result, oidList->oid[o].dsName);
				}
				o++;
			}
			r++;
		}
		i++;
	}

	// rrd stuff
	rrdUpdate(&interfaceList);

	// cleanup
    i = 0;
	while (i < interfaceList.count) {
		syslog(LOG_DEBUG, "free: %s", interfaceList.interface[i].ip);
        oidList = &interfaceList.interface[i].oidList;
        rrdList = &interfaceList.interface[i].rrdList;
		o = 0;
		while (o < oidList->count) {
			if (oidList->oid[o].result != NULL) 
				free(oidList->oid[o].result);
			if (oidList->oid[o].resultWalk != NULL) {
				free(*oidList->oid[o].resultWalk);
				free(oidList->oid[o].resultWalk);
			}
			o++;
		}
		free(oidList->oid);
		free(rrdList->rrd);
		i++;
	}  
	free(interfaceList.interface);

	syslog(LOG_INFO, "finished %s ...", basename(argv[0]));
	closelog();

	return 0;
}
