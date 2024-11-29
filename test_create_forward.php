<?php					
// https://iam.local.santannapisa.it/test_create_forward.php


//      #################################################################
//      #  Forwarder managent commands
//      
//      sub ListForwarders {
//        my ($this, $domainName) = @_;
//        my $line = 'ListForwarders';
//        $line .= ' ' .$domainName if $domainName;
//        $this->send($line);
//        return undef unless $this->_parseResponse();
//        $this->parseWords($this->getWords);
//      }    
//      
//      sub CreateForwarder {
//        my ($this, $forwarderName, $address) = @_;
//        croak 'usage CGP::CLI->CreateForwarder($forwarderName, $address)'
//          unless defined $forwarderName && defined $address;
//        $this->send('CreateForwarder '.$forwarderName.' TO '.$this->printWords($address));
//        $this->_parseResponse(); 
//      }
//      sub RenameForwarder {
//        my ($this, $forwarderName, $newName) = @_;
//        croak 'usage CGP::CLI->RenameForwarder($forwarderName, $newName)'
//          unless defined $forwarderName && defined $newName;
//        $this->send('RenameForwarder '.$forwarderName.' into '.$newName);
//        $this->_parseResponse(); 
//      }
//      
//      sub DeleteForwarder {
//        my ($this, $forwarderName) = @_;
//        croak 'usage CGP::CLI->DeleteForwarder($forwarderName)'
//          unless defined $forwarderName;
//        $this->send('DeleteForwarder '.$forwarderName);
//        $this->_parseResponse();    
//      }
//      
//      sub GetForwarder {
//        my ($this, $forwarderName) = @_;
//        croak 'usage CGP::CLI->GetForwarder($forwarderName)'
//          unless defined $forwarderName;
//        $this->send('GetForwarder '.$forwarderName);
//        return undef unless $this->_parseResponse();
//        $this->parseWords($this->getWords);      
//      }
//      sub FindForwarders {
//        my ($this, $domain,$forwarderAddress) = @_;
//        croak 'usage CGP::CLI->FindForwarders($domainName,$forwarderAddress)'
//          unless (defined $domain && defined $forwarderAddress);
//        $this->send('FindForwarders '.$domain.' TO '.$this->printWords($forwarderAddress));
//        return undef unless $this->_parseResponse();
//        $this->parseWords($this->getWords);      
//      }

// $m='Chiara.Gherardi@santannapisa.it';
// $m='Andrea.Bonora@santannapisa.it';
$m='Sara.Falleni@santannapisa.it';
$a=explode('@',$m);
// $c='/usr/bin/perl AB.pl ABCreaForwarder '.strtolower($a[0]);
// echo $c.'<br />';
$c='/usr/bin/perl AB.pl ABGetForwarder '.strtolower($a[0]);
echo shell_exec($c);



?>					