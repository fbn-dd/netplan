#!/usr/bin/perl -w

use strict;
use warnings;
use DBI();
use Expect;
use Mail::Sendmail;
use File::Basename;
use File::Copy;

my $database = 'netplan';
my $hostname = 'dbserver';
my $dbuser   = 'netplan';
my $dbpass   = 'n3tpl@n';
my $cfgdir   = '/tmp';
my $ssh	     = '/usr/bin/ssh';

# Connect to the database.
my $dbh = DBI->connect("DBI:mysql:database=$database;host=$hostname",
                       $dbuser, $dbpass,
                       {'RaiseError' => 1});

# Now retrieve data from the table.
my $sth = $dbh->prepare('
    SELECT n.description as geraet, i.ip as ip, n.config_password as pass
    FROM interface as i INNER JOIN node as n ON i.id_node=n.id_node
    WHERE n.id_type IN ( 2, 5, 9)
	AND n.config_password NOT LIKE ""
	AND n.description LIKE "HSS-KBR"
    GROUP BY i.id_node');

$sth->execute();
while (my $ref = $sth->fetchrow_hashref()) {

  my $host = $ref->{'ip'};
  my $user = undef;
  my $pass = undef;

  if ( $ref->{'pass'} =~ /\:/ ) {
    ($user, $pass) = split(/:/,$ref->{'pass'}, 2);
  } else {
    $user = 'root';
    $pass = $ref->{'pass'};
  }

  my $dev  = $ref->{'geraet'};

  $dev =~ s/[\/ ]/_/g;

  print "fetching wlan log from $dev [$host]\n";

  my $cmd = "show bootlog";
  my @params = ('-o', 'ConnectTimeout=3', '-l', $user, $host);

  my $spawn_ok;
  my $cmd_sent;
  my $log = "";

  my $cfgfile = $cfgdir.'/'.$dev.'.lcs';
  my $tmpcfgfile = $cfgfile.'.tmp';

  # wget, da die http classen in perl mit dem defekten ssl mancher LCOS Versionen nicht klar kommen
  system('wget --timeout=5 --tries=5 --http-user=\''.$user.'\' --http-password=\''.$pass.'\' --no-check-certificate https://'.$host.'/config/1/3/46/ -O'.$tmpcfgfile.' >>/dev/null 2>&1');

  my $filesize = -s $tmpcfgfile;

  if ($filesize > 0) {
	open(FIN, "$tmpcfgfile");
	my @line = <FIN>;
	for (@line) {
		if ($_ =~ m/hung/) {
			print "$dev: found evidence (WLAN Log)\n";

			# create an Expect object by spawning another process
			# openssh, da die ssh classen mit dem defekten ssl mancher LCOS Versionen nicht klar kommen
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
				print "$dev: found evidence (Bootlog)\n";
			}

			last;
		}
	}
  }
  unlink($tmpcfgfile);
}
$sth->finish();

# Disconnect from the database.
$dbh->disconnect();


