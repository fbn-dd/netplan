#ifndef _MYSQL_H
#define _MYSQL_H

#include <mysql/mysql.h>
#include "snmppoller.h"

int dbConnect(MYSQL *mysql);
int dbClose(MYSQL *mysql);
int dbQuery(MYSQL *mysql, char *query, MYSQL_RES **result);
int dbFetch(MYSQL_RES *result);
int dbGetInterfaceList(MYSQL *mysql, tInterfaceList *list);

#endif
