#!/usr/bin/perl

$cardfile_1 = './2001.CRD';
$cardfile_2 = './BUS-CRDS.CRD';

open DATA, "$cardfile_1" or die "can't open $cardfile_1 $!";

while (<DATA>) {
	$line = <DATA>;
	if ($line =~ m/^\r/) {
	} else {
		printf "%s\n", $line;
	}
}

close (DATA);

open DATA, "$cardfile_2" or die "can't open $cardfile_2 $!";
