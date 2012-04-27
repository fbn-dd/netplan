#!/usr/bin/perl -w

use strict;
use DBI();
use Expect;
use SVN::Client;
use Mail::Sendmail;
use File::Basename;

my $database = 'netplan';
my $hostname = 'dbserver';
my $dbuser   = 'netplan';
my $dbpass   = 'xxxxxxxxxxxxx';
my $cfgdir   = '/opt/config/trunk/lancom';
my $ssh	     = '/usr/bin/ssh';

# Subversion
my $svn = new SVN::Client(auth => [SVN::Client::get_simple_provider(),
                          SVN::Client::get_simple_prompt_provider(\&simple_prompt,2),
                          SVN::Client::get_username_provider()]);

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

  my $cmd  = 'readscript -i';
  my $dev  = $ref->{'geraet'};

  $dev =~ s/[\/ ]/_/g;

  print "fetching config from $dev [$host]\n";

  my @params = ('-o', 'ConnectTimeout=3', '-l', $user, $host);

  # create an Expect object by spawning another process
  my $exp = Expect->spawn($ssh, @params) or die "Cannot spawn $ssh: $!\n";
  $exp->log_stdout(0);

  my $spawn_ok;
  my $cmd_sent;
  my $config = "";
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
		    $config = $self->before();
                    $self->send("quit\r");
                  } else {
                    $self->send($cmd."\r");
		    $cmd_sent = 1;
		  }
                  exp_continue;
                }
               ],
              );

  if (!$spawn_ok) {
    print "failed to fetch config from $dev [$host], continue with next host\n";
    next;
  }
  
  # if no longer needed, do a soft_close to nicely shut down the command
  $exp->soft_close();

  if (length($config) <= 1000) {
    print "failed reading config from $dev [$host], continue with next host\n";
    next;
  }

  if ($config =~ m/closed by remote host\./) {
    print "failed reading config from $dev [$host], continue with next host\n";
    next;
  }

  # remove command and prompt
  my @tmp = split("\n", $config);
  shift @tmp;
  pop @tmp;
  pop @tmp;
  $config = join("\n", @tmp);

  if (! -d $cfgdir) {
    mkdir $cfgdir;
    $svn->add($cfgdir, 0);
  }

  my $cfgfile = $cfgdir.'/lancom-'.$dev.'.lcs';
  my $newcfg = (! -e $cfgfile);

  open(CFG,'>', $cfgfile) or die "Can't open config file '$cfgfile': $!\n";
  print CFG $config;
  close CFG;

  if ($newcfg) {
    print "new config from $dev [$host], adding to svn\n";
    $svn->add($cfgfile, 0);
  }

}
$sth->finish();

# Disconnect from the database.
$dbh->disconnect();

# svn status
my $g_status = '';
$svn->status($cfgdir, undef, \&get_status, 1, 1, 1, 0);

if (length($g_status) > 0) {
  my %mail = ( Smtp    => 'smtp.example.org',
               To      => '"Technik" <technik@lists.example.org>',
               From    => '"Lancom Config Backup" <technik@lists.example.org>',
	       Subject => 'Lancom config change detected',
               Message => $g_status);

  sendmail(%mail) or die $Mail::Sendmail::error;
  print $g_status;
} else {
  print "nothing modified\n";
}

# svn commit
$svn->commit($cfgdir, 0);

sub map_status {
  my ($status) = @_;
  my %code_map = ( $SVN::Wc::Status::none        => ' ',
                   $SVN::Wc::Status::normal      => ' ',
                   $SVN::Wc::Status::added       => 'A',
                   $SVN::Wc::Status::missing     => '!',
                   $SVN::Wc::Status::incomplete  => '!',
                   $SVN::Wc::Status::deleted     => 'D',
                   $SVN::Wc::Status::replaced    => 'R',
                   $SVN::Wc::Status::modified    => 'M',
                   $SVN::Wc::Status::merged      => 'G',
                   $SVN::Wc::Status::conflicted  => 'C',
                   $SVN::Wc::Status::obstructed  => '~',
                   $SVN::Wc::Status::ignored     => 'I',
                   $SVN::Wc::Status::external    => 'X',
                   $SVN::Wc::Status::unversioned => '?', );

  return $code_map{$status};
}

sub get_status {
  my ($path, $status) = @_;
  if ( $status->text_status != $SVN::Wc::Status::ignored
    and $status->text_status != $SVN::Wc::Status::none
    and $status->text_status != $SVN::Wc::Status::normal ) {
    $g_status .= map_status($status->text_status)."      ".basename($path)."\n";
  }
} 
