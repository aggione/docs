<?php
// https://dotnetu.local/ldap/check.php
	
// cron di dotnetu (sudo su) (sudo crontab -u www-data -e)
// # m h  dom mon dow   command
// 59 */8  *  *  * /usr/bin/php /var/www/html/ldap/check.php >> /var/log/check.log 2>&1
// 0 */8  *  *  * /usr/bin/php /var/www/html/rac/aggiorna_cmg_bkp.php >> /var/log/aggiorna_cmg_bkp.log 2>&1
// 10 0 * * * /usr/bin/php /var/www/html/triage/registro_presenze/allievi.php?toc=YlnTD5ESSpWYB1TPjg2Y0RjQ&func=invgio&soll=0 >> /var/log/pic.log 2>&1
// 10 15 * * * /usr/bin/php /var/www/html/triage/registro_presenze/allievi.php?toc=YlnTD5ESSpWYB1TPjg2Y0RjQ&func=invgio&soll=1 >> /var/log/pic.log 2>&1
	

	session_start();
	$app='CHECK_LDAP_ML';
	// include_once('../rac/_comuni/comune.php');	// === non importato per lancio php da cron
	// include_once('_comuni/comune.php');
	$ip=getIpAddress();
	$logf=checklog($app);
	// tolog('Start');

	$devel=false;
	$tempo_inattivo_massimo=round(30*60);	// in secondi (30 minuti)
	// $tempo_minimo_tra_login=round(5);	// in secondi (5 secondi)
	$ora=round(microtime(true));					// tempo attuale in secondi
	$ip=getIpAddress();

	$echo=1;
	$out='';

	if (!isset($_SESSION)){session_start();};
	$time = $_SERVER['REQUEST_TIME'];
	$timeout_duration = 1800;	// timeout, specified in seconds (30 minuti)
	if (isset($_SESSION['LAST_ACTIVITY']) && ($time - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
		session_unset();    
		session_destroy();
		session_start();    
	}
	$_SESSION['LAST_ACTIVITY'] = $time;
	@set_time_limit(600);	// tempo massimo esecuzione dello script (10 minuti)

	// --- connessione ad LDAP
	$auterr="";
	$ds = ldap_connect('192.168.64.11',389);
	if (!$ds) {
		$auterr.="Non posso connettermi al server di autenticazione LDAP ";
	} else {
		ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
		ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);
//		$ldapBind = ldap_bind($ds,'uid='.$uid_login.',ou=Users,o=sss,c=it',$password_login
		$ldapBind = ldap_bind($ds,'cn=manager,o=sss,c=it','fugasse');
		if (!$ldapBind) {$auterr.="Credenziali di autenticazione (LDAP) errate ";};
	}
	if($auterr!='') {
		if ($echo) {echo $auterr;}
		exit;
	}

	$sql="select can.*, d.* from sss_import.ab_can can, sss_import.ab_didorgsedi d where can.ka=d.uo(+) and can.ldap_uid is not null and d.typ='org' order by can.cognome, can.nome";
	$u='SSS_IMPORT'; $p='ugovimport'; $c='SSS-DB'; $conn=getconn($u,$p,$c,'o'); $r=load_dbx($conn,$sql,'o',true,'m');
	$ro=$r['data']; $nc=$r['colonne']; $nr=$r['righe'];

	aggiorna_dn($ds,$ro);
	aggiorna_groupsintranet($ds,$ro);
	ldap_unbind($ds);
	error_clear_last();
	mail_list_automatiche();
	
//	mail_utf8('a.bongiorni@santannapisa.it,s.fermo@santannapisa.it','Alberto','a.bongiorni@santannapisa.it','Check LDAP',$out);
	mail_utf8('a.bongiorni@santannapisa.it','Alberto','a.bongiorni@santannapisa.it','Check LDAP e ML',$out);

	function aggiorna_dn($ds,$ro){
		global $echo,$out;

		$out.='Autenticato come manager '.date("Y-m-d H:i:s")."<br>";
		
		// --- aggiornamento di meta 
		$cosa='dn';			
		$branches=array('unitorg','organizzazione');
		for ($k=0; $k < count($branches); $k++) {
			$bra=$branches[$k];
			$base="ou=$bra,ou=meta,o=sss,c=it";	// 'ou=Users,o=sss,c=it' 'ou=meta,o=sss,c=it'
			if($echo) {echo '<br><br>--- check '.$base;}
			$out.='<br><br>--- check '.$base."\n";
			/*	// FILTRI
				=      - matches exact value
				=*xxx  - matches values ending xxx
				=xxx*  - matches values beginning xxx
				=*xxx* - matches values containing xxx
				=*     - matches all values (if set - NULLS are not returned)
				>=xxx  - matches everthing from xxx to end of directory
				<=xxx  - matches everything up to xxx in directory
				~=xxx  - matches similar entries (not all systems)
				--- Boolean operators for constructing complex search
				&(term1)(term2)  						- matches term1 AND term2
				|(term1)(term2)  						- matches term1 OR term2
				!(term1)            					- matches NOT term1
				&(|(term1)(term2))(!(&(term1)(term2)) 	- matches XOR term1 term2
			*/		
			$p='(|(uid=a.bongiorni)(uid=a.signorini))';	// OK
			// $p='uid=a.bongiorni';	
			// $p='departmentNumber=005730';
			$p='uid=*';
			$result=ldap_search($ds, $base, $p, array($cosa));
			if ($result) {
				$info = ldap_get_entries($ds, $result);
			}

			$ndc=0; $ndi=0; $ntr=0;	// ndc = da cancellare, ndi = da inserire, ntr = trovati
			for ($i=0; $i < $info["count"]; $i++) {
				$tf='<span style="color:red;"> non trovato - da cancellare</span>';
				for ($j=0; $j < count($ro); $j++) {
					$r=$ro[$j]['DN1'];
					$r=str_replace("ou=organizzazione", "ou=$bra", $r);
					$r=str_replace("ou=sedi", "ou=$bra", $r);
					$r=str_replace("ou=unitorg", "ou=$bra", $r);
					$r=str_replace("ou=didattica", "ou=$bra", $r);
					$can='uid='.$ro[$j]['LDAP_UID'].','.$r;
					if ($info[$i][$cosa]==$can){
						$tf=" trovato DN1 ($j)";
					}
		//			if ($info[$i][$cosa]=='uid='.$ro[$j]['LDAP_UID'].','.$ro[$j]['DN']){
		//				$tf=" trovato DN ($j)";
		//			}
				}
				if (strpos($tf, 'cancellare')) {
					if ($echo) {echo "<br>".$info[$i][$cosa].' => '.$tf;}
					$out.="<br>".$info[$i][$cosa].' => '.$tf;
					$ndc++;
					$c=$info[$i][$cosa];
					$item=ldap_read($ds, $c, "(objectclass=*)");
					$entry = ldap_get_entries($ds, $item);
					$ee=$entry;
					$iUid=$entry[0]['uid'][0];
					$iUU=$entry[0]['aliasedobjectname'][0];
					ldap_delete ($ds, $info[$i][$cosa]);
					// --- visualizza il livello superiore
					// $item=ldap_read($ds, substr($c, strpos($c, ',')+1), "(objectclass=*)");
					// $ed = ldap_get_entries($ds, $item);
				};
			}

			for ($j=0; $j < count($ro); $j++) {
				if ($ro[$j]['LDAP_UID']!='' && strpos($ro[$j]['DN1'], 'ou=meta,o=sss,c=it')){
					$r=$ro[$j]['DN1'];
					$r=str_replace("ou=organizzazione", "ou=$bra", $r);
					$r=str_replace("ou=sedi", "ou=$bra", $r);
					$r=str_replace("ou=unitorg", "ou=$bra", $r);
					$r=str_replace("ou=didattica", "ou=$bra", $r);
					$can='uid='.$ro[$j]['LDAP_UID'].','.$r;
					$tf='<span style="color:green;"> non trovato - da inserire</span>';
					for ($i=0; $i < $info["count"]; $i++) {
						if ($info[$i][$cosa]==$can){
							$tf=" trovato DN1 ($j)";
						}
					}
					if (strpos($tf, 'inserire')) {
						if ($echo) {echo "<br>".$can.' => '.$tf;}
						$out.="<br>".$can.' => '.$tf;
						$ndi++;
						$aon='uid='.$ro[$j]['LDAP_UID'].',ou=users,o=sss,c=it';
						// --- lettura di controllo dell'utente nel branch Users
						// $item=ldap_read($ds, $aon, "(objectclass=*)");
						// $e = ldap_get_entries($ds, $item);
						
						// --- letture di controllo del branch che lo deve contenere
						$liv=$ro[$j]['LIVELLO'];
						for ($z=1; $z <= $liv; $z++) {
							$s='';
							for ($w=$z; $w > 0; $w--) {
								$s.='ou='.$ro[$j]['L'.($w)].',';
							}
							$s.=$base;
							try {
								$item=ldap_read($ds, $s, "(objectclass=*)");
								if (!$item) {
									$a=array();
									$a['ou']=$ro[$j]['L'.($z)];
									$a['objectclass'][0]='organizationalUnit';
									$a['objectclass'][1]='top';
									// --- get descrizione del livello
									$sql="select d.* from sss_import.ab_didorgsedi d where uo='".$ro[$j]['L'.($z)]."' and typ='org'";
									$u='SSS_IMPORT'; $p='ugovimport'; $c='SSS-DB'; $conn=getconn($u,$p,$c,'o'); $r1=load_dbx($conn,$sql,'o',true,'m');
									$ro1=$r1['data']; $nc1=$r1['colonne']; $nr1=$r1['righe'];
									$a['description']=$ro1[0]['DESCR'];
									ldap_add($ds ,$s ,$a);
									ldap_get_option($ds, LDAP_OPT_DIAGNOSTIC_MESSAGE, $err);
								} else {
									// ok esiste
									$e = ldap_get_entries($ds, $item);
								}
							} catch (Exception $err) {
							}		
						}
						$entry = Array();
						$entry['objectclass'][0]='alias';
						$entry['objectclass'][1]='extensibleObject';
						$entry['objectclass'][2]='top';
						$entry['aliasedobjectname'][0]=$aon;
						$entry['uid'][0]=$ro[$j]['LDAP_UID'];
						ldap_add($ds ,$can ,$entry);
						ldap_get_option($ds, LDAP_OPT_DIAGNOSTIC_MESSAGE, $err);
					} else {
						$ntr++;
					};
				}
			}
			if($echo) {
				echo '<br>da cancellare => <span style="color:red;">'.$ndc.'</span>';
				echo '<br>da inserire => <span style="color:green;">'.$ndi.'</span>';
				echo '<br>corretti => <span style="color:blue;">'.$ntr.'</span>';
			}
			$out.='<br>da cancellare => <span style="color:red;">'.$ndc."</span>\n".'da inserire => <span style="color:green;">'.$ndi."</span>\n".'corretti => <span style="color:blue;">'.$ntr."</span>\n";
		}		
	}
	function aggiorna_groupsintranet($ds,$ro){
		global $echo,$out;
		// --- aggiornamento di GroupsIntranet
		$cosa='dn';			
		if ($echo) {echo '<br><br>--- check GroupsIntranet';}
		$out.='<br><br>--- check GroupsIntranet'."\n";
		$ndc=0; $ndi=0; $ntr=0;	// ndc = da cancellare, ndi = da inserire, ntr = trovati
		$branches=array('AllieviMagistrali','AllieviMaster_1','AllieviMaster_2','AllieviOrd_1','AllieviOrd_2','AllieviPerf','Assegnisti','Borsisti','Collaboratori','Dirigenti','DocentiEsterni','Dottorandi','LavoratoriAutonomi','PersonaleTA','Professionisti','ProfessoriAss','ProfessoriOrd','RicercatoriTD','RicercatoriTI');
		for ($k=0; $k < count($branches); $k++) {
			$bra=$branches[$k];
			$base="cn=$bra,ou=GroupsIntranet,o=sss,c=it";	// 'ou=Users,o=sss,c=it' 'ou=meta,o=sss,c=it'
			$p='memberuid=*';
			$result=ldap_search($ds, $base, $p, array($cosa));
			if ($result) {
	//			$info = ldap_get_entries($ds, $result);

				// $userdn = getDN($ds, $user, $base);
				$result = ldap_read($ds, $base, $p);
				if ($result) {
					// echo '<br>--- '.$base;
					$entries = ldap_get_entries($ds, $result);
					if ($entries['count']>0){
						for ($h=0; $h < $entries[0]['memberuid']['count']; $h++) {
							$u=$entries[0]['memberuid'][$h];
							$tf='<span style="color:red;"> non trovato - da cancellare</span>';
							for ($y=0; $y < count($ro); $y++) {
								if ($u=='uid='.$ro[$y]['LDAP_UID'].',ou=Users,o=sss,c=it' and $base=='cn='.$ro[$y]['GROUPS_INTRANET'].',ou=GroupsIntranet,o=sss,c=it') {
									$tf=" trovato ($y)";
								}
							}
							if (strpos($tf, 'cancellare')) {
								if ($echo) {echo '<br>'.$bra.' => '.$entries[0]['memberuid'][$h].' '.$tf;}
								$out.='<br>'.$bra.' => '.$entries[0]['memberuid'][$h].' '.$tf."\n";
								$ndc++;
								$removal = array("memberuid"=>$u);
								ldap_mod_del($ds, $base, $removal);
							}
						}
					}
				}
			}
		}
		for ($y=0; $y < count($ro); $y++) {
			if ($ro[$y]['GROUPS_INTRANET']!='' and $ro[$y]['LDAP_UID']!=''){
				$bra=$ro[$y]['GROUPS_INTRANET'];
				$base="cn=$bra,ou=GroupsIntranet,o=sss,c=it";
				$p='memberuid=*';
				$tf='<span style="color:green;"> non trovato - da inserire</span>';
				$result=ldap_search($ds, $base, $p, array($cosa));
				if ($result) {
					$result = ldap_read($ds, $base, $p);
					if ($result) {
						$entries = ldap_get_entries($ds, $result);
						if ($entries['count']>0){
							for ($h=0; $h < $entries[0]['memberuid']['count']; $h++) {
								$u=$entries[0]['memberuid'][$h];
								if ($u=='uid='.$ro[$y]['LDAP_UID'].',ou=Users,o=sss,c=it') {
									$tf=" trovato ($y)";
								}
							}
						}
					}
				}
				if (strpos($tf, 'inserire')) {
					if($echo) {echo '<br>'.$bra.' => '.'uid='.$ro[$y]['LDAP_UID'].',ou=Users,o=sss,c=it '.$tf;}
					$out.='<br>'.$bra.' => '.'uid='.$ro[$y]['LDAP_UID'].',ou=Users,o=sss,c=it '.$tf."\n";
					$ndi++;
					$insert = array("memberuid"=>'uid='.$ro[$y]['LDAP_UID'].',ou=Users,o=sss,c=it');
					ldap_mod_add($ds, $base, $insert);
					// ---
				} else {
					$ntr++;
				}
			}
		}
		if($echo) {
			echo '<br>da cancellare => <span style="color:red;">'.$ndc.'</span>';
			echo '<br>da inserire => <span style="color:green;">'.$ndi.'</span>';
			echo '<br>corretti => <span style="color:blue;">'.$ntr.'</span>';
		}
		$out.='<br>da cancellare => <span style="color:red;">'.$ndc."</span>\n".'da inserire => <span style="color:green;">'.$ndi."</span>\n".'corretti => <span style="color:blue;">'.$ntr."</span>\n";
		
	}
	function mail_list_automatiche(){
		global $echo,$out;
		// controlla e sistema le ML automatiche
		$sql = 'select * from sss_import.TBMAILIST where attivo=1 order by NOME_LISTA';
		$u='SSS_IMPORT'; $p='ugovimport'; $c='SSS-DB'; $conn=getconn($u,$p,$c,'o'); $mla_list=load_dbx($conn,$sql,'o',true,'m');
		$out.='<br><hr>Mailing Lists:<hr>';
		foreach ($mla_list['data'] as $i){
			$o=verificaLista($i);
			if($echo) {echo $o.'<br>';}
			$out.=$o;
		}
	}
	function verificaLista($x) {				// 	verifica di una singola lista
		global $echo;
		$oo='<hr><strong>'.$x['NOME_LISTA'].'</strong><br>';

		$lista=$x['NOME_LISTA'];
		$sql_feed=trim($x['QUERY']);
		$sql_null=trim($x['QUERY2']);
		$s_fix=str_replace(' ','',trim($x['LISTAFIX']));
		$s='';
		
		// feed
		$sf='';
		if ($sql_feed!=''){
			$u='SSS_IMPORT'; $p='ugovimport'; $c='SSS-DB'; $conn=getconn($u,$p,$c,'o'); $r=load_dbx($conn,$sql_feed,'o',true,'o');
			$ro=$r['data']; $rj=$r['json']; $nc=$r['colonne']; $nr=$r['righe'];
			$sf=implode(',',$r['data']['MAIL']);
		}

		// null
		$sn=$s_fix;
		if ($sql_null!=''){
			$u='SSS_IMPORT'; $p='ugovimport'; $c='SSS-DB'; $conn=getconn($u,$p,$c,'o'); $r=load_dbx($conn,$sql_null,'o',true,'o');
			$ro=$r['data']; $rj=$r['json']; $nc=$r['colonne']; $nr=$r['righe'];
			$s=implode(',',$r['data']['MAIL']);
			if ($s_fix!=''){$sn.=',';}
			$sn.=$s;
		}

		$af=explode(",", str_replace(' ','',$sf));	// lista dei feed
		unset($af[""]);
		$an=explode(",", str_replace(' ','',$sn));	// lista dei null
		unset($an[""]);
		$a=array_merge($af,$an);							// lista di tutti gli account che devono esistere
		$a=array_diff( $a, array("") );	// unset($a[""]);
		$jasubs=shell_exec('/usr/bin/perl /var/www/html/ldap/AB.pl ABListSubscribers '.$lista);	// attualmente sottoscritti
		$asubs=json_decode($jasubs, true);	

		// loop sugli esistenti per correggere i mode errati o cancellare se non più richiesti
		$n=count($asubs['UID']);
		for ($j = 0; $j < $n; $j++) {
			if (in_array($asubs['UID'][$j], $a)){ 			// se esiste
				if (in_array($asubs['UID'][$j], $af)){		// deve essere feed
					if ($asubs['MODE'][$j]!='feed'){				// modo sbagliato
						$oo.=shell_exec('/usr/bin/perl /var/www/html/ldap/AB.pl ABList ABHTML '.$lista.' feed '.$asubs['UID'][$j]);
					}
				} else {																	// deve essere null
					if ($asubs['MODE'][$j]!='null'){				// modo sbagliato
						$oo.=shell_exec('/usr/bin/perl /var/www/html/ldap/AB.pl ABList ABHTML '.$lista.' null '.$asubs['UID'][$j]);
					}
				}
			} else {																		// non esiste CANCELLO
				if ($asubs['MODE'][$j] == 'unsubscribe'){
				} else {
					if (trim($sn,' ') == '' && $asubs['MODE'][$j] == 'null') {
					} else {
						$oo.=shell_exec('/usr/bin/perl /var/www/html/ldap/AB.pl ABList ABHTML '.$lista.' unsubscribe '.$asubs['UID'][$j]);
					}
				}
			}
		}

		// loop sui richiesti se non già esistenti vengono inseriti
		$n=count($a);
		for ($j = 0; $j < $n; $j++) {
			if (!in_array($a[$j], $asubs['UID'])){		// non esiste INSERISCO
				if (in_array($a[$j], $af)){							// deve essere FEED
					$oo.=shell_exec('/usr/bin/perl /var/www/html/ldap/AB.pl ABList ABHTML '.$lista.' feed '.$a[$j]);
				} else {																// deve essere null
					$oo.=shell_exec('/usr/bin/perl /var/www/html/ldap/AB.pl ABList ABHTML '.$lista.' null '.$a[$j]);
				}
			}
		}
		return $oo;
	}	
	function mail_utf8($to,$from_user,$from_email,$subject='(No subject)',$message='',$style='') {
		// dentro $to ci può essere: (esempi)
			// 'a.bongiorni@santannapisa.it'
			// 'a.bongiorni@santannapisa.it, s.fermo@santannapisa.it'
			// 'Alberto <a.bongiorni@santannapisa.it>, Simonetta <s.fermo@santannapisa.it>'
			// "Alberto <a.bongiorni@santannapisa.it>,\r\nCc: Simonetta <s.fermo@santannapisa.it>"
			// "Alberto <a.bongiorni@santannapisa.it>,\r\nBcc: Simonetta <s.fermo@santannapisa.it>"
		$nl="\r\n";
		$from_user = "=?UTF-8?B?".base64_encode($from_user)."?=";
		$subject = "=?UTF-8?B?".base64_encode($subject)."?=";
		$headers = "From: $from_user <$from_email>".$nl."MIME-Version: 1.0".$nl."Content-type: text/html; charset=UTF-8".$nl;
		return mail($to, $subject, $message, $headers);
	}	

// === le funzioni che seguono sono in sostituzione del file /rac/_comuni/comune.php
// === non importato per lancio php da cron
function checklog($app){
	if ($app=='RAC'){$logf='log/'.$app.'_'.date("Ym").'.log';}
	else {$logf='../_comuni/log/'.$app.'_'.date("Ym").'.log';}
	$logf=$app.'_'.date("Ym").'.log';
	if (!file_exists($logf)) {	// creo il file di log se non esiste
		$myfile = fopen($logf,"w") or die("Unable to open file!");
		fwrite($myfile,"");
		fclose($myfile);
		chmod($logf, 0777);
		tolog("creato il file di log $logf");
	};
	return $logf;
}
function to_json($o){
	global $logf;
	$r=json_encode($o);
	$e=json_last_error();
	switch ($e) {
		case JSON_ERROR_NONE:	// No error has occurred 	 
			break;
		case JSON_ERROR_DEPT:	
			file_put_contents($logf,"--- JSON The maximum stack depth has been exceeded\r\n", FILE_APPEND );
			break;
		case JSON_ERROR_STATE_MISMATCH:
			file_put_contents($logf,"--- JSON Invalid or malformed JSON\r\n", FILE_APPEND );
			break;
		case JSON_ERROR_CTRL_CHAR:
			file_put_contents($logf,"--- JSON Control character error, possibly incorrectly encoded\r\n", FILE_APPEND );
			break;
		case JSON_ERROR_SYNTAX:
			file_put_contents($logf,"--- JSON Syntax error\r\n", FILE_APPEND );
			break;
		case JSON_ERROR_UTF8:
			file_put_contents($logf,"--- JSON Malformed UTF-8 characters, possibly incorrectly encoded\r\n", FILE_APPEND );
			break;
		case JSON_ERROR_RECURSION:
			file_put_contents($logf,"--- JSON One or more recursive references in the value to be encoded\r\n", FILE_APPEND );
			break;
		case JSON_ERROR_INF_OR_NAN: 	
			file_put_contents($logf,"--- JSON One or more NAN or INF values in the value to be encoded\r\n", FILE_APPEND );
			break;
		case JSON_ERROR_UNSUPPORTED_TYPE:
			file_put_contents($logf,"--- JSON A value of a type that cannot be encoded was given\r\n", FILE_APPEND );
			break;
		case JSON_ERROR_INVALID_PROPERTY_NAME:
			file_put_contents($logf,"--- JSON A property name that cannot be encoded was given\r\n", FILE_APPEND );
			break;
		case JSON_ERROR_UTF16:
			file_put_contents($logf,"--- JSON Malformed UTF-16 characters, possibly incorrectly encoded\r\n", FILE_APPEND );
			break;
	}
	return $r;
}
function getconn($u,$p,$c,$t){	
	// ritorna una connessione permanente 	
	// 		esempio: $conn_UGOV_SIAIE=getconn('SIAIE_SSSUP_PROD','p3n6w0xa!',$ao['UGOV'],'o');
	// 			oci_pconnect( "utente", "password", "easy o SID (TNS_ENTRY)");
	// 			oci_pconnect('hr', 'welcome', 'localhost/XE');
	// 			oci_pconnect( string $username , string $password [, string $connection_string [, string $character_set [, int $session_mode ]]] )
	// 				session_mode = OCI_DEFAULT, OCI_SYSOPER and OCI_SYSDBA.
	// global $logf;
	global $devel;
	switch ($t) {
		case 'o':	
			// oracle
			$a = array(	// array di connessioni per oracle
				'CIA1'=>'(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=ccscia.sssup.it)(PORT=1521))(CONNECT_DATA=(SERVICE_NAME=CIA1)))'
				,'SISDB'=>'(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=sisdb.local)(PORT=1521))(CONNECT_DATA=(SERVICE_NAME=SISSSS)))'
				,'SISDBTEST'=>'(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=sisdbtest.local)(PORT=1521))(CONNECT_DATA=(SERVICE_NAME=SISTEST)))'
				,'DOTNET'=>'(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=dotnet.local)(PORT=1521))(CONNECT_DATA=(SERVICE_NAME=LORADOTN)))'
				,'SSS'=>'(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=192.168.64.119)(PORT=1521))(CONNECT_DATA=(SERVICE_NAME=SSS)))'
				,'DW'=>'(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=192.168.64.33)(PORT=1521))(CONNECT_DATA=(SERVICE_NAME=DWSSSA)))'
				,'HRSERV'=>'(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=192.168.64.143)(PORT=1521))(CONNECT_DATA=(SID=ORCL)))'
				,'HRSERV-NEW'=>'(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=192.168.64.143)(PORT=1521))(CONNECT_DATA=(SID=ORCL)))'
				,'SSS-DB'=>'(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=192.168.64.24)(PORT=1521))(CONNECT_DATA=(SERVICE_NAME=UGOV)))'
				,'SSSUP_PREPROD'=>'(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=130.186.81.138)(PORT=5555))(CONNECT_DATA=(SERVICE_NAME=ugov_sssup_preprod_ext.cineca.it)))'
				,'SSSUP_PROD'=>'(DESCRIPTION = (ADDRESS_LIST = (LOAD_BALANCE = ON) (FAILOVER = ON)(ADDRESS=(PROTOCOL=tcp)(HOST=cman01-ext.dbc.cineca.it)(PORT=5555))(ADDRESS=(PROTOCOL=tcp)(HOST=cman02-ext.dbc.cineca.it)(PORT=5555)))(CONNECT_DATA = (SERVICE_NAME = ugov_sssup_prod_ext.cineca.it )))'
				,'IRIS_SSSUP_PREPROD_EXT'=>'(DESCRIPTION=(ADDRESS_LIST=(FAILOVER=on)(LOAD_BALANCE=on)(ADDRESS=(PROTOCOL=TCP)(HOST=cman01-ext.dbc.cineca.it)(PORT=5555))(ADDRESS=(PROTOCOL=TCP)(HOST=cman02-ext.dbc.cineca.it)(PORT=5555)))(CONNECT_DATA=(SERVICE_NAME=IRIS_SSSUP_PREPROD_ext.cineca.it)))'
				,'IRIS_PROD'=>'(DESCRIPTION=(ADDRESS_LIST=(FAILOVER=on)(LOAD_BALANCE=on)(ADDRESS=(PROTOCOL=TCP)(HOST=cman01-ext.dbc.cineca.it)(PORT=5555))(ADDRESS=(PROTOCOL=TCP)(HOST=cman02-ext.dbc.cineca.it)(PORT=5555)))(CONNECT_DATA=(SERVICE_NAME=IRIS_SSSUP_PROD_ext.cineca.it)))'
				,'ORACLESRV'=>'(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=192.168.64.103)(PORT=1521))(CONNECT_DATA=(SERVICE_NAME=UGOV)))'
				,'ORAXEWIN10'=>'(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=192.168.64.140)(PORT=1521))(CONNECT_DATA=(SID=XE)))'
			); 
			if (substr($c,0,12)!='(DESCRIPTION'){	// stringa di connessione
				if (array_key_exists($c, $a)) {
					$c=$a[$c];		// nome nell'array delle connessioni
				} else {
					return false;		// no stringa di connessione e no in array delle connessioni
				}
			}
			try {
				$r = oci_pconnect($u,$p,$c,'AL32UTF8');	// in utf8
				return $r;
				if (!$r) {
				  $e = oci_error();
					if ($devel){log_on_error('Oracle Connection failed: ' . $e['message']);} else {tolog('Oracle Connection failed: ' . $e['message']);}
				}				
			} catch (PDOException $e) {
				if ($devel){log_on_error('Oracle Connection failed: ' . $e->getMessage());} else {tolog('Oracle Connection failed: ' . $e->getMessage());}
				return false;
			}
			break;
		case 'p':	
			// potgres
			$a = array(	// array di connessioni per Postgress
				'PG SIS'=>'host=sisdb-n.local port=5432 dbname=sis'
				,'PG local'=>'host=localhost port=5432 dbname=albo'
				,'PG idb2'=>'host=idb2.local port=5432 dbname=sssdb'
				,'PG albo toshiba'=>'host=10.1.106.194 port=5432 dbname=albo'
				// ,'PG albo dotnetu'=>'host=192.168.64.107 port=5432 dbname=albo'
				,'PG albo dotnetu'=>'host=localhost port=5432 dbname=albo'
				,'PG biblio'=>'host=192.168.64.7 port=5432 dbname=sssdb'
			);	
			if (substr($c,0,5)!='host='){	// stringa di connessione
				if (array_key_exists($c, $a)) {
					$c=$a[$c];		// nome nell'array delle connessioni
				} else {
					return false;		// no stringa di connessione e no in array delle connessioni
				}
			}
			try {
				$r = pg_connect($c." user=$u"." password=$p");	// pg_connect("host=sheep port=5432 dbname=mary user=lamb password=foo");
				return $r;
			} catch (PDOException $e) {
				if ($devel){log_on_error('Postgres Connection failed: ' . $e->getMessage());} else {tolog('Postgres Connection failed: ' . $e->getMessage());}
				return false;
			}
			break;
		case 'm':
			// MySQL
			$a = array(	// array di connessioni per MySQL
				'sss_import'=>array('h'=>'localhost','db'=>'sss_import')
			);	
			if (gettype($c)!='object' && gettype($c)!='array'){	// "boolean" "integer" "double" "string" "array" "object" "resource" "NULL" "unknown type"
				if (array_key_exists($c, $a)) {
					$c=$a[$c];		// nome nell'array delle connessioni
				} else {
					return false;		// no stringa di connessione e no in array delle connessioni
				}
			}
			try {
				$r = new mysqli($c['h'], $u, $p, $c['db']);	// mysqli_connect(host,username,password,dbname,port,socket);
				return $r;
			} catch (PDOException $e) {
				if ($devel){log_on_error('MySQL Connection failed: ' . $e->getMessage());} else {tolog('MySQL Connection failed: ' . $e->getMessage());}
				return false;
			}
			break;
		case 'l':
			// LDAP o AD	ldap_connect ([ string $host = NULL [, int $port = 389 ]] )
			$a = array(	// array di connessioni per LDAP / AD
				'LDAP'=>array('u'=>'cn=manager,o=sss,c=it','p'=>'fugasse','s'=>'192.168.64.11','porta'=>389,'t'=>'ou=Users,o=sss,c=it')
				,'AD'=>array('u'=>'Administrator','p'=>'aDuNo2016tt','s'=>'192.168.64.81','porta'=>389,'t'=>'ou=OpenLdap,dc=sssapisa,dc=it')
			);
			if (gettype($c)!='object' && gettype($c)!='array'){	// "boolean" "integer" "double" "string" "array" "object" "resource" "NULL" "unknown type"
				if (array_key_exists($c, $a)) {
					$c=$a[$c];		// nome nell'array delle connessioni
				} else {
					return false;		// no stringa di connessione e no in array delle connessioni
				}
			}
			try {
				$r = ldap_connect($c['s'],$c['porta']);	//  or die("Could not connect to LDAP server.");
				if ($r) {
					ldap_set_option($r, LDAP_OPT_PROTOCOL_VERSION, 3);
					ldap_set_option($r, LDAP_OPT_REFERRALS, 0);
					return $r;
				// ] else {	
				//	die('Cannot Connect to LDAP server');
				};
			} catch (PDOException $e) {
				if ($devel){log_on_error('LDAP Connection failed: ' . $e->getMessage());} else {tolog('LDAP Connection failed: ' . $e->getMessage());}
				return false;
			}
			break;
	};
}
function load_dbx($conn,$sql,$dbt='o',$tolog=true,$fetch='o'){
	$a=load_db($conn,$sql,$dbt='o',$tolog,$fetch);
	$b=array('data'=>$a[2],'json'=>json_encode($a[2]),'righe'=>$a[1],'colonne'=>$a[0]);
	return $b;
}
function load_db($conn,$sql,$dbt='o',$tolog=true,$fetch='o'){		// dbt: o=oracle, p=Postgres, m=MySQL
	global $out, $ip, $logf, $develerr;

	if (empty($fetch)){$fetch=$dbt;}
//	error_reporting(0);
	$nc=0;	// colonne
	$nr=0;	// righe
	$result='';	
//	if ($tolog) {tolog('load_db - sql: '.$sql);}
	$ret=true;
	$noret=['insert','delete','update','truncate','drop'];
	$a=explode(" ",$sql);
	for ($i = 0; $i < count($a); $i++) {
		if (in_array(strtolower($a[$i]), $noret)){$ret=false;}
		//  for ($j = 0; $j < count($noret); $j++) {
		//  	if (strtoupper($a[$i]) == strtoupper($noret[$j])){$ret=false;}
		//  }
	}
	$e=false;
	$ee='';
	switch ($dbt) {
		case 'o':	
			try{
				$parsed = oci_parse($conn, $sql);		// https://www.php.net/manual/en/function.oci-parse.php
				$ee=print_r(error_get_last(),true);
				if ($ee!=''){tolog('OCI parse error: '.$sql.' - oci_error: '.$ee);$ee='';error_clear_last();}
			} catch(Throwable $e) {
					$trace = $e->getTrace();
					$m = $e->getMessage().' in '.$e->getFile().' on line '.$e->getLine().' called from '.$trace[0]['file'].' on line '.$trace[0]['line'];
					tolog('OCI parse error: '.$sql.' - oci_error: '.$m);
					return;
			}
			try{
				$r = oci_execute($parsed); 				// https://www.php.net/manual/en/function.oci-execute.php
				$ee=print_r(error_get_last(),true);
				if ($ee!=''){tolog('OCI parse error: '.$sql.' - oci_error: '.$ee);error_clear_last();}
			} catch(Throwable $e) {
					$trace = $e->getTrace();
					$m = $e->getMessage().' in '.$e->getFile().' on line '.$e->getLine().' called from '.$trace[0]['file'].' on line '.$trace[0]['line'];
					tolog('OCI execute error: '.$sql.' - oci_error: '.$m);
					return;
			}
		
//			$parsed = oci_parse($conn, $sql);		// https://www.php.net/manual/en/function.oci-parse.php
//			$e = oci_error($conn);  						// https://www.php.net/manual/en/function.oci-error.php
//			if ($e) {
//   			log_on_error('OCI parse error: '.$sql.' - oci_error: '.$e['message']);
//			} else {
//				$r = oci_execute($parsed); 				// https://www.php.net/manual/en/function.oci-execute.php
//				$e = oci_error($conn);  
//			}
//			if ($e) {
//				log_on_error('OCI execute error: '.$sql.' - oci_error: '.$e['message']);
//			} else {
				if ($ret){
					try {
						if ($fetch=='o'){$nr = oci_fetch_all($parsed, $result);}
						if ($fetch=='m'){$nr = oci_fetch_all($parsed, $result, null, null, OCI_FETCHSTATEMENT_BY_ROW);} // come mysql
//						$nr = oci_fetch_all($parsed, $result);	// https://www.php.net/manual/en/function.oci-fetch-all.php numero righe
						$ee=print_r(error_get_last(),true);
						if ($ee!=''){tolog('OCI parse error: '.$sql.' - oci_error: '.$ee);$ee='';error_clear_last();}
						$nc = oci_num_fields($parsed);					// https://www.php.net/manual/en/function.oci-num-fields.php numero delle colonne
						$ee=print_r(error_get_last(),true);
						if ($ee!=''){tolog('OCI parse error: '.$sql.' - oci_error: '.$ee);$ee='';error_clear_last();}
						if($tolog) {tolog('righe: '.$nr.' - colonne: '.$nc.' - SQL ==> '.$sql);}
					} catch(Throwable $e) {
							$trace = $e->getTrace();
							$m = $e->getMessage().' in '.$e->getFile().' on line '.$e->getLine().' called from '.$trace[0]['file'].' on line '.$trace[0]['line'];
							tolog('ERRORE: '.$sql.' - oci_error: '.$m);
							return;
//					} catch (Exception $e) {
//						log_on_error('ERRORE: '.$e->getMessage().' - SQL ==> '.$sql);
					}
				} else {
					try {
						$nr = oci_num_rows ($parsed);							// righe affette da insert, update, delete 
						$ee=print_r(error_get_last(),true);
						if ($ee!=''){tolog('OCI parse error: '.$sql.' - oci_error: '.$ee);$ee='';error_clear_last();}
						if($tolog) {tolog('righe: '.$nr.' - SQL ==> '.$sql);}
					} catch(Throwable $e) {
							$trace = $e->getTrace();
							$m = $e->getMessage().' in '.$e->getFile().' on line '.$e->getLine().' called from '.$trace[0]['file'].' on line '.$trace[0]['line'];
							tolog('ERRORE: '.$sql.' - oci_error: '.$m);
							return;
//					} catch (Exception $e) {
//						log_on_error('ERRORE: '.$e->getMessage().' - SQL ==> '.$sql);
					}
				}
//			}
			break;
		case 'p':	
			$parsed = pg_query($conn, utf8_encode($sql));
			if (!$parsed) {
				$e =  pg_last_error($conn);
				if ($devel){log_on_error('pg_query error: '.$sql.' - pg_last_error: '.$e['message']);} else {tolog('pg_query error: '.$sql.' - pg_last_error: '.$e['message']);}
			} else {	
				$result = pg_fetch_all($parsed);
				if (!$result) {
					$e = pg_last_error($conn);  // For oci_parse errors pass the connection handle
					if ($devel){log_on_error('pg_fetch_all error: '.$sql.' - pg_last_error: '.$e['message']);} else {tolog('pg_fetch_all error: '.$sql.' - pg_last_error: '.$e['message']);}
				} else {
					try {
						$nc = pg_num_fields($parsed);	// numero delle colonne (fields)
						$nr = pg_num_rows($parsed);
					} catch (Exception $e) {
						if ($devel){log_on_error('ERRORE: '.$e->getMessage().' - SQL ==> '.$sql);} else {tolog('ERRORE: '.$e->getMessage().' - SQL ==> '.$sql);}
					}
				}
			}
			break;
		case 'm':	
			break;
	};
//	error_reporting($develerr);
	if ($ret){
		return array($nc,$nr,$result);	// nc=numero colonne, nr=numero righe, result=array dati
	}
}
function ocitocsv($conn, $sql) {
	$parsed = oci_parse($conn, $sql);
	oci_execute($parsed);
	$ncols = oci_num_fields($parsed);
	$nrows = oci_fetch_all($parsed, $results);
	$out='';
	if ($nrows > 0) {
		$out='';
		for ($i = 1; $i <= $ncols; $i++) {
		    $c_name  = oci_field_name($parsed, $i);
		    $c_type  = oci_field_type($parsed, $i);
		    $c_size  = oci_field_size($parsed, $i);
		    if ($i > 1) {$out .= ';';};
			$out .= '"'.$c_name.'"';
		}
	  $out .= " \n";
		for ($j = 0; $j < $nrows; $j++) {
			for ($i = 1; $i <= $ncols; $i++) {
			    $c_name = oci_field_name($parsed, $i);
			    $c_type = oci_field_type($parsed, $i);
			    $c_size = oci_field_size($parsed, $i);
			    if ($i > 1) {$out .= ';';};
				$item=$results[$c_name][$j];
				switch ($c_type) {
					case 'DATE':
						$out .= '"'.$item.'"';
						break;
					case 'VARCHAR2':
						$out .= '"'.$item.'"';
						break;
					case 'NUMBER':
						if ($item==0) {$item='0';};	// ???
						$out .= $item;
						break;
					default:
						$out .= '"'.$item.'"';
				}
			}
		  $out .= " \n";
		}
	};
	return $out;
}
function getIpAddress() {															// IP
	$ip = ""; 
	$v=array('HTTP_X_FORWARDED_FOR','HTTP_CLIENT_IP','REMOTE_ADDR');
	$o=array();
	foreach ($v as $p0) {
		$p1='';
		if($_SERVER) {
			if(isset($_SERVER[$p0])){$p1=$_SERVER[$p0];}
		} else {
			if(getenv($p0)){$p1=getenv($p0);}
		}
		if ($p1!='' and !in_array($p1, $o)) {
			if ($ip!=""){$ip.=" - ";};
			$ip.=$p1;
			array_push($o,$p1);
		}
	}
	
	//  if($_SERVER) {
	//  	if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))	{if ($ip!=""){$ip.=" - ";}; $ip.=$_SERVER['HTTP_X_FORWARDED_FOR'];}
	//  	if(isset($_SERVER['HTTP_CLIENT_IP']))				{if ($ip!=""){$ip.=" - ";}; $ip.=$_SERVER['HTTP_CLIENT_IP'];}
	//  	if(isset($_SERVER['REMOTE_ADDR']))					{if ($ip!=""){$ip.=" - ";}; $ip.=$_SERVER['REMOTE_ADDR'];}
	//  } else {
	//  	if(getenv('HTTP_X_FORWARDED_FOR'))					{if ($ip!=""){$ip.=" - ";}; $ip.=getenv('HTTP_X_FORWARDED_FOR');}
	//  	if(getenv('HTTP_CLIENT_IP'))								{if ($ip!=""){$ip.=" - ";}; $ip = getenv('HTTP_CLIENT_IP');}
	//  	if(getenv('REMOTE_ADDR'))										{if ($ip!=""){$ip.=" - ";}; $ip = getenv('REMOTE_ADDR');}
	//  }
	return $ip;
}
function log_on_error($msg){
	global $logf, $func;
	if ($msg != ''){tolog($msg);}
	// file_put_contents($logf,"--- DUMP DOPO ERRORE --- \r\n",FILE_APPEND);
	file_put_contents($logf,"--- SERVER \r\n",FILE_APPEND);
	foreach ($_SERVER as $key => $value) {
		if (is_array($value)){$v=json_encode($value);} else {$v=$value;}
		file_put_contents($logf,"    $key -> $v\r\n",FILE_APPEND);
	};	
	file_put_contents($logf,"--- SESSION \r\n",FILE_APPEND);
	foreach ($_SESSION as $key => $value) {
		if (is_array($value)){$v=json_encode($value);} else {$v=$value;}
		file_put_contents($logf,"    $key -> $v\r\n",FILE_APPEND);
	};	
	file_put_contents($logf,"--- REQUEST \r\n",FILE_APPEND);
	foreach ($_REQUEST as $key => $value) {	// _REQUEST contiene _GET o _POST
		if (is_array($value)){$v=json_encode($value);} else {$v=$value;}
		file_put_contents($logf,"    $key -> $v\r\n",FILE_APPEND);
	};

}
function tolog($messaggio){
	global $ip,$logf,$app; 
	if (empty($app)){$app='_generico';}
	if (empty($logf)){$logf=checklog($app);}
	if (empty($ip)){$ip=getIpAddress();}
	$uid='######';
	if (isset($_SESSION['uid_login'])){$uid=$_SESSION['uid_login'];}
	$napp=getcwd();
	file_put_contents($logf, $napp.' - ' . date("Ymd His").' - ip: '.$ip.' - uid: '.$uid.' - '.$messaggio."\r\n", FILE_APPEND | LOCK_EX);
}	
function cidr2range($ipv4){
	if ($ip=strpos($ipv4,'/')){
		$n_ip=(1<<(32-substr($ipv4,1+$ip)))-1;   
		$ip_dec=ip2long(substr($ipv4,0,$ip)); 
	}	else {
		$n_ip=0;
		$ip_dec=ip2long($ipv4);             
	}
	$ip_min=$ip_dec&~$n_ip;
	$ip_max=$ip_min+$n_ip;
	#Array(2) of Decimal Values Range
	return [$ip_min,$ip_max];
	#Array(2) of Ipv4 Human Readable Range
	return [long2ip($ip_min),long2ip($ip_max)];
	#Array(2) of Ipv4 and Subnet Range
	return [long2ip($ip_min),long2ip(~$n_ip)];
	#Array(2) of Ipv4 and Wildcard Bits
	return [long2ip($ip_min),long2ip($n_ip)];
	#Integer Number of Ipv4 in Range
	return ++$n_ip;
}
function datediff($tipo, $partenza, $fine){
	// $partenza e $fine = data stringa in formato dd/mm/aaaa
	switch ($tipo){
		case "A" : $tipo = 365;						break;
		case "M" : $tipo = (365 / 12);		break;
		case "S" : $tipo = (365 / 52);		break;
		case "G" : $tipo = 1;							break;
	}
	$arr_partenza = explode("/", $partenza);
	$partenza_gg = $arr_partenza[0];
	$partenza_mm = $arr_partenza[1];
	$partenza_aa = $arr_partenza[2];
	$arr_fine = explode("/", $fine);
	$fine_gg = $arr_fine[0];
	$fine_mm = $arr_fine[1];
	$fine_aa = $arr_fine[2];
	$date_diff = mktime(12, 0, 0, $fine_mm, $fine_gg, $fine_aa) - mktime(12, 0, 0, $partenza_mm, $partenza_gg, $partenza_aa);
	$date_diff  = floor(($date_diff / 60 / 60 / 24) / $tipo);
	return $date_diff;
}
function ControllaCF($cf) {
	if( $cf === '' )  return '';
	if( strlen($cf) != 16 ) {
		return "La lunghezza del codice fiscale non &egrave; corretta:
		<br>il codice fiscale dovrebbe essere lungo esattamente 16 caratteri.";
	}
	$cf = strtoupper($cf);
	if( preg_match("/^[A-Z0-9]+\$/D", $cf) != 1 ){
		return "Il codice fiscale contiene dei caratteri non validi:
		<br>i soli caratteri validi sono le lettere e le cifre.";
	}
	$s = 0;
	for( $i = 1; $i <= 13; $i += 2 ){
		$c = $cf[$i];
		if( strcmp($c, "0") >= 0 and strcmp($c, "9") <= 0 ){
			$s += ord($c) - ord('0');
		} else {
			$s += ord($c) - ord('A');
		}
	}
	for( $i = 0; $i <= 14; $i += 2 ){
		$c = $cf[$i];
		switch( $c ){
			case '0':  $s += 1;  break;
			case '1':  $s += 0;  break;
			case '2':  $s += 5;  break;
			case '3':  $s += 7;  break;
			case '4':  $s += 9;  break;
			case '5':  $s += 13;  break;
			case '6':  $s += 15;  break;
			case '7':  $s += 17;  break;
			case '8':  $s += 19;  break;
			case '9':  $s += 21;  break;
			case 'A':  $s += 1;  break;
			case 'B':  $s += 0;  break;
			case 'C':  $s += 5;  break;
			case 'D':  $s += 7;  break;
			case 'E':  $s += 9;  break;
			case 'F':  $s += 13;  break;
			case 'G':  $s += 15;  break;
			case 'H':  $s += 17;  break;
			case 'I':  $s += 19;  break;
			case 'J':  $s += 21;  break;
			case 'K':  $s += 2;  break;
			case 'L':  $s += 4;  break;
			case 'M':  $s += 18;  break;
			case 'N':  $s += 20;  break;
			case 'O':  $s += 11;  break;
			case 'P':  $s += 3;  break;
			case 'Q':  $s += 6;  break;
			case 'R':  $s += 8;  break;
			case 'S':  $s += 12;  break;
			case 'T':  $s += 14;  break;
			case 'U':  $s += 16;  break;
			case 'V':  $s += 10;  break;
			case 'W':  $s += 22;  break;
			case 'X':  $s += 25;  break;
			case 'Y':  $s += 24;  break;
			case 'Z':  $s += 23;  break;
			/*. missing_default: .*/
		}
	}
	if( chr($s%26 + ord('A')) != $cf[15] ) {
		return "Il codice fiscale non &egrave; corretto:
		<br>il codice di controllo non corrisponde.";
	}
	return "";
}
class CheckCF {
	/*
		uso: 
		$chk = new CheckCF();
		if ($chk->isFormallyCorrect('BNGLRT57T20G702B')) {
				print('Codice Fiscale formally correct');
				printf('Birth Day: %s',     $chk->getDayBirth());
				printf('Birth Month: %s',   $chk->getMonthBirth());
				printf('Birth Year: %s',    $chk->getYearBirth());
				printf('Birth Country: %s', $chk->getCountryBirth());
				printf('Sex: %s',           $chk->getSex());
		} else {
				print('Codice Fiscale wrong');
		}		
	*/
	const REGEX_CODICEFISCALE = '/^[a-z]{6}[0-9]{2}[a-z][0-9]{2}[a-z][0-9]{3}[a-z]$/i';		// fiscal's code regex
	const CHR_WOMEN = 'F';	// women char
	const CHR_MALE = 'M';		// male char
	private $isValid = false;
	private $sex = null;
	private $countryBirth = null;
	private $dayBirth = null;
	private $monthBirth = null;
	private $yearBirth = null;
	private $error = null;
	private $listDecOmocodia = array('A' => '!', 'B' => '!', 'C' => '!', 'D' => '!', 'E' => '!', 'F' => '!', 'G' => '!', 'H' => '!', 'I' => '!', 'J' => '!', 'K' => '!', 'L' => '0', 'M' => '1', 'N' => '2', 'O' => '!', 'P' => '3', 'Q' => '4', 'R' => '5', 'S' => '6', 'T' => '7', 'U' => '8', 'V' => '9', 'W' => '!', 'X' => '!', 'Y' => '!', 'Z' => '!',);
	private $listSostOmocodia = array(6, 7, 9, 10, 12, 13, 14);
	private $listEvenChar = array('0' => 0, '1' => 1, '2' => 2, '3' => 3, '4' => 4, '5' => 5, '6' => 6, '7' => 7, '8' => 8, '9' => 9, 'A' => 0, 'B' => 1, 'C' => 2, 'D' => 3, 'E' => 4, 'F' => 5, 'G' => 6, 'H' => 7, 'I' => 8, 'J' => 9, 'K' => 10, 'L' => 11, 'M' => 12, 'N' => 13, 'O' => 14, 'P' => 15, 'Q' => 16, 'R' => 17, 'S' => 18, 'T' => 19, 'U' => 20, 'V' => 21, 'W' => 22, 'X' => 23, 'Y' => 24, 'Z' => 25);
	private $listOddChar = array('0' => 1, '1' => 0, '2' => 5, '3' => 7, '4' => 9, '5' => 13, '6' => 15, '7' => 17, '8' => 19, '9' => 21, 'A' => 1, 'B' => 0, 'C' => 5, 'D' => 7, 'E' => 9, 'F' => 13, 'G' => 15, 'H' => 17, 'I' => 19, 'J' => 21, 'K' => 2, 'L' => 4, 'M' => 18, 'N' => 20, 'O' => 11, 'P' => 3, 'Q' => 6, 'R' => 8, 'S' => 12, 'T' => 14, 'U' => 16, 'V' => 10, 'W' => 22, 'X' => 25, 'Y' => 24, 'Z' => 23);
	private $listCtrlCode = array(0 => 'A', 1 => 'B', 2 => 'C', 3 => 'D', 4 => 'E', 5 => 'F', 6 => 'G', 7 => 'H', 8 => 'I', 9 => 'J', 10 => 'K', 11 => 'L', 12 => 'M', 13 => 'N', 14 => 'O', 15 => 'P', 16 => 'Q', 17 => 'R', 18 => 'S', 19 => 'T', 20 => 'U', 21 => 'V', 22 => 'W', 23 => 'X', 24 => 'Y', 25 => 'Z');
	private $listDecMonth = array('A' => '01', 'B' => '02', 'C' => '03', 'D' => '04', 'E' => '05', 'H' => '06', 'L' => '07', 'M' => '08', 'P' => '09', 'R' => '10', 'S' => '11', 'T' => '12');
	// private $listError = array(0 => 'Empty code', 1 => 'Len error', 2 => 'Code with wrong char', 3 => 'Code with wrong char in omocodia', 4 => 'Wrong code');
	private $listError = array(0 => 'Nessun CF', 1 => 'CF con lunghezza diversa da 16 crt', 2 => 'CF con caratteri non ammessi', 3 => 'CF con caratteri non ammessi in omocodia', 4 => 'CF con codice di controllo errato');
	public function getIsValid() {			return $this->isValid;			}
	public function getError() {				return $this->error;				}
	public function getSex() {					return $this->sex;					}
	public function getCountryBirth() {	return $this->countryBirth;	}
	public function getYearBirth() {		return $this->yearBirth;		}
	public function getMonthBirth() {		return $this->monthBirth;		}
	public function getDayBirth() {			return $this->dayBirth;			}
	public function isFormallyCorrect($codiceFiscale) {
		$this->resetProperties();
		try {
			if (empty($codiceFiscale)) {	$this->raiseException(0);	}																		// check empty
			if (strlen($codiceFiscale) !== 16) { $this->raiseException(1); }														// Vcheck len
			if (!preg_match(self::REGEX_CODICEFISCALE, $codiceFiscale)) {$this->raiseException(2);}			// Check regex
			$codiceFiscale = strtoupper($codiceFiscale);
			$cFCharList = str_split($codiceFiscale);
			for ($i = 0; $i < count($this->listSostOmocodia); $i++) {																		// check omocodia
				if (!is_numeric($cFCharList[$this->listSostOmocodia[$i]])) {
					if ($this->listDecOmocodia[$cFCharList[$this->listSostOmocodia[$i]]] === '!') {$this->raiseException(3);}
				}
			}
			$pari = 0;
			$dispari = $this->listOddChar[$cFCharList[14]];
			for ($i = 0; $i < 13; $i += 2) {																							// loop first 14 char, step 2
				$dispari = $dispari + $this->listOddChar[$cFCharList[$i]];
				$pari = $pari + $this->listEvenChar[$cFCharList[$i + 1]];
			}
			// verify first 15 char with checksum char (char 16)
			if (!($this->listCtrlCode[($pari + $dispari) % 26] === $cFCharList[15])) {$this->raiseException(4);}
			for ($i = 0; $i < count($this->listSostOmocodia); $i++) {											// replace "omocodie"
				if (!is_numeric($cFCharList[$this->listSostOmocodia[$i]])) {
					$CFCharList[$this->listSostOmocodia[$i]] = $this->listDecOmocodia[$cFCharList[$this->listSostOmocodia[$i]]];
				}
			}
			$codiceFiscaleAdattato = implode($cFCharList);
			// get fiscal code data
			$this->sex = ((int)(substr($codiceFiscaleAdattato, 9, 2) > 40) ? self::CHR_WOMEN : self::CHR_MALE);
			$this->countryBirth = substr($codiceFiscaleAdattato, 11, 4);
			$this->yearBirth = substr($codiceFiscaleAdattato, 6, 2);
			$this->dayBirth = substr($codiceFiscaleAdattato, 9, 2);
			$this->monthBirth = $this->listDecMonth[substr($codiceFiscaleAdattato, 8, 1)];
			// get day birth if sex is women
			if ($this->sex == self::CHR_WOMEN) {
				$this->dayBirth = $this->dayBirth - 40;
				if (strlen($this->dayBirth) === 1) {$this->dayBirth = '0' . $this->dayBirth;}
			}
			// End verify
			$this->isValid = true;
			$this->error = null;
		} catch (\Exception $e) {
			$this->error = $e->getMessage();
			$this->isValid = false;
		}
		return $this->isValid;
	}
	private function resetProperties() {
			$this->isValid = false;
			$this->sex = null;
			$this->countryBirth = null;
			$this->dayBirth = null;
			$this->monthBirth = null;
			$this->yearBirth = null;
			$this->error = null;
	}
	private function raiseException($errorNum) {
			$errMessage = isset($this->listError[$errorNum]) ? $this->listError[$errorNum] : 'Unknown Exception';
			throw new \Exception($errMessage, $errorNum);
	}
}
class CalcolaCF {
	/*
		uso: 
		$calc = new CalcolaCF();
		$calc->calcola('Alberto', 'Bongiorni', 'M', new \DateTime('1957-12-20'), 'G702');		
	*/
	private $mesi = ['A', 'B', 'C', 'D', 'E', 'H', 'L', 'M', 'P', 'R', 'S', 'T'];
	private $vocali = ['A', 'E', 'I', 'O', 'U'];
	private $consonanti = ['B', 'C', 'D', 'F', 'G', 'H', 'J', 'K', 'L', 'M', 'N', 'P', 'Q', 'R', 'S', 'T', 'V', 'W', 'X', 'Y', 'Z'];
	private $numeri = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
	private $alfabeto = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
	private $matriceCodiceControllo = ["01" => 1, "00" => 0, "11" => 0, "10" => 1, "21" => 5, "20" => 2, "31" => 7, "30" => 3, "41" => 9, "40" => 4, "51" => 13, "50" => 5, "61" => 15, "60" => 6, "71" => 17, "70" => 7, "81" => 19, "80" => 8, "91" => 21, "90" => 9, "101" => 1, "100" => 0, "111" => 0, "110" => 1, "121" => 5, "120" => 2, "131" => 7, "130" => 3, "141" => 9, "140" => 4, "151" => 13, "150" => 5, "161" => 15, "160" => 6, "171" => 17, "170" => 7, "181" => 19, "180" => 8, "191" => 21, "190" => 9, "201" => 2, "200" => 10, "211" => 4, "210" => 11, "221" => 18, "220" => 12, "231" => 20, "230" => 13, "241" => 11, "240" => 14, "251" => 3, "250" => 15, "261" => 6, "260" => 16, "271" => 8, "270" => 17, "281" => 12, "280" => 18, "291" => 14, "290" => 19, "301" => 16, "300" => 20, "311" => 10, "310" => 21, "321" => 22, "320" => 22, "331" => 25, "330" => 23, "341" => 24, "340" => 24, "351" => 23, "350" => 25];
	public function calcola($nome, $cognome, $sesso, \DateTime $dataNascita, $codiceComune) {
		$codiceFiscale = '';
		$nome = $this->sanitizeString($nome);
		$cognome = $this->sanitizeString($cognome);
		$sesso = $this->sanitizeString($sesso);
		$giorno = $dataNascita->format('d');
		$mese = $dataNascita->format('n');
		$anno = $dataNascita->format('y');
		// inizia con il calcolo dei primi sei caratteri corrispondenti al nome e cognome
		$codiceFiscale = $this->calcolaCognome($cognome) . $this->calcolaNome($nome);
		// calcola i dati corrispondenti alla data di nascita
		if ($sesso == 'F') {$giorno += 40;}
		$codiceFiscale .= $anno . $this->mesi[$mese - 1] . $giorno;
		// aggiunge il codice del comune
		$codiceFiscale .= $codiceComune;
		// e finalmente calcola il codice controllo
		$codiceControllo = 0;
		$alfanumerico = array_merge($this->numeri, $this->alfabeto);
		for ($i = 0; $i < 15; $i++) {
			$codiceControllo += $this->matriceCodiceControllo[strval(array_search($codiceFiscale[$i], $alfanumerico)) . strval((($i + 1) % 2))];
		}
		$codiceFiscale .= $this->alfabeto[$codiceControllo % 26];
		return $codiceFiscale;
	}
	private function calcolaNome($string) {
		$i = 0;
		$res = '';
		$cons = '';
		while (strlen($cons) < 4 && ($i + 1 <= strlen($string))) {
			if (array_search($string[$i], $this->consonanti) !== false) {$cons .= $string[$i];}
			$i++;
		}
		if (strlen($cons) > 3) {
			$res = $cons[0] . $cons[2] . $cons[3];
			return $res;
		} else {
			$res = $cons;
		}
		// Se non bastano prendo le vocali
		$i = 0;
		while (strlen($res) < 3 && ($i + 1 <= strlen($string))) {
			if (array_search($string[$i], $this->vocali) !== false) {$res .= $string[$i];}
			$i++;
		}
		$res .= "XXX";
		return substr($res, 0, 3);
	}
	private function calcolaCognome($string) {
			$res = '';
			$i = 0;
			while(strlen($res) < 3 && ($i + 1 <= strlen($string))) {
				if (array_search($string[$i], $this->consonanti) !== false) {$res .= $string[$i];}
				$i++;
			}
			// Se non bastano le consonanti, prendo le vocali
			$i = 0;
			while(strlen($res) < 3 && ($i + 1 <= strlen($string))) {
				if (array_search($string[$i], $this->vocali) !== false) {$res .= $string[$i];}
				$i++;
			}
			$res .= "XXX";
			return substr($res, 0, 3);
	}
	private function sanitizeString($string) {
			$string = trim($string);
			$string = strtoupper(iconv('UTF-8', 'ASCII//TRANSLIT', $string));
			$string = str_replace(' ', '', $string);
			return $string;
	}
}
?>