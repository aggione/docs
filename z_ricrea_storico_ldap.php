<?php
/*
	-- ricostruzione nuovo storico account da quello vecchio
	https://dotnetu.local/ldap/ricrea_storico_ldap.php
	https://iam.local.santannapisa.it/ricrea_storico_ldap.php
	
	-- per ricostruire cancellando tutte le prove
	https://iam.local.santannapisa.it/ricrea_storico_ldap.php?t=1 @@@@@@ NON FARE MAI --- E' ATTIVATA LA PRODUZIONE
*/
session_start();
echo "<h2>Script da non utilizzare perché alcuni account dello storico sono stati modificati e il nuovo repository degli account è ormai in produzione</h2>";
exit;

$nf=strtoupper(__FILE__);
$anf=explode('/',$nf);
$eanf=end($anf);
$anf=explode('.',$eanf);
$GLOBALS['app']=$anf[0];

$logf='_log_ricrea_storico.log';
file_put_contents($logf, date("Y/m/d H:i:s").' - START'."\r\n", LOCK_EX);

if (empty($_SESSION[$GLOBALS['app']]['devel'])){$_SESSION[$GLOBALS['app']]['devel']=0;}
@set_time_limit(3600);			//  60 minuti (periodo massimo di esecuzione di una chiamata)	- @set_time_limit(0); // tempo 

$force=false;
$devel=true;

if (!empty($_REQUEST['force'])){$force=true;}
if (!empty($_REQUEST['devel'])){$devel=true;}

error_reporting(E_ALL);

$uu='storico_ldap'; $pp='Ld4pSt0r1c0'; $cc='mysql'; 
$conn_sl = mysqli_connect($cc, $uu, $pp);	
mysqli_select_db($conn_sl,'storico_ldap'); //or die('Could not select database');
$e=mysqli_error($conn_sl);
if (empty($conn_sl) or $e!='') {
	if ($html){echo '<h2>Problemi di connessione al db MySQL su idmngt.local</h2>';}
	exit;
}

$uu='storico_ldap'; $pp='Ld4pSt0r1c0'; $cc='dotnetu.local'; 
$conn_slo = mysqli_connect($cc, $uu, $pp);	
mysqli_select_db($conn_slo,'storico_ldap'); //or die('Could not select database');
$e=mysqli_error($conn_slo);
if (empty($conn_slo) or $e!='') {
	if ($html){echo '<h2>Problemi di connessione al db MySQL su dotnetu.local</h2>';}
	exit;
}

$u='C##SSS_IMPORT'; $p='ugovimport'; $c='(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=192.168.64.140)(PORT=1521))(CONNECT_DATA=(SID=XE)))'; 
$conn_new = oci_pconnect($u,$p,$c,'AL32UTF8');	// in utf8
if (empty($conn_new)) {
	echo '<h2>problemi di connessione al db ORACLE di interscambio</h2>';;
	exit;
}
$sql = "ALTER SESSION SET NLS_DATE_FORMAT = 'DD/MM/YYYY HH24:MI:SS'";
$p = oci_parse($conn_new, $sql);
$e = oci_execute($p);

$ora = date("H");
$do=true;
$start=round(microtime(true));					// tempo attuale in secondi
if (true){		// intesta pagina html
	$s='<!DOCTYPE html>
	<html>
		<head>
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
			<meta name="description" content="Ricrea Storico LDAP">
			<meta name="keywords" content="Ricrea Storico LDAP">
			<meta name="author" content="Alberto Bongiorni">

			<meta charset="utf-8">
			<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
			
			<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
			<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
			<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
			<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
			
			<title>Ricrea Storico LDAP</title>
		</head>
		<body class="container-fluid">
			<div class="row p-2">
				<div class="col-sm-12">
					'.get_alert('<div class="text-center font-weight-bold"><h1 style="display: inline;">Ricrea Storico LDAP</h1> (start: '.date('d/m/Y H:i:s').') - (time: <span id="sec">0:0:0</span>) <span id="fine"></span></div>').'
				</div>
			</div>
			<div class="row p-2">
				<div class="col-sm-12">
					'.get_alert('<div class="text-center" id="elab"><strong>INIZIO</strong></div>','info').'
				</div>
			</div>
			
			<!-- Progress bar -->

			<div class="progress" style="height:40px">
			  <div id="per" class="progress-bar bg-success text-center font-weight-bold" style="width:0%;height:40px"></div>
			</div>

			<div class="row p-2">
				<div class="col-sm-12">
					'.get_alert('<div id="sql"></div>').'
				</div>
			</div>
			<div class="row p-2">
				<div class="col-sm-12">
					'.get_alert('<div id="err"></div>','danger').'
				</div>
			</div>
		</body>
	</html>
	';
	echo $s;
	if (!empty(ob_get_level())){flush(); ob_flush();}
}
$s='';
if (true){		// lettura da db interscambio
	disp('<br>leggo ab_can','elab',true);
	$sql="select distinct LDAP_UID, lower(LDAP_UID) ldap_uid_lower, COD_FISC, KR, KA, KI, MAIL, MAIL_ESTERNA, to_char(dt_rap_fin,'yyyy-mm-dd') fine_rapporto from c##sss_import.ab_can where LDAP_UID is not null and COD_FISC is not null";
	disp($sql,'sql');
	$r=oraexec($sql);
	if (!empty($r['nr'])){$_SESSION[$GLOBALS['app']]['ab_can']=$r['result'];}

	disp('<br>leggo ab_csn','elab',true);
	$sql="select distinct LDAP_UID, lower(LDAP_UID) ldap_uid_lower, COD_FISC, KR, KA, KI, MAIL, MAIL_ESTERNA from c##sss_import.ab_csn where LDAP_UID is not null and COD_FISC is not null";
	disp($sql,'sql');
	$r=oraexec($sql);
	if (!empty($r['nr'])){$_SESSION[$GLOBALS['app']]['ab_csn']=$r['result'];}

	disp('<br>leggo ab_can_l inesistenti in ab_can o ab_csn','elab',true);
	$sql="select distinct LDAP_UID, lower(LDAP_UID) ldap_uid_lower, COD_FISC, KR, KA, KI, MAIL, MAIL_ESTERNA from c##sss_import.ab_can_l where LDAP_UID is not null and COD_FISC is not null and COD_FISC not in (select distinct COD_FISC from (select distinct COD_FISC from c##sss_import.ab_can union select distinct COD_FISC from c##sss_import.ab_csn))";
	disp($sql,'sql');
	$r=oraexec($sql);
	if (!empty($r['nr'])){$_SESSION[$GLOBALS['app']]['ab_can_l']=$r['result'];}
}
if (true){		// cancella i dati storici
	// cancello gli alias degli storicizzati
	disp('<br>cancello gli alias delle richieste di account del vecchio storico','elab',true);
	$sql="delete from storico_ldap.richieste_account_alias where raa_ra_k in (select distinct ra_k from storico_ldap.richieste_account where raa_usr_ins='old_storico')";
	disp($sql,'sql');
	$a=mysqli_query($conn_sl,$sql);
	if (!empty(mysqli_error($conn_sl))){disp('<br />'.$sql.'<br />'.mysqli_error($conn_sl),'err',true);}

	// cancello i log degli storicizzati
	disp('<br>cancello i log delle richieste di account del vecchio storico','elab',true);
	$sql="delete from storico_ldap.richieste_account_log where ral_ra_k in (select distinct ra_k from storico_ldap.richieste_account where ra_usr_ins='old_storico')";
	disp($sql,'sql');
	$a=mysqli_query($conn_sl,$sql);
	if (!empty(mysqli_error($conn_sl))){disp('<br />'.$sql.'<br />'.mysqli_error($conn_sl),'err',true);}

	// cancello gli storicizzati
	disp('<br>cancello le richieste di account del vecchio storico','elab',true);
	$sql="delete from storico_ldap.richieste_account where ra_usr_ins='old_storico'";
	disp($sql,'sql');
	$a=mysqli_query($conn_sl,$sql);
	if (!empty(mysqli_error($conn_sl))){disp('<br />'.$sql.'<br />'.mysqli_error($conn_sl),'err',true);}
}
// @@@@@@@ cancellare il contenuto della prossima if
// essendo entrato in produzione non va fatto mai pena la perdita di dati dello storico
if (false){	
//  if (!empty($_GET['t'])){if ($_GET['t']==1){ // -- CANCELLAZIONE TOTALE (ANCHE DELLE PROVE)
//  
//  	// cancello gli alias
//  	disp('<br>cancello gli alias','elab',true);
//  	$sql="delete from storico_ldap.richieste_account_alias";
//  	disp($sql,'sql');
//  	$a=mysqli_query($conn_sl,$sql);
//  	if (!empty(mysqli_error($conn_sl))){disp('<br />'.$sql.'<br />'.mysqli_error($conn_sl),'err',true);}
//  
//  	// cancello i log 
//  	disp('<br>cancello i log','elab',true);
//  	$sql="delete from storico_ldap.richieste_account_log";
//  	disp($sql,'sql');
//  	$a=mysqli_query($conn_sl,$sql);
//  	if (!empty(mysqli_error($conn_sl))){disp('<br />'.$sql.'<br />'.mysqli_error($conn_sl),'err',true);}
//  
//  	// cancello le richieste di account
//  	disp('<br>cancello le richieste di account','elab',true);
//  	$sql="delete from storico_ldap.richieste_account";
//  	disp($sql,'sql');
//  	$a=mysqli_query($conn_sl,$sql);
//  	if (!empty(mysqli_error($conn_sl))){disp('<br />'.$sql.'<br />'.mysqli_error($conn_sl),'err',true);}
//  }}
}
// exit;
if (true){		// ricrea i dati storici

	// inserisco nel nuovo storico gli account del vecchio storico
	disp('<br>inserisco nel nuovo storico gli account del vecchio storico','elab',true);
	// $sql="insert into storico_ldap.richieste_account (ra_tipo, ra_nome, ra_cognome, ra_mail, ra_notificata, ra_inizio, ra_fine, ra_stato, ra_uid, ra_redirect, ra_prima_password, ra_note, ra_usr_ins) select 'PF' as tipo, tblAnagrafe.nome, tblAnagrafe.cognome, concat(concat(concat(LOWER(tblAnagrafe.nome), '.'), LOWER(tblAnagrafe.cognome)), '@santannapisa.it') as mail, 0 as notificata, from_unixtime(tblMailAccount.apertura, '%Y-%m-%d') as inizio, from_unixtime(tblMailAccount.chiusura, '%Y-%m-%d') as fine, 'storicizzata' as stato, tblMailAccount.nome as uid, tblMailAccount.redirezione as redirect, tblMailAccount.password as prima_password, concat(concat(tblGID.nome, ' '), tblGID.descrizione) as note, 'old_storico' as usr_ins from tblMailAccount left join tblAnagrafe on tblMailAccount.idAnagrafe = tblAnagrafe.id left join tblGID on tblAnagrafe.idGID = tblGID.id";
	$sql="select tblAnagrafe.nome, tblAnagrafe.cognome, concat(concat(concat(LOWER(tblAnagrafe.nome), '.'), LOWER(tblAnagrafe.cognome)), '@santannapisa.it') as mail, from_unixtime(tblMailAccount.apertura, '%Y-%m-%d') as inizio, from_unixtime(tblMailAccount.chiusura, '%Y-%m-%d') as fine, tblMailAccount.nome as uid, tblMailAccount.redirezione as redirect, tblMailAccount.password as prima_password, concat(concat(tblGID.nome, ' '), tblGID.descrizione) as note from tblMailAccount left join tblAnagrafe on tblMailAccount.idAnagrafe = tblAnagrafe.id left join tblGID on tblAnagrafe.idGID = tblGID.id";
	disp($sql,'sql');
	$x=mysqli_query($conn_slo,$sql);
	if (!empty(mysqli_error($conn_slo))){disp('<br />'.$sql.'<br />'.mysqli_error($conn_slo),'err',true);}
	if (mysqli_num_rows($x) > 0) {	// 
		$a=array();	
		foreach($x as $row){$a[]=$row;}
		disp(' ('.count($a).')','elab',true);
		$i=0;
		disp_bar(0);
		foreach ($a as $row){
			$i++;
			disp_bar(round(($i/count($a)*100),1));
			$row['nome']=(($row['nome']=='')?	"''":"'".implode("''",explode("'",$row['nome']))."'");
			$row['cognome']=(($row['cognome']=='')?"''":"'".implode("\'",explode("'",$row['cognome']))."'");
			$row['mail']=(($row['mail']=='')?'null':"'".implode("",explode(" ",implode("\'",explode("'",$row['mail']))))."'");
			$row['inizio']=(($row['inizio']=='')?'null':"'".implode("\'",explode("'",$row['inizio']))."'");
			$row['fine']=(($row['fine']=='')?'null':"'".implode("\'",explode("'",$row['fine']))."'");
			$row['uid']=(($row['uid']=='')?'null':"'".implode("\'",explode("'",$row['uid']))."'");
			$row['redirect']=(($row['redirect']=='')?'null':"'".implode("\'",explode("'",$row['redirect']))."'");
			$row['prima_password']=(($row['prima_password']=='')?	'null':"'".implode("\'",explode("'",$row['prima_password']))."'"	);
			$row['note']=(($row['note']=='')?'null':"'".implode("\'",explode("'",$row['note']))."'");
			$sql="insert into storico_ldap.richieste_account (
				ra_tipo, 
				ra_nome, 
				ra_cognome, 
				ra_ad_princ_mail, 
				ra_notificata, 
				ra_inizio, 
				ra_fine, 
				ra_stato, 
				ra_uid, 
				ra_redirect, 
				ra_prima_password, 
				ra_note, 
				ra_usr_ins
			) values (
				'PF'
				,".$row['nome']." 
				,".$row['cognome']."
				,".$row['mail']."
				,0
				,".$row['inizio']." 
				,".$row['fine']."
				,'storicizzata'
				,".$row['uid']."
				,".$row['redirect']."
				,".$row['prima_password']."
				,".$row['note']."
				,'old_storico'
			)";
			disp($sql,'sql');
			// file_put_contents($logf, date("Y/m/d H:i:s").' - '.implode("\r\n",explode('<br />',$sql))."\r\n", FILE_APPEND | LOCK_EX);
			try {
				$b=mysqli_query($conn_sl,$sql);
			} catch (Exception $e) {
				disp('<br />richieste_account: '.$sql.'<br />'.$e->getMessage(),'err',true);
			}
			if (!empty(mysqli_error($conn_sl))){disp('<br />richieste_account: '.$sql.'<br />'.mysqli_error($conn_sl),'err',true);}
		}
	}
	// exit;	
	// @@@@@@@@@ chiudi e riapri la connessione o commit
	// try {
		// $b=mysqli_query($conn_sl,'commit');
	// } catch (Exception $e) {
		// disp('<br />commit: '.$sql.'<br />'.$e->getMessage(),'err',true);
	// }
	// if (!empty(mysqli_error($conn_sl))){disp('<br />commit: '.$sql.'<br />'.mysqli_error($conn_sl),'err',true);}
	
	// inserisco negli alias del nuovo storico gli alias del vecchio storico
	disp('<br>inserisco negli alias del nuovo storico gli alias del vecchio storico','elab',true);
	// $sql="insert into storico_ldap.richieste_account_alias (raa_ra_k, raa_ra_uid, raa_uid, raa_usr_ins) select 	richieste_account.ra_k, richieste_account.ra_uid, tblAlias.nome as alias, 'old_storico' as usr_ins from tblAlias join tblMailAccount on tblMailAccount.id = tblAlias.idMailAccount join richieste_account on tblMailAccount.nome = richieste_account.ra_uid";
	$sql="select tblMailAccount.nome as uid, tblAlias.nome as alias from tblAlias join tblMailAccount on tblMailAccount.id = tblAlias.idMailAccount";
	disp($sql,'sql');
	$x=mysqli_query($conn_slo,$sql);
	if (!empty(mysqli_error($conn_slo))){disp('<br />'.$sql.'<br />'.mysqli_error($conn_slo),'err',true);}
	if (mysqli_num_rows($x) > 0) {	// 
		$a=array();	foreach($x as $row){$a[]=$row;}
		disp(' ('.count($a).')','elab',true);
		$i=0;
		disp_bar(0);
		foreach ($a as $row){
			$i++;
			disp_bar(round(($i/count($a)*100),1));
			// cerco il ra_k 
			$sql="select * from storico_ldap.richieste_account where ra_uid='".$row['uid']."'";
			disp($sql,'sql');
			$xx=mysqli_query($conn_sl,$sql);
			if (mysqli_num_rows($xx) > 0) {	// 
				$y=array();	foreach($xx as $r){$y[]=$r;}
				$sql="insert into storico_ldap.richieste_account_alias (
					raa_ra_k, 
					raa_ra_uid, 
					raa_uid, 
					raa_usr_ins
				) values (
					".(($y[0]['ra_k']=='')			?	"0"		:	"'".implode("''",explode("'",$y[0]['ra_k']))."'"	).", 
					".(($row['uid']=='')			?	"''"	:	"'".implode("''",explode("'",$row['uid']))."'"		).", 
					".(($row['alias']=='')			?	"''"	:	"'".implode("''",explode("'",$row['alias']))."'"	).", 
					'old_storico'
				)";
				disp($sql,'sql');
				try {
					$b=mysqli_query($conn_sl,$sql);
				} catch (Exception $e) {
					disp('<br />'.$sql.'<br />'.$e->getMessage(),'err',true);
				}
				if (!empty(mysqli_error($conn_sl))){disp('<br />'.$sql.'<br />'.mysqli_error($conn_sl),'err',true);}
			} else {
				disp('<br />'.$sql.'<br />inserimento alias - uid non trovato in storico_ldap.richieste_account per alias '.$row['alias'],'err',true);
			}
		}
	}
}

if (true){
	disp('<br>popolamento del campo ra_ad_princ_mail selezionando tra gli alias quello che più si avvicina a nome.cognome','elab',true);
	$ra=array();	
	$sql="select * from storico_ldap.richieste_account where ra_usr_ins='old_storico'";
	disp($sql,'sql');
	$x=mysqli_query($conn_sl,$sql);
	if (mysqli_num_rows($x) > 0) {	// 
		foreach($x as $r){$ra[]=$r;}
		disp(' ('.count($ra).')','elab',true);
		$i=0;
		disp_bar(0);
		foreach ($ra as $rarow){
			$i++;
			disp_bar(round(($i/count($ra)*100),1));
			$nc=implode('',explode(' ',strtolower($rarow['ra_nome'].'.'.$rarow['ra_cognome']))).'@santannapisa.it';
			$raa=array();
			$sql="select * from storico_ldap.richieste_account_alias where raa_ra_uid='".$rarow['ra_uid']."' and raa_usr_ins='old_storico'";
			disp($sql,'sql');
			$x=mysqli_query($conn_sl,$sql);
			if (mysqli_num_rows($x) > 0) {	// 
				foreach($x as $r){$raa[]=$r;}
				foreach ($raa as $raarow){
					// calcolo l'edit distance fra nome.cognome e l'alias
					$alias=strtolower($raarow['raa_uid']).'@santannapisa.it';
					if (levenshtein($nc,$alias) < 3){
						if (strtolower($rarow['ra_ad_princ_mail']) != $alias) {
							$sql = "update storico_ldap.richieste_account set ra_ad_princ_mail='".$alias."' where ra_uid='".$rarow['ra_uid']."' and ra_usr_ins='old_storico'";
							disp($sql,'sql');
							// disp('<li>cambio alias da '.$rarow['ra_ad_princ_mail'].': '.$sql,'err',true);
							try {
								$b=mysqli_query($conn_sl,$sql);
							} catch (Exception $e) {
								disp('<br />aggiorna ra_ad_princ_mail: '.$sql.'<br />'.$e->getMessage(),'err',true);
							}
							if (!empty(mysqli_error($conn_sl))){disp('<br />aggiorna ra_ad_princ_mail: '.$sql.'<br />'.mysqli_error($conn_sl),'err',true);}
						}
					}
				}
			}
		}			
	}
}
			
if (true){		// aggiorna i dati storici da interscambio
	disp('<br>aggiorno i dati conosciuti da ab_can o ab_csn o ab_can_l','elab',true);
	// aggiorno i dati conosciuti da ab_can o ab_csn o ab_can_l
	$sql="select ra_k, lower(ra_uid) as ra_uid from richieste_account where ra_uid is not null and ra_usr_ins = 'old_storico' union select raa_ra_k as ra_k, lower(raa_uid) as ra_uid from richieste_account_alias where raa_uid is not null and raa_usr_ins = 'old_storico' order by ra_k, ra_uid";
	$x=mysqli_query($conn_sl,$sql);
	if (!empty(mysqli_error($conn_sl))){disp('<br />'.$sql.'<br />'.mysqli_error($conn_sl),'err',true);}
	if (mysqli_num_rows($x) > 0) {	// 
		$a=array();	foreach($x as $row){$a[]=$row;}
		disp(' ('.count($a).')','elab',true);
		$sa=''; $dep_ra_k=-1;
		$i=0;
		disp_bar(0);
		foreach ($a as $row){
			$i++;
			disp_bar(round(($i/count($a)*100),1));
			if ($row['ra_k'] != $dep_ra_k){
				if ($sa != ''){
					$sql="update storico_ldap.richieste_account set ra_aliases='".$sa."' where ra_k=".$dep_ra_k;
					disp($sql,'sql');
					try {
						$b=mysqli_query($conn_sl,$sql);
					} catch (Exception $e) {
						disp('<br />'.$from.$sql.'<br />'.$e->getMessage(),'err',true);
					}
					if (!empty(mysqli_error($conn_sl))){disp('<br />aggiorna aliases: '.$sql.'<br />'.mysqli_error($conn_sl),'err',true);}
				}
				$dep_ra_k = $row['ra_k'];
				$sa=$row['ra_uid'];
			} else {
				if ($sa != ''){$sa.='|';}
				$sa.=$row['ra_uid'];
			}
			$sql=''; $from='';
			if (!empty($_SESSION[$GLOBALS['app']]['ab_can'])){
				// carriere attive
				$from='can ';
				$p=array_search(strtolower($row['ra_uid']),$_SESSION[$GLOBALS['app']]['ab_can']['LDAP_UID_LOWER']);
				if ($p !== false){
					$sql="update storico_ldap.richieste_account set";
					$sql.=" ra_cf='".trim($_SESSION[$GLOBALS['app']]['ab_can']['COD_FISC'][$p])."'";
					if (!empty($_SESSION[$GLOBALS['app']]['ab_can']['KR'][$p])){$sql.=", ra_ruolo='".trim($_SESSION[$GLOBALS['app']]['ab_can']['KR'][$p])."'";}
					if (!empty($_SESSION[$GLOBALS['app']]['ab_can']['KA'][$p])){$sql.=", ra_afferenza='".trim($_SESSION[$GLOBALS['app']]['ab_can']['KA'][$p])."'";}
					if (!empty($_SESSION[$GLOBALS['app']]['ab_can']['DAB'][$p])){$sql.=", ra_d_afferenza='".trim(substr($_SESSION[$GLOBALS['app']]['ab_can']['DAB'][$p],0,60))."'";}
					if (!empty($_SESSION[$GLOBALS['app']]['ab_can']['KI'][$p])){$sql.=", ra_inquadramento='".implode("''",explode("'",trim(crt_filter($_SESSION[$GLOBALS['app']]['ab_can']['KI'][$p],'§ma§mi§nu'))))."'";}
					if (!empty($_SESSION[$GLOBALS['app']]['ab_can']['MAIL_ESTERNA'][$p])){
						$sql.=", ra_mail='".$_SESSION[$GLOBALS['app']]['ab_can']['MAIL_ESTERNA'][$p]."'";
					}
					if (stripos('santannapisa',$_SESSION[$GLOBALS['app']]['ab_can']['MAIL'][$p]) !== false){
						$sql.=", ra_ad_princ_mail='".$_SESSION[$GLOBALS['app']]['ab_can']['MAIL'][$p]."'";
					}
					$sql.=", ra_fine='".$_SESSION[$GLOBALS['app']]['ab_can']['FINE_RAPPORTO'][$p]."'"; // aggiorno la data di fine
					$sql.=" where ra_k='".$row['ra_k']."'";
				}
			}
			if ($sql == ''){
				if (!empty($_SESSION[$GLOBALS['app']]['ab_csn'])){
					// carriere scadute
					$from='csn ';
					$p=array_search(strtolower($row['ra_uid']),$_SESSION[$GLOBALS['app']]['ab_csn']['LDAP_UID_LOWER']);
					if ($p !== false){
						$sql="update storico_ldap.richieste_account set";
						$sql.=" ra_cf='".trim($_SESSION[$GLOBALS['app']]['ab_csn']['COD_FISC'][$p])."'";
						if (!empty($_SESSION[$GLOBALS['app']]['ab_csn']['KR'][$p])){$sql.=", ra_ruolo='".trim($_SESSION[$GLOBALS['app']]['ab_csn']['KR'][$p])."'";}
						if (!empty($_SESSION[$GLOBALS['app']]['ab_csn']['KA'][$p])){$sql.=", ra_afferenza='".trim($_SESSION[$GLOBALS['app']]['ab_csn']['KA'][$p])."'";}
						if (!empty($_SESSION[$GLOBALS['app']]['ab_csn']['DAB'][$p])){$sql.=", ra_d_afferenza='".trim(substr($_SESSION[$GLOBALS['app']]['ab_csn']['DAB'][$p],0,60))."'";}
						if (!empty($_SESSION[$GLOBALS['app']]['ab_csn']['KI'][$p])){$sql.=", ra_inquadramento='".implode("''",explode("'",trim($_SESSION[$GLOBALS['app']]['ab_csn']['KI'][$p])))."'";}
						// if (stripos('santannapisa',$_SESSION[$GLOBALS['app']]['ab_csn']['MAIL'][$p])){
							// $sql.=", ra_mail='".$_SESSION[$GLOBALS['app']]['ab_csn']['MAIL'][$p]."'";
							// $amail=explode('@',$_SESSION[$GLOBALS['app']]['ab_csn']['MAIL'][$p]);
							// if(!empty($amail[0])){$sql.=", ra_ad_princ_mail='".$amail[0]."'";}
						// }
						if (!empty($_SESSION[$GLOBALS['app']]['ab_csn']['MAIL_ESTERNA'][$p])){
							$sql.=", ra_mail='".$_SESSION[$GLOBALS['app']]['ab_csn']['MAIL_ESTERNA'][$p]."'";
						}
						if (stripos('santannapisa',$_SESSION[$GLOBALS['app']]['ab_csn']['MAIL'][$p]) !== false){
							$sql.=", ra_ad_princ_mail='".$_SESSION[$GLOBALS['app']]['ab_csn']['MAIL'][$p]."'";
						}
						$sql.=" where ra_k='".$row['ra_k']."'";
					}
				}
			}
			if ($sql == ''){
				if (!empty($_SESSION[$GLOBALS['app']]['ab_can_l'])){
					// storico modifiche carriere
					$from='can_l ';
					$p=array_search(strtolower($row['ra_uid']),$_SESSION[$GLOBALS['app']]['ab_csn']['LDAP_UID_LOWER']);
					if ($p !== false){
						$sql="update storico_ldap.richieste_account set";
						$sql.=" ra_cf='".trim($_SESSION[$GLOBALS['app']]['ab_can_l']['COD_FISC'][$p])."'";
						if (!empty($_SESSION[$GLOBALS['app']]['ab_can_l']['KR'][$p])){$sql.=", ra_ruolo='".trim($_SESSION[$GLOBALS['app']]['ab_can_l']['KR'][$p])."'";}
						if (!empty($_SESSION[$GLOBALS['app']]['ab_can_l']['KA'][$p])){$sql.=", ra_afferenza='".trim($_SESSION[$GLOBALS['app']]['ab_can_l']['KA'][$p])."'";}
						if (!empty($_SESSION[$GLOBALS['app']]['ab_can_l']['DAB'][$p])){$sql.=", ra_d_afferenza='".trim(substr($_SESSION[$GLOBALS['app']]['ab_can_l']['DAB'][$p],0,60))."'";}
						if (!empty($_SESSION[$GLOBALS['app']]['ab_can_l']['KI'][$p])){$sql.=", ra_inquadramento='".implode("''",explode("'",trim($_SESSION[$GLOBALS['app']]['ab_can_l']['KI'][$p])))."'";}
						// if (stripos('santannapisa',$_SESSION[$GLOBALS['app']]['ab_can_l']['MAIL'][$p])){
							// $sql.=", ra_mail='".$_SESSION[$GLOBALS['app']]['ab_can_l']['MAIL'][$p]."'";
							// $amail=explode('@',$_SESSION[$GLOBALS['app']]['ab_can_l']['MAIL'][$p]);
							// if(!empty($amail[0])){$sql.=", ra_ad_princ_mail='".$amail[0]."'";}
						// }
						if (!empty($_SESSION[$GLOBALS['app']]['ab_can_l']['MAIL_ESTERNA'][$p])){
							$sql.=", ra_mail='".$_SESSION[$GLOBALS['app']]['ab_can_l']['MAIL_ESTERNA'][$p]."'";
						}
						if (stripos('santannapisa',$_SESSION[$GLOBALS['app']]['ab_can_l']['MAIL'][$p]) !== false){
							$sql.=", ra_ad_princ_mail='".$_SESSION[$GLOBALS['app']]['ab_can_l']['MAIL'][$p]."'";
						}
						$sql.=" where ra_k='".$row['ra_k']."'";
					}
				}
			}

			if ($sql != ''){
				disp($sql,'sql');
				try {
					$b=mysqli_query($conn_sl,$sql);
				} catch (Exception $e) {
					disp('<br />'.$from.$sql.'<br />'.$e->getMessage(),'err',true);
				}
				if (!empty(mysqli_error($conn_sl))){disp('<br />'.$from.$sql.'<br />'.mysqli_error($conn_sl),'err',true);}
			}
		}
	}
}
if (true){		// aggiorna afferenze / ruoli / inquadramenti dai vecchi dati storici se non già popolati
	// cerco le afferenze / ruoli / inquadramenti
	disp('<br>cerco le afferenze / ruoli / inquadramenti','elab',true);
	// tramite questo oggetto  di interpretazione ruolo, afferenza, inquadramento per trasferimento da vecchio a nuovo storico, match di  tblGID.descrizione ora in note di richieste_account
	$an=array(
	"AGR Classe di Scienze Sperimentali - Agraria"=>array('KR'=>'','KA'=>'','KI'=>'','CFR'=>'')
	//				,"ALTRO Altro"
	,"AMMINISTR Personale Tecnico-Amministrativo"=>array('KR'=>'ND','KA'=>'','KI'=>'','CFR'=>'')
	,"BIBLIO Biblioteca"=>array('KR'=>'ND','KA'=>'005789','KI'=>'','CFR'=>'')
	,"CSI Centro Servizi Informatici"=>array('KR'=>'ND','KA'=>'005730','KI'=>'','CFR'=>'')
	,"DIRAMM Direzione Amministrativa"=>array('KR'=>'ND','KA'=>'320004','KI'=>'','CFR'=>'')
	,"ECO Classe di Scienze Sociali - Economia"=>array('KR'=>'','KA'=>'330323','KI'=>'','CFR'=>'')
	,"GIUR Classe di Scienze Sociali - Giurisprudenza"=>array('KR'=>'','KA'=>'330321','KI'=>'','CFR'=>'')
	,"GP-ICT Graduate Program in Information  and Communication Technology (LM con Unitrento)"=>array('KR'=>'','KA'=>'','KI'=>'','CFR'=>'')
	,"GP-IST Graduate Program in Information Science and Technology  (LM con Unitrento)"=>array('KR'=>'','KA'=>'','KI'=>'','CFR'=>'')
	,"GPCSE Graduate Program in Computer Science and Engineering"=>array('KR'=>'','KA'=>'','KI'=>'','CFR'=>'')
	,"GPIST Graduate Program in Information  Science and Technology"=>array('KR'=>'','KA'=>'','KI'=>'','CFR'=>'')
	,"IGPISTG Integrated Graduate Program in International Studies and Transnational Governance"=>array('KR'=>'','KA'=>'','KI'=>'','CFR'=>'')
	,"ING Classe di Scienze Sperimentali - Ingegneria"=>array('KR'=>'','KA'=>'330225','KI'=>'','CFR'=>'')
	,"IST-BIOROBOTICA Istituto di Biorobotica"=>array('KR'=>'','KA'=>'350039','KI'=>'','CFR'=>'')
	,"IST-DIRPOLIS DIRPOLIS, Diritto, Politica, Sviluppo"=>array('KR'=>'','KA'=>'350037','KI'=>'','CFR'=>'')
	,"IST-ECONOMIA Istituto di ECONOMIA"=>array('KR'=>'','KA'=>'350035','KI'=>'','CFR'=>'')
	,"IST-MANAGEMENT Istituto di MANAGEMENT"=>array('KR'=>'','KA'=>'350036','KI'=>'','CFR'=>'')
	,"IST-SCIENZEVITA Istituto Scienze della vita"=>array('KR'=>'','KA'=>'350038','KI'=>'','CFR'=>'')
	,"IST-TECIP Istituto di Tecnologia della comunicazione, dell\'informazione e della percezione"=>array('KR'=>'','KA'=>'350034','KI'=>'','CFR'=>'')
	,"MAIN Laboratorio IN-SAT Innovation in Business and Territorial Systems"=>array('KR'=>'','KA'=>'','KI'=>'','CFR'=>'')
	,"MAPNET Masters on Photonic Networks Engineering"=>array('KR'=>'','KA'=>'350034','KI'=>'4612B','CFR'=>'')
	,"MASTAGRO Master in valorizzazione e controllo delle produzioni agro-alimentari di qualità"=>array('KR'=>'','KA'=>'','KI'=>'','CFR'=>'')
	,"\"MASTAMB Master in Gestione e Controllo dell\'Ambiente\""=>array('KR'=>'','KA'=>'350036','KI'=>'DDD','CFR'=>'')
	,"MASTCINA Master Cina"=>array('KR'=>'','KA'=>'','KI'=>'','CFR'=>'')
	,"MASTERIT Master in Information Technology"=>array('KR'=>'','KA'=>'','KI'=>'','CFR'=>'')
	,"MASTERMAIN Master MAIN"=>array('KR'=>'','KA'=>'','KI'=>'','CFR'=>'')
	,"MASTERMES Master MESLAB"=>array('KR'=>'','KA'=>'','KI'=>'','CFR'=>'')
	,"MASTERSUB Master Medicina Subacquea"=>array('KR'=>'','KA'=>'350038','KI'=>'FFF','CFR'=>'')
	,"MASTHUM Master Universitario in Diritti Umani e Gestione dei Conflitti"=>array('KR'=>'','KA'=>'','KI'=>'','CFR'=>'')
	,"MASTIMCNE master IMCNE"=>array('KR'=>'','KA'=>'','KI'=>'','CFR'=>'')
	,"MASTINNO Master in Management dell\'Innovazione"=>array('KR'=>'','KA'=>'350036','KI'=>'BBB','CFR'=>'')
	,"MASTNETENG International Master on Communication Networks Engineering"=>array('KR'=>'','KA'=>'350034','KI'=>'CCC','CFR'=>'')
	,"MECS Master of Science in Computer Science and Networking (LM con Unipisa)"=>array('KR'=>'','KA'=>'','KI'=>'','CFR'=>'')
	,"MED Classe di Scienze Sperimentali - Medicina"=>array('KR'=>'','KA'=>'330226','KI'=>'','CFR'=>'')
	,"POL Classe di Scienze Sociali - Scienze Politiche"=>array('KR'=>'','KA'=>'330322','KI'=>'','CFR'=>'')
	);			
	$i=0;
	disp_bar(0);
	foreach ($an as $k => $v){
		$i++;
		disp_bar(round(($i/count($an)*100),1));
		$sql="update storico_ldap.richieste_account set";
		$sql_set="";
		if (!empty($v['KR'])){
			if (!empty($sql_set)){$sql_set.=", ";}
			$sql_set.=" ra_ruolo = if (ra_ruolo is null, '".$v['KR']."', ra_ruolo)";
		}
		if (!empty($v['KA'])){
			if (!empty($sql_set)){$sql_set.=", ";}
			$sql_set.=" ra_afferenza = if (ra_afferenza is null, '".$v['KA']."', ra_afferenza)";
		}
		// if (!empty($v['DA'])){
			// if (!empty($sql_set)){$sql_set.=", ";}
			// $sql_set.=" ra_d_afferenza = if (ra_d_afferenza is null, '".$v['DA']."', ra_d_afferenza)";
		// }
		if (!empty($v['KI'])){
			if (!empty($sql_set)){$sql_set.=", ";}
			$sql_set.=" ra_inquadramento = if (ra_inquadramento is null, '".implode("''",explode("'",$v['KI']))."', ra_inquadramento)";
		}
		$sql.=$sql_set." where ra_note='".$k."'";
		disp($sql,'sql');
		if (!empty($sql_set)){
			try {
				$b=mysqli_query($conn_sl,$sql);
			} catch (Exception $e) {
				disp('<br />'.$sql.'<br />'.$e->getMessage(),'err',true);
			}
			if (!empty(mysqli_error($conn_sl))){disp('<br />'.$sql.'<br />'.mysqli_error($conn_sl),'err',true);}
		}
	}

	/*				
	350038	Istituto di Scienze della Vita	21D20	ECOGRAFIA CLINICA ED APPLICAZIONI IN NEFROLOGIA
	350038	Istituto di Scienze della Vita	E7E30	FISIOPATOLOGIA CLINICA E MANAGEMENT DELLO SCOMPENSO CARDIACO
	350036	Istituto di Management	35564	GESTIONE E CONTROLLO DELL'AMBIENTE: ECONOMIA CIRCOLARE E  MANAGEMENT EFFICIENTE DELLE RISORSE
	350036	Istituto di Management	DDD	GESTIONE E CONTROLLO DELL'AMBIENTE: TECNOLOGIE E MANAGEMENT PER IL CICLO DEI RIFIUTI
	350034	Istituto di Tecnologie della Comunicazione, dell'Informazione e della Percezione	CCC	INTERNATIONAL MASTER ON COMMUNICATION NETWORKS ENGINEERING 
	350038	Istituto di Scienze della Vita	A0CA5	LA TEORIA E LA PRATICA DELL’ACCESSO VASCOLARE NEL PAZIENTE IN EMODIALISI
	350037	Istituto di Diritto, Politica e Sviluppo	5AA75	MASTER IN ELECTORAL POLICY AND ADMINISTRATION 
	350037	Istituto di Diritto, Politica e Sviluppo	E0F8B	MASTER IN ELECTORAL POLICY AND ADMINISTRATION (MEPA) (ONLINE)
	350037	Istituto di Diritto, Politica e Sviluppo	63E40	MASTER IN HUMAN RIGHTS AND CONFLICT MANAGEMENT
	350036	Istituto di Management	BBB	MASTER IN MANAGEMENT, INNOVAZIONE E INGEGNERIA DEI SERVIZI
	350038	Istituto di Scienze della Vita	FFF	MASTER IN MEDICINA SUBACQUEA ED IPERBARICA 
	350037	Istituto di Diritto, Politica e Sviluppo	EEE	MASTER OF ARTS IN HUMAN RIGHTS AND CONFLICT MANAGEMENT
	350038	Istituto di Scienze della Vita	3D4A4	PERCUTANEOUS INTERVENTIONAL TREATMENT OF STRUCTURAL HEART DISEASES
	350034	Istituto di Tecnologie della Comunicazione, dell'Informazione e della Percezione	4612B	PHOTONIC INTEGRATED CIRCUITS, SENSORS AND NETWORKS (PIXNET)
	350038	Istituto di Scienze della Vita	D7B37	TRATTAMENTO PERCUTANEO DELLA MALATTIA CORONARICA
	350038	Istituto di Scienze della Vita	A001	VINI ITALIANI E MERCATI MONDIALI
	350038	Istituto di Scienze della Vita	E0497	VINI ITALIANI E MERCATI MONDIALI

	// --- nuove afferenze
	005769	Amministrazione Istituto di Biorobotica
	005764	Area Acquisti
	005717	Area Affari Generali
	005771	Area Amministrazione Istituti di Management, Economia e Dirpolis
	005770	Area Amministrazione Istituti Tecip e Scienze della Vita
	005763	Area Contabilità e Bilancio
	005767	Area della Formazione
	005761	Area Promozione, Coordinamento e valutazione ricerca
	005760	Area Relazioni Esterne e Comunicazione
	005762	Area Risorse Umane
	005766	Area Tecnica
	005726	Area Tecnico-gestionale
	005768	Area Terza Missione
	330321	Cl. Sc. Sociali - Giurisprudenza
	330323	Cl. Sc. Sociali - Scienze Economiche
	330322	Cl. Sc. Sociali - Scienze Politiche
	330224	Cl. Sc. Sperimentali - Agraria
	330225	Cl. Sc. Sperimentali - Ingegneria
	330226	Cl. Sc. Sperimentali - Medicina
	320004	Direzione Generale
	350039	Istituto di Biorobotica
	350037	Istituto di Diritto, Politica e Sviluppo
	350056	Istituto di Diritto, Politica e Sviluppo
	350035	Istituto di Economia
	005797	Istituto di Intelligenza Meccanica
	350036	Istituto di Management
	350038	Istituto di Scienze della Vita
	350034	Istituto di Tecnologie della Comunicazione, dell'Informazione e della Percezione
	350037	Istituto DIRPOLIS
	350034	Istituto TeCIP
	000000	Non assegnato
	350042	Personale Tecnico Biorobotica
	005798	Personale Tecnico Istituto di Intelligenza Meccanica
	350053	Personale Tecnico Management
	005568	Personale Tecnico Scienze della Vita
	350044	Personale Tecnico TeCIP
	170044	Scuola Sant'Anna
	170044	Scuola Sup. di Studi Univ. e Perfezionamento S.Anna di PISA
	005730	Servizi ICT
	005736	Staff
	005772	Staff
	005747	Staff del Rettore
	005737	U.O. Affari Legali
	005786	U.O. Allievi Ordinari e Lauree Magistrali
	005788	U.O. Alta Formazione
	005789	U.O. Biblioteca
	005722	U.O. Bilanci e Fiscalità
	005784	U.O. Comunicazione e Informazione
	005723	U.O. Contabilità
	005782	U.O. Coordinamento amministrativo-contabile Edilizia, infrastrutture e servizi
	005774	U.O. Coordinamento e supporto ricerca
	005790	U.O. Ecosistema dell'innovazione
	005751	U.O. Gare d'appalto
	005779	U.O. Gestione Organi e procedure elettorali
	005781	U.O. Gestione tecnica Infrastrutture e Servizi
	005783	U.O. Gestione Tecnica Sviluppo Edilizio
	005780	U.O. Infrastrutture e Servizi
	005777	U.O. Personale docente e di supporto alla ricerca
	005776	U.O. Personale Tecnico Amministrativo
	005787	U.O. PhD
	005791	U.O. Placement
	005773	U.O. Programmazione, Controllo, Performance e Qualità
	005775	U.O. Promozione e valutazione ricerca
	005728	U.O. Provveditorato
	005785	U.O. Relazioni internazionali
	005796	U.O. Ricerca Istituti di Management, Economia e Dirpolis
	005794	U.O. Ricerca Istituti Tecip e Scienze della Vita
	005794	U.O. Ricerca Istituti Tecip, Scienze della Vita e Intelligenza Meccanica
	005792	U.O. Ricerca Istituto Biorobotica
	005778	U.O. Segreterie, Organizzazione eventi e protocollo
	005795	U.O. Servizi amministrativo-contabili e logistici Istituti Management, Economia e Dirpolis
	005793	U.O. Servizi amministrativo-contabili e logistici Istituti Tecip, Scienze della Vita e Intelligenza Meccanica
	005738	U.O. Sicurezza e Ambiente
	005725	U.O. Valorizzazione Ricerca
	*/				
}
disp('<br><strong>FINE</strong>','elab',true);
disp(' - (end: '.date('d/m/Y H:i:s').')','fine');

file_put_contents($logf, date("Y/m/d H:i:s").' - END'."\r\n", FILE_APPEND | LOCK_EX);

function oraexec($sql,$fret=false,$t='o'){
	global $conn_new, $devel;
	if ($sql==''){return;}
	$a=explode(" ",strtolower($sql));
	$ret=false; if ($a[0]=='select'){$ret=true;}
	if ($fret === true){$ret=true;}
	$p = oci_parse($conn_new, $sql);
	if ($p){
		$x = oci_execute($p);
	}
	$e = oci_error($conn_new); 
	if (!empty($e)){disp('<br />'.$sql.'<br />'.mysqli_error(json_encode($e)),'err',true);}

	if ($ret){
		$result=array();
		if ($a[0]=='select'){
			if ($t=='o'){$result['nr'] = oci_fetch_all($p, $r);}
			else {$result['nr'] = oci_fetch_all($p, $r, null, null, OCI_FETCHSTATEMENT_BY_ROW);}
			$result['nc']=oci_num_fields($p);
			$result['result']=$r;
		} else {
			$result['nr'] = oci_num_rows($p);
		}
		return $result;
	}
}
function disp($m,$dom,$append=false){
	global $start, $logf;
	if ($dom=='err'){
		file_put_contents($logf, date("Y/m/d H:i:s").' - '.implode("\r\n",explode('<br />',$m))."\r\n", FILE_APPEND | LOCK_EX);
	}
	$ora=round(microtime(true));					// tempo attuale in secondi
	$ns=$ora - $start;
	$m=str_replace("'","\'",$m);
	$m=str_replace('"','\"',$m);
	$s = '<script type="text/javascript" temp="1">if ($("#'.$dom.'").length > 0){';
	if ($append){
		$s.='$("#'.$dom.'").append("'.$m.'");';
	} else {
		$s.='$("#'.$dom.'").html("'.$m.'");';
	}
	$s.='$("#sec").html("'.gmdate("H:i:s", $ns).'");'; // display avanzamento orario
	$s .='} 
	$("[temp]").remove(); 
	</script>';	
	echo $s;
	if (!empty(ob_get_level())){flush(); ob_flush();}
}
function disp_bar($p){
	$p=str_replace("'","\'",$p);
	$p=str_replace('"','\"',$p);
	$s = '<script type="text/javascript" temp="1">';
	$s.='$("#per").html("<h1>'.$p.'%</h1>");';
	$s.='$("#per").css("width", "'.$p.'%");';
	$s.='$("[temp]").remove();';
	$s.='</script>';
	echo $s;
	if (!empty(ob_get_level())){flush(); ob_flush();}
}
function get_alert($x='',$stl='dark',$small=false){
	$s='';
	if (empty($stl)){$stl='dark';}
	if ($small){
		$s.='<div class="alert-'.$stl.'">'.$x.'</div>';
	} else {
		$s.='<div class="row py-1 alert alert-'.$stl.'">';
			$s.='<div class="col-sm">'.$x.'</div>';
		$s.='</div>';		
	}
	return $s;
}
function convert_date($sdt,$ffrom='dd/mm/yyyy',$fto='yyyy-mm-dd'){
	// sdt = stringa data, ffrom = formato input, fto = formato output
	$sdt=substr($sdt,0,strlen($ffrom));
	$dd=substr($sdt,strpos($ffrom,'dd'),2);
	$mm=substr($sdt,strpos($ffrom,'mm'),2);
	$yyyy=substr($sdt,strpos($ffrom,'yyyy'),4);
	return str_replace('yyyy',$yyyy,str_replace('mm',$mm,str_replace('dd',$dd,$fto)));
}
function crt_filter($s,$sok){
/*
	unico carattere non ammesso è §
	restituisce la stringa $s depurata dei caratteri che non sono in $sok
	$sok può essere un codice che quì viene trasformato in una stringa
			- §ma = ABCDEFGHIJKLMNOPQRSTUVWXYZ
			- §mi = abcdefghijklmnopqrstuvwxyz
			- §nu = 0123456789
	$sok può essere un codice come sopra seguito da una stringa di caratteri ammessi
			- esempio: §ma§mi§-/()[]{} (significa maiuscole, minuscole - / e tutte le parentesi)

	esempi:
	echo '<br />'.crt_filter('alberto','§ma§Albo');			// =	lbo
	echo '<br />'.crt_filter('Alberto','§ma§Albo');			// =	Albo
	echo '<br />'.crt_filter('-()123EkeVeriA456','§ma');	// =	EVA
	echo '<br />'.crt_filter('-()123EkeVeriA456','§mi');	// =	keeri
	echo '<br />'.crt_filter('-()123EkeVeriA456','§nu');	// =	123456
	echo '<br />'.crt_filter('-()123EkeVeriA456','§ma§nu');	// =	123EVA456
*/

	$soko=$sok;
	if (empty($sok)){return $s;}
	if (stripos($sok,'§') === false){
		$sc=$sok;
	} else {
		$sc='';
		if (stripos($sok,'§ma') !== false){$sc.='ABCDEFGHIJKLMNOPQRSTUVWXYZ'; $sok=str_replace('§ma','',$sok);}
		if (stripos($sok,'§mi') !== false){$sc.='abcdefghijklmnopqrstuvwxyz'; $sok=str_replace('§mi','',$sok);}
		if (stripos($sok,'§nu') !== false){$sc.='0123456789'; $sok=str_replace('§nu','',$sok);}
		if (strlen($sok)>0){$sok=str_replace('§','',$sok); $sc.=$sok;}
	}
	// return $sc;
	$out='';
	for ($i=0; $i<strlen($s); $i++){
		if (strpos($sc,$s[$i]) !== false){$out.=$s[$i];}
	}
	// return $s.' - '.$soko.' - '.$sc.' - '.$out;
	return $out;
}
?>