#!/usr/bin/perl -w

$SIG{'INT'} = sub { killed('INT'); };
$SIG{'KILL'} = sub { killed('KILL'); };
$SIG{'TERM'} = sub { killed('TERM'); };

use strict;
use Config::IniFiles;
use XMLRPC::Lite;
use Net::Telnet;
use DBI;
use Expect;

# Config Options

my $file = "/etc/eipen/eipen-server.conf";
my $config = Config::IniFiles->new(-file=> $file) or die "Could not open $file!";

my $database_db = $config->val('database', 'db');
my $database_host = $config->val('database', 'host');
my $database_userid = $config->val('database', 'user_id');
my $database_passwd = $config->val('database', 'db_password');

my $client_port = $config->val('main','client_port');

my $rootpasswd = $config->val('guest', 'root_password');

my $cobbler_server = $config->val('cobbler','server_name');
my $cobbler_uname = $config->val('cobbler','user_name');
my $cobbler_passwd = $config->val('cobbler','password');

my $reply_email = $config->val('messaging', 'reply_email');
my $from_email = $config->val('messaging', 'from_email');
my $new_machine_file = $config->val('messaging', 'new_machine');
my $bare_metal_file = $config->val('messaging', 'bare_metal');
my $error_file = $config->val('messaging', 'error');


#################

sub printaccess {
        my $msg = $_[0];
        my $now = localtime time;
        print "$now: $msg\n";
}

sub printerror {
        my $msg = $_[0];
        my $now = localtime time;
        print STDERR "$now: $msg\n";
}

sub killed {
        my($sig) = @_;
        printaccess("recieved signal $sig.  shutting down");
        exit(1);
}

sub sendemail {
        my $to = "To: ".$_[0]."\n";
        my $subject = "Subject: ".$_[1]."\n";
        my $body = $_[2];

        my $sendmail = "/usr/sbin/sendmail -t";
        my $reply_to = "Reply-to: $reply_email\n";
        my $from = "From: Eipen <$from_email>\n";

        open(SENDMAIL, "|$sendmail") or die "Cannot open $sendmail: $!";
        print SENDMAIL $reply_to; 
        print SENDMAIL $from;
        print SENDMAIL $to; 
        print SENDMAIL "Content-type: text/plain\n"; 
        print SENDMAIL $subject; 
        print SENDMAIL $body; 
        close(SENDMAIL);
}

sub senderror {
}

if ( !@ARGV ) {
        print "Usage: eipen-server.pl --help\n";
        exit(1);
}

my $funccall = $ARGV[0];

if ($funccall eq "--help") {
        print "eipen-server.pl createmachine hostip guestname diskimage macaddr memory email clientip coursename machinetype\n".
               "eipen-server.pl destroymachine hostip guestname\n".
               "eipen-server.pl savemachine hostip guestname destimage email\n".
			"eipen-server.pl createbaremetal hostip guestname profilename email coursename\n";
        exit(1);
}

if (!($funccall eq "createmachine") && !($funccall eq "destroymachine") && !($funccall eq "savemachine") && !($funccall eq "createbaremetal")) {
        print "Usage: eipen-server.pl --help\n";
        exit(1);
}

my $hostip = $ARGV[1];
my $guestname = $ARGV[2];
my $client;

open STDIN, '/dev/null'   or die "Can't read /dev/null: $!";
open STDOUT, '>>/tmp/'.$guestname.'.log' or die "Can't write to /tmp/".$guestname.".log: $!";
open STDERR, '>>/tmp/'.$guestname.'.log' or die "Can't write to /tmp/".$guestname.".log: $!";


if ($funccall eq "createbaremetal") {
	my $profilename = $ARGV[3];
	my $email = $ARGV[4];
	my $coursename = $ARGV[5];

	my $sleepcount = 10;
	my $sleepamt = 60;

	my ($dbh, $sth, $rv, $query);

	my $fence_command;

	$client = XMLRPC::Lite->proxy("http://$cobbler_server/cobbler_api_rw");

	my $creds = $client->login($cobbler_uname, $cobbler_passwd)->result();
	my $system = $client->new_system($creds)->result();

	$client->modify_system($system, "name", $hostip, $creds)->result();
	$client->modify_system($system, "profile", $profilename, $creds)->result();
	$client->modify_system($system, "ip", $hostip, $creds)->result();
	$client->modify_system($system, "ksmeta", "studentname=$email coursename=$coursename", $creds)->result();

	printerror("~~$hostip~~$profilename~~$email~~$coursename~~");

	my $result = $client->save_system($system, $creds)->result();

	$dbh = DBI->connect("DBI:mysql:database=$database_db;host=$database_host",
		 $database_userid, $database_passwd, {'PrintError' => 1, 'RaiseError' => 1});
     if (!$dbh) { die(printerror("Died connecting to database for fence command")); }

	$query = "SELECT fence_command FROM host WHERE ipaddr = '$hostip'";
	$sth = $dbh->prepare($query);
	if (! $sth) { die(printerror("Died preparing statement for fence command")); }

	$rv = $sth->execute();
	if (! $rv) { die(printerror("Died executing statement for fence command")); }

	$sth->bind_columns(undef, \$fence_command);

	while ($sth->fetch()) {
		system("$fence_command &");
	}

	#TO-DO: Check status and report
	sleep($sleepamt);

	my $i;
     for ($i = 0; $i<$sleepcount; $i++) {
		
		my $sock = new Net::Telnet;
		my $sockret = $sock->open(Timeout => 5, Host => $hostip, port=> 22, Errmode=>"return");


		if($sockret) {		
			my $email_body;
			my $email_title;

			open FILE, "<", $bare_metal_file or die $!;
			while (my $line = <FILE>) {
				$line =~ s/%course_name/$coursename/gi; 
				$line =~ s/%host_ip/$hostip/gi;
				$line =~ s/%root_password/$rootpasswd/gi;

				if (!$email_title) {
					$email_title = $line;
				} else {
					$email_body .= $line . "\n";
				}	
			}

			close(FILE);

			sendemail ($email, $email_title, $email_body);
			exit(0);
		}

		sleep($sleepamt);
	}

	my $email_body;
	my $email_title;

	printaccess($error_file);
	open FILE, "<", $error_file or die $!;
	while (my $line = <FILE>) {
		$line =~ s/%course_name/$coursename/gi; 
		$line =~ s/%host_ip/$hostip/gi;
		$line =~ s/%root_password/$rootpasswd/gi;

		if (!$email_title) {
			$email_title = $line;
		} else {
			$email_body .= $line . "\n";
		}	
	}

	close(FILE);
	sendemail ($email, $email_title, $email_body);
	
} elsif ($funccall eq "createmachine") {
	my $disk = $ARGV[3];
	my $macaddr = $ARGV[4];
	my $memory = $ARGV[5];
	my $email = $ARGV[6];
	my $clientip = $ARGV[7];
	my $coursename = $ARGV[8];
	my $vmtype = $ARGV[9];

	printaccess("Starting machine for $coursename : $email on $clientip ($disk - $macaddr - $memory - $vmtype)");

	$client = new XMLRPC::Lite->proxy("http://$hostip:$client_port") or die "Could not connect to $hostip:$client_port";

	eval { my $ret = $client->startMachine($guestname, $disk, $macaddr, $memory, $vmtype) or die "Unable to run connection: $!"; };
	if ($@) {
		print "$@";
	}

	my $i;
	for ($i = 0; $i<20; $i++) {
		my $ret = $client->isDomainRunning($guestname) or die "Unable to run connection: $!";
		if ($ret->result == 1) {
			my $email_body;
			my $email_title;

			printaccess($new_machine_file);
			open FILE, "<", $new_machine_file or die $!;
			while (my $line = <FILE>) {
				$line =~ s/%course_name/$coursename/gi;
				$line =~ s/%client_ip/$clientip/gi;
				$line =~ s/%root_password/$rootpasswd/gi;

				if (!$email_title) {
					$email_title = $line;
				} else {
					$email_body .= $line . "\n";
				}
			}

			close(FILE);

			sendemail ($email, $email_title, $email_body);
			exit(0);
		}
		sleep 30;
	}

	my $email_body;
	my $email_title;

	printaccess($error_file);
	open FILE, "<", $error_file or die $!;
	while (my $line = <FILE>) {
		$line =~ s/%course_name/$coursename/gi; 
		$line =~ s/%client_ip/$clientip/gi;
		$line =~ s/%root_password/$rootpasswd/gi;

		if (!$email_title) {
			$email_title = $line;
		} else {
			$email_body .= $line . "\n";
		}	
	}

	close(FILE);
	sendemail ($email, $email_title, $email_body);

} elsif ($funccall eq "destroymachine") {
	$client = new XMLRPC::Lite->proxy("http://$hostip:$client_port") or die "Could not connect to $hostip:$client_port";
	my $ret = $client->destroyMachine($guestname) or die "Unable to run command: $!";
} elsif ($funccall eq "savemachine") {
	my $dest = $ARGV[3];
	my $overwrite = $ARGV[4];
	my $email = $ARGV[5];

	$client = new XMLRPC::Lite->proxy("http://$hostip:$client_port") or die "Could not connect to $hostip:$client_port";
	my $ret = $client->saveMachine($guestname, $dest, $overwrite) or die "Unable to run command: $!";

	if ($ret->value() == 1) {
		sendemail($email, "Your machine has been saved", "You machine has been saved correctly");
	} else {
		sendemail($email, "Your machine has not been saved", "There was a problem saving your machine");
	}
}
