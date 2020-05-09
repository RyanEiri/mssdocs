#!/usr/bin/perl

$cardfile_1 = './card.parsed';
$dataout = './card2.parsed';

open DATA, "$cardfile_1" or die "can't open $cardfile_1 $!";
open DATAOUT, ">$dataout" or die "can't open $dataout $!";

while (<DATA>) {
	my($line) = $_;
	chomp($line);
	$line =~ s/\$\n^\n/\n/;
	
	print DATAOUT "$line";
}

close (DATA);
