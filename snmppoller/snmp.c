#include <net-snmp/net-snmp-config.h>
#include <net-snmp/net-snmp-includes.h>
#include "snmp.h"

#define RESULTSIZE 1024

/*
 * initialize
 */
void snmpInitialize(tInterfaceList *interfaceList)
{
    tOidList *oidList;

	/* initialize library */
	init_snmp("snmppoller");
	netsnmp_ds_set_boolean(NETSNMP_DS_LIBRARY_ID, NETSNMP_DS_LIB_QUICK_PRINT, TRUE);

	unsigned int i = 0;
	while (i < interfaceList->count) {
		syslog(LOG_DEBUG, "snmpInitialize: %s", interfaceList->interface[i].ip);
        oidList = &interfaceList->interface[i].oidList;

        unsigned int o = 0;
        while (o < oidList->count) {
            syslog(LOG_DEBUG, "snmpInitialize: * %s", oidList->oid[o].oidName);
			oidList->oid[o].OidLen = sizeof(oidList->oid[o].Oid)/sizeof(oidList->oid[o].Oid[0]);
			if (!read_objid(oidList->oid[o].oidName, oidList->oid[o].Oid, &oidList->oid[o].OidLen)) {
				syslog(LOG_ERR, "snmpInitialize: read_objid failed for: %d %s", interfaceList->interface[i].id_interface, oidList->oid[o].oidName);
				exit(1);
			}
            o++;
        }
        i++;
    }
}

/*
 * simple printing of returned data
 */
int snmpStoreResult (tSession *session, struct snmp_pdu *pdu, char **result)
{
	char buf[RESULTSIZE];
	struct variable_list *vp;
	struct snmp_session *sp = session->sess;
	unsigned int o = session->current_oid;
	unsigned int i,j;

	*result = (char*)calloc(RESULTSIZE+1, sizeof(char));

	vp = pdu->variables;
	if (pdu->errstat == SNMP_ERR_NOERROR) {
		while (vp) {
			snprint_value(*result, RESULTSIZE, vp->name, vp->name_length, vp);

			// tolower & remove spaces and linebreaks
			for (i=0, j=0; i<strlen(*result); i++) {
				if ((*result)[i] > 0x22) {
					if ((*result)[i] >= 0x41 && (*result)[i] <= 0x5A) {
						(*result)[j] = (*result)[i] + 0x20;
					} else {
						(*result)[j] = (*result)[i];
					}
					j++;
				}
			}
			(*result)[j] = 0;

			// fix lancom snr
			if (session->oidList.oid[o].id_oidtype == 12 &&
				( session->oidList.oid[o].id_type == 2 ||
  				session->oidList.oid[o].id_type == 5 ||
  				session->oidList.oid[o].id_type == 9 ||
  				session->oidList.oid[o].id_type == 24 )) {
				snprintf(*result, RESULTSIZE, "%.0f", atoi(*result) * 0.64);
			}

			snprint_variable(buf, sizeof(buf), vp->name, vp->name_length, vp);
			syslog(LOG_DEBUG, "snmpStoreResult: %s: %s", sp->peername, buf);
			vp = vp->next_variable;
		}
		return 1;
	} else {
		if (vp) {
			snprint_objid(buf, sizeof(buf), vp->name, vp->name_length);
		} else {
			strcpy(buf, "(empty buf)");
		}

		syslog(LOG_ERR, "snmpStoreResult: %s: %s: (%s)", sp->peername, buf, snmp_errstring(pdu->errstat));

		if (pdu->errstat == SNMP_ERR_NOSUCHNAME) {
			snprintf(*result, RESULTSIZE, "U");
			return 1;
		}

		return 0;
	}
}

void storeEmptyResult(tSession *host)
{
	tOid *oid;
	oid = &host->oidList.oid[host->current_oid];
	while (host->current_oid < host->oidList.count) {
		if (oid->doWalk) {
			oid->resultWalkCount++;
			oid->resultWalk = (char**) realloc(oid->resultWalk, oid->resultWalkCount * sizeof(char*));
			*oid->resultWalk = (char*)calloc(RESULTSIZE, sizeof(char));
			snprintf(*oid->resultWalk, RESULTSIZE, "U");
		} else {
			oid->result = (char*)calloc(RESULTSIZE, sizeof(char));
			snprintf(oid->result, RESULTSIZE, "U");
		}
		host->current_oid++;
		oid = &host->oidList.oid[host->current_oid];
	}
}

/*
 * response handler
 */
int snmpAsyncResponse(int operation, struct snmp_session *sp, int reqid,
		    struct snmp_pdu *pdu, void *magic)
{
	char buf[RESULTSIZE];
	tSession *host = (tSession *)magic;
	struct snmp_pdu *req;
	tOid *oid;

	oid = &host->oidList.oid[host->current_oid];

	if (operation == NETSNMP_CALLBACK_OP_RECEIVED_MESSAGE) {
		if (oid->doWalk) {
			if (pdu->variables->name_length >= oid->OidLen &&
				memcmp(pdu->variables->name, oid->Oid, oid->OidLen * sizeof(oid))==0 &&
				pdu->variables->type != SNMP_ENDOFMIBVIEW &&
				pdu->variables->type != SNMP_NOSUCHOBJECT &&
				pdu->variables->type != SNMP_NOSUCHINSTANCE) {

				snprint_objid(buf, RESULTSIZE, pdu->variables->name, pdu->variables->name_length);
				if (strcmp(buf, oid->currentName) == 0) {
					syslog(LOG_ERR, "snmpAsyncResponse: %s: oid %s not increasing", sp->peername, buf);
					snmpActiveHosts--;
					return 1;
				}
				strcpy(oid->currentName, buf);

				oid->resultWalkCount++;
				oid->resultWalk = (char**) realloc(oid->resultWalk, oid->resultWalkCount * sizeof(char*));
				if (snmpStoreResult(host, pdu, &oid->resultWalk[oid->resultWalkCount-1])) {
					req = snmp_pdu_create(SNMP_MSG_GETNEXT);
					snprint_objid(buf, RESULTSIZE, pdu->variables->name, pdu->variables->name_length);
					snmp_add_null_var(req, pdu->variables->name, pdu->variables->name_length);
					if (snmp_send(host->sess, req))
						return 1;
					else {
						syslog(LOG_ERR, "snmpAsyncResponse: snmp_send failed for: %s", sp->peername);
						snmp_free_pdu(req);
					}
				} else {
					oid->resultWalkCount--;
					oid->resultWalk = (char**) realloc(oid->resultWalk, oid->resultWalkCount * sizeof(char*));
					storeEmptyResult(host);
				}
			}
		} else {
			if (!snmpStoreResult(host, pdu, &oid->result)) {
				storeEmptyResult(host);
			}
		}
		if (host->current_oid < host->oidList.count-1) {
			host->current_oid++;
			req = snmp_pdu_create(host->oidList.oid[host->current_oid].doWalk ? SNMP_MSG_GETNEXT : SNMP_MSG_GET);
			snmp_add_null_var(req, host->oidList.oid[host->current_oid].Oid, host->oidList.oid[host->current_oid].OidLen);
			if (snmp_send(host->sess, req))
				return 1;
			else {
				syslog(LOG_ERR, "snmpAsyncResponse: snmp_send failed for: %s", sp->peername);
				snmp_free_pdu(req);
			}
		}
	} else {
		syslog(LOG_DEBUG, "snmpAsyncResponse: %s: Timeout", sp->peername);
		storeEmptyResult(host);
	}

	/* something went wrong (or end of variables) 
 	* this host not active any more
 	*/
	snmpActiveHosts--;
	return 1;
}

void snmpAsynchronous(tInterfaceList *interfaceList)
{
	tSession *hs;
	tInterface *interface;

	/* startup all hosts */
	unsigned int i = 0;
	while (i < interfaceList->count) {
		interface = &interfaceList->interface[i];
		hs = &sessions[i];

		//printf("snmpAsynchronous: %s\n", interfaceList->interface[i].ip);

		struct snmp_pdu *req;
		struct snmp_session sess;
		snmp_sess_init(&sess);			/* initialize session */
		sess.version = SNMP_VERSION_1;
		sess.peername = strdup(interface->ip);
		sess.community = (u_char*)strdup(interface->community);
		sess.community_len = strlen((char*)sess.community);
		sess.callback = snmpAsyncResponse;		/* default callback */
		sess.callback_magic = hs;
		if (!(hs->sess = snmp_open(&sess))) {
			syslog(LOG_ERR, "snmpAsynchronous: snmp_open failed for: %d %s", interfaceList->interface[i].id_interface, interfaceList->interface[i].ip);
			i++;
			continue;
		}

		hs->oidList = interface->oidList;
		hs->current_oid = 0;
		req = snmp_pdu_create(hs->oidList.oid[hs->current_oid].doWalk ? SNMP_MSG_GETNEXT : SNMP_MSG_GET);	/* send the first GET */
		snmp_add_null_var(req, hs->oidList.oid[hs->current_oid].Oid, hs->oidList.oid[hs->current_oid].OidLen);
		if (snmp_send(hs->sess, req))
			snmpActiveHosts++;
		else {
			syslog(LOG_ERR, "snmpAsynchronous: snmp_send failed for: %d %s", interfaceList->interface[i].id_interface, interfaceList->interface[i].ip);
			snmp_free_pdu(req);
		}
		i++;
	}

	/* loop while any active hosts */

	while (snmpActiveHosts) {
		int fds = 0, block = 1;
		fd_set fdset;
		struct timeval timeout;

		FD_ZERO(&fdset);
		snmp_select_info(&fds, &fdset, &timeout, &block);
		fds = select(fds, &fdset, NULL, NULL, block ? NULL : &timeout);
		if (fds < 0) {
			perror("select failed");
			exit(1);
		}
		if (fds)
			snmp_read(&fdset);
		else
			snmp_timeout();
	}

	/* cleanup */

	i = 0;
	while (i < interfaceList->count) {
		interface = &interfaceList->interface[i];
		hs = &sessions[i];
		if (hs->sess) snmp_close(hs->sess);
		i++;
	}
}
