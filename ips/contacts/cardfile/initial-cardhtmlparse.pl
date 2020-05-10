#!/usr/bin/perl

$cardfile_1 = './2001.html';
$dataout = './card.parsed';

open DATA, "$cardfile_1" or die "can't open $cardfile_1 $!";
open DATAOUT, ">$dataout" or die "can't open $dataout $!";

while (<DATA>) {
	my($line) = $_;
	chomp($line);
	$line =~ s/<BR>/\n/;
	$line =~ s/<LI>/\n\n/;
	$line =~ s/<!--.*-->//;
	$line =~ s/<\/UL>/\n/;
	$line =~ s/<UL>//;
	$line =~ s/\n\$^\n\n|\n\$^\n\n\n/\n\n/;
	
	print DATAOUT "$line";
}

close (DATA);
