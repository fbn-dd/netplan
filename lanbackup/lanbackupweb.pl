#!/usr/bin/perl -w

use strict;
use warnings;
use DBI();
use Expect;
use SVN::Client;
use Mail::Sendmail;
use File::Basename;
use File::Copy;

my $database = 'netplan';
my $hostname = 'dbserver';
my $dbuser   = 'netplan';
my $dbpass   = 'xxxxxxxxxxxxx';
my $cfgdir   = '/opt/config/trunk/lancom';
my $ssh	     = '/usr/bin/ssh';
my $mailsrv  = 'smtp.example.org';
my $mailfrom = '"Lancom Config Backup" <technik@lists.example.org>',
my $mailto   = '"Recipient" <whatever@example.com>';

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
    WHERE n.id_type IN ( 2, 5, 9 ) AND n.config_password NOT LIKE ""
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

  my $spawn_ok;
  my $cmd_sent;

  if (! -d $cfgdir) {
    mkdir $cfgdir;
    $svn->add($cfgdir, 0);
  }

  my $cfgfile = $cfgdir.'/lancom-'.$dev.'.lcs';
  my $tmpcfgfile = $cfgfile.'.tmp';
  my $newcfg = (! -e $cfgfile);

  system('wget --timeout=5 --tries=5 --user-agent=\'LCStools\' --http-user=\''.$user.'\' --http-password=\''.$pass.'\' --post-data=\'scriptaddpar=-i\' --no-check-certificate https://'.$host.'/savescript/doit -O'.$tmpcfgfile.' >>/dev/null 2>&1');

  my $filesize = -s $tmpcfgfile;

  if ($filesize > 0) {
    print "success\n";
    move($tmpcfgfile, $cfgfile);

    if ($newcfg) {
      print "new config from $dev [$host], adding to svn\n";
      $svn->add($cfgfile, 0);
    }
  } else {
    print "error\n";
    unlink($tmpcfgfile);
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
print 'https://www.example.org/netplan/websvn/comp.php?repname=Backups+LANCOM&compare[]=/@'.$rev.'&compare[]=/@HEAD'."\n";

if (length($g_status) > 0) {
  my %mail = ( Smtp    => $mailsrv,
               To      => $mailto,
               From    => $mailfrom,
	       Subject => 'Lancom config change detected',
               Message => $g_status."\n\nhttps://www.example.org/netplan/websvn/comp.php?repname=Backups+LANCOM&compare[]=/@".$rev.'&compare[]=/@HEAD');

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
