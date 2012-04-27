#!/usr/bin/perl -w

use strict;
use warnings;
use DBI();
use Net::SNMP;

my $database = 'netplan';
my $hostname = 'dbserver';
my $dbuser   = 'netplan';
my $dbpass   = 'xxxxxxxxxxxxx';
my $cfgdir   = '/tmp';
my $ssh	     = '/usr/bin/ssh';

my $sysObjectId = '.1.3.6.1.2.1.1.2.0';
my $logId    = '.1.3.46';

# Connect to the database.
my $dbh = DBI->connect("DBI:mysql:database=$database;host=$hostname",
                       $dbuser, $dbpass,
                       {'RaiseError' => 1});

# Now retrieve data from the table.
my $sth = $dbh->prepare('
    SELECT
	n.description as geraet,
	i.ip as ip,
	n.snmp_community as snmp_pass
    FROM interface as i INNER JOIN node as n ON i.id_node=n.id_node
    WHERE n.id_type IN ( 2, 5, 9)
    GROUP BY i.id_node');

$sth->execute();
while (my $ref = $sth->fetchrow_hashref()) {
  my $host = $ref->{'ip'};
  my $snmp_pass = undef;
  my $result = undef;
  my $freeze = 0;

  if( $ref->{'snmp_pass'} =~ m/disabled/ ) {
    next; 
  } else {
    if ( $ref->{'snmp_pass'} eq "" ) {
      $snmp_pass = "public";
    } else {
      $snmp_pass = $ref->{'snmp_pass'};
    }
  }

  my $dev  = $ref->{'geraet'};

  $dev =~ s/[\/ ]/_/g;

#  print "fetching wlan log from $dev [$host]\n";

  my ($session, $error) = Net::SNMP->session(
     -hostname  => shift || $host,
     -community => shift || $snmp_pass,
     -port      => shift || 161 
  );

  if (!defined($session)) {
#     printf("ERROR: %s.\n", $error);
     exit 1;
  }

  $result = $session->get_request(
    -varbindlist => [$sysObjectId]
  );

  if (!defined($result)) {
#    printf("ERROR: %s.\n", $session->error);
    $session->close;
    next;
  }

  my $vendorId = $result->{$sysObjectId};

  $result = undef;
  $result = $session->get_table(
    -baseoid => $vendorId.$logId
  );

  if (!defined($result)) {
#    printf("ERROR: %s.\n", $session->error);
    $session->close;
    next;
  }

  $session->close;

  my %output = %{$result};
  foreach my $key ( keys %output ) {
    if ($output{$key} =~ m/WLAN card hung, resetting/) {
      print "$dev: found evidence (WLAN Log)\n";
      last;
    }
  }

}
$sth->finish();

# Disconnect from the database.
$dbh->disconnect();
