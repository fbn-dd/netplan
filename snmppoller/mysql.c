#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <mysql/mysql.h>
#include "mysql.h"

int dbConnect(MYSQL *mysql)
{
	mysql_init(mysql);
	mysql_options(mysql,MYSQL_READ_DEFAULT_GROUP,"statsd");
	if (!mysql_real_connect(mysql,"dbserver","netplan","xxxxxxxxxxxxx","netplan",0,NULL,0)) {
    	syslog(LOG_ERR, "Failed to connect to database: Error: %s", mysql_error(mysql));
        return 1;
	}
    return 0;
}

int dbClose(MYSQL *mysql)
{
	mysql_close(mysql);
    return 0;
}

int dbQuery(MYSQL *mysql, char *query, MYSQL_RES **result)
{

    if (mysql_real_query(mysql, query, strlen(query))) {
    	syslog(LOG_ERR, "Failed to query database: Error: %s", mysql_error(mysql));
        return 1;
	}

    *result = mysql_store_result(mysql);
    return 0;
}

int dbGetInterfaceList(MYSQL *mysql, tInterfaceList *list)
{
	MYSQL_RES *result;
	MYSQL_ROW row;

    char *query = "\
		SELECT \
			i.id_interface, \
			i.id_device, \
			i.ip, \
			n.snmp_community, \
			o.id_oidtype, \
			o.oid, \
			t.file_prefix, \
			t.ds_name, \
			t.rrd_dst, \
			t.rrd_heartbeat, \
			t.rrd_min, \
			t.rrd_max, \
			t.dowalk, \
			i.id_node, \
			o.id_type, \
			i.oid_override, \
			i.oid_ifid \
		FROM interface i, node n, oid o, oidtype t \
		WHERE \
			i.id_node=n.id_node AND \
			i.id_device=o.id_device AND \
			n.id_type=o.id_type AND \
			o.id_oidtype=t.id_oidtype AND \
			t.file_prefix IS NOT NULL AND \
			t.ds_name IS NOT NULL AND \
			( t.id_mode IS NULL OR t.id_mode=i.id_mode ) AND \
			1=1 \
		ORDER BY \
			i.id_interface, \
			t.file_prefix, \
			t.ds_name";

	dbQuery(mysql, query, &result);

	tInterface *interface = NULL;
	tRrd *rrd = NULL;
	tOid *oid = NULL;

    unsigned int last_id_interface = 0;
	char last_filePrefix[64] = "";
	char emptyString[1] = "";
	
	char trafficin_dsName[64] = "traffic_in";
	char trafficin_oidName[64] = "IF-MIB::ifInOctets.";
	char trafficout_dsName[64] = "traffic_out";
	char trafficout_oidName[64] = "IF-MIB::ifOutOctets.";

    tRrdList *rrdList;
    tOidList *oidList;

	unsigned int i = 0;
	unsigned int r = 0;
	unsigned int o = 0;

	list->count = 0;
	list->interface = NULL;

	while ((row = mysql_fetch_row(result))) {

		if (last_id_interface != atoi(row[0])) {
			list->count++;
			strncpy(last_filePrefix, "", sizeof(last_filePrefix));

    		list->interface = (tInterface*) realloc(list->interface, list->count * sizeof(tInterface));
			interface = &list->interface[list->count-1];

			interface->oidList.count = 0;
			interface->oidList.oid = NULL;

			interface->rrdList.count = 0;
			interface->rrdList.rrd = NULL;

			interface->id_interface = atoi(row[0]);
			interface->id_node = atoi(row[13]);
			interface->id_device = atoi(row[1]);
        	strncpy(interface->ip, row[2], sizeof(interface->ip));
        	strncpy(interface->community, row[3], sizeof(interface->community));
		}

		if (strcmp(last_filePrefix, row[6]) != 0) {
			interface->rrdList.count++;

    		interface->rrdList.rrd = (tRrd*) realloc(interface->rrdList.rrd,  interface->rrdList.count * sizeof(tRrd));
			rrd = &interface->rrdList.rrd[interface->rrdList.count-1];

			rrd->oidList.count = 0;
			rrd->oidList.oid = NULL;

			strncpy(rrd->filePrefix, row[6], sizeof(rrd->filePrefix));
			rrd->doWalk = atoi(row[12]);
		}

		interface->oidList.count++;
		rrd->oidList.count++;

    	interface->oidList.oid = (tOid*) realloc(interface->oidList.oid,  interface->oidList.count * sizeof(tOid));
		oid = &interface->oidList.oid[interface->oidList.count-1];

		oid->id_oidtype = atoi(row[4]);
		
		// oid overriding activated for this interface
		if (atoi(row[15]) == 1 && row[16] != NULL && strcmp(emptyString, row[16]) != 0 && (strcmp(trafficin_dsName, row[7]) == 0 || strcmp(trafficout_dsName, row[7]) == 0)) {
			char ifid[6] = "";
			char temp_oidName[64] = "";

			syslog(LOG_DEBUG, "oid_override: entered %d %s ifid: %s temp_oidname: %s", list->count, interface->ip, ifid, temp_oidName);
			if (strcmp(trafficin_dsName, row[7]) == 0) {
				strncpy(temp_oidName, trafficin_oidName, sizeof(temp_oidName));
				strncpy(ifid, row[16], sizeof(ifid));
				strcat(temp_oidName, ifid);
				strncpy(oid->oidName, temp_oidName, sizeof(oid->oidName));
			}
		  
			if (strcmp(trafficout_dsName, row[7]) == 0) {
				strncpy(temp_oidName, trafficout_oidName, sizeof(temp_oidName));
				strncpy(ifid, row[16], sizeof(ifid));
				strcat(temp_oidName, ifid);
				strncpy(oid->oidName, temp_oidName, sizeof(oid->oidName));
			}
			syslog(LOG_DEBUG, "oid_override: leaving %d %s %d %s %d %d %s %s", list->count, interface->ip, interface->rrdList.count,
                        rrd->filePrefix, interface->oidList.count, rrd->oidList.count, ifid, temp_oidName);
		} else {
			strncpy(oid->oidName, row[5], sizeof(oid->oidName));
		}
		
		strncpy(oid->filePrefix, row[6], sizeof(oid->filePrefix));
		strncpy(oid->dsName, row[7], sizeof(oid->dsName));
		strncpy(oid->rrdDst, row[8], sizeof(oid->rrdDst));
		strncpy(oid->rrdHeartbeat, row[9], sizeof(oid->rrdHeartbeat));
		strncpy(oid->rrdMin, row[10], sizeof(oid->rrdMin));
		strncpy(oid->rrdMax, row[11], sizeof(oid->rrdMax));
		oid->doWalk = atoi(row[12]);
		oid->id_type = atoi(row[14]);
		oid->result = NULL;
		oid->resultWalk = NULL;
		oid->resultWalkCount = 0;

		syslog(LOG_DEBUG, "dbGetInterfaceList: %d %s %d %s %d %d %s %s %d", list->count, interface->ip, interface->rrdList.count, 
			rrd->filePrefix, interface->oidList.count, rrd->oidList.count, oid->oidName, oid->dsName, atoi(row[15]));

		strncpy(last_filePrefix, row[6], sizeof(last_filePrefix));
		last_id_interface = atoi(row[0]);
	}

	// link additional pointer
    i = 0;
    while (i < list->count) {
        rrdList = &list->interface[i].rrdList;
        r = 0;
		o = 0;
        while (r < rrdList->count) {
            oidList = &rrdList->rrd[r].oidList;
			oidList->oid = &list->interface[i].oidList.oid[o];
            r++;
			o+=oidList->count;
        }
		i++;
    }

	mysql_free_result(result);
    return 0;
}
