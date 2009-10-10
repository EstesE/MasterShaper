#!/usr/bin/perl

# This scripts converts IANA port-numbers and protocol-numbers
# to a CSV format.
#
# http://www.iana.org/assignments/port-numbers
# http://www.iana.org/assignments/protocol-numbers

use Class::CSV;

#
# PORT NUMBERS
#
open(FILE, "port-numbers");

my $csv = Class::CSV->new(
   fields => [qw/idx name desc number user_def/],
   line_separator => "\r\n"
);

while(<FILE>) {

	$line = $_;
	chomp($line);

	if($line =~ /^#/ || $line eq "") {
		next;
	}

   # we reached the end of port-numbers list
   if($line =~ /^References/) {
      close(FILE);
   }

	if($line !~ /udp/ && $line !~ /tcp/) {
		next;
	}

	@fields = split(' ', $line);

	$portname = $fields[0];
	$number   = $fields[1];
	$desc     = "";

	for($i = 2; $i < @fields; $i++) {
		$desc.= $fields[$i] ." ";
	}
	
	$desc = substr($desc, 0, length($desc)-1);

	($number, $proto) = split('/', $number);

	if($number !~ /^[0-9]/) {
      next;
   }

   # strip all non-alphanumeric characters
   $desc =~ s/[^\w\s-]//g;
   $desc = quotemeta($desc);

   $csv->add_line({
      idx => '',
      name => $portname,
      desc => $desc,
      number => $number,
      user_def => 'N',
   });
   $action = "Add port: ". $portname .", ". $desc .", ". $number .", ". $proto;
}

close(FILE);
open(OUTFILE, ">../htdocs/contrib/port-numbers.csv");
print OUTFILE $csv->string();
close(OUTFILE);

#
# PROTOCOL NUMBERS
#
open(FILE, "protocol-numbers");

my $csv = Class::CSV->new(
   fields => [qw/idx proto_number proto_name proto_desc user_def/],
   line_separator => "\r\n"
);

while(<FILE>) {

	$line = $_;
	chomp($line);

	if($line =~ /^#/ || $line eq "") {
		next;
	}

   # we reached the end of protocol-numbers list
   if($line =~ /^References/) {
      close(FILE);
   }

	@fields = split(' ', $line);

	$number   = $fields[0];
	$protoname = $fields[1];
	$desc     = "";

	for($i = 2; $i < @fields; $i++) {
		$desc.= $fields[$i] ." ";
	}
	
	$desc = substr($desc, 0, length($desc)-1);

	if($number !~ /^[0-9]/) {
      next;
   }


   # strip all non-alphanumeric characters
   $desc =~ s/[^\w\s-]//g;
   $desc = quotemeta($desc);

   $csv->add_line({
      idx => '',
      proto_number => $number,
      proto_name => $protoname,
      proto_desc => $desc,
      user_def => 'N',
   });
   $action = "Add port: ". $number .", ". $protoname .", ". $desc;
}

close(FILE);
open(OUTFILE, ">../htdocs/contrib/protocol-numbers.csv");
print OUTFILE $csv->string();
close(OUTFILE);
