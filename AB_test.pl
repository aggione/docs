use CLI;
use Data::Dumper;
#use strict;
use warnings;
use JSON;

$datestring = localtime();
# my $devel=0;
my $span0='<span style="color:red;">';
my $span1='</span>';
# if ($devel==1) {
	print $span0.'<hr />PERL - AB - inizio - '.$datestring.$span1;
	print $span0.'<br />Argomenti: -> '.Dumper(@ARGV).$span1;
# }
