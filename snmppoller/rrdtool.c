#include <unistd.h>
#include <sys/types.h>
#include <sys/stat.h>

#include <rrd.h>
#include "snmppoller.h"
#include "rrdtool.h"

#define RRD_ARGV_SIZE 128

#ifdef DEBUG
  #define RRD_PATH "/opt/netplan/trunk/snmppoller/rrd"
#else
  #define RRD_PATH "/opt/netplan/trunk/stats/rrd"
#endif

void rrdCreateHelper(char *rrd_file, tOidList *oidList)
{
    unsigned int i = 0;
    unsigned int o = 0;

	int rrd_argc;
	char **rrd_argv;

	rrd_argc = 9 + oidList->count;
	rrd_argv = malloc((rrd_argc+1) * sizeof(char*));
	for (i=0; i<rrd_argc; i++)
		rrd_argv[i] = malloc(RRD_ARGV_SIZE);

	strncpy(rrd_argv[0], "rrdCreateHelper", RRD_ARGV_SIZE);
	rrd_argv[rrd_argc] = NULL;

	strncpy(rrd_argv[1], rrd_file, RRD_ARGV_SIZE);
	strncpy(rrd_argv[2], "--step=60", RRD_ARGV_SIZE);

    o = 0;
    while (o < oidList->count) {
		char buf[RRD_ARGV_SIZE];
		snprintf(buf, RRD_ARGV_SIZE, "DS:%s:%s:%s:%s:%s", 
			oidList->oid[o].dsName, oidList->oid[o].rrdDst, oidList->oid[o].rrdHeartbeat, 
			oidList->oid[o].rrdMin, oidList->oid[o].rrdMax);
		strncpy(rrd_argv[3+o], buf, RRD_ARGV_SIZE);
        o++;
    }

	strncpy(rrd_argv[3+o], "RRA:AVERAGE:0.5:1:360", RRD_ARGV_SIZE);
	strncpy(rrd_argv[4+o], "RRA:AVERAGE:0.5:2:720", RRD_ARGV_SIZE);
	strncpy(rrd_argv[5+o], "RRA:AVERAGE:0.5:14:720", RRD_ARGV_SIZE);
	strncpy(rrd_argv[6+o], "RRA:AVERAGE:0.5:56:720", RRD_ARGV_SIZE);
	strncpy(rrd_argv[7+o], "RRA:AVERAGE:0.5:728:720", RRD_ARGV_SIZE);
	strncpy(rrd_argv[8+o], "RRA:AVERAGE:0.5:7280:720", RRD_ARGV_SIZE);

	optind = 0;
	opterr = 0;

	rrd_clear_error();
	rrd_create(rrd_argc,rrd_argv);

	if (rrd_test_error()!=0) {
		syslog(LOG_ERR, "rrd_create failed: %s %s", rrd_file, rrd_get_error());
		for (i=0; i<rrd_argc; i++)
			syslog(LOG_ERR, "rrd_argv[%d] = %s", i, rrd_argv[i]);
	}
}

void rrdUpdateHelper(char *rrd_file, tOidList *oidList, unsigned int w)
{
    unsigned int i = 0;
    unsigned int o = 0;

	int rrd_argc;
	char **rrd_argv;

	struct timeval now;
	struct stat rrd_stat;

	if (access(rrd_file, F_OK)!=0) {
		rrdCreateHelper(rrd_file, oidList);
	} else {
		gettimeofday(&now, 0);
		stat(rrd_file, &rrd_stat);
		if (rrd_stat.st_mtime + 10 > now.tv_sec) {
			syslog(LOG_DEBUG, "rrd_update cancled: (minimum one second step)");
			return;
		} 
	}

	rrd_argc = 3;
	rrd_argv = malloc((rrd_argc+1) * sizeof(char*));
	for (i=0; i<rrd_argc; i++)
		rrd_argv[i] = malloc(RRD_ARGV_SIZE);

	strncpy(rrd_argv[0], "rrdUpdateHelper", RRD_ARGV_SIZE);
	rrd_argv[rrd_argc] = NULL;

	strncpy(rrd_argv[1], rrd_file, RRD_ARGV_SIZE);
	strncpy(rrd_argv[2], "N", RRD_ARGV_SIZE);

    o = 0;
    while (o < oidList->count) {
		char buf[RRD_ARGV_SIZE];
		if (oidList->oid[o].doWalk) {
			snprintf(buf, RRD_ARGV_SIZE, ":%s", oidList->oid[o].resultWalk[w]);
		} else {
			snprintf(buf, RRD_ARGV_SIZE, ":%s", oidList->oid[o].result);
		}
		strncat(rrd_argv[2], buf, RRD_ARGV_SIZE);
        o++;
    }

	optind = 0;
	opterr = 0;

	rrd_clear_error();
	rrd_update(rrd_argc,rrd_argv);

	if (rrd_test_error()!=0) {
		syslog(LOG_ERR, "rrd_update failed: %s %s", rrd_file, rrd_get_error());
		for (i=0; i<rrd_argc; i++)
			syslog(LOG_ERR, "rrd_argv[%d] = %s", i, rrd_argv[i]);
	}
}

void rrdUpdate(tInterfaceList *interfaceList)
{
    tRrdList *rrdList;
	tOidList oidList;

	char rrd_file[128];
    unsigned int i = 0;
    unsigned int r = 0;
    unsigned int w = 0;

	oidList.count = 1;

    i = 0;
    while (i < interfaceList->count) {
        syslog(LOG_DEBUG, "rrdtool: %s", interfaceList->interface[i].ip);
        rrdList = &interfaceList->interface[i].rrdList;
        r = 0;
        while (r < rrdList->count) {
            syslog(LOG_DEBUG, "rrdtool: %s: %s %d", interfaceList->interface[i].ip, 
				rrdList->rrd[r].filePrefix, rrdList->rrd[r].doWalk);

			if (rrdList->rrd[r].doWalk) {
				if (strcmp(rrdList->rrd[r].filePrefix,"snr")==0) {
					if (rrdList->rrd[r].oidList.count != 2)
            			syslog(LOG_ERR, "rrdtool: %s: %s oid_count was %d (!=2)", 
							interfaceList->interface[i].ip, rrdList->rrd[r].filePrefix, 
							rrdList->rrd[r].oidList.count);
					tOid *mac = &rrdList->rrd[r].oidList.oid[0];
					tOid *snr = &rrdList->rrd[r].oidList.oid[1];
					if (mac->resultWalkCount != snr->resultWalkCount) {
            			syslog(LOG_ERR, "rrdtool: %s: %s mac_count (%d) != snr_count (%d)", 
							interfaceList->interface[i].ip, rrdList->rrd[r].filePrefix, 
							mac->resultWalkCount, snr->resultWalkCount);
						r++;
						continue;
					}
					w = 0;
					while (w < mac->resultWalkCount) {
						oidList.oid = snr;
						snprintf(rrd_file, sizeof(rrd_file), "%s/%s_%d_%s.rrd", 
							RRD_PATH, rrdList->rrd[r].filePrefix, interfaceList->interface[i].id_node, mac->resultWalk[w]);
						rrdUpdateHelper(rrd_file, &oidList, w);
						w++;
					}
				}
			} else {
				snprintf(rrd_file, sizeof(rrd_file), "%s/%s_%d.rrd", 
					RRD_PATH, rrdList->rrd[r].filePrefix, interfaceList->interface[i].id_interface);
				rrdUpdateHelper(rrd_file, &rrdList->rrd[r].oidList, 0);
			}
            r++;
        }
        i++;
    }
}
