#!/bin/sh

NETPLAN=/opt/netplan/trunk
WEATHERMAP=/opt/weathermap/weathermap
TRAFFICMAP=/var/www/trafficmap

# $Sektion als eindimensionales Array deklarieren
declare -a Sektion
# IDs der Sektionen als Index mit Kürzel für Dateinamen als Wert
Sektion[1]="dd"
Sektion[2]="rb"
Sektion[3]="cw"
Sektion[8]="ftl"
Sektion[22]="let"
Sektion[21]="sh"

# static Maps VZ and Survey
$WEATHERMAP --config $NETPLAN/trafficmap/fbn-map-vz.conf --image-uri fbn-map-vz.png --output $TRAFFICMAP/fbn-map-vz.png --htmloutput $TRAFFICMAP/fbn-map-vz.html
$WEATHERMAP --config $NETPLAN/trafficmap/uebersicht.conf --image-uri index.png --output $TRAFFICMAP/index.png --htmloutput $TRAFFICMAP/index.html
$WEATHERMAP --config $NETPLAN/trafficmap/fbn-map-internet.conf --image-uri fbn-map-internet.png --output $TRAFFICMAP/fbn-map-internet.png --htmloutput $TRAFFICMAP/fbn-map-internet.html
$WEATHERMAP --config $NETPLAN/trafficmap/trafficampel.conf --image-uri trafficampel.png --output $TRAFFICMAP/trafficampel.png --htmloutput $TRAFFICMAP/trafficampel.html

for S in 1 2 3 8 22 21
do
    php -f $NETPLAN/trafficmap/trafficmap.php -- -m$S >$NETPLAN/tmp/fbn-map-${Sektion[$S]}.conf.tmp
    diff $NETPLAN/tmp/fbn-map-${Sektion[$S]}.conf.tmp $NETPLAN/tmp/fbn-map-${Sektion[$S]}.conf >/dev/null
    MAP_DIFF=$?
    if (test $MAP_DIFF -ne 0)
    then
        # alte Map durch neue ersetzen
        mv $NETPLAN/tmp/fbn-map-${Sektion[$S]}.conf.tmp $NETPLAN/tmp/fbn-map-${Sektion[$S]}.conf
    fi
    $WEATHERMAP --config $NETPLAN/tmp/fbn-map-${Sektion[$S]}.conf --image-uri fbn-map-${Sektion[$S]}.png --output $TRAFFICMAP/fbn-map-${Sektion[$S]}.png --htmloutput $TRAFFICMAP/fbn-map-${Sektion[$S]}.html
done

