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
my $cfgdir   = '/opt/config/trunk/mikrotik';
my $ssh	     = '/usr/bin/ssh';
my $mailsrv  = 'smtp.example.org';
my $mailfrom = '"Mikrotik Config Backup" <technik@lists.example.org>';
my $mailto   = '<technik@lists.example.org>';

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
    FROM interface as i INNER JOIN node as n ON i.id_node = n.id_node
    WHERE n.id_type = 30 AND n.config_password NOT LIKE ""
    GROUP BY i.id_node');

$sth->execute();
while (my $ref = $sth->fetchrow_hashref()) {

  my $host = $ref->{'ip'};
  my $user = undef;
  my $pass = undef;

  if ( $ref->{'pass'} =~ /\:/ ) {
    ($user, $pass) = split(/:/,$ref->{'pass'}, 2);
  } else {
    # default username for MikroTik RouterOS is "admin", not "root"
    $user = 'admin';
    $pass = $ref->{'pass'};
  }

  my $cmd  = 'export';
  my $dev  = $ref->{'geraet'};

  $dev =~ s/[\/ ]/_/g;

  print "fetching config from $dev [$host]\n";

  `echo $pass > /opt/netplan/trunk/tmp/rbbackup`;
  my $config = `sshpass -f/opt/netplan/trunk/tmp/rbbackup ssh -o StrictHostKeyChecking=no $user\@$host $cmd`;
  `rm -rf /opt/netplan/trunk/tmp/rbbackup`;

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

  my $cfgfile = $cfgdir.'/mikrotik-'.$dev.'.cfg';
  my $newcfg = (! -e $cfgfile);

  open(CFG,'>', $cfgfile) or die "Can't open config file '$cfgfile': $!\n";

  # fix openvpn server mac address, because its changing on every export
  # we need to find a config part like this:
  #---
  #/interface ovpn-server server
  #set auth=sha1,md5 certificate=none cipher=blowfish128,aes128 default-profile=\
  #    default enabled=asdf keepalive-timeout=160 mac-address=ab:ba:cd:dc:ef:fe \
  #    max-mtu=1500 mode=ip netmask=24 port=1194 require-client-certificate=no
  #---
  # 3rd memorized string is everything AFTER the mac-address value
  # preserve values except for mac-address
  # old try: `sed -i 's#default enabled=\(.*\) keepalive-timeout=\(.*\) mac-address=.*:[0-9a-fA-F][0-9a-fA-F]\(.*\)#default enabled=\1 keepalive-timeout=\2 mac-address=FB:DD:FB:DD:FB:DD\3#' $cfgfile`;
  # new try:
  #debug: if () { print 'match\n'; print $config; } else { print 'no match\n'; }
  $config =~ s/default enabled=(.*) keepalive-timeout=(.*) mac-address=.*:[0-9a-fA-F][0-9a-fA-F](.*)/default enabled=$1 keepalive-timeout=$2 mac-address=FB:DD:FB:DD:FB:DD$3/;

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

# fetch current revision
# Because we use a shared repository for all configs, we need to get the repositories last revision, not that of our subdirectory
# therefore we need to lookup $cfgdir/../
my $cmd = 'svn info -R '.$cfgdir.'/../ | grep Revision | cut -d" " -f2 | sort -n | tail -1';
my $rev = `$cmd`;
# remove trailing spaces
$rev =~ s/\s+$//;
print 'https://www.example.org/netplan/websvn/comp.php?repname=Backups+MikroTik&compare[]=/@'.$rev.'&compare[]=/@HEAD'."\n";

if (length($g_status) > 0) {
  my %mail = ( Smtp    => $mailsrv,
               To      => $mailto,
               From    => $mailfrom,
	       Subject => 'Mikrotik config change detected',
               Message => $g_status."\n\nhttps://www.example.org/netplan/websvn/comp.php?repname=Backups+MikroTik&compare[]=/@".$rev.'&compare[]=/@HEAD');

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
