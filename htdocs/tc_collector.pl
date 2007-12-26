#!/usr/bin/perl -W

###########################################################################
#
# tc data collector for mastershaper
#				
#  collects traffic statistic from tc to calculate the current
#  throughput of tc classes.
#
# Copyright (c) by Andreas Unterkircher
# All rights reserved
#
#  This program is free software; you can redistribute it and/or modify
#  it under the terms of the GNU General Public License as published by
#  the Free Software Foundation; either version 2 of the License, or
#  (at your option) any later version.
#
#  This program is distributed in the hope that it will be useful,
#  but WITHOUT ANY WARRANTY; without even the implied warranty of
#  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#  GNU General Public License for more details.
#
#  You should have received a copy of the GNU General Public License
#  along with this program; if not, write to the Free Software
#  Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
#
###########################################################################

sub readCfg;
sub getInterfaces();
sub daemonize;
sub cleanup;

$SIG{'INT'} = \&cleanup;
$SIG{'QUIT'} = \&cleanup;
$SIG{'ABRT'} = \&cleanup;
$SIG{'TERM'} = \&cleanup;

use constant LOG_INFO  => 1;
use constant LOG_WARN  => 2;
use constant LOG_DEBUG => 3;

# databse Interface
use strict;
load_module("DBI");
load_module("Getopt::Std");
load_module("XML::Simple");
use POSIX qw(setsid);

# some variables
my $class_id = 0;
my $bytes    = 0;
my $sec_counter = 0;
my (%bandwidth, %last_bytes, %config, %options);

# helper vars
my ($now, $interface, $ms_options, $msg, $level, $line);
my ($key, $value, $data, $tc_if, $current_bytes);
my ($current_bw, $aver_bw, $date, $file, $sth);
my ($dbh, $tc, %counter);
my @tc_interfaces = ();
my @temp_array = ();
my @result = ();
my @tcs = ();
my $verbose = 0;

# flush the buffer
$| = 1;

getopts("hdv:l:",\%options);

# shuld we display
if(defined($options{h})) {
   show_help();
   exit(0);
}

# should we fork now?
if(defined($options{d})) {
   &daemonize;
}

# should we output something?
if(defined($options{v})) {
   $verbose = $options{v};
}

# read options from the config file
readCfg('config.dat');

# connect to MySQL
mysql_connect();

while(1) {

   if(!$dbh->ping()) {

      printMsg(LOG_INFO, "MySQL is not available. Waiting for auto-reconnect...");
      sleep(1);
      next;

   }

   $sec_counter++;

   # get the used interface from the configureation stored in database
   # rules can be reloaded during our run so get the interfaces everytime.
   @tc_interfaces  = getInterfaces();

   $now = time();

   foreach $tc_if (@tc_interfaces) {

      # get the current stats from tc
      my @lines  = `$config{'tc_bin'} -s class show dev $tc_if`;

      # analyze the lines
      foreach $line (@lines) {

         # if the line doesn't contain what we need, get the next...
         if((($line !~ /class/) && ($line !~ /Sent/)) || ($line eq "")) {
            next;
         }
	
         # we calculate for the next class
         if($class_id eq 0) {

            # extract class id from the line string
            $class_id = extract_class_id($line);

            if($class_id ne 0) {

               printMsg(LOG_DEBUG, "Fetching data interface: ". $tc_if .", class: ". $class_id);

               # we already counting this class?
               if(!defined($counter{$tc_if ."_". $class_id})) {

                  $counter{$tc_if ."_". $class_id} = 0;
                  $last_bytes{$tc_if ."_". $class_id} = 0;

               }
            }
         }
         else {

            # extract current bytes from the line string
            $current_bytes = extract_bytes($line);

            printMsg(LOG_DEBUG, "Bytes for interface: ". $tc_if .", class: ". $class_id .", ". $current_bytes ." bytes");

            if($current_bytes > 0) {

               if(defined($last_bytes{$tc_if ."_". $class_id}) &&
                  $last_bytes{$tc_if ."_". $class_id} ne 0) {

                  # calculate the bandwidth from the last second
                  $current_bw = $current_bytes - $last_bytes{$tc_if ."_". $class_id};

               }
               else {	
                  $current_bw = 0;
               }

               # store the current bytes
               $last_bytes{$tc_if ."_". $class_id} = $current_bytes;
               # add it to the bandwidth summary
               $bandwidth{$tc_if ."_". $class_id}+=$current_bw;
               # increment the counter
               $counter{$tc_if ."_". $class_id}++;
            }

            # this class has been calculated, make all ready for the next one
            $class_id = 0;

         }
      }
   }

   if($sec_counter eq 10) {

      @tcs = keys(%bandwidth);
      $data = "";

      printMsg(LOG_WARN, "Storing tc statistic now.");

      foreach $tc (@tcs) {

         ($tc_if, $class_id) = split('_', $tc);

         # calculate the average bandwidth
         if($counter{$tc_if ."_". $class_id} > 0) {
            $aver_bw = $bandwidth{$tc_if ."_". $class_id}/($counter{$tc_if ."_". $class_id}); 
         } else {
            $aver_bw = 0;
         }
			
         # bytes to bits
         $aver_bw = round($aver_bw*8);

         printMsg(LOG_DEBUG, "Interface: ". $tc_if .", class: ". $class_id .", Bandwidth: ". $aver_bw ."bit/s");

         $data.= $tc_if ."_". $class_id ."=". $aver_bw .",";

         # this class has been calculated, make all ready for the next one
         $counter{$tc_if ."_". $class_id} = 0;
         $bandwidth{$tc_if ."_". $class_id} = 0;

      }

      if($data ne "") {
         $data = substr($data, 0, length($data)-1);
      }
		     
      if($data ne "") {
         # printMsg($data);
         $sth = $dbh->prepare("
            INSERT INTO ". $config{'mysql_prefix'} ."stats (stat_data, stat_time) 
            VALUES ('". $data ."', '". $now ."')
         ") || printMsg(LOG_WARN, "Error on preparing data: ". $dbh->errstr);

         $sth->execute() || printMsg(LOG_WARN, "Error on inserting data: ". $sth->errstr);
         $sth->finish();

         printMsg(LOG_WARN, "Statistics stored in MySQL database.");
      }
      else {
         printMsg(LOG_WARN, "No data available for statistics. tc rules loaded?");
      }

      # delete old samples
      $dbh->do("DELETE FROM ". $config{'mysql_prefix'} ."stats WHERE stat_time < ". ($now-300) ."");

      # reset helper vars
      %bandwidth = ();
      $sec_counter = 0;

   }

   sleep(1);

}

# disconnect from database
mysql_disconnect();





# returns the used interfaces
sub getInterfaces() {

   my @temp = ();
   $interface = $dbh->prepare("
      SELECT DISTINCT if_name
      FROM
         $config{'mysql_prefix'}interfaces iface
      INNER JOIN
         $config{'mysql_prefix'}network_paths np
      ON (
         np.netpath_if1=iface.if_idx
         OR
         np.netpath_if2=iface.if_idx
      )
      WHERE
         np.netpath_active='Y'
   ");
   $interface->execute();
   while(@result = $interface->fetchrow_array()) {
      push(@temp, $result[0]);
   }
   $interface->finish();
   return @temp;
}

sub readCfg {

   $file = $_[0];
   my $xml = new XML::Simple();
   my $ref = $xml->XMLin($file, ForceArray => ['config'], SuppressEmpty => 1);
   my @keys = keys(%$ref);

   #use Data::Dumper;
   #print Dumper($ref);

   foreach $key (@keys) {
      $config{lc($key)} = $ref->{$key};
   }
} # readCfg()

sub round {

   my($number) = shift;
   return int($number + .5 * ($number <=> 0));

}

sub extract_class_id {

   $line = shift;

   if($line =~ /class/) {

      @temp_array = ();
      @temp_array = split(' ', $line);
      return $temp_array[2];

   }

   return 0;
}

sub extract_bytes {

   $line = shift;

   if($line =~ /Sent/) {

      @temp_array = ();
      @temp_array = split(' ', $line);
      return $temp_array[1];

   }

   return -1;
}

sub getOption {

   my $opt_key = shift;

   $ms_options = $dbh->prepare("SELECT setting_value FROM ". $config{'mysql_prefix'} ."settings WHERE setting_key like '". $opt_key ."'");
   $ms_options->execute();

   if(@result = $ms_options->fetchrow_array()) {

      $ms_options->finish();
      return $result[0];

   }

   $ms_options->finish();
   return 0;

}

sub daemonize {

   defined(my $pid = fork) or die "Can't fork: $!";
   exit if $pid;

   open STDIN, '/dev/null' or die "Can't read to /dev/null: $!";
   open STDOUT, '/dev/null' or die "Can't write to /dev/null: $!";
   open STDERR, '/dev/null' or die "Can't read to /dev/null: $!";
   open (PIDFILE, ">/var/run/mastershaper.pid");
   printf PIDFILE "%d\n", POSIX::getpid();
   close (PIDFILE);

   setsid or die "Can't start a new session: $!";
   umask 0;

}

sub cleanup {
   mysql_disconnect();
   unlink("/var/run/mastershaper.pid");
   exit(0);
}

sub printMsg {

   $level = shift;
   $msg = shift;

   # user requested this output?
   if($level <= $verbose) {

      $date = localtime();
      print $date .": ". $msg ."\n";

   }
}

sub mysql_connect {

   $dbh = DBI->connect( 'dbi:mysql:'. $config{'mysql_db'} .':'. $config{'mysql_host'}, $config{'mysql_user'}, $config{'mysql_pass'}) || die "Can't connect to MySQL database: $dbh->errstr\n";
   $dbh->{mysql_auto_reconnect} = 1;

}

sub mysql_disconnect {
   $dbh->disconnect;
}

sub show_help {

   print qq|tc_collector.pl - MasterShaper tc statistic collector
(c) Andreas Unterkircher <unki\@netshadow.at>
http://www.mastershaper.org
   
./tc_collector.pl <options>

 -d  ... fork into background
 -h  ... this help text
 -vx ... verbose level (1 info, 2 warn, 3 debug)

|;

}

sub load_module {
   my $module = $_[0];
   eval "use $module";
   # Check eval response
   if ($@) {
      # Eval failed, so not installed
      print "Perl module $module is not available. Please check MasterShaper's requirements.\n"; 
      exit(1);
   }
}
