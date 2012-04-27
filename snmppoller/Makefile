#
# Warning: you may need more libraries than are included here on the
# build line.  The agent frequently needs various libraries in order
# to compile pieces of it, but is OS dependent and we can't list all
# the combinations here.  Instead, look at the libraries that were
# used when linking the snmpd master agent and copy those to this
# file.
#

CC=gcc

OBJS1=snmppoller.o snmp.o mysql.o rrdtool.o
TARGETS=snmppoller

CFLAGS=`net-snmp-config --cflags`
BUILDLIBS=`net-snmp-config --libs` -lmysqlclient -lrrd

# shared library flags (assumes gcc)
DLFLAGS=-fPIC -shared

all: $(TARGETS)

snmppoller: $(OBJS1)
	$(CC) -o snmppoller $(OBJS1)  $(BUILDLIBS)

clean:
	rm $(OBJS1) $(TARGETS)
