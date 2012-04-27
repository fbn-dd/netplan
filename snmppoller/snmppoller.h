#ifndef _SNMPPOLLER_H
#define _SNMPPOLLER_H

#include <net-snmp/net-snmp-config.h>
#include <net-snmp/net-snmp-includes.h>
#include <syslog.h>

//#define DEBUG 1

typedef struct sOid {
	unsigned int id_oidtype;
	unsigned int id_type;
	char oidName[64];
	char currentName[64];
	char filePrefix[64];
	char dsName[64];
	char rrdDst[32];
	char rrdHeartbeat[32];
	char rrdMin[32];
	char rrdMax[32];
	unsigned int doWalk;
	oid Oid[MAX_OID_LEN];
	size_t OidLen;
	char *result;
	unsigned int resultWalkCount;
	char **resultWalk;
} tOid;

typedef struct sOidList {
	unsigned int count;
	tOid *oid;
} tOidList;

typedef struct sRrd {
	char filePrefix[64];
	unsigned int doWalk;
	tOidList oidList;
} tRrd;

typedef struct sRrdList {
    unsigned int count;
	tRrd *rrd;
} tRrdList;

typedef struct sInterface {
	unsigned int id_interface;
	unsigned int id_node;
	unsigned int id_device;
	char ip[16];
	char community[32];
	tOidList oidList;
	tRrdList rrdList;
} tInterface;

typedef struct sInterfaceList {
	unsigned int count;
	tInterface *interface;
} tInterfaceList;

typedef struct sSession {
	struct snmp_session *sess;	/* SNMP session data */
	unsigned int current_oid;
	tOidList oidList;
} tSession;

tSession *sessions;
int snmpActiveHosts;			/* hosts that we have not completed */

#endif
