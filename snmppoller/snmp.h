#ifndef _SNMP_H
#define _SNMP_H

#include <net-snmp/net-snmp-config.h>
#include <net-snmp/net-snmp-includes.h>
#include "snmppoller.h"

void snmpInitialize(tInterfaceList *interfaceList);
void snmpAsynchronous(tInterfaceList *interfaceList);

#endif
