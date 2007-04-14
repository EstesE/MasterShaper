#!/usr/bin/perl

# This scripts insert into the table shaper_ports
# the ports defined by IANA.
#
# http://www.iana.org/assignments/port-numbers
#
# this will delete all not user_defined ports from the table
# and insert new ones.

use DBI;

sub readCfg;

# read some options from the config file
readCfg('config.dat');

# connect to MySQL
my $dbh = DBI->connect( 'dbi:mysql:'. $config{'MYSQL_DB'} .':'. $config{'MYSQL_HOST'}, $config{'MYSQL_USER'}, $config{'MYSQL_PASS'}) || die "Kann keine Verbindung zum MySQL-Server aufbauen: $DBI::errstr\n";

#$sth = $dbh->prepare("DELETE FROM shaper_ports WHERE port_user_defined='N'") || die $dbh->errstr;
#$sth->execute() || die $sth->errstr;
#$sth->finish();


open(FILE, "port-numbers");

while(<FILE>) {

	$line = $_;
	chomp($line);

	if($line =~ /^#/ || $line eq "") {
		next;
	}

	if($line !~ /udp/ && $line !~ /tcp/) {
		next;
	}

	@fields = split(' ', $line);

	$portname = $fields[0];
	$number = $fields[1];
	$desc = "";

	for($i = 2; $i < @fields; $i++) {
		$desc.= $fields[$i] ." ";
	}
	
	$desc = substr($desc, 0, length($desc)-1);

	($number, $proto) = split('/', $number);

	if($number =~ /[0-9]/) {

		$desc = quotemeta($desc); 

                $port_id = isPort($portname, getProtocolId($proto));

		if(!$port_id) {
		   $query = "INSERT INTO shaper_ports (port_name, port_desc, port_number, port_protocol_id, port_user_defined, port_TOS) VALUES ('". $portname ."', '". $desc ."', '". $number ."', '". getProtocolId($proto) ."', 'N', '')";
		   $action = "Add port: ". $portname .", ". $desc .", ". $number .", ". $proto;
		} else {
                   $query = "UPDATE shaper_ports SET port_desc='". $desc ."', port_number='". $number ."', port_protocol_id='". getProtocolId($proto) ."', port_user_defined='N' WHERE port_idx='". $port_id ."'";
		   $action = "Update port: ". $portname .", ". $desc .", ". $number .", ". $proto;
		}
		print $action ."\n";
		#print $query ."\n";
		$sth = $dbh->prepare($query);
		$sth->execute() ||die $sth->errstr;
		$sth->finish();
	}
}

close(FILE);

$dbh->disconnect();


sub readCfg
{
        $file = $_[0];

        if(open(CONFIG, $file)) {

                while(<CONFIG>) {

                        $line = $_;
                        chomp($line);

                        if($line !~ /^#/ && $line ne "") {
				($key, $value) = split("=", $line);
				$value =~ s/\"//g;
				$config{$key} = $value;
			}

		}
		
		close(CONFIG);
	}
}

sub getProtocolId()
{
   $proto_name = $_[0];        

   $protocols = $dbh->prepare("SELECT proto_idx FROM shaper_protocols WHERE proto_name like '". $proto_name ."'");
   $protocols->execute();

   if(@result = $protocols->fetchrow_array())
   {
      $protocols->finish();
      return $result[0];
   }
   $procotols->finish();
   return 0;
}

sub isPort()
{
   $port_name = $_[0];
   $proto_id  = $_[1];

   $ports = $dbh->prepare("SELECT port_idx FROM shaper_ports WHERE port_name like '". $port_name ."' and port_protocol_id='". $proto_id ."'");
   $ports->execute();

   if(@result = $ports->fetchrow_array())
   {
      $ports->finish();
      return $result[0];
   }
   $ports->finish();
   return 0;
}
