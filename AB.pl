# ----------------------------------------------------------------------
# Alberto - dic 2016
# ----------------------------------------------------------------------
# Rilevazione dati da communigate - account e mailng-lists
# Gli argomenti devono essere nell'ordine previsto, quelli eccedenti vengono ignorati
# ----------------------------------------------------------------------
# --- esempi (php)
# $p='C:\xampp5634\perl\bin\perl.exe ';
# $s='C:\xampp5634\htdocs\lib\pl\AB.pl ';
# $result = shell_exec($p.$s.'ABHTML ABListLists pippo pluto ');
# $result = shell_exec($p.$s.'ABListLists pippo pluto ');
# $result = shell_exec($p.$s.'ABGetAccountInfo a.bongiorni ');
# $result = shell_exec($p.$s.'ABGetAccountInfo ABHTML a.bongiorni pippo pluto ');
# $result = shell_exec($p.$s.'ABListSubscribers utenti-ugov ');
# $result = shell_exec($p.$s.'ABListSubscribers utenti-ugov ABHTML ');
# $result = shell_exec($p.$s.'ABClonaLista utenti-ugov utenti-ugov-clone a.bongiorni ');
# $result = shell_exec($p.$s.'ABCancellaLista utenti-ugov-clone ');
# $result = shell_exec($p.$s.'ABListAccounts ABHTML ');
# $result = shell_exec($p.$s.'ABCreaAccountRedirect a.bongi_test \'albo.bongi_test\''
# ----------------------------------------------------------------------
# http://www.communigate.com/CGPerl/ = CommuniGate Pro Perl Interface
# http://www.communigate.com/ScriptRepository/ = Script Repository for CommuniGate Pro
# http://www.communigate.com/CommuniGatePro/CLI.html = CLI interface

# https://www.communigate.ru/CGPerl/ = CommuniGate Pro Perl Interface
# http://www.communigate.ru/ScriptRepository/ = Script Repository for CommuniGate Pro
# http://www.communigate.ru/CommuniGatePro/CLI.html = CLI interface
# ----------------------------------------------------------------------

#

use CLI;
use Data::Dumper;
#use strict;
use warnings;
use JSON; # 	imports encode_json, decode_json, to_json and from_json.
# use CGI; # DA ERRORE
# use PHP:
#	print "Content-Type: text/html; charset=iso-8859-1\n\n";
# FTP::ias1\/var/www/html/DBUtenti/src|forcepwdape.pl

$datestring = localtime();
# print "Current date and time $datestring\n";

my $devel=0;
my $span0='<span style="color:red;">';
my $span1='</span>';
if ($devel==1) {
	print $span0.'<hr />PERL - AB - inizio - '.$datestring.$span1;
	print $span0.'<br />Argomenti: -> '.Dumper(@ARGV).$span1;
}
my $log=0;
my $log_a=1;
my $logfile='log.txt';
# if ($log or $log_a) {
if ($log) {
	open(FILEHANDLE,">>".$logfile);
	print FILEHANDLE "\n\n========================== PERL - AB - inizio - ".$datestring."\n";
	print FILEHANDLE "Argomenti: --> \n".Dumper(@ARGV);
	close(FILEHANDLE);
}

# ----------------------------------------------------------------------
# connessione e dichiarazione della libreria communigate
# ----------------------------------------------------------------------
my $HostCM = 'mail.santannapisa.it';
my $CGServerAddress = "193.205.80.99";
my $Domain = 'santannapisa.it';
my $PwdCM = 'Sk4cK0lov1tcH';
my $Dom = 'santannapisa.it';
my $PortCM = 106;
my $UserCM = 'postmaster';
my $cli = new CGP::CLI( { PeerAddr => $HostCM,
						  PeerPort => $PortCM,
						  login    => $UserCM,
						  password => $PwdCM } )
   || die "Can't login to CGPro: ".$CGP::ERR_STRING."\n";

# &get_comunigate($HostCM,$PortCM,$UserCM,$PwdCM);
# @@@ testare se la connessione ha funzionato

# ----------------------------------------------------------------------
# lista delle funzioni ammesse
# ----------------------------------------------------------------------
my $LF=['ABListLists','ABGetAccountInfo','ABListSubscribers','ABClonaLista','ABNuovaLista','ABCancellaLista','ABList','ABVerificaLista','ABListAccounts','ABGetList','ABUpdateList','ABListGroups','ABGetAccountLists','ABDeleteAccount','ABCreaAccountRedirect','ABCreaForwarder','ABGetForwarder'];

# $cli->CreateForwarder('john','john@external.site.com')
#        || die "Error: ".$cli->getErrMessage.", quitting";

# CreateAccount( accountName => name, [ settings => userData, accountType => accountType, externalFlag => externalFlag ] );
# Example:
#   my $UserData = {
#     AccessModes =>  [ qw(Mail POP IMAP PWD WebMail WebSite) ],
#     RealName => 'John X. Smith',
#     MaxAccountSize =>  '100K',
#   };
#   $cli->CreateAccount(accountName => 'john',settings => $UserData, )
#         || die "Error: ".$cli->getErrMessage.", quitting";
# 

# GetAccountMailRules(accountName)
# Example:
#   my $Rules=$cli->GetAccountMailRules('john')
#             || die "Error: ".$cli->getErrMessage.", quitting";
# 
#   foreach my $Rule (@$Rules) {
#     my $conditions=$Rule->[2],$actions=$Rule->[3];
#     print "\nName='$Rule->[1]' Priority=$Rule->[0]\n";
#     print "  If\n  ";
#     foreach my $cond (@$conditions) {
#       print " $_ " foreach (@$cond);
#       print "\n  ";
#     }
#     print "Then\n  ";
#     foreach my $actn (@$actions) {
#       print " $_ " foreach (@$actn);
#       print "\n  ";
#     }
#   }

# SetAccountMailRules(accountName,newRules)
# Example:
#   my @Rules =(
#   [ 5, '#Vacation',
#     [['Human Generated', '---']],
#     [['Reply with', "Sorry, I'm on vacation"]]
#   ],
#   [ 5,'Mark Important Messages',
#     [ ['Subject', 'is', '*important*'] ],
#     [ ['Mark', 'Flagged'],
#       ['FingerNotify', '123.45.67.89'],
#       ['Forward to', 'my@home.address']
#     ]
#   ]
#   );
# 
#   $cli->SetAccountMailRules('jonh',\@Rules)
#         || die "Error: ".$cli->getErrMessage.", quitting";

# GetMailboxAliases(accountName);
# Example:
#   if($Aliases=$cli->GetMailboxAliases('john')) {
#     print "The aliases are:\n";
#     foreach (keys %$Aliases) {
#       print " $_ = @$Aliases{$_}\n";
#     }
#   } else {
#      ($cli->isSuccess) ? print "No aliases\n"
#        : die "Error: ".$cli->getErrMessage.", quitting";
#   }

# SetMailboxAliases(accountName,newAliases);
# Example:
#   $Aliases=$cli->GetMailboxAliases('john')
#       || die "Error: ".$cli->getErrMessage.", quitting";
#   @$Aliases{'ExternalBox'}='~user@company.com/INBOX';
#   $cli->SetMailboxAliases('john',$Aliases)
#       || die "Error: ".$cli->getErrMessage.", quitting";
			
# ----------------------------------------------------------------------
# lista di tipi di output ammessi
# ----------------------------------------------------------------------
my $LO=['ABJson','ABHTML'];	# se non presente il default è ABJson

# ----------------------------------------------------------------------
# lista di tipi di output ammessi
# ----------------------------------------------------------------------
my $LM=['ABBase','ABEsteso'];	# se non presente il default è ABBase

# ----------------------------------------------------------------------
# cerco tra gli argomenti la funzione e il tipo di output
# ----------------------------------------------------------------------
my $F='';		# funzione (necessario)
my $O='ABJson';	# output di default
my $M='ABBase';	# modo di default
my @AR = ();
$n=0;
foreach $A (@ARGV) {
	if ($devel==1) {print $span0.'<br />'.$A.$span1;}
	$f=0;
	foreach $E (@$LF) {
		if ($A eq $E) {
			$F=$A;	# uno dei parametri è il nome funzione
			$f=1;
		}
	}
	foreach $E (@$LO) {
		if ($A eq $E) {
			$O=$A;	# uno dei parametri è il tipo di output
			$f=1;
		}
	}
	foreach $E (@$LM) {
		if ($A eq $E) {
			$M=$A;	# uno dei parametri è il modo
			$f=1;
		}
	}
	# se non riconosciuto come nome funzione o tipo output o modo lo lascio come argomento
	if ($f==0) {
		# if ($n==0) {
		# 	@AR = ($A);
		# } else {
			push(@AR, $A);
		# }
		++$n;
	}
}
# AR contiene la lista degli argomenti ripulita del nome funzione,del tipo output e del modo
if ($devel) {if ($n>0) {print $span0.'<br />--- AR<br />'.Dumper(@AR).$span1;}}
if ($devel) {print $span0.'<br />Funzione: '.$F.' - Output: '.$F.' - Modo: '.$M.$span1;}
if ($devel) {if ($F eq '') {print $span0.'<br />funzione mancante'.$span1;}}
# if ($log or $log_a) {
if ($log) {
	open(FILEHANDLE,">>".$logfile);
	if ($devel) {if ($n>0) {print FILEHANDLE $span0.'<br />--- AR<br />'.Dumper(@AR).$span1."\n";}}
	if ($devel) {print FILEHANDLE $span0.'<br />funzione: '.$F.' - Output: '.$O.$span1."\n";}
	if ($devel) {if ($F eq '') {print FILEHANDLE $span0.'<br />funzione mancante'.$span1."\n";}}
	close(FILEHANDLE);
}
# ----------------------------------------------------------------------
if ($F eq 'ABGetForwarder') { 
# ----------------------------------------------------------------------
# prevede 1 argomento 
#	arg[0] = nc (alberto.bongiorni) 			... deve esistere

	if(@AR) {
		
		my $userName=$AR[0];		# uid (@alumnisssup.mail.onmicrosoft.com)

		# $cli->CreateForwarder('john','john@external.site.com')
		#        || die "Error: ".$cli->getErrMessage.", quitting";

		$fo = $cli->GetForwarder($userName)
			|| die "Error: ".$cli->getErrMessage.", quitting";
		print $fo;
	}
}

# ----------------------------------------------------------------------
if ($F eq 'ABCreaForwarder') { 
# ----------------------------------------------------------------------
# prevede 1 argomento 
#	arg[0] = nc (alberto.bongiorni) 			... deve esistere

	if(@AR) {
		
		my $userName=$AR[0];		# uid (@alumnisssup.mail.onmicrosoft.com)

		# $cli->CreateForwarder('john','john@external.site.com')
		#        || die "Error: ".$cli->getErrMessage.", quitting";

		$cli->CreateForwarder($userName,"$userName\@alumnisssup.mail.onmicrosoft.com")
			|| die "Error: ".$cli->getErrMessage.", quitting";
		print "CreateForwarder $userName";
	}
}

# ----------------------------------------------------------------------
if ($F eq 'ABDeleteAccount') { 
# ----------------------------------------------------------------------
# prevede 1 argomento 
#	arg[0] = nc (a.bongiorni) 			... deve esistere

	if(@AR) {
		my $userName=$AR[0];		# uid
		$cli->DeleteAccount($userName)
			|| die "Error: ".$cli->getErrMessage.", quitting";
		print "DeleteAccount $userName";
	}
}

# ----------------------------------------------------------------------
if ($F eq 'ABCreaAccountRedirect') { 
# ----------------------------------------------------------------------
# prevede x argomenti 
#	arg[0] = uid (a.bongiorni) 					... deve esistere
#	arg[1] = nome (alberto) 						... deve esistere
#	arg[2] = cognome (bongiorni) 				... deve esistere
#	arg[3] = alias (alberto.bongiorni) 	... opzionale
#	arg[4] = size (100K) 								... opzionale

# todo: 
# 	prevedere altri campi oltre a nome cognome
#		creare l'alias (attualmente non funziona)
#		determinare lo spazio (minimo se ridirezionato)

	if(@AR) {
		
		$ne=$#AR + 1;
		if ($ne < 3) { # AR deve avere almeno i primi 4 parametri
			print "Error: too few parameters, quitting";
			die;
		}

		my $userName=$AR[0];		# uid
		my $nome=$AR[1];				# nome
		my $cognome=$AR[2];			# cognome

		if ($ne > 3) { # AR ha anche il quarto argomento (alias)
			$userext = $AR[3];
		} else {
			$userext = '';
		}

		# determino il size
		if ($ne > 4) { # AR ha anche il quinto argomento (size)
			$size = $AR[4];
		} else {
			$size = '100K';
		}
		print "<br>Size: $size";

		# preparo i dati da scrivere
		my $UserData = {
			AccessModes =>  [ qw(Mail POP IMAP PWD WebMail WebSite) ],
			RealName => $nome.' '.$cognome,
			MaxAccountSize =>  $size,
			givenName =>  $nome,
			Surname =>  $cognome,
		};
		
		# --- crea account
		$cli->CreateAccount(accountName => $userName,settings => $UserData, ) || die "Error: ".$cli->getErrMessage.", quitting";

		print "<br>CreateAccount: $userName";
		
		# --- crea redirect
		my $Rulesorig = $cli->GetAccountMailRules("$userName\@$Domain");
		foreach my $Rulex (@$Rulesorig) {
			my $actions=$Rulex->[3];
			foreach my $actn (@$actions) {
				my $a=$actn->[0];
		    	# print "$userName: '$a' -> $actn->[1]\n";
				if($a=~/Forward to|Redirect to|Mirror to/) {
					# print "$userName: '$a' -> $actn->[1]";
					$NewRules=$actn->[1].",$userext\@alumnisssup.mail.onmicrosoft.com";
					# print $NewRules;
					$act=$a;
				} 
			}
		} 
		my @Rules="";
		if ($NewRules eq ""){
			@Rules =(
				[ 1, '#Redirect',
					[],
					[
						['Mirror to', "$userext\@alumnisssup.mail.onmicrosoft.com"]
						,['Discard',"---" ]
					] 
				]
			);
		} else {
			@Rules =(
				[ 1, '#Redirect',
					[],
					[
						['Mirror to', $NewRules],
						['Discard', "---"]
					] 
				]
			);
		}
		$cli->SetAccountMailRules("$userName\@$Domain",\@Rules);
		print "<br>SetAccountMailRules";

		# --- crea alias
		if ($userext ne ''){
			# --- NON FUNZIONA
			my $aliasexist=0;
			$Aliases=$cli->GetMailboxAliases($userName);
			print "<br>GetMailboxAliases: $userName";
			if($Aliases) {
				foreach (keys %$Aliases) {
					print "<br>$_ = @$Aliases{$_}";
					if (@$Aliases{$_} eq $userext){
						$aliasexist=1;
					}
				}
			}
			print "<br>aliasexist: $aliasexist";
			if ($aliasexist eq 0){
				@$Aliases{'ExternalBox'}='~'.$userext.'@santannapisa.it/INBOX';
				 print "<br>aliases: $aliasexist";
				$cli->SetMailboxAliases("$userName\@$Domain",$Aliases)
					|| print "<br><strong>SetMailboxAliases Error: ".$cli->getErrMessage.", quitting</strong>";
			} else {
				print "<br>L'alias $userext esiste gi&agrave;";
			}
			print "<br>SetMailboxAliases: $userName $userext";
		}
	}
}



# ----------------------------------------------------------------------
if ($F eq 'ABListGroups') { 
# ----------------------------------------------------------------------
# non prevede argomenti - ritorna la lista dei gruppi
	$t=$cli->ListGroups($Domain);
	if ($devel) {
		print $span0.'<hr />'.Dumper($t).$span1;
		foreach $tt (@$t) {
			$ttt=$cli->GetGroup($tt);
			print $span0.'<hr /><strong>'.$tt.'</strong><br>'.Dumper(@$ttt{'Members'}).$span1;
		}
	}
	if ($O eq 'ABJson') {
		$n=0;
		$R="{\"GROUPS\": [";
		foreach $tt (@$t) {
			if ($n>0) {$R.=",";}
			$R.="\"".$tt."\"";
			$n++;
		}	
		$R.="]}";
		if ($devel) {print $span0.'<br />'.$span1;}
		print $R;
	}
}

# ----------------------------------------------------------------------
if ($F eq 'ABGetList') { 
# ----------------------------------------------------------------------
# prevede 1 argomento 
#	nome_lista ... deve esistere
	if(@AR) {
		$t0=$cli->GetList($AR[0]);
		if ($devel) {
			print $span0.'<hr />'.Dumper($t0).$span1;
		}
		if ($O eq 'ABHTML') {
#			print "The ".$AR[0]." settings are:\n";
			print '<hr>';
			foreach (keys %$t0) {
				print "<span style='color:green;'>$_</span> = \"@$t0{$_}\"<br />\n"
			}
#			print "<br /><span style='color:red;'>$_</span> = \"@$t0{$_}\"\n" foreach (keys %$t0);
			print '<br /><br />';
		}
	} else {
		if ($devel) {print $span0.'<br />Manca un parametro'.$span1;}
	}
}

# ----------------------------------------------------------------------
if ($F eq 'ABUpdateList') { 
# ----------------------------------------------------------------------
# prevede 3 argomenti
#	nome_lista ... deve esistere
# parametro 
#	valore
	if ($devel) {
		print $span0.'<br />--- AR<br />'.Dumper(@AR).$span1;
	}
	$ne=$#AR + 1;
	if ($ne > 2) { # AR deve avere ancora almeno tre argomenti
		my $nl=$AR[0];	# nome lista
		my $np=$AR[1];	# nome parametro
		my $vp=$AR[2];	# valore

		my $LP = ['PolicyText', 'WarningText', 'DigestMessageLimit', 'ListFields', 'TOCLine', 'WarningSubject', 'PolicySubject', 'SizeLimit', 'ArchiveSizeLimit', 'DigestHeader', 'ArchiveMessageLimit', 'TOCTrailer', 'Subscribe', 'FeedTrailer', 'ByeText', 'DigestSizeLimit', 'Charset', 'DigestSubject', 'Owner', 'ConfirmationSubject', 'ByeSubject'];	# lista dei parametri ammessi
		
		$f=0;
		foreach $E (@$LP) {
			if ($np eq $E) {
				$f=1;
			}
		}		
		if ($f==1) {
			if ($devel) {
				$t0=$cli->GetList($nl);
				print "<hr />prima";
				print "<br /><span style='color:red;'>$_</span> = \"@$t0{$_}\"\n" foreach (keys %$t0);
			}
			my $r = $cli->UpdateList($nl,{$np => $vp}) || die "Error: ".$cli->getErrMessage.", quitting";
			if ($devel) {
				$t0=$cli->GetList($nl);
				print "<hr />dopo";
				print "<br /><span style='color:red;'>$_</span> = \"@$t0{$_}\"\n" foreach (keys %$t0);
			}
		} else {
			if ($devel) {
				print $span0.'<br />'.$np.' = Parametro non riconosciuto'.$span1;
			}
		}
	} else {
		if ($devel) {
			print $span0.'<br />Manca un parametro'.$span1;
		}
	}
}

# ----------------------------------------------------------------------
if ($F eq 'ABListLists') { 
# ----------------------------------------------------------------------
# non prevede argomenti - ritorna la lista delle ML
# esempio di output -> {"ML":["docenti","PhDstudents","amministrativi"]}
	$t=$cli->ListLists($Domain);
	if ($devel) {
		print $span0.'<hr />'.Dumper($t).$span1;
		foreach $tt (@$t) {
			$ttt=$cli->ListSubscribers($tt);
			print $span0.'<hr /><strong>'.$tt.'</strong><br>'.Dumper($ttt).$span1;
		}
	}
	if ($O eq 'ABJson') {
		$n=0;
		$R="{\"ML\": [";
		foreach $tt (@$t) {
			if ($n>0) {$R.=",";}
			$R.="\"".$tt."\"";
			$n++;
		}	
		$R.="]}";
		if ($devel) {print $span0.'<br />'.$span1;}
		print $R;
	}
	if ($O eq 'ABHTML') {
		$R="<table>";
		$R.="<tr><th>Nome lista</th></tr>";
		foreach $tt (@$t) {
			$R.="<tr><td>".$tt."</td></tr>";
		}	
		$R.="</table>";
		if ($devel) {print $span0.'<br />'.$span1;}
		print $R;
	}
}
# ----------------------------------------------------------------------
if ($F eq 'ABListSubscribers') { 
# ----------------------------------------------------------------------
# prevede 1 argomento 
#	nome_lista ... deve esistere
	if(@AR) {
		$t0=$cli->ListSubscribers($AR[0]);
		if ($devel) {
			print $span0.'<hr />'.Dumper($t0).$span1;
		}
		if ($O eq 'ABJson') {
			$n=0;
			foreach $tt (@$t0) {
				$t1=$cli->GetSubscriberInfo($AR[0],$tt);
				if ($devel) {
					print $span0.'<br />'.$tt.' -> '.Dumper($t1).$span1;
				}
				if ($n==0) {
					@uid = ($tt);
					@info = @$t1{'mode'}
				} else {
					push(@uid, $tt);
					push(@info, @$t1{'mode'});
				}
				$n++;
			}	
			$n=0;
			$s1='';
			$s2='';
			foreach $u(@uid) {
				if ($n>0) {
					$s1.=",";
					$s2.=",";
				}
				$s1.="\"".$uid[$n]."\"";
				$s2.="\"".$info[$n]."\"";
				$n++;
			}
			$R="{\"UID\": [".$s1."],\"MODE\": [".$s2."]}";
			if ($devel) {print $span0.'<br />'.$span1;}
			print $R;
		}
		if ($O eq 'ABHTML') {
			$n=0;
			$R="<table>";
			$R.="<tr><th>Sottoscrittore</th><th>modo</th></tr>";
			foreach $tt (@$t0) {
				$t1=$cli->GetSubscriberInfo($AR[0],$tt);
				$R.="<tr><td>".$tt."</td><td>".@$t1{'mode'}."</td></tr>";
			}	
			$R.="</table>";
			if ($devel) {print $span0.'<br />'.$span1;}
			print $R;
		}
	} else {
		if ($devel) {print $span0.'<br />Manca un parametro'.$span1;}
	}
}
# ----------------------------------------------------------------------
if ($F eq 'ABClonaLista') { 
# ----------------------------------------------------------------------
# prevede 3 argomenti 
#	1. nome_lista (da clonare) ... deve esistere
#	2. nome_lista (da creare) ... non deve esistere
#	3. owner della nuova lista ... deve esistere l'account
	if ($log or $log_a) {
		open(FILEHANDLE,">>".$logfile);	# append
#		open(FILEHANDLE,">".$logfile);	# overwrite
		if ($log) {print FILEHANDLE "Argomenti funzione: (1 lista da clonare, 2 lista da creare, 3 owner) -> \n".Dumper(@AR);}
	}
	if ($O eq 'ABHTML') {$R='';}	
	$ne=$#AR + 1;
	if ($ne > 2) { # AR deve avere ancora almeno tre argomenti
		my $l1=$AR[0];	# lista da clonare
		my $l2=$AR[1];	# nome lista su cui copiare la lista l1
		my $onl=$AR[2];	# owner nuova lista
		if ($devel) {print $span0.'<br />--- AR<br />'.Dumper(@AR).$span1;}
		$t=$cli->ListLists($Domain);
		$tf=0;	# check
		foreach $tt (@$t) {
			if ($tt eq $l1) {
				$tf=1;	# lista da clonare trovata
				if ($devel) {print $span0.'<br />'.$l1.' esiste'.$span1;}
			}
		}	
		if ($tf eq 1) {
			foreach $tt (@$t) {
				if ($tt eq $l2) {
					$tf=2;
					if ($devel) {print $span0.'<br />'.$l2.' esiste già'.$span1;}
				}
			}	
		} else {
			if ($devel) {print $span0.'<br />'.$l1.' non esiste'.$span1;}
		}
		if ($tf eq 1) {
			if ($devel) {print $span0.'<br />'.$l2.' non esiste'.$span1;}
			$t2 = $cli->ListAccounts($Domain);
			if ($devel) {print $span0.'<br /> '.Dumper($t2).$span1;}
			if ($devel) {
				my $json = encode_json \%$t2;
				print $span0.'<br />'.$json.$span1;
			}
			if ($devel) {print $span0.'<br />inizio cerca '.$onl.$span1;}
			foreach $tt (sort keys %$t2) {
				if ($tt eq $onl) {
					$tf=3;	# owner trovato
					if ($devel) {print $span0.'<br />'.$onl.' esiste'.$span1;}
				}
			}	
			if ($devel) {print $span0.'<br />fine cerca '.$onl.$span1;}
		}
		if ($tf eq 1) {
			if ($devel) {print $span0.'<br />'.$onl.' non esiste'.$span1;}
		}
		if ($tf eq 3) {
			if ($cli->CreateList($l2,$onl)) {
				$R.='<br />'.$span0.$l2.' creata'.$span1;
				if ($devel) {print '<br />'.$span0.$l2.' creata'.$span1;}
				if ($log or $log_a) {print FILEHANDLE $l2." creata\n";}
			} else {
				$R.='<br />'.$span0.$l2.' creazione'.$span1." ==> Error: ".$cli->getErrMessage;
				if ($devel) {print '<br />'.$span0.$l2.' creazione ==> Error: '.$cli->getErrMessage.$span1;}
				if ($log or $log_a) {print FILEHANDLE $l2.' creazione ==> Error: '.$cli->getErrMessage."\n";}
			} 
			$ssML=$cli->ListSubscribers($l1);
			foreach $ss (@$ssML) {
				$ssi=$cli->GetSubscriberInfo($l1,$ss);
				$m=@$ssi{'mode'};
				if ($cli->List($l2, $m, $ss, 'silently')) {
					$R.='<br />'.$span0.$l2.' '.$m.' '.$ss.$span1;
					if ($devel) {print '<br />'.$span0.$l2.' '.$m.' '.$ss.$span1;}
					if ($log or $log_a) {print FILEHANDLE $l2.' '.$m.' '.$ss."\n";}
				} else {
					$R.='<br />'.$span0.$l2.' '.$m.' '.$ss.$span1." ==> Error: ".$cli->getErrMessage;
					if ($devel) {print '<br />'.$span0.$l2.' '.$m.' '.$ss." ==> Error: ".$cli->getErrMessage.$span1;}
					if ($log or $log_a) {print FILEHANDLE $l2.' '.$m.' '.$ss." ==> Error: ".$cli->getErrMessage."\n";}
				} 
			}
		}
	} else {
		if ($devel) {print $span0.'<br />numero elementi AR: '.$ne.$span1;}
	}
	if ($log or $log_a) {close(FILEHANDLE);}
	if ($O eq 'ABHTML') {print $R;}
}
# ----------------------------------------------------------------------
if ($F eq 'ABNuovaLista') { 
# ----------------------------------------------------------------------
# prevede 2 argomenti 
#	nome_lista ... deve esistere
#	owner ... deve esistere
	if ($log or $log_a) {
		open(FILEHANDLE,">>".$logfile);	# append
#		open(FILEHANDLE,">".$logfile);	# overwrite
#		print FILEHANDLE "Argomenti: -> \n".Dumper(@ARGV);
		if($log) {print FILEHANDLE "Argomenti funzione (1 lista, 2 owner): -> \n".Dumper(@AR);}
	}
	if ($O eq 'ABHTML') {$R='';}	
	$ne=$#AR + 1;
	if ($ne > 1) { # AR deve avere ancora almeno due argomenti
		my $l=$AR[0];	# lista da inserire
		my $o=$AR[1];	# owner
		$t=$cli->ListLists($Domain);
		$tf=1;	# check
		foreach $tt (@$t) {
			if ($tt eq $l) {
				$tf=0;	# lista da inserire esistente
				if ($devel) {print $span0.'<br />'.$l.' esiste già'.$span1;}
			}
		}	
		if ($tf eq 1) {
			if ($cli->CreateList($l,$o)) {
				# ... perde il case di $l --- neanche con RenameList
#				$ll=lc($l);
#				$ll1='z_'.$ll.'_temporanea';
#				$cli->RenameList($ll,$lll);
#				$cli->RenameList($lll,$l);
				$R.='<br />'.$span0.$l.' creata'.$span1;
				if ($devel) {print '<br />'.$span0.$l.' creata'.$span1;}
				if ($log or $log_a) {print FILEHANDLE $l." creata\n";}
			} else {
				$R.='<br />'.$span0.$l.' creazione'.$span1." ==> Error: ".$cli->getErrMessage;
				if ($devel) {print '<br />'.$span0.$l.' creazione ==> Error: '.$cli->getErrMessage.$span1;}
				if ($log or $log_a) {print FILEHANDLE $l.' creazione ==> Error: '.$cli->getErrMessage."\n";}
			} 
		}
	}
	if ($log or $log_a) {close(FILEHANDLE);}
	if ($O eq 'ABHTML') {print $R;}
}
# ----------------------------------------------------------------------
if ($F eq 'ABCancellaLista') { 
# ----------------------------------------------------------------------
# prevede 1 argomento 
#	nome_lista ... deve esistere
	if ($log or $log_a) {
		open(FILEHANDLE,">>".$logfile);	# append
#		open(FILEHANDLE,">".$logfile);	# overwrite
#		print FILEHANDLE "Argomenti: -> \n".Dumper(@ARGV);
		if($log) {print FILEHANDLE "Argomenti funzione (1 lista): -> \n".Dumper(@AR);}
	}
	if ($O eq 'ABHTML') {$R='';}	
	if(@AR) {
		my $l=$AR[0];	# lista da cancellare
		$t=$cli->ListLists($Domain);
		$tf=0;	# check
		foreach $tt (@$t) {
			if ($tt eq $l) {
				$tf=1;	# lista da cancellare trovata
				if ($devel) {print $span0.'<br />'.$l.' esiste'.$span1;}
			}
		}	
		if ($tf eq 1) {
			if ($cli->DeleteList($l)) {
				$R.='<br />'.$span0.$l.' cancellata'.$span1;
				if ($devel) {print '<br />'.$span0.$l.' cancellata'.$span1;}
				if ($log or $log_a) {print FILEHANDLE $l." cancellata\n";}
			} else {
				$R.='<br />'.$span0.$l.' cancellazione'.$span1." ==> Error: ".$cli->getErrMessage;
				if ($devel) {print '<br />'.$span0.$l.' cancellazione ==> Error: '.$cli->getErrMessage.$span1;}
				if ($log or $log_a) {print FILEHANDLE $l.' cancellazione ==> Error: '.$cli->getErrMessage."\n";}
			} 
		}
	}
	if ($log or $log_a) {close(FILEHANDLE);}
	if ($O eq 'ABHTML') {print $R;}
}
# ----------------------------------------------------------------------
if ($F eq 'ABList') { # sottoscrizione e cancellazione (unsubscribe) silenziosa
# ----------------------------------------------------------------------
# prevede 3/4 argomenti
#	nome_lista ... deve esistere
#	azione ... 'subscribe','unsubscribe','null','feed'
#	indirizzo mail ... se unsubscribe deve esistere nella lista
#	modo ... silently (non chiede conferma)
#	if ($log or $log_a) {
	open(FILEHANDLE,">>".$logfile);	# append
	if ($log) {
#		open(FILEHANDLE,">".$logfile);	# overwrite
#		print FILEHANDLE "Argomenti: -> \n".Dumper(@ARGV);
		if ($log) {print FILEHANDLE "Argomenti funzione (1 lista, 2 azione, 3 mail, 4 modo): -> \n".Dumper(@AR);}
	}
	$R='';
	$ne=$#AR + 1;
	if ($ne > 2) { # AR deve avere ancora almeno tre argomenti
		my $l=$AR[0];	# lista 
		my $a=$AR[1];	# azione
		my $m=$AR[2];	# indirizzo mail
		my $s='silently';	# silenzioso?
		if ($ne > 3) {  # è specificato anche il modo silenzioso
			$s=$AR[3];	# modo
		}
		my $do=1;
		if ($a eq 'unsubscribe') {  
			$do=0;
		}
		$ssML=$cli->ListSubscribers($l);
		foreach $ss (@$ssML) {
			if ($ss eq $m) {	# trovata sottoscrizione della mail indicata
				if ($a eq 'unsubscribe') {  
					$do=1;	# esiste ed è stato richiesto di cancellare
				} else {
					$ssi=$cli->GetSubscriberInfo($l,$ss);
					$mm=@$ssi{'mode'};
					if ($mm eq $a) {
						$do=0;	# esiste già (stesso modo) ed è stato richiesto di scrivere
					} else {
						$do=2;	# esiste già ed è stato richiesto di scrivere ma il modo è diverso
					}	
				}	
			}
		}
		if ($do eq 2) {
			if ($cli->List($l, 'unsubscribe', $m, $s)) {
				$R.='<br />'.$span0.$l.' unsubscribe '.$m.$span1;
				if ($devel) {print '<br />'.$span0.$l.' unsubscribe '.$m.$span1;}
				print FILEHANDLE $datestring.' '.$l.' unsubscribe '.$m."\n";
			} else {
				$R.='<br />'.$span0.$l.' unsubscribe '.$m.$span1." ==> Error: ".$cli->getErrMessage;
				if ($devel) {print '<br />'.$span0.$l.' unsubscribe '.$m." ==> Error: ".$cli->getErrMessage.$span1;}
				print FILEHANDLE $datestring.' '.$l.' unsubscribe '.$m." ==> Error: ".$cli->getErrMessage."\n";
			} 
			$do=1;
		}
		if ($do eq 1) {
			if ($cli->List($l, $a, $m, $s)) {
				if ($a eq 'unsubscribe') {
					$R.='<br />'.$span0.$l.' '.$a.' '.$m.$span1;
				} else {
					$R.='<br />'.$l.' '.$a.' '.$m;
				}
				if ($devel) {print '<br />'.$l.' '.$a.' '.$m;}
				print FILEHANDLE $datestring.' '.$l.' '.$a.' '.$m."\n";
			} else {
				$R.='<br />'.$l.' '.$a.' '.$m.$span0." ==> Error: ".$cli->getErrMessage.$span1;
				if ($devel) {print '<br />'.$l.' '.$a.' '.$m." ==> Error: ".$cli->getErrMessage;}
				print FILEHANDLE $datestring.' '.$l.' '.$a.' '.$m." ==> Error: ".$cli->getErrMessage."\n";
			} 
		}
	}
	if ($devel) {
		print FILEHANDLE $datestring.' $O: ('.$O.')   $R: ('.$R.")\n";
	}
	close(FILEHANDLE);
	if ($O eq 'ABHTML') {print $R;}
}
# ----------------------------------------------------------------------
if ($F eq 'ABVerificaLista') { # sottoscrizione e cancellazione (unsubscribe) silenziosa
# ----------------------------------------------------------------------
# prevede 2/3 argomenti
#	nome_lista ... deve esistere
#	lista ... nuovi da sottoscrivere (lista mail separati da ,)
#	modo ... (feed (default), null ...)
	if ($log or $log_a) {
		open(FILEHANDLE,">>".$logfile);	# append
#		open(FILEHANDLE,">".$logfile);	# overwrite
#		print FILEHANDLE "Argomenti: -> \n".Dumper(@ARGV);
		if ($log) {print FILEHANDLE "Argomenti funzione (1 lista, 2 mails, 3 modo): -> \n".Dumper(@AR);}
	}

	if ($O eq 'ABHTML') {$R='';}	
	$ne=$#AR + 1;
#	$log=0;

	my $modo='feed';	# modo di default
	if ($ne > 2) { 		# è stato passato anche il modo (feed (default), null ...)
		$modo=$AR[2];
	}
	if( not grep $_ eq $modo, ('feed', 'null') ) {	# modi accettati
		if ($log or $log_a) {
			print FILEHANDLE 'Modo non gestito: -> '.$modo."\n";
			close(FILEHANDLE);
		}
		return;	# esco
	} else {
		if ($log) {
			print FILEHANDLE 'Modo: -> '.$modo."\n";
		}
	}

	if ($ne > 1) { # AR deve avere ancora almeno due argomenti
		my $l=$AR[0];	# lista 
		my $j=$AR[1];	# lista mail separate da , (sostituiranno quelle sottoscritte con $modo)
		$ssML=$cli->ListSubscribers($l);
		my @jj = split /,/, $j;		# array delle mail da scrivere
#		if ($log) {print FILEHANDLE "Lista mail da controllare: -> \n".Dumper(@jj);}
		# ----------------------------------------------------- unsubscribe
		foreach $ss (@$ssML) {
			$ssi=$cli->GetSubscriberInfo($l,$ss);
			$mm=@$ssi{'mode'};
#			if ($log) {print FILEHANDLE 'attuale --> '.$l.' '.$ss.' '.$mm."\n";}
			if ($mm eq $modo) {
				# controllo che non sia tra quelli da riscrivere
				$do=1;
				foreach $sss (@jj) {
					if (lc(trim($sss)) eq lc(trim($ss))){
						if ($log) {print FILEHANDLE 'lascio ---> '.$l.' '.$ss."\n";}
						$do=0;
					}
				}
#				if ($log) {print FILEHANDLE $do.' execute List --> '.$l.' unsubscribe '.$ss."\n";}
				if ($do){
					# la if successiva permette la gestione dell'errore
					if ($cli->List($l, 'unsubscribe', $ss, 'silently')) {
						if ($log or $log_a) {print FILEHANDLE $l.' unsubscribe '.$ss."\n";}
						$R.='<br />'.$span0.$l.' unsubscribe '.$ss.$span1;
						if ($devel) {print '<br />'.$span0.$l.' unsubscribe '.$ss.$span1;}
					} else {
						if ($log or $log_a) {print FILEHANDLE $l.' unsubscribe '.$ss." ==> Error: ".$cli->getErrMessage."\n";}
						$R.='<br />'.$span0.$l.' unsubscribe '.$ss.$span1." ==> Error: ".$cli->getErrMessage;
						if ($devel) {print '<br />'.$span0.$l.' unsubscribe '.$ss." ==> Error: ".$cli->getErrMessage.$span1;}
					} 
				}
			}
		}
		# ----------------------------------------------------- feed o null ($modo)
		foreach $sss (@jj) {
			# controllo che non sia tra i feed/null esistenti
			$do=1;
			foreach $ss (@$ssML) {
				$ssi=$cli->GetSubscriberInfo($l,$ss);
				$mm=@$ssi{'mode'};
				if ($mm eq $modo) {
					if (lc(trim($sss)) eq lc(trim($ss))){
						if ($log) {print FILEHANDLE 'esistente ---> '.$l.' '.$ss."\n";}
						$do=0;
					}
				}
			}
#			if ($log) {print FILEHANDLE $do.' execute List --> '.$l.' '.$modo.' '.$sss."\n";}
			if ($do){
				# la if successiva permette la gestione dell'errore
				if($cli->List($l, $modo, $sss, 'silently')) {
					if ($log or $log_a) {print FILEHANDLE $l.' '.$modo.' '.$sss."\n";}
					$R.='<br />'.$l.' '.$modo.' '.$sss;
					if ($devel) {print '<br />'.$l.' '.$modo.' '.$sss;}
				} else {
					if ($log or $log_a) {print FILEHANDLE $l.' '.$modo.' '.$sss." ==> Error: ".$cli->getErrMessage."\n";}
					$R.='<br />'.$l.' '.$modo.' '.$sss." ==> Error: ".$cli->getErrMessage;
					if ($devel) {print '<br />'.$l.' '.$modo.' '.$sss." ==> Error: ".$cli->getErrMessage;}
				}
			}
		}
	}
	if ($log or $log_a) {close(FILEHANDLE);}
	if ($O eq 'ABHTML') {print $R;}
}
# ----------------------------------------------------------------------
if ($F eq 'ABGetAccountInfo') {	# informazioni di un account
# ----------------------------------------------------------------------
# prevede 1 argomento
#	uid ... deve esistere
	if(@AR) {
		my $AR0=$AR[0];
		$log=0;
		if ($log) {		
			open(FILEHANDLE,">>pl_log.txt");
			print FILEHANDLE "\n========================== PERL";
			print FILEHANDLE "\n -- ".$AR0;
			print FILEHANDLE "\n -- ".$AR[0];
			print FILEHANDLE "\n -- ".Dumper(@AR);
			print FILEHANDLE "\n -- ".Dumper(@ARGV);
		}
		$ll=$cli->ListLists($Domain);	# lista delle ML	
		$t0=$cli->GetAccountEffectiveSettings("$AR0\@$Domain");
		$t1=$cli->GetAccountInfo("$AR0\@$Domain");
		$t2=$cli->GetAccountAliases("$AR0\@$Domain");
		$tg=$cli->ListGroups($Domain);	# lista dei gruppi
		
		# --- serializzo la lista degli alias
		$t2s='';
		foreach $tt (@$t2) {
			if ($t2s eq '') {
			} else {
				$t2s.=',';
			}
			$t2s.=$tt;
		}

		if ($log) {		
			print FILEHANDLE "\n".'lista alias: '.$t2s;
		}

		# --- trasformo la lista degli alias (+ l'account) in un array per controllarne la presenza nelle ML e nei gruppi
		@aea = ($AR0);
		push(@aea,split(',', $t2s));

		# --- cerco l'appartenenza alle ML 
		$t5s='';
		foreach $ttt (@$ll) {										# $ttt nome della ML
			$subML=$cli->ListSubscribers($ttt);		# subscribers della lista
			foreach $tttt (@$subML) {							# loop sui membri della lista
				my $mm=substr($tttt, 0, index($tttt, '@'));
				foreach $t2ai (@aea) {							# loop su arrai account + alias
					if ($mm eq $t2ai) {			# se il sottoscrittore corrisponde all'account inserisco la lista tra le ML cui appartiene
						if ($log) {		
							print FILEHANDLE "\n".'account: '.$AR[0].' lista: '.$ttt.' - membro: '.$tttt.' ('.$mm.') - alias: '.$t2ai;
						}
						if ($t5s eq '') {
						} else {
							$t5s.=",";
						}
						$t5s.=$ttt;
					}
				}
			}
		}

		if ($log) {		
			close(FILEHANDLE);
		}
	
		# --- catturo le appartenenze a gruppi
		# $n=0;
		$t6s='';
		foreach $tgx (@$tg) {						# loop sui gruppi ($tgx = nome del gruppo)
			$tgd=$cli->GetGroup($tgx);		# dettagli di un gruppo
			$tgl = @$tgd{'Members'};			# catturo i membri del gruppo
			foreach $tgi (@$tgl) {				# loop sui membri del gruppo
				foreach $t2ai (@aea) {			# loop su arrai account + alias
					if ($tgi eq $t2ai) {
						if ($t6s eq '') {
						} else {
							$t6s.=',';
						}
						$t6s.=$tgx;
					}
				}
			}
		}

		# ML di cui è owner
		$t7=$cli->GetAccountLists("$AR[0]\@$Domain");	
		# --- stringfy delle ML di cui è owner
		$t7s='';
		foreach $ttt (keys %$t7) {
			if ($t7s eq ''){
			} else {
				$t7s.=',';
			}
			$t7s.=$ttt;
		}
			
		if ($devel) {
			print $span0;
			print '<hr />'.Dumper($t0);
			print '<hr />'.Dumper($t1);
			print '<hr />'.Dumper($t2);
			print $span1;
		}
		if ($O eq 'ABJson') {
			$R="{";
			$R.='"UID": ["'.$AR[0].'"]';
			$R.=',"COGNOME": ["'.((length @$t0{'Surname'})?@$t0{'Surname'}:'').'"]';
			$R.=',"NOME": ["'.((length @$t0{'givenName'})?@$t0{'givenName'}:'').'"]';
			$R.=',"SPAZIO": ["'.((length @$t0{'MaxAccountSize'})?@$t0{'MaxAccountSize'}:'').'"]';
			$R.=',"CLASSE": ["'.((length @$t0{'ServiceClass'})?@$t0{'ServiceClass'}:'').'"]';
			$R.=',"OCCUPATO": ["'.((length @$t0{'StorageUsed'})?@$t1{'StorageUsed'}:'').'"]';
			$R.=',"ULTIMO_ACCESSO": ["'.((length @$t0{'LastLogin'})?@$t1{'LastLogin'}:'').'"]';
			$R.=',"ALIASES": ["'.$t2s.'"]';
			$R.=',"ML": ["'.$t5s.'"]';
			$R.=',"GRUPPI": ["'.$t6s.'"]';
			$R.=',"MLOWN": ["'.$t7s.'"]';
			$R.="}";
			if ($devel) {print $span0.'<br />'.$span1;}
			print $R;
		}
		if ($O eq 'ABHTML') {
			$R="<table>";
			$R.="<tr><th>UID</th><th>COGNOME</th><th>NOME</th><th>SPAZIO</th><th>CLASSE</th><th>OCCUPATO</th><th>ULTIMO_ACCESSO</th><th>ALIASES</th><th>LISTS</th><th>GRUPPI</th><th>ML OWN</th></tr>";
			$R.="<tr><td>".$AR[0]."</td><td>".@$t0{'Surname'}."</td><td>".@$t0{'givenName'}."</td><td>".@$t0{'MaxAccountSize'}."</td><td>".@$t0{'ServiceClass'}."</td><td>".@$t1{'StorageUsed'}."</td><td>".@$t1{'LastLogin'}."</td><td>".$t2s."</td><td>".$t5s."</td><td>".$t6s."</td><td>".$t7s."</td></tr>";
			$R.="</table>";
			if ($devel) {print $span0.'<br />'.$span1;}
			print $R;
		}
	} else {
		if ($devel) {print $span0.'<br />Manca un parametro'.$span1;}
	}
}
# ----------------------------------------------------------------------
if ($F eq 'ABListAccounts') { # informazioni di tutti gli account
# ----------------------------------------------------------------------
# prevede 0 argomenti
#	if ($log) {
#		open(FILEHANDLE,">>pl_log.txt");
		open(FILEHANDLE,">pl_log.txt");
		print FILEHANDLE "\n\n========================== PERL \n";
		print FILEHANDLE "--> \n".Dumper($F);
#		close(FILEHANDLE);
#	}
	my $n = 0;
	my $t0 = $cli->ListAccounts($Domain);	# lista degli account del dominio
	die "\nError " . $cli->getErrMessage . "(".$cli->getErrCode.") fetching accounts list\n" unless ($t0);
	my $ll=$cli->ListLists($Domain);	# lista delle ML
	my $tg=$cli->ListGroups($Domain);	# lista dei gruppi
	# if (@$t0) {
		foreach $tt (sort keys %$t0) {
			if ($M eq 'ABEsteso') {
				$accountData = $cli->GetAccountEffectiveSettings("$tt\@$Domain");
				$Surname = @$accountData{'Surname'} || '';
				$givenName = @$accountData{'givenName'} || '';
		#		$realName = @$accountData{'RealName'} || '';
				$maxSize = @$accountData{'MaxAccountSize'} || '';
				$ServiceClass = @$accountData{'ServiceClass'} || '';
				$storageUsed=$cli->GetAccountInfo("$tt\@$Domain",'StorageUsed') || '-empty-';
				$lastAccess=$cli->GetAccountInfo("$tt\@$Domain",'LastLogin') || '-not yet-';
				$listAliases=$cli->GetAccountAliases("$tt\@$Domain");
				$listMLO=$cli->GetAccountLists("$tt\@$Domain");								# ML di cui è owner
				
				# --- stringfy degli aliases
				$listAliasesString='';
				foreach $ttt (@$listAliases) {
					if ($listAliasesString eq '') {
					} else {
						$listAliasesString.=',';
					};
					$listAliasesString.=$ttt;
				};

				# --- stringfy delle ML di cui è owner
				$listMLOString='';
				foreach $ttt (keys %$listMLO) {
					if ($listMLOString eq ''){
					} else {
						$listMLOString.=',';
					};
					$listMLOString.=$ttt;
				};
				
				# --- trasformo la lista degli alias (+ l'account) in un array per controllarne la presenza nelle ML e nei gruppi
				@aea = ($tt);
				push(@aea,split(',', $listAliasesString));	# push(@t2a,$t2);
				
				# --- catturo le appartenenze a gruppi
				$n=0;
				$listGroupsString='';
				foreach $ttt (@$tg) {					# loop sui gruppi ($ttt = nome del gruppo)
					$tgd=$cli->GetGroup($ttt);			# dettagli di un gruppo
					$tgl = @$tgd{'Members'};			# catturo i membri del gruppo
					foreach $tgi (@$tgl) {				# loop sui membri del gruppo
						foreach $t2ai (@aea) {			# loop su arrai account + alias
							if ($tgi eq $t2ai) {
								if ($listGroupsString eq '') {
								} else {
									$listGroupsString.=',';
								};
								$listGroupsString.=$ttt;
							};
						};
					};
				};
				
				# --- cerco l'appartenenza alle ML 
				$listMLString='';
				foreach $ttt (@$ll) {	# $ttt nome della ML
					$subML=$cli->ListSubscribers($ttt);		# subscribers della lista
					foreach $tttt (@$subML) {				# loop sui membri della lista
						foreach $t2ai (@aea) {				# loop su arrai account + alias
							if ($tttt eq $t2ai) {			# se il sottoscrittore corrisponde all'account inserisco la lista tra le ML cui appartiene
								if ($listMLString eq '') {
								} else {
									$listMLString.=",";
								};
								$listMLString.=$ttt;
							};
						};
					};
				};
				print FILEHANDLE "--> (".$tt.") (".$Surname.") (".$givenName.") (".$maxSize.") (".$ServiceClass.") (".$storageUsed.") (".$lastAccess.") (".$listAliasesString.") (".$listMLOString.") (".$listGroupsString.") (".$listMLString.") \n";
			} else {
				print FILEHANDLE "--> (".$tt.") \n";
			}


			if ($n==0) {
				@uid = ($tt);
				if ($M eq 'ABEsteso') {
					@as = ($Surname);
					@ag = ($givenName);
		#			@rn = ($realName);
					@ms = ($maxSize);
					@sc = ($ServiceClass);
					@su = ($storageUsed);
					@la = ($lastAccess);
					@al = ($listAliasesString);
					@mlo =($listMLOString);
					@gr = ($listGroupsString);
					@ml = ($listMLString);
				};
			} else {
				push(@uid, $tt);
				if ($M eq 'ABEsteso') {
					push(@as, $Surname);
					push(@ag, $givenName);
		#			push(@rn, $realName);
					push(@ms, $maxSize);
					push(@sc, $ServiceClass);
					push(@su, $storageUsed);
					push(@la, $lastAccess);
					push(@al, $listAliasesString);
					push(@mlo, $listMLOString);
					push(@gr, $listGroupsString);
					push(@ml, $listMLString);
				};
			};
			$n++;
		};	
	# };
	close(FILEHANDLE);

	if ($O eq 'ABJson') {
		$n=0;
		$s1='';
		if ($M eq 'ABEsteso') {
			$s2='';
			$s3='';
			$s4='';
			$s5='';
			$s6='';
			$s7='';
			$s8='';
			$s9='';
			$s10='';
			$s11='';
		};
		foreach $u(@uid) {
			if ($n>0) {
				$s1.=",";
				if ($M eq 'ABEsteso') {
					$s2.=",";
					$s3.=",";
					$s4.=",";
					$s5.=",";
					$s6.=",";
					$s7.=",";
					$s8.=",";
					$s9.=",";
					$s10.=",";
					$s11.=",";
				};
			};
			$s1.="\"".$uid[$n]."\"";
			if ($M eq 'ABEsteso') {
				$s2.="\"".$as[$n]."\"";
				$s3.="\"".$ag[$n]."\"";
				$s4.="\"".$ms[$n]."\"";
				$s5.="\"".$su[$n]."\"";
				$s6.="\"".$la[$n]."\"";
				$s7.="\"".$al[$n]."\"";
				$s8.="\"".$sc[$n]."\"";
				$s9.="\"".$mlo[$n]."\"";
				$s10.="\"".$gr[$n]."\"";
				$s11.="\"".$ml[$n]."\"";
			};
			$n++;
		};
		$R="{\"UID\": [".$s1."]";
		if ($M eq 'ABEsteso') {
			$R.=",\"COGNOME\": [".$s2."],\"NOME\": [".$s3."],\"SPAZIO\": [".$s4."],\"OCCUPATO\": [".$s5."],\"ULTIMO_ACCESSO\": [".$s6."],\"ALIASES\": [".$s7."],\"CLASSE\": [".$s8."],\"MLOWN\": [".$s9."],\"GRUPPI\": [".$s10."],\"ML\": [".$s11."]";
		};
		$R.="}";
		print $R;
	};
	if ($O eq 'ABHTML') {
		$n=0;
		$R="<table>";
		$R.="<tr><th>Account</th>";
		if ($M eq 'ABEsteso') {
			$R.="<th>Cognome</th><th>Nome</th><th>Spazio</th><th>Usato</th><th>Ultimo accesso</th><th>Aliases</th><th>Classe</th><th>Liste Owner</th><th>Gruppi</th><th>Liste</th>";
		};
		$R.="</tr>";
		$n=0;
		foreach $u(@uid) {
			$R.="<tr><td>".$uid[$n]."</td>";
			if ($M eq 'ABEsteso') {
				$R.="<td>".$as[$n]."</td><td>".$ag[$n]."</td><td>".$ms[$n]."</td><td>".$su[$n]."</td><td>".$la[$n]."</td><td>".$al[$n]."</td><td>".$sc[$n]."</td><td>".$mlo[$n]."</td><td>".$gr[$n]."</td><td>".$ml[$n]."</td>";
			};
			$R.="</tr>";
			$n++;
		};	
		$R.="</table>";
		print $R;
	};
};
# --------------------------------------------------------------------
# subroutines
# --------------------------------------------------------------------
### sub get_comunigate ($HostCM,$PortCM,$UserCM,$PwdCM) {
### # @_
### # Result of a subroutine is always the last thing evaluated. 
### 	$cli = new CGP::CLI( { PeerAddr => $_[0],
### 	                       PeerPort => $_[1],
### 	                       login    => $_[2],
### 	                       password => $_[3] } )
### 	   || die "Can't login to CGPro: ".$CGP::ERR_STRING."\n";
### 
### #	return $cli;
### }
sub str_replace {my ($from,$to,$string) = @_;$string =~s/$from/$to/ig;return $string;}
sub ltrim { my $s = shift; $s =~ s/^\s+//;       return $s }
sub rtrim { my $s = shift; $s =~ s/\s+$//;       return $s }
sub  trim { my $s = shift; $s =~ s/^\s+|\s+$//g; return $s }
### sub ab_list ($l, $a, $m, $s) {
### 	$cli->List($l, $a, $m, $s) || die print "Error: ".$cli->getErrMessage.", quitting";	# ok
### }
