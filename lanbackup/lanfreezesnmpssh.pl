#!/usr/bin/perl -w

use strict;
use warnings;
use DBI();
use Expect;
use Mail::Sendmail;
use File::Basename;
use File::Copy;
use Net::SNMP;

my $database = 'netplan';
my $hostname = 'dbserver';
my $dbuser   = 'netplan';
my $dbpass   = 'xxxxxxxxxxxxx';
my $cfgdir   = '/tmp';
my $ssh	     = '/usr/bin/ssh';

my $sysObjectId = '.1.3.6.1.2.1.1.2.0';
my $logId    = '.1.3.46';

my $cmd = "show bootlog";

# Connect to the database.
my $dbh = DBI->connect("DBI:mysql:database=$database;host=$hostname",
                       $dbuser, $dbpass,
                       {'RaiseError' => 1});

# Now retrieve data from the table.
my $sth = $dbh->prepare('
    SELECT
	n.description as geraet,
	i.ip as ip,
	n.config_password as pass,
	n.snmp_community as snmp_pass
    FROM interface as i INNER JOIN node as n ON i.id_node=n.id_node
    WHERE n.id_type IN ( 2, 5, 9)
	AND n.config_password NOT LIKE ""
#	AND n.description LIKE "HSS-KBR"
    GROUP BY i.id_node');

$sth->execute();
while (my $ref = $sth->fetchrow_hashref()) {
  my $host = $ref->{'ip'};
  my $user = undef;
  my $pass = undef;
  my $snmp_pass = undef;
  my $result = undef;
  my $freeze = 0;

  if( $ref->{'snmp_pass'} =~ m/disabled/ ) {
    
  } else {
    if ( $ref->{'snmp_pass'} eq "" ) {
      $snmp_pass = "public";
    } else {
      $snmp_pass = $ref->{'snmp_pass'};
    }
  }

  if ( $ref->{'pass'} =~ /\:/ ) {
    ($user, $pass) = split(/:/,$ref->{'pass'}, 2);
  } else {
    $user = 'root';
    $pass = $ref->{'pass'};
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
     printf("ERROR: %s.\n", $error);
     exit 1;
  }

  $result = $session->get_request(
    -varbindlist => [$sysObjectId]
  );

  if (!defined($result)) {
    printf("ERROR: %s.\n", $session->error);
    $session->close;
    next;
  }

  my $vendorId = $result->{$sysObjectId};

  $result = undef;
  $result = $session->get_table(
    -baseoid => $vendorId.$logId
  );

  if (!defined($result)) {
    printf("ERROR: %s.\n", $session->error);
    $session->close;
    next;
  }

  $session->close;

  my %output = %{$result};

  foreach my $key ( keys %output) {
    if ($output{$key} =~ m/WLAN card hung, resetting/) {
      $freeze++;
#      print "$dev: found evidence (WLAN Log)\n";

      my @params = ('-o', 'ConnectTimeout=3', '-l', $user, $host);

      my $spawn_ok;
      my $cmd_sent;
      my $log = "";

      my $exp = Expect->spawn($ssh, @params) or die "Cannot spawn $ssh: $!\n";
      $exp->log_stdout(0);

      $exp->expect(10,
                   [
                    qr/Are you sure you want to continue connecting/i,
                     sub {
                       my $self = shift;
                       $spawn_ok = 1;
                       $self->send("yes\n");
                       exp_continue;
                     }
                   ],
                   [
                    qr/password: /i,
                     sub {
                       my $self = shift;
                       $spawn_ok = 1;
                       $self->send("$pass\n");
                       exp_continue;
                     }
                   ],
                   [
                    qr/Permission denied /i,
                     sub {
                       $spawn_ok = 0;
                       exp_continue;
                     }
                   ],
                   [
                    qr/> /i,
                     sub {
                       my $self = shift;
                       $spawn_ok = 1;
                       if ($cmd_sent) {
                         $log = $self->before();
                         $self->send("quit\r");
                       } else {
                         $self->send($cmd."\r");
                         $cmd_sent = 1;
                       }
                       exp_continue;
                     }
                   ],
                  );

      if ($log =~ m/R00 = 0x48000000/) {
        $freeze++;
#        print "$dev: found evidence (Bootlog)\n";
      }

      last;
    }
  }
  if ($freeze > 0) {
    my $addtext = "möglicherweise";
    if ($freeze > 1) {
      $addtext = "";
    }
    print "$dev hat ".$addtext."das Kälteproblem\n";
  }
}
$sth->finish();

# Disconnect from the database.
$dbh->disconnect();


